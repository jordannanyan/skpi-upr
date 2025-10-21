<?php

namespace App\Http\Controllers;

use App\Http\Requests\LaporanSkpiSubmitRequest;
use App\Http\Requests\LaporanSkpiVerifyRequest;
use App\Http\Requests\LaporanSkpiDecisionRequest;
use App\Models\LaporanSkpi;
use App\Models\ApprovalLog;
use App\Services\LaporanSkpiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class LaporanSkpiController extends Controller
{
    public function __construct(private LaporanSkpiService $svc) {}

    /* =========================
       Listing & detail
       ========================= */

    // GET /api/laporan-skpi?nim=&prodi_id=&fakultas_id=&status=&q=&per_page=
    public function index(Request $req)
    {
        $per = (int) ($req->integer('per_page') ?: 25);

        $q = LaporanSkpi::query()
            ->with([
                'mhs:nim,nama_mahasiswa,id_prodi',
                'pengaju:id,username,role',
            ])
            ->ofNim($req->string('nim'))
            ->ofProdi($req->integer('prodi_id'))
            ->ofFakultas($req->integer('fakultas_id'))
            ->ofStatus($req->string('status'))
            ->when(trim((string)$req->string('q')) !== '', function($qq) use ($req){
                $kw = trim((string)$req->string('q'));
                $qq->where(function($w) use ($kw){
                    $w->where('no_pengesahan','like',"%{$kw}%")
                      ->orWhere('catatan_verifikasi','like',"%{$kw}%")
                      ->orWhere('nim','like',"%{$kw}%");
                });
            })
            ->latest('id');

        return response()->json($q->paginate($per)->appends($req->query()));
    }

    // GET /api/laporan-skpi/{id}
    public function show(int $id)
    {
        $row = LaporanSkpi::with([
            'mhs:nim,nama_mahasiswa,id_prodi',
            'pengaju:id,username,role',
            'approvals.actor:id,username,role',
        ])->findOrFail($id);

        return response()->json($row);
    }

    /* =========================
       Tahap 1: Submit (AdminJurusan/Kajur)
       ========================= */

    // POST /api/laporan-skpi/submit
    public function submit(LaporanSkpiSubmitRequest $req)
    {
        $user = $req->user();
        $this->mustRole($user->role, ['AdminJurusan','Kajur']);

        $data = $req->validated();

        // (opsional) larang duplikasi draft untuk nim sama yang masih berjalan
        $exists = LaporanSkpi::where('nim', $data['nim'])
            ->whereIn('status', [
                LaporanSkpi::ST_SUBMITTED,
                LaporanSkpi::ST_VERIFIED,
                LaporanSkpi::ST_WAKADEK,
            ])->exists();
        if ($exists) {
            return response()->json(['message' => 'Masih ada pengajuan berjalan untuk NIM ini'], 422);
        }

        $row = LaporanSkpi::create([
            'nim'            => $data['nim'],
            'id_pengaju'     => $user->id,
            'tgl_pengajuan'  => now()->toDateString(),
            'status'         => LaporanSkpi::ST_SUBMITTED,
            'catatan_verifikasi' => $req->input('catatan'),
            'versi_file'     => 0,
        ]);

        ApprovalLog::create([
            'laporan_id' => $row->id,
            'actor_id'   => $user->id,
            'actor_role' => $user->role,
            'action'     => ApprovalLog::ACT_SUBMITTED,
            'level'      => ApprovalLog::LVL_SUBMISSION,
            'note'       => $req->input('catatan'),
        ]);

        return response()->json($row->load('mhs','pengaju'), 201);
    }

    /* =========================
       Tahap 2: Verifikasi (AdminFakultas)
       - isi tgl/no pengesahan
       ========================= */

    // POST /api/laporan-skpi/{id}/verify
    public function verify(int $id, LaporanSkpiVerifyRequest $req)
    {
        $user = $req->user();
        $this->mustRole($user->role, ['AdminFakultas','SuperAdmin']);

        $row = LaporanSkpi::findOrFail($id);
        if ($row->status !== LaporanSkpi::ST_SUBMITTED) {
            return response()->json(['message' => 'Status tidak valid untuk verifikasi'], 422);
        }

        $data = $req->validated();

        $row->update([
            'tgl_pengesahan'    => $data['tgl_pengesahan'],
            'no_pengesahan'     => $data['no_pengesahan'],
            'catatan_verifikasi'=> $req->input('catatan_verifikasi'),
            'status'            => LaporanSkpi::ST_VERIFIED,
        ]);

        ApprovalLog::create([
            'laporan_id' => $row->id,
            'actor_id'   => $user->id,
            'actor_role' => $user->role,
            'action'     => ApprovalLog::ACT_VERIFIED,
            'level'      => ApprovalLog::LVL_SUBMISSION,
            'note'       => $req->input('catatan_verifikasi'),
        ]);

        return response()->json($row->load('mhs','pengaju'));
    }

    /* =========================
       Tahap 3: Keputusan Wakadek
       ========================= */

    // POST /api/laporan-skpi/{id}/wakadek
    public function decideWakadek(int $id, LaporanSkpiDecisionRequest $req)
    {
        $user = $req->user();
        $this->mustRole($user->role, ['Wakadek','SuperAdmin']);

        $row = LaporanSkpi::findOrFail($id);
        if ($row->status !== LaporanSkpi::ST_VERIFIED) {
            return response()->json(['message' => 'Status tidak valid untuk persetujuan Wakadek'], 422);
        }

        $data = $req->validated();

        if ($data['approve']) {
            $row->update(['status' => LaporanSkpi::ST_WAKADEK]);
            ApprovalLog::create([
                'laporan_id' => $row->id,
                'actor_id'   => $user->id,
                'actor_role' => $user->role,
                'action'     => ApprovalLog::ACT_APPROVED,
                'level'      => ApprovalLog::LVL_WAKADEK,
                'note'       => $req->input('note'),
            ]);
        } else {
            $row->update(['status' => LaporanSkpi::ST_REJECTED]);
            ApprovalLog::create([
                'laporan_id' => $row->id,
                'actor_id'   => $user->id,
                'actor_role' => $user->role,
                'action'     => ApprovalLog::ACT_REJECTED,
                'level'      => ApprovalLog::LVL_WAKADEK,
                'note'       => $req->input('note'),
            ]);
        }

        return response()->json($row->fresh());
    }

    /* =========================
       Tahap 4: Keputusan Dekan (+ generate file kalau approve)
       ========================= */

    // POST /api/laporan-skpi/{id}/dekan
    public function decideDekan(int $id, LaporanSkpiDecisionRequest $req)
    {
        $user = $req->user();
        $this->mustRole($user->role, ['Dekan','SuperAdmin']);

        $row = LaporanSkpi::findOrFail($id);
        if ($row->status !== LaporanSkpi::ST_WAKADEK) {
            return response()->json(['message' => 'Status tidak valid untuk persetujuan Dekan'], 422);
        }

        $data = $req->validated();

        if ($data['approve']) {
            $row->update(['status' => LaporanSkpi::ST_APPROVED]);
            ApprovalLog::create([
                'laporan_id' => $row->id,
                'actor_id'   => $user->id,
                'actor_role' => $user->role,
                'action'     => ApprovalLog::ACT_APPROVED,
                'level'      => ApprovalLog::LVL_DEKAN,
                'note'       => $req->input('note'),
            ]);

            // generate file final
            $this->svc->generateFile($row);
        } else {
            $row->update(['status' => LaporanSkpi::ST_REJECTED]);
            ApprovalLog::create([
                'laporan_id' => $row->id,
                'actor_id'   => $user->id,
                'actor_role' => $user->role,
                'action'     => ApprovalLog::ACT_REJECTED,
                'level'      => ApprovalLog::LVL_DEKAN,
                'note'       => $req->input('note'),
            ]);
        }

        return response()->json($row->fresh());
    }

    /* =========================
       Re-generate file (opsional, setelah approved)
       ========================= */

    // POST /api/laporan-skpi/{id}/regenerate
    public function regenerate(int $id, Request $req)
    {
        $user = $req->user();
        // izinkan SuperAdmin/AdminFakultas/Dekan untuk regenerate
        $this->mustRole($user->role, ['SuperAdmin','AdminFakultas','Dekan']);

        $row = LaporanSkpi::findOrFail($id);
        if ($row->status !== LaporanSkpi::ST_APPROVED) {
            return response()->json(['message' => 'Hanya laporan APPROVED yang bisa di-generate ulang'], 422);
        }

        $this->svc->generateFile($row);

        ApprovalLog::create([
            'laporan_id' => $row->id,
            'actor_id'   => $user->id,
            'actor_role' => $user->role,
            'action'     => ApprovalLog::ACT_REGENERATED,
            'level'      => null,
            'note'       => $req->input('note'),
        ]);

        return response()->json($row->fresh());
    }

    /* =========================
       Download file hasil generate
       ========================= */

    // GET /api/laporan-skpi/{id}/download
    public function download(int $id)
    {
        $row = LaporanSkpi::findOrFail($id);
        if (!$row->file_laporan) {
            return response()->json(['message' => 'File belum tersedia'], 404);
        }

        $rel = LaporanSkpi::dir().'/'.$row->file_laporan;

        /** @var \Illuminate\Filesystem\FilesystemAdapter $disk */
        $disk = Storage::disk('public');

        if (!$disk->exists($rel)) {
            return response()->json(['message' => 'File tidak ditemukan di storage'], 404);
        }

        // gunakan response()->download agar Intelephense happy
        return response()->download($disk->path($rel), $row->file_laporan);
    }

    /* =========================
       Helpers
       ========================= */

    private function mustRole(string $role, array $allowed): void
    {
        if (!in_array($role, $allowed, true)) {
            abort(response()->json(['message'=>'Forbidden'], 403));
        }
    }
}

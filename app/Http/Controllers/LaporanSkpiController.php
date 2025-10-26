<?php

namespace App\Http\Controllers;

use App\Http\Requests\LaporanSkpiDecisionRequest;
use App\Http\Requests\LaporanSkpiSubmitRequest;
use App\Models\ApprovalLog;
use App\Models\LaporanSkpi;
use App\Services\LaporanSkpiService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class LaporanSkpiController extends Controller
{
    public function __construct(private LaporanSkpiService $svc)
    {
    }

    /* =========================
       Listing & detail
       ========================= */

    // GET /api/laporan-skpi?nim=&prodi_id=&fakultas_id=&status=&q=&per_page=
    public function index(Request $req)
    {
        $per  = (int) ($req->integer('per_page') ?: 25);
        $user = $req->user();

        $q = LaporanSkpi::query()
            ->with([
                'mhs:nim,nama_mahasiswa,id_prodi',
                'mhs.prodi:id,nama_prodi,id_fakultas',
                'mhs.prodi.fakultas:id,nama_fakultas',
                'pengaju:id,username,role',
            ])
            ->ofNim($req->string('nim'))
            ->ofProdi($req->integer('prodi_id'))
            ->ofFakultas($req->integer('fakultas_id'))
            // status filter: case-insensitive
            ->when(trim((string)$req->string('status')) !== '', function ($qq) use ($req) {
                $st = strtolower((string)$req->string('status'));
                $qq->where(DB::raw('LOWER(status)'), $st);
            })
            ->when(trim((string) $req->string('q')) !== '', function ($qq) use ($req) {
                $kw = trim((string) $req->string('q'));
                $qq->where(function ($w) use ($kw) {
                    $w->where('no_pengesahan', 'like', "%{$kw}%")
                      ->orWhere('catatan_verifikasi', 'like', "%{$kw}%")
                      ->orWhere('nim', 'like', "%{$kw}%");
                });
            });

        // Paksa scope SELALU (abaikan manipulasi filter client)
        $this->applyScope($q, $user);

        $q->latest('id');

        return response()->json($q->paginate($per)->appends($req->query()));
    }

    // GET /api/laporan-skpi/{id}
    public function show(int $id, Request $req)
    {
        $user = $req->user();

        $row = LaporanSkpi::with([
            'mhs:nim,nama_mahasiswa,id_prodi',
            'pengaju:id,username,role',
            'approvals.actor:id,username,role',
        ])->findOrFail($id);

        // scope detail
        if ($user->isProdiScoped()) {
            abort_unless($this->inUserProdi($user->id_prodi, $row->nim), 403, 'Out of prodi scope');
        } elseif ($user->isFacultyScoped()) {
            abort_unless($this->inUserFak($user->id_fakultas, $row->id), 403, 'Out of faculty scope');
        }

        return response()->json($row);
    }

    /* =========================
       Tahap 1: Submit (AdminJurusan/Kajur)
       ========================= */

    // POST /api/laporan-skpi/submit
    public function submit(LaporanSkpiSubmitRequest $req)
    {
        $user = $req->user();
        $this->mustRole($user->role, ['AdminJurusan', 'Kajur', 'SuperAdmin']);

        $data = $req->validated();

        // scope: nim harus milik prodi user (untuk role prodi-scoped)
        if ($user->isProdiScoped()) {
            abort_unless($this->inUserProdi($user->id_prodi, $data['nim']), 403, 'Out of prodi scope');
        } elseif ($user->isFacultyScoped()) {
            // fakultas-scoped boleh submit untuk semua prodi di fakultasnya
            $idf = DB::table('ref_mahasiswa as m')
                ->join('ref_prodi as p', 'p.id', '=', 'm.id_prodi')
                ->where('m.nim', $data['nim'])
                ->value('p.id_fakultas');
            abort_unless($idf && (int)$idf === (int)$user->id_fakultas, 403, 'Out of faculty scope');
        }

        // larang duplikasi pengajuan berjalan (case-insensitive)
        $running = [
            LaporanSkpi::ST_SUBMITTED,
            LaporanSkpi::ST_VERIFIED,
            LaporanSkpi::ST_WAKADEK,
        ];
        $exists = LaporanSkpi::where('nim', $data['nim'])
            ->whereIn(DB::raw('LOWER(status)'), array_map('strtolower', $running))
            ->exists();
        if ($exists) {
            return response()->json(['message' => 'Masih ada pengajuan berjalan untuk NIM ini'], 422);
        }

        $row = LaporanSkpi::create([
            'nim' => $data['nim'],
            'id_pengaju' => $user->id,
            'tgl_pengajuan' => now()->toDateString(),
            'status' => LaporanSkpi::ST_SUBMITTED,
            'catatan_verifikasi' => $req->input('catatan'),
            'versi_file' => 0,
        ]);

        ApprovalLog::create([
            'laporan_id' => $row->id,
            'actor_id' => $user->id,
            'actor_role' => $user->role,
            'action' => ApprovalLog::ACT_SUBMITTED,
            'level' => ApprovalLog::LVL_SUBMISSION,
            'note' => $req->input('catatan'),
        ]);

        return response()->json($row->load('mhs', 'pengaju'), 201);
    }

    /* =========================
       Tahap 2: Verifikasi Kajur -> VERIFIED / REJECTED
       ========================= */

    // POST /api/laporan-skpi/{id}/verify  (KHUSUS KAJUR)
    public function verify(int $id, Request $req)
    {
        $user = $req->user();
        $this->mustRole($user->role, ['Kajur', 'SuperAdmin']);

        $row = LaporanSkpi::findOrFail($id);

        // scope prodi
        abort_unless($this->inUserProdi($user->id_prodi, $row->nim), 403, 'Out of prodi scope');

        if (!$this->isStatus($row, LaporanSkpi::ST_SUBMITTED)) {
            return response()->json(['message' => 'Status tidak valid untuk verifikasi Kajur'], 422);
        }

        $approve = filter_var($req->input('approve', true), FILTER_VALIDATE_BOOL);

        if ($approve) {
            $row->update(['status' => LaporanSkpi::ST_VERIFIED]);
            ApprovalLog::create([
                'laporan_id' => $row->id,
                'actor_id' => $user->id,
                'actor_role' => $user->role,
                'action' => ApprovalLog::ACT_VERIFIED,
                'level' => ApprovalLog::LVL_SUBMISSION,
                'note' => $req->input('note'),
            ]);
        } else {
            $row->update(['status' => LaporanSkpi::ST_REJECTED]);
            ApprovalLog::create([
                'laporan_id' => $row->id,
                'actor_id' => $user->id,
                'actor_role' => $user->role,
                'action' => ApprovalLog::ACT_REJECTED,
                'level' => ApprovalLog::LVL_SUBMISSION,
                'note' => $req->input('note'),
            ]);
        }

        return response()->json($row->fresh()->load('mhs', 'pengaju'));
    }

    /* =========================
       Pengesahan Admin Fakultas (isi no/tgl) — TANPA ubah status
       ========================= */

    // POST /api/laporan-skpi/{id}/pengesahan
    public function pengesahan(int $id, Request $req)
    {
        $user = $req->user();
        $this->mustRole($user->role, ['AdminFakultas', 'SuperAdmin']);

        $row = LaporanSkpi::findOrFail($id);

        // scope fakultas
        abort_unless($this->inUserFak($user->id_fakultas, $row->id), 403, 'Out of faculty scope');

        if (!$this->isStatus($row, LaporanSkpi::ST_VERIFIED)) {
            return response()->json(['message' => 'Harus status VERIFIED (oleh Kajur)'], 422);
        }

        $data = $req->validate([
            'no_pengesahan' => 'required|string|max:100',
            'tgl_pengesahan' => 'required|date',
            'catatan_verifikasi' => 'nullable|string|max:500',
        ]);

        $row->update([
            'tgl_pengesahan' => $data['tgl_pengesahan'],
            'no_pengesahan' => $data['no_pengesahan'],
            'catatan_verifikasi' => $data['catatan_verifikasi'] ?? $row->catatan_verifikasi,
        ]);

        ApprovalLog::create([
            'laporan_id' => $row->id,
            'actor_id' => $user->id,
            'actor_role' => $user->role,
            'action' => ApprovalLog::ACT_VERIFIED, // atau ACT_PENGESAHAN
            'level' => ApprovalLog::LVL_SUBMISSION,
            'note' => 'Input pengesahan oleh Admin Fakultas',
        ]);

        return response()->json($row->fresh());
    }

    /* =========================
       Tahap 3: Keputusan Wakadek
       ========================= */

    // POST /api/laporan-skpi/{id}/wakadek
    public function decideWakadek(int $id, LaporanSkpiDecisionRequest $req)
    {
        $user = $req->user();
        $this->mustRole($user->role, ['Wakadek', 'SuperAdmin']);

        $row = LaporanSkpi::findOrFail($id);

        // scope fakultas
        abort_unless($this->inUserFak($user->id_fakultas, $row->id), 403, 'Out of faculty scope');

        if (!$this->isStatus($row, LaporanSkpi::ST_VERIFIED)) {
            return response()->json(['message' => 'Status tidak valid untuk persetujuan Wakadek'], 422);
        }
        if (!$row->no_pengesahan || !$row->tgl_pengesahan) {
            return response()->json(['message' => 'No/Tgl pengesahan belum diisi Admin Fakultas'], 422);
        }

        $data = $req->validated();

        if ($data['approve']) {
            $row->update(['status' => LaporanSkpi::ST_WAKADEK]);
            ApprovalLog::create([
                'laporan_id' => $row->id,
                'actor_id' => $user->id,
                'actor_role' => $user->role,
                'action' => ApprovalLog::ACT_APPROVED,
                'level' => ApprovalLog::LVL_WAKADEK,
                'note' => $req->input('note'),
            ]);
        } else {
            $row->update(['status' => LaporanSkpi::ST_REJECTED]);
            ApprovalLog::create([
                'laporan_id' => $row->id,
                'actor_id' => $user->id,
                'actor_role' => $user->role,
                'action' => ApprovalLog::ACT_REJECTED,
                'level' => ApprovalLog::LVL_WAKADEK,
                'note' => $req->input('note'),
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
        $this->mustRole($user->role, ['Dekan', 'SuperAdmin']);

        $row = LaporanSkpi::findOrFail($id);

        // scope fakultas
        abort_unless($this->inUserFak($user->id_fakultas, $row->id), 403, 'Out of faculty scope');

        if (!$this->isStatus($row, LaporanSkpi::ST_WAKADEK)) {
            return response()->json(['message' => 'Status tidak valid untuk persetujuan Dekan'], 422);
        }

        $data = $req->validated();

        if ($data['approve']) {
            $row->update(['status' => LaporanSkpi::ST_APPROVED]);
            ApprovalLog::create([
                'laporan_id' => $row->id,
                'actor_id' => $user->id,
                'actor_role' => $user->role,
                'action' => ApprovalLog::ACT_APPROVED,
                'level' => ApprovalLog::LVL_DEKAN,
                'note' => $req->input('note'),
            ]);

            // generate file final
            $this->svc->generateFile($row);
        } else {
            $row->update(['status' => LaporanSkpi::ST_REJECTED]);
            ApprovalLog::create([
                'laporan_id' => $row->id,
                'actor_id' => $user->id,
                'actor_role' => $user->role,
                'action' => ApprovalLog::ACT_REJECTED,
                'level' => ApprovalLog::LVL_DEKAN,
                'note' => $req->input('note'),
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
        $this->mustRole($user->role, ['SuperAdmin', 'AdminFakultas', 'Dekan']);

        $row = LaporanSkpi::findOrFail($id);

        // scope fakultas
        if ($user->isFacultyScoped()) {
            abort_unless($this->inUserFak($user->id_fakultas, $row->id), 403, 'Out of faculty scope');
        } elseif ($user->isProdiScoped()) {
            // prodi-scoped tidak boleh regenerate
            abort(403, 'Forbidden');
        }

        if (!$this->isStatus($row, LaporanSkpi::ST_APPROVED)) {
            return response()->json(['message' => 'Hanya laporan APPROVED yang bisa di-generate ulang'], 422);
        }

        $this->svc->generateFile($row);

        ApprovalLog::create([
            'laporan_id' => $row->id,
            'actor_id' => $user->id,
            'actor_role' => $user->role,
            'action' => ApprovalLog::ACT_REGENERATED,
            'level' => null,
            'note' => $req->input('note'),
        ]);

        return response()->json($row->fresh());
    }

    /* =========================
       Download file hasil generate
       ========================= */

    // GET /api/laporan-skpi/{id}/download
    public function download(int $id, Request $req)
    {
        $user = $req->user();
        $row = LaporanSkpi::findOrFail($id);

        // scope check
        if ($user->isProdiScoped()) {
            abort_unless($this->inUserProdi($user->id_prodi, $row->nim), 403, 'Out of prodi scope');
        } elseif ($user->isFacultyScoped()) {
            abort_unless($this->inUserFak($user->id_fakultas, $row->id), 403, 'Out of faculty scope');
        }

        if (!$row->file_laporan) {
            return response()->json(['message' => 'File belum tersedia'], 404);
        }

        $rel = LaporanSkpi::dir() . '/' . $row->file_laporan;

        /** @var \Illuminate\Filesystem\FilesystemAdapter $disk */
        $disk = Storage::disk('public');

        if (!$disk->exists($rel)) {
            return response()->json(['message' => 'File tidak ditemukan di storage'], 404);
        }

        return response()->download($disk->path($rel), $row->file_laporan);
    }

    /* =========================
       DELETE /api/laporan-skpi/{id}  (SuperAdmin only)
       ========================= */
    public function destroy(int $id, Request $req)
    {
        $user = $req->user();
        $this->mustRole($user->role, ['SuperAdmin']); // hanya SuperAdmin

        $row = LaporanSkpi::findOrFail($id);

        DB::transaction(function () use ($row) {
            // hapus file jika ada
            if ($row->file_laporan) {
                $rel = LaporanSkpi::dir() . '/' . $row->file_laporan;
                $disk = Storage::disk('public');
                if ($disk->exists($rel)) {
                    $disk->delete($rel);
                }
            }

            // hapus approval logs (jika tidak cascade)
            if (method_exists($row, 'approvals')) {
                $row->approvals()->delete();
            }

            $row->delete();
        });

        return response()->json(['message' => 'Laporan dihapus']);
    }

    /* =========================
       Helpers
       ========================= */

    private function mustRole(string $role, array $allowed): void
    {
        abort_unless(in_array($role, $allowed, true), 403, 'Forbidden');
    }

    // case-insensitive status checker
    private function isStatus(LaporanSkpi $row, string $expected): bool
    {
        return strtolower((string)$row->status) === strtolower($expected);
    }

    // cek apakah NIM milik prodi tertentu
    private function inUserProdi(int $userProdiId, string $nim): bool
    {
        $idp = DB::table('ref_mahasiswa')->where('nim', $nim)->value('id_prodi');
        return $idp && (int) $idp === (int) $userProdiId;
    }

    // cek apakah laporan berada di fakultas user
    private function inUserFak(int $userFakId, int $laporanId): bool
    {
        $idf = DB::table('laporan_skpi as l')
            ->join('ref_mahasiswa as m', 'm.nim', '=', 'l.nim')
            ->join('ref_prodi as p', 'p.id', '=', 'm.id_prodi')
            ->where('l.id', $laporanId)
            ->value('p.id_fakultas');

        return $idf && (int) $idf === (int) $userFakId;
    }

    // Paksa scope ke query list
    private function applyScope(Builder $q, $user): void
    {
        if ($user->isProdiScoped()) {
            $q->whereHas('mhs', fn($mq) => $mq->where('id_prodi', $user->id_prodi));
        } elseif ($user->isFacultyScoped()) {
            $q->whereHas('mhs.prodi', fn($p) => $p->where('id_fakultas', $user->id_fakultas));
        }
    }
}

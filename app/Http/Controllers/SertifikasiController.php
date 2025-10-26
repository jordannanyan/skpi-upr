<?php

namespace App\Http\Controllers;

use App\Http\Requests\SertifikasiStoreRequest;
use App\Http\Requests\SertifikasiUpdateRequest;
use App\Models\Sertifikasi;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SertifikasiController extends Controller
{
    /**
     * GET /api/sertifikasi?nim=&prodi_id=&fakultas_id=&kategori=&nama=&q=&per_page=
     */
    public function index(Request $req)
    {
        $perPage = (int) $req->integer('per_page') ?: 25;
        $user = $req->user();

        $q = Sertifikasi::query()
            ->with([
                'mahasiswa:nim,nama_mahasiswa,id_prodi',
                'mahasiswa.prodi:id,nama_prodi,id_fakultas',
                'mahasiswa.prodi.fakultas:id,nama_fakultas',
            ])
            ->ofNim($req->string('nim'))
            ->ofNama($req->string('nama'))
            ->ofProdi($req->integer('prodi_id'))
            ->ofFakultas($req->integer('fakultas_id'))
            ->ofKategori($req->string('kategori'))
            ->search($req->string('q'));

        // Paksa scope sesuai role (abaikan manipulasi filter dari client)
        $this->applyScope($q, $user);

        $rows = $q->latest('id')
            ->paginate($perPage)
            ->appends($req->query());

        return response()->json($rows);
    }

    /**
     * GET /api/sertifikasi/{id}
     */
    public function show(int $id, Request $req)
    {
        $user = $req->user();

        $row = Sertifikasi::with([
            'mahasiswa:nim,nama_mahasiswa,id_prodi',
            'mahasiswa.prodi:id,nama_prodi,id_fakultas',
            'mahasiswa.prodi.fakultas:id,nama_fakultas',
        ])->findOrFail($id);

        $this->assertRowInScope($row, $user);

        return response()->json($row);
    }

    /**
     * ➕ GET /api/mahasiswa/{nim}/sertifikat
     * Shortcut by NIM untuk halaman detail SKPI.
     */
    public function indexByMahasiswa(string $nim, Request $req)
    {
        $user = $req->user();

        $q = Sertifikasi::query()
            ->with([
                'mahasiswa:nim,nama_mahasiswa,id_prodi',
                'mahasiswa.prodi:id,nama_prodi,id_fakultas',
                'mahasiswa.prodi.fakultas:id,nama_fakultas',
            ])
            ->ofNim($nim);

        $this->applyScope($q, $user);

        $rows = $q->orderBy('id', 'desc')->get();

        return response()->json($rows);
    }

    /**
     * POST /api/sertifikasi  (multipart/form-data)
     */
    public function store(SertifikasiStoreRequest $req)
    {
        $user = $req->user();
        $data = $req->validated();
        $dir  = Sertifikasi::dir();

        // Pastikan NIM berada di dalam scope user
        $this->assertNimInScope($data['nim'], $user);

        $file = $req->file('file');
        $safeSlug = Str::slug(substr($data['nama_sertifikasi'], 0, 60), '-');
        $ext = $file->getClientOriginalExtension();
        $filename = $data['nim'].'_'.$safeSlug.'_'.Str::random(6).'.'.$ext;

        /** @var \Illuminate\Filesystem\FilesystemAdapter $disk */
        $disk = Storage::disk('public');
        $disk->putFileAs($dir, $file, $filename);

        $row = Sertifikasi::create([
            'nim'                  => $data['nim'],
            'nama_sertifikasi'     => $data['nama_sertifikasi'],
            'kategori_sertifikasi' => $data['kategori_sertifikasi'],
            'file_sertifikat'      => $filename,
        ]);

        return response()->json(
            $row->fresh()->load([
                'mahasiswa:nim,nama_mahasiswa,id_prodi',
                'mahasiswa.prodi:id,nama_prodi,id_fakultas',
                'mahasiswa.prodi.fakultas:id,nama_fakultas',
            ]),
            201
        );
    }

    /**
     * PUT/PATCH /api/sertifikasi/{id}  (multipart opsional)
     */
    public function update(SertifikasiUpdateRequest $req, int $id)
    {
        $user = $req->user();
        $row  = Sertifikasi::findOrFail($id);
        $data = $req->validated();
        $dir  = Sertifikasi::dir();

        // Pastikan row berada dalam scope user
        $this->assertRowInScope($row, $user);

        /** @var \Illuminate\Filesystem\FilesystemAdapter $disk */
        $disk = Storage::disk('public');

        // Jika NIM diubah, pastikan masih dalam scope
        if (array_key_exists('nim', $data) && $data['nim'] !== $row->nim) {
            $this->assertNimInScope($data['nim'], $user);
        }

        if ($req->hasFile('file')) {
            if ($row->file_sertifikat) {
                $disk->delete($dir.'/'.$row->file_sertifikat);
            }
            $file = $req->file('file');
            $safeSlug = Str::slug(substr(($data['nama_sertifikasi'] ?? $row->nama_sertifikasi), 0, 60), '-');
            $nimForName = ($data['nim'] ?? $row->nim);
            $ext = $file->getClientOriginalExtension();
            $filename = $nimForName.'_'.$safeSlug.'_'.Str::random(6).'.'.$ext;
            $disk->putFileAs($dir, $file, $filename);
            $data['file_sertifikat'] = $filename;
        }

        $row->update($data);

        return response()->json(
            $row->fresh()->load([
                'mahasiswa:nim,nama_mahasiswa,id_prodi',
                'mahasiswa.prodi:id,nama_prodi,id_fakultas',
                'mahasiswa.prodi.fakultas:id,nama_fakultas',
            ])
        );
    }

    /**
     * DELETE /api/sertifikasi/{id}
     */
    public function destroy(int $id, Request $req)
    {
        $user = $req->user();
        $row = Sertifikasi::findOrFail($id);
        $dir = Sertifikasi::dir();

        // Pastikan row berada dalam scope user
        $this->assertRowInScope($row, $user);

        /** @var \Illuminate\Filesystem\FilesystemAdapter $disk */
        $disk = Storage::disk('public');

        if ($row->file_sertifikat) {
            $disk->delete($dir.'/'.$row->file_sertifikat);
        }
        $row->delete();

        return response()->json(['deleted'=>true]);
    }

    /**
     * GET /api/sertifikasi/{id}/download
     */
    public function download(int $id, Request $req)
    {
        $user = $req->user();
        $row = Sertifikasi::findOrFail($id);

        // Pastikan row berada dalam scope user
        $this->assertRowInScope($row, $user);

        if (!$row->file_sertifikat) {
            return response()->json(['message'=>'File not found'], 404);
        }

        $rel = Sertifikasi::dir().'/'.$row->file_sertifikat;

        /** @var \Illuminate\Filesystem\FilesystemAdapter $disk */
        $disk = Storage::disk('public');

        if (!$disk->exists($rel)) {
            return response()->json(['message'=>'File missing on storage'], 404);
        }

        return response()->download($disk->path($rel), $row->file_sertifikat);
    }

    /* =========================
       Helpers: Scope enforcement
       ========================= */

    /**
     * Terapkan scope ke query berdasarkan role user.
     */
    private function applyScope(Builder $q, $user): void
    {
        if ($user->isProdiScoped()) {
            $q->whereHas('mahasiswa', fn($mq) => $mq->where('id_prodi', $user->id_prodi));
        } elseif ($user->isFacultyScoped()) {
            $q->whereHas('mahasiswa.prodi', fn($p) => $p->where('id_fakultas', $user->id_fakultas));
        }
    }

    /**
     * Pastikan sebuah row berada dalam scope user.
     */
    private function assertRowInScope(Sertifikasi $row, $user): void
    {
        if ($user->isProdiScoped()) {
            $idp = DB::table('ref_mahasiswa')->where('nim', $row->nim)->value('id_prodi');
            abort_unless($idp && (int)$idp === (int)$user->id_prodi, 403, 'Out of prodi scope');
        } elseif ($user->isFacultyScoped()) {
            $idf = DB::table('ref_mahasiswa as m')
                ->join('ref_prodi as p', 'p.id', '=', 'm.id_prodi')
                ->where('m.nim', $row->nim)
                ->value('p.id_fakultas');
            abort_unless($idf && (int)$idf === (int)$user->id_fakultas, 403, 'Out of faculty scope');
        }
    }

    /**
     * Pastikan NIM berada dalam scope user (untuk store/update).
     */
    private function assertNimInScope(string $nim, $user): void
    {
        if ($user->isProdiScoped()) {
            $idp = DB::table('ref_mahasiswa')->where('nim', $nim)->value('id_prodi');
            abort_unless($idp && (int)$idp === (int)$user->id_prodi, 403, 'Out of prodi scope');
        } elseif ($user->isFacultyScoped()) {
            $idf = DB::table('ref_mahasiswa as m')
                ->join('ref_prodi as p', 'p.id', '=', 'm.id_prodi')
                ->where('m.nim', $nim)
                ->value('p.id_fakultas');
            abort_unless($idf && (int)$idf === (int)$user->id_fakultas, 403, 'Out of faculty scope');
        }
    }
}

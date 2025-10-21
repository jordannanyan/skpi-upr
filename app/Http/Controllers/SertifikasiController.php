<?php

namespace App\Http\Controllers;

use App\Http\Requests\SertifikasiStoreRequest;
use App\Http\Requests\SertifikasiUpdateRequest;
use App\Models\Sertifikasi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SertifikasiController extends Controller
{
    // GET /api/sertifikasi?nim=&prodi_id=&fakultas_id=&q=&per_page=
    public function index(Request $req)
    {
        $perPage = (int) $req->integer('per_page') ?: 25;

        $rows = Sertifikasi::query()
            ->with(['mahasiswa:nim,nama_mahasiswa,id_prodi'])
            ->ofNim($req->string('nim'))
            ->ofProdi($req->integer('prodi_id'))
            ->ofFakultas($req->integer('fakultas_id'))
            ->search($req->string('q'))
            ->latest('id')
            ->paginate($perPage)
            ->appends($req->query());

        return response()->json($rows);
    }

    // GET /api/sertifikasi/{id}
    public function show(int $id)
    {
        $row = Sertifikasi::with(['mahasiswa:nim,nama_mahasiswa,id_prodi'])->findOrFail($id);
        return response()->json($row);
    }

    // POST /api/sertifikasi  (multipart/form-data)
    public function store(SertifikasiStoreRequest $req)
    {
        $data = $req->validated();
        $dir  = Sertifikasi::dir();

        $file = $req->file('file');
        $safeSlug = Str::slug(substr($data['nama_sertifikasi'], 0, 60), '-');
        $filename = $data['nim'].'_'.$safeSlug.'_'.Str::random(6).'.'.$file->getClientOriginalExtension();

        /** @var \Illuminate\Filesystem\FilesystemAdapter $disk */
        $disk = Storage::disk('public');
        $disk->putFileAs($dir, $file, $filename);

        $row = Sertifikasi::create([
            'nim'                  => $data['nim'],
            'nama_sertifikasi'     => $data['nama_sertifikasi'],
            'kategori_sertifikasi' => $data['kategori_sertifikasi'],
            'file_sertifikat'      => $filename, // hanya nama file
        ]);

        return response()->json($row->fresh()->load('mahasiswa'), 201);
    }

    // PUT/PATCH /api/sertifikasi/{id}  (multipart opsional)
    public function update(SertifikasiUpdateRequest $req, int $id)
    {
        $row  = Sertifikasi::findOrFail($id);
        $data = $req->validated();
        $dir  = Sertifikasi::dir();

        /** @var \Illuminate\Filesystem\FilesystemAdapter $disk */
        $disk = Storage::disk('public');

        if ($req->hasFile('file')) {
            // hapus file lama jika ada
            if ($row->file_sertifikat) {
                $disk->delete($dir.'/'.$row->file_sertifikat);
            }
            $file = $req->file('file');
            $safeSlug = Str::slug(substr(($data['nama_sertifikasi'] ?? $row->nama_sertifikasi), 0, 60), '-');
            $filename = ($data['nim'] ?? $row->nim).'_'.$safeSlug.'_'.Str::random(6).'.'.$file->getClientOriginalExtension();
            $disk->putFileAs($dir, $file, $filename);
            $data['file_sertifikat'] = $filename;
        }

        $row->update($data);
        return response()->json($row->fresh()->load('mahasiswa'));
    }

    // DELETE /api/sertifikasi/{id}
    public function destroy(int $id)
    {
        $row = Sertifikasi::findOrFail($id);
        $dir = Sertifikasi::dir();

        /** @var \Illuminate\Filesystem\FilesystemAdapter $disk */
        $disk = Storage::disk('public');

        if ($row->file_sertifikat) {
            $disk->delete($dir.'/'.$row->file_sertifikat);
        }
        $row->delete();

        return response()->json(['deleted'=>true]);
    }

    // GET /api/sertifikasi/{id}/download
    public function download(int $id)
    {
        $row = Sertifikasi::findOrFail($id);
        if (!$row->file_sertifikat) {
            return response()->json(['message'=>'File not found'], 404);
        }

        $rel = Sertifikasi::dir().'/'.$row->file_sertifikat;

        /** @var \Illuminate\Filesystem\FilesystemAdapter $disk */
        $disk = Storage::disk('public');

        if (!$disk->exists($rel)) {
            return response()->json(['message'=>'File missing on storage'], 404);
        }

        // gunakan response()->download + path() agar Intelephense happy
        return response()->download($disk->path($rel), $row->file_sertifikat);
    }
}

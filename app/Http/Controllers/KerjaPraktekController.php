<?php

namespace App\Http\Controllers;

use App\Http\Requests\KpStoreRequest;
use App\Http\Requests\KpUpdateRequest;
use App\Models\KerjaPraktek;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class KerjaPraktekController extends Controller
{
    // GET /api/kp?nim=&prodi_id=&fakultas_id=&q=&per_page=
    public function index(Request $req)
    {
        $perPage = (int) $req->integer('per_page') ?: 25;

        $rows = KerjaPraktek::query()
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

    // GET /api/kp/{id}
    public function show(int $id)
    {
        $row = KerjaPraktek::with(['mahasiswa:nim,nama_mahasiswa,id_prodi'])->findOrFail($id);
        return response()->json($row);
    }

    // POST /api/kp  (multipart/form-data)
    public function store(KpStoreRequest $req)
    {
        $data = $req->validated();

        // simpan file -> storage/app/public/skpi/kp
        $file = $req->file('file');
        $dir  = KerjaPraktek::dir();

        // nama file aman: {nim}_{slug_kegiatan}_{uniq}.{ext}
        $safeSlug = Str::slug(substr($data['nama_kegiatan'], 0, 60), '-');
        $filename = $data['nim'] . '_' . $safeSlug . '_' . Str::random(6) . '.' . $file->getClientOriginalExtension();

        Storage::disk('public')->putFileAs($dir, $file, $filename);

        $row = KerjaPraktek::create([
            'nim'            => $data['nim'],
            'nama_kegiatan'  => $data['nama_kegiatan'],
            'file_sertifikat' => $filename, // hanya nama file
        ]);

        return response()->json($row->fresh()->load('mahasiswa'), 201);
    }

    // PATCH/PUT /api/kp/{id}
    public function update(KpUpdateRequest $req, int $id)
    {
        $row = KerjaPraktek::findOrFail($id);
        $data = $req->validated();

        // update file bila ada
        if ($req->hasFile('file')) {
            $dir = KerjaPraktek::dir();
            // hapus file lama (jika ada)
            if ($row->file_sertifikat) {
                Storage::disk('public')->delete($dir . '/' . $row->file_sertifikat);
            }
            $file = $req->file('file');
            $safeSlug = Str::slug(substr(($data['nama_kegiatan'] ?? $row->nama_kegiatan), 0, 60), '-');
            $filename = ($data['nim'] ?? $row->nim) . '_' . $safeSlug . '_' . Str::random(6) . '.' . $file->getClientOriginalExtension();
            Storage::disk('public')->putFileAs($dir, $file, $filename);
            $data['file_sertifikat'] = $filename; // simpan nama file baru
        }

        $row->update($data);

        return response()->json($row->fresh()->load('mahasiswa'));
    }

    // DELETE /api/kp/{id}
    public function destroy(int $id)
    {
        $row = KerjaPraktek::findOrFail($id);
        $dir = KerjaPraktek::dir();

        // hapus file fisik
        if ($row->file_sertifikat) {
            Storage::disk('public')->delete($dir . '/' . $row->file_sertifikat);
        }

        $row->delete();

        return response()->json(['deleted' => true]);
    }

    // (opsional) GET /api/kp/{id}/download
    public function download(int $id)
    {
        $row = KerjaPraktek::findOrFail($id);
        if (!$row->file_sertifikat) {
            return response()->json(['message' => 'File not found'], 404);
        }

        $relPath = KerjaPraktek::dir() . '/' . $row->file_sertifikat;

        /** @var \Illuminate\Filesystem\FilesystemAdapter $disk */
        $disk = Storage::disk('public');

        if (!$disk->exists($relPath)) {
            return response()->json(['message' => 'File missing on storage'], 404);
        }

        // gunakan response()->download + path() agar Intelephense happy
        return response()->download($disk->path($relPath), $row->file_sertifikat);
    }
}

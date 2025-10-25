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
    /**
     * GET /api/kp?nim=&nama=&prodi_id=&fakultas_id=&q=&per_page=
     */
    public function index(Request $req)
    {
        $perPage = (int) ($req->integer('per_page') ?: 25);

        $rows = KerjaPraktek::query()
            // Eager load sudah di model ($with), tapi ini menyempitkan kolom agar hemat payload
            ->with([
                'mahasiswa:nim,nama_mahasiswa,id_prodi',
                'mahasiswa.prodi:id,nama_prodi,id_fakultas',
                'mahasiswa.prodi.fakultas:id,nama_fakultas',
            ])
            ->ofNim($req->string('nim'))
            ->ofNama($req->string('nama'))                // filter nama mahasiswa
            ->ofProdi($req->integer('prodi_id'))
            ->ofFakultas($req->integer('fakultas_id'))
            ->search($req->string('q'))                   // cari di kegiatan/nim/nama/prodi/fakultas
            ->latest('id')
            ->paginate($perPage)
            ->appends($req->query());

        return response()->json($rows);
    }

    /**
     * GET /api/kp/{id}
     */
    public function show(int $id)
    {
        $row = KerjaPraktek::with([
            'mahasiswa:nim,nama_mahasiswa,id_prodi',
            'mahasiswa.prodi:id,nama_prodi,id_fakultas',
            'mahasiswa.prodi.fakultas:id,nama_fakultas',
        ])->findOrFail($id);

        return response()->json($row);
    }

    /**
     * POST /api/kp  (multipart/form-data)
     */
    public function store(KpStoreRequest $req)
    {
        $data = $req->validated();

        // simpan file
        $file = $req->file('file');
        $dir  = KerjaPraktek::dir();

        $safeSlug = Str::slug(substr($data['nama_kegiatan'], 0, 60), '-');
        $filename = $data['nim'] . '_' . $safeSlug . '_' . Str::random(6) . '.' . $file->getClientOriginalExtension();

        Storage::disk('public')->putFileAs($dir, $file, $filename);

        $row = KerjaPraktek::create([
            'nim'             => $data['nim'],
            'nama_kegiatan'   => $data['nama_kegiatan'],
            'file_sertifikat' => $filename,
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
     * PATCH/PUT /api/kp/{id}
     */
    public function update(KpUpdateRequest $req, int $id)
    {
        $row = KerjaPraktek::findOrFail($id);
        $data = $req->validated();

        if ($req->hasFile('file')) {
            $dir = KerjaPraktek::dir();

            if ($row->file_sertifikat) {
                Storage::disk('public')->delete($dir . '/' . $row->file_sertifikat);
            }

            $file = $req->file('file');
            $safeSlug = Str::slug(substr(($data['nama_kegiatan'] ?? $row->nama_kegiatan), 0, 60), '-');
            $filename = ($data['nim'] ?? $row->nim) . '_' . $safeSlug . '_' . Str::random(6) . '.' . $file->getClientOriginalExtension();
            Storage::disk('public')->putFileAs($dir, $file, $filename);
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
     * DELETE /api/kp/{id}
     */
    public function destroy(int $id)
    {
        $row = KerjaPraktek::findOrFail($id);
        $dir = KerjaPraktek::dir();

        if ($row->file_sertifikat) {
            Storage::disk('public')->delete($dir . '/' . $row->file_sertifikat);
        }

        $row->delete();

        return response()->json(['deleted' => true]);
    }

    /**
     * (opsional) GET /api/kp/{id}/download
     */
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

        return response()->download($disk->path($relPath), $row->file_sertifikat);
    }
}

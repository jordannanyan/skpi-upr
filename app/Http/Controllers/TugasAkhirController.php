<?php

namespace App\Http\Controllers;

use App\Http\Requests\TaStoreRequest;
use App\Http\Requests\TaUpdateRequest;
use App\Models\TugasAkhir;
use Illuminate\Http\Request;

class TugasAkhirController extends Controller
{
    /**
     * GET /api/ta?nim=&prodi_id=&fakultas_id=&q=&sort=&dir=&per_page=
     */
    public function index(Request $req)
    {
        $perPage = (int) ($req->integer('per_page') ?: 25);

        $sort = (string) ($req->string('sort') ?: 'id');
        $dir  = strtolower((string) $req->string('dir')) === 'desc' ? 'desc' : 'asc';
        $allowedSort = ['id','nim','kategori','judul','created_at','nama_mhs','nama_prodi','nama_fakultas'];
        if (!in_array($sort, $allowedSort, true)) $sort = 'id';

        $q = TugasAkhir::query()
            // Batasi kolom eager load agar hemat payload
            ->with([
                'mahasiswa:nim,nama_mahasiswa,id_prodi',
                'mahasiswa.prodi:id,nama_prodi,id_fakultas',
                'mahasiswa.prodi.fakultas:id,nama_fakultas',
            ])
            ->ofNim($req->string('nim'))
            ->ofProdi($req->integer('prodi_id'))
            ->ofFakultas($req->integer('fakultas_id'))
            ->search($req->string('q'));

        // Sorting (nama_* via subquery)
        if (in_array($sort, ['nama_mhs','nama_prodi','nama_fakultas'], true)) {
            $q->sort($sort, $dir);
        } else {
            $q->orderBy($sort, $dir);
        }

        $rows = $q->paginate($perPage)->appends($req->query());

        return response()->json($rows);
    }

    /**
     * GET /api/ta/{id}
     */
    public function show(int $id)
    {
        $row = TugasAkhir::with([
            'mahasiswa:nim,nama_mahasiswa,id_prodi',
            'mahasiswa.prodi:id,nama_prodi,id_fakultas',
            'mahasiswa.prodi.fakultas:id,nama_fakultas',
        ])->findOrFail($id);

        return response()->json($row);
    }

    /**
     * POST /api/ta
     */
    public function store(TaStoreRequest $req)
    {
        $row = TugasAkhir::create($req->validated());

        return response()->json(
            $row->load([
                'mahasiswa:nim,nama_mahasiswa,id_prodi',
                'mahasiswa.prodi:id,nama_prodi,id_fakultas',
                'mahasiswa.prodi.fakultas:id,nama_fakultas',
            ]),
            201
        );
    }

    /**
     * PUT/PATCH /api/ta/{id}
     */
    public function update(TaUpdateRequest $req, int $id)
    {
        $row = TugasAkhir::findOrFail($id);
        $row->update($req->validated());

        return response()->json(
            $row->load([
                'mahasiswa:nim,nama_mahasiswa,id_prodi',
                'mahasiswa.prodi:id,nama_prodi,id_fakultas',
                'mahasiswa.prodi.fakultas:id,nama_fakultas',
            ])
        );
    }

    /**
     * DELETE /api/ta/{id}
     */
    public function destroy(int $id)
    {
        $row = TugasAkhir::findOrFail($id);
        $row->delete();
        return response()->json(['deleted' => true]);
    }
}

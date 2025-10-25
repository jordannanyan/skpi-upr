<?php

namespace App\Http\Controllers;

use App\Http\Requests\CplStoreRequest;
use App\Http\Requests\CplUpdateRequest;
use App\Models\Cpl;
use Illuminate\Http\Request;

class CplController extends Controller
{
    /**
     * GET /api/cpl?q=&prodi_id=&fakultas_id=&sort=&dir=&per_page=&with_counts=1
     */
    public function index(Request $req)
    {
        $perPage    = (int) $req->integer('per_page') ?: 25;
        $withCounts = (bool) $req->boolean('with_counts');

        $q = Cpl::query()
            // batasi kolom eager load supaya hemat payload
            ->with([
                'prodi:id,nama_prodi,id_fakultas',
                'prodi.fakultas:id,nama_fakultas',
            ])
            ->ofProdi($req->integer('prodi_id'))
            ->ofFakultas($req->integer('fakultas_id'))
            ->search($req->string('q'));

        // sorting (termasuk nama_prodi/nama_fakultas)
        $q->sort($req->string('sort'), $req->string('dir'));

        if ($withCounts) {
            $q->withCount('skor');
        }

        return response()->json(
            $q->paginate($perPage)->appends($req->query())
        );
    }

    /**
     * GET /api/cpl/{kode}
     */
    public function show(string $kode)
    {
        $row = Cpl::query()
            ->with([
                'prodi:id,nama_prodi,id_fakultas',
                'prodi.fakultas:id,nama_fakultas',
            ])
            ->withCount('skor')
            ->findOrFail($kode);

        return response()->json($row);
    }

    /**
     * POST /api/cpl
     * Pastikan CplStoreRequest menambahkan validasi 'id_prodi' => 'nullable|integer|exists:ref_prodi,id'
     */
    public function store(CplStoreRequest $req)
    {
        $row = Cpl::create($req->validated());
        return response()->json(
            $row->load(['prodi:id,nama_prodi,id_fakultas','prodi.fakultas:id,nama_fakultas']),
            201
        );
    }

    /**
     * PUT/PATCH /api/cpl/{kode}
     * Pastikan CplUpdateRequest juga mengizinkan 'id_prodi'
     */
    public function update(CplUpdateRequest $req, string $kode)
    {
        $row = Cpl::findOrFail($kode);
        $row->update($req->validated());

        return response()->json(
            $row->load(['prodi:id,nama_prodi,id_fakultas','prodi.fakultas:id,nama_fakultas'])
        );
    }

    /**
     * DELETE /api/cpl/{kode}
     */
    public function destroy(string $kode)
    {
        $row = Cpl::findOrFail($kode);
        $row->delete();
        return response()->json(['deleted'=>true]);
    }
}

<?php

namespace App\Http\Controllers;

use App\Http\Requests\FakultasIndexRequest;
use App\Models\RefFakultas;

class FakultasController extends Controller
{
    /**
     * GET /api/fakultas?q=&sort=&dir=&per_page=&with_counts=1&with=prodi
     */
    public function index(FakultasIndexRequest $req)
    {
        $perPage    = (int)($req->integer('per_page') ?: 25);
        $withCounts = (bool)$req->boolean('with_counts');
        $with       = (string)$req->string('with');

        $q = RefFakultas::query()
            ->search($req->string('q'))
            ->sort($req->string('sort'), $req->string('dir'));

        // eager minimal untuk tampilkan nama, tidak berat
        if (str_contains($with, 'prodi')) {
            $q->with(['prodi:id,id_fakultas,nama_prodi,nama_singkat,jenis_jenjang']);
        }

        if ($withCounts) {
            // jumlah prodi via withCount bawaan
            $q->withCount('prodi');

            // jumlah mahasiswa: gunakan relasi hasManyThrough
            $q->withCount('mahasiswa');
            // hasilnya: prodi_count, mahasiswa_count di payload
        }

        $rows = $q->paginate($perPage)->appends($req->query());

        return response()->json($rows);
    }

    /**
     * GET /api/fakultas/{id}?with_counts=1&with=prodi
     */
    public function show($id)
    {
        $withCounts = (bool)request()->boolean('with_counts');
        $with       = (string)request()->string('with');

        $q = RefFakultas::query();

        if (str_contains($with, 'prodi')) {
            $q->with(['prodi:id,id_fakultas,nama_prodi,nama_singkat,jenis_jenjang']);
        }
        if ($withCounts) {
            $q->withCount(['prodi','mahasiswa']);
        }

        $row = $q->findOrFail((int)$id);

        return response()->json($row);
    }
}

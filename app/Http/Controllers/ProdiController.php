<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProdiIndexRequest;
use App\Models\RefProdi;

class ProdiController extends Controller
{
    /**
     * GET /api/prodi?fakultas_id=&q=&sort=&dir=&per_page=&with_counts=1
     */
    public function index(ProdiIndexRequest $req)
    {
        $perPage = (int)($req->integer('per_page') ?: 25);
        $withCounts = (bool)$req->boolean('with_counts');

        $q = RefProdi::query()
            ->with(['fakultas:id,nama_fakultas'])
            ->ofFakultas($req->integer('fakultas_id'))
            ->search($req->string('q'))
            ->sort($req->string('sort'), $req->string('dir'));

        if ($withCounts) {
            $q->withCount('mahasiswa');
        }

        $rows = $q->paginate($perPage)->appends($req->query());

        return response()->json($rows);
    }

    /**
     * GET /api/prodi/{id}?with_counts=1
     */
    public function show($id)
    {
        $withCounts = (bool)request()->boolean('with_counts');

        $q = RefProdi::query()->with(['fakultas:id,nama_fakultas']);
        if ($withCounts) $q->withCount('mahasiswa');

        $prodi = $q->findOrFail((int)$id);

        return response()->json($prodi);
    }
}

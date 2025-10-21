<?php

namespace App\Http\Controllers;

use App\Http\Requests\MahasiswaIndexRequest;
use App\Models\RefMahasiswa;

class MahasiswaController extends Controller
{
    /**
     * GET /api/mahasiswa?prodi_id=&fakultas_id=&q=&sort=&dir=&per_page=
     */
    public function index(MahasiswaIndexRequest $req)
    {
        $user = $req->user(); // kalau pakai auth

        $perPage = (int)($req->integer('per_page') ?: 25);

        $rows = RefMahasiswa::query()
            ->with(['prodi:id,id_fakultas,nama_prodi']) // eager load prodi (hemat query)
            ->byUserScope($user)                        // batasi sesuai role (opsional)
            ->ofProdi($req->integer('prodi_id'))
            ->ofFakultas($req->integer('fakultas_id'))
            ->search($req->string('q'))
            ->sort($req->string('sort'), $req->string('dir'))
            ->paginate($perPage)
            ->appends($req->query()); // mempertahankan query string di pagination links

        return response()->json($rows);
    }
}

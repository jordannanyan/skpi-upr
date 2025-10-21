<?php

namespace App\Http\Controllers;

use App\Http\Requests\TaStoreRequest;
use App\Http\Requests\TaUpdateRequest;
use App\Models\TugasAkhir;
use Illuminate\Http\Request;

class TugasAkhirController extends Controller
{
    // GET /api/ta?nim=&prodi_id=&fakultas_id=&q=&per_page=
    public function index(Request $req)
    {
        $perPage = (int) ($req->integer('per_page') ?: 25);

        $rows = TugasAkhir::query()
            ->with(['mahasiswa:nim,nama_mahasiswa,id_prodi'])
            ->when($req->filled('nim'), fn($q)=>$q->where('nim', $req->string('nim')))
            ->when($req->filled('prodi_id'), function($q) use($req){
                $q->whereHas('mahasiswa', fn($m)=>$m->where('id_prodi', (int)$req->integer('prodi_id')));
            })
            ->when($req->filled('fakultas_id'), function($q) use($req){
                $q->whereHas('mahasiswa.prodi', fn($p)=>$p->where('id_fakultas', (int)$req->integer('fakultas_id')));
            })
            ->when(trim((string)$req->string('q')) !== '', function($q) use($req){
                $kw = trim((string)$req->string('q'));
                $q->where(function($w) use ($kw){
                    $w->where('judul','like',"%{$kw}%")
                      ->orWhere('kategori','like',"%{$kw}%")
                      ->orWhere('nim','like',"%{$kw}%");
                });
            })
            ->latest('id')
            ->paginate($perPage)
            ->appends($req->query());

        return response()->json($rows);
    }

    // GET /api/ta/{id}
    public function show(int $id)
    {
        $row = TugasAkhir::with(['mahasiswa:nim,nama_mahasiswa,id_prodi'])->findOrFail($id);
        return response()->json($row);
    }

    // POST /api/ta
    public function store(TaStoreRequest $req)
    {
        $row = TugasAkhir::create($req->validated());
        return response()->json($row->load('mahasiswa'), 201);
    }

    // PUT/PATCH /api/ta/{id}
    public function update(TaUpdateRequest $req, int $id)
    {
        $row = TugasAkhir::findOrFail($id);
        $row->update($req->validated());
        return response()->json($row->load('mahasiswa'));
    }

    // DELETE /api/ta/{id}
    public function destroy(int $id)
    {
        $row = TugasAkhir::findOrFail($id);
        $row->delete();
        return response()->json(['deleted'=>true]);
    }
}

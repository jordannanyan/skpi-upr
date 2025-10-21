<?php

namespace App\Http\Controllers;

use App\Http\Requests\CplStoreRequest;
use App\Http\Requests\CplUpdateRequest;
use App\Models\Cpl;

class CplController extends Controller
{
    // GET /api/cpl?q=&sort=&dir=&per_page=&with_counts=1
    public function index()
    {
        $perPage = (int) request('per_page', 25);
        $withCounts = (bool) request()->boolean('with_counts');

        $q = Cpl::query()->search(request('q'))->sort(request('sort'), request('dir'));

        if ($withCounts) {
            $q->withCount('skor'); // jumlah baris skor
        }

        return response()->json(
            $q->paginate($perPage)->appends(request()->query())
        );
    }

    // GET /api/cpl/{kode}
    public function show(string $kode)
    {
        $row = Cpl::query()
            ->withCount('skor')
            ->findOrFail($kode);
        return response()->json($row);
    }

    // POST /api/cpl
    public function store(CplStoreRequest $req)
    {
        $row = Cpl::create($req->validated());
        return response()->json($row, 201);
    }

    // PUT/PATCH /api/cpl/{kode}
    public function update(CplUpdateRequest $req, string $kode)
    {
        $row = Cpl::findOrFail($kode);
        $row->update($req->validated());
        return response()->json($row);
    }

    // DELETE /api/cpl/{kode}
    public function destroy(string $kode)
    {
        $row = Cpl::findOrFail($kode);
        $row->delete();
        return response()->json(['deleted'=>true]);
    }
}

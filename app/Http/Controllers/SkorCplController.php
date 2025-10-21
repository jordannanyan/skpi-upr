<?php

namespace App\Http\Controllers;

use App\Http\Requests\SkorCplUpsertRequest;
use App\Models\SkorCpl;
use Illuminate\Support\Facades\DB;

class SkorCplController extends Controller
{
    // GET /api/cpl/{kode}/skor?nim=&per_page=
    public function indexByCpl(string $kode)
    {
        $perPage = (int) request('per_page', 50);

        $q = SkorCpl::query()
            ->where('kode_cpl', $kode)
            ->with(['mahasiswa:nim,nama_mahasiswa,id_prodi']);

        if ($nim = request('nim')) {
            $q->where('nim', $nim);
        }

        return response()->json(
            $q->orderBy('nim')->paginate($perPage)->appends(request()->query())
        );
    }

    // GET /api/mahasiswa/{nim}/skor-cpl
    public function indexByMahasiswa(string $nim)
    {
        $rows = SkorCpl::query()
            ->where('nim', $nim)
            ->with('cpl:kode_cpl,kategori,deskripsi')
            ->orderBy('kode_cpl')
            ->get();

        return response()->json($rows);
    }

    // POST /api/skr-cpl/upsert (single or batch)
    public function upsert(SkorCplUpsertRequest $req)
    {
        $items = $req->input('items');

        if ($items) {
            // batch upsert
            $payload = collect($items)->map(fn($x) => [
                'kode_cpl' => $x['kode_cpl'],
                'nim'      => $x['nim'],
                'skor'     => $x['skor'],
                'created_at' => now(),
                'updated_at' => now(),
            ])->all();

            foreach (array_chunk($payload, 500) as $chunk) {
                DB::table('skor_cpl')->upsert(
                    $chunk,
                    ['kode_cpl','nim'],
                    ['skor','updated_at']
                );
            }

            return response()->json(['upserted' => count($payload)]);
        }

        // single upsert
        $data = $req->validated();

        DB::table('skor_cpl')->upsert(
            [[
                'kode_cpl' => $data['kode_cpl'],
                'nim'      => $data['nim'],
                'skor'     => $data['skor'],
                'created_at' => now(),
                'updated_at' => now(),
            ]],
            ['kode_cpl','nim'],
            ['skor','updated_at']
        );

        return response()->json(['ok'=>true]);
    }

    // DELETE /api/cpl/{kode}/skor/{nim}
    public function destroy(string $kode, string $nim)
    {
        $deleted = SkorCpl::where('kode_cpl',$kode)->where('nim',$nim)->delete();
        return response()->json(['deleted' => (bool)$deleted]);
    }
}

<?php

namespace App\Http\Controllers;

use App\Services\UptTikSyncService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SyncController extends Controller
{
    // POST /api/sync/upttik/all
    public function all(Request $req, UptTikSyncService $svc)
    {
        // biar gak timeout di PHP
        @set_time_limit(0);
        @ini_set('max_execution_time', '0');

        // kalau mau lewatkan param paging/delta dari client:
        $params = $req->input('params');
        if (!is_array($params)) {
            $params = null; // aman
        }

        // jalankan semuanya, urut: fakultas -> prodi -> mahasiswa
        $f = $svc->syncFakultas($params);
        $p = $svc->syncProdi($params);
        $m = $svc->syncMahasiswa($params);

        // ringkasan sanity check
        $mhsTotal = (int) DB::table('ref_mahasiswa')->count();
        $usersMhs = (int) DB::table('users')->where('role', 'Mahasiswa')->count();

        return response()->json([
            'message' => 'Sinkronisasi selesai',
            'counts'  => [
                'fakultas'         => $f,
                'prodi'            => $p,
                'mahasiswa_synced' => $m,          // jumlah baris mhs yang diproses dari API (post-filter)
                'ref_mahasiswa'    => $mhsTotal,   // total di tabel referensi
                'users_mahasiswa'  => $usersMhs,   // total user role Mahasiswa setelah sync
            ],
        ]);
    }
}

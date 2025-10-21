<?php

namespace App\Http\Controllers;

use App\Services\UptTikSyncService;

class SyncController extends Controller
{
    public function all(UptTikSyncService $svc)
    {
        $out = $svc->syncAll();
        return back()->with('ok', "Sinkron selesai. Fakultas: {$out['fakultas']}, Prodi: {$out['prodi']}, Mahasiswa: {$out['mahasiswa']}");
    }
}

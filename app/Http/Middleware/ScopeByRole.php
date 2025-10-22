<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ScopeByRole
{
    public function handle(Request $request, Closure $next)
    {
        $u = $request->user();
        if (!$u) return $next($request);

        // Simpan scope ke request agar dipakai controller (via helper)
        if (in_array($u->role, ['AdminJurusan','Kajur']) && $u->id_prodi) {
            $request->attributes->set('scope_prodi_id', (int)$u->id_prodi);
        }
        if (in_array($u->role, ['AdminFakultas','Wakadek','Dekan']) && $u->id_fakultas) {
            $request->attributes->set('scope_fakultas_id', (int)$u->id_fakultas);
        }

        return $next($request);
    }
}

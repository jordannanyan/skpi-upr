<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Support\Facades\DB;


class Scope
{
    // mhs harus dalam prodi user (untuk AJ/Kajur)
    public static function inUserProdi(User $u, string $nim): bool
    {
        if (!$u->isProdiScoped()) return true; // non-prodi roles lolos
        $idp = DB::table('ref_mahasiswa')->where('nim',$nim)->value('id_prodi');
        return $idp && (int)$u->id_prodi === (int)$idp;
    }

    // laporan harus dalam fakultas user (untuk AF/Wakadek/Dekan)
    public static function inUserFak(User $u, int $laporanId): bool
    {
        if (!$u->isFacultyScoped()) return true;
        $idf = DB::table('laporan_skpi as l')
            ->join('ref_mahasiswa as m','m.nim','=','l.nim')
            ->join('ref_prodi as p','p.id','=','m.id_prodi')
            ->where('l.id',$laporanId)
            ->value('p.id_fakultas');
        return $idf && (int)$u->id_fakultas === (int)$idf;
    }
}

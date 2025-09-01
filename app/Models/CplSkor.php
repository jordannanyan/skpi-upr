<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CplSkor extends Model
{
    use HasFactory;

    protected $table = 'tb_cpl_skor';
    protected $primaryKey = 'id_cpl_skor';

    // Selama masa transisi, biarkan id_cpl tetap ada.
    // Setelah kolom id_cpl di-drop, hapus dari $fillable.
    protected $fillable = [
        'id_cpl_master',
        'id_cpl',          // LEGACY (boleh dihapus nanti)
        'id_mahasiswa',
        'skor_cpl',
        // 'catatan_indo',   // aktifkan jika kamu menambah kolom catatan
        // 'catatan_inggris'
    ];

    /** RELATIONS */

    // Relasi baru: skor -> master CPL (CPL1, CPL2, ...)
    public function cplMaster()
    {
        return $this->belongsTo(CplMaster::class, 'id_cpl_master', 'id_cpl_master');
    }

    // Relasi lama (kategori CPL: Sikap/PP/Umum/Khusus) — opsional dipertahankan saat transisi
    public function cpl()
    {
        return $this->belongsTo(Cpl::class, 'id_cpl', 'id_cpl');
    }

    public function mahasiswa()
    {
        return $this->belongsTo(Mahasiswa::class, 'id_mahasiswa', 'id_mahasiswa');
    }

    public function cetaks()
    {
        return $this->hasMany(Cetak::class, 'id_cpl_skor', 'id_cpl_skor');
    }
}

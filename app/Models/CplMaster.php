<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CplMaster extends Model
{
    use HasFactory;

    protected $table = 'tb_cpl_master';
    protected $primaryKey = 'id_cpl_master';
    public $timestamps = true;

    protected $fillable = [
        'id_prodi',
        'id_cpl',      // kategori opsional (Sikap/PP/Umum/Khusus)
        'kode',        // CPL1, CPL2, ...
        'nama_cpl',    // judul/ringkas (opsional)
        'deskripsi',   // deskripsi CPL (opsional)
        'status',      // 'aktif' | 'noaktif'
    ];

    /** RELATIONS */

    // Prodi pemilik CPL master
    public function prodi()
    {
        return $this->belongsTo(Prodi::class, 'id_prodi', 'id_prodi');
    }

    // Kategori CPL (opsional): Sikap/Penguasaan Pengetahuan/Keterampilan Umum/Keterampilan Khusus
    public function kategori()
    {
        return $this->belongsTo(Cpl::class, 'id_cpl', 'id_cpl');
    }

    // Daftar skor mahasiswa untuk CPL master ini
    public function skors()
    {
        return $this->hasMany(CplSkor::class, 'id_cpl_master', 'id_cpl_master');
    }

    /** SCOPES (opsional) */
    public function scopeAktif($query)
    {
        return $query->where('status', 'aktif');
    }
}

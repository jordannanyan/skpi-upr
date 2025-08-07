<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CplSkor extends Model
{
    use HasFactory;

    protected $table = 'tb_cpl_skor';
    protected $primaryKey = 'id_cpl_skor';
    protected $fillable = ['id_cpl', 'id_mahasiswa', 'skor_cpl'];

    public function cpl()
    {
        return $this->belongsTo(Cpl::class, 'id_cpl');
    }
    public function mahasiswa()
    {
        return $this->belongsTo(Mahasiswa::class, 'id_mahasiswa');
    }
    public function isiCapaian()
    {
        return $this->hasMany(IsiCapaian::class, 'id_cpl_skor');
    }
    public function cetaks()
    {
        return $this->hasMany(Cetak::class, 'id_cpl_skor');
    }
}

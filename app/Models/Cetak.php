<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Cetak extends Model
{
    use HasFactory;

    protected $table = 'tb_cetak';
    protected $primaryKey = 'id_cetak';
    protected $fillable = ['id_cpl_skor', 'id_pengesahan', 'status', 'tgl_cetak'];

    public function cplSkor() { return $this->belongsTo(CplSkor::class, 'id_cpl_skor'); }
    public function pengesahan() { return $this->belongsTo(Pengesahan::class, 'id_pengesahan'); }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IsiCapaian extends Model
{
    use HasFactory;

    protected $table = 'tb_isi_capaian';
    protected $primaryKey = 'id_capaian';
    protected $fillable = ['deskripsi_indo', 'id_cpl_skor', 'deskripsi_inggris'];

    public function cplSkor() { return $this->belongsTo(CplSkor::class, 'id_cpl_skor'); }
}
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KerjaPraktek extends Model
{
    use HasFactory;

    protected $table = 'tb_kerja_praktek';
    protected $primaryKey = 'id_kerja_praktek';
    protected $fillable = ['id_mahasiswa', 'nama_kegiatan', 'file_sertifikat'];

    public function mahasiswa() { return $this->belongsTo(Mahasiswa::class, 'id_mahasiswa'); }
}


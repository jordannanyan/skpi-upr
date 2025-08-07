<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Sertifikasi extends Model
{
    use HasFactory;

    protected $table = 'tb_sertifikasi';
    protected $primaryKey = 'id_sertifikasi';
    protected $fillable = ['id_mahasiswa', 'nama_sertifikasi', 'kategori_sertifikasi', 'file_sertifikat'];

    public function mahasiswa() { return $this->belongsTo(Mahasiswa::class, 'id_mahasiswa'); }
}

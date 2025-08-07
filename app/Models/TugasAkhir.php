<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TugasAkhir extends Model
{
    use HasFactory;

    protected $table = 'tb_tugas_akhir';
    protected $primaryKey = 'id_tugas_akhir';
    protected $fillable = ['id_mahasiswa', 'kategori', 'judul', 'file_halaman_dpn', 'file_lembar_pengesahan'];

    public function mahasiswa() { return $this->belongsTo(Mahasiswa::class, 'id_mahasiswa'); }
}
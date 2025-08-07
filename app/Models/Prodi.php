<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Sanctum\HasApiTokens;

class Prodi extends Model
{
    use HasFactory, HasApiTokens;

    protected $table = 'tb_prodi';
    protected $primaryKey = 'id_prodi';
    protected $fillable = ['id_fakultas', 'nama_prodi', 'username', 'password', 'akreditasi', 'sk_akre', 'jenis_jenjang', 'kompetensi_kerja', 'bahasa', 'penilaian', 'jenis_lanjutan', 'alamat'];

    public function fakultas() { return $this->belongsTo(Fakultas::class, 'id_fakultas'); }
    public function mahasiswas() { return $this->hasMany(Mahasiswa::class, 'id_prodi'); }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Sanctum\HasApiTokens;

class Mahasiswa extends Model
{
    use HasFactory, HasApiTokens;

    protected $table = 'tb_mahasiswa';
    protected $primaryKey = 'id_mahasiswa';
    protected $fillable = ['id_prodi', 'nama_mahasiswa', 'username', 'password', 'tgl_keluar', 'tgl_masuk', 'no_telp', 'alamat', 'tanggal_lahir', 'tempat_lahir', 'nim_mahasiswa'];

    public function prodi()
    {
        return $this->belongsTo(Prodi::class, 'id_prodi');
    }
    public function pengajuans()
    {
        return $this->hasMany(Pengajuan::class, 'id_mahasiswa');
    }
    public function cplSkors()
    {
        return $this->hasMany(CplSkor::class, 'id_mahasiswa');
    }
    public function kerjaPraktek()
    {
        return $this->hasMany(KerjaPraktek::class, 'id_mahasiswa');
    }
    public function tugasAkhir()
    {
        return $this->hasMany(TugasAkhir::class, 'id_mahasiswa');
    }
    public function sertifikasi()
    {
        return $this->hasMany(Sertifikasi::class, 'id_mahasiswa');
    }
}

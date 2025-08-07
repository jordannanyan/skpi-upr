<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Pengajuan extends Model
{
    use HasFactory;

    protected $table = 'tb_pengajuan';
    protected $primaryKey = 'id_pengajuan';
    protected $fillable = ['id_mahasiswa', 'id_kategori', 'status', 'tgl_pengajuan'];

    public function mahasiswa() { return $this->belongsTo(Mahasiswa::class, 'id_mahasiswa'); }
    public function kategori() { return $this->belongsTo(Kategori::class, 'id_kategori'); }
    public function pengesahan() { return $this->hasOne(Pengesahan::class, 'id_pengajuan'); }
}

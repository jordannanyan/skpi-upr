<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Kategori extends Model
{
    use HasFactory;

    protected $table = 'tb_kategori';
    protected $primaryKey = 'id_kategori';
    protected $fillable = ['nama_kategori', 'status'];

    public function pengajuans() { return $this->hasMany(Pengajuan::class, 'id_kategori'); }
}

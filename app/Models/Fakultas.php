<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Sanctum\HasApiTokens;

class Fakultas extends Model
{
    use HasFactory, HasApiTokens;

    protected $table = 'tb_fakultas';
    protected $primaryKey = 'id_fakultas';
    protected $fillable = ['nama_fakultas', 'username', 'password', 'nama_dekan', 'nip', 'alamat'];

    public function prodis()
    {
        return $this->hasMany(Prodi::class, 'id_fakultas');
    }
    
    public function pengesahan()
    {
        return $this->hasMany(Pengesahan::class, 'id_fakultas');
    }
}

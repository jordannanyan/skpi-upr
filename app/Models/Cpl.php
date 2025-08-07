<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Cpl extends Model
{
    use HasFactory;

    protected $table = 'tb_cpl';
    protected $primaryKey = 'id_cpl';
    protected $fillable = ['nama_cpl', 'status'];

    public function cplSkors() { return $this->hasMany(CplSkor::class, 'id_cpl'); }
}


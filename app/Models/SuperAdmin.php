<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Sanctum\HasApiTokens;

class SuperAdmin extends Model
{
    use HasFactory, HasApiTokens;

    protected $table = 'tb_super_admin';
    protected $primaryKey = 'id_super_admin';
    protected $fillable = ['username', 'password'];
}

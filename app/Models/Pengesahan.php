<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Pengesahan extends Model
{
    use HasFactory;

    protected $table = 'tb_pengesahan';
    protected $primaryKey = 'id_pengesahan';
    protected $fillable = ['id_fakultas', 'id_pengajuan', 'tgl_pengesahan', 'nomor_pengesahan'];

    public function fakultas() { return $this->belongsTo(Fakultas::class, 'id_fakultas'); }
    public function pengajuan() { return $this->belongsTo(Pengajuan::class, 'id_pengajuan'); }
    public function cetaks() { return $this->hasMany(Cetak::class, 'id_pengesahan'); }
}

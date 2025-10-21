<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TugasAkhir extends Model
{
    protected $table = 'tugas_akhir';
    protected $fillable = ['nim','kategori','judul'];

    public function mahasiswa(): BelongsTo { return $this->belongsTo(RefMahasiswa::class, 'nim', 'nim'); }
}

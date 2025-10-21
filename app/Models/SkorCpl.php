<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SkorCpl extends Model
{
    protected $table = 'skor_cpl';
    protected $fillable = ['nim', 'kode_cpl', 'skor'];

    protected $casts = ['skor' => 'decimal:2'];

    public function mahasiswa(): BelongsTo
    {
        return $this->belongsTo(RefMahasiswa::class, 'nim', 'nim');
    }
    public function cpl(): BelongsTo
    {
        return $this->belongsTo(Cpl::class, 'kode_cpl', 'kode_cpl');
    }
}

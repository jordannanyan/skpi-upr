<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cpl extends Model
{
    protected $table = 'cpl';
    protected $primaryKey = 'kode_cpl';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['kode_cpl','kategori','deskripsi'];

    public function skor(): HasMany
    {
        return $this->hasMany(SkorCpl::class, 'kode_cpl', 'kode_cpl');
    }

    /* ==== Scopes ==== */

    public function scopeSearch($q, ?string $kw)
    {
        $kw = trim((string)$kw);
        if ($kw !== '') {
            $q->where(function($w) use ($kw){
                $w->where('kode_cpl','LIKE',"%{$kw}%")
                  ->orWhere('kategori','LIKE',"%{$kw}%")
                  ->orWhere('deskripsi','LIKE',"%{$kw}%");
            });
        }
        return $q;
    }

    public function scopeSort($q, ?string $sort, ?string $dir = 'asc')
    {
        $sort = $sort ?: 'kode_cpl';
        $dir  = strtolower((string)$dir)==='desc' ? 'desc' : 'asc';
        $allowed = ['kode_cpl','kategori','created_at'];
        if (!in_array($sort,$allowed,true)) $sort = 'kode_cpl';
        return $q->orderBy($sort,$dir);
    }
}

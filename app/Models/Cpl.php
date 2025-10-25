<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Cpl extends Model
{
    protected $table = 'cpl';
    protected $primaryKey = 'kode_cpl';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['kode_cpl','kategori','deskripsi','id_prodi'];

    // auto eager-load agar akses nama prodi/fakultas tidak N+1
    protected $with = ['prodi.fakultas'];

    // sertakan field turunan ke JSON
    protected $appends = ['nama_prodi','nama_fakultas'];

    protected $casts = [
        'id_prodi' => 'integer',
    ];

    /* ===== Relasi ===== */
    public function skor(): HasMany
    {
        return $this->hasMany(SkorCpl::class, 'kode_cpl', 'kode_cpl');
    }

    public function prodi(): BelongsTo
    {
        return $this->belongsTo(RefProdi::class, 'id_prodi');
    }

    /* ===== Accessors ===== */
    public function getNamaProdiAttribute(): ?string
    {
        return optional($this->prodi)->nama_prodi;
    }

    public function getNamaFakultasAttribute(): ?string
    {
        return optional($this->prodi?->fakultas)->nama_fakultas;
    }

    /* ===== Scopes ===== */

    public function scopeOfProdi($q, ?int $idProdi)
    {
        if ($idProdi) $q->where('id_prodi', $idProdi);
        return $q;
    }

    public function scopeOfFakultas($q, ?int $idFakultas)
    {
        if ($idFakultas) {
            $q->whereHas('prodi', fn($p)=>$p->where('id_fakultas', $idFakultas));
        }
        return $q;
    }

    public function scopeSearch($q, ?string $kw)
    {
        $kw = trim((string)$kw);
        if ($kw !== '') {
            $q->where(function($w) use ($kw){
                $w->where('kode_cpl','LIKE',"%{$kw}%")
                  ->orWhere('kategori','LIKE',"%{$kw}%")
                  ->orWhere('deskripsi','LIKE',"%{$kw}%")
                  ->orWhereHas('prodi', fn($p)=>$p->where('nama_prodi','LIKE',"%{$kw}%"))
                  ->orWhereHas('prodi.fakultas', fn($f)=>$f->where('nama_fakultas','LIKE',"%{$kw}%"));
            });
        }
        return $q;
    }

    public function scopeSort($q, ?string $sort, ?string $dir = 'asc')
    {
        $sort = $sort ?: 'kode_cpl';
        $dir  = strtolower((string)$dir)==='desc' ? 'desc' : 'asc';

        $allowed = ['kode_cpl','kategori','created_at','nama_prodi','nama_fakultas'];
        if (!in_array($sort,$allowed,true)) $sort = 'kode_cpl';

        // sort by relasi via subquery portable
        if ($sort === 'nama_prodi') {
            return $q->orderByRaw(
                "(SELECT rp.nama_prodi FROM ref_prodi rp WHERE rp.id = cpl.id_prodi) {$dir}"
            );
        }
        if ($sort === 'nama_fakultas') {
            return $q->orderByRaw(
                "(SELECT rf.nama_fakultas FROM ref_fakultas rf WHERE rf.id = (SELECT rp.id_fakultas FROM ref_prodi rp WHERE rp.id = cpl.id_prodi)) {$dir}"
            );
        }

        return $q->orderBy($sort,$dir);
    }
}

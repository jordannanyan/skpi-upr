<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class RefFakultas extends Model
{
    protected $table = 'ref_fakultas';
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'int';

    protected $fillable = ['id','nama_fakultas','nama_dekan','nip','alamat'];

    /** Prodi di fakultas ini */
    public function prodi(): HasMany
    {
        return $this->hasMany(RefProdi::class, 'id_fakultas');
    }

    /** Mahasiswa melalui Prodi (hasManyThrough) */
    public function mahasiswa(): HasManyThrough
    {
        // fakultas.id -> prodi.id_fakultas, mahasiswa.id_prodi -> prodi.id
        return $this->hasManyThrough(
            RefMahasiswa::class,  // target
            RefProdi::class,      // through
            'id_fakultas',        // FK di ref_prodi yang merujuk fakultas
            'id_prodi',           // FK di ref_mahasiswa yang merujuk prodi
            'id',                 // PK fakultas (local key)
            'id'                  // PK prodi (local key on through)
        );
    }

    /* ===== Scopes ===== */

    public function scopeSearch($q, ?string $kw)
    {
        $kw = trim((string)$kw);
        if ($kw !== '') {
            $q->where(function ($w) use ($kw) {
                $w->where('nama_fakultas', 'LIKE', "%{$kw}%")
                  ->orWhere('nama_dekan', 'LIKE', "%{$kw}%")
                  ->orWhere('nip', 'LIKE', "%{$kw}%")
                  ->orWhere('alamat', 'LIKE', "%{$kw}%")
                  ->orWhere('id', 'LIKE', "%{$kw}%");
            });
        }
        return $q;
    }

    public function scopeSort($q, ?string $sort, ?string $dir = 'asc')
    {
        $sort = $sort ?: 'nama_fakultas';
        $dir  = strtolower((string)$dir) === 'desc' ? 'desc' : 'asc';
        $allowed = ['id','nama_fakultas','nama_dekan','nip','created_at'];
        if (!in_array($sort, $allowed, true)) $sort = 'nama_fakultas';
        return $q->orderBy($sort, $dir);
    }
}

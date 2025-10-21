<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RefProdi extends Model
{
    protected $table = 'ref_prodi';
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'int';

    protected $fillable = ['id','id_fakultas','nama_prodi','nama_singkat','jenis_jenjang'];

    public function fakultas(): BelongsTo
    {
        return $this->belongsTo(RefFakultas::class, 'id_fakultas');
    }

    public function mahasiswa(): HasMany
    {
        return $this->hasMany(RefMahasiswa::class, 'id_prodi');
    }

    /* ===== Scopes ===== */
    public function scopeOfFakultas($q, ?int $idFakultas)
    {
        if ($idFakultas) $q->where('id_fakultas', $idFakultas);
        return $q;
    }

    public function scopeSearch($q, ?string $kw)
    {
        $kw = trim((string)$kw);
        if ($kw !== '') {
            $q->where(function ($w) use ($kw) {
                $w->where('nama_prodi', 'LIKE', "%{$kw}%")
                  ->orWhere('nama_singkat', 'LIKE', "%{$kw}%")
                  ->orWhere('jenis_jenjang', 'LIKE', "%{$kw}%")
                  ->orWhere('id', 'LIKE', "%{$kw}%");
            });
        }
        return $q;
    }

    public function scopeSort($q, ?string $sort, ?string $dir = 'asc')
    {
        $sort = $sort ?: 'nama_prodi';
        $dir  = strtolower($dir)==='desc' ? 'desc' : 'asc';
        $allowed = ['id','nama_prodi','nama_singkat','jenis_jenjang','id_fakultas','created_at'];
        if (!in_array($sort, $allowed, true)) $sort = 'nama_prodi';
        return $q->orderBy($sort, $dir);
    }
}

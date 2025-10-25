<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RefProdi extends Model
{
    protected $table = 'ref_prodi';
    protected $primaryKey = 'id';

    /** 
     * Pertahankan sesuai kode awalmu. 
     * Jika 'id' auto-increment di database, set true.
     */
    public $incrementing = false;
    protected $keyType = 'int';

    protected $fillable = ['id','id_fakultas','nama_prodi','nama_singkat','jenis_jenjang'];

    /** 
     * Agar response JSON menyertakan 'nama_fakultas' langsung.
     */
    protected $appends = ['nama_fakultas'];

    /* =======================
     * Relationships
     * ======================= */
    public function fakultas(): BelongsTo
    {
        return $this->belongsTo(RefFakultas::class, 'id_fakultas');
    }

    public function mahasiswa(): HasMany
    {
        return $this->hasMany(RefMahasiswa::class, 'id_prodi');
    }

    /* =======================
     * Accessors
     * ======================= */
    public function getNamaFakultasAttribute(): ?string
    {
        return optional($this->fakultas)->nama_fakultas;
    }

    /* =======================
     * Scopes (Filters & Sort)
     * ======================= */

    public function scopeOfFakultas($q, ?int $idFakultas)
    {
        if ($idFakultas) {
            $q->where('id_fakultas', $idFakultas);
        }
        return $q;
    }

    public function scopeInFakultas($q, ?array $ids)
    {
        if (!empty($ids)) {
            $q->whereIn('id_fakultas', $ids);
        }
        return $q;
    }

    /**
     * Pencarian fleksibel:
     * - nama_prodi, nama_singkat, jenis_jenjang, id
     * - nama_fakultas via whereHas('fakultas')
     */
    public function scopeSearch($q, ?string $kw)
    {
        $kw = trim((string)$kw);
        if ($kw !== '') {
            $q->where(function ($w) use ($kw) {
                $w->where('nama_prodi', 'LIKE', "%{$kw}%")
                  ->orWhere('nama_singkat', 'LIKE', "%{$kw}%")
                  ->orWhere('jenis_jenjang', 'LIKE', "%{$kw}%")
                  ->orWhere('id', 'LIKE', "%{$kw}%")
                  ->orWhereHas('fakultas', function ($f) use ($kw) {
                      $f->where('nama_fakultas', 'LIKE', "%{$kw}%");
                  });
            });
        }
        return $q;
    }

    /**
     * Sort aman (whitelist). Tambahan: 'nama_fakultas'
     * Untuk sort 'nama_fakultas' dipakai subquery agar tidak mengubah select utama.
     */
    public function scopeSort($q, ?string $sort, ?string $dir = 'asc')
    {
        $sort = $sort ?: 'nama_prodi';
        $dir  = strtolower($dir) === 'desc' ? 'desc' : 'asc';

        $allowed = ['id','nama_prodi','nama_singkat','jenis_jenjang','id_fakultas','created_at','nama_fakultas'];
        if (!in_array($sort, $allowed, true)) {
            $sort = 'nama_prodi';
        }

        if ($sort === 'nama_fakultas') {
            // orderBy nama_fakultas via subquery (portable & aman)
            $q->orderByRaw(
                "(SELECT rf.nama_fakultas FROM ref_fakultas rf WHERE rf.id = {$this->table}.id_fakultas) {$dir}"
            );
            return $q;
        }

        return $q->orderBy($sort, $dir);
    }
}

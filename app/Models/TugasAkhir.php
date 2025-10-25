<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TugasAkhir extends Model
{
    protected $table = 'tugas_akhir';

    protected $fillable = ['nim','kategori','judul'];

    /**
     * Eager-load relasi sampai prodi & fakultas agar aksesors tidak N+1.
     */
    protected $with = [
        'mahasiswa:nim,nama_mahasiswa,id_prodi',
        'mahasiswa.prodi:id,nama_prodi,id_fakultas',
        'mahasiswa.prodi.fakultas:id,nama_fakultas',
    ];

    /**
     * Sertakan field turunan langsung di JSON.
     */
    protected $appends = ['nama_mhs','nama_prodi','nama_fakultas'];

    /* =======================
     * Relationships
     * ======================= */

    public function mahasiswa(): BelongsTo
    {
        return $this->belongsTo(RefMahasiswa::class, 'nim', 'nim');
    }

    // Alias opsional untuk konsistensi front-end (r.mhs.nama_mahasiswa)
    public function mhs(): BelongsTo
    {
        return $this->belongsTo(RefMahasiswa::class, 'nim', 'nim');
    }

    /* =======================
     * Accessors
     * ======================= */

    public function getNamaMhsAttribute(): ?string
    {
        return optional($this->mahasiswa)->nama_mahasiswa;
    }

    public function getNamaProdiAttribute(): ?string
    {
        return optional($this->mahasiswa?->prodi)->nama_prodi;
    }

    public function getNamaFakultasAttribute(): ?string
    {
        return optional($this->mahasiswa?->prodi?->fakultas)->nama_fakultas;
    }

    /* =======================
     * Scopes
     * ======================= */

    public function scopeOfNim($q, ?string $nim)
    {
        if ($nim) $q->where('nim', $nim);
        return $q;
    }

    /** Filter by Prodi id (via mahasiswa) */
    public function scopeOfProdi($q, ?int $idProdi)
    {
        if ($idProdi) $q->whereHas('mahasiswa', fn($m)=>$m->where('id_prodi', $idProdi));
        return $q;
    }

    /** Filter by Fakultas id (via prodi) */
    public function scopeOfFakultas($q, ?int $idFakultas)
    {
        if ($idFakultas) {
            $q->whereHas('mahasiswa.prodi', fn($p)=>$p->where('id_fakultas', $idFakultas));
        }
        return $q;
    }

    /** Pencarian: judul/kategori/NIM + nama mahasiswa/prodi/fakultas */
    public function scopeSearch($q, ?string $kw)
    {
        $kw = trim((string)$kw);
        if ($kw !== '') {
            $q->where(function($w) use ($kw){
                $w->where('judul', 'LIKE', "%{$kw}%")
                  ->orWhere('kategori', 'LIKE', "%{$kw}%")
                  ->orWhere('nim', 'LIKE', "%{$kw}%")
                  ->orWhereHas('mahasiswa', fn($m)=>$m->where('nama_mahasiswa','LIKE',"%{$kw}%"))
                  ->orWhereHas('mahasiswa.prodi', fn($p)=>$p->where('nama_prodi','LIKE',"%{$kw}%"))
                  ->orWhereHas('mahasiswa.prodi.fakultas', fn($f)=>$f->where('nama_fakultas','LIKE',"%{$kw}%"));
            });
        }
        return $q;
    }

    /** Sorting aman (whitelist) termasuk nama_mhs/prodi/fakultas */
    public function scopeSort($q, ?string $sort, ?string $dir = 'asc')
    {
        $sort = $sort ?: 'id';
        $dir  = strtolower($dir)==='desc' ? 'desc' : 'asc';

        $allowed = ['id','nim','kategori','judul','created_at','nama_mhs','nama_prodi','nama_fakultas'];
        if (!in_array($sort, $allowed, true)) $sort = 'id';

        if ($sort === 'nama_mhs') {
            $q->orderByRaw(
                "(SELECT rm.nama_mahasiswa FROM ref_mahasiswa rm WHERE rm.nim = {$this->table}.nim) {$dir}"
            );
            return $q;
        }
        if ($sort === 'nama_prodi') {
            $q->orderByRaw(
                "(SELECT rp.nama_prodi FROM ref_prodi rp WHERE rp.id = (SELECT rm.id_prodi FROM ref_mahasiswa rm WHERE rm.nim = {$this->table}.nim)) {$dir}"
            );
            return $q;
        }
        if ($sort === 'nama_fakultas') {
            $q->orderByRaw(
                "(SELECT rf.nama_fakultas
                   FROM ref_fakultas rf
                  WHERE rf.id = (
                        SELECT rp.id_fakultas
                          FROM ref_prodi rp
                         WHERE rp.id = (SELECT rm.id_prodi FROM ref_mahasiswa rm WHERE rm.nim = {$this->table}.nim)
                  )) {$dir}"
            );
            return $q;
        }

        return $q->orderBy($sort, $dir);
    }
}

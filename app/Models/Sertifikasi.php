<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Sertifikasi extends Model
{
    protected $table = 'sertifikasi';
    protected $fillable = ['nim','nama_sertifikasi','kategori_sertifikasi','file_sertifikat'];

    /**
     * Auto eager-load sampai fakultas untuk hindari N+1.
     * (Kolom dibatasi di controller.)
     */
    protected $with = ['mahasiswa.prodi.fakultas'];

    /**
     * Tambah field turunan ke JSON.
     */
    protected $appends = ['file_url','nama_mhs','nama_prodi','nama_fakultas'];

    /* ========= Relationships ========= */
    public function mahasiswa(): BelongsTo
    {
        return $this->belongsTo(RefMahasiswa::class, 'nim', 'nim');
    }

    public static function dir(): string
    {
        return 'skpi/sertifikasi';
    }

    /* ========= Accessors ========= */
    public function getFileUrlAttribute(): ?string
    {
        if (!$this->file_sertifikat) return null;
        $path = self::dir().'/'.$this->file_sertifikat;

        /** @var \Illuminate\Filesystem\FilesystemAdapter $disk */
        $disk = Storage::disk('public');

        // Jadikan absolut mengikuti host (termasuk :8000)
        $rel = $disk->url($path); // biasanya /storage/...
        return url($rel);
    }

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

    /* ========= Scopes ========= */
    public function scopeOfNim($q, ?string $nim)
    {
        if ($nim) $q->where('nim', $nim);
        return $q;
    }

    public function scopeOfNama($q, ?string $nama)
    {
        $nama = trim((string)$nama);
        if ($nama !== '') {
            $q->whereHas('mahasiswa', fn($m)=>$m->where('nama_mahasiswa','like',"%{$nama}%"));
        }
        return $q;
    }

    public function scopeOfProdi($q, ?int $idProdi)
    {
        if ($idProdi) $q->whereHas('mahasiswa', fn($m)=>$m->where('id_prodi',$idProdi));
        return $q;
    }

    public function scopeOfFakultas($q, ?int $idFakultas)
    {
        if ($idFakultas) {
            $q->whereHas('mahasiswa.prodi', fn($p)=>$p->where('id_fakultas',$idFakultas));
        }
        return $q;
    }

    public function scopeOfKategori($q, ?string $kat)
    {
        $kat = trim((string)$kat);
        if ($kat !== '') $q->where('kategori_sertifikasi', 'like', "%{$kat}%");
        return $q;
    }

    public function scopeSearch($q, ?string $kw)
    {
        $kw = trim((string)$kw);
        if ($kw !== '') {
            $q->where(function($w) use ($kw){
                $w->where('nama_sertifikasi','like',"%{$kw}%")
                  ->orWhere('kategori_sertifikasi','like',"%{$kw}%")
                  ->orWhere('nim','like',"%{$kw}%")
                  ->orWhereHas('mahasiswa', fn($m)=>$m->where('nama_mahasiswa','like',"%{$kw}%"))
                  ->orWhereHas('mahasiswa.prodi', fn($p)=>$p->where('nama_prodi','like',"%{$kw}%"))
                  ->orWhereHas('mahasiswa.prodi.fakultas', fn($f)=>$f->where('nama_fakultas','like',"%{$kw}%"));
            });
        }
        return $q;
    }
}

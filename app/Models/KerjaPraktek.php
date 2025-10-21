<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class KerjaPraktek extends Model
{
    protected $table = 'kerja_praktek';
    protected $fillable = ['nim', 'nama_kegiatan', 'file_sertifikat'];

    protected $appends = ['file_url']; // tampilkan url di JSON

    public function mahasiswa(): BelongsTo
    {
        return $this->belongsTo(RefMahasiswa::class, 'nim', 'nim');
    }

    // lokasi dasar di disk public
    public static function dir(): string
    {
        return 'skpi/kp';
    }


    /* ===== Scopes untuk filter ===== */
    public function scopeOfNim($q, ?string $nim)
    {
        if ($nim) $q->where('nim', $nim);
        return $q;
    }
    public function scopeOfProdi($q, ?int $idProdi)
    {
        if ($idProdi) $q->whereHas('mahasiswa', fn($m) => $m->where('id_prodi', $idProdi));
        return $q;
    }
    public function scopeOfFakultas($q, ?int $idFakultas)
    {
        if ($idFakultas) {
            $q->whereHas('mahasiswa.prodi', fn($p) => $p->where('id_fakultas', $idFakultas));
        }
        return $q;
    }
    public function scopeSearch($q, ?string $kw)
    {
        $kw = trim((string)$kw);
        if ($kw !== '') {
            $q->where(function ($w) use ($kw) {
                $w->where('nama_kegiatan', 'like', "%{$kw}%")
                    ->orWhere('nim', 'like', "%{$kw}%");
            });
        }
        return $q;
    }

    // + optional: use Illuminate\Filesystem\FilesystemAdapter;

    public function getFileUrlAttribute(): ?string
    {
        if (!$this->file_sertifikat) return null;

        $path = self::dir() . '/' . $this->file_sertifikat;

        /** @var \Illuminate\Filesystem\FilesystemAdapter $disk */
        $disk = Storage::disk('public');

        return $disk->url($path);
    }
}

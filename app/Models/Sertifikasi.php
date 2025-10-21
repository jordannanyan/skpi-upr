<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Sertifikasi extends Model
{
    protected $table = 'sertifikasi';
    protected $fillable = ['nim','nama_sertifikasi','kategori_sertifikasi','file_sertifikat'];
    protected $appends = ['file_url']; // tampilkan url file di JSON

    public function mahasiswa(): BelongsTo
    {
        return $this->belongsTo(RefMahasiswa::class, 'nim', 'nim');
    }

    public static function dir(): string
    {
        return 'skpi/sertifikasi';
    }

    // Intelephense-safe accessor
    public function getFileUrlAttribute(): ?string
    {
        if (!$this->file_sertifikat) return null;
        $path = self::dir().'/'.$this->file_sertifikat;
        /** @var \Illuminate\Filesystem\FilesystemAdapter $disk */
        $disk = Storage::disk('public');
        return $disk->url($path);
    }

    /* === Scopes === */
    public function scopeOfNim($q, ?string $nim)
    {
        if ($nim) $q->where('nim', $nim);
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
    public function scopeSearch($q, ?string $kw)
    {
        $kw = trim((string)$kw);
        if ($kw !== '') {
            $q->where(function($w) use ($kw){
                $w->where('nama_sertifikasi','like',"%{$kw}%")
                  ->orWhere('kategori_sertifikasi','like',"%{$kw}%")
                  ->orWhere('nim','like',"%{$kw}%");
            });
        }
        return $q;
    }
}

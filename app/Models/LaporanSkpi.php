<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class LaporanSkpi extends Model
{
    // Status (dipakai aplikasi). Disimpan UPPERCASE via mutator, dikembalikan lowercase via accessor.
    const ST_SUBMITTED = 'submitted';    // diajukan (admin jurusan)
    const ST_VERIFIED  = 'verified';     // diverifikasi (kajur) & diberi no/tgl pengesahan (admin fakultas)
    const ST_WAKADEK   = 'wakadek_ok';   // disetujui wakadek
    const ST_APPROVED  = 'approved';     // final (dekan ok) -> bisa cetak
    const ST_REJECTED  = 'rejected';     // ditolak di tahap manapun

    protected $table = 'laporan_skpi';

    protected $fillable = [
        'nim',
        'id_pengaju',
        'tgl_pengajuan',
        'tgl_pengesahan',
        'no_pengesahan',
        'status',
        'catatan_verifikasi',
        'file_laporan',
        'versi_file',
        'generated_at',
    ];

    protected $casts = [
        'tgl_pengajuan'  => 'date',
        'tgl_pengesahan' => 'date',
        'generated_at'   => 'datetime',
    ];

    protected $appends = ['file_url', 'nama_mhs', 'nama_prodi', 'nama_fakultas'];

    /* ===== Accessors & Mutators ===== */

    /**
     * Simpan status ke DB selalu UPPERCASE (cocok enum 'SUBMITTED', dst).
     */
    public function setStatusAttribute($value): void
    {
        $this->attributes['status'] = strtoupper((string) $value);
    }

    /**
     * Kembalikan status ke aplikasi sebagai lowercase (konsisten dengan konstanta & frontend).
     */
    public function getStatusAttribute($value): ?string
    {
        return is_null($value) ? null : strtolower((string) $value);
    }

    public function getNamaMhsAttribute(): ?string
    {
        return optional($this->mhs)->nama_mahasiswa;
    }

    public function getNamaProdiAttribute(): ?string
    {
        // pastikan RefMahasiswa punya relasi prodi(): BelongsTo RefProdi
        return optional(optional($this->mhs)->prodi)->nama_prodi;
    }

    public function getNamaFakultasAttribute(): ?string
    {
        // pastikan RefProdi punya relasi fakultas(): BelongsTo RefFakultas
        return optional(optional(optional($this->mhs)->prodi)->fakultas)->nama_fakultas;
    }

    public function getFileUrlAttribute(): ?string
    {
        if (!$this->file_laporan) return null;
        $path = self::dir() . '/' . $this->file_laporan;
        /** @var \Illuminate\Filesystem\FilesystemAdapter $disk */
        $disk = Storage::disk('public');
        return $disk->url($path);
    }

    /* ===== Relations ===== */

    public function mhs(): BelongsTo
    {
        return $this->belongsTo(RefMahasiswa::class, 'nim', 'nim');
    }

    public function pengaju(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_pengaju');
    }

    public function approvals(): HasMany
    {
        return $this->hasMany(ApprovalLog::class, 'laporan_id');
    }

    /* ===== Misc ===== */

    public static function dir(): string
    {
        return 'skpi/laporan';
    }

    /* ===== Scopes ===== */

    public function scopeOfNim($q, ?string $nim)
    {
        if ($nim) $q->where('nim', $nim);
        return $q;
    }

    public function scopeOfProdi($q, ?int $idProdi)
    {
        if ($idProdi) {
            $q->whereHas('mhs', fn($m) => $m->where('id_prodi', $idProdi));
        }
        return $q;
    }

    public function scopeOfFakultas($q, ?int $idFakultas)
    {
        if ($idFakultas) {
            $q->whereHas('mhs.prodi', fn($p) => $p->where('id_fakultas', $idFakultas));
        }
        return $q;
    }

    /**
     * Filter status case-insensitive (kompatibel data lama UPPERCASE).
     */
    public function scopeOfStatus($q, ?string $st)
    {
        if ($st !== null && $st !== '') {
            $q->where(DB::raw('LOWER(status)'), strtolower($st));
        }
        return $q;
    }
}

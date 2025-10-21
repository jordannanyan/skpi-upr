<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RefMahasiswa extends Model
{
    protected $table = 'ref_mahasiswa';
    protected $primaryKey = 'nim';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'nim','id_prodi','nama_mahasiswa','tgl_masuk','tgl_yudisium',
        'no_telp','alamat','tanggal_lahir','tempat_lahir'
    ];

    protected $casts = [
        'tgl_masuk'      => 'date',
        'tgl_yudisium'   => 'date',
        'tanggal_lahir'  => 'date',
    ];

    /* =======================
     * Relationships
     * ======================= */
    public function prodi(): BelongsTo
    {
        return $this->belongsTo(RefProdi::class, 'id_prodi');
    }

    /* =======================
     * Query Scopes (Filters)
     * ======================= */

    /** Filter by single Prodi id */
    public function scopeOfProdi($q, ?int $idProdi)
    {
        if ($idProdi) {
            $q->where('id_prodi', $idProdi);
        }
        return $q;
    }

    /** Filter by multiple Prodi ids */
    public function scopeInProdi($q, ?array $ids)
    {
        if (!empty($ids)) {
            $q->whereIn('id_prodi', $ids);
        }
        return $q;
    }

    /** Filter by Fakultas id (via relasi prodi) */
    public function scopeOfFakultas($q, ?int $idFakultas)
    {
        if ($idFakultas) {
            $q->whereHas('prodi', fn($p) => $p->where('id_fakultas', $idFakultas));
        }
        return $q;
    }

    /** Filter by multiple Fakultas ids */
    public function scopeInFakultas($q, ?array $ids)
    {
        if (!empty($ids)) {
            $q->whereHas('prodi', fn($p) => $p->whereIn('id_fakultas', $ids));
        }
        return $q;
    }

    /** Simple keyword search (nim / nama / tempat lahir) */
    public function scopeSearch($q, ?string $keyword)
    {
        $kw = trim((string)$keyword);
        if ($kw !== '') {
            $q->where(function($w) use ($kw) {
                $w->where('nim', 'LIKE', "%{$kw}%")
                  ->orWhere('nama_mahasiswa', 'LIKE', "%{$kw}%")
                  ->orWhere('tempat_lahir', 'LIKE', "%{$kw}%");
            });
        }
        return $q;
    }

    /** Sorting aman (whitelist kolom) */
    public function scopeSort($q, ?string $sort, ?string $dir = 'asc')
    {
        $sort = $sort ?: 'nama_mahasiswa';
        $dir  = strtolower($dir) === 'desc' ? 'desc' : 'asc';

        $allowed = ['nim','nama_mahasiswa','tgl_masuk','tgl_yudisium','id_prodi','created_at'];
        if (!in_array($sort, $allowed, true)) $sort = 'nama_mahasiswa';

        return $q->orderBy($sort, $dir);
    }

    /** Batasi data sesuai role user (optional) */
    public function scopeByUserScope($q, $user)
    {
        if (!$user) return $q;
        // Admin Fakultas / Dekan / Wakadek → filter fakultas
        if (method_exists($user, 'isFacultyScoped') && $user->isFacultyScoped() && $user->id_fakultas) {
            $q->ofFakultas($user->id_fakultas);
        }
        // Kajur / Admin Jurusan → filter prodi
        if (method_exists($user, 'isProdiScoped') && $user->isProdiScoped() && $user->id_prodi) {
            $q->ofProdi($user->id_prodi);
        }
        return $q;
    }
}

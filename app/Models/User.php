<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Notifications\Notifiable;
// kalau mau Sanctum nanti:
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use Notifiable;
    use HasApiTokens; // aman meski belum dipakai sekarang

    const ROLES = ['SuperAdmin','Dekan','Wakadek','Kajur','AdminFakultas','AdminJurusan'];

    protected $table = 'users';
    protected $fillable = ['role','username','password','id_fakultas','id_prodi','remember_token'];
    protected $hidden   = ['password','remember_token'];
    protected $casts = [
        'id_fakultas' => 'integer',
        'id_prodi'    => 'integer',
    ];

    # relasi
    public function fakultas(): BelongsTo { return $this->belongsTo(RefFakultas::class, 'id_fakultas'); }
    public function prodi(): BelongsTo    { return $this->belongsTo(RefProdi::class, 'id_prodi'); }

    # scope helper buat query data lain
    public function isFacultyScoped(): bool { return in_array($this->role, ['Dekan','Wakadek','AdminFakultas'], true); }
    public function isProdiScoped(): bool   { return in_array($this->role, ['Kajur','AdminJurusan'], true); }
    public function isSuperAdmin(): bool    { return $this->role === 'SuperAdmin'; }

    # hash password otomatis saat set
    public function setPasswordAttribute($value): void
    {
        if (!$value) { return; }
        // hanya re-hash kalau belum di-hash
        $this->attributes['password'] = \Illuminate\Support\Facades\Hash::needsRehash($value)
            ? \Illuminate\Support\Facades\Hash::make($value)
            : $value;
    }

    # normalisasi username ke lowercase & trim
    public function setUsernameAttribute($value): void
    {
        $this->attributes['username'] = strtolower(trim((string)$value));
    }
}

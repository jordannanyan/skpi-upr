<?php

namespace App\Services;

use App\Models\RefFakultas;
use App\Models\RefProdi;
use App\Models\RefMahasiswa;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class UptTikSyncService
{
    public function __construct(private UptTikClient $client) {}

    /* ========== Helpers ========== */

    private function norm(?string $v): ?string
    {
        if ($v === null) return null;
        $v = trim($v);
        return $v === '' ? null : $v;
    }

    private function normDate($v): ?string
    {
        if (!$v) return null;
        $v = trim((string)$v);
        if ($v === '0000-00-00' || $v === '0000-00-00 00:00:00') return null;
        // Ambil 10 char pertama format YYYY-MM-DD
        if (strlen($v) >= 10 && preg_match('/^\d{4}-\d{2}-\d{2}/', $v)) {
            return substr($v, 0, 10);
        }
        return null;
    }

    private function hashRow(array $row, array $keys): string
    {
        $payload = [];
        foreach ($keys as $k) {
            $payload[$k] = $row[$k] ?? null;
        }
        return hash('sha256', json_encode($payload));
    }
    /* ========== Orchestrators ========== */

    public function syncAll(?string $key = null): array
    {
        // Order matters: Fakultas -> Prodi -> Mahasiswa
        $f = $this->syncFakultas($key);
        $p = $this->syncProdi($key);
        $m = $this->syncMahasiswa($key);

        return ['fakultas' => $f, 'prodi' => $p, 'mahasiswa' => $m];
    }

    // (Optional) run selected groups in one call
    public function syncSelected(array $what = ['fakultas', 'prodi', 'mahasiswa'], ?string $key = null): array
    {
        $out = ['fakultas' => 0, 'prodi' => 0, 'mahasiswa' => 0];
        if (in_array('fakultas', $what, true))  $out['fakultas']  = $this->syncFakultas($key);
        if (in_array('prodi', $what, true))     $out['prodi']     = $this->syncProdi($key);
        if (in_array('mahasiswa', $what, true)) $out['mahasiswa'] = $this->syncMahasiswa($key);
        return $out;
    }

    /* ========== Fakultas ========== */

    public function syncFakultas(?string $key = null): int
    {
        $lock = Cache::lock('sync:upttik:fakultas', 300);
        if (!$lock->get()) return 0;

        try {
            $rows = collect($this->client->getFakultas($key))
                ->filter(fn($r) => isset($r['id_fakultas']))
                ->map(function ($r) {
                    $idf  = (int)$r['id_fakultas'];
                    // normalize: kosong -> fallback "UNKNOWN-{id}"
                    $nama = isset($r['nama_fakultas']) ? trim((string)$r['nama_fakultas']) : '';
                    if ($nama === '') {
                        $nama = "UNKNOWN-{$idf}";
                    }

                    return [
                        'id'            => $idf,
                        'nama_fakultas' => $nama,  // <-- tidak pernah NULL lagi
                        'nama_dekan'    => $this->norm($r['nama_dekan'] ?? null),
                        'nip'           => $this->norm($r['nip'] ?? null),
                        'alamat'        => $this->norm($r['alamat'] ?? null),
                        'created_at'    => now(),
                        'updated_at'    => now(),
                    ];
                })
                ->unique('id')
                ->values();

            foreach ($rows->chunk(500) as $chunk) {
                DB::table('ref_fakultas')->upsert(
                    $chunk->all(),
                    ['id'],
                    ['nama_fakultas', 'nama_dekan', 'nip', 'alamat', 'updated_at']
                );
            }

            return $rows->count();
        } finally {
            optional($lock)->release();
        }
    }


    /* ========== Prodi ========== */

    public function syncProdi(?string $key = null): int
    {
        $lock = Cache::lock('sync:upttik:prodi', 300);
        if (!$lock->get()) return 0;

        try {
            $rows = collect($this->client->getProdi($key))
                ->filter(fn($r) => isset($r['id_prodi']))
                ->map(function ($r) {
                    $idp = (int)$r['id_prodi'];
                    return [
                        'id'            => $idp,
                        'id_fakultas'   => (int)($r['id_fakultas'] ?? 0),
                        'nama_prodi'    => $this->norm($r['nama_prodi'] ?? null) ?? "UNKNOWN-{$idp}",
                        'nama_singkat'  => $this->norm($r['nama_singkat'] ?? null),
                        'jenis_jenjang' => $this->norm($r['jenis_jenjang'] ?? null),
                        'created_at'    => now(),
                        'updated_at'    => now(),
                    ];
                })
                ->unique('id')
                ->values();

            // Pastikan fakultas referensi ada minimal (kalau ada id_fakultas=0 biarkan, FK RESTRICT akan menjaga)
            foreach ($rows->chunk(500) as $chunk) {
                DB::table('ref_prodi')->upsert($chunk->all(), ['id'], [
                    'id_fakultas',
                    'nama_prodi',
                    'nama_singkat',
                    'jenis_jenjang',
                    'updated_at'
                ]);
            }

            return $rows->count();
        } finally {
            optional($lock)->release();
        }
    }

    /* ========== Mahasiswa ========== */

    public function syncMahasiswa(?string $key = null): int
    {
        $lock = \Illuminate\Support\Facades\Cache::lock('sync:upttik:mahasiswa', 600);
        if (!$lock->get()) return 0;

        // helper
        $norm = function ($v) {
            if ($v === null) return null;
            $v = trim((string)$v);
            return $v === '' ? null : $v;
        };
        $normDate = function ($v) {
            if (!$v) return null;
            $v = trim((string)$v);
            if ($v === '0000-00-00' || $v === '0000-00-00 00:00:00') return null;
            if (strlen($v) >= 10 && preg_match('/^\d{4}-\d{2}-\d{2}/', $v)) return substr($v, 0, 10);
            return null;
        };

        try {
            $raw = collect($this->client->getMahasiswa($key));

            // kumpulkan prodi/fakultas dari nested untuk di-upsert dulu
            $upsertFak = [];
            $upsertPro = [];

            $rows = $raw
                ->filter(function ($r) {
                    $nim = $r['id_mahasiswa'] ?? $r['nim'] ?? null;
                    $idp = $r['id_prodi'] ?? null;
                    return !empty($nim) && !empty($idp); // wajib keduanya ada
                })
                ->unique(function ($r) {
                    return ($r['id_mahasiswa'] ?? $r['nim']) . '|' . ($r['id_prodi']);
                })
                ->map(function ($r) use (&$upsertFak, &$upsertPro, $norm, $normDate) {
                    $nim = (string)($r['id_mahasiswa'] ?? $r['nim']);
                    $idProdi = (int)$r['id_prodi'];

                    // kalau ada nested prodi → siapkan upsert fak/prodi
                    if (!empty($r['prodi']) && is_array($r['prodi'])) {
                        $p   = $r['prodi'];
                        $idp = (int)($p['id_prodi'] ?? $idProdi);
                        $idf = (int)($p['id_fakultas'] ?? 0);

                        if ($idf > 0) {
                            $upsertFak[$idf] = [
                                'id'            => $idf,
                                'nama_fakultas' => ($nf = $norm($p['nama_fakultas'] ?? null)) ?: "UNKNOWN-{$idf}",
                                'nama_dekan'    => $norm($p['nama_dekan'] ?? null),
                                'nip'           => $norm($p['nip'] ?? null),
                                'alamat'        => $norm($p['fakultas_alamat'] ?? null),
                                'created_at'    => now(),
                                'updated_at' => now(),
                            ];
                        }
                        if ($idp > 0) {
                            $upsertPro[$idp] = [
                                'id'            => $idp,
                                'id_fakultas'   => $idf ?: null,
                                'nama_prodi'    => ($np = $norm($p['nama_prodi'] ?? null)) ?: "UNKNOWN-{$idp}",
                                'nama_singkat'  => $norm($p['nama_singkat'] ?? null),
                                'jenis_jenjang' => $norm($p['jenis_jenjang'] ?? null),
                                'created_at'    => now(),
                                'updated_at' => now(),
                            ];
                        }
                    }

                    // siapkan baris mahasiswa (pastikan NOT NULL aman)
                    $nama = $norm($r['nama_mahasiswa'] ?? '') ?? '';
                    if ($nama === '') $nama = "UNKNOWN-{$nim}";

                    return [
                        'nim'            => $nim,
                        'id_prodi'       => $idProdi,                       // NOT NULL
                        'nama_mahasiswa' => $nama,                          // NOT NULL (fallback)
                        'tgl_masuk'      => $normDate($r['tgl_masuk'] ?? null),
                        'tgl_yudisium'   => $normDate($r['tgl_yudisium'] ?? null),
                        'no_telp'        => $norm($r['no_telp'] ?? null),
                        'alamat'         => $norm($r['alamat'] ?? null),
                        'tanggal_lahir'  => $normDate($r['tanggal_lahir'] ?? null),
                        'tempat_lahir'   => $norm($r['tempat_lahir'] ?? null), // bisa string bebas
                        'created_at'     => now(),
                        'updated_at' => now(),
                    ];
                })
                ->values();

            // Upsert fakultas & prodi dari nested, tanpa MENIMPA nama jika sudah ada
            if (!empty($upsertFak)) {
                $ids = collect($upsertFak)->keys()->map(fn($v) => (int)$v)->values();
                $existing = DB::table('ref_fakultas')->whereIn('id', $ids)->pluck('id')->all();

                // Hanya INSERT fakultas yang belum ada; jangan update nama_fakultas untuk yg sudah ada
                $toInsert = collect($upsertFak)
                    ->filter(fn($row) => !in_array((int)$row['id'], $existing, true))
                    ->map(function ($row) {
                        // Pastikan ada nama (fallback) hanya untuk INSERT baru
                        if (!isset($row['nama_fakultas']) || $row['nama_fakultas'] === null || $row['nama_fakultas'] === '') {
                            $row['nama_fakultas'] = 'UNKNOWN-' . $row['id'];
                        }
                        return $row;
                    })
                    ->values();

                if ($toInsert->isNotEmpty()) {
                    // insertOrIgnore agar idempotent; tidak ada kolom update
                    DB::table('ref_fakultas')->insertOrIgnore($toInsert->all());
                }

                // Untuk yang SUDAH ADA: update kolom lain HANYA jika kita punya nama yang BUKAN fallback (opsional)
                $toUpdate = collect($upsertFak)
                    ->filter(fn($row) => in_array((int)$row['id'], $existing, true))
                    ->filter(function ($row) {
                        // update kalau kita benar2 punya nama valid
                        return isset($row['nama_fakultas']) && str_starts_with((string)$row['nama_fakultas'], 'UNKNOWN-') === false;
                    })
                    ->values();

                foreach ($toUpdate->chunk(500) as $chunk) {
                    // upsert dengan update nama_fakultas bila ada nama valid
                    DB::table('ref_fakultas')->upsert(
                        $chunk->all(),
                        ['id'],
                        ['nama_fakultas', 'nama_dekan', 'nip', 'alamat', 'updated_at']
                    );
                }
            }

            if (!empty($upsertPro)) {
                $ids = collect($upsertPro)->keys()->map(fn($v) => (int)$v)->values();
                $existing = DB::table('ref_prodi')->whereIn('id', $ids)->pluck('id')->all();

                // INSERT prodi yang belum ada (gunakan fallback nama hanya untuk INSERT)
                $toInsert = collect($upsertPro)
                    ->filter(fn($row) => !in_array((int)$row['id'], $existing, true))
                    ->map(function ($row) {
                        if (!isset($row['nama_prodi']) || $row['nama_prodi'] === null || $row['nama_prodi'] === '') {
                            $row['nama_prodi'] = 'UNKNOWN-' . $row['id'];
                        }
                        return $row;
                    })
                    ->values();

                if ($toInsert->isNotEmpty()) {
                    DB::table('ref_prodi')->insertOrIgnore($toInsert->all());
                }

                // UPDATE untuk yang sudah ada: hanya kalau kita punya nama valid (bukan fallback)
                $toUpdate = collect($upsertPro)
                    ->filter(fn($row) => in_array((int)$row['id'], $existing, true))
                    ->filter(function ($row) {
                        return isset($row['nama_prodi']) && str_starts_with((string)$row['nama_prodi'], 'UNKNOWN-') === false;
                    })
                    ->values();

                foreach ($toUpdate->chunk(500) as $chunk) {
                    DB::table('ref_prodi')->upsert(
                        $chunk->all(),
                        ['id'],
                        ['id_fakultas', 'nama_prodi', 'nama_singkat', 'jenis_jenjang', 'updated_at']
                    );
                }
            }


            // Pastikan setiap id_prodi yang akan dipakai eksis; kalau belum ada (tidak ada nested & belum pernah disync), buat placeholder
            $allNeededProdi = $rows->pluck('id_prodi')->unique()->values();
            $missing = $allNeededProdi->diff(
                DB::table('ref_prodi')->whereIn('id', $allNeededProdi)->pluck('id')
            );
            if ($missing->isNotEmpty()) {
                $placeholders = $missing->map(fn($idp) => [
                    'id'            => (int)$idp,
                    'id_fakultas'   => null,
                    'nama_prodi'    => "UNKNOWN-{$idp}",
                    'nama_singkat'  => null,
                    'jenis_jenjang' => null,
                    'created_at'    => now(),
                    'updated_at' => now(),
                ]);
                foreach ($placeholders->chunk(500) as $chunk) {
                    DB::table('ref_prodi')->upsert($chunk->all(), ['id'], [
                        'id_fakultas',
                        'nama_prodi',
                        'nama_singkat',
                        'jenis_jenjang',
                        'updated_at'
                    ]);
                }
            }

            // Upsert mahasiswa (idempotent; aman NOT NULL & FK)
            foreach ($rows->chunk(500) as $chunk) {
                DB::table('ref_mahasiswa')->upsert($chunk->all(), ['nim'], [
                    'id_prodi',
                    'nama_mahasiswa',
                    'tgl_masuk',
                    'tgl_yudisium',
                    'no_telp',
                    'alamat',
                    'tanggal_lahir',
                    'tempat_lahir',
                    'updated_at'
                ]);
            }

            // Upsert tugas akhir dari mahasiswa yang memiliki judul_tugas_akhir
            $tugasAkhirData = $raw
                ->filter(function ($r) use ($norm) {
                    $nim = $r['id_mahasiswa'] ?? $r['nim'] ?? null;
                    $judul = $norm($r['judul_tugas_akhir'] ?? null);
                    return !empty($nim) && !empty($judul);
                })
                ->map(function ($r) use ($norm) {
                    $nim = (string)($r['id_mahasiswa'] ?? $r['nim']);
                    $judul = $norm($r['judul_tugas_akhir'] ?? null);

                    return [
                        'nim'       => $nim,
                        'kategori'  => 'TA',  // Kategori tidak ada di API, bisa diisi null atau default
                        'judul'     => $judul,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                })
                ->unique('nim')  // Satu mahasiswa satu tugas akhir
                ->values();

            if ($tugasAkhirData->isNotEmpty()) {
                foreach ($tugasAkhirData->chunk(500) as $chunk) {
                    DB::table('tugas_akhir')->upsert($chunk->all(), ['nim'], [
                        'judul',
                        'updated_at'
                    ]);
                }
            }

            return $rows->count();
        } finally {
            optional($lock)->release();
        }
    }
}

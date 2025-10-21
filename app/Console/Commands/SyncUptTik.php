<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\UptTikSyncService;

class SyncUptTik extends Command
{
    protected $signature = 'skpi:sync {--all} {--fakultas} {--prodi} {--mahasiswa}';
    protected $description = 'Sinkronisasi master Fakultas/Prodi/Mahasiswa dari UPT TIK';

    public function handle(UptTikSyncService $svc): int
    {
        $all = $this->option('all');

        if ($all || $this->option('fakultas')) {
            $n = $svc->syncFakultas();
            $this->info("Fakultas: {$n} baris disinkron");
        }

        if ($all || $this->option('prodi')) {
            $n = $svc->syncProdi();
            $this->info("Prodi: {$n} baris disinkron");
        }

        if ($all || $this->option('mahasiswa')) {
            $n = $svc->syncMahasiswa();
            $this->info("Mahasiswa: {$n} baris disinkron");
        }

        if (!$all && !$this->option('fakultas') && !$this->option('prodi') && !$this->option('mahasiswa')) {
            $out = $svc->syncAll();
            $this->info("All done. Fakultas: {$out['fakultas']}, Prodi: {$out['prodi']}, Mahasiswa: {$out['mahasiswa']}");
        }

        return self::SUCCESS;
    }
}

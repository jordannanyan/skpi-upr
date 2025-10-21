<?php

namespace App\Services;

use App\Models\LaporanSkpi;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class LaporanSkpiService
{
    /**
     * Generate file laporan (stub).
     * Di sini kamu bisa ganti dengan generator PDF sebenarnya (DomPDF/Snappy).
     * Untuk sekarang: kita simpan placeholder .txt agar flow end-to-end jalan.
     */
    public function generateFile(LaporanSkpi $laporan): LaporanSkpi
    {
        $dir = LaporanSkpi::dir();

        // versi naik
        $nextVersion = ((int)($laporan->versi_file ?? 0)) + 1;

        // nama file: SKPI_{nim}_{versi}.pdf (sementara .txt sebagai placeholder)
        $base  = 'SKPI_'.$laporan->nim.'_v'.$nextVersion;
        $fname = $base.'.txt';

        $content = "LAPORAN SKPI (placeholder)\n"
                 . "NIM: {$laporan->nim}\n"
                 . "No Pengesahan: ".($laporan->no_pengesahan ?? '-')."\n"
                 . "Tgl Pengesahan: ".($laporan->tgl_pengesahan?->format('Y-m-d') ?? '-')."\n"
                 . "Generated at: ".now()->toDateTimeString()."\n";

        /** @var \Illuminate\Filesystem\FilesystemAdapter $disk */
        $disk = Storage::disk('public');
        $disk->put($dir.'/'.$fname, $content);

        $laporan->update([
            'file_laporan' => $fname,
            'versi_file'   => $nextVersion,
            'generated_at' => now(),
        ]);

        return $laporan->refresh();
    }
}

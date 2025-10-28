<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\MahasiswaController;
use App\Http\Controllers\ProdiController;
use App\Http\Controllers\FakultasController;
use App\Http\Controllers\SyncController;
use App\Http\Controllers\CplController;
use App\Http\Controllers\SkorCplController;
use App\Http\Controllers\KerjaPraktekController;
use App\Http\Controllers\TugasAkhirController;
use App\Http\Controllers\SertifikasiController;
use App\Http\Controllers\LaporanSkpiController;
use App\Http\Controllers\ApprovalLogController;

/*
|--------------------------------------------------------------------------
| Public routes
|--------------------------------------------------------------------------
*/
Route::post('/login', [AuthController::class, 'login']);

Route::post('/login/nim', [AuthController::class, 'loginByNim']);

/*
|--------------------------------------------------------------------------
| Protected routes (Sanctum)
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function () {
    // session helpers
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // Users CRUD
    Route::get('/users', [UserController::class, 'index']);
    Route::post('/users', [UserController::class, 'store']);
    Route::get('/users/{id}', [UserController::class, 'show']);
    Route::match(['put', 'patch'], '/users/{id}', [UserController::class, 'update']);
    Route::delete('/users/{id}', [UserController::class, 'destroy']);

    // Sync (restricted by role)
    Route::middleware('role:SuperAdmin,AdminFakultas,AdminJurusan')->group(function () {
        Route::post('/sync/upttik/all', [SyncController::class, 'all']);
    });


    // Fakultas/Prodi/Mahasiswa
    Route::get('/fakultas', [FakultasController::class, 'index']);
    Route::get('/fakultas/{id}', [FakultasController::class, 'show']);
    Route::get('/prodi', [ProdiController::class, 'index']);
    Route::get('/prodi/{id}', [ProdiController::class, 'show']);
    Route::get('/mahasiswa', [MahasiswaController::class, 'index']);

    // CPL master
    Route::get('/cpl', [CplController::class, 'index']);
    Route::get('/cpl/{kode}', [CplController::class, 'show']);
    Route::post('/cpl', [CplController::class, 'store']);
    Route::match(['put', 'patch'], '/cpl/{kode}', [CplController::class, 'update']);
    Route::delete('/cpl/{kode}', [CplController::class, 'destroy']);

    // Skor CPL
    Route::get('/cpl/{kode}/skor', [SkorCplController::class, 'indexByCpl']);
    Route::get('/mahasiswa/{nim}/skor-cpl', [SkorCplController::class, 'indexByMahasiswa']);
    Route::post('/skor-cpl/upsert', [SkorCplController::class, 'upsert']);
    Route::delete('/cpl/{kode}/skor/{nim}', [SkorCplController::class, 'destroy']);

    // Kerja Praktek
    Route::get('/kp', [KerjaPraktekController::class, 'index']);
    Route::get('/kp/{id}', [KerjaPraktekController::class, 'show']);
    Route::post('/kp', [KerjaPraktekController::class, 'store']);     // multipart
    Route::match(['put', 'patch'], '/kp/{id}', [KerjaPraktekController::class, 'update']); // multipart opsional
    Route::delete('/kp/{id}', [KerjaPraktekController::class, 'destroy']);
    Route::get('/kp/{id}/download', [KerjaPraktekController::class, 'download']);
    // ➕ Shortcut by NIM untuk halaman detail
    Route::get('/mahasiswa/{nim}/kerja-praktek', [KerjaPraktekController::class, 'indexByMahasiswa']);

    // Tugas Akhir
    Route::middleware(['auth:sanctum'])->group(function () {
        Route::get('/ta', [TugasAkhirController::class, 'index']);
        Route::get('/ta/{id}', [TugasAkhirController::class, 'show']);
        Route::post('/ta', [TugasAkhirController::class, 'store'])->middleware('role:SuperAdmin,Kajur,AdminJurusan,Mahasiswa');
        Route::match(['put', 'patch'], '/ta/{id}', [TugasAkhirController::class, 'update'])->middleware('role:SuperAdmin,Kajur,AdminJurusan,Mahasiswa');
        Route::delete('/ta/{id}', [TugasAkhirController::class, 'destroy'])->middleware('role:SuperAdmin,Kajur,AdminJurusan,Mahasiswa');
        Route::get('/mahasiswa/{nim}/tugas-akhir', [TugasAkhirController::class, 'indexByMahasiswa']);
    });


    // Sertifikasi
    Route::get('/sertifikasi', [SertifikasiController::class, 'index']);
    Route::get('/sertifikasi/{id}', [SertifikasiController::class, 'show']);
    Route::post('/sertifikasi', [SertifikasiController::class, 'store']);        // multipart
    Route::match(['put', 'patch'], '/sertifikasi/{id}', [SertifikasiController::class, 'update']); // multipart opsional
    Route::delete('/sertifikasi/{id}', [SertifikasiController::class, 'destroy']);
    Route::get('/sertifikasi/{id}/download', [SertifikasiController::class, 'download']);
    // ➕ Shortcut by NIM
    Route::get('/mahasiswa/{nim}/sertifikat', [SertifikasiController::class, 'indexByMahasiswa']);

    // Laporan SKPI
    Route::get('/laporan-skpi', [LaporanSkpiController::class, 'index']);
    Route::get('/laporan-skpi/{id}', [LaporanSkpiController::class, 'show']);

    Route::post('/laporan-skpi/submit', [LaporanSkpiController::class, 'submit'])
        ->middleware('role:AdminJurusan,Kajur,SuperAdmin');

    // verifikasi Kajur
    Route::post('/laporan-skpi/{id}/verify', [LaporanSkpiController::class, 'verify'])
        ->middleware('role:Kajur,SuperAdmin');

    // pengesahan Admin Fakultas
    Route::post('/laporan-skpi/{id}/pengesahan', [LaporanSkpiController::class, 'pengesahan'])
        ->middleware('role:AdminFakultas,SuperAdmin');

    // approve Wakadek & Dekan
    Route::post('/laporan-skpi/{id}/wakadek', [LaporanSkpiController::class, 'decideWakadek'])
        ->middleware('role:Wakadek,SuperAdmin');
    Route::post('/laporan-skpi/{id}/dekan', [LaporanSkpiController::class, 'decideDekan'])
        ->middleware('role:Dekan,SuperAdmin');

    // regenerate & download
    Route::post('/laporan-skpi/{id}/regenerate', [LaporanSkpiController::class, 'regenerate'])
        ->middleware('role:SuperAdmin,AdminFakultas,Dekan');
    Route::get('/laporan-skpi/{id}/download', [LaporanSkpiController::class, 'download']);

    // ➕ Delete Laporan (SuperAdmin only)
    Route::delete('/laporan-skpi/{id}', [LaporanSkpiController::class, 'destroy'])
        ->middleware('role:SuperAdmin');

    // Approval logs
    Route::get('/approval-logs', [ApprovalLogController::class, 'index'])
        ->middleware('role:SuperAdmin,Dekan,Wakadek,AdminFakultas,Kajur,AdminJurusan');

    Route::get('/approval-logs/{id}', [ApprovalLogController::class, 'show'])
        ->middleware('role:SuperAdmin,Dekan,Wakadek,AdminFakultas,Kajur,AdminJurusan');
});

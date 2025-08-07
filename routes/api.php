<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\{
    MahasiswaController,
    FakultasController,
    ProdiController,
    KategoriController,
    PengajuanController,
    PengesahanController,
    CplController,
    CplSkorController,
    IsiCapaianController,
    CetakController,
    KerjaPraktekController,
    TugasAkhirController,
    SertifikasiController,
    SuperAdminController,
    LoginController
};

// Login routes
Route::post('/login/superadmin', [LoginController::class, 'loginSuperAdmin']);
Route::post('/login/fakultas', [LoginController::class, 'loginFakultas']);
Route::post('/login/prodi', [LoginController::class, 'loginProdi']);
Route::post('/login/mahasiswa', [LoginController::class, 'loginMahasiswa']);



// Auto-generated routes for all CRUD controllers
Route::apiResource('mahasiswa', MahasiswaController::class);
Route::apiResource('fakultas', FakultasController::class);
Route::apiResource('prodi', ProdiController::class);
Route::apiResource('kategori', KategoriController::class);
Route::apiResource('pengajuan', PengajuanController::class);
Route::apiResource('pengesahan', PengesahanController::class);
Route::apiResource('cpl', CplController::class);
Route::apiResource('cpl-skor', CplSkorController::class);
Route::apiResource('isi-capaian', IsiCapaianController::class);
Route::apiResource('cetak', CetakController::class);
Route::apiResource('kerja-praktek', KerjaPraktekController::class);
Route::apiResource('tugas-akhir', TugasAkhirController::class);
Route::apiResource('sertifikasi', SertifikasiController::class);
Route::apiResource('super-admin', SuperAdminController::class);
Route::get('/pengesahan/print/{id}', [PengesahanController::class, 'getPengesahanDetail']);


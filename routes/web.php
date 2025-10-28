<?php

use Illuminate\Support\Facades\Route;

// Login page (sudah jalan)
Route::get('/login', fn() => view('pages.login'))->name('login.page');

Route::view('/login/mahasiswa', 'pages.login-mahasiswa')->name('login.mahasiswa');

// Admin shell + halaman dashboard (pakai layout)
Route::get('/admin', fn () => view('admin.dashboard'))->name('admin.page');

// Root → redirect ke login
Route::get('/', fn() => redirect()->route('login.page'));

// Halaman profil/password 
Route::get('/admin/profile', fn () => view('admin.profile'))->name('admin.profile');
Route::get('/admin/password', fn () => view('admin.password'))->name('admin.password');

// (Optional) web logout, kalau nanti dipakai form POST non-API
Route::post('/logout', function () {
    // kalau pakai Sanctum token Bearer, logout via /api/logout sudah cukup.
    // Ini hanya buat jaga-jaga redirect dari UI:
    return redirect()->route('login.page');
});

// Laporan pages (FE)
Route::prefix('/admin/laporan')->group(function () {
    Route::get('/', fn() => view('admin.laporan.index'))->name('laporan.index');
    Route::get('/create', fn() => view('admin.laporan.create'))->name('laporan.create');
    Route::get('/{id}/edit', fn($id) => view('admin.laporan.edit', ['id'=>$id]))->name('laporan.edit');
});

// Admin list pages (read-only masters)
Route::prefix('/admin')->group(function () {
    Route::get('/mahasiswa', fn() => view('admin.mahasiswa.index'))->name('admin.mahasiswa');
    Route::get('/prodi',     fn() => view('admin.prodi.index'))->name('admin.prodi');
    Route::get('/fakultas',  fn() => view('admin.fakultas.index'))->name('admin.fakultas');
});

// TA pages (Admin)
Route::prefix('/admin/ta')->group(function () {
    Route::get('/', fn() => view('admin.ta.index'))->name('ta.index');
    Route::get('/create', fn() => view('admin.ta.create'))->name('ta.create');
    Route::get('/{id}/edit', fn($id) => view('admin.ta.edit', ['id'=>$id]))->name('ta.edit');
});

// KP pages (Admin)
Route::prefix('/admin/kp')->group(function () {
    Route::get('/', fn() => view('admin.kp.index'))->name('kp.index');
    Route::get('/create', fn() => view('admin.kp.create'))->name('kp.create');
    Route::get('/{id}/edit', fn($id) => view('admin.kp.edit', ['id'=>$id]))->name('kp.edit');
});

// Sertifikasi pages (Admin)
Route::prefix('/admin/sertifikasi')->group(function () {
    Route::get('/', fn() => view('admin.sertifikasi.index'))->name('sertifikasi.index');
    Route::get('/create', fn() => view('admin.sertifikasi.create'))->name('sertifikasi.create');
    Route::get('/{id}/edit', fn($id) => view('admin.sertifikasi.edit', ['id'=>$id]))->name('sertifikasi.edit');
});

// CPL (Admin pages)
Route::prefix('/admin/cpl')->group(function () {
    Route::get('/', fn() => view('admin.cpl.index'))->name('cpl.index');
    Route::get('/create', fn() => view('admin.cpl.create'))->name('cpl.create');
    Route::get('/{kode}/edit', fn($kode) => view('admin.cpl.edit', ['kode'=>$kode]))->name('cpl.edit');

    // Kelola skor untuk 1 CPL
    Route::get('/{kode}/skor', fn($kode) => view('admin.cpl.skor', ['kode'=>$kode]))->name('cpl.skor');
});

// (opsional) lihat skor milik 1 mahasiswa
Route::get('/admin/mahasiswa/{nim}/skor-cpl', fn($nim) => view('admin.cpl.skor_mhs', ['nim'=>$nim]))
     ->name('cpl.skor.mhs');

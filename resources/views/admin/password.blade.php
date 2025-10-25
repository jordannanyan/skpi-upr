@extends('layouts.admin')

@section('title','Ganti Password – SKPI UPR')
@section('pageTitle','Ganti Password')

@push('head')
  @vite(['resources/js/admin/pages/password.js'])
@endpush

@section('content')
<div class="row justify-content-center">
  <div class="col-md-7 col-lg-6">
    <div class="card border-0 shadow-sm">
      <div class="card-body">
        <div class="mb-3">
          <div class="h5 mb-1">Ubah Password</div>
          <div class="text-muted small">Silakan isi password saat ini dan password baru.</div>
        </div>

        <div id="pwAlert" class="alert d-none"></div>

        <div class="mb-3">
          <label class="form-label">Password Saat Ini</label>
          <input type="password" id="curPass" class="form-control" autocomplete="current-password" placeholder="Masukkan password saat ini">
        </div>

        <div class="mb-3">
          <label class="form-label">Password Baru</label>
          <input type="password" id="newPass" class="form-control" autocomplete="new-password" placeholder="Minimal 8 karakter">
          <div class="form-text" id="pwHint">—</div>
        </div>

        <div class="mb-3">
          <label class="form-label">Konfirmasi Password Baru</label>
          <input type="password" id="newPass2" class="form-control" autocomplete="new-password" placeholder="Ulangi password baru">
        </div>

        <div class="form-check mb-3">
          <input class="form-check-input" type="checkbox" id="logoutAll">
          <label class="form-check-label" for="logoutAll">
            Logout semua sesi lain (opsional)
          </label>
        </div>

        <div class="d-flex gap-2">
          <button class="btn btn-primary" id="btnUpdatePw">
            <i class="bi bi-shield-lock me-1"></i> Simpan Password
          </button>
          <a href="{{ route('admin.profile') }}" class="btn btn-outline-secondary">Kembali</a>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection

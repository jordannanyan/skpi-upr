@extends('layouts.admin')

@section('title','Tugas Akhir – SKPI UPR')
@section('pageTitle','Tugas Akhir')

@push('head')
  @vite(['resources/js/admin/pages/ta/index.js'])
@endpush

@section('content')
<div id="bridge" data-admin-url="{{ url('/admin') }}" data-login-url="{{ url('/login') }}"></div>

{{-- Filter --}}
<div class="card border-0 shadow-sm mb-3">
  <div class="card-body">
    <div class="row g-2 align-items-end">
      <div class="col-md-4">
        <label class="form-label small text-muted">Cari (judul/kategori)</label>
        <input type="text" id="taKw" class="form-control" placeholder="mis. Sistem / Jaringan">
      </div>
      <div class="col-md-3">
        <label class="form-label small text-muted">Filter NIM</label>
        <input type="text" id="taNim" class="form-control" placeholder="mis. 22xx">
      </div>

      {{-- Khusus SuperAdmin: Fakultas & Prodi --}}
      <div id="roleFilters" class="d-none">
        <div class="row g-2">
          <div class="col-md-3">
            <label class="form-label small text-muted">Fakultas</label>
            <select id="taFak" class="form-select">
              <option value="">— Semua —</option>
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label small text-muted">Prodi</label>
            <select id="taProdi" class="form-select">
              <option value="">— Semua —</option>
            </select>
          </div>
        </div>
      </div>

      <div class="col-md-2 d-grid mt-2 mt-md-0">
        <button class="btn btn-outline-secondary mt-2" id="taCari">Terapkan</button>
      </div>
    </div>
  </div>
</div>

{{-- Toolbar Aksi (Tambah TA) --}}
<div class="d-flex justify-content-between align-items-center mb-3">
  <div></div>
  <a href="{{ route('ta.create') }}" class="btn btn-primary" id="btnGoCreate">
    <i class="bi bi-plus-lg me-1"></i> Tambah TA
  </a>
</div>

<div class="table-responsive">
  <table class="table table-hover align-middle">
    <thead class="table-light">
      <tr>
        <th style="width:80px;">ID</th>
        <th style="width:140px;">NIM</th>
        <th>Nama</th>
        <th>Prodi</th>
        <th>Fakultas</th>
        <th style="width:160px;">Kategori</th>
        <th>Judul</th>
        <th>Aksi</th>
      </tr>
    </thead>
    <tbody id="taBody">
      <tr>
        <td colspan="8" class="text-center text-muted p-4">Memuat…</td>
      </tr>
    </tbody>
  </table>
</div>
@endsection

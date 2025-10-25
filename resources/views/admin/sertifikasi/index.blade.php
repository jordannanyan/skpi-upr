@extends('layouts.admin')

@section('title','Sertifikasi – SKPI UPR')
@section('pageTitle','Sertifikasi')

@push('head')
  @vite(['resources/js/admin/pages/sertif/index.js'])
@endpush

@section('content')
{{-- Filter --}}
<div class="card border-0 shadow-sm mb-3">
  <div class="card-body">
    <div class="row g-2 align-items-end">
      <div class="col-md-3">
        <label class="form-label small text-muted">Cari (nama sertifikasi / kata kunci)</label>
        <input type="text" id="sfKw" class="form-control" placeholder="mis. Oracle / TOEFL">
      </div>
      <div class="col-md-2">
        <label class="form-label small text-muted">Filter NIM</label>
        <input type="text" id="sfNim" class="form-control" placeholder="mis. 22xx">
      </div>
      <div class="col-md-2">
        <label class="form-label small text-muted">Kategori</label>
        <input type="text" id="sfKat" class="form-control" placeholder="mis. Bahasa / Vendor">
      </div>
      <div class="col-md-3">
        <label class="form-label small text-muted">Fakultas</label>
        <select id="sfFak" class="form-select">
          <option value="">— Semua —</option>
        </select>
      </div>
      <div class="col-md-2">
        <label class="form-label small text-muted">Prodi</label>
        <select id="sfProdi" class="form-select">
          <option value="">— Semua —</option>
        </select>
      </div>
      <div class="col-12 col-md-2 d-grid">
        <button class="btn btn-outline-secondary" id="sfCari">Terapkan</button>
      </div>
    </div>
  </div>
</div>

{{-- Toolbar Aksi --}}
<div class="d-flex justify-content-between align-items-center mb-3">
  <div></div>
  <a href="{{ route('sertifikasi.create') }}" class="btn btn-primary" id="btnGoCreate">
    <i class="bi bi-plus-lg me-1"></i> Tambah Sertifikasi
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
        <th style="width:160px;">Nama Sertifikasi</th>
        <th>Aksi</th>
      </tr>
    </thead>
    <tbody id="sfBody">
      <tr>
        <td colspan="8" class="text-center text-muted p-4">Memuat…</td>
      </tr>
    </tbody>
  </table>
</div>
@endsection

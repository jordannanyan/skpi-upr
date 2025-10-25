@extends('layouts.admin')

@section('title','CPL – SKPI UPR')
@section('pageTitle','CPL (Capaian Pembelajaran Lulusan)')

@push('head')
  @vite(['resources/js/admin/pages/cpl/index.js'])
@endpush

@section('content')
{{-- Filter --}}
<div class="card border-0 shadow-sm mb-3">
  <div class="card-body">
    <div class="row g-2 align-items-end">
      <div class="col-md-4">
        <label class="form-label small text-muted">Cari (kode/kategori/deskripsi/prodi/fakultas)</label>
        <input type="text" id="q" class="form-control" placeholder="mis. CPL01 / Sikap / Kedokteran">
      </div>
      <div class="col-md-4">
        <label class="form-label small text-muted">Fakultas</label>
        <select id="cplFak" class="form-select">
          <option value="">— Semua —</option>
        </select>
      </div>
      <div class="col-md-3">
        <label class="form-label small text-muted">Prodi</label>
        <select id="cplProdi" class="form-select">
          <option value="">— Semua —</option>
        </select>
      </div>
      <div class="col-md-1 d-grid">
        <button class="btn btn-outline-secondary" id="btnCari">Terapkan</button>
      </div>
    </div>
  </div>
</div>

{{-- Toolbar --}}
<div class="d-flex justify-content-between align-items-center mb-3">
  <div></div>
  <a href="{{ route('cpl.create') }}" class="btn btn-primary ms-2" id="btnGoCreate">
    <i class="bi bi-plus-lg me-1"></i> CPL Baru
  </a>
</div>

<div class="table-responsive">
  <table class="table table-hover align-middle">
    <thead class="table-light">
      <tr>
        <th style="width:130px;">Kode</th>
        <th style="width:160px;">Kategori</th>
        <th style="width:220px;">Prodi</th>
        <th style="width:220px;">Fakultas</th>
        <th>Deskripsi</th>
        <th>Aksi</th>
      </tr>
    </thead>
    <tbody id="cplBody">
      <tr><td colspan="7" class="text-center text-muted p-4">Memuat…</td></tr>
    </tbody>
  </table>
</div>
@endsection

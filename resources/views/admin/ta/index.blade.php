@extends('layouts.admin')

@section('title','Tugas Akhir – SKPI UPR')
@section('pageTitle','Tugas Akhir')

@push('head')
  @vite(['resources/js/admin/pages/ta/index.js'])
@endpush

@section('content')
{{-- Filter (di dalam card, seperti KP) --}}
<div class="card border-0 shadow-sm mb-3">
  <div class="card-body">
    <div class="row g-2 align-items-end">
      <div class="col-md-3">
        <label class="form-label small text-muted">Cari (judul/kategori)</label>
        <input type="text" id="taKw" class="form-control" placeholder="mis. Sistem / Jaringan">
      </div>
      <div class="col-md-2">
        <label class="form-label small text-muted">Filter NIM</label>
        <input type="text" id="taNim" class="form-control" placeholder="mis. 22xx">
      </div>
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
      <div class="col-md-1 d-grid">
        <button class="btn btn-outline-secondary" id="taCari">Terapkan</button>
      </div>
    </div>
  </div>
</div>

{{-- Toolbar Aksi (Tambah TA) — sama seperti KP --}}
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
        <th style="width:220px;">Aksi</th>
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

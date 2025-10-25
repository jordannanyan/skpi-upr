@extends('layouts.admin')

@section('title','Kerja Praktik – SKPI UPR')
@section('pageTitle','Kerja Praktik')

@push('head')
  @vite(['resources/js/admin/pages/kp/index.js'])
@endpush

@section('content')
<div class="card border-0 shadow-sm mb-3">
  <div class="card-body">
    <div class="row g-2 align-items-end">
      <div class="col-md-3">
        <label class="form-label small text-muted">Cari (kegiatan/keyword)</label>
        <input type="text" id="kpKw" class="form-control" placeholder="mis. Magang / Seminar">
      </div>
      <div class="col-md-2">
        <label class="form-label small text-muted">Filter NIM</label>
        <input type="text" id="kpNim" class="form-control" placeholder="mis. 22xx">
      </div>
      <div class="col-md-3">
        <label class="form-label small text-muted">Nama Mahasiswa</label>
        <input type="text" id="kpNama" class="form-control" placeholder="mis. Siti / Budi">
      </div>
      <div class="col-md-2">
        <label class="form-label small text-muted">Fakultas</label>
        <select id="kpFak" class="form-select">
          <option value="">— Semua —</option>
        </select>
      </div>
      <div class="col-md-2">
        <label class="form-label small text-muted">Prodi</label>
        <select id="kpProdi" class="form-select">
          <option value="">— Semua —</option>
        </select>
      </div>
      <div class="col-12 col-md-2 d-grid">
        <button class="btn btn-outline-secondary" id="kpCari">Terapkan</button>
      </div>
    </div>
  </div>
</div>
{{-- Toolbar Aksi (Tambah KP) --}}
<div class="d-flex justify-content-between align-items-center mb-3">
  <div></div>
  <a href="{{ route('kp.create') }}" class="btn btn-primary" id="btnGoCreate">
    <i class="bi bi-plus-lg me-1"></i> Tambah KP
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
        <th>Nama Kegiatan</th>
        <th style="width:220px;">Aksi</th>
      </tr>
    </thead>
    <tbody id="kpBody">
      <tr>
        <td colspan="7" class="text-center text-muted p-4">Memuat…</td>
      </tr>
    </tbody>
  </table>
</div>

<div class="d-flex justify-content-between align-items-center">
  <div class="small text-muted" id="kpInfo">—</div>
  <div class="btn-group">
    <button class="btn btn-sm btn-outline-secondary" id="kpPrev">‹</button>
    <button class="btn btn-sm btn-outline-secondary" id="kpNext">›</button>
  </div>
</div>
@endsection

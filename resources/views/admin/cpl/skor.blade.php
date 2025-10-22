@extends('layouts.admin')

@section('title','Skor CPL – SKPI UPR')
@section('pageTitle','Kelola Skor CPL')

@push('head')
  @vite(['resources/js/admin/pages/cpl/skor.js'])
@endpush

@section('content')
<div class="card border-0 shadow-sm mb-3">
  <div class="card-body">
    <div class="row g-2 align-items-end">
      <div class="col-md-3">
        <label class="form-label small text-muted">Kode CPL</label>
        <input type="text" id="vKode" class="form-control" value="{{ $kode }}" disabled>
      </div>
      <div class="col-md-3">
        <label class="form-label small text-muted">Fakultas</label>
        <select id="selFak" class="form-select">
          <option value="">— Pilih Fakultas —</option>
        </select>
      </div>
      <div class="col-md-3">
        <label class="form-label small text-muted">Prodi</label>
        <select id="selProdi" class="form-select" disabled>
          <option value="">— Pilih Prodi —</option>
        </select>
      </div>
      <div class="col-md-3">
        <label class="form-label small text-muted">Mahasiswa (NIM)</label>
        <select id="selNim" class="form-select" disabled>
          <option value="">— Pilih NIM —</option>
        </select>
      </div>

      <div class="col-md-2">
        <label class="form-label small text-muted">Skor</label>
        <input type="number" id="inpSkor" class="form-control" step="0.01" placeholder="0 - 100">
      </div>
      <div class="col-md-2">
        <button class="btn btn-primary w-100" id="btnUpsert">
          <i class="bi bi-save me-1"></i> Simpan/Update
        </button>
      </div>
    </div>
  </div>
</div>

<div class="table-responsive">
  <table class="table table-hover align-middle">
    <thead class="table-light">
      <tr>
        <th style="width:160px;">NIM</th>
        <th>Nama</th>
        <th style="width:120px;">Skor</th>
        <th style="width:140px;">Aksi</th>
      </tr>
    </thead>
    <tbody id="skorBody">
      <tr><td colspan="4" class="text-center text-muted p-4">Memuat…</td></tr>
    </tbody>
  </table>
</div>

@push('scripts')
<script>window.__CPL_KODE__ = @json($kode)</script>
@endpush
@endsection

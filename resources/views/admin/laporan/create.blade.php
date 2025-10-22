@extends('layouts.admin')

@section('title','Pengajuan SKPI – SKPI UPR')
@section('pageTitle','Pengajuan SKPI')

@push('head')
  @vite(['resources/js/admin/pages/laporan/create.js'])
@endpush

@section('content')
<div class="card border-0 shadow-sm">
  <div class="card-body">
    <div class="row g-3">
      <div class="col-md-4">
        <label class="form-label small text-muted">Fakultas</label>
        <select id="selFak" class="form-select">
          <option value="">— Pilih Fakultas —</option>
        </select>
      </div>

      <div class="col-md-4">
        <label class="form-label small text-muted">Prodi</label>
        <select id="selProdi" class="form-select" disabled>
          <option value="">— Pilih Prodi —</option>
        </select>
      </div>

      <div class="col-md-4">
        <label class="form-label small text-muted">Mahasiswa (NIM)</label>
        <select id="selNim" class="form-select" disabled>
          <option value="">— Pilih NIM —</option>
        </select>
      </div>

      <div class="col-12">
        <label class="form-label small text-muted">Catatan (opsional)</label>
        <textarea id="inpCatatan" class="form-control" rows="2" placeholder="Catatan pengajuan"></textarea>
      </div>

      <div class="col-12 d-flex gap-2">
        <button class="btn btn-primary" id="btnSubmit">Ajukan</button>
        <a href="{{ route('laporan.index') }}" class="btn btn-outline-secondary">Kembali</a>
      </div>
    </div>
  </div>
</div>
@endsection

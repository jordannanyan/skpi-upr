@extends('layouts.admin')

@section('title','Tambah Kerja Praktek – SKPI UPR')
@section('pageTitle','Tambah Kerja Praktek')

@push('head')
  @vite(['resources/js/admin/pages/kp/create.js'])
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

      <div class="col-md-8">
        <label class="form-label small text-muted">Nama Kegiatan</label>
        <input type="text" id="inpNama" class="form-control" placeholder="Nama kegiatan KP">
      </div>

      <div class="col-md-4">
        <label class="form-label small text-muted">File Sertifikat (opsional)</label>
        <input type="file" id="inpFile" class="form-control" accept=".pdf,.jpg,.jpeg,.png">
      </div>

      <div class="col-12 d-flex gap-2">
        <button class="btn btn-primary" id="btnSubmit">Simpan</button>
        <a href="{{ route('kp.index') }}" class="btn btn-outline-secondary">Kembali</a>
      </div>
    </div>
  </div>
</div>
@endsection

@extends('layouts.admin')

@section('title','Sertifikasi – SKPI UPR')
@section('pageTitle','Sertifikasi')

@push('head')
  @vite(['resources/js/admin/pages/sertif/index.js'])
@endpush

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
  <div class="d-flex gap-2 flex-grow-1">
    <input type="text" id="sfKw" class="form-control" placeholder="Cari nama sertifikasi" style="max-width:260px;">
    <input type="text" id="sfNim" class="form-control" placeholder="Filter NIM" style="max-width:200px;">
    <input type="text" id="sfKat" class="form-control" placeholder="Filter kategori" style="max-width:200px;">
    <button class="btn btn-outline-secondary" id="sfCari">Cari</button>
  </div>

  <a href="{{ route('sertifikasi.create') }}" class="btn btn-primary ms-2" id="btnGoCreate">
    <i class="bi bi-plus-lg me-1"></i> Tambah Sertifikasi
  </a>
</div>

<div class="table-responsive">
  <table class="table table-hover align-middle">
    <thead class="table-light">
      <tr>
        <th style="width:80px;">ID</th>
        <th style="width:160px;">NIM</th>
        <th style="width:180px;">Kategori</th>
        <th>Nama Sertifikasi</th>
        <th style="width:180px;">Sertifikat</th>
        <th style="width:240px;">Aksi</th>
      </tr>
    </thead>
    <tbody id="sfBody">
      <tr>
        <td colspan="6" class="text-center text-muted p-4">Memuat…</td>
      </tr>
    </tbody>
  </table>
</div>
@endsection

@extends('layouts.admin')

@section('title','Kerja Praktek – SKPI UPR')
@section('pageTitle','Kerja Praktek')

@push('head')
  @vite(['resources/js/admin/pages/kp/index.js'])
@endpush

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
  <div class="d-flex gap-2 flex-grow-1">
    <input type="text" id="kpKw" class="form-control" placeholder="Cari nama kegiatan" style="max-width:260px;">
    <input type="text" id="kpNim" class="form-control" placeholder="Filter NIM" style="max-width:200px;">
    <button class="btn btn-outline-secondary" id="kpCari">Cari</button>
  </div>

  <a href="{{ route('kp.create') }}" class="btn btn-primary ms-2" id="btnGoCreate">
    <i class="bi bi-plus-lg me-1"></i> Tambah KP
  </a>
</div>

<div class="table-responsive">
  <table class="table table-hover align-middle">
    <thead class="table-light">
      <tr>
        <th style="width:80px;">ID</th>
        <th style="width:160px;">NIM</th>
        <th>Nama Kegiatan</th>
        <th style="width:220px;">Sertifikat</th>
        <th style="width:240px;">Aksi</th>
      </tr>
    </thead>
    <tbody id="kpBody">
      <tr>
        <td colspan="5" class="text-center text-muted p-4">Memuat…</td>
      </tr>
    </tbody>
  </table>
</div>
@endsection

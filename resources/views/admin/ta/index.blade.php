@extends('layouts.admin')

@section('title','Tugas Akhir – SKPI UPR')
@section('pageTitle','Tugas Akhir')

@push('head')
  @vite(['resources/js/admin/pages/ta/index.js'])
@endpush

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
  <div class="d-flex gap-2 flex-grow-1">
    <input type="text" id="taKw" class="form-control" placeholder="Cari judul/kategori" style="max-width:260px;">
    <input type="text" id="taNim" class="form-control" placeholder="Filter NIM" style="max-width:200px;">
    <button class="btn btn-outline-secondary" id="taCari">Cari</button>
  </div>

  <a href="{{ route('ta.create') }}" class="btn btn-primary ms-2" id="btnGoCreate">
    <i class="bi bi-plus-lg me-1"></i> Tambah TA
  </a>
</div>

<div class="table-responsive">
  <table class="table table-hover align-middle">
    <thead class="table-light">
      <tr>
        <th style="width:80px;">ID</th>
        <th style="width:160px;">NIM</th>
        <th style="width:160px;">Kategori</th>
        <th>Judul</th>
        <th style="width:220px;">Aksi</th>
      </tr>
    </thead>
    <tbody id="taBody">
      <tr>
        <td colspan="5" class="text-center text-muted p-4">Memuat…</td>
      </tr>
    </tbody>
  </table>
</div>
@endsection

@extends('layouts.admin')

@section('title','CPL – SKPI UPR')
@section('pageTitle','CPL (Capaian Pembelajaran Lulusan)')

@push('head')
  @vite(['resources/js/admin/pages/cpl/index.js'])
@endpush

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
  <div class="d-flex gap-2 flex-grow-1">
    <input type="text" id="q" class="form-control" placeholder="Cari kode/kategori/deskripsi" style="max-width:360px;">
    <button class="btn btn-outline-secondary" id="btnCari">Cari</button>
  </div>

  <a href="{{ route('cpl.create') }}" class="btn btn-primary ms-2" id="btnGoCreate">
    <i class="bi bi-plus-lg me-1"></i> CPL Baru
  </a>
</div>

<div class="table-responsive">
  <table class="table table-hover align-middle">
    <thead class="table-light">
      <tr>
        <th style="width:150px;">Kode</th>
        <th style="width:180px;">Kategori</th>
        <th>Deskripsi</th>
        <th style="width:120px;">#Skor</th>
        <th style="width:260px;">Aksi</th>
      </tr>
    </thead>
    <tbody id="cplBody">
      <tr><td colspan="5" class="text-center text-muted p-4">Memuat…</td></tr>
    </tbody>
  </table>
</div>
@endsection

@extends('layouts.admin')

@section('title', 'Laporan SKPI – SKPI UPR')
@section('pageTitle', 'Laporan SKPI')

@push('head')
  @vite(['resources/js/admin/pages/laporan/index.js'])
@endpush

@section('content')
  <div class="d-flex justify-content-between align-items-center mb-3">
    <div class="d-flex gap-2 flex-grow-1">
      <input type="text" id="lapNim" class="form-control" placeholder="Filter NIM" style="max-width:220px;">
      <select id="lapStatus" class="form-select" style="max-width:200px;">
        <option value="">— Semua status —</option>
        <option value="submitted">submitted</option>
        <option value="verified">verified</option>
        <option value="wakadek_ok">wakadek_ok</option>
        <option value="approved">approved</option>
        <option value="rejected">rejected</option>
      </select>
      <button class="btn btn-outline-secondary" id="lapCari">Cari</button>
    </div>

    <a href="{{ route('laporan.create') }}" class="btn btn-primary ms-2" id="btnGoCreate">
      <i class="bi bi-plus-lg me-1"></i> Pengajuan Baru
    </a>
  </div>

  <div class="table-responsive">
    <table class="table table-hover align-middle">
      <thead class="table-light">
        <tr>
          <th style="width:80px;">ID</th>
          <th style="width:160px;">NIM</th>
          <th>Nama</th>
          <th>Prodi</th>
          <th>Fakultas</th>
          <th style="width:140px;">Status</th>
          <th>No/Tgl Pengesahan</th>
          <th>Catatan</th>
          <th>File / Aksi</th>
        </tr>
      </thead>

      <tbody id="lapBody">
        <tr>
          <td colspan="9" class="text-center text-muted p-4">Memuat…</td>
        </tr>
      </tbody>
    </table>
  </div>
@endsection

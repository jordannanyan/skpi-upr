@extends('layouts.admin')

@section('title','Skor CPL Mahasiswa – SKPI UPR')
@section('pageTitle','Skor CPL Mahasiswa')

@push('head')
  @vite(['resources/js/admin/pages/cpl/skor_mhs.js'])
@endpush

@section('content')
<div class="card border-0 shadow-sm">
  <div class="card-body">
    <div class="row g-3">
      <div class="col-md-4">
        <label class="form-label small text-muted">NIM</label>
        <input type="text" id="vNim" class="form-control" value="{{ $nim }}" disabled>
      </div>
    </div>
    <hr>
    <div class="table-responsive">
      <table class="table table-hover align-middle">
        <thead class="table-light">
          <tr>
            <th style="width:150px;">Kode CPL</th>
            <th>Kategori</th>
            <th>Deskripsi</th>
            <th style="width:120px;">Skor</th>
          </tr>
        </thead>
        <tbody id="mhsSkorBody">
          <tr><td colspan="4" class="text-center text-muted p-4">Memuat…</td></tr>
        </tbody>
      </table>
    </div>
  </div>
</div>
@endsection

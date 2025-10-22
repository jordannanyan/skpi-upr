@extends('layouts.admin')

@section('title','Edit CPL – SKPI UPR')
@section('pageTitle','Edit CPL')

@push('head')
  @vite(['resources/js/admin/pages/cpl/edit.js'])
@endpush

@section('content')
<div class="card border-0 shadow-sm">
  <div class="card-body">
    <div class="row g-3">
      <div class="col-md-3">
        <label class="form-label small text-muted">Kode CPL</label>
        <input type="text" id="kode" class="form-control" disabled>
        <div class="form-text">Kode tidak dapat diubah.</div>
      </div>
      <div class="col-md-4">
        <label class="form-label small text-muted">Kategori</label>
        <input type="text" id="kategori" class="form-control">
      </div>
      <div class="col-md-12">
        <label class="form-label small text-muted">Deskripsi</label>
        <textarea id="deskripsi" class="form-control" rows="3"></textarea>
      </div>
      <div class="col-12 d-flex gap-2">
        <button class="btn btn-primary" id="btnUpdate">Simpan Perubahan</button>
        <a href="{{ route('cpl.index') }}" class="btn btn-outline-secondary">Kembali</a>
        <a href="{{ route('cpl.skor', $kode) }}" class="btn btn-outline-dark ms-auto">
          Kelola Skor CPL
        </a>
      </div>
    </div>
  </div>
</div>

@push('scripts')
<script>window.__CPL_KODE__ = @json($kode);</script>
@endpush
@endsection

@extends('layouts.admin')

@section('title','Edit Tugas Akhir – SKPI UPR')
@section('pageTitle','Edit Tugas Akhir')

@push('head')
  @vite(['resources/js/admin/pages/ta/edit.js'])
@endpush

@section('content')
<div class="card border-0 shadow-sm">
  <div class="card-body">
    <div class="row g-3">
      {{-- NIM dibuat dropdown 1 opsi (diisi JS dari detail TA) --}}
      <div class="col-md-4">
        <label class="form-label small text-muted">Mahasiswa (NIM)</label>
        <select id="selNim" class="form-select">
          <option value="">Memuat…</option>
        </select>
        <div class="form-text">NIM mengikuti data TA yang diedit.</div>
      </div>

      <div class="col-md-4">
        <label class="form-label small text-muted">Kategori</label>
        <select id="selKategori" class="form-select">
          <option value="">— Pilih kategori —</option>
          <option value="skripsi">Skripsi</option>
          <option value="tesis">Tesis</option>
          <option value="disertasi">Disertasi</option>
        </select>
        <div class="form-text">Pilih salah satu: Skripsi, Tesis, atau Disertasi.</div>
      </div>

      <div class="col-md-8">
        <label class="form-label small text-muted">Judul</label>
        <input type="text" id="inpJudul" class="form-control" placeholder="Judul tugas akhir">
      </div>

      <div class="col-12 d-flex gap-2">
        <button class="btn btn-primary" id="btnUpdate">Simpan Perubahan</button>
        <a href="{{ route('ta.index') }}" class="btn btn-outline-secondary">Kembali</a>
      </div>
    </div>
  </div>
</div>

@push('scripts')
<script>window.__TA_ID__ = {{ (int)$id }};</script>
@endpush
@endsection

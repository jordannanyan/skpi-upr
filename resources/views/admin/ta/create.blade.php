@extends('layouts.admin')

@section('title','Tambah Tugas Akhir – SKPI UPR')
@section('pageTitle','Tambah Tugas Akhir')

@push('head')
  @vite(['resources/js/admin/pages/ta/create.js'])
@endpush

@section('content')
<div id="bridge"
     data-admin-url="{{ url('/admin') }}"
     data-login-url="{{ url('/login') }}"></div>

<div class="card border-0 shadow-sm">
  <div class="card-body">

    {{-- Info Mahasiswa (hanya tampil untuk role Mahasiswa) --}}
    <div id="mhsInfoBox" class="alert alert-info d-none">
      <div class="fw-semibold mb-1">Anda masuk sebagai Mahasiswa</div>
      <div class="small">
        <div><span class="text-muted">NIM:</span> <code id="mhsInfoNim">-</code></div>
        <div><span class="text-muted">Nama:</span> <span id="mhsInfoNama">-</span></div>
        <div><span class="text-muted">Prodi:</span> <span id="mhsInfoProdi">-</span></div>
        <div><span class="text-muted">Fakultas:</span> <span id="mhsInfoFak">-</span></div>
      </div>
      <div class="mt-2 small text-muted">
        NIM akan otomatis digunakan, Anda tidak perlu memilih NIM.
      </div>
    </div>

    <div class="row g-3">

      {{-- Blok masters (disembunyikan otomatis untuk Mahasiswa) --}}
      <div class="col-md-4 block-masters">
        <label class="form-label small text-muted">Fakultas</label>
        <select id="selFak" class="form-select">
          <option value="">— Pilih Fakultas —</option>
        </select>
      </div>

      <div class="col-md-4 block-masters">
        <label class="form-label small text-muted">Prodi</label>
        <select id="selProdi" class="form-select" disabled>
          <option value="">— Pilih Prodi —</option>
        </select>
      </div>

      <div class="col-md-4 block-masters">
        <label class="form-label small text-muted">Mahasiswa (NIM)</label>
        <select id="selNim" class="form-select" disabled>
          <option value="">— Pilih NIM —</option>
        </select>
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
        <button class="btn btn-primary" id="btnSubmit">Simpan</button>
        <a href="{{ route('ta.index') }}" class="btn btn-outline-secondary">Kembali</a>
      </div>
    </div>
  </div>
</div>
@endsection

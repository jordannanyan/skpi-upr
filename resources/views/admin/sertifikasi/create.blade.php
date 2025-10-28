@extends('layouts.admin')

@section('title','Tambah Sertifikasi – SKPI UPR')
@section('pageTitle','Tambah Sertifikasi')

@push('head')
  @vite(['resources/js/admin/pages/sertif/create.js'])
@endpush

@section('content')
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

      <div class="col-md-6">
        <label class="form-label small text-muted">Nama Sertifikasi</label>
        <input type="text" id="inpNama" class="form-control" placeholder="Nama sertifikasi">
      </div>

      <div class="col-md-3">
        <label class="form-label small text-muted">Kategori</label>
        <select id="selKategori" class="form-select">
          <option value="">— Pilih Kategori —</option>
          <option value="Sertifikat Keahlian">Sertifikat Keahlian</option>
          <option value="Pelatihan/Seminar/Workshop">Pelatihan/Seminar/Workshop</option>
          <option value="Prestasi dan Penghargaan">Prestasi dan Penghargaan</option>
          <option value="Pengalaman Organisasi">Pengalaman Organisasi</option>
        </select>
      </div>

      <div class="col-md-3">
        <label class="form-label small text-muted">File Sertifikat (opsional)</label>
        <input type="file" id="inpFile" class="form-control" accept=".pdf,.jpg,.jpeg,.png">
      </div>

      <div class="col-12 d-flex gap-2">
        <button class="btn btn-primary" id="btnSubmit">Simpan dan Ajukan</button>
        <a href="{{ route('sertifikasi.index') }}" class="btn btn-outline-secondary">Kembali</a>
      </div>
    </div>
  </div>
</div>
@endsection

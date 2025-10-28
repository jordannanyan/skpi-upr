@extends('layouts.admin')

@section('title','Tambah Kerja Praktek – SKPI UPR')
@section('pageTitle','Tambah Kerja Praktek')

@push('head')
  @vite(['resources/js/admin/pages/kp/create.js'])
@endpush

@section('content')
<div id="bridge"
     data-admin-url="{{ url('/admin') }}"
     data-login-url="{{ url('/login') }}"></div>

<div class="card border-0 shadow-sm">
  <div class="card-body">
    {{-- Info Mahasiswa (hanya tampil ketika role = Mahasiswa, di-show oleh JS) --}}
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
      {{-- Blok masters: disembunyikan otomatis untuk Mahasiswa oleh JS --}}
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

      <div class="col-md-8">
        <label class="form-label small text-muted">Nama Kegiatan</label>
        <input type="text" id="inpNama" class="form-control" placeholder="Nama kegiatan KP">
      </div>

      <div class="col-md-4">
        <label class="form-label small text-muted">File Sertifikat (opsional)</label>
        <input type="file" id="inpFile" class="form-control" accept=".pdf,.jpg,.jpeg,.png">
      </div>

      <div class="col-12 d-flex gap-2">
        <button class="btn btn-primary" id="btnSubmit">Simpan</button>
        <a href="{{ route('kp.index') }}" class="btn btn-outline-secondary">Kembali</a>
      </div>
    </div>
  </div>
</div>
@endsection

@extends('layouts.admin')

@section('title','Tambah CPL – SKPI UPR')
@section('pageTitle','Tambah CPL')

@push('head')
  @vite(['resources/js/admin/pages/cpl/create.js'])
@endpush

@section('content')
<div id="bridge" data-admin-url="{{ url('/admin') }}" data-login-url="{{ url('/login') }}"></div>

<div class="card border-0 shadow-sm">
  <div class="card-body">
    <div class="row g-3">
      <div class="col-md-3">
        <label class="form-label small text-muted">Kode CPL</label>
        <input type="text" id="kode" class="form-control" placeholder="Mis. CPL-01">
      </div>

      <div class="col-md-4">
        <label class="form-label small text-muted">Kategori</label>
        <select id="kategori" class="form-select">
          <option value="">— Pilih Kategori —</option>
          <option value="Sikap">Sikap</option>
          <option value="Pengetahuan">Pengetahuan</option>
          <option value="Keterampilan Umum">Keterampilan Umum</option>
          <option value="Keterampilan Khusus">Keterampilan Khusus</option>
        </select>
      </div>

      {{-- Blok master Fak/Prodi (disembunyikan untuk AdminJurusan/Kajur oleh JS) --}}
      <div id="rowMasters" class="col-12">
        <div class="row g-3">
          <div class="col-md-3">
            <label class="form-label small text-muted">Fakultas</label>
            <select id="selFak" class="form-select">
              <option value="">— Pilih Fakultas —</option>
            </select>
          </div>

          <div class="col-md-2">
            <label class="form-label small text-muted">Prodi</label>
            <select id="selProdi" class="form-select" disabled>
              <option value="">— Pilih Prodi —</option>
            </select>
          </div>
        </div>
      </div>

      <div class="col-md-12">
        <label class="form-label small text-muted">Deskripsi</label>
        <textarea id="deskripsi" class="form-control" rows="3" placeholder="Uraian CPL"></textarea>
      </div>

      <div class="col-12 d-flex gap-2">
        <button class="btn btn-primary" id="btnSimpan">Simpan</button>
        <a href="{{ route('cpl.index') }}" class="btn btn-outline-secondary">Kembali</a>
      </div>
    </div>
  </div>
</div>
@endsection

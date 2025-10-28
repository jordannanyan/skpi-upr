@extends('layouts.admin')

@section('title','Edit CPL – SKPI UPR')
@section('pageTitle','Edit CPL')

@push('head')
  @vite(['resources/js/admin/pages/cpl/edit.js'])
@endpush

@section('content')
<div id="bridge" data-admin-url="{{ url('/admin') }}" data-login-url="{{ url('/login') }}"></div>

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
        <select id="kategori" class="form-select">
          <option value="">— Pilih Kategori —</option>
          <option value="Sikap">Sikap</option>
          <option value="Pengetahuan">Pengetahuan</option>
          <option value="Keterampilan Umum">Keterampilan Umum</option>
          <option value="Keterampilan Khusus">Keterampilan Khusus</option>
        </select>
      </div>

      {{-- Blok master Fak/Prodi (akan di-hide untuk AdminJurusan/Kajur oleh JS) --}}
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
            <div class="form-text">Opsional. Biarkan kosong jika CPL lintas prodi.</div>
          </div>
        </div>
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

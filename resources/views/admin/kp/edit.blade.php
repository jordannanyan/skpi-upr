@extends('layouts.admin')

@section('title','Edit Kerja Praktek – SKPI UPR')
@section('pageTitle','Edit Kerja Praktek')

@push('head')
  @vite(['resources/js/admin/pages/kp/edit.js'])
@endpush

@section('content')
<div class="card border-0 shadow-sm">
  <div class="card-body">
    <div class="row g-3">
      {{-- NIM dikunci pada edit (dropdown 1 opsi) --}}
      <div class="col-md-4">
        <label class="form-label small text-muted">Mahasiswa (NIM)</label>
        <select id="selNim" class="form-select">
          <option value="">Memuat…</option>
        </select>
        <div class="form-text">NIM mengikuti data KP yang diedit.</div>
      </div>

      <div class="col-md-5">
        <label class="form-label small text-muted">Nama Kegiatan</label>
        <input type="text" id="inpNama" class="form-control" placeholder="Nama kegiatan KP">
      </div>

      <div class="col-md-3">
        <label class="form-label small text-muted">Ganti Sertifikat (opsional)</label>
        <input type="file" id="inpFile" class="form-control" accept=".pdf,.jpg,.jpeg,.png">
      </div>

      <div class="col-12 d-flex gap-2">
        <button class="btn btn-primary" id="btnUpdate">Simpan Perubahan</button>
        <a href="{{ route('kp.index') }}" class="btn btn-outline-secondary">Kembali</a>
      </div>
    </div>
  </div>
</div>

@push('scripts')
<script>window.__KP_ID__ = {{ (int)$id }};</script>
@endpush
@endsection

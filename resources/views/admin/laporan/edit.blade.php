@extends('layouts.admin')

@section('title','Detail Laporan – SKPI UPR')
@section('pageTitle','Detail Laporan SKPI')

@push('head')
  @vite(['resources/js/admin/pages/laporan/edit.js'])
@endpush

@section('content')
<div class="card border-0 shadow-sm">
  <div class="card-body">
    <div id="boxLoading" class="text-muted">Memuat…</div>

    <div id="boxDetail" class="d-none">
      <div class="row g-3">
        <div class="col-md-3">
          <div class="small text-muted">ID</div>
          <div id="vId" class="fw-semibold">-</div>
        </div>
        <div class="col-md-3">
          <div class="small text-muted">NIM</div>
          <div id="vNim" class="fw-semibold">-</div>
        </div>
        <div class="col-md-3">
          <div class="small text-muted">Status</div>
          <span id="vStatus" class="badge text-bg-secondary">-</span>
        </div>
        <div class="col-md-3">
          <div class="small text-muted">No/Tgl Pengesahan</div>
          <div id="vPengesahan">- / -</div>
        </div>

        <div class="col-12">
          <div class="small text-muted">Catatan Verifikasi</div>
          <div id="vCatatan">-</div>
        </div>

        <div class="col-12 d-flex flex-wrap gap-2" id="vFileArea">
          <!-- tombol file/generate diisi JS -->
        </div>
      </div>

      <hr>

      <!-- Form khusus Admin Fakultas (pengesahan) -->
      <div id="formPengesahan" class="d-none">
        <div class="small fw-semibold mb-2">Pengesahan (Admin Fakultas)</div>
        <div class="row g-2">
          <div class="col-md-4">
            <label class="form-label small text-muted">No Pengesahan</label>
            <input type="text" id="noPengesahan" class="form-control" placeholder="Nomor…">
          </div>
          <div class="col-md-4">
            <label class="form-label small text-muted">Tgl Pengesahan</label>
            <input type="date" id="tglPengesahan" class="form-control">
          </div>
          <div class="col-md-4">
            <label class="form-label small text-muted">Catatan (opsional)</label>
            <input type="text" id="catPengesahan" class="form-control" placeholder="Catatan…">
          </div>
        </div>
        <div class="mt-3 d-flex gap-2">
          <button class="btn btn-warning" id="btnSimpanPengesahan">Simpan Pengesahan</button>
        </div>
        <hr>
      </div>

      <!-- Aksi verifikasi/approve sesuai role -->
      <div class="d-flex flex-wrap gap-2" id="boxActions">
        <!-- diisi tombol sesuai role oleh JS -->
      </div>

      <div class="mt-3">
        <a href="{{ route('laporan.index') }}" class="btn btn-outline-secondary">Kembali</a>
      </div>
    </div>
  </div>
</div>

<script>window.__LAP_ID__ = {{ (int)$id }};</script>
@endsection

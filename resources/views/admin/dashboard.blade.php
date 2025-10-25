@extends('layouts.admin')

@section('title','Dashboard – SKPI UPR')
@section('pageTitle','Dashboard')

@push('head')
  @vite(['resources/js/admin/pages/dashboard.js'])
@endpush

@section('content')
  {{-- Row: Statistik ringkas --}}
  <div class="row g-3 mb-3">
    <div class="col-md-4">
      <div class="card border-0 shadow-sm">
        <div class="card-body">
          <div class="text-muted small">Mahasiswa</div>
          <div class="h3 mb-0" id="statMhs">—</div>
        </div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card border-0 shadow-sm">
        <div class="card-body">
          <div class="text-muted small">Prodi</div>
          <div class="h3 mb-0" id="statProdi">—</div>
        </div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card border-0 shadow-sm">
        <div class="card-body">
          <div class="text-muted small">Fakultas</div>
          <div class="h3 mb-0" id="statFak">—</div>
        </div>
      </div>
    </div>
  </div>

  {{-- Row: Sinkronisasi (hanya tampil untuk SuperAdmin/AdminFakultas/AdminJurusan) --}}
  <div id="syncCard" class="card border-0 shadow-sm d-none">
    <div class="card-body">
      <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div>
          <div class="fw-semibold">Sinkronisasi Data</div>
          <div class="text-muted small">
            Endpoint: <code>POST /sync/upttik/all</code>
          </div>
          <div class="small mt-1">
            Status: <span id="syncStatus" class="text-muted">Siap</span>
            <span class="text-muted">•</span>
            Terakhir: <span id="syncTime" class="text-muted">—</span>
          </div>
        </div>
        <div class="d-flex align-items-center gap-2">
          <button id="btnSync" class="btn btn-outline-primary">
            <span class="sync-label">Sinkronkan</span>
            <span class="spinner-border spinner-border-sm align-text-bottom d-none" role="status" aria-hidden="true"></span>
          </button>
          <button id="btnSyncReload" class="btn btn-outline-secondary d-none">Muat Ulang Statistik</button>
        </div>
      </div>
      <div id="syncMsg" class="mt-2 small text-muted"></div>
    </div>
  </div>
@endsection

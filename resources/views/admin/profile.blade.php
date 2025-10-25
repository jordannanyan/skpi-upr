@extends('layouts.admin')

@section('title','Profil Saya – SKPI UPR')
@section('pageTitle','Profil Saya')

@push('head')
  @vite(['resources/js/admin/pages/profile.js'])
@endpush

@section('content')
<div class="row g-3">
  <div class="col-md-4">
    <div class="card border-0 shadow-sm h-100">
      <div class="card-body d-flex flex-column align-items-center text-center">
        <div class="rounded-circle bg-light d-flex align-items-center justify-content-center mb-3" style="width:96px;height:96px;">
          <i class="bi bi-person fs-1 text-secondary"></i>
        </div>
        <div class="h5 mb-0" id="profUsername">—</div>
        <div class="mt-2">
          <span class="badge text-bg-secondary" id="profRole">—</span>
        </div>
        <div class="text-muted small mt-3">
          Terakhir login: <span id="profLastLogin">—</span>
        </div>
      </div>
    </div>
  </div>

  <div class="col-md-8">
    <div class="card border-0 shadow-sm">
      <div class="card-body">
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label small text-muted">Fakultas</label>
            <div class="form-control-plaintext fw-semibold" id="profFakultas">—</div>
          </div>
          <div class="col-md-6">
            <label class="form-label small text-muted">Program Studi</label>
            <div class="form-control-plaintext fw-semibold" id="profProdi">—</div>
          </div>

          <div class="col-12">
            <hr>
          </div>

          <div class="col-12 d-flex flex-wrap gap-2">
            <a class="btn btn-outline-primary" id="btnGoPassword" href="#">
              <i class="bi bi-shield-lock me-1"></i> Ganti Password
            </a>
            <a class="btn btn-outline-secondary" href="{{ route('admin.page') }}">
              <i class="bi bi-speedometer me-1"></i> Kembali ke Dashboard
            </a>
          </div>

          <div class="col-12">
            <div class="alert alert-info d-none mt-3" id="profInfoBox"></div>
            <div class="alert alert-danger d-none mt-3" id="profErrBox"></div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection

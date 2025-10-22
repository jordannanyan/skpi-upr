@extends('layouts.admin')

@section('title','Dashboard – SKPI UPR')
@section('pageTitle','Dashboard')

@push('head')
  @vite(['resources/js/admin/pages/dashboard.js'])
@endpush

@section('content')
  <div class="row g-3">
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
@endsection

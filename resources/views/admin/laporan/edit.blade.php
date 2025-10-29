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

      <!-- Tahapan & Tindakan -->
      <div class="row g-3">
        <div class="col-lg-7">
          <div class="border rounded p-3">
            <div class="small fw-semibold mb-2">Tahapan Pengajuan</div>
            <ol class="list-group list-group-numbered" id="boxStepper">
              <!-- diisi JS: Submitted -> Verified -> Wakadek OK -> Approved -->
            </ol>
          </div>
        </div>
        <div class="col-lg-5">
          <div class="border rounded p-3">
            <div class="small fw-semibold mb-2">Yang Perlu Anda Lakukan</div>
            <div id="boxNextAction" class="alert alert-info py-2 mb-2 d-none"></div>
            <ul id="boxRoleChecklist" class="small mb-0">
              <!-- diisi JS sesuai role -->
            </ul>
          </div>
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

      <hr>

      <!-- Info Mahasiswa -->
      <div class="row g-3">
        <div class="col-12">
          <div class="small fw-semibold mb-2">Informasi Mahasiswa</div>
          <div class="row g-3">
            <div class="col-md-4">
              <div class="small text-muted">Nama</div>
              <div id="mNama" class="fw-semibold">-</div>
            </div>
            <div class="col-md-4">
              <div class="small text-muted">Program Studi</div>
              <div id="mProdi" class="fw-semibold">-</div>
            </div>
            <div class="col-md-4">
              <div class="small text-muted">Fakultas</div>
              <div id="mFak" class="fw-semibold">-</div>
            </div>
          </div>
        </div>

        <!-- SKOR CPL -->
        <div class="col-12">
          <div class="small fw-semibold mb-2">Skor CPL</div>
          <div class="table-responsive">
            <table class="table table-sm table-hover align-middle">
              <thead class="table-light">
                <tr>
                  <th style="width:120px;">Kode CPL</th>
                  <th>Nama CPL</th>
                  <th style="width:120px;">Skor</th>
                </tr>
              </thead>
              <tbody id="tblCplBody">
                <tr><td colspan="3" class="text-muted">Memuat…</td></tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- TA & KP -->
        <div class="col-md-6">
          <div class="small fw-semibold mb-2">Tugas Akhir</div>
          <div class="table-responsive">
            <table class="table table-sm table-hover align-middle">
              <thead class="table-light">
                <tr>
                  <th style="width:120px;">Kategori</th>
                  <th>Judul</th>
                </tr>
              </thead>
              <tbody id="tblTaBody">
                <tr><td colspan="2" class="text-muted">Memuat…</td></tr>
              </tbody>
            </table>
          </div>
        </div>
        <div class="col-md-6">
          <div class="small fw-semibold mb-2">Kerja Praktek</div>
          <div class="table-responsive">
            <table class="table table-sm table-hover align-middle">
              <thead class="table-light">
                <tr>
                  <th>Nama Kegiatan</th>
                  <th style="width:120px;">File</th>
                </tr>
              </thead>
              <tbody id="tblKpBody">
                <tr><td colspan="2" class="text-muted">Memuat…</td></tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Sertifikat -->
        <div class="col-12">
          <div class="small fw-semibold mb-2">Sertifikat</div>
          <div class="table-responsive">
            <table class="table table-sm table-hover align-middle">
              <thead class="table-light">
                <tr>
                  <th>Nama Sertifikasi</th>
                  <th>Kategori</th>
                  <th style="width:90px;">Tahun</th>
                  <th style="width:120px;">File</th>
                </tr>
              </thead>
              <tbody id="tblSertBody">
                <tr><td colspan="4" class="text-muted">Memuat…</td></tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <div class="mt-3">
        <a href="{{ route('laporan.index') }}" class="btn btn-outline-secondary">Kembali</a>
      </div>
    </div>
  </div>
</div>

<script>window.__LAP_ID__ = {{ (int)$id }};</script>
@endsection

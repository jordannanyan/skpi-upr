@extends('layouts.admin')

@section('title','Prodi — SKPI UPR')
@section('pageTitle','Prodi')

@section('content')
<div class="card border-0 shadow-sm mb-3">
  <div class="card-body">
    <div class="row g-2 align-items-end">
      <div class="col-md-4">
        <label class="form-label small text-muted">Cari</label>
        <input type="text" id="proKw" class="form-control" placeholder="nama prodi">
      </div>
      <div class="col-md-4">
        <label class="form-label small text-muted">Fakultas</label>
        <select id="proFak" class="form-select">
          <option value="">— Semua —</option>
        </select>
      </div>
      <div class="col-md-2 d-grid">
        <button class="btn btn-outline-secondary" id="proCari">Terapkan</button>
      </div>
    </div>
  </div>
</div>

<div class="table-responsive">
  <table class="table table-hover align-middle">
    <thead class="table-light">
      <tr>
        <th style="width:100px;">ID</th>
        <th>Nama Prodi</th>
        <th style="width:160px;">Fakultas</th>
        <th style="width:100px;">Jenjang</th>
      </tr>
    </thead>
    <tbody id="proBody">
      <tr><td colspan="4" class="text-center text-muted p-4">Memuat…</td></tr>
    </tbody>
  </table>
</div>
@endsection

@push('scripts')
<script>
(function(){
  const API   = document.getElementById('bridge').dataset.apiBase
  const token = localStorage.getItem('auth_token') || ''
  axios.defaults.baseURL = API
  if (token) axios.defaults.headers.common['Authorization'] = `Bearer ${token}`

  const role  = localStorage.getItem('auth_role') || ''
  const idFak = localStorage.getItem('auth_id_fakultas') || ''

  const selF = document.getElementById('proFak')
  const body = document.getElementById('proBody')
  const kw   = document.getElementById('proKw')

  async function loadMasters(){
    const {data} = await axios.get('/fakultas?per_page=100')
    const arr = data.data || data
    arr.forEach(x=>{
      const opt = document.createElement('option')
      opt.value = x.id; opt.textContent = x.nama_fakultas || `Fakultas ${x.id}`
      selF.appendChild(opt)
    })
    if (['AdminFakultas','Wakadek','Dekan'].includes(role) && idFak) {
      selF.value = idFak
      selF.disabled = true
    }
  }

  async function load(){
    body.innerHTML = `<tr><td colspan="4" class="text-center text-muted p-4">Memuat…</td></tr>`
    let url = `/prodi?per_page=100`
    const q = (kw.value||'').trim()
    const f = selF.value
    if (q) url += `&q=${encodeURIComponent(q)}`
    if (f) url += `&fakultas_id=${encodeURIComponent(f)}`
    if (!f && ['AdminFakultas','Wakadek','Dekan'].includes(role) && idFak) url += `&fakultas_id=${idFak}`

    const {data} = await axios.get(url)
    const rows = data.data || data
    if (!rows.length) {
      body.innerHTML = `<tr><td colspan="4" class="text-center text-muted p-4">Tidak ada data</td></tr>`
      return
    }
    body.innerHTML = ''
    rows.forEach(r=>{
      const tr = document.createElement('tr')
      tr.innerHTML = `<td>${r.id}</td><td>${r.nama_prodi||'-'}</td><td>${r.id_fakultas||'-'}</td><td>${r.jenis_jenjang||'-'}</td>`
      body.appendChild(tr)
    })
  }

  document.getElementById('proCari').addEventListener('click', load)

  ;(async function init(){
    await loadMasters()
    await load()
  })()
})();
</script>
@endpush

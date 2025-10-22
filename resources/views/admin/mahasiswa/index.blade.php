@extends('layouts.admin')

@section('title','Mahasiswa — SKPI UPR')
@section('pageTitle','Mahasiswa')

@section('content')
<div class="card border-0 shadow-sm mb-3">
  <div class="card-body">
    <div class="row g-2 align-items-end">
      <div class="col-md-4">
        <label class="form-label small text-muted">Cari (nama/NIM)</label>
        <input type="text" id="mhsKw" class="form-control" placeholder="mis. 1930 / Thomas">
      </div>
      <div class="col-md-3">
        <label class="form-label small text-muted">Fakultas</label>
        <select id="mhsFak" class="form-select">
          <option value="">— Semua —</option>
        </select>
      </div>
      <div class="col-md-3">
        <label class="form-label small text-muted">Prodi</label>
        <select id="mhsProdi" class="form-select">
          <option value="">— Semua —</option>
        </select>
      </div>
      <div class="col-md-2 d-grid">
        <button class="btn btn-outline-secondary" id="mhsCari">Terapkan</button>
      </div>
    </div>
  </div>
</div>

<div class="table-responsive">
  <table class="table table-hover align-middle">
    <thead class="table-light">
      <tr>
        <th style="width:160px;">NIM</th>
        <th>Nama</th>
        <th style="width:120px;">Prodi</th>
      </tr>
    </thead>
    <tbody id="mhsBody">
      <tr><td colspan="3" class="text-center text-muted p-4">Memuat…</td></tr>
    </tbody>
  </table>
</div>

<div class="d-flex justify-content-between align-items-center">
  <div class="small text-muted" id="mhsInfo">—</div>
  <div class="btn-group">
    <button class="btn btn-sm btn-outline-secondary" id="mhsPrev">‹</button>
    <button class="btn btn-sm btn-outline-secondary" id="mhsNext">›</button>
  </div>
</div>
@endsection

@push('scripts')
<script>
(function(){
  const bridge = document.getElementById('bridge')
  const API = bridge.dataset.apiBase
  const token = localStorage.getItem('auth_token') || ''
  axios.defaults.baseURL = API
  if (token) axios.defaults.headers.common['Authorization'] = `Bearer ${token}`

  const role      = localStorage.getItem('auth_role') || ''   // di-set di admin-shell.js
  const idProdi   = localStorage.getItem('auth_id_prodi') || ''
  const idFak     = localStorage.getItem('auth_id_fakultas') || ''

  const body = document.getElementById('mhsBody')
  const info = document.getElementById('mhsInfo')
  const selF = document.getElementById('mhsFak')
  const selP = document.getElementById('mhsProdi')
  const kw   = document.getElementById('mhsKw')

  let page = 1, last = 1

  // load dropdown Fakultas & Prodi
  async function loadMasters(){
    const [f,p] = await Promise.all([
      axios.get('/fakultas?per_page=100'),
      axios.get('/prodi?per_page=200')
    ])
    const fak = (f.data.data || f.data)
    const pro = (p.data.data || p.data)

    fak.forEach(x=>{
      const opt = document.createElement('option')
      opt.value = x.id; opt.textContent = x.nama_fakultas || `Fakultas ${x.id}`
      selF.appendChild(opt)
    })
    pro.forEach(x=>{
      const opt = document.createElement('option')
      opt.value = x.id; opt.textContent = x.nama_prodi || `Prodi ${x.id}`
      opt.dataset.fak = x.id_fakultas || ''
      selP.appendChild(opt)
    })

    // scope by role (sembunyikan pilihan)
    if (['AdminFakultas','Wakadek','Dekan'].includes(role) && idFak) {
      selF.value = idFak
      filterProdiByFak()
      selF.disabled = true
    }
    if (['AdminJurusan','Kajur'].includes(role) && idProdi) {
      selP.value = idProdi
      selP.disabled = true
      if (idFak) { selF.value = idFak; selF.disabled = true; filterProdiByFak() }
    }
  }

  function filterProdiByFak(){
    const v = selF.value
    ;[...selP.options].forEach((o,i)=>{
      if (i===0) return
      const of = o.dataset.fak || ''
      o.hidden = (v && of !== v)
    })
    if (v) {
      // kalau prodi terpilih tapi tidak satu fakultas, reset
      const cur = selP.selectedOptions[0]
      if (cur && (cur.dataset.fak||'') !== v) selP.value = ''
    }
  }
  selF.addEventListener('change', filterProdiByFak)

  async function load(pageWant=1){
    body.innerHTML = `<tr><td colspan="3" class="text-center text-muted p-4">Memuat…</td></tr>`
    let url = `/mahasiswa?per_page=20&page=${pageWant}`
    const q = (kw.value||'').trim()
    const pf = selF.value, pp = selP.value

    if (q)  url += `&q=${encodeURIComponent(q)}`
    if (pf) url += `&fakultas_id=${encodeURIComponent(pf)}`
    if (pp) url += `&prodi_id=${encodeURIComponent(pp)}`

    // role default scope
    if (!pf && ['AdminFakultas','Wakadek','Dekan'].includes(role) && idFak) url += `&fakultas_id=${idFak}`
    if (!pp && ['AdminJurusan','Kajur'].includes(role) && idProdi) url += `&prodi_id=${idProdi}`

    const {data} = await axios.get(url)
    const rows = data.data || data
    const meta = data.meta || {}
    page = meta.current_page || pageWant
    last = meta.last_page || page

    if (!rows.length) {
      body.innerHTML = `<tr><td colspan="3" class="text-center text-muted p-4">Tidak ada data</td></tr>`
      info.textContent = '—'
      return
    }

    body.innerHTML = ''
    rows.forEach(r=>{
      const tr = document.createElement('tr')
      tr.innerHTML = `<td>${r.nim}</td><td>${r.nama_mahasiswa||'-'}</td><td>${r.id_prodi||'-'}</td>`
      body.appendChild(tr)
    })
    info.textContent = meta.total ? `Hal. ${page}/${last} • ${meta.total} data` : `Hal. ${page}`
  }

  document.getElementById('mhsCari').addEventListener('click', ()=>load(1))
  document.getElementById('mhsPrev').addEventListener('click', ()=>{ if(page>1) load(page-1) })
  document.getElementById('mhsNext').addEventListener('click', ()=>{ if(page<last) load(page+1) })

  ;(async function init(){
    await loadMasters()
    await load(1)
  })()
})();
</script>
@endpush

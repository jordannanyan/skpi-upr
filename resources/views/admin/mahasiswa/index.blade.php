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
{{-- Toolbar Sinkronisasi (selalu tampil di halaman admin) --}}
<div id="mhsSyncBar" class="card border-0 shadow-sm mb-3">
  <div class="card-body d-flex align-items-center justify-content-between flex-wrap gap-2">
    <div>
      <div class="fw-semibold">Sinkronisasi Data Mahasiswa</div>
      <div class="text-muted small">Endpoint: <code>POST /sync/upttik/all</code></div>
      <div class="small mt-1">
        Status: <span id="mhsSyncStatus" class="text-muted">Siap</span>
        <span class="text-muted">•</span>
        Terakhir: <span id="mhsSyncTime" class="text-muted">—</span>
      </div>
    </div>
    <div class="d-flex align-items-center gap-2">
      <button id="mhsSyncBtn" class="btn btn-outline-primary">
        <span class="mhs-sync-label">Sinkronkan</span>
        <span class="spinner-border spinner-border-sm align-text-bottom d-none" role="status" aria-hidden="true"></span>
      </button>
      <button id="mhsSyncReload" class="btn btn-outline-secondary d-none">Muat Ulang Daftar</button>
    </div>
    <div id="mhsSyncMsg" class="w-100 small text-muted mt-2"></div>
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

  const body = document.getElementById('mhsBody')
  const info = document.getElementById('mhsInfo')
  const selF = document.getElementById('mhsFak')
  const selP = document.getElementById('mhsProdi')
  const kw   = document.getElementById('mhsKw')

  // Elements for sync bar
  const btn       = document.getElementById('mhsSyncBtn')
  const spin      = btn?.querySelector('.spinner-border')
  const lbl       = btn?.querySelector('.mhs-sync-label')
  const btnReload = document.getElementById('mhsSyncReload')
  const elStatus  = document.getElementById('mhsSyncStatus')
  const elTime    = document.getElementById('mhsSyncTime')
  const elMsg     = document.getElementById('mhsSyncMsg')

  let page = 1, last = 1

  const escapeHtml = s => String(s ?? '').replace(/[&<>"']/g, m => ({
    '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'
  }[m]))

  function getNamaProdi(r){
    // prefer relasi r.prodi.nama_prodi, fallback accessor r.nama_prodi
    return r?.prodi?.nama_prodi ?? r?.nama_prodi ?? '-'
  }

  // ==== SYNC helpers (tanpa role) ====
  function setSyncBusy(b){
    if (!btn || !spin || !lbl) return
    btn.disabled = b
    spin.classList.toggle('d-none', !b)
    lbl.textContent = b ? 'Menyinkronkan…' : 'Sinkronkan'
  }
  function setSyncStatus(text, ok = true){
    if (!elStatus) return
    elStatus.textContent = text
    elStatus.classList.remove('text-muted','text-success','text-danger')
    elStatus.classList.add(ok ? 'text-success' : 'text-danger')
  }
  function setSyncTime(ts){ if (elTime) elTime.textContent = ts }
  function setSyncMsg(msg, isError=false){
    if (!elMsg) return
    elMsg.textContent = msg || ''
    elMsg.classList.remove('text-muted','text-danger')
    elMsg.classList.add(isError ? 'text-danger' : 'text-muted')
  }
  async function doSync(){
    setSyncBusy(true)
    setSyncStatus('Proses…', true)
    setSyncMsg('')
    try{
      const res = await axios.post('/sync/upttik/all') // no payload
      const nowId = new Date().toLocaleString('id-ID')
      setSyncTime(nowId)
      const msg = res?.data?.message || res?.data?.msg || 'Sinkronisasi berhasil.'
      setSyncMsg(msg, false)
      setSyncStatus('Berhasil', true)
      btnReload?.classList.remove('d-none')
    }catch(err){
      const code = err?.response?.status
      const srv  = err?.response?.data?.message || err?.message || 'Gagal'
      setSyncMsg(`Gagal sinkronisasi${code ? ` (HTTP ${code})` : ''}: ${srv}`, true)
      setSyncStatus('Gagal', false)
    }finally{
      setSyncBusy(false)
    }
  }

  // ==== Masters ====
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
  }

  function filterProdiByFak(){
    const v = selF.value
    ;[...selP.options].forEach((o,i)=>{
      if (i===0) return
      const of = o.dataset.fak || ''
      o.hidden = (v && of !== v)
    })
    if (v) {
      const cur = selP.selectedOptions[0]
      if (cur && (cur.dataset.fak||'') !== v) selP.value = ''
    }
  }
  selF.addEventListener('change', filterProdiByFak)

  // ==== Load table ====
  async function load(pageWant=1){
    body.innerHTML = `<tr><td colspan="3" class="text-center text-muted p-4">Memuat…</td></tr>`
    let url = `/mahasiswa?per_page=20&page=${pageWant}`
    const q = (kw.value||'').trim()
    const pf = selF.value, pp = selP.value

    if (q)  url += `&q=${encodeURIComponent(q)}`
    if (pf) url += `&fakultas_id=${encodeURIComponent(pf)}`
    if (pp) url += `&prodi_id=${encodeURIComponent(pp)}`

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
      tr.innerHTML = `
        <td>${escapeHtml(r.nim)}</td>
        <td>${escapeHtml(r.nama_mahasiswa || '-')}</td>
        <td>${escapeHtml(getNamaProdi(r))}</td>
      `
      body.appendChild(tr)
    })
    info.textContent = meta.total ? `Hal. ${page}/${last} • ${meta.total} data` : `Hal. ${page}`
  }

  // ==== Events ====
  document.getElementById('mhsCari').addEventListener('click', ()=>load(1))
  document.getElementById('mhsPrev').addEventListener('click', ()=>{ if(page>1) load(page-1) })
  document.getElementById('mhsNext').addEventListener('click', ()=>{ if(page<last) load(page+1) })
  btn?.addEventListener('click', doSync)
  btnReload?.addEventListener('click', async ()=>{
    btnReload.classList.add('d-none')
    await load(1)
  })

  // ==== Init ====
  ;(async function init(){
    await loadMasters()
    await load(1)
  })()
})();
</script>
@endpush

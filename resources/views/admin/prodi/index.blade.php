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
{{-- Toolbar Sinkronisasi (selalu tampil di halaman admin) --}}
<div id="proSyncBar" class="card border-0 shadow-sm mb-3">
  <div class="card-body d-flex align-items-center justify-content-between flex-wrap gap-2">
    <div>
      <div class="fw-semibold">Sinkronisasi Data Prodi</div>
      <div class="text-muted small">Endpoint: <code>POST /sync/upttik/all</code></div>
      <div class="small mt-1">
        Status: <span id="proSyncStatus" class="text-muted">Siap</span>
        <span class="text-muted">•</span>
        Terakhir: <span id="proSyncTime" class="text-muted">—</span>
      </div>
    </div>
    <div class="d-flex align-items-center gap-2">
      <button id="proSyncBtn" class="btn btn-outline-primary">
        <span class="pro-sync-label">Sinkronkan</span>
        <span class="spinner-border spinner-border-sm align-text-bottom d-none" role="status" aria-hidden="true"></span>
      </button>
      <button id="proSyncReload" class="btn btn-outline-secondary d-none">Muat Ulang Daftar</button>
    </div>
    <div id="proSyncMsg" class="w-100 small text-muted mt-2"></div>
  </div>
</div>

<div class="table-responsive">
  <table class="table table-hover align-middle">
    <thead class="table-light">
      <tr>
        <th style="width:100px;">ID</th>
        <th>Nama Prodi</th>
        <th style="width:220px;">Fakultas</th>
        <th style="width:120px;">Jenjang</th>
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

  const selF = document.getElementById('proFak')
  const body = document.getElementById('proBody')
  const kw   = document.getElementById('proKw')

  // Sync elements
  const btn   = document.getElementById('proSyncBtn')
  const spin  = btn?.querySelector('.spinner-border')
  const lbl   = btn?.querySelector('.pro-sync-label')
  const btnReload = document.getElementById('proSyncReload')
  const elStatus  = document.getElementById('proSyncStatus')
  const elTime    = document.getElementById('proSyncTime')
  const elMsg     = document.getElementById('proSyncMsg')

  const escapeHtml = s => String(s ?? '').replace(/[&<>"']/g, m => ({
    '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'
  }[m]))

  // Ambil nama fakultas dari relasi jika ada, fallback ke accessor nama_fakultas
  function getNamaFakultas(r){
    return r?.fakultas?.nama_fakultas ?? r?.nama_fakultas ?? '-'
  }

  // ==== SYNC helpers ====
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

  async function loadMasters(){
    const {data} = await axios.get('/fakultas?per_page=100')
    const arr = data.data || data
    arr.forEach(x=>{
      const opt = document.createElement('option')
      opt.value = x.id
      opt.textContent = x.nama_fakultas || `Fakultas ${x.id}`
      selF.appendChild(opt)
    })
  }

  async function load(){
    body.innerHTML = `<tr><td colspan="4" class="text-center text-muted p-4">Memuat…</td></tr>`
    let url = `/prodi?per_page=100`
    const q = (kw.value||'').trim()
    const f = selF.value
    if (q) url += `&q=${encodeURIComponent(q)}`
    if (f) url += `&fakultas_id=${encodeURIComponent(f)}`

    const {data} = await axios.get(url)
    const rows = data.data || data
    if (!rows.length) {
      body.innerHTML = `<tr><td colspan="4" class="text-center text-muted p-4">Tidak ada data</td></tr>`
      return
    }
    body.innerHTML = ''
    rows.forEach(r=>{
      const tr = document.createElement('tr')
      tr.innerHTML = `
        <td>${escapeHtml(r.id)}</td>
        <td>${escapeHtml(r.nama_prodi || '-')}</td>
        <td>${escapeHtml(getNamaFakultas(r))}</td>
        <td>${escapeHtml(r.jenis_jenjang || '-')}</td>
      `
      body.appendChild(tr)
    })
  }

  document.getElementById('proCari').addEventListener('click', load)
  btn?.addEventListener('click', doSync)
  btnReload?.addEventListener('click', async ()=>{
    btnReload.classList.add('d-none')
    await load()
  })

  ;(async function init(){
    await loadMasters()
    await load()
  })()
})();
</script>
@endpush

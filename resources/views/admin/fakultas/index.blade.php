@extends('layouts.admin')

@section('title','Fakultas — SKPI UPR')
@section('pageTitle','Fakultas')

@section('content')


{{-- Filter & Search --}}
<div class="card border-0 shadow-sm mb-3">
  <div class="card-body">
    <div class="row g-2 align-items-end">
      <div class="col-md-6">
        <label class="form-label small text-muted">Cari (nama fakultas / dekan)</label>
        <input type="text" id="fakKw" class="form-control" placeholder="mis. Teknik / Prof. Andi">
      </div>
      <div class="col-md-2 d-grid">
        <button class="btn btn-outline-secondary" id="fakCari">Terapkan</button>
      </div>
    </div>
  </div>
</div>

{{-- Toolbar Sinkronisasi (selalu tampil di halaman admin) --}}
<div id="fakSyncBar" class="card border-0 shadow-sm mb-3">
  <div class="card-body d-flex align-items-center justify-content-between flex-wrap gap-2">
    <div>
      <div class="fw-semibold">Sinkronisasi Data Fakultas</div>
      <div class="text-muted small">Endpoint: <code>POST /sync/upttik/all</code></div>
      <div class="small mt-1">
        Status: <span id="fakSyncStatus" class="text-muted">Siap</span>
        <span class="text-muted">•</span>
        Terakhir: <span id="fakSyncTime" class="text-muted">—</span>
      </div>
    </div>
    <div class="d-flex align-items-center gap-2">
      <button id="fakSyncBtn" class="btn btn-outline-primary">
        <span class="fak-sync-label">Sinkronkan</span>
        <span class="spinner-border spinner-border-sm align-text-bottom d-none" role="status" aria-hidden="true"></span>
      </button>
      <button id="fakSyncReload" class="btn btn-outline-secondary d-none">Muat Ulang Daftar</button>
    </div>
    <div id="fakSyncMsg" class="w-100 small text-muted mt-2"></div>
  </div>
</div>

<div class="table-responsive">
  <table class="table table-hover align-middle">
    <thead class="table-light">
      <tr>
        <th style="width:100px;">ID</th>
        <th>Nama Fakultas</th>
        <th>Dekan</th>
      </tr>
    </thead>
    <tbody id="fakBody">
      <tr><td colspan="3" class="text-center text-muted p-4">Memuat…</td></tr>
    </tbody>
  </table>
</div>

<div class="d-flex justify-content-between align-items-center">
  <div class="small text-muted" id="fakInfo">—</div>
  <div class="btn-group">
    <button class="btn btn-sm btn-outline-secondary" id="fakPrev">‹</button>
    <button class="btn btn-sm btn-outline-secondary" id="fakNext">›</button>
  </div>
</div>
@endsection

@push('scripts')
<script>
(function(){
  const API   = document.getElementById('bridge').dataset.apiBase
  const token = localStorage.getItem('auth_token') || ''
  axios.defaults.baseURL = API
  if (token) axios.defaults.headers.common['Authorization'] = `Bearer ${token}`

  const body = document.getElementById('fakBody')
  const info = document.getElementById('fakInfo')
  const kw   = document.getElementById('fakKw')
  const btnCari = document.getElementById('fakCari')
  const btnPrev = document.getElementById('fakPrev')
  const btnNext = document.getElementById('fakNext')

  // Sync elements
  const btnSync   = document.getElementById('fakSyncBtn')
  const spin      = btnSync?.querySelector('.spinner-border')
  const lbl       = btnSync?.querySelector('.fak-sync-label')
  const btnReload = document.getElementById('fakSyncReload')
  const elStatus  = document.getElementById('fakSyncStatus')
  const elTime    = document.getElementById('fakSyncTime')
  const elMsg     = document.getElementById('fakSyncMsg')

  let page = 1, last = 1

  const escapeHtml = s => String(s ?? '').replace(/[&<>"']/g, m => ({
    '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'
  }[m]))

  async function load(pageWant = 1){
    body.innerHTML = `<tr><td colspan="3" class="text-center text-muted p-4">Memuat…</td></tr>`
    let url = `/fakultas?per_page=20&page=${pageWant}`
    const q = (kw.value || '').trim()
    if (q) url += `&q=${encodeURIComponent(q)}`

    const { data } = await axios.get(url)
    const rows = data.data || data
    const meta = data.meta || {}

    page = meta.current_page || pageWant
    last = meta.last_page || page

    if (!rows.length){
      body.innerHTML = `<tr><td colspan="3" class="text-center text-muted p-4">Tidak ada data</td></tr>`
      info.textContent = '—'
      return
    }

    body.innerHTML = rows.map(r => `
      <tr>
        <td>${escapeHtml(r.id)}</td>
        <td>${escapeHtml(r.nama_fakultas || '-')}</td>
        <td>${escapeHtml(r.nama_dekan || '-')}</td>
      </tr>
    `).join('')

    info.textContent = meta.total ? `Hal. ${page}/${last} • ${meta.total} data` : `Hal. ${page}`
  }

  // ==== Sinkronisasi ====
  function setSyncBusy(b){
    if (!btnSync || !spin || !lbl) return
    btnSync.disabled = b
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

  // Events
  kw.addEventListener('keydown', (e)=>{ if (e.key === 'Enter') load(1) })
  btnCari.addEventListener('click', ()=>load(1))
  btnPrev.addEventListener('click', ()=>{ if (page > 1) load(page - 1) })
  btnNext.addEventListener('click', ()=>{ if (page < last) load(page + 1) })

  btnSync?.addEventListener('click', doSync)
  btnReload?.addEventListener('click', async ()=>{
    btnReload.classList.add('d-none')
    await load(1)
  })

  // init
  load(1)
})();
</script>
@endpush

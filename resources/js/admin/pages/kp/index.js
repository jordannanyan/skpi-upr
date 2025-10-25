// resources/js/admin/pages/kp/index.js
import { api } from '../../../services/api'
import { auth } from '../../../services/auth'

const $ = s => document.querySelector(s)
const $$ = s => document.querySelectorAll(s)

let me = null
let role = 'AdminJurusan'
const bridge = document.getElementById('bridge')

const body = $('#kpBody')
const info = $('#kpInfo')
const inpKw = $('#kpKw')
const inpNim = $('#kpNim')
const inpNama = $('#kpNama')
const selFak = $('#kpFak')
const selPro = $('#kpProdi')

let page = 1, last = 1

const escapeHtml = s => String(s ?? '').replace(/[&<>"']/g, m => ({
  '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'
}[m]))

// ===== Normalisasi URL file (perbaiki kasus localhost vs :8000)
function normalizeFileUrl(u) {
  if (!u) return null
  try {
    // Jika sudah relatif (/storage/...), langsung pakai
    if (u.startsWith('/')) return u
    const parsed = new URL(u, window.location.origin)
    // paksa origin mengikuti halaman saat ini (termasuk :8000)
    parsed.protocol = window.location.protocol
    parsed.host = window.location.host
    return parsed.toString()
  } catch {
    // fallback: coba ambil pathname jika ada "http(s)://host/path"
    const m = u.match(/^https?:\/\/[^/]+(\/.*)$/i)
    return m ? m[1] : u
  }
}

// ===== Ambil nama/prodi/fakultas dari payload
function getNama(r){ return r?.nama_mhs ?? r?.mhs?.nama_mahasiswa ?? '-' }
function getProdi(r){ return r?.nama_prodi ?? r?.prodi?.nama_prodi ?? '-' }
function getFak(r){ return r?.nama_fakultas ?? r?.prodi?.fakultas?.nama_fakultas ?? '-' }

// ===== Aksi per baris
function renderActions(r){
  const linkEdit = `<a class="btn btn-sm btn-outline-primary" href="/admin/kp/${r.id}/edit">Edit</a>`
  const fileUrl = normalizeFileUrl(r.file_url)
  const dl = fileUrl
    ? `<a class="btn btn-sm btn-outline-secondary" target="_blank" href="${fileUrl}">Download</a>`
    : ''
  const canDelete = ['AdminJurusan','Kajur','SuperAdmin'].includes(role)
  const del = canDelete
    ? `<button class="btn btn-sm btn-outline-danger" data-act="del" data-id="${r.id}">Hapus</button>`
    : ''
  return [linkEdit, dl, del].filter(Boolean).join(' ')
}

async function loadMe(){
  const { data } = await api.get('/me')
  me = data
  role = data.role
  const canCreate = ['AdminJurusan','Kajur','SuperAdmin'].includes(role)
  if (!canCreate) $('#btnGoCreate')?.classList.add('d-none')
}

function applyScope(url){
  if (role === 'AdminJurusan' || role === 'Kajur') {
    if (me?.id_prodi) url += `&prodi_id=${me.id_prodi}`
  }
  if (['AdminFakultas','Wakadek','Dekan'].includes(role)) {
    if (me?.id_fakultas) url += `&fakultas_id=${me.id_fakultas}`
  }
  return url
}

// ===== Masters (dropdown Fakultas & Prodi)
async function loadMasters(){
  const [f,p] = await Promise.all([
    api.get('/fakultas?per_page=100'),
    api.get('/prodi?per_page=200'),
  ])
  const faks = f.data.data || f.data
  const pros = p.data.data || p.data

  selFak.innerHTML = `<option value="">— Semua —</option>`
  faks.forEach(x=>{
    const opt = document.createElement('option')
    opt.value = x.id
    opt.textContent = x.nama_fakultas || `Fakultas ${x.id}`
    selFak.appendChild(opt)
  })

  selPro.innerHTML = `<option value="">— Semua —</option>`
  pros.forEach(x=>{
    const opt = document.createElement('option')
    opt.value = x.id
    opt.textContent = x.nama_prodi || `Prodi ${x.id}`
    opt.dataset.fak = x.id_fakultas || ''
    selPro.appendChild(opt)
  })

  // filter prodi by fakultas (client-side)
  const filterProdiByFak = ()=>{
    const v = selFak.value
    ;[...selPro.options].forEach((o,i)=>{
      if (i===0) return
      o.hidden = (v && (o.dataset.fak || '') !== v)
    })
    if (v) {
      const cur = selPro.selectedOptions[0]
      if (cur && (cur.dataset.fak||'') !== v) selPro.value = ''
    }
  }
  selFak.addEventListener('change', filterProdiByFak)
  filterProdiByFak()
}

async function loadKP(pageWant=1){
  body.innerHTML = `<tr><td colspan="7" class="text-center text-muted p-4">Memuat…</td></tr>`

  let url = `/kp?per_page=30&page=${pageWant}`
  const kw   = (inpKw?.value || '').trim()
  const nim  = (inpNim?.value || '').trim()
  const nama = (inpNama?.value || '').trim()
  const fkid = selFak?.value || ''
  const prid = selPro?.value || ''

  if (kw)   url += `&q=${encodeURIComponent(kw)}`
  if (nim)  url += `&nim=${encodeURIComponent(nim)}`
  // nama mahasiswa → kalau backend belum punya param khusus, masukkan ke q juga
  if (nama) url += `&q=${encodeURIComponent(nama)}`
  if (fkid) url += `&fakultas_id=${encodeURIComponent(fkid)}`
  if (prid) url += `&prodi_id=${encodeURIComponent(prid)}`
  url = applyScope(url)

  const { data } = await api.get(url)
  const rows = data.data || data
  const meta = data.meta || {}
  page = meta.current_page || pageWant
  last = meta.last_page || page

  if (!rows.length){
    body.innerHTML = `<tr><td colspan="7" class="text-center text-muted p-4">Tidak ada data</td></tr>`
    info.textContent = '—'
    return
  }

  body.innerHTML = rows.map(r=>{
    const fileUrl = normalizeFileUrl(r.file_url)
    return `
      <tr>
        <td>${r.id}</td>
        <td><code>${escapeHtml(r.nim || '-')}</code></td>
        <td>${escapeHtml(getNama(r))}</td>
        <td>${escapeHtml(getProdi(r))}</td>
        <td>${escapeHtml(getFak(r))}</td>
        <td>${escapeHtml(r.nama_kegiatan || '-')}</td>
        <td class="d-flex flex-wrap gap-2">${renderActions({...r, file_url: fileUrl})}</td>
      </tr>
    `
  }).join('')

  info.textContent = meta.total ? `Hal. ${page}/${last} • ${meta.total} data` : `Hal. ${page}`
}

// ===== Events
$('#kpCari')?.addEventListener('click', ()=>loadKP(1))
inpKw?.addEventListener('keydown', e => { if(e.key==='Enter') loadKP(1) })
inpNim?.addEventListener('keydown', e => { if(e.key==='Enter') loadKP(1) })
inpNama?.addEventListener('keydown', e => { if(e.key==='Enter') loadKP(1) })
$('#kpPrev')?.addEventListener('click', ()=>{ if(page>1) loadKP(page-1) })
$('#kpNext')?.addEventListener('click', ()=>{ if(page<last) loadKP(page+1) })

// hapus
$('#kpBody')?.addEventListener('click', async (e)=>{
  const btn = e.target.closest('button[data-act="del"]')
  if(!btn) return
  const id = btn.dataset.id
  if (!confirm('Hapus data KP ini?')) return
  try{
    await api.delete(`/kp/${id}`)
    await loadKP(page)
  }catch(err){
    alert(err?.response?.data?.message || err.message)
  }
})

;(async function init(){
  try{
    await loadMe()
    await loadMasters()
    await loadKP(1)
  }catch(err){
    auth.clear()
    window.location.replace(bridge?.dataset?.loginUrl || '/login')
  }
})()

// resources/js/admin/pages/sertif/index.js
import { api } from '../../../services/api'
import { auth } from '../../../services/auth'

const $ = s => document.querySelector(s)

let me = null
let role = 'AdminJurusan'
const bridge = document.getElementById('bridge')

// elements
const body = $('#sfBody')
const inpKw = $('#sfKw')
const inpNim = $('#sfNim')
const inpKat = $('#sfKat')
const selFak = $('#sfFak')
const selPro = $('#sfProdi')

const escapeHtml = s => String(s ?? '').replace(/[&<>"']/g, m => ({
  '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'
}[m]))

// ==== Ambil nama/prodi/fakultas dari payload (akomodasi berbagai bentuk) ====
const getNama = r => r?.nama_mhs ?? r?.mhs?.nama_mahasiswa ?? r?.mahasiswa?.nama_mahasiswa ?? '-'
const getProdi = r => r?.nama_prodi ?? r?.prodi?.nama_prodi ?? r?.mhs?.prodi?.nama_prodi ?? r?.mahasiswa?.prodi?.nama_prodi ?? '-'
const getFak   = r => r?.nama_fakultas ?? r?.prodi?.fakultas?.nama_fakultas ?? r?.mhs?.prodi?.fakultas?.nama_fakultas ?? r?.mahasiswa?.prodi?.fakultas?.nama_fakultas ?? '-'

// ==== Perbaiki URL download agar sesuai origin (termasuk :8000) ====
function normalizeFileUrl(u) {
  if (!u) return null
  try {
    if (u.startsWith('/')) return u // relative path ok
    const parsed = new URL(u, window.location.origin)
    parsed.protocol = window.location.protocol
    parsed.host = window.location.host
    return parsed.toString()
  } catch {
    const m = u.match(/^https?:\/\/[^/]+(\/.*)$/i)
    return m ? m[1] : u
  }
}

function renderActions(r){
  const linkEdit = `<a class="btn btn-sm btn-outline-primary" href="/admin/sertifikasi/${r.id}/edit">Edit</a>`
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

// ==== Masters (Fakultas & Prodi) ====
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

async function loadSertif(){
  body.innerHTML = `<tr><td colspan="8" class="text-center text-muted p-4">Memuat…</td></tr>`

  let url = '/sertifikasi?per_page=30'
  const kw  = inpKw?.value?.trim()
  const nim = inpNim?.value?.trim()
  const kat = inpKat?.value?.trim()
  const fkid = selFak?.value || ''
  const prid = selPro?.value || ''

  if (kw)  url += '&q=' + encodeURIComponent(kw)
  if (nim) url += '&nim=' + encodeURIComponent(nim)
  if (kat) url += '&kategori=' + encodeURIComponent(kat)
  if (fkid) url += '&fakultas_id=' + encodeURIComponent(fkid)
  if (prid) url += '&prodi_id=' + encodeURIComponent(prid)
  url = applyScope(url)

  const { data } = await api.get(url)
  const rows = data.data || data

  if (!rows.length){
    body.innerHTML = `<tr><td colspan="8" class="text-center text-muted p-4">Tidak ada data</td></tr>`
    return
  }

  body.innerHTML = rows.map(r=>{
    const nama = escapeHtml(getNama(r))
    const prodi = escapeHtml(getProdi(r))
    const fak = escapeHtml(getFak(r))
    const katv = escapeHtml(r.kategori_sertifikasi ?? r.kategori ?? '-')
    const nm = escapeHtml(r.nama_sertifikasi ?? r.nama ?? '-')
    const fileUrl = normalizeFileUrl(r.file_url)

    return `
      <tr>
        <td>${r.id}</td>
        <td><code>${escapeHtml(r.nim || '-')}</code></td>
        <td>${nama}</td>
        <td>${prodi}</td>
        <td>${fak}</td>
        <td>${katv}</td>
        <td>${nm}</td>
        <td class="d-flex flex-wrap gap-2">${renderActions({...r, file_url: fileUrl})}</td>
      </tr>
    `
  }).join('')
}

$('#sfCari')?.addEventListener('click', loadSertif)
inpKw?.addEventListener('keydown', e => { if(e.key==='Enter') loadSertif() })
inpNim?.addEventListener('keydown', e => { if(e.key==='Enter') loadSertif() })
inpKat?.addEventListener('keydown', e => { if(e.key==='Enter') loadSertif() })

// hapus
$('#sfBody')?.addEventListener('click', async (e)=>{
  const btn = e.target.closest('button[data-act="del"]')
  if(!btn) return
  const id = btn.dataset.id
  if (!confirm('Hapus data sertifikasi ini?')) return
  try{
    await api.delete(`/sertifikasi/${id}`)
    await loadSertif()
  }catch(err){
    alert(err?.response?.data?.message || err.message)
  }
})

;(async function init(){
  try{
    await loadMe()
    await loadMasters()
    await loadSertif()
  }catch(err){
    auth.clear()
    window.location.replace(bridge?.dataset?.loginUrl || '/login')
  }
})()

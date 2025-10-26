// js/admin/pages/laporan/index.js

import { api } from '../../../services/api'
import { auth } from '../../../services/auth'

// util
const $ = s => document.querySelector(s)
const $$ = s => document.querySelectorAll(s)
const escapeHtml = s => String(s ?? '').replace(/[&<>"']/g, m => ({
  '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'
}[m]))

let me = null
let role = 'AdminJurusan' // default fallback
const bridge = document.getElementById('bridge')
const ADMIN_URL = bridge?.dataset?.adminUrl || '/admin'

// badge helper
function badge(st){
  return st==='approved'   ? 'success'  :
         st==='rejected'   ? 'danger'   :
         st==='verified'   ? 'primary'  :
         st==='wakadek_ok' ? 'warning'  : 'secondary'
}

function roleDefaultFilter(base){
  if (role === 'Kajur')         base += '&status=submitted&prodi_id='    + (me?.id_prodi ?? '')
  if (role === 'AdminJurusan')  base += '&prodi_id='                     + (me?.id_prodi ?? '')
  if (role === 'AdminFakultas') base += '&fakultas_id='                  + (me?.id_fakultas ?? '')
  if (role === 'Wakadek')       base += '&status=verified&fakultas_id='  + (me?.id_fakultas ?? '')
  if (role === 'Dekan')         base += '&status=wakadek_ok&fakultas_id='+ (me?.id_fakultas ?? '')
  return base
}

function renderActions(r){
  const parts = []

  // Detail
  parts.push(`
    <a class="btn btn-sm btn-outline-primary d-inline-flex align-items-center justify-content-center"
       href="/admin/laporan/${r.id}/edit"
       title="Detail" aria-label="Detail">
      <i class="bi bi-eye"></i>
    </a>
  `)

  // File (jika ada)
  if (r.file_url) {
    parts.push(`
      <a class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center justify-content-center"
         target="_blank" href="${r.file_url}"
         title="Lihat File" aria-label="Lihat File">
        <i class="bi bi-file-earmark"></i>
      </a>
    `)
  }

  // Regenerate (approved)
  if (r.status === 'approved') {
    parts.push(`
      <button class="btn btn-sm btn-outline-dark d-inline-flex align-items-center justify-content-center"
              data-act="generate" data-id="${r.id}"
              title="Generate Ulang" aria-label="Generate Ulang">
        <i class="bi bi-arrow-clockwise"></i>
      </button>
    `)
  }

  // Delete (SuperAdmin)
  if (role === 'SuperAdmin') {
    parts.push(`
      <button class="btn btn-sm btn-outline-danger d-inline-flex align-items-center justify-content-center"
              data-act="delete" data-id="${r.id}"
              title="Hapus" aria-label="Hapus">
        <i class="bi bi-trash"></i>
      </button>
    `)
  }

  return parts.join(' ')
}


async function loadMe(){
  const { data } = await api.get('/me')
  me = data
  role = data.role
  const canCreate = ['AdminJurusan','Kajur','SuperAdmin'].includes(role)
  if (!canCreate) $('#btnGoCreate')?.classList.add('d-none')
}

async function loadLaporan(){
  let url = '/laporan-skpi?per_page=30'
  const nim = $('#lapNim')?.value?.trim()
  const st  = $('#lapStatus')?.value || ''
  if (nim) url += '&nim=' + encodeURIComponent(nim)
  if (st)  url += '&status=' + encodeURIComponent(st)
  url = roleDefaultFilter(url)

  const body = $('#lapBody')
  body.innerHTML = `<tr><td colspan="9" class="text-center text-muted p-4">Memuat…</td></tr>`

  const { data } = await api.get(url)
  const rows = data.data || data

  if (!rows.length){
    body.innerHTML = `<tr><td colspan="9" class="text-center text-muted p-4">Tidak ada data</td></tr>`
    return
  }

  body.innerHTML = rows.map(r => {
    const nt  = (r.no_pengesahan || '-') + ' / ' + (r.tgl_pengesahan || '-')
    const cat = r.catatan_verifikasi || '-'
    const nama = escapeHtml(r.nama_mhs || '-')
    const prodi = escapeHtml(r.nama_prodi || '-')
    const fakultas = escapeHtml(r.nama_fakultas || '-')

    return `
      <tr>
        <td>${r.id}</td>
        <td><code>${r.nim}</code></td>
        <td>${nama}</td>
        <td>${prodi}</td>
        <td>${fakultas}</td>
        <td><span class="badge text-bg-${badge(r.status)}">${r.status}</span></td>
        <td>${nt}</td>
        <td>${escapeHtml(cat)}</td>
        <td class="d-flex flex-wrap gap-2">${renderActions(r)}</td>
      </tr>
    `
  }).join('')
}

$('#lapCari')?.addEventListener('click', loadLaporan)
$('#lapStatus')?.addEventListener('change', loadLaporan)
$('#lapNim')?.addEventListener('keydown', (e)=>{ if(e.key==='Enter') loadLaporan() })

// handle actions dari list
$('#lapBody')?.addEventListener('click', async (e)=>{
  const btnGen = e.target.closest('button[data-act="generate"]')
  const btnDel = e.target.closest('button[data-act="delete"]')

  // Regenerate
  if (btnGen) {
    const id = btnGen.dataset.id
    btnGen.disabled = true
    try{
      const { data } = await api.post(`/laporan-skpi/${id}/regenerate`)
      if (data.file_url) window.open(data.file_url,'_blank')
      await loadLaporan()
    }catch(err){
      alert(err?.response?.data?.message || err.message)
    }finally{
      btnGen.disabled = false
    }
    return
  }

  // Delete (SuperAdmin only)
  if (btnDel) {
    const id = btnDel.dataset.id
    if (!confirm(`Hapus laporan #${id}? Tindakan ini tidak bisa dibatalkan.`)) return
    btnDel.disabled = true
    try{
      await api.delete(`/laporan-skpi/${id}`)
      await loadLaporan()
    }catch(err){
      alert(err?.response?.data?.message || err.message)
    }finally{
      btnDel.disabled = false
    }
  }
})

;(async function init(){
  try{
    await loadMe()
    await loadLaporan()
  }catch(err){
    auth.clear()
    window.location.replace(bridge?.dataset?.loginUrl || '/login')
  }
})()

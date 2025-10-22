import { api } from '../../../services/api'
import { auth } from '../../../services/auth'

const $ = s => document.querySelector(s)
const $$ = s => document.querySelectorAll(s)

let me = null
let role = 'AdminJurusan'
const bridge = document.getElementById('bridge')

function renderActions(r){
  const linkEdit = `<a class="btn btn-sm btn-outline-primary" href="/admin/kp/${r.id}/edit">Edit</a>`
  const dl = r.file_url
    ? `<a class="btn btn-sm btn-outline-secondary" target="_blank" href="${r.file_url}">Download</a>`
    : ''
  // Hapus: hanya AdminJurusan/Kajur/SuperAdmin
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
  // sembunyikan create jika bukan AdminJurusan/Kajur/SuperAdmin
  const canCreate = ['AdminJurusan','Kajur','SuperAdmin'].includes(role)
  if (!canCreate) $('#btnGoCreate')?.classList.add('d-none')
}

function applyScope(url){
  // default scope filter berdasarkan role
  if (role === 'AdminJurusan' || role === 'Kajur') {
    if (me?.id_prodi) url += `&prodi_id=${me.id_prodi}`
  }
  if (['AdminFakultas','Wakadek','Dekan'].includes(role)) {
    if (me?.id_fakultas) url += `&fakultas_id=${me.id_fakultas}`
  }
  return url
}

async function loadKP(){
  let url = '/kp?per_page=30'
  const kw  = $('#kpKw')?.value?.trim()
  const nim = $('#kpNim')?.value?.trim()
  if (kw)  url += '&q=' + encodeURIComponent(kw)
  if (nim) url += '&nim=' + encodeURIComponent(nim)
  url = applyScope(url)

  const { data } = await api.get(url)
  const rows = data.data || data
  const body = $('#kpBody'); body.innerHTML = ''
  if (!rows.length){
    body.innerHTML = `<tr><td colspan="5" class="text-center text-muted p-4">Tidak ada data</td></tr>`
    return
  }
  rows.forEach(r=>{
    const cert = r.file_url ? 'Ada' : '—'
    const tr = document.createElement('tr')
    tr.innerHTML = `
      <td>${r.id}</td>
      <td>${r.nim}</td>
      <td>${r.nama_kegiatan || '-'}</td>
      <td>${cert}</td>
      <td class="d-flex flex-wrap gap-2">${renderActions(r)}</td>
    `
    body.appendChild(tr)
  })
}

$('#kpCari')?.addEventListener('click', loadKP)
$('#kpKw')?.addEventListener('keydown', e => { if(e.key==='Enter') loadKP() })
$('#kpNim')?.addEventListener('keydown', e => { if(e.key==='Enter') loadKP() })

// hapus
$('#kpBody')?.addEventListener('click', async (e)=>{
  const btn = e.target.closest('button[data-act="del"]')
  if(!btn) return
  const id = btn.dataset.id
  if (!confirm('Hapus data KP ini?')) return
  try{
    await api.delete(`/kp/${id}`)
    await loadKP()
  }catch(err){
    alert(err?.response?.data?.message || err.message)
  }
})

;(async function init(){
  try{
    await loadMe()
    await loadKP()
  }catch(err){
    auth.clear()
    window.location.replace(bridge?.dataset?.loginUrl || '/login')
  }
})()

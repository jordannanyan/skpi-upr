import { api } from '../../../services/api'
import { auth } from '../../../services/auth'

const $ = s => document.querySelector(s)

let me = null
let role = 'AdminJurusan'
const bridge = document.getElementById('bridge')

function renderActions(r){
  const linkEdit = `<a class="btn btn-sm btn-outline-primary" href="/admin/sertifikasi/${r.id}/edit">Edit</a>`
  const dl = r.file_url
    ? `<a class="btn btn-sm btn-outline-secondary" target="_blank" href="${r.file_url}">Download</a>`
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

async function loadSertif(){
  let url = '/sertifikasi?per_page=30'
  const kw  = $('#sfKw')?.value?.trim()
  const nim = $('#sfNim')?.value?.trim()
  const kat = $('#sfKat')?.value?.trim()
  if (kw)  url += '&q=' + encodeURIComponent(kw)
  if (nim) url += '&nim=' + encodeURIComponent(nim)
  if (kat) url += '&kategori=' + encodeURIComponent(kat)
  url = applyScope(url)

  const { data } = await api.get(url)
  const rows = data.data || data
  const body = $('#sfBody'); body.innerHTML = ''
  if (!rows.length){
    body.innerHTML = `<tr><td colspan="6" class="text-center text-muted p-4">Tidak ada data</td></tr>`
    return
  }
  rows.forEach(r=>{
    const cert = r.file_url ? 'Ada' : '—'
    const tr = document.createElement('tr')
    tr.innerHTML = `
      <td>${r.id}</td>
      <td>${r.nim}</td>
      <td>${r.kategori_sertifikasi || '-'}</td>
      <td>${r.nama_sertifikasi || '-'}</td>
      <td>${cert}</td>
      <td class="d-flex flex-wrap gap-2">${renderActions(r)}</td>
    `
    body.appendChild(tr)
  })
}

$('#sfCari')?.addEventListener('click', loadSertif)
$('#sfKw')?.addEventListener('keydown', e => { if(e.key==='Enter') loadSertif() })
$('#sfNim')?.addEventListener('keydown', e => { if(e.key==='Enter') loadSertif() })
$('#sfKat')?.addEventListener('keydown', e => { if(e.key==='Enter') loadSertif() })

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
    await loadSertif()
  }catch(err){
    auth.clear()
    window.location.replace(bridge?.dataset?.loginUrl || '/login')
  }
})()

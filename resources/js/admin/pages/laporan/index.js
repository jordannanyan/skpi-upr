//js/admin/pages/laporan/index.js

import { api } from '../../../services/api'
import { auth } from '../../../services/auth'

// util
const $ = s => document.querySelector(s)
const $$ = s => document.querySelectorAll(s)

let me = null
let role = 'AdminJurusan' // default fallback
const bridge = document.getElementById('bridge')
const ADMIN_URL = bridge?.dataset?.adminUrl || '/admin'

// badge helper
function badge(st){
  return st==='approved' ? 'success' :
         st==='rejected' ? 'danger'  :
         st==='verified' ? 'primary' :
         st==='wakadek_ok' ? 'warning' : 'secondary'
}

function roleDefaultFilter(base){
  if (role === 'Kajur')        base += '&status=submitted&prodi_id=' + (me?.id_prodi ?? '')
  if (role === 'AdminJurusan') base += '&prodi_id=' + (me?.id_prodi ?? '')
  if (role === 'AdminFakultas')base += '&fakultas_id=' + (me?.id_fakultas ?? '')
  if (role === 'Wakadek')      base += '&status=verified&fakultas_id=' + (me?.id_fakultas ?? '')
  if (role === 'Dekan')        base += '&status=wakadek_ok&fakultas_id=' + (me?.id_fakultas ?? '')
  return base
}

function renderActions(r){
  // tombol ke edit page sesuai role
  const linkEdit = `<a class="btn btn-sm btn-outline-primary" href="/admin/laporan/${r.id}/edit">Detail</a>`

  // download jika file ada
  const dl = r.file_url ? `<a class="btn btn-sm btn-outline-secondary" target="_blank" href="${r.file_url}">File</a>` : ''

  // generate jika approved
  const gen = (r.status==='approved')
    ? `<button class="btn btn-sm btn-outline-dark" data-act="generate" data-id="${r.id}">Generate</button>`
    : ''

  return [linkEdit, dl, gen].filter(Boolean).join(' ')
}

async function loadMe(){
  const { data } = await api.get('/me')
  me = data
  role = data.role
  // sembunyikan tombol create utk role yg tak boleh submit
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

  const { data } = await api.get(url)
  const rows = data.data || data
  const body = $('#lapBody'); body.innerHTML = ''
  if (!rows.length){
    body.innerHTML = `<tr><td colspan="6" class="text-center text-muted p-4">Tidak ada data</td></tr>`
    return
  }

  rows.forEach(r => {
    const nt = (r.no_pengesahan||'-') + ' / ' + (r.tgl_pengesahan||'-')
    const cat = r.catatan_verifikasi || '-'
    const tr = document.createElement('tr')
    tr.innerHTML = `
      <td>${r.id}</td>
      <td>${r.nim}</td>
      <td><span class="badge text-bg-${badge(r.status)}">${r.status}</span></td>
      <td>${nt}</td>
      <td>${cat}</td>
      <td class="d-flex flex-wrap gap-2">${renderActions(r)}</td>
    `
    body.appendChild(tr)
  })
}

$('#lapCari')?.addEventListener('click', loadLaporan)
$('#lapStatus')?.addEventListener('change', loadLaporan)
$('#lapNim')?.addEventListener('keydown', (e)=>{ if(e.key==='Enter') loadLaporan() })

// handle generate dari list
$('#lapBody')?.addEventListener('click', async (e)=>{
  const btn = e.target.closest('button[data-act="generate"]')
  if(!btn) return
  const id = btn.dataset.id
  try{
    const { data } = await api.post(`/laporan-skpi/${id}/regenerate`)
    if (data.file_url) window.open(data.file_url,'_blank')
    await loadLaporan()
  }catch(err){
    alert(err?.response?.data?.message || err.message)
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

//js/admin/pages/laporan/edit.js

import { api } from '../../../services/api'
import { auth } from '../../../services/auth'

const $ = s => document.querySelector(s)
const id = window.__LAP_ID__

let me=null, role=null, row=null

function badge(st){
  return st==='approved' ? 'success' :
         st==='rejected' ? 'danger'  :
         st==='verified' ? 'primary' :
         st==='wakadek_ok' ? 'warning' : 'secondary'
}

async function loadMe(){
  const { data } = await api.get('/me')
  me = data; role = data.role
}

async function loadDetail(){
  const { data } = await api.get(`/laporan-skpi/${id}`)
  row = data
  $('#vId').textContent = row.id
  $('#vNim').textContent = row.nim
  $('#vStatus').textContent = row.status
  $('#vStatus').className = `badge text-bg-${badge(row.status)}`
  $('#vPengesahan').textContent = (row.no_pengesahan||'-') + ' / ' + (row.tgl_pengesahan||'-')
  $('#vCatatan').textContent = row.catatan_verifikasi || '-'

  const fileArea = $('#vFileArea'); fileArea.innerHTML=''
  if (row.file_url) {
    const a = document.createElement('a')
    a.className='btn btn-outline-secondary btn-sm'
    a.href = row.file_url; a.target='_blank'
    a.textContent='Lihat File'
    fileArea.appendChild(a)

    const b = document.createElement('button')
    b.className='btn btn-outline-dark btn-sm'
    b.textContent='Generate Ulang'
    b.addEventListener('click', onRegenerate)
    fileArea.appendChild(b)
  }

  // tampilkan form pengesahan hanya utk AdminFakultas (+ masih perlu)
  if (['AdminFakultas','SuperAdmin'].includes(role) && row.status==='verified') {
    $('#formPengesahan').classList.remove('d-none')
    $('#noPengesahan').value = row.no_pengesahan || ''
    $('#tglPengesahan').value = row.tgl_pengesahan || ''
    $('#catPengesahan').value = row.catatan_verifikasi || ''
  } else {
    $('#formPengesahan').classList.add('d-none')
  }

  // render tombol aksi sesuai role + status
  const box = $('#boxActions'); box.innerHTML = ''
  if (role==='Kajur' && row.status==='submitted') {
    box.appendChild(btn('Setujui (Kajur)', 'success', onKajurApprove))
    box.appendChild(btn('Tolak (Kajur)', 'outline-danger', onKajurReject))
  }
  if (role==='Wakadek' && row.status==='verified' && row.no_pengesahan && row.tgl_pengesahan) {
    box.appendChild(btn('Setujui (Wakadek)', 'success', onWakadekApprove))
    box.appendChild(btn('Tolak (Wakadek)', 'outline-danger', onWakadekReject))
  }
  if (role==='Dekan' && row.status==='wakadek_ok') {
    box.appendChild(btn('Setujui (Dekan)', 'success', onDekanApprove))
    box.appendChild(btn('Tolak (Dekan)', 'outline-danger', onDekanReject))
  }

  $('#boxLoading').classList.add('d-none')
  $('#boxDetail').classList.remove('d-none')
}

function btn(label, style, handler){
  const b = document.createElement('button')
  b.className = `btn btn-${style}`
  b.textContent = label
  b.addEventListener('click', handler)
  return b
}

/* ========== Actions ========== */

async function onKajurApprove(){
  try{
    await api.post(`/laporan-skpi/${id}/verify`, { approve:true, note:null })
    await loadDetail()
    alert('Disetujui oleh Kajur.')
  }catch(err){ alert(err?.response?.data?.message || err.message) }
}
async function onKajurReject(){
  const note = prompt('Alasan penolakan (opsional)?') || null
  try{
    await api.post(`/laporan-skpi/${id}/verify`, { approve:false, note })
    await loadDetail()
  }catch(err){ alert(err?.response?.data?.message || err.message) }
}

$('#btnSimpanPengesahan')?.addEventListener('click', async ()=>{
  const no = $('#noPengesahan').value.trim()
  const tgl= $('#tglPengesahan').value.trim()
  const cat= $('#catPengesahan').value.trim() || null
  if (!no || !tgl) return alert('Isi nomor & tanggal pengesahan.')
  try{
    await api.post(`/laporan-skpi/${id}/pengesahan`, {
      no_pengesahan: no, tgl_pengesahan: tgl, catatan_verifikasi: cat
    })
    await loadDetail()
    alert('Pengesahan disimpan.')
  }catch(err){ alert(err?.response?.data?.message || err.message) }
})

async function onWakadekApprove(){
  try{
    await api.post(`/laporan-skpi/${id}/wakadek`, { approve:true })
    await loadDetail()
    alert('Disetujui Wakadek.')
  }catch(err){ alert(err?.response?.data?.message || err.message) }
}
async function onWakadekReject(){
  const note = prompt('Alasan penolakan (opsional)?') || null
  try{
    await api.post(`/laporan-skpi/${id}/wakadek`, { approve:false, note })
    await loadDetail()
  }catch(err){ alert(err?.response?.data?.message || err.message) }
}

async function onDekanApprove(){
  try{
    await api.post(`/laporan-skpi/${id}/dekan`, { approve:true })
    await loadDetail()
    alert('Disetujui Dekan & file dibuat.')
  }catch(err){ alert(err?.response?.data?.message || err.message) }
}
async function onDekanReject(){
  const note = prompt('Alasan penolakan (opsional)?') || null
  try{
    await api.post(`/laporan-skpi/${id}/dekan`, { approve:false, note })
    await loadDetail()
  }catch(err){ alert(err?.response?.data?.message || err.message) }
}

async function onRegenerate(){
  try{
    const { data } = await api.post(`/laporan-skpi/${id}/regenerate`)
    if (data.file_url) window.open(data.file_url,'_blank')
    await loadDetail()
  }catch(err){ alert(err?.response?.data?.message || err.message) }
}

/* ========== Boot ========== */
;(async function init(){
  try{
    await loadMe()
    await loadDetail()
  }catch(err){
    auth.clear()
    window.location.replace('/login')
  }
})()

import { api } from '../../../services/api'
import { auth } from '../../../services/auth'

const $ = s => document.querySelector(s)

let me=null, role=null

async function mustRole(){
  const { data } = await api.get('/me')
  me = data; role = data.role
  if (!['AdminJurusan','Kajur','SuperAdmin'].includes(role)) {
    alert('Hanya Admin Jurusan/Kajur yang boleh menambah.')
    window.location.replace('/admin/kp')
    throw new Error('forbidden')
  }
}

async function loadFakultas(){
  const { data } = await api.get('/fakultas?per_page=100')
  const rows = data.data || data
  const sel = $('#selFak')
  sel.innerHTML = `<option value="">— Pilih Fakultas —</option>`
  rows.forEach(f=>{
    const opt = document.createElement('option')
    opt.value = f.id
    opt.textContent = f.nama_fakultas || `Fakultas ${f.id}`
    sel.appendChild(opt)
  })
  if (me?.id_fakultas) {
    sel.value = String(me.id_fakultas)
    sel.dispatchEvent(new Event('change'))
  }
}

async function loadProdi(id_fak){
  $('#selProdi').disabled = true
  const { data } = await api.get('/prodi?per_page=200' + (id_fak ? ('&fakultas_id='+id_fak) : ''))
  const rows = data.data || data
  const sel = $('#selProdi')
  sel.innerHTML = `<option value="">— Pilih Prodi —</option>`
  rows.forEach(p=>{
    const opt = document.createElement('option')
    opt.value = p.id
    opt.textContent = p.nama_prodi || `Prodi ${p.id}`
    sel.appendChild(opt)
  })
  $('#selProdi').disabled = false

  if (me?.id_prodi) {
    sel.value = String(me.id_prodi)
    sel.dispatchEvent(new Event('change'))
  }
}

async function loadMhs(id_prodi){
  $('#selNim').disabled = true
  const { data } = await api.get('/mahasiswa?per_page=200&prodi_id='+encodeURIComponent(id_prodi))
  const rows = data.data || data
  const sel = $('#selNim')
  sel.innerHTML = `<option value="">— Pilih NIM —</option>`
  rows.forEach(m=>{
    const opt=document.createElement('option')
    opt.value = m.nim
    opt.textContent = `${m.nim} — ${m.nama_mahasiswa||'-'}`
    sel.appendChild(opt)
  })
  $('#selNim').disabled = false
}

$('#selFak')?.addEventListener('change', (e)=>{
  const idf = e.target.value
  $('#selProdi').innerHTML = `<option value="">— Pilih Prodi —</option>`
  $('#selNim').innerHTML   = `<option value="">— Pilih NIM —</option>`
  $('#selNim').disabled = true
  if (idf) loadProdi(idf)
})

$('#selProdi')?.addEventListener('change', (e)=>{
  const idp = e.target.value
  $('#selNim').innerHTML = `<option value="">— Pilih NIM —</option>`
  if (idp) loadMhs(idp)
})

$('#btnSubmit')?.addEventListener('click', async ()=>{
  const nim  = $('#selNim').value
  const nama = $('#inpNama').value.trim()
  const file = $('#inpFile').files?.[0] || null

  if(!nim || !nama) return alert('Lengkapi NIM dan Nama Kegiatan.')

  const fd = new FormData()
  fd.append('nim', nim)
  fd.append('nama_kegiatan', nama)
  if (file) fd.append('file', file)

  try{
    await api.post('/kp', fd, { headers:{ 'Content-Type':'multipart/form-data' } })
    alert('Data tersimpan')
    window.location.replace('/admin/kp')
  }catch(err){
    alert(err?.response?.data?.message || err.message)
  }
})

;(async function init(){
  try{
    await mustRole()
    await loadFakultas()
  }catch(e){}
})()

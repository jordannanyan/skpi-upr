import { api } from '../../../services/api'
import { auth } from '../../../services/auth'

const $ = s => document.querySelector(s)
const id = window.__KP_ID__

let me=null, role=null, row=null

async function loadMe(){
  const { data } = await api.get('/me')
  me = data; role = data.role
}

async function loadDetail(){
  const { data } = await api.get(`/kp/${id}`)
  row = data

  // lock nim: dropdown 1 opsi
  const sel = $('#selNim')
  sel.innerHTML = ''
  const opt = document.createElement('option')
  opt.value = row.nim
  opt.textContent = `${row.nim} — ${(row.mahasiswa?.nama_mahasiswa || '-')}`
  sel.appendChild(opt)
  sel.value = row.nim
  sel.disabled = true

  $('#inpNama').value = row.nama_kegiatan || ''
}

$('#btnUpdate')?.addEventListener('click', async ()=>{
  const nama = $('#inpNama').value.trim()
  const file = $('#inpFile').files?.[0] || null
  if(!nama) return alert('Isi nama kegiatan.')

  const fd = new FormData()
  fd.append('_method','PUT') // for match(['put','patch'])
  fd.append('nama_kegiatan', nama)
  if (file) fd.append('file', file)

  try{
    await api.post(`/kp/${id}`, fd, { headers:{ 'Content-Type':'multipart/form-data' } })
    alert('Perubahan disimpan')
    window.location.replace('/admin/kp')
  }catch(err){
    alert(err?.response?.data?.message || err.message)
  }
})

;(async function init(){
  try{
    await loadMe()
    await loadDetail()
  }catch(err){
    auth.clear()
    window.location.replace('/login')
  }
})()

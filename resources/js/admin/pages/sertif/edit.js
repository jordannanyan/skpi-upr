import { api } from '../../../services/api'
import { auth } from '../../../services/auth'

const $ = s => document.querySelector(s)
const id = window.__SF_ID__

let me=null, role=null, row=null

async function loadMe(){
  const { data } = await api.get('/me')
  me = data; role = data.role
  if (!['AdminJurusan','Kajur','SuperAdmin'].includes(role)) {
    alert('Hanya Admin Jurusan/Kajur yang boleh mengubah.')
    window.location.replace('/admin/sertifikasi')
    throw new Error('forbidden')
  }
}

async function loadDetail(){
  const { data } = await api.get(`/sertifikasi/${id}`)
  row = data

  // lock nim
  const sel = $('#selNim')
  sel.innerHTML = ''
  const opt = document.createElement('option')
  opt.value = row.nim
  opt.textContent = `${row.nim} — ${(row.mahasiswa?.nama_mahasiswa || row.nama_mhs || '-')}`
  sel.appendChild(opt)
  sel.value = row.nim
  sel.disabled = true

  // prefill
  $('#inpNama').value = row.nama_sertifikasi || ''

  // set kategori ke dropdown
  const selKategori = $('#selKategori')
  const current = (row.kategori_sertifikasi || '').trim()
  // jika value lama tidak ada di 4 opsi, tambahkan sementara agar tidak hilang
  if (current && ![...selKategori.options].some(o => o.value === current)) {
    const extra = document.createElement('option')
    extra.value = current
    extra.textContent = current + ' (lainnya)'
    selKategori.appendChild(extra)
  }
  selKategori.value = current || ''
}

$('#btnUpdate')?.addEventListener('click', async ()=>{
  const nama = $('#inpNama').value.trim()
  const kat  = $('#selKategori').value.trim()
  const file = $('#inpFile').files?.[0] || null
  if(!nama || !kat) return alert('Lengkapi Nama Sertifikasi dan Kategori.')

  const fd = new FormData()
  fd.append('_method','PUT')
  fd.append('nama_sertifikasi', nama)
  fd.append('kategori_sertifikasi', kat)
  if (file) fd.append('file', file)

  try{
    await api.post(`/sertifikasi/${id}`, fd, { headers:{ 'Content-Type':'multipart/form-data' } })
    alert('Perubahan disimpan')
    window.location.replace('/admin/sertifikasi')
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

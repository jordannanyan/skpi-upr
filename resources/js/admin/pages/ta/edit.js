import { api } from '../../../services/api'
import { auth } from '../../../services/auth'

const $ = s => document.querySelector(s)
const id = window.__TA_ID__
const bridge = document.getElementById('bridge')
const ADMIN_URL = bridge?.dataset?.adminUrl || '/admin'

let me=null, role=null, row=null

async function mustRole(){
  const { data } = await api.get('/me')
  me = data; role = data.role
  if (!['AdminJurusan','Kajur','SuperAdmin'].includes(role)) {
    alert('Tidak diizinkan.'); window.location.replace(`${ADMIN_URL}/ta`); throw new Error('forbidden')
  }
}

async function loadDetail(){
  const { data } = await api.get(`/ta/${id}`)
  row = data

  // prefill inputs
  $('#inpKategori').value = row.kategori || ''
  $('#inpJudul').value    = row.judul || ''

  // preselect nim: tambahkan option jika belum ada
  const selNim = $('#selNim')
  const opt = document.createElement('option')
  opt.value = row.nim; opt.textContent = row.nim
  selNim.appendChild(opt)
  selNim.value = row.nim
}

$('#btnUpdate')?.addEventListener('click', async ()=>{
  const nim = $('#selNim').value
  const kategori = $('#inpKategori').value.trim()
  const judul = $('#inpJudul').value.trim()
  if (!nim || !kategori || !judul) return alert('Lengkapi NIM, Kategori, dan Judul.')

  try{
    await api.put(`/ta/${id}`, { nim, kategori, judul })
    alert('Perubahan disimpan.')
    window.location.replace(`${ADMIN_URL}/ta`)
  }catch(err){
    alert(err?.response?.data?.message || err.message)
  }
})

;(async function init(){
  try{
    await mustRole()
    await loadDetail()
  }catch(err){
    auth.clear()
    window.location.replace('/login')
  }
})()

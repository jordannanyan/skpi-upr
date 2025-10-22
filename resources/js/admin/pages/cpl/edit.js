import { api } from '../../../services/api'
import { auth } from '../../../services/auth'

const $ = s => document.querySelector(s)
const kode = window.__CPL_KODE__

async function mustRole(){
  const { data } = await api.get('/me')
  if (!['SuperAdmin'].includes(data.role)) {
    alert('Hanya SuperAdmin yang boleh mengubah CPL.')
    window.location.replace('/admin/cpl')
    throw new Error('forbidden')
  }
}

async function loadDetail(){
  const { data } = await api.get(`/cpl/${encodeURIComponent(kode)}`)
  $('#kode').value = data.kode_cpl
  $('#kategori').value = data.kategori || ''
  $('#deskripsi').value = data.deskripsi || ''
}

$('#btnUpdate')?.addEventListener('click', async ()=>{
  const kategori = $('#kategori').value.trim()
  const deskripsi = $('#deskripsi').value.trim()
  if(!kategori || !deskripsi) return alert('Lengkapi kategori & deskripsi.')
  try{
    await api.put(`/cpl/${encodeURIComponent(kode)}`, { kategori, deskripsi })
    alert('Perubahan disimpan')
    window.location.replace('/admin/cpl')
  }catch(err){ alert(err?.response?.data?.message || err.message) }
})

;(async function init(){
  try{
    await mustRole()
    await loadDetail()
  }catch(err){ auth.clear(); window.location.replace('/login') }
})()

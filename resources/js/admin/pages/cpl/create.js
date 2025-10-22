import { api } from '../../../services/api'
import { auth } from '../../../services/auth'

const $ = s => document.querySelector(s)

async function mustRole(){
  const { data } = await api.get('/me')
  if (!['SuperAdmin'].includes(data.role)) {
    alert('Hanya SuperAdmin yang boleh menambah CPL.')
    window.location.replace('/admin/cpl')
    throw new Error('forbidden')
  }
}

$('#btnSimpan')?.addEventListener('click', async ()=>{
  const kode = $('#kode').value.trim()
  const kategori = $('#kategori').value.trim()
  const deskripsi = $('#deskripsi').value.trim()
  if(!kode || !kategori || !deskripsi) return alert('Lengkapi semua field.')

  try{
    await api.post('/cpl', { kode_cpl: kode, kategori, deskripsi })
    alert('CPL dibuat')
    window.location.replace('/admin/cpl')
  }catch(err){ alert(err?.response?.data?.message || err.message) }
})

;(async function init(){
  try{ await mustRole() }catch(e){}
})()

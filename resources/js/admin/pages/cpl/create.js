import { api } from '../../../services/api'
import { auth } from '../../../services/auth'

const $ = s => document.querySelector(s)

const bridge = document.getElementById('bridge')
const ADMIN_URL = bridge?.dataset?.adminUrl || '/admin'
const LOGIN_URL = bridge?.dataset?.loginUrl || '/login'

const isAuthError = (err) => {
  const st = err?.response?.status
  return st === 401 || st === 419
}

async function mustRole(){
  try{
    const { data } = await api.get('/me')
    if (!['SuperAdmin'].includes(data.role)) {
      alert('Hanya SuperAdmin yang boleh menambah CPL.')
      window.location.replace(`${ADMIN_URL}/cpl`)
      throw new Error('forbidden')
    }
  }catch(err){
    if (isAuthError(err)) {
      auth.clear(); window.location.replace(LOGIN_URL)
    } else {
      alert('Gagal memuat profil.'); window.location.replace(`${ADMIN_URL}/cpl`)
    }
    throw err
  }
}

async function loadMasters(){
  try{
    const [f,p] = await Promise.all([
      api.get('/fakultas?per_page=100'),
      api.get('/prodi?per_page=200')
    ])
    const faks = f.data?.data || f.data || []
    const pros = p.data?.data || p.data || []

    const selF = $('#selFak')
    const selP = $('#selProdi')

    // isi fakultas
    selF.innerHTML = `<option value="">— Pilih Fakultas —</option>`
    faks.forEach(x=>{
      const opt = document.createElement('option')
      opt.value = x.id
      opt.textContent = x.nama_fakultas || `Fakultas ${x.id}`
      selF.appendChild(opt)
    })

    // isi prodi (dengan data-id_fakultas)
    selP.innerHTML = `<option value="">— Pilih Prodi —</option>`
    pros.forEach(x=>{
      const opt = document.createElement('option')
      opt.value = x.id
      opt.textContent = x.nama_prodi || `Prodi ${x.id}`
      opt.dataset.fak = x.id_fakultas || ''
      selP.appendChild(opt)
    })

    // filter prodi by fakultas
    const filterProdiByFak = ()=>{
      const v = selF.value
      let hasVisible = false
      ;[...selP.options].forEach((o, i)=>{
        if (i===0) return
        const match = !v || (o.dataset.fak || '') === v
        o.hidden = !match
        if (match) hasVisible = true
      })
      selP.disabled = !hasVisible
      if (selP.selectedIndex > 0) {
        const cur = selP.selectedOptions[0]
        if (cur && (cur.dataset.fak||'') !== v) selP.value = ''
      }
    }
    selF.addEventListener('change', filterProdiByFak)
    filterProdiByFak()
  }catch(err){
    if (isAuthError(err)) {
      auth.clear(); return window.location.replace(LOGIN_URL)
    }
    console.error('Gagal memuat master:', err?.response?.data || err.message)
    $('#selFak').innerHTML = `<option value="">(gagal memuat fakultas)</option>`
    $('#selProdi').innerHTML = `<option value="">(gagal memuat prodi)</option>`
    $('#selProdi').disabled = true
  }
}

$('#btnSimpan')?.addEventListener('click', async ()=>{
  const kode      = $('#kode').value.trim()
  const kategori  = $('#kategori').value.trim()
  const deskripsi = $('#deskripsi').value.trim()
  const id_prodi  = $('#selProdi').value ? Number($('#selProdi').value) : null

  if(!kode || !kategori || !deskripsi){
    return alert('Lengkapi Kode, Kategori, dan Deskripsi.')
  }

  try{
    // backend CplStoreRequest mengizinkan id_prodi nullable|exists:ref_prodi,id
    await api.post('/cpl', {
      kode_cpl: kode,
      kategori,
      deskripsi,
      id_prodi, // bisa null
    })
    alert('CPL dibuat')
    window.location.replace(`${ADMIN_URL}/cpl`)
  }catch(err){
    const msg = err?.response?.data?.message || err.message
    alert(`Gagal menyimpan: ${msg}`)
  }
})

;(async function init(){
  try{
    await mustRole()
    await loadMasters()
  }catch(e){/* handled above */}
})()

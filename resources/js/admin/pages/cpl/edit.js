import { api } from '../../../services/api'
import { auth } from '../../../services/auth'

const $ = s => document.querySelector(s)
const kode = window.__CPL_KODE__

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
      alert('Hanya SuperAdmin yang boleh mengubah CPL.')
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

    selF.innerHTML = `<option value="">— Pilih Fakultas —</option>`
    faks.forEach(x=>{
      const opt = document.createElement('option')
      opt.value = x.id
      opt.textContent = x.nama_fakultas || `Fakultas ${x.id}`
      selF.appendChild(opt)
    })

    selP.innerHTML = `<option value="">— Pilih Prodi —</option>`
    pros.forEach(x=>{
      const opt = document.createElement('option')
      opt.value = x.id
      opt.textContent = x.nama_prodi || `Prodi ${x.id}`
      opt.dataset.fak = x.id_fakultas || ''
      selP.appendChild(opt)
    })

    const filterProdiByFak = ()=>{
      const v = selF.value
      let hasVisible = false
      ;[...selP.options].forEach((o,i)=>{
        if (i===0) return
        const match = !v || (o.dataset.fak || '') === v
        o.hidden = !match
        if (match) hasVisible = true
      })
      selP.disabled = !hasVisible
      // reset jika prodi terpilih bukan dari fak yang sama
      if (selP.selectedIndex > 0) {
        const cur = selP.selectedOptions[0]
        if (cur && (cur.dataset.fak||'') !== v) selP.value = ''
      }
    }
    selF.addEventListener('change', filterProdiByFak)

    // return helper utk preselect setelah detail dimuat
    return { filterProdiByFak }
  }catch(err){
    if (isAuthError(err)) {
      auth.clear(); window.location.replace(LOGIN_URL); throw err
    }
    console.error('Gagal memuat master:', err?.response?.data || err.message)
    $('#selFak').innerHTML = `<option value="">(gagal memuat fakultas)</option>`
    $('#selProdi').innerHTML = `<option value="">(gagal memuat prodi)</option>`
    $('#selProdi').disabled = true
    return { filterProdiByFak: ()=>{} }
  }
}

async function loadDetail(filterProdiByFak){
  const { data } = await api.get(`/cpl/${encodeURIComponent(kode)}`)
  // Server sebaiknya mengembalikan: { kode_cpl, kategori, deskripsi, id_prodi, prodi:{id,id_fakultas,...} }
  $('#kode').value = data.kode_cpl || ''
  $('#kategori').value = data.kategori || ''
  $('#deskripsi').value = data.deskripsi || ''

  const id_prodi   = data.id_prodi ?? data.prodi?.id ?? null
  const id_fak     = data.prodi?.id_fakultas ?? null

  // preselect fak & prodi bila tersedia
  if (id_fak) {
    $('#selFak').value = String(id_fak)
  }
  // filter prodi sesuai fakultas sebelum memilih prodi
  filterProdiByFak?.()

  if (id_prodi) {
    $('#selProdi').value = String(id_prodi)
    $('#selProdi').disabled = false
  }
}

$('#btnUpdate')?.addEventListener('click', async ()=>{
  const kategori  = $('#kategori').value.trim()
  const deskripsi = $('#deskripsi').value.trim()
  const id_prodi  = $('#selProdi').value ? Number($('#selProdi').value) : null

  if(!kategori || !deskripsi) return alert('Lengkapi kategori & deskripsi.')

  try{
    // CplUpdateRequest kita sudah mengizinkan: id_prodi sometimes|nullable|integer|exists
    await api.put(`/cpl/${encodeURIComponent(kode)}`, {
      kategori,
      deskripsi,
      id_prodi, // boleh null untuk melepas prodi
    })
    alert('Perubahan disimpan')
    window.location.replace(`${ADMIN_URL}/cpl`)
  }catch(err){
    const msg = err?.response?.data?.message || err.message
    alert(`Gagal menyimpan: ${msg}`)
  }
})

;(async function init(){
  try{
    await mustRole()
    const { filterProdiByFak } = await loadMasters()
    await loadDetail(filterProdiByFak)
  }catch(err){
    if (isAuthError(err)) {
      auth.clear(); window.location.replace(LOGIN_URL)
    }
  }
})()

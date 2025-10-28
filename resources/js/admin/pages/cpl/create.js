import { api } from '../../../services/api'
import { auth } from '../../../services/auth'

const $ = s => document.querySelector(s)

const bridge    = document.getElementById('bridge')
const ADMIN_URL = bridge?.dataset?.adminUrl || '/admin'
const LOGIN_URL = bridge?.dataset?.loginUrl || '/login'

const isAuthError = (err) => [401,419].includes(err?.response?.status)

let me = null
let role = null
let myProdiId = null

function hideMastersForProdiRole(){
  $('#rowMasters')?.classList.add('d-none')
  $('#selFak')?.closest('.col-md-3')?.classList.add('d-none')
  $('#selProdi')?.closest('.col-md-2')?.classList.add('d-none')
}

async function mustRole(){
  try{
    const { data } = await api.get('/me')
    me   = data
    role = data?.role
    myProdiId = data?.id_prodi ?? data?.prodi?.id ?? null

    // izinkan SuperAdmin & role prodi
    const allowed = ['SuperAdmin','AdminJurusan','Kajur']
    if (!allowed.includes(role)) {
      alert('Anda tidak berhak menambah CPL.')
      window.location.replace(`${ADMIN_URL}/cpl`)
      throw new Error('forbidden')
    }

    // untuk role prodi, sembunyikan Fak/Prodi dan pastikan punya id_prodi
    if ((role === 'AdminJurusan' || role === 'Kajur')) {
      if (!myProdiId) {
        alert('Prodi Anda tidak terdeteksi. Hubungi admin.')
        window.location.replace(`${ADMIN_URL}/cpl`)
        throw new Error('no-prodi')
      }
      hideMastersForProdiRole()
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
  // hanya SuperAdmin yang perlu master Fak/Prodi
  if (role !== 'SuperAdmin') return
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
      if (selP.selectedIndex > 0) {
        const cur = selP.selectedOptions[0]
        if (cur && (cur.dataset.fak||'') !== v) selP.value = ''
      }
    }
    selF.addEventListener('change', filterProdiByFak)
    filterProdiByFak()
  }catch(err){
    if (isAuthError(err)) { auth.clear(); return window.location.replace(LOGIN_URL) }
    console.error('Gagal memuat master:', err?.response?.data || err.message)
    $('#selFak').innerHTML   = `<option value="">(gagal memuat fakultas)</option>`
    $('#selProdi').innerHTML = `<option value="">(gagal memuat prodi)</option>`
    $('#selProdi').disabled  = true
  }
}

$('#btnSimpan')?.addEventListener('click', async ()=>{
  const kode      = $('#kode').value.trim()
  const kategori  = $('#kategori').value.trim()
  const deskripsi = $('#deskripsi').value.trim()

  if(!kode || !kategori || !deskripsi){
    return alert('Lengkapi Kode, Kategori, dan Deskripsi.')
  }

  // Tentukan id_prodi:
  // - SuperAdmin: ambil dari dropdown (boleh null)
  // - AdminJurusan/Kajur: paksa ke prodi user
  let id_prodi = null
  if (role === 'SuperAdmin') {
    const sp = $('#selProdi')?.value || ''
    id_prodi = sp ? Number(sp) : null
  } else {
    id_prodi = myProdiId
  }

  try{
    await api.post('/cpl', {
      kode_cpl: kode,
      kategori,
      deskripsi,
      id_prodi, // dipaksa untuk role prodi
    })
    alert('CPL dibuat')
    window.location.replace(`${ADMIN_URL}/cpl`)
  }catch(err){
    const msg = err?.response?.data?.errors
      ? Object.values(err.response.data.errors).flat().join('\n')
      : (err?.response?.data?.message || err.message)
    alert(`Gagal menyimpan:\n${msg}`)
  }
})

;(async function init(){
  try{
    await mustRole()
    await loadMasters()
  }catch(_){ /* sudah ditangani */ }
})()

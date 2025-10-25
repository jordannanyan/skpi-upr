// resources/js/admin/pages/ta/create.js
import { api } from '../../../services/api'
import { auth } from '../../../services/auth'

const $ = s => document.querySelector(s)
const bridge = document.getElementById('bridge')
const ADMIN_URL = bridge?.dataset?.adminUrl || '/admin'

let me = null, role = null

const KAT_ALLOWED = ['skripsi','tesis','disertasi']
const normKat = v => String(v || '').trim().toLowerCase()

function setBusy(b) {
  const btn = $('#btnSubmit')
  btn.disabled = b
  if (b) {
    btn.dataset._old = btn.textContent
    btn.textContent = 'Menyimpan...'
  } else {
    btn.textContent = btn.dataset._old || 'Simpan'
  }
}

async function mustRole(){
  const { data } = await api.get('/me')
  me = data; role = data.role
  if (!['AdminJurusan','Kajur','SuperAdmin'].includes(role)) {
    alert('Hanya Admin Jurusan/Kajur yang boleh menambah TA.')
    window.location.replace(`${ADMIN_URL}/ta`)
    throw new Error('forbidden')
  }
}

async function loadFakultas(){
  const { data } = await api.get('/fakultas?per_page=100')
  const rows = data.data || data
  const sel = $('#selFak')
  sel.innerHTML = `<option value="">— Pilih Fakultas —</option>`
  rows.forEach(f=>{
    const opt=document.createElement('option')
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
    const opt=document.createElement('option')
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
  const nim = $('#selNim').value
  const kategori = normKat($('#selKategori').value)
  const judul = ($('#inpJudul').value || '').trim()

  if (!nim) return alert('Pilih NIM.')
  if (!KAT_ALLOWED.includes(kategori)) return alert('Pilih kategori: Skripsi, Tesis, atau Disertasi.')
  if (!judul) return alert('Judul tidak boleh kosong.')

  setBusy(true)
  try{
    await api.post('/ta', { nim, kategori, judul })
    alert('Tersimpan.')
    window.location.replace(`${ADMIN_URL}/ta`)
  }catch(err){
    alert(err?.response?.data?.message || err.message)
  }finally{
    setBusy(false)
  }
})

;(async function init(){
  try{
    await mustRole()
    await loadFakultas()
  }catch(e){
    auth.clear()
    window.location.replace(bridge?.dataset?.loginUrl || '/login')
  }
})()

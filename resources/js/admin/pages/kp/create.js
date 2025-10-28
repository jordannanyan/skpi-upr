// resources/js/admin/pages/kp/create.js
import { api } from '../../../services/api'
import { auth } from '../../../services/auth'

const $ = s => document.querySelector(s)
const bridge   = document.getElementById('bridge')
const ADMIN_URL= bridge?.dataset?.adminUrl || '/admin'
const LOGIN_URL= bridge?.dataset?.loginUrl || '/login'

let me=null, role=null
let myNim=null, myNama=null, myProdi=null, myFak=null
let submitting=false

const isLikelyNim = s => /^[0-9]{6,20}$/.test(String(s||'').trim())

function setBusy(b){
  const btn = $('#btnSubmit')
  if(!btn) return
  submitting = b
  btn.disabled = b
  if(b){
    btn.dataset._old = btn.textContent
    btn.textContent = 'Menyimpan...'
  }else{
    btn.textContent = btn.dataset._old || 'Simpan'
  }
}

async function loadMe(){
  const { data } = await api.get('/me')
  me = data
  role = data?.role || ''

  // Sumber NIM berlapis
  const nimFromMe = me?.nim ?? me?.mahasiswa?.nim ?? (isLikelyNim(me?.username) ? me.username : '')
  const nimFromLS = localStorage.getItem('auth_nim') || ''
  myNim = nimFromMe || nimFromLS || null
  if (myNim && isLikelyNim(myNim)) localStorage.setItem('auth_nim', myNim)

  myNama  = me?.nama_mahasiswa || me?.mahasiswa?.nama_mahasiswa || me?.name || null
  myProdi = me?.prodi?.nama_prodi || me?.mahasiswa?.prodi?.nama_prodi || null
  myFak   = me?.fakultas?.nama_fakultas || me?.mahasiswa?.prodi?.fakultas?.nama_fakultas || null
}

async function ensureRole(){
  await loadMe()
  // Mahasiswa boleh create KP miliknya
  if (role === 'Mahasiswa') return
  // Non Mahasiswa: izin seperti TA
  if (!['AdminJurusan','Kajur','SuperAdmin'].includes(role)) {
    alert('Hanya Admin Jurusan/Kajur/SuperAdmin atau Mahasiswa (untuk miliknya) yang boleh menambah KP.')
    window.location.replace(`${ADMIN_URL}/kp`)
    throw new Error('forbidden')
  }
}

/* ===== Masters (non-Mahasiswa) ===== */
async function loadFakultas(){
  const sel = $('#selFak')
  if (!sel) return
  const { data } = await api.get('/fakultas?per_page=100')
  const rows = data.data || data
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
  const sel = $('#selProdi')
  if (!sel) return
  sel.disabled = true
  const { data } = await api.get('/prodi?per_page=200' + (id_fak ? ('&fakultas_id='+id_fak) : ''))
  const rows = data.data || data
  sel.innerHTML = `<option value="">— Pilih Prodi —</option>`
  rows.forEach(p=>{
    const opt = document.createElement('option')
    opt.value = p.id
    opt.textContent = p.nama_prodi || `Prodi ${p.id}`
    sel.appendChild(opt)
  })
  sel.disabled = false

  if (me?.id_prodi) {
    sel.value = String(me.id_prodi)
    sel.dispatchEvent(new Event('change'))
  }
}

async function loadMhs(id_prodi){
  const sel = $('#selNim')
  if (!sel) return
  sel.disabled = true
  const { data } = await api.get('/mahasiswa?per_page=200&prodi_id='+encodeURIComponent(id_prodi))
  const rows = data.data || data
  sel.innerHTML = `<option value="">— Pilih NIM —</option>`
  rows.forEach(m=>{
    const opt = document.createElement('option')
    opt.value = m.nim
    opt.textContent = `${m.nim} — ${m.nama_mahasiswa || '-'}`
    sel.appendChild(opt)
  })
  sel.disabled = false
}

/* ===== Wiring masters ===== */
$('#selFak')?.addEventListener('change', (e)=>{
  const idf = e.target.value
  const selPro = $('#selProdi')
  const selNim = $('#selNim')
  if (selPro) selPro.innerHTML = `<option value="">— Pilih Prodi —</option>`
  if (selNim) {
    selNim.innerHTML = `<option value="">— Pilih NIM —</option>`
    selNim.disabled = true
  }
  if (idf) loadProdi(idf)
})

$('#selProdi')?.addEventListener('change', (e)=>{
  const idp = e.target.value
  const selNim = $('#selNim')
  if (selNim) selNim.innerHTML = `<option value="">— Pilih NIM —</option>`
  if (idp) loadMhs(idp)
})

/* ===== Submit ===== */
async function handleSubmit(){
  if (submitting) return
  const nama = ($('#inpNama')?.value || '').trim()
  const file = $('#inpFile')?.files?.[0] || null

  if (!nama) return alert('Nama kegiatan tidak boleh kosong.')

  // Tentukan NIM yang akan dikirim
  let nimToSend = null
  if (role === 'Mahasiswa') {
    nimToSend = myNim || localStorage.getItem('auth_nim') || ''
    if (!isLikelyNim(nimToSend)) {
      return alert('NIM Anda tidak terdeteksi. Silakan login ulang via Login Mahasiswa.')
    }
  } else {
    const nimSel = $('#selNim')?.value || ''
    if (!isLikelyNim(nimSel)) return alert('Pilih NIM.')
    nimToSend = nimSel
  }

  // Payload: selalu kirim nim + nama_kegiatan, file opsional
  const fd = new FormData()
  fd.append('nim', nimToSend)
  fd.append('nama_kegiatan', nama)
  if (file) fd.append('file', file)

  setBusy(true)
  try{
    await api.post('/kp', fd, { headers: { 'Content-Type':'multipart/form-data' } })
    alert('Tersimpan.')
    window.location.replace(`${ADMIN_URL}/kp`)
  }catch(err){
    alert(err?.response?.data?.message || err.message)
  }finally{
    setBusy(false)
  }
}

$('#btnSubmit')?.addEventListener('click', handleSubmit)
$('#inpNama')?.addEventListener('keydown', e => { if (e.key === 'Enter') handleSubmit() })

/* ===== Init ===== */
;(async function init(){
  try{
    await ensureRole()

    if (role === 'Mahasiswa') {
      // Sembunyikan area master, tampilkan kartu identitas
      document.querySelectorAll('.block-masters').forEach(el => el.classList.add('d-none'))
      const box = $('#mhsInfoBox')
      if (box) {
        box.classList.remove('d-none')
        $('#mhsInfoNim')   && ($('#mhsInfoNim').textContent   = myNim || localStorage.getItem('auth_nim') || '-')
        $('#mhsInfoNama')  && ($('#mhsInfoNama').textContent  = myNama || '-')
        $('#mhsInfoProdi') && ($('#mhsInfoProdi').textContent = myProdi || '-')
        $('#mhsInfoFak')   && ($('#mhsInfoFak').textContent   = myFak || '-')
      }
    } else {
      await loadFakultas()
    }
  }catch(e){
    auth.clear?.()
    window.location.replace(LOGIN_URL)
  }
})()

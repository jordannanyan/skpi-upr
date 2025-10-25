// resources/js/admin/pages/ta/edit.js
import { api } from '../../../services/api'
import { auth } from '../../../services/auth'

const $ = s => document.querySelector(s)
const id = window.__TA_ID__
const bridge = document.getElementById('bridge')
const ADMIN_URL = bridge?.dataset?.adminUrl || '/admin'

let me = null, role = null, row = null

const KAT_ALLOWED = ['skripsi', 'tesis', 'disertasi']
const normKat = v => String(v || '').trim().toLowerCase()

function setBusy(b) {
  const btn = $('#btnUpdate')
  if (!btn) return
  btn.disabled = b
  if (b) {
    btn.dataset._old = btn.textContent
    btn.textContent = 'Menyimpan…'
  } else {
    btn.textContent = btn.dataset._old || 'Simpan Perubahan'
  }
}

async function mustRole() {
  const { data } = await api.get('/me')
  me = data; role = data.role
  if (!['AdminJurusan','Kajur','SuperAdmin'].includes(role)) {
    alert('Tidak diizinkan.')
    window.location.replace(`${ADMIN_URL}/ta`)
    throw new Error('forbidden')
  }
}

async function loadDetail() {
  const { data } = await api.get(`/ta/${id}`)
  row = data

  // Prefill NIM (lock 1 opsi) + tampilkan nama jika tersedia
  const selNim = $('#selNim')
  selNim.innerHTML = ''
  const nama = row?.mahasiswa?.nama_mahasiswa || row?.mhs?.nama_mahasiswa || ''
  const opt = document.createElement('option')
  opt.value = row.nim
  opt.textContent = nama ? `${row.nim} — ${nama}` : row.nim
  selNim.appendChild(opt)
  selNim.value = row.nim
  selNim.disabled = true

  // Prefill kategori (dropdown)
  const kat = normKat(row.kategori)
  const selKategori = $('#selKategori')
  selKategori.value = KAT_ALLOWED.includes(kat) ? kat : ''

  // Prefill judul
  $('#inpJudul').value = row.judul || ''
}

async function doUpdate() {
  const nim = $('#selNim').value
  const kategori = normKat($('#selKategori').value)
  const judul = ($('#inpJudul').value || '').trim()

  if (!nim) return alert('NIM tidak valid.')
  if (!KAT_ALLOWED.includes(kategori)) return alert('Pilih kategori: Skripsi, Tesis, atau Disertasi.')
  if (!judul) return alert('Judul tidak boleh kosong.')

  setBusy(true)
  try {
    await api.put(`/ta/${id}`, { nim, kategori, judul })
    alert('Perubahan disimpan.')
    window.location.replace(`${ADMIN_URL}/ta`)
  } catch (err) {
    alert(err?.response?.data?.message || err.message)
  } finally {
    setBusy(false)
  }
}

$('#btnUpdate')?.addEventListener('click', doUpdate)
// submit via Enter di field judul
$('#inpJudul')?.addEventListener('keydown', e => { if (e.key === 'Enter') doUpdate() })

;(async function init(){
  try{
    await mustRole()
    await loadDetail()
  }catch(err){
    auth.clear()
    window.location.replace(bridge?.dataset?.loginUrl || '/login')
  }
})()

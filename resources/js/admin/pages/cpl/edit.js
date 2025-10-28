// resources/js/admin/pages/cpl/edit.js
import { api } from '../../../services/api'
import { auth } from '../../../services/auth'

const $ = s => document.querySelector(s)
const kode = window.__CPL_KODE__

const bridge    = document.getElementById('bridge')
const ADMIN_URL = bridge?.dataset?.adminUrl || '/admin'
const LOGIN_URL = bridge?.dataset?.loginUrl || '/login'

const isAuthError = (err) => [401,419].includes(err?.response?.status)

let me = null
let role = null
let myProdiId = null

function hideMastersForProdiRole() {
  // Sembunyikan blok Fak/Prodi untuk role prodi
  $('#rowMasters')?.classList.add('d-none')
  $('#selFak')?.closest('.col-md-4')?.classList.add('d-none')
  $('#selProdi')?.closest('.col-md-4')?.classList.add('d-none')
}

async function loadMeOrFail() {
  try {
    const { data } = await api.get('/me')
    me = data
    role = data?.role
    myProdiId = data?.id_prodi ?? data?.prodi?.id ?? null
  } catch (err) {
    if (isAuthError(err)) {
      auth.clear()
      window.location.replace(LOGIN_URL)
    } else {
      alert('Gagal memuat profil.')
      window.location.replace(`${ADMIN_URL}/cpl`)
    }
    throw err
  }
}

/** Role rule:
 *  - SuperAdmin → bebas edit, boleh pindah prodi
 *  - AdminJurusan/Kajur → hanya boleh edit CPL prodi sendiri, Fak/Prodi disembunyikan
 */
async function mustRole() {
  await loadMeOrFail()
  const allowed = ['SuperAdmin', 'AdminJurusan', 'Kajur']
  if (!allowed.includes(role)) {
    alert('Anda tidak berhak mengubah CPL.')
    window.location.replace(`${ADMIN_URL}/cpl`)
    throw new Error('forbidden')
  }
  if ((role === 'AdminJurusan' || role === 'Kajur')) {
    if (!myProdiId) {
      alert('Prodi Anda tidak terdeteksi. Hubungi admin.')
      window.location.replace(`${ADMIN_URL}/cpl`)
      throw new Error('no-prodi')
    }
    hideMastersForProdiRole()
  }
}

async function loadMastersForSuperAdmin() {
  if (role !== 'SuperAdmin') return { filterProdiByFak: ()=>{} }
  try {
    const [f, p] = await Promise.all([
      api.get('/fakultas?per_page=100'),
      api.get('/prodi?per_page=200'),
    ])
    const faks = f.data?.data || f.data || []
    const pros = p.data?.data || p.data || []

    const selF = $('#selFak')
    const selP = $('#selProdi')

    if (selF) {
      selF.innerHTML = `<option value="">— Pilih Fakultas —</option>`
      faks.forEach(x => {
        const opt = document.createElement('option')
        opt.value = x.id
        opt.textContent = x.nama_fakultas || `Fakultas ${x.id}`
        selF.appendChild(opt)
      })
    }

    if (selP) {
      selP.innerHTML = `<option value="">— Pilih Prodi —</option>`
      pros.forEach(x => {
        const opt = document.createElement('option')
        opt.value = x.id
        opt.textContent = x.nama_prodi || `Prodi ${x.id}`
        opt.dataset.fak = x.id_fakultas || ''
        selP.appendChild(opt)
      })
    }

    const filterProdiByFak = () => {
      if (!selF || !selP) return
      const v = selF.value
      let hasVisible = false
      ;[...selP.options].forEach((o, i) => {
        if (i === 0) return
        const match = !v || (o.dataset.fak || '') === v
        o.hidden = !match
        if (match) hasVisible = true
      })
      selP.disabled = !hasVisible
      if (selP.selectedIndex > 0) {
        const cur = selP.selectedOptions[0]
        if (cur && (cur.dataset.fak || '') !== v) selP.value = ''
      }
    }

    selF?.addEventListener('change', filterProdiByFak)
    return { filterProdiByFak }
  } catch (err) {
    if (isAuthError(err)) {
      auth.clear(); window.location.replace(LOGIN_URL); throw err
    }
    console.error('Gagal memuat master:', err?.response?.data || err.message)
    $('#selFak') && ($('#selFak').innerHTML = `<option value="">(gagal memuat fakultas)</option>`)
    $('#selProdi') && ($('#selProdi').innerHTML = `<option value="">(gagal memuat prodi)</option>`)
    $('#selProdi') && ($('#selProdi').disabled = true)
    return { filterProdiByFak: ()=>{} }
  }
}

async function loadDetail(filterProdiByFak) {
  const { data } = await api.get(`/cpl/${encodeURIComponent(kode)}`)
  // server: { kode_cpl, kategori, deskripsi, id_prodi, prodi:{id, id_fakultas, ...} }
  $('#kode').value      = data.kode_cpl || ''
  $('#kategori').value  = data.kategori || ''
  $('#deskripsi').value = data.deskripsi || ''

  const id_prodi = data.id_prodi ?? data.prodi?.id ?? null
  const id_fak   = data.prodi?.id_fakultas ?? null

  // Validasi scope untuk AdminJurusan/Kajur
  if ((role === 'AdminJurusan' || role === 'Kajur')) {
    if (!id_prodi || Number(id_prodi) !== Number(myProdiId)) {
      alert('Anda tidak berhak mengubah CPL di luar prodi Anda.')
      window.location.replace(`${ADMIN_URL}/cpl`)
      throw new Error('out-of-scope')
    }
  }

  // Preselect Fak/Prodi untuk SuperAdmin saja
  if (role === 'SuperAdmin') {
    if (id_fak) $('#selFak').value = String(id_fak)
    filterProdiByFak?.()
    if (id_prodi) {
      $('#selProdi').value = String(id_prodi)
      $('#selProdi').disabled = false
    }
  }
}

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

$('#btnUpdate')?.addEventListener('click', async () => {
  const kategori  = $('#kategori')?.value?.trim() ?? ''
  const deskripsi = $('#deskripsi')?.value?.trim() ?? ''

  // Tentukan id_prodi yang dikirim
  let id_prodi = null
  if (role === 'SuperAdmin') {
    const sp = $('#selProdi')?.value || ''
    id_prodi = sp ? Number(sp) : null  // boleh null melepas prodi
  } else {
    id_prodi = myProdiId               // dipaksa ke prodi user
  }

  if (!kategori || !deskripsi) {
    return alert('Lengkapi kategori & deskripsi.')
  }

  setBusy(true)
  try {
    await api.put(`/cpl/${encodeURIComponent(kode)}`, {
      kategori,
      deskripsi,
      id_prodi,
    })
    alert('Perubahan disimpan.')
    window.location.replace(`${ADMIN_URL}/cpl`)
  } catch (err) {
    const msg =
      err?.response?.data?.errors
        ? Object.values(err.response.data.errors).flat().join('\n')
        : (err?.response?.data?.message || err.message)
    alert(`Gagal menyimpan:\n${msg}`)
  } finally {
    setBusy(false)
  }
})

// init
;(async function init() {
  try {
    await mustRole()
    const { filterProdiByFak } = await loadMastersForSuperAdmin()
    await loadDetail(filterProdiByFak)
  } catch (_) {
    // sudah ditangani di tempat lain
  }
})()

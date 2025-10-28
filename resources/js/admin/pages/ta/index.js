import { api } from '../../../services/api'
import { auth } from '../../../services/auth'

// util
const $ = s => document.querySelector(s)
const escapeHtml = s => String(s ?? '').replace(/[&<>"']/g, m => ({
  '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
}[m]))
const isAuthError = (err) => {
  const st = err?.response?.status
  return st === 401 || st === 419
}
const showTableMessage = (msg, cols) => {
  const body = $('#taBody')
  body.innerHTML = `<tr><td colspan="${cols}" class="text-center text-muted p-4">${escapeHtml(msg)}</td></tr>`
}

const getNamaMhs = (r) => r?.nama_mhs ?? r?.mhs?.nama_mahasiswa ?? r?.mahasiswa?.nama_mahasiswa ?? '-'
const getProdi   = (r) => r?.nama_prodi ?? r?.prodi?.nama_prodi ?? r?.mhs?.prodi?.nama_prodi ?? r?.mahasiswa?.prodi?.nama_prodi ?? '-'
const getFak     = (r) => r?.nama_fakultas ?? r?.prodi?.fakultas?.nama_fakultas ?? r?.mhs?.prodi?.fakultas?.nama_fakultas ?? r?.mahasiswa?.prodi?.fakultas?.nama_fakultas ?? '-'

const COLS = (() => document.querySelectorAll('table thead th').length || 8)()

let me = null
let role = 'AdminJurusan'
let myNim = null

const bridge = document.getElementById('bridge')
const ADMIN_URL = bridge?.dataset?.adminUrl || '/admin'
const LOGIN_URL = bridge?.dataset?.loginUrl || '/login'

const body    = $('#taBody')
const btnGoCreate = $('#btnGoCreate')

function hideFilterCardForMahasiswa() {
  document.querySelector('.card.border-0.shadow-sm.mb-3')?.classList.add('d-none')
}

function roleDefaultFilter(base) {
  if (role === 'Kajur'         && me?.id_prodi)    base += '&prodi_id=' + encodeURIComponent(me.id_prodi)
  if (role === 'AdminJurusan'  && me?.id_prodi)    base += '&prodi_id=' + encodeURIComponent(me.id_prodi)
  if (role === 'AdminFakultas' && me?.id_fakultas) base += '&fakultas_id=' + encodeURIComponent(me.id_fakultas)
  if (role === 'Wakadek'       && me?.id_fakultas) base += '&fakultas_id=' + encodeURIComponent(me.id_fakultas)
  if (role === 'Dekan'         && me?.id_fakultas) base += '&fakultas_id=' + encodeURIComponent(me.id_fakultas)
  return base
}

async function loadMe() {
  const { data } = await api.get('/me')
  me   = data
  role = data.role
  myNim = data?.nim || data?.username || null

  // Mahasiswa: sembunyikan filter, tapi TAMPILKAN tombol Tambah TA agar bisa CRUD dirinya
  if (role === 'Mahasiswa') {
    hideFilterCardForMahasiswa()
    btnGoCreate?.classList.remove('d-none')
  } else {
    // non mahasiswa: tetap seperti biasa (boleh tambah bila role tertentu)
    const canCreate = ['AdminJurusan', 'Kajur', 'SuperAdmin'].includes(role)
    if (!canCreate) btnGoCreate?.classList.add('d-none')
  }
}

async function loadMasters() {
  if (role === 'Mahasiswa') return
  try {
    const [f, p] = await Promise.all([
      api.get('/fakultas?per_page=100'),
      api.get('/prodi?per_page=200'),
    ])
    const selFak = document.getElementById('taFak')
    const selPro = document.getElementById('taProdi')
    const faks = f.data.data || f.data
    const pros = p.data.data || p.data

    selFak.innerHTML = `<option value="">— Semua —</option>`
    faks.forEach(x => {
      const opt = document.createElement('option')
      opt.value = x.id
      opt.textContent = x.nama_fakultas || `Fakultas ${x.id}`
      selFak.appendChild(opt)
    })

    selPro.innerHTML = `<option value="">— Semua —</option>`
    pros.forEach(x => {
      const opt = document.createElement('option')
      opt.value = x.id
      opt.textContent = x.nama_prodi || `Prodi ${x.id}`
      opt.dataset.fak = x.id_fakultas || ''
      selPro.appendChild(opt)
    })

    const filterProdiByFak = () => {
      const v = selFak.value
      ;[...selPro.options].forEach((o, i) => {
        if (i === 0) return
        o.hidden = (v && (o.dataset.fak || '') !== v)
      })
      if (v) {
        const cur = selPro.selectedOptions[0]
        if (cur && (cur.dataset.fak || '') !== v) selPro.value = ''
      }
    }
    selFak.addEventListener('change', filterProdiByFak)
    filterProdiByFak()

    if (['AdminJurusan', 'Kajur'].includes(role) && me?.id_prodi) {
      selPro.value = String(me.id_prodi)
      const optPro = [...selPro.options].find(o => o.value === String(me.id_prodi))
      if (optPro?.dataset?.fak) {
        selFak.value = optPro.dataset.fak
        filterProdiByFak()
      }
    }
    if (['AdminFakultas', 'Wakadek', 'Dekan'].includes(role) && me?.id_fakultas) {
      selFak.value = String(me.id_fakultas)
      filterProdiByFak()
    }
  } catch (err) {
    if (isAuthError(err)) throw err
    alert(err?.response?.data?.message || err.message || 'Gagal memuat master data')
  }
}

async function loadTa(pageWant = 1) {
  const COLS = document.querySelectorAll('table thead th').length || 8
  showTableMessage('Memuat…', COLS)

  try {
    let url = `/ta?per_page=50&page=${pageWant}`

    if (role === 'Mahasiswa') {
      if (!myNim) { showTableMessage('Tidak dapat menentukan NIM Anda.', COLS); return }
      url += `&nim=${encodeURIComponent(myNim)}`
    } else {
      const kw   = (document.getElementById('taKw')?.value || '').trim()
      const nim  = (document.getElementById('taNim')?.value || '').trim()
      const fkid = document.getElementById('taFak')?.value || ''
      const prid = document.getElementById('taProdi')?.value || ''
      if (kw)   url += `&q=${encodeURIComponent(kw)}`
      if (nim)  url += `&nim=${encodeURIComponent(nim)}`
      if (fkid) url += `&fakultas_id=${encodeURIComponent(fkid)}`
      if (prid) url += `&prodi_id=${encodeURIComponent(prid)}`
      url = roleDefaultFilter(url)
    }

    const { data } = await api.get(url)
    const rows = data.data || data
    if (!rows.length) { showTableMessage('Tidak ada data', COLS); return }

    body.innerHTML = rows.map(r => {
      const nama     = escapeHtml(getNamaMhs(r))
      const prodi    = escapeHtml(getProdi(r))
      const fak      = escapeHtml(getFak(r))
      const kategori = escapeHtml(r.kategori ?? r.kategori_ta ?? '-')
      const judul    = escapeHtml(r.judul ?? '-')
      const ownRow   = String(r.nim || '') === String(myNim || '')

      let actionHtml = ''
      if (role === 'Mahasiswa') {
        // Mahasiswa: hanya boleh HAPUS baris miliknya sendiri
        actionHtml = ownRow ? `
          <button class="btn btn-sm btn-outline-danger d-inline-flex align-items-center justify-content-center"
                  data-act="del" data-id="${r.id}" title="Hapus TA" aria-label="Hapus">
            <i class="bi bi-trash"></i>
          </button>
        ` : '—'
      } else {
        // Admin/dosen: tetap seperti sebelumnya
        actionHtml = `
          <a class="btn btn-sm btn-outline-primary d-inline-flex align-items-center justify-content-center"
             href="${ADMIN_URL}/ta/${r.id}/edit" title="Edit TA" aria-label="Edit">
            <i class="bi bi-pencil-square"></i>
          </a>
          ${ (role === 'SuperAdmin') ? `
          <button class="btn btn-sm btn-outline-danger d-inline-flex align-items-center justify-content-center"
                  data-act="del" data-id="${r.id}" title="Hapus TA" aria-label="Hapus">
            <i class="bi bi-trash"></i>
          </button>` : '' }
        `
      }


      return `
        <tr>
          <td>${r.id}</td>
          <td><code>${escapeHtml(r.nim ?? '-')}</code></td>
          <td>${nama}</td>
          <td>${prodi}</td>
          <td>${fak}</td>
          <td>${kategori}</td>
          <td>${judul}</td>
          <td class="d-flex flex-wrap gap-2">${actionHtml}</td>
        </tr>
      `
    }).join('')

  } catch (err) {
    if (isAuthError(err)) {
      auth.clear(); window.location.replace(LOGIN_URL); return
    }
    const st = err?.response?.status
    showTableMessage(st === 403 ? 'Anda tidak memiliki akses.' : 'Gagal memuat data.', COLS)
  }
}

// events
document.getElementById('taCari')?.addEventListener('click', () => loadTa(1))
document.getElementById('taKw')  ?.addEventListener('keydown', (e) => { if (e.key === 'Enter') loadTa(1) })
document.getElementById('taNim') ?.addEventListener('keydown', (e) => { if (e.key === 'Enter') loadTa(1) })

// delete (Mahasiswa hanya boleh hapus miliknya, dicek lagi di backend)
document.getElementById('taBody')?.addEventListener('click', async (e) => {
  const btn = e.target.closest('button[data-act="del"]')
  if (!btn) return
  const id = btn.dataset.id
  if (!confirm('Hapus Tugas Akhir ini?')) return
  try {
    await api.delete(`/ta/${id}`)
    await loadTa()
  } catch (err) {
    if (isAuthError(err)) { auth.clear(); window.location.replace(LOGIN_URL); return }
    alert(err?.response?.data?.message || err.message)
  }
})

// init
;(async function init() {
  try {
    await loadMe()
    await loadMasters()
    await loadTa(1)
  } catch (err) {
    if (isAuthError(err)) {
      auth.clear(); window.location.replace(LOGIN_URL)
    } else {
      showTableMessage('Terjadi kesalahan saat inisialisasi halaman.', COLS)
      console.error(err)
    }
  }
})()

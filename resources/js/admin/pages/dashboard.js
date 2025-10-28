// resources/js/admin/pages/dashboard.js
import { api } from '../../services/api'

const $ = (s) => document.querySelector(s)
const isAuthError = (err) => [401, 419].includes(err?.response?.status)
const isLikelyNim = (v) => /^[0-9]{6,20}$/.test(String(v || '').trim())

/* ========== ADMIN/STAF DASHBOARD ========== */
async function loadAdminStats() {
  try {
    const [m, p, f] = await Promise.all([
      api.get('/mahasiswa?per_page=1'),
      api.get('/prodi?per_page=1'),
      api.get('/fakultas?per_page=1'),
    ])
    const getTotal = (res) =>
      res?.data?.total ??
      res?.data?.meta?.total ??
      res?.data?.data?.length ?? '—'

    $('#statMhs').textContent = getTotal(m)
    $('#statProdi').textContent = getTotal(p)
    $('#statFak').textContent = getTotal(f)
  } catch { }
}

/** Sinkronisasi - hanya untuk role tertentu */
const ALLOWED_ROLES = ['SuperAdmin', 'AdminFakultas', 'AdminJurusan']
function showSyncCard() { $('#syncCard')?.classList.remove('d-none') }
function setSyncBusy(busy) {
  const btn = $('#btnSync'), spn = btn?.querySelector('.spinner-border'), lbl = btn?.querySelector('.sync-label')
  if (!btn || !spn || !lbl) return
  btn.disabled = busy; spn.classList.toggle('d-none', !busy); lbl.textContent = busy ? 'Menyinkronkan…' : 'Sinkronkan'
}
function setSyncStatus(text, ok = true) {
  const el = $('#syncStatus'); if (!el) return
  el.textContent = text
  el.classList.remove('text-muted', 'text-success', 'text-danger')
  el.classList.add(ok ? 'text-success' : 'text-danger')
}
function setSyncTime(ts) { const el = $('#syncTime'); if (el) el.textContent = ts }
function setSyncMsg(msg, isError = false) {
  const el = $('#syncMsg'); if (!el) return
  el.textContent = msg || ''
  el.classList.remove('text-muted', 'text-danger')
  el.classList.add(isError ? 'text-danger' : 'text-muted')
}
async function tryShowSyncByRole(role) { if (ALLOWED_ROLES.includes(role)) showSyncCard() }
async function doSync() {
  setSyncBusy(true); setSyncStatus('Proses…', true); setSyncMsg('')
  try {
    const res = await api.post('/sync/upttik/all', {}, { timeout: 600000 })
    const nowId = new Date().toLocaleString('id-ID')
    setSyncTime(nowId)
    const msg = res?.data?.message || res?.data?.msg || 'Sinkronisasi berhasil.'
    setSyncMsg(msg, false); setSyncStatus('Berhasil', true)
    $('#btnSyncReload')?.classList.remove('d-none')
  } catch (err) {
    const code = err?.response?.status
    const srv = err?.response?.data?.message || err?.message || 'Gagal'
    setSyncMsg(`Gagal sinkronisasi${code ? ` (HTTP ${code})` : ''}: ${srv}`, true)
    setSyncStatus('Gagal', false)
  } finally { setSyncBusy(false) }
}

/* ========== MAHASISWA DASHBOARD ========== */
function showStudentSection() {
  $('#adminStats')?.classList.add('d-none')
  $('#syncCard')?.classList.add('d-none')
  $('#studentStats')?.classList.remove('d-none')
}
const esc = (s) => String(s ?? '').replace(/[&<>"']/g, m => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[m]))

function pickNim(me) {
  const cand = me?.nim ?? me?.mahasiswa?.nim ?? (isLikelyNim(me?.username) ? me.username : '')
  const fromLS = localStorage.getItem('auth_nim') || ''
  const nim = cand || fromLS || ''
  if (isLikelyNim(nim)) localStorage.setItem('auth_nim', nim)
  return nim
}

function toDate(v) { const d = v ? new Date(v) : null; return isNaN(d?.getTime?.()) ? null : d }
function byCreatedDesc(a, b) {
  const da = toDate(a.created_at) || 0
  const db = toDate(b.created_at) || 0
  return db - da
}

async function loadStudentOverview(nim) {
  showStudentSection()
  // hit 3 endpoint khusus by nim
  const [taRes, kpRes, sfRes] = await Promise.allSettled([
    api.get(`/mahasiswa/${encodeURIComponent(nim)}/tugas-akhir`),
    api.get(`/mahasiswa/${encodeURIComponent(nim)}/kerja-praktek`),
    api.get(`/mahasiswa/${encodeURIComponent(nim)}/sertifikat`),
  ])

  const toRows = (r) => (Array.isArray(r?.value?.data?.data) ? r.value.data.data
    : Array.isArray(r?.value?.data) ? r.value.data : [])

  const tas = toRows(taRes).map(x => ({ type: 'TA', title: x.judul, extra: x.kategori || x.kategori_ta, created_at: x.created_at, id: x.id }))
  const kps = toRows(kpRes).map(x => ({ type: 'KP', title: x.nama_kegiatan, extra: 'Kerja Praktek', created_at: x.created_at, id: x.id }))
  const sfs = toRows(sfRes).map(x => ({ type: 'Sertifikasi', title: x.nama_sertifikasi || x.nama, extra: x.kategori_sertifikasi || x.kategori, created_at: x.created_at, id: x.id }))

  // set counters
  $('#mStatTa').textContent = String(tas.length)
  $('#mStatKp').textContent = String(kps.length)
  $('#mStatSf').textContent = String(sfs.length)

  // recent 5 combined
  const recent = [...tas, ...kps, ...sfs].sort(byCreatedDesc).slice(0, 5)
  const tbody = $('#mRecent')
  if (!recent.length) {
    tbody.innerHTML = `<tr><td colspan="3" class="text-center text-muted p-3">Belum ada data</td></tr>`
  } else {
    tbody.innerHTML = recent.map(r => `
      <tr>
        <td><span class="badge text-bg-secondary">${esc(r.type)}</span></td>
        <td>${esc(r.title || '-')}</td>
        <td class="text-muted">${esc(r.extra || '')}</td>
      </tr>
    `).join('')
  }
}

/* ========== INIT ========== */
; (async function init() {
  try {
    const { data: me } = await api.get('/me')
    const role = me?.role || ''
    if (role === 'Mahasiswa') {
      const nim = pickNim(me)
      if (!nim) throw new Error('NIM tidak terdeteksi')
      await loadStudentOverview(nim)
    } else {
      await loadAdminStats()
      await tryShowSyncByRole(role)
    }

    $('#btnSync')?.addEventListener('click', doSync)
    $('#btnSyncReload')?.addEventListener('click', async () => {
      $('#btnSyncReload')?.classList.add('d-none')
      await loadAdminStats()
    })
  } catch (err) {
    if (isAuthError(err)) {
      // fallback: tetap tampilkan admin cards kosong
      console.warn('Auth error on /me, showing minimal dashboard')
    } else {
      console.error(err)
    }
  }
})()

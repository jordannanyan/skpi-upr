// resources/js/admin/pages/dashboard.js
import { api } from '../../services/api'

const $ = (s) => document.querySelector(s)

async function loadStats() {
  try {
    const [m, p, f] = await Promise.all([
      api.get('/mahasiswa?per_page=1'),
      api.get('/prodi?per_page=1'),
      api.get('/fakultas?per_page=1'),
    ])
    const getTotal = (res) =>
      res?.data?.total ??
      res?.data?.meta?.total ??
      res?.data?.data?.length ??
      '—'

    $('#statMhs').textContent   = getTotal(m)
    $('#statProdi').textContent = getTotal(p)
    $('#statFak').textContent   = getTotal(f)
  } catch (_) {
    // biarkan default "—"
  }
}

/** ===== Sinkronisasi ===== */
const ALLOWED_ROLES = ['SuperAdmin', 'AdminFakultas', 'AdminJurusan']

function showSyncCard() {
  $('#syncCard')?.classList.remove('d-none')
}

function setSyncBusy(busy) {
  const btn = $('#btnSync')
  const spn = btn?.querySelector('.spinner-border')
  const lbl = btn?.querySelector('.sync-label')
  if (!btn || !spn || !lbl) return
  btn.disabled = busy
  spn.classList.toggle('d-none', !busy)
  lbl.textContent = busy ? 'Menyinkronkan…' : 'Sinkronkan'
}

function setSyncStatus(text, ok = true) {
  const el = $('#syncStatus')
  if (!el) return
  el.textContent = text
  el.classList.remove('text-muted', 'text-success', 'text-danger')
  el.classList.add(ok ? 'text-success' : 'text-danger')
}

function setSyncTime(ts) {
  const el = $('#syncTime')
  if (!el) return
  el.textContent = ts
}

function setSyncMsg(msg, isError = false) {
  const el = $('#syncMsg')
  if (!el) return
  el.textContent = msg || ''
  el.classList.remove('text-muted', 'text-danger')
  el.classList.add(isError ? 'text-danger' : 'text-muted')
}

async function tryShowSyncByRole() {
  try {
    const { data } = await api.get('/me')
    const role = data?.role
    if (ALLOWED_ROLES.includes(role)) {
      showSyncCard()
    }
  } catch {
    // jika gagal ambil /me, abaikan saja
  }
}

async function doSync() {
  setSyncBusy(true)
  setSyncStatus('Proses…', true)
  setSyncMsg('')
  try {
    const res = await api.post('/sync/upttik/all') // tidak butuh payload
    // Respons bisa bervariasi, tampilkan ringkas
    const nowId = new Date().toLocaleString('id-ID')
    setSyncTime(nowId)

    // Coba baca pesan server jika ada
    const msg =
      res?.data?.message ||
      res?.data?.msg ||
      'Sinkronisasi berhasil.'
    setSyncMsg(msg, false)
    setSyncStatus('Berhasil', true)

    // Tampilkan tombol muat ulang statistik
    $('#btnSyncReload')?.classList.remove('d-none')
  } catch (err) {
    const code = err?.response?.status
    const srv  = err?.response?.data?.message || err?.message || 'Gagal'
    setSyncMsg(`Gagal sinkronisasi${code ? ` (HTTP ${code})` : ''}: ${srv}`, true)
    setSyncStatus('Gagal', false)
  } finally {
    setSyncBusy(false)
  }
}

/** ===== Init ===== */
(async function init() {
  await loadStats()
  await tryShowSyncByRole()

  $('#btnSync')?.addEventListener('click', doSync)
  $('#btnSyncReload')?.addEventListener('click', async () => {
    $('#btnSyncReload')?.classList.add('d-none')
    await loadStats()
  })
})()

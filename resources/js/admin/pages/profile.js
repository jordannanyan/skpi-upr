import { api } from '../../services/api'
import { auth } from '../../services/auth'

const $ = (s) => document.querySelector(s)

const bridge       = document.getElementById('bridge')
const LOGIN_URL    = bridge?.dataset?.loginUrl    || '/login'
const PASSWORD_URL = bridge?.dataset?.passwordUrl || '/admin/password'

const setText = (el, txt = '—') => { if (el) el.textContent = txt }
const isAuthError = (err) => {
  const st = err?.response?.status
  return st === 401 || st === 419
}

function fmtTs(ms) {
  if (!ms) return '—'
  try {
    const d = new Date(Number(ms))
    if (isNaN(d.getTime())) return '—'
    // tampilkan ringkas: dd/mm/yyyy hh:mm
    return `${String(d.getDate()).padStart(2,'0')}/${String(d.getMonth()+1).padStart(2,'0')}/${d.getFullYear()} ${String(d.getHours()).padStart(2,'0')}:${String(d.getMinutes()).padStart(2,'0')}`
  } catch { return '—' }
}

async function loadMasters() {
  const [f, p] = await Promise.all([
    api.get('/fakultas?per_page=1000').catch(()=>({ data: [] })),
    api.get('/prodi?per_page=2000').catch(()=>({ data: [] })),
  ])
  const faks = f.data?.data || f.data || []
  const pros = p.data?.data || p.data || []

  const mapF = new Map(faks.map(x => [String(x.id), x.nama_fakultas || `Fakultas ${x.id}`]))
  const mapP = new Map(pros.map(x => [String(x.id), x.nama_prodi || `Prodi ${x.id}`]))

  return { mapF, mapP }
}

async function bootstrap() {
  if (!auth.get()) return window.location.replace(LOGIN_URL)

  try {
    const [{ data: me }, masters] = await Promise.all([api.get('/me'), loadMasters()])
    // isi header kartu
    setText($('#profUsername'), me.username || '-')
    setText($('#profRole'), me.role || '-')
    // fakultas/prodi
    const fakName = masters.mapF.get(String(me.id_fakultas ?? '')) || '-'
    const proName = masters.mapP.get(String(me.id_prodi ?? '')) || '-'
    setText($('#profFakultas'), fakName)
    setText($('#profProdi'), proName)

    // last login (pakai localStorage timestamp dari login.js kalau ada)
    const last = localStorage.getItem('auth_login_at')
    setText($('#profLastLogin'), fmtTs(last))

    // set link ganti password dari bridge
    const a = $('#btnGoPassword'); if (a) a.setAttribute('href', PASSWORD_URL)

    // info tambahan (opsional)
    const infoBox = $('#profInfoBox')
    if (infoBox) {
      infoBox.textContent = 'Data profil diambil dari sesi saat ini.'
      infoBox.classList.remove('d-none')
    }
  } catch (err) {
    if (isAuthError(err)) {
      auth.clear()
      return window.location.replace(LOGIN_URL)
    }
    const ebox = $('#profErrBox')
    if (ebox) {
      ebox.textContent = err?.response?.data?.message || err.message || 'Gagal memuat profil'
      ebox.classList.remove('d-none')
    }
  }
}

bootstrap()

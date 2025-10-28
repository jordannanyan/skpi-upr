// resources/js/admin/shell.js
import { api } from '../services/api'
import { auth } from '../services/auth'

// ==== Bridge & URL (aman meski #bridge tidak ada) ====
const bridge       = document.getElementById('bridge')
const getDataAttr  = (k, dft) => (bridge?.dataset?.[k] ?? dft)
const LOGIN_URL    = getDataAttr('loginUrl', '/login')
const ADMIN_URL    = getDataAttr('adminUrl', '/admin')
const LOGOUT_URL   = getDataAttr('logoutUrl', '/logout')
const PROFILE_URL  = getDataAttr('profileUrl', '/admin/profile')
const PASSWORD_URL = getDataAttr('passwordUrl', '/admin/password')

// ==== Helper ====
const $ = (s) => document.querySelector(s)
const isAuthError = (err) => {
  const st = err?.response?.status
  return st === 401 || st === 419
}
const setText = (el, txt) => { if (el) el.textContent = txt ?? '' }
const isLikelyNim = (v) => /^[0-9]{6,20}$/.test(String(v || '').trim())

// ==== Pastikan header Authorization langsung terpasang ====
const token = auth.get?.() || localStorage.getItem('auth_token')
if (token) {
  api.defaults.headers.common['Authorization'] = `Bearer ${token}`
} else {
  // Tidak ada token → paksa ke login
  window.location.replace(LOGIN_URL)
}

// ==== Render UI user ====
function renderUserUI(user = {}) {
  // Sidebar + Header
  setText($('#userRole'), user.role || '-')
  setText($('#userName'), user.username || '-')
  setText($('#userRoleMini'), user.role ? `(${user.role})` : '')
  setText($('#userDesc'), user.username ? `${user.username} • ${user.role || '-'}` : '—')

  // Link profil & password
  $('#linkProfile')?.setAttribute('href', PROFILE_URL)
  $('#linkPassword')?.setAttribute('href', PASSWORD_URL)
}

// ==== Sembunyikan menu berdasar role (data-roles="Role1,Role2,..." ) ====
function guardMenuByRole(role) {
  document.querySelectorAll('#adminMenu a[data-roles]').forEach((a) => {
    const allow = (a.getAttribute('data-roles') || '')
      .split(',')
      .map(s => s.trim())
      .filter(Boolean)
    if (role && !allow.includes(role)) {
      a.style.display = 'none'
    }
  })
}

// ==== Set menu aktif sederhana (tahan terhadap URL absolut) ====
// ==== Set menu aktif: pilih satu link terbaik (longest match, exact > prefix) ====
function highlightActiveMenu() {
  const curPath = window.location.pathname.replace(/\/+$/, '') || '/';
  const items = Array.from(document.querySelectorAll('#adminMenu a[href]'));

  // hapus semua 'active' yang mungkin ditetapkan server/JS sebelumnya
  items.forEach(a => a.classList.remove('active'));

  let best = null;
  let bestScore = -1;

  for (const a of items) {
    let href = a.getAttribute('href') || '';
    try {
      href = new URL(href, window.location.origin).pathname;
    } catch (_) { /* relative ok */ }
    href = href.replace(/\/+$/, '') || '/';

    // Abaikan anchor kosong
    if (!href || href === '#') continue;

    // Skor: exact match diberi +1000, prefix match diberi panjang href
    let score = -1;
    if (href === curPath) {
      score = 1000 + href.length; // exact menang
    } else if (curPath.startsWith(href) && (href === '/' || curPath[href.length] === '/' )) {
      // pastikan match pada boundary segmen, supaya '/admin' tidak aktif untuk '/administrator'
      score = href.length; // semakin spesifik semakin tinggi
    }

    if (score > bestScore) {
      bestScore = score;
      best = a;
    }
  }

  if (best && bestScore >= 0) {
    best.classList.add('active');
  }
}


// ==== Simpan cache identitas ringan ke localStorage ====
function cacheIdentity(me = {}) {
  // Simpan dasar
  localStorage.setItem('auth_username', me.username ?? localStorage.getItem('auth_username') ?? '')
  localStorage.setItem('auth_role', me.role ?? localStorage.getItem('auth_role') ?? '')
  localStorage.setItem('auth_id_fakultas', String(me.id_fakultas ?? localStorage.getItem('auth_id_fakultas') ?? ''))
  localStorage.setItem('auth_id_prodi', String(me.id_prodi ?? localStorage.getItem('auth_id_prodi') ?? ''))

  // Tentukan NIM yang paling akurat
  const nimFromMe =
    me.nim ??
    me?.mahasiswa?.nim ??
    (isLikelyNim(me.username) ? me.username : '')

  const nimExisting = localStorage.getItem('auth_nim') || ''
  localStorage.setItem('auth_nim', (nimFromMe || nimExisting || ''))
}

// ==== Bootstrap (/me) ====
;(async function bootstrap() {
  try {
    const { data } = await api.get('/me')
    const me = data || {}

    cacheIdentity(me)
    renderUserUI(me)
    guardMenuByRole(me.role)
    highlightActiveMenu()

    // Opsional: expose ringkas untuk FE lain
    window.__AUTH__ = {
      username: me.username || localStorage.getItem('auth_username') || '',
      role: me.role || localStorage.getItem('auth_role') || '',
      nim: localStorage.getItem('auth_nim') || '',
      id_fakultas: Number(localStorage.getItem('auth_id_fakultas') || 0) || null,
      id_prodi: Number(localStorage.getItem('auth_id_prodi') || 0) || null,
    }
  } catch (err) {
    if (isAuthError(err)) {
      auth.clear?.()
      return window.location.replace(LOGIN_URL)
    }
    // Fallback render dari cache
    const cached = {
      username: localStorage.getItem('auth_username') || '',
      role: localStorage.getItem('auth_role') || ''
    }
    renderUserUI(cached)
    guardMenuByRole(cached.role)
    highlightActiveMenu()
    console.warn('Gagal memuat profil /me:', err?.response?.data || err.message)
  }
})()

// ==== Logout (dua tombol) ====
async function doLogout(e) {
  e?.preventDefault?.()
  try { await api.post('/logout') } catch { /* ignore */ }
  auth.clear?.()
  // Bersihkan cache ringan juga
  localStorage.removeItem('auth_username')
  localStorage.removeItem('auth_role')
  localStorage.removeItem('auth_id_fakultas')
  localStorage.removeItem('auth_id_prodi')
  localStorage.removeItem('auth_nim')
  localStorage.removeItem('auth_login_at')
  window.location.replace(LOGIN_URL || LOGOUT_URL || '/login')
}
$('#btnLogout')?.addEventListener('click', doLogout)     // sidebar
$('#btnLogoutTop')?.addEventListener('click', doLogout)  // dropdown header

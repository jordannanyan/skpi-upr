// resources/js/admin/shell.js
import { api } from '../services/api'
import { auth } from '../services/auth'

// ==== Bridge & URL ====
const bridge        = document.getElementById('bridge')
const LOGIN_URL     = bridge?.dataset?.loginUrl     || '/login'
const ADMIN_URL     = bridge?.dataset?.adminUrl     || '/admin'
const LOGOUT_URL    = bridge?.dataset?.logoutUrl    || '/logout'     // fallback redirect
const PROFILE_URL   = bridge?.dataset?.profileUrl   || '/admin/profile'
const PASSWORD_URL  = bridge?.dataset?.passwordUrl  || '/admin/password'

// ==== Helper ====
const $ = (s) => document.querySelector(s)
const isAuthError = (err) => {
  const st = err?.response?.status
  return st === 401 || st === 419
}
const setText = (el, txt) => { if (el) el.textContent = txt }

// ==== Guard awal: harus ada token ====
if (!auth.get()) {
  window.location.replace(LOGIN_URL)
}

// ==== Render UI user ====
function renderUserUI(user = {}) {
  // Sidebar
  setText($('#userRole'), user.role || '-')
  // Header (dropdown)
  setText($('#userName'), user.username || '-')
  setText($('#userRoleMini'), user.role ? `(${user.role})` : '')
  setText($('#userDesc'), user.username ? `${user.username} • ${user.role || '-'}` : '—')

  // Link profil & password
  const linkProfile = $('#linkProfile')
  const linkPassword = $('#linkPassword')
  if (linkProfile)  linkProfile.setAttribute('href', PROFILE_URL)
  if (linkPassword) linkPassword.setAttribute('href', PASSWORD_URL)
}

// ==== Sembunyikan menu berdasar role (data-roles) ====
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

// ==== Set active menu sederhana ====
function highlightActiveMenu() {
  // Pilih berdasarkan prefix href (tetap) ATAU gunakan attribute data-active sesuai kebutuhan
  document.querySelectorAll('#adminMenu a[href]').forEach(a => {
    const href = a.getAttribute('href') || ''
    if (href !== '#' && window.location.pathname.startsWith(href)) {
      a.classList.add('active')
    }
  })
}

// ==== Bootstrap (load /me) ====
;(async function bootstrap() {
  try {
    const { data } = await api.get('/me')
    const me = data || {}

    // simpan ke localStorage untuk fallback halaman lain (opsional)
    localStorage.setItem('auth_username', me.username || '')
    localStorage.setItem('auth_role', me.role || '')
    localStorage.setItem('auth_id_fakultas', String(me.id_fakultas ?? ''))
    localStorage.setItem('auth_id_prodi', String(me.id_prodi ?? ''))

    renderUserUI(me)
    guardMenuByRole(me.role)
    highlightActiveMenu()
  } catch (err) {
    if (isAuthError(err)) {
      // token invalid/expired
      auth.clear()
      return window.location.replace(LOGIN_URL)
    }
    // error lain: tetap render dari cache agar UI tidak kosong
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
  auth.clear()
  // Redirect ke LOGIN_URL; jika kamu ingin benar-benar hit /logout web route, ganti ke LOGOUT_URL
  window.location.replace(LOGIN_URL)
}
$('#btnLogout')?.addEventListener('click', doLogout)     // sidebar
$('#btnLogoutTop')?.addEventListener('click', doLogout)  // dropdown header

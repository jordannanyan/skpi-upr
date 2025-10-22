import { api } from '../services/api'
import { auth } from '../services/auth'

// Bridge
const bridge = document.getElementById('bridge')
const LOGIN_URL = bridge?.dataset?.loginUrl || '/login'
const ADMIN_URL = bridge?.dataset?.adminUrl || '/admin'

// Redirect kalau belum login
if (!auth.get()) {
  window.location.replace(LOGIN_URL)
}

// Muat profil user
;(async function bootstrap() {
  try {
    const { data } = await api.get('/me')
    // tampilkan info user
    document.getElementById('userRole').textContent = data.role || '-'
    document.getElementById('userInfo').textContent = data.username || '-'
    // (opsional) sembunyikan menu berdasar role di sini
  } catch (e) {
    auth.clear()
    window.location.replace(LOGIN_URL)
  }
})()

// Logout
document.getElementById('btnLogout')?.addEventListener('click', async (e) => {
  e.preventDefault()
  try { await api.post('/logout') } catch {}
  auth.clear()
  window.location.replace(LOGIN_URL)
})

// Set active menu (sederhana)
document.querySelectorAll('#adminMenu a[data-active]').forEach(a => {
  if (window.location.pathname.startsWith(a.getAttribute('href'))) {
    a.classList.add('active')
  }
})

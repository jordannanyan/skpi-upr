import { api } from '../services/api'
import { auth } from '../services/auth'

// Ambil data bridge dari meta (biar nggak hardcode URL)
const bridge = document.getElementById('bridge')
const ADMIN_URL = bridge?.dataset?.adminUrl || '/admin'

const form = document.getElementById('loginForm')
const userEl = document.getElementById('username')
const passEl = document.getElementById('password')
const errBox = document.getElementById('loginError')

if (form) {
  form.addEventListener('submit', async (e) => {
    e.preventDefault()
    errBox.classList.add('d-none')
    const username = userEl.value.trim()
    const password = passEl.value

    if (!username || !password) {
      errBox.textContent = 'Username dan password wajib diisi.'
      errBox.classList.remove('d-none')
      return
    }

    try {
      const { data } = await api.post('/login', { username, password })
      // ekspektasi: { token, user: { username, role, id_fakultas?, id_prodi? } }
      if (!data?.token) throw new Error('Token tidak diterima dari server.')
      auth.set(data.token)
      // redirect ke /admin (nanti kita bangun halaman adminnya)
      window.location.replace(ADMIN_URL)
    } catch (err) {
      const msg = err?.response?.data?.message || 'Login gagal. Periksa kredensial.'
      errBox.textContent = msg
      errBox.classList.remove('d-none')
    }
  })
}

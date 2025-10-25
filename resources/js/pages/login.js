// resources/js/pages/login.js
import { api } from '../services/api'
import { auth } from '../services/auth'

// Ambil data bridge dari meta (biar nggak hardcode URL)
const bridge = document.getElementById('bridge')
const ADMIN_URL = bridge?.dataset?.adminUrl || '/admin'

const form   = document.getElementById('loginForm')
const userEl = document.getElementById('username')
const passEl = document.getElementById('password')
const errBox = document.getElementById('loginError')

const setSubmitting = (isSubmitting) => {
  const btn = form?.querySelector('button[type="submit"]')
  if (!btn) return
  btn.disabled = isSubmitting
  btn.innerText = isSubmitting ? 'Memproses…' : 'Masuk'
}

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
      setSubmitting(true)

      // ekspektasi response: { token, user: { id, username, role, id_fakultas?, id_prodi? } }
      const { data } = await api.post('/login', { username, password })
      const token = data?.token
      const user  = data?.user || {}

      if (!token) throw new Error('Token tidak diterima dari server.')

      // 1) simpan token di storage yg konsisten dgn shell
      auth.set(token) // pastikan auth.set menulis ke localStorage 'auth_token'
      localStorage.setItem('auth_token', token) // hard-ensure untuk shell/axios lain

      // 2) set header Authorization SEGERA (tanpa reload)
      api.defaults.headers.common['Authorization'] = `Bearer ${token}`

      // 3) simpan atribut user utk fallback UI
      localStorage.setItem('auth_username', user.username || username)
      localStorage.setItem('auth_role', user.role || '')
      localStorage.setItem('auth_id_fakultas', String(user.id_fakultas ?? ''))
      localStorage.setItem('auth_id_prodi', String(user.id_prodi ?? ''))

      // OPTIONAL: timestamp login (bisa dipakai utk auto-expire manual)
      localStorage.setItem('auth_login_at', String(Date.now()))

      // 4) lanjut ke admin
      window.location.replace(ADMIN_URL)
    } catch (err) {
      const msg = err?.response?.data?.message || err.message || 'Login gagal. Periksa kredensial.'
      errBox.textContent = msg
      errBox.classList.remove('d-none')
    } finally {
      setSubmitting(false)
    }
  })
}

// resources/js/pages/login-nim.js
import { api } from '../services/api'
import { auth } from '../services/auth'

const form = document.getElementById('loginNimForm')
const errBox = document.getElementById('loginError')
const bridge = document.getElementById('bridge')
const ADMIN_URL = bridge?.dataset?.adminUrl || '/admin'

const setBusy = (b) => {
  const btn = form.querySelector('button[type="submit"]')
  btn.disabled = b
  btn.textContent = b ? 'Memproses…' : 'Masuk'
}

form?.addEventListener('submit', async (e) => {
  e.preventDefault()
  errBox.classList.add('d-none')

  const nim = (document.getElementById('nim').value || '').trim()
  if (!nim) {
    errBox.textContent = 'NIM wajib diisi.'
    errBox.classList.remove('d-none')
    return
  }

  setBusy(true)
  try {
    const { data } = await api.post('/login/nim', { nim })
    const token = data?.token
    const user  = data?.user || {}

    if (!token) throw new Error('Token tidak diterima.')

    // simpan token
    auth.set(token)
    api.defaults.headers.common['Authorization'] = `Bearer ${token}`

    // simpan identitas ringkas untuk FE
    localStorage.setItem('auth_username', user.username || nim)
    localStorage.setItem('auth_role', user.role || 'Mahasiswa')
    localStorage.setItem('auth_id_fakultas', String(user.id_fakultas ?? ''))
    localStorage.setItem('auth_id_prodi', String(user.id_prodi ?? ''))
    localStorage.setItem('auth_nim', user.nim || nim)   // ⟵ SIMPAN NIM DI SINI

    window.location.replace(ADMIN_URL)
  } catch (err) {
    errBox.textContent = err?.response?.data?.message || err.message || 'Login gagal.'
    errBox.classList.remove('d-none')
  } finally {
    setBusy(false)
  }
})

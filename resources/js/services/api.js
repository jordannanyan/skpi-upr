// resources/js/services/api.js
import axios from 'axios'

// Ambil base URL dari <meta id="bridge"> kalau ada
let API_BASE = '/api'
try {
  const bridge = typeof document !== 'undefined' ? document.getElementById('bridge') : null
  API_BASE = bridge?.dataset?.apiBase || API_BASE
} catch { /* noop */ }

export const api = axios.create({
  baseURL: API_BASE,
  timeout: 20000, // 20s, bebas ubah
  headers: {
    'Accept': 'application/json',
    'X-Requested-With': 'XMLHttpRequest',
    // Jangan set 'Content-Type' global: biarkan axios menentukan (JSON vs FormData)
  },
})

// ——— Set Authorization awal dari localStorage
const bootToken = localStorage.getItem('auth_token')
if (bootToken) {
  api.defaults.headers.common['Authorization'] = `Bearer ${bootToken}`
} else {
  delete api.defaults.headers.common['Authorization']
}

// ——— Interceptor request: sinkronkan header Authorization setiap request
api.interceptors.request.use((config) => {
  const tk = localStorage.getItem('auth_token')
  if (tk && tk !== '') {
    config.headers.Authorization = `Bearer ${tk}`
  } else {
    // penting: jangan kirim bearer basi
    delete config.headers.Authorization
  }
  return config
})

// ——— (Opsional) Interceptor response: normalisasi error msg, tanpa auto-redirect di sini
api.interceptors.response.use(
  (res) => res,
  (err) => {
    // Biarkan handler di masing-masing halaman yang memutuskan redirect logout.
    // Tapi kita rapikan pesan agar seragam.
    err._friendlyMessage =
      err?.response?.data?.message ||
      err?.message ||
      'Terjadi kesalahan jaringan.'
    return Promise.reject(err)
  }
)

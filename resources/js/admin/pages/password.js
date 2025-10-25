import { api } from '../../services/api'
import { auth } from '../../services/auth'

const $ = (s) => document.querySelector(s)

const bridge    = document.getElementById('bridge')
const LOGIN_URL = bridge?.dataset?.loginUrl || '/login'
const ADMIN_URL = bridge?.dataset?.adminUrl || '/admin'

const curPass   = $('#curPass')   // saat ini tidak dipakai server-side, tetap ditampilkan untuk UX
const newPass   = $('#newPass')
const newPass2  = $('#newPass2')
const logoutAll = $('#logoutAll')
const btn       = $('#btnUpdatePw')
const alertBox  = $('#pwAlert')
const hint      = $('#pwHint')

let userId = null  // diisi dari /me

const isAuthError = (err) => {
  const st = err?.response?.status
  return st === 401 || st === 419
}

function showAlert(type, msg) {
  alertBox.className = `alert alert-${type}`
  alertBox.textContent = msg
  alertBox.classList.remove('d-none')
}
function clearAlert() {
  alertBox.className = 'alert d-none'
  alertBox.textContent = ''
}

function meter(pw) {
  if (!pw) return { score:0, text:'—' }
  let score = 0
  if (pw.length >= 8) score++
  if (/[A-Z]/.test(pw)) score++
  if (/[a-z]/.test(pw)) score++
  if (/\d/.test(pw)) score++
  if (/[^A-Za-z0-9]/.test(pw)) score++
  const labels = ['Sangat lemah','Lemah','Cukup','Baik','Kuat','Sangat kuat']
  return { score, text: labels[Math.min(score, labels.length - 1)] }
}
function updateHint() {
  const { text } = meter(newPass.value.trim())
  hint.textContent = `Kekuatan password: ${text}`
}

async function mustAuthAndLoadMe() {
  if (!auth.get()) return window.location.replace(LOGIN_URL)
  try {
    const { data } = await api.get('/me')
    userId = data?.id
    if (!userId) throw new Error('User id tidak tersedia dari /me')
  } catch (err) {
    if (isAuthError(err)) {
      auth.clear(); return window.location.replace(LOGIN_URL)
    }
    showAlert('danger', 'Gagal memuat profil pengguna.')
    throw err
  }
}

btn?.addEventListener('click', async () => {
  clearAlert()

  // FE validation
  const current_password       = curPass.value // disimpan untuk UX; tidak dikirim karena route update tidak memerlukannya
  const password               = newPass.value
  const password_confirmation  = newPass2.value
  const doLogoutOthers         = !!logoutAll.checked

  if (!password || !password_confirmation) {
    return showAlert('warning', 'Lengkapi password baru & konfirmasi.')
  }
  if (password.length < 8) {
    return showAlert('warning', 'Password baru minimal 8 karakter.')
  }
  if (password !== password_confirmation) {
    return showAlert('warning', 'Konfirmasi password tidak cocok.')
  }
  if (!userId) {
    return showAlert('danger', 'User belum terdeteksi. Muat ulang halaman.')
  }

  btn.disabled = true
  btn.innerHTML = `<span class="spinner-border spinner-border-sm me-2"></span>Menyimpan...`

  try {
    // ====== PENTING ======
    // Gunakan route update user: PUT /api/users/{id}
    // Sesuai UserUpdateRequest: password bersifat optional; kirim hanya { password }.
    await api.put(`/users/${userId}`, { password })

    showAlert('success', 'Password berhasil diubah.')

    if (doLogoutOthers) {
      // Backend kamu belum menyediakan endpoint revoke-others;
      // setidaknya logout token saat ini supaya aman.
      try { await api.post('/logout') } catch {}
      auth.clear()
      setTimeout(() => window.location.replace(LOGIN_URL), 600)
    } else {
      // Bersihkan form, tetap di halaman
      curPass.value = ''
      newPass.value = ''
      newPass2.value = ''
      updateHint()
      // Opsional redirect ringan:
      // setTimeout(() => window.location.replace(ADMIN_URL), 800)
    }
  } catch (err) {
    if (isAuthError(err)) {
      auth.clear(); return window.location.replace(LOGIN_URL)
    }
    const msg = err?.response?.data?.message
      || (err?.response?.data?.errors ? 'Validasi gagal.' : null)
      || 'Gagal mengubah password.'
    showAlert('danger', msg)
  } finally {
    btn.disabled = false
    btn.innerHTML = `<i class="bi bi-shield-lock me-1"></i> Simpan Password`
  }
})

newPass?.addEventListener('input', updateHint)
updateHint()

;(async function init(){
  try {
    await mustAuthAndLoadMe()
  } catch {}
})()

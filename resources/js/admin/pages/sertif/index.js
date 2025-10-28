// resources/js/admin/pages/sertif/index.js
import { api } from '../../../services/api'
import { auth } from '../../../services/auth'

const $ = s => document.querySelector(s)
const esc = s => String(s ?? '').replace(/[&<>"']/g, m => (
  {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m]
))
const isAuthError = err => [401,419].includes(err?.response?.status)
const isLikelyNim = v => /^[0-9]{6,20}$/.test(String(v || '').trim())

let me = null
let role = 'AdminJurusan'
let myNim = null

// bridge
const bridge    = document.getElementById('bridge')
const ADMIN_URL = bridge?.dataset?.adminUrl || '/admin'
const LOGIN_URL = bridge?.dataset?.loginUrl || '/login'

// elements
const body      = $('#sfBody')
const inpKw     = $('#sfKw')
const inpNim    = $('#sfNim')
const inpKat    = $('#sfKat')
const selFak    = $('#sfFak')
const selPro    = $('#sfProdi')
const roleBox   = $('#roleFilters')
const btnCreate = $('#btnGoCreate')

// helpers
const getNama = r =>
  r?.nama_mhs ??
  r?.mhs?.nama_mahasiswa ??
  r?.mahasiswa?.nama_mahasiswa ?? '-'

const getProdi = r =>
  r?.nama_prodi ??
  r?.prodi?.nama_prodi ??
  r?.mhs?.prodi?.nama_prodi ??
  r?.mahasiswa?.prodi?.nama_prodi ?? '-'

const getFak = r =>
  r?.nama_fakultas ??
  r?.prodi?.fakultas?.nama_fakultas ??
  r?.mhs?.prodi?.fakultas?.nama_fakultas ??
  r?.mahasiswa?.prodi?.fakultas?.nama_fakultas ?? '-'

function normalizeFileUrl(u){
  if (!u) return null
  try{
    if (u.startsWith('/')) return u
    const parsed = new URL(u, window.location.origin)
    parsed.protocol = window.location.protocol
    parsed.host     = window.location.host
    return parsed.toString()
  }catch{
    const m = u.match(/^https?:\/\/[^/]+(\/.*)$/i)
    return m ? m[1] : u
  }
}

function showMsg(msg){
  const COLS = document.querySelectorAll('table thead th').length || 8
  body.innerHTML = `<tr><td colspan="${COLS}" class="text-center text-muted p-4">${esc(msg)}</td></tr>`
}

// scoping url untuk non-superadmin
function roleDefaultFilter(url){
  if ((role==='AdminJurusan' || role==='Kajur') && me?.id_prodi) {
    url += `&prodi_id=${encodeURIComponent(me.id_prodi)}`
  }
  if (['AdminFakultas','Wakadek','Dekan'].includes(role) && me?.id_fakultas) {
    url += `&fakultas_id=${encodeURIComponent(me.id_fakultas)}`
  }
  return url
}

async function loadMe(){
  const { data } = await api.get('/me')
  me   = data
  role = data.role

  // Tentukan NIM user bila ada
  const nimFromMe = me?.nim ?? me?.mahasiswa?.nim ?? (isLikelyNim(me?.username) ? me.username : '')
  const nimFromLS = localStorage.getItem('auth_nim') || ''
  myNim = nimFromMe || nimFromLS || null
  if (myNim && isLikelyNim(myNim)) localStorage.setItem('auth_nim', myNim)

  // tombol tambah
  const canCreate = ['AdminJurusan','Kajur','SuperAdmin','Mahasiswa'].includes(role)
  if (!canCreate) btnCreate?.classList.add('d-none')

  // Mahasiswa: sembunyikan seluruh kartu filter
  if (role === 'Mahasiswa') {
    document.querySelector('.card.border-0.shadow-sm.mb-3')?.classList.add('d-none')
  } else {
    // SuperAdmin: tampilkan filter Fak/Pro
    if (role === 'SuperAdmin') {
      roleBox?.classList.remove('d-none')
      await loadMasters()
    } else {
      roleBox?.classList.add('d-none')
    }
  }
}

async function loadMasters(){
  try{
    const [f,p] = await Promise.all([
      api.get('/fakultas?per_page=100'),
      api.get('/prodi?per_page=200'),
    ])
    const faks = f.data.data || f.data
    const pros = p.data.data || p.data

    selFak.innerHTML = `<option value="">— Semua —</option>`
    faks.forEach(x=>{
      const o = document.createElement('option')
      o.value = x.id
      o.textContent = x.nama_fakultas || `Fakultas ${x.id}`
      selFak.appendChild(o)
    })

    selPro.innerHTML = `<option value="">— Semua —</option>`
    pros.forEach(x=>{
      const o = document.createElement('option')
      o.value = x.id
      o.textContent = x.nama_prodi || `Prodi ${x.id}`
      o.dataset.fak = x.id_fakultas || ''
      selPro.appendChild(o)
    })

    selFak.addEventListener('change', ()=>{
      const v = selFak.value
      ;[...selPro.options].forEach((o,i)=>{
        if (i===0) return
        o.hidden = (v && (o.dataset.fak || '') !== v)
      })
      const cur = selPro.selectedOptions[0]
      if (v && cur && (cur.dataset.fak||'') !== v) selPro.value = ''
    })
  }catch(err){
    if (isAuthError(err)) throw err
    alert(err?.response?.data?.message || err.message || 'Gagal memuat master data')
  }
}

function renderActions(r){
  // Mahasiswa: hanya boleh HAPUS baris miliknya sendiri
  if (role === 'Mahasiswa') {
    const isOwn = String(r.nim || '') === String(myNim || '')
    return isOwn ? `
      <button class="btn btn-sm btn-outline-danger d-inline-flex align-items-center justify-content-center"
              data-act="del" data-id="${r.id}" title="Hapus KP" aria-label="Hapus">
        <i class="bi bi-trash"></i>
      </button>
    ` : '' // baris milik orang lain: tanpa aksi
  }

  // Staf: tetap seperti sebelumnya
  const canEdit = role === 'SuperAdmin' || role === 'AdminJurusan' || role === 'Kajur'
  const btnEdit = canEdit ? `
    <a class="btn btn-sm btn-outline-primary d-inline-flex align-items-center justify-content-center"
       href="${ADMIN_URL}/kp/${r.id}/edit" title="Edit KP" aria-label="Edit">
      <i class="bi bi-pencil-square"></i>
    </a>` : ''

  const btnDel  = role === 'SuperAdmin' ? `
    <button class="btn btn-sm btn-outline-danger d-inline-flex align-items-center justify-content-center"
            data-act="del" data-id="${r.id}" title="Hapus KP" aria-label="Hapus">
      <i class="bi bi-trash"></i>
    </button>` : ''

  return [btnEdit, btnDel].filter(Boolean).join(' ')
}


async function loadSertif(){
  showMsg('Memuat…')
  try{
    let url = '/sertifikasi?per_page=30'

    if (role === 'Mahasiswa') {
      if (!isLikelyNim(myNim)) { showMsg('Tidak dapat menentukan NIM Anda. Silakan login ulang.',); return }
      url += `&nim=${encodeURIComponent(myNim)}`
    } else {
      const kw  = (inpKw?.value || '').trim()
      const nim = (inpNim?.value || '').trim()
      const kat = (inpKat?.value || '').trim()

      if (kw)  url += `&q=${encodeURIComponent(kw)}`
      if (nim) url += `&nim=${encodeURIComponent(nim)}`
      if (kat) url += `&kategori=${encodeURIComponent(kat)}`

      if (role === 'SuperAdmin') {
        const fkid = selFak?.value || ''
        const prid = selPro?.value || ''
        if (fkid) url += `&fakultas_id=${encodeURIComponent(fkid)}`
        if (prid) url += `&prodi_id=${encodeURIComponent(prid)}`
      } else {
        url = roleDefaultFilter(url)
      }
    }

    const { data } = await api.get(url)
    const rows = data.data || data

    if (!rows.length){ showMsg('Tidak ada data'); return }

    body.innerHTML = rows.map(r=>{
      const fileUrl = normalizeFileUrl(r.file_url)
      return `
        <tr>
          <td>${r.id}</td>
          <td><code>${esc(r.nim || '-')}</code></td>
          <td>${esc(getNama(r))}</td>
          <td>${esc(getProdi(r))}</td>
          <td>${esc(getFak(r))}</td>
          <td>${esc(r.kategori_sertifikasi ?? r.kategori ?? '-')}</td>
          <td>${esc(r.nama_sertifikasi ?? r.nama ?? '-')}</td>
          <td class="d-flex flex-wrap gap-2">${renderActions({...r, file_url:fileUrl})}</td>
        </tr>
      `
    }).join('')
  }catch(err){
    if (isAuthError(err)) {
      auth.clear()
      window.location.replace(LOGIN_URL)
      return
    }
    const st = err?.response?.status
    showMsg(st===403 ? 'Anda tidak memiliki akses untuk melihat data ini.' : 'Gagal memuat data.')
  }
}

// events
$('#sfCari')?.addEventListener('click', loadSertif)
inpKw?.addEventListener('keydown', e => { if(e.key==='Enter') loadSertif() })
inpNim?.addEventListener('keydown', e => { if(e.key==='Enter') loadSertif() })
inpKat?.addEventListener('keydown', e => { if(e.key==='Enter') loadSertif() })

// hapus (cek backend juga)
$('#sfBody')?.addEventListener('click', async (e)=>{
  const btn = e.target.closest('button[data-act="del"]')
  if(!btn) return
  const id = btn.dataset.id
  if (!confirm('Hapus data sertifikasi ini?')) return
  try{
    await api.delete(`/sertifikasi/${id}`)
    await loadSertif()
  }catch(err){
    if (isAuthError(err)) {
      auth.clear()
      window.location.replace(LOGIN_URL)
      return
    }
    alert(err?.response?.data?.message || err.message)
  }
})

// boot
;(async function init(){
  try{
    await loadMe()
    await loadSertif()
  }catch(err){
    if (isAuthError(err)) {
      auth.clear()
      window.location.replace(LOGIN_URL)
    } else {
      showMsg('Terjadi kesalahan saat inisialisasi halaman.')
      console.error(err)
    }
  }
})()

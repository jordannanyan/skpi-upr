// resources/js/admin/pages/kp/index.js
import { api } from '../../../services/api'
import { auth } from '../../../services/auth'

// ========== Utils ==========
const $  = s => document.querySelector(s)
const esc = s => String(s ?? '').replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m]))
const isAuthError = (err) => [401,419].includes(err?.response?.status)
const isLikelyNim = (v) => /^[0-9]{6,20}$/.test(String(v || '').trim())

// Bridge
const bridge     = document.getElementById('bridge')
const ADMIN_URL  = bridge?.dataset?.adminUrl || '/admin'
const LOGIN_URL  = bridge?.dataset?.loginUrl || '/login'

// Elemen
const filterCard = document.querySelector('.card.border-0.shadow-sm.mb-3')
const body       = $('#kpBody')
const info       = $('#kpInfo')
const inpKw      = $('#kpKw')
const inpNim     = $('#kpNim')
const selFak     = $('#kpFak')
const selPro     = $('#kpProdi')
const roleBox    = $('#roleFilters')
const btnCreate  = $('#btnGoCreate')
const btnPrev    = $('#kpPrev')
const btnNext    = $('#kpNext')

// Kolom
const COLS       = (() => document.querySelectorAll('table thead th').length || 7)()

// State
let me = null, role = 'AdminJurusan', myNim = null
let page = 1, last = 1

// ===== Helpers =====
const getNama = r => r?.nama_mhs ?? r?.mhs?.nama_mahasiswa ?? r?.mahasiswa?.nama_mahasiswa ?? '-'
const getProdi= r => r?.nama_prodi ?? r?.prodi?.nama_prodi ?? r?.mhs?.prodi?.nama_prodi ?? r?.mahasiswa?.prodi?.nama_prodi ?? '-'
const getFak  = r => r?.nama_fakultas ?? r?.prodi?.fakultas?.nama_fakultas ?? r?.mhs?.prodi?.fakultas?.nama_fakultas ?? r?.mahasiswa?.prodi?.fakultas?.nama_fakultas ?? '-'

function showMsg(msg){ body.innerHTML = `<tr><td colspan="${COLS}" class="text-center text-muted p-4">${esc(msg)}</td></tr>` }

// Staff scoping via prodi/fakultas
function roleDefaultFilter(url){
  if ((role==='AdminJurusan'||role==='Kajur') && me?.id_prodi) {
    url += `&prodi_id=${encodeURIComponent(me.id_prodi)}`
  }
  if (['AdminFakultas','Wakadek','Dekan'].includes(role) && me?.id_fakultas) {
    url += `&fakultas_id=${encodeURIComponent(me.id_fakultas)}`
  }
  return url
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



function renderRows(rows){
  body.innerHTML = rows.map(r => `
    <tr>
      <td>${r.id}</td>
      <td><code>${esc(r.nim || '-')}</code></td>
      <td>${esc(getNama(r))}</td>
      <td>${esc(getProdi(r))}</td>
      <td>${esc(getFak(r))}</td>
      <td>${esc(r.nama_kegiatan || '-')}</td>
      <td class="d-flex flex-wrap gap-2">${renderActions(r)}</td>
    </tr>
  `).join('')
}

// ===== Boot =====
async function loadMe(){
  const { data } = await api.get('/me')
  me = data; role = data.role

  // Tentukan NIM paling akurat
  const nimFromMe = data?.nim ?? data?.mahasiswa?.nim ?? (isLikelyNim(data?.username) ? data.username : '') ?? ''
  myNim = nimFromMe || localStorage.getItem('auth_nim') || ''

  if (role === 'Mahasiswa') {
    // 1) Sembunyikan seluruh kartu filter
    filterCard?.classList.add('d-none')

    // 2) Sembunyikan pagination prev/next
    btnPrev?.classList.add('d-none')
    btnNext?.classList.add('d-none')

    // 3) Lock input NIM (kalau masih ada di DOM)
    if (inpNim) {
      inpNim.value = myNim || ''
      inpNim.readOnly = true
      inpNim.classList.add('bg-light')
      inpNim.placeholder = 'NIM Anda'
    }

    // 4) Tampilkan tombol tambah agar bisa input miliknya
    btnCreate?.classList.remove('d-none')
  } else {
    // Staf
    const canCreate = ['AdminJurusan','Kajur','SuperAdmin'].includes(role)
    if (!canCreate) btnCreate?.classList.add('d-none')

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

// ===== Loader =====
async function loadKP(pageWant = 1){
  showMsg('Memuat…')
  try{
    if (role === 'Mahasiswa') {
      // Shortcut by NIM, tanpa filter & tanpa pagination
      if (!myNim) { showMsg('Tidak dapat menentukan NIM Anda.'); return }
      const { data } = await api.get(`/mahasiswa/${encodeURIComponent(myNim)}/kerja-praktek`)
      const rows = Array.isArray(data?.data) ? data.data : (Array.isArray(data) ? data : [])
      if (!rows.length) { showMsg('Tidak ada data'); info && (info.textContent='—'); return }
      renderRows(rows)
      info && (info.textContent = `Menampilkan ${rows.length} data`)
      return
    }

    // Staf: pakai endpoint standar /kp
    let url = `/kp?per_page=30&page=${pageWant}`
    const kw   = (inpKw?.value || '').trim()
    const nim  = (inpNim?.value || '').trim()
    if (kw)  url += `&q=${encodeURIComponent(kw)}`
    if (nim) url += `&nim=${encodeURIComponent(nim)}`

    if (role === 'SuperAdmin') {
      const fkid = selFak?.value || ''
      const prid = selPro?.value || ''
      if (fkid) url += `&fakultas_id=${encodeURIComponent(fkid)}`
      if (prid) url += `&prodi_id=${encodeURIComponent(prid)}`
    } else {
      url = roleDefaultFilter(url)
    }

    const { data } = await api.get(url)
    const rows = data.data || data
    const meta = data.meta || {}
    page = meta.current_page || pageWant
    last = meta.last_page || page

    if (!rows.length){ showMsg('Tidak ada data'); info && (info.textContent='—'); return }
    renderRows(rows)
    info && (info.textContent = meta.total ? `Hal. ${page}/${last} • ${meta.total} data` : `Hal. ${page}`)
  }catch(err){
    if (isAuthError(err)) {
      auth.clear()
      window.location.replace(LOGIN_URL)
      return
    }
    showMsg(err?.response?.status===403 ? 'Anda tidak memiliki akses untuk melihat data ini.' : 'Gagal memuat data.')
  }
}

// ===== Events =====
$('#kpCari')?.addEventListener('click', ()=>loadKP(1))
inpKw?.addEventListener('keydown', e=>{ if(e.key==='Enter') loadKP(1) })
inpNim?.addEventListener('keydown', e=>{
  if (role === 'Mahasiswa') return e.preventDefault()
  if (e.key === 'Enter') loadKP(1)
})
btnPrev?.addEventListener('click', ()=>{ if(page>1) loadKP(page-1) })
btnNext?.addEventListener('click', ()=>{ if(page<last) loadKP(page+1) })

// Hapus (SuperAdmin only)
$('#kpBody')?.addEventListener('click', async (e)=>{
  const btn = e.target.closest('button[data-act="del"]')
  if(!btn) return
  const id = btn.dataset.id
  if (!confirm('Hapus data KP ini?')) return
  try{
    await api.delete(`/kp/${id}`)
    await loadKP(page)
  }catch(err){
    if (isAuthError(err)) {
      auth.clear()
      window.location.replace(LOGIN_URL)
      return
    }
    alert(err?.response?.data?.message || err.message)
  }
})

// ===== Init =====
;(async function init(){
  try{
    await loadMe()
    await loadKP(1)
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

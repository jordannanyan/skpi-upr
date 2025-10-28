// resources/js/admin/pages/cpl/index.js
import { api } from '../../../services/api'
import { auth } from '../../../services/auth'

const $ = s => document.querySelector(s)
const esc = s => String(s ?? '').replace(/[&<>"']/g, m => (
  {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m]
))
const isAuthError = err => [401,419].includes(err?.response?.status)

let me = null
let role = null
let myProdiId = null

const bridge   = document.getElementById('bridge')
const LOGIN_URL= bridge?.dataset?.loginUrl || '/login'
const ADMIN_URL= bridge?.dataset?.adminUrl || '/admin'

const roleBox = $('#roleFilters')
const selFak  = $('#cplFak')
const selPro  = $('#cplProdi')
const body    = $('#cplBody')
const btnCreate = $('#btnGoCreate')

// ---- util baris: ambil id_prodi baris secara defensif ----
const getRowProdiId = (r) =>
  r?.id_prodi ??
  r?.prodi?.id ??
  r?.prodi_id ??
  null

// ---- tombol aksi per baris berdasar role & scope ----
function actions(row){
  const rowProdiId = getRowProdiId(row)
  const inMyProdi  = (myProdiId && rowProdiId) ? Number(rowProdiId) === Number(myProdiId) : false

  const canEdit   = (role === 'SuperAdmin') || ((role === 'AdminJurusan' || role === 'Kajur') && inMyProdi)
  const canDelete = (role === 'SuperAdmin') || ((role === 'AdminJurusan' || role === 'Kajur') && inMyProdi)

  const hrefEdit = `${ADMIN_URL}/cpl/${encodeURIComponent(row.kode_cpl)}/edit`
  const hrefSkor = `${ADMIN_URL}/cpl/${encodeURIComponent(row.kode_cpl)}/skor`

  const edit = canEdit ? `
    <a class="btn btn-sm btn-outline-primary d-inline-flex align-items-center justify-content-center"
       href="${hrefEdit}" title="Edit CPL" aria-label="Edit">
      <i class="bi bi-pencil-square"></i>
    </a>` : ''

  const skor = `
    <a class="btn btn-sm btn-outline-dark d-inline-flex align-items-center justify-content-center"
       href="${hrefSkor}" title="Kelola Skor" aria-label="Skor">
      <i class="bi bi-bar-chart-line"></i>
    </a>`

  const del = canDelete ? `
    <button class="btn btn-sm btn-outline-danger d-inline-flex align-items-center justify-content-center"
            data-act="del" data-kode="${row.kode_cpl}"
            title="Hapus CPL" aria-label="Hapus">
      <i class="bi bi-trash"></i>
    </button>` : ''

  return [edit, skor, del].filter(Boolean).join(' ')
}

async function loadMe(){
  try{
    const { data } = await api.get('/me')
    me = data
    role = data?.role || localStorage.getItem('auth_role') || null
    myProdiId = data?.id_prodi ?? data?.prodi?.id ?? null
  }catch(err){
    if (isAuthError(err)) {
      auth.clear()
      return window.location.replace(LOGIN_URL)
    }
    role = localStorage.getItem('auth_role') || null
    myProdiId = Number(localStorage.getItem('auth_id_prodi') || 0) || null
    console.warn('Gagal memuat /me:', err?.response?.data || err.message)
  }

  // Create: SuperAdmin + Prodi (AdminJurusan/Kajur)
  const canCreate = ['SuperAdmin','AdminJurusan','Kajur'].includes(role)
  if (!canCreate) btnCreate?.classList.add('d-none')
  else            btnCreate?.classList.remove('d-none')

  // Filter Fak/Pro hanya SuperAdmin
  if (role === 'SuperAdmin') {
    roleBox?.classList.remove('d-none')
    await loadMasters()
  } else {
    roleBox?.classList.add('d-none')
  }
}

async function loadMasters(){
  try{
    const [f,p] = await Promise.all([
      api.get('/fakultas?per_page=100'),
      api.get('/prodi?per_page=200'),
    ])
    const faks = f.data?.data || f.data || []
    const pros = p.data?.data || p.data || []

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

    // Sinkronisasi Prodi saat Fakultas berubah
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
    if (isAuthError(err)) { auth.clear(); return window.location.replace(LOGIN_URL) }
    const st  = err?.response?.status
    const msg = err?.response?.data?.message || err.message || 'unknown error'
    selFak.innerHTML = `<option value="">(gagal memuat fakultas: ${st||''} ${esc(msg)})</option>`
    selPro.innerHTML = `<option value="">(gagal memuat prodi: ${st||''} ${esc(msg)})</option>`
  }
}

// tambahkan scope default ke URL untuk non-SuperAdmin
function roleDefaultFilter(url){
  if ((role === 'AdminJurusan' || role === 'Kajur') && me?.id_prodi) {
    url += `&prodi_id=${encodeURIComponent(me.id_prodi)}`
  }
  if (['AdminFakultas','Wakadek','Dekan'].includes(role) && me?.id_fakultas) {
    url += `&fakultas_id=${encodeURIComponent(me.id_fakultas)}`
  }
  return url
}

async function loadList(){
  const kw   = $('#q')?.value?.trim()
  const fkid = selFak?.value || ''
  const prid = selPro?.value || ''

  body.innerHTML = `<tr><td colspan="6" class="text-center text-muted p-4">Memuat…</td></tr>`

  try{
    let url = '/cpl?per_page=200&with_counts=1'
    if (kw)   url += `&q=${encodeURIComponent(kw)}`

    if (role === 'SuperAdmin') {
      if (fkid) url += `&fakultas_id=${encodeURIComponent(fkid)}`
      if (prid) url += `&prodi_id=${encodeURIComponent(prid)}`
    } else {
      url = roleDefaultFilter(url)
    }

    const { data } = await api.get(url)
    const rows = data.data || data || []

    if (!rows.length) {
      body.innerHTML = `<tr><td colspan="6" class="text-center text-muted p-4">Tidak ada data</td></tr>`
      return
    }

    body.innerHTML = rows.map(r=>{
      const prodi = esc(r.nama_prodi ?? r.prodi?.nama_prodi ?? '-')
      const fak   = esc(r.nama_fakultas ?? r.prodi?.fakultas?.nama_fakultas ?? '-')
      return `
        <tr>
          <td>${esc(r.kode_cpl)}</td>
          <td>${esc(r.kategori || '-')}</td>
          <td>${prodi}</td>
          <td>${fak}</td>
          <td>${esc(r.deskripsi || '-')}</td>
          <td class="d-flex flex-wrap gap-2">${actions(r)}</td>
        </tr>
      `
    }).join('')
  }catch(err){
    if (isAuthError(err)) { auth.clear(); return window.location.replace(LOGIN_URL) }
    console.error('Gagal load list:', err?.response?.data || err.message)
    body.innerHTML = `<tr><td colspan="6" class="text-center text-danger p-4">Gagal memuat data CPL</td></tr>`
  }
}

// events
$('#btnCari')?.addEventListener('click', loadList)
$('#q')?.addEventListener('keydown', e=>{ if(e.key==='Enter') loadList() })

// delete: SuperAdmin, AdminJurusan, Kajur (FE), backend tetap cek scope
$('#cplBody')?.addEventListener('click', async (e)=>{
  const btn = e.target.closest('button[data-act="del"]')
  if (!btn) return
  const kode = btn.dataset.kode
  if (!confirm(`Hapus CPL ${kode}?`)) return
  try{
    await api.delete(`/cpl/${encodeURIComponent(kode)}`)
    await loadList()
  }catch(err){
    if (isAuthError(err)) { auth.clear(); return window.location.replace(LOGIN_URL) }
    alert(err?.response?.data?.message || err.message)
  }
})

// boot
;(async function init(){
  try{
    await loadMe()
    await loadList()
  }catch(err){
    if (isAuthError(err)) {
      auth.clear(); window.location.replace(LOGIN_URL)
    } else {
      console.error(err)
      body.innerHTML = `<tr><td colspan="6" class="text-center text-danger p-4">Terjadi kesalahan saat inisialisasi</td></tr>`
    }
  }
})()

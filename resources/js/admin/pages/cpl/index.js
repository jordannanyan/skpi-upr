import { api } from '../../../services/api'
import { auth } from '../../../services/auth'

const $ = s => document.querySelector(s)
let role = null

const bridge = document.getElementById('bridge')
const LOGIN_URL = bridge?.dataset?.loginUrl || '/login'

const selFak = $('#cplFak')
const selPro = $('#cplProdi')
const body   = $('#cplBody')

const escapeHtml = s => String(s ?? '').replace(/[&<>"']/g, m => ({
  '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'
}[m]))

const isAuthError = (err) => {
  const st = err?.response?.status
  // 401 Unauthorized / 419 Page Expired (CSRF/expired token)
  return st === 401 || st === 419
}

async function loadMe(){
  try{
    const { data } = await api.get('/me')
    role = data?.role || localStorage.getItem('auth_role') || null
  }catch(err){
    if (isAuthError(err)) {
      auth.clear()
      return window.location.replace(LOGIN_URL)
    }
    // fallback: jangan logout, pakai role dari localStorage bila ada
    role = localStorage.getItem('auth_role') || null
    console.warn('Gagal memuat /me:', err?.response?.data || err.message)
  }

  // hanya SA (atau role yang kamu izinkan) yang boleh create/delete CPL
  if (!['SuperAdmin'].includes(role)) {
    $('#btnGoCreate')?.classList.add('d-none')
  }
}

async function loadMasters(){
  try{
    const [f,p] = await Promise.all([
      api.get('/fakultas'),
      api.get('/prodi'),
    ])
    const faks = f.data?.data || f.data || []
    const pros = p.data?.data || p.data || []

    selFak.innerHTML = `<option value="">— Semua —</option>`
    faks.forEach(x=>{
      const opt = document.createElement('option')
      opt.value = x.id
      opt.textContent = x.nama_fakultas || `Fakultas ${x.id}`
      selFak.appendChild(opt)
    })

    selPro.innerHTML = `<option value="">— Semua —</option>`
    pros.forEach(x=>{
      const opt = document.createElement('option')
      opt.value = x.id
      opt.textContent = x.nama_prodi || `Prodi ${x.id}`
      opt.dataset.fak = x.id_fakultas || ''
      selPro.appendChild(opt)
    })

    const filterProdiByFak = ()=>{
      const v = selFak.value
      ;[...selPro.options].forEach((o,i)=>{
        if (i===0) return
        o.hidden = (v && (o.dataset.fak || '') !== v)
      })
      if (v) {
        const cur = selPro.selectedOptions[0]
        if (cur && (cur.dataset.fak||'') !== v) selPro.value = ''
      }
    }
    selFak.addEventListener('change', filterProdiByFak)
    filterProdiByFak()
  }catch(err){
    if (isAuthError(err)) {
      auth.clear(); return window.location.replace(LOGIN_URL)
    }
    const st = err?.response?.status
    const msg = err?.response?.data?.message || err.message || 'unknown error'

    console.error('Gagal load masters:', st, msg)

    // tampilkan status di UI supaya ketahuan
    selFak.innerHTML = `<option value="">(gagal memuat fakultas: ${st||''} ${escapeHtml(msg)})</option>`
    selPro.innerHTML = `<option value="">(gagal memuat prodi: ${st||''} ${escapeHtml(msg)})</option>`
  }
}

function actions(row){
  const toEdit = `<a class="btn btn-sm btn-outline-primary" href="/admin/cpl/${encodeURIComponent(row.kode_cpl)}/edit">Edit</a>`
  const toSkor = `<a class="btn btn-sm btn-outline-dark" href="/admin/cpl/${encodeURIComponent(row.kode_cpl)}/skor">Skor</a>`
  const del = ['SuperAdmin'].includes(role)
    ? `<button class="btn btn-sm btn-outline-danger" data-act="del" data-kode="${row.kode_cpl}">Hapus</button>`
    : ''
  return [toEdit,toSkor,del].filter(Boolean).join(' ')
}

async function loadList(){
  const kw   = $('#q')?.value?.trim()
  const fkid = selFak?.value || ''
  const prid = selPro?.value || ''

  body.innerHTML = `<tr><td colspan="7" class="text-center text-muted p-4">Memuat…</td></tr>`

  try{
    let url = '/cpl?per_page=100&with_counts=1'
    if (kw)   url += '&q='+encodeURIComponent(kw)
    if (fkid) url += '&fakultas_id='+encodeURIComponent(fkid)
    if (prid) url += '&prodi_id='+encodeURIComponent(prid)

    const { data } = await api.get(url)
    const rows = data.data || data || []

    if(!rows.length){
      body.innerHTML = `<tr><td colspan="7" class="text-center text-muted p-4">Tidak ada data</td></tr>`
      return
    }

    body.innerHTML = rows.map(r=>{
      const prodi = escapeHtml(r.nama_prodi ?? r.prodi?.nama_prodi ?? '-')
      const fak   = escapeHtml(r.nama_fakultas ?? r.prodi?.fakultas?.nama_fakultas ?? '-')
      return `
        <tr>
          <td>${escapeHtml(r.kode_cpl)}</td>
          <td>${escapeHtml(r.kategori || '-')}</td>
          <td>${prodi}</td>
          <td>${fak}</td>
          <td>${escapeHtml(r.deskripsi || '-')}</td>
          <td class="d-flex flex-wrap gap-2">${actions(r)}</td>
        </tr>
      `
    }).join('')
  }catch(err){
    if (isAuthError(err)) {
      auth.clear(); return window.location.replace(LOGIN_URL)
    }
    console.error('Gagal load list:', err?.response?.data || err.message)
    body.innerHTML = `<tr><td colspan="7" class="text-center text-danger p-4">Gagal memuat data CPL</td></tr>`
  }
}

$('#btnCari')?.addEventListener('click', loadList)
$('#q')?.addEventListener('keydown', e=>{ if(e.key==='Enter') loadList() })

// delete
$('#cplBody')?.addEventListener('click', async (e)=>{
  const btn = e.target.closest('button[data-act="del"]')
  if(!btn) return
  const kode = btn.dataset.kode
  if(!confirm(`Hapus CPL ${kode}?`)) return
  try{
    await api.delete(`/cpl/${encodeURIComponent(kode)}`)
    await loadList()
  }catch(err){
    if (isAuthError(err)) {
      auth.clear(); return window.location.replace(LOGIN_URL)
    }
    alert(err?.response?.data?.message || err.message)
  }
})

;(async function init(){
  await loadMe()       // ini sendiri sudah handle 401/419 → logout
  await loadMasters()  // kalau gagal non-auth, tidak logout
  await loadList()     // kalau gagal non-auth, tidak logout
})()

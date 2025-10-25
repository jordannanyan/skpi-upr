// resources/js/admin/pages/ta/index.js
import { api } from '../../../services/api'
import { auth } from '../../../services/auth'

// util
const $ = s => document.querySelector(s)
const escapeHtml = s => String(s ?? '').replace(/[&<>"']/g, m => ({
  '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'
}[m]))

// getters tampilan
const getNamaMhs = (r) => r?.nama_mhs ?? r?.mhs?.nama_mahasiswa ?? r?.mahasiswa?.nama_mahasiswa ?? '-'
const getProdi   = (r) => r?.nama_prodi ?? r?.prodi?.nama_prodi ?? r?.mhs?.prodi?.nama_prodi ?? r?.mahasiswa?.prodi?.nama_prodi ?? '-'
const getFak     = (r) => r?.nama_fakultas ?? r?.prodi?.fakultas?.nama_fakultas ?? r?.mhs?.prodi?.fakultas?.nama_fakultas ?? r?.mahasiswa?.prodi?.fakultas?.nama_fakultas ?? '-'

// hitung jumlah kolom (thead sudah ditambah Prodi & Fakultas → 8 kolom)
const COLS = (() => document.querySelectorAll('table thead th').length || 8)()

let me = null
let role = 'AdminJurusan' // default fallback
const bridge = document.getElementById('bridge')
const ADMIN_URL = bridge?.dataset?.adminUrl || '/admin'

// elements
const body = $('#taBody')
const inpKw = $('#taKw')
const inpNim = $('#taNim')
const selFak = $('#taFak')
const selPro = $('#taProdi')

async function loadMe(){
  const { data } = await api.get('/me')
  me = data
  role = data.role
  // hanya AdminJurusan/Kajur/SuperAdmin yang boleh tambah
  const canCreate = ['AdminJurusan','Kajur','SuperAdmin'].includes(role)
  if (!canCreate) $('#btnGoCreate')?.classList.add('d-none')
}

async function loadMasters(){
  const [f,p] = await Promise.all([
    api.get('/fakultas?per_page=100'),
    api.get('/prodi?per_page=200'),
  ])
  const faks = f.data.data || f.data
  const pros = p.data.data || p.data

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

  // filter prodi by fakultas (client-side)
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
}

async function loadTa(pageWant=1){
  body.innerHTML = `<tr><td colspan="${COLS}" class="text-center text-muted p-4">Memuat…</td></tr>`

  let url = `/ta?per_page=50&page=${pageWant}`
  const kw  = (inpKw?.value || '').trim()
  const nim = (inpNim?.value || '').trim()
  const fkid = selFak?.value || ''
  const prid = selPro?.value || ''

  if (kw)  url += `&q=${encodeURIComponent(kw)}`
  if (nim) url += `&nim=${encodeURIComponent(nim)}`
  if (fkid) url += `&fakultas_id=${encodeURIComponent(fkid)}`
  if (prid) url += `&prodi_id=${encodeURIComponent(prid)}`

  const { data } = await api.get(url)
  const rows = data.data || data
  const meta = data.meta || {}

  if (!rows.length){
    body.innerHTML = `<tr><td colspan="${COLS}" class="text-center text-muted p-4">Tidak ada data</td></tr>`
    return
  }

  body.innerHTML = rows.map(r => {
    const nama = escapeHtml(getNamaMhs(r))
    const prodi = escapeHtml(getProdi(r))
    const fak = escapeHtml(getFak(r))
    const kategori = escapeHtml(r.kategori ?? r.kategori_ta ?? '-')
    const judul = escapeHtml(r.judul ?? '-')
    return `
      <tr>
        <td>${r.id}</td>
        <td><code>${escapeHtml(r.nim ?? '-')}</code></td>
        <td>${nama}</td>
        <td>${prodi}</td>
        <td>${fak}</td>
        <td>${kategori}</td>
        <td>${judul}</td>
        <td class="d-flex flex-wrap gap-2">
          <a class="btn btn-sm btn-outline-primary" href="${ADMIN_URL}/ta/${r.id}/edit">Edit</a>
          <button class="btn btn-sm btn-outline-danger" data-act="del" data-id="${r.id}">Hapus</button>
        </td>
      </tr>
    `
  }).join('')
}

$('#taCari')?.addEventListener('click', ()=>loadTa(1))
inpKw?.addEventListener('keydown', (e)=>{ if(e.key==='Enter') loadTa(1) })
inpNim?.addEventListener('keydown', (e)=>{ if(e.key==='Enter') loadTa(1) })

// handle delete dari list
$('#taBody')?.addEventListener('click', async (e)=>{
  const btn = e.target.closest('button[data-act="del"]')
  if(!btn) return
  const id = btn.dataset.id
  if (!confirm('Hapus Tugas Akhir ini?')) return
  try{
    await api.delete(`/ta/${id}`)
    await loadTa()
  }catch(err){
    alert(err?.response?.data?.message || err.message)
  }
})

;(async function init(){
  try{
    await loadMe()
    await loadMasters()
    await loadTa(1)
  }catch(err){
    auth.clear()
    window.location.replace(bridge?.dataset?.loginUrl || '/login')
  }
})()

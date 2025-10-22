import { api } from '../../../services/api'
import { auth } from '../../../services/auth'

// util
const $ = s => document.querySelector(s)
const $$ = s => document.querySelectorAll(s)

let me = null
let role = 'AdminJurusan' // default fallback
const bridge = document.getElementById('bridge')
const ADMIN_URL = bridge?.dataset?.adminUrl || '/admin'

async function loadMe(){
  const { data } = await api.get('/me')
  me = data
  role = data.role
  // hanya AdminJurusan/Kajur/SuperAdmin yang boleh tambah
  const canCreate = ['AdminJurusan','Kajur','SuperAdmin'].includes(role)
  if (!canCreate) $('#btnGoCreate')?.classList.add('d-none')
}

async function loadTa(){
  let url = '/ta?per_page=50'
  const kw  = $('#taKw')?.value?.trim()
  const nim = $('#taNim')?.value?.trim()
  if (kw)  url += '&q=' + encodeURIComponent(kw)
  if (nim) url += '&nim=' + encodeURIComponent(nim)

  const { data } = await api.get(url)
  const rows = data.data || data
  const body = $('#taBody'); body.innerHTML = ''
  if (!rows.length){
    body.innerHTML = `<tr><td colspan="5" class="text-center text-muted p-4">Tidak ada data</td></tr>`
    return
  }

  rows.forEach(r => {
    const tr = document.createElement('tr')
    tr.innerHTML = `
      <td>${r.id}</td>
      <td>${r.nim}</td>
      <td>${r.kategori || '-'}</td>
      <td>${r.judul || '-'}</td>
      <td class="d-flex flex-wrap gap-2">
        <a class="btn btn-sm btn-outline-primary" href="${ADMIN_URL}/ta/${r.id}/edit">Edit</a>
        <button class="btn btn-sm btn-outline-danger" data-act="del" data-id="${r.id}">Hapus</button>
      </td>
    `
    body.appendChild(tr)
  })
}

$('#taCari')?.addEventListener('click', loadTa)
$('#taKw')?.addEventListener('keydown', (e)=>{ if(e.key==='Enter') loadTa() })
$('#taNim')?.addEventListener('keydown', (e)=>{ if(e.key==='Enter') loadTa() })

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
    await loadTa()
  }catch(err){
    auth.clear()
    window.location.replace(bridge?.dataset?.loginUrl || '/login')
  }
})()

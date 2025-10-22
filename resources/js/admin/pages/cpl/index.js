import { api } from '../../../services/api'
import { auth } from '../../../services/auth'

const $ = s => document.querySelector(s)
let role = null

async function loadMe(){
  const { data } = await api.get('/me')
  role = data.role
  // hanya SA (atau role yang kamu izinkan) yang boleh create/delete CPL
  if (!['SuperAdmin'].includes(role)) $('#btnGoCreate')?.classList.add('d-none')
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
  const kw = $('#q')?.value?.trim()
  let url = '/cpl?per_page=100&with_counts=1'
  if (kw) url += '&q='+encodeURIComponent(kw)
  const { data } = await api.get(url)
  const rows = data.data || data
  const body = $('#cplBody'); body.innerHTML=''
  if(!rows.length){
    body.innerHTML = `<tr><td colspan="5" class="text-center text-muted p-4">Tidak ada data</td></tr>`
    return
  }
  rows.forEach(r=>{
    const tr = document.createElement('tr')
    tr.innerHTML = `
      <td>${r.kode_cpl}</td>
      <td>${r.kategori || '-'}</td>
      <td>${r.deskripsi || '-'}</td>
      <td>${r.skor_count ?? 0}</td>
      <td class="d-flex flex-wrap gap-2">${actions(r)}</td>
    `
    body.appendChild(tr)
  })
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
  }catch(err){ alert(err?.response?.data?.message || err.message) }
})

;(async function init(){
  try{
    await loadMe()
    await loadList()
  }catch(err){
    auth.clear(); window.location.replace('/login')
  }
})()

import { api } from '../../../services/api'
import { auth } from '../../../services/auth'

const $ = s => document.querySelector(s)
const kode = window.__CPL_KODE__

let me=null, role=null

async function mustRole(){
  const { data } = await api.get('/me')
  me = data; role = data.role
  if (!['AdminJurusan','Kajur','SuperAdmin'].includes(role)) {
    alert('Hanya Admin Jurusan/Kajur yang boleh mengelola skor.')
    window.location.replace('/admin/cpl')
    throw new Error('forbidden')
  }
}

async function loadFakultas(){
  const { data } = await api.get('/fakultas?per_page=100')
  const rows = data.data || data
  const sel = $('#selFak')
  sel.innerHTML = `<option value="">— Pilih Fakultas —</option>`
  rows.forEach(f=>{
    const o = document.createElement('option')
    o.value = f.id; o.textContent = f.nama_fakultas || `Fakultas ${f.id}`
    sel.appendChild(o)
  })
  if (me?.id_fakultas) { sel.value = String(me.id_fakultas); sel.dispatchEvent(new Event('change')) }
}

async function loadProdi(id_fak){
  $('#selProdi').disabled = true
  const { data } = await api.get('/prodi?per_page=200' + (id_fak?('&fakultas_id='+id_fak):''))
  const rows = data.data || data
  const sel = $('#selProdi')
  sel.innerHTML = `<option value="">— Pilih Prodi —</option>`
  rows.forEach(p=>{
    const o = document.createElement('option')
    o.value = p.id; o.textContent = p.nama_prodi || `Prodi ${p.id}`
    sel.appendChild(o)
  })
  $('#selProdi').disabled = false
  if (me?.id_prodi) { sel.value = String(me.id_prodi); sel.dispatchEvent(new Event('change')) }
}

async function loadMhs(id_prodi){
  $('#selNim').disabled = true
  const { data } = await api.get('/mahasiswa?per_page=200&prodi_id='+encodeURIComponent(id_prodi))
  const rows = data.data || data
  const sel = $('#selNim')
  sel.innerHTML = `<option value="">— Pilih NIM —</option>`
  rows.forEach(m=>{
    const o = document.createElement('option')
    o.value = m.nim; o.textContent = `${m.nim} — ${m.nama_mahasiswa||'-'}`
    sel.appendChild(o)
  })
  $('#selNim').disabled = false
}

async function loadSkorList(){
  // daftar skor untuk CPL ini (tanpa filter prodi/fakultas: tampilkan semua yg ada)
  const { data } = await api.get(`/cpl/${encodeURIComponent(kode)}/skor?per_page=200`)
  const rows = data.data || data
  const body = $('#skorBody'); body.innerHTML = ''
  if(!rows.length){
    body.innerHTML = `<tr><td colspan="4" class="text-center text-muted p-4">Belum ada skor</td></tr>`
    return
  }
  rows.forEach(r=>{
    const tr = document.createElement('tr')
    const nama = r.mahasiswa?.nama_mahasiswa || '-'
    tr.innerHTML = `
      <td>${r.nim}</td>
      <td>${nama}</td>
      <td>${r.skor}</td>
      <td>
        <button class="btn btn-sm btn-outline-danger" data-act="del" data-nim="${r.nim}">Hapus</button>
      </td>
    `
    body.appendChild(tr)
  })
}

$('#selFak')?.addEventListener('change', e=>{
  const idf = e.target.value
  $('#selProdi').innerHTML = `<option value="">— Pilih Prodi —</option>`
  $('#selNim').innerHTML   = `<option value="">— Pilih NIM —</option>`
  $('#selNim').disabled = true
  if (idf) loadProdi(idf)
})

$('#selProdi')?.addEventListener('change', e=>{
  const idp = e.target.value
  $('#selNim').innerHTML = `<option value="">— Pilih NIM —</option>`
  if (idp) loadMhs(idp)
})

$('#btnUpsert')?.addEventListener('click', async ()=>{
  const nim  = $('#selNim').value
  const skor = parseFloat($('#inpSkor').value)
  if(!nim || isNaN(skor)) return alert('Pilih NIM dan isi skor.')
  try{
    await api.post('/skor-cpl/upsert', { kode_cpl: kode, nim, skor })
    await loadSkorList()
    alert('Skor disimpan/diupdate.')
  }catch(err){ alert(err?.response?.data?.message || err.message) }
})

$('#skorBody')?.addEventListener('click', async (e)=>{
  const btn = e.target.closest('button[data-act="del"]')
  if(!btn) return
  const nim = btn.dataset.nim
  if(!confirm(`Hapus skor CPL ${kode} untuk NIM ${nim}?`)) return
  try{
    await api.delete(`/cpl/${encodeURIComponent(kode)}/skor/${encodeURIComponent(nim)}`)
    await loadSkorList()
  }catch(err){ alert(err?.response?.data?.message || err.message) }
})

;(async function init(){
  try{
    await mustRole()
    await loadFakultas()
    await loadSkorList()
  }catch(err){
    auth.clear(); window.location.replace('/login')
  }
})()

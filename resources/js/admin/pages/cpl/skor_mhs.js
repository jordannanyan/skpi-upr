import { api } from '../../../services/api'
import { auth } from '../../../services/auth'

const $ = s => document.querySelector(s)
const nim = document.querySelector('#vNim')?.value

async function loadList(){
  const { data } = await api.get(`/mahasiswa/${encodeURIComponent(nim)}/skor-cpl`)
  const rows = data || []
  const body = $('#mhsSkorBody'); body.innerHTML=''
  if(!rows.length){
    body.innerHTML = `<tr><td colspan="4" class="text-center text-muted p-4">Belum ada skor</td></tr>`
    return
  }
  rows.forEach(r=>{
    const tr = document.createElement('tr')
    tr.innerHTML = `
      <td>${r.kode_cpl}</td>
      <td>${r.cpl?.kategori || '-'}</td>
      <td>${r.cpl?.deskripsi || '-'}</td>
      <td>${r.skor}</td>
    `
    body.appendChild(tr)
  })
}

;(async function init(){
  try{
    // hanya butuh token valid
    await api.get('/me')
    await loadList()
  }catch(e){
    auth.clear(); window.location.replace('/login')
  }
})()

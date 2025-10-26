import { api } from '../../../services/api'
import { auth } from '../../../services/auth'

const $ = s => document.querySelector(s)
const id = window.__LAP_ID__

let me=null, role=null, row=null

// --- helpers status ---
const statusLc = () => String(row?.status ?? '').toLowerCase()

function badge(st){
  const s = String(st||'').toLowerCase()
  return s==='approved'   ? 'success'  :
         s==='rejected'   ? 'danger'   :
         s==='verified'   ? 'primary'  :
         s==='wakadek_ok' ? 'warning'  : 'secondary'
}

async function loadMe(){
  const { data } = await api.get('/me')
  me = data; role = data.role
}

function stepStatusIcon(done){ return done ? 'text-bg-success' : 'text-bg-secondary' }

function renderStepper(){
  const steps = [
    { key: 'submitted',  label: 'Pengajuan (Admin Jurusan)' },
    { key: 'verified',   label: 'Verifikasi (Kajur)' },
    { key: 'wakadek_ok', label: 'Persetujuan (Wakadek)' },
    { key: 'approved',   label: 'Persetujuan Akhir (Dekan) & Generate' },
  ]
  const s = statusLc()
  const idx = steps.findIndex(x => x.key === s)
  const doneUntil = (idx >= 0 ? idx : -1)
  const ul = $('#boxStepper')
  ul.innerHTML = steps.map((stp,i) => {
    const done = i <= doneUntil
    return `
      <li class="list-group-item d-flex align-items-center justify-content-between">
        <span>${stp.label}</span>
        <span class="badge ${stepStatusIcon(done)}">${done ? 'Selesai' : 'Menunggu'}</span>
      </li>
    `
  }).join('')
}

function renderRoleChecklist(){
  const box = $('#boxRoleChecklist')
  const info = $('#boxNextAction')

  info.classList.add('d-none')
  box.innerHTML = ''

  const s = statusLc()
  const needs = []

  if (role === 'Kajur') {
    if (s === 'submitted') {
      info.textContent = 'Anda perlu memverifikasi pengajuan ini.'
      info.classList.remove('d-none')
      needs.push('Periksa kelengkapan berkas dan data mahasiswa.')
      needs.push('Setujui atau tolak pengajuan.')
    } else {
      needs.push('Menunggu proses selanjutnya oleh Admin Fakultas / Wakadek / Dekan.')
    }
  }

  if (role === 'AdminFakultas') {
    if (s === 'verified' && (!row.no_pengesahan || !row.tgl_pengesahan)) {
      info.textContent = 'Anda perlu mengisi nomor dan tanggal pengesahan.'
      info.classList.remove('d-none')
      needs.push('Isi No Pengesahan dan Tgl Pengesahan.')
      needs.push('Tambahkan catatan jika diperlukan.')
      needs.push('Setelah tersimpan, Wakadek dapat memproses persetujuan.')
    } else if (s === 'verified') {
      needs.push('Nomor & tanggal pengesahan sudah diisi. Menunggu persetujuan Wakadek.')
    } else {
      needs.push('Tidak ada tindakan pada tahap ini.')
    }
  }

  if (role === 'Wakadek') {
    if (s === 'verified' && row.no_pengesahan && row.tgl_pengesahan) {
      info.textContent = 'Anda perlu menyetujui atau menolak pengajuan ini.'
      info.classList.remove('d-none')
      needs.push('Review pengesahan yang sudah diinput Admin Fakultas.')
      needs.push('Setujui atau tolak pengajuan.')
    } else if (s === 'verified') {
      needs.push('Menunggu Admin Fakultas mengisi nomor & tanggal pengesahan.')
    } else {
      needs.push('Tidak ada tindakan pada tahap ini.')
    }
  }

  if (role === 'Dekan') {
    if (s === 'wakadek_ok') {
      info.textContent = 'Anda perlu memberikan persetujuan akhir.'
      info.classList.remove('d-none')
      needs.push('Lakukan persetujuan akhir.')
      needs.push('Jika disetujui, sistem akan menghasilkan file SKPI final.')
    } else {
      needs.push('Tidak ada tindakan pada tahap ini.')
    }
  }

  if (needs.length === 0) {
    box.innerHTML = `<li class="text-muted">Tidak ada tindakan khusus.</li>`
  } else {
    box.innerHTML = needs.map(n => `<li>• ${n}</li>`).join('')
  }
}

async function loadDetail(){
  const { data } = await api.get(`/laporan-skpi/${id}`)
  row = data
  const s = statusLc()

  $('#vId').textContent = row.id
  $('#vNim').textContent = row.nim
  $('#vStatus').textContent = row.status   // tampilkan apa adanya dari API
  $('#vStatus').className = `badge text-bg-${badge(s)}`
  $('#vPengesahan').textContent = (row.no_pengesahan||'-') + ' / ' + (row.tgl_pengesahan||'-')
  $('#vCatatan').textContent = row.catatan_verifikasi || '-'

  // Nama/prodi/fakultas (dari accessor)
  $('#mNama').textContent  = row.nama_mhs || '-'
  $('#mProdi').textContent = row.nama_prodi || '-'
  $('#mFak').textContent   = row.nama_fakultas || '-'

  const fileArea = $('#vFileArea'); fileArea.innerHTML=''
  if (row.file_url) {
    const a = document.createElement('a')
    a.className='btn btn-outline-secondary btn-sm'
    a.href = row.file_url; a.target='_blank'
    a.textContent='Lihat File'
    fileArea.appendChild(a)

    // hanya role tertentu yang boleh regenerate saat approved
    if (['SuperAdmin','AdminFakultas','Dekan'].includes(role) && s==='approved') {
      const b = document.createElement('button')
      b.className='btn btn-outline-dark btn-sm'
      b.textContent='Generate Ulang'
      b.addEventListener('click', onRegenerate)
      fileArea.appendChild(b)
    }
  }

  // form pengesahan: AdminFakultas / SuperAdmin saat status 'verified'
  if (['AdminFakultas','SuperAdmin'].includes(role) && s==='verified') {
    $('#formPengesahan').classList.remove('d-none')
    $('#noPengesahan').value = row.no_pengesahan || ''
    $('#tglPengesahan').value = row.tgl_pengesahan || ''
    $('#catPengesahan').value = row.catatan_verifikasi || ''
  } else {
    $('#formPengesahan').classList.add('d-none')
  }

  // tombol aksi sesuai role + status
  const box = $('#boxActions'); box.innerHTML = ''
  if (role==='Kajur' && s==='submitted') {
    box.appendChild(btn('Setujui (Kajur)', 'success', onKajurApprove))
    box.appendChild(btn('Tolak (Kajur)', 'outline-danger', onKajurReject))
  }
  if (role==='Wakadek' && s==='verified' && row.no_pengesahan && row.tgl_pengesahan) {
    box.appendChild(btn('Setujui (Wakadek)', 'success', onWakadekApprove))
    box.appendChild(btn('Tolak (Wakadek)', 'outline-danger', onWakadekReject))
  }
  if (role==='Dekan' && s==='wakadek_ok') {
    box.appendChild(btn('Setujui (Dekan)', 'success', onDekanApprove))
    box.appendChild(btn('Tolak (Dekan)', 'outline-danger', onDekanReject))
  }

  renderStepper()
  renderRoleChecklist()

  $('#boxLoading').classList.add('d-none')
  $('#boxDetail').classList.remove('d-none')

  // Load data akademik
  await Promise.all([
    fetchCpl(row.nim),
    fetchTa(row.nim),
    fetchKp(row.nim),
    fetchSert(row.nim),
  ])
}

function btn(label, style, handler){
  const b = document.createElement('button')
  b.className = `btn btn-${style}`
  b.textContent = label
  b.addEventListener('click', handler)
  return b
}

/* ====== Data Akademik (CPL, TA, KP, Sertifikat) ====== */

async function fetchCpl(nim){
  const tb = $('#tblCplBody')
  tb.innerHTML = `<tr><td colspan="3" class="text-muted">Memuat…</td></tr>`
  try{
    const { data } = await api.get(`/mahasiswa/${encodeURIComponent(nim)}/skor-cpl`)
    const rows = Array.isArray(data) ? data : (data?.data || [])
    if (!rows.length) {
      tb.innerHTML = `<tr><td colspan="3" class="text-muted">Belum ada data</td></tr>`
      return
    }
    tb.innerHTML = rows.map(r => `
      <tr>
        <td><code>${r.kode_cpl ?? r.kode ?? '-'}</code></td>
        <td>${esc(r.nama_cpl ?? r.nama ?? '-')}</td>
        <td>${fmtNum(r.skor ?? r.nilai ?? r.value)}</td>
      </tr>
    `).join('')
  }catch(e){
    tb.innerHTML = `<tr><td colspan="3" class="text-danger">Gagal memuat data CPL</td></tr>`
  }
}

async function fetchTa(nim){
  const tb = $('#tblTaBody')
  tb.innerHTML = `<tr><td colspan="3" class="text-muted">Memuat…</td></tr>`
  try{
    const { data } = await api.get(`/mahasiswa/${encodeURIComponent(nim)}/tugas-akhir`)
    const rows = Array.isArray(data) ? data : (data?.data || [])
    if (!rows.length) {
      tb.innerHTML = `<tr><td colspan="3" class="text-muted">Belum ada data</td></tr>`
      return
    }
    tb.innerHTML = rows.map(r => `
      <tr>
        <td>${esc(r.judul ?? '-')}</td>
        <td>${esc(r.tahun ?? '-')}</td>
        <td>${esc(r.nilai ?? '-')}</td>
      </tr>
    `).join('')
  }catch(e){
    tb.innerHTML = `<tr><td colspan="3" class="text-danger">Gagal memuat data TA</td></tr>`
  }
}

async function fetchKp(nim){
  const tb = $('#tblKpBody')
  tb.innerHTML = `<tr><td colspan="3" class="text-muted">Memuat…</td></tr>`
  try{
    const { data } = await api.get(`/mahasiswa/${encodeURIComponent(nim)}/kerja-praktek`)
    const rows = Array.isArray(data) ? data : (data?.data || [])
    if (!rows.length) {
      tb.innerHTML = `<tr><td colspan="3" class="text-muted">Belum ada data</td></tr>`
      return
    }
    tb.innerHTML = rows.map(r => `
      <tr>
        <td>${esc(r.judul ?? r.lokasi ?? '-')}</td>
        <td>${esc(r.tahun ?? '-')}</td>
        <td>${esc(r.nilai ?? '-')}</td>
      </tr>
    `).join('')
  }catch(e){
    tb.innerHTML = `<tr><td colspan="3" class="text-danger">Gagal memuat data KP</td></tr>`
  }
}

async function fetchSert(nim){
  const tb = $('#tblSertBody')
  tb.innerHTML = `<tr><td colspan="4" class="text-muted">Memuat…</td></tr>`
  try{
    const { data } = await api.get(`/mahasiswa/${encodeURIComponent(nim)}/sertifikat`)
    const rows = Array.isArray(data) ? data : (data?.data || [])
    if (!rows.length) {
      tb.innerHTML = `<tr><td colspan="4" class="text-muted">Belum ada data</td></tr>`
      return
    }
    tb.innerHTML = rows.map(r => `
      <tr>
        <td>${esc(r.nama_sertifikat ?? r.nama ?? '-')}</td>
        <td>${esc(r.penyelenggara ?? r.provider ?? '-')}</td>
        <td>${esc(r.tahun ?? '-')}</td>
        <td>${esc(r.nomor ?? r.no_sertifikat ?? '-')}</td>
      </tr>
    `).join('')
  }catch(e){
    tb.innerHTML = `<tr><td colspan="4" class="text-danger">Gagal memuat data Sertifikat</td></tr>`
  }
}

function esc(s){ return String(s ?? '').replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m])) }
function fmtNum(x){ const n = Number(x); return Number.isFinite(n) ? n.toFixed(2) : (x ?? '-') }

/* ========== Actions ========== */

async function onKajurApprove(){
  try{
    await api.post(`/laporan-skpi/${id}/verify`, { approve:true, note:null })
    await loadDetail()
    alert('Disetujui oleh Kajur.')
  }catch(err){ alert(err?.response?.data?.message || err.message) }
}
async function onKajurReject(){
  const note = prompt('Alasan penolakan (opsional)?') || null
  try{
    await api.post(`/laporan-skpi/${id}/verify`, { approve:false, note })
    await loadDetail()
  }catch(err){ alert(err?.response?.data?.message || err.message) }
}

$('#btnSimpanPengesahan')?.addEventListener('click', async ()=>{
  const no = $('#noPengesahan').value.trim()
  const tgl= $('#tglPengesahan').value.trim()
  const cat= $('#catPengesahan').value.trim() || null
  if (!no || !tgl) return alert('Isi nomor & tanggal pengesahan.')
  try{
    await api.post(`/laporan-skpi/${id}/pengesahan`, {
      no_pengesahan: no, tgl_pengesahan: tgl, catatan_verifikasi: cat
    })
    await loadDetail()
    alert('Pengesahan disimpan.')
  }catch(err){ alert(err?.response?.data?.message || err.message) }
})

async function onWakadekApprove(){
  try{
    await api.post(`/laporan-skpi/${id}/wakadek`, { approve:true })
    await loadDetail()
    alert('Disetujui Wakadek.')
  }catch(err){ alert(err?.response?.data?.message || err.message) }
}
async function onWakadekReject(){
  const note = prompt('Alasan penolakan (opsional)?') || null
  try{
    await api.post(`/laporan-skpi/${id}/wakadek`, { approve:false, note })
    await loadDetail()
  }catch(err){ alert(err?.response?.data?.message || err.message) }
}

async function onDekanApprove(){
  try{
    await api.post(`/laporan-skpi/${id}/dekan`, { approve:true })
    await loadDetail()
    alert('Disetujui Dekan & file dibuat.')
  }catch(err){ alert(err?.response?.data?.message || err.message) }
}
async function onDekanReject(){
  const note = prompt('Alasan penolakan (opsional)?') || null
  try{
    await api.post(`/laporan-skpi/${id}/dekan`, { approve:false, note })
    await loadDetail()
  }catch(err){ alert(err?.response?.data?.message || err.message) }
}

async function onRegenerate(){
  try{
    const { data } = await api.post(`/laporan-skpi/${id}/regenerate`)
    if (data.file_url) window.open(data.file_url,'_blank')
    await loadDetail()
  }catch(err){ alert(err?.response?.data?.message || err.message) }
}

/* ========== Boot ========== */
;(async function init(){
  try{
    await loadMe()
    await loadDetail()
  }catch(err){
    auth.clear()
    window.location.replace('/login')
  }
})()

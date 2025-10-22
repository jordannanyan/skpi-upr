@extends('layouts.admin')

@section('title','Fakultas — SKPI UPR')
@section('pageTitle','Fakultas')

@section('content')
<div class="table-responsive">
  <table class="table table-hover align-middle">
    <thead class="table-light">
      <tr>
        <th style="width:100px;">ID</th>
        <th>Nama Fakultas</th>
        <th>Dekan</th>
      </tr>
    </thead>
    <tbody id="fakBody">
      <tr><td colspan="3" class="text-center text-muted p-4">Memuat…</td></tr>
    </tbody>
  </table>
</div>
@endsection

@push('scripts')
<script>
(function(){
  const API   = document.getElementById('bridge').dataset.apiBase
  const token = localStorage.getItem('auth_token') || ''
  axios.defaults.baseURL = API
  if (token) axios.defaults.headers.common['Authorization'] = `Bearer ${token}`

  const body = document.getElementById('fakBody')

  async function load(){
    const {data} = await axios.get('/fakultas?per_page=100')
    const rows = data.data || data
    if (!rows.length){
      body.innerHTML = `<tr><td colspan="3" class="text-center text-muted p-4">Tidak ada data</td></tr>`
      return
    }
    body.innerHTML = ''
    rows.forEach(r=>{
      const tr = document.createElement('tr')
      tr.innerHTML = `<td>${r.id}</td><td>${r.nama_fakultas||'-'}</td><td>${r.nama_dekan||'-'}</td>`
      body.appendChild(tr)
    })
  }

  load()
})();
</script>
@endpush

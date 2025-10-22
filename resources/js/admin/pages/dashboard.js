import { api } from '../../services/api'

async function loadStats() {
  try {
    const [m,p,f] = await Promise.all([
      api.get('/mahasiswa?per_page=1'),
      api.get('/prodi?per_page=1'),
      api.get('/fakultas?per_page=1'),
    ])
    const getTotal = (res) => res?.data?.total ?? res?.data?.meta?.total ?? res?.data?.data?.length ?? '—'
    document.getElementById('statMhs').textContent  = getTotal(m)
    document.getElementById('statProdi').textContent= getTotal(p)
    document.getElementById('statFak').textContent  = getTotal(f)
  } catch {
    // biarkan default "—"
  }
}

loadStats()

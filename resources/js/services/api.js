import axios from 'axios'
import { auth } from './auth'

// Base URL ke /api (sesuai laravel route)
const api = axios.create({
  baseURL: '/api',
  timeout: 30000,
  headers: { 'Accept': 'application/json' }
})

// Inject bearer token kalau ada
api.interceptors.request.use((config) => {
  const t = auth.get()
  if (t) config.headers.Authorization = `Bearer ${t}`
  return config
})

export { api }

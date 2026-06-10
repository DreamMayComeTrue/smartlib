/**
 * Centralised Axios client for SmartLib.
 *
 * Import this in any component:
 *   import http from '@/api/http'
 *   const { data } = await http.get('/api/books')
 *
 * - baseURL points at the Laragon-served Slim API
 * - request interceptor adds the JWT from localStorage if present
 * - response interceptor catches 401s and bounces the user to /login
 */

import axios from 'axios'
import router from '@/router'

const http = axios.create({
  baseURL: import.meta.env.VITE_API_BASE_URL || 'http://smartlib.test',
  timeout: 10_000,
  headers: { 'Content-Type': 'application/json' }
})

// Attach the JWT (if any) to every outgoing request
http.interceptors.request.use(config => {
  const token = localStorage.getItem('token')
  if (token) {
    config.headers.Authorization = `Bearer ${token}`
  }
  return config
})

// If the server says the token is gone/expired, send the user back to login
http.interceptors.response.use(
  res => res,
  err => {
    if (err.response?.status === 401) {
      localStorage.removeItem('token')
      localStorage.removeItem('user')
      // Avoid redirect loops if we're already on /login
      if (router.currentRoute.value.path !== '/login') {
        router.push('/login')
      }
    }
    return Promise.reject(err)
  }
)

export default http

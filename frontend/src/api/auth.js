/**
 * Shared auth state — a single reactive source of truth for the logged-in user.
 *
 * Why this exists:
 * localStorage is not reactive. If you do `localStorage.setItem('user', …)`
 * during login, components reading from localStorage via `computed()` won't
 * re-render until the user manually refreshes the page.
 *
 * This module wraps the user + token in Vue `ref`s so any component that
 * imports `user` (or `isAdmin`) automatically re-renders on login/logout.
 * localStorage is updated alongside the ref so the session survives reloads.
 */

import { ref, computed } from 'vue'

// ── Internal state — initialised from localStorage at module load ──
function readUser () {
  try {
    return JSON.parse(localStorage.getItem('user') || 'null')
  } catch {
    return null
  }
}

const _user  = ref(readUser())
const _token = ref(localStorage.getItem('token'))

// ── Public reactive getters ──
export const user    = computed(() => _user.value)
export const token   = computed(() => _token.value)
export const isAuth  = computed(() => !!_token.value)
export const isAdmin = computed(() => _user.value?.role === 'admin')

// ── Mutators ──
/**
 * Call this after a successful POST /api/members/login.
 * @param {object} userData  the `user` object returned by the server
 * @param {string} tokenStr  the JWT returned by the server
 */
export function login (userData, tokenStr) {
  _user.value  = userData
  _token.value = tokenStr
  localStorage.setItem('user',  JSON.stringify(userData))
  localStorage.setItem('token', tokenStr)
}

/**
 * Clears local session. Does NOT call the backend — JWTs are stateless,
 * so logout is purely a client-side concern.
 */
export function logout () {
  _user.value  = null
  _token.value = null
  localStorage.removeItem('user')
  localStorage.removeItem('token')
}

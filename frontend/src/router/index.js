/**
 * Vue Router — defines every page in SmartLib and gates them
 * with a global beforeEach guard that checks JWT + role claims.
 */

import { createRouter, createWebHistory } from 'vue-router'

const routes = [
  {
    path: '/',
    component: () => import('@/views/BookListView.vue'),
    meta: { title: 'Catalogue' }
  },
  {
    path: '/login',
    component: () => import('@/views/LoginView.vue'),
    meta: { title: 'Login', hideNav: true }
  },
  {
    path: '/register',
    component: () => import('@/views/RegisterView.vue'),
    meta: { title: 'Register', hideNav: true }
  },
  {
    path: '/borrow/:id',
    component: () => import('@/views/BorrowView.vue'),
    meta: { title: 'Borrow', requiresAuth: true }
  },
  {
    path: '/my-borrows',
    component: () => import('@/views/ReturnView.vue'),
    meta: { title: 'My Borrows', requiresAuth: true }
  },
  {
    path: '/admin',
    component: () => import('@/views/AdminView.vue'),
    meta: { title: 'Admin', requiresAuth: true, requiresAdmin: true }
  },
  // Catch-all → home
  { path: '/:pathMatch(.*)*', redirect: '/' }
]

const router = createRouter({
  history: createWebHistory(),
  routes
})

// Decode the role out of the user object we stash at login time.
// (We never *trust* this client-side — the backend still enforces it on every request.)
function currentUser () {
  try {
    return JSON.parse(localStorage.getItem('user') || 'null')
  } catch {
    return null
  }
}

router.beforeEach((to) => {
  const token = localStorage.getItem('token')
  const user  = currentUser()

  if (to.meta.requiresAuth && !token) {
    return { path: '/login', query: { redirect: to.fullPath } }
  }
  if (to.meta.requiresAdmin && user?.role !== 'admin') {
    return { path: '/' }
  }

  document.title = to.meta.title ? `${to.meta.title} · SmartLib` : 'SmartLib'
})

export default router

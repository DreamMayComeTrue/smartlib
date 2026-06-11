<template>
  <div class="app-shell">
    <!-- Top navigation -->
    <header v-if="!$route.meta.hideNav" class="navbar">
      <RouterLink to="/" class="brand">
        <span class="brand-mark">📚</span>
        <span class="brand-name">SmartLib</span>
      </RouterLink>

      <nav class="nav-links">
        <RouterLink to="/">Catalogue</RouterLink>
        <RouterLink v-if="user" to="/my-borrows">My Borrows</RouterLink>
        <RouterLink v-if="user?.role === 'admin'" to="/admin">Admin</RouterLink>
      </nav>

      <div class="nav-auth">
        <template v-if="user">
          <RouterLink to="/account" class="user-name" title="Change password">{{ user.name }}</RouterLink>
          <button class="btn btn-ghost" @click="logout">Log out</button>
        </template>
        <template v-else>
          <RouterLink to="/login" class="btn btn-ghost">Log in</RouterLink>
          <RouterLink to="/register" class="btn btn-primary">Sign up</RouterLink>
        </template>
      </div>
    </header>

    <!-- Routed view -->
    <main class="app-main">
      <RouterView />
    </main>

    <!-- Footer -->
    <footer v-if="!$route.meta.hideNav" class="app-footer">
      <small>SmartLib · Polar Bear · SCSM2223</small>
    </footer>
  </div>
</template>

<script setup>
import { useRouter, RouterLink, RouterView } from 'vue-router'
import { user, logout as clearAuth } from '@/api/auth'

const router = useRouter()

function logout () {
  clearAuth()
  router.push('/login')
}
</script>

<style scoped>
.app-shell {
  min-height: 100vh;
  display: flex;
  flex-direction: column;
}

.navbar {
  display: flex;
  align-items: center;
  gap: 1.5rem;
  padding: 0.85rem 1.5rem;
  background: var(--surface);
  border-bottom: 1px solid var(--border);
  box-shadow: 0 2px 4px rgba(0,0,0,0.03);
  position: sticky;
  top: 0;
  z-index: 10;
}

.brand {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-weight: 700;
  font-size: 1.15rem;
  text-decoration: none;
  color: var(--text);
}
.brand-mark { font-size: 1.5rem; }

.nav-links {
  display: flex;
  gap: 1.2rem;
  flex: 1;
}
.nav-links a {
  text-decoration: none;
  color: var(--text-muted);
  font-weight: 500;
  padding: 0.4rem 0;
  border-bottom: 2px solid transparent;
  transition: color 0.15s, border-color 0.15s;
}
.nav-links a:hover { color: var(--text); }
.nav-links a.router-link-exact-active {
  color: var(--primary);
  border-bottom-color: var(--primary);
}

.nav-auth {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}
.user-name {
  color: var(--text-muted);
  font-size: 0.9rem;
  text-decoration: none;
  padding: 0.3rem 0.5rem;
  border-radius: var(--radius);
  transition: background 0.15s, color 0.15s;
}
.user-name:hover {
  background: var(--surface-muted);
  color: var(--text);
}

.app-main {
  flex: 1;
  padding: 1.5rem;
  max-width: 1200px;
  width: 100%;
  margin: 0 auto;
}

.app-footer {
  text-align: center;
  padding: 1rem;
  color: var(--text-muted);
  border-top: 1px solid var(--border);
}

@media (max-width: 640px) {
  .navbar { flex-wrap: wrap; gap: 0.75rem; }
  .nav-links { order: 3; width: 100%; justify-content: center; }
  .app-main { padding: 1rem; }
}
</style>

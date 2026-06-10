<template>
  <section class="auth-page">
    <div class="auth-card card">
      <div class="brand-row">
        <span class="brand-mark">📚</span>
        <h1>Welcome back</h1>
      </div>
      <p class="muted">Log in to borrow books and view your history.</p>

      <form @submit.prevent="login">
        <div class="form-group">
          <label for="email">Email</label>
          <input
            id="email"
            v-model="email"
            type="email"
            class="form-input"
            autocomplete="email"
            required
          />
        </div>

        <div class="form-group">
          <label for="password">Password</label>
          <input
            id="password"
            v-model="password"
            type="password"
            class="form-input"
            autocomplete="current-password"
            required
          />
        </div>

        <div v-if="error" class="alert alert-error">{{ error }}</div>

        <button
          type="submit"
          class="btn btn-primary"
          style="width:100%"
          :disabled="loading"
        >
          {{ loading ? 'Logging in…' : 'Log in' }}
        </button>
      </form>

      <p class="register-link">
        Don't have an account?
        <RouterLink to="/register">Create one</RouterLink>
      </p>
    </div>
  </section>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter, useRoute, RouterLink } from 'vue-router'
import http from '@/api/http'

const router = useRouter()
const route  = useRoute()

const email    = ref('')
const password = ref('')
const error    = ref('')
const loading  = ref(false)

async function login () {
  error.value = ''
  loading.value = true
  try {
    const { data } = await http.post('/api/members/login', {
      email:    email.value,
      password: password.value
    })
    localStorage.setItem('token', data.token)
    localStorage.setItem('user',  JSON.stringify(data.user))
    router.push(route.query.redirect || '/')
  } catch (err) {
    error.value = err.response?.data?.message ?? 'Login failed. Please try again.'
  } finally {
    loading.value = false
  }
}
</script>

<style scoped>
.auth-page {
  display: flex;
  align-items: center;
  justify-content: center;
  min-height: 80vh;
}
.auth-card {
  width: 100%;
  max-width: 420px;
}
.brand-row {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin-bottom: 0.5rem;
}
.brand-mark { font-size: 1.75rem; }
.register-link {
  text-align: center;
  margin-top: 1rem;
  font-size: 0.9rem;
  color: var(--text-muted);
}
.register-link a { color: var(--primary); text-decoration: none; }
.register-link a:hover { text-decoration: underline; }
</style>

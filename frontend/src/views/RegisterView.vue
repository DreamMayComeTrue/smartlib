<template>
  <section class="auth-page">
    <div class="auth-card card">
      <h1>Create an account</h1>
      <p class="muted">Sign up to start borrowing books.</p>

      <form @submit.prevent="register">
        <div class="form-group">
          <label for="name">Full name</label>
          <input id="name" v-model="form.name" type="text" class="form-input" required />
        </div>

        <div class="form-group">
          <label for="email">Email</label>
          <input id="email" v-model="form.email" type="email" class="form-input" required />
        </div>

        <div class="form-group">
          <label for="password">Password</label>
          <input
            id="password"
            v-model="form.password"
            type="password"
            class="form-input"
            minlength="8"
            required
          />
          <p class="muted hint">At least 8 characters.</p>
        </div>

        <div v-if="error"   class="alert alert-error">{{ error }}</div>
        <div v-if="success" class="alert alert-success">{{ success }}</div>

        <button type="submit" class="btn btn-primary" style="width:100%" :disabled="loading">
          {{ loading ? 'Creating account…' : 'Sign up' }}
        </button>
      </form>

      <p class="register-link">
        Already have an account?
        <RouterLink to="/login">Log in</RouterLink>
      </p>
    </div>
  </section>
</template>

<script setup>
import { reactive, ref } from 'vue'
import { useRouter, RouterLink } from 'vue-router'
import http from '@/api/http'

const router = useRouter()
const form = reactive({ name: '', email: '', password: '' })
const error   = ref('')
const success = ref('')
const loading = ref(false)

async function register () {
  error.value   = ''
  success.value = ''
  loading.value = true
  try {
    await http.post('/api/members/register', form)
    success.value = 'Account created. Redirecting to login…'
    setTimeout(() => router.push('/login'), 1200)
  } catch (err) {
    error.value = err.response?.data?.message ?? 'Registration failed.'
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
.auth-card { width: 100%; max-width: 420px; }
.hint { font-size: 0.8rem; margin-top: 0.25rem; }
.register-link {
  text-align: center;
  margin-top: 1rem;
  font-size: 0.9rem;
  color: var(--text-muted);
}
.register-link a { color: var(--primary); text-decoration: none; }
.register-link a:hover { text-decoration: underline; }
</style>

<template>
  <section class="account-page">
    <div class="account-card card">
      <h1>Change password</h1>
      <p class="muted">Update the password you use to log in.</p>

      <form @submit.prevent="submit">
        <div class="form-group">
          <label for="current">Current password</label>
          <input
            id="current"
            v-model="form.current"
            type="password"
            class="form-input"
            autocomplete="current-password"
            required
          />
        </div>

        <div class="form-group">
          <label for="new">New password</label>
          <input
            id="new"
            v-model="form.next"
            type="password"
            class="form-input"
            autocomplete="new-password"
            minlength="8"
            required
          />
          <p class="muted hint">At least 8 characters.</p>
        </div>

        <div class="form-group">
          <label for="confirm">Confirm new password</label>
          <input
            id="confirm"
            v-model="form.confirm"
            type="password"
            class="form-input"
            autocomplete="new-password"
            minlength="8"
            required
          />
          <p
            v-if="form.confirm && !confirmsMatch"
            class="muted hint mismatch"
          >Passwords don't match.</p>
        </div>

        <div v-if="error"   class="alert alert-error">{{ error }}</div>
        <div v-if="success" class="alert alert-success">{{ success }}</div>

        <button
          type="submit"
          class="btn btn-primary"
          style="width:100%"
          :disabled="loading || !confirmsMatch"
        >
          {{ loading ? 'Updating…' : 'Update password' }}
        </button>
      </form>

      <RouterLink to="/" class="back-link">← Back to catalogue</RouterLink>
    </div>
  </section>
</template>

<script setup>
import { reactive, ref, computed } from 'vue'
import { RouterLink } from 'vue-router'
import http from '@/api/http'

const form = reactive({ current: '', next: '', confirm: '' })
const error   = ref('')
const success = ref('')
const loading = ref(false)

// Client-side guard — backend also rejects mismatched passwords (defence in depth)
const confirmsMatch = computed(() =>
  form.confirm === '' || form.confirm === form.next
)

async function submit () {
  error.value   = ''
  success.value = ''

  if (form.next !== form.confirm) {
    error.value = "New password and confirmation don't match."
    return
  }
  if (form.next === form.current) {
    error.value = 'New password must be different from the current one.'
    return
  }

  loading.value = true
  try {
    await http.post('/api/members/password', {
      current_password: form.current,
      new_password:     form.next
    })
    success.value = 'Password updated. You can keep using your current session.'
    form.current = ''
    form.next    = ''
    form.confirm = ''
  } catch (err) {
    error.value = err.response?.data?.message ?? 'Failed to update password.'
  } finally {
    loading.value = false
  }
}
</script>

<style scoped>
.account-page {
  display: flex;
  align-items: center;
  justify-content: center;
  min-height: 70vh;
}
.account-card {
  width: 100%;
  max-width: 420px;
}
.hint { font-size: 0.8rem; margin-top: 0.25rem; }
.mismatch { color: var(--danger); }
.back-link {
  display: inline-block;
  margin-top: 1rem;
  text-align: center;
  width: 100%;
  font-size: 0.9rem;
  color: var(--text-muted);
  text-decoration: none;
}
.back-link:hover { color: var(--text); }
</style>

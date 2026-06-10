<template>
  <section class="borrow-view">
    <RouterLink to="/" class="back-link">← Back to catalogue</RouterLink>

    <LoadingSpinner v-if="loading" label="Loading book…" />

    <div v-else-if="error" class="alert alert-error">{{ error }}</div>

    <div v-else-if="success" class="card stack">
      <h2>You're all set!</h2>
      <p>You've borrowed <strong>{{ book.title }}</strong>.</p>
      <p class="muted">Due date: <strong>{{ success.due_date }}</strong></p>
      <div class="flex">
        <RouterLink to="/my-borrows" class="btn btn-primary">View my borrows</RouterLink>
        <RouterLink to="/" class="btn btn-ghost">Browse more</RouterLink>
      </div>
    </div>

    <BorrowForm
      v-else-if="book"
      :book="book"
      @borrowed="onBorrowed"
      @cancel="router.push('/')"
    />
  </section>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute, useRouter, RouterLink } from 'vue-router'
import http from '@/api/http'
import BorrowForm from '@/components/BorrowForm.vue'
import LoadingSpinner from '@/components/LoadingSpinner.vue'

const route   = useRoute()
const router  = useRouter()
const book    = ref(null)
const loading = ref(true)
const error   = ref('')
const success = ref(null)

async function loadBook () {
  loading.value = true
  try {
    const { data } = await http.get(`/api/books/${route.params.id}`)
    book.value = data.data
  } catch (err) {
    error.value = err.response?.data?.message ?? 'Could not load book.'
  } finally {
    loading.value = false
  }
}

function onBorrowed (payload) {
  success.value = payload
}

onMounted(loadBook)
</script>

<style scoped>
.borrow-view {
  max-width: 560px;
  margin: 0 auto;
}
.back-link {
  display: inline-block;
  margin-bottom: 1rem;
  color: var(--text-muted);
  text-decoration: none;
  font-size: 0.9rem;
}
.back-link:hover { color: var(--text); }
</style>

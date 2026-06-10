<template>
  <section class="return-view">
    <header class="page-head">
      <h1>My Borrows</h1>
      <p class="muted">All books you've borrowed, current and past.</p>
    </header>

    <LoadingSpinner v-if="loading" label="Loading your borrows…" />

    <div v-else-if="error" class="alert alert-error">{{ error }}</div>

    <div v-else-if="records.length === 0" class="empty card">
      <p class="muted">You haven't borrowed any books yet.</p>
      <RouterLink to="/" class="btn btn-primary">Browse catalogue</RouterLink>
    </div>

    <div v-else class="stack">
      <div v-if="toast" class="alert" :class="`alert-${toast.type}`">{{ toast.msg }}</div>

      <div class="table-wrap">
        <table class="table">
          <thead>
            <tr>
              <th>Book</th>
              <th>Borrowed</th>
              <th>Due</th>
              <th>Status</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="r in records" :key="r.id">
              <td>
                <div class="book-cell">
                  <strong>{{ r.title }}</strong>
                  <small class="muted">by {{ r.author }}</small>
                </div>
              </td>
              <td>{{ r.borrow_date }}</td>
              <td>{{ r.due_date }}</td>
              <td>
                <span class="badge" :class="badgeClass(r)">
                  {{ statusLabel(r) }}
                </span>
              </td>
              <td>
                <button
                  v-if="r.status === 'active'"
                  class="btn btn-primary"
                  :disabled="returning === r.id"
                  @click="returnBook(r.id)"
                >
                  {{ returning === r.id ? 'Returning…' : 'Return' }}
                </button>
                <span v-else class="muted">—</span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </section>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { RouterLink } from 'vue-router'
import http from '@/api/http'
import LoadingSpinner from '@/components/LoadingSpinner.vue'

const records   = ref([])
const loading   = ref(true)
const error     = ref('')
const returning = ref(null)
const toast     = ref(null)

async function loadBorrows () {
  loading.value = true
  try {
    const { data } = await http.get('/api/borrow/me')
    records.value = data.data || []
  } catch (err) {
    error.value = err.response?.data?.message ?? 'Failed to load borrows.'
  } finally {
    loading.value = false
  }
}

async function returnBook (recordId) {
  returning.value = recordId
  try {
    await http.post(`/api/return/${recordId}`)
    showToast('success', 'Book returned. Thanks!')
    await loadBorrows()
  } catch (err) {
    showToast('error', err.response?.data?.message ?? 'Return failed.')
  } finally {
    returning.value = null
  }
}

function statusLabel (r) {
  if (r.status === 'returned') return 'Returned'
  if (r.status === 'overdue' || (r.status === 'active' && new Date(r.due_date) < new Date())) {
    return 'Overdue'
  }
  return 'Active'
}

function badgeClass (r) {
  const label = statusLabel(r)
  if (label === 'Returned') return 'badge-success'
  if (label === 'Overdue')  return 'badge-danger'
  return ''
}

function showToast (type, msg) {
  toast.value = { type, msg }
  setTimeout(() => { toast.value = null }, 3500)
}

onMounted(loadBorrows)
</script>

<style scoped>
.page-head { margin-bottom: 1.5rem; }
.table-wrap { overflow-x: auto; }
.book-cell {
  display: flex;
  flex-direction: column;
}
.badge {
  background: var(--surface-muted);
  color: var(--text-muted);
  padding: 0.2rem 0.55rem;
  border-radius: 999px;
  font-size: 0.72rem;
  font-weight: 500;
}
.badge-success { background: var(--success-soft); color: var(--success); }
.badge-danger  { background: var(--danger-soft);  color: var(--danger); }

.empty {
  text-align: center;
  padding: 3rem 1rem;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 1rem;
}
</style>

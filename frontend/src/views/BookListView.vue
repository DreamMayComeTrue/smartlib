<template>
  <section class="book-list">
    <header class="page-head">
      <div>
        <h1>Catalogue</h1>
        <p class="muted">Browse books and borrow with one click.</p>
      </div>
      <SearchBar @search="handleSearch" />
    </header>

    <!-- Loading skeleton grid -->
    <div v-if="isLoading" class="grid skeleton-grid">
      <div v-for="n in 6" :key="n" class="skeleton-card"></div>
    </div>

    <!-- Error state -->
    <div v-else-if="error" class="alert alert-error">
      {{ error }}
      <button class="btn btn-ghost" @click="loadBooks">Retry</button>
    </div>

    <!-- Empty state -->
    <div v-else-if="filteredBooks.length === 0" class="empty">
      <p class="muted">No books match your search.</p>
    </div>

    <!-- Real grid -->
    <div v-else class="grid">
      <BookCard
        v-for="book in filteredBooks"
        :key="book.id"
        :book="book"
        @borrow="handleBorrow"
      />
    </div>

    <!-- Toast for borrow result -->
    <transition name="toast-fade">
      <div
        v-if="toast"
        class="toast alert"
        :class="`alert-${toast.type}`"
        role="status"
      >{{ toast.msg }}</div>
    </transition>

    <!-- Confirm-borrow modal -->
    <transition name="modal-fade">
      <div
        v-if="pendingBook"
        class="modal-backdrop"
        @click.self="cancelBorrow"
      >
        <div class="modal card" role="dialog" aria-modal="true">
          <h2>Confirm borrow</h2>
          <p class="muted">You're about to borrow:</p>
          <div class="book-summary">
            <strong>{{ pendingBook.title }}</strong>
            <small class="muted">by {{ pendingBook.author }}</small>
          </div>
          <p class="muted hint">
            Due in 14 days. You can return it any time from
            <em>My Borrows</em>.
          </p>
          <div class="modal-actions">
            <button
              class="btn btn-ghost"
              :disabled="borrowing"
              @click="cancelBorrow"
            >Cancel</button>
            <button
              class="btn btn-primary"
              :disabled="borrowing"
              @click="confirmBorrow"
            >{{ borrowing ? 'Borrowing…' : 'Confirm borrow' }}</button>
          </div>
        </div>
      </div>
    </transition>
  </section>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import http from '@/api/http'
import BookCard from '@/components/BookCard.vue'
import SearchBar from '@/components/SearchBar.vue'

const router       = useRouter()
const books        = ref([])
const isLoading    = ref(true)
const error        = ref('')
const searchQuery  = ref('')
const toast        = ref(null)
const pendingBook  = ref(null)   // book queued for borrow confirmation
const borrowing    = ref(false)  // true while POST /api/borrow is in flight

// Client-side filter — instant feedback, no network call per keystroke
const filteredBooks = computed(() => {
  const q = searchQuery.value.toLowerCase()
  if (!q) return books.value
  return books.value.filter(b =>
    b.title.toLowerCase().includes(q) ||
    b.author.toLowerCase().includes(q) ||
    (b.category || '').toLowerCase().includes(q)
  )
})

function handleSearch (query) { searchQuery.value = query }

// Step 1 — clicking Borrow on a card opens the confirmation modal.
function handleBorrow (bookId) {
  if (!localStorage.getItem('token')) {
    router.push({ path: '/login', query: { redirect: '/' } })
    return
  }
  const book = books.value.find(b => b.id === bookId)
  if (book) pendingBook.value = book
}

// Step 2 — user clicked "Confirm borrow" in the modal: fire the API call.
async function confirmBorrow () {
  if (!pendingBook.value) return
  borrowing.value = true
  try {
    await http.post('/api/borrow', { book_id: pendingBook.value.id })
    // Optimistic UI: decrement available_count for instant feedback
    pendingBook.value.available_count = Math.max(0, pendingBook.value.available_count - 1)
    showToast('success', `Borrowed "${pendingBook.value.title}". Check "My Borrows" for the due date.`)
    pendingBook.value = null
  } catch (err) {
    showToast('error', err.response?.data?.message ?? 'Borrow failed.')
    pendingBook.value = null
  } finally {
    borrowing.value = false
  }
}

function cancelBorrow () {
  if (borrowing.value) return    // ignore clicks while request is mid-flight
  pendingBook.value = null
}

function showToast (type, msg) {
  toast.value = { type, msg }
  setTimeout(() => { toast.value = null }, 3500)
}

async function loadBooks () {
  isLoading.value = true
  error.value = ''
  try {
    const { data } = await http.get('/api/books')
    books.value = data.data || []
  } catch (err) {
    error.value = err.response?.data?.message ?? 'Failed to load books.'
  } finally {
    isLoading.value = false
  }
}

onMounted(loadBooks)
</script>

<style scoped>
.page-head {
  display: flex;
  flex-direction: column;
  gap: 1rem;
  margin-bottom: 1.5rem;
}
@media (min-width: 768px) {
  .page-head {
    flex-direction: row;
    align-items: flex-end;
    justify-content: space-between;
  }
  .page-head > :last-child { min-width: 320px; }
}

.grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 1.25rem;
}
@media (max-width: 1024px) {
  .grid { grid-template-columns: repeat(2, 1fr); }
}
@media (max-width: 600px) {
  .grid { grid-template-columns: 1fr; }
}

/* Shimmer skeleton */
.skeleton-card {
  height: 280px;
  border-radius: var(--radius-lg);
  background: linear-gradient(90deg, #f0f4f8 25%, #e2ecf4 50%, #f0f4f8 75%);
  background-size: 200% 100%;
  animation: shimmer 1.5s infinite;
}
@keyframes shimmer {
  0%   { background-position: 200% 0; }
  100% { background-position: -200% 0; }
}

.empty {
  text-align: center;
  padding: 4rem 1rem;
}

/* Floating toast — always visible regardless of scroll */
.toast {
  position: fixed;
  top: 80px;          /* sits just below the navbar */
  left: 50%;
  transform: translateX(-50%);
  z-index: 100;
  min-width: 280px;
  max-width: 90vw;
  margin: 0;
  text-align: center;
  box-shadow: var(--shadow);
}
.toast-fade-enter-active,
.toast-fade-leave-active {
  transition: opacity 0.25s ease, transform 0.25s ease;
}
.toast-fade-enter-from,
.toast-fade-leave-to {
  opacity: 0;
  transform: translate(-50%, -8px);
}

/* Confirm-borrow modal */
.modal-backdrop {
  position: fixed;
  inset: 0;
  background: rgba(15, 23, 42, 0.45);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 1rem;
  z-index: 50;
}
.modal {
  width: 100%;
  max-width: 420px;
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}
.book-summary {
  background: var(--surface-muted);
  padding: 0.75rem 1rem;
  border-radius: var(--radius);
  display: flex;
  flex-direction: column;
  gap: 0.15rem;
}
.book-summary strong { font-size: 1rem; color: var(--text); }
.hint { font-size: 0.85rem; }
.modal-actions {
  display: flex;
  justify-content: flex-end;
  gap: 0.5rem;
  margin-top: 0.5rem;
}
.modal-fade-enter-active,
.modal-fade-leave-active {
  transition: opacity 0.2s ease;
}
.modal-fade-enter-active .modal,
.modal-fade-leave-active .modal {
  transition: transform 0.2s ease;
}
.modal-fade-enter-from,
.modal-fade-leave-to {
  opacity: 0;
}
.modal-fade-enter-from .modal,
.modal-fade-leave-to .modal {
  transform: translateY(-8px);
}
</style>

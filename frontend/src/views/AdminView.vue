<template>
  <section class="admin-view">
    <header class="page-head">
      <div>
        <h1>Admin Dashboard</h1>
        <p class="muted">Manage the catalogue. {{ books.length }} title(s).</p>
      </div>
      <button class="btn btn-primary" @click="openCreate">+ Add book</button>
    </header>

    <LoadingSpinner v-if="loading" label="Loading…" />
    <div v-else-if="error" class="alert alert-error">{{ error }}</div>

    <div v-else class="stack">
      <div v-if="toast" class="alert" :class="`alert-${toast.type}`">{{ toast.msg }}</div>

      <div class="table-wrap">
        <table class="table">
          <thead>
            <tr>
              <th>Title</th>
              <th>Author</th>
              <th>Category</th>
              <th>Stock</th>
              <th>Available</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="b in books" :key="b.id">
              <td>{{ b.title }}</td>
              <td>{{ b.author }}</td>
              <td>{{ b.category || '—' }}</td>
              <td>{{ b.stock }}</td>
              <td>{{ b.available_count }}</td>
              <td class="row-actions">
                <button class="btn btn-ghost"  @click="openEdit(b)">Edit</button>
                <button class="btn btn-danger" @click="confirmDelete(b)">Delete</button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Modal: create / edit -->
    <div v-if="modal.open" class="modal-backdrop" @click.self="closeModal">
      <form class="modal card stack" @submit.prevent="saveBook">
        <h2>{{ modal.mode === 'edit' ? 'Edit book' : 'Add book' }}</h2>

        <div class="form-group">
          <label>Title</label>
          <input v-model="modal.form.title" class="form-input" required />
        </div>
        <div class="form-group">
          <label>Author</label>
          <input v-model="modal.form.author" class="form-input" required />
        </div>
        <div class="form-group">
          <label>ISBN (13 digits)</label>
          <input v-model="modal.form.isbn" class="form-input" maxlength="13" pattern="\d{13}" />
        </div>
        <div class="form-group">
          <label>Category</label>
          <input v-model="modal.form.category" class="form-input" />
        </div>
        <div class="form-group">
          <label>Stock</label>
          <input v-model.number="modal.form.stock" type="number" min="1" class="form-input" required />
        </div>

        <div v-if="modal.error" class="alert alert-error">{{ modal.error }}</div>

        <div class="flex between">
          <button type="button" class="btn btn-ghost" @click="closeModal">Cancel</button>
          <button type="submit" class="btn btn-primary" :disabled="modal.saving">
            {{ modal.saving ? 'Saving…' : 'Save' }}
          </button>
        </div>
      </form>
    </div>
  </section>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import http from '@/api/http'
import LoadingSpinner from '@/components/LoadingSpinner.vue'

const books   = ref([])
const loading = ref(true)
const error   = ref('')
const toast   = ref(null)

const blankForm = () => ({
  id: null, title: '', author: '', isbn: '', category: '', stock: 1
})

const modal = reactive({
  open: false,
  mode: 'create',      // 'create' | 'edit'
  form: blankForm(),
  saving: false,
  error: ''
})

async function loadBooks () {
  loading.value = true
  try {
    const { data } = await http.get('/api/books')
    books.value = data.data || []
  } catch (err) {
    error.value = err.response?.data?.message ?? 'Failed to load books.'
  } finally {
    loading.value = false
  }
}

function openCreate () {
  modal.mode  = 'create'
  modal.form  = blankForm()
  modal.error = ''
  modal.open  = true
}
function openEdit (book) {
  modal.mode  = 'edit'
  modal.form  = { ...book, isbn: book.isbn || '', category: book.category || '' }
  modal.error = ''
  modal.open  = true
}
function closeModal () {
  modal.open = false
}

async function saveBook () {
  modal.saving = true
  modal.error  = ''
  try {
    if (modal.mode === 'create') {
      await http.post('/api/books', modal.form)
      showToast('success', 'Book added.')
    } else {
      await http.put(`/api/books/${modal.form.id}`, modal.form)
      showToast('success', 'Book updated.')
    }
    modal.open = false
    await loadBooks()
  } catch (err) {
    modal.error = err.response?.data?.message ?? 'Save failed.'
  } finally {
    modal.saving = false
  }
}

async function confirmDelete (book) {
  if (!confirm(`Delete "${book.title}"? This cannot be undone.`)) return
  try {
    await http.delete(`/api/books/${book.id}`)
    showToast('success', 'Book deleted.')
    await loadBooks()
  } catch (err) {
    showToast('error', err.response?.data?.message ?? 'Delete failed.')
  }
}

function showToast (type, msg) {
  toast.value = { type, msg }
  setTimeout(() => { toast.value = null }, 3500)
}

onMounted(loadBooks)
</script>

<style scoped>
.page-head {
  display: flex;
  justify-content: space-between;
  align-items: flex-end;
  flex-wrap: wrap;
  gap: 1rem;
  margin-bottom: 1.5rem;
}
.table-wrap { overflow-x: auto; }
.row-actions {
  display: flex;
  gap: 0.4rem;
  justify-content: flex-end;
}

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
  max-width: 480px;
  max-height: 90vh;
  overflow-y: auto;
}
</style>

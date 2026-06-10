<template>
  <article class="book-card">
    <div class="cover">
      <span class="cover-icon">📖</span>
    </div>

    <div class="body">
      <h3 class="title">{{ book.title }}</h3>
      <p class="author">by {{ book.author }}</p>

      <div class="meta">
        <span v-if="book.category" class="badge">{{ book.category }}</span>
        <span
          class="badge"
          :class="available ? 'badge-success' : 'badge-danger'"
        >
          {{ available ? `${book.available_count} available` : 'Out of stock' }}
        </span>
      </div>

      <div class="actions">
        <button
          class="btn btn-primary"
          :disabled="!available"
          @click="$emit('borrow', book.id)"
        >
          {{ available ? 'Borrow' : 'Unavailable' }}
        </button>
        <button
          v-if="showAdmin"
          class="btn btn-ghost"
          @click="$emit('edit', book)"
        >Edit</button>
        <button
          v-if="showAdmin"
          class="btn btn-danger"
          @click="$emit('delete', book.id)"
        >Delete</button>
      </div>
    </div>
  </article>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  book:      { type: Object, required: true },
  showAdmin: { type: Boolean, default: false }
})

defineEmits(['borrow', 'edit', 'delete'])

const available = computed(() => Number(props.book.available_count) > 0)
</script>

<style scoped>
.book-card {
  display: flex;
  flex-direction: column;
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius-lg);
  overflow: hidden;
  box-shadow: var(--shadow-sm);
  transition: transform 0.15s, box-shadow 0.15s;
}
.book-card:hover {
  transform: translateY(-2px);
  box-shadow: var(--shadow);
}

.cover {
  height: 120px;
  background: linear-gradient(135deg, #dbeafe, #c7d2fe);
  display: flex;
  align-items: center;
  justify-content: center;
}
.cover-icon { font-size: 3rem; }

.body {
  padding: 1rem;
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
  flex: 1;
}

.title {
  font-size: 1rem;
  margin: 0;
  /* clamp long titles to 2 lines */
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
.author {
  margin: 0;
  font-size: 0.85rem;
  color: var(--text-muted);
}

.meta {
  display: flex;
  flex-wrap: wrap;
  gap: 0.4rem;
  margin-top: auto;
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

.actions {
  display: flex;
  flex-wrap: wrap;
  gap: 0.4rem;
  margin-top: 0.5rem;
}
.actions .btn { flex: 1; min-width: 0; }
</style>

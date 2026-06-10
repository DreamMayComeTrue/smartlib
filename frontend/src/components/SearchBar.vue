<template>
  <div class="search-bar">
    <span class="search-icon">🔍</span>
    <input
      v-model="query"
      type="text"
      class="search-input"
      :placeholder="placeholder"
      @input="emitSearch"
    />
    <button
      v-if="query"
      class="clear-btn"
      type="button"
      aria-label="Clear search"
      @click="clear"
    >×</button>
  </div>
</template>

<script setup>
import { ref } from 'vue'

defineProps({
  placeholder: { type: String, default: 'Search books by title, author or category…' }
})

const emit  = defineEmits(['search'])
const query = ref('')

// Tiny debounce so we don't spam parent on every keystroke
let timer
function emitSearch () {
  clearTimeout(timer)
  timer = setTimeout(() => emit('search', query.value.trim()), 150)
}

function clear () {
  query.value = ''
  emit('search', '')
}
</script>

<style scoped>
.search-bar {
  position: relative;
  display: flex;
  align-items: center;
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  padding: 0 0.75rem;
  transition: border-color 0.15s, box-shadow 0.15s;
}
.search-bar:focus-within {
  border-color: var(--primary);
  box-shadow: 0 0 0 3px var(--primary-soft);
}

.search-icon { font-size: 0.95rem; color: var(--text-muted); }

.search-input {
  flex: 1;
  border: none;
  outline: none;
  padding: 0.65rem 0.5rem;
  font-size: 0.95rem;
  background: transparent;
  color: var(--text);
}

.clear-btn {
  background: none;
  border: none;
  color: var(--text-muted);
  font-size: 1.5rem;
  line-height: 1;
  cursor: pointer;
  padding: 0 0.25rem;
}
.clear-btn:hover { color: var(--text); }
</style>

<template>
  <form class="card stack" @submit.prevent="submit">
    <h2>Borrow “{{ book.title }}”</h2>
    <p class="muted">By {{ book.author }} · {{ book.available_count }} copy(ies) available</p>

    <div class="form-group">
      <label for="duration">Borrow duration</label>
      <select id="duration" v-model="durationDays" class="form-input">
        <option :value="7">7 days</option>
        <option :value="14">14 days (default)</option>
        <option :value="21">21 days</option>
      </select>
      <p class="muted hint">
        The backend enforces a fixed 14-day default — this field is informational.
      </p>
    </div>

    <label class="terms-row">
      <input v-model="agreed" type="checkbox" />
      I agree to return the book by the due date.
    </label>

    <div v-if="error" class="alert alert-error">{{ error }}</div>

    <div class="flex between">
      <button type="button" class="btn btn-ghost" @click="$emit('cancel')">Cancel</button>
      <button
        type="submit"
        class="btn btn-primary"
        :disabled="!agreed || submitting || book.available_count < 1"
      >
        {{ submitting ? 'Borrowing…' : 'Confirm borrow' }}
      </button>
    </div>
  </form>
</template>

<script setup>
import { ref } from 'vue'
import http from '@/api/http'

const props = defineProps({
  book: { type: Object, required: true }
})
const emit = defineEmits(['borrowed', 'cancel'])

const durationDays = ref(14)
const agreed       = ref(false)
const submitting   = ref(false)
const error        = ref('')

async function submit () {
  error.value = ''
  submitting.value = true
  try {
    const { data } = await http.post('/api/borrow', { book_id: props.book.id })
    emit('borrowed', data)
  } catch (err) {
    error.value = err.response?.data?.message ?? 'Borrow failed. Please try again.'
  } finally {
    submitting.value = false
  }
}
</script>

<style scoped>
.terms-row {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.9rem;
}
.hint { font-size: 0.8rem; margin-top: 0.25rem; }
</style>

<template>
  <div class="spinner-wrap" :class="{ inline }">
    <div class="spinner" :style="{ width: size + 'px', height: size + 'px' }"></div>
    <span v-if="label" class="label">{{ label }}</span>
  </div>
</template>

<script setup>
defineProps({
  size:   { type: Number,  default: 24 },
  label:  { type: String,  default: '' },
  inline: { type: Boolean, default: false }
})
</script>

<style scoped>
.spinner-wrap {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  padding: 1.5rem 0;
}
.spinner-wrap.inline { padding: 0; }

.spinner {
  border: 3px solid var(--surface-muted);
  border-top-color: var(--primary);
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

.label {
  font-size: 0.9rem;
  color: var(--text-muted);
}

@keyframes spin {
  to { transform: rotate(360deg); }
}
</style>

<template>
  <span class="px-3 py-1 rounded-full text-sm font-medium" :class="statusClass">
    {{ statusLabel }}
  </span>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  status: {
    type: String,
    required: true
  }
})

const statusClass = computed(() => {
  const classes = {
    requested: 'bg-yellow-100 text-yellow-800',
    approved: 'bg-green-100 text-green-800',
    cancelled: 'bg-red-100 text-red-800'
  }
  return classes[props.status] || 'bg-gray-100 text-gray-800'
})

const statusLabel = computed(() => {
  const labels = {
    requested: 'Solicitado',
    approved: 'Aprovado',
    cancelled: 'Cancelado'
  }
  return labels[props.status] || props.status
})
</script>

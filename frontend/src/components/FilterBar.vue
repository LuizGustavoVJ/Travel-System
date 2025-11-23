<template>
  <div class="bg-white shadow rounded-lg p-6 mb-6">
    <h3 class="text-lg font-medium text-gray-900 mb-4">Filtros</h3>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
        <select v-model="localFilters.status" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
          <option value="">Todos</option>
          <option value="requested">Solicitado</option>
          <option value="approved">Aprovado</option>
          <option value="cancelled">Cancelado</option>
        </select>
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Destino</label>
        <input v-model="localFilters.destination" type="text" placeholder="Digite o destino"
          class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" />
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Data de Ida (De)</label>
        <input v-model="localFilters.start_date_from" type="date"
          class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" />
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Data de Ida (Até)</label>
        <input v-model="localFilters.start_date_to" type="date"
          class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" />
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Data de Volta (De)</label>
        <input v-model="localFilters.end_date_from" type="date"
          class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" />
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Data de Volta (Até)</label>
        <input v-model="localFilters.end_date_to" type="date"
          class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" />
      </div>
    </div>
    <div class="flex justify-end space-x-3 mt-4">
      <button @click="clearFilters" class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
        Limpar Filtros
      </button>
      <button @click="applyFilters" class="px-4 py-2 border border-transparent rounded-md text-sm font-medium text-white bg-blue-600 hover:bg-blue-700">
        Aplicar Filtros
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue'

const props = defineProps({
  filters: { type: Object, default: () => ({}) }
})

const emit = defineEmits(['apply-filters', 'clear-filters'])

const localFilters = ref({
  status: props.filters.status || '',
  destination: props.filters.destination || '',
  start_date_from: props.filters.start_date_from || '',
  start_date_to: props.filters.start_date_to || '',
  end_date_from: props.filters.end_date_from || '',
  end_date_to: props.filters.end_date_to || ''
})

watch(() => props.filters, (newFilters) => {
  localFilters.value = { ...newFilters }
}, { deep: true })

const applyFilters = () => {
  emit('apply-filters', { ...localFilters.value })
}

const clearFilters = () => {
  localFilters.value = {
    status: '',
    destination: '',
    start_date_from: '',
    start_date_to: '',
    end_date_from: '',
    end_date_to: ''
  }
  emit('clear-filters')
}
</script>

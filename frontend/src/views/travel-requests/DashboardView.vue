<template>
  <div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <!-- Cabeçalho -->
      <div class="flex justify-between items-center mb-8">
        <div>
          <h1 class="text-3xl font-bold text-gray-900">Pedidos de Viagem</h1>
          <p class="text-gray-600 mt-1">Olá, {{ authStore.user?.name }} <span v-if="authStore.user?.role === 'admin'" class="text-blue-600 font-medium">(Admin)</span></p>
        </div>
        <div class="flex space-x-3">
          <button @click="router.push('/travel-requests/create')"
            class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
            + Novo Pedido
          </button>
          <button @click="handleLogout"
            class="px-4 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700">
            Sair
          </button>
        </div>
      </div>

      <!-- Filtros -->
      <FilterBar :filters="filters" @apply-filters="applyFilters" @clear-filters="clearFilters" />

      <!-- Loading -->
      <div v-if="loading" class="flex justify-center items-center h-64">
        <div class="text-gray-600">Carregando...</div>
      </div>

      <!-- Erro -->
      <div v-else-if="error" class="rounded-md bg-red-50 p-4">
        <p class="text-sm text-red-700">{{ error }}</p>
      </div>

      <!-- Lista de Pedidos -->
      <div v-else>
        <!-- Empty State -->
        <div v-if="travelRequests.length === 0" class="text-center py-12 bg-white rounded-lg shadow">
          <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
          </svg>
          <h3 class="mt-2 text-sm font-medium text-gray-900">Nenhum pedido encontrado</h3>
          <p class="mt-1 text-sm text-gray-500">Comece criando um novo pedido de viagem.</p>
          <div class="mt-6">
            <button @click="router.push('/travel-requests/create')"
              class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
              + Novo Pedido
            </button>
          </div>
        </div>

        <!-- Tabela -->
        <div v-else class="bg-white shadow-md rounded-lg overflow-hidden">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Solicitante</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Destino</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Datas</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Ações</th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <tr v-for="request in travelRequests" :key="request.id" class="hover:bg-gray-50">
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                  {{ request.id.substring(0, 8) }}...
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                  {{ request.requester_name }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                  {{ request.destination }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                  {{ formatDate(request.start_date) }} - {{ formatDate(request.end_date) }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <StatusBadge :status="request.status" />
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-2">
                  <button @click="viewDetails(request.id)"
                    class="text-blue-600 hover:text-blue-900">Ver</button>
                  <button v-if="canEdit(request)" @click="editRequest(request.id)"
                    class="text-green-600 hover:text-green-900">Editar</button>
                  <button v-if="canDelete(request)" @click="confirmDelete(request)"
                    class="text-red-600 hover:text-red-900">Deletar</button>
                </td>
              </tr>
            </tbody>
          </table>

          <!-- Paginação -->
          <Pagination v-if="pagination.last_page > 1"
            :current-page="pagination.current_page"
            :last-page="pagination.last_page"
            :from="pagination.from"
            :to="pagination.to"
            :total="pagination.total"
            @page-changed="loadPage" />
        </div>
      </div>
    </div>

    <!-- Modal de Confirmação de Deleção -->
    <Modal :show="showDeleteModal" title="Confirmar Deleção" confirm-text="Deletar"
      cancel-text="Cancelar" confirm-class="bg-red-600 hover:bg-red-700" :loading="deleting"
      @close="showDeleteModal = false" @confirm="deleteRequest">
      <p class="text-sm text-gray-500">
        Tem certeza que deseja deletar o pedido de viagem para <strong>{{ requestToDelete?.destination }}</strong>?
        Esta ação não pode ser desfeita.
      </p>
    </Modal>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useTravelRequestStore } from '@/stores/travelRequest'
import { useAuthStore } from '@/stores/auth'
import FilterBar from '@/components/FilterBar.vue'
import Pagination from '@/components/Pagination.vue'
import StatusBadge from '@/components/StatusBadge.vue'
import Modal from '@/components/Modal.vue'

const router = useRouter()
const travelRequestStore = useTravelRequestStore()
const authStore = useAuthStore()

const travelRequests = ref([])
const loading = ref(false)
const error = ref('')
const filters = ref({})
const pagination = ref({
  current_page: 1,
  last_page: 1,
  from: 0,
  to: 0,
  total: 0
})

const showDeleteModal = ref(false)
const requestToDelete = ref(null)
const deleting = ref(false)

const formatDate = (date) => {
  if (!date) return '-'
  return new Date(date).toLocaleDateString('pt-BR')
}

const canEdit = (request) => {
  return request.status === 'requested' && request.user_id === authStore.user?.id
}

const canDelete = (request) => {
  return request.user_id === authStore.user?.id || authStore.user?.role === 'admin'
}

const viewDetails = (id) => {
  router.push(`/travel-requests/${id}`)
}

const editRequest = (id) => {
  router.push(`/travel-requests/${id}/edit`)
}

const confirmDelete = (request) => {
  requestToDelete.value = request
  showDeleteModal.value = true
}

const deleteRequest = async () => {
  deleting.value = true
  try {
    await travelRequestStore.deleteTravelRequest(requestToDelete.value.id)
    showDeleteModal.value = false
    requestToDelete.value = null
    loadTravelRequests()
  } catch (err) {
    error.value = err.response?.data?.message || 'Erro ao deletar pedido'
  } finally {
    deleting.value = false
  }
}

const loadTravelRequests = async (page = 1) => {
  loading.value = true
  error.value = ''
  try {
    const response = await travelRequestStore.getTravelRequests({ ...filters.value, page })
    travelRequests.value = response.data
    pagination.value = {
      current_page: response.current_page,
      last_page: response.last_page,
      from: response.from,
      to: response.to,
      total: response.total
    }
  } catch (err) {
    error.value = err.response?.data?.message || 'Erro ao carregar pedidos'
  } finally {
    loading.value = false
  }
}

const applyFilters = (newFilters) => {
  filters.value = newFilters
  loadTravelRequests(1)
}

const clearFilters = () => {
  filters.value = {}
  loadTravelRequests(1)
}

const loadPage = (page) => {
  loadTravelRequests(page)
}

const handleLogout = async () => {
  await authStore.logout()
  router.push('/login')
}

onMounted(() => {
  loadTravelRequests()
})
</script>

<template>
  <div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
      <!-- Loading -->
      <div v-if="loading" class="flex justify-center items-center h-64">
        <div class="text-gray-600">Carregando...</div>
      </div>

      <!-- Erro -->
      <div v-else-if="error" class="rounded-md bg-red-50 p-4">
        <div class="flex">
          <div class="ml-3">
            <h3 class="text-sm font-medium text-red-800">Erro</h3>
            <div class="mt-2 text-sm text-red-700">{{ error }}</div>
          </div>
        </div>
      </div>

      <!-- Detalhes do Pedido -->
      <div v-else-if="travelRequest">
        <!-- Cabeçalho -->
        <div class="mb-8">
          <button @click="router.push('/dashboard')" class="text-blue-600 hover:text-blue-800 flex items-center mb-4">
            ← Voltar para Dashboard
          </button>
          <div class="flex justify-between items-start">
            <div>
              <h1 class="text-3xl font-bold text-gray-900">Detalhes do Pedido</h1>
              <p class="mt-2 text-gray-600">ID: {{ travelRequest.id }}</p>
            </div>
            <StatusBadge :status="travelRequest.status" />
          </div>
        </div>

        <!-- Card Principal -->
        <div class="bg-white shadow-md rounded-lg overflow-hidden mb-6">
          <div class="p-6 space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <h3 class="text-sm font-medium text-gray-500">Solicitante</h3>
                <p class="mt-1 text-lg text-gray-900">{{ travelRequest.requester_name }}</p>
              </div>
              <div>
                <h3 class="text-sm font-medium text-gray-500">Destino</h3>
                <p class="mt-1 text-lg text-gray-900">{{ travelRequest.destination }}</p>
              </div>
              <div>
                <h3 class="text-sm font-medium text-gray-500">Data de Ida</h3>
                <p class="mt-1 text-lg text-gray-900">{{ formatDate(travelRequest.start_date) }}</p>
              </div>
              <div>
                <h3 class="text-sm font-medium text-gray-500">Data de Volta</h3>
                <p class="mt-1 text-lg text-gray-900">{{ formatDate(travelRequest.end_date) }}</p>
              </div>
              <div v-if="travelRequest.notes" class="md:col-span-2">
                <h3 class="text-sm font-medium text-gray-500">Observações</h3>
                <p class="mt-1 text-gray-900">{{ travelRequest.notes }}</p>
              </div>
              <div v-if="travelRequest.approved_by">
                <h3 class="text-sm font-medium text-gray-500">Aprovado por</h3>
                <p class="mt-1 text-gray-900">{{ travelRequest.approved_by }}</p>
              </div>
              <div v-if="travelRequest.cancelled_by">
                <h3 class="text-sm font-medium text-gray-500">Cancelado por</h3>
                <p class="mt-1 text-gray-900">{{ travelRequest.cancelled_by }}</p>
              </div>
              <div v-if="travelRequest.cancelled_reason" class="md:col-span-2">
                <h3 class="text-sm font-medium text-gray-500">Motivo do Cancelamento</h3>
                <p class="mt-1 text-gray-900">{{ travelRequest.cancelled_reason }}</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Ações -->
        <div class="flex justify-end space-x-3">
          <button v-if="canEdit" @click="router.push(`/travel-requests/${travelRequest.id}/edit`)"
            class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700">
            Editar
          </button>
          <button v-if="canApprove" @click="showApproveModal = true"
            class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
            Aprovar
          </button>
          <button v-if="canCancel" @click="showCancelModal = true"
            class="px-4 py-2 bg-yellow-600 text-white rounded-md hover:bg-yellow-700">
            Cancelar
          </button>
          <button v-if="canDelete" @click="showDeleteModal = true"
            class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700">
            Deletar
          </button>
        </div>
      </div>
    </div>

    <!-- Modal de Aprovação -->
    <Modal :show="showApproveModal" title="Aprovar Pedido" confirm-text="Aprovar"
      :loading="approving" @close="showApproveModal = false" @confirm="approveRequest">
      <p class="text-sm text-gray-500">
        Tem certeza que deseja aprovar o pedido de viagem para <strong>{{ travelRequest?.destination }}</strong>?
      </p>
    </Modal>

    <!-- Modal de Cancelamento -->
    <Modal :show="showCancelModal" title="Cancelar Pedido" confirm-text="Cancelar Pedido"
      confirm-class="bg-yellow-600 hover:bg-yellow-700" :loading="cancelling"
      @close="showCancelModal = false" @confirm="cancelRequest">
      <div>
        <p class="text-sm text-gray-500 mb-4">
          Informe o motivo do cancelamento do pedido para <strong>{{ travelRequest?.destination }}</strong>:
        </p>
        <textarea v-model="cancelReason" rows="4" placeholder="Digite o motivo do cancelamento..."
          class="w-full rounded-md border-gray-300 shadow-sm focus:border-yellow-500 focus:ring-yellow-500"></textarea>
        <p v-if="cancelError" class="mt-2 text-sm text-red-600">{{ cancelError }}</p>
      </div>
    </Modal>

    <!-- Modal de Deleção -->
    <Modal :show="showDeleteModal" title="Deletar Pedido" confirm-text="Deletar"
      cancel-text="Cancelar" confirm-class="bg-red-600 hover:bg-red-700" :loading="deleting"
      @close="showDeleteModal = false" @confirm="deleteRequest">
      <p class="text-sm text-gray-500">
        Tem certeza que deseja deletar o pedido de viagem para <strong>{{ travelRequest?.destination }}</strong>?
        Esta ação não pode ser desfeita.
      </p>
    </Modal>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useTravelRequestStore } from '@/stores/travelRequest'
import { useAuthStore } from '@/stores/auth'
import StatusBadge from '@/components/StatusBadge.vue'
import Modal from '@/components/Modal.vue'

const router = useRouter()
const route = useRoute()
const travelRequestStore = useTravelRequestStore()
const authStore = useAuthStore()

const travelRequest = ref(null)
const loading = ref(true)
const error = ref('')

const showApproveModal = ref(false)
const approving = ref(false)

const showCancelModal = ref(false)
const cancelling = ref(false)
const cancelReason = ref('')
const cancelError = ref('')

const showDeleteModal = ref(false)
const deleting = ref(false)

const canEdit = computed(() => {
  return travelRequest.value?.status === 'requested' && 
         travelRequest.value?.user_id === authStore.user?.id
})

const canApprove = computed(() => {
  return authStore.user?.role === 'admin' && travelRequest.value?.status === 'requested'
})

const canCancel = computed(() => {
  return authStore.user?.role === 'admin' && travelRequest.value?.status === 'requested'
})

const canDelete = computed(() => {
  return travelRequest.value?.user_id === authStore.user?.id || authStore.user?.role === 'admin'
})

const formatDate = (date) => {
  if (!date) return '-'
  return new Date(date).toLocaleDateString('pt-BR')
}

const loadTravelRequest = async () => {
  loading.value = true
  error.value = ''
  try {
    const response = await travelRequestStore.getTravelRequest(route.params.id)
    travelRequest.value = response
  } catch (err) {
    error.value = err.response?.data?.message || 'Erro ao carregar pedido'
  } finally {
    loading.value = false
  }
}

const approveRequest = async () => {
  approving.value = true
  try {
    await travelRequestStore.approveTravelRequest(travelRequest.value.id)
    showApproveModal.value = false
    await loadTravelRequest()
  } catch (err) {
    error.value = err.response?.data?.message || 'Erro ao aprovar pedido'
  } finally {
    approving.value = false
  }
}

const cancelRequest = async () => {
  cancelError.value = ''
  if (!cancelReason.value.trim()) {
    cancelError.value = 'O motivo do cancelamento é obrigatório'
    return
  }
  
  cancelling.value = true
  try {
    await travelRequestStore.cancelTravelRequest(travelRequest.value.id, cancelReason.value)
    showCancelModal.value = false
    cancelReason.value = ''
    await loadTravelRequest()
  } catch (err) {
    cancelError.value = err.response?.data?.message || 'Erro ao cancelar pedido'
  } finally {
    cancelling.value = false
  }
}

const deleteRequest = async () => {
  deleting.value = true
  try {
    await travelRequestStore.deleteTravelRequest(travelRequest.value.id)
    router.push('/dashboard')
  } catch (err) {
    error.value = err.response?.data?.message || 'Erro ao deletar pedido'
    showDeleteModal.value = false
  } finally {
    deleting.value = false
  }
}

onMounted(() => {
  loadTravelRequest()
})
</script>

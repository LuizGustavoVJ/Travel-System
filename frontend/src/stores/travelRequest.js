import { defineStore } from 'pinia'
import { ref } from 'vue'
import { travelRequestService } from '@/services/travelRequestService'

export const useTravelRequestStore = defineStore('travelRequest', () => {
  // State
  const travelRequests = ref([])
  const currentTravelRequest = ref(null)
  const loading = ref(false)
  const pagination = ref({
    current_page: 1,
    last_page: 1,
    per_page: 15,
    total: 0
  })

  // Actions
  async function fetchAll(params = {}) {
    loading.value = true
    try {
      const data = await travelRequestService.getAll(params)
      travelRequests.value = data.data
      pagination.value = {
        current_page: data.current_page,
        last_page: data.last_page,
        per_page: data.per_page,
        total: data.total
      }
      return data
    } catch (error) {
      throw error
    } finally {
      loading.value = false
    }
  }

  async function fetchById(id) {
    loading.value = true
    try {
      const data = await travelRequestService.getById(id)
      currentTravelRequest.value = data
      return data
    } catch (error) {
      throw error
    } finally {
      loading.value = false
    }
  }

  async function create(travelRequestData) {
    loading.value = true
    try {
      const data = await travelRequestService.create(travelRequestData)
      travelRequests.value.unshift(data)
      return data
    } catch (error) {
      throw error
    } finally {
      loading.value = false
    }
  }

  async function update(id, travelRequestData) {
    loading.value = true
    try {
      const data = await travelRequestService.update(id, travelRequestData)
      const index = travelRequests.value.findIndex(tr => tr.id === id)
      if (index !== -1) {
        travelRequests.value[index] = data
      }
      if (currentTravelRequest.value?.id === id) {
        currentTravelRequest.value = data
      }
      return data
    } catch (error) {
      throw error
    } finally {
      loading.value = false
    }
  }

  async function remove(id) {
    loading.value = true
    try {
      await travelRequestService.delete(id)
      travelRequests.value = travelRequests.value.filter(tr => tr.id !== id)
    } catch (error) {
      throw error
    } finally {
      loading.value = false
    }
  }

  async function approve(id) {
    loading.value = true
    try {
      const data = await travelRequestService.approve(id)
      const index = travelRequests.value.findIndex(tr => tr.id === id)
      if (index !== -1) {
        travelRequests.value[index] = data
      }
      if (currentTravelRequest.value?.id === id) {
        currentTravelRequest.value = data
      }
      return data
    } catch (error) {
      throw error
    } finally {
      loading.value = false
    }
  }

  async function cancel(id, reason) {
    loading.value = true
    try {
      const data = await travelRequestService.cancel(id, reason)
      const index = travelRequests.value.findIndex(tr => tr.id === id)
      if (index !== -1) {
        travelRequests.value[index] = data
      }
      if (currentTravelRequest.value?.id === id) {
        currentTravelRequest.value = data
      }
      return data
    } catch (error) {
      throw error
    } finally {
      loading.value = false
    }
  }

  return {
    // State
    travelRequests,
    currentTravelRequest,
    loading,
    pagination,
    // Actions
    fetchAll,
    fetchById,
    create,
    update,
    remove,
    approve,
    cancel
  }
})

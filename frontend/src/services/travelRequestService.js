import apiClient from '@/api/axios'

export const travelRequestService = {
  async getAll(params = {}) {
    const response = await apiClient.get('/travel-requests', { params })
    return response.data
  },

  async getById(id) {
    const response = await apiClient.get(`/travel-requests/${id}`)
    return response.data
  },

  async create(data) {
    const response = await apiClient.post('/travel-requests', data)
    return response.data
  },

  async update(id, data) {
    const response = await apiClient.put(`/travel-requests/${id}`, data)
    return response.data
  },

  async delete(id) {
    const response = await apiClient.delete(`/travel-requests/${id}`)
    return response.data
  },

  async approve(id) {
    const response = await apiClient.post(`/travel-requests/${id}/approve`)
    return response.data
  },

  async cancel(id, reason) {
    const response = await apiClient.post(`/travel-requests/${id}/cancel`, { cancelled_reason: reason })
    return response.data
  }
}

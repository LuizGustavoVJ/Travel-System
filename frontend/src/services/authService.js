import apiClient from '@/api/axios'

export const authService = {
  async login(credentials) {
    const response = await apiClient.post('/auth/login', credentials)
    return response.data
  },

  async register(userData) {
    const response = await apiClient.post('/auth/register', userData)
    return response.data
  },

  async logout() {
    const response = await apiClient.post('/auth/logout')
    return response.data
  },

  async me() {
    const response = await apiClient.get('/auth/me')
    return response.data
  },

  async refreshToken() {
    const response = await apiClient.post('/auth/refresh')
    return response.data
  }
}

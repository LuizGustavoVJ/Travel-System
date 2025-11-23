import axios from 'axios'
import { useAuthStore } from '@/stores/auth'
import router from '@/router'

const apiClient = axios.create({
  baseURL: import.meta.env.VITE_API_URL || 'http://localhost:8080/api',
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json'
  }
})

// Request Interceptor - Adiciona token JWT
apiClient.interceptors.request.use(
  (config) => {
    const authStore = useAuthStore()
    if (authStore.token) {
      config.headers.Authorization = `Bearer ${authStore.token}`
    }
    return config
  },
  (error) => {
    return Promise.reject(error)
  }
)

// Response Interceptor - Trata erros globalmente
apiClient.interceptors.response.use(
  (response) => response,
  async (error) => {
    const authStore = useAuthStore()
    
    // Token expirado ou inválido
    if (error.response?.status === 401 && authStore.isAuthenticated) {
      authStore.logout()
      router.push({ name: 'login', query: { redirect: router.currentRoute.value.fullPath } })
    }
    
    // Forbidden
    if (error.response?.status === 403) {
      router.push({ name: 'forbidden' })
    }
    
    return Promise.reject(error)
  }
)

export default apiClient

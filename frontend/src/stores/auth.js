import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { authService } from '@/services/authService'

export const useAuthStore = defineStore('auth', () => {
  // State
  const token = ref(localStorage.getItem('token') || null)
  const user = ref(JSON.parse(localStorage.getItem('user') || 'null'))

  // Getters
  const isAuthenticated = computed(() => !!token.value)
  const isAdmin = computed(() => user.value?.role === 'admin')

  // Actions
  async function login(credentials) {
    try {
      const data = await authService.login(credentials)
      setAuth(data.access_token, data.user)
      return data
    } catch (error) {
      throw error
    }
  }

  async function register(userData) {
    try {
      const data = await authService.register(userData)
      setAuth(data.access_token, data.user)
      return data
    } catch (error) {
      throw error
    }
  }

  async function logout() {
    try {
      await authService.logout()
    } catch (error) {
      // Ignora erro de logout no backend
    } finally {
      clearAuth()
    }
  }

  async function fetchUser() {
    try {
      const data = await authService.me()
      user.value = data
      localStorage.setItem('user', JSON.stringify(data))
      return data
    } catch (error) {
      clearAuth()
      throw error
    }
  }

  function setAuth(newToken, newUser) {
    token.value = newToken
    user.value = newUser
    localStorage.setItem('token', newToken)
    localStorage.setItem('user', JSON.stringify(newUser))
  }

  function clearAuth() {
    token.value = null
    user.value = null
    localStorage.removeItem('token')
    localStorage.removeItem('user')
  }

  return {
    // State
    token,
    user,
    // Getters
    isAuthenticated,
    isAdmin,
    // Actions
    login,
    register,
    logout,
    fetchUser,
    setAuth,
    clearAuth
  }
})

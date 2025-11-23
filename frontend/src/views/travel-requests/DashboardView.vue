<template>
  <div class="min-h-screen bg-gray-50">
    <nav class="bg-white shadow-sm">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
          <div class="flex items-center">
            <h1 class="text-xl font-bold text-gray-900">Travel System</h1>
          </div>
          <div class="flex items-center space-x-4">
            <span class="text-gray-700">{{ user?.name }}</span>
            <span v-if="isAdmin" class="badge badge-approved">Admin</span>
            <button @click="logout" class="btn btn-secondary">Logout</button>
          </div>
        </div>
      </div>
    </nav>

    <main class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
      <div class="px-4 py-6 sm:px-0">
        <div class="flex justify-between items-center mb-6">
          <h2 class="text-2xl font-bold text-gray-900">Travel Requests</h2>
          <router-link to="/travel-requests/create" class="btn btn-primary">
            New Request
          </router-link>
        </div>

        <div class="card">
          <p class="text-gray-600">
            Dashboard implementation in progress...
          </p>
          <p class="text-sm text-gray-500 mt-2">
            Frontend architecture with Composition API, Pinia, Vue Router, Axios Interceptors, and Clean Architecture is ready!
          </p>
        </div>
      </div>
    </main>
  </div>
</template>

<script setup>
import { useAuth } from '@/composables/useAuth'

const { user, isAdmin, logout } = useAuth()
</script>

<template>
  <div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
      <div>
        <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
          Travel System
        </h2>
        <p class="mt-2 text-center text-sm text-gray-600">
          Sign in to your account
        </p>
      </div>
      
      <form class="mt-8 space-y-6" @submit.prevent="handleSubmit">
        <div v-if="error" class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded">
          {{ error }}
        </div>
        
        <div class="rounded-md shadow-sm space-y-4">
          <div>
            <label for="email" class="label">Email</label>
            <input
              id="email"
              v-model="values.email"
              type="email"
              required
              :class="['input', errors.email && touched.email ? 'input-error' : '']"
              placeholder="your@email.com"
              @blur="handleBlur('email')"
            />
            <p v-if="errors.email && touched.email" class="error-message">
              {{ errors.email }}
            </p>
          </div>
          
          <div>
            <label for="password" class="label">Password</label>
            <input
              id="password"
              v-model="values.password"
              type="password"
              required
              :class="['input', errors.password && touched.password ? 'input-error' : '']"
              placeholder="••••••••"
              @blur="handleBlur('password')"
            />
            <p v-if="errors.password && touched.password" class="error-message">
              {{ errors.password }}
            </p>
          </div>
        </div>

        <div>
          <button
            type="submit"
            :disabled="loading"
            class="btn btn-primary w-full"
          >
            {{ loading ? 'Signing in...' : 'Sign in' }}
          </button>
        </div>
        
        <div class="text-center">
          <router-link to="/register" class="text-blue-600 hover:text-blue-500">
            Don't have an account? Register
          </router-link>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { useAuth } from '@/composables/useAuth'
import { useFormValidation, validators } from '@/composables/useFormValidation'

const { login, loading, error } = useAuth()

const { values, errors, touched, validateAll, handleBlur } = useFormValidation(
  { email: '', password: '' },
  {
    email: [validators.required(), validators.email()],
    password: [validators.required(), validators.minLength(6)]
  }
)

const handleSubmit = async () => {
  if (!validateAll()) return
  
  try {
    await login(values.value)
  } catch (err) {
    // Error handled by useAuth
  }
}
</script>

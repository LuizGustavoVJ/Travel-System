<template>
  <div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
      <div>
        <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
          Create your account
        </h2>
      </div>
      
      <form class="mt-8 space-y-6" @submit.prevent="handleSubmit">
        <div v-if="error" class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded">
          {{ error }}
        </div>
        
        <div class="space-y-4">
          <div>
            <label for="name" class="label">Name</label>
            <input
              id="name"
              v-model="values.name"
              type="text"
              required
              :class="['input', errors.name && touched.name ? 'input-error' : '']"
              @blur="handleBlur('name')"
            />
            <p v-if="errors.name && touched.name" class="error-message">
              {{ errors.name }}
            </p>
          </div>
          
          <div>
            <label for="email" class="label">Email</label>
            <input
              id="email"
              v-model="values.email"
              type="email"
              required
              :class="['input', errors.email && touched.email ? 'input-error' : '']"
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
            {{ loading ? 'Creating account...' : 'Register' }}
          </button>
        </div>
        
        <div class="text-center">
          <router-link to="/login" class="text-blue-600 hover:text-blue-500">
            Already have an account? Sign in
          </router-link>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { useAuth } from '@/composables/useAuth'
import { useFormValidation, validators } from '@/composables/useFormValidation'

const { register, loading, error } = useAuth()

const { values, errors, touched, validateAll, handleBlur } = useFormValidation(
  { name: '', email: '', password: '' },
  {
    name: [validators.required()],
    email: [validators.required(), validators.email()],
    password: [validators.required(), validators.minLength(6)]
  }
)

const handleSubmit = async () => {
  if (!validateAll()) return
  
  try {
    await register(values.value)
  } catch (err) {
    // Error handled by useAuth
  }
}
</script>

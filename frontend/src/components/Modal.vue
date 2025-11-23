<template>
  <Teleport to="body">
    <Transition name="modal">
      <div v-if="show" class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex min-h-screen items-center justify-center p-4">
          <div class="fixed inset-0 bg-black bg-opacity-50 transition-opacity" @click="close"></div>
          <div class="relative bg-white rounded-lg shadow-xl max-w-md w-full p-6">
            <div class="flex justify-between items-start mb-4">
              <h3 class="text-lg font-medium text-gray-900">{{ title }}</h3>
              <button @click="close" class="text-gray-400 hover:text-gray-500">
                <span class="text-2xl">&times;</span>
              </button>
            </div>
            <div class="mb-6">
              <slot></slot>
            </div>
            <div class="flex justify-end space-x-3">
              <button @click="close" class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                {{ cancelText }}
              </button>
              <button @click="confirm" :disabled="loading"
                :class="confirmClass"
                class="px-4 py-2 border border-transparent rounded-md text-sm font-medium text-white disabled:opacity-50">
                {{ loading ? 'Processando...' : confirmText }}
              </button>
            </div>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
const props = defineProps({
  show: { type: Boolean, default: false },
  title: { type: String, required: true },
  confirmText: { type: String, default: 'Confirmar' },
  cancelText: { type: String, default: 'Cancelar' },
  confirmClass: { type: String, default: 'bg-blue-600 hover:bg-blue-700' },
  loading: { type: Boolean, default: false }
})

const emit = defineEmits(['close', 'confirm'])

const close = () => emit('close')
const confirm = () => emit('confirm')
</script>

<style scoped>
.modal-enter-active, .modal-leave-active {
  transition: opacity 0.3s;
}
.modal-enter-from, .modal-leave-to {
  opacity: 0;
}
</style>

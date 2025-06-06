<template>
  <div
    v-if="isOpen"
    class="fixed inset-0 z-50 overflow-y-auto"
    @click.self="handleBackdropClick"
  >
    <!-- Backdrop -->
    <div class="fixed inset-0 bg-black/50 transition-opacity" />
    
    <!-- Modal container -->
    <div class="flex min-h-full items-center justify-center p-4">
      <div
        :class="modalClasses"
        @click.stop
      >
        <!-- Header -->
        <div v-if="$slots.header || title" class="flex items-center justify-between p-6 border-b border-gray-200">
          <div class="flex-1">
            <slot name="header">
              <h3 class="text-lg font-semibold text-gray-900">{{ title }}</h3>
              <p v-if="subtitle" class="mt-1 text-sm text-gray-600">{{ subtitle }}</p>
            </slot>
          </div>
          <button
            v-if="showCloseButton"
            @click="closeModal"
            class="ml-4 text-gray-400 hover:text-gray-600 transition-colors"
          >
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <!-- Body -->
        <div :class="bodyClasses">
          <slot />
        </div>

        <!-- Footer -->
        <div v-if="$slots.footer" class="px-6 py-4 border-t border-gray-200 bg-gray-50">
          <slot name="footer" />
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, watch } from 'vue'

interface Props {
  isOpen: boolean
  title?: string
  subtitle?: string
  size?: 'sm' | 'md' | 'lg' | 'xl' | 'full'
  showCloseButton?: boolean
  closeOnBackdrop?: boolean
  padding?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  size: 'md',
  showCloseButton: true,
  closeOnBackdrop: true,
  padding: true
})

const emit = defineEmits<{
  close: []
  'update:isOpen': [value: boolean]
}>()

const modalClasses = computed(() => {
  const sizeClasses = {
    sm: 'max-w-md',
    md: 'max-w-lg',
    lg: 'max-w-2xl',
    xl: 'max-w-4xl',
    full: 'max-w-7xl mx-4'
  }

  return [
    'relative bg-white rounded-lg shadow-xl transform transition-all',
    'w-full',
    sizeClasses[props.size]
  ].join(' ')
})

const bodyClasses = computed(() => {
  return props.padding ? 'p-6' : ''
})

const closeModal = () => {
  emit('close')
  emit('update:isOpen', false)
}

const handleBackdropClick = () => {
  if (props.closeOnBackdrop) {
    closeModal()
  }
}

// Handle escape key
const handleKeydown = (event: KeyboardEvent) => {
  if (event.key === 'Escape' && props.isOpen) {
    closeModal()
  }
}

// Add/remove event listeners
watch(() => props.isOpen, (isOpen) => {
  if (isOpen) {
    document.addEventListener('keydown', handleKeydown)
    document.body.style.overflow = 'hidden'
  } else {
    document.removeEventListener('keydown', handleKeydown)
    document.body.style.overflow = ''
  }
})
</script>

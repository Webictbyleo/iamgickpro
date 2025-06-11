<template>
  <Teleport to="body">
    <Transition
      enter-active-class="transition duration-300 ease-out"
      enter-from-class="transform translate-x-full opacity-0"
      enter-to-class="transform translate-x-0 opacity-100"
      leave-active-class="transition duration-200 ease-in"
      leave-from-class="transform translate-x-0 opacity-100"
      leave-to-class="transform translate-x-full opacity-0"
    >
      <div
        v-if="visible"
        :class="[
          'fixed top-4 right-4 z-50 max-w-sm w-full bg-white rounded-lg shadow-lg border border-gray-200',
          'dark:bg-gray-800 dark:border-gray-700'
        ]"
      >
        <div class="p-4">
          <div class="flex items-start">
            <div class="flex-shrink-0">
              <!-- Success Icon -->
              <CheckCircleIcon 
                v-if="type === 'success'" 
                class="h-5 w-5 text-green-500"
              />
              <!-- Error Icon -->
              <ExclamationTriangleIcon 
                v-else-if="type === 'error'" 
                class="h-5 w-5 text-red-500"
              />
              <!-- Loading Icon -->
              <div 
                v-else-if="type === 'loading'" 
                class="h-5 w-5 border-2 border-blue-200 border-t-blue-600 rounded-full animate-spin"
              />
              <!-- Info Icon -->
              <InformationCircleIcon 
                v-else 
                class="h-5 w-5 text-blue-500"
              />
            </div>
            
            <div class="ml-3 w-0 flex-1">
              <p class="text-sm font-medium text-gray-900 dark:text-white">
                {{ title }}
              </p>
              <p v-if="message" class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                {{ message }}
              </p>
            </div>
            
            <div class="ml-4 flex-shrink-0 flex">
              <button
                @click="close"
                class="bg-white dark:bg-gray-800 rounded-md inline-flex text-gray-400 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500"
              >
                <XMarkIcon class="h-5 w-5" />
              </button>
            </div>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import {
  CheckCircleIcon,
  ExclamationTriangleIcon,
  InformationCircleIcon,
  XMarkIcon
} from '@heroicons/vue/24/outline'

interface Props {
  type?: 'success' | 'error' | 'loading' | 'info'
  title: string
  message?: string
  duration?: number
  persistent?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  type: 'info',
  duration: 5000,
  persistent: false
})

const emit = defineEmits<{
  close: []
}>()

const visible = ref(true)

const close = () => {
  visible.value = false
  setTimeout(() => {
    emit('close')
  }, 200)
}

onMounted(() => {
  if (!props.persistent && props.duration > 0) {
    setTimeout(() => {
      close()
    }, props.duration)
  }
})
</script>

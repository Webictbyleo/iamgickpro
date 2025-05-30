<template>
  <div class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center p-4 z-50">
    <div class="bg-white rounded-lg shadow-xl max-w-md w-full">
      <div class="px-6 py-4 border-b border-gray-200">
        <h3 class="text-lg font-medium text-gray-900">Auto-refresh Settings</h3>
        <p class="mt-1 text-sm text-gray-500">
          Configure automatic refresh for tracking export progress
        </p>
      </div>
      
      <div class="px-6 py-4 space-y-4">
        <!-- Enable/Disable Toggle -->
        <div class="flex items-center justify-between">
          <label for="auto-refresh-toggle" class="text-sm font-medium text-gray-700">
            Enable auto-refresh
          </label>
          <button
            id="auto-refresh-toggle"
            @click="localEnabled = !localEnabled"
            :class="[
              localEnabled ? 'bg-blue-600' : 'bg-gray-200',
              'relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2'
            ]"
          >
            <span
              :class="[
                localEnabled ? 'translate-x-5' : 'translate-x-0',
                'pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out'
              ]"
            />
          </button>
        </div>
        
        <!-- Interval Selection -->
        <div v-if="localEnabled" class="space-y-2">
          <label for="refresh-interval" class="block text-sm font-medium text-gray-700">
            Refresh interval
          </label>
          <select
            id="refresh-interval"
            v-model="localInterval"
            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
          >
            <option :value="5">Every 5 seconds</option>
            <option :value="10">Every 10 seconds</option>
            <option :value="15">Every 15 seconds</option>
            <option :value="30">Every 30 seconds</option>
            <option :value="60">Every minute</option>
          </select>
          <p class="text-xs text-gray-500">
            More frequent updates will consume more bandwidth
          </p>
        </div>
        
        <!-- Info Box -->
        <div class="bg-blue-50 border border-blue-200 rounded-md p-3">
          <div class="flex">
            <component :is="informationCircle" class="h-5 w-5 text-blue-400 flex-shrink-0" />
            <div class="ml-3">
              <p class="text-sm text-blue-800">
                Auto-refresh will automatically stop when all jobs are completed or failed.
              </p>
            </div>
          </div>
        </div>
      </div>
      
      <div class="px-6 py-4 bg-gray-50 flex justify-end space-x-3 rounded-b-lg">
        <button
          @click="$emit('close')"
          class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
        >
          Cancel
        </button>
        <button
          @click="handleSave"
          class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
        >
          Save Settings
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useIcons } from '@/composables/useIcons'

interface Props {
  isEnabled: boolean
  interval: number
}

interface Emits {
  (e: 'save', enabled: boolean, interval: number): void
  (e: 'close'): void
}

const props = defineProps<Props>()
const emit = defineEmits<Emits>()

const { informationCircle } = useIcons()

const localEnabled = ref(props.isEnabled)
const localInterval = ref(props.interval)

const handleSave = () => {
  emit('save', localEnabled.value, localInterval.value)
}

onMounted(() => {
  localEnabled.value = props.isEnabled
  localInterval.value = props.interval
})
</script>

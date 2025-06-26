<template>
  <TransitionRoot as="template" :show="isOpen">
    <Dialog as="div" class="relative z-50" @close="closeModal">
      <TransitionChild
        as="template"
        enter="ease-out duration-300"
        enter-from="opacity-0"
        enter-to="opacity-100"
        leave="ease-in duration-200"
        leave-from="opacity-100"
        leave-to="opacity-0"
      >
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity backdrop-blur-sm" />
      </TransitionChild>

      <div class="fixed inset-0 z-10 overflow-y-auto">
        <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
          <TransitionChild
            as="template"
            enter="ease-out duration-300"
            enter-from="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            enter-to="opacity-100 translate-y-0 sm:scale-100"
            leave="ease-in duration-200"
            leave-from="opacity-100 translate-y-0 sm:scale-100"
            leave-to="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
          >
            <DialogPanel class="relative transform overflow-hidden rounded-2xl bg-white px-6 pb-6 pt-6 text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-lg">
              <!-- Header -->
              <div class="flex items-center justify-between mb-6">
                <div class="flex items-center space-x-3">
                  <div class="w-10 h-10 bg-gradient-to-br from-violet-500 to-purple-600 rounded-xl flex items-center justify-center shadow-lg">
                    <ArrowDownTrayIcon class="w-5 h-5 text-white" />
                  </div>
                  <div>
                    <DialogTitle as="h3" class="text-xl font-bold text-gray-900">
                      Export Design
                    </DialogTitle>
                    <p class="text-sm text-gray-500 mt-1">
                      {{ design?.title || 'Untitled Design' }}
                    </p>
                  </div>
                </div>
                <button
                  @click="closeModal"
                  class="text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-full p-2 transition-all duration-200"
                >
                  <XMarkIcon class="w-5 h-5" />
                </button>
              </div>

              <!-- Design Preview -->
              <div class="mb-6">
                <div class="bg-gray-50 rounded-xl p-4 border border-gray-200">
                  <div class="flex items-center space-x-4">
                    <div class="w-16 h-16 bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
                      <img
                        v-if="design?.thumbnail"
                        :src="design.thumbnail"
                        :alt="design.title"
                        class="w-full h-full object-cover"
                      />
                      <div v-else class="w-full h-full bg-gray-100 flex items-center justify-center">
                        <PaintBrushIcon class="w-6 h-6 text-gray-400" />
                      </div>
                    </div>
                    <div class="flex-1">
                      <h4 class="font-semibold text-gray-900">{{ design?.title || 'Untitled Design' }}</h4>
                      <p class="text-sm text-gray-600">{{ design?.width }}×{{ design?.height }} pixels</p>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Export Options -->
              <div class="space-y-6">
                <!-- Format Selection -->
                <div>
                  <label class="block text-sm font-semibold text-gray-900 mb-3">Export Format</label>
                  <div class="grid grid-cols-2 gap-3">
                    <button
                      v-for="format in exportFormats"
                      :key="format.type"
                      @click="selectedFormat = format.type"
                      :class="[
                        selectedFormat === format.type
                          ? 'border-violet-500 bg-violet-50 text-violet-700'
                          : 'border-gray-200 hover:border-violet-300 text-gray-700',
                        'flex items-center justify-center p-3 border-2 rounded-lg transition-all duration-200 font-medium'
                      ]"
                    >
                      <span class="mr-2">{{ format.icon }}</span>
                      {{ format.label }}
                    </button>
                  </div>
                </div>

                <!-- Quality Setting -->
                <div>
                  <label class="block text-sm font-semibold text-gray-900 mb-3">
                    Quality: {{ Math.round(selectedQuality * 100) }}%
                  </label>
                  <input
                    v-model="selectedQuality"
                    type="range"
                    min="0.1"
                    max="1"
                    step="0.1"
                    class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer slider"
                  />
                  <div class="flex justify-between text-xs text-gray-500 mt-1">
                    <span>Lower file size</span>
                    <span>Higher quality</span>
                  </div>
                </div>

                <!-- Scale Setting -->
                <div>
                  <label class="block text-sm font-semibold text-gray-900 mb-3">
                    Size: {{ selectedScale }}x ({{ Math.round((design?.width || 800) * selectedScale) }}×{{ Math.round((design?.height || 600) * selectedScale) }})
                  </label>
                  <div class="grid grid-cols-4 gap-2">
                    <button
                      v-for="scale in scaleOptions"
                      :key="scale.value"
                      @click="selectedScale = scale.value"
                      :class="[
                        selectedScale === scale.value
                          ? 'bg-violet-500 text-white'
                          : 'bg-gray-100 text-gray-700 hover:bg-gray-200',
                        'py-2 px-3 rounded-lg text-sm font-medium transition-colors duration-200'
                      ]"
                    >
                      {{ scale.label }}
                    </button>
                  </div>
                </div>
              </div>

              <!-- Export Progress -->
              <div v-if="isExporting" class="mt-6">
                <div class="bg-gray-50 rounded-lg p-4">
                  <div class="flex items-center space-x-3">
                    <div class="animate-spin rounded-full h-5 w-5 border-2 border-violet-500 border-t-transparent"></div>
                    <span class="text-sm text-gray-600">Exporting design...</span>
                  </div>
                  <div class="mt-2 bg-gray-200 rounded-full h-2">
                    <div 
                      class="bg-violet-500 h-2 rounded-full transition-all duration-300"
                      :style="{ width: `${exportProgress}%` }"
                    ></div>
                  </div>
                </div>
              </div>

              <!-- Export Complete -->
              <div v-if="exportComplete" class="mt-6">
                <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                  <div class="flex items-center space-x-3">
                    <CheckCircleIcon class="w-5 h-5 text-green-500" />
                    <span class="text-sm text-green-700 font-medium">Export completed successfully!</span>
                  </div>
                </div>
              </div>

              <!-- Action Buttons -->
              <div class="flex justify-end space-x-3 mt-6 pt-4 border-t border-gray-100">
                <button
                  @click="closeModal"
                  class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors duration-200"
                >
                  Cancel
                </button>
                <button
                  @click="handleExport"
                  :disabled="isExporting"
                  class="px-6 py-2 text-sm font-medium text-white bg-gradient-to-r from-violet-500 to-purple-600 rounded-lg hover:from-violet-600 hover:to-purple-700 disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-200"
                >
                  {{ isExporting ? 'Exporting...' : 'Export Design' }}
                </button>
              </div>
            </DialogPanel>
          </TransitionChild>
        </div>
      </div>
    </Dialog>
  </TransitionRoot>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import {
  Dialog,
  DialogPanel,
  DialogTitle,
  TransitionChild,
  TransitionRoot,
} from '@headlessui/vue'
import {
  ArrowDownTrayIcon,
  XMarkIcon,
  PaintBrushIcon,
  CheckCircleIcon,
} from '@heroicons/vue/24/outline'
import { designRenderer } from '@/editor/sdk/DesignRenderer'
import { designAPI } from '@/services/api'
import type { SearchResult, Design } from '@/types'

interface Props {
  isOpen: boolean
  design: SearchResult | null
}

interface Emits {
  close: []
  exported: [url: string, filename: string]
}

const props = defineProps<Props>()
const emit = defineEmits<Emits>()

// Export state
const selectedFormat = ref('png')
const selectedQuality = ref(0.8)
const selectedScale = ref(1)
const isExporting = ref(false)
const exportProgress = ref(0)
const exportComplete = ref(false)

// Export format options (simplified to what DesignRenderer supports)
const exportFormats = [
  {
    type: 'png',
    label: 'PNG',
    icon: '🖼️',
    description: 'High quality with transparency'
  },
  {
    type: 'jpeg',
    label: 'JPEG',
    icon: '📷',
    description: 'Smaller file size'
  }
]

// Scale options
const scaleOptions = [
  { value: 0.5, label: '0.5x' },
  { value: 1, label: '1x' },
  { value: 2, label: '2x' },
  { value: 3, label: '3x' }
]

// Methods
const closeModal = () => {
  if (!isExporting.value) {
    emit('close')
    resetState()
  }
}

const resetState = () => {
  selectedFormat.value = 'png'
  selectedQuality.value = 0.8
  selectedScale.value = 1
  isExporting.value = false
  exportProgress.value = 0
  exportComplete.value = false
}

const handleExport = async () => {
  if (!props.design || isExporting.value) return

  try {
    isExporting.value = true
    exportProgress.value = 0
    exportComplete.value = false

    // Simulate progress updates
    const progressInterval = setInterval(() => {
      exportProgress.value += 10
      if (exportProgress.value >= 90) {
        clearInterval(progressInterval)
      }
    }, 100)

    let designData: Design

    try {
      // Try to fetch the complete design data from the API
      const response = await designAPI.getDesign(props.design.id)
      if (response.data?.data) {
        designData = response.data.data
      } else {
        throw new Error('Failed to fetch design data')
      }
    } catch (error) {
      console.warn('Could not fetch full design data, using placeholder:', error)
      // Fallback to creating a simple design structure
      designData = {
        id: props.design.id,
        name: props.design.title || 'Untitled Design',
        title: props.design.title || 'Untitled Design',
        width: props.design.width || 800,
        height: props.design.height || 600,
        data: {
          backgroundColor: '#ffffff',
          background: { type: 'solid', color: '#ffffff' }
        },
        layers: [],
        userId: '',
        projectId: '',
        isPublic: false,
        createdAt: props.design.created_at,
        updatedAt: props.design.created_at
      } as Design
    }

    // Generate the export using DesignRenderer
    const blob = await designRenderer.generatePreviewBlob(designData, {
      width: designData.width * selectedScale.value,
      height: designData.height * selectedScale.value,
      format: selectedFormat.value as 'png' | 'jpeg',
      quality: selectedQuality.value,
      background: designData.data?.background?.color || '#ffffff'
    })

    // Complete progress
    clearInterval(progressInterval)
    exportProgress.value = 100

    // Create download URL
    const url = URL.createObjectURL(blob)
    const timestamp = new Date().toISOString().slice(0, 19).replace(/:/g, '-')
    const filename = `${props.design.title || 'design'}-${timestamp}.${selectedFormat.value}`

    // Trigger download
    const link = document.createElement('a')
    link.href = url
    link.download = filename
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)

    // Clean up URL
    URL.revokeObjectURL(url)

    // Show completion state
    exportComplete.value = true
    
    // Emit exported event
    emit('exported', url, filename)

    // Auto-close after 2 seconds
    setTimeout(() => {
      closeModal()
    }, 2000)

  } catch (error) {
    console.error('Export failed:', error)
    // You could show an error state here
  } finally {
    isExporting.value = false
  }
}

// Watch for modal opening to reset state
watch(() => props.isOpen, (newValue) => {
  if (newValue) {
    resetState()
  }
})
</script>

<style scoped>
.slider::-webkit-slider-thumb {
  appearance: none;
  width: 20px;
  height: 20px;
  border-radius: 50%;
  background: #8b5cf6;
  cursor: pointer;
  border: 2px solid #ffffff;
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.2);
}

.slider::-moz-range-thumb {
  width: 20px;
  height: 20px;
  border-radius: 50%;
  background: #8b5cf6;
  cursor: pointer;
  border: 2px solid #ffffff;
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.2);
}
</style>

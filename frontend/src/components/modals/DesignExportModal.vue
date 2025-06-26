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
            <DialogPanel class="relative transform overflow-hidden rounded-2xl bg-white px-4 pb-4 pt-5 text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-2xl sm:p-6">
              <!-- Header -->
              <div class="flex items-center justify-between mb-6">
                <div class="flex items-center space-x-3">
                  <div class="w-10 h-10 bg-gradient-to-br from-violet-500 to-purple-600 rounded-xl flex items-center justify-center">
                    <ArrowDownTrayIcon class="w-5 h-5 text-white" />
                  </div>
                  <div>
                    <DialogTitle as="h3" class="text-xl font-bold text-gray-900">
                      Export Design
                    </DialogTitle>
                    <p class="text-sm text-gray-500 mt-1">{{ design?.title || 'Untitled Design' }}</p>
                  </div>
                </div>
                <button
                  @click="closeModal"
                  class="text-gray-400 hover:text-gray-600 transition-colors duration-200"
                >
                  <XMarkIcon class="w-6 h-6" />
                </button>
              </div>

              <!-- Design Preview -->
              <div class="mb-6">
                <div class="bg-gray-50 rounded-xl p-4 border border-gray-200">
                  <div class="flex items-center space-x-4">
                    <div class="w-20 h-20 bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
                      <img
                        v-if="design?.thumbnail"
                        :src="design.thumbnail"
                        :alt="design.title"
                        class="w-full h-full object-cover"
                      />
                      <div v-else class="w-full h-full bg-gray-100 flex items-center justify-center">
                        <PaintBrushIcon class="w-8 h-8 text-gray-400" />
                      </div>
                    </div>
                    <div class="flex-1">
                      <h4 class="font-semibold text-gray-900">{{ design?.title || 'Untitled Design' }}</h4>
                      <p class="text-sm text-gray-500">{{ design?.description || 'No description available' }}</p>
                      <div class="flex items-center space-x-4 mt-2 text-xs text-gray-400">
                        <span>{{ formatDate(design?.created_at || new Date().toISOString()) }}</span>
                        <span>•</span>
                        <span>{{ design?.author || 'Unknown Author' }}</span>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Export Options -->
              <div class="space-y-6">
                <!-- Format Selection -->
                <div>
                  <label class="block text-sm font-semibold text-gray-900 mb-3">Export Format</label>
                  <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                    <button
                      v-for="format in exportFormats"
                      :key="format.type"
                      @click="selectedFormat = format.type"
                      :disabled="!isFormatAvailable(format.type)"
                      :class="[
                        selectedFormat === format.type
                          ? 'border-violet-500 bg-violet-50 text-violet-700'
                          : isFormatAvailable(format.type)
                          ? 'border-gray-200 hover:border-gray-300 text-gray-700'
                          : 'border-gray-100 bg-gray-50 text-gray-400 cursor-not-allowed',
                        'relative flex flex-col items-center p-4 border-2 rounded-xl transition-all duration-200',
                        isFormatAvailable(format.type) ? 'hover:shadow-md' : ''
                      ]"
                    >
                      <span class="text-2xl mb-2">{{ format.icon }}</span>
                      <span class="font-medium text-sm">{{ format.label }}</span>
                      <span class="text-xs text-gray-500 mt-1">{{ format.description }}</span>
                      
                      <!-- Animation/Video Required Badge -->
                      <div
                        v-if="!isFormatAvailable(format.type) && ['gif', 'mp4'].includes(format.type)"
                        class="absolute -top-1 -right-1 bg-amber-100 text-amber-600 text-xs px-1.5 py-0.5 rounded-md font-medium"
                      >
                        {{ format.type === 'gif' ? 'Animation' : 'Video' }} Required
                      </div>
                      
                      <!-- Selected indicator -->
                      <div
                        v-if="selectedFormat === format.type"
                        class="absolute -top-2 -right-2 w-6 h-6 bg-violet-500 rounded-full flex items-center justify-center"
                      >
                        <CheckIcon class="w-4 h-4 text-white" />
                      </div>
                    </button>
                  </div>
                </div>

                <!-- Quality/Size Settings -->
                <div v-if="['png', 'jpg'].includes(selectedFormat)">
                  <label class="block text-sm font-semibold text-gray-900 mb-3">Quality & Size</label>
                  <div class="space-y-4">
                    <!-- Quality Preset -->
                    <div>
                      <label class="block text-xs font-medium text-gray-700 mb-2">Quality Preset</label>
                      <select
                        v-model="exportSettings.quality"
                        class="block w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-violet-500/20 focus:border-violet-500"
                      >
                        <option value="web">Web (72 DPI)</option>
                        <option value="print">Print (300 DPI)</option>
                        <option value="high">High Quality (600 DPI)</option>
                      </select>
                    </div>

                    <!-- Custom Dimensions -->
                    <div class="grid grid-cols-2 gap-4">
                      <div>
                        <label class="block text-xs font-medium text-gray-700 mb-2">Width (px)</label>
                        <input
                          v-model.number="exportSettings.width"
                          type="number"
                          min="1"
                          max="10000"
                          class="block w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-violet-500/20 focus:border-violet-500"
                        />
                      </div>
                      <div>
                        <label class="block text-xs font-medium text-gray-700 mb-2">Height (px)</label>
                        <input
                          v-model.number="exportSettings.height"
                          type="number"
                          min="1"
                          max="10000"
                          class="block w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-violet-500/20 focus:border-violet-500"
                        />
                      </div>
                    </div>

                    <!-- Maintain Aspect Ratio -->
                    <div class="flex items-center">
                      <input
                        id="maintain-ratio"
                        v-model="exportSettings.maintainAspectRatio"
                        type="checkbox"
                        class="h-4 w-4 text-violet-600 focus:ring-violet-500 border-gray-300 rounded"
                      />
                      <label for="maintain-ratio" class="ml-2 block text-sm text-gray-700">
                        Maintain aspect ratio
                      </label>
                    </div>
                  </div>
                </div>

                <!-- Background Options -->
                <div v-if="['png', 'jpg'].includes(selectedFormat)">
                  <label class="block text-sm font-semibold text-gray-900 mb-3">Background</label>
                  <div class="grid grid-cols-3 gap-3">
                    <button
                      v-for="bg in backgroundOptions"
                      :key="bg.type"
                      @click="exportSettings.background = bg.type"
                      :class="[
                        exportSettings.background === bg.type
                          ? 'border-violet-500 bg-violet-50'
                          : 'border-gray-200 hover:border-gray-300',
                        'relative flex flex-col items-center p-3 border-2 rounded-lg transition-all duration-200'
                      ]"
                    >
                      <div :class="bg.preview" class="w-8 h-8 rounded mb-2"></div>
                      <span class="text-sm font-medium">{{ bg.label }}</span>
                      
                      <!-- Selected indicator -->
                      <div
                        v-if="exportSettings.background === bg.type"
                        class="absolute -top-1 -right-1 w-5 h-5 bg-violet-500 rounded-full flex items-center justify-center"
                      >
                        <CheckIcon class="w-3 h-3 text-white" />
                      </div>
                    </button>
                  </div>
                </div>

                <!-- Animation/Video Settings -->
                <div v-if="['gif', 'mp4'].includes(selectedFormat) && (design?.hasAnimation || design?.isVideo)">
                  <label class="block text-sm font-semibold text-gray-900 mb-3">
                    {{ selectedFormat === 'gif' ? 'Animation Settings' : 'Video Settings' }}
                  </label>
                  <div class="space-y-4">
                    <!-- Duration Info -->
                    <div v-if="design?.duration" class="bg-blue-50 border border-blue-200 rounded-lg p-3">
                      <div class="flex items-center space-x-2">
                        <div class="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center">
                          <span class="text-blue-600">⏱️</span>
                        </div>
                        <div>
                          <p class="text-sm font-medium text-blue-900">
                            {{ selectedFormat === 'gif' ? 'Animation' : 'Video' }} Duration
                          </p>
                          <p class="text-xs text-blue-700">{{ formatDuration(design.duration) }}</p>
                        </div>
                      </div>
                    </div>

                    <!-- GIF Settings -->
                    <div v-if="selectedFormat === 'gif'" class="grid grid-cols-2 gap-4">
                      <div>
                        <label class="block text-xs font-medium text-gray-700 mb-2">Frame Rate (FPS)</label>
                        <select
                          v-model="exportSettings.frameRate"
                          class="block w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-violet-500/20 focus:border-violet-500"
                        >
                          <option value="12">12 FPS (Smaller file)</option>
                          <option value="24">24 FPS (Smooth)</option>
                          <option value="30">30 FPS (High quality)</option>
                        </select>
                      </div>
                      <div>
                        <label class="block text-xs font-medium text-gray-700 mb-2">Loop Count</label>
                        <select
                          v-model="exportSettings.loopCount"
                          class="block w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-violet-500/20 focus:border-violet-500"
                        >
                          <option value="infinite">Infinite Loop</option>
                          <option value="1">Play Once</option>
                          <option value="3">3 Times</option>
                          <option value="5">5 Times</option>
                        </select>
                      </div>
                    </div>

                    <!-- MP4 Settings -->
                    <div v-if="selectedFormat === 'mp4'" class="grid grid-cols-2 gap-4">
                      <div>
                        <label class="block text-xs font-medium text-gray-700 mb-2">Video Quality</label>
                        <select
                          v-model="exportSettings.videoQuality"
                          class="block w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-violet-500/20 focus:border-violet-500"
                        >
                          <option value="720p">720p (HD)</option>
                          <option value="1080p">1080p (Full HD)</option>
                          <option value="1440p">1440p (2K)</option>
                          <option value="2160p">2160p (4K)</option>
                        </select>
                      </div>
                      <div>
                        <label class="block text-xs font-medium text-gray-700 mb-2">Frame Rate (FPS)</label>
                        <select
                          v-model="exportSettings.frameRate"
                          class="block w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-violet-500/20 focus:border-violet-500"
                        >
                          <option value="24">24 FPS (Cinema)</option>
                          <option value="30">30 FPS (Standard)</option>
                          <option value="60">60 FPS (Smooth)</option>
                        </select>
                      </div>
                    </div>

                    <!-- Compression Settings -->
                    <div class="space-y-3">
                      <div class="flex items-center justify-between">
                        <label class="text-xs font-medium text-gray-700">
                          {{ selectedFormat === 'gif' ? 'File Size Optimization' : 'Video Compression' }}
                        </label>
                        <span class="text-xs text-gray-500">
                          {{ exportSettings.compression }}%
                        </span>
                      </div>
                      <input
                        v-model.number="exportSettings.compression"
                        type="range"
                        min="10"
                        max="100"
                        step="10"
                        class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer"
                      />
                      <div class="flex justify-between text-xs text-gray-500">
                        <span>Smaller file</span>
                        <span>Better quality</span>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Export Progress -->
                <div v-if="isExporting" class="bg-blue-50 border border-blue-200 rounded-xl p-4">
                  <div class="flex items-center space-x-3">
                    <div class="w-8 h-8 border-2 border-blue-600 border-t-transparent rounded-full animate-spin"></div>
                    <div>
                      <p class="font-medium text-blue-900">Exporting your design...</p>
                      <p class="text-sm text-blue-700">{{ exportProgress }}% complete</p>
                    </div>
                  </div>
                  <div class="mt-3 bg-white rounded-full overflow-hidden">
                    <div
                      class="h-2 bg-gradient-to-r from-blue-500 to-violet-500 transition-all duration-300"
                      :style="{ width: `${exportProgress}%` }"
                    ></div>
                  </div>
                </div>

                <!-- Export Success -->
                <div v-if="exportComplete" class="bg-green-50 border border-green-200 rounded-xl p-4">
                  <div class="flex items-center space-x-3">
                    <div class="w-8 h-8 bg-green-500 rounded-full flex items-center justify-center">
                      <CheckIcon class="w-5 h-5 text-white" />
                    </div>
                    <div>
                      <p class="font-medium text-green-900">Export completed successfully!</p>
                      <p class="text-sm text-green-700">Your design has been exported and is ready for download.</p>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Actions -->
              <div class="flex items-center justify-end space-x-3 mt-8 pt-6 border-t border-gray-200">
                <button
                  @click="closeModal"
                  class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-violet-500/20 transition-colors duration-200"
                  :disabled="isExporting"
                >
                  {{ exportComplete ? 'Close' : 'Cancel' }}
                </button>
                
                <button
                  v-if="exportComplete"
                  @click="downloadExport"
                  class="px-6 py-2 text-sm font-medium text-white bg-gradient-to-r from-green-600 to-emerald-600 rounded-lg hover:from-green-700 hover:to-emerald-700 focus:outline-none focus:ring-2 focus:ring-green-500/20 transition-all duration-200 flex items-center space-x-2"
                >
                  <ArrowDownTrayIcon class="w-4 h-4" />
                  <span>Download</span>
                </button>
                
                <button
                  v-else
                  @click="startExport"
                  :disabled="isExporting"
                  class="px-6 py-2 text-sm font-medium text-white bg-gradient-to-r from-violet-600 to-purple-600 rounded-lg hover:from-violet-700 hover:to-purple-700 focus:outline-none focus:ring-2 focus:ring-violet-500/20 transition-all duration-200 flex items-center space-x-2 disabled:opacity-50 disabled:cursor-not-allowed"
                >
                  <ArrowDownTrayIcon class="w-4 h-4" />
                  <span>{{ isExporting ? 'Exporting...' : 'Export Design' }}</span>
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
  XMarkIcon,
  ArrowDownTrayIcon,
  PaintBrushIcon,
  CheckIcon
} from '@heroicons/vue/24/outline'

interface SearchResult {
  id: string
  type: 'design' | 'template' | 'media' | 'export'
  title: string
  description?: string
  thumbnail?: string
  url?: string
  author?: string
  created_at: string
  isPremium?: boolean
  exportStatus?: 'pending' | 'processing' | 'completed' | 'failed'
  hasAnimation?: boolean
  isVideo?: boolean
  duration?: number // in seconds for videos/animations
}

interface ExportSettings {
  quality: 'web' | 'print' | 'high'
  width: number
  height: number
  maintainAspectRatio: boolean
  background: 'transparent' | 'white' | 'custom'
  customBackground?: string
  // Animation/Video settings
  frameRate?: number
  loopCount?: string | number
  videoQuality?: string
  compression?: number
}

interface ExportFormat {
  type: string
  label: string
  description: string
  icon: string
}

interface BackgroundOption {
  type: 'transparent' | 'white' | 'custom'
  label: string
  preview: string
}

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
const isExporting = ref(false)
const exportProgress = ref(0)
const exportComplete = ref(false)
const exportUrl = ref('')

// Export settings
const exportSettings = ref<ExportSettings>({
  quality: 'web',
  width: 1920,
  height: 1080,
  maintainAspectRatio: true,
  background: 'transparent',
  frameRate: 24,
  loopCount: 'infinite',
  videoQuality: '1080p',
  compression: 80
})

// Export formats
const exportFormats = computed<ExportFormat[]>(() => {
  const baseFormats: ExportFormat[] = [
    { type: 'png', label: 'PNG', description: 'Transparent', icon: '🖼️' },
    { type: 'jpg', label: 'JPG', description: 'Compressed', icon: '📷' },
    { type: 'svg', label: 'SVG', description: 'Vector', icon: '📐' },
    { type: 'pdf', label: 'PDF', description: 'Document', icon: '📄' }
  ]

  // Add animation formats if the design has animation or is a video
  if (props.design?.hasAnimation || props.design?.isVideo) {
    baseFormats.push(
      { type: 'gif', label: 'GIF', description: 'Animated', icon: '🎬' },
      { type: 'mp4', label: 'MP4', description: 'Video', icon: '🎥' }
    )
  }

  return baseFormats
})

// Background options
const backgroundOptions: BackgroundOption[] = [
  { type: 'transparent', label: 'Transparent', preview: 'bg-gradient-to-br from-gray-100 to-gray-200 opacity-50' },
  { type: 'white', label: 'White', preview: 'bg-white border border-gray-200' },
  { type: 'custom', label: 'Custom', preview: 'bg-gradient-to-br from-violet-400 to-purple-500' }
]

// Methods
const isFormatAvailable = (format: string): boolean => {
  // Basic formats are always available
  if (['png', 'jpg', 'svg', 'pdf'].includes(format)) {
    return true
  }
  
  // Animation formats (GIF, MP4) are only available for designs with animation or video
  if (['gif', 'mp4'].includes(format)) {
    return !!(props.design?.hasAnimation || props.design?.isVideo)
  }
  
  return false
}

const closeModal = () => {
  if (!isExporting.value) {
    resetModal()
    emit('close')
  }
}

const resetModal = () => {
  selectedFormat.value = 'png'
  isExporting.value = false
  exportProgress.value = 0
  exportComplete.value = false
  exportUrl.value = ''
  exportSettings.value = {
    quality: 'web',
    width: 1920,
    height: 1080,
    maintainAspectRatio: true,
    background: 'transparent',
    frameRate: 24,
    loopCount: 'infinite',
    videoQuality: '1080p',
    compression: 80
  }
}

const startExport = async () => {
  if (!props.design) return

  isExporting.value = true
  exportProgress.value = 0
  exportComplete.value = false

  try {
    // Simulate export progress
    const progressInterval = setInterval(() => {
      exportProgress.value += Math.random() * 15
      if (exportProgress.value >= 100) {
        exportProgress.value = 100
        clearInterval(progressInterval)
        
        // Complete export
        setTimeout(() => {
          isExporting.value = false
          exportComplete.value = true
          
          // Generate mock download URL
          const timestamp = Date.now()
          const filename = `${props.design?.title || 'design'}-${timestamp}.${selectedFormat.value}`
          exportUrl.value = `https://example.com/exports/${filename}`
          
          emit('exported', exportUrl.value, filename)
        }, 500)
      }
    }, 200)

    // In a real implementation, you would call the export API here
    // const response = await exportAPI.exportDesign(props.design.id, {
    //   format: selectedFormat.value,
    //   settings: exportSettings.value
    // })
    
  } catch (error) {
    console.error('Export failed:', error)
    isExporting.value = false
    // Handle error state
  }
}

const downloadExport = () => {
  if (exportUrl.value) {
    // Create temporary download link
    const link = document.createElement('a')
    link.href = exportUrl.value
    link.download = `${props.design?.title || 'design'}.${selectedFormat.value}`
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
    
    // Close modal after download starts
    setTimeout(() => {
      closeModal()
    }, 1000)
  }
}

const formatDate = (dateString: string): string => {
  const date = new Date(dateString)
  return new Intl.DateTimeFormat('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  }).format(date)
}

const formatDuration = (seconds: number): string => {
  const minutes = Math.floor(seconds / 60)
  const remainingSeconds = seconds % 60
  
  if (minutes > 0) {
    return `${minutes}m ${remainingSeconds}s`
  }
  return `${remainingSeconds}s`
}

// Watch for modal close to reset state
watch(() => props.isOpen, (newValue) => {
  if (!newValue) {
    setTimeout(resetModal, 300) // Wait for close animation
  }
})

// Watch aspect ratio setting
watch(() => exportSettings.value.maintainAspectRatio, (maintain) => {
  if (maintain && props.design) {
    // Calculate aspect ratio and adjust height
    const aspectRatio = 16/9 // Default aspect ratio, in real app this would come from design
    exportSettings.value.height = Math.round(exportSettings.value.width / aspectRatio)
  }
})

// Watch width changes when maintaining aspect ratio
watch(() => exportSettings.value.width, (newWidth) => {
  if (exportSettings.value.maintainAspectRatio && props.design) {
    const aspectRatio = 16/9 // Default aspect ratio
    exportSettings.value.height = Math.round(newWidth / aspectRatio)
  }
})
</script>

<style scoped>
/* Custom scrollbar for the modal */
::-webkit-scrollbar {
  width: 4px;
}

::-webkit-scrollbar-track {
  background: #f1f1f1;
  border-radius: 4px;
}

::-webkit-scrollbar-thumb {
  background: #c1c1c1;
  border-radius: 4px;
}

::-webkit-scrollbar-thumb:hover {
  background: #a1a1a1;
}
</style>

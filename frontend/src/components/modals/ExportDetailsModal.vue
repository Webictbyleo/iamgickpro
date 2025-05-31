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
        <div class="fixed inset-0 bg-black bg-opacity-75 transition-opacity backdrop-blur-sm" />
      </TransitionChild>

      <div class="fixed inset-0 z-10 overflow-y-auto">
        <div class="flex min-h-full items-center justify-center p-4">
          <TransitionChild
            as="template"
            enter="ease-out duration-300"
            enter-from="opacity-0 scale-95"
            enter-to="opacity-100 scale-100"
            leave="ease-in duration-200"
            leave-from="opacity-100 scale-100"
            leave-to="opacity-0 scale-95"
          >
            <DialogPanel class="relative max-w-2xl w-full bg-white rounded-2xl shadow-2xl overflow-hidden">
              <!-- Header -->
              <div class="flex items-center justify-between p-6 border-b border-gray-200 bg-gradient-to-r from-gray-50 to-gray-100">
                <div class="flex items-center space-x-4">
                  <div class="w-12 h-12 bg-gradient-to-br from-indigo-500 to-violet-600 rounded-xl flex items-center justify-center shadow-lg">
                    <component :is="getStatusIcon()" class="w-6 h-6 text-white" />
                  </div>
                  <div>
                    <DialogTitle as="h3" class="text-xl font-bold text-gray-900">
                      Export Details
                    </DialogTitle>
                    <p class="text-sm text-gray-600 mt-1">
                      Job #{{ exportJob?.id || 'Unknown' }}
                    </p>
                  </div>
                </div>
                <button
                  @click="closeModal"
                  class="p-2 text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded-lg transition-all duration-200"
                  title="Close"
                >
                  <XMarkIcon class="w-5 h-5" />
                </button>
              </div>

              <!-- Content -->
              <div class="p-6">
                <!-- Export Preview -->
                <div v-if="exportJob?.thumbnail" class="mb-6">
                  <div class="relative w-full h-48 bg-gray-100 rounded-xl overflow-hidden">
                    <img
                      :src="exportJob.thumbnail"
                      :alt="exportJob.title"
                      class="w-full h-full object-cover"
                    />
                    <div class="absolute inset-0 bg-gradient-to-t from-black/50 to-transparent"></div>
                    <div class="absolute bottom-4 left-4 text-white">
                      <h4 class="text-lg font-semibold">{{ exportJob.title }}</h4>
                      <p v-if="exportJob.description" class="text-sm text-gray-200">{{ exportJob.description }}</p>
                    </div>
                  </div>
                </div>

                <!-- Status Section -->
                <div class="mb-6">
                  <div class="flex items-center justify-between mb-4">
                    <h4 class="text-lg font-semibold text-gray-900">Export Status</h4>
                    <div class="flex items-center space-x-2">
                      <div class="w-3 h-3 rounded-full" :class="getStatusDotClass()"></div>
                      <span class="text-sm font-medium" :class="getStatusTextClass()">
                        {{ getStatusLabel() }}
                      </span>
                    </div>
                  </div>

                  <!-- Progress Bar for processing -->
                  <div v-if="exportJob?.exportStatus === 'processing'" class="mb-4">
                    <div class="flex justify-between text-sm text-gray-600 mb-2">
                      <span>Processing...</span>
                      <span>{{ progress }}%</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                      <div 
                        class="bg-indigo-600 h-2 rounded-full transition-all duration-500"
                        :style="{ width: `${progress}%` }"
                      ></div>
                    </div>
                  </div>

                  <!-- Status Description -->
                  <p class="text-sm text-gray-600">{{ getStatusDescription() }}</p>
                </div>

                <!-- Export Information -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                  <!-- File Information -->
                  <div class="space-y-4">
                    <h4 class="text-lg font-semibold text-gray-900">File Information</h4>
                    <div class="space-y-3">
                      <div class="flex justify-between">
                        <span class="text-sm font-medium text-gray-700">Format:</span>
                        <span class="text-sm text-gray-600 font-mono">{{ getFileFormat() }}</span>
                      </div>
                      <div class="flex justify-between">
                        <span class="text-sm font-medium text-gray-700">Quality:</span>
                        <span class="text-sm text-gray-600">{{ getQualityLabel() }}</span>
                      </div>
                      <div v-if="exportJob?.dimensions" class="flex justify-between">
                        <span class="text-sm font-medium text-gray-700">Dimensions:</span>
                        <span class="text-sm text-gray-600">{{ exportJob.dimensions.width }}×{{ exportJob.dimensions.height }}px</span>
                      </div>
                      <div v-if="fileSize" class="flex justify-between">
                        <span class="text-sm font-medium text-gray-700">File Size:</span>
                        <span class="text-sm text-gray-600">{{ formatFileSize(fileSize) }}</span>
                      </div>
                    </div>
                  </div>

                  <!-- Job Information -->
                  <div class="space-y-4">
                    <h4 class="text-lg font-semibold text-gray-900">Job Information</h4>
                    <div class="space-y-3">
                      <div class="flex justify-between">
                        <span class="text-sm font-medium text-gray-700">Created:</span>
                        <span class="text-sm text-gray-600">{{ formatDate(exportJob?.created_at) }}</span>
                      </div>
                      <div v-if="exportJob?.exportStatus === 'completed'" class="flex justify-between">
                        <span class="text-sm font-medium text-gray-700">Completed:</span>
                        <span class="text-sm text-gray-600">{{ formatDate(completedAt) }}</span>
                      </div>
                      <div class="flex justify-between">
                        <span class="text-sm font-medium text-gray-700">Author:</span>
                        <span class="text-sm text-gray-600">{{ exportJob?.author || 'Unknown' }}</span>
                      </div>
                      <div v-if="exportJob?.exportStatus === 'processing'" class="flex justify-between">
                        <span class="text-sm font-medium text-gray-700">Est. Time:</span>
                        <span class="text-sm text-gray-600">{{ estimatedTime }}</span>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Error Details (if failed) -->
                <div v-if="exportJob?.exportStatus === 'failed'" class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg">
                  <div class="flex items-start space-x-3">
                    <ExclamationTriangleIcon class="w-5 h-5 text-red-500 mt-0.5" />
                    <div>
                      <h4 class="text-sm font-medium text-red-800">Export Failed</h4>
                      <p class="text-sm text-red-700 mt-1">{{ errorMessage }}</p>
                    </div>
                  </div>
                </div>

                <!-- Additional Options (for completed exports) -->
                <div v-if="exportJob?.exportStatus === 'completed'" class="border-t border-gray-200 pt-6">
                  <h4 class="text-lg font-semibold text-gray-900 mb-4">Additional Actions</h4>
                  <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <button
                      @click="shareExport"
                      class="flex items-center justify-center px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors duration-200"
                    >
                      <ShareIcon class="w-4 h-4 mr-2" />
                      Share
                    </button>
                    <button
                      @click="duplicateExport"
                      class="flex items-center justify-center px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors duration-200"
                    >
                      <DocumentDuplicateIcon class="w-4 h-4 mr-2" />
                      Re-export
                    </button>
                    <button
                      @click="editOriginal"
                      class="flex items-center justify-center px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors duration-200"
                    >
                      <PencilIcon class="w-4 h-4 mr-2" />
                      Edit Original
                    </button>
                  </div>
                </div>
              </div>

              <!-- Footer Actions -->
              <div class="flex items-center justify-end space-x-3 px-6 py-4 bg-gray-50 border-t border-gray-200">
                <button
                  @click="closeModal"
                  class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors duration-200"
                >
                  Close
                </button>
                <button
                  v-if="exportJob?.exportStatus === 'completed'"
                  @click="downloadExport"
                  class="flex items-center px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 transition-colors duration-200"
                >
                  <ArrowDownTrayIcon class="w-4 h-4 mr-2" />
                  Download
                </button>
                <button
                  v-else-if="exportJob?.exportStatus === 'failed'"
                  @click="retryExport"
                  class="flex items-center px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700 transition-colors duration-200"
                >
                  <ArrowPathIcon class="w-4 h-4 mr-2" />
                  Retry Export
                </button>
                <button
                  v-else-if="exportJob?.exportStatus === 'processing'"
                  @click="cancelExport"
                  class="flex items-center px-4 py-2 text-sm font-medium text-white bg-gray-600 rounded-lg hover:bg-gray-700 transition-colors duration-200"
                >
                  <XMarkIcon class="w-4 h-4 mr-2" />
                  Cancel
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
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { Dialog, DialogPanel, DialogTitle, TransitionChild, TransitionRoot } from '@headlessui/vue'
import {
  XMarkIcon,
  ArrowDownTrayIcon,
  ExclamationTriangleIcon,
  CheckCircleIcon,
  ClockIcon,
  ArrowPathIcon,
  ShareIcon,
  DocumentDuplicateIcon,
  PencilIcon,
  Cog6ToothIcon
} from '@heroicons/vue/24/outline'

// Types
interface ExportJob {
  id: string
  title: string
  description?: string
  thumbnail?: string
  exportStatus: 'pending' | 'processing' | 'completed' | 'failed'
  created_at: string
  author?: string
  url?: string
  dimensions?: {
    width: number
    height: number
  }
  format?: string
  quality?: string
  metadata?: Record<string, any>
}

interface Props {
  isOpen: boolean
  exportJob: ExportJob | null
}

interface Emits {
  close: []
  download: [job: ExportJob]
  retry: [job: ExportJob]
  cancel: [job: ExportJob]
  share: [job: ExportJob]
  duplicate: [job: ExportJob]
  editOriginal: [job: ExportJob]
}

const props = defineProps<Props>()
const emit = defineEmits<Emits>()

// Refs
const progress = ref(0)
const fileSize = ref<number | null>(null)
const completedAt = ref<string>('')
const estimatedTime = ref('2-3 minutes')
const errorMessage = ref('An unexpected error occurred during export. Please try again.')

// Computed properties
const getStatusIcon = () => {
  switch (props.exportJob?.exportStatus) {
    case 'completed':
      return CheckCircleIcon
    case 'processing':
      return Cog6ToothIcon
    case 'failed':
      return ExclamationTriangleIcon
    default:
      return ClockIcon
  }
}

const getStatusDotClass = () => {
  switch (props.exportJob?.exportStatus) {
    case 'completed':
      return 'bg-green-500'
    case 'processing':
      return 'bg-blue-500 animate-pulse'
    case 'failed':
      return 'bg-red-500'
    default:
      return 'bg-yellow-500'
  }
}

const getStatusTextClass = () => {
  switch (props.exportJob?.exportStatus) {
    case 'completed':
      return 'text-green-700'
    case 'processing':
      return 'text-blue-700'
    case 'failed':
      return 'text-red-700'
    default:
      return 'text-yellow-700'
  }
}

const getStatusLabel = () => {
  switch (props.exportJob?.exportStatus) {
    case 'completed':
      return 'Export Completed'
    case 'processing':
      return 'Processing Export'
    case 'failed':
      return 'Export Failed'
    default:
      return 'Export Pending'
  }
}

const getStatusDescription = () => {
  switch (props.exportJob?.exportStatus) {
    case 'completed':
      return 'Your export has been successfully generated and is ready for download.'
    case 'processing':
      return 'Your export is currently being processed. This may take a few minutes depending on the complexity.'
    case 'failed':
      return 'The export process encountered an error. You can retry the export or contact support if the issue persists.'
    default:
      return 'Your export request is in the queue and will begin processing shortly.'
  }
}

const getFileFormat = () => {
  return props.exportJob?.format?.toUpperCase() || 'PNG'
}

const getQualityLabel = () => {
  const quality = props.exportJob?.quality || 'high'
  const qualityMap: Record<string, string> = {
    low: 'Low (72 DPI)',
    medium: 'Medium (150 DPI)',
    high: 'High (300 DPI)',
    ultra: 'Ultra (600 DPI)'
  }
  return qualityMap[quality] || 'High (300 DPI)'
}

// Methods
const formatDate = (dateString?: string) => {
  if (!dateString) return 'Unknown'
  const date = new Date(dateString)
  return date.toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

const formatFileSize = (bytes: number) => {
  const units = ['B', 'KB', 'MB', 'GB']
  let size = bytes
  let unitIndex = 0
  
  while (size >= 1024 && unitIndex < units.length - 1) {
    size /= 1024
    unitIndex++
  }
  
  return `${size.toFixed(1)} ${units[unitIndex]}`
}

const closeModal = () => {
  emit('close')
}

const downloadExport = () => {
  if (props.exportJob) {
    emit('download', props.exportJob)
  }
}

const retryExport = () => {
  if (props.exportJob) {
    emit('retry', props.exportJob)
  }
}

const cancelExport = () => {
  if (props.exportJob) {
    emit('cancel', props.exportJob)
  }
}

const shareExport = () => {
  if (props.exportJob) {
    emit('share', props.exportJob)
  }
}

const duplicateExport = () => {
  if (props.exportJob) {
    emit('duplicate', props.exportJob)
  }
}

const editOriginal = () => {
  if (props.exportJob) {
    emit('editOriginal', props.exportJob)
  }
}

// Simulate progress for processing jobs
let progressInterval: NodeJS.Timeout | null = null

onMounted(() => {
  if (props.exportJob?.exportStatus === 'processing') {
    progress.value = Math.floor(Math.random() * 40) + 10 // Start at 10-50%
    progressInterval = setInterval(() => {
      if (progress.value < 95) {
        progress.value += Math.floor(Math.random() * 5) + 1
      }
    }, 2000)
  } else if (props.exportJob?.exportStatus === 'completed') {
    progress.value = 100
    fileSize.value = Math.floor(Math.random() * 5000000) + 1000000 // 1-6MB
    completedAt.value = new Date(Date.now() - Math.random() * 86400000).toISOString() // Within last 24h
  }
})

onUnmounted(() => {
  if (progressInterval) {
    clearInterval(progressInterval)
  }
})
</script>

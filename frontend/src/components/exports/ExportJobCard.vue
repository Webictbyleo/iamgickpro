<template>
  <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 hover:shadow-md transition-shadow">
    <div class="flex items-start justify-between">const {
  document,
  clock,
  exclamationTriangle,
  arrowDownTray,
  arrowPath,
  xMark,
  ellipsisVertical,
  checkCircle,
  cog,
  exclamationCircle
} = useIcons()ob Info -->
      <div class="flex-1 min-w-0">
        <div class="flex items-center space-x-4">
          <!-- Design Thumbnail -->
          <div class="flex-shrink-0">
            <div class="w-16 h-16 bg-gray-100 rounded-lg overflow-hidden">
              <img
                v-if="job.designThumbnail"
                :src="job.designThumbnail"
                :alt="job.designName"
                class="w-full h-full object-cover"
              />
              <div v-else class="w-full h-full flex items-center justify-center">
                <DocumentIcon class="h-8 w-8 text-gray-400" />
              </div>
            </div>
          </div>

          <!-- Job Details -->
          <div class="flex-1 min-w-0">
            <h3 class="text-lg font-medium text-gray-900 truncate">
              {{ job.designName }}
            </h3>
            <div class="mt-1 flex items-center space-x-4 text-sm text-gray-500">
              <span class="inline-flex items-center">
                <FormatIcon class="h-4 w-4 mr-1" />
                {{ job.format.toUpperCase() }}
              </span>
              <span class="inline-flex items-center">
                <ClockIcon class="h-4 w-4 mr-1" />
                {{ formatDate(job.createdAt) }}
              </span>
              <span v-if="job.fileSize" class="inline-flex items-center">
                <DocumentIcon class="h-4 w-4 mr-1" />
                {{ formatFileSize(job.fileSize) }}
              </span>
            </div>
          </div>
        </div>

        <!-- Progress Bar (for processing jobs) -->
        <div v-if="job.status === 'processing'" class="mt-4">
          <div class="flex items-center justify-between text-sm">
            <span class="text-gray-600">Processing...</span>
            <span class="text-gray-600">{{ job.progress || 0 }}%</span>
          </div>
          <div class="mt-1 bg-gray-200 rounded-full h-2">
            <div
              class="bg-blue-600 h-2 rounded-full transition-all duration-300"
              :style="{ width: `${job.progress || 0}%` }"
            ></div>
          </div>
        </div>

        <!-- Error Message (for failed jobs) -->
        <div v-if="job.status === 'failed' && job.errorMessage" class="mt-3">
          <div class="bg-red-50 border border-red-200 rounded-md p-3">
            <div class="flex">
              <ExclamationTriangleIcon class="h-5 w-5 text-red-400 flex-shrink-0" />
              <div class="ml-3">
                <p class="text-sm text-red-800">{{ job.errorMessage }}</p>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Status Badge and Actions -->
      <div class="flex-shrink-0 ml-6">
        <div class="flex items-center space-x-3">
          <!-- Status Badge -->
          <span :class="statusClasses" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium">
            <component :is="statusIcon" class="h-3 w-3 mr-1" />
            {{ job.status.charAt(0).toUpperCase() + job.status.slice(1) }}
          </span>

          <!-- Actions -->
          <div class="flex items-center space-x-2">
            <!-- Download Button (for completed jobs) -->
            <button
              v-if="job.status === 'completed'"
              @click="$emit('download', job)"
              class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-colors"
            >
              <component :is="arrowDownTray" class="h-4 w-4 mr-1" />
              Download
            </button>

            <!-- Retry Button (for failed jobs) -->
            <button
              v-if="job.status === 'failed'"
              @click="$emit('retry', job.id)"
              class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors"
            >
              <component :is="arrowPath" class="h-4 w-4 mr-1" />
              Retry
            </button>

            <!-- Cancel Button (for pending/processing jobs) -->
            <button
              v-if="job.status === 'pending' || job.status === 'processing'"
              @click="$emit('cancel', job.id)"
              class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition-colors"
            >
              <component :is="xMark" class="h-4 w-4 mr-1" />
              Cancel
            </button>

            <!-- More Options Menu -->
            <div class="relative" ref="menuRef">
              <button
                @click="showMenu = !showMenu"
                class="inline-flex items-center p-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
              >
                <component :is="ellipsisVertical" class="h-4 w-4" />
              </button>

              <!-- Dropdown Menu -->
              <div
                v-if="showMenu"
                class="absolute right-0 top-full mt-1 w-48 bg-white rounded-md shadow-lg ring-1 ring-black ring-opacity-5 z-50"
              >
                <div class="py-1">
                  <button
                    @click="copyJobInfo"
                    class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                  >
                    Copy Job Info
                  </button>
                  <button
                    v-if="job.status === 'completed'"
                    @click="copyDownloadLink"
                    class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                  >
                    Copy Download Link
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useIcons } from '@/composables/useIcons'
import { useNotifications } from '@/composables/useNotifications'
import type { ExportJob } from '@/types'

interface Props {
  job: ExportJob
}

interface Emits {
  (e: 'download', job: ExportJob): void
  (e: 'cancel', jobId: string): void
  (e: 'retry', jobId: string): void
}

const props = defineProps<Props>()
const emit = defineEmits<Emits>()

const { 
  document: documentIcon,
  clock,
  exclamationTriangle,
  arrowDownTray,
  arrowPath,
  xMark,
  ellipsisVertical,
  checkCircle,
  cog,
  exclamationCircle
} = useIcons()

const { showSuccess } = useNotifications()

const showMenu = ref(false)
const menuRef = ref<HTMLElement>()

// Status-based styling
const statusClasses = computed(() => {
  switch (props.job.status) {
    case 'completed':
      return 'bg-green-100 text-green-800'
    case 'processing':
      return 'bg-blue-100 text-blue-800'
    case 'pending':
      return 'bg-yellow-100 text-yellow-800'
    case 'failed':
      return 'bg-red-100 text-red-800'
    default:
      return 'bg-gray-100 text-gray-800'
  }
})

const statusIcon = computed(() => {
  switch (props.job.status) {
    case 'completed':
      return checkCircle
    case 'processing':
      return cog
    case 'pending':
      return clock
    case 'failed':
      return exclamationCircle
    default:
      return clock
  }
})

const FormatIcon = computed(() => documentIcon)

// Utility functions
const formatDate = (dateString: string) => {
  const date = new Date(dateString)
  const now = new Date()
  const diffInMinutes = Math.floor((now.getTime() - date.getTime()) / (1000 * 60))
  
  if (diffInMinutes < 1) return 'Just now'
  if (diffInMinutes < 60) return `${diffInMinutes}m ago`
  if (diffInMinutes < 1440) return `${Math.floor(diffInMinutes / 60)}h ago`
  
  return date.toLocaleDateString()
}

const formatFileSize = (bytes: number) => {
  const sizes = ['B', 'KB', 'MB', 'GB']
  if (bytes === 0) return '0 B'
  const i = Math.floor(Math.log(bytes) / Math.log(1024))
  return `${(bytes / Math.pow(1024, i)).toFixed(1)} ${sizes[i]}`
}

const copyJobInfo = async () => {
  const info = `Job ID: ${props.job.id}\nDesign: ${props.job.designName}\nFormat: ${props.job.format.toUpperCase()}\nStatus: ${props.job.status}\nCreated: ${formatDate(props.job.createdAt)}`
  
  try {
    await navigator.clipboard.writeText(info)
    showSuccess('Job info copied to clipboard')
  } catch (err) {
    console.error('Failed to copy job info:', err)
  }
  
  showMenu.value = false
}

const copyDownloadLink = async () => {
  if (props.job.downloadUrl) {
    try {
      await navigator.clipboard.writeText(props.job.downloadUrl)
      showSuccess('Download link copied to clipboard')
    } catch (err) {
      console.error('Failed to copy download link:', err)
    }
  }
  
  showMenu.value = false
}

// Close menu when clicking outside
const handleClickOutside = (event: MouseEvent) => {
  if (menuRef.value && !menuRef.value.contains(event.target as Node)) {
    showMenu.value = false
  }
}

onMounted(() => {
  document.addEventListener('click', handleClickOutside)
})

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside)
})
</script>

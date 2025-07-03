<template>
  <AppLayout
    title="Export Jobs"
    subtitle="Manage and download your exported designs"
  >
    <div class="min-h-screen bg-gray-50 dark:bg-gray-900">
      <!-- Stats Summary Section -->
      <div class="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
          <div class="flex items-center justify-center space-x-6">
            <div class="text-center">
              <div class="text-2xl font-bold text-primary-600">{{ processingJobs.length }}</div>
              <div class="text-xs text-gray-500 dark:text-gray-400">Processing</div>
            </div>
              <div class="text-center">
                <div class="text-2xl font-bold text-green-600">{{ completedJobs.length }}</div>
                <div class="text-xs text-gray-500 dark:text-gray-400">Completed</div>
              </div>
              <div class="text-center">
                <div class="text-2xl font-bold text-yellow-600">{{ pendingJobs.length }}</div>
                <div class="text-xs text-gray-500 dark:text-gray-400">Queued</div>
              </div>
              <div class="text-center">
                <div class="text-2xl font-bold text-red-600">{{ failedJobs.length }}</div>
                <div class="text-xs text-gray-500 dark:text-gray-400">Failed</div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Filters and Controls -->
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-4">
          <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between space-y-4 sm:space-y-0">
            <!-- Filters -->
            <div class="flex items-center space-x-4">
              <select 
                v-model="filters.status" 
                @change="applyFilters"
                class="rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm focus:border-primary-500 focus:ring-primary-500"
              >
                <option value="">All Status</option>
                <option value="pending">Pending</option>
                <option value="processing">Processing</option>
                <option value="completed">Completed</option>
                <option value="failed">Failed</option>
              </select>
              
              <select 
                v-model="filters.format" 
                @change="applyFilters"
                class="rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm focus:border-primary-500 focus:ring-primary-500"
              >
                <option value="">All Formats</option>
                <option value="png">PNG</option>
                <option value="jpg">JPG</option>
                <option value="pdf">PDF</option>
                <option value="svg">SVG</option>
                <option value="mp4">MP4</option>
                <option value="gif">GIF</option>
              </select>
            </div>

            <!-- Actions -->
            <div class="flex items-center space-x-3">
              <button
                @click="refreshJobs"
                :disabled="isLoading"
                class="inline-flex items-center px-3 py-2 border border-gray-300 dark:border-gray-600 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 disabled:opacity-50"
              >
                <component :is="refresh" class="h-4 w-4 mr-2" :class="{ 'animate-spin': isLoading }" />
                Refresh
              </button>
              
              <button
                v-if="hasActiveJobs"
                @click="showAutoRefreshModal = true"
                class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
              >
                <component :is="play" class="h-4 w-4 mr-2" />
                Auto-refresh: {{ autoRefresh ? 'On' : 'Off' }}
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Export Jobs List -->
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pb-8">
        <div v-if="isLoading && exportJobs.length === 0" class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-8">
          <div class="text-center">
            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-primary-600 mx-auto"></div>
            <p class="mt-4 text-gray-500 dark:text-gray-400">Loading export jobs...</p>
          </div>
        </div>

        <div v-else-if="exportJobs.length === 0" class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-8">
          <div class="text-center">
            <component :is="documentDownload" class="mx-auto h-12 w-12 text-gray-400 dark:text-gray-500" />
            <h3 class="mt-4 text-lg font-medium text-gray-900 dark:text-white">No export jobs found</h3>
            <p class="mt-2 text-gray-500 dark:text-gray-400">
              Export jobs will appear here once you start exporting your designs.
            </p>
            <router-link
              to="/designs"
              class="mt-4 inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-primary-600 hover:bg-primary-700"
            >
              Go to Designs
            </router-link>
          </div>
        </div>

        <div v-else class="space-y-4">
          <ExportJobCard
            v-for="job in exportJobs"
            :key="job.id"
            :job="job"
            @download="handleDownload"
            @cancel="handleCancel"
            @retry="handleRetry"
          />
        </div>

        <!-- Pagination -->
        <div v-if="pagination.totalPages > 1" class="mt-8">
          <ExportJobsPagination
            :current-page="pagination.page"
            :last-page="pagination.totalPages"
            :total="pagination.total"
            @page-changed="handlePageChange"
          />
        </div>
      </div>
    

    <!-- Auto-refresh Settings Modal -->
    <AutoRefreshModal
      v-if="showAutoRefreshModal"
      :is-enabled="autoRefresh"
      :interval="refreshInterval"
      @save="handleAutoRefreshSave"
      @close="showAutoRefreshModal = false"
    />
  </AppLayout>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useExportsStore } from '@/stores/exports'
import { useIcons } from '@/composables/useIcons'
import AppLayout from '@/components/layout/AppLayout.vue'
import ExportJobCard from '@/components/exports/ExportJobCard.vue'
import ExportJobsPagination from '@/components/exports/ExportJobsPagination.vue'
import AutoRefreshModal from '@/components/exports/AutoRefreshModal.vue'

const { 
  refresh, 
  play, 
  documentDownload 
} = useIcons()

const exportsStore = useExportsStore()

// Local state
const filters = ref({
  status: '',
  format: ''
})

const showAutoRefreshModal = ref(false)
const autoRefresh = ref(false)
const refreshInterval = ref(10) // seconds
let refreshTimer: ReturnType<typeof setInterval> | null = null

// Computed
const {
  exportJobs,
  isLoading,
  pendingJobs,
  processingJobs,
  completedJobs,
  failedJobs,
  hasActiveJobs,
  pagination
} = exportsStore

// Methods
const fetchJobs = () => {
  const params: any = {
    page: pagination.page,
    limit: 20 // Use default limit since pagination doesn't have limit property
  }
  
  if (filters.value.status) params.status = filters.value.status
  if (filters.value.format) params.format = filters.value.format
  
  return exportsStore.fetchExportJobs(params)
}

const applyFilters = () => {
  pagination.page = 1
  fetchJobs()
}

const refreshJobs = () => {
  fetchJobs()
}

const handlePageChange = (page: number) => {
  pagination.page = page
  fetchJobs()
}

const handleDownload = (job: any) => {
  exportsStore.downloadExport(job)
}

const handleCancel = (jobId: string) => {
  exportsStore.cancelExportJob(jobId)
}

const handleRetry = (jobId: string) => {
  exportsStore.retryExportJob(jobId)
}

const handleAutoRefreshSave = (enabled: boolean, interval: number) => {
  autoRefresh.value = enabled
  refreshInterval.value = interval
  showAutoRefreshModal.value = false
  
  setupAutoRefresh()
}

const setupAutoRefresh = () => {
  if (refreshTimer) {
    clearInterval(refreshTimer)
    refreshTimer = null
  }
  
  if (autoRefresh.value && hasActiveJobs) {
    refreshTimer = setInterval(() => {
      if (hasActiveJobs) {
        refreshJobs()
      } else {
        autoRefresh.value = false
        if (refreshTimer) {
          clearInterval(refreshTimer)
          refreshTimer = null
        }
      }
    }, refreshInterval.value * 1000)
  }
}

// Lifecycle
onMounted(() => {
  fetchJobs()
})

onUnmounted(() => {
  if (refreshTimer) {
    clearInterval(refreshTimer)
  }
})
</script>

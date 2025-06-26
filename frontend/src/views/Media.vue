<template>
  <AppLayout>
    <div class="space-y-6">
      <!-- Simplified Header Section -->
      <div class="bg-white rounded-xl border border-gray-200 p-6 shadow-sm">
        <div class="flex items-center justify-between">
          <div class="flex items-center space-x-4">
            <div class="w-12 h-12 bg-violet-600 rounded-lg flex items-center justify-center">
              <PhotoIcon class="w-6 h-6 text-white" />
            </div>
            <div>
              <h1 class="text-2xl font-bold text-gray-900">
                Image Library
              </h1>
              <p class="text-gray-600 mt-1">
                Discover high-quality images for your designs
              </p>
            </div>
          </div>
          
          <!-- Upload Button -->
          <div class="flex items-center space-x-3">
            <div class="hidden sm:flex items-center text-sm text-gray-500">
              <span class="w-2 h-2 bg-green-400 rounded-full mr-2"></span>
              {{ mediaItems.length }} items loaded
            </div>
            <button
              @click="triggerFileUpload"
              class="inline-flex items-center px-4 py-2 bg-violet-600 text-white rounded-lg font-medium hover:bg-violet-700 transition-colors"
            >
              <ArrowUpTrayIcon class="w-5 h-5 mr-2" />
              Upload Image
            </button>
            
            <input
              ref="fileInput"
              type="file"
              multiple
              accept="image/*"
              class="hidden"
              @change="handleFileUpload"
            >
          </div>
        </div>
      </div>

      <!-- Simplified Search and Filters -->
      <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="p-4">
          <div class="flex flex-col lg:flex-row lg:items-center space-y-4 lg:space-y-0 lg:space-x-4">
            <!-- Search Input -->
            <div class="flex-1">
              <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                  <MagnifyingGlassIcon class="h-5 w-5 text-gray-400" />
                </div>
                <input
                  v-model="searchQuery"
                  type="text"
                  placeholder="Search for images..."
                  class="block w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-lg text-gray-900 placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-violet-500 focus:border-violet-500 transition-colors"
                  @keyup.enter="searchMedia"
                  @input="debouncedSearch"
                />
              </div>
            </div>
            
            <!-- Search Button -->
            <div class="flex items-center">
              <button
                @click="searchMedia"
                :disabled="isLoading"
                class="inline-flex items-center px-4 py-2.5 bg-violet-600 text-white rounded-lg font-medium hover:bg-violet-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
              >
                <MagnifyingGlassIcon v-if="!isLoading" class="w-4 h-4 mr-2" />
                <div v-else class="w-4 h-4 mr-2 border-2 border-white border-t-transparent rounded-full animate-spin"></div>
                {{ isLoading ? 'Searching...' : 'Search' }}
              </button>
            </div>
          </div>
        </div>
        
        <!-- Simplified Tabs -->
        <div class="border-t border-gray-200 bg-gray-50">
          <nav class="flex">
            <button
              v-for="tab in tabs"
              :key="tab.id"
              @click="activeTab = tab.id"
              :class="[
                activeTab === tab.id
                  ? 'border-violet-500 text-violet-600 bg-white'
                  : 'border-transparent text-gray-500 hover:text-gray-700',
                'whitespace-nowrap py-3 px-6 border-b-2 font-medium text-sm transition-colors flex items-center space-x-2'
              ]"
            >
              <span>{{ tab.icon }}</span>
              <span>{{ tab.name }}</span>
              <span v-if="tab.id === 'uploads' && userMediaCount > 0" class="bg-violet-100 text-violet-600 px-2 py-0.5 rounded-full text-xs font-medium">
                {{ userMediaCount }}
              </span>
            </button>
          </nav>
        </div>
      </div>

      <!-- Optimized Loading State -->
      <div v-if="isLoading" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-4">
        <div
          v-for="i in 18"
          :key="i"
          class="aspect-square bg-gray-200 rounded-lg animate-pulse"
        ></div>
      </div>

      <!-- Optimized Media Grid -->
      <MediaGrid
        v-else-if="mediaItems.length > 0"
        :items="mediaItems"
        @openInEditor="openInEditor"
      />

      <!-- Simplified Empty State -->
      <div v-else class="text-center py-12">
        <div class="w-16 h-16 bg-violet-600 rounded-lg flex items-center justify-center mx-auto mb-4">
          <PhotoIcon class="w-8 h-8 text-white" />
        </div>
        
        <h3 class="text-xl font-semibold text-gray-900 mb-2">
          {{ getEmptyStateTitle() }}
        </h3>
        <p class="text-gray-600 mb-6 max-w-md mx-auto">
          {{ getEmptyStateDescription() }}
        </p>
        
        <div class="flex flex-col sm:flex-row items-center justify-center space-y-3 sm:space-y-0 sm:space-x-4">
          <button
            v-if="activeTab === 'uploads'"
            @click="triggerFileUpload"
            class="inline-flex items-center px-4 py-2 bg-violet-600 text-white rounded-lg font-medium hover:bg-violet-700 transition-colors"
          >
            <ArrowUpTrayIcon class="h-5 w-5 mr-2" />
            Upload Your First Image
          </button>
          
          <button
            v-else
            @click="clearSearch"
            class="inline-flex items-center px-4 py-2 bg-white text-gray-700 border border-gray-300 rounded-lg font-medium hover:bg-gray-50 transition-colors"
          >
            <MagnifyingGlassIcon class="h-5 w-5 mr-2" />
            Try Different Search
          </button>
        </div>
      </div>

      <!-- Simplified Pagination -->
      <div v-if="totalPages > 1" class="flex items-center justify-center space-x-1">
        <button
          @click="goToPage(currentPage - 1)"
          :disabled="currentPage === 1"
          class="p-2 rounded border border-gray-300 text-gray-500 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
          </svg>
        </button>
        
        <div class="flex items-center space-x-1">
          <button
            v-for="page in getPaginationPages()"
            :key="page"
            @click="goToPage(page)"
            :class="[
              page === currentPage
                ? 'bg-violet-600 text-white'
                : 'bg-white text-gray-700 hover:bg-gray-50 border border-gray-300',
              'px-3 py-2 rounded text-sm font-medium transition-colors min-w-[40px]'
            ]"
          >
            {{ page }}
          </button>
        </div>
        
        <button
          @click="goToPage(currentPage + 1)"
          :disabled="currentPage === totalPages"
          class="p-2 rounded border border-gray-300 text-gray-500 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
          </svg>
        </button>
      </div>
    </div>

    <!-- Media Preview Modal -->
    <div v-if="previewItem" class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 backdrop-blur-sm" @click="closePreview">
      <div class="relative max-w-4xl max-h-[90vh] m-4" @click.stop>
        <img
          :src="previewItem.url"
          :alt="previewItem.name"
          class="max-w-full max-h-full rounded-2xl shadow-2xl"
        />
        <button
          @click="closePreview"
          class="absolute top-4 right-4 w-10 h-10 bg-black/50 backdrop-blur-sm text-white rounded-full flex items-center justify-center hover:bg-black/70 transition-all duration-200"
        >
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
          </svg>
        </button>
      </div>
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted, watch, computed } from 'vue'
import { useRouter } from 'vue-router'
import { 
  MagnifyingGlassIcon, 
  ArrowUpTrayIcon, 
  PhotoIcon,
  EyeIcon
} from '@heroicons/vue/24/outline'
import AppLayout from '@/components/layout/AppLayout.vue'
import MediaGrid from '@/components/MediaGrid.vue'
import { mediaAPI } from '@/services/api'
import type { MediaItem } from '@/types'

const router = useRouter()

// Reactive data
const searchQuery = ref('')
const activeTab = ref('stock')
const isLoading = ref(false)
const mediaItems = ref<MediaItem[]>([])
const currentPage = ref(1)
const totalPages = ref(1)
const fileInput = ref<HTMLInputElement>()
const previewItem = ref<MediaItem | null>(null)
const searchTimeout = ref<NodeJS.Timeout>()
const lastSearchQuery = ref('')

// Enhanced tabs with icons
const tabs = [
  { id: 'stock', name: 'Stock Images', icon: '🎨' },
  { id: 'uploads', name: 'My Images', icon: '📁' },
]

// Computed properties
const userMediaCount = computed(() => {
  return activeTab.value === 'uploads' ? mediaItems.value.length : 0
})

// Utility functions (optimized)
const debouncedSearch = () => {
  if (searchTimeout.value) {
    clearTimeout(searchTimeout.value)
  }
  
  // Store the current search query value at the time of call
  const currentQuery = searchQuery.value
  
  searchTimeout.value = setTimeout(() => {
    // Only search if the query hasn't changed since the timeout was set
    if (searchQuery.value === currentQuery) {
      searchMedia()
    }
  }, 500)
}

// Memoized functions for better performance
const fileSizeCache = new Map<number, string>()
const formatFileSize = (bytes: number | undefined): string => {
  if (!bytes) return 'Unknown size'
  if (fileSizeCache.has(bytes)) return fileSizeCache.get(bytes)!
  
  const sizes = ['B', 'KB', 'MB', 'GB']
  const i = Math.floor(Math.log(bytes) / Math.log(1024))
  const result = Math.round(bytes / Math.pow(1024, i) * 100) / 100 + ' ' + sizes[i]
  fileSizeCache.set(bytes, result)
  return result
}

const getEmptyStateTitle = (): string => {
  if (activeTab.value === 'uploads') {
    return userMediaCount.value === 0 ? 'No images yet' : 'No matching images'
  }
  return searchQuery.value ? 'No images found' : 'Start your search'
}

const getEmptyStateDescription = (): string => {
  if (activeTab.value === 'uploads') {
    return userMediaCount.value === 0 
      ? 'Upload your first image file to build your personal library' 
      : 'Try adjusting your search criteria or upload new images'
  }
  return searchQuery.value 
    ? 'Try different keywords or browse our stock collection' 
    : 'Search through millions of high-quality images for your designs'
}

const getPaginationPages = (): number[] => {
  const maxVisible = 7
  const pages: number[] = []
  
  if (totalPages.value <= maxVisible) {
    for (let i = 1; i <= totalPages.value; i++) {
      pages.push(i)
    }
  } else {
    const start = Math.max(1, currentPage.value - 3)
    const end = Math.min(totalPages.value, start + maxVisible - 1)
    
    for (let i = start; i <= end; i++) {
      pages.push(i)
    }
  }
  
  return pages
}

// Media operations
const searchMedia = async () => {
  // Skip if already loading or if query hasn't changed
  if (isLoading.value || lastSearchQuery.value === searchQuery.value) {
    return
  }
  
  lastSearchQuery.value = searchQuery.value
  currentPage.value = 1 // Reset to first page for new search
  
  if (activeTab.value === 'uploads') {
    await loadUserUploads()
  } else {
    await loadStockMedia()
  }
}

const loadStockMedia = async () => {
  try {
    isLoading.value = true
    const response = await mediaAPI.searchStockMedia({
      query: searchQuery.value,
      type: 'image', // Only search for images
      page: currentPage.value,
      limit: 24
    })
    
    if (response.data) {
      mediaItems.value = response.data.data
      totalPages.value = response.data.pagination.totalPages
    }
  } catch (error) {
    console.error('Failed to load stock media:', error)
    mediaItems.value = []
  } finally {
    isLoading.value = false
  }
}

const loadUserUploads = async () => {
  try {
    isLoading.value = true
    const response = await mediaAPI.getMedia({
      search: searchQuery.value,
      type: 'image', // Only load images
      page: currentPage.value,
      limit: 24
    })
    
    if (response.data) {
      mediaItems.value = response.data.data
      totalPages.value = response.data.pagination.totalPages
    }
  } catch (error) {
    console.error('Failed to load user uploads:', error)
    mediaItems.value = []
  } finally {
    isLoading.value = false
  }
}

const triggerFileUpload = () => {
  fileInput.value?.click()
}

const handleFileUpload = async (event: Event) => {
  const files = (event.target as HTMLInputElement).files
  if (!files || files.length === 0) return
  
  try {
    isLoading.value = true
    
    for (const file of Array.from(files)) {
      await mediaAPI.uploadMedia(file)
    }
    
    // Switch to "My Uploads" tab to show the uploaded files
    activeTab.value = 'uploads'
    currentPage.value = 1
    
    // Load user uploads to show the newly uploaded files
    await loadUserUploads()
    
    // Show success message
    console.log(`Successfully uploaded ${files.length} file(s)`)
  } catch (error) {
    console.error('Failed to upload media:', error)
  } finally {
    isLoading.value = false
  }
}

const openInEditor = async (item: MediaItem) => {
  // Create DSN for uploaded media
  if (!item.url) {
    console.warn('Cannot open in editor: media item has no URL', item)
    return
  }
  // Use media ID if available, otherwise use URL
  const resourceId = item.uuid || item.url
  const encodedId = btoa(resourceId)
  const source = item.source !=='upload' ? 'stock' : 'upload'
  const provider = source === 'upload' ? 'media' : item.source
  const dsn = `${source}:${provider}:${encodedId}`
  
  console.log(`🔗 Opening media in editor with DSN: ${dsn} (resource: ${resourceId})`)
  
  // Navigate to editor with DSN format
  router.push({
    name: 'Editor',
    params: { id: dsn }
  })
}

const previewMedia = (item: MediaItem) => {
  previewItem.value = item
}

const closePreview = () => {
  previewItem.value = null
}

const clearSearch = () => {
  searchQuery.value = ''
  lastSearchQuery.value = ''
  currentPage.value = 1
  searchMedia()
}

const goToPage = (page: number) => {
  if (page >= 1 && page <= totalPages.value) {
    currentPage.value = page
    searchMedia()
  }
}

// Watchers
watch(activeTab, () => {
  currentPage.value = 1
  lastSearchQuery.value = '' // Reset search state when switching tabs
  searchMedia()
})

// Initialize with stock media
onMounted(() => {
  searchMedia()
})

// Cleanup caches on unmount to prevent memory leaks
onUnmounted(() => {
  fileSizeCache.clear()
})
</script>

<template>
  <AppLayout>
    <div ref="contentContainer" class="space-y-6">
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
              v-if="shouldShowMainUploadButton"
              @click="triggerFileUpload"
              :disabled="isUploading"
              class="inline-flex items-center px-4 py-2 bg-violet-600 text-white rounded-lg font-medium hover:bg-violet-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
            >
              <div v-if="isUploading" class="w-5 h-5 mr-2 border-2 border-white border-t-transparent rounded-full animate-spin"></div>
              <ArrowUpTrayIcon v-else class="w-5 h-5 mr-2" />
              {{ isUploading ? 'Uploading...' : 'Upload Image' }}
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
                  :placeholder="activeTab === 'stock' ? 'Search stock images... (e.g., business, nature, technology)' : 'Search your images...'"
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

      <!-- Upload Progress Indicator -->
      <div v-if="isUploading" class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-4">
        <div class="flex items-center justify-between mb-2">
          <div class="flex items-center">
            <div class="w-5 h-5 mr-2 border-2 border-blue-600 border-t-transparent rounded-full animate-spin"></div>
            <span class="text-blue-800 font-medium">Uploading images...</span>
          </div>
          <span class="text-blue-600 text-sm">{{ uploadProgress }}%</span>
        </div>
        <div class="w-full bg-blue-200 rounded-full h-2">
          <div 
            class="bg-blue-600 h-2 rounded-full transition-all duration-300"
            :style="{ width: `${uploadProgress}%` }"
          ></div>
        </div>
      </div>

      <!-- Optimized Media Grid -->
      <MediaMasonry
        v-else-if="mediaItems.length > 0"
        :mediaItems="mediaItems"
        :actions="mediaActions"
        :columns="columns"
        :showSelection="false"
        @mediaClick="openInEditor"
        @action="handleMediaAction"
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
            :disabled="isUploading"
            class="inline-flex items-center px-4 py-2 bg-violet-600 text-white rounded-lg font-medium hover:bg-violet-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
          >
            <div v-if="isUploading" class="w-5 h-5 mr-2 border-2 border-white border-t-transparent rounded-full animate-spin"></div>
            <ArrowUpTrayIcon v-else class="h-5 w-5 mr-2" />
            {{ isUploading ? 'Uploading...' : 'Upload Your First Image' }}
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
      <div class="relative w-full h-full max-w-7xl max-h-full p-4 flex items-center justify-center" @click.stop>
        <img
          :src="previewItem.url"
          :alt="previewItem.name"
          class="max-w-full max-h-full object-contain rounded-2xl shadow-2xl"
        />
        <button
          @click="closePreview"
          class="absolute top-8 right-8 w-12 h-12 bg-black/60 backdrop-blur-sm text-white rounded-full flex items-center justify-center hover:bg-black/80 transition-all duration-200 z-10"
        >
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
          </svg>
        </button>
      </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div v-if="deleteItem" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm">
      <div class="bg-white rounded-xl p-6 max-w-md w-full mx-4 shadow-xl">
        <div class="flex items-center justify-center w-12 h-12 bg-red-100 rounded-lg mx-auto mb-4">
          <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
          </svg>
        </div>
        
        <h3 class="text-lg font-semibold text-gray-900 text-center mb-2">
          Delete Image?
        </h3>
        <p class="text-gray-600 text-center mb-6">
          This action cannot be undone. The image will be permanently removed from your library.
        </p>
        
        <div class="flex space-x-3">
          <button
            @click="cancelDelete"
            class="flex-1 px-4 py-2 bg-gray-100 text-gray-700 rounded-lg font-medium hover:bg-gray-200 transition-colors"
          >
            Cancel
          </button>
          <button
            @click="confirmDelete"
            :disabled="isDeleting"
            class="flex-1 px-4 py-2 bg-red-600 text-white rounded-lg font-medium hover:bg-red-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
          >
            {{ isDeleting ? 'Deleting...' : 'Delete' }}
          </button>
        </div>
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
  EyeIcon,
  TrashIcon,
  PencilSquareIcon,
  ArrowDownTrayIcon
} from '@heroicons/vue/24/outline'
import AppLayout from '@/components/layout/AppLayout.vue'
import MediaMasonry from '@/components/common/MediaMasonry.vue'
import { mediaAPI } from '@/services/api'
import type { MediaItem } from '@/types'

const router = useRouter()

// Reactive data
const searchQuery = ref('')
const activeTab = ref('stock')
const isLoading = ref(false)
const isUploading = ref(false)
const uploadProgress = ref(0)
const mediaItems = ref<MediaItem[]>([])
const currentPage = ref(1)
const totalPages = ref(1)
const fileInput = ref<HTMLInputElement>()
const previewItem = ref<MediaItem | null>(null)
const searchTimeout = ref<NodeJS.Timeout>()
const lastSearchQuery = ref('')
const deleteItem = ref<MediaItem | null>(null)
const isDeleting = ref(false)
const contentContainer = ref<HTMLElement>()

// Enhanced tabs with icons
const tabs = [
  { id: 'stock', name: 'Stock Images', icon: '🎨' },
  { id: 'uploads', name: 'My Images', icon: '📁' },
]

// MediaMasonry actions configuration
const mediaActions = computed(() => [
  {
    key: 'view',
    label: 'View',
    icon: EyeIcon
  },
  {
    key: 'edit',
    label: 'Edit in Designer',
    icon: PencilSquareIcon
  },
  {
    key: 'download',
    label: 'Download',
    icon: ArrowDownTrayIcon
  },
  ...(activeTab.value === 'uploads' ? [{
    key: 'delete',
    label: 'Delete',
    icon: TrashIcon
  }] : [])
])

// Computed properties
const userMediaCount = computed(() => {
  return activeTab.value === 'uploads' ? mediaItems.value.length : 0
})

// Show upload button logic
const shouldShowMainUploadButton = computed(() => {
  // Hide main upload button when on uploads tab and there are no items (empty state shows its own upload button)
  if (activeTab.value === 'uploads' && mediaItems.value.length === 0 && !isLoading.value) {
    return false
  }
  return true
})

// Responsive columns for MediaMasonry
const columns = ref(2)

const updateColumns = () => {
  if (!contentContainer.value) return
  
  const containerWidth = contentContainer.value.clientWidth
  
  // Calculate columns based on container width, not window width
  // Account for gaps between columns (16px default gap)
  const gap = 16
  const minColumnWidth = 200 // Minimum width for each column
  
  let calculatedColumns = Math.floor((containerWidth + gap) / (minColumnWidth + gap))
  
  // Apply responsive constraints
  if (containerWidth >= 1280) calculatedColumns = Math.min(calculatedColumns, 6)      // xl: max 6
  else if (containerWidth >= 1024) calculatedColumns = Math.min(calculatedColumns, 5) // lg: max 5  
  else if (containerWidth >= 768) calculatedColumns = Math.min(calculatedColumns, 4)  // md: max 4
  else if (containerWidth >= 640) calculatedColumns = Math.min(calculatedColumns, 3)  // sm: max 3
  else calculatedColumns = Math.min(calculatedColumns, 2)                             // base: max 2
  
  columns.value = Math.max(1, calculatedColumns) // Ensure at least 1 column
}

// Handle media actions from MediaMasonry
const handleMediaAction = (action: string, file: MediaItem) => {
  switch (action) {
    case 'view':
      previewItem.value = file
      break
    case 'edit':
      openInEditor(file)
      break
    case 'download':
      downloadMedia(file)
      break
    case 'delete':
      handleDeleteItem(file)
      break
    default:
      console.warn('Unknown action:', action)
  }
}

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
    : 'Browse through our curated collection of high-quality images'
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
    
    // Use default search term if no query is provided for stock media
    // Rotate through popular terms for variety when using defaults
    const getDefaultQuery = () => {
      const defaultTerms = ['business', 'technology', 'nature', 'lifestyle', 'design'];
      const hourOfDay = new Date().getHours();
      const dayOfYear = Math.floor((Date.now() - new Date(new Date().getFullYear(), 0, 0).getTime()) / 86400000);
      const index = (hourOfDay + dayOfYear) % defaultTerms.length;
      return defaultTerms[index];
    }
    
    const query = searchQuery.value.trim() || getDefaultQuery()
    
    const response = await mediaAPI.searchStockMedia({
      query: query,
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
  
  const fileArray = Array.from(files)
  
  try {
    isUploading.value = true
    uploadProgress.value = 0
    
    // Switch to "My Uploads" tab to show the uploading progress
    activeTab.value = 'uploads'
    
    // If we're currently showing empty state, load existing uploads first
    if (mediaItems.value.length === 0) {
      await loadUserUploads()
    }
    
    // Upload files with progress tracking
    const uploadedItems: MediaItem[] = []
    
    for (let i = 0; i < fileArray.length; i++) {
      const file = fileArray[i]
      
      try {
        const response = await mediaAPI.uploadMedia(file)
        
        // Add the uploaded item to our list optimistically
        if (response.data?.data?.media) {
          const uploadedItem = response.data.data.media
          uploadedItems.push(uploadedItem)
          
          // Insert at the beginning of the list (most recent first)
          mediaItems.value.unshift(uploadedItem)
        }
        
        // Update progress
        uploadProgress.value = Math.round(((i + 1) / fileArray.length) * 100)
        
      } catch (error) {
        console.error(`Failed to upload ${file.name}:`, error)
        // Continue with other files even if one fails
      }
    }
    
    // Reset file input
    if (event.target) {
      (event.target as HTMLInputElement).value = ''
    }
    
    // Show success message
    if (uploadedItems.length > 0) {
      console.log(`Successfully uploaded ${uploadedItems.length} file(s)`)
    }
    
    if (uploadedItems.length !== fileArray.length) {
      console.warn(`${fileArray.length - uploadedItems.length} file(s) failed to upload`)
    }
    
  } catch (error) {
    console.error('Failed to upload media:', error)
  } finally {
    isUploading.value = false
    uploadProgress.value = 0
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

// Delete functions
const handleDeleteItem = (item: MediaItem) => {
  // Only allow deleting uploaded media
  if (item.source !== 'upload') {
    console.warn('Cannot delete stock media')
    return
  }
  deleteItem.value = item
}

const cancelDelete = () => {
  deleteItem.value = null
  isDeleting.value = false
}

const confirmDelete = async () => {
  if (!deleteItem.value || !deleteItem.value.uuid) return
  
  try {
    isDeleting.value = true
    await mediaAPI.deleteMedia(deleteItem.value.uuid)
    
    // Remove the item from the current view
    mediaItems.value = mediaItems.value.filter(item => item.uuid !== deleteItem.value?.uuid)
    
    // Close the modal
    deleteItem.value = null
    
    console.log('Media deleted successfully')
  } catch (error) {
    console.error('Failed to delete media:', error)
  } finally {
    isDeleting.value = false
  }
}

// Download function
const downloadMedia = async (item: MediaItem) => {
  try {
    const response = await fetch(item.url, {
      method: 'GET',
      headers: {
        'Accept': 'image/*',
      },
    })
    
    if (!response.ok) {
      throw new Error(`Failed to download: ${response.statusText}`)
    }
    
    const blob = await response.blob()
    const url = window.URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url
    
    // Generate filename with proper extension from mimeType
    const getExtensionFromMimeType = (mimeType: string): string => {
      const mimeToExt: Record<string, string> = {
        'image/jpeg': 'jpg',
        'image/jpg': 'jpg',
        'image/png': 'png',
        'image/gif': 'gif',
        'image/webp': 'webp',
        'image/svg+xml': 'svg',
        'image/bmp': 'bmp',
        'image/tiff': 'tiff',
        'image/x-icon': 'ico'
      }
      return mimeToExt[mimeType.toLowerCase()] || 'jpg'
    }
    
    const extension = getExtensionFromMimeType(item.mimeType)
    const filename = item.name 
      ? `${item.name.replace(/\.[^/.]+$/, "")}.${extension}`
      : `media_${item.id}.${extension}`
    
    link.download = filename
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
    window.URL.revokeObjectURL(url)
    
    console.log(`Downloaded: ${filename}`)
  } catch (error) {
    console.error('Failed to download media:', error)
    // You could add a toast notification here
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
  
  // Set up ResizeObserver for better performance than window resize
  if (contentContainer.value) {
    updateColumns()
    
    const resizeObserver = new ResizeObserver(() => {
      updateColumns()
    })
    
    resizeObserver.observe(contentContainer.value)
    
    // Store observer for cleanup
    ;(contentContainer.value as any)._resizeObserver = resizeObserver
  }
})

// Cleanup caches on unmount to prevent memory leaks
onUnmounted(() => {
  fileSizeCache.clear()
  
  // Clean up ResizeObserver
  if (contentContainer.value && (contentContainer.value as any)._resizeObserver) {
    ;(contentContainer.value as any)._resizeObserver.disconnect()
  }
})
</script>

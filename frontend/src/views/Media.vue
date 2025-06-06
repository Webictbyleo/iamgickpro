<template>
  <AppLayout>
    <div class="space-y-8">
      <!-- Header Section with Enhanced Design -->
      <div class="relative overflow-hidden bg-gradient-to-br from-violet-50 via-white to-purple-50 rounded-2xl border border-violet-100/50 p-8">
        <!-- Background Pattern -->
        <div class="absolute inset-0 opacity-30">
          <div class="absolute top-4 right-4 w-32 h-32 bg-gradient-to-br from-violet-400/20 to-purple-400/20 rounded-full blur-xl"></div>
          <div class="absolute bottom-4 left-4 w-24 h-24 bg-gradient-to-br from-blue-400/20 to-violet-400/20 rounded-full blur-xl"></div>
        </div>
        
        <div class="relative flex items-center justify-between">
          <div class="flex items-center space-x-4">
            <div class="w-12 h-12 bg-gradient-to-br from-violet-500 to-purple-600 rounded-xl flex items-center justify-center shadow-lg">
              <PhotoIcon class="w-6 h-6 text-white" />
            </div>
            <div>
              <h1 class="text-3xl font-bold bg-gradient-to-r from-violet-900 via-purple-800 to-violet-900 bg-clip-text text-transparent">
                Stock Media Library
              </h1>
              <p class="text-violet-600/80 mt-1 font-medium">
                Discover millions of high-quality images, videos, and graphics for your designs
              </p>
            </div>
          </div>
          
          <!-- Enhanced Upload Button -->
          <div class="flex items-center space-x-3">
            <div class="hidden sm:flex items-center text-sm text-violet-600/70">
              <span class="w-2 h-2 bg-green-400 rounded-full mr-2 animate-pulse"></span>
              {{ mediaItems.length }} items loaded
            </div>
            <button
              @click="triggerFileUpload"
              class="group relative inline-flex items-center px-6 py-3 bg-gradient-to-r from-violet-600 to-purple-600 text-white rounded-xl font-semibold shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-violet-500/20"
            >
              <ArrowUpTrayIcon class="w-5 h-5 mr-2 group-hover:scale-110 transition-transform" />
              Upload Media
              
            </button>
            
            <input
              ref="fileInput"
              type="file"
              multiple
              accept="image/*,video/*"
              class="hidden"
              @change="handleFileUpload"
            >
          </div>
        </div>
      </div>

      <!-- Enhanced Search and Filters -->
      <div class="bg-white rounded-2xl shadow-lg border border-gray-100 overflow-hidden">
        <div class="p-6">
          <div class="flex flex-col lg:flex-row lg:items-center space-y-4 lg:space-y-0 lg:space-x-6">
            <!-- Advanced Search Input -->
            <div class="flex-1">
              <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                  <MagnifyingGlassIcon class="h-5 w-5 text-violet-400" />
                </div>
                <input
                  v-model="searchQuery"
                  type="text"
                  placeholder="Search for images, videos, graphics, icons..."
                  class="block w-full pl-12 pr-4 py-3.5 border border-gray-200 rounded-xl bg-gray-50/50 text-gray-900 placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-violet-500/20 focus:border-violet-300 focus:bg-white transition-all duration-200 text-sm"
                  @keyup.enter="searchMedia"
                  @input="debouncedSearch"
                />
                <div class="absolute inset-y-0 right-0 pr-4 flex items-center">
                  <kbd class="inline-flex items-center px-2 py-1 border border-gray-200 rounded text-xs font-mono text-gray-500 bg-gray-100">
                    ⏎
                  </kbd>
                </div>
              </div>
            </div>
            
            <!-- Enhanced Filters -->
            <div class="flex items-center space-x-4">
              <!-- Type Filter -->
              <div class="relative">
                <select
                  v-model="selectedType"
                  class="appearance-none bg-white border border-gray-200 rounded-xl px-4 py-3 pr-10 text-sm font-medium text-gray-700 focus:outline-none focus:ring-2 focus:ring-violet-500/20 focus:border-violet-300 transition-all duration-200"
                  @change="searchMedia"
                >
                  <option value="">All Types</option>
                  <option value="image">📸 Images</option>
                  <option value="video">🎥 Videos</option>
                  <option value="vector">✨ Vectors</option>
                  <option value="icon">🎯 Icons</option>
                </select>
                <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                  <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                  </svg>
                </div>
              </div>
              
              <!-- Search Button -->
              <button
                @click="searchMedia"
                :disabled="isLoading"
                class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-violet-600 to-purple-600 text-white rounded-xl font-semibold shadow-lg hover:shadow-xl disabled:opacity-50 disabled:cursor-not-allowed hover:from-violet-700 hover:to-purple-700 transform hover:-translate-y-0.5 transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-violet-500/20"
              >
                <MagnifyingGlassIcon v-if="!isLoading" class="w-4 h-4 mr-2" />
                <div v-else class="w-4 h-4 mr-2 border-2 border-white border-t-transparent rounded-full animate-spin"></div>
                {{ isLoading ? 'Searching...' : 'Search' }}
              </button>
            </div>
          </div>
        </div>
        
        <!-- Enhanced Tabs -->
        <div class="border-t border-gray-100 bg-gray-50/50">
          <nav class="flex space-x-8 px-6">
            <button
              v-for="tab in tabs"
              :key="tab.id"
              @click="activeTab = tab.id"
              :class="[
                activeTab === tab.id
                  ? 'border-violet-500 text-violet-600 bg-white shadow-sm'
                  : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
                'whitespace-nowrap py-4 px-4 border-b-2 font-semibold text-sm rounded-t-lg transition-all duration-200 flex items-center space-x-2'
              ]"
            >
              <span class="text-lg">{{ tab.icon }}</span>
              <span>{{ tab.name }}</span>
              <span v-if="tab.id === 'uploads' && userMediaCount > 0" class="bg-violet-100 text-violet-600 px-2 py-0.5 rounded-full text-xs font-bold">
                {{ userMediaCount }}
              </span>
            </button>
          </nav>
        </div>
      </div>

      <!-- Enhanced Loading State -->
      <div v-if="isLoading" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-6">
        <div
          v-for="i in 18"
          :key="i"
          class="group relative aspect-square bg-gradient-to-br from-gray-100 to-gray-200 rounded-2xl overflow-hidden animate-pulse"
        >
          <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/20 to-transparent -translate-x-full group-hover:translate-x-full transition-transform duration-1000"></div>
        </div>
      </div>

      <!-- Enhanced Media Grid -->
      <div v-else-if="mediaItems.length > 0" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-6">
        <div
          v-for="item in mediaItems"
          :key="item.id"
          class="group relative aspect-square bg-gray-100 rounded-2xl overflow-hidden cursor-pointer transform hover:scale-105 transition-all duration-300 hover:shadow-2xl hover:shadow-violet-500/25"
          @click="selectMedia(item)"
        >
          <!-- Media Image -->
          <img
            :src="item.thumbnail || item.url"
            :alt="item.name || 'Media item'"
            class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110"
            loading="lazy"
          />
          
          <!-- Overlay -->
          <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-all duration-300">
            <!-- Action Buttons -->
            <div class="absolute inset-0 flex items-center justify-center">
              <div class="flex items-center space-x-2 transform translate-y-4 group-hover:translate-y-0 transition-transform duration-300">
                <button 
                  @click.stop="selectMedia(item)"
                  class="bg-white/90 backdrop-blur-sm text-gray-900 px-4 py-2 rounded-lg text-sm font-semibold hover:bg-white shadow-lg transform hover:scale-105 transition-all duration-200"
                >
                  Select
                </button>
                <button 
                  @click.stop="previewMedia(item)"
                  class="bg-violet-600/90 backdrop-blur-sm text-white p-2 rounded-lg hover:bg-violet-700 shadow-lg transform hover:scale-105 transition-all duration-200"
                >
                  <EyeIcon class="w-4 h-4" />
                </button>
              </div>
            </div>
            
            <!-- Media Info Overlay -->
            <div class="absolute bottom-0 left-0 right-0 p-3 transform translate-y-2 group-hover:translate-y-0 transition-transform duration-300">
              <div class="flex items-center justify-between text-white">
                <div class="flex-1 min-w-0">
                  <p class="text-sm font-medium truncate">{{ item.name || 'Untitled' }}</p>
                  <p class="text-xs text-gray-300">{{ formatFileSize(item.size) }}</p>
                </div>
              </div>
            </div>
          </div>
          
          <!-- Media Type Badge -->
          <div class="absolute top-3 right-3">
            <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-bold bg-black/70 backdrop-blur-sm text-white border border-white/20">
              <span class="mr-1">{{ getTypeIcon(item.type) }}</span>
              {{ item.type?.toUpperCase() || 'FILE' }}
            </span>
          </div>
          
          <!-- Premium Badge (if applicable) -->
          <div v-if="item.isPremium" class="absolute top-3 left-3">
            <span class="inline-flex items-center px-2 py-1 rounded-lg text-xs font-bold bg-gradient-to-r from-yellow-400 to-orange-500 text-white shadow-lg">
              ⭐ PRO
            </span>
          </div>
        </div>
      </div>

      <!-- Enhanced Empty State -->
      <div v-else class="text-center py-16">
        <div class="relative">
          <!-- Background decoration -->
          <div class="absolute inset-0 flex items-center justify-center">
            <div class="w-32 h-32 bg-gradient-to-br from-violet-100 to-purple-100 rounded-full blur-3xl opacity-50"></div>
          </div>
          
          <div class="relative">
            <div class="w-20 h-20 bg-gradient-to-br from-violet-500 to-purple-600 rounded-2xl flex items-center justify-center mx-auto mb-6 shadow-lg">
              <PhotoIcon class="w-10 h-10 text-white" />
            </div>
            
            <h3 class="text-2xl font-bold text-gray-900 mb-3">
              {{ getEmptyStateTitle() }}
            </h3>
            <p class="text-gray-600 mb-8 max-w-md mx-auto leading-relaxed">
              {{ getEmptyStateDescription() }}
            </p>
            
            <div class="flex flex-col sm:flex-row items-center justify-center space-y-3 sm:space-y-0 sm:space-x-4">
              <button
                v-if="activeTab === 'uploads'"
                @click="triggerFileUpload"
                class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-violet-600 to-purple-600 text-white rounded-xl font-semibold shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 transition-all duration-200"
              >
                <ArrowUpTrayIcon class="h-5 w-5 mr-2" />
                Upload Your First Media
              </button>
              
              <button
                v-else
                @click="clearSearch"
                class="inline-flex items-center px-6 py-3 bg-white text-gray-700 border border-gray-300 rounded-xl font-semibold hover:bg-gray-50 transition-all duration-200"
              >
                <MagnifyingGlassIcon class="h-5 w-5 mr-2" />
                Try Different Search
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Enhanced Pagination -->
      <div v-if="totalPages > 1" class="flex items-center justify-center space-x-2">
        <button
          @click="goToPage(currentPage - 1)"
          :disabled="currentPage === 1"
          class="p-2 rounded-lg border border-gray-300 text-gray-500 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-200"
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
                ? 'bg-gradient-to-r from-violet-600 to-purple-600 text-white shadow-lg'
                : 'bg-white text-gray-700 hover:bg-gray-50 border border-gray-300',
              'px-4 py-2 rounded-lg text-sm font-semibold transition-all duration-200 min-w-[40px]'
            ]"
          >
            {{ page }}
          </button>
        </div>
        
        <button
          @click="goToPage(currentPage + 1)"
          :disabled="currentPage === totalPages"
          class="p-2 rounded-lg border border-gray-300 text-gray-500 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-200"
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
import { ref, onMounted, watch, computed } from 'vue'
import { 
  MagnifyingGlassIcon, 
  ArrowUpTrayIcon, 
  PhotoIcon,
  EyeIcon
} from '@heroicons/vue/24/outline'
import AppLayout from '@/components/layout/AppLayout.vue'
import { mediaAPI } from '@/services/api'
import type { MediaItem } from '@/types'

// Reactive data
const searchQuery = ref('')
const selectedType = ref('')
const activeTab = ref('stock')
const isLoading = ref(false)
const mediaItems = ref<MediaItem[]>([])
const currentPage = ref(1)
const totalPages = ref(1)
const fileInput = ref<HTMLInputElement>()
const previewItem = ref<MediaItem | null>(null)
const searchTimeout = ref<NodeJS.Timeout>()

// Enhanced tabs with icons
const tabs = [
  { id: 'stock', name: 'Stock Media', icon: '🎨' },
  { id: 'uploads', name: 'My Uploads', icon: '📁' },
]

// Computed properties
const userMediaCount = computed(() => {
  return activeTab.value === 'uploads' ? mediaItems.value.length : 0
})

// Utility functions
const debouncedSearch = () => {
  if (searchTimeout.value) {
    clearTimeout(searchTimeout.value)
  }
  searchTimeout.value = setTimeout(searchMedia, 500)
}

const formatFileSize = (bytes: number | undefined): string => {
  if (!bytes) return 'Unknown size'
  const sizes = ['B', 'KB', 'MB', 'GB']
  const i = Math.floor(Math.log(bytes) / Math.log(1024))
  return Math.round(bytes / Math.pow(1024, i) * 100) / 100 + ' ' + sizes[i]
}

const getTypeIcon = (type: string | undefined): string => {
  const iconMap: Record<string, string> = {
    'image': '📸',
    'video': '🎥',
    'vector': '✨',
    'icon': '🎯',
    'audio': '🎵',
    'document': '📄'
  }
  return iconMap[type?.toLowerCase() || ''] || '📁'
}

const getEmptyStateTitle = (): string => {
  if (activeTab.value === 'uploads') {
    return userMediaCount.value === 0 ? 'No uploads yet' : 'No matching uploads'
  }
  return searchQuery.value ? 'No media found' : 'Start your search'
}

const getEmptyStateDescription = (): string => {
  if (activeTab.value === 'uploads') {
    return userMediaCount.value === 0 
      ? 'Upload your first media file to build your personal library' 
      : 'Try adjusting your search criteria or upload new media'
  }
  return searchQuery.value 
    ? 'Try different keywords or browse our stock collection' 
    : 'Search through millions of high-quality images, videos, and graphics'
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
  if (activeTab.value === 'uploads') {
    await loadUserUploads()
  } else {
    await loadStockMedia()
  }
}

const loadStockMedia = async () => {
  try {
    isLoading.value = true
    const response = await mediaAPI.getStockMedia({
      query: searchQuery.value,
      category: selectedType.value, // Use category instead of type for stock media
      page: currentPage.value,
      limit: 24
    })
    
    if (response.data) {
      mediaItems.value = response.data.data.media
      totalPages.value = response.data.data.pagination.totalPages
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
      type: selectedType.value as any,
      page: currentPage.value,
      limit: 24
    })
    
    if (response.data) {
      mediaItems.value = response.data.data.media
      totalPages.value = response.data.data.pagination.totalPages
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

const selectMedia = (item: MediaItem) => {
  // TODO: Implement media selection for editor
  // This would typically add the media to the current design or copy URL to clipboard
  if (navigator.clipboard) {
    navigator.clipboard.writeText(item.url)
    console.log('Media URL copied to clipboard:', item.url)
  }
}

const previewMedia = (item: MediaItem) => {
  previewItem.value = item
}

const closePreview = () => {
  previewItem.value = null
}

const clearSearch = () => {
  searchQuery.value = ''
  selectedType.value = ''
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
  searchMedia()
})

// Initialize with stock media
onMounted(() => {
  searchMedia()
})
</script>

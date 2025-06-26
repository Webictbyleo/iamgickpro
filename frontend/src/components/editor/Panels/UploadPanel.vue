<template>
  <div class="flex flex-col h-full">
    <!-- Header -->
    <div class="p-4 border-b border-gray-200 bg-white">
      <h3 class="text-lg font-semibold text-gray-900">Your Uploads</h3>
      <p class="text-sm text-gray-600 mt-1">Manage and use your uploaded image files</p>
    </div>

    <!-- Hidden file input (only accept images) -->
    <input
      ref="fileInput"
      type="file"
      multiple
      accept="image/*"
      @change="handleFileUpload"
      class="hidden"
    />

    <!-- Enhanced Search and Upload Section -->
    <div v-if="userMedia.length > 0" class="p-4 border-b border-gray-200 bg-white">
      <div class="space-y-3">
        <!-- Enhanced Search Input -->
        <div class="relative">
          <input
            v-model="searchQuery"
            type="text"
            placeholder="Search your uploads..."
            class="w-full pl-10 pr-10 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200 bg-gray-50 focus:bg-white"
            @keydown.enter="handleSearch"
          />
          <svg class="absolute left-3 top-3.5 h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
          </svg>
          <!-- Clear search button -->
          <button
            v-if="searchQuery"
            @click="clearSearch"
            class="absolute right-3 top-3.5 h-5 w-5 text-gray-400 hover:text-gray-600 transition-colors"
          >
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <!-- Search Stats -->
        <div v-if="searchQuery && !isLoadingUserMedia" class="flex items-center justify-between text-xs text-gray-500">
          <span>{{ filteredUploads.length }} results for "{{ searchQuery }}"</span>
          <span v-if="hasMoreUserMedia" class="text-blue-600">+ more available</span>
        </div>

        <!-- Enhanced Upload Button -->
        <button
          @click="fileInput?.click()"
          class="w-full flex items-center justify-center gap-2 px-4 py-3 text-sm font-medium text-blue-600 bg-blue-50 border border-blue-200 rounded-lg hover:bg-blue-100 hover:border-blue-300 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-all duration-200 shadow-sm hover:shadow-md"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
          </svg>
          <span>Upload More Files</span>
        </button>
      </div>
    </div>

    <!-- Sticky Type Filter Tabs -->
    <div v-if="userMedia.length > 0" class="sticky top-0 z-10 bg-white border-b border-gray-200">
      <div class="px-4 py-3">
        <div class="flex space-x-1 bg-gray-100 rounded-lg p-1">
          <button
            v-for="filter in typeFilters"
            :key="filter.id"
            @click="selectedTypeFilter = filter.id"
            :class="[
              'flex-1 px-3 py-2 text-sm font-medium rounded-md transition-colors whitespace-nowrap text-center',
              selectedTypeFilter === filter.id
                ? 'bg-white text-gray-900 shadow-sm'
                : 'text-gray-500 hover:text-gray-700'
            ]"
          >
            <component :is="iconComponents[filter.icon as keyof typeof iconComponents]" class="w-4 h-4 mx-auto mb-1" />
            <span class="block text-xs">{{ filter.label }}</span>
          </button>
        </div>
      </div>
    </div>

    <!-- Scrollable Content Area -->
    <div class="flex-1 overflow-y-auto">
      <!-- Enhanced Loading State -->
      <div v-if="isLoadingUserMedia && userMedia.length === 0" class="space-y-4">
        <div class="text-center py-8">
          <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mx-auto"></div>
          <p class="mt-2 text-sm text-gray-600">Loading your uploads...</p>
        </div>
        <!-- Loading skeleton -->
        <div class="px-4">
          <div class="grid grid-cols-2 gap-3">
            <div v-for="i in 6" :key="i" class="aspect-square bg-gray-200 rounded-lg animate-pulse"></div>
          </div>
        </div>
      </div>
      
      <!-- Enhanced Empty State -->
      <div v-else-if="filteredUploads.length === 0 && !isLoadingUserMedia" class="text-center py-12">
        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
        </svg>
        <h3 class="mt-2 text-sm font-medium text-gray-900">
          {{ searchQuery ? 'No uploads found' : userMedia.length === 0 ? 'No uploads yet' : `No ${selectedTypeFilter}s found` }}
        </h3>
        <p class="mt-1 text-sm text-gray-500">
          {{ searchQuery 
            ? 'Try adjusting your search terms' 
            : userMedia.length === 0
            ? 'Upload your first media files to start building your design library' 
            : `Upload some ${selectedTypeFilter} files or try a different filter`
          }}
        </p>
        
        <!-- Action buttons -->
        <div class="mt-4 space-y-2">
          <button
            v-if="!searchQuery && userMedia.length === 0"
            @click="fileInput?.click()"
            class="mx-auto flex items-center justify-center gap-2 px-6 py-3 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-all duration-200 shadow-sm hover:shadow-md"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
            </svg>
            <span>Upload Your First File</span>
          </button>
          <button
            v-if="searchQuery"
            @click="clearSearch"
            class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors text-sm"
          >
            Clear Search
          </button>
          <p v-if="userMedia.length === 0" class="text-xs text-gray-400">
            Drag and drop works too!
          </p>
        </div>
      </div>
      
      <!-- Enhanced Upload Grid -->
      <div v-else class="p-4 space-y-4">
        <!-- Results header -->
        <div v-if="searchQuery" class="flex items-center justify-between">
          <h4 class="text-sm font-medium text-gray-900">{{ filteredUploads.length }} {{ selectedTypeFilter }}s</h4>
          <button
            @click="clearSearch"
            class="text-xs text-gray-500 hover:text-gray-700 transition-colors"
          >
            Clear search
          </button>
        </div>

        <!-- Enhanced Grid -->
        <div class="grid grid-cols-2 gap-3">
          <div
            v-for="file in filteredUploads"
            :key="file.id"
            @click="addMedia(file)"
            class="relative group cursor-pointer border border-gray-200 rounded-lg overflow-hidden hover:border-blue-500 hover:shadow-lg transition-all duration-200 bg-white transform hover:scale-[1.02]"
          >
            <!-- Enhanced Media Preview - Images only -->
            <div class="aspect-square bg-gradient-to-br from-gray-50 to-gray-100 flex items-center justify-center relative overflow-hidden">
              <img
                v-if="file.type === 'image'"
                :src="file.thumbnail || file.url"
                :alt="file.name"
                class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-200"
                loading="lazy"
                @error="handleImageError"
              />
              <div v-else class="text-gray-400 bg-gray-100 w-full h-full flex items-center justify-center">
                <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
              </div>
              
              <!-- Enhanced selection indicator -->
              <div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-20 transition-all flex items-center justify-center">
                <div class="bg-white rounded-full p-2 opacity-0 group-hover:opacity-100 transition-opacity shadow-lg">
                  <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                  </svg>
                </div>
              </div>
            </div>

            <!-- File info overlay -->
            <div class="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-black/60 to-transparent p-2 opacity-0 group-hover:opacity-100 transition-opacity">
              <p class="text-white text-xs truncate">{{ file.name }}</p>
              <p v-if="file.size" class="text-white/80 text-xs">{{ formatFileSize(file.size) }}</p>
            </div>
          </div>
        </div>
        
        <!-- Enhanced Load More Button -->
        <div v-if="hasMoreUserMedia && !isLoadingUserMedia" class="pt-2">
          <button
            @click="loadMoreUserMedia"
            class="w-full py-3 px-4 bg-gray-50 hover:bg-gray-100 border border-gray-200 hover:border-gray-300 rounded-lg transition-all duration-200 text-sm font-medium text-gray-700 hover:text-gray-900 flex items-center justify-center space-x-2"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            <span>Load More Files</span>
          </button>
        </div>
        
        <!-- Loading more indicator -->
        <div v-if="isLoadingUserMedia && userMedia.length > 0" class="text-center py-4">
          <div class="inline-flex items-center space-x-2">
            <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-blue-600"></div>
            <span class="text-sm text-gray-600">Loading more files...</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue'
import { useUserMedia } from '@/composables/useUserMedia'
import type { MediaItem } from '@/types'
import { 
  PhotoIcon, 
  VideoCameraIcon, 
  MusicalNoteIcon 
} from '@heroicons/vue/24/outline'

// Icon components for template
const iconComponents = {
  PhotoIcon,
  VideoCameraIcon,
  MusicalNoteIcon
}

const emit = defineEmits<{
  addMedia: [mediaData: any]
}>()

// File input
const fileInput = ref<HTMLInputElement>()

// Search and filter
const searchQuery = ref('')
const searchTimeout = ref<NodeJS.Timeout | null>(null)
const selectedTypeFilter = ref('image')
const isDragOver = ref(false)

const typeFilters = computed(() => [
  { 
    id: 'image', 
    label: 'Images', 
    icon: 'PhotoIcon'
  }
])

// User media management
const {
  userMedia,
  isLoadingUserMedia,
  hasMoreUserMedia,
  searchUserMedia,
  loadMoreUserMedia,
  uploadFiles
} = useUserMedia()

// Computed filters
const filteredUploads = computed(() => {
  let filtered = userMedia.value

  // Filter by search query
  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase()
    filtered = filtered.filter(file => 
      file.name.toLowerCase().includes(query) ||
      (file.tags && file.tags.some((tag: string) => tag.toLowerCase().includes(query)))
    )
  }

  // Filter by type
  filtered = filtered.filter(file => file.type === selectedTypeFilter.value)

  return filtered
})

// Enhanced search functionality
const handleSearch = () => {
  if (!searchQuery.value.trim()) return
  // The filtering is handled by the computed property, so no additional action needed
}

const clearSearch = () => {
  searchQuery.value = ''
}

// File upload handling
const handleFileUpload = async (event: Event) => {
  const target = event.target as HTMLInputElement
  const files = target.files
  
  if (!files || files.length === 0) return
  
  try {
    await uploadFiles(files)
    // Reset input
    target.value = ''
  } catch (error) {
    console.error('Upload failed:', error)
  }
}

// Error handling
const handleImageError = (event: Event) => {
  const target = event.target as HTMLImageElement
  target.style.display = 'none'
  const parent = target.parentElement
  if (parent) {
    // Show a fallback icon or placeholder
    parent.innerHTML = `
      <div class="w-full h-full bg-gray-100 flex items-center justify-center">
        <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
        </svg>
      </div>
    `
  }
}

// Drag and drop handling
const handleDrop = async (event: DragEvent) => {
  event.preventDefault()
  isDragOver.value = false
  const files = event.dataTransfer?.files
  
  if (!files || files.length === 0) return
  
  try {
    await uploadFiles(files)
  } catch (error) {
    console.error('Upload failed:', error)
  }
}

// Media addition
const addMedia = (mediaData: MediaItem) => {
  const imageData = {
    src: mediaData.url || mediaData.thumbnailUrl,
    alt: mediaData.name || 'Uploaded media'
  }
  emit('addMedia', imageData)
}

// Utility functions
const formatFileSize = (bytes: number): string => {
  if (bytes === 0) return '0 Bytes'
  const k = 1024
  const sizes = ['Bytes', 'KB', 'MB', 'GB']
  const i = Math.floor(Math.log(bytes) / Math.log(k))
  return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i]
}

// Initialize
onMounted(() => {
  searchUserMedia('')
})

// Enhanced search with debouncing
watch(searchQuery, (newQuery) => {
  if (searchTimeout.value) clearTimeout(searchTimeout.value)
  searchTimeout.value = setTimeout(() => {
    // The filtering is handled by computed property, no API call needed for local filtering
    if (newQuery.trim()) handleSearch()
  }, 500) // 500ms debounce
})

// Tab switching
watch([selectedTypeFilter], () => {
  // Filtering is handled by computed property
})
</script>

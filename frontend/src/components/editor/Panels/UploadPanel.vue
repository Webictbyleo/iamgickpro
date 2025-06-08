<template>
  <div class="flex flex-col h-full">
    

    <!-- Hidden file input (always present for programmatic access) -->
    <input
      ref="fileInput"
      type="file"
      multiple
      accept="image/*,video/*,audio/*"
      @change="handleFileUpload"
      class="hidden"
    />

    <!-- Upload Section (only show when no uploads or empty filtered results) -->
    <div v-if="shouldShowUploadArea" class="p-3 bg-gradient-to-r from-blue-50 via-indigo-50 to-blue-50 border-b border-gray-200">      
      <!-- Compact Upload Area -->
      <div 
        @click="fileInput?.click()"
        @dragover.prevent="isDragOver = true"
        @dragleave.prevent="isDragOver = false"
        @drop.prevent="handleDrop"
        :class="[
          'relative border-2 border-dashed rounded-lg p-4 cursor-pointer transition-all text-center group overflow-hidden',
          isDragOver
            ? 'border-blue-400 bg-blue-100/50 scale-[1.02] shadow-lg'
            : 'border-blue-300 hover:border-blue-400 hover:bg-blue-100/30'
        ]"
      >
        <!-- Background gradient effect -->
        <div class="absolute inset-0 bg-gradient-to-br from-blue-400/5 via-transparent to-indigo-400/5 opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
        
        <!-- Content -->
        <div class="relative z-10">
          <div class="flex items-center justify-center gap-3 mb-2">
            <!-- Upload icon -->
            <div class="relative">
              <div :class="[
                'w-8 h-8 rounded-lg flex items-center justify-center transition-all duration-300',
                isDragOver 
                  ? 'bg-blue-500 text-white scale-110' 
                  : 'bg-blue-100 text-blue-600 group-hover:bg-blue-200 group-hover:scale-110'
              ]">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                </svg>
              </div>
              <!-- Pulse effect -->
              <div v-if="isDragOver" class="absolute inset-0 w-8 h-8 rounded-lg bg-blue-400 animate-ping opacity-30"></div>
            </div>
            
            <!-- Text content -->
            <div class="text-left">
              <p class="text-sm font-semibold text-gray-900">
                {{ isDragOver ? 'Drop files here!' : 'Upload Files' }}
              </p>
              <p class="text-xs text-gray-600">
                Drag & drop or click to browse
              </p>
            </div>
          </div>
          
          <!-- File type badges -->
          <div class="flex justify-center gap-1.5">
            <span class="px-2 py-0.5 bg-emerald-100 text-emerald-700 rounded-full text-xs font-medium">IMG</span>
            <span class="px-2 py-0.5 bg-red-100 text-red-700 rounded-full text-xs font-medium">VID</span>
            <span class="px-2 py-0.5 bg-purple-100 text-purple-700 rounded-full text-xs font-medium">AUD</span>
          </div>
          
          <!-- Upload limit -->
          <p class="text-xs text-gray-500 mt-1.5">
            Up to 10MB each
          </p>
        </div>
        
        <!-- Animated border effect -->
        <div v-if="isDragOver" class="absolute inset-0 rounded-lg border-2 border-blue-400 animate-pulse"></div>
      </div>
    </div>

    <!-- Search and Filter (only show when there are uploads) -->
    <div v-if="userMedia.length > 0" class="border-b border-gray-200 bg-white">
      <!-- Search Bar -->
      <div class="p-3 pb-0">
        <div class="relative">
          <input
            v-model="searchQuery"
            type="text"
            placeholder="Search your uploads..."
            class="w-full pl-9 pr-8 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm transition-all"
          />
          <svg class="absolute left-2.5 top-2.5 h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
          </svg>
          <!-- Clear search button -->
          <button
            v-if="searchQuery"
            @click="searchQuery = ''"
            class="absolute right-2.5 top-2.5 h-4 w-4 text-gray-400 hover:text-gray-600 transition-colors"
          >
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>
      </div>

      <!-- Upload Button -->
      <div class="p-3 pt-2">
        <button
          @click="fileInput?.click()"
          class="w-full flex items-center justify-center gap-2 px-4 py-2.5 text-sm font-medium text-blue-600 bg-blue-50 border border-blue-200 rounded-lg hover:bg-blue-100 hover:border-blue-300 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-all duration-200"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
          </svg>
          <span>Upload More Files</span>
        </button>
      </div>

      <!-- Type Filter Tabs -->
      <div class="flex">
        <button
          v-for="filter in typeFilters"
          :key="filter.id"
          @click="selectedTypeFilter = filter.id"
          :class="[
            'flex-1 px-4 py-3 text-sm font-medium border-b-2 transition-colors flex items-center justify-center',
            selectedTypeFilter === filter.id
              ? 'text-blue-600 border-blue-600 bg-blue-50/50'
              : 'text-gray-500 border-transparent hover:text-gray-700 hover:border-gray-300'
          ]"
        >
          <component :is="iconComponents[filter.icon as keyof typeof iconComponents]" class="w-4 h-4 mr-2" />
          {{ filter.label }}
        </button>
      </div>
    </div>

    <!-- Content Area -->
    <div class="flex-1 overflow-y-auto">
      <!-- Loading State -->
      <div v-if="isLoadingUserMedia" class="p-4 text-center py-12">
        <div class="mx-auto w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center mb-3">
          <div class="animate-spin rounded-full h-6 w-6 border-2 border-blue-600 border-t-transparent"></div>
        </div>
        <h3 class="text-base font-medium text-gray-900 mb-1">Loading your uploads...</h3>
        <p class="text-sm text-gray-500">This might take a moment</p>
      </div>
      
      <!-- Empty State -->
      <div v-else-if="filteredUploads.length === 0" class="p-4 text-center py-12">
        <div class="mx-auto w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mb-3">
          <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
          </svg>
        </div>
        <h3 class="text-lg font-medium text-gray-900 mb-2">
          {{ searchQuery ? 'No uploads found' : 'No uploads yet' }}
        </h3>
        <p class="text-sm text-gray-500 mb-4 max-w-sm mx-auto">
          {{ searchQuery 
            ? 'Try adjusting your search terms or check your spelling' 
            : 'Upload your first media files to start building your design library' 
          }}
        </p>
        <div v-if="!searchQuery" class="space-y-2">
          <button
            @click="fileInput?.click()"
            class="mx-auto flex items-center justify-center gap-2 px-6 py-3 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-all duration-200 shadow-sm hover:shadow-md"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
            </svg>
            <span>Upload Your First File</span>
          </button>
          <p class="text-xs text-gray-400">
            Drag and drop works too!
          </p>
        </div>
      </div>
      
      <!-- Upload Grid -->
      <div v-else class="p-4">
        <div class="grid grid-cols-2 gap-4">
          <div
            v-for="file in filteredUploads"
            :key="file.id"
            @click="addMedia(file)"
            class="relative group cursor-pointer border border-gray-200 rounded-xl overflow-hidden hover:border-blue-400 hover:shadow-lg transition-all duration-200 bg-white"
          >
            <!-- Media Preview -->
            <div class="aspect-square bg-gradient-to-br from-gray-50 to-gray-100 flex items-center justify-center relative overflow-hidden">
              <img
                v-if="file.type === 'image'"
                :src="file.thumbnail || file.url"
                :alt="file.name"
                class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-200"
                loading="lazy"
              />
              <div v-else-if="file.type === 'video'" class="relative w-full h-full bg-gradient-to-br from-gray-800 to-gray-900 flex items-center justify-center">
                <div class="absolute inset-0 bg-black/20"></div>
                <svg class="w-8 h-8 text-white z-10" fill="currentColor" viewBox="0 0 24 24">
                  <path d="M8 5v14l11-7z"/>
                </svg>
                <div class="absolute bottom-2 right-2 bg-black/75 text-white text-xs px-2 py-1 rounded-full backdrop-blur-sm">
                  {{ formatDuration(file.duration) }}
                </div>
                <div class="absolute top-2 left-2 bg-red-500 text-white text-xs px-2 py-1 rounded-full font-medium">
                  VIDEO
                </div>
              </div>
              <div v-else-if="file.type === 'audio'" class="w-full h-full bg-gradient-to-br from-purple-100 to-purple-200 flex items-center justify-center relative">
                <svg class="w-10 h-10 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3" />
                </svg>
                <div class="absolute bottom-2 right-2 bg-purple-600 text-white text-xs px-2 py-1 rounded-full">
                  {{ formatDuration(file.duration) }}
                </div>
                <div class="absolute top-2 left-2 bg-purple-500 text-white text-xs px-2 py-1 rounded-full font-medium">
                  AUDIO
                </div>
              </div>
              <div v-else class="text-gray-400 bg-gray-100 w-full h-full flex items-center justify-center">
                <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
              </div>
              
              <!-- Selection indicator -->
              <div class="absolute top-2 right-2 w-6 h-6 bg-white/90 backdrop-blur-sm rounded-full flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
                <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                </svg>
              </div>
            </div>

            

            <!-- Hover Overlay -->
            <div class="absolute inset-0 bg-blue-600/10 opacity-0 group-hover:opacity-100 transition-all duration-200 flex items-center justify-center rounded-xl">
              <div class="bg-white/90 backdrop-blur-sm px-3 py-2 rounded-lg shadow-sm opacity-0 group-hover:opacity-100 transition-all duration-200 transform translate-y-2 group-hover:translate-y-0">
                <p class="text-xs font-medium text-gray-900">Click to add</p>
              </div>
            </div>
          </div>
        </div>
        
        <!-- Load More Button -->
        <div v-if="hasMoreUserMedia && !isLoadingUserMedia" class="mt-6 text-center">
          <BaseButton
            @click="loadMoreUserMedia"
            variant="outline"
            size="md"
            class="px-6"
          >
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
            </svg>
            Load More Files
          </BaseButton>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue'
import BaseButton from '@/components/common/BaseButton.vue'
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
const selectedTypeFilter = ref('image')
const isDragOver = ref(false)

const typeFilters = computed(() => [
  { 
    id: 'image', 
    label: 'Images', 
    icon: 'PhotoIcon'
  },
  { 
    id: 'video', 
    label: 'Videos', 
    icon: 'VideoCameraIcon'
  },
  { 
    id: 'audio', 
    label: 'Audio', 
    icon: 'MusicalNoteIcon'
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

// Show upload area only when there are no uploads at all
const shouldShowUploadArea = computed(() => {
  return userMedia.value.length === 0
})

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

const formatDuration = (seconds?: number): string => {
  if (!seconds) return '0:00'
  const minutes = Math.floor(seconds / 60)
  const remainingSeconds = Math.floor(seconds % 60)
  return `${minutes}:${remainingSeconds.toString().padStart(2, '0')}`
}

// Initialize
onMounted(() => {
  searchUserMedia('')
})

// Watch for search changes
watch([searchQuery, selectedTypeFilter], () => {
  if (searchQuery.value || selectedTypeFilter.value) {
    // For filtering, we don't need to make new API calls since we're filtering local data
    // The computed property will handle the filtering
  }
})
</script>

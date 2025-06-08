<template>
  <div class="p-4 space-y-6">
    <!-- Search and Filter -->
    <div class="space-y-3">
      <div class="relative">
        <input
          v-model="searchQuery"
          type="text"
          placeholder="Search media..."
          class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
        />
        <svg class="absolute left-3 top-2.5 h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
        </svg>
      </div>
      
      <!-- Media Type Tabs -->
      <div class="overflow-x-auto">
        <div class="flex space-x-1 bg-gray-100 rounded-lg p-1 min-w-max">
          <button
            v-for="tab in mediaTabs"
            :key="tab.id"
            @click="activeTab = tab.id"
            :class="[
              'flex-shrink-0 px-3 py-2 text-sm font-medium rounded-md transition-colors whitespace-nowrap',
              activeTab === tab.id
                ? 'bg-white text-gray-900 shadow-sm'
                : 'text-gray-500 hover:text-gray-700'
            ]"
          >
            {{ tab.label }}
          </button>
        </div>
      </div>
    </div>

    <!-- Upload Section -->
    <div v-if="activeTab === 'uploads'" class="space-y-4">
      <!-- Hidden file input -->
      <input
        ref="fileInput"
        type="file"
        multiple
        accept="image/*,video/*"
        @change="handleFileUpload"
        class="hidden"
      />
      
      <!-- Loading State -->
      <div v-if="isLoadingUserMedia" class="text-center py-8">
        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mx-auto"></div>
        <p class="mt-2 text-sm text-gray-600">Loading your media...</p>
      </div>
      
      <!-- Empty State with Upload CTA -->
      <div v-else-if="filteredUploads.length === 0 && !searchQuery" class="text-center py-12">
        <div 
          @click="fileInput?.click()"
          @dragover.prevent
          @drop.prevent="handleDrop"
          class="border-2 border-dashed border-gray-300 rounded-lg p-8 cursor-pointer hover:border-blue-400 hover:bg-blue-50 transition-all"
        >
          <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
          </svg>
          <h3 class="mt-4 text-lg font-medium text-gray-900">Upload your media</h3>
          <p class="mt-2 text-sm text-gray-600">
            Click here or drag and drop your files
          </p>
          <p class="mt-1 text-xs text-gray-500">PNG, JPG, GIF, MP4 up to 10MB</p>
        </div>
      </div>
      
      <!-- Search Empty State -->
      <div v-else-if="filteredUploads.length === 0 && searchQuery" class="text-center py-12">
        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
        </svg>
        <h3 class="mt-2 text-sm font-medium text-gray-900">No media found</h3>
        <p class="mt-1 text-sm text-gray-500">Try adjusting your search terms</p>
      </div>
      
      <!-- Media Grid with Upload Button -->
      <div v-else>
        <!-- Upload Button (fixed position) -->
        <div class="mb-4">
          <BaseButton
            @click="fileInput?.click()"
            variant="primary"
            size="sm"
            class="w-full"
          >
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
            </svg>
            Upload Media
          </BaseButton>
        </div>
        
        <!-- Uploaded Files Grid -->
        <div class="grid grid-cols-2 gap-3">
          <div
            v-for="file in filteredUploads"
            :key="file.id"
            @click="addMedia(file)"
            class="relative group cursor-pointer border border-gray-200 rounded-lg overflow-hidden hover:border-blue-500 transition-colors"
          >
            <div class="aspect-square bg-gray-100 flex items-center justify-center">
              <img
                v-if="file.type === 'image'"
                :src="file.thumbnail || file.url"
                :alt="file.name"
                class="w-full h-full object-cover"
              />
              <div v-else class="text-gray-400">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                </svg>
              </div>
            </div>
            <div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-20 transition-all flex items-center justify-center">
              <svg class="w-6 h-6 text-white opacity-0 group-hover:opacity-100 transition-opacity" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
              </svg>
            </div>
          </div>
        </div>
        
        <!-- Load More Button for Uploads -->
        <BaseButton
          v-if="hasMoreUserMedia && !isLoadingUserMedia"
          @click="loadMoreUserMedia"
          variant="outline"
          size="sm"
          full-width
          class="mt-4"
        >
          Load More Uploads
        </BaseButton>
      </div>
    </div>

    <!-- Stock Photos -->
    <div v-if="activeTab === 'photos'" class="space-y-4">
      <div v-if="isLoadingPhotos" class="text-center py-8">
        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mx-auto"></div>
        <p class="mt-2 text-sm text-gray-600">Loading photos...</p>
      </div>
      
      <!-- Empty State for Photos -->
      <div v-else-if="filteredPhotos.length === 0" class="text-center py-12">
        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
        </svg>
        <h3 class="mt-2 text-sm font-medium text-gray-900">No photos found</h3>
        <p class="mt-1 text-sm text-gray-500">
          {{ searchQuery ? 'Try adjusting your search terms' : 'Search for photos to get started' }}
        </p>
      </div>
      
      <div v-else class="grid grid-cols-2 gap-3">
        <div
          v-for="photo in filteredPhotos"
          :key="photo.id"
          @click="addMedia(photo)"
          class="relative group cursor-pointer border border-gray-200 rounded-lg overflow-hidden hover:border-blue-500 transition-colors"
        >
          <div class="aspect-square bg-gray-100">
            <img
              :src="photo.thumbnail"
              :alt="photo.alt"
              class="w-full h-full object-cover"
              loading="lazy"
            />
          </div>
          <div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-20 transition-all flex items-center justify-center">
            <svg class="w-6 h-6 text-white opacity-0 group-hover:opacity-100 transition-opacity" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
            </svg>
          </div>
        </div>
      </div>
      
      <BaseButton
        v-if="stockPhotos.length > 0 && !isLoadingPhotos"
        @click="loadMorePhotos"
        variant="outline"
        size="sm"
        full-width
      >
        Load More Photos
      </BaseButton>
    </div>

    <!-- Icons -->
    <div v-if="activeTab === 'icons'" class="space-y-4">
      <div v-if="isLoadingIcons" class="text-center py-8">
        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mx-auto"></div>
        <p class="mt-2 text-sm text-gray-600">Loading icons...</p>
      </div>
      
      <!-- Empty State for Icons -->
      <div v-else-if="filteredIcons.length === 0" class="text-center py-12">
        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zM21 5a2 2 0 00-2-2h-4a2 2 0 00-2 2v12a4 4 0 004 4 4 4 0 004-4V5z" />
        </svg>
        <h3 class="mt-2 text-sm font-medium text-gray-900">No icons found</h3>
        <p class="mt-1 text-sm text-gray-500">
          {{ searchQuery ? 'Try adjusting your search terms' : 'Search for icons to get started' }}
        </p>
      </div>
      
      <div v-else class="grid grid-cols-3 gap-3">
        <div
          v-for="icon in filteredIcons"
          :key="icon.id"
          @click="addMedia(icon)"
          class="relative group cursor-pointer border border-gray-200 rounded-lg p-4 hover:border-blue-500 transition-colors bg-white"
        >
          <div class="aspect-square flex items-center justify-center">
            <img
              :src="icon.thumbnail"
              :alt="icon.alt"
              class="w-8 h-8 object-contain"
            />
          </div>
          <div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-10 transition-all rounded-lg"></div>
        </div>
      </div>
      
      <BaseButton
        v-if="stockIcons.length > 0 && !isLoadingIcons"
        @click="loadMoreIcons"
        variant="outline"
        size="sm"
        full-width
      >
        Load More Icons
      </BaseButton>
    </div>

    <!-- Shapes -->
    <div v-if="activeTab === 'shapes'" class="space-y-4">
      <div v-if="isLoadingShapes" class="text-center py-8">
        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mx-auto"></div>
        <p class="mt-2 text-sm text-gray-600">Loading shapes...</p>
      </div>
      
      <!-- Empty State for Shapes -->
      <div v-else-if="filteredShapes.length === 0" class="text-center py-12">
        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" />
        </svg>
        <h3 class="mt-2 text-sm font-medium text-gray-900">No shapes found</h3>
        <p class="mt-1 text-sm text-gray-500">
          {{ searchQuery ? 'Try adjusting your search terms' : 'Search for shapes to get started' }}
        </p>
      </div>
      
      <div v-else class="grid grid-cols-3 gap-3">
        <div
          v-for="shape in filteredShapes"
          :key="shape.id"
          @click="addMedia(shape)"
          class="relative group cursor-pointer border border-gray-200 rounded-lg p-4 hover:border-blue-500 transition-colors bg-white"
        >
          <div class="aspect-square flex items-center justify-center">
            <img
              :src="shape.thumbnail"
              :alt="shape.alt"
              class="w-8 h-8 object-contain"
            />
          </div>
          <p class="text-xs text-center text-gray-600 mt-2">{{ shape.alt }}</p>
          <div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-5 transition-all rounded-lg"></div>
        </div>
      </div>
      
      <BaseButton
        v-if="stockShapes.length > 0 && !isLoadingShapes"
        @click="loadMoreShapes"
        variant="outline"
        size="sm"
        full-width
      >
        Load More Shapes
      </BaseButton>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue'
import BaseButton from '@/components/common/BaseButton.vue'
import { useStockMedia, type StockMediaItem } from '../../../composables/useStockMedia'
import { useUserMedia } from '../../../composables/useUserMedia'
import type { MediaItem } from '@/types'

const emit = defineEmits<{
  addMedia: [mediaData: any]
}>()

// Tab management
const activeTab = ref('uploads')
const searchQuery = ref('')

const mediaTabs = [
  { id: 'uploads', label: 'Uploads' },
  { id: 'photos', label: 'Photos' },
  { id: 'icons', label: 'Icons' },
  { id: 'shapes', label: 'Shapes' }
]

// File uploads
const fileInput = ref<HTMLInputElement>()

// User media management
const {
  userMedia,
  isLoadingUserMedia,
  hasMoreUserMedia,
  searchUserMedia,
  loadMoreUserMedia,
  uploadFiles
} = useUserMedia()

// Stock media
const {
  stockPhotos,
  stockIcons,
  stockShapes,
  isLoadingPhotos,
  isLoadingIcons,
  isLoadingShapes,
  searchPhotos,
  searchIcons,
  searchShapes,
  loadMorePhotos,
  loadMoreIcons,
  loadMoreShapes
} = useStockMedia()

// Shapes are now managed by the useStockMedia composable

// Computed filters for uploaded files
const filteredUploads = computed(() => {
  if (!searchQuery.value) return userMedia.value
  return userMedia.value.filter(file => 
    file.name.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
    (file.tags && file.tags.some((tag: string) => tag.toLowerCase().includes(searchQuery.value.toLowerCase())))
  )
})

const filteredPhotos = computed(() => {
  if (!searchQuery.value) return stockPhotos.value
  return stockPhotos.value.filter((photo: StockMediaItem) => 
    photo.alt.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
    photo.tags?.some((tag: string) => tag.toLowerCase().includes(searchQuery.value.toLowerCase()))
  )
})

const filteredIcons = computed(() => {
  if (!searchQuery.value) return stockIcons.value
  return stockIcons.value.filter((icon: StockMediaItem) =>
    icon.alt.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
    icon.tags?.some((tag: string) => tag.toLowerCase().includes(searchQuery.value.toLowerCase()))
  )
})

const filteredShapes = computed(() => {
  if (!searchQuery.value) return stockShapes.value
  return stockShapes.value.filter((shape: StockMediaItem) => 
    shape.alt.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
    shape.tags?.some((tag: string) => tag.toLowerCase().includes(searchQuery.value.toLowerCase()))
  )
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
  const files = event.dataTransfer?.files
  
  if (!files || files.length === 0) return
  
  try {
    await uploadFiles(files)
  } catch (error) {
    console.error('Upload failed:', error)
  }
}

// Media addition
const addMedia = (mediaData: any) => {
  // Handle shapes from stock media
  if (activeTab.value === 'shapes') {
    const shapeData = {
      shapeType: 'custom',
      src: mediaData.src || mediaData.thumbnail || mediaData.url,
      name: mediaData.alt || mediaData.name || 'Shape',
      fill: {
        type: 'solid',
        color: '#3B82F6',
        opacity: 1
      },
      stroke: '#1E40AF',
      strokeWidth: 2,
      strokeOpacity: 1,
      strokeLineCap: 'round',
      strokeLineJoin: 'round'
    }
    emit('addMedia', shapeData)
  } else {
    // Handle images and other media (both uploaded and stock)
    const imageData = {
      src: mediaData.src || mediaData.thumbnail || mediaData.url || mediaData.thumbnailUrl,
      alt: mediaData.alt || mediaData.name || 'Image'
    }
    emit('addMedia', imageData)
  }
}

// Initialize
onMounted(() => {
  // Load initial user uploads
  searchUserMedia('')
  
  // Load initial stock media
  searchPhotos('design')
  searchIcons('design')
  searchShapes('basic')
})

// Watch for search changes
watch(searchQuery, (newQuery) => {
  if (activeTab.value === 'uploads') {
    searchUserMedia(newQuery)
  } else if (activeTab.value === 'photos' && newQuery) {
    searchPhotos(newQuery)
  } else if (activeTab.value === 'icons' && newQuery) {
    searchIcons(newQuery)
  } else if (activeTab.value === 'shapes' && newQuery) {
    searchShapes(newQuery)
  }
})

watch(activeTab, (newTab) => {
  if (newTab === 'uploads' && userMedia.value.length === 0) {
    searchUserMedia('')
  } else if (newTab === 'photos' && stockPhotos.value.length === 0) {
    searchPhotos('design')
  } else if (newTab === 'icons' && stockIcons.value.length === 0) {
    searchIcons('design')
  } else if (newTab === 'shapes' && stockShapes.value.length === 0) {
    searchShapes('basic')
  }
})
</script>

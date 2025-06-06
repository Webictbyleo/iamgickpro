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
      <div class="flex space-x-1 bg-gray-100 rounded-lg p-1">
        <button
          v-for="tab in mediaTabs"
          :key="tab.id"
          @click="activeTab = tab.id"
          :class="[
            'flex-1 px-3 py-2 text-sm font-medium rounded-md transition-colors',
            activeTab === tab.id
              ? 'bg-white text-gray-900 shadow-sm'
              : 'text-gray-500 hover:text-gray-700'
          ]"
        >
          {{ tab.label }}
        </button>
      </div>
    </div>

    <!-- Upload Section -->
    <div v-if="activeTab === 'uploads'" class="space-y-4">
      <div class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center hover:border-gray-400 transition-colors">
        <input
          ref="fileInput"
          type="file"
          multiple
          accept="image/*,video/*"
          @change="handleFileUpload"
          class="hidden"
        />
        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
        </svg>
        <p class="mt-2 text-sm text-gray-600">
          <button @click="fileInput?.click()" class="font-medium text-blue-600 hover:text-blue-500">
            Click to upload
          </button>
          or drag and drop
        </p>
        <p class="text-xs text-gray-500">PNG, JPG, GIF, MP4 up to 10MB</p>
      </div>
      
      <!-- Uploaded Files Grid -->
      <div v-if="uploadedFiles.length > 0" class="grid grid-cols-2 gap-3">
        <div
          v-for="file in filteredUploads"
          :key="file.id"
          @click="addMedia(file)"
          class="relative group cursor-pointer border border-gray-200 rounded-lg overflow-hidden hover:border-blue-500 transition-colors"
        >
          <div class="aspect-square bg-gray-100 flex items-center justify-center">
            <img
              v-if="file.type === 'image'"
              :src="file.thumbnail"
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
    </div>

    <!-- Stock Photos -->
    <div v-if="activeTab === 'photos'" class="space-y-4">
      <div v-if="isLoadingPhotos" class="text-center py-8">
        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mx-auto"></div>
        <p class="mt-2 text-sm text-gray-600">Loading photos...</p>
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
      <div class="grid grid-cols-3 gap-3">
        <div
          v-for="shape in shapes"
          :key="shape.type"
          @click="addShape(shape)"
          class="relative group cursor-pointer border border-gray-200 rounded-lg p-4 hover:border-blue-500 transition-colors bg-white"
        >
          <div class="aspect-square flex items-center justify-center">
            <div :class="shape.iconClass" class="text-2xl text-gray-600 group-hover:text-blue-600 transition-colors">
              {{ shape.icon }}
            </div>
          </div>
          <p class="text-xs text-center text-gray-600 mt-2">{{ shape.label }}</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue'
import BaseButton from '@/components/common/BaseButton.vue'
import { useStockMedia, type StockMediaItem } from '../../../composables/useStockMedia'
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
const uploadedFiles = ref<any[]>([])

// Stock media
const {
  stockPhotos,
  stockIcons,
  isLoadingPhotos,
  isLoadingIcons,
  searchPhotos,
  searchIcons,
  loadMorePhotos,
  loadMoreIcons
} = useStockMedia()

// Built-in shapes
const shapes = [
  { type: 'rectangle', label: 'Rectangle', icon: '▭', iconClass: '' },
  { type: 'circle', label: 'Circle', icon: '●', iconClass: '' },
  { type: 'triangle', label: 'Triangle', icon: '▲', iconClass: '' },
  { type: 'star', label: 'Star', icon: '★', iconClass: '' },
  { type: 'polygon', label: 'Polygon', icon: '⬟', iconClass: '' },
  { type: 'arrow', label: 'Arrow', icon: '➤', iconClass: '' }
]

// Computed filtered results
const filteredUploads = computed(() => {
  if (!searchQuery.value) return uploadedFiles.value
  return uploadedFiles.value.filter(file => 
    file.name.toLowerCase().includes(searchQuery.value.toLowerCase())
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

// File upload handling
const handleFileUpload = (event: Event) => {
  const target = event.target as HTMLInputElement
  const files = target.files
  
  if (!files) return
  
  for (const file of Array.from(files)) {
    const fileData = {
      id: Date.now() + Math.random(),
      name: file.name,
      type: file.type.startsWith('image/') ? 'image' : 'video',
      file,
      thumbnail: URL.createObjectURL(file),
      src: URL.createObjectURL(file)
    }
    
    uploadedFiles.value.push(fileData)
  }
  
  // Reset input
  target.value = ''
}

// Media addition
const addMedia = (mediaData: any) => {
  const imageData = {
    src: mediaData.src || mediaData.thumbnail,
    alt: mediaData.alt || mediaData.name || 'Image',
    objectFit: 'cover'
  }
  
  emit('addMedia', imageData)
}

const addShape = (shape: any) => {
  const shapeData = {
    shapeType: shape.type,
    fill: {
      type: 'solid',
      color: '#3B82F6',
      opacity: 1
    },
    stroke: '#1E40AF',
    strokeWidth: 2,
    strokeOpacity: 1,
    strokeLineCap: 'round',
    strokeLineJoin: 'round',
    cornerRadius: shape.type === 'rectangle' ? 8 : 0
  }
  
  emit('addMedia', shapeData)
}

// Initialize
onMounted(() => {
  // Load initial stock media
  searchPhotos('design')
  searchIcons('design')
})

// Watch for search changes
watch(searchQuery, (newQuery) => {
  if (activeTab.value === 'photos' && newQuery) {
    searchPhotos(newQuery)
  } else if (activeTab.value === 'icons' && newQuery) {
    searchIcons(newQuery)
  }
})

watch(activeTab, (newTab) => {
  if (newTab === 'photos' && stockPhotos.value.length === 0) {
    searchPhotos('design')
  } else if (newTab === 'icons' && stockIcons.value.length === 0) {
    searchIcons('design')
  }
})
</script>

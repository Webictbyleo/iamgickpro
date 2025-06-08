<template>
  <div class="flex flex-col h-full">
    <!-- Header -->
    <div class="p-4 border-b border-gray-200 bg-white">
      <h3 class="text-lg font-semibold text-gray-900">Stock Media</h3>
      <p class="text-sm text-gray-600 mt-1">Browse photos, icons, shapes, and videos</p>
    </div>

    <!-- Search -->
    <div class="p-4 border-b border-gray-200 bg-white">
      <div class="relative">
        <input
          v-model="searchQuery"
          type="text"
          placeholder="Search stock media..."
          class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
        />
        <svg class="absolute left-3 top-2.5 h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
        </svg>
      </div>
    </div>
    
    <!-- Sticky Media Type Tabs -->
    <div class="sticky top-0 z-10 bg-white border-b border-gray-200">
      <div class="px-4 py-3">
        <div class="flex space-x-1 bg-gray-100 rounded-lg p-1">
          <button
            v-for="tab in mediaTabs"
            :key="tab.id"
            @click="activeTab = tab.id"
            :class="[
              'flex-1 px-3 py-2 text-sm font-medium rounded-md transition-colors whitespace-nowrap text-center',
              activeTab === tab.id
                ? 'bg-white text-gray-900 shadow-sm'
                : 'text-gray-500 hover:text-gray-700'
            ]"
          >
            <component :is="tab.icon" class="w-4 h-4 mx-auto mb-1" />
            <span class="block text-xs">{{ tab.label }}</span>
          </button>
        </div>
      </div>
    </div>

    <!-- Scrollable Content Area -->
    <div class="flex-1 overflow-y-auto">
      <!-- Stock Photos -->
      <div v-if="activeTab === 'photos'" class="p-4">
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
        
        <div v-else>
          <div class="grid grid-cols-2 gap-3">
            <div
              v-for="photo in filteredPhotos"
              :key="photo.id"
              @click="addMedia(photo)"
              class="relative group cursor-pointer border border-gray-200 rounded-lg overflow-hidden hover:border-blue-500 hover:shadow-md transition-all"
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
            class="mt-4"
          >
            Load More Photos
          </BaseButton>
        </div>
      </div>

      <!-- Icons -->
      <div v-if="activeTab === 'icons'" class="p-4">
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
        
        <div v-else>
          <div class="grid grid-cols-3 gap-3">
            <div
              v-for="icon in filteredIcons"
              :key="icon.id"
              @click="addMedia(icon)"
              class="relative group cursor-pointer border border-gray-200 rounded-lg p-4 hover:border-blue-500 hover:shadow-md transition-all bg-white"
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
            class="mt-4"
          >
            Load More Icons
          </BaseButton>
        </div>
      </div>

      <!-- Shapes -->
      <div v-if="activeTab === 'shapes'" class="p-4">
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
        
        <div v-else>
          <div class="grid grid-cols-3 gap-3">
            <div
              v-for="shape in filteredShapes"
              :key="shape.id"
              @click="addMedia(shape)"
              class="relative group cursor-pointer border border-gray-200 rounded-lg p-4 hover:border-blue-500 hover:shadow-md transition-all bg-white"
            >
              <div class="aspect-square flex items-center justify-center">
                <img
                  :src="shape.thumbnail"
                  :alt="shape.alt"
                  class="w-8 h-8 object-contain"
                />
              </div>
              <p class="text-xs text-center text-gray-600 mt-2 truncate">{{ shape.alt }}</p>
              <div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-5 transition-all rounded-lg"></div>
            </div>
          </div>
          
          <BaseButton
            v-if="stockShapes.length > 0 && !isLoadingShapes"
            @click="loadMoreShapes"
            variant="outline"
            size="sm"
            full-width
            class="mt-4"
          >
            Load More Shapes
          </BaseButton>
        </div>
      </div>

      <!-- Videos -->
      <div v-if="activeTab === 'videos'" class="p-4">
        <div v-if="isLoadingVideos" class="text-center py-8">
          <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mx-auto"></div>
          <p class="mt-2 text-sm text-gray-600">Loading videos...</p>
        </div>
        
        <!-- Empty State for Videos -->
        <div v-else-if="filteredVideos.length === 0" class="text-center py-12">
          <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
          </svg>
          <h3 class="mt-2 text-sm font-medium text-gray-900">No videos found</h3>
          <p class="mt-1 text-sm text-gray-500">
            {{ searchQuery ? 'Try adjusting your search terms' : 'Search for videos to get started' }}
          </p>
        </div>
        
        <div v-else>
          <div class="grid grid-cols-2 gap-3">
            <div
              v-for="video in filteredVideos"
              :key="video.id"
              @click="addMedia(video)"
              class="relative group cursor-pointer border border-gray-200 rounded-lg overflow-hidden hover:border-blue-500 hover:shadow-md transition-all"
            >
              <div class="aspect-video bg-gray-900 flex items-center justify-center">
                <img
                  v-if="video.thumbnail"
                  :src="video.thumbnail"
                  :alt="video.alt"
                  class="w-full h-full object-cover"
                  loading="lazy"
                />
                <svg v-else class="w-8 h-8 text-white" fill="currentColor" viewBox="0 0 24 24">
                  <path d="M8 5v14l11-7z"/>
                </svg>
              </div>
              <div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-20 transition-all flex items-center justify-center">
                <svg class="w-8 h-8 text-white opacity-0 group-hover:opacity-100 transition-opacity" fill="currentColor" viewBox="0 0 24 24">
                  <path d="M8 5v14l11-7z"/>
                </svg>
              </div>
            </div>
          </div>
          
          <BaseButton
            v-if="stockVideos.length > 0 && !isLoadingVideos"
            @click="loadMoreVideos"
            variant="outline"
            size="sm"
            full-width
            class="mt-4"
          >
            Load More Videos
          </BaseButton>
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
import { 
  PhotoIcon,
  SparklesIcon,
  Square3Stack3DIcon,
  VideoCameraIcon
} from '@heroicons/vue/24/outline'

const emit = defineEmits<{
  addMedia: [mediaData: any]
}>()

// Tab management
const activeTab = ref('photos')
const searchQuery = ref('')

const mediaTabs = [
  { id: 'photos', label: 'Photos', icon: PhotoIcon },
  { id: 'icons', label: 'Icons', icon: SparklesIcon },
  { id: 'shapes', label: 'Shapes', icon: Square3Stack3DIcon },
  { id: 'videos', label: 'Videos', icon: VideoCameraIcon }
]

// Stock media
const {
  stockPhotos,
  stockIcons,
  stockShapes,
  stockVideos,
  isLoadingPhotos,
  isLoadingIcons,
  isLoadingShapes,
  isLoadingVideos,
  searchPhotos,
  searchIcons,
  searchShapes,
  searchVideos,
  loadMorePhotos,
  loadMoreIcons,
  loadMoreShapes,
  loadMoreVideos
} = useStockMedia()

// Shapes are now managed by the useStockMedia composable

// Computed filters for stock media
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

const filteredVideos = computed(() => {
  if (!searchQuery.value) return stockVideos.value
  return stockVideos.value.filter((video: StockMediaItem) => 
    video.alt.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
    video.tags?.some((tag: string) => tag.toLowerCase().includes(searchQuery.value.toLowerCase()))
  )
})

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
  // Load initial stock media
  searchPhotos('design')
  searchIcons('design') 
  searchShapes('basic')
  searchVideos('design')
})

// Watch for search changes
watch(searchQuery, (newQuery) => {
  if (activeTab.value === 'photos' && newQuery) {
    searchPhotos(newQuery)
  } else if (activeTab.value === 'icons' && newQuery) {
    searchIcons(newQuery)
  } else if (activeTab.value === 'shapes' && newQuery) {
    searchShapes(newQuery)
  } else if (activeTab.value === 'videos' && newQuery) {
    searchVideos(newQuery)
  }
})

watch(activeTab, (newTab) => {
  if (newTab === 'photos' && stockPhotos.value.length === 0) {
    searchPhotos('design')
  } else if (newTab === 'icons' && stockIcons.value.length === 0) {
    searchIcons('design')
  } else if (newTab === 'shapes' && stockShapes.value.length === 0) {
    searchShapes('basic')
  } else if (newTab === 'videos' && stockVideos.value.length === 0) {
    searchVideos('design')
  }
})
</script>

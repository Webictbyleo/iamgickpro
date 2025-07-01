<template>
  <div class="flex flex-col h-full">
    <!-- Header -->
    <div class="p-4 border-b border-gray-200 bg-white">
      <h3 class="text-lg font-semibold text-gray-900">Stock Media</h3>
      <p class="text-sm text-gray-600 mt-1">Browse photos, icons, and shapes</p>
    </div>

    <!-- Enhanced Search -->
    <div class="p-4 border-b border-gray-200 bg-white">
      <div class="space-y-3">
        <!-- Search Input -->
        <div class="relative">
          <input
            v-model="searchQuery"
            type="text"
            placeholder="Search stock media..."
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
        <div v-if="searchQuery && !isCurrentTabLoading" class="flex items-center justify-between text-xs text-gray-500">
          <span>{{ getCurrentResults().length }} results for "{{ searchQuery }}"</span>
          <span v-if="hasMoreForCurrentTab" class="text-blue-600">{{ hasMoreForCurrentTab ? '+ more available' : '' }}</span>
        </div>
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
        <!-- Enhanced Loading State -->
        <div v-if="isLoadingPhotos && filteredPhotos.length === 0" class="space-y-4">
          <div class="text-center py-8">
            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mx-auto"></div>
            <p class="mt-2 text-sm text-gray-600">Loading photos...</p>
          </div>
          <!-- Loading skeleton -->
          <div class="grid grid-cols-2 gap-3">
            <div v-for="i in 6" :key="i" class="aspect-square bg-gray-200 rounded-lg animate-pulse"></div>
          </div>
        </div>
        
        <!-- Empty State for Photos -->
        <div v-else-if="filteredPhotos.length === 0 && !isLoadingPhotos" class="text-center py-12">
          <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
          </svg>
          <h3 class="mt-2 text-sm font-medium text-gray-900">No photos found</h3>
          <p class="mt-1 text-sm text-gray-500">
            {{ searchQuery ? 'Try adjusting your search terms' : 'Search for photos to get started' }}
          </p>
          <button
            v-if="searchQuery"
            @click="clearSearch"
            class="mt-4 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors text-sm"
          >
            Clear Search
          </button>
        </div>
        
        <div v-else class="space-y-4">
          <!-- Results header -->
          <div v-if="searchQuery" class="flex items-center justify-between">
            <h4 class="text-sm font-medium text-gray-900">{{ filteredPhotos.length }} Photos</h4>
            <button
              @click="clearSearch"
              class="text-xs text-gray-500 hover:text-gray-700 transition-colors"
            >
              Clear search
            </button>
          </div>
          
          <!-- MediaMasonry for Photos Grid -->
          <MediaMasonry
            :media-items="formattedPhotos"
            :show-selection="false"
            :actions="photoActions"
            :columns="2"
            @media-click="addMedia"
            @action="handleMediaAction"
          />
          
          <!-- Enhanced Load More Button -->
          <div v-if="hasMorePhotos && !isLoadingPhotos" class="pt-2">
            <button
              @click="loadMorePhotos"
              class="w-full py-3 px-4 bg-gray-50 hover:bg-gray-100 border border-gray-200 hover:border-gray-300 rounded-lg transition-all duration-200 text-sm font-medium text-gray-700 hover:text-gray-900 flex items-center justify-center space-x-2"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
              </svg>
              <span>Load More Photos</span>
            </button>
          </div>
          
          <!-- Loading more indicator -->
          <div v-if="isLoadingPhotos && filteredPhotos.length > 0" class="text-center py-4">
            <div class="inline-flex items-center space-x-2">
              <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-blue-600"></div>
              <span class="text-sm text-gray-600">Loading more photos...</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Icons -->
      <div v-if="activeTab === 'icons'" class="p-4">
        <!-- Enhanced Loading State -->
        <div v-if="isLoadingIcons && filteredIcons.length === 0" class="space-y-4">
          <div class="text-center py-8">
            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mx-auto"></div>
            <p class="mt-2 text-sm text-gray-600">Loading icons...</p>
          </div>
          <!-- Loading skeleton -->
          <div class="grid grid-cols-4 gap-3">
            <div v-for="i in 8" :key="i" class="aspect-square bg-gray-200 rounded-lg animate-pulse"></div>
          </div>
        </div>
        
        <!-- Empty State for Icons -->
        <div v-else-if="filteredIcons.length === 0 && !isLoadingIcons" class="text-center py-12">
          <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zM21 5a2 2 0 00-2-2h-4a2 2 0 00-2 2v12a4 4 0 004 4 4 4 0 004-4V5z" />
          </svg>
          <h3 class="mt-2 text-sm font-medium text-gray-900">No icons found</h3>
          <p class="mt-1 text-sm text-gray-500">
            {{ searchQuery ? 'Try adjusting your search terms' : 'Search for icons to get started' }}
          </p>
          <button
            v-if="searchQuery"
            @click="clearSearch"
            class="mt-4 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors text-sm"
          >
            Clear Search
          </button>
        </div>
        
        <div v-else class="space-y-4">
          <!-- Results header -->
          <div v-if="searchQuery" class="flex items-center justify-between">
            <h4 class="text-sm font-medium text-gray-900">{{ filteredIcons.length }} Icons</h4>
            <button
              @click="clearSearch"
              class="text-xs text-gray-500 hover:text-gray-700 transition-colors"
            >
              Clear search
            </button>
          </div>
          
          <!-- Enhanced Grid -->
          <div class="grid grid-cols-4 gap-3">
            <div
              v-for="icon in filteredIcons"
              :key="icon.id"
              @click="addMedia(icon)"
              @mouseenter="showTooltip($event, icon.alt)"
              @mouseleave="hideTooltip"
              class="relative group cursor-pointer border border-gray-200 rounded-lg p-3 hover:border-blue-500 hover:shadow-lg transition-all duration-200 bg-white transform hover:scale-105"
            >
              <div class="aspect-square flex items-center justify-center">
                <img
                  :src="icon.thumbnail || icon.src"
                  :alt="icon.alt"
                  class="w-8 h-8 object-contain filter group-hover:brightness-110 transition-all"
                  @error="handleImageError"
                />
              </div>
              <div class="absolute inset-0 bg-blue-50 opacity-0 group-hover:opacity-50 transition-all rounded-lg"></div>
            </div>
          </div>
          
          <!-- Enhanced Load More Button -->
          <div v-if="hasMoreIcons && !isLoadingIcons" class="pt-2">
            <button
              @click="loadMoreIcons"
              class="w-full py-3 px-4 bg-gray-50 hover:bg-gray-100 border border-gray-200 hover:border-gray-300 rounded-lg transition-all duration-200 text-sm font-medium text-gray-700 hover:text-gray-900 flex items-center justify-center space-x-2"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
              </svg>
              <span>Load More Icons</span>
            </button>
          </div>
          
          <!-- Loading more indicator -->
          <div v-if="isLoadingIcons && filteredIcons.length > 0" class="text-center py-4">
            <div class="inline-flex items-center space-x-2">
              <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-blue-600"></div>
              <span class="text-sm text-gray-600">Loading more icons...</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Shapes -->
      <div v-if="activeTab === 'shapes'" class="p-4">
        <!-- Enhanced Loading State -->
        <div v-if="isLoadingShapes && filteredShapes.length === 0" class="space-y-4">
          <div class="text-center py-8">
            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mx-auto"></div>
            <p class="mt-2 text-sm text-gray-600">Loading shapes...</p>
          </div>
          <!-- Loading skeleton -->
          <div class="grid grid-cols-4 gap-3">
            <div v-for="i in 8" :key="i" class="aspect-square bg-gray-200 rounded-lg animate-pulse"></div>
          </div>
        </div>
        
        <!-- Empty State for Shapes -->
        <div v-else-if="filteredShapes.length === 0 && !isLoadingShapes" class="text-center py-12">
          <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" />
          </svg>
          <h3 class="mt-2 text-sm font-medium text-gray-900">No shapes found</h3>
          <p class="mt-1 text-sm text-gray-500">
            {{ searchQuery ? 'Try adjusting your search terms' : 'Search for shapes to get started' }}
          </p>
          <button
            v-if="searchQuery"
            @click="clearSearch"
            class="mt-4 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors text-sm"
          >
            Clear Search
          </button>
        </div>
        
        <div v-else class="space-y-4">
          <!-- Results header -->
          <div v-if="searchQuery" class="flex items-center justify-between">
            <h4 class="text-sm font-medium text-gray-900">{{ filteredShapes.length }} Shapes</h4>
            <button
              @click="clearSearch"
              class="text-xs text-gray-500 hover:text-gray-700 transition-colors"
            >
              Clear search
            </button>
          </div>
          
          <!-- Enhanced Grid -->
          <div class="grid grid-cols-4 gap-3">
            <div
              v-for="shape in filteredShapes"
              :key="shape.id"
              @click="addMedia(shape)"
              class="relative group cursor-pointer border border-gray-200 rounded-lg p-3 hover:border-blue-500 hover:shadow-lg transition-all duration-200 bg-white transform hover:scale-105"
            >
              <div class="aspect-square flex items-center justify-center">
                <img
                  :src="shape.thumbnail || shape.src"
                  :alt="shape.alt"
                  class="w-8 h-8 object-contain filter group-hover:brightness-110 transition-all"
                  @error="handleImageError"
                />
              </div>
              <div class="absolute inset-0 bg-blue-50 opacity-0 group-hover:opacity-50 transition-all rounded-lg"></div>
              <!-- Shape name -->
              <p class="text-xs text-center text-gray-600 mt-1 truncate group-hover:text-gray-900 transition-colors">{{ shape.alt }}</p>
            </div>
          </div>
          
          <!-- Enhanced Load More Button -->
          <div v-if="hasMoreShapes && !isLoadingShapes" class="pt-2">
            <button
              @click="loadMoreShapes"
              class="w-full py-3 px-4 bg-gray-50 hover:bg-gray-100 border border-gray-200 hover:border-gray-300 rounded-lg transition-all duration-200 text-sm font-medium text-gray-700 hover:text-gray-900 flex items-center justify-center space-x-2"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
              </svg>
              <span>Load More Shapes</span>
            </button>
          </div>
          
          <!-- Loading more indicator -->
          <div v-if="isLoadingShapes && filteredShapes.length > 0" class="text-center py-4">
            <div class="inline-flex items-center space-x-2">
              <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-blue-600"></div>
              <span class="text-sm text-gray-600">Loading more shapes...</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Videos -->
    </div>
    
    <!-- Global Tooltip -->
    <div
      v-if="tooltipVisible"
      class="fixed bg-gray-900 text-white text-xs px-2 py-1 rounded whitespace-nowrap z-[9999] pointer-events-none shadow-lg"
      :style="{
        left: tooltipPosition.x + 'px',
        top: tooltipPosition.y + 'px',
        transform: 'translateX(-50%)'
      }"
    >
      {{ tooltipText }}
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue'
import BaseButton from '@/components/common/BaseButton.vue'
import MediaMasonry from '@/components/common/MediaMasonry.vue'
import { useStockMedia, type StockMediaItem } from '../../../composables/useStockMedia'
import type { MediaItem } from '@/types'
import { GeometryUtils } from '@/utils/GeometryUtils'
import { 
  PhotoIcon,
  SparklesIcon,
  Square3Stack3DIcon,
  PlusIcon
} from '@heroicons/vue/24/outline'

const props = defineProps<{
}>()

const emit = defineEmits<{
  addMedia: [mediaData: any]
}>()

// Tab management
const activeTab = ref('photos')
const searchQuery = ref('')
const searchTimeout = ref<NodeJS.Timeout | null>(null)

// Tooltip state
const tooltipVisible = ref(false)
const tooltipText = ref('')
const tooltipPosition = ref({ x: 0, y: 0 })

const mediaTabs = [
  { id: 'photos', label: 'Photos', icon: PhotoIcon },
  { id: 'icons', label: 'Icons', icon: SparklesIcon },
  { id: 'shapes', label: 'Shapes', icon: Square3Stack3DIcon }
]

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
  loadMoreShapes,
  hasMorePhotos,
  hasMoreIcons,
  hasMoreShapes
} = useStockMedia()

// Computed properties for loading states and results
const isCurrentTabLoading = computed(() => {
  switch (activeTab.value) {
    case 'photos': return isLoadingPhotos.value
    case 'icons': return isLoadingIcons.value
    case 'shapes': return isLoadingShapes.value
    default: return false
  }
})

const hasMoreForCurrentTab = computed(() => {
  switch (activeTab.value) {
    case 'photos': return hasMorePhotos.value
    case 'icons': return hasMoreIcons.value
    case 'shapes': return hasMoreShapes.value
    default: return false
  }
})

const getCurrentResults = () => {
  switch (activeTab.value) {
    case 'photos': return filteredPhotos.value
    case 'icons': return filteredIcons.value
    case 'shapes': return filteredShapes.value
    default: return []
  }
}

// Shapes are now managed by the useStockMedia composable

// Stock media results (no additional filtering needed since API handles search)
const filteredPhotos = computed(() => stockPhotos.value)
const filteredIcons = computed(() => stockIcons.value)
const filteredShapes = computed(() => stockShapes.value)

// Formatted photos for MediaMasonry component
const formattedPhotos = computed(() => {
  return filteredPhotos.value.map(photo => ({
    id: photo.id,
    uuid: photo.id, // Use id as uuid for stock photos
    name: photo.alt || 'Untitled Photo',
    type: 'image' as const,
    mimeType: 'image/jpeg',
    size: 0, // Stock photos don't have size info
    url:   photo.src || photo.thumbnail,
    thumbnailUrl: photo.thumbnail || photo.src,
    width: photo.width, // Default dimensions for stock photos
    height: photo.height,
    source: 'stock' as const,
    sourceId: photo.id,
    metadata: photo,
    tags: [], // Stock photos don't have tags in our current format
    isPremium: false,
    isActive: true,
    createdAt: new Date().toISOString(),
    updatedAt: new Date().toISOString()
  }))
})

// Actions for stock photos
const photoActions = computed(() => [
  {
    key: 'add',
    label: 'Add to Design',
    icon: PlusIcon
  }
])

// Media addition
const addMedia = (mediaData: any) => {
  // Check if this is a MediaItem (from MediaMasonry) or raw stock media data
  const isMediaItem = mediaData.uuid && mediaData.source
  
  // Function to calculate appropriate dimensions for the design stage using GeometryUtils
  const calculateDimensions = (originalWidth: number, originalHeight: number) => {
    const sourceDimensions = {
      width: originalWidth || 400,
      height: originalHeight || 400
    }
    
    // Define target constraints for design stage
    const targetDimensions = {
      width: 600,  // Maximum width for design stage
      height: 400  // Maximum height for design stage
    }
    
    // Use GeometryUtils to resize with contain mode (maintains aspect ratio, fits within bounds)
    const resizeResult = GeometryUtils.resize(sourceDimensions, targetDimensions, {
      mode: 'contain',
      minWidth: 100,  // Minimum width to ensure visibility
      minHeight: 100, // Minimum height to ensure visibility
      allowUpscaling: true // Allow small images to be scaled up
    })
    
    return {
      width: Math.round(resizeResult.width),
      height: Math.round(resizeResult.height)
    }
  }
  
  if (isMediaItem) {
    // Handle MediaItem from MediaMasonry
    const dimensions = calculateDimensions(mediaData.width, mediaData.height)
    const imageData = {
      src: mediaData.src || mediaData.url || mediaData.thumbnail,
      alt: mediaData.alt || mediaData.name
    }
    emit('addMedia', { 
      type: 'image', 
      data: imageData,
      transform: dimensions
    })
  } else {
    // Handle shapes from stock media as SVG layers
    if (activeTab.value === 'shapes') {
      const svgData = {
        src: mediaData.url || mediaData.src || mediaData.thumbnail,
        name: mediaData.alt || mediaData.name || 'Shape',
        viewBox: '0 0 100 100', // Default viewBox, will be updated when SVG is loaded
        preserveAspectRatio: 'xMidYMid meet',
        fillColors: {},
        strokeColors: {},
        strokeWidths: {},
        originalWidth: 100, // Default size, will be updated when SVG is loaded
        originalHeight: 100
      }
      emit('addMedia', { type: 'svg', data: svgData })
    } else {
      // Handle images and other media (both uploaded and stock)
      const dimensions = calculateDimensions(mediaData.width, mediaData.height)
      const imageData = {
        src: mediaData.src || mediaData.url || mediaData.thumbnail || mediaData.thumbnailUrl,
        alt: mediaData.alt || mediaData.name || 'Image'
      }
      emit('addMedia', { 
        type: 'image', 
        data: imageData,
        transform: dimensions
      })
    }
  }
}

// Handle MediaMasonry actions
const handleMediaAction = (action: string, file: any) => {
  if (action === 'add') {
    addMedia(file)
  }
}

// Enhanced search functionality
const handleSearch = () => {
  if (!searchQuery.value.trim()) return
  
  const query = searchQuery.value.trim()
  
  switch (activeTab.value) {
    case 'photos':
      searchPhotos(query)
      break
    case 'icons':
      searchIcons(query)
      break
    case 'shapes':
      searchShapes(query)
      break
  }
}

const clearSearch = () => {
  searchQuery.value = ''
  // Reload default content for current tab
  switch (activeTab.value) {
    case 'photos':
      searchPhotos('design')
      break
    case 'icons':
      searchIcons('design')
      break
    case 'shapes':
      searchShapes('basic')
      break
  }
}

const handleImageError = (event: Event) => {
  const target = event.target as HTMLImageElement
  target.style.display = 'none'
  const parent = target.parentElement
  if (parent) {
    parent.innerHTML = `
      <div class="w-full h-full flex items-center justify-center bg-gray-100">
        <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
        </svg>
      </div>
    `
  }
}

// Initialize
onMounted(() => {
  // Load initial stock media
  searchPhotos('design')
  searchIcons('design') 
  searchShapes('basic')
})

// Enhanced search with debouncing
watch(searchQuery, (newQuery) => {
  // Clear existing timeout
  if (searchTimeout.value) {
    clearTimeout(searchTimeout.value)
  }
  
  // Set new timeout for debounced search
  searchTimeout.value = setTimeout(() => {
    if (newQuery.trim()) {
      handleSearch()
    }
  }, 500) // 500ms debounce
})

// Tab switching with smart loading
watch(activeTab, (newTab) => {
  // Only load if no search query and no existing data
  const shouldLoad = !searchQuery.value.trim()
  
  if (newTab === 'photos' && shouldLoad && stockPhotos.value.length === 0) {
    searchPhotos('design')
  } else if (newTab === 'icons' && shouldLoad && stockIcons.value.length === 0) {
    searchIcons('design')
  } else if (newTab === 'shapes' && shouldLoad && stockShapes.value.length === 0) {
    searchShapes('basic')
  }
})

// Tooltip functionality
const showTooltip = (event: MouseEvent, text: string) => {
  const rect = (event.target as HTMLElement).getBoundingClientRect()
  tooltipText.value = text
  tooltipPosition.value = {
    x: rect.left + rect.width / 2,
    y: rect.bottom + 8
  }
  tooltipVisible.value = true
}

const hideTooltip = () => {
  tooltipVisible.value = false
}
</script>

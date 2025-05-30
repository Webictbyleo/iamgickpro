<template>
  <AppLayout>
    <div class="space-y-6">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">Stock Media</h1>
          <p class="text-gray-600 mt-1">Browse millions of high-quality images, videos, and graphics</p>
        </div>
        
        <!-- Upload Button -->
        <div class="flex items-center space-x-4">
          <button
            @click="triggerFileUpload"
            class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
          >
            <ArrowUpTrayIcon class="h-4 w-4 mr-2" />
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

      <!-- Search and Filters -->
      <div class="bg-white p-6 rounded-lg shadow">
        <div class="flex items-center space-x-4">
          <div class="flex-1">
            <div class="relative">
              <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <MagnifyingGlassIcon class="h-5 w-5 text-gray-400" />
              </div>
              <input
                v-model="searchQuery"
                type="text"
                placeholder="Search for images, videos, graphics..."
                class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md leading-5 bg-white placeholder-gray-500 focus:outline-none focus:placeholder-gray-400 focus:ring-1 focus:ring-primary-500 focus:border-primary-500"
                @keyup.enter="searchMedia"
              />
            </div>
          </div>
          
          <select
            v-model="selectedType"
            class="block px-3 py-2 border border-gray-300 rounded-md bg-white focus:outline-none focus:ring-1 focus:ring-primary-500 focus:border-primary-500"
            @change="searchMedia"
          >
            <option value="">All Types</option>
            <option value="image">Images</option>
            <option value="video">Videos</option>
            <option value="vector">Vectors</option>
          </select>
          
          <button
            @click="searchMedia"
            class="inline-flex items-center px-4 py-2 bg-primary-600 text-white rounded-md hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
          >
            Search
          </button>
        </div>
      </div>

      <!-- Tabs -->
      <div class="border-b border-gray-200">
        <nav class="-mb-px flex space-x-8">
          <button
            v-for="tab in tabs"
            :key="tab.id"
            @click="activeTab = tab.id"
            :class="[
              activeTab === tab.id
                ? 'border-primary-500 text-primary-600'
                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
              'whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm'
            ]"
          >
            {{ tab.name }}
          </button>
        </nav>
      </div>

      <!-- Loading State -->
      <div v-if="isLoading" class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
        <div
          v-for="i in 12"
          :key="i"
          class="aspect-square bg-gray-200 rounded-lg animate-pulse"
        />
      </div>

      <!-- Media Grid -->
      <div v-else-if="mediaItems.length > 0" class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
        <div
          v-for="item in mediaItems"
          :key="item.id"
          class="group relative aspect-square bg-gray-100 rounded-lg overflow-hidden cursor-pointer hover:shadow-lg transition-shadow"
          @click="selectMedia(item)"
        >
          <img
            :src="item.thumbnail || item.url"
            :alt="item.name || 'Media item'"
            class="w-full h-full object-cover"
          />
          
          <div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-30 transition-all">
            <div class="absolute inset-0 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
              <button class="bg-white text-gray-900 px-3 py-1 rounded text-sm font-medium">
                Select
              </button>
            </div>
          </div>
          
          <div class="absolute top-2 right-2">
            <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-black bg-opacity-50 text-white">
              {{ item.type }}
            </span>
          </div>
        </div>
      </div>

      <!-- Empty State -->
      <div v-else class="text-center py-12">
        <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
          <PhotoIcon class="w-8 h-8 text-gray-400" />
        </div>
        <h3 class="text-lg font-medium text-gray-900 mb-2">
          {{ activeTab === 'uploads' ? 'No uploads yet' : 'No media found' }}
        </h3>
        <p class="text-gray-600 mb-4">
          {{ activeTab === 'uploads' 
            ? 'Upload your first media file to get started' 
            : 'Try searching for something else or upload your own media' 
          }}
        </p>
        <button
          v-if="activeTab === 'uploads'"
          @click="triggerFileUpload"
          class="inline-flex items-center px-4 py-2 bg-primary-600 text-white rounded-md hover:bg-primary-700"
        >
          <ArrowUpTrayIcon class="h-4 w-4 mr-2" />
          Upload Media
        </button>
      </div>

      <!-- Pagination -->
      <div v-if="totalPages > 1" class="flex items-center justify-center space-x-2">
        <button
          v-for="page in totalPages"
          :key="page"
          @click="goToPage(page)"
          :class="[
            page === currentPage
              ? 'bg-primary-600 text-white'
              : 'bg-white text-gray-700 hover:bg-gray-50',
            'px-3 py-2 border border-gray-300 rounded-md text-sm font-medium'
          ]"
        >
          {{ page }}
        </button>
      </div>
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import { ref, onMounted, watch } from 'vue'
import { 
  MagnifyingGlassIcon, 
  ArrowUpTrayIcon, 
  PhotoIcon 
} from '@heroicons/vue/24/outline'
import AppLayout from '@/components/layout/AppLayout.vue'
import { mediaAPI } from '@/services/api'
import type { MediaItem } from '@/types'

const searchQuery = ref('')
const selectedType = ref('')
const activeTab = ref('stock')
const isLoading = ref(false)
const mediaItems = ref<MediaItem[]>([])
const currentPage = ref(1)
const totalPages = ref(1)
const fileInput = ref<HTMLInputElement>()

const tabs = [
  { id: 'stock', name: 'Stock Media' },
  { id: 'uploads', name: 'My Uploads' },
]

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
      page: currentPage.value,
      per_page: 24
    })
    
    if (response.data) {
      mediaItems.value = response.data.data
      totalPages.value = response.data.meta.last_page
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
      per_page: 24
    })
    
    if (response.data) {
      mediaItems.value = response.data.data
      totalPages.value = response.data.meta.last_page
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
    
    // Refresh uploads if on uploads tab
    if (activeTab.value === 'uploads') {
      await loadUserUploads()
    }
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
  }
}

const goToPage = (page: number) => {
  currentPage.value = page
  searchMedia()
}

// Watch for tab changes
watch(activeTab, () => {
  currentPage.value = 1
  searchMedia()
})

// Initialize with stock media
onMounted(() => {
  searchMedia()
})
</script>

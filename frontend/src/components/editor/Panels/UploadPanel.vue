<template>
  <div class="flex flex-col h-full relative bg-secondary-50 dark:bg-secondary-800">
    <!-- Header -->
    <div class="p-4 border-b border-secondary-200 dark:border-secondary-700 bg-white dark:bg-secondary-900">
      <div class="flex items-center justify-between">
        <div>
          <h3 class="text-lg font-semibold text-secondary-900 dark:text-secondary-100">Your Uploads</h3>
          <p class="text-sm text-secondary-600 dark:text-secondary-400 mt-1">Manage and use your uploaded image files</p>
        </div>
      </div>
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
    <div v-if="userMedia.length > 0" class="p-4 border-b border-secondary-200 dark:border-secondary-700 bg-white dark:bg-secondary-900">
      <div class="space-y-3">
        <!-- Enhanced Search Input -->
        <div class="relative">
          <input
            v-model="searchQuery"
            type="text"
            placeholder="Search your uploads..."
            class="w-full pl-10 pr-10 py-3 border border-secondary-200 dark:border-secondary-700 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-all duration-200 bg-white dark:bg-secondary-700 text-secondary-900 dark:text-secondary-100 placeholder-secondary-500 dark:placeholder-secondary-400"
            @keydown.enter="handleSearch"
          />
          <svg class="absolute left-3 top-3.5 h-5 w-5 text-secondary-400 dark:text-secondary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
          </svg>
          <!-- Clear search button -->
          <button
            v-if="searchQuery"
            @click="clearSearch"
            class="absolute right-3 top-3.5 h-5 w-5 text-secondary-400 hover:text-secondary-600 dark:text-secondary-500 dark:hover:text-secondary-300 transition-colors"
          >
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <!-- Search Stats -->
        <div v-if="searchQuery && !isLoadingUserMedia" class="flex items-center justify-between text-xs text-secondary-500 dark:text-secondary-400">
          <span>{{ filteredUploads.length }} results for "{{ searchQuery }}"</span>
          <span v-if="hasMoreUserMedia" class="text-primary-600 dark:text-primary-400">+ more available</span>
        </div>

        <!-- Enhanced Upload Button -->
        <button
          @click="fileInput?.click()"
          class="group w-full flex items-center justify-center gap-3 px-4 py-4 border-2 border-dashed border-primary-200 dark:border-primary-700 rounded-xl hover:border-primary-400 dark:hover:border-primary-500 hover:bg-primary-50 dark:hover:bg-secondary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 dark:focus:ring-primary-400 focus:ring-offset-2 dark:focus:ring-offset-secondary-800 active:scale-95 transition-all duration-200 text-sm font-medium text-secondary-700 dark:text-secondary-300 group-hover:text-primary-700 dark:group-hover:text-primary-300"
        >
          <div class="w-8 h-8 bg-primary-100 dark:bg-secondary-700 rounded-lg flex items-center justify-center group-hover:bg-primary-200 dark:group-hover:bg-secondary-600 group-hover:scale-110 transition-all duration-200">
            <svg class="w-4 h-4 text-primary-600 dark:text-primary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
            </svg>
          </div>
          <span>Upload More Images</span>
        </button>
      </div>
    </div>



    <!-- Scrollable Content Area -->
    <div class="flex-1 overflow-y-auto" :class="{ 'pb-20': selectedItems.size > 0 }">
      <!-- Enhanced Loading State -->
      <div v-if="isLoadingUserMedia && userMedia.length === 0" class="space-y-4">
        <div class="text-center py-8">
          <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary-600 dark:border-primary-400 mx-auto"></div>
          <p class="mt-2 text-sm text-secondary-600 dark:text-secondary-300">Loading your uploads...</p>
        </div>
        <!-- Loading skeleton -->
        <div class="px-4">
          <div class="grid grid-cols-2 gap-3">
            <div v-for="i in 6" :key="i" class="aspect-square bg-secondary-200 dark:bg-secondary-700 rounded-lg animate-pulse"></div>
          </div>
        </div>
      </div>
      
      <!-- Enhanced Empty State -->
      <div v-else-if="filteredUploads.length === 0 && !isLoadingUserMedia" class="text-center py-12">
        <svg class="mx-auto h-12 w-12 text-secondary-400 dark:text-secondary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
        </svg>
        <h3 class="mt-2 text-sm font-medium text-secondary-900 dark:text-secondary-100">
          {{ searchQuery ? 'No uploads found' : 'No uploads yet' }}
        </h3>
        <p class="mt-1 text-sm text-secondary-500 dark:text-secondary-400">
          {{ searchQuery 
            ? 'Try adjusting your search terms' 
            : 'Upload your first image files to start building your design library'
          }}
        </p>
        
        <!-- Action buttons -->
        <div class="mt-4 space-y-2">
          <button
            v-if="!searchQuery && userMedia.length === 0"
            @click="fileInput?.click()"
            class="group mx-auto flex flex-col items-center gap-3 px-8 py-6 border-2 border-dashed border-primary-300 dark:border-primary-600 rounded-2xl hover:border-primary-500 dark:hover:border-primary-400 hover:bg-primary-50 dark:hover:bg-secondary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 dark:focus:ring-primary-400 focus:ring-offset-2 dark:focus:ring-offset-secondary-800 active:scale-95 transition-all duration-200"
          >
            <div class="w-12 h-12 bg-primary-100 dark:bg-secondary-700 rounded-xl flex items-center justify-center group-hover:bg-primary-200 dark:group-hover:bg-secondary-600 group-hover:scale-110 transition-all duration-200">
              <svg class="w-6 h-6 text-primary-600 dark:text-primary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
              </svg>
            </div>
            <div class="text-center">
              <span class="block text-base font-semibold text-secondary-900 dark:text-secondary-100 group-hover:text-primary-700 dark:group-hover:text-primary-300">Upload Your First Image</span>
              <span class="block text-sm text-secondary-500 dark:text-secondary-400 mt-1">Choose files or drag and drop</span>
            </div>
          </button>
          <button
            v-if="searchQuery"
            @click="clearSearch"
            class="px-4 py-2 bg-secondary-100 dark:bg-secondary-700 text-secondary-700 dark:text-secondary-300 rounded-lg hover:bg-secondary-200 dark:hover:bg-secondary-600 hover:text-secondary-900 dark:hover:text-secondary-100 focus:outline-none focus:ring-2 focus:ring-secondary-500 dark:focus:ring-secondary-400 focus:ring-offset-2 dark:focus:ring-offset-secondary-800 transition-all duration-200 text-sm font-medium"
          >
            Clear Search
          </button>
          <p v-if="userMedia.length === 0" class="text-xs text-secondary-400 dark:text-secondary-500">
            Drag and drop works too!
          </p>
        </div>
      </div>
      
      <!-- Enhanced Upload Grid -->
      <div v-else class="p-4 space-y-4">
        <!-- Results header -->
        <div v-if="searchQuery" class="flex items-center justify-between">
          <h4 class="text-sm font-medium text-secondary-900 dark:text-secondary-100">{{ filteredUploads.length }} images</h4>
          <button
            @click="clearSearch"
            class="text-xs text-secondary-500 hover:text-secondary-700 dark:text-secondary-400 dark:hover:text-secondary-200 transition-colors"
          >
            Clear search
          </button>
        </div>

        <!-- Media Masonry Grid -->
        <MediaMasonry
          :media-items="filteredUploads"
          :selected-items="selectedItems"
          :actions="mediaActions"
          :columns="2"
          :gap="0"
          :show-info-overlay="false"
          @media-click="addMedia"
          @toggle-selection="toggleSelection"
          @action="handleMediaAction"
        />
        
        <!-- Enhanced Load More Button -->
        <div v-if="hasMoreUserMedia && !isLoadingUserMedia" class="pt-2">
          <button
            @click="loadMoreUserMedia"
            class="w-full py-3 px-4 bg-gray-50 hover:bg-gray-100 dark:bg-gray-700 dark:hover:bg-gray-600 border border-gray-200 hover:border-gray-300 dark:border-gray-600 dark:hover:border-gray-500 rounded-lg transition-all duration-200 text-sm font-medium text-gray-700 hover:text-gray-900 dark:text-gray-300 dark:hover:text-gray-100 flex items-center justify-center space-x-2"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            <span>Load More Images</span>
          </button>
        </div>
        
        <!-- Loading more indicator -->
        <div v-if="isLoadingUserMedia && userMedia.length > 0" class="text-center py-4">
          <div class="inline-flex items-center space-x-2">
            <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-primary-600 dark:border-primary-400"></div>
            <span class="text-sm text-gray-600 dark:text-gray-300">Loading more images...</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div v-if="showDeleteConfirmation" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
      <div class="bg-white dark:bg-gray-800 rounded-lg p-6 max-w-md w-full mx-4 shadow-xl">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-2">Delete Media</h3>
        <p class="text-gray-600 dark:text-gray-300 mb-4">
          Are you sure you want to delete "{{ itemToDelete?.name }}"? This action cannot be undone.
        </p>
        <div class="flex justify-end gap-3">
          <button
            @click="cancelDelete"
            :disabled="isDeleting"
            class="px-4 py-2 text-gray-700 hover:text-gray-900 dark:text-gray-300 dark:hover:text-gray-100 transition-colors"
          >
            Cancel
          </button>
          <button
            @click="deleteSingleItem"
            :disabled="isDeleting"
            class="px-4 py-2 bg-danger-600 text-white rounded-lg hover:bg-danger-700 transition-colors disabled:opacity-50"
          >
            {{ isDeleting ? 'Deleting...' : 'Delete' }}
          </button>
        </div>
      </div>
    </div>

    <!-- Bulk Delete Confirmation Modal -->
    <div v-if="showBulkDeleteConfirmation" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
      <div class="bg-white dark:bg-gray-800 rounded-lg p-6 max-w-md w-full mx-4 shadow-xl">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-2">Delete Selected Media</h3>
        <p class="text-gray-600 dark:text-gray-300 mb-4">
          Are you sure you want to delete {{ selectedItems.size }} selected media items? This action cannot be undone.
        </p>
        <div class="flex justify-end gap-3">
          <button
            @click="cancelDelete"
            :disabled="isDeleting"
            class="px-4 py-2 text-gray-700 hover:text-gray-900 dark:text-gray-300 dark:hover:text-gray-100 transition-colors"
          >
            Cancel
          </button>
          <button
            @click="deleteSelectedItems"
            :disabled="isDeleting"
            class="px-4 py-2 bg-danger-600 text-white rounded-lg hover:bg-danger-700 transition-colors disabled:opacity-50"
          >
            {{ isDeleting ? 'Deleting...' : 'Delete Selected' }}
          </button>
        </div>
      </div>
    </div>

    <!-- Bottom Sheet for Selected Items -->
    <div 
      v-if="selectedItems.size > 0"
      class="absolute bottom-0 left-0 right-0 bg-white dark:bg-gray-800 border-t border-gray-200 dark:border-gray-600 shadow-lg transform transition-transform duration-300 ease-out"
      :class="selectedItems.size > 0 ? 'translate-y-0' : 'translate-y-full'"
    >
      <div class="p-3">
        <div class="flex items-center justify-between mb-3">
          <div class="flex items-center gap-2">
            <div class="w-6 h-6 bg-primary-100 dark:bg-primary-900/30 rounded-full flex items-center justify-center">
              <span class="text-xs font-semibold text-primary-600 dark:text-primary-400">{{ selectedItems.size }}</span>
            </div>
            <p class="text-sm font-medium text-gray-900 dark:text-gray-100">
              {{ selectedItems.size === 1 ? '1 selected' : `${selectedItems.size} selected` }}
            </p>
          </div>
          <button
            @click="clearSelection"
            class="p-1 text-gray-400 hover:text-gray-600 dark:text-gray-500 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded transition-colors"
            title="Clear selection"
          >
            <XMarkIcon class="w-4 h-4" />
          </button>
        </div>
        
        <div class="flex gap-2">
          <button
            @click="confirmBulkDelete"
            class="flex-1 flex items-center justify-center gap-1.5 px-3 py-2 bg-danger-600 text-white rounded-lg hover:bg-danger-700 transition-colors text-sm font-medium"
          >
            <TrashIcon class="w-3.5 h-3.5" />
            Delete
          </button>
          <button
            @click="clearSelection"
            class="px-3 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors text-sm font-medium"
          >
            Cancel
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch, onUnmounted } from 'vue'
import { TrashIcon, ArrowDownTrayIcon, XMarkIcon } from '@heroicons/vue/24/outline'
import { useUserMedia } from '@/composables/useUserMedia'
import { mediaAPI } from '@/services/api'
import type { MediaItem } from '@/types'
import MediaMasonry from '@/components/common/MediaMasonry.vue'
import { GeometryUtils } from '@/utils/GeometryUtils'

const emit = defineEmits<{
  addMedia: [mediaData: any]
}>()

// File input
const fileInput = ref<HTMLInputElement>()

// Search and filter
const searchQuery = ref('')
const searchTimeout = ref<NodeJS.Timeout | null>(null)
const isDragOver = ref(false)

// Selection management
const selectedItems = ref<Set<string>>(new Set())

// Delete confirmation
const showDeleteConfirmation = ref(false)
const showBulkDeleteConfirmation = ref(false)
const itemToDelete = ref<MediaItem | null>(null)
const isDeleting = ref(false)

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

  // Only show images (since we only accept images now)
  filtered = filtered.filter(file => file.type === 'image')

  return filtered
})

const clearSearch = () => {
  searchQuery.value = ''
}

// Media actions for the masonry component
const mediaActions = computed(() => [
  {
    key: 'download',
    label: 'Download',
    icon: ArrowDownTrayIcon
  },
  {
    key: 'delete',
    label: 'Delete',
    icon: TrashIcon
  }
])

// Handle media actions from the masonry component
const handleMediaAction = (action: string, file: MediaItem) => {
  switch (action) {
    case 'download':
      downloadMedia(file)
      break
    case 'delete':
      confirmDeleteSingle(file)
      break
  }
}

// Selection management
const toggleSelection = (itemId: string) => {
  if (selectedItems.value.has(itemId)) {
    selectedItems.value.delete(itemId)
  } else {
    selectedItems.value.add(itemId)
  }
}

const clearSelection = () => {
  selectedItems.value.clear()
}

// Delete functionality
const confirmDeleteSingle = (media: MediaItem) => {
  itemToDelete.value = media
  showDeleteConfirmation.value = true
}

const confirmBulkDelete = () => {
  showBulkDeleteConfirmation.value = true
}

const deleteSelectedItems = async () => {
  const selectedIds = Array.from(selectedItems.value)
  if (selectedIds.length === 0) return
  
  try {
    isDeleting.value = true
    
    // Get the UUIDs for the selected items
    const selectedUuids = selectedIds.map(id => {
      const media = userMedia.value.find(item => item.id === id)
      return media?.uuid || media?.id // Fallback to id if uuid not available
    }).filter((uuid): uuid is string => Boolean(uuid))
    
    await mediaAPI.bulkDeleteMedia({ uuids: selectedUuids })
    
    // Remove from local store
    selectedIds.forEach(id => {
      const index = userMedia.value.findIndex(item => item.id === id)
      if (index !== -1) {
        userMedia.value.splice(index, 1)
      }
    })
    
    clearSelection()
    showBulkDeleteConfirmation.value = false
  } catch (error) {
    console.error('Failed to delete media items:', error)
  } finally {
    isDeleting.value = false
  }
}

const deleteSingleItem = async () => {
  if (!itemToDelete.value) return
  
  try {
    isDeleting.value = true
    const uuid = itemToDelete.value.uuid || itemToDelete.value.id
    await mediaAPI.deleteMedia(uuid)
    
    // Remove from local store
    const index = userMedia.value.findIndex(item => item.id === itemToDelete.value!.id)
    if (index !== -1) {
      userMedia.value.splice(index, 1)
    }
    
    showDeleteConfirmation.value = false
    itemToDelete.value = null
  } catch (error) {
    console.error('Failed to delete media item:', error)
  } finally {
    isDeleting.value = false
  }
}

const cancelDelete = () => {
  showDeleteConfirmation.value = false
  showBulkDeleteConfirmation.value = false
  itemToDelete.value = null
}

// Download functionality
const downloadMedia = (media: MediaItem) => {
  // Create a temporary link and trigger download
  const link = document.createElement('a')
  link.href = media.url
  link.download = media.name || 'download'
  link.target = '_blank'
  document.body.appendChild(link)
  link.click()
  document.body.removeChild(link)
}

// Enhanced search functionality
const handleSearch = () => {
  if (!searchQuery.value.trim()) return
  // The filtering is handled by the computed property, so no additional action needed
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

  const dimensions = calculateDimensions(mediaData.width || 400, mediaData.height || 400)
  const imageData = {
    src: mediaData.url || mediaData.thumbnailUrl,
    alt: mediaData.name || 'Uploaded media'
  }
  emit('addMedia', { 
    type: 'image', 
    data: imageData,
    transform: dimensions
  })
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

onUnmounted(() => {
  // Cleanup if needed
})

// Enhanced search with debouncing
watch(searchQuery, (newQuery) => {
  if (searchTimeout.value) clearTimeout(searchTimeout.value)
  searchTimeout.value = setTimeout(() => {
    // The filtering is handled by computed property, no API call needed for local filtering
    if (newQuery.trim()) handleSearch()
  }, 500) // 500ms debounce
})
</script>

<style scoped>
/* Responsive Grid Layout with Proper Aspect Ratio Preservation */
.media-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 16px;
  width: 100%;
}

.media-item {
  width: 100%;
  min-width: 0;
  position: relative;
}

.media-preview {
  position: relative;
  width: 100%;
  height: 0; /* Height set by padding-bottom */
  overflow: hidden;
}

.media-preview img {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  object-fit: cover;
  border-radius: inherit;
}

/* Fallback when no image or error */
.media-preview svg {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
}

/* Enhanced selection indicator overlay */
.media-preview .absolute.inset-0 {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
}

/* File info overlay positioning */
.media-preview .absolute.bottom-0 {
  position: absolute;
  bottom: 0;
  left: 0;
  right: 0;
}

/* Responsive adjustments */
@media (min-width: 480px) {
  .media-grid {
    gap: 18px;
  }
}

@media (min-width: 768px) {
  .media-grid {
    gap: 20px;
  }
}

/* For very large sidebars, add 3rd column */
@media (min-width: 1400px) {
  .media-grid {
    grid-template-columns: repeat(3, minmax(0, 1fr));
  }
}

/* Smooth transitions for hover effects */
.media-item {
  transition: transform 0.2s ease-out, box-shadow 0.2s ease-out;
}

.media-item:hover {
  transform: translateY(-2px);
}

/* Handle selection states */
.media-item.ring-2 {
  transform: translateY(-1px);
}

.media-item.ring-2:hover {
  transform: translateY(-2px);
}

/* Ensure no overflow */
.media-grid * {
  box-sizing: border-box;
}
</style>

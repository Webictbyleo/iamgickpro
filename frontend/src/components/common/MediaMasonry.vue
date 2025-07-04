<template>
  <div class="media-masonry">
    <div
      v-for="file in mediaItems"
      :key="file.id"
      class="masonry-item relative group border border-gray-200 dark:border-gray-700 rounded-lg hover:border-primary-500 dark:hover:border-primary-400 hover:shadow-lg transition-all duration-200 bg-white dark:bg-gray-800 transform hover:scale-[1.02]"
      :class="{ 'ring-2 ring-primary-500 dark:ring-primary-400 border-primary-500 dark:border-primary-400': selectedItems.has(file.id) }"
    >
      <!-- Selection Checkbox -->
      <div v-if="showSelection" class="absolute top-3 left-3 z-10 transition-opacity duration-200" 
           :class="selectedItems.has(file.id) ? 'opacity-100' : 'opacity-0 group-hover:opacity-100'">
        <label class="relative flex items-center justify-center cursor-pointer group/checkbox">
          <input
            type="checkbox"
            :checked="selectedItems.has(file.id)"
            @change="toggleSelection(file.id)"
            @click.stop
            class="sr-only"
          />
          <div 
            class="w-5 h-5 rounded-md border-2 transition-all duration-200 flex items-center justify-center backdrop-blur-sm"
            :class="selectedItems.has(file.id) 
              ? 'bg-primary-500 dark:bg-primary-400 border-primary-500 dark:border-primary-400 shadow-lg' 
              : 'bg-white/90 dark:bg-gray-800/90 border-white/70 dark:border-gray-600/70 shadow-md hover:bg-white dark:hover:bg-gray-800 hover:border-primary-400 dark:hover:border-primary-500 hover:shadow-lg'"
          >
            <svg 
              v-if="selectedItems.has(file.id)"
              class="w-3 h-3 text-white"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
              stroke-width="3"
            >
              <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
            </svg>
          </div>
        </label>
      </div>

      <!-- Context Dropdown Menu -->
      <div class="absolute top-2 right-2 z-20">
        <ContextDropdown
          :actions="actions"
          :title="file.name"
          :subtitle="getFileSubtitle(file)"
          position="auto"
          trigger-class="opacity-0 group-hover:opacity-100 bg-white dark:bg-gray-800"
          @action="handleDropdownAction($event, file)"
        />
      </div>        <!-- Media Preview -->
        <div 
          @click="$emit('mediaClick', file)"
          class="media-preview bg-gradient-to-br from-gray-50 to-gray-100 dark:from-gray-700 dark:to-gray-800 flex items-center justify-center relative overflow-hidden cursor-pointer rounded-lg"
          :style="getMediaItemStyle(file)"
        >
          <img
            v-if="file.type === 'image' && !imageErrors.has(file.id)"
            :src="file.thumbnail || file.url"
            :alt="file.name"
            :data-file-id="file.id"
            class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-200"
            loading="lazy"
            @error="handleImageError"
          />
          <div v-if="file.type !== 'image' || imageErrors.has(file.id)" class="text-gray-400 dark:text-gray-500 bg-gray-100 dark:bg-gray-700 w-full h-full flex items-center justify-center">
            <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
            </svg>
          </div>
          
          <!-- Info Overlay for Stock Media -->
          <div 
            v-if="showInfoOverlay && file.source !== 'upload'"
            class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-black/80 via-black/40 to-transparent p-3 text-white opacity-0 group-hover:opacity-100 transition-opacity duration-200"
          >
            <div class="space-y-1">
              <p class="text-sm font-medium truncate">{{ file.name || 'Untitled' }}</p>
              <div class="flex items-center justify-between text-xs text-gray-200">
                <span v-if="file.width && file.height">{{ file.width }}×{{ file.height }}</span>
                <span v-if="file.size" class="ml-auto">{{ formatFileSize(file.size) }}</span>
              </div>
              <div v-if="file.attribution || isStockMedia(file)" class="text-xs text-gray-300 truncate">
                <span v-if="file.attribution">{{ file.attribution }}</span>
                <span v-if="file.attribution && isStockMedia(file)"> • </span>
                <span v-if="isStockMedia(file)" class="capitalize">{{ getSourceDisplayName(file.source) }}</span>
              </div>
            </div>
          </div>
          
          <!-- Premium Badge for Stock Media -->
          <div 
            v-if="file.isPremium && isStockMedia(file)"
            class="absolute top-2 left-2 bg-gradient-to-r from-warning-400 to-warning-500 text-white px-2 py-1 rounded-md text-xs font-medium shadow-lg"
          >
            ⭐ PRO
          </div>
        </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted, nextTick } from 'vue'
import type { MediaItem } from '@/types'
import ContextDropdown from '@/components/common/ContextDropdown.vue'
import type { DropdownAction } from '@/components/common/ContextDropdown.vue'

interface Props {
  mediaItems: MediaItem[]
  selectedItems?: Set<string>
  actions?: DropdownAction[]
  columns?: number
  gap?: number
  showSelection?: boolean
  showInfoOverlay?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  selectedItems: () => new Set(),
  actions: () => [],
  columns: 2,
  gap: 16,
  showSelection: true,
  showInfoOverlay: true
})

const emit = defineEmits<{
  'mediaClick': [file: MediaItem]
  'toggleSelection': [id: string]
  'action': [action: string, file: MediaItem]
}>()

// Local state
const imageErrors = ref<Set<string>>(new Set())

// Methods
const toggleSelection = (itemId: string) => {
  emit('toggleSelection', itemId)
}

const handleDropdownAction = (action: DropdownAction, file: MediaItem) => {
  emit('action', action.key, file)
}

const getFileSubtitle = (file: MediaItem): string => {
  const parts = []
  
  if (file.size) {
    parts.push(formatFileSize(file.size))
  }
  
  if (file.width && file.height) {
    parts.push(`${file.width}×${file.height}`)
  }
  
  return parts.join(' • ')
}

// Smart media item styling that maintains image proportions 
const getMediaItemStyle = (file: MediaItem) => {
  if (!file.width || !file.height || file.type !== 'image') {
    return {
      aspectRatio: '1 / 1' // Square fallback
    }
  }
  
  const aspectRatio = file.width / file.height
  
  // Return natural aspect ratio but constrain extreme values
  const constrainedRatio = Math.max(0.5, Math.min(3, aspectRatio))
  
  return {
    aspectRatio: `${constrainedRatio} / 1`
  }
}

// Error handling
const handleImageError = (event: Event) => {
  const target = event.target as HTMLImageElement
  target.style.display = 'none'
  const parent = target.parentElement
  if (parent) {
    parent.innerHTML = `
      <div class="w-full h-full bg-gray-100 flex items-center justify-center">
        <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
        </svg>
      </div>
    `
  }
}

// Utility functions
const formatFileSize = (bytes: number): string => {
  if (bytes === 0) return '0 Bytes'
  const k = 1024
  const sizes = ['Bytes', 'KB', 'MB', 'GB']
  const i = Math.floor(Math.log(bytes) / Math.log(k))
  return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i]
}

// Helper function to check if media is from stock providers
const isStockMedia = (file: MediaItem): boolean => {
  return ['unsplash', 'pexels', 'iconfinder', 'giphy'].includes(file.source)
}

// Helper function to get display name for source
const getSourceDisplayName = (source: MediaItem['source']): string => {
  const sourceNames: Record<MediaItem['source'], string> = {
    'unsplash': 'Unsplash',
    'pexels': 'Pexels',
    'iconfinder': 'Iconfinder',
    'giphy': 'Giphy',
    'upload': 'Upload',
    'generated': 'Generated'
  }
  return sourceNames[source] || 'Stock Image'
}
</script>

<style scoped>
/* True Masonry Layout using CSS Columns */
.media-masonry {
  column-count: v-bind(columns);
  column-gap: v-bind(gap + 'px');
  width: 100%;
}

.masonry-item {
  display: inline-block;
  width: 100%;
  margin-bottom: v-bind(gap + 'px');
  position: relative;
  break-inside: avoid;
  vertical-align: top;
}

.media-preview {
  position: relative;
  width: 100%;
  overflow: hidden;
  /* Use aspect-ratio for natural image proportions */
}

.media-preview img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  border-radius: inherit;
}

.media-preview .absolute.inset-0 {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
}

.media-preview .absolute.bottom-0 {
  position: absolute;
  bottom: 0;
  left: 0;
  right: 0;
}

/* Responsive adjustments */
@media (min-width: 768px) {
  .media-masonry {
    column-gap: v-bind((gap + 4) + 'px');
  }
  
  .masonry-item {
    margin-bottom: v-bind((gap + 4) + 'px');
  }
}

@media (min-width: 1024px) {
  .media-masonry {
    column-gap: v-bind((gap + 8) + 'px');
  }
  
  .masonry-item {
    margin-bottom: v-bind((gap + 8) + 'px');
  }
}

/* Smooth transitions for hover effects */
.masonry-item {
  transition: transform 0.2s ease-out, box-shadow 0.2s ease-out;
}

.masonry-item:hover {
  transform: translateY(-2px);
}

/* Handle selection states */
.masonry-item.ring-2 {
  transform: translateY(-1px);
}

.masonry-item.ring-2:hover {
  transform: translateY(-2px);
}
</style>

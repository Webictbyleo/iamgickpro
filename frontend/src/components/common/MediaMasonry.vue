<template>
  <div class="media-masonry">
    <div
      v-for="file in mediaItems"
      :key="file.id"
      class="masonry-item relative group border border-gray-200 rounded-lg hover:border-blue-500 hover:shadow-lg transition-all duration-200 bg-white transform hover:scale-[1.02]"
      :class="{ 'ring-2 ring-blue-500 border-blue-500': selectedItems.has(file.id) }"
    >
      <!-- Selection Checkbox -->
      <div class="absolute top-2 left-2 z-10 opacity-0 group-hover:opacity-100 transition-opacity">
        <label class="relative flex items-center justify-center w-6 h-6 bg-white/90 rounded-full shadow-sm cursor-pointer hover:bg-white transition-all">
          <input
            type="checkbox"
            :checked="selectedItems.has(file.id)"
            @change="toggleSelection(file.id)"
            @click.stop
            class="sr-only"
          />
          <div 
            class="w-4 h-4 rounded-full border-2 transition-all flex items-center justify-center"
            :class="selectedItems.has(file.id) 
              ? 'bg-blue-600 border-blue-600' 
              : 'border-gray-300 hover:border-blue-400'"
          >
            <svg 
              v-if="selectedItems.has(file.id)"
              class="w-2.5 h-2.5 text-white"
              fill="currentColor" 
              viewBox="0 0 20 20"
            >
              <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
            </svg>
          </div>
        </label>
      </div>

      <!-- Dropdown Menu -->
      <div class="absolute top-2 right-2 z-20 dropdown-container">
        <div class="relative">
          <button
            class="w-8 h-8 bg-white/90 rounded-full flex items-center justify-center shadow-sm hover:bg-white transition-all"
            :class="dropdownOpen === file.id ? 'opacity-100 bg-white' : 'opacity-0 group-hover:opacity-100'"
            @click.stop="toggleDropdown(file.id)"
          >
            <svg class="w-4 h-4 text-gray-600" fill="currentColor" viewBox="0 0 20 20">
              <path d="M10 12a2 2 0 100-4 2 2 0 000 4z"/>
              <path d="M10 4a2 2 0 100-4 2 2 0 000 4z"/>
              <path d="M10 20a2 2 0 100-4 2 2 0 000 4z"/>
            </svg>
          </button>
          
          <!-- Dropdown Menu -->
          <div 
            v-if="dropdownOpen === file.id"
            class="absolute left-0 top-full mt-1 w-40 bg-white rounded-lg shadow-lg border border-gray-100 py-1 z-50"
            @click.stop
          >
            <button
              v-for="action in actions"
              :key="action.key"
              @click="handleAction(action.key, file)"
              :class="[
                'w-full px-4 py-2 text-left text-sm flex items-center transition-colors',
                action.key === 'delete' 
                  ? 'text-red-600 hover:bg-red-50' 
                  : 'text-gray-700 hover:bg-gray-50'
              ]"
            >
              <component :is="action.icon" class="w-4 h-4 mr-2" />
              {{ action.label }}
            </button>
            
            <hr v-if="actions.some(a => a.key === 'delete')" class="my-1 border-gray-100">
          </div>
        </div>
      </div>

      <!-- Media Preview -->
      <div 
        @click="$emit('mediaClick', file)"
        class="media-preview bg-gradient-to-br from-gray-50 to-gray-100 flex items-center justify-center relative overflow-hidden cursor-pointer rounded-lg"
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
        <div v-if="file.type !== 'image' || imageErrors.has(file.id)" class="text-gray-400 bg-gray-100 w-full h-full flex items-center justify-center">
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
      <div class="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-black/60 to-transparent p-2 opacity-0 group-hover:opacity-100 transition-opacity rounded-b-lg">
        <p class="text-white text-xs truncate">{{ file.name }}</p>
        <p v-if="file.size" class="text-white/80 text-xs">{{ formatFileSize(file.size) }}</p>
        <p v-if="file.width && file.height" class="text-white/80 text-xs">{{ file.width }}×{{ file.height }}</p>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue'
import type { MediaItem } from '@/types'

interface MediaAction {
  key: string
  label: string
  icon: any
}

interface Props {
  mediaItems: MediaItem[]
  selectedItems?: Set<string>
  actions?: MediaAction[]
  columns?: number
  gap?: number
}

const props = withDefaults(defineProps<Props>(), {
  selectedItems: () => new Set(),
  actions: () => [],
  columns: 2,
  gap: 16
})

const emit = defineEmits<{
  'mediaClick': [file: MediaItem]
  'toggleSelection': [id: string]
  'action': [action: string, file: MediaItem]
}>()

// Local state
const dropdownOpen = ref<string | null>(null)
const imageErrors = ref<Set<string>>(new Set())

// Methods
const toggleSelection = (itemId: string) => {
  emit('toggleSelection', itemId)
}

const toggleDropdown = (itemId: string) => {
  dropdownOpen.value = dropdownOpen.value === itemId ? null : itemId
}

const closeDropdown = () => {
  dropdownOpen.value = null
}

const handleAction = (action: string, file: MediaItem) => {
  emit('action', action, file)
  closeDropdown()
}

// Handle click outside to close dropdown
const handleClickOutside = (event: Event) => {
  const target = event.target as Element
  if (!target.closest('.dropdown-container')) {
    closeDropdown()
  }
}

onMounted(() => {
  document.addEventListener('click', handleClickOutside)
})

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside)
})

// Smart media item styling that maintains image proportions with reduced spacing
const getMediaItemStyle = (file: MediaItem) => {
  if (!file.width || !file.height || file.type !== 'image') {
    return {
      height: '200px' // Fixed height fallback
    }
  }
  
  const aspectRatio = file.width / file.height
  let height: number
  
  // Use a more conservative height calculation to reduce gaps
  if (aspectRatio > 2) {
    // Very wide images: smaller height
    height = 150
  } else if (aspectRatio > 1.5) {
    // Wide landscape images
    height = 180
  } else if (aspectRatio > 1) {
    // Landscape images
    height = 200
  } else if (aspectRatio > 0.75) {
    // Square-ish images
    height = 220
  } else if (aspectRatio > 0.5) {
    // Portrait images
    height = 260
  } else {
    // Very tall images: limited height
    height = 300
  }
  
  // Ensure reasonable bounds - tighter range for better grid consistency
  height = Math.max(150, Math.min(300, height))
  
  return {
    height: `${height}px`
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
</script>

<style scoped>
/* Improved Masonry Grid Layout */
.media-masonry {
  display: grid;
  grid-template-columns: repeat(v-bind(columns), minmax(0, 1fr));
  gap: v-bind(gap + 'px');
  width: 100%;
  /* Enable CSS Grid masonry when supported */
  grid-template-rows: masonry;
}

/* Fallback for browsers that don't support masonry */
@supports not (grid-template-rows: masonry) {
  .media-masonry {
    display: grid;
    grid-template-columns: repeat(v-bind(columns), minmax(0, 1fr));
    gap: v-bind(gap + 'px');
    align-items: start;
  }
}

.masonry-item {
  width: 100%;
  min-width: 0;
  position: relative;
  break-inside: avoid;
}

.media-preview {
  position: relative;
  width: 100%;
  overflow: hidden;
  /* Remove padding-bottom approach for better spacing */
}

.media-preview img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  border-radius: inherit;
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
@media (min-width: 768px) {
  .media-masonry {
    gap: v-bind((gap + 4) + 'px');
  }
}

@media (min-width: 1024px) {
  .media-masonry {
    gap: v-bind((gap + 8) + 'px');
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

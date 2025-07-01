<template>
  <div class="media-masonry">
    <div
      v-for="file in mediaItems"
      :key="file.id"
      class="masonry-item relative group border border-gray-200 rounded-lg hover:border-blue-500 hover:shadow-lg transition-all duration-200 bg-white transform hover:scale-[1.02]"
      :class="{ 'ring-2 ring-blue-500 border-blue-500': selectedItems.has(file.id) }"
      :ref="(el: any) => setDropdownRef(file.id, el as HTMLElement)"
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
              ? 'bg-blue-500 border-blue-500 shadow-lg' 
              : 'bg-white/90 border-white/70 shadow-md hover:bg-white hover:border-blue-400 hover:shadow-lg'"
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

      <!-- Dropdown Menu -->
      <div class="absolute top-2 right-2 z-20">
        <button
          :ref="(el: any) => setDropdownButtonRef(file.id, el as HTMLElement)"
          class="w-8 h-8 bg-white rounded-full flex items-center justify-center shadow-sm hover:shadow-md transition-all duration-200"
          :class="dropdownOpen === file.id ? 'opacity-100 bg-white shadow-md' : 'opacity-0 group-hover:opacity-100'"
          @click.stop="toggleDropdown(file.id)"
        >
          <svg class="w-4 h-4 text-gray-600" fill="currentColor" viewBox="0 0 20 20">
            <path d="M10 12a2 2 0 100-4 2 2 0 000 4z"/>
            <path d="M10 4a2 2 0 100-4 2 2 0 000 4z"/>
            <path d="M10 20a2 2 0 100-4 2 2 0 000 4z"/>
          </svg>
        </button>
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
        
        <!-- Info Overlay for Stock Media -->
        <div 
          v-if="file.source !== 'upload'"
          class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-black/80 via-black/40 to-transparent p-3 text-white opacity-0 group-hover:opacity-100 transition-opacity duration-200"
        >
          <div class="space-y-1">
            <p class="text-sm font-medium truncate">{{ file.name || 'Untitled' }}</p>
            <div class="flex items-center justify-between text-xs text-gray-200">
              <span v-if="file.width && file.height">{{ file.width }}×{{ file.height }}</span>
              <span v-if="file.size" class="ml-auto">{{ formatFileSize(file.size) }}</span>
            </div>
            <div v-if="file.attribution || file.source === 'stock'" class="text-xs text-gray-300 truncate">
              <span v-if="file.attribution">{{ file.attribution }}</span>
              <span v-if="file.attribution && file.source === 'stock'"> • </span>
              <span v-if="file.source === 'stock'" class="capitalize">Stock Image</span>
            </div>
          </div>
        </div>
        
        <!-- Premium Badge for Stock Media -->
        <div 
          v-if="file.isPremium && file.source === 'stock'"
          class="absolute top-2 left-2 bg-gradient-to-r from-amber-400 to-orange-500 text-white px-2 py-1 rounded-md text-xs font-medium shadow-lg"
        >
          ⭐ PRO
        </div>
      </div>
    </div>
  </div>

  <!-- Floating Dropdown Portal -->
  <Teleport to="body">
    <div 
      v-if="dropdownOpen && getFileById(dropdownOpen)"
      class="fixed w-48 bg-white rounded-lg shadow-xl border border-gray-100 py-1 z-[9999] transition-all duration-200 ease-out"
      :style="dropdownPosition"
      @click.stop
    >
      <!-- File Info Section -->
      <div class="px-4 py-3 border-b border-gray-100">
        <p class="text-sm font-medium text-gray-900 truncate">{{ getFileById(dropdownOpen)?.name }}</p>
        <div class="flex items-center justify-between mt-1 text-xs text-gray-500">
          <span v-if="getFileById(dropdownOpen)?.size">{{ formatFileSize(getFileById(dropdownOpen)!.size) }}</span>
          <span v-if="getFileById(dropdownOpen)?.width && getFileById(dropdownOpen)?.height">
            {{ getFileById(dropdownOpen)?.width }}×{{ getFileById(dropdownOpen)?.height }}
          </span>
        </div>
      </div>

      <!-- Action Buttons -->
      <div class="py-1">
        <button
          v-for="action in actions"
          :key="action.key"
          @click="handleAction(action.key, getFileById(dropdownOpen)!)"
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
      </div>
    </div>
  </Teleport>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted, nextTick } from 'vue'
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
  showSelection?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  selectedItems: () => new Set(),
  actions: () => [],
  columns: 2,
  gap: 16,
  showSelection: true
})

const emit = defineEmits<{
  'mediaClick': [file: MediaItem]
  'toggleSelection': [id: string]
  'action': [action: string, file: MediaItem]
}>()

// Local state
const dropdownOpen = ref<string | null>(null)
const dropdownRefs = ref<Record<string, HTMLElement | null>>({})
const dropdownButtonRefs = ref<Record<string, HTMLElement | null>>({})
const imageErrors = ref<Set<string>>(new Set())
const dropdownPosition = ref({ top: '0px', left: '0px' })

// Methods
const toggleSelection = (itemId: string) => {
  emit('toggleSelection', itemId)
}

const toggleDropdown = (itemId: string) => {
  if (dropdownOpen.value === itemId) {
    dropdownOpen.value = null
  } else {
    dropdownOpen.value = itemId
    nextTick(() => {
      positionDropdown(itemId)
    })
  }
}

const closeDropdown = () => {
  dropdownOpen.value = null
}

const handleAction = (action: string, file: MediaItem) => {
  emit('action', action, file)
  closeDropdown()
}

const setDropdownRef = (id: string, el: HTMLElement | null) => {
  if (el) dropdownRefs.value[id] = el
}

const setDropdownButtonRef = (id: string, el: HTMLElement | null) => {
  if (el) dropdownButtonRefs.value[id] = el
}

const positionDropdown = (itemId: string) => {
  const buttonEl = dropdownButtonRefs.value[itemId]
  if (!buttonEl) return

  const calculatePosition = () => {
    const buttonRect = buttonEl.getBoundingClientRect()
    const windowWidth = window.innerWidth
    const windowHeight = window.innerHeight
    const dropdownWidth = 192 // w-48 = 192px
    
    // Calculate horizontal position
    let left = buttonRect.right - dropdownWidth
    if (left < 16) {
      left = buttonRect.left
    }
    if (left + dropdownWidth > windowWidth - 16) {
      left = windowWidth - dropdownWidth - 16
    }
    
    // Calculate vertical position
    let top = buttonRect.bottom + 4
    
    // Get actual dropdown element to check height
    const dropdownEl = document.querySelector('.fixed.w-48.bg-white.rounded-lg.shadow-xl') as HTMLElement
    if (dropdownEl) {
      const actualHeight = dropdownEl.offsetHeight
      
      // Position above if dropdown would overflow below
      if (top + actualHeight > windowHeight - 16) {
        top = buttonRect.top - actualHeight - 4
      }
      
      // Ensure dropdown doesn't go above viewport
      if (top < 16) {
        top = Math.min(buttonRect.bottom + 4, windowHeight - actualHeight - 16)
      }
    } else {
      // Fallback estimation when dropdown element is not yet available
      const estimatedHeight = 150
      if (top + estimatedHeight > windowHeight - 16) {
        top = buttonRect.top - estimatedHeight - 4
      }
    }
    
    return {
      top: `${Math.round(top)}px`,
      left: `${Math.round(left)}px`
    }
  }

  // Set initial position
  dropdownPosition.value = calculatePosition()
  
  // Fine-tune position after dropdown is rendered
  nextTick(() => {
    dropdownPosition.value = calculatePosition()
  })
}

const getFileById = (id: string | null): MediaItem | null => {
  if (!id) return null
  return props.mediaItems.find(file => file.id === id) || null
}

// Handle click outside to close dropdown
const handleClickOutside = (event: Event) => {
  if (!dropdownOpen.value) return
  
  const target = event.target as HTMLElement
  const openId = dropdownOpen.value
  const buttonEl = dropdownButtonRefs.value[openId]
  
  // Check if click is on the dropdown button
  if (buttonEl && buttonEl.contains(target)) {
    return
  }
  
  // Check if click is on the floating dropdown itself
  const dropdownEl = document.querySelector('.fixed.w-48.bg-white.rounded-lg.shadow-xl')
  if (dropdownEl && dropdownEl.contains(target)) {
    return
  }
  
  // Close dropdown if clicked outside
  closeDropdown()
}

onMounted(() => {
  document.addEventListener('mousedown', handleClickOutside)
})
onUnmounted(() => {
  document.removeEventListener('mousedown', handleClickOutside)
})

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

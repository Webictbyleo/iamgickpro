<template>
  <div 
    class="group cursor-pointer"
    @click="handleClick"
    @mouseenter="isHovered = true"
    @mouseleave="isHovered = false"
  >
    <!-- Design Thumbnail Container with dynamic aspect ratio handling -->
    <div class="relative bg-white rounded-lg shadow-sm border border-gray-200 hover:shadow-lg transition-all duration-200 overflow-hidden">
      <!-- Main Thumbnail Area with adaptive height -->
      <div 
        class="relative bg-gray-50 flex items-center justify-center p-4"
        :style="getThumbnailContainerStyle()"
      >
        <!-- Loading state -->
        <div v-if="isImageLoading" class="absolute inset-0 flex items-center justify-center">
          <div class="animate-spin rounded-full h-6 w-6 border-2 border-gray-300 border-t-blue-500"></div>
        </div>
        
        <!-- Design thumbnail image with smart sizing -->
        <img 
          v-if="design.thumbnail"
          :src="design.thumbnail"
          :alt="design.title"
          :class="getImageClasses()"
          @load="isImageLoading = false"
          @error="handleImageError"
        />
        
        <!-- Fallback placeholder with consistent styling -->
        <div v-else class="flex items-center justify-center bg-gray-50 rounded-md" :style="{ minHeight: '120px' }">
          <div class="text-center">
            <component :is="icons.template" class="w-12 h-12 text-gray-300 mx-auto mb-2" />
            <span class="text-xs text-gray-400 font-medium">{{ getDesignType() }}</span>
          </div>
        </div>
        
        <!-- Hover Overlay -->
        <div class="absolute inset-0 bg-black/0 group-hover:bg-black/20 transition-all duration-200"></div>
        
        <!-- Action Buttons - Positioned at top right -->
        <div class="absolute top-2 right-2 opacity-0 group-hover:opacity-100 transition-opacity duration-200">
          <div class="flex flex-col space-y-1">
            <!-- More actions dropdown -->
            <Menu as="div" class="relative">
              <MenuButton 
                @click.stop
                class="p-1.5 bg-white/90 backdrop-blur-sm rounded-md shadow-sm hover:bg-white transition-colors duration-150 text-gray-600 hover:text-gray-800"
                title="More Actions"
              >
                <component :is="icons.moreHorizontal" class="w-4 h-4" />
              </MenuButton>
              
              <Transition
                enter-active-class="transition ease-out duration-100"
                enter-from-class="transform opacity-0 scale-95"
                enter-to-class="transform opacity-100 scale-100"
                leave-active-class="transition ease-in duration-75"
                leave-from-class="transform opacity-100 scale-100"
                leave-to-class="transform opacity-0 scale-95"
              >
                <MenuItems class="absolute right-0 mt-1 w-44 origin-top-right bg-white rounded-lg shadow-lg ring-1 ring-black/5 focus:outline-none z-50 border border-gray-100">
                  <div class="py-1">
                    <MenuItem v-slot="{ active }">
                      <button
                        @click.stop="$emit('edit', design)"
                        :class="[
                          active ? 'bg-gray-50' : '',
                          'flex items-center w-full px-3 py-2 text-sm text-gray-700'
                        ]"
                      >
                        <component :is="icons.edit" class="w-4 h-4 mr-3" />
                        Edit
                      </button>
                    </MenuItem>
                    <MenuItem v-slot="{ active }">
                      <button
                        @click.stop="$emit('duplicate', design)"
                        :class="[
                          active ? 'bg-gray-50' : '',
                          'flex items-center w-full px-3 py-2 text-sm text-gray-700'
                        ]"
                      >
                        <component :is="icons.duplicate" class="w-4 h-4 mr-3" />
                        Duplicate
                      </button>
                    </MenuItem>
                    <MenuItem v-slot="{ active }">
                      <button
                        @click.stop="handleDownload"
                        :class="[
                          active ? 'bg-gray-50' : '',
                          'flex items-center w-full px-3 py-2 text-sm text-gray-700'
                        ]"
                      >
                        <component :is="icons.download" class="w-4 h-4 mr-3" />
                        Download
                      </button>
                    </MenuItem>
                    <div class="border-t border-gray-100 my-1"></div>
                    <MenuItem v-slot="{ active }">
                      <button
                        @click.stop="$emit('delete', design)"
                        :class="[
                          active ? 'bg-red-50' : '',
                          'flex items-center w-full px-3 py-2 text-sm text-red-600'
                        ]"
                      >
                        <component :is="icons.delete" class="w-4 h-4 mr-3" />
                        Delete
                      </button>
                    </MenuItem>
                  </div>
                </MenuItems>
              </Transition>
            </Menu>
          </div>
        </div>
      </div>
      
      <!-- Design Info Footer -->
      <div class="px-3 py-2 bg-white border-t border-gray-100">
        <h3 class="text-sm font-medium text-gray-900 truncate leading-tight">
          {{ design.title }}
        </h3>
        <div class="flex items-center justify-between mt-1">
          <span class="text-xs text-gray-500">
            {{ design.width }} × {{ design.height }}
          </span>
          <span class="text-xs text-gray-400">
            {{ formatDate(design.updatedAt) }}
          </span>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { Menu, MenuButton, MenuItems, MenuItem } from '@headlessui/vue'
import type { Design } from '@/types'
import { useIcons } from '@/composables/useIcons'

const icons = useIcons()

interface Props {
  design: Design
}

const props = defineProps<Props>()

const emit = defineEmits<{
  click: [design: Design]
  edit: [design: Design]
  duplicate: [design: Design]
  delete: [design: Design]
  preview: [design: Design]
  share: [design: Design]
  rename: [design: Design]
  download: [design: Design]
  quickAction: [design: Design]
}>()

// Component state
const isHovered = ref(false)
const isImageLoading = ref(true)

// Event handlers
const handleClick = () => {
  emit('click', props.design)
}

const handlePreview = () => {
  emit('preview', props.design)
}

const handleShare = () => {
  emit('share', props.design)
}

const handleRename = () => {
  emit('rename', props.design)
}

const handleDownload = () => {
  emit('download', props.design)
}

const handleQuickAction = () => {
  emit('quickAction', props.design)
}

const handleImageError = () => {
  isImageLoading.value = false
}

// Utility functions

// Calculate dynamic thumbnail container style based on design dimensions
const getThumbnailContainerStyle = () => {
  const { width, height } = props.design
  const aspectRatio = width / height
  
  // Define max dimensions for the thumbnail container
  const maxWidth = 280
  const maxHeight = 320
  const minHeight = 160
  
  let containerHeight: number
  
  if (aspectRatio > 1.5) {
    // Wide landscape designs (banners, etc.)
    containerHeight = Math.max(minHeight, Math.min(maxHeight * 0.6, maxWidth / aspectRatio))
  } else if (aspectRatio < 0.7) {
    // Tall portrait designs
    containerHeight = Math.min(maxHeight, maxWidth / aspectRatio)
  } else {
    // Square-ish or standard dimensions
    containerHeight = Math.min(maxHeight * 0.8, maxWidth / aspectRatio)
  }
  
  return {
    height: `${Math.round(containerHeight)}px`,
    minHeight: `${minHeight}px`
  }
}

// Get appropriate image classes based on design aspect ratio
const getImageClasses = () => {
  const { width, height } = props.design
  const aspectRatio = width / height
  
  const baseClasses = 'max-w-full max-h-full object-contain bg-white rounded-md shadow-sm'
  
  if (aspectRatio > 1.5) {
    // Wide designs - ensure they fit width-wise
    return `${baseClasses} w-full h-auto`
  } else if (aspectRatio < 0.7) {
    // Tall designs - ensure they fit height-wise
    return `${baseClasses} h-full w-auto`
  } else {
    // Balanced designs
    return `${baseClasses} w-full h-auto`
  }
}

const formatDate = (date: string | Date) => {
  const d = new Date(date)
  const now = new Date()
  const diff = now.getTime() - d.getTime()
  const days = Math.floor(diff / (1000 * 60 * 60 * 24))
  const hours = Math.floor(diff / (1000 * 60 * 60))
  const minutes = Math.floor(diff / (1000 * 60))
  
  if (minutes < 1) return 'Just now'
  if (minutes < 60) return `${minutes}m ago`
  if (hours < 24) return `${hours}h ago`
  if (days === 0) return 'Today'
  if (days === 1) return 'Yesterday'
  if (days < 7) return `${days} days ago`
  if (days < 30) return `${Math.floor(days / 7)} weeks ago`
  if (days < 365) return `${Math.floor(days / 30)} months ago`
  
  return d.toLocaleDateString()
}

const getDesignType = () => {
  // Determine design type based on dimensions with more granular categories
  const { width, height } = props.design
  const aspectRatio = width / height
  
  if (Math.abs(aspectRatio - 1) < 0.1) return 'Square'
  
  if (aspectRatio > 2.5) return 'Banner'
  if (aspectRatio > 1.8) return 'Wide'
  if (aspectRatio > 1.3) return 'Landscape'
  if (aspectRatio > 1) return 'Horizontal'
  
  if (aspectRatio < 0.4) return 'Vertical'
  if (aspectRatio < 0.6) return 'Tall'
  if (aspectRatio < 0.8) return 'Portrait'
  
  return 'Standard'
}
</script>

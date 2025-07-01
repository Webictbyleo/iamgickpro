<template>
  <div class="media-grid">
    <div
      v-for="item in items"
      :key="item.id"
      class="media-item group"
      :class="{ 'selected': selectedItems.has(item.uuid || item.id) }"
    >
      <!-- Selection Checkbox (only for user uploads) -->
      <div 
        v-if="showSelection && canDelete(item)"
        class="absolute top-2 left-2 z-10"
        @click.stop
      >
        <input
          type="checkbox"
          :checked="selectedItems.has(item.uuid || item.id)"
          @change="toggleSelection(item)"
          class="w-4 h-4 text-violet-600 bg-white border-gray-300 rounded focus:ring-violet-500 focus:ring-2"
        />
      </div>

      <!-- Media Image -->
      <img
        :src="item.thumbnail || item.url"
        :alt="item.name || 'Media item'"
        class="media-image"
        loading="lazy"
        @click="$emit('openInEditor', item)"
      />
      
      <!-- Action Buttons Overlay -->
      <div 
        v-if="canDelete(item)"
        class="absolute top-2 right-2 opacity-0 group-hover:opacity-100 transition-opacity z-10"
      >
        <button
          @click.stop="$emit('deleteItem', item)"
          class="p-1.5 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors shadow-lg"
          title="Delete media"
        >
          <TrashIcon class="w-4 h-4" />
        </button>
      </div>
      
      <!-- Simple Info Overlay -->
      <div class="media-info-overlay">
        <div class="media-info">
          <p class="media-name">{{ item.name || 'Untitled' }}</p>
          <p class="media-size">{{ formatFileSize(item.size) }}</p>
        </div>
      </div>
      
      <!-- Premium Badge -->
      <div v-if="item.isPremium" class="premium-badge">
        ⭐ PRO
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { TrashIcon } from '@heroicons/vue/24/outline'
import type { MediaItem } from '@/types'

interface Props {
  items: MediaItem[]
  selectedItems?: Set<string>
  showSelection?: boolean
}

interface Emits {
  openInEditor: [item: MediaItem]
  deleteItem: [item: MediaItem]
  toggleSelection: [item: MediaItem]
}

const props = withDefaults(defineProps<Props>(), {
  selectedItems: () => new Set(),
  showSelection: false
})

const emit = defineEmits<Emits>()

// Check if item can be deleted (only user uploads)
const canDelete = (item: MediaItem): boolean => {
  return item.source === 'upload' || !item.source
}

// Toggle item selection
const toggleSelection = (item: MediaItem) => {
  emit('toggleSelection', item)
}

// Optimized utility functions with caching
const fileSizeCache = new Map<number, string>()
const formatFileSize = (bytes: number | undefined): string => {
  if (!bytes) return 'Unknown'
  if (fileSizeCache.has(bytes)) return fileSizeCache.get(bytes)!
  
  const sizes = ['B', 'KB', 'MB', 'GB']
  const i = Math.floor(Math.log(bytes) / Math.log(1024))
  const result = Math.round(bytes / Math.pow(1024, i) * 100) / 100 + ' ' + sizes[i]
  fileSizeCache.set(bytes, result)
  return result
}
</script>

<style scoped>
.media-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 1rem;
}

@media (min-width: 640px) {
  .media-grid {
    grid-template-columns: repeat(3, 1fr);
  }
}

@media (min-width: 768px) {
  .media-grid {
    grid-template-columns: repeat(4, 1fr);
  }
}

@media (min-width: 1024px) {
  .media-grid {
    grid-template-columns: repeat(5, 1fr);
  }
}

@media (min-width: 1280px) {
  .media-grid {
    grid-template-columns: repeat(6, 1fr);
  }
}

.media-item {
  position: relative;
  aspect-ratio: 1;
  background-color: #f3f4f6;
  border-radius: 0.5rem;
  overflow: hidden;
  cursor: pointer;
  contain: layout style paint;
  will-change: transform;
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.media-item:hover {
  transform: translateY(-2px);
  box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
}

.media-image {
  width: 100%;
  height: 100%;
  object-fit: cover;
  transition: transform 0.2s ease;
}

.media-item:hover .media-image {
  transform: scale(1.05);
}

.media-info-overlay {
  position: absolute;
  bottom: 0;
  left: 0;
  right: 0;
  background: linear-gradient(to top, rgba(0, 0, 0, 0.8), transparent);
  padding: 1rem 0.75rem 0.75rem;
  opacity: 0;
  transition: opacity 0.2s ease;
}

.media-item:hover .media-info-overlay {
  opacity: 1;
}

.media-info {
  color: white;
}

.media-name {
  font-size: 0.875rem;
  font-weight: 500;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  margin-bottom: 0.25rem;
}

.media-size {
  font-size: 0.75rem;
  color: #d1d5db;
}

.premium-badge {
  position: absolute;
  top: 0.5rem;
  left: 0.5rem;
  background: linear-gradient(to right, #fbbf24, #f97316);
  color: white;
  padding: 0.25rem 0.5rem;
  border-radius: 0.375rem;
  font-size: 0.75rem;
  font-weight: 500;
}

/* Performance optimizations */
.media-item {
  transform: translateZ(0); /* Force GPU acceleration */
}

.media-item * {
  backface-visibility: hidden;
  perspective: 1000px;
}

/* Optimize for scrolling */
.media-grid {
  contain: layout style;
}
</style>

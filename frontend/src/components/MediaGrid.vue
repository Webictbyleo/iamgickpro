<template>
  <div class="media-grid">
    <div
      v-for="item in items"
      :key="item.id"
      class="media-item"
      @click="$emit('select', item)"
    >
      <!-- Media Image -->
      <img
        :src="item.thumbnail || item.url"
        :alt="item.name || 'Media item'"
        class="media-image"
        loading="lazy"
      />
      
      <!-- Overlay (only visible on hover) -->
      <div class="media-overlay">
        <div class="media-actions">
          <button 
            @click.stop="$emit('select', item)"
            class="btn-select"
          >
            Select
          </button>
          <button 
            @click.stop="$emit('preview', item)"
            class="btn-preview"
          >
            <EyeIcon class="w-4 h-4" />
          </button>
        </div>
        
        <!-- Media Info -->
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
import { EyeIcon } from '@heroicons/vue/24/outline'
import type { MediaItem } from '@/types'

interface Props {
  items: MediaItem[]
}

interface Emits {
  select: [item: MediaItem]
  preview: [item: MediaItem]
}

defineProps<Props>()
defineEmits<Emits>()

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

.media-overlay {
  position: absolute;
  inset: 0;
  background-color: rgba(0, 0, 0, 0);
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  padding: 0.75rem;
  opacity: 0;
  transition: all 0.2s ease;
}

.media-item:hover .media-overlay {
  background-color: rgba(0, 0, 0, 0.6);
  opacity: 1;
}

.media-actions {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
}

.btn-select {
  background-color: white;
  color: #111827;
  padding: 0.375rem 0.75rem;
  border-radius: 0.375rem;
  font-size: 0.875rem;
  font-weight: 500;
  border: none;
  cursor: pointer;
  transition: background-color 0.15s ease;
}

.btn-select:hover {
  background-color: #f9fafb;
}

.btn-preview {
  background-color: #7c3aed;
  color: white;
  padding: 0.5rem;
  border-radius: 0.375rem;
  border: none;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: background-color 0.15s ease;
}

.btn-preview:hover {
  background-color: #6d28d9;
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

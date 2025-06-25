<template>
  <div class="bg-white rounded-xl border border-gray-200 overflow-hidden hover:shadow-lg transition-all duration-300 group">
    <!-- Media Preview -->
    <div class="relative aspect-video bg-gray-100 overflow-hidden">
      <img 
        :src="media.thumbnail" 
        :alt="media.title"
        class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
      />
      
      <!-- Play Button Overlay for Videos -->
      <div v-if="media.type === 'video'" class="absolute inset-0 flex items-center justify-center bg-black bg-opacity-20">
        <div class="w-12 h-12 bg-white bg-opacity-90 rounded-full flex items-center justify-center shadow-lg">
          <svg class="w-5 h-5 text-gray-800 ml-1" fill="currentColor" viewBox="0 0 24 24">
            <path d="M8 5v14l11-7z"/>
          </svg>
        </div>
      </div>
      
      <!-- Media Type Badge -->
      <div class="absolute top-3 left-3">
        <span class="px-2 py-1 text-xs font-semibold rounded-full"
              :class="getMediaTypeBadgeClass(media.type)">
          {{ getMediaTypeIcon(media.type) }} {{ media.type.toUpperCase() }}
        </span>
      </div>
      
      <!-- Viral Indicator -->
      <div v-if="media.isViral" class="absolute top-3 right-3">
        <div class="w-8 h-8 bg-red-500 rounded-full flex items-center justify-center animate-pulse">
          <span class="text-white text-xs font-bold">🔥</span>
        </div>
      </div>
      
      <!-- Duration for Videos -->
      <div v-if="media.type === 'video' && media.duration" class="absolute bottom-3 right-3">
        <span class="px-2 py-1 bg-black bg-opacity-75 text-white text-xs rounded">
          {{ formatDuration(media.duration) }}
        </span>
      </div>
    </div>

    <!-- Content -->
    <div class="p-4">
      <!-- Title and Description -->
      <h3 class="font-semibold text-gray-900 mb-2 line-clamp-2 group-hover:text-purple-600 transition-colors">
        {{ media.title }}
      </h3>
      
      <p class="text-sm text-gray-600 mb-3 line-clamp-2">
        {{ media.description }}
      </p>
      
      <!-- Platform and Author -->
      <div class="flex items-center justify-between mb-3">
        <div class="flex items-center space-x-2">
          <img :src="getPlatformIcon(media.platform)" :alt="media.platform" class="w-5 h-5">
          <span class="text-sm text-gray-500">{{ media.author }}</span>
        </div>
        <span class="text-xs text-gray-400">{{ formatDate(media.createdAt) }}</span>
      </div>
      
      <!-- Stats -->
      <div class="grid grid-cols-3 gap-2 mb-4">
        <div class="text-center">
          <div class="text-sm font-semibold text-gray-900">{{ formatNumber(media.views) }}</div>
          <div class="text-xs text-gray-500">Views</div>
        </div>
        <div class="text-center">
          <div class="text-sm font-semibold text-gray-900">{{ formatNumber(media.likes) }}</div>
          <div class="text-xs text-gray-500">Likes</div>
        </div>
        <div class="text-center">
          <div class="text-sm font-semibold text-gray-900">{{ formatNumber(media.shares) }}</div>
          <div class="text-xs text-gray-500">Shares</div>
        </div>
      </div>
      
      <!-- Tags -->
      <div class="flex flex-wrap gap-1 mb-4">
        <span 
          v-for="tag in media.tags.slice(0, 3)" 
          :key="tag"
          class="px-2 py-1 bg-gray-100 text-gray-600 text-xs rounded-full"
        >
          #{{ tag }}
        </span>
        <span v-if="media.tags.length > 3" class="text-xs text-gray-400">
          +{{ media.tags.length - 3 }} more
        </span>
      </div>
      
      <!-- Action Buttons -->
      <div class="flex space-x-2">
        <button
          @click="$emit('recreate', media)"
          class="flex-1 px-3 py-2 bg-purple-600 text-white text-sm font-medium rounded-lg hover:bg-purple-700 transition-colors"
        >
          🎨 Recreate
        </button>
        <button
          @click="$emit('view-analytics', media)"
          class="px-3 py-2 border border-gray-300 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-50 transition-colors"
        >
          📊
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import type { TrendingMedia } from '@/types'

interface Props {
  media: TrendingMedia
}

defineProps<Props>()

defineEmits<{
  recreate: [media: TrendingMedia]
  'view-analytics': [media: TrendingMedia]
}>()

const getMediaTypeBadgeClass = (type: string) => {
  switch (type) {
    case 'video':
      return 'bg-red-100 text-red-800'
    case 'image':
      return 'bg-blue-100 text-blue-800'
    case 'carousel':
      return 'bg-purple-100 text-purple-800'
    case 'story':
      return 'bg-green-100 text-green-800'
    default:
      return 'bg-gray-100 text-gray-800'
  }
}

const getMediaTypeIcon = (type: string) => {
  switch (type) {
    case 'video':
      return '🎥'
    case 'image':
      return '📸'
    case 'carousel':
      return '🎠'
    case 'story':
      return '📱'
    default:
      return '📄'
  }
}

const getPlatformIcon = (platform: string) => {
  const icons: Record<string, string> = {
    tiktok: '/icons/tiktok.svg',
    instagram: '/icons/instagram.svg',
    twitter: '/icons/twitter.svg',
    youtube: '/icons/youtube.svg',
    linkedin: '/icons/linkedin.svg',
    facebook: '/icons/facebook.svg'
  }
  return icons[platform] || '/icons/default.svg'
}

const formatDuration = (seconds: number) => {
  const mins = Math.floor(seconds / 60)
  const secs = seconds % 60
  return `${mins}:${secs.toString().padStart(2, '0')}`
}

const formatNumber = (num: number) => {
  if (num >= 1000000) {
    return (num / 1000000).toFixed(1) + 'M'
  } else if (num >= 1000) {
    return (num / 1000).toFixed(1) + 'K'
  }
  return num.toString()
}

const formatDate = (date: string) => {
  const d = new Date(date)
  const now = new Date()
  const diffTime = Math.abs(now.getTime() - d.getTime())
  const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24))
  
  if (diffDays === 1) return 'Yesterday'
  if (diffDays < 7) return `${diffDays}d ago`
  if (diffDays < 30) return `${Math.floor(diffDays / 7)}w ago`
  return `${Math.floor(diffDays / 30)}mo ago`
}
</script>

<style scoped>
.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.aspect-video {
  aspect-ratio: 16 / 9;
}
</style>

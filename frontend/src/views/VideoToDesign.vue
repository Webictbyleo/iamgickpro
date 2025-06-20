<template>
  <AppLayout>
    <div class="max-w-4xl mx-auto space-y-8">
      <!-- Header -->
      <div class="text-center">
        <h1 class="text-3xl font-bold text-gray-900">YouTube Thumbnail Generator</h1>
        <p class="text-lg text-gray-600 mt-2">
          Create eye-catching thumbnails inspired by any YouTube video using AI
        </p>
        <p class="text-sm text-gray-500 mt-1">
          Extract visual themes, colors, and key moments from YouTube videos to generate professional thumbnails
        </p>
      </div>

      <!-- YouTube Thumbnail Plugin Component -->
      <YoutubeThumbnailPlugin
        @thumbnail-selected="handleThumbnailSelected"
        @video-analyzed="handleVideoAnalyzed"
        @thumbnails-generated="handleThumbnailsGenerated"
        @error="handleError"
      />

      <!-- Quick Actions -->
      <div v-if="recentThumbnails.length > 0" class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Recent Thumbnails</h3>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
          <div
            v-for="thumbnail in recentThumbnails.slice(0, 4)"
            :key="thumbnail.id"
            class="group relative bg-gray-50 rounded-lg overflow-hidden border border-gray-200 cursor-pointer hover:shadow-md transition-shadow"
            @click="handleThumbnailSelected(thumbnail)"
          >
            <div class="aspect-video">
              <img
                :src="thumbnail.previewUrl"
                :alt="thumbnail.title"
                class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-200"
              />
            </div>
            <div class="p-2">
              <p class="text-xs text-gray-600 truncate">{{ thumbnail.title }}</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Tips and Information -->
      <div class="bg-blue-50 border border-blue-200 rounded-xl p-6">
        <div class="flex items-start space-x-3">
          <svg class="w-6 h-6 text-blue-600 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
          <div>
            <h3 class="text-lg font-semibold text-blue-900">How it Works</h3>
            <div class="mt-2 text-sm text-blue-800 space-y-2">
              <p>• <strong>Analyze:</strong> Enter any YouTube video URL to extract key visual elements</p>
              <p>• <strong>Customize:</strong> Choose your preferred style and generation options</p>
              <p>• <strong>Generate:</strong> Our AI creates multiple thumbnail variations based on the original video</p>
              <p>• <strong>Edit:</strong> Select a thumbnail to open it in the design editor for further customization</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Featured Styles -->
      <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Popular Thumbnail Styles</h3>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
          <div
            v-for="style in featuredStyles"
            :key="style.value"
            class="text-center p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors"
          >
            <div class="w-12 h-12 bg-red-100 text-red-600 rounded-lg flex items-center justify-center mx-auto mb-2">
              <component :is="getStyleIcon(style.value)" class="w-6 h-6" />
            </div>
            <h4 class="font-medium text-gray-900 text-sm">{{ style.label }}</h4>
            <p class="text-xs text-gray-600 mt-1">{{ style.description }}</p>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import { ref, h } from 'vue'
import { useRouter } from 'vue-router'
import AppLayout from '@/components/layout/AppLayout.vue'
import YoutubeThumbnailPlugin from '@/components/plugins/YoutubeThumbnailPlugin.vue'
import { designAPI } from '@/services/api'
import type { YouTubeVideoInfo, YouTubeThumbnailDisplay } from '@/types'

const router = useRouter()

// State
const recentThumbnails = ref<YouTubeThumbnailDisplay[]>([])
const currentVideoInfo = ref<YouTubeVideoInfo | null>(null)

// Featured styles for display
const featuredStyles = [
  {
    value: 'modern',
    label: 'Modern',
    description: 'Clean and professional'
  },
  {
    value: 'dramatic',
    label: 'Dramatic',
    description: 'High contrast and bold'
  },
  {
    value: 'colorful',
    label: 'Colorful',
    description: 'Vibrant and eye-catching'
  },
  {
    value: 'gaming',
    label: 'Gaming',
    description: 'Perfect for game content'
  }
]

// Event handlers
const handleThumbnailSelected = async (thumbnail: YouTubeThumbnailDisplay) => {
  // Thumbnails are for preview/download only - no design creation
  console.log('Thumbnail selected for preview/download:', thumbnail)
}

const handleVideoAnalyzed = (videoInfo: YouTubeVideoInfo) => {
  currentVideoInfo.value = videoInfo
  console.log('Video analyzed:', videoInfo)
}

const handleThumbnailsGenerated = (thumbnails: YouTubeThumbnailDisplay[]) => {
  // Add to recent thumbnails
  recentThumbnails.value = [...thumbnails, ...recentThumbnails.value].slice(0, 8)
  console.log('Thumbnails generated:', thumbnails)
}

const handleError = (error: string) => {
  console.error('Plugin error:', error)
  // You could show a toast notification here
}

// Icon generation for featured styles
const getStyleIcon = (styleValue: string) => {
  const iconComponents = {
    modern: () => h('svg', {
      fill: 'none',
      stroke: 'currentColor',
      viewBox: '0 0 24 24'
    }, [
      h('path', {
        'stroke-linecap': 'round',
        'stroke-linejoin': 'round',
        'stroke-width': '2',
        d: 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'
      })
    ]),
    dramatic: () => h('svg', {
      fill: 'none',
      stroke: 'currentColor',
      viewBox: '0 0 24 24'
    }, [
      h('path', {
        'stroke-linecap': 'round',
        'stroke-linejoin': 'round',
        'stroke-width': '2',
        d: 'M13 10V3L4 14h7v7l9-11h-7z'
      })
    ]),
    colorful: () => h('svg', {
      fill: 'none',
      stroke: 'currentColor',
      viewBox: '0 0 24 24'
    }, [
      h('path', {
        'stroke-linecap': 'round',
        'stroke-linejoin': 'round',
        'stroke-width': '2',
        d: 'M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zM21 5a2 2 0 00-2-2h-4a2 2 0 00-2 2v12a4 4 0 004 4h4a2 2 0 002-2V5z'
      })
    ]),
    gaming: () => h('svg', {
      fill: 'none',
      stroke: 'currentColor',
      viewBox: '0 0 24 24'
    }, [
      h('path', {
        'stroke-linecap': 'round',
        'stroke-linejoin': 'round',
        'stroke-width': '2',
        d: 'M11 4a2 2 0 114 0v1a1 1 0 001 1h3a1 1 0 011 1v3a1 1 0 01-1 1h-1a2 2 0 100 4h1a1 1 0 011 1v3a1 1 0 01-1 1h-3a1 1 0 01-1-1v-1a2 2 0 10-4 0v1a1 1 0 01-1 1H7a1 1 0 01-1-1v-3a1 1 0 00-1-1H4a2 2 0 110-4h1a1 1 0 001-1V7a1 1 0 011-1h3a1 1 0 001-1V4z'
      })
    ])
  }
  
  return iconComponents[styleValue as keyof typeof iconComponents] || iconComponents.modern
}
</script>

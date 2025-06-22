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


</script>

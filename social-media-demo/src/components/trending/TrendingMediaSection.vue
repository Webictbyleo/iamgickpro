<template>
  <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
    <!-- Section Header -->
    <div class="bg-gradient-to-r from-purple-50 to-pink-50 px-6 py-4 border-b border-gray-200">
      <div class="flex items-center justify-between">
        <div class="flex items-center space-x-3">
          <div class="w-10 h-10 bg-purple-600 rounded-lg flex items-center justify-center">
            <span class="text-white font-semibold">🎥</span>
          </div>
          <div>
            <h3 class="text-lg font-semibold text-gray-900">Trending Media</h3>
            <p class="text-sm text-gray-600">Viral content you can recreate</p>
          </div>
        </div>
        <div class="flex items-center space-x-2">
          <div class="w-2 h-2 bg-red-500 rounded-full animate-pulse"></div>
          <span class="text-xs text-gray-500 font-medium">Live</span>
        </div>
      </div>
    </div>

    <!-- Filter Tabs -->
    <div class="px-6 py-3 bg-gray-50 border-b border-gray-200">
      <nav class="flex space-x-6">
        <button
          v-for="tab in mediaTabs"
          :key="tab.id"
          @click="activeTab = tab.id"
          :class="[
            'pb-2 px-1 border-b-2 font-medium text-sm transition-colors',
            activeTab === tab.id
              ? 'border-purple-500 text-purple-600'
              : 'border-transparent text-gray-500 hover:text-gray-700'
          ]"
        >
          {{ tab.icon }} {{ tab.label }}
        </button>
      </nav>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="flex items-center justify-center py-12">
      <LoadingSpinner size="md" color="primary" />
      <span class="ml-3 text-gray-600">Loading trending media...</span>
    </div>

    <!-- Media Grid -->
    <div v-else-if="filteredMedia.length > 0" class="p-6">
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <TrendingMediaCard
          v-for="media in filteredMedia"
          :key="media.id"
          :media="media"
          @recreate="handleRecreateMedia"
          @view-analytics="handleViewAnalytics"
        />
      </div>

      <!-- Load More Button -->
      <div v-if="hasMore" class="text-center mt-6">
        <button
          @click="loadMore"
          :disabled="loadingMore"
          class="px-6 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors disabled:opacity-50"
        >
          <span v-if="loadingMore">Loading...</span>
          <span v-else>Load More</span>
        </button>
      </div>
    </div>

    <!-- Empty State -->
    <div v-else class="text-center py-12">
      <div class="text-4xl mb-4">🎬</div>
      <h3 class="text-lg font-semibold text-gray-900 mb-2">No trending media found</h3>
      <p class="text-gray-600 mb-4">Try a different filter or check back later for new content.</p>
      <button
        @click="refreshMedia"
        class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors"
      >
        🔄 Refresh
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useWorkflowStore } from '@/stores/workflow'
import { useUiStore } from '@/stores/ui'
import TrendingMediaCard from '@/components/trending/TrendingMediaCard.vue'
import LoadingSpinner from '@/components/ui/LoadingSpinner.vue'
import type { TrendingMedia, ContentType } from '@/types'

const router = useRouter()
const workflowStore = useWorkflowStore()
const uiStore = useUiStore()

// State
const activeTab = ref('all')
const loading = ref(false)
const loadingMore = ref(false)
const hasMore = ref(true)
const page = ref(1)

// Tab configuration
const mediaTabs = [
  { id: 'all', label: 'All', icon: '🌟' },
  { id: 'video', label: 'Videos', icon: '🎥' },
  { id: 'image', label: 'Images', icon: '📸' },
  { id: 'carousel', label: 'Carousels', icon: '🎠' },
  { id: 'viral', label: 'Viral', icon: '🔥' }
]

// Mock data for trending media
const allMedia = ref<TrendingMedia[]>([
  {
    id: '1',
    title: 'Day in My Life as a Content Creator',
    description: 'Follow my daily routine and see how I create content that goes viral',
    type: 'video',
    platform: 'tiktok',
    author: '@creativejess',
    thumbnail: 'https://images.unsplash.com/photo-1611532736597-de2d4265fba3?w=400',
    views: 2500000,
    likes: 450000,
    shares: 85000,
    duration: 45,
    isViral: true,
    tags: ['lifestyle', 'creator', 'routine'],
    createdAt: '2025-06-23T10:00:00Z'
  },
  {
    id: '2',
    title: 'Quick Recipe: 5-Minute Breakfast',
    description: 'Healthy breakfast ideas that will change your morning routine',
    type: 'video',
    platform: 'instagram',
    author: '@healthyeats',
    thumbnail: 'https://images.unsplash.com/photo-1551782450-a2132b4ba21d?w=400',
    views: 1800000,
    likes: 320000,
    shares: 65000,
    duration: 30,
    isViral: true,
    tags: ['food', 'healthy', 'quick'],
    createdAt: '2025-06-22T14:30:00Z'
  },
  {
    id: '3',
    title: 'Before & After: Room Transformation',
    description: 'Transform any space on a budget with these simple tips',
    type: 'carousel',
    platform: 'instagram',
    author: '@homedecor_tips',
    thumbnail: 'https://images.unsplash.com/photo-1586023492125-27b2c045efd7?w=400',
    views: 950000,
    likes: 180000,
    shares: 35000,
    isViral: false,
    tags: ['decor', 'diy', 'budget'],
    createdAt: '2025-06-21T16:15:00Z'
  },
  {
    id: '4',
    title: 'Productivity Hacks That Actually Work',
    description: 'Science-backed methods to boost your productivity by 300%',
    type: 'video',
    platform: 'youtube',
    author: '@productivitypro',
    thumbnail: 'https://images.unsplash.com/photo-1611224923853-80b023f02d71?w=400',
    views: 3200000,
    likes: 580000,
    shares: 125000,
    duration: 180,
    isViral: true,
    tags: ['productivity', 'tips', 'work'],
    createdAt: '2025-06-20T09:00:00Z'
  },
  {
    id: '5',
    title: 'Street Fashion: Tokyo Style',
    description: 'Exploring the unique fashion trends taking over Tokyo streets',
    type: 'image',
    platform: 'instagram',
    author: '@fashionista_tokyo',
    thumbnail: 'https://images.unsplash.com/photo-1445205170230-053b83016050?w=400',
    views: 720000,
    likes: 95000,
    shares: 18000,
    isViral: false,
    tags: ['fashion', 'tokyo', 'street'],
    createdAt: '2025-06-19T12:45:00Z'
  },
  {
    id: '6',
    title: 'Mindfulness in 60 Seconds',
    description: 'Quick meditation technique for busy professionals',
    type: 'video',
    platform: 'tiktok',
    author: '@mindful_moments',
    thumbnail: 'https://images.unsplash.com/photo-1506905925346-21bda4d32df4?w=400',
    views: 1600000,
    likes: 280000,
    shares: 55000,
    duration: 60,
    isViral: true,
    tags: ['mindfulness', 'meditation', 'wellness'],
    createdAt: '2025-06-18T07:30:00Z'
  }
])

// Computed
const filteredMedia = computed(() => {
  let filtered = allMedia.value

  if (activeTab.value === 'viral') {
    filtered = filtered.filter(media => media.isViral)
  } else if (activeTab.value !== 'all') {
    filtered = filtered.filter(media => media.type === activeTab.value)
  }

  return filtered.slice(0, page.value * 6)
})

// Methods
const loadTrendingMedia = async () => {
  loading.value = true
  try {
    // Simulate API call
    await new Promise(resolve => setTimeout(resolve, 1000))
    // In real app, this would fetch from API
    hasMore.value = allMedia.value.length > page.value * 6
  } catch (error) {
    console.error('Failed to load trending media:', error)
    uiStore.addNotification({
      type: 'error',
      title: 'Error',
      message: 'Failed to load trending media'
    })
  } finally {
    loading.value = false
  }
}

const loadMore = async () => {
  loadingMore.value = true
  try {
    await new Promise(resolve => setTimeout(resolve, 500))
    page.value++
    hasMore.value = allMedia.value.length > page.value * 6
  } catch (error) {
    console.error('Failed to load more media:', error)
  } finally {
    loadingMore.value = false
  }
}

const refreshMedia = () => {
  page.value = 1
  loadTrendingMedia()
}

const handleRecreateMedia = (media: TrendingMedia) => {
  // Map media type to content type
  const getContentType = (mediaType: string): ContentType => {
    switch (mediaType) {
      case 'video': return 'video'
      case 'image': return 'image'
      case 'carousel': return 'carousel'
      case 'story': return 'story'
      default: return 'mixed'
    }
  }

  // Pre-populate workflow with media inspiration
  workflowStore.setContentPrompt({
    topic_id: 'inspired-' + media.id,
    platforms: [media.platform],
    content_types: [getContentType(media.type)],
    primary_type: getContentType(media.type),
    tone: 'conversational',
    style: 'engaging',
    length: 'medium',
    hashtags: true,
    mentions: false,
    call_to_action: true,
    visual_style: 'creative'
  })

  uiStore.addNotification({
    type: 'success',
    title: 'Inspiration Added! 🎨',
    message: `Starting workflow based on "${media.title}"`
  })

  // Navigate to workflow
  workflowStore.setCurrentStep('content')
  router.push('/workflow')
}

const handleViewAnalytics = (media: TrendingMedia) => {
  uiStore.addNotification({
    type: 'info',
    title: 'Analytics Preview',
    message: `Views: ${media.views.toLocaleString()}, Engagement: ${((media.likes + media.shares) / media.views * 100).toFixed(1)}%`
  })
}

// Lifecycle
onMounted(() => {
  loadTrendingMedia()
})
</script>

<style scoped>
.animate-pulse {
  animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}

@keyframes pulse {
  0%, 100% { opacity: 1; }
  50% { opacity: 0.5; }
}
</style>

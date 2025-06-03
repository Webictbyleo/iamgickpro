<template>
  <AppLayout>
    <div class="max-w-6xl mx-auto space-y-8">
      <!-- Development Notice -->
      <div v-if="isDevelopment" class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
        <div class="flex">
          <div class="flex-shrink-0">
            <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
              <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
            </svg>
          </div>
          <div class="ml-3">
            <h3 class="text-sm font-medium text-yellow-800">Development Mode</h3>
            <p class="mt-1 text-sm text-yellow-700">
              Mock video info and designs will be generated if the API is unavailable. This helps with frontend development and testing.
            </p>
          </div>
        </div>
      </div>

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

      <!-- Video URL Input -->
      <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-8">
        <div class="max-w-2xl mx-auto">
          <label class="block text-sm font-medium text-gray-700 mb-2">
            YouTube Video URL
          </label>
          <div class="flex space-x-4">
            <input
              v-model="videoUrl"
              type="url"
              placeholder="https://www.youtube.com/watch?v=dQw4w9WgXcQ"
              class="flex-1 px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
              :disabled="isProcessing"
            />
            <button
              @click="extractVideoInfo"
              :disabled="!videoUrl || isProcessing"
              class="px-6 py-3 bg-primary-600 text-white rounded-lg hover:bg-primary-700 disabled:opacity-50 disabled:cursor-not-allowed flex items-center space-x-2"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
              </svg>
              <span>Analyze Video</span>
            </button>
          </div>
          
          <!-- Error Message -->
          <div v-if="errorMessage" class="mt-4 p-3 bg-red-50 border border-red-200 rounded-md">
            <p class="text-sm text-red-700">{{ errorMessage }}</p>
          </div>
          
          <!-- Video Preview -->
          <div v-if="videoInfo" class="mt-6 p-4 bg-gray-50 rounded-lg">
            <div class="flex items-start space-x-4">
              <div class="relative">
                <img
                  :src="videoInfo.thumbnailUrl"
                  :alt="videoInfo.title"
                  class="w-24 h-18 object-cover rounded"
                />
                <div v-if="isDevelopment && errorMessage.includes('mock video info')" class="absolute -top-1 -right-1 bg-yellow-400 text-yellow-900 text-xs px-1 rounded">
                  MOCK
                </div>
              </div>
              <div class="flex-1">
                <h3 class="font-medium text-gray-900">{{ videoInfo.title }}</h3>
                <p class="text-sm text-gray-600 mt-1">{{ videoInfo.channelTitle }}</p>
                <p class="text-sm text-gray-500 mt-1">
                  Duration: {{ formatDuration(videoInfo.duration) }}
                </p>
                <div v-if="isDevelopment && errorMessage.includes('mock video info')" class="mt-2">
                  <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                      <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                    </svg>
                    Mock Data
                  </span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Thumbnail Generation Options -->
      <div v-if="videoInfo" class="bg-white rounded-xl shadow-sm border border-gray-200 p-8">
        <h2 class="text-xl font-semibold text-gray-900 mb-6">Thumbnail Generation Options</h2>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
          <!-- Thumbnail Styles -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-3">
              Thumbnail Styles
            </label>
            <div class="space-y-2">
              <label v-for="style in thumbnailStyles" :key="style.value" class="flex items-center">
                <input
                  v-model="selectedThumbnailStyles"
                  :value="style.value"
                  type="checkbox"
                  class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded"
                />
                <span class="ml-2 text-sm text-gray-700">{{ style.label }}</span>
              </label>
            </div>
          </div>

          <!-- Thumbnail Options -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-3">
              Generation Settings
            </label>
            <div class="space-y-4">
              <div>
                <label class="block text-sm text-gray-600 mb-1">Number of Thumbnails</label>
                <select
                  v-model="maxThumbnails"
                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-primary-500 focus:border-primary-500"
                >
                  <option :value="3">3 thumbnails</option>
                  <option :value="5">5 thumbnails</option>
                  <option :value="8">8 thumbnails</option>
                  <option :value="12">12 thumbnails</option>
                </select>
              </div>

              <div>
                <label class="block text-sm text-gray-600 mb-1">Thumbnail Size</label>
                <select
                  v-model="thumbnailSize"
                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-primary-500 focus:border-primary-500"
                >
                  <option value="1280x720">YouTube Standard (1280×720)</option>
                  <option value="1920x1080">HD (1920×1080)</option>
                  <option value="2560x1440">2K (2560×1440)</option>
                </select>
              </div>
              
              <label class="flex items-center">
                <input
                  v-model="includeTitle"
                  type="checkbox"
                  class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded"
                />
                <span class="ml-2 text-sm text-gray-700">Include video title overlay</span>
              </label>
              
              <label class="flex items-center">
                <input
                  v-model="extractKeyFrames"
                  type="checkbox"
                  class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded"
                />
                <span class="ml-2 text-sm text-gray-700">Extract key frames for backgrounds</span>
              </label>

              <label class="flex items-center">
                <input
                  v-model="includeChannelBranding"
                  type="checkbox"
                  class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded"
                />
                <span class="ml-2 text-sm text-gray-700">Include channel branding elements</span>
              </label>
            </div>
          </div>
        </div>

        <!-- Custom Thumbnail Prompt -->
        <div class="mt-6">
          <label class="block text-sm font-medium text-gray-700 mb-2">
            Custom Instructions (Optional)
          </label>
          <textarea
            v-model="customPrompt"
            rows="3"
            placeholder="e.g., 'Make it more dramatic', 'Use bright colors', 'Focus on the main subject'..."
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-primary-500 focus:border-primary-500"
          />
        </div>

        <!-- Generate Button -->
        <div class="mt-8 text-center">
          <button
            @click="generateThumbnails"
            :disabled="isProcessing || selectedThumbnailStyles.length === 0"
            class="px-8 py-3 bg-gradient-to-r from-red-600 to-pink-600 text-white rounded-lg hover:from-red-700 hover:to-pink-700 disabled:opacity-50 disabled:cursor-not-allowed font-medium flex items-center mx-auto space-x-2"
          >
            <svg v-if="isProcessing" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
            </svg>
            <span v-if="isProcessing">Generating Thumbnails...</span>
            <span v-else>Generate YouTube Thumbnails</span>
          </button>
        </div>
      </div>

      <!-- Processing Status -->
      <div v-if="currentJob" class="bg-white rounded-xl shadow-sm border border-gray-200 p-8">
        <div class="space-y-6">
          <div class="text-center">
            <h3 class="text-lg font-medium text-gray-900">Creating Your Thumbnails</h3>
            <p class="text-gray-600 mt-1">AI is analyzing your video and generating custom thumbnail designs</p>
          </div>

          <!-- Progress Bar -->
          <div class="w-full bg-gray-200 rounded-full h-2">
            <div 
              class="bg-gradient-to-r from-red-600 to-pink-600 h-2 rounded-full transition-all duration-500"
              :style="{ width: `${currentJob.progress}%` }"
            ></div>
          </div>

          <div class="text-center">
            <p class="text-sm text-gray-600">{{ getProgressMessage() }}</p>
            <p class="text-xs text-gray-500 mt-1">{{ currentJob.progress }}% complete</p>
          </div>
        </div>
      </div>

      <!-- Generated Thumbnails -->
      <div v-if="generatedThumbnails.length > 0" class="bg-white rounded-xl shadow-sm border border-gray-200 p-8">
        <div class="flex items-center justify-between mb-6">
          <h2 class="text-xl font-semibold text-gray-900">Generated Thumbnails</h2>
          <div v-if="isDevelopment && errorMessage.includes('DEV MODE')" class="text-xs bg-yellow-100 text-yellow-800 px-2 py-1 rounded flex items-center space-x-1">
            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
            </svg>
            <span>Mock Data</span>
          </div>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          <div
            v-for="thumbnail in generatedThumbnails"
            :key="thumbnail.id"
            class="group cursor-pointer bg-white rounded-lg border border-gray-200 hover:border-red-300 hover:shadow-lg transition-all duration-200"
            @click="handleThumbnailSelected(thumbnail)"
          >
            <!-- Thumbnail Preview -->
            <div class="aspect-video bg-gray-100 rounded-t-lg overflow-hidden">
              <img
                :src="thumbnail.thumbnailUrl"
                :alt="thumbnail.title"
                class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-200"
              />
            </div>

            <!-- Content -->
            <div class="p-4">
              <div class="flex items-start justify-between mb-2">
                <h3 class="font-medium text-gray-900 group-hover:text-red-600 transition-colors">
                  {{ thumbnail.title }}
                </h3>
                <div class="flex items-center space-x-1 text-xs text-gray-500">
                  <span class="bg-red-100 text-red-700 px-2 py-1 rounded">
                    {{ thumbnail.confidence }}% match
                  </span>
                </div>
              </div>

              <p class="text-sm text-gray-600 mb-3 line-clamp-2">
                {{ thumbnail.description }}
              </p>

              <!-- Style Tags -->
              <div class="flex flex-wrap gap-1 mb-3">
                <span
                  v-for="tag in thumbnail.tags.slice(0, 3)"
                  :key="tag"
                  class="text-xs bg-gray-100 text-gray-600 px-2 py-1 rounded"
                >
                  {{ tag }}
                </span>
                <span
                  v-if="thumbnail.tags.length > 3"
                  class="text-xs text-gray-500"
                >
                  +{{ thumbnail.tags.length - 3 }} more
                </span>
              </div>

              <!-- Dimensions and Style -->
              <div class="flex items-center justify-between text-xs text-gray-500">
                <span>{{ thumbnail.width }} × {{ thumbnail.height }}px</span>
                <span class="capitalize">{{ thumbnail.category }} Style</span>
              </div>
            </div>

            <!-- Action Button -->
            <div class="p-4 pt-0">
              <button class="w-full bg-red-600 text-white py-2 px-4 rounded-md hover:bg-red-700 transition-colors text-sm font-medium">
                Edit This Thumbnail
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import { ref, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import AppLayout from '@/components/layout/AppLayout.vue'
import { videoAnalysisAPI, designAPI } from '@/services/api'
import { DesignType } from '@/types'
import type { 
  YouTubeVideoInfo, 
  DesignSuggestion, 
  GenerateDesignFromVideoResponse,
  GenerateDesignFromVideoRequest
} from '@/types'

const router = useRouter()

const videoUrl = ref('')
const videoInfo = ref<YouTubeVideoInfo | null>(null)
const isProcessing = ref(false)
const errorMessage = ref('')
const currentJob = ref<GenerateDesignFromVideoResponse | null>(null)
const generatedThumbnails = ref<DesignSuggestion[]>([])

// Thumbnail-specific reactive variables
const selectedThumbnailStyles = ref<string[]>(['modern'])
const maxThumbnails = ref(5)
const thumbnailSize = ref('1280x720')
const includeTitle = ref(true)
const extractKeyFrames = ref(true)
const includeChannelBranding = ref(false)
const customPrompt = ref('')

// Development flag - set to false in production
const isDevelopment = import.meta.env.DEV

// Track polling interval for cleanup
let pollInterval: NodeJS.Timeout | null = null

// Thumbnail style options
const thumbnailStyles = [
  { value: 'modern', label: 'Modern & Clean' },
  { value: 'dramatic', label: 'Dramatic & Bold' },
  { value: 'colorful', label: 'Vibrant & Colorful' },
  { value: 'minimal', label: 'Minimal & Professional' },
  { value: 'gaming', label: 'Gaming Style' },
  { value: 'educational', label: 'Educational & Informative' },
  { value: 'lifestyle', label: 'Lifestyle & Vlog' },
  { value: 'tech', label: 'Tech & Reviews' },
] as const

// Mock video info generator for development
const generateMockVideoInfo = (url: string): YouTubeVideoInfo => {
  // Extract video ID from URL for more realistic mock
  const videoIdMatch = url.match(/(?:youtube\.com\/watch\?v=|youtu\.be\/)([^&\n?#]+)/)
  const videoId = videoIdMatch ? videoIdMatch[1] : 'dQw4w9WgXcQ'
  
  return {
    videoId,
    title: 'Amazing Creative Tutorial - Learn Design Skills',
    description: 'This is a comprehensive tutorial about creative design techniques and modern approaches to visual storytelling.',
    thumbnailUrl: `https://picsum.photos/480/360?random=${Date.now()}`,
    duration: 654, // 10:54 minutes
    channelTitle: 'Creative Design Studio',
    publishedAt: '2024-01-15T10:30:00Z',
    viewCount: 125000,
    tags: ['design', 'tutorial', 'creative', 'art', 'visual', 'modern']
  }
}

// Mock thumbnail generator for development
const generateMockThumbnails = (videoInfo: YouTubeVideoInfo): DesignSuggestion[] => {
  const [width, height] = thumbnailSize.value.split('x').map(Number)
  
  const mockThumbnails: DesignSuggestion[] = [
    {
      id: `mock-thumb-${Date.now()}-1`,
      title: `${videoInfo.title} - Modern Style`,
      description: `Clean and modern thumbnail design with bold typography and engaging visuals inspired by "${videoInfo.title}".`,
      category: 'modern',
      thumbnailUrl: `https://picsum.photos/${width}/${height}?random=1`,
      designData: {
        version: '1.0',
        canvas: { width, height, backgroundColor: '#ffffff' },
        layers: [
          {
            id: 'bg-1', type: 'shape' as const, name: 'Background',
            visible: true, locked: false, opacity: 1,
            x: 0, y: 0, width, height, rotation: 0, scaleX: 1, scaleY: 1, zIndex: 0,
            properties: { fill: '#2563eb', stroke: 'none' }
          },
          {
            id: 'title-1', type: 'text' as const, name: 'Title',
            visible: true, locked: false, opacity: 1,
            x: 50, y: height - 150, width: width - 100, height: 100,
            rotation: 0, scaleX: 1, scaleY: 1, zIndex: 1,
            properties: {
              text: videoInfo.title.slice(0, 50) + (videoInfo.title.length > 50 ? '...' : ''),
              fontSize: Math.floor(width / 25), fontFamily: 'Arial', fontWeight: 'bold',
              color: '#ffffff', textAlign: 'center'
            }
          }
        ]
      },
      width, height, confidence: 88,
      sourceFrames: [0, 3, 7], tags: ['modern', 'clean', 'professional', ...videoInfo.tags.slice(0, 2)]
    },
    {
      id: `mock-thumb-${Date.now()}-2`,
      title: `${videoInfo.title} - Dramatic Style`,
      description: `High-impact dramatic thumbnail with bold colors and attention-grabbing elements.`,
      category: 'dramatic',
      thumbnailUrl: `https://picsum.photos/${width}/${height}?random=2`,
      designData: {
        version: '1.0',
        canvas: { width, height, backgroundColor: '#000000' },
        layers: [
          {
            id: 'bg-2', type: 'shape' as const, name: 'Background',
            visible: true, locked: false, opacity: 1,
            x: 0, y: 0, width, height, rotation: 0, scaleX: 1, scaleY: 1, zIndex: 0,
            properties: { fill: '#dc2626', stroke: 'none' }
          },
          {
            id: 'title-2', type: 'text' as const, name: 'Title',
            visible: true, locked: false, opacity: 1,
            x: 50, y: height / 2 - 50, width: width - 100, height: 100,
            rotation: 0, scaleX: 1, scaleY: 1, zIndex: 1,
            properties: {
              text: videoInfo.title.slice(0, 40) + (videoInfo.title.length > 40 ? '...' : ''),
              fontSize: Math.floor(width / 20), fontFamily: 'Arial', fontWeight: 'bold',
              color: '#ffffff', textAlign: 'center'
            }
          }
        ]
      },
      width, height, confidence: 92,
      sourceFrames: [1, 5, 9], tags: ['dramatic', 'bold', 'eye-catching', ...videoInfo.tags.slice(0, 2)]
    },
    {
      id: `mock-thumb-${Date.now()}-3`,
      title: `${videoInfo.title} - Colorful Style`,
      description: `Vibrant and colorful thumbnail design that stands out in YouTube feeds.`,
      category: 'colorful',
      thumbnailUrl: `https://picsum.photos/${width}/${height}?random=3`,
      designData: {
        version: '1.0',
        canvas: { width, height, backgroundColor: '#f59e0b' },
        layers: [
          {
            id: 'bg-3', type: 'shape' as const, name: 'Background',
            visible: true, locked: false, opacity: 1,
            x: 0, y: 0, width, height, rotation: 0, scaleX: 1, scaleY: 1, zIndex: 0,
            properties: { fill: 'linear-gradient(45deg, #f59e0b, #ef4444, #8b5cf6)', stroke: 'none' }
          },
          {
            id: 'title-3', type: 'text' as const, name: 'Title',
            visible: true, locked: false, opacity: 1,
            x: 50, y: 50, width: width - 100, height: 80,
            rotation: 0, scaleX: 1, scaleY: 1, zIndex: 1,
            properties: {
              text: videoInfo.title.slice(0, 45) + (videoInfo.title.length > 45 ? '...' : ''),
              fontSize: Math.floor(width / 22), fontFamily: 'Arial', fontWeight: 'bold',
              color: '#ffffff', textAlign: 'center'
            }
          }
        ]
      },
      width, height, confidence: 85,
      sourceFrames: [2, 6, 11], tags: ['colorful', 'vibrant', 'energetic', ...videoInfo.tags.slice(0, 2)]
    }
  ]

  // Filter based on selected thumbnail styles
  return mockThumbnails.filter(thumbnail => 
    selectedThumbnailStyles.value.includes(thumbnail.category)
  ).slice(0, maxThumbnails.value)
}

const extractVideoInfo = async (): Promise<void> => {
  if (!videoUrl.value) return
  
  try {
    isProcessing.value = true
    errorMessage.value = ''
    
    const response = await videoAnalysisAPI.extractVideoInfo({
      videoUrl: videoUrl.value
    })
    
    if (response.data?.data) {
      videoInfo.value = response.data.data
    }
  } catch (error: unknown) {
    console.error('Failed to extract video info:', error)
    
    if (isDevelopment) {
      console.warn('API failed, using mock video info for development')
      
      // Generate mock video info so user can proceed with testing
      videoInfo.value = generateMockVideoInfo(videoUrl.value)
      errorMessage.value = '⚠️ DEV MODE: API unavailable, using mock video info'
    } else {
      const errorMsg = error && typeof error === 'object' && 'response' in error
        ? (error as any).response?.data?.message
        : 'Failed to analyze video. Please check the URL and try again.'
      errorMessage.value = errorMsg
    }
  } finally {
    isProcessing.value = false
  }
}

const generateThumbnails = async (): Promise<void> => {
  if (!videoInfo.value || selectedThumbnailStyles.value.length === 0) return
  
  try {
    isProcessing.value = true
    errorMessage.value = ''
    
    const [width, height] = thumbnailSize.value.split('x').map(Number)
    
    const requestData: GenerateDesignFromVideoRequest = {
      videoUrl: videoUrl.value,
      designTypes: [DesignType.THUMBNAIL],
      maxDesigns: maxThumbnails.value,
      includeTranscript: false, // Not needed for thumbnails
      extractFrames: extractKeyFrames.value,
      customPrompt: `Generate YouTube thumbnail in ${selectedThumbnailStyles.value.join(', ')} style(s). Size: ${width}x${height}. ${includeTitle.value ? 'Include video title overlay.' : ''} ${includeChannelBranding.value ? 'Include channel branding elements.' : ''} ${customPrompt.value}`
    }
    
    const response = await videoAnalysisAPI.generateDesignsFromVideo(requestData)
    
    if (response.data?.data?.job) {
      currentJob.value = response.data.data.job
      pollJobStatus(currentJob.value.jobId)
    }
  } catch (error: unknown) {
    console.error('Failed to start thumbnail generation:', error)
    
    if (isDevelopment && videoInfo.value) {
      console.warn('Thumbnail generation API failed, using mock thumbnails for development')
      
      setTimeout(() => {
        const mockThumbnails = generateMockThumbnails(videoInfo.value!)
        generatedThumbnails.value = mockThumbnails
        isProcessing.value = false
        
        if (errorMessage.value.includes('mock video info')) {
          errorMessage.value = '⚠️ DEV MODE: Using mock video info and thumbnails (API unavailable)'
        } else {
          errorMessage.value = '⚠️ DEV MODE: Thumbnail generation API unavailable, showing mock thumbnails'
        }
      }, 2000)
    } else {
      const errorMsg = error && typeof error === 'object' && 'response' in error
        ? (error as any).response?.data?.message
        : 'Failed to start thumbnail generation. Please try again.'
      errorMessage.value = errorMsg
      isProcessing.value = false
    }
  }
}

const pollJobStatus = async (jobId: string): Promise<void> => {
  // Clear any existing interval
  if (pollInterval) {
    clearInterval(pollInterval)
  }

  pollInterval = setInterval(async () => {
    try {
      const response = await videoAnalysisAPI.getAnalysisJob(jobId)
      
      if (response.data?.data?.job) {
        currentJob.value = response.data.data.job
        
        if (currentJob.value.status === 'completed') {
          if (pollInterval) {
            clearInterval(pollInterval)
            pollInterval = null
          }
          handleJobCompleted(currentJob.value)
        } else if (currentJob.value.status === 'failed') {
          if (pollInterval) {
            clearInterval(pollInterval)
            pollInterval = null
          }
          handleJobFailed(currentJob.value)
        }
      }
    } catch (error: unknown) {
      console.error('Failed to poll job status:', error)
      if (pollInterval) {
        clearInterval(pollInterval)
        pollInterval = null
      }
      isProcessing.value = false
    }
  }, 2000)
}

const handleJobCompleted = (job: GenerateDesignFromVideoResponse): void => {
  isProcessing.value = false
  if (job.result?.suggestedDesigns) {
    generatedThumbnails.value = job.result.suggestedDesigns
  }
}

const handleJobFailed = (job: GenerateDesignFromVideoResponse): void => {
  isProcessing.value = false
  
  if (isDevelopment && videoInfo.value) {
    console.warn('API failed, using mock thumbnails for development')
    
    const mockThumbnails = generateMockThumbnails(videoInfo.value)
    generatedThumbnails.value = mockThumbnails
    
    errorMessage.value = '⚠️ DEV MODE: API failed, showing mock thumbnails for development'
  } else {
    errorMessage.value = job.errorMessage || 'Thumbnail generation failed. Please try again.'
  }
  
  console.error('Job failed:', job.errorMessage)
}

const handleThumbnailSelected = async (thumbnail: DesignSuggestion): Promise<void> => {
  try {
    const response = await designAPI.createDesign({
      name: thumbnail.title,
      width: thumbnail.width,
      height: thumbnail.height,
      data: thumbnail.designData
    })
    
    if (response.data?.data?.id) {
      await router.push(`/editor/${response.data.data.id}`)
    }
  } catch (error: unknown) {
    console.error('Failed to create thumbnail design:', error)
    const errorMsg = error && typeof error === 'object' && 'response' in error
      ? (error as any).response?.data?.message
      : 'Failed to create thumbnail design. Please try again.'
    errorMessage.value = errorMsg
  }
}

const formatDuration = (seconds: number): string => {
  const minutes = Math.floor(seconds / 60)
  const remainingSeconds = seconds % 60
  return `${minutes}:${remainingSeconds.toString().padStart(2, '0')}`
}

const getProgressMessage = (): string => {
  if (!currentJob.value) return ''
  
  const progress = currentJob.value.progress || 0
  if (progress < 25) return 'Extracting key frames and analyzing video content...'
  if (progress < 50) return 'Identifying best moments and visual elements for thumbnails...'
  if (progress < 75) return 'Generating thumbnail designs with AI-powered styling...'
  if (progress < 100) return 'Finalizing thumbnail layouts and preparing previews...'
  return 'YouTube thumbnail generation complete! Your designs are ready.'
}

// Cleanup on component unmount
onUnmounted(() => {
  if (pollInterval) {
    clearInterval(pollInterval)
    pollInterval = null
  }
})
</script>

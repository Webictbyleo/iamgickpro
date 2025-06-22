<template>
  <div class="youtube-thumbnail-plugin">
    <!-- Plugin Header -->
    <div class="mb-6">
      <div class="flex items-center space-x-3 mb-2">
        <div class="flex-shrink-0">
          <svg class="w-8 h-8 text-red-600" fill="currentColor" viewBox="0 0 24 24">
            <path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/>
          </svg>
        </div>
        <div>
          <h3 class="text-lg font-semibold text-gray-900">YouTube Thumbnail Generator</h3>
          <p class="text-sm text-gray-600">Create AI-powered thumbnails from YouTube videos</p>
        </div>
      </div>
    </div>

    <!-- Video URL Input -->
    <div class="bg-white rounded-lg border border-gray-200 p-6 mb-6">
      <label class="block text-sm font-medium text-gray-700 mb-2">
        YouTube Video URL
      </label>
      <div class="flex space-x-3">
        <input
          v-model="videoUrl"
          type="url"
          placeholder="https://www.youtube.com/watch?v=dQw4w9WgXcQ"
          class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500 text-sm"
          :disabled="isProcessing"
          @keyup.enter="extractVideoInfo"
        />
        <button
          @click="extractVideoInfo"
          :disabled="!videoUrl || isProcessing"
          class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 disabled:opacity-50 disabled:cursor-not-allowed flex items-center space-x-2 text-sm font-medium"
        >
          <svg v-if="isProcessing && !videoInfo" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="m4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
          </svg>
          <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
          </svg>
          <span>{{ isProcessing && !videoInfo ? 'Analyzing...' : 'Analyze' }}</span>
        </button>
      </div>
      
      <!-- Error Message -->
      <div v-if="errorMessage" class="mt-3 p-3 bg-red-50 border border-red-200 rounded-md">
        <p class="text-sm text-red-700">{{ errorMessage }}</p>
      </div>
      
      <!-- Video Preview -->
      <div v-if="videoInfo" class="mt-4 p-4 bg-gray-50 rounded-lg">
        <div class="flex items-start space-x-4">
          <div class="relative">
            <img
              :src="videoInfo.thumbnailUrl"
              :alt="videoInfo.title"
              class="w-20 h-15 object-cover rounded"
            />
          </div>
          <div class="flex-1 min-w-0">
            <h4 class="font-medium text-gray-900 truncate">{{ videoInfo.title }}</h4>
            <p class="text-sm text-gray-600 mt-1">{{ videoInfo.channelTitle }}</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Generation Options -->
    <div v-if="videoInfo" class="bg-white rounded-lg border border-gray-200 p-6 mb-6">
      <h4 class="text-md font-semibold text-gray-900 mb-4">Generation Options</h4>
      
      <!-- AI Generation Info -->
      <div class="mb-4 p-3 bg-blue-50 border border-blue-200 rounded-md">
        <div class="flex items-center space-x-2">
          <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
          <p class="text-sm text-blue-700">
            <strong>AI-Powered Generation:</strong> 
            Uses Google Imagen 4 Ultra (Replicate) or OpenAI GPT-4 Vision for high-quality thumbnail creation.
          </p>
        </div>
      </div>
      
      <div class="space-y-4">
        <!-- Thumbnail Style Selection -->
        <div>
          <Listbox v-model="selectedThumbnailStyle">
            <ListboxLabel class="block text-sm font-medium text-gray-700 mb-2">
              Thumbnail Style
            </ListboxLabel>
            <div class="relative">
              <ListboxButton class="relative w-full cursor-pointer rounded-lg bg-white py-2 pl-3 pr-10 text-left border border-gray-300 focus:border-red-500 focus:outline-none focus:ring-2 focus:ring-red-200 text-sm">
                <span class="flex items-center">
                  <div class="w-5 h-5 rounded bg-red-100 text-red-600 flex items-center justify-center mr-2">
                    <component :is="getStyleIcon(selectedThumbnailStyle)" class="w-3 h-3" />
                  </div>
                  <span class="block truncate font-medium">
                    {{ thumbnailStyles.find(s => s.value === selectedThumbnailStyle)?.label }}
                  </span>
                </span>
                <span class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-2">
                  <ChevronUpDownIcon class="h-4 w-4 text-gray-400" aria-hidden="true" />
                </span>
              </ListboxButton>

              <transition
                leave-active-class="transition duration-100 ease-in"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
              >
                <ListboxOptions class="absolute z-10 mt-1 max-h-60 w-full overflow-auto rounded-lg bg-white py-1 shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none text-sm">
                  <ListboxOption
                    v-for="style in thumbnailStyles"
                    :key="style.value"
                    :value="style.value"
                    v-slot="{ active, selected }"
                  >
                    <li
                      :class="[
                        active ? 'bg-red-50 text-red-900' : 'text-gray-900',
                        'relative cursor-pointer select-none py-2 px-3'
                      ]"
                    >
                      <div class="flex items-center">
                        <div
                          :class="[
                            'w-6 h-6 rounded flex items-center justify-center mr-3',
                            selected || active
                              ? 'bg-red-100 text-red-600'
                              : 'bg-gray-100 text-gray-400'
                          ]"
                        >
                          <component :is="getStyleIcon(style.value)" class="w-4 h-4" />
                        </div>
                        <div class="flex-1">
                          <span
                            :class="[
                              selected ? 'font-semibold' : 'font-medium',
                              'block'
                            ]"
                          >
                            {{ style.label }}
                          </span>
                          <span
                            :class="[
                              active ? 'text-red-700' : 'text-gray-500',
                              'block text-xs mt-0.5'
                            ]"
                          >
                            {{ style.description }}
                          </span>
                        </div>
                        <span
                          v-if="selected"
                          :class="[
                            active ? 'text-red-600' : 'text-red-500',
                            'absolute inset-y-0 right-0 flex items-center pr-3'
                          ]"
                        >
                          <CheckIcon class="h-4 w-4" aria-hidden="true" />
                        </span>
                      </div>
                    </li>
                  </ListboxOption>
                </ListboxOptions>
              </transition>
            </div>
          </Listbox>
        </div>

        <!-- Number of Thumbnails -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            Number of Thumbnails ({{ maxThumbnails }})
          </label>
          <input
            v-model.number="maxThumbnails"
            type="range"
            min="1"
            max="10"
            class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer slider"
          />
          <div class="flex justify-between text-xs text-gray-500 mt-1">
            <span>1</span>
            <span>10</span>
          </div>
        </div>

        <!-- Custom Prompt -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            Custom Prompt (Optional)
          </label>
          <textarea
            v-model="customPrompt"
            rows="2"
            placeholder="Add specific instructions for thumbnail generation..."
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500 text-sm"
            :disabled="isProcessing"
          ></textarea>
        </div>
      </div>

      <!-- Generate Button -->
      <div class="mt-6">
        <button
          @click="generateThumbnails"
          :disabled="isProcessing"
          class="w-full px-4 py-3 bg-red-600 text-white rounded-lg hover:bg-red-700 disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center space-x-2 font-medium"
        >
          <svg v-if="isProcessing && currentJob" class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="m4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
          </svg>
          <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
          </svg>
          <span>{{ isProcessing && currentJob ? 'Generating Thumbnails...' : 'Generate Thumbnails' }}</span>
        </button>
      </div>
    </div>

    <!-- Progress Indicator -->
    <div v-if="isProcessing && currentJob" class="bg-white rounded-lg border border-gray-200 p-6 mb-6">
      <div class="flex items-center space-x-3 mb-3">
        <div class="flex-shrink-0">
          <svg class="w-5 h-5 text-blue-500 animate-spin" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="m4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
          </svg>
        </div>
        <div class="flex-1">
          <h4 class="text-sm font-medium text-gray-900">
            {{ currentJob.method === 'replicate' ? 'Generating with AI (Replicate)' : 'Generating with AI (OpenAI)' }}
          </h4>
          <p class="text-sm text-gray-600">{{ getProgressMessage() }}</p>
        </div>
      </div>
      
      <!-- Extended timeout warning for Replicate -->
      <div v-if="currentJob.method === 'replicate'" class="mb-3 p-3 bg-amber-50 border border-amber-200 rounded-md">
        <div class="flex items-center space-x-2">
          <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.864-.833-2.634 0L4.232 15.5c-.77.833.192 2.5 1.732 2.5z" />
          </svg>
          <p class="text-sm text-amber-700">
            <strong>High-quality AI generation in progress.</strong> 
            This may take 3-8 minutes for {{ maxThumbnails }} thumbnails using Google Imagen 4 Ultra.
          </p>
        </div>
      </div>
      
      <div class="w-full bg-gray-200 rounded-full h-2">
        <div
          class="bg-blue-600 h-2 rounded-full transition-all duration-300"
          :style="{ width: `${currentJob.progress || 0}%` }"
        ></div>
      </div>
      <div class="flex justify-between text-xs text-gray-500 mt-1">
        <span>{{ currentJob.progress || 0 }}% Complete</span>
        <span>{{ currentJob.status || 'processing' }}</span>
      </div>
      
      <!-- Progress details for Replicate -->
      <div v-if="currentJob.method === 'replicate' && currentJob.currentVariation" class="mt-2 text-xs text-gray-600">
        Processing variation {{ currentJob.currentVariation }} of {{ maxThumbnails }}
      </div>
    </div>

    <!-- Recent Thumbnails Section -->
    <div class="bg-white rounded-lg border border-gray-200 p-6 mb-6">
      <div class="flex items-center justify-between mb-4">
        <h4 class="text-md font-semibold text-gray-900">
          Recent Thumbnails
        </h4>
        <button
          @click="toggleRecentThumbnails"
          :disabled="loadingRecentThumbnails"
          class="px-3 py-2 text-sm bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors flex items-center space-x-2"
        >
          <svg v-if="loadingRecentThumbnails" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="m4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
          </svg>
          <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
          <span>{{ showRecentThumbnails ? 'Hide' : 'Show' }} Recent</span>
        </button>
      </div>
      
      <!-- Recent Thumbnails Grid -->
      <div v-if="showRecentThumbnails" class="space-y-4">
        <div v-if="recentThumbnails.length === 0 && !loadingRecentThumbnails" class="text-center py-8">
          <svg class="w-12 h-12 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
          </svg>
          <p class="text-gray-500">No recent thumbnails found</p>
          <p class="text-sm text-gray-400 mt-1">Generate some thumbnails to see them here</p>
        </div>
        
        <div v-else class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
          <div
            v-for="thumbnail in recentThumbnails"
            :key="thumbnail.id"
            class="group relative bg-gray-50 rounded-lg overflow-hidden border border-gray-200 hover:shadow-lg transition-shadow"
          >
            <div class="aspect-w-16 aspect-h-9">
              <img
                :src="thumbnail.preview_url"
                :alt="thumbnail.title"
                class="w-full h-full object-cover"
              />
            </div>
            <div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-40 transition-opacity flex items-center justify-center">
              <div class="opacity-0 group-hover:opacity-100 transition-opacity flex space-x-2">
                <a
                  :href="thumbnail.image_url"
                  target="_blank"
                  class="px-3 py-2 bg-white text-gray-900 rounded-md text-sm font-medium hover:bg-gray-100 transition-colors"
                >
                  View Full
                </a>
              </div>
            </div>
            <div class="absolute bottom-2 left-2 right-2">
              <div class="bg-black bg-opacity-75 text-white text-xs px-2 py-1 rounded">
                <div class="font-medium truncate">{{ thumbnail.video_title || thumbnail.video_id }}</div>
                <div class="flex items-center justify-between mt-1">
                  <span class="text-gray-300">{{ thumbnail.generation_method }}</span>
                  <span class="text-gray-300">{{ new Date(thumbnail.created_at).toLocaleDateString() }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Generated Thumbnails -->
    <div v-if="generatedThumbnails.length > 0" class="bg-white rounded-lg border border-gray-200 p-6">
      <h4 class="text-md font-semibold text-gray-900 mb-4">
        Generated Thumbnails ({{ generatedThumbnails.length }})
      </h4>
      
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <div
          v-for="thumbnail in generatedThumbnails"
          :key="thumbnail.id"
          class="group relative bg-gray-50 rounded-lg overflow-hidden border border-gray-200 hover:shadow-lg transition-shadow"
        >
          <div class="aspect-video">
            <img
              :src="thumbnail.previewUrl"
              :alt="thumbnail.title"
              class="w-full h-full object-cover"
            />
          </div>
          
          <div class="p-3">
            <h5 class="font-medium text-gray-900 text-sm truncate">{{ thumbnail.title }}</h5>
            <p class="text-xs text-gray-600 mt-1">{{ thumbnail.style }} style thumbnail</p>
            
            <div class="flex items-center justify-between mt-2 mb-3">
              <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                {{ thumbnail.generationMethod === 'replicate' ? 'Imagen 4 Ultra' : 'GPT-4 Vision' }}
              </span>
              <span class="text-xs text-gray-500">{{ thumbnail.style }}</span>
            </div>

            <!-- Action Buttons -->
            <div class="flex space-x-2">
              <button
                @click="previewThumbnail(thumbnail)"
                class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-700 py-2 px-3 rounded-md text-xs font-medium transition-colors flex items-center justify-center space-x-1"
              >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                </svg>
                <span>Preview</span>
              </button>
              <button
                @click="downloadThumbnail(thumbnail)"
                class="flex-1 bg-blue-600 hover:bg-blue-700 text-white py-2 px-3 rounded-md text-xs font-medium transition-colors flex items-center justify-center space-x-1"
              >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <span>Download</span>
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, h, onUnmounted } from 'vue'
import {
  Listbox,
  ListboxButton,
  ListboxLabel,
  ListboxOption,
  ListboxOptions,
} from '@headlessui/vue'
import { ChevronUpDownIcon, CheckIcon } from '@heroicons/vue/24/outline'
import { pluginAPI } from '@/services/api'
import type { 
  YouTubeVideoInfo, 
  YouTubeThumbnailDisplay,
  YouTubeVideoAnalysisResult,
  YouTubeThumbnailGenerationResult,
  PluginServiceResponse
} from '@/types'

// Props
interface Props {
  initialVideoUrl?: string
  autoAnalyze?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  initialVideoUrl: '',
  autoAnalyze: false
})

// Emits
const emit = defineEmits<{
  'thumbnail-selected': [thumbnail: YouTubeThumbnailDisplay]
  'video-analyzed': [videoInfo: YouTubeVideoInfo]
  'thumbnails-generated': [thumbnails: YouTubeThumbnailDisplay[]]
  'error': [error: string]
}>()

// Reactive state
const videoUrl = ref(props.initialVideoUrl)
const videoInfo = ref<YouTubeVideoInfo | null>(null)
const isProcessing = ref(false)
const errorMessage = ref('')
const currentJob = ref<any>(null)
const generatedThumbnails = ref<YouTubeThumbnailDisplay[]>([])

// Thumbnail-specific reactive variables
const selectedThumbnailStyle = ref<string>('modern')
const maxThumbnails = ref(5)
const customPrompt = ref('')

// Recent thumbnails
const recentThumbnails = ref<any[]>([])
const showRecentThumbnails = ref(false)
const loadingRecentThumbnails = ref(false)

// Track polling interval for cleanup
let pollInterval: NodeJS.Timeout | null = null

// Enhanced thumbnail style options (matching backend)
const thumbnailStyles = [
  { 
    value: 'modern', 
    label: 'Modern & Clean',
    description: 'Contemporary design with bold typography'
  },
  { 
    value: 'dramatic', 
    label: 'Dramatic & Bold',
    description: 'High-contrast, cinematic feel'
  },
  { 
    value: 'colorful', 
    label: 'Vibrant & Colorful',
    description: 'Bright, energetic rainbow colors'
  },
  { 
    value: 'minimalist', 
    label: 'Minimal & Professional',
    description: 'Simple, elegant design with white space'
  },
  { 
    value: 'professional', 
    label: 'Professional & Polished',
    description: 'Corporate, sophisticated design'
  },
  { 
    value: 'gaming', 
    label: 'Gaming & Esports',
    description: 'Neon accents, competitive energy'
  },
  { 
    value: 'tech', 
    label: 'Tech & Futuristic',
    description: 'Sci-fi inspired, digital elements'
  },
  { 
    value: 'educational', 
    label: 'Educational & Clear',
    description: 'Learning-focused, informative design'
  },
  { 
    value: 'entertainment', 
    label: 'Entertainment & Fun',
    description: 'Playful, engaging show-business vibes'
  },
  { 
    value: 'business', 
    label: 'Business & Success',
    description: 'Professional growth, corporate success'
  },
  { 
    value: 'lifestyle', 
    label: 'Lifestyle & Personal',
    description: 'Warm, relatable everyday imagery'
  },
  { 
    value: 'vintage', 
    label: 'Vintage & Retro',
    description: 'Classic 80s-90s nostalgic appeal'
  },
  { 
    value: 'neon', 
    label: 'Neon & Cyberpunk',
    description: 'Glowing effects, futuristic energy'
  },
  { 
    value: 'cinematic', 
    label: 'Cinematic & Epic',
    description: 'Movie-quality, dramatic storytelling'
  },
  { 
    value: 'cartoon', 
    label: 'Cartoon & Animated',
    description: 'Fun illustrations, comic-book style'
  }
] as const

// Function to get appropriate icon component for each style
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
        d: 'M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z'
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
    minimalist: () => h('svg', {
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
    professional: () => h('svg', {
      fill: 'none',
      stroke: 'currentColor',
      viewBox: '0 0 24 24'
    }, [
      h('path', {
        'stroke-linecap': 'round',
        'stroke-linejoin': 'round',
        'stroke-width': '2',
        d: 'M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2-2v2m8 0V6a2 2 0 012 2v6a2 2 0 01-2 2H6a2 2 0 01-2-2V8a2 2 0 012-2V6'
      })
    ])
  }
  
  return iconComponents[styleValue as keyof typeof iconComponents] || iconComponents.modern
}

// Methods
const extractVideoInfo = async (): Promise<void> => {
  if (!videoUrl.value) return
  
  try {
    isProcessing.value = true
    errorMessage.value = ''
    
    const response = await pluginAPI.executeCommand({
      pluginId: 'youtube_thumbnail',
      command: 'analyze_video',
      layerId: null,
      parameters: {
        video_url: videoUrl.value
      }
    })
    
    if (response.data?.success && response.data.data.success) {
      const analysisResult = response.data.data.result as YouTubeVideoAnalysisResult
      // Convert to the expected YouTubeVideoInfo format
      videoInfo.value = {
        videoId: analysisResult.video_id,
        title: analysisResult.title,
        description: analysisResult.description || '',
        thumbnailUrl: analysisResult.thumbnail_url,
        channelTitle: analysisResult.author_name,
        publishedAt: analysisResult.fetched_at,
        viewCount: analysisResult.view_count || 0,
        tags: []
      }
      emit('video-analyzed', videoInfo.value as YouTubeVideoInfo)
    } else {
      throw new Error('Failed to analyze video')
    }
  } catch (error: any) {
    console.error('Failed to extract video info:', error)
    const errorMsg = error?.response?.data?.message || 'Failed to analyze video. Please check the URL and try again.'
    errorMessage.value = errorMsg
    emit('error', errorMsg)
  } finally {
    isProcessing.value = false
  }
}

const generateThumbnails = async (): Promise<void> => {
  if (!videoInfo.value) return
  
  let progressInterval: NodeJS.Timeout | null = null
  
  try {
    isProcessing.value = true
    errorMessage.value = ''
    
    // Initialize progress tracking
    currentJob.value = {
      progress: 0,
      status: 'starting',
      method: 'unknown',
      currentVariation: null,
      startTime: Date.now()
    }
    
    // Try async mode first (better for long-running operations)
    try {
      await generateThumbnailsAsync()
    } catch (asyncError) {
      console.warn('Async mode failed, falling back to sync mode:', asyncError)
      await generateThumbnailsSync()
    }
    
  } catch (error: any) {
    console.error('Failed to generate thumbnails:', error)
    const errorMsg = error?.response?.data?.message || 'Failed to generate thumbnails. Please try again.'
    errorMessage.value = errorMsg
    emit('error', errorMsg)
  } finally {
    if (progressInterval) {
      clearInterval(progressInterval)
    }
    isProcessing.value = false
  }
}

const generateThumbnailsAsync = async (): Promise<void> => {
  // Start async job
  const response = await pluginAPI.executeCommand({
    pluginId: 'youtube_thumbnail',
    command: 'generate_thumbnail_variations_async',
    layerId: null,
    parameters: {
      video_url: videoUrl.value,
      thumbnail_count: maxThumbnails.value,
      style: selectedThumbnailStyle.value,
      custom_prompt: customPrompt.value || null
    }
  })
  
  console.log('Async response structure:', JSON.stringify(response.data, null, 2))
  
  if (!response.data?.success || !(response.data as any)?.data?.result?.success) {
    throw new Error('Failed to start async thumbnail generation')
  }
  
  const jobId = (response.data as any).data.result.job_id
  
  if (!jobId) {
    console.error('No job_id found in response:', response.data)
    throw new Error('No job_id returned from async command')
  }
  
  console.log('Extracted job_id:', jobId)
  currentJob.value.status = 'queued'
  currentJob.value.progress = 5
  
  // Poll for progress
  await pollJobProgress(jobId)
}

const generateThumbnailsSync = async (): Promise<void> => {
  // Simulate progress for sync mode
  const progressInterval = setInterval(() => {
    if (currentJob.value && currentJob.value.progress < 90) {
      const increment = Math.random() * 8 + 2 // 2-10% increments
      currentJob.value.progress = Math.min(90, currentJob.value.progress + increment)
      
      const estimatedVariation = Math.ceil((currentJob.value.progress / 90) * maxThumbnails.value)
      currentJob.value.currentVariation = estimatedVariation
    }
  }, 3000)
  
  try {
    const response = await pluginAPI.executeCommand({
      pluginId: 'youtube_thumbnail',
      command: 'generate_thumbnail_variations',
      layerId: null,
      parameters: {
        video_url: videoUrl.value,
        thumbnail_count: maxThumbnails.value,
        style: selectedThumbnailStyle.value,
        custom_prompt: customPrompt.value || null
      }
    }, {
      timeout: 700000 // 11+ minutes timeout to accommodate Replicate processing
    })
    
    clearInterval(progressInterval)
    
    if (response.data?.success && response.data.data.success) {
      processGenerationResult(response.data.data.result as YouTubeThumbnailGenerationResult)
    } else {
      throw new Error('Sync generation failed')
    }
  } catch (error) {
    clearInterval(progressInterval)
    throw error
  }
}

const pollJobProgress = async (jobId: string): Promise<void> => {
  const maxPolls = 200 // ~15 minutes with 4.5 second intervals
  let polls = 0
  
  while (polls < maxPolls) {
    await new Promise(resolve => setTimeout(resolve, 4500)) // 4.5 second intervals
    polls++
    
    try {
      const statusResponse = await pluginAPI.executeCommand({
        pluginId: 'youtube_thumbnail',
        command: 'get_job_status',
        layerId: null,
        parameters: { job_id: jobId }
      })
      
      if (statusResponse.data?.success) {
        const jobStatus = statusResponse.data.data.result
        
        // Update progress from backend
        if (currentJob.value) {
          currentJob.value.progress = jobStatus.progress || 0
          currentJob.value.status = jobStatus.message || jobStatus.status || 'processing'
          currentJob.value.method = jobStatus.generation_method || 'unknown'
          currentJob.value.currentVariation = jobStatus.current_variation || null
        }
        
        if (jobStatus.status === 'completed' && jobStatus.result) {
          processGenerationResult(jobStatus.result as YouTubeThumbnailGenerationResult)
          return
        } else if (jobStatus.status === 'failed') {
          throw new Error(jobStatus.message || 'Job failed')
        } else if (jobStatus.status === 'cancelled') {
          throw new Error('Job was cancelled')
        }
      }
    } catch (pollError) {
      console.error('Error polling job status:', pollError)
      // Continue polling unless it's a fatal error
    }
  }
  
  throw new Error('Job timeout - took longer than expected')
}

const processGenerationResult = (generationResult: YouTubeThumbnailGenerationResult): void => {
  // Update final job info
  if (currentJob.value) {
    currentJob.value.method = generationResult.generation_method || 'unknown'
    currentJob.value.progress = 100
    currentJob.value.status = 'completed'
  }
  
  generatedThumbnails.value = generationResult.thumbnail_variations.map((thumb) => ({
    id: thumb.id,
    title: thumb.title,
    previewUrl: thumb.preview_url,
    fullImageUrl: thumb.image_url,
    thumbnailUrl: thumb.thumbnail_url,
    style: thumb.style,
    createdAt: thumb.created_at,
    generationMethod: thumb.generation_method || generationResult.generation_method
  }))
  
  emit('thumbnails-generated', generatedThumbnails.value)
  
  // Show success message with generation method
  const methodName = generationResult.generation_method === 'replicate' ? 'Replicate (Imagen 4 Ultra)' : 'OpenAI (GPT-4 Vision)'
  console.log(`Successfully generated ${generatedThumbnails.value.length} thumbnails using ${methodName}`)
}

const previewThumbnail = (thumbnail: YouTubeThumbnailDisplay): void => {
  // Create a simple lightbox overlay to preview the image
  const overlay = document.createElement('div')
  overlay.className = 'fixed inset-0 bg-black bg-opacity-75 flex items-center justify-center z-50 cursor-pointer'
  
  const closeOverlay = () => {
    overlay.remove()
    document.removeEventListener('keydown', handleEscape)
  }
  
  const handleEscape = (e: KeyboardEvent) => {
    if (e.key === 'Escape') closeOverlay()
  }
  
  overlay.onclick = closeOverlay
  document.addEventListener('keydown', handleEscape)
  
  const img = document.createElement('img')
  img.src = thumbnail.fullImageUrl
  img.className = 'max-w-full max-h-full object-contain'
  img.alt = thumbnail.title
  
  const container = document.createElement('div')
  container.className = 'relative max-w-4xl max-h-4xl p-4'
  container.onclick = (e) => e.stopPropagation()
  
  const closeBtn = document.createElement('button')
  closeBtn.innerHTML = '✕'
  closeBtn.className = 'absolute top-2 right-2 text-white bg-black bg-opacity-50 rounded-full w-8 h-8 flex items-center justify-center hover:bg-opacity-75'
  closeBtn.onclick = closeOverlay
  
  container.appendChild(img)
  container.appendChild(closeBtn)
  overlay.appendChild(container)
  document.body.appendChild(overlay)
}

const downloadThumbnail = async (thumbnail: YouTubeThumbnailDisplay): Promise<void> => {
  try {
    // Download the full size image
    const response = await fetch(thumbnail.fullImageUrl)
    const blob = await response.blob()
    const url = window.URL.createObjectURL(blob)
    
    const link = document.createElement('a')
    link.href = url
    link.download = `youtube-thumbnail-${thumbnail.id}.jpg`
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
    
    // Clean up the blob URL
    window.URL.revokeObjectURL(url)
  } catch (error) {
    console.error('Failed to download thumbnail:', error)
    emit('error', 'Failed to download thumbnail. Please try again.')
  }
}

const getProgressMessage = (): string => {
  if (!currentJob.value) return ''
  
  const progress = currentJob.value.progress || 0
  const method = currentJob.value.method
  
  if (method === 'replicate') {
    // Progress messages for Replicate (longer process)
    if (progress < 10) return 'Initializing Google Imagen 4 Ultra...'
    if (progress < 30) return 'Analyzing video content and extracting key elements...'
    if (progress < 60) return 'Generating high-quality thumbnails with AI...'
    if (progress < 90) return 'Processing and optimizing images...'
    if (progress < 100) return 'Finalizing thumbnail variations...'
    return 'Thumbnail generation complete!'
  } else if (method === 'openai') {
    // Progress messages for OpenAI (faster process)
    if (progress < 20) return 'Preparing original thumbnail for AI processing...'
    if (progress < 50) return 'Generating variations with GPT-4 Vision...'
    if (progress < 80) return 'Processing and creating multiple sizes...'
    if (progress < 100) return 'Finalizing thumbnail previews...'
    return 'Thumbnail generation complete!'
  } else {
    // Generic progress messages
    if (progress < 25) return 'Extracting key frames and analyzing video content...'
    if (progress < 50) return 'Identifying best moments and visual elements...'
    if (progress < 75) return 'Generating thumbnail designs with AI...'
    if (progress < 100) return 'Finalizing layouts and preparing previews...'
    return 'Thumbnail generation complete!'
  }
}

// Load recent thumbnails
const loadRecentThumbnails = async (): Promise<void> => {
  try {
    loadingRecentThumbnails.value = true
    errorMessage.value = ''
    
    const response = await pluginAPI.executeCommand({
      pluginId: 'youtube_thumbnail',
      command: 'get_recent_thumbnails',
      layerId: null,
      parameters: {
        limit: 10
      }
    })
    
    if (response.data?.success && response.data.data?.result?.success) {
      // Flatten generations into individual thumbnails for display
      const generations = response.data.data.result.generations || []
      const flatThumbnails: any[] = []
      
      generations.forEach((generation: any) => {
        generation.thumbnails.forEach((thumbnail: any) => {
          flatThumbnails.push({
            ...thumbnail,
            video_id: generation.video_id,
            video_title: generation.video_title,
            video_channel: generation.video_channel,
            generation_id: generation.id,
            generation_method: generation.generation_method,
            created_at: generation.created_at
          })
        })
      })
      
      recentThumbnails.value = flatThumbnails
    } else {
      throw new Error('Failed to load recent thumbnails')
    }
  } catch (error: any) {
    console.error('Error loading recent thumbnails:', error)
    errorMessage.value = 'Failed to load recent thumbnails: ' + (error.message || 'Unknown error')
  } finally {
    loadingRecentThumbnails.value = false
  }
}

// Toggle recent thumbnails view
const toggleRecentThumbnails = async (): Promise<void> => {
  showRecentThumbnails.value = !showRecentThumbnails.value
  if (showRecentThumbnails.value && recentThumbnails.value.length === 0) {
    await loadRecentThumbnails()
  }
}

// Auto-analyze on mount if props specify
if (props.autoAnalyze && props.initialVideoUrl) {
  extractVideoInfo()
}

// Cleanup
onUnmounted(() => {
  if (pollInterval) {
    clearInterval(pollInterval)
    pollInterval = null
  }
})
</script>

<style scoped>
.slider::-webkit-slider-thumb {
  appearance: none;
  height: 20px;
  width: 20px;
  border-radius: 50%;
  background: #dc2626;
  cursor: pointer;
}

.slider::-moz-range-thumb {
  height: 20px;
  width: 20px;
  border-radius: 50%;
  background: #dc2626;
  cursor: pointer;
  border: none;
}
</style>

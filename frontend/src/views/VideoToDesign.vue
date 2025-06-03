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
          <!-- Thumbnail Style Selection with HeadlessUI Listbox -->
          <div>
            <Listbox v-model="selectedThumbnailStyle">
              <ListboxLabel class="block text-sm font-medium text-gray-700 mb-3">
                Choose Thumbnail Style
              </ListboxLabel>
              <div class="relative">
                <ListboxButton class="relative w-full cursor-pointer rounded-lg bg-white py-3 pl-4 pr-10 text-left shadow-sm border border-gray-300 focus:border-red-500 focus:outline-none focus:ring-2 focus:ring-red-200">
                  <span class="flex items-center">
                    <div class="w-6 h-6 rounded-md bg-red-100 text-red-600 flex items-center justify-center mr-3">
                      <component :is="getStyleIcon(selectedThumbnailStyle)" class="w-4 h-4" />
                    </div>
                    <span class="block truncate font-medium">
                      {{ thumbnailStyles.find(s => s.value === selectedThumbnailStyle)?.label }}
                    </span>
                  </span>
                  <span class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3">
                    <ChevronUpDownIcon class="h-5 w-5 text-gray-400" aria-hidden="true" />
                  </span>
                </ListboxButton>

                <transition
                  leave-active-class="transition duration-100 ease-in"
                  leave-from-class="opacity-100"
                  leave-to-class="opacity-0"
                >
                  <ListboxOptions class="absolute z-10 mt-1 max-h-60 w-full overflow-auto rounded-lg bg-white py-1 shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none">
                    <ListboxOption
                      v-for="style in thumbnailStyles"
                      :key="style.value"
                      :value="style.value"
                      v-slot="{ active, selected }"
                      class="cursor-pointer"
                    >
                      <li
                        :class="[
                          active ? 'bg-red-50 text-red-900' : 'text-gray-900',
                          'relative cursor-pointer select-none py-3 px-4'
                        ]"
                      >
                        <div class="flex items-center">
                          <div
                            :class="[
                              'w-8 h-8 rounded-lg flex items-center justify-center mr-3',
                              selected || active
                                ? 'bg-red-100 text-red-600'
                                : 'bg-gray-100 text-gray-400'
                            ]"
                          >
                            <component :is="getStyleIcon(style.value)" class="w-5 h-5" />
                          </div>
                          <div class="flex-1">
                            <span
                              :class="[
                                selected ? 'font-semibold' : 'font-medium',
                                'block text-sm'
                              ]"
                            >
                              {{ style.label }}
                            </span>
                            <span
                              :class="[
                                active ? 'text-red-700' : 'text-gray-500',
                                'block text-xs mt-1'
                              ]"
                            >
                              {{ style.description }}
                            </span>
                          </div>
                          <span
                            v-if="selected"
                            :class="[
                              active ? 'text-red-600' : 'text-red-500',
                              'absolute inset-y-0 right-0 flex items-center pr-4'
                            ]"
                          >
                            <CheckIcon class="h-5 w-5" aria-hidden="true" />
                          </span>
                        </div>
                      </li>
                    </ListboxOption>
                  </ListboxOptions>
                </transition>
              </div>
            </Listbox>
          </div>

          <!-- Generation Settings -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-3">
              Generation Settings
            </label>
            <div class="space-y-4">
              <div>
                <label class="block text-sm text-gray-600 mb-1">Number of Thumbnails</label>
                <select
                  v-model="maxThumbnails"
                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-red-500 focus:border-red-500"
                >
                  <option :value="3">3 thumbnails</option>
                  <option :value="5">5 thumbnails</option>
                  <option :value="8">8 thumbnails</option>
                  <option :value="10">10 thumbnails</option>
                </select>
              </div>

              <div>
                <label class="block text-sm text-gray-600 mb-1">Thumbnail Size</label>
                <select
                  v-model="thumbnailSize"
                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-red-500 focus:border-red-500"
                >
                  <option value="1280x720">YouTube Standard (1280×720)</option>
                  <option value="1920x1080">HD (1920×1080)</option>
                  <option value="2560x1440">2K (2560×1440)</option>
                </select>
              </div>
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
            placeholder="e.g., 'Make it more dramatic', 'Use bright colors', 'Focus on the main subject', 'Add arrows pointing to key elements'..."
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-red-500 focus:border-red-500"
          />
        </div>

        <!-- Generate Button -->
        <div class="mt-8 text-center">
          <button
            @click="generateThumbnails"
            :disabled="isProcessing"
            class="px-8 py-4 bg-gradient-to-r from-red-600 to-pink-600 text-white rounded-xl hover:from-red-700 hover:to-pink-700 disabled:opacity-50 disabled:cursor-not-allowed font-semibold flex items-center mx-auto space-x-3 shadow-lg hover:shadow-xl transition-all duration-200"
          >
            <svg v-if="isProcessing" class="animate-spin -ml-1 mr-3 h-6 w-6 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <svg v-else class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
            </svg>
            <span v-if="isProcessing">Generating Thumbnails...</span>
            <span v-else>🎬 Generate YouTube Thumbnails</span>
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
        <div class="flex items-center justify-between mb-8">
          <div>
            <h2 class="text-2xl font-semibold text-gray-900">Generated Thumbnails</h2>
            <p class="text-gray-600 mt-1">Click any thumbnail to edit it in our design studio</p>
          </div>
          <div v-if="isDevelopment && errorMessage.includes('DEV MODE')" class="text-xs bg-yellow-100 text-yellow-800 px-3 py-2 rounded-full flex items-center space-x-2">
            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
            </svg>
            <span>Development Mode</span>
          </div>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-8">
          <div
            v-for="thumbnail in generatedThumbnails"
            :key="thumbnail.id"
            class="group cursor-pointer bg-white rounded-xl border-2 border-gray-200 hover:border-red-300 hover:shadow-2xl transition-all duration-300 overflow-hidden"
            @click="handleThumbnailSelected(thumbnail)"
          >
            <!-- Thumbnail Preview with YouTube-like styling -->
            <div class="relative aspect-video bg-gradient-to-br from-gray-900 to-gray-800 rounded-t-xl overflow-hidden">
              <img
                :src="thumbnail.thumbnailUrl"
                :alt="thumbnail.title"
                class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500"
              />
              
              <!-- YouTube Play Button Overlay -->
              <div class="absolute inset-0 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity duration-300">
                <div class="bg-red-600 rounded-full p-4 shadow-2xl">
                  <svg class="w-8 h-8 text-white ml-1" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M8 5v14l11-7z"/>
                  </svg>
                </div>
              </div>

              <!-- Confidence Badge -->
              <div class="absolute top-3 right-3">
                <span class="bg-black bg-opacity-75 text-white text-xs px-2 py-1 rounded-full font-medium">
                  {{ thumbnail.confidence }}% Match
                </span>
              </div>

              <!-- Style Badge -->
              <div class="absolute top-3 left-3">
                <span class="bg-red-600 text-white text-xs px-2 py-1 rounded-full font-medium capitalize">
                  {{ thumbnail.category }}
                </span>
              </div>

              <!-- Duration Badge (Mock) -->
              <div class="absolute bottom-3 right-3">
                <span class="bg-black bg-opacity-75 text-white text-xs px-2 py-1 rounded font-medium">
                  {{ formatDuration(videoInfo?.duration || 0) }}
                </span>
              </div>
            </div>

            <!-- Content -->
            <div class="p-6">
              <h3 class="font-semibold text-gray-900 group-hover:text-red-600 transition-colors mb-2 text-lg">
                {{ thumbnail.title }}
              </h3>

              <p class="text-sm text-gray-600 mb-4 line-clamp-2 leading-relaxed">
                {{ thumbnail.description }}
              </p>

              <!-- Style Tags -->
              <div class="flex flex-wrap gap-2 mb-4">
                <span
                  v-for="tag in thumbnail.tags.slice(0, 4)"
                  :key="tag"
                  class="text-xs bg-gray-100 text-gray-700 px-3 py-1 rounded-full font-medium"
                >
                  {{ tag }}
                </span>
                <span
                  v-if="thumbnail.tags.length > 4"
                  class="text-xs text-gray-500 px-2 py-1"
                >
                  +{{ thumbnail.tags.length - 4 }} more
                </span>
              </div>

              <!-- Dimensions -->
              <div class="flex items-center justify-between text-sm text-gray-500 mb-4">
                <span class="flex items-center space-x-1">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4a1 1 0 011-1h4m11 0h-4a1 1 0 011 1v4m-11 8v4a1 1 0 01-1 1H4m11 0h4a1 1 0 01-1-1v-4" />
                  </svg>
                  <span class="">{{ thumbnail.width }} × {{ thumbnail.height }}px</span>
                </span>
                <span class="text-red-600 font-medium">Ready to edit</span>
              </div>

              <!-- Action Button -->
              <button class="w-full bg-gradient-to-r from-red-600 to-pink-600 text-white py-3 px-4 rounded-lg hover:from-red-700 hover:to-pink-700 transition-all duration-200 font-semibold shadow-md hover:shadow-lg">
                ✨ Edit This Thumbnail
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import { ref, onUnmounted, h } from 'vue'
import { useRouter } from 'vue-router'
import {
  Listbox,
  ListboxButton,
  ListboxLabel,
  ListboxOption,
  ListboxOptions,
} from '@headlessui/vue'
import { ChevronUpDownIcon, CheckIcon } from '@heroicons/vue/24/outline'
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
const selectedThumbnailStyle = ref<string>('modern')
const maxThumbnails = ref(5)
const thumbnailSize = ref('1280x720')
const customPrompt = ref('')

// Development flag - set to false in production
const isDevelopment = import.meta.env.DEV

// Track polling interval for cleanup
let pollInterval: NodeJS.Timeout | null = null

// Enhanced thumbnail style options with descriptions
const thumbnailStyles = [
  { 
    value: 'modern', 
    label: 'Modern & Clean',
    description: 'Minimalist design with bold typography and clean layouts'
  },
  { 
    value: 'dramatic', 
    label: 'Dramatic & Bold',
    description: 'High-contrast colors and attention-grabbing elements'
  },
  { 
    value: 'colorful', 
    label: 'Vibrant & Colorful',
    description: 'Bright, energetic colors that stand out in feeds'
  },
  { 
    value: 'minimal', 
    label: 'Minimal & Professional',
    description: 'Simple, elegant design focused on clarity'
  },
  { 
    value: 'gaming', 
    label: 'Gaming Style',
    description: 'Dynamic effects and gaming-oriented design elements'
  },
  { 
    value: 'educational', 
    label: 'Educational & Informative',
    description: 'Clear, instructional layout with helpful visual cues'
  },
  { 
    value: 'lifestyle', 
    label: 'Lifestyle & Vlog',
    description: 'Personal, relatable design with warm, inviting colors'
  },
  { 
    value: 'tech', 
    label: 'Tech & Reviews',
    description: 'Modern, tech-focused design with sleek aesthetics'
  },
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
    minimal: () => h('svg', {
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
    gaming: () => h('svg', {
      fill: 'none',
      stroke: 'currentColor',
      viewBox: '0 0 24 24'
    }, [
      h('path', {
        'stroke-linecap': 'round',
        'stroke-linejoin': 'round',
        'stroke-width': '2',
        d: 'M11 4a2 2 0 114 0v1a1 1 0 001 1h3a1 1 0 011 1v3a1 1 0 01-1 1h-1a2 2 0 100 4h1a1 1 0 011 1v3a1 1 0 01-1 1h-3a1 1 0 01-1-1v-1a2 2 0 10-4 0v1a1 1 0 01-1 1H7a1 1 0 01-1-1v-3a1 1 0 00-1-1H4a1 1 0 01-1-1V9a1 1 0 011-1h1a2 2 0 100-4H4a1 1 0 01-1-1V5a1 1 0 011-1h3a1 1 0 001-1v-1z'
      })
    ]),
    educational: () => h('svg', {
      fill: 'none',
      stroke: 'currentColor',
      viewBox: '0 0 24 24'
    }, [
      h('path', {
        'stroke-linecap': 'round',
        'stroke-linejoin': 'round',
        'stroke-width': '2',
        d: 'M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253'
      })
    ]),
    lifestyle: () => h('svg', {
      fill: 'none',
      stroke: 'currentColor',
      viewBox: '0 0 24 24'
    }, [
      h('path', {
        'stroke-linecap': 'round',
        'stroke-linejoin': 'round',
        'stroke-width': '2',
        d: 'M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z'
      })
    ]),
    tech: () => h('svg', {
      fill: 'none',
      stroke: 'currentColor',
      viewBox: '0 0 24 24'
    }, [
      h('path', {
        'stroke-linecap': 'round',
        'stroke-linejoin': 'round',
        'stroke-width': '2',
        d: 'M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z'
      })
    ])
  }
  
  return iconComponents[styleValue as keyof typeof iconComponents] || iconComponents.modern
}

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

// Enhanced mock thumbnail generator with more examples
const generateMockThumbnails = (videoInfo: YouTubeVideoInfo): DesignSuggestion[] => {
  const [width, height] = thumbnailSize.value.split('x').map(Number)
  const selectedStyle = selectedThumbnailStyle.value
  
  const mockThumbnails: DesignSuggestion[] = [
    {
      id: `mock-thumb-${Date.now()}-1`,
      title: `${videoInfo.title.slice(0, 40)}... - ${selectedStyle.charAt(0).toUpperCase() + selectedStyle.slice(1)} Style`,
      description: `Eye-catching ${selectedStyle} thumbnail design with bold typography and engaging visuals inspired by "${videoInfo.title}".`,
      category: selectedStyle,
      thumbnailUrl: `https://picsum.photos/${width}/${height}?random=${Date.now() + 1}`,
      designData: {
        version: '1.0',
        canvas: { width, height, backgroundColor: selectedStyle === 'minimal' ? '#ffffff' : '#1a1a1a' },
        layers: []
      },
      width, height, confidence: 92,
      sourceFrames: [0, 3, 7], 
      tags: [selectedStyle, 'youtube', 'thumbnail', 'professional', ...videoInfo.tags.slice(0, 2)]
    },
    {
      id: `mock-thumb-${Date.now()}-2`,
      title: `${videoInfo.title.slice(0, 35)}... - Alternative Design`,
      description: `Alternative ${selectedStyle} thumbnail approach with different layout and visual hierarchy.`,
      category: selectedStyle,
      thumbnailUrl: `https://picsum.photos/${width}/${height}?random=${Date.now() + 2}`,
      designData: {
        version: '1.0',
        canvas: { width, height, backgroundColor: '#2563eb' },
        layers: []
      },
      width, height, confidence: 88,
      sourceFrames: [1, 5, 9], 
      tags: [selectedStyle, 'alternative', 'creative', 'engaging', ...videoInfo.tags.slice(0, 2)]
    },
    {
      id: `mock-thumb-${Date.now()}-3`,
      title: `${videoInfo.title.slice(0, 30)}... - Enhanced Version`,
      description: `Enhanced ${selectedStyle} design with premium visual effects and optimized click-through potential.`,
      category: selectedStyle,
      thumbnailUrl: `https://picsum.photos/${width}/${height}?random=${Date.now() + 3}`,
      designData: {
        version: '1.0',
        canvas: { width, height, backgroundColor: '#dc2626' },
        layers: []
      },
      width, height, confidence: 95,
      sourceFrames: [2, 6, 11], 
      tags: [selectedStyle, 'enhanced', 'premium', 'optimized', ...videoInfo.tags.slice(0, 2)]
    },
    {
      id: `mock-thumb-${Date.now()}-4`,
      title: `${videoInfo.title.slice(0, 32)}... - Variant A`,
      description: `Creative variant focusing on visual storytelling and emotional engagement.`,
      category: selectedStyle,
      thumbnailUrl: `https://picsum.photos/${width}/${height}?random=${Date.now() + 4}`,
      designData: {
        version: '1.0',
        canvas: { width, height, backgroundColor: '#059669' },
        layers: []
      },
      width, height, confidence: 87,
      sourceFrames: [3, 7, 12], 
      tags: [selectedStyle, 'variant', 'storytelling', 'emotional', ...videoInfo.tags.slice(0, 2)]
    },
    {
      id: `mock-thumb-${Date.now()}-5`,
      title: `${videoInfo.title.slice(0, 38)}... - Variant B`,
      description: `Bold variant with strong visual hierarchy and call-to-action elements.`,
      category: selectedStyle,
      thumbnailUrl: `https://picsum.photos/${width}/${height}?random=${Date.now() + 5}`,
      designData: {
        version: '1.0',
        canvas: { width, height, backgroundColor: '#7c3aed' },
        layers: []
      },
      width, height, confidence: 90,
      sourceFrames: [4, 8, 13], 
      tags: [selectedStyle, 'bold', 'hierarchy', 'call-to-action', ...videoInfo.tags.slice(0, 1)]
    },
    {
      id: `mock-thumb-${Date.now()}-6`,
      title: `${videoInfo.title.slice(0, 35)}... - Pro Edition`,
      description: `Professional-grade thumbnail with advanced design techniques and visual psychology.`,
      category: selectedStyle,
      thumbnailUrl: `https://picsum.photos/${width}/${height}?random=${Date.now() + 6}`,
      designData: {
        version: '1.0',
        canvas: { width, height, backgroundColor: '#ea580c' },
        layers: []
      },
      width, height, confidence: 94,
      sourceFrames: [5, 9, 14], 
      tags: [selectedStyle, 'professional', 'advanced', 'psychology', ...videoInfo.tags.slice(0, 1)]
    },
    {
      id: `mock-thumb-${Date.now()}-7`,
      title: `${videoInfo.title.slice(0, 33)}... - Trending Style`,
      description: `Thumbnail designed following current YouTube trending patterns and best practices.`,
      category: selectedStyle,
      thumbnailUrl: `https://picsum.photos/${width}/${height}?random=${Date.now() + 7}`,
      designData: {
        version: '1.0',
        canvas: { width, height, backgroundColor: '#be185d' },
        layers: []
      },
      width, height, confidence: 91,
      sourceFrames: [6, 10, 15], 
      tags: [selectedStyle, 'trending', 'popular', 'best-practices', ...videoInfo.tags.slice(0, 1)]
    },
    {
      id: `mock-thumb-${Date.now()}-8`,
      title: `${videoInfo.title.slice(0, 36)}... - Creative Mix`,
      description: `Creative blend of multiple design approaches for maximum visual impact.`,
      category: selectedStyle,
      thumbnailUrl: `https://picsum.photos/${width}/${height}?random=${Date.now() + 8}`,
      designData: {
        version: '1.0',
        canvas: { width, height, backgroundColor: '#0891b2' },
        layers: []
      },
      width, height, confidence: 89,
      sourceFrames: [7, 11, 16], 
      tags: [selectedStyle, 'creative', 'mixed', 'impact', ...videoInfo.tags.slice(0, 1)]
    }
  ]

  return mockThumbnails.slice(0, maxThumbnails.value)
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
  if (!videoInfo.value) return
  
  try {
    isProcessing.value = true
    errorMessage.value = ''
    
    const [width, height] = thumbnailSize.value.split('x').map(Number)
    
    const requestData: GenerateDesignFromVideoRequest = {
      videoUrl: videoUrl.value,
      designTypes: [DesignType.THUMBNAIL],
      maxDesigns: maxThumbnails.value,
      includeTranscript: false,
      extractFrames: true,
      customPrompt: `Generate YouTube thumbnail in ${selectedThumbnailStyle.value} style. Size: ${width}x${height}. ${customPrompt.value}`
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

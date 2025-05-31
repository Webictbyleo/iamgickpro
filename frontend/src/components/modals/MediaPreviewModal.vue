<template>
  <TransitionRoot as="template" :show="isOpen">
    <Dialog as="div" class="relative z-50" @close="closeModal">
      <TransitionChild
        as="template"
        enter="ease-out duration-300"
        enter-from="opacity-0"
        enter-to="opacity-100"
        leave="ease-in duration-200"
        leave-from="opacity-100"
        leave-to="opacity-0"
      >
        <div class="fixed inset-0 bg-black bg-opacity-75 transition-opacity backdrop-blur-sm" />
      </TransitionChild>

      <div class="fixed inset-0 z-10 overflow-y-auto">
        <div class="flex min-h-full items-center justify-center p-4">
          <TransitionChild
            as="template"
            enter="ease-out duration-300"
            enter-from="opacity-0 scale-95"
            enter-to="opacity-100 scale-100"
            leave="ease-in duration-200"
            leave-from="opacity-100 scale-100"
            leave-to="opacity-0 scale-95"
          >
            <DialogPanel class="relative max-w-6xl w-full bg-white rounded-2xl shadow-2xl overflow-hidden">
              <!-- Header -->
              <div class="flex items-center justify-between p-6 border-b border-gray-200 bg-gradient-to-r from-gray-50 to-gray-100">
                <div class="flex items-center space-x-4">
                  <div class="w-10 h-10 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-xl flex items-center justify-center shadow-lg">
                    <component :is="getMediaIcon()" class="w-5 h-5 text-white" />
                  </div>
                  <div>
                    <DialogTitle as="h3" class="text-xl font-bold text-gray-900">
                      {{ media?.title || 'Media Preview' }}
                    </DialogTitle>
                    <p class="text-sm text-gray-600 mt-1">
                      {{ getMediaTypeLabel() }} • {{ formatFileSize(media?.size) }}
                    </p>
                  </div>
                </div>
                <div class="flex items-center space-x-2">
                  <!-- Media Actions -->
                  <button
                    v-if="canDownload"
                    @click="downloadMedia"
                    class="p-2 text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded-lg transition-all duration-200"
                    title="Download"
                  >
                    <ArrowDownTrayIcon class="w-5 h-5" />
                  </button>
                  <button
                    @click="closeModal"
                    class="p-2 text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded-lg transition-all duration-200"
                    title="Close"
                  >
                    <XMarkIcon class="w-5 h-5" />
                  </button>
                </div>
              </div>

              <!-- Media Content -->
              <div class="relative">
                <!-- Loading Overlay (shows on top of content) -->
                <div v-if="isLoading" class="absolute inset-0 z-10 flex items-center justify-center bg-gray-50 bg-opacity-90">
                  <div class="flex flex-col items-center space-y-4">
                    <div class="w-8 h-8 border-4 border-indigo-200 border-t-indigo-600 rounded-full animate-spin"></div>
                    <p class="text-gray-600">Loading media...</p>
                  </div>
                </div>

                <!-- Error State -->
                <div v-if="hasError" class="flex items-center justify-center h-96 bg-gray-50">
                  <div class="flex flex-col items-center space-y-4 max-w-sm text-center">
                    <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center">
                      <ExclamationTriangleIcon class="w-8 h-8 text-red-600" />
                    </div>
                    <div>
                      <h4 class="text-lg font-semibold text-gray-900 mb-2">Preview Not Available</h4>
                      <p class="text-gray-600">{{ errorMessage }}</p>
                    </div>
                  </div>
                </div>

                <!-- Image Preview -->
                <div v-else-if="mediaType === 'image'" class="bg-gray-900">
                  <div class="relative max-h-[70vh] overflow-hidden flex items-center justify-center">
                    <img
                      :src="media?.url"
                      :alt="media?.title"
                      class="max-w-full max-h-full object-contain"
                      :class="{ 'opacity-0': isLoading }"
                      @load="handleMediaLoad"
                      @error="handleMediaError"
                    />
                    <!-- Image Controls Overlay -->
                    <div class="absolute bottom-4 right-4 flex items-center space-x-2">
                      <button
                        @click="toggleFullscreen"
                        class="bg-black/50 backdrop-blur-sm text-white p-2 rounded-lg hover:bg-black/70 transition-all duration-200"
                        title="Fullscreen"
                      >
                        <ArrowsPointingOutIcon class="w-4 h-4" />
                      </button>
                    </div>
                  </div>
                </div>

                <!-- Video Preview -->
                <div v-else-if="mediaType === 'video'" class="bg-black">
                  <div class="relative w-full h-[70vh] flex items-center justify-center">
                    <video
                      ref="videoElement"
                      :src="media?.url"
                      controls
                      class="max-w-full max-h-full object-contain"
                      :class="{ 'opacity-0': isLoading }"
                      @loadeddata="handleMediaLoad"
                      @error="handleMediaError"
                      @play="handleVideoPlay"
                      @pause="handleVideoPause"
                    >
                      Your browser does not support the video tag.
                    </video>
                    <!-- Video Controls Overlay -->
                    <div v-if="!isLoading" class="absolute bottom-4 right-4 flex items-center space-x-2 z-20">
                      <button
                        @click="toggleMute"
                        class="bg-black/50 backdrop-blur-sm text-white p-2 rounded-lg hover:bg-black/70 transition-all duration-200"
                        :title="isVideoMuted ? 'Unmute' : 'Mute'"
                      >
                        <SpeakerXMarkIcon v-if="isVideoMuted" class="w-4 h-4" />
                        <SpeakerWaveIcon v-else class="w-4 h-4" />
                      </button>
                      <button
                        @click="toggleFullscreen"
                        class="bg-black/50 backdrop-blur-sm text-white p-2 rounded-lg hover:bg-black/70 transition-all duration-200"
                        title="Fullscreen"
                      >
                        <ArrowsPointingOutIcon class="w-4 h-4" />
                      </button>
                    </div>
                  </div>
                </div>

                <!-- Audio Preview -->
                <div v-else-if="mediaType === 'audio'" class="bg-gradient-to-br from-indigo-50 to-purple-50 p-12 min-h-[60vh]">
                  <div class="max-w-lg mx-auto">
                    <div class="text-center mb-8">
                      <div class="w-24 h-24 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-full flex items-center justify-center mx-auto mb-4 shadow-lg">
                        <MusicalNoteIcon class="w-12 h-12 text-white" />
                      </div>
                      <h4 class="text-xl font-bold text-gray-900 mb-2">{{ media?.title }}</h4>
                      <p class="text-gray-600">{{ formatDuration(audioDuration) }}</p>
                    </div>
                    
                    <!-- Audio Player -->
                    <div class="bg-white rounded-2xl p-6 shadow-lg border border-indigo-100 transition-opacity duration-300" :class="{ 'opacity-30': isLoading }">
                      <audio
                        ref="audioElement"
                        :src="media?.url"
                        class="w-full !h-12"
                        controls
                        preload="metadata"
                        @loadeddata="handleMediaLoad"
                        @error="handleMediaError"
                        @loadedmetadata="handleAudioMetadata"
                      >
                        Your browser does not support the audio tag.
                      </audio>
                      
                      <!-- Audio Info -->
                      <div v-if="!isLoading" class="mt-4 text-center text-sm text-gray-600">
                        <p v-if="audioDuration > 0">Duration: {{ formatDuration(audioDuration) }}</p>
                        <p v-else>Click play to start audio</p>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Unsupported Media Type -->
                <div v-else class="flex items-center justify-center h-96 bg-gray-50">
                  <div class="flex flex-col items-center space-y-4 max-w-sm text-center">
                    <div class="w-16 h-16 bg-gray-200 rounded-full flex items-center justify-center">
                      <DocumentIcon class="w-8 h-8 text-gray-500" />
                    </div>
                    <div>
                      <h4 class="text-lg font-semibold text-gray-900 mb-2">Preview Not Supported</h4>
                      <p class="text-gray-600 mb-4">
                        This file type ({{ getFileExtension() }}) cannot be previewed in the browser.
                      </p>
                      <button
                        v-if="canDownload"
                        @click="downloadMedia"
                        class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition-colors duration-200"
                      >
                        <ArrowDownTrayIcon class="w-4 h-4 mr-2" />
                        Download File
                      </button>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Media Information -->
              <div v-if="!isLoading && !hasError" class="border-t border-gray-200 bg-gray-50 p-6">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                  <!-- File Information -->
                  <div>
                    <h5 class="text-sm font-bold text-gray-900 mb-3">File Information</h5>
                    <div class="space-y-2 text-sm">
                      <div class="flex justify-between">
                        <span class="text-gray-600">Type:</span>
                        <span class="font-medium text-gray-900">{{ getMediaTypeLabel() }}</span>
                      </div>
                      <div class="flex justify-between">
                        <span class="text-gray-600">Size:</span>
                        <span class="font-medium text-gray-900">{{ formatFileSize(media?.size) }}</span>
                      </div>
                      <div v-if="media?.dimensions" class="flex justify-between">
                        <span class="text-gray-600">Dimensions:</span>
                        <span class="font-medium text-gray-900">{{ media.dimensions.width }}×{{ media.dimensions.height }}</span>
                      </div>
                      <div v-if="audioDuration" class="flex justify-between">
                        <span class="text-gray-600">Duration:</span>
                        <span class="font-medium text-gray-900">{{ formatDuration(audioDuration) }}</span>
                      </div>
                    </div>
                  </div>
                  
                  <!-- Metadata -->
                  <div v-if="media?.metadata">
                    <h5 class="text-sm font-bold text-gray-900 mb-3">Details</h5>
                    <div class="space-y-2 text-sm">
                      <div v-if="media.author" class="flex justify-between">
                        <span class="text-gray-600">Author:</span>
                        <span class="font-medium text-gray-900">{{ media.author }}</span>
                      </div>
                      <div class="flex justify-between">
                        <span class="text-gray-600">Added:</span>
                        <span class="font-medium text-gray-900">{{ formatDate(media.created_at) }}</span>
                      </div>
                      <div v-if="media.tags?.length" class="flex justify-between">
                        <span class="text-gray-600">Tags:</span>
                        <div class="flex flex-wrap gap-1">
                          <span
                            v-for="tag in media.tags.slice(0, 3)"
                            :key="tag"
                            class="inline-flex px-2 py-1 bg-indigo-100 text-indigo-700 text-xs rounded-full"
                          >
                            {{ tag }}
                          </span>
                        </div>
                      </div>
                    </div>
                  </div>
                  
                  <!-- Actions -->
                  <div>
                    <h5 class="text-sm font-bold text-gray-900 mb-3">Actions</h5>
                    <div class="space-y-2">
                      <button
                        @click="addToDesign"
                        class="w-full flex items-center justify-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition-colors duration-200"
                      >
                        <PlusIcon class="w-4 h-4 mr-2" />
                        Add to Design
                      </button>
                      <button
                        v-if="canDownload"
                        @click="downloadMedia"
                        class="w-full flex items-center justify-center px-4 py-2 bg-gray-600 text-white text-sm font-medium rounded-lg hover:bg-gray-700 transition-colors duration-200"
                      >
                        <ArrowDownTrayIcon class="w-4 h-4 mr-2" />
                        Download
                      </button>
                    </div>
                  </div>
                </div>
              </div>
            </DialogPanel>
          </TransitionChild>
        </div>
      </div>
    </Dialog>
  </TransitionRoot>
</template>

<script setup lang="ts">
import { ref, computed, watch, onMounted, onUnmounted } from 'vue'
import {
  Dialog,
  DialogPanel,
  DialogTitle,
  TransitionChild,
  TransitionRoot,
} from '@headlessui/vue'
import {
  XMarkIcon,
  ArrowDownTrayIcon,
  ExclamationTriangleIcon,
  ArrowsPointingOutIcon,
  SpeakerXMarkIcon,
  SpeakerWaveIcon,
  MusicalNoteIcon,
  DocumentIcon,
  PlusIcon,
  PhotoIcon,
  VideoCameraIcon,
  FilmIcon
} from '@heroicons/vue/24/outline'

interface MediaItem {
  id: string
  title: string
  url: string
  type: string
  size?: number
  author?: string
  created_at: string
  thumbnail?: string
  dimensions?: {
    width: number
    height: number
  }
  metadata?: Record<string, any>
  tags?: string[]
}

interface Props {
  isOpen: boolean
  media: MediaItem | null
}

interface Emits {
  close: []
  addToDesign: [media: MediaItem]
  download: [media: MediaItem]
}

const props = defineProps<Props>()
const emit = defineEmits<Emits>()

// Refs
const videoElement = ref<HTMLVideoElement>()
const audioElement = ref<HTMLAudioElement>()

// State
const isLoading = ref(true)
const hasError = ref(false)
const errorMessage = ref('')
const isVideoMuted = ref(false)
const audioDuration = ref(0)

// Computed properties
const mediaType = computed(() => {
  if (!props.media?.type) return 'unknown'
  
  const type = props.media.type.toLowerCase()
  console.log('MediaPreviewModal - Detecting type for:', type, 'from media:', props.media)
  
  if (type.includes('image') || ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'].includes(type)) {
    return 'image'
  }
  if (type.includes('video') || ['mp4', 'webm', 'mov', 'avi', 'mkv'].includes(type)) {
    return 'video'
  }
  if (type.includes('audio') || ['mp3', 'wav', 'ogg', 'aac', 'm4a'].includes(type)) {
    return 'audio'
  }
  return 'document'
})

const canDownload = computed(() => {
  return !!(props.media?.url)
})

// Methods
const getMediaIcon = () => {
  switch (mediaType.value) {
    case 'image':
      return PhotoIcon
    case 'video':
      return VideoCameraIcon
    case 'audio':
      return MusicalNoteIcon
    default:
      return DocumentIcon
  }
}

const getMediaTypeLabel = () => {
  const type = props.media?.type || 'Unknown'
  return type.toUpperCase()
}

const getFileExtension = () => {
  if (!props.media?.url) return 'Unknown'
  const parts = props.media.url.split('.')
  return parts.length > 1 ? parts.pop()?.toUpperCase() : 'Unknown'
}

const formatFileSize = (bytes?: number) => {
  if (!bytes) return 'Unknown'
  
  const sizes = ['Bytes', 'KB', 'MB', 'GB']
  const i = Math.floor(Math.log(bytes) / Math.log(1024))
  return `${(bytes / Math.pow(1024, i)).toFixed(1)} ${sizes[i]}`
}

const formatDuration = (seconds: number) => {
  if (!seconds || seconds === 0) return '0:00'
  
  const minutes = Math.floor(seconds / 60)
  const remainingSeconds = Math.floor(seconds % 60)
  return `${minutes}:${remainingSeconds.toString().padStart(2, '0')}`
}

const formatDate = (dateString: string) => {
  const date = new Date(dateString)
  return date.toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  })
}

const handleMediaLoad = () => {
  isLoading.value = false
  hasError.value = false
}

const handleMediaError = () => {
  isLoading.value = false
  hasError.value = true
  errorMessage.value = `Failed to load ${mediaType.value}. The file may be corrupted or unsupported.`
}

const handleVideoPlay = () => {
  // Video started playing
}

const handleVideoPause = () => {
  // Video paused
}

const handleAudioMetadata = () => {
  if (audioElement.value) {
    audioDuration.value = audioElement.value.duration || 0
  }
}

const toggleMute = () => {
  if (videoElement.value) {
    videoElement.value.muted = !videoElement.value.muted
    isVideoMuted.value = videoElement.value.muted
  }
}

const toggleFullscreen = () => {
  if (document.fullscreenElement) {
    document.exitFullscreen()
  } else {
    const element = videoElement.value || document.querySelector('.max-h-\\[70vh\\]')
    if (element) {
      element.requestFullscreen()
    }
  }
}

const closeModal = () => {
  // Stop any playing media
  if (videoElement.value) {
    videoElement.value.pause()
  }
  if (audioElement.value) {
    audioElement.value.pause()
  }
  
  emit('close')
}

const addToDesign = () => {
  if (props.media) {
    emit('addToDesign', props.media)
  }
}

const downloadMedia = () => {
  if (props.media) {
    emit('download', props.media)
  }
}

// Watch for media changes
watch(() => props.media, (newMedia) => {
  if (newMedia) {
    isLoading.value = true
    hasError.value = false
    errorMessage.value = ''
    audioDuration.value = 0
  }
}, { immediate: true })

// Watch for mediaType changes and handle document types
watch(mediaType, (newType) => {
  if (newType === 'document') {
    // Document types don't have loadable content, so clear loading immediately
    isLoading.value = false
    hasError.value = false
  }
}, { immediate: true })

// Watch for modal open/close
watch(() => props.isOpen, (isOpen) => {
  if (!isOpen) {
    // Reset state when modal closes
    isLoading.value = true
    hasError.value = false
    errorMessage.value = ''
    audioDuration.value = 0
  }
})

// Keyboard shortcuts
const handleKeydown = (event: KeyboardEvent) => {
  if (!props.isOpen) return
  
  switch (event.key) {
    case 'Escape':
      closeModal()
      break
    case ' ':
      if (mediaType.value === 'video' && videoElement.value) {
        event.preventDefault()
        if (videoElement.value.paused) {
          videoElement.value.play()
        } else {
          videoElement.value.pause()
        }
      }
      break
    case 'f':
    case 'F':
      if (mediaType.value === 'video' || mediaType.value === 'image') {
        event.preventDefault()
        toggleFullscreen()
      }
      break
    case 'm':
    case 'M':
      if (mediaType.value === 'video') {
        event.preventDefault()
        toggleMute()
      }
      break
  }
}

onMounted(() => {
  document.addEventListener('keydown', handleKeydown)
})

onUnmounted(() => {
  document.removeEventListener('keydown', handleKeydown)
})
</script>

<style scoped>
/* Custom scrollbar */
::-webkit-scrollbar {
  width: 6px;
}

::-webkit-scrollbar-track {
  background: #f1f5f9;
  border-radius: 8px;
}

::-webkit-scrollbar-thumb {
  background: linear-gradient(180deg, #6366f1, #8b5cf6);
  border-radius: 8px;
}

::-webkit-scrollbar-thumb:hover {
  background: linear-gradient(180deg, #4f46e5, #7c3aed);
}

/* Ensure video and audio elements are responsive */
video, audio {
  max-width: 100%;
  height: auto;
}

/* Fullscreen improvements */
:fullscreen {
  background: black;
  display: flex;
  align-items: center;
  justify-content: center;
}

:fullscreen video,
:fullscreen img {
  max-width: 100vw;
  max-height: 100vh;
  object-fit: contain;
}
</style>

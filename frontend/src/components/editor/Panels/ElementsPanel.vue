<template>
  <div class="h-full flex flex-col bg-white">
    <!-- Header -->
    <div class="p-4 border-b bg-gray-50">
      <h3 class="text-lg font-semibold text-gray-900">Elements</h3>
      <p class="text-sm text-gray-600 mt-1">Add text, images, and shapes to your design</p>
    </div>

    <!-- Hidden file input for image uploads -->
    <input
      ref="fileInput"
      type="file"
      accept="image/*"
      @change="handleFileUpload"
      class="hidden"
    />

    <!-- Content Area -->
    <div class="flex-1 overflow-y-auto">
      <!-- Quick Add Section -->
      <div class="p-4">
        <div class="grid grid-cols-2 gap-3">
          <button
            @click="addTextElement"
            class="group flex flex-col items-center p-6 border-2 border-dashed border-blue-200 rounded-xl hover:border-blue-400 hover:bg-blue-50 transition-all duration-200"
          >
            <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center mb-3 group-hover:bg-blue-200 group-hover:scale-110 transition-all duration-200">
              <!-- Custom "T" icon for text -->
              <svg class="w-6 h-6 text-blue-600" fill="currentColor" viewBox="0 0 24 24">
                <path d="M5 4v3h5.5v10h3V7H19V4H5z"/>
              </svg>
            </div>
            <span class="text-sm font-semibold text-gray-700 group-hover:text-blue-700">Add Text</span>
          </button>
          
          <button
            @click="addImagePlaceholder"
            :disabled="isUploading"
            class="group flex flex-col items-center p-6 border-2 border-dashed border-green-200 rounded-xl hover:border-green-400 hover:bg-green-50 transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            <div class="w-12 h-12 bg-green-100 rounded-xl flex items-center justify-center mb-3 group-hover:bg-green-200 group-hover:scale-110 transition-all duration-200">
              <div v-if="isUploading" class="w-6 h-6 border-2 border-green-600 border-t-transparent rounded-full animate-spin"></div>
              <svg v-else class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16L8.586 11.414C9.367 10.633 10.633 10.633 11.414 11.414L16 16M14 14L15.586 12.414C16.367 11.633 17.633 11.633 18.414 12.414L20 14M14 8H14.01M6 20H18C19.1046 20 20 19.1046 20 18V6C20 4.89543 19.1046 4 18 4H6C4.89543 4 4 4.89543 4 6V18C4 19.1046 4.89543 20 6 20Z" />
              </svg>
            </div>
            <span class="text-sm font-semibold text-gray-700 group-hover:text-green-700">
              {{ isUploading ? 'Uploading...' : 'Add Image' }}
            </span>
          </button>
        </div>
      </div>

      <!-- Shapes -->
      <div class="p-4 border-t">
        <h3 class="text-sm font-semibold text-gray-900 mb-4 flex items-center">
          <svg class="w-4 h-4 mr-2 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3" />
          </svg>
          Basic Shapes
        </h3>
        <div class="grid grid-cols-4 gap-3">
          <button
            v-for="shape in shapes"
            :key="shape.type"
            @click="addShape(shape.type)"
            class="group flex flex-col items-center p-4 rounded-lg border border-gray-200 hover:border-purple-300 hover:bg-purple-50 transition-all duration-200 hover:shadow-sm"
          >
            <div class="w-10 h-10 flex items-center justify-center mb-2 group-hover:scale-110 transition-transform duration-200">
              <div v-html="shape.icon" class="text-2xl text-gray-600 group-hover:text-purple-600"></div>
            </div>
            <span class="text-xs font-medium text-gray-700 group-hover:text-purple-700 text-center leading-tight">{{ shape.label }}</span>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
<script setup lang="ts">
import { ref, computed } from 'vue'
import type { LayerType } from '@/types'
import { mediaAPI } from '@/services/api'
import { useNotifications } from '@/composables/useNotifications'
import {
  DocumentTextIcon,
  PhotoIcon,
  Square3Stack3DIcon,
  PaintBrushIcon
} from '@heroicons/vue/24/outline'

interface ElementConfig {
  type: LayerType
  label: string
  icon: any
  defaultProps: any
}

interface ShapeConfig {
  type: string
  label: string
  icon: string
}

const emit = defineEmits<{
  'add-element': [type: LayerType, properties: any]
}>()

const { uploadFailed } = useNotifications()

const searchQuery = ref('')
const fileInput = ref<HTMLInputElement>()
const isUploading = ref(false)

// File validation constants
const MAX_FILE_SIZE = 10 * 1024 * 1024 // 10MB
const ALLOWED_MIME_TYPES = [
  'image/jpeg',
  'image/jpg', 
  'image/png',
  'image/gif',
  'image/webp',
  'image/svg+xml'
]

// Basic elements configuration
const basicElements: ElementConfig[] = [
  {
    type: 'text',
    label: 'Text',
    icon: DocumentTextIcon,
    defaultProps: {
      text: 'Add your text here',
      fontSize: 24,
      fontFamily: 'Inter',
      color: '#000000',
      fontWeight: 'normal',
      textAlign: 'left'
    }
  },
  {
    type: 'image',
    label: 'Image',
    icon: PhotoIcon,
    defaultProps: {
      src: 'https://picsum.photos/400/300',
      alt: 'Sample Image'
    }
  },
  {
    type: 'shape',
    label: 'Shape',
    icon: Square3Stack3DIcon,
    defaultProps: {
      shapeType: 'rectangle',
      fill: { type: 'solid', color: '#8B5CF6', opacity: 1 },
      stroke: '#7C3AED',
      strokeWidth: 2
    }
  }
]

// Shapes configuration
const shapes: ShapeConfig[] = [
  { type: 'rectangle', label: 'Rectangle', icon: '⬜' },
  { type: 'circle', label: 'Circle', icon: '⭕' },
  { type: 'triangle', label: 'Triangle', icon: '🔺' },
  { type: 'line', label: 'Line', icon: '📏' },
  { type: 'arrow', label: 'Arrow', icon: '➡️' },
  { type: 'polygon', label: 'Polygon', icon: '🔶' }
]

// Computed filtered arrays
const filteredBasicElements = computed(() => {
  if (!searchQuery.value.trim()) return basicElements
  return basicElements.filter(element => 
    element.label.toLowerCase().includes(searchQuery.value.toLowerCase())
  )
})

const filteredShapes = computed(() => {
  if (!searchQuery.value.trim()) return shapes
  return shapes.filter(shape => 
    shape.label.toLowerCase().includes(searchQuery.value.toLowerCase())
  )
})

// Methods
const addElement = (type: LayerType, properties: any = {}) => {
  emit('add-element', type, properties)
}

// Calculate appropriate dimensions for the design stage
const calculateDimensions = (originalWidth: number, originalHeight: number) => {
  const maxWidth = 600
  const maxHeight = 400
  
  const aspectRatio = originalWidth / originalHeight
  let width = originalWidth
  let height = originalHeight
  
  if (width > maxWidth) {
    width = maxWidth
    height = width / aspectRatio
  }
  
  if (height > maxHeight) {
    height = maxHeight
    width = height * aspectRatio
  }
  
  return { width: Math.round(width), height: Math.round(height) }
}

// Validate uploaded file
const validateFile = (file: File): string | null => {
  // Check file size
  if (file.size > MAX_FILE_SIZE) {
    return `File size (${(file.size / 1024 / 1024).toFixed(1)}MB) exceeds maximum allowed size of ${MAX_FILE_SIZE / 1024 / 1024}MB`
  }
  
  // Check file type
  if (!ALLOWED_MIME_TYPES.includes(file.type)) {
    return `File type "${file.type}" is not supported. Please upload a JPEG, PNG, GIF, WebP, or SVG image.`
  }
  
  return null // No validation errors
}

const addShape = (shapeType: string) => {
  const shapeProps = {
    shapeType,
    fill: {
      type: 'solid',
      color: '#8B5CF6',
      opacity: 1
    },
    stroke: '#7C3AED',
    strokeWidth: 2,
    cornerRadius: 0
  }

  emit('add-element', 'shape', shapeProps)
}

const addTextElement = () => {
  addElement('text', {
    text: 'Click to edit text',
    fontSize: 32,
    fontFamily: 'Inter',
    color: '#1F2937',
    fontWeight: '600',
    textAlign: 'left'
  })
}

const addImagePlaceholder = () => {
  // Trigger file input instead of using placeholder image
  fileInput.value?.click()
}

const handleFileUpload = async (event: Event) => {
  const target = event.target as HTMLInputElement
  const files = target.files
  
  if (!files || files.length === 0) return
  
  try {
    const file = files[0] // Only handle the first file
    
    // Validate file before upload
    const validationError = validateFile(file)
    if (validationError) {
      uploadFailed(validationError)
      target.value = '' // Reset input
      return
    }
    
    isUploading.value = true
    
    const response = await mediaAPI.uploadMedia(file, {
      name: file.name
    })
    
    if (response.data?.data?.media) {
      const uploadedMedia = response.data.data.media
      const dimensions = calculateDimensions(uploadedMedia.width || 400, uploadedMedia.height || 400)
      
      // Add the uploaded image to the design
      emit('add-element', 'image', {
        src: uploadedMedia.url || uploadedMedia.thumbnailUrl,
        alt: uploadedMedia.name || 'Uploaded image',
        ...dimensions
      })
    }
    
    // Reset input
    target.value = ''
  } catch (error) {
    console.error('Failed to upload image:', error)
    const errorMessage = error instanceof Error ? error.message : 'Failed to upload image. Please try again.'
    uploadFailed(errorMessage)
    target.value = ''
  } finally {
    isUploading.value = false
  }
}
</script>

<template>
  <div class="h-full flex flex-col bg-gray-50 dark:bg-gray-800">
    <!-- Header -->
    <div class="p-4 border-b border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900">
      <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Elements</h3>
      <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Add text, images, and shapes to your design</p>
    </div>

    <!-- Content Area -->
    <div class="flex-1 overflow-y-auto">
      <!-- Quick Add Section -->
      <div class="p-4">
        <div class="grid grid-cols-2 gap-3">
          <button
            @click="addTextElement"
            class="group flex flex-col items-center p-6 border-2 border-dashed border-primary-200 dark:border-primary-700 rounded-xl hover:border-primary-400 dark:hover:border-primary-500 hover:bg-primary-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-primary-500 dark:focus:ring-primary-400 focus:ring-offset-2 dark:focus:ring-offset-gray-800 active:scale-95 active:bg-primary-100 dark:active:bg-gray-600 transition-all duration-200"
          >
            <div class="w-12 h-12 bg-primary-100 dark:bg-gray-700 rounded-xl flex items-center justify-center mb-3 group-hover:bg-primary-200 dark:group-hover:bg-gray-600 group-hover:scale-110 transition-all duration-200">
              <!-- Custom "T" icon for text -->
              <svg class="w-6 h-6 text-primary-600 dark:text-primary-400" fill="currentColor" viewBox="0 0 24 24">
                <path d="M5 4v3h5.5v10h3V7H19V4H5z"/>
              </svg>
            </div>
            <span class="text-sm font-semibold text-gray-700 dark:text-gray-300 group-hover:text-primary-700 dark:group-hover:text-primary-300">Add Text</span>
          </button>
          
          <button
            @click="addImagePlaceholder"
            class="group flex flex-col items-center p-6 border-2 border-dashed border-primary-200 dark:border-primary-700 rounded-xl hover:border-primary-400 dark:hover:border-primary-500 hover:bg-primary-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-primary-500 dark:focus:ring-primary-400 focus:ring-offset-2 dark:focus:ring-offset-gray-800 active:scale-95 active:bg-primary-100 dark:active:bg-gray-600 transition-all duration-200"
          >
            <div class="w-12 h-12 bg-primary-100 dark:bg-gray-700 rounded-xl flex items-center justify-center mb-3 group-hover:bg-primary-200 dark:group-hover:bg-gray-600 group-hover:scale-110 transition-all duration-200">
              <svg class="w-6 h-6 text-primary-600 dark:text-primary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16L8.586 11.414C9.367 10.633 10.633 10.633 11.414 11.414L16 16M14 14L15.586 12.414C16.367 11.633 17.633 11.633 18.414 12.414L20 14M14 8H14.01M6 20H18C19.1046 20 20 19.1046 20 18V6C20 4.89543 19.1046 4 18 4H6C4.89543 4 4 4.89543 4 6V18C4 19.1046 4.89543 20 6 20Z" />
              </svg>
            </div>
            <span class="text-sm font-semibold text-gray-700 dark:text-gray-300 group-hover:text-primary-700 dark:group-hover:text-primary-300">Add Image</span>
          </button>
        </div>
      </div>

      <!-- Basic Elements -->
      <div class="p-3">
        <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-3 flex items-center">
          <svg class="w-4 h-4 mr-2 text-primary-600 dark:text-primary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
          </svg>
          Elements
        </h3>
        <div class="grid grid-cols-3 gap-2">
          <button
            v-for="element in filteredBasicElements"
            :key="element.type"
            @click="addElement(element.type, element.defaultProps)"
            class="group flex flex-col items-center p-3 rounded-lg border border-gray-200 dark:border-gray-600 hover:border-primary-300 dark:hover:border-primary-500 hover:bg-primary-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-primary-500 dark:focus:ring-primary-400 focus:ring-offset-2 dark:focus:ring-offset-gray-800 active:scale-95 active:bg-primary-100 dark:active:bg-gray-600 transition-all duration-200 hover:shadow-sm"
          >
            <div class="w-8 h-8 bg-gray-100 dark:bg-gray-700 rounded-lg flex items-center justify-center mb-2 group-hover:bg-primary-100 dark:group-hover:bg-gray-600">
              <component :is="element.icon" class="w-4 h-4 text-gray-600 dark:text-gray-400 group-hover:text-primary-600 dark:group-hover:text-primary-400" />
            </div>
            <span class="text-xs font-medium text-gray-700 dark:text-gray-300 group-hover:text-primary-700 dark:group-hover:text-primary-300 text-center leading-tight">{{ element.label }}</span>
          </button>
        </div>
      </div>

      <!-- Shapes -->
      <div class="p-4 border-t border-gray-200 dark:border-gray-700">
        <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-4 flex items-center">
          <svg class="w-4 h-4 mr-2 text-primary-600 dark:text-primary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3" />
          </svg>
          Basic Shapes
        </h3>
        <div class="grid grid-cols-4 gap-3">
          <button
            v-for="shape in shapes"
            :key="shape.type"
            @click="addShape(shape.type)"
            class="group flex flex-col items-center p-4 rounded-lg border border-gray-200 dark:border-gray-600 hover:border-primary-300 dark:hover:border-primary-500 hover:bg-primary-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-primary-500 dark:focus:ring-primary-400 focus:ring-offset-2 dark:focus:ring-offset-gray-800 active:scale-95 active:bg-primary-100 dark:active:bg-gray-600 transition-all duration-200 hover:shadow-sm"
          >
            <div class="w-10 h-10 flex items-center justify-center mb-2 group-hover:scale-110 transition-transform duration-200">
              <div v-html="shape.icon" class="text-2xl text-gray-600 dark:text-gray-400 group-hover:text-primary-600 dark:group-hover:text-primary-400"></div>
            </div>
            <span class="text-xs font-medium text-gray-700 dark:text-gray-300 group-hover:text-primary-700 dark:group-hover:text-primary-300 text-center leading-tight">{{ shape.label }}</span>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
<script setup lang="ts">
import { ref, computed } from 'vue'
import type { LayerType } from '@/types'
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

const searchQuery = ref('')

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
  addElement('image', {
    src: 'https://picsum.photos/400/300',
    alt: 'Image Placeholder'
  })
}
</script>

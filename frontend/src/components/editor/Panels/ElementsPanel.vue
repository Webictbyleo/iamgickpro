<template>
  <div class="h-full flex flex-col bg-white">
    <!-- Search Bar -->
    <div class="p-3 border-b bg-gray-50">
      <div class="relative">
        <input
          v-model="searchQuery"
          type="text" 
          placeholder="Search elements..."
          class="w-full pl-8 pr-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent"
        />
        <svg class="absolute left-2.5 top-2.5 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
        </svg>
      </div>
    </div>

    <!-- Content Area -->
    <div class="flex-1 overflow-y-auto">
      <!-- Quick Add Section -->
      <div class="p-3 border-b">
        <div class="grid grid-cols-2 gap-2">
          <button
            @click="addTextElement"
            class="group flex flex-col items-center p-4 border-2 border-dashed border-purple-200 rounded-xl hover:border-purple-400 hover:bg-purple-50 transition-all duration-200"
          >
            <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center mb-2 group-hover:bg-purple-200">
              <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
              </svg>
            </div>
            <span class="text-sm font-medium text-gray-700 group-hover:text-purple-700">Add Text</span>
          </button>
          
          <button
            @click="addImagePlaceholder"
            class="group flex flex-col items-center p-4 border-2 border-dashed border-purple-200 rounded-xl hover:border-purple-400 hover:bg-purple-50 transition-all duration-200"
          >
            <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center mb-2 group-hover:bg-purple-200">
              <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
              </svg>
            </div>
            <span class="text-sm font-medium text-gray-700 group-hover:text-purple-700">Add Image</span>
          </button>
        </div>
      </div>

      <!-- Basic Elements -->
      <div class="p-3">
        <h3 class="text-sm font-semibold text-gray-900 mb-3 flex items-center">
          <svg class="w-4 h-4 mr-2 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
          </svg>
          Elements
        </h3>
        <div class="grid grid-cols-3 gap-2">
          <button
            v-for="element in filteredBasicElements"
            :key="element.type"
            @click="addElement(element.type, element.defaultProps)"
            class="group flex flex-col items-center p-3 rounded-lg border border-gray-100 hover:border-purple-300 hover:bg-purple-50 transition-all duration-200 hover:shadow-sm"
          >
            <div class="w-8 h-8 bg-gray-100 rounded-lg flex items-center justify-center mb-2 group-hover:bg-purple-100">
              <component :is="element.icon" class="w-4 h-4 text-gray-600 group-hover:text-purple-600" />
            </div>
            <span class="text-xs font-medium text-gray-700 group-hover:text-purple-700 text-center leading-tight">{{ element.label }}</span>
          </button>
        </div>
      </div>

      <!-- Shapes -->
      <div class="p-3 border-t">
        <h3 class="text-sm font-semibold text-gray-900 mb-3 flex items-center">
          <svg class="w-4 h-4 mr-2 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
          </svg>
          Shapes
        </h3>
        <div class="grid grid-cols-4 gap-2">
          <button
            v-for="shape in filteredShapes"
            :key="shape.type"
            @click="addShape(shape.type)"
            class="group flex flex-col items-center p-3 rounded-lg border border-gray-100 hover:border-purple-300 hover:bg-purple-50 transition-all duration-200 hover:shadow-sm"
          >
            <div class="w-8 h-8 flex items-center justify-center text-lg mb-2 group-hover:text-purple-600">
              {{ shape.icon }}
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
  { type: 'star', label: 'Star', icon: '⭐' },
  { type: 'polygon', label: 'Polygon', icon: '🔶' },
  { type: 'heart', label: 'Heart', icon: '💙' }
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

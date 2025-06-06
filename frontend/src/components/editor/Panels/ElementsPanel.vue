<template>
  <div class="p-4 space-y-6">
    <!-- Basic Elements -->
    <div>
      <h3 class="text-sm font-semibold text-gray-900 mb-3">Basic Elements</h3>
      <div class="grid grid-cols-2 gap-2">
        <button
          v-for="element in basicElements"
          :key="element.type"
          @click="addElement(element.type, element.defaultProps)"
          class="flex flex-col items-center p-3 border border-gray-200 rounded-lg hover:border-blue-300 hover:bg-blue-50 transition-colors group"
        >
          <component :is="element.icon" class="w-8 h-8 text-gray-600 group-hover:text-blue-600 mb-2" />
          <span class="text-xs font-medium text-gray-700 group-hover:text-blue-700">{{ element.label }}</span>
        </button>
      </div>
    </div>

    <!-- Shapes -->
    <div>
      <h3 class="text-sm font-semibold text-gray-900 mb-3">Shapes</h3>
      <div class="grid grid-cols-3 gap-2">
        <button
          v-for="shape in shapes"
          :key="shape.type"
          @click="addShape(shape.type)"
          class="flex flex-col items-center p-3 border border-gray-200 rounded-lg hover:border-blue-300 hover:bg-blue-50 transition-colors group"
        >
          <div class="w-8 h-8 flex items-center justify-center text-2xl mb-2 group-hover:text-blue-600">
            {{ shape.icon }}
          </div>
          <span class="text-xs font-medium text-gray-700 group-hover:text-blue-700">{{ shape.label }}</span>
        </button>
      </div>
    </div>

    <!-- Quick Actions -->
    <div>
      <h3 class="text-sm font-semibold text-gray-900 mb-3">Quick Actions</h3>
      <div class="space-y-2">
        <BaseButton
          variant="outline"
          size="sm"
          full-width
          @click="addTextElement"
        >
          <DocumentTextIcon class="w-4 h-4 mr-2" />
          Add Text
        </BaseButton>
        
        <BaseButton
          variant="outline"
          size="sm"
          full-width
          @click="triggerImageUpload"
        >
          <PhotoIcon class="w-4 h-4 mr-2" />
          Upload Image
        </BaseButton>
        
        <BaseButton
          variant="outline"
          size="sm"
          full-width
          @click="addShapeElement"
        >
          <Square3Stack3DIcon class="w-4 h-4 mr-2" />
          Add Shape
        </BaseButton>
      </div>
    </div>

    <!-- Templates -->
    <div>
      <h3 class="text-sm font-semibold text-gray-900 mb-3">Templates</h3>
      <div class="grid grid-cols-2 gap-2">
        <button
          v-for="template in templates"
          :key="template.id"
          @click="applyTemplate(template)"
          class="relative overflow-hidden border border-gray-200 rounded-lg hover:border-blue-300 transition-colors group aspect-square"
        >
          <img
            :src="template.thumbnail"
            :alt="template.name"
            class="w-full h-full object-cover"
          />
          <div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-20 transition-all">
            <div class="absolute bottom-0 left-0 right-0 p-2 text-white opacity-0 group-hover:opacity-100 transition-opacity">
              <span class="text-xs font-medium">{{ template.name }}</span>
            </div>
          </div>
        </button>
      </div>
    </div>

    <!-- Hidden file input for image uploads -->
    <input
      ref="imageInput"
      type="file"
      accept="image/*"
      class="hidden"
      @change="handleImageUpload"
    />
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import BaseButton from '@/components/common/BaseButton.vue'
import {
  DocumentTextIcon,
  PhotoIcon,
  Square3Stack3DIcon,
  PaintBrushIcon
} from '@heroicons/vue/24/outline'
import type { LayerType } from '@/types'

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

interface Template {
  id: string
  name: string
  thumbnail: string
  data: any
}

const emit = defineEmits<{
  addElement: [type: LayerType, properties: any]
}>()

const imageInput = ref<HTMLInputElement>()

// Basic elements configuration
const basicElements: ElementConfig[] = [
  {
    type: 'text',
    label: 'Text',
    icon: DocumentTextIcon,
    defaultProps: {
      text: 'Add your text here',
      fontSize: 24,
      fontFamily: 'Arial',
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
      alt: 'Sample Image',
      objectFit: 'cover'
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
  { type: 'star', label: 'Star', icon: '⭐' }
]

// Sample templates
const templates: Template[] = [
  {
    id: 'social-post',
    name: 'Social Post',
    thumbnail: 'https://picsum.photos/200/200?random=1',
    data: {}
  },
  {
    id: 'business-card',
    name: 'Business Card',
    thumbnail: 'https://picsum.photos/200/200?random=2',
    data: {}
  },
  {
    id: 'flyer',
    name: 'Flyer',
    thumbnail: 'https://picsum.photos/200/200?random=3',
    data: {}
  },
  {
    id: 'poster',
    name: 'Poster',
    thumbnail: 'https://picsum.photos/200/200?random=4',
    data: {}
  }
]

const addElement = (type: LayerType, properties: any = {}) => {
  emit('addElement', type, properties)
}

const addShape = (shapeType: string) => {
  const shapeProps = {
    shapeType,
    fill: {
      type: 'solid',
      color: '#3B82F6',
      opacity: 1
    },
    stroke: '#1E40AF',
    strokeWidth: 2,
    cornerRadius: shapeType === 'rectangle' ? 8 : 0
  }

  emit('addElement', 'shape', shapeProps)
}

const addTextElement = () => {
  addElement('text', {
    text: 'Click to edit text',
    fontSize: 24,
    fontFamily: 'Arial',
    color: '#000000',
    fontWeight: 'normal',
    textAlign: 'left'
  })
}

const addShapeElement = () => {
  addShape('rectangle')
}

const triggerImageUpload = () => {
  imageInput.value?.click()
}

const handleImageUpload = (event: Event) => {
  const target = event.target as HTMLInputElement
  const file = target.files?.[0]
  
  if (file) {
    const reader = new FileReader()
    reader.onload = (e) => {
      const src = e.target?.result as string
      addElement('image', {
        src,
        alt: file.name,
        objectFit: 'cover'
      })
    }
    reader.readAsDataURL(file)
    
    // Reset input
    target.value = ''
  }
}

const applyTemplate = (template: Template) => {
  // TODO: Implement template application
  console.log('Apply template:', template.name)
}
</script>

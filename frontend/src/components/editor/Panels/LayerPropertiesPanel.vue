<template>
  <div class="p-4">
    <div v-if="selectedLayers.length === 0" class="text-center py-12 text-gray-500">
      <svg class="w-12 h-12 mx-auto mb-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5M7.188 2.239l.777 2.897M5.136 7.965l-2.898-.777M13.95 4.05l-2.122 2.122m-5.657 5.656l-2.12 2.122"></path>
      </svg>
      <p class="text-sm">No layer selected</p>
      <p class="text-xs mt-1">Select a layer to edit properties</p>
    </div>
    
    <div v-else class="space-y-6">
      <!-- Multi-selection info -->
      <div v-if="selectedLayers.length > 1" class="p-3 bg-blue-50 rounded-lg">
        <div class="text-sm font-medium text-blue-900">{{ selectedLayers.length }} layers selected</div>
        <div class="text-xs text-blue-700 mt-1">Common properties shown below</div>
      </div>
      
      <!-- Transform Properties -->
      <div>
        <h3 class="text-sm font-medium text-gray-900 mb-3">Transform</h3>
        <div class="space-y-3">
          <!-- Position -->
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-xs font-medium text-gray-700 mb-1">X</label>
              <input
                :value="getCommonProperty('x')"
                @input="handleNumberInput('x', $event)"
                type="number"
                class="w-full px-2 py-1 text-sm border border-gray-300 rounded focus:ring-1 focus:ring-blue-500 focus:border-blue-500"
              />
            </div>
            <div>
              <label class="block text-xs font-medium text-gray-700 mb-1">Y</label>
              <input
                :value="getCommonProperty('y')"
                @input="handleNumberInput('y', $event)"
                type="number"
                class="w-full px-2 py-1 text-sm border border-gray-300 rounded focus:ring-1 focus:ring-blue-500 focus:border-blue-500"
              />
            </div>
          </div>
          
          <!-- Size -->
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-xs font-medium text-gray-700 mb-1">Width</label>
              <input
                :value="getCommonProperty('width')"
                @input="handleNumberInput('width', $event)"
                type="number"
                min="1"
                class="w-full px-2 py-1 text-sm border border-gray-300 rounded focus:ring-1 focus:ring-blue-500 focus:border-blue-500"
              />
            </div>
            <div>
              <label class="block text-xs font-medium text-gray-700 mb-1">Height</label>
              <input
                :value="getCommonProperty('height')"
                @input="handleNumberInput('height', $event)"
                type="number"
                min="1"
                class="w-full px-2 py-1 text-sm border border-gray-300 rounded focus:ring-1 focus:ring-blue-500 focus:border-blue-500"
              />
            </div>
          </div>
          
          <!-- Rotation -->
          <div>
            <label class="block text-xs font-medium text-gray-700 mb-1">Rotation (degrees)</label>
            <input
              :value="getCommonProperty('rotation')"
              @input="handleNumberInput('rotation', $event)"
              type="number"
              min="-360"
              max="360"
              class="w-full px-2 py-1 text-sm border border-gray-300 rounded focus:ring-1 focus:ring-blue-500 focus:border-blue-500"
            />
          </div>
          
          <!-- Opacity -->
          <div>
            <label class="block text-xs font-medium text-gray-700 mb-1">Opacity</label>
            <input
              :value="getCommonProperty('opacity')"
              @input="handleNumberInput('opacity', $event)"
              type="range"
              min="0"
              max="1"
              step="0.01"
              class="w-full"
            />
            <div class="text-xs text-gray-500 text-center mt-1">
              {{ Math.round((getCommonProperty('opacity') || 1) * 100) }}%
            </div>
          </div>
        </div>
      </div>

      <!-- Layer Settings -->
      <div>
        <h3 class="text-sm font-medium text-gray-900 mb-3">Layer</h3>
        <div class="space-y-3">
          <!-- Layer Name -->
          <div>
            <label class="block text-xs font-medium text-gray-700 mb-1">Name</label>
            <input
              :value="getCommonProperty('name')"
              @input="handleTextInput('name', $event)"
              type="text"
              class="w-full px-2 py-1 text-sm border border-gray-300 rounded focus:ring-1 focus:ring-blue-500 focus:border-blue-500"
            />
          </div>
          
          <!-- Visibility and Lock -->
          <div class="flex space-x-3">
            <label class="flex items-center">
              <input
                :checked="getCommonProperty('visible')"
                @change="handleCheckboxChange('visible', $event)"
                type="checkbox"
                class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
              />
              <span class="ml-2 text-sm text-gray-700">Visible</span>
            </label>
            
            <label class="flex items-center">
              <input
                :checked="getCommonProperty('locked')"
                @change="handleCheckboxChange('locked', $event)"
                type="checkbox"
                class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
              />
              <span class="ml-2 text-sm text-gray-700">Locked</span>
            </label>
          </div>
        </div>
      </div>

      <!-- Type-specific Properties -->
      <div v-if="primaryLayer">
        <!-- Text Properties -->
        <div v-if="primaryLayer.type === 'text'" class="space-y-4">
          <h3 class="text-sm font-medium text-gray-900 mb-3">Text</h3>
          
          <!-- Text Content -->
          <div>
            <label class="block text-xs font-medium text-gray-700 mb-1">Text</label>
            <textarea
              :value="primaryLayer.properties.text"
              @input="handleTextPropertyInput('text', $event)"
              rows="3"
              class="w-full px-2 py-1 text-sm border border-gray-300 rounded focus:ring-1 focus:ring-blue-500 focus:border-blue-500"
            />
          </div>
          
          <!-- Font Family -->
          <div>
            <label class="block text-xs font-medium text-gray-700 mb-1">Font Family</label>
            <select
              :value="primaryLayer.properties.fontFamily"
              @change="handleTextPropertySelect('fontFamily', $event)"
              class="w-full px-2 py-1 text-sm border border-gray-300 rounded focus:ring-1 focus:ring-blue-500 focus:border-blue-500"
            >
              <option value="Arial">Arial</option>
              <option value="Helvetica">Helvetica</option>
              <option value="Times New Roman">Times New Roman</option>
              <option value="Georgia">Georgia</option>
              <option value="Verdana">Verdana</option>
              <option value="Courier New">Courier New</option>
            </select>
          </div>
          
          <!-- Font Size and Weight -->
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-xs font-medium text-gray-700 mb-1">Size</label>
              <input
                :value="primaryLayer.properties.fontSize"
                @input="handleTextPropertyNumber('fontSize', $event)"
                type="number"
                min="8"
                max="200"
                class="w-full px-2 py-1 text-sm border border-gray-300 rounded focus:ring-1 focus:ring-blue-500 focus:border-blue-500"
              />
            </div>
            <div>
              <label class="block text-xs font-medium text-gray-700 mb-1">Weight</label>
              <select
                :value="primaryLayer.properties.fontWeight"
                @change="handleTextPropertySelect('fontWeight', $event)"
                class="w-full px-2 py-1 text-sm border border-gray-300 rounded focus:ring-1 focus:ring-blue-500 focus:border-blue-500"
              >
                <option value="normal">Normal</option>
                <option value="bold">Bold</option>
                <option value="lighter">Lighter</option>
                <option value="bolder">Bolder</option>
              </select>
            </div>
          </div>
          
          <!-- Text Color -->
          <div>
            <label class="block text-xs font-medium text-gray-700 mb-1">Color</label>
            <div class="flex space-x-2">
              <input
                :value="primaryLayer.properties.color"
                @input="handleTextPropertyInput('color', $event)"
                type="color"
                class="w-12 h-8 border border-gray-300 rounded cursor-pointer"
              />
              <input
                :value="primaryLayer.properties.color"
                @input="handleTextPropertyInput('color', $event)"
                type="text"
                class="flex-1 px-2 py-1 text-sm border border-gray-300 rounded focus:ring-1 focus:ring-blue-500 focus:border-blue-500"
              />
            </div>
          </div>
          
          <!-- Text Alignment -->
          <div>
            <label class="block text-xs font-medium text-gray-700 mb-1">Alignment</label>
            <div class="flex space-x-1">
              <button
                v-for="align in ['left', 'center', 'right']"
                :key="align"
                @click="updateTextProperty('textAlign', align)"
                :class="[
                  'flex-1 px-2 py-1 text-xs border rounded transition-colors',
                  primaryLayer.properties.textAlign === align
                    ? 'bg-blue-600 text-white border-blue-600'
                    : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50'
                ]"
              >
                {{ align.charAt(0).toUpperCase() + align.slice(1) }}
              </button>
            </div>
          </div>
        </div>

        <!-- Shape Properties -->
        <div v-if="primaryLayer.type === 'shape'" class="space-y-4">
          <h3 class="text-sm font-medium text-gray-900 mb-3">Shape</h3>
          
          <!-- Fill Color -->
          <div>
            <label class="block text-xs font-medium text-gray-700 mb-1">Fill Color</label>
            <div class="flex space-x-2">
              <input
                :value="primaryLayer.properties.fill?.color || '#3B82F6'"
                @input="handleShapePropertyInput('fill', $event)"
                type="color"
                class="w-12 h-8 border border-gray-300 rounded cursor-pointer"
              />
              <input
                :value="primaryLayer.properties.fill?.color || '#3B82F6'"
                @input="handleShapePropertyInput('fill', $event)"
                type="text"
                class="flex-1 px-2 py-1 text-sm border border-gray-300 rounded focus:ring-1 focus:ring-blue-500 focus:border-blue-500"
              />
            </div>
          </div>
          
          <!-- Stroke -->
          <div class="space-y-2">
            <label class="block text-xs font-medium text-gray-700">Stroke</label>
            <div class="flex space-x-2">
              <input
                :value="primaryLayer.properties.stroke || '#1E40AF'"
                @input="handleShapePropertyInput('stroke', $event)"
                type="color"
                class="w-12 h-8 border border-gray-300 rounded cursor-pointer"
              />
              <input
                :value="primaryLayer.properties.strokeWidth || 2"
                @input="handleShapePropertyNumber('strokeWidth', $event)"
                type="number"
                min="0"
                max="20"
                placeholder="Width"
                class="flex-1 px-2 py-1 text-sm border border-gray-300 rounded focus:ring-1 focus:ring-blue-500 focus:border-blue-500"
              />
            </div>
          </div>
          
          <!-- Corner Radius (for rectangles) -->
          <div v-if="primaryLayer.properties.shapeType === 'rectangle'">
            <label class="block text-xs font-medium text-gray-700 mb-1">Corner Radius</label>
            <input
              :value="primaryLayer.properties.cornerRadius || 0"
              @input="handleShapePropertyNumber('cornerRadius', $event)"
              type="number"
              min="0"
              max="100"
              class="w-full px-2 py-1 text-sm border border-gray-300 rounded focus:ring-1 focus:ring-blue-500 focus:border-blue-500"
            />
          </div>
        </div>

        <!-- Image Properties -->
        <div v-if="primaryLayer.type === 'image'" class="space-y-4">
          <h3 class="text-sm font-medium text-gray-900 mb-3">Image</h3>
          
          <!-- Alt Text -->
          <div>
            <label class="block text-xs font-medium text-gray-700 mb-1">Alt Text</label>
            <input
              :value="primaryLayer.properties.alt"
              @input="handleImagePropertyInput('alt', $event)"
              type="text"
              class="w-full px-2 py-1 text-sm border border-gray-300 rounded focus:ring-1 focus:ring-blue-500 focus:border-blue-500"
            />
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import type { Layer } from '@/types'

interface Props {
  selectedLayers: Layer[]
}

const props = defineProps<Props>()

const emit = defineEmits<{
  updateProperties: [layerId: string, properties: Partial<Layer>]
}>()

const primaryLayer = computed(() => props.selectedLayers[0] || null)

const getCommonProperty = (property: string): any => {
  if (props.selectedLayers.length === 0) return ''
  
  const firstValue = (props.selectedLayers[0] as any)[property]
  const allSame = props.selectedLayers.every(layer => (layer as any)[property] === firstValue)
  
  return allSame ? firstValue : ''
}

// Safe event handlers
const handleNumberInput = (property: string, event: Event) => {
  const target = event.target as HTMLInputElement
  if (target) {
    const value = parseFloat(target.value)
    updateProperty(property, value)
  }
}

const handleTextInput = (property: string, event: Event) => {
  const target = event.target as HTMLInputElement
  if (target) {
    updateProperty(property, target.value)
  }
}

const handleCheckboxChange = (property: string, event: Event) => {
  const target = event.target as HTMLInputElement
  if (target) {
    updateProperty(property, target.checked)
  }
}

const handleSelectChange = (property: string, event: Event) => {
  const target = event.target as HTMLSelectElement
  if (target) {
    updateProperty(property, target.value)
  }
}

const handleTextPropertyInput = (property: string, event: Event) => {
  const target = event.target as HTMLInputElement
  if (target) {
    updateTextProperty(property, target.value)
  }
}

const handleTextPropertyNumber = (property: string, event: Event) => {
  const target = event.target as HTMLInputElement
  if (target) {
    updateTextProperty(property, parseInt(target.value))
  }
}

const handleShapePropertyInput = (property: string, event: Event) => {
  const target = event.target as HTMLInputElement
  if (target) {
    if (property === 'fill') {
      // Handle fill color specially
      const currentFill = primaryLayer.value?.properties.fill || {}
      updateShapeProperty('fill', { ...currentFill, color: target.value })
    } else {
      updateShapeProperty(property, target.value)
    }
  }
}

const handleShapePropertyNumber = (property: string, event: Event) => {
  const target = event.target as HTMLInputElement
  if (target) {
    updateShapeProperty(property, parseInt(target.value))
  }
}

const handleImagePropertyInput = (property: string, event: Event) => {
  const target = event.target as HTMLInputElement
  if (target) {
    updateImageProperty(property, target.value)
  }
}

const handleTextPropertySelect = (property: string, event: Event) => {
  const target = event.target as HTMLSelectElement
  if (target) {
    updateTextProperty(property, target.value)
  }
}

const handleImagePropertySelect = (property: string, event: Event) => {
  const target = event.target as HTMLSelectElement
  if (target) {
    updateImageProperty(property, target.value)
  }
}

const updateProperty = (property: string, value: any) => {
  props.selectedLayers.forEach(layer => {
    emit('updateProperties', layer.id, { [property]: value })
  })
}

const updateTextProperty = (property: string, value: any) => {
  if (!primaryLayer.value || primaryLayer.value.type !== 'text') return
  
  const newProperties = {
    ...primaryLayer.value.properties,
    [property]: value
  }
  
  emit('updateProperties', primaryLayer.value.id, { properties: newProperties })
}

const updateShapeProperty = (property: string, value: any) => {
  if (!primaryLayer.value || primaryLayer.value.type !== 'shape') return
  
  const newProperties = {
    ...primaryLayer.value.properties,
    [property]: value
  }
  
  emit('updateProperties', primaryLayer.value.id, { properties: newProperties })
}

const updateImageProperty = (property: string, value: any) => {
  if (!primaryLayer.value || primaryLayer.value.type !== 'image') return
  
  const newProperties = {
    ...primaryLayer.value.properties,
    [property]: value
  }
  
  emit('updateProperties', primaryLayer.value.id, { properties: newProperties })
}
</script>

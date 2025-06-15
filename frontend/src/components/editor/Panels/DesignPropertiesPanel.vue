<template>
  <div class="p-4 space-y-6">
    <!-- Canvas Properties -->
    <div>
      <h3 class="text-sm font-medium text-gray-900 mb-3">Canvas</h3>
      <div class="space-y-4">
        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="block text-xs font-medium text-gray-700 mb-1">Width</label>
            <input
              :value="canvasWidth"
              @input="updateCanvasWidth"
              type="number"
              min="1"
              max="10000"
              class="input w-full text-sm"
            />
          </div>
          <div>
            <label class="block text-xs font-medium text-gray-700 mb-1">Height</label>
            <input
              :value="canvasHeight"
              @input="updateCanvasHeight"
              type="number"
              min="1"
              max="10000"
              class="input w-full text-sm"
            />
          </div>
        </div>
        
        <div>
          <label class="block text-xs font-medium text-gray-700 mb-1">Background</label>
          
          <!-- Background Type Selector -->
          <div class="mb-3">
            <div class="flex space-x-1 p-1 bg-gray-100 rounded-lg">
              <button
                @click="setBackgroundType('solid')"
                :class="[
                  'flex-1 py-1 px-2 text-xs font-medium rounded-md transition-colors',
                  backgroundType === 'solid'
                    ? 'bg-white text-gray-900 shadow-sm'
                    : 'text-gray-600 hover:text-gray-900'
                ]"
              >
                Solid
              </button>
              <button
                @click="setBackgroundType('linear')"
                :class="[
                  'flex-1 py-1 px-2 text-xs font-medium rounded-md transition-colors',
                  backgroundType === 'linear'
                    ? 'bg-white text-gray-900 shadow-sm'
                    : 'text-gray-600 hover:text-gray-900'
                ]"
              >
                Linear
              </button>
              <button
                @click="setBackgroundType('radial')"
                :class="[
                  'flex-1 py-1 px-2 text-xs font-medium rounded-md transition-colors',
                  backgroundType === 'radial'
                    ? 'bg-white text-gray-900 shadow-sm'
                    : 'text-gray-600 hover:text-gray-900'
                ]"
              >
                Radial
              </button>
            </div>
          </div>

          <!-- Solid Color Background -->
          <div v-if="backgroundType === 'solid'" class="flex items-center space-x-2">
            <input
              :value="solidBackgroundColor"
              @input="updateSolidBackgroundColor"
              type="color"
              class="w-12 h-8 rounded border border-gray-300 cursor-pointer"
            />
            <input
              :value="solidBackgroundColor"
              @input="updateSolidBackgroundColor"
              type="text"
              class="input flex-1 text-sm"
              placeholder="#ffffff"
            />
          </div>

          <!-- Gradient Background -->
          <div v-else class="space-y-3">
            <!-- Gradient Direction (Linear only) -->
            <div v-if="backgroundType === 'linear'">
              <label class="block text-xs font-medium text-gray-700 mb-1">
                Angle: {{ gradientAngle }}°
              </label>
              <input
                :value="gradientAngle"
                @input="updateGradientAngle"
                type="range"
                min="0"
                max="360"
                step="1"
                class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer"
              />
            </div>

            <!-- Gradient Center (Radial only) -->
            <div v-if="backgroundType === 'radial'" class="grid grid-cols-2 gap-2">
              <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">Center X</label>
                <input
                  :value="Math.round(gradientCenterX * 100)"
                  @input="updateGradientCenterX"
                  type="range"
                  min="0"
                  max="100"
                  step="1"
                  class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer"
                />
                <div class="text-xs text-gray-500 text-center mt-1">{{ Math.round(gradientCenterX * 100) }}%</div>
              </div>
              <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">Center Y</label>
                <input
                  :value="Math.round(gradientCenterY * 100)"
                  @input="updateGradientCenterY"
                  type="range"
                  min="0"
                  max="100"
                  step="1"
                  class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer"
                />
                <div class="text-xs text-gray-500 text-center mt-1">{{ Math.round(gradientCenterY * 100) }}%</div>
              </div>
            </div>

            <!-- Gradient Radius (Radial only) -->
            <div v-if="backgroundType === 'radial'">
              <label class="block text-xs font-medium text-gray-700 mb-1">
                Radius: {{ Math.round(gradientRadius * 100) }}%
              </label>
              <input
                :value="Math.round(gradientRadius * 100)"
                @input="updateGradientRadius"
                type="range"
                min="10"
                max="150"
                step="1"
                class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer"
              />
            </div>

            <!-- Gradient Colors -->
            <div>
              <label class="block text-xs font-medium text-gray-700 mb-2">Colors</label>
              <div class="space-y-2">
                <div
                  v-for="(colorStop, index) in gradientColors"
                  :key="index"
                  class="flex items-center space-x-2"
                >
                  <input
                    :value="colorStop.color"
                    @input="(e) => updateGradientColor(index, (e.target as HTMLInputElement).value)"
                    type="color"
                    class="w-8 h-8 rounded border border-gray-300 cursor-pointer"
                  />
                  <input
                    :value="Math.round(colorStop.stop * 100)"
                    @input="(e) => updateGradientStop(index, parseInt((e.target as HTMLInputElement).value) / 100)"
                    type="range"
                    min="0"
                    max="100"
                    step="1"
                    class="flex-1 h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer"
                  />
                  <span class="text-xs text-gray-500 w-8">{{ Math.round(colorStop.stop * 100) }}%</span>
                  <button
                    v-if="gradientColors.length > 2"
                    @click="removeGradientColor(index)"
                    class="w-6 h-6 text-red-500 hover:text-red-700 transition-colors"
                  >
                    ×
                  </button>
                </div>
                <button
                  v-if="gradientColors.length < 5"
                  @click="addGradientColor"
                  class="w-full py-1 text-xs text-blue-600 hover:text-blue-800 border border-dashed border-gray-300 rounded transition-colors"
                >
                  + Add Color
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Preset Sizes -->
    <div>
      <h3 class="text-sm font-medium text-gray-900 mb-3">Preset Sizes</h3>
      <div class="grid grid-cols-1 gap-2">
        <button
          v-for="preset in presetSizes"
          :key="preset.name"
          @click="applyPreset(preset)"
          class="text-left p-3 border border-gray-200 rounded-lg hover:border-blue-300 hover:bg-blue-50 transition-colors"
        >
          <div class="font-medium text-sm text-gray-900">{{ preset.name }}</div>
          <div class="text-xs text-gray-600">{{ preset.width }}×{{ preset.height }}px</div>
          <div class="text-xs text-gray-500 mt-1">{{ preset.description }}</div>
        </button>
      </div>
    </div>

    <!-- Background Presets -->
    <div>
      <h3 class="text-sm font-medium text-gray-900 mb-3">Background Presets</h3>
      <div class="grid grid-cols-4 gap-2">
        <button
          v-for="color in backgroundPresets"
          :key="color"
          @click="() => applyBackgroundPreset(color)"
          :style="{ backgroundColor: color }"
          class="w-full h-8 rounded border border-gray-300 hover:border-gray-400 transition-colors"
          :title="color"
        />
      </div>
    </div>

    <!-- Export Settings -->
    <div>
      <h3 class="text-sm font-medium text-gray-900 mb-3">Export Settings</h3>
      <div class="space-y-3">
        <div>
          <label class="block text-xs font-medium text-gray-700 mb-1">Quality</label>
          <select class="input w-full text-sm">
            <option value="high">High (100%)</option>
            <option value="medium">Medium (80%)</option>
            <option value="low">Low (60%)</option>
          </select>
        </div>
        
        <div>
          <label class="block text-xs font-medium text-gray-700 mb-1">DPI</label>
          <select class="input w-full text-sm">
            <option value="72">72 DPI (Web)</option>
            <option value="150">150 DPI (Print)</option>
            <option value="300">300 DPI (High Quality)</option>
          </select>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'
import type { DesignBackground } from '@/types'

interface PresetSize {
  name: string
  width: number
  height: number
  description: string
}

interface Props {
  canvasWidth: number
  canvasHeight: number
  backgroundColor: string | DesignBackground
}

const props = defineProps<Props>()

const emit = defineEmits<{
  'update:canvasWidth': [value: number]
  'update:canvasHeight': [value: number]
  'update:backgroundColor': [value: string | DesignBackground]
}>()

// Background state management
const backgroundConfig = computed(() => {
  if (typeof props.backgroundColor === 'string') {
    return { type: 'solid' as const, color: props.backgroundColor }
  }
  return props.backgroundColor || { type: 'solid' as const, color: '#ffffff' }
})

const backgroundType = computed(() => backgroundConfig.value.type || 'solid')

const solidBackgroundColor = computed(() => {
  if (backgroundConfig.value.type === 'solid') {
    return backgroundConfig.value.color || '#ffffff'
  }
  return '#ffffff'
})

const gradientAngle = computed(() => {
  if (backgroundConfig.value.type === 'linear' && backgroundConfig.value.gradient) {
    return backgroundConfig.value.gradient.angle || 0
  }
  return 0
})

const gradientCenterX = computed(() => {
  if (backgroundConfig.value.type === 'radial' && backgroundConfig.value.gradient) {
    return backgroundConfig.value.gradient.centerX || 0.5
  }
  return 0.5
})

const gradientCenterY = computed(() => {
  if (backgroundConfig.value.type === 'radial' && backgroundConfig.value.gradient) {
    return backgroundConfig.value.gradient.centerY || 0.5
  }
  return 0.5
})

const gradientRadius = computed(() => {
  if (backgroundConfig.value.type === 'radial' && backgroundConfig.value.gradient) {
    return backgroundConfig.value.gradient.radius || 0.7
  }
  return 0.7
})

const gradientColors = computed(() => {
  if (backgroundConfig.value.gradient && backgroundConfig.value.gradient.colors) {
    return backgroundConfig.value.gradient.colors
  }
  return [
    { color: '#ffffff', stop: 0 },
    { color: '#000000', stop: 1 }
  ]
})

// Background update methods
const setBackgroundType = (type: 'solid' | 'linear' | 'radial') => {
  if (type === 'solid') {
    emit('update:backgroundColor', {
      type: 'solid',
      color: solidBackgroundColor.value
    })
  } else {
    emit('update:backgroundColor', {
      type,
      gradient: {
        colors: gradientColors.value,
        ...(type === 'linear' ? { angle: gradientAngle.value } : {}),
        ...(type === 'radial' ? {
          centerX: gradientCenterX.value,
          centerY: gradientCenterY.value,
          radius: gradientRadius.value
        } : {})
      }
    })
  }
}

const updateSolidBackgroundColor = (event: Event) => {
  const target = event.target as HTMLInputElement
  emit('update:backgroundColor', {
    type: 'solid',
    color: target.value
  })
}

const updateGradientAngle = (event: Event) => {
  const target = event.target as HTMLInputElement
  const newAngle = parseInt(target.value)
  
  if (backgroundConfig.value.type === 'linear') {
    emit('update:backgroundColor', {
      type: 'linear',
      gradient: {
        ...backgroundConfig.value.gradient,
        angle: newAngle,
        colors: gradientColors.value
      }
    })
  }
}

const updateGradientCenterX = (event: Event) => {
  const target = event.target as HTMLInputElement
  const newCenterX = parseInt(target.value) / 100
  
  if (backgroundConfig.value.type === 'radial') {
    emit('update:backgroundColor', {
      type: 'radial',
      gradient: {
        ...backgroundConfig.value.gradient,
        centerX: newCenterX,
        colors: gradientColors.value
      }
    })
  }
}

const updateGradientCenterY = (event: Event) => {
  const target = event.target as HTMLInputElement
  const newCenterY = parseInt(target.value) / 100
  
  if (backgroundConfig.value.type === 'radial') {
    emit('update:backgroundColor', {
      type: 'radial',
      gradient: {
        ...backgroundConfig.value.gradient,
        centerY: newCenterY,
        colors: gradientColors.value
      }
    })
  }
}

const updateGradientRadius = (event: Event) => {
  const target = event.target as HTMLInputElement
  const newRadius = parseInt(target.value) / 100
  
  if (backgroundConfig.value.type === 'radial') {
    emit('update:backgroundColor', {
      type: 'radial',
      gradient: {
        ...backgroundConfig.value.gradient,  
        radius: newRadius,
        colors: gradientColors.value
      }
    })
  }
}

const updateGradientColor = (index: number, color: string) => {
  const newColors = [...gradientColors.value]
  newColors[index] = { ...newColors[index], color }
  
  if (backgroundConfig.value.type !== 'solid') {
    emit('update:backgroundColor', {
      type: backgroundConfig.value.type,
      gradient: {
        ...backgroundConfig.value.gradient,
        colors: newColors
      }
    })
  }
}

const updateGradientStop = (index: number, stop: number) => {
  const newColors = [...gradientColors.value]
  newColors[index] = { ...newColors[index], stop }
  // Sort colors by stop position
  newColors.sort((a, b) => a.stop - b.stop)
  
  if (backgroundConfig.value.type !== 'solid') {
    emit('update:backgroundColor', {
      type: backgroundConfig.value.type,
      gradient: {
        ...backgroundConfig.value.gradient,
        colors: newColors
      }
    })
  }
}

const addGradientColor = () => {
  const newColors = [...gradientColors.value]
  // Add new color at the midpoint between last two colors
  const lastStop = newColors[newColors.length - 1]?.stop || 1
  const secondLastStop = newColors[newColors.length - 2]?.stop || 0
  const newStop = (lastStop + secondLastStop) / 2
  
  newColors.push({
    color: '#808080',
    stop: newStop
  })
  
  // Sort colors by stop position
  newColors.sort((a, b) => a.stop - b.stop)
  
  if (backgroundConfig.value.type !== 'solid') {
    emit('update:backgroundColor', {
      type: backgroundConfig.value.type,
      gradient: {
        ...backgroundConfig.value.gradient,
        colors: newColors
      }
    })
  }
}

const removeGradientColor = (index: number) => {
  const newColors = [...gradientColors.value]
  newColors.splice(index, 1)
  
  if (backgroundConfig.value.type !== 'solid') {
    emit('update:backgroundColor', {
      type: backgroundConfig.value.type,
      gradient: {
        ...backgroundConfig.value.gradient,
        colors: newColors
      }
    })
  }
}

// Preset sizes
const presetSizes: PresetSize[] = [
  { name: 'Instagram Post', width: 1080, height: 1080, description: 'Square format' },
  { name: 'Instagram Story', width: 1080, height: 1920, description: '9:16 ratio' },
  { name: 'Facebook Post', width: 1200, height: 630, description: 'Landscape format' },
  { name: 'Twitter Header', width: 1500, height: 500, description: 'Header banner' },
  { name: 'YouTube Thumbnail', width: 1280, height: 720, description: '16:9 ratio' },
  { name: 'Business Card', width: 1050, height: 600, description: 'Standard size' },
  { name: 'A4 Document', width: 2480, height: 3508, description: 'Print ready' },
  { name: 'Custom', width: 800, height: 600, description: 'Custom dimensions' }
]

// Background color presets
const backgroundPresets = [
  '#ffffff', '#f8f9fa', '#e9ecef', '#dee2e6',
  '#000000', '#212529', '#495057', '#6c757d',
  '#007bff', '#0056b3', '#004085', '#002752',
  '#28a745', '#1e7e34', '#155724', '#0a3622',
  '#dc3545', '#c82333', '#a71e2a', '#721c24',
  '#ffc107', '#e0a800', '#b69500', '#7d6600'
]

const updateCanvasWidth = (event: Event) => {
  const target = event.target as HTMLInputElement
  const value = parseInt(target.value)
  if (!isNaN(value) && value > 0) {
    emit('update:canvasWidth', value)
  }
}

const updateCanvasHeight = (event: Event) => {
  const target = event.target as HTMLInputElement
  const value = parseInt(target.value)
  if (!isNaN(value) && value > 0) {
    emit('update:canvasHeight', value)
  }
}

const updateBackgroundColor = (event: Event) => {
  const target = event.target as HTMLInputElement
  emit('update:backgroundColor', {
    type: 'solid',
    color: target.value
  })
}

const applyBackgroundPreset = (color: string) => {
  emit('update:backgroundColor', {
    type: 'solid',
    color: color
  })
}

const applyPreset = (preset: PresetSize) => {
  emit('update:canvasWidth', preset.width)
  emit('update:canvasHeight', preset.height)
}
</script>

<style scoped>
.input {
  padding: 0.75rem;
  border: 1px solid #d1d5db;
  border-radius: 0.375rem;
  outline: none;
}

.input:focus {
  outline: 2px solid #3b82f6;
  outline-offset: 2px;
  border-color: transparent;
}
</style>

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
          <label class="block text-xs font-medium text-gray-700 mb-1">Background Color</label>
          <div class="flex items-center space-x-2">
            <input
              :value="backgroundColor"
              @input="updateBackgroundColor"
              type="color"
              class="w-12 h-8 rounded border border-gray-300 cursor-pointer"
            />
            <input
              :value="backgroundColor"
              @input="updateBackgroundColor"
              type="text"
              class="input flex-1 text-sm"
              placeholder="#ffffff"
            />
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
          @click="() => emit('update:backgroundColor', color)"
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
interface PresetSize {
  name: string
  width: number
  height: number
  description: string
}

interface Props {
  canvasWidth: number
  canvasHeight: number
  backgroundColor: string
}

const props = defineProps<Props>()

const emit = defineEmits<{
  'update:canvasWidth': [value: number]
  'update:canvasHeight': [value: number]
  'update:backgroundColor': [value: string]
}>()

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
  emit('update:backgroundColor', target.value)
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

<template>
  <div class="p-4 space-y-6">
    <!-- Color Picker Section -->
    <div class="space-y-4">
      <div class="flex items-center justify-between">
        <h3 class="text-sm font-medium text-gray-900">Color Picker</h3>
        <button
          @click="toggleEyedropper"
          :class="[
            'px-3 py-1.5 text-xs font-medium rounded-md transition-colors',
            isEyedropperActive
              ? 'bg-blue-100 text-blue-800 hover:bg-blue-200'
              : 'bg-gray-100 text-gray-600 hover:bg-gray-200'
          ]"
          :title="isEyedropperActive ? 'Disable Eyedropper' : 'Enable Eyedropper'"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
          </svg>
        </button>
      </div>

      <!-- Main Color Input -->
      <div class="space-y-3">
        <div class="flex items-center space-x-3">
          <div class="relative">
            <input
              v-model="currentColor"
              type="color"
              class="w-16 h-16 border-2 border-gray-200 rounded-lg cursor-pointer shadow-sm hover:shadow-md transition-shadow"
              @input="handleColorChange"
            />
            <div class="absolute -bottom-1 -right-1 w-4 h-4 bg-white rounded-full border border-gray-300 flex items-center justify-center">
              <div class="w-2 h-2 rounded-full bg-gradient-to-br from-red-400 to-blue-600"></div>
            </div>
          </div>
          
          <div class="flex-1 space-y-2">
            <input
              v-model="currentColor"
              type="text"
              class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 font-mono"
              placeholder="#000000"
              @input="handleColorChange"
            />
            
            <!-- RGB Input -->
            <div class="grid grid-cols-3 gap-2">
              <input
                v-model.number="rgbColor.r"
                type="number"
                min="0"
                max="255"
                class="w-full px-2 py-1 text-xs border border-gray-300 rounded focus:ring-1 focus:ring-blue-500"
                placeholder="R"
                @input="handleRgbChange"
              />
              <input
                v-model.number="rgbColor.g"
                type="number"
                min="0"
                max="255"
                class="w-full px-2 py-1 text-xs border border-gray-300 rounded focus:ring-1 focus:ring-blue-500"
                placeholder="G"
                @input="handleRgbChange"
              />
              <input
                v-model.number="rgbColor.b"
                type="number"
                min="0"
                max="255"
                class="w-full px-2 py-1 text-xs border border-gray-300 rounded focus:ring-1 focus:ring-blue-500"
                placeholder="B"
                @input="handleRgbChange"
              />
            </div>
          </div>
        </div>

        <!-- Opacity Slider -->
        <div class="space-y-2">
          <label class="block text-xs font-medium text-gray-700">
            Opacity: {{ Math.round(opacity * 100) }}%
          </label>
          <input
            v-model.number="opacity"
            type="range"
            min="0"
            max="1"
            step="0.01"
            class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer slider"
            @input="handleOpacityChange"
          />
        </div>
      </div>
    </div>

    <!-- Color Palette -->
    <div class="space-y-4">
      <div class="flex items-center justify-between">
        <h3 class="text-sm font-medium text-gray-900">Color Palette</h3>
        <button
          @click="showPaletteOptions = !showPaletteOptions"
          class="px-3 py-1.5 text-xs font-medium text-gray-600 border border-gray-300 rounded-md hover:bg-gray-50 transition-colors"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z" />
          </svg>
        </button>
      </div>

      <!-- Palette Options -->
      <div v-if="showPaletteOptions" class="space-y-2">
        <select
          v-model="selectedPalette"
          class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
          @change="loadPalette"
        >
          <option value="material">Material Design</option>
          <option value="flat">Flat UI</option>
          <option value="pastel">Pastel</option>
          <option value="brand">Brand Colors</option>
          <option value="grayscale">Grayscale</option>
          <option value="custom">Custom</option>
        </select>
      </div>

      <!-- Color Swatches -->
      <div class="grid grid-cols-8 gap-2">
        <button
          v-for="(color, index) in currentPalette"
          :key="index"
          @click="selectPaletteColor(color)"
          :style="{ backgroundColor: color }"
          class="w-8 h-8 rounded-lg border border-gray-200 hover:border-gray-400 transition-colors shadow-sm hover:shadow-md cursor-pointer"
          :title="color"
        ></button>
      </div>

      <!-- Add to Palette Button -->
      <button
        @click="addToPalette(currentColor)"
        class="w-full px-3 py-2 text-sm font-medium text-gray-600 border border-dashed border-gray-300 rounded-md hover:border-gray-400 hover:bg-gray-50 transition-colors"
      >
        <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
        </svg>
        Add Current Color
      </button>
    </div>

    <!-- Recent Colors -->
    <div v-if="recentColors.length > 0" class="space-y-4">
      <h3 class="text-sm font-medium text-gray-900">Recent Colors</h3>
      <div class="grid grid-cols-8 gap-2">
        <button
          v-for="(color, index) in recentColors.slice(0, 16)"
          :key="index"
          @click="selectRecentColor(color)"
          :style="{ backgroundColor: color }"
          class="w-8 h-8 rounded-lg border border-gray-200 hover:border-gray-400 transition-colors shadow-sm hover:shadow-md cursor-pointer"
          :title="color"
        ></button>
      </div>
    </div>

    <!-- Gradient Builder -->
    <div class="space-y-4">
      <div class="flex items-center justify-between">
        <h3 class="text-sm font-medium text-gray-900">Gradients</h3>
        <button
          @click="showGradientBuilder = !showGradientBuilder"
          :class="[
            'px-3 py-1.5 text-xs font-medium rounded-md transition-colors',
            showGradientBuilder
              ? 'bg-purple-100 text-purple-800 hover:bg-purple-200'
              : 'bg-gray-100 text-gray-600 hover:bg-gray-200'
          ]"
        >
          {{ showGradientBuilder ? 'Hide' : 'Show' }} Builder
        </button>
      </div>

      <!-- Gradient Builder -->
      <div v-if="showGradientBuilder" class="space-y-4">
        <!-- Gradient Type Selector -->
        <div>
          <label class="block text-xs font-medium text-gray-700 mb-2">Gradient Type</label>
          <div class="flex space-x-1 p-1 bg-gray-100 rounded-lg">
            <button
              @click="gradientType = 'linear'"
              :class="[
                'flex-1 py-1 px-2 text-xs font-medium rounded-md transition-colors',
                gradientType === 'linear'
                  ? 'bg-white text-gray-900 shadow-sm'
                  : 'text-gray-600 hover:text-gray-900'
              ]"
            >
              Linear
            </button>
            <button
              @click="gradientType = 'radial'"
              :class="[
                'flex-1 py-1 px-2 text-xs font-medium rounded-md transition-colors',
                gradientType === 'radial'
                  ? 'bg-white text-gray-900 shadow-sm'
                  : 'text-gray-600 hover:text-gray-900'
              ]"
            >
              Radial
            </button>
          </div>
        </div>

        <!-- Gradient Colors -->
        <div>
          <label class="block text-xs font-medium text-gray-700 mb-2">Colors</label>
          <div class="space-y-2">
            <div
              v-for="(colorStop, index) in gradientStops"
              :key="index"
              class="flex items-center space-x-2"
            >
              <input
                :value="colorStop.color"
                @input="updateGradientStop(index, 'color', ($event.target as HTMLInputElement).value)"
                type="color"
                class="w-8 h-8 rounded border border-gray-300 cursor-pointer"
              />
              <input
                :value="Math.round(colorStop.stop * 100)"
                @input="updateGradientStop(index, 'stop', parseInt(($event.target as HTMLInputElement).value) / 100)"
                type="range"
                min="0"
                max="100"
                step="1"
                class="flex-1 h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer"
              />
              <span class="text-xs text-gray-500 w-8">{{ Math.round(colorStop.stop * 100) }}%</span>
              <button
                v-if="gradientStops.length > 2"
                @click="removeGradientStop(index)"
                class="w-6 h-6 text-red-500 hover:text-red-700 transition-colors"
              >
                ×
              </button>
            </div>
            <button
              v-if="gradientStops.length < 5"
              @click="addGradientStop"
              class="w-full py-1 text-xs text-blue-600 hover:text-blue-800 border border-dashed border-gray-300 rounded transition-colors"
            >
              + Add Color
            </button>
          </div>
        </div>

        <!-- Linear Gradient Controls -->
        <div v-if="gradientType === 'linear'">
          <label class="block text-xs font-medium text-gray-700 mb-1">
            Angle: {{ gradientAngle }}°
          </label>
          <input
            :value="gradientAngle"
            @input="gradientAngle = parseInt(($event.target as HTMLInputElement).value)"
            type="range"
            min="0"
            max="360"
            step="1"
            class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer"
          />
        </div>

        <!-- Radial Gradient Controls -->
        <div v-if="gradientType === 'radial'" class="space-y-3">
          <div class="grid grid-cols-2 gap-2">
            <div>
              <label class="block text-xs font-medium text-gray-700 mb-1">Center X</label>
              <input
                :value="Math.round(radialCenterX * 100)"
                @input="radialCenterX = parseInt(($event.target as HTMLInputElement).value) / 100"
                type="range"
                min="0"
                max="100"
                step="1"
                class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer"
              />
              <div class="text-xs text-gray-500 text-center mt-1">{{ Math.round(radialCenterX * 100) }}%</div>
            </div>
            <div>
              <label class="block text-xs font-medium text-gray-700 mb-1">Center Y</label>
              <input
                :value="Math.round(radialCenterY * 100)"
                @input="radialCenterY = parseInt(($event.target as HTMLInputElement).value) / 100"
                type="range"
                min="0"
                max="100"
                step="1"
                class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer"
              />
              <div class="text-xs text-gray-500 text-center mt-1">{{ Math.round(radialCenterY * 100) }}%</div>
            </div>
          </div>
          <div>
            <label class="block text-xs font-medium text-gray-700 mb-1">
              Radius: {{ Math.round(radialRadius * 100) }}%
            </label>
            <input
              :value="Math.round(radialRadius * 100)"
              @input="radialRadius = parseInt(($event.target as HTMLInputElement).value) / 100"
              type="range"
              min="10"
              max="150"
              step="1"
              class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer"
            />
          </div>
        </div>

        <!-- Gradient Preview -->
        <div
          class="w-full h-12 border border-gray-200 rounded-lg"
          :style="{ background: currentGradient }"
        ></div>

        <!-- Apply Gradient Button -->
        <button
          @click="applyCustomGradient"
          class="w-full px-3 py-2 text-sm font-medium text-white bg-purple-600 rounded-md hover:bg-purple-700 transition-colors"
        >
          Apply Gradient
        </button>
      </div>

      <!-- Preset Gradients -->
      <div class="grid grid-cols-3 gap-2">
        <button
          v-for="(gradient, index) in presetGradients"
          :key="index"
          @click="applyPresetGradient(gradient)"
          class="w-full h-10 rounded-lg border border-gray-200 hover:border-gray-400 transition-colors shadow-sm hover:shadow-md cursor-pointer"
          :style="{ background: gradient.css }"
          :title="gradient.name"
        ></button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue'

interface RGB {
  r: number
  g: number
  b: number
}

interface GradientColors {
  from: string
  to: string
}

interface PresetGradient {
  name: string
  css: string
}

const emit = defineEmits<{
  'apply-color': [color: string]
  'apply-gradient': [gradient: string]
}>()

// Color state
const currentColor = ref('#3B82F6')
const opacity = ref(1)
const isEyedropperActive = ref(false)
const rgbColor = ref<RGB>({ r: 59, g: 130, b: 246 })

// Palette state
const showPaletteOptions = ref(false)
const selectedPalette = ref('material')
const recentColors = ref<string[]>([])

// Gradient state
const showGradientBuilder = ref(false)
const gradientType = ref<'linear' | 'radial'>('linear')
const gradientStops = ref<Array<{ color: string; stop: number }>>([
  { color: '#3B82F6', stop: 0 },
  { color: '#8B5CF6', stop: 1 }
])
const gradientAngle = ref(90)
const radialCenterX = ref(0.5)
const radialCenterY = ref(0.5)
const radialRadius = ref(0.7)

// Legacy gradient state for backward compatibility
const gradientColors = ref<GradientColors>({
  from: '#3B82F6',
  to: '#8B5CF6'
})
const gradientDirection = ref('to right')

// Color palettes
const colorPalettes: Record<string, string[]> = {
  material: [
    '#F44336', '#E91E63', '#9C27B0', '#673AB7',
    '#3F51B5', '#2196F3', '#03A9F4', '#00BCD4',
    '#009688', '#4CAF50', '#8BC34A', '#CDDC39',
    '#FFEB3B', '#FFC107', '#FF9800', '#FF5722'
  ],
  flat: [
    '#E74C3C', '#C0392B', '#E67E22', '#D35400',
    '#F39C12', '#F1C40F', '#2ECC71', '#27AE60',
    '#1ABC9C', '#16A085', '#3498DB', '#2980B9',
    '#9B59B6', '#8E44AD', '#34495E', '#2C3E50'
  ],
  pastel: [
    '#FFB3BA', '#FFDFBA', '#FFFFBA', '#BAFFC9',
    '#BAE1FF', '#D4BAFF', '#FFB3E6', '#FFB3B3',
    '#B3FFB3', '#B3E5FC', '#E1BEE7', '#F8BBD9',
    '#FFE0B2', '#DCEDC8', '#C8E6C9', '#B39DDB'
  ],
  brand: [
    '#1DA1F2', '#4267B2', '#E4405F', '#FF0000',
    '#00D924', '#FFFC00', '#FF6900', '#6441A4',
    '#1BD741', '#FF1744', '#00C851', '#FF8A80',
    '#8BC34A', '#FFD600', '#FF5722', '#795548'
  ],
  grayscale: [
    '#000000', '#212121', '#424242', '#616161',
    '#757575', '#9E9E9E', '#BDBDBD', '#E0E0E0',
    '#EEEEEE', '#F5F5F5', '#FAFAFA', '#FFFFFF',
    '#263238', '#37474F', '#455A64', '#546E7A'
  ],
  custom: [] as string[]
}

// Preset gradients
const presetGradients: PresetGradient[] = [
  // Linear gradients
  { name: 'Ocean Breeze', css: 'linear-gradient(90deg, #667eea 0%, #764ba2 100%)' },
  { name: 'Sunset Glow', css: 'linear-gradient(90deg, #ff7e5f 0%, #feb47b 100%)' },
  { name: 'Purple Dreams', css: 'linear-gradient(90deg, #8B5CF6 0%, #EC4899 100%)' },
  { name: 'Forest Fresh', css: 'linear-gradient(90deg, #11998e 0%, #38ef7d 100%)' },
  { name: 'Arctic Blue', css: 'linear-gradient(90deg, #2196F3 0%, #21CBF3 100%)' },
  { name: 'Rose Gold', css: 'linear-gradient(90deg, #ff9a9e 0%, #fecfef 100%)' },
  { name: 'Citrus Burst', css: 'linear-gradient(90deg, #fa709a 0%, #fee140 100%)' },
  { name: 'Mint Fresh', css: 'linear-gradient(90deg, #a8edea 0%, #fed6e3 100%)' },
  
  // Diagonal gradients
  { name: 'Electric Violet', css: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)' },
  { name: 'Fire Sunset', css: 'linear-gradient(135deg, #f093fb 0%, #f5576c 100%)' },
  { name: 'Cool Sky', css: 'linear-gradient(135deg, #4facfe 0%, #00f2fe 100%)' },
  { name: 'Green Paradise', css: 'linear-gradient(135deg, #43e97b 0%, #38f9d7 100%)' },
  
  // Radial gradients
  { name: 'Radial Sunset', css: 'radial-gradient(circle at 50% 50%, #ff7e5f 0%, #feb47b 70%)' },
  { name: 'Radial Ocean', css: 'radial-gradient(circle at 50% 50%, #667eea 0%, #764ba2 70%)' },
  { name: 'Radial Purple', css: 'radial-gradient(circle at 50% 50%, #8B5CF6 0%, #EC4899 70%)' },
  { name: 'Radial Mint', css: 'radial-gradient(circle at 50% 50%, #a8edea 0%, #fed6e3 70%)' }
]

// Computed properties
const currentPalette = computed(() => {
  return colorPalettes[selectedPalette.value as keyof typeof colorPalettes] || []
})

const currentGradient = computed(() => {
  if (gradientType.value === 'linear') {
    const colorStops = gradientStops.value
      .map(stop => `${stop.color} ${Math.round(stop.stop * 100)}%`)
      .join(', ')
    return `linear-gradient(${gradientAngle.value}deg, ${colorStops})`
  } else {
    const colorStops = gradientStops.value
      .map(stop => `${stop.color} ${Math.round(stop.stop * 100)}%`)
      .join(', ')
    return `radial-gradient(circle at ${Math.round(radialCenterX.value * 100)}% ${Math.round(radialCenterY.value * 100)}%, ${colorStops})`
  }
})

// Legacy gradient for backward compatibility
const legacyGradient = computed(() => {
  return `linear-gradient(${gradientDirection.value}, ${gradientColors.value.from}, ${gradientColors.value.to})`
})

// Methods
const hexToRgb = (hex: string): RGB => {
  const result = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex)
  return result ? {
    r: parseInt(result[1], 16),
    g: parseInt(result[2], 16),
    b: parseInt(result[3], 16)
  } : { r: 0, g: 0, b: 0 }
}

const rgbToHex = (r: number, g: number, b: number): string => {
  return "#" + ((1 << 24) + (r << 16) + (g << 8) + b).toString(16).slice(1)
}

const handleColorChange = () => {
  rgbColor.value = hexToRgb(currentColor.value)
  addToRecentColors(currentColor.value)
  emit('apply-color', currentColor.value)
}

const handleRgbChange = () => {
  currentColor.value = rgbToHex(rgbColor.value.r, rgbColor.value.g, rgbColor.value.b)
  addToRecentColors(currentColor.value)
  emit('apply-color', currentColor.value)
}

const handleOpacityChange = () => {
  // Convert color to rgba and emit
  const rgb = hexToRgb(currentColor.value)
  const rgba = `rgba(${rgb.r}, ${rgb.g}, ${rgb.b}, ${opacity.value})`
  emit('apply-color', rgba)
}

const selectPaletteColor = (color: string) => {
  currentColor.value = color
  rgbColor.value = hexToRgb(color)
  addToRecentColors(color)
  emit('apply-color', color)
}

const selectRecentColor = (color: string) => {
  currentColor.value = color
  rgbColor.value = hexToRgb(color)
  emit('apply-color', color)
}

const addToRecentColors = (color: string) => {
  const filtered = recentColors.value.filter(c => c !== color)
  recentColors.value = [color, ...filtered].slice(0, 16)
}

const addToPalette = (color: string) => {
  if (selectedPalette.value === 'custom') {
    const customPalette = colorPalettes.custom
    if (!customPalette.includes(color)) {
      customPalette.push(color)
    }
  }
}

const loadPalette = () => {
  // Load selected palette
  showPaletteOptions.value = false
}

const toggleEyedropper = async () => {
  // Check if EyeDropper API is supported
  if (!('EyeDropper' in window)) {
    alert('Eyedropper is not supported in this browser. Please use Chrome, Edge, or other Chromium-based browsers.')
    return
  }

  try {
    isEyedropperActive.value = true
    
    // Create a new EyeDropper instance
    const eyeDropper = new (window as any).EyeDropper()
    
    // Start the eyedropper
    const result = await eyeDropper.open()
    
    if (result && result.sRGBHex) {
      // Set the picked color (just update the color picker, don't apply)
      currentColor.value = result.sRGBHex
      rgbColor.value = hexToRgb(result.sRGBHex)
      addToRecentColors(result.sRGBHex)
      // Don't emit apply-color automatically - user should click to apply
    }
  } catch (error) {
    // User cancelled or other error
    console.log('Eyedropper cancelled or failed:', error)
  } finally {
    isEyedropperActive.value = false
  }
}

const updateGradientPreview = () => {
  // Gradient preview is automatically updated via computed property
}

const applyGradient = () => {
  emit('apply-gradient', currentGradient.value)
}

const applyPresetGradient = (gradient: PresetGradient) => {
  emit('apply-gradient', gradient.css)
}

// Enhanced gradient builder methods
const updateGradientStop = (index: number, property: 'color' | 'stop', value: string | number) => {
  if (property === 'color') {
    gradientStops.value[index].color = value as string
  } else {
    gradientStops.value[index].stop = value as number
  }
  // Sort gradient stops by position
  gradientStops.value.sort((a, b) => a.stop - b.stop)
}

const addGradientStop = () => {
  const stops = gradientStops.value
  // Add new stop at the midpoint between last two stops
  const lastStop = stops[stops.length - 1]?.stop || 1
  const secondLastStop = stops[stops.length - 2]?.stop || 0
  const newStop = (lastStop + secondLastStop) / 2
  
  gradientStops.value.push({
    color: '#808080',
    stop: newStop
  })
  
  // Sort by stop position
  gradientStops.value.sort((a, b) => a.stop - b.stop)
}

const removeGradientStop = (index: number) => {
  if (gradientStops.value.length > 2) {
    gradientStops.value.splice(index, 1)
  }
}

const applyCustomGradient = () => {
  // Create a gradient string that can be parsed by the enhanced handler
  if (gradientType.value === 'linear') {
    const colorStops = gradientStops.value
      .map(stop => `${stop.color} ${Math.round(stop.stop * 100)}%`)
      .join(', ')
    const gradientString = `linear-gradient(${gradientAngle.value}deg, ${colorStops})`
    emit('apply-gradient', gradientString)
  } else {
    const colorStops = gradientStops.value
      .map(stop => `${stop.color} ${Math.round(stop.stop * 100)}%`)
      .join(', ')
    const gradientString = `radial-gradient(circle at ${Math.round(radialCenterX.value * 100)}% ${Math.round(radialCenterY.value * 100)}%, ${colorStops})`
    emit('apply-gradient', gradientString)
  }
}

// Watch for color changes and update RGB
watch(currentColor, (newColor) => {
  rgbColor.value = hexToRgb(newColor)
})

// Initialize with default color
rgbColor.value = hexToRgb(currentColor.value)
</script>

<style scoped>
/* Custom slider styling */
.slider {
  -webkit-appearance: none;
  appearance: none;
  height: 8px;
  border-radius: 4px;
  background: #e5e7eb;
  outline: none;
  transition: background 0.3s;
}

.slider::-webkit-slider-thumb {
  -webkit-appearance: none;
  appearance: none;
  width: 20px;
  height: 20px;
  border-radius: 50%;
  background: linear-gradient(135deg, #3b82f6, #1d4ed8);
  cursor: pointer;
  border: 2px solid white;
  box-shadow: 0 2px 8px rgba(59, 130, 246, 0.3);
}

.slider::-moz-range-thumb {
  width: 20px;
  height: 20px;
  border-radius: 50%;
  background: linear-gradient(135deg, #3b82f6, #1d4ed8);
  cursor: pointer;
  border: 2px solid white;
  box-shadow: 0 2px 8px rgba(59, 130, 246, 0.3);
}

.slider:hover {
  background: #d1d5db;
}

.slider::-webkit-slider-thumb:hover {
  box-shadow: 0 4px 12px rgba(59, 130, 246, 0.5);
  transform: scale(1.1);
}
</style>

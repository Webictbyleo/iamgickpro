<template>
  <Popover v-slot="{ open }" class="relative">
    <PopoverButton
      :class="[
        'w-8 h-8 rounded-lg border-2 border-gray-300 focus:outline-none focus:ring-2 focus:ring-purple-500 transition-all duration-200',
        open ? 'ring-2 ring-purple-500' : ''
      ]"
      :style="{ backgroundColor: displayColor }"
    >
      <span class="sr-only">Select color</span>
    </PopoverButton>

    <Transition
      enter-active-class="transition duration-200 ease-out"
      enter-from-class="transform scale-95 opacity-0"
      enter-to-class="transform scale-100 opacity-100"
      leave-active-class="transition duration-75 ease-in"
      leave-from-class="transform scale-100 opacity-100"
      leave-to-class="transform scale-95 opacity-0"
    >
      <PopoverPanel class="absolute z-[9999] mt-2 w-80 bg-white rounded-xl shadow-xl border border-gray-200 p-4 popover-panel">
        <!-- Color Picker Tabs -->
        <div class="flex space-x-1 mb-4">
          <button
            v-for="tab in tabs"
            :key="tab.id"
            @click="activeTab = tab.id"
            :class="[
              'flex-1 px-3 py-2 text-sm font-medium rounded-lg transition-colors',
              activeTab === tab.id
                ? 'bg-purple-100 text-purple-700'
                : 'text-gray-600 hover:text-gray-900 hover:bg-gray-100'
            ]"
          >
            {{ tab.label }}
          </button>
        </div>

        <!-- Solid Color Tab -->
        <div v-if="activeTab === 'solid'" class="space-y-4">
          <!-- Color Preview and Controls -->
          <div class="flex items-center space-x-3">
            <div 
              class="w-16 h-16 rounded-lg border-2 border-gray-300 flex-shrink-0"
              :style="{ backgroundColor: currentColor }"
            />
            <div class="flex-1 space-y-2">
              <!-- Hex Input with Color Picker -->
              <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">Hex Color</label>
                <div class="flex">
                  <span class="inline-flex items-center px-3 py-2 border border-r-0 border-gray-300 bg-gray-50 text-gray-500 text-sm rounded-l-lg">#</span>
                  <input
                    ref="hexInput"
                    :value="hexValue"
                    @input="updateHex"
                    @click="triggerColorPicker"
                    class="flex-1 px-3 py-2 border border-gray-300 rounded-r-lg focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent text-sm cursor-pointer"
                    placeholder="000000"
                    maxlength="6"
                    title="Click to open system color picker"
                  />
                  <!-- Hidden native color input -->
                  <input
                    ref="colorInput"
                    type="color"
                    :value="currentColor"
                    @input="updateFromColorPicker"
                    class="sr-only"
                  />
                </div>
              </div>
              
              <!-- Opacity Slider -->
              <div v-if="allowAlpha">
                <label class="block text-xs font-medium text-gray-700 mb-1">Opacity: {{ Math.round(opacity * 100) }}%</label>
                <input
                  type="range"
                  min="0"
                  max="1"
                  step="0.01"
                  :value="opacity"
                  @input="updateOpacity"
                  class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer slider"
                />
              </div>
            </div>
          </div>

          <!-- Color Harmony Generator -->
          <div v-if="colorHarmony.length > 0">
            <label class="block text-sm font-medium text-gray-700 mb-2">Color Harmony</label>
            <div class="flex space-x-2">
              <button
                v-for="harmonyColor in colorHarmony"
                :key="harmonyColor"
                @click="updateColor(harmonyColor)"
                :class="[
                  'w-6 h-6 rounded-lg border-2 transition-transform hover:scale-110',
                  value === harmonyColor ? 'border-purple-500 ring-2 ring-purple-200' : 'border-gray-300'
                ]"
                :style="{ backgroundColor: harmonyColor }"
                :title="getColorName(harmonyColor)"
              />
            </div>
          </div>

          <!-- Preset Colors -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Preset Colors</label>
            <div class="grid grid-cols-10 gap-2">
              <button
                v-for="color in presetColors"
                :key="color"
                @click="updateColor(color)"
                :class="[
                  'w-6 h-6 rounded-lg border-2 transition-transform hover:scale-110',
                  isCurrentColor(color) ? 'border-purple-500 ring-2 ring-purple-200' : 'border-gray-300'
                ]"
                :style="{ backgroundColor: color }"
                :title="color"
              />
            </div>
          </div>

          <!-- Recently Used -->
          <div v-if="recentColors.length > 0">
            <label class="block text-sm font-medium text-gray-700 mb-2">Recently Used</label>
            <div class="flex space-x-2 flex-wrap gap-2">
              <button
                v-for="color in recentColors"
                :key="color"
                @click="updateColor(color)"
                :class="[
                  'w-6 h-6 rounded-lg border-2 transition-transform hover:scale-110',
                  isCurrentColor(color) ? 'border-purple-500 ring-2 ring-purple-200' : 'border-gray-300'
                ]"
                :style="{ backgroundColor: color }"
                :title="color"
              />
            </div>
          </div>
        </div>

        <!-- Gradient Tab -->
        <div v-if="activeTab === 'gradient' && allowGradient" class="space-y-4">
          <!-- Gradient Preview -->
          <div class="h-8 rounded-lg border-2 border-gray-300" :style="{ background: gradientPreview }" />
          
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Type</label>
              <PropertyDropdown
                :value="gradientType"
                :options="gradientOptions"
                @update="updateGradientType"
              />
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Direction</label>
              <PropertyDropdown
                :value="gradientDirection"
                :options="gradientDirectionOptions"
                @update="updateGradientDirection"
              />
            </div>
          </div>

          <!-- Gradient Colors with improved UX -->
          <div>
            <div class="flex items-center justify-between mb-2">
              <label class="block text-sm font-medium text-gray-700">Color Stops</label>
              <button
                v-if="gradientColors.length < 5"
                @click="addGradientColor"
                class="text-xs px-2 py-1 bg-purple-100 text-purple-700 rounded-lg hover:bg-purple-200 transition-colors"
              >
                + Add Stop
              </button>
            </div>
            
            <div class="space-y-3">
              <div 
                v-for="(colorStop, index) in gradientColors" 
                :key="index" 
                class="flex items-center space-x-3 p-2 bg-gray-50 rounded-lg"
              >
                <button
                  @click="openGradientColorPicker(index)"
                  class="w-8 h-8 rounded-lg border-2 border-gray-300 flex-shrink-0"
                  :style="{ backgroundColor: colorStop.color }"
                />
                
                <div class="flex-1 space-y-1">
                  <input
                    :value="colorStop.color"
                    @input="updateGradientColorValue(index, $event)"
                    class="w-full px-2 py-1 border border-gray-300 rounded text-xs"
                    placeholder="#000000"
                  />
                  <div class="flex items-center space-x-2">
                    <span class="text-xs text-gray-500">Position:</span>
                    <input
                      type="range"
                      min="0"
                      max="100"
                      :value="colorStop.position"
                      @input="updateGradientColorPosition(index, $event)"
                      class="flex-1 h-1 bg-gray-200 rounded-lg appearance-none cursor-pointer"
                    />
                    <span class="text-xs text-gray-500 w-8">{{ colorStop.position }}%</span>
                  </div>
                </div>
                
                <button
                  v-if="gradientColors.length > 2"
                  @click="removeGradientColor(index)"
                  class="text-red-500 hover:text-red-700 p-1"
                  title="Remove color stop"
                >
                  <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                  </svg>
                </button>
              </div>
            </div>
          </div>
          
          <!-- Gradient Presets -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Gradient Presets</label>
            <div class="grid grid-cols-4 gap-2">
              <button
                v-for="preset in gradientPresets"
                :key="preset.name"
                @click="applyGradientPreset(preset)"
                class="h-8 rounded-lg border-2 border-gray-300 hover:border-purple-500 transition-colors"
                :style="{ background: preset.value }"
                :title="preset.name"
              />
            </div>
          </div>
        </div>
      </PopoverPanel>
    </Transition>
  </Popover>
</template>

<script setup lang="ts">
import { ref, computed, watch, nextTick } from 'vue'
import { Popover, PopoverButton, PopoverPanel } from '@headlessui/vue'
import PropertyDropdown from './PropertyDropdown.vue'
import chroma from 'chroma-js'

interface Props {
  value: string
  allowGradient?: boolean
  allowAlpha?: boolean
}

interface GradientColorStop {
  color: string
  position: number
}

interface GradientPreset {
  name: string
  value: string
}

const props = withDefaults(defineProps<Props>(), {
  allowGradient: false,
  allowAlpha: false
})

const emit = defineEmits<{
  update: [value: string]
}>()

// Template refs
const hexInput = ref<HTMLInputElement>()
const colorInput = ref<HTMLInputElement>()

// State
const activeTab = ref('solid')
const recentColors = ref<string[]>([])
const opacity = ref(1)

const tabs = computed(() => {
  const baseTabs = [{ id: 'solid', label: 'Solid' }]
  if (props.allowGradient) {
    baseTabs.push({ id: 'gradient', label: 'Gradient' })
  }
  return baseTabs
})

const displayColor = computed(() => {
  if (props.value.startsWith('linear-gradient') || props.value.startsWith('radial-gradient')) {
    return props.value
  }
  return props.value
})

// Enhanced color utilities using chroma-js
const currentColor = computed(() => {
  try {
    if (props.value.startsWith('#') || props.value.startsWith('rgb') || chroma.valid(props.value)) {
      return chroma(props.value).hex()
    }
    return '#000000'
  } catch {
    return '#000000'
  }
})

const hexValue = computed(() => {
  try {
    return chroma(currentColor.value).hex().slice(1)
  } catch {
    return '000000'
  }
})

// Color harmony generation using chroma-js
const colorHarmony = computed(() => {
  try {
    const baseColor = chroma(currentColor.value)
    const hsl = baseColor.hsl()
    const hue = hsl[0] || 0
    
    // Generate complementary, triadic, and analogous colors
    const harmony = [
      chroma.hsl((hue + 180) % 360, hsl[1], hsl[2]).hex(), // Complementary
      chroma.hsl((hue + 120) % 360, hsl[1], hsl[2]).hex(), // Triadic 1
      chroma.hsl((hue + 240) % 360, hsl[1], hsl[2]).hex(), // Triadic 2
      chroma.hsl((hue + 30) % 360, hsl[1], hsl[2]).hex(),  // Analogous 1
      chroma.hsl((hue - 30 + 360) % 360, hsl[1], hsl[2]).hex(), // Analogous 2
    ]
    
    return harmony.filter(color => color !== currentColor.value)
  } catch {
    return []
  }
})

// Gradient state with enhanced structure
const gradientType = ref('linear')
const gradientDirection = ref('90deg')
const gradientColors = ref<GradientColorStop[]>([
  { color: '#ff0000', position: 0 },
  { color: '#0000ff', position: 100 }
])

const gradientOptions = [
  { value: 'linear', label: 'Linear' },
  { value: 'radial', label: 'Radial' }
]

const gradientDirectionOptions = [
  { value: '0deg', label: 'To Top' },
  { value: '90deg', label: 'To Right' },
  { value: '180deg', label: 'To Bottom' },
  { value: '270deg', label: 'To Left' },
  { value: '45deg', label: 'To Top Right' },
  { value: '135deg', label: 'To Bottom Right' },
  { value: '225deg', label: 'To Bottom Left' },
  { value: '315deg', label: 'To Top Left' }
]

const gradientPreview = computed(() => {
  const sortedStops = [...gradientColors.value].sort((a, b) => a.position - b.position)
  const colorStops = sortedStops.map(stop => `${stop.color} ${stop.position}%`).join(', ')
  
  if (gradientType.value === 'linear') {
    return `linear-gradient(${gradientDirection.value}, ${colorStops})`
  } else {
    return `radial-gradient(circle, ${colorStops})`
  }
})

const gradientPresets: GradientPreset[] = [
  { name: 'Sunset', value: 'linear-gradient(90deg, #ff7e5f, #feb47b)' },
  { name: 'Ocean', value: 'linear-gradient(90deg, #667eea, #764ba2)' },
  { name: 'Forest', value: 'linear-gradient(90deg, #56ab2f, #a8e6cf)' },
  { name: 'Fire', value: 'linear-gradient(90deg, #ff4b1f, #ff9068)' },
  { name: 'Purple Rain', value: 'linear-gradient(90deg, #667eea, #764ba2)' },
  { name: 'Cool Blues', value: 'linear-gradient(90deg, #2196f3, #21cbf3)' },
  { name: 'Warm Sunset', value: 'linear-gradient(90deg, #ff9a9e, #fecfef, #fecfef)' },
  { name: 'Night Sky', value: 'linear-gradient(90deg, #0c3483, #a2b6df, #6b8cce, #a2b6df)' }
]

const presetColors = [
  '#000000', '#ffffff', '#ff0000', '#00ff00', '#0000ff', '#ffff00', '#ff00ff', '#00ffff',
  '#800000', '#008000', '#000080', '#808000', '#800080', '#008080', '#c0c0c0', '#808080',
  '#ff6b6b', '#4ecdc4', '#45b7d1', '#96ceb4', '#feca57', '#ff9ff3', '#54a0ff', '#5f27cd',
  '#ff6348', '#ff4757', '#2ed573', '#1e90ff', '#3742fa', '#f1c40f', '#e67e22', '#9b59b6'
]

// Color utility functions
const getColorName = (color: string): string => {
  try {
    const c = chroma(color)
    const hsl = c.hsl()
    const hue = hsl[0] || 0
    
    if (hue < 30) return 'Red'
    if (hue < 60) return 'Orange'
    if (hue < 90) return 'Yellow'
    if (hue < 150) return 'Green'
    if (hue < 210) return 'Cyan'
    if (hue < 270) return 'Blue'
    if (hue < 330) return 'Purple'
    return 'Red'
  } catch {
    return color
  }
}

const isCurrentColor = (color: string): boolean => {
  try {
    return chroma(color).hex() === chroma(currentColor.value).hex()
  } catch {
    return color === props.value
  }
}

// Color picker methods
const triggerColorPicker = async () => {
  await nextTick()
  colorInput.value?.click()
}

const updateFromColorPicker = (event: Event) => {
  const target = event.target as HTMLInputElement
  updateColor(target.value)
}

const updateColor = (color: string) => {
  try {
    // Validate color using chroma-js
    const validColor = chroma(color).hex()
    
    // Add to recent colors
    if (!recentColors.value.includes(validColor)) {
      recentColors.value.unshift(validColor)
      if (recentColors.value.length > 12) {
        recentColors.value.pop()
      }
    }
    
    // Apply opacity if enabled
    if (props.allowAlpha && opacity.value < 1) {
      const colorWithAlpha = chroma(validColor).alpha(opacity.value).css()
      emit('update', colorWithAlpha)
    } else {
      emit('update', validColor)
    }
  } catch (error) {
    console.warn('Invalid color:', color, error)
  }
}

const updateHex = (event: Event) => {
  const target = event.target as HTMLInputElement
  let hex = target.value.replace(/[^0-9a-fA-F]/g, '')
  if (hex.length <= 6) {
    try {
      const color = chroma(`#${hex.padEnd(6, '0')}`).hex()
      updateColor(color)
    } catch {
      // Invalid hex, ignore
    }
  }
}

const updateOpacity = (event: Event) => {
  const target = event.target as HTMLInputElement
  opacity.value = parseFloat(target.value)
  updateColor(currentColor.value)
}

// Gradient methods
const updateGradientType = (value: string | number) => {
  gradientType.value = String(value)
  updateGradientValue()
}

const updateGradientDirection = (value: string | number) => {
  gradientDirection.value = String(value)
  updateGradientValue()
}

const updateGradientColorValue = (index: number, event: Event) => {
  const target = event.target as HTMLInputElement
  try {
    const validColor = chroma(target.value).hex()
    gradientColors.value[index].color = validColor
    updateGradientValue()
  } catch {
    // Invalid color, ignore
  }
}

const updateGradientColorPosition = (index: number, event: Event) => {
  const target = event.target as HTMLInputElement
  gradientColors.value[index].position = parseInt(target.value)
  updateGradientValue()
}

const addGradientColor = () => {
  const newPosition = gradientColors.value.length > 0 
    ? Math.min(100, Math.max(...gradientColors.value.map(c => c.position)) + 20)
    : 50
  
  gradientColors.value.push({ 
    color: '#000000', 
    position: newPosition 
  })
  updateGradientValue()
}

const removeGradientColor = (index: number) => {
  gradientColors.value.splice(index, 1)
  updateGradientValue()
}

const openGradientColorPicker = (index: number) => {
  // Create a temporary color input for this specific gradient color
  const tempInput = document.createElement('input')
  tempInput.type = 'color'
  tempInput.value = gradientColors.value[index].color
  tempInput.style.display = 'none'
  document.body.appendChild(tempInput)
  
  tempInput.addEventListener('change', (event) => {
    const target = event.target as HTMLInputElement
    gradientColors.value[index].color = target.value
    updateGradientValue()
    document.body.removeChild(tempInput)
  })
  
  tempInput.click()
}

const applyGradientPreset = (preset: GradientPreset) => {
  // Parse the preset gradient to extract colors and positions
  try {
    const gradientMatch = preset.value.match(/(linear|radial)-gradient\(([^)]+)\)/)
    if (gradientMatch) {
      const [, type, params] = gradientMatch
      gradientType.value = type
      
      // Extract colors from the gradient
      const colorMatches = params.match(/#[0-9a-fA-F]{6}/g) || []
      if (colorMatches.length >= 2) {
        gradientColors.value = colorMatches.map((color, index) => ({
          color,
          position: index * (100 / (colorMatches.length - 1))
        }))
      }
      
      updateGradientValue()
    }
  } catch (error) {
    console.warn('Failed to parse gradient preset:', error)
  }
}

const updateGradientValue = () => {
  const sortedStops = [...gradientColors.value].sort((a, b) => a.position - b.position)
  const colorStops = sortedStops.map(stop => `${stop.color} ${stop.position}%`).join(', ')
  
  const gradientValue = gradientType.value === 'linear' 
    ? `linear-gradient(${gradientDirection.value}, ${colorStops})`
    : `radial-gradient(circle, ${colorStops})`
    
  emit('update', gradientValue)
}

// Parse existing value when prop changes
watch(() => props.value, (newValue) => {
  try {
    if (newValue.startsWith('linear-gradient')) {
      activeTab.value = 'gradient'
      gradientType.value = 'linear'
      
      // Parse direction
      const directionMatch = newValue.match(/linear-gradient\(([^,]+),/)
      if (directionMatch) {
        gradientDirection.value = directionMatch[1].trim()
      }
      
      // Parse colors and positions
      const colorMatches = [...newValue.matchAll(/(#[0-9a-fA-F]{6}|rgb\([^)]+\))\s*(\d+%)?/g)]
      if (colorMatches.length >= 2) {
        gradientColors.value = colorMatches.map((match, index) => ({
          color: match[1],
          position: match[2] ? parseInt(match[2]) : index * (100 / (colorMatches.length - 1))
        }))
      }
    } else if (newValue.startsWith('radial-gradient')) {
      activeTab.value = 'gradient'
      gradientType.value = 'radial'
      
      // Parse colors and positions similar to linear
      const colorMatches = [...newValue.matchAll(/(#[0-9a-fA-F]{6}|rgb\([^)]+\))\s*(\d+%)?/g)]
      if (colorMatches.length >= 2) {
        gradientColors.value = colorMatches.map((match, index) => ({
          color: match[1],
          position: match[2] ? parseInt(match[2]) : index * (100 / (colorMatches.length - 1))
        }))
      }
    } else if (newValue.startsWith('rgba')) {
      // Parse RGBA for opacity
      const rgba = chroma(newValue)
      opacity.value = rgba.alpha()
    } else {
      activeTab.value = 'solid'
    }
  } catch (error) {
    console.warn('Failed to parse color value:', newValue, error)
  }
}, { immediate: true })
</script>

<style scoped>
/* Custom slider styling */
.slider {
  background: linear-gradient(to right, transparent 0%, currentColor 100%);
  outline: none;
  -webkit-appearance: none;
  appearance: none;
}

.slider::-webkit-slider-thumb {
  -webkit-appearance: none;
  appearance: none;
  width: 16px;
  height: 16px;
  border-radius: 50%;
  background: #ffffff;
  border: 2px solid #d1d5db;
  cursor: pointer;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.slider::-webkit-slider-thumb:hover {
  border-color: #8b5cf6;
}

.slider::-moz-range-thumb {
  width: 16px;
  height: 16px;
  border-radius: 50%;
  background: #ffffff;
  border: 2px solid #d1d5db;
  cursor: pointer;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.slider::-moz-range-thumb:hover {
  border-color: #8b5cf6;
}

/* Gradient color stop range slider */
input[type="range"] {
  -webkit-appearance: none;
  appearance: none;
  background: transparent;
  cursor: pointer;
}

input[type="range"]::-webkit-slider-track {
  background: #e5e7eb;
  height: 4px;
  border-radius: 2px;
}

input[type="range"]::-webkit-slider-thumb {
  -webkit-appearance: none;
  appearance: none;
  height: 12px;
  width: 12px;
  border-radius: 50%;
  background: #8b5cf6;
  border: 2px solid #ffffff;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.2);
}

input[type="range"]::-moz-range-track {
  background: #e5e7eb;
  height: 4px;
  border-radius: 2px;
  border: none;
}

input[type="range"]::-moz-range-thumb {
  height: 12px;
  width: 12px;
  border-radius: 50%;
  background: #8b5cf6;
  border: 2px solid #ffffff;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.2);
  cursor: pointer;
}
</style>

<template>
  <Menu as="div" class="relative">
    <MenuButton as="template">
      <button
        class="px-6 py-2.5 bg-gradient-to-r from-primary-600 to-primary-700 hover:from-primary-700 hover:to-primary-800 text-white rounded-xl font-semibold text-sm transition-all duration-200 hover:shadow-lg hover:shadow-primary-500/25 focus:outline-none focus:ring-2 focus:ring-primary-500/50 dark:focus:ring-primary-400/50 hover:scale-105 w-32 flex items-center justify-center"
      >
        <span>Export</span>
        <ChevronDownIcon class="w-4 h-4 ml-2" />
      </button>
    </MenuButton>

    <Transition
      enter-active-class="transition duration-100 ease-out"
      enter-from-class="transform scale-95 opacity-0"
      enter-to-class="transform scale-100 opacity-100"
      leave-active-class="transition duration-75 ease-in"
      leave-from-class="transform scale-100 opacity-100"
      leave-to-class="transform scale-95 opacity-0"
    >
      <MenuItems class="absolute right-0 mt-3 w-80 bg-white dark:bg-secondary-900 rounded-2xl shadow-xl border border-secondary-100 dark:border-secondary-800 py-3 z-50 backdrop-blur-sm">
        <NestedDropdownView>
          <!-- Main View - Quick Export Options -->
          <template #main="{ goToNested }">
            <!-- Quick Export Options -->
            <div class="px-4 py-2 border-b border-secondary-100 dark:border-secondary-800">
              <p class="text-xs font-semibold text-secondary-500 dark:text-secondary-400 uppercase tracking-wider mb-3">Quick Export</p>
              <div class="grid grid-cols-2 gap-2">
                <MenuItem v-slot="{ active }">
                  <button
                    @click="handleExport('png')"
                    :class="[
                      'flex items-center p-3 rounded-xl text-sm w-full transition-all duration-200',
                      active ? 'bg-primary-100 dark:bg-secondary-600 text-primary-700 dark:text-secondary-100 shadow-sm' : 'text-secondary-700 dark:text-secondary-300 hover:bg-secondary-100 dark:hover:bg-secondary-700'
                    ]"
                  >
                    <div class="w-10 h-10 bg-gradient-to-br from-blue-100 to-blue-200 dark:from-blue-900/30 dark:to-blue-800/30 rounded-xl flex items-center justify-center mr-3">
                      <PhotoIcon class="w-5 h-5 text-blue-600 dark:text-blue-400" />
                    </div>
                    <div class="text-left">
                      <p class="font-semibold">PNG</p>
                      <p class="text-xs text-secondary-500 dark:text-secondary-400">High quality</p>
                    </div>
                  </button>
                </MenuItem>
                
                <MenuItem v-slot="{ active }">
                  <button
                    @click="handleExport('jpg')"
                    :class="[
                      'flex items-center p-3 rounded-xl text-sm w-full transition-all duration-200',
                      active ? 'bg-primary-100 dark:bg-secondary-600 text-primary-700 dark:text-secondary-100 shadow-sm' : 'text-secondary-700 dark:text-secondary-300 hover:bg-secondary-100 dark:hover:bg-secondary-700'
                    ]"
                  >
                    <div class="w-10 h-10 bg-gradient-to-br from-emerald-100 to-emerald-200 dark:from-emerald-900/30 dark:to-emerald-800/30 rounded-xl flex items-center justify-center mr-3">
                      <PhotoIcon class="w-5 h-5 text-emerald-600 dark:text-emerald-400" />
                    </div>
                    <div class="text-left">
                      <p class="font-semibold">JPG</p>
                      <p class="text-xs text-secondary-500 dark:text-secondary-400">Smaller size</p>
                    </div>
                  </button>
                </MenuItem>
              </div>
            </div>

            <!-- More Formats -->
            <div class="px-4 py-2 border-b border-secondary-100 dark:border-secondary-800">
              <p class="text-xs font-semibold text-secondary-500 dark:text-secondary-400 uppercase tracking-wider mb-2">More Formats</p>
              <div class="space-y-1">
                <MenuItem 
                  v-for="format in moreFormats"
                  :key="format.value"
                  v-slot="{ active }"
                >
                  <button
                    @click="handleExport(format.value)"
                    :class="[
                      'flex items-center justify-between w-full px-3 py-2 text-sm rounded-lg transition-all duration-200',
                      active ? 'bg-primary-100 dark:bg-secondary-600 text-primary-700 dark:text-secondary-100' : 'text-secondary-700 dark:text-secondary-300 hover:bg-secondary-100 dark:hover:bg-secondary-700'
                    ]"
                  >
                    <div class="flex items-center">
                      <component :is="format.icon" class="w-4 h-4 mr-3 text-secondary-500 dark:text-secondary-400" />
                      <span class="font-medium">{{ format.label }}</span>
                    </div>
                    <span class="text-xs text-secondary-500 dark:text-secondary-400">{{ format.extension }}</span>
                  </button>
                </MenuItem>
              </div>
            </div>

            <!-- Advanced Export -->
            <div class="px-4 py-2">
              <button
                @click.stop="goToNested"
                class="flex items-center w-full px-3 py-2 text-sm rounded-lg transition-all duration-200 text-secondary-700 dark:text-secondary-300 hover:bg-primary-100 dark:hover:bg-secondary-600 hover:text-primary-700 dark:hover:text-secondary-100"
              >
                <Cog6ToothIcon class="w-4 h-4 mr-3 text-secondary-500 dark:text-secondary-400" />
                <span class="font-medium">Advanced Options</span>
              </button>
            </div>
          </template>

          <!-- Nested View - Advanced Export Options -->
          <template #nested="{ goBack }">
            <div class="space-y-4">
              <div>
                <h3 class="text-lg font-semibold text-secondary-900 dark:text-secondary-100 mb-4">Advanced Export</h3>
                <p class="text-sm text-secondary-500 dark:text-secondary-400 mb-6">Customize your export settings</p>
              </div>

              <!-- Format Selection -->
              <div>
                <label class="block text-sm font-medium text-secondary-700 dark:text-secondary-300 mb-2">
                  Format
                </label>
                <select
                  v-model="selectedFormat"
                  class="w-full px-3 py-2 bg-secondary-50 dark:bg-secondary-700 border border-secondary-200 dark:border-secondary-600 rounded-lg text-secondary-900 dark:text-secondary-100 focus:ring-2 focus:ring-primary-500/50 dark:focus:ring-primary-400/50 focus:border-primary-500 dark:focus:border-primary-400 transition-all duration-200"
                >
                  <option 
                    v-for="format in advancedFormats" 
                    :key="format.value" 
                    :value="format.value"
                  >
                    {{ format.label }}
                  </option>
                </select>
              </div>

              <!-- Quality Settings -->
              <div v-if="selectedFormat === 'jpg' || selectedFormat === 'webp'">
                <label class="block text-sm font-medium text-secondary-700 dark:text-secondary-300 mb-2">
                  Quality ({{ exportQuality }}%)
                </label>
                <input
                  v-model.number="exportQuality"
                  type="range"
                  min="10"
                  max="100"
                  step="10"
                  class="w-full h-2 bg-secondary-200 dark:bg-secondary-700 rounded-lg appearance-none cursor-pointer quality-slider"
                />
                <div class="flex justify-between text-xs text-secondary-500 dark:text-secondary-400 mt-1">
                  <span>Lower size</span>
                  <span>Higher quality</span>
                </div>
              </div>

              <!-- Background Options -->
              <div>
                <label class="block text-sm font-medium text-secondary-700 dark:text-secondary-300 mb-2">
                  Background
                </label>
                <div class="grid grid-cols-2 gap-2">
                  <button
                    @click="exportBackground = 'transparent'"
                    :class="[
                      'p-3 rounded-lg border text-sm transition-all duration-200',
                      exportBackground === 'transparent' 
                        ? 'bg-primary-100 dark:bg-secondary-600 border-primary-200 dark:border-secondary-500 text-primary-700 dark:text-secondary-100' 
                        : 'bg-secondary-50 dark:bg-secondary-700 border-secondary-200 dark:border-secondary-600 text-secondary-700 dark:text-secondary-300 hover:bg-secondary-100 dark:hover:bg-secondary-600'
                    ]"
                  >
                    Transparent
                  </button>
                  <button
                    @click="exportBackground = 'white'"
                    :class="[
                      'p-3 rounded-lg border text-sm transition-all duration-200',
                      exportBackground === 'white' 
                        ? 'bg-primary-100 dark:bg-secondary-600 border-primary-200 dark:border-secondary-500 text-primary-700 dark:text-secondary-100' 
                        : 'bg-secondary-50 dark:bg-secondary-700 border-secondary-200 dark:border-secondary-600 text-secondary-700 dark:text-secondary-300 hover:bg-secondary-100 dark:hover:bg-secondary-600'
                    ]"
                  >
                    White
                  </button>
                </div>
              </div>

              <!-- Action Buttons -->
              <div class="flex space-x-3 pt-2">
                <button
                  @click="handleAdvancedExport"
                  class="flex-1 px-4 py-2 bg-gradient-to-r from-primary-600 to-primary-700 hover:from-primary-700 hover:to-primary-800 text-white rounded-lg font-medium text-sm transition-all duration-200"
                >
                  Export {{ selectedFormat.toUpperCase() }}
                </button>
                <button
                  @click="goBack"
                  class="px-4 py-2 bg-secondary-100 dark:bg-secondary-700 hover:bg-secondary-200 dark:hover:bg-secondary-600 text-secondary-700 dark:text-secondary-300 rounded-lg font-medium text-sm transition-all duration-200"
                >
                  Cancel
                </button>
              </div>
            </div>
          </template>
        </NestedDropdownView>
      </MenuItems>
    </Transition>
  </Menu>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { Menu, MenuButton, MenuItems, MenuItem } from '@headlessui/vue'
import {
  ChevronDownIcon,
  PhotoIcon,
  FilmIcon,
  Cog6ToothIcon
} from '@heroicons/vue/24/outline'
import NestedDropdownView from '@/components/ui/NestedDropdownView.vue'
import { useDesignStore } from '@/stores/design'

interface Props {
  compact?: boolean;
}

withDefaults(defineProps<Props>(), {
  compact: false
})

const emit = defineEmits<{
  export: [format: string, options?: any]
  'advanced-export': []
}>()

// Design store to access current design
const designStore = useDesignStore()

// Advanced export state
const selectedFormat = ref('png')
const exportQuality = ref(90)
const exportBackground = ref<'transparent' | 'white'>('transparent')

// Check if current design has animations
const hasAnimations = computed(() => {
  const { currentDesign } = designStore
  if (!currentDesign?.layers) return false
  
  // Check if any layer has animation data or is a video layer
  return currentDesign.layers.some((layer: any) => {
    // Check for video layer type
    if (layer.type === 'video') {
      return true
    }
    
    // Check for animations property (backend entity structure)
    if (layer.animations && Array.isArray(layer.animations) && layer.animations.length > 0) {
      return true
    }
    
    // Check for animation properties in layer properties
    if (layer.properties?.animation) {
      return true
    }
    
    return false
  })
})

// Base formats that are always available
const baseFormats = [
  { 
    value: 'webp', 
    label: 'WebP Modern', 
    extension: '.webp',
    icon: PhotoIcon
  }
]

// Animation formats (only shown when design has animations)
const animationFormats = [
  { 
    value: 'gif', 
    label: 'Animated GIF', 
    extension: '.gif',
    icon: FilmIcon
  },
  { 
    value: 'mp4', 
    label: 'MP4 Video', 
    extension: '.mp4',
    icon: FilmIcon
  }
]

// Computed property for formats available in "More Formats" section
const moreFormats = computed(() => {
  const formats = [...baseFormats]
  
  // Add animation formats if design has animations
  if (hasAnimations.value) {
    formats.push(...animationFormats)
  }
  
  return formats
})

// Computed property for formats available in advanced export
const advancedFormats = computed(() => {
  const formats = [
    { value: 'png', label: 'PNG - High Quality' },
    { value: 'jpg', label: 'JPG - Smaller Size' },
    { value: 'webp', label: 'WebP - Modern' }
  ]
  
  // Add animation formats if design has animations
  if (hasAnimations.value) {
    formats.push(
      { value: 'gif', label: 'GIF - Animation' },
      { value: 'mp4', label: 'MP4 - Video' }
    )
  }
  
  return formats
})

const handleExport = (format: string) => {
  emit('export', format)
}

const handleAdvancedExport = () => {
  const options = {
    format: selectedFormat.value,
    quality: exportQuality.value,
    background: exportBackground.value
  }
  emit('export', selectedFormat.value, options)
}
</script>

<style scoped>
/* Quality slider styling */
.quality-slider {
  -webkit-appearance: none;
  appearance: none;
}

.quality-slider::-webkit-slider-track {
  background: rgb(229 231 235); /* secondary-200 */
  height: 8px;
  border-radius: 4px;
}

.dark .quality-slider::-webkit-slider-track {
  background: rgb(71 85 105); /* secondary-700 */
}

.quality-slider::-webkit-slider-thumb {
  -webkit-appearance: none;
  appearance: none;
  height: 16px;
  width: 16px;
  border-radius: 50%;
  background: rgb(59 130 246); /* primary-500 */
  border: 2px solid rgb(255 255 255); /* white */
  cursor: pointer;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
  transition: all 0.2s ease;
}

.dark .quality-slider::-webkit-slider-thumb {
  background: rgb(96 165 250); /* primary-400 */
  border-color: rgb(30 41 59); /* secondary-800 */
}

.quality-slider::-webkit-slider-thumb:hover {
  background: rgb(37 99 235); /* primary-600 */
  transform: scale(1.1);
}

.dark .quality-slider::-webkit-slider-thumb:hover {
  background: rgb(147 197 253); /* primary-300 */
}

.quality-slider::-moz-range-track {
  background: rgb(229 231 235); /* secondary-200 */
  height: 8px;
  border-radius: 4px;
  border: none;
}

.dark .quality-slider::-moz-range-track {
  background: rgb(71 85 105); /* secondary-700 */
}

.quality-slider::-moz-range-thumb {
  height: 16px;
  width: 16px;
  border-radius: 50%;
  background: rgb(59 130 246); /* primary-500 */
  border: 2px solid rgb(255 255 255); /* white */
  cursor: pointer;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
  transition: all 0.2s ease;
}

.dark .quality-slider::-moz-range-thumb {
  background: rgb(96 165 250); /* primary-400 */
  border-color: rgb(30 41 59); /* secondary-800 */
}

.quality-slider::-moz-range-thumb:hover {
  background: rgb(37 99 235); /* primary-600 */
}

.dark .quality-slider::-moz-range-thumb:hover {
  background: rgb(147 197 253); /* primary-300 */
}

.quality-slider:focus {
  outline: none;
  box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.5); /* primary-500/50 */
}

.dark .quality-slider:focus {
  box-shadow: 0 0 0 2px rgba(96, 165, 250, 0.5); /* primary-400/50 */
}
</style>

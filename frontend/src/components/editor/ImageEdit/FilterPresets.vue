<template>
  <div>
    <h4 class="text-sm font-medium text-gray-900 mb-4 flex items-center">
      <SparklesIcon class="w-4 h-4 mr-2 text-purple-500" />
      Filter Presets
    </h4>
    
    <div class="grid grid-cols-3 gap-2">
      <div 
        v-for="preset in filterPresets" 
        :key="preset.name"
        @click="applyPreset(preset)"
        :class="[
          'relative group cursor-pointer border-2 rounded-xl overflow-hidden transition-all duration-300 transform hover:scale-105',
          currentPreset === preset.name 
            ? 'border-blue-500 ring-2 ring-blue-500/20 shadow-lg' 
            : 'border-gray-200 hover:border-gray-300 hover:shadow-md'
        ]"
      >
        <!-- Preview with sample image -->
        <div class="aspect-square bg-gradient-to-br p-3 flex items-center justify-center relative overflow-hidden"
             :style="{ background: preset.preview.background }">
          <!-- Sample image element with filter applied -->
          <div 
            class="w-10 h-8 rounded-lg bg-cover bg-center relative transform transition-transform duration-300 group-hover:scale-110"
            :style="{ 
              backgroundImage: 'url(https://images.unsplash.com/photo-1501594907352-04cda38ebc29?w=100&h=80&fit=crop)',
              filter: preset.preview.cssFilter 
            }"
          >
            <!-- Overlay for better visual effect -->
            <div class="absolute inset-0 bg-black/5 rounded-lg"></div>
          </div>
          
          <!-- Selected indicator with animation -->
          <Transition 
            enter-active-class="transition-all duration-200 ease-out"
            enter-from-class="scale-0 opacity-0"
            enter-to-class="scale-100 opacity-100"
            leave-active-class="transition-all duration-150 ease-in"
            leave-from-class="scale-100 opacity-100"
            leave-to-class="scale-0 opacity-0"
          >
            <div v-if="currentPreset === preset.name" 
                 class="absolute top-1.5 right-1.5 w-5 h-5 bg-blue-500 rounded-full flex items-center justify-center shadow-md">
              <CheckIcon class="w-3 h-3 text-white" />
            </div>
          </Transition>
          
          <!-- Hover overlay -->
          <div class="absolute inset-0 bg-black/10 opacity-0 group-hover:opacity-100 transition-opacity duration-200 rounded-lg"></div>
        </div>
        
        <!-- Label with enhanced styling -->
        <div class="p-2 bg-white">
          <p class="text-xs font-semibold text-gray-900 text-center truncate">{{ preset.name }}</p>
          <p class="text-xs text-gray-500 text-center mt-0.5">{{ preset.description }}</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import type { ImageLayerProperties } from '@/types'
import { SparklesIcon, CheckIcon } from '@heroicons/vue/24/outline'

interface FilterPreset {
  name: string
  description: string
  values: Partial<ImageLayerProperties>
  preview: {
    background: string
    cssFilter: string
  }
}

interface Props {
  currentPreset: string
}

const props = defineProps<Props>()

const emit = defineEmits<{
  applyPreset: [preset: FilterPreset]
}>()

// Enhanced filter presets with better visual previews
const filterPresets: FilterPreset[] = [
  {
    name: 'Original',
    description: 'No filters',
    values: { brightness: 1, contrast: 1, saturation: 1, blur: 0, hue: 0, sepia: 0, grayscale: 0, invert: 0 },
    preview: {
      background: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
      cssFilter: 'none'
    }
  },
  {
    name: 'Vivid',
    description: 'Pop colors',
    values: { brightness: 1.1, contrast: 1.3, saturation: 1.4, blur: 0, hue: 5, sepia: 0, grayscale: 0, invert: 0 },
    preview: {
      background: 'linear-gradient(135deg, #f093fb 0%, #f5576c 100%)',
      cssFilter: 'brightness(1.1) contrast(1.3) saturate(1.4) hue-rotate(5deg)'
    }
  },
  {
    name: 'Cool',
    description: 'Blue tones',
    values: { brightness: 1.05, contrast: 1.1, saturation: 1.2, blur: 0, hue: -10, sepia: 0, grayscale: 0, invert: 0 },
    preview: {
      background: 'linear-gradient(135deg, #4facfe 0%, #00f2fe 100%)',
      cssFilter: 'brightness(1.05) contrast(1.1) saturate(1.2) hue-rotate(-10deg)'
    }
  },
  {
    name: 'Warm',
    description: 'Golden tones',
    values: { brightness: 1.1, contrast: 1.15, saturation: 1.1, blur: 0, hue: 15, sepia: 0.1, grayscale: 0, invert: 0 },
    preview: {
      background: 'linear-gradient(135deg, #fa709a 0%, #fee140 100%)',
      cssFilter: 'brightness(1.1) contrast(1.15) saturate(1.1) hue-rotate(15deg) sepia(0.1)'
    }
  },
  {
    name: 'Vintage',
    description: 'Retro feel',
    values: { brightness: 0.95, contrast: 1.2, saturation: 0.8, blur: 0, hue: 10, sepia: 0.3, grayscale: 0, invert: 0 },
    preview: {
      background: 'linear-gradient(135deg, #d299c2 0%, #fef9d7 100%)',
      cssFilter: 'brightness(0.95) contrast(1.2) saturate(0.8) hue-rotate(10deg) sepia(0.3)'
    }
  },
  {
    name: 'B&W',
    description: 'Monochrome',
    values: { brightness: 1, contrast: 1.1, saturation: 1, blur: 0, hue: 0, sepia: 0, grayscale: 1, invert: 0 },
    preview: {
      background: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
      cssFilter: 'grayscale(1) contrast(1.1)'
    }
  },
  {
    name: 'Sepia',
    description: 'Warm brown',
    values: { brightness: 1, contrast: 1.05, saturation: 0.9, blur: 0, hue: 0, sepia: 0.8, grayscale: 0, invert: 0 },
    preview: {
      background: 'linear-gradient(135deg, #c79081 0%, #dfa579 100%)',
      cssFilter: 'sepia(0.8) contrast(1.05) saturate(0.9)'
    }
  },
  {
    name: 'Soft',
    description: 'Gentle blur',
    values: { brightness: 1.05, contrast: 0.95, saturation: 0.95, blur: 1.5, hue: 0, sepia: 0, grayscale: 0, invert: 0 },
    preview: {
      background: 'linear-gradient(135deg, #a8edea 0%, #fed6e3 100%)',
      cssFilter: 'brightness(1.05) contrast(0.95) saturate(0.95) blur(1px)'
    }
  },
  {
    name: 'Drama',
    description: 'High contrast',
    values: { brightness: 0.9, contrast: 1.5, saturation: 1.2, blur: 0, hue: 0, sepia: 0, grayscale: 0, invert: 0 },
    preview: {
      background: 'linear-gradient(135deg, #434343 0%, #000000 100%)',
      cssFilter: 'brightness(0.9) contrast(1.5) saturate(1.2)'
    }
  }
]

const applyPreset = (preset: FilterPreset) => {
  emit('applyPreset', preset)
}
</script>

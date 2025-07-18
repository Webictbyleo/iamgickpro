<template>
  <!-- Compact View (in normal Elements panel) -->
  <div v-if="!fullView" class="space-y-4">
    <!-- Quick Color Section Header -->
    <div class="flex items-center justify-between">
      <h4 class="text-sm font-semibold text-gray-800 flex items-center">
        <div class="w-5 h-5 mr-2 rounded-lg bg-gradient-to-br from-orange-500 to-pink-500 flex items-center justify-center">
          <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zM21 5a2 2 0 00-2-2h-4a2 2 0 00-2 2v12a4 4 0 004 4h4a2 2 0 002-2V5z" />
          </svg>
        </div>
        Quick Colors
      </h4>
      <button
        @click="enterColorMode"
        class="group flex items-center px-3 py-1.5 text-xs font-semibold text-orange-600 bg-orange-50 border border-orange-200 rounded-lg hover:bg-orange-100 hover:border-orange-300 transition-all duration-200 hover:shadow-sm"
      >
        <span>More Colors</span>
        <svg class="w-3 h-3 ml-1 transition-transform group-hover:translate-x-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
        </svg>
      </button>
    </div>

    <!-- Color Picker Row -->
    <div class="flex items-center space-x-4">
      <!-- Main Color Picker -->
      <div class="relative group">
        <input
          v-model="currentColor"
          type="color"
          @input="handleCompactColorChange"
          class="w-10 h-10 border-3 border-white rounded-xl cursor-pointer shadow-lg hover:shadow-xl transition-all duration-200 ring-2 ring-gray-200 hover:ring-gray-300"
          title="Choose custom color"
        />
        <div class="absolute -bottom-1 -right-1 w-4 h-4 bg-white rounded-full border-2 border-gray-200 flex items-center justify-center shadow-sm">
          <div class="w-2 h-2 rounded-full bg-gradient-to-br from-orange-400 to-pink-500"></div>
        </div>
        <!-- Tooltip -->
        <div class="absolute -top-8 left-1/2 transform -translate-x-1/2 px-2 py-1 bg-gray-900 text-white text-xs rounded opacity-0 group-hover:opacity-100 transition-opacity duration-200 pointer-events-none whitespace-nowrap">
          {{ currentColor }}
        </div>
      </div>

      <!-- Quick Color Swatches -->
      <div class="flex flex-wrap gap-2 flex-1">
        <button
          v-for="(color, index) in quickColors"
          :key="color"
          @click="selectColor(color)"
          :style="{ backgroundColor: color }"
          class="group relative w-7 h-7 flex-shrink-0 rounded-lg border-2 border-white hover:border-gray-300 transition-all duration-200 hover:scale-110 shadow-md hover:shadow-lg ring-1 ring-gray-200 hover:ring-gray-300"
          :title="color"
        >
          <!-- Selection indicator -->
          <div 
            v-if="currentColor.toLowerCase() === color.toLowerCase()"
            class="absolute inset-0 rounded-lg ring-2 ring-orange-500 ring-offset-1"
          ></div>
          <!-- Hover tooltip -->
          <div class="absolute -top-8 left-1/2 transform -translate-x-1/2 px-2 py-1 bg-gray-900 text-white text-xs rounded opacity-0 group-hover:opacity-100 transition-opacity duration-200 pointer-events-none whitespace-nowrap z-10">
            {{ color }}
          </div>
        </button>
      </div>
    </div>

    <!-- Recent Colors (if any) -->
    <div v-if="recentColors.length > 0" class="space-y-2">
      <p class="text-xs font-medium text-gray-600">Recent</p>
      <div class="flex space-x-1.5">
        <button
          v-for="color in recentColors.slice(0, 6)"
          :key="color"
          @click="selectColor(color)"
          :style="{ backgroundColor: color }"
          class="group relative w-5 h-5 rounded border border-gray-200 hover:border-gray-300 transition-all duration-200 hover:scale-110 shadow-sm"
          :title="color"
        >
          <!-- Selection indicator -->
          <div 
            v-if="currentColor.toLowerCase() === color.toLowerCase()"
            class="absolute inset-0 rounded ring-2 ring-orange-500 ring-offset-1"
          ></div>
        </button>
      </div>
    </div>
  </div>

  <!-- Full View (dedicated color mode) -->
  <div v-else class="h-full flex flex-col bg-white">
    <!-- Compact Header with Back Button -->
    <div class="p-3 border-b border-gray-200 bg-gray-50">
      <div class="flex items-center justify-between">
        <div class="flex items-center space-x-3">
          <button
            @click="exitColorMode"
            class="w-8 h-8 flex items-center justify-center rounded-lg border border-gray-300 bg-white hover:bg-gray-50 transition-colors"
            title="Back to Elements"
          >
            <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
            </svg>
          </button>
          <div>
            <h3 class="text-sm font-semibold text-gray-900">Colors</h3>
          </div>
        </div>
        <div class="text-xs text-gray-500 bg-white px-2 py-1 rounded border">
          {{ recentColors.length }} recent
        </div>
      </div>
    </div>

    <!-- Color Content -->
    <div class="flex-1 overflow-y-auto p-3 space-y-4">
      <!-- Current Color Display -->
      <div class="bg-gray-50 rounded-lg p-3">
        <h4 class="text-sm font-medium text-gray-700 mb-3">Custom Color</h4>
        <div class="flex items-center space-x-3">
          <input
            v-model="currentColor"
            type="color"
            class="w-12 h-12 border-2 border-white rounded-lg cursor-pointer shadow-sm"
            title="Choose custom color"
          />
          <div class="flex-1 space-y-2">
            <input
              v-model="currentColor"
              type="text"
              class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 font-mono"
              placeholder="#000000"
            />
            <button
              @click="handleColorChange"
              class="w-full px-3 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition-colors"
            >
              Apply
            </button>
          </div>
        </div>
      </div>

      <!-- Quick Access Colors -->
      <div>
        <h4 class="text-sm font-medium text-gray-700 mb-3">Quick Access</h4>
        <div class="grid grid-cols-4 gap-2">
          <button
            v-for="color in quickColors"
            :key="color"
            @click="selectColor(color)"
            :style="{ backgroundColor: color }"
            class="w-full aspect-square rounded-lg border-2 border-white hover:border-gray-300 transition-all duration-200 hover:scale-105 shadow-sm"
            :class="{ 'ring-2 ring-blue-500 ring-offset-1': currentColor.toLowerCase() === color.toLowerCase() }"
            :title="color"
          >
          </button>
        </div>
      </div>

      <!-- Popular Colors Palette -->
      <div>
        <h4 class="text-sm font-medium text-gray-700 mb-3">Popular Colors</h4>
        <div class="grid grid-cols-6 gap-1.5">
          <button
            v-for="color in popularColors.slice(0, 24)"
            :key="color"
            @click="selectColor(color)"
            :style="{ backgroundColor: color }"
            class="w-full aspect-square rounded border border-gray-200 hover:border-gray-400 transition-all duration-200 hover:scale-105"
            :class="{ 'ring-2 ring-blue-500 ring-offset-1': currentColor.toLowerCase() === color.toLowerCase() }"
            :title="color"
          >
          </button>
        </div>
      </div>

      <!-- Recent Colors -->
      <div v-if="recentColors.length > 0">
        <div class="flex items-center justify-between mb-3">
          <h4 class="text-sm font-medium text-gray-700">Recent Colors</h4>
          <button
            @click="clearRecentColors"
            class="text-xs text-gray-500 hover:text-red-600 transition-colors"
          >
            Clear
          </button>
        </div>
        <div class="grid grid-cols-6 gap-1.5">
          <button
            v-for="color in recentColors.slice(0, 12)"
            :key="color"
            @click="selectColor(color)"
            :style="{ backgroundColor: color }"
            class="w-full aspect-square rounded border border-gray-200 hover:border-gray-400 transition-all duration-200 hover:scale-105"
            :class="{ 'ring-2 ring-blue-500 ring-offset-1': currentColor.toLowerCase() === color.toLowerCase() }"
            :title="color"
          >
          </button>
        </div>
      </div>

      <!-- Color Palettes -->
      <div class="space-y-4">
        <!-- Brand Colors -->
        <div>
          <h5 class="text-sm font-medium text-gray-700 mb-2">Brand Colors</h5>
          <div class="grid grid-cols-6 gap-1.5">
            <button
              v-for="color in brandColors.slice(0, 12)"
              :key="color"
              @click="selectColor(color)"
              :style="{ backgroundColor: color }"
              class="w-full aspect-square rounded border border-gray-200 hover:border-gray-400 transition-all duration-200 hover:scale-105"
              :class="{ 'ring-2 ring-blue-500 ring-offset-1': currentColor.toLowerCase() === color.toLowerCase() }"
              :title="color"
            >
            </button>
          </div>
        </div>

        <!-- Pastel Colors -->
        <div>
          <h5 class="text-sm font-medium text-gray-700 mb-2">Pastel Colors</h5>
          <div class="grid grid-cols-6 gap-1.5">
            <button
              v-for="color in pastelColors.slice(0, 12)"
              :key="color"
              @click="selectColor(color)"
              :style="{ backgroundColor: color }"
              class="w-full aspect-square rounded border border-gray-200 hover:border-gray-400 transition-all duration-200 hover:scale-105"
              :class="{ 'ring-2 ring-blue-500 ring-offset-1': currentColor.toLowerCase() === color.toLowerCase() }"
              :title="color"
            >
            </button>
          </div>
        </div>

        <!-- Grayscale -->
        <div>
          <h5 class="text-sm font-medium text-gray-700 mb-2">Grayscale</h5>
          <div class="grid grid-cols-6 gap-1.5">
            <button
              v-for="color in grayscaleColors.slice(0, 12)"
              :key="color"
              @click="selectColor(color)"
              :style="{ backgroundColor: color }"
              class="w-full aspect-square rounded border border-gray-200 hover:border-gray-400 transition-all duration-200 hover:scale-105"
              :class="{ 'ring-2 ring-blue-500 ring-offset-1': currentColor.toLowerCase() === color.toLowerCase() }"
              :title="color"
            >
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'

interface Props {
  fullView?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  fullView: false
})

const emit = defineEmits<{
  'apply-color': [color: string]
  'enter-color-mode': []
  'exit-color-mode': []
}>()

// State
const currentColor = ref('#3B82F6')
const recentColors = ref<string[]>([])

// Quick access colors - commonly used in design
const quickColors = [
  '#000000', // Black
  '#FFFFFF', // White  
  '#EF4444', // Red
  '#3B82F6', // Blue
  '#10B981', // Green
  '#F59E0B', // Yellow
  '#8B5CF6', // Purple
  '#F97316'  // Orange
]

// Extended popular colors for the expanded view
const popularColors = [
  // Grays
  '#000000', '#374151', '#6B7280', '#9CA3AF', '#D1D5DB', '#F3F4F6', '#F9FAFB', '#FFFFFF',
  // Reds
  '#7F1D1D', '#DC2626', '#EF4444', '#F87171', '#FCA5A5', '#FECACA', '#FEE2E2', '#FEF2F2',
  // Blues
  '#1E3A8A', '#1D4ED8', '#3B82F6', '#60A5FA', '#93C5FD', '#BFDBFE', '#DBEAFE', '#EFF6FF',
  // Greens
  '#14532D', '#15803D', '#16A34A', '#22C55E', '#4ADE80', '#86EFAC', '#BBF7D0', '#DCFCE7',
  // Yellows/Oranges
  '#92400E', '#D97706', '#F59E0B', '#FBBF24', '#FCD34D', '#FDE68A', '#FEF3C7', '#FFFBEB',
  // Purples
  '#581C87', '#7C2D12', '#8B5CF6', '#A78BFA', '#C4B5FD', '#DDD6FE', '#EDE9FE', '#F5F3FF',
  // Pinks
  '#831843', '#BE185D', '#EC4899', '#F472B6', '#F9A8D4', '#FBCFE8', '#FCE7F3', '#FDF2F8',
  // Teals
  '#134E4A', '#0F766E', '#14B8A6', '#2DD4BF', '#5EEAD4', '#99F6E4', '#CCFBF1', '#F0FDFA'
]

// Brand colors (modern web design colors)
const brandColors = [
  '#1DA1F2', // Twitter Blue
  '#4267B2', // Facebook Blue
  '#E4405F', // Instagram Pink
  '#FF0000', // YouTube Red
  '#25D366', // WhatsApp Green
  '#FFFC00', // Snapchat Yellow
  '#FF6900', // Reddit Orange
  '#6441A4', // Twitch Purple
  '#1BD741', // Spotify Green
  '#FF1744', // Pinterest Red
  '#00C851', // Android Green
  '#FF8A80', // Coral
  '#8BC34A', // Light Green
  '#FFD600', // Amber
  '#FF5722', // Deep Orange
  '#795548'  // Brown
]

// Pastel colors
const pastelColors = [
  '#FFB3BA', '#FFDFBA', '#FFFFBA', '#BAFFC9',
  '#BAE1FF', '#D4BAFF', '#FFB3E6', '#FFB3B3',
  '#B3FFB3', '#B3E5FC', '#E1BEE7', '#F8BBD9',
  '#FFE0B2', '#DCEDC8', '#C8E6C9', '#B39DDB',
  '#FFCDD2', '#F8BBD9', '#E1BEE7', '#D1C4E9',
  '#C5CAE9', '#BBDEFB', '#B3E5FC', '#B2EBF2',
  '#B2DFDB', '#C8E6C9', '#DCEDC8', '#F0F4C3',
  '#FFF9C4', '#FFECB3', '#FFE0B2', '#FFCCBC'
]

// Grayscale colors
const grayscaleColors = [
  '#000000', '#212121', '#424242', '#616161',
  '#757575', '#9E9E9E', '#BDBDBD', '#E0E0E0',
  '#EEEEEE', '#F5F5F5', '#FAFAFA', '#FFFFFF',
  '#263238', '#37474F', '#455A64', '#546E7A',
  '#607D8B', '#78909C', '#90A4AE', '#B0BEC5',
  '#CFD8DC', '#ECEFF1', '#F5F5F5', '#FAFAFA',
  '#FFFFFF', '#F8F9FA', '#E9ECEF', '#DEE2E6',
  '#CED4DA', '#ADB5BD', '#6C757D', '#495057'
]

// Methods
const handleColorChange = () => {
  // Only add to recent colors in full view when Apply button is clicked
  if (props.fullView) {
    addToRecentColors(currentColor.value)
  }
  emit('apply-color', currentColor.value)
}

const handleCompactColorChange = () => {
  // In compact view, don't add to recent colors for the main color picker
  // Users should use color swatches for frequently used colors
  emit('apply-color', currentColor.value)
}

const selectColor = (color: string) => {
  currentColor.value = color
  addToRecentColors(color)
  emit('apply-color', color)
}

const enterColorMode = () => {
  emit('enter-color-mode')
}

const exitColorMode = () => {
  emit('exit-color-mode')
}

const addToRecentColors = (color: string) => {
  // Remove if already exists to move to front
  const filtered = recentColors.value.filter(c => c !== color)
  // Add to front and limit to 16 items
  recentColors.value = [color, ...filtered].slice(0, 16)
  // Save to localStorage
  localStorage.setItem('quickColorPicker_recentColors', JSON.stringify(recentColors.value))
}

const clearRecentColors = () => {
  recentColors.value = []
  localStorage.removeItem('quickColorPicker_recentColors')
}

// Load recent colors from localStorage on mount
onMounted(() => {
  try {
    const saved = localStorage.getItem('quickColorPicker_recentColors')
    if (saved) {
      recentColors.value = JSON.parse(saved).slice(0, 16)
    }
  } catch (error) {
    console.warn('Failed to load recent colors from localStorage:', error)
  }
})
</script>

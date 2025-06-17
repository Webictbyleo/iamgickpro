<template>
  <div class="h-full flex flex-col bg-white">
    <!-- Header -->
    <div class="p-4 border-b bg-gray-50">
      <div class="flex items-center space-x-3">
        <div class="w-8 h-8 bg-gradient-to-br from-blue-500 to-purple-600 rounded-lg flex items-center justify-center">
          <AdjustmentsHorizontalIcon class="w-4 h-4 text-white" />
        </div>
        <div>
          <h3 class="text-sm font-semibold text-gray-900">Image Effects</h3>
          <p class="text-xs text-gray-600">Transform your images with professional filters</p>
        </div>
      </div>
    </div>

    <!-- Content -->
    <div class="flex-1 overflow-y-auto p-4 space-y-6">
      <!-- Filter Presets -->
      <FilterPresets 
        :currentPreset="currentPreset"
        @applyPreset="handlePresetApplication"
      />

      <!-- Custom Adjustments -->
      <CustomAdjustments
        :brightness="brightness"
        :contrast="contrast"
        :saturation="saturation"
        :hue="hue"
        :blur="blur"
        :sepia="sepia"
        :grayscale="grayscale"
        :invert="invert"
        @updateProperty="updateProperty"
        @resetFilters="resetFilters"
      />

      <!-- Shadow Controls -->
      <ShadowControls
        :shadowEnabled="shadowEnabled"
        :shadowOffsetX="shadowOffsetX"
        :shadowOffsetY="shadowOffsetY"
        :shadowBlur="shadowBlur"
        :shadowColor="shadowColor"
        :shadowOpacity="shadowOpacity"
        @updateProperty="updateProperty"
        @resetShadow="resetShadow"
      />

      <!-- Border Radius Controls -->
      <BorderRadiusControls
        :borderRadius="borderRadius"
        @updateBorderRadius="(value: number) => updateProperty('borderRadius', value)"
      />

      <!-- Reset All -->
      <div class="pt-4 border-t border-gray-200">
        <button
          @click="resetToDefaults"
          class="w-full flex items-center justify-center px-3 py-2 text-xs font-medium text-red-600 bg-red-50 border border-red-200 rounded-lg hover:bg-red-100 hover:border-red-300 transition-colors"
        >
          <ArrowPathIcon class="w-3 h-3 mr-1" />
          Reset All to Defaults
        </button>
      </div>
    </div>

    <!-- Apply Button -->
    <div class="p-4 border-t bg-gray-50">
      <button
        @click="resetToDefaults"
        class="w-full flex items-center justify-center px-4 py-2 text-sm font-medium text-gray-600 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors"
      >
        <ArrowPathIcon class="w-4 h-4 mr-2" />
        Reset All to Defaults
      </button>
      <p class="text-xs text-gray-500 mt-2 text-center">All changes apply in real-time</p>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import type { ImageLayerProperties } from '@/types'
import {
  AdjustmentsHorizontalIcon,
  ArrowPathIcon
} from '@heroicons/vue/24/outline'

// Modular components
import FilterPresets from '../ImageEdit/FilterPresets.vue'
import CustomAdjustments from '../ImageEdit/CustomAdjustments.vue'
import ShadowControls from '../ImageEdit/ShadowControls.vue'
import BorderRadiusControls from '../ImageEdit/BorderRadiusControls.vue'

// Define props
interface Props {
  properties: ImageLayerProperties
}

const props = defineProps<Props>()

// Define emits
const emit = defineEmits<{
  update: [properties: Partial<ImageLayerProperties>]
}>()

// Reactive properties (removed unused properties: alt, quality, flipX, flipY, preserveAspectRatio)
const src = ref(props.properties.src || '')
const blur = ref(props.properties.blur || 0)
const brightness = ref(props.properties.brightness || 1)
const contrast = ref(props.properties.contrast || 1)
const saturation = ref(props.properties.saturation || 1)
const hue = ref(props.properties.hue || 0)
const sepia = ref(props.properties.sepia || 0)
const grayscale = ref(props.properties.grayscale || 0)
const invert = ref(props.properties.invert || 0)
const borderRadius = ref(props.properties.borderRadius || 0)

// Shadow properties
const shadowEnabled = ref(!!props.properties.shadow)
const shadowOffsetX = ref(props.properties.shadow?.offsetX || 5)
const shadowOffsetY = ref(props.properties.shadow?.offsetY || 5)
const shadowBlur = ref(props.properties.shadow?.blur || 5)
const shadowColor = ref(props.properties.shadow?.color || '#000000')
const shadowOpacity = ref(props.properties.shadow?.opacity || 0.5)

// Current preset tracking
const currentPreset = ref('Original')

// Methods
const updateProperty = (key: string, value: any) => {
  switch (key) {
    case 'src':
      src.value = value
      break
    case 'blur':
      blur.value = value
      break
    case 'brightness':
      brightness.value = value
      break
    case 'contrast':
      contrast.value = value
      break
    case 'saturation':
      saturation.value = value
      break
    case 'hue':
      hue.value = value
      break
    case 'sepia':
      sepia.value = value
      break
    case 'grayscale':
      grayscale.value = value
      break
    case 'invert':
      invert.value = value
      break
    case 'borderRadius':
      borderRadius.value = value
      break
    case 'shadowEnabled':
      shadowEnabled.value = value
      break
    case 'shadowOffsetX':
      shadowOffsetX.value = value
      break
    case 'shadowOffsetY':
      shadowOffsetY.value = value
      break
    case 'shadowBlur':
      shadowBlur.value = value
      break
    case 'shadowColor':
      shadowColor.value = value
      break
    case 'shadowOpacity':
      shadowOpacity.value = value
      break
  }
  
  // Reset current preset when manually adjusting (except shadow enabled toggle)
  if (key !== 'shadowEnabled') {
    currentPreset.value = 'Custom'
  }
  
  // Emit real-time update to apply changes immediately
  emitRealTimeUpdate()
}

const emitRealTimeUpdate = () => {
  const properties: Partial<ImageLayerProperties> = {
    src: src.value,
    blur: blur.value,
    brightness: brightness.value,
    contrast: contrast.value,
    saturation: saturation.value,
    hue: hue.value,
    sepia: sepia.value,
    grayscale: grayscale.value,
    invert: invert.value,
    borderRadius: borderRadius.value,
    shadow: shadowEnabled.value ? {
      enabled: true,
      offsetX: shadowOffsetX.value,
      offsetY: shadowOffsetY.value,
      blur: shadowBlur.value,
      color: shadowColor.value,
      opacity: shadowOpacity.value
    } : undefined
  }
  
  emit('update', properties)
}

const handlePresetApplication = (preset: any) => {
  currentPreset.value = preset.name
  
  // Apply all preset values efficiently
  const presetValues = preset.values
  
  // Special handling for "Original" preset to ensure it works like resetFilters
  if (preset.name === 'Original') {
    brightness.value = 1
    contrast.value = 1
    saturation.value = 1
    blur.value = 0
    hue.value = 0
    sepia.value = 0
    grayscale.value = 0
    invert.value = 0
  } else {
    // Update all values at once for other presets
    if (presetValues.brightness !== undefined) brightness.value = presetValues.brightness
    if (presetValues.contrast !== undefined) contrast.value = presetValues.contrast
    if (presetValues.saturation !== undefined) saturation.value = presetValues.saturation
    if (presetValues.blur !== undefined) blur.value = presetValues.blur
    if (presetValues.hue !== undefined) hue.value = presetValues.hue
    if (presetValues.sepia !== undefined) sepia.value = presetValues.sepia
    if (presetValues.grayscale !== undefined) grayscale.value = presetValues.grayscale  
    if (presetValues.invert !== undefined) invert.value = presetValues.invert
  }
  
  // Emit single real-time update after all values are set
  emitRealTimeUpdate()
}

const resetFilters = () => {
  // Reset filter properties to default values
  brightness.value = 1
  contrast.value = 1
  saturation.value = 1
  blur.value = 0
  hue.value = 0
  sepia.value = 0
  grayscale.value = 0
  invert.value = 0
  currentPreset.value = 'Original'
  
  // Emit real-time update
  emitRealTimeUpdate()
}

const resetShadow = () => {
  // Reset shadow properties to default values
  shadowEnabled.value = false
  shadowOffsetX.value = 5
  shadowOffsetY.value = 5
  shadowBlur.value = 5
  shadowColor.value = '#000000'
  shadowOpacity.value = 0.5
  
  // Emit real-time update
  emitRealTimeUpdate()
}

const resetToDefaults = () => {
  // Reset all properties to default values
  resetFilters()
  resetShadow()
  borderRadius.value = 0
  
  // Emit real-time update
  emitRealTimeUpdate()
}
</script>

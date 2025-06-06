<template>
  <div class="p-4 space-y-6">
    <!-- Animation Controls -->
    <div class="space-y-4">
      <div class="flex items-center justify-between">
        <h3 class="text-sm font-medium text-gray-900">Animation</h3>
        <button
          @click="toggleAnimation"
          :class="[
            'px-3 py-1.5 text-xs font-medium rounded-md transition-colors',
            isAnimationEnabled
              ? 'bg-green-100 text-green-800 hover:bg-green-200'
              : 'bg-gray-100 text-gray-600 hover:bg-gray-200'
          ]"
        >
          {{ isAnimationEnabled ? 'Enabled' : 'Disabled' }}
        </button>
      </div>

      <!-- Animation Type Selection -->
      <div v-if="isAnimationEnabled" class="space-y-3">
        <label class="block text-xs font-medium text-gray-700">Animation Type</label>
        <select
          v-model="selectedAnimationType"
          class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
          @change="applyAnimationType"
        >
          <option value="">Select Animation</option>
          <optgroup label="Entrance">
            <option value="fadeIn">Fade In</option>
            <option value="slideInFromLeft">Slide In Left</option>
            <option value="slideInFromRight">Slide In Right</option>
            <option value="slideInFromTop">Slide In Top</option>
            <option value="slideInFromBottom">Slide In Bottom</option>
            <option value="scaleIn">Scale In</option>
            <option value="bounceIn">Bounce In</option>
            <option value="rotateIn">Rotate In</option>
          </optgroup>
          <optgroup label="Exit">
            <option value="fadeOut">Fade Out</option>
            <option value="slideOutToLeft">Slide Out Left</option>
            <option value="slideOutToRight">Slide Out Right</option>
            <option value="slideOutToTop">Slide Out Top</option>
            <option value="slideOutToBottom">Slide Out Bottom</option>
            <option value="scaleOut">Scale Out</option>
            <option value="rotateOut">Rotate Out</option>
          </optgroup>
          <optgroup label="Emphasis">
            <option value="pulse">Pulse</option>
            <option value="shake">Shake</option>
            <option value="bounce">Bounce</option>
            <option value="wobble">Wobble</option>
            <option value="swing">Swing</option>
            <option value="flash">Flash</option>
          </optgroup>
        </select>
      </div>
    </div>

    <!-- Animation Settings -->
    <div v-if="isAnimationEnabled && selectedAnimationType" class="space-y-4">
      <h4 class="text-sm font-medium text-gray-900">Animation Settings</h4>
      
      <!-- Duration -->
      <div class="space-y-2">
        <label class="block text-xs font-medium text-gray-700">
          Duration: {{ animationSettings.duration }}s
        </label>
        <input
          v-model.number="animationSettings.duration"
          type="range"
          min="0.1"
          max="5"
          step="0.1"
          class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer slider"
          @input="updateAnimationSettings"
        />
      </div>

      <!-- Delay -->
      <div class="space-y-2">
        <label class="block text-xs font-medium text-gray-700">
          Delay: {{ animationSettings.delay }}s
        </label>
        <input
          v-model.number="animationSettings.delay"
          type="range"
          min="0"
          max="3"
          step="0.1"
          class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer slider"
          @input="updateAnimationSettings"
        />
      </div>

      <!-- Easing -->
      <div class="space-y-2">
        <label class="block text-xs font-medium text-gray-700">Easing</label>
        <select
          v-model="animationSettings.easing"
          class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
          @change="updateAnimationSettings"
        >
          <option value="linear">Linear</option>
          <option value="ease">Ease</option>
          <option value="ease-in">Ease In</option>
          <option value="ease-out">Ease Out</option>
          <option value="ease-in-out">Ease In Out</option>
          <option value="cubic-bezier(0.68, -0.55, 0.265, 1.55)">Bounce</option>
          <option value="cubic-bezier(0.25, 0.46, 0.45, 0.94)">Smooth</option>
        </select>
      </div>

      <!-- Repeat -->
      <div class="space-y-2">
        <label class="flex items-center">
          <input
            v-model="animationSettings.repeat"
            type="checkbox"
            class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
            @change="updateAnimationSettings"
          />
          <span class="ml-2 text-sm text-gray-700">Repeat Animation</span>
        </label>
        
        <div v-if="animationSettings.repeat" class="ml-6 space-y-2">
          <label class="block text-xs font-medium text-gray-700">
            Repeat Count (0 = infinite)
          </label>
          <input
            v-model.number="animationSettings.repeatCount"
            type="number"
            min="0"
            max="10"
            class="w-20 px-2 py-1 text-sm border border-gray-300 rounded focus:ring-1 focus:ring-blue-500 focus:border-blue-500"
            @input="updateAnimationSettings"
          />
        </div>
      </div>
    </div>

    <!-- Timeline Preview -->
    <div v-if="isAnimationEnabled" class="space-y-4">
      <div class="flex items-center justify-between">
        <h4 class="text-sm font-medium text-gray-900">Timeline</h4>
        <div class="flex space-x-2">
          <button
            @click="previewAnimation"
            class="px-3 py-1.5 text-xs font-medium text-blue-600 border border-blue-600 rounded-md hover:bg-blue-50 transition-colors"
          >
            {{ isPreviewPlaying ? 'Stop' : 'Preview' }}
          </button>
          <button
            @click="resetAnimation"
            class="px-3 py-1.5 text-xs font-medium text-gray-600 border border-gray-300 rounded-md hover:bg-gray-50 transition-colors"
          >
            Reset
          </button>
        </div>
      </div>

      <!-- Timeline Bar -->
      <div class="space-y-2">
        <div class="relative h-8 bg-gray-100 rounded-lg overflow-hidden">
          <!-- Animation duration visualization -->
          <div
            class="absolute top-0 left-0 h-full bg-gradient-to-r from-blue-400 to-purple-500 rounded-lg transition-all duration-300"
            :style="{
              width: `${Math.min(100, (animationSettings.duration / 5) * 100)}%`,
              marginLeft: `${Math.min(80, (animationSettings.delay / 3) * 80)}%`
            }"
          ></div>
          
          <!-- Timeline markers -->
          <div class="absolute inset-0 flex items-center justify-between px-2 text-xs text-gray-500">
            <span>0s</span>
            <span>{{ totalAnimationTime }}s</span>
          </div>
          
          <!-- Progress indicator -->
          <div
            v-if="isPreviewPlaying"
            class="absolute top-0 w-1 h-full bg-red-500 transition-all"
            :style="{
              left: `${animationProgress}%`,
              transition: `left ${totalAnimationTime}s linear`
            }"
          ></div>
        </div>
        
        <div class="flex justify-between text-xs text-gray-500">
          <span>Delay: {{ animationSettings.delay }}s</span>
          <span>Duration: {{ animationSettings.duration }}s</span>
        </div>
      </div>
    </div>

    <!-- Animation Presets -->
    <div class="space-y-4">
      <h4 class="text-sm font-medium text-gray-900">Quick Presets</h4>
      <div class="grid grid-cols-2 gap-2">
        <button
          v-for="preset in animationPresets"
          :key="preset.name"
          @click="applyPreset(preset)"
          class="p-3 text-left border border-gray-200 rounded-lg hover:border-blue-500 hover:bg-blue-50 transition-colors group"
        >
          <div class="text-sm font-medium text-gray-900 group-hover:text-blue-900">
            {{ preset.name }}
          </div>
          <div class="text-xs text-gray-500 group-hover:text-blue-600 mt-1">
            {{ preset.description }}
          </div>
        </button>
      </div>
    </div>

    <!-- Keyframe Editor (Advanced) -->
    <div v-if="showAdvanced" class="space-y-4">
      <div class="flex items-center justify-between">
        <h4 class="text-sm font-medium text-gray-900">Keyframes</h4>
        <button
          @click="addKeyframe"
          class="px-3 py-1.5 text-xs font-medium text-blue-600 border border-blue-600 rounded-md hover:bg-blue-50 transition-colors"
        >
          Add Keyframe
        </button>
      </div>
      
      <div class="space-y-2">
        <div
          v-for="(keyframe, index) in keyframes"
          :key="index"
          class="flex items-center space-x-3 p-3 border border-gray-200 rounded-lg"
        >
          <input
            v-model.number="keyframe.time"
            type="number"
            min="0"
            :max="animationSettings.duration"
            step="0.1"
            class="w-16 px-2 py-1 text-xs border border-gray-300 rounded focus:ring-1 focus:ring-blue-500"
            placeholder="Time"
          />
          <span class="text-xs text-gray-500">s</span>
          
          <select
            v-model="keyframe.property"
            class="flex-1 px-2 py-1 text-xs border border-gray-300 rounded focus:ring-1 focus:ring-blue-500"
          >
            <option value="opacity">Opacity</option>
            <option value="x">Position X</option>
            <option value="y">Position Y</option>
            <option value="scaleX">Scale X</option>
            <option value="scaleY">Scale Y</option>
            <option value="rotation">Rotation</option>
          </select>
          
          <input
            v-model="keyframe.value"
            type="text"
            class="w-20 px-2 py-1 text-xs border border-gray-300 rounded focus:ring-1 focus:ring-blue-500"
            placeholder="Value"
          />
          
          <button
            @click="removeKeyframe(index)"
            class="p-1 text-red-600 hover:text-red-800 transition-colors"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>
      </div>
    </div>

    <!-- Advanced Toggle -->
    <div class="pt-4 border-t border-gray-200">
      <button
        @click="showAdvanced = !showAdvanced"
        class="flex items-center text-sm text-blue-600 hover:text-blue-800 transition-colors"
      >
        <svg
          class="w-4 h-4 mr-2 transition-transform"
          :class="{ 'rotate-90': showAdvanced }"
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
        >
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
        </svg>
        {{ showAdvanced ? 'Hide' : 'Show' }} Advanced Options
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue'

interface AnimationSettings {
  duration: number
  delay: number
  easing: string
  repeat: boolean
  repeatCount: number
}

interface AnimationPreset {
  name: string
  description: string
  type: string
  settings: Partial<AnimationSettings>
}

interface Keyframe {
  time: number
  property: string
  value: string
}

interface Props {
  selectedLayers: any[]
}

const props = defineProps<Props>()

const emit = defineEmits<{
  updateAnimation: [layerId: string, animation: any]
  previewAnimation: [layerId: string]
  stopAnimation: [layerId: string]
}>()

// Animation state
const isAnimationEnabled = ref(false)
const selectedAnimationType = ref('')
const isPreviewPlaying = ref(false)
const animationProgress = ref(0)
const showAdvanced = ref(false)

// Animation settings
const animationSettings = ref<AnimationSettings>({
  duration: 1,
  delay: 0,
  easing: 'ease',
  repeat: false,
  repeatCount: 0
})

// Keyframes for advanced animation
const keyframes = ref<Keyframe[]>([])

// Animation presets
const animationPresets: AnimationPreset[] = [
  {
    name: 'Quick Fade',
    description: 'Fast fade in effect',
    type: 'fadeIn',
    settings: { duration: 0.5, delay: 0, easing: 'ease' }
  },
  {
    name: 'Smooth Slide',
    description: 'Elegant slide from left',
    type: 'slideInFromLeft',
    settings: { duration: 0.8, delay: 0.2, easing: 'ease-out' }
  },
  {
    name: 'Bounce Entry',
    description: 'Playful bounce effect',
    type: 'bounceIn',
    settings: { duration: 1, delay: 0, easing: 'cubic-bezier(0.68, -0.55, 0.265, 1.55)' }
  },
  {
    name: 'Scale Pop',
    description: 'Pop-in scale animation',
    type: 'scaleIn',
    settings: { duration: 0.6, delay: 0, easing: 'ease-out' }
  },
  {
    name: 'Attention Pulse',
    description: 'Repeated pulse effect',
    type: 'pulse',
    settings: { duration: 1, delay: 0, easing: 'ease-in-out', repeat: true, repeatCount: 0 }
  },
  {
    name: 'Rotate In',
    description: 'Spinning entrance',
    type: 'rotateIn',
    settings: { duration: 1.2, delay: 0, easing: 'ease' }
  }
]

// Computed properties
const totalAnimationTime = computed(() => {
  return animationSettings.value.duration + animationSettings.value.delay
})

const hasSelectedLayers = computed(() => {
  return props.selectedLayers && props.selectedLayers.length > 0
})

// Methods
const toggleAnimation = () => {
  isAnimationEnabled.value = !isAnimationEnabled.value
  
  if (!isAnimationEnabled.value) {
    // Clear animation from selected layers
    props.selectedLayers.forEach(layer => {
      emit('stopAnimation', layer.id)
    })
  }
}

const applyAnimationType = () => {
  if (!selectedAnimationType.value || !hasSelectedLayers.value) return
  
  updateAnimation()
}

const updateAnimationSettings = () => {
  if (!isAnimationEnabled.value || !hasSelectedLayers.value) return
  
  updateAnimation()
}

const updateAnimation = () => {
  const animationData = {
    type: selectedAnimationType.value,
    settings: { ...animationSettings.value },
    keyframes: keyframes.value
  }
  
  props.selectedLayers.forEach(layer => {
    emit('updateAnimation', layer.id, animationData)
  })
}

const previewAnimation = () => {
  if (!hasSelectedLayers.value) return
  
  if (isPreviewPlaying.value) {
    // Stop preview
    isPreviewPlaying.value = false
    animationProgress.value = 0
    props.selectedLayers.forEach(layer => {
      emit('stopAnimation', layer.id)
    })
  } else {
    // Start preview
    isPreviewPlaying.value = true
    animationProgress.value = 0
    
    props.selectedLayers.forEach(layer => {
      emit('previewAnimation', layer.id)
    })
    
    // Animate progress bar
    setTimeout(() => {
      animationProgress.value = 100
    }, 50)
    
    // Reset after animation completes
    setTimeout(() => {
      isPreviewPlaying.value = false
      animationProgress.value = 0
    }, (totalAnimationTime.value * 1000) + 100)
  }
}

const resetAnimation = () => {
  isPreviewPlaying.value = false
  animationProgress.value = 0
  
  props.selectedLayers.forEach(layer => {
    emit('stopAnimation', layer.id)
  })
}

const applyPreset = (preset: AnimationPreset) => {
  selectedAnimationType.value = preset.type
  
  // Apply preset settings
  Object.assign(animationSettings.value, preset.settings)
  
  isAnimationEnabled.value = true
  updateAnimation()
}

const addKeyframe = () => {
  keyframes.value.push({
    time: 0,
    property: 'opacity',
    value: '1'
  })
}

const removeKeyframe = (index: number) => {
  keyframes.value.splice(index, 1)
  updateAnimation()
}

// Watch for layer selection changes
watch(() => props.selectedLayers, (newLayers) => {
  if (!newLayers || newLayers.length === 0) {
    isAnimationEnabled.value = false
    selectedAnimationType.value = ''
    return
  }
  
  // Load animation settings from first selected layer
  const firstLayer = newLayers[0]
  if (firstLayer.animation) {
    isAnimationEnabled.value = true
    selectedAnimationType.value = firstLayer.animation.type || ''
    if (firstLayer.animation.settings) {
      Object.assign(animationSettings.value, firstLayer.animation.settings)
    }
    if (firstLayer.animation.keyframes) {
      keyframes.value = [...firstLayer.animation.keyframes]
    }
  } else {
    isAnimationEnabled.value = false
    selectedAnimationType.value = ''
  }
}, { immediate: true })
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

/* Animation for progress bar */
.rotate-90 {
  transform: rotate(90deg);
}
</style>

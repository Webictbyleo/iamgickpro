<template>
  <div>
    <!-- Expandable Header Button -->
    <button
      @click="isExpanded = !isExpanded"
      :class="[
        'w-full flex items-center justify-between p-4 text-left border rounded-lg transition-all duration-200',
        isExpanded 
          ? 'border-indigo-500 bg-indigo-50' 
          : 'border-gray-200 hover:border-gray-300 hover:bg-gray-50'
      ]"
    >
      <div class="flex items-center space-x-3">
        <div :class="[
          'w-8 h-8 rounded-lg flex items-center justify-center transition-colors',
          isExpanded ? 'bg-indigo-500' : 'bg-gray-500'
        ]">
          <Square3Stack3DIcon class="w-4 h-4 text-white" />
        </div>
        <div>
          <h4 class="text-sm font-medium text-gray-900">Shadow Effects</h4>
          <p class="text-xs text-gray-600">Add depth with customizable shadows</p>
        </div>
      </div>
      <ChevronDownIcon 
        :class="[
          'w-4 h-4 text-gray-500 transition-transform duration-200',
          isExpanded ? 'rotate-180' : ''
        ]"
      />
    </button>

    <!-- Expanded Content -->
    <Transition
      enter-active-class="transition-all duration-300 ease-out"
      enter-from-class="max-h-0 opacity-0"
      enter-to-class="max-h-screen opacity-100"
      leave-active-class="transition-all duration-300 ease-in"
      leave-from-class="max-h-screen opacity-100"
      leave-to-class="max-h-0 opacity-0"
    >
      <div v-if="isExpanded" class="mt-4 space-y-4 overflow-hidden">
        <!-- Shadow Presets -->
        <div class="mb-6">
          <h5 class="text-xs font-medium text-gray-700 mb-3">Shadow Presets</h5>
          <div class="grid grid-cols-2 gap-2">
            <button 
              v-for="preset in shadowPresets" 
              :key="preset.name"
              @click="applyShadowPreset(preset)"
              :class="[
                'p-3 text-left border rounded-lg transition-all duration-200 hover:shadow-md',
                currentPreset === preset.name 
                  ? 'border-indigo-500 bg-indigo-50' 
                  : 'border-gray-200 hover:border-gray-300'
              ]"
            >
              <!-- Preview -->
              <div class="mb-2 flex justify-center">
                <div 
                  class="w-8 h-6 bg-gray-300 rounded"
                  :style="{ boxShadow: preset.enabled ? preset.cssValue : 'none' }"
                ></div>
              </div>
              <p class="text-xs font-medium text-gray-900">{{ preset.name }}</p>
              <p class="text-xs text-gray-500">{{ preset.description }}</p>
            </button>
          </div>
        </div>

        <!-- Shadow Enable Toggle -->
        <div class="flex items-center space-x-2">
          <input
            type="checkbox"
            :checked="shadowEnabled"
            @change="updateProperty('shadowEnabled', ($event.target as HTMLInputElement)?.checked)"
            class="w-4 h-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500"
          />
          <label class="text-xs font-medium text-gray-700">Enable Shadow</label>
        </div>

        <!-- Custom Shadow Controls -->
        <template v-if="shadowEnabled">
          <div class="space-y-4 pt-4 border-t border-gray-200">
            <!-- Shadow Offset X -->
            <div>
              <label class="block text-xs font-medium text-gray-700 mb-2">
                Offset X: {{ shadowOffsetX }}px
              </label>
              <PropertySlider
                :value="shadowOffsetX"
                @update="updateProperty('shadowOffsetX', $event)"
                :min="-100"
                :max="100"
                :step="1"
                class="w-full"
              />
            </div>

            <!-- Shadow Offset Y -->
            <div>
              <label class="block text-xs font-medium text-gray-700 mb-2">
                Offset Y: {{ shadowOffsetY }}px
              </label>
              <PropertySlider
                :value="shadowOffsetY"
                @update="updateProperty('shadowOffsetY', $event)"
                :min="-100"
                :max="100"
                :step="1"
                class="w-full"
              />
            </div>

            <!-- Shadow Blur -->
            <div>
              <label class="block text-xs font-medium text-gray-700 mb-2">
                Shadow Blur: {{ shadowBlur }}px
              </label>
              <PropertySlider
                :value="shadowBlur"
                @update="updateProperty('shadowBlur', $event)"
                :min="0"
                :max="50"
                :step="0.5"
                class="w-full"
              />
            </div>

            <!-- Shadow Color -->
            <div>
              <label class="block text-xs font-medium text-gray-700 mb-2">
                Shadow Color
              </label>
              <input
                type="color"
                :value="shadowColor"
                @input="updateProperty('shadowColor', ($event.target as HTMLInputElement)?.value)"
                class="w-full h-10 rounded-lg border border-gray-300 cursor-pointer"
              />
            </div>

            <!-- Shadow Opacity -->
            <div>
              <label class="block text-xs font-medium text-gray-700 mb-2">
                Shadow Opacity: {{ Math.round(shadowOpacity * 100) }}%
              </label>
              <PropertySlider
                :value="shadowOpacity"
                @update="updateProperty('shadowOpacity', $event)"
                :min="0"
                :max="1"
                :step="0.01"
                class="w-full"
              />
            </div>
          </div>
        </template>

        <!-- Reset Button -->
        <div class="pt-4 border-t border-gray-200">
          <button
            @click="resetShadow"
            class="w-full flex items-center justify-center px-3 py-2 text-xs font-medium text-red-600 bg-red-50 border border-red-200 rounded-lg hover:bg-red-100 hover:border-red-300 transition-colors"
          >
            <ArrowPathIcon class="w-3 h-3 mr-1" />
            Reset Shadow
          </button>
        </div>
      </div>
    </Transition>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { Square3Stack3DIcon, ChevronDownIcon, ArrowPathIcon } from '@heroicons/vue/24/outline'
import PropertySlider from '@/components/editor/Properties/PropertySlider.vue'

interface ShadowPreset {
  name: string
  description: string
  enabled: boolean
  offsetX: number
  offsetY: number
  blur: number
  color: string
  opacity: number
  cssValue: string
}

interface Props {
  shadowEnabled: boolean
  shadowOffsetX: number
  shadowOffsetY: number
  shadowBlur: number
  shadowColor: string
  shadowOpacity: number
}

const props = defineProps<Props>()

const emit = defineEmits<{
  updateProperty: [key: string, value: any]
  resetShadow: []
}>()

const isExpanded = ref(false)
const currentPreset = ref<string>('None')

// Shadow presets similar to Canva
const shadowPresets: ShadowPreset[] = [
  {
    name: 'None',
    description: 'No shadow',
    enabled: false,
    offsetX: 0,
    offsetY: 0,
    blur: 0,
    color: '#000000',
    opacity: 0,
    cssValue: 'none'
  },
  {
    name: 'Subtle',
    description: 'Light shadow',
    enabled: true,
    offsetX: 2,
    offsetY: 2,
    blur: 4,
    color: '#000000',
    opacity: 0.1,
    cssValue: '2px 2px 4px rgba(0, 0, 0, 0.1)'
  },
  {
    name: 'Soft',
    description: 'Gentle depth',
    enabled: true,
    offsetX: 4,
    offsetY: 4,
    blur: 8,
    color: '#000000',
    opacity: 0.15,
    cssValue: '4px 4px 8px rgba(0, 0, 0, 0.15)'
  },
  {
    name: 'Medium',
    description: 'Balanced shadow',
    enabled: true,
    offsetX: 6,
    offsetY: 6,
    blur: 12,
    color: '#000000',
    opacity: 0.2,
    cssValue: '6px 6px 12px rgba(0, 0, 0, 0.2)'
  },
  {
    name: 'Strong',
    description: 'Bold shadow',
    enabled: true,
    offsetX: 8,
    offsetY: 8,
    blur: 16,
    color: '#000000',
    opacity: 0.25,
    cssValue: '8px 8px 16px rgba(0, 0, 0, 0.25)'
  },
  {
    name: 'Dramatic',
    description: 'High contrast',
    enabled: true,
    offsetX: 12,
    offsetY: 12,
    blur: 24,
    color: '#000000',
    opacity: 0.3,
    cssValue: '12px 12px 24px rgba(0, 0, 0, 0.3)'
  }
]

const updateProperty = (key: string, value: any) => {
  emit('updateProperty', key, value)
}

const applyShadowPreset = (preset: ShadowPreset) => {
  currentPreset.value = preset.name
  
  emit('updateProperty', 'shadowEnabled', preset.enabled)
  if (preset.enabled) {
    emit('updateProperty', 'shadowOffsetX', preset.offsetX)
    emit('updateProperty', 'shadowOffsetY', preset.offsetY)
    emit('updateProperty', 'shadowBlur', preset.blur)
    emit('updateProperty', 'shadowColor', preset.color)
    emit('updateProperty', 'shadowOpacity', preset.opacity)
  }
}

const resetShadow = () => {
  currentPreset.value = 'None'
  emit('resetShadow')
}
</script>

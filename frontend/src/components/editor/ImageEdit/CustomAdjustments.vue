<template>
  <div>
    <!-- Expandable Header Button -->
    <button
      @click="isExpanded = !isExpanded"
      :class="[
        'w-full flex items-center justify-between p-4 text-left border rounded-lg transition-all duration-200',
        isExpanded 
          ? 'border-blue-500 bg-blue-50' 
          : 'border-gray-200 hover:border-gray-300 hover:bg-gray-50'
      ]"
    >
      <div class="flex items-center space-x-3">
        <div :class="[
          'w-8 h-8 rounded-lg flex items-center justify-center transition-colors',
          isExpanded ? 'bg-blue-500' : 'bg-gray-500'
        ]">
          <Cog6ToothIcon class="w-4 h-4 text-white" />
        </div>
        <div>
          <h4 class="text-sm font-medium text-gray-900">Custom Adjustments</h4>
          <p class="text-xs text-gray-600">Fine-tune brightness, contrast, and more</p>
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
        <!-- Quick Adjustments Grid -->
        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="block text-xs font-medium text-gray-700 mb-2">
              Brightness
            </label>
            <PropertySlider
              :value="brightness"
              @update="updateProperty('brightness', $event)"
              :min="0"
              :max="3"
              :step="0.1"
              class="w-full"
            />
          </div>
          
          <div>
            <label class="block text-xs font-medium text-gray-700 mb-2">
              Contrast
            </label>
            <PropertySlider
              :value="contrast"
              @update="updateProperty('contrast', $event)"
              :min="0"
              :max="3"
              :step="0.1"
              class="w-full"
            />
          </div>
          
          <div>
            <label class="block text-xs font-medium text-gray-700 mb-2">
              Saturation
            </label>
            <PropertySlider
              :value="saturation"
              @update="updateProperty('saturation', $event)"
              :min="0"
              :max="3"
              :step="0.1"
              class="w-full"
            />
          </div>
          
          <div>
            <label class="block text-xs font-medium text-gray-700 mb-2">
              Warmth
            </label>
            <PropertySlider
              :value="hue"
              @update="updateProperty('hue', $event)"
              :min="-50"
              :max="50"
              :step="1"
              class="w-full"
            />
          </div>
        </div>

        <!-- Additional Filters -->
        <div class="space-y-4 pt-4 border-t border-gray-200">
          <!-- Blur -->
          <div>
            <label class="block text-xs font-medium text-gray-700 mb-2">
              Blur: {{ blur }}px
            </label>
            <PropertySlider
              :value="blur"
              @update="updateProperty('blur', $event)"
              :min="0"
              :max="50"
              :step="0.5"
              class="w-full"
            />
          </div>

          <!-- Sepia -->
          <div>
            <label class="block text-xs font-medium text-gray-700 mb-2">
              Sepia: {{ Math.round(sepia * 100) }}%
            </label>
            <PropertySlider
              :value="sepia"
              @update="updateProperty('sepia', $event)"
              :min="0"
              :max="1"
              :step="0.01"
              class="w-full"
            />
          </div>

          <!-- Grayscale -->
          <div>
            <label class="block text-xs font-medium text-gray-700 mb-2">
              Grayscale: {{ Math.round(grayscale * 100) }}%
            </label>
            <PropertySlider
              :value="grayscale"
              @update="updateProperty('grayscale', $event)"
              :min="0"
              :max="1"
              :step="0.01"
              class="w-full"
            />
          </div>

          <!-- Invert -->
          <div>
            <label class="block text-xs font-medium text-gray-700 mb-2">
              Invert: {{ Math.round(invert * 100) }}%
            </label>
            <PropertySlider
              :value="invert"
              @update="updateProperty('invert', $event)"
              :min="0"
              :max="1"
              :step="0.01"
              class="w-full"
            />
          </div>
        </div>

        <!-- Reset Button -->
        <div class="pt-4 border-t border-gray-200">
          <button
            @click="resetFilters"
            class="w-full flex items-center justify-center px-3 py-2 text-xs font-medium text-red-600 bg-red-50 border border-red-200 rounded-lg hover:bg-red-100 hover:border-red-300 transition-colors"
          >
            <ArrowPathIcon class="w-3 h-3 mr-1" />
            Reset Filters
          </button>
        </div>
      </div>
    </Transition>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { Cog6ToothIcon, ChevronDownIcon, ArrowPathIcon } from '@heroicons/vue/24/outline'
import PropertySlider from '@/components/editor/Properties/PropertySlider.vue'

interface Props {
  brightness: number
  contrast: number
  saturation: number
  hue: number
  blur: number
  sepia: number
  grayscale: number
  invert: number
}

const props = defineProps<Props>()

const emit = defineEmits<{
  updateProperty: [key: string, value: number]
  resetFilters: []
}>()

const isExpanded = ref(false)

const updateProperty = (key: string, value: number) => {
  emit('updateProperty', key, value)
}

const resetFilters = () => {
  emit('resetFilters')
}
</script>

<template>
  <div>
    <!-- Expandable Header Button -->
    <button
      @click="isExpanded = !isExpanded"
      :class="[
        'w-full flex items-center justify-between p-4 text-left border rounded-lg transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-primary-500 dark:focus:ring-primary-400 focus:ring-offset-2 dark:focus:ring-offset-secondary-800',
        isExpanded 
          ? 'border-primary-500 dark:border-primary-400 bg-primary-50 dark:bg-secondary-700' 
          : 'border-secondary-200 dark:border-secondary-700 hover:border-secondary-300 dark:hover:border-secondary-600 hover:bg-secondary-50 dark:hover:bg-secondary-800'
      ]"
    >
      <div class="flex items-center space-x-3">
        <div :class="[
          'w-8 h-8 rounded-lg flex items-center justify-center transition-colors',
          isExpanded ? 'bg-primary-500 dark:bg-primary-600' : 'bg-secondary-500 dark:bg-secondary-600'
        ]">
          <Cog6ToothIcon class="w-4 h-4 text-white" />
        </div>
        <div>
          <h4 class="text-sm font-medium text-secondary-900 dark:text-secondary-100">Custom Adjustments</h4>
          <p class="text-xs text-secondary-600 dark:text-secondary-400">Fine-tune brightness, contrast, and more</p>
        </div>
      </div>
      <ChevronDownIcon 
        :class="[
          'w-4 h-4 text-secondary-500 dark:text-secondary-400 transition-transform duration-200',
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
            <label class="block text-xs font-medium text-secondary-700 dark:text-secondary-300 mb-2">
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
            <label class="block text-xs font-medium text-secondary-700 dark:text-secondary-300 mb-2">
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
            <label class="block text-xs font-medium text-secondary-700 dark:text-secondary-300 mb-2">
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
            <label class="block text-xs font-medium text-secondary-700 dark:text-secondary-300 mb-2">
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
        <div class="space-y-4 pt-4 border-t border-secondary-200 dark:border-secondary-700">
          <!-- Blur -->
          <div>
            <label class="block text-xs font-medium text-secondary-700 dark:text-secondary-300 mb-2">
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
            <label class="block text-xs font-medium text-secondary-700 dark:text-secondary-300 mb-2">
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
            <label class="block text-xs font-medium text-secondary-700 dark:text-secondary-300 mb-2">
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
            <label class="block text-xs font-medium text-secondary-700 dark:text-secondary-300 mb-2">
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
        <div class="pt-4 border-t border-secondary-200 dark:border-secondary-700">
          <button
            @click="resetFilters"
            class="w-full flex items-center justify-center px-3 py-2 text-xs font-medium text-danger-600 dark:text-danger-400 bg-danger-50 dark:bg-danger-900/20 border border-danger-200 dark:border-danger-800 rounded-lg hover:bg-danger-100 dark:hover:bg-danger-900/30 hover:border-danger-300 dark:hover:border-danger-700 transition-colors"
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

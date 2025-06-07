<template>
  <div>
    <h4 class="text-sm font-medium text-gray-900 mb-3 flex items-center">
      <Squares2X2Icon class="w-4 h-4 mr-2 text-gray-500" />
      Border Radius
    </h4>
    
    <div class="space-y-4">
      <!-- Border Radius Presets -->
      <div class="grid grid-cols-4 gap-2 mb-4">
        <button 
          v-for="preset in radiusPresets" 
          :key="preset.name"
          @click="applyPreset(preset.value)"
          :class="[
            'p-2 flex flex-col items-center border rounded-lg transition-all duration-200 hover:shadow-md',
            borderRadius === preset.value 
              ? 'border-blue-500 bg-blue-50' 
              : 'border-gray-200 hover:border-gray-300'
          ]"
        >
          <!-- Preview -->
          <div 
            class="w-6 h-6 bg-gray-300 mb-1"
            :style="{ borderRadius: `${preset.value}px` }"
          ></div>
          <span class="text-xs font-medium text-gray-700">{{ preset.name }}</span>
        </button>
      </div>

      <!-- Custom Border Radius -->
      <div>
        <label class="block text-xs font-medium text-gray-700 mb-2">
          Custom: {{ borderRadius }}px
        </label>
        <PropertySlider
          :value="borderRadius"
          @update="updateBorderRadius"
          :min="0"
          :max="100"
          :step="1"
          class="w-full"
        />
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { Squares2X2Icon } from '@heroicons/vue/24/outline'
import PropertySlider from '@/components/editor/Properties/PropertySlider.vue'

interface RadiusPreset {
  name: string
  value: number
}

interface Props {
  borderRadius: number
}

const props = defineProps<Props>()

const emit = defineEmits<{
  updateBorderRadius: [value: number]
}>()

const radiusPresets: RadiusPreset[] = [
  { name: 'None', value: 0 },
  { name: 'Small', value: 4 },
  { name: 'Medium', value: 8 },
  { name: 'Large', value: 16 },
  { name: 'XL', value: 24 },
  { name: 'Round', value: 50 }
]

const updateBorderRadius = (value: number) => {
  emit('updateBorderRadius', value)
}

const applyPreset = (value: number) => {
  updateBorderRadius(value)
}
</script>

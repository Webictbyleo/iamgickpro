<template>
  <div class="flex items-center space-x-4">
    <!-- Shape Type -->
    <div class="flex items-center space-x-2">
      <label class="text-sm font-medium text-gray-700 min-w-max">Shape:</label>
      <PropertyDropdown
        :value="shapeType"
        :options="shapeOptions"
        @update="$emit('update', { shapeType: String($event) })"
        class="w-32"
        :show-dropdown-icon="false"
      />
    </div>

    <!-- Fill Color -->
    <div class="flex items-center space-x-2">
      <label class="text-sm font-medium text-gray-700">Fill:</label>
      <PropertyColorPicker
        :value="fill"
        @update="$emit('update', { fill: $event })"
        allow-gradient
      />
    </div>

    <!-- Stroke Color -->
    <div class="flex items-center space-x-2">
      <label class="text-sm font-medium text-gray-700">Stroke:</label>
      <PropertyColorPicker
        :value="stroke"
        @update="$emit('update', { stroke: $event })"
      />
    </div>

    <!-- Stroke Width -->
    <div class="flex items-center space-x-2">
      <label class="text-sm font-medium text-gray-700">Width:</label>
      <PropertyInput
        :value="strokeWidth"
        type="number"
        @update="$emit('update', { strokeWidth: Number($event) })"
        class="w-16"
        :min="0"
        :max="20"
      />
    </div>

    <!-- Border Radius (for rectangles) -->
    <div v-if="shapeType === 'rectangle'" class="flex items-center space-x-2">
      <label class="text-sm font-medium text-gray-700">Radius:</label>
      <PropertyInput
        :value="borderRadius"
        type="number"
        @update="$emit('update', { borderRadius: Number($event) })"
        class="w-16"
        :min="0"
        :max="100"
      />
    </div>

    <!-- Shadow Toggle -->
    <div class="flex items-center space-x-2">
      <PropertyToggle
        :active="hasShadow"
        @update="$emit('update', { hasShadow: $event })"
        tooltip="Add Shadow"
      >
        <ShadowIcon class="w-4 h-4" />
      </PropertyToggle>
    </div>
  </div>
</template>

<script setup lang="ts">
import PropertyDropdown from '@/components/editor/Properties/PropertyDropdown.vue'
import PropertyInput from '@/components/editor/Properties/PropertyInput.vue'
import PropertyToggle from '@/components/editor/Properties/PropertyToggle.vue'
import PropertyColorPicker from '@/components/editor/Properties/PropertyColorPicker.vue'
import ShadowIcon from '@/components/icons/ShadowIcon.vue'

interface Props {
  shapeType?: string
  fill?: string
  stroke?: string
  strokeWidth?: number
  borderRadius?: number
  hasShadow?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  shapeType: 'rectangle',
  fill: '#3498db',
  stroke: '#2980b9',
  strokeWidth: 2,
  borderRadius: 0,
  hasShadow: false
})

const emit = defineEmits<{
  update: [properties: Partial<Props>]
}>()

const shapeOptions = [
  { value: 'rectangle', label: 'Rectangle' },
  { value: 'circle', label: 'Circle' },
  { value: 'triangle', label: 'Triangle' },
  { value: 'line', label: 'Line' },
  { value: 'arrow', label: 'Arrow' },
  { value: 'star', label: 'Star' },
  { value: 'polygon', label: 'Polygon' },
  { value: 'heart', label: 'Heart' }
]
</script>

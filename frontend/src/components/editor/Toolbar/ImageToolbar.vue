<template>
  <div class="flex items-center space-x-4">
    <!-- Opacity -->
    <div class="flex items-center space-x-2">
      <label class="text-sm font-medium text-gray-700">Opacity:</label>
      <IconDropdown
        :icon="TransparencyIcon"
        tooltip="Adjust opacity"
        placement="bottom-start"
        width="w-64"
      >
        <template #default="{ close }">
          <div class="p-4">
            <div class="flex items-center justify-between mb-3">
              <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Opacity</span>
              <span class="text-sm text-gray-500 dark:text-gray-400">{{ Math.round((opacity || 1) * 100) }}%</span>
            </div>
            <PropertySlider
              :value="opacity || 1"
              @update="$emit('update', { opacity: Number($event) })"
              :min="0"
              :max="1"
              :step="0.01"
              class="w-full"
            />
          </div>
        </template>
      </IconDropdown>
    </div>

    <!-- Position -->
    <div class="flex items-center space-x-2">
      <label class="text-sm font-medium text-gray-700">Position:</label>
      <PropertyDropdown
        :value="objectPosition"
        :options="positionOptions"
        @update="$emit('update', { objectPosition: String($event) })"
        class="w-24"
        :show-dropdown-icon="false"
      />
    </div>

    <!-- Flip Controls -->
    <div class="flex items-center space-x-2">
      <label class="text-sm font-medium text-gray-700">Flip:</label>
      <PropertyDropdown
        :value="getFlipValue()"
        :options="flipOptions"
        @update="handleFlipUpdate($event)"
        class="w-28"
        :show-dropdown-icon="false"
      />
    </div>

    <!-- Edit Button for Advanced Settings -->
    <div class="flex items-center space-x-1 ml-3 pl-3 border-l border-gray-200">
      <ModernButton
        variant="ghost"
        size="xs"
        @click="$emit('edit-image')"
        tooltip="Advanced Image Settings"
        class="hover:bg-blue-50 dark:hover:bg-blue-900/20 text-gray-600 hover:text-blue-600 dark:text-gray-300 dark:hover:text-blue-400"
      >
        <AdjustmentsHorizontalIcon class="w-3.5 h-3.5" />
      </ModernButton>
    </div>
  </div>
</template>

<script setup lang="ts">
import PropertySlider from '@/components/editor/Properties/PropertySlider.vue'
import PropertyDropdown from '@/components/editor/Properties/PropertyDropdown.vue'
import IconDropdown from '@/components/ui/IconDropdown.vue'
import TransparencyIcon from '@/components/icons/TransparencyIcon.vue'
import ModernButton from '@/components/common/ModernButton.vue'
import { AdjustmentsHorizontalIcon } from '@heroicons/vue/24/outline'

interface Props {
  alt?: string
  opacity?: number
  borderRadius?: number
  objectPosition?: string
  preserveAspectRatio?: boolean
  quality?: number
  // Filter properties
  brightness?: number
  contrast?: number
  saturation?: number
  blur?: number
  hue?: number
  sepia?: number
  grayscale?: number
  invert?: number
  // Transform properties
  flipX?: boolean
  flipY?: boolean
  // Shadow properties (simplified for toolbar)
  shadowEnabled?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  alt: '',
  opacity: 1,
  borderRadius: 0,
  objectPosition: 'center',
  preserveAspectRatio: true,
  quality: 100,
  // Filter properties defaults
  brightness: 1,
  contrast: 1,
  saturation: 1,
  blur: 0,
  hue: 0,
  sepia: 0,
  grayscale: 0,
  invert: 0,
  // Transform properties defaults
  flipX: false,
  flipY: false,
  // Shadow properties defaults
  shadowEnabled: false
})

const emit = defineEmits<{
  update: [properties: Partial<Props>]
  'edit-image': []
}>()

const positionOptions = [
  { value: 'center', label: 'Center' },
  { value: 'top', label: 'Top' },
  { value: 'bottom', label: 'Bottom' },
  { value: 'left', label: 'Left' },
  { value: 'right', label: 'Right' },
  { value: 'top left', label: 'Top Left' },
  { value: 'top right', label: 'Top Right' },
  { value: 'bottom left', label: 'Bottom Left' },
  { value: 'bottom right', label: 'Bottom Right' }
]

const flipOptions = [
  { value: 'none', label: 'None' },
  { value: 'horizontal', label: 'Horizontal' },
  { value: 'vertical', label: 'Vertical' },
  { value: 'both', label: 'Both' }
]

const getFlipValue = () => {
  if (props.flipX && props.flipY) return 'both'
  if (props.flipX) return 'horizontal'
  if (props.flipY) return 'vertical'
  return 'none'
}

const handleFlipUpdate = (value: string | number) => {
  const flipValue = String(value)
  const updates: Partial<Props> = {}
  
  switch (flipValue) {
    case 'none':
      updates.flipX = false
      updates.flipY = false
      break
    case 'horizontal':
      updates.flipX = true
      updates.flipY = false
      break
    case 'vertical':
      updates.flipX = false
      updates.flipY = true
      break
    case 'both':
      updates.flipX = true
      updates.flipY = true
      break
  }
  
  emit('update', updates)
}
</script>

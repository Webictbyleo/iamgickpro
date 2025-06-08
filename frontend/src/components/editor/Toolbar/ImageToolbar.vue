<template>
  <div class="flex items-center space-x-4">
    <!-- Transparency -->
    <div class="flex items-center space-x-2">
      <IconDropdown
        :icon="TransparencyIcon"
        text="Transparency"
        tooltip="Adjust transparency"
        placement="bottom-start"
        width="w-64"
      >
        <template #default="{ close }">
          <div class="p-4">
            <div class="flex items-center justify-between mb-3">
              <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Transparency</span>
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

    <!-- Flip Controls -->
    <div class="flex items-center space-x-2">
      
      <IconDropdown
        :icon="ArrowsRightLeftIcon"
        text="Flip"
        tooltip="Flip controls"
        placement="bottom-start"
        width="w-48"
      >
        <template #default="{ close }">
          <div class="p-4">
            <div class="flex items-center justify-between mb-3">
              <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Flip Controls</span>
            </div>
            <div class="flex flex-col space-y-2">
              <ModernButton
                variant="ghost"
                size="sm"
                :class="[
                  'px-3 py-2 flex items-center !justify-start space-x-2 transition-colors rounded-md',
                  flipX 
                    ? 'bg-blue-100 text-blue-600 hover:bg-blue-200 dark:bg-blue-900/30 dark:text-blue-400 dark:hover:bg-blue-900/50' 
                    : 'text-gray-600 hover:text-gray-800 hover:bg-gray-100 dark:text-gray-300 dark:hover:text-gray-100 dark:hover:bg-gray-700'
                ]"
                @click="$emit('update', { flipX: !flipX })"
                tooltip="Flip Horizontally"
              >
                <ArrowsRightLeftIcon class="w-4 h-4" />
                <span class="text-sm font-medium">Horizontal</span>
              </ModernButton>
              
              <ModernButton
                variant="ghost"
                size="sm"
                :class="[
                  'px-3 py-2 flex items-center !justify-start space-x-2 transition-colors rounded-md',
                  flipY 
                    ? 'bg-blue-100 text-blue-600 hover:bg-blue-200 dark:bg-blue-900/30 dark:text-blue-400 dark:hover:bg-blue-900/50' 
                    : 'text-gray-600 hover:text-gray-800 hover:bg-gray-100 dark:text-gray-300 dark:hover:text-gray-100 dark:hover:bg-gray-700'
                ]"
                @click="$emit('update', { flipY: !flipY })"
                tooltip="Flip Vertically"
              >
                <ArrowsUpDownIcon class="w-4 h-4" />
                <span class="text-sm font-medium">Vertical</span>
              </ModernButton>
            </div>
            <div class="mt-2 text-xs text-gray-500 dark:text-gray-400">
              Click buttons to toggle horizontal or vertical flip
            </div>
          </div>
        </template>
      </IconDropdown>
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
import IconDropdown from '@/components/ui/IconDropdown.vue'
import TransparencyIcon from '@/components/icons/TransparencyIcon.vue'
import ModernButton from '@/components/common/ModernButton.vue'
import { AdjustmentsHorizontalIcon, ArrowsRightLeftIcon, ArrowsUpDownIcon } from '@heroicons/vue/24/outline'

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
</script>

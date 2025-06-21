<template>
  <div class="flex items-center space-x-4">
    <!-- SVG Source Display -->
    <div class="flex items-center space-x-2">
      <label class="text-sm font-medium text-gray-700 dark:text-gray-300 min-w-max">SVG:</label>
      <span class="px-3 py-1 text-sm bg-gray-100 dark:bg-gray-700 rounded-md border border-gray-200 dark:border-gray-600 min-w-[120px] text-gray-800 dark:text-gray-200 font-medium truncate">
        {{ getSvgLabel(src) }}
      </span>
    </div>

    <!-- Simple Path Styling -->
    <IconDropdown
      :icon="PaintBrushIcon"
      tooltip="Style SVG paths"
      placement="bottom-end"
      text="Style Paths"
      width="w-72"
      :showChevron="false"
      buttonClass="hover:bg-blue-50 dark:hover:bg-blue-900/20 text-gray-600 hover:text-blue-600 dark:text-gray-300 dark:hover:text-blue-400"
      iconClass="w-3.5 h-3.5"
    >
      <template #default="{ close }">
        <div class="p-4 space-y-4">
          <div class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
            Path Styling
            <div class="text-xs text-gray-500 dark:text-gray-400 font-normal mt-1">
              All SVG elements are rendered as paths for optimal performance
            </div>
          </div>
          
          <!-- Global Fill Color -->
          <div class="space-y-3">
            <div class="flex items-center justify-between space-x-3">
              <span class="text-sm text-gray-600 dark:text-gray-400">Fill Color:</span>
              <PropertyColorPicker
                :value="globalFillColor"
                @update="updateGlobalFill"
              />
            </div>
            
            <!-- Global Stroke -->
            <div class="flex items-center justify-between space-x-3">
              <span class="text-sm text-gray-600 dark:text-gray-400">Stroke Color:</span>
              <PropertyColorPicker
                :value="globalStrokeColor"
                @update="updateGlobalStroke"
              />
            </div>
            
            <!-- Global Stroke Width -->
            <div class="flex items-center justify-between space-x-3">
              <span class="text-sm text-gray-600 dark:text-gray-400">Stroke Width:</span>
              <PropertyNumberInput
                :value="globalStrokeWidth"
                @update:value="updateGlobalStrokeWidth"
                @change="updateGlobalStrokeWidth"
                :min="0"
                :max="20"
                :step="0.5"
                unit="px"
                input-class="w-16"
                placeholder="1"
              />
            </div>
          </div>

          <!-- Path Count Info -->
          <div v-if="pathCount > 0" class="text-xs text-gray-500 dark:text-gray-400 pt-2 border-t border-gray-200 dark:border-gray-600">
            Styling {{ pathCount }} path{{ pathCount === 1 ? '' : 's' }}
          </div>

          <!-- Reset Section -->
          <div class="border-t border-gray-200 dark:border-gray-600 pt-4">
            <ModernButton
              variant="outline"
              size="sm"
              @click="resetAllStyles"
              class="w-full"
            >
              Reset to Original
            </ModernButton>
          </div>
        </div>
      </template>
    </IconDropdown>

    <!-- Aspect Ratio -->
    <div class="flex items-center space-x-2">
      <PropertyDropdown
        :value="preserveAspectRatio || 'xMidYMid meet'"
        @update="(value: string | number) => $emit('update', { preserveAspectRatio: value as string })"
        :options="aspectRatioOptions"
        placeholder="Aspect Ratio"
      />
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { PaintBrushIcon } from '@heroicons/vue/24/outline'
import PropertyNumberInput from '@/components/editor/Properties/PropertyNumberInput.vue'
import PropertyDropdown from '@/components/editor/Properties/PropertyDropdown.vue'
import PropertyColorPicker from '@/components/editor/Properties/PropertyColorPicker.vue'
import IconDropdown from '@/components/ui/IconDropdown.vue'
import ModernButton from '@/components/common/ModernButton.vue'

interface Props {
  src?: string
  fillColors?: Record<string, string>
  strokeColors?: Record<string, string>
  strokeWidths?: Record<string, number>
  preserveAspectRatio?: string
  // SVG elements info (optional, used for path count)
  svgElements?: Array<{
    type: string
    id?: string
    className?: string
    originalFill?: string
    originalStroke?: string
    originalStrokeWidth?: number
  }>
}

const props = withDefaults(defineProps<Props>(), {
  src: '',
  fillColors: () => ({}),
  strokeColors: () => ({}),
  strokeWidths: () => ({}),
  preserveAspectRatio: 'xMidYMid meet',
  svgElements: () => ([])
})

const emit = defineEmits<{
  update: [properties: Partial<Props>]
}>()

// Aspect ratio options for SVG scaling
const aspectRatioOptions = [
  { label: 'Fit (Meet)', value: 'xMidYMid meet' },
  { label: 'Fill (Slice)', value: 'xMidYMid slice' },
  { label: 'Stretch', value: 'none' },
]

// Global style values - use 'global' as the key for all paths
const globalFillColor = computed(() => {
  return props.fillColors?.['global'] || '#000000'
})

const globalStrokeColor = computed(() => {
  return props.strokeColors?.['global'] || 'none'
})

const globalStrokeWidth = computed(() => {
  return props.strokeWidths?.['global'] || 1
})

// Count of paths (for display purposes)
const pathCount = computed(() => {
  return props.svgElements?.length || 0
})

const getSvgLabel = (src: string): string => {
  if (!src) return 'No SVG'
  
  // Extract filename from URL
  const urlParts = src.split('/')
  const filename = urlParts[urlParts.length - 1]
  
  if (filename) {
    // Remove file extension and normalize
    return filename.replace(/\.[^/.]+$/, '').replace(/[-_]/g, ' ')
  }
  
  return 'SVG Shape'
}

// Global styling functions - apply to all paths using 'global' key
const updateGlobalFill = (color: string) => {
  console.log('🎨 SVGToolbar: Updating global fill color to:', color)
  const newFillColors = { ...props.fillColors }
  newFillColors['global'] = color
  emit('update', { fillColors: newFillColors })
}

const updateGlobalStroke = (color: string) => {
  console.log('🎨 SVGToolbar: Updating global stroke color to:', color)
  const newStrokeColors = { ...props.strokeColors }
  newStrokeColors['global'] = color
  emit('update', { strokeColors: newStrokeColors })
}

const updateGlobalStrokeWidth = (width: number) => {
  console.log('🎨 SVGToolbar: Updating global stroke width to:', width)
  const newStrokeWidths = { ...props.strokeWidths }
  newStrokeWidths['global'] = width
  emit('update', { strokeWidths: newStrokeWidths })
}

const resetAllStyles = () => {
  console.log('🎨 SVGToolbar: Resetting all styles to original')
  emit('update', { 
    fillColors: {}, 
    strokeColors: {}, 
    strokeWidths: {} 
  })
}
</script>

<style scoped>
/* Ensure PropertyColorPicker popovers appear above all other elements */
:deep(.popover-panel) {
  z-index: 9999 !important;
}

/* Additional fallback for headless UI popovers */
:deep([role="dialog"]) {
  z-index: 9999 !important;
}

/* Ensure the container doesn't clip overflow */
.space-y-4 {
  overflow: visible !important;
}

/* Specifically handle the color picker container */
.color-picker-container {
  overflow: visible !important;
}

/* Ensure any nested popovers can escape the container bounds */
.color-picker-container :deep(*) {
  overflow: visible !important;
}
</style>



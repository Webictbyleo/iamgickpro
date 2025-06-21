<template>
  <div class="flex items-center space-x-4">
    <!-- SVG Source Display -->
    <div class="flex items-center space-x-2">
      <label class="text-sm font-medium text-gray-700 dark:text-gray-300 min-w-max">SVG:</label>
      <span class="px-3 py-1 text-sm bg-gray-100 dark:bg-gray-700 rounded-md border border-gray-200 dark:border-gray-600 min-w-[120px] text-gray-800 dark:text-gray-200 font-medium truncate">
        {{ getSvgLabel(src) }}
      </span>
    </div>

    <!-- Customize Colors & Styles -->
    <IconDropdown
      :icon="PaintBrushIcon"
      tooltip="Customize SVG colors and styles"
      placement="bottom-end"
      text="Customize"
      width="w-80"
      :showChevron="false"
      buttonClass="hover:bg-blue-50 dark:hover:bg-blue-900/20 text-gray-600 hover:text-blue-600 dark:text-gray-300 dark:hover:text-blue-400"
      iconClass="w-3.5 h-3.5"
    >
      <template #default="{ close }">
        <div class="p-4 space-y-4" style="overflow: visible;">
          <!-- Fill Colors Section -->
          <div>
            <div class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Fill Colors</div>
            
            <div v-if="availableElements.length === 0" class="text-sm text-gray-500 dark:text-gray-400 text-center py-4">
              No customizable elements found in this SVG
            </div>
            
            <div v-else class="space-y-3 max-h-32 overflow-y-auto color-picker-container">
              <div 
                v-for="element in availableElements.slice(0, 3)" 
                :key="element.id + '-fill'"
                class="flex items-center justify-between space-x-3"
              >
                <span class="text-sm text-gray-600 dark:text-gray-400 font-mono truncate flex-1">
                  {{ element.label }}
                </span>
                <PropertyColorPicker
                  :value="fillColors?.[element.id] || element.defaultFill || '#000000'"
                  @update="(value: string) => updateFillColor(element.id, value)"
                />
              </div>
            </div>
          </div>

          <!-- Stroke Section -->
          <div class="border-t border-gray-200 dark:border-gray-600 pt-4">
            <div class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Stroke Settings</div>
            
            <div v-if="availableElements.length === 0" class="text-sm text-gray-500 dark:text-gray-400 text-center py-4">
              No customizable elements found in this SVG
            </div>
            
            <div v-else class="space-y-3">
              <!-- Stroke Color for main element -->
              <div class="flex items-center justify-between space-x-3">
                <span class="text-sm text-gray-600 dark:text-gray-400">Stroke Color:</span>
                <PropertyColorPicker
                  :value="strokeColors?.[availableElements[0]?.id] || '#000000'"
                  @update="(value: string) => updateStrokeColor(availableElements[0]?.id, value)"
                />
              </div>
              
              <!-- Stroke Width -->
              <div class="flex items-center justify-between space-x-3">
                <span class="text-sm text-gray-600 dark:text-gray-400">Stroke Width:</span>
                <PropertyNumberInput
                  :value="strokeWidths?.[availableElements[0]?.id] || 1"
                  @update:value="(value) => updateStrokeWidth(availableElements[0]?.id, value)"
                  @change="(value) => updateStrokeWidth(availableElements[0]?.id, value)"
                  :min="0"
                  :max="10"
                  :step="0.5"
                  unit="px"
                  input-class="w-16"
                  placeholder="1"
                />
              </div>
            </div>
          </div>

          <!-- Reset Section -->
          <div class="border-t border-gray-200 dark:border-gray-600 pt-4">
            <ModernButton
              variant="outline"
              size="sm"
              @click="resetAllCustomizations"
              class="w-full"
            >
              Reset All to Default
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

interface SVGElement {
  id: string
  label: string
  defaultFill?: string
  defaultStroke?: string
  defaultStrokeWidth?: number
}

interface Props {
  src?: string
  fillColors?: Record<string, string>
  strokeColors?: Record<string, string>
  strokeWidths?: Record<string, number>
  preserveAspectRatio?: string
}

const props = withDefaults(defineProps<Props>(), {
  src: '',
  fillColors: () => ({}),
  strokeColors: () => ({}),
  strokeWidths: () => ({}),
  preserveAspectRatio: 'xMidYMid meet'
})

const emit = defineEmits<{
  update: [properties: Partial<Props>]
}>()

// Aspect ratio options for SVG scaling - simplified to most common options
const aspectRatioOptions = [
  { label: 'Fit (Meet)', value: 'xMidYMid meet' },
  { label: 'Fill (Slice)', value: 'xMidYMid slice' },
  { label: 'Stretch', value: 'none' },
]

// Mock available elements - in a real implementation, this would parse the SVG
// For now, we'll provide common element types that users can customize
const availableElements = computed((): SVGElement[] => {
  // In a real implementation, we would parse the SVG content to find elements with IDs or classes
  // For now, we'll provide some common element identifiers
  return [
    { id: 'path', label: 'Path Elements', defaultFill: '#000000', defaultStroke: 'none', defaultStrokeWidth: 1 },
    { id: 'circle', label: 'Circle Elements', defaultFill: 'none', defaultStroke: '#000000', defaultStrokeWidth: 1 },
    { id: 'rect', label: 'Rectangle Elements', defaultFill: '#000000', defaultStroke: 'none', defaultStrokeWidth: 1 },
    { id: 'polygon', label: 'Polygon Elements', defaultFill: '#000000', defaultStroke: 'none', defaultStrokeWidth: 1 },
    { id: 'line', label: 'Line Elements', defaultFill: 'none', defaultStroke: '#000000', defaultStrokeWidth: 1 },
  ]
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

const updateFillColor = (elementId: string, color: string) => {
  const newFillColors = { ...props.fillColors }
  newFillColors[elementId] = color
  emit('update', { fillColors: newFillColors })
}

const updateStrokeColor = (elementId: string, color: string) => {
  const newStrokeColors = { ...props.strokeColors }
  newStrokeColors[elementId] = color
  emit('update', { strokeColors: newStrokeColors })
}

const updateStrokeWidth = (elementId: string, width: number) => {
  const newStrokeWidths = { ...props.strokeWidths }
  newStrokeWidths[elementId] = width
  emit('update', { strokeWidths: newStrokeWidths })
}

const resetFillColors = () => {
  emit('update', { fillColors: {} })
}

const resetStrokeColors = () => {
  emit('update', { strokeColors: {} })
}

const resetStrokeWidths = () => {
  emit('update', { strokeWidths: {} })
}

const resetAllCustomizations = () => {
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



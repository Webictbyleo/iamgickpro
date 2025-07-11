<template>
  <div class="flex items-center space-x-4">
    <!-- Chart Color Controls -->
    <div class="flex items-center space-x-3">
      <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Primary:</span>
      <PropertyColorPicker
        :value="theme?.primary || '#3B82F6'"
        @update="updateThemeColor('primary', $event)"
      />
    </div>

    <div class="flex items-center space-x-3">
      <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Secondary:</span>
      <PropertyColorPicker
        :value="theme?.secondary || '#10B981'"
        @update="updateThemeColor('secondary', $event)"
      />
    </div>

    <div class="flex items-center space-x-3">
      <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Background:</span>
      <PropertyColorPicker
        :value="theme?.background || '#FFFFFF'"
        @update="updateThemeColor('background', $event)"
      />
    </div>

    <!-- Edit Button -->
     <div>
      <ModernButton
      variant="outline"
      size="sm"
      @click="$emit('edit-chart')"
      tooltip="Open chart editor"
    >
      <PencilIcon class="w-4 h-4 mr-1" />
      Edit
    </ModernButton>
     </div>
    
  </div>
</template>

<script setup lang="ts">
import { 
  PencilIcon
} from '@heroicons/vue/24/outline'
import ModernButton from '@/components/common/ModernButton.vue'
import PropertyColorPicker from '@/components/editor/Properties/PropertyColorPicker.vue'
import type { ChartTheme } from '@/types'

interface Props {
  theme?: ChartTheme
}

const props = defineProps<Props>()

const emit = defineEmits<{
  update: [properties: Partial<Props>]
  'edit-chart': []
}>()

const updateThemeColor = (colorType: keyof ChartTheme, color: string) => {
  if (!props.theme) return
  const updatedTheme = { ...props.theme, [colorType]: color }
  emit('update', { theme: updatedTheme })
}
</script>

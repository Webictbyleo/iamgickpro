<template>
  <div class="bg-white border-b shadow-sm flex-shrink-0">
    <div class="flex items-center justify-between px-4 py-2">
      <!-- Left Section -->
      <div class="flex items-center space-x-3">
        <button
          @click="$router.push('/dashboard')"
          class="p-1.5 text-gray-600 hover:text-gray-900 transition-colors rounded-md hover:bg-gray-100"
          title="Back to Dashboard"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
          </svg>
        </button>
        
        <div class="flex items-center space-x-2">
          <input
            :value="designName"
            @input="updateDesignName"
            @blur="$emit('save')"
            class="text-base font-medium bg-transparent border-none outline-none focus:bg-gray-50 rounded px-2 py-1 min-w-0"
            placeholder="Untitled Design"
          />
          <span class="text-xs text-gray-500 whitespace-nowrap">{{ saveStatus }}</span>
        </div>
      </div>
      
      <!-- Center Tools -->
      <div class="flex items-center space-x-1 bg-gray-100 rounded-md p-0.5">
        <BaseButton
          variant="ghost"
          size="xs"
          :disabled="!canUndo"
          @click="$emit('undo')"
          title="Undo (Ctrl+Z)"
          class="p-1.5"
        >
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6" />
          </svg>
        </BaseButton>
        
        <BaseButton
          variant="ghost"
          size="xs"
          :disabled="!canRedo"
          @click="$emit('redo')"
          title="Redo (Ctrl+Y)"
          class="p-1.5"
        >
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 10h-10a8 8 0 00-8 8v2m18-10l-6 6m6-6l-6-6" />
          </svg>
        </BaseButton>
        
        <div class="w-px h-4 bg-gray-300 mx-0.5"></div>
        
        <BaseButton
          variant="ghost"
          size="xs"
          @click="$emit('save')"
          title="Save (Ctrl+S)"
          class="p-1.5"
        >
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4" />
          </svg>
        </BaseButton>
      </div>
      
      <!-- Right Actions -->
      <div class="flex items-center space-x-2">
        <BaseButton
          variant="outline"
          size="xs"
          @click="$emit('preview')"
          class="px-3 py-1.5"
        >
          <svg class="w-3.5 h-3.5 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
          </svg>
          Preview
        </BaseButton>
        
        <BaseDropdown trigger-text="">
          <template #trigger>
            <BaseButton variant="primary" size="xs" class="px-3 py-1.5">
              <svg class="w-3.5 h-3.5 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
              </svg>
              Export
              <svg class="w-3 h-3 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
              </svg>
            </BaseButton>
          </template>
          
          <template #default="{ close }">
            <div class="py-1">
              <button
                v-for="format in exportFormats"
                :key="format.value"
                @click="handleExport(format.value, close)"
                class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 flex items-center"
              >
                <span class="flex-1">{{ format.label }}</span>
                <span class="text-xs text-gray-500">{{ format.extension }}</span>
              </button>
            </div>
          </template>
        </BaseDropdown>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import BaseButton from '@/components/common/BaseButton.vue'
import BaseDropdown from '@/components/common/BaseDropdown.vue'
import {
  CursorArrowRaysIcon,
  HandRaisedIcon,
  DocumentTextIcon,
  Square3Stack3DIcon
} from '@heroicons/vue/24/outline'

interface Props {
  designName: string
  saveStatus: string
  canUndo: boolean
  canRedo: boolean
  layerCount?: number
  selectedCount?: number
  canvasWidth?: number
  canvasHeight?: number
}

const props = withDefaults(defineProps<Props>(), {
  layerCount: 0,
  selectedCount: 0,
  canvasWidth: 800,
  canvasHeight: 600
})

const emit = defineEmits<{
  'update:designName': [value: string]
  save: []
  export: [format: string]
  preview: []
  share: []
  undo: []
  redo: []
}>()

const activeTool = ref('select')

// Tools configuration
const tools = [
  { id: 'select', icon: CursorArrowRaysIcon, tooltip: 'Select (V)' },
  { id: 'pan', icon: HandRaisedIcon, tooltip: 'Pan (H)' },
  { id: 'text', icon: DocumentTextIcon, tooltip: 'Text (T)' },
  { id: 'shapes', icon: Square3Stack3DIcon, tooltip: 'Shapes (R)' }
]

// Export formats
const exportFormats = [
  { value: 'png', label: 'PNG Image', extension: '.png' },
  { value: 'jpg', label: 'JPEG Image', extension: '.jpg' },
  { value: 'pdf', label: 'PDF Document', extension: '.pdf' },
  { value: 'svg', label: 'SVG Vector', extension: '.svg' }
]

const updateDesignName = (event: Event) => {
  const target = event.target as HTMLInputElement
  emit('update:designName', target.value)
}

const setActiveTool = (toolId: string) => {
  activeTool.value = toolId
  // TODO: Implement tool switching logic
}

const handleExport = (format: string, closeDropdown: () => void) => {
  emit('export', format)
  closeDropdown()
}
</script>

<template>
  <div class="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 flex-shrink-0 shadow-sm">
    <!-- Light Modern Toolbar to complement dark sidebar -->
    <div class="flex items-center h-16 px-6">
      <!-- Left Section - Logo & Design Name -->
      <div class="flex items-center space-x-4 min-w-0 mr-8">
        <!-- Logo/Brand Space -->
        <div class="flex items-center">
          <div class="w-8 h-8 bg-gradient-to-br from-violet-500 to-purple-600 rounded-lg flex items-center justify-center shadow-lg hover:shadow-xl transition-shadow duration-200">
            <span class="text-white font-bold text-sm">IG</span>
          </div>
        </div>
        
        <!-- Resize Dropdown -->
        <ResizeDropdown @resize="handleResize" @custom-resize="() => emit('custom-resize')" />
        
        <!-- Design Name -->
        <div class="flex items-center bg-gray-50 dark:bg-gray-700 rounded-xl px-4 py-2.5 border border-gray-200 dark:border-gray-600 hover:border-violet-300 dark:hover:border-violet-500 transition-all duration-200 shadow-sm hover:shadow-md group">
          <input
            :value="designName"
            @input="updateDesignName"
            @blur="$emit('save')"
            class="text-sm font-medium bg-transparent border-none outline-none text-gray-900 dark:text-gray-100 placeholder-gray-500 dark:placeholder-gray-400 focus:bg-white dark:focus:bg-gray-600 rounded-lg px-2 py-1 min-w-0 max-w-xs truncate transition-all duration-200 group-hover:bg-white dark:group-hover:bg-gray-600"
            placeholder="Untitled Design"
          />
        </div>
      </div>

      <!-- Center Section - History Controls -->
      <div class="flex-1 flex items-center justify-center">
        <div class="flex items-center bg-gray-50 dark:bg-gray-700 rounded-2xl p-1.5 shadow-sm border border-gray-200 dark:border-gray-600">
          <!-- History Controls -->
          <div class="flex items-center space-x-1">
            <button
              :disabled="!canUndo"
              @click="$emit('undo')"
              :class="[
                'p-2.5 rounded-xl transition-all duration-200 group relative',
                canUndo 
                  ? 'hover:bg-white dark:hover:bg-gray-600 hover:shadow-sm text-gray-600 dark:text-gray-300 hover:text-violet-600 dark:hover:text-violet-400 hover:scale-105' 
                  : 'text-gray-400 dark:text-gray-500 cursor-not-allowed opacity-50'
              ]"
              title="Undo (Ctrl+Z)"
            >
              <UndoIcon class="w-4 h-4" />
            </button>
            
            <button
              :disabled="!canRedo"
              @click="$emit('redo')"
              :class="[
                'p-2.5 rounded-xl transition-all duration-200 group relative',
                canRedo 
                  ? 'hover:bg-white dark:hover:bg-gray-600 hover:shadow-sm text-gray-600 dark:text-gray-300 hover:text-violet-600 dark:hover:text-violet-400 hover:scale-105' 
                  : 'text-gray-400 dark:text-gray-500 cursor-not-allowed opacity-50'
              ]"
              title="Redo (Ctrl+Y)"
            >
              <RedoIcon class="w-4 h-4" />
            </button>
          </div>
        </div>
      </div>

      <!-- Right Section - Export Action -->
      <div class="flex items-center space-x-3">
        <!-- Export Dropdown -->
        <ExportDropdown @export="handleExport" />
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import ExportDropdown from './ExportDropdown.vue'
import ResizeDropdown from './ResizeDropdown.vue'
import UndoIcon from '@/components/icons/UndoIcon.vue'
import RedoIcon from '@/components/icons/RedoIcon.vue'

interface Props {
  designName: string
  saveStatus: string
  canUndo: boolean
  canRedo: boolean
  showComments?: boolean
  commentCount?: number
  selectedLayer?: any
}

const props = withDefaults(defineProps<Props>(), {
  showComments: false,
  commentCount: 0,
  selectedLayer: undefined
})

const emit = defineEmits<{
  'update:designName': [value: string]
  'save': []
  'undo': []
  'redo': []
  'share': []
  'toggle-comments': []
  'present': []
  'export': [format: string]
  'resize': [width: number, height: number]
  'custom-resize': []
}>()

const updateDesignName = (event: Event) => {
  const target = event.target as HTMLInputElement
  emit('update:designName', target.value)
}

const handleExport = (format: string) => {
  emit('export', format)
}

const handleResize = (width: number, height: number) => {
  emit('resize', width, height)
}
</script>

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
            ref="designNameInput"
            :value="designName"
            @input="updateDesignName"
            @focus="handleDesignNameFocus"
            @blur="handleDesignNameBlur"
            @keydown.enter="handleDesignNameEnter"
            @keydown.escape="handleDesignNameEscape"
            class="text-sm font-medium bg-transparent border-none outline-none text-gray-900 dark:text-gray-100 placeholder-gray-500 dark:placeholder-gray-400 focus:bg-white dark:focus:bg-gray-600 rounded-lg px-2 py-1 min-w-0 max-w-xs truncate transition-all duration-200 group-hover:bg-white dark:group-hover:bg-gray-600"
            :placeholder="designName || 'Untitled Design'"
          />
        </div>
      </div>

      <!-- Center Section - History Controls & Status Indicators -->
      <div class="flex-1 flex items-center justify-center">
        <div class="flex items-center space-x-4">
          <!-- History Controls - Flat Design -->
          <div class="flex items-center bg-gray-100 dark:bg-gray-700 rounded-lg border border-gray-200 dark:border-gray-600">
            <button
              :disabled="!canUndo"
              @click="$emit('undo')"
              :class="[
                'px-3 py-2 transition-all duration-200 flex items-center space-x-1.5 border-r border-gray-200 dark:border-gray-600',
                canUndo 
                  ? 'hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 hover:text-violet-600 dark:hover:text-violet-400' 
                  : 'text-gray-400 dark:text-gray-500 cursor-not-allowed'
              ]"
              title="Undo (Ctrl+Z)"
            >
              <UndoIcon class="w-4 h-4" />
              <span class="text-xs font-medium">Undo</span>
            </button>
            
            <button
              :disabled="!canRedo"
              @click="$emit('redo')"
              :class="[
                'px-3 py-2 transition-all duration-200 flex items-center space-x-1.5',
                canRedo 
                  ? 'hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 hover:text-violet-600 dark:hover:text-violet-400' 
                  : 'text-gray-400 dark:text-gray-500 cursor-not-allowed'
              ]"
              title="Redo (Ctrl+Y)"
            >
              <RedoIcon class="w-4 h-4" />
              <span class="text-xs font-medium">Redo</span>
            </button>
          </div>

          <!-- Save Status Indicator with Icon -->
          <div class="flex items-center">
            <div 
              :class="[
                'flex items-center space-x-1.5 px-2.5 py-1.5 rounded-lg transition-all duration-200',
                saveStatus === 'saved' ? 'bg-green-50 dark:bg-green-900/20 text-green-600 dark:text-green-400' :
                saveStatus === 'saving' ? 'bg-yellow-50 dark:bg-yellow-900/20 text-yellow-600 dark:text-yellow-400' :
                saveStatus === 'error' ? 'bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-400' : 
                'bg-gray-50 dark:bg-gray-700 text-gray-500 dark:text-gray-400'
              ]"
              :title="saveStatus === 'saved' ? 'All changes saved' :
                     saveStatus === 'saving' ? 'Saving changes...' :
                     saveStatus === 'error' ? 'Failed to save changes' : 'Unsaved changes'"
            >
              <!-- Saved Icon -->
              <CheckCircleIcon 
                v-if="saveStatus === 'saved'" 
                class="w-4 h-4"
              />
              <!-- Saving Icon -->
              <ArrowPathIcon 
                v-else-if="saveStatus === 'saving'" 
                class="w-4 h-4 animate-spin"
              />
              <!-- Error Icon -->
              <ExclamationCircleIcon 
                v-else-if="saveStatus === 'error'" 
                class="w-4 h-4"
              />
              <!-- Unsaved Icon -->
              <ClockIcon 
                v-else 
                class="w-4 h-4"
              />
            </div>
          </div>
        </div>
      </div>

      <!-- Right Section - Save & Export Actions -->
      <div class="flex items-center space-x-3">
        <!-- Save Button -->
        <button
          @click="$emit('save')"
          :disabled="saveStatus === 'saving'"
          class="inline-flex items-center px-4 py-2 bg-violet-600 hover:bg-violet-700 disabled:bg-violet-400 text-white text-sm font-medium rounded-lg transition-all duration-200 shadow-sm hover:shadow-md disabled:cursor-not-allowed"
          title="Save Design (Ctrl+S)"
        >
          <ArrowPathIcon 
            v-if="saveStatus === 'saving'" 
            class="w-4 h-4 mr-2 animate-spin"
          />
          <svg 
            v-else
            class="w-4 h-4 mr-2" 
            fill="none" 
            stroke="currentColor" 
            viewBox="0 0 24 24"
          >
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4" />
          </svg>
          {{ saveStatus === 'saving' ? 'Saving...' : 'Save' }}
        </button>
        
        <!-- Export Dropdown -->
        <ExportDropdown @export="handleExport" />
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import ExportDropdown from './ExportDropdown.vue'
import ResizeDropdown from './ResizeDropdown.vue'
import UndoIcon from '@/components/icons/UndoIcon.vue'
import RedoIcon from '@/components/icons/RedoIcon.vue'
import { 
  CheckCircleIcon, 
  ArrowPathIcon, 
  ExclamationCircleIcon, 
  ClockIcon 
} from '@heroicons/vue/24/outline'

interface Props {
  designName: string
  saveStatus: string
  canUndo: boolean
  canRedo: boolean
  showComments?: boolean
  commentCount?: number
}

const props = withDefaults(defineProps<Props>(), {
  showComments: false,
  commentCount: 0
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

const designNameInput = ref<HTMLInputElement>()
const originalDesignName = ref<string>('')

const updateDesignName = (event: Event) => {
  const target = event.target as HTMLInputElement
  emit('update:designName', target.value)
}

const handleDesignNameFocus = (event: Event) => {
  const target = event.target as HTMLInputElement
  originalDesignName.value = target.value
  // Select all text when focused for easy editing
  setTimeout(() => {
    target.select()
  }, 0)
}

const handleDesignNameBlur = () => {
  emit('save')
}

const handleDesignNameEnter = (event: KeyboardEvent) => {
  const target = event.target as HTMLInputElement
  target.blur()
  emit('save')
}

const handleDesignNameEscape = (event: KeyboardEvent) => {
  const target = event.target as HTMLInputElement
  // Restore original value and blur
  target.value = originalDesignName.value
  emit('update:designName', originalDesignName.value)
  target.blur()
}

const handleExport = (format: string) => {
  emit('export', format)
}

const handleResize = (width: number, height: number) => {
  emit('resize', width, height)
}
</script>

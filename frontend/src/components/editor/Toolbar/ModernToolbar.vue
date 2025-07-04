<template>
  <div class="bg-white dark:bg-secondary-900 border-b border-secondary-200 dark:border-secondary-700 flex-shrink-0 shadow-sm">
    <!-- Light Modern Toolbar to complement dark sidebar -->
    <div class="flex items-center h-16 px-6">
      <!-- Left Section - Logo & Design Name -->
      <div class="flex items-center space-x-4 min-w-0 mr-8">
        <!-- Logo/Brand Space -->
        <div class="flex items-center">
          <div class="w-8 h-8 bg-gradient-to-br from-primary-500 to-primary-600 rounded-lg flex items-center justify-center shadow-lg hover:shadow-xl transition-shadow duration-200">
            <span class="text-white font-bold text-sm">{{ appInitials }}</span>
          </div>
        </div>
        
        <!-- Design Name -->
        <div class="flex items-center bg-secondary-50 dark:bg-secondary-800 rounded-xl px-4 py-2.5 border border-secondary-200 dark:border-secondary-600 hover:border-primary-300 dark:hover:border-primary-500 transition-all duration-200 shadow-sm hover:shadow-md group">
          <input
            ref="designNameInput"
            :value="designName"
            @input="updateDesignName"
            @focus="handleDesignNameFocus"
            @blur="handleDesignNameBlur"
            @keydown.enter="handleDesignNameEnter"
            @keydown.escape="handleDesignNameEscape"
            class="text-sm font-medium bg-transparent border-none outline-none text-secondary-900 dark:text-secondary-100 placeholder-secondary-500 dark:placeholder-secondary-400 focus:bg-white dark:focus:bg-secondary-700 rounded-lg px-2 py-1 min-w-0 max-w-xs truncate transition-all duration-200 group-hover:bg-white dark:group-hover:bg-secondary-700"
            :placeholder="designName || 'Untitled Design'"
          />
        </div>
      </div>

      <!-- Center Section - History Controls & Status Indicators -->
      <div class="flex-1 flex items-center justify-center">
        <div class="flex items-center space-x-4">
          <!-- History Controls - Flat Design -->
          <div class="flex items-center bg-secondary-100 dark:bg-secondary-800 rounded-lg border border-secondary-200 dark:border-secondary-600">
            <button
              :disabled="!canUndo"
              @click="$emit('undo')"
              :class="[
                'px-3 py-2 transition-all duration-200 flex items-center space-x-1.5 border-r border-secondary-200 dark:border-secondary-600',
                canUndo 
                  ? 'hover:bg-secondary-200 dark:hover:bg-secondary-700 text-secondary-700 dark:text-secondary-200 hover:text-primary-600 dark:hover:text-primary-400' 
                  : 'text-secondary-400 dark:text-secondary-500 cursor-not-allowed'
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
                  ? 'hover:bg-secondary-200 dark:hover:bg-secondary-700 text-secondary-700 dark:text-secondary-200 hover:text-primary-600 dark:hover:text-primary-400' 
                  : 'text-secondary-400 dark:text-secondary-500 cursor-not-allowed'
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
                saveStatus === 'saved' ? 'bg-success-50 dark:bg-success-900/20 text-success-600 dark:text-success-400' :
                saveStatus === 'saving' ? 'bg-warning-50 dark:bg-warning-900/20 text-warning-600 dark:text-warning-400' :
                saveStatus === 'error' ? 'bg-danger-50 dark:bg-danger-900/20 text-danger-600 dark:text-danger-400' : 
                'bg-secondary-50 dark:bg-secondary-800 text-secondary-500 dark:text-secondary-400'
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

      <!-- Right Section - Theme Toggle, Save & Export Actions -->
      <div class="flex items-center space-x-3">
        <!-- Theme Toggle -->
        <ThemeToggle />
        
        <!-- Save Button -->
        <button
          @click="$emit('save')"
          :disabled="saveStatus === 'saving'"
          class="inline-flex items-center px-4 py-2 bg-primary-600 hover:bg-primary-700 disabled:bg-primary-400 text-white text-sm font-medium rounded-lg transition-all duration-200 shadow-sm hover:shadow-md disabled:cursor-not-allowed focus:outline-none focus:ring-2 focus:ring-primary-500/50 dark:focus:ring-primary-400/50"
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
import { ref, computed } from 'vue'
import ExportDropdown from './ExportDropdown.vue'
import ThemeToggle from '@/components/ui/ThemeToggle.vue'
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

const appInitials = computed(() => {
  const title = import.meta.env.VITE_APP_TITLE || 'Design Studio'
  const words = title.split(' ').filter(word => word.length > 0)
  
  if (words.length === 1) {
    // Single word: take first two characters
    return words[0].substring(0, 2).toUpperCase()
  } else if (words.length >= 2) {
    // Multiple words: take first character of first two words
    return (words[0][0] + words[1][0]).toUpperCase()
  }
  
  // Fallback
  return 'DS'
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
  // Only save if the value actually changed
  if (originalDesignName.value !== props.designName) {
    emit('save')
  }
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
</script>

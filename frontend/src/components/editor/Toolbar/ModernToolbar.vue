<template>
  <div class="bg-white dark:bg-gray-900 border-b border-gray-200 dark:border-gray-700 flex-shrink-0 shadow-sm backdrop-blur-sm">
    <!-- Enhanced Modern Compact Toolbar -->
    <div class="flex items-center h-14 px-4">
      <!-- Left Section - Design Name & Status with enhanced styling -->
      <div class="flex items-center space-x-3 min-w-0 mr-6">
        <div class="flex items-center space-x-2 bg-gray-50 dark:bg-gray-800 rounded-lg px-3 py-2">
          <input
            :value="designName"
            @input="updateDesignName"
            @blur="$emit('save')"
            class="text-sm font-semibold bg-transparent border-none outline-none focus:bg-white dark:focus:bg-gray-700 rounded-md px-2 py-1 min-w-0 max-w-xs truncate transition-colors duration-200"
            placeholder="Untitled Design"
          />
          
          <div 
            :class="[
              'flex items-center h-6 px-2 rounded-full text-xs font-medium transition-all duration-200',
              saveStatus === 'Saving...' ? 'bg-yellow-100 dark:bg-yellow-900 text-yellow-800 dark:text-yellow-200' :
              saveStatus === 'All changes saved' ? 'bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200' :
              'bg-red-100 dark:bg-red-900 text-red-800 dark:text-red-200'
            ]"
            :title="saveStatus"
          >
            <div 
              :class="[
                'w-1.5 h-1.5 rounded-full mr-1.5',
                saveStatus === 'Saving...' ? 'bg-yellow-500 animate-pulse' : 
                saveStatus === 'All changes saved' ? 'bg-green-500' : 
                'bg-red-500'
              ]"
            ></div>
            <span class="truncate max-w-[80px] font-medium">{{ saveStatus }}</span>
          </div>
        </div>
      </div>

      <!-- Center Section - Enhanced Compact Tools -->
      <div class="flex-1 flex items-center justify-center">
        <div class="flex items-center bg-gray-100 dark:bg-gray-800 rounded-xl p-1 shadow-inner backdrop-blur-sm border border-gray-200 dark:border-gray-700">
          <!-- History Controls with better spacing -->
          <div class="flex border-r border-gray-300 dark:border-gray-600 pr-2 mr-2">
            <ModernButton
              :disabled="!canUndo"
              @click="$emit('undo')"
              variant="ghost"
              size="sm"
              tooltip="Undo (Ctrl+Z)"
              class="rounded-lg mr-1"
            >
              <UndoIcon class="w-4 h-4" />
            </ModernButton>
            
            <ModernButton
              :disabled="!canRedo"
              @click="$emit('redo')"
              variant="ghost"
              size="sm"
              tooltip="Redo (Ctrl+Y)"
              class="rounded-lg"
            >
              <RedoIcon class="w-4 h-4" />
            </ModernButton>
          </div>

          <!-- Quick Create Tools with enhanced styling -->
          <div class="flex space-x-1">
            <ModernButton
              @click="handleToolChange('text')"
              variant="ghost"
              size="sm"
              :class="[
                'rounded-lg transition-all duration-200',
                activeTool === 'text' 
                  ? 'bg-blue-100 dark:bg-blue-800 text-blue-600 dark:text-blue-200 shadow-sm scale-105' 
                  : 'hover:bg-gray-200 dark:hover:bg-gray-700'
              ]"
              tooltip="Text Tool (T)"
            >
              <DocumentTextIcon class="w-4 h-4" />
            </ModernButton>
            
            <ModernButton
              @click="handleToolChange('shape')"
              variant="ghost"
              size="sm"
              :class="[
                'rounded-lg transition-all duration-200',
                activeTool === 'shape' 
                  ? 'bg-blue-100 dark:bg-blue-800 text-blue-600 dark:text-blue-200 shadow-sm scale-105' 
                  : 'hover:bg-gray-200 dark:hover:bg-gray-700'
              ]"
              tooltip="Shape Tool (R)"
            >
              <Square3Stack3DIcon class="w-4 h-4" />
            </ModernButton>
            
            <ModernButton
              @click="handleToolChange('image')"
              variant="ghost"
              size="sm"
              :class="[
                'rounded-lg transition-all duration-200',
                activeTool === 'image' 
                  ? 'bg-blue-100 dark:bg-blue-800 text-blue-600 dark:text-blue-200 shadow-sm scale-105' 
                  : 'hover:bg-gray-200 dark:hover:bg-gray-700'
              ]"
              tooltip="Image Tool (I)"
            >
              <PhotoIcon class="w-4 h-4" />
            </ModernButton>
          </div>
        </div>
      </div>

      <!-- Right Section - Enhanced Actions with better spacing -->
      <div class="flex items-center space-x-1">
        <!-- Export Dropdown -->
        <ExportDropdown @export="handleExport" compact />
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import {
  DocumentTextIcon,
  Square3Stack3DIcon,
  PhotoIcon,
  UserGroupIcon,
  ChatBubbleLeftIcon,
  PresentationChartLineIcon
} from '@heroicons/vue/24/outline'
import ModernButton from '@/components/common/ModernButton.vue'
import ExportDropdown from './ExportDropdown.vue'
import UndoIcon from '@/components/icons/UndoIcon.vue'
import RedoIcon from '@/components/icons/RedoIcon.vue'

interface Props {
  designName: string
  saveStatus: string
  canUndo: boolean
  canRedo: boolean
  activeTool?: string
  showComments?: boolean
  commentCount?: number
  selectedLayer?: any
}

const props = withDefaults(defineProps<Props>(), {
  activeTool: 'select',
  showComments: false,
  commentCount: 0,
  selectedLayer: undefined
})

const emit = defineEmits<{
  'update:designName': [value: string]
  'save': []
  'undo': []
  'redo': []
  'add-text': []
  'add-shape': []
  'add-image': []
  'share': []
  'toggle-comments': []
  'present': []
  'export': [format: string]
  'tool-change': [tool: string]
}>()

const updateDesignName = (event: Event) => {
  const target = event.target as HTMLInputElement
  emit('update:designName', target.value)
}

const handleExport = (format: string) => {
  emit('export', format)
}

const handleToolChange = (tool: string) => {
  emit('tool-change', tool)
}
</script>

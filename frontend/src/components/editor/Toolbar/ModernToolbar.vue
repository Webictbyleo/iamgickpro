<template>
  <div class="bg-white border-b border-gray-200 flex-shrink-0 shadow-sm">
    <!-- Main Toolbar Row -->
    <div class="flex items-center justify-between h-14 px-4">
      <!-- Left Section - Design Name & Status -->
      <div class="flex items-center space-x-4 min-w-0 flex-1">
        <input
          :value="designName"
          @input="updateDesignName"
          @blur="$emit('save')"
          class="text-lg font-semibold bg-transparent border-none outline-none focus:bg-gray-50 rounded-lg px-3 py-2 min-w-0 max-w-xs truncate"
          placeholder="Untitled Design"
        />
        
        <div class="flex items-center space-x-2">
          <div :class="[
            'flex items-center space-x-1 px-2 py-1 rounded-full text-xs font-medium',
            saveStatus === 'Saving...' ? 'bg-yellow-100 text-yellow-800' :
            saveStatus === 'All changes saved' ? 'bg-green-100 text-green-800' :
            'bg-red-100 text-red-800'
          ]">
            <div v-if="saveStatus === 'Saving...'" class="w-2 h-2 bg-yellow-500 rounded-full animate-pulse"></div>
            <div v-else-if="saveStatus === 'All changes saved'" class="w-2 h-2 bg-green-500 rounded-full"></div>
            <div v-else class="w-2 h-2 bg-red-500 rounded-full"></div>
            <span>{{ saveStatus }}</span>
          </div>
        </div>
      </div>

      <!-- Center Section - Main Tools -->
      <div class="flex items-center space-x-1 bg-gray-100 rounded-xl p-1">
        <!-- Undo/Redo -->
        <div class="flex items-center space-x-1 pr-2 border-r border-gray-300">
          <ModernButton
            :disabled="!canUndo"
            @click="$emit('undo')"
            variant="ghost"
            size="sm"
            tooltip="Undo (Ctrl+Z)"
          >
            <UndoIcon class="w-4 h-4" />
          </ModernButton>
          
          <ModernButton
            :disabled="!canRedo"
            @click="$emit('redo')"
            variant="ghost"
            size="sm"
            tooltip="Redo (Ctrl+Y)"
          >
            <RedoIcon class="w-4 h-4" />
          </ModernButton>
        </div>

        <!-- Quick Add Tools -->
        <div class="flex items-center space-x-1 px-2">
          <ModernButton
            @click="$emit('add-text')"
            variant="ghost"
            size="sm"
            tooltip="Add Text (T)"
          >
            <DocumentTextIcon class="w-4 h-4" />
          </ModernButton>
          
          <ModernButton
            @click="$emit('add-shape')"
            variant="ghost"
            size="sm"
            tooltip="Add Shape (R)"
          >
            <Square3Stack3DIcon class="w-4 h-4" />
          </ModernButton>
          
          <ModernButton
            @click="$emit('add-image')"
            variant="ghost"
            size="sm"
            tooltip="Add Image"
          >
            <PhotoIcon class="w-4 h-4" />
          </ModernButton>
        </div>
      </div>

      <!-- Right Section - Actions -->
      <div class="flex items-center space-x-2 flex-1 justify-end">
        <!-- Collaboration -->
        <ModernButton
          @click="$emit('share')"
          variant="outline"
          size="sm"
        >
          <UserGroupIcon class="w-4 h-4 mr-2" />
          Share
        </ModernButton>

        <!-- Comments -->
        <ModernButton
          @click="$emit('toggle-comments')"
          variant="ghost"
          size="sm"
          :class="{ 'bg-blue-100 text-blue-600': showComments }"
          tooltip="Comments"
        >
          <ChatBubbleLeftIcon class="w-4 h-4" />
          <span v-if="commentCount > 0" class="ml-1 text-xs">{{ commentCount }}</span>
        </ModernButton>

        <!-- Present Mode -->
        <ModernButton
          @click="$emit('present')"
          variant="outline"
          size="sm"
        >
          <PresentationChartLineIcon class="w-4 h-4 mr-2" />
          Present
        </ModernButton>

        <!-- Export Dropdown -->
        <ExportDropdown @export="handleExport" />
      </div>
    </div>

    <!-- Secondary Toolbar Row (when tools are active) -->
    <div 
      v-if="activeTool && toolOptions[activeTool]"
      class="border-t border-gray-200 bg-gray-50 px-4 py-2"
    >
      <component 
        :is="toolOptions[activeTool].component" 
        v-bind="toolOptions[activeTool].props"
        @update="handleToolUpdate"
      />
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
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
import TextToolbar from './TextToolbar.vue'
import ShapeToolbar from './ShapeToolbar.vue'
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
}

const props = withDefaults(defineProps<Props>(), {
  activeTool: 'select',
  showComments: false,
  commentCount: 0
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
  'tool-update': [tool: string, data: any]
}>()

// Tool-specific options and components
const toolOptions = computed(() => ({
  text: {
    component: TextToolbar,
    props: {}
  },
  shapes: {
    component: ShapeToolbar,
    props: {}
  }
} as Record<string, { component: any; props: any }>))

const updateDesignName = (event: Event) => {
  const target = event.target as HTMLInputElement
  emit('update:designName', target.value)
}

const handleExport = (format: string) => {
  emit('export', format)
}

const handleToolUpdate = (data: any) => {
  emit('tool-update', props.activeTool || 'select', data)
}
</script>

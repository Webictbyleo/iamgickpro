<template>
  <div class="bg-white dark:bg-gray-900 border-b border-gray-200 dark:border-gray-700 flex-shrink-0 shadow-sm">
    <!-- Modern Compact Toolbar -->
    <div class="flex items-center h-12 px-3">
      <!-- Left Section - Design Name & Status -->
      <div class="flex items-center space-x-2 min-w-0 mr-4">
        <input
          :value="designName"
          @input="updateDesignName"
          @blur="$emit('save')"
          class="text-base font-medium bg-transparent border-none outline-none focus:bg-gray-50 dark:focus:bg-gray-800 rounded-md px-2 py-1 min-w-0 max-w-xs truncate"
          placeholder="Untitled Design"
        />
        
        <div 
          :class="[
            'flex items-center h-6 px-2 rounded-full text-xs font-medium transition-colors',
            saveStatus === 'Saving...' ? 'bg-yellow-100 dark:bg-yellow-900 text-yellow-800 dark:text-yellow-200' :
            saveStatus === 'All changes saved' ? 'bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200' :
            'bg-red-100 dark:bg-red-900 text-red-800 dark:text-red-200'
          ]"
          title={saveStatus}
        >
          <div 
            :class="[
              'w-1.5 h-1.5 rounded-full mr-1',
              saveStatus === 'Saving...' ? 'bg-yellow-500 animate-pulse' : 
              saveStatus === 'All changes saved' ? 'bg-green-500' : 
              'bg-red-500'
            ]"
          ></div>
          <span class="truncate max-w-[60px]">{{ saveStatus }}</span>
        </div>
      </div>

      <!-- Center Section - Compact Tools -->
      <div class="flex-1 flex items-center justify-center">
        <div class="flex items-center bg-gray-100 dark:bg-gray-800 rounded-lg p-0.5 shadow-inner">
          <!-- History Controls -->
          <div class="flex border-r border-gray-200 dark:border-gray-700 pr-1 mr-1">
            <ModernButton
              :disabled="!canUndo"
              @click="$emit('undo')"
              variant="ghost"
              size="xs"
              tooltip="Undo (Ctrl+Z)"
              class="rounded-l-md"
            >
              <UndoIcon class="w-3.5 h-3.5" />
            </ModernButton>
            
            <ModernButton
              :disabled="!canRedo"
              @click="$emit('redo')"
              variant="ghost"
              size="xs"
              tooltip="Redo (Ctrl+Y)"
              class="rounded-r-md"
            >
              <RedoIcon class="w-3.5 h-3.5" />
            </ModernButton>
          </div>

          <!-- Quick Create Tools -->
          <div class="flex">
            <ModernButton
              @click="handleToolChange('text')"
              variant="ghost"
              size="xs"
              :class="activeTool === 'text' ? 'bg-blue-100 dark:bg-blue-800 text-blue-600 dark:text-blue-200' : ''"
              tooltip="Text Tool (T)"
            >
              <DocumentTextIcon class="w-3.5 h-3.5" />
            </ModernButton>
            
            <ModernButton
              @click="handleToolChange('shape')"
              variant="ghost"
              size="xs"
              :class="activeTool === 'shape' ? 'bg-blue-100 dark:bg-blue-800 text-blue-600 dark:text-blue-200' : ''"
              tooltip="Shape Tool (R)"
            >
              <Square3Stack3DIcon class="w-3.5 h-3.5" />
            </ModernButton>
            
            <ModernButton
              @click="handleToolChange('image')"
              variant="ghost"
              size="xs"
              :class="activeTool === 'image' ? 'bg-blue-100 dark:bg-blue-800 text-blue-600 dark:text-blue-200' : ''"
              tooltip="Image Tool"
            >
              <PhotoIcon class="w-3.5 h-3.5" />
            </ModernButton>
          </div>
        </div>
      </div>

      <!-- Right Section - Actions -->
      <div class="flex items-center space-x-1">
        <!-- Export Dropdown -->
        <ExportDropdown @export="handleExport" compact />
      </div>
    </div>

    <!-- Secondary Toolbar Row - Show layer-specific toolbar when layer is selected, or tool toolbar when tool is active -->
    <div 
      v-if="selectedLayer || (activeTool && toolOptions[activeTool])"
      class="border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-3 py-1.5 flex items-center"
    >
      <!-- Priority 1: Show layer-specific toolbar if a layer is selected -->
      <TextToolbar
        v-if="selectedLayer && selectedLayer.type === 'text'"
        :fontFamily="selectedLayer.properties?.fontFamily"
        :fontSize="selectedLayer.properties?.fontSize"
        :fontWeight="selectedLayer.properties?.fontWeight"
        :fontStyle="selectedLayer.properties?.fontStyle"
        :textDecoration="selectedLayer.properties?.textDecoration"
        :textAlign="selectedLayer.properties?.textAlign"
        :color="selectedLayer.properties?.fill"
        @update="(props) => $emit('tool-update', 'text', props)"
      />
      
      <ShapeToolbar
        v-else-if="selectedLayer && selectedLayer.type === 'shape'"
        :fill="selectedLayer.properties?.fill"
        :stroke="selectedLayer.properties?.stroke"
        :strokeWidth="selectedLayer.properties?.strokeWidth"
        :cornerRadius="selectedLayer.properties?.cornerRadius"
        @update="(props) => $emit('tool-update', 'shape', props)"
      />
      
      <!-- Priority 2: Show tool-specific toolbar only if no layer is selected -->
      <component 
        v-else-if="!selectedLayer && activeTool && toolOptions[activeTool]"
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

const handleToolChange = (tool: string) => {
  emit('tool-change', tool)
}
</script>

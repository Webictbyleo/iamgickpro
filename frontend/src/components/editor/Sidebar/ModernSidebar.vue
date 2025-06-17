<template>
  <div class="w-16 bg-gray-900 flex flex-col items-center py-4 border-r border-gray-800">
    <!-- Logo/Home -->
    <button
      @click="$router.push('/dashboard')"
      class="w-10 h-10 bg-gradient-to-br from-purple-500 to-blue-600 rounded-xl flex items-center justify-center mb-6 hover:scale-110 transition-transform duration-200 group"
      title="Back to Dashboard"
    >
      <svg class="w-5 h-5 text-white group-hover:rotate-12 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
      </svg>
    </button>

    <!-- Tool Icons -->
    <div class="flex flex-col space-y-2 mb-6">
      <button
        v-for="tool in tools"
        :key="tool.id"
        @click="setActiveTool(tool.id)"
        :class="[
          'w-10 h-10 rounded-lg flex items-center justify-center transition-all duration-200 group relative',
          activeTool === tool.id
            ? 'bg-purple-600 text-white shadow-lg scale-105'
            : 'text-gray-400 hover:text-white hover:bg-gray-800'
        ]"
        :title="tool.tooltip"
      >
        <component :is="tool.icon" class="w-5 h-5" />
        
        <!-- Keyboard shortcut indicator -->
        <div 
          v-if="tool.shortcut"
          class="absolute -right-1 -top-1 w-4 h-4 bg-gray-700 rounded-full flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity duration-200"
        >
          <span class="text-xs text-gray-300 font-mono">{{ tool.shortcut }}</span>
        </div>
      </button>
    </div>

    <!-- Panel Icons -->
    <div class="flex flex-col space-y-2 flex-1">
      <button
        v-for="panel in panels"
        :key="panel.id"
        @click="setActivePanel(panel.id)"
        :class="[
          'w-10 h-10 rounded-lg flex items-center justify-center transition-all duration-200 group relative',
          activePanel === panel.id
            ? 'bg-blue-600 text-white shadow-lg'
            : 'text-gray-400 hover:text-white hover:bg-gray-800'
        ]"
        :title="panel.label"
      >
        <component v-if="panel.icon !== 'TemplateIcon'" :is="panel.icon" class="w-5 h-5" />
        <!-- Custom Template Icon - Layout/Template themed -->
        <svg v-else class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
          <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2zm0 2v3h16V6H4zm0 5v7h4v-7H4zm6 0v3h10v-3H10zm0 5v2h10v-2H10z"/>
        </svg>
        
        <!-- Notification badge for certain panels -->
        <div 
          v-if="panel.badge && (panel.badgeCount ?? 0) > 0"
          class="absolute -right-1 -top-1 w-4 h-4 bg-red-500 rounded-full flex items-center justify-center"
        >
          <span class="text-xs text-white font-semibold">{{ panel.badgeCount }}</span>
        </div>
      </button>
    </div>

    <!-- Quick Actions -->
    <div class="flex flex-col space-y-2 mt-auto">
      <button
        @click="$emit('action', 'export')"
        class="w-10 h-10 rounded-lg flex items-center justify-center text-gray-400 hover:text-blue-400 hover:bg-gray-800 transition-all duration-200"
        title="Export"
      >
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
        </svg>
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import {
  CursorArrowRaysIcon,
  HandRaisedIcon,
  Squares2X2Icon,
  Square3Stack3DIcon as LayersIcon,
  SwatchIcon,
  DocumentDuplicateIcon,
  FilmIcon,
  CloudArrowUpIcon,
  CogIcon
} from '@heroicons/vue/24/outline'

interface Tool {
  id: string
  icon: any
  tooltip: string
  shortcut?: string
}

interface Panel {
  id: string
  icon: any
  label: string
  badge?: boolean
  badgeCount?: number
}

const emit = defineEmits<{
  'tool-change': [toolId: string]
  'panel-change': [panelId: string]
  'action': [action: string]
}>()

const activeTool = ref('select')
const activePanel = ref('elements')

// Tool configuration - keeping only essential editor tools
const tools: Tool[] = [
  { id: 'select', icon: CursorArrowRaysIcon, tooltip: 'Select Tool', shortcut: 'V' },
  { id: 'pan', icon: HandRaisedIcon, tooltip: 'Pan Tool', shortcut: 'H' }
]

// Panel configuration
const panels: Panel[] = [
  { id: 'elements', icon: Squares2X2Icon, label: 'Elements' },
  { id: 'templates', icon: 'TemplateIcon', label: 'Templates' },
  { id: 'uploads', icon: CloudArrowUpIcon, label: 'Uploads' },
  { id: 'media', icon: FilmIcon, label: 'Stock Media' },
  { id: 'layers', icon: LayersIcon, label: 'Layers' },
  { id: 'colors', icon: SwatchIcon, label: 'Colors' }
]

const setActiveTool = (toolId: string) => {
  activeTool.value = toolId
  emit('tool-change', toolId)
}

const setActivePanel = (panelId: string) => {
  activePanel.value = panelId
  emit('panel-change', panelId)
}
</script>

<template>
  <div class="w-24 bg-gray-900 dark:bg-gray-950 flex flex-col items-center py-4 border-r border-gray-700 dark:border-gray-800 h-full overflow-hidden sidebar-container">
    
    <!-- Tool Icons -->
    <div class="flex flex-col space-y-3 mb-6 flex-shrink-0">
      <div
        v-for="tool in tools"
        :key="tool.id"
        class="flex flex-col items-center group"
      >
        <button
          @click="setActiveTool(tool.id)"
          :class="[
            'w-10 h-10 rounded-lg flex items-center justify-center transition-all duration-200 relative',
            activeTool === tool.id
              ? 'bg-gray-100 text-gray-900 shadow-lg scale-105 ring-2 ring-gray-400/50 dark:bg-gray-100 dark:text-gray-900'
              : 'text-gray-400 hover:text-white hover:bg-gray-800 dark:hover:bg-gray-700'
          ]"
          :title="tool.tooltip"
        >
          <component :is="tool.icon" class="w-5 h-5" />
          
          <!-- Keyboard shortcut indicator -->
          <div 
            v-if="tool.shortcut"
            class="absolute -right-1 -top-1 w-4 h-4 bg-gray-700 dark:bg-gray-600 rounded-full flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity duration-200"
          >
            <span class="text-xs text-gray-300 font-mono">{{ tool.shortcut }}</span>
          </div>
        </button>
        
        <!-- Tool Label -->
        <button
          @click="setActiveTool(tool.id)"
          :class="[
            'text-xs font-medium transition-colors duration-200 mt-1 text-center leading-tight w-full truncate px-1 bg-transparent border-none cursor-pointer',
            activeTool === tool.id
              ? 'text-gray-200 dark:text-gray-300'
              : 'text-gray-500 group-hover:text-gray-300'
          ]"
        >
          {{ tool.label }}
        </button>
      </div>
    </div>

    <!-- Panel Icons -->
    <div class="relative flex-1 min-h-0 w-full">
      <!-- Scroll fade indicators -->
      <div 
        v-show="showTopFade"
        class="absolute top-0 left-0 right-0 h-4 bg-gradient-to-b from-gray-900 to-transparent dark:from-gray-950 pointer-events-none z-10 transition-opacity duration-300"
      ></div>
      <div 
        v-show="showBottomFade"
        class="absolute bottom-0 left-0 right-0 h-4 bg-gradient-to-t from-gray-900 to-transparent dark:from-gray-950 pointer-events-none z-10 transition-opacity duration-300"
      ></div>
      
      <!-- Custom Scrollbar Component -->
      <CustomScrollbar
        ref="customScrollbar"
        scroll-area-class="flex flex-col space-y-3 px-2 py-2"
        @scroll="handleScroll"
        :track-width="12"
      >
        <button
          v-for="panel in panels"
          :key="panel.id"
          @click="setActivePanel(panel.id)"
          :class="[
            'flex flex-col items-center group w-full py-2 rounded-lg transition-all duration-200 relative',
            activePanel === panel.id
              ? 'bg-gray-600 text-white shadow-lg ring-2 ring-gray-400/50'
              : 'text-gray-400 hover:text-white hover:bg-gray-800 dark:hover:bg-gray-700'
          ]"
          :title="panel.label"
        >
          <component v-if="panel.icon !== 'TemplateIcon'" :is="panel.icon" class="w-5 h-5" />
          <!-- Custom Template Icon - Layout/Template themed -->
          <svg v-else class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
            <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2zm0 2v3h16V6H4zm0 5v7h4v-7H4zm6 0v3h10v-3H10zm0 5v2h10v-2H10z"/>
          </svg>
          
          <!-- Panel Label -->
          <span 
            :class="[
              'text-xs font-medium transition-colors duration-200 mt-1 text-center leading-tight w-full truncate px-1',
              activePanel === panel.id
                ? 'text-white'
                : 'text-gray-400 group-hover:text-white'
            ]"
          >
            {{ panel.label }}
          </span>
          
          <!-- Notification badge for certain panels -->
          <div 
            v-if="panel.badge && (panel.badgeCount ?? 0) > 0"
            class="absolute -right-1 -top-1 w-4 h-4 bg-danger-500 rounded-full flex items-center justify-center ring-2 ring-gray-900"
          >
            <span class="text-xs text-white font-semibold">{{ panel.badgeCount }}</span>
          </div>
        </button>
      </CustomScrollbar>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue'
import CustomScrollbar from '@/components/ui/CustomScrollbar.vue'
import {
  CursorArrowRaysIcon,
  HandRaisedIcon,
  Squares2X2Icon,
  Square3Stack3DIcon as LayersIcon,
  SwatchIcon,
  PlayIcon,
  FilmIcon,
  CloudArrowUpIcon,
  CogIcon
} from '@heroicons/vue/24/outline'

interface Tool {
  id: string
  icon: any
  label: string
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
}>()

const activeTool = ref('select')
const activePanel = ref('elements')

// Scroll state management for fade indicators
const customScrollbar = ref<InstanceType<typeof CustomScrollbar>>()
const showTopFade = ref(false)
const showBottomFade = ref(false)

// Handle scroll events from custom scrollbar
const handleScroll = ({ scrollTop, scrollHeight, clientHeight }: { scrollTop: number; scrollHeight: number; clientHeight: number }) => {
  // Show top fade when scrolled down
  showTopFade.value = scrollTop > 10
  
  // Show bottom fade when not at bottom
  showBottomFade.value = scrollTop < scrollHeight - clientHeight - 10
}

// Check scroll state on mount and resize
const checkScrollState = () => {
  if (customScrollbar.value) {
    customScrollbar.value.updateScrollbar()
  }
}

onMounted(() => {
  // Initial check after content is rendered
  setTimeout(checkScrollState, 100)
  
  // Listen for window resize to recheck scroll state
  window.addEventListener('resize', checkScrollState)
})

onUnmounted(() => {
  window.removeEventListener('resize', checkScrollState)
})

// Tool configuration - keeping only essential editor tools
const tools: Tool[] = [
  { id: 'select', icon: CursorArrowRaysIcon, label: 'Select', tooltip: 'Select Tool', shortcut: 'V' },
  { id: 'pan', icon: HandRaisedIcon, label: 'Pan', tooltip: 'Pan Tool', shortcut: 'H' }
]

// Panel configuration
const panels: Panel[] = [
  { id: 'elements', icon: Squares2X2Icon, label: 'Elements' },
  { id: 'colors', icon: SwatchIcon, label: 'Colors' },
  { id: 'templates', icon: 'TemplateIcon', label: 'Templates' },
  { id: 'uploads', icon: CloudArrowUpIcon, label: 'Uploads' },
  { id: 'media', icon: FilmIcon, label: 'Media' },
  { id: 'layers', icon: LayersIcon, label: 'Layers' },
  { id: 'animation', icon: PlayIcon, label: 'Animation' },
  { id: 'settings', icon: CogIcon, label: 'Settings', badge: true, badgeCount: 3 }
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

<style scoped>
/* Sidebar container styling */
.sidebar-container {
  position: relative;
  width: 96px; /* Fixed width to prevent any shifts */
  min-width: 96px;
  max-width: 96px;
}

/* Fade indicators styling */
.fade-indicator {
  transition: opacity 0.3s ease-in-out;
  pointer-events: none;
  z-index: 10;
}
</style>

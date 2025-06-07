<template>
  <Transition name="floating-toolbar">
    <div 
      v-if="shouldShow"
      class="relative bg-white/95 dark:bg-gray-900/95 rounded-xl shadow-xl border border-gray-200/50 dark:border-gray-700/50 px-4 py-2.5 flex items-center space-x-3"
      :style="floatingStyle"
    >
      <!-- Context-specific toolbars based on selected layer or active tool -->
      
      <!-- Text Layer Toolbar -->
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
      
      <!-- Shape Layer Toolbar -->
      <ShapeToolbar
        v-else-if="selectedLayer && selectedLayer.type === 'shape'"
        :shapeType="selectedLayer.properties?.shapeType"
        :fill="selectedLayer.properties?.fill"
        :stroke="selectedLayer.properties?.stroke"
        :strokeWidth="selectedLayer.properties?.strokeWidth"
        :borderRadius="selectedLayer.properties?.borderRadius"
        :hasShadow="selectedLayer.properties?.hasShadow"
        @update="(props) => $emit('tool-update', 'shape', props)"
      />
      
      <!-- Image Layer Toolbar -->
      <ImageToolbar
        v-else-if="selectedLayer && selectedLayer.type === 'image'"
        :alt="selectedLayer.properties?.alt"
        :opacity="selectedLayer.properties?.opacity"
        :borderRadius="selectedLayer.properties?.borderRadius"
        :objectPosition="selectedLayer.properties?.objectPosition"
        :preserveAspectRatio="selectedLayer.properties?.preserveAspectRatio"
        :quality="selectedLayer.properties?.quality"
        :brightness="selectedLayer.properties?.brightness"
        :contrast="selectedLayer.properties?.contrast"
        :saturation="selectedLayer.properties?.saturation"
        :blur="selectedLayer.properties?.blur"
        :hue="selectedLayer.properties?.hue"
        :sepia="selectedLayer.properties?.sepia"
        :grayscale="selectedLayer.properties?.grayscale"
        :invert="selectedLayer.properties?.invert"
        :flipX="selectedLayer.properties?.flipX"
        :flipY="selectedLayer.properties?.flipY"
        :shadowEnabled="selectedLayer.properties?.shadow?.enabled"
        @update="(props) => $emit('tool-update', 'image', props)"
        @edit-image="$emit('toggle-panel', 'image-editing', selectedLayer)"
      />
      
      <!-- Tool-specific Toolbar (when no layer selected but tool is active) -->
      <component 
        v-else-if="!selectedLayer && activeTool && toolOptions[activeTool]"
        :is="toolOptions[activeTool].component" 
        v-bind="toolOptions[activeTool].props"
        @update="handleToolUpdate"
      />

      <!-- Quick Actions for any selected layer -->
      <div 
        v-if="selectedLayer" 
        class="flex items-center space-x-1 ml-3 pl-3 border-l border-gray-200/60 dark:border-gray-700/60"
      >
        <ModernButton
          variant="ghost"
          size="xs"
          @click="$emit('duplicate-layer')"
          tooltip="Duplicate (Ctrl+D)"
          class="hover:bg-blue-50 dark:hover:bg-blue-900/20 text-gray-600 hover:text-blue-600 dark:text-gray-300 dark:hover:text-blue-400"
        >
          <DocumentDuplicateIcon class="w-3.5 h-3.5" />
        </ModernButton>
        
        <ModernButton
          variant="ghost"
          size="xs"
          @click="$emit('delete-layer')"
          tooltip="Delete (Del)"
          class="hover:bg-red-50 dark:hover:bg-red-900/20 text-gray-600 hover:text-red-600 dark:text-gray-300 dark:hover:text-red-400"
        >
          <TrashIcon class="w-3.5 h-3.5" />
        </ModernButton>
        
        <ModernButton
          variant="ghost"
          size="xs"
          @click="$emit('lock-layer')"
          :tooltip="selectedLayer.locked ? 'Unlock Layer' : 'Lock Layer'"
          :class="selectedLayer.locked 
            ? 'text-amber-600 hover:text-amber-700 dark:text-amber-400 dark:hover:text-amber-300 bg-amber-50 dark:bg-amber-900/20' 
            : 'hover:bg-gray-50 dark:hover:bg-gray-800 text-gray-600 hover:text-gray-700 dark:text-gray-300 dark:hover:text-gray-200'"
        >
          <component 
            :is="selectedLayer.locked ? LockClosedIcon : LockOpenIcon" 
            class="w-3.5 h-3.5" 
          />
        </ModernButton>
      </div>
    </div>
  </Transition>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import type { Component } from 'vue'
import type { Layer } from '@/types'
import { 
  DocumentDuplicateIcon, 
  TrashIcon, 
  LockClosedIcon, 
  LockOpenIcon 
} from '@heroicons/vue/24/outline'
import ModernButton from '@/components/common/ModernButton.vue'
import TextToolbar from '@/components/editor/Toolbar/TextToolbar.vue'
import ShapeToolbar from '@/components/editor/Toolbar/ShapeToolbar.vue'
import ImageToolbar from '@/components/editor/Toolbar/ImageToolbar.vue'

interface ToolOption {
  component: Component
  props: Record<string, any>
}

interface Props {
  selectedLayer?: Layer | null
  activeTool?: string
  position?: { x: number; y: number }
  show?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  selectedLayer: null,
  activeTool: undefined,
  position: () => ({ x: 0, y: 0 }),
  show: true
})

const emit = defineEmits<{
  'tool-update': [tool: string, props: any]
  'duplicate-layer': []
  'delete-layer': []
  'lock-layer': []
  'toggle-panel': [panelType: string, data?: any]
}>()

// Tool-specific options and components - fixed 'shapes' to 'shape'
const toolOptions = computed((): Record<string, ToolOption> => ({
  text: {
    component: TextToolbar,
    props: {}
  },
  shape: {
    component: ShapeToolbar,
    props: {}
  },
  image: {
    component: ImageToolbar,
    props: {}
  }
}))

// Show the toolbar if there's a selected layer or an active tool (but not select tool)
const shouldShow = computed(() => {
  if (!props.show) return false
  
  return (
    props.selectedLayer || 
    (props.activeTool && props.activeTool !== 'select' && props.activeTool !== 'pan')
  )
})

// Dynamic positioning for the floating toolbar
const floatingStyle = computed(() => {
  // For future use if we need dynamic positioning
  // Currently using relative positioning within the toolbar area
  return {
    maxWidth: '100%'
  }
})

const handleToolUpdate = (data: any) => {
  emit('tool-update', props.activeTool || 'select', data)
}
</script>

<style scoped>
/* Enhanced animations for the floating toolbar */
.floating-toolbar-enter-active {
  transition: all 0.25s cubic-bezier(0.25, 0.46, 0.45, 0.94);
}

.floating-toolbar-leave-active {
  transition: all 0.2s cubic-bezier(0.55, 0.055, 0.675, 0.19);
}

.floating-toolbar-enter-from {
  opacity: 0;
  transform: scale(0.9) translateY(-10px);
}

.floating-toolbar-leave-to {
  opacity: 0;
  transform: scale(0.95) translateY(-5px);
}

.floating-toolbar-enter-to,
.floating-toolbar-leave-from {
  opacity: 1;
  transform: scale(1) translateY(0);
}

/* Enhanced backdrop blur and shadow */
.backdrop-blur-md {
  backdrop-filter: blur(12px);
  -webkit-backdrop-filter: blur(12px);
}

/* Subtle border glow effect */
.border-gray-200\/50 {
  border-color: rgba(229, 231, 235, 0.5);
  box-shadow: 0 0 0 1px rgba(255, 255, 255, 0.1) inset;
}

.dark .border-gray-700\/50 {
  border-color: rgba(55, 65, 81, 0.5);
  box-shadow: 0 0 0 1px rgba(255, 255, 255, 0.05) inset;
}

/* Enhanced shadow */
.shadow-xl {
  box-shadow: 
    0 20px 25px -5px rgba(0, 0, 0, 0.1),
    0 10px 10px -5px rgba(0, 0, 0, 0.04),
    0 0 0 1px rgba(0, 0, 0, 0.05);
}

.dark .shadow-xl {
  box-shadow: 
    0 20px 25px -5px rgba(0, 0, 0, 0.25),
    0 10px 10px -5px rgba(0, 0, 0, 0.1),
    0 0 0 1px rgba(255, 255, 255, 0.05);
}
</style>

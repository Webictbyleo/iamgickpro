<template>
  <div class="h-full bg-gray-100 relative flex flex-col">
    <!-- Fixed Floating Context Toolbar Area - No Layout Shift -->
    <div class="border-b px-4 flex items-center justify-center flex-shrink-0 relative h-14">
      <!-- Floating Context Toolbar -->
      <Transition
        enter-active-class="transition-all duration-200 ease-out"
        enter-from-class="opacity-0 transform translate-y-2 scale-95"
        enter-to-class="opacity-100 transform translate-y-0 scale-100"
        leave-active-class="transition-all duration-150 ease-in"
        leave-from-class="opacity-100 transform translate-y-0 scale-100"
        leave-to-class="opacity-0 transform translate-y-2 scale-95"
      >
        <FloatingContextToolbar
          v-if="showFloatingToolbar && (selectedLayer || (activeTool && activeTool !== 'select'))"
          :selected-layer="selectedLayer"
          :active-tool="activeTool"
          :position="floatingToolbarPosition"
          :show="showFloatingToolbar"
          @tool-update="(tool, data) => $emit('tool-update', tool, data)"
          @duplicate-layer="$emit('duplicate-layer')"
          @delete-layer="$emit('delete-layer')"
          @lock-layer="$emit('lock-layer')"
          @toggle-panel="(panelType, data) => $emit('toggle-panel', panelType, data)"
          @position-preset="(preset) => $emit('position-preset', preset)"
          @update-layer-opacity="(opacity) => $emit('update-layer-opacity', opacity)"
        />
      </Transition>
    </div>
    
    <!-- Canvas Container -->
    <div class="flex-1 overflow-hidden relative min-h-0" @click="handleContainerClick">
      <div class="absolute inset-0">
        <div
          ref="canvasContainer"
          class="w-full h-full relative konva-canvas-container"
          @click.stop
        >
          <!-- Konva Stage will be mounted here -->
         
          
          <!-- Grid overlay (optional) -->
          <div
            v-if="showGrid"
            class="absolute inset-0 pointer-events-none"
            :style="gridStyle"
          />
          
          <!-- Rulers (optional) -->
          <div v-if="showRulers" class="absolute -top-6 -left-6 right-0 bottom-0 pointer-events-none">
            <!-- Horizontal ruler -->
            <div class="absolute top-0 left-6 right-0 h-6 bg-gray-100 border-b">
              <div class="relative h-full">
                <div
                  v-for="mark in horizontalMarks"
                  :key="mark.position"
                  class="absolute top-0 border-l border-gray-400"
                  :style="{ left: mark.position + 'px', height: mark.size + 'px' }"
                />
              </div>
            </div>
            
            <!-- Vertical ruler -->
            <div class="absolute top-6 left-0 bottom-0 w-6 bg-gray-100 border-r">
              <div class="relative w-full h-full">
                <div
                  v-for="mark in verticalMarks"
                  :key="mark.position"
                  class="absolute left-0 border-t border-gray-400"
                  :style="{ top: mark.position + 'px', width: mark.size + 'px' }"
                />
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, nextTick, onUnmounted } from 'vue'
import type { Layer } from '@/types'
import FloatingContextToolbar from './FloatingContextToolbar.vue'

interface Props {
  width: number
  height: number
  zoomLevel?: number
  showGrid?: boolean
  showRulers?: boolean
  selectedLayer?: Layer | null
  activeTool?: string
  showFloatingToolbar?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  zoomLevel: 1,
  showGrid: false,
  showRulers: false,
  selectedLayer: null,
  activeTool: undefined,
  showFloatingToolbar: true
})

const emit = defineEmits<{
  'tool-update': [tool: string, data: any]
  'duplicate-layer': []
  'delete-layer': []
  'lock-layer': []
  'toggle-panel': [panelType: string, data?: any]
  'position-preset': [preset: string]
  'update-layer-opacity': [opacity: number]
  'layer-context-menu': [event: MouseEvent, layer?: Layer | null]
  'toggle-visibility': [layerId: number]
  'clear-selection': []
}>()

const canvasContainer = ref<HTMLElement>()

// Expose the canvas container for parent component access
defineExpose({
  canvasContainer
})

// Floating toolbar position
const floatingToolbarPosition = computed(() => ({
  x: 0,
  y: 0
}))

// Computed styles for grid overlay
const gridStyle = computed(() => {
  if (!props.showGrid) return {}
  
  const gridSize = 20 * props.zoomLevel
  return {
    backgroundImage: `
      linear-gradient(to right, rgba(0,0,0,0.1) 1px, transparent 1px),
      linear-gradient(to bottom, rgba(0,0,0,0.1) 1px, transparent 1px)
    `,
    backgroundSize: `${gridSize}px ${gridSize}px`
  }
})

// Ruler marks
const horizontalMarks = computed(() => {
  if (!props.showRulers) return []
  
  const marks = []
  const step = 50 * props.zoomLevel
  const majorStep = 100 * props.zoomLevel
  
  for (let i = 0; i <= props.width * props.zoomLevel; i += step) {
    marks.push({
      position: i,
      size: i % majorStep === 0 ? 24 : 12
    })
  }
  
  return marks
})

const verticalMarks = computed(() => {
  if (!props.showRulers) return []
  
  const marks = []
  const step = 50 * props.zoomLevel
  const majorStep = 100 * props.zoomLevel
  
  for (let i = 0; i <= props.height * props.zoomLevel; i += step) {
    marks.push({
      position: i,
      size: i % majorStep === 0 ? 24 : 12
    })
  }
  
  return marks
})

// Initialize canvas
onMounted(async () => {
  await nextTick()
  if (canvasContainer.value) {
    console.log('DesignCanvas: Container ready', {
      container: canvasContainer.value,
      containerDimensions: `${canvasContainer.value.offsetWidth}x${canvasContainer.value.offsetHeight}`,
      containerClasses: canvasContainer.value.className,
      containerStyle: canvasContainer.value.style.cssText,
      containerChildren: canvasContainer.value.children.length
    })
    // Container is now accessible via defineExpose, no need to emit
  } else {
    console.error('DesignCanvas: Container not found!')
  }

  // Add event listeners
  document.addEventListener('editor:context-menu', handleEditorContextMenu as EventListener)
})

// Event listeners
const handleContainerClick = (event: MouseEvent) => {
  // Clear selection when clicking on the canvas container (outside the canvas)
  // This provides an easy way to deselect layers
  emit('clear-selection')
}

const handleEditorContextMenu = (event: CustomEvent) => {
  const { layer, position, event: originalEvent } = event.detail
  console.log('🎯 DesignCanvas: Received context menu event', { layer, position })
  
  // Forward the context menu to the parent EditorLayout
  // Don't show our own context menu, let the parent handle it
  emit('layer-context-menu', originalEvent, layer)
}

onUnmounted(() => {
  document.removeEventListener('editor:context-menu', handleEditorContextMenu as EventListener)
})
</script>

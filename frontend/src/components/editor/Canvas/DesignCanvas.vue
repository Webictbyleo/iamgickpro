<template>
  <div class="flex-1 bg-gray-100 relative flex flex-col min-w-0">
    <!-- Canvas Toolbar -->
    <div class="bg-white border-b px-4 py-2 flex items-center justify-between flex-shrink-0">
      <div class="flex items-center space-x-4">
        <div class="flex items-center space-x-2">
          <BaseButton
            variant="ghost"
            size="xs"
            @click="zoomOut"
            title="Zoom Out"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM13 10H7" />
            </svg>
          </BaseButton>
          
          <span class="text-sm text-gray-600 min-w-16 text-center">{{ Math.round(zoomLevel * 100) }}%</span>
          
          <BaseButton
            variant="ghost"
            size="xs"
            @click="zoomIn"
            title="Zoom In"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7" />
            </svg>
          </BaseButton>
        </div>
        
        <div class="w-px h-4 bg-gray-300"></div>
        
        <BaseButton
          variant="ghost"
          size="xs"
          @click="zoomToFit"
        >
          Fit
        </BaseButton>
        
        <BaseButton
          variant="ghost"
          size="xs"
          @click="resetZoom"
        >
          100%
        </BaseButton>
      </div>
      
      <div class="flex items-center space-x-2 text-sm text-gray-600">
        <span>Canvas: {{ width }}×{{ height }}px</span>
      </div>
    </div>
    
    <!-- Canvas Container -->
    <div class="flex-1 overflow-hidden relative">
      <div class="absolute inset-0 flex items-center justify-center p-8">
        <div
          ref="canvasContainer"
          class="bg-white shadow-lg border relative"
          :style="canvasStyle"
        >
          <!-- Konva Stage will be mounted here -->
          
          <!-- Canvas Background -->
          <div
            class="absolute inset-0"
            :style="{ backgroundColor }"
          />
          
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
import { ref, computed, onMounted, watch, nextTick } from 'vue'
import BaseButton from '@/components/common/BaseButton.vue'

interface Props {
  width: number
  height: number
  zoomLevel: number
  backgroundColor: string
  showGrid?: boolean
  showRulers?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  showGrid: false,
  showRulers: false
})

const emit = defineEmits<{
  canvasReady: [container: HTMLElement]
  zoomChanged: [zoom: number]
}>()

const canvasContainer = ref<HTMLElement>()
const currentZoom = ref(props.zoomLevel)

// Computed styles
const canvasStyle = computed(() => ({
  width: props.width * currentZoom.value + 'px',
  height: props.height * currentZoom.value + 'px',
  minWidth: props.width * currentZoom.value + 'px',
  minHeight: props.height * currentZoom.value + 'px'
}))

const gridStyle = computed(() => {
  if (!props.showGrid) return {}
  
  const gridSize = 20 * currentZoom.value
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
  const step = 50 * currentZoom.value
  const majorStep = 100 * currentZoom.value
  
  for (let i = 0; i <= props.width * currentZoom.value; i += step) {
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
  const step = 50 * currentZoom.value
  const majorStep = 100 * currentZoom.value
  
  for (let i = 0; i <= props.height * currentZoom.value; i += step) {
    marks.push({
      position: i,
      size: i % majorStep === 0 ? 24 : 12
    })
  }
  
  return marks
})

// Zoom controls
const zoomIn = () => {
  const newZoom = Math.min(currentZoom.value * 1.2, 5)
  updateZoom(newZoom)
}

const zoomOut = () => {
  const newZoom = Math.max(currentZoom.value / 1.2, 0.1)
  updateZoom(newZoom)
}

const zoomToFit = () => {
  if (!canvasContainer.value) return
  
  const container = canvasContainer.value.parentElement
  if (!container) return
  
  const containerRect = container.getBoundingClientRect()
  const availableWidth = containerRect.width - 64 // padding
  const availableHeight = containerRect.height - 64 // padding
  
  const scaleX = availableWidth / props.width
  const scaleY = availableHeight / props.height
  const newZoom = Math.min(scaleX, scaleY, 1)
  
  updateZoom(newZoom)
}

const resetZoom = () => {
  updateZoom(1)
}

const updateZoom = (zoom: number) => {
  currentZoom.value = zoom
  emit('zoomChanged', zoom)
}

// Initialize canvas
onMounted(async () => {
  await nextTick()
  if (canvasContainer.value) {
    emit('canvasReady', canvasContainer.value)
  }
})

// Watch for external zoom changes
watch(() => props.zoomLevel, (newZoom) => {
  currentZoom.value = newZoom
})
</script>

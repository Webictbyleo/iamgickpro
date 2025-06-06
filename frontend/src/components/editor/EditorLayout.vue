<template>
  <div class="min-h-screen bg-gray-900 flex flex-col">
    <!-- Top Toolbar -->
    <MainToolbar 
      v-model:design-name="designName"
      :save-status="saveStatus"
      :can-undo="canUndo"
      :can-redo="canRedo"
      @save="handleSave"
      @export="handleExport"
      @preview="handlePreview"
      @share="handleShare"
      @undo="handleUndo"
      @redo="handleRedo"
    />

    <!-- Main Editor Layout -->
    <div class="flex-1 flex min-h-0">
      <!-- Left Sidebar -->
      <div class="w-80 bg-white border-r flex flex-col">
        <!-- Sidebar Tabs -->
        <div class="flex border-b">
          <button
            v-for="tab in leftTabs"
            :key="tab.id"
            @click="activeLeftTab = tab.id as 'elements' | 'layers' | 'media' | 'animation'"
            :class="[
              'flex-1 px-4 py-3 text-sm font-medium border-b-2 transition-colors',
              activeLeftTab === tab.id
                ? 'border-blue-500 text-blue-600 bg-blue-50'
                : 'border-transparent text-gray-500 hover:text-gray-700 hover:bg-gray-50'
            ]"
          >
            {{ tab.label }}
          </button>
        </div>
        
        <!-- Sidebar Content -->
        <div class="flex-1 overflow-y-auto">
          <!-- Elements Panel -->
          <ElementsPanel v-if="activeLeftTab === 'elements'" @add-element="handleAddElement" />
          
          <!-- Layers Panel -->
          <LayerPanel 
            v-if="activeLeftTab === 'layers'" 
            :layers="layers"
            :selected-layers="selectedLayers"
            @select-layer="handleSelectLayer"
            @duplicate-layer="handleDuplicateLayer"
            @delete-layer="handleDeleteLayer"
            @toggle-visibility="handleToggleVisibility"
            @toggle-lock="handleToggleLock"
            @reorder-layers="handleReorderLayers"
          />

          <!-- Media Panel -->
          <MediaPanel v-if="activeLeftTab === 'media'" @add-media="handleAddMedia" />

          <!-- Animation Panel -->
          <AnimationPanel 
            v-if="activeLeftTab === 'animation'" 
            :selected-layers="selectedLayers"
            @update-animation="handleUpdateAnimation"
            @preview-animation="handlePreviewAnimation"
            @stop-animation="handleStopAnimation"
          />
        </div>
      </div>

      <!-- Canvas Area -->
      <div class="flex-1 bg-gray-100 relative flex flex-col min-w-0">
        <DesignCanvas 
          :width="canvasWidth"
          :height="canvasHeight"
          :zoom-level="zoomLevel"
          :background-color="backgroundColor"
          @canvas-ready="handleCanvasReady"
          @zoom-changed="handleZoomChanged"
        />
      </div>

      <!-- Right Sidebar -->
      <div class="w-80 bg-white border-l flex flex-col">
        <!-- Sidebar Tabs -->
        <div class="flex border-b">
          <button
            v-for="tab in rightTabs"
            :key="tab.id"
            @click="activeRightTab = tab.id as 'design' | 'properties'"
            :class="[
              'flex-1 px-4 py-3 text-sm font-medium border-b-2 transition-colors',
              activeRightTab === tab.id
                ? 'border-blue-500 text-blue-600 bg-blue-50'
                : 'border-transparent text-gray-500 hover:text-gray-700 hover:bg-gray-50'
            ]"
          >
            {{ tab.label }}
          </button>
        </div>
        
        <!-- Properties Panel -->
        <div class="flex-1 overflow-y-auto">
          <DesignPropertiesPanel 
            v-if="activeRightTab === 'design'"
            v-model:canvas-width="canvasWidth"
            v-model:canvas-height="canvasHeight"
            v-model:background-color="backgroundColor"
          />
          
          <LayerPropertiesPanel 
            v-if="activeRightTab === 'properties'"
            :selected-layers="selectedLayers"
            @update-properties="handleUpdateProperties"
          />
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, provide } from 'vue'
import { useDesignStore } from '@/stores/design'
import type { Layer, LayerType } from '@/types'

// Component imports
import MainToolbar from './Toolbar/MainToolbar.vue'
import ElementsPanel from './Panels/ElementsPanel.vue'
import LayerPanel from './Panels/LayerPanel.vue'
import MediaPanel from './Panels/MediaPanel.vue'
import AnimationPanel from './Panels/AnimationPanel.vue'
import DesignCanvas from './Canvas/DesignCanvas.vue'
import DesignPropertiesPanel from './Panels/DesignPropertiesPanel.vue'
import LayerPropertiesPanel from './Panels/LayerPropertiesPanel.vue'

// Composables
import { useDesignEditor } from '@/composables/useDesignEditor'
import { useLayerManagement } from '@/composables/useLayerManagement'
import { useKeyboardShortcuts } from '@/composables/useKeyboardShortcuts'

const designStore = useDesignStore()

// Use composables
const {
  editorSDK,
  canUndo,
  canRedo,
  hasUnsavedChanges,
  initializeEditor,
  saveDesign,
  exportDesign,
  undo,
  redo
} = useDesignEditor()

const {
  selectedLayers,
  addElement,
  selectLayer,
  duplicateLayer,
  deleteLayer,
  toggleLayerVisibility,
  toggleLayerLock,
  reorderLayers,
  updateLayerProperties
} = useLayerManagement(editorSDK)

// Set up keyboard shortcuts
useKeyboardShortcuts({
  onSave: saveDesign,
  onUndo: undo,
  onRedo: redo,
  onDuplicate: () => duplicateLayer(selectedLayers.value[0]?.id),
  onDelete: () => deleteLayer(selectedLayers.value[0]?.id),
  onSelectAll: () => {
    // Select all layers logic
  }
})

// UI State
const activeLeftTab = ref<'elements' | 'layers' | 'media' | 'animation'>('elements')
const activeRightTab = ref<'design' | 'properties'>('design')
const zoomLevel = ref(1)

// Sidebar tabs configuration
const leftTabs = [
  { id: 'elements' as const, label: 'Elements' },
  { id: 'layers' as const, label: 'Layers' },
  { id: 'media' as const, label: 'Media' },
  { id: 'animation' as const, label: 'Animation' }
]

const rightTabs = [
  { id: 'design' as const, label: 'Design' },
  { id: 'properties' as const, label: 'Properties' }
]

// Computed properties
const designName = computed({
  get: () => designStore.currentDesign?.name || 'Untitled Design',
  set: (value: string) => {
    if (designStore.currentDesign) {
      designStore.updateDesignName(value)
    }
  }
})

const saveStatus = computed(() => {
  if (designStore.isLoading) return 'Saving...'
  if (hasUnsavedChanges.value) return 'Unsaved changes'
  return 'All changes saved'
})

const canvasWidth = computed({
  get: () => designStore.currentDesign?.width || 800,
  set: (value: number) => {
    if (designStore.currentDesign) {
      designStore.updateCanvasSize(value, canvasHeight.value)
      editorSDK.value?.canvas.setSize(value, canvasHeight.value)
    }
  }
})

const canvasHeight = computed({
  get: () => designStore.currentDesign?.height || 600,
  set: (value: number) => {
    if (designStore.currentDesign) {
      designStore.updateCanvasSize(canvasWidth.value, value)
      editorSDK.value?.canvas.setSize(canvasWidth.value, value)
    }
  }
})

const backgroundColor = computed({
  get: () => designStore.currentDesign?.designData.canvas.backgroundColor || '#ffffff',
  set: (value: string) => {
    if (designStore.currentDesign) {
      designStore.currentDesign.designData.canvas.backgroundColor = value
      editorSDK.value?.canvas.setBackgroundColor(value)
    }
  }
})

const layers = computed(() => designStore.currentDesign?.designData.layers || [])

// Event handlers
const handleCanvasReady = (container: HTMLElement) => {
  initializeEditor(container)
}

const handleAddElement = (type: LayerType, properties: any) => {
  addElement(type, properties)
}

const handleSelectLayer = (layerId: string, event: MouseEvent) => {
  selectLayer(layerId, event)
}

const handleDuplicateLayer = (layerId: string) => {
  duplicateLayer(layerId)
}

const handleDeleteLayer = (layerId: string) => {
  deleteLayer(layerId)
}

const handleToggleVisibility = (layerId: string) => {
  toggleLayerVisibility(layerId)
}

const handleToggleLock = (layerId: string) => {
  toggleLayerLock(layerId)
}

const handleReorderLayers = (layerIds: string[]) => {
  reorderLayers(layerIds)
}

const handleUpdateProperties = (layerId: string, properties: Partial<Layer>) => {
  updateLayerProperties(layerId, properties)
}

const handleZoomChanged = (zoom: number) => {
  zoomLevel.value = zoom
}

const handleSave = () => {
  saveDesign()
}

const handleExport = (format: string) => {
  exportDesign(format)
}

const handlePreview = () => {
  // Preview logic
  console.log('Preview design')
}

const handleShare = () => {
  // Share logic
  console.log('Share design')
}

const handleUndo = () => {
  undo()
}

const handleRedo = () => {
  redo()
}

const handleAddMedia = (mediaData: any) => {
  // Add media to canvas
  addElement('image', mediaData)
}

const handleUpdateAnimation = (layerId: string, animation: any) => {
  // Update layer animation
  console.log('Update animation for layer:', layerId, animation)
  // TODO: Implement animation update logic
}

const handlePreviewAnimation = (layerId: string) => {
  // Preview layer animation
  console.log('Preview animation for layer:', layerId)
  // TODO: Implement animation preview logic
}

const handleStopAnimation = (layerId: string) => {
  // Stop layer animation
  console.log('Stop animation for layer:', layerId)
  // TODO: Implement animation stop logic
}

// Provide editor context to child components
provide('editorSDK', editorSDK)
provide('designStore', designStore)
</script>

<template>
  <div class="h-screen bg-gray-50 dark:bg-gray-900 flex overflow-hidden">
    <!-- Modern Left Sidebar -->
    <ModernSidebar 
      :active-tool="activeTool"
      :active-panel="activePanel"
      @tool-change="handleToolChange"
      @panel-change="handleSidebarPanelChange"
      @action="handleSidebarAction"
    />

    <!-- Main Content Area -->
    <div class="flex-1 flex flex-col min-h-0 h-full">
      <!-- Modern Toolbar -->
      <ModernToolbar 
        v-model:design-name="designName"
        :save-status="saveStatus"
        :can-undo="canUndo"
        :can-redo="canRedo"
        :active-tool="activeTool || undefined"
        :selected-layer="selectedLayer"
        @save="handleSave"
        @export="handleExport"
        @undo="handleUndo"
        @redo="handleRedo"
        @tool-change="handleToolChange"
      />

      <!-- Canvas Debug Test (temporary) - DISABLED -->
      <!-- <CanvasDebugTest /> -->

      <!-- Canvas Container -->
      <div class="flex-1 flex min-h-0 h-full">
        <!-- Left Panel (Elements, Templates, Media, Contextual) -->
        <div 
          v-if="activePanel && availablePanels.includes(activePanel) || activePanelModal"
          class="w-80 bg-white dark:bg-gray-900 border-r border-gray-200 dark:border-gray-700 flex flex-col h-full"
        >
          <!-- Panel Header -->
          <div class="p-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between flex-shrink-0">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
              {{ getPanelTitle(activePanel || activePanelModal || '') }}
            </h2>
            <ModernButton
              variant="ghost"
              size="sm"
              @click="handleCancelPanelModal"
            >
              <XMarkIcon class="w-5 h-5" />
            </ModernButton>
          </div>

          <!-- Panel Content -->
          <div class="flex-1 overflow-y-auto">
            <!-- Regular Panels -->
            <ElementsPanel 
              v-if="activePanel === 'elements'" 
              @add-element="handleAddElement" 
              @add-template="handleAddTemplate"
            />

            <TemplatesPanel 
              v-if="activePanel === 'templates'" 
              @use-template="handleUseTemplate"
            />
            
            <MediaPanel 
              v-if="activePanel === 'media'" 
              @add-media="handleAddMedia" 
            />

            <!-- Animation Panel -->
            <AnimationPanel 
              v-if="activePanel === 'animation'"
              :selected-layers="selectedLayers"
              @update-animation="handleUpdateAnimation"
              @preview-animation="handlePreviewAnimation"
              @stop-animation="handleStopAnimation"
            />

            <!-- Colors Panel -->
            <ColorsPanel 
              v-if="activePanel === 'colors'"
              @apply-color="handleApplyColor"
              @apply-gradient="handleApplyGradient"
            />

            <!-- Layers Panel -->
            <LayerPanel 
              v-if="activePanel === 'layers'"
              :layers="layers"
              :selected-layers="selectedLayers"
              @select-layer="handleSelectLayer"
              @duplicate-layer="handleDuplicateLayer"
              @delete-layer="handleDeleteLayer"
              @toggle-visibility="handleToggleVisibility"
              @toggle-lock="handleToggleLock"
              @reorder-layers="handleReorderLayers"
            />

            <!-- Contextual Panels -->
            <ImageEditingPanel
              v-if="activePanelModal === 'image-editing' && selectedLayer && selectedLayer.type === 'image'"
              :properties="selectedLayer.properties as ImageLayerProperties"
              @apply="handleApplyImageEdit"
            />
          </div>
        </div>

        <!-- Canvas Area -->
        <div class="flex-1 bg-gray-100 dark:bg-gray-800 relative flex flex-col min-w-0 h-full">

          <!-- Canvas -->
          <div class="flex-1 relative min-h-0">
            <DesignCanvas 
              :width="canvasWidth"
              :height="canvasHeight"
              :zoom-level="zoomLevel"
              :background-color="backgroundColor"
              :selected-layer="selectedLayer"
              :active-tool="activeTool || undefined"
              :show-floating-toolbar="true"
              @canvas-ready="handleCanvasReady"
              @zoom-changed="handleZoomChanged"
              @tool-update="handleToolUpdate"
              @duplicate-layer="handleDuplicateSelectedLayer"
              @delete-layer="handleDeleteSelectedLayer"
              @lock-layer="handleLockSelectedLayer"
              @toggle-panel="handleTogglePanel"
            />

            <!-- Zoom Controls -->
            <ZoomControls
              :zoom="zoomLevel"
              :canvas-width="canvasWidth"
              :canvas-height="canvasHeight"
              :container-width="canvasContainerWidth"
              :container-height="canvasContainerHeight"
              @update:zoom="handleZoomChanged"
              @pan-to-center="handlePanToCenter"
            />
          </div>
        </div>


      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, provide, watch, onMounted } from 'vue'
import { useDesignStore } from '@/stores/design'
import type { Layer, LayerType, ImageLayerProperties } from '@/types'

// Modern Component imports
import ModernSidebar from './Sidebar/ModernSidebar.vue'
import ModernToolbar from './Toolbar/ModernToolbar.vue'
import ZoomControls from './Canvas/ZoomControls.vue'
import ModernButton from '@/components/common/ModernButton.vue'

// Legacy component imports (still used)
import ElementsPanel from './Panels/ElementsPanel.vue'
import TemplatesPanel from './Panels/TemplatesPanel.vue'
import LayerPanel from './Panels/LayerPanel.vue'
import MediaPanel from './Panels/MediaPanel.vue'
import AnimationPanel from './Panels/AnimationPanel.vue'
import ColorsPanel from './Panels/ColorsPanel.vue'
import DesignCanvas from './Canvas/DesignCanvas.vue'
import ImageEditingPanel from './Panels/ImageEditingPanel.vue'

// Icons
import { XMarkIcon } from '@heroicons/vue/24/outline'

// Composables
import { useDesignEditor } from '@/composables/useDesignEditor'
import { useLayerManagement } from '@/composables/useLayerManagement'
import { useKeyboardShortcuts } from '@/composables/useKeyboardShortcuts'
import { usePanelManagement } from '@/composables/usePanelManagement'

const designStore = useDesignStore()

// Create helper methods for design updates
const updateDesignBackground = (backgroundColor: string) => {
  if (designStore.currentDesign) {
    designStore.currentDesign.designData.canvas.backgroundColor = backgroundColor
    designStore.currentDesign.updatedAt = new Date().toISOString()
  }
}

const updateDesignSize = (width: number, height: number) => {
  if (designStore.currentDesign) {
    designStore.updateCanvasSize(width, height)
  }
}

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

// Initialize smart panel management
const {
  activePanel,
  activePanelModal,
  currentPanelState,
  leftPanels: availablePanels,
  autoCloseContextual,
  getPanelTitle,
  isLeftPanel,
  getContextualPanelForLayer,
  handlePanelChange,
  handleToggleContextualPanel,
  handleLayerSelectionChange,
  closeAllPanels,
  closeContextualPanels,
  isPanelActive,
  cachePanelData,
  getCachedPanelData,
  cleanCache
} = usePanelManagement()

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

// Initialize with default panel
onMounted(() => {
  handlePanelChange('elements')
})

// Modern UI State
const activeTool = ref<'select' | 'text' | 'shape' | 'image' | 'pan' | null>('select')
const zoomLevel = ref(1)
const canvasContainerWidth = ref(1000)
const canvasContainerHeight = ref(700)

// Panel configuration - include all panels that should show in left sidebar
const leftPanels = ['elements', 'templates', 'media', 'layers', 'animation', 'colors']

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
    updateDesignSize(value, canvasHeight.value)
    editorSDK.value?.canvas.setSize(value, canvasHeight.value)
  }
})

const canvasHeight = computed({
  get: () => designStore.currentDesign?.height || 600,
  set: (value: number) => {
    updateDesignSize(canvasWidth.value, value)
    editorSDK.value?.canvas.setSize(canvasWidth.value, value)
  }
})

const backgroundColor = computed({
  get: () => designStore.currentDesign?.designData?.canvas?.backgroundColor || '#ffffff',
  set: (value: string) => {
    updateDesignBackground(value)
    editorSDK.value?.canvas.setBackgroundColor(value)
  }
})

const layers = computed(() => designStore.currentDesign?.designData?.layers || [])
const selectedLayer = computed(() => selectedLayers.value[0] || null)

// Watch for layer selection changes and auto-close contextual panels
watch(selectedLayer, (newLayer, oldLayer) => {
  if (newLayer) {
    // When a layer is selected, automatically switch to select tool
    // This ensures the layer-specific toolbar will show instead of tool toolbar
    activeTool.value = 'select'
    
    // Handle layer selection change through panel management
    handleLayerSelectionChange(selectedLayer.value ? [selectedLayer.value] : [])
  } else {
    // Auto-close contextual panel when no layer is selected
    closeContextualPanels()
  }
})

// Event handlers
const handleToolChange = (tool: string) => {
  activeTool.value = tool as any
  
  // When switching tools, clear any active panel if needed
  // This provides more canvas space for the user
}

const handleSidebarPanelChange = (panel: string) => {
  handlePanelChange(panel)
}

const handleSidebarAction = (action: string) => {
  switch (action) {
    case 'save':
      handleSave()
      break
    case 'export':
      handleExport('png')
      break
    case 'undo':
      handleUndo()
      break
    case 'redo':
      handleRedo()
      break
  }
}

const handleCanvasReady = (container: HTMLElement) => {
  initializeEditor(container)
}

const handleAddElement = (type: LayerType, properties: any) => {
  addElement(type, properties)
  activeTool.value = 'select' // Switch to select tool after adding element
}

const handleAddTemplate = (template: any) => {
  console.log('Adding template:', template)
  // TODO: Implement template handling logic
}

const handleUseTemplate = async (template: any) => {
  try {
    // Create a new design with template dimensions
    const newDesign = designStore.createNewDesign(
      template.width || 800,
      template.height || 600
    )

    // Copy template data if available
    if (template.designData) {
      newDesign.designData = { ...template.designData }
    }

    newDesign.name = `${template.name} Copy`
    newDesign.title = `${template.name} Copy`

    // Save the design
    const result = await designStore.saveDesign(newDesign)

    if (result.success && editorSDK.value) {
      // Load the template data into the editor
      await editorSDK.value.loadDesign(newDesign)
      closeAllPanels() // Close panels using new panel management
    } else {
      console.error('Failed to create design from template:', result.error)
    }
  } catch (error) {
    console.error('Template application failed:', error)
  }
}

const handleSelectLayer = (layerId: string, event: MouseEvent) => {
  selectLayer(layerId, event)
  // Layer selection will automatically show context toolbar
}

const handleDuplicateLayer = (layerId: string) => {
  duplicateLayer(layerId)
}

const handleDeleteLayer = (layerId: string) => {
  deleteLayer(layerId)
}

// Floating Context Toolbar Event Handlers
const handleDuplicateSelectedLayer = () => {
  if (selectedLayer.value) {
    handleDuplicateLayer(selectedLayer.value.id)
  }
}

const handleDeleteSelectedLayer = () => {
  if (selectedLayer.value) {
    handleDeleteLayer(selectedLayer.value.id)
  }
}

const handleLockSelectedLayer = () => {
  if (selectedLayer.value) {
    handleToggleLock(selectedLayer.value.id)
  }
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

const handleUpdateProperty = (property: string, value: any) => {
  if (selectedLayer.value) {
    const updates = { [property]: value }
    updateLayerProperties(selectedLayer.value.id, updates)
  }
}

const handleZoomChanged = (zoom: number) => {
  zoomLevel.value = zoom
}

const handlePanToCenter = () => {
  // Use centerView instead of panToCenter
  editorSDK.value?.canvas.centerView()
}

const handleSave = () => {
  saveDesign()
}

const handleExport = (format: string) => {
  exportDesign(format)
}

const handleUndo = () => {
  undo()
}

const handleRedo = () => {
  redo()
}

const handleAddMedia = (mediaData: any) => {
  addElement('image', mediaData)
}

// Animation Panel Event Handlers
const handleUpdateAnimation = (animationData: any) => {
  if (selectedLayer.value && editorSDK.value) {
    // Use the available animation API methods
    const { layerId, time, properties } = animationData
    editorSDK.value.animation.addKeyframe(layerId || selectedLayer.value.id, time || 0, properties || {})
  }
}

const handlePreviewAnimation = (animationData: any) => {
  if (editorSDK.value) {
    // Use the available animation API methods
    editorSDK.value.animation.play()
  }
}

const handleStopAnimation = () => {
  if (editorSDK.value) {
    editorSDK.value.animation.stop()
  }
}

// Colors Panel Event Handlers
const handleApplyColor = (colorData: any) => {
  if (selectedLayer.value) {
    if (selectedLayer.value.type === 'shape') {
      const updates: Partial<Layer> = {
        properties: {
          ...selectedLayer.value.properties,
          fill: {
            type: 'solid' as const,
            color: colorData.color,
            opacity: colorData.opacity || 1
          }
        }
      }
      updateLayerProperties(selectedLayer.value.id, updates)
    } else if (selectedLayer.value.type === 'text') {
      const updates: Partial<Layer> = {
        properties: {
          ...selectedLayer.value.properties,
          color: colorData.color
        }
      }
      updateLayerProperties(selectedLayer.value.id, updates)
    }
  }
}

const handleApplyGradient = (gradientData: any) => {
  if (selectedLayer.value && selectedLayer.value.type === 'shape') {
    const updates: Partial<Layer> = {
      properties: {
        ...selectedLayer.value.properties,
        fill: {
          type: gradientData.type || 'linear' as const,
          colors: gradientData.colors || [],
          angle: gradientData.angle || 0
        }
      }
    }
    updateLayerProperties(selectedLayer.value.id, updates)
  }
}



const handleToolUpdate = (toolType: string, properties: any) => {
  if (selectedLayer.value) {
    if (toolType === 'text' && selectedLayer.value.type === 'text') {
      // Map the properties from TextToolbar to layer property paths
      Object.entries(properties).forEach(([key, value]) => {
        let updatePath = key;
        
        // Map toolbar property names to layer property paths
        switch (key) {
          case 'fontFamily':
            updatePath = 'properties.fontFamily';
            break;
          case 'fontSize':
            updatePath = 'properties.fontSize';
            break;
          case 'fontWeight':
            updatePath = 'properties.fontWeight';
            break;
          case 'fontStyle':
            updatePath = 'properties.fontStyle';
            break;
          case 'textDecoration':
            updatePath = 'properties.textDecoration';
            break;
          case 'textAlign':
            updatePath = 'properties.textAlign';
            break;
          case 'color':
            updatePath = 'properties.fill';
            break;
          default:
            updatePath = `properties.${key}`;
        }
        
        // Update the design store
        designStore.updateLayerProperty(selectedLayer.value.id, updatePath, value);
      });
    } else if (toolType === 'shape' && selectedLayer.value.type === 'shape') {
      // Similar mapping for shape properties
      Object.entries(properties).forEach(([key, value]) => {
        designStore.updateLayerProperty(selectedLayer.value.id, `properties.${key}`, value);
      });
    } else if (toolType === 'image' && selectedLayer.value.type === 'image') {
      // Handle image properties
      Object.entries(properties).forEach(([key, value]) => {
        designStore.updateLayerProperty(selectedLayer.value.id, `properties.${key}`, value);
      });
    }
  }
}

// Generic Panel Modal Handlers
const handleTogglePanel = (panelType: string, data?: any) => {
  // Handle both regular and contextual panels through the smart panel management
  if (isLeftPanel(panelType)) {
    handlePanelChange(panelType)
  } else {
    handleToggleContextualPanel(panelType, data)
  }
}

const handleCancelPanelModal = () => {
  closeAllPanels()
}

const handleApplyImageEdit = (updatedProperties: any) => {
  if (selectedLayer.value && selectedLayer.value.type === 'image') {
    updateLayerProperties(selectedLayer.value.id, {
      properties: {
        ...selectedLayer.value.properties,
        ...updatedProperties
      }
    })
  }
  closeContextualPanels()
}

// Provide editor context to child components
provide('editorSDK', editorSDK)
provide('designStore', designStore)
</script>

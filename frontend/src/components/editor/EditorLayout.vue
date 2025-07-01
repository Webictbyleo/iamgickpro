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
              :disabled="isLoadingTemplate"
              @use-template="handleUseTemplate"
            />
            
            <UploadPanel 
              v-if="activePanel === 'uploads'" 
              @add-media="handleAddMedia" 
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
              @toggle-visibility="toggleLayerVisibility"
              @toggle-lock="toggleLayerLock"
              @reorder-layers="reorderLayers"
              @update-layer-name="handleUpdateLayerName"
            />
            <!-- Plugin Panel Manager -->
            <PluginPanelManager 
              v-if="activePanelModal && isPluginPanel(activePanelModal) && selectedLayer"
              :plugin-id="activePanelModal.slice(7)" 
              :selected-layer="selectedLayer"
              @layer-updated="handlePluginLayerUpdate"
              @close="handleCancelPanelModal"
            />

            <!-- Contextual Panels -->
            <ImageEditingPanel
              v-if="activePanelModal === 'image-editing' && selectedLayer && selectedLayer.type === 'image'"
              :properties="selectedLayer.properties as ImageLayerProperties"
              @update="handleUpdateImageEdit"
            />
          </div>
        </div>

        <!-- Canvas Area -->
        <div ref="canvasViewportElement" class="flex-1 bg-gray-100 dark:bg-gray-800 relative flex flex-col min-w-0 h-full">

          <!-- Canvas -->
          <div class="flex-1 relative min-h-0">
            <DesignCanvas 
              ref="designCanvasRef"
              :width="canvasWidth"
              :height="canvasHeight"
              :zoom-level="zoomLevel"
              :selected-layer="selectedLayer"
              :active-tool="activeTool || undefined"
              :show-floating-toolbar="true"
              @tool-update="handleToolUpdate"
              @duplicate-layer="handleDuplicateSelectedLayer"
              @delete-layer="handleDeleteSelectedLayer"
              @lock-layer="handleLockSelectedLayer"
              @toggle-panel="handleTogglePanel"
              @position-preset="handlePositionPreset"
              @update-layer-opacity="handleUpdateLayerOpacity"
              @layer-context-menu="handleLayerContextMenu"
              @toggle-visibility="toggleLayerVisibility"
              @clear-selection="handleClearSelection"
              @plugin-tool="handlePluginToolPanel"
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
              @fit-to-screen="handleFitToScreen"
            />
          </div>
        </div>


      </div>
    </div>

    <!-- Context Menu -->
    <DesignEditorContextMenu
      :visible="contextMenu.visible"
      :position="contextMenu.position"
      :target-layer="contextMenu.layer"
      :has-clipboard="hasClipboard"
      @action="handleContextMenuAction"
      @close="handleCloseContextMenu"
    />

    <!-- Export Modal -->
    <DesignExportModal
      :is-open="exportModal.isOpen"
      :design="exportModal.design"
      @close="exportModal.isOpen = false"
      @exported="handleExportCompleted"
    />
    
    <!-- Template Loading Overlay -->
    <div v-if="isLoadingTemplate" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
      <div class="bg-white rounded-lg p-6 max-w-sm mx-4 text-center shadow-2xl">
        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-purple-600 mx-auto mb-4"></div>
        <h3 class="text-lg font-semibold text-gray-900 mb-2">Loading Template</h3>
        <p class="text-gray-600">Please wait while we load your template...</p>
      </div>
    </div>

    <!-- Sync Status Indicator -->
    <div v-if="getUnsyncedLayers.length > 0 || failedSyncLayers.length > 0" 
         class="fixed top-4 right-4 z-40 bg-white rounded-lg shadow-lg border border-gray-200 p-3 max-w-sm">
      <div class="flex items-start space-x-3">
        <!-- Status Icon -->
        <div class="flex-shrink-0">
          <div v-if="failedSyncLayers.length > 0" class="w-5 h-5 bg-red-100 rounded-full flex items-center justify-center">
            <svg class="w-3 h-3 text-red-600" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
            </svg>
          </div>
          <div v-else class="w-5 h-5 bg-yellow-100 rounded-full flex items-center justify-center">
            <svg class="w-3 h-3 text-yellow-600 animate-spin" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
          </div>
        </div>
        
        <!-- Status Message -->
        <div class="flex-1 min-w-0">
          <p class="text-sm font-medium text-gray-900">
            <span v-if="failedSyncLayers.length > 0">Sync Failed</span>
            <span v-else>Syncing Changes</span>
          </p>
          <p class="text-sm text-gray-500">
            <span v-if="failedSyncLayers.length > 0">
              {{ failedSyncLayers.length }} layer{{ failedSyncLayers.length !== 1 ? 's' : '' }} failed to sync
            </span>
            <span v-else>
              {{ getUnsyncedLayers.length }} layer{{ getUnsyncedLayers.length !== 1 ? 's' : '' }} pending
            </span>
          </p>
          
          <!-- Retry Button for Failed Syncs -->
          <button v-if="failedSyncLayers.length > 0" 
                  @click="retryFailedSyncs"
                  class="mt-2 text-xs bg-red-600 text-white px-2 py-1 rounded hover:bg-red-700 transition-colors">
            Retry Sync
          </button>
        </div>
      </div>
    </div>
  </div>
  <!-- Toast Notifications -->
      <ToastNotifications />
</template>

<script setup lang="ts">
import { ref, computed, provide, watch, onMounted, onUnmounted, nextTick } from 'vue'
import { useDesignStore } from '@/stores/design'
import type { Layer, LayerType, ImageLayerProperties, Design, DesignBackground, Template, DetailedTemplate, PluginEvent, PluginLayerUpdate } from '@/types'
import ToastNotifications from '@/components/ui/ToastNotifications.vue'
import DesignExportModal from '@/components/modals/DesignExportModal.vue'

// Modern Component imports
import ModernSidebar from './Sidebar/ModernSidebar.vue'
import ModernToolbar from './Toolbar/ModernToolbar.vue'
import ZoomControls from './Canvas/ZoomControls.vue'
import ModernButton from '@/components/common/ModernButton.vue'

// Legacy component imports (still used)
import ElementsPanel from './Panels/ElementsPanel.vue'
import TemplatesPanel from './Panels/TemplatesPanel.vue'
import UploadPanel from './Panels/UploadPanel.vue'
import LayerPanel from './Panels/LayerPanel.vue'
import MediaPanel from './Panels/MediaPanel.vue'
import AnimationPanel from './Panels/AnimationPanel.vue'
import ColorsPanel from './Panels/ColorsPanel.vue'
import DesignCanvas from './Canvas/DesignCanvas.vue'
import ImageEditingPanel from './Panels/ImageEditingPanel.vue'
import DesignEditorContextMenu from './ContextMenu/DesignEditorContextMenu.vue'
import PluginPanelManager from './plugins/PluginPanelManager.vue'

// Icons
import { XMarkIcon } from '@heroicons/vue/24/outline'

// Composables
import { useDesignEditor } from '@/composables/useDesignEditor'
import { useLayerManagement } from '@/composables/useLayerManagement'
import { useKeyboardShortcuts } from '@/composables/useKeyboardShortcuts'
import { usePanelManagement } from '@/composables/usePanelManagement'
import { useRoute, useRouter } from 'vue-router'

// API Services
import { templateAPI, mediaAPI, layerAPI } from '@/services/api'
import { useNotifications } from '@/composables/useNotifications'

const designStore = useDesignStore()
const route = useRoute()
const router = useRouter()

// Create helper methods for design updates
const updateDesignBackground = (background: string | DesignBackground) => {
  if (designStore.currentDesign) {
    if (typeof background === 'string') {
      // Legacy string format
      designStore.currentDesign.data.backgroundColor = background
      designStore.currentDesign.data.background = { type: 'solid', color: background }
    } else {
      // New DesignBackground format
      designStore.currentDesign.data.background = background
      // Keep legacy field for backward compatibility
      if (background.type === 'solid') {
        designStore.currentDesign.data.backgroundColor = background.color
      }
    }
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
  hasUnsyncedChanges,
  saveError,
  isPerformingHistoryOperation,
  getUnsyncedLayers,
  retryFailedSyncs,
  pendingSyncOperations,
  failedSyncLayers,
  temporaryLayers,
  initializeEditor,
  loadDesign,
  loadDesignFromData,
  saveDesign,
  exportDesign,
  undo,
  redo,
  cleanup
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

// Initialize notifications
const { showSuccess, showError, showInfo } = useNotifications()

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
  cleanCache,
  openPluginPanel,
  isPluginPanel,
} = usePanelManagement()

// Panel context for context-aware panels
const panelContext = ref<any>(null)

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


// Modern UI State
const activeTool = ref<'select' | 'text' | 'shape' | 'image' | 'pan' | null>('select')
const zoomLevel = ref(1)
const canvasContainerWidth = ref(1000)
const canvasContainerHeight = ref(700)
const canvasViewportElement = ref<HTMLElement>()
const designCanvasRef = ref<InstanceType<typeof DesignCanvas>>()
const isLoadingTemplate = ref(false)

// Update canvas container dimensions based on actual viewport
const updateCanvasViewportDimensions = () => {
  if (designCanvasRef.value && designCanvasRef.value.canvasContainer && designCanvasRef.value.canvasContainer.parentElement) {
    const rect = designCanvasRef.value.canvasContainer.parentElement.getBoundingClientRect()
    canvasContainerWidth.value = rect.width
    canvasContainerHeight.value = rect.height
    
    // Update stage dimensions to match viewport
    if (editorSDK.value) {
      editorSDK.value.canvas.setStageSize(rect.width, rect.height)
    }
  }
}

// Watch for window resize and panel changes
onMounted(() => {
  updateCanvasViewportDimensions()
  window.addEventListener('resize', updateCanvasViewportDimensions)
})

onUnmounted(() => {
  window.removeEventListener('resize', updateCanvasViewportDimensions)
})

// Update dimensions when panels change
watch([activePanel, activePanelModal], (newValues, oldValues) => {
  nextTick(() => {
    
    updateCanvasViewportDimensions()
    
    // Only auto-fit to screen when panels open/close, not when switching between panels
    const [newActivePanel, newActivePanelModal] = newValues || [null, null]
    const [oldActivePanel, oldActivePanelModal] = oldValues || [null, null]
    
    // Check if panel opened or closed (not just switched)
    const wasPanelOpen = !!oldActivePanel || !!oldActivePanelModal
    const isPanelOpen = !!newActivePanel || !!newActivePanelModal
    
    // Center stage when panel state changes (opened or closed) - preserves zoom level
    if (wasPanelOpen !== isPanelOpen) {
      console.log('🎯 EditorLayout: Panel state changed, centering canvas (preserving zoom)', {
        wasPanelOpen,
        isPanelOpen,
        newActivePanel,
        newActivePanelModal
      })
      
      // Immediate viewport update
      setTimeout(() => {
        updateCanvasViewportDimensions()
      }, 50) // Quick update for responsive feel
      
      // Center the canvas after panel animation completes (without changing zoom)
      setTimeout(() => {
        centerCanvas()
      }, 300) // Delay for panel animations to complete
    }
  })
})

// Context Menu State
const contextMenu = ref({
  visible: false,
  position: { x: 0, y: 0 },
  layer: null as Layer | null
})

// Export Modal State
const exportModal = ref({
  isOpen: false,
  design: null as any | null
})

// Clipboard state for copy/paste functionality
const clipboard = ref<Layer | null>(null)
const hasClipboard = computed(() => clipboard.value !== null)

// Panel configuration - include all panels that should show in left sidebar
const leftPanels = ['elements', 'templates', 'media', 'layers', 'animation', 'colors']

// Computed properties
const designName = computed({
  get: () => designStore.currentDesign?.name || designStore.currentDesign?.title  || 'Untitled Design',
  set: (value: string) => {
    if (designStore.currentDesign) {
      designStore.updateDesignName(value)
    }
  }
})

const saveStatus = computed(() => {
  if (saveError.value) return 'error'
  if (designStore.isLoading) return 'saving'
  if (hasUnsyncedChanges.value) return 'unsaved'
  return 'saved'
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
  get: () => {
    const currentDesign = designStore.currentDesign
    if (!currentDesign) return '#ffffff'
    
    // Check new background format first
    if (currentDesign.data.background) {
      return currentDesign.data.background
    }
    
    // Fall back to legacy backgroundColor field
    return currentDesign.data.backgroundColor || '#ffffff'
  },
  set: (value: string | DesignBackground) => {
    updateDesignBackground(value)
    // Update canvas background
    if (typeof value === 'string') {
      editorSDK.value?.canvas.setBackgroundColor(value)
    } else {
      editorSDK.value?.canvas.setBackground(value)
    }
  }
})

const designWidth = ref<number>(600)
const designHeight = ref<number>(400)

const layers = computed(() => designStore.currentDesign?.layers || [])
const selectedLayer = computed(() => selectedLayers.value[0] || null)

// Watch for layer selection changes and auto-close contextual panels
watch(selectedLayer, (newLayer, oldLayer) => {
  if(newLayer && newLayer.id === oldLayer?.id) {
    // No change in selection, do nothing
    return
  }
  if (newLayer) {
    // When a layer is selected, automatically switch to select tool
    // This ensures the layer-specific toolbar will show instead of tool toolbar
    activeTool.value = 'select'
    
    // Handle layer selection change through panel management
    handleLayerSelectionChange(selectedLayer.value ? [selectedLayer.value] : [])
  } else {
    // Auto-close contextual panel when no layer is selected
    closeContextualPanels()
    // If we have plugin panel active, switch to elements panel
    if (activePanelModal.value && isPluginPanel(activePanelModal.value)) {
      handlePanelChange('elements')
    }
  }
})

watch(() => [designStore.currentDesign?.width, designStore.currentDesign?.height], (newValues) => {
  const [newWidth, newHeight] = newValues
  console.log('🎯 EditorLayout: Design dimensions updated:', { newWidth, newHeight })
  if (newWidth && newHeight) {
    designWidth.value = newWidth
    designHeight.value = newHeight
  }else if(newWidth) {
    designWidth.value = newWidth
  } else if(newHeight) {
    designHeight.value = newHeight
  }
})

// Event handlers
const handleToolChange = (tool: string) => {
  activeTool.value = tool as any
  
  // Handle tool-specific functionality
  if (editorSDK.value?.canvas) {
    if (tool === 'pan') {
      // Enable pan mode
      editorSDK.value.canvas.setPanMode(true)
    } else {
      // Disable pan mode for other tools
      editorSDK.value.canvas.setPanMode(false)
    }
  }
  
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

// Initialize editor using the canvas container ref
const initializeEditorWithCanvas = async () => {
  await nextTick() // Ensure DOM is fully rendered
  
  if (designCanvasRef.value && designCanvasRef.value.canvasContainer) {
    console.log('🎯 EditorLayout: Initializing editor with canvas container')
    initializeEditor(designCanvasRef.value.canvasContainer)
    // Auto-fit to screen after editor initialization
    autoFitToScreen()
  } else {
    console.error('❌ EditorLayout: Canvas container not available for editor initialization')
  }
}

const handleAddElement = (type: LayerType, properties: any) => {
  addElement(type, properties)
  activeTool.value = 'select' // Switch to select tool after adding element
}

const handleAddTemplate = (template: any) => {
  console.log('Adding template:', template)
  // TODO: Implement template handling logic
}

const handleUseTemplate = async (template: Template) => {
  if (isLoadingTemplate.value) return // Prevent multiple simultaneous template loads
  
  try {
    isLoadingTemplate.value = true
    
    // Show loading notification
    showInfo('Loading template...')
    
    // Get the full template data using the getTemplate API
    const response = await templateAPI.getTemplate(template.uuid)
    
    console.log('📋 Raw template API response:', {
      status: response.status,
      hasData: !!response.data,
      hasSuccess: response.data?.success,
      responseStructure: response.data ? Object.keys(response.data) : []
    })
    
    if (response.data?.success && response.data?.data?.template) {
      const fullTemplate = response.data.data.template
      
      console.log('📋 Full template data received:', {
        uuid: fullTemplate.uuid,
        name: fullTemplate.name,
        hasCanvasSettings: !!fullTemplate.canvasSettings,
        hasLayers: !!fullTemplate.layers,
        layerCount: fullTemplate.layers?.length || 0
      })
      
      if (!fullTemplate.canvasSettings && !fullTemplate.layers) {
        throw new Error('Template data not available')
      }
      
      if (!editorSDK.value) {
        throw new Error('Editor SDK not initialized')
      }
      
      // Update the current design with template data while preserving the design ID
      if (designStore.currentDesign) {
        // Clear all existing layers
        await layerAPI.clearAllLayers(designStore.currentDesign.id)
        // Create a modified copy of the current design with template data
        const updatedDesign: Design = {
          ...designStore.currentDesign,
          width: fullTemplate.width,
          height: fullTemplate.height,
          data: {
            ...designStore.currentDesign.data,
            ...fullTemplate.canvasSettings
          },
          layers: fullTemplate.layers || [],
          updatedAt: new Date().toISOString()
        }
        
        console.log('📋 Loading template into editor:', {
          templateName: fullTemplate.name,
          canvasSize: `${fullTemplate.width}x${fullTemplate.height}`,
          layerCount: fullTemplate.layers?.length || 0
        })
        
        // Load the updated design using the new loadDesignFromData method
        const loadResult = await loadDesignFromData(updatedDesign)
        
        if (!loadResult.success) {
          throw new Error(loadResult.error || 'Failed to load template data into editor')
        }
        
        // Close all panels and auto-fit to screen
        
        autoFitToScreen()
        
        showSuccess('Template loaded successfully!')
      } else {
        throw new Error('No current design to load template into')
      }
    } else {
      throw new Error(response.data?.message || 'Failed to load template')
    }
  } catch (error: any) {
    console.error('Template loading failed:', error)
    
    const errorMessage = error.response?.data?.message || error.message || 'Failed to load template'
    showError(`Template Error: ${errorMessage}`)
  } finally {
    isLoadingTemplate.value = false
  }
}

// History handlers
const handleUndo = () => {
  undo()
}

const handleRedo = () => {
  redo()
}

const handleSelectLayer = (layerId: number, event: MouseEvent) => {
  selectLayer(layerId, event)
  // Layer selection will automatically show context toolbar
}

const handleDuplicateLayer = (layerId: number) => {
  duplicateLayer(layerId)
}

const handleDeleteLayer = (layerId: number) => {
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
    toggleLayerLock(selectedLayer.value.id)
  }
}

const handlePositionPreset = (preset: string) => {
  if (!selectedLayer.value || !editorSDK.value) return

  // Use the proper TransformManager API for position presets
  // The EditorSDK handles canvas dimensions internally
  editorSDK.value.transform.applyPositionPreset(preset)
}

const handleUpdateLayerOpacity = (opacity: number) => {
  if (!selectedLayer.value || !editorSDK.value) {
    return
  }
  
  console.log('🎨 Updating layer opacity:', opacity, 'for layer:', selectedLayer.value.id)
  
  // Update layer opacity directly through LayerManager
  // This will update the LayerNode and emit the layer:updated event
  editorSDK.value.layers.updateLayer(selectedLayer.value.id, { opacity })
}

const handleUpdateLayerName = (layerId: number, name: string) => {
  if (!editorSDK.value) return
  
  // Update layer name through the LayerManager
  updateLayerProperties(layerId, { name })
}

const handleUpdateProperty = (property: string, value: any) => {
  if (selectedLayer.value) {
    const updates = { [property]: value }
    updateLayerProperties(selectedLayer.value.id, updates)
  }
}

const handleZoomChanged = (zoom: number) => {
  zoomLevel.value = zoom
  // Apply zoom using editorSDK with zoomToCenter option for programmatic zooms
  if (editorSDK.value) {
    editorSDK.value.canvas.setZoom(zoom, { zoomToCenter: true })
  }
}

const handlePanToCenter = () => {
  // Use centerView instead of panToCenter
  //editorSDK.value?.canvas.centerView()
}

const handleFitToScreen = () => {
  
  // First, ensure viewport dimensions are up to date
  updateCanvasViewportDimensions()
  
  // Use zoomToFit method from editorSDK with proper viewport dimensions
  if (editorSDK.value && canvasViewportElement.value) {
    // Get the actual viewport dimensions
    
    let viewportWidth = canvasContainerWidth.value
    let viewportHeight = canvasContainerHeight.value
    
    // Only account for zoom controls (positioned at bottom-right)
    // Reduce width by 100px for zoom controls, height by 50px
    const zoomControlAdjustment = { width: 100, height: 50 }
    
    viewportWidth = Math.max(viewportWidth - zoomControlAdjustment.width, 400)
    viewportHeight = Math.max(viewportHeight - zoomControlAdjustment.height, 300)
    console.log('🎯 EditorLayout: Adjusted viewport dimensions for fitToScreen:', {
      viewportWidth,
      viewportHeight
    })
    
    // Call fitCanvasToViewport with the adjusted viewport dimensions
    editorSDK.value.canvas.fitCanvasToViewport(viewportWidth, viewportHeight)
    zoomLevel.value = editorSDK.value.canvas.getZoom() || 1
  }
}

// Auto-fit to screen utility function
const autoFitToScreen = () => {
  if(isPerformingHistoryOperation.value) {
    console.warn('Skipping auto-fit while performing history operation')
    return
  }
  
  console.log('🎯 autoFitToScreen called - ensuring consistent viewport fit')
  
  // Use multiple nextTick calls to ensure DOM is fully updated
  nextTick(() => {
    // First, update viewport dimensions to ensure they're current
    updateCanvasViewportDimensions()
    
    // Then use a delay to ensure all layout changes are complete
    setTimeout(() => {
      if (editorSDK.value) {
        console.log('🎯 Executing auto-fit with current viewport dimensions:', {
          viewportWidth: canvasContainerWidth.value,
          viewportHeight: canvasContainerHeight.value
        })
        handleFitToScreen()
      }
    }, 150) // Slightly longer delay for more consistency
  })
}

const handleSave = async () => {
  try {
    await saveDesign()
  } catch (error) {
    console.error('Save failed:', error)
    // Error handling is already done in saveDesign function
  }
}

const handleExport = async (format: string) => {
  if (!editorSDK.value) {
    console.error('EditorSDK not available for export')
    return
  }

  try {
    // Use the existing designName computed property and sanitize for filename
    const sanitizedName = designName.value
      .replace(/[^a-zA-Z0-9\-_\s]/g, '') // Remove special characters
      .trim()
      .replace(/\s+/g, '_') // Replace spaces with underscores
    const filename = `${sanitizedName}.${format}`
    
    console.log(`Exporting design as ${format}...`)
    editorSDK.value.downloadAsImage(format as 'png' | 'jpeg' | 'webp', filename)
    console.log(`Export completed: ${filename}`)
  } catch (error) {
    console.error('Export failed:', error)
    // TODO: Show error notification to user
  }
}

const handleExportCompleted = (url: string, filename: string) => {
  console.log('Export completed:', { url, filename })
  // TODO: Handle completed export (download, notifications, etc.)
}

const handleAddMedia = (mediaData: any) => {
  // Handle new structured media data
  if (mediaData.type && mediaData.data) {
    // New format: { type: 'svg'|'image', data: properties }
    addElement(mediaData.type, mediaData.data,mediaData.transform || undefined)
  } else {
    // Legacy format: direct properties (for backward compatibility)
    addElement('image', mediaData)
  }
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

// Property panel event handlers
const handleCanvasWidthUpdate = (width: number) => {
  updateDesignSize(width, canvasHeight.value)
  editorSDK.value?.canvas.setSize(width, canvasHeight.value)
}

const handleCanvasHeightUpdate = (height: number) => {
  updateDesignSize(canvasWidth.value, height)
  editorSDK.value?.canvas.setSize(canvasWidth.value, height)
}

const handleBackgroundUpdate = (background: string | DesignBackground) => {
  backgroundColor.value = background
}

// Context Menu Event Handler
const handleLayerContextMenu = (event: MouseEvent, layer?: Layer | null) => {
  console.log('🎯 EditorLayout: handleLayerContextMenu called', { 
    layerName: layer?.name || 'none',
    layerId: layer?.id || 'none',
    eventType: event.type,
    position: { x: event.clientX, y: event.clientY },
    layerObject: layer,
    layerType: typeof layer,
    layerKeys: layer ? Object.keys(layer) : []
  })
  
  // Prevent default browser context menu and stop propagation
  event.preventDefault()
  event.stopPropagation()
  
  // Close any existing context menu first to allow switching
  if (contextMenu.value.visible) {
    contextMenu.value.visible = false
  }
  
  // Use nextTick and a small delay to ensure the right-click event is fully processed
  nextTick(() => {
    setTimeout(() => {
      console.log('🎯 EditorLayout: Setting context menu visible with layer:', layer?.name || 'NO LAYER')
      console.log('🎯 EditorLayout: Context menu layer object:', layer)
      // Set context menu state
      contextMenu.value = {
        visible: true,
        position: { x: event.clientX, y: event.clientY },
        layer: layer || null
      }
      console.log('🎯 EditorLayout: Context menu state set:', {
        visible: contextMenu.value.visible,
        hasLayer: !!contextMenu.value.layer,
        layerName: contextMenu.value.layer?.name || 'NO LAYER'
      })
    }, 10) // Small delay to ensure event propagation is complete
  })
}

// Context Menu Handlers
const handleCloseContextMenu = () => {
  contextMenu.value.visible = false
}

// Unified Context Menu Action Handler
const handleContextMenuAction = (actionType: string, payload?: any) => {
  console.log('Context menu action:', actionType, payload)
  
  // Close context menu first
  handleCloseContextMenu()
  
  // Handle the action based on type
  switch (actionType) {
    case 'copy':
      handleCopyLayer()
      break
    
    case 'paste':
      handlePasteLayer()
      break
    
    case 'duplicate':
      handleContextMenuDuplicate()
      break
    
    case 'delete':
      handleContextMenuDelete()
      break
    
    case 'bring-to-front':
      handleBringToFront()
      break
    
    case 'bring-forward':
      handleBringForward()
      break
    
    case 'send-backward':
      handleSendBackward()
      break
    
    case 'send-to-back':
      handleSendToBack()
      break
    
    case 'toggle-lock':
      handleToggleLock()
      break
    
    case 'toggle-visibility':
      handleToggleVisibility()
      break
    
    case 'select-all':
      handleSelectAll()
      break
    
    case 'zoom-to-fit':
      handleZoomToFit()
      break
    
    case 'reset-zoom':
      handleResetZoom()
      break
    
    case 'context-action':
      if (payload) {
        handleContextAction(payload)
      }
      break
    
    default:
      console.warn('Unknown context menu action:', actionType, payload)
  }
}

const handleCopyLayer = () => {
  if (contextMenu.value.layer) {
    // Create a deep copy of the layer for clipboard
    clipboard.value = JSON.parse(JSON.stringify(contextMenu.value.layer))
    console.log('Layer copied to clipboard:', clipboard.value?.id)
  }
}

const handlePasteLayer = () => {
  if (clipboard.value && editorSDK.value) {
    // Create a new layer from clipboard data
    const newLayer = {
      ...clipboard.value,
      id: -Date.now(), // Generate new numeric ID
      name: `${clipboard.value.name} Copy`,
      transform: {
        ...clipboard.value.transform,
        x: (clipboard.value.transform.x || 0) + 20, // Offset position slightly
        y: (clipboard.value.transform.y || 0) + 20
      }
    }
    
    // Add the new layer through the SDK
    editorSDK.value.layers.addLayer(newLayer)
    console.log('Layer pasted from clipboard:', newLayer.id)
  }
}

const handleBringToFront = () => {
  if (contextMenu.value.layer && editorSDK.value) {
    editorSDK.value.layers.bringToFront(contextMenu.value.layer.id)
    console.log('Brought layer to front:', contextMenu.value.layer.id)
  }
}

const handleBringForward = () => {
  if (contextMenu.value.layer && editorSDK.value) {
    editorSDK.value.layers.bringForward(contextMenu.value.layer.id)
    console.log('Brought layer forward:', contextMenu.value.layer.id)
  }
}

const handleSendBackward = () => {
  if (contextMenu.value.layer && editorSDK.value) {
    editorSDK.value.layers.sendBackward(contextMenu.value.layer.id)
    console.log('Sent layer backward:', contextMenu.value.layer.id)
  }
}

const handleSendToBack = () => {
  if (contextMenu.value.layer && editorSDK.value) {
    editorSDK.value.layers.sendToBack(contextMenu.value.layer.id)
    console.log('Sent layer to back:', contextMenu.value.layer.id)
  }
}

const handleToggleLock = () => {
  if (contextMenu.value.layer) {
    toggleLayerLock(contextMenu.value.layer.id)
    console.log('Toggled layer lock:', contextMenu.value.layer.id)
  }
}

const handleToggleVisibility = () => {
  if (contextMenu.value.layer) {
    toggleLayerVisibility(contextMenu.value.layer.id)
    console.log('Toggled layer visibility:', contextMenu.value.layer.id)
  }
}

const handleContextMenuDuplicate = () => {
  if (contextMenu.value.layer && editorSDK.value) {
    editorSDK.value.layers.duplicateLayer(contextMenu.value.layer.id)
    console.log('Layer duplicated:', contextMenu.value.layer.id)
  }
}

const handleContextMenuDelete = () => {
  if (contextMenu.value.layer && editorSDK.value) {
    editorSDK.value.layers.deleteLayer(contextMenu.value.layer.id)
    console.log('Deleted layer:', contextMenu.value.layer.id)
  }
}

// Clear Selection Handler
const handleClearSelection = () => {
  if (editorSDK.value) {
    editorSDK.value.layers.deselectAll()
  }
}

// Enhanced Context Menu Event Handlers
const handleContextAction = (action: string) => {
  if (!contextMenu.value.layer || !editorSDK.value) return
  
  const layer = contextMenu.value.layer
  
  switch (action) {
    case 'edit-text':
      if (layer.type === 'text') {
        // Open text editing panel
        handleTogglePanel('text-editing', layer)
      }
      break
    case 'edit-image':
      if (layer.type === 'image') {
        // Open image editing panel
        handleTogglePanel('image-editing', layer)
      }
      break
    case 'edit-shape':
      if (layer.type === 'shape') {
        // Open shape properties panel
        handleTogglePanel('shape-editing', layer)
      }
      break
    case 'ungroup':
      if (layer.type === 'group') {
        // Implement ungroup functionality
        // editorSDK.value.layers.ungroupLayer?.(layer.id)
        console.log('Ungroup functionality not yet implemented for:', layer.id)
      }
      break
    default:
      console.log('Context action not implemented:', action)
  }
}

const handleSelectAll = () => {
  if (editorSDK.value) {
    // Select all layers in the current design
    const allLayerIds = layers.value.map((layer: Layer) => layer.id)
    editorSDK.value.layers.selectLayers(allLayerIds)
    console.log('Selected all layers:', allLayerIds)
  }
}

const handleZoomToFit = () => {
  if (editorSDK.value) {
    editorSDK.value.canvas.zoomToFit()
    zoomLevel.value = editorSDK.value.canvas.getZoom() || 1
    console.log('Zoomed to fit canvas')
  }
}

const handleResetZoom = () => {
  if (editorSDK.value) {
    editorSDK.value.canvas.setZoom(1)
    zoomLevel.value = 1
    console.log('Reset zoom to 100%')
  }
}

// Center canvas utility function (preserves zoom level)
const centerCanvas = () => {
  if (editorSDK.value) {
    editorSDK.value.canvas.centerCanvas()
  }
}

// Generic Panel Modal Handlers
const handleTogglePanel = (panelType: string, data?: any) => {
  // Clear panel context
  panelContext.value = null
  
  // Handle both regular and contextual panels through the smart panel management
  if (isLeftPanel(panelType)) {
    handlePanelChange(panelType)
  } else {
    handleToggleContextualPanel(panelType, data)
  }
}

const handlePluginToolPanel = (event: PluginEvent) => {
  if(!selectedLayer.value)return;
  // Use PluginPanelManager to handle plugin-specific panels
  openPluginPanel(event.pluginId, selectedLayer.value.id, event.options)
}

const handleCancelPanelModal = () => {
  panelContext.value = null
  closeAllPanels()
}

const handleUpdateImageEdit = (updatedProperties: any) => {
  if (selectedLayer.value && selectedLayer.value.type === 'image') {
    updateLayerProperties(selectedLayer.value.id, {
      properties: {
        ...selectedLayer.value.properties,
        ...updatedProperties
      }
    })
  }
}

// Colors Panel Event Handlers
const handleApplyColor = (colorString: string) => {
  // Parse color string to extract color and opacity
  let color = colorString
  let opacity = 1

  if (colorString.startsWith('rgba')) {
    // Parse rgba(r, g, b, a) format
    const match = colorString.match(/rgba\((\d+),\s*(\d+),\s*(\d+),\s*([\d.]+)\)/)
    if (match) {
      const r = parseInt(match[1])
      const g = parseInt(match[2])
      const b = parseInt(match[3])
      opacity = parseFloat(match[4])
      color = `#${((1 << 24) + (r << 16) + (g << 8) + b).toString(16).slice(1)}`
    }
  }

  if (selectedLayer.value) {
    // Apply color to selected layer based on layer type
    if (selectedLayer.value.type === 'shape') {
      const updates: Partial<Layer> = {
        properties: {
          ...selectedLayer.value.properties,
          fill: {
            type: 'solid' as const,
            color: color,
            opacity: opacity
          }
        }
      }
      updateLayerProperties(selectedLayer.value.id, updates)
    } else if (selectedLayer.value.type === 'text') {
      const updates: Partial<Layer> = {
        properties: {
          ...selectedLayer.value.properties,
          color: color,
          opacity: opacity
        }
      }
      updateLayerProperties(selectedLayer.value.id, updates)
    } else if (selectedLayer.value.type === 'svg') {
      // For SVG layers, apply color to the main fill color (global)
      const svgProperties = selectedLayer.value.properties as any
      const updates: Partial<Layer> = {
        properties: {
          ...svgProperties,
          fillColors: {
            ...svgProperties.fillColors,
            global: color
          }
        }
      }
      updateLayerProperties(selectedLayer.value.id, updates)
      console.log('Applied color to SVG layer:', selectedLayer.value.id, color)
    }
    console.log('Applied color to layer:', selectedLayer.value.id, color)
  } else {
    // Apply to canvas background if no layer selected using new DesignBackground format
    backgroundColor.value = {
      type: 'solid',
      color: color
    }
    console.log('Applied color to canvas background:', color)
  }
}

const handleApplyGradient = (gradientString: string) => {
  if (selectedLayer.value && selectedLayer.value.type === 'shape') {
    // For shape layers, we need to parse the gradient and create a proper ShapeFillConfig
    const isLinear = gradientString.includes('linear-gradient')
    const isRadial = gradientString.includes('radial-gradient')
    
    if (isLinear || isRadial) {
      // Extract colors from gradient string
      const colorMatches = gradientString.match(/#[0-9a-fA-F]{6}/g) || []
      const colors = colorMatches.map((color, index) => ({
        color: color,
        stop: index / Math.max(1, colorMatches.length - 1)
      }))
      
      const updates: Partial<Layer> = {
        properties: {
          ...selectedLayer.value.properties,
          fill: {
            type: isLinear ? 'linear' as const : 'radial' as const,
            colors: colors,
            angle: isLinear ? 90 : undefined, // Default angle for linear gradients
            opacity: 1
          }
        }
      }
      updateLayerProperties(selectedLayer.value.id, updates)
      console.log('Applied gradient to shape layer:', selectedLayer.value.id, gradientString)
    }
  } else if (selectedLayer.value && selectedLayer.value.type === 'text') {
    // For text layers, gradients might not be supported, apply the first color instead
    const colorMatch = gradientString.match(/#[0-9a-fA-F]{6}/)
    if (colorMatch) {
      handleApplyColor(colorMatch[0])
    }
  } else if (selectedLayer.value && selectedLayer.value.type === 'svg') {
    // For SVG layers, apply the primary color from the gradient to the global fill
    const colorMatch = gradientString.match(/#[0-9a-fA-F]{6}/)
    if (colorMatch) {
      const svgProperties = selectedLayer.value.properties as any
      const updates: Partial<Layer> = {
        properties: {
          ...svgProperties,
          fillColors: {
            ...svgProperties.fillColors,
            global: colorMatch[0]
          }
        }
      }
      updateLayerProperties(selectedLayer.value.id, updates)
      console.log('Applied gradient primary color to SVG layer:', selectedLayer.value.id, colorMatch[0])
    }
  } else {
    // Apply gradient to canvas background using new DesignBackground format
    const isLinear = gradientString.includes('linear-gradient')
    const isRadial = gradientString.includes('radial-gradient')
    
    if (isLinear || isRadial) {
      // Enhanced color extraction with stop positions
      const colorStopRegex = /#[0-9a-fA-F]{6}\s*(?:(\d+)%)?/g
      const colorMatches = Array.from(gradientString.matchAll(colorStopRegex))
      
      const colors = colorMatches.map((match, index) => ({
        color: match[0].replace(/\s*\d+%/, ''), // Remove percentage from color
        stop: match[1] ? parseInt(match[1]) / 100 : index / Math.max(1, colorMatches.length - 1)
      }))
      
      // Parse gradient properties
      let angle = 0
      let centerX = 0.5
      let centerY = 0.5
      let radius = 0.7
      
      if (isLinear) {
        // Parse angle from linear gradient
        const angleMatch = gradientString.match(/(\d+)deg/)
        if (angleMatch) {
          angle = parseInt(angleMatch[1])
        } else {
          // Convert direction to angle
          if (gradientString.includes('to right')) angle = 90
          else if (gradientString.includes('to bottom')) angle = 180
          else if (gradientString.includes('to left')) angle = 270
          else if (gradientString.includes('to top')) angle = 0
          else if (gradientString.includes('to bottom right')) angle = 135
          else if (gradientString.includes('to bottom left')) angle = 225
        }
      } else {
        // Parse radial gradient center position
        const centerMatch = gradientString.match(/circle at (\d+)% (\d+)%/)
        if (centerMatch) {
          centerX = parseInt(centerMatch[1]) / 100
          centerY = parseInt(centerMatch[2]) / 100
        }
      }
      
      const gradientBackground: DesignBackground = {
        type: isLinear ? 'linear' : 'radial',
        gradient: {
          colors: colors,
          ...(isLinear ? { angle } : {
            centerX,
            centerY,
            radius
          })
        }
      }
      
      backgroundColor.value = gradientBackground
      console.log('🔍 EditorLayout: handleApplyGradient - Applied gradient to canvas background:', gradientBackground)
      console.log('🔍 EditorLayout: handleApplyGradient - Original gradient string:', gradientString)
      console.log('🔍 EditorLayout: handleApplyGradient - Parsed gradient config:', JSON.stringify(gradientBackground, null, 2))
    } else {
      // Fallback to single color if gradient parsing fails
      const colorMatch = gradientString.match(/#[0-9a-fA-F]{6}/)
      if (colorMatch) {
        backgroundColor.value = {
          type: 'solid',
          color: colorMatch[0]
        }
        console.log('Applied gradient color to canvas background:', colorMatch[0])
      }
    }
  }
}

const handleToolUpdate = (toolType: string, properties: any) => {
  if (selectedLayer.value) {
    // Build the updates object with the existing layer properties structure
    const updates: Partial<Layer> = {
      properties: {
        ...selectedLayer.value.properties,
        ...properties
      }
    }
    
    // Use updateLayerProperties instead of direct store updates
    // This ensures proper visual updates through the LayerManager
    updateLayerProperties(selectedLayer.value.id, updates)
    console.log('Updated layer properties from tool:', selectedLayer.value.id, properties)
  }
}


// Load design when component mounts
onMounted(async () => {
  const idParam = route.params.id as string
    // Initialize editor with canvas container
  await initializeEditorWithCanvas()
  try {
    if (idParam && idParam !== 'new') {
      // Check if this is a DSN format (for images) vs regular design ID
      if (isDSNFormat(idParam)) {
        await handleLoadImageAsDSN(idParam)
      } else {
        // Load existing design
        await handleLoadExistingDesign(idParam)
      }
    } else {
      // Create new design
      await handleCreateNewDesign()
    }
  } catch (error) {
    console.error('Failed to initialize editor:', error)
    // Create fallback design on any error
    await handleCreateFallbackDesign()
  }
  

  
  // Set up global event listeners
  setupGlobalEventListeners()
  
  // Ensure consistent auto-fit after everything is loaded and rendered
  // This backup mechanism ensures the design is properly fitted regardless of timing issues
  await nextTick() // Wait for Vue to finish rendering
  setTimeout(() => {
    if (editorSDK.value) {
      console.log('🎯 Applying backup auto-fit to ensure consistent viewport')
      // Use the public API through handleFitToScreen which calls the CanvasManager
      handleFitToScreen()
    }
  }, 500) // Longer delay to ensure everything is settled
})

// Helper function to load existing design
const handleLoadExistingDesign = async (designIdParam: string) => {
  // Parse design ID as integer for proper backend compatibility
  const designId = parseInt(designIdParam, 10)
  
  // Validate that the parsed ID is a valid number
  if (isNaN(designId) || designId <= 0) {
    console.warn(`Invalid design ID: ${designIdParam}`)
    // Redirect to new design creation
    router.replace({ name: 'Editor' })
    return
  }
  
  console.log(`🔄 Loading design ${designId}...`)
  
  // Use the improved loadDesign from useDesignEditor
  // This will load the design into the store AND the EditorSDK
  const loadResult = await loadDesign(designId.toString())
  
  if (!loadResult.success) {
    console.warn(`Failed to load design ${designId}:`, loadResult.error)
    
    // Create a fallback design when loading fails
    console.log('Creating fallback design due to load failure')
    const fallbackDesign = designStore.createNewDesign(800, 600)
    fallbackDesign.name = `Design ${designId} (Recovery)`
    fallbackDesign.title = `Design ${designId} (Recovery)`
    
    console.log('Fallback design created:', fallbackDesign)
  } else {
    console.log("✅ Design loaded successfully:", loadResult.design?.id)
  }
}

// Helper function to create new design
const handleCreateNewDesign = async () => {
  console.log('🔄 Creating new design...')
  
  // Get dimensions from query parameters, with fallback to defaults
  const width = route.query.width ? parseInt(route.query.width as string, 10) : 800
  const height = route.query.height ? parseInt(route.query.height as string, 10) : 600
  
  // Validate dimensions
  const validWidth = !isNaN(width) && width > 0 && width <= 8000 ? width : 800
  const validHeight = !isNaN(height) && height > 0 && height <= 8000 ? height : 600
  
  console.log(`🎨 Creating new design with dimensions: ${validWidth}x${validHeight}`)
  
  // Create new design with the specified or default dimensions
  const newDesign = designStore.createNewDesign(validWidth, validHeight)
  newDesign.name = 'Untitled Design'
  newDesign.title = 'Untitled Design'
  
  // Save the new design to the backend
  const result = await designStore.saveDesign(newDesign, true)
  
  if (result.success && newDesign.id) {
    const newDesignId = parseInt(newDesign.id.toString(), 10)
    if (!isNaN(newDesignId) && newDesignId > 0) {
      await editorSDK.value?.loadDesign(newDesign)
      // Replace current route with the new design ID (remove query params)
      router.replace({ 
        name: 'Editor', 
        params: { id: newDesignId.toString() } 
      })
      console.log(`✅ New design created with ID: ${newDesignId} and dimensions: ${validWidth}x${validHeight}`)
    }
  } else {
    console.warn('Failed to save new design, continuing with local version')
  }
  
  // Auto-fit to screen after new design creation
  autoFitToScreen()
}

// Helper function to create fallback design
const handleCreateFallbackDesign = async () => {
  console.log('🔄 Creating fallback design...')
  
  const fallbackDesign = designStore.createNewDesign(800, 600)
  fallbackDesign.name = 'Untitled Design'
  fallbackDesign.title = 'Untitled Design'
  
  console.log('Fallback design created')
  autoFitToScreen()
}

// Helper function to set up global event listeners
const setupGlobalEventListeners = () => {
  // Add global event listener for context menu
  document.addEventListener('editor:context-menu', handleEditorContextMenuEvent as EventListener)
  
  // Add global event listener for auto-fit requests
  document.addEventListener('editor:auto-fit-request', handleAutoFitRequest as EventListener)
  
  console.log('✅ Global event listeners set up')
}

// Global context menu event handler
const handleEditorContextMenuEvent = (event: CustomEvent) => {
  const { layer, position, event: originalEvent } = event.detail
  console.log('🎯 EditorLayout: Received global context menu event', { 
    layer: layer ? {
      id: layer.id,
      name: layer.name,
      type: layer.type
    } : null, 
    position,
    hasLayer: !!layer,
    layerProps: layer ? Object.keys(layer) : []
  })
  
  // Use a small delay to ensure the right-click event has fully completed
  nextTick(() => {
    console.log('🎯 EditorLayout: About to call handleLayerContextMenu with layer:', layer?.name || 'NO LAYER')
    handleLayerContextMenu(originalEvent, layer)
  })
}

// Global auto-fit request event handler
const handleAutoFitRequest = (event: CustomEvent) => {
  const { reason, design } = event.detail
  
  // Skip auto-fit during history operations to prevent flashing
  if (isPerformingHistoryOperation.value) {
    console.log('📐 Auto-fit skipped during undo/redo operation to prevent flashing')
    return
  }
  
  // Auto-fit to screen with appropriate reason
  autoFitToScreen()
  console.log('📐 Auto-fit executed for reason:', reason)
}

// Cleanup on unmount
onUnmounted(() => {
  cleanup()
  // Remove event listeners
  document.removeEventListener('editor:context-menu', handleEditorContextMenuEvent as EventListener)
  document.removeEventListener('editor:auto-fit-request', handleAutoFitRequest as EventListener)
  // Optionally save the design if there are unsaved changes
  if (hasUnsavedChanges.value) {
    saveDesign(false) // Don't show notifications for route change saves
  }
})

// Provide editor context to child components
provide('editorSDK', editorSDK)
provide('designStore', designStore)

// DSN (Data Source Name) format helpers
// DSN format: type:source:base64EncodedId
// Examples: 
// - stock:unsplash:base64EncodedUrl (stock image from Unsplash)
// - stock:pexels:base64EncodedUrl (stock image from Pexels)  
// - stock:iconfinder:base64EncodedUrl (icon from Iconfinder)
// - upload:media:base64EncodedResourceId (uploaded media file)
const isDSNFormat = (param: string): boolean => {
  // DSN format: type:source:(base64 encoded URL or resource ID)
  const parts = param.split(':')
  if (parts.length !== 3) return false
  
  const [type, source, encodedId] = parts
  
  // Validate type and source
  const validTypes = ['stock', 'upload']
  const validSources = ['unsplash', 'pexels', 'iconfinder', 'media']
  
  return validTypes.includes(type) && 
         validSources.includes(source) && 
         /^[A-Za-z0-9+/=]+$/.test(encodedId) // Valid base64 characters
}

// Parse DSN format
const parseDSN = (dsn: string): { type: string; source: string; id: string } | null => {
  // DSN format: type:source:(base64 encoded URL or resource ID)
  // Example: upload:media:L21lZGlhLzE5N2JjMGI3MmU4YTk0MzI3MjUxNzAyZDc0MzM2ZTI2LTY4NDZjODQ4M2RiNGMuanB
  const parts = dsn.split(':')
  if (parts.length !== 3) return null
  
  const [type, source, encodedId] = parts
  
  // Validate type
  if (!['stock', 'upload'].includes(type)) return null
  
  // Validate source
  if (!['unsplash', 'pexels', 'iconfinder', 'media'].includes(source)) return null
  
  // Validate base64 encoded ID (should contain valid base64 characters)
  if (!/^[A-Za-z0-9+/=]+$/.test(encodedId)) return null
  
  return {
    type,
    source, 
    id: encodedId // Keep as base64 encoded, will decode when needed
  }
}

// Handle loading image from DSN format
const handleLoadImageAsDSN = async (dsn: string) => {
  console.log(`🔄 Loading image from DSN: ${dsn}`)
  const parsed = parseDSN(dsn)
  if (!parsed) {
    console.warn(`Invalid DSN format: ${dsn}`)
    router.replace({ name: 'Editor' })
    return
  }
  
  try {
    // Decode the base64 encoded ID
    let decodedId: string
    try {
      decodedId = atob(parsed.id)
      console.log(`🔓 Decoded ID: ${decodedId}`)
    } catch (error) {
      throw new Error(`Invalid base64 encoded ID: ${parsed.id}`)
    }
    
    // Load image data and dimensions based on DSN type and source
    let imageUrl: string
    let imageData: any
    let imageDimensions: { width: number; height: number }
    
    if (parsed.type === 'upload') {
      // For uploads, use the media API to get complete information
      if (parsed.source === 'media') {
        console.log(`� Fetching uploaded media info for ID: ${decodedId}`)
        const response = await mediaAPI.getMediaItem(decodedId)
        imageData = response.data.data.media // Unwrap the API response structure
        imageUrl = response.data.data.media.url
        
        // Use dimensions from API if available, otherwise load image to get dimensions
        if (imageData.width && imageData.height) {
          imageDimensions = {
            width: imageData.width,
            height: imageData.height
          }
          console.log(`✅ Got dimensions from API: ${imageDimensions.width}x${imageDimensions.height}`)
        } else {
          console.log(`📏 Loading image to get dimensions: ${imageUrl}`)
          imageDimensions = await loadImageDimensions(imageUrl)
          console.log(`✅ Got dimensions from image load: ${imageDimensions.width}x${imageDimensions.height}`)
        }
      } else {
        throw new Error(`Unsupported upload source: ${parsed.source}`)
      }
      
    } else if (parsed.type === 'stock') {
      // For stock media, the decoded ID might be a URL or a resource identifier
        imageUrl = decodedId
        console.log(`📏 Loading stock image to get dimensions: ${imageUrl}`)
        imageDimensions = await loadImageDimensions(imageUrl)
        console.log(`✅ Got dimensions from stock image load: ${imageDimensions.width}x${imageDimensions.height}`)
        
        imageData = {
          url: imageUrl,
          width: imageDimensions.width,
          height: imageDimensions.height,
          name: `${parsed.source} Image`,
          source: parsed.source
        }
      
    } else {
      throw new Error(`Unsupported DSN type: ${parsed.type}`)
    }
    
    // Create new design with dimensions based on the loaded image
    // Smart canvas sizing based on image dimensions and aspect ratio
    let canvasWidth: number
    let canvasHeight: number
    
    const imageAspectRatio = imageDimensions.width / imageDimensions.height
    canvasWidth = imageDimensions.width
    canvasHeight =imageDimensions.height
    
    console.log(`🎨 Creating design with canvas: ${canvasWidth}x${canvasHeight}, image: ${imageDimensions.width}x${imageDimensions.height}, aspect ratio: ${imageAspectRatio.toFixed(2)}`)
    
    const newDesign = designStore.createNewDesign(canvasWidth, canvasHeight)
    newDesign.name = imageData.title || imageData.name || `${parsed.source} Design`
    newDesign.title = newDesign.name
    
    // Save the new design to the backend
    const result = await designStore.saveDesign(undefined, true)

    if (result.success && newDesign.id) {
      const newDesignId = parseInt(newDesign.id.toString(), 10)
      if (!isNaN(newDesignId) && newDesignId > 0) {
        await editorSDK.value?.loadDesign(newDesign)
        // Add image layer using the layer management system
        addElement('image', {
          src: imageUrl,
          alt: imageData.alt || imageData.description || '',
          name: imageData.title || imageData.name || 'Image'
        }, {
          x: 0,
          y: 0,
          scaleX: 1,
          scaleY: 1,
          rotation: 0,
          width: imageDimensions.width,
          height: imageDimensions.height
        })
        
        // Replace current route with the new design ID
        router.replace({ 
          name: 'Editor', 
          params: { id: newDesignId.toString() } 
        })
        console.log(`✅ Image design created with ID: ${newDesignId}`)
      }
    } else {
      console.warn('Failed to save image design, continuing with local version')
    }
    
    console.log('✅ Image loaded successfully from DSN:', {
      dsn: parsed,
      dimensions: imageDimensions,
      canvasDimensions: { width: canvasWidth, height: canvasHeight }
    })
    
  } catch (error) {
    console.error('Failed to load image from DSN:', error)
    
    // Create fallback design
    const fallbackDesign = designStore.createNewDesign(800, 600)
    fallbackDesign.name = 'New Design'
    fallbackDesign.title = 'New Design'
    
    // Show error notification
    if (error instanceof Error) {
      console.error(`❌ Error loading image: ${error.message}`)
    }
  }
}

// Utility function to load image dimensions from URL
const loadImageDimensions = (url: string): Promise<{ width: number; height: number }> => {
  return new Promise((resolve, reject) => {
    const img = new Image()
    
    img.onload = () => {
      resolve({
        width: img.naturalWidth,
        height: img.naturalHeight
      })
    }
    
    img.onerror = (error) => {
      console.warn(`Failed to load image for dimensions: ${url}`, error)
      // Return reasonable default dimensions instead of rejecting
      resolve({
        width: 800,
        height: 600
      })
    }
    
    // Set cross-origin to handle external images, but handle failures gracefully
    img.crossOrigin = 'anonymous'
    
    // Add timeout to prevent hanging
    const timeout = setTimeout(() => {
      console.warn(`Image load timeout: ${url}`)
      resolve({
        width: 800,
        height: 600
      })
    }, 10000) // 10 second timeout
    
    img.onload = () => {
      clearTimeout(timeout)
      resolve({
        width: img.naturalWidth,
        height: img.naturalHeight
      })
    }
    
    img.src = url
  })
}



// Plugin layer update handler
const handlePluginLayerUpdate = (update: PluginLayerUpdate) => {
  if (!editorSDK.value) return
  
  console.log('🔌 Handling plugin layer update:', update)
  
  // Update layer plugins
  if (update.plugins) {
   // Find layer from the store
    const layer = layers.value.find(layer => layer.id === update.layerId)
    if (!layer) {
      console.warn(`Layer not found for update: ${update.layerId}`)
      return
    }
    // Update plugins for the layer
    layer.plugins = {
      ...layer.plugins || {},
      ...update.plugins
    }
    console.log(`Updated plugins for layer ${layer.id}:`, layer.plugins)
  }
  
  // Apply any additional updates (properties, transform, etc.)
  if (update.updates) {
    const updates: Partial<Layer> = {
      properties: {
        ...selectedLayer.value.properties,
        ...update.updates.properties
      }
    }
    updateLayerProperties(update.layerId, updates)
  }
}
</script>
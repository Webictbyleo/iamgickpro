import { ref, computed, nextTick, type ComputedRef } from 'vue'
import { useRoute } from 'vue-router'
import { useDesignStore } from '@/stores/design'
import { useDesignHistory } from '@/composables/useDesignHistory'
import { useDesignPreview } from '@/composables/useDesignPreview'
import { EditorSDK } from '@/editor/sdk/EditorSDK'
import { layerAPI } from '@/services/api'
import type { EditorConfig } from '@/editor/sdk/types'
import type { Layer } from '@/types'

export function useDesignEditor() {
  const route = useRoute()
  const designStore = useDesignStore()
  const { generateAndSaveThumbnail } = useDesignPreview()

  // Initialize design history service
  const {
    canUndo,
    canRedo,
    currentEntry,
    undo: historyUndo,
    redo: historyRedo,
    addHistoryEntry,
    addLayerHistoryEntry,
    addCanvasHistoryEntry,
    addPropertyHistoryEntry,
    clearHistory,
    initialize: initializeHistory,
    getCurrentDesign: getCurrentHistoryDesign
  } = useDesignHistory()

  // Refs
  const editorSDK = ref<EditorSDK | null>(null)
  const isInitializing = ref(false)
  const hasUnsavedChanges = ref(false)
  const saveError = ref(false)
  const isPerformingHistoryOperation = ref(false) // Flag to prevent history reset during undo/redo

  // Auto-save functionality
  let autoSaveInterval: ReturnType<typeof setInterval>
  let thumbnailUpdateTimeout: ReturnType<typeof setTimeout>

  // Debounced thumbnail generation to avoid excessive API calls
  const debouncedThumbnailGeneration = () => {
    if (thumbnailUpdateTimeout) {
      clearTimeout(thumbnailUpdateTimeout)
    }
    
    thumbnailUpdateTimeout = setTimeout(async () => {
      const currentDesign = designStore.currentDesign
      if (currentDesign && currentDesign.id && !editorSDK.value?.isLoading()) {
        try {
          console.log('🖼️ Generating design thumbnail...')
          await generateAndSaveThumbnail(currentDesign, {
            width: 300,
            height: 200,
            format: 'png',
            quality: 0.8,
            updateBackend: true
          })
        } catch (error) {
          console.error('Failed to generate thumbnail:', error)
        }
      }
    }, 3000) // Wait 3 seconds after last change before generating thumbnail
  }

  const initializeEditor = async (container: HTMLElement) => {
    if (isInitializing.value || !container) {
      return
    }

    isInitializing.value = true
    
    try {
      // Clear any existing content in the container
      container.innerHTML = ''

      // Get current design dimensions (for canvas content)
      const currentDesign = designStore.currentDesign
      const designWidth = currentDesign?.width || 800
      const designHeight = currentDesign?.height || 600

      // Use container dimensions for the stage (viewport)
      const containerRect = container.getBoundingClientRect()
      const stageWidth = containerRect.width || 1000
      const stageHeight = containerRect.height || 700

      console.log('Initializing EditorSDK:', {
        designDimensions: `${designWidth}x${designHeight}`,
        containerDimensions: `${stageWidth}x${stageHeight}`,
        containerElement: container
      })

      // Initialize SDK with container dimensions for stage
      const config: EditorConfig = {
        container,
        width: stageWidth,
        height: stageHeight,
        // Pass design dimensions separately for canvas content
        canvasWidth: designWidth,
        canvasHeight: designHeight
      }

      editorSDK.value = new EditorSDK(config)

      // Setup SDK event listeners
      setupSDKEventListeners()

      // Load current design if exists
      if (currentDesign) {
        console.log('Loading existing design into editor:', currentDesign.id)
        await editorSDK.value.loadDesign(currentDesign)
      } else {
        console.log('No current design found, editor initialized with empty canvas')
        // Editor is ready to use even without a design
        // The SDK will handle layer creation when they're added
      }

      // Start auto-save only after editor is fully initialized and design is loaded
      setTimeout(() => {
        startAutoSave()
      }, 1000) // Small delay to ensure loading is complete

    } catch (error) {
      console.error('Error initializing editor SDK:', error)
    } finally {
      isInitializing.value = false
    }
  }

  const setupSDKEventListeners = () => {
    if (!editorSDK.value) return

    console.log('Setting up SDK event listeners')

    // Layer events
    editorSDK.value.on('layer:created', async (layer: Layer) => {
      console.log('🎯 Event received: layer:created', layer)
      // Only process if not loading a design to prevent circular saves
      if (!editorSDK.value?.isLoading()) {
        // Normal layer creation - persist to backend
        const result = await designStore.addLayer(layer)
        if (result.success && result.layer && result.layer.id !== layer.id && editorSDK.value) {
          // Update the SDK layer with the new ID from backend
          console.log(`🔄 Updating layer ID from ${layer.id} to ${result.layer.id}`)
          editorSDK.value.layers.updateLayerId(layer.id, result.layer.id)
        }
        hasUnsavedChanges.value = true
        saveError.value = false // Clear previous save errors
        console.log('📦 Design store layers after add:', designStore.currentDesign?.layers)
        
        // Trigger thumbnail generation
        debouncedThumbnailGeneration()
        
        // Add to history
        if (designStore.currentDesign) {
          addLayerHistoryEntry(designStore.currentDesign, 'add', layer.name || `${layer.type} layer`)
        }
      } else {
        // During design loading - skip persistence to avoid circular saves
        designStore.addLayer(layer, { skipPersistence: true })
        console.log('📦 Layer added during loading (skipped persistence):', layer.id)
      }
    })

    editorSDK.value.on('layer:updated', (layer: Layer) => {
      
      // Only process if not loading a design to prevent circular saves
      if (!editorSDK.value?.isLoading()) {
        designStore.updateLayer(layer.id, layer, { skipPersistence: false })
        hasUnsavedChanges.value = true
        saveError.value = false // Clear previous save errors
        console.log('📦 Layer updated in store with backend persistence')
        
        // Trigger thumbnail generation
        debouncedThumbnailGeneration()
        
        // Add to history
        if (designStore.currentDesign) {
          addLayerHistoryEntry(designStore.currentDesign, 'modify', layer.name || `${layer.type} layer`)
        }
      } else {
        console.log('📦 Layer updated during loading (ignored to prevent circular saves)')
      }
    })

    editorSDK.value.on('layer:deleted', (layerId: number) => {
      console.log('🎯 Event received: layer:deleted', layerId)
      // Only process if not loading a design to prevent circular saves
      if (!editorSDK.value?.isLoading()) {
        // Store the layer name before deletion for history
        const layerName = designStore.currentDesign?.layers?.find(l => l.id === layerId)?.name || 'Unknown layer'
        
        designStore.removeLayer(layerId)
        hasUnsavedChanges.value = true
        saveError.value = false // Clear previous save errors
        
        // Trigger thumbnail generation
        debouncedThumbnailGeneration()
        
        // Add to history
        if (designStore.currentDesign) {
          addLayerHistoryEntry(designStore.currentDesign, 'delete', layerName)
        }
      }
    })

    // Selection events
    editorSDK.value.on('selection:changed', (layerIds: number[]) => {
      console.log('🎯 Event received: selection:changed', layerIds)
      // Selection changes don't affect unsaved state during loading
      designStore.selectedLayerIds = layerIds
    })

    // Canvas events
    editorSDK.value.on('canvas:changed', () => {
      // Only mark as changed if not loading a design
      if (!editorSDK.value?.isLoading()) {
        hasUnsavedChanges.value = true
        
        // Add to history
        if (designStore.currentDesign) {
          addCanvasHistoryEntry(designStore.currentDesign, 'settings')
        }
      }
    })

    // Design events
    editorSDK.value.on('design:loaded', (design: any) => {
      console.log('🎯 Event received: design:loaded', design)
      
      // Sync store with loaded design state (without triggering saves)
      if (design.layers && Array.isArray(design.layers)) {
        // Update store layers directly to match what was loaded into the editor
        if (designStore.currentDesign) {
          designStore.currentDesign.layers = [...design.layers]
          console.log('📦 Store synced with loaded layers:', design.layers.length)
        }
      }
      
      // Only initialize history if we're not performing an undo/redo operation
      if (!isPerformingHistoryOperation.value && designStore.currentDesign) {
        initializeHistory(designStore.currentDesign, 'Design loaded')
        console.log('📋 History initialized with loaded design')
      } else if (isPerformingHistoryOperation.value) {
        console.log('📋 Skipped history initialization during undo/redo operation')
      }
      
      // Clear unsaved changes since we just loaded a fresh design
      hasUnsavedChanges.value = false
      
      // Emit auto-fit event only if we're not performing a history operation
      if (typeof window !== 'undefined' && !isPerformingHistoryOperation.value) {
        const autoFitEvent = new CustomEvent('editor:auto-fit-request', { 
          detail: { reason: 'design-loaded', design } 
        })
        document.dispatchEvent(autoFitEvent)
        console.log('📐 Auto-fit request sent for design loading')
      } else if (isPerformingHistoryOperation.value) {
        console.log('📐 Auto-fit skipped during undo/redo operation to prevent flashing')
      }
    })

    // Context menu events
    editorSDK.value.on('layer:context-menu', (data: any) => {
      console.log('🎯 Composable: Received layer:context-menu event', {
        data,
        hasLayer: !!data.layer,
        layerName: data.layer?.name || 'NO LAYER',
        layerId: data.layer?.id || 'NO ID',
        layerType: data.layer?.type || 'NO TYPE'
      })
      // Emit the event to be handled by the UI components
      // The data contains: { event, layer, position }
      if (typeof window !== 'undefined') {
        const customEvent = new CustomEvent('editor:context-menu', { 
          detail: data 
        })
        console.log('🎯 Composable: Dispatching editor:context-menu event with layer:', data.layer?.name || 'NO LAYER')
        document.dispatchEvent(customEvent)
      }
    })
  }

  const saveDesign = async (showNotification: boolean = true) => {
    if (!designStore.currentDesign) return
    
    try {
      saveError.value = false
      const result = await designStore.saveDesign(undefined, showNotification)
      if (result.success) {
        hasUnsavedChanges.value = false
        
      } else {
        saveError.value = true
        throw new Error(result.error || 'Save failed')
      }
    } catch (error) {
      console.error('Failed to save design:', error)
      saveError.value = true
      throw error
    }
  }

  const exportDesign = async (format: string) => {
    if (!designStore.currentDesign) return
    
    try {
      await designStore.exportDesign(designStore.currentDesign.id, format as any)
    } catch (error) {
      console.error('Export failed:', error)
      throw error
    }
  }

  const undo = async () => {
    if (!canUndo.value) return
    
    console.log('🔄 Starting undo operation...')
    isPerformingHistoryOperation.value = true
    
    const previousDesign = historyUndo()
    if (previousDesign && designStore.currentDesign && editorSDK.value) {
      // Store current layers for comparison
      const oldLayers = designStore.currentDesign.layers || []
      
      // Update the store with the previous design state
      if (previousDesign.layers) {
        designStore.currentDesign.layers = [...previousDesign.layers]
      }
      console.log('📦 Design store layers after undo:', previousDesign.layers)
      
      // Reload the design in the editor (loadDesign will reset the flag)
      try {
        await editorSDK.value.loadDesign(previousDesign)
        console.log('🔄 Design state restored after undo operation')
        
        // Save changed layers to backend
        await saveChangedLayersToBackend(oldLayers, previousDesign.layers || [])
      } catch (error) {
        console.error('Failed to reload design after undo:', error)
        isPerformingHistoryOperation.value = false
      }
      
      console.log('✨ Undo operation initiated - design state will be restored')
    } else {
      isPerformingHistoryOperation.value = false
    }
  }

  const redo = async () => {
    if (!canRedo.value) return
    
    console.log('🔄 Starting redo operation...')
    isPerformingHistoryOperation.value = true
    
    const nextDesign = historyRedo()
    if (nextDesign && designStore.currentDesign && editorSDK.value) {
      // Store current layers for comparison
      const oldLayers = designStore.currentDesign.layers || []
      
      // Update the store with the next design state
      if (nextDesign.layers) {
        designStore.currentDesign.layers = [...nextDesign.layers]
      }
      
      // Reload the design in the editor (loadDesign will reset the flag)
      try {
        await editorSDK.value.loadDesign(nextDesign)
        console.log('🔄 Design state restored after redo operation')
        
        // Save changed layers to backend
        await saveChangedLayersToBackend(oldLayers, nextDesign.layers || [])
      } catch (error) {
        console.error('Failed to reload design after redo:', error)
        isPerformingHistoryOperation.value = false
      }
      
      hasUnsavedChanges.value = true
      console.log('✨ Redo operation initiated - design state will be restored')
    } else {
      isPerformingHistoryOperation.value = false
    }
  }

  /**
   * Save changed layers to backend after undo/redo operations
   */
  const saveChangedLayersToBackend = async (oldLayers: Layer[], newLayers: Layer[]): Promise<void> => {
    if (!designStore.currentDesign?.id) return

    try {
      // Create maps for quick lookup
      const oldLayerMap = new Map(oldLayers.map(layer => [layer.id, layer]))
      const newLayerMap = new Map(newLayers.map(layer => [layer.id, layer]))

      // Find deleted layers
      const deletedLayerIds = oldLayers
        .filter(layer => !newLayerMap.has(layer.id))
        .map(layer => layer.id)

      // Find added layers
      const addedLayers = newLayers
        .filter(layer => !oldLayerMap.has(layer.id))

      // Find modified layers
      const modifiedLayers = newLayers
        .filter(layer => {
          const oldLayer = oldLayerMap.get(layer.id)
          return oldLayer && JSON.stringify(oldLayer) !== JSON.stringify(layer)
        })

      console.log('📊 Layer changes detected:', {
        deleted: deletedLayerIds.length,
        added: addedLayers.length,
        modified: modifiedLayers.length
      })

      // Save changes to backend using the API
      const promises: Promise<any>[] = []

      // Delete removed layers
      for (const layerId of deletedLayerIds) {
        if (layerId > 0) { // Only delete layers that exist in backend (positive IDs)
          promises.push(layerAPI.deleteLayer(layerId))
        }
      }

      // Create new layers
      for (const layer of addedLayers) {
        if (layer.id < 0) { // Only create temporary layers (negative IDs)
          // Skip SVG layers for now as backend doesn't support them yet
          if (layer.type === 'svg') continue
          
          promises.push(layerAPI.createLayer({
            designId: designStore.currentDesign.id,
            type: layer.type as 'text' | 'image' | 'shape' | 'group' | 'video' | 'audio',
            name: layer.name,
            properties: layer.properties,
            transform: layer.transform,
            visible: layer.visible,
            locked: layer.locked,
            zIndex: layer.zIndex
          }))
        }
      }

      // Update modified layers
      for (const layer of modifiedLayers) {
        if (layer.id > 0) { // Only update layers that exist in backend
          promises.push(layerAPI.updateLayer(layer.id, {
            name: layer.name,
            properties: layer.properties,
            transform: layer.transform,
            visible: layer.visible,
            locked: layer.locked,
            zIndex: layer.zIndex
          }))
        }
      }

      // Execute all API calls
      if (promises.length > 0) {
        await Promise.allSettled(promises)
        console.log('✅ Layer changes saved to backend')
      }

    } catch (error) {
      console.error('Failed to save layer changes to backend:', error)
      // Don't throw - continue with local changes even if backend save fails
    }
  }

  const startAutoSave = () => {
    // Don't start auto-save if it's already running
    if (autoSaveInterval) return
    
    autoSaveInterval = setInterval(() => {
      // Only auto-save if we have unsaved changes and we're not currently loading a design
      if (hasUnsavedChanges.value && designStore.currentDesign && !editorSDK.value?.isLoading()) {
        console.log('🔄 Auto-saving design...')
        saveDesign(false) // Don't show notifications for autosave
      }
    }, 30000) // Auto-save every 30 seconds
    console.log('✅ Auto-save started')
  }

  const stopAutoSave = () => {
    if (autoSaveInterval) {
      clearInterval(autoSaveInterval)
      autoSaveInterval = null as any
      console.log('🛑 Auto-save stopped')
    }
  }

  const stopThumbnailGeneration = () => {
    if (thumbnailUpdateTimeout) {
      clearTimeout(thumbnailUpdateTimeout)
      thumbnailUpdateTimeout = null as any
      console.log('🛑 Thumbnail generation stopped')
    }
  }

  const loadDesign = async (designId?: string) => {
    const id = designId || route.params.id as string
    
    try {
      if (id) {
        const result = await designStore.loadDesign(id)
        if (!result.success) {
          console.error('Failed to load design:', result.error)
          return result // Return the failed result
        }
        
        // Load the design into the EditorSDK after store loads it
        if (result.design && editorSDK.value) {
          console.log('Loading design into EditorSDK:', result.design.id)
          await editorSDK.value.loadDesign(result.design)
        }
        
        return result // Return the successful result
      } else {
        const newDesign = designStore.createNewDesign()
        return { success: true, design: newDesign } // Return new design result
      }
    } finally {
      // Always reset the history operation flag when loadDesign completes
      isPerformingHistoryOperation.value = false
    }
  }

  const cleanup = async () => {
    // Cleanup SDK
    if (editorSDK.value) {
      editorSDK.value.destroy()
      editorSDK.value = null
    }
    
    stopAutoSave()
    stopThumbnailGeneration()
    
    // Save before leaving
    if (hasUnsavedChanges.value && designStore.currentDesign) {
      await saveDesign(false) // Don't show notifications for cleanup autosave
    }
    designStore.clearCurrentDesign()
  }

  return {
    editorSDK: computed(() => editorSDK.value) as ComputedRef<EditorSDK | null>,
    isInitializing: computed(() => isInitializing.value),
    hasUnsavedChanges: computed(() => hasUnsavedChanges.value),
    saveError: computed(() => saveError.value),
    isPerformingHistoryOperation: computed(() => isPerformingHistoryOperation.value),
    canUndo,
    canRedo,
    initializeEditor,
    loadDesign,
    saveDesign,
    exportDesign,
    undo,
    redo,
    cleanup
  }
}

import { ref, computed, nextTick, type ComputedRef } from 'vue'
import { useRoute } from 'vue-router'
import { useDesignStore } from '@/stores/design'
import { useDesignHistory } from '@/composables/useDesignHistory'
import { useDesignPreview } from '@/composables/useDesignPreview'
import { EditorSDK } from '@/editor/sdk/EditorSDK'
import { layerAPI } from '@/services/api'
import { GeometryUtils } from '@/utils/GeometryUtils'
import type { EditorConfig } from '@/editor/sdk/types'
import type { Layer, Design } from '@/types'

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

  // Auto-save functionality and sync status tracking
  let autoSaveInterval: ReturnType<typeof setInterval>
  let thumbnailUpdateTimeout: ReturnType<typeof setTimeout>
  let layerUpdateTimeout: ReturnType<typeof setTimeout>
  const pendingLayerUpdates = new Map<number, Layer>() // Store pending updates by layer ID
  
  // Enhanced sync status tracking
  const pendingSyncOperations = new Set<number>() // Track layers with pending sync operations
  const failedSyncLayers = new Set<number>() // Track layers that failed to sync
  const temporaryLayers = new Set<number>() // Track layers that haven't been saved to backend yet

  // Utility functions for sync status management
  const markLayerAsPending = (layerId: number) => {
    pendingSyncOperations.add(layerId)
    if (designStore.currentDesign?.layers) {
      const layer = designStore.currentDesign.layers.find(l => l.id === layerId)
      if (layer) {
        layer.syncStatus = 'pending'
        layer.lastSyncAttempt = new Date().toISOString()
      }
    }
  }

  const markLayerAsSynced = (layerId: number) => {
    pendingSyncOperations.delete(layerId)
    failedSyncLayers.delete(layerId)
    temporaryLayers.delete(layerId)
    if (designStore.currentDesign?.layers) {
      const layer = designStore.currentDesign.layers.find(l => l.id === layerId)
      if (layer) {
        layer.syncStatus = 'synced'
        layer.isTemporary = false
        layer.syncError = undefined
      }
    }
  }

  const markLayerAsFailed = (layerId: number, error: string) => {
    pendingSyncOperations.delete(layerId)
    failedSyncLayers.add(layerId)
    if (designStore.currentDesign?.layers) {
      const layer = designStore.currentDesign.layers.find(l => l.id === layerId)
      if (layer) {
        layer.syncStatus = 'failed'
        layer.syncError = error
        layer.lastSyncAttempt = new Date().toISOString()
      }
    }
  }

  const markLayerAsTemporary = (layerId: number) => {
    temporaryLayers.add(layerId)
    if (designStore.currentDesign?.layers) {
      const layer = designStore.currentDesign.layers.find(l => l.id === layerId)
      if (layer) {
        layer.isTemporary = true
        layer.syncStatus = 'pending'
      }
    }
  }

  const markLayerAsDeleting = (layerId: number) => {
    pendingSyncOperations.add(layerId)
    if (designStore.currentDesign?.layers) {
      const layer = designStore.currentDesign.layers.find(l => l.id === layerId)
      if (layer) {
        layer.syncStatus = 'deleting'
        layer.lastSyncAttempt = new Date().toISOString()
      }
    }
  }

  // Helper function to update layer ID in all tracking sets
  const updateLayerIdInTrackingSets = (oldId: number, newId: number) => {
    // Update tracking sets
    if (pendingSyncOperations.has(oldId)) {
      pendingSyncOperations.delete(oldId)
      pendingSyncOperations.add(newId)
    }
    if (failedSyncLayers.has(oldId)) {
      failedSyncLayers.delete(oldId)
      failedSyncLayers.add(newId)
    }
    if (temporaryLayers.has(oldId)) {
      temporaryLayers.delete(oldId)
      temporaryLayers.add(newId)
    }
    
    // Update pending layer updates map
    if (pendingLayerUpdates.has(oldId)) {
      const layerData = pendingLayerUpdates.get(oldId)
      if (layerData) {
        layerData.id = newId
        pendingLayerUpdates.delete(oldId)
        pendingLayerUpdates.set(newId, layerData)
      }
    }
  }

  // Helper function to initialize sync status for loaded designs
  const initializeSyncStatusForLoadedDesign = (design: any) => {
    console.log('🔄 Initializing sync status for loaded design')
    
    // Clear all tracking sets since this is a fresh load
    pendingSyncOperations.clear()
    failedSyncLayers.clear()
    temporaryLayers.clear()
    pendingLayerUpdates.clear()
    
    // Mark design as synced
    if (designStore.currentDesign) {
      designStore.currentDesign.syncStatus = 'synced'
      designStore.currentDesign.isTemporary = false
      designStore.currentDesign.syncError = undefined
    }
    
    // Mark all layers as synced
    if (design.layers && Array.isArray(design.layers)) {
      design.layers.forEach((layer: Layer) => {
        layer.syncStatus = 'synced'
        layer.isTemporary = false
        layer.syncError = undefined
        console.log(`📦 Layer ${layer.id} (${layer.name}) marked as synced`)
      })
    }
    
    console.log('✅ Sync status initialized for loaded design')
  }

  // Enhanced sync status getters
  const getUnsyncedLayers = computed(() => {
    if (!designStore.currentDesign?.layers) return []
    return designStore.currentDesign.layers.filter(layer => {
      // Skip layers that are clearly synced from backend (positive IDs with synced status)
      if (layer.id > 0 && layer.syncStatus === 'synced') {
        return false
      }
      
      // Check for unsynced conditions
      return layer.syncStatus !== 'synced' || 
             pendingSyncOperations.has(layer.id) ||
             failedSyncLayers.has(layer.id) ||
             temporaryLayers.has(layer.id)
    })
  })

  const hasUnsyncedChanges = computed(() => {
    return hasUnsavedChanges.value || 
           getUnsyncedLayers.value.length > 0 ||
           designStore.currentDesign?.syncStatus !== 'synced'
  })

  // Enhanced computed for failed sync layers to ensure UI reactivity
  const failedSyncLayersComputed = computed(() => {
    return Array.from(failedSyncLayers).filter(layerId => 
      designStore.currentDesign?.layers?.some(layer => layer.id === layerId)
    )
  })

  // Retry mechanism for failed sync operations
  const retryFailedSyncs = async () => {
    const failedLayers = Array.from(failedSyncLayers)
    console.log(`🔄 Retrying sync for ${failedLayers.length} failed layers`)
    
    for (const layerId of failedLayers) {
      const layer = designStore.currentDesign?.layers?.find(l => l.id === layerId)
      if (layer) {
        console.log(`🔄 Retrying sync for layer: ${layer.name} (${layerId})`)
        
        // Clear the failed status before retrying
        failedSyncLayers.delete(layerId)
        
        // Queue for retry
        debouncedLayerUpdate(layer)
      }
    }
  }

  // Auto-retry mechanism - retry failed syncs when connection is restored
  const setupAutoRetry = () => {
    // Listen for online/offline events
    const handleOnline = () => {
      console.log('🌐 Network connection restored, retrying failed syncs')
      if (failedSyncLayers.size > 0) {
        retryFailedSyncs()
      }
    }
    
    window.addEventListener('online', handleOnline)
    
    // Cleanup function
    return () => {
      window.removeEventListener('online', handleOnline)
    }
  }

  // Debounced thumbnail generation to avoid excessive API calls
  const debouncedThumbnailGeneration = (triggerSource?: string) => {
    // Skip thumbnail generation for design name changes
    if (triggerSource === 'name-change') {
      return
    }
    
    if (thumbnailUpdateTimeout) {
      clearTimeout(thumbnailUpdateTimeout)
    }
    
    thumbnailUpdateTimeout = setTimeout(async () => {
      const currentDesign = designStore.currentDesign
      if (currentDesign && currentDesign.id && !editorSDK.value?.isLoading()) {
        try {
          console.log('🖼️ Generating design thumbnail...')
          
          // Calculate proper thumbnail dimensions while preserving aspect ratio
          const designDimensions = {
            width: currentDesign.width || 800,
            height: currentDesign.height || 600
          }
          
          // Target maximum thumbnail size from environment variables
          const maxThumbnailSize = { 
            width: Number(import.meta.env.VITE_THUMBNAIL_MAX_WIDTH) || 400, 
            height: Number(import.meta.env.VITE_THUMBNAIL_MAX_HEIGHT) || 300 
          }
          
          // Use GeometryUtils to calculate dimensions that preserve aspect ratio
          const thumbnailResult = GeometryUtils.resize(designDimensions, maxThumbnailSize, {
            mode: 'contain',
            allowUpscaling: false // Don't upscale small designs
          })
          
          await generateAndSaveThumbnail(currentDesign, {
            width: Math.round(thumbnailResult.width),
            height: Math.round(thumbnailResult.height),
            format: 'jpeg',
            quality: 0.8,
            updateBackend: true
          })
        } catch (error) {
          console.error('Failed to generate thumbnail:', error)
        }
      }
    }, 1500) // Reduced from 3 seconds to 1.5 seconds for faster response
  }

  // Enhanced debounced layer update with sync status tracking
  const debouncedLayerUpdate = (layer: Layer) => {
    // Store the latest layer state
    pendingLayerUpdates.set(layer.id, layer)
    
    // Mark layer as pending sync
    markLayerAsPending(layer.id)
    
    if (layerUpdateTimeout) {
      clearTimeout(layerUpdateTimeout)
    } 
    
    layerUpdateTimeout = setTimeout(async () => {
      console.log('🔄 Processing debounced layer updates...')
      
      // Process all pending layer updates
      const updates = Array.from(pendingLayerUpdates.entries())
      pendingLayerUpdates.clear()
      
      for (const [layerId, layerData] of updates) {
        try {
          // Check if this is a temporary layer (needs CREATE) or existing layer (needs UPDATE)
          const isTemporary = temporaryLayers.has(layerId)
          
          if (isTemporary) {
            // Use CREATE endpoint for temporary layers - but DON'T call addLayer again
            // Instead, directly call the layer API to create in backend
            const response = await layerAPI.createLayer({
              designId: designStore.currentDesign!.id,
              type: layerData.type as 'text' | 'image' | 'shape' | 'group' | 'video' | 'audio',
              name: layerData.name,
              properties: layerData.properties,
              transform: layerData.transform,
              visible: layerData.visible,
              locked: layerData.locked,
              zIndex: layerData.zIndex
            })
            
            // Check if the API response indicates success
            const responseData = response.data as any
            if (responseData?.success && responseData?.data?.layer) {
              const backendLayer = responseData.data.layer
              const oldId = layerId
              const newId = backendLayer.id
              
              console.log(`📦 Temporary layer created in backend: ${oldId} -> ${newId}`)
              
              // Update the layer ID in the design store
              if (designStore.currentDesign?.layers) {
                const layerIndex = designStore.currentDesign.layers.findIndex(l => l.id === oldId)
                if (layerIndex !== -1) {
                  designStore.currentDesign.layers[layerIndex].id = newId
                  // Also update any other properties that might have changed
                  Object.assign(designStore.currentDesign.layers[layerIndex], backendLayer)
                }
              }
              
              // Update tracking sets
              updateLayerIdInTrackingSets(oldId, newId)
              
              // Update the layer in the SDK
              if (editorSDK.value?.layers?.updateLayerId) {
                editorSDK.value.layers.updateLayerId(oldId, newId)
              }
              
              // Mark as synced with new ID
              markLayerAsSynced(newId)
            } else {
              // Backend returned error response
              const errorMessage = responseData?.error || responseData?.message || 'Backend layer creation failed'
              throw new Error(errorMessage)
            }
          } else {
            // Use UPDATE endpoint for existing layers
            const result = await designStore.updateLayer(layerId, layerData, { skipPersistence: false })
            
            if (result.success) {
              console.log('📦 Layer updated in backend:', layerId)
              markLayerAsSynced(layerId)
            } else {
              // Design store returned error
              throw new Error(result.error || 'Layer update failed')
            }
          }
        } catch (error: any) {
          console.error('Failed to sync layer:', layerId, error)
          const errorMessage = error.response?.data?.message || error.message || 'Network error'
          markLayerAsFailed(layerId, errorMessage)
          
          // Show user notification for sync failure
          if (error.name === 'NetworkError' || error.code === 'ECONNREFUSED') {
            console.warn('🌐 Network connectivity issue detected for layer sync')
          }
        }
      }
      
      hasUnsavedChanges.value = true
      saveError.value = false // Clear previous save errors
      
      // Trigger thumbnail generation after all layer updates
      debouncedThumbnailGeneration('layer-change')
    }, 500) // Wait 500ms after last change before persisting to backend
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

  // Helper function to handle layer changes with proper history tracking
  const handleLayerChangeWithHistory = (
    action: 'add' | 'update' | 'delete', 
    layerOrId: Layer | number, 
    additionalUpdates?: () => void
  ) => {
    if (!designStore.currentDesign) return

    let layerName = ''
    
    // Handle different actions
    switch (action) {
      case 'update':
        if (typeof layerOrId === 'object') {
          const layer = layerOrId as Layer
          layerName = layer.name || `${layer.type} layer`
          
          // Update the design store immediately
          const layerIndex = designStore.currentDesign.layers?.findIndex(l => l.id === layer.id)
          if (layerIndex !== undefined && layerIndex >= 0 && designStore.currentDesign.layers) {
            //designStore.currentDesign.layers[layerIndex] = { ...layer }
            console.log('Updating layer with object:', layer)
            designStore.currentDesign.updatedAt = new Date().toISOString()
          }
        }
        break
        
      case 'delete':
        if (typeof layerOrId === 'number') {
          const layerId = layerOrId as number
          // Store layer name before deletion
          layerName = designStore.currentDesign.layers?.find(l => l.id === layerId)?.name || 'Unknown layer'
          
          // Remove from store
          designStore.removeLayer(layerId)
        }
        break
        
      case 'add':
        if (typeof layerOrId === 'object') {
          const layer = layerOrId as Layer
          layerName = layer.name || `${layer.type} layer`
          // Add logic is already handled in layer:created event
        }
        break
    }
    
    // Execute any additional updates
    if (additionalUpdates) {
      additionalUpdates()
    }
    
    // Mark as having unsaved changes
    hasUnsavedChanges.value = true
    saveError.value = false
    
    // Trigger thumbnail generation
    debouncedThumbnailGeneration('layer-add')
    
    // Add to history with updated design data
    const actionMap = {
      add: 'add' as const,
      update: 'modify' as const, 
      delete: 'delete' as const
    }
    
    addLayerHistoryEntry(designStore.currentDesign, actionMap[action], layerName)
  }

  const setupSDKEventListeners = () => {
    if (!editorSDK.value) return

    console.log('Setting up SDK event listeners')

    // Enhanced layer events with sync status tracking
    editorSDK.value.on('layer:created', async (layer: Layer) => {
      console.log('🎯 Event received: layer:created', layer)
      // Only process if not loading a design to prevent circular saves
      if (!editorSDK.value?.isLoading()) {
        
        // Generate temporary ID for new layers (negative to distinguish from backend IDs)
        const tempId = layer.id > 0 ? -Date.now() : layer.id
        if (layer.id > 0) {
          layer.id = tempId
        }
        
        // Mark as temporary layer
        markLayerAsTemporary(layer.id)
        
        // Add to store without immediate backend persistence
        const result = await designStore.addLayer(layer, { skipPersistence: true })
        
        // Check the result from the design store
        if (result.success && result.layer) {
          // Update layer ID if backend assigned a new one
          if (result.layer.id !== layer.id) {
            const oldId = layer.id
            const newId = result.layer.id
            console.log(`🔄 Updating temporary layer ID from ${oldId} to ${newId}`)
            
            // Update tracking sets with new ID
            updateLayerIdInTrackingSets(oldId, newId)
            
            // Update the layer object
            layer.id = newId
            
            // Update the layer in the SDK if it has an updateLayerId method
            if (editorSDK.value?.layers?.updateLayerId) {
              editorSDK.value.layers.updateLayerId(oldId, newId)
            }
          }
        } else {
          console.warn('Layer addition to store failed:', result.error)
        }
        
        // Set up debounced persistence with sync tracking
        debouncedLayerUpdate(layer)
        
        hasUnsavedChanges.value = true
        saveError.value = false // Clear previous save errors
        console.log('📦 Design store layers after add:', designStore.currentDesign?.layers)
        
        // Trigger thumbnail generation
        debouncedThumbnailGeneration('layer-add')
        
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
      console.log('🎯 Event received: layer:updated', layer)
      // Only process if not loading a design to prevent circular saves
      if (!editorSDK.value?.isLoading()) {
        
        // Handle layer update with proper history tracking and sync status
        handleLayerChangeWithHistory('update', layer, () => {
          // Use debounced update for backend persistence with sync tracking
          debouncedLayerUpdate(layer)
        })
        
      } else {
        console.log('📦 Layer updated during loading (ignored to prevent circular saves)')
      }
    })

    editorSDK.value.on('layer:deleted', async (layerId: number) => {
      console.log('🎯 Event received: layer:deleted', layerId)
      // Only process if not loading a design to prevent circular saves
      if (!editorSDK.value?.isLoading()) {
        
        // Mark layer as being deleted
        markLayerAsDeleting(layerId)
        
        try {
          // Delete from backend only if not a temporary layer
          if (!temporaryLayers.has(layerId)) {
            const result = await designStore.removeLayer(layerId)
            
            if (result.success) {
              console.log('📦 Layer deleted from backend:', layerId)
            } else {
              // Design store returned error - but might be network vs validation error
              const errorMessage = result.error || 'Delete failed'
              console.error('Backend layer deletion failed:', errorMessage)
              
              // Check if it's a network error vs validation/permission error
              const isNetworkError = errorMessage.includes('Network Error') ||
                                    errorMessage.includes('timeout') ||
                                    errorMessage.includes('ECONNREFUSED')
              
              if (!isNetworkError) {
                // Validation/permission error - don't delete locally, mark as failed
                markLayerAsFailed(layerId, errorMessage)
                return // Exit early, don't delete locally
              }
              
              // Network error - continue with local deletion but mark as failed
              console.warn('Network error during deletion, proceeding with local removal')
              throw new Error(errorMessage)
            }
          } else {
            // Just remove from local state for temporary layers
            // Find and remove the layer from the current design layers array
            if (designStore.currentDesign?.layers) {
              const layerIndex = designStore.currentDesign.layers.findIndex(l => l.id === layerId)
              if (layerIndex !== -1) {
                designStore.currentDesign.layers.splice(layerIndex, 1)
              }
            }
            console.log('📦 Temporary layer removed locally:', layerId)
          }
          
          // Clean up tracking sets
          temporaryLayers.delete(layerId)
          pendingSyncOperations.delete(layerId)
          failedSyncLayers.delete(layerId)
          
        } catch (error: any) {
          console.error('Failed to delete layer from backend:', layerId, error)
          const errorMessage = error.message || 'Network error'
          markLayerAsFailed(layerId, `Delete failed: ${errorMessage}`)
          
          // Note: Layer remains visible in UI with failed sync status
          // User can retry deletion later when network is restored
        }
        
        // Handle layer deletion with proper history tracking
        handleLayerChangeWithHistory('delete', layerId)
        
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
           debouncedThumbnailGeneration('canvas-change')
          addCanvasHistoryEntry(designStore.currentDesign, 'settings')
        }
      }
    })

    // Design events
    editorSDK.value.on('design:loaded', (design: any) => {
      console.log('🎯 Event received: design:loaded', design)
      
      // Only initialize sync status if we're not performing a loadDesignFromData operation
      if (!isPerformingHistoryOperation.value) {
        // Initialize sync status for all loaded content (clears pending states)
        initializeSyncStatusForLoadedDesign(design)
      } else {
        console.log('📦 Skipping sync status reset during loadDesignFromData operation')
      }
      
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
      
      // Mark the design itself as synced
      if (designStore.currentDesign) {
        designStore.currentDesign.syncStatus = 'synced'
        designStore.currentDesign.isTemporary = false
        designStore.currentDesign.syncError = undefined
        console.log('📦 Design marked as synced')
      }
      
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
    
    if (layerUpdateTimeout) {
      clearTimeout(layerUpdateTimeout)
      layerUpdateTimeout = null as any
      console.log('🛑 Layer update debouncing stopped')
    }
    
    // Clear any pending layer updates
    pendingLayerUpdates.clear()
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
        
        // Initialize sync status for new design
        initializeSyncStatusForLoadedDesign(newDesign)
        
        return { success: true, design: newDesign } // Return new design result
      }
    } finally {
      // Always reset the history operation flag when loadDesign completes
      isPerformingHistoryOperation.value = false
    }
  }

  // Helper function to create multiple layers in backend in bulk
  const createLayersInBackend = async (layers: Layer[]): Promise<void> => {
    if (!designStore.currentDesign?.id || layers.length === 0) return
    
    console.log(`🔄 Creating ${layers.length} layers in backend...`)
    
    for (const layer of layers) {
      try {
       
        
        const response = await layerAPI.createLayer({
          designId: designStore.currentDesign.id,
          type: layer.type,
          name: layer.name,
          properties: layer.properties,
          transform: layer.transform,
          visible: layer.visible,
          locked: layer.locked,
          zIndex: layer.zIndex
        })
        
        // Check if the API response indicates success
        const responseData = response.data as any
        if (responseData?.success && responseData?.data?.layer) {
          const backendLayer = responseData.data.layer
          const oldId = layer.id
          const newId = backendLayer.id
          
          console.log(`📦 Layer created in backend: "${layer.name}" ${oldId} -> ${newId}`)
          
          // Update the layer ID in the design store and layers array
          layer.id = newId
          if (designStore.currentDesign?.layers) {
            const layerIndex = designStore.currentDesign.layers.findIndex(l => l.id === oldId)
            if (layerIndex !== -1) {
              designStore.currentDesign.layers[layerIndex].id = newId
              // Also update any other properties that might have changed
              Object.assign(designStore.currentDesign.layers[layerIndex], backendLayer)
            }
          }
          
          // Update the layer in the SDK
          if (editorSDK.value?.layers?.updateLayerId) {
            editorSDK.value.layers.updateLayerId(oldId, newId)
          }
          
          // Mark layer properties as synced
          layer.syncStatus = 'synced'
          layer.isTemporary = false
          layer.syncError = undefined
          
        } else {
          // Backend returned error response
          const errorMessage = responseData?.error || responseData?.message || 'Backend layer creation failed'
          console.error(`Failed to create layer "${layer.name}":`, errorMessage)
          
          // Mark layer as failed
          layer.syncStatus = 'failed'
          layer.syncError = errorMessage
          layer.lastSyncAttempt = new Date().toISOString()
        }
      } catch (error: any) {
        console.error(`Failed to create layer "${layer.name}":`, error)
        const errorMessage = error.response?.data?.message || error.message || 'Network error'
        
        // Mark layer as failed
        layer.syncStatus = 'failed'
        layer.syncError = errorMessage
        layer.lastSyncAttempt = new Date().toISOString()
      }
    }
    
    console.log('✅ Bulk layer creation completed')
  }

  /**
   * Load a design directly from a Design object (e.g., from template data)
   * This method bypasses the backend API call and loads the provided design data directly
   * into both the store and the EditorSDK.
   * 
   * @param design - The Design object to load
   * @returns Promise with success status and design data
   */
  const loadDesignFromData = async (design: Design) => {
    try {
      console.log('Loading design from data into EditorSDK:', design.id)
      initializeSyncStatusForLoadedDesign(design)
      // Set flag to prevent sync status reset during SDK loading
      isPerformingHistoryOperation.value = true
      // Set the design in the store
      designStore.currentDesign = design
      // Order layers by zIndex before loading to ensure correct layer stacking
      if (design.layers && Array.isArray(design.layers)) {
        design.layers.sort((a, b) => (a.zIndex || 0) - (b.zIndex || 0))
        // Mark layers as temporary
        design.layers.forEach(layer => {
            markLayerAsTemporary(layer.id)
            debouncedLayerUpdate(layer)
        })
      }
      
      
      
      // Load the design into the EditorSDK (this will trigger design:loaded event but flag prevents sync reset)
      if (editorSDK.value) {
        await editorSDK.value.loadDesign(design)
        console.log('Design loaded successfully into EditorSDK:', design.id)
      } else {
        console.warn('EditorSDK not available, design loaded into store only')
      }
      
      
      
      return { success: true, design }
    } catch (error: any) {
      console.error('Failed to load design from data:', error)
      return { 
        success: false, 
        error: error.message || 'Failed to load design from data',
        design: undefined
      }
    } finally {
      // Always reset the history operation flag when loadDesignFromData completes
      isPerformingHistoryOperation.value = false
    }
  }

  // Set up auto-retry mechanism
  const cleanupAutoRetry = setupAutoRetry()

  const cleanup = async () => {
    // Cleanup SDK
    if (editorSDK.value) {
      editorSDK.value.destroy()
      editorSDK.value = null
    }
    
    stopAutoSave()
    stopThumbnailGeneration()
    cleanupAutoRetry() // Clean up auto-retry event listeners
    
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
    hasUnsyncedChanges, // Enhanced sync status
    saveError: computed(() => saveError.value),
    isPerformingHistoryOperation: computed(() => isPerformingHistoryOperation.value),
    canUndo,
    canRedo,
    // Sync status tracking
    getUnsyncedLayers,
    retryFailedSyncs,
    pendingSyncOperations: computed(() => Array.from(pendingSyncOperations)),
    failedSyncLayers: failedSyncLayersComputed,
    temporaryLayers: computed(() => Array.from(temporaryLayers)),
    initializeEditor,
    loadDesign,
    loadDesignFromData,
    saveDesign,
    exportDesign,
    undo,
    redo,
    cleanup
  }
}

import { ref, computed, nextTick, type ComputedRef } from 'vue'
import { useRoute } from 'vue-router'
import { useDesignStore } from '@/stores/design'
import { EditorSDK } from '@/editor/sdk/EditorSDK'
import type { EditorConfig } from '@/editor/sdk/types'
import type { Layer } from '@/types'

export function useDesignEditor() {
  const route = useRoute()
  const designStore = useDesignStore()

  // Refs
  const editorSDK = ref<EditorSDK | null>(null)
  const isInitializing = ref(false)
  const hasUnsavedChanges = ref(false)
  const saveError = ref(false)
  const canUndo = ref(false)
  const canRedo = ref(false)

  // Auto-save functionality
  let autoSaveInterval: ReturnType<typeof setInterval>

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
      } else {
        console.log('📦 Layer updated during loading (ignored to prevent circular saves)')
      }
    })

    editorSDK.value.on('layer:deleted', (layerId: number) => {
      console.log('🎯 Event received: layer:deleted', layerId)
      // Only process if not loading a design to prevent circular saves
      if (!editorSDK.value?.isLoading()) {
        designStore.removeLayer(layerId)
        hasUnsavedChanges.value = true
        saveError.value = false // Clear previous save errors
      }
    })

    // Selection events
    editorSDK.value.on('selection:changed', (layerIds: number[]) => {
      console.log('🎯 Event received: selection:changed', layerIds)
      // Selection changes don't affect unsaved state during loading
      designStore.selectedLayerIds = layerIds
    })

    // History events
    editorSDK.value.on('history:changed', (historyState: any) => {
      console.log('🎯 Event received: history:changed', historyState)
      canUndo.value = historyState.canUndo
      canRedo.value = historyState.canRedo
    })

    // Canvas events
    editorSDK.value.on('canvas:changed', () => {
      // Only mark as changed if not loading a design
      if (!editorSDK.value?.isLoading()) {
        hasUnsavedChanges.value = true
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
      
      // Clear unsaved changes since we just loaded a fresh design
      hasUnsavedChanges.value = false
      
      // Emit a custom event to trigger auto-fit in EditorLayout
      if (typeof window !== 'undefined') {
        const autoFitEvent = new CustomEvent('editor:auto-fit-request', { 
          detail: { reason: 'design-loaded', design } 
        })
        document.dispatchEvent(autoFitEvent)
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
        if (showNotification) {
          // Only show success notification for manual saves
          console.log('✅ Design saved successfully')
          // TODO: Add toast notification here for manual saves
        }
      } else {
        saveError.value = true
        throw new Error(result.error || 'Save failed')
      }
    } catch (error) {
      console.error('Failed to save design:', error)
      saveError.value = true
      if (showNotification) {
        // Only show error notification for manual saves
        // TODO: Add error toast notification here for manual saves
      }
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

  const undo = () => {
    if (!editorSDK.value || !canUndo.value) return
    editorSDK.value.undo()
  }

  const redo = () => {
    if (!editorSDK.value || !canRedo.value) return
    editorSDK.value.redo()
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

  const loadDesign = async (designId?: string) => {
    const id = designId || route.params.id as string
    
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
  }

  const cleanup = async () => {
    // Cleanup SDK
    if (editorSDK.value) {
      editorSDK.value.destroy()
      editorSDK.value = null
    }
    
    stopAutoSave()
    
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
    canUndo: computed(() => canUndo.value),
    canRedo: computed(() => canRedo.value),
    initializeEditor,
    loadDesign,
    saveDesign,
    exportDesign,
    undo,
    redo,
    cleanup
  }
}

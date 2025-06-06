import { ref, computed, nextTick, type ComputedRef } from 'vue'
import { useRoute } from 'vue-router'
import { useDesignStore } from '@/stores/design'
import { EditorSDK } from '@/editor/sdk/EditorSDK'
import type { EditorConfig } from '@/editor/sdk/types'

export function useDesignEditor() {
  const route = useRoute()
  const designStore = useDesignStore()

  // Refs
  const editorSDK = ref<EditorSDK | null>(null)
  const isInitializing = ref(false)
  const hasUnsavedChanges = ref(false)
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

      // Get current design dimensions
      const currentDesign = designStore.currentDesign
      const designWidth = currentDesign?.width || 800
      const designHeight = currentDesign?.height || 600

      // Use design dimensions for the stage (not container dimensions)
      // The container size will be handled by Vue's reactive styling
      const width = designWidth
      const height = designHeight

      console.log('Initializing EditorSDK:', {
        designDimensions: `${designWidth}x${designHeight}`,
        stageDimensions: `${width}x${height}`,
        containerElement: container
      })

      // Initialize SDK
      const config: EditorConfig = {
        container,
        width,
        height
      }

      editorSDK.value = new EditorSDK(config)

      // Setup SDK event listeners
      setupSDKEventListeners()

      // Load current design if exists
      if (currentDesign) {
        await editorSDK.value.loadDesign(currentDesign)
      }

      // Start auto-save
      startAutoSave()

    } catch (error) {
      console.error('Error initializing editor SDK:', error)
    } finally {
      isInitializing.value = false
    }
  }

  const setupSDKEventListeners = () => {
    if (!editorSDK.value) return

    // Layer events
    editorSDK.value.on('layer:created', (layer: any) => {
      designStore.addLayer(layer)
      hasUnsavedChanges.value = true
    })

    editorSDK.value.on('layer:updated', (layer: any) => {
      designStore.updateLayer(layer.id, layer)
      hasUnsavedChanges.value = true
    })

    editorSDK.value.on('layer:deleted', (layerId: string) => {
      designStore.removeLayer(layerId)
      hasUnsavedChanges.value = true
    })

    // Selection events
    editorSDK.value.on('selection:changed', (layerIds: string[]) => {
      designStore.selectedLayerIds = layerIds
    })

    // History events
    editorSDK.value.on('history:changed', (historyState: any) => {
      canUndo.value = historyState.canUndo
      canRedo.value = historyState.canRedo
    })

    // Canvas events
    editorSDK.value.on('canvas:changed', () => {
      hasUnsavedChanges.value = true
    })
  }

  const saveDesign = async () => {
    if (!designStore.currentDesign) return
    
    try {
      await designStore.saveDesign()
      hasUnsavedChanges.value = false
    } catch (error) {
      console.error('Failed to save design:', error)
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
    // TODO: Implement undo in EditorSDK
    console.log('Undo operation')
  }

  const redo = () => {
    if (!editorSDK.value || !canRedo.value) return
    // TODO: Implement redo in EditorSDK
    console.log('Redo operation')
  }

  const startAutoSave = () => {
    autoSaveInterval = setInterval(() => {
      if (hasUnsavedChanges.value && designStore.currentDesign) {
        saveDesign()
      }
    }, 30000) // Auto-save every 30 seconds
  }

  const stopAutoSave = () => {
    if (autoSaveInterval) {
      clearInterval(autoSaveInterval)
    }
  }

  const loadDesign = async (designId?: string) => {
    const id = designId || route.params.id as string
    
    if (id) {
      const result = await designStore.loadDesign(id)
      if (!result.success) {
        console.error('Failed to load design:', result.error)
        throw new Error(result.error)
      }
    } else {
      designStore.createNewDesign()
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
      await saveDesign()
    }
  }

  return {
    editorSDK: computed(() => editorSDK.value) as ComputedRef<EditorSDK | null>,
    isInitializing: computed(() => isInitializing.value),
    hasUnsavedChanges: computed(() => hasUnsavedChanges.value),
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

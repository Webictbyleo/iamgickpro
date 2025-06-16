import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import type { Design, DesignData, Layer, CreateLayerData, UpdateLayerData, DuplicateLayerData, Transform, LayerType } from '@/types'
import { designAPI, layerAPI } from '@/services/api'
import { useAuthStore } from '@/stores/auth'
import { useNotifications } from '@/composables/useNotifications'

// Backend layer response structure (matches ResponseDTOFactory output)
interface BackendLayerData {
  id: number
  uuid: string
  name: string
  type: string
  properties: any
  transform: {
    x?: number
    y?: number
    width?: number
    height?: number
    rotation?: number
    scaleX?: number
    scaleY?: number
    [key: string]: any
  }
  zIndex: number
  visible: boolean
  locked: boolean
  opacity: number
  animations?: any
  mask?: any
  design?: {
    id: number
    uuid: string
    name: string
  }
  parent?: {
    id: number
    uuid: string
    name: string
  }
  children?: Array<{
    id: number
    uuid: string
    name: string
    type: string
    zIndex: number
  }>
  createdAt: string
  updatedAt?: string
}

// Helper function to convert backend layer data to frontend Layer format
function backendToFrontendLayer(backendLayer: BackendLayerData): Layer {
  return {
    id: backendLayer.id, // Use numeric ID from backend
    type: backendLayer.type as LayerType,
    name: backendLayer.name,
    visible: backendLayer.visible,
    locked: backendLayer.locked,
    transform: {
      x: backendLayer.transform?.x || 0,
      y: backendLayer.transform?.y || 0,
      width: backendLayer.transform?.width || 100,
      height: backendLayer.transform?.height || 100,
      rotation: backendLayer.transform?.rotation || 0,
      scaleX: backendLayer.transform?.scaleX || 1,
      scaleY: backendLayer.transform?.scaleY || 1,
      opacity: backendLayer.transform?.opacity || backendLayer.opacity || 1
    },
    zIndex: backendLayer.zIndex,
    properties: backendLayer.properties
  }
}

// Add type definitions for API responses
interface ApiResponse<T> {
  data: T
  message?: string
}

// Define the paginated designs response structure
interface DesignsApiResponse {
  designs: Design[]
  pagination: {
    total: number
    page: number
    totalPages: number
  }
}

// Define valid export formats
type ExportFormat = 'png' | 'jpg' | 'jpeg' | 'pdf' | 'svg'

interface CreateDesignRequest {
  name: string
  width: number
  height: number
  data: DesignData
}

interface UpdateDesignRequest {
  name: string
  width: number
  height: number
  data: DesignData
  description?: string
  projectId?: number
}

export const useDesignStore = defineStore('design', () => {
  const { designSaved, saveFailed, designDeleted, designExported, exportFailed, showError } = useNotifications()
  
  const currentDesign = ref<Design | null>(null)
  const designs = ref<Design[]>([])
  const isLoading = ref(false)
  const error = ref<string | null>(null)
  
  const hasCurrentDesign = computed(() => !!currentDesign.value)

  // Helper functions to convert Layer data to API formats
  const layerToCreateLayerData = (layer: Layer, designId: string): CreateLayerData => {
    const transform: Transform = {
      x: layer.transform?.x || 0,
      y: layer.transform?.y || 0,
      width: layer.transform?.width || 100,
      height: layer.transform?.height || 100,
      rotation: layer.transform?.rotation || 0,
      scaleX: layer.transform?.scaleX || 1,
      scaleY: layer.transform?.scaleY || 1
    }

    return {
      designId,
      type: layer.type,
      name: layer.name,
      properties: layer.properties || {},
      transform,
      zIndex: layer.zIndex,
      visible: layer.visible !== false,
      locked: layer.locked || false
    }
  }

  const layerToUpdateLayerData = (updates: Partial<Layer>): UpdateLayerData => {
    const updateData: UpdateLayerData = {}

    if (updates.name !== undefined) updateData.name = updates.name
    if (updates.properties !== undefined){
      // Filter out empty values from properties
      updateData.properties = Object.fromEntries(
        Object.entries(updates.properties).filter(([_, value]) => value !== null && value !== undefined && value !== '')
      )
    }
    if (updates.zIndex !== undefined) updateData.zIndex = updates.zIndex
    if (updates.visible !== undefined) updateData.visible = updates.visible
    if (updates.locked !== undefined) updateData.locked = updates.locked



    // Handle transform properties
    if (updates.transform) {
      const transform = updates.transform
      if (transform.x !== undefined || transform.y !== undefined || 
          transform.width !== undefined || transform.height !== undefined ||
          transform.rotation !== undefined || transform.scaleX !== undefined ||
          transform.scaleY !== undefined) {
        updateData.transform = {
          x: transform.x,
          y: transform.y,
          width: transform.width,
          height: transform.height,
          rotation: transform.rotation,
          scaleX: transform.scaleX,
          scaleY: transform.scaleY
        }
      }
    }

    return updateData
  }

  const createNewDesign = (width = 800, height = 600): Design => {
    const authStore = useAuthStore()
    const newDesign: Design = {
      id: `design_${Date.now()}`,
      name: 'Untitled Design',
      title: 'Untitled Design',
      width,
      height,
      userId: authStore.user?.id || '1',
      isPublic: false,
      createdAt: new Date().toISOString(),
      updatedAt: new Date().toISOString(),
      data: {
        backgroundColor: '#ffffff',
        animationSettings: {},
        customProperties: {},
        globalStyles: {},
        gridSettings: {
          gridSize: 20,
          showGrid: false,
          snapToGrid: false,
          snapToObjects: false,
          snapTolerance: 5
        },
        viewportSettings: {
          zoom: 1,
          panX: 0,
          panY: 0
        }
      },
      layers: [], // Initialize empty layers array
    }
    
    currentDesign.value = newDesign
    return newDesign
  }
  
  const saveDesign = async (design?: Design, showNotifications: boolean = true): Promise<{ success: boolean; error?: string }> => {
    const designToSave = design || currentDesign.value
    if (!designToSave) return { success: false, error: 'No design to save' }
    
    try {
      isLoading.value = true
      error.value = null
      
      designToSave.updatedAt = new Date().toISOString()
      
      let response: { data?: ApiResponse<Design> }
      console.log(designToSave)
      if (designToSave.id.toString().startsWith('design_')) {
        // New design - create it
        const createRequest: CreateDesignRequest = {
          name: designToSave.name?? designToSave.title,
          width: designToSave.width,
          height: designToSave.height,
          data: designToSave.data
        }
        response = await designAPI.createDesign(createRequest)
        
        if (response.data?.data) {
          // Update the local design with the server-generated data
          const savedDesign = response.data.data
          Object.assign(designToSave, savedDesign)
          currentDesign.value = savedDesign
        }
      } else {
        // Existing design - update it
        const updateRequest: UpdateDesignRequest = {
          name: designToSave.name?? designToSave.title, // Use name or title
          width: designToSave.width,
          height: designToSave.height,
          data: designToSave.data,
          description: designToSave.description,
          projectId: designToSave.projectId ? (typeof designToSave.projectId === 'string' ? parseInt(designToSave.projectId, 10) : designToSave.projectId) : undefined
        }
        response = await designAPI.updateDesign(designToSave.id, updateRequest)
      }
      
      // Update local designs list
      const existingIndex = designs.value.findIndex(d => d.id === designToSave.id)
      if (existingIndex >= 0) {
        designs.value[existingIndex] = designToSave
      } else {
        designs.value.unshift(designToSave)
      }
      
      if (showNotifications) {
        designSaved(designToSave.name?? designToSave.title)
      }
      return { success: true }
    } catch (err: unknown) {
      const errorMessage = (err as any)?.response?.data?.message || (err as Error)?.message || 'Save failed'
      error.value = errorMessage
      if (showNotifications) {
        saveFailed(errorMessage)
      }
      return { success: false, error: errorMessage }
    } finally {
      isLoading.value = false
    }
  }
  
  const loadDesign = async (id: string): Promise<{ success: boolean; design?: Design; error?: string }> => {
    try {
      isLoading.value = true
      error.value = null
      
      const response: { data?: ApiResponse<Design> } = await designAPI.getDesign(id)
      
      if (response.data?.data) {
        currentDesign.value = response.data.data
        return { success: true, design: response.data.data }
      }
      
      return { success: false, error: 'Design not found' }
    } catch (err: unknown) {
      const errorMessage = (err as any)?.response?.data?.message || (err as Error)?.message || 'Load failed'
      error.value = errorMessage
      showError('Failed to Load Design', errorMessage)
      return { success: false, error: errorMessage }
    } finally {
      isLoading.value = false
    }
  }
  
  const updateDesignName = (name: string) => {
    if (currentDesign.value) {
      currentDesign.value.name = name
      currentDesign.value.updatedAt = new Date().toISOString()
    }
  }
  
  const updateCanvasSize = (width: number, height: number) => {
    if (currentDesign.value) {
      currentDesign.value.width = width
      currentDesign.value.height = height
      // Update background color if data exists, otherwise set default
      if (!currentDesign.value.data.backgroundColor) {
        currentDesign.value.data.backgroundColor = '#ffffff'
      }
      currentDesign.value.updatedAt = new Date().toISOString()
    }
  }
  
    const addLayer = async (layer: Layer, { skipPersistence = false } = {}): Promise<{ success: boolean; error?: string; layer?: Layer }> => {
    if (!currentDesign.value) {
      console.warn('Cannot add layer: No current design loaded')
      return { success: false, error: 'No current design loaded' }
    }

    // Ensure layers array exists
    if (!currentDesign.value.layers) {
      console.warn('Cannot add layer: Layers array is not initialized, creating new array')
      currentDesign.value.layers = []
    }

    // If skipPersistence is true (during design loading), just add to local state
    if (skipPersistence) {
      currentDesign.value.layers.push(layer)
      currentDesign.value.updatedAt = new Date().toISOString()
      console.log(`Layer added locally (skipped persistence). Total layers: ${currentDesign.value.layers.length}`)
      return { success: true, layer }
    }

    try {
      // First persist to backend
      const createLayerData = layerToCreateLayerData(layer, currentDesign.value.id)
      const response = await layerAPI.createLayer(createLayerData)
      
      if (response.data?.data?.layer) {
        // Type assertion for the backend response structure
        const backendLayer = response.data.data.layer as unknown as BackendLayerData
        
        // Convert backend layer format to frontend Layer format
        const persistedLayer = backendToFrontendLayer(backendLayer)
        
        // Add to local state
        currentDesign.value.layers.push(persistedLayer)
        currentDesign.value.updatedAt = new Date().toISOString()
        
        console.log(`Layer added and persisted successfully. Total layers: ${currentDesign.value.layers.length}`)
        return { success: true, layer: persistedLayer }
      } else {
        console.error('No layer data returned from API')
        return { success: false, error: 'No layer data returned from API' }
      }
    } catch (err: unknown) {
      console.error('Failed to persist layer to backend:', err)
      const errorMessage = (err as any)?.response?.data?.message || (err as Error)?.message || 'Failed to create layer'
      
      // Fallback: add to local state only
      currentDesign.value.layers.push(layer)
      currentDesign.value.updatedAt = new Date().toISOString()
      
      console.warn('Layer added to local state only (backend failed)')
      return { success: false, error: errorMessage }
    }
  }
  
  const removeLayer = async (layerId: number): Promise<{ success: boolean; error?: string }> => {
    if (!currentDesign.value) {
      console.warn('Cannot remove layer: No current design loaded')
      return { success: false, error: 'No current design loaded' }
    }

    // Ensure layers exist
    if (!currentDesign.value.layers) {
      console.warn('Cannot remove layer: Layers array is not initialized')
      return { success: false, error: 'Layers array is not initialized' }
    }

    const index = currentDesign.value.layers.findIndex((l: Layer) => l.id === layerId)
    if (index < 0) {
      console.warn(`Layer ${layerId} not found in design`)
      return { success: false, error: 'Layer not found in design' }
    }

    const layer = currentDesign.value.layers[index]
    
    try {
      // If this is a temporary frontend ID (negative numbers), skip backend deletion
      if (layerId < 0) {
        console.warn(`Layer ${layerId} appears to be a local-only layer, skipping backend deletion`)
      } else {
        // Try to delete from backend using the numeric ID
        await layerAPI.deleteLayer(layerId)
        console.log(`Layer ${layerId} deleted from backend successfully`)
      }

      // Remove from local state regardless of backend success
      currentDesign.value.layers.splice(index, 1)
      currentDesign.value.updatedAt = new Date().toISOString()
      
      console.log(`Layer ${layerId} removed successfully. Total layers: ${currentDesign.value.layers.length}`)
      return { success: true }
      
    } catch (err: unknown) {
      console.error('Failed to delete layer from backend:', err)
      const errorMessage = (err as any)?.response?.data?.message || (err as Error)?.message || 'Failed to delete layer'
      
      // Still remove from local state as fallback
      currentDesign.value.layers.splice(index, 1)
      currentDesign.value.updatedAt = new Date().toISOString()
      
      console.warn('Layer removed from local state only (backend failed)')
      return { success: false, error: errorMessage }
    }
  }
  
  const updateLayer = async (layerId: number, updates: Partial<Layer>, { skipPersistence = false } = {}): Promise<{ success: boolean; error?: string; layer?: Layer }> => {
    if (!currentDesign.value) {
      console.warn('Cannot update layer: No current design loaded')
      return { success: false, error: 'No current design loaded' }
    }

    // Ensure layers exist
    if (!currentDesign.value.layers) {
      console.warn('Cannot update layer: Layers array is not initialized')
      return { success: false, error: 'Layers array is not initialized' }
    }
    console.log(`Updating layer ${layerId} with updates:`, updates)
    const layer = currentDesign.value.layers.find((l: Layer) => l.id === layerId)
    if (!layer) {
      console.warn(`Layer ${layerId} not found in design`)
      return { success: false, error: 'Layer not found in design' }
    }

    // If skipPersistence is true (during design loading), just update locally
    if (skipPersistence) {
      Object.assign(layer, updates)
      currentDesign.value.updatedAt = new Date().toISOString()
      console.log(`Layer ${layerId} updated locally (skipped persistence)`)
      return { success: true, layer: { ...layer, ...updates } }
    }

    try {
      let updatedLayer = layer
      
      // Try to update backend if this is not a temporary local-only layer (negative ID)
      if (layerId > 0) {
        const updateData = layerToUpdateLayerData(updates)
        console.log("Updating layer in backend with data:", updateData)
        const response = await layerAPI.updateLayer(layerId, updateData)
        
        if (response.data?.data?.layer) {
          // Type assertion for the backend response structure
          const backendLayer = response.data.data.layer as unknown as BackendLayerData
          
          // Convert backend response to frontend format and merge with updates
          const backendUpdates = backendToFrontendLayer(backendLayer)
          updatedLayer = {
            ...layer,
            ...backendUpdates
          }
          
          console.log(`Layer ${layerId} updated in backend successfully`)
        } else {
          // Backend update failed, just apply local updates
          updatedLayer = { ...layer, ...updates }
          console.warn(`Backend update failed, applying local updates only`)
        }
      } else {
        // Local-only layer, just apply updates locally
        updatedLayer = { ...layer, ...updates }
        console.warn(`Layer ${layerId} is local-only, applying updates locally`)
      }

      // Update local state
      Object.assign(layer, updatedLayer)
      currentDesign.value.updatedAt = new Date().toISOString()
      
      console.log(`Layer ${layerId} updated successfully`)
      return { success: true, layer: updatedLayer }
      
    } catch (err: unknown) {
      console.error('Failed to update layer in backend:', err)
      const errorMessage = (err as any)?.response?.data?.message || (err as Error)?.message || 'Failed to update layer'
      
      // Fallback: apply updates locally only
      Object.assign(layer, updates)
      currentDesign.value.updatedAt = new Date().toISOString()
      
      console.warn('Layer updated locally only (backend failed)')
      return { success: false, error: errorMessage }
    }
  }
  
  const loadUserDesigns = async (): Promise<{ success: boolean; error?: string }> => {
    try {
      isLoading.value = true
      error.value = null
      
      const response = await designAPI.getDesigns()
      
      if (response.data?.data) {
        designs.value = response.data.data
        return { success: true }
      }
      
      designs.value = []
      return { success: true }
    } catch (err: unknown) {
      const errorMessage = (err as any)?.response?.data?.message || (err as Error)?.message || 'Load failed'
      error.value = errorMessage
      showError('Failed to Load Designs', errorMessage)
      designs.value = []
      return { success: false, error: errorMessage }
    } finally {
      isLoading.value = false
    }
  }
  
  const deleteDesign = async (id: string): Promise<{ success: boolean; error?: string }> => {
    try {
      isLoading.value = true
      error.value = null
      
      // Get the design name before deletion for the notification
      const design = designs.value.find(d => d.id === id)
      const designName = design?.name || 'Design'
      
      await designAPI.deleteDesign(id)
      
      // Remove from local list
      designs.value = designs.value.filter(d => d.id !== id)
      
      // Clear current design if it's the one being deleted
      if (currentDesign.value?.id === id) {
        currentDesign.value = null
      }
      
      designDeleted(designName)
      return { success: true }
    } catch (err: unknown) {
      const errorMessage = (err as any)?.response?.data?.message || (err as Error)?.message || 'Delete failed'
      error.value = errorMessage
      showError('Failed to Delete Design', errorMessage)
      return { success: false, error: errorMessage }
    } finally {
      isLoading.value = false
    }
  }
  
  const exportDesign = async (id: string, format: ExportFormat): Promise<{ success: boolean; data?: any; error?: string }> => {
    try {
      isLoading.value = true
      error.value = null
      
      const response = await designAPI.exportDesign(id, format)
      
      designExported(format.toUpperCase())
      return { success: true, data: response.data }
    } catch (err: unknown) {
      const errorMessage = (err as any)?.response?.data?.message || (err as Error)?.message || 'Export failed'
      error.value = errorMessage
      exportFailed(errorMessage)
      return { success: false, error: errorMessage }
    } finally {
      isLoading.value = false
    }
  }
  
  const duplicateDesign = async (id: string): Promise<{ success: boolean; design?: Design; error?: string }> => {
    try {
      isLoading.value = true
      error.value = null
      
      const response: { data?: ApiResponse<Design> } = await designAPI.duplicateDesign(id)
      
      if (response.data?.data) {
        const duplicatedDesign = response.data.data
        designs.value.unshift(duplicatedDesign)
        designSaved(`${duplicatedDesign.name} (Copy)`)
        return { success: true, design: duplicatedDesign }
      }
      
      return { success: false, error: 'Duplication failed' }
    } catch (err: unknown) {
      const errorMessage = (err as any)?.response?.data?.message || (err as Error)?.message || 'Duplication failed'
      error.value = errorMessage
      showError('Failed to Duplicate Design', errorMessage)
      return { success: false, error: errorMessage }
    } finally {
      isLoading.value = false
    }
  }

  // Enhanced layer management methods
  const getLayerById = (layerId: number): Layer | null => {
    if (!currentDesign.value) return null
    return currentDesign.value.layers?.find((l: Layer) => l.id === layerId) || null
  }

  const updateLayerProperty = (layerId: number, property: string, value: any) => {
    if (currentDesign.value) {
      const layer = currentDesign.value.layers?.find((l: Layer) => l.id === layerId)
      if (layer) {
        if (property.startsWith('properties.')) {
          const propPath = property.replace('properties.', '')
          layer.properties[propPath] = value
        } else {
          (layer as any)[property] = value
        }
        currentDesign.value.updatedAt = new Date().toISOString()
      }
    }
  }

  const reorderLayers = (layerIds: number[]) => {
    if (currentDesign.value && currentDesign.value.layers) {
      const reorderedLayers = layerIds.map(id => 
        currentDesign.value!.layers?.find((l: Layer) => l.id === id)
      ).filter(Boolean) as Layer[]
      
      currentDesign.value.layers = reorderedLayers
      currentDesign.value.updatedAt = new Date().toISOString()
    }
  }

  const duplicateLayer = async (layerId: number): Promise<{ success: boolean; error?: string; layer?: Layer }> => {
    if (!currentDesign.value) {
      return { success: false, error: 'No current design loaded' }
    }
    
    const originalLayer = getLayerById(layerId)
    if (!originalLayer) {
      return { success: false, error: 'Original layer not found' }
    }

    try {
      // Try to duplicate via backend API if this is a persisted layer (positive ID)
      if (layerId > 0) {
        const duplicateData: DuplicateLayerData = {
          name: `${originalLayer.name} Copy`
        }
        
        const response = await layerAPI.duplicateLayer(layerId, duplicateData)
        
        if (response.data?.data?.layer) {
          // Type assertion for the backend response structure
          const backendLayer = response.data.data.layer as unknown as BackendLayerData
          
          // Convert backend layer to frontend format
          const duplicatedLayer = backendToFrontendLayer(backendLayer)
          
          // Add to local state
          if (!currentDesign.value.layers) {
            currentDesign.value.layers = []
          }
          currentDesign.value.layers.push(duplicatedLayer)
          currentDesign.value.updatedAt = new Date().toISOString()
          
          console.log(`Layer duplicated via backend successfully`)
          return { success: true, layer: duplicatedLayer }
        }
      }
      
      // Fallback: local duplication (for local layers or if backend fails)
      const duplicatedLayer: Layer = {
        ...originalLayer,
        id: -Date.now(), // Use negative timestamp as temporary ID
        name: `${originalLayer.name} Copy`,
        transform: {
          ...originalLayer.transform,
          x: (originalLayer.transform?.x || 0) + 10,
          y: (originalLayer.transform?.y || 0) + 10
        },
        zIndex: originalLayer.zIndex + 1
      }
      
      if (!currentDesign.value.layers) {
        currentDesign.value.layers = []
      }
      currentDesign.value.layers.push(duplicatedLayer)
      currentDesign.value.updatedAt = new Date().toISOString()
      
      console.log(`Layer duplicated locally`)
      return { success: true, layer: duplicatedLayer }
      
    } catch (err: unknown) {
      console.error('Failed to duplicate layer via backend:', err)
      const errorMessage = (err as any)?.response?.data?.message || (err as Error)?.message || 'Failed to duplicate layer'
      
      // Fallback: local duplication
      const duplicatedLayer: Layer = {
        ...originalLayer,
        id: -Date.now(), // Use negative timestamp as temporary ID
        name: `${originalLayer.name} Copy`,
        transform: {
          ...originalLayer.transform,
          x: (originalLayer.transform?.x || 0) + 10,
          y: (originalLayer.transform?.y || 0) + 10
        },
        zIndex: originalLayer.zIndex + 1
      }
      
      if (!currentDesign.value.layers) {
        currentDesign.value.layers = []
      }
      currentDesign.value.layers.push(duplicatedLayer)
      currentDesign.value.updatedAt = new Date().toISOString()
      
      console.warn('Layer duplicated locally (backend failed)')
      return { success: false, error: errorMessage }
    }
  }

  const clearSelection = () => {
    // This will be handled by the SDK, but we track it in the store
    selectedLayerIds.value = []
  }

  // Method to clear the current design
  const clearCurrentDesign = () => {
    currentDesign.value = null
    selectedLayerIds.value = []
  }

  // Method to reset the store
  const resetStore = () => {
    currentDesign.value = null
    designs.value = []
    isLoading.value = false
    error.value = null
    selectedLayerIds.value = []
  }



  const selectedLayerIds = ref<number[]>([])
  const selectedLayers = computed(() => 
    selectedLayerIds.value.map(id => getLayerById(id)).filter(Boolean) as Layer[]
  )

  return {
    // State
    currentDesign,
    designs,
    isLoading,
    error,
    
    // Getters
    hasCurrentDesign,
    selectedLayerIds,
    selectedLayers,
    
    // Actions
    createNewDesign,
    saveDesign,
    loadDesign,
    updateDesignName,
    updateCanvasSize,
    addLayer,
    removeLayer,
    updateLayer,
    loadUserDesigns,
    deleteDesign,
    exportDesign,
    duplicateDesign,
    getLayerById,
    updateLayerProperty,
    reorderLayers,
    duplicateLayer,
    clearSelection,
    clearCurrentDesign,
    resetStore
  }
})

import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import type { Design, DesignData, Layer } from '@/types'
import { designAPI } from '@/services/api'
import { useAuthStore } from '@/stores/auth'
import { useNotifications } from '@/composables/useNotifications'

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
  designData: DesignData
}

interface UpdateDesignRequest {
  name: string
  width: number
  height: number
  designData: DesignData
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
  
  const createNewDesign = (width = 800, height = 600): Design => {
    const authStore = useAuthStore()
    const newDesign: Design = {
      id: `design_${Date.now()}`,
      name: 'Untitled Design',
      title: 'Untitled Design', // Add missing title property
      width,
      height,
      dimensions: { // Add missing dimensions property
        width,
        height
      },
      userId: authStore.user?.id || '1',
      isPublic: false,
      createdAt: new Date().toISOString(),
      updatedAt: new Date().toISOString(),
      designData: {
        version: '1.0',
        layers: [],
        canvas: {
          width,
          height,
          backgroundColor: '#ffffff',
        },
      },
    }
    
    currentDesign.value = newDesign
    return newDesign
  }
  
  const saveDesign = async (design?: Design): Promise<{ success: boolean; error?: string }> => {
    const designToSave = design || currentDesign.value
    if (!designToSave) return { success: false, error: 'No design to save' }
    
    try {
      isLoading.value = true
      error.value = null
      
      designToSave.updatedAt = new Date().toISOString()
      
      let response: { data?: ApiResponse<Design> }
      
      if (designToSave.id.startsWith('design_')) {
        // New design - create it
        const createRequest: CreateDesignRequest = {
          name: designToSave.name,
          width: designToSave.width,
          height: designToSave.height,
          designData: designToSave.designData
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
          name: designToSave.name,
          width: designToSave.width,
          height: designToSave.height,
          designData: designToSave.designData,
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
      
      designSaved(designToSave.name)
      return { success: true }
    } catch (err: unknown) {
      const errorMessage = (err as any)?.response?.data?.message || (err as Error)?.message || 'Save failed'
      error.value = errorMessage
      saveFailed(errorMessage)
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
      currentDesign.value.designData.canvas.width = width
      currentDesign.value.designData.canvas.height = height
      currentDesign.value.updatedAt = new Date().toISOString()
    }
  }
  
  const addLayer = (layer: Layer) => {
    if (currentDesign.value) {
      currentDesign.value.designData.layers.push(layer)
      currentDesign.value.updatedAt = new Date().toISOString()
    }
  }
  
  const removeLayer = (layerId: string) => {
    if (currentDesign.value) {
      const index = currentDesign.value.designData.layers.findIndex(l => l.id === layerId)
      if (index >= 0) {
        currentDesign.value.designData.layers.splice(index, 1)
        currentDesign.value.updatedAt = new Date().toISOString()
      }
    }
  }
  
  const updateLayer = (layerId: string, updates: Partial<Layer>) => {
    if (currentDesign.value) {
      const layer = currentDesign.value.designData.layers.find(l => l.id === layerId)
      if (layer) {
        Object.assign(layer, updates)
        currentDesign.value.updatedAt = new Date().toISOString()
      }
    }
  }
  
  const loadUserDesigns = async (): Promise<{ success: boolean; error?: string }> => {
    try {
      isLoading.value = true
      error.value = null
      
      const response = await designAPI.getDesigns()
      
      if (response.data?.data?.designs) {
        designs.value = response.data.data.designs
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
  const getLayerById = (layerId: string): Layer | null => {
    if (!currentDesign.value) return null
    return currentDesign.value.designData.layers.find(l => l.id === layerId) || null
  }

  const updateLayerProperty = (layerId: string, property: string, value: any) => {
    if (currentDesign.value) {
      const layer = currentDesign.value.designData.layers.find(l => l.id === layerId)
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

  const reorderLayers = (layerIds: string[]) => {
    if (currentDesign.value) {
      const reorderedLayers = layerIds.map(id => 
        currentDesign.value!.designData.layers.find(l => l.id === id)
      ).filter(Boolean) as Layer[]
      
      currentDesign.value.designData.layers = reorderedLayers
      currentDesign.value.updatedAt = new Date().toISOString()
    }
  }

  const duplicateLayer = (layerId: string): Layer | null => {
    if (!currentDesign.value) return null
    
    const originalLayer = getLayerById(layerId)
    if (!originalLayer) return null
    
    const duplicatedLayer: Layer = {
      ...originalLayer,
      id: `layer_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`,
      name: `${originalLayer.name} Copy`,
      x: originalLayer.x + 10,
      y: originalLayer.y + 10,
      zIndex: originalLayer.zIndex + 1
    }
    
    currentDesign.value.designData.layers.push(duplicatedLayer)
    currentDesign.value.updatedAt = new Date().toISOString()
    
    return duplicatedLayer
  }

  const clearSelection = () => {
    // This will be handled by the SDK, but we track it in the store
    selectedLayerIds.value = []
  }

  const selectedLayerIds = ref<string[]>([])
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
  }
})

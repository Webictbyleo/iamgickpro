import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import type { Design, DesignData, Layer } from '@/types'
import { designAPI } from '@/services/api'
import { useAuthStore } from '@/stores/auth'
import { useNotifications } from '@/composables/useNotifications'

export const useDesignStore = defineStore('design', () => {
  const { designSaved, saveFailed, designDeleted, designExported, exportFailed, showError } = useNotifications()
  
  const currentDesign = ref<Design | null>(null)
  const designs = ref<Design[]>([])
  const isLoading = ref(false)
  const error = ref<string | null>(null)
  
  const hasCurrentDesign = computed(() => !!currentDesign.value)
  
  const createNewDesign = (width = 800, height = 600) => {
    const authStore = useAuthStore()
    const newDesign: Design = {
      id: `design_${Date.now()}`,
      title: 'Untitled Design',
      dimensions: {
        width,
        height
      },
      userId: authStore.user?.id || '1',
      isPublic: false,
      createdAt: new Date().toISOString(),
      updatedAt: new Date().toISOString(),
      data: {
        version: '1.0.0',
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
  
  const saveDesign = async (design?: Design) => {
    const designToSave = design || currentDesign.value
    if (!designToSave) return { success: false, error: 'No design to save' }
    
    try {
      isLoading.value = true
      error.value = null
      
      designToSave.updatedAt = new Date().toISOString()
      
      let response
      if (designToSave.id.startsWith('design_')) {
        // New design - create it
        response = await designAPI.createDesign({
          name: designToSave.title,
          width: designToSave.dimensions.width,
          height: designToSave.dimensions.height
        })
        
        if (response.data) {
          // Update the local design with the server-generated ID
          const savedDesign = response.data.data
          Object.assign(designToSave, savedDesign)
          currentDesign.value = savedDesign
        }
      } else {
        // Existing design - update it
        response = await designAPI.updateDesign(designToSave.id, designToSave)
      }
      
      // Update local designs list
      const existingIndex = designs.value.findIndex(d => d.id === designToSave.id)
      if (existingIndex >= 0) {
        designs.value[existingIndex] = designToSave
      } else {
        designs.value.unshift(designToSave)
      }
      
      designSaved(designToSave.title)
      return { success: true }
    } catch (err: any) {
      const errorMessage = err?.response?.data?.message || err?.message || 'Save failed'
      error.value = errorMessage
      saveFailed(errorMessage)
      return { success: false, error: errorMessage }
    } finally {
      isLoading.value = false
    }
  }
  
  const loadDesign = async (id: string) => {
    try {
      isLoading.value = true
      error.value = null
      
      const response = await designAPI.getDesign(id)
      
      if (response.data) {
        currentDesign.value = response.data.data
        return { success: true, design: response.data.data }
      }
      
      return { success: false, error: 'Design not found' }
    } catch (err: any) {
      const errorMessage = err?.response?.data?.message || err?.message || 'Load failed'
      error.value = errorMessage
      showError('Failed to Load Design', errorMessage)
      return { success: false, error: errorMessage }
    } finally {
      isLoading.value = false
    }
  }
  
  const updateDesignName = (name: string) => {
    if (currentDesign.value) {
      currentDesign.value.title = name
      currentDesign.value.updatedAt = new Date().toISOString()
    }
  }
  
  const updateCanvasSize = (width: number, height: number) => {
    if (currentDesign.value) {
      currentDesign.value.dimensions.width = width
      currentDesign.value.dimensions.height = height
      currentDesign.value.data.canvas.width = width
      currentDesign.value.data.canvas.height = height
      currentDesign.value.updatedAt = new Date().toISOString()
    }
  }
  
  const addLayer = (layer: Layer) => {
    if (currentDesign.value) {
      currentDesign.value.data.layers.push(layer)
      currentDesign.value.updatedAt = new Date().toISOString()
    }
  }
  
  const removeLayer = (layerId: string) => {
    if (currentDesign.value) {
      const index = currentDesign.value.data.layers.findIndex(l => l.id === layerId)
      if (index >= 0) {
        currentDesign.value.data.layers.splice(index, 1)
        currentDesign.value.updatedAt = new Date().toISOString()
      }
    }
  }
  
  const updateLayer = (layerId: string, updates: Partial<Layer>) => {
    if (currentDesign.value) {
      const layer = currentDesign.value.data.layers.find(l => l.id === layerId)
      if (layer) {
        Object.assign(layer, updates)
        currentDesign.value.updatedAt = new Date().toISOString()
      }
    }
  }
  
  const loadUserDesigns = async () => {
    try {
      isLoading.value = true
      error.value = null
      
      const response = await designAPI.getDesigns()
      
      if (response.data) {
        designs.value = response.data.data
        return { success: true, designs: response.data.data }
      }
      
      return { success: false, error: 'Failed to load designs' }
    } catch (err: any) {
      const errorMessage = err?.response?.data?.message || err?.message || 'Failed to load designs'
      error.value = errorMessage
      showError('Failed to Load Designs', errorMessage)
      designs.value = []
      return { success: false, error: errorMessage }
    } finally {
      isLoading.value = false
    }
  }
  
  const deleteDesign = async (id: string) => {
    try {
      isLoading.value = true
      error.value = null
      
      // Get the design name before deletion for the notification
      const designToDelete = designs.value.find(d => d.id === id)
      const designName = designToDelete?.title || 'Design'
      
      await designAPI.deleteDesign(id)
      
      // Remove from local designs list
      designs.value = designs.value.filter(d => d.id !== id)
      
      // Clear current design if it's the one being deleted
      if (currentDesign.value?.id === id) {
        currentDesign.value = null
      }
      
      designDeleted(designName)
      return { success: true }
    } catch (err: any) {
      const errorMessage = err?.response?.data?.message || err?.message || 'Delete failed'
      error.value = errorMessage
      showError('Failed to Delete Design', errorMessage)
      return { success: false, error: errorMessage }
    } finally {
      isLoading.value = false
    }
  }
  
  const exportDesign = async (id: string, format: 'png' | 'jpg' | 'pdf' | 'svg') => {
    try {
      isLoading.value = true
      error.value = null
      
      const response = await designAPI.exportDesign(id, format)
      
      // Create download link
      const url = window.URL.createObjectURL(new Blob([response.data]))
      const link = document.createElement('a')
      link.href = url
      link.setAttribute('download', `design.${format}`)
      document.body.appendChild(link)
      link.click()
      link.remove()
      window.URL.revokeObjectURL(url)
      
      designExported(format.toUpperCase())
      return { success: true }
    } catch (err: any) {
      const errorMessage = err?.response?.data?.message || err?.message || 'Export failed'
      error.value = errorMessage
      exportFailed(errorMessage)
      return { success: false, error: errorMessage }
    } finally {
      isLoading.value = false
    }
  }
  
  const duplicateDesign = async (id: string) => {
    try {
      isLoading.value = true
      error.value = null
      
      const response = await designAPI.duplicateDesign(id)
      
      if (response.data) {
        const duplicatedDesign = response.data.data
        designs.value.unshift(duplicatedDesign)
        designSaved(duplicatedDesign.title)
        return { success: true, design: duplicatedDesign }
      }
      
      return { success: false, error: 'Duplication failed' }
    } catch (err: any) {
      const errorMessage = err?.response?.data?.message || err?.message || 'Duplication failed'
      error.value = errorMessage
      showError('Failed to Duplicate Design', errorMessage)
      return { success: false, error: errorMessage }
    } finally {
      isLoading.value = false
    }
  }

  return {
    currentDesign,
    designs,
    isLoading,
    error,
    hasCurrentDesign,
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
  }
})

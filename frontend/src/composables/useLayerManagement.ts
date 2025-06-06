import { computed, type Ref, type ComputedRef } from 'vue'
import { useDesignStore } from '@/stores/design'
import type { EditorSDK } from '@/editor/sdk/EditorSDK'
import type { Layer, LayerType, TextLayerProperties, ShapeLayerProperties, ImageLayerProperties } from '@/types'

export function useLayerManagement(editorSDK: Ref<EditorSDK | null> | ComputedRef<EditorSDK | null>) {
  const designStore = useDesignStore()

  const selectedLayers = computed(() => designStore.selectedLayers)

  const addElement = async (type: LayerType, properties: any = {}) => {
    if (!editorSDK.value) {
      console.warn('EditorSDK not initialized')
      return
    }

    try {
      const baseLayer = {
        x: 100,
        y: 100,
        width: 150,
        height: 100,
        rotation: 0,
        opacity: 1,
        visible: true,
        locked: false,
        name: `${type} ${Date.now()}`,
        scaleX: 1,
        scaleY: 1,
        zIndex: 0
      }

      if (type === 'text') {
        const textLayer: Layer = {
          ...baseLayer,
          id: `text_${Date.now()}`,
          type: 'text',
          properties: {
            text: properties.text || 'Hello World!',
            fontSize: properties.fontSize || 24,
            fontFamily: properties.fontFamily || 'Arial',
            color: properties.color || '#000000',
            fontWeight: properties.fontWeight || 'normal',
            textAlign: properties.textAlign || 'left',
            fontStyle: 'normal',
            textDecoration: 'none',
            lineHeight: 1.2,
            letterSpacing: 0,
            wordSpacing: 0
          } as TextLayerProperties
        }
        
        await editorSDK.value.layers.createLayer('text', textLayer)
      } else if (type === 'shape') {
        const shapeLayer: Layer = {
          ...baseLayer,
          id: `shape_${Date.now()}`,
          type: 'shape',
          properties: {
            shapeType: properties.shapeType || 'rectangle',
            fill: {
              type: 'solid',
              color: properties.color || '#3B82F6',
              opacity: 1
            },
            stroke: properties.stroke || '#1E40AF',
            strokeWidth: properties.strokeWidth || 2,
            strokeOpacity: 1,
            strokeLineCap: 'round',
            strokeLineJoin: 'round',
            cornerRadius: properties.cornerRadius || (properties.shapeType === 'rectangle' ? 8 : 0),
            sides: 6,
            points: 5,
            innerRadius: 0.5,
            x1: 0,
            y1: 0,
            x2: 100,
            y2: 100
          } as ShapeLayerProperties
        }
        
        await editorSDK.value.layers.createLayer('shape', shapeLayer)
      } else if (type === 'image') {
        const imageLayer: Layer = {
          ...baseLayer,
          id: `image_${Date.now()}`,
          type: 'image',
          properties: {
            src: properties.src || 'https://picsum.photos/400/300',
            alt: properties.alt || 'Sample Image',
            objectFit: properties.objectFit || 'cover'
          } as ImageLayerProperties
        }
        
        await editorSDK.value.layers.createLayer('image', imageLayer)
      }

    } catch (error) {
      console.error('Failed to add element through SDK:', error)
    }
  }

  const selectLayer = (layerId: string, event: MouseEvent) => {
    if (!editorSDK.value) return

    const isMultiSelect = event.ctrlKey || event.metaKey
    
    if (isMultiSelect) {
      // Toggle selection
      const currentSelection = designStore.selectedLayerIds
      if (currentSelection.includes(layerId)) {
        designStore.selectedLayerIds = currentSelection.filter(id => id !== layerId)
      } else {
        designStore.selectedLayerIds = [...currentSelection, layerId]
      }
    } else {
      designStore.selectedLayerIds = [layerId]
    }
  }

  const selectAllLayers = () => {
    if (!editorSDK.value) return
    const layers = designStore.currentDesign?.designData.layers || []
    const layerIds = layers.map(l => l.id)
    designStore.selectedLayerIds = layerIds
  }

  const duplicateLayer = async (layerId: string) => {
    if (!editorSDK.value || !layerId) return
    
    try {
      await editorSDK.value.layers.duplicateLayer(layerId)
    } catch (error) {
      console.error('Failed to duplicate layer:', error)
    }
  }

  const deleteLayer = async (layerId: string) => {
    if (!editorSDK.value || !layerId) return
    
    try {
      await editorSDK.value.layers.deleteLayer(layerId)
    } catch (error) {
      console.error('Failed to delete layer:', error)
    }
  }

  const deleteSelection = async () => {
    if (!editorSDK.value || selectedLayers.value.length === 0) return

    try {
      for (const layer of selectedLayers.value) {
        await editorSDK.value.layers.deleteLayer(layer.id)
      }
    } catch (error) {
      console.error('Failed to delete selection:', error)
    }
  }

  const duplicateSelection = async () => {
    if (!editorSDK.value || selectedLayers.value.length === 0) return

    try {
      for (const layer of selectedLayers.value) {
        await editorSDK.value.layers.duplicateLayer(layer.id)
      }
    } catch (error) {
      console.error('Failed to duplicate selection:', error)
    }
  }

  const toggleLayerVisibility = async (layerId: string) => {
    if (!editorSDK.value) return
    
    try {
      const layer = designStore.getLayerById(layerId)
      if (layer) {
        await editorSDK.value.layers.updateLayer(layerId, { visible: !layer.visible })
      }
    } catch (error) {
      console.error('Failed to toggle layer visibility:', error)
    }
  }

  const toggleLayerLock = async (layerId: string) => {
    if (!editorSDK.value) return
    
    try {
      const layer = designStore.getLayerById(layerId)
      if (layer) {
        await editorSDK.value.layers.updateLayer(layerId, { locked: !layer.locked })
      }
    } catch (error) {
      console.error('Failed to toggle layer lock:', error)
    }
  }

  const reorderLayers = (layerIds: string[]) => {
    if (!designStore.currentDesign) return
    
    // Update the design store
    designStore.reorderLayers(layerIds)
    
    // TODO: Update the visual order in the canvas
    if (editorSDK.value) {
      // editorSDK.value.layers.reorderLayers(layerIds)
    }
  }

  const updateLayerProperties = async (layerId: string, properties: Partial<Layer>) => {
    if (!editorSDK.value) return
    
    try {
      await editorSDK.value.layers.updateLayer(layerId, properties)
    } catch (error) {
      console.error('Failed to update layer properties:', error)
    }
  }

  const updateSelectedLayersProperty = async (property: string, value: any) => {
    if (!editorSDK.value || selectedLayers.value.length === 0) return
    
    try {
      for (const layer of selectedLayers.value) {
        await editorSDK.value.layers.updateLayer(layer.id, { [property]: value })
      }
    } catch (error) {
      console.error('Failed to update selected layers:', error)
    }
  }

  const getCommonProperty = (property: string): any => {
    if (selectedLayers.value.length === 0) return ''
    
    const firstValue = (selectedLayers.value[0] as any)[property]
    const allSame = selectedLayers.value.every(layer => (layer as any)[property] === firstValue)
    
    return allSame ? firstValue : ''
  }

  return {
    selectedLayers,
    addElement,
    selectLayer,
    selectAllLayers,
    duplicateLayer,
    deleteLayer,
    deleteSelection,
    duplicateSelection,
    toggleLayerVisibility,
    toggleLayerLock,
    reorderLayers,
    updateLayerProperties,
    updateSelectedLayersProperty,
    getCommonProperty
  }
}

import Konva from 'konva'
import type { EventEmitter } from './EventEmitter'
import type { 
  LayerAPI, 
  EditorState, 
  LayerNode, 
  LayerType,
  KonvaLayerRenderer,
  Transform
} from './types'
import type { Layer, LayerType as LayerTypeImport } from '../../types'
import { TextLayerRenderer } from './renderers/TextLayerRenderer'
import { ImageLayerRenderer } from './renderers/ImageLayerRenderer'
import { ShapeLayerRenderer } from './renderers/ShapeLayerRenderer'
import { GroupLayerRenderer } from './renderers/GroupLayerRenderer'

export class LayerManager implements LayerAPI {
  private layers: Map<string, LayerNode> = new Map()
  private renderers: Map<LayerType, KonvaLayerRenderer> = new Map()
  private mainLayer!: Konva.Layer
  private transformManager: any = null // Will be injected from EditorSDK

  constructor(
    private stage: Konva.Stage,
    private state: EditorState,
    private emitter: EventEmitter
  ) {
    this.setupRenderers()
    this.setupLayers()
  }

  setTransformManager(transformManager: any): void {
    this.transformManager = transformManager
  }

  // ============================================================================
  // LAYER CRUD OPERATIONS
  // ============================================================================

  async createLayer(type: string, data: Partial<Layer>): Promise<LayerNode> {
    console.log('LayerManager: Creating layer', { type, data })
    
    const layerNode = this.createLayerNode(type, data)
    
    // Get renderer for the layer type
    const renderer = this.getRenderer(type)
    
    // Create Konva node
    const konvaNode = renderer.render(layerNode)
    layerNode.konvaNode = konvaNode

    // Add click event to make layer selectable
    konvaNode.on('click tap', (evt: Konva.KonvaEventObject<MouseEvent | TouchEvent>) => {
      // Handle multi-selection with ctrl/cmd key
      if (evt.evt.ctrlKey || evt.evt.metaKey) {
        const currentSelection = [...this.state.selectedLayers]
        const index = currentSelection.indexOf(layerNode.id)
        if (index === -1) {
          currentSelection.push(layerNode.id)
        } else {
          currentSelection.splice(index, 1)
        }
        this.selectLayers(currentSelection)
      } else {
        // Single selection for normal click
        this.selectLayer(layerNode.id)
      }
      // Stop event bubbling to prevent stage from clearing selection
      evt.cancelBubble = true
    })

    // Make layer draggable
    konvaNode.draggable(true)
    
    // Add to main layer
    this.mainLayer.add(konvaNode as Konva.Shape | Konva.Group)
    
    // Store layer in collection
    this.layers.set(layerNode.id, layerNode)
    
    // Force redraw
    this.mainLayer.batchDraw()
    
    // Emit creation event    
    this.emitter.emit('layer:created', {
      id: layerNode.id,
      type: layerNode.type as LayerTypeImport,
      name: layerNode.name,
      visible: layerNode.visible,
      locked: layerNode.locked,
      opacity: layerNode.opacity,
      x: layerNode.x,
      y: layerNode.y,
      width: layerNode.width,
      height: layerNode.height,
      rotation: layerNode.rotation,
      scaleX: layerNode.scaleX,
      scaleY: layerNode.scaleY,
      zIndex: layerNode.zIndex,
      properties: layerNode.properties
    })
    
    return layerNode
  }

  async addLayer(layerData: Partial<Layer>): Promise<LayerNode> {
    // Determine layer type from layerData or default to 'text'
    const type = layerData.type || 'text'
    return this.createLayer(type, layerData)
  }

  async deleteLayer(layerId: string): Promise<void> {
    const layer = this.layers.get(layerId)
    if (!layer) return

    // Remove from selection if selected
    const selectedIndex = this.state.selectedLayers.indexOf(layerId)
    if (selectedIndex > -1) {
      this.state.selectedLayers.splice(selectedIndex, 1)
    }

    // Remove Konva node
    if (layer.konvaNode) {
      layer.konvaNode.destroy()
    }

    this.layers.delete(layerId)
    
    // Update transform manager selection
    if (this.transformManager) {
      this.transformManager.deselectAll()
    }
    
    // Force redraw to reflect the deletion
    this.mainLayer.batchDraw()
    
    this.emitter.emit('layer:deleted', layerId)
  }

  async updateLayer(layerId: string, updates: Partial<LayerNode>): Promise<void> {
    const layer = this.layers.get(layerId)
    if (!layer) {
      throw new Error(`Layer ${layerId} not found`)
    }

    // Update layer properties
    Object.assign(layer, updates)

    // Update Konva node if it exists
    if (layer.konvaNode) {
      this.updateKonvaNode(layer.konvaNode, layer)
    }
    
    // Force redraw to reflect the updates
    this.mainLayer.batchDraw()

    // Convert LayerNode to Layer for the event
    const layerData: Layer = {
      id: layer.id,
      type: layer.type as LayerTypeImport,
      name: layer.name,
      visible: layer.visible,
      locked: layer.locked,
      opacity: layer.opacity,
      x: layer.x,
      y: layer.y,
      width: layer.width,
      height: layer.height,
      rotation: layer.rotation,
      scaleX: layer.scaleX,
      scaleY: layer.scaleY,
      zIndex: layer.zIndex,
      properties: layer.properties
    }

    this.emitter.emit('layer:updated', layerData)
  }

  async duplicateLayer(layerId: string): Promise<LayerNode> {
    const originalLayer = this.layers.get(layerId)
    if (!originalLayer) throw new Error(`Layer ${layerId} not found`)

    // Create duplicate data
    const duplicateData = {
      ...originalLayer,
      id: undefined, // Will be generated
      name: `${originalLayer.name} Copy`,
      x: originalLayer.x + 10,
      y: originalLayer.y + 10
    } as Partial<Layer>

    return this.createLayer(originalLayer.type, duplicateData)
  }

  // ============================================================================
  // SELECTION AND TRANSFORMATION
  // ============================================================================

  selectLayer(layerId: string): void {
    const layer = this.layers.get(layerId)
    if (!layer) return

    // Update internal selection state
    this.state.selectedLayers = [layerId]
    
    // Delegate to transform manager
    if (this.transformManager) {
      this.transformManager.selectLayer(layer)
    }
  }

  selectLayers(layerIds: string[]): void {
    // Update internal selection state
    this.state.selectedLayers = layerIds.filter(id => this.layers.has(id))
    
    // Delegate to transform manager
    if (this.transformManager) {
      const layers = this.state.selectedLayers.map(id => this.layers.get(id)).filter(Boolean) as LayerNode[]
      this.transformManager.selectLayers(layers)
    }
  }

  toggleSelection(layerId: string): void {
    const currentSelection = [...this.state.selectedLayers]
    const index = currentSelection.indexOf(layerId)
    
    if (index === -1) {
      // Add to selection
      currentSelection.push(layerId)
    } else {
      // Remove from selection
      currentSelection.splice(index, 1)
    }
    
    this.selectLayers(currentSelection)
  }

  deselectAll(): void {
    // Clear internal selection state
    this.state.selectedLayers = []
    
    // Delegate to transform manager
    if (this.transformManager) {
      this.transformManager.deselectAll()
    }
  }

  getLayer(layerId: string): LayerNode | null {
    return this.layers.get(layerId) || null
  }

  getAllLayers(): LayerNode[] {
    return Array.from(this.layers.values())
  }

  moveLayer(layerId: string, newIndex: number): void {
    const layer = this.layers.get(layerId)
    if (!layer) return

    const allLayers = Array.from(this.layers.values())
    const currentIndex = allLayers.indexOf(layer)
    
    if (currentIndex === -1) return

    // Remove from current position
    allLayers.splice(currentIndex, 1)
    
    // Insert at new position
    const clampedIndex = Math.max(0, Math.min(newIndex, allLayers.length))
    allLayers.splice(clampedIndex, 0, layer)

    // Update z-indices
    allLayers.forEach((l, index) => {
      l.zIndex = index
      if (l.konvaNode) {
        l.konvaNode.zIndex(index)
      }
    })

    this.emitter.emit('layer:moved', { layerId, newIndex: clampedIndex })
  }

  reorderLayers(layerIds: string[]): void {
    // Validate that all layer IDs exist
    const validLayerIds = layerIds.filter(id => this.layers.has(id))
    if (validLayerIds.length === 0) return

    // Get all layers and create a mapping
    const allLayers = Array.from(this.layers.values())
    const reorderedLayers: LayerNode[] = []
    
    // Add layers in the new order
    validLayerIds.forEach(id => {
      const layer = this.layers.get(id)
      if (layer) {
        reorderedLayers.push(layer)
      }
    })
    
    // Add any remaining layers that weren't in the reorder list
    allLayers.forEach(layer => {
      if (!validLayerIds.includes(layer.id)) {
        reorderedLayers.push(layer)
      }
    })

    // Update z-indices first
    reorderedLayers.forEach((layer, index) => {
      layer.zIndex = index
    })

    // Remove all Konva nodes from the layer
    this.mainLayer.removeChildren()

    // Re-add them in the correct order (bottom to top)
    reorderedLayers.forEach(layer => {
      if (layer.konvaNode) {
        layer.konvaNode.zIndex(layer.zIndex)
        this.mainLayer.add(layer.konvaNode as Konva.Shape | Konva.Group)
      }
    })

    // Force redraw to reflect the new order
    this.mainLayer.batchDraw()

    this.emitter.emit('layers:reordered', { layerIds: validLayerIds })
  }

  // ============================================================================
  // LAYER ORDERING METHODS
  // ============================================================================

  bringToFront(layerId: string): void {
    const layer = this.layers.get(layerId)
    if (!layer) return

    const allLayers = Array.from(this.layers.values()).sort((a, b) => a.zIndex - b.zIndex)
    const maxZIndex = Math.max(...allLayers.map(l => l.zIndex))
    
    layer.zIndex = maxZIndex + 1
    
    if (layer.konvaNode) {
      layer.konvaNode.zIndex(layer.zIndex)
      layer.konvaNode.moveToTop()
    }
    
    this.mainLayer.batchDraw()
    this.emitter.emit('layer:moved', { layerId, action: 'bringToFront' })
  }

  bringForward(layerId: string): void {
    const layer = this.layers.get(layerId)
    if (!layer) return

    const allLayers = Array.from(this.layers.values()).sort((a, b) => a.zIndex - b.zIndex)
    const currentIndex = allLayers.findIndex(l => l.id === layerId)
    
    if (currentIndex < allLayers.length - 1) {
      const nextLayer = allLayers[currentIndex + 1]
      const tempZIndex = layer.zIndex
      layer.zIndex = nextLayer.zIndex
      nextLayer.zIndex = tempZIndex
      
      if (layer.konvaNode && nextLayer.konvaNode) {
        layer.konvaNode.zIndex(layer.zIndex)
        nextLayer.konvaNode.zIndex(nextLayer.zIndex)
      }
      
      this.mainLayer.batchDraw()
      this.emitter.emit('layer:moved', { layerId, action: 'bringForward' })
    }
  }

  sendBackward(layerId: string): void {
    const layer = this.layers.get(layerId)
    if (!layer) return

    const allLayers = Array.from(this.layers.values()).sort((a, b) => a.zIndex - b.zIndex)
    const currentIndex = allLayers.findIndex(l => l.id === layerId)
    
    if (currentIndex > 0) {
      const prevLayer = allLayers[currentIndex - 1]
      const tempZIndex = layer.zIndex
      layer.zIndex = prevLayer.zIndex
      prevLayer.zIndex = tempZIndex
      
      if (layer.konvaNode && prevLayer.konvaNode) {
        layer.konvaNode.zIndex(layer.zIndex)
        prevLayer.konvaNode.zIndex(prevLayer.zIndex)
      }
      
      this.mainLayer.batchDraw()
      this.emitter.emit('layer:moved', { layerId, action: 'sendBackward' })
    }
  }

  sendToBack(layerId: string): void {
    const layer = this.layers.get(layerId)
    if (!layer) return

    const allLayers = Array.from(this.layers.values()).sort((a, b) => a.zIndex - b.zIndex)
    const minZIndex = Math.min(...allLayers.map(l => l.zIndex))
    
    layer.zIndex = minZIndex - 1
    
    if (layer.konvaNode) {
      layer.konvaNode.zIndex(layer.zIndex)
      layer.konvaNode.moveToBottom()
    }
    
    this.mainLayer.batchDraw()
    this.emitter.emit('layer:moved', { layerId, action: 'sendToBack' })
  }

  async clear(): Promise<void> {
    // Clear all layers
    Array.from(this.layers.values()).forEach(layer => {
      if (layer.konvaNode) {
        layer.konvaNode.destroy()
      }
    })
    
    this.layers.clear()
    this.mainLayer.removeChildren()
    this.mainLayer.batchDraw()
  }

  destroy(): void {
    // Remove all layers
    Array.from(this.layers.values()).forEach(layer => {
      if (layer.konvaNode) {
        layer.konvaNode.destroy()
      }
    })
    
    // Clear collections
    this.layers.clear()
    this.renderers.clear()
    
  // Remove transformer
    if (this.transformManager) {
      this.transformManager.destroy()
      this.transformManager = null
    }
    
    // Remove main layer
    if (this.mainLayer) {
      this.mainLayer.destroy()
    }
  }

  // ============================================================================
  // PRIVATE METHODS
  // ============================================================================

  private emitSelectionChange(): void {
    // Get selected layer data
    const selectedLayers = this.state.selectedLayers.map(id => {
      const layer = this.layers.get(id)
      if (!layer) return null

      return {
        id: layer.id,
        type: layer.type,
        name: layer.name,
        properties: layer.properties,
        visible: layer.visible,
        locked: layer.locked,
        x: layer.x,
        y: layer.y,
        width: layer.width,
        height: layer.height,
        rotation: layer.rotation,
        scaleX: layer.scaleX,
        scaleY: layer.scaleY,
        zIndex: layer.zIndex
      }
    }).filter(Boolean)

    // Emit both events for compatibility
    this.emitter.emit('layer:selected', selectedLayers)
    this.emitter.emit('selection:changed', this.state.selectedLayers)
  }

  private setupTransformer(): void {
    // Transformer functionality is now handled by TransformManager
    // This method is kept for compatibility but does nothing
  }

  private setupLayers(): void {
    // Only create the main content layer if it doesn't exist
    const layers = this.stage.getLayers()
    
    if (layers.length === 0) {
      this.mainLayer = new Konva.Layer()
      this.stage.add(this.mainLayer)
    } else {
      this.mainLayer = layers[0]
    }
  }

  private setupRenderers(): void {
    const textRenderer = new TextLayerRenderer()
    textRenderer.setEventEmitter(this.emitter) // Pass event emitter for inline editing
    
    this.renderers.set('text', textRenderer)
    this.renderers.set('image', new ImageLayerRenderer())
    this.renderers.set('shape', new ShapeLayerRenderer())
    this.renderers.set('group', new GroupLayerRenderer())
  }

  private updateKonvaNode(node: Konva.Node, layer: LayerNode): void {
    // Update basic transform properties
    node.setAttrs({
      x: layer.x,
      y: layer.y,
      rotation: layer.rotation,
      scaleX: layer.scaleX,
      scaleY: layer.scaleY,
      opacity: layer.opacity,
      visible: layer.visible,
      listening: !layer.locked
    })

    // Delegate to the appropriate renderer for layer-specific property updates
    const renderer = this.renderers.get(layer.type as LayerType)
    if (renderer && typeof renderer.update === 'function') {
      renderer.update(node, layer)
    }
  }

  private getNextZIndex(): number {
    const layers = Array.from(this.layers.values())
    return layers.length > 0 ? Math.max(...layers.map(l => l.zIndex)) + 1 : 0
  }

  private createLayerNode(type: string, data: Partial<Layer>): LayerNode {
    // Merge default properties with provided properties
    const defaultProps = this.getDefaultProperties(type as Layer['type'])
    const mergedProperties = { ...defaultProps, ...data.properties }
    
    // Get canvas dimensions from stage, but ensure we have proper fallbacks
    // The stage might not be properly sized during initial load from dashboard
    const stageWidth = this.stage.width()
    const stageHeight = this.stage.height()
    
    // Use consistent default positioning that works regardless of load timing
    // Place new layers near the center but offset slightly to avoid overlap
    const canvasWidth = stageWidth > 0 ? stageWidth : 800
    const canvasHeight = stageHeight > 0 ? stageHeight : 600
    
    // Use center positioning with consistent offset
    const defaultX = data.x !== undefined ? data.x : (canvasWidth / 2) - 75 // Slightly left of center
    const defaultY = data.y !== undefined ? data.y : (canvasHeight / 2) - 50 // Slightly above center
    
    const layerNode = {
      id: data.id || `layer_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`,
      type,
      name: data.name || `${type} Layer`,
      visible: data.visible !== false,
      locked: data.locked || false,
      opacity: data.opacity || 1,
      x: defaultX,
      y: defaultY,
      width: data.width || 100,
      height: data.height || 100,
      rotation: data.rotation || 0,
      scaleX: data.scaleX || 1,
      scaleY: data.scaleY || 1,
      zIndex: data.zIndex || this.getNextZIndex(),
      properties: mergedProperties
    } as LayerNode
    
    return layerNode
  }

  private getRenderer(type: string): KonvaLayerRenderer {
    const renderer = this.renderers.get(type as LayerType)
    if (!renderer) {
      throw new Error(`No renderer found for layer type: ${type}`)
    }
    return renderer
  }

  private getDefaultProperties(type: Layer['type']): any {
    switch (type) {
      case 'text':
        return {
          text: 'Text Layer',
          fontSize: 24,
          fontFamily: 'Arial',
          color: '#000000',
          textAlign: 'left',
          fontWeight: 'normal',
          fontStyle: 'normal',
          lineHeight: 1.2,
          letterSpacing: 0,
          textDecoration: 'none'
        }
      case 'shape':
        return {
          fill: '#3498db',
          stroke: '#2980b9',
          strokeWidth: 2,
          shapeType: 'rectangle'
        }
      case 'image':
        return {
          src: ''
        }
      case 'group':
        return {}
      default:
        return {}
    }
  }

  // ============================================================================
}

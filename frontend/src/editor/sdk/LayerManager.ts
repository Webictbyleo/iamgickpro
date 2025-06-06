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
  private transformer: Konva.Transformer | null = null
  private mainLayer!: Konva.Layer

  constructor(
    private stage: Konva.Stage,
    private state: EditorState,
    private emitter: EventEmitter
  ) {
    this.setupRenderers()
    this.setupLayers()
    this.setupTransformer()
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
    
    console.log('LayerManager: Konva node created', {
      layerId: layerNode.id,
      nodeType: konvaNode.getClassName(),
      position: { x: konvaNode.x(), y: konvaNode.y() },
      size: { width: konvaNode.width?.(), height: konvaNode.height?.() },
      visible: konvaNode.visible()
    })
    
    // Add click event to make layer selectable
    konvaNode.on('click tap', () => {
      this.selectLayer(layerNode.id)
    })
    
    // Make layer draggable
    konvaNode.draggable(true)
     // Add to main layer (cast to Shape since all our rendered nodes are shapes/groups)
   this.mainLayer.add(konvaNode as Konva.Shape | Konva.Group)
    
   // Debug: Log layer creation
   console.log('LayerManager: Layer created', {
     layerId: layerNode.id,
     layerType: type,
     konvaNode: konvaNode,
     mainLayerChildren: this.mainLayer.children.length,
     nodePosition: `(${konvaNode.x()}, ${konvaNode.y()})`,
     nodeVisible: konvaNode.visible()
   })
   
   // Force redraw to ensure element appears
   this.mainLayer.batchDraw()
    
    console.log('LayerManager: Added to main layer', {
      mainLayerChildren: this.mainLayer.children.length
    })
    
    // Force redraw to make the element visible
    this.mainLayer.batchDraw()
    
    console.log('LayerManager: Batch draw completed')
    
    // Store layer
    this.layers.set(layerNode.id, layerNode)
    
    // Convert LayerNode to Layer for the event
    const layerData: Layer = {
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
    }
    
    this.emitter.emit('layer:created', layerData)
    return layerNode
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
    this.updateTransformer()
    
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
    this.state.selectedLayers = [layerId]
    this.updateTransformer()
    this.emitSelectionChange()
  }

  selectLayers(layerIds: string[]): void {
    this.state.selectedLayers = [...layerIds]
    this.updateTransformer()
    this.emitSelectionChange()
  }

  deselectAll(): void {
    this.state.selectedLayers = []
    this.updateTransformer()
    this.emitSelectionChange()
  }

  getSelectedLayers(): LayerNode[] {
    return this.state.selectedLayers
      .map(id => this.layers.get(id))
      .filter(Boolean) as LayerNode[]
  }

  // ============================================================================
  // LAYER HIERARCHY
  // ============================================================================

  async setParent(layerId: string, parentId: string | null): Promise<void> {
    const layer = this.layers.get(layerId)
    if (!layer) return

    // Remove from current parent
    if (layer.parent) {
      const childIndex = layer.parent.children?.indexOf(layer) ?? -1
      if (childIndex > -1) {
        layer.parent.children?.splice(childIndex, 1)
      }
    }

    // Set new parent
    if (parentId) {
      const parent = this.layers.get(parentId)
      if (parent) {
        layer.parent = parent
        if (!parent.children) parent.children = []
        parent.children.push(layer)
      }
    } else {
      layer.parent = undefined
    }

    this.updateLayerOrder()
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

  getLayer(layerId: string): LayerNode | null {
    return this.layers.get(layerId) || null
  }

  getAllLayers(): LayerNode[] {
    return Array.from(this.layers.values())
  }

  // ============================================================================
  // LAYER TRANSFORMATION
  // ============================================================================

  async transformLayer(layerId: string, transform: Partial<Transform>): Promise<void> {
    const layer = this.layers.get(layerId)
    if (!layer) return

    // Update layer properties
    if (transform.x !== undefined) layer.x = transform.x
    if (transform.y !== undefined) layer.y = transform.y
    if (transform.width !== undefined) layer.width = transform.width
    if (transform.height !== undefined) layer.height = transform.height
    if (transform.rotation !== undefined) layer.rotation = transform.rotation
    if (transform.scaleX !== undefined) layer.scaleX = transform.scaleX
    if (transform.scaleY !== undefined) layer.scaleY = transform.scaleY
    if (transform.opacity !== undefined) layer.opacity = transform.opacity

    // Update Konva node
    if (layer.konvaNode) {
      this.syncLayerToNode(layer, layer.konvaNode)
    }

    this.emitter.emit('layer:updated', layer)
  }

  getLayerBounds(layerId: string): { x: number, y: number, width: number, height: number } | null {
    const layer = this.layers.get(layerId)
    if (!layer || !layer.konvaNode) return null

    const clientRect = layer.konvaNode.getClientRect()
    return {
      x: clientRect.x,
      y: clientRect.y,
      width: clientRect.width,
      height: clientRect.height
    }
  }

  // ============================================================================
  // PRIVATE METHODS
  // ============================================================================

  private createMainLayer(): Konva.Layer {
    const layer = new Konva.Layer({ name: 'main-layer' })
    this.stage.add(layer)
    return layer
  }

  private createUILayer(): Konva.Layer {
    const layer = new Konva.Layer({ name: 'ui-layer' })
    this.stage.add(layer)
    return layer
  }

  private createLayerNode(type: string, data: Partial<Layer>): LayerNode {
    // Merge default properties with provided properties
    const defaultProps = this.getDefaultProperties(type as Layer['type'])
    const mergedProperties = { ...defaultProps, ...data.properties }
    
    // Get canvas center for better default positioning
    const canvasWidth = this.stage.width() || 800
    const canvasHeight = this.stage.height() || 600
    const defaultX = data.x !== undefined ? data.x : Math.max(50, canvasWidth / 4)
    const defaultY = data.y !== undefined ? data.y : Math.max(50, canvasHeight / 4)
    
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
      zIndex: data.zIndex || 0,
      properties: mergedProperties,
      parent: null,
      children: []
    }
    
    return layerNode
  }

  private updateKonvaNode(node: Konva.Node, layer: LayerNode): void {
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
  }

  private emitSelectionChange(): void {
    this.emitter.emit('layer:selected', this.state.selectedLayers)
  }

  private setupRenderers(): void {
    this.renderers.set('text', new TextLayerRenderer())
    this.renderers.set('image', new ImageLayerRenderer())
    this.renderers.set('shape', new ShapeLayerRenderer())
    this.renderers.set('group', new GroupLayerRenderer())
  }

  private setupLayers(): void {
    // Only create the main content layer if it doesn't exist
    // Don't automatically create UI layers
    const layers = this.stage.getLayers()
    
    if (layers.length === 0) {
      // Only create main content layer
      this.mainLayer = new Konva.Layer()
      this.stage.add(this.mainLayer)
      console.log('LayerManager: Created new main layer')
    } else {
      this.mainLayer = layers[0]
      console.log('LayerManager: Using existing layer')
    }
    
    console.log('LayerManager: Setup complete', {
      mainLayerExists: !!this.mainLayer,
      stageLayers: this.stage.getLayers().length
    })
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
          src: '',
          objectFit: 'contain'
        }
      case 'group':
        return {}
      default:
        return {}
    }
  }

  private getNextZIndex(): number {
    const layers = Array.from(this.layers.values())
    return layers.length > 0 ? Math.max(...layers.map(l => l.zIndex)) + 1 : 0
  }

  private syncLayerToNode(layer: LayerNode, node: Konva.Node): void {
    node.setAttrs({
      x: layer.x,
      y: layer.y,
      width: layer.width,
      height: layer.height,
      rotation: layer.rotation,
      scaleX: layer.scaleX,
      scaleY: layer.scaleY,
      opacity: layer.opacity,
      visible: layer.visible,
      listening: !layer.locked
    })

    // Update renderer-specific properties
    const renderer = this.getRenderer(layer.type)
    if (renderer) {
      renderer.update(node, layer)
    }
  }

  private syncNodeToLayer(node: Konva.Node, layer: LayerNode): void {
    const attrs = node.getAttrs()
    layer.x = attrs.x || 0
    layer.y = attrs.y || 0
    layer.width = attrs.width || layer.width
    layer.height = attrs.height || layer.height
    layer.rotation = attrs.rotation || 0
    layer.scaleX = attrs.scaleX || 1
    layer.scaleY = attrs.scaleY || 1
    layer.opacity = attrs.opacity ?? 1
  }

  private updateLayerOrder(): void {
    const sortedLayers = this.getAllLayers()
    sortedLayers.forEach((layer, index) => {
      if (layer.konvaNode) {
        layer.konvaNode.zIndex(index)
      }
    })
  }

  private updateTransformer(): void {
    const selectedNodes = this.state.selectedLayers
      .map(id => this.getLayer(id))
      .filter((layer): layer is LayerNode => layer !== null && layer.konvaNode !== undefined)
      .map(layer => layer.konvaNode!)

    // Create transformer and UI layer lazily when first needed
    if (selectedNodes.length > 0 && !this.transformer) {
      this.createTransformerLazily()
    }

    if (this.transformer) {
      if (selectedNodes.length > 0) {
        this.transformer.nodes(selectedNodes)
        this.transformer.getLayer()?.batchDraw()
      } else {
        this.transformer.nodes([])
        this.transformer.getLayer()?.batchDraw()
      }
    }
  }

  private createTransformerLazily(): void {
    // Get or create UI layer
    let uiLayer = this.stage.getLayers()[1]
    if (!uiLayer) {
      uiLayer = new Konva.Layer({ name: 'ui-layer' })
      this.stage.add(uiLayer)
    }

    // Create transformer
    this.transformer = new Konva.Transformer({
      boundBoxFunc: (oldBox, newBox) => {
        if (newBox.width < 5 || newBox.height < 5) {
          return oldBox
        }
        return newBox
      }
    })

    uiLayer.add(this.transformer)
  }

  private setupTransformer(): void {
    // Don't create UI layer automatically - only create when actually needed
    // The transformer will be created lazily when first selection is made
  }

  async clear(): Promise<void> {
    // Clear all layers
    Array.from(this.layers.values()).forEach(layer => {
      if (layer.konvaNode) {
        layer.konvaNode.destroy()
      }
    })
    
    this.layers.clear()
    this.state.selectedLayers = []
    this.updateTransformer()
  }

  destroy(): void {
    this.layers.clear()
    this.renderers.clear()
    if (this.transformer) {
      this.transformer.destroy()
      this.transformer = null
    }
  }
}

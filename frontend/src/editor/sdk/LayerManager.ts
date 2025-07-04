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
import { SVGLayerRenderer } from './renderers/SVGLayerRenderer'

export class LayerManager implements LayerAPI {
  private layers: Map<number, LayerNode> = new Map()
  private renderers: Map<LayerType, KonvaLayerRenderer> = new Map()
  private mainLayer!: Konva.Layer
  private transformManager: any = null // Will be injected from EditorSDK
  private historyManager: any = null // Will be injected from EditorSDK

  constructor(
    private stage: Konva.Stage,
    private state: EditorState,
    private emitter: EventEmitter
  ) {
    this.setupRenderers()
    this.setupLayers()
    this.setupStageEventHandlers()
  }

  setTransformManager(transformManager: any): void {
    this.transformManager = transformManager
  }

  setHistoryManager(historyManager: any): void {
    // History is now handled by useDesignHistory composable - disable SDK history
    this.historyManager = null
  }

  /**
   * Get the main layer for external access (e.g., from CanvasManager)
   */
  getMainLayer(): Konva.Layer {
    return this.mainLayer
  }

  /**
   * Update main content layer for viewport changes (zoom/pan)
   * FIXED: Do not apply manual offsets to the main layer!
   * Konva's stage transformation automatically handles zoom/pan for all child layers.
   * Manual offsetting creates double transformation and breaks positioning.
   */
  updateMainLayerForViewport(): void {
    // DO NOTHING - Stage transformation handles zoom/pan automatically
    // The main layer should remain at (0,0) and let the stage handle transformations
  }

  /**
   * Handle viewport changes to update hit detection
   */
  handleViewportChange(data: { zoom: number; panX: number; panY: number }): void {
    // Always refresh hit detection when zoom changes (not just significant changes)
    // This ensures hit detection works correctly at all zoom levels
    
    
    // Update state
    this.state.zoom = data.zoom
    this.state.panX = data.panX
    this.state.panY = data.panY
  }

  // ============================================================================
  // LAYER CRUD OPERATIONS
  // ============================================================================

  /**
   * Force refresh hit detection for all layers - useful after zoom changes
   */
  public refreshHitDetection(): void {
    
  }

  async createLayer(type: string, data: Partial<Layer>, silent: boolean = false): Promise<LayerNode> {
    
    const layerNode = this.createLayerNode(type, data)
    
    // Get renderer for the layer type
    const renderer = this.getRenderer(type)
    
    // Create Konva node
    const konvaNode = renderer.render(layerNode)
    layerNode.konvaNode = konvaNode
    
    // Improve hit detection for scaled stages
    this.improveHitDetection(konvaNode)

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
    
    // Add universal drag handlers for unselected layers only
    // These will be managed dynamically based on selection state
    this.addUniversalDragHandlers(layerNode, konvaNode)
    
    // Add boundary constraints to prevent dragging outside stage
    this.addDragBoundaryConstraints(konvaNode)
    
    // Add to main layer
    this.mainLayer.add(konvaNode as Konva.Shape | Konva.Group)
    
    // Store layer in collection
    this.layers.set(layerNode.id, layerNode)
    
    // Force redraw
    this.mainLayer.batchDraw()
    
    // Add to history for undo/redo
    if (!silent && this.historyManager) {
      this.historyManager.addCreateLayerCommand(layerNode)
    }
    
    // Emit creation event only if not silent and not loading a design
    if (!silent && !this.state.isLoadingDesign) {
      this.emitter.emit('layer:created', this.layerNodeToLayer(layerNode))
    }
    
    return layerNode
  }

  async addLayer(layerData: Partial<Layer>): Promise<LayerNode> {
    // Determine layer type from layerData or default to 'text'
    const type = layerData.type || 'text'
    return this.createLayer(type, layerData)
  }

  async deleteLayer(layerId: number, silent: boolean = false): Promise<void> {
    const layer = this.layers.get(layerId)
    if (!layer) return

    // Store layer data for history before deletion
    const layerDataForHistory = { ...layer }

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
    
    // Add to history for undo/redo
    if (!silent && this.historyManager) {
      this.historyManager.addDeleteLayerCommand(layerDataForHistory)
    }
    
    if (!silent && !this.state.isLoadingDesign) {
      this.emitter.emit('layer:deleted', layerId)
    }
  }

  async updateLayer(layerId: number, updates: Partial<LayerNode>, silent: boolean = false): Promise<void> {
    const layer = this.layers.get(layerId)
    if (!layer) {
      throw new Error(`Layer ${layerId} not found`)
    }

    // Store previous data for history
    const previousData = { ...layer }

    // Update layer properties
    Object.assign(layer, updates)

    // Update Konva node if it exists
    if (layer.konvaNode) {
      this.updateKonvaNode(layer.konvaNode, layer)
    }
    
    // Force redraw to reflect the updates
    this.mainLayer.batchDraw()

    // Add to history for undo/redo
    if (!silent && this.historyManager) {
      this.historyManager.addUpdateLayerCommand(layerId, previousData, layer)
    }

    if (!silent && !this.state.isLoadingDesign) {
      this.emitter.emit('layer:updated', this.layerNodeToLayer(layer))
    }
  }

  /**
   * Update layer ID after backend synchronization
   * Used when a temporary layer ID needs to be replaced with the backend-assigned ID
   */
  updateLayerId(oldId: number, newId: number): boolean {
    const layer = this.layers.get(oldId)
    if (!layer) {
      return false
    }

    // Update the layer's ID
    layer.id = newId
    
    // Update the Konva node's ID
    if (layer.konvaNode) {
      layer.konvaNode.id(newId.toString())
    }

    // Move layer to new ID in the map
    this.layers.delete(oldId)
    this.layers.set(newId, layer)

    // Update selection if the old ID was selected
    const selectedIndex = this.state.selectedLayers.indexOf(oldId)
    if (selectedIndex !== -1) {
      this.state.selectedLayers[selectedIndex] = newId
    }

    return true
  }

  async duplicateLayer(layerId: number): Promise<LayerNode> {
    const originalLayer = this.layers.get(layerId)
    if (!originalLayer) throw new Error(`Layer ${layerId} not found`)

    // Create duplicate data
    const duplicateData = {
      ...originalLayer,
      id: undefined, // Will be generated
      name: `${originalLayer.name} Copy`,
      transform: this.createTransformFromLayerNode(originalLayer, 10, 10)
    } as Partial<Layer>

    return this.createLayer(originalLayer.type, duplicateData)
  }

  // ============================================================================
  // SELECTION AND TRANSFORMATION
  // ============================================================================

  selectLayer(layerId: number): void {
    const layer = this.layers.get(layerId)
    if (!layer) return

    // Update internal selection state
    this.state.selectedLayers = [layerId]
    
    // Update drag handlers for all layers based on new selection
    this.updateDragHandlersForSelection()
    
    // Delegate to transform manager
    if (this.transformManager) {
      this.transformManager.selectLayer(layer)
    }
    
    // Emit selection change event
    this.emitSelectionChange()
  }

  selectLayers(layerIds: number[]): void {
    // Update internal selection state
    this.state.selectedLayers = layerIds.filter(id => this.layers.has(id))
    
    // Update drag handlers for all layers based on new selection
    this.updateDragHandlersForSelection()
    
    // Delegate to transform manager
    if (this.transformManager) {
      const layers = this.state.selectedLayers.map(id => this.layers.get(id)).filter(Boolean) as LayerNode[]
      this.transformManager.selectLayers(layers)
    }
    
    // Emit selection change event
    this.emitSelectionChange()
  }

  toggleSelection(layerId: number): void {
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
    
    // Update drag handlers for all layers (all will now get universal handlers)
    this.updateDragHandlersForSelection()
    
    // Delegate to transform manager
    if (this.transformManager) {
      this.transformManager.deselectAll()
    }
    
    // Emit selection change event
    this.emitSelectionChange()
  }

  getLayer(layerId: number): LayerNode | null {
    return this.layers.get(layerId) || null
  }

  getAllLayers(): LayerNode[] {
    return Array.from(this.layers.values())
  }

  moveLayer(layerId: number, newIndex: number): void {
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

  reorderLayers(layerIds: number[], silent: boolean = false): void {
    // Validate that all layer IDs exist
    const validLayerIds = layerIds.filter(id => this.layers.has(id))
    if (validLayerIds.length === 0) return

    // Store previous order for history
    const previousOrder = Array.from(this.layers.values())
      .sort((a, b) => a.zIndex - b.zIndex)
      .map(layer => layer.id)

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

    // Remove all Konva nodes from the layer except background
    const backgroundRect = this.mainLayer.findOne('.canvas-background') as Konva.Rect
    this.mainLayer.removeChildren()
    
    // Re-add background if it exists
    if (backgroundRect) {
      this.mainLayer.add(backgroundRect)
      backgroundRect.moveToBottom()
    }

    // Re-add them in the correct order (bottom to top)
    reorderedLayers.forEach(layer => {
      if (layer.konvaNode) {
        layer.konvaNode.zIndex(layer.zIndex)
        this.mainLayer.add(layer.konvaNode as Konva.Shape | Konva.Group)
      }
    })

    // Force redraw to reflect the new order
    this.mainLayer.batchDraw()

    // Add to history for undo/redo
    if (!silent && this.historyManager) {
      this.historyManager.addReorderLayersCommand(previousOrder, validLayerIds)
    }

    if (!silent && !this.state.isLoadingDesign) {
      this.emitter.emit('layers:reordered', { layerIds: validLayerIds })
    }
  }

  // ============================================================================
  // LAYER ORDERING METHODS
  // ============================================================================

  bringToFront(layerId: number): void {
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

  bringForward(layerId: number): void {
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

  sendBackward(layerId: number): void {
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

  sendToBack(layerId: number): void {
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
    
    // Preserve background when clearing layers
    const backgroundRect = this.mainLayer.findOne('.canvas-background') as Konva.Rect
    this.mainLayer.removeChildren()
    
    // Re-add background if it exists
    if (backgroundRect) {
      this.mainLayer.add(backgroundRect)
      backgroundRect.moveToBottom()
    }
    
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

  /**
   * Convert LayerNode (internal SDK format) to Layer (API format) for event emission
   */
  private layerNodeToLayer(layerNode: LayerNode): Layer {
    return {
      id: layerNode.id,
      type: layerNode.type as LayerTypeImport,
      name: layerNode.name,
      visible: layerNode.visible,
      locked: layerNode.locked,
      transform: {
        x: layerNode.x,
        y: layerNode.y,
        width: layerNode.width,
        height: layerNode.height,
        rotation: layerNode.rotation,
        scaleX: layerNode.scaleX,
        scaleY: layerNode.scaleY,
        opacity: layerNode.opacity
      },
      zIndex: layerNode.zIndex,
      properties: layerNode.properties
    }
  }

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
    // Check if we already have a main content layer
    const existingMainLayer = this.stage.findOne('.main-content-layer')
    
    if (existingMainLayer && existingMainLayer instanceof Konva.Layer) {
      this.mainLayer = existingMainLayer
      // Ensure it's positioned at origin (fix any previous incorrect positioning)
      this.mainLayer.setAttrs({ x: 0, y: 0 })
      return
    }

    // Create a dedicated main content layer for user content
    // This ensures separation from CanvasManager's background layer
    this.mainLayer = new Konva.Layer({
      name: 'main-content-layer',
      id: 'main-content-layer',
      x: 0,  // Always keep at origin
      y: 0   // Always keep at origin
    })    
    // Add CSS-style class for better identification
    this.mainLayer.setAttr('className', 'main-content-layer')
    
    this.stage.add(this.mainLayer)
    
    // Canvas clipping is now handled by CanvasManager
    
    // Ensure the main content layer is above any background layers
    // Background layers should stay at the bottom
    const backgroundLayer = this.stage.findOne('.background-layer')
    if (backgroundLayer) {
      backgroundLayer.moveToBottom()
    }
  }

  private setupRenderers(): void {
    const textRenderer = new TextLayerRenderer()
    textRenderer.setEventEmitter(this.emitter) // Pass event emitter for inline editing
    
    this.renderers.set('text', textRenderer)
    this.renderers.set('image', new ImageLayerRenderer())
    this.renderers.set('shape', new ShapeLayerRenderer())
    this.renderers.set('group', new GroupLayerRenderer())
    this.renderers.set('svg', new SVGLayerRenderer())

    // Set up text editing event handlers
    this.emitter.on('layer:update-properties', this.handleLayerPropertyUpdate.bind(this))
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

  /**
   * Handle layer property updates from inline text editing
   */
  private async handleLayerPropertyUpdate(data: { layerId: number; properties: any }): Promise<void> {
    const { layerId, properties } = data
    const layer = this.layers.get(layerId)
    
    if (!layer) {
      return
    }

    // Update layer properties
    Object.assign(layer.properties, properties)

    // Update Konva node if it exists
    if (layer.konvaNode) {
      this.updateKonvaNode(layer.konvaNode, layer)
    }
    
    // Force redraw to reflect the updates
    this.mainLayer.batchDraw()

    // Add to history for undo/redo
    if (this.historyManager) {
      const previousData = { ...layer }
      const newData = { ...layer, properties: { ...layer.properties, ...properties } }
      this.historyManager.addUpdateLayerCommand(layerId, previousData, newData)
    }

    // Emit layer updated event only if not in loading state
    if (!this.state.isLoadingDesign) {
      this.emitter.emit('layer:updated', this.layerNodeToLayer(layer))
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
    const defaultX = data.transform?.x !== undefined ? data.transform.x : (canvasWidth / 2) - 75 // Slightly left of center
    const defaultY = data.transform?.y !== undefined ? data.transform.y : (canvasHeight / 2) - 50 // Slightly above center
    
    const layerNode = {
      id: data.id || -Date.now(), // Use negative timestamp as temporary number ID
      type,
      name: data.name || `${type} Layer`,
      visible: data.visible !== false,
      locked: data.locked || false,
      opacity: data.transform?.opacity || 1,
      x: defaultX,
      y: defaultY,
      width: data.transform?.width || 100,
      height: data.transform?.height || 100,
      rotation: data.transform?.rotation || 0,
      scaleX: data.transform?.scaleX || 1,
      scaleY: data.transform?.scaleY || 1,
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

  /**
   * Create a transform object from LayerNode properties
   */
  private createTransformFromLayerNode(layerNode: LayerNode, offsetX: number = 0, offsetY: number = 0): Transform {
    return {
      x: layerNode.x + offsetX,
      y: layerNode.y + offsetY,
      width: layerNode.width,
      height: layerNode.height,
      rotation: layerNode.rotation,
      scaleX: layerNode.scaleX,
      scaleY: layerNode.scaleY,
      opacity: layerNode.opacity
    }
  }

  /**
   * Setup stage event handlers for debugging coordinate issues
   */
  private setupStageEventHandlers(): void {
    // Add stage click handler for debugging
    this.stage.on('click tap', (evt: Konva.KonvaEventObject<MouseEvent | TouchEvent>) => {
      const pointer = this.stage.getPointerPosition()
      const stageScale = this.stage.scaleX()
      const stagePosition = this.stage.position()
      
      // Try to find what was actually clicked
      let targetInfo: any = 'unknown'
      if (evt.target) {
        targetInfo = {
          className: evt.target.className,
          name: evt.target.name?.() || 'unnamed',
          type: evt.target.getType?.() || 'unknown',
          position: (evt.target as any).x ? { x: (evt.target as any).x(), y: (evt.target as any).y() } : 'no-position',
          listening: evt.target.listening?.() || 'unknown'
        }
      }
      
      // If clicking on empty stage, deselect all
      if (evt.target === this.stage) {
        this.deselectAll()
      }
    })
    
    // Listen for viewport changes to update hit detection
    this.emitter.on('viewport:changed', this.handleViewportChange.bind(this))
  }

  /**
   * Improve hit detection for scaled stages
   */
  private improveHitDetection(konvaNode: Konva.Node): void {
    // Ensure the node has proper hit detection
    (konvaNode as any).perfectDrawEnabled?.(false) // Improve performance if method exists
    konvaNode.listening(true) // Ensure it's listening to events
    
    // For text nodes, ensure they have proper hit detection that works with scaling
    if (konvaNode instanceof Konva.Text) {
      const textNode = konvaNode as Konva.Text
      
      // Reset any custom hit function to use default behavior
      textNode.hitFunc(null)
      
      // Instead, ensure the text node has proper bounds
      textNode.listening(true)
      textNode.perfectDrawEnabled(false)
    }
    
    // For shapes, ensure they have proper hit areas
    if (konvaNode instanceof Konva.Shape) {
      const shapeNode = konvaNode as Konva.Shape
      const strokeWidth = (shapeNode as any).strokeWidth?.() || 0
      
      // Ensure adequate hit stroke width for small scales
      const minHitWidth = 4
      shapeNode.hitStrokeWidth(Math.max(minHitWidth, strokeWidth))
      shapeNode.listening(true)
      shapeNode.perfectDrawEnabled(false)
    }
    
    // For groups and other nodes, ensure they have proper listening
    if (konvaNode instanceof Konva.Group) {
      // Groups should propagate events to children
      konvaNode.listening(true)
      
      // Recursively improve hit detection for all children
      konvaNode.getChildren().forEach(child => {
        this.improveHitDetection(child)
      })
    }
    
    // For images, ensure they are listening
    if (konvaNode instanceof Konva.Image) {
      konvaNode.listening(true)
      konvaNode.perfectDrawEnabled(false)
    }
  }

  // ============================================================================
  // UNIVERSAL DRAG HANDLING
  // ============================================================================

  /**
   * Add universal drag handlers to ALL layers, ensuring layer updates are always emitted
   */
  private addUniversalDragHandlers(layerNode: LayerNode, konvaNode: Konva.Node): void {
    let dragStartPosition: { x: number; y: number } | null = null

    // Drag start handler
    konvaNode.on('dragstart.universal', () => {
      // Store initial position
      dragStartPosition = { x: layerNode.x, y: layerNode.y }
      
      // Update cursor
      const container = konvaNode.getStage()?.container()
      if (container) {
        container.style.cursor = 'grabbing'
      }
    })

    // Drag move handler for position tracking (no events emitted here)
    konvaNode.on('dragmove.universal', () => {
      if (!konvaNode) return
      
      // Update layer position silently during drag (no events emitted)
      layerNode.x = konvaNode.x()
      layerNode.y = konvaNode.y()
    })

    // Drag end handler
    konvaNode.on('dragend.universal', () => {
      // Update final position
      if (konvaNode) {
        layerNode.x = konvaNode.x()
        layerNode.y = konvaNode.y()
      }
      
      
      // Reset cursor
      const container = konvaNode.getStage()?.container()
      if (container) {
        container.style.cursor = 'default'
      }
      
      // Emit final layer update event
      if (!this.state.isLoadingDesign) {
        this.emitter.emit('layer:updated', this.layerNodeToLayer(layerNode))
      }
      
      // Add to history if position actually changed
      if (dragStartPosition && this.historyManager && 
          (dragStartPosition.x !== layerNode.x || dragStartPosition.y !== layerNode.y)) {
        this.historyManager.addTransformLayerCommand(
          layerNode.id,
          dragStartPosition,
          { x: layerNode.x, y: layerNode.y }
        )
      }
      
      dragStartPosition = null
    })
  }

  /**
   * Add boundary constraints to prevent layers from being dragged outside the stage
   */
  private addDragBoundaryConstraints(konvaNode: Konva.Node): void {
    konvaNode.dragBoundFunc((pos) => {
      const stage = konvaNode.getStage()
      if (!stage) return pos
      
      // Get canvas dimensions from CanvasManager instead of stage dimensions
      // This ensures proper bounds based on actual design canvas size
      const canvasSize = this.getCanvasSize()
      const canvasWidth = canvasSize.width
      const canvasHeight = canvasSize.height
      
      // Get node dimensions
      const nodeBox = konvaNode.getClientRect()
      
      // Calculate boundaries (keep at least part of the object visible)
      const minX = -nodeBox.width + 50 // Allow 50px outside left edge
      const maxX = canvasWidth - 50     // Allow 50px outside right edge
      const minY = -nodeBox.height + 50 // Allow 50px outside top edge
      const maxY = canvasHeight - 50     // Allow 50px outside bottom edge
      
      return {
        x: Math.max(minX, Math.min(maxX, pos.x)),
        y: Math.max(minY, Math.min(maxY, pos.y))
      }
    })
  }

  /**
   * Get canvas dimensions from state or fallback to defaults
   */
  private getCanvasSize(): { width: number; height: number } {
    // Use stage dimensions as they represent the canvas size
    // CanvasManager should ensure stage dimensions match canvas content size
    const stageWidth = this.stage.width()
    const stageHeight = this.stage.height()
    return {
      width: stageWidth > 0 ? stageWidth : 800,
      height: stageHeight > 0 ? stageHeight : 600
    }
  }

  /**
   * Enable universal drag handlers for unselected layers
   */
  private enableUniversalDragHandlers(layerNode: LayerNode): void {
    if (!layerNode.konvaNode) return
    
    // Only enable if layer is not currently selected
    if (!this.state.selectedLayers.includes(layerNode.id)) {
      this.addUniversalDragHandlers(layerNode, layerNode.konvaNode)
    }
  }

  /**
   * Disable universal drag handlers (when layer becomes selected)
   */
  private disableUniversalDragHandlers(layerNode: LayerNode): void {
    if (!layerNode.konvaNode) return
    
    // Remove universal drag handlers
    layerNode.konvaNode.off('dragstart.universal')
    layerNode.konvaNode.off('dragmove.universal')
    layerNode.konvaNode.off('dragend.universal')
  }

  /**
   * Update drag handlers for all layers based on current selection
   */
  private updateDragHandlersForSelection(): void {
    // Update drag handlers for all layers based on their selection state
    for (const layer of this.layers.values()) {
      if (this.state.selectedLayers.includes(layer.id)) {
        // Layer is selected - disable universal handlers (TransformManager will handle)
        this.disableUniversalDragHandlers(layer)
      } else {
        // Layer is not selected - enable universal handlers
        this.enableUniversalDragHandlers(layer)
      }
    }
  }

  // ============================================================================
  // LAYER CRUD OPERATIONS (CONTINUED)
  // ============================================================================
}

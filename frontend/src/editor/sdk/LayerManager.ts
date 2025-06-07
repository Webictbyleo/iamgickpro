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
    const layer = this.layers.get(layerId)
    if (!layer) return

    // Update internal selection state
    this.state.selectedLayers = [layerId]
    
    // Update visual selection
    if (layer.konvaNode) {
      layer.konvaNode.moveToTop()
      this.mainLayer.batchDraw()
    }

    // Update transformer
    this.updateTransformer()

    // Emit selection event
    this.emitSelectionChange()
  }

  selectLayers(layerIds: string[]): void {
    // Update internal selection state
    this.state.selectedLayers = layerIds.filter(id => this.layers.has(id))
    
    // Update visual selection
    this.state.selectedLayers.forEach(id => {
      const layer = this.layers.get(id)
      if (layer?.konvaNode) {
        layer.konvaNode.moveToTop()
      }
    })
    
    this.mainLayer.batchDraw()

    // Update transformer
    this.updateTransformer()

    // Emit selection event
    this.emitSelectionChange()
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
    
    // Update transformer
    this.updateTransformer()
    
    // Emit selection event
    this.emitSelectionChange()
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
    if (this.transformer) {
      this.transformer.destroy()
      this.transformer = null
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

  private updateTransformer(): void {
    // Get selected layer nodes
    const selectedLayers = this.state.selectedLayers
      .map(id => this.getLayer(id))
      .filter((layer): layer is LayerNode => layer !== null && layer.konvaNode !== undefined)
    
    const selectedNodes = selectedLayers.map(layer => layer.konvaNode!)

    // Create transformer lazily when first needed
    if (selectedNodes.length > 0 && !this.transformer) {
      this.createTransformerLazily()
    }

    if (this.transformer) {
      // Update transformer nodes
      this.transformer.nodes(selectedNodes)

      // Configure transformer behavior based on selected layer types
      this.configureTransformerForLayers(selectedLayers)

      // Update transformer visibility
      this.transformer.visible(selectedNodes.length > 0)

      // Force redraw of transformer layer
      this.transformer.getLayer()?.batchDraw()
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

  private configureTransformerForLayers(layers: LayerNode[]): void {
    if (!this.transformer) return

    // Check if all selected layers are text layers
    const allTextLayers = layers.every(layer => layer.type === 'text')
    const hasTextLayers = layers.some(layer => layer.type === 'text')

    if (allTextLayers) {
      // For text layers only: Canva-style resize behavior
      this.transformer.setAttrs({
        enabledAnchors: [
          // Corner handles for uniform scaling (prevents distortion)
          'top-left', 'top-right', 'bottom-left', 'bottom-right',
          // Middle handles for width resizing with text wrapping
          'middle-left', 'middle-right'
        ],
        keepRatio: false,
        centeredScaling: false,
        rotateEnabled: true,
        borderEnabled: true,
        anchorSize: 8,
        // Enhanced constraint function for Canva-style text resizing
        boundBoxFunc: (oldBox: { x: number; y: number; width: number; height: number }, newBox: { x: number; y: number; width: number; height: number }) => {
          // Get the current transformer anchor being used
          const activeAnchor = this.transformer?.getActiveAnchor()
          
          // Minimum dimensions
          const minWidth = 50
          const maxWidth = 2000
          const minHeight = 20
          
          // Corner handles: uniform scaling (maintain aspect ratio)
          if (activeAnchor && ['top-left', 'top-right', 'bottom-left', 'bottom-right'].includes(activeAnchor)) {
            // Calculate scale factors
            const scaleX = newBox.width / oldBox.width
            const scaleY = newBox.height / oldBox.height
            
            // Use the smaller scale to maintain aspect ratio
            const uniformScale = Math.min(scaleX, scaleY)
            
            // Apply uniform scaling
            newBox.width = oldBox.width * uniformScale
            newBox.height = oldBox.height * uniformScale
            
            // Apply constraints
            if (newBox.width < minWidth) {
              const constraintScale = minWidth / newBox.width
              newBox.width = minWidth
              newBox.height = newBox.height * constraintScale
            }
            if (newBox.width > maxWidth) {
              const constraintScale = maxWidth / newBox.width
              newBox.width = maxWidth
              newBox.height = newBox.height * constraintScale
            }
            if (newBox.height < minHeight) {
              const constraintScale = minHeight / newBox.height
              newBox.height = minHeight
              newBox.width = newBox.width * constraintScale
            }
          }
          // Middle handles: width resizing with dynamic height expansion
          else if (activeAnchor && ['middle-left', 'middle-right'].includes(activeAnchor)) {
            // Constrain width changes only
            if (newBox.width < minWidth) {
              newBox.width = minWidth
            } else if (newBox.width > maxWidth) {
              newBox.width = maxWidth
            }
            
            // For middle handles, allow height to expand as needed for text wrapping
            // Don't constrain the height to oldBox.height - let it grow for wrapped text
            // The actual height will be calculated in the resize handlers based on text content
            
            // Only apply minimum height constraint
            if (newBox.height < minHeight) {
              newBox.height = minHeight
            }
            // Don't restrict maximum height to allow text expansion
          }
          
          return newBox
        }
      })
    } else if (hasTextLayers) {
      // Mixed selection: disable resize to prevent text distortion
      this.transformer.setAttrs({
        enabledAnchors: [], // No resize handles
        keepRatio: false,
        centeredScaling: false,
        rotateEnabled: false,
        borderEnabled: true,
        anchorSize: 0,
        boundBoxFunc: (oldBox: { x: number; y: number; width: number; height: number }, newBox: { x: number; y: number; width: number; height: number }) => {
          // Prevent any scaling for mixed selections with text
          return oldBox
        }
      })
    } else {
      // Non-text layers: full transform capabilities
      this.transformer.setAttrs({
        enabledAnchors: [
          'top-left', 'top-center', 'top-right',
          'middle-right', 'middle-left',
          'bottom-left', 'bottom-center', 'bottom-right'
        ],
        keepRatio: false,
        centeredScaling: false,
        rotateEnabled: true,
        borderEnabled: true,
        anchorSize: 8,
        boundBoxFunc: (oldBox: { x: number; y: number; width: number; height: number }, newBox: { x: number; y: number; width: number; height: number }) => {
          // Standard minimum size constraints for non-text layers
          if (newBox.width < 5 || newBox.height < 5) {
            return oldBox
          }
          return newBox
        }
      })
    }

    // Enhanced transform event listeners for text layers
    this.transformer.off('transformend.textResize')
    this.transformer.off('transform.textPreview')
    
    if (hasTextLayers) {
      // Real-time preview during transform (optional, can be disabled for performance)
      this.transformer.on('transform.textPreview', () => {
        layers.forEach(layer => {
          if (layer.type === 'text' && layer.konvaNode) {
            this.previewTextLayerResize(layer)
          }
        })
      })
      
      // Final resize handling when transform ends
      this.transformer.on('transformend.textResize', () => {
        layers.forEach(layer => {
          if (layer.type === 'text' && layer.konvaNode) {
            this.handleTextLayerResize(layer)
          }
        })
      })
    }
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

  private setupTransformer(): void {
    // Don't create UI layer automatically - only create when actually needed
    // The transformer will be created lazily when first selection is made
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

  private getNextZIndex(): number {
    const layers = Array.from(this.layers.values())
    return layers.length > 0 ? Math.max(...layers.map(l => l.zIndex)) + 1 : 0
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
  // TEXT LAYER RESIZE HANDLING
  // ============================================================================

  /**
   * Preview text layer resize during transform for real-time feedback
   * This method provides visual feedback without committing changes
   */
  /**
   * Preview text layer resize during transform (Canva-style behavior)
   */
  private previewTextLayerResize(layer: LayerNode): void {
    if (!layer.konvaNode || layer.type !== 'text') return
    
    const textNode = layer.konvaNode as Konva.Text
    const activeAnchor = this.transformer?.getActiveAnchor()
    
    // Store base dimensions if not already stored
    if (!textNode.getAttr('baseDimensions')) {
      textNode.setAttr('baseDimensions', {
        width: layer.width,
        height: layer.height
      })
    }
    
    const baseDimensions = textNode.getAttr('baseDimensions')
    
    // Determine resize type based on active anchor
    const isWidthResize = activeAnchor && ['middle-left', 'middle-right'].includes(activeAnchor)
    const isCornerResize = activeAnchor && ['top-left', 'top-right', 'bottom-left', 'bottom-right'].includes(activeAnchor)
    
    if (isWidthResize) {
      // Middle handle resize: Text wrapping (reset scale, apply width)
      // Calculate the new width based on current transform box size
      const transformBox = textNode.getClientRect()
      
      textNode.setAttrs({
        width: transformBox.width,
        scaleX: 1,
        scaleY: 1
      })
    } else if (isCornerResize) {
      // Corner handle resize: Uniform scaling (keep scale, don't reset)
      // This maintains text appearance and prevents distortion
      const scaleX = textNode.scaleX()
      const scaleY = textNode.scaleY()
      
      // Apply uniform scaling - use the smaller scale to maintain aspect ratio
      const uniformScale = Math.min(Math.abs(scaleX), Math.abs(scaleY))
      
      textNode.setAttrs({
        scaleX: scaleX < 0 ? -uniformScale : uniformScale,
        scaleY: scaleY < 0 ? -uniformScale : uniformScale
      })
    }
  }

  /**
   * Handle final text layer resize when transform ends
   * This method commits the resize changes and applies auto-resize if enabled
   */
  /**
   * Handle final text layer resize when transform ends (Canva-style behavior)  
   */
  private handleTextLayerResize(layer: LayerNode): void {
    if (!layer.konvaNode || layer.type !== 'text') return
    
    const textNode = layer.konvaNode as Konva.Text
    const activeAnchor = this.transformer?.getActiveAnchor()
    
    // Store base dimensions if not already stored
    if (!textNode.getAttr('baseDimensions')) {
      textNode.setAttr('baseDimensions', {
        width: layer.width,
        height: layer.height
      })
    }
    
    const baseDimensions = textNode.getAttr('baseDimensions')
    
    // Determine resize type based on active anchor
    const isWidthResize = activeAnchor && ['middle-left', 'middle-right'].includes(activeAnchor)
    const isCornerResize = activeAnchor && ['top-left', 'top-right', 'bottom-left', 'bottom-right'].includes(activeAnchor)
    
    if (isWidthResize) {
      // Middle handle resize: Text wrapping (reset scale, apply width)
      // Calculate the new width based on current transform box size
      const transformBox = textNode.getClientRect()
      
      textNode.setAttrs({
        width: transformBox.width,
        scaleX: 1,
        scaleY: 1
      })
      
      // Update layer dimensions - reset to new base dimensions without scale
      layer.width = textNode.width()
      layer.height = textNode.height()
      layer.scaleX = 1
      layer.scaleY = 1
      
      // Update stored base dimensions for future operations
      textNode.setAttr('baseDimensions', {
        width: layer.width,
        height: layer.height
      })
      
    } else if (isCornerResize) {
      // Corner handle resize: Uniform scaling (keep scale values)
      const scaleX = textNode.scaleX()
      const scaleY = textNode.scaleY()
      
      // Apply uniform scaling - use the smaller scale to maintain aspect ratio
      const uniformScale = Math.min(Math.abs(scaleX), Math.abs(scaleY))
      
      textNode.setAttrs({
        scaleX: scaleX < 0 ? -uniformScale : uniformScale,
        scaleY: scaleY < 0 ? -uniformScale : uniformScale
      })
      
      // Update layer scale values (base dimensions stay the same)
      layer.scaleX = textNode.scaleX()
      layer.scaleY = textNode.scaleY()
      // width and height remain as original base dimensions
    }

    // Force redraw
    textNode.getLayer()?.draw()

    // Emit layer update event
    this.emitter.emit('layer:updated', { 
      layerId: layer.id, 
      properties: { 
        width: layer.width, 
        height: layer.height,
        scaleX: layer.scaleX,
        scaleY: layer.scaleY
      } 
    })
  }

  /**
   * Optimize text layer rendering quality
   * This method ensures text remains sharp and readable during and after transforms
   */
  private optimizeTextQuality(textNode: Konva.Text): void {
    try {
      // Clear any existing cache that might cause blurriness
      textNode.clearCache()
      
      // Set optimal text rendering properties
      textNode.perfectDrawEnabled(false) // Disable for better performance
      textNode.strokeHitEnabled(false) // Optimize hit detection
      
      // Ensure text is properly positioned on pixel boundaries to prevent blurriness
      const x = Math.round(textNode.x())
      const y = Math.round(textNode.y())
      textNode.position({ x, y })
      
      // Force immediate redraw
      textNode.getLayer()?.batchDraw()
      
    } catch (error) {
      console.warn('Text quality optimization failed:', error)
    }
  }

  /**
   * Validate auto-resize configuration to ensure valid settings
   */
  private validateAutoResizeConfig(config: any): boolean {
    if (!config || typeof config !== 'object') return false
    
    // Check required properties
    if (typeof config.enabled !== 'boolean') return false
    
    // Validate numeric constraints if present
    if (config.minFontSize !== undefined && (typeof config.minFontSize !== 'number' || config.minFontSize < 1)) return false
    if (config.maxFontSize !== undefined && (typeof config.maxFontSize !== 'number' || config.maxFontSize > 300)) return false
    if (config.minWidth !== undefined && (typeof config.minWidth !== 'number' || config.minWidth < 1)) return false
    if (config.maxWidth !== undefined && (typeof config.maxWidth !== 'number' || config.maxWidth < 0)) return false
    
    // Validate mode if present
    if (config.mode !== undefined && !['width', 'height', 'both'].includes(config.mode)) return false
    
    return true
  }

  // ============================================================================
}

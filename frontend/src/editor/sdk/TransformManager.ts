import Konva from 'konva'
import type { LayerNode, EditorState, LayerType } from './types'
import type { EventEmitter } from './EventEmitter'
import type { Layer } from '@/types'

// Define the Box type interface for transformer constraints
interface Box {
  x: number
  y: number
  width: number
  height: number
  rotation: number
}

/**
 * TransformManager - Handles all layer transformations including selection, positioning, and resizing
 * This class takes over transformer responsibilities from LayerManager for cleaner separation of concerns
 */
export class TransformManager {
  private transformer: Konva.Transformer | null = null
  private selectedLayers: LayerNode[] = []
  private historyManager: any = null
  private transformStartStates: Map<number, Partial<LayerNode>> = new Map()
  private currentTransformerLayer: Konva.Layer | null = null

  constructor(
    private stage: Konva.Stage,
    private state: EditorState,
    private emitter: EventEmitter
  ) {
    this.setupTransformer()
    this.setupEventListeners()
  }

  /**
   * Set the history manager for transform capture - DISABLED
   */
  setHistoryManager(historyManager: any): void {
    // History is now handled by useDesignHistory composable - disable SDK history
    this.historyManager = null
    console.log('TransformManager: History management disabled - now handled by useDesignHistory composable')
  }

  // ============================================================================
  // SELECTION MANAGEMENT
  // ============================================================================

  selectLayers(layers: LayerNode[]): void {
    this.selectedLayers = layers.filter(layer => layer.konvaNode)
    this.updateTransformer()
    this.emitSelectionChange()
  }

  selectLayer(layer: LayerNode): void {
    this.selectLayers([layer])
  }

  deselectAll(): void {
    this.selectedLayers = []
    this.updateTransformer()
    this.emitSelectionChange()
  }

  getSelectedLayers(): LayerNode[] {
    return [...this.selectedLayers]
  }

  // ============================================================================
  // POSITION PRESETS
  // ============================================================================

  applyPositionPreset(preset: string, canvasWidth: number, canvasHeight: number): void {
    if (this.selectedLayers.length === 0) return

    console.log(`🎯 TransformManager: Applying position preset "${preset}" to ${this.selectedLayers.length} layer(s)`)

    this.selectedLayers.forEach(layer => {
      // Log layer information for debugging
      console.log(`🔍 Layer ${layer.id} (${layer.type}):`, {
        position: { x: layer.x, y: layer.y },
        dimensions: { width: layer.width, height: layer.height },
        hasKonvaNode: !!layer.konvaNode,
        konvaOffsets: layer.konvaNode ? { 
          offsetX: layer.konvaNode.offsetX(), 
          offsetY: layer.konvaNode.offsetY() 
        } : null
      })

      // Get the effective dimensions and visual bounds
      const effectiveDimensions = this.getEffectiveLayerDimensions(layer)
      const visualBounds = this.getVisualBounds(layer)
      
      console.log(`📏 Effective dimensions for layer ${layer.id}:`, effectiveDimensions)
      console.log(`👁️ Visual bounds for layer ${layer.id}:`, visualBounds)
      
      // Calculate preset position based on layer dimensions (same for all layer types)
      const position = this.calculatePresetPosition(
        preset, 
        layer.width, 
        layer.height, 
        canvasWidth, 
        canvasHeight
      )
      
      console.log(`📍 Calculated position for "${preset}":`, position)
      
      // All layers should be positioned at the exact coordinates requested
      // Internal rendering offsets should be handled in the renderer, not here
      console.log(`🔧 Setting layer ${layer.id} to position:`, position)
      
      // Store old position for history
      const oldPosition = { x: layer.x, y: layer.y }
      
      // Position all layers at the exact coordinates requested (consistent behavior)
      // Internal rendering offsets are handled in the renderer, not here
      layer.x = position.x
      layer.y = position.y
      
      // Update Konva node
      if (layer.konvaNode) {
        layer.konvaNode.setAttrs({
          x: position.x,
          y: position.y
        })
      }
      
      // Emit update event only if not loading a design
      if (!this.state.isLoadingDesign) {
        this.emitter.emit('layer:updated', this.layerNodeToLayer(layer))
      }
    })

    // Force a complete redraw to ensure positions are applied
    this.stage.batchDraw()
    
    // Update transformer to match new positions
    this.updateTransformer()
  }

  private calculatePresetPosition(
    preset: string, 
    width: number, 
    height: number, 
    canvasWidth: number, 
    canvasHeight: number
  ): { x: number; y: number } {
    switch (preset) {
      case 'top-left':
        return { x: 0, y: 0 }
      case 'top-center':
        return { x: (canvasWidth - width) / 2, y: 0 }
      case 'top-right':
        return { x: canvasWidth - width, y: 0 }
      case 'center-left':
        return { x: 0, y: (canvasHeight - height) / 2 }
      case 'center':
        return { x: (canvasWidth - width) / 2, y: (canvasHeight - height) / 2 }
      case 'center-right':
        return { x: canvasWidth - width, y: (canvasHeight - height) / 2 }
      case 'bottom-left':
        return { x: 0, y: canvasHeight - height }
      case 'bottom-center':
        return { x: (canvasWidth - width) / 2, y: canvasHeight - height }
      case 'bottom-right':
        return { x: canvasWidth - width, y: canvasHeight - height }
      default:
        return { x: 0, y: 0 }
    }
  }

  /**
   * Get effective layer dimensions - same for all layer types
   */
  private getEffectiveLayerDimensions(layer: LayerNode): { width: number; height: number } {
    return { width: layer.width, height: layer.height }
  }

  /**
   * Get the layer bounds - same for all layer types  
   */
  private getVisualBounds(layer: LayerNode): { x: number; y: number; width: number; height: number } {
    return {
      x: layer.x,
      y: layer.y,
      width: layer.width,
      height: layer.height
    }
  }

  // ============================================================================
  // TRANSFORMATION HANDLING
  // ============================================================================

  private updateTransformer(): void {
    if (!this.transformer) return

    const selectedNodes = this.selectedLayers
      .map(layer => layer.konvaNode)
      .filter((node): node is Konva.Node => node !== null && node !== undefined)

    // Remove transformer from current layer if it exists
    if (this.currentTransformerLayer && this.transformer.getParent()) {
      this.transformer.remove()
      this.currentTransformerLayer.batchDraw()
    }

    this.transformer.nodes(selectedNodes)
    this.transformer.visible(selectedNodes.length > 0)

    if (selectedNodes.length > 0) {
      // Add transformer to the same layer as the first selected node
      const targetLayer = selectedNodes[0].getLayer() as Konva.Layer
      if (targetLayer) {
        targetLayer.add(this.transformer)
        this.currentTransformerLayer = targetLayer
        this.configureTransformerForLayers()
        this.setupTransformEventHandlers()
        
       
        
        targetLayer.batchDraw()
      }
    } else {
      this.currentTransformerLayer = null
    }
  }

  private configureTransformerForLayers(): void {
    if (!this.transformer || this.selectedLayers.length === 0) return

    const layerTypes = this.selectedLayers.map(layer => layer.type)
    const allText = layerTypes.every(type => type === 'text')
    const allImages = layerTypes.every(type => type === 'image')
    const allShapes = layerTypes.every(type => type === 'shape')
    const allSvg = layerTypes.every(type => type === 'svg')
    const mixed = layerTypes.length > 1 && new Set(layerTypes).size > 1

    if (allText) {
      this.configureTextTransformer()
    } else if (allImages) {
      this.configureImageTransformer()
    } else if (allShapes) {
      this.configureShapeTransformer()
    } else if (allSvg) {
      this.configureSvgTransformer()
    } else if (mixed) {
      this.configureMixedTransformer()
    } else {
      this.configureDefaultTransformer()
    }
  }

  private configureTextTransformer(): void {
    if (!this.transformer) return

    this.transformer.setAttrs({
      enabledAnchors: [
        'top-left', 'top-right', 'bottom-left', 'bottom-right', // Corner scaling
        'middle-left', 'middle-right' // Width resizing for text wrapping
      ],
      keepRatio: false,
      rotateEnabled: true,
      borderEnabled: true,
      anchorSize: this.getResponsiveAnchorSize(),
      boundBoxFunc: (oldBox: Box, newBox: Box) => {
        const activeAnchor = this.transformer?.getActiveAnchor()
        const minWidth = 50

        // Corner handles: uniform scaling
        if (activeAnchor && ['top-left', 'top-right', 'bottom-left', 'bottom-right'].includes(activeAnchor)) {
          const scale = Math.min(newBox.width / oldBox.width, newBox.height / oldBox.height)
          newBox.width = Math.max(minWidth, oldBox.width * scale)
          newBox.height = Math.max(20, oldBox.height * scale)
        }
        // Side handles: width resizing only - DON'T constrain height for text wrapping
        else if (activeAnchor && ['middle-left', 'middle-right'].includes(activeAnchor)) {
          newBox.width = Math.max(minWidth, newBox.width)
          // Let height expand/contract naturally based on text content
          // Don't set any height constraints here
        }

        return newBox
      }
    })
  }

  private configureImageTransformer(): void {
    if (!this.transformer) return

    this.transformer.setAttrs({
      enabledAnchors: [
        'top-left', 'top-right', 'bottom-left', 'bottom-right', // Corner scaling
        'top-center', 'middle-left', 'middle-right', 'bottom-center' // Edge scaling for clipping
      ],
      keepRatio: false,
      rotateEnabled: true,
      borderEnabled: true,
      anchorSize: this.getResponsiveAnchorSize(),
      boundBoxFunc: (oldBox: Box, newBox: Box) => {
        const activeAnchor = this.transformer?.getActiveAnchor()
        const minSize = 20

        // Corner handles: uniform scaling
        if (activeAnchor && ['top-left', 'top-right', 'bottom-left', 'bottom-right'].includes(activeAnchor)) {
          const scale = Math.max(newBox.width / oldBox.width, newBox.height / oldBox.height)
          newBox.width = Math.max(minSize, oldBox.width * scale)
          newBox.height = Math.max(minSize, oldBox.height * scale)
        }
        // Edge handles: directional scaling for clipping
        else {
          newBox.width = Math.max(minSize, newBox.width)
          newBox.height = Math.max(minSize, newBox.height)
        }

        return newBox
      }
    })
  }

  private configureMixedTransformer(): void {
    if (!this.transformer) return

    // For mixed selections, only allow moving (no resize handles)
    this.transformer.setAttrs({
      enabledAnchors: [],
      rotateEnabled: false,
      borderEnabled: true,
      anchorSize: 0
    })
  }

  private configureDefaultTransformer(): void {
    if (!this.transformer) return

    // Standard transformation for other layer types
    this.transformer.setAttrs({
      enabledAnchors: [
        'top-left', 'top-center', 'top-right',
        'middle-left', 'middle-right',
        'bottom-left', 'bottom-center', 'bottom-right'
      ],
      keepRatio: false,
      rotateEnabled: true,
      borderEnabled: true,
      anchorSize: this.getResponsiveAnchorSize(),
      boundBoxFunc: (oldBox: Box, newBox: Box) => {
        return newBox.width < 5 || newBox.height < 5 ? oldBox : newBox
      }
    })
  }

  private configureShapeTransformer(): void {
    if (!this.transformer || this.selectedLayers.length === 0) return

    // Check if all selected shapes should maintain aspect ratio
    const aspectRatioShapes = ['circle', 'heart', 'star', 'polygon', 'triangle']
    const shapeTypes = this.selectedLayers.map(layer => 
      layer.properties?.shapeType || 'rectangle'
    )
    const shouldMaintainAspectRatio = shapeTypes.some(shapeType => 
      aspectRatioShapes.includes(shapeType)
    )

    if (shouldMaintainAspectRatio) {
      // Configure transformer to maintain aspect ratio for proportional shapes
      this.transformer.setAttrs({
        enabledAnchors: [
          'top-left', 'top-right', 'bottom-left', 'bottom-right' // Only corner handles
        ],
        keepRatio: true, // This maintains proportions during scaling
        rotateEnabled: true,
        borderEnabled: true,
        anchorSize: this.getResponsiveAnchorSize(),
        boundBoxFunc: (oldBox: Box, newBox: Box) => {
          const minSize = 20
          
          // Ensure minimum size is maintained
          if (newBox.width < minSize || newBox.height < minSize) {
            const scale = Math.max(minSize / newBox.width, minSize / newBox.height)
            newBox.width = Math.max(minSize, newBox.width * scale)
            newBox.height = Math.max(minSize, newBox.height * scale)
          }
          
          return newBox
        }
      })
    } else {
      // For rectangular shapes and lines, allow free transformation
      this.transformer.setAttrs({
        enabledAnchors: [
          'top-left', 'top-center', 'top-right',
          'middle-left', 'middle-right',
          'bottom-left', 'bottom-center', 'bottom-right'
        ],
        keepRatio: false,
        rotateEnabled: true,
        borderEnabled: true,
        anchorSize: this.getResponsiveAnchorSize(),
        boundBoxFunc: (oldBox: Box, newBox: Box) => {
          const minSize = 20
          newBox.width = Math.max(minSize, newBox.width)
          newBox.height = Math.max(minSize, newBox.height)
          return newBox
        }
      })
    }
  }

  private configureSvgTransformer(): void {
    if (!this.transformer) return

    // Configure transformer for SVG layers - similar to images but optimized for vector graphics
    this.transformer.setAttrs({
      enabledAnchors: [
        'top-left', 'top-center', 'top-right',
        'middle-left', 'middle-right',
        'bottom-left', 'bottom-center', 'bottom-right'
      ],
      keepRatio: false, // Allow non-proportional scaling for SVG graphics
      rotateEnabled: true,
      borderEnabled: true,
      anchorSize: this.getResponsiveAnchorSize(),
      boundBoxFunc: (oldBox: Box, newBox: Box) => {
        const minSize = 20
        
        // Ensure minimum size for SVG layers
        if (newBox.width < minSize || newBox.height < minSize) {
          newBox.width = Math.max(minSize, newBox.width)
          newBox.height = Math.max(minSize, newBox.height)
        }
        
        return newBox
      }
    })
  }

  private getResponsiveAnchorSize(): number {
    const baseSize = 6
    const scale = this.stage.scaleX() || 1
    
    // Calculate responsive size - anchors should maintain consistent screen size
    // When zoomed in (scale > 1), we need larger values to maintain visual size
    // When zoomed out (scale < 1), we need smaller values but with minimum
    const responsiveSize = baseSize / scale
    
    // Ensure minimum size for usability and maximum size to prevent huge anchors
    const finalSize = Math.max(10, Math.min(responsiveSize, 18))
    
    // Log anchor size changes for debugging
    console.log(`🎯 TransformManager: Anchor size calculated - scale: ${scale.toFixed(2)}, size: ${finalSize.toFixed(1)}px`)
    
    return finalSize
  }

  private setupTransformEventHandlers(): void {
    if (!this.transformer) return

    // Remove existing handlers
    this.transformer.off('transformstart.transform-manager')
    this.transformer.off('transform.transform-manager')
    this.transformer.off('transformend.transform-manager')

    // Add transform start handler to capture initial state
    this.transformer.on('transformstart.transform-manager', () => {
      this.handleTransformStart()
    })

    // Add transform handler for real-time updates (especially for images)
    this.transformer.on('transform.transform-manager', () => {
      this.handleTransform()
    })

    // Add transform end handler for final cleanup
    this.transformer.on('transformend.transform-manager', () => {
      this.handleTransformEnd()
    })

    // Add individual transform handlers for each selected node
    this.addNodeTransformHandlers()
    
    // Add drag and transform handlers for the selected nodes
    this.addNodeDragHandlers()
    this.addNodeTransformHandlers()
  }

  private handleTransformStart(): void {
    // Capture initial transform state for history
    this.transformStartStates.clear()
    
    // Disable command merging for discrete transform operations
    if (this.historyManager && typeof this.historyManager.disableMergingTemporarily === 'function') {
      this.historyManager.disableMergingTemporarily(2000) // 2 seconds
    }
    
    this.selectedLayers.forEach(layer => {
      if (!layer.konvaNode) return
      
      // Capture the current state before transformation
      const currentState = {
        id: layer.id,
        x: layer.x,
        y: layer.y,
        width: layer.width,
        height: layer.height,
        rotation: layer.rotation,
        scaleX: layer.scaleX || 1,
        scaleY: layer.scaleY || 1
      }
      
      this.transformStartStates.set(layer.id, currentState)
    })
  }

  private handleTransform(): void {
    // Handle real-time transformations for images and text
    this.selectedLayers.forEach(layer => {
      if (!layer.konvaNode) return

      if (layer.type === 'image') {
        this.handleImageTransformRealtime(layer)
      } else if (layer.type === 'text') {
        this.handleTextTransformRealtime(layer)
      }
      // Shapes and SVG don't need special real-time handling
    })
  }

  private handleImageTransformRealtime(layer: LayerNode): void {
    // Handle real-time image transformation during transform event
    if (!layer.konvaNode || layer.type !== 'image') return

    // Find the Image node within the group
    let imageNode: Konva.Image | null = null
    
    if (layer.konvaNode instanceof Konva.Group) {
      imageNode = layer.konvaNode.findOne('Image') as Konva.Image
    } else if (layer.konvaNode instanceof Konva.Image) {
      imageNode = layer.konvaNode
    }
    
    if (!imageNode || typeof imageNode.image !== 'function') return

    // For Groups, check the group's scale, not the image's scale
    const groupNode = layer.konvaNode instanceof Konva.Group ? layer.konvaNode : null
    const scaleX = groupNode ? groupNode.scaleX() : imageNode.scaleX()
    const scaleY = groupNode ? groupNode.scaleY() : imageNode.scaleY()
    
    if (scaleX !== 1 || scaleY !== 1) {
      // Reset scale on the group if it exists, otherwise on the image
      if (groupNode) {
        groupNode.setAttrs({
          scaleX: 1,
          scaleY: 1,
          width: groupNode.width() * Math.abs(scaleX),
          height: groupNode.height() * Math.abs(scaleY),
        })
        
        // Update the image size to match the group
        imageNode.setAttrs({
          width: groupNode.width(),
          height: groupNode.height(),
        })
      } else {
        imageNode.setAttrs({
          scaleX: 1,
          scaleY: 1,
          width: imageNode.width() * Math.abs(scaleX),
          height: imageNode.height() * Math.abs(scaleY),
        })
      }
      
      // Apply crop with the new dimensions
      this.applyCrop(imageNode, layer.properties?.objectPosition || 'center-middle')
    }
  }

  private handleTextTransformRealtime(layer: LayerNode): void {
    // Handle real-time text transformation during transform event
    if (!layer.konvaNode || layer.type !== 'text') return

    const textNode = layer.konvaNode as Konva.Text
    const activeAnchor = this.transformer?.getActiveAnchor()

    if (!activeAnchor || !textNode) return

    // Check if we're using middle handles for width resizing (text wrapping)
    if (['middle-left', 'middle-right'].includes(activeAnchor)) {
      // Follow Konva documentation pattern exactly:
      // Just reset scale and apply to width - that's it!
      textNode.setAttrs({
        width: textNode.width() * textNode.scaleX(),
        scaleX: 1,
      })
    }
    // Corner handles maintain aspect ratio for uniform scaling
    else if (['top-left', 'top-right', 'bottom-left', 'bottom-right'].includes(activeAnchor)) {
      // For corner handles, we let the scale happen normally for uniform scaling
      // The final scale will be applied in handleTextTransform during transformend
    }
  }

  private handleShapeTransformRealtime(layer: LayerNode): void {
    // Handle real-time shape transformation during transform event
    if (!layer.konvaNode || layer.type !== 'shape') return

    const shapeNode = layer.konvaNode as Konva.Shape
    const shapeType = layer.properties?.shapeType || 'rectangle'
    
    // For path-based shapes (arrow, heart), handle real-time path regeneration
    if (['arrow', 'heart'].includes(shapeType) && shapeNode instanceof Konva.Path) {
      const scaleX = shapeNode.scaleX()
      const scaleY = shapeNode.scaleY()
      
      if (scaleX !== 1 || scaleY !== 1) {
        // Calculate new dimensions
        const newWidth = layer.width * Math.abs(scaleX)
        const newHeight = layer.height * Math.abs(scaleY)
        
        // Update layer dimensions first
        layer.width = newWidth
        layer.height = newHeight
        
        // Regenerate path with new dimensions
        this.regeneratePathForShape(shapeNode, shapeType, newWidth, newHeight)
        
        // Reset scale
        shapeNode.setAttrs({
          scaleX: 1,
          scaleY: 1
        })
      }
    }
  }

  private regeneratePathForShape(shapeNode: Konva.Path, shapeType: string, width: number, height: number): void {
    // Generate SVG path data for specific shape types with given dimensions
    let pathData = ''

    switch (shapeType) {
      case 'heart':
        pathData = this.generateHeartPath(width, height)
        break
      case 'arrow':
        pathData = this.generateArrowPath(width, height)
        break
      default:
        console.warn(`regeneratePathForShape: Unsupported shape type: ${shapeType}`)
        return
    }

    // Update the path node with new path data
    shapeNode.setAttrs({
      data: pathData,
      width: width,
      height: height
    })
  }

  private generateHeartPath(width: number, height: number): string {
    // Generate heart shape SVG path scaled to given dimensions
    const w = width
    const h = height
    const centerX = w / 2
    const topY = h * 0.3
    
    // Heart shape path using cubic bezier curves
    return `M ${centerX} ${h * 0.9} 
            C ${centerX} ${h * 0.65}, ${w * 0.1} ${topY}, ${centerX} ${topY}
            C ${w * 0.9} ${topY}, ${centerX} ${h * 0.65}, ${centerX} ${h * 0.9} Z`
  }

  private generateArrowPath(width: number, height: number): string {
    // Generate arrow shape SVG path scaled to given dimensions
    const w = width
    const h = height
    const shaftWidth = h * 0.3
    const headWidth = h * 0.8
    const headLength = w * 0.3
    
    // Arrow shape path
    return `M 0 ${(h - shaftWidth) / 2}
            L ${w - headLength} ${(h - shaftWidth) / 2}
            L ${w - headLength} ${(h - headWidth) / 2}
            L ${w} ${h / 2}
            L ${w - headLength} ${(h + headWidth) / 2}
            L ${w - headLength} ${(h + shaftWidth) / 2}
            L 0 ${(h + shaftWidth) / 2}
            Z`
  }

  private handleTransformEnd(): void {
    this.selectedLayers.forEach(layer => {
      if (!layer.konvaNode) return

      const node = layer.konvaNode
      
      // Update layer data with transform results
      layer.x = node.x()
      layer.y = node.y()
      layer.rotation = node.rotation()

      // Handle layer-specific transformations
      if (layer.type === 'text') {
        this.handleTextTransform(layer)
      } else if (layer.type === 'image') {
        this.handleImageTransform(layer)
      } else if (layer.type === 'svg') {
        this.handleSvgTransform(layer)
      }

      // For non-text, non-image, non-svg layers (shapes), reset scale to 1 and apply to width/height
      // Text, image, and SVG layers are handled in their specific transform methods above
      if (layer.type !== 'image' && layer.type !== 'text' && layer.type !== 'svg' && (node.scaleX() !== 1 || node.scaleY() !== 1)) {
        layer.width = layer.width * Math.abs(node.scaleX())
        layer.height = layer.height * Math.abs(node.scaleY())
        layer.scaleX = 1
        layer.scaleY = 1
        
        node.setAttrs({
          width: layer.width,  
          height: layer.height,
          scaleX: 1,
          scaleY: 1
        })
      }

      // Emit update event only if not loading a design
      if (!this.state.isLoadingDesign) {
        this.emitter.emit('layer:updated', this.layerNodeToLayer(layer))
      }
    })

    // Capture final state and add to history if history manager is available
    if (this.historyManager && this.transformStartStates.size > 0) {
      this.selectedLayers.forEach(layer => {
        const initialState = this.transformStartStates.get(layer.id)
        if (initialState) {
          const finalState = {
            id: layer.id,
            x: layer.x,
            y: layer.y,
            width: layer.width,
            height: layer.height,
            rotation: layer.rotation,
            scaleX: layer.scaleX || 1,
            scaleY: layer.scaleY || 1
          }
          
          // Only add to history if something actually changed
          const hasChanged = (
            initialState.x !== finalState.x ||
            initialState.y !== finalState.y ||
            initialState.width !== finalState.width ||
            initialState.height !== finalState.height ||
            initialState.rotation !== finalState.rotation ||
            initialState.scaleX !== finalState.scaleX ||
            initialState.scaleY !== finalState.scaleY
          )
          
          if (hasChanged) {
            this.historyManager.addTransformLayerCommand(layer.id, initialState, finalState)
          }
        }
      })
      
      // Clear transform states
      this.transformStartStates.clear()
    }

    // Update transformer to reflect new node dimensions
    this.updateTransformer()

    this.stage.batchDraw()
  }

  private handleTextTransform(layer: LayerNode): void {
    // Handle text-specific transformations following Konva docs exactly
    const textNode = layer.konvaNode as Konva.Text  
    const activeAnchor = this.transformer?.getActiveAnchor()

    if (!textNode || !activeAnchor) return
    
    // Middle handles: Width resizing for text wrapping
    if (['middle-left', 'middle-right'].includes(activeAnchor)) {
      // Following Konva docs: just update width, let height be auto-calculated
      layer.width = textNode.width()
      // DON'T set layer.height - let it be determined by text content
      // DON'T set height on textNode - it should wrap naturally
    }
    // Corner handles: Uniform scaling
    else if (['top-left', 'top-right', 'bottom-left', 'bottom-right'].includes(activeAnchor)) {
      // Uniform scaling: apply scale to both font size and dimensions
      const scaleX = Math.abs(textNode.scaleX())
      const scaleY = Math.abs(textNode.scaleY())
      const scale = Math.min(scaleX, scaleY) // Use minimum scale for uniform scaling
      
      // Get current font size and calculate new font size
      const currentFontSize = textNode.fontSize() || 16
      const newFontSize = Math.max(8, currentFontSize * scale) // Minimum font size of 8
      
      // Calculate new width
      const newWidth = textNode.width() * scaleX
      
      // Apply the scaling - NO height management
      textNode.setAttrs({
        fontSize: newFontSize,
        width: newWidth,
        scaleX: 1,
        scaleY: 1
      })
      
      // Update layer data - NO height management
      layer.width = newWidth
      // Update font size in properties if they exist
      if (layer.properties && 'fontSize' in layer.properties) {
        (layer.properties as any).fontSize = newFontSize
      }
    }
  }

  private handleImageTransform(layer: LayerNode): void {
    // Handle image-specific transformations like clipping
    if (!layer.konvaNode || layer.type !== 'image') {
      console.warn('handleImageTransform: Invalid layer or not an image layer')
      return
    }

    // For image layers, the konvaNode is a Group containing the actual Image node
    let imageNode: Konva.Image | null = null
    
    if (layer.konvaNode instanceof Konva.Group) {
      // Find the Image node within the group
      imageNode = layer.konvaNode.findOne('Image') as Konva.Image
    } else if (layer.konvaNode instanceof Konva.Image) {
      // Direct Image node (less common case)
      imageNode = layer.konvaNode
    }
    
    // Verify we found a valid Konva.Image node
    if (!imageNode || typeof imageNode.image !== 'function') {
      console.warn('handleImageTransform: Could not find valid Konva.Image node')
      return
    }

    // Update layer dimensions based on the current node size
    const node = layer.konvaNode
    layer.width = node.width() * Math.abs(node.scaleX())
    layer.height = node.height() * Math.abs(node.scaleY())
    layer.scaleX = 1
    layer.scaleY = 1

    // Update the node dimensions and reset scale
    node.setAttrs({
      width: layer.width,
      height: layer.height,
      scaleX: 1,
      scaleY: 1
    })

    // Update the image node to match the group size
    imageNode.setAttrs({
      width: layer.width,
      height: layer.height
    })

    // Apply final crop with current object position
    this.applyCrop(imageNode, layer.properties?.objectPosition || 'center-middle')
  }

  private handleSvgTransform(layer: LayerNode): void {
    // Handle SVG-specific transformations for path-based SVG rendering
    if (!layer.konvaNode || layer.type !== 'svg') {
      console.warn('handleSvgTransform: Invalid layer or not an SVG layer')
      return
    }

    const node = layer.konvaNode
    
    // Update layer dimensions based on the current node size and scale
    if (node.scaleX() !== 1 || node.scaleY() !== 1) {
      const oldWidth = layer.width
      const oldHeight = layer.height
      const scaleX = Math.abs(node.scaleX())
      const scaleY = Math.abs(node.scaleY())
      
      // Preserve the current position to prevent jumping
      const currentX = node.x()
      const currentY = node.y()
      
      // Calculate new dimensions
      layer.width = oldWidth * scaleX
      layer.height = oldHeight * scaleY
      layer.scaleX = 1
      layer.scaleY = 1

      // Update the group dimensions and reset scale while preserving position
      node.setAttrs({
        width: layer.width,
        height: layer.height,
        scaleX: 1,
        scaleY: 1,
        x: currentX,
        y: currentY
      })

      // For SVG layers with path-based rendering, we need to update the individual path scales
      if (layer.konvaNode instanceof Konva.Group) {
        // Find all SVG path elements within the group
        const svgPaths = layer.konvaNode.find('.svg-path') as Konva.Path[]
        
        if (svgPaths.length > 0) {
          console.log(`🔄 SVGTransform: Updating ${svgPaths.length} path elements with new scale`)
          
          // Update each path's scale to maintain the overall size
          svgPaths.forEach(path => {
            const currentScaleX = path.scaleX() || 1
            const currentScaleY = path.scaleY() || 1
            
            // Apply the new scale to maintain the group's transformed size
            path.setAttrs({
              scaleX: currentScaleX * scaleX,
              scaleY: currentScaleY * scaleY
            })
          })
          
          console.log(`✅ SVGTransform: Updated SVG paths for layer ${layer.id}`, {
            oldDimensions: { width: oldWidth, height: oldHeight },
            newDimensions: { width: layer.width, height: layer.height },
            scaleFactors: { scaleX, scaleY }
          })
        } else {
          console.log('⚠️ SVGTransform: No SVG paths found in group - may be loading or error state')
        }
      }
    }
  }

  private handleDefaultTransform(layer: LayerNode): void {
    // Standard transform handling for other layer types
    // This is already handled in the main handleTransformEnd method
  }

  private applyImageClipping(layer: LayerNode, imageNode: Konva.Image): void {
    // Use the applyCrop method that follows Konva documentation pattern
    this.applyCrop(imageNode, layer.properties?.objectPosition || 'center-middle')
  }

  // Function to apply crop following Konva documentation pattern
  private applyCrop(imageNode: Konva.Image, objectPosition: string): void {
    if (!imageNode || typeof imageNode.image !== 'function') {
      console.warn('applyCrop: Invalid image node provided')
      return
    }

    const img = imageNode.image() as HTMLImageElement
    if (!img || !img.width || !img.height) {
      console.warn('applyCrop: Image not loaded or invalid dimensions')
      return
    }

    // Store the last crop position used
    imageNode.setAttr('lastCropUsed', objectPosition)
    
    // Convert our objectPosition format to Konva crop position format
    const cropPosition = this.mapObjectPositionToCropPosition(objectPosition)
    
    const crop = this.getCrop(
      img,
      { width: imageNode.width(), height: imageNode.height() },
      cropPosition
    )
    
    imageNode.setAttrs(crop)
  }

  // Function to calculate crop values from source image, its visible size and a crop strategy
  // This follows the exact pattern from Konva documentation
  private getCrop(
    image: HTMLImageElement, 
    size: { width: number; height: number }, 
    clipPosition: string = 'center-middle'
  ): { cropX: number; cropY: number; cropWidth: number; cropHeight: number } {
    const width = size.width
    const height = size.height
    const aspectRatio = width / height

    let newWidth: number
    let newHeight: number

    const imageRatio = image.width / image.height

    if (aspectRatio >= imageRatio) {
      newWidth = image.width
      newHeight = image.width / aspectRatio
    } else {
      newWidth = image.height * aspectRatio
      newHeight = image.height
    }

    let x = 0
    let y = 0
    
    if (clipPosition === 'left-top') {
      x = 0
      y = 0
    } else if (clipPosition === 'left-middle') {
      x = 0
      y = (image.height - newHeight) / 2
    } else if (clipPosition === 'left-bottom') {
      x = 0
      y = image.height - newHeight
    } else if (clipPosition === 'center-top') {
      x = (image.width - newWidth) / 2
      y = 0
    } else if (clipPosition === 'center-middle') {
      x = (image.width - newWidth) / 2
      y = (image.height - newHeight) / 2
    } else if (clipPosition === 'center-bottom') {
      x = (image.width - newWidth) / 2
      y = image.height - newHeight
    } else if (clipPosition === 'right-top') {
      x = image.width - newWidth
      y = 0
    } else if (clipPosition === 'right-middle') {
      x = image.width - newWidth
      y = (image.height - newHeight) / 2
    } else if (clipPosition === 'right-bottom') {
      x = image.width - newWidth
      y = image.height - newHeight
    }

    return {
      cropX: x,
      cropY: y,
      cropWidth: newWidth,
      cropHeight: newHeight,
    }
  }

  // Map our objectPosition format to Konva crop position format
  private mapObjectPositionToCropPosition(objectPosition: string): string {
    switch (objectPosition) {
      case 'top left':
        return 'left-top'
      case 'top center':
      case 'top':
        return 'center-top'
      case 'top right':
        return 'right-top'
      case 'center left':
      case 'left':
        return 'left-middle'
      case 'center center':
      case 'center':
        return 'center-middle'
      case 'center right':
      case 'right':
        return 'right-middle'
      case 'bottom left':
        return 'left-bottom'
      case 'bottom center':
      case 'bottom':
        return 'center-bottom'
      case 'bottom right':
        return 'right-bottom'
      default:
        return 'center-middle'
    }
  }

  // ============================================================================
  // SETUP AND CLEANUP
  // ============================================================================

  private setupTransformer(): void {
    this.transformer = new Konva.Transformer({
      boundBoxFunc: (oldBox: Box, newBox: Box) => {
        if (newBox.width < 5 || newBox.height < 5) {
          return oldBox
        }
        return newBox
      },
      // Enable responsive scaling for handles
      enabledAnchors: [
        'top-left', 'top-center', 'top-right',
        'middle-left', 'middle-right',
        'bottom-left', 'bottom-center', 'bottom-right'
      ],
      rotateEnabled: true,
      borderEnabled: true,
      // These will be dynamically adjusted based on stage scale
      anchorSize: 8,
      borderStroke: '#007bff',
      borderStrokeWidth: 1,
      anchorStroke: '#007bff',
      anchorFill: '#ffffff',
      anchorCornerRadius: 4,
      rotateAnchorOffset: 20
    })
    // Transformer will be added to the appropriate layer in updateTransformer()
    
    this.currentTransformerLayer = null
  }

  private setupEventListeners(): void {
    // Listen for text reflow events to refresh transformation handles
    this.emitter.on('text:reflow', this.handleTextReflow.bind(this))

    // Listen for layer updates that might require transformer refresh
    this.emitter.on('layer:updated', this.handleLayerUpdated.bind(this))
    
  }

  private handleTextReflow(data: { layerId: number; newHeight: number; reason: string }): void {
    // Find the layer that was reflowed
    const layer = this.selectedLayers.find(l => l.id === data.layerId)
    if (!layer) return

    // Update the layer height with the new calculated height
    layer.height = data.newHeight

    // If this layer is selected, refresh the transformer
    if (this.selectedLayers.some(l => l.id === data.layerId)) {
      // Force transformer to recalculate its bounds
      this.updateTransformer()
      
      // Force current transformer layer redraw to show updated handles
      this.currentTransformerLayer?.batchDraw()
    }
  }

  private handleLayerUpdated(layer: any): void {
    // Find if this is one of our selected layers
    const selectedLayer = this.selectedLayers.find(l => l.id === layer.id)
    if (!selectedLayer) return

    // Update the layer data
    Object.assign(selectedLayer, layer)

    // If it's a text layer that might have height changes, refresh transformer
    if (selectedLayer.type === 'text') {
      this.updateTransformer()
      this.currentTransformerLayer?.batchDraw()
    }
  }

  private emitSelectionChange(): void {
    // Only emit selection events if not loading a design
    if (!this.state.isLoadingDesign) {
      const selectedLayerData = this.selectedLayers.map(layer => this.layerNodeToLayer(layer))
      this.emitter.emit('selection:changed', this.selectedLayers.map(layer => layer.id))
      this.emitter.emit('layer:selected', selectedLayerData)
    }
  }

  private layerNodeToLayer(layer: LayerNode): Layer {
    return {
      id: layer.id,
      type: layer.type as LayerType,
      name: layer.name,
      visible: layer.visible,
      locked: layer.locked,
      transform: {
        x: layer.x,
        y: layer.y,
        width: layer.width,
        height: layer.height,
        rotation: layer.rotation,
        scaleX: layer.scaleX || 1,
        scaleY: layer.scaleY || 1,
        opacity: layer.opacity
      },
      zIndex: layer.zIndex,
      properties: layer.properties || {}
    }
  }

  private addNodeDragHandlers(): void {
    // Add drag handlers for each selected node to centralize position management
    this.selectedLayers.forEach(layer => {
      if (!layer.konvaNode) return

      // Remove existing drag handlers for this node
      layer.konvaNode.off('dragstart.transform-manager')
      layer.konvaNode.off('dragend.transform-manager')

      // Add drag start handler
      layer.konvaNode.on('dragstart.transform-manager', () => {
        const container = layer.konvaNode?.getStage()?.container()
        if (container) {
          container.style.cursor = 'grabbing'
        }
        
        // Capture initial position for history
        if (this.historyManager) {
          const initialState = {
            id: layer.id,
            x: layer.x,
            y: layer.y
          }
          this.transformStartStates.set(layer.id, initialState)
        }
      })

      // Add drag end handler
      layer.konvaNode.on('dragend.transform-manager', () => {
        const container = layer.konvaNode?.getStage()?.container()
        if (container) {
          container.style.cursor = 'default'
        }
        
        // Update layer position from node
        if (layer.konvaNode) {
          const oldX = layer.x
          const oldY = layer.y
          
          layer.x = layer.konvaNode.x()
          layer.y = layer.konvaNode.y()
          
          // Add to history if history manager is available and position changed
          if (this.historyManager) {
            const initialState = this.transformStartStates.get(layer.id)
            if (initialState && (initialState.x !== layer.x || initialState.y !== layer.y)) {
              const finalState = {
                id: layer.id,
                x: layer.x,
                y: layer.y
              }
              this.historyManager.addTransformLayerCommand(layer.id, initialState, finalState)
            }
            this.transformStartStates.delete(layer.id)
          }
          
          // Emit layer update event to sync with store only if not loading a design
          if (!this.state.isLoadingDesign) {
            this.emitter.emit('layer:updated', this.layerNodeToLayer(layer))
          }
        }
      })
    })
  }

  private addNodeTransformHandlers(): void {
    // Add individual transform handlers for each selected node
    // This follows the Konva documentation pattern for text wrapping
    this.selectedLayers.forEach(layer => {
      if (!layer.konvaNode) return

      // Remove existing transform handlers for this node
      layer.konvaNode.off('transform.node-transform')

      if (layer.type === 'text') {
        // Add text-specific transform handler for real-time text wrapping
        layer.konvaNode.on('transform.node-transform', () => {
          const textNode = layer.konvaNode as Konva.Text
          const activeAnchor = this.transformer?.getActiveAnchor()

          if (!textNode || !activeAnchor) return

          // Handle width resizing for text wrapping (middle handles)
          if (['middle-left', 'middle-right'].includes(activeAnchor)) {
            // Follow Konva documentation pattern exactly
            textNode.setAttrs({
              width: textNode.width() * textNode.scaleX(),
              scaleX: 1,
            })
          }
        })
      }
    })
  }

 

  

  destroy(): void {
    // Clean up event listeners
    this.emitter.off('text:reflow', this.handleTextReflow)
    this.emitter.off('layer:updated', this.handleLayerUpdated)

    // Clean up node transform and drag handlers
    this.selectedLayers.forEach(layer => {
      if (layer.konvaNode) {
        layer.konvaNode.off('transform.node-transform')
        layer.konvaNode.off('dragstart.transform-manager')
        layer.konvaNode.off('dragend.transform-manager')
      }
    })

    if (this.transformer) {
      this.transformer.destroy()
      this.transformer = null
    }
    
    this.selectedLayers = []
  }

  /**
   * Update transformer anchor sizes when zoom level changes
   */
  updateTransformerForZoom(): void {
    if (this.transformer && this.selectedLayers.length > 0) {
      // Reconfigure transformer with new anchor size
      this.configureTransformerForLayers()
    }
  }
}

import Konva from 'konva'
import type { LayerNode } from './types'
import type { EventEmitter } from './EventEmitter'

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
  private uiLayer: Konva.Layer | null = null
  private selectedLayers: LayerNode[] = []

  constructor(
    private stage: Konva.Stage,
    private emitter: EventEmitter
  ) {
    this.setupUILayer()
    this.setupTransformer()
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

    this.selectedLayers.forEach(layer => {
      const position = this.calculatePresetPosition(
        preset, 
        layer.width, 
        layer.height, 
        canvasWidth, 
        canvasHeight
      )
      
      // Update layer position
      layer.x = position.x
      layer.y = position.y
      
      // Update Konva node
      if (layer.konvaNode) {
        layer.konvaNode.setAttrs({
          x: position.x,
          y: position.y
        })
      }
      
      // Emit update event
      this.emitter.emit('layer:updated', this.layerNodeToLayer(layer))
    })

    // Redraw layers
    this.stage.batchDraw()
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

  // ============================================================================
  // TRANSFORMATION HANDLING
  // ============================================================================

  private updateTransformer(): void {
    if (!this.transformer) return

    const selectedNodes = this.selectedLayers
      .map(layer => layer.konvaNode)
      .filter((node): node is Konva.Node => node !== null && node !== undefined)

    this.transformer.nodes(selectedNodes)
    this.transformer.visible(selectedNodes.length > 0)

    if (selectedNodes.length > 0) {
      this.configureTransformerForLayers()
      this.setupTransformEventHandlers()
    }

    this.uiLayer?.batchDraw()
  }

  private configureTransformerForLayers(): void {
    if (!this.transformer || this.selectedLayers.length === 0) return

    const layerTypes = this.selectedLayers.map(layer => layer.type)
    const allText = layerTypes.every(type => type === 'text')
    const allImages = layerTypes.every(type => type === 'image')
    const mixed = layerTypes.length > 1 && new Set(layerTypes).size > 1

    if (allText) {
      this.configureTextTransformer()
    } else if (allImages) {
      this.configureImageTransformer()
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
      anchorSize: 8,
      boundBoxFunc: (oldBox: Box, newBox: Box) => {
        const activeAnchor = this.transformer?.getActiveAnchor()
        const minWidth = 50
        const minHeight = 20

        // Corner handles: uniform scaling
        if (activeAnchor && ['top-left', 'top-right', 'bottom-left', 'bottom-right'].includes(activeAnchor)) {
          const scale = Math.min(newBox.width / oldBox.width, newBox.height / oldBox.height)
          newBox.width = Math.max(minWidth, oldBox.width * scale)
          newBox.height = Math.max(minHeight, oldBox.height * scale)
        }
        // Side handles: width resizing only
        else if (activeAnchor && ['middle-left', 'middle-right'].includes(activeAnchor)) {
          newBox.width = Math.max(minWidth, newBox.width)
          newBox.height = Math.max(minHeight, oldBox.height) // Maintain height for now
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
      anchorSize: 8,
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
      anchorSize: 8,
      boundBoxFunc: (oldBox: Box, newBox: Box) => {
        return newBox.width < 5 || newBox.height < 5 ? oldBox : newBox
      }
    })
  }

  private setupTransformEventHandlers(): void {
    if (!this.transformer) return

    // Remove existing handlers
    this.transformer.off('transform.transform-manager')
    this.transformer.off('transformend.transform-manager')

    // Add transform handler for real-time updates (especially for images)
    this.transformer.on('transform.transform-manager', () => {
      this.handleTransform()
    })

    // Add transform end handler for final cleanup
    this.transformer.on('transformend.transform-manager', () => {
      this.handleTransformEnd()
    })
  }

  private handleTransform(): void {
    // Handle real-time transformations, especially for image cropping
    this.selectedLayers.forEach(layer => {
      if (!layer.konvaNode) return

      if (layer.type === 'image') {
        this.handleImageTransformRealtime(layer)
      }
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
      } else {
        this.handleDefaultTransform(layer)
      }

      // For non-image layers, reset scale to 1 and apply to width/height
      // For image layers, this is handled in handleImageTransform
      if (layer.type !== 'image' && (node.scaleX() !== 1 || node.scaleY() !== 1)) {
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

      // Emit update event
      this.emitter.emit('layer:updated', this.layerNodeToLayer(layer))
    })

    this.stage.batchDraw()
  }

  private handleTextTransform(layer: LayerNode): void {
    // Handle text-specific transformations like text wrapping
    const textNode = layer.konvaNode as Konva.Text
    const activeAnchor = this.transformer?.getActiveAnchor()

    if (activeAnchor && ['middle-left', 'middle-right'].includes(activeAnchor)) {
      // Width resizing: apply new width and let text wrap
      const newWidth = textNode.width() * Math.abs(textNode.scaleX())
      textNode.setAttrs({
        width: newWidth,
        scaleX: 1,
        scaleY: 1
      })
      
      // Update layer dimensions
      layer.width = newWidth
      layer.height = textNode.height() // Use actual text height after wrapping
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

  private setupUILayer(): void {
    // Get or create UI layer for transformer
    const layers = this.stage.getLayers()
    this.uiLayer = layers.find(layer => layer.name() === 'ui-layer') || null
    
    if (!this.uiLayer) {
      this.uiLayer = new Konva.Layer({ name: 'ui-layer' })
      this.stage.add(this.uiLayer)
    }
  }

  private setupTransformer(): void {
    this.transformer = new Konva.Transformer({
      boundBoxFunc: (oldBox: Box, newBox: Box) => {
        if (newBox.width < 5 || newBox.height < 5) {
          return oldBox
        }
        return newBox
      }
    })

    this.uiLayer?.add(this.transformer)
  }

  private emitSelectionChange(): void {
    const selectedLayerData = this.selectedLayers.map(layer => this.layerNodeToLayer(layer))
    this.emitter.emit('selection:changed', this.selectedLayers.map(layer => layer.id))
    this.emitter.emit('layer:selected', selectedLayerData)
  }

  private layerNodeToLayer(layer: LayerNode): any {
    return {
      id: layer.id,
      type: layer.type,
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
  }

  destroy(): void {
    if (this.transformer) {
      this.transformer.destroy()
      this.transformer = null
    }
    
    this.selectedLayers = []
  }
}

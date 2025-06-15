import Konva from 'konva'
import type { EventEmitter } from './EventEmitter'
import type { 
  CanvasAPI, 
  EditorState, 
  LayerNode 
} from './types'
import type { LayerManager } from './LayerManager'

/**
 * CanvasManager - Handles canvas viewport control, rendering, and export
 */
export class CanvasManager implements CanvasAPI {
  private gridLayer?: Konva.Layer
  private guidesLayer?: Konva.Layer
  private backgroundLayer?: Konva.Layer
  private backgroundRect?: Konva.Rect
  private gridVisible = false
  private guidesVisible = false
  private gridSize = 20
  private backgroundColor: string = '#ffffff'
  private layerManager: LayerManager | null = null
  
  // Store original design canvas dimensions (independent of stage scaling)
  private originalCanvasWidth: number
  private originalCanvasHeight: number

  constructor(
    private stage: Konva.Stage,
    private state: EditorState,
    private eventEmitter: EventEmitter
  ) {
    // Store original canvas dimensions - will be updated when design is loaded
    this.originalCanvasWidth = this.stage.width()
    this.originalCanvasHeight = this.stage.height()
    
    this.setupCanvas()
    this.setupEventHandlers()
  }

  // ============================================================================
  // INTEGRATION METHODS
  // ============================================================================

  /**
   * Set the LayerManager instance for integration
   */
  setLayerManager(layerManager: LayerManager): void {
    this.layerManager = layerManager
    
    // Ensure background layer stays at the bottom when LayerManager adds its layers
    if (this.backgroundLayer) {
      this.backgroundLayer.moveToBottom()
      console.log('🔍 CanvasManager: Ensured background layer is at bottom after LayerManager integration')
    }
  }

  // ============================================================================
  // CANVAS MANAGEMENT
  // ============================================================================

  setSize(width: number, height: number): void {
    this.stage.width(width)
    this.stage.height(height)
    
    // Update original canvas dimensions when size is explicitly changed
    this.originalCanvasWidth = width
    this.originalCanvasHeight = height
    
    // Update background rectangle dimensions if it exists
    if (this.backgroundRect) {
      this.backgroundRect.width(width)
      this.backgroundRect.height(height)
      this.backgroundLayer?.batchDraw()
    }
    
    // Update grid when canvas size changes
    this.updateGrid()
    
    // Update background to maintain proper styling
    this.updateBackground()
    
    // Only emit canvas:resized if not loading a design
    if (!this.state.isLoadingDesign) {
      this.eventEmitter.emit('canvas:resized', { width, height })
    }
  }

  getSize(): { width: number, height: number } {
    return {
      width: this.originalCanvasWidth,
      height: this.originalCanvasHeight
    }
  }

  setBackgroundColor(color: string): void {
    this.backgroundColor = color
    this.createBackground()
    this.updateBackground()
  }

  getBackgroundColor(): string {
    return this.backgroundColor
  }

  // ============================================================================
  // VIEWPORT CONTROL
  // ============================================================================

  zoomToFit(): void {
    this.fitCanvasToViewport()
  }

  /**
   * Fit the entire canvas to the available viewport space with provided dimensions
   */
  fitCanvasToViewport(viewportWidth?: number, viewportHeight?: number): void {
    const container = this.stage.container()
    if (!container) return

    let availableWidth: number
    let availableHeight: number

    if (viewportWidth && viewportHeight) {
      // Use provided viewport dimensions (from EditorLayout)
      availableWidth = viewportWidth
      availableHeight = viewportHeight
    } else {
      // Fallback: try to get dimensions from container
      const viewportContainer = container.parentElement
      if (!viewportContainer) return

      const viewportRect = viewportContainer.getBoundingClientRect()
      availableWidth = viewportRect.width
      availableHeight = viewportRect.height
    }
    
    // Use the original design canvas size (not current stage size)
    const canvasWidth = this.originalCanvasWidth
    const canvasHeight = this.originalCanvasHeight
    
    // Add minimal padding around the canvas for breathing room
    const padding = 50 // Reduced padding to maximize canvas usage
    const targetWidth = Math.max(availableWidth - padding * 2, 200) // Minimum 200px
    const targetHeight = Math.max(availableHeight - padding * 2, 150) // Minimum 150px
    
    // Calculate scale to fit the entire canvas in viewport
    const scaleX = targetWidth / canvasWidth
    const scaleY = targetHeight / canvasHeight
    let scale = Math.min(scaleX, scaleY)
    
    // Allow scaling up to a reasonable maximum (3x)
    scale = Math.min(scale, 3)
    
    // Ensure minimum scale for very large canvases
    scale = Math.max(scale, 0.1)
    
    console.log('🔍 CanvasManager fitCanvasToViewport:', {
      availableViewport: { width: availableWidth, height: availableHeight },
      canvasSize: { width: canvasWidth, height: canvasHeight },
      targetSize: { width: targetWidth, height: targetHeight },
      scaleCalculations: { scaleX, scaleY, finalScale: scale },
      padding,
      willScale: scale < 1 ? 'down' : scale > 1 ? 'up' : 'same'
    })
    
    // Center the canvas in the viewport
    const scaledCanvasWidth = canvasWidth * scale
    const scaledCanvasHeight = canvasHeight * scale
    
    // Calculate center position with proper padding consideration
    const x = (availableWidth - scaledCanvasWidth) / 2
    const y = (availableHeight - scaledCanvasHeight) / 2
    
    console.log('🔍 CanvasManager positioning:', {
      scaledCanvasSize: { width: scaledCanvasWidth, height: scaledCanvasHeight },
      calculatedPosition: { x, y },
      finalScale: scale
    })
    
    this.stage.scale({ x: scale, y: scale })
    this.stage.position({ x, y })
    
    // Update state
    this.state.zoom = scale
    this.state.panX = x
    this.state.panY = y
    
    // Update background for viewport changes
    this.updateBackgroundForViewport()
    
    // Emit viewport change event only if not loading a design
    if (!this.state.isLoadingDesign) {
      this.eventEmitter.emit('viewport:changed', { 
        zoom: scale, 
        panX: x, 
        panY: y 
      })
    }
  }

  /**
   * Zoom to fit content (existing behavior)
   */
  zoomToFitContent(): void {
    const padding = 50
    const stageWidth = this.originalCanvasWidth
    const stageHeight = this.originalCanvasHeight
    
    // Get bounds of all visible layers
    const bounds = this.getContentBounds()
    if (!bounds) return

    const contentWidth = bounds.width
    const contentHeight = bounds.height
    
    // Calculate zoom to fit content with padding
    const scaleX = (stageWidth - padding * 2) / contentWidth
    const scaleY = (stageHeight - padding * 2) / contentHeight
    const scale = Math.min(scaleX, scaleY, 2) // Max zoom of 2x
    
    // Calculate center position
    const centerX = stageWidth / 2
    const centerY = stageHeight / 2
    const contentCenterX = bounds.x + contentWidth / 2
    const contentCenterY = bounds.y + contentHeight / 2
    
    const x = centerX - contentCenterX * scale
    const y = centerY - contentCenterY * scale
    
    this.stage.scale({ x: scale, y: scale })
    this.stage.position({ x, y })
    
    // Update state
    this.state.zoom = scale
    this.state.panX = x
    this.state.panY = y
    
    // Update background for viewport changes
    this.updateBackgroundForViewport()
    
    // Emit viewport change event only if not loading a design
    if (!this.state.isLoadingDesign) {
      this.eventEmitter.emit('viewport:changed', { 
        zoom: scale, 
        panX: x, 
        panY: y 
      })
    }
  }

  zoomToLayer(layerId: string): void {
    const layer = this.findLayerNode(layerId)
    if (!layer?.konvaNode) return

    const bounds = layer.konvaNode.getClientRect()
    const padding = 100
    
    const stageWidth = this.stage.width()
    const stageHeight = this.stage.height()
    
    const scaleX = (stageWidth - padding * 2) / bounds.width
    const scaleY = (stageHeight - padding * 2) / bounds.height
    const scale = Math.min(scaleX, scaleY, 3) // Max zoom of 3x
    
    const centerX = stageWidth / 2
    const centerY = stageHeight / 2
    const boundsCenterX = bounds.x + bounds.width / 2
    const boundsCenterY = bounds.y + bounds.height / 2
    
    const x = centerX - boundsCenterX * scale
    const y = centerY - boundsCenterY * scale
    
    this.stage.scale({ x: scale, y: scale })
    this.stage.position({ x, y })
    
    this.state.zoom = scale
    this.state.panX = x
    this.state.panY = y
    
    // Update background for viewport changes
    this.updateBackgroundForViewport()
    
    // Emit viewport change event only if not loading a design
    if (!this.state.isLoadingDesign) {
      this.eventEmitter.emit('viewport:changed', { 
        zoom: scale, 
        panX: x, 
        panY: y 
      })
    }
  }

  setZoom(zoom: number, options?: { zoomToCenter?: boolean }): void {
    const clampedZoom = Math.max(0.1, Math.min(10, zoom))
    const oldScale = this.stage.scaleX()
    
    // If zoomToCenter is true or no pointer is available, zoom to center
    const pointer = this.stage.getPointerPosition()
    const shouldZoomToCenter = options?.zoomToCenter || !pointer
    
    if (shouldZoomToCenter) {
      // Zoom to center of the stage
      const stageWidth = this.stage.width()
      const stageHeight = this.stage.height()
      const stageCenter = {
        x: stageWidth / 2,
        y: stageHeight / 2
      }
      
      const mousePointTo = {
        x: (stageCenter.x - this.stage.x()) / oldScale,
        y: (stageCenter.y - this.stage.y()) / oldScale
      }
      
      const newPos = {
        x: stageCenter.x - mousePointTo.x * clampedZoom,
        y: stageCenter.y - mousePointTo.y * clampedZoom
      }
      
      this.stage.position(newPos)
      this.state.panX = newPos.x
      this.state.panY = newPos.y
    } else {
      // Zoom towards mouse pointer
      const mousePointTo = {
        x: (pointer.x - this.stage.x()) / oldScale,
        y: (pointer.y - this.stage.y()) / oldScale
      }
      
      const newPos = {
        x: pointer.x - mousePointTo.x * clampedZoom,
        y: pointer.y - mousePointTo.y * clampedZoom
      }
      
      this.stage.position(newPos)
      this.state.panX = newPos.x
      this.state.panY = newPos.y
    }
    
    this.stage.scale({ x: clampedZoom, y: clampedZoom })
    this.state.zoom = clampedZoom
    
    // Update background for viewport changes
    this.updateBackgroundForViewport()
    
    // Emit viewport change event only if not loading a design
    if (!this.state.isLoadingDesign) {
      this.eventEmitter.emit('viewport:changed', { 
        zoom: clampedZoom, 
        panX: this.state.panX, 
        panY: this.state.panY 
      })
    }
  }

  getZoom(): number {
    return this.stage.scaleX()
  }

  pan(deltaX: number, deltaY: number): void {
    const newPos = {
      x: this.stage.x() + deltaX,
      y: this.stage.y() + deltaY
    }
    
    this.stage.position(newPos)
    this.state.panX = newPos.x
    this.state.panY = newPos.y
    
    // Update background for viewport changes
    this.updateBackgroundForViewport()
    
    // Emit viewport change event only if not loading a design
    if (!this.state.isLoadingDesign) {
      this.eventEmitter.emit('viewport:changed', { 
        zoom: this.state.zoom, 
        panX: newPos.x, 
        panY: newPos.y 
      })
    }
  }

  setPanMode(enabled: boolean): void {
    if (enabled) {
      this.stage.draggable(true)
      this.stage.container().style.cursor = 'grab'
    } else {
      this.stage.draggable(false)
      this.stage.container().style.cursor = 'default'
    }
  }

  centerView(): void {
    const stageWidth = this.stage.width()
    const stageHeight = this.stage.height()
    
    this.stage.position({
      x: stageWidth / 2,
      y: stageHeight / 2
    })
    this.stage.scale({ x: 1, y: 1 })
    
    this.state.zoom = 1
    this.state.panX = stageWidth / 2
    this.state.panY = stageHeight / 2
    
    // Update background for viewport changes
    this.updateBackgroundForViewport()
    
    this.eventEmitter.emit('viewport:changed', { 
      zoom: 1, 
      panX: this.state.panX, 
      panY: this.state.panY 
    })
  }

  // ============================================================================
  // RENDERING AND EXPORT
  // ============================================================================

  toDataURL(options?: { format?: string, quality?: number, pixelRatio?: number }): string {
    const opts = {
      format: 'image/png',
      quality: 1,
      pixelRatio: 1,
      ...options
    }
    
    return this.stage.toDataURL({
      mimeType: opts.format,
      quality: opts.quality,
      pixelRatio: opts.pixelRatio
    })
  }

  toBlob(callback: (blob: Blob | null) => void, options?: { format?: string, quality?: number, pixelRatio?: number }): void {
    const opts = {
      format: 'image/png',
      quality: 1,
      pixelRatio: 1,
      ...options
    }
    
    this.stage.toBlob({
      callback,
      mimeType: opts.format,
      quality: opts.quality,
      pixelRatio: opts.pixelRatio
    })
  }

  // ============================================================================
  // GRID AND GUIDES
  // ============================================================================

  showGrid(show: boolean): void {
    this.gridVisible = show
    
    if (show) {
      this.createGrid()
    } else if (this.gridLayer) {
      this.gridLayer.visible(false)
    }
    
    this.stage.batchDraw()
  }

  setGridSize(size: number): void {
    this.gridSize = Math.max(5, Math.min(100, size))
    if (this.gridVisible) {
      this.updateGrid()
    }
  }

  showGuides(show: boolean): void {
    this.guidesVisible = show
    
    if (show) {
      this.createGuides()
    } else if (this.guidesLayer) {
      this.guidesLayer.visible(false)
    }
    
    this.stage.batchDraw()
  }

  // ============================================================================
  // HIT TESTING
  // ============================================================================

  getLayerAtPoint(x: number, y: number): LayerNode | null {
    const intersections = this.stage.getIntersection({ x, y })
    if (intersections) {
      return this.findLayerNodeByKonvaNode(intersections)
    }
    return null
  }

  getLayersInArea(x: number, y: number, width: number, height: number): LayerNode[] {
    const rect = new Konva.Rect({
      x, y, width, height,
      visible: false
    })
    
    const layers: LayerNode[] = []
    this.stage.find('*').forEach(node => {
      if (Konva.Util.haveIntersection(rect.getClientRect(), node.getClientRect())) {
        const layerNode = this.findLayerNodeByKonvaNode(node)
        if (layerNode) {
          layers.push(layerNode)
        }
      }
    })
    
    rect.destroy()
    return layers
  }

  // ============================================================================
  // PRIVATE METHODS
  // ============================================================================

  private setupCanvas(): void {
    console.log(`🔍 CanvasManager: setupCanvas() called - creating Konva background for export compatibility`)
    this.createBackground()
    this.updateBackground()
    console.log(`🔍 CanvasManager: setupCanvas() completed with Konva background layer`)
  }

  private createBackground(): void {
    // Create dedicated background layer if it doesn't exist
    if (!this.backgroundLayer) {
      this.backgroundLayer = new Konva.Layer({
        name: 'background-layer'
      })
      
      // Add background layer as the first layer (bottom-most)
      this.stage.add(this.backgroundLayer)
      this.backgroundLayer.moveToBottom()
      
      console.log('🔍 CanvasManager: Created background layer')
    }
    
    // Create or update background rectangle
    if (!this.backgroundRect) {
      this.backgroundRect = new Konva.Rect({
        x: 0,
        y: 0,
        width: this.originalCanvasWidth,
        height: this.originalCanvasHeight,
        fill: this.backgroundColor,
        listening: false, // Don't interfere with interactions
        name: 'canvas-background',
      })
      
      this.backgroundLayer.add(this.backgroundRect)
      console.log('🔍 CanvasManager: Created background rectangle')
    } else {
      // Update existing background
      this.backgroundRect.fill(this.backgroundColor)
      this.backgroundRect.width(this.originalCanvasWidth)
      this.backgroundRect.height(this.originalCanvasHeight)
    }
    
    // Ensure background layer is at the bottom
    this.backgroundLayer.moveToBottom()
    this.backgroundLayer.batchDraw()
  }

  private setupEventHandlers(): void {
    // Wheel zoom
    this.stage.on('wheel', (e) => {
      e.evt.preventDefault()
      
      const scaleBy = 1.1
      const oldScale = this.stage.scaleX()
      const pointer = this.stage.getPointerPosition()
      
      if (!pointer) return
      
      const mousePointTo = {
        x: (pointer.x - this.stage.x()) / oldScale,
        y: (pointer.y - this.stage.y()) / oldScale
      }
      
      const newScale = e.evt.deltaY > 0 ? oldScale * scaleBy : oldScale / scaleBy
      const clampedScale = Math.max(0.1, Math.min(10, newScale))
      
      this.stage.scale({ x: clampedScale, y: clampedScale })
      
      const newPos = {
        x: pointer.x - mousePointTo.x * clampedScale,
        y: pointer.y - mousePointTo.y * clampedScale
      }
      
      this.stage.position(newPos)
      this.state.zoom = clampedScale
      this.state.panX = newPos.x
      this.state.panY = newPos.y
      
      // Update background for viewport changes
      this.updateBackgroundForViewport()
      
      this.eventEmitter.emit('viewport:changed', { 
        zoom: clampedScale, 
        panX: newPos.x, 
        panY: newPos.y 
      })
    })

    // Pan with middle mouse or space+drag
    let isPanning = false
    
    this.stage.on('mousedown', (e) => {
      if (e.evt.button === 1 || (e.evt.button === 0 && e.evt.ctrlKey)) {
        isPanning = true
        this.stage.draggable(true)
      }
    })
    
    this.stage.on('mouseup', () => {
      if (isPanning) {
        isPanning = false
        this.stage.draggable(false)
      }
    })
    
    this.stage.on('mousemove', () => {
      if (isPanning) {
        this.state.panX = this.stage.x()
        this.state.panY = this.stage.y()
        
        // Update background for viewport changes during panning
        this.updateBackgroundForViewport()
        
        this.eventEmitter.emit('viewport:changed', { 
          zoom: this.state.zoom, 
          panX: this.state.panX, 
          panY: this.state.panY 
        })
      }
    })

    // Update cursor during stage drag for pan mode
    this.stage.on('dragstart', () => {
      this.stage.container().style.cursor = 'grabbing'
    })

    this.stage.on('dragend', () => {
      // Only reset cursor if not in pan mode
      if (!this.stage.draggable()) {
        this.stage.container().style.cursor = 'default'
      } else {
        this.stage.container().style.cursor = 'grab'
      }
      
      // Update state after pan
      this.state.panX = this.stage.x()
      this.state.panY = this.stage.y()
      
      // Update background for viewport changes after dragging
      this.updateBackgroundForViewport()
      
      this.eventEmitter.emit('viewport:changed', { 
        zoom: this.state.zoom, 
        panX: this.state.panX, 
        panY: this.state.panY 
      })
    })
  }

  private createGrid(): void {
    if (!this.gridLayer) {
      this.gridLayer = new Konva.Layer()
      this.stage.add(this.gridLayer)
    }
    
    this.updateGrid()
  }

  private updateGrid(): void {
    if (!this.gridLayer) return
    
    this.gridLayer.destroyChildren()
    
    if (!this.gridVisible) return
    
    const stageWidth = this.stage.width()
    const stageHeight = this.stage.height()
    
    // Vertical lines
    for (let x = 0; x <= stageWidth; x += this.gridSize) {
      this.gridLayer.add(new Konva.Line({
        points: [x, 0, x, stageHeight],
        stroke: '#e0e0e0',
        strokeWidth: 1,
        listening: false
      }))
    }
    
    // Horizontal lines
    for (let y = 0; y <= stageHeight; y += this.gridSize) {
      this.gridLayer.add(new Konva.Line({
        points: [0, y, stageWidth, y],
        stroke: '#e0e0e0',
        strokeWidth: 1,
        listening: false
      }))
    }
    
    this.gridLayer.batchDraw()
  }

  private createGuides(): void {
    if (!this.guidesLayer) {
      this.guidesLayer = new Konva.Layer()
      this.stage.add(this.guidesLayer)
    }
    
    this.guidesLayer.visible(this.guidesVisible)
  }

  private getContentBounds(): { x: number, y: number, width: number, height: number } | null {
    // Get the main layer (first layer) which contains all the layer nodes
    const mainLayer = this.stage.getLayers()[0]
    if (!mainLayer) return null
    
    const layerNodes = mainLayer.getChildren()
    if (layerNodes.length === 0) return null
    
    let minX = Infinity
    let minY = Infinity
    let maxX = -Infinity
    let maxY = -Infinity
    
    layerNodes.forEach(node => {
      const bounds = node.getClientRect()
      minX = Math.min(minX, bounds.x)
      minY = Math.min(minY, bounds.y)
      maxX = Math.max(maxX, bounds.x + bounds.width)
      maxY = Math.max(maxY, bounds.y + bounds.height)
    })
    
    return {
      x: minX,
      y: minY,
      width: maxX - minX,
      height: maxY - minY
    }
  }

  private findLayerNode(layerId: string): LayerNode | null {
    // This would need to be implemented by accessing the layer manager
    // For now, return null - this will be connected when integrated
    return null
  }

  private findLayerNodeByKonvaNode(konvaNode: Konva.Node): LayerNode | null {
    // This would need to be implemented by accessing the layer manager
    // For now, return null - this will be connected when integrated
    return null
  }

  // ============================================================================
  // PUBLIC METHODS FOR INTEGRATION
  // ============================================================================

  getStage(): Konva.Stage {
    return this.stage
  }

  destroy(): void {
    // Clean up background elements
    if (this.backgroundRect) {
      this.backgroundRect.destroy()
      this.backgroundRect = undefined
    }
    
    if (this.backgroundLayer) {
      this.backgroundLayer.destroy()
      this.backgroundLayer = undefined
    }
    
    // Clean up other layers
    if (this.gridLayer) {
      this.gridLayer.destroy()
      this.gridLayer = undefined
    }
    
    if (this.guidesLayer) {
      this.guidesLayer.destroy()
      this.guidesLayer = undefined
    }
  }

  zoomIn(): void {
    const newScale = this.state.zoom * 1.2
    this.setZoom(newScale, { zoomToCenter: true })
  }

  zoomOut(): void {
    const newScale = this.state.zoom / 1.2
    this.setZoom(newScale, { zoomToCenter: true })
  }

  resetZoom(): void {
    this.setZoom(1, { zoomToCenter: true })
    this.stage.position({ x: 0, y: 0 })
    
    // Update background for viewport changes
    this.updateBackgroundForViewport()
    
    this.updateState()
  }

  private setupBackground(): void {
    this.updateBackground()
  }

  private updateBackground(): void {
    // Update Konva background rectangle for export compatibility
    if (this.backgroundRect) {
      this.backgroundRect.fill(this.backgroundColor)
      this.backgroundLayer?.batchDraw()
      console.log(`🔍 CanvasManager: Background dimension is ${this.backgroundRect.width()}x${this.backgroundRect.height()}`)
    }
  }

  /**
   * Update background rectangle for viewport changes (zoom/pan)
   * According to Konva documentation, when stage is transformed, background needs to be reset
   * to fill the entire visible stage area
   */
  private updateBackgroundForViewport(): void {
    if (!this.backgroundRect || !this.backgroundLayer) return
    
    // Get current stage transform
    const scale = this.stage.scaleX()
    const stagePos = this.stage.position()
    
    // Calculate the visible area in world coordinates
    const containerWidth = this.stage.width()
    const containerHeight = this.stage.height()
    
    // Calculate where the visible area is in world coordinates
    const visibleX = -stagePos.x / scale
    const visibleY = -stagePos.y / scale
    const visibleWidth = containerWidth / scale
    const visibleHeight = containerHeight / scale
    
    // Update background rectangle to fill the entire visible area
    this.backgroundRect.setAttrs({
      x: visibleX,
      y: visibleY,
      width: visibleWidth,
      height: visibleHeight,
      fill: this.backgroundColor
    })
    
    // Force redraw of background layer
    this.backgroundLayer.batchDraw()
    
    console.log(`🔍 CanvasManager: Updated background for viewport - pos:(${visibleX.toFixed(1)}, ${visibleY.toFixed(1)}) size:(${visibleWidth.toFixed(1)}x${visibleHeight.toFixed(1)}) scale:${scale.toFixed(2)}`)
  }

  private updateState(): void {
    const pos = this.stage.position()
    const scale = this.stage.scaleX()
    
    this.state.zoom = scale
    this.state.panX = pos.x
    this.state.panY = pos.y
    
    this.eventEmitter.emit('viewport:changed', {
      zoom: scale,
      panX: pos.x,
      panY: pos.y
    })
  }
}

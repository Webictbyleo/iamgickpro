import Konva from 'konva'
import type { EventEmitter } from './EventEmitter'
import type { 
  CanvasAPI, 
  EditorState, 
  LayerNode 
} from './types'

/**
 * CanvasManager - Handles canvas viewport control, rendering, and export
 */
export class CanvasManager implements CanvasAPI {
  private gridLayer?: Konva.Layer
  private guidesLayer?: Konva.Layer
  private backgroundRect?: Konva.Rect
  private gridVisible = false
  private guidesVisible = false
  private gridSize = 20
  private backgroundColor: string = '#ffffff'

  constructor(
    private stage: Konva.Stage,
    private state: EditorState,
    private eventEmitter: EventEmitter
  ) {
    this.setupCanvas()
    this.setupEventHandlers()
  }

  // ============================================================================
  // CANVAS MANAGEMENT
  // ============================================================================

  setSize(width: number, height: number): void {
    this.stage.width(width)
    this.stage.height(height)
    
    // Update background if it exists
    if (this.backgroundRect) {
      this.backgroundRect.width(width)
      this.backgroundRect.height(height)
    }
    
    this.updateGrid()
    this.eventEmitter.emit('canvas:resized', { width, height })
  }

  getSize(): { width: number, height: number } {
    return {
      width: this.stage.width(),
      height: this.stage.height()
    }
  }

  setBackgroundColor(color: string): void {
    this.backgroundColor = color

    if (!this.backgroundRect) {
      this.createBackground()
    }
    
    if (this.backgroundRect) {
      this.backgroundRect.fill(color)
      this.stage.batchDraw()
    }
  }

  getBackgroundColor(): string {
    return this.backgroundColor
  }

  // ============================================================================
  // VIEWPORT CONTROL
  // ============================================================================

  zoomToFit(): void {
    const padding = 50
    const stageWidth = this.stage.width()
    const stageHeight = this.stage.height()
    
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
    
    this.state.zoom = scale
    this.state.panX = x
    this.state.panY = y
    
    this.eventEmitter.emit('viewport:changed', { 
      zoom: scale, 
      panX: x, 
      panY: y 
    })
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
    
    this.eventEmitter.emit('viewport:changed', { 
      zoom: scale, 
      panX: x, 
      panY: y 
    })
  }

  setZoom(zoom: number): void {
    const clampedZoom = Math.max(0.1, Math.min(10, zoom))
    const oldScale = this.stage.scaleX()
    const pointer = this.stage.getPointerPosition()
    
    if (pointer) {
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
    
    this.eventEmitter.emit('viewport:changed', { 
      zoom: clampedZoom, 
      panX: this.state.panX, 
      panY: this.state.panY 
    })
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
    
    this.eventEmitter.emit('viewport:changed', { 
      zoom: this.state.zoom, 
      panX: newPos.x, 
      panY: newPos.y 
    })
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
    console.log(`🔍 CanvasManager: setupCanvas() called - DISABLED automatic layer creation`)
    
    // DON'T create background, grid, and guides layers automatically
    // These should only be created when explicitly requested
    console.log(`🔍 CanvasManager: setupCanvas() completed without creating extra layers`)
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
        this.eventEmitter.emit('viewport:changed', { 
          zoom: this.state.zoom, 
          panX: this.state.panX, 
          panY: this.state.panY 
        })
      }
    })
  }

  private createBackground(): void {
    if (!this.backgroundRect) {
      this.backgroundRect = new Konva.Rect({
        x: 0,
        y: 0,
        width: this.stage.width(),
        height: this.stage.height(),
        fill: '#ffffff',
        listening: false
      })
      
      // Use existing layer instead of creating a new one
      const existingLayers = this.stage.getChildren()
      if (existingLayers.length > 0) {
        // Add background to the first (main) layer
        const mainLayer = existingLayers[0] as Konva.Layer
        mainLayer.add(this.backgroundRect)
        // Move background to bottom of the layer
        this.backgroundRect.moveToBottom()
        mainLayer.batchDraw()
      }
    }
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
    const nodes = this.stage.find('.layer-node')
    if (nodes.length === 0) return null
    
    let minX = Infinity
    let minY = Infinity
    let maxX = -Infinity
    let maxY = -Infinity
    
    nodes.forEach(node => {
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
    // Clean up any background elements
  }

  zoomIn(): void {
    const newScale = this.state.zoom * 1.2
    this.setZoom(newScale)
  }

  zoomOut(): void {
    const newScale = this.state.zoom / 1.2
    this.setZoom(newScale)
  }

  resetZoom(): void {
    this.setZoom(1)
    this.stage.position({ x: 0, y: 0 })
    this.updateState()
  }

  private setupBackground(): void {
    this.updateBackground()
  }

  private updateBackground(): void {
    const container = this.stage.container()
    container.style.backgroundColor = this.backgroundColor
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

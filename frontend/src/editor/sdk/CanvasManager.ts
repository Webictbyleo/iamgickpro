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
  private backgroundRect?: Konva.Rect
  private gridVisible = false
  private guidesVisible = false
  private gridSize = 20
  private backgroundColor: string = '#ffffff'
  private backgroundConfig: any = null // Store full background configuration
  private layerManager: LayerManager | null = null
  
  // Store original design canvas dimensions (independent of stage scaling)
  private originalCanvasWidth: number
  private originalCanvasHeight: number

  constructor(
    private stage: Konva.Stage,
    private state: EditorState,
    private eventEmitter: EventEmitter,
    canvasWidth?: number,
    canvasHeight?: number
  ) {
    // Store original canvas dimensions (design content size, not stage viewport size)
    this.originalCanvasWidth = canvasWidth || this.stage.width()
    this.originalCanvasHeight = canvasHeight || this.stage.height()
    
    // Ensure we have a visible default background color
    this.backgroundColor = '#ffffff'
    this.backgroundConfig = { type: 'solid', color: this.backgroundColor }
    
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
    
    // If we have a background rect, add it to the main layer as the bottom-most element
    if (this.backgroundRect) {
      const mainLayer = this.layerManager.getMainLayer()
      mainLayer.add(this.backgroundRect)
      this.backgroundRect.moveToBottom() // Ensure background is at the bottom
      this.applyBackgroundFill() // Ensure background has proper fill
      mainLayer.batchDraw()
      console.log('🔍 CanvasManager: Added existing background rect to main layer after LayerManager integration')
    } else {
      // Create background now that LayerManager is available
      this.createBackground()
      console.log('🔍 CanvasManager: Created background after LayerManager integration')
    }
    
    // Call debug to see current state
    this.debugBackground()
  }

  // ============================================================================
  // CANVAS MANAGEMENT
  // ============================================================================

  setSize(width: number, height: number): void {
    console.log('🔍 CanvasManager: setSize called', {
      oldCanvasDimensions: { width: this.originalCanvasWidth, height: this.originalCanvasHeight },
      newCanvasDimensions: { width, height },
      stageDimensions: { width: this.stage.width(), height: this.stage.height() },
      isLoadingDesign: this.state.isLoadingDesign
    })
    
    // DO NOT change stage dimensions - stage should always match viewport
    // Only update the canvas content dimensions
    this.originalCanvasWidth = width
    this.originalCanvasHeight = height
    
    // Update background rectangle dimensions if it exists
    if (this.backgroundRect) {
      this.backgroundRect.width(width)
      this.backgroundRect.height(height)
      // Redraw the main layer since background is now part of it
      this.layerManager?.getMainLayer().batchDraw()
      console.log('🔍 CanvasManager: Updated background rectangle dimensions')
    }
    
    // Update grid when canvas size changes
    this.updateGrid()
    
    // Update background to maintain proper styling
    this.updateBackground()
    
    // Only emit canvas:resized if not loading a design
    if (!this.state.isLoadingDesign) {
      this.eventEmitter.emit('canvas:resized', { width, height })
    }
    
    console.log('🔍 CanvasManager: setSize completed', {
      canvasDimensions: { width: this.originalCanvasWidth, height: this.originalCanvasHeight },
      stageDimensions: { width: this.stage.width(), height: this.stage.height() }
    })
  }

  getSize(): { width: number, height: number } {
    return {
      width: this.originalCanvasWidth,
      height: this.originalCanvasHeight
    }
  }

  setBackgroundColor(color: string): void {
    this.backgroundColor = color
    this.backgroundConfig = { type: 'solid', color }
    this.createBackground()
    this.updateViewport() // Use updateViewport() instead of updateBackground() to ensure proper positioning
    
    // Emit canvas:changed event for autosave and history tracking
    if (!this.state.isLoadingDesign) {
      this.eventEmitter.emit('canvas:changed')
    }
  }

  setBackground(background: any): void {
    console.log('🔍 CanvasManager: setBackground called with:', background)
    console.log('🔍 CanvasManager: Background type:', typeof background)
    console.log('🔍 CanvasManager: Background stringified:', JSON.stringify(background, null, 2))
    
    this.backgroundConfig = background
    // For backward compatibility with solid colors
    if (background?.type === 'solid' && background?.color) {
      this.backgroundColor = background.color
    } else if (typeof background === 'string') {
      this.backgroundColor = background
      this.backgroundConfig = { type: 'solid', color: background }
    }
    console.log('🔍 CanvasManager: Updated backgroundConfig:', this.backgroundConfig)
    console.log('🔍 CanvasManager: Current backgroundColor:', this.backgroundColor)
    
    this.createBackground()
    //this.updateViewport()
    
    // Emit canvas:changed event for autosave and history tracking
    if (!this.state.isLoadingDesign) {
      this.eventEmitter.emit('canvas:changed')
    }
    
    console.log('🔍 CanvasManager: Background update complete')
  }

  getBackgroundColor(): string {
    return this.backgroundColor
  }

  getBackground(): any {
    return this.backgroundConfig || { type: 'solid', color: this.backgroundColor }
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
      console.log('🔍 CanvasManager: Using provided viewport dimensions', { viewportWidth, viewportHeight })
    } else {
      // Fallback: try to get dimensions from container
      const viewportContainer = container.parentElement
      if (!viewportContainer) return

      const viewportRect = viewportContainer.getBoundingClientRect()
      availableWidth = viewportRect.width
      availableHeight = viewportRect.height
      console.log('🔍 CanvasManager: Using container dimensions', { 
        width: availableWidth, 
        height: availableHeight,
        containerRect: viewportRect 
      })
    }

    console.log('🔍 CanvasManager: Stage vs Container comparison', {
      stage: { width: this.stage.width(), height: this.stage.height() },
      container: { width: container.offsetWidth, height: container.offsetHeight },
      available: { width: availableWidth, height: availableHeight }
    })

    const canvasWidth = this.originalCanvasWidth
    const canvasHeight = this.originalCanvasHeight

    // Apply padding for breathing room
    const padding = 20
    const targetWidth = Math.max(availableWidth - padding * 2, 100)
    const targetHeight = Math.max(availableHeight - padding * 2, 100)

    // Calculate scale using CSS object-fit: contain logic
    const scaleX = targetWidth / canvasWidth
    const scaleY = targetHeight / canvasHeight
    let scale = Math.min(scaleX, scaleY)
    scale = Math.max(scale, 0.01)
    scale = Math.min(scale, 10)
    
    console.log('🔍 CanvasManager: FitToViewport calculation', {
      available: { width: availableWidth, height: availableHeight },
      target: { width: targetWidth, height: targetHeight },
      canvas: { width: canvasWidth, height: canvasHeight },
      scales: { x: scaleX, y: scaleY, final: scale },
      padding
    })
    
    this.setZoom(scale, { zoomToCenter: true })

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
    this.updateViewport()
    
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
    this.updateViewport()
    
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
      // Zoom while keeping the canvas content centered in the viewport
      const stageWidth = this.stage.width()
      const stageHeight = this.stage.height()
      
      // Calculate viewport center
      const viewportCenter = {
        x: stageWidth / 2,
        y: stageHeight / 2
      }
      
      // Calculate canvas center in world coordinates
      const canvasCenter = {
        x: this.originalCanvasWidth / 2,
        y: this.originalCanvasHeight / 2
      }
      
      // Position the stage so that the canvas center aligns with viewport center
      // Add a small vertical offset if needed to account for UI elements
      const verticalOffset = 0 // We'll adjust this if needed based on debug output
      const newPos = {
        x: viewportCenter.x - canvasCenter.x * clampedZoom,
        y: viewportCenter.y - canvasCenter.y * clampedZoom + verticalOffset
      }
      
      console.log('🔍 CanvasManager: Centering debug', {
        stageSize: { width: stageWidth, height: stageHeight },
        viewportCenter,
        canvasSize: { width: this.originalCanvasWidth, height: this.originalCanvasHeight },
        canvasCenter,
        zoom: clampedZoom,
        newPos,
        calculation: {
          x: `${viewportCenter.x} - ${canvasCenter.x} * ${clampedZoom} = ${newPos.x}`,
          y: `${viewportCenter.y} - ${canvasCenter.y} * ${clampedZoom} = ${newPos.y}`
        }
      })
      
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
    this.updateViewport()
    
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
    return this.state.zoom || 1
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
    this.updateViewport()
    
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
      // Enable stage dragging
      this.stage.draggable(true)
      this.stage.container().style.cursor = 'grab'
      
      // Disable layer interactions
      this.stage.listening(false)
      
      // Clear any current selection
      if (this.layerManager) {
        this.layerManager.deselectAll()
      }
      
      // Add pan cursor feedback
      this.stage.on('dragstart.panmode', () => {
        this.stage.container().style.cursor = 'grabbing'
      })
      
      this.stage.on('dragend.panmode', () => {
        this.stage.container().style.cursor = 'grab'
      })
      
    } else {
      // Disable stage dragging
      this.stage.draggable(false)
      this.stage.container().style.cursor = 'default'
      
      // Re-enable layer interactions
      this.stage.listening(true)
      
      // Remove pan mode event listeners
      this.stage.off('dragstart.panmode')
      this.stage.off('dragend.panmode')
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
    this.updateViewport()
    
    this.eventEmitter.emit('viewport:changed', { 
      zoom: 1, 
      panX: this.state.panX, 
      panY: this.state.panY 
    })
  }

  /**
   * Center the canvas in the viewport while preserving the current zoom level
   * This is useful when the viewport size changes (e.g., panels opening/closing)
   */
  centerCanvas(): void {
    const currentZoom = this.getZoom()
    
    // Use the existing setZoom logic with zoomToCenter to center the canvas
    // This preserves the zoom level and centers the content
    this.setZoom(currentZoom, { zoomToCenter: true })
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
    console.log(`🔍 CanvasManager: setupCanvas() called - background will be created when LayerManager is set`)
    // Background creation moved to setLayerManager() method
    // this.createBackground() - removed, LayerManager not available yet
    this.updateBackground()
    console.log(`🔍 CanvasManager: setupCanvas() completed`)
  }

  private createBackground(): void {
    // Only create background if LayerManager is available
    if (!this.layerManager) {
      console.log('🔍 CanvasManager: LayerManager not available, skipping background creation')
      return
    }
    
    // Create or update background rectangle
    if (!this.backgroundRect) {
      this.backgroundRect = new Konva.Rect({
        x: 0,
        y: 0,
        width: this.originalCanvasWidth,
        height: this.originalCanvasHeight,
        listening: false, // Don't interfere with interactions
        name: 'canvas-background',
        fill: this.backgroundColor, // Ensure it has a fill
        visible: true, // Ensure it's visible
        opacity: 1 // Ensure it's opaque
      })
      
      // Add to main layer
      const mainLayer = this.layerManager.getMainLayer()
      mainLayer.add(this.backgroundRect)
      this.backgroundRect.moveToBottom() // Ensure background is at the bottom
      console.log('🔍 CanvasManager: Created and added background rectangle to main layer', {
        width: this.backgroundRect.width(),
        height: this.backgroundRect.height(),
        backgroundColor: this.backgroundColor,
        fill: this.backgroundRect.fill(),
        visible: this.backgroundRect.visible()
      })
    }
    
    // Ensure background is attached (in case it was detached)
    this.ensureBackgroundAttached()
    
    // Apply background fill based on configuration
    this.applyBackgroundFill()
    
    // Force redraw on the main layer
    this.layerManager.getMainLayer().batchDraw()
    console.log('🔍 CanvasManager: Background creation and rendering complete')
    
    // Call debug to see current state
    this.debugBackground()
  }
  private applyBackgroundFill(): void {
    if (!this.backgroundRect) {
      console.log('🔍 CanvasManager: applyBackgroundFill - missing backgroundRect')
      return
    }
    
    // Ensure background is attached before applying fill
    this.ensureBackgroundAttached()

    const config = this.backgroundConfig || { type: 'solid', color: this.backgroundColor }
    console.log('🔍 CanvasManager: Applying background fill with config:', config)
    console.log('🔍 CanvasManager: BackgroundRect dimensions:', { 
      width: this.backgroundRect.width(), 
      height: this.backgroundRect.height(),
      x: this.backgroundRect.x(),
      y: this.backgroundRect.y()
    })

    if (config.type === 'solid' || !config.type) {
      // Solid color background
      console.log('🔍 CanvasManager: Applying solid color:', config.color || this.backgroundColor)
      this.backgroundRect.fill(config.color || this.backgroundColor)
      this.backgroundRect.fillLinearGradientStartPoint(undefined)
      this.backgroundRect.fillLinearGradientEndPoint(undefined)
      this.backgroundRect.fillRadialGradientStartPoint(undefined)
      this.backgroundRect.fillRadialGradientEndPoint(undefined)
      console.log('🔍 CanvasManager: Solid color applied, fill:', this.backgroundRect.fill())
    } else if (config.type === 'linear' && config.gradient) {
      // Linear gradient background
      console.log('🔍 CanvasManager: Applying linear gradient:', config.gradient)
      const gradient = config.gradient
      const angle = gradient.angle || 0
      
      // Use current background rectangle dimensions instead of original canvas size
      const width = this.backgroundRect.width()
      const height = this.backgroundRect.height()
      const rectX = this.backgroundRect.x()
      const rectY = this.backgroundRect.y()
      
      console.log('🔍 CanvasManager: Background rect dimensions for gradient:', { width, height, x: rectX, y: rectY })
      console.log('🔍 CanvasManager: Gradient angle:', angle)
      console.log('🔍 CanvasManager: Gradient colors:', gradient.colors)
      
      // Convert angle to start/end points relative to background rectangle
      const radians = (angle * Math.PI) / 180
      const length = Math.sqrt(width * width + height * height)
      const centerX = width / 2
      const centerY = height / 2
      const startX = centerX - (Math.cos(radians) * length) / 2
      const startY = centerY - (Math.sin(radians) * length) / 2
      const endX = centerX + (Math.cos(radians) * length) / 2
      const endY = centerY + (Math.sin(radians) * length) / 2
      
      console.log('🔍 CanvasManager: Gradient points (relative to rect):', { startX, startY, endX, endY })
      
      // Build color stops array
      const colorStops: (number | string)[] = []
      gradient.colors.forEach((stop: { color: string; stop: number }) => {
        colorStops.push(stop.stop, stop.color)
      })
      
      console.log('🔍 CanvasManager: Color stops array:', colorStops)
      
      this.backgroundRect.fill(undefined)
      this.backgroundRect.fillLinearGradientStartPoint({ x: startX, y: startY })
      this.backgroundRect.fillLinearGradientEndPoint({ x: endX, y: endY })
      this.backgroundRect.fillLinearGradientColorStops(colorStops)
      
      // Clear radial gradient properties
      this.backgroundRect.fillRadialGradientStartPoint(undefined)
      this.backgroundRect.fillRadialGradientEndPoint(undefined)
      
      console.log('🔍 CanvasManager: Linear gradient applied')
      console.log('🔍 CanvasManager: fillLinearGradientStartPoint:', this.backgroundRect.fillLinearGradientStartPoint())
      console.log('🔍 CanvasManager: fillLinearGradientEndPoint:', this.backgroundRect.fillLinearGradientEndPoint())
      console.log('🔍 CanvasManager: fillLinearGradientColorStops:', this.backgroundRect.fillLinearGradientColorStops())
    } else if (config.type === 'radial' && config.gradient) {
      // Radial gradient background
      console.log('🔍 CanvasManager: Applying radial gradient:', config.gradient)
      const gradient = config.gradient
      
      // Use current background rectangle dimensions instead of original canvas size
      const width = this.backgroundRect.width()
      const height = this.backgroundRect.height()
      const centerX = (gradient.centerX || 0.5) * width
      const centerY = (gradient.centerY || 0.5) * height
      const radius = (gradient.radius || 0.7) * Math.max(width, height) / 2
      
      console.log('🔍 CanvasManager: Radial gradient params (relative to rect):', { centerX, centerY, radius })
      
      // Build color stops array
      const colorStops: (number | string)[] = []
      gradient.colors.forEach((stop: { color: string; stop: number }) => {
        colorStops.push(stop.stop, stop.color)
      })
      
      console.log('🔍 CanvasManager: Radial color stops:', colorStops)
      
      this.backgroundRect.fill(undefined)
      this.backgroundRect.fillRadialGradientStartPoint({ x: centerX, y: centerY })
      this.backgroundRect.fillRadialGradientEndPoint({ x: centerX, y: centerY })
      this.backgroundRect.fillRadialGradientStartRadius(0)
      this.backgroundRect.fillRadialGradientEndRadius(radius)
      this.backgroundRect.fillRadialGradientColorStops(colorStops)
      
      // Clear linear gradient properties
      this.backgroundRect.fillLinearGradientStartPoint(undefined)
      this.backgroundRect.fillLinearGradientEndPoint(undefined)
      
      console.log('🔍 CanvasManager: Radial gradient applied')
    } else {
      console.log('🔍 CanvasManager: Unknown background config type:', config.type)
    }
    
    // Ensure background rect is at the bottom and redraw the main layer
    if (this.backgroundRect && this.layerManager) {
      this.backgroundRect.moveToBottom()
      this.layerManager.getMainLayer().batchDraw()
      console.log('🔍 CanvasManager: Background moved to bottom and main layer redrawn')
    }
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
      this.updateViewport()
      
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
        this.updateViewport()
        
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
      this.updateViewport()
      
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

  /**
   * Get current canvas dimensions (for debugging)
   */
  getCanvasDimensions(): { width: number, height: number } {
    return {
      width: this.originalCanvasWidth,
      height: this.originalCanvasHeight
    }
  }

  /**
   * Debug method to check all canvas-related dimensions and states
   */
  debugCanvasDimensions(): void {
    console.log('🐛 CanvasManager: Canvas Dimensions Debug Report', {
      originalCanvasSize: { width: this.originalCanvasWidth, height: this.originalCanvasHeight },
      stageSize: { width: this.stage.width(), height: this.stage.height() },
      backgroundRectSize: this.backgroundRect ? { 
        width: this.backgroundRect.width(), 
        height: this.backgroundRect.height(),
        x: this.backgroundRect.x(),
        y: this.backgroundRect.y(),
        visible: this.backgroundRect.visible()
      } : 'not created',
      layerManagerAttached: !!this.layerManager,
      isLoadingDesign: this.state.isLoadingDesign,
      mainLayerChildren: this.layerManager ? this.layerManager.getMainLayer().children.length : 'N/A'
    })
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
    this.updateViewport()
    
    this.updateState()
  }

  destroy(): void {
    // Clean up background elements
    if (this.backgroundRect) {
      this.backgroundRect.destroy()
      this.backgroundRect = undefined
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
    
    console.log('🔍 CanvasManager: Destroyed all canvas elements')
  }

  private updateBackground(): void {
    // Update Konva background rectangle for export compatibility
    if (this.backgroundRect) {
      this.backgroundRect.fill(this.backgroundColor)
      this.layerManager?.getMainLayer().batchDraw()
      console.log(`🔍 CanvasManager: Background dimension is ${this.backgroundRect.width()}x${this.backgroundRect.height()}`)
      
    }
  }

  /**
   * Update background rectangle for viewport changes (zoom/pan)
   * FIXED: Keep background constrained to canvas dimensions, don't expand to fill viewport
   */
  private updateBackgroundForViewport(): void {
    if (!this.backgroundRect) return
    
    // FIXED: Instead of expanding to fill visible viewport, keep background at canvas size
    // This ensures the background only covers the actual design canvas area
    this.backgroundRect.setAttrs({
      x: 0,
      y: 0,
      width: this.originalCanvasWidth,
      height: this.originalCanvasHeight
      // Don't set fill here - it would override gradients!
    })
    
    this.layerManager?.getMainLayer().batchDraw()
    
    console.log(`🔍 CanvasManager: Background kept at canvas dimensions - pos:(0, 0) size:(${this.originalCanvasWidth}x${this.originalCanvasHeight}) [FIXED: no longer expanding to viewport]`)
  }

  /**
   * Update all viewport-dependent elements (background and content layers)
   * This should be called instead of updateBackgroundForViewport() to ensure
   * both background and user content layers are properly updated for zoom/pan changes
   */
  private updateViewport(): void {
    // Update background layer
    this.updateBackgroundForViewport()
    
    // Update main content layer through LayerManager
    if (this.layerManager) {
      this.layerManager.updateMainLayerForViewport()
    }
    
    
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

  /**
   * Ensure background is properly attached to main layer
   */
  ensureBackgroundAttached(): void {
    if (!this.backgroundRect || !this.layerManager) {
      return
    }
    
    const mainLayer = this.layerManager.getMainLayer()
    const isAttached = mainLayer.children.includes(this.backgroundRect)
    
    if (!isAttached) {
      console.log('🔍 CanvasManager: Background detached, re-adding to main layer')
      mainLayer.add(this.backgroundRect)
      this.backgroundRect.moveToBottom()
      this.applyBackgroundFill()
      mainLayer.batchDraw()
      console.log('🔍 CanvasManager: Background re-attached successfully')
    }
  }

  /**
   * Debug method to check background state
   */
  debugBackground(): void {
    console.log('🔍 CanvasManager: Background Debug Info:')
    console.log('  - LayerManager available:', !!this.layerManager)
    console.log('  - Background rect exists:', !!this.backgroundRect)
    console.log('  - Background color:', this.backgroundColor)
    console.log('  - Background config:', this.backgroundConfig)
    
    if (this.backgroundRect) {
      console.log('  - Background rect properties:', {
        x: this.backgroundRect.x(),
        y: this.backgroundRect.y(),
        width: this.backgroundRect.width(),
        height: this.backgroundRect.height(),
        fill: this.backgroundRect.fill(),
        visible: this.backgroundRect.visible(),
        opacity: this.backgroundRect.opacity(),
        listening: this.backgroundRect.listening(),
        parent: this.backgroundRect.getParent()?.name()
      })
      
      if (this.layerManager) {
        const mainLayer = this.layerManager.getMainLayer()
        console.log('  - Main layer children count:', mainLayer.children.length)
        console.log('  - Background rect in main layer:', mainLayer.children.includes(this.backgroundRect))
      }
    }
    
    if (this.layerManager) {
      const mainLayer = this.layerManager.getMainLayer()
      console.log('  - Main layer info:', {
        name: mainLayer.name(),
        children: mainLayer.children.length,
        visible: mainLayer.visible(),
        opacity: mainLayer.opacity()
      })
    }
  }

  /**
   * Test method to force background visibility with a contrasting color
   */
  testBackground(): void {
    console.log('🔍 CanvasManager: Testing background visibility with red color')
    this.setBackgroundColor('#ff0000') // Set to red for visibility test
    this.debugBackground()
  }

  /**
   * Update stage dimensions to match viewport (separate from canvas content size)
   */
  setStageSize(width: number, height: number): void {
    console.log('🔍 CanvasManager: setStageSize called', {
      oldStageDimensions: { width: this.stage.width(), height: this.stage.height() },
      newStageDimensions: { width, height }
    })
    
    this.stage.width(width)
    this.stage.height(height)
    
    console.log('🔍 CanvasManager: Stage dimensions updated')
  }
}

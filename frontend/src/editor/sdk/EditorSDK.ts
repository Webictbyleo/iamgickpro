import Konva from 'konva'
import { EventEmitter } from './EventEmitter'
import type { 
  EditorConfig, 
  EditorState, 
  LayerAPI, 
  CanvasAPI, 
  AnimationAPI, 
  PluginAPI,
  LayerNode,
  EditorEvents
} from './types'
import { LayerManager } from './LayerManager'
import { CanvasManager } from './CanvasManager'
import { AnimationManager } from './AnimationManager'
import { PluginManager } from './PluginManager'
import { TransformManager } from './TransformManager'
import { HistoryManager } from './HistoryManager'
import type { Layer, Design, DesignData, LayerType } from '../../types'

/**
 * Main Editor SDK class - provides unified interface for all editor operations
 */
export class EditorSDK extends EventEmitter {
  private stage: Konva.Stage
  private layerManager: LayerManager
  private canvasManager: CanvasManager
  private animationManager: AnimationManager
  private pluginManager: PluginManager
  private transformManager: TransformManager
  private historyManager: HistoryManager
  
  private state: EditorState = {
    selectedLayers: [],
    zoom: 1,
    panX: 0,
    panY: 0,
    isPlaying: false,
    currentTime: 0,
    duration: 10,
    isLoadingDesign: false // Track loading state in shared state
  }

  constructor(private config: EditorConfig) {
    super()
    
    // Resolve container element
    const container = typeof config.container === 'string' 
      ? document.querySelector(config.container) as HTMLDivElement
      : config.container as HTMLDivElement
    
    if (!container) {
      throw new Error('Container element not found')
    }
    
    // Clear any existing content in the container
    container.innerHTML = ''
    
    
    
    // Create Konva Stage (fills the container viewport)
    this.stage = new Konva.Stage({
      container,
      width: config.width || 800,
      height: config.height || 600
    })
    
    // Improve hit detection for better layer selection at any zoom level
    this.stage.listening(true)
    
    // Set up layerEmitter on stage for renderer communication
    ;(this.stage as any).layerEmitter = this
    
    // Initialize managers with canvas dimensions
    const canvasWidth = config.canvasWidth || config.width || 800
    const canvasHeight = config.canvasHeight || config.height || 600
    this.layerManager = new LayerManager(this.stage, this.state, this)
    this.canvasManager = new CanvasManager(this.stage, this.state, this, canvasWidth, canvasHeight)
    this.animationManager = new AnimationManager(this.state, this)
    this.pluginManager = new PluginManager(this.state, this)
    this.transformManager = new TransformManager(this.stage, this.state, this)
    this.historyManager = new HistoryManager(null, this) // Kept for compatibility but disabled
    
    // Connect managers
    this.connectManagers()
    
    this.setupEventHandlers()
    this.initializeCanvas();

    (window as any).editorSDK = this // Expose globally for debugging
  }

  // ============================================================================
  // MANAGER CONNECTIONS
  // ============================================================================

  private connectManagers(): void {
    // Connect AnimationManager with LayerManager for layer lookup
    this.animationManager.setLayerFinder((layerId: number) => this.layerManager.getLayer(layerId))
    
    // Connect LayerManager with TransformManager for transformation handling
    this.layerManager.setTransformManager(this.transformManager)
    
    // History is now handled by useDesignHistory composable - disable SDK history
    this.historyManager = new HistoryManager(null, this) // Kept for compatibility but not connected
    this.layerManager.setHistoryManager(null) // Disabled
    this.transformManager.setHistoryManager(null) // Disabled
    
    // Connect CanvasManager with LayerManager for background integration
    this.canvasManager.setLayerManager(this.layerManager)
    
    // Connect viewport changes to transform manager for responsive anchors
    this.on('viewport:changed', () => {
      this.transformManager.updateTransformerForZoom()
    })
  }

  // ============================================================================
  // PUBLIC API
  // ============================================================================

  /**
   * Layer management API
   */
  get layers(): LayerAPI {
    return this.layerManager
  }

  /**
   * Canvas management API
   */
  get canvas(): CanvasAPI {
    return this.canvasManager
  }

  /**
   * History management API - DISABLED (now handled by useDesignHistory)
   */
  get history() {
    return {
      undo: () => console.warn('History now handled by useDesignHistory composable'),
      redo: () => console.warn('History now handled by useDesignHistory composable'),
      canUndo: () => false,
      canRedo: () => false,
      clear: () => console.warn('History now handled by useDesignHistory composable'),
      getState: () => ({ canUndo: false, canRedo: false, currentIndex: -1, totalCommands: 0 })
    }
  }

  /**
   * Animation management API
   */
  get animation(): AnimationAPI {
    return this.animationManager
  }

  /**
   * Plugin management API
   */
  get plugins(): PluginAPI {
    return this.pluginManager as PluginAPI
  }

  /**
   * Transform management API
   */
  get transform() {
    return {
      selectLayers: (layerIds: number[]) => {
        const layers = layerIds.map(id => this.layerManager.getLayer(id)).filter(Boolean) as LayerNode[]
        this.transformManager.selectLayers(layers)
        // Update state for compatibility
        this.state.selectedLayers = layerIds
      },
      selectLayer: (layerId: number) => {
        const layer = this.layerManager.getLayer(layerId)
        if (layer) {
          this.transformManager.selectLayer(layer)
          // Update state for compatibility
          this.state.selectedLayers = [layerId]
        }
      },
      deselectAll: () => {
        this.transformManager.deselectAll()
        // Update state for compatibility
        this.state.selectedLayers = []
      },
      applyPositionPreset: (preset: string) => {
        const canvasSize = this.canvasManager.getSize()
        this.transformManager.applyPositionPreset(preset, canvasSize.width, canvasSize.height)
      },
      getSelectedLayers: () => this.transformManager.getSelectedLayers()
    }
  }

  /**
   * Get current editor state
   */
  getState(): EditorState {
    return { ...this.state }
  }

  /**
   * Check if design is currently being loaded
   */
  isLoading(): boolean {
    return this.state.isLoadingDesign
  }

  /**
   * Load design data into the editor
   */
  async loadDesign(design: Design): Promise<void> {
    try {
      // Set loading state to prevent circular events
      this.state.isLoadingDesign = true
      
      // Clear existing layers first
      await this.layerManager.clear()
      
      // Set canvas size
      this.canvasManager.setSize(design.width, design.height)
      
      // Ensure design data structure exists
      if (!design.data) {
        console.warn('EditorSDK: Design data is missing, initializing default structure')
        design.data = {
          animationSettings: {},
          backgroundColor: '#ffffff',
          customProperties: {},
          globalStyles: {},
          gridSettings: {
            gridSize: 20,
            showGrid: false,
            snapToGrid: false,
            snapToObjects: false,
            snapTolerance: 5
          },
          viewportSettings: {
            zoom: 1,
            panX: 0,
            panY: 0
          }
        }
      }
      
      // Set background from design data - support both new and legacy formats
      if (design.data.background) {
        // New DesignBackground format
        this.canvasManager.setBackground(design.data.background)
      } else if (design.data.backgroundColor) {
        // Legacy string format
        this.canvasManager.setBackgroundColor(design.data.backgroundColor)
      } else {
        // Default background
        this.canvasManager.setBackgroundColor('#ffffff')
      }
      
      // Load layers - check both direct layers array and legacy data structure
      let layersToLoad: Layer[] = []
      
      if (design.layers && Array.isArray(design.layers)) {
        // Use direct layers array from backend
        layersToLoad = design.layers
      } else {
        layersToLoad = []
      }
      
      // Load layers silently to prevent event emission during loading
      for (const layerData of layersToLoad) {
        try {
          // Create layer with silent flag to prevent events during loading
          await this.layerManager.createLayer(layerData.type, layerData, true)
        } catch (layerError) {
          console.error(`EditorSDK: Failed to load layer ${layerData.id}:`, layerError)
          // Continue loading other layers even if one fails
        }
      }
      
      // Apply viewport settings if available
      if (design.data.viewportSettings) {
        if (design.data.viewportSettings.zoom !== undefined) {
          this.state.zoom = design.data.viewportSettings.zoom
        }
        if (design.data.viewportSettings.panX !== undefined) {
          this.state.panX = design.data.viewportSettings.panX
        }
        if (design.data.viewportSettings.panY !== undefined) {
          this.state.panY = design.data.viewportSettings.panY
        }
        
        // Apply zoom and pan to stage
        this.stage.scale({ x: this.state.zoom, y: this.state.zoom })
        this.stage.position({ x: this.state.panX, y: this.state.panY })
      } else {
        // Fit canvas to viewport if no custom viewport settings
        this.canvasManager.zoomToFit()
      }
      
      // Clear loading state before emitting events
      this.state.isLoadingDesign = false
      
      // Force refresh hit detection after design loads to ensure proper interaction at all zoom levels
      this.layerManager.refreshHitDetection()
      
      this.emit('design:loaded', design)
    } catch (error) {
      this.state.isLoadingDesign = false
      console.error('EditorSDK: Failed to load design:', error)
      throw error
    }
  }

  /**
   * Export current design data
   */
  exportDesign(): DesignData {
    const layers = this.layerManager.getAllLayers()
    const canvasSize = this.canvasManager.getSize()
    const background = this.canvasManager.getBackground()
    const backgroundColor = this.canvasManager.getBackgroundColor()
    
    return {
      // Support both new and legacy background formats
      background,
      backgroundColor, // Keep for backward compatibility
      animationSettings: {},
      customProperties: {},
      globalStyles: {},
      gridSettings: {
        gridSize: 20,
        showGrid: false,
        snapToGrid: false,
        snapToObjects: false,
        snapTolerance: 5
      },
      viewportSettings: {
        zoom: this.state.zoom,
        panX: this.state.panX,
        panY: this.state.panY
      }
    }
  }

  /**
   * Export design as image in specified format
   * Simple and direct export using Konva's built-in toDataURL method
   */
  exportAsImage(format: 'png' | 'jpeg' | 'webp' = 'png', quality: number = 1): string {
    if (!this.stage) {
      throw new Error('Stage not initialized')
    }

    try {
      // Get the original canvas dimensions for consistent export
      const canvasSize = this.canvasManager.getSize()
      
      // Store current stage transform state
      const currentScale = this.stage.scaleX()
      const currentX = this.stage.x()
      const currentY = this.stage.y()
      
      // Temporarily reset stage transform for export
      this.stage.scale({ x: 1, y: 1 })
      this.stage.position({ x: 0, y: 0 })
      this.stage.batchDraw()
      
      // Export at original canvas size without any viewport scaling
      const dataURL = this.stage.toDataURL({
        mimeType: `image/${format}`,
        quality: format === 'jpeg' ? quality : 1,
        pixelRatio: 1, // Use 1:1 pixel ratio for exact canvas size
        x: 0,
        y: 0,
        width: canvasSize.width,
        height: canvasSize.height
      })
      
      // Restore original stage transform
      this.stage.scale({ x: currentScale, y: currentScale })
      this.stage.position({ x: currentX, y: currentY })
      this.stage.batchDraw()
      
      return dataURL
    } catch (error) {
      console.error('🎨 EditorSDK: Failed to export design as image:', error)
      throw new Error(`Failed to export as ${format.toUpperCase()}: ${error instanceof Error ? error.message : 'Unknown error'}`)
    }
  }

  /**
   * Download design as image file
   */
  downloadAsImage(
    format: 'png' | 'jpeg' | 'webp' = 'png', 
    filename?: string, 
    quality: number = 1
  ): void {
    try {
      const dataURL = this.exportAsImage(format, quality)
      
      // Create download link
      const link = document.createElement('a')
      link.download = filename || `design.${format}`
      link.href = dataURL
      
      // Trigger download
      document.body.appendChild(link)
      link.click()
      document.body.removeChild(link)
    } catch (error) {
      console.error('Download failed:', error)
      throw error
    }
  }

  /**
   * Destroy the editor and clean up resources
   */
  destroy(): void {
    this.layerManager.destroy()
    this.canvasManager.destroy()
    this.animationManager.destroy()
    this.pluginManager.destroy()
    this.transformManager.destroy()
    this.historyManager.destroy()
    this.stage.destroy()
    this.removeAllListeners()
  }

  // ============================================================================
  // PRIVATE METHODS
  // ============================================================================

  private setupEventHandlers(): void {
    // Remove stage click handler - let LayerManager handle all clicks naturally
    // This prevents interference with Konva's natural event bubbling at different zoom levels
    
    // Context menu handler for right-click
    this.stage.on('contextmenu', (e:Konva.KonvaEventObject<MouseEvent | TouchEvent>) => {
      e.evt.preventDefault()
      
      // Get the clicked layer
      const clickedLayer = e.target

      
      // Find the layer data if a layer was clicked
      let layerData = null
      if (clickedLayer && clickedLayer.id()) {
        const layerId = parseInt(clickedLayer.id(), 10) // Parse string ID back to number
        const layerNode = this.layerManager.getLayer(layerId)
        
        if (layerNode) {
          // Convert LayerNode to Layer format for compatibility with UI components
          layerData = {
            id: layerNode.id,
            type: layerNode.type as any, // Cast to satisfy type checking
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
        }
      }
      
      // Emit context menu event with mouse position and layer data
      this.emit('layer:context-menu', {
        event: e.evt,
        layer: layerData,
        position: {
          x: e.evt instanceof MouseEvent ? e.evt.clientX : e.evt.touches[0].clientX,
          y: e.evt instanceof MouseEvent ? e.evt.clientY : e.evt.touches[0].clientY
        }
      })
    })

    // Stage drag handlers for panning
    this.stage.on('dragstart', () => {
      this.stage.container().style.cursor = 'grabbing'
    })

    this.stage.on('dragend', () => {
      this.stage.container().style.cursor = 'default'
      this.updateViewport()
    })

    // Wheel handler for zooming
    this.stage.on('wheel', (e) => {
      e.evt.preventDefault()
      
      const oldScale = this.stage.scaleX()
      const pointer = this.stage.getPointerPosition()!
      
      const scaleBy = 1.1
      const newScale = e.evt.deltaY < 0 ? oldScale * scaleBy : oldScale / scaleBy
      
      this.stage.scale({ x: newScale, y: newScale })
      
      // Adjust position to zoom around pointer
      const newPos = {
        x: pointer.x - (pointer.x - this.stage.x()) * (newScale / oldScale),
        y: pointer.y - (pointer.y - this.stage.y()) * (newScale / oldScale)
      }
      
      this.stage.position(newPos)
      this.updateViewport()
    })

    // History is now handled by useDesignHistory composable
    // Removed history event forwarding

    // Keyboard shortcuts
    window.addEventListener('keydown', this.handleKeyboard.bind(this))
  }

  private handleKeyboard(e: KeyboardEvent): void {
    // Only handle shortcuts when editor is focused
    const container = typeof this.config.container === 'string'
      ? document.querySelector(this.config.container) as HTMLElement
      : this.config.container as HTMLElement
      
    if (!container || !container.contains(document.activeElement)) {
      return
    }

    switch (e.key) {
      case 'Delete':
      case 'Backspace':
        e.preventDefault()
        this.deleteSelectedLayers()
        break
      
      case 'Escape':
        e.preventDefault()
        this.layerManager.deselectAll()
        break
        
      case 'a':
        if (e.ctrlKey || e.metaKey) {
          e.preventDefault()
          this.selectAllLayers()
        }
        break
        
      case 'z':
        if (e.ctrlKey || e.metaKey) {
          e.preventDefault()
          if (e.shiftKey) {
            // Redo
            this.redo()
          } else {
            // Undo
            this.undo()
          }
        }
        break
        
      case '0':
        if (e.ctrlKey || e.metaKey) {
          e.preventDefault()
          this.canvasManager.zoomToFit()
        }
        break
    }
  }

  private initializeCanvas(): void {
    // Set initial background
    if (this.config.backgroundColor) {
      this.canvasManager.setBackgroundColor(this.config.backgroundColor)
    }
  }

  private updateViewport(): void {
    const pos = this.stage.position()
    const scale = this.stage.scaleX()
    
    this.state.zoom = scale
    this.state.panX = pos.x
    this.state.panY = pos.y
    
    this.emit('viewport:changed', { 
      zoom: scale, 
      panX: pos.x, 
      panY: pos.y 
    })
    
    // Force refresh hit detection after viewport changes to ensure proper click detection at all zoom levels
    this.layerManager.refreshHitDetection()
  }

  private async deleteSelectedLayers(): Promise<void> {
    const selectedIds = [...this.state.selectedLayers]
    for (const layerId of selectedIds) {
      await this.layerManager.deleteLayer(layerId)
    }
  }

  private selectAllLayers(): void {
    const allLayers = this.layerManager.getAllLayers()
    const layerIds = allLayers.map((layer: LayerNode) => layer.id)
    this.layerManager.selectLayers(layerIds)
  }

  // ============================================================================
  // PUBLIC UNDO/REDO METHODS - DISABLED (now handled by useDesignHistory)
  // ============================================================================

  undo(): void {
    console.warn('EditorSDK: Undo/Redo now handled by useDesignHistory composable')
  }

  redo(): void {
    console.warn('EditorSDK: Undo/Redo now handled by useDesignHistory composable')
  }

  /**
   * Check if undo is available - always false (handled by useDesignHistory)
   */
  canUndo(): boolean {
    return false
  }

  /**
   * Check if redo is available - always false (handled by useDesignHistory)
   */
  canRedo(): boolean {
    return false
  }

  /**
   * Get current history state for UI updates - always empty (handled by useDesignHistory)
   */
  getHistoryState() {
    return { canUndo: false, canRedo: false, currentIndex: -1, totalCommands: 0 }
  }

  /**
   * Clear all history - no-op (handled by useDesignHistory)
   */
  clearHistory(): void {
    console.warn('EditorSDK: History clearing now handled by useDesignHistory composable')
  }
}

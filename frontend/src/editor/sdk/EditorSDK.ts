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
import type { Layer, Design, LayerType } from '../../types'

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
  
  private state: EditorState = {
    selectedLayers: [],
    zoom: 1,
    panX: 0,
    panY: 0,
    isPlaying: false,
    currentTime: 0,
    duration: 10
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
    
    
    
    // Create Konva Stage
    this.stage = new Konva.Stage({
      container,
      width: config.width || 800,
      height: config.height || 600
    })
    
    // Set up layerEmitter on stage for renderer communication
    ;(this.stage as any).layerEmitter = this
    
    // Initialize managers
    this.layerManager = new LayerManager(this.stage, this.state, this)
    this.canvasManager = new CanvasManager(this.stage, this.state, this)
    this.animationManager = new AnimationManager(this.state, this)
    this.pluginManager = new PluginManager(this.state, this)
    this.transformManager = new TransformManager(this.stage, this)
    
    // Connect managers
    this.connectManagers()
    
    this.setupEventHandlers()
    this.initializeCanvas()
  }

  // ============================================================================
  // MANAGER CONNECTIONS
  // ============================================================================

  private connectManagers(): void {
    // Connect AnimationManager with LayerManager for layer lookup
    this.animationManager.setLayerFinder((layerId: string) => this.layerManager.getLayer(layerId))
    
    // Connect LayerManager with TransformManager for transformation handling
    this.layerManager.setTransformManager(this.transformManager)
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
      selectLayers: (layerIds: string[]) => {
        const layers = layerIds.map(id => this.layerManager.getLayer(id)).filter(Boolean) as LayerNode[]
        this.transformManager.selectLayers(layers)
        // Update state for compatibility
        this.state.selectedLayers = layerIds
      },
      selectLayer: (layerId: string) => {
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
   * Load design data into the editor
   */
  async loadDesign(design: Design): Promise<void> {
    try {
      console.log('EditorSDK: Loading design', design)
      
      // Clear existing layers
      await this.layerManager.clear()
      
      // Set canvas size
      this.canvasManager.setSize(design.width, design.height)
      
      // Ensure design data structure exists
      if (!design.designData) {
        console.warn('EditorSDK: Design data is missing, initializing default structure')
        design.designData = {
          version: '1.0',
          layers: [],
          canvas: {
            width: design.width,
            height: design.height,
            backgroundColor: '#ffffff'
          }
        }
      }
      
      // Ensure canvas settings exist
      if (!design.designData.canvas) {
        console.warn('EditorSDK: Canvas settings are missing, initializing defaults')
        design.designData.canvas = {
          width: design.width,
          height: design.height,
          backgroundColor: '#ffffff'
        }
      }
      
      // Ensure layers array exists
      if (!design.designData.layers) {
        console.warn('EditorSDK: Layers array is missing, initializing empty array')
        design.designData.layers = []
      }
      
      // Set background color
      if (design.designData.canvas.backgroundColor) {
        this.canvasManager.setBackgroundColor(design.designData.canvas.backgroundColor)
      }
      
      // Load layers (safe iteration)
      console.log(`EditorSDK: Loading ${design.designData.layers.length} layers`)
      for (const layerData of design.designData.layers) {
        try {
          await this.layerManager.createLayer(layerData.type, layerData)
        } catch (layerError) {
          console.error(`EditorSDK: Failed to load layer ${layerData.id}:`, layerError)
          // Continue loading other layers even if one fails
        }
      }
      
      // Center view
      this.canvasManager.centerView()
      
      console.log('EditorSDK: Design loaded successfully')
      this.emit('design:loaded', design)
    } catch (error) {
      console.error('EditorSDK: Failed to load design:', error)
      throw error
    }
  }

  /**
   * Export current design data
   */
  exportDesign(): Design['designData'] {
    const layers = this.layerManager.getAllLayers()
    const canvasSize = this.canvasManager.getSize()
    const backgroundColor = this.canvasManager.getBackgroundColor()
    
    return {
      version: '1.0',
      layers: layers.map((layer: LayerNode) => ({
        id: layer.id,
        type: layer.type as LayerType,
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
      })),
      canvas: {
        width: canvasSize.width,
        height: canvasSize.height,
        backgroundColor
      }
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
    this.stage.destroy()
    this.removeAllListeners()
  }

  // ============================================================================
  // PRIVATE METHODS
  // ============================================================================

  private setupEventHandlers(): void {
    // Stage click handler for selection
    this.stage.on('click tap', (e) => {
      // Get the actual clicked layer, not the parent
      const clickedNode = e.target
      const clickedLayer = clickedNode instanceof Konva.Stage ? null : clickedNode
      
      if (clickedLayer) {
        // Prevent event from bubbling to stage
        e.cancelBubble = true
        
        // Handle multi-selection with ctrl/cmd key
        if (e.evt.ctrlKey || e.evt.metaKey) {
          const currentSelection = [...this.state.selectedLayers]
          const layerId = clickedLayer.id()
          const index = currentSelection.indexOf(layerId)
          if (index === -1) {
            currentSelection.push(layerId)
            this.layerManager.selectLayers(currentSelection)
          } else {
            currentSelection.splice(index, 1)
            this.layerManager.selectLayers(currentSelection)
          }
        } else {
          // Single selection
          const layerId = clickedLayer.id()
          if (layerId) {
            this.layerManager.selectLayer(layerId)
          }
        }
      } else if (!e.evt.ctrlKey && !e.evt.metaKey) {
        // Clear selection when clicking on empty stage area
        // (but not during multi-select)
        this.layerManager.deselectAll()
      }
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

  private undo(): void {
    // TODO: Implement undo functionality
    console.log('Undo - to be implemented')
  }

  private redo(): void {
    // TODO: Implement redo functionality
    console.log('Redo - to be implemented')
  }
}

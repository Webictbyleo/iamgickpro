import type Konva from 'konva'
import type { Layer } from '../../types'

// ============================================================================
// CORE EDITOR TYPES
// ============================================================================

export interface EditorConfig {
  container: HTMLElement | string
  width: number
  height: number
  backgroundColor?: string
  plugins?: string[]
}

export interface EditorState {
  selectedLayers: number[]
  zoom: number
  panX: number
  panY: number
  isPlaying: boolean
  currentTime: number
  duration: number
  isLoadingDesign: boolean
}

export interface LayerNode {
  id: number
  type: string
  name: string
  visible: boolean
  locked: boolean
  opacity: number
  x: number
  y: number
  width: number
  height: number
  rotation: number
  scaleX: number
  scaleY: number
  zIndex: number
  properties: Record<string, any>
  konvaNode?: Konva.Node
  parent?: LayerNode | null
  children?: LayerNode[]
}

// ============================================================================
// LAYER TYPES AND RENDERERS
// ============================================================================

export type LayerType = 'text' | 'image' | 'shape' | 'group'

export interface Transform {
  x?: number
  y?: number
  width?: number
  height?: number
  rotation?: number
  scaleX?: number
  scaleY?: number
  opacity?: number
}

export interface KonvaLayerRenderer {
  render(layerData: LayerNode): Konva.Node
  update(node: Konva.Node, layerData: LayerNode): void
  destroy(node: Konva.Node): void
  canRender?(layer: Layer): boolean
  getSupportedTypes?(): string[]
}

// ============================================================================
// PLUGIN TYPES
// ============================================================================

export interface EditorPlugin {
  id: string
  name: string
  version: string
  url: string
  manifest: PluginManifest
  iframe?: HTMLIFrameElement
  loaded: boolean
  onLoad?: () => void
  onUnload?: () => void
  onMessage?: (event: MessageEvent) => void
  onSelectionChange?: (selectedLayers: string[]) => void
  onLayerUpdate?: (layer: LayerNode) => void
}

export interface PluginManifest {
  id: string
  name: string
  version: string
  description: string
  permissions: PluginPermission[]
  api_version: string
  entry_point: string
  capabilities: PluginCapability[]
  styles?: string[]
}

export type PluginPermission = 'layer_access' | 'canvas_access' | 'file_access' | 'network_access'

export type PluginCapability = 'text_editing' | 'image_editing' | 'shape_creation' | 'animation'

// ============================================================================
// LAYER API TYPES
// ============================================================================

export interface LayerAPI {
  // Layer CRUD operations
  createLayer(type: string, data: Partial<Layer>): Promise<LayerNode>
  addLayer(layerData: Partial<Layer>): Promise<LayerNode>
  deleteLayer(layerId: number): Promise<void>
  updateLayer(layerId: number, updates: Partial<LayerNode>): Promise<void>
  getLayer(layerId: number): LayerNode | null
  getAllLayers(): LayerNode[]
  
  // Layer selection and transformation
  selectLayer(layerId: number): void
  selectLayers(layerIds: number[]): void
  toggleSelection(layerId: number): void
  deselectAll(): void
  moveLayer(layerId: number, newIndex: number): void
  reorderLayers(layerIds: number[]): void
  duplicateLayer(layerId: number): Promise<LayerNode>
  
  // Layer ordering methods
  bringToFront(layerId: number): void
  bringForward(layerId: number): void
  sendBackward(layerId: number): void
  sendToBack(layerId: number): void
  
  clear(): Promise<void>
}

// ============================================================================
// CANVAS API TYPES
// ============================================================================

export interface CanvasAPI {
  // Canvas management
  setSize(width: number, height: number): void
  getSize(): { width: number; height: number }
  setBackgroundColor(color: string): void
  setBackground(background: any): void
  getBackgroundColor(): string
  getBackground(): any
  
  // Viewport control
  centerView(): void
  zoomToFit(): void
  fitCanvasToViewport(viewportWidth?: number, viewportHeight?: number): void
  resetZoom(): void
  setZoom(zoom: number, options?: { zoomToCenter?: boolean }): void
  getZoom(): number
  setPanMode(enabled: boolean): void
  destroy(): void
}

// ============================================================================
// ANIMATION API TYPES
// ============================================================================

export interface AnimationAPI {
  // Playback control
  play(): void
  pause(): void
  stop(): void
  seek(time: number): void
  
  // Timeline management
  getDuration(): number
  setDuration(duration: number): void
  
  // Keyframe management
  addKeyframe(layerId: number, time: number, properties: Record<string, any>): void
  removeKeyframe(layerId: number, time: number): void
  setLayerFinder(finder: (layerId: number) => LayerNode | null): void
  destroy(): void
}

// ============================================================================
// PLUGIN API TYPES
// ============================================================================

export interface PluginAPI {
  // Plugin lifecycle
  loadPlugin(url: string): Promise<void>
  unloadPlugin(id: string): void
  getPlugins(): string[]
  destroy(): void
}

// ============================================================================
// EVENT TYPES
// ============================================================================

export interface EditorEvents {
  'layer:created': (layer: LayerNode) => void
  'layer:updated': (layer: LayerNode) => void
  'layer:deleted': (layerId: number) => void
  'layer:selected': (layerIds: number[]) => void
  'layer:context-menu': (data: { event: MouseEvent; layer: LayerNode | null; position: { x: number; y: number } }) => void
  'viewport:changed': (viewport: { zoom: number; panX: number; panY: number }) => void
  'design:loaded': (design: any) => void
}

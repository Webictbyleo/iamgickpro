// ============================================================================
// BACKEND RESPONSE STRUCTURE TYPES
// ============================================================================

/**
 * Base response structure from backend - all API responses follow this pattern
 */
export interface BaseApiResponse {
  success: boolean
  message: string
  timestamp: string
}

/**
 * Single entity response structure
 */
export interface ApiResponse<T> extends BaseApiResponse {
  data: T
}

/**
 * Paginated response structure for collections
 */
export interface PaginatedApiResponse<T> extends BaseApiResponse {
  data: {
    pagination: {
      total: number
      page: number
      totalPages: number
    }
  }
}

/**
 * Backend paginated response structure (actual response from PaginatedResponseDTO)
 */
export interface BackendPaginatedResponse<T> {
  data: T[]
  pagination: {
    page: number
    limit: number
    total: number
    totalPages: number
  }
  message: string
}

/**
 * Authentication response structure
 */
export interface AuthApiResponse extends BaseApiResponse {
  token: string
  user: User
}

export interface UpdatedUserApiResponse extends BaseApiResponse {
  user: User
}

/**
 * Error response structure
 */
export interface ErrorApiResponse extends BaseApiResponse {
  success: false
  error?: {
    code?: string
    details?: Record<string, any>
  }
}

// ============================================================================
// ENTITY TYPE DEFINITIONS
// ============================================================================

export interface User {
  id: string
  email: string
  firstName: string
  lastName: string
  username?: string
  jobTitle?: string
  company?: string
  website?: string
  portfolio?: string
  bio?: string
  avatar?: string
  role: string
  roles?: string[]
  plan?: string
  emailVerified?: boolean
  isActive?: boolean
  socialLinks?: {
    twitter?: string
    linkedin?: string
    dribbble?: string
    behance?: string
  }
  settings?: Record<string, any>
  stats?: {
    projectCount?: number
    mediaCount?: number
    exportJobCount?: number
  }
  createdAt: string
  updatedAt?: string
  lastLoginAt?: string
}

export interface Design {
  id: string
  name: string
  title: string // Display title for the design (usually same as name)
  description?: string
  data: DesignData
  layers?: Layer[] // Optional direct layers array from backend
  thumbnail?: string
  width: number
  height: number
  userId: string
  projectId?: string
  isPublic: boolean
  createdAt: string
  updatedAt: string
  // Sync status tracking for network failure handling
  syncStatus?: 'synced' | 'pending' | 'failed'
  isTemporary?: boolean // True if design has not been saved to backend yet
  lastSyncAttempt?: string // ISO timestamp of last sync attempt
  syncError?: string // Error message from last failed sync
}

export interface DesignData {
  animationSettings?: Record<string, any> // Animation settings for the design
  backgroundColor?: string // Background color for the design (legacy)
  background?: DesignBackground // New background configuration supporting gradients
  customProperties?: Record<string, any> // Custom properties for the design
  globalStyles?: Record<string, any> // Global styles for the design
  layers?: Layer[] // Layers data (optional, can be stored at Design level or here)
  gridSettings?: {
    gridSize: number // Size of the grid cells
    showGrid: boolean
    snapToGrid: boolean
    snapToObjects: boolean
    snapTolerance: number // Tolerance for snapping to grid or objects
  }
  viewportSettings?: {
    zoom: number // Current zoom level
    panX: number // Horizontal pan offset
    panY: number // Vertical pan offset
  }

}

export interface DesignBackground {
  type: 'solid' | 'linear' | 'radial'
  color?: string // For solid backgrounds
  gradient?: {
    colors: Array<{ color: string; stop: number }> // Color stops for gradients
    angle?: number // For linear gradients (in degrees)
    centerX?: number // For radial gradients (0-1)
    centerY?: number // For radial gradients (0-1)
    radius?: number // For radial gradients (0-1)
  }
}

export interface CanvasSettings {
  width: number
  height: number
  backgroundColor: string
  backgroundImage?: string
}

export interface Layer {
  id: number
  type: LayerType
  name: string
  visible: boolean
  locked: boolean
  transform: Transform
  zIndex: number
  properties: LayerProperties
  plugins?: Record<string, any>
  // Sync status tracking for network failure handling
  syncStatus?: 'synced' | 'pending' | 'failed' | 'deleting'
  isTemporary?: boolean // True if layer has not been saved to backend yet
  lastSyncAttempt?: string // ISO timestamp of last sync attempt
  syncError?: string // Error message from last failed sync
}

export type LayerType = 'text' | 'image' | 'shape' | 'group' | 'video' | 'audio' | 'svg'

// Base interface for all layer properties
export interface BaseLayerProperties {
  [key: string]: any
}

// Text layer properties matching backend TextLayerProperties
export interface TextLayerProperties extends BaseLayerProperties {
  text: string
  fontFamily: string
  fontSize: number
  fontWeight: 'normal' | 'bold' | '100' | '200' | '300' | '400' | '500' | '600' | '700' | '800' | '900'
  fontStyle: 'normal' | 'italic' | 'oblique'
  textAlign: 'left' | 'center' | 'right' | 'justify'
  color: string
  lineHeight: number
  letterSpacing: number
  textDecoration: 'none' | 'underline' | 'overline' | 'line-through'
  // Auto-resize properties for dynamic text sizing
  autoResize: AutoResizeConfig
}

// Auto-resize configuration for text layers
export interface AutoResizeConfig {
  enabled: boolean
  mode: 'width' | 'height' | 'both' | 'none'
  maxWidth?: number
  maxHeight?: number
  minWidth?: number
  minHeight?: number
  padding?: {
    top: number
    right: number
    bottom: number
    left: number
  }
}

// Image shadow configuration matching backend structure
export interface ImageShadowConfig {
  enabled: boolean
  offsetX?: number
  offsetY?: number
  blur?: number
  color?: string
  opacity?: number
}

// Image layer properties matching backend ImageLayerProperties
export interface ImageLayerProperties extends BaseLayerProperties {
  src: string
  alt: string
  objectPosition: 'center' | 'top' | 'bottom' | 'left' | 'right' | 'top left' | 'top right' | 'bottom left' | 'bottom right'
  preserveAspectRatio: boolean
  quality: number
  // Scaling and dimension properties
  scaleMode?: 'fill' | 'fit' | 'stretch' // New: Controls how image scales within container
  originalWidth?: number // New: Store original image dimensions
  originalHeight?: number // New: Store original image dimensions
  explicitDimensions?: boolean // New: Whether dimensions were explicitly set by user
  // Filter properties (following backend order and ranges)
  blur: number // 0-50px
  brightness: number // 0-3
  contrast: number // 0-3
  saturation: number // 0-3
  hue: number // -360 to 360 degrees
  sepia: number // 0-1
  grayscale: number // 0-1
  invert: number // 0-1
  // SVG filter properties
  shadow: ImageShadowConfig
  // Additional frontend properties for UI convenience
  flipX: boolean
  flipY: boolean
}

// Shape fill configuration matching backend structure
export interface ShapeFillConfig {
  type: 'solid' | 'linear' | 'radial' | 'pattern'
  color?: string
  opacity?: number
  colors?: Array<{ color: string; stop: number; opacity?: number }>
  angle?: number
  centerX?: number
  centerY?: number
  radius?: number
  patternType?: 'dots' | 'stripes' | 'grid'
  size?: number
  backgroundColor?: string
  spacing?: number
}

// Shape effect configuration
export interface ShapeEffectConfig {
  enabled: boolean
  offsetX?: number
  offsetY?: number
  blur?: number
  color?: string
  opacity?: number
}

// Shape layer properties matching backend ShapeLayerProperties
export interface ShapeLayerProperties extends BaseLayerProperties {
  shapeType: 'rectangle' | 'circle' | 'ellipse' | 'triangle' | 'polygon' | 'star' | 'line' | 'arrow' | 'heart'
  fill: ShapeFillConfig
  stroke: string
  strokeWidth: number
  strokeOpacity: number
  strokeDashArray?: string
  strokeLineCap: 'butt' | 'round' | 'square'
  strokeLineJoin: 'miter' | 'round' | 'bevel'
  cornerRadius: number
  sides: number
  points: number
  innerRadius: number
  x1: number
  y1: number
  x2: number
  y2: number
  shadow?: ShapeEffectConfig
  glow?: ShapeEffectConfig
}

// Group layer properties matching backend GroupLayerRenderer
export interface GroupLayerProperties extends BaseLayerProperties {
  children?: Array<any>
  blendMode?: 'normal' | 'multiply' | 'screen' | 'overlay' | 'darken' | 'lighten' | 'color-dodge' | 'color-burn' | 'hard-light' | 'soft-light' | 'difference' | 'exclusion' | 'hue' | 'saturation' | 'color' | 'luminosity'
  isolation?: boolean
  clipPath?: {
    enabled: boolean
    type: 'rectangle' | 'circle' | 'ellipse' | 'polygon' | 'path'
    x?: number
    y?: number
    width?: number
    height?: number
    cx?: number
    cy?: number
    r?: number
    rx?: number
    ry?: number
    cornerRadius?: number
    points?: Array<{ x: number; y: number }>
    d?: string
  }
  mask?: {
    enabled: boolean
    type: 'gradient' | 'image' | 'shape'
    shapeType?: 'rectangle' | 'circle' | 'ellipse'
    src?: string
    gradient?: {
      type: 'linear' | 'radial'
      stops: Array<{ offset: string; color: string; opacity: number }>
    }
  }
}

// Video layer properties
export interface VideoLayerProperties extends BaseLayerProperties {
  src: string
  autoplay?: boolean
  loop?: boolean
  muted?: boolean
  controls?: boolean
}

// Audio layer properties
export interface AudioLayerProperties extends BaseLayerProperties {
  src: string
  autoplay?: boolean
  loop?: boolean
  muted?: boolean
  controls?: boolean
}

// SVG vector layer properties
export interface SVGLayerProperties extends BaseLayerProperties {
  src: string // URL or inline SVG content
  viewBox?: string // SVG viewBox attribute
  preserveAspectRatio?: string // SVG preserveAspectRatio attribute
  fillColors?: Record<string, string> // Map of element IDs/classes to fill colors for customization
  strokeColors?: Record<string, string> // Map of element IDs/classes to stroke colors
  strokeWidths?: Record<string, number> // Map of element IDs/classes to stroke widths
  originalWidth?: number // Original SVG width
  originalHeight?: number // Original SVG height
  // Parsed SVG elements for toolbar integration (populated by renderer)
  svgElements?: Array<{
    type: string
    id?: string
    className?: string
    originalFill?: string
    originalStroke?: string
    originalStrokeWidth?: number
  }>
}

// Union type for all layer properties
export type LayerProperties = 
  | TextLayerProperties
  | ImageLayerProperties 
  | ShapeLayerProperties
  | GroupLayerProperties
  | VideoLayerProperties
  | AudioLayerProperties
  | SVGLayerProperties
  | BaseLayerProperties

export interface ImageFilter {
  type: 'blur' | 'brightness' | 'contrast' | 'saturate' | 'grayscale' | 'sepia'
  value: number
}

export interface Template {
  id: string
  uuid: string
  name: string
  title: string // Display title for the template (usually same as name)
  category: string
  description?: string
  tags: string[]
  thumbnail: string
  thumbnailUrl: string
  previewUrl: string
  width: number
  height: number
  designData?: DesignData
  layers?: Layer[] // Template layers data
  isPremium: boolean
  isActive: boolean
  rating: number
  ratingCount: number
  usageCount: number
  createdAt: string
  updatedAt: string
}

// Detailed template response from backend (from getTemplate API call)
export interface DetailedTemplate {
  id: string
  uuid: string
  name: string
  description?: string
  category: string
  tags: string[]
  thumbnailUrl: string
  previewUrl: string
  width: number
  height: number
  canvasSettings: DesignData // Canvas configuration data
  layers: Layer[] // Template layers data
  isPremium: boolean
  isActive: boolean
  rating: number
  ratingCount: number
  usageCount: number
  createdBy?: {
    id: string
    username: string
  }
  createdAt: string
  updatedAt?: string
}

export interface MediaItem {
  id: string
  uuid: string
  name: string
  type: 'image' | 'video' | 'audio'
  mimeType: string
  size: number
  url: string
  thumbnailUrl?: string
  thumbnail?: string // Keep for backward compatibility
  width?: number
  height?: number
  duration?: number
  source: 'unsplash'| 'pexels'| 'iconfinder'| 'giphy'| 'upload'| 'generated'
  sourceId?: string
  metadata?: any
  tags: string[]
  attribution?: string
  license?: string
  isPremium: boolean
  isActive: boolean
  uploadedBy?: {
    id: string
    username: string
  }
  createdAt: string
  updatedAt?: string
}

export interface Project {
  id: string
  name: string
  description?: string
  tags: string[]
  isPublic: boolean
  thumbnail?: string
  designCount: number
  userId: string
  user?: {
    id: string
    name: string
    username: string
    avatar?: string
  }
  createdAt: string
  updatedAt?: string
}

export interface ExportJob {
  id: string
  designId: string
  designName: string
  designThumbnail?: string
  format: 'png' | 'jpg' | 'jpeg' | 'pdf' | 'svg' | 'mp4' | 'gif'
  status: 'pending' | 'processing' | 'completed' | 'failed' | 'cancelled'
  progress?: number
  options: ExportOptions
  downloadUrl?: string
  fileSize?: number
  errorMessage?: string
  createdAt: string
  updatedAt: string
  completedAt?: string
}

export interface ExportOptions {
  quality?: number
  width?: number
  height?: number
  scale?: number
  transparent?: boolean
  duration?: number // for video/gif exports
  fps?: number // for video/gif exports
}

// ============================================================================
// PAGINATION AND SEARCH TYPES
// ============================================================================

export interface PaginationParams {
  page?: number
  limit?: number
}

export interface SearchParams extends PaginationParams {
  search?: string
  sort_by?: string
  sort_order?: 'asc' | 'desc'
}

export interface DesignSearchParams extends SearchParams {
  project_id?: string
  sort_by?: 'name' | 'created_at' | 'updated_at'
}

export interface TemplateSearchParams extends SearchParams {
  category?: string
  is_premium?: boolean
  q?: string // Add search query parameter
  tags?: string
  sort_by?: 'created_at' | 'updated_at' | 'name' | 'popularity'
}

export interface TemplateCategoriesResponse{
  data: Array<{
    name: string
    slug: string
    title: string
  }>
}

export interface MediaSearchParams extends SearchParams {
  type?: 'image' | 'video' | 'audio'
  sort_by?: 'created_at' | 'updated_at' | 'name' | 'size'
}

export interface ExportJobSearchParams extends SearchParams {
  status?: 'pending' | 'processing' | 'completed' | 'failed' | 'cancelled'
  format?: string
  design_id?: string
  sort_by?: 'created_at' | 'updated_at' | 'status'
}

export interface StockMediaSearchParams extends PaginationParams {
  query?: string
  type?: 'image' | 'video' | 'shape' | 'icon'
  color?: string
  orientation?: 'landscape' | 'portrait' | 'square'
}

// ============================================================================
// UNIFIED SEARCH TYPES
// ============================================================================

export interface SearchResult {
  id: string
  type: 'design' | 'template' | 'media' | 'export'
  title: string
  description?: string
  thumbnail?: string
  url?: string
  author?: string
  created_at?: string // Optional for templates that may not have creation dates
  isPremium?: boolean
  exportStatus?: 'pending' | 'processing' | 'completed' | 'failed'
  hasAnimation?: boolean
  isVideo?: boolean
  duration?: number // in seconds for videos/animations
  width?: number
  height?: number
  stats?: {
    likes: number
    views: number
    downloads?: number
  }
}

export interface ContentFilter {
  type: string
  label: string
  icon: string
  count: number
}

export interface UnifiedSearchParams extends PaginationParams {
  q?: string
  type?: 'all' | 'design' | 'template' | 'media' | 'export'
  sort?: 'relevance' | 'newest' | 'popular' | 'name'
  filters?: string[]
}

// Specialized search response types to match backend DTOs
// Types for search results matching backend DTOs
export interface GlobalSearchItem {
  id: number
  name: string
  description?: string | null
  thumbnail?: string | null
  thumbnail_url?: string | null
  category?: string
  tags?: string[] | null
  is_premium?: boolean
  mime_type?: string
  size?: number
  url?: string
  created_at?: string | null
  updatedAt?: string
  type: string
}

export interface ProjectSearchItem {
  id: number
  name: string
  description: string | null
  thumbnail: string | null
  updatedAt: string
  type: string
}

export interface TemplateSearchItem {
  id: number
  uuid: string
  name: string
  description: string
  category: string
  tags: string[]
  thumbnailUrl: string
  previewUrl: string
  width: number
  height: number
  isPremium: boolean
  isActive: boolean
  rating: number
  ratingCount: number
  usageCount: number
  createdAt: string
  updatedAt: string
}

export interface MediaSearchItem {
  id: number
  name: string
  type: string
  mime_type: string
  size: number
  url: string
  thumbnail_url: string | null
  tags: string[] | null
  created_at: string | null
}

export interface GlobalSearchResponseData {
  results: GlobalSearchItem[]
  query: string
  pagination: {
    page: number
    limit: number
    total: number
    totalPages: number
  }
  message: string
}

export interface ProjectSearchResponseData {
  projects: ProjectSearchItem[]
  pagination: {
    page: number
    limit: number
    total: number
    totalPages: number
  }
  message: string
}

export interface TemplateSearchResponseData {
  templates: TemplateSearchItem[]
  pagination: {
    page: number
    limit: number
    total: number
    totalPages: number
  }
  message: string
}

export interface MediaSearchResponseData {
  media: MediaSearchItem[]
  pagination: {
    page: number
    limit: number
    total: number
    totalPages: number
  }
  message: string
}

export interface SearchSuggestionResponseData {
  suggestions: Array<{
    text: string
    type: string
  }>
  query: string
  message: string
}

// Unified search response types for different search endpoints
export interface GlobalSearchApiResponse {
  results: GlobalSearchItem[]
  query: string
  pagination: {
    page: number
    limit: number
    total: number
    totalPages: number
  }
  message: string
}

export interface ProjectSearchApiResponse {
  projects: ProjectSearchItem[]
  pagination: {
    page: number
    limit: number
    total: number
    totalPages: number
  }
  message: string
}

export interface TemplateSearchApiResponse {
  templates: TemplateSearchItem[]
  pagination: {
    page: number
    limit: number
    total: number
    totalPages: number
  }
  message: string
}

export interface MediaSearchApiResponse {
  media: MediaSearchItem[]
  pagination: {
    page: number
    limit: number
    total: number
    totalPages: number
  }
  message: string
}

export interface SearchSuggestionApiResponse {
  suggestions: Array<{
    text: string
    type: string
  }>
  query: string
  message: string
}

// Legacy unified response for backward compatibility
export interface SearchResponseData {
  results: SearchResult[]
  total: number
  pagination?: {
    page: number
    limit: number
    pages: number
    total: number
  }
}

export interface UnifiedSearchApiResponse extends BaseApiResponse {
  data: SearchResponseData
}

// ============================================================================
// ANALYTICS AND DASHBOARD TYPES
// ============================================================================

export interface DashboardStats {
  overview: {
    totalDesigns: number
    totalProjects: number
    totalExports: number
    completedExports: number
    storageUsed: number
    successRate: number
  }
  recentActivity: Array<{
    date: string
    designs_created: number
    exports_completed: number
    projects_created: number
  }>
}

export interface DesignAnalytics {
  views: number
  exports: number
  shares: number
  edit_time: number
  last_opened: string
}

export interface UserSubscription {
  plan: string
  isActive: boolean,
  usage: Record<string, number>,
  limits: Record<string, number>,
  features: Record<string, boolean>,
  planInfo: {
    name: string
    description: string
    price?: {
      monthly?: number
      yearly?: number
      currency?: string
    }
  }
}

export interface StorageUsage {
  used: number
  limit: number
  percentage: number
}

// ============================================================================
// COLLABORATION TYPES
// ============================================================================

export interface ShareDesignData {
  email?: string
  permissions: 'view' | 'edit'
  expires_at?: string
}

export interface ShareResponse {
  share_url: string
}

export interface Collaborator {
  user: User
  permissions: string
  joined_at: string
}

// ============================================================================
// AUTHENTICATION TYPES
// ============================================================================

export interface LoginCredentials {
  email: string
  password: string
}

export interface RegisterData {
  firstName: string
  lastName: string
  email: string
  password: string
  confirmPassword: string
}

export interface ResetPasswordData {
  token: string
  newPassword: string
}

export interface ChangePasswordData {
  currentPassword: string
  newPassword: string
}

// ============================================================================
// SPECIFIC API RESPONSE TYPES
// ============================================================================

/**
 * Design API Response Types - matching backend DesignResponseDTO structure
 */
export interface DesignApiResponse extends BaseApiResponse {
  data: {
    design: Design
  }
}

/**
 * Designs paginated response
 */
export interface DesignsApiResponse extends BaseApiResponse {
  data: Design[]
  pagination: {
    total: number
    page: number
    totalPages: number
  }
}

/**
 * Template API Response Types - matching backend TemplateResponseDTO structure
 */
export interface TemplateApiResponse extends BaseApiResponse {
  data: {
    template: Template
  }
}

/**
 * Detailed Template API Response - for single template with full data
 */
export interface DetailedTemplateApiResponse extends BaseApiResponse {
  data: {
    template: DetailedTemplate
  }
}

/**
 * Templates paginated response
 */
export interface TemplatesApiResponse extends BaseApiResponse {
  data: {
    templates: Template[]
    pagination: {
      total: number
      page: number
      totalPages: number
    }
  }
}

/**
 * Single Media API Response Types - matching backend MediaResponseDTO structure for single media
 */
export interface MediaApiResponse extends BaseApiResponse {
  data: {
    media: MediaItem
  }
}

/**
 * Media list paginated response (matches backend PaginatedResponseDTO structure)
 */
export interface MediaListApiResponse {
  data: MediaItem[]
  pagination: {
    page: number
    limit: number
    total: number
    totalPages: number
  }
  message: string
}

/**
 * Project API Response Types - matching backend ProjectResponseDTO structure
 */
export interface ProjectApiResponse extends BaseApiResponse {
  data: {
    project: Project
  }
}

/**
 * Projects paginated response
 */
export interface ProjectsApiResponse extends BaseApiResponse {
  data: {
    projects: Project[]
    pagination: {
      total: number
      page: number
      totalPages: number
    }
  }
}

/**
 * Export Job API Response Types - matching backend ExportJobResponseDTO structure
 */
export interface ExportJobApiResponse extends BaseApiResponse {
  data: {
    job: ExportJob
  }
}

/**
 * Export jobs paginated response
 */
export interface ExportJobsApiResponse extends BaseApiResponse {
  data: {
    jobs: ExportJob[]
    pagination: {
      total: number
      page: number
      totalPages: number
    }
  }
}

// ============================================================================
// PLUGIN TYPES
// ============================================================================

export interface Plugin {
  id: string
  uuid: string
  name: string
  description?: string
  identifier: string
  version: string
  status: 'pending' | 'approved' | 'rejected' | 'suspended'
  categories: string[]
  tags: string[]
  permissions: string[]
  manifest: Record<string, any>
  entryPoint: string
  iconUrl?: string
  bannerUrl?: string
  isPremium: boolean
  price?: number
  installCount: number
  rating: number
  ratingCount: number
  isActive: boolean
  userId: string
  user?: {
    id: string
    name: string
    username: string
    avatar?: string
  }
  reviewedBy?: string
  reviewedAt?: string
  reviewNotes?: string
  createdAt: string
  updatedAt: string
}

export interface CreatePluginData {
  name: string
  description: string
  categories: string[]
  version: string
  permissions: string[]
  manifest: Record<string, any>
}

export interface UpdatePluginData {
  name?: string
  description?: string
  categories?: string[]
  version?: string
  permissions?: string[]
  manifest?: Record<string, any>
  status?: string
}

export interface RejectPluginData {
  reason: string
}

export interface DuplicateLayerData {
  name?: string
  targetDesignId?: string
}

export interface MoveLayerData {
  direction?: 'up' | 'down' | 'top' | 'bottom'
  targetZIndex?: number
}

export interface BulkUpdateLayersData {
  layers: Array<{
    id: number
    updates: Record<string, any>
  }>
}

// Transform interface for layer positioning and scaling
export interface Transform {
  x?: number
  y?: number
  width?: number
  height?: number
  rotation?: number
  scaleX?: number
  scaleY?: number
  skewX?: number
  skewY?: number
  opacity?: number
}

// Enhanced Layer properties based on backend DTOs
export interface CreateLayerData {
  designId: string
  type: 'text' | 'image' | 'shape' | 'group' | 'video' | 'audio' | 'svg'
  name: string
  properties?: Record<string, any>
  transform: Transform
  zIndex?: number
  visible?: boolean
  locked?: boolean
  parentLayerId?: string
}

export interface UpdateLayerData {
  name?: string
  properties?: Record<string, any>
  transform?: Transform
  zIndex?: number
  visible?: boolean
  locked?: boolean
  parentLayerId?: string
}

// ============================================================================
// PLUGIN API RESPONSE TYPES
// ============================================================================

export interface PluginApiResponse extends BaseApiResponse {
  data: {
    plugin: Plugin
  }
}

export interface PluginsApiResponse extends BaseApiResponse {
  data: {
    plugins: Plugin[]
    pagination: {
      total: number
      page: number
      totalPages: number
    }
  }
}

export interface PluginCategoriesApiResponse extends BaseApiResponse {
  data: {
    categories: string[]
  }
}

// ============================================================================
// LAYER API RESPONSE TYPES  
// ============================================================================

export interface LayerApiResponse extends BaseApiResponse {
  data: {
    layer: Layer
  }
}

export interface LayersApiResponse extends BaseApiResponse {
  data: {
    layers: Layer[]
  }
}

// ============================================================================
// YOUTUBE VIDEO ANALYSIS TYPES
// ============================================================================

export interface YouTubeVideoInfo {
  videoId: string
  title: string
  description: string
  thumbnailUrl: string
  channelTitle: string
  publishedAt: string
  viewCount: number
  tags: string[]
}

export interface VideoAnalysisResult {
  videoInfo: YouTubeVideoInfo
  extractedFrames: VideoFrame[]
  transcript?: string
  keyMoments: KeyMoment[]
  suggestedDesigns: DesignSuggestion[]
  colorPalette: ColorPalette
  dominantThemes: string[]
}

export interface VideoFrame {
  timestamp: number
  imageUrl: string
  description?: string
  confidence: number
}

export interface KeyMoment {
  timestamp: number
  description: string
  importance: number
  frameUrl: string
}

export interface DesignSuggestion {
  id: string
  title: string
  description: string
  category: string
  thumbnailUrl: string
  designData: DesignData
  width: number
  height: number
  confidence: number
  sourceFrames: number[]
  tags: string[]
}

export interface ColorPalette {
  primary: string
  secondary: string
  accent: string
  neutral: string
  colors: string[]
}

export interface ThumbnailGenerationOptions {
  style: string
  maxThumbnails: number
  size: string
  customPrompt?: string
}

export interface GenerateDesignFromVideoRequest {
  videoUrl: string
  designTypes?: DesignType[]
  customPrompt?: string
  options?: ThumbnailGenerationOptions
}

export interface GenerateDesignFromVideoResponse {
  jobId: string
  status: 'processing' | 'completed' | 'failed'
  progress: number
  result?: VideoAnalysisResult
  errorMessage?: string
  estimatedTime?: number
}

export enum DesignType {
  SOCIAL_MEDIA = 'social_media',
  PRESENTATION = 'presentation',
  POSTER = 'poster',
  THUMBNAIL = 'thumbnail',
  BANNER = 'banner',
  INFOGRAPHIC = 'infographic'
}

// ============================================================================
// VIDEO ANALYSIS API RESPONSE TYPES
// ============================================================================

export interface VideoAnalysisApiResponse extends BaseApiResponse {
  data: {
    job: GenerateDesignFromVideoResponse
  }
}

export interface VideoAnalysisJobApiResponse extends BaseApiResponse {
  data: {
    job: GenerateDesignFromVideoResponse
  }
}

export interface VideoAnalysisJobsApiResponse extends BaseApiResponse {
  data: {
    jobs: GenerateDesignFromVideoResponse[]
    pagination: {
      total: number
      page: number
      totalPages: number
    }
  }
}

export interface ThumbnailGenerationRequest extends GenerateDesignFromVideoRequest {
  options: ThumbnailGenerationOptions & {
    includeTitle?: boolean
    includeChannelBranding?: boolean
  }
}

export interface ThumbnailDesign extends DesignSuggestion {
  style: string
  clickThroughRate?: number
  engagement?: number
}

// ============================================================================
// INTEGRATIONS API TYPES
// ============================================================================

export interface Integration {
  service: string
  isConfigured: boolean
  settings: Record<string, any>
  createdAt: {
    date: string
    timezone_type: number
    timezone: string
  }
  updatedAt: {
    date: string
    timezone_type: number
    timezone: string
  }
}

export interface SaveIntegrationData {
  serviceName: string
  credentials: Record<string, any>
}

export interface TestIntegrationData {
  serviceName: string
  credentials?: Record<string, any>
}

export interface TestIntegrationResult {
  success: boolean
  message: string
  data?: Record<string, any>
}

export interface PluginCommandRequest {
  pluginId: string
  command: string
  layerId: number
  parameters?: Record<string, any>
  options?: Record<string, any>
}

export interface PluginEvent{
  pluginId: string
  event: string
  options?: Record<string, any>
}



export interface PluginLayerUpdate{
  layerId: number
  plugins: Record<string, any>
  updates?: Partial<{
  properties: Record<string, any>
  transform: Transform
 }>
}


export interface PluginCommandResult {
  success: boolean
  result: any
  layer: {
    id: number
    plugins: Record<string, any>
  }
}

export interface InstalledPlugin {
  id: string
  name: string
  description: string
  icon: string
  version: string
  commands: string[]
  requirements: {
    integrations?: string[]
    layer_types?: string[]
    permissions?: string[]
  }
}

// ============================================================================
// REMOVEBG PLUGIN TYPES
// ============================================================================

/**
 * RemoveBG Plugin Data Structure
 * This represents what's stored in layer.plugins.removebg
 */
export interface RemoveBgPluginData {
  original_image?: {
    src: string
    properties: Record<string, any>
    stored_at: string
  }
  processed_images?: {
    removed_bg?: {
      src: string
      created_at: string
      parameters: Record<string, any>
      api_response_info: {
        detected_type?: string
        width?: string
        height?: string
        credits_charged?: string
      }
    }
  }
  current_state?: 'not_processed' | 'background_removed' | 'background_original'
  last_updated?: string
}

/**
 * RemoveBG Plugin Command Results (what the plugin methods return)
 */
export interface RemoveBgRemoveResult {
  success: boolean
  message: string
  processed_image: string
  credits_used: number
  can_restore: boolean
  cached?: boolean
}

export interface RemoveBgRestoreResult {
  success: boolean
  message: string
  restored_image: string
  can_remove: boolean
}

export interface RemoveBgPreviewResult {
  success: boolean
  preview_url?: string
  cached: boolean
  message?: string
}

export interface RemoveBgStatusResult {
  status: 'not_processed' | 'background_removed' | 'background_original' | 'unknown'
  can_remove: boolean
  can_restore: boolean
  processed_at?: string
  available_images?: string[]
}

export interface RemoveBgClearCacheResult {
  success: boolean
  message: string
  cleared: boolean
}

/**
 * RemoveBg Plugin Service Response Structure
 */
export interface RemoveBgPluginServiceResponse {
  success: boolean
  result: RemoveBgRemoveResult | RemoveBgRestoreResult | RemoveBgPreviewResult | RemoveBgStatusResult | RemoveBgClearCacheResult
  layer: {
    id: number
    plugins: Record<string, any>
  }
}

/**
 * Complete API Response (wrapped in standard API response format)
 */
export interface RemoveBgApiResponse extends ApiResponse<RemoveBgPluginServiceResponse> {
  // Inherits from ApiResponse<T> which provides:
  // data: PluginServiceResponse
  // success: boolean
  // message: string
  // timestamp: string
}

/**
 * YouTube video info from analyze_video command (actual backend response structure)
 */
export interface YouTubeVideoAnalysisResult {
  video_id: string
  title: string
  author_name: string
  author_url: string
  thumbnail_url: string
  thumbnail_width: number
  thumbnail_height: number
  view_count: number | null
  description: string | null
  fetched_at: string
}

/**
 * YouTube Thumbnail Plugin - Thumbnail Generation Response
 */
export interface YouTubeThumbnailGenerationResult {
  success: true
  thumbnail_variations: YouTubeThumbnailVariation[]
  video_info: YouTubeVideoAnalysisResult
  generation_method: 'replicate' | 'openai'
  generation_parameters: {
    custom_prompt?: string
    thumbnail_count: number
    style: string
    generated_at: string
  }
}

/**
 * Individual thumbnail variation from AI generation (actual backend response)
 */
export interface YouTubeThumbnailVariation {
  id: string
  title: string
  prompt: string
  image_url: string
  local_path: string
  preview_url: string
  preview_path: string
  thumbnail_url: string
  thumbnail_path: string
  original_size: string
  youtube_size: string
  style: string
  generation_method?: 'replicate' | 'openai'
  created_at: string
}

/**
 * Simplified thumbnail data for UI display (no design data needed)
 */
export interface YouTubeThumbnailDisplay {
  id: string
  title: string
  previewUrl: string  // For displaying in the UI
  fullImageUrl: string  // For download/full view
  thumbnailUrl: string  // Small thumbnail
  style: string
  generationMethod?: 'replicate' | 'openai'
  createdAt: string
}

/**
 * Generic Plugin Service Response structure
 */
export interface PluginServiceResponse {
  success: boolean
  result: any // Can be any plugin result
  layer?: {
    id: number
    plugins: Record<string, any>
  }
}

// ============================================================================
// ADMIN API TYPES
// ============================================================================

/**
 * Admin User data structure for admin interface
 */
export interface AdminUser {
  id: number
  uuid: string
  email: string
  firstName: string | null
  lastName: string | null
  username: string | null
  roles: string[]
  isActive: boolean
  emailVerified: boolean
  plan: string | null
  createdAt: string
  updatedAt: string | null
  lastLoginAt: string | null
  failedLoginAttempts: number
  isLocked: boolean
}

/**
 * Detailed admin user data structure with additional information
 */
export interface AdminUserDetails extends AdminUser {
  jobTitle: string | null
  company: string | null
  website: string | null
  portfolio: string | null
  bio: string | null
  socialLinks: Record<string, string> | null
  timezone: string | null
  language: string | null
  avatar: string | null
  settings: Record<string, any> | null
  counts: {
    projects: number
    mediaFiles: number
    exportJobs: number
    subscriptions: number
  }
}

/**
 * Admin users list response structure
 */
export interface AdminUsersListResponse {
  users: AdminUser[]
  pagination: {
    current_page: number
    total_pages: number
    total_items: number
    items_per_page: number
    has_next: boolean
    has_prev: boolean
  }
}

/**
 * Admin platform statistics structure
 */
export interface AdminPlatformStats {
  users: {
    total: number
    active: number
    verified: number
    admins: number
  }
  recent_registrations: number
  designs: {
    total: number
    public: number
    private: number
    templates: number
  }
  storage: {
    used: number
    total: number
    uploads_count: number
  }
  exports: {
    total: number
    successful: number
    failed: number
    in_progress: number
  }
}

/**
 * Admin subscription plan structure
 */
export interface AdminSubscriptionPlan {
  id: number
  code: string
  name: string
  description: string | null
  monthly_price: string
  yearly_price: string
  currency: string
  is_active: boolean
  is_default: boolean
  sort_order: number
  limits: Record<string, number>
  features: Record<string, boolean>
  created_at: string
  updated_at: string | null
  subscriber_count?: number // Optional since it might not always be included
}

/**
 * Admin analytics metrics structure
 */
export interface AdminAnalyticsMetrics {
  totalUsers: number
  userGrowth: number
  activeSubscriptions: number
  subscriptionGrowth: number
  monthlyRevenue: number
  revenueGrowth: number
  projectsCreated: number
  projectGrowth: number
}

/**
 * Admin analytics time series data point
 */
export interface AdminAnalyticsTimeSeriesDataPoint {
  date: string
  count: number
}

/**
 * Admin analytics time series data structure
 */
export interface AdminAnalyticsTimeSeriesData {
  userGrowth: AdminAnalyticsTimeSeriesDataPoint[]
  contentCreation: AdminAnalyticsTimeSeriesDataPoint[]
  exportActivity: AdminAnalyticsTimeSeriesDataPoint[]
  revenue: AdminAnalyticsTimeSeriesDataPoint[]
}

/**
 * Admin analytics top plan structure
 */
export interface AdminAnalyticsTopPlan {
  name: string
  subscribers: number
  revenue: number
  percentage: number
}

/**
 * Admin analytics activity metrics structure
 */
export interface AdminAnalyticsActivityMetrics {
  dailyActiveUsers: number
  weeklyActiveUsers: number
  avgSessionDuration: number
  projectsPerUser: number
  exportsPerUser: number
}

/**
 * Admin analytics system metrics structure
 */
export interface AdminAnalyticsSystemMetrics {
  apiResponseTime: number
  errorRate: number
  storageUsage: number
  uptime: number
}

/**
 * Admin analytics export data structure
 */
export interface AdminAnalyticsExportData {
  total_exports: number
  exports_per_user: number
  popular_formats: Array<{
    format: string
    count: number
  }>
  success_rate: number
}

/**
 * Admin analytics content trends structure
 */
export interface AdminAnalyticsContentTrend {
  category: string
  usage_count: number
}

/**
 * Comprehensive admin analytics response structure
 */
export interface AdminAnalyticsData {
  metrics: AdminAnalyticsMetrics
  timeSeriesData: AdminAnalyticsTimeSeriesData
  topPlans: AdminAnalyticsTopPlan[]
  activityMetrics: AdminAnalyticsActivityMetrics
  systemMetrics: AdminAnalyticsSystemMetrics
  exportAnalytics: AdminAnalyticsExportData
  contentTrends: AdminAnalyticsContentTrend[]
}

/**
 * Admin API Response Types
 */
export interface AdminUsersApiResponse extends ApiResponse<AdminUsersListResponse> {}
export interface AdminUserDetailsApiResponse extends ApiResponse<{ user: AdminUserDetails }> {}
export interface AdminUserStatusApiResponse extends ApiResponse<{ user: AdminUser }> {}
export interface AdminUserRolesApiResponse extends ApiResponse<{ user: AdminUser }> {}
export interface AdminUserPlanApiResponse extends ApiResponse<{ user: AdminUser }> {}
export interface AdminBulkPlanApiResponse extends ApiResponse<{ users: AdminUser[] }> {}
export interface AdminPlatformStatsApiResponse extends ApiResponse<AdminPlatformStats> {}
export interface AdminPlansApiResponse extends ApiResponse<{ plans: AdminSubscriptionPlan[] }> {}
export interface AdminPlanApiResponse extends ApiResponse<{ plan: AdminSubscriptionPlan }> {}
export interface AdminAnalyticsApiResponse extends ApiResponse<AdminAnalyticsData> {}

/**
 * Admin API Request Types
 */
export interface AdminUpdateUserStatusRequest {
  active: boolean
}

export interface AdminUpdateUserRolesRequest {
  roles: string[]
}

export interface AdminAssignPlanRequest {
  plan_code: string
}

export interface AdminBulkAssignPlanRequest {
  user_ids: number[]
  plan_code: string
}

export interface AdminCreatePlanRequest {
  code: string
  name: string
  description?: string
  monthly_price: string
  yearly_price: string
  currency: string
  is_active?: boolean
  is_default?: boolean
  sort_order?: number
  limits?: Record<string, number>
  features?: Record<string, boolean>
}

export interface AdminUpdatePlanRequest {
  code?: string
  name?: string
  description?: string
  monthly_price?: string
  yearly_price?: string
  currency?: string
  is_active?: boolean
  is_default?: boolean
  sort_order?: number
  limits?: Record<string, number>
  features?: Record<string, boolean>
}

export interface AdminAnalyticsParams {
  startDate?: string
  endDate?: string
  granularity?: 'day' | 'week' | 'month'
}

export interface AdminUsersParams {
  page?: number
  limit?: number
  search?: string
  status?: string
  role?: string
}
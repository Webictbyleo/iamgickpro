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
 * Authentication response structure
 */
export interface AuthApiResponse extends BaseApiResponse {
  token: string
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
  socialLinks?: {
    twitter?: string
    linkedin?: string
    dribbble?: string
    behance?: string
  }
  createdAt: string
  updatedAt?: string
  lastLoginAt?: string
}

export interface Design {
  id: string
  name: string
  description?: string
  designData: DesignData
  thumbnail?: string
  width: number
  height: number
  userId: string
  projectId?: string
  isPublic: boolean
  createdAt: string
  updatedAt: string
}

export interface DesignData {
  version: string
  layers: Layer[]
  canvas: CanvasSettings
}

export interface CanvasSettings {
  width: number
  height: number
  backgroundColor: string
  backgroundImage?: string
}

export interface Layer {
  id: string
  type: LayerType
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
  properties: LayerProperties
}

export type LayerType = 'text' | 'image' | 'shape' | 'group'

export interface LayerProperties {
  // Text layer properties
  text?: string
  fontSize?: number
  fontFamily?: string
  fontWeight?: string | number
  fontStyle?: string
  textAlign?: string
  color?: string
  lineHeight?: number
  letterSpacing?: number
  
  // Image layer properties
  src?: string
  filters?: ImageFilter[]
  
  // Shape layer properties
  fill?: string
  stroke?: string
  strokeWidth?: number
  cornerRadius?: number
  
  // Group layer properties
  children?: Layer[]
}

export interface ImageFilter {
  type: 'blur' | 'brightness' | 'contrast' | 'saturate' | 'grayscale' | 'sepia'
  value: number
}

export interface Template {
  id: string
  uuid: string
  name: string
  category: string
  description?: string
  tags: string[]
  thumbnail: string
  thumbnailUrl: string
  previewUrl: string
  width: number
  height: number
  designData?: DesignData
  isPremium: boolean
  isActive: boolean
  rating: number
  ratingCount: number
  usageCount: number
  createdAt: string
  updatedAt: string
}

export interface MediaItem {
  id: string
  name: string
  type: 'image' | 'video' | 'audio'
  url: string
  thumbnail?: string
  size: number
  width?: number
  height?: number
  tags: string[]
  isPremium: boolean
  source: 'upload' | 'stock'
  createdAt: string
  updatedAt: string
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
  sort_by?: 'created_at' | 'updated_at' | 'name' | 'popularity'
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
  category?: string
  color?: string
  orientation?: 'landscape' | 'portrait' | 'square'
}

// ============================================================================
// ANALYTICS AND DASHBOARD TYPES
// ============================================================================

export interface DashboardStats {
  total_designs: number
  total_templates_used: number
  active_projects: number
  storage_used: number
  export_count: number
  monthly_growth: {
    designs: number
    exports: number
    templates: number
  }
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
  status: string
  expires_at: string
  features: string[]
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
  email: string
  password: string
  confirmPassword: string
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
  data: {
    designs: Design[]
    pagination: {
      total: number
      page: number
      totalPages: number
    }
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
 * Media API Response Types - matching backend MediaResponseDTO structure
 */
export interface MediaApiResponse extends BaseApiResponse {
  data: {
    media: MediaItem
  }
}

/**
 * Media list paginated response
 */
export interface MediaListApiResponse extends BaseApiResponse {
  data: {
    media: MediaItem[]
    pagination: {
      total: number
      page: number
      totalPages: number
    }
  }
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
  type: 'text' | 'image' | 'shape' | 'group' | 'video' | 'audio'
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



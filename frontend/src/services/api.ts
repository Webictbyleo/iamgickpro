// filepath: /var/www/html/iamgickpro/frontend/src/services/api.ts
import { api } from '../utils/api'
import type { 
  Design, 
  Template, 
  MediaItem, 
  User, 
  ExportJob, 
  ExportOptions, 
  Project,
  Layer,
  Plugin,
  ApiResponse,
  PaginatedApiResponse,
  AuthApiResponse,
  DashboardStats,
  DesignAnalytics,
  UserSubscription,
  StorageUsage,
  ShareDesignData,
  ShareResponse,
  Collaborator,
  LoginCredentials,
  RegisterData,
  ResetPasswordData,
  ChangePasswordData,
  DesignSearchParams,
  TemplateSearchParams,
  MediaSearchParams,
  ExportJobSearchParams,
  StockMediaSearchParams,
  SearchResult,
  ContentFilter,
  UnifiedSearchParams,
  SearchResponseData,
  UnifiedSearchApiResponse,
  GlobalSearchApiResponse,
  ProjectSearchApiResponse,
  TemplateSearchApiResponse,
  MediaSearchApiResponse,
  SearchSuggestionApiResponse,
  DesignsApiResponse,
  TemplatesApiResponse,
  MediaApiResponse,
  MediaListApiResponse,
  ProjectsApiResponse,
  ExportJobsApiResponse,
  CreateLayerData,
  UpdateLayerData,
  DuplicateLayerData,
  MoveLayerData,
  BulkUpdateLayersData,
  CreatePluginData,
  UpdatePluginData,
  RejectPluginData,
  LayerApiResponse,
  LayersApiResponse,
  PluginApiResponse,
  PluginsApiResponse,
  PluginCategoriesApiResponse,
  YouTubeVideoInfo,
  GenerateDesignFromVideoRequest,
  VideoAnalysisApiResponse,
  VideoAnalysisJobApiResponse,
  VideoAnalysisJobsApiResponse,
  UpdatedUserApiResponse,
  TemplateCategoriesResponse,
  Integration,
  SaveIntegrationData,
  TestIntegrationData,
  TestIntegrationResult
} from '@/types'

// Authentication API - aligned with AuthController
export const authAPI = {
  // POST /auth/register
  register: (data: RegisterData) => 
    api.post<AuthApiResponse>('/auth/register', data),

  // POST /auth/login
  login: (credentials: LoginCredentials) =>
    api.post<AuthApiResponse>('/auth/login', credentials),

  // GET /auth/me
  getCurrentUser: () => api.get<{user:User}>('/auth/me'),

  // PUT /auth/change-password
  changePassword: (data: ChangePasswordData) =>
    api.put<ApiResponse<{ message: string }>>('/auth/change-password', data),

  // POST /auth/logout
  logout: () => api.post<ApiResponse<{ message: string }>>('/auth/logout'),

  // Additional methods for compatibility
  refreshToken: () => api.post<ApiResponse<{ token: string }>>('/auth/refresh'),
  forgotPassword: (email: string) =>
    api.post<ApiResponse<{ message: string }>>('/auth/forgot-password', { email }),
  resetPassword: (data: ResetPasswordData) => 
    api.post<ApiResponse<{ message: string }>>('/auth/reset-password', data),
  verifyEmail: (token: string) =>
    api.post<ApiResponse<{ message: string }>>('/auth/verify-email', { token }),
  resendVerification: (email: string) =>
    api.post<ApiResponse<{ message: string }>>('/auth/resend-verification', { email }),
}

// Design API - aligned with DesignController (8 endpoints)
export const designAPI = {
  // GET /designs
  getDesigns: (params?: DesignSearchParams) => 
    api.get<DesignsApiResponse>('/designs', { params }),

  // POST /designs
  createDesign: (data: {
    name: string
    width: number
    height: number
    projectId?: number
    description?: string
    data?: any
  }) => api.post<ApiResponse<Design>>('/designs', data),

  // GET /designs/{id}
  getDesign: (id: string) => api.get<ApiResponse<Design>>(`/designs/${id}`),

  // PUT /designs/{id}
  updateDesign: (id: string, data: {
    name?: string
    width?: number
    height?: number
    data?: any
    description?: string
    projectId?: number
  }) => api.put<ApiResponse<Design>>(`/designs/${id}`, data),

  // DELETE /designs/{id}
  deleteDesign: (id: string) => api.delete<ApiResponse<{ message: string }>>(`/designs/${id}`),

  // POST /designs/{id}/duplicate
  duplicateDesign: (id: string, data?: {
    name?: string
    projectId?: number
  }) => api.post<ApiResponse<Design>>(`/designs/${id}/duplicate`, data),

  // PUT /designs/{id}/thumbnail
  updateThumbnail: (id: string, data: {
    thumbnail: string
    format?: string
  }) => api.put<ApiResponse<Design>>(`/designs/${id}/thumbnail`, data),

  // GET /designs/search
  searchDesigns: (params: {
    q: string
    page?: number
    limit?: number
    project_id?: string
    sort?: string
    order?: string
  }) => api.get<DesignsApiResponse>('/designs/search', { params }),

  // Additional methods for compatibility
  exportDesign: (id: string, format: 'png' | 'jpg' | 'jpeg' | 'pdf' | 'svg') =>
    api.post(`/designs/${id}/export`, { format }, { responseType: 'blob' }),
  getRecentDesigns: (limit = 8) => 
    api.get<ApiResponse<Design[]>>('/designs/recent', { params: { limit } }),
}

// Export Jobs API - aligned with ExportJobController (10 endpoints)
export const exportAPI = {
  // GET /export-jobs
  getExportJobs: (params?: ExportJobSearchParams) => 
    api.get<ExportJobsApiResponse>('/export-jobs', { params }),

  // POST /export-jobs
  createExportJob: (data: {
    designId: number
    format?: 'png' | 'jpeg' | 'svg' | 'pdf' | 'mp4' | 'gif'
    quality?: 'low' | 'medium' | 'high' | 'ultra'
    width?: number
    height?: number
    scale?: number
    transparent?: boolean
    backgroundColor?: string
    animationSettings?: any[]
  }) => api.post<ApiResponse<ExportJob>>('/export-jobs', data),

  // GET /export-jobs/queue-status
  getQueueStatus: () => api.get<ApiResponse<{
    pending: number
    processing: number
    completed: number
    failed: number
  }>>('/export-jobs/queue-status'),

  // GET /export-jobs/stats
  getStats: () => api.get<ApiResponse<{
    total: number
    byStatus: Record<string, number>
    byFormat: Record<string, number>
    successRate: number
  }>>('/export-jobs/stats'),

  // GET /export-jobs/{id}
  getExportJob: (id: string) => api.get<ApiResponse<ExportJob>>(`/export-jobs/${id}`),

  // PUT /export-jobs/{id} - Not allowed (returns error)
  updateExportJob: (id: string, data: any) => 
    api.put<ApiResponse<{ message: string }>>(`/export-jobs/${id}`, data),

  // DELETE /export-jobs/{id}
  deleteExportJob: (id: string) => api.delete<ApiResponse<{ message: string }>>(`/export-jobs/${id}`),

  // POST /export-jobs/{id}/cancel
  cancelExportJob: (id: string) => api.post<ApiResponse<ExportJob>>(`/export-jobs/${id}/cancel`),

  // GET /export-jobs/{id}/download
  downloadExport: (id: string) => 
    api.get(`/export-jobs/${id}/download`, { responseType: 'blob' }),

  // POST /export-jobs/{id}/retry
  retryExportJob: (id: string) => api.post<ApiResponse<ExportJob>>(`/export-jobs/${id}/retry`),
}

// Layer API - aligned with LayerController (7 endpoints)
export const layerAPI = {
  // POST /layers
  createLayer: (data: CreateLayerData) => 
    api.post<LayerApiResponse>('/layers', data),

  // PUT /layers/bulk-update
  bulkUpdateLayers: (data: BulkUpdateLayersData) => 
    api.put<LayersApiResponse>('/layers/bulk-update', data),

  // GET /layers/{id}
  getLayer: (id: number) => api.get<LayerApiResponse>(`/layers/${id}`),

  // PUT /layers/{id}
  updateLayer: (id: number, data: UpdateLayerData) => 
    api.put<LayerApiResponse>(`/layers/${id}`, data),

  // DELETE /layers/{id}
  deleteLayer: (id: number) => api.delete<ApiResponse<{ message: string }>>(`/layers/${id}`),

  // POST /layers/{id}/duplicate
  duplicateLayer: (id: number, data?: DuplicateLayerData) => 
    api.post<LayerApiResponse>(`/layers/${id}/duplicate`, data),

  // PUT /layers/{id}/move
  moveLayer: (id: number, data: MoveLayerData) => 
    api.put<LayerApiResponse>(`/layers/${id}/move`, data),
}

// Media API - aligned with MediaController (9 endpoints)
export const mediaAPI = {
  // GET /media
  getMedia: (params?: MediaSearchParams) => 
    api.get<MediaListApiResponse>('/media', { params }),

  // POST /media/upload
  uploadMedia: (file: File, data?: {
    name?: string
  }) => {
    const formData = new FormData()
    formData.append('file', file)
    if (data?.name) formData.append('name', data.name)
    
    // Don't set Content-Type header - let browser set it automatically with boundary
    return api.post<MediaApiResponse>('/media/upload', formData)
  },

  // DELETE /media/bulk/delete
  bulkDeleteMedia: (data: {
    mediaIds: string[]
  }) => api.delete<ApiResponse<{ message: string }>>('/media/bulk/delete', { data }),

  // POST /media/duplicate/{uuid}
  duplicateMedia: (uuid: string, data?: {
    name?: string
    folder?: string
  }) => api.post<MediaApiResponse>(`/media/duplicate/${uuid}`, data),

  // GET /media/search
  searchMedia: (params: {
    q: string
    type?: string
    format?: string
    page?: number
    limit?: number
  }) => api.get<MediaListApiResponse>('/media/search', { params }),

  // GET /media/stock/search
  searchStockMedia: (params: StockMediaSearchParams) => 
    api.get<MediaListApiResponse>('/media/stock/search', { params }),

  // GET /media/{uuid}
  getMediaItem: (uuid: string) => api.get<MediaApiResponse>(`/media/${uuid}`),

  // PUT /media/{uuid}
  updateMedia: (uuid: string, data: {
    name?: string
    description?: string
    tags?: string[]
    isActive?: boolean
  }) => api.put<MediaApiResponse>(`/media/${uuid}`, data),

  // DELETE /media/{uuid}
  deleteMedia: (uuid: string) => api.delete<ApiResponse<{ message: string }>>(`/media/${uuid}`),
}

// Plugin API - aligned with PluginController (12 endpoints)
export const pluginAPI = {
  // GET /plugins
  getPlugins: (params?: {
    page?: number
    limit?: number
    category?: string
    search?: string
    status?: string
  }) => api.get<PluginsApiResponse>('/plugins', { params }),

  // POST /plugins
  createPlugin: (data: CreatePluginData) => 
    api.post<PluginApiResponse>('/plugins', data),

  // GET /plugins/categories
  getCategories: () => api.get<PluginCategoriesApiResponse>('/plugins/categories'),

  // GET /plugins/my-plugins
  getMyPlugins: (params?: {
    page?: number
    limit?: number
  }) => api.get<PluginsApiResponse>('/plugins/my-plugins', { params }),

  // GET /plugins/{id}
  getPlugin: (id: string) => api.get<PluginApiResponse>(`/plugins/${id}`),

  // PUT /plugins/{id}
  updatePlugin: (id: string, data: UpdatePluginData) => 
    api.put<PluginApiResponse>(`/plugins/${id}`, data),

  // DELETE /plugins/{id}
  deletePlugin: (id: string) => api.delete<ApiResponse<{ message: string }>>(`/plugins/${id}`),

  // POST /plugins/{id}/approve
  approvePlugin: (id: string, data: {
    notes?: string
  }) => api.post<PluginApiResponse>(`/plugins/${id}/approve`, data),

  // POST /plugins/{id}/install
  installPlugin: (id: string) => api.post<ApiResponse<{ message: string }>>(`/plugins/${id}/install`),

  // POST /plugins/{id}/reject
  rejectPlugin: (id: string, data: RejectPluginData) => 
    api.post<ApiResponse<{ message: string }>>(`/plugins/${id}/reject`, data),

  // POST /plugins/{id}/uninstall
  uninstallPlugin: (id: string) => api.post<ApiResponse<{ message: string }>>(`/plugins/${id}/uninstall`),

  // POST /plugins/{id}/upload-file
  uploadPluginFile: (id: string, file: File) => {
    const formData = new FormData()
    formData.append('file', file)
    
    return api.post<ApiResponse<{ fileUrl: string }>>(`/plugins/${id}/upload-file`, formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    })
  },

  // POST /plugins/execute-command - Execute plugin command
  executeCommand: (data: {
    pluginId: string
    command: string
    layerId: number
    parameters?: Record<string, any>
  }) => api.post<ApiResponse<{
    layer: {
      id: number
      plugins: Record<string, any>
    }
    result?: any
  }>>('/plugins/execute-command', data),

  // GET /plugins/available - Get available plugins for user
  getAvailablePlugins: () => api.get<ApiResponse<any[]>>('/plugins/available'),
}

// Project API - aligned with ProjectController (8 endpoints)
export const projectAPI = {
  // GET /projects
  getProjects: (params?: {
    page?: number
    limit?: number
    search?: string
    tags?: string
    status?: string
    sort?: string
    order?: string
  }) => api.get<ProjectsApiResponse>('/projects', { params }),

  // POST /projects
  createProject: (data: {
    name: string
    description?: string
    isPublic?: boolean
    settings?: {
      canvasWidth?: number
      canvasHeight?: number
      backgroundColor?: string
      orientation?: string
      units?: string
      dpi?: number
      gridVisible?: boolean
      rulersVisible?: boolean
      guidesVisible?: boolean
      snapToGrid?: boolean
      snapToGuides?: boolean
      snapToObjects?: boolean
    }
    tags?: string[]
    thumbnail?: string
  }) => api.post<ApiResponse<Project>>('/projects', data),

  // GET /projects/public
  getPublicProjects: (params?: {
    page?: number
    limit?: number
    search?: string
    tags?: string
    sort?: string
    order?: string
  }) => api.get<ProjectsApiResponse>('/projects/public', { params }),

  // GET /projects/{id}
  getProject: (id: string) => api.get<ApiResponse<Project>>(`/projects/${id}`),

  // PUT /projects/{id}
  updateProject: (id: string, data: {
    name?: string
    description?: string
    isPublic?: boolean
    settings?: any
    tags?: string[]
    thumbnail?: string
  }) => api.put<ApiResponse<Project>>(`/projects/${id}`, data),

  // DELETE /projects/{id}
  deleteProject: (id: string) => api.delete<ApiResponse<{ message: string }>>(`/projects/${id}`),

  // POST /projects/{id}/duplicate
  duplicateProject: (id: string, data?: {
    name?: string
    includeDesigns?: boolean
  }) => api.post<ApiResponse<Project>>(`/projects/${id}/duplicate`, data),

  // POST /projects/{id}/share
  shareProject: (id: string, data: {
    isPublic?: boolean
    shareUrl?: string
    permissions?: string[]
  }) => api.post<ApiResponse<{ shareUrl: string }>>(`/projects/${id}/share`, data),
}

// Template API - aligned with TemplateController (6 endpoints)
export const templateAPI = {
  // GET /templates
  getTemplates: (params?: TemplateSearchParams) => 
    api.get<TemplatesApiResponse>('/templates', { params }),

  // POST /templates
  createTemplate: (data: {
    name: string
    description?: string
    category: string
    designId: number
    isPublic?: boolean
    tags?: string[]
    thumbnail?: string
    previewImages?: string[]
  }) => api.post<ApiResponse<Template>>('/templates', data),

  // GET /templates/categories
  getCategories: () => api.get<TemplateCategoriesResponse>('/templates/categories'),

  // GET /templates/search
  searchTemplates: (params: TemplateSearchParams) => 
    api.get<TemplatesApiResponse>('/templates/search', { params }),

  // GET /templates/{uuid}
  getTemplate: (uuid: string) => api.get<ApiResponse<Template>>(`/templates/${uuid}`),

  // POST /templates/{uuid}/use
  useTemplate: (uuid: string, data?: {
    name?: string
    projectId?: number
  }) => api.post<ApiResponse<Design>>(`/templates/${uuid}/use`, data),
}

// User API - aligned with UserController (8 endpoints)
export const userAPI = {
  // POST /user/avatar
  uploadAvatar: (file: File) => {
    const formData = new FormData()
    formData.append('avatar', file)
    return api.post<ApiResponse<{ avatar: string }>>('/user/avatar', formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    })
  },

  // PUT /user/password
  changePassword: (data: ChangePasswordData) =>
    api.put<ApiResponse<{ message: string }>>('/user/password', data),

  // GET /user/profile
  getProfile: () => api.get<ApiResponse<User>>('/user/profile'),

  // PUT /user/profile
  updateProfile: (data: {
    firstName?: string
    lastName?: string
    email?: string
    username?: string
    bio?: string
    website?: string
    location?: string
    timezone?: string
    language?: string
    settings?: any
  }) => api.put<UpdatedUserApiResponse>('/user/profile', data),

  // DELETE /user/settings/privacy/delete
  deleteAccount: () => api.delete<ApiResponse<{ message: string }>>('/user/settings/privacy/delete'),

  // POST /user/settings/privacy/download
  downloadData: () => api.post('/user/settings/privacy/download', {}, { responseType: 'blob' }),

  // POST /user/settings/privacy/export
  exportData: (data: {
    format: 'json' | 'csv' | 'xml'
    includeDesigns?: boolean
    includeMedia?: boolean
    includeProjects?: boolean
  }) => api.post<ApiResponse<{ downloadUrl: string }>>('/user/settings/privacy/export', data),

  // GET /user/subscription
  getSubscription: () => api.get<ApiResponse<UserSubscription>>('/user/subscription'),
}

// ============================================================================
// UNIFIED SEARCH API
// ============================================================================

// Unified Search API - provides global search across all content types
export const searchAPI = {
  // GET /search - Global search across designs, templates, media, and exports
  unifiedSearch: (params: UnifiedSearchParams) => 
    api.get<GlobalSearchApiResponse>('/search', { params }),

  // GET /search/templates - Search specifically for templates
  searchTemplates: (params: {
    q?: string
    category?: string
    tags?: string
    page?: number
    limit?: number
  }) => api.get<TemplateSearchApiResponse>('/search/templates', { params }),

  // GET /search/media - Search specifically for media files
  searchMedia: (params: {
    q?: string
    type?: 'image' | 'video' | 'audio'
    page?: number
    limit?: number
  }) => api.get<MediaSearchApiResponse>('/search/media', { params }),

  // GET /search/projects - Search specifically for user projects
  searchProjects: (params: {
    q?: string
    page?: number
    limit?: number
  }) => api.get<ProjectSearchApiResponse>('/search/projects', { params }),

  // GET /search/suggestions - Get search suggestions
  getSearchSuggestions: (params: {
    q?: string
    limit?: number
  }) => api.get<SearchSuggestionApiResponse>('/search/suggestions', { params }),
}

// ============================================================================
// ANALYTICS API
// ============================================================================

// Analytics API - provides analytics and dashboard statistics
export const analyticsAPI = {
  // GET /analytics/dashboard - Get dashboard stats
  getDashboardStats: () => api.get<ApiResponse<DashboardStats>>('/analytics/dashboard'),

  // GET /analytics/designs/{id} - Get design analytics
  getDesignAnalytics: (designId: string) => 
    api.get<ApiResponse<DesignAnalytics>>(`/analytics/designs/${designId}`),
}

// ============================================================================
// COLLABORATION API
// ============================================================================

// Collaboration API - provides sharing and collaboration features
export const collaborationAPI = {
  // POST /designs/{id}/share - Share design
  shareDesign: (designId: string, data: ShareDesignData) => 
    api.post<ApiResponse<ShareResponse>>(`/designs/${designId}/share`, data),

  // GET /designs/shared - Get shared designs
  getSharedDesigns: () => api.get<DesignsApiResponse>('/designs/shared'),

  // GET /designs/{id}/collaborators - Get design collaborators
  getCollaborators: (designId: string) => 
    api.get<ApiResponse<Collaborator[]>>(`/designs/${designId}/collaborators`),
}

// ============================================================================
// YOUTUBE VIDEO ANALYSIS API
// ============================================================================

// YouTube Video Analysis API - for generating designs from video content
export const videoAnalysisAPI = {
  // POST /video-analysis/generate - Generate designs from YouTube video
  generateDesignsFromVideo: (data: GenerateDesignFromVideoRequest) =>
    api.post<VideoAnalysisApiResponse>('/video-analysis/generate', data),

  // GET /video-analysis/jobs/{jobId} - Get video analysis job status
  getAnalysisJob: (jobId: string) =>
    api.get<VideoAnalysisJobApiResponse>(`/video-analysis/jobs/${jobId}`),

  // GET /video-analysis/jobs - Get user's video analysis jobs
  getAnalysisJobs: (params?: {
    page?: number
    limit?: number
    status?: 'processing' | 'completed' | 'failed'
  }) => api.get<VideoAnalysisJobsApiResponse>('/video-analysis/jobs', { params }),

  // DELETE /video-analysis/jobs/{jobId} - Delete analysis job
  deleteAnalysisJob: (jobId: string) =>
    api.delete<ApiResponse<{ message: string }>>(`/video-analysis/jobs/${jobId}`),

  // POST /video-analysis/jobs/{jobId}/retry - Retry failed analysis
  retryAnalysisJob: (jobId: string) =>
    api.post<VideoAnalysisJobApiResponse>(`/video-analysis/jobs/${jobId}/retry`),

  // GET /video-analysis/extract-info - Extract basic video info from URL
  extractVideoInfo: (params: { videoUrl: string }) =>
    api.get<ApiResponse<YouTubeVideoInfo>>('/video-analysis/extract-info', { params }),
}

// ============================================================================
// INTEGRATIONS API
// ============================================================================

// Integrations API - for managing third-party service credentials
export const integrationsAPI = {
  // GET /integrations - Get all user integrations
  getIntegrations: () =>
    api.get<ApiResponse<Integration[]>>('/integrations'),

  // POST /integrations - Save integration credentials
  saveIntegration: (data: SaveIntegrationData) =>
    api.post<ApiResponse<{ message: string }>>('/integrations', data),

  // POST /integrations/test - Test integration credentials
  testIntegration: (data: TestIntegrationData) =>
    api.post<ApiResponse<TestIntegrationResult>>('/integrations/test', data),

  // GET /integrations/{serviceName} - Get specific integration
  getIntegration: (serviceName: string) =>
    api.get<ApiResponse<Integration>>(`/integrations/${serviceName}`),

  // DELETE /integrations/{serviceName} - Remove integration
  removeIntegration: (serviceName: string) =>
    api.delete<ApiResponse<{ message: string }>>(`/integrations/${serviceName}`),
}

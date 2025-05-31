import { api } from '../utils/api'
import type { Design, Template, MediaItem, User, ExportJob, ExportOptions } from '@/types'

export interface PaginatedResponse<T> {
  data: T[]
  meta: {
    total: number
    page: number
    per_page: number
    last_page: number
    from: number
    to: number
  }
}

export interface ApiResponse<T> {
  data: T
  message?: string
}

// Authentication API
export const authAPI = {
  // Login user
  login: (credentials: { email: string; password: string }) =>
    api.post<ApiResponse<{ user: User; token: string }>>('/auth/login', credentials),

  // Register new user
  register: (data: {
    firstName: string
    lastName: string
    email: string
    password: string
    confirmPassword: string
  }) => api.post<ApiResponse<{ user: User; token: string }>>('/auth/register', data),

  // Logout user
  logout: () => api.post('/auth/logout'),

  // Refresh token
  refreshToken: () => api.post<ApiResponse<{ token: string }>>('/auth/refresh'),

  // Forgot password
  forgotPassword: (email: string) =>
    api.post<ApiResponse<{ message: string }>>('/auth/forgot-password', { email }),

  // Reset password
  resetPassword: (data: {
    token: string
    email: string
    password: string
    confirmPassword: string
  }) => api.post<ApiResponse<{ message: string }>>('/auth/reset-password', data),

  // Verify email
  verifyEmail: (token: string) =>
    api.post<ApiResponse<{ message: string }>>('/auth/verify-email', { token }),

  // Resend verification email
  resendVerification: (email: string) =>
    api.post<ApiResponse<{ message: string }>>('/auth/resend-verification', { email }),
}

// Design API
export const designAPI = {
  // Get user's designs with pagination and filters
  getDesigns: (params?: {
    page?: number
    per_page?: number
    search?: string
    sort_by?: 'created_at' | 'updated_at' | 'name'
    sort_order?: 'asc' | 'desc'
  }) => api.get<PaginatedResponse<Design>>('/designs', { params }),

  // Get single design by ID
  getDesign: (id: string) => api.get<ApiResponse<Design>>(`/designs/${id}`),

  // Create new design
  createDesign: (data: {
    name: string
    width: number
    height: number
    template_id?: string
  }) => api.post<ApiResponse<Design>>('/designs', data),

  // Update design
  updateDesign: (id: string, data: Partial<Design>) => 
    api.put<ApiResponse<Design>>(`/designs/${id}`, data),

  // Delete design
  deleteDesign: (id: string) => api.delete(`/designs/${id}`),

  // Duplicate design
  duplicateDesign: (id: string) => api.post<ApiResponse<Design>>(`/designs/${id}/duplicate`),

  // Save design data (canvas, layers, etc.)
  saveDesignData: (id: string, data: any) => 
    api.put<ApiResponse<Design>>(`/designs/${id}/data`, { data }),

  // Export design
  exportDesign: (id: string, format: 'png' | 'jpg' | 'pdf' | 'svg') =>
    api.post(`/designs/${id}/export`, { format }, { responseType: 'blob' }),

  // Get recent designs
  getRecentDesigns: (limit = 8) => 
    api.get<ApiResponse<Design[]>>('/designs/recent', { params: { limit } }),
}

// Template API
export const templateAPI = {
  // Get templates with pagination and filters
  getTemplates: (params?: {
    page?: number
    per_page?: number
    category?: string
    search?: string
    is_premium?: boolean
    sort_by?: 'created_at' | 'updated_at' | 'name' | 'popularity'
    sort_order?: 'asc' | 'desc'
  }) => api.get<PaginatedResponse<Template>>('/templates', { params }),

  // Get template by ID
  getTemplate: (id: string) => api.get<ApiResponse<Template>>(`/templates/${id}`),

  // Get featured templates
  getFeaturedTemplates: (limit = 6) => 
    api.get<ApiResponse<Template[]>>('/templates/featured', { params: { limit } }),

  // Get template categories
  getCategories: () => api.get<ApiResponse<string[]>>('/templates/categories'),

  // Create design from template
  createFromTemplate: (templateId: string, name?: string) =>
    api.post<ApiResponse<Design>>(`/templates/${templateId}/create-design`, { name }),
}

// Media API
export const mediaAPI = {
  // Get user's media with pagination
  getMedia: (params?: {
    page?: number
    per_page?: number
    type?: 'image' | 'video' | 'audio'
    search?: string
    sort_by?: 'created_at' | 'updated_at' | 'name' | 'size'
    sort_order?: 'asc' | 'desc'
  }) => api.get<PaginatedResponse<MediaItem>>('/media', { params }),

  // Upload media file
  uploadMedia: (file: File, folder?: string) => {
    const formData = new FormData()
    formData.append('file', file)
    if (folder) formData.append('folder', folder)
    
    return api.post<ApiResponse<MediaItem>>('/media/upload', formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    })
  },

  // Delete media item
  deleteMedia: (id: string) => api.delete(`/media/${id}`),

  // Get stock media
  getStockMedia: (params?: {
    page?: number
    per_page?: number
    query?: string
    category?: string
    color?: string
    orientation?: 'landscape' | 'portrait' | 'square'
  }) => api.get<PaginatedResponse<MediaItem>>('/media/stock', { params }),
}

// Analytics API
export const analyticsAPI = {
  // Get dashboard stats
  getDashboardStats: () => api.get<ApiResponse<{
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
  }>>('/analytics/dashboard'),

  // Get design analytics
  getDesignAnalytics: (designId: string) => 
    api.get<ApiResponse<{
      views: number
      exports: number
      shares: number
      edit_time: number
      last_opened: string
    }>>(`/analytics/designs/${designId}`),
}

// User API
export const userAPI = {
  // Get current user profile
  getProfile: () => api.get<ApiResponse<User>>('/user/profile'),

  // Update user profile
  updateProfile: (data: Partial<User>) => 
    api.put<ApiResponse<User>>('/user/profile', data),

  // Upload avatar
  uploadAvatar: (file: File) => {
    const formData = new FormData()
    formData.append('avatar', file)
    return api.post<ApiResponse<{ avatar: string }>>('/user/avatar', formData, {
      headers: {
        'Content-Type': 'multipart/form-data'
      }
    })
  },

  // Change password
  changePassword: (data: { currentPassword: string; newPassword: string }) =>
    api.put<ApiResponse<{ message: string }>>('/user/password', data),

  // Update user preferences
  updatePreferences: (preferences: Record<string, any>) =>
    api.put<ApiResponse<User>>('/user/preferences', { preferences }),

  // Get user subscription info
  getSubscription: () => api.get<ApiResponse<{
    plan: string
    status: string
    expires_at: string
    features: string[]
  }>>('/user/subscription'),

  // Get user storage usage
  getStorageUsage: () => api.get<ApiResponse<{
    used: number
    limit: number
    percentage: number
  }>>('/user/storage'),
}

// Collaboration API (for future features)
export const collaborationAPI = {
  // Share design
  shareDesign: (designId: string, data: {
    email?: string
    permissions: 'view' | 'edit'
    expires_at?: string
  }) => api.post<ApiResponse<{ share_url: string }>>(`/designs/${designId}/share`, data),

  // Get shared designs
  getSharedDesigns: () => api.get<PaginatedResponse<Design>>('/designs/shared'),

  // Get design collaborators
  getCollaborators: (designId: string) => 
    api.get<ApiResponse<Array<{
      user: User
      permissions: string
      joined_at: string
    }>>>(`/designs/${designId}/collaborators`),
}

// Export Jobs API
export const exportAPI = {
  // Get user's export jobs with pagination and filters
  getExportJobs: (params?: {
    page?: number
    per_page?: number
    status?: 'pending' | 'processing' | 'completed' | 'failed'
    format?: string
    design_id?: string
    sort_by?: 'created_at' | 'updated_at' | 'status'
    sort_order?: 'asc' | 'desc'
  }) => api.get<PaginatedResponse<ExportJob>>('/export-jobs', { params }),

  // Get single export job by ID
  getExportJob: (id: string) => api.get<ApiResponse<ExportJob>>(`/export-jobs/${id}`),

  // Create new export job
  createExportJob: (data: {
    designId: string
    format: 'png' | 'jpg' | 'jpeg' | 'pdf' | 'svg' | 'mp4' | 'gif'
    options?: ExportOptions
  }) => api.post<ApiResponse<ExportJob>>('/export-jobs', data),

  // Cancel export job
  cancelExportJob: (id: string) => api.delete(`/export-jobs/${id}`),

  // Download completed export
  downloadExport: (id: string) => 
    api.get(`/export-jobs/${id}/download`, { responseType: 'blob' }),

  // Get export job progress (for real-time updates)
  getJobProgress: (id: string) => api.get<ApiResponse<{
    status: string
    progress: number
    message?: string
  }>>(`/export-jobs/${id}/progress`),

  // Retry failed export job
  retryExportJob: (id: string) => api.post<ApiResponse<ExportJob>>(`/export-jobs/${id}/retry`),
}

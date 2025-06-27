import axios from 'axios'
import type { AxiosRequestConfig } from 'axios'

// Create axios instance with base configuration
export const api = axios.create({
  baseURL: import.meta.env.VITE_API_URL || '/api',
  timeout: 60000, // Increased to 60 seconds for long-running operations like Replicate API
})


// Request interceptor to add auth token and set appropriate headers
api.interceptors.request.use(
  (config) => {
    const token = localStorage.getItem('auth_token')
    if (token) {
      config.headers.Authorization = `Bearer ${token}`
    }
    
    // For FormData, ensure no Content-Type header is set so browser can set it with boundary
    if (config.data instanceof FormData) {
      // Remove any Content-Type headers that might have been set
      delete config.headers['Content-Type']
      delete config.headers['content-type']
      // Ensure headers object doesn't have the default
      if (config.headers.common) {
        delete config.headers.common['Content-Type']
      }
    } else {
      // For non-FormData requests, set Content-Type to application/json if not already set
      const hasContentType = config.headers['Content-Type'] || config.headers['content-type']
      if (!hasContentType) {
        config.headers['Content-Type'] = 'application/json'
      }
    }
    
    return config
  },
  (error) => {
    return Promise.reject(error)
  }
)

// Response interceptor to handle common errors
api.interceptors.response.use(
  (response) => {
    return response
  },
  (error) => {
    if (error.response?.status === 401) {
      // Unauthorized - clear auth token and redirect to login
      localStorage.removeItem('auth_token')
      
      // Construct redirect URL properly
      const currentPath = window.location.pathname
      const currentSearch = window.location.search
      
      // Don't redirect if already on login page to avoid infinite redirect loops
      if (currentPath === '/login') {
        return Promise.reject(error)
      }
      
      // Construct the full current URL (path + search params, excluding any existing redirect params)
      let redirectPath = currentPath
      if (currentSearch) {
        // Parse current search params and remove any existing redirect param to avoid nesting
        const searchParams = new URLSearchParams(currentSearch)
        searchParams.delete('redirect') // Remove any existing redirect param
        const cleanSearch = searchParams.toString()
        if (cleanSearch) {
          redirectPath = `${currentPath}?${cleanSearch}`
        }
      }
      
      // Redirect to login with the clean redirect URL
      const loginUrl = `/login?redirect=${encodeURIComponent(redirectPath)}`
      window.location.href = loginUrl
    }
    return Promise.reject(error)
  }
)

// Extended API instance with support for per-request options
export const apiWithOptions = {
  get: <T = any>(url: string, config?: AxiosRequestConfig) => 
    api.get<T>(url, config),
  
  post: <T = any>(url: string, data?: any, config?: AxiosRequestConfig) => 
    api.post<T>(url, data, config),
  
  put: <T = any>(url: string, data?: any, config?: AxiosRequestConfig) => 
    api.put<T>(url, data, config),
  
  delete: <T = any>(url: string, config?: AxiosRequestConfig) => 
    api.delete<T>(url, config),
  
  patch: <T = any>(url: string, data?: any, config?: AxiosRequestConfig) => 
    api.patch<T>(url, data, config),
}

export default api

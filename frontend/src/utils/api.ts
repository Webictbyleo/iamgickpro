import axios from 'axios'

// Create axios instance with base configuration
export const api = axios.create({
  baseURL: import.meta.env.VITE_API_URL || '/api',
  timeout: 10000,
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
      // Unauthorized - redirect to login
      localStorage.removeItem('auth_token')
      const currentPath = window.location.pathname + window.location.search
      window.location.href = `/login?redirect=${encodeURIComponent(currentPath)}`
    }
    return Promise.reject(error)
  }
)

export default api

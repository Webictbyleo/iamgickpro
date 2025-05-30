import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { api } from '@/utils/api'
import { useNotifications } from '@/composables/useNotifications'
import type { User } from '@/types'

interface LoginCredentials {
  email: string
  password: string
}

interface RegisterCredentials {
  name: string
  email: string
  password: string
  password_confirmation: string
}

export const useAuthStore = defineStore('auth', () => {
  const { success, error: showError } = useNotifications()
  
  const user = ref<User | null>(null)
  const token = ref<string | null>(localStorage.getItem('auth_token'))
  const isLoading = ref(false)
  const error = ref<string | null>(null)

  const isAuthenticated = computed(() => !!token.value && !!user.value)

  const setError = (message: string) => {
    error.value = message
    showError(message)
    setTimeout(() => {
      error.value = null
    }, 5000)
  }

  const login = async (credentials: LoginCredentials) => {
    isLoading.value = true
    error.value = null
    
    try {
      const response = await api.post('/auth/login', credentials)
      const { user: userData, access_token } = response.data.data
      
      user.value = userData
      token.value = access_token
      localStorage.setItem('auth_token', access_token)
      
      success('Welcome back! You have been logged in successfully.')
      return { success: true }
    } catch (err: any) {
      const message = err.response?.data?.message || 'Login failed'
      setError(message)
      return { success: false, error: message }
    } finally {
      isLoading.value = false
    }
  }

  const register = async (credentials: RegisterCredentials) => {
    isLoading.value = true
    error.value = null
    
    try {
      const response = await api.post('/auth/register', credentials)
      const { user: userData, access_token } = response.data.data
      
      user.value = userData
      token.value = access_token
      localStorage.setItem('auth_token', access_token)
      
      success('Account created successfully! Welcome to IGPro!')
      return { success: true }
    } catch (err: any) {
      const message = err.response?.data?.message || 'Registration failed'
      setError(message)
      return { success: false, error: message }
    } finally {
      isLoading.value = false
    }
  }

  const logout = async () => {
    isLoading.value = true
    
    try {
      if (token.value) {
        await api.post('/auth/logout')
      }
      success('You have been logged out successfully.')
    } catch (err) {
      console.warn('Logout API call failed:', err)
      // Still show success since local logout will happen regardless
      success('You have been logged out.')
    } finally {
      user.value = null
      token.value = null
      localStorage.removeItem('auth_token')
      isLoading.value = false
    }
  }

  const fetchUser = async () => {
    if (!token.value) return { success: false, error: 'No token available' }
    
    isLoading.value = true
    error.value = null
    
    try {
      const response = await api.get('/auth/me')
      user.value = response.data.data
      return { success: true }
    } catch (err: any) {
      const message = err.response?.data?.message || 'Failed to fetch user'
      setError(message)
      
      // If token is invalid, clear it
      if (err.response?.status === 401) {
        await logout()
      }
      
      return { success: false, error: message }
    } finally {
      isLoading.value = false
    }
  }

  const updateProfile = async (data: Partial<User>) => {
    isLoading.value = true
    error.value = null
    
    try {
      const response = await api.put('/user/profile', data)
      user.value = response.data.data
      success('Profile updated successfully!')
      return { success: true }
    } catch (err: any) {
      const message = err.response?.data?.message || 'Failed to update profile'
      setError(message)
      return { success: false, error: message }
    } finally {
      isLoading.value = false
    }
  }

  const refreshToken = async () => {
    if (!token.value) return { success: false, error: 'No token to refresh' }
    
    try {
      const response = await api.post('/auth/refresh')
      const { access_token } = response.data.data
      
      token.value = access_token
      localStorage.setItem('auth_token', access_token)
      
      return { success: true }
    } catch (err: any) {
      console.warn('Token refresh failed:', err)
      await logout()
      return { success: false, error: 'Token refresh failed' }
    }
  }

  // Initialize auth state on store creation
  const initialize = async () => {
    if (token.value) {
      await fetchUser()
    }
  }

  return {
    // State
    user,
    token,
    isLoading,
    error,
    
    // Getters
    isAuthenticated,
    
    // Actions
    login,
    register,
    logout,
    fetchUser,
    updateProfile,
    refreshToken,
    initialize,
    setError
  }
})

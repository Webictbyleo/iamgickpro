import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { authAPI } from '@/services/api'
import { useNotifications } from '@/composables/useNotifications'
import type { User, LoginCredentials, RegisterData, ResetPasswordData } from '@/types'

export const useAuthStore = defineStore('auth', () => {
  const { 
    success, 
    error: notificationError, 
    showSuccess, 
    showError
  } = useNotifications()
  
  const user = ref<User | null>(null)
  const token = ref<string | null>(localStorage.getItem('auth_token'))

  const isLoading = ref(false)
  const error = ref<string | null>(null)

  const isAuthenticated = computed(() => !!token.value)

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
      const response = await authAPI.login(credentials)
      const { user: userData, token: access_token } = response.data
      
      user.value = userData
      token.value = access_token
      localStorage.setItem('auth_token', access_token)
      
      success('Login Successful', 'Welcome back! You have been logged in successfully.')
      return { success: true, user: userData }
    } catch (err: any) {
      const message = err.response?.data?.message || 'Login failed'
      setError(message)
      return { success: false, error: message }
    } finally {
      isLoading.value = false
    }
  }

  const register = async (credentials: RegisterData) => {
    isLoading.value = true
    error.value = null
    
    try {
      const response = await authAPI.register(credentials)
      const { user: userData, token: access_token } = response.data
      
      user.value = userData
      token.value = access_token
      localStorage.setItem('auth_token', access_token)
      
      success('Registration Successful', 'Account created successfully! Welcome to IGPro!')
      return { success: true, user: userData }
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
        await authAPI.logout()
      }
      success('Logout Successful', 'You have been logged out successfully.')
    } catch (err) {
      console.warn('Logout API call failed:', err)
      // Still show success since local logout will happen regardless
      success('Logout Complete', 'You have been logged out.')
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
      const response = await authAPI.getCurrentUser()
      user.value = response.data.user
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
      const response = await authAPI.updateProfile(data)
      user.value = response.data.user
      success('Profile Updated', 'Profile updated successfully!')
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
      const response = await authAPI.refreshToken()
      const { token: access_token } = response.data.data
      
      token.value = access_token
      localStorage.setItem('auth_token', access_token)
      
      return { success: true }
    } catch (err: any) {
      console.warn('Token refresh failed:', err)
      await logout()
      return { success: false, error: 'Token refresh failed' }
    }
  }

  const forgotPassword = async (email: string) => {
    isLoading.value = true
    error.value = null
    
    try {
      const response = await authAPI.forgotPassword(email)
      success('Reset Link Sent', 'Please check your email for password reset instructions.')
      return { success: true }
    } catch (err: any) {
      const message = err.response?.data?.message || 'Failed to send reset email'
      setError(message)
      return { success: false, error: message }
    } finally {
      isLoading.value = false
    }
  }

  const resetPassword = async (data: ResetPasswordData) => {
    isLoading.value = true
    error.value = null
    
    try {
      const response = await authAPI.resetPassword(data)
      success('Password Reset', 'Your password has been reset successfully.')
      return { success: true }
    } catch (err: any) {
      const message = err.response?.data?.message || 'Failed to reset password'
      setError(message)
      return { success: false, error: message }
    } finally {
      isLoading.value = false
    }
  }

  const verifyToken = async () => {
    if (!token.value) return { success: false, error: 'No token available' }
    
    try {
      const response = await authAPI.getCurrentUser()
      user.value = response.data.user
      return { success: true }
    } catch (err: any) {
      console.warn('Token verification failed:', err)
      await logout()
      return { success: false, error: 'Token verification failed' }
    }
  }

  const updateUser = async (data: Partial<User>) => {
    isLoading.value = true
    error.value = null
    
    try {
      const response = await authAPI.updateProfile(data)
      user.value = response.data.user
      success('Profile Updated', 'Profile updated successfully!')
      return { success: true }
    } catch (err: any) {
      const message = err.response?.data?.message || 'Failed to update profile'
      setError(message)
      return { success: false, error: message }
    } finally {
      isLoading.value = false
    }
  }

  // Initialize auth state on store creation
  const initialize = async () => {
    if (token.value) {
      // Always fetch user if we have a token since we don't store user data in localStorage
      console.log('🔄 Initializing auth state: fetching current user...')
      await fetchUser()
    }
  }

  // Auto-initialize when store is created
  initialize()

  // Alias for compatibility
  const initializeAuth = initialize

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
    initializeAuth,
    forgotPassword,
    resetPassword,
    verifyToken,
    updateUser,
    setError
  }
})

import { ref, computed } from 'vue'
import { useRouter } from 'vue-router'
import { authAPI } from '@/services/api'
import type { User } from '@/types'
import { useNotifications } from './useNotifications'

// Global auth state
const user = ref<User | null>(null)
const token = ref<string | null>(localStorage.getItem('auth_token'))
const isLoading = ref(false)

export const useAuth = () => {
  const router = useRouter()
  const { success, error } = useNotifications()

  // Computed properties
  const isAuthenticated = computed(() => !!token.value && !!user.value)
  const isGuest = computed(() => !isAuthenticated.value)

  // Initialize auth state from localStorage
  const initializeAuth = async () => {
    const storedToken = localStorage.getItem('auth_token')
    const storedUser = localStorage.getItem('user_data')

    if (storedToken && storedUser) {
      token.value = storedToken
      try {
        user.value = JSON.parse(storedUser)
        // Optionally verify token is still valid
        await verifyToken()
      } catch (error) {
        console.error('Failed to parse stored user data:', error)
        logout()
      }
    }
  }

  // Verify token is still valid
  const verifyToken = async () => {
    if (!token.value) return false

    try {
      // This could call an endpoint like /auth/verify or get current user
      const response = await authAPI.refreshToken()
      token.value = response.data.data.token
      localStorage.setItem('auth_token', token.value)
      return true
    } catch (error) {
      console.error('Token verification failed:', error)
      logout()
      return false
    }
  }

  // Login function
  const login = async (credentials: { email: string; password: string }) => {
    isLoading.value = true
    try {
      const response = await authAPI.login(credentials)
      const { user: userData, token: authToken } = response.data.data

      // Store auth data
      user.value = userData
      token.value = authToken
      localStorage.setItem('auth_token', authToken)
      localStorage.setItem('user_data', JSON.stringify(userData))

      success('Welcome Back!', `Hello ${userData.firstName}, you're now logged in.`)
      
      // Redirect to dashboard or intended route
      const redirectTo = router.currentRoute.value.query.redirect as string || '/dashboard'
      router.push(redirectTo)

      return { success: true, user: userData }
    } catch (loginError: any) {
      console.error('Login failed:', loginError)
      const message = loginError.response?.data?.message || 'Invalid email or password'
      error('Login Failed', message)
      return { success: false, error: message }
    } finally {
      isLoading.value = false
    }
  }

  // Register function
  const register = async (data: {
    firstName: string
    lastName: string
    email: string
    password: string
    confirmPassword: string
  }) => {
    isLoading.value = true
    try {
      const response = await authAPI.register(data)
      const { user: userData, token: authToken } = response.data.data

      // Store auth data
      user.value = userData
      token.value = authToken
      localStorage.setItem('auth_token', authToken)
      localStorage.setItem('user_data', JSON.stringify(userData))

      success('Account Created!', `Welcome to IamGickPro, ${userData.firstName}!`)
      
      // Redirect to dashboard
      router.push('/dashboard')

      return { success: true, user: userData }
    } catch (registerError: any) {
      console.error('Registration failed:', registerError)
      const message = registerError.response?.data?.message || 'Registration failed'
      error('Registration Failed', message)
      return { success: false, error: message }
    } finally {
      isLoading.value = false
    }
  }

  // Logout function
  const logout = async () => {
    try {
      if (token.value) {
        await authAPI.logout()
      }
    } catch (error) {
      console.error('Logout API call failed:', error)
    } finally {
      // Clear local state regardless of API call result
      user.value = null
      token.value = null
      localStorage.removeItem('auth_token')
      localStorage.removeItem('user_data')
      
      success('Goodbye!', 'You have been logged out successfully.')
      router.push('/login')
    }
  }

  // Forgot password
  const forgotPassword = async (email: string) => {
    isLoading.value = true
    try {
      await authAPI.forgotPassword(email)
      success('Reset Link Sent', 'Please check your email for password reset instructions.')
      return { success: true }
    } catch (forgotError: any) {
      const message = forgotError.response?.data?.message || 'Failed to send reset email'
      error('Reset Failed', message)
      return { success: false, error: message }
    } finally {
      isLoading.value = false
    }
  }

  // Reset password
  const resetPassword = async (data: {
    token: string
    email: string
    password: string
    confirmPassword: string
  }) => {
    isLoading.value = true
    try {
      await authAPI.resetPassword(data)
      success('Password Reset', 'Your password has been reset successfully.')
      router.push('/login')
      return { success: true }
    } catch (resetError: any) {
      const message = resetError.response?.data?.message || 'Failed to reset password'
      error('Reset Failed', message)
      return { success: false, error: message }
    } finally {
      isLoading.value = false
    }
  }

  // Update user profile
  const updateUser = (userData: Partial<User>) => {
    if (user.value) {
      user.value = { ...user.value, ...userData }
      localStorage.setItem('user_data', JSON.stringify(user.value))
    }
  }

  return {
    // State
    user: computed(() => user.value),
    token: computed(() => token.value),
    isLoading: computed(() => isLoading.value),
    isAuthenticated,
    isGuest,

    // Methods
    initializeAuth,
    verifyToken,
    login,
    register,
    logout,
    forgotPassword,
    resetPassword,
    updateUser,
  }
}

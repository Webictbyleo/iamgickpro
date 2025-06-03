import { computed } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useNotifications } from './useNotifications'

export const useAuth = () => {
  const router = useRouter()
  const authStore = useAuthStore()
  const { success, error } = useNotifications()

  // Computed properties from store
  const isAuthenticated = computed(() => authStore.isAuthenticated)
  const isGuest = computed(() => !authStore.isAuthenticated)

  // Login function
  const login = async (credentials: { email: string; password: string }) => {
    try {
      const result = await authStore.login(credentials)
      
      if (result.success) {
        // Redirect to dashboard or intended route
        const redirectTo = router.currentRoute.value.query.redirect as string || '/dashboard'
        router.push(redirectTo)
      } else {
        error('Login Failed', result.error || 'Invalid email or password')
      }
      
      return result
    } catch (loginError: any) {
      console.error('Login failed:', loginError)
      const message = loginError.response?.data?.message || 'Invalid email or password'
      error('Login Failed', message)
      return { success: false, error: message }
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
    try {
      const result = await authStore.register(data)
      
      if (result.success) {
        const appTitle = import.meta.env.VITE_APP_TITLE || 'Design Studio'
        success('Account Created!', `Welcome to ${appTitle}, ${result.user?.firstName}!`)
        router.push('/dashboard')
      } else {
        error('Registration Failed', result.error || 'Registration failed')
      }
      
      return result
    } catch (registerError: any) {
      console.error('Registration failed:', registerError)
      const message = registerError.response?.data?.message || 'Registration failed'
      error('Registration Failed', message)
      return { success: false, error: message }
    }
  }

  // Logout function
  const logout = async () => {
    await authStore.logout()
    success('Goodbye!', 'You have been logged out successfully.')
    router.push('/login')
  }

  // Forgot password
  const forgotPassword = async (email: string) => {
    try {
      const result = await authStore.forgotPassword(email)
      
      if (result.success) {
        success('Reset Link Sent', 'Please check your email for password reset instructions.')
      } else {
        error('Reset Failed', result.error || 'Failed to send reset email')
      }
      
      return result
    } catch (forgotError: any) {
      const message = forgotError.response?.data?.message || 'Failed to send reset email'
      error('Reset Failed', message)
      return { success: false, error: message }
    }
  }

  // Reset password
  const resetPassword = async (data: {
    token: string
    email: string
    password: string
    confirmPassword: string
  }) => {
    try {
      const result = await authStore.resetPassword(data)
      
      if (result.success) {
        success('Password Reset', 'Your password has been reset successfully.')
        router.push('/login')
      } else {
        error('Reset Failed', result.error || 'Failed to reset password')
      }
      
      return result
    } catch (resetError: any) {
      const message = resetError.response?.data?.message || 'Failed to reset password'
      error('Reset Failed', message)
      return { success: false, error: message }
    }
  }

  return {
    // State from store
    user: computed(() => authStore.user),
    token: computed(() => authStore.token),
    isLoading: computed(() => authStore.isLoading),
    isAuthenticated,
    isGuest,

    // Methods that delegate to store
    initializeAuth: authStore.initializeAuth,
    verifyToken: authStore.verifyToken,
    updateUser: authStore.updateUser,
    
    // Enhanced methods with UI feedback
    login,
    register,
    logout,
    forgotPassword,
    resetPassword,
  }
}

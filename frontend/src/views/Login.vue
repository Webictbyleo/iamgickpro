<template>
  <div class="min-h-screen bg-gradient-to-br from-indigo-50 via-white to-violet-50 flex items-center justify-center p-4">
    <div class="w-full max-w-md">
      <!-- Logo and Header -->
      <div class="text-center mb-8">
        <div class="inline-flex items-center justify-center w-16 h-16 bg-gradient-to-r from-indigo-600 to-violet-600 rounded-2xl shadow-lg mb-4">
          <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zM21 5a2 2 0 00-2-2h-4a2 2 0 00-2 2v12a4 4 0 004 4h4a2 2 0 002-2V5z"></path>
          </svg>
        </div>
        <h1 class="text-3xl font-bold text-gray-900 mb-2">Welcome Back</h1>
        <p class="text-gray-600">Sign in to continue to {{ appTitle }}</p>
      </div>

      <!-- Login Form -->
      <div class="bg-white rounded-2xl shadow-xl border border-gray-100 p-8">
        <!-- Success Message -->
        <div v-if="successMessage" class="mb-6 bg-green-50 border border-green-200 rounded-lg p-4">
          <div class="flex items-center">
            <CheckCircleIcon class="h-5 w-5 text-green-500 mr-2" />
            <p class="text-sm text-green-800">{{ successMessage }}</p>
          </div>
        </div>

        <form @submit.prevent="handleLogin" class="space-y-6">
          <!-- Email Field -->
          <div>
            <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
              Email Address
            </label>
            <div class="relative">
              <input
                id="email"
                v-model="loginForm.email"
                type="email"
                required
                autocomplete="email"
                class="w-full px-4 py-3 pl-12 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-300 transition-all duration-200"
                placeholder="Enter your email"
                :class="{ 'border-red-300 focus:border-red-300 focus:ring-red-500/20': emailError }"
              />
              <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                <EnvelopeIcon class="h-5 w-5 text-gray-400" />
              </div>
            </div>
            <p v-if="emailError" class="mt-1 text-sm text-red-600">{{ emailError }}</p>
          </div>

          <!-- Password Field -->
          <div>
            <label for="password" class="block text-sm font-medium text-gray-700 mb-2">
              Password
            </label>
            <div class="relative">
              <input
                id="password"
                v-model="loginForm.password"
                :type="showPassword ? 'text' : 'password'"
                required
                autocomplete="current-password"
                class="w-full px-4 py-3 pl-12 pr-12 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-300 transition-all duration-200"
                placeholder="Enter your password"
                :class="{ 'border-red-300 focus:border-red-300 focus:ring-red-500/20': passwordError }"
              />
              <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                <LockClosedIcon class="h-5 w-5 text-gray-400" />
              </div>
              <button
                type="button"
                @click="showPassword = !showPassword"
                class="absolute inset-y-0 right-0 pr-4 flex items-center text-gray-400 hover:text-gray-600 transition-colors"
              >
                <EyeIcon v-if="!showPassword" class="h-5 w-5" />
                <EyeSlashIcon v-else class="h-5 w-5" />
              </button>
            </div>
            <p v-if="passwordError" class="mt-1 text-sm text-red-600">{{ passwordError }}</p>
          </div>

          <!-- Remember Me & Forgot Password -->
          <div class="flex items-center justify-between">
            <label class="flex items-center">
              <input
                v-model="loginForm.rememberMe"
                type="checkbox"
                class="w-4 h-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500/20 focus:ring-2"
              />
              <span class="ml-2 text-sm text-gray-600">Remember me</span>
            </label>
            <router-link
              to="/forgot-password"
              class="text-sm text-indigo-600 hover:text-indigo-700 font-medium transition-colors"
            >
              Forgot password?
            </router-link>
          </div>

          <!-- Submit Button -->
          <button
            type="submit"
            :disabled="isLoading || !isFormValid"
            class="w-full bg-gradient-to-r from-indigo-600 to-violet-600 text-white py-3 px-4 rounded-xl font-medium hover:from-indigo-700 hover:to-violet-700 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-200 shadow-lg"
          >
            <span v-if="isLoading" class="flex items-center justify-center">
              <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              Signing in...
            </span>
            <span v-else>Sign In</span>
          </button>
        </form>

        <!-- Sign Up Link -->
        <div class="mt-8 text-center">
          <p class="text-sm text-gray-600">
            Don't have an account?
            <router-link
              to="/register"
              class="text-indigo-600 hover:text-indigo-700 font-medium transition-colors"
            >
              Sign up for free
            </router-link>
          </p>
        </div>
      </div>

      <!-- Footer -->
      <div class="mt-8 text-center">
        <p class="text-xs text-gray-500">
          By signing in, you agree to our
          <a href="/terms" class="text-indigo-600 hover:text-indigo-700">Terms of Service</a>
          and
          <a href="/privacy" class="text-indigo-600 hover:text-indigo-700">Privacy Policy</a>
        </p>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import {
  EnvelopeIcon,
  LockClosedIcon,
  EyeIcon,
  EyeSlashIcon,
  CheckCircleIcon,
} from '@heroicons/vue/24/outline'
import { useAuth } from '@/composables/useAuth'

// Router
const router = useRouter()
const route = useRoute()

// Auth composable
const { login, isLoading, isAuthenticated } = useAuth()

// Form state
const loginForm = ref({
  email: '',
  password: '',
  rememberMe: false,
})

const showPassword = ref(false)
const emailError = ref('')
const passwordError = ref('')
const successMessage = ref('')

// Computed properties
const appTitle = computed(() => import.meta.env.VITE_APP_TITLE || 'Design Studio')

const isFormValid = computed(() => {
  return loginForm.value.email.length > 0 && 
         loginForm.value.password.length > 0 &&
         !emailError.value && 
         !passwordError.value
})

// Validation
const validateEmail = () => {
  const email = loginForm.value.email
  if (!email) {
    emailError.value = 'Email is required'
    return false
  }
  
  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
  if (!emailRegex.test(email)) {
    emailError.value = 'Please enter a valid email address'
    return false
  }
  
  emailError.value = ''
  return true
}

const validatePassword = () => {
  const password = loginForm.value.password
  if (!password) {
    passwordError.value = 'Password is required'
    return false
  }
  
  if (password.length < 6) {
    passwordError.value = 'Password must be at least 6 characters'
    return false
  }
  
  passwordError.value = ''
  return true
}

// Handle login
const handleLogin = async () => {
  // Validate form
  const isEmailValid = validateEmail()
  const isPasswordValid = validatePassword()
  
  if (!isEmailValid || !isPasswordValid) {
    return
  }

  // Attempt login
  const result = await login({
    email: loginForm.value.email,
    password: loginForm.value.password,
  })

  if (result.success) {
    successMessage.value = 'Login successful! Redirecting...'
    setTimeout(() => {
      // Redirect will be handled by the auth composable
      router.push('/dashboard')
    }, 2000)
  } else {
    successMessage.value = ''
  }
}

// Lifecycle
onMounted(() => {
  // Redirect if already authenticated
  if (isAuthenticated.value) {
    router.push('/dashboard')
  }
  
  // Check for success message from query params
  if (route.query.message) {
    successMessage.value = route.query.message as string
  }
})

// Clear errors when form changes
const clearErrors = () => {
  emailError.value = ''
  passwordError.value = ''
}

// Watch form changes
import { watch } from 'vue'
watch(() => loginForm.value.email, clearErrors)
watch(() => loginForm.value.password, clearErrors)
</script>

<style scoped>
/* Custom styles for enhanced visual appeal */
.bg-gradient-to-br {
  background-image: linear-gradient(to bottom right, var(--tw-gradient-stops));
}

/* Focus ring enhancement */
input:focus {
  box-shadow: 0 0 0 3px rgb(99 102 241 / 0.1);
}

/* Button hover effects */
button:hover:not(:disabled) {
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgb(0 0 0 / 0.15);
}

/* Animation for loading spinner */
@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

.animate-spin {
  animation: spin 1s linear infinite;
}
</style>

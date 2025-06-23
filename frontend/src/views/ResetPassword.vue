<template>
  <div class="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-100 flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
      <!-- Logo and Header -->
      <div class="text-center">
        <router-link to="/" class="inline-block">
          <h1 class="text-3xl font-bold bg-gradient-to-r from-blue-600 to-indigo-600 bg-clip-text text-transparent">
            {{ appTitle }}
          </h1>
        </router-link>
        <h2 class="mt-6 text-2xl font-semibold text-gray-900">
          Set new password
        </h2>
        <p class="mt-2 text-sm text-gray-600">
          Please enter your new password below.
        </p>
      </div>

      <!-- Form -->
      <form @submit.prevent="handleSubmit" class="mt-8 space-y-6">
        <div class="bg-white rounded-2xl shadow-xl p-8 space-y-6">
          <!-- Error Message -->
          <div v-if="errorMessage" class="bg-red-50 border border-red-200 rounded-lg p-4">
            <div class="flex items-center">
              <ExclamationCircleIcon class="h-5 w-5 text-red-500 mr-2" />
              <p class="text-sm text-red-800">{{ errorMessage }}</p>
            </div>
          </div>

          <!-- Invalid Token Message -->
          <div v-if="!token" class="bg-red-50 border border-red-200 rounded-lg p-4">
            <div class="flex items-center">
              <ExclamationCircleIcon class="h-5 w-5 text-red-500 mr-2" />
              <p class="text-sm text-red-800">Invalid or missing reset token. Please request a new password reset.</p>
            </div>
          </div>

          <div v-if="token" class="space-y-6">
            <!-- New Password Field -->
            <div>
              <label for="newPassword" class="block text-sm font-medium text-gray-700 mb-2">
                New password
              </label>
              <div class="relative">
                <input
                  id="newPassword"
                  v-model="newPassword"
                  :type="showPassword ? 'text' : 'password'"
                  required
                  minlength="8"
                  class="w-full px-4 py-3 pr-12 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                  placeholder="Enter your new password"
                  :disabled="loading"
                />
                <button
                  type="button"
                  @click="showPassword = !showPassword"
                  class="absolute inset-y-0 right-0 pr-3 flex items-center"
                >
                  <EyeIcon v-if="!showPassword" class="h-5 w-5 text-gray-400 hover:text-gray-600" />
                  <EyeSlashIcon v-else class="h-5 w-5 text-gray-400 hover:text-gray-600" />
                </button>
              </div>
              <p class="mt-1 text-xs text-gray-500">
                Password must be at least 8 characters long
              </p>
            </div>

            <!-- Confirm Password Field -->
            <div>
              <label for="confirmPassword" class="block text-sm font-medium text-gray-700 mb-2">
                Confirm new password
              </label>
              <div class="relative">
                <input
                  id="confirmPassword"
                  v-model="confirmPassword"
                  :type="showConfirmPassword ? 'text' : 'password'"
                  required
                  minlength="8"
                  class="w-full px-4 py-3 pr-12 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                  placeholder="Confirm your new password"
                  :disabled="loading"
                />
                <button
                  type="button"
                  @click="showConfirmPassword = !showConfirmPassword"
                  class="absolute inset-y-0 right-0 pr-3 flex items-center"
                >
                  <EyeIcon v-if="!showConfirmPassword" class="h-5 w-5 text-gray-400 hover:text-gray-600" />
                  <EyeSlashIcon v-else class="h-5 w-5 text-gray-400 hover:text-gray-600" />
                </button>
              </div>
              <p v-if="confirmPassword && newPassword !== confirmPassword" class="mt-1 text-xs text-red-500">
                Passwords do not match
              </p>
            </div>

            <!-- Submit Button -->
            <button
              type="submit"
              :disabled="loading || !newPassword || !confirmPassword || newPassword !== confirmPassword"
              class="w-full flex justify-center py-3 px-4 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-200"
            >
              <ArrowPathIcon v-if="loading" class="animate-spin -ml-1 mr-3 h-5 w-5" />
              {{ loading ? 'Updating...' : 'Update Password' }}
            </button>
          </div>
        </div>

        <!-- Back to Login -->
        <div class="text-center">
          <router-link
            to="/login"
            class="text-sm text-blue-600 hover:text-blue-500 font-medium transition-colors"
          >
            ← Back to login
          </router-link>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { authAPI } from '@/services/api'
import { ExclamationCircleIcon, ArrowPathIcon, EyeIcon, EyeSlashIcon } from '@heroicons/vue/24/outline'

const route = useRoute()
const router = useRouter()

const token = ref<string | null>(null)
const newPassword = ref('')
const confirmPassword = ref('')
const showPassword = ref(false)
const showConfirmPassword = ref(false)
const loading = ref(false)
const errorMessage = ref('')

// App title from environment variable
const appTitle = computed(() => import.meta.env.VITE_APP_TITLE || 'Design Studio')

onMounted(() => {
  token.value = route.query.token as string || null
})

const handleSubmit = async () => {
  if (!token.value || !newPassword.value || newPassword.value !== confirmPassword.value) return

  loading.value = true
  errorMessage.value = ''

  try {
    await authAPI.resetPassword({
      token: token.value,
      newPassword: newPassword.value
    })
    
    // Success - redirect to login with success message
    router.push({
      name: 'Login',
      query: { message: 'Password reset successful. Please log in with your new password.' }
    })
  } catch (error: any) {
    errorMessage.value = error.response?.data?.message || 'An error occurred. Please try again.'
  } finally {
    loading.value = false
  }
}
</script>

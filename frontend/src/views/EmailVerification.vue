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
          Email Verification
        </h2>
      </div>

      <div class="bg-white rounded-2xl shadow-xl p-8 space-y-6">
        <!-- Loading State -->
        <div v-if="loading" class="text-center">
          <ArrowPathIcon class="animate-spin mx-auto h-12 w-12 text-blue-500" />
          <p class="mt-4 text-gray-600">Verifying your email...</p>
        </div>

        <!-- Success State -->
        <div v-else-if="verified" class="text-center">
          <CheckCircleIcon class="mx-auto h-12 w-12 text-green-500" />
          <h3 class="mt-4 text-lg font-semibold text-gray-900">Email Verified!</h3>
          <p class="mt-2 text-gray-600">Your email has been successfully verified. You can now access all features.</p>
          <router-link
            to="/login"
            class="mt-6 inline-block bg-gradient-to-r from-blue-600 to-indigo-600 text-white px-6 py-3 rounded-lg font-medium hover:from-blue-700 hover:to-indigo-700 transition-all duration-200"
          >
            Continue to Login
          </router-link>
        </div>

        <!-- Error State -->
        <div v-else class="text-center">
          <XCircleIcon class="mx-auto h-12 w-12 text-red-500" />
          <h3 class="mt-4 text-lg font-semibold text-gray-900">Verification Failed</h3>
          <p class="mt-2 text-gray-600">{{ errorMessage || 'Invalid or expired verification token.' }}</p>
          
          <!-- Resend Verification Form -->
          <div class="mt-6 space-y-4">
            <div>
              <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                Email address
              </label>
              <input
                id="email"
                v-model="email"
                type="email"
                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                placeholder="Enter your email address"
                :disabled="resending"
              />
            </div>
            <button
              @click="resendVerification"
              :disabled="resending || !email"
              class="w-full bg-blue-600 text-white py-3 px-4 rounded-lg font-medium hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
            >
              <ArrowPathIcon v-if="resending" class="animate-spin -ml-1 mr-3 h-5 w-5 inline" />
              {{ resending ? 'Sending...' : 'Resend Verification Email' }}
            </button>
          </div>
          
          <router-link
            to="/login"
            class="mt-4 inline-block text-sm text-blue-600 hover:text-blue-500 transition-colors"
          >
            ← Back to login
          </router-link>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { useRoute } from 'vue-router'
import { authAPI } from '@/services/api'
import { CheckCircleIcon, XCircleIcon, ArrowPathIcon } from '@heroicons/vue/24/outline'

const route = useRoute()

const loading = ref(true)
const verified = ref(false)
const errorMessage = ref('')
const email = ref('')
const resending = ref(false)

// App title from environment
const appTitle = computed(() => import.meta.env.VITE_APP_TITLE || 'Design Studio')

const verifyEmail = async (token: string) => {
  try {
    await authAPI.verifyEmail(token)
    verified.value = true
  } catch (error: any) {
    errorMessage.value = error.response?.data?.message || 'Verification failed'
  } finally {
    loading.value = false
  }
}

const resendVerification = async () => {
  if (!email.value) return
  
  resending.value = true
  try {
    await authAPI.resendVerification(email.value)
    errorMessage.value = 'A new verification email has been sent to your inbox.'
  } catch (error: any) {
    errorMessage.value = error.response?.data?.message || 'Failed to resend verification email'
  } finally {
    resending.value = false
  }
}

onMounted(() => {
  const token = route.query.token as string
  if (token) {
    verifyEmail(token)
  } else {
    loading.value = false
    errorMessage.value = 'No verification token provided'
  }
})
</script>

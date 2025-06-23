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
          Reset your password
        </h2>
        <p class="mt-2 text-sm text-gray-600">
          Enter your email address and we'll send you a link to reset your password.
        </p>
      </div>

      <!-- Form -->
      <form @submit.prevent="handleSubmit" class="mt-8 space-y-6">
        <div class="bg-white rounded-2xl shadow-xl p-8 space-y-6">
          <!-- Success Message -->
          <div v-if="successMessage" class="bg-green-50 border border-green-200 rounded-lg p-4">
            <div class="flex items-center">
              <CheckCircleIcon class="h-5 w-5 text-green-500 mr-2" />
              <p class="text-sm text-green-800">{{ successMessage }}</p>
            </div>
          </div>

          <!-- Error Message -->
          <div v-if="errorMessage" class="bg-red-50 border border-red-200 rounded-lg p-4">
            <div class="flex items-center">
              <ExclamationCircleIcon class="h-5 w-5 text-red-500 mr-2" />
              <p class="text-sm text-red-800">{{ errorMessage }}</p>
            </div>
          </div>

          <!-- Email Field -->
          <div>
            <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
              Email address
            </label>
            <input
              id="email"
              v-model="email"
              type="email"
              required
              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
              placeholder="Enter your email address"
              :disabled="loading || submitted"
            />
          </div>

          <!-- Submit Button -->
          <button
            type="submit"
            :disabled="loading || submitted || !email"
            class="w-full flex justify-center py-3 px-4 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-200"
          >
            <ArrowPathIcon v-if="loading" class="animate-spin -ml-1 mr-3 h-5 w-5" />
            {{ loading ? 'Sending...' : submitted ? 'Email Sent' : 'Send Reset Link' }}
          </button>
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
import { ref, computed } from 'vue'
import { authAPI } from '@/services/api'
import { CheckCircleIcon, ExclamationCircleIcon, ArrowPathIcon } from '@heroicons/vue/24/outline'

const email = ref('')
const loading = ref(false)
const submitted = ref(false)
const errorMessage = ref('')
const successMessage = ref('')

// App title from environment variable
const appTitle = computed(() => import.meta.env.VITE_APP_TITLE || 'Design Studio')

const handleSubmit = async () => {
  if (!email.value) return

  loading.value = true
  errorMessage.value = ''
  successMessage.value = ''

  try {
    await authAPI.forgotPassword(email.value)
    submitted.value = true
    successMessage.value = 'If your email exists, a reset link has been sent to your inbox.'
  } catch (error: any) {
    errorMessage.value = error.response?.data?.message || 'An error occurred. Please try again.'
  } finally {
    loading.value = false
  }
}
</script>

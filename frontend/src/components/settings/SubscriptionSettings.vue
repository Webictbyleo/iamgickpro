<template>
  <div class="space-y-8">
    <!-- Section Header -->
    <div class="text-center">
      <h2 class="text-2xl font-bold bg-gradient-to-r from-gray-900 to-gray-700 bg-clip-text text-transparent">
        Subscription Plans
      </h2>
      <p class="text-gray-600 mt-2">
        Manage your subscription and view plan details
      </p>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="flex items-center justify-center py-12">
      <div class="w-8 h-8 border-2 border-indigo-500 border-t-transparent rounded-full animate-spin"></div>
      <span class="ml-3 text-gray-600">Loading subscription data...</span>
    </div>

    <!-- Content -->
    <div v-else class="space-y-8">
      <!-- Current Plan -->
      <div class="bg-gradient-to-br from-indigo-50 to-violet-50 rounded-2xl p-8 border border-indigo-100">
        <div class="flex items-start justify-between mb-6">
          <div>
            <div class="flex items-center space-x-3 mb-2">
              <div class="w-10 h-10 bg-gradient-to-br from-indigo-500 to-violet-600 rounded-xl flex items-center justify-center">
                <component :is="icons.star" class="w-5 h-5 text-white" />
              </div>
              <div>
                <h3 class="text-2xl font-bold text-gray-900">{{ subscriptionData?.planInfo?.name || 'Free' }} Plan</h3>
                <span v-if="subscriptionData?.isActive" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                  <component :is="icons.check" class="w-3 h-3 mr-1" />
                  Active
                </span>
                <span v-else class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                  Inactive
                </span>
              </div>
            </div>
            <p class="text-gray-600">{{ subscriptionData?.planInfo?.description || 'Basic plan with essential features' }}</p>
            <div v-if="subscriptionData?.planInfo?.price?.monthly && subscriptionData.planInfo.price.monthly > 0" class="mt-3">
              <span class="text-2xl font-bold text-gray-900">${{ subscriptionData.planInfo.price.monthly }}</span>
              <span class="text-gray-600">/month</span>
              <span v-if="subscriptionData.planInfo.price.yearly" class="ml-4 text-sm text-green-600">
                Save ${{ (subscriptionData.planInfo.price.monthly * 12 - subscriptionData.planInfo.price.yearly) }} yearly
              </span>
            </div>
          </div>
        </div>

        <!-- Usage Statistics -->
        <div v-if="subscriptionData" class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
          <div class="bg-white rounded-lg p-4 text-center">
            <div class="text-2xl font-bold text-indigo-600">{{ subscriptionData.usage.projects || 0 }}</div>
            <div class="text-sm text-gray-600">Projects</div>
            <div v-if="subscriptionData.limits.projects > 0" class="text-xs text-gray-500 mt-1">
              of {{ subscriptionData.limits.projects }}
            </div>
          </div>
          <div class="bg-white rounded-lg p-4 text-center">
            <div class="text-2xl font-bold text-indigo-600">{{ formatStorage(subscriptionData.usage.storageUsed || 0) }}</div>
            <div class="text-sm text-gray-600">Storage</div>
            <div v-if="subscriptionData.limits.storage > 0" class="text-xs text-gray-500 mt-1">
              of {{ formatStorage(subscriptionData.limits.storage) }}
            </div>
          </div>
          <div class="bg-white rounded-lg p-4 text-center">
            <div class="text-2xl font-bold text-indigo-600">{{ subscriptionData.usage.exportJobs || 0 }}</div>
            <div class="text-sm text-gray-600">Exports</div>
            <div v-if="subscriptionData.limits.exports > 0" class="text-xs text-gray-500 mt-1">
              of {{ subscriptionData.limits.exports }}
            </div>
          </div>
          <div class="bg-white rounded-lg p-4 text-center">
            <div class="text-2xl font-bold text-indigo-600">{{ subscriptionData.usage.mediaFiles || 0 }}</div>
            <div class="text-sm text-gray-600">Media Files</div>
          </div>
        </div>

        <!-- Plan Features -->
        <div v-if="subscriptionData?.features" class="bg-white rounded-xl p-6">
          <h4 class="text-lg font-semibold text-gray-900 mb-4">Plan Features</h4>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            <div v-for="(enabled, feature) in subscriptionData.features" :key="feature" class="flex items-center space-x-3">
              <component :is="enabled ? icons.check : icons.x" :class="enabled ? 'w-5 h-5 text-green-500' : 'w-5 h-5 text-gray-400'" />
              <span :class="enabled ? 'text-sm text-gray-700' : 'text-sm text-gray-400'">{{ formatFeatureName(feature) }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useIcons } from '@/composables/useIcons'
import { userAPI } from '@/services/api'
import { useAuthStore } from '@/stores/auth'
import type { UserSubscription } from '@/types'

const icons = useIcons()

// State
const loading = ref(true)
const subscriptionData = ref<UserSubscription | null>(null)

// Methods
const fetchSubscriptionData = async () => {
  try {
    const response = await userAPI.getSubscription()
    subscriptionData.value = response.data.data
  } catch (error) {
    console.error('Failed to fetch subscription data:', error)
  } finally {
    loading.value = false
  }
}

const formatDate = (dateString: string): string => {
  const date = new Date(dateString)
  return date.toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  })
}

const formatStorage = (bytes: number): string => {
  if (bytes === 0) return '0 B'
  const k = 1024
  const sizes = ['B', 'KB', 'MB', 'GB', 'TB']
  const i = Math.floor(Math.log(bytes) / Math.log(k))
  return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i]
}

const formatFeatureName = (feature: string): string => {
  return feature
    .split('_')
    .map(word => word.charAt(0).toUpperCase() + word.slice(1))
    .join(' ')
}

// Load data on component mount
onMounted(() => {
  fetchSubscriptionData()
})
</script>

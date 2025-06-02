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
                <h3 class="text-2xl font-bold text-gray-900">{{ currentPlan.name }}</h3>
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                  <component :is="icons.check" class="w-3 h-3 mr-1" />
                  Active
                </span>
              </div>
            </div>
            <p class="text-gray-600">{{ currentPlan.description }}</p>
          </div>
        
      </div>

      <!-- Plan Features -->
      <div class="bg-white rounded-xl p-6 mb-6">
        <h4 class="text-lg font-semibold text-gray-900 mb-4">Plan Features</h4>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
          <div v-for="feature in currentPlan.features" :key="feature.name" class="flex items-center space-x-3">
            <component :is="icons.check" class="w-5 h-5 text-green-500 flex-shrink-0" />
            <span class="text-sm text-gray-700">{{ feature.name }}</span>
          </div>
        </div>
      </div>

      <!-- Usage Statistics -->
      <div class="bg-white rounded-xl p-6">
        <h4 class="text-lg font-semibold text-gray-900 mb-4">Current Usage</h4>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
          <div v-for="usage in usageStats" :key="usage.label" class="text-center">
            <div v-if="usage.limit !== 'unlimited'" class="relative w-16 h-16 mx-auto mb-3">
              <svg class="w-16 h-16 transform -rotate-90" viewBox="0 0 36 36">
                <path
                  class="text-gray-200"
                  d="M18 2.0845
                    a 15.9155 15.9155 0 0 1 0 31.831
                    a 15.9155 15.9155 0 0 1 0 -31.831"
                  fill="none"
                  stroke="currentColor"
                  stroke-width="2"
                />
                <path
                  class="text-indigo-500"
                  :stroke-dasharray="`${usage.percentage || 0}, 100`"
                  d="M18 2.0845
                    a 15.9155 15.9155 0 0 1 0 31.831
                    a 15.9155 15.9155 0 0 1 0 -31.831"
                  fill="none"
                  stroke="currentColor"
                  stroke-width="2"
                />
              </svg>
              <div class="absolute inset-0 flex items-center justify-center">
                <span class="text-sm font-bold text-gray-900">{{ usage.percentage || 0 }}%</span>
              </div>
            </div>
            <div v-else class="w-16 h-16 mx-auto mb-3 flex items-center justify-center bg-green-100 rounded-full">
              <span class="text-xl font-bold text-green-600">∞</span>
            </div>
            <p class="text-sm font-medium text-gray-900">{{ usage.used }}/{{ usage.limit }}</p>
            <p class="text-xs text-gray-500">{{ usage.label }}</p>
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

const icons = useIcons()

// Types
interface Plan {
  id: string
  name: string
  description: string
  price: number
  interval: string
  features: Array<{
    name: string
    included: boolean
  }>
}

interface UsageStat {
  label: string
  used: number
  limit: number | 'unlimited'
  percentage?: number
}

interface SubscriptionData {
  plan: string
  isActive: boolean
  usage: {
    projects: number
    mediaFiles: number
    exportJobs: number
  }
  limits: {
    projects: number
    storage: number
    exports: number
  }
}

// State
const loading = ref(true)
const currentPlan = ref<Plan>({
  id: 'free',
  name: 'Free Plan',
  description: 'Perfect for getting started with basic design needs',
  price: 0,
  interval: 'month',
  features: []
})

const usageStats = ref<UsageStat[]>([])

// Plan configurations
const planConfigs: Record<string, Omit<Plan, 'id'>> = {
  free: {
    name: 'Free Plan',
    description: 'Perfect for getting started with basic design needs',
    price: 0,
    interval: 'month',
    features: [
      { name: 'Up to 5 designs', included: true },
      { name: 'Basic templates', included: true },
      { name: 'Standard exports', included: true },
      { name: '100MB storage', included: true },
      { name: 'Community support', included: true },
      { name: 'Collaboration tools', included: false },
      { name: 'Premium templates', included: false },
      { name: 'Priority support', included: false }
    ]
  },
  pro: {
    name: 'Pro Plan',
    description: 'Perfect for professional designers and creative teams',
    price: 29,
    interval: 'month',
    features: [
      { name: 'Unlimited designs', included: true },
      { name: 'HD exports (up to 4K)', included: true },
      { name: 'Premium templates library', included: true },
      { name: 'Collaboration tools', included: true },
      { name: 'Priority support', included: true },
      { name: 'Advanced editing tools', included: true },
      { name: 'Cloud storage (10GB)', included: true },
      { name: 'Brand kit management', included: true }
    ]
  },
  business: {
    name: 'Business Plan',
    description: 'Perfect for teams and businesses with advanced needs',
    price: 99,
    interval: 'month',
    features: [
      { name: 'Everything in Pro', included: true },
      { name: 'Unlimited team members', included: true },
      { name: 'Advanced collaboration', included: true },
      { name: 'Custom templates', included: true },
      { name: 'API access', included: true },
      { name: 'Dedicated support', included: true },
      { name: 'Cloud storage (100GB)', included: true },
      { name: 'Advanced analytics', included: true }
    ]
  }
}

// Methods
const fetchSubscriptionData = async () => {
  try {
    const response = await userAPI.getSubscription()
    
    // Update current plan
    const planConfig = planConfigs[response.plan] || planConfigs.free
    currentPlan.value = {
      id: response.plan,
      ...planConfig
    }
    
    // Calculate usage stats
    const storageUsed = Math.round(response.usage.mediaFiles * 2.5) // Approximate MB per media file
    const storageLimit = response.limits.storage / (1024 * 1024) // Convert bytes to MB
    
    usageStats.value = [
      {
        label: 'Designs Created',
        used: response.usage.projects,
        limit: response.limits.projects === -1 ? 'unlimited' : response.limits.projects,
        percentage: response.limits.projects === -1 ? undefined : Math.round((response.usage.projects / response.limits.projects) * 100)
      },
      {
        label: 'Storage Used (MB)',
        used: storageUsed,
        limit: response.limits.storage === -1 ? 'unlimited' : storageLimit,
        percentage: response.limits.storage === -1 ? undefined : Math.round((storageUsed / storageLimit) * 100)
      },
      {
        label: 'Exports This Month',
        used: response.usage.exportJobs,
        limit: response.limits.exports === -1 ? 'unlimited' : response.limits.exports,
        percentage: response.limits.exports === -1 ? undefined : Math.round((response.usage.exportJobs / response.limits.exports) * 100)
      }
    ]
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

// Load data on component mount
onMounted(() => {
  fetchSubscriptionData()
})
</script>

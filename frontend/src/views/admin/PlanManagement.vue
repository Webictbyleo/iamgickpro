<template>
  <AppLayout title="Plan Management" subtitle="Manage subscription plans, pricing, and features">
    <div class="max-w-7xl mx-auto">
      <!-- Header Controls -->
      <div class="mb-8">
        <div class="flex items-center justify-end">
          <div class="flex items-center space-x-4">
            <!-- Plan Stats -->
            <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-200">
              <div class="flex items-center space-x-4">
                <div class="text-center">
                  <div class="text-2xl font-bold text-blue-600">{{ stats.plans?.total || 0 }}</div>
                  <div class="text-xs text-gray-500">Total Plans</div>
                </div>
                <div class="text-center">
                  <div class="text-2xl font-bold text-green-600">{{ stats.plans?.active || 0 }}</div>
                  <div class="text-xs text-gray-500">Active Plans</div>
                </div>
                <div class="text-center">
                  <div class="text-2xl font-bold text-purple-600">{{ stats.subscriptions?.active || 0 }}</div>
                  <div class="text-xs text-gray-500">Active Subscriptions</div>
                </div>
              </div>
            </div>
            <button
              @click="showCreatePlanModal = true"
              class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors flex items-center space-x-2"
            >
              <PlusIcon class="w-5 h-5" />
              <span>Create Plan</span>
            </button>
          </div>
        </div>
      </div>

      <!-- Plans Grid -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
        <!-- Loading State -->
        <div v-if="loading" class="col-span-full">
          <div class="bg-white rounded-xl p-8 text-center shadow-sm border border-gray-200">
            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto"></div>
            <p class="mt-4 text-gray-600">Loading plans...</p>
          </div>
        </div>

        <!-- Plan Cards -->
        <div v-else-if="plans.length > 0" v-for="plan in plans" :key="plan.id" class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
          <div class="p-6">
            <!-- Plan Header -->
            <div class="flex items-center justify-between mb-4">
              <div>
                <h3 class="text-xl font-semibold text-gray-900">{{ plan.name }}</h3>
                <p class="text-sm text-gray-500">{{ plan.description || 'No description' }}</p>
              </div>
              <div class="flex items-center space-x-2">
                <span :class="plan.is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'" 
                      class="px-2 py-1 text-xs font-medium rounded-full">
                  {{ plan.is_active ? 'Active' : 'Inactive' }}
                </span>
                <button
                  @click="editPlan(plan)"
                  class="p-1 text-gray-400 hover:text-blue-600 transition-colors"
                >
                  <PencilIcon class="w-4 h-4" />
                </button>
              </div>
            </div>

            <!-- Pricing -->
            <div class="mb-4">
              <div class="flex items-baseline">
                <span class="text-3xl font-bold text-gray-900">${{ formatPrice(plan.monthly_price || 0) }}</span>
                <span class="ml-1 text-gray-500">/ month</span>
              </div>
              <p v-if="plan.yearly_price && parseFloat(plan.monthly_price || '0') > 0" class="text-sm text-blue-600 mt-1">
                ${{ formatPrice(plan.yearly_price) }}/year (save {{ calculateSavings(plan.monthly_price, plan.yearly_price) }}%)
              </p>
              <p v-else-if="parseFloat(plan.monthly_price || '0') === 0" class="text-sm text-green-600 mt-1">
                Free Plan - No charge
              </p>
            </div>

            <!-- Features -->
            <div class="mb-6">
              <h4 class="text-sm font-medium text-gray-900 mb-3">Features</h4>
              <ul class="space-y-2">
                <li v-for="(isEnabled, featureName) in plan.features" :key="String(featureName)" class="flex items-center text-sm">
                  <CheckCircleIcon v-if="isEnabled" class="w-4 h-4 mr-2 flex-shrink-0 text-green-500" />
                  <XMarkIcon v-else class="w-4 h-4 mr-2 flex-shrink-0 text-red-500" />
                  <span :class="isEnabled ? 'text-gray-700' : 'text-gray-400 line-through'">
                    {{ formatFeatureName(String(featureName)) }}
                  </span>
                </li>
                <li v-if="!plan.features || Object.keys(plan.features).length === 0" class="text-sm text-gray-400 italic flex items-center">
                  <XMarkIcon class="w-4 h-4 mr-2 text-gray-300" />
                  No features configured
                </li>
              </ul>
            </div>

            <!-- Limits -->
            <div class="mb-6">
              <h4 class="text-sm font-medium text-gray-900 mb-3">Plan Limits</h4>
              <div class="space-y-2 text-sm">
                <div class="flex justify-between">
                  <span class="text-gray-600">Projects:</span>
                  <span class="font-medium">{{ formatLimit(plan.limits?.projects) }}</span>
                </div>
                <div class="flex justify-between">
                  <span class="text-gray-600">Storage:</span>
                  <span class="font-medium">{{ formatStorage(plan.limits?.storage) }}</span>
                </div>
                <div class="flex justify-between">
                  <span class="text-gray-600">Monthly Exports:</span>
                  <span class="font-medium">{{ formatLimit(plan.limits?.exports) }}</span>
                </div>
                <div class="flex justify-between">
                  <span class="text-gray-600">Team Members:</span>
                  <span class="font-medium">{{ formatLimit(plan.limits?.collaborators) }}</span>
                </div>
              </div>
            </div>

            <!-- Actions -->
            <div class="flex items-center justify-between pt-4 border-t border-gray-200">
              <div class="text-sm text-gray-500">
                {{ formatSubscriberCount(plan.subscriber_count) }}
              </div>
              <div class="flex items-center space-x-2">
                <button
                  @click="togglePlanStatus(plan)"
                  :class="plan.is_active ? 'text-red-600 hover:text-red-700' : 'text-green-600 hover:text-green-700'"
                  class="text-sm font-medium transition-colors"
                >
                  {{ plan.is_active ? 'Deactivate' : 'Activate' }}
                </button>
                <button
                  @click="deletePlan(plan)"
                  class="text-sm font-medium text-red-600 hover:text-red-700 transition-colors"
                >
                  Delete
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Empty State -->
        <div v-else class="col-span-full">
          <div class="bg-white rounded-xl p-8 text-center shadow-sm border border-gray-200">
            <CurrencyDollarIcon class="w-16 h-16 text-gray-400 mx-auto mb-4" />
            <h3 class="text-lg font-medium text-gray-900 mb-2">No plans created yet</h3>
            <p class="text-gray-600 mb-4">Create your first subscription plan to get started.</p>
            <button
              @click="showCreatePlanModal = true"
              class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors"
            >
              Create Plan
            </button>
          </div>
        </div>
      </div>

      <!-- Recent Activity -->
      <div class="bg-white rounded-xl shadow-sm border border-gray-200">
        <div class="p-6">
          <h3 class="text-lg font-semibold text-gray-900 mb-4">Recent Subscription Activity</h3>
          
          <!-- Activity List -->
          <div v-if="recentActivity.length > 0" class="space-y-4">
            <div v-for="activity in recentActivity" :key="activity.id" class="flex items-center justify-between py-3 border-b border-gray-100 last:border-b-0">
              <div class="flex items-center space-x-3">
                <div :class="getActivityIcon(activity.type).color" class="p-2 rounded-full">
                  <component :is="getActivityIcon(activity.type).icon" class="w-4 h-4 text-white" />
                </div>
                <div>
                  <p class="text-sm font-medium text-gray-900">{{ activity.description }}</p>
                  <p class="text-xs text-gray-500">{{ formatDate(activity.createdAt) }}</p>
                </div>
              </div>
              <div class="text-sm text-gray-600">
                {{ activity.planName }}
              </div>
            </div>
          </div>

          <!-- Empty Activity State -->
          <div v-else class="text-center py-8">
            <p class="text-gray-500">No recent subscription activity</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Plan Modal -->
    <PlanModal
      :is-open="showCreatePlanModal || !!editingPlan"
      :editing-plan="editingPlan"
      :submitting="submitting"
      @close="closeModal"
      @submit="savePlan"
    />
  </AppLayout>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useNotifications } from '@/composables/useNotifications'
import { adminAPI } from '@/services/api'
import type { AdminSubscriptionPlan, AdminPlatformStats } from '@/types'
import AppLayout from '@/components/layout/AppLayout.vue'
import PlanModal from './components/PlanModal.vue'

// Heroicons
import { 
  PlusIcon,
  CheckCircleIcon,
  XMarkIcon,
  PencilIcon,
  TrashIcon,
  CurrencyDollarIcon,
  UsersIcon,
  CloudArrowUpIcon,
  DocumentIcon
} from '@heroicons/vue/24/outline'

// Access control
const authStore = useAuthStore()
const router = useRouter()

const { showSuccess, showError } = useNotifications()

// Component state
const loading = ref(false)
const submitting = ref(false)
const showCreatePlanModal = ref(false)
const editingPlan = ref<AdminSubscriptionPlan | null>(null)

// Data
const plans = ref<AdminSubscriptionPlan[]>([])
const stats = ref<{
  plans: { total: number; active: number }
  subscriptions: { active: number }
}>({
  plans: { total: 0, active: 0 },
  subscriptions: { active: 0 }
})
const recentActivity = ref<any[]>([]) // TODO: Create proper type for activity

// Methods
const loadPlans = async () => {
  loading.value = true
  try {
    const response = await adminAPI.getPlans()
    plans.value = response.data.data.plans || []
    
    // Load stats as well
    await loadStats()
  } catch (error) {
    console.error('Failed to load plans:', error)
    showError('Failed to load plans')
  } finally {
    loading.value = false
  }
}

const loadStats = async () => {
  try {
    const response = await adminAPI.getPlatformStats()
    // Calculate plan stats from the plans data
    const totalPlans = plans.value.length
    const activePlans = plans.value.filter(p => p.is_active).length
    
    stats.value = {
      plans: {
        total: totalPlans,
        active: activePlans
      },
      subscriptions: {
        active: 0 // Will be implemented when subscription stats are available
      }
    }
  } catch (error) {
    console.error('Failed to load stats:', error)
    // Don't show error for stats as it's secondary data
  }
}

const editPlan = (plan: AdminSubscriptionPlan) => {
  editingPlan.value = plan
}

const closeModal = () => {
  showCreatePlanModal.value = false
  editingPlan.value = null
}

const savePlan = async (planData: any) => { // TODO: Create proper type for plan form data
  submitting.value = true
  try {
    if (editingPlan.value) {
      await adminAPI.updatePlan(editingPlan.value.id, planData)
      showSuccess('Plan updated successfully')
    } else {
      await adminAPI.createPlan(planData)
      showSuccess('Plan created successfully')
    }
    
    closeModal()
    await loadPlans()
  } catch (error) {
    console.error('Failed to save plan:', error)
    showError('Failed to save plan')
  } finally {
    submitting.value = false
  }
}

const togglePlanStatus = async (plan: AdminSubscriptionPlan) => {
  try {
    await adminAPI.updatePlan(plan.id, { 
      is_active: !plan.is_active 
    })
    plan.is_active = !plan.is_active
    showSuccess('Plan status updated successfully')
  } catch (error) {
    console.error('Failed to toggle plan status:', error)
    showError('Failed to update plan status')
  }
}

const deletePlan = async (plan: AdminSubscriptionPlan) => {
  if (!confirm(`Are you sure you want to delete the "${plan.name}" plan?`)) {
    return
  }

  try {
    await adminAPI.deletePlan(plan.id)
    showSuccess('Plan deleted successfully')
    await loadPlans()
  } catch (error) {
    console.error('Failed to delete plan:', error)
    showError('Failed to delete plan')
  }
}

const calculateSavings = (monthlyPrice: string | number, yearlyPrice: string | number): number => {
  const monthly = typeof monthlyPrice === 'string' ? parseFloat(monthlyPrice) : monthlyPrice
  const yearly = typeof yearlyPrice === 'string' ? parseFloat(yearlyPrice) : yearlyPrice
  
  // If monthly price is 0 or yearly price is 0, no savings calculation
  if (monthly === 0 || yearly === 0) return 0
  
  const annualMonthly = monthly * 12
  const savings = ((annualMonthly - yearly) / annualMonthly) * 100
  
  return Math.round(savings)
}

// Utility functions
const formatStorage = (bytes: number | null) => {
  if (!bytes || bytes === -1) return 'Unlimited'
  
  // Convert bytes to appropriate unit
  const units = [
    { name: 'B', size: 1 },
    { name: 'KB', size: 1024 },
    { name: 'MB', size: 1024 * 1024 },
    { name: 'GB', size: 1024 * 1024 * 1024 },
    { name: 'TB', size: 1024 * 1024 * 1024 * 1024 },
    { name: 'PB', size: 1024 * 1024 * 1024 * 1024 * 1024 }
  ]
  
  // Find the appropriate unit
  let unit = units[0]
  for (let i = units.length - 1; i >= 0; i--) {
    if (bytes >= units[i].size) {
      unit = units[i]
      break
    }
  }
  
  const size = bytes / unit.size
  
  // Smart formatting based on size
  if (size >= 1000) {
    return `${Math.round(size).toLocaleString()} ${unit.name}`
  } else if (size >= 100) {
    return `${Math.round(size)} ${unit.name}`
  } else if (size >= 10) {
    // Only show decimal if it's not .0
    const rounded = Math.round(size * 10) / 10
    return rounded % 1 === 0 ? `${Math.round(rounded)} ${unit.name}` : `${rounded} ${unit.name}`
  } else {
    // Show up to 2 decimals, but remove trailing zeros
    const rounded = Math.round(size * 100) / 100
    return rounded % 1 === 0 ? `${Math.round(rounded)} ${unit.name}` : `${rounded} ${unit.name}`
  }
}

const formatLimit = (value: number | null | undefined) => {
  if (!value || value === -1) return 'Unlimited'
  
  // Format large numbers with commas
  if (value >= 1000) {
    return value.toLocaleString()
  }
  
  return value.toString()
}

const formatPrice = (price: string | number) => {
  const numPrice = typeof price === 'string' ? parseFloat(price) : price
  // Format price with proper decimal places
  return numPrice % 1 === 0 ? numPrice.toString() : numPrice.toFixed(2)
}

const formatSubscriberCount = (count: number | undefined) => {
  if (count === undefined || count === null) {
    return 'No subscriber data'
  }
  
  if (count === 0) {
    return 'No subscribers'
  }
  
  if (count === 1) {
    return '1 subscriber'
  }
  
  return `${count.toLocaleString()} subscribers`
}

const formatDate = (dateString: string) => {
  return new Date(dateString).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

const getActivityIcon = (type: string) => {
  switch (type) {
    case 'subscription':
      return { icon: PlusIcon, color: 'bg-green-500' }
    case 'cancellation':
      return { icon: XMarkIcon, color: 'bg-red-500' }
    default:
      return { icon: DocumentIcon, color: 'bg-blue-500' }
  }
}

const formatFeatureName = (featureName: string): string => {
  return featureName
    .replace(/_/g, ' ')
    .replace(/\b\w/g, l => l.toUpperCase())
}

// Lifecycle
onMounted(() => {
  // Check admin access on component load
  setTimeout(() => {
    if (!authStore.isAdmin) {
      router.push({ name: 'Dashboard' })
      return
    }
    loadPlans()
  }, 100)
})
</script>

<style scoped>
/* Add any component-specific styles here */
</style>

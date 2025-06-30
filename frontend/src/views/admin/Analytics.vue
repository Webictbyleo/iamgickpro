<template>
  <AppLayout title="Analytics & Reports" subtitle="Monitor platform performance and user engagement">
    <div class="max-w-7xl mx-auto">
      <!-- Header -->
      <div class="mb-8">
        <div class="flex items-center justify-between">
          <div>
            <h1 class="text-3xl font-bold text-gray-900">Analytics & Reports</h1>
            <p class="mt-2 text-gray-600">Monitor platform performance and user engagement</p>
          </div>
          <div class="flex items-center space-x-4">
            <!-- Date Range Selector -->
            <select
              v-model="selectedPeriod"
              @change="loadAnalytics"
              class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            >
              <option value="7d">Last 7 days</option>
              <option value="30d">Last 30 days</option>
              <option value="90d">Last 90 days</option>
              <option value="1y">Last year</option>
            </select>
            <button
              @click="exportReport"
              class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors flex items-center space-x-2"
            >
              <ArrowDownTrayIcon class="w-5 h-5" />
              <span>Export Report</span>
            </button>
          </div>
        </div>
      </div>

      <!-- Key Metrics Cards -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <!-- Total Users -->
        <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-200">
          <div class="flex items-center">
            <div class="p-3 rounded-full bg-blue-100">
              <UsersIcon class="w-8 h-8 text-blue-600" />
            </div>
            <div class="ml-4">
              <p class="text-sm font-medium text-gray-600">Total Users</p>
              <p class="text-2xl font-bold text-gray-900">{{ formatNumber(metrics.totalUsers) }}</p>
              <p :class="metrics.userGrowth >= 0 ? 'text-green-600' : 'text-red-600'" class="text-sm">
                {{ metrics.userGrowth >= 0 ? '+' : '' }}{{ metrics.userGrowth }}% from last period
              </p>
            </div>
          </div>
        </div>

        <!-- Active Subscriptions -->
        <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-200">
          <div class="flex items-center">
            <div class="p-3 rounded-full bg-green-100">
              <CurrencyDollarIcon class="w-8 h-8 text-green-600" />
            </div>
            <div class="ml-4">
              <p class="text-sm font-medium text-gray-600">Active Subscriptions</p>
              <p class="text-2xl font-bold text-gray-900">{{ formatNumber(metrics.activeSubscriptions) }}</p>
              <p :class="metrics.subscriptionGrowth >= 0 ? 'text-green-600' : 'text-red-600'" class="text-sm">
                {{ metrics.subscriptionGrowth >= 0 ? '+' : '' }}{{ metrics.subscriptionGrowth }}% from last period
              </p>
            </div>
          </div>
        </div>

        <!-- Revenue -->
        <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-200">
          <div class="flex items-center">
            <div class="p-3 rounded-full bg-purple-100">
              <CurrencyDollarIcon class="w-8 h-8 text-purple-600" />
            </div>
            <div class="ml-4">
              <p class="text-sm font-medium text-gray-600">Monthly Revenue</p>
              <p class="text-2xl font-bold text-gray-900">${{ formatNumber(metrics.monthlyRevenue) }}</p>
              <p :class="metrics.revenueGrowth >= 0 ? 'text-green-600' : 'text-red-600'" class="text-sm">
                {{ metrics.revenueGrowth >= 0 ? '+' : '' }}{{ metrics.revenueGrowth }}% from last period
              </p>
            </div>
          </div>
        </div>

        <!-- Projects Created -->
        <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-200">
          <div class="flex items-center">
            <div class="p-3 rounded-full bg-yellow-100">
              <DocumentDuplicateIcon class="w-8 h-8 text-yellow-600" />
            </div>
            <div class="ml-4">
              <p class="text-sm font-medium text-gray-600">Projects Created</p>
              <p class="text-2xl font-bold text-gray-900">{{ formatNumber(metrics.projectsCreated) }}</p>
              <p :class="metrics.projectGrowth >= 0 ? 'text-green-600' : 'text-red-600'" class="text-sm">
                {{ metrics.projectGrowth >= 0 ? '+' : '' }}{{ metrics.projectGrowth }}% from last period
              </p>
            </div>
          </div>
        </div>
      </div>

      <!-- Charts Section -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
        <!-- User Registration Chart -->
        <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-200">
          <div class="flex items-center justify-between mb-6">
            <h3 class="text-lg font-semibold text-gray-900">User Registrations</h3>
            <div class="text-sm text-gray-500">{{ selectedPeriod }}</div>
          </div>
          
          <!-- Simple Chart Placeholder -->
          <div class="h-64 bg-gray-100 rounded-lg flex items-center justify-center">
            <div class="text-center">
              <ChartBarIcon class="w-12 h-12 text-gray-400 mx-auto mb-2" />
              <p class="text-gray-500">Chart visualization would go here</p>
              <p class="text-xs text-gray-400 mt-1">Integration with Chart.js or similar library</p>
            </div>
          </div>
        </div>

        <!-- Revenue Chart -->
        <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-200">
          <div class="flex items-center justify-between mb-6">
            <h3 class="text-lg font-semibold text-gray-900">Revenue Trends</h3>
            <div class="text-sm text-gray-500">{{ selectedPeriod }}</div>
          </div>
          
          <!-- Simple Chart Placeholder -->
          <div class="h-64 bg-gray-100 rounded-lg flex items-center justify-center">
            <div class="text-center">
              <ChartBarIcon class="w-12 h-12 text-gray-400 mx-auto mb-2" />
              <p class="text-gray-500">Chart visualization would go here</p>
              <p class="text-xs text-gray-400 mt-1">Integration with Chart.js or similar library</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Detailed Analytics Tables -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Top Plans by Revenue -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200">
          <div class="p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Top Plans by Revenue</h3>
            
            <div v-if="loading" class="py-8 text-center">
              <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mx-auto"></div>
              <p class="mt-2 text-gray-600">Loading...</p>
            </div>

            <div v-else class="space-y-4">
              <div v-for="plan in topPlans" :key="plan.name" class="flex items-center justify-between py-3 border-b border-gray-100 last:border-b-0">
                <div class="flex items-center space-x-3">
                  <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-purple-600 rounded-lg flex items-center justify-center">
                    <span class="text-white font-medium text-sm">{{ plan.name.charAt(0) }}</span>
                  </div>
                  <div>
                    <p class="font-medium text-gray-900">{{ plan.name }}</p>
                    <p class="text-sm text-gray-500">{{ plan.subscribers }} subscribers</p>
                  </div>
                </div>
                <div class="text-right">
                  <p class="font-semibold text-gray-900">${{ formatNumber(plan.revenue) }}</p>
                  <p class="text-sm text-gray-500">{{ plan.percentage }}% of total</p>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- User Activity -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200">
          <div class="p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">User Activity Insights</h3>
            
            <div class="space-y-4">
              <!-- Daily Active Users -->
              <div class="flex items-center justify-between py-3 border-b border-gray-100">
                <div class="flex items-center space-x-3">
                  <div class="w-2 h-2 bg-green-500 rounded-full"></div>
                  <span class="text-gray-700">Daily Active Users</span>
                </div>
                <span class="font-semibold text-gray-900">{{ formatNumber(activityMetrics.dailyActiveUsers) }}</span>
              </div>

              <!-- Weekly Active Users -->
              <div class="flex items-center justify-between py-3 border-b border-gray-100">
                <div class="flex items-center space-x-3">
                  <div class="w-2 h-2 bg-blue-500 rounded-full"></div>
                  <span class="text-gray-700">Weekly Active Users</span>
                </div>
                <span class="font-semibold text-gray-900">{{ formatNumber(activityMetrics.weeklyActiveUsers) }}</span>
              </div>

              <!-- Average Session Duration -->
              <div class="flex items-center justify-between py-3 border-b border-gray-100">
                <div class="flex items-center space-x-3">
                  <div class="w-2 h-2 bg-purple-500 rounded-full"></div>
                  <span class="text-gray-700">Avg. Session Duration</span>
                </div>
                <span class="font-semibold text-gray-900">{{ activityMetrics.avgSessionDuration }}m</span>
              </div>

              <!-- Projects per User -->
              <div class="flex items-center justify-between py-3 border-b border-gray-100">
                <div class="flex items-center space-x-3">
                  <div class="w-2 h-2 bg-yellow-500 rounded-full"></div>
                  <span class="text-gray-700">Avg. Projects per User</span>
                </div>
                <span class="font-semibold text-gray-900">{{ activityMetrics.projectsPerUser }}</span>
              </div>

              <!-- Exports per User -->
              <div class="flex items-center justify-between py-3">
                <div class="flex items-center space-x-3">
                  <div class="w-2 h-2 bg-red-500 rounded-full"></div>
                  <span class="text-gray-700">Avg. Exports per User</span>
                </div>
                <span class="font-semibold text-gray-900">{{ activityMetrics.exportsPerUser }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- System Performance Metrics -->
      <div class="mt-8 bg-white rounded-xl shadow-sm border border-gray-200">
        <div class="p-6">
          <h3 class="text-lg font-semibold text-gray-900 mb-6">System Performance</h3>
          
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <!-- API Response Time -->
            <div class="text-center">
              <div class="text-3xl font-bold text-green-600 mb-2">{{ systemMetrics.apiResponseTime }}ms</div>
              <div class="text-sm text-gray-600">Avg API Response Time</div>
              <div class="mt-1 text-xs text-gray-500">
                <span :class="systemMetrics.apiResponseTime < 200 ? 'text-green-600' : 'text-yellow-600'">
                  {{ systemMetrics.apiResponseTime < 200 ? 'Excellent' : 'Good' }}
                </span>
              </div>
            </div>

            <!-- Error Rate -->
            <div class="text-center">
              <div class="text-3xl font-bold text-blue-600 mb-2">{{ systemMetrics.errorRate }}%</div>
              <div class="text-sm text-gray-600">Error Rate</div>
              <div class="mt-1 text-xs text-gray-500">
                <span :class="systemMetrics.errorRate < 1 ? 'text-green-600' : 'text-red-600'">
                  {{ systemMetrics.errorRate < 1 ? 'Excellent' : 'Needs Attention' }}
                </span>
              </div>
            </div>

            <!-- Storage Usage -->
            <div class="text-center">
              <div class="text-3xl font-bold text-purple-600 mb-2">{{ systemMetrics.storageUsage }}%</div>
              <div class="text-sm text-gray-600">Storage Usage</div>
              <div class="mt-1 text-xs text-gray-500">
                <span :class="systemMetrics.storageUsage < 80 ? 'text-green-600' : 'text-yellow-600'">
                  {{ systemMetrics.storageUsage < 80 ? 'Good' : 'Monitor' }}
                </span>
              </div>
            </div>

            <!-- Uptime -->
            <div class="text-center">
              <div class="text-3xl font-bold text-green-600 mb-2">{{ systemMetrics.uptime }}%</div>
              <div class="text-sm text-gray-600">System Uptime</div>
              <div class="mt-1 text-xs text-gray-500">
                <span class="text-green-600">Excellent</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useNotifications } from '@/composables/useNotifications'
import { adminAPI } from '@/services/api'
import AppLayout from '@/components/layout/AppLayout.vue'

// Heroicons
import { 
  ArrowDownTrayIcon,
  UsersIcon,
  CurrencyDollarIcon,
  DocumentDuplicateIcon,
  CloudArrowUpIcon,
  ChartBarIcon,
  ArrowTrendingUpIcon,
  ArrowTrendingDownIcon,
  EyeIcon,
  CalendarDaysIcon
} from '@heroicons/vue/24/outline'

// Access control
const authStore = useAuthStore()
const router = useRouter()

const { showSuccess, showError } = useNotifications()

// Component state
const loading = ref(false)
const selectedPeriod = ref('30d')

// Data - Initialize with empty/zero values, will be populated from API
const metrics = reactive({
  totalUsers: 0,
  userGrowth: 0,
  activeSubscriptions: 0,
  subscriptionGrowth: 0,
  monthlyRevenue: 0,
  revenueGrowth: 0,
  projectsCreated: 0,
  projectGrowth: 0,
})

const topPlans = ref<Array<{
  name: string
  subscribers: number
  revenue: number
  percentage: number
}>>([])

const activityMetrics = reactive({
  dailyActiveUsers: 0,
  weeklyActiveUsers: 0,
  avgSessionDuration: 0,
  projectsPerUser: 0,
  exportsPerUser: 0,
})

const systemMetrics = reactive({
  apiResponseTime: 0,
  errorRate: 0,
  storageUsage: 0,
  uptime: 0,
})

// Methods
const loadAnalytics = async () => {
  loading.value = true
  try {
    // Use platform stats as basic analytics for now
    const response = await adminAPI.getPlatformStats()
    const platformStats = response.data.data
    
    // Update metrics with platform stats data
    Object.assign(metrics, {
      totalUsers: platformStats.users?.total || 0,
      userGrowth: 0, // TODO: Calculate when historical data is available
      activeSubscriptions: 0, // TODO: Get from subscription stats
      subscriptionGrowth: 0, // TODO: Calculate when historical data is available
      monthlyRevenue: 0, // TODO: Calculate from subscription data
      revenueGrowth: 0, // TODO: Calculate when historical data is available
      projectsCreated: platformStats.designs?.total || 0,
      projectGrowth: 0, // TODO: Calculate when historical data is available
    })
    
    // Update activity metrics
    Object.assign(activityMetrics, {
      dailyActiveUsers: platformStats.users?.active || 0,
      weeklyActiveUsers: platformStats.users?.active || 0, // TODO: Get weekly stats
      avgSessionDuration: 0, // TODO: Get from analytics
      projectsPerUser: platformStats.designs?.total && platformStats.users?.total ? 
        parseFloat((platformStats.designs.total / platformStats.users.total).toFixed(1)) : 0,
      exportsPerUser: platformStats.exports?.total && platformStats.users?.total ? 
        parseFloat((platformStats.exports.total / platformStats.users.total).toFixed(1)) : 0,
    })
    
    // Update system metrics
    Object.assign(systemMetrics, {
      apiResponseTime: 0, // TODO: Get from monitoring
      errorRate: 0, // TODO: Get from monitoring  
      storageUsage: platformStats.storage?.used && platformStats.storage?.total ?
        Math.round((platformStats.storage.used / platformStats.storage.total) * 100) : 0,
      uptime: 0, // TODO: Get from monitoring
    })
    
    // TODO: Load top plans from subscription data when available
    topPlans.value = []
    
    showSuccess('Analytics data loaded successfully')
  } catch (error) {
    console.error('Failed to load analytics:', error)
    showError('Failed to load analytics data')
  } finally {
    loading.value = false
  }
}

const getStartDate = (period: string): string => {
  const now = new Date()
  switch (period) {
    case '7d':
      return new Date(now.getTime() - 7 * 24 * 60 * 60 * 1000).toISOString()
    case '30d':
      return new Date(now.getTime() - 30 * 24 * 60 * 60 * 1000).toISOString()
    case '90d':
      return new Date(now.getTime() - 90 * 24 * 60 * 60 * 1000).toISOString()
    case '1y':
      return new Date(now.getTime() - 365 * 24 * 60 * 60 * 1000).toISOString()
    default:
      return new Date(now.getTime() - 30 * 24 * 60 * 60 * 1000).toISOString()
  }
}

const exportReport = async () => {
  try {
    showSuccess('Report export started - this feature will be implemented soon')
    
    // In a real implementation, this would:
    // 1. Generate a report file (PDF/CSV)
    // 2. Download it to the user's device
  } catch (error) {
    console.error('Failed to export report:', error)
    showError('Failed to export report')
  }
}

const formatNumber = (num: number) => {
  if (num >= 1000000) {
    return (num / 1000000).toFixed(1) + 'M'
  } else if (num >= 1000) {
    return (num / 1000).toFixed(1) + 'K'
  } else {
    return num.toString()
  }
}

// Lifecycle
onMounted(() => {
  // Check admin access on component load
  setTimeout(() => {
    if (!authStore.isAdmin) {
      router.push({ name: 'Dashboard' })
      return
    }
    loadAnalytics()
  }, 100)
})
</script>

<style scoped>
/* Add any component-specific styles here */
</style>

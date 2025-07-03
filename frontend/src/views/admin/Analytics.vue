<template>
  <AppLayout title="Analytics & Reports" subtitle="Monitor platform performance and user engagement">
    <div class="max-w-7xl mx-auto">
      <!-- Header Controls -->
      <div class="mb-8">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
          <div class="flex items-center text-sm text-gray-600 dark:text-gray-400">
            <CalendarDaysIcon class="w-4 h-4 mr-1" />
            Last updated: {{ formatLastUpdated() }}
          </div>
          <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
            <!-- Auto Refresh Toggle -->
            <div class="flex items-center space-x-2">
              <label class="text-sm text-gray-700 dark:text-gray-300">Auto-refresh</label>
              <button
                @click="toggleAutoRefresh"
                :class="[
                  'relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 focus:ring-offset-white dark:focus:ring-offset-gray-900',
                  autoRefreshEnabled ? 'bg-primary-600' : 'bg-gray-200 dark:bg-gray-700'
                ]"
              >
                <span
                  :class="[
                    'pointer-events-none inline-block h-5 w-5 transform rounded-full shadow ring-0 transition duration-200 ease-in-out',
                    autoRefreshEnabled ? 'bg-white translate-x-5' : 'bg-white dark:bg-gray-100 translate-x-0'
                  ]"
                />
              </button>
            </div>

            <!-- Date Range Selector -->
            <select
              v-model="selectedPeriod"
              @change="loadAnalytics"
              :disabled="loading"
              class="px-3 py-2 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent disabled:bg-gray-100 dark:disabled:bg-gray-700 disabled:cursor-not-allowed min-w-0"
            >
              <option value="7d">Last 7 days</option>
              <option value="30d">Last 30 days</option>
              <option value="90d">Last 90 days</option>
              <option value="1y">Last year</option>
            </select>

            <!-- Action Buttons -->
            <div class="flex space-x-2">
              <button
                @click="loadAnalytics"
                :disabled="loading"
                class="px-3 py-2 border border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-800 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 focus:ring-offset-white dark:focus:ring-offset-gray-900 transition-colors flex items-center space-x-2 disabled:opacity-50 disabled:cursor-not-allowed"
              >
                <ArrowPathIcon class="w-4 h-4" :class="{ 'animate-spin': loading }" />
                <span class="hidden sm:inline">Refresh</span>
              </button>
              <button
                @click="exportReport"
                :disabled="loading"
                class="px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 focus:ring-offset-white dark:focus:ring-offset-gray-900 transition-colors flex items-center space-x-2 disabled:opacity-50 disabled:cursor-not-allowed"
              >
                <ArrowDownTrayIcon class="w-4 h-4" />
                <span class="hidden sm:inline">Export</span>
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Key Metrics Cards -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 lg:gap-6 mb-8">
        <!-- Total Users -->
        <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm border border-gray-200 dark:border-gray-700 hover:shadow-md transition-shadow duration-200 analytics-card fade-in">
          <div class="flex items-center">
            <div class="p-3 rounded-full bg-gradient-to-r from-primary-500 to-primary-600 shadow-lg">
              <UsersIcon class="w-6 h-6 text-white" />
            </div>
            <div class="ml-4 flex-1">
              <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Total Users</p>
              <p class="text-2xl font-bold text-gray-900 dark:text-white metric-value">{{ formatNumber(metrics.totalUsers) }}</p>
              <div class="flex items-center mt-1">
                <ArrowTrendingUpIcon v-if="metrics.userGrowth >= 0" class="w-4 h-4 text-success-600 dark:text-success-400 mr-1" />
                <ArrowTrendingDownIcon v-else class="w-4 h-4 text-danger-600 dark:text-danger-400 mr-1" />
                <p :class="metrics.userGrowth >= 0 ? 'text-success-600 dark:text-success-400' : 'text-danger-600 dark:text-danger-400'" class="text-sm font-medium">
                  {{ metrics.userGrowth >= 0 ? '+' : '' }}{{ metrics.userGrowth }}%
                </p>
                <span class="text-gray-500 dark:text-gray-400 text-xs ml-1">vs last period</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Active Subscriptions -->
        <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm border border-gray-200 dark:border-gray-700 hover:shadow-md transition-shadow duration-200">
          <div class="flex items-center">
            <div class="p-3 rounded-full bg-gradient-to-r from-accent-500 to-accent-600 shadow-lg">
              <CurrencyDollarIcon class="w-6 h-6 text-white" />
            </div>
            <div class="ml-4 flex-1">
              <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Active Subscriptions</p>
              <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ formatNumber(metrics.activeSubscriptions) }}</p>
              <div class="flex items-center mt-1">
                <ArrowTrendingUpIcon v-if="metrics.subscriptionGrowth >= 0" class="w-4 h-4 text-success-600 dark:text-success-400 mr-1" />
                <ArrowTrendingDownIcon v-else class="w-4 h-4 text-danger-600 dark:text-danger-400 mr-1" />
                <p :class="metrics.subscriptionGrowth >= 0 ? 'text-success-600 dark:text-success-400' : 'text-danger-600 dark:text-danger-400'" class="text-sm font-medium">
                  {{ metrics.subscriptionGrowth >= 0 ? '+' : '' }}{{ metrics.subscriptionGrowth }}%
                </p>
                <span class="text-gray-500 dark:text-gray-400 text-xs ml-1">vs last period</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Revenue -->
        <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm border border-gray-200 dark:border-gray-700 hover:shadow-md transition-shadow duration-200">
          <div class="flex items-center">
            <div class="p-3 rounded-full bg-gradient-to-r from-primary-500 to-primary-600 shadow-lg">
              <CurrencyDollarIcon class="w-6 h-6 text-white" />
            </div>
            <div class="ml-4 flex-1">
              <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Monthly Revenue</p>
              <p class="text-2xl font-bold text-gray-900 dark:text-white">${{ formatNumber(metrics.monthlyRevenue) }}</p>
              <div class="flex items-center mt-1">
                <ArrowTrendingUpIcon v-if="metrics.revenueGrowth >= 0" class="w-4 h-4 text-success-600 dark:text-success-400 mr-1" />
                <ArrowTrendingDownIcon v-else class="w-4 h-4 text-danger-600 dark:text-danger-400 mr-1" />
                <p :class="metrics.revenueGrowth >= 0 ? 'text-success-600 dark:text-success-400' : 'text-danger-600 dark:text-danger-400'" class="text-sm font-medium">
                  {{ metrics.revenueGrowth >= 0 ? '+' : '' }}{{ metrics.revenueGrowth }}%
                </p>
                <span class="text-gray-500 dark:text-gray-400 text-xs ml-1">vs last period</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Projects Created -->
        <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm border border-gray-200 dark:border-gray-700 hover:shadow-md transition-shadow duration-200">
          <div class="flex items-center">
            <div class="p-3 rounded-full bg-gradient-to-r from-accent-500 to-accent-600 shadow-lg">
              <DocumentDuplicateIcon class="w-6 h-6 text-white" />
            </div>
            <div class="ml-4 flex-1">
              <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Projects Created</p>
              <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ formatNumber(metrics.projectsCreated) }}</p>
              <div class="flex items-center mt-1">
                <ArrowTrendingUpIcon v-if="metrics.projectGrowth >= 0" class="w-4 h-4 text-success-600 dark:text-success-400 mr-1" />
                <ArrowTrendingDownIcon v-else class="w-4 h-4 text-danger-600 dark:text-danger-400 mr-1" />
                <p :class="metrics.projectGrowth >= 0 ? 'text-success-600 dark:text-success-400' : 'text-danger-600 dark:text-danger-400'" class="text-sm font-medium">
                  {{ metrics.projectGrowth >= 0 ? '+' : '' }}{{ metrics.projectGrowth }}%
                </p>
                <span class="text-gray-500 dark:text-gray-400 text-xs ml-1">vs last period</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Charts Section -->
      <div class="mb-8">
        <!-- Charts Header -->
        <div class="flex items-center justify-between mb-6">
          <div>
            <h2 class="text-xl font-bold text-gray-900 dark:text-white">Analytics Trends</h2>
            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Visual insights into platform performance</p>
          </div>
          <div class="flex items-center space-x-2 bg-gray-100 dark:bg-gray-800 px-3 py-2 rounded-lg">
            <div class="w-2 h-2 bg-primary-500 rounded-full"></div>
            <span class="text-sm text-gray-600 dark:text-gray-400 font-medium">{{ selectedPeriod }}</span>
          </div>
        </div>

        <!-- Primary Charts Row -->
        <div class="grid grid-cols-1 xl:grid-cols-2 gap-6 mb-6">
          <!-- User Registration Chart - Featured -->
          <div class="bg-white dark:bg-gray-800 rounded-xl p-4 lg:p-6 shadow-sm border border-gray-200 dark:border-gray-700 hover:shadow-md transition-shadow duration-200 xl:col-span-1">
            <div class="flex items-center justify-between mb-6">
              <div class="flex items-center space-x-3">
                <div class="p-2 bg-primary-100 dark:bg-primary-900 rounded-lg">
                  <UsersIcon class="w-5 h-5 text-primary-600 dark:text-primary-400" />
                </div>
                <div>
                  <h3 class="text-lg font-semibold text-gray-900 dark:text-white">User Growth</h3>
                  <p class="text-sm text-gray-500 dark:text-gray-400">New user registrations over time</p>
                </div>
              </div>
              <div class="flex items-center space-x-2">
                <div class="w-3 h-3 bg-primary-500 rounded-full animate-pulse"></div>
                <span class="text-xs text-gray-500 dark:text-gray-400 bg-primary-50 dark:bg-primary-900 px-2 py-1 rounded font-medium">Live</span>
              </div>
            </div>
            
            <div class="h-64 sm:h-80 relative chart-container">
              <Line
                v-if="userRegistrationChartData"
                :data="userRegistrationChartData"
                :options="chartOptions"
              />
              <div v-else-if="loading" class="absolute inset-0 bg-gradient-to-br from-primary-50 to-primary-100 dark:from-primary-900 dark:to-primary-800 rounded-lg flex items-center justify-center">
                <div class="text-center">
                  <div class="animate-spin rounded-full h-10 w-10 border-b-2 border-primary-600 mx-auto mb-3"></div>
                  <p class="text-primary-600 dark:text-primary-400 font-medium">Loading user data...</p>
                </div>
              </div>
              <div v-else class="absolute inset-0 bg-gradient-to-br from-gray-100 to-gray-200 dark:from-gray-800 dark:to-gray-900 rounded-lg flex items-center justify-center">
                <div class="text-center">
                  <ChartBarIcon class="w-16 h-16 text-gray-400 dark:text-gray-600 mx-auto mb-3" />
                  <p class="text-gray-600 dark:text-gray-400 font-medium">No user registration data</p>
                  <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">Registration trends will appear here when data is available</p>
                </div>
              </div>
            </div>
          </div>

          <!-- Content Creation Chart - Featured -->
          <div class="bg-white dark:bg-gray-800 rounded-xl p-4 lg:p-6 shadow-sm border border-gray-200 dark:border-gray-700 hover:shadow-md transition-shadow duration-200 xl:col-span-1">
            <div class="flex items-center justify-between mb-6">
              <div class="flex items-center space-x-3">
                <div class="p-2 bg-accent-100 dark:bg-accent-900 rounded-lg">
                  <DocumentDuplicateIcon class="w-5 h-5 text-accent-600 dark:text-accent-400" />
                </div>
                <div>
                  <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Content Creation</h3>
                  <p class="text-sm text-gray-500 dark:text-gray-400">Projects and designs created over time</p>
                </div>
              </div>
              <div class="flex items-center space-x-2">
                <div class="w-3 h-3 bg-accent-500 rounded-full animate-pulse"></div>
                <span class="text-xs text-gray-500 dark:text-gray-400 bg-accent-50 dark:bg-accent-900 px-2 py-1 rounded font-medium">Live</span>
              </div>
            </div>
            
            <div class="h-64 sm:h-80 relative chart-container">
              <Line
                v-if="contentCreationChartData"
                :data="contentCreationChartData"
                :options="chartOptions"
              />
              <div v-else-if="loading" class="absolute inset-0 bg-gradient-to-br from-accent-50 to-accent-100 dark:from-accent-900 dark:to-accent-800 rounded-lg flex items-center justify-center">
                <div class="text-center">
                  <div class="animate-spin rounded-full h-10 w-10 border-b-2 border-accent-600 mx-auto mb-3"></div>
                  <p class="text-accent-600 dark:text-accent-400 font-medium">Loading content data...</p>
                </div>
              </div>
              <div v-else class="absolute inset-0 bg-gradient-to-br from-gray-100 to-gray-200 dark:from-gray-800 dark:to-gray-900 rounded-lg flex items-center justify-center">
                <div class="text-center">
                  <DocumentDuplicateIcon class="w-16 h-16 text-gray-400 dark:text-gray-600 mx-auto mb-3" />
                  <p class="text-gray-600 dark:text-gray-400 font-medium">No content creation data</p>
                  <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">Project creation trends will appear here when data is available</p>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Secondary Charts Row -->
        <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
          <!-- Export Activity Chart -->
          <div class="bg-white dark:bg-gray-800 rounded-xl p-4 lg:p-6 shadow-sm border border-gray-200 dark:border-gray-700 hover:shadow-md transition-shadow duration-200 xl:col-span-2">
            <div class="flex items-center justify-between mb-6">
              <div class="flex items-center space-x-3">
                <div class="p-2 bg-primary-100 dark:bg-primary-900 rounded-lg">
                  <CloudArrowUpIcon class="w-5 h-5 text-primary-600 dark:text-primary-400" />
                </div>
                <div>
                  <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Export Activity</h3>
                  <p class="text-sm text-gray-500 dark:text-gray-400">Content exports and downloads over time</p>
                </div>
              </div>
              <div class="flex items-center space-x-2">
                <div class="w-3 h-3 bg-primary-500 rounded-full animate-pulse"></div>
                <span class="text-xs text-gray-500 dark:text-gray-400 bg-primary-50 dark:bg-primary-900 px-2 py-1 rounded font-medium">Live</span>
              </div>
            </div>
            
            <div class="h-56 sm:h-64 relative chart-container">
              <Line
                v-if="exportActivityChartData"
                :data="exportActivityChartData"
                :options="chartOptions"
              />
              <div v-else-if="loading" class="absolute inset-0 bg-gradient-to-br from-primary-50 to-primary-100 dark:from-primary-900 dark:to-primary-800 rounded-lg flex items-center justify-center">
                <div class="text-center">
                  <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary-600 mx-auto mb-2"></div>
                  <p class="text-primary-600 dark:text-primary-400 font-medium text-sm">Loading export data...</p>
                </div>
              </div>
              <div v-else class="absolute inset-0 bg-gradient-to-br from-gray-100 to-gray-200 dark:from-gray-800 dark:to-gray-900 rounded-lg flex items-center justify-center">
                <div class="text-center">
                  <CloudArrowUpIcon class="w-12 h-12 text-gray-400 dark:text-gray-600 mx-auto mb-2" />
                  <p class="text-gray-600 dark:text-gray-400 font-medium">No export data</p>
                  <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">Export activity will appear here when data is available</p>
                </div>
              </div>
            </div>
          </div>

          <!-- Charts Summary Panel -->
          <div class="bg-white dark:bg-gray-800 rounded-xl p-6 border border-gray-200 dark:border-gray-700">
            <div class="mb-4">
              <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Data Summary</h3>
              <p class="text-sm text-gray-600 dark:text-gray-400">Key metrics for {{ selectedPeriod }}</p>
            </div>
            
            <div class="space-y-4">
              <!-- Total New Users -->
              <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-3 border border-gray-200 dark:border-gray-600">
                <div class="flex items-center justify-between">
                  <div class="flex items-center space-x-2">
                    <div class="w-2 h-2 bg-primary-500 rounded-full"></div>
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">New Users</span>
                  </div>
                  <span class="text-lg font-bold text-primary-600 dark:text-primary-400">{{ getTotalGrowthThisPeriod('userGrowth') }}</span>
                </div>
              </div>

              <!-- Total Projects -->
              <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-3 border border-gray-200 dark:border-gray-600">
                <div class="flex items-center justify-between">
                  <div class="flex items-center space-x-2">
                    <div class="w-2 h-2 bg-accent-500 rounded-full"></div>
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Projects</span>
                  </div>
                  <span class="text-lg font-bold text-accent-600 dark:text-accent-400">{{ getTotalGrowthThisPeriod('contentCreation') }}</span>
                </div>
              </div>

              <!-- Total Exports -->
              <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-3 border border-gray-200 dark:border-gray-600">
                <div class="flex items-center justify-between">
                  <div class="flex items-center space-x-2">
                    <div class="w-2 h-2 bg-primary-500 rounded-full"></div>
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Exports</span>
                  </div>
                  <span class="text-lg font-bold text-primary-600 dark:text-primary-400">{{ getTotalGrowthThisPeriod('exportActivity') }}</span>
                </div>
              </div>

              <!-- Average Daily Activity -->
              <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-3 border border-gray-200 dark:border-gray-600">
                <div class="flex items-center justify-between">
                  <div class="flex items-center space-x-2">
                    <div class="w-2 h-2 bg-accent-500 rounded-full"></div>
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Daily Avg</span>
                  </div>
                  <span class="text-lg font-bold text-accent-600 dark:text-accent-400">{{ getAverageDaily('contentCreation') }}</span>
                </div>
              </div>
            </div>

            <!-- Period Indicator -->
            <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-600">
              <div class="flex items-center justify-center space-x-2">
                <CalendarDaysIcon class="w-4 h-4 text-primary-600 dark:text-primary-400" />
                <span class="text-xs text-primary-600 dark:text-primary-400 font-medium">{{ selectedPeriod.replace('d', ' days').replace('y', ' year') }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Detailed Analytics Tables -->
      <div class="grid grid-cols-1 xl:grid-cols-2 gap-6 mt-8">
        <!-- Top Plans by Revenue -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 hover:shadow-md transition-shadow duration-200">
          <div class="p-6">
            <div class="flex items-center justify-between mb-4">
              <div>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Top Plans by Revenue</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Revenue breakdown by subscription plan</p>
              </div>
              <div class="text-xs text-gray-400 dark:text-gray-500 bg-gray-100 dark:bg-gray-800 px-2 py-1 rounded">
                {{ topPlans.length }} plans
              </div>
            </div>
            
            <div v-if="loading" class="py-8 text-center">
              <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary-600 mx-auto mb-2"></div>
              <p class="text-gray-500 dark:text-gray-400 text-sm">Loading revenue data...</p>
            </div>

            <div v-else-if="topPlans.length > 0" class="space-y-3">
              <div v-for="(plan, index) in topPlans" :key="plan.name" class="flex items-center justify-between py-3 px-3 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors duration-150 border border-gray-200 dark:border-gray-700">
                <div class="flex items-center space-x-3">
                  <div class="flex items-center justify-center w-8 h-8 rounded-full text-xs font-medium text-white" :style="{ backgroundColor: getColorForIndex(index) }">
                    {{ index + 1 }}
                  </div>
                  <div class="w-10 h-10 bg-gradient-to-br from-primary-500 to-accent-600 rounded-lg flex items-center justify-center shadow-sm">
                    <span class="text-white font-medium text-sm">{{ plan.name.charAt(0) }}</span>
                  </div>
                  <div>
                    <p class="font-medium text-gray-900 dark:text-white">{{ plan.name }}</p>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ formatNumber(plan.subscribers) }} subscribers</p>
                  </div>
                </div>
                <div class="text-right">
                  <p class="font-semibold text-gray-900 dark:text-white">${{ formatNumber(plan.revenue) }}</p>
                  <p class="text-xs text-gray-500 dark:text-gray-400">{{ plan.percentage }}% of total</p>
                </div>
              </div>
            </div>

            <div v-else class="py-8 text-center">
              <div class="text-gray-400 dark:text-gray-600">
                <CurrencyDollarIcon class="w-12 h-12 mx-auto mb-2" />
                <p class="text-sm font-medium">No revenue data available</p>
                <p class="text-xs mt-1">Revenue breakdown will appear here</p>
              </div>
            </div>
          </div>
        </div>

        <!-- User Activity -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 hover:shadow-md transition-shadow duration-200">
          <div class="p-6">
            <div class="flex items-center justify-between mb-4">
              <div>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">User Activity Insights</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Key engagement and usage metrics</p>
              </div>
              <div class="text-xs text-gray-400 dark:text-gray-500 bg-gray-100 dark:bg-gray-800 px-2 py-1 rounded">
                Live data
              </div>
            </div>
            
            <div class="space-y-4">
              <!-- Daily Active Users -->
              <div class="flex items-center justify-between py-3 px-3 rounded-lg bg-gray-100 dark:bg-gray-800 border border-gray-200 dark:border-gray-700">
                <div class="flex items-center space-x-3">
                  <div class="w-3 h-3 bg-accent-500 rounded-full animate-pulse"></div>
                  <span class="text-gray-700 dark:text-gray-300 font-medium">Daily Active Users</span>
                </div>
                <span class="font-bold text-accent-700 dark:text-accent-300">{{ formatNumber(activityMetrics.dailyActiveUsers) }}</span>
              </div>

              <!-- Weekly Active Users -->
              <div class="flex items-center justify-between py-3 px-3 rounded-lg bg-gray-100 dark:bg-gray-800 border border-gray-200 dark:border-gray-700">
                <div class="flex items-center space-x-3">
                  <div class="w-3 h-3 bg-primary-500 rounded-full"></div>
                  <span class="text-gray-700 dark:text-gray-300 font-medium">Weekly Active Users</span>
                </div>
                <span class="font-bold text-primary-700 dark:text-primary-300">{{ formatNumber(activityMetrics.weeklyActiveUsers) }}</span>
              </div>

              <!-- Average Session Duration -->
              <div class="flex items-center justify-between py-3 px-3 rounded-lg bg-gray-100 dark:bg-gray-800 border border-gray-200 dark:border-gray-700">
                <div class="flex items-center space-x-3">
                  <div class="w-3 h-3 bg-accent-500 rounded-full"></div>
                  <span class="text-gray-700 dark:text-gray-300 font-medium">Avg. Session Duration</span>
                </div>
                <span class="font-bold text-accent-700 dark:text-accent-300">{{ activityMetrics.avgSessionDuration }}m</span>
              </div>

              <!-- Projects per User -->
              <div class="flex items-center justify-between py-3 px-3 rounded-lg bg-gray-100 dark:bg-gray-800 border border-gray-200 dark:border-gray-700">
                <div class="flex items-center space-x-3">
                  <div class="w-3 h-3 bg-primary-500 rounded-full"></div>
                  <span class="text-gray-700 dark:text-gray-300 font-medium">Avg. Projects per User</span>
                </div>
                <span class="font-bold text-primary-700 dark:text-primary-300">{{ activityMetrics.projectsPerUser }}</span>
              </div>

              <!-- Exports per User -->
              <div class="flex items-center justify-between py-3 px-3 rounded-lg bg-gray-100 dark:bg-gray-800 border border-gray-200 dark:border-gray-700">
                <div class="flex items-center space-x-3">
                  <div class="w-3 h-3 bg-accent-500 rounded-full"></div>
                  <span class="text-gray-700 dark:text-gray-300 font-medium">Avg. Exports per User</span>
                </div>
                <span class="font-bold text-accent-700 dark:text-accent-300">{{ activityMetrics.exportsPerUser }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- System Performance Metrics -->
      <div class="mt-8 bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
        <div class="p-6">
          <div class="flex items-center justify-between mb-6">
            <div>
              <h3 class="text-lg font-semibold text-gray-900 dark:text-white">System Performance</h3>
              <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Real-time system health and performance metrics</p>
            </div>
            <div class="flex items-center space-x-2">
              <div class="w-2 h-2 bg-accent-500 rounded-full animate-pulse"></div>
              <span class="text-sm text-gray-500 dark:text-gray-400">Live</span>
            </div>
          </div>
          
          <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            <!-- API Response Time -->
            <div class="bg-white dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
              <div class="flex items-center justify-between mb-2">
                <div class="p-2 bg-gray-100 dark:bg-gray-700 rounded-lg">
                  <ArrowPathIcon class="w-5 h-5 text-gray-600 dark:text-gray-400" />
                </div>
                <div :class="systemMetrics.apiResponseTime < 200 ? 'text-success-800 dark:text-success-100 bg-success-100 dark:bg-success-800' : 'text-warning-800 dark:text-warning-100 bg-warning-100 dark:bg-warning-800'" class="text-xs font-medium px-2 py-1 rounded-full">
                  {{ systemMetrics.apiResponseTime < 200 ? 'Excellent' : 'Good' }}
                </div>
              </div>
              <div class="text-2xl font-bold text-gray-900 dark:text-white mb-1">{{ systemMetrics.apiResponseTime }}ms</div>
              <div class="text-sm text-gray-600 dark:text-gray-400">Avg API Response Time</div>
              <div class="mt-2 bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                <div 
                  class="h-2 rounded-full transition-all duration-500"
                  :class="systemMetrics.apiResponseTime < 200 ? 'bg-success-500' : 'bg-warning-500'"
                  :style="{ width: `${Math.min(100, (systemMetrics.apiResponseTime / 500) * 100)}%` }"
                ></div>
              </div>
            </div>

            <!-- Error Rate -->
            <div class="bg-white dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
              <div class="flex items-center justify-between mb-2">
                <div class="p-2 bg-gray-100 dark:bg-gray-700 rounded-lg">
                  <ExclamationTriangleIcon class="w-5 h-5 text-gray-600 dark:text-gray-400" />
                </div>
                <div :class="systemMetrics.errorRate < 1 ? 'text-success-800 dark:text-success-100 bg-success-100 dark:bg-success-800' : 'text-danger-800 dark:text-danger-100 bg-danger-100 dark:bg-danger-800'" class="text-xs font-medium px-2 py-1 rounded-full">
                  {{ systemMetrics.errorRate < 1 ? 'Excellent' : 'Needs Attention' }}
                </div>
              </div>
              <div class="text-2xl font-bold text-gray-900 dark:text-white mb-1">{{ systemMetrics.errorRate }}%</div>
              <div class="text-sm text-gray-600 dark:text-gray-400">Error Rate</div>
              <div class="mt-2 bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                <div 
                  class="h-2 rounded-full transition-all duration-500"
                  :class="systemMetrics.errorRate < 1 ? 'bg-success-500' : 'bg-danger-500'"
                  :style="{ width: `${Math.min(100, (systemMetrics.errorRate / 5) * 100)}%` }"
                ></div>
              </div>
            </div>

            <!-- Storage Usage -->
            <div class="bg-white dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
              <div class="flex items-center justify-between mb-2">
                <div class="p-2 bg-gray-100 dark:bg-gray-700 rounded-lg">
                  <ServerIcon class="w-5 h-5 text-gray-600 dark:text-gray-400" />
                </div>
                <div :class="systemMetrics.storageUsage < 80 ? 'text-success-800 dark:text-success-100 bg-success-100 dark:bg-success-800' : 'text-warning-800 dark:text-warning-100 bg-warning-100 dark:bg-warning-800'" class="text-xs font-medium px-2 py-1 rounded-full">
                  {{ systemMetrics.storageUsage < 80 ? 'Good' : 'Monitor' }}
                </div>
              </div>
              <div class="text-2xl font-bold text-gray-900 dark:text-white mb-1">{{ systemMetrics.storageUsage }}%</div>
              <div class="text-sm text-gray-600 dark:text-gray-400">Storage Usage</div>
              <div class="mt-2 bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                <div 
                  class="h-2 rounded-full transition-all duration-500"
                  :class="systemMetrics.storageUsage < 80 ? 'bg-success-500' : systemMetrics.storageUsage < 90 ? 'bg-warning-500' : 'bg-danger-500'"
                  :style="{ width: `${systemMetrics.storageUsage}%` }"
                ></div>
              </div>
            </div>

            <!-- Uptime -->
            <div class="bg-white dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
              <div class="flex items-center justify-between mb-2">
                <div class="p-2 bg-gray-100 dark:bg-gray-700 rounded-lg">
                  <CheckCircleIcon class="w-5 h-5 text-gray-600 dark:text-gray-400" />
                </div>
                <div class="text-success-800 dark:text-success-100 text-xs font-medium px-2 py-1 rounded-full bg-success-100 dark:bg-success-800">
                  Excellent
                </div>
              </div>
              <div class="text-2xl font-bold text-gray-900 dark:text-white mb-1">{{ systemMetrics.uptime }}%</div>
              <div class="text-sm text-gray-600 dark:text-gray-400">System Uptime</div>
              <div class="mt-2 bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                <div 
                  class="bg-success-500 h-2 rounded-full transition-all duration-500"
                  :style="{ width: `${systemMetrics.uptime}%` }"
                ></div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted, onUnmounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useNotifications } from '@/composables/useNotifications'
import { adminAPI } from '@/services/api'
import AppLayout from '@/components/layout/AppLayout.vue'

// Chart.js imports
import {
  Chart as ChartJS,
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  Title,
  Tooltip,
  Legend,
  Filler
} from 'chart.js'
import { Line } from 'vue-chartjs'

// Register Chart.js components
ChartJS.register(
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  Title,
  Tooltip,
  Legend,
  Filler
)

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
  CalendarDaysIcon,
  ArrowPathIcon,
  ExclamationTriangleIcon,
  ServerIcon,
  CheckCircleIcon
} from '@heroicons/vue/24/outline'

// Access control
const authStore = useAuthStore()
const router = useRouter()

const { showSuccess, showError } = useNotifications()

// Component state
const loading = ref(false)
const selectedPeriod = ref('30d')
const autoRefreshEnabled = ref(false)
const lastUpdated = ref(new Date())
let autoRefreshInterval: NodeJS.Timeout | null = null

// Time series data from API
const timeSeriesData = ref<{
  userGrowth: Array<{ date: string; count: number }>
  contentCreation: Array<{ date: string; count: number }>
  exportActivity: Array<{ date: string; count: number }>
  revenue: Array<{ date: string; count: number }>
}>({
  userGrowth: [],
  contentCreation: [],
  exportActivity: [],
  revenue: []
})

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

// Chart configuration - Optimized for performance and responsiveness
const chartOptions = computed(() => ({
  responsive: true,
  maintainAspectRatio: false,
  animation: {
    duration: 750,
    easing: 'easeInOutQuart' as const
  },
  interaction: {
    intersect: false,
    mode: 'index' as const,
  },
  plugins: {
    legend: {
      display: false
    },
    tooltip: {
      backgroundColor: 'rgba(15, 23, 42, 0.95)', // secondary-900 with opacity
      titleColor: 'rgb(248 250 252)', // secondary-50
      bodyColor: 'rgb(248 250 252)', // secondary-50
      borderColor: 'rgba(148 163 184, 0.2)', // secondary-400 with opacity
      borderWidth: 1,
      cornerRadius: 8,
      displayColors: false,
      padding: 12,
      callbacks: {
        title: function(context: any) {
          return context[0].label
        },
        label: function(context: any) {
          return `${context.dataset.label}: ${context.parsed.y.toLocaleString()}`
        }
      }
    }
  },
  scales: {
    x: {
      display: true,
      grid: {
        display: false
      },
      ticks: {
        color: 'rgb(100 116 139)', // secondary-500 for light mode
        font: {
          size: window.innerWidth < 768 ? 10 : 11,
          family: 'Inter, sans-serif'
        },
        maxTicksLimit: window.innerWidth < 768 ? 5 : 8
      }
    },
    y: {
      display: true,
      beginAtZero: true,
      grid: {
        color: 'rgba(148 163 184, 0.3)', // secondary-400 with opacity for better contrast
        drawBorder: false
      },
      ticks: {
        color: 'rgb(100 116 139)', // secondary-500 for better contrast
        font: {
          size: window.innerWidth < 768 ? 10 : 11,
          family: 'Inter, sans-serif'
        },
        callback: function(value: any) {
          if (value >= 1000000) {
            return (value / 1000000).toFixed(1) + 'M'
          } else if (value >= 1000) {
            return (value / 1000).toFixed(1) + 'K'
          }
          return Number.isInteger(value) ? value : ''
        },
        maxTicksLimit: window.innerWidth < 768 ? 4 : 6
      }
    }
  },
  elements: {
    line: {
      tension: 0.35,
      borderWidth: window.innerWidth < 768 ? 2 : 2
    },
    point: {
      radius: window.innerWidth < 768 ? 2 : 3,
      hoverRadius: window.innerWidth < 768 ? 4 : 5,
      borderWidth: 2,
      hoverBorderWidth: 2
    }
  }
}))

// Chart data computed properties
const userRegistrationChartData = computed(() => {
  if (!timeSeriesData.value.userGrowth.length) return null
  
  return {
    labels: timeSeriesData.value.userGrowth.map(item => {
      return new Date(item.date).toLocaleDateString('en-US', { 
        month: 'short', 
        day: 'numeric' 
      })
    }),
    datasets: [
      {
        label: 'New Users',
        data: timeSeriesData.value.userGrowth.map(item => item.count),
        borderColor: '#3b82f6',
        backgroundColor: 'rgba(59, 130, 246, 0.1)',
        fill: true,
        borderWidth: 2
      }
    ]
  }
})

const contentCreationChartData = computed(() => {
  if (!timeSeriesData.value.contentCreation.length) return null
  
  return {
    labels: timeSeriesData.value.contentCreation.map(item => {
      return new Date(item.date).toLocaleDateString('en-US', { 
        month: 'short', 
        day: 'numeric' 
      })
    }),
    datasets: [
      {
        label: 'Projects Created',
        data: timeSeriesData.value.contentCreation.map(item => item.count),
        borderColor: '#10b981',
        backgroundColor: 'rgba(16, 185, 129, 0.1)',
        fill: true,
        borderWidth: 2
      }
    ]
  }
})

const exportActivityChartData = computed(() => {
  if (!timeSeriesData.value.exportActivity.length) return null
  
  return {
    labels: timeSeriesData.value.exportActivity.map(item => {
      return new Date(item.date).toLocaleDateString('en-US', { 
        month: 'short', 
        day: 'numeric' 
      })
    }),
    datasets: [
      {
        label: 'Exports Created',
        data: timeSeriesData.value.exportActivity.map(item => item.count),
        borderColor: '#f59e0b',
        backgroundColor: 'rgba(245, 158, 11, 0.1)',
        fill: true,
        borderWidth: 2
      }
    ]
  }
})

// Methods
const loadAnalytics = async () => {
  loading.value = true
  try {
    // Get analytics data from the backend
    const startDate = getStartDate(selectedPeriod.value)
    const endDate = new Date().toISOString()
    
    const response = await adminAPI.getAnalytics({
      startDate,
      endDate,
      granularity: selectedPeriod.value === '7d' ? 'day' : selectedPeriod.value === '30d' ? 'day' : 'week'
    })
    
    const analyticsData = response.data.data
    
    // Update metrics with real data
    Object.assign(metrics, analyticsData.metrics)
    
    // Update activity metrics
    Object.assign(activityMetrics, analyticsData.activityMetrics)
    
    // Update system metrics
    Object.assign(systemMetrics, analyticsData.systemMetrics)
    
    // Update top plans
    topPlans.value = analyticsData.topPlans || []
    
    // Update time series data for charts
    timeSeriesData.value = analyticsData.timeSeriesData || {
      userGrowth: [],
      contentCreation: [],
      exportActivity: [],
      revenue: []
    }
    
    // Update last updated timestamp
    lastUpdated.value = new Date()
    
    showSuccess('Analytics data loaded successfully')
  } catch (error) {
    console.error('Failed to load analytics:', error)
    
    // Fallback to platform stats if analytics endpoint fails
    try {
      const response = await adminAPI.getPlatformStats()
      const platformStats = response.data.data
      
      // Update metrics with platform stats data
      Object.assign(metrics, {
        totalUsers: platformStats.users?.total || 0,
        userGrowth: 0, // Growth calculation requires historical data
        activeSubscriptions: platformStats.users?.total || 0, // All users are considered subscribers
        subscriptionGrowth: 0, // Growth calculation requires historical data
        monthlyRevenue: 0, // Revenue data not available in basic platform stats
        revenueGrowth: 0, // Revenue growth requires historical data
        projectsCreated: platformStats.designs?.total || 0,
        projectGrowth: 0, // Growth calculation requires historical data
      })
      
      // Update activity metrics
      Object.assign(activityMetrics, {
        dailyActiveUsers: platformStats.users?.active || 0,
        weeklyActiveUsers: platformStats.users?.active || 0, // Weekly stats not available in basic platform stats
        avgSessionDuration: 0, // Session analytics not available in basic platform stats
        projectsPerUser: platformStats.designs?.total && platformStats.users?.total ? 
          parseFloat((platformStats.designs.total / platformStats.users.total).toFixed(1)) : 0,
        exportsPerUser: platformStats.exports?.total && platformStats.users?.total ? 
          parseFloat((platformStats.exports.total / platformStats.users.total).toFixed(1)) : 0,
      })
      
      // Update system metrics
      Object.assign(systemMetrics, {
        apiResponseTime: 0, // API response time monitoring not available in basic platform stats
        errorRate: 0, // Error rate monitoring not available in basic platform stats
        storageUsage: platformStats.storage?.used && platformStats.storage?.total ?
          Math.round((platformStats.storage.used / platformStats.storage.total) * 100) : 0,
        uptime: 0, // Uptime monitoring not available in basic platform stats
      })
      
      lastUpdated.value = new Date()
      showError('Analytics service unavailable, showing basic platform stats')
    } catch (fallbackError) {
      console.error('Failed to load fallback data:', fallbackError)
      showError('Failed to load analytics data')
    }
  } finally {
    loading.value = false
  }
}

const toggleAutoRefresh = () => {
  autoRefreshEnabled.value = !autoRefreshEnabled.value
  
  if (autoRefreshEnabled.value) {
    // Refresh every 5 minutes
    autoRefreshInterval = setInterval(() => {
      loadAnalytics()
    }, 5 * 60 * 1000)
    showSuccess('Auto-refresh enabled (5min intervals)')
  } else {
    if (autoRefreshInterval) {
      clearInterval(autoRefreshInterval)
      autoRefreshInterval = null
    }
    showSuccess('Auto-refresh disabled')
  }
}

const formatLastUpdated = () => {
  const now = new Date()
  const diff = now.getTime() - lastUpdated.value.getTime()
  const minutes = Math.floor(diff / 60000)
  
  if (minutes < 1) {
    return 'Just now'
  } else if (minutes < 60) {
    return `${minutes} minute${minutes === 1 ? '' : 's'} ago`
  } else {
    return lastUpdated.value.toLocaleTimeString()
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

const getTotalGrowthThisPeriod = (metric: 'userGrowth' | 'contentCreation' | 'exportActivity') => {
  const data = timeSeriesData.value[metric]
  if (!data.length) return 0
  return data.reduce((sum, item) => sum + item.count, 0)
}

const getAverageDaily = (metric: 'userGrowth' | 'contentCreation' | 'exportActivity') => {
  const data = timeSeriesData.value[metric]
  if (!data.length) return 0
  const total = data.reduce((sum, item) => sum + item.count, 0)
  return Math.round(total / data.length * 10) / 10 // Round to 1 decimal place
}

const getColorForIndex = (index: number) => {
  const colors = [
    '#3b82f6', // blue
    '#10b981', // emerald
    '#f59e0b', // amber
    '#ef4444', // red
    '#8b5cf6', // violet
    '#06b6d4', // cyan
    '#84cc16', // lime
    '#f97316'  // orange
  ]
  return colors[index % colors.length]
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

onUnmounted(() => {
  // Clean up auto-refresh interval
  if (autoRefreshInterval) {
    clearInterval(autoRefreshInterval)
    autoRefreshInterval = null
  }
})
</script>

<style scoped>
/* Custom animations and transitions */
.analytics-card {
  transition: all 0.2s ease-in-out;
}

.analytics-card:hover {
  transform: translateY(-2px);
}

/* Smooth loading animation */
@keyframes shimmer {
  0% {
    background-position: -1000px 0;
  }
  100% {
    background-position: 1000px 0;
  }
}

.shimmer {
  background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
  background-size: 1000px 100%;
  animation: shimmer 2s infinite;
}

/* Progress bars animations */
.progress-bar {
  transition: all 1s ease-out;
}

/* Chart container optimizations */
.chart-container {
  position: relative;
  overflow: hidden;
  /* Contain layout for better performance */
  contain: layout style paint;
}

/* Custom scrollbar for tables */
.custom-scroll::-webkit-scrollbar {
  width: 4px;
  height: 4px;
}

.custom-scroll::-webkit-scrollbar-track {
  background: #f1f5f9;
  border-radius: 2px;
}

.custom-scroll::-webkit-scrollbar-thumb {
  background: #cbd5e1;
  border-radius: 2px;
}

.custom-scroll::-webkit-scrollbar-thumb:hover {
  background: #94a3b8;
}

/* Responsive improvements */
@media (max-width: 640px) {
  .metric-value {
    font-size: 1.25rem;
  }
}

/* Loading states */
.loading-placeholder {
  background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
  background-size: 1000px 100%;
  animation: shimmer 1.5s infinite;
  border-radius: 0.375rem;
}

/* Fade in animation for content */
@keyframes fadeIn {
  from {
    opacity: 0;
    transform: translateY(10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.fade-in {
  animation: fadeIn 0.5s ease-out;
}

/* Pulse animation for live indicators */
@keyframes pulse-dot {
  0%, 100% {
    opacity: 1;
  }
  50% {
    opacity: 0.5;
  }
}

.pulse-dot {
  animation: pulse-dot 2s infinite;
}
</style>

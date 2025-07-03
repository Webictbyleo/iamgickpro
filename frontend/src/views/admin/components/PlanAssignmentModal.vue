<template>
  <!-- Enhanced Plan Assignment Modal with HeadlessUI -->
  <TransitionRoot appear :show="true" as="template">
    <Dialog as="div" @close="$emit('close')" class="relative z-50">
      <!-- Backdrop -->
      <TransitionChild
        as="template"
        enter="duration-300 ease-out"
        enter-from="opacity-0"
        enter-to="opacity-100"
        leave="duration-200 ease-in"
        leave-from="opacity-100"
        leave-to="opacity-0"
      >
        <div class="fixed inset-0 bg-black bg-opacity-75 backdrop-blur-sm" />
      </TransitionChild>

      <!-- Modal Container -->
      <div class="fixed inset-0 overflow-y-auto">
        <div class="flex min-h-full items-center justify-center p-4 text-center">
          <TransitionChild
            as="template"
            enter="duration-300 ease-out"
            enter-from="opacity-0 scale-95"
            enter-to="opacity-100 scale-100"
            leave="duration-200 ease-in"
            leave-from="opacity-100 scale-100"
            leave-to="opacity-0 scale-95"
          >
            <DialogPanel class="w-full max-w-3xl transform overflow-hidden rounded-2xl bg-white dark:bg-gray-800 text-left align-middle shadow-2xl transition-all">
              <!-- Modal Header -->
              <div class="relative px-6 py-6 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800">
                <div class="flex items-center justify-between">
                  <DialogTitle as="h3" class="text-xl font-bold text-gray-900 dark:text-white flex items-center">
                    <CreditCardIcon class="w-6 h-6 text-primary-600 dark:text-primary-400 mr-3" />
                    {{ mode === 'single' ? 'Assign Plan to User' : 'Bulk Plan Assignment' }}
                  </DialogTitle>
                  <button
                    @click="$emit('close')"
                    class="rounded-full p-2 text-gray-400 dark:text-gray-500 hover:text-gray-600 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-primary-500 transition-colors"
                    aria-label="Close modal"
                  >
                    <XMarkIcon class="w-5 h-5" />
                  </button>
                </div>
              </div>

              <!-- Modal Content -->
              <div class="p-6">
                <!-- Loading State -->
                <div v-if="loading" class="text-center py-8">
                  <div class="animate-spin rounded-full h-12 w-12 border-4 border-gray-200 dark:border-gray-700 border-t-primary-600 mx-auto"></div>
                  <p class="mt-4 text-gray-600 dark:text-gray-400">Loading plans...</p>
                </div>

                <!-- Content -->
                <div v-else class="space-y-6">
                  <!-- Users Section -->
                  <div v-if="mode === 'single' && user" class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700">
                    <h4 class="text-sm font-semibold text-gray-900 dark:text-white mb-3 flex items-center">
                      <UserIcon class="w-4 h-4 mr-2 text-gray-600 dark:text-gray-400" />
                      Target User
                    </h4>
                    <div class="flex items-center space-x-4">
                      <!-- Avatar -->
                      <div class="relative">
                        <div class="h-12 w-12 rounded-xl bg-gray-600 dark:bg-gray-500 flex items-center justify-center shadow-lg">
                          <span class="text-white font-bold text-sm">{{ getUserInitials(user) }}</span>
                        </div>
                        <!-- Status Badge -->
                        <div class="absolute -bottom-1 -right-1">
                          <span 
                            :class="[
                              'inline-flex items-center justify-center w-4 h-4 rounded-full text-white font-semibold text-xs shadow-md',
                              user.isActive ? 'bg-success-500' : 'bg-danger-500'
                            ]"
                          >
                            <CheckCircleIcon v-if="user.isActive" class="w-2.5 h-2.5" />
                            <XMarkIcon v-else class="w-2.5 h-2.5" />
                          </span>
                        </div>
                      </div>

                      <!-- User Info -->
                      <div class="flex-1 min-w-0">
                        <h5 class="text-base font-semibold text-gray-900 dark:text-white truncate">
                          {{ user.firstName }} {{ user.lastName }}
                        </h5>
                        <p class="text-sm text-gray-600 dark:text-gray-400 truncate">{{ user.email }}</p>
                        
                        <!-- Current Plan -->
                        <div class="mt-1">
                          <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200 border border-gray-200 dark:border-gray-600">
                            Current: {{ user.plan || 'Free' }} Plan
                          </span>
                        </div>
                      </div>
                    </div>
                  </div>

                  <!-- Bulk Users Section -->
                  <div v-else-if="mode === 'bulk'" class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700">
                    <h4 class="text-sm font-semibold text-gray-900 dark:text-white mb-3 flex items-center">
                      <UsersIcon class="w-4 h-4 mr-2 text-gray-600 dark:text-gray-400" />
                      Selected Users ({{ selectedUsers?.length || 0 }})
                    </h4>
                    <div class="space-y-2 max-h-32 overflow-y-auto">
                      <div v-for="user in selectedUsers || []" :key="user.id" class="flex items-center space-x-3 p-2 bg-gray-50 dark:bg-gray-700 rounded-lg border border-gray-200 dark:border-gray-600">
                        <div class="h-8 w-8 rounded-lg bg-gray-600 dark:bg-gray-500 flex items-center justify-center shadow-sm">
                          <span class="text-white font-bold text-xs">{{ getUserInitials(user) }}</span>
                        </div>
                        <div class="flex-1 min-w-0">
                          <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ user.firstName }} {{ user.lastName }}</p>
                          <p class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ user.email }}</p>
                        </div>
                        <span class="text-xs text-gray-400 dark:text-gray-500">{{ user.plan || 'Free' }}</span>
                      </div>
                    </div>
                  </div>

                  <!-- Plan Selection -->
                  <div>
                    <h4 class="text-sm font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                      <SparklesIcon class="w-4 h-4 mr-2 text-gray-600 dark:text-gray-400" />
                      Select New Plan
                    </h4>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                      <div
                        v-for="plan in availablePlans"
                        :key="plan.id"
                        class="relative"
                      >
                        <label
                          :class="[
                            'flex flex-col p-4 border-2 rounded-xl cursor-pointer transition-all duration-200',
                            selectedPlan === plan.code
                              ? 'border-gray-400 dark:border-gray-500 bg-gray-100 dark:bg-gray-700 shadow-lg'
                              : 'border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700'
                          ]"
                        >
                          <div class="flex items-center justify-between mb-3">
                            <div class="flex items-center">
                              <input
                                v-model="selectedPlan"
                                :value="plan.code"
                                type="radio"
                                class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 dark:border-gray-600 transition-colors bg-white dark:bg-gray-700"
                              />
                              <span class="ml-3 text-base font-semibold text-gray-900 dark:text-white">{{ plan.name }}</span>
                            </div>
                            <!-- Plan Badge -->
                            <span
                              :class="[
                                'inline-flex items-center px-2 py-1 rounded-full text-xs font-medium',
                                plan.is_default
                                  ? 'bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200 border border-gray-200 dark:border-gray-600'
                                  : plan.is_active
                                    ? 'bg-success-100 dark:bg-success-900 text-success-800 dark:text-success-200 border border-success-200 dark:border-success-800'
                                    : 'bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200 border border-gray-200 dark:border-gray-600'
                              ]"
                            >
                              {{ plan.is_default ? 'Default' : plan.is_active ? 'Active' : 'Inactive' }}
                            </span>
                          </div>

                          <!-- Plan Description -->
                          <p v-if="plan.description" class="text-sm text-gray-600 dark:text-gray-400 mb-3">{{ plan.description }}</p>

                          <!-- Pricing -->
                          <div class="mb-3">
                            <div class="flex items-baseline">
                              <span class="text-2xl font-bold text-gray-900 dark:text-white">${{ formatPrice(plan.monthly_price || 0) }}</span>
                              <span class="ml-1 text-gray-500 dark:text-gray-400">/ month</span>
                            </div>
                            <p v-if="plan.yearly_price && parseFloat(plan.monthly_price) > 0" class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                              ${{ formatPrice(plan.yearly_price) }}/year (save {{ calculateSavings(plan.monthly_price, plan.yearly_price) }}%)
                            </p>
                            <p v-else-if="parseFloat(plan.monthly_price) === 0" class="text-sm text-success-600 dark:text-success-400 mt-1">
                              Free Plan - No charge
                            </p>
                          </div>

                          <!-- Features -->
                          <div v-if="plan.features && Object.keys(plan.features).length > 0">
                            <h5 class="text-xs font-medium text-gray-700 dark:text-gray-300 mb-2">Features</h5>
                            <ul class="space-y-1">
                              <li v-for="(isEnabled, featureName) in plan.features" :key="String(featureName)" class="flex items-center text-xs">
                                <CheckCircleIcon v-if="isEnabled" class="w-3 h-3 mr-1 flex-shrink-0 text-success-500" />
                                <XMarkIcon v-else class="w-3 h-3 mr-1 flex-shrink-0 text-danger-700 dark:text-danger-300" />
                                <span :class="isEnabled ? 'text-gray-700 dark:text-gray-300' : 'text-gray-500 dark:text-gray-400 line-through'">
                                  {{ formatFeatureName(String(featureName)) }}
                                </span>
                              </li>
                            </ul>
                          </div>

                          <!-- Limits -->
                          <div v-if="plan.limits && Object.keys(plan.limits).length > 0" class="mt-3">
                            <h5 class="text-xs font-medium text-gray-700 dark:text-gray-300 mb-2">Limits</h5>
                            <div class="grid grid-cols-2 gap-1">
                              <div v-for="(value, limitName) in plan.limits" :key="String(limitName)" class="text-xs text-gray-600 dark:text-gray-400">
                                <span class="font-medium">{{ formatLimitName(String(limitName)) }}:</span>
                                <span class="ml-1">{{ formatLimitValue(value) }}</span>
                              </div>
                            </div>
                          </div>
                        </label>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Modal Footer -->
              <div class="px-6 py-4 bg-gray-50 dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700 flex items-center justify-between">
                <div class="text-sm text-gray-600 dark:text-gray-400">
                  <span v-if="mode === 'single'">Assigning plan to 1 user</span>
                  <span v-else">Assigning plan to {{ selectedUsers?.length || 0 }} users</span>
                </div>
                <div class="flex items-center space-x-3">
                  <button
                    @click="$emit('close')"
                    class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 focus:ring-offset-white dark:focus:ring-offset-gray-900 transition-colors"
                  >
                    Cancel
                  </button>
                  <button
                    @click="assignPlan"
                    :disabled="!selectedPlan || assigning"
                    class="px-4 py-2 text-sm font-medium text-white bg-primary-600 border border-transparent rounded-lg hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 focus:ring-offset-white dark:focus:ring-offset-gray-900 disabled:opacity-50 disabled:cursor-not-allowed transition-colors flex items-center space-x-2"
                  >
                    <div v-if="assigning" class="animate-spin rounded-full h-4 w-4 border-2 border-white border-t-transparent"></div>
                    <span>{{ assigning ? 'Assigning...' : 'Assign Plan' }}</span>
                  </button>
                </div>
              </div>
            </DialogPanel>
          </TransitionChild>
        </div>
      </div>
    </Dialog>
  </TransitionRoot>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useNotifications } from '@/composables/useNotifications'
import { adminAPI } from '@/services/api'
import type { AdminUser, AdminSubscriptionPlan } from '@/types'
import {
  Dialog,
  DialogPanel,
  DialogTitle,
  TransitionChild,
  TransitionRoot,
} from '@headlessui/vue'

// Heroicons
import {
  CreditCardIcon,
  XMarkIcon,
  UserIcon,
  UsersIcon,
  CheckCircleIcon,
  SparklesIcon
} from '@heroicons/vue/24/outline'

// Props
interface Props {
  mode: 'single' | 'bulk'
  user?: AdminUser | null
  selectedUsers?: AdminUser[]
}

const props = defineProps<Props>()

// Emits
interface Emits {
  (e: 'close'): void
  (e: 'assigned', users: AdminUser[], planCode: string): void
}

const emit = defineEmits<Emits>()

const { showSuccess, showError } = useNotifications()

// State
const loading = ref(true)
const assigning = ref(false)
const availablePlans = ref<AdminSubscriptionPlan[]>([])
const selectedPlan = ref<string>('')

// Load available plans
const loadPlans = async () => {
  try {
    loading.value = true
    const response = await adminAPI.getPlans()
    availablePlans.value = response.data.data.plans
    
    // Pre-select the first active plan
    const activePlans = availablePlans.value.filter(plan => plan.is_active)
    if (activePlans.length > 0) {
      selectedPlan.value = activePlans[0].code
    }
  } catch (error) {
    console.error('Failed to load plans:', error)
    showError('Failed to load available plans')
  } finally {
    loading.value = false
  }
}

// Assign plan
const assignPlan = async () => {
  if (!selectedPlan.value) return

  try {
    assigning.value = true

    if (props.mode === 'single' && props.user) {
      // Single user assignment
      await adminAPI.assignPlanToUser(props.user.id, { plan_code: selectedPlan.value })
      emit('assigned', [props.user], selectedPlan.value)
      showSuccess('Plan assigned successfully')
    } else if (props.mode === 'bulk' && props.selectedUsers) {
      // Bulk assignment using bulk API
      const userIds = props.selectedUsers.map(user => user.id)
      await adminAPI.bulkAssignPlanToUsers({ 
        user_ids: userIds, 
        plan_code: selectedPlan.value 
      })
      emit('assigned', props.selectedUsers, selectedPlan.value)
      showSuccess(`Plan assigned to ${props.selectedUsers.length} users successfully`)
    }

    emit('close')
  } catch (error) {
    console.error('Failed to assign plan:', error)
    showError('Failed to assign plan')
  } finally {
    assigning.value = false
  }
}

// Utility functions
const getUserInitials = (user: AdminUser): string => {
  return `${user.firstName?.[0] || ''}${user.lastName?.[0] || ''}`.toUpperCase() || 'U'
}

const formatPrice = (price: string | number): string => {
  const numPrice = typeof price === 'string' ? parseFloat(price) : price
  return numPrice.toFixed(2)
}

const formatFeatureName = (name: string): string => {
  return name
    .split('_')
    .map(word => word.charAt(0).toUpperCase() + word.slice(1))
    .join(' ')
}

const formatLimitName = (name: string): string => {
  return name
    .split('_')
    .map(word => word.charAt(0).toUpperCase() + word.slice(1))
    .join(' ')
}

const formatLimitValue = (value: number): string => {
  if (value === -1) return 'Unlimited'
  if (value >= 1000000) return `${(value / 1000000).toFixed(1)}M`
  if (value >= 1000) return `${(value / 1000).toFixed(1)}K`
  return value.toString()
}

const calculateSavings = (monthlyPrice: string, yearlyPrice: string): number => {
  const monthly = parseFloat(monthlyPrice)
  const yearly = parseFloat(yearlyPrice)
  
  // If monthly price is 0 or yearly price is 0, no savings calculation
  if (monthly === 0 || yearly === 0) return 0
  
  const annualMonthly = monthly * 12
  const savings = ((annualMonthly - yearly) / annualMonthly) * 100
  
  return Math.round(savings)
}

// Lifecycle
onMounted(() => {
  loadPlans()
})
</script>

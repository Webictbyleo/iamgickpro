<template>
  <!-- Enhanced Edit Roles Modal with HeadlessUI -->
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
            <DialogPanel class="w-full max-w-2xl transform overflow-hidden rounded-2xl bg-white dark:bg-gray-800 text-left align-middle shadow-2xl transition-all">
              <!-- Modal Header -->
              <div class="relative px-6 py-6 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800">
                <div class="flex items-center justify-between">
                  <DialogTitle as="h3" class="text-xl font-bold text-gray-900 dark:text-white flex items-center">
                    <ShieldCheckIcon class="w-6 h-6 text-primary-600 dark:text-primary-400 mr-3" />
                    Edit User Roles
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
                <!-- User Profile Card -->
                <div class="bg-white dark:bg-gray-800 rounded-xl p-4 mb-6 border border-gray-200 dark:border-gray-700">
                  <div class="flex items-center space-x-4">
                    <!-- Avatar -->
                    <div class="relative">
                      <div class="h-14 w-14 rounded-xl bg-primary-600 dark:bg-primary-500 flex items-center justify-center shadow-lg">
                        <span class="text-white font-bold text-lg">{{ getUserInitials(user) }}</span>
                      </div>
                      <!-- Status Badge -->
                      <div class="absolute -bottom-1 -right-1">
                        <span 
                          :class="[
                            'inline-flex items-center justify-center w-5 h-5 rounded-full text-white font-semibold text-xs shadow-md',
                            user.isActive ? 'bg-success-500' : 'bg-danger-500'
                          ]"
                        >
                          <CheckCircleIcon v-if="user.isActive" class="w-3 h-3" />
                          <XMarkIcon v-else class="w-3 h-3" />
                        </span>
                      </div>
                    </div>

                    <!-- User Info -->
                    <div class="flex-1 min-w-0">
                      <h4 class="text-lg font-semibold text-gray-900 dark:text-white truncate">
                        {{ user.firstName }} {{ user.lastName }}
                      </h4>
                      <p class="text-sm text-gray-600 dark:text-gray-400 truncate">{{ user.email }}</p>
                      <p v-if="user.username" class="text-xs text-gray-500 dark:text-gray-400">@{{ user.username }}</p>
                      
                      <!-- Current Plan -->
                      <div class="mt-2">
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100 dark:bg-gray-800 text-gray-800 dark:text-gray-200 border border-gray-200 dark:border-gray-700">
                          {{ user.plan || 'Free' }} Plan
                        </span>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Current Roles Display -->
                <div class="mb-6">
                  <h4 class="text-sm font-semibold text-gray-900 dark:text-white mb-2">Current Roles</h4>
                  <div class="flex flex-wrap gap-2">
                    <span
                      v-for="role in user.roles"
                      :key="role"
                      :class="[
                        'inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold border',
                        role === 'ROLE_ADMIN'
                          ? 'bg-primary-100 dark:bg-primary-900 text-primary-800 dark:text-primary-200 border-primary-200 dark:border-primary-800'
                          : 'bg-gray-100 dark:bg-gray-800 text-gray-800 dark:text-gray-200 border-gray-200 dark:border-gray-700'
                      ]"
                    >
                      <StarIcon v-if="role === 'ROLE_ADMIN'" class="w-3 h-3 mr-1" />
                      <UserIcon v-else class="w-3 h-3 mr-1" />
                      {{ formatRole(role) }}
                    </span>
                  </div>
                </div>

                <!-- Role Selection -->
                <div class="space-y-4">
                  <div>
                    <h4 class="text-sm font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                      <CogIcon class="w-4 h-4 mr-2 text-gray-600 dark:text-gray-400" />
                      Assign Roles
                    </h4>
                    
                    <div class="space-y-3">
                      <div
                        v-for="role in availableRoles"
                        :key="role.value"
                        class="relative"
                      >
                        <label
                          :class="[
                            'flex items-start p-4 border-2 rounded-xl cursor-pointer transition-all duration-200',
                            selectedRoles.includes(role.value)
                              ? 'border-gray-400 dark:border-gray-500 bg-gray-100 dark:bg-gray-700 shadow-md'
                              : 'border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700'
                          ]"
                        >
                          <div class="flex items-center h-6">
                            <input
                              v-model="selectedRoles"
                              :value="role.value"
                              type="checkbox"
                              class="h-5 w-5 text-primary-600 focus:ring-primary-500 border-gray-300 dark:border-gray-600 rounded transition-colors bg-white dark:bg-gray-700"
                            />
                          </div>
                          <div class="ml-4 flex-1">
                            <div class="flex items-center space-x-2">
                              <StarIcon v-if="role.value === 'ROLE_ADMIN'" class="w-5 h-5 text-gray-600 dark:text-gray-400" />
                              <UserIcon v-else class="w-5 h-5 text-gray-600 dark:text-gray-400" />
                              <span class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ role.label }}</span>
                              <span
                                v-if="role.value === 'ROLE_ADMIN'"
                                class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-200 dark:bg-gray-600 text-gray-800 dark:text-gray-200"
                              >
                                High Access
                              </span>
                            </div>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">{{ role.description }}</p>
                            
                            <!-- Role Features -->
                            <div class="mt-2">
                              <div class="flex flex-wrap gap-1">
                                <span
                                  v-for="feature in role.features"
                                  :key="feature"
                                  class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300"
                                >
                                  {{ feature }}
                                </span>
                              </div>
                            </div>
                          </div>
                        </label>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Admin Warning -->
                <TransitionRoot
                  as="template"
                  :show="hasAdminRole"
                  enter="transition ease-out duration-200"
                  enter-from="opacity-0 transform scale-95"
                  enter-to="opacity-100 transform scale-100"
                  leave="transition ease-in duration-150"
                  leave-from="opacity-100 transform scale-100"
                  leave-to="opacity-0 transform scale-95"
                >
                  <div class="mt-6 p-4 bg-warning-50 dark:bg-warning-900/20 border-2 border-warning-200 dark:border-warning-800 rounded-xl">
                    <div class="flex items-start">
                      <div class="flex-shrink-0">
                        <ExclamationTriangleIcon class="w-6 h-6 text-warning-600 dark:text-warning-400" />
                      </div>
                      <div class="ml-3">
                        <h3 class="text-sm font-semibold text-warning-800 dark:text-warning-200">Administrator Access Warning</h3>
                        <div class="mt-2 text-sm text-warning-700 dark:text-warning-300">
                          <p>This user will gain full administrative privileges including:</p>
                          <ul class="list-disc list-inside mt-1 space-y-1">
                            <li>Access to all user data and management</li>
                            <li>System configuration and settings</li>
                            <li>Financial and billing information</li>
                            <li>Platform analytics and reporting</li>
                          </ul>
                          <p class="mt-2 font-medium">Please ensure this user is trusted and authorized.</p>
                        </div>
                      </div>
                    </div>
                  </div>
                </TransitionRoot>

                <!-- Changes Summary -->
                <div v-if="hasRoleChanges" class="mt-6 p-4 bg-primary-50 dark:bg-primary-900/20 border border-primary-200 dark:border-primary-800 rounded-xl">
                  <h4 class="text-sm font-semibold text-primary-900 dark:text-primary-100 mb-2 flex items-center">
                    <InformationCircleIcon class="w-4 h-4 mr-2" />
                    Role Changes Summary
                  </h4>
                  <div class="space-y-2 text-sm">
                    <div v-if="rolesToAdd.length > 0" class="flex items-center space-x-2">
                      <PlusIcon class="w-4 h-4 text-success-600 dark:text-success-400" />
                      <span class="text-success-800 dark:text-success-200">Adding: {{ rolesToAdd.map(formatRole).join(', ') }}</span>
                    </div>
                    <div v-if="rolesToRemove.length > 0" class="flex items-center space-x-2">
                      <MinusIcon class="w-4 h-4 text-danger-600 dark:text-danger-400" />
                      <span class="text-danger-800 dark:text-danger-200">Removing: {{ rolesToRemove.map(formatRole).join(', ') }}</span>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Modal Footer -->
              <div class="border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-6 py-4">
                <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                  <div class="text-sm text-gray-500 dark:text-gray-400">
                    Changes will take effect immediately
                  </div>
                  
                  <div class="flex items-center space-x-3 w-full sm:w-auto">
                    <button
                      @click="$emit('close')"
                      class="px-4 py-2 text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 focus:ring-offset-white dark:focus:ring-offset-gray-900 transition-colors flex-shrink-0"
                    >
                      Cancel
                    </button>
                    <button
                      @click="saveRoles"
                      :disabled="loading || !hasRoleChanges"
                      class="px-6 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 focus:ring-offset-white dark:focus:ring-offset-gray-900 transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center flex-shrink-0"
                    >
                      <CogIcon v-if="!loading" class="w-4 h-4 mr-2" />
                      <div v-else class="animate-spin rounded-full h-4 w-4 border-2 border-white border-t-transparent mr-2"></div>
                      {{ loading ? 'Updating...' : 'Update Roles' }}
                    </button>
                  </div>
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
import { ref, computed, onMounted } from 'vue'
import { useNotifications } from '@/composables/useNotifications'
import { adminAPI } from '@/services/api'
import type { AdminUser } from '@/types'
import { 
  Dialog, 
  DialogPanel, 
  DialogTitle, 
  TransitionChild, 
  TransitionRoot 
} from '@headlessui/vue'

// Heroicons
import { 
  XMarkIcon, 
  ExclamationTriangleIcon,
  ShieldCheckIcon,
  CheckCircleIcon,
  UserIcon,
  StarIcon,
  CogIcon,
  InformationCircleIcon,
  PlusIcon,
  MinusIcon
} from '@heroicons/vue/24/outline'

interface Props {
  user: AdminUser
}

interface Emits {
  (e: 'close'): void
  (e: 'updated', user: AdminUser): void
}

const props = defineProps<Props>()
const emit = defineEmits<Emits>()

const { showSuccess, showError } = useNotifications()

const loading = ref(false)
const selectedRoles = ref<string[]>([])

const availableRoles = [
  {
    value: 'ROLE_USER',
    label: 'User',
    description: 'Standard user access with basic platform features',
    features: ['Create Projects', 'Upload Media', 'Basic Exports', 'Personal Settings']
  },
  {
    value: 'ROLE_ADMIN',
    label: 'Administrator',
    description: 'Full administrative access to all platform features',
    features: ['User Management', 'System Settings', 'Analytics', 'Billing', 'All User Features']
  }
]

// Computed properties
const hasAdminRole = computed(() => {
  return selectedRoles.value.includes('ROLE_ADMIN')
})

const hasRoleChanges = computed(() => {
  const currentRoles = [...props.user.roles].sort()
  const newRoles = [...selectedRoles.value].sort()
  return JSON.stringify(currentRoles) !== JSON.stringify(newRoles)
})

const rolesToAdd = computed(() => {
  return selectedRoles.value.filter(role => !props.user.roles.includes(role))
})

const rolesToRemove = computed(() => {
  return props.user.roles.filter(role => !selectedRoles.value.includes(role))
})

// Methods
const getUserInitials = (user: AdminUser): string => {
  return `${user.firstName?.[0] || ''}${user.lastName?.[0] || ''}`.toUpperCase() || 'U'
}

const formatRole = (role: string): string => {
  return role.replace('ROLE_', '').toLowerCase().replace(/^\w/, c => c.toUpperCase())
}

const saveRoles = async () => {
  if (!hasRoleChanges.value) {
    emit('close')
    return
  }

  // Confirmation for removing admin role
  if (rolesToRemove.value.includes('ROLE_ADMIN')) {
    const confirmed = confirm(
      `Are you sure you want to remove Administrator access from ${props.user.firstName} ${props.user.lastName}? This action cannot be undone and will immediately revoke all admin privileges.`
    )
    if (!confirmed) return
  }

  loading.value = true
  try {
    const response = await adminAPI.updateUserRoles(props.user.id, { roles: selectedRoles.value })
    const updatedUser = response.data.data.user as unknown as AdminUser
    
    emit('updated', updatedUser)
    
    // Show appropriate success message
    if (rolesToAdd.value.length > 0 && rolesToRemove.value.length > 0) {
      showSuccess('User roles updated successfully')
    } else if (rolesToAdd.value.length > 0) {
      showSuccess(`Added ${rolesToAdd.value.map(formatRole).join(', ')} role(s) successfully`)
    } else if (rolesToRemove.value.length > 0) {
      showSuccess(`Removed ${rolesToRemove.value.map(formatRole).join(', ')} role(s) successfully`)
    }
    
    emit('close')
  } catch (error) {
    console.error('Failed to update user roles:', error)
    showError('Failed to update user roles')
  } finally {
    loading.value = false
  }
}

// Initialize selected roles when component mounts
onMounted(() => {
  selectedRoles.value = [...props.user.roles]
})
</script>

<style scoped>
/* Custom scrollbar for modal content */
.overflow-y-auto::-webkit-scrollbar {
  width: 6px;
}

.overflow-y-auto::-webkit-scrollbar-track {
  background: #f1f5f9;
  border-radius: 3px;
}

.overflow-y-auto::-webkit-scrollbar-thumb {
  background: #cbd5e1;
  border-radius: 3px;
}

.overflow-y-auto::-webkit-scrollbar-thumb:hover {
  background: #94a3b8;
}

/* Enhanced backdrop blur for better visual hierarchy */
.backdrop-blur-sm {
  backdrop-filter: blur(4px);
}

/* Smooth transitions for interactive elements */
.transition-all {
  transition-property: all;
  transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
  transition-duration: 200ms;
}

/* Focus styles for better accessibility */
input[type="checkbox"]:focus {
  outline: 2px solid transparent;
  outline-offset: 2px;
  box-shadow: 0 0 0 2px #3b82f6;
}

button:focus {
  outline: 2px solid transparent;
  outline-offset: 2px;
}

/* Animation for loading spinner */
@keyframes spin {
  from {
    transform: rotate(0deg);
  }
  to {
    transform: rotate(360deg);
  }
}

.animate-spin {
  animation: spin 1s linear infinite;
}
</style>

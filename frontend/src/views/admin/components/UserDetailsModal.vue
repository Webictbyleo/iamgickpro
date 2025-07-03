<template>
  <!-- Enhanced User Details Modal with HeadlessUI -->
  <TransitionRoot appear :show="show" as="template">
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
            <DialogPanel class="w-full max-w-4xl transform overflow-hidden rounded-2xl bg-white text-left align-middle shadow-2xl transition-all max-h-[90vh] flex flex-col">
              <!-- Modal Header -->
              <div class="relative px-6 py-6 border-b border-gray-200 bg-gradient-to-r from-blue-50 to-indigo-50">
                <div class="flex items-center justify-between">
                  <DialogTitle as="h3" class="text-2xl font-bold text-gray-900 flex items-center">
                    <UserIcon class="w-8 h-8 text-blue-600 mr-3" />
                    User Details
                  </DialogTitle>
                  <button
                    @click="$emit('close')"
                    class="rounded-full p-2 text-gray-400 hover:text-gray-600 hover:bg-white/50 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all duration-200"
                    aria-label="Close modal"
                  >
                    <XMarkIcon class="w-6 h-6" />
                  </button>
                </div>
              </div>

              <!-- Modal Content -->
              <div class="flex-1 overflow-y-auto">
                <!-- Loading State -->
                <div v-if="loading" class="p-8">
                  <div class="flex flex-col items-center justify-center py-12">
                    <div class="relative">
                      <div class="animate-spin rounded-full h-16 w-16 border-4 border-blue-200"></div>
                      <div class="animate-spin rounded-full h-16 w-16 border-4 border-blue-600 border-t-transparent absolute top-0"></div>
                    </div>
                    <p class="mt-6 text-lg text-gray-600 animate-pulse">Loading user details...</p>
                  </div>
                </div>

                <!-- User Details Content -->
                <div v-else-if="user" class="p-6 space-y-6">
                  <!-- User Header Card -->
                  <div class="bg-gradient-to-br from-blue-50 via-indigo-50 to-purple-50 rounded-2xl p-6 border border-blue-100">
                    <div class="flex items-start space-x-6">
                      <!-- Avatar -->
                      <div class="flex-shrink-0">
                        <div class="relative">
                          <div class="h-24 w-24 rounded-2xl bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center shadow-lg">
                            <span class="text-white font-bold text-2xl">{{ getUserInitials }}</span>
                          </div>
                          <!-- Status Badge -->
                          <div class="absolute -bottom-2 -right-2">
                            <span 
                              :class="[
                                'inline-flex items-center justify-center w-8 h-8 rounded-full text-white font-semibold text-xs shadow-lg',
                                user.isActive ? 'bg-green-500' : 'bg-red-500'
                              ]"
                            >
                              <CheckCircleIcon v-if="user.isActive" class="w-5 h-5" />
                              <XMarkIcon v-else class="w-5 h-5" />
                            </span>
                          </div>
                        </div>
                      </div>

                      <!-- User Info -->
                      <div class="flex-1 min-w-0">
                        <div class="flex items-center space-x-3 mb-2">
                          <h4 class="text-2xl font-bold text-gray-900 truncate">
                            {{ user.firstName }} {{ user.lastName }}
                          </h4>
                          <div class="flex space-x-2">
                            <span
                              v-for="role in user.roles"
                              :key="role"
                              :class="[
                                'inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold',
                                role === 'ROLE_ADMIN'
                                  ? 'bg-purple-100 text-purple-800 border border-purple-200'
                                  : 'bg-blue-100 text-blue-800 border border-blue-200'
                              ]"
                            >
                              <StarIcon v-if="role === 'ROLE_ADMIN'" class="w-3 h-3 mr-1" />
                              <UserIcon v-else class="w-3 h-3 mr-1" />
                              {{ formatRole(role) }}
                            </span>
                          </div>
                        </div>
                        
                        <div class="space-y-2">
                          <div class="flex items-center text-gray-600">
                            <EnvelopeIcon class="w-4 h-4 mr-2 text-gray-400" />
                            <span class="text-sm">{{ user.email }}</span>
                            <CheckCircleIcon v-if="user.emailVerified" class="w-4 h-4 ml-2 text-green-500" title="Email verified" />
                            <ExclamationTriangleIcon v-else class="w-4 h-4 ml-2 text-yellow-500" title="Email not verified" />
                          </div>
                          
                          <div v-if="user.username" class="flex items-center text-gray-600">
                            <AtSymbolIcon class="w-4 h-4 mr-2 text-gray-400" />
                            <span class="text-sm">{{ user.username }}</span>
                          </div>
                          
                          <div class="flex items-center text-gray-600">
                            <IdentificationIcon class="w-4 h-4 mr-2 text-gray-400" />
                            <span class="text-sm font-mono">{{ user.uuid }}</span>
                            <button
                              @click="copyToClipboard(user.uuid)"
                              class="ml-2 p-1 rounded hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-blue-500"
                              title="Copy UUID"
                            >
                              <ClipboardIcon class="w-4 h-4 text-gray-400" />
                            </button>
                          </div>
                        </div>

                        <!-- Status Badges -->
                        <div class="mt-4 flex flex-wrap gap-2">
                          <span
                            :class="[
                              'inline-flex items-center px-3 py-1 rounded-full text-xs font-medium',
                              user.isActive
                                ? 'bg-green-100 text-green-800 border border-green-200'
                                : 'bg-red-100 text-red-800 border border-red-200'
                            ]"
                          >
                            {{ user.isActive ? 'Active' : 'Inactive' }}
                          </span>
                          
                          <span
                            v-if="user.isLocked"
                            class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800 border border-red-200"
                          >
                            <KeyIcon class="w-3 h-3 mr-1" />
                            Account Locked
                          </span>
                          
                          <span
                            :class="[
                              'inline-flex items-center px-3 py-1 rounded-full text-xs font-medium',
                              user.emailVerified
                                ? 'bg-blue-100 text-blue-800 border border-blue-200'
                                : 'bg-yellow-100 text-yellow-800 border border-yellow-200'
                            ]"
                          >
                            {{ user.emailVerified ? 'Email Verified' : 'Email Unverified' }}
                          </span>
                          
                          <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-purple-100 text-purple-800 border border-purple-200">
                            {{ user.plan || 'Free' }} Plan
                          </span>
                        </div>
                      </div>
                    </div>
                  </div>

                  <!-- Activity Statistics -->
                  <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div class="bg-white rounded-xl p-4 border border-gray-200 hover:border-blue-300 transition-colors">
                      <div class="flex items-center justify-between">
                        <div>
                          <p class="text-sm font-medium text-gray-600">Projects</p>
                          <p class="text-2xl font-bold text-blue-600">{{ user.counts?.projects || 0 }}</p>
                        </div>
                        <FolderIcon class="w-8 h-8 text-blue-500" />
                      </div>
                    </div>
                    
                    <div class="bg-white rounded-xl p-4 border border-gray-200 hover:border-green-300 transition-colors">
                      <div class="flex items-center justify-between">
                        <div>
                          <p class="text-sm font-medium text-gray-600">Media Files</p>
                          <p class="text-2xl font-bold text-green-600">{{ user.counts?.mediaFiles || 0 }}</p>
                        </div>
                        <PhotoIcon class="w-8 h-8 text-green-500" />
                      </div>
                    </div>
                    
                    <div class="bg-white rounded-xl p-4 border border-gray-200 hover:border-purple-300 transition-colors">
                      <div class="flex items-center justify-between">
                        <div>
                          <p class="text-sm font-medium text-gray-600">Exports</p>
                          <p class="text-2xl font-bold text-purple-600">{{ user.counts?.exportJobs || 0 }}</p>
                        </div>
                        <ArrowDownTrayIcon class="w-8 h-8 text-purple-500" />
                      </div>
                    </div>
                    
                    <div class="bg-white rounded-xl p-4 border border-gray-200 hover:border-yellow-300 transition-colors">
                      <div class="flex items-center justify-between">
                        <div>
                          <p class="text-sm font-medium text-gray-600">Subscriptions</p>
                          <p class="text-2xl font-bold text-yellow-600">{{ user.counts?.subscriptions || 0 }}</p>
                        </div>
                        <CreditCardIcon class="w-8 h-8 text-yellow-500" />
                      </div>
                    </div>
                  </div>

                  <!-- Professional Information -->
                  <div v-if="hasProfileInfo" class="bg-white rounded-xl p-6 border border-gray-200">
                    <h4 class="flex items-center text-lg font-semibold text-gray-900 mb-4">
                      <BriefcaseIcon class="w-5 h-5 mr-2 text-gray-600" />
                      Professional Information
                    </h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                      <div v-if="user.jobTitle" class="space-y-1">
                        <label class="text-sm font-medium text-gray-500">Job Title</label>
                        <p class="text-gray-900 font-medium">{{ user.jobTitle }}</p>
                      </div>
                      <div v-if="user.company" class="space-y-1">
                        <label class="text-sm font-medium text-gray-500">Company</label>
                        <p class="text-gray-900 font-medium">{{ user.company }}</p>
                      </div>
                      <div v-if="user.website" class="md:col-span-2 space-y-1">
                        <label class="text-sm font-medium text-gray-500">Website</label>
                        <p class="text-gray-900">
                          <a 
                            :href="user.website" 
                            target="_blank" 
                            class="text-blue-600 hover:text-blue-700 underline decoration-2 underline-offset-2 flex items-center"
                          >
                            {{ user.website }}
                            <ArrowTopRightOnSquareIcon class="w-4 h-4 ml-1" />
                          </a>
                        </p>
                      </div>
                      <div v-if="user.portfolio" class="md:col-span-2 space-y-1">
                        <label class="text-sm font-medium text-gray-500">Portfolio</label>
                        <p class="text-gray-900">
                          <a 
                            :href="user.portfolio" 
                            target="_blank" 
                            class="text-blue-600 hover:text-blue-700 underline decoration-2 underline-offset-2 flex items-center"
                          >
                            {{ user.portfolio }}
                            <ArrowTopRightOnSquareIcon class="w-4 h-4 ml-1" />
                          </a>
                        </p>
                      </div>
                    </div>
                  </div>

                  <!-- Bio -->
                  <div v-if="user.bio" class="bg-white rounded-xl p-6 border border-gray-200">
                    <h4 class="flex items-center text-lg font-semibold text-gray-900 mb-4">
                      <DocumentTextIcon class="w-5 h-5 mr-2 text-gray-600" />
                      Bio
                    </h4>
                    <div class="prose prose-gray max-w-none">
                      <p class="text-gray-700 leading-relaxed whitespace-pre-wrap">{{ user.bio }}</p>
                    </div>
                  </div>

                  <!-- Social Links -->
                  <div v-if="user.socialLinks && Object.keys(user.socialLinks).length > 0" class="bg-white rounded-xl p-6 border border-gray-200">
                    <h4 class="flex items-center text-lg font-semibold text-gray-900 mb-4">
                      <LinkIcon class="w-5 h-5 mr-2 text-gray-600" />
                      Social Links
                    </h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                      <div 
                        v-for="(url, platform) in user.socialLinks" 
                        :key="platform" 
                        class="flex items-center justify-between p-3 bg-gray-50 rounded-lg"
                      >
                        <span class="text-sm font-medium text-gray-700 capitalize flex items-center">
                          <GlobeAltIcon class="w-4 h-4 mr-2 text-gray-500" />
                          {{ platform }}
                        </span>
                        <a 
                          :href="url" 
                          target="_blank" 
                          class="text-blue-600 hover:text-blue-700 text-sm font-medium flex items-center"
                        >
                          View Profile
                          <ArrowTopRightOnSquareIcon class="w-3 h-3 ml-1" />
                        </a>
                      </div>
                    </div>
                  </div>

                  <!-- Account Timeline -->
                  <div class="bg-white rounded-xl p-6 border border-gray-200">
                    <h4 class="flex items-center text-lg font-semibold text-gray-900 mb-4">
                      <ClockIcon class="w-5 h-5 mr-2 text-gray-600" />
                      Account Timeline
                    </h4>
                    <div class="space-y-4">
                      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <div class="p-4 bg-blue-50 rounded-lg border border-blue-200">
                          <div class="flex items-center space-x-3">
                            <div class="p-2 bg-blue-100 rounded-full">
                              <CalendarIcon class="w-4 h-4 text-blue-600" />
                            </div>
                            <div>
                              <p class="text-sm font-medium text-blue-900">Account Created</p>
                              <p class="text-sm text-blue-700">{{ formatDate(user.createdAt) }}</p>
                            </div>
                          </div>
                        </div>
                        
                        <div v-if="user.lastLoginAt" class="p-4 bg-green-50 rounded-lg border border-green-200">
                          <div class="flex items-center space-x-3">
                            <div class="p-2 bg-green-100 rounded-full">
                              <ArrowRightOnRectangleIcon class="w-4 h-4 text-green-600" />
                            </div>
                            <div>
                              <p class="text-sm font-medium text-green-900">Last Login</p>
                              <p class="text-sm text-green-700">{{ formatDate(user.lastLoginAt) }}</p>
                            </div>
                          </div>
                        </div>
                        
                        <div v-if="user.updatedAt" class="p-4 bg-purple-50 rounded-lg border border-purple-200">
                          <div class="flex items-center space-x-3">
                            <div class="p-2 bg-purple-100 rounded-full">
                              <PencilIcon class="w-4 h-4 text-purple-600" />
                            </div>
                            <div>
                              <p class="text-sm font-medium text-purple-900">Last Updated</p>
                              <p class="text-sm text-purple-700">{{ formatDate(user.updatedAt) }}</p>
                            </div>
                          </div>
                        </div>
                      </div>
                      
                      <!-- Security Info -->
                      <div v-if="user.failedLoginAttempts > 0" class="p-4 bg-yellow-50 rounded-lg border border-yellow-200">
                        <div class="flex items-center space-x-3">
                          <div class="p-2 bg-yellow-100 rounded-full">
                            <ExclamationTriangleIcon class="w-4 h-4 text-yellow-600" />
                          </div>
                          <div>
                            <p class="text-sm font-medium text-yellow-900">Security Alert</p>
                            <p class="text-sm text-yellow-700">{{ user.failedLoginAttempts }} failed login attempts</p>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>

                  <!-- User Settings -->
                  <div v-if="user.settings || user.timezone || user.language" class="bg-white rounded-xl p-6 border border-gray-200">
                    <h4 class="flex items-center text-lg font-semibold text-gray-900 mb-4">
                      <CogIcon class="w-5 h-5 mr-2 text-gray-600" />
                      User Settings
                    </h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                      <div v-if="user.timezone" class="space-y-1">
                        <label class="text-sm font-medium text-gray-500">Timezone</label>
                        <p class="text-gray-900 font-medium">{{ user.timezone }}</p>
                      </div>
                      <div v-if="user.language" class="space-y-1">
                        <label class="text-sm font-medium text-gray-500">Language</label>
                        <p class="text-gray-900 font-medium">{{ user.language }}</p>
                      </div>
                    </div>
                    <div v-if="user.settings && Object.keys(user.settings).length > 0" class="mt-6">
                      <label class="text-sm font-medium text-gray-500 mb-2 block">Custom Settings</label>
                      <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
                        <pre class="text-xs text-gray-700 overflow-x-auto whitespace-pre-wrap">{{ JSON.stringify(user.settings, null, 2) }}</pre>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Error State -->
                <div v-else class="p-8">
                  <div class="flex flex-col items-center justify-center py-12">
                    <div class="p-4 bg-red-100 rounded-full mb-4">
                      <ExclamationCircleIcon class="w-12 h-12 text-red-600" />
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Failed to Load User Details</h3>
                    <p class="text-gray-600 mb-4">There was an error loading the user information.</p>
                    <button
                      @click="retryLoad"
                      class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors"
                    >
                      Try Again
                    </button>
                  </div>
                </div>
              </div>

              <!-- Modal Footer / Actions -->
              <div class="border-t border-gray-200 bg-gray-50 px-6 py-4">
                <div class="flex items-center justify-between">
                  <div class="flex items-center space-x-2 text-sm text-gray-500">
                    <ClockIcon class="w-4 h-4" />
                    <span>Last updated {{ user ? formatRelativeTime(user.updatedAt || user.createdAt) : '' }}</span>
                  </div>
                  
                  <div class="flex items-center space-x-3">
                    <button
                      @click="$emit('close')"
                      class="px-4 py-2 text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition-colors"
                    >
                      Close
                    </button>
                    
                    <button
                      v-if="user && !user.isActive"
                      @click="handleActivateUser"
                      :disabled="actionLoading"
                      class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center"
                    >
                      <UserPlusIcon v-if="!actionLoading" class="w-4 h-4 mr-2" />
                      <div v-else class="animate-spin rounded-full h-4 w-4 border-2 border-white border-t-transparent mr-2"></div>
                      {{ actionLoading ? 'Activating...' : 'Activate User' }}
                    </button>
                    
                    <button
                      v-if="user && user.isActive"
                      @click="handleDeactivateUser"
                      :disabled="actionLoading"
                      class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center"
                    >
                      <UserMinusIcon v-if="!actionLoading" class="w-4 h-4 mr-2" />
                      <div v-else class="animate-spin rounded-full h-4 w-4 border-2 border-white border-t-transparent mr-2"></div>
                      {{ actionLoading ? 'Deactivating...' : 'Deactivate User' }}
                    </button>
                    
                    <button
                      v-if="user"
                      @click="handleEditRoles"
                      class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors flex items-center"
                    >
                      <CogIcon class="w-4 h-4 mr-2" />
                      Edit Roles
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
import { ref, watch, computed } from 'vue'
import { useNotifications } from '@/composables/useNotifications'
import { adminAPI } from '@/services/api'
import type { AdminUserDetails } from '@/types'
import { 
  Dialog, 
  DialogPanel, 
  DialogTitle, 
  TransitionChild, 
  TransitionRoot 
} from '@headlessui/vue'

// Heroicons - All icons used in the template
import { 
  XMarkIcon,
  CheckCircleIcon,
  ExclamationTriangleIcon,
  ExclamationCircleIcon,
  KeyIcon,
  UserIcon,
  EnvelopeIcon,
  AtSymbolIcon,
  IdentificationIcon,
  ClipboardIcon,
  FolderIcon,
  PhotoIcon,
  ArrowDownTrayIcon,
  CreditCardIcon,
  BriefcaseIcon,
  ArrowTopRightOnSquareIcon,
  DocumentTextIcon,
  LinkIcon,
  GlobeAltIcon,
  ClockIcon,
  CalendarIcon,
  ArrowRightOnRectangleIcon,
  PencilIcon,
  CogIcon,
  UserPlusIcon,
  UserMinusIcon,
  StarIcon // Using StarIcon instead of CrownIcon
} from '@heroicons/vue/24/outline'

const { showSuccess, showError } = useNotifications()

// Props
interface Props {
  show: boolean
  userId?: number | null
}

const props = defineProps<Props>()

// Emits
const emit = defineEmits<{
  close: []
  updated: []
  editRoles: [user: AdminUserDetails]
}>()

// State
const loading = ref(false)
const actionLoading = ref(false)
const user = ref<AdminUserDetails | null>(null)

// Computed properties
const getUserInitials = computed(() => {
  if (!user.value) return 'U'
  return `${user.value.firstName?.[0] || ''}${user.value.lastName?.[0] || ''}`.toUpperCase() || 'U'
})

const hasProfileInfo = computed(() => {
  return user.value && (user.value.jobTitle || user.value.company || user.value.website || user.value.portfolio)
})

// Methods
const loadUserDetails = async (userId: number) => {
  if (!userId) return

  loading.value = true
  try {
    const response = await adminAPI.getUserDetails(userId)
    user.value = response.data.data.user
  } catch (error) {
    console.error('Failed to load user details:', error)
    showError('Failed to load user details')
    user.value = null
  } finally {
    loading.value = false
  }
}

const retryLoad = () => {
  if (props.userId) {
    loadUserDetails(props.userId)
  }
}

const copyToClipboard = async (text: string) => {
  try {
    await navigator.clipboard.writeText(text)
    showSuccess('Copied to clipboard')
  } catch (error) {
    console.error('Failed to copy to clipboard:', error)
    showError('Failed to copy to clipboard')
  }
}

const handleActivateUser = async () => {
  if (!user.value) return
  
  actionLoading.value = true
  try {
    await adminAPI.updateUserStatus(user.value.id, { active: true })
    user.value.isActive = true
    showSuccess('User activated successfully')
    emit('updated')
  } catch (error) {
    console.error('Failed to activate user:', error)
    showError('Failed to activate user')
  } finally {
    actionLoading.value = false
  }
}

const handleDeactivateUser = async () => {
  if (!user.value) return
  
  if (!confirm(`Are you sure you want to deactivate ${user.value.firstName} ${user.value.lastName}?`)) {
    return
  }
  
  actionLoading.value = true
  try {
    await adminAPI.updateUserStatus(user.value.id, { active: false })
    user.value.isActive = false
    showSuccess('User deactivated successfully')
    emit('updated')
  } catch (error) {
    console.error('Failed to deactivate user:', error)
    showError('Failed to deactivate user')
  } finally {
    actionLoading.value = false
  }
}

const handleEditRoles = () => {
  // Emit the user data to parent component to handle role editing
  if (user.value) {
    emit('editRoles', user.value)
  }
}

const formatRole = (role: string) => {
  return role.replace('ROLE_', '').toLowerCase().replace(/^\w/, c => c.toUpperCase())
}

const formatDate = (dateString: string) => {
  if (!dateString) return 'Never'
  
  const date = new Date(dateString)
  return date.toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

const formatRelativeTime = (dateString: string) => {
  if (!dateString) return 'never'
  
  const date = new Date(dateString)
  const now = new Date()
  const diffInSeconds = Math.floor((now.getTime() - date.getTime()) / 1000)
  
  if (diffInSeconds < 60) return 'just now'
  if (diffInSeconds < 3600) return `${Math.floor(diffInSeconds / 60)} minutes ago`
  if (diffInSeconds < 86400) return `${Math.floor(diffInSeconds / 3600)} hours ago`
  if (diffInSeconds < 604800) return `${Math.floor(diffInSeconds / 86400)} days ago`
  
  return formatDate(dateString)
}

// Watchers
watch(() => props.userId, (newUserId) => {
  if (newUserId && props.show) {
    loadUserDetails(newUserId)
  }
}, { immediate: true })

watch(() => props.show, (show) => {
  if (show && props.userId) {
    loadUserDetails(props.userId)
  } else if (!show) {
    user.value = null
  }
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

/* Card hover effects */
.hover\:border-blue-300:hover {
  border-color: #93c5fd;
}

.hover\:border-green-300:hover {
  border-color: #86efac;
}

.hover\:border-purple-300:hover {
  border-color: #d8b4fe;
}

.hover\:border-yellow-300:hover {
  border-color: #fde047;
}

/* Focus styles for better accessibility */
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

/* Pulse animation for loading text */
@keyframes pulse {
  0%, 100% {
    opacity: 1;
  }
  50% {
    opacity: 0.5;
  }
}

.animate-pulse {
  animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}
</style>

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
            <DialogPanel class="w-full max-w-4xl transform overflow-hidden rounded-2xl bg-white dark:bg-gray-800 text-left align-middle shadow-2xl transition-all max-h-[90vh] flex flex-col">
              <!-- Modal Header -->
              <div class="relative px-6 py-6 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800">
                <div class="flex items-center justify-between">
                  <DialogTitle as="h3" class="text-2xl font-bold text-gray-900 dark:text-white flex items-center">
                    <UserIcon class="w-8 h-8 text-primary-600 dark:text-primary-400 mr-3" />
                    User Details
                  </DialogTitle>
                  <button
                    @click="$emit('close')"
                    class="rounded-full p-2 text-gray-400 dark:text-gray-500 hover:text-gray-600 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-primary-500 transition-colors"
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
                    <div class="animate-spin rounded-full h-16 w-16 border-4 border-gray-200 dark:border-gray-700 border-t-primary-600"></div>
                    <p class="mt-6 text-lg text-gray-600 dark:text-gray-400 animate-pulse">Loading user details...</p>
                  </div>
                </div>

                <!-- User Details Content -->
                <div v-else-if="user" class="p-6 space-y-6">
                  <!-- User Header Card -->
                  <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 border border-gray-200 dark:border-gray-700">
                    <div class="flex items-start space-x-6">
                      <!-- Avatar -->
                      <div class="flex-shrink-0">
                        <div class="relative">
                          <div class="h-24 w-24 rounded-2xl bg-gray-600 dark:bg-gray-500 flex items-center justify-center shadow-lg">
                            <span class="text-white font-bold text-2xl">{{ getUserInitials }}</span>
                          </div>
                          <!-- Status Badge -->
                          <div class="absolute -bottom-2 -right-2">
                            <span 
                              :class="[
                                'inline-flex items-center justify-center w-8 h-8 rounded-full text-white font-semibold text-xs shadow-lg',
                                user.isActive ? 'bg-success-500' : 'bg-danger-500'
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
                          <h4 class="text-2xl font-bold text-gray-900 dark:text-white truncate">
                            {{ user.firstName }} {{ user.lastName }}
                          </h4>
                          <div class="flex space-x-2">
                            <span
                              v-for="role in user.roles"
                              :key="role"
                              class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200 border border-gray-200 dark:border-gray-600"
                            >
                              <StarIcon v-if="role === 'ROLE_ADMIN'" class="w-3 h-3 mr-1" />
                              <UserIcon v-else class="w-3 h-3 mr-1" />
                              {{ formatRole(role) }}
                            </span>
                          </div>
                        </div>
                        
                        <div class="space-y-2">
                          <div class="flex items-center text-gray-600 dark:text-gray-400">
                            <EnvelopeIcon class="w-4 h-4 mr-2 text-gray-400 dark:text-gray-500" />
                            <span class="text-sm">{{ user.email }}</span>
                            <CheckCircleIcon v-if="user.emailVerified" class="w-4 h-4 ml-2 text-success-500" title="Email verified" />
                            <ExclamationTriangleIcon v-else class="w-4 h-4 ml-2 text-warning-500" title="Email not verified" />
                          </div>
                          
                          <div v-if="user.username" class="flex items-center text-gray-600 dark:text-gray-400">
                            <AtSymbolIcon class="w-4 h-4 mr-2 text-gray-400 dark:text-gray-500" />
                            <span class="text-sm">{{ user.username }}</span>
                          </div>
                          
                          <div class="flex items-center text-gray-600 dark:text-gray-400">
                            <IdentificationIcon class="w-4 h-4 mr-2 text-gray-400 dark:text-gray-500" />
                            <span class="text-sm font-mono">{{ user.uuid }}</span>
                            <button
                              @click="copyToClipboard(user.uuid)"
                              class="ml-2 p-1 rounded hover:bg-gray-100 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-primary-500 transition-colors"
                              title="Copy UUID"
                            >
                              <ClipboardIcon class="w-4 h-4 text-gray-400 dark:text-gray-500" />
                            </button>
                          </div>
                        </div>

                        <!-- Status Badges -->
                        <div class="mt-4 flex flex-wrap gap-2">
                          <span
                            :class="[
                              'inline-flex items-center px-3 py-1 rounded-full text-xs font-medium',
                              user.isActive
                                ? 'bg-success-100 dark:bg-success-900 text-success-800 dark:text-success-200 border border-success-200 dark:border-success-800'
                                : 'bg-danger-100 dark:bg-danger-900 text-danger-800 dark:text-danger-200 border border-danger-200 dark:border-danger-800'
                            ]"
                          >
                            {{ user.isActive ? 'Active' : 'Inactive' }}
                          </span>
                          
                          <span
                            v-if="user.isLocked"
                            class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-danger-100 dark:bg-danger-900 text-danger-800 dark:text-danger-200 border border-danger-200 dark:border-danger-800"
                          >
                            <KeyIcon class="w-3 h-3 mr-1" />
                            Account Locked
                          </span>
                          
                          <span
                            :class="[
                              'inline-flex items-center px-3 py-1 rounded-full text-xs font-medium',
                              user.emailVerified
                                ? 'bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200 border border-gray-200 dark:border-gray-600'
                                : 'bg-warning-100 dark:bg-warning-900 text-warning-800 dark:text-warning-200 border border-warning-200 dark:border-warning-800'
                            ]"
                          >
                            {{ user.emailVerified ? 'Email Verified' : 'Email Unverified' }}
                          </span>
                          
                          <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-gray-100 dark:bg-gray-800 text-gray-800 dark:text-gray-200 border border-gray-200 dark:border-gray-700">
                            {{ user.plan || 'Free' }} Plan
                          </span>
                        </div>
                      </div>
                    </div>
                  </div>

                  <!-- Activity Statistics -->
                  <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600 transition-colors">
                      <div class="flex items-center justify-between">
                        <div>
                          <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Projects</p>
                          <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ user.counts?.projects || 0 }}</p>
                        </div>
                        <FolderIcon class="w-8 h-8 text-gray-500 dark:text-gray-400" />
                      </div>
                    </div>
                    
                    <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600 transition-colors">
                      <div class="flex items-center justify-between">
                        <div>
                          <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Media Files</p>
                          <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ user.counts?.mediaFiles || 0 }}</p>
                        </div>
                        <PhotoIcon class="w-8 h-8 text-gray-500 dark:text-gray-400" />
                      </div>
                    </div>
                    
                    <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600 transition-colors">
                      <div class="flex items-center justify-between">
                        <div>
                          <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Exports</p>
                          <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ user.counts?.exportJobs || 0 }}</p>
                        </div>
                        <ArrowDownTrayIcon class="w-8 h-8 text-gray-500 dark:text-gray-400" />
                      </div>
                    </div>
                    
                    <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600 transition-colors">
                      <div class="flex items-center justify-between">
                        <div>
                          <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Subscriptions</p>
                          <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ user.counts?.subscriptions || 0 }}</p>
                        </div>
                        <CreditCardIcon class="w-8 h-8 text-gray-500 dark:text-gray-400" />
                      </div>
                    </div>
                  </div>

                  <!-- Professional Information -->
                  <div v-if="hasProfileInfo" class="bg-white dark:bg-gray-800 rounded-xl p-6 border border-gray-200 dark:border-gray-700">
                    <h4 class="flex items-center text-lg font-semibold text-gray-900 dark:text-white mb-4">
                      <BriefcaseIcon class="w-5 h-5 mr-2 text-gray-600 dark:text-gray-400" />
                      Professional Information
                    </h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                      <div v-if="user.jobTitle" class="space-y-1">
                        <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Job Title</label>
                        <p class="text-gray-900 dark:text-white font-medium">{{ user.jobTitle }}</p>
                      </div>
                      <div v-if="user.company" class="space-y-1">
                        <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Company</label>
                        <p class="text-gray-900 dark:text-white font-medium">{{ user.company }}</p>
                      </div>
                      <div v-if="user.website" class="md:col-span-2 space-y-1">
                        <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Website</label>
                        <p class="text-gray-900 dark:text-white">
                          <a 
                            :href="user.website" 
                            target="_blank" 
                            class="text-primary-600 dark:text-primary-400 hover:text-primary-700 dark:hover:text-primary-300 underline decoration-2 underline-offset-2 flex items-center"
                          >
                            {{ user.website }}
                            <ArrowTopRightOnSquareIcon class="w-4 h-4 ml-1" />
                          </a>
                        </p>
                      </div>
                      <div v-if="user.portfolio" class="md:col-span-2 space-y-1">
                        <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Portfolio</label>
                        <p class="text-gray-900 dark:text-white">
                          <a 
                            :href="user.portfolio" 
                            target="_blank" 
                            class="text-primary-600 dark:text-primary-400 hover:text-primary-700 dark:hover:text-primary-300 underline decoration-2 underline-offset-2 flex items-center"
                          >
                            {{ user.portfolio }}
                            <ArrowTopRightOnSquareIcon class="w-4 h-4 ml-1" />
                          </a>
                        </p>
                      </div>
                    </div>
                  </div>

                  <!-- Bio -->
                  <div v-if="user.bio" class="bg-white dark:bg-gray-800 rounded-xl p-6 border border-gray-200 dark:border-gray-700">
                    <h4 class="flex items-center text-lg font-semibold text-gray-900 dark:text-white mb-4">
                      <DocumentTextIcon class="w-5 h-5 mr-2 text-gray-600 dark:text-gray-400" />
                      Bio
                    </h4>
                    <div class="prose prose-gray max-w-none">
                      <p class="text-gray-700 dark:text-gray-300 leading-relaxed whitespace-pre-wrap">{{ user.bio }}</p>
                    </div>
                  </div>

                  <!-- Social Links -->
                  <div v-if="user.socialLinks && Object.keys(user.socialLinks).length > 0" class="bg-white dark:bg-gray-800 rounded-xl p-6 border border-gray-200 dark:border-gray-700">
                    <h4 class="flex items-center text-lg font-semibold text-gray-900 dark:text-white mb-4">
                      <LinkIcon class="w-5 h-5 mr-2 text-gray-600 dark:text-gray-400" />
                      Social Links
                    </h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                      <div 
                        v-for="(url, platform) in user.socialLinks" 
                        :key="platform" 
                        class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg"
                      >
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300 capitalize flex items-center">
                          <GlobeAltIcon class="w-4 h-4 mr-2 text-gray-500 dark:text-gray-400" />
                          {{ platform }}
                        </span>
                        <a 
                          :href="url" 
                          target="_blank" 
                          class="text-primary-600 dark:text-primary-400 hover:text-primary-700 dark:hover:text-primary-300 text-sm font-medium flex items-center"
                        >
                          View Profile
                          <ArrowTopRightOnSquareIcon class="w-3 h-3 ml-1" />
                        </a>
                      </div>
                    </div>
                  </div>

                  <!-- Account Timeline -->
                  <div class="bg-white dark:bg-gray-800 rounded-xl p-6 border border-gray-200 dark:border-gray-700">
                    <h4 class="flex items-center text-lg font-semibold text-gray-900 dark:text-white mb-4">
                      <ClockIcon class="w-5 h-5 mr-2 text-gray-600 dark:text-gray-400" />
                      Account Timeline
                    </h4>
                    <div class="space-y-4">
                      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <div class="p-4 bg-gray-50 dark:bg-gray-700 rounded-lg border border-gray-200 dark:border-gray-600">
                          <div class="flex items-center space-x-3">
                            <div class="p-2 bg-gray-100 dark:bg-gray-600 rounded-full">
                              <CalendarIcon class="w-4 h-4 text-gray-600 dark:text-gray-400" />
                            </div>
                            <div>
                              <p class="text-sm font-medium text-gray-900 dark:text-gray-100">Account Created</p>
                              <p class="text-sm text-gray-700 dark:text-gray-300">{{ formatDate(user.createdAt) }}</p>
                            </div>
                          </div>
                        </div>
                        
                        <div v-if="user.lastLoginAt" class="p-4 bg-gray-50 dark:bg-gray-700 rounded-lg border border-gray-200 dark:border-gray-600">
                          <div class="flex items-center space-x-3">
                            <div class="p-2 bg-gray-100 dark:bg-gray-600 rounded-full">
                              <ArrowRightOnRectangleIcon class="w-4 h-4 text-gray-600 dark:text-gray-400" />
                            </div>
                            <div>
                              <p class="text-sm font-medium text-gray-900 dark:text-gray-100">Last Login</p>
                              <p class="text-sm text-gray-700 dark:text-gray-300">{{ formatDate(user.lastLoginAt) }}</p>
                            </div>
                          </div>
                        </div>
                        
                        <div v-if="user.updatedAt" class="p-4 bg-gray-50 dark:bg-gray-700 rounded-lg border border-gray-200 dark:border-gray-600">
                          <div class="flex items-center space-x-3">
                            <div class="p-2 bg-gray-100 dark:bg-gray-600 rounded-full">
                              <PencilIcon class="w-4 h-4 text-gray-600 dark:text-gray-400" />
                            </div>
                            <div>
                              <p class="text-sm font-medium text-gray-900 dark:text-gray-100">Last Updated</p>
                              <p class="text-sm text-gray-700 dark:text-gray-300">{{ formatDate(user.updatedAt) }}</p>
                            </div>
                          </div>
                        </div>
                      </div>
                      
                      <!-- Security Info -->
                      <div v-if="user.failedLoginAttempts > 0" class="p-4 bg-warning-50 dark:bg-warning-900/20 rounded-lg border border-warning-200 dark:border-warning-800">
                        <div class="flex items-center space-x-3">
                          <div class="p-2 bg-warning-100 dark:bg-warning-900 rounded-full">
                            <ExclamationTriangleIcon class="w-4 h-4 text-warning-600 dark:text-warning-400" />
                          </div>
                          <div>
                            <p class="text-sm font-medium text-warning-900 dark:text-warning-100">Security Alert</p>
                            <p class="text-sm text-warning-700 dark:text-warning-300">{{ user.failedLoginAttempts }} failed login attempts</p>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>

                  <!-- User Settings -->
                  <div v-if="user.settings || user.timezone || user.language" class="bg-white dark:bg-gray-800 rounded-xl p-6 border border-gray-200 dark:border-gray-700">
                    <h4 class="flex items-center text-lg font-semibold text-gray-900 dark:text-white mb-4">
                      <CogIcon class="w-5 h-5 mr-2 text-gray-600 dark:text-gray-400" />
                      User Settings
                    </h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                      <div v-if="user.timezone" class="space-y-1">
                        <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Timezone</label>
                        <p class="text-gray-900 dark:text-white font-medium">{{ user.timezone }}</p>
                      </div>
                      <div v-if="user.language" class="space-y-1">
                        <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Language</label>
                        <p class="text-gray-900 dark:text-white font-medium">{{ user.language }}</p>
                      </div>
                    </div>
                    <div v-if="user.settings && Object.keys(user.settings).length > 0" class="mt-6">
                      <label class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-2 block">Custom Settings</label>
                      <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4 border border-gray-200 dark:border-gray-600">
                        <pre class="text-xs text-gray-700 dark:text-gray-300 overflow-x-auto whitespace-pre-wrap">{{ JSON.stringify(user.settings, null, 2) }}</pre>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Error State -->
                <div v-else class="p-8">
                  <div class="flex flex-col items-center justify-center py-12">
                    <div class="p-4 bg-danger-100 dark:bg-danger-900 rounded-full mb-4">
                      <ExclamationCircleIcon class="w-12 h-12 text-danger-600 dark:text-danger-400" />
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Failed to Load User Details</h3>
                    <p class="text-gray-600 dark:text-gray-400 mb-4">There was an error loading the user information.</p>
                    <button
                      @click="retryLoad"
                      class="px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 focus:ring-offset-white dark:focus:ring-offset-gray-900 transition-colors"
                    >
                      Try Again
                    </button>
                  </div>
                </div>
              </div>

              <!-- Modal Footer / Actions -->
              <div class="border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-6 py-4">
                <div class="flex items-center justify-between">
                  <div class="flex items-center space-x-2 text-sm text-gray-500 dark:text-gray-400">
                    <ClockIcon class="w-4 h-4" />
                    <span>Last updated {{ user ? formatRelativeTime(user.updatedAt || user.createdAt) : '' }}</span>
                  </div>
                  
                  <div class="flex items-center space-x-3">
                    <button
                      @click="$emit('close')"
                      class="px-4 py-2 text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 focus:ring-offset-white dark:focus:ring-offset-gray-900 transition-colors"
                    >
                      Close
                    </button>
                    
                    <button
                      v-if="user && !user.isActive"
                      @click="handleActivateUser"
                      :disabled="actionLoading"
                      class="px-4 py-2 bg-success-600 hover:bg-success-700 text-white rounded-lg focus:ring-2 focus:ring-success-500 focus:ring-offset-2 focus:ring-offset-white dark:focus:ring-offset-gray-900 transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center"
                    >
                      <UserPlusIcon v-if="!actionLoading" class="w-4 h-4 mr-2" />
                      <div v-else class="animate-spin rounded-full h-4 w-4 border-2 border-white border-t-transparent mr-2"></div>
                      {{ actionLoading ? 'Activating...' : 'Activate User' }}
                    </button>
                    
                    <button
                      v-if="user && user.isActive"
                      @click="handleDeactivateUser"
                      :disabled="actionLoading"
                      class="px-4 py-2 bg-danger-600 hover:bg-danger-700 text-white rounded-lg focus:ring-2 focus:ring-danger-500 focus:ring-offset-2 focus:ring-offset-white dark:focus:ring-offset-gray-900 transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center"
                    >
                      <UserMinusIcon v-if="!actionLoading" class="w-4 h-4 mr-2" />
                      <div v-else class="animate-spin rounded-full h-4 w-4 border-2 border-white border-t-transparent mr-2"></div>
                      {{ actionLoading ? 'Deactivating...' : 'Deactivate User' }}
                    </button>
                    
                    <button
                      v-if="user"
                      @click="handleEditRoles"
                      class="px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 focus:ring-offset-white dark:focus:ring-offset-gray-900 transition-colors flex items-center"
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

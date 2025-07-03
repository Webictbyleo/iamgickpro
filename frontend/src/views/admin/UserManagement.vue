<template>
  <AppLayout title="User Management" subtitle="Manage platform users, roles, and permissions">
    <div class="max-w-7xl mx-auto">
      <!-- Header Controls -->
      <div class="mb-8">
        <div class="flex items-center justify-end">
          <div class="flex items-center space-x-4">
            <!-- Platform Stats -->
            <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-200">
              <div class="flex items-center space-x-4">
                <div class="text-center">
                  <div class="text-2xl font-bold text-blue-600">{{ stats.users?.total || 0 }}</div>
                  <div class="text-xs text-gray-500">Total Users</div>
                </div>
                <div class="text-center">
                  <div class="text-2xl font-bold text-green-600">{{ stats.users?.active || 0 }}</div>
                  <div class="text-xs text-gray-500">Active</div>
                </div>
                <div class="text-center">
                  <div class="text-2xl font-bold text-purple-600">{{ stats.users?.admins || 0 }}</div>
                  <div class="text-xs text-gray-500">Admins</div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Filters and Search -->
      <div class="bg-white rounded-xl shadow-sm border border-gray-200 mb-6">
        <div class="p-6">
          <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <!-- Search -->
            <div class="md:col-span-2">
              <label class="block text-sm font-medium text-gray-700 mb-2">Search Users</label>
              <div class="relative">
                <MagnifyingGlassIcon class="absolute left-3 top-1/2 transform -translate-y-1/2 w-5 h-5 text-gray-400" />
                <input
                  v-model="searchQuery"
                  type="text"
                  placeholder="Search by email, name, or username..."
                  class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                  @input="debouncedSearch"
                />
              </div>
            </div>

            <!-- Status Filter -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
              <select
                v-model="filters.status"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                @change="loadUsers"
              >
                <option value="">All Status</option>
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
                <option value="verified">Verified</option>
                <option value="unverified">Unverified</option>
              </select>
            </div>

            <!-- Role Filter -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Role</label>
              <select
                v-model="filters.role"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                @change="loadUsers"
              >
                <option value="">All Roles</option>
                <option value="admin">Admin</option>
                <option value="user">User</option>
              </select>
            </div>
          </div>
        </div>
      </div>

      <!-- Users Table -->
      <div class="bg-white rounded-xl shadow-sm border border-gray-200">
        <!-- Bulk Actions Header -->
        <div v-if="selectedUserIds.length > 0" class="px-6 py-3 bg-blue-50 border-b border-blue-200">
          <div class="flex items-center justify-between">
            <div class="flex items-center space-x-3">
              <span class="text-sm font-medium text-blue-900">
                {{ selectedUserIds.length }} user{{ selectedUserIds.length === 1 ? '' : 's' }} selected
              </span>
              <button
                @click="selectedUserIds = []"
                class="text-sm text-blue-600 hover:text-blue-800 font-medium"
              >
                Clear selection
              </button>
            </div>
            <div class="flex items-center space-x-2">
              <button
                @click="bulkAssignPlan"
                class="px-3 py-1 text-sm font-medium text-white bg-orange-600 border border-transparent rounded-md hover:bg-orange-700 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:ring-offset-2 transition-colors flex items-center space-x-1"
              >
                <CreditCardIcon class="w-4 h-4" />
                <span>Assign Plan</span>
              </button>
              <button
                @click="bulkActivateUsers"
                class="px-3 py-1 text-sm font-medium text-white bg-green-600 border border-transparent rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition-colors flex items-center space-x-1"
              >
                <UserPlusIcon class="w-4 h-4" />
                <span>Activate</span>
              </button>
              <button
                @click="bulkDeactivateUsers"
                class="px-3 py-1 text-sm font-medium text-white bg-red-600 border border-transparent rounded-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition-colors flex items-center space-x-1"
              >
                <UserMinusIcon class="w-4 h-4" />
                <span>Deactivate</span>
              </button>
            </div>
          </div>
        </div>

        <div class="overflow-hidden">
          <!-- Loading State -->
          <div v-if="loading" class="p-8 text-center">
            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto"></div>
            <p class="mt-4 text-gray-600">Loading users...</p>
          </div>

          <!-- Users Table -->
          <div v-else-if="users.length > 0" class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
              <thead class="bg-gray-50">
                <tr>
                  <th class="relative px-6 py-3 text-left">
                    <input
                      type="checkbox"
                      :checked="selectedUserIds.length === users.length && users.length > 0"
                      :indeterminate="selectedUserIds.length > 0 && selectedUserIds.length < users.length"
                      @change="toggleSelectAll"
                      class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                    />
                  </th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Plan</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Joined</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Last Login</th>
                  <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
              </thead>
              <tbody class="bg-white divide-y divide-gray-200">
                <tr v-for="user in users" :key="user.id" class="hover:bg-gray-50">
                  <!-- Checkbox -->
                  <td class="relative px-6 py-4 whitespace-nowrap">
                    <input
                      v-model="selectedUserIds"
                      :value="user.id"
                      type="checkbox"
                      class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                    />
                  </td>
                  <!-- User Info -->
                  <td class="px-6 py-4 whitespace-nowrap">
                    <div class="flex items-center">
                      <div class="flex-shrink-0 h-10 w-10">
                        <div class="h-10 w-10 rounded-full bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center">
                          <span class="text-white font-semibold text-sm">{{ getUserInitials(user) }}</span>
                        </div>
                      </div>
                      <div class="ml-4">
                        <div class="text-sm font-medium text-gray-900">{{ user.firstName }} {{ user.lastName }}</div>
                        <div class="text-sm text-gray-500">{{ user.email }}</div>
                        <div v-if="user.username" class="text-xs text-gray-400">@{{ user.username }}</div>
                      </div>
                    </div>
                  </td>

                  <!-- Status -->
                  <td class="px-6 py-4 whitespace-nowrap">
                    <div class="flex flex-col space-y-1">
                      <!-- Active Status -->
                      <span
                        :class="[
                          'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
                          user.isActive
                            ? 'bg-green-100 text-green-800'
                            : 'bg-red-100 text-red-800'
                        ]"
                      >
                        <CheckCircleIcon 
                          v-if="user.isActive" 
                          class="w-3 h-3 mr-1" 
                        />
                        <XMarkIcon 
                          v-else
                          class="w-3 h-3 mr-1" 
                        />
                        {{ user.isActive ? 'Active' : 'Inactive' }}
                      </span>
                      <!-- Email Verified -->
                      <span
                        :class="[
                          'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
                          user.emailVerified
                            ? 'bg-blue-100 text-blue-800'
                            : 'bg-yellow-100 text-yellow-800'
                        ]"
                      >
                        <CheckCircleIcon 
                          v-if="user.emailVerified" 
                          class="w-3 h-3 mr-1" 
                        />
                        <ExclamationTriangleIcon 
                          v-else
                          class="w-3 h-3 mr-1" 
                        />
                        {{ user.emailVerified ? 'Verified' : 'Unverified' }}
                      </span>
                      <!-- Locked Status -->
                      <span
                        v-if="user.isLocked"
                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800"
                      >
                        <KeyIcon class="w-3 h-3 mr-1" />
                        Locked
                      </span>
                    </div>
                  </td>

                  <!-- Role -->
                  <td class="px-6 py-4 whitespace-nowrap">
                    <div class="flex flex-wrap gap-1">
                      <span
                        v-for="role in user.roles"
                        :key="role"
                        :class="[
                          'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
                          role === 'ROLE_ADMIN'
                            ? 'bg-purple-100 text-purple-800'
                            : 'bg-gray-100 text-gray-800'
                        ]"
                      >
                        {{ formatRole(role) }}
                      </span>
                    </div>
                  </td>

                  <!-- Plan -->
                  <td class="px-6 py-4 whitespace-nowrap">
                    <span class="text-sm text-gray-900 capitalize">{{ user.plan || 'Free' }}</span>
                  </td>

                  <!-- Joined Date -->
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                    {{ formatDate(user.createdAt) }}
                  </td>

                  <!-- Last Login -->
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                    {{ user.lastLoginAt ? formatDate(user.lastLoginAt) : 'Never' }}
                  </td>

                  <!-- Actions -->
                  <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                    <div class="flex items-center justify-end space-x-2">
                      <!-- View Details -->
                      <button
                        @click="viewUserDetails(user)"
                        class="text-blue-600 hover:text-blue-900 p-1 rounded transition-colors"
                        title="View Details"
                      >
                        <EyeIcon class="w-4 h-4" />
                      </button>

                      <!-- Toggle Status -->
                      <button
                        @click="toggleUserStatus(user)"
                        :class="[
                          'p-1 rounded transition-colors',
                          user.isActive
                            ? 'text-red-600 hover:text-red-900'
                            : 'text-green-600 hover:text-green-900'
                        ]"
                        :title="user.isActive ? 'Deactivate User' : 'Activate User'"
                      >
                        <UserMinusIcon v-if="user.isActive" class="w-4 h-4" />
                        <UserPlusIcon v-else class="w-4 h-4" />
                      </button>

                      <!-- Edit Roles -->
                      <button
                        @click="editUserRoles(user)"
                        class="text-purple-600 hover:text-purple-900 p-1 rounded transition-colors"
                        title="Edit Roles"
                      >
                        <KeyIcon class="w-4 h-4" />
                      </button>

                      <!-- Assign Plan -->
                      <button
                        @click="assignPlanToUser(user)"
                        class="text-orange-600 hover:text-orange-900 p-1 rounded transition-colors"
                        title="Assign Plan"
                      >
                        <CreditCardIcon class="w-4 h-4" />
                      </button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Empty State -->
          <div v-else class="p-8 text-center">
            <UsersIcon class="w-16 h-16 text-gray-400 mx-auto mb-4" />
            <h3 class="text-lg font-medium text-gray-900 mb-2">No users found</h3>
            <p class="text-gray-500">Try adjusting your search criteria or filters.</p>
          </div>
        </div>

        <!-- Pagination -->
        <div v-if="pagination.total_pages > 1" class="bg-gray-50 px-6 py-3 border-t border-gray-200">
          <div class="flex items-center justify-between">
            <div class="text-sm text-gray-700">
              Showing {{ ((pagination.current_page - 1) * pagination.items_per_page) + 1 }} to
              {{ Math.min(pagination.current_page * pagination.items_per_page, pagination.total_items) }} of
              {{ pagination.total_items }} results
            </div>
            <div class="flex items-center space-x-2">
              <button
                @click="changePage(pagination.current_page - 1)"
                :disabled="!pagination.has_prev"
                class="px-3 py-1 text-sm border border-gray-300 rounded-md disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-50"
              >
                Previous
              </button>
              <span class="text-sm text-gray-700">
                Page {{ pagination.current_page }} of {{ pagination.total_pages }}
              </span>
              <button
                @click="changePage(pagination.current_page + 1)"
                :disabled="!pagination.has_next"
                class="px-3 py-1 text-sm border border-gray-300 rounded-md disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-50"
              >
                Next
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- User Details Modal -->
    <UserDetailsModal
      :show="!!selectedUser"
      :userId="selectedUser?.id"
      @close="selectedUser = null"
      @updated="handleUserUpdated"
      @editRoles="handleEditRolesFromModal"
    />

    <!-- Edit Roles Modal -->
    <EditRolesModal
      v-if="editingUser"
      :user="editingUser"
      @close="editingUser = null"
      @updated="handleUserUpdated"
    />

    <!-- Plan Assignment Modal -->
    <PlanAssignmentModal
      v-if="showPlanAssignmentModal"
      :mode="planAssignmentMode"
      :user="userForPlanAssignment"
      :selectedUsers="selectedUsersForBulkPlan"
      @close="showPlanAssignmentModal = false"
      @assigned="handlePlanAssigned"
    />
  </AppLayout>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useNotifications } from '@/composables/useNotifications'
import { adminAPI } from '@/services/api'
import { debounce } from 'lodash-es'
import AppLayout from '@/components/layout/AppLayout.vue'
import UserDetailsModal from './components/UserDetailsModal.vue'
import EditRolesModal from './components/EditRolesModal.vue'
import PlanAssignmentModal from './components/PlanAssignmentModal.vue'
import type { AdminUser, AdminPlatformStats } from '@/types'

// Heroicons
import { 
  MagnifyingGlassIcon,
  CheckCircleIcon,
  XMarkIcon,
  ExclamationTriangleIcon,
  KeyIcon,
  EyeIcon,
  UserMinusIcon,
  UserPlusIcon,
  UsersIcon,
  CreditCardIcon
} from '@heroicons/vue/24/outline'

// Access control
const authStore = useAuthStore()
const router = useRouter()

// Check admin access on component load
onMounted(() => {
  // Wait for auth store to be ready
  setTimeout(() => {
    if (!authStore.isAdmin) {
      router.push({ name: 'Dashboard' })
      return
    }
    loadUsers()
    loadStats()
  }, 100)
})

interface Pagination {
  current_page: number
  total_pages: number
  total_items: number
  items_per_page: number
  has_next: boolean
  has_prev: boolean
}

const { showSuccess, showError } = useNotifications()

// State
const loading = ref(false)
const users = ref<AdminUser[]>([])
const stats = ref<Pick<AdminPlatformStats, 'users' | 'recent_registrations'>>({
  users: { total: 0, active: 0, verified: 0, admins: 0 },
  recent_registrations: 0
})
const pagination = ref<Pagination>({
  current_page: 1,
  total_pages: 1,
  total_items: 0,
  items_per_page: 20,
  has_next: false,
  has_prev: false
})

// Filters and search
const searchQuery = ref('')
const filters = ref({
  status: '',
  role: '',
  page: 1,
  limit: 20
})

// Modals
const selectedUser = ref<AdminUser | null>(null)
const editingUser = ref<AdminUser | null>(null)
const showPlanAssignmentModal = ref(false)
const planAssignmentMode = ref<'single' | 'bulk'>('single')
const userForPlanAssignment = ref<AdminUser | null>(null)
const selectedUsersForBulkPlan = ref<AdminUser[]>([])
const selectedUserIds = ref<number[]>([]) // For bulk selection

// Search debounce
const debouncedSearch = debounce(() => {
  filters.value.page = 1
  loadUsers()
}, 300)

// Load users
const loadUsers = async () => {
  loading.value = true
  try {
    const params = {
      page: filters.value.page,
      limit: filters.value.limit,
      ...(searchQuery.value && { search: searchQuery.value }),
      ...(filters.value.status && { status: filters.value.status }),
      ...(filters.value.role && { role: filters.value.role })
    }

    const response = await adminAPI.getUsers(params)
    users.value = response.data.data.users
    pagination.value = response.data.data.pagination
  } catch (error) {
    console.error('Failed to load users:', error)
    showError('Failed to load users')
  } finally {
    loading.value = false
  }
}

// Load platform stats
const loadStats = async () => {
  try {
    const response = await adminAPI.getPlatformStats()
    stats.value = response.data.data
  } catch (error) {
    console.error('Failed to load stats:', error)
  }
}

// User actions
const viewUserDetails = (user: AdminUser) => {
  selectedUser.value = user
}

const editUserRoles = (user: AdminUser) => {
  editingUser.value = user
}

const toggleUserStatus = async (user: AdminUser) => {
  try {
    const response = await adminAPI.updateUserStatus(user.id, { active: !user.isActive })
    const updatedUser = response.data.data.user
    
    // Update user in list
    const index = users.value.findIndex((u: AdminUser) => u.id === user.id)
    if (index !== -1) {
      users.value[index] = updatedUser
    }

    showSuccess(`User ${updatedUser.isActive ? 'activated' : 'deactivated'} successfully`)
  } catch (error) {
    console.error('Failed to update user status:', error)
    showError('Failed to update user status')
  }
}

const assignPlanToUser = (user: AdminUser) => {
  planAssignmentMode.value = 'single'
  userForPlanAssignment.value = user
  showPlanAssignmentModal.value = true
}

const bulkAssignPlan = () => {
  if (selectedUserIds.value.length === 0) {
    showError('Please select users first')
    return
  }
  
  const selectedUsers = users.value.filter(user => selectedUserIds.value.includes(user.id))
  planAssignmentMode.value = 'bulk'
  selectedUsersForBulkPlan.value = selectedUsers
  showPlanAssignmentModal.value = true
}

const bulkActivateUsers = async () => {
  if (selectedUserIds.value.length === 0) {
    showError('Please select users first')
    return
  }

  const selectedCount = selectedUserIds.value.length
  if (!confirm(`Are you sure you want to activate ${selectedCount} user${selectedCount === 1 ? '' : 's'}?`)) {
    return
  }

  try {
    loading.value = true
    const promises = selectedUserIds.value.map(userId => 
      adminAPI.updateUserStatus(userId, { active: true })
    )
    
    await Promise.all(promises)
    
    // Update users in the list
    selectedUserIds.value.forEach(userId => {
      const index = users.value.findIndex(u => u.id === userId)
      if (index !== -1) {
        users.value[index].isActive = true
      }
    })
    
    selectedUserIds.value = []
    showSuccess(`${selectedCount} users activated successfully`)
    
    // Refresh stats
    loadStats()
  } catch (error) {
    console.error('Failed to activate users:', error)
    showError('Failed to activate users')
  } finally {
    loading.value = false
  }
}

const bulkDeactivateUsers = async () => {
  if (selectedUserIds.value.length === 0) {
    showError('Please select users first')
    return
  }

  const selectedCount = selectedUserIds.value.length
  if (!confirm(`Are you sure you want to deactivate ${selectedCount} user${selectedCount === 1 ? '' : 's'}? Deactivated users will not be able to log in.`)) {
    return
  }

  try {
    loading.value = true
    const promises = selectedUserIds.value.map(userId => 
      adminAPI.updateUserStatus(userId, { active: false })
    )
    
    await Promise.all(promises)
    
    // Update users in the list
    selectedUserIds.value.forEach(userId => {
      const index = users.value.findIndex(u => u.id === userId)
      if (index !== -1) {
        users.value[index].isActive = false
      }
    })
    
    selectedUserIds.value = []
    showSuccess(`${selectedCount} users deactivated successfully`)
    
    // Refresh stats
    loadStats()
  } catch (error) {
    console.error('Failed to deactivate users:', error)
    showError('Failed to deactivate users')
  } finally {
    loading.value = false
  }
}

const handlePlanAssigned = (assignedUsers: AdminUser[], planCode: string) => {
  // Update users in the list (API call is handled by the modal)
  assignedUsers.forEach(assignedUser => {
    const index = users.value.findIndex(u => u.id === assignedUser.id)
    if (index !== -1) {
      users.value[index].plan = planCode
    }
  })
  
  // Clear selections and close modal
  selectedUserIds.value = []
  selectedUsersForBulkPlan.value = []
  userForPlanAssignment.value = null
  showPlanAssignmentModal.value = false
  
  // Refresh stats
  loadStats()
}

const handleUserUpdated = (updatedUser?: AdminUser) => {
  // If no updatedUser is provided, reload the users list
  if (!updatedUser) {
    loadUsers()
    loadStats() // Refresh stats too
    selectedUser.value = null
    editingUser.value = null
    return
  }
  
  // Update specific user in the list
  const index = users.value.findIndex((u: AdminUser) => u.id === updatedUser.id)
  if (index !== -1) {
    users.value[index] = updatedUser
  }
  selectedUser.value = null
  editingUser.value = null
}

// Handle edit roles action from UserDetailsModal
const handleEditRolesFromModal = (user: AdminUser) => {
  selectedUser.value = null // Close the details modal
  editingUser.value = user  // Open the edit roles modal
}

// Pagination
const changePage = (page: number) => {
  filters.value.page = page
  loadUsers()
}

// Bulk selection functions
const toggleSelectAll = (event: Event) => {
  const target = event.target as HTMLInputElement
  if (target.checked) {
    selectedUserIds.value = users.value.map(user => user.id)
  } else {
    selectedUserIds.value = []
  }
}

// Utility functions
const getUserInitials = (user: AdminUser): string => {
  return `${user.firstName?.[0] || ''}${user.lastName?.[0] || ''}`.toUpperCase() || 'U'
}

const formatRole = (role: string): string => {
  return role.replace('ROLE_', '').toLowerCase().replace('_', ' ')
}

const formatDate = (dateString: string): string => {
  return new Date(dateString).toLocaleDateString()
}
</script>

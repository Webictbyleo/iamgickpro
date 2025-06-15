<template>
  <div class="space-y-8">
    <!-- Profile Header -->
    <div class="flex items-start space-x-6">
      <div class="relative">
        <div v-if="userProfile.avatar" class="w-20 h-20 rounded-full overflow-hidden shadow-lg">
          <img :src="userProfile.avatar" :alt="`${userProfile.firstName} ${userProfile.lastName}`" class="w-full h-full object-cover" />
        </div>
        <div v-else class="w-20 h-20 bg-gradient-to-br from-indigo-500 to-violet-600 rounded-full flex items-center justify-center text-white text-2xl font-bold shadow-lg">
          {{ avatarInitials }}
        </div>
        <button
          @click="uploadAvatar"
          class="absolute -bottom-2 -right-2 w-8 h-8 bg-white border-2 border-gray-200 rounded-full flex items-center justify-center text-gray-600 hover:text-indigo-600 hover:border-indigo-300 transition-colors duration-200 shadow-sm"
          title="Upload photo"
        >
          <CameraIcon class="w-4 h-4" />
        </button>
        <!-- Hidden file input -->
        <input
          ref="fileInput"
          type="file"
          accept="image/*"
          @change="handleAvatarUpload"
          class="hidden"
        />
      </div>
      <div class="flex-1">
        <h2 class="text-2xl font-bold text-gray-900">{{ userProfile.firstName }} {{ userProfile.lastName }}</h2>
        <p class="text-gray-600 mt-1">{{ userProfile.email }}</p>
        <p class="text-sm text-gray-500 mt-2">
          Member since {{ formatDate(userProfile.createdAt) }}
        </p>
      </div>
    </div>

    <!-- Profile Form -->
    <form @submit.prevent="saveProfile" class="space-y-6">
      <!-- Basic Information -->
      <div class="bg-gray-50 rounded-xl p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Basic Information</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div>
            <label for="firstName" class="block text-sm font-medium text-gray-700 mb-2">
              First Name
            </label>
            <input
              id="firstName"
              v-model="userProfile.firstName"
              type="text"
              class="w-full px-4 py-3 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-300 transition-all duration-200"
              placeholder="Enter your first name"
            />
          </div>
          <div>
            <label for="lastName" class="block text-sm font-medium text-gray-700 mb-2">
              Last Name
            </label>
            <input
              id="lastName"
              v-model="userProfile.lastName"
              type="text"
              class="w-full px-4 py-3 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-300 transition-all duration-200"
              placeholder="Enter your last name"
            />
          </div>
          <div class="md:col-span-2">
            <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
              Email Address
            </label>
            <input
              id="email"
              v-model="userProfile.email"
              type="email"
              class="w-full px-4 py-3 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-300 transition-all duration-200"
              placeholder="Enter your email address"
            />
          </div>
        </div>
      </div>

      <!-- Professional Information -->
      <div class="bg-gray-50 rounded-xl p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Professional Information</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div>
            <label for="jobTitle" class="block text-sm font-medium text-gray-700 mb-2">
              Job Title
            </label>
            <input
              id="jobTitle"
              v-model="userProfile.jobTitle"
              type="text"
              class="w-full px-4 py-3 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-300 transition-all duration-200"
              placeholder="e.g. Graphic Designer"
            />
          </div>
          <div>
            <label for="company" class="block text-sm font-medium text-gray-700 mb-2">
              Company
            </label>
            <input
              id="company"
              v-model="userProfile.company"
              type="text"
              class="w-full px-4 py-3 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-300 transition-all duration-200"
              placeholder="Enter your company name"
            />
          </div>
          <div>
            <label for="website" class="block text-sm font-medium text-gray-700 mb-2">
              Website
            </label>
            <input
              id="website"
              v-model="userProfile.website"
              type="url"
              class="w-full px-4 py-3 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-300 transition-all duration-200"
              placeholder="https://your-website.com"
            />
          </div>
          <div>
            <label for="portfolio" class="block text-sm font-medium text-gray-700 mb-2">
              Portfolio
            </label>
            <input
              id="portfolio"
              v-model="userProfile.portfolio"
              type="url"
              class="w-full px-4 py-3 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-300 transition-all duration-200"
              placeholder="https://your-portfolio.com"
            />
          </div>
        </div>
      </div>

      <!-- Bio -->
      <div class="bg-gray-50 rounded-xl p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Bio</h3>
        <div>
          <label for="bio" class="block text-sm font-medium text-gray-700 mb-2">
            About You
          </label>
          <textarea
            id="bio"
            v-model="userProfile.bio"
            rows="4"
            class="w-full px-4 py-3 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-300 transition-all duration-200 resize-none"
            placeholder="Tell us about yourself, your experience, and what you do..."
          ></textarea>
          <p class="text-sm text-gray-500 mt-2">
            {{ userProfile.bio.length }}/500 characters
          </p>
        </div>
      </div>

      <!-- Social Links -->
      <div class="bg-gray-50 rounded-xl p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Social Links</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div>
            <label for="twitter" class="block text-sm font-medium text-gray-700 mb-2">
              Twitter
            </label>
            <div class="relative">
              <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <span class="text-gray-500">@</span>
              </div>
              <input
                id="twitter"
                v-model="userProfile.socialLinks.twitter"
                type="text"
                class="w-full pl-8 pr-4 py-3 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-300 transition-all duration-200"
                placeholder="username"
              />
            </div>
          </div>
          <div>
            <label for="linkedin" class="block text-sm font-medium text-gray-700 mb-2">
              LinkedIn
            </label>
            <input
              id="linkedin"
              v-model="userProfile.socialLinks.linkedin"
              type="url"
              class="w-full px-4 py-3 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-300 transition-all duration-200"
              placeholder="https://linkedin.com/in/username"
            />
          </div>
          <div>
            <label for="dribbble" class="block text-sm font-medium text-gray-700 mb-2">
              Dribbble
            </label>
            <input
              id="dribbble"
              v-model="userProfile.socialLinks.dribbble"
              type="url"
              class="w-full px-4 py-3 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-300 transition-all duration-200"
              placeholder="https://dribbble.com/username"
            />
          </div>
          <div>
            <label for="behance" class="block text-sm font-medium text-gray-700 mb-2">
              Behance
            </label>
            <input
              id="behance"
              v-model="userProfile.socialLinks.behance"
              type="url"
              class="w-full px-4 py-3 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-300 transition-all duration-200"
              placeholder="https://behance.net/username"
            />
          </div>
        </div>
      </div>

      <!-- Security & Password -->
      <div class="bg-gradient-to-br from-red-50 to-orange-50 rounded-xl p-6 border border-red-100">
        <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
          <LockClosedIcon class="w-5 h-5 mr-2 text-red-600" />
          Security & Password
        </h3>
        <div class="flex items-center justify-between">
          <div class="flex-1">
            <h4 class="text-sm font-medium text-gray-900">Password</h4>
            <p class="text-sm text-gray-500">Change your account password</p>
          </div>
          <button
            @click="showPasswordChange = true"
            type="button"
            class="px-4 py-2 text-sm font-medium text-red-600 border border-red-300 rounded-lg hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-red-500/20 transition-colors"
          >
            Change Password
          </button>
        </div>
      </div>

      <!-- Actions -->
      <div class="flex items-center justify-between pt-6 border-t border-gray-200">
        <div class="flex items-center space-x-4">
          <button
            type="button"
            @click="resetForm"
            class="px-6 py-3 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors duration-200"
          >
            Reset Changes
          </button>
        </div>
        <div class="flex items-center space-x-4">
          <button
            type="submit"
            :disabled="isSaving"
            class="px-6 py-3 text-sm font-medium text-white bg-gradient-to-r from-indigo-600 to-violet-600 rounded-lg hover:from-indigo-700 hover:to-violet-700 disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-200 shadow-lg"
          >
            <span v-if="isSaving" class="flex items-center">
              <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              Saving...
            </span>
            <span v-else>Save Changes</span>
          </button>
        </div>
      </div>
    </form>

    <!-- Change Password Modal -->
    <div v-if="showPasswordChange" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
      <div class="bg-white rounded-2xl max-w-md w-full p-6">
        <div class="flex items-center mb-4">
          <LockClosedIcon class="w-6 h-6 text-indigo-600 mr-3" />
          <h3 class="text-lg font-semibold text-gray-900">Change Password</h3>
        </div>
        <form @submit.prevent="changePassword" class="space-y-4">
          <div>
            <label for="currentPassword" class="block text-sm font-medium text-gray-700 mb-1">
              Current Password
            </label>
            <input
              id="currentPassword"
              v-model="passwordForm.currentPassword"
              type="password"
              required
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-300 transition-all duration-200"
            />
          </div>
          <div>
            <label for="newPassword" class="block text-sm font-medium text-gray-700 mb-1">
              New Password
            </label>
            <input
              id="newPassword"
              v-model="passwordForm.newPassword"
              type="password"
              required
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-300 transition-all duration-200"
            />
          </div>
          <div>
            <label for="confirmPassword" class="block text-sm font-medium text-gray-700 mb-1">
              Confirm New Password
            </label>
            <input
              id="confirmPassword"
              v-model="passwordForm.confirmPassword"
              type="password"
              required
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-300 transition-all duration-200"
            />
          </div>
          <div class="flex space-x-3 pt-4">
            <button
              type="button"
              @click="showPasswordChange = false"
              class="flex-1 px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-gray-500/20 transition-colors"
            >
              Cancel
            </button>
            <button
              type="submit"
              :disabled="passwordChanging"
              class="flex-1 px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
            >
              <span v-if="passwordChanging">Changing...</span>
              <span v-else>Change Password</span>
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch, onMounted } from 'vue'
import { CameraIcon, LockClosedIcon } from '@heroicons/vue/24/outline'
import { userAPI } from '@/services/api'
import { useNotifications } from '@/composables/useNotifications'
import { useAuthStore } from '@/stores/auth'
import type { User } from '@/types'

// Types
interface UserProfile {
  firstName: string
  lastName: string
  email: string
  jobTitle: string
  company: string
  website: string
  portfolio: string
  bio: string
  avatar?: string
  createdAt: string
  socialLinks: {
    twitter: string
    linkedin: string
    dribbble: string
    behance: string
  }
}

interface PasswordForm {
  currentPassword: string
  newPassword: string
  confirmPassword: string
}

// Composables
const { success, error } = useNotifications()
const authStore = useAuthStore()

// State
const isSaving = ref(false)
const showPasswordChange = ref(false)
const passwordChanging = ref(false)
const fileInput = ref<HTMLInputElement | null>(null)
const isLoadingProfile = ref(false)

// Initialize user profile from auth store
const initializeUserProfile = (user: User | null): UserProfile => {
  if (!user) {
    return {
      firstName: '',
      lastName: '',
      email: '',
      jobTitle: '',
      company: '',
      website: '',
      portfolio: '',
      bio: '',
      avatar: '',
      createdAt: new Date().toISOString(),
      socialLinks: {
        twitter: '',
        linkedin: '',
        dribbble: '',
        behance: ''
      }
    }
  }

  return {
    firstName: user.firstName || '',
    lastName: user.lastName || '',
    email: user.email || '',
    jobTitle: user.jobTitle || '',
    company: user.company || '',
    website: user.website || '',
    portfolio: user.portfolio || '',
    bio: user.bio || '',
    avatar: user.avatar || '',
    createdAt: user.createdAt || new Date().toISOString(),
    socialLinks: {
      twitter: user.socialLinks?.twitter || '',
      linkedin: user.socialLinks?.linkedin || '',
      dribbble: user.socialLinks?.dribbble || '',
      behance: user.socialLinks?.behance || ''
    }
  }
}

const userProfile = ref<UserProfile>(initializeUserProfile(authStore.user))

const passwordForm = ref<PasswordForm>({
  currentPassword: '',
  newPassword: '',
  confirmPassword: ''
})

// Watch for auth store user changes
watch(() => authStore.user, (newUser) => {
  if (newUser) {
    userProfile.value = initializeUserProfile(newUser)
  }
}, { immediate: true })

// Lifecycle hooks
onMounted(async () => {
  // If user is not loaded, try to fetch it
  if (!authStore.user && authStore.isAuthenticated) {
    isLoadingProfile.value = true
    try {
      await authStore.fetchUser()
    } catch (err) {
      console.error('Failed to fetch user profile:', err)
    } finally {
      isLoadingProfile.value = false
    }
  }
})

// Computed
const avatarInitials = computed(() => {
  const first = userProfile.value.firstName.charAt(0).toUpperCase()
  const last = userProfile.value.lastName.charAt(0).toUpperCase()
  return first + last
})

// Methods
const formatDate = (dateString: string): string => {
  const date = new Date(dateString)
  return date.toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  })
}

const uploadAvatar = () => {
  fileInput.value?.click()
}

const handleAvatarUpload = async (event: Event) => {
  const file = (event.target as HTMLInputElement).files?.[0]
  if (!file) return

  // Validate file type
  if (!file.type.startsWith('image/')) {
    error('Invalid File Type', 'Please select a valid image file (JPEG, PNG, GIF, WebP)')
    return
  }

  // Validate file size (max 5MB)
  if (file.size > 5 * 1024 * 1024) {
    error('File Too Large', 'File size must be less than 5MB')
    return
  }

  // Store previous avatar for rollback
  const previousAvatar = userProfile.value.avatar

  try {
    // Create preview URL
    const previewUrl = URL.createObjectURL(file)
    userProfile.value.avatar = previewUrl

    // Upload to server using centralized API
    const response = await userAPI.uploadAvatar(file)
    
    // Update avatar with the server response
    const newAvatarUrl = response.data.data.avatar
    userProfile.value.avatar = newAvatarUrl
    
    // Update the auth store with the new avatar
    await authStore.updateProfile({ avatar: newAvatarUrl })
    
    success('Avatar Updated', 'Your profile picture has been uploaded successfully')
    
    // Clean up the preview URL
    URL.revokeObjectURL(previewUrl)
    
  } catch (uploadError) {
    console.error('Error uploading avatar:', uploadError)
    error('Upload Failed', 'Failed to upload avatar. Please try again.')
    
    // Revert to previous avatar if upload failed
    userProfile.value.avatar = previousAvatar || ''
  }
}

const changePassword = async () => {
  if (passwordForm.value.newPassword !== passwordForm.value.confirmPassword) {
    error('Password Mismatch', 'New passwords do not match')
    return
  }

  if (passwordForm.value.newPassword.length < 8) {
    error('Invalid Password', 'New password must be at least 8 characters long')
    return
  }

  passwordChanging.value = true
  try {
    const response = await userAPI.changePassword({
      currentPassword: passwordForm.value.currentPassword,
      newPassword: passwordForm.value.newPassword
    })
    
    success('Password Changed', 'Your password has been updated successfully')
    showPasswordChange.value = false
    
    // Reset form
    passwordForm.value = {
      currentPassword: '',
      newPassword: '',
      confirmPassword: ''
    }
    
  } catch (changeError) {
    console.error('Error changing password:', changeError)
    error('Password Change Failed', 'Failed to change password. Please check your current password and try again.')
  } finally {
    passwordChanging.value = false
  }
}

const saveProfile = async () => {
  if (!authStore.user) {
    error('Authentication Error', 'You must be logged in to update your profile')
    return
  }

  isSaving.value = true
  try {
    // Create a clean profile object excluding readonly fields and mapping to User type
    const profileData: Partial<User> = {
      firstName: userProfile.value.firstName,
      lastName: userProfile.value.lastName,
      email: userProfile.value.email,
      jobTitle: userProfile.value.jobTitle,
      company: userProfile.value.company,
      website: userProfile.value.website,
      portfolio: userProfile.value.portfolio,
      bio: userProfile.value.bio,
      avatar: userProfile.value.avatar,
      socialLinks: {
        twitter: userProfile.value.socialLinks.twitter,
        linkedin: userProfile.value.socialLinks.linkedin,
        dribbble: userProfile.value.socialLinks.dribbble,
        behance: userProfile.value.socialLinks.behance
      }
    }

    const result = await authStore.updateProfile(profileData)
    
    if (result.success) {
      // Success notification is already handled by the auth store
      console.log('Profile saved successfully')
    } else {
      error('Save Failed', result.error || 'Failed to save profile. Please try again.')
    }
    
  } catch (saveError) {
    console.error('Error saving profile:', saveError)
    error('Save Failed', 'Failed to save profile. Please try again.')
  } finally {
    isSaving.value = false
  }
}

const resetForm = () => {
  // Reset to original values or reload from API
  console.log('Reset form')
}
</script>

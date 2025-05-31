<template>
  <div class="space-y-8">
    <!-- Email Notifications -->
    <div class="bg-white rounded-xl border border-gray-200 p-6">
      <div class="flex items-center justify-between mb-6">
        <div>
          <h3 class="text-lg font-semibold text-gray-900">Email Notifications</h3>
          <p class="text-sm text-gray-600 mt-1">Choose what email notifications you want to receive</p>
        </div>
        <div class="flex items-center space-x-2">
          <span class="text-sm text-gray-700">All Email</span>
          <button
            @click="toggleAllEmail"
            :class="[
              'relative inline-flex h-6 w-11 items-center rounded-full transition-colors duration-200',
              allEmailEnabled ? 'bg-indigo-600' : 'bg-gray-200'
            ]"
          >
            <span
              :class="[
                'inline-block h-4 w-4 transform rounded-full bg-white transition-transform duration-200',
                allEmailEnabled ? 'translate-x-6' : 'translate-x-1'
              ]"
            />
          </button>
        </div>
      </div>

      <div class="space-y-4">
        <div v-for="notification in emailNotifications" :key="notification.id" class="flex items-start justify-between py-3">
          <div class="flex-1">
            <div class="flex items-center space-x-3">
              <component :is="notification.icon" class="w-5 h-5 text-gray-400" />
              <div>
                <h4 class="text-sm font-medium text-gray-900">{{ notification.title }}</h4>
                <p class="text-sm text-gray-600">{{ notification.description }}</p>
              </div>
            </div>
          </div>
          <button
            @click="toggleEmailNotification(notification.id)"
            :class="[
              'relative inline-flex h-6 w-11 items-center rounded-full transition-colors duration-200 ml-4',
              notification.enabled ? 'bg-indigo-600' : 'bg-gray-200'
            ]"
          >
            <span
              :class="[
                'inline-block h-4 w-4 transform rounded-full bg-white transition-transform duration-200',
                notification.enabled ? 'translate-x-6' : 'translate-x-1'
              ]"
            />
          </button>
        </div>
      </div>
    </div>

    <!-- Push Notifications -->
    <div class="bg-white rounded-xl border border-gray-200 p-6">
      <div class="flex items-center justify-between mb-6">
        <div>
          <h3 class="text-lg font-semibold text-gray-900">Push Notifications</h3>
          <p class="text-sm text-gray-600 mt-1">Manage browser push notifications</p>
        </div>
        <div class="flex items-center space-x-2">
          <span class="text-sm text-gray-700">All Push</span>
          <button
            @click="toggleAllPush"
            :class="[
              'relative inline-flex h-6 w-11 items-center rounded-full transition-colors duration-200',
              allPushEnabled ? 'bg-indigo-600' : 'bg-gray-200'
            ]"
          >
            <span
              :class="[
                'inline-block h-4 w-4 transform rounded-full bg-white transition-transform duration-200',
                allPushEnabled ? 'translate-x-6' : 'translate-x-1'
              ]"
            />
          </button>
        </div>
      </div>

      <div v-if="!browserSupported" class="mb-4 p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
        <div class="flex items-start space-x-3">
          <ExclamationTriangleIcon class="w-5 h-5 text-yellow-500 mt-0.5" />
          <div>
            <h4 class="text-sm font-medium text-yellow-800">Browser Not Supported</h4>
            <p class="text-sm text-yellow-700 mt-1">Your browser doesn't support push notifications.</p>
          </div>
        </div>
      </div>

      <div v-else-if="!pushPermission" class="mb-4 p-4 bg-blue-50 border border-blue-200 rounded-lg">
        <div class="flex items-start space-x-3">
          <InformationCircleIcon class="w-5 h-5 text-blue-500 mt-0.5" />
          <div class="flex-1">
            <h4 class="text-sm font-medium text-blue-800">Enable Push Notifications</h4>
            <p class="text-sm text-blue-700 mt-1">Allow notifications to stay updated on your designs and exports.</p>
            <button
              @click="requestPushPermission"
              class="mt-2 px-3 py-1 text-sm font-medium text-blue-600 bg-blue-100 rounded hover:bg-blue-200 transition-colors duration-200"
            >
              Enable Notifications
            </button>
          </div>
        </div>
      </div>

      <div class="space-y-4">
        <div v-for="notification in pushNotifications" :key="notification.id" class="flex items-start justify-between py-3">
          <div class="flex-1">
            <div class="flex items-center space-x-3">
              <component :is="notification.icon" class="w-5 h-5 text-gray-400" />
              <div>
                <h4 class="text-sm font-medium text-gray-900">{{ notification.title }}</h4>
                <p class="text-sm text-gray-600">{{ notification.description }}</p>
              </div>
            </div>
          </div>
          <button
            @click="togglePushNotification(notification.id)"
            :disabled="!pushPermission"
            :class="[
              'relative inline-flex h-6 w-11 items-center rounded-full transition-colors duration-200 ml-4',
              notification.enabled && pushPermission ? 'bg-indigo-600' : 'bg-gray-200',
              !pushPermission ? 'opacity-50 cursor-not-allowed' : ''
            ]"
          >
            <span
              :class="[
                'inline-block h-4 w-4 transform rounded-full bg-white transition-transform duration-200',
                notification.enabled && pushPermission ? 'translate-x-6' : 'translate-x-1'
              ]"
            />
          </button>
        </div>
      </div>
    </div>

    <!-- In-App Notifications -->
    <div class="bg-white rounded-xl border border-gray-200 p-6">
      <div class="flex items-center justify-between mb-6">
        <div>
          <h3 class="text-lg font-semibold text-gray-900">In-App Notifications</h3>
          <p class="text-sm text-gray-600 mt-1">Control notifications shown within the application</p>
        </div>
      </div>

      <div class="space-y-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-3">Notification Position</label>
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-3">
            <div
              v-for="position in notificationPositions"
              :key="position.id"
              @click="inAppSettings.position = position.id"
              :class="[
                'relative p-3 border-2 rounded-lg cursor-pointer transition-all duration-200 text-center',
                inAppSettings.position === position.id
                  ? 'border-indigo-500 bg-indigo-50'
                  : 'border-gray-200 hover:border-gray-300'
              ]"
            >
              <div class="text-sm font-medium text-gray-900">{{ position.name }}</div>
              <div v-if="inAppSettings.position === position.id" class="absolute top-1 right-1">
                <CheckIcon class="w-4 h-4 text-indigo-600" />
              </div>
            </div>
          </div>
        </div>

        <div>
          <label for="autoHide" class="block text-sm font-medium text-gray-700 mb-2">
            Auto-hide Duration (seconds)
          </label>
          <select
            id="autoHide"
            v-model="inAppSettings.autoHideDuration"
            class="w-full md:w-1/3 px-4 py-3 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-300 transition-all duration-200"
          >
            <option value="3">3 seconds</option>
            <option value="5">5 seconds</option>
            <option value="10">10 seconds</option>
            <option value="0">Never (manual dismiss)</option>
          </select>
        </div>

        <div class="space-y-3">
          <label class="flex items-center space-x-3">
            <input
              v-model="inAppSettings.showIcons"
              type="checkbox"
              class="w-4 h-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500"
            />
            <span class="text-sm text-gray-700">Show notification icons</span>
          </label>
          <label class="flex items-center space-x-3">
            <input
              v-model="inAppSettings.enableSounds"
              type="checkbox"
              class="w-4 h-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500"
            />
            <span class="text-sm text-gray-700">Enable notification sounds</span>
          </label>
          <label class="flex items-center space-x-3">
            <input
              v-model="inAppSettings.groupSimilar"
              type="checkbox"
              class="w-4 h-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500"
            />
            <span class="text-sm text-gray-700">Group similar notifications</span>
          </label>
        </div>
      </div>
    </div>

    <!-- Digest Settings -->
    <div class="bg-white rounded-xl border border-gray-200 p-6">
      <div class="flex items-center justify-between mb-6">
        <div>
          <h3 class="text-lg font-semibold text-gray-900">Email Digest</h3>
          <p class="text-sm text-gray-600 mt-1">Receive a summary of your activity</p>
        </div>
        <button
          @click="digestSettings.enabled = !digestSettings.enabled"
          :class="[
            'relative inline-flex h-6 w-11 items-center rounded-full transition-colors duration-200',
            digestSettings.enabled ? 'bg-indigo-600' : 'bg-gray-200'
          ]"
        >
          <span
            :class="[
              'inline-block h-4 w-4 transform rounded-full bg-white transition-transform duration-200',
              digestSettings.enabled ? 'translate-x-6' : 'translate-x-1'
            ]"
          />
        </button>
      </div>

      <div v-if="digestSettings.enabled" class="space-y-4">
        <div>
          <label for="frequency" class="block text-sm font-medium text-gray-700 mb-2">
            Frequency
          </label>
          <select
            id="frequency"
            v-model="digestSettings.frequency"
            class="w-full md:w-1/2 px-4 py-3 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-300 transition-all duration-200"
          >
            <option value="daily">Daily</option>
            <option value="weekly">Weekly</option>
            <option value="monthly">Monthly</option>
          </select>
        </div>

        <div>
          <label for="time" class="block text-sm font-medium text-gray-700 mb-2">
            Preferred Time
          </label>
          <select
            id="time"
            v-model="digestSettings.time"
            class="w-full md:w-1/2 px-4 py-3 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-300 transition-all duration-200"
          >
            <option value="08:00">8:00 AM</option>
            <option value="12:00">12:00 PM</option>
            <option value="18:00">6:00 PM</option>
            <option value="20:00">8:00 PM</option>
          </select>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-3">Include in Digest</label>
          <div class="space-y-2">
            <label class="flex items-center space-x-3">
              <input
                v-model="digestSettings.includeDesigns"
                type="checkbox"
                class="w-4 h-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500"
              />
              <span class="text-sm text-gray-700">New designs created</span>
            </label>
            <label class="flex items-center space-x-3">
              <input
                v-model="digestSettings.includeExports"
                type="checkbox"
                class="w-4 h-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500"
              />
              <span class="text-sm text-gray-700">Export activity</span>
            </label>
            <label class="flex items-center space-x-3">
              <input
                v-model="digestSettings.includeCollaboration"
                type="checkbox"
                class="w-4 h-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500"
              />
              <span class="text-sm text-gray-700">Collaboration updates</span>
            </label>
            <label class="flex items-center space-x-3">
              <input
                v-model="digestSettings.includeAccount"
                type="checkbox"
                class="w-4 h-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500"
              />
              <span class="text-sm text-gray-700">Account changes</span>
            </label>
          </div>
        </div>
      </div>
    </div>

    <!-- Actions -->
    <div class="flex items-center justify-between pt-6 border-t border-gray-200">
      <div class="flex items-center space-x-4">
        <button
          @click="testNotification"
          class="px-6 py-3 text-sm font-medium text-indigo-600 bg-indigo-50 border border-indigo-200 rounded-lg hover:bg-indigo-100 transition-colors duration-200"
        >
          Send Test Notification
        </button>
      </div>
      <div class="flex items-center space-x-4">
        <button
          @click="saveNotificationSettings"
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
          <span v-else>Save Settings</span>
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, computed, onMounted } from 'vue'
import {
  CheckIcon,
  EnvelopeIcon,
  BellIcon,
  ExclamationTriangleIcon,
  InformationCircleIcon,
  CheckCircleIcon,
  ClockIcon,
  UserGroupIcon,
  CreditCardIcon,
  ShieldCheckIcon,
  DocumentIcon
} from '@heroicons/vue/24/outline'

// State
const isSaving = ref(false)
const browserSupported = ref(true)
const pushPermission = ref(false)

const emailNotifications = ref([
  {
    id: 'export_complete',
    title: 'Export Complete',
    description: 'When your design export is ready for download',
    icon: CheckCircleIcon,
    enabled: true
  },
  {
    id: 'export_failed',
    title: 'Export Failed',
    description: 'When an export job fails',
    icon: ExclamationTriangleIcon,
    enabled: true
  },
  {
    id: 'collaboration',
    title: 'Collaboration Updates',
    description: 'When someone shares a design with you or comments',
    icon: UserGroupIcon,
    enabled: true
  },
  {
    id: 'billing',
    title: 'Billing & Subscription',
    description: 'Payment confirmations and subscription updates',
    icon: CreditCardIcon,
    enabled: true
  },
  {
    id: 'security',
    title: 'Security Alerts',
    description: 'Login attempts and security-related notifications',
    icon: ShieldCheckIcon,
    enabled: true
  },
  {
    id: 'product_updates',
    title: 'Product Updates',
    description: 'New features and important announcements',
    icon: BellIcon,
    enabled: false
  },
  {
    id: 'tips',
    title: 'Tips & Tutorials',
    description: 'Design tips and tutorial recommendations',
    icon: DocumentIcon,
    enabled: false
  }
])

const pushNotifications = ref([
  {
    id: 'export_complete',
    title: 'Export Complete',
    description: 'When your design export is ready',
    icon: CheckCircleIcon,
    enabled: true
  },
  {
    id: 'collaboration',
    title: 'Collaboration',
    description: 'New comments and shared designs',
    icon: UserGroupIcon,
    enabled: true
  },
  {
    id: 'reminders',
    title: 'Reminders',
    description: 'Scheduled reminders and deadlines',
    icon: ClockIcon,
    enabled: false
  }
])

const notificationPositions = ref([
  { id: 'top-right', name: 'Top Right' },
  { id: 'top-left', name: 'Top Left' },
  { id: 'bottom-right', name: 'Bottom Right' },
  { id: 'bottom-left', name: 'Bottom Left' }
])

const inAppSettings = reactive({
  position: 'top-right',
  autoHideDuration: 5,
  showIcons: true,
  enableSounds: false,
  groupSimilar: true
})

const digestSettings = reactive({
  enabled: true,
  frequency: 'weekly',
  time: '08:00',
  includeDesigns: true,
  includeExports: true,
  includeCollaboration: true,
  includeAccount: false
})

// Computed
const allEmailEnabled = computed(() => emailNotifications.value.every(n => n.enabled))
const allPushEnabled = computed(() => pushNotifications.value.every(n => n.enabled))

// Methods
const toggleAllEmail = () => {
  const newState = !allEmailEnabled.value
  emailNotifications.value.forEach(notification => {
    notification.enabled = newState
  })
}

const toggleAllPush = () => {
  const newState = !allPushEnabled.value
  pushNotifications.value.forEach(notification => {
    notification.enabled = newState
  })
}

const toggleEmailNotification = (id: string) => {
  const notification = emailNotifications.value.find(n => n.id === id)
  if (notification) {
    notification.enabled = !notification.enabled
  }
}

const togglePushNotification = (id: string) => {
  if (!pushPermission.value) return
  
  const notification = pushNotifications.value.find(n => n.id === id)
  if (notification) {
    notification.enabled = !notification.enabled
  }
}

const requestPushPermission = async () => {
  if (!('Notification' in window)) {
    browserSupported.value = false
    return
  }

  try {
    const permission = await Notification.requestPermission()
    pushPermission.value = permission === 'granted'
  } catch (error) {
    console.error('Error requesting notification permission:', error)
  }
}

const testNotification = () => {
  // Test in-app notification
  console.log('Sending test notification...')
  
  // If push notifications are enabled, send a test push notification
  if (pushPermission.value) {
    new Notification('Test Notification', {
      body: 'This is a test notification from ImgGick Pro',
      icon: '/favicon.ico'
    })
  }
}

const saveNotificationSettings = async () => {
  isSaving.value = true
  try {
    // Simulate API call
    await new Promise(resolve => setTimeout(resolve, 1500))
    console.log('Notification settings saved')
    // Show success notification
  } catch (error) {
    console.error('Error saving notification settings:', error)
  } finally {
    isSaving.value = false
  }
}

// Check notification permission on mount
onMounted(() => {
  if ('Notification' in window) {
    pushPermission.value = Notification.permission === 'granted'
  } else {
    browserSupported.value = false
  }
})
</script>

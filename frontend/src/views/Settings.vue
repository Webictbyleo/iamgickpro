<template>
  <AppLayout>
    <div class="min-h-screen bg-gray-50">
      <!-- Header -->
      <div class="bg-white border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div class="py-8">
            <div class="flex items-center space-x-4">
              <div class="w-12 h-12 bg-gradient-to-br from-indigo-500 to-violet-600 rounded-xl flex items-center justify-center shadow-lg">
                <Cog6ToothIcon class="w-6 h-6 text-white" />
              </div>
              <div>
                <h1 class="text-3xl font-bold bg-gradient-to-r from-indigo-900 via-violet-800 to-indigo-900 bg-clip-text text-transparent">
                  Settings
                </h1>
                <p class="text-gray-600 mt-1">
                  Manage your account preferences and configuration
                </p>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Settings Content -->
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="bg-white rounded-2xl shadow-lg border border-gray-100 overflow-hidden">
          <!-- Tab Navigation -->
          <div class="border-b border-gray-200">
            <nav class="flex space-x-8 px-6" aria-label="Tabs">
              <RouterLink
                v-for="tab in tabs"
                :key="tab.id"
                :to="{ name: 'Settings', params: { tab: tab.id } }"
                :class="[
                  'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors duration-200',
                  activeTab === tab.id
                    ? 'border-indigo-500 text-indigo-600'
                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                ]"
              >
                <div class="flex items-center space-x-2">
                  <component :is="tab.icon" class="w-5 h-5" />
                  <span>{{ tab.name }}</span>
                </div>
              </RouterLink>
            </nav>
          </div>

          <!-- Tab Content -->
          <div class="p-6">
            <!-- Profile Tab -->
            <div v-if="activeTab === 'profile'" class="space-y-8">
              <ProfileSettings />
            </div>

            <!-- Subscription Tab -->
            <div v-if="activeTab === 'subscription'" class="space-y-8">
              <SubscriptionSettings />
            </div>

            <!-- Integrations Tab -->
            <div v-if="activeTab === 'integrations'" class="space-y-8">
              <IntegrationsSettings />
            </div>

            <!-- General Tab -->
            <div v-if="activeTab === 'general'" class="space-y-8">
              <GeneralSettings />
            </div>

            <!-- Notifications Tab -->
            <div v-if="activeTab === 'notifications'" class="space-y-8">
              <NotificationSettings />
            </div>

            <!-- Privacy Tab -->
            <div v-if="activeTab === 'privacy'" class="space-y-8">
              <PrivacySettings />
            </div>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import {
  Cog6ToothIcon,
  UserIcon,
  CreditCardIcon,
  PuzzlePieceIcon,
  BellIcon,
  ShieldCheckIcon,
  AdjustmentsHorizontalIcon
} from '@heroicons/vue/24/outline'
import AppLayout from '@/components/layout/AppLayout.vue'
import ProfileSettings from '@/components/settings/ProfileSettings.vue'
import SubscriptionSettings from '@/components/settings/SubscriptionSettings.vue'
import GeneralSettings from '@/components/settings/GeneralSettings.vue'
import NotificationSettings from '@/components/settings/NotificationSettings.vue'
import PrivacySettings from '@/components/settings/PrivacySettings.vue'
import IntegrationsSettings from '@/components/settings/IntegrationsSettings.vue'

const route = useRoute()
const router = useRouter()

// Tab configuration
const tabs = ref([
  {
    id: 'profile',
    name: 'Profile',
    icon: UserIcon,
    description: 'Personal information and account details'
  },
  {
    id: 'subscription',
    name: 'Subscription',
    icon: CreditCardIcon,
    description: 'Current plan and subscription details'
  },
  {
    id: 'integrations',
    name: 'Integrations',
    icon: PuzzlePieceIcon,
    description: 'Third-party service API keys and settings'
  },
  {
    id: 'general',
    name: 'General',
    icon: AdjustmentsHorizontalIcon,
    description: 'Application preferences and settings'
  },
  {
    id: 'notifications',
    name: 'Notifications',
    icon: BellIcon,
    description: 'Email and push notification preferences'
  },
  {
    id: 'privacy',
    name: 'Privacy',
    icon: ShieldCheckIcon,
    description: 'Privacy and security settings'
  }
])

// Active tab computed from route
const activeTab = computed(() => {
  const tabParam = route.params.tab as string
  const validTab = tabs.value.find(tab => tab.id === tabParam)
  return validTab ? tabParam : 'profile'
})

// Watch for route changes and update URL if invalid tab
watch(() => route.params.tab, (newTab) => {
  if (!newTab || !tabs.value.find(tab => tab.id === newTab)) {
    router.replace({ name: 'Settings', params: { tab: 'profile' } })
  }
}, { immediate: true })

onMounted(() => {
  // Ensure we have a valid tab in the URL
  if (!route.params.tab) {
    router.replace({ name: 'Settings', params: { tab: 'profile' } })
  }
})
</script>

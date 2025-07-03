<template>
  <AppLayout
    title="Settings"
    subtitle="Manage your account preferences and configuration"
  >
    <div class="min-h-screen bg-gray-50 dark:bg-gray-900">
      <!-- Settings Content -->
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-100 dark:border-gray-700 overflow-hidden">
          <!-- Tab Navigation -->
          <div class="border-b border-gray-200 dark:border-gray-700">
            <nav class="flex space-x-8 px-6" aria-label="Tabs">
              <RouterLink
                v-for="tab in tabs"
                :key="tab.id"
                :to="{ name: 'Settings', params: { tab: tab.id } }"
                :class="[
                  'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors duration-200',
                  activeTab === tab.id
                    ? 'border-primary-500 text-primary-600 dark:text-primary-400'
                    : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300 dark:hover:border-gray-600'
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

            <!-- Privacy Tab -->
            <div v-if="activeTab === 'privacy'" class="space-y-8">
              <PrivacySettings />
            </div>

            <!-- Theme Tab -->
            <div v-if="activeTab === 'theme'" class="space-y-8">
              <ThemeSettings />
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
  ShieldCheckIcon,
  PaintBrushIcon
} from '@heroicons/vue/24/outline'
import AppLayout from '@/components/layout/AppLayout.vue'
import ProfileSettings from '@/components/settings/ProfileSettings.vue'
import SubscriptionSettings from '@/components/settings/SubscriptionSettings.vue'
import PrivacySettings from '@/components/settings/PrivacySettings.vue'
import IntegrationsSettings from '@/components/settings/IntegrationsSettings.vue'
import ThemeSettings from '@/components/settings/ThemeSettings.vue'

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
    id: 'privacy',
    name: 'Privacy',
    icon: ShieldCheckIcon,
    description: 'Privacy and security settings'
  },
  {
    id: 'theme',
    name: 'Theme',
    icon: PaintBrushIcon,
    description: 'Appearance and color customization'
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

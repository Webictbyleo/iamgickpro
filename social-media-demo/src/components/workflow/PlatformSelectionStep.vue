<template>
  <div class="space-y-6">
    <!-- Selected Topic Summary -->
    <div v-if="workflowStore.selectedTopic" class="bg-blue-50 rounded-lg p-4 border border-blue-200">
      <div class="flex items-center space-x-2 mb-2">
        <TagIcon class="w-5 h-5 text-blue-600" />
        <h4 class="font-semibold text-gray-900">Selected Topic:</h4>
      </div>
      <p class="text-gray-700">{{ workflowStore.selectedTopic.title }}</p>
    </div>

    <!-- Selected Accounts Display -->
    <div v-if="workflowStore.selectedAccounts.length > 0" class="bg-green-50 border border-green-200 rounded-lg p-4">
      <div class="flex items-center space-x-4 mb-3">
        <div class="w-10 h-10 bg-green-500 rounded-lg flex items-center justify-center">
          <CheckIcon class="w-5 h-5 text-white" />
        </div>
        <div class="flex-1">
          <h4 class="font-semibold text-green-900">
            {{ workflowStore.selectedAccounts.length }} Account{{ workflowStore.selectedAccounts.length === 1 ? '' : 's' }} Selected!
          </h4>
          <p class="text-green-800">Ready to create content for multiple platforms</p>
        </div>
      </div>
      
      <!-- Selected Accounts List -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
        <div 
          v-for="account in workflowStore.selectedAccounts" 
          :key="account.id"
          class="flex items-center space-x-3 bg-white rounded-lg p-3 border border-green-200"
        >
          <img
            :src="account.avatar"
            :alt="account.display_name"
            class="w-8 h-8 rounded-lg object-cover"
          />
          <div class="flex-1 min-w-0">
            <p class="text-sm font-medium text-gray-900 truncate">{{ account.display_name }}</p>
            <p class="text-xs text-gray-600">{{ getPlatformName(account.platform) }}</p>
          </div>
          <button
            @click="workflowStore.removeSelectedAccount(account)"
            class="w-6 h-6 bg-red-100 hover:bg-red-200 rounded-full flex items-center justify-center transition-colors"
          >
            <span class="text-red-600 text-sm">×</span>
          </button>
        </div>
      </div>
      
      <!-- Continue Button -->
      <div class="mt-4 flex justify-end">
        <button
          @click="proceedToNextStep"
          class="px-6 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg font-medium transition-colors flex items-center space-x-2"
        >
          <span>Continue with Selected Accounts</span>
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
          </svg>
        </button>
      </div>
    </div>

    <!-- Platform Filter -->
    <div class="bg-white rounded-lg border border-gray-200 p-6">
      <div class="flex items-center space-x-2 mb-4">
        <LinkIcon class="w-5 h-5 text-gray-600" />
        <h3 class="text-xl font-bold text-gray-900">Filter by Platform</h3>
      </div>
      <div class="flex flex-wrap gap-3">
        <button
          @click="selectedPlatform = null"
          class="px-6 py-3 rounded-2xl text-sm font-semibold transition-all duration-300 transform hover:scale-105 hover:rotate-1 shadow-md hover:shadow-lg"
          :class="{
            'bg-gradient-to-r from-primary-500 via-primary-600 to-primary-700 text-white shadow-xl ring-2 ring-primary-300/50': selectedPlatform === null,
            'bg-gray-100 text-gray-700 hover:bg-gray-200 hover:shadow-lg border border-gray-200': selectedPlatform !== null
          }"
        >
          <span class="flex items-center space-x-2">
            <span class="text-lg">🌟</span>
            <span>All Platforms</span>
          </span>
        </button>
        <button
          v-for="platform in platforms"
          :key="platform"
          @click="selectedPlatform = platform"
          class="px-6 py-3 rounded-2xl text-sm font-semibold transition-all duration-300 transform hover:scale-105 hover:rotate-1 shadow-md hover:shadow-lg"
          :class="{
            'bg-gradient-to-r from-primary-500 via-primary-600 to-primary-700 text-white shadow-xl ring-2 ring-primary-300/50': selectedPlatform === platform,
            'bg-gray-100 text-gray-700 hover:bg-gray-200 hover:shadow-lg border border-gray-200': selectedPlatform !== platform
          }"
        >
          <span class="flex items-center space-x-2">
            <span class="text-lg">{{ getPlatformIcon(platform) }}</span>
            <span>{{ getPlatformName(platform) }}</span>
          </span>
        </button>
      </div>
    </div>

    <!-- Enhanced Loading State -->
    <div v-if="loading.accounts" class="flex flex-col items-center justify-center py-20 bg-white/50 backdrop-blur-sm rounded-2xl border border-gray-200">
      <div class="relative mb-8">
        <LoadingSpinner size="lg" />
        <div class="absolute -inset-6 bg-primary-100 rounded-full animate-ping opacity-20"></div>
        <div class="absolute -inset-3 bg-primary-200 rounded-full animate-pulse opacity-30"></div>
      </div>
      <h3 class="text-xl font-bold text-gray-900 mb-2">🔗 Loading Connected Accounts...</h3>
      <p class="text-gray-600 font-medium mb-6 text-center max-w-md">
        Fetching your connected social media accounts
      </p>
      <div class="flex space-x-2">
        <div class="w-3 h-3 bg-primary-500 rounded-full animate-bounce"></div>
        <div class="w-3 h-3 bg-primary-500 rounded-full animate-bounce" style="animation-delay: 0.2s"></div>
        <div class="w-3 h-3 bg-primary-500 rounded-full animate-bounce" style="animation-delay: 0.4s"></div>
      </div>
    </div>

    <!-- Accounts Grid -->
    <div v-else-if="filteredAccounts.length > 0" class="space-y-4">
      <div class="flex items-center justify-between bg-white rounded-lg p-4 border border-gray-200">
        <div class="flex items-center space-x-3">
          <UserIcon class="w-5 h-5 text-gray-600" />
          <div>
            <h3 class="font-semibold text-gray-900">Connected Accounts</h3>
            <p class="text-sm text-gray-600">{{ filteredAccounts.length }} accounts available</p>
          </div>
        </div>
      </div>
      
      <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
        <SocialAccountCard
          v-for="account in filteredAccounts"
          :key="account.id"
          :account="account"
          :selected="workflowStore.selectedAccounts.some(a => a.id === account.id)"
          :multi-select="true"
          @select="handleAccountToggle"
          @connect="handleAccountConnect"
        />
      </div>
    </div>

    <!-- Empty State -->
    <div v-else class="text-center py-12 bg-white rounded-lg border border-gray-200">
      <div class="text-4xl mb-4">📱</div>
      <h3 class="text-lg font-semibold text-gray-900 mb-2">No accounts found</h3>
      <p class="text-gray-600 mb-6 max-w-md mx-auto">
        {{ selectedPlatform ? `No ${getPlatformName(selectedPlatform)} accounts connected` : 'No social media accounts connected' }}
      </p>
      <div class="flex justify-center space-x-3">
        <button class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition-colors">
          Connect New Account
        </button>
        <button
          @click="selectedPlatform = null"
          class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg font-medium transition-colors"
        >
          Reset Filter
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { useWorkflowStore } from '@/stores/workflow';
import { useUiStore } from '@/stores/ui';
import { socialAccountsApi } from '@/services/api';
import type { SocialAccount, SocialPlatform } from '@/types';
import { CheckIcon, TagIcon, LinkIcon, UserIcon } from '@heroicons/vue/24/solid';

import SocialAccountCard from '@/components/social/SocialAccountCard.vue';
import LoadingSpinner from '@/components/ui/LoadingSpinner.vue';

const workflowStore = useWorkflowStore();
const uiStore = useUiStore();

const accounts = ref<SocialAccount[]>([]);
const selectedPlatform = ref<SocialPlatform | null>(null);
const platforms: SocialPlatform[] = ['twitter', 'linkedin', 'instagram', 'facebook', 'tiktok', 'youtube'];

const loading = ref({
  accounts: false
});

const filteredAccounts = computed(() => {
  if (selectedPlatform.value) {
    return accounts.value.filter(account => account.platform === selectedPlatform.value);
  }
  return accounts.value;
});

const loadAccounts = async () => {
  loading.value.accounts = true;
  try {
    const response = await socialAccountsApi.getAccounts();
    if (response.success) {
      accounts.value = response.data;
    }
  } catch (error) {
    console.error('Failed to load accounts:', error);
    uiStore.addNotification({
      type: 'error',
      title: 'Error',
      message: 'Failed to load social media accounts'
    });
  } finally {
    loading.value.accounts = false;
  }
};

const handleAccountToggle = (account: SocialAccount) => {
  if (!account.is_connected) {
    uiStore.addNotification({
      type: 'warning',
      title: 'Account Not Connected',
      message: 'Please connect this account first'
    });
    return;
  }

  workflowStore.toggleSelectedAccount(account);
  
  const isSelected = workflowStore.selectedAccounts.some(a => a.id === account.id);
  uiStore.addNotification({
    type: 'success',
    title: isSelected ? 'Account Selected' : 'Account Deselected',
    message: `${account.display_name} ${isSelected ? 'added to' : 'removed from'} selection`
  });
};

const proceedToNextStep = () => {
  if (workflowStore.selectedAccounts.length > 0) {
    workflowStore.goToNextStep();
  }
};

const handleAccountConnect = async (account: SocialAccount) => {
  uiStore.setLoading('connect', true);
  try {
    const response = await socialAccountsApi.connectAccount(account.platform);
    if (response.success) {
      // Update account status
      const accountIndex = accounts.value.findIndex(a => a.id === account.id);
      if (accountIndex >= 0) {
        accounts.value[accountIndex].is_connected = true;
      }
      
      uiStore.addNotification({
        type: 'success',
        title: 'Account Connected',
        message: `Successfully connected ${account.display_name}`
      });
    }
  } catch (error) {
    console.error('Failed to connect account:', error);
    uiStore.addNotification({
      type: 'error',
      title: 'Connection Failed',
      message: 'Failed to connect social media account'
    });
  } finally {
    uiStore.setLoading('connect', false);
  }
};

const getPlatformIcon = (platform: SocialPlatform) => {
  const icons: Record<SocialPlatform, string> = {
    twitter: '🐦',
    linkedin: '💼',
    instagram: '📷',
    facebook: '👥',
    tiktok: '🎵',
    youtube: '📺'
  };
  return icons[platform];
};

const getPlatformName = (platform: SocialPlatform) => {
  const names: Record<SocialPlatform, string> = {
    twitter: 'Twitter',
    linkedin: 'LinkedIn',
    instagram: 'Instagram',
    facebook: 'Facebook',
    tiktok: 'TikTok',
    youtube: 'YouTube'
  };
  return names[platform];
};

onMounted(() => {
  loadAccounts();
});
</script>

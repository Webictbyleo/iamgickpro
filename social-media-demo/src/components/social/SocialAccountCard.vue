<template>
  <div 
    class="group relative border rounded-lg p-4 cursor-pointer transition-all duration-200"
    :class="{
      'border-blue-300 bg-blue-50 ring-1 ring-blue-100': selected,
      'border-gray-200 bg-white hover:border-gray-300 hover:shadow-sm': !selected && account.is_connected,
      'border-gray-300 bg-gray-50 opacity-75': !account.is_connected
    }"
    @click="handleSelect"
  >
    <!-- Selection Checkbox (for multi-select mode) -->
    <div 
      v-if="multiSelect"
      class="absolute top-3 right-3 w-5 h-5 rounded border-2 transition-all duration-200"
      :class="{
        'bg-blue-600 border-blue-600': selected,
        'border-gray-300 bg-white': !selected
      }"
    >
      <CheckIcon v-if="selected" class="w-3 h-3 text-white m-0.5" />
    </div>

    <!-- Selection Indicator (for single-select mode) -->
    <div 
      v-else-if="selected"
      class="absolute top-2 right-2 w-6 h-6 bg-blue-600 rounded-full flex items-center justify-center"
    >
      <CheckIcon class="w-4 h-4 text-white" />
    </div>
    <!-- Header -->
    <div class="flex items-center justify-between mb-4">
      <div class="flex items-center space-x-3">
        <div class="relative">
          <img
            :src="account.avatar"
            :alt="account.display_name"
            class="w-12 h-12 rounded-lg object-cover"
          />
          <div 
            class="absolute -bottom-1 -right-1 w-6 h-6 rounded-lg flex items-center justify-center text-xs"
            :class="{
              'bg-green-500 text-white': account.is_connected,
              'bg-gray-400 text-white': !account.is_connected
            }"
          >
            {{ getPlatformIcon(account.platform) }}
          </div>
        </div>
        
        <div>
          <h3 class="font-semibold text-gray-900 mb-1">{{ account.display_name }}</h3>
          <p class="text-sm text-gray-600">{{ account.username }}</p>
        </div>
      </div>

      <!-- Connection Status -->
      <div class="flex flex-col items-end space-y-1">
        <span
          class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium"
          :class="{
            'bg-green-100 text-green-800': account.is_connected,
            'bg-gray-100 text-gray-800': !account.is_connected
          }"
        >
          {{ account.is_connected ? 'Connected' : 'Not Connected' }}
        </span>
      </div>
    </div>

    <!-- Account Info -->
    <div class="grid grid-cols-2 gap-4 mb-3">
      <div>
        <div class="text-xs text-gray-500">Followers</div>
        <div class="text-sm font-semibold text-gray-900">
          {{ formatNumber(account.followers) }}
        </div>
      </div>
      <div>
        <div class="text-xs text-gray-500">Type</div>
        <div class="text-sm font-semibold text-gray-900 capitalize">
          {{ account.account_type }}
        </div>
      </div>
    </div>

    <!-- Footer -->
    <div class="flex items-center justify-between">
      <div class="flex items-center space-x-2 text-sm text-gray-600">
        <span>{{ getPlatformName(account.platform) }}</span>
      </div>
      
      <!-- Action Button -->
      <button
        v-if="!account.is_connected"
        @click.stop="$emit('connect', account)"
        class="px-3 py-1 bg-blue-600 hover:bg-blue-700 text-white text-sm rounded-md transition-colors"
      >
        Connect
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import type { SocialAccount, SocialPlatform } from '@/types';
import { CheckIcon } from '@heroicons/vue/24/solid';

interface Props {
  account: SocialAccount;
  selected?: boolean;
  multiSelect?: boolean;
}

interface Emits {
  (e: 'select', account: SocialAccount): void;
  (e: 'connect', account: SocialAccount): void;
}

const props = withDefaults(defineProps<Props>(), {
  selected: false,
  multiSelect: false
});

const emit = defineEmits<Emits>();

const handleSelect = () => {
  if (props.account.is_connected) {
    emit('select', props.account);
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

const formatNumber = (num: number) => {
  if (num >= 1000000) {
    return `${(num / 1000000).toFixed(1)}M`;
  }
  if (num >= 1000) {
    return `${(num / 1000).toFixed(1)}K`;
  }
  return num.toString();
};
</script>

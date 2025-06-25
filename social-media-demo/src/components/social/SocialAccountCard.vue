<template>
  <div 
    class="group relative border rounded-lg cursor-pointer transition-all duration-200"
    :class="{
      'border-blue-300 bg-blue-50 ring-1 ring-blue-100': selected,
      'border-gray-200 bg-white hover:border-gray-300 hover:shadow-sm': !selected && account.is_connected,
      'border-gray-300 bg-gray-50 opacity-75': !account.is_connected,
      'p-3': compact,
      'p-4': !compact
    }"
    @click="handleSelect"
  >
    <!-- Selection Checkbox (for multi-select mode) -->
    <div 
      v-if="multiSelect"
      class="absolute top-2 right-2"
      :class="compact ? 'w-4 h-4' : 'w-5 h-5'"
    >
      <div 
        class="w-full h-full rounded border-2 transition-all duration-200"
        :class="{
          'bg-blue-600 border-blue-600': selected,
          'border-gray-300 bg-white': !selected
        }"
      >
        <CheckIcon v-if="selected" :class="compact ? 'w-2.5 h-2.5 text-white m-0.5' : 'w-3 h-3 text-white m-0.5'" />
      </div>
    </div>

    <!-- Selection Indicator (for single-select mode) -->
    <div 
      v-else-if="selected"
      class="absolute top-2 right-2 bg-blue-600 rounded-full flex items-center justify-center"
      :class="compact ? 'w-5 h-5' : 'w-6 h-6'"
    >
      <CheckIcon :class="compact ? 'w-3 h-3 text-white' : 'w-4 h-4 text-white'" />
    </div>

    <!-- Header -->
    <div class="flex items-center justify-between" :class="compact ? 'mb-2' : 'mb-4'">
      <div class="flex items-center" :class="compact ? 'space-x-2' : 'space-x-3'">
        <div class="relative">
          <img
            :src="account.avatar"
            :alt="account.display_name"
            :class="compact ? 'w-8 h-8 rounded object-cover' : 'w-12 h-12 rounded-lg object-cover'"
          />
          <div 
            class="absolute -bottom-0.5 -right-0.5 rounded flex items-center justify-center text-xs"
            :class="{
              'bg-green-500 text-white': account.is_connected,
              'bg-gray-400 text-white': !account.is_connected,
              'w-4 h-4': compact,
              'w-6 h-6 rounded-lg': !compact
            }"
          >
            {{ getPlatformIcon(account.platform) }}
          </div>
        </div>
        
        <div class="flex-1 min-w-0">
          <h3 :class="compact ? 'font-medium text-gray-900 text-sm truncate' : 'font-semibold text-gray-900 mb-1'">
            {{ account.display_name }}
          </h3>
          <p :class="compact ? 'text-xs text-gray-600 truncate' : 'text-sm text-gray-600'">
            {{ account.username }}
          </p>
        </div>
      </div>

      <!-- Connection Status -->
      <div v-if="!compact" class="flex flex-col items-end space-y-1">
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

    <!-- Account Info (only show in non-compact mode) -->
    <div v-if="!compact" class="grid grid-cols-2 gap-4 mb-3">
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
      <div class="flex items-center space-x-2 text-gray-600" :class="compact ? 'text-xs' : 'text-sm'">
        <span>{{ getPlatformName(account.platform) }}</span>
        <!-- Show connection status in compact mode -->
        <span v-if="compact" class="text-xs" :class="account.is_connected ? 'text-green-600' : 'text-gray-400'">
          {{ account.is_connected ? '●' : '○' }}
        </span>
      </div>
      
      <!-- Action Button -->
      <button
        v-if="!account.is_connected"
        @click.stop="$emit('connect', account)"
        :class="compact 
          ? 'px-2 py-1 bg-blue-600 hover:bg-blue-700 text-white text-xs rounded transition-colors'
          : 'px-3 py-1 bg-blue-600 hover:bg-blue-700 text-white text-sm rounded-md transition-colors'
        "
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
  compact?: boolean;
}

interface Emits {
  (e: 'select', account: SocialAccount): void;
  (e: 'connect', account: SocialAccount): void;
}

const props = withDefaults(defineProps<Props>(), {
  selected: false,
  multiSelect: false,
  compact: false
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

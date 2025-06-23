<template>
  <div class="space-y-6">
    <!-- Content Summary -->
    <div class="bg-gray-50 rounded-lg p-4 space-y-2">
      <div>
        <span class="font-medium text-gray-900">Content:</span>
        <span class="text-gray-700 ml-2 line-clamp-1">{{ workflowStore.generatedContent?.content.text }}</span>
      </div>
      <div>
        <span class="font-medium text-gray-900">Platforms:</span>
        <span class="text-gray-700 ml-2">{{ workflowStore.selectedAccounts.map(a => a.display_name).join(', ') }}</span>
      </div>
      <div v-if="workflowStore.generatedCreatives.length > 0">
        <span class="font-medium text-gray-900">Creatives:</span>
        <span class="text-gray-700 ml-2">{{ workflowStore.generatedCreatives.length }} asset(s) generated</span>
      </div>
    </div>

    <!-- Posting Options -->
    <div class="space-y-4">
      <h4 class="font-semibold text-gray-900">Posting Behavior</h4>
      
      <!-- Immediate vs Scheduled -->
      <div class="space-y-3">
        <label class="flex items-center">
          <input
            v-model="behavior.immediate"
            type="radio"
            :value="true"
            name="posting-time"
            class="text-primary-600 focus:ring-primary-500"
          />
          <span class="ml-2 text-sm text-gray-700">Post immediately</span>
        </label>
        
        <label class="flex items-center">
          <input
            v-model="behavior.immediate"
            type="radio"
            :value="false"
            name="posting-time"
            class="text-primary-600 focus:ring-primary-500"
          />
          <span class="ml-2 text-sm text-gray-700">Schedule for later</span>
        </label>
      </div>

      <!-- Schedule Time (if scheduled) -->
      <div v-if="!behavior.immediate" class="space-y-3">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Schedule Date & Time</label>
          <input
            v-model="behavior.schedule_time"
            type="datetime-local"
            class="input"
            :min="minDateTime"
          />
        </div>
        
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Timezone</label>
          <select v-model="behavior.timezone" class="input">
            <option value="UTC">UTC</option>
            <option value="America/New_York">Eastern Time</option>
            <option value="America/Chicago">Central Time</option>
            <option value="America/Denver">Mountain Time</option>
            <option value="America/Los_Angeles">Pacific Time</option>
            <option value="Europe/London">London</option>
            <option value="Europe/Paris">Paris</option>
            <option value="Asia/Tokyo">Tokyo</option>
          </select>
        </div>
      </div>

      <!-- Advanced Options -->
      <div class="space-y-3">
        <label class="flex items-center">
          <input
            v-model="behavior.auto_optimize"
            type="checkbox"
            class="rounded border-gray-300 text-primary-600 focus:ring-primary-500"
          />
          <span class="ml-2 text-sm text-gray-700">Auto-optimize posting time for maximum engagement</span>
        </label>
        
        <label class="flex items-center">
          <input
            v-model="behavior.cross_platform"
            type="checkbox"
            class="rounded border-gray-300 text-primary-600 focus:ring-primary-500"
          />
          <span class="ml-2 text-sm text-gray-700">Cross-post to multiple platforms</span>
        </label>
      </div>

      <!-- Platform Selection (if cross-posting) -->
      <div v-if="behavior.cross_platform" class="space-y-3">
        <h5 class="font-medium text-gray-900">Select Platforms</h5>
        <div class="grid grid-cols-2 gap-3">
          <label
            v-for="account in connectedAccounts"
            :key="account.id"
            class="flex items-center p-3 border rounded-lg cursor-pointer hover:bg-gray-50"
          >
            <input
              v-model="behavior.platforms"
              :value="account.platform"
              type="checkbox"
              class="rounded border-gray-300 text-primary-600 focus:ring-primary-500"
            />
            <img
              :src="account.avatar"
              :alt="account.display_name"
              class="w-6 h-6 rounded-full ml-3 mr-2"
            />
            <div class="flex-1 min-w-0">
              <div class="text-sm font-medium text-gray-900 truncate">{{ account.display_name }}</div>
              <div class="text-xs text-gray-500">{{ getPlatformName(account.platform) }}</div>
            </div>
          </label>
        </div>
      </div>
    </div>

    <!-- Preview Schedule -->
    <div v-if="schedulePreview.length > 0" class="space-y-4">
      <h4 class="font-semibold text-gray-900">Posting Schedule Preview</h4>
      
      <div class="space-y-2">
        <div
          v-for="(schedule, index) in schedulePreview"
          :key="index"
          class="flex items-center justify-between p-3 bg-gray-50 rounded-lg"
        >
          <div class="flex items-center space-x-3">
            <span>{{ getPlatformIcon(schedule.platform) }}</span>
            <div>
              <div class="text-sm font-medium text-gray-900">{{ getPlatformName(schedule.platform) }}</div>
              <div class="text-xs text-gray-500">{{ schedule.time }}</div>
            </div>
          </div>
          <span
            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
            :class="{
              'bg-green-100 text-green-800': schedule.status === 'immediate',
              'bg-blue-100 text-blue-800': schedule.status === 'scheduled'
            }"
          >
            {{ schedule.status === 'immediate' ? 'Immediate' : 'Scheduled' }}
          </span>
        </div>
      </div>
    </div>

    <!-- Action Buttons -->
    <div class="flex justify-center space-x-4">
      <button
        v-if="behavior.immediate"
        @click="publishNow"
        :disabled="loading.publish"
        class="btn-primary px-8"
      >
        <LoadingSpinner v-if="loading.publish" size="sm" class="mr-2" />
        🚀 Publish Now
      </button>
      
      <button
        v-else
        @click="schedulePost"
        :disabled="loading.schedule || !isValidSchedule"
        class="btn-primary px-8"
      >
        <LoadingSpinner v-if="loading.schedule" size="sm" class="mr-2" />
        📅 Schedule Post
      </button>
      
      <button
        @click="saveDraft"
        :disabled="loading.draft"
        class="btn-secondary px-8"
      >
        <LoadingSpinner v-if="loading.draft" size="sm" class="mr-2" />
        💾 Save Draft
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, computed, onMounted, watch } from 'vue';
import { useWorkflowStore } from '@/stores/workflow';
import { useUiStore } from '@/stores/ui';
import { postingApi, socialAccountsApi } from '@/services/api';
import type { PostingBehavior, SocialAccount, SocialPlatform } from '@/types';

import LoadingSpinner from '@/components/ui/LoadingSpinner.vue';

const workflowStore = useWorkflowStore();
const uiStore = useUiStore();

const connectedAccounts = ref<SocialAccount[]>([]);

const behavior = reactive<PostingBehavior>({
  immediate: true,
  scheduled: false,
  auto_optimize: false,
  cross_platform: false,
  platforms: [],
  timezone: 'UTC'
});

const loading = ref({
  publish: false,
  schedule: false,
  draft: false
});

const minDateTime = computed(() => {
  const now = new Date();
  now.setMinutes(now.getMinutes() + 30); // Minimum 30 minutes from now
  return now.toISOString().slice(0, 16);
});

const isValidSchedule = computed(() => {
  if (behavior.immediate) return true;
  if (!behavior.schedule_time) return false;
  const scheduleDate = new Date(behavior.schedule_time);
  const now = new Date();
  return scheduleDate > now;
});

const schedulePreview = computed(() => {
  const platforms = behavior.cross_platform ? behavior.platforms : workflowStore.selectedAccounts.map(a => a.platform);
  
  return platforms.map(platform => ({
    platform: platform!,
    time: behavior.immediate ? 'Now' : formatDateTime(behavior.schedule_time || ''),
    status: behavior.immediate ? 'immediate' : 'scheduled'
  }));
});

const loadConnectedAccounts = async () => {
  try {
    const response = await socialAccountsApi.getAccounts();
    if (response.success) {
      connectedAccounts.value = response.data.filter(account => account.is_connected);
    }
  } catch (error) {
    console.error('Failed to load accounts:', error);
  }
};

const publishNow = async () => {
  if (!workflowStore.generatedContent) return;

  loading.value.publish = true;
  try {
    const platforms = behavior.cross_platform ? behavior.platforms : workflowStore.selectedAccounts.map(a => a.platform);
    const response = await postingApi.publishImmediately(workflowStore.generatedContent.id, platforms);
    
    if (response.success) {
      workflowStore.setPostingBehavior(behavior);
      
      uiStore.addNotification({
        type: 'success',
        title: 'Content Published',
        message: `Successfully published to ${response.data.published.length} platform(s)`
      });

      if (response.data.failed.length > 0) {
        uiStore.addNotification({
          type: 'warning',
          title: 'Partial Success',
          message: `Failed to publish to ${response.data.failed.join(', ')}`
        });
      }
    }
  } catch (error) {
    console.error('Failed to publish:', error);
    uiStore.addNotification({
      type: 'error',
      title: 'Publishing Failed',
      message: 'Failed to publish content. Please try again.'
    });
  } finally {
    loading.value.publish = false;
  }
};

const schedulePost = async () => {
  if (!workflowStore.generatedContent || !isValidSchedule.value) return;

  loading.value.schedule = true;
  try {
    const response = await postingApi.schedulePost(behavior, workflowStore.generatedContent.id);
    
    if (response.success) {
      workflowStore.setPostingBehavior(behavior);
      response.data.forEach(schedule => {
        workflowStore.addPostingSchedule(schedule);
      });
      
      uiStore.addNotification({
        type: 'success',
        title: 'Content Scheduled',
        message: `Successfully scheduled for ${formatDateTime(behavior.schedule_time!)}`
      });
    }
  } catch (error) {
    console.error('Failed to schedule:', error);
    uiStore.addNotification({
      type: 'error',
      title: 'Scheduling Failed',
      message: 'Failed to schedule content. Please try again.'
    });
  } finally {
    loading.value.schedule = false;
  }
};

const saveDraft = async () => {
  loading.value.draft = true;
  
  // Simulate saving draft
  setTimeout(() => {
    workflowStore.setPostingBehavior(behavior);
    
    uiStore.addNotification({
      type: 'info',
      title: 'Draft Saved',
      message: 'Content saved as draft successfully'
    });
    
    loading.value.draft = false;
  }, 1000);
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

const formatDateTime = (dateTimeString: string) => {
  if (!dateTimeString) return '';
  const date = new Date(dateTimeString);
  return date.toLocaleString();
};

// Initialize platforms array with selected accounts
watch(() => workflowStore.selectedAccounts, (accounts) => {
  if (accounts.length > 0) {
    behavior.platforms = accounts.map(a => a.platform);
  }
}, { immediate: true });

onMounted(() => {
  loadConnectedAccounts();
  
  // Set default schedule time to 1 hour from now
  const defaultTime = new Date();
  defaultTime.setHours(defaultTime.getHours() + 1);
  behavior.schedule_time = defaultTime.toISOString().slice(0, 16);
});
</script>

<style scoped>
.line-clamp-1 {
  display: -webkit-box;
  -webkit-line-clamp: 1;
  line-clamp: 1;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>

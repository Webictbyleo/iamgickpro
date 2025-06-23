<template>
  <div class="px-4 py-6">
    <!-- Header -->
    <div class="mb-8">
      <h1 class="text-3xl font-bold text-gray-900 mb-2">
        AI Social Media Studio
      </h1>
      <p class="text-lg text-gray-600">
        Create engaging content with AI-powered workflow automation
      </p>
    </div>

    <!-- Stats Overview -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
      <StatsCard
        title="Content Created"
        value="47"
        change="+12%"
        trend="up"
        icon="📝"
      />
      <StatsCard
        title="Avg. Engagement"
        value="8.4K"
        change="+24%"
        trend="up"
        icon="❤️"
      />
      <StatsCard
        title="Platforms Connected"
        value="5"
        change="0"
        trend="neutral"
        icon="🔗"
      />
    </div>

    <!-- Quick Actions -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
      <!-- Start New Workflow -->
      <div class="card">
        <div class="flex items-center mb-4">
          <div class="w-12 h-12 bg-primary-100 rounded-xl flex items-center justify-center mr-4">
            <span class="text-2xl">🚀</span>
          </div>
          <div>
            <h3 class="text-lg font-semibold text-gray-900">Create New Content</h3>
            <p class="text-gray-600">Start an AI-powered content creation workflow</p>
          </div>
        </div>
        <router-link
          to="/workflow"
          class="btn-primary w-full text-center"
        >
          Start Workflow
        </router-link>
      </div>

      <!-- Recent Activity -->
      <div class="card">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Recent Activity</h3>
        <div class="space-y-3">
          <ActivityItem
            action="Published to Twitter"
            content="AI Revolution in Workplace"
            time="2 hours ago"
            status="success"
          />
          <ActivityItem
            action="Generated creative"
            content="Sustainable Living Tips"
            time="4 hours ago"
            status="success"
          />
          <ActivityItem
            action="Scheduled post"
            content="Remote Work Best Practices"
            time="1 day ago"
            status="pending"
          />
        </div>
      </div>
    </div>

    <!-- Trending Topics Preview -->
    <div class="card">
      <div class="flex justify-between items-center mb-6">
        <h3 class="text-lg font-semibold text-gray-900">Trending Topics</h3>
        <router-link
          to="/workflow"
          class="text-primary-600 hover:text-primary-700 font-medium text-sm"
        >
          View all →
        </router-link>
      </div>
      
      <div v-if="loading.topics" class="flex justify-center py-8">
        <LoadingSpinner />
      </div>
      
      <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <TrendingTopicCard
          v-for="topic in trendingTopics.slice(0, 6)"
          :key="topic.id"
          :topic="topic"
          @select="handleTopicSelect"
          compact
        />
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useWorkflowStore } from '@/stores/workflow';
import { useUiStore } from '@/stores/ui';
import { trendingApi } from '@/services/api';
import type { TrendingTopic } from '@/types';

import StatsCard from '@/components/ui/StatsCard.vue';
import ActivityItem from '@/components/ui/ActivityItem.vue';
import TrendingTopicCard from '@/components/trending/TrendingTopicCard.vue';
import LoadingSpinner from '@/components/ui/LoadingSpinner.vue';

const router = useRouter();
const workflowStore = useWorkflowStore();
const uiStore = useUiStore();

const trendingTopics = ref<TrendingTopic[]>([]);
const loading = ref({
  topics: false
});

const loadTrendingTopics = async () => {
  loading.value.topics = true;
  try {
    const response = await trendingApi.getTrendingTopics();
    if (response.success) {
      trendingTopics.value = response.data;
    }
  } catch (error) {
    console.error('Failed to load trending topics:', error);
    uiStore.addNotification({
      type: 'error',
      title: 'Error',
      message: 'Failed to load trending topics'
    });
  } finally {
    loading.value.topics = false;
  }
};

const handleTopicSelect = (topic: TrendingTopic) => {
  workflowStore.setSelectedTopic(topic);
  workflowStore.setCurrentStep('platform');
  router.push('/workflow');
};

onMounted(() => {
  loadTrendingTopics();
});
</script>

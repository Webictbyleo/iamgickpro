<template>
  <div 
    class="group relative border rounded-lg p-4 cursor-pointer transition-all duration-200"
    :class="{
      'border-blue-300 bg-blue-50 ring-1 ring-blue-100': selected,
      'border-gray-200 bg-white hover:border-gray-300 hover:shadow-sm': !selected,
      'p-3': compact
    }"
    @click="$emit('select', topic)"
  >
    <!-- Header -->
    <div class="flex items-start justify-between mb-3">
      <div class="flex items-center space-x-2">
        <span 
          class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium"
          :class="getCategoryColor(topic.category)"
        >
          {{ topic.category }}
        </span>
        <div class="flex items-center text-xs text-gray-500">
          <HeartIcon class="w-3 h-3 mr-1" />
          <span>{{ formatEngagement(topic.engagement) }}</span>
          <span class="mx-1">•</span>
          <span>{{ topic.trending_score }}%</span>
        </div>
      </div>
      
      <div v-if="!compact" class="flex items-center space-x-1">
        <div 
          class="w-2 h-2 rounded-full"
          :class="getTrendingColor(topic.trending_score)"
        ></div>
        <span class="text-xs text-gray-500">Live</span>
      </div>
    </div>

    <!-- Content -->
    <h3 class="font-semibold text-gray-900 mb-2 line-clamp-2 leading-tight">
      {{ topic.title }}
    </h3>
    
    <p class="text-sm text-gray-600 mb-3 line-clamp-2 leading-relaxed">
      {{ topic.description }}
    </p>

    <!-- Tags -->
    <div class="flex flex-wrap gap-1 mb-3">
      <span
        v-for="tag in topic.tags.slice(0, compact ? 2 : 3)"
        :key="tag"
        class="inline-block px-2 py-1 bg-gray-100 text-gray-700 text-xs rounded-md"
      >
        #{{ tag }}
      </span>
      <span 
        v-if="topic.tags.length > (compact ? 2 : 3)"
        class="inline-block px-2 py-1 bg-gray-200 text-gray-600 text-xs rounded-md"
      >
        +{{ topic.tags.length - (compact ? 2 : 3) }}
      </span>
    </div>

    <!-- Footer -->
    <div class="flex items-center justify-between text-xs text-gray-500">
      <span>{{ topic.source }}</span>
      <span>{{ formatTime(topic.created_at) }}</span>
    </div>

    <!-- Selection Indicator -->
    <div 
      v-if="selected"
      class="absolute top-2 right-2 w-6 h-6 bg-blue-600 rounded-full flex items-center justify-center"
    >
      <CheckIcon class="w-4 h-4 text-white" />
    </div>
    
    <!-- Enhanced Hover Effect -->
    <div class="absolute inset-0 rounded-3xl bg-gradient-to-br from-transparent via-primary-50/10 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300 pointer-events-none"></div>
  </div>
</template>

<script setup lang="ts">
import type { TrendingTopic } from '@/types';
import { CheckIcon, HeartIcon } from '@heroicons/vue/24/solid';

interface Props {
  topic: TrendingTopic;
  selected?: boolean;
  compact?: boolean;
}

interface Emits {
  (e: 'select', topic: TrendingTopic): void;
}

withDefaults(defineProps<Props>(), {
  selected: false,
  compact: false
});

defineEmits<Emits>();

const getCategoryColor = (category: string) => {
  const colors: Record<string, string> = {
    'Technology': 'bg-blue-100 text-blue-800',
    'Lifestyle': 'bg-green-100 text-green-800',
    'Business': 'bg-purple-100 text-purple-800',
    'Health': 'bg-red-100 text-red-800',
    'Finance': 'bg-yellow-100 text-yellow-800',
    'Travel': 'bg-indigo-100 text-indigo-800',
  };
  return colors[category] || 'bg-gray-100 text-gray-800';
};

const getTrendingColor = (score: number) => {
  if (score >= 90) return 'bg-red-500';
  if (score >= 80) return 'bg-orange-500';
  if (score >= 70) return 'bg-yellow-500';
  return 'bg-green-500';
};

const formatEngagement = (engagement: number) => {
  if (engagement >= 1000000) {
    return `${(engagement / 1000000).toFixed(1)}M`;
  }
  if (engagement >= 1000) {
    return `${(engagement / 1000).toFixed(1)}K`;
  }
  return engagement.toString();
};

const formatTime = (dateString: string) => {
  const date = new Date(dateString);
  const now = new Date();
  const diffInHours = Math.floor((now.getTime() - date.getTime()) / (1000 * 60 * 60));
  
  if (diffInHours < 1) return 'Just now';
  if (diffInHours < 24) return `${diffInHours}h ago`;
  return `${Math.floor(diffInHours / 24)}d ago`;
};
</script>

<style scoped>
.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>

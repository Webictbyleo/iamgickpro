<template>
  <div class="space-y-8">
    <!-- Enhanced Selected Topic Display -->
    <div v-if="workflowStore.selectedTopic" class="relative overflow-hidden bg-gradient-to-br from-green-50 via-emerald-50 to-teal-50 border-2 border-green-300 rounded-3xl p-8 shadow-xl animate-scale-in">
      <div class="flex items-center space-x-6">
        <div class="flex-shrink-0">
          <div class="w-16 h-16 bg-gradient-to-br from-green-500 via-green-600 to-green-700 rounded-2xl flex items-center justify-center shadow-2xl rotate-3 hover:rotate-0 transition-transform duration-300">
            <CheckIcon class="w-8 h-8 text-white" />
          </div>
        </div>
        <div class="flex-1">
          <h4 class="text-2xl font-bold text-green-900 mb-2 flex items-center space-x-2">
            <span>✨ Perfect Topic Selected!</span>
          </h4>
          <p class="text-green-800 font-semibold text-lg">{{ workflowStore.selectedTopic.title }}</p>
          <p class="text-green-700 text-base mt-2 leading-relaxed">{{ workflowStore.selectedTopic.description }}</p>
          <div class="flex items-center space-x-4 mt-4">
            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-green-200 text-green-800">
              {{ workflowStore.selectedTopic.category }}
            </span>
            <span class="text-green-600 text-sm font-medium">
              🔥 {{ workflowStore.selectedTopic.engagement.toLocaleString() }} engagement
            </span>
          </div>
        </div>
        <div class="text-right">
          <div class="text-4xl animate-bounce-gentle">🚀</div>
          <div class="text-sm font-semibold text-green-600 mt-2 flex items-center space-x-1">
            <div class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></div>
            <span>Auto-advancing...</span>
          </div>
        </div>
      </div>
      
      <!-- Enhanced decorative background -->
      <div class="absolute -top-6 -right-6 w-32 h-32 bg-green-300/20 rounded-full blur-2xl animate-pulse"></div>
      <div class="absolute -bottom-4 -left-4 w-24 h-24 bg-emerald-300/15 rounded-full blur-xl animate-float"></div>
      <div class="absolute top-1/2 right-8 w-16 h-16 bg-teal-300/10 rounded-full blur-lg"></div>
    </div>

    <!-- Enhanced Category Filter -->
    <div class="bg-white/80 backdrop-blur-sm rounded-2xl border border-gray-200 p-8 shadow-lg animate-fade-in-up">
      <div class="flex items-center space-x-3 mb-6">
        <div class="w-8 h-8 bg-gradient-to-br from-primary-500 to-primary-600 rounded-lg flex items-center justify-center">
          <span class="text-lg">🎯</span>
        </div>
        <h3 class="text-xl font-bold text-gray-900">Browse by Category</h3>
      </div>
      <div class="flex flex-wrap gap-3">
        <button
          @click="selectedCategory = null"
          class="px-6 py-3 rounded-2xl text-sm font-semibold transition-all duration-300 transform hover:scale-105 hover:rotate-1 shadow-md hover:shadow-lg"
          :class="{
            'bg-gradient-to-r from-primary-500 via-primary-600 to-primary-700 text-white shadow-xl ring-2 ring-primary-300/50': selectedCategory === null,
            'bg-gray-100 text-gray-700 hover:bg-gray-200 hover:shadow-lg border border-gray-200': selectedCategory !== null
          }"
        >
          <span class="flex items-center space-x-2">
            <span class="text-lg">🌟</span>
            <span>All Topics</span>
          </span>
        </button>
        <button
          v-for="category in categories"
          :key="category.id"
          @click="selectedCategory = category.id"
          class="px-6 py-3 rounded-2xl text-sm font-semibold transition-all duration-300 transform hover:scale-105 hover:rotate-1 shadow-md hover:shadow-lg"
          :class="{
            'bg-gradient-to-r from-primary-500 via-primary-600 to-primary-700 text-white shadow-xl ring-2 ring-primary-300/50': selectedCategory === category.id,
            'bg-gray-100 text-gray-700 hover:bg-gray-200 hover:shadow-lg border border-gray-200': selectedCategory !== category.id
          }"
        >
          <span class="flex items-center space-x-2">
            <span class="text-lg">{{ category.icon }}</span>
            <span>{{ category.name }}</span>
          </span>
        </button>
      </div>
    </div>

    <!-- Enhanced Search -->
    <div class="bg-white/80 backdrop-blur-sm rounded-2xl border border-gray-200 p-8 shadow-lg animate-fade-in-up">
      <div class="flex items-center space-x-3 mb-6">
        <div class="w-8 h-8 bg-gradient-to-br from-blue-500 to-blue-600 rounded-lg flex items-center justify-center">
          <MagnifyingGlassIcon class="w-4 h-4 text-white" />
        </div>
        <h3 class="text-xl font-bold text-gray-900">Search Topics</h3>
      </div>
      <div class="relative group">
        <MagnifyingGlassIcon class="absolute left-5 top-1/2 transform -translate-y-1/2 h-5 w-5 text-gray-400 transition-colors group-focus-within:text-primary-500" />
        <input
          v-model="searchQuery"
          type="text"
          placeholder="Search trending topics, hashtags, or keywords..."
          class="w-full pl-14 pr-12 py-4 border-2 border-gray-300 rounded-2xl focus:outline-none focus:ring-4 focus:ring-primary-500/20 focus:border-primary-500 text-gray-900 placeholder-gray-500 shadow-sm transition-all duration-300 text-lg font-medium"
          @input="handleSearch"
        />
        <div v-if="searchQuery" class="absolute right-4 top-1/2 transform -translate-y-1/2">
          <button
            @click="searchQuery = ''; searchResults = []"
            class="text-gray-400 hover:text-gray-600 transition-colors p-1 rounded-full hover:bg-gray-100"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>
        <!-- Search suggestion indicator -->
        <div v-if="!searchQuery" class="absolute right-4 top-1/2 transform -translate-y-1/2 text-gray-400">
          <svg class="w-5 h-5 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
          </svg>
        </div>
      </div>
    </div>

    <!-- Enhanced Loading State -->
    <div v-if="loading.topics || loading.search" class="flex flex-col items-center justify-center py-20 bg-white/50 backdrop-blur-sm rounded-2xl border border-gray-200">
      <div class="relative mb-8">
        <LoadingSpinner size="lg" />
        <div class="absolute -inset-6 bg-primary-100 rounded-full animate-ping opacity-20"></div>
        <div class="absolute -inset-3 bg-primary-200 rounded-full animate-pulse opacity-30"></div>
      </div>
      <h3 class="text-xl font-bold text-gray-900 mb-2">
        {{ loading.search ? '🔍 Searching Trending Topics...' : '📡 Loading Trending Topics...' }}
      </h3>
      <p class="text-gray-600 font-medium mb-6 text-center max-w-md">
        {{ loading.search ? 'Finding the most relevant trending topics for your search' : 'Fetching the latest AI-curated trending topics for your niche' }}
      </p>
      <div class="flex space-x-2">
        <div class="w-3 h-3 bg-primary-500 rounded-full animate-bounce"></div>
        <div class="w-3 h-3 bg-primary-500 rounded-full animate-bounce" style="animation-delay: 0.2s"></div>
        <div class="w-3 h-3 bg-primary-500 rounded-full animate-bounce" style="animation-delay: 0.4s"></div>
      </div>
    </div>

    <!-- Enhanced Topics Grid -->
    <div v-else-if="filteredTopics.length > 0" class="space-y-8">
      <div class="flex items-center justify-between bg-white/60 backdrop-blur-sm rounded-2xl p-6 border border-gray-200">
        <div class="flex items-center space-x-4">
          <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl flex items-center justify-center">
            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z" />
            </svg>
          </div>
          <div>
            <h3 class="text-xl font-bold text-gray-900">
              {{ searchQuery ? `🔍 Search Results` : `🔥 Trending Topics` }}
            </h3>
            <p class="text-gray-600 font-medium">{{ filteredTopics.length }} topics found</p>
          </div>
        </div>
        <div class="flex items-center space-x-3 text-sm text-gray-500">
          <div class="w-3 h-3 bg-red-500 rounded-full animate-pulse"></div>
          <span class="font-medium">Live trending data</span>
        </div>
      </div>
      
      <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-8">
        <div
          v-for="(topic, index) in filteredTopics"
          :key="topic.id"
          class="transform transition-all duration-500 hover:scale-105 animate-fade-in-up"
          :style="{ animationDelay: `${index * 0.1}s` }"
        >
          <TrendingTopicCard
            :topic="topic"
            :selected="workflowStore.selectedTopic?.id === topic.id"
            @select="handleTopicSelect"
            enhanced
          />
        </div>
      </div>
    </div>

    <!-- Enhanced Empty State -->
    <div v-else class="text-center py-20 bg-white/50 backdrop-blur-sm rounded-2xl border border-gray-200">
      <div class="relative mb-8">
        <div class="text-8xl animate-bounce-gentle filter drop-shadow-lg">🔍</div>
        <div class="absolute -inset-4 bg-gray-100 rounded-full animate-ping opacity-20"></div>
      </div>
      <h3 class="text-2xl font-bold text-gray-900 mb-4">No topics found</h3>
      <p class="text-gray-600 mb-8 max-w-md mx-auto text-lg leading-relaxed">
        {{ searchQuery 
          ? `No topics match "${searchQuery}". Try different keywords or browse categories above.` 
          : 'No trending topics available for this category. Try selecting a different category.' 
        }}
      </p>
      <div class="flex justify-center space-x-4">
        <button
          v-if="searchQuery"
          @click="searchQuery = ''; searchResults = []"
          class="btn-primary flex items-center space-x-2 px-6 py-3 text-lg font-semibold shadow-lg hover:shadow-xl transform hover:scale-105 transition-all duration-200"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
          <span>Clear Search</span>
        </button>
        <button
          @click="selectedCategory = null"
          class="btn-secondary flex items-center space-x-2 px-6 py-3 text-lg font-semibold shadow-lg hover:shadow-xl transform hover:scale-105 transition-all duration-200"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
          </svg>
          <span>Reset Filters</span>
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue';
import { useWorkflowStore } from '@/stores/workflow';
import { useUiStore } from '@/stores/ui';
import { trendingApi } from '@/services/api';
import type { TrendingTopic, TopicCategory } from '@/types';
import { MagnifyingGlassIcon } from '@heroicons/vue/24/outline';
import { CheckIcon } from '@heroicons/vue/24/solid';

import TrendingTopicCard from '@/components/trending/TrendingTopicCard.vue';
import LoadingSpinner from '@/components/ui/LoadingSpinner.vue';

const workflowStore = useWorkflowStore();
const uiStore = useUiStore();

const categories = ref<TopicCategory[]>([]);
const topics = ref<TrendingTopic[]>([]);
const searchResults = ref<TrendingTopic[]>([]);
const selectedCategory = ref<string | null>(null);
const searchQuery = ref('');

const loading = ref({
  categories: false,
  topics: false,
  search: false
});

const filteredTopics = computed(() => {
  if (searchQuery.value.trim()) {
    return searchResults.value;
  }
  
  if (selectedCategory.value) {
    return topics.value.filter(topic => topic.category === categories.value.find(c => c.id === selectedCategory.value)?.name);
  }
  
  return topics.value;
});

const loadCategories = async () => {
  loading.value.categories = true;
  try {
    const response = await trendingApi.getCategories();
    if (response.success) {
      categories.value = response.data;
    }
  } catch (error) {
    console.error('Failed to load categories:', error);
    uiStore.addNotification({
      type: 'error',
      title: 'Error',
      message: 'Failed to load topic categories'
    });
  } finally {
    loading.value.categories = false;
  }
};

const loadTopics = async () => {
  loading.value.topics = true;
  try {
    const response = await trendingApi.getTrendingTopics(selectedCategory.value || undefined);
    if (response.success) {
      topics.value = response.data;
    }
  } catch (error) {
    console.error('Failed to load topics:', error);
    uiStore.addNotification({
      type: 'error',
      title: 'Error',
      message: 'Failed to load trending topics'
    });
  } finally {
    loading.value.topics = false;
  }
};

const handleSearch = async () => {
  if (!searchQuery.value.trim()) {
    searchResults.value = [];
    return;
  }

  loading.value.search = true;
  try {
    const response = await trendingApi.searchTopics(searchQuery.value);
    if (response.success) {
      searchResults.value = response.data;
    }
  } catch (error) {
    console.error('Failed to search topics:', error);
    uiStore.addNotification({
      type: 'error',
      title: 'Error',
      message: 'Failed to search topics'
    });
  } finally {
    loading.value.search = false;
  }
};

const handleTopicSelect = (topic: TrendingTopic) => {
  workflowStore.setSelectedTopic(topic);
  uiStore.addNotification({
    type: 'success',
    title: 'Topic Selected',
    message: `Selected "${topic.title}" for content creation`
  });
  
  // Auto-advance to next step after a short delay
  setTimeout(() => {
    workflowStore.goToNextStep();
  }, 1500);
};

watch(selectedCategory, () => {
  if (!searchQuery.value.trim()) {
    loadTopics();
  }
});

onMounted(() => {
  loadCategories();
  loadTopics();
});
</script>

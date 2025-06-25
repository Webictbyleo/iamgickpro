<template>
  <div class="h-full flex flex-col">
    <!-- Compact Selected Topic Display -->
    <div v-if="workflowStore.selectedTopic" class="mb-4 bg-gradient-to-r from-green-50 to-emerald-50 border border-green-200 rounded-xl p-4 animate-scale-in">
      <div class="flex items-center space-x-4">
        <div class="w-10 h-10 bg-green-500 rounded-lg flex items-center justify-center">
          <CheckIcon class="w-5 h-5 text-white" />
        </div>
        <div class="flex-1 min-w-0">
          <h4 class="text-lg font-bold text-green-900 truncate">✨ {{ workflowStore.selectedTopic.title }}</h4>
          <div class="flex items-center space-x-3 mt-1">
            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-200 text-green-800">
              {{ workflowStore.selectedTopic.category }}
            </span>
            <span class="text-green-600 text-sm">🔥 {{ workflowStore.selectedTopic.engagement.toLocaleString() }}</span>
          </div>
        </div>
        <div class="text-2xl">🚀</div>
      </div>
    </div>

    <!-- Compact Filters and Search Row -->
    <div class="mb-6 bg-white rounded-xl border border-gray-200 p-4">
      <!-- Search Bar -->
      <div class="mb-4">
        <div class="relative">
          <MagnifyingGlassIcon class="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-gray-400" />
          <input
            v-model="searchQuery"
            type="text"
            placeholder="Search trending topics..."
            class="w-full pl-10 pr-10 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 text-sm"
            @input="handleSearch"
          />
          <button
            v-if="searchQuery"
            @click="searchQuery = ''; searchResults = []"
            class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-600"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>
      </div>

      <!-- Category Filter Chips -->
      <div class="flex flex-wrap gap-2">
        <button
          @click="selectedCategory = null"
          class="px-3 py-1.5 rounded-lg text-sm font-medium transition-all"
          :class="{
            'bg-primary-500 text-white': selectedCategory === null,
            'bg-gray-100 text-gray-700 hover:bg-gray-200': selectedCategory !== null
          }"
        >
          🌟 All
        </button>
        <button
          v-for="category in categories"
          :key="category.id"
          @click="selectedCategory = category.id"
          class="px-3 py-1.5 rounded-lg text-sm font-medium transition-all"
          :class="{
            'bg-primary-500 text-white': selectedCategory === category.id,
            'bg-gray-100 text-gray-700 hover:bg-gray-200': selectedCategory !== category.id
          }"
        >
          {{ category.icon }} {{ category.name }}
        </button>
      </div>
    </div>

    <!-- Trending Topics Content Area -->
    <div class="flex-1 min-h-0">
      <!-- Compact Loading State -->
      <div v-if="loading.topics || loading.search" class="flex flex-col items-center justify-center py-12 bg-white/50 rounded-xl border border-gray-200">
        <LoadingSpinner size="md" />
        <h3 class="text-lg font-bold text-gray-900 mt-4 mb-2">
          {{ loading.search ? '🔍 Searching...' : '📡 Loading Topics...' }}
        </h3>
        <p class="text-gray-600 text-sm text-center max-w-sm">
          {{ loading.search ? 'Finding relevant topics' : 'Fetching trending topics' }}
        </p>
      </div>

      <!-- Topics Grid -->
      <div v-else-if="filteredTopics.length > 0" class="h-full">
        <!-- Compact Results Header -->
        <div class="flex items-center justify-between mb-4 bg-white/60 rounded-lg p-3 border border-gray-200">
          <div class="flex items-center space-x-3">
            <div class="w-8 h-8 bg-blue-500 rounded-lg flex items-center justify-center">
              <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z" />
              </svg>
            </div>
            <div>
              <h3 class="text-lg font-bold text-gray-900">
                {{ searchQuery ? `🔍 Results` : `🔥 Trending` }}
              </h3>
              <p class="text-gray-600 text-sm">{{ filteredTopics.length }} topics</p>
            </div>
          </div>
          <div class="flex items-center space-x-2 text-xs text-gray-500">
            <div class="w-2 h-2 bg-red-500 rounded-full animate-pulse"></div>
            <span>Live</span>
          </div>
        </div>
        
        <!-- Scrollable Grid Container -->
        <div class="overflow-y-auto h-96">
          <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4 pr-2">
            <div
              v-for="topic in filteredTopics"
              :key="topic.id"
              class="transform transition-all duration-300 hover:scale-105"
            >
              <TrendingTopicCard
                :topic="topic"
                :selected="workflowStore.selectedTopic?.id === topic.id"
                @select="handleTopicSelect"
                compact
              />
            </div>
          </div>
        </div>
      </div>

      <!-- Compact Empty State -->
      <div v-else class="text-center py-12 bg-white/50 rounded-xl border border-gray-200">
        <div class="text-4xl mb-4">🔍</div>
        <h3 class="text-lg font-bold text-gray-900 mb-2">No topics found</h3>
        <p class="text-gray-600 mb-6 max-w-sm mx-auto text-sm">
          {{ searchQuery 
            ? `No matches for "${searchQuery}". Try different keywords.` 
            : 'No topics for this category. Try a different filter.' 
          }}
        </p>
        <div class="flex justify-center space-x-3">
          <button
            v-if="searchQuery"
            @click="searchQuery = ''; searchResults = []"
            class="px-4 py-2 bg-primary-500 text-white rounded-lg text-sm font-medium hover:bg-primary-600 transition-colors"
          >
            Clear Search
          </button>
          <button
            @click="selectedCategory = null"
            class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-200 transition-colors"
          >
            Reset Filters
          </button>
        </div>
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

<template>
  <div class="min-h-screen bg-gray-50">
    <!-- Professional Header -->
    <div class="bg-white border-b border-gray-200">
      <div class="max-w-7xl mx-auto px-6 py-8">
        <div class="flex items-center justify-between">
          <div class="flex items-center space-x-6">
            <div class="w-16 h-16 bg-gradient-to-br from-blue-600 to-indigo-700 rounded-2xl flex items-center justify-center shadow-lg">
              <span class="text-2xl">🚀</span>
            </div>
            <div>
              <h1 class="text-3xl font-bold text-gray-900">AI Social Media Studio</h1>
              <p class="text-lg text-gray-600 mt-1">Professional content creation platform</p>
            </div>
          </div>
          <div class="flex items-center space-x-4">
            <div class="text-right">
              <div class="text-sm text-gray-500">{{ new Date().toLocaleDateString('en-US', { weekday: 'long' }) }}</div>
              <div class="text-lg font-semibold text-gray-900">{{ new Date().toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' }) }}</div>
            </div>
            <div class="w-12 h-12 bg-green-100 rounded-xl flex items-center justify-center">
              <div class="w-3 h-3 bg-green-500 rounded-full animate-pulse"></div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="max-w-7xl mx-auto px-6 py-8">
      <!-- Professional Stats Grid -->
      <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 hover:shadow-md transition-shadow">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-gray-600">Content Created</p>
              <p class="text-3xl font-bold text-gray-900 mt-2">47</p>
              <div class="flex items-center mt-2">
                <div class="flex items-center text-green-600">
                  <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M5.293 9.707a1 1 0 010-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 01-1.414 1.414L11 7.414V15a1 1 0 11-2 0V7.414L6.707 9.707a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                  </svg>
                  <span class="text-sm font-medium">12%</span>
                </div>
                <span class="text-sm text-gray-500 ml-2">vs last month</span>
              </div>
            </div>
            <div class="w-12 h-12 bg-blue-50 rounded-lg flex items-center justify-center">
              <span class="text-xl">📝</span>
            </div>
          </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 hover:shadow-md transition-shadow">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-gray-600">Avg. Engagement</p>
              <p class="text-3xl font-bold text-gray-900 mt-2">8.4K</p>
              <div class="flex items-center mt-2">
                <div class="flex items-center text-green-600">
                  <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M5.293 9.707a1 1 0 010-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 01-1.414 1.414L11 7.414V15a1 1 0 11-2 0V7.414L6.707 9.707a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                  </svg>
                  <span class="text-sm font-medium">24%</span>
                </div>
                <span class="text-sm text-gray-500 ml-2">this week</span>
              </div>
            </div>
            <div class="w-12 h-12 bg-purple-50 rounded-lg flex items-center justify-center">
              <span class="text-xl">❤️</span>
            </div>
          </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 hover:shadow-md transition-shadow">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-gray-600">Active Platforms</p>
              <p class="text-3xl font-bold text-gray-900 mt-2">5</p>
              <div class="flex items-center mt-2">
                <div class="flex items-center text-gray-600">
                  <span class="text-sm font-medium">All connected</span>
                </div>
              </div>
            </div>
            <div class="w-12 h-12 bg-green-50 rounded-lg flex items-center justify-center">
              <span class="text-xl">🔗</span>
            </div>
          </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 hover:shadow-md transition-shadow">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-gray-600">Success Rate</p>
              <p class="text-3xl font-bold text-gray-900 mt-2">94%</p>
              <div class="flex items-center mt-2">
                <div class="flex items-center text-green-600">
                  <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M5.293 9.707a1 1 0 010-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 01-1.414 1.414L11 7.414V15a1 1 0 11-2 0V7.414L6.707 9.707a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                  </svg>
                  <span class="text-sm font-medium">3%</span>
                </div>
                <span class="text-sm text-gray-500 ml-2">improvement</span>
              </div>
            </div>
            <div class="w-12 h-12 bg-orange-50 rounded-lg flex items-center justify-center">
              <span class="text-xl">📈</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Main Content Grid -->
      <div class="grid grid-cols-1 xl:grid-cols-3 gap-8">
        <!-- Primary Action Card -->
        <div class="xl:col-span-2">
          <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="p-8">
              <div class="flex items-start justify-between mb-6">
                <div class="flex items-center space-x-4">
                  <div class="w-14 h-14 bg-gradient-to-br from-blue-600 to-indigo-700 rounded-xl flex items-center justify-center">
                    <span class="text-2xl">✨</span>
                  </div>
                  <div>
                    <h2 class="text-2xl font-bold text-gray-900">Create New Content</h2>
                    <p class="text-gray-600 mt-1">Start your AI-powered content creation workflow</p>
                  </div>
                </div>
                <div class="px-3 py-1 bg-blue-50 text-blue-700 rounded-full text-sm font-medium">
                  AI Powered
                </div>
              </div>

              <div class="grid grid-cols-3 gap-6 mb-8">
                <div class="text-center p-4 bg-gray-50 rounded-lg">
                  <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center mx-auto mb-3">
                    <span class="text-lg">🎯</span>
                  </div>
                  <h3 class="font-semibold text-gray-900 mb-1">Choose Topic</h3>
                  <p class="text-sm text-gray-600">Select trending topics or create custom ones</p>
                </div>
                <div class="text-center p-4 bg-gray-50 rounded-lg">
                  <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center mx-auto mb-3">
                    <span class="text-lg">🤖</span>
                  </div>
                  <h3 class="font-semibold text-gray-900 mb-1">AI Generation</h3>
                  <p class="text-sm text-gray-600">Let AI create engaging content for you</p>
                </div>
                <div class="text-center p-4 bg-gray-50 rounded-lg">
                  <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center mx-auto mb-3">
                    <span class="text-lg">🚀</span>
                  </div>
                  <h3 class="font-semibold text-gray-900 mb-1">Publish</h3>
                  <p class="text-sm text-gray-600">Schedule or publish across platforms</p>
                </div>
              </div>

              <router-link
                to="/workflow"
                class="w-full inline-flex items-center justify-center px-6 py-4 bg-gradient-to-r from-blue-600 to-indigo-700 hover:from-blue-700 hover:to-indigo-800 text-white font-semibold rounded-lg transition-all duration-200 transform hover:scale-[1.02] shadow-sm hover:shadow-md"
              >
                <span class="mr-2">🚀</span>
                Start Creating Content
              </router-link>
            </div>
          </div>
        </div>

        <!-- Activity Sidebar -->
        <div class="space-y-6">
          <!-- Trending Media Preview -->
          <div class="bg-white rounded-xl shadow-sm border border-gray-200">
            <div class="p-6 border-b border-gray-200">
              <div class="flex items-center justify-between">
                <div>
                  <h3 class="text-lg font-semibold text-gray-900">🔥 Viral Now</h3>
                  <p class="text-sm text-gray-600 mt-1">Recreate trending content</p>
                </div>
                <div class="w-2 h-2 bg-red-500 rounded-full animate-pulse"></div>
              </div>
            </div>
            <div class="p-6">
              <div class="space-y-4">
                <div class="flex items-center space-x-3 p-3 bg-gradient-to-r from-purple-50 to-pink-50 rounded-lg">
                  <div class="w-10 h-10 bg-purple-600 rounded-lg flex items-center justify-center">
                    <span class="text-white text-sm">🎥</span>
                  </div>
                  <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-gray-900">Day in My Life</p>
                    <p class="text-xs text-gray-500">2.5M views • TikTok</p>
                  </div>
                </div>
                <div class="flex items-center space-x-3 p-3 bg-gray-50 rounded-lg">
                  <div class="w-10 h-10 bg-orange-600 rounded-lg flex items-center justify-center">
                    <span class="text-white text-sm">🍳</span>
                  </div>
                  <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-gray-900">Quick Recipe</p>
                    <p class="text-xs text-gray-500">1.8M views • Instagram</p>
                  </div>
                </div>
                <div class="flex items-center space-x-3 p-3 bg-gray-50 rounded-lg">
                  <div class="w-10 h-10 bg-blue-600 rounded-lg flex items-center justify-center">
                    <span class="text-white text-sm">💡</span>
                  </div>
                  <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-gray-900">Productivity Tips</p>
                    <p class="text-xs text-gray-500">3.2M views • YouTube</p>
                  </div>
                </div>
              </div>
              <div class="mt-4">
                <button
                  @click="scrollToTrendingMedia"
                  class="w-full px-4 py-2 bg-purple-600 text-white text-sm font-medium rounded-lg hover:bg-purple-700 transition-colors"
                >
                  🎨 View All & Recreate
                </button>
              </div>
            </div>
          </div>

          <!-- Quick Stats -->
          <div class="bg-white rounded-xl shadow-sm border border-gray-200">
            <div class="p-6 border-b border-gray-200">
              <h3 class="text-lg font-semibold text-gray-900">Quick Insights</h3>
            </div>
            <div class="p-6 space-y-4">
              <div class="flex items-center justify-between">
                <span class="text-sm text-gray-600">Best performing time</span>
                <span class="text-sm font-medium text-gray-900">2:00 PM</span>
              </div>
              <div class="flex items-center justify-between">
                <span class="text-sm text-gray-600">Top platform</span>
                <span class="text-sm font-medium text-gray-900">Twitter</span>
              </div>
              <div class="flex items-center justify-between">
                <span class="text-sm text-gray-600">Content this week</span>
                <span class="text-sm font-medium text-gray-900">12 posts</span>
              </div>
              <div class="flex items-center justify-between">
                <span class="text-sm text-gray-600">AI suggestions used</span>
                <span class="text-sm font-medium text-gray-900">89%</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Trending Topics Section -->
      <div class="mt-8">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200">
          <div class="p-6 border-b border-gray-200">
            <div class="flex items-center justify-between">
              <div>
                <h3 class="text-lg font-semibold text-gray-900">Trending Topics</h3>
                <p class="text-sm text-gray-600 mt-1">Discover what's popular in your industry</p>
              </div>
              <router-link
                to="/workflow"
                class="text-blue-600 hover:text-blue-700 text-sm font-medium"
              >
                View all →
              </router-link>
            </div>
          </div>
          
          <div class="p-6">
            <div v-if="loading.topics" class="flex justify-center py-8">
              <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
            </div>
            
            <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
              <div 
                v-for="topic in trendingTopics.slice(0, 6)"
                :key="topic.id"
                @click="handleTopicSelect(topic)"
                class="group p-4 border border-gray-200 rounded-lg hover:border-blue-300 hover:shadow-sm transition-all duration-200 cursor-pointer bg-gray-50 hover:bg-white"
              >
                <div class="flex items-center justify-between mb-3">
                  <div class="w-10 h-10 bg-gradient-to-br from-blue-600 to-indigo-700 rounded-lg flex items-center justify-center text-white text-sm font-bold">
                    {{ topic.category?.charAt(0).toUpperCase() || '🔥' }}
                  </div>
                  <div class="text-right">
                    <div class="text-xs text-gray-500">Score</div>
                    <div class="text-sm font-bold text-blue-600">{{ topic.trending_score || '95' }}</div>
                  </div>
                </div>
                <h4 class="font-semibold text-gray-900 mb-2 group-hover:text-blue-600 transition-colors">
                  {{ topic.title }}
                </h4>
                <p class="text-sm text-gray-600 mb-3 line-clamp-2">
                  {{ topic.description }}
                </p>
                <div class="flex items-center justify-between">
                  <span class="px-2 py-1 bg-blue-100 text-blue-700 rounded text-xs font-medium">
                    {{ topic.category || 'Trending' }}
                  </span>
                  <span class="text-xs text-gray-500">
                    {{ topic.engagement || '2.5K' }} reach
                  </span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Trending Media Section -->
      <div id="trending-media-section" class="mt-8">
        <TrendingMediaSection />
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
import TrendingMediaSection from '@/components/trending/TrendingMediaSection.vue';
import type { TrendingTopic } from '@/types';

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

const scrollToTrendingMedia = () => {
  // Smooth scroll to trending media section
  const element = document.querySelector('#trending-media-section');
  if (element) {
    element.scrollIntoView({ behavior: 'smooth' });
  }
};

onMounted(() => {
  loadTrendingTopics();
});
</script>

<style scoped>
/* Line clamp utility */
.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

/* Professional transitions */
.transition-shadow {
  transition: box-shadow 0.15s ease-in-out;
}

.transition-all {
  transition: all 0.15s ease-in-out;
}

.transition-colors {
  transition: color 0.15s ease-in-out;
}

/* Hover effects */
.hover\:scale-\[1\.02\]:hover {
  transform: scale(1.02);
}

/* Focus styles */
button:focus,
a:focus {
  outline: 2px solid rgb(59 130 246);
  outline-offset: 2px;
}

/* Loading animation */
@keyframes spin {
  to { transform: rotate(360deg); }
}

.animate-spin {
  animation: spin 1s linear infinite;
}

.animate-pulse {
  animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}

@keyframes pulse {
  0%, 100% { opacity: 1; }
  50% { opacity: 0.5; }
}

/* Responsive adjustments */
@media (max-width: 768px) {
  .grid-cols-1.md\:grid-cols-4 {
    gap: 1rem;
  }
  
  .grid-cols-1.md\:grid-cols-2.lg\:grid-cols-3 {
    grid-template-columns: 1fr;
  }
  
  .xl\:col-span-2 {
    grid-column: span 1;
  }
  
  .text-3xl {
    font-size: 1.875rem;
  }
  
  .text-2xl {
    font-size: 1.5rem;
  }
}

/* Card hover effects */
.bg-white:hover {
  transform: translateY(-1px);
}

/* Professional shadows */
.shadow-sm {
  box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
}

.shadow-md {
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
}

/* Button enhancements */
.bg-gradient-to-r:hover {
  box-shadow: 0 4px 14px 0 rgba(0, 0, 0, 0.1);
}

/* Text color improvements */
.text-gray-900 {
  color: rgb(17 24 39);
}

.text-gray-600 {
  color: rgb(75 85 99);
}

.text-gray-500 {
  color: rgb(107 114 128);
}
</style>

<template>
  <div class="h-full flex flex-col bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-50">
    <!-- Modern Header with Topic Focus -->
    <div v-if="workflowStore.selectedTopic" 
         class="mb-8 bg-gradient-to-r from-blue-600 via-indigo-600 to-purple-600 rounded-3xl shadow-2xl overflow-hidden">
      <!-- Main Header -->
      <div class="p-8 text-white">
        <div class="flex items-center justify-between mb-6">
          <div class="flex items-center space-x-6">
            <div class="w-16 h-16 bg-white/20 rounded-3xl flex items-center justify-center backdrop-blur-sm">
              <span class="text-2xl">🎯</span>
            </div>
            <div>
              <div class="text-sm font-medium opacity-90 uppercase tracking-wide">Content Creation</div>
              <h1 class="text-3xl font-bold leading-tight">{{ workflowStore.selectedTopic?.title }}</h1>
              <div class="text-base opacity-80 mt-1">AI-powered content generation studio</div>
            </div>
          </div>
          <div class="bg-white/20 backdrop-blur-sm px-6 py-4 rounded-2xl border border-white/30">
            <div class="text-lg font-bold">⚡ AI Studio</div>
            <div class="text-sm opacity-90">Smart Generation</div>
          </div>
        </div>
        
        <!-- Progress Indicator -->
        <div class="bg-white/10 backdrop-blur-sm rounded-2xl p-4">
          <div class="flex items-center justify-between mb-3">
            <span class="text-sm font-semibold opacity-90">Workflow Progress</span>
            <span class="text-sm opacity-80">{{ getWorkflowProgress() }}% Complete</span>
          </div>
          <div class="w-full bg-white/20 rounded-full h-2">
            <div 
              class="bg-gradient-to-r from-yellow-400 to-orange-400 h-2 rounded-full transition-all duration-500"
              :style="{ width: `${getWorkflowProgress()}%` }"
            ></div>
          </div>
        </div>
      </div>
    </div>

    <!-- Selected Accounts Panel (Separate from header) -->
    <div v-if="workflowStore.selectedAccounts.length > 0" 
         class="mb-6 bg-white rounded-2xl shadow-lg border border-gray-100 overflow-hidden">
      <div class="bg-gradient-to-r from-green-500 to-emerald-600 p-4">
        <div class="flex items-center space-x-3">
          <div class="w-10 h-10 bg-white/20 backdrop-blur rounded-xl flex items-center justify-center">
            <span class="text-white text-lg">🚀</span>
          </div>
          <div class="text-white">
            <h3 class="text-lg font-bold">Publishing Destinations</h3>
            <p class="text-sm opacity-90">{{ workflowStore.selectedAccounts.length }} platform{{ workflowStore.selectedAccounts.length > 1 ? 's' : '' }} selected</p>
          </div>
        </div>
      </div>
      <div class="p-6">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
          <div 
            v-for="account in workflowStore.selectedAccounts" 
            :key="account.id"
            class="flex items-center space-x-3 p-4 bg-gray-50 rounded-xl border border-gray-200 hover:shadow-md transition-all duration-200"
          >
            <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center text-white font-bold">
              {{ getPlatformIcon(account.platform) }}
            </div>
            <div class="flex-1">
              <div class="font-semibold text-gray-900">{{ account.display_name }}</div>
              <div class="text-sm text-gray-500 capitalize">{{ account.platform }}</div>
              <div class="text-xs text-green-600 font-medium">✓ Connected</div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Streamlined Content Creation Workflow -->
    <div class="flex-1 min-h-0 space-y-8">
      <!-- Step 1: Goal Selection -->
      <div class="transform transition-all duration-500 ease-out" 
           :class="selectedGoal ? 'scale-95 opacity-75 hover:scale-100 hover:opacity-100' : 'scale-100'">
        <div class="bg-white rounded-3xl shadow-lg border border-gray-100 overflow-hidden">
          <div class="bg-gradient-to-r from-orange-500 to-red-500 p-5">
            <div class="flex items-center space-x-4">
              <div class="w-12 h-12 bg-white/20 backdrop-blur rounded-2xl flex items-center justify-center">
                <span class="text-white text-xl">🎯</span>
              </div>
              <div class="text-white">
                <h2 class="text-2xl font-bold">Choose Your Content Goal</h2>
                <p class="text-sm opacity-90">What do you want to achieve with this content?</p>
              </div>
              <div class="ml-auto bg-white/20 backdrop-blur px-4 py-2 rounded-xl">
                <span class="text-white text-sm font-medium">Step 1</span>
              </div>
            </div>
          </div>
          <div class="p-6">
            <QuickGoalSelector 
              v-model:selectedGoal="selectedGoal"
              @goal-selected="onGoalSelected"
            />
          </div>
        </div>
      </div>

      <!-- Step 2: Smart Suggestions -->
      <div v-if="selectedGoal" 
           class="transform transition-all duration-700 ease-out translate-y-0 opacity-100"
           :class="quickInput ? 'scale-95 opacity-75 hover:scale-100 hover:opacity-100' : 'scale-100'">
        <div class="bg-white rounded-3xl shadow-lg border border-gray-100 overflow-hidden">
          <div class="bg-gradient-to-r from-purple-500 to-indigo-600 p-5">
            <div class="flex items-center space-x-4">
              <div class="w-12 h-12 bg-white/20 backdrop-blur rounded-2xl flex items-center justify-center">
                <span class="text-white text-xl">💡</span>
              </div>
              <div class="text-white">
                <h2 class="text-2xl font-bold">Smart Content Ideas</h2>
                <p class="text-sm opacity-90">AI-powered suggestions based on current trends</p>
              </div>
              <div class="ml-auto bg-white/20 backdrop-blur px-4 py-2 rounded-xl">
                <span class="text-white text-sm font-medium">Step 2</span>
              </div>
            </div>
          </div>
          <div class="p-6">
            <SmartSuggestions 
              :goal="selectedGoal"
              @suggestion-selected="onSuggestionSelected"
            />
          </div>
        </div>
      </div>

      <!-- Step 3: Quick Input for Custom Ideas -->
      <div v-if="selectedGoal" class="bg-white rounded-3xl shadow-lg border border-gray-100 overflow-hidden">
        <div class="bg-gradient-to-r from-emerald-500 to-teal-600 p-5">
          <div class="flex items-center space-x-4">
            <div class="w-12 h-12 bg-white/20 backdrop-blur rounded-2xl flex items-center justify-center">
              <span class="text-white text-xl">✏️</span>
            </div>
            <div class="text-white">
              <h2 class="text-2xl font-bold">Your Content Focus</h2>
              <p class="text-sm opacity-90">Tell us what you want to create or use a suggestion above</p>
            </div>
            <div class="ml-auto bg-white/20 backdrop-blur px-4 py-2 rounded-xl">
              <span class="text-white text-sm font-medium">Step 3</span>
            </div>
          </div>
        </div>
        <div class="p-6">
          <div class="space-y-4">
            <div>
              <label class="block text-lg font-semibold text-gray-800 mb-3">
                {{ getQuickInputLabel() }}
                <span class="text-red-500">*</span>
              </label>
              <textarea
                v-model="quickInput"
                :placeholder="getQuickInputPlaceholder()"
                rows="3"
                class="w-full px-4 py-4 border-2 border-gray-200 rounded-2xl focus:ring-4 focus:ring-emerald-100 focus:border-emerald-500 transition-all duration-200 bg-gray-50 focus:bg-white text-base placeholder-gray-500 resize-none"
                @keyup.enter.meta="quickGenerate"
                @keyup.enter.ctrl="quickGenerate"
              />
              <div class="flex items-center justify-between mt-2">
                <div class="text-sm text-gray-500">
                  💡 Be specific for better AI results
                </div>
                <div class="text-sm text-gray-400">
                  {{ quickInput.length }}/500
                </div>
              </div>
            </div>

            <!-- Advanced Options Toggle -->
            <div class="flex items-center justify-between">
              <div class="flex items-center space-x-4">
                <select 
                  v-model="targetAudience"
                  class="px-4 py-2 border border-gray-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-all duration-200 bg-gray-50 focus:bg-white text-sm"
                >
                  <option value="general">👥 General audience</option>
                  <option value="professionals">💼 Professionals</option>
                  <option value="entrepreneurs">🚀 Entrepreneurs</option>
                  <option value="customers">🎯 Existing customers</option>
                  <option value="prospects">🔍 Potential customers</option>
                </select>
                
                <select 
                  v-model="desiredAction"
                  class="px-4 py-2 border border-gray-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-all duration-200 bg-gray-50 focus:bg-white text-sm"
                >
                  <option value="engage">👍 Like & engage</option>
                  <option value="share">📤 Share with others</option>
                  <option value="comment">💬 Leave a comment</option>
                  <option value="visit">🔗 Visit website/link</option>
                  <option value="signup">📝 Sign up/Register</option>
                  <option value="follow">➕ Follow account</option>
                </select>
              </div>
              
              <!-- Quick Generate Button -->
              <button
                v-if="quickInput.trim()"
                @click="quickGenerate"
                :disabled="!canQuickGenerate || loading.generate"
                class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 text-white font-bold rounded-xl transition-all duration-200 transform hover:scale-105 disabled:opacity-50 disabled:cursor-not-allowed disabled:transform-none shadow-lg"
              >
                <span v-if="!loading.generate" class="mr-2">⚡</span>
                <ArrowPathIcon v-else class="w-5 h-5 mr-2 animate-spin" />
                <span>{{ loading.generate ? 'Creating...' : 'Quick Generate' }}</span>
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Step 4: Advanced Content Generator -->
      <div v-if="selectedGoal && quickInput" class="transform transition-all duration-700 ease-out translate-y-0 opacity-100">
        <div class="bg-white rounded-3xl shadow-lg border border-gray-100 overflow-hidden">
          <div class="bg-gradient-to-r from-blue-600 to-purple-600 p-5">
            <div class="flex items-center space-x-4">
              <div class="w-12 h-12 bg-white/20 backdrop-blur rounded-2xl flex items-center justify-center">
                <span class="text-white text-xl">🎨</span>
              </div>
              <div class="text-white">
                <h2 class="text-2xl font-bold">Advanced Content Studio</h2>
                <p class="text-sm opacity-90">Fine-tune your content with professional options</p>
              </div>
              <div class="ml-auto bg-white/20 backdrop-blur px-4 py-2 rounded-xl">
                <span class="text-white text-sm font-medium">Final Step</span>
              </div>
            </div>
          </div>
          <div class="p-6">
            <ContentGenerator 
              :goal="selectedGoal"
              :details="goalDetails"
              :topic="workflowStore.selectedTopic?.title"
              :platforms="workflowStore.selectedAccounts?.map(account => account.platform)"
              @generate="onGenerate"
              @content-generated="onContentGenerated"
            />
          </div>
        </div>
      </div>

      <!-- Generated Content Display -->
      <div v-if="generatedContent" class="bg-gradient-to-br from-green-50 via-emerald-50 to-teal-50 rounded-3xl border-2 border-green-200 shadow-xl overflow-hidden">
        <div class="bg-gradient-to-r from-green-600 to-emerald-600 p-6">
          <div class="flex items-center justify-between">
            <div class="flex items-center space-x-4">
              <div class="w-16 h-16 bg-white/20 backdrop-blur rounded-2xl flex items-center justify-center">
                <CheckCircleIcon class="w-8 h-8 text-white" />
              </div>
              <div class="text-white">
                <h2 class="text-3xl font-bold">Content Created Successfully! 🎉</h2>
                <p class="text-base opacity-90">Your AI-powered content is ready to captivate your audience</p>
              </div>
            </div>
            <div class="flex space-x-3">
              <button
                @click="generateContent"
                :disabled="loading.generate"
                class="inline-flex items-center px-5 py-3 bg-white/20 backdrop-blur border border-white/30 rounded-xl text-white hover:bg-white/30 transition-all duration-200 font-medium"
              >
                <ArrowPathIcon v-if="!loading.generate" class="w-5 h-5 mr-2" />
                <ArrowPathIcon v-else class="w-5 h-5 mr-2 animate-spin" />
                <span>{{ loading.generate ? 'Regenerating...' : 'Regenerate' }}</span>
              </button>
              <button
                @click="copyToClipboard"
                class="inline-flex items-center px-5 py-3 bg-white text-green-600 rounded-xl hover:bg-gray-50 transition-all duration-200 font-medium shadow-sm"
              >
                📋 Copy Content
              </button>
            </div>
          </div>
        </div>
        
        <!-- Enhanced Content Preview -->
        <div class="p-8">
          <div class="bg-white rounded-2xl shadow-lg border border-gray-100 overflow-hidden">
            <div class="bg-gradient-to-r from-gray-50 to-blue-50 px-8 py-6 border-b border-gray-100">
              <div class="flex items-center justify-between">
                <div class="flex items-center space-x-6">
                  <h3 class="text-2xl font-bold text-gray-900">Your Generated Content</h3>
                  <div class="flex items-center space-x-3">
                    <span class="px-4 py-2 bg-blue-100 text-blue-700 rounded-full text-sm font-medium">
                      {{ generatedContent.content?.text?.length || 0 }} characters
                    </span>
                    <span class="px-4 py-2 bg-purple-100 text-purple-700 rounded-full text-sm font-medium capitalize">
                      {{ generatedContent.type || 'text' }} post
                    </span>
                    <span class="px-4 py-2 bg-green-100 text-green-700 rounded-full text-sm font-medium capitalize">
                      {{ selectedGoal }} goal
                    </span>
                  </div>
                </div>
                <div class="flex items-center space-x-3">
                  <span class="text-base text-gray-600">Ready to publish</span>
                  <div class="w-4 h-4 bg-green-500 rounded-full animate-pulse"></div>
                </div>
              </div>
            </div>
            
            <div class="p-8">
              <div class="prose prose-lg max-w-none text-gray-800 leading-relaxed bg-gradient-to-br from-gray-50 to-white p-8 rounded-2xl border border-gray-100 whitespace-pre-line shadow-inner">
                {{ generatedContent.content?.text || 'Content will appear here...' }}
              </div>
            </div>
            
            <!-- Enhanced Engagement Prediction -->
            <div v-if="generatedContent.engagement_prediction" class="px-8 pb-8">
              <div class="bg-gradient-to-r from-blue-50 to-purple-50 rounded-2xl p-8 border border-blue-100">
                <div class="flex items-center space-x-3 mb-6">
                  <span class="text-3xl">📊</span>
                  <h4 class="text-2xl font-bold text-gray-900">Predicted Performance</h4>
                  <span class="px-3 py-1 bg-blue-100 text-blue-700 rounded-full text-sm font-medium">AI Forecast</span>
                </div>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                  <div class="text-center p-6 bg-white rounded-2xl shadow-sm border border-gray-100 hover:shadow-lg transition-all duration-200">
                    <div class="text-3xl font-bold text-green-600 mb-2">{{ generatedContent.engagement_prediction.likes }}</div>
                    <div class="text-sm text-gray-600 font-medium">Expected Likes</div>
                    <div class="text-sm text-green-600 mt-2">👍 High engagement</div>
                  </div>
                  <div class="text-center p-6 bg-white rounded-2xl shadow-sm border border-gray-100 hover:shadow-lg transition-all duration-200">
                    <div class="text-3xl font-bold text-blue-600 mb-2">{{ generatedContent.engagement_prediction.shares }}</div>
                    <div class="text-sm text-gray-600 font-medium">Shares</div>
                    <div class="text-sm text-blue-600 mt-2">📤 Great reach</div>
                  </div>
                  <div class="text-center p-6 bg-white rounded-2xl shadow-sm border border-gray-100 hover:shadow-lg transition-all duration-200">
                    <div class="text-3xl font-bold text-purple-600 mb-2">{{ generatedContent.engagement_prediction.comments }}</div>
                    <div class="text-sm text-gray-600 font-medium">Comments</div>
                    <div class="text-sm text-purple-600 mt-2">💬 Active discussion</div>
                  </div>
                  <div class="text-center p-6 bg-white rounded-2xl shadow-sm border border-gray-100 hover:shadow-lg transition-all duration-200">
                    <div class="text-3xl font-bold text-orange-600 mb-2">{{ formatReach(generatedContent.engagement_prediction.reach) }}</div>
                    <div class="text-sm text-gray-600 font-medium">Reach</div>
                    <div class="text-sm text-orange-600 mt-2">🚀 Wide impact</div>
                  </div>
                </div>
              </div>
            </div>
          </div>
          
          <!-- Next Steps CTA -->
          <div class="mt-8 flex flex-col sm:flex-row gap-6">
            <button
              @click="proceedToNext"
              class="flex-1 inline-flex items-center justify-center px-8 py-5 bg-gradient-to-r from-green-600 to-emerald-600 hover:from-green-700 hover:to-emerald-700 text-white font-bold text-lg rounded-2xl transition-all duration-200 transform hover:scale-105 shadow-xl"
            >
              <span class="mr-3 text-xl">🚀</span>
              Continue to Publishing
            </button>
            <button
              @click="saveAsDraft"
              class="flex-1 inline-flex items-center justify-center px-8 py-5 bg-white border-2 border-gray-200 hover:border-gray-300 text-gray-700 font-bold text-lg rounded-2xl transition-all duration-200 hover:shadow-lg"
            >
              <span class="mr-3 text-xl">💾</span>
              Save as Draft
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { useWorkflowStore } from '@/stores/workflow';
import { useUiStore } from '@/stores/ui';
import { mockGeneratedContent, simulateApiDelay } from '@/data/mockData';
import type { SocialPlatform } from '@/types';

// Import modular components
import QuickGoalSelector from '@/components/content/QuickGoalSelector.vue';
import SmartSuggestions from '@/components/content/SmartSuggestions.vue';
import ContentGenerator from '@/components/content/ContentGenerator.vue';

import { 
  ArrowPathIcon, 
  CheckCircleIcon
} from '@heroicons/vue/24/solid';

const workflowStore = useWorkflowStore();
const uiStore = useUiStore();

// Loading states
const loading = ref({
  generate: false
});

// Streamlined workflow state
const selectedGoal = ref<string>('');
const quickInput = ref<string>('');
const targetAudience = ref<string>('general');
const desiredAction = ref<string>('engage');
const generatedContent = ref<any>(null);

// Goal details computed from quick inputs
const goalDetails = computed(() => {
  const base = {
    target_audience: targetAudience.value,
    call_to_action: desiredAction.value
  };

  switch (selectedGoal.value) {
    case 'promote':
      return {
        ...base,
        product_name: quickInput.value,
        key_benefit: quickInput.value
      };
    case 'educate':
      return {
        ...base,
        topic: quickInput.value,
        skill_level: 'all'
      };
    case 'community':
      return {
        ...base,
        engagement_type: 'discussion'
      };
    case 'news':
      return {
        ...base,
        news_topic: quickInput.value,
        importance: quickInput.value
      };
    default:
      return base;
  }
});

// Event handlers for component integration
const onGoalSelected = (goal: string) => {
  selectedGoal.value = goal;
  // Auto-focus on quick input when goal is selected
  setTimeout(() => {
    const input = document.querySelector('input[placeholder*="' + getQuickInputPlaceholder() + '"]') as HTMLInputElement;
    if (input) input.focus();
  }, 100);
};

const onSuggestionSelected = (suggestion: any) => {
  console.log('Suggestion selected:', suggestion);
  // Auto-fill quick input based on suggestion
  if (suggestion.suggested_content) {
    quickInput.value = suggestion.suggested_content;
  } else if (suggestion.title) {
    quickInput.value = suggestion.title;
  }
  
  // Show notification
  uiStore.addNotification({
    type: 'success',
    title: 'Suggestion Applied! 🎯',
    message: `Using "${suggestion.title}" for content generation`
  });
};

const onGenerate = (generationData: any) => {
  console.log('Generating content with:', generationData);
  generateContent();
};

const onContentGenerated = (content: any) => {
  console.log('Content generated:', content);
  generatedContent.value = content;
  
  // Show success notification
  uiStore.addNotification({
    type: 'success',
    title: 'Content Generated Successfully! 🎉',
    message: 'Your AI-powered content is ready'
  });
  
  // Move to next step in workflow if available
  if (workflowStore.goToNextStep) {
    workflowStore.goToNextStep();
  }
};

// Can quick generate check
const canQuickGenerate = computed(() => {
  return selectedGoal.value && quickInput.value.trim() && 
         workflowStore.selectedTopic && workflowStore.selectedAccounts.length > 0;
});

// Quick generate function
const quickGenerate = async () => {
  if (!canQuickGenerate.value) {
    uiStore.addNotification({
      type: 'warning',
      title: 'Missing Information',
      message: 'Please complete all required fields to generate content'
    });
    return;
  }
  await generateContent();
};

// Quick input helpers
const getQuickInputLabel = () => {
  switch (selectedGoal.value) {
    case 'promote':
      return 'What are you promoting?';
    case 'educate':
      return 'What do you want to teach?';
    case 'community':
      return 'What topic for discussion?';
    case 'news':
      return 'What\'s the news about?';
    default:
      return 'What\'s your main topic?';
  }
};

const getQuickInputPlaceholder = () => {
  switch (selectedGoal.value) {
    case 'promote':
      return 'e.g., New AI Writing Tool, Summer Sale';
    case 'educate':
      return 'e.g., How to use AI for content creation';
    case 'community':
      return 'e.g., Best practices for remote work';
    case 'news':
      return 'e.g., Company milestone, industry update';
    default:
      return 'e.g., Your main topic or focus';
  }
};

// Platform icon helper
const getPlatformIcon = (platform: SocialPlatform) => {
  const icons: Record<SocialPlatform, string> = {
    twitter: '🐦',
    instagram: '📷',
    facebook: '👥',
    linkedin: '💼',
    tiktok: '🎵',
    youtube: '📺'
  };
  return icons[platform] || '📱';
};

// Generate content function
const generateContent = async () => {
  if (!selectedGoal.value || !quickInput.value.trim()) {
    uiStore.addNotification({
      type: 'warning',
      title: 'Missing Information',
      message: 'Please select a goal and provide details'
    });
    return;
  }

  loading.value.generate = true;
  
  try {
    // Show generation start notification
    uiStore.addNotification({
      type: 'info',
      title: 'Generating Content...',
      message: 'AI is creating personalized content for you'
    });
    
    // Simulate API delay
    await simulateApiDelay(2000);
    
    // Use mock data for now
    const platform = workflowStore.selectedAccounts[0]?.platform || 'twitter';
    const mockContent = mockGeneratedContent[platform];
    if (mockContent && mockContent.length > 0) {
      // Enhance mock content with current selections
      const enhancedContent = {
        ...mockContent[0],
        goal: selectedGoal.value,
        content: {
          ...mockContent[0].content,
          text: generateCustomMockText()
        },
        engagement_prediction: {
          likes: Math.floor(Math.random() * 500) + 100,
          shares: Math.floor(Math.random() * 100) + 20,
          comments: Math.floor(Math.random() * 50) + 10,
          reach: Math.floor(Math.random() * 10000) + 2000
        }
      };
      
      generatedContent.value = enhancedContent;
      workflowStore.setGeneratedContent(enhancedContent);
      
      uiStore.addNotification({
        type: 'success',
        title: 'Content Generated Successfully! 🎉',
        message: `Created amazing ${selectedGoal.value} content for your audience`
      });
    }
  } catch (error) {
    console.error('Error generating content:', error);
    uiStore.addNotification({
      type: 'error',
      title: 'Generation Failed',
      message: 'Failed to generate content. Please try again.'
    });
  } finally {
    loading.value.generate = false;
  }
};

// Generate custom mock text based on user selections
const generateCustomMockText = () => {
  const topic = workflowStore.selectedTopic?.title || 'trending topic';
  
  const templates = {
    promote: `🚀 Excited to introduce ${quickInput.value || 'our latest innovation'}! 
    
This ${topic}-focused solution is perfect for ${targetAudience.value} looking to enhance their workflow.

Key benefits:
✅ Streamlined process
✅ Proven results  
✅ Easy implementation

Ready to ${desiredAction.value === 'visit' ? 'learn more' : 'get started'}? 

#Innovation #${selectedGoal.value} #Growth`,
    
    educate: `📚 ${quickInput.value || topic} - What You Need to Know

Here's a quick breakdown for ${targetAudience.value}:

🎯 Key takeaways:
• Essential concepts explained
• Practical applications
• Common mistakes to avoid

Perfect for anyone looking to improve their understanding.

${desiredAction.value === 'comment' ? 'What\'s your experience with this?' : 'Save this for later!'}

#Education #${topic} #Learning`,
    
    community: `🤝 Let's discuss: ${quickInput.value || topic}

Quick question for my ${targetAudience.value} community:

What's your take on the latest trends in ${topic}? 

I'm curious to hear different perspectives and experiences.

Drop your thoughts below! 👇

#Community #Discussion #${topic}`,
    
    news: `📢 Breaking: ${quickInput.value || 'Industry Update'}

Important news for ${targetAudience.value}:

${topic} is evolving rapidly, and here's what you need to know:

• Impact on current practices
• New opportunities emerging
• Action steps to take

${desiredAction.value === 'visit' ? 'Full details in bio' : 'Thoughts?'} 

#News #${topic} #Industry`,
    
    default: `✨ ${quickInput.value || topic}

Sharing some insights about ${topic} for ${targetAudience.value}.

This is particularly relevant right now because of recent developments in the space.

What are your thoughts?

#${topic} #Insights #Growth`
  };
  
  return templates[selectedGoal.value as keyof typeof templates] || templates.default;
};

// Additional helper functions
const copyToClipboard = async () => {
  if (generatedContent.value?.content?.text) {
    try {
      await navigator.clipboard.writeText(generatedContent.value.content.text);
      uiStore.addNotification({
        type: 'success',
        title: 'Copied! 📋',
        message: 'Content copied to clipboard'
      });
    } catch (error) {
      console.error('Failed to copy:', error);
      uiStore.addNotification({
        type: 'error',
        title: 'Copy Failed',
        message: 'Could not copy to clipboard'
      });
    }
  }
};

const proceedToNext = () => {
  if (generatedContent.value && workflowStore.goToNextStep) {
    workflowStore.goToNextStep();
  }
};

const saveAsDraft = () => {
  if (generatedContent.value) {
    // Save content as draft logic here
    uiStore.addNotification({
      type: 'success',
      title: 'Draft Saved 💾',
      message: 'Content saved to drafts'
    });
  }
};

const formatReach = (reach: number | undefined) => {
  if (!reach) return '0';
  if (reach >= 1000000) {
    return `${(reach / 1000000).toFixed(1)}M`;
  }
  if (reach >= 1000) {
    return `${(reach / 1000).toFixed(1)}K`;
  }
  return reach.toString();
};

// Calculate workflow progress
const getWorkflowProgress = () => {
  let progress = 0;
  if (selectedGoal.value) progress += 25;
  if (quickInput.value.trim()) progress += 25;
  if (targetAudience.value && desiredAction.value) progress += 25;
  if (generatedContent.value) progress += 25;
  return progress;
};

// Initialize
onMounted(() => {
  // Auto-select first goal if none selected
  if (!selectedGoal.value) {
    selectedGoal.value = 'promote';
  }
});
</script>

<style scoped>
/* Enhanced Modern UI Styles */

/* Smooth, performant transitions */
.transition-all {
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

/* Advanced focus styles with accessibility */
input:focus, select:focus, textarea:focus {
  outline: none;
  box-shadow: 0 0 0 3px rgba(147, 51, 234, 0.1), 0 0 0 1px rgba(147, 51, 234, 0.3);
  transform: translateY(-1px);
}

/* Enhanced hover effects */
button:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
}

/* Glass morphism effect */
.backdrop-blur {
  backdrop-filter: blur(10px) saturate(180%);
  -webkit-backdrop-filter: blur(10px) saturate(180%);
}

/* Smooth loading animation */
@keyframes spin {
  to { transform: rotate(360deg); }
}

.animate-spin {
  animation: spin 1s linear infinite;
}

/* Pulse animation for status indicators */
@keyframes pulse {
  0%, 100% { opacity: 1; }
  50% { opacity: 0.5; }
}

.animate-pulse {
  animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}

/* Enhanced shadow system */
.shadow-sm {
  box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
}

.shadow-md {
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
}

.shadow-lg {
  box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
}

.shadow-xl {
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
}

.shadow-inner {
  box-shadow: inset 0 2px 4px 0 rgba(0, 0, 0, 0.06);
}

/* Modern gradient backgrounds */
.bg-gradient-to-r {
  background-image: linear-gradient(to right, var(--tw-gradient-stops));
}

.bg-gradient-to-br {
  background-image: linear-gradient(to bottom right, var(--tw-gradient-stops));
}

.bg-gradient-to-b {
  background-image: linear-gradient(to bottom, var(--tw-gradient-stops));
}

/* Interactive card effects */
.hover\:scale-105:hover {
  transform: scale(1.05);
}

.hover\:scale-100:hover {
  transform: scale(1.0);
}

/* Custom scrollbar for better UX */
.overflow-y-auto {
  scrollbar-width: thin;
  scrollbar-color: rgba(156, 163, 175, 0.5) transparent;
}

.overflow-y-auto::-webkit-scrollbar {
  width: 6px;
}

.overflow-y-auto::-webkit-scrollbar-track {
  background: transparent;
}

.overflow-y-auto::-webkit-scrollbar-thumb {
  background-color: rgba(156, 163, 175, 0.5);
  border-radius: 3px;
}

.overflow-y-auto::-webkit-scrollbar-thumb:hover {
  background-color: rgba(156, 163, 175, 0.7);
}

/* Text utilities */
.text-shadow-sm {
  text-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
}

/* Enhanced responsive breakpoints */
@media (max-width: 768px) {
  .md\:grid-cols-3 {
    grid-template-columns: 1fr;
  }
  
  .md\:flex-row {
    flex-direction: column;
  }
  
  .md\:space-x-4 > :not([hidden]) ~ :not([hidden]) {
    margin-left: 0;
    margin-top: 1rem;
  }
}

/* Smooth reveal animations */
@keyframes slideUp {
  from {
    opacity: 0;
    transform: translateY(20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.animate-slide-up {
  animation: slideUp 0.5s ease-out forwards;
}

/* Status indicator styles */
.status-indicator {
  position: relative;
  overflow: hidden;
}

.status-indicator::before {
  content: '';
  position: absolute;
  top: 0;
  left: -100%;
  width: 100%;
  height: 100%;
  background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
  transition: left 0.5s;
}

.status-indicator:hover::before {
  left: 100%;
}

/* Enhanced form styling */
input[type="text"], select {
  transition: all 0.2s ease;
}

input[type="text"]:focus, select:focus {
  background-color: white;
  border-color: #6366f1;
  box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
}

/* Button enhancement */
button {
  position: relative;
  overflow: hidden;
  transition: all 0.2s ease;
}

button::before {
  content: '';
  position: absolute;
  top: 50%;
  left: 50%;
  width: 0;
  height: 0;
  background: rgba(255, 255, 255, 0.2);
  border-radius: 50%;
  transform: translate(-50%, -50%);
  transition: width 0.3s ease, height 0.3s ease;
}

button:active::before {
  width: 300px;
  height: 300px;
}
</style>

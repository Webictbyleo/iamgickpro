<template>
  <div class="h-full flex flex-col bg-gray-50">
    <!-- Clean Header -->
    <div class="bg-white border-b border-gray-200 px-6 py-4">
      <div class="flex items-center justify-between">
        <div class="flex items-center space-x-4">
          <div class="w-10 h-10 bg-blue-600 rounded-lg flex items-center justify-center">
            <span class="text-white font-semibold">📝</span>
          </div>
          <div>
            <h1 class="text-xl font-semibold text-gray-900">Content Generation</h1>
            <p class="text-sm text-gray-600">Create engaging content for {{ workflowStore.selectedTopic?.title }}</p>
          </div>
        </div>
        
        <!-- Progress -->
        <div class="flex items-center space-x-4">
          <div class="text-sm text-gray-500">{{ getWorkflowProgress() }}% Complete</div>
          <div class="w-24 bg-gray-200 rounded-full h-2">
            <div 
              class="bg-blue-600 h-2 rounded-full transition-all duration-300"
              :style="{ width: `${getWorkflowProgress()}%` }"
            ></div>
          </div>
        </div>
      </div>
    </div>

    <!-- Selected Accounts Panel -->
    <div v-if="workflowStore.selectedAccounts.length > 0" 
         class="bg-white border-b border-gray-200 px-6 py-4">
      <div class="flex items-center justify-between mb-3">
        <h3 class="text-sm font-medium text-gray-900">Publishing to {{ workflowStore.selectedAccounts.length }} platform{{ workflowStore.selectedAccounts.length > 1 ? 's' : '' }}</h3>
      </div>
      <div class="flex flex-wrap gap-2">
        <div 
          v-for="account in workflowStore.selectedAccounts" 
          :key="account.id"
          class="inline-flex items-center space-x-2 px-3 py-1 bg-blue-50 text-blue-700 rounded-full text-sm"
        >
          <span>{{ getPlatformIcon(account.platform) }}</span>
          <span class="font-medium">{{ account.display_name }}</span>
        </div>
      </div>
    </div>

    <!-- Tab Navigation -->
    <div class="bg-white border-b border-gray-200 px-6">
      <nav class="flex space-x-8" role="tablist">
        <button
          v-for="tab in tabs"
          :key="tab.id"
          @click="activeTab = tab.id"
          :class="[
            'py-4 px-1 border-b-2 font-medium text-sm transition-colors duration-200',
            activeTab === tab.id
              ? 'border-blue-600 text-blue-600'
              : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
          ]"
          :disabled="!tab.enabled"
          role="tab"
          :aria-selected="activeTab === tab.id"
        >
          <span class="inline-flex items-center space-x-2">
            <span>{{ tab.icon }}</span>
            <span>{{ tab.label }}</span>
            <span v-if="tab.completed" class="text-green-500">✓</span>
          </span>
        </button>
      </nav>
    </div>

    <!-- Tab Content -->
    <div class="flex-1 overflow-auto bg-white">
      <!-- Goal Selection Tab -->
      <div v-show="activeTab === 'goal'" class="p-6 h-full">
        <div class="max-w-4xl mx-auto">
          <div class="mb-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-2">Choose Your Content Goal</h2>
            <p class="text-gray-600">What do you want to achieve with this content?</p>
          </div>
          <QuickGoalSelector 
            v-model:selectedGoal="selectedGoal"
            @goal-selected="onGoalSelected"
          />
        </div>
      </div>

      <!-- Smart Suggestions Tab -->
      <div v-show="activeTab === 'suggestions'" class="p-6 h-full">
        <div class="max-w-4xl mx-auto">
          <div class="mb-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-2">Smart Content Ideas</h2>
            <p class="text-gray-600">AI-powered suggestions based on current trends</p>
          </div>
          <SmartSuggestions 
            v-if="selectedGoal"
            :goal="selectedGoal"
            @suggestion-selected="onSuggestionSelected"
          />
        </div>
      </div>

      <!-- Content Details Tab -->
      <div v-show="activeTab === 'details'" class="p-6 h-full">
        <div class="max-w-4xl mx-auto space-y-8">
          <div class="mb-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-2">Content Details</h2>
            <p class="text-gray-600">Tell us what you want to create or use a suggestion</p>
          </div>
          
          

          <!-- Advanced Content Generator -->
          <div v-if="quickInput.trim()" class="bg-white border border-gray-200 rounded-lg p-6">
            <h3 class="text-base font-medium text-gray-900 mb-4">Advanced Options</h3>
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
    </div>

    <!-- Generated Content Display (Outside of tabs, appears after generation) -->
    <div v-if="generatedContent" class="bg-white border-t border-gray-200 p-6">
      <div class="max-w-4xl mx-auto">
        <div class="bg-green-50 border border-green-200 rounded-lg p-6">
          <div class="flex items-center justify-between mb-4">
            <div class="flex items-center space-x-3">
              <CheckCircleIcon class="w-6 h-6 text-green-600" />
              <h2 class="text-lg font-semibold text-gray-900">Content Created Successfully!</h2>
            </div>
            <div class="flex space-x-3">
              <button
                @click="generateContent"
                :disabled="loading.generate"
                class="inline-flex items-center px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 disabled:opacity-50 transition-colors duration-200"
              >
                <ArrowPathIcon :class="['w-4 h-4 mr-2', loading.generate ? 'animate-spin' : '']" />
                <span>{{ loading.generate ? 'Regenerating...' : 'Regenerate' }}</span>
              </button>
              <button
                @click="copyToClipboard"
                class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors duration-200"
              >
                📋 Copy Content
              </button>
            </div>
          </div>
          
          <!-- Content Preview -->
          <div class="bg-white rounded-lg border border-gray-200 p-6 mb-4">
            <div class="flex items-center justify-between mb-4">
              <h3 class="text-base font-medium text-gray-900">Generated Content</h3>
              <div class="flex items-center space-x-3 text-xs text-gray-500">
                <span class="px-2 py-1 bg-blue-100 text-blue-700 rounded-full">
                  {{ generatedContent.content?.text?.length || 0 }} chars
                </span>
                <span class="px-2 py-1 bg-purple-100 text-purple-700 rounded-full capitalize">
                  {{ generatedContent.type || 'text' }} post
                </span>
                <span class="px-2 py-1 bg-green-100 text-green-700 rounded-full capitalize">
                  {{ selectedGoal }} goal
                </span>
              </div>
            </div>
            
            <div class="prose max-w-none text-gray-800 leading-relaxed whitespace-pre-line">
              {{ generatedContent.content?.text || 'Content will appear here...' }}
            </div>
          </div>
          
          <!-- Engagement Prediction -->
          <div v-if="generatedContent.engagement_prediction" class="bg-gray-50 rounded-lg p-4 mb-4">
            <h4 class="text-sm font-medium text-gray-900 mb-3">Predicted Performance</h4>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
              <div class="text-center">
                <div class="text-lg font-semibold text-green-600">{{ generatedContent.engagement_prediction.likes }}</div>
                <div class="text-xs text-gray-600">Expected Likes</div>
              </div>
              <div class="text-center">
                <div class="text-lg font-semibold text-blue-600">{{ generatedContent.engagement_prediction.shares }}</div>
                <div class="text-xs text-gray-600">Shares</div>
              </div>
              <div class="text-center">
                <div class="text-lg font-semibold text-purple-600">{{ generatedContent.engagement_prediction.comments }}</div>
                <div class="text-xs text-gray-600">Comments</div>
              </div>
              <div class="text-center">
                <div class="text-lg font-semibold text-orange-600">{{ formatReach(generatedContent.engagement_prediction.reach) }}</div>
                <div class="text-xs text-gray-600">Reach</div>
              </div>
            </div>
          </div>
          
          <!-- Next Steps -->
          <div class="flex flex-col sm:flex-row gap-3">
            <button
              @click="proceedToNext"
              class="flex-1 inline-flex items-center justify-center px-6 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors duration-200"
            >
              <span class="mr-2">🚀</span>
              Continue to Publishing
            </button>
            <button
              @click="saveAsDraft"
              class="flex-1 inline-flex items-center justify-center px-6 py-3 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors duration-200"
            >
              <span class="mr-2">💾</span>
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

// Tab management
const activeTab = ref('goal');
const tabs = computed(() => [
  {
    id: 'goal',
    label: 'Goal',
    icon: '🎯',
    enabled: true,
    completed: !!selectedGoal.value
  },
  {
    id: 'suggestions',
    label: 'Suggestions',
    icon: '💡',
    enabled: !!selectedGoal.value,
    completed: !!selectedGoal.value
  },
  {
    id: 'details',
    label: 'Details',
    icon: '✏️',
    enabled: !!selectedGoal.value,
    completed: !!quickInput.value.trim()
  }
]);

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
  // Auto-advance to suggestions tab
  setTimeout(() => {
    activeTab.value = 'suggestions';
  }, 500);
};

const onSuggestionSelected = (suggestion: any) => {
  console.log('Suggestion selected:', suggestion);
  // Auto-fill quick input based on suggestion
  if (suggestion.suggested_content) {
    quickInput.value = suggestion.suggested_content;
  } else if (suggestion.title) {
    quickInput.value = suggestion.title;
  }
  
  // Auto-advance to details tab
  setTimeout(() => {
    activeTab.value = 'details';
  }, 500);
  
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
  
  // Update workflow store and complete the content step
  workflowStore.setGeneratedContent(content);
  workflowStore.completeStep('content');
  
  // Show success notification
  uiStore.addNotification({
    type: 'success',
    title: 'Content Generated Successfully! 🎉',
    message: 'Your AI-powered content is ready to publish'
  });
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
  // Start with the goal tab
  activeTab.value = 'goal';
  
  // Auto-select first goal if none selected
  if (!selectedGoal.value) {
    selectedGoal.value = 'promote';
  }
});
</script>

<style scoped>
/* Clean, professional styling */
.transition-colors {
  transition: color 0.2s ease, background-color 0.2s ease, border-color 0.2s ease;
}

/* Focus styles for accessibility */
input:focus, select:focus, textarea:focus {
  outline: none;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

/* Button hover effects */
button:hover:not(:disabled) {
  transform: translateY(-1px);
}

button:disabled {
  cursor: not-allowed;
}

/* Tab styling */
nav[role="tablist"] button[role="tab"] {
  position: relative;
}

nav[role="tablist"] button[role="tab"]:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

/* Smooth scrolling */
.overflow-auto {
  scroll-behavior: smooth;
}

/* Custom scrollbar */
.overflow-auto::-webkit-scrollbar {
  width: 6px;
}

.overflow-auto::-webkit-scrollbar-track {
  background: #f1f5f9;
}

.overflow-auto::-webkit-scrollbar-thumb {
  background: #cbd5e1;
  border-radius: 3px;
}

.overflow-auto::-webkit-scrollbar-thumb:hover {
  background: #94a3b8;
}

/* Responsive adjustments */
@media (max-width: 768px) {
  .md\:grid-cols-2 {
    grid-template-columns: 1fr;
  }
  
  .sm\:flex-row {
    flex-direction: column;
  }
}
</style>

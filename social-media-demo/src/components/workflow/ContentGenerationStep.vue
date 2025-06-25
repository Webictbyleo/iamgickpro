<template>
  <div class="h-full flex flex-col">
    <!-- Compact Context Summary -->
    <div v-if="workflowStore.selectedTopic && workflowStore.selectedAccounts.length > 0" 
         class="mb-4 bg-gradient-to-r from-blue-50 to-indigo-50 rounded-xl p-4 border border-blue-200">
      <div class="flex items-center justify-between">
        <div class="flex items-center space-x-4">
          <div class="flex items-center space-x-2">
            <span class="text-sm font-medium text-gray-900">Topic:</span>
            <span class="text-sm text-gray-700">{{ workflowStore.selectedTopic?.title }}</span>
          </div>
          <div class="flex items-center space-x-2">
            <span class="text-sm font-medium text-gray-900">Platforms:</span>
            <div class="flex space-x-1">
              <span 
                v-for="account in workflowStore.selectedAccounts.slice(0, 3)" 
                :key="account.id"
                class="inline-flex items-center px-2 py-1 rounded text-xs bg-blue-100 text-blue-800"
              >
                {{ getPlatformIcon(account.platform) }} {{ account.display_name }}
              </span>
              <span v-if="workflowStore.selectedAccounts.length > 3" class="text-xs text-gray-500">
                +{{ workflowStore.selectedAccounts.length - 3 }} more
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Streamlined Workflow -->
    <div class="flex-1 min-h-0 space-y-4">
      <!-- 1. Quick Goal Selection (Collapsible after selection) -->
      <div class="transition-all duration-300" :class="selectedGoal ? 'opacity-75 hover:opacity-100' : ''">
        <QuickGoalSelector 
          v-model:selectedGoal="selectedGoal"
          @goal-selected="onGoalSelected"
        />
      </div>

      <!-- 2. Smart Suggestions (Shows when goal is selected) -->
      <div v-if="selectedGoal" class="transition-all duration-300">
        <SmartSuggestions 
          :goal="selectedGoal"
          @suggestion-selected="onSuggestionSelected"
        />
      </div>

      <!-- 3. Quick Details Collection (Compact form) -->
      <div v-if="selectedGoal" class="bg-white rounded-xl border border-gray-200 p-4">
        <div class="flex items-center space-x-3 mb-4">
          <div class="w-6 h-6 bg-gradient-to-br from-orange-500 to-red-600 rounded-lg flex items-center justify-center">
            <span class="text-white font-bold text-xs">2</span>
          </div>
          <h3 class="text-lg font-bold text-gray-900">Quick Details</h3>
          <span class="text-sm text-gray-500">Help AI create better content</span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <!-- Goal-specific quick input -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              {{ getQuickInputLabel() }}
            </label>
            <input
              v-model="quickInput"
              type="text"
              :placeholder="getQuickInputPlaceholder()"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent text-sm"
            />
          </div>

          <!-- Target audience -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Target Audience</label>
            <select 
              v-model="targetAudience"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent text-sm"
            >
              <option value="general">General audience</option>
              <option value="professionals">Professionals</option>
              <option value="entrepreneurs">Entrepreneurs</option>
              <option value="customers">Existing customers</option>
              <option value="prospects">Potential customers</option>
            </select>
          </div>

          <!-- Desired action -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Desired Action</label>
            <select 
              v-model="desiredAction"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent text-sm"
            >
              <option value="engage">Like & engage</option>
              <option value="share">Share with others</option>
              <option value="comment">Leave a comment</option>
              <option value="visit">Visit website/link</option>
              <option value="signup">Sign up/Register</option>
              <option value="follow">Follow account</option>
            </select>
          </div>
        </div>
      </div>

      <!-- 4. Content Templates (Shows relevant templates) -->
      <div v-if="selectedGoal && quickInput" class="transition-all duration-300">
        <ContentTemplates 
          :goal="selectedGoal"
          @template-selected="onTemplateSelected"
        />
      </div>

      <!-- 5. Content Generator (Final step) -->
      <div v-if="selectedGoal && quickInput" class="transition-all duration-300">
        <ContentGenerator 
          :goal="selectedGoal"
          :details="goalDetails"
          :topic="workflowStore.selectedTopic?.title"
          :platforms="workflowStore.selectedAccounts?.map(account => account.platform)"
          @generate="onGenerate"
          @content-generated="onContentGenerated"
        />
      </div>

      <!-- Generated Content Display -->
      <div v-if="generatedContent" class="bg-gradient-to-r from-green-50 to-emerald-50 rounded-xl border border-green-200 p-6">
        <div class="flex items-center justify-between mb-4">
          <div class="flex items-center space-x-2">
            <CheckCircleIcon class="w-6 h-6 text-green-600" />
            <h3 class="text-lg font-bold text-gray-900">Content Generated Successfully!</h3>
          </div>
          <div class="flex space-x-2">
            <button
              @click="generateContent"
              :disabled="loading.generate"
              class="inline-flex items-center px-4 py-2 bg-white border border-green-300 rounded-lg text-green-700 hover:bg-green-50 transition-colors"
            >
              <ArrowPathIcon v-if="!loading.generate" class="w-4 h-4 mr-2" />
              <ArrowPathIcon v-else class="w-4 h-4 mr-2 animate-spin" />
              <span>{{ loading.generate ? 'Regenerating...' : 'Regenerate' }}</span>
            </button>
          </div>
        </div>
        
        <!-- Content Preview -->
        <div class="bg-white rounded-lg p-6 border border-green-200">
          <div class="flex items-center justify-between mb-4">
            <h4 class="font-medium text-gray-900">Generated Content</h4>
            <div class="flex items-center space-x-4 text-sm text-gray-500">
              <span>{{ generatedContent.content?.text?.length || 0 }} characters</span>
              <span class="capitalize">{{ generatedContent.type || 'text' }}</span>
              <span class="px-2 py-1 bg-green-100 text-green-800 rounded text-xs">{{ selectedGoal }}</span>
            </div>
          </div>
          <div class="prose prose-sm max-w-none text-gray-800 leading-relaxed bg-gray-50 p-4 rounded border whitespace-pre-line">
            {{ generatedContent.content?.text || 'Content will appear here...' }}
          </div>
          
          <!-- Engagement Prediction -->
          <div v-if="generatedContent.engagement_prediction" class="mt-4 pt-4 border-t border-gray-200">
            <h5 class="font-medium text-gray-900 mb-3">📊 Predicted Performance</h5>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
              <div class="text-center p-3 bg-gray-50 rounded-lg">
                <div class="text-lg font-bold text-green-600">{{ generatedContent.engagement_prediction.likes }}</div>
                <div class="text-xs text-gray-600">Expected Likes</div>
              </div>
              <div class="text-center p-3 bg-gray-50 rounded-lg">
                <div class="text-lg font-bold text-blue-600">{{ generatedContent.engagement_prediction.shares }}</div>
                <div class="text-xs text-gray-600">Shares</div>
              </div>
              <div class="text-center p-3 bg-gray-50 rounded-lg">
                <div class="text-lg font-bold text-purple-600">{{ generatedContent.engagement_prediction.comments }}</div>
                <div class="text-xs text-gray-600">Comments</div>
              </div>
              <div class="text-center p-3 bg-gray-50 rounded-lg">
                <div class="text-lg font-bold text-orange-600">{{ generatedContent.engagement_prediction.reach }}</div>
                <div class="text-xs text-gray-600">Reach</div>
              </div>
            </div>
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
import ContentTemplates from '@/components/content/ContentTemplates.vue';
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
  }
};

const onTemplateSelected = (template: any) => {
  console.log('Template selected:', template);
  // Templates can influence content generation
};

const onGenerate = (generationData: any) => {
  console.log('Generating content with:', generationData);
  generateContent();
};

const onContentGenerated = (content: any) => {
  console.log('Content generated:', content);
  generatedContent.value = content;
  // Move to next step in workflow if available
  if (workflowStore.goToNextStep) {
    workflowStore.goToNextStep();
  }
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
        title: 'Content Generated',
        message: `Successfully created ${selectedGoal.value} content!`
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

// Initialize
onMounted(() => {
  // Auto-select first goal if none selected
  if (!selectedGoal.value) {
    selectedGoal.value = 'promote';
  }
});
</script>

<style scoped>
/* Smooth transitions for better UX */
.transition-all {
  transition: all 0.3s ease;
}

/* Custom focus styles */
input:focus, select:focus, textarea:focus {
  outline: none;
  box-shadow: 0 0 0 3px rgba(147, 51, 234, 0.1);
}

/* Loading animation */
@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

.animate-spin {
  animation: spin 1s linear infinite;
}
</style>

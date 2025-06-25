<template>
  <div class="space-y-6">
    <!-- Quick Generation Options -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
      <!-- Content Type Selection -->
      <div class="space-y-3">
        <label class="block text-sm font-semibold text-gray-800">Content Type</label>
        <div class="grid grid-cols-2 gap-3">
          <button 
            v-for="type in contentTypes" 
            :key="type.value"
            @click="selectedType = type.value"
            :class="[
              'p-4 rounded-xl border-2 transition-all duration-200 text-left',
              selectedType === type.value 
                ? 'border-green-500 bg-green-50 text-green-900' 
                : 'border-gray-200 hover:border-green-300 hover:bg-green-25'
            ]"
          >
            <div class="flex items-center space-x-3">
              <span class="text-xl">{{ type.icon }}</span>
              <div>
                <div class="font-medium text-sm">{{ type.label }}</div>
                <div class="text-xs text-gray-500">{{ type.engagement_rate }} avg</div>
              </div>
            </div>
          </button>
        </div>
      </div>

      <!-- Tone Selection -->
      <div class="space-y-3">
        <label class="block text-sm font-semibold text-gray-800">Content Tone</label>
        <select 
          v-model="selectedTone"
          class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all duration-200 bg-gray-50 focus:bg-white"
        >
          <option v-for="tone in tones" :key="tone.value" :value="tone.value">
            {{ tone.label }}
          </option>
        </select>
      </div>
    </div>

    <!-- Custom Prompt -->
    <div class="space-y-3">
      <label class="block text-sm font-semibold text-gray-800">
        Additional Instructions 
        <span class="text-gray-500 font-normal">(optional)</span>
      </label>
      <textarea
        v-model="customPrompt"
        :placeholder="getPromptPlaceholder()"
        rows="3"
        class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all duration-200 bg-gray-50 focus:bg-white resize-none"
      ></textarea>
    </div>

    <!-- Performance Preview -->
    <div class="bg-gradient-to-r from-green-50 to-emerald-50 rounded-xl p-4 border border-green-200">
      <h4 class="font-semibold text-gray-900 mb-3 flex items-center">
        <span class="mr-2">📊</span>
        Expected Performance
      </h4>
      <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="text-center p-3 bg-white rounded-lg shadow-sm">
          <div class="text-lg font-bold text-blue-600">{{ getPrediction('likes') }}</div>
          <div class="text-xs text-gray-600">Likes</div>
        </div>
        <div class="text-center p-3 bg-white rounded-lg shadow-sm">
          <div class="text-lg font-bold text-green-600">{{ getPrediction('shares') }}</div>
          <div class="text-xs text-gray-600">Shares</div>
        </div>
        <div class="text-center p-3 bg-white rounded-lg shadow-sm">
          <div class="text-lg font-bold text-purple-600">{{ getPrediction('comments') }}</div>
          <div class="text-xs text-gray-600">Comments</div>
        </div>
        <div class="text-center p-3 bg-white rounded-lg shadow-sm">
          <div class="text-lg font-bold text-orange-600">{{ getPrediction('reach') }}</div>
          <div class="text-xs text-gray-600">Reach</div>
        </div>
      </div>
    </div>

    <!-- Generate Button -->
    <div class="flex justify-center pt-4">
      <button
        @click="generateContent"
        :disabled="loading || !canGenerate"
        class="inline-flex items-center px-8 py-4 bg-gradient-to-r from-green-600 to-emerald-600 hover:from-green-700 hover:to-emerald-700 text-white font-bold text-lg rounded-2xl transition-all duration-200 transform hover:scale-105 disabled:opacity-50 disabled:cursor-not-allowed disabled:transform-none shadow-xl hover:shadow-2xl"
      >
        <SparklesIcon v-if="!loading" class="w-6 h-6 mr-3" />
        <ArrowPathIcon v-else class="w-6 h-6 mr-3 animate-spin" />
        <span>{{ loading ? 'Creating Amazing Content...' : 'Generate Content' }}</span>
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';
import { SparklesIcon, ArrowPathIcon } from '@heroicons/vue/24/solid';
import { mockEngagementPredictions } from '@/data/mockData';
import type { ContentType, ContentTone } from '@/types';

interface Props {
  goal?: string;
  topic?: string;
  platforms?: string[];
}

interface Emits {
  (e: 'generate', data: {
    type: ContentType;
    tone: ContentTone;
    prompt: string;
    goal?: string;
  }): void;
  (e: 'content-generated', content: any): void;
}

const props = defineProps<Props>();
const emit = defineEmits<Emits>();

const loading = ref(false);
const selectedType = ref<ContentType>('text');
const selectedTone = ref<ContentTone>('professional');
const customPrompt = ref('');

const contentTypes = [
  {
    value: 'text' as ContentType,
    label: 'Text Post',
    icon: '✍️',
    engagement_rate: '4.2%'
  },
  {
    value: 'image' as ContentType,
    label: 'Image Post',
    icon: '📸',
    engagement_rate: '6.8%'
  },
  {
    value: 'video' as ContentType,
    label: 'Video',
    icon: '🎬',
    engagement_rate: '12.4%'
  },
  {
    value: 'carousel' as ContentType,
    label: 'Carousel',
    icon: '🎨',
    engagement_rate: '8.1%'
  }
];

const tones = [
  { value: 'professional' as ContentTone, label: 'Professional' },
  { value: 'casual' as ContentTone, label: 'Casual' },
  { value: 'humorous' as ContentTone, label: 'Humorous' },
  { value: 'inspiring' as ContentTone, label: 'Inspiring' },
  { value: 'educational' as ContentTone, label: 'Educational' }
];

const canGenerate = computed(() => {
  return props.topic && props.platforms && props.platforms.length > 0;
});

const getPromptPlaceholder = () => {
  if (!props.goal) return "Describe what you want to create...";
  
  const placeholders = {
    promote: "e.g., Announce our new AI feature that saves 50% time...",
    educate: "e.g., Explain how to use AI for content creation...",
    community: "e.g., Ask what's the biggest challenge in content creation...",
    entertain: "e.g., Share a funny story about content creation fails..."
  };
  
  return placeholders[props.goal as keyof typeof placeholders] || "Describe what you want to create...";
};

const getPrediction = (metric: string) => {
  const supportedTypes: (keyof typeof mockEngagementPredictions)[] = ['text', 'image', 'video'];
  const predictionType = supportedTypes.includes(selectedType.value as keyof typeof mockEngagementPredictions) 
    ? selectedType.value as keyof typeof mockEngagementPredictions
    : 'text';
    
  const predictions = mockEngagementPredictions[predictionType];
  const value = predictions[metric as keyof typeof predictions];
  
  if (typeof value === 'object' && 'avg' in value) {
    return metric === 'reach' ? `${(value.avg / 1000).toFixed(1)}K` : value.avg.toString();
  }
  
  return '0';
};

const generateContent = async () => {
  if (!canGenerate.value) return;
  
  loading.value = true;
  
  // Emit generate event with current settings
  emit('generate', {
    type: selectedType.value,
    tone: selectedTone.value,
    prompt: customPrompt.value,
    goal: props.goal
  });
  
  // Simulate generation time and create mock content
  setTimeout(() => {
    loading.value = false;
    
    // Create mock generated content
    const mockContent = {
      id: Date.now().toString(),
      type: selectedType.value,
      content: {
        text: `🚀 Generated ${selectedType.value} content for ${props.goal || 'general'} goal!\n\nThis is mock content that would be generated by AI based on:\n- Topic: ${props.topic || 'Your selected topic'}\n- Tone: ${selectedTone.value}\n- Custom prompt: ${customPrompt.value || 'Default content generation'}\n\nThis content is optimized for ${props.platforms?.join(', ') || 'your selected platforms'} and designed to achieve your ${props.goal || 'content'} objectives.\n\n#AI #ContentCreation #SocialMedia`
      },
      engagement_prediction: {
        likes: Math.floor(Math.random() * 500) + 100,
        shares: Math.floor(Math.random() * 100) + 20,
        comments: Math.floor(Math.random() * 50) + 10,
        reach: Math.floor(Math.random() * 10000) + 2000
      },
      goal: props.goal,
      platforms: props.platforms || []
    };
    
    // Emit the generated content
    emit('content-generated', mockContent);
  }, 2000);
};
</script>

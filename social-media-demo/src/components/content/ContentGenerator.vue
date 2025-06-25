<template>
  <div class="bg-gradient-to-r from-green-50 to-emerald-50 rounded-xl border border-green-200 p-6">
    <div class="flex items-center space-x-3 mb-4">
      <div class="w-8 h-8 bg-gradient-to-br from-green-500 to-emerald-600 rounded-lg flex items-center justify-center">
        <SparklesIcon class="w-4 h-4 text-white" />
      </div>
      <h3 class="text-lg font-bold text-gray-900">Generate Content</h3>
      <span class="text-sm text-green-600 font-medium">AI-powered creation</span>
    </div>

    <!-- Quick inputs -->
    <div class="space-y-4 mb-6">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Content Type</label>
          <select 
            v-model="selectedType"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent"
          >
            <option v-for="type in contentTypes" :key="type.value" :value="type.value">
              {{ type.icon }} {{ type.label }} ({{ type.engagement_rate }} engagement)
            </option>
          </select>
        </div>
        
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Tone</label>
          <select 
            v-model="selectedTone"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent"
          >
            <option v-for="tone in tones" :key="tone.value" :value="tone.value">
              {{ tone.label }}
            </option>
          </select>
        </div>
      </div>

      <!-- Custom prompt input -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">
          What do you want to create? 
          <span class="text-gray-500 font-normal">(optional - we'll generate based on your topic)</span>
        </label>
        <textarea
          v-model="customPrompt"
          :placeholder="getPromptPlaceholder()"
          rows="3"
          class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent text-sm"
        ></textarea>
      </div>
    </div>

    <!-- Success prediction -->
    <div class="bg-white rounded-lg p-4 border border-green-200 mb-6">
      <h4 class="font-medium text-gray-900 mb-3">📊 Predicted Performance</h4>
      <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="text-center">
          <div class="text-lg font-bold text-blue-600">{{ getPrediction('likes') }}</div>
          <div class="text-xs text-gray-600">Likes</div>
        </div>
        <div class="text-center">
          <div class="text-lg font-bold text-green-600">{{ getPrediction('shares') }}</div>
          <div class="text-xs text-gray-600">Shares</div>
        </div>
        <div class="text-center">
          <div class="text-lg font-bold text-purple-600">{{ getPrediction('comments') }}</div>
          <div class="text-xs text-gray-600">Comments</div>
        </div>
        <div class="text-center">
          <div class="text-lg font-bold text-orange-600">{{ getPrediction('reach') }}</div>
          <div class="text-xs text-gray-600">Reach</div>
        </div>
      </div>
    </div>

    <!-- Generate button -->
    <div class="flex justify-center">
      <button
        @click="generateContent"
        :disabled="loading || !canGenerate"
        class="inline-flex items-center px-8 py-3 bg-gradient-to-r from-green-600 to-emerald-600 hover:from-green-700 hover:to-emerald-700 text-white font-semibold rounded-xl transition-all duration-200 transform hover:scale-105 disabled:opacity-50 disabled:cursor-not-allowed disabled:transform-none shadow-lg"
      >
        <SparklesIcon v-if="!loading" class="w-5 h-5 mr-2" />
        <ArrowPathIcon v-else class="w-5 h-5 mr-2 animate-spin" />
        <span>{{ loading ? 'Creating...' : 'Generate Content' }}</span>
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
  
  // Simulate generation time
  setTimeout(() => {
    loading.value = false;
  }, 2000);
};
</script>

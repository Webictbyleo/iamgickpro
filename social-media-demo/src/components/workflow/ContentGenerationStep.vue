<template>
  <div class="space-y-6">
    <!-- Context Summary -->
    <div v-if="workflowStore.selectedTopic && workflowStore.selectedAccounts.length > 0" 
         class="bg-gradient-to-r from-blue-50 to-indigo-50 rounded-xl p-4 border border-blue-200">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="bg-white rounded-lg p-3 border border-blue-100">
          <span class="font-medium text-gray-900">Topic:</span>
          <p class="text-gray-700 mt-1">{{ workflowStore.selectedTopic?.title }}</p>
        </div>
        <div class="bg-white rounded-lg p-3 border border-blue-100">
          <span class="font-medium text-gray-900">Platforms ({{ workflowStore.selectedAccounts.length }}):</span>
          <div class="mt-1 space-y-1">
            <div 
              v-for="account in workflowStore.selectedAccounts" 
              :key="account.id"
              class="text-gray-700 text-sm"
            >
              {{ account.display_name }} ({{ getPlatformName(account.platform) }})
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Content Type Selection -->
    <div class="bg-white rounded-xl border border-gray-200 p-6">
      <div class="flex items-center space-x-2 mb-6">
        <SparklesIcon class="w-6 h-6 text-purple-600" />
        <h3 class="text-xl font-bold text-gray-900">What do you want to create?</h3>
      </div>
      
      <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <button
          v-for="contentType in availableContentTypes"
          :key="contentType.value"
          @click="prompt.primary_type = contentType.value"
          class="group relative p-4 rounded-xl border-2 transition-all duration-200 hover:scale-105"
          :class="{
            'border-purple-300 bg-purple-50 ring-2 ring-purple-200': prompt.primary_type === contentType.value,
            'border-gray-200 bg-white hover:border-gray-300 hover:shadow-md': prompt.primary_type !== contentType.value
          }"
        >
          <div class="text-center">
            <div class="text-3xl mb-2">{{ contentType.icon }}</div>
            <h4 class="font-semibold text-gray-900 mb-1">{{ contentType.label }}</h4>
            <p class="text-xs text-gray-600">{{ contentType.description }}</p>
          </div>
          <div 
            v-if="prompt.primary_type === contentType.value"
            class="absolute top-2 right-2 w-5 h-5 bg-purple-600 rounded-full flex items-center justify-center"
          >
            <CheckIcon class="w-3 h-3 text-white" />
          </div>
        </button>
      </div>

      <!-- Additional Content Types -->
      <div v-if="prompt.primary_type" class="border-t border-gray-200 pt-6">
        <h4 class="font-medium text-gray-900 mb-3">Also generate (optional):</h4>
        <div class="flex flex-wrap gap-2">
          <button
            v-for="additionalType in getAdditionalContentTypes()"
            :key="additionalType.value"
            @click="toggleAdditionalContentType(additionalType.value)"
            class="px-4 py-2 rounded-lg border transition-all duration-200"
            :class="{
              'border-purple-300 bg-purple-100 text-purple-700': prompt.content_types.includes(additionalType.value),
              'border-gray-300 bg-white text-gray-700 hover:border-gray-400': !prompt.content_types.includes(additionalType.value)
            }"
          >
            <span class="mr-2">{{ additionalType.icon }}</span>
            {{ additionalType.label }}
          </button>
        </div>
      </div>
    </div>

    <!-- Content Configuration -->
    <div v-if="prompt.primary_type" class="grid grid-cols-1 xl:grid-cols-2 gap-6">
      <!-- Primary Configuration -->
      <div class="bg-white rounded-xl border border-gray-200 p-6">
        <div class="flex items-center space-x-2 mb-6">
          <CogIcon class="w-5 h-5 text-gray-600" />
          <h3 class="text-lg font-bold text-gray-900">Content Settings</h3>
        </div>

        <div class="space-y-6">
          <!-- Tone Selection -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-3">Tone & Voice</label>
            <Listbox v-model="prompt.tone">
              <div class="relative">
                <ListboxButton class="relative w-full cursor-pointer rounded-lg bg-white py-3 pl-4 pr-10 text-left border border-gray-300 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                  <span class="block truncate font-medium">{{ getToneLabel(prompt.tone) }}</span>
                  <span class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3">
                    <ChevronUpDownIcon class="h-5 w-5 text-gray-400" />
                  </span>
                </ListboxButton>
                <ListboxOptions class="absolute z-10 mt-1 max-h-60 w-full overflow-auto rounded-md bg-white py-1 shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none">
                  <ListboxOption
                    v-for="option in toneOptions"
                    :key="option.value"
                    :value="option.value"
                    class="relative cursor-pointer select-none py-2 pl-4 pr-9 ui-active:bg-purple-100 ui-active:text-purple-900 ui-not-active:text-gray-900"
                  >
                    <span class="block truncate ui-selected:font-medium ui-not-selected:font-normal">
                      {{ option.label }}
                    </span>
                    <span class="absolute inset-y-0 right-0 flex items-center pr-3 text-purple-600 ui-not-selected:hidden">
                      <CheckIcon class="h-5 w-5" />
                    </span>
                  </ListboxOption>
                </ListboxOptions>
              </div>
            </Listbox>
          </div>

          <!-- Style Selection -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-3">Content Style</label>
            <Listbox v-model="prompt.style">
              <div class="relative">
                <ListboxButton class="relative w-full cursor-pointer rounded-lg bg-white py-3 pl-4 pr-10 text-left border border-gray-300 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                  <span class="block truncate font-medium">{{ getStyleLabel(prompt.style) }}</span>
                  <span class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3">
                    <ChevronUpDownIcon class="h-5 w-5 text-gray-400" />
                  </span>
                </ListboxButton>
                <ListboxOptions class="absolute z-10 mt-1 max-h-60 w-full overflow-auto rounded-md bg-white py-1 shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none">
                  <ListboxOption
                    v-for="option in styleOptions"
                    :key="option.value"
                    :value="option.value"
                    class="relative cursor-pointer select-none py-2 pl-4 pr-9 ui-active:bg-purple-100 ui-active:text-purple-900 ui-not-active:text-gray-900"
                  >
                    <span class="block truncate ui-selected:font-medium ui-not-selected:font-normal">
                      {{ option.label }}
                    </span>
                    <span class="absolute inset-y-0 right-0 flex items-center pr-3 text-purple-600 ui-not-selected:hidden">
                      <CheckIcon class="h-5 w-5" />
                    </span>
                  </ListboxOption>
                </ListboxOptions>
              </div>
            </Listbox>
          </div>

          <!-- Length Selection -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-3">Content Length</label>
            <Listbox v-model="prompt.length">
              <div class="relative">
                <ListboxButton class="relative w-full cursor-pointer rounded-lg bg-white py-3 pl-4 pr-10 text-left border border-gray-300 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                  <span class="block truncate font-medium">{{ getLengthLabel(prompt.length) }}</span>
                  <span class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3">
                    <ChevronUpDownIcon class="h-5 w-5 text-gray-400" />
                  </span>
                </ListboxButton>
                <ListboxOptions class="absolute z-10 mt-1 max-h-60 w-full overflow-auto rounded-md bg-white py-1 shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none">
                  <ListboxOption
                    v-for="option in lengthOptions"
                    :key="option.value"
                    :value="option.value"
                    class="relative cursor-pointer select-none py-2 pl-4 pr-9 ui-active:bg-purple-100 ui-active:text-purple-900 ui-not-active:text-gray-900"
                  >
                    <span class="block truncate ui-selected:font-medium ui-not-selected:font-normal">
                      {{ option.label }}
                    </span>
                    <span class="absolute inset-y-0 right-0 flex items-center pr-3 text-purple-600 ui-not-selected:hidden">
                      <CheckIcon class="h-5 w-5" />
                    </span>
                  </ListboxOption>
                </ListboxOptions>
              </div>
            </Listbox>
          </div>

          <!-- Content Features -->
          <div class="space-y-3">
            <label class="block text-sm font-medium text-gray-700">Include:</label>
            <div class="space-y-2">
              <label class="flex items-center">
                <input 
                  v-model="prompt.hashtags"
                  type="checkbox" 
                  class="rounded border-gray-300 text-purple-600 focus:ring-purple-500"
                />
                <span class="ml-2 text-sm text-gray-700">Hashtags</span>
              </label>
              <label class="flex items-center">
                <input 
                  v-model="prompt.mentions"
                  type="checkbox" 
                  class="rounded border-gray-300 text-purple-600 focus:ring-purple-500"
                />
                <span class="ml-2 text-sm text-gray-700">Mentions (@)</span>
              </label>
              <label class="flex items-center">
                <input 
                  v-model="prompt.call_to_action"
                  type="checkbox" 
                  class="rounded border-gray-300 text-purple-600 focus:ring-purple-500"
                />
                <span class="ml-2 text-sm text-gray-700">Call-to-Action</span>
              </label>
            </div>
          </div>
        </div>
      </div>

      <!-- Media-Specific Configuration -->
      <div v-if="isMediaContent()" class="bg-white rounded-xl border border-gray-200 p-6">
        <div class="flex items-center space-x-2 mb-6">
          <PhotoIcon class="w-5 h-5 text-gray-600" />
          <h3 class="text-lg font-bold text-gray-900">Visual Settings</h3>
        </div>

        <div class="space-y-6">
          <!-- Visual Style for Images/Videos -->
          <div v-if="['image', 'video', 'reel', 'story'].includes(prompt.primary_type)">
            <label class="block text-sm font-medium text-gray-700 mb-3">Visual Style</label>
            <div class="grid grid-cols-2 gap-3">
              <button
                v-for="style in visualStyleOptions"
                :key="style.value"
                @click="prompt.visual_style = style.value"
                class="p-3 rounded-lg border-2 text-left transition-all duration-200"
                :class="{
                  'border-purple-300 bg-purple-50': prompt.visual_style === style.value,
                  'border-gray-200 hover:border-gray-300': prompt.visual_style !== style.value
                }"
              >
                <div class="font-medium text-sm">{{ style.label }}</div>
                <div class="text-xs text-gray-600">{{ style.description }}</div>
              </button>
            </div>
          </div>

          <!-- Aspect Ratio -->
          <div v-if="['image', 'video'].includes(prompt.primary_type)">
            <label class="block text-sm font-medium text-gray-700 mb-3">Aspect Ratio</label>
            <div class="grid grid-cols-3 gap-3">
              <button
                v-for="ratio in aspectRatioOptions"
                :key="ratio.value"
                @click="prompt.aspect_ratio = ratio.value"
                class="p-3 rounded-lg border-2 text-center transition-all duration-200"
                :class="{
                  'border-purple-300 bg-purple-50': prompt.aspect_ratio === ratio.value,
                  'border-gray-200 hover:border-gray-300': prompt.aspect_ratio !== ratio.value
                }"
              >
                <div class="font-medium text-sm">{{ ratio.label }}</div>
                <div class="text-xs text-gray-600">{{ ratio.description }}</div>
              </button>
            </div>
          </div>

          <!-- Video Duration -->
          <div v-if="['video', 'reel'].includes(prompt.primary_type)">
            <label class="block text-sm font-medium text-gray-700 mb-3">Duration</label>
            <input
              v-model.number="prompt.video_duration"
              type="range"
              min="15"
              max="180"
              step="15"
              class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer"
            />
            <div class="flex justify-between text-sm text-gray-600 mt-1">
              <span>15s</span>
              <span class="font-medium">{{ prompt.video_duration }}s</span>
              <span>3min</span>
            </div>
          </div>

          <!-- Color Scheme -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-3">Color Scheme (optional)</label>
            <input
              v-model="prompt.color_scheme"
              type="text"
              placeholder="e.g., vibrant blues and whites, warm autumn tones"
              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
            />
          </div>
        </div>
      </div>
    </div>

    <!-- Custom Instructions -->
    <div v-if="prompt.primary_type" class="bg-white rounded-xl border border-gray-200 p-6">
      <div class="flex items-center space-x-2 mb-4">
        <DocumentTextIcon class="w-5 h-5 text-gray-600" />
        <h3 class="text-lg font-bold text-gray-900">Additional Instructions</h3>
      </div>
      <textarea
        v-model="prompt.custom_instructions"
        placeholder="Add any specific requirements, brand guidelines, or creative direction..."
        rows="4"
        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
      ></textarea>
    </div>

    <!-- Generate Button -->
    <div v-if="prompt.primary_type" class="flex justify-center">
      <button
        @click="generateContent"
        :disabled="loading.generate"
        class="inline-flex items-center px-8 py-4 bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-700 hover:to-indigo-700 text-white font-semibold rounded-xl transition-all duration-200 transform hover:scale-105 disabled:opacity-50 disabled:cursor-not-allowed disabled:transform-none shadow-lg"
      >
        <SparklesIcon v-if="!loading.generate" class="w-5 h-5 mr-2" />
        <ArrowPathIcon v-else class="w-5 h-5 mr-2 animate-spin" />
        <span>{{ loading.generate ? 'Creating Magic...' : 'Generate Content' }}</span>
      </button>
    </div>

    <!-- Generated Content Display -->
    <div v-if="workflowStore.generatedContent" class="space-y-6">
      <div class="bg-gradient-to-r from-green-50 to-emerald-50 rounded-xl border border-green-200 p-6">
        <div class="flex items-center justify-between mb-4">
          <div class="flex items-center space-x-2">
            <CheckCircleIcon class="w-6 h-6 text-green-600" />
            <h3 class="text-lg font-bold text-gray-900">Content Generated!</h3>
          </div>
          <button
            @click="regenerateContent"
            :disabled="loading.generate"
            class="inline-flex items-center px-4 py-2 bg-white border border-green-300 rounded-lg text-green-700 hover:bg-green-50 transition-colors"
          >
            <ArrowPathIcon v-if="!loading.generate" class="w-4 h-4 mr-2" />
            <ArrowPathIcon v-else class="w-4 h-4 mr-2 animate-spin" />
            <span>{{ loading.generate ? 'Regenerating...' : 'Regenerate' }}</span>
          </button>
        </div>
        
        <!-- Enhanced Content Preview -->
        <div class="bg-gray-50 rounded-lg p-4">
          <div v-if="workflowStore.generatedContent.content.text" class="space-y-3">
            <div class="flex items-center justify-between">
              <h4 class="font-medium text-gray-900">Generated Content</h4>
              <span class="text-sm text-gray-500">{{ workflowStore.generatedContent.content.text.length }} characters</span>
            </div>
            <div class="prose prose-sm max-w-none text-gray-800 leading-relaxed bg-white p-4 rounded border">
              {{ workflowStore.generatedContent.content.text }}
            </div>
            <div v-if="workflowStore.generatedContent.content.hashtags" class="flex flex-wrap gap-2">
              <span 
                v-for="hashtag in workflowStore.generatedContent.content.hashtags" 
                :key="hashtag"
                class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800"
              >
                #{{ hashtag }}
              </span>
            </div>
          </div>
          
          <div v-if="workflowStore.generatedContent.content.media_url" class="mt-4">
            <div class="aspect-square bg-gray-100 rounded-lg flex items-center justify-center">
              <img 
                v-if="workflowStore.generatedContent.type === 'image'"
                :src="workflowStore.generatedContent.content.media_url" 
                alt="Generated content"
                class="max-w-full max-h-full object-cover rounded"
              />
              <video 
                v-else-if="['video', 'reel'].includes(workflowStore.generatedContent.type)"
                :src="workflowStore.generatedContent.content.media_url" 
                controls
                class="max-w-full max-h-full rounded"
              >
              </video>
              <div v-else class="text-center">
                <PhotoIcon class="w-16 h-16 text-gray-400 mx-auto mb-2" />
                <p class="text-gray-600">Media content preview</p>
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
import { contentApi } from '@/services/api';
import type { 
  ContentPrompt, 
  ContentType, 
  ContentTone, 
  ContentStyle, 
  ContentLength,
  SocialPlatform 
} from '@/types';

import { 
  SparklesIcon, 
  ArrowPathIcon, 
  CheckIcon, 
  CheckCircleIcon,
  CogIcon,
  PhotoIcon,
  DocumentTextIcon,
  ChevronUpDownIcon
} from '@heroicons/vue/24/solid';

import { Listbox, ListboxButton, ListboxOptions, ListboxOption } from '@headlessui/vue';

const workflowStore = useWorkflowStore();
const uiStore = useUiStore();

const loading = ref({
  generate: false
});

// Initialize prompt with default values
const prompt = ref<ContentPrompt>({
  topic_id: '',
  platforms: [],
  content_types: [],
  primary_type: 'text',
  tone: 'professional',
  style: 'informative',
  length: 'medium',
  hashtags: true,
  mentions: false,
  call_to_action: true,
  visual_style: 'professional',
  aspect_ratio: '1:1',
  video_duration: 30,
  audio_duration: 60,
  custom_instructions: ''
});

// Content Type Options
const availableContentTypes = computed(() => [
  {
    value: 'text' as ContentType,
    label: 'Text Post',
    icon: '✍️',
    description: 'Engaging text content with hashtags'
  },
  {
    value: 'image' as ContentType,
    label: 'AI Image',
    icon: '🎨',
    description: 'AI-generated visual content'
  },
  {
    value: 'video' as ContentType,
    label: 'Video',
    icon: '🎬',
    description: 'AI-generated video content'
  },
  {
    value: 'carousel' as ContentType,
    label: 'Carousel',
    icon: '📸',
    description: 'Multi-slide visual story'
  },
  {
    value: 'reel' as ContentType,
    label: 'Short Video',
    icon: '🎥',
    description: 'Short-form vertical video'
  },
  {
    value: 'story' as ContentType,
    label: 'Story',
    icon: '📱',
    description: 'Temporary story content'
  },
  {
    value: 'audio' as ContentType,
    label: 'Audio',
    icon: '🎙️',
    description: 'AI-generated audio content'
  },
  {
    value: 'mixed' as ContentType,
    label: 'Mixed Media',
    icon: '🎭',
    description: 'Combination of multiple formats'
  }
]);

const getAdditionalContentTypes = () => {
  return availableContentTypes.value.filter(type => 
    type.value !== prompt.value.primary_type && 
    !prompt.value.content_types.includes(type.value)
  );
};

const toggleAdditionalContentType = (type: ContentType) => {
  const index = prompt.value.content_types.indexOf(type);
  if (index > -1) {
    prompt.value.content_types.splice(index, 1);
  } else {
    prompt.value.content_types.push(type);
  }
};

// Tone Options
const toneOptions = [
  { value: 'professional' as ContentTone, label: 'Professional & Polished' },
  { value: 'casual' as ContentTone, label: 'Casual & Friendly' },
  { value: 'humorous' as ContentTone, label: 'Humorous & Witty' },
  { value: 'inspiring' as ContentTone, label: 'Inspiring & Motivational' },
  { value: 'educational' as ContentTone, label: 'Educational & Informative' },
  { value: 'conversational' as ContentTone, label: 'Conversational & Personal' },
  { value: 'authoritative' as ContentTone, label: 'Authoritative & Expert' }
];

// Style Options
const styleOptions = [
  { value: 'informative' as ContentStyle, label: 'Informative & Educational' },
  { value: 'engaging' as ContentStyle, label: 'Engaging & Interactive' },
  { value: 'promotional' as ContentStyle, label: 'Promotional & Sales-focused' },
  { value: 'storytelling' as ContentStyle, label: 'Storytelling & Narrative' },
  { value: 'tutorial' as ContentStyle, label: 'Tutorial & How-to' },
  { value: 'behind-scenes' as ContentStyle, label: 'Behind-the-scenes' },
  { value: 'testimonial' as ContentStyle, label: 'Testimonial & Review' },
  { value: 'announcement' as ContentStyle, label: 'Announcement & News' }
];

// Length Options
const lengthOptions = [
  { value: 'micro' as ContentLength, label: 'Micro (Tweet-sized)' },
  { value: 'short' as ContentLength, label: 'Short & Punchy' },
  { value: 'medium' as ContentLength, label: 'Medium Length' },
  { value: 'long' as ContentLength, label: 'Long-form' },
  { value: 'thread' as ContentLength, label: 'Thread/Series' }
];

// Visual Style Options
const visualStyleOptions = [
  { value: 'realistic' as const, label: 'Realistic', description: 'Photo-realistic style' },
  { value: 'artistic' as const, label: 'Artistic', description: 'Creative and artistic' },
  { value: 'minimalist' as const, label: 'Minimalist', description: 'Clean and simple' },
  { value: 'vibrant' as const, label: 'Vibrant', description: 'Bold and colorful' },
  { value: 'professional' as const, label: 'Professional', description: 'Corporate style' },
  { value: 'creative' as const, label: 'Creative', description: 'Unique and innovative' }
];

// Aspect Ratio Options
const aspectRatioOptions = [
  { value: '1:1' as const, label: '1:1', description: 'Square' },
  { value: '16:9' as const, label: '16:9', description: 'Landscape' },
  { value: '9:16' as const, label: '9:16', description: 'Portrait' },
  { value: '4:5' as const, label: '4:5', description: 'Instagram' },
  { value: 'custom' as const, label: 'Custom', description: 'Custom size' }
];

// Helper functions
const isMediaContent = () => {
  return ['image', 'video', 'reel', 'story', 'carousel'].includes(prompt.value.primary_type);
};

const getToneLabel = (value: ContentTone) => {
  return toneOptions.find(option => option.value === value)?.label || value;
};

const getStyleLabel = (value: ContentStyle) => {
  return styleOptions.find(option => option.value === value)?.label || value;
};

const getLengthLabel = (value: ContentLength) => {
  return lengthOptions.find(option => option.value === value)?.label || value;
};

const getPlatformName = (platform: SocialPlatform) => {
  const names: Record<SocialPlatform, string> = {
    twitter: 'Twitter',
    linkedin: 'LinkedIn',
    instagram: 'Instagram',
    facebook: 'Facebook',
    tiktok: 'TikTok',
    youtube: 'YouTube'
  };
  return names[platform];
};

// Content generation
const generateContent = async () => {
  if (!workflowStore.selectedTopic || workflowStore.selectedAccounts.length === 0) {
    uiStore.addNotification({
      type: 'error',
      title: 'Missing Information',
      message: 'Please select a topic and at least one platform first'
    });
    return;
  }

  // Update prompt with current selection
  prompt.value.topic_id = workflowStore.selectedTopic.id;
  prompt.value.platforms = workflowStore.selectedAccounts.map(a => a.platform);
  
  // Add primary type to content types if not already included
  if (!prompt.value.content_types.includes(prompt.value.primary_type)) {
    prompt.value.content_types.unshift(prompt.value.primary_type);
  }

  loading.value.generate = true;
  try {
    const response = await contentApi.generateContent(prompt.value);
    if (response.success) {
      workflowStore.setGeneratedContent(response.data);
      workflowStore.setContentPrompt(prompt.value);
      
      uiStore.addNotification({
        type: 'success',
        title: 'Content Generated',
        message: `Successfully generated ${prompt.value.primary_type} content!`
      });
    }
  } catch (error) {
    console.error('Failed to generate content:', error);
    uiStore.addNotification({
      type: 'error',
      title: 'Generation Failed',
      message: 'Failed to generate content. Please try again.'
    });
  } finally {
    loading.value.generate = false;
  }
};

const regenerateContent = () => {
  generateContent();
};

onMounted(() => {
  if (workflowStore.selectedAccounts.length > 0) {
    prompt.value.platforms = workflowStore.selectedAccounts.map(a => a.platform);
  }
  if (workflowStore.selectedTopic) {
    prompt.value.topic_id = workflowStore.selectedTopic.id;
  }
});
</script>

<template>
  <div class="space-y-6">
    <!-- Content Type Selection -->
    <div class="space-y-6">
      <div class="space-y-3">
        <label class="block text-sm font-semibold text-gray-800">Content Type</label>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
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
            <div class="flex flex-col items-center space-y-2">
              <span class="text-2xl">{{ type.icon }}</span>
              <div class="text-center">
                <div class="font-medium text-sm">{{ type.label }}</div>
                <div class="text-xs text-gray-500">{{ type.engagement_rate }} avg</div>
              </div>
            </div>
          </button>
        </div>
      </div>

      <!-- Media Attachments for specific content types -->
      <div v-if="needsMediaAttachment" class="space-y-3">
        <label class="block text-sm font-semibold text-gray-800">
          {{ getAttachmentLabel() }}
          <span class="text-gray-500 font-normal">(optional - AI will generate if not provided)</span>
        </label>
        <div class="border-2 border-dashed border-gray-300 rounded-xl p-6 text-center hover:border-green-400 transition-colors">
          <input
            ref="fileInput"
            type="file"
            :accept="getAcceptedFileTypes()"
            :multiple="selectedType === 'carousel'"
            @change="handleFileUpload"
            class="hidden"
          >
          <div v-if="!attachedFiles.length" class="space-y-3">
            <div class="text-4xl text-gray-400">
              {{ selectedType === 'video' ? '🎬' : '📸' }}
            </div>
            <div>
              <button
                @click="fileInput?.click()"
                class="text-green-600 hover:text-green-700 font-medium"
              >
                {{ getUploadButtonText() }}
              </button>
              <p class="text-xs text-gray-500 mt-1">{{ getFileRequirements() }}</p>
            </div>
          </div>
          <div v-else class="space-y-3">
            <div class="flex flex-wrap gap-2 justify-center">
              <div 
                v-for="(file, index) in attachedFiles" 
                :key="index"
                class="relative bg-gray-100 rounded-lg p-2 flex items-center space-x-2"
              >
                <span class="text-sm font-medium">{{ file.name }}</span>
                <button
                  @click="removeFile(index)"
                  class="text-red-500 hover:text-red-700 text-xs"
                >
                  ✕
                </button>
              </div>
            </div>
            <button
              v-if="selectedType === 'carousel'"
              @click="fileInput?.click()"
              class="text-green-600 hover:text-green-700 font-medium text-sm"
            >
              + Add More Images
            </button>
          </div>
        </div>
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
import type { ContentType } from '@/types';

interface Props {
  goal?: string;
  topic?: string;
  platforms?: string[];
}

interface Emits {
  (e: 'generate', data: {
    type: ContentType;
    prompt: string;
    goal?: string;
    attachments?: File[];
  }): void;
  (e: 'content-generated', content: any): void;
}

const props = defineProps<Props>();
const emit = defineEmits<Emits>();

const loading = ref(false);
const selectedType = ref<ContentType>('text');
const customPrompt = ref('');
const attachedFiles = ref<File[]>([]);
const fileInput = ref<HTMLInputElement>();

// Computed properties for media attachments
const needsMediaAttachment = computed(() => {
  return ['image', 'video', 'carousel'].includes(selectedType.value);
});

const getAttachmentLabel = () => {
  switch (selectedType.value) {
    case 'image': return 'Upload Image';
    case 'video': return 'Upload Video';
    case 'carousel': return 'Upload Images (2-10 images)';
    default: return 'Upload Media';
  }
};

const getAcceptedFileTypes = () => {
  switch (selectedType.value) {
    case 'image':
    case 'carousel':
      return 'image/*';
    case 'video':
      return 'video/*';
    default:
      return '*/*';
  }
};

const getUploadButtonText = () => {
  switch (selectedType.value) {
    case 'image': return 'Click to upload image';
    case 'video': return 'Click to upload video';
    case 'carousel': return 'Click to upload images';
    default: return 'Click to upload';
  }
};

const getFileRequirements = () => {
  switch (selectedType.value) {
    case 'image': return 'JPG, PNG up to 10MB';
    case 'video': return 'MP4, MOV up to 100MB. AI will optimize for HD when possible.';
    case 'carousel': return 'JPG, PNG up to 10MB each. 2-10 images recommended.';
    default: return '';
  }
};

const handleFileUpload = (event: Event) => {
  const target = event.target as HTMLInputElement;
  const files = Array.from(target.files || []);
  
  if (selectedType.value === 'carousel') {
    // For carousel, append to existing files (max 10)
    const newFiles = [...attachedFiles.value, ...files].slice(0, 10);
    attachedFiles.value = newFiles;
  } else {
    // For single file types, replace existing
    attachedFiles.value = files.slice(0, 1);
  }
  
  // Reset input
  if (target) target.value = '';
};

const removeFile = (index: number) => {
  attachedFiles.value.splice(index, 1);
};

// Watch for content type changes to clear attachments
import { watch } from 'vue';
watch(selectedType, (newType, oldType) => {
  if (newType !== oldType) {
    attachedFiles.value = [];
  }
});

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
  
  // Emit generate event with current settings including attachments
  emit('generate', {
    type: selectedType.value,
    prompt: customPrompt.value,
    goal: props.goal,
    attachments: attachedFiles.value.length > 0 ? attachedFiles.value : undefined
  });
  
  // Simulate generation time and create mock content
  setTimeout(() => {
    loading.value = false;
    
    // Create mock generated content
    const mockContent = {
      id: Date.now().toString(),
      type: selectedType.value,
      content: {
        text: `🚀 Generated ${selectedType.value} content for ${props.goal || 'general'} goal!\n\nThis is mock content that would be generated by AI based on:\n- Topic: ${props.topic || 'Your selected topic'}\n- Type: ${selectedType.value}\n- Custom prompt: ${customPrompt.value || 'Default content generation'}\n- Attachments: ${attachedFiles.value.length > 0 ? `${attachedFiles.value.length} file(s)` : 'AI-generated media'}\n\nThis content is optimized for ${props.platforms?.join(', ') || 'your selected platforms'} and designed to achieve your ${props.goal || 'content'} objectives.\n\n#AI #ContentCreation #SocialMedia`,
        media: needsMediaAttachment.value && attachedFiles.value.length === 0 ? 
          [`AI-generated ${selectedType.value === 'carousel' ? 'images' : selectedType.value}`] : 
          attachedFiles.value.map(f => f.name)
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

<template>
  <div class="space-y-4">
    <!-- Content Type Indicator -->
    <div class="flex items-center justify-between">
      <div class="flex items-center space-x-2">
        <div class="flex items-center space-x-2 px-3 py-1 bg-purple-100 text-purple-700 rounded-full text-sm font-medium">
          <span>{{ getContentTypeIcon(content.type) }}</span>
          <span>{{ getContentTypeLabel(content.type) }}</span>
        </div>
        <div v-if="content.status === 'generating'" class="flex items-center space-x-2 px-3 py-1 bg-orange-100 text-orange-700 rounded-full text-sm">
          <div class="w-3 h-3 border-2 border-orange-600 border-t-transparent rounded-full animate-spin"></div>
          <span>Generating...</span>
        </div>
        <div v-else-if="content.status === 'completed'" class="flex items-center space-x-2 px-3 py-1 bg-green-100 text-green-700 rounded-full text-sm">
          <CheckCircleIcon class="w-4 h-4" />
          <span>Complete</span>
        </div>
      </div>
      
      <!-- Action Buttons -->
      <div class="flex items-center space-x-2">
        <button 
          @click="$emit('copy', content)"
          class="p-2 text-gray-600 hover:text-gray-800 hover:bg-gray-100 rounded-lg transition-colors"
          title="Copy content"
        >
          <ClipboardDocumentIcon class="w-4 h-4" />
        </button>
        <button 
          @click="$emit('edit', content)"
          class="p-2 text-gray-600 hover:text-gray-800 hover:bg-gray-100 rounded-lg transition-colors"
          title="Edit content"
        >
          <PencilIcon class="w-4 h-4" />
        </button>
        <button 
          @click="$emit('download', content)"
          class="p-2 text-gray-600 hover:text-gray-800 hover:bg-gray-100 rounded-lg transition-colors"
          title="Download content"
        >
          <ArrowDownTrayIcon class="w-4 h-4" />
        </button>
      </div>
    </div>

    <!-- Content Preview -->
    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
      <!-- Text Content -->
      <div v-if="content.content.text" class="p-4">
        <div class="flex items-center justify-between mb-3">
          <h4 class="font-medium text-gray-900">Generated Text</h4>
          <span class="text-sm text-gray-500">{{ content.content.text.length }} characters</span>
        </div>
        
        <div class="relative">
          <div 
            class="prose prose-sm max-w-none text-gray-800 leading-relaxed"
            :class="{ 'line-clamp-4': !expanded.text }"
          >
            {{ content.content.text }}
          </div>
          
          <button
            v-if="content.content.text.length > 200"
            @click="expanded.text = !expanded.text"
            class="mt-2 text-sm text-purple-600 hover:text-purple-700 font-medium"
          >
            {{ expanded.text ? 'Show less' : 'Read more' }}
          </button>
        </div>

        <!-- Hashtags -->
        <div v-if="content.content.hashtags && content.content.hashtags.length > 0" class="mt-4">
          <div class="flex flex-wrap gap-2">
            <span 
              v-for="hashtag in content.content.hashtags" 
              :key="hashtag"
              class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800"
            >
              #{{ hashtag }}
            </span>
          </div>
        </div>

        <!-- Mentions -->
        <div v-if="content.content.mentions && content.content.mentions.length > 0" class="mt-2">
          <div class="flex flex-wrap gap-2">
            <span 
              v-for="mention in content.content.mentions" 
              :key="mention"
              class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800"
            >
              @{{ mention }}
            </span>
          </div>
        </div>
      </div>

      <!-- Media Content -->
      <div v-if="content.content.media_url" class="relative">
        <!-- Image -->
        <div v-if="content.type === 'image'" class="aspect-square bg-gray-100 flex items-center justify-center">
          <img 
            v-if="content.content.media_url"
            :src="content.content.media_url" 
            :alt="content.content.caption || 'Generated image'"
            class="max-w-full max-h-full object-cover rounded"
          />
          <div v-else class="text-center">
            <PhotoIcon class="w-16 h-16 text-gray-400 mx-auto mb-2" />
            <p class="text-gray-600">Image generating...</p>
          </div>
        </div>

        <!-- Video -->
        <div v-else-if="['video', 'reel'].includes(content.type)" class="aspect-video bg-gray-100 flex items-center justify-center">
          <video 
            v-if="content.content.media_url"
            :src="content.content.media_url" 
            controls
            class="max-w-full max-h-full rounded"
          >
            Your browser does not support the video tag.
          </video>
          <div v-else class="text-center">
            <FilmIcon class="w-16 h-16 text-gray-400 mx-auto mb-2" />
            <p class="text-gray-600">Video generating...</p>
            <div v-if="content.progress" class="mt-2 w-48 bg-gray-200 rounded-full h-2">
              <div 
                class="bg-purple-600 h-2 rounded-full transition-all duration-300"
                :style="{ width: `${content.progress}%` }"
              ></div>
            </div>
          </div>
        </div>

        <!-- Carousel -->
        <div v-else-if="content.type === 'carousel'" class="bg-gray-100">
          <div v-if="content.content.media_urls && content.content.media_urls.length > 0" class="relative">
            <div class="flex overflow-x-auto space-x-2 p-4">
              <div 
                v-for="(url, index) in content.content.media_urls" 
                :key="index"
                class="flex-shrink-0 w-64 h-64 bg-white rounded-lg overflow-hidden border"
              >
                <img :src="url" :alt="`Slide ${index + 1}`" class="w-full h-full object-cover" />
              </div>
            </div>
            <div class="absolute bottom-2 left-1/2 transform -translate-x-1/2 bg-black bg-opacity-50 text-white px-2 py-1 rounded text-sm">
              {{ content.content.media_urls.length }} slides
            </div>
          </div>
          <div v-else class="aspect-square flex items-center justify-center">
            <div class="text-center">
              <PhotoIcon class="w-16 h-16 text-gray-400 mx-auto mb-2" />
              <p class="text-gray-600">Carousel generating...</p>
            </div>
          </div>
        </div>

        <!-- Audio -->
        <div v-else-if="content.type === 'audio'" class="p-6 bg-gradient-to-r from-purple-100 to-pink-100">
          <div v-if="content.content.media_url" class="text-center">
            <SpeakerWaveIcon class="w-16 h-16 text-purple-600 mx-auto mb-4" />
            <audio controls class="w-full">
              <source :src="content.content.media_url" type="audio/mpeg">
              Your browser does not support the audio element.
            </audio>
            <div v-if="content.content.duration" class="mt-2 text-sm text-gray-600">
              Duration: {{ formatDuration(content.content.duration) }}
            </div>
          </div>
          <div v-else class="text-center">
            <SpeakerWaveIcon class="w-16 h-16 text-gray-400 mx-auto mb-2" />
            <p class="text-gray-600">Audio generating...</p>
          </div>
        </div>

        <!-- Caption for media -->
        <div v-if="content.content.caption" class="p-4 border-t border-gray-200">
          <p class="text-sm text-gray-700">{{ content.content.caption }}</p>
        </div>
      </div>
    </div>

    <!-- Platform Compatibility -->
    <div v-if="platforms.length > 1" class="bg-gray-50 rounded-lg p-4">
      <h4 class="font-medium text-gray-900 mb-3">Platform Compatibility</h4>
      <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
        <div 
          v-for="platform in platforms" 
          :key="platform"
          class="flex items-center space-x-2 p-2 bg-white rounded-lg border"
        >
          <span class="text-lg">{{ getPlatformIcon(platform) }}</span>
          <span class="text-sm font-medium">{{ getPlatformName(platform) }}</span>
          <CheckCircleIcon class="w-4 h-4 text-green-500" />
        </div>
      </div>
    </div>

    <!-- Generation Metadata -->
    <div v-if="content.generation_time || content.model_used" class="flex items-center justify-between text-xs text-gray-500">
      <span v-if="content.generation_time">Generated in {{ content.generation_time }}s</span>
      <span v-if="content.model_used">{{ content.model_used }}</span>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import type { GeneratedContent, SocialPlatform, ContentType } from '@/types';
import { 
  CheckCircleIcon, 
  ClipboardDocumentIcon, 
  PencilIcon, 
  ArrowDownTrayIcon,
  PhotoIcon,
  FilmIcon,
  SpeakerWaveIcon
} from '@heroicons/vue/24/outline';

interface Props {
  content: GeneratedContent;
  platforms: SocialPlatform[];
}

interface Emits {
  (e: 'edit', content: GeneratedContent): void;
  (e: 'copy', content: GeneratedContent): void;
  (e: 'download', content: GeneratedContent): void;
}

defineProps<Props>();
defineEmits<Emits>();

const expanded = ref({
  text: false
});

const getContentTypeIcon = (type: ContentType) => {
  const icons = {
    text: '✍️',
    image: '🎨',
    video: '🎬',
    carousel: '📸',
    reel: '🎥',
    story: '📱',
    audio: '🎙️',
    mixed: '🎭'
  };
  return icons[type] || '📄';
};

const getContentTypeLabel = (type: ContentType) => {
  const labels = {
    text: 'Text Post',
    image: 'AI Image',
    video: 'Video',
    carousel: 'Carousel',
    reel: 'Short Video',
    story: 'Story',
    audio: 'Audio',
    mixed: 'Mixed Media'
  };
  return labels[type] || type;
};

const getPlatformIcon = (platform: SocialPlatform) => {
  const icons = {
    twitter: '🐦',
    linkedin: '💼',
    instagram: '📷',
    facebook: '👥',
    tiktok: '🎵',
    youtube: '📺'
  };
  return icons[platform];
};

const getPlatformName = (platform: SocialPlatform) => {
  const names = {
    twitter: 'Twitter',
    linkedin: 'LinkedIn',
    instagram: 'Instagram',
    facebook: 'Facebook',
    tiktok: 'TikTok',
    youtube: 'YouTube'
  };
  return names[platform];
};

const formatDuration = (seconds: number) => {
  const mins = Math.floor(seconds / 60);
  const secs = seconds % 60;
  return `${mins}:${secs.toString().padStart(2, '0')}`;
};
</script>

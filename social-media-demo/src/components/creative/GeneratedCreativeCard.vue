<template>
  <div class="bg-white border border-gray-200 rounded-lg overflow-hidden hover:shadow-md transition-shadow">
    <!-- Image Preview -->
    <div class="relative">
      <img
        :src="creative.image_url"
        :alt="`${creative.type} creative`"
        class="w-full h-48 object-cover"
      />
      
      <!-- Creative Type Badge -->
      <div class="absolute top-2 left-2">
        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-white bg-opacity-90 text-gray-800">
          {{ getCreativeTypeName(creative.type) }}
        </span>
      </div>
      
      <!-- Platform Badge -->
      <div class="absolute top-2 right-2">
        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-white bg-opacity-90 text-gray-800">
          {{ getPlatformIcon(creative.platform) }} {{ getPlatformName(creative.platform) }}
        </span>
      </div>
    </div>

    <!-- Content -->
    <div class="p-4">
      <div class="flex items-center justify-between mb-2">
        <h4 class="font-medium text-gray-900">
          {{ getCreativeTypeName(creative.type) }}
        </h4>
        <span class="text-xs text-gray-500">
          {{ formatDate(creative.created_at) }}
        </span>
      </div>
      
      <div class="text-sm text-gray-600 mb-3">
        {{ creative.dimensions.width }} × {{ creative.dimensions.height }}px
      </div>

      <!-- Actions -->
      <div class="flex space-x-2">
        <button
          @click="downloadCreative"
          class="flex-1 flex items-center justify-center space-x-1 px-3 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm rounded-md font-medium transition-colors"
        >
          <ArrowDownTrayIcon class="w-4 h-4" />
          <span>Download</span>
        </button>
        
        <button
          @click="previewCreative"
          class="flex-1 flex items-center justify-center space-x-1 px-3 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm rounded-md font-medium transition-colors"
        >
          <EyeIcon class="w-4 h-4" />
          <span>Preview</span>
        </button>
      </div>
    </div>
  </div>

  <!-- Preview Modal -->
  <div
    v-if="showPreview"
    class="fixed inset-0 bg-black bg-opacity-75 flex items-center justify-center z-50 p-4"
    @click="showPreview = false"
  >
    <div class="max-w-4xl max-h-full">
      <img
        :src="creative.image_url"
        :alt="`${creative.type} creative`"
        class="max-w-full max-h-full object-contain"
      />
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import type { GeneratedCreative, CreativeType, SocialPlatform } from '@/types';
import { ArrowDownTrayIcon, EyeIcon } from '@heroicons/vue/24/outline';

interface Props {
  creative: GeneratedCreative;
}

const props = defineProps<Props>();

const showPreview = ref(false);

const getCreativeTypeName = (type: CreativeType) => {
  const names: Record<CreativeType, string> = {
    thumbnail: 'Video Thumbnail',
    cover: 'Cover Image',
    story: 'Story Format',
    post: 'Social Post',
    banner: 'Banner',
    logo: 'Logo'
  };
  return names[type] || type;
};

const getPlatformIcon = (platform: SocialPlatform) => {
  const icons: Record<SocialPlatform, string> = {
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

const formatDate = (dateString: string) => {
  const date = new Date(dateString);
  return date.toLocaleDateString();
};

const downloadCreative = () => {
  // In a real app, this would trigger a download
  const link = document.createElement('a');
  link.href = props.creative.download_url;
  link.download = `${props.creative.type}-${props.creative.id}.jpg`;
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
};

const previewCreative = () => {
  showPreview.value = true;
};
</script>

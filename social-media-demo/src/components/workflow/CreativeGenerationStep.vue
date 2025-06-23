<template>
  <div class="space-y-6">
    <!-- Content Context -->
    <div v-if="workflowStore.generatedContent" class="bg-blue-50 rounded-lg p-4 border border-blue-200">
      <div class="flex items-center space-x-2 mb-2">
        <DocumentTextIcon class="w-5 h-5 text-blue-600" />
        <h4 class="font-semibold text-gray-900">Generated Content:</h4>
      </div>
      <p class="text-sm text-gray-700 line-clamp-2">{{ workflowStore.generatedContent.content.text }}</p>
    </div>

    <!-- Creative Configuration -->
    <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
      <!-- Creative Types Selection -->
      <div class="bg-white rounded-lg border border-gray-200 p-4 space-y-4">
        <div class="flex items-center space-x-2">
          <PhotoIcon class="w-5 h-5 text-gray-600" />
          <h4 class="font-semibold text-gray-900">Creative Assets</h4>
        </div>
        
        <div class="grid grid-cols-1 gap-3">
          <label
            v-for="creativeType in availableCreatives"
            :key="creativeType.type"
            class="flex items-center p-3 rounded-lg border cursor-pointer transition-colors"
            :class="{
              'border-blue-300 bg-blue-50': selectedCreatives.includes(creativeType.type),
              'border-gray-200 hover:border-gray-300 hover:bg-gray-50': !selectedCreatives.includes(creativeType.type)
            }"
          >
            <input
              :value="creativeType.type"
              v-model="selectedCreatives"
              type="checkbox"
              class="rounded border-gray-300 text-blue-600 focus:ring-blue-500 mr-3"
            />
            <div class="flex-1">
              <div class="flex items-center space-x-2 mb-1">
                <span class="text-lg">{{ creativeType.icon }}</span>
                <span class="font-medium text-gray-900">{{ creativeType.name }}</span>
              </div>
              <p class="text-sm text-gray-600">{{ creativeType.description }}</p>
              <p class="text-xs text-gray-500 mt-1">{{ creativeType.dimensions.width }}x{{ creativeType.dimensions.height }}px</p>
            </div>
          </label>
        </div>
      </div>

      <!-- Style Configuration -->
      <div class="bg-white rounded-lg border border-gray-200 p-4 space-y-4">
        <div class="flex items-center space-x-2">
          <SwatchIcon class="w-5 h-5 text-gray-600" />
          <h4 class="font-semibold text-gray-900">Style & Options</h4>
        </div>

        <!-- Style Selection using HeadlessUI -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Visual Style</label>
          <RadioGroup v-model="selectedStyle">
            <div class="grid grid-cols-2 gap-2">
              <RadioGroupOption
                v-for="style in styles"
                :key="style"
                :value="style"
                v-slot="{ checked }"
                class="cursor-pointer"
              >
                <div
                  class="p-3 rounded-lg border text-sm font-medium text-center transition-colors capitalize"
                  :class="{
                    'border-blue-500 bg-blue-50 text-blue-700': checked,
                    'border-gray-200 bg-white text-gray-700 hover:bg-gray-50': !checked
                  }"
                >
                  {{ style }}
                </div>
              </RadioGroupOption>
            </div>
          </RadioGroup>
        </div>

        <!-- Additional Options -->
        <div class="space-y-3">
          <div class="border-t border-gray-200 pt-3">
            <label class="flex items-center">
              <input
                v-model="includeText"
                type="checkbox"
                class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
              />
              <span class="ml-2 text-sm text-gray-700">Include text overlay on visuals</span>
            </label>
          </div>
        </div>

        <!-- Generate Button -->
        <div v-if="selectedCreatives.length > 0" class="pt-3 border-t border-gray-200">
          <button
            @click="generateCreatives"
            :disabled="loading.generate"
            class="w-full flex items-center justify-center space-x-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-md transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
          >
            <LoadingSpinner v-if="loading.generate" size="sm" />
            <SparklesIcon v-else class="w-4 h-4" />
            <span>
              {{ loading.generate ? 'Generating...' : `Generate ${selectedCreatives.length} Asset${selectedCreatives.length > 1 ? 's' : ''}` }}
            </span>
          </button>
        </div>
      </div>
    </div>

    <!-- Generated Creatives -->
    <div v-if="workflowStore.generatedCreatives.length > 0" class="space-y-4">
      <div class="bg-green-50 rounded-lg p-4 border border-green-200">
        <div class="flex items-center justify-between">
          <div class="flex items-center space-x-2">
            <CheckCircleIcon class="w-5 h-5 text-green-600" />
            <h4 class="font-semibold text-gray-900">Generated Assets</h4>
            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
              {{ workflowStore.generatedCreatives.length }} asset{{ workflowStore.generatedCreatives.length > 1 ? 's' : '' }}
            </span>
          </div>
          <button
            @click="downloadAllCreatives"
            class="flex items-center space-x-1 px-3 py-1 bg-green-600 hover:bg-green-700 text-white text-sm rounded-md transition-colors"
          >
            <ArrowDownTrayIcon class="w-4 h-4" />
            <span>Download All</span>
          </button>
        </div>
        <p class="text-sm text-green-700 mt-2">Your visual assets have been generated successfully and are ready to use.</p>
      </div>
      
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <GeneratedCreativeCard
          v-for="creative in workflowStore.generatedCreatives"
          :key="creative.id"
          :creative="creative"
        />
      </div>
    </div>

    <!-- Empty State -->
    <div v-if="selectedCreatives.length === 0" class="text-center py-8 bg-gray-50 rounded-lg border border-dashed border-gray-300">
      <div class="text-3xl mb-3">🎨</div>
      <h3 class="font-medium text-gray-900 mb-2">Select Creative Types</h3>
      <p class="text-sm text-gray-600">Choose visual assets to generate for your content</p>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { RadioGroup, RadioGroupOption } from '@headlessui/vue';
import { 
  DocumentTextIcon, 
  PhotoIcon, 
  SwatchIcon, 
  SparklesIcon, 
  CheckCircleIcon,
  ArrowDownTrayIcon
} from '@heroicons/vue/24/solid';
import { useWorkflowStore } from '@/stores/workflow';
import { useUiStore } from '@/stores/ui';
import { creativeApi } from '@/services/api';
import type { CreativeType, CreativeRequest } from '@/types';

import GeneratedCreativeCard from '@/components/creative/GeneratedCreativeCard.vue';
import LoadingSpinner from '@/components/ui/LoadingSpinner.vue';

const workflowStore = useWorkflowStore();
const uiStore = useUiStore();

const selectedCreatives = ref<CreativeType[]>([]);
const selectedStyle = ref<'minimal' | 'bold' | 'colorful' | 'elegant' | 'modern'>('modern');
const includeText = ref(true);
const styles = ['minimal', 'bold', 'colorful', 'elegant', 'modern'] as const;

const loading = ref({
  generate: false
});

const availableCreatives = computed(() => {
  const platform = workflowStore.selectedAccounts[0]?.platform;
  const base = [
    {
      type: 'post' as CreativeType,
      name: 'Social Media Post',
      description: 'Square format perfect for Instagram and Facebook',
      dimensions: { width: 1080, height: 1080 },
      icon: '📱'
    },
    {
      type: 'story' as CreativeType,
      name: 'Story Format',
      description: 'Vertical format for Instagram and Facebook stories',
      dimensions: { width: 1080, height: 1920 },
      icon: '📲'
    },
    {
      type: 'cover' as CreativeType,
      name: 'Cover Image',
      description: 'Header image for social media profiles',
      dimensions: { width: 1200, height: 630 },
      icon: '🖼️'
    }
  ];

  if (platform === 'youtube') {
    base.push({
      type: 'thumbnail' as CreativeType,
      name: 'Video Thumbnail',
      description: 'Eye-catching thumbnail for video content',
      dimensions: { width: 1280, height: 720 },
      icon: '🎬'
    });
  }

  if (platform === 'linkedin' || platform === 'facebook') {
    base.push({
      type: 'banner' as CreativeType,
      name: 'Banner/Header',
      description: 'Professional banner for business profiles',
      dimensions: { width: 1584, height: 396 },
      icon: '🏢'
    });
  }

  return base;
});

const generateCreatives = async () => {
  if (!workflowStore.generatedContent || workflowStore.selectedAccounts.length === 0) {
    uiStore.addNotification({
      type: 'error',
      title: 'Missing Requirements',
      message: 'Please generate content first'
    });
    return;
  }

  loading.value.generate = true;

  try {
    const requests: CreativeRequest[] = selectedCreatives.value.map(type => {
      const creativeInfo = availableCreatives.value.find(c => c.type === type);
      return {
        id: `req_${Date.now()}_${type}`,
        content_id: workflowStore.generatedContent!.id,
        type,
        platform: workflowStore.selectedAccounts[0]!.platform,
        dimensions: creativeInfo?.dimensions || { width: 1080, height: 1080 },
        style: selectedStyle.value,
        include_text: includeText.value
      };
    });

    // Add requests to store
    requests.forEach(request => {
      workflowStore.addCreativeRequest(request);
    });

    const response = await creativeApi.generateMultipleCreatives(requests);
    if (response.success) {
      response.data.forEach(creative => {
        workflowStore.addGeneratedCreative(creative);
      });

      uiStore.addNotification({
        type: 'success',
        title: 'Creatives Generated',
        message: `Generated ${response.data.length} creative assets successfully`
      });
    }
  } catch (error) {
    console.error('Failed to generate creatives:', error);
    uiStore.addNotification({
      type: 'error',
      title: 'Generation Failed',
      message: 'Failed to generate creative assets. Please try again.'
    });
  } finally {
    loading.value.generate = false;
  }
};

const downloadAllCreatives = async () => {
  try {
    for (const creative of workflowStore.generatedCreatives) {
      // Create a temporary link element to trigger download
      const link = document.createElement('a');
      link.href = creative.image_url;
      link.download = `${creative.type}-${creative.id}.png`;
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);
      
      // Add small delay between downloads to prevent browser blocking
      await new Promise(resolve => setTimeout(resolve, 500));
    }

    uiStore.addNotification({
      type: 'success',
      title: 'Download Started',
      message: `Downloading ${workflowStore.generatedCreatives.length} creative assets`
    });
  } catch (error) {
    console.error('Failed to download creatives:', error);
    uiStore.addNotification({
      type: 'error',
      title: 'Download Failed',
      message: 'Failed to download creative assets. Please try again.'
    });
  }
};

onMounted(() => {
  // Auto-select recommended creatives based on platform
  const platform = workflowStore.selectedAccounts[0]?.platform;
  if (platform === 'instagram') {
    selectedCreatives.value = ['post', 'story'];
  } else if (platform === 'youtube') {
    selectedCreatives.value = ['thumbnail'];
  } else {
    selectedCreatives.value = ['post'];
  }
});
</script>

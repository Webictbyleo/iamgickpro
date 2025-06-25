<template>
  <div class="bg-white rounded-xl border border-gray-200 p-6">
    <div class="flex items-center justify-between mb-4">
      <h3 class="text-lg font-bold text-gray-900">Content Templates</h3>
      <div class="flex items-center space-x-2">
        <select 
          v-model="selectedGoal" 
          @change="filterTemplates"
          class="text-sm border border-gray-300 rounded-lg px-3 py-1 focus:ring-2 focus:ring-purple-500 focus:border-transparent"
        >
          <option value="">All Goals</option>
          <option value="promote">Promote</option>
          <option value="educate">Educate</option>
          <option value="community">Community</option>
        </select>
      </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
      <div 
        v-for="template in filteredTemplates" 
        :key="template.id"
        @click="selectTemplate(template)"
        class="group p-4 border border-gray-200 rounded-lg hover:border-purple-300 hover:shadow-md transition-all duration-200 cursor-pointer"
      >
        <div class="flex items-start justify-between mb-3">
          <h4 class="font-medium text-gray-900 text-sm group-hover:text-purple-700">{{ template.title }}</h4>
          <div class="text-right">
            <div class="text-xs font-bold text-green-600">{{ template.engagement_score }}</div>
            <div class="text-xs text-gray-500">score</div>
          </div>
        </div>
        
        <p class="text-xs text-gray-600 mb-3 line-clamp-2">{{ template.preview }}</p>
        
        <div class="space-y-2">
          <div class="flex flex-wrap gap-1">
            <span 
              v-for="platform in template.best_platforms.slice(0, 3)" 
              :key="platform"
              class="px-2 py-1 bg-blue-100 text-blue-600 rounded text-xs"
            >
              {{ platform }}
            </span>
          </div>
          
          <div class="flex flex-wrap gap-1">
            <span 
              v-for="hashtag in template.hashtags.slice(0, 3)" 
              :key="hashtag"
              class="px-2 py-1 bg-gray-100 text-gray-600 rounded text-xs"
            >
              {{ hashtag }}
            </span>
            <span v-if="template.hashtags.length > 3" class="text-xs text-gray-400">
              +{{ template.hashtags.length - 3 }}
            </span>
          </div>
        </div>
      </div>
    </div>

    <!-- Quick Structure Reference -->
    <div v-if="selectedGoal" class="mt-6 pt-6 border-t border-gray-200">
      <h4 class="font-medium text-gray-900 mb-3">🏗️ Proven Structures for {{ getGoalLabel(selectedGoal) }}</h4>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
        <div 
          v-for="(structure, index) in getStructures(selectedGoal)" 
          :key="index"
          class="p-3 bg-gray-50 rounded-lg"
        >
          <div class="text-sm font-medium text-gray-700">{{ structure }}</div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';
import { mockContentSuggestions, mockContentTemplates } from '@/data/mockData';
import type { SocialPlatform } from '@/types';

interface ContentTemplate {
  id: string;
  title: string;
  preview: string;
  engagement_score: number;
  best_platforms: SocialPlatform[];
  hashtags: string[];
  goal?: string;
}

interface Props {
  goal?: string;
}

interface Emits {
  (e: 'select', template: ContentTemplate): void;
}

const props = defineProps<Props>();
const emit = defineEmits<Emits>();

const selectedGoal = ref(props.goal || '');

// Convert mock suggestions to templates format
const allTemplates = computed(() => {
  const templates: ContentTemplate[] = [];
  
  const platformMap: Record<string, SocialPlatform> = {
    'LinkedIn': 'linkedin',
    'Twitter': 'twitter', 
    'Instagram': 'instagram',
    'Facebook': 'facebook',
    'TikTok': 'tiktok',
    'YouTube': 'youtube',
    'Medium': 'linkedin' // Map Medium to LinkedIn as fallback
  };
  
  Object.entries(mockContentSuggestions).forEach(([goal, suggestions]) => {
    suggestions.forEach(suggestion => {
      const mappedPlatforms = suggestion.best_platforms
        .map(platform => platformMap[platform])
        .filter(Boolean) as SocialPlatform[];
        
      templates.push({
        ...suggestion,
        best_platforms: mappedPlatforms,
        goal
      });
    });
  });
  
  return templates;
});

const filteredTemplates = computed(() => {
  if (!selectedGoal.value) return allTemplates.value;
  return allTemplates.value.filter(template => template.goal === selectedGoal.value);
});

const getGoalLabel = (goal: string) => {
  const labels = {
    promote: 'Promotion',
    educate: 'Education', 
    community: 'Community Building'
  };
  return labels[goal as keyof typeof labels] || goal;
};

const getStructures = (goal: string) => {
  return mockContentTemplates[goal as keyof typeof mockContentTemplates]?.structures || [];
};

const filterTemplates = () => {
  // Reactive computed will handle this automatically
};

const selectTemplate = (template: ContentTemplate) => {
  emit('select', template);
};
</script>

<style scoped>
.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>

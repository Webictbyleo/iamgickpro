<template>
  <div class="bg-white rounded-lg border border-gray-200 p-6">
    <div class="flex items-center space-x-3 mb-6">
      <div class="w-8 h-8 bg-blue-600 rounded-lg flex items-center justify-center">
        <SparklesIcon class="w-4 h-4 text-white" />
      </div>
      <div class="flex-1">
        <h3 class="text-lg font-semibold text-gray-900">Smart Content Ideas</h3>
        <p class="text-sm text-gray-600">AI-powered recommendations for {{ goal }} content</p>
      </div>
    </div>

    <!-- Scrollable suggestions container -->
    <div class="max-h-96 overflow-y-auto space-y-4 pr-2">
      <div 
        v-for="suggestion in filteredSuggestions" 
        :key="suggestion.id"
        @click="selectSuggestion(suggestion)"
        class="group p-4 bg-gray-50 hover:bg-blue-50 rounded-lg border border-gray-200 hover:border-blue-300 transition-all duration-200 cursor-pointer"
      >
        <div class="flex items-start justify-between mb-3">
          <div class="flex items-start space-x-3 flex-1">
            <div class="flex-1">
              <h4 class="text-sm font-medium text-gray-900 mb-1">{{ suggestion.title }}</h4>
              <p class="text-sm text-gray-600 leading-relaxed">{{ suggestion.reason }}</p>
            </div>
          </div>
          <div class="text-right ml-4">
            <div class="text-sm font-bold text-green-600">{{ suggestion.confidence }}%</div>
            <div class="text-xs text-gray-500">confidence</div>
          </div>
        </div>
        
        <div class="flex items-center justify-between text-xs text-gray-500 mb-3">
          <div class="flex items-center space-x-4">
            <span>Reach: <span class="font-medium text-gray-700">{{ suggestion.estimated_reach }}</span></span>
            <span>Best time: <span class="font-medium text-gray-700">{{ suggestion.best_time }}</span></span>
          </div>
          <div class="flex items-center space-x-2">
            <span>Trend:</span>
            <div class="w-12 h-2 bg-gray-200 rounded-full">
              <div 
                class="h-2 bg-blue-600 rounded-full transition-all duration-300"
                :style="{ width: `${suggestion.trend_score}%` }"
              ></div>
            </div>
            <span class="font-medium text-blue-600">{{ suggestion.trend_score }}</span>
          </div>
        </div>
        
        <div class="flex items-center justify-between">
          <div class="flex items-center space-x-2">
            <span 
              class="px-2 py-1 rounded-full text-xs font-medium"
              :class="{
                'bg-red-100 text-red-600': suggestion.type === 'trending',
                'bg-blue-100 text-blue-600': suggestion.type === 'audience', 
                'bg-green-100 text-green-600': suggestion.type === 'timing',
                'bg-orange-100 text-orange-600': suggestion.type === 'viral_opportunity',
                'bg-purple-100 text-purple-600': suggestion.type === 'seasonal'
              }"
            >
              {{ getSuggestionTypeLabel(suggestion.type) }}
            </span>
            <span 
              v-if="suggestion.urgency === 'urgent'"
              class="px-2 py-1 bg-red-100 text-red-600 rounded-full text-xs font-medium"
            >
              ⚡ Urgent
            </span>
          </div>
          <button 
            @click.stop="selectSuggestion(suggestion)"
            class="text-sm text-blue-600 hover:text-blue-700 font-medium transition-colors px-3 py-1 hover:bg-blue-100 rounded-lg"
          >
            Use this idea →
          </button>
        </div>
      </div>
    </div>

    <!-- Quick stats -->
    <div v-if="filteredSuggestions.length > 0" class="mt-6 pt-4 border-t border-gray-200">
      <div class="grid grid-cols-3 gap-4 text-center">
        <div>
          <div class="text-lg font-semibold text-blue-600">{{ averageConfidence }}%</div>
          <div class="text-xs text-gray-600">Avg. Confidence</div>
        </div>
        <div>
          <div class="text-lg font-semibold text-green-600">{{ highOpportunities }}</div>
          <div class="text-xs text-gray-600">High Opportunities</div>
        </div>
        <div>
          <div class="text-lg font-semibold text-orange-600">{{ urgentSuggestions }}</div>
          <div class="text-xs text-gray-600">Urgent Actions</div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { SparklesIcon } from '@heroicons/vue/24/solid'
import { enhancedSmartSuggestions } from '@/data/mockData'

interface Props {
  goal: string
}

interface Emits {
  (e: 'suggestion-selected', suggestion: any): void
}

const props = defineProps<Props>()
const emit = defineEmits<Emits>()

// Filter suggestions based on the selected goal
const filteredSuggestions = computed(() => {
  return enhancedSmartSuggestions.filter(suggestion => {
    // Show all trending and timing suggestions
    if (suggestion.type === 'trending' || suggestion.type === 'timing') return true
    
    // Show goal-specific suggestions
    switch (props.goal) {
      case 'promote':
        return suggestion.type === 'viral_opportunity' || suggestion.type === 'collaboration'
      case 'educate':
        return suggestion.type === 'audience' || suggestion.type === 'seasonal'
      case 'community':
        return suggestion.type === 'collaboration' || suggestion.type === 'viral_opportunity'
      default:
        return true
    }
  }).slice(0, 6) // Show more suggestions since they're now scrollable
})

const selectSuggestion = (suggestion: any) => {
  // Add suggested_content field based on suggestion data
  const enhancedSuggestion = {
    ...suggestion,
    suggested_content: suggestion.title || suggestion.reason || `Content idea: ${suggestion.title}`
  };
  
  emit('suggestion-selected', enhancedSuggestion);
};

const getSuggestionTypeLabel = (type: string) => {
  const labels: Record<string, string> = {
    trending: '🔥 Trending',
    audience: '👥 Audience',
    timing: '⏰ Timing',
    viral_opportunity: '🚀 Viral',
    seasonal: '🎯 Seasonal',
    collaboration: '🤝 Collab'
  }
  return labels[type] || type
}

const averageConfidence = computed(() => {
  if (filteredSuggestions.value.length === 0) return 0
  const total = filteredSuggestions.value.reduce((sum, s) => sum + s.confidence, 0)
  return Math.round(total / filteredSuggestions.value.length)
})

const highOpportunities = computed(() => {
  return filteredSuggestions.value.filter(s => s.confidence >= 85).length
})

const urgentSuggestions = computed(() => {
  return filteredSuggestions.value.filter(s => s.urgency === 'urgent').length
})
</script>

<style scoped>
/* Custom scrollbar for suggestions */
.overflow-y-auto::-webkit-scrollbar {
  width: 6px;
}

.overflow-y-auto::-webkit-scrollbar-track {
  background: #f1f5f9;
  border-radius: 3px;
}

.overflow-y-auto::-webkit-scrollbar-thumb {
  background: #cbd5e1;
  border-radius: 3px;
}

.overflow-y-auto::-webkit-scrollbar-thumb:hover {
  background: #94a3b8;
}

/* Smooth transitions */
.transition-all {
  transition: all 0.2s ease;
}
</style>

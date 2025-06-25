<template>
  <div class="bg-gradient-to-r from-purple-50 to-indigo-50 rounded-xl border border-purple-200 p-6">
    <div class="flex items-center space-x-3 mb-4">
      <div class="w-8 h-8 bg-gradient-to-br from-purple-500 to-indigo-600 rounded-lg flex items-center justify-center">
        <SparklesIcon class="w-4 h-4 text-white" />
      </div>
      <div class="flex-1">
        <h3 class="text-lg font-bold text-gray-900">Smart Suggestions</h3>
        <p class="text-xs text-purple-600 font-medium">AI-powered recommendations for {{ goal }} content</p>
      </div>
      <div class="text-xs text-purple-700 bg-purple-100 px-2 py-1 rounded">
        Step 2 of 3
      </div>
    </div>

    <div class="space-y-3">
      <div 
        v-for="suggestion in filteredSuggestions" 
        :key="suggestion.id"
        @click="selectSuggestion(suggestion)"
        class="group p-4 bg-white rounded-lg border border-purple-100 hover:border-purple-300 hover:shadow-md transition-all duration-200 cursor-pointer"
      >
        <div class="flex items-start justify-between mb-2">
          <div class="flex items-center space-x-2">
            <span class="text-sm font-medium text-gray-900">{{ suggestion.title }}</span>
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
              class="px-2 py-1 bg-red-100 text-red-600 rounded-full text-xs font-bold animate-pulse"
            >
              ⚡ Urgent
            </span>
          </div>
          <div class="text-right">
            <div class="text-xs font-bold text-green-600">{{ suggestion.confidence }}%</div>
            <div class="text-xs text-gray-500">confidence</div>
          </div>
        </div>
        
        <p class="text-sm text-gray-600 mb-3">{{ suggestion.reason }}</p>
        
        <div class="space-y-2">
          <div class="flex items-center justify-between text-xs">
            <div class="flex items-center space-x-3">
              <span class="text-gray-500">Reach: <span class="font-medium text-gray-700">{{ suggestion.estimated_reach }}</span></span>
              <span class="text-gray-500">Best time: <span class="font-medium text-gray-700">{{ suggestion.best_time }}</span></span>
            </div>
            <div class="flex items-center space-x-1">
              <span class="text-xs text-gray-500">Trend Score:</span>
              <div class="w-12 h-2 bg-gray-200 rounded-full">
                <div 
                  class="h-2 bg-gradient-to-r from-green-400 to-green-600 rounded-full"
                  :style="{ width: `${suggestion.trend_score}%` }"
                ></div>
              </div>
              <span class="text-xs font-bold text-green-600">{{ suggestion.trend_score }}</span>
            </div>
          </div>
          
          <!-- Platform tags -->
          <div class="flex items-center justify-between">
            <div class="flex space-x-1">
              <span 
                v-for="platform in suggestion.platforms.slice(0, 3)" 
                :key="platform"
                class="px-2 py-1 bg-gray-100 text-gray-600 rounded text-xs"
              >
                {{ platform }}
              </span>
              <span v-if="suggestion.platforms.length > 3" class="text-xs text-gray-400">
                +{{ suggestion.platforms.length - 3 }}
              </span>
            </div>
            <div class="text-xs text-gray-500">
              Competition: 
              <span 
                class="font-medium"
                :class="{
                  'text-green-600': suggestion.competition_level === 'low',
                  'text-yellow-600': suggestion.competition_level === 'medium',
                  'text-red-600': suggestion.competition_level === 'high' || suggestion.competition_level === 'very_high'
                }"
              >
                {{ suggestion.competition_level }}
              </span>
            </div>
          </div>
        </div>
        
        <!-- Action button -->
        <div class="mt-3 pt-3 border-t border-gray-100">
          <button class="w-full text-center text-sm text-purple-600 hover:text-purple-700 font-medium transition-colors">
            💡 Use this suggestion
          </button>
        </div>
      </div>
    </div>

    <!-- Quick stats -->
    <div v-if="filteredSuggestions.length > 0" class="mt-4 p-3 bg-white rounded-lg border border-purple-200">
      <h4 class="text-sm font-medium text-gray-900 mb-2">📊 Quick Stats</h4>
      <div class="grid grid-cols-3 gap-4 text-center">
        <div>
          <div class="text-lg font-bold text-purple-600">{{ averageConfidence }}%</div>
          <div class="text-xs text-gray-600">Avg. Confidence</div>
        </div>
        <div>
          <div class="text-lg font-bold text-green-600">{{ highOpportunities }}</div>
          <div class="text-xs text-gray-600">High Opportunities</div>
        </div>
        <div>
          <div class="text-lg font-bold text-orange-600">{{ urgentSuggestions }}</div>
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
  }).slice(0, 4) // Limit to top 4 suggestions
})

const selectSuggestion = (suggestion: any) => {
  emit('suggestion-selected', suggestion)
}

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
.animate-pulse {
  animation: pulse 1.5s infinite;
}

@keyframes pulse {
  0%, 100% { opacity: 1; }
  50% { opacity: 0.6; }
}
</style>

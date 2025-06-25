<template>
  <div class="bg-white rounded-xl border border-gray-200 p-6">
    <div class="flex items-center justify-between mb-4">
      <div class="flex items-center space-x-3">
        <div class="w-8 h-8 bg-gradient-to-br from-purple-500 to-indigo-600 rounded-lg flex items-center justify-center">
          <span class="text-white font-bold text-sm">1</span>
        </div>
        <div>
          <h3 class="text-xl font-bold text-gray-900">What's your goal?</h3>
          <p class="text-sm text-gray-600">Choose what you want to achieve with this post</p>
        </div>
      </div>
      <div class="text-xs text-gray-500 bg-gray-100 px-2 py-1 rounded">
        Step 1 of 3
      </div>
    </div>
    
    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
      <button
        v-for="goal in quickGoals"
        :key="goal.value"
        @click="selectGoal(goal)"
        class="group relative p-4 rounded-lg border-2 transition-all duration-200 hover:scale-105 text-left"
        :class="{
          'border-purple-300 bg-purple-50 ring-2 ring-purple-200 shadow-md': selectedGoal === goal.value,
          'border-gray-200 bg-white hover:border-purple-200 hover:shadow-md': selectedGoal !== goal.value
        }"
      >
        <div class="flex items-center justify-between mb-3">
          <div class="text-2xl">{{ goal.icon }}</div>
          <div v-if="goal.trending" class="px-2 py-1 bg-red-100 text-red-600 text-xs rounded-full font-medium">
            🔥 Hot
          </div>
        </div>
        
        <h4 class="font-semibold text-gray-900 mb-2 text-sm">{{ goal.label }}</h4>
        <p class="text-xs text-gray-600 leading-tight mb-2">{{ goal.description }}</p>
        
        <!-- Success metrics -->
        <div class="flex items-center justify-between text-xs">
          <span class="text-green-600 font-bold">{{ goal.engagement_boost }}</span>
          <span class="text-gray-500">avg. boost</span>
        </div>
        
        <!-- Selection indicator -->
        <div 
          v-if="selectedGoal === goal.value"
          class="absolute top-2 right-2 w-5 h-5 bg-purple-600 rounded-full flex items-center justify-center"
        >
          <CheckIcon class="w-3 h-3 text-white" />
        </div>
      </button>
    </div>

    <!-- Goal-specific pro tip -->
    <div v-if="selectedGoal" class="mt-4 p-4 bg-gradient-to-r from-blue-50 to-indigo-50 rounded-lg border border-blue-200">
      <div class="flex items-start space-x-3">
        <div class="w-6 h-6 bg-blue-500 rounded-full flex items-center justify-center flex-shrink-0 mt-0.5">
          <span class="text-white text-xs">💡</span>
        </div>
        <div class="flex-1">
          <h4 class="font-medium text-blue-900 mb-1">Pro Tip for {{ getSelectedGoalLabel() }}</h4>
          <p class="text-blue-800 text-sm">{{ getSelectedGoalTip() }}</p>
          <div class="mt-2 flex flex-wrap gap-1">
            <span 
              v-for="platform in getSelectedGoalPlatforms()" 
              :key="platform"
              class="inline-block px-2 py-1 bg-blue-100 text-blue-700 text-xs rounded"
            >
              {{ platform }}
            </span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { CheckIcon } from '@heroicons/vue/24/solid'

interface Goal {
  value: string
  label: string
  icon: string
  description: string
  engagement_boost: string
  trending?: boolean
  bestFor: string[]
}

interface Props {
  selectedGoal?: string
}

interface Emits {
  (e: 'update:selectedGoal', value: string): void
  (e: 'goal-selected', value: string): void
}

const props = defineProps<Props>()
const emit = defineEmits<Emits>()

const quickGoals: Goal[] = [
  {
    value: 'promote',
    label: 'Promote & Sell',
    icon: '🚀',
    description: 'Drive sales and showcase your offerings with compelling promotional content',
    engagement_boost: '+45%',
    trending: true,
    bestFor: ['LinkedIn', 'Instagram', 'Facebook']
  },
  {
    value: 'educate',
    label: 'Teach & Share Knowledge',
    icon: '📚',
    description: 'Build authority and trust by teaching your audience valuable skills',
    engagement_boost: '+62%',
    bestFor: ['YouTube', 'LinkedIn', 'TikTok']
  },
  {
    value: 'community',
    label: 'Build Community',
    icon: '🤝',
    description: 'Foster engagement and create meaningful connections with your audience',
    engagement_boost: '+78%',
    trending: true,
    bestFor: ['Instagram', 'Facebook', 'Twitter']
  }
]

const goalTips = {
  promote: "Focus on benefits over features. Show real results and social proof to build credibility.",
  educate: "Break complex topics into digestible steps. Use visual aids and real examples for clarity.",
  community: "Ask questions that spark genuine conversation. Be authentic and respond to comments promptly."
}

const selectGoal = (goal: Goal) => {
  emit('update:selectedGoal', goal.value)
  emit('goal-selected', goal.value)
}

const getSelectedGoalLabel = () => {
  const goal = quickGoals.find(g => g.value === props.selectedGoal)
  return goal?.label || ''
}

const getSelectedGoalTip = () => {
  return goalTips[props.selectedGoal as keyof typeof goalTips] || ''
}

const getSelectedGoalPlatforms = () => {
  const goal = quickGoals.find(g => g.value === props.selectedGoal)
  return goal?.bestFor || []
}
</script>

<style scoped>
/* Component-specific animations */
.transition-all {
  transition: all 0.2s ease;
}

button:hover {
  transform: translateY(-1px);
}

button:active {
  transform: translateY(0);
}
</style>

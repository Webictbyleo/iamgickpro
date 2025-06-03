<template>
  <div class="space-y-6">
    <div class="text-center">
      <h3 class="text-lg font-medium text-gray-900">Analyzing Video Content</h3>
      <p class="text-gray-600 mt-1">AI is processing your video to generate design ideas</p>
    </div>

    <!-- Progress Bar -->
    <div class="w-full bg-gray-200 rounded-full h-2">
      <div 
        class="bg-gradient-to-r from-primary-600 to-purple-600 h-2 rounded-full transition-all duration-500"
        :style="{ width: `${progress}%` }"
      ></div>
    </div>

    <div class="text-center">
      <p class="text-sm text-gray-600">{{ progressMessage }}</p>
      <p class="text-xs text-gray-500 mt-1">{{ progress }}% complete</p>
    </div>

    <!-- Processing Steps -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
      <div
        v-for="(step, index) in processingSteps"
        :key="step.id"
        class="flex items-center space-x-3 p-3 rounded-lg"
        :class="getStepClass(index)"
      >
        <div class="flex-shrink-0">
          <div
            v-if="index < currentStep"
            class="w-6 h-6 bg-green-500 rounded-full flex items-center justify-center"
          >
            <CheckIcon class="w-4 h-4 text-white" />
          </div>
          <div
            v-else-if="index === currentStep"
            class="w-6 h-6 bg-primary-600 rounded-full flex items-center justify-center"
          >
            <div class="w-3 h-3 bg-white rounded-full animate-pulse"></div>
          </div>
          <div
            v-else
            class="w-6 h-6 bg-gray-300 rounded-full"
          ></div>
        </div>
        <div class="flex-1 min-w-0">
          <p class="text-sm font-medium text-gray-900">{{ step.title }}</p>
          <p class="text-xs text-gray-500">{{ step.description }}</p>
        </div>
      </div>
    </div>

    <!-- Estimated Time -->
    <div v-if="job.estimatedTime" class="text-center">
      <p class="text-sm text-gray-600">
        Estimated time remaining: {{ formatTime(job.estimatedTime) }}
      </p>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, watch } from 'vue'
import { CheckIcon } from '@heroicons/vue/24/solid'
import type { GenerateDesignFromVideoResponse } from '@/types'

interface Props {
  job: GenerateDesignFromVideoResponse
}

interface Emits {
  (e: 'completed', job: GenerateDesignFromVideoResponse): void
  (e: 'failed', job: GenerateDesignFromVideoResponse): void
}

const props = defineProps<Props>()
const emit = defineEmits<Emits>()

const processingSteps = [
  {
    id: 'extract',
    title: 'Extracting Video Content',
    description: 'Downloading and analyzing video frames'
  },
  {
    id: 'analyze',
    title: 'AI Visual Analysis',
    description: 'Identifying themes, colors, and concepts'
  },
  {
    id: 'generate',
    title: 'Creating Designs',
    description: 'Generating design variations with AI'
  },
  {
    id: 'complete',
    title: 'Finalizing Results',
    description: 'Optimizing and preparing designs'
  }
]

const progress = computed(() => props.job.progress || 0)
const currentStep = computed(() => Math.floor(progress.value / 25))

const progressMessage = computed(() => {
  if (progress.value < 25) return 'Downloading and extracting key frames from YouTube video...'
  if (progress.value < 50) return 'AI analyzing visual content, themes, and color palette...'
  if (progress.value < 75) return 'Generating design concepts based on video analysis...'
  if (progress.value < 100) return 'Finalizing designs and preparing thumbnails...'
  return 'YouTube video analysis complete! Your designs are ready.'
})

const getStepClass = (index: number) => {
  if (index < currentStep.value) return 'bg-green-50 border border-green-200'
  if (index === currentStep.value) return 'bg-primary-50 border border-primary-200'
  return 'bg-gray-50 border border-gray-200'
}

const formatTime = (seconds: number): string => {
  const minutes = Math.floor(seconds / 60)
  const remainingSeconds = seconds % 60
  
  if (minutes > 0) {
    return `${minutes}m ${remainingSeconds}s`
  }
  return `${remainingSeconds}s`
}

watch(() => props.job.status, (newStatus) => {
  if (newStatus === 'completed') {
    emit('completed', props.job)
  } else if (newStatus === 'failed') {
    emit('failed', props.job)
  }
})
</script>

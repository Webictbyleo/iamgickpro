<template>
  <div class="min-h-screen bg-gray-50">
    <!-- Minimal Header -->
    <header class="bg-white border-b border-gray-100">
      <div class="max-w-7xl mx-auto px-4 py-3">
        <div class="flex items-center justify-between">
          <div class="flex items-center space-x-3">
            <div class="w-7 h-7 bg-blue-600 rounded-lg flex items-center justify-center">
              <BoltIcon class="w-4 h-4 text-white" />
            </div>
            <h1 class="text-lg font-medium text-gray-900">Content Studio</h1>
          </div>
          
          <button
            v-if="workflowStore.completedSteps.length > 0"
            @click="resetWorkflow"
            class="text-sm text-gray-500 hover:text-gray-700 transition-colors"
          >
            Start Over
          </button>
        </div>
      </div>
    </header>

    <!-- Horizontal Step Navigation -->
    <div class="bg-white border-b border-gray-100">
      <div class="max-w-7xl mx-auto px-4">
        <TabGroup 
          :selectedIndex="currentStepIndex" 
          @change="changeStep"
          manual
        >
          <TabList class="flex space-x-1 overflow-x-auto py-4">
            <Tab
              v-for="(step, index) in steps"
              :key="step.id"
              v-slot="{ selected }"
              :disabled="!canAccessStep(index)"
              class="flex-shrink-0 focus:outline-none"
            >
              <div 
                class="flex items-center space-x-3 px-4 py-2 rounded-lg text-sm font-medium transition-all duration-200"
                :class="{
                  'bg-blue-50 text-blue-700 border border-blue-200': selected,
                  'text-green-600 hover:bg-green-50': !selected && isStepCompleted(step.id),
                  'text-gray-400 cursor-not-allowed': !canAccessStep(index),
                  'text-gray-600 hover:bg-gray-50 hover:text-gray-800': !selected && canAccessStep(index) && !isStepCompleted(step.id)
                }"
              >
                <!-- Step Icon -->
                <div 
                  class="w-6 h-6 rounded-full flex items-center justify-center text-xs font-bold transition-all"
                  :class="{
                    'bg-blue-600 text-white': selected,
                    'bg-green-500 text-white': !selected && isStepCompleted(step.id),
                    'bg-gray-200 text-gray-400': !canAccessStep(index),
                    'bg-gray-300 text-gray-600': !selected && canAccessStep(index) && !isStepCompleted(step.id)
                  }"
                >
                  <CheckIcon v-if="isStepCompleted(step.id) && !selected" class="w-3 h-3" />
                  <span v-else>{{ index + 1 }}</span>
                </div>
                
                <!-- Step Title -->
                <span class="hidden sm:block">{{ step.title }}</span>
              </div>
            </Tab>
          </TabList>
          
          <!-- Step Content -->
          <TabPanels class="mt-6">
            <TabPanel 
              v-for="step in steps" 
              :key="step.id"
              class="focus:outline-none"
            >
              <div class="max-w-5xl mx-auto pb-8">
                <!-- Step Header -->
                <div class="mb-6">
                  <h2 class="text-2xl font-semibold text-gray-900 mb-2">{{ step.title }}</h2>
                  <p class="text-gray-600">{{ step.description }}</p>
                </div>
                
                <!-- Step Content -->
                <div class="bg-white rounded-lg border border-gray-200 p-6">
                  <component :is="step.component" />
                </div>
                
                <!-- Step Navigation -->
                <div class="flex items-center justify-between mt-6">
                  <button
                    v-if="currentStepIndex > 0"
                    @click="goToPreviousStep"
                    class="flex items-center space-x-2 px-4 py-2 text-gray-600 hover:text-gray-800 hover:bg-gray-50 rounded-lg transition-colors"
                  >
                    <ChevronLeftIcon class="w-4 h-4" />
                    <span>Previous</span>
                  </button>
                  <div v-else></div>
                  
                  <div class="flex items-center space-x-4">
                    <!-- Step Status -->
                    <div v-if="canProceedFromCurrentStep" class="flex items-center space-x-2 text-green-600">
                      <CheckCircleIcon class="w-5 h-5" />
                      <span class="text-sm font-medium">Complete</span>
                    </div>
                    
                    <!-- Next Button -->
                    <button
                      v-if="currentStepIndex < steps.length - 1"
                      :disabled="!canProceedFromCurrentStep"
                      @click="goToNextStep"
                      class="flex items-center space-x-2 px-6 py-2 rounded-lg font-medium transition-all duration-200"
                      :class="{
                        'bg-blue-600 hover:bg-blue-700 text-white': canProceedFromCurrentStep,
                        'bg-gray-200 text-gray-400 cursor-not-allowed': !canProceedFromCurrentStep
                      }"
                    >
                      <span>Continue</span>
                      <ChevronRightIcon class="w-4 h-4" />
                    </button>
                    
                    <!-- Finish Button -->
                    <button
                      v-else-if="canProceedFromCurrentStep"
                      @click="resetWorkflow"
                      class="flex items-center space-x-2 px-6 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg font-medium transition-colors"
                    >
                      <span>Create New</span>
                      <PlusIcon class="w-4 h-4" />
                    </button>
                  </div>
                </div>
              </div>
            </TabPanel>
          </TabPanels>
        </TabGroup>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { TabGroup, TabList, Tab, TabPanels, TabPanel } from '@headlessui/vue';
import { 
  CheckIcon, 
  CheckCircleIcon, 
  ChevronLeftIcon, 
  ChevronRightIcon, 
  PlusIcon,
  BoltIcon 
} from '@heroicons/vue/24/solid';
import { useWorkflowStore } from '@/stores/workflow';
import { useUiStore } from '@/stores/ui';
import type { WorkflowStep as WorkflowStepType } from '@/types';

import TrendingTopicsStep from '@/components/workflow/TrendingTopicsStep.vue';
import PlatformSelectionStep from '@/components/workflow/PlatformSelectionStep.vue';
import ContentGenerationStep from '@/components/workflow/ContentGenerationStep.vue';
import CreativeGenerationStep from '@/components/workflow/CreativeGenerationStep.vue';
import PostingBehaviorStep from '@/components/workflow/PostingBehaviorStep.vue';

const workflowStore = useWorkflowStore();
const uiStore = useUiStore();

// Step configuration
const steps = [
  {
    id: 'trending' as WorkflowStepType,
    title: 'Select Trending Topic',
    description: 'Discover AI-curated trending topics tailored to your niche and audience',
    component: TrendingTopicsStep
  },
  {
    id: 'platform' as WorkflowStepType,
    title: 'Choose Platform & Account',
    description: 'Select your destination social media platform and connected account',
    component: PlatformSelectionStep
  },
  {
    id: 'content' as WorkflowStepType,
    title: 'Generate Content',
    description: 'Customize AI prompts and create engaging, personalized content',
    component: ContentGenerationStep
  },
  {
    id: 'creative' as WorkflowStepType,
    title: 'Create Visual Assets',
    description: 'Generate stunning thumbnails, covers, and visual elements with AI',
    component: CreativeGenerationStep
  },
  {
    id: 'posting' as WorkflowStepType,
    title: 'Configure Posting',
    description: 'Set up optimal posting schedules and automation behavior',
    component: PostingBehaviorStep
  }
];

// Computed properties
const currentStepIndex = computed(() => {
  return steps.findIndex(step => step.id === workflowStore.currentStep);
});

const canProceedFromCurrentStep = computed(() => {
  return workflowStore.canProceedToNext;
});

// Helper functions
const isStepCompleted = (stepId: WorkflowStepType) => {
  return workflowStore.completedSteps.includes(stepId);
};

const canAccessStep = (stepIndex: number) => {
  const stepId = steps[stepIndex].id;
  return workflowStore.canNavigateTo(stepId);
};

// Navigation functions
const changeStep = (newIndex: number) => {
  if (canAccessStep(newIndex)) {
    workflowStore.setCurrentStep(steps[newIndex].id);
  }
};

const goToNextStep = () => {
  if (canProceedFromCurrentStep.value && currentStepIndex.value < steps.length - 1) {
    workflowStore.goToNextStep();
  }
};

const goToPreviousStep = () => {
  if (currentStepIndex.value > 0) {
    workflowStore.goToPreviousStep();
  }
};

const resetWorkflow = () => {
  workflowStore.resetWorkflow();
  uiStore.addNotification({
    type: 'info',
    title: 'Workflow Reset',
    message: 'Started a new content creation workflow'
  });
};
</script>

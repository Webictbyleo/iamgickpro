<template>
  <div 
    class="transition-all duration-700 ease-out"
    :class="{
      'workflow-step-enhanced': enhanced,
      'workflow-step': !enhanced
    }"
  >
    <div v-if="enhanced" class="relative group">
      <!-- Simplified Step Design -->
      <div 
        class="relative bg-white rounded-2xl border transition-all duration-300 overflow-hidden"
        :class="{
          'border-primary-300 shadow-md ring-2 ring-primary-100/50': active,
          'border-green-300 shadow-sm ring-1 ring-green-100/50': completed && !active,
          'border-gray-200 hover:border-gray-300 hover:shadow-sm': !active && !completed
        }"
      >
        <!-- Simplified Header -->
        <div 
          class="px-6 py-4 border-b transition-all duration-300"
          :class="{
            'bg-primary-50/50 border-primary-200': active,
            'bg-green-50/50 border-green-200': completed && !active,
            'bg-gray-50/30 border-gray-200': !active && !completed
          }"
        >
          <div class="flex items-center space-x-4">
            <!-- Simplified Step Number -->
            <div 
              class="flex-shrink-0 w-12 h-12 rounded-xl flex items-center justify-center font-bold text-sm transition-all duration-300"
              :class="{
                'bg-gradient-to-br from-primary-500 to-primary-600 text-white': active,
                'bg-gradient-to-br from-green-500 to-green-600 text-white': completed && !active,
                'bg-white text-gray-600 border-2 border-gray-300': !active && !completed
              }"
            >
              <CheckIcon v-if="completed && !active" class="w-6 h-6" />
              <div v-else-if="active" class="w-2 h-2 bg-white rounded-full animate-pulse"></div>
              <span v-else class="text-lg">{{ stepNumber }}</span>
            </div>
            
            <!-- Step Info -->
            <div class="flex-1 min-w-0">
              <div class="flex items-center space-x-3 mb-1">
                <h3 
                  class="text-xl font-bold transition-colors"
                  :class="{
                    'text-primary-700': active,
                    'text-green-700': completed && !active,
                    'text-gray-900': !active && !completed
                  }"
                >
                  {{ title }}
                </h3>
                
                <!-- Simplified Status Badges -->
                <span 
                  v-if="completed && !active"
                  class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800"
                >
                  <CheckIcon class="w-3 h-3 mr-1" />
                  Completed
                </span>
                <span 
                  v-else-if="active"
                  class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-primary-100 text-primary-800"
                >
                  <div class="w-2 h-2 bg-primary-600 rounded-full mr-1 animate-pulse"></div>
                  Active
                </span>
              </div>
              
              <p 
                class="text-sm leading-relaxed"
                :class="{
                  'text-primary-600': active,
                  'text-green-600': completed && !active,
                  'text-gray-600': !active && !completed
                }"
              >
                {{ description }}
              </p>
            </div>
          </div>
        </div>
        
        <!-- Step Content -->
        <div v-if="active || completed" class="p-6">
          <div 
            class="transition-all duration-300"
            :class="{
              'animate-fade-in-up': active
            }"
          >
            <slot />
          </div>
        </div>
      </div>
    </div>
    
    <!-- Original Step Design (fallback) -->
    <div v-else class="workflow-step">
      <div class="flex items-start space-x-4">
        <!-- Step Number -->
        <div 
          class="flex-shrink-0 w-10 h-10 rounded-full flex items-center justify-center font-semibold text-sm transition-colors"
          :class="{
            'bg-primary-600 text-white': active,
            'bg-green-600 text-white': completed,
            'bg-gray-200 text-gray-600': !active && !completed
          }"
        >
          <CheckIcon v-if="completed" class="w-5 h-5" />
          <span v-else>{{ stepNumber }}</span>
        </div>
        
        <!-- Step Content -->
        <div class="flex-1 min-w-0">
          <div class="flex items-center justify-between mb-2">
            <h3 
              class="text-lg font-semibold transition-colors"
              :class="{
                'text-primary-600': active,
                'text-green-600': completed,
                'text-gray-900': !active && !completed
              }"
            >
              {{ title }}
            </h3>
            
            <span 
              v-if="completed"
              class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800"
            >
              Completed
            </span>
          </div>
          
          <p class="text-gray-600 mb-4">
            {{ description }}
          </p>
          
          <!-- Step Content Slot -->
          <div v-if="active || completed">
            <slot />
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { CheckIcon } from '@heroicons/vue/24/solid';

interface Props {
  stepNumber: string;
  title: string;
  description: string;
  active: boolean;
  completed: boolean;
  enhanced?: boolean;
}

withDefaults(defineProps<Props>(), {
  enhanced: false
});
</script>

<style scoped>
@keyframes fade-in {
  from {
    opacity: 0;
    transform: translateY(10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.animate-fade-in {
  animation: fade-in 0.5s ease-out;
}

.workflow-step-enhanced {
  margin-bottom: 2rem;
}
</style>

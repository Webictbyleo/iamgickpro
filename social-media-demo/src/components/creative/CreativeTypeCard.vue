<template>
  <div 
    class="border rounded-lg p-4 cursor-pointer transition-all duration-200 hover:shadow-md"
    :class="{
      'border-primary-500 bg-primary-50': selected,
      'border-gray-200 bg-white hover:border-gray-300': !selected
    }"
    @click="$emit('toggle', creativeType.type)"
  >
    <div class="flex items-center justify-between">
      <div class="flex items-center space-x-3">
        <div class="text-3xl">{{ creativeType.icon }}</div>
        <div>
          <h4 class="font-medium text-gray-900">{{ creativeType.name }}</h4>
          <p class="text-sm text-gray-600">{{ creativeType.description }}</p>
          <div class="text-xs text-gray-500 mt-1">
            {{ creativeType.dimensions.width }} × {{ creativeType.dimensions.height }}px
          </div>
        </div>
      </div>
      
      <!-- Selection Indicator -->
      <div 
        class="w-6 h-6 rounded-full border-2 flex items-center justify-center transition-colors"
        :class="{
          'border-primary-500 bg-primary-500': selected,
          'border-gray-300': !selected
        }"
      >
        <CheckIcon v-if="selected" class="w-4 h-4 text-white" />
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import type { CreativeType } from '@/types';
import { CheckIcon } from '@heroicons/vue/24/solid';

interface Props {
  creativeType: {
    type: CreativeType;
    name: string;
    description: string;
    dimensions: { width: number; height: number };
    icon: string;
  };
  selected: boolean;
}

interface Emits {
  (e: 'toggle', type: CreativeType): void;
}

defineProps<Props>();
defineEmits<Emits>();
</script>

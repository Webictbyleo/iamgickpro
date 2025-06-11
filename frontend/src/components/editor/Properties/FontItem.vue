<template>
  <div class="flex items-center justify-between">
    <div class="flex items-center space-x-3 min-w-0 flex-1">
      <!-- Font Status -->
      <div class="flex-shrink-0 w-4 h-4 flex items-center justify-center">
        <div v-if="isLoading" class="w-3 h-3 border border-purple-300 border-t-purple-600 rounded-full animate-spin"></div>
        <CheckIcon v-else-if="isLoaded" class="w-3 h-3 text-green-600" />
        <div v-else class="w-3 h-3 border border-gray-300 rounded-full"></div>
      </div>
      
      <!-- Font Preview -->
      <div class="min-w-0 flex-1">
        <div class="flex items-center space-x-2">
          <span 
            :class="[
              'truncate transition-colors',
              selected ? 'font-semibold text-purple-900' : 'font-normal text-gray-900',
              isLoaded ? '' : 'text-gray-500'
            ]"
            :style="{ 
              fontFamily: isLoaded ? fontStack : 'inherit',
              fontSize: '14px'
            }"
          >
            {{ font.family }}
          </span>
        </div>
        
        <!-- Font Preview Text -->
        <div 
          class="text-sm text-gray-600 mt-1 truncate transition-opacity duration-200"
          :style="{ 
            fontFamily: isLoaded ? fontStack : 'inherit',
            fontSize: '13px',
            opacity: isLoaded ? 1 : 0.6
          }"
        >
          {{ isLoaded ? previewText : 'Loading preview...' }}
        </div>
      </div>
    </div>
    
    <!-- Selection Indicator -->
    <CheckIcon 
      v-if="selected"
      class="h-4 w-4 text-purple-600 flex-shrink-0"
    />
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { CheckIcon } from '@heroicons/vue/24/outline'
import { FontManager } from '@/services/FontManager'
import type { FontFamily } from '@/services/FontManager'

interface Props {
  font: FontFamily
  selected: boolean
  previewText: string
}

const props = defineProps<Props>()

const isLoaded = computed(() => {
  return props.font.loaded || FontManager.isFontLoaded(props.font.family)
})

const isLoading = computed(() => {
  return props.font.loading || FontManager.isFontLoading(props.font.family)
})

const fontStack = computed(() => {
  return FontManager.getFontWithFallbacks(props.font.family)
})
</script>

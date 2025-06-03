<template>
  <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    <div
      v-for="suggestion in suggestions"
      :key="suggestion.id"
      class="group cursor-pointer bg-white rounded-lg border border-gray-200 hover:border-primary-300 hover:shadow-lg transition-all duration-200"
      @click="$emit('select', suggestion)"
    >
      <!-- Thumbnail -->
      <div class="aspect-[4/3] bg-gray-100 rounded-t-lg overflow-hidden">
        <img
          :src="suggestion.thumbnailUrl"
          :alt="suggestion.title"
          class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-200"
        />
      </div>

      <!-- Content -->
      <div class="p-4">
        <div class="flex items-start justify-between mb-2">
          <h3 class="font-medium text-gray-900 group-hover:text-primary-600 transition-colors">
            {{ suggestion.title }}
          </h3>
          <div class="flex items-center space-x-1 text-xs text-gray-500">
            <span class="bg-primary-100 text-primary-700 px-2 py-1 rounded">
              {{ suggestion.confidence }}% match
            </span>
          </div>
        </div>

        <p class="text-sm text-gray-600 mb-3 line-clamp-2">
          {{ suggestion.description }}
        </p>

        <!-- Tags -->
        <div class="flex flex-wrap gap-1 mb-3">
          <span
            v-for="tag in suggestion.tags.slice(0, 3)"
            :key="tag"
            class="text-xs bg-gray-100 text-gray-600 px-2 py-1 rounded"
          >
            {{ tag }}
          </span>
          <span
            v-if="suggestion.tags.length > 3"
            class="text-xs text-gray-500"
          >
            +{{ suggestion.tags.length - 3 }} more
          </span>
        </div>

        <!-- Dimensions and Category -->
        <div class="flex items-center justify-between text-xs text-gray-500">
          <span>{{ suggestion.width }} × {{ suggestion.height }}px</span>
          <span class="capitalize">{{ suggestion.category }}</span>
        </div>
      </div>

      <!-- Action Button -->
      <div class="p-4 pt-0">
        <button class="w-full bg-primary-600 text-white py-2 px-4 rounded-md hover:bg-primary-700 transition-colors text-sm font-medium">
          Create Design from Video
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import type { DesignSuggestion } from '@/types'

interface Props {
  suggestions: DesignSuggestion[]
}

interface Emits {
  (e: 'select', suggestion: DesignSuggestion): void
}

defineProps<Props>()
defineEmits<Emits>()
</script>

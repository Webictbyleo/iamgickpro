<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between" v-if="title">
      <div>
        <h2 class="text-xl font-semibold text-gray-900">{{ title }}</h2>
        <p v-if="subtitle" class="text-sm text-gray-600 mt-1">{{ subtitle }}</p>
      </div>
      <button
        v-if="showViewAll"
        class="inline-flex items-center text-sm font-medium text-gray-600 hover:text-gray-900 transition-colors"
        @click="$emit('viewAll')"
      >
        View all
        <ArrowRightIcon class="w-4 h-4 ml-1" />
      </button>
    </div>
    
    <!-- Templates Grid -->
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 2xl:grid-cols-6 gap-4">
      <div
        v-for="template in templates"
        :key="template.id"
        :class="[
          'group relative',
          disabled ? 'cursor-not-allowed opacity-60' : 'cursor-pointer'
        ]"
        @click="!disabled && handleTemplateClick(template)"
      >
        <!-- Template Card -->
        <div class="relative bg-white rounded-lg border border-gray-200 overflow-hidden hover:border-gray-300 hover:shadow-md transition-all duration-200">
          <!-- Template Thumbnail -->
          <div 
            class="relative bg-gray-50 overflow-hidden"
            :style="getTemplateCardStyle(template)"
          >
            <img
              v-if="template.thumbnail || template.thumbnailUrl"
              :src="template.thumbnail || template.thumbnailUrl"
              :alt="template.title"
              class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
              loading="lazy"
            />
            <div v-else class="w-full h-full flex items-center justify-center bg-gradient-to-br from-gray-50 to-gray-100">
              <component :is="icons.template" class="w-8 h-8 text-gray-300" />
            </div>
            
            <!-- Hover Overlay -->
            <div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-10 transition-opacity duration-200 flex items-center justify-center">
              <div class="opacity-0 group-hover:opacity-100 transition-opacity duration-200">
                <div class="bg-white text-gray-900 px-3 py-2 rounded-lg font-medium text-sm shadow-lg">
                  Use Template
                </div>
              </div>
            </div>
          </div>
          
          <!-- Template Info -->
          <div class="p-3">
            <h3 class="font-medium text-gray-900 text-sm leading-tight mb-1 truncate">
              {{ template.title || template.name }}
            </h3>
            
            <div class="flex items-center justify-between">
              <span class="text-xs text-gray-500 capitalize">{{ template.category }}</span>
              
              <!-- Usage count or rating -->
              <div v-if="template.usageCount > 0" class="flex items-center text-xs text-gray-400">
                <component :is="icons.users" class="w-3 h-3 mr-1" />
                {{ formatUsageCount(template.usageCount) }}
              </div>
              <div v-else-if="template.rating >= 4.0" class="flex items-center text-xs text-gray-400">
                <component :is="icons.star" class="w-3 h-3 mr-1 text-yellow-400" />
                {{ template.rating.toFixed(1) }}
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Loading States -->
      <div
        v-if="loading"
        v-for="i in loadingCount"
        :key="`loading-${i}`"
        class="animate-pulse"
      >
        <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
          <div class="aspect-[4/3] bg-gray-200"></div>
          <div class="p-3 space-y-2">
            <div class="h-4 bg-gray-200 rounded w-3/4"></div>
            <div class="h-3 bg-gray-200 rounded w-1/2"></div>
          </div>
        </div>
      </div>
    </div>

    <!-- Empty State -->
    <div v-if="!loading && templates.length === 0" class="text-center py-20">
      <div class="w-16 h-16 bg-gray-100 rounded-2xl flex items-center justify-center mx-auto mb-6">
        <component :is="icons.template" class="w-8 h-8 text-gray-400" />
      </div>
      <h3 class="text-lg font-semibold text-gray-900 mb-2">No templates found</h3>
      <p class="text-gray-500 max-w-sm mx-auto mb-6">
        {{ emptyStateMessage || "Try adjusting your search or explore our template categories" }}
      </p>
      <button
        v-if="showCreateButton"
        @click="$emit('createNew')"
        class="inline-flex items-center px-4 py-2 bg-gray-900 text-white font-medium rounded-lg hover:bg-gray-800 transition-colors"
      >
        <component :is="icons.plus" class="w-4 h-4 mr-2" />
        Create New Design
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ArrowRightIcon } from '@heroicons/vue/24/outline'
import type { Template } from '@/types'
import { useIcons } from '@/composables/useIconsNew'

const icons = useIcons()

interface Props {
  title?: string
  subtitle?: string
  templates: Template[]
  loading?: boolean
  loadingCount?: number
  showViewAll?: boolean
  emptyStateMessage?: string
  showCreateButton?: boolean
  disabled?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  title: '',
  subtitle: '',
  loading: false,
  loadingCount: 8,
  showViewAll: true,
  emptyStateMessage: '',
  showCreateButton: false,
  disabled: false
})

const emit = defineEmits<{
  select: [template: Template]
  viewAll: []
  createNew: []
}>()

// Methods
const handleTemplateClick = (template: Template) => {
  if (props.disabled) return
  emit('select', template)
}

const formatUsageCount = (count: number): string => {
  if (count >= 1000000) {
    return (count / 1000000).toFixed(1) + 'M'
  } else if (count >= 1000) {
    return (count / 1000).toFixed(1) + 'K'
  }
  return count.toString()
}

// Calculate dynamic template card style based on template dimensions
const getTemplateCardStyle = (template: Template) => {
  const width = template.width || 800
  const height = template.height || 600
  const aspectRatio = width / height
  
  // Standardize aspect ratios for better grid consistency
  let finalAspectRatio = aspectRatio
  
  // Group similar aspect ratios together for visual consistency
  if (aspectRatio > 2.5) {
    // Ultra-wide (banners) -> 3:1
    finalAspectRatio = 3
  } else if (aspectRatio > 1.8) {
    // Wide (landscape) -> 2:1
    finalAspectRatio = 2
  } else if (aspectRatio > 1.2) {
    // Standard landscape -> 4:3
    finalAspectRatio = 4/3
  } else if (aspectRatio > 0.9) {
    // Square-ish -> 1:1
    finalAspectRatio = 1
  } else if (aspectRatio > 0.6) {
    // Portrait -> 3:4
    finalAspectRatio = 3/4
  } else {
    // Tall portrait -> 2:3
    finalAspectRatio = 2/3
  }
  
  return {
    aspectRatio: finalAspectRatio.toString()
  }
}
</script>

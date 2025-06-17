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
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 2xl:grid-cols-6 gap-6">
      <div
        v-for="template in templates"
        :key="template.id"
        :class="[
          'group',
          disabled ? 'cursor-not-allowed opacity-60' : 'cursor-pointer'
        ]"
        @click="!disabled && handleTemplateClick(template)"
      >
        <!-- Template Card - Simplified and focused on thumbnail -->
        <div class="relative">
          <!-- Template Thumbnail - Main Focus -->
          <div class="relative aspect-[3/4] bg-gray-100 rounded-xl overflow-hidden shadow-sm hover:shadow-lg transition-all duration-300">
            <img
              v-if="template.thumbnail || template.thumbnailUrl"
              :src="template.thumbnail || template.thumbnailUrl"
              :alt="template.title"
              class="w-full h-full object-cover transition-transform duration-300 group-hover:scale-105"
              loading="lazy"
            />
            <div v-else class="w-full h-full flex items-center justify-center bg-gradient-to-br from-gray-50 to-gray-100">
              <component :is="icons.template" class="w-8 h-8 text-gray-400" />
            </div>
            
            <!-- Minimal Premium Badge -->
            <div v-if="template.isPremium" class="absolute top-2 right-2">
              <div class="w-6 h-6 bg-amber-400 rounded-full flex items-center justify-center shadow-sm">
                <component :is="icons.sparkle" class="w-3 h-3 text-white" />
              </div>
            </div>
          </div>
          
          <!-- Minimal Template Info -->
          <div class="mt-3 space-y-1">
            <h3 class="text-sm font-medium text-gray-900 truncate group-hover:text-gray-600 transition-colors">
              {{ template.title || template.name }}
            </h3>
            
            <!-- Simple Category -->
            <p class="text-xs text-gray-500 capitalize">{{ template.category }}</p>
            
            <!-- Optional Rating (only if notable) -->
            <div v-if="template.rating >= 4.0" class="flex items-center space-x-1">
              <component :is="icons.star" class="w-3 h-3 text-yellow-400" />
              <span class="text-xs text-gray-600">{{ template.rating.toFixed(1) }}</span>
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
        <div class="aspect-[3/4] bg-gray-200 rounded-xl"></div>
        <div class="mt-3 space-y-2">
          <div class="h-4 bg-gray-200 rounded w-3/4"></div>
          <div class="h-3 bg-gray-200 rounded w-1/2"></div>
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
</script>

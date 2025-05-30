<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between" v-if="title">
      <h2 class="text-2xl font-bold text-gray-900">{{ title }}</h2>
      <button
        v-if="showViewAll"
        class="inline-flex items-center text-sm text-blue-600 hover:text-blue-700 font-semibold hover:underline transition-colors"
        @click="$emit('viewAll')"
      >
        View all
        <ArrowRightIcon class="w-4 h-4 ml-1" />
      </button>
    </div>
    
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6 gap-6">
      <div
        v-for="template in templates"
        :key="template.id"
        class="group cursor-pointer"
        @click="$emit('select', template)"
      >
        <div class="relative aspect-video bg-gradient-to-br from-gray-100 to-gray-200 rounded-2xl overflow-hidden mb-3 shadow-sm hover:shadow-md transition-all duration-200">
          <img
            v-if="template.thumbnail"
            :src="template.thumbnail"
            :alt="template.title"
            class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
          />
          <div v-else class="w-full h-full flex items-center justify-center">
            <component :is="icons.template" class="w-8 h-8 text-gray-400" />
          </div>
          
          <!-- Premium badge -->
          <div v-if="template.isPremium" class="absolute top-3 right-3">
            <span class="inline-flex items-center px-2 py-1 rounded-lg text-xs font-semibold bg-gradient-to-r from-yellow-400 to-amber-400 text-white shadow-sm">
              <component :is="icons.sparkle" class="w-3 h-3 mr-1" />
              Pro
            </span>
          </div>
          
          <!-- Hover overlay -->
          <div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-20 transition-all duration-200 flex items-center justify-center opacity-0 group-hover:opacity-100">
            <button class="px-4 py-2 bg-white/90 backdrop-blur-sm text-gray-900 font-semibold rounded-xl shadow-lg hover:bg-white transition-all duration-200 transform hover:scale-105">
              Use Template
            </button>
          </div>
        </div>
        
        <div class="space-y-1">
          <h3 class="text-sm font-semibold text-gray-900 truncate group-hover:text-blue-600 transition-colors">{{ template.title }}</h3>
          <p class="text-xs text-gray-500">{{ template.category }}</p>
        </div>
      </div>

      <!-- Loading States -->
      <div
        v-if="loading"
        v-for="i in loadingCount"
        :key="`loading-${i}`"
        class="animate-pulse"
      >
        <div class="aspect-video bg-gradient-to-br from-gray-200 to-gray-300 rounded-2xl mb-3 shadow-sm"></div>
        <div class="space-y-2">
          <div class="h-4 bg-gray-200 rounded-lg w-4/5"></div>
          <div class="h-3 bg-gray-200 rounded-lg w-2/3"></div>
        </div>
      </div>
    </div>

    <!-- Empty State -->
    <div v-if="!loading && templates.length === 0" class="text-center py-16 bg-gradient-to-br from-gray-50 to-purple-50 rounded-2xl border border-gray-100">
      <div class="w-20 h-20 bg-purple-100 rounded-2xl flex items-center justify-center mx-auto mb-6">
        <component :is="icons.template" class="w-10 h-10 text-purple-600" />
      </div>
      <h3 class="text-xl font-semibold text-gray-900 mb-3">No templates available</h3>
      <p class="text-gray-600 max-w-sm mx-auto">Check back later for new professional templates to get you started quickly</p>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ArrowRightIcon } from '@heroicons/vue/24/outline'
import type { Template } from '@/types'
import { useIcons } from '@/composables/useIcons'

const icons = useIcons()

interface Props {
  title?: string
  templates: Template[]
  loading?: boolean
  loadingCount?: number
  showViewAll?: boolean
}

withDefaults(defineProps<Props>(), {
  title: '',
  loading: false,
  loadingCount: 6,
  showViewAll: true
})

defineEmits<{
  select: [template: Template]
  viewAll: []
}>()
</script>

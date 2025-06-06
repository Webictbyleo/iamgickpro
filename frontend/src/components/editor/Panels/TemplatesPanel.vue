<template>
  <div class="h-full flex flex-col">
    <!-- Search Bar -->
    <div class="p-3 border-b border-gray-200">
      <div class="relative">
        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
          <MagnifyingGlassIcon class="h-4 w-4 text-gray-400" />
        </div>
        <input
          v-model="searchQuery"
          type="text"
          placeholder="Search templates..."
          class="block w-full pl-9 pr-3 py-2 border border-gray-300 rounded-lg text-sm placeholder-gray-500 focus:outline-none focus:ring-1 focus:ring-purple-500 focus:border-purple-500"
        />
      </div>
    </div>

    <!-- Category Filter -->
    <div class="p-3 border-b border-gray-200">
      <select
        v-model="selectedCategory"
        class="block w-full px-3 py-2 border border-gray-300 rounded-lg text-sm bg-white focus:outline-none focus:ring-1 focus:ring-purple-500 focus:border-purple-500"
      >
        <option value="">All Categories</option>
        <option value="social-media">Social Media</option>
        <option value="presentation">Presentation</option>
        <option value="marketing">Marketing</option>
        <option value="print">Print</option>
        <option value="document">Document</option>
        <option value="logo">Logo</option>
        <option value="web-graphics">Web Graphics</option>
      </select>
    </div>

    <!-- Templates Grid -->
    <div class="flex-1 overflow-y-auto p-3">
      <!-- Loading State -->
      <div v-if="isLoading" class="grid grid-cols-2 gap-3">
        <div
          v-for="i in 6"
          :key="i"
          class="animate-pulse"
        >
          <div class="aspect-video bg-gray-200 rounded-lg mb-2"></div>
          <div class="h-3 bg-gray-200 rounded w-3/4 mb-1"></div>
          <div class="h-2 bg-gray-200 rounded w-1/2"></div>
        </div>
      </div>

      <!-- Templates List -->
      <div v-else-if="templates.length > 0" class="grid grid-cols-2 gap-3">
        <div
          v-for="template in templates"
          :key="template.id"
          class="group cursor-pointer"
          @click="useTemplate(template)"
        >
          <div class="relative aspect-video bg-gradient-to-br from-gray-100 to-gray-200 rounded-lg overflow-hidden mb-2 hover:shadow-md transition-all duration-200">
            <img
              v-if="template.thumbnailUrl"
              :src="template.thumbnailUrl"
              :alt="template.name"
              class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
            />
            <div v-else class="w-full h-full flex items-center justify-center">
              <DocumentDuplicateIcon class="w-6 h-6 text-gray-400" />
            </div>
            
            <!-- Premium badge -->
            <div v-if="template.isPremium" class="absolute top-2 right-2">
              <span class="inline-flex items-center px-2 py-1 rounded-md text-xs font-semibold bg-gradient-to-r from-yellow-400 to-amber-400 text-white shadow-sm">
                <SparklesIcon class="w-3 h-3 mr-1" />
                Pro
              </span>
            </div>
            
            <!-- Hover overlay -->
            <div class="absolute inset-0 bg-black/0 group-hover:bg-black/10 transition-colors duration-200 flex items-center justify-center opacity-0 group-hover:opacity-100">
              <div class="bg-white/90 backdrop-blur-sm rounded-lg px-3 py-2 text-sm font-medium text-gray-900">
                Use Template
              </div>
            </div>
          </div>
          
          <div class="space-y-1">
            <h4 class="text-sm font-medium text-gray-900 truncate">{{ template.name }}</h4>
            <div class="flex items-center justify-between text-xs text-gray-500">
              <span>{{ template.category?.replace('-', ' ') || 'General' }}</span>
              <span>{{ template.usageCount || 0 }} uses</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Empty State -->
      <div v-else class="text-center py-12">
        <div class="w-16 h-16 mx-auto mb-4 bg-gray-100 rounded-full flex items-center justify-center">
          <DocumentDuplicateIcon class="w-8 h-8 text-gray-400" />
        </div>
        <h3 class="text-lg font-medium text-gray-900 mb-2">
          No Templates Found
        </h3>
        <p class="text-sm text-gray-500">
          Try adjusting your search or category filter
        </p>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, watch } from 'vue'
import { MagnifyingGlassIcon, DocumentDuplicateIcon, SparklesIcon } from '@heroicons/vue/24/outline'
import { templateAPI } from '@/services/api'
import type { Template } from '@/types'

interface TemplatesPanelProps {}

const emit = defineEmits<{
  'use-template': [template: Template]
}>()

// State
const searchQuery = ref('')
const selectedCategory = ref('')
const templates = ref<Template[]>([])
const isLoading = ref(false)

// Debounce timer for search
let searchTimeout: ReturnType<typeof setTimeout> | null = null

// Load templates
const loadTemplates = async (resetList = false) => {
  try {
    isLoading.value = true
    
    const params: any = {
      page: 1,
      limit: 50 // Load more for the panel
    }

    // Use search API if there's a query or category filter
    if (searchQuery.value || selectedCategory.value) {
      if (searchQuery.value) params.q = searchQuery.value
      if (selectedCategory.value) params.category = selectedCategory.value
      
      const response = await templateAPI.searchTemplates(params)
      
      if (response.data?.data?.templates) {
        templates.value = response.data.data.templates
      }
    } else {
      // Use regular getTemplates for no filters
      const response = await templateAPI.getTemplates(params)
      
      if (response.data?.data?.templates) {
        templates.value = response.data.data.templates
      }
    }
  } catch (error) {
    console.error('Failed to load templates:', error)
    templates.value = []
  } finally {
    isLoading.value = false
  }
}

// Use template
const useTemplate = (template: Template) => {
  emit('use-template', template)
}

// Watch for search query changes with debounce
watch(searchQuery, (newQuery) => {
  if (searchTimeout) {
    clearTimeout(searchTimeout)
  }
  
  searchTimeout = setTimeout(() => {
    loadTemplates(true)
  }, 300)
})

// Watch for category changes
watch(selectedCategory, () => {
  loadTemplates(true)
})

// Load templates on mount
onMounted(() => {
  loadTemplates(true)
})
</script>

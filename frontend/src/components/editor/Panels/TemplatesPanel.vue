<template>
  <div class="h-full flex flex-col">
    <!-- Header -->
    <div class="p-4 border-b border-gray-200 bg-white">
      <h3 class="text-lg font-semibold text-gray-900">Design Templates</h3>
      <p class="text-sm text-gray-600 mt-1">Choose from professional templates to get started</p>
    </div>

    <!-- Enhanced Search and Filter Section -->
    <div class="p-4 border-b border-gray-200 bg-white">
      <div class="space-y-3">
        <!-- Enhanced Search Input -->
        <div class="relative">
          <input
            v-model="searchQuery"
            type="text"
            placeholder="Search templates..."
            class="w-full pl-10 pr-10 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-all duration-200 bg-gray-50 focus:bg-white"
            @keydown.enter="handleSearch"
          />
          <MagnifyingGlassIcon class="absolute left-3 top-3.5 h-5 w-5 text-gray-400" />
          <!-- Clear search button -->
          <button
            v-if="searchQuery"
            @click="clearSearch"
            class="absolute right-3 top-3.5 h-5 w-5 text-gray-400 hover:text-gray-600 transition-colors"
          >
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <!-- Search Stats -->
        <div v-if="searchQuery && !isLoading" class="flex items-center justify-between text-xs text-gray-500">
          <span>{{ templates.length }} results for "{{ searchQuery }}"</span>
          <span v-if="templates.length > 0" class="text-purple-600">{{ templates.length }} templates found</span>
        </div>

        <!-- Enhanced Category Filter -->
        <div class="relative">
          <select
            v-model="selectedCategory"
            class="w-full px-4 py-3 border border-gray-300 rounded-lg text-sm bg-gray-50 focus:bg-white focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-all duration-200 appearance-none cursor-pointer"
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
          <!-- Custom dropdown arrow -->
          <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
            </svg>
          </div>
        </div>
      </div>
    </div>

    <!-- Scrollable Content Area -->
    <div class="flex-1 overflow-y-auto">
      <!-- Enhanced Loading State -->
      <div v-if="isLoading" class="space-y-4">
        <div class="text-center py-8">
          <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-purple-600 mx-auto"></div>
          <p class="mt-2 text-sm text-gray-600">Loading templates...</p>
        </div>
        <!-- Loading skeleton -->
        <div class="px-4">
          <div class="grid grid-cols-2 gap-3">
            <div v-for="i in 6" :key="i" class="space-y-2">
              <div class="aspect-video bg-gray-200 rounded-lg animate-pulse"></div>
              <div class="h-3 bg-gray-200 rounded w-3/4 animate-pulse"></div>
              <div class="h-2 bg-gray-200 rounded w-1/2 animate-pulse"></div>
            </div>
          </div>
        </div>
      </div>

      <!-- Enhanced Empty State -->
      <div v-else-if="templates.length === 0" class="text-center py-12">
        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
        </svg>
        <h3 class="mt-2 text-sm font-medium text-gray-900">
          {{ searchQuery || selectedCategory ? 'No templates found' : 'No templates available' }}
        </h3>
        <p class="mt-1 text-sm text-gray-500">
          {{ searchQuery || selectedCategory 
            ? 'Try adjusting your search terms or category filter' 
            : 'Templates will appear here when available'
          }}
        </p>
        <button
          v-if="searchQuery || selectedCategory"
          @click="clearAllFilters"
          class="mt-4 px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors text-sm"
        >
          Clear Filters
        </button>
      </div>

      <!-- Enhanced Templates Grid -->
      <div v-else class="p-4 space-y-4">
        <!-- Results header -->
        <div v-if="searchQuery || selectedCategory" class="flex items-center justify-between">
          <h4 class="text-sm font-medium text-gray-900">
            {{ templates.length }} Templates
            <span v-if="selectedCategory" class="text-gray-500">in {{ selectedCategory.replace('-', ' ') }}</span>
          </h4>
          <button
            @click="clearAllFilters"
            class="text-xs text-gray-500 hover:text-gray-700 transition-colors"
          >
            Clear filters
          </button>
        </div>

        <!-- Enhanced Grid -->
        <div class="grid grid-cols-2 gap-3">
          <div
            v-for="template in templates"
            :key="template.id"
            class="group cursor-pointer"
            @click="useTemplate(template)"
          >
            <div class="relative aspect-video bg-gradient-to-br from-gray-100 to-gray-200 rounded-lg overflow-hidden mb-2 hover:shadow-lg transition-all duration-200 border border-gray-200 hover:border-purple-300 transform hover:scale-[1.02]">
              <img
                v-if="template.thumbnailUrl"
                :src="template.thumbnailUrl"
                :alt="template.name"
                class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                loading="lazy"
                @error="handleImageError"
              />
              <div v-else class="w-full h-full flex items-center justify-center bg-gradient-to-br from-purple-50 to-purple-100">
                <DocumentDuplicateIcon class="w-8 h-8 text-purple-400" />
              </div>
              
              <!-- Premium badge -->
              <div v-if="template.isPremium" class="absolute top-2 right-2">
                <span class="inline-flex items-center px-2 py-1 rounded-md text-xs font-semibold bg-gradient-to-r from-yellow-400 to-amber-400 text-white shadow-sm">
                  <SparklesIcon class="w-3 h-3 mr-1" />
                  Pro
                </span>
              </div>
              
              <!-- Enhanced hover overlay -->
              <div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-20 transition-all duration-200 flex items-center justify-center">
                <div class="bg-white rounded-full p-2 opacity-0 group-hover:opacity-100 transition-opacity shadow-lg">
                  <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                  </svg>
                </div>
              </div>
            </div>
            
            <!-- Enhanced template info -->
            <div class="space-y-1">
              <h4 class="text-sm font-medium text-gray-900 truncate group-hover:text-purple-600 transition-colors">{{ template.name }}</h4>
              <div class="flex items-center justify-between text-xs text-gray-500">
                <span class="capitalize">{{ template.category?.replace('-', ' ') || 'General' }}</span>
                <div class="flex items-center space-x-1">
                  <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                  </svg>
                  <span>{{ template.usageCount || 0 }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>
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
const searchTimeout = ref<NodeJS.Timeout | null>(null)

// Enhanced search functionality
const handleSearch = () => {
  if (!searchQuery.value.trim() && !selectedCategory.value) return
  loadTemplates(true)
}

const clearSearch = () => {
  searchQuery.value = ''
}

const clearAllFilters = () => {
  searchQuery.value = ''
  selectedCategory.value = ''
  loadTemplates(true)
}

// Error handling
const handleImageError = (event: Event) => {
  const target = event.target as HTMLImageElement
  target.style.display = 'none'
  const parent = target.parentElement
  if (parent) {
    // Show a fallback icon
    parent.innerHTML = `
      <div class="w-full h-full flex items-center justify-center bg-gradient-to-br from-purple-50 to-purple-100">
        <svg class="w-8 h-8 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
        </svg>
      </div>
    `
  }
}

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
      } else {
        templates.value = []
      }
    } else {
      // Use regular getTemplates for no filters
      const response = await templateAPI.getTemplates(params)
      
      if (response.data?.data?.templates) {
        templates.value = response.data.data.templates
      } else {
        templates.value = []
      }
    }
  } catch (error) {
    console.error('Failed to load templates:', error)
    templates.value = []
    // Could add a toast notification here for better UX
  } finally {
    isLoading.value = false
  }
}

// Use template
const useTemplate = (template: Template) => {
  emit('use-template', template)
}

// Enhanced search with debouncing
watch(searchQuery, (newQuery) => {
  if (searchTimeout.value) {
    clearTimeout(searchTimeout.value)
  }
  
  searchTimeout.value = setTimeout(() => {
    loadTemplates(true)
  }, 500) // 500ms debounce
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

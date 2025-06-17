<template>
  <AppLayout>
    <div class="space-y-6">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">Templates</h1>
          <p class="text-gray-600 mt-1">Choose from professional templates to get started quickly</p>
        </div>
        
        <!-- Search and Filters -->
        <div class="flex items-center space-x-4">
          <div class="relative">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
              <MagnifyingGlassIcon class="h-5 w-5 text-gray-400" />
            </div>
            <input
              v-model="searchQuery"
              type="text"
              placeholder="Search templates..."
              class="block w-80 pl-10 pr-3 py-2 border border-gray-300 rounded-md leading-5 bg-white placeholder-gray-500 focus:outline-none focus:placeholder-gray-400 focus:ring-1 focus:ring-primary-500 focus:border-primary-500"
            />
          </div>
          
          <select
            v-model="selectedCategory"
            class="block px-3 py-2 border border-gray-300 rounded-md bg-white focus:outline-none focus:ring-1 focus:ring-primary-500 focus:border-primary-500"
          >
            <option value="">All Categories</option>
            <option value="business">Business</option>
            <option value="social">Social Media</option>
            <option value="marketing">Marketing</option>
            <option value="presentation">Presentation</option>
          </select>
        </div>
      </div>

      <!-- Templates Grid -->
      <TemplateGrid
        title=""
        subtitle="Discover professional templates to jumpstart your creative projects"
        :templates="templates"
        :loading="isLoading"
        :loading-count="8"
        :show-view-all="false"
        :empty-state-message="'Try adjusting your search criteria or browse our featured templates'"
        :show-create-button="true"
        :disabled="isUsingTemplate"
        @select="handleTemplateSelected"
        @createNew="handleCreateNew"
      />
      
      <!-- Using Template Loading Overlay -->
      <div v-if="isUsingTemplate" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg p-6 max-w-sm mx-4 text-center">
          <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mx-auto mb-4"></div>
          <h3 class="text-lg font-semibold text-gray-900 mb-2">Creating Design</h3>
          <p class="text-gray-600">Please wait while we set up your template...</p>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import { ref, watch, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { MagnifyingGlassIcon } from '@heroicons/vue/24/outline'
import AppLayout from '@/components/layout/AppLayout.vue'
import TemplateGrid from '@/components/ui/TemplateGrid.vue'
import { useDesignStore } from '@/stores/design'
import { templateAPI } from '@/services/api'
import type { Template } from '@/types'

const router = useRouter()
const designStore = useDesignStore()

const searchQuery = ref('')
const selectedCategory = ref('')
const templates = ref<Template[]>([])
const isLoading = ref(false)
const isUsingTemplate = ref(false)
const currentPage = ref(1)
const hasMorePages = ref(true)

// Debounce timer for search
let searchTimeout: ReturnType<typeof setTimeout> | null = null

const loadTemplates = async (resetList = false) => {
  try {
    isLoading.value = true
    
    // Reset page if this is a new search
    if (resetList) {
      currentPage.value = 1
      templates.value = []
    }

    const params: any = {
      page: currentPage.value,
      limit: 20
    }

    // Use search API if there's a query or category filter
    if (searchQuery.value || selectedCategory.value) {
      if (searchQuery.value) params.q = searchQuery.value
      if (selectedCategory.value) params.category = selectedCategory.value
      
      const response = await templateAPI.searchTemplates(params)
      
      if (response.data) {
        const newTemplates = response.data.data.templates
        templates.value = resetList ? newTemplates : [...templates.value, ...newTemplates]
        hasMorePages.value = response.data.data.pagination.page < response.data.data.pagination.totalPages
      }
    } else {
      // Use regular getTemplates for no filters
      const response = await templateAPI.getTemplates(params)
      
      if (response.data) {
        const newTemplates = response.data.data.templates
        templates.value = resetList ? newTemplates : [...templates.value, ...newTemplates]
        hasMorePages.value = response.data.data.pagination.page < response.data.data.pagination.totalPages
      }
    }
  } catch (error) {
    console.error('Failed to load templates:', error)
    if (resetList) {
      templates.value = []
    }
  } finally {
    isLoading.value = false
  }
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

onMounted(() => {
  loadTemplates(true)
})

const handleTemplateSelected = async (template: Template) => {
  if (isUsingTemplate.value) return // Prevent multiple clicks
  
  try {
    isUsingTemplate.value = true
    
    // Show a loading notification
    const notification = {
      id: Date.now(),
      type: 'info' as const,
      title: 'Creating design...',
      message: `Using template: ${template.name}`,
      duration: 0 // Don't auto-dismiss
    }
    
    // Use the template API to create a design from template
    const response = await templateAPI.useTemplate(template.uuid, {
      name: `${template.name} Copy`
    })
    
    if (response.data?.success && response.data.data) {
      // Success notification
      console.log('Template used successfully, navigating to editor...')
      
      // Navigate to editor with the new design
      router.push(`/editor/${response.data.data.id}`)
    } else {
      throw new Error('Failed to create design from template')
    }
  } catch (error) {
    console.error('Failed to create design from template:', error)
    
    // Show error notification
    console.error('Template application failed, trying fallback method...')
    
    // Fallback to manual creation if API fails
    try {
      const newDesign = designStore.createNewDesign(
        template.width || 800,
        template.height || 600
      )
      
      if (template.designData) {
        newDesign.data = { ...template.designData }
      }
      
      newDesign.name = `${template.name} Copy`
      
      const result = await designStore.saveDesign(newDesign, true)
      
      if (result.success) {
        console.log('Fallback template creation successful')
        router.push(`/editor/${newDesign.id}`)
      } else {
        console.error('Failed to save design:', result.error)
        throw new Error('Failed to save design')
      }
    } catch (fallbackError) {
      console.error('Fallback creation also failed:', fallbackError)
      // Show final error notification
      console.error('Both template application methods failed')
    }
  } finally {
    isUsingTemplate.value = false
  }
}

const handleCreateNew = () => {
  // Navigate to create new design page
  router.push('/editor')
}
</script>

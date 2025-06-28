<template>
  <AppLayout>
    <div class="space-y-6">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">Templates</h1>
          <p class="text-gray-600 mt-1">Choose from professional templates to get started quickly</p>
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
        :show-admin-controls="authStore.isAdmin"
        :disabled="isUsingTemplate"
        @select="handleTemplateSelected"
        @createNew="handleCreateNew"
        @delete="handleDeleteTemplate"
      />

      <!-- Pagination -->
      <Pagination
        v-if="totalPages > 1"
        :current-page="currentPage"
        :total-pages="totalPages"
        @page-change="handlePageChange"
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
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import AppLayout from '@/components/layout/AppLayout.vue'
import Pagination from '@/components/common/Pagination.vue'
import TemplateGrid from '@/components/ui/TemplateGrid.vue'
import { useDesignStore } from '@/stores/design'
import { useAuthStore } from '@/stores/auth'
import { templateAPI } from '@/services/api'
import type { Template, TemplateSearchParams } from '@/types'

const router = useRouter()
const designStore = useDesignStore()
const authStore = useAuthStore()

const templates = ref<Template[]>([])
const isLoading = ref(false)
const isUsingTemplate = ref(false)
const currentPage = ref(1)
const totalPages = ref(1)
const totalResults = ref(0)
const hasMorePages = ref(true)

const loadTemplates = async (resetPage = false) => {
  try {
    isLoading.value = true
    
    // Reset to page 1 if this is a new search/filter change
    if (resetPage) {
      currentPage.value = 1
    }

    const params: TemplateSearchParams = {
      page: currentPage.value,
      limit: 22
    }

    // Use regular getTemplates for all templates
    const response = await templateAPI.getTemplates(params)
    
    if (response.data) {
      templates.value = response.data.data.templates
      totalPages.value = response.data.data.pagination?.totalPages || 1
      totalResults.value = response.data.data.pagination?.total || templates.value.length
      hasMorePages.value = response.data.data.pagination ? response.data.data.pagination.page < response.data.data.pagination.totalPages : false
    }
  } catch (error) {
    console.error('Failed to load templates:', error)
    // Reset templates on error
    templates.value = []
    totalPages.value = 1
    totalResults.value = 0
  } finally {
    isLoading.value = false
  }
}

onMounted(() => {
  loadTemplates()
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

const handleDeleteTemplate = async (template: Template) => {
  if (!authStore.isAdmin) {
    console.error('Only admin users can delete templates')
    return
  }

  if (!confirm(`Are you sure you want to delete "${template.name}"? This action cannot be undone.`)) {
    return
  }

  try {
    await templateAPI.deleteTemplate(template.uuid)
    // Remove the template from the local array
    templates.value = templates.value.filter(t => t.uuid !== template.uuid)
    totalResults.value = Math.max(0, totalResults.value - 1)
    console.log('Template deleted successfully')
  } catch (error) {
    console.error('Failed to delete template:', error)
  }
}

// Handle page change for pagination
const handlePageChange = (page: number) => {
  currentPage.value = page
  loadTemplates() // Load new page data from server
}
</script>

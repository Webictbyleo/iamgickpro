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
        :templates="filteredTemplates"
        :loading="isLoading"
        :loading-count="8"
        :show-view-all="false"
        @select="handleTemplateSelected"
      />
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
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

const filteredTemplates = computed(() => {
  let filtered = templates.value
  
  if (searchQuery.value) {
    filtered = filtered.filter(template =>
      template.title.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
      template.description?.toLowerCase().includes(searchQuery.value.toLowerCase())
    )
  }
  
  if (selectedCategory.value) {
    filtered = filtered.filter(template => template.category === selectedCategory.value)
  }
  
  return filtered
})

const loadTemplates = async () => {
  try {
    isLoading.value = true
    const response = await templateAPI.getTemplates({
      page: 1,
      limit: 50
    })
    
    if (response.data) {
      templates.value = response.data.data
    }
  } catch (error) {
    console.error('Failed to load templates:', error)
    templates.value = []
  } finally {
    isLoading.value = false
  }
}

onMounted(() => {
  loadTemplates()
})

const handleTemplateSelected = async (template: Template) => {
  // Create new design from template
  const newDesign = designStore.createNewDesign(
    template.dimensions.width,
    template.dimensions.height
  )
  
  // Copy template data to design
  if (template.designData) {
    newDesign.designData = { ...template.designData }
  }
  
  newDesign.name = `${template.name} Copy`
  
  // Save the design first
  const result = await designStore.saveDesign(newDesign)
  
  if (result.success) {
    // Navigate to editor
    router.push(`/editor/${newDesign.id}`)
  } else {
    console.error('Failed to create design from template:', result.error)
  }
}
</script>

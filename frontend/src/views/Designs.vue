<template>
  <AppLayout
    title="My Designs"
    subtitle="Manage all your creative projects"
    @search="handleSearch">
  
    <div class="p-6">
      <!-- Filter and Sort Bar -->
      <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-4 mb-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between space-y-4 sm:space-y-0">
          <div class="flex items-center space-x-4">
            <select
              v-model="selectedCategory"
              class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500"
            >
              <option value="">All Categories</option>
              <option value="social-media">Social Media</option>
              <option value="business">Business</option>
              <option value="marketing">Marketing</option>
              <option value="personal">Personal</option>
            </select>
            
            <select
              v-model="sortBy"
              class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500"
            >
              <option value="updated_at">Recently Updated</option>
              <option value="created_at">Recently Created</option>
              <option value="name">Name A-Z</option>
            </select>
          </div>
          
          <div class="flex items-center space-x-2">
            <button
              @click="viewMode = 'grid'"
              :class="[
                'p-2 rounded-md',
                viewMode === 'grid' ? 'bg-primary-100 dark:bg-primary-900 text-primary-600 dark:text-primary-400' : 'text-gray-400 dark:text-gray-500 hover:text-gray-600 dark:hover:text-gray-400'
              ]"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path>
              </svg>
            </button>
            <button
              @click="viewMode = 'list'"
              :class="[
                'p-2 rounded-md',
                viewMode === 'list' ? 'bg-primary-100 dark:bg-primary-900 text-primary-600 dark:text-primary-400' : 'text-gray-400 dark:text-gray-500 hover:text-gray-600 dark:hover:text-gray-400'
              ]"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"></path>
              </svg>
            </button>
          </div>
        </div>
      </div>

      <!-- Designs Content -->
      <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        <!-- Results Summary -->
        <div v-if="!loading && totalResults > 0" class="text-sm text-gray-600 dark:text-gray-300 mb-4">
          Showing {{ designs.length }} of {{ totalResults }} designs
        </div>
        
        <!-- Grid View -->
        <div v-if="viewMode === 'grid'">
          <!-- Loading State -->
          <div v-if="loading" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4">
            <div 
              v-for="i in 12"
              :key="`loading-${i}`"
              class="bg-white dark:bg-gray-800 rounded-lg border border-gray-100 dark:border-gray-700 animate-pulse overflow-hidden flex flex-col"
            >
              <div class="h-40 bg-gray-200 dark:bg-gray-700"></div>
              <div class="p-3 space-y-2">
                <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-3/4"></div>
                <div class="flex justify-between items-center">
                  <div class="h-3 bg-gray-200 dark:bg-gray-700 rounded w-1/3"></div>
                  <div class="h-5 bg-gray-200 dark:bg-gray-700 rounded w-16"></div>
                </div>
              </div>
            </div>
          </div>
          
          <!-- Designs Grid -->
          <div v-else class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4">
            <div
              v-for="design in filteredDesigns"
              :key="design.id"
              class="group"
            >
              <!-- Design Card with Compact Height -->
              <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-100 dark:border-gray-700 hover:border-gray-200 dark:hover:border-gray-600 transition-colors overflow-hidden flex flex-col">
                <!-- Design Thumbnail Container - Responsive height -->
                <div class="relative h-40 bg-gray-50 dark:bg-gray-700 flex items-center justify-center p-2">
                  <div 
                    v-if="design.thumbnail"
                    class="relative max-w-full max-h-full"
                    :style="{
                      aspectRatio: `${design.width || 1}/${design.height || 1}`,
                      width: design.width > design.height ? '100%' : 'auto',
                      height: design.height > design.width ? '100%' : 'auto'
                    }"
                  >
                    <img
                      :src="design.thumbnail"
                      :alt="design.title"
                      class="w-full h-full object-cover rounded shadow-sm cursor-pointer"
                      loading="lazy"
                      @click="editDesign(design)"
                    />
                  </div>
                  <div 
                    v-else 
                    class="w-32 h-24 bg-gray-100 rounded flex items-center justify-center cursor-pointer"
                    @click="editDesign(design)"
                  >
                    <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                  </div>
                  
                  <!-- Context Dropdown Menu -->
                  <div class="absolute top-2 right-2">
                    <ContextDropdown
                      :actions="getDesignActions(design)"
                      :title="design.name"
                      :subtitle="`${design.width}×${design.height} • ${getFileTypeLabel(design)}`"
                      position="auto"
                      trigger-class="opacity-0 group-hover:opacity-100"
                      @action="handleDropdownAction($event, design)"
                    />
                  </div>
                </div>
                
                <!-- Design Info - Compact layout -->
                <div class="p-3">
                  <h3 class="font-medium text-gray-900 dark:text-white truncate text-sm cursor-pointer hover:text-primary-600 dark:hover:text-primary-400 transition-colors mb-1" @click="editDesign(design)">
                    {{ design.title || design.name || 'Untitled Design' }}
                  </h3>
                  <div class="flex items-center justify-between">
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                      {{ formatDate(design.updatedAt || design.createdAt) }}
                    </p>
                    <span class="inline-block px-2 py-1 bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 text-xs rounded">
                      {{ design.width }}×{{ design.height }}
                    </span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- List View -->
        <div v-else-if="viewMode === 'list'" class="space-y-4">
          <div
            v-for="design in filteredDesigns"
            :key="design.id"
            class="flex items-center p-4 border border-gray-200 dark:border-gray-700 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer"
            @click="openDesign(design)"
          >
            <div class="w-16 h-16 bg-gray-100 dark:bg-gray-700 rounded-lg flex-shrink-0 overflow-hidden">
              <img
                v-if="design.thumbnail"
                :src="design.thumbnail"
                :alt="design.title"
                class="w-full h-full object-cover"
              />
              <div v-else class="w-full h-full flex items-center justify-center">
                <svg class="w-6 h-6 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                </svg>
              </div>
            </div>
            
            <div class="ml-4 flex-1">
              <h3 class="font-semibold text-gray-900 dark:text-white">{{ design.title }}</h3>
              <p class="text-sm text-gray-500 dark:text-gray-400">{{ design.width }}×{{ design.height }}</p>
              <p class="text-sm text-gray-500 dark:text-gray-400">Updated {{ formatDate(design.updatedAt) }}</p>
            </div>
            
            <div class="flex items-center space-x-2">
              <button
                @click.stop="editDesign(design)"
                class="p-2 text-gray-400 dark:text-gray-500 hover:text-primary-600 dark:hover:text-primary-400 rounded-md hover:bg-primary-50 dark:hover:bg-primary-900/20"
              >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path>
                </svg>
              </button>
              <button
                @click.stop="duplicateDesign(design)"
                class="p-2 text-gray-400 dark:text-gray-500 hover:text-green-600 dark:hover:text-green-400 rounded-md hover:bg-green-50 dark:hover:bg-green-900/20"
              >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                </svg>
              </button>
              <button
                @click.stop="downloadDesign(design)"
                class="p-2 text-gray-400 dark:text-gray-500 hover:text-purple-600 dark:hover:text-purple-400 rounded-md hover:bg-purple-50 dark:hover:bg-purple-900/20"
                title="Download"
              >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                </svg>
              </button>
              <button
                @click.stop="deleteDesign(design)"
                class="p-2 text-gray-400 dark:text-gray-500 hover:text-red-600 dark:hover:text-red-400 rounded-md hover:bg-red-50 dark:hover:bg-red-900/20"
              >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                </svg>
              </button>
            </div>
          </div>
        </div>

        <!-- Empty State -->
        <div v-if="!loading && designs.length === 0" class="text-center py-12">
          <div class="text-gray-400 dark:text-gray-500 text-lg mb-4">No designs found</div>
          <p class="text-gray-600 dark:text-gray-300 mb-6">
            {{ searchQuery ? 'Try adjusting your search criteria' : 'No designs available yet' }}
          </p>
        </div>
      </div>

      <!-- Pagination -->
      <Pagination
        v-if="totalPages > 1"
        :current-page="currentPage"
        :total-pages="totalPages"
        @page-change="handlePageChange"
      />
    </div>

    <!-- Export Modal -->
    <CompactDesignExportModal
      :is-open="isExportModalOpen"
      :design="selectedDesignForExport"
      @close="handleExportModalClose"
      @exported="handleExportComplete"
    />

    <!-- Convert to Template Modal -->
    <ConvertToTemplateModal
      ref="convertModal"
      :is-open="isConvertModalOpen"
      :design="selectedDesignForConversion"
      @close="closeConvertModal"
      @convert="handleConversionSubmit"
    />
  </AppLayout>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useDesignStore } from '@/stores/design'
import { useAuthStore } from '@/stores/auth'
import type { Design, DesignSearchParams, SearchResult } from '@/types'
import { designAPI } from '@/services/api'
import type { DropdownAction } from '@/components/common/ContextDropdown.vue'

// Icons
import { 
  PencilIcon, 
  DocumentDuplicateIcon, 
  ArrowDownTrayIcon, 
  RectangleStackIcon, 
  TrashIcon 
} from '@heroicons/vue/24/outline'

// Components
import AppLayout from '@/components/layout/AppLayout.vue'
import Pagination from '@/components/common/Pagination.vue'
import ContextDropdown from '@/components/common/ContextDropdown.vue'
import CompactDesignExportModal from '@/components/modals/CompactDesignExportModal.vue'
import ConvertToTemplateModal from '@/components/modals/ConvertToTemplateModal.vue'

const router = useRouter()
const designStore = useDesignStore()
const authStore = useAuthStore()

// State
const loading = ref(false)
const viewMode = ref<'grid' | 'list'>('grid')
const selectedCategory = ref('')
const sortBy = ref('updated_at')
const searchQuery = ref('')
const currentPage = ref(1)
const totalPages = ref(1)
const totalResults = ref(0)
const designs = ref<Design[]>([])

// Dropdown state


// Export modal state
const isExportModalOpen = ref(false)
const selectedDesignForExport = ref<SearchResult | null>(null)

// Convert to template modal state
const isConvertModalOpen = ref(false)
const selectedDesignForConversion = ref<Design | null>(null)
const convertModal = ref<InstanceType<typeof ConvertToTemplateModal> | null>(null)

// Methods
const loadDesigns = async (resetPage = false) => {
  try {
    loading.value = true
    
    // Reset to page 1 if this is a new search/filter change
    if (resetPage) {
      currentPage.value = 1
    }

    const params = {
      sort: sortBy.value as 'name' | 'created_at' | 'updated_at',
      page: currentPage.value,
      limit: 12,
    }

    let response;

    // Use searchDesigns if there's a search query, otherwise use getDesigns
    if (searchQuery.value.trim()) {
      // Use search API for text-based search
      const searchParams = {
        q: searchQuery.value.trim(),
        page: currentPage.value,
        limit: 12,
        sort: sortBy.value as 'name' | 'created_at' | 'updated_at'
      }
      console.log('Searching designs with params:', searchParams)
      response = await designAPI.searchDesigns(searchParams)
    } else {
      // Use regular getDesigns for listing with filters
      console.log('Loading designs with params:', params)
      response = await designAPI.getDesigns(params)
    }
    
    console.log('Designs API response:', {
      dataLength: response.data?.data?.length || 0,
      pagination: response.data?.pagination,
      currentPage: currentPage.value
    })
    
    if (response.data?.data) {
      designs.value = response.data.data
      if (response.data.pagination) {
        const pagination = response.data.pagination
        totalPages.value = pagination.totalPages || 1
        totalResults.value = pagination.total || 0
        
        // If we're on a page beyond available data, redirect to last valid page
        if (designs.value.length === 0 && currentPage.value > 1 && pagination.totalPages > 0) {
          console.warn(`Page ${currentPage.value} has no results. Redirecting to page ${pagination.totalPages}`)
          currentPage.value = Math.min(currentPage.value, pagination.totalPages)
          // Re-fetch data for the corrected page
          if (currentPage.value !== parseInt(String(currentPage.value))) {
            await loadDesigns()
            return
          }
        }
      } else {
        totalPages.value = 1
        totalResults.value = designs.value.length
      }
    } else {
      designs.value = []
      totalPages.value = 1
      totalResults.value = 0
    }
  } catch (error) {
    console.error('Failed to load designs:', error)
    designs.value = []
    totalPages.value = 1
    totalResults.value = 0
  } finally {
    loading.value = false
  }
}

// Computed
const filteredDesigns = computed(() => {
  // Now that we're using server-side pagination, just return the loaded designs
  return designs.value
})

// Methods
const handleSearch = (query: string) => {
  searchQuery.value = query
}

// New Context Dropdown Methods
const getDesignActions = (design: Design): DropdownAction[] => {
  const actions: DropdownAction[] = [
    {
      key: 'edit',
      label: 'Edit Design',
      icon: PencilIcon,
      variant: 'default'
    },
    {
      key: 'duplicate',
      label: 'Duplicate',
      icon: DocumentDuplicateIcon,
      variant: 'default'
    },
    {
      key: 'export',
      label: 'Export',
      icon: ArrowDownTrayIcon,
      variant: 'default'
    }
  ]

  // Add admin-only actions
  if (authStore.isAdmin) {
    actions.push({
      key: 'convert',
      label: 'Convert to Template',
      icon: RectangleStackIcon,
      variant: 'primary'
    })
  }

  // Add delete action with separator
  actions.push({
    key: 'delete',
    label: 'Delete',
    icon: TrashIcon,
    variant: 'danger',
    separator: true
  })

  return actions
}

const getFileTypeLabel = (design: Design): string => {
  // Create a label based on dimensions
  const aspectRatio = design.width / design.height
  if (aspectRatio > 1.5) return 'Landscape'
  if (aspectRatio < 0.75) return 'Portrait'
  return 'Square'
}

const handleDropdownAction = (action: DropdownAction, design: Design) => {
  switch (action.key) {
    case 'edit':
      editDesign(design)
      break
    case 'duplicate':
      duplicateDesign(design)
      break
    case 'export':
      downloadDesign(design)
      break
    case 'convert':
      openConvertModal(design)
      break
    case 'delete':
      deleteDesign(design)
      break
    default:
      console.warn('Unknown action:', action.key)
  }
}

const openDesign = (design: Design) => {
  router.push(`/editor/${design.id}`)
}

const editDesign = (design: Design) => {
  router.push(`/editor/${design.id}`)
}

const duplicateDesign = async (design: Design) => {
  try {
    await designStore.duplicateDesign(design.id)
    await loadDesigns()
  } catch (error) {
    console.error('Failed to duplicate design:', error)
  }
}

const deleteDesign = async (design: Design) => {
  if (confirm('Are you sure you want to delete this design?')) {
    try {
      await designStore.deleteDesign(design.id)
      await loadDesigns()
    } catch (error) {
      console.error('Failed to delete design:', error)
    }
  }
}

const openConvertModal = (design: Design) => {
  if (!authStore.isAdmin) {
    console.error('Only admin users can convert designs to templates')
    return
  }
  
  selectedDesignForConversion.value = design
  isConvertModalOpen.value = true
}

const closeConvertModal = () => {
  isConvertModalOpen.value = false
  selectedDesignForConversion.value = null
}

const handleConversionSubmit = async (formData: any) => {
  if (!selectedDesignForConversion.value) {
    console.error('No design selected for conversion')
    convertModal.value?.resetConverting()
    return
  }

  try {
    await designAPI.convertToTemplate(selectedDesignForConversion.value.id, {
      category: formData.category,
      name: formData.name,
      description: formData.description,
      tags: formData.tags,
      isPremium: formData.isPremium,
      isActive: formData.isActive
    })
    
    console.log(`Design "${selectedDesignForConversion.value.title || selectedDesignForConversion.value.name}" has been successfully converted to a template!`)
    closeConvertModal()
    
    // Optionally show a success notification
    // You can implement a notification system here if available
  } catch (error) {
    console.error('Failed to convert design to template:', error)
    // Reset the converting state in the modal
    convertModal.value?.resetConverting()
  }
}

const downloadDesign = (design: Design) => {
  // Convert Design to SearchResult format for the export modal
  selectedDesignForExport.value = convertDesignToSearchResult(design)
  isExportModalOpen.value = true
}

// Helper function to convert Design to SearchResult
const convertDesignToSearchResult = (design: Design): SearchResult => {
  return {
    id: design.id,
    type: 'design' as const,
    title: design.title,
    description: design.description,
    thumbnail: design.thumbnail,
    created_at: design.createdAt,
    width: design.width,
    height: design.height,
    // Add any animation/video detection logic here if needed
    hasAnimation: false,
    isVideo: false
  }
}

const handleExportModalClose = () => {
  isExportModalOpen.value = false
  selectedDesignForExport.value = null
}

const handleExportComplete = (url: string, filename: string) => {
  console.log('Export completed:', { url, filename })
  // Optionally show a success message or handle the exported file
}

const formatDate = (date: string | Date) => {
  const d = new Date(date)
  const now = new Date()
  const diff = now.getTime() - d.getTime()
  const days = Math.floor(diff / (1000 * 60 * 60 * 24))
  
  if (days === 0) return 'Today'
  if (days === 1) return 'Yesterday'
  if (days < 7) return `${days} days ago`
  if (days < 30) return `${Math.floor(days / 7)} weeks ago`
  
  return d.toLocaleDateString()
}

// Handle page change for pagination
const handlePageChange = (page: number) => {
  // Validate page number before making request
  if (page < 1 || page > totalPages.value) {
    console.warn(`Invalid page number: ${page}. Valid range: 1-${totalPages.value}`)
    return
  }
  
  currentPage.value = page
  loadDesigns() // Load new page data from server
}

// Watch for search and sort changes
let searchTimeout: NodeJS.Timeout
watch(searchQuery, () => {
  if (searchTimeout) {
    clearTimeout(searchTimeout)
  }
  
  searchTimeout = setTimeout(() => {
    loadDesigns(true)
  }, 300)
})

watch(sortBy, () => {
  loadDesigns(true)
})

// Lifecycle
onMounted(() => {
  loadDesigns()
})
</script>

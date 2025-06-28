<template>
  <AppLayout
    title="My Designs"
    subtitle="Manage all your creative projects"
    @search="handleSearch"
  >
    <div class="p-6">
      <!-- Filter and Sort Bar -->
      <div class="bg-white rounded-lg shadow-sm p-4 mb-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between space-y-4 sm:space-y-0">
          <div class="flex items-center space-x-4">
            <select
              v-model="selectedCategory"
              class="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
            >
              <option value="">All Categories</option>
              <option value="social-media">Social Media</option>
              <option value="business">Business</option>
              <option value="marketing">Marketing</option>
              <option value="personal">Personal</option>
            </select>
            
            <select
              v-model="sortBy"
              class="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
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
                viewMode === 'grid' ? 'bg-blue-100 text-blue-600' : 'text-gray-400 hover:text-gray-600'
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
                viewMode === 'list' ? 'bg-blue-100 text-blue-600' : 'text-gray-400 hover:text-gray-600'
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
      <div class="bg-white rounded-lg shadow-sm p-6">
        <!-- Results Summary -->
        <div v-if="!loading && totalResults > 0" class="text-sm text-gray-600 mb-4">
          Showing {{ designs.length }} of {{ totalResults }} designs
        </div>
        
        <!-- Grid View -->
        <div v-if="viewMode === 'grid'">
          <!-- Loading State -->
          <div v-if="loading" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4">
            <div 
              v-for="i in 12"
              :key="`loading-${i}`"
              class="bg-white rounded-lg border border-gray-100 animate-pulse overflow-hidden h-80 flex flex-col"
            >
              <div class="h-48 bg-gray-200"></div>
              <div class="flex-1 p-3 space-y-2">
                <div class="h-4 bg-gray-200 rounded w-3/4"></div>
                <div class="h-3 bg-gray-200 rounded w-1/2"></div>
                <div class="mt-2 h-6 bg-gray-200 rounded w-16"></div>
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
              <!-- Design Card with Fixed Height -->
              <div class="bg-white rounded-lg border border-gray-100 hover:border-gray-200 transition-colors overflow-hidden h-80 flex flex-col">
                <!-- Design Thumbnail Container - Fixed height but proper aspect ratio -->
                <div class="relative h-48 bg-gray-50 flex items-center justify-center p-2">
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
                  
                  <!-- Dropdown Menu -->
                  <div class="absolute top-2 right-2">
                    <div class="relative">
                      <button
                        class="w-8 h-8 bg-white/90 backdrop-blur-sm rounded-full flex items-center justify-center shadow-sm hover:bg-white transition-all opacity-0 group-hover:opacity-100"
                        @click.stop="toggleDropdown(design.id)"
                      >
                        <svg class="w-4 h-4 text-gray-600" fill="currentColor" viewBox="0 0 20 20">
                          <path d="M10 12a2 2 0 100-4 2 2 0 000 4z"/>
                          <path d="M10 4a2 2 0 100-4 2 2 0 000 4z"/>
                          <path d="M10 20a2 2 0 100-4 2 2 0 000 4z"/>
                        </svg>
                      </button>
                      
                      <!-- Dropdown Menu -->
                      <div 
                        :class="[
                          'absolute right-0 top-full mt-1 w-48 bg-white rounded-lg shadow-lg border border-gray-100 py-1 transition-all duration-200 z-10',
                          dropdownOpen === design.id ? 'opacity-100 visible' : 'opacity-0 invisible'
                        ]"
                      >
                        <button
                          @click.stop="editDesign(design); closeDropdown()"
                          class="w-full px-4 py-2 text-left text-sm text-gray-700 hover:bg-gray-50 flex items-center"
                        >
                          <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                          </svg>
                          Edit Design
                        </button>
                        <button
                          @click.stop="duplicateDesign(design); closeDropdown()"
                          class="w-full px-4 py-2 text-left text-sm text-gray-700 hover:bg-gray-50 flex items-center"
                        >
                          <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                          </svg>
                          Duplicate
                        </button>
                        <button
                          @click.stop="downloadDesign(design); closeDropdown()"
                          class="w-full px-4 py-2 text-left text-sm text-gray-700 hover:bg-gray-50 flex items-center"
                        >
                          <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                          </svg>
                          Export
                        </button>
                        
                        <!-- Admin Convert to Template -->
                        <button
                          v-if="authStore.isAdmin"
                          @click.stop="openConvertModal(design); closeDropdown()"
                          class="w-full px-4 py-2 text-left text-sm text-violet-600 hover:bg-violet-50 flex items-center"
                        >
                          <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                          </svg>
                          Convert to Template
                        </button>
                        
                        <hr class="my-1 border-gray-100">
                        <button
                          @click.stop="deleteDesign(design); closeDropdown()"
                          class="w-full px-4 py-2 text-left text-sm text-red-600 hover:bg-red-50 flex items-center"
                        >
                          <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                          </svg>
                          Delete
                        </button>
                      </div>
                    </div>
                  </div>
                </div>
                
                <!-- Design Info - Takes remaining space -->
                <div class="flex-1 p-3 flex flex-col justify-between">
                  <div>
                    <h3 class="font-medium text-gray-900 truncate text-sm cursor-pointer hover:text-violet-600 transition-colors" @click="editDesign(design)">
                      {{ design.title || design.name || 'Untitled Design' }}
                    </h3>
                    <p class="text-xs text-gray-500 mt-1">
                      {{ formatDate(design.updatedAt || design.createdAt) }}
                    </p>
                  </div>
                  
                  <!-- Design dimensions -->
                  <div class="mt-2">
                    <span class="inline-block px-2 py-1 bg-gray-100 text-gray-600 text-xs rounded">
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
            class="flex items-center p-4 border border-gray-200 rounded-lg hover:bg-gray-50 cursor-pointer"
            @click="openDesign(design)"
          >
            <div class="w-16 h-16 bg-gray-100 rounded-lg flex-shrink-0 overflow-hidden">
              <img
                v-if="design.thumbnail"
                :src="design.thumbnail"
                :alt="design.title"
                class="w-full h-full object-cover"
              />
              <div v-else class="w-full h-full flex items-center justify-center">
                <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                </svg>
              </div>
            </div>
            
            <div class="ml-4 flex-1">
              <h3 class="font-semibold text-gray-900">{{ design.title }}</h3>
              <p class="text-sm text-gray-500">{{ design.width }}×{{ design.height }}</p>
              <p class="text-sm text-gray-500">Updated {{ formatDate(design.updatedAt) }}</p>
            </div>
            
            <div class="flex items-center space-x-2">
              <button
                @click.stop="editDesign(design)"
                class="p-2 text-gray-400 hover:text-blue-600 rounded-md hover:bg-blue-50"
              >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path>
                </svg>
              </button>
              <button
                @click.stop="duplicateDesign(design)"
                class="p-2 text-gray-400 hover:text-green-600 rounded-md hover:bg-green-50"
              >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                </svg>
              </button>
              <button
                @click.stop="downloadDesign(design)"
                class="p-2 text-gray-400 hover:text-purple-600 rounded-md hover:bg-purple-50"
                title="Download"
              >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                </svg>
              </button>
              <button
                @click.stop="deleteDesign(design)"
                class="p-2 text-gray-400 hover:text-red-600 rounded-md hover:bg-red-50"
              >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                </svg>
              </button>
              <button
                @click.stop="deleteDesign(design)"
                class="p-2 text-gray-400 hover:text-red-600 rounded-md hover:bg-red-50"
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
          <div class="text-gray-400 text-lg mb-4">No designs found</div>
          <p class="text-gray-600 mb-6">
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
  </AppLayout>``
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useDesignStore } from '@/stores/design'
import { useAuthStore } from '@/stores/auth'
import type { Design, DesignSearchParams, SearchResult } from '@/types'
import { designAPI } from '@/services/api'

// Components
import AppLayout from '@/components/layout/AppLayout.vue'
import Pagination from '@/components/common/Pagination.vue'
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
const dropdownOpen = ref<string | null>(null)

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

// Dropdown methods
const toggleDropdown = (designId: string) => {
  dropdownOpen.value = dropdownOpen.value === designId ? null : designId
}

const closeDropdown = () => {
  dropdownOpen.value = null
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

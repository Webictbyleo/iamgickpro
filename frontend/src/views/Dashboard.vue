<template>
  <AppLayout
    title="Dashboard"
    subtitle="Welcome back! Ready to create something amazing?"
    @search="handleSearch"
  >
    <div class="space-y-8">
      <!-- Welcome Hero Section - Refined and Compact -->
      <div class="relative overflow-hidden bg-gradient-to-br from-violet-600 via-purple-600 to-fuchsia-600 rounded-2xl shadow-xl">
        <!-- Simplified background elements -->
        <div class="absolute inset-0">
          <div class="absolute top-0 -left-4 w-48 h-48 bg-purple-300 rounded-full mix-blend-multiply filter blur-xl opacity-20 animate-blob"></div>
          <div class="absolute top-0 -right-4 w-48 h-48 bg-yellow-300 rounded-full mix-blend-multiply filter blur-xl opacity-20 animate-blob animation-delay-2000"></div>
        </div>
        
        <!-- Glass morphism overlay -->
        <div class="absolute inset-0 bg-white/5 backdrop-blur-sm"></div>
        
        <div class="relative px-6 py-8">
          <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between">
            <div class="flex-1 mb-8 lg:mb-0">
              <!-- Compact Welcome Message -->
              <div class="flex items-center space-x-4 mb-6">
                <div class="w-14 h-14 bg-white/20 backdrop-blur-md rounded-2xl flex items-center justify-center border border-white/30">
                  <component :is="icons.sparkle" class="w-7 h-7 text-white" />
                </div>
                <div class="flex-1">
                  <div class="inline-flex items-center px-3 py-1 bg-white/20 backdrop-blur-sm rounded-full text-white/90 text-sm font-medium mb-2 border border-white/30">
                    <div class="w-2 h-2 bg-green-400 rounded-full mr-2 animate-pulse"></div>
                    Online & Ready
                  </div>
                  <h1 class="text-3xl lg:text-4xl font-bold text-white leading-tight">
                    Welcome back,
                    <span class="bg-gradient-to-r from-yellow-300 to-pink-300 bg-clip-text text-transparent">
                      {{ user?.firstName || 'Designer' }}
                    </span>!
                  </h1>
                  <p class="text-lg text-white/90 mt-2">
                    Your creative studio awaits. Let's bring your vision to life.
                  </p>
                </div>
              </div>
              
              <!-- Compact Quick Stats -->
              <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-white/15 backdrop-blur-md rounded-xl p-4 border border-white/20 hover:border-white/40 transition-all duration-300">
                  <div class="flex items-center justify-between mb-2">
                    <component :is="icons.design" class="w-6 h-6 text-blue-300" />
                  </div>
                  <div class="text-2xl font-bold text-white">{{ dashboardStats?.overview?.totalDesigns || 0 }}</div>
                  <div class="text-sm text-white/80">Total Designs</div>
                </div>
                
                <div class="bg-white/15 backdrop-blur-md rounded-xl p-4 border border-white/20 hover:border-white/40 transition-all duration-300">
                  <div class="flex items-center justify-between mb-2">
                    <component :is="icons.folder" class="w-6 h-6 text-green-300" />
                  </div>
                  <div class="text-2xl font-bold text-white">{{ dashboardStats?.overview?.totalProjects || 0 }}</div>
                  <div class="text-sm text-white/80">Active Projects</div>
                </div>
                
                <div class="bg-white/15 backdrop-blur-md rounded-xl p-4 border border-white/20 hover:border-white/40 transition-all duration-300">
                  <div class="flex items-center justify-between mb-2">
                    <component :is="icons.download" class="w-6 h-6 text-orange-300" />
                  </div>
                  <div class="text-2xl font-bold text-white">{{ dashboardStats?.overview?.totalExports || 0 }}</div>
                  <div class="text-sm text-white/80">Total Exports</div>
                </div>
                
                <div class="bg-white/15 backdrop-blur-md rounded-xl p-4 border border-white/20 hover:border-white/40 transition-all duration-300">
                  <div class="flex items-center justify-between mb-2">
                    <component :is="icons.template" class="w-6 h-6 text-purple-300" />
                  </div>
                  <div class="text-2xl font-bold text-white">{{ Math.round(dashboardStats?.overview?.successRate || 0) }}%</div>
                  <div class="text-sm text-white/80">Success Rate</div>
                </div>
              </div>
            </div>
            

          </div>
        </div>
      </div>

      <!-- Quick Actions Grid -->
      <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-8">
        <h2 class="text-2xl font-bold text-gray-900 mb-6">Quick Actions</h2>
        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
          <router-link
            to="/templates"
            class="group p-6 bg-gradient-to-br from-green-50 to-emerald-50 rounded-2xl hover:from-green-100 hover:to-emerald-100 transition-all transform hover:scale-105 border border-green-100"
          >
            <div class="w-12 h-12 bg-green-500 rounded-xl flex items-center justify-center mb-4 group-hover:bg-green-600 transition-colors">
              <component :is="icons.template" class="w-6 h-6 text-white" />
            </div>
            <h3 class="font-semibold text-gray-900 text-sm">Templates</h3>
            <p class="text-xs text-gray-500 mt-1">Browse library</p>
          </router-link>

          <router-link
            to="/media"
            class="group p-6 bg-gradient-to-br from-purple-50 to-violet-50 rounded-2xl hover:from-purple-100 hover:to-violet-100 transition-all transform hover:scale-105 border border-purple-100"
          >
            <div class="w-12 h-12 bg-purple-500 rounded-xl flex items-center justify-center mb-4 group-hover:bg-purple-600 transition-colors">
              <component :is="icons.media" class="w-6 h-6 text-white" />
            </div>
            <h3 class="font-semibold text-gray-900 text-sm">Stock Media</h3>
            <p class="text-xs text-gray-500 mt-1">Photos & videos</p>
          </router-link>

          <router-link
            to="/designs"
            class="group p-6 bg-gradient-to-br from-orange-50 to-amber-50 rounded-2xl hover:from-orange-100 hover:to-amber-100 transition-all transform hover:scale-105 border border-orange-100"
          >
            <div class="w-12 h-12 bg-orange-500 rounded-xl flex items-center justify-center mb-4 group-hover:bg-orange-600 transition-colors">
              <component :is="icons.folder" class="w-6 h-6 text-white" />
            </div>
            <h3 class="font-semibold text-gray-900 text-sm">My Designs</h3>
            <p class="text-xs text-gray-500 mt-1">View all projects</p>
          </router-link>

          <router-link
            to="/video-to-design"
            class="group p-6 bg-gradient-to-br from-red-50 to-pink-50 rounded-2xl hover:from-red-100 hover:to-pink-100 transition-all transform hover:scale-105 border border-red-100"
          >
            <div class="w-12 h-12 bg-gradient-to-r from-red-500 to-pink-500 rounded-xl flex items-center justify-center mb-4 group-hover:from-red-600 group-hover:to-pink-600 transition-all">
              <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 24 24">
                <path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/>
              </svg>
            </div>
            <h3 class="font-semibold text-gray-900 text-sm">YouTube Thumbnail</h3>
            <p class="text-xs text-gray-500 mt-1">AI Generator</p>
          </router-link>
        </div>
      </div>

      <!-- Recent Designs Section -->
      <div class="bg-white rounded-lg border border-gray-100">
        <div class="p-6 border-b border-gray-100">
          <div class="flex items-center justify-between">
            <div>
              <h2 class="text-xl font-bold text-gray-900">Recent Designs</h2>
              <p class="text-gray-500 mt-1 text-sm">Continue working on your latest projects</p>
            </div>
            <router-link
              to="/designs" 
              class="inline-flex items-center px-3 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg font-medium transition-colors text-sm"
            >
              View all
              <ArrowRightIcon class="w-4 h-4 ml-1" />
            </router-link>
          </div>
        </div>
        
        <div class="p-6">
          <div class="relative">
            <!-- Show loading cards first -->
            <div v-if="loading" class="flex overflow-x-auto scrollbar-thin scrollbar-thumb-gray-300 scrollbar-track-gray-100 pb-4 gap-4">
              <div 
                v-for="i in 3"
                :key="`loading-${i}`"
                class="flex-shrink-0 w-60 bg-white rounded-lg border border-gray-100 animate-pulse overflow-hidden flex flex-col"
              >
                <div class="h-40 bg-gray-200"></div>
                <div class="p-3 space-y-2">
                  <div class="h-4 bg-gray-200 rounded w-3/4"></div>
                  <div class="flex justify-between items-center">
                    <div class="h-3 bg-gray-200 rounded w-1/3"></div>
                    <div class="h-5 bg-gray-200 rounded w-16"></div>
                  </div>
                </div>
              </div>
            </div>
            
            <!-- Show designs when loaded -->
            <div v-else-if="recentDesigns.length > 0" class="flex overflow-x-auto scrollbar-thin scrollbar-thumb-gray-300 scrollbar-track-gray-100 pb-4 gap-4">
              <div
                v-for="design in recentDesigns"
                :key="design.id"
                class="flex-shrink-0 w-60"
                :data-design-id="design.id"
              >
                <!-- Design Card with Compact Height -->
                <div class="bg-white rounded-lg border border-gray-100 hover:border-gray-200 transition-colors overflow-hidden flex flex-col group">
                  <!-- Design Thumbnail Container - Responsive height -->
                  <div class="relative h-40 bg-gray-50 flex items-center justify-center p-2">
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
                      <component :is="icons.design" class="w-8 h-8 text-gray-400" />
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
                            <component :is="icons.edit" class="w-4 h-4 mr-2" />
                            Edit Design
                          </button>
                          <button
                            @click.stop="duplicateDesign(design); closeDropdown()"
                            class="w-full px-4 py-2 text-left text-sm text-gray-700 hover:bg-gray-50 flex items-center"
                          >
                            <component :is="icons.duplicate" class="w-4 h-4 mr-2" />
                            Duplicate
                          </button>
                          <button
                            @click.stop="downloadDesign(design); closeDropdown()"
                            class="w-full px-4 py-2 text-left text-sm text-gray-700 hover:bg-gray-50 flex items-center"
                          >
                            <component :is="icons.download" class="w-4 h-4 mr-2" />
                            Export
                          </button>
                          <hr class="my-1 border-gray-100">
                          <button
                            @click.stop="deleteDesign(design); closeDropdown()"
                            class="w-full px-4 py-2 text-left text-sm text-red-600 hover:bg-red-50 flex items-center"
                          >
                            <component :is="icons.delete" class="w-4 h-4 mr-2" />
                            Delete
                          </button>
                        </div>
                      </div>
                    </div>
                  </div>
                  
                  <!-- Design Info - Compact layout -->
                  <div class="p-3">
                    <h3 class="font-medium text-gray-900 truncate text-sm cursor-pointer hover:text-violet-600 transition-colors mb-1" @click="editDesign(design)">
                      {{ design.title || design.name || 'Untitled Design' }}
                    </h3>
                    <div class="flex items-center justify-between">
                      <p class="text-xs text-gray-500">
                        {{ new Date(design.updatedAt || design.createdAt).toLocaleDateString() }}
                      </p>
                      <span class="inline-block px-2 py-1 bg-gray-100 text-gray-600 text-xs rounded">
                        {{ design.width }}×{{ design.height }}
                      </span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            
            <!-- Empty State -->
            <div 
              v-else
              class="text-center py-12 bg-gray-50 rounded-lg"
            >
              <div class="w-12 h-12 bg-gray-200 rounded-lg flex items-center justify-center mx-auto mb-4">
                <component :is="icons.design" class="w-6 h-6 text-gray-500" />
              </div>
              <h3 class="text-lg font-medium text-gray-900 mb-2">No designs yet</h3>
              <p class="text-gray-500 mb-6 text-sm">Start creating your first design!</p>
              <router-link
                to="/editor"
                class="inline-flex items-center px-4 py-2 bg-violet-600 text-white rounded-lg font-medium hover:bg-violet-700 transition-colors text-sm"
              >
                <component :is="icons.plus" class="w-4 h-4 mr-2" />
                Create Design
              </router-link>
            </div>
          </div>
        </div>
      </div>

      <!-- Featured Templates Section -->
      <div class="bg-white rounded-lg border border-gray-100">
        <div class="p-6 border-b border-gray-100">
          <div class="flex items-center justify-between">
            <div>
              <h2 class="text-xl font-bold text-gray-900">Featured Templates</h2>
              <p class="text-gray-500 mt-1 text-sm">Professional designs to get you started quickly</p>
            </div>
            <router-link
              to="/templates"
              class="inline-flex items-center px-3 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg font-medium transition-colors text-sm"
            >
              Browse all
              <ArrowRightIcon class="w-4 h-4 ml-1" />
            </router-link>
          </div>
        </div>
        
        <div class="p-6">
          <div class="relative">
            <div class="flex overflow-x-auto scrollbar-thin scrollbar-thumb-gray-300 scrollbar-track-gray-100 pb-4 gap-4">
              <div
                v-for="template in featuredTemplates"
                :key="template.id"
                class="flex-shrink-0 w-60 cursor-pointer group"
                @click="useTemplate(template)"
              >
                <!-- Template Card with Dynamic Aspect Ratio -->
                <div class="bg-white rounded-lg border border-gray-100 hover:border-gray-300 hover:shadow-md transition-all duration-200 overflow-hidden">
                  <!-- Template Thumbnail -->
                  <div 
                    class="relative bg-gray-50 overflow-hidden"
                    :style="getTemplateCardStyle(template)"
                  >
                    <img
                      v-if="template.thumbnail || template.thumbnailUrl"
                      :src="template.thumbnail || template.thumbnailUrl"
                      :alt="template.title || template.name"
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
                      <span class="text-xs text-gray-500 capitalize">{{ template.category || 'General' }}</span>
                      
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
              
              <!-- Loading Cards -->
              <div 
                v-if="templatesLoading"
                v-for="i in 4"
                :key="`template-loading-${i}`"
                class="flex-shrink-0 w-60 animate-pulse"
              >
                <div class="bg-white rounded-lg border border-gray-100 overflow-hidden">
                  <div class="aspect-[3/4] bg-gray-200"></div>
                  <div class="p-3 space-y-2">
                    <div class="h-4 bg-gray-200 rounded w-3/4"></div>
                    <div class="h-3 bg-gray-200 rounded w-1/2"></div>
                  </div>
                </div>
              </div>
            </div>
          </div>
          
          <!-- Empty State -->
          <div 
            v-if="!templatesLoading && featuredTemplates.length === 0"
            class="text-center py-12 bg-gray-50 rounded-lg"
          >
            <div class="w-12 h-12 bg-gray-200 rounded-lg flex items-center justify-center mx-auto mb-4">
              <component :is="icons.template" class="w-6 h-6 text-gray-500" />
            </div>
            <h3 class="text-lg font-medium text-gray-900 mb-2">No templates available</h3>
            <p class="text-gray-500 mb-6 text-sm">Check back later for new templates</p>
            <router-link
              to="/templates"
              class="inline-flex items-center px-4 py-2 bg-purple-600 text-white rounded-lg font-medium hover:bg-purple-700 transition-colors text-sm"
            >
              Browse Templates
              <component :is="icons.arrowRight" class="w-4 h-4 ml-2" />
            </router-link>
          </div>
        </div>
      </div>
    </div>

    <!-- Export Modal -->
    <CompactDesignExportModal
      :is-open="isExportModalOpen"
      :design="selectedDesignForExport"
      @close="handleExportModalClose"
      @exported="handleExportComplete"
    />
  </AppLayout>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { 
  ArrowRightIcon 
} from '@heroicons/vue/24/outline'
import { useAuthStore } from '@/stores/auth'
import { useDesignStore } from '@/stores/design'
import { useIcons } from '@/composables/useIcons'
import { useNotifications } from '@/composables/useNotifications'
import { analyticsAPI, templateAPI, designAPI } from '@/services/api'
import type { Design, Template, DashboardStats, SearchResult } from '@/types'

// Components
import AppLayout from '@/components/layout/AppLayout.vue'
import DesignCard from '@/components/ui/DesignCard.vue'
import DesignGrid from '@/components/ui/DesignGrid.vue'
import TemplateGrid from '@/components/ui/TemplateGrid.vue'
import CompactDesignExportModal from '@/components/modals/CompactDesignExportModal.vue'

const router = useRouter()
const authStore = useAuthStore()
const designStore = useDesignStore()
const icons = useIcons()
const { showInfo, showSuccess, showError } = useNotifications()

// State
const loading = ref(false)
const templatesLoading = ref(false)
const statsLoading = ref(false)
const recentDesigns = ref<Design[]>([])
const featuredTemplates = ref<Template[]>([])
const dashboardStats = ref<DashboardStats | null>(null)

// Dropdown state
const dropdownOpen = ref<string | null>(null)

// Export modal state
const isExportModalOpen = ref(false)
const selectedDesignForExport = ref<SearchResult | null>(null)
const exportModalOpen = ref(false)
const selectedDesign = ref<Design | null>(null)

// Template application state
const isApplyingTemplate = ref(false)

// Computed
const user = computed(() => authStore.user)

const formatDesignCount = (count: number): string => {
  if (count >= 1000000) {
    return `${(count / 1000000).toFixed(1)}M`
  } else if (count >= 1000) {
    return `${(count / 1000).toFixed(1)}K`
  }
  return count.toString()
}

const formatLastLogin = (date: Date): string => {
  const now = new Date()
  const diffMs = now.getTime() - date.getTime()
  const diffHours = Math.floor(diffMs / (1000 * 60 * 60))
  
  if (diffHours < 1) return 'Just now'
  if (diffHours < 24) return `${diffHours}h ago`
  const diffDays = Math.floor(diffHours / 24)
  if (diffDays < 7) return `${diffDays}d ago`
  return date.toLocaleDateString()
}

// Methods
const handleSearch = (query: string) => {
  router.push(`/designs?search=${encodeURIComponent(query)}`)
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
    await loadRecentDesigns()
  } catch (error) {
    console.error('Failed to duplicate design:', error)
  }
}

const deleteDesign = async (design: Design) => {
  if (confirm('Are you sure you want to delete this design?')) {
    try {
      await designStore.deleteDesign(design.id)
      await loadRecentDesigns()
    } catch (error) {
      console.error('Failed to delete design:', error)
    }
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

const useTemplate = async (template: Template) => {
  try {
    isApplyingTemplate.value = true
    
    // Show loading notification
    showInfo('Applying template...')
    
    // Use the proper API endpoint to apply the template
    const response = await templateAPI.useTemplate(template.uuid, {
      name: `${template.name} Copy`
    })
    
    if (response.data?.success && response.data?.data) {
      const newDesign = response.data.data
      
      // Navigate to the editor with the new design
      router.push(`/editor/${newDesign.id}`)
      
      showSuccess('Template applied successfully!')
    } else {
      throw new Error(response.data?.message || 'Failed to apply template')
    }
  } catch (error: any) {
    console.error('Template application failed:', error)
    
    const errorMessage = error.response?.data?.message || error.message || 'Failed to apply template'
    showError(`Template Error: ${errorMessage}`)
  } finally {
    isApplyingTemplate.value = false
  }
}

const loadDashboardStats = async () => {
  try {
    statsLoading.value = true
    const response = await analyticsAPI.getDashboardStats()
    
    if (response.data?.data) {
      dashboardStats.value = response.data.data
    }
  } catch (error) {
    console.error('Failed to load dashboard stats:', error)
    // Don't use fallback data - let the component show empty state
    dashboardStats.value = null
  } finally {
    statsLoading.value = false
  }
}

const loadRecentDesigns = async () => {
  loading.value = true
  try {
    // Load only recent 6 designs directly from API with pagination
    const response = await designAPI.getDesigns({
      page: 1,
      limit: 6,
      sort_by: 'updated_at',
      sort_order: 'desc'
    })
    
    if (response.data?.data) {
      recentDesigns.value = response.data.data
    } else {
      recentDesigns.value = []
    }
  } catch (error) {
    console.error('Failed to load recent designs:', error)
    recentDesigns.value = []
  } finally {
    loading.value = false
  }
}

const loadFeaturedTemplates = async () => {
  templatesLoading.value = true
  try {
    const response = await templateAPI.getTemplates({
      page: 1,
      limit: 6
    })
    
    if (response.data?.data?.templates) {
      featuredTemplates.value = response.data.data.templates
    }
  } catch (error) {
    console.error('Failed to load featured templates:', error)
    // Don't use fallback data - let the component show empty state
    featuredTemplates.value = []
  } finally {
    templatesLoading.value = false
  }
}

// Helper functions for template cards
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

const formatUsageCount = (count: number): string => {
  if (count >= 1000000) {
    return (count / 1000000).toFixed(1) + 'M'
  } else if (count >= 1000) {
    return (count / 1000).toFixed(1) + 'K'
  }
  return count.toString()
}

// Lifecycle hooks
onMounted(async () => {
  try {
    await Promise.all([
      loadDashboardStats(),
      loadRecentDesigns(),
      loadFeaturedTemplates()
    ])
  } catch (error) {
    console.error('Failed to load dashboard data:', error)
  }
})


</script>

<style scoped>
@keyframes blob {
  0% {
    transform: translate(0px, 0px) scale(1);
  }
  33% {
    transform: translate(30px, -50px) scale(1.1);
  }
  66% {
    transform: translate(-20px, 20px) scale(0.9);
  }
  100% {
    transform: translate(0px, 0px) scale(1);
  }
}

.animate-blob {
  animation: blob 7s infinite;
}

.animation-delay-2000 {
  animation-delay: 2s;
}

.animation-delay-4000 {
  animation-delay: 4s;
}

/* Custom Scrollbar Styles */
.scrollbar-thin {
  scrollbar-width: thin;
}

.scrollbar-thin::-webkit-scrollbar {
  height: 6px;
}

.scrollbar-thin::-webkit-scrollbar-track {
  background: #f3f4f6;
  border-radius: 3px;
}

.scrollbar-thin::-webkit-scrollbar-thumb {
  background: #d1d5db;
  border-radius: 3px;
}

.scrollbar-thin::-webkit-scrollbar-thumb:hover {
  background: #9ca3af;
}

/* Hide scrollbar for Firefox */
.scrollbar-thin {
  scrollbar-color: #d1d5db #f3f4f6;
  scrollbar-width: thin;
}
</style>

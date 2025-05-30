<template>
  <AppLayout>
    <div class="space-y-8">
      <!-- Search Header -->
      <div class="relative overflow-hidden bg-gradient-to-br from-indigo-50 via-white to-violet-50 rounded-2xl border border-indigo-100/50 p-8">
        <!-- Background Pattern -->
        <div class="absolute inset-0 opacity-30">
          <div class="absolute top-4 right-4 w-32 h-32 bg-gradient-to-br from-indigo-400/20 to-violet-400/20 rounded-full blur-xl"></div>
          <div class="absolute bottom-4 left-4 w-24 h-24 bg-gradient-to-br from-blue-400/20 to-indigo-400/20 rounded-full blur-xl"></div>
        </div>
        
        <div class="relative">
          <div class="flex items-center space-x-4 mb-6">
            <div class="w-12 h-12 bg-gradient-to-br from-indigo-500 to-violet-600 rounded-xl flex items-center justify-center shadow-lg">
              <MagnifyingGlassIcon class="w-6 h-6 text-white" />
            </div>
            <div>
              <h1 class="text-3xl font-bold bg-gradient-to-r from-indigo-900 via-violet-800 to-indigo-900 bg-clip-text text-transparent">
                Search Results
              </h1>
              <p class="text-indigo-600/80 mt-1 font-medium">
                {{ searchQuery ? `Results for "${searchQuery}"` : 'Browse all content' }}
              </p>
            </div>
          </div>
          
          <!-- Enhanced Search Bar -->
          <div class="relative max-w-2xl">
            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
              <MagnifyingGlassIcon class="h-5 w-5 text-indigo-400" />
            </div>
            <input
              v-model="searchQuery"
              type="text"
              placeholder="Search designs, templates, media..."
              class="block w-full pl-12 pr-4 py-4 border border-gray-200 rounded-xl bg-white/80  text-gray-900 placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-300 focus:bg-white transition-all duration-200 text-lg shadow-lg"
              @keyup.enter="performSearch"
              @input="debouncedSearch"
            />
            <div class="absolute inset-y-0 right-0 pr-4 flex items-center">
              <kbd class="inline-flex items-center px-2 py-1 border border-gray-200 rounded text-sm font-mono text-gray-500 bg-gray-100">
                ⏎
              </kbd>
            </div>
          </div>
        </div>
      </div>

      <!-- Search Statistics and Filters -->
      <div class="bg-white rounded-2xl shadow-lg border border-gray-100 overflow-hidden">
        <div class="p-6">
          <!-- Search Stats -->
          <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6">
            <div class="flex items-center space-x-4 mb-4 sm:mb-0">
              <div class="text-sm text-gray-600">
                <span class="font-semibold text-gray-900">{{ totalResults }}</span>
                {{ totalResults === 1 ? 'result' : 'results' }} found
                <span v-if="searchQuery" class="text-indigo-600">for "{{ searchQuery }}"</span>
              </div>
              <div v-if="searchTime" class="text-sm text-gray-500">
                ({{ searchTime }}ms)
              </div>
            </div>
            
            <div class="flex items-center space-x-3">
              <!-- Sort Options -->
              <select
                v-model="sortBy"
                class="appearance-none bg-white border border-gray-200 rounded-xl px-4 py-2 pr-8 text-sm font-medium text-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-300 transition-all duration-200"
                @change="performSearch"
              >
                <option value="relevance">Most Relevant</option>
                <option value="newest">Newest First</option>
                <option value="popular">Most Popular</option>
                <option value="name">Name A-Z</option>
              </select>
            </div>
          </div>
          
          <!-- Content Type Filters -->
          <div class="flex flex-wrap gap-3">
            <button
              v-for="filter in contentFilters"
              :key="filter.type"
              @click="toggleFilter(filter.type)"
              :class="[
                activeFilters.includes(filter.type)
                  ? 'bg-indigo-600 text-white shadow-lg border-indigo-600'
                  : 'bg-white text-gray-700 hover:bg-gray-50 border-gray-200',
                'inline-flex items-center px-4 py-2 border rounded-xl text-sm font-medium transition-all duration-200 hover:shadow-md'
              ]"
            >
              <span class="mr-2 text-lg">{{ filter.icon }}</span>
              {{ filter.label }}
              <span v-if="filter.count > 0" class="ml-2 px-2 py-0.5 bg-black/10 rounded-full text-xs">
                {{ filter.count }}
              </span>
            </button>
          </div>
        </div>
      </div>

      <!-- Loading State -->
      <div v-if="isLoading" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <div
          v-for="i in 9"
          :key="i"
          class="bg-white rounded-2xl border border-gray-100 overflow-hidden animate-pulse"
        >
          <div class="aspect-video bg-gradient-to-br from-gray-100 to-gray-200"></div>
          <div class="p-4 space-y-3">
            <div class="h-4 bg-gray-200 rounded w-3/4"></div>
            <div class="h-3 bg-gray-200 rounded w-1/2"></div>
          </div>
        </div>
      </div>

      <!-- Search Results Grid -->
      <div v-else-if="searchResults.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <div
          v-for="result in searchResults"
          :key="`${result.type}-${result.id}`"
          class="group bg-white rounded-2xl border border-gray-100 overflow-hidden hover:shadow-xl hover:shadow-indigo-500/10 transform hover:-translate-y-1 transition-all duration-300 cursor-pointer"
          @click="openResult(result)"
        >
          <!-- Result Image/Thumbnail -->
          <div class="relative aspect-video bg-gradient-to-br from-gray-100 to-gray-200 overflow-hidden">
            <img
              v-if="result.thumbnail"
              :src="result.thumbnail"
              :alt="result.title"
              class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
              loading="lazy"
            />
            <div v-else class="w-full h-full flex items-center justify-center">
              <component :is="getResultIcon(result.type)" class="w-12 h-12 text-gray-400" />
            </div>
            
            <!-- Badges Container -->
            <div class="absolute top-3 left-3 flex flex-wrap items-start gap-2 max-w-[calc(100%-6rem)]">
              <!-- Type Badge -->
              <span class="inline-flex items-center px-3 py-1 rounded-lg text-xs font-bold bg-black/70 backdrop-blur-sm text-white border border-white/20">
                <span class="mr-1.5">{{ getTypeIcon(result.type) }}</span>
                {{ result.type.toUpperCase() }}
              </span>
              
              <!-- Animation Badge -->
              <span v-if="result.hasAnimation" class="inline-flex items-center px-2 py-1 rounded-lg text-xs font-bold bg-gradient-to-r from-purple-500 to-pink-500 text-white shadow-lg">
                🎬 ANIMATED
              </span>
              
              <!-- Video Badge -->
              <span v-if="result.isVideo && result.type !== 'template'" class="inline-flex items-center px-2 py-1 rounded-lg text-xs font-bold bg-gradient-to-r from-red-500 to-pink-600 text-white shadow-lg">
                🎥 VIDEO
              </span>
              
              <!-- Duration Badge -->
              <span v-if="result.duration && (result.hasAnimation || result.isVideo) && result.type !== 'template'" class="inline-flex items-center px-2 py-1 rounded-lg text-xs font-medium bg-black/70 text-white shadow-lg">
                {{ formatDuration(result.duration) }}
              </span>
            </div>
            
            <!-- Premium Badge -->
            <div v-if="result.isPremium" class="absolute top-3 right-3">
              <span class="inline-flex items-center px-2 py-1 rounded-lg text-xs font-bold bg-gradient-to-r from-yellow-400 to-orange-500 text-white shadow-lg">
                ⭐ PRO
              </span>
            </div>
            
            <!-- Hover Actions -->
            <div class="absolute inset-0 bg-gradient-to-t from-black/50 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300">
              <div class="absolute bottom-4 left-4 right-4 flex items-center justify-between">
                <!-- Primary Action Button -->
                <div class="flex items-center space-x-2">
                  <!-- Design Actions -->
                  <template v-if="result.type === 'design'">
                    <button 
                      @click.stop="editDesign(result)"
                      class="bg-white/90 backdrop-blur-sm text-gray-900 px-4 py-2 rounded-lg text-sm font-semibold hover:bg-white transition-all duration-200 flex items-center space-x-2"
                    >
                      <PencilIcon class="w-4 h-4" />
                      <span>Edit</span>
                    </button>
                    <button 
                      @click.stop="exportDesign(result)"
                      class="bg-indigo-600/90 backdrop-blur-sm text-white px-4 py-2 rounded-lg text-sm font-semibold hover:bg-indigo-700 transition-all duration-200 flex items-center space-x-2"
                    >
                      <ArrowDownTrayIcon class="w-4 h-4" />
                      <span>Export</span>
                    </button>
                  </template>
                  
                  <!-- Template Actions -->
                  <template v-else-if="result.type === 'template'">
                    <button 
                      @click.stop="useTemplate(result)"
                      class="bg-white/90 backdrop-blur-sm text-gray-900 px-4 py-2 rounded-lg text-sm font-semibold hover:bg-white transition-all duration-200 flex items-center space-x-2"
                    >
                      <DocumentDuplicateIcon class="w-4 h-4" />
                      <span>Use Template</span>
                    </button>
                  </template>
                  
                  <!-- Media Actions -->
                  <template v-else-if="result.type === 'media'">
                    <button 
                      @click.stop="addToDesign(result)"
                      class="bg-white/90 backdrop-blur-sm text-gray-900 px-4 py-2 rounded-lg text-sm font-semibold hover:bg-white transition-all duration-200 flex items-center space-x-2"
                    >
                      <PlusIcon class="w-4 h-4" />
                      <span>Add to Design</span>
                    </button>
                  </template>
                  
                  <!-- Export Actions -->
                  <template v-else-if="result.type === 'export'">
                    <button 
                      v-if="result.exportStatus === 'completed'"
                      @click.stop="downloadExport(result)"
                      class="bg-green-600/90 backdrop-blur-sm text-white px-4 py-2 rounded-lg text-sm font-semibold hover:bg-green-700 transition-all duration-200 flex items-center space-x-2"
                    >
                      <ArrowDownTrayIcon class="w-4 h-4" />
                      <span>Download</span>
                    </button>
                    <div 
                      v-else
                      class="bg-amber-100/90 backdrop-blur-sm text-amber-800 px-4 py-2 rounded-lg text-sm font-semibold flex items-center space-x-2"
                    >
                      <div class="w-4 h-4 border-2 border-amber-600 border-t-transparent rounded-full animate-spin" v-if="result.exportStatus === 'processing'"></div>
                      <ClockIcon class="w-4 h-4" v-else />
                      <span>{{ getExportStatusLabel(result.exportStatus) }}</span>
                    </div>
                  </template>
                </div>
                
                <!-- Preview Button -->
                <button 
                  @click.stop="openResult(result)"
                  class="bg-black/20 backdrop-blur-sm text-white p-2 rounded-lg hover:bg-black/30 transition-all duration-200"
                >
                  <EyeIcon class="w-4 h-4" />
                </button>
              </div>
            </div>
          </div>
          
          <!-- Result Info -->
          <div class="p-4">
            <h3 class="font-semibold text-gray-900 group-hover:text-indigo-600 transition-colors duration-200 line-clamp-2">
              {{ result.title }}
            </h3>
            <p v-if="result.description" class="text-sm text-gray-600 mt-1 line-clamp-2">
              {{ result.description }}
            </p>
            <div class="flex items-center justify-between mt-3">
              <div class="flex items-center space-x-2 text-xs text-gray-500">
                <span>{{ formatDate(result.created_at) }}</span>
                <span v-if="result.author">• by {{ result.author }}</span>
              </div>
              <div v-if="result.stats" class="flex items-center space-x-3 text-xs text-gray-500">
                <span class="flex items-center">
                  <HeartIcon class="w-3 h-3 mr-1" />
                  {{ result.stats.likes }}
                </span>
                <span class="flex items-center">
                  <EyeIcon class="w-3 h-3 mr-1" />
                  {{ result.stats.views }}
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Empty State -->
      <div v-else class="text-center py-16">
        <div class="relative">
          <!-- Background decoration -->
          <div class="absolute inset-0 flex items-center justify-center">
            <div class="w-32 h-32 bg-gradient-to-br from-indigo-100 to-violet-100 rounded-full blur-3xl opacity-50"></div>
          </div>
          
          <div class="relative">
            <div class="w-20 h-20 bg-gradient-to-br from-indigo-500 to-violet-600 rounded-2xl flex items-center justify-center mx-auto mb-6 shadow-lg">
              <MagnifyingGlassIcon class="w-10 h-10 text-white" />
            </div>
            
            <h3 class="text-2xl font-bold text-gray-900 mb-3">
              {{ searchQuery ? 'No results found' : 'Start searching' }}
            </h3>
            <p class="text-gray-600 mb-8 max-w-md mx-auto leading-relaxed">
              {{ searchQuery 
                ? 'Try different keywords or check your spelling. You can also browse our popular content below.' 
                : 'Enter a search term above to find designs, templates, and media across our platform.'
              }}
            </p>
            
            <div class="flex flex-col sm:flex-row items-center justify-center space-y-3 sm:space-y-0 sm:space-x-4">
              <button
                v-if="searchQuery"
                @click="clearSearch"
                class="inline-flex items-center px-6 py-3 bg-white text-gray-700 border border-gray-300 rounded-xl font-semibold hover:bg-gray-50 transition-all duration-200"
              >
                <MagnifyingGlassIcon class="h-5 w-5 mr-2" />
                Clear Search
              </button>
              
              <button
                @click="showPopular"
                class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-indigo-600 to-violet-600 text-white rounded-xl font-semibold shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 transition-all duration-200"
              >
                <FireIcon class="h-5 w-5 mr-2" />
                Browse Popular
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Enhanced Pagination -->
      <div v-if="totalPages > 1" class="flex items-center justify-center space-x-2">
        <button
          @click="goToPage(currentPage - 1)"
          :disabled="currentPage === 1"
          class="p-3 rounded-xl border border-gray-300 text-gray-500 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-200"
        >
          <ChevronLeftIcon class="w-5 h-5" />
        </button>
        
        <div class="flex items-center space-x-1">
          <button
            v-for="page in getPaginationPages()"
            :key="page"
            @click="goToPage(page)"
            :class="[
              page === currentPage
                ? 'bg-gradient-to-r from-indigo-600 to-violet-600 text-white shadow-lg'
                : 'bg-white text-gray-700 hover:bg-gray-50 border border-gray-300',
              'px-4 py-3 rounded-xl text-sm font-semibold transition-all duration-200 min-w-[48px]'
            ]"
          >
            {{ page }}
          </button>
        </div>
        
        <button
          @click="goToPage(currentPage + 1)"
          :disabled="currentPage === totalPages"
          class="p-3 rounded-xl border border-gray-300 text-gray-500 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-200"
        >
          <ChevronRightIcon class="w-5 h-5" />
        </button>
      </div>
    </div>
    
    <!-- Design Export Modal -->
    <DesignExportModal
      :is-open="showExportModal"
      :design="designToExport"
      @close="closeExportModal"
      @exported="handleExportComplete"
    />
  </AppLayout>
</template>

<script setup lang="ts">
import { ref, onMounted, watch, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { 
  MagnifyingGlassIcon,
  EyeIcon,
  HeartIcon,
  FireIcon,
  ChevronLeftIcon,
  ChevronRightIcon,
  DocumentIcon,
  PhotoIcon,
  VideoCameraIcon,
  PaintBrushIcon,
  PencilIcon,
  ArrowDownTrayIcon,
  DocumentDuplicateIcon,
  PlusIcon,
  ClockIcon
} from '@heroicons/vue/24/outline'
import AppLayout from '@/components/layout/AppLayout.vue'
import DesignExportModal from '@/components/modals/DesignExportModal.vue'

// Types
interface SearchResult {
  id: string
  type: 'design' | 'template' | 'media' | 'export'
  title: string
  description?: string
  thumbnail?: string
  url?: string
  author?: string
  created_at: string
  isPremium?: boolean
  exportStatus?: 'pending' | 'processing' | 'completed' | 'failed'
  hasAnimation?: boolean
  isVideo?: boolean
  duration?: number // in seconds for videos/animations
  stats?: {
    likes: number
    views: number
    downloads?: number
  }
}

interface ContentFilter {
  type: string
  label: string
  icon: string
  count: number
}

// Router and Route
const route = useRoute()
const router = useRouter()

// Reactive data
const searchQuery = ref('')
const isLoading = ref(false)
const searchResults = ref<SearchResult[]>([])
const totalResults = ref(0)
const totalPages = ref(1)
const currentPage = ref(1)
const sortBy = ref('relevance')
const activeFilters = ref<string[]>([])
const searchTimeout = ref<NodeJS.Timeout>()
const searchTime = ref<number | null>(null)

// Export modal state
const showExportModal = ref(false)
const designToExport = ref<SearchResult | null>(null)

// Content filters
const contentFilters = ref<ContentFilter[]>([
  { type: 'design', label: 'Designs', icon: '🎨', count: 0 },
  { type: 'template', label: 'Templates', icon: '📄', count: 0 },
  { type: 'media', label: 'Media', icon: '📸', count: 0 },
  { type: 'export', label: 'Exports', icon: '💾', count: 0 }
])

// Utility functions
const debouncedSearch = () => {
  if (searchTimeout.value) {
    clearTimeout(searchTimeout.value)
  }
  searchTimeout.value = setTimeout(performSearch, 500)
}

const getResultIcon = (type: string) => {
  const iconMap: Record<string, any> = {
    'design': PaintBrushIcon,
    'template': DocumentIcon,
    'media': PhotoIcon,
    'export': DocumentIcon,
    'video': VideoCameraIcon
  }
  return iconMap[type] || DocumentIcon
}

const getTypeIcon = (type: string): string => {
  const iconMap: Record<string, string> = {
    'design': '🎨',
    'template': '📄',
    'media': '📸',
    'export': '💾',
    'video': '🎥'
  }
  return iconMap[type] || '📁'
}

const getActionLabel = (type: string): string => {
  const labelMap: Record<string, string> = {
    'design': 'Edit Design',
    'template': 'Use Template',
    'media': 'Add to Design',
    'export': 'Download'
  }
  return labelMap[type] || 'View'
}

const formatDate = (dateString: string): string => {
  const date = new Date(dateString)
  const now = new Date()
  const diffMs = now.getTime() - date.getTime()
  const diffDays = Math.floor(diffMs / (1000 * 60 * 60 * 24))
  
  if (diffDays === 0) return 'Today'
  if (diffDays === 1) return 'Yesterday'
  if (diffDays < 7) return `${diffDays} days ago`
  if (diffDays < 30) return `${Math.floor(diffDays / 7)} weeks ago`
  if (diffDays < 365) return `${Math.floor(diffDays / 30)} months ago`
  return `${Math.floor(diffDays / 365)} years ago`
}

const formatDuration = (seconds: number): string => {
  const minutes = Math.floor(seconds / 60)
  const remainingSeconds = seconds % 60
  
  if (minutes > 0) {
    return `${minutes}:${remainingSeconds.toString().padStart(2, '0')}`
  }
  return `0:${remainingSeconds.toString().padStart(2, '0')}`
}

const getPaginationPages = (): number[] => {
  const maxVisible = 7
  const pages: number[] = []
  
  if (totalPages.value <= maxVisible) {
    for (let i = 1; i <= totalPages.value; i++) {
      pages.push(i)
    }
  } else {
    const start = Math.max(1, currentPage.value - 3)
    const end = Math.min(totalPages.value, start + maxVisible - 1)
    
    for (let i = start; i <= end; i++) {
      pages.push(i)
    }
  }
  
  return pages
}

// Search operations
const performSearch = async () => {
  const startTime = Date.now()
  isLoading.value = true
  
  try {
    // Update URL with search parameters
    const query: Record<string, any> = { q: searchQuery.value }
    if (sortBy.value !== 'relevance') query.sort = sortBy.value
    if (activeFilters.value.length > 0) query.filters = activeFilters.value.join(',')
    if (currentPage.value > 1) query.page = currentPage.value
    
    await router.push({ query })
    
    // Simulate API call (replace with actual API call)
    await new Promise(resolve => setTimeout(resolve, 800))
    
    // Mock search results
    const mockResults: SearchResult[] = []
    for (let i = 1; i <= 12; i++) {
      const type = ['design', 'template', 'media', 'export'][Math.floor(Math.random() * 4)] as any
      const hasAnimation = Math.random() > 0.7 // 30% chance of having animation
      const isVideo = Math.random() > 0.8 // 20% chance of being a video
      
      const result: SearchResult = {
        id: `result-${i}`,
        type,
        title: `${searchQuery.value || 'Sample'} Result ${i}`,
        description: `This is a sample search result for "${searchQuery.value}". It demonstrates the search functionality.`,
        thumbnail: `https://picsum.photos/400/300?random=${i}`,
        author: `Designer ${i}`,
        created_at: new Date(Date.now() - Math.random() * 365 * 24 * 60 * 60 * 1000).toISOString(),
        isPremium: Math.random() > 0.7,
        hasAnimation,
        isVideo,
        duration: (hasAnimation || isVideo) ? Math.floor(Math.random() * 30) + 5 : undefined, // 5-35 seconds
        stats: {
          likes: Math.floor(Math.random() * 1000),
          views: Math.floor(Math.random() * 10000)
        }
      }
      
      // Add export status for export type results
      if (type === 'export') {
        const statuses = ['pending', 'processing', 'completed', 'failed'] as const
        result.exportStatus = statuses[Math.floor(Math.random() * statuses.length)]
        // Add download URL for completed exports
        if (result.exportStatus === 'completed') {
          result.url = `https://example.com/downloads/export-${i}.png`
        }
      }
      
      mockResults.push(result)
    }
    
    searchResults.value = mockResults
    totalResults.value = mockResults.length
    totalPages.value = Math.ceil(totalResults.value / 12)
    
    // Update filter counts
    contentFilters.value.forEach(filter => {
      filter.count = mockResults.filter(result => result.type === filter.type).length
    })
    
    searchTime.value = Date.now() - startTime
  } catch (error) {
    console.error('Search failed:', error)
    searchResults.value = []
    totalResults.value = 0
  } finally {
    isLoading.value = false
  }
}

const toggleFilter = (filterType: string) => {
  const index = activeFilters.value.indexOf(filterType)
  if (index > -1) {
    activeFilters.value.splice(index, 1)
  } else {
    activeFilters.value.push(filterType)
  }
  currentPage.value = 1
  performSearch()
}

const clearSearch = () => {
  searchQuery.value = ''
  activeFilters.value = []
  sortBy.value = 'relevance'
  currentPage.value = 1
  router.push({ query: {} })
  performSearch()
}

const showPopular = () => {
  searchQuery.value = ''
  sortBy.value = 'popular'
  activeFilters.value = []
  currentPage.value = 1
  performSearch()
}

const openResult = (result: SearchResult) => {
  // Navigate to appropriate view based on result type
  switch (result.type) {
    case 'design':
      router.push(`/editor/${result.id}`)
      break
    case 'template':
      router.push(`/templates/${result.id}`)
      break
    case 'media':
      router.push(`/media?selected=${result.id}`)
      break
    case 'export':
      router.push(`/exports/${result.id}`)
      break
    default:
      console.log('Opening result:', result)
  }
}

// Type-specific action handlers
const editDesign = (result: SearchResult) => {
  router.push(`/editor/${result.id}`)
}

const exportDesign = (result: SearchResult) => {
  designToExport.value = result
  showExportModal.value = true
}

const useTemplate = (result: SearchResult) => {
  // Create new design from template
  router.push(`/editor?template=${result.id}`)
}

const addToDesign = (result: SearchResult) => {
  // Create new design with media as layer
  router.push(`/editor?media=${result.id}`)
}

const downloadExport = (result: SearchResult) => {
  // Download the export file
  if (result.url) {
    // Create temporary download link
    const link = document.createElement('a')
    link.href = result.url
    link.download = result.title || 'export'
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
  } else {
    console.log('Downloading export:', result.id)
    // In a real implementation, you would get the download URL from API
    alert(`Download for "${result.title}" will start shortly.`)
  }
}

const getExportStatusLabel = (status?: string): string => {
  const statusLabels: Record<string, string> = {
    pending: 'Pending',
    processing: 'Processing',
    completed: 'Completed',
    failed: 'Failed'
  }
  return statusLabels[status || 'pending'] || 'Unknown'
}

// Export modal handlers
const closeExportModal = () => {
  showExportModal.value = false
  designToExport.value = null
}

const handleExportComplete = (url: string, filename: string) => {
  console.log('Export completed:', { url, filename })
  // You could show a success notification here
  // notificationStore.addNotification({
  //   type: 'success',
  //   title: 'Export Complete',
  //   message: `${filename} has been exported successfully`
  // })
}

const goToPage = (page: number) => {
  if (page >= 1 && page <= totalPages.value) {
    currentPage.value = page
    performSearch()
  }
}

// Initialize from route query
onMounted(() => {
  const query = route.query.q as string
  if (query) {
    searchQuery.value = query
  }
  
  const sort = route.query.sort as string
  if (sort) {
    sortBy.value = sort
  }
  
  const filters = route.query.filters as string
  if (filters) {
    activeFilters.value = filters.split(',')
  }
  
  const page = route.query.page as string
  if (page) {
    currentPage.value = parseInt(page, 10)
  }
  
  performSearch()
})

// Watch for route changes
watch(() => route.query, () => {
  const query = route.query.q as string
  if (query !== searchQuery.value) {
    searchQuery.value = query || ''
    performSearch()
  }
})
</script>

<style scoped>
.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>

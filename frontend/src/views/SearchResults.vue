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

      <!-- Enhanced Search Statistics and Filters -->
      <div class="bg-white rounded-2xl shadow-lg border border-gray-100 overflow-hidden">
        <div class="p-6">
          <!-- Search Stats with Better Layout -->
          <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between mb-6 gap-4">
            <div class="flex flex-col sm:flex-row sm:items-center gap-4">
              <div class="text-sm text-gray-600">
                <span class="font-semibold text-gray-900 text-lg">{{ totalResults.toLocaleString() }}</span>
                <span class="ml-1">{{ totalResults === 1 ? 'result' : 'results' }}</span>
                <span v-if="searchQuery" class="text-indigo-600 font-medium ml-1">for "{{ searchQuery }}"</span>
              </div>
              <div v-if="searchTime" class="text-sm text-gray-500 flex items-center">
                <div class="w-1 h-1 bg-gray-400 rounded-full mr-2"></div>
                {{ searchTime }}ms
              </div>
            </div>
            
            <div class="flex flex-col sm:flex-row items-start sm:items-center gap-3">
              <!-- View Mode Toggle -->
              <div class="flex items-center bg-gray-100 rounded-xl p-1">
                <button
                  @click="viewMode = 'masonry'"
                  :class="[
                    viewMode === 'masonry' 
                      ? 'bg-white text-gray-900 shadow-sm' 
                      : 'text-gray-500 hover:text-gray-700',
                    'p-2 rounded-lg transition-all duration-200'
                  ]"
                  title="Masonry View"
                >
                  <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                    <rect x="3" y="3" width="7" height="7" rx="1"/>
                    <rect x="14" y="3" width="7" height="4" rx="1"/>
                    <rect x="14" y="9" width="7" height="12" rx="1"/>
                    <rect x="3" y="12" width="7" height="9" rx="1"/>
                  </svg>
                </button>
                <button
                  @click="viewMode = 'grid'"
                  :class="[
                    viewMode === 'grid' 
                      ? 'bg-white text-gray-900 shadow-sm' 
                      : 'text-gray-500 hover:text-gray-700',
                    'p-2 rounded-lg transition-all duration-200'
                  ]"
                  title="Grid View"
                >
                  <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                    <rect x="3" y="3" width="7" height="7" rx="1"/>
                    <rect x="14" y="3" width="7" height="7" rx="1"/>
                    <rect x="3" y="14" width="7" height="7" rx="1"/>
                    <rect x="14" y="14" width="7" height="7" rx="1"/>
                  </svg>
                </button>
                <button
                  @click="viewMode = 'list'"
                  :class="[
                    viewMode === 'list' 
                      ? 'bg-white text-gray-900 shadow-sm' 
                      : 'text-gray-500 hover:text-gray-700',
                    'p-2 rounded-lg transition-all duration-200'
                  ]"
                  title="List View"
                >
                  <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                    <rect x="3" y="5" width="18" height="2" rx="1"/>
                    <rect x="3" y="11" width="18" height="2" rx="1"/>
                    <rect x="3" y="17" width="18" height="2" rx="1"/>
                  </svg>
                </button>
              </div>
              
              <!-- Results Per Page -->
              <select
                v-model="resultsPerPage"
                class="appearance-none bg-white border border-gray-200 rounded-xl px-3 py-2 pr-8 text-sm font-medium text-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-300 transition-all duration-200"
                @change="handleResultsPerPageChange"
              >
                <option value="12">12 per page</option>
                <option value="24">24 per page</option>
                <option value="48">48 per page</option>
                <option value="96">96 per page</option>
              </select>
              
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
          
          <!-- Enhanced Content Type Filters -->
          <div class="flex flex-wrap gap-3">
            <button
              @click="clearAllFilters"
              :class="[
                activeFilters.length === 0
                  ? 'bg-gray-900 text-white shadow-lg border-gray-900'
                  : 'bg-white text-gray-700 hover:bg-gray-50 border-gray-200',
                'inline-flex items-center px-4 py-2 border rounded-xl text-sm font-medium transition-all duration-200 hover:shadow-md'
              ]"
            >
              <span class="mr-2">🔍</span>
              All
              <span v-if="totalResults > 0" class="ml-2 px-2 py-0.5 bg-black/10 rounded-full text-xs">
                {{ totalResults }}
              </span>
            </button>
            
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
              <span v-if="filter.count > 0" class="ml-2 px-2 py-0.5 bg-black/10 rounded-full text-xs font-medium">
                {{ filter.count }}
              </span>
            </button>
          </div>
        </div>
      </div>

      <!-- Enhanced Loading State with Skeletons -->
      <div v-if="isLoading" class="space-y-6">
        <!-- Loading Stats Bar -->
        <div class="bg-white rounded-2xl border border-gray-100 p-6">
          <div class="flex items-center justify-between">
            <div class="flex items-center space-x-4">
              <div class="h-6 bg-gray-200 rounded-lg w-32 animate-pulse"></div>
              <div class="h-4 bg-gray-200 rounded w-16 animate-pulse"></div>
            </div>
            <div class="flex space-x-3">
              <div class="h-10 bg-gray-200 rounded-xl w-24 animate-pulse"></div>
              <div class="h-10 bg-gray-200 rounded-xl w-32 animate-pulse"></div>
            </div>
          </div>
          <div class="flex space-x-3 mt-6">
            <div v-for="i in 5" :key="i" class="h-10 bg-gray-200 rounded-xl w-20 animate-pulse"></div>
          </div>
        </div>
        
        <!-- Loading Grid based on view mode -->
        <div :class="getGridClasses()">
          <div
            v-for="i in parseInt(resultsPerPage)"
            :key="i"
            class="bg-white rounded-2xl border border-gray-100 overflow-hidden animate-pulse"
            :class="viewMode === 'list' ? 'flex items-center space-x-4 p-4' : ''"
          >
            <div 
              v-if="viewMode !== 'list'"
              class="aspect-video bg-gradient-to-br from-gray-100 to-gray-200"
            ></div>
            <div 
              v-else 
              class="w-20 h-20 bg-gradient-to-br from-gray-100 to-gray-200 rounded-lg flex-shrink-0"
            ></div>
            <div :class="viewMode === 'list' ? 'flex-1 space-y-2' : 'p-4 space-y-3'">
              <div class="h-4 bg-gray-200 rounded w-3/4"></div>
              <div class="h-3 bg-gray-200 rounded w-1/2"></div>
              <div v-if="viewMode === 'list'" class="h-3 bg-gray-200 rounded w-2/3"></div>
            </div>
          </div>
        </div>
      </div>

      <!-- Search Results with Multiple View Modes -->
      <div v-else-if="searchResults.length > 0" class="space-y-6">
        <!-- Results Container -->
        <div :class="getGridClasses()">
          <div
            v-for="result in searchResults"
            :key="`${result.type}-${result.id}`"
            :class="getResultCardClasses(result)"
            @click="openResult(result)"
          >
            <!-- Result Image/Thumbnail with optimized loading -->
            <div 
              v-if="viewMode !== 'list'"
              class="relative bg-gradient-to-br from-gray-100 to-gray-200 overflow-hidden"
              :class="getThumbnailContainerClass(result)"
            >
              <img
                v-if="result.thumbnail"
                :src="result.thumbnail"
                :alt="result.title"
                class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                loading="lazy"
                @error="handleImageError"
              />
              <div v-else class="w-full h-full flex items-center justify-center">
                <component :is="getResultIcon(result.type)" class="w-12 h-12 text-gray-400" />
              </div>
              
              <!-- Enhanced Badges Container -->
              <div class="absolute top-3 right-3 flex flex-col items-end gap-2 max-w-[calc(100%-6rem)]">
                <!-- Type Badge -->
                <span class="inline-flex items-center px-3 py-1 rounded-lg text-xs font-bold bg-black/70 backdrop-blur-sm text-white border border-white/20">
                  <span class="mr-1.5">{{ getTypeIcon(result.type) }}</span>
                  {{ result.type.toUpperCase() }}
                </span>
                
                <!-- Additional Badges -->
                <div class="flex flex-col items-end gap-1">
                  <span v-if="result.hasAnimation" class="inline-flex items-center px-2 py-1 rounded-lg text-xs font-bold bg-gradient-to-r from-purple-500 to-pink-500 text-white shadow-lg">
                    🎬 ANIMATED
                  </span>
                  
                  <span v-if="result.isVideo && result.type !== 'template'" class="inline-flex items-center px-2 py-1 rounded-lg text-xs font-bold bg-gradient-to-r from-red-500 to-pink-600 text-white shadow-lg">
                    🎥 VIDEO
                  </span>
                  
                  <span v-if="result.duration && (result.hasAnimation || result.isVideo) && result.type !== 'template'" class="inline-flex items-center px-2 py-1 rounded-lg text-xs font-medium bg-black/70 text-white shadow-lg">
                    {{ formatDuration(result.duration) }}
                  </span>
                  
                  <span v-if="result.isPremium" class="inline-flex items-center px-2 py-1 rounded-lg text-xs font-bold bg-gradient-to-r from-yellow-400 to-orange-500 text-white shadow-lg">
                    ⭐ PRO
                  </span>
                </div>
              </div>
              
              <!-- Enhanced Hover Actions -->
              <div class="absolute inset-0 bg-gradient-to-t from-black/50 via-black/20 to-transparent opacity-0 group-hover:opacity-100 transition-all duration-300">
                <div class="absolute bottom-3 left-3 right-3">
                  <div class="flex items-center justify-center gap-2">
                    <template v-if="result.type === 'design'">
                      <button 
                        @click.stop="editDesign(result)"
                        class="flex-1 bg-white/90 backdrop-blur-md text-gray-900 px-3 py-2 rounded-lg text-sm font-medium hover:bg-white hover:scale-105 transform transition-all duration-200 flex items-center justify-center gap-1.5 shadow-lg border border-white/20"
                      >
                        <PencilIcon class="w-4 h-4" />
                        <span>Edit</span>
                      </button>
                      <button 
                        @click.stop="exportDesign(result)"
                        class="flex-1 bg-indigo-600/90 backdrop-blur-md text-white px-3 py-2 rounded-lg text-sm font-medium hover:bg-indigo-700 hover:scale-105 transform transition-all duration-200 flex items-center justify-center gap-1.5 shadow-lg border border-indigo-500/30"
                      >
                        <ArrowDownTrayIcon class="w-4 h-4" />
                        <span>Export</span>
                      </button>
                    </template>
                    
                    <template v-else-if="result.type === 'template'">
                      <button 
                        @click.stop="useTemplate(result)"
                        class="w-full bg-white/90 backdrop-blur-md text-gray-900 px-3 py-2 rounded-lg text-sm font-medium hover:bg-white hover:scale-105 transform transition-all duration-200 flex items-center justify-center gap-1.5 shadow-lg border border-white/20"
                      >
                        <DocumentDuplicateIcon class="w-4 h-4" />
                        <span>Use Template</span>
                      </button>
                    </template>
                    
                    <template v-else-if="result.type === 'media'">
                      <button 
                        @click.stop="addToDesign(result)"
                        class="flex-1 bg-white/90 backdrop-blur-md text-gray-900 px-3 py-2 rounded-lg text-sm font-medium hover:bg-white hover:scale-105 transform transition-all duration-200 flex items-center justify-center gap-1.5 shadow-lg border border-white/20"
                      >
                        <PlusIcon class="w-4 h-4" />
                        <span>Add</span>
                      </button>
                      <button 
                        @click.stop="openMediaPreview(result)"
                        class="flex-1 bg-indigo-600/90 backdrop-blur-md text-white px-3 py-2 rounded-lg text-sm font-medium hover:bg-indigo-700 hover:scale-105 transform transition-all duration-200 flex items-center justify-center gap-1.5 shadow-lg border border-indigo-500/30"
                      >
                        <EyeIcon class="w-4 h-4" />
                        <span>Preview</span>
                      </button>
                    </template>
                    
                    <template v-else-if="result.type === 'export'">
                      <button 
                        v-if="result.exportStatus === 'completed'"
                        @click.stop="downloadExport(result)"
                        class="w-full bg-green-600/90 backdrop-blur-md text-white px-3 py-2 rounded-lg text-sm font-medium hover:bg-green-700 hover:scale-105 transform transition-all duration-200 flex items-center justify-center gap-1.5 shadow-lg border border-green-500/30"
                      >
                        <ArrowDownTrayIcon class="w-4 h-4" />
                        <span>Download</span>
                      </button>
                      <div 
                        v-else
                        class="w-full bg-amber-500/90 backdrop-blur-md text-white px-3 py-2 rounded-lg text-sm font-medium flex items-center justify-center gap-1.5 shadow-lg border border-amber-400/30"
                      >
                        <div class="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin" v-if="result.exportStatus === 'processing'"></div>
                        <ClockIcon class="w-4 h-4" v-else />
                        <span>{{ getExportStatusLabel(result.exportStatus) }}</span>
                      </div>
                    </template>
                  </div>
                </div>
              </div>
            </div>
            
            <!-- List View Thumbnail -->
            <div v-if="viewMode === 'list'" class="w-20 h-20 bg-gradient-to-br from-gray-100 to-gray-200 rounded-lg overflow-hidden flex-shrink-0">
              <img
                v-if="result.thumbnail"
                :src="result.thumbnail"
                :alt="result.title"
                class="w-full h-full object-cover"
                loading="lazy"
                @error="handleImageError"
              />
              <div v-else class="w-full h-full flex items-center justify-center">
                <component :is="getResultIcon(result.type)" class="w-8 h-8 text-gray-400" />
              </div>
            </div>
            
            <!-- Result Info -->
            <div :class="viewMode === 'list' ? 'flex-1 min-w-0 px-4' : 'p-4'">
              <div class="flex items-start justify-between mb-2">
                <h3 class="font-semibold text-gray-900 group-hover:text-indigo-600 transition-colors duration-200 line-clamp-2 flex-1">
                  {{ result.title }}
                </h3>
                <div v-if="viewMode === 'list'" class="ml-4">
                  <span class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium bg-gray-100 text-gray-800">
                    {{ result.type.toUpperCase() }}
                  </span>
                </div>
              </div>
              
              <p v-if="result.description" class="text-sm text-gray-600 mb-3 line-clamp-2">
                {{ result.description }}
              </p>
              
              <div class="flex items-center justify-between text-sm">
                <div class="flex items-center space-x-2 text-gray-500">
                  <span v-if="result.created_at">{{ formatDate(result.created_at) }}</span>
                  <span v-if="result.author" class="flex items-center">
                    <template v-if="result.created_at">• by {{ result.author }}</template>
                    <template v-else>by {{ result.author }}</template>
                  </span>
                </div>
                <div v-if="result.stats" class="flex items-center space-x-3 text-gray-500">
                  <span class="flex items-center">
                    <HeartIcon class="w-3 h-3 mr-1" />
                    {{ formatNumber(result.stats.likes) }}
                  </span>
                  <span class="flex items-center">
                    <EyeIcon class="w-3 h-3 mr-1" />
                    {{ formatNumber(result.stats.views) }}
                  </span>
                </div>
              </div>
              
              <!-- List View Actions -->
              <div v-if="viewMode === 'list'" class="flex items-center gap-2 mt-3">
                <template v-if="result.type === 'design'">
                  <button 
                    @click.stop="editDesign(result)"
                    class="inline-flex items-center gap-1 px-3 py-1.5 bg-indigo-50 text-indigo-700 rounded-lg text-sm font-medium hover:bg-indigo-100 transition-colors"
                  >
                    <PencilIcon class="w-3 h-3" />
                    Edit
                  </button>
                  <button 
                    @click.stop="exportDesign(result)"
                    class="inline-flex items-center gap-1 px-3 py-1.5 bg-gray-50 text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-100 transition-colors"
                  >
                    <ArrowDownTrayIcon class="w-3 h-3" />
                    Export
                  </button>
                </template>
                
                <template v-else-if="result.type === 'template'">
                  <button 
                    @click.stop="useTemplate(result)"
                    class="inline-flex items-center gap-1 px-3 py-1.5 bg-indigo-50 text-indigo-700 rounded-lg text-sm font-medium hover:bg-indigo-100 transition-colors"
                  >
                    <DocumentDuplicateIcon class="w-3 h-3" />
                    Use Template
                  </button>
                </template>
                
                <template v-else-if="result.type === 'media'">
                  <button 
                    @click.stop="addToDesign(result)"
                    class="inline-flex items-center gap-1 px-3 py-1.5 bg-indigo-50 text-indigo-700 rounded-lg text-sm font-medium hover:bg-indigo-100 transition-colors"
                  >
                    <PlusIcon class="w-3 h-3" />
                    Add to Design
                  </button>
                  <button 
                    @click.stop="openMediaPreview(result)"
                    class="inline-flex items-center gap-1 px-3 py-1.5 bg-gray-50 text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-100 transition-colors"
                  >
                    <EyeIcon class="w-3 h-3" />
                    Preview
                  </button>
                </template>
                
                <template v-else-if="result.type === 'export'">
                  <button 
                    v-if="result.exportStatus === 'completed'"
                    @click.stop="downloadExport(result)"
                    class="inline-flex items-center gap-1 px-3 py-1.5 bg-green-50 text-green-700 rounded-lg text-sm font-medium hover:bg-green-100 transition-colors"
                  >
                    <ArrowDownTrayIcon class="w-3 h-3" />
                    Download
                  </button>
                  <span v-else class="inline-flex items-center gap-1 px-3 py-1.5 bg-amber-50 text-amber-700 rounded-lg text-sm font-medium">
                    <div class="w-3 h-3 border-2 border-amber-600 border-t-transparent rounded-full animate-spin" v-if="result.exportStatus === 'processing'"></div>
                    <ClockIcon class="w-3 h-3" v-else />
                    {{ getExportStatusLabel(result.exportStatus) }}
                  </span>
                </template>
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
      <Pagination
        :current-page="currentPage"
        :total-pages="totalPages"
        @page-change="goToPage"
      />
    </div>
    
    <!-- Design Export Modal -->
    <CompactDesignExportModal
      :is-open="showExportModal"
      :design="designToExport"
      @close="closeExportModal"
      @exported="handleExportComplete"
    />
    
    <!-- Media Preview Modal -->
    <MediaPreviewModal
      :is-open="showMediaPreview"
      :media="mediaToPreview"
      @close="closeMediaPreview"
      @addToDesign="handleAddToDesign"
      @download="handleDownloadMedia"
    />
    
    <!-- Export Details Modal -->
    <ExportDetailsModal
      :is-open="showExportDetailsModal"
      :export-job="exportJobFromResult"
      @close="closeExportDetailsModal"
      @download="handleExportDownload"
      @retry="handleExportRetry"
      @cancel="handleExportCancel"
      @share="handleExportShare"
      @duplicate="handleExportDuplicate"
      @edit-original="handleEditOriginal"
    />
    
    <!-- Using Template Loading Overlay -->
    <div v-if="isUsingTemplate" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
      <div class="bg-white rounded-lg p-6 max-w-sm mx-4 text-center">
        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mx-auto mb-4"></div>
        <h3 class="text-lg font-semibold text-gray-900 mb-2">Creating Design</h3>
        <p class="text-gray-600">Please wait while we set up your template...</p>
      </div>
    </div>
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
import Pagination from '@/components/common/Pagination.vue'
import CompactDesignExportModal from '@/components/modals/CompactDesignExportModal.vue'
import MediaPreviewModal from '@/components/modals/MediaPreviewModal.vue'
import ExportDetailsModal from '@/components/modals/ExportDetailsModal.vue'
import { searchAPI, templateAPI } from '@/services/api'
import { useDesignStore } from '@/stores/design'
import type { SearchResult, ContentFilter, MediaItem, MediaSearchItem } from '@/types'

// Define interface for MediaPreviewModal compatibility
interface MediaPreviewItem {
  id: string
  title: string
  url: string
  type: string
  size?: number
  author?: string
  created_at: string // Always provide a default value
  thumbnail?: string
  dimensions?: {
    width: number
    height: number
  }
  metadata?: Record<string, any>
  tags?: string[]
}

// Router and Route
const route = useRoute()
const router = useRouter()

// Stores
const designStore = useDesignStore()

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

// View and interaction state
const viewMode = ref<'masonry' | 'grid' | 'list'>('masonry')
const resultsPerPage = ref('24')

// Export modal state
const showExportModal = ref(false)
const designToExport = ref<SearchResult | null>(null)

// Export details modal state
const showExportDetailsModal = ref(false)
const exportToView = ref<SearchResult | null>(null)

// Media preview modal state
const showMediaPreview = ref(false)
const mediaToPreview = ref<MediaPreviewItem | null>(null)

// Template usage state
const isUsingTemplate = ref(false)

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

const formatNumber = (num: number): string => {
  if (num >= 1000000) {
    return (num / 1000000).toFixed(1) + 'M'
  }
  if (num >= 1000) {
    return (num / 1000).toFixed(1) + 'K'
  }
  return num.toString()
}

const getGridClasses = (): string => {
  switch (viewMode.value) {
    case 'list':
      return 'space-y-4'
    case 'grid':
      return 'grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6'
    case 'masonry':
    default:
      return 'masonry-grid'
  }
}

const getResultCardClasses = (result: SearchResult): string => {
  const baseClasses = 'group bg-white rounded-2xl border border-gray-100 overflow-hidden hover:shadow-xl hover:shadow-indigo-500/10 transform hover:-translate-y-1 transition-all duration-300 cursor-pointer relative'
  
  if (viewMode.value === 'list') {
    return `${baseClasses} flex items-center space-x-4 p-4`
  } else if (viewMode.value === 'masonry') {
    return `${baseClasses} break-inside-avoid mb-6`
  } else {
    return baseClasses
  }
}

const handleImageError = (event: Event) => {
  const img = event.target as HTMLImageElement
  img.style.display = 'none'
}

const handleResultsPerPageChange = () => {
  currentPage.value = 1
  performSearch()
}

const clearAllFilters = () => {
  activeFilters.value = []
  currentPage.value = 1
  performSearch()
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
    
    // Prepare API parameters for search
    const searchParams = {
      q: searchQuery.value || '',
      page: currentPage.value,
      limit: parseInt(resultsPerPage.value),
      sort: sortBy.value !== 'relevance' ? (sortBy.value as 'newest' | 'popular' | 'name') : undefined,
    }
    
    let response;
    
    // Determine search type and call appropriate endpoint based on active filters
    if (activeFilters.value.length === 1) {
      if (activeFilters.value.includes('template')) {
        response = await searchAPI.searchTemplates(searchParams)
        searchResults.value = response.data.templates.map((template: any) => ({
          ...template,
          type: 'template',
          title: template.name,
          ...(template.created_at || template.updatedAt ? { created_at: template.created_at || template.updatedAt } : {})
        }))
        totalResults.value = response.data.pagination.total
        totalPages.value = response.data.pagination.totalPages
      } else if (activeFilters.value.includes('media')) {
        response = await searchAPI.searchMedia(searchParams)
        searchResults.value = response.data.media.map((media: MediaSearchItem) => ({
          id: String(media.id), // Convert to string to match SearchResult type
          type: 'media',
          title: media.name,
          url: media.url,
          thumbnail: media.thumbnail_url || undefined,
          created_at: media.created_at || new Date().toISOString(),
          description: media.tags?.join(', '),
          isVideo: media.mime_type.startsWith('video/'),
          size: media.size
        }))
        totalResults.value = response.data.pagination.total
        totalPages.value = response.data.pagination.totalPages
      } else if (activeFilters.value.includes('design')) {
        // Map to projects search since designs are stored as projects
        response = await searchAPI.searchProjects(searchParams)
        searchResults.value = response.data.projects.map((project: any) => ({
          ...project,
          type: 'design',
          title: project.name,
          created_at: project.created_at || project.updatedAt
        }))
        totalResults.value = response.data.pagination.total
        totalPages.value = response.data.pagination.totalPages
      } else {
        // For other filters (like export), fall back to global search
        response = await searchAPI.unifiedSearch({
          ...searchParams,
          type: 'all',
          filters: activeFilters.value
        })
        searchResults.value = response.data.results.map((item: any) => ({
          ...item,
          title: item.name,
          created_at: item.created_at || item.updatedAt
        }))
        totalResults.value = response.data.pagination.total
        totalPages.value = response.data.pagination.totalPages
      }
    } else {
      // Use global search for multiple filters or no filters
      response = await searchAPI.unifiedSearch({
        ...searchParams,
        type: 'all',
        filters: activeFilters.value.length > 0 ? activeFilters.value : undefined
      })
      searchResults.value = response.data.results.map((item: any) => ({
        ...item,
        title: item.name,
        created_at: item.created_at || item.updatedAt
      }))
      totalResults.value = response.data.pagination.total
      totalPages.value = response.data.pagination.totalPages
    }
    
   
    
    // Update filter counts based on search results
    contentFilters.value.forEach(filter => {
      filter.count = searchResults.value.filter(result => result.type === filter.type).length
    })
    
    searchTime.value = Date.now() - startTime
  } catch (error) {
    console.error('Search failed:', error)
    searchResults.value = []
    totalResults.value = 0
    totalPages.value = 1
    
    // Reset filter counts
    contentFilters.value.forEach(filter => {
      filter.count = 0
    })
    
    searchTime.value = Date.now() - startTime
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
  // Handle different result types with specific actions
  switch (result.type) {
    case 'media':
      // For media, open media preview modal
      openMediaPreview(result)
      break
    case 'design':
      // For designs, open in editor directly (same as Designs.vue)
      router.push(`/editor/${result.id}`)
      break
    case 'template':
      // For templates, use the template API to create a new design (same as Templates.vue)
      handleTemplateSelected(result)
      break
    case 'export':
      // For exports, show export details modal
      openExportDetails(result)
      break
    default:
      console.log('Opening result:', result)
  }
}

// Open image in editor using DSN format (similar to MediaPanel)
const openInEditor = (result: SearchResult) => {
  // Create DSN based on result source
  let dsn: string
  
  if (!result.url) {
    console.warn('Cannot create DSN: result has no URL', result)
    return
  }
  
  if (result.type === 'media') {
    // Base64 encode the URL as per DSN specification
    const encodedUrl = btoa(result.id || result.url)
    dsn = `upload:media:${encodedUrl}`
    console.log(`🔗 Generated DSN: ${dsn} (URL: ${result.url})`)
    // Navigate to editor with DSN
    router.push({ name: 'Editor', params: { id: dsn } })
  } else {
    // For other types that might be stock media, we need to determine the source
    // This could be based on the URL domain or other properties
    let source = 'unknown'
    
    if (result.url.includes('unsplash.com') || result.url.includes('images.unsplash.com')) {
      source = 'unsplash'
    } else if (result.url.includes('pexels.com') || result.url.includes('images.pexels.com')) {
      source = 'pexels'
    } else if (result.url.includes('iconfinder.com') || result.url.includes('cdn.iconfinder.com')) {
      source = 'iconfinder'
    }
    
    if (source !== 'unknown') {
      // For stock media, base64 encode the URL
      const encodedUrl = btoa(result.url)
      dsn = `stock:${source}:${encodedUrl}`
      console.log(`🔗 Generated stock DSN: ${dsn} (URL: ${result.url}, source: ${source})`)
      // Navigate to editor with DSN
      router.push({ name: 'Editor', params: { id: dsn } })
    } else {
      console.warn('Cannot determine source for result:', result)
    }
  }
}

// Media preview handlers
const convertToMediaItem = (result: SearchResult): MediaPreviewItem => {
  // Determine proper MIME type based on result properties
  let mimeType = 'image/jpeg' // Default to image
  let mediaUrl = result.url || result.thumbnail || ''
  
  // Determine mime type from URL extension or result metadata
  if (result.isVideo) {
    mimeType = 'video/mp4'
  } else if (mediaUrl.includes('.png')) {
    mimeType = 'image/png'
  } else if (mediaUrl.includes('.gif')) {
    mimeType = 'image/gif'
  } else if (mediaUrl.includes('.svg')) {
    mimeType = 'image/svg+xml'
  } else if (mediaUrl.includes('.webm')) {
    mimeType = 'video/webm'
  }
  
  console.log('Converting SearchResult to MediaPreviewItem:', { 
    resultType: result.type, 
    isVideo: result.isVideo, 
    mimeType, 
    mediaUrl 
  })
  
  // Convert SearchResult to MediaPreviewItem format
  return {
    id: result.id,
    title: result.title,
    url: mediaUrl,
    type: mimeType,
    size: 0, // File size not available in SearchResult type
    thumbnail: result.thumbnail,
    dimensions: {
      width: result.width || 800,
      height: result.height || 600
    }, // Use actual dimensions from result, default to standard size if not available
    tags: ['media'], // Tags not available in SearchResult type
    created_at: result.created_at || new Date().toISOString() // Provide default if missing
  }
}

const openMediaPreview = (result: SearchResult) => {
  mediaToPreview.value = convertToMediaItem(result)
  showMediaPreview.value = true
}

const closeMediaPreview = () => {
  showMediaPreview.value = false
  mediaToPreview.value = null
}

const handleAddToDesign = (media: MediaPreviewItem) => {
  // Add media to current design or create new design
  router.push(`/editor?media=${media.id}`)
  closeMediaPreview()
}

const handleDownloadMedia = (media: MediaPreviewItem) => {
  // Download the media file
  console.log('Downloading media:', media.title)
  
  // Create temporary download link
  const link = document.createElement('a')
  link.href = media.url
  link.download = media.title || 'media-file'
  link.target = '_blank'
  document.body.appendChild(link)
  link.click()
  document.body.removeChild(link)
  
  // Optional: Show success notification
  console.log(`Download started for "${media.title}"`)
}

// Export details handlers
const openExportDetails = (result: SearchResult) => {
  exportToView.value = result
  showExportDetailsModal.value = true
}

const closeExportDetailsModal = () => {
  showExportDetailsModal.value = false
  exportToView.value = null
}


// Type-specific action handlers
const editDesign = (result: SearchResult) => {
  // Follow the same pattern as Designs.vue - direct navigation to editor
  router.push(`/editor/${result.id}`)
}

const exportDesign = (result: SearchResult) => {
  designToExport.value = result
  showExportModal.value = true
}

const useTemplate = (result: SearchResult) => {
  // Follow the same pattern as Templates.vue - use the template API to create a new design
  handleTemplateSelected(result)
}

const handleTemplateSelected = async (template: SearchResult) => {
  if (isUsingTemplate.value) return // Prevent multiple clicks
  
  try {
    isUsingTemplate.value = true
    console.log(`🔗 Using template: ${template.title} (ID: ${template.id})`)
    
    // Use the template API to create a design from template
    // Templates have both id and uuid - use uuid for the API if available, fallback to id
    const templateId = (template as any).uuid || template.id
    const response = await templateAPI.useTemplate(templateId, {
      name: `${template.title} Copy`
    })
    
    if (response.data?.success && response.data.data) {
      console.log('Template used successfully, navigating to editor...')
      // Navigate to editor with the new design
      router.push(`/editor/${response.data.data.id}`)
    } else {
      throw new Error('Failed to create design from template')
    }
  } catch (error) {
    console.error('Failed to create design from template:', error)
    console.error('Template application failed, trying fallback method...')
    
    // Fallback to manual creation if API fails (same as Templates.vue)
    try {
      const newDesign = designStore.createNewDesign(
        (template as any).width || 800,
        (template as any).height || 600
      )
      
      if ((template as any).designData) {
        newDesign.data = { ...(template as any).designData }
      }
      
      newDesign.name = `${template.title} Copy`
      
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
      console.error('Both template application methods failed')
      // Final fallback: navigate to editor with template query parameter
      router.push({ 
        name: 'Editor', 
        query: { template: template.id }
      })
    }
  } finally {
    isUsingTemplate.value = false
  }
}

const addToDesign = (result: SearchResult) => {
  // Navigate to editor with media query parameter
  // The editor should handle adding this media to a new design
  if (result.type === 'media') {
    router.push({ 
      name: 'Editor', 
      query: { media: result.id }
    })
    console.log(`🔗 Adding media to design: ${result.title} (ID: ${result.id})`)
  } else {
    console.warn('addToDesign called with non-media result:', result)
  }
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

const getExportStatusClass = (status?: string): string => {
  const statusClasses: Record<string, string> = {
    pending: 'text-yellow-600',
    processing: 'text-blue-600',
    completed: 'text-green-600',
    failed: 'text-red-600'
  }
  return statusClasses[status || 'pending'] || 'text-gray-600'
}

// Determine thumbnail container aspect ratio based on content type and dimensions
const getThumbnailContainerClass = (result: SearchResult): string => {
  const { type, width, height, isVideo } = result
  
  // Calculate aspect ratio if dimensions are available
  let aspectRatio = 16 / 9 // Default video aspect ratio
  
  if (width && height) {
    aspectRatio = width / height
  }
  
  // Different aspect ratios based on content type and dimensions
  if (type === 'design') {
    if (aspectRatio > 2.5) return 'aspect-[5/2]' // Wide banners
    if (aspectRatio > 1.8) return 'aspect-[21/9]' // Ultra-wide
    if (aspectRatio > 1.3) return 'aspect-video' // Landscape
    if (aspectRatio < 0.6) return 'aspect-[2/3]' // Tall portrait
    if (aspectRatio < 0.9) return 'aspect-[3/4]' // Portrait
    return 'aspect-square' // Square designs
  }
  
  if (type === 'template') {
    // Templates often have standard aspect ratios
    if (aspectRatio > 1.5) return 'aspect-video'
    if (aspectRatio < 0.8) return 'aspect-[3/4]'
    return 'aspect-square'
  }
  
  if (type === 'media') {
    if (isVideo) return 'aspect-video' // Videos are typically 16:9
    if (aspectRatio > 2) return 'aspect-[5/2]' // Wide photos
    if (aspectRatio > 1.5) return 'aspect-video' // Landscape photos
    if (aspectRatio < 0.7) return 'aspect-[3/4]' // Portrait photos
    return 'aspect-square' // Square media
  }
  
  // Default for exports and other types
  return 'aspect-video'
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

// Computed property to convert SearchResult to ExportJob format
const exportJobFromResult = computed(() => {
  if (!exportToView.value) return null
  
  const result = exportToView.value
  return {
    id: result.id,
    title: result.title,
    description: result.description,
    thumbnail: result.thumbnail,
    exportStatus: result.exportStatus || 'pending',
    created_at: result.created_at || new Date().toISOString(), // Provide default if missing
    author: result.author,
    url: result.url,
    dimensions: {
      width: 1920, // Default dimensions, could be dynamic
      height: 1080
    },
    format: 'PNG', // Default format, could be dynamic
    quality: 'High', // Default quality, could be dynamic
    metadata: {}
  }
})

// ExportDetailsModal event handlers
const handleExportDownload = (job: any) => {
  if (job.url && job.exportStatus === 'completed') {
    // Create temporary download link
    const link = document.createElement('a')
    link.href = job.url
    link.download = `${job.title}.png` // Could be dynamic based on format
    link.target = '_blank'
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
    
    console.log(`Download started for export: "${job.title}"`)
  } else {
    console.log('Export not ready for download:', job.exportStatus)
  }
}

const handleExportRetry = (job: any) => {
  console.log('Retrying export:', job.title)
  // Implement retry logic here
  // This could trigger a new export job or restart a failed one
}

const handleExportCancel = (job: any) => {
  console.log('Cancelling export:', job.title)
  // Implement cancel logic here
  // This would stop a processing export job
}

const handleExportShare = (job: any) => {
  console.log('Sharing export:', job.title)
  // Implement share logic here
  // This could open a share modal or copy a link to clipboard
}

const handleExportDuplicate = (job: any) => {
  console.log('Duplicating export:', job.title)
  // Implement duplicate logic here
  // This could open the export modal with the same settings
}

const handleEditOriginal = (job: any) => {
  console.log('Editing original design for export:', job.title)
  // Navigate to editor with the original design
  router.push(`/editor/${job.id}`)
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

/* Enhanced Masonry-style grid for varied content */
.masonry-grid {
  columns: 1;
  column-gap: 1.5rem;
}

@media (min-width: 640px) {
  .masonry-grid {
    columns: 2;
  }
}

@media (min-width: 1024px) {
  .masonry-grid {
    columns: 3;
  }
}

@media (min-width: 1280px) {
  .masonry-grid {
    columns: 4;
  }
}

@media (min-width: 1536px) {
  .masonry-grid {
    columns: 5;
  }
}

/* Enhanced button styling and hover effects */
button {
  transition: all 0.2s ease;
}

button:hover {
  transform: translateY(-1px);
}

button:active {
  transform: translateY(0);
}

/* Improved hover actions with better backdrop effects */
.group:hover .hover-actions {
  backdrop-filter: blur(12px);
}

/* Enhanced button animations */
@keyframes buttonPulse {
  0%, 100% {
    transform: scale(1);
  }
  50% {
    transform: scale(1.05);
  }
}

.button-pulse:hover {
  animation: buttonPulse 0.3s ease-in-out;
}

/* Better shadow effects for buttons */
.button-shadow {
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
}

.button-shadow:hover {
  box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
}

/* Improved loading states */
.loading-button {
  pointer-events: none;
  opacity: 0.7;
}

/* Better focus states for accessibility */
button:focus-visible {
  outline: 2px solid #4f46e5;
  outline-offset: 2px;
}

/* Enhanced disabled state */
button:disabled {
  opacity: 0.5;
  cursor: not-allowed;
  transform: none !important;
}

/* Improved animations and transitions */
.group:hover .group-hover\:scale-105 {
  transform: scale(1.05);
}

/* Enhanced focus states */
input:focus,
select:focus,
button:focus {
  outline: none;
  box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
}

/* Loading animation improvements */
@keyframes shimmer {
  0% {
    background-position: -200% 0;
  }
  100% {
    background-position: 200% 0;
  }
}

.animate-pulse {
  background: linear-gradient(90deg, #f3f4f6 25%, #e5e7eb 50%, #f3f4f6 75%);
  background-size: 200% 100%;
  animation: shimmer 2s infinite;
}

/* Smooth scroll behavior */
html {
  scroll-behavior: smooth;
}

/* Custom scrollbar for webkit browsers */
::-webkit-scrollbar {
  width: 6px;
  height: 6px;
}

::-webkit-scrollbar-track {
  background: #f1f5f9;
}

::-webkit-scrollbar-thumb {
  background: #cbd5e1;
  border-radius: 3px;
}

::-webkit-scrollbar-thumb:hover {
  background: #94a3b8;
}

/* Improved checkbox styling */
input[type="checkbox"] {
  appearance: none;
  width: 1rem;
  height: 1rem;
  border: 2px solid #d1d5db;
  border-radius: 0.25rem;
  background-color: white;
  position: relative;
  cursor: pointer;
  transition: all 0.2s ease;
}

input[type="checkbox"]:checked {
  background-color: #4f46e5;
  border-color: #4f46e5;
}

input[type="checkbox"]:checked::after {
  content: '';
  position: absolute;
  top: 1px;
  left: 4px;
  width: 4px;
  height: 8px;
  border: solid white;
  border-width: 0 2px 2px 0;
  transform: rotate(45deg);
}

input[type="checkbox"]:focus {
  outline: none;
  box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
  border-color: #4f46e5;
}

/* Badge styling improvements */
.badge-glow {
  box-shadow: 0 0 20px rgba(147, 51, 234, 0.3);
}

/* Improved hover effects for different view modes */
.grid-view-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
}

.list-view-card:hover {
  background-color: #f8fafc;
  border-color: #e2e8f0;
}

.masonry-view-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
}

/* Mobile responsiveness improvements */
@media (max-width: 640px) {
  .masonry-grid {
    columns: 1;
    column-gap: 1rem;
  }
  
  .grid {
    grid-template-columns: repeat(1, minmax(0, 1fr));
    gap: 1rem;
  }
}

/* Dark mode support (if needed) */
@media (prefers-color-scheme: dark) {
  .bg-white {
    background-color: #1f2937;
  }
  
  .text-gray-900 {
    color: #f9fafb;
  }
  
  .text-gray-600 {
    color: #d1d5db;
  }
  
  .border-gray-100 {
    border-color: #374151;
  }
}

/* Print styles */
@media print {
  .hover\:shadow-xl,
  .hover\:shadow-indigo-500\/10,
  .transform,
  .hover\:-translate-y-1 {
    box-shadow: none !important;
    transform: none !important;
  }
}
</style>

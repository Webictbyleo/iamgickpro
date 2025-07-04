<template>
  <AppLayout
    title="Dashboard"
    subtitle="Welcome back! Ready to create something amazing?"
    @search="handleSearch"
  >
    <div class="space-y-8">
      <!-- Enhanced Professional Hero Section -->
      <div class="relative overflow-hidden bg-white dark:bg-gray-900 rounded-2xl shadow-xl border border-gray-200 dark:border-gray-800">
        <!-- Modern background pattern -->
        <div class="absolute inset-0">
          <div class="absolute inset-0 opacity-20 dark:opacity-30">
            <div class="absolute inset-0" style="background-image: radial-gradient(circle at 1px 1px, rgba(0,0,0,0.06) 1px, transparent 0); background-size: 24px 24px;"></div>
          </div>
          <!-- Theme-aware gradient overlay -->
          <div class="absolute inset-0 bg-gradient-to-br from-blue-500/10 via-purple-500/5 to-indigo-500/10 dark:from-blue-600/8 dark:via-transparent dark:to-purple-600/8"></div>
        </div>
        
        <div class="relative px-6 py-8 lg:px-10 lg:py-12">
          <div class="flex flex-col xl:flex-row xl:items-center xl:justify-between gap-8">
            <!-- Enhanced Left Content - Welcome Section -->
            <div class="flex-1 max-w-2xl">
              <div class="flex items-start gap-6">
                <!-- Enhanced Profile Avatar with theme integration -->
                <div class="flex-shrink-0">
                  <div class="relative w-20 h-20 bg-gradient-to-br from-blue-500 to-purple-600 dark:from-blue-600 dark:to-purple-700 rounded-2xl flex items-center justify-center shadow-xl ring-4 ring-blue-500/20 dark:ring-blue-600/20">
                    <span class="text-white font-bold text-2xl">
                      {{ user?.firstName?.charAt(0) || user?.email?.charAt(0) || 'U' }}
                    </span>
                    <!-- Online indicator -->
                    <div class="absolute -bottom-1 -right-1 w-6 h-6 bg-success-500 rounded-full border-3 border-white dark:border-gray-900 flex items-center justify-center">
                      <div class="w-2 h-2 bg-white rounded-full animate-pulse"></div>
                    </div>
                  </div>
                </div>
                
                <!-- Enhanced Welcome Text -->
                <div class="flex-1 min-w-0">
                  <!-- Status badge -->
                  <div class="inline-flex items-center px-4 py-2 bg-success-500/20 rounded-full text-success-600 dark:text-success-300 text-sm font-medium mb-4 border border-success-500/30 backdrop-blur-sm">
                    <div class="w-2 h-2 bg-success-500 dark:bg-success-400 rounded-full mr-2 animate-pulse"></div>
                    Active Session
                  </div>
                  
                  <!-- Main welcome heading -->
                  <h1 class="text-3xl lg:text-4xl xl:text-5xl font-bold text-gray-900 dark:text-white leading-tight mb-3">
                    Welcome back,
                    <br class="sm:hidden" />
                    <span class="bg-gradient-to-r from-blue-600 to-purple-600 dark:from-blue-400 dark:to-purple-400 bg-clip-text text-transparent">
                      {{ user?.firstName || 'Creator' }}
                    </span>
                  </h1>
                  
                  <!-- Enhanced description -->
                  <p class="text-gray-600 dark:text-gray-300 text-lg lg:text-xl leading-relaxed mb-6">
                    Your creative workspace is ready. Transform your ideas into stunning designs with our powerful tools.
                  </p>
                  
                  <!-- Quick access actions for mobile -->
                  <div class="flex flex-wrap gap-3 xl:hidden">
                    <router-link
                      to="/editor"
                      class="inline-flex items-center px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl font-semibold transition-all duration-200 transform hover:scale-105 shadow-lg text-sm"
                    >
                      <component :is="icons.plus" class="w-4 h-4 mr-2" />
                      Create
                    </router-link>
                    <router-link
                      to="/templates"
                      class="inline-flex items-center px-5 py-2.5 bg-white/20 hover:bg-white/30 dark:bg-gray-800 dark:hover:bg-gray-700 text-gray-900 dark:text-white rounded-xl font-semibold transition-all duration-200 border border-gray-300/50 hover:border-gray-400/50 dark:border-gray-700 dark:hover:border-gray-600 text-sm backdrop-blur-sm"
                    >
                      <component :is="icons.template" class="w-4 h-4 mr-2" />
                      Browse
                    </router-link>
                  </div>
                </div>
              </div>
            </div>

            <!-- Enhanced Stats Grid -->
            <div class="flex-shrink-0">
              <div class="grid grid-cols-2 lg:grid-cols-4 xl:grid-cols-2 gap-4">
                <div class="group bg-gradient-to-br from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 rounded-xl p-4 transition-all duration-300 hover:transform hover:scale-105 hover:shadow-xl text-white">
                  <div class="flex items-center justify-between mb-3">
                    <div class="w-10 h-10 bg-white/20 rounded-xl flex items-center justify-center group-hover:bg-white/30 transition-colors backdrop-blur-sm">
                      <component :is="icons.design" class="w-5 h-5 text-white" />
                    </div>
                    <div class="w-2 h-2 bg-white/60 rounded-full group-hover:bg-white transition-colors"></div>
                  </div>
                  <div class="text-2xl font-bold text-white mb-1">{{ dashboardStats?.overview?.totalDesigns || 0 }}</div>
                  <div class="text-sm text-blue-100 font-medium">Total Projects</div>
                </div>
                
                <div class="group bg-gradient-to-br from-purple-500 to-purple-600 hover:from-purple-600 hover:to-purple-700 rounded-xl p-4 transition-all duration-300 hover:transform hover:scale-105 hover:shadow-xl text-white">
                  <div class="flex items-center justify-between mb-3">
                    <div class="w-10 h-10 bg-white/20 rounded-xl flex items-center justify-center group-hover:bg-white/30 transition-colors backdrop-blur-sm">
                      <component :is="icons.folder" class="w-5 h-5 text-white" />
                    </div>
                    <div class="w-2 h-2 bg-white/60 rounded-full group-hover:bg-white transition-colors"></div>
                  </div>
                  <div class="text-2xl font-bold text-white mb-1">{{ dashboardStats?.overview?.totalProjects || 0 }}</div>
                  <div class="text-sm text-purple-100 font-medium">Collections</div>
                </div>
                
                <div class="group bg-gradient-to-br from-orange-500 to-yellow-500 hover:from-orange-600 hover:to-yellow-600 rounded-xl p-4 transition-all duration-300 hover:transform hover:scale-105 hover:shadow-xl text-white">
                  <div class="flex items-center justify-between mb-3">
                    <div class="w-10 h-10 bg-white/20 rounded-xl flex items-center justify-center group-hover:bg-white/30 transition-colors backdrop-blur-sm">
                      <component :is="icons.download" class="w-5 h-5 text-white" />
                    </div>
                    <div class="w-2 h-2 bg-white/60 rounded-full group-hover:bg-white transition-colors"></div>
                  </div>
                  <div class="text-2xl font-bold text-white mb-1">{{ dashboardStats?.overview?.totalExports || 0 }}</div>
                  <div class="text-sm text-orange-100 font-medium">Downloads</div>
                </div>
                
                <div class="group bg-gradient-to-br from-emerald-500 to-green-600 hover:from-emerald-600 hover:to-green-700 rounded-xl p-4 transition-all duration-300 hover:transform hover:scale-105 hover:shadow-xl text-white">
                  <div class="flex items-center justify-between mb-3">
                    <div class="w-10 h-10 bg-white/20 rounded-xl flex items-center justify-center group-hover:bg-white/30 transition-colors backdrop-blur-sm">
                      <component :is="icons.template" class="w-5 h-5 text-white" />
                    </div>
                    <div class="w-2 h-2 bg-white/60 rounded-full group-hover:bg-white transition-colors"></div>
                  </div>
                  <div class="text-2xl font-bold text-white mb-1">{{ Math.round(dashboardStats?.overview?.successRate || 0) }}%</div>
                  <div class="text-sm text-emerald-100 font-medium">Success Rate</div>
                </div>
              </div>
            </div>

            <!-- Enhanced Action Buttons (Desktop) -->
            <div class="hidden xl:flex flex-shrink-0">
              <div class="flex flex-col gap-4">
                <router-link
                  to="/editor"
                  class="group inline-flex items-center px-8 py-4 bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white rounded-xl font-semibold transition-all duration-200 transform hover:scale-105 shadow-xl hover:shadow-2xl text-base"
                >
                  <component :is="icons.plus" class="w-5 h-5 mr-3 group-hover:rotate-90 transition-transform duration-200" />
                  Create New Design
                </router-link>
                <router-link
                  to="/templates"
                  class="group inline-flex items-center px-8 py-4 bg-white/20 hover:bg-white/30 dark:bg-gray-800 dark:hover:bg-gray-700 backdrop-blur-sm text-gray-900 dark:text-white rounded-xl font-semibold transition-all duration-200 border border-gray-300/50 hover:border-gray-400/50 dark:border-gray-700 dark:hover:border-gray-600 hover:shadow-lg text-base"
                >
                  <component :is="icons.template" class="w-5 h-5 mr-3 group-hover:scale-110 transition-transform duration-200" />
                  Browse Templates
                </router-link>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Enhanced Quick Actions Grid -->
      <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-200 dark:border-gray-700 p-8">
        <div class="flex items-center justify-between mb-8">
          <div>
            <h2 class="text-3xl font-bold text-gray-900 dark:text-white">Quick Actions</h2>
            <p class="text-gray-600 dark:text-gray-300 mt-2 text-lg">Start creating with these essential tools</p>
          </div>
          <div class="hidden sm:flex items-center space-x-2">
            <div class="w-2 h-2 bg-blue-500 dark:bg-blue-400 rounded-full animate-pulse"></div>
            <span class="text-sm text-gray-500 dark:text-gray-400 font-medium">Ready to create</span>
          </div>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
          <router-link
            to="/templates"
            class="group relative p-6 bg-gradient-to-br from-blue-50 to-blue-100 dark:from-blue-900/20 dark:to-blue-800/20 rounded-2xl hover:from-blue-100 hover:to-blue-200 dark:hover:from-blue-900/30 dark:hover:to-blue-800/30 transition-all duration-300 transform hover:scale-105 border border-blue-200/50 dark:border-blue-700/50 hover:border-blue-300 dark:hover:border-blue-600 shadow-sm hover:shadow-xl"
          >
            <div class="flex items-center justify-between mb-4">
              <div class="w-14 h-14 bg-blue-500 dark:bg-blue-600 rounded-2xl flex items-center justify-center group-hover:bg-blue-600 dark:group-hover:bg-blue-700 transition-colors shadow-lg ring-4 ring-blue-500/20 dark:ring-blue-600/20">
                <component :is="icons.template" class="w-7 h-7 text-white" />
              </div>
              <div class="text-blue-500 dark:text-blue-400 opacity-0 group-hover:opacity-100 transition-all duration-300 transform translate-x-2 group-hover:translate-x-0">
                <ArrowRightIcon class="w-6 h-6" />
              </div>
            </div>
            <h3 class="font-bold text-gray-900 dark:text-white text-lg mb-2">Templates</h3>
            <p class="text-gray-600 dark:text-gray-300">Professional designs ready to customize</p>
          </router-link>

          <router-link
            to="/media"
            class="group relative p-6 bg-gradient-to-br from-purple-50 to-purple-100 dark:from-purple-900/20 dark:to-purple-800/20 rounded-2xl hover:from-purple-100 hover:to-purple-200 dark:hover:from-purple-900/30 dark:hover:to-purple-800/30 transition-all duration-300 transform hover:scale-105 border border-purple-200/50 dark:border-purple-700/50 hover:border-purple-300 dark:hover:border-purple-600 shadow-sm hover:shadow-xl"
          >
            <div class="flex items-center justify-between mb-4">
              <div class="w-14 h-14 bg-purple-500 dark:bg-purple-600 rounded-2xl flex items-center justify-center group-hover:bg-purple-600 dark:group-hover:bg-purple-700 transition-colors shadow-lg ring-4 ring-purple-500/20 dark:ring-purple-600/20">
                <component :is="icons.media" class="w-7 h-7 text-white" />
              </div>
              <div class="text-purple-500 dark:text-purple-400 opacity-0 group-hover:opacity-100 transition-all duration-300 transform translate-x-2 group-hover:translate-x-0">
                <ArrowRightIcon class="w-6 h-6" />
              </div>
            </div>
            <h3 class="font-bold text-gray-900 dark:text-white text-lg mb-2">Media Library</h3>
            <p class="text-gray-600 dark:text-gray-300">Manage your images and assets</p>
          </router-link>

          <router-link
            to="/designs"
            class="group relative p-6 bg-gradient-to-br from-emerald-50 to-emerald-100 dark:from-emerald-900/20 dark:to-emerald-800/20 rounded-2xl hover:from-emerald-100 hover:to-emerald-200 dark:hover:from-emerald-900/30 dark:hover:to-emerald-800/30 transition-all duration-300 transform hover:scale-105 border border-emerald-200/50 dark:border-emerald-700/50 hover:border-emerald-300 dark:hover:border-emerald-600 shadow-sm hover:shadow-xl"
          >
            <div class="flex items-center justify-between mb-4">
              <div class="w-14 h-14 bg-emerald-500 dark:bg-emerald-600 rounded-2xl flex items-center justify-center group-hover:bg-emerald-600 dark:group-hover:bg-emerald-700 transition-colors shadow-lg ring-4 ring-emerald-500/20 dark:ring-emerald-600/20">
                <component :is="icons.folder" class="w-7 h-7 text-white" />
              </div>
              <div class="text-emerald-500 dark:text-emerald-400 opacity-0 group-hover:opacity-100 transition-all duration-300 transform translate-x-2 group-hover:translate-x-0">
                <ArrowRightIcon class="w-6 h-6" />
              </div>
            </div>
            <h3 class="font-bold text-gray-900 dark:text-white text-lg mb-2">My Projects</h3>
            <p class="text-gray-600 dark:text-gray-300">Browse and manage your designs</p>
          </router-link>

          <router-link
            to="/editor"
            class="group relative p-6 bg-gradient-to-br from-indigo-50 to-indigo-100 dark:from-indigo-900/20 dark:to-indigo-800/20 rounded-2xl hover:from-indigo-100 hover:to-indigo-200 dark:hover:from-indigo-900/30 dark:hover:to-indigo-800/30 transition-all duration-300 transform hover:scale-105 border border-indigo-200/50 dark:border-indigo-700/50 hover:border-indigo-300 dark:hover:border-indigo-600 shadow-sm hover:shadow-xl"
          >
            <div class="flex items-center justify-between mb-4">
              <div class="w-14 h-14 bg-indigo-500 dark:bg-indigo-600 rounded-2xl flex items-center justify-center group-hover:bg-indigo-600 dark:group-hover:bg-indigo-700 transition-colors shadow-lg ring-4 ring-indigo-500/20 dark:ring-indigo-600/20">
                <component :is="icons.plus" class="w-7 h-7 text-white" />
              </div>
              <div class="text-indigo-500 dark:text-indigo-400 opacity-0 group-hover:opacity-100 transition-all duration-300 transform translate-x-2 group-hover:translate-x-0">
                <ArrowRightIcon class="w-6 h-6" />
              </div>
            </div>
            <h3 class="font-bold text-gray-900 dark:text-white text-lg mb-2">Create Design</h3>
            <p class="text-gray-600 dark:text-gray-300">Start from scratch with our editor</p>
          </router-link>
        </div>
      </div>

      <!-- Enhanced Recent Designs Section -->
      <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-200 dark:border-gray-700">
        <div class="px-8 py-6 border-b border-gray-100 dark:border-gray-700">
          <div class="flex items-center justify-between">
            <div>
              <h2 class="text-3xl font-bold text-gray-900 dark:text-white">Recent Projects</h2>
              <p class="text-gray-600 dark:text-gray-300 mt-2 text-lg">Continue working on your latest designs</p>
            </div>
            <router-link
              to="/designs" 
              class="inline-flex items-center px-6 py-3 bg-blue-600 hover:bg-blue-700 dark:bg-blue-700 dark:hover:bg-blue-800 text-white rounded-xl font-semibold transition-all duration-200 transform hover:scale-105 shadow-lg text-sm"
            >
              View all
              <ArrowRightIcon class="w-4 h-4 ml-2" />
            </router-link>
          </div>
        </div>
        
        <div class="p-8">
          <div class="relative">
            <!-- Show loading cards first -->
            <div v-if="loading" class="flex overflow-x-auto scrollbar-thin scrollbar-thumb-gray-300 dark:scrollbar-thumb-gray-600 scrollbar-track-gray-100 dark:scrollbar-track-gray-800 pb-4 gap-6">
              <div 
                v-for="i in 3"
                :key="`loading-${i}`"
                class="flex-shrink-0 w-72 bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 animate-pulse overflow-hidden"
              >
                <div class="h-48 bg-gray-200 dark:bg-gray-700"></div>
                <div class="p-4 space-y-3">
                  <div class="h-5 bg-gray-200 dark:bg-gray-700 rounded w-3/4"></div>
                  <div class="flex justify-between items-center">
                    <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-1/3"></div>
                    <div class="h-6 bg-gray-200 dark:bg-gray-700 rounded w-20"></div>
                  </div>
                </div>
              </div>
            </div>
            
            <!-- Show designs when loaded -->
            <div v-else-if="recentDesigns.length > 0" class="flex overflow-x-auto scrollbar-thin scrollbar-thumb-gray-300 dark:scrollbar-thumb-gray-600 scrollbar-track-gray-100 dark:scrollbar-track-gray-800 pb-4 gap-6">
              <div
                v-for="design in recentDesigns"
                :key="design.id"
                class="flex-shrink-0 w-72"
                :data-design-id="design.id"
              >
                <!-- Professional Design Card -->
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600 hover:shadow-lg transition-all duration-300 overflow-hidden group">
                  <!-- Design Thumbnail Container -->
                  <div class="relative h-48 bg-gray-50 dark:bg-gray-700 flex items-center justify-center p-3">
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
                        class="w-full h-full object-cover rounded-lg shadow-sm cursor-pointer hover:scale-105 transition-transform duration-300"
                        loading="lazy"
                        @click="editDesign(design)"
                      />
                    </div>
                    <div 
                      v-else 
                      class="w-36 h-28 bg-gray-100 dark:bg-gray-600 rounded-lg flex items-center justify-center cursor-pointer hover:bg-gray-200 dark:hover:bg-gray-500 transition-colors"
                      @click="editDesign(design)"
                    >
                      <component :is="icons.design" class="w-10 h-10 text-gray-400 dark:text-gray-500" />
                    </div>
                    
                    <!-- Professional Dropdown Menu -->
                    <div class="absolute top-3 right-3">
                      <div class="relative">
                        <button
                          class="w-9 h-9 bg-white/95 dark:bg-gray-800/95 backdrop-blur-sm rounded-lg flex items-center justify-center shadow-sm hover:bg-white dark:hover:bg-gray-800 transition-all opacity-0 group-hover:opacity-100 border border-gray-200 dark:border-gray-600"
                          @click.stop="toggleDropdown(design.id)"
                        >
                          <svg class="w-4 h-4 text-gray-600 dark:text-gray-300" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M10 12a2 2 0 100-4 2 2 0 000 4z"/>
                            <path d="M10 4a2 2 0 100-4 2 2 0 000 4z"/>
                            <path d="M10 20a2 2 0 100-4 2 2 0 000 4z"/>
                          </svg>
                        </button>
                        
                        <!-- Professional Dropdown Menu -->
                        <div 
                          :class="[
                            'absolute right-0 top-full mt-2 w-52 bg-white dark:bg-gray-800 rounded-xl shadow-xl border border-gray-200 dark:border-gray-700 py-2 transition-all duration-200 z-10',
                            dropdownOpen === design.id ? 'opacity-100 visible' : 'opacity-0 invisible'
                          ]"
                        >
                          <button
                            @click.stop="editDesign(design); closeDropdown()"
                            class="w-full px-4 py-3 text-left text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700 flex items-center transition-colors"
                          >
                            <component :is="icons.edit" class="w-4 h-4 mr-3 text-gray-500 dark:text-gray-400" />
                            Edit Design
                          </button>
                          <button
                            @click.stop="duplicateDesign(design); closeDropdown()"
                            class="w-full px-4 py-3 text-left text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700 flex items-center transition-colors"
                          >
                            <component :is="icons.duplicate" class="w-4 h-4 mr-3 text-gray-500 dark:text-gray-400" />
                            Duplicate
                          </button>
                          <button
                            @click.stop="downloadDesign(design); closeDropdown()"
                            class="w-full px-4 py-3 text-left text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700 flex items-center transition-colors"
                          >
                            <component :is="icons.download" class="w-4 h-4 mr-3 text-gray-500 dark:text-gray-400" />
                            Export
                          </button>
                          <hr class="my-1 border-gray-100 dark:border-gray-700">
                          <button
                            @click.stop="deleteDesign(design); closeDropdown()"
                            class="w-full px-4 py-3 text-left text-sm text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 flex items-center transition-colors"
                          >
                            <component :is="icons.delete" class="w-4 h-4 mr-3" />
                            Delete
                          </button>
                        </div>
                      </div>
                    </div>
                  </div>
                  
                  <!-- Professional Design Info -->
                  <div class="p-4">
                    <h3 class="font-semibold text-gray-900 dark:text-white truncate text-base cursor-pointer hover:text-blue-600 dark:hover:text-primary-400 transition-colors mb-2" @click="editDesign(design)">
                      {{ design.title || design.name || 'Untitled Design' }}
                    </h3>
                    <div class="flex items-center justify-between">
                      <p class="text-sm text-gray-500 dark:text-gray-400">
                        {{ new Date(design.updatedAt || design.createdAt).toLocaleDateString() }}
                      </p>
                      <span class="inline-flex items-center px-2.5 py-1 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-xs font-medium rounded-md">
                        {{ design.width }}×{{ design.height }}
                      </span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            
            <!-- Professional Empty State -->
            <div 
              v-else
              class="text-center py-16 bg-gradient-to-br from-gray-50 to-gray-100 dark:from-gray-800 dark:to-gray-900 rounded-xl"
            >
              <div class="w-16 h-16 bg-gray-200 dark:bg-gray-700 rounded-xl flex items-center justify-center mx-auto mb-6">
                <component :is="icons.design" class="w-8 h-8 text-gray-500 dark:text-gray-400" />
              </div>
              <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-3">No projects yet</h3>
              <p class="text-gray-600 dark:text-gray-300 mb-8 max-w-md mx-auto">Start your creative journey by creating your first design project.</p>
              <router-link
                to="/editor"
                class="inline-flex items-center px-6 py-3 bg-gray-900 dark:bg-primary-600 text-white rounded-lg font-semibold hover:bg-gray-800 dark:hover:bg-primary-700 transition-colors shadow-lg"
              >
                <component :is="icons.plus" class="w-5 h-5 mr-2" />
                Create First Project
              </router-link>
            </div>
          </div>
        </div>
      </div>

      <!-- Enhanced Featured Templates Section -->
      <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-200 dark:border-gray-700">
        <div class="px-8 py-6 border-b border-gray-100 dark:border-gray-700">
          <div class="flex items-center justify-between">
            <div>
              <h2 class="text-3xl font-bold text-gray-900 dark:text-white">Featured Templates</h2>
              <p class="text-gray-600 dark:text-gray-300 mt-2 text-lg">Professional designs to accelerate your workflow</p>
            </div>
            <router-link
              to="/templates"
              class="inline-flex items-center px-6 py-3 bg-blue-600 hover:bg-blue-700 dark:bg-blue-700 dark:hover:bg-blue-800 text-white rounded-xl font-semibold transition-all duration-200 transform hover:scale-105 shadow-lg text-sm"
            >
              Browse all
              <ArrowRightIcon class="w-4 h-4 ml-2" />
            </router-link>
          </div>
        </div>
        
        <div class="p-8">
          <div class="relative">
            <div class="flex overflow-x-auto scrollbar-thin scrollbar-thumb-gray-300 dark:scrollbar-thumb-gray-600 scrollbar-track-gray-100 dark:scrollbar-track-gray-800 pb-4 gap-6">
              <div
                v-for="template in featuredTemplates"
                :key="template.id"
                class="flex-shrink-0 w-72 cursor-pointer group"
                @click="useTemplate(template)"
              >
                <!-- Professional Template Card -->
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600 hover:shadow-lg transition-all duration-300 overflow-hidden">
                  <!-- Template Thumbnail -->
                  <div 
                    class="relative bg-gray-50 dark:bg-gray-700 overflow-hidden"
                    :style="getTemplateCardStyle(template)"
                  >
                    <img
                      v-if="template.thumbnail || template.thumbnailUrl"
                      :src="template.thumbnail || template.thumbnailUrl"
                      :alt="template.title || template.name"
                      class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                      loading="lazy"
                    />
                    <div v-else class="w-full h-full flex items-center justify-center bg-gradient-to-br from-gray-50 to-gray-100 dark:from-gray-700 dark:to-gray-800">
                      <component :is="icons.template" class="w-10 h-10 text-gray-300 dark:text-gray-500" />
                    </div>
                    
                    <!-- Professional Hover Overlay -->
                    <div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-20 transition-all duration-300 flex items-center justify-center">
                      <div class="opacity-0 group-hover:opacity-100 transition-all duration-300 transform translate-y-2 group-hover:translate-y-0">
                        <div class="bg-white dark:bg-gray-800 text-gray-900 dark:text-white px-4 py-2 rounded-lg font-semibold text-sm shadow-xl border border-gray-200 dark:border-gray-700">
                          Use Template
                        </div>
                      </div>
                    </div>
                  </div>
                  
                  <!-- Professional Template Info -->
                  <div class="p-4">
                    <h3 class="font-semibold text-gray-900 dark:text-white text-base leading-tight mb-2 truncate">
                      {{ template.title || template.name }}
                    </h3>
                    <div class="flex items-center justify-between">
                      <span class="text-sm text-gray-500 dark:text-gray-400 capitalize bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded-md">{{ template.category || 'General' }}</span>
                      
                      <!-- Usage count or rating -->
                      <div v-if="template.usageCount > 0" class="flex items-center text-sm text-gray-500 dark:text-gray-400">
                        <component :is="icons.users" class="w-4 h-4 mr-1" />
                        {{ formatUsageCount(template.usageCount) }}
                      </div>
                      <div v-else-if="template.rating >= 4.0" class="flex items-center text-sm text-gray-500 dark:text-gray-400">
                        <component :is="icons.star" class="w-4 h-4 mr-1 text-yellow-400" />
                        {{ template.rating.toFixed(1) }}
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              
              <!-- Professional Loading Cards -->
              <div 
                v-if="templatesLoading"
                v-for="i in 4"
                :key="`template-loading-${i}`"
                class="flex-shrink-0 w-72 animate-pulse"
              >
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                  <div class="aspect-[3/4] bg-gray-200 dark:bg-gray-700"></div>
                  <div class="p-4 space-y-3">
                    <div class="h-5 bg-gray-200 dark:bg-gray-700 rounded w-3/4"></div>
                    <div class="flex justify-between items-center">
                      <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-1/2"></div>
                      <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-1/4"></div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
          
          <!-- Professional Empty State -->
          <div 
            v-if="!templatesLoading && featuredTemplates.length === 0"
            class="text-center py-16 bg-gradient-to-br from-gray-50 to-gray-100 dark:from-gray-800 dark:to-gray-900 rounded-xl"
          >
            <div class="w-16 h-16 bg-gray-200 dark:bg-gray-700 rounded-xl flex items-center justify-center mx-auto mb-6">
              <component :is="icons.template" class="w-8 h-8 text-gray-500 dark:text-gray-400" />
            </div>
            <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-3">No templates available</h3>
            <p class="text-gray-600 dark:text-gray-300 mb-8 max-w-md mx-auto">Professional templates will appear here to help jumpstart your projects.</p>
            <router-link
              to="/templates"
              class="inline-flex items-center px-6 py-3 bg-gray-900 dark:bg-primary-600 text-white rounded-lg font-semibold hover:bg-gray-800 dark:hover:bg-primary-700 transition-colors shadow-lg"
            >
              Browse Templates
              <component :is="icons.arrowRight" class="w-5 h-5 ml-2" />
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
/* Professional background pattern */
.professional-pattern {
  background-image: radial-gradient(circle at 1px 1px, rgba(255,255,255,0.15) 1px, transparent 0);
  background-size: 20px 20px;
}

/* Enhanced floating animations */
@keyframes float {
  0%, 100% {
    transform: translateY(0px);
  }
  50% {
    transform: translateY(-6px);
  }
}

@keyframes glow {
  0%, 100% {
    opacity: 1;
  }
  50% {
    opacity: 0.5;
  }
}

@keyframes float-delayed {
  0%, 100% {
    transform: translateY(0px);
  }
  50% {
    transform: translateY(-15px);
  }
}

@keyframes pulse-slow {
  0%, 100% {
    opacity: 0.1;
  }
  50% {
    opacity: 0.3;
  }
}

@keyframes gradient {
  0%, 100% {
    background-size: 200% 200%;
    background-position: left center;
  }
  50% {
    background-size: 200% 200%;
    background-position: right center;
  }
}

.animate-float {
  animation: float 6s ease-in-out infinite;
}

.animate-float-delayed {
  animation: float-delayed 8s ease-in-out infinite;
  animation-delay: 2s;
}

.animate-pulse-slow {
  animation: pulse-slow 4s ease-in-out infinite;
}

.animate-gradient {
  background-size: 200% 200%;
  animation: gradient 3s ease infinite;
}

/* Enhanced scrollbar styles for professional look */
.scrollbar-thin {
  scrollbar-width: thin;
}

.scrollbar-thin::-webkit-scrollbar {
  height: 8px;
}

.scrollbar-thin::-webkit-scrollbar-track {
  background: #f8fafc;
  border-radius: 4px;
}

.scrollbar-thin::-webkit-scrollbar-thumb {
  background: #cbd5e1;
  border-radius: 4px;
  border: 1px solid #f1f5f9;
}

.scrollbar-thin::-webkit-scrollbar-thumb:hover {
  background: #94a3b8;
}

/* Dark mode scrollbar styling */
.dark .scrollbar-thin::-webkit-scrollbar-track {
  background: #1f2937;
}

.dark .scrollbar-thin::-webkit-scrollbar-thumb {
  background: #4b5563;
  border: 1px solid #374151;
}

.dark .scrollbar-thin::-webkit-scrollbar-thumb:hover {
  background: #6b7280;
}

/* Firefox scrollbar styling */
.scrollbar-thin {
  scrollbar-color: #cbd5e1 #f8fafc;
  scrollbar-width: thin;
}

.dark .scrollbar-thin {
  scrollbar-color: #4b5563 #1f2937;
}

/* Professional hover animations */
.hover-lift {
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.hover-lift:hover {
  transform: translateY(-2px);
  box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
}

/* Professional focus states */
.focus-ring:focus {
  outline: none;
  box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.5);
  border-radius: 0.375rem;
}

/* Subtle animations for professional feel */
@keyframes fadeInUp {
  from {
    opacity: 0;
    transform: translateY(20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.animate-fade-in-up {
  animation: fadeInUp 0.6s ease-out;
}

/* Professional button hover effects */
.btn-professional {
  transition: all 0.2s ease;
  transform: scale(1);
}

.btn-professional:hover {
  transform: scale(1.05);
}

.btn-professional:active {
  transform: scale(0.95);
}

/* Enhanced stats card animations */
.stats-card {
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.stats-card:hover {
  transform: translateY(-4px) scale(1.02);
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
}

/* Glowing effect for interactive elements */
.glow-on-hover {
  position: relative;
  overflow: hidden;
}

.glow-on-hover::before {
  content: '';
  position: absolute;
  top: 0;
  left: -100%;
  width: 100%;
  height: 100%;
  background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.1), transparent);
  transition: left 0.5s;
}

.glow-on-hover:hover::before {
  left: 100%;
}
</style>

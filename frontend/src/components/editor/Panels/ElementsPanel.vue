<template>
  <div class="h-full flex flex-col bg-gray-50 dark:bg-gray-900">
    <!-- Search Bar -->
    <div class="p-4 border-b border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800">
      <div class="relative">
        <MagnifyingGlassIcon class="absolute left-3 top-1/2 transform -translate-y-1/2 w-4 h-4 text-gray-400 dark:text-gray-500" />
        <input
          v-model="searchQuery"
          type="text"
          placeholder="Search elements..."
          class="w-full pl-10 pr-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-all duration-200 shadow-sm hover:shadow-md"
        />
      </div>
    </div>

    <!-- Category Navigation -->
    <div class="px-4 py-4 border-b border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800">
      <div class="relative flex items-center">
        <!-- Left Arrow -->
        <button
          @click="scrollCategories('left')"
          :disabled="!canScrollLeft"
          :class="[
            'absolute left-0 z-10 w-9 h-9 rounded-full flex items-center justify-center transition-all duration-200 border',
            canScrollLeft 
              ? 'bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600 shadow-md border-gray-200 dark:border-gray-600 hover:shadow-lg' 
              : 'bg-gray-100 dark:bg-gray-800 text-gray-300 dark:text-gray-600 cursor-not-allowed border-gray-200 dark:border-gray-700'
          ]"
        >
          <ChevronLeftIcon class="w-4 h-4" />
        </button>

        <!-- Scrollable Categories Container -->
        <div 
          ref="categoriesContainer"
          class="flex overflow-x-hidden scroll-smooth mx-11"
          @scroll="updateScrollState"
        >
          <div class="flex space-x-2 px-2 py-1">
            <button
              v-for="category in categories"
              :key="category.id"
              @click="activeCategory = category.id"
              :class="[
                'flex items-center space-x-2 px-4 py-2.5 rounded-xl text-sm font-medium whitespace-nowrap transition-all duration-200 flex-shrink-0 border',
                activeCategory === category.id
                  ? 'bg-primary-500 hover:bg-primary-600 text-white border-primary-500 shadow-md hover:shadow-lg transform hover:scale-105'
                  : 'bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600 border-gray-200 dark:border-gray-600 shadow-sm hover:shadow-md hover:border-gray-300 dark:hover:border-gray-500'
              ]"
            >
              <component :is="category.icon" class="w-4 h-4" />
              <span>{{ category.label }}</span>
            </button>
          </div>
        </div>

        <!-- Right Arrow -->
        <button
          @click="scrollCategories('right')"
          :disabled="!canScrollRight"
          :class="[
            'absolute right-0 z-10 w-9 h-9 rounded-full flex items-center justify-center transition-all duration-200 border',
            canScrollRight 
              ? 'bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600 shadow-md border-gray-200 dark:border-gray-600 hover:shadow-lg' 
              : 'bg-gray-100 dark:bg-gray-800 text-gray-300 dark:text-gray-600 cursor-not-allowed border-gray-200 dark:border-gray-700'
          ]"
        >
          <ChevronRightIcon class="w-4 h-4" />
        </button>
      </div>
    </div>

    <!-- Content Area with Native Scrollbar -->
    <div class="flex-1 overflow-y-auto bg-gray-50 dark:bg-gray-900">
      <div class="p-4 space-y-8">
        <!-- Quick Actions (All Categories) -->
        <div v-if="activeCategory === 'all'">
          <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-3 flex items-center">
            <SparklesIcon class="w-4 h-4 mr-2 text-primary-500" />
            Quick Add
          </h3>
          <div class="grid grid-cols-2 gap-3 mb-6">
            <button
              @click="addTextElement"
              class="group flex flex-col items-center p-4 border-2 border-dashed border-primary-200 dark:border-primary-700 rounded-xl hover:border-primary-400 dark:hover:border-primary-500 hover:bg-primary-50 dark:hover:bg-gray-800 transition-all duration-200"
            >
              <div class="w-12 h-12 bg-gradient-to-br from-primary-500 to-primary-600 rounded-xl flex items-center justify-center mb-3 group-hover:scale-110 transition-transform duration-200">
                <span class="text-white font-bold text-lg">T</span>
              </div>
              <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Add Text</span>
            </button>
            
            <button
              @click="addImagePlaceholder"
              :disabled="isUploading"
              class="group flex flex-col items-center p-4 border-2 border-dashed border-primary-200 dark:border-primary-700 rounded-xl hover:border-primary-400 dark:hover:border-primary-500 hover:bg-primary-50 dark:hover:bg-gray-800 transition-all duration-200 disabled:opacity-50"
            >
              <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl flex items-center justify-center mb-3 group-hover:scale-110 transition-transform duration-200">
                <PhotoIcon v-if="!isUploading" class="w-6 h-6 text-white" />
                <div v-else class="w-5 h-5 border-2 border-white border-t-transparent rounded-full animate-spin"></div>
              </div>
              <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                {{ isUploading ? 'Uploading...' : 'Upload' }}
              </span>
            </button>
          </div>

          <!-- Trending Elements -->
          <div class="mb-6">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-3 flex items-center">
              <FireIcon class="w-4 h-4 mr-2 text-orange-500" />
              Trending
            </h3>
            <HorizontalSlider
              :items="trendingElements"
              :min-items-for-arrows="3"
              :show-view-all="false"
              gap="md"
            >
              <template #item="{ item }">
                <div
                  @click="addTrendingElement(item)"
                  class="group relative flex-shrink-0 w-24 h-24 bg-gradient-to-br from-gray-100 to-gray-200 dark:from-gray-700 dark:to-gray-800 rounded-lg cursor-pointer hover:scale-105 transition-all duration-200 overflow-hidden"
                >
                  <div class="absolute inset-0 flex items-center justify-center">
                    <div v-html="item.preview" class="text-2xl"></div>
                  </div>
                  <div class="absolute inset-0 bg-black/0 group-hover:bg-black/10 transition-colors duration-200"></div>
                  <div class="absolute bottom-1 right-1 w-6 h-6 bg-primary-500 rounded-full flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity duration-200">
                    <PlusIcon class="w-3 h-3 text-white" />
                  </div>
                </div>
              </template>
            </HorizontalSlider>
          </div>
        </div>

        <!-- Text Elements -->
        <div v-if="activeCategory === 'all' || activeCategory === 'text'">
          <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-3 flex items-center">
            <DocumentTextIcon class="w-4 h-4 mr-2 text-primary-500" />
            Text Styles
          </h3>
          <div class="grid grid-cols-1 gap-3">
            <div
              v-for="textStyle in filteredTextStyles"
              :key="textStyle.id"
              @click="addElement('text', textStyle.properties)"
              class="group p-4 border border-gray-200 dark:border-gray-600 rounded-lg hover:border-primary-300 dark:hover:border-primary-500 hover:bg-primary-50 dark:hover:bg-gray-800 cursor-pointer transition-all duration-200"
            >
              <div class="flex items-center justify-between">
                <div class="flex-1">
                  <div 
                    :style="{ 
                      fontSize: Math.min(textStyle.properties.fontSize / 2, 18) + 'px',
                      fontFamily: textStyle.properties.fontFamily,
                      fontWeight: textStyle.properties.fontWeight,
                      color: textStyle.properties.color
                    }"
                    class="mb-1"
                  >
                    {{ textStyle.preview }}
                  </div>
                  <span class="text-xs text-gray-500 dark:text-gray-400">{{ textStyle.label }}</span>
                </div>
                <PlusIcon class="w-4 h-4 text-gray-400 group-hover:text-primary-500 transition-colors" />
              </div>
            </div>
          </div>
        </div>

        <!-- Shapes -->
        <div v-if="activeCategory === 'all' || activeCategory === 'shapes'">
          <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-3 flex items-center">
            <Square3Stack3DIcon class="w-4 h-4 mr-2 text-primary-500" />
            Shapes
          </h3>
          
          <!-- All Category: Horizontal Scroll View for Basic Shapes -->
          <div v-if="activeCategory === 'all'">
            <HorizontalSlider
              :items="limitedBasicShapes"
              :total-items="filteredBasicShapes.length"
              :min-items-for-arrows="4"
              view-all-text="View All Shapes"
              @view-all="activeCategory = 'shapes'"
            >
              <template #item="{ item: shape }">
                <div
                  @click="addShape(shape.type, shape.properties)"
                  class="group relative flex-shrink-0 w-20 h-20 flex flex-col items-center justify-center p-2 border border-gray-200 dark:border-gray-600 rounded-lg hover:border-primary-300 dark:hover:border-primary-500 hover:bg-primary-50 dark:hover:bg-gray-800 cursor-pointer transition-all duration-200"
                >
                  <div class="flex-1 flex items-center justify-center mb-1">
                    <div v-html="shape.icon" class="text-lg text-gray-600 dark:text-gray-400 group-hover:text-primary-500 transition-colors"></div>
                  </div>
                  <span class="text-xs font-medium text-gray-700 dark:text-gray-300 text-center leading-tight">{{ shape.label }}</span>
                </div>
              </template>
            </HorizontalSlider>
          </div>
          
          <!-- Shapes Category: Full View with Sections -->
          <div v-else>
            <!-- Basic Shapes -->
            <div class="mb-4">
              <h4 class="text-xs font-medium text-gray-600 dark:text-gray-400 mb-3 uppercase tracking-wide">Basic</h4>
              <div class="grid grid-cols-4 gap-3">
                <div
                  v-for="shape in filteredBasicShapes"
                  :key="shape.type"
                  @click="addShape(shape.type, shape.properties)"
                  class="group relative aspect-square flex flex-col items-center justify-center p-3 border border-gray-200 dark:border-gray-600 rounded-lg hover:border-primary-300 dark:hover:border-primary-500 hover:bg-primary-50 dark:hover:bg-gray-800 cursor-pointer transition-all duration-200"
                >
                  <div class="flex-1 flex items-center justify-center mb-2">
                    <div v-html="shape.icon" class="text-2xl text-gray-600 dark:text-gray-400 group-hover:text-primary-500 transition-colors"></div>
                  </div>
                  <span class="text-xs font-medium text-gray-700 dark:text-gray-300 text-center leading-tight">{{ shape.label }}</span>
                </div>
              </div>
            </div>

            <!-- Advanced Shapes -->
            <div>
              <h4 class="text-xs font-medium text-gray-600 dark:text-gray-400 mb-3 uppercase tracking-wide">Advanced</h4>
              <div class="grid grid-cols-3 gap-3">
                <div
                  v-for="shape in filteredAdvancedShapes"
                  :key="shape.type"
                  @click="addShape(shape.type, shape.properties)"
                  class="group relative aspect-square flex flex-col items-center justify-center p-3 border border-gray-200 dark:border-gray-600 rounded-lg hover:border-primary-300 dark:hover:border-primary-500 hover:bg-primary-50 dark:hover:bg-gray-800 cursor-pointer transition-all duration-200"
                >
                  <div class="flex-1 flex items-center justify-center mb-2">
                    <div v-html="shape.icon" class="text-xl text-gray-600 dark:text-gray-400 group-hover:text-primary-500 transition-colors"></div>
                  </div>
                  <span class="text-xs font-medium text-gray-700 dark:text-gray-300 text-center leading-tight">{{ shape.label }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Icons -->
        <div v-if="activeCategory === 'all' || activeCategory === 'icons'">
          <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-3 flex items-center">
            <StarIcon class="w-4 h-4 mr-2 text-primary-500" />
            Icons & Graphics
          </h3>
          
          <!-- All Category: Horizontal Scroll View for Icons -->
          <div v-if="activeCategory === 'all'">
            <HorizontalSlider
              :items="limitedIcons"
              :total-items="filteredIcons.length"
              :min-items-for-arrows="4"
              view-all-text="View All Icons"
              @view-all="activeCategory = 'icons'"
            >
              <template #item="{ item: icon }">
                <div
                  @click="addElement('svg', icon.properties)"
                  class="group relative flex-shrink-0 w-20 h-20 bg-gray-50 dark:bg-gray-800 rounded-lg cursor-pointer hover:bg-primary-50 dark:hover:bg-gray-700 transition-all duration-200 overflow-hidden border border-gray-200 dark:border-gray-600 hover:border-primary-300 dark:hover:border-primary-500"
                >
                  <div class="absolute inset-0 flex items-center justify-center p-3">
                    <div v-html="icon.svg" class="w-full h-full text-gray-600 dark:text-gray-400 group-hover:text-primary-500 transition-colors"></div>
                  </div>
                  <div class="absolute bottom-1 right-1 w-4 h-4 bg-primary-500 rounded-full flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity duration-200">
                    <PlusIcon class="w-2 h-2 text-white" />
                  </div>
                </div>
              </template>
            </HorizontalSlider>
          </div>
          
          <!-- Icons Category: Full Grid View -->
          <div v-else>
            <div class="grid grid-cols-4 gap-3">
              <div
                v-for="icon in filteredIcons"
                :key="icon.id"
                @click="addElement('svg', icon.properties)"
                class="group relative aspect-square bg-gray-50 dark:bg-gray-800 rounded-lg cursor-pointer hover:bg-primary-50 dark:hover:bg-gray-700 transition-all duration-200 overflow-hidden border border-gray-200 dark:border-gray-600 hover:border-primary-300 dark:hover:border-primary-500"
              >
                <div class="absolute inset-0 flex items-center justify-center p-3">
                  <div v-html="icon.svg" class="w-full h-full text-gray-600 dark:text-gray-400 group-hover:text-primary-500 transition-colors"></div>
                </div>
                <div class="absolute bottom-1 right-1 w-5 h-5 bg-primary-500 rounded-full flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity duration-200">
                  <PlusIcon class="w-2.5 h-2.5 text-white" />
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Lines & Arrows -->
        <div v-if="activeCategory === 'all' || activeCategory === 'lines'">
          <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-3 flex items-center">
            <ArrowRightIcon class="w-4 h-4 mr-2 text-primary-500" />
            Lines & Arrows
          </h3>
          <div class="grid grid-cols-2 gap-3">
            <div
              v-for="line in filteredLines"
              :key="line.id"
              @click="addElement('shape', line.properties)"
              class="group relative h-16 bg-gray-50 dark:bg-gray-800 rounded-lg cursor-pointer hover:bg-primary-50 dark:hover:bg-gray-700 transition-all duration-200 border border-gray-200 dark:border-gray-600 hover:border-primary-300 dark:hover:border-primary-500"
            >
              <div class="absolute inset-0 flex items-center justify-center p-4">
                <div v-html="line.preview" class="text-gray-600 dark:text-gray-400 group-hover:text-primary-500 transition-colors"></div>
              </div>
              <div class="absolute bottom-1 right-1 w-5 h-5 bg-primary-500 rounded-full flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity duration-200">
                <PlusIcon class="w-2.5 h-2.5 text-white" />
              </div>
            </div>
          </div>
        </div>

        <!-- Frames & Containers -->
        <div v-if="activeCategory === 'all' || activeCategory === 'frames'">
          <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-3 flex items-center">
            <RectangleStackIcon class="w-4 h-4 mr-2 text-primary-500" />
            Frames & Containers
          </h3>
          <div class="grid grid-cols-3 gap-3">
            <div
              v-for="frame in filteredFrames"
              :key="frame.id"
              @click="addElement('shape', frame.properties)"
              class="group relative aspect-[4/3] bg-gray-50 dark:bg-gray-800 rounded-lg cursor-pointer hover:bg-primary-50 dark:hover:bg-gray-700 transition-all duration-200 border border-gray-200 dark:border-gray-600 hover:border-primary-300 dark:hover:border-primary-500"
            >
              <div class="absolute inset-0 flex items-center justify-center p-3">
                <div v-html="frame.preview" class="w-full h-full text-gray-400 dark:text-gray-500 group-hover:text-primary-400 transition-colors"></div>
              </div>
              <div class="absolute bottom-1 right-1 w-5 h-5 bg-primary-500 rounded-full flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity duration-200">
                <PlusIcon class="w-2.5 h-2.5 text-white" />
              </div>
              <div class="absolute bottom-0 left-0 right-0 bg-black/80 text-white text-xs p-1 text-center opacity-0 group-hover:opacity-100 transition-opacity duration-200 rounded-b-lg">
                {{ frame.label }}
              </div>
            </div>
          </div>
        </div>

        <!-- Charts -->
        <div v-if="activeCategory === 'all' || activeCategory === 'charts'">
          <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-3 flex items-center">
            <ChartBarIcon class="w-4 h-4 mr-2 text-primary-500" />
            Charts & Data
          </h3>
          <div class="grid grid-cols-2 gap-3">
            <div
              v-for="chart in filteredCharts"
              :key="chart.id"
              @click="addChart(chart)"
              class="group relative aspect-[4/3] bg-white dark:bg-gray-800 rounded-lg cursor-pointer hover:bg-primary-50 dark:hover:bg-gray-700 transition-all duration-200 border border-gray-200 dark:border-gray-600 hover:border-primary-300 dark:hover:border-primary-500 shadow-sm hover:shadow-md"
            >
              <div class="absolute inset-0 flex items-center justify-center p-4">
                <div v-html="chart.preview" class="w-full h-full text-gray-600 dark:text-gray-400 group-hover:text-primary-500 transition-colors"></div>
              </div>
              <div class="absolute bottom-1 right-1 w-6 h-6 bg-primary-500 rounded-full flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity duration-200">
                <PlusIcon class="w-3 h-3 text-white" />
              </div>
              <div class="absolute bottom-0 left-0 right-0 bg-black/80 text-white text-xs p-2 text-center opacity-0 group-hover:opacity-100 transition-opacity duration-200 rounded-b-lg">
                {{ chart.label }}
              </div>
            </div>
          </div>
        </div>

        <!-- Photos -->
        <div v-if="activeCategory === 'all' || activeCategory === 'photos'">
          <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-3 flex items-center">
            <PhotoIcon class="w-4 h-4 mr-2 text-primary-500" />
            Photos & Images
            <div v-if="isLoadingPhotos || isLoadingUserMedia" class="ml-2 w-4 h-4 border-2 border-primary-500 border-t-transparent rounded-full animate-spin"></div>
          </h3>
          
          <!-- All Category: Horizontal Scroll View -->
          <div v-if="activeCategory === 'all'" class="space-y-4">
            <!-- Limited Photos Horizontal Scroll -->
            <HorizontalSlider
              :items="limitedPhotos"
              :total-items="combinedPhotos.length"
              :min-items-for-arrows="3"
              view-all-text="View All Photos"
              @view-all="viewAllPhotos"
            >
              <template #item="{ item: photo }">
                <div
                  @click="addPhoto(photo)"
                  class="group relative flex-shrink-0 w-24 h-24 bg-gray-50 dark:bg-gray-800 rounded-lg cursor-pointer hover:bg-primary-50 dark:hover:bg-gray-700 transition-all duration-200 border border-gray-200 dark:border-gray-600 hover:border-primary-300 dark:hover:border-primary-500 overflow-hidden photo-preview"
                >
                  <div class="absolute inset-0 flex items-center justify-center">
                    <div v-html="photo.preview" class="w-full h-full"></div>
                  </div>
                  <div class="absolute bottom-1 right-1 w-4 h-4 bg-primary-500 rounded-full flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity duration-200">
                    <PlusIcon class="w-2 h-2 text-white" />
                  </div>
                  <!-- Category indicator -->
                  <div class="absolute top-1 left-1 w-2 h-2 rounded-full" :class="photo.category === 'user' ? 'bg-blue-500' : 'bg-purple-500'"></div>
                </div>
              </template>
            </HorizontalSlider>
            
            <!-- Empty State for All mode -->
            <div v-if="limitedPhotos.length === 0 && !isLoadingPhotos && !isLoadingUserMedia" class="text-center py-6">
              <PhotoIcon class="w-8 h-8 mx-auto text-gray-400 dark:text-gray-500 mb-2" />
              <p class="text-xs text-gray-500 dark:text-gray-400">No photos available</p>
            </div>
          </div>
          
          <!-- Photos Category: Full View with Sections -->
          <div v-else>
            <!-- User Photos Section -->
            <div v-if="userPhotos.length > 0" class="mb-6">
              <h4 class="text-xs font-medium text-gray-600 dark:text-gray-400 mb-3 uppercase tracking-wide flex items-center">
                <span class="w-2 h-2 bg-blue-500 rounded-full mr-2"></span>
                Your Photos ({{ userPhotos.length }})
              </h4>
              <div class="grid grid-cols-2 gap-3">
                <div
                  v-for="photo in userPhotos"
                  :key="photo.id"
                  @click="addPhoto(photo)"
                  class="group relative aspect-[4/3] bg-gray-50 dark:bg-gray-800 rounded-lg cursor-pointer hover:bg-primary-50 dark:hover:bg-gray-700 transition-all duration-200 border border-gray-200 dark:border-gray-600 hover:border-blue-300 dark:hover:border-blue-500 overflow-hidden"
                >
                  <div class="absolute inset-0 flex items-center justify-center p-1">
                    <div v-html="photo.preview" class="w-full h-full"></div>
                  </div>
                  <div class="absolute bottom-1 right-1 w-5 h-5 bg-blue-500 rounded-full flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity duration-200">
                    <PlusIcon class="w-2.5 h-2.5 text-white" />
                  </div>
                  <div class="absolute bottom-0 left-0 right-0 bg-black/80 text-white text-xs p-1 text-center opacity-0 group-hover:opacity-100 transition-opacity duration-200 rounded-b-lg">
                    {{ photo.label }}
                  </div>
                </div>
              </div>
            </div>
            
            <!-- Stock Photos Section -->
            <div v-if="stockPhotosFiltered.length > 0">
              <h4 class="text-xs font-medium text-gray-600 dark:text-gray-400 mb-3 uppercase tracking-wide flex items-center">
                <span class="w-2 h-2 bg-purple-500 rounded-full mr-2"></span>
                Stock Photos ({{ stockPhotosFiltered.length }})
              </h4>
              <div class="grid grid-cols-2 gap-3">
                <div
                  v-for="photo in stockPhotosFiltered"
                  :key="photo.id"
                  @click="addPhoto(photo)"
                  class="group relative aspect-[4/3] bg-gray-50 dark:bg-gray-800 rounded-lg cursor-pointer hover:bg-primary-50 dark:hover:bg-gray-700 transition-all duration-200 border border-gray-200 dark:border-gray-600 hover:border-purple-300 dark:hover:border-purple-500 overflow-hidden"
                >
                  <div class="absolute inset-0 flex items-center justify-center p-1">
                    <div v-html="photo.preview" class="w-full h-full"></div>
                  </div>
                  <div class="absolute bottom-1 right-1 w-5 h-5 bg-purple-500 rounded-full flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity duration-200">
                    <PlusIcon class="w-2.5 h-2.5 text-white" />
                  </div>
                  <div class="absolute bottom-0 left-0 right-0 bg-black/80 text-white text-xs p-1 text-center opacity-0 group-hover:opacity-100 transition-opacity duration-200 rounded-b-lg">
                    {{ photo.label }}
                  </div>
                </div>
              </div>
            </div>
            
            <!-- Empty State for Photos mode -->
            <div v-if="!isLoadingPhotos && !isLoadingUserMedia && userPhotos.length === 0 && stockPhotosFiltered.length === 0" class="text-center py-8">
              <PhotoIcon class="w-12 h-12 mx-auto text-gray-400 dark:text-gray-500 mb-3" />
              <p class="text-sm text-gray-500 dark:text-gray-400 mb-2">No photos found</p>
              <p class="text-xs text-gray-400 dark:text-gray-500">Upload your first photo or try a different search term</p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Hidden file input for image uploads -->
    <input
      ref="fileInput"
      type="file"
      accept="image/*"
      @change="handleFileUpload"
      class="hidden"
    />
  </div>
</template>
<script setup lang="ts">
import { ref, computed, onMounted, nextTick, watch } from 'vue'
import type { LayerType, ChartData, ChartDataset, ScatterDataPoint, BubbleDataPoint } from '@/types'
import { mediaAPI } from '@/services/api'
import { useNotifications } from '@/composables/useNotifications'
import { useStockMedia } from '@/composables/useStockMedia'
import { useUserMedia } from '@/composables/useUserMedia'
import HorizontalSlider from '@/components/common/HorizontalSlider.vue'
import {
  MagnifyingGlassIcon,
  DocumentTextIcon,
  PhotoIcon,
  Square3Stack3DIcon,
  StarIcon,
  ArrowRightIcon,
  RectangleStackIcon,
  SparklesIcon,
  FireIcon,
  PlusIcon,
  ChevronLeftIcon,
  ChevronRightIcon,
  ChartBarIcon
} from '@heroicons/vue/24/outline'

interface ElementConfig {
  type: LayerType
  label: string
  icon: any
  defaultProps: any
}

interface ShapeConfig {
  type: string
  label: string
  icon: string
  properties?: any
}

interface TextStyle {
  id: string
  label: string
  preview: string
  properties: any
}

interface IconElement {
  id: string
  name: string
  svg: string
  properties: any
}

interface LineElement {
  id: string
  label: string
  preview: string
  properties: any
}

interface FrameElement {
  id: string
  label: string
  preview: string
  properties: any
}

interface TrendingElement {
  id: string
  type: LayerType
  preview: string
  properties: any
}

interface ChartElement {
  id: string
  label: string
  type: string
  preview: string
  properties: any
}

interface Category {
  id: string
  label: string
  icon: any
}

const emit = defineEmits<{
  'add-element': [type: LayerType, properties: any]
}>()

const { uploadFailed } = useNotifications()

// Media composables
const { 
  stockPhotos, 
  isLoadingPhotos, 
  searchPhotos 
} = useStockMedia()

const { 
  userMedia, 
  isLoadingUserMedia, 
  searchUserMedia 
} = useUserMedia()

// State
const fileInput = ref<HTMLInputElement>()
const isUploading = ref(false)
const searchQuery = ref('')
const activeCategory = ref('all')

// Category navigation state
const categoriesContainer = ref<HTMLElement>()
const canScrollLeft = ref(false)
const canScrollRight = ref(false)

// File validation constants
const MAX_FILE_SIZE = 10 * 1024 * 1024 // 10MB
const ALLOWED_MIME_TYPES = [
  'image/jpeg',
  'image/jpg', 
  'image/png',
  'image/gif',
  'image/webp',
  'image/svg+xml'
]

// Categories
const categories: Category[] = [
  { id: 'all', label: 'All', icon: SparklesIcon },
  { id: 'text', label: 'Text', icon: DocumentTextIcon },
  { id: 'photos', label: 'Photos', icon: PhotoIcon },
  { id: 'shapes', label: 'Shapes', icon: Square3Stack3DIcon },
  { id: 'icons', label: 'Icons', icon: StarIcon },
  { id: 'lines', label: 'Lines', icon: ArrowRightIcon },
  { id: 'frames', label: 'Frames', icon: RectangleStackIcon },
  { id: 'charts', label: 'Charts', icon: ChartBarIcon }
]

// Trending Elements
const trendingElements: TrendingElement[] = [
  {
    id: 'trending-gradient-circle',
    type: 'shape',
    preview: '🟡',
    properties: {
      shapeType: 'circle',
      fill: {
        type: 'linear',
        colors: [
          { color: '#ff6b6b', stop: 0 },
          { color: '#feca57', stop: 1 }
        ],
        angle: 45
      },
      stroke: 'transparent',
      width: 100,
      height: 100
    }
  },
  {
    id: 'trending-neon-text',
    type: 'text',
    preview: '✨',
    properties: {
      text: 'NEON',
      fontSize: 48,
      fontFamily: 'Inter',
      fontWeight: '900',
      color: '#00ffff',
      stroke: '#0099ff',
      strokeWidth: 2
    }
  },
  {
    id: 'trending-arrow-right',
    type: 'shape',
    preview: '➡️',
    properties: {
      shapeType: 'arrow',
      fill: { type: 'solid', color: '#667eea', opacity: 1 },
      stroke: 'transparent',
      width: 120,
      height: 40
    }
  },
  {
    id: 'trending-speech-bubble',
    type: 'shape',
    preview: '💬',
    properties: {
      shapeType: 'rounded-rectangle',
      fill: { type: 'solid', color: '#f093fb', opacity: 1 },
      stroke: '#e91e63',
      strokeWidth: 2,
      cornerRadius: 20,
      width: 120,
      height: 80
    }
  },
  {
    id: 'trending-star',
    type: 'shape',
    preview: '⭐',
    properties: {
      shapeType: 'star',
      fill: { type: 'solid', color: '#ffd700', opacity: 1 },
      stroke: '#ff8c00',
      strokeWidth: 2,
      width: 80,
      height: 80
    }
  },
  {
    id: 'trending-heart',
    type: 'shape',
    preview: '❤️',
    properties: {
      shapeType: 'heart',
      fill: { type: 'solid', color: '#ff4757', opacity: 1 },
      stroke: 'transparent',
      width: 80,
      height: 80
    }
  }
]

// Text Styles
const textStyles: TextStyle[] = [
  {
    id: 'heading-large',
    label: 'Large Heading',
    preview: 'Big Bold Title',
    properties: {
      text: 'Add a heading',
      fontSize: 72,
      fontFamily: 'Inter',
      fontWeight: '900',
      color: '#1f2937',
      textAlign: 'center'
    }
  },
  {
    id: 'heading-medium',
    label: 'Medium Heading',
    preview: 'Section Title',
    properties: {
      text: 'Add a subheading',
      fontSize: 48,
      fontFamily: 'Inter',
      fontWeight: '700',
      color: '#374151',
      textAlign: 'left'
    }
  },
  {
    id: 'heading-small',
    label: 'Small Heading',
    preview: 'Mini Header',
    properties: {
      text: 'Add a small heading',
      fontSize: 32,
      fontFamily: 'Inter',
      fontWeight: '600',
      color: '#4b5563',
      textAlign: 'left'
    }
  },
  {
    id: 'body-text',
    label: 'Body Text',
    preview: 'Regular paragraph text',
    properties: {
      text: 'Add some body text',
      fontSize: 16,
      fontFamily: 'Inter',
      fontWeight: '400',
      color: '#6b7280',
      textAlign: 'left'
    }
  },
  {
    id: 'quote-text',
    label: 'Quote',
    preview: '"Inspiring Quote"',
    properties: {
      text: '"Add your quote here"',
      fontSize: 24,
      fontFamily: 'Georgia',
      fontWeight: '400',
      color: '#8b5cf6',
      textAlign: 'center',
      fontStyle: 'italic'
    }
  },
  {
    id: 'button-text',
    label: 'Button Text',
    preview: 'CLICK ME',
    properties: {
      text: 'BUTTON',
      fontSize: 14,
      fontFamily: 'Inter',
      fontWeight: '700',
      color: '#ffffff',
      textAlign: 'center',
      textTransform: 'uppercase',
      letterSpacing: '1px'
    }
  }
]

// Basic Shapes
const basicShapes: ShapeConfig[] = [
  { 
    type: 'rectangle', 
    label: 'Rectangle', 
    icon: '⬜',
    properties: {
      fill: { type: 'solid', color: '#8b5cf6', opacity: 1 },
      stroke: 'transparent',
      cornerRadius: 0,
      width: 120,
      height: 80
    }
  },
  { 
    type: 'circle', 
    label: 'Circle', 
    icon: '⭕',
    properties: {
      fill: { type: 'solid', color: '#06d6a0', opacity: 1 },
      stroke: 'transparent',
      width: 100,
      height: 100
    }
  },
  { 
    type: 'triangle', 
    label: 'Triangle', 
    icon: '🔺',
    properties: {
      fill: { type: 'solid', color: '#ffd60a', opacity: 1 },
      stroke: 'transparent',
      width: 100,
      height: 100
    }
  },
  { 
    type: 'line', 
    label: 'Line', 
    icon: '📏',
    properties: {
      stroke: '#374151',
      strokeWidth: 3,
      width: 150,
      height: 3
    }
  }
]

// Advanced Shapes
const advancedShapes: ShapeConfig[] = [
  { 
    type: 'rounded-rectangle', 
    label: 'Rounded Rect', 
    icon: '▢',
    properties: {
      fill: { type: 'solid', color: '#f72585', opacity: 1 },
      stroke: 'transparent',
      cornerRadius: 20,
      width: 120,
      height: 80
    }
  },
  { 
    type: 'ellipse', 
    label: 'Ellipse', 
    icon: '⬭',
    properties: {
      fill: { type: 'solid', color: '#4361ee', opacity: 1 },
      stroke: 'transparent',
      width: 140,
      height: 80
    }
  },
  { 
    type: 'pentagon', 
    label: 'Pentagon', 
    icon: '⬟',
    properties: {
      fill: { type: 'solid', color: '#f77f00', opacity: 1 },
      stroke: 'transparent',
      width: 100,
      height: 100
    }
  },
  { 
    type: 'hexagon', 
    label: 'Hexagon', 
    icon: '⬢',
    properties: {
      fill: { type: 'solid', color: '#fcbf49', opacity: 1 },
      stroke: 'transparent',
      width: 100,
      height: 100
    }
  },
  { 
    type: 'star', 
    label: 'Star', 
    icon: '⭐',
    properties: {
      fill: { type: 'solid', color: '#ffd23f', opacity: 1 },
      stroke: '#ff8500',
      strokeWidth: 2,
      width: 100,
      height: 100
    }
  },
  { 
    type: 'heart', 
    label: 'Heart', 
    icon: '❤️',
    properties: {
      fill: { type: 'solid', color: '#e63946', opacity: 1 },
      stroke: 'transparent',
      width: 100,
      height: 100
    }
  }
]

// Icon Elements (SVG icons)
const iconElements: IconElement[] = [
  {
    id: 'icon-home',
    name: 'Home',
    svg: '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M10 20v-6h4v6h5v-8h3L12 3 2 12h3v8z"/></svg>',
    properties: {
      svg: '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M10 20v-6h4v6h5v-8h3L12 3 2 12h3v8z"/></svg>',
      width: 60,
      height: 60,
      fillColors: { global: '#6b7280' }
    }
  },
  {
    id: 'icon-heart',
    name: 'Heart',
    svg: '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/></svg>',
    properties: {
      svg: '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/></svg>',
      width: 60,
      height: 60,
      fillColors: { global: '#ef4444' }
    }
  },
  {
    id: 'icon-star',
    name: 'Star',
    svg: '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>',
    properties: {
      svg: '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>',
      width: 60,
      height: 60,
      fillColors: { global: '#fbbf24' }
    }
  },
  {
    id: 'icon-check',
    name: 'Check',
    svg: '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>',
    properties: {
      svg: '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>',
      width: 60,
      height: 60,
      fillColors: { global: '#10b981' }
    }
  },
  {
    id: 'icon-arrow-right',
    name: 'Arrow Right',
    svg: '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M8.59 16.59L13.17 12 8.59 7.41 10 6l6 6-6 6-1.41-1.41z"/></svg>',
    properties: {
      svg: '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M8.59 16.59L13.17 12 8.59 7.41 10 6l6 6-6 6-1.41-1.41z"/></svg>',
      width: 60,
      height: 60,
      fillColors: { global: '#8b5cf6' }
    }
  },
  {
    id: 'icon-mail',
    name: 'Mail',
    svg: '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/></svg>',
    properties: {
      svg: '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/></svg>',
      width: 60,
      height: 60,
      fillColors: { global: '#3b82f6' }
    }
  },
  {
    id: 'icon-phone',
    name: 'Phone',
    svg: '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M6.62 10.79c1.44 2.83 3.76 5.14 6.59 6.59l2.2-2.2c.27-.27.67-.36 1.02-.24 1.12.37 2.33.57 3.57.57.55 0 1 .45 1 1V20c0 .55-.45 1-1 1-9.39 0-17-7.61-17-17 0-.55.45-1 1-1h3.5c.55 0 1 .45 1 1 0 1.25.2 2.45.57 3.57.11.35.03.74-.25 1.02l-2.2 2.2z"/></svg>',
    properties: {
      svg: '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M6.62 10.79c1.44 2.83 3.76 5.14 6.59 6.59l2.2-2.2c.27-.27.67-.36 1.02-.24 1.12.37 2.33.57 3.57.57.55 0 1 .45 1 1V20c0 .55-.45 1-1 1-9.39 0-17-7.61-17-17 0-.55.45-1 1-1h3.5c.55 0 1 .45 1 1 0 1.25.2 2.45.57 3.57.11.35.03.74-.25 1.02l-2.2 2.2z"/></svg>',
      width: 60,
      height: 60,
      fillColors: { global: '#14b8a6' }
    }
  },
  {
    id: 'icon-location',
    name: 'Location',
    svg: '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/></svg>',
    properties: {
      svg: '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/></svg>',
      width: 60,
      height: 60,
      fillColors: { global: '#f59e0b' }
    }
  }
]

// Lines and Arrows
const lineElements: LineElement[] = [
  {
    id: 'straight-line',
    label: 'Straight Line',
    preview: '<svg viewBox="0 0 100 20" style="width: 100%; height: 100%;"><line x1="5" y1="10" x2="95" y2="10" stroke="currentColor" stroke-width="2"/></svg>',
    properties: {
      shapeType: 'line',
      stroke: '#374151',
      strokeWidth: 3,
      width: 150,
      height: 3
    }
  },
  {
    id: 'arrow-right',
    label: 'Arrow Right',
    preview: '<svg viewBox="0 0 100 20" style="width: 100%; height: 100%;"><defs><marker id="arrow" markerWidth="10" markerHeight="10" refX="9" refY="3" orient="auto" markerUnits="strokeWidth"><polygon points="0 0, 10 3, 0 6" fill="currentColor"/></marker></defs><line x1="5" y1="10" x2="85" y2="10" stroke="currentColor" stroke-width="2" marker-end="url(#arrow)"/></svg>',
    properties: {
      shapeType: 'arrow',
      direction: 'right',
      fill: { type: 'solid', color: '#6366f1', opacity: 1 },
      stroke: 'transparent',
      width: 120,
      height: 30
    }
  },
  {
    id: 'arrow-left',
    label: 'Arrow Left',
    preview: '<svg viewBox="0 0 100 20" style="width: 100%; height: 100%;"><defs><marker id="arrow-left" markerWidth="10" markerHeight="10" refX="1" refY="3" orient="auto" markerUnits="strokeWidth"><polygon points="10 0, 0 3, 10 6" fill="currentColor"/></marker></defs><line x1="15" y1="10" x2="95" y2="10" stroke="currentColor" stroke-width="2" marker-start="url(#arrow-left)"/></svg>',
    properties: {
      shapeType: 'arrow',
      direction: 'left',
      fill: { type: 'solid', color: '#06d6a0', opacity: 1 },
      stroke: 'transparent',
      width: 120,
      height: 30
    }
  },
  {
    id: 'double-arrow',
    label: 'Double Arrow',
    preview: '<svg viewBox="0 0 100 20" style="width: 100%; height: 100%;"><defs><marker id="arrow-start" markerWidth="10" markerHeight="10" refX="1" refY="3" orient="auto"><polygon points="10 0, 0 3, 10 6" fill="currentColor"/></marker><marker id="arrow-end" markerWidth="10" markerHeight="10" refX="9" refY="3" orient="auto"><polygon points="0 0, 10 3, 0 6" fill="currentColor"/></marker></defs><line x1="15" y1="10" x2="85" y2="10" stroke="currentColor" stroke-width="2" marker-start="url(#arrow-start)" marker-end="url(#arrow-end)"/></svg>',
    properties: {
      shapeType: 'arrow',
      direction: 'both',
      fill: { type: 'solid', color: '#f59e0b', opacity: 1 },
      stroke: 'transparent',
      width: 140,
      height: 30
    }
  }
]

// Frames and Containers
const frameElements: FrameElement[] = [
  {
    id: 'photo-frame',
    label: 'Photo Frame',
    preview: '<svg viewBox="0 0 100 80" style="width: 100%; height: 100%;"><rect x="5" y="5" width="90" height="70" fill="none" stroke="currentColor" stroke-width="8"/><rect x="15" y="15" width="70" height="50" fill="none" stroke="currentColor" stroke-width="1" stroke-dasharray="3,3"/></svg>',
    properties: {
      shapeType: 'rounded-rectangle',
      fill: { type: 'solid', color: 'transparent', opacity: 0 },
      stroke: '#8b5cf6',
      strokeWidth: 8,
      cornerRadius: 4,
      width: 200,
      height: 150
    }
  },
  {
    id: 'border-frame',
    label: 'Border Frame',
    preview: '<svg viewBox="0 0 100 80" style="width: 100%; height: 100%;"><rect x="5" y="5" width="90" height="70" fill="none" stroke="currentColor" stroke-width="3"/></svg>',
    properties: {
      shapeType: 'rectangle',
      fill: { type: 'solid', color: 'transparent', opacity: 0 },
      stroke: '#374151',
      strokeWidth: 3,
      width: 200,
      height: 150
    }
  },
  {
    id: 'rounded-frame',
    label: 'Rounded Frame',
    preview: '<svg viewBox="0 0 100 80" style="width: 100%; height: 100%;"><rect x="5" y="5" width="90" height="70" rx="15" ry="15" fill="none" stroke="currentColor" stroke-width="3"/></svg>',
    properties: {
      shapeType: 'rounded-rectangle',
      fill: { type: 'solid', color: 'transparent', opacity: 0 },
      stroke: '#ef4444',
      strokeWidth: 3,
      cornerRadius: 20,
      width: 200,
      height: 150
    }
  },
  {
    id: 'circle-frame',
    label: 'Circle Frame',
    preview: '<svg viewBox="0 0 100 100" style="width: 100%; height: 100%;"><circle cx="50" cy="50" r="40" fill="none" stroke="currentColor" stroke-width="6"/></svg>',
    properties: {
      shapeType: 'circle',
      fill: { type: 'solid', color: 'transparent', opacity: 0 },
      stroke: '#10b981',
      strokeWidth: 6,
      width: 150,
      height: 150
    }
  },
  {
    id: 'dashed-frame',
    label: 'Dashed Frame',
    preview: '<svg viewBox="0 0 100 80" style="width: 100%; height: 100%;"><rect x="5" y="5" width="90" height="70" fill="none" stroke="currentColor" stroke-width="2" stroke-dasharray="8,4"/></svg>',
    properties: {
      shapeType: 'rectangle',
      fill: { type: 'solid', color: 'transparent', opacity: 0 },
      stroke: '#f59e0b',
      strokeWidth: 2,
      strokeDasharray: '8,4',
      width: 200,
      height: 150
    }
  },
  {
    id: 'decorative-frame',
    label: 'Decorative Frame',
    preview: '<svg viewBox="0 0 100 80" style="width: 100%; height: 100%;"><rect x="8" y="8" width="84" height="64" fill="none" stroke="currentColor" stroke-width="1"/><rect x="5" y="5" width="90" height="70" fill="none" stroke="currentColor" stroke-width="2"/><circle cx="15" cy="15" r="3" fill="currentColor"/><circle cx="85" cy="15" r="3" fill="currentColor"/><circle cx="15" cy="65" r="3" fill="currentColor"/><circle cx="85" cy="65" r="3" fill="currentColor"/></svg>',
    properties: {
      shapeType: 'rounded-rectangle',
      fill: { type: 'solid', color: 'transparent', opacity: 0 },
      stroke: '#8b5cf6',
      strokeWidth: 3,
      cornerRadius: 8,
      width: 200,
      height: 150
    }
  }
]

// Chart Elements
const chartElements: ChartElement[] = [
  {
    id: 'bar-chart',
    label: 'Bar Chart',
    type: 'bar',
    preview: '<svg viewBox="0 0 100 80" style="width: 100%; height: 100%;"><rect x="10" y="50" width="12" height="25" fill="currentColor"/><rect x="26" y="35" width="12" height="40" fill="currentColor"/><rect x="42" y="20" width="12" height="55" fill="currentColor"/><rect x="58" y="40" width="12" height="35" fill="currentColor"/><rect x="74" y="30" width="12" height="45" fill="currentColor"/><line x1="5" y1="75" x2="95" y2="75" stroke="currentColor" stroke-width="1"/><line x1="5" y1="75" x2="5" y2="15" stroke="currentColor" stroke-width="1"/></svg>',
    properties: {
      chartType: 'bar',
      data: [
        { label: 'Q1', value: 25 },
        { label: 'Q2', value: 40 },
        { label: 'Q3', value: 55 },
        { label: 'Q4', value: 35 },
        { label: 'Q5', value: 45 }
      ],
      width: 300,
      height: 200,
      colors: ['#3b82f6', '#06d6a0', '#ffd60a', '#f72585', '#8b5cf6']
    }
  },
  {
    id: 'line-chart',
    label: 'Line Chart',
    type: 'line',
    preview: '<svg viewBox="0 0 100 80" style="width: 100%; height: 100%;"><polyline points="10,60 25,45 40,30 55,50 70,25 85,40" fill="none" stroke="currentColor" stroke-width="2"/><circle cx="10" cy="60" r="2" fill="currentColor"/><circle cx="25" cy="45" r="2" fill="currentColor"/><circle cx="40" cy="30" r="2" fill="currentColor"/><circle cx="55" cy="50" r="2" fill="currentColor"/><circle cx="70" cy="25" r="2" fill="currentColor"/><circle cx="85" cy="40" r="2" fill="currentColor"/><line x1="5" y1="75" x2="95" y2="75" stroke="currentColor" stroke-width="1"/><line x1="5" y1="75" x2="5" y2="15" stroke="currentColor" stroke-width="1"/></svg>',
    properties: {
      chartType: 'line',
      data: [
        { label: 'Jan', value: 30 },
        { label: 'Feb', value: 45 },
        { label: 'Mar', value: 60 },
        { label: 'Apr', value: 40 },
        { label: 'May', value: 65 },
        { label: 'Jun', value: 50 }
      ],
      width: 300,
      height: 200,
      strokeColor: '#3b82f6',
      strokeWidth: 3
    }
  },
  {
    id: 'pie-chart',
    label: 'Pie Chart',
    type: 'pie',
    preview: '<svg viewBox="0 0 100 80" style="width: 100%; height: 100%;"><g transform="translate(50,40)"><path d="M 0,-25 A 25,25 0 0,1 22.5,-9.58 L 0,0 Z" fill="#3b82f6"></path><path d="M 22.5,-9.58 A 25,25 0 0,1 9.58,22.5 L 0,0 Z" fill="#06d6a0"></path><path d="M 9.58,22.5 A 25,25 0 0,1 -24.27,-5.88 L 0,0 Z" fill="#ffd60a"></path><path d="M -24.27,-5.88 A 25,25 0 0,1 0,-25 L 0,0 Z" fill="#f72585"></path></g></svg>',
    properties: {
      chartType: 'pie',
      data: [
        { label: 'Desktop', value: 45, color: '#3b82f6' },
        { label: 'Mobile', value: 35, color: '#06d6a0' },
        { label: 'Tablet', value: 15, color: '#ffd60a' },
        { label: 'Other', value: 5, color: '#f72585' }
      ],
      width: 250,
      height: 250
    }
  },
  {
    id: 'donut-chart',
    label: 'Donut Chart',
    type: 'doughnut',
    preview: '<svg viewBox="0 0 100 80" style="width: 100%; height: 100%;"><circle cx="50" cy="40" r="25" fill="none" stroke="#e5e7eb" stroke-width="12"/><circle cx="50" cy="40" r="25" fill="none" stroke="currentColor" stroke-width="12" stroke-dasharray="47.12 157.08" stroke-dashoffset="0" transform="rotate(-90 50 40)"/><circle cx="50" cy="40" r="25" fill="none" stroke="#f72585" stroke-width="12" stroke-dasharray="31.42 157.08" stroke-dashoffset="-47.12" transform="rotate(-90 50 40)"/><circle cx="50" cy="40" r="25" fill="none" stroke="#8b5cf6" stroke-width="12" stroke-dasharray="23.56 157.08" stroke-dashoffset="-78.54" transform="rotate(-90 50 40)"/></svg>',
    properties: {
      chartType: 'doughnut',
      data: [
        { label: 'Sales', value: 50, color: '#3b82f6' },
        { label: 'Marketing', value: 30, color: '#f72585' },
        { label: 'Support', value: 20, color: '#8b5cf6' }
      ],
      width: 250,
      height: 250,
      innerRadius: 0.5
    }
  },
  {
    id: 'area-chart',
    label: 'Area Chart',
    type: 'area',
    preview: '<svg viewBox="0 0 100 80" style="width: 100%; height: 100%;"><defs><linearGradient id="area-gradient" x1="0%" y1="0%" x2="0%" y2="100%"><stop offset="0%" style="stop-color:currentColor;stop-opacity:0.3"/><stop offset="100%" style="stop-color:currentColor;stop-opacity:0.1"/></linearGradient></defs><polygon points="10,75 10,50 25,40 40,25 55,45 70,20 85,35 85,75" fill="url(#area-gradient)"/><polyline points="10,50 25,40 40,25 55,45 70,20 85,35" fill="none" stroke="currentColor" stroke-width="2"/><line x1="5" y1="75" x2="95" y2="75" stroke="currentColor" stroke-width="1"/><line x1="5" y1="75" x2="5" y2="15" stroke="currentColor" stroke-width="1"/></svg>',
    properties: {
      chartType: 'area',
      data: [
        { label: 'Week 1', value: 40 },
        { label: 'Week 2', value: 50 },
        { label: 'Week 3', value: 65 },
        { label: 'Week 4', value: 45 },
        { label: 'Week 5', value: 70 },
        { label: 'Week 6', value: 55 }
      ],
      width: 300,
      height: 200,
      fillColor: '#3b82f6',
      strokeColor: '#1e40af'
    }
  },
  {
    id: 'scatter-chart',
    label: 'Scatter Chart',
    type: 'scatter',
    preview: '<svg viewBox="0 0 100 80" style="width: 100%; height: 100%;"><circle cx="15" cy="45" r="3" fill="currentColor"/><circle cx="25" cy="35" r="3" fill="currentColor"/><circle cx="35" cy="55" r="3" fill="currentColor"/><circle cx="45" cy="25" r="3" fill="currentColor"/><circle cx="55" cy="40" r="3" fill="currentColor"/><circle cx="65" cy="30" r="3" fill="currentColor"/><circle cx="75" cy="50" r="3" fill="currentColor"/><circle cx="85" cy="35" r="3" fill="currentColor"/><line x1="5" y1="75" x2="95" y2="75" stroke="currentColor" stroke-width="1"/><line x1="5" y1="75" x2="5" y2="15" stroke="currentColor" stroke-width="1"/></svg>',
    properties: {
      chartType: 'scatter',
      data: [
        { x: 10, y: 20 },
        { x: 15, y: 35 },
        { x: 20, y: 25 },
        { x: 25, y: 45 },
        { x: 30, y: 30 },
        { x: 35, y: 40 },
        { x: 40, y: 50 },
        { x: 45, y: 35 },
        { x: 50, y: 55 },
        { x: 55, y: 42 }
      ] as ScatterDataPoint[],
      width: 400,
      height: 300,
      pointColor: '#3b82f6',
      pointRadius: 5
    }
  },
  {
    id: 'bubble-chart',
    label: 'Bubble Chart',
    type: 'bubble',
    preview: '<svg viewBox="0 0 100 80" style="width: 100%; height: 100%;"><circle cx="20" cy="40" r="6" fill="currentColor" opacity="0.7"/><circle cx="35" cy="25" r="4" fill="currentColor" opacity="0.7"/><circle cx="50" cy="50" r="8" fill="currentColor" opacity="0.7"/><circle cx="65" cy="35" r="5" fill="currentColor" opacity="0.7"/><circle cx="80" cy="45" r="7" fill="currentColor" opacity="0.7"/><line x1="5" y1="75" x2="95" y2="75" stroke="currentColor" stroke-width="1"/><line x1="5" y1="75" x2="5" y2="15" stroke="currentColor" stroke-width="1"/></svg>',
    properties: {
      chartType: 'bubble',
      data: [
        { x: 10, y: 20, r: 5 },
        { x: 15, y: 35, r: 8 },
        { x: 25, y: 25, r: 6 },
        { x: 30, y: 45, r: 10 },
        { x: 40, y: 30, r: 4 },
        { x: 45, y: 40, r: 7 },
        { x: 50, y: 55, r: 9 },
        { x: 55, y: 35, r: 5 }
      ] as BubbleDataPoint[],
      width: 400,
      height: 300,
      pointColor: '#3b82f6',
      pointRadius: 5
    }
  }
]

// Computed combined photos (user media + stock photos)
const combinedPhotos = computed(() => {
  const photos: any[] = []
  
  // Add user uploaded media
  if (userMedia.value && userMedia.value.length > 0) {
    console.log('Adding user media:', userMedia.value.length)
    userMedia.value.forEach(media => {
      photos.push({
        id: `user-${media.id}`,
        label: media.name || 'User Photo',
        src: media.thumbnailUrl || media.url,
        preview: `<img src="${media.thumbnailUrl || media.url}" alt="${media.name || 'User Photo'}" style="width: 100%; height: 100%; object-fit: cover; border-radius: 4px;" />`,
        properties: {
          type: 'user-media',
          src: media.url,
          alt: media.name || 'User Photo',
          width: media.width || 300,
          height: media.height || 300
        },
        category: 'user'
      })
    })
  }
  
  // Add stock photos
  if (stockPhotos.value && stockPhotos.value.length > 0) {
    console.log('Adding stock photos:', stockPhotos.value.length)
    stockPhotos.value.forEach(stock => {
      photos.push({
        id: `stock-${stock.id}`,
        label: stock.alt || 'Stock Photo',
        src: stock.thumbnail,
        preview: `<img src="${stock.thumbnail}" alt="${stock.alt || 'Stock Photo'}" style="width: 100%; height: 100%; object-fit: cover; border-radius: 4px;" />`,
        properties: {
          type: 'stock-media',
          src: stock.src,
          alt: stock.alt || 'Stock Photo',
          width: stock.width || 300,
          height: stock.height || 300
        },
        category: 'stock'
      })
    })
  } else {
    console.log('No stock photos available:', stockPhotos.value)
  }
  
  console.log('Total combined photos:', photos.length)
  return photos
})

// Computed filtered data
const filteredTextStyles = computed(() => {
  if (!searchQuery.value) return textStyles
  return textStyles.filter(style => 
    style.label.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
    style.preview.toLowerCase().includes(searchQuery.value.toLowerCase())
  )
})

const filteredBasicShapes = computed(() => {
  if (!searchQuery.value) return basicShapes
  return basicShapes.filter(shape => 
    shape.label.toLowerCase().includes(searchQuery.value.toLowerCase())
  )
})

const filteredAdvancedShapes = computed(() => {
  if (!searchQuery.value) return advancedShapes
  return advancedShapes.filter(shape => 
    shape.label.toLowerCase().includes(searchQuery.value.toLowerCase())
  )
})

// Limited shapes for "All" category view (horizontal scroll)
const limitedBasicShapes = computed(() => {
  const maxShapes = 6
  return filteredBasicShapes.value.slice(0, maxShapes)
})

const filteredIcons = computed(() => {
  if (!searchQuery.value) return iconElements
  return iconElements.filter(icon => 
    icon.name.toLowerCase().includes(searchQuery.value.toLowerCase())
  )
})

// Limited icons for "All" category view (horizontal scroll)
const limitedIcons = computed(() => {
  const maxIcons = 6
  return filteredIcons.value.slice(0, maxIcons)
})

const filteredLines = computed(() => {
  if (!searchQuery.value) return lineElements
  return lineElements.filter(line => 
    line.label.toLowerCase().includes(searchQuery.value.toLowerCase())
  )
})

const filteredFrames = computed(() => {
  if (!searchQuery.value) return frameElements
  return frameElements.filter(frame => 
    frame.label.toLowerCase().includes(searchQuery.value.toLowerCase())
  )
})

const filteredCharts = computed(() => {
  if (!searchQuery.value) return chartElements
  return chartElements.filter(chart => 
    chart.label.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
    chart.type.toLowerCase().includes(searchQuery.value.toLowerCase())
  )
})

const filteredPhotos = computed(() => {
  // Don't apply additional filtering since search APIs already filter results
  // The combinedPhotos already include filtered results from searchPhotos() and searchUserMedia()
  return combinedPhotos.value
})

// Separate user photos and stock photos for visual organization
const userPhotos = computed(() => {
  return combinedPhotos.value.filter(photo => photo.category === 'user')
})

const stockPhotosFiltered = computed(() => {
  return combinedPhotos.value.filter(photo => photo.category === 'stock')
})

// Limited photos for "All" category view (horizontal scroll)
const limitedPhotos = computed(() => {
  const maxPhotos = 6
  const allPhotos = combinedPhotos.value
  return allPhotos.slice(0, maxPhotos)
})

// Methods
const addElement = (type: LayerType, properties: any = {}) => {
  emit('add-element', type, properties)
}

const addTrendingElement = (element: TrendingElement) => {
  addElement(element.type, element.properties)
}

// Calculate appropriate dimensions for the design stage
const calculateDimensions = (originalWidth: number, originalHeight: number) => {
  const maxWidth = 600
  const maxHeight = 400
  
  const aspectRatio = originalWidth / originalHeight
  let width = originalWidth
  let height = originalHeight
  
  if (width > maxWidth) {
    width = maxWidth
    height = width / aspectRatio
  }
  
  if (height > maxHeight) {
    height = maxHeight
    width = height * aspectRatio
  }
  
  return { width: Math.round(width), height: Math.round(height) }
}

// Validate uploaded file
const validateFile = (file: File): string | null => {
  // Check file size
  if (file.size > MAX_FILE_SIZE) {
    return `File size (${(file.size / 1024 / 1024).toFixed(1)}MB) exceeds maximum allowed size of ${MAX_FILE_SIZE / 1024 / 1024}MB`
  }
  
  // Check file type
  if (!ALLOWED_MIME_TYPES.includes(file.type)) {
    return `File type "${file.type}" is not supported. Please upload a JPEG, PNG, GIF, WebP, or SVG image.`
  }
  
  return null // No validation errors
}

const addShape = (shapeType: string, customProperties: any = {}) => {
  const defaultProps = {
    shapeType,
    fill: {
      type: 'solid',
      color: '#8B5CF6',
      opacity: 1
    },
    stroke: '#7C3AED',
    strokeWidth: 2,
    cornerRadius: 0
  }

  const finalProps = { ...defaultProps, ...customProperties }
  emit('add-element', 'shape', finalProps)
}

const addTextElement = () => {
  addElement('text', {
    text: 'Click to edit text',
    fontSize: 32,
    fontFamily: 'Inter',
    color: '#1F2937',
    fontWeight: '600',
    textAlign: 'left'
  })
}

const addImagePlaceholder = () => {
  // Trigger file input instead of using placeholder image
  fileInput.value?.click()
}

const addChart = (chart: ChartElement) => {
  // Transform chart element data to match ChartLayerProperties structure
  let chartData: any
  
  // Handle different chart data structures
  if (chart.properties.chartType === 'pie' || chart.properties.chartType === 'doughnut') {
    // For pie/doughnut charts, structure data differently
    chartData = {
      labels: chart.properties.data.map((item: any) => item.label),
      datasets: [{
        label: 'Dataset 1',
        data: chart.properties.data.map((item: any) => item.value),
        backgroundColor: chart.properties.data.map((item: any) => item.color || '#3B82F6'),
        borderColor: '#FFFFFF',
        borderWidth: 2
      }]
    }
  } else if (chart.properties.chartType === 'scatter') {
    // For scatter charts, use (x, y) data format
    chartData = {
      labels: [], // Scatter charts don't use labels
      datasets: [{
        label: 'Dataset 1',
        data: chart.properties.data, // Use the (x, y) data directly
        backgroundColor: chart.properties.pointColor || '#3B82F6',
        borderColor: chart.properties.strokeColor || '#1E40AF',
        borderWidth: 1,
        pointRadius: chart.properties.pointRadius || 5,
        pointBackgroundColor: chart.properties.pointColor || '#3B82F6',
        pointBorderColor: chart.properties.strokeColor || '#1E40AF'
      }]
    }
  } else if (chart.properties.chartType === 'bubble') {
    // For bubble charts, use (x, y, r) data format
    chartData = {
      labels: [], // Bubble charts don't use labels
      datasets: [{
        label: 'Dataset 1',
        data: chart.properties.data, // Use the (x, y, r) data directly
        backgroundColor: chart.properties.pointColor || '#3B82F6',
        borderColor: chart.properties.strokeColor || '#1E40AF',
        borderWidth: 1
      }]
    }
  } else {
    // For other chart types (bar, line, area)
    chartData = {
      labels: chart.properties.data.map((item: any) => item.label),
      datasets: [{
        label: 'Dataset 1',
        data: chart.properties.data.map((item: any) => item.value),
        backgroundColor: chart.properties.colors?.[0] || chart.properties.fillColor || '#3B82F6',
        borderColor: chart.properties.strokeColor || '#1E40AF',
        borderWidth: chart.properties.strokeWidth || 2,
        pointRadius: chart.properties.pointRadius || 3,
        pointBackgroundColor: chart.properties.pointColor || '#3B82F6',
        pointBorderColor: chart.properties.strokeColor || '#1E40AF'
      }]
    }
  }

  const chartProperties = {
    chartType: chart.properties.chartType,
    data: chartData,
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          display: true,
          position: 'top'
        },
        title: {
          display: false,
          text: 'Chart'
        },
        tooltip: {
          enabled: true
        }
      },
      scales: {
        x: {
          display: !['pie', 'doughnut'].includes(chart.properties.chartType),
          grid: {
            display: true,
            color: '#E5E7EB'
          }
        },
        y: {
          display: !['pie', 'doughnut'].includes(chart.properties.chartType),
          grid: {
            display: true,
            color: '#E5E7EB'
          }
        }
      },
      animation: {
        duration: 1000,
        easing: 'easeInOutQuad'
      }
    },
    theme: {
      primary: '#3B82F6',
      secondary: '#8B5CF6', 
      background: '#FFFFFF',
      text: '#1F2937',
      grid: '#E5E7EB',
      accent: ['#EF4444', '#F59E0B', '#10B981', '#F97316', '#8B5CF6', '#EC4899']
    }
  }

  const dimensions = {
    width: chart.properties.width || 300,
    height: chart.properties.height || 200
  }

  addElement('chart', { ...chartProperties, ...dimensions })
}

const addPhoto = (photo: any) => {
  // Add image to design
  const dimensions = calculateDimensions(
    photo.properties.width || 300, 
    photo.properties.height || 300
  )
  
  addElement('image', {
    src: photo.properties.src,
    alt: photo.properties.alt || photo.label,
    ...dimensions
  })
}

const viewAllPhotos = () => {
  activeCategory.value = 'photos'
}

const handleFileUpload = async (event: Event) => {
  const target = event.target as HTMLInputElement
  const files = target.files
  
  if (!files || files.length === 0) return
  
  try {
    const file = files[0] // Only handle the first file
    
    // Validate file before upload
    const validationError = validateFile(file)
    if (validationError) {
      uploadFailed(validationError)
      target.value = '' // Reset input
      return
    }
    
    isUploading.value = true
    
    const response = await mediaAPI.uploadMedia(file, {
      name: file.name
    })
    
    if (response.data?.data?.media) {
      const uploadedMedia = response.data.data.media
      const dimensions = calculateDimensions(uploadedMedia.width || 400, uploadedMedia.height || 400)
      
      // Add the uploaded image to the design
      emit('add-element', 'image', {
        src: uploadedMedia.url || uploadedMedia.thumbnailUrl,
        alt: uploadedMedia.name || 'Uploaded image',
        ...dimensions
      })
    }
    
    // Reset input
    target.value = ''
  } catch (error) {
    console.error('Failed to upload image:', error)
    const errorMessage = error instanceof Error ? error.message : 'Failed to upload image. Please try again.'
    uploadFailed(errorMessage)
    target.value = ''
  } finally {
    isUploading.value = false
  }
}

// Category navigation methods
const updateScrollState = () => {
  if (!categoriesContainer.value) return
  
  const container = categoriesContainer.value
  canScrollLeft.value = container.scrollLeft > 0
  canScrollRight.value = container.scrollLeft < (container.scrollWidth - container.clientWidth)
}

const scrollCategories = (direction: 'left' | 'right') => {
  if (!categoriesContainer.value) return
  
  const container = categoriesContainer.value
  const scrollAmount = 120 // Scroll by roughly one category button width
  
  if (direction === 'left') {
    container.scrollBy({ left: -scrollAmount, behavior: 'smooth' })
  } else {
    container.scrollBy({ left: scrollAmount, behavior: 'smooth' })
  }
  
  // Update scroll state after animation
  setTimeout(updateScrollState, 300)
}

// Initialize scroll state on mount
onMounted(() => {
  nextTick(() => {
    updateScrollState()
    // Initialize media
    initializeMedia()
  })
})

// Initialize media data
const initializeMedia = async () => {
  try {
    console.log('Initializing media...')
    
    // Load initial user media
    await searchUserMedia('', 'image')
    console.log('User media loaded:', userMedia.value.length)
    
    // Try different search terms to find stock photos
    const searchTerms = ['business', 'people', 'office', 'technology', 'nature']
    let stockLoaded = false
    
    for (const term of searchTerms) {
      if (!stockLoaded) {
        try {
          await searchPhotos(term)
          if (stockPhotos.value.length > 0) {
            console.log(`Stock photos loaded with term "${term}":`, stockPhotos.value.length)
            stockLoaded = true
            break
          }
        } catch (error) {
          console.warn(`Failed to load stock photos with term "${term}":`, error)
        }
      }
    }
    
    if (!stockLoaded) {
      console.warn('No stock photos could be loaded with any search term')
    }
  } catch (error) {
    console.error('Failed to initialize media:', error)
  }
}

// Search media when query changes
const searchMedia = async (query: string) => {
  if (activeCategory.value === 'photos' || activeCategory.value === 'all') {
    try {
      // Search user media
      await searchUserMedia(query, 'image')
      
      // Search stock photos - use a popular default term if query is empty
      const stockQuery = query || 'business'
      await searchPhotos(stockQuery)
      console.log(`Stock photos search "${stockQuery}" returned:`, stockPhotos.value.length)
    } catch (error) {
      console.error('Failed to search media:', error)
    }
  }
}

// Watch for search query changes
let searchTimeout: NodeJS.Timeout | null = null

watch(searchQuery, (newQuery) => {
  if (searchTimeout) {
    clearTimeout(searchTimeout)
  }
  
  searchTimeout = setTimeout(() => {
    // Always search, even with empty query (will use default terms)
    searchMedia(newQuery)
  }, 300) // Reduced timeout for better responsiveness
})

// Watch for category changes
watch(activeCategory, (newCategory) => {
  if (newCategory === 'photos') {
    initializeMedia()
  }
})
</script>

<style scoped>
/* Enhanced photo preview styling */
.photo-preview {
  transition: all 0.2s ease-out;
}

.photo-preview:hover {
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

/* Global hover effects for grouped elements */
.group:hover .group-hover\:scale-105 {
  transform: scale(1.05);
}

/* Custom focus styles for accessibility */
button:focus-visible {
  outline: 2px solid rgb(59 130 246);
  outline-offset: 2px;
}
</style>
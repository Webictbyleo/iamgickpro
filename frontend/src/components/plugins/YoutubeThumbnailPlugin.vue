<template>
  <div class="youtube-thumbnail-plugin min-h-screen bg-gradient-to-br from-slate-50 via-white to-blue-50">
    <!-- Modern Header with Glass Effect -->
    <div class="sticky top-0 z-10 backdrop-blur-lg bg-white/80 border-b border-gray-200/50 p-6 mb-8">
      <div class="max-w-4xl mx-auto">
        <div class="flex items-center justify-between">
          <div class="flex items-center space-x-4">
            <div class="relative">
              <div class="absolute inset-0 bg-gradient-to-r from-red-500 to-pink-500 rounded-2xl blur opacity-75"></div>
              <div class="relative bg-gradient-to-r from-red-500 to-pink-500 p-3 rounded-2xl">
                <svg class="w-8 h-8 text-white" fill="currentColor" viewBox="0 0 24 24">
                  <path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/>
                </svg>
              </div>
            </div>
            <div>
              <h1 class="text-2xl font-bold bg-gradient-to-r from-gray-900 to-gray-600 bg-clip-text text-transparent">
                YouTube Thumbnail Generator
              </h1>
              <p class="text-gray-600 text-sm font-medium">AI-powered thumbnail creation with 15+ styles</p>
            </div>
          </div>
          
          <!-- Quick Stats -->
          <div v-if="generatedThumbnails.length > 0" class="hidden md:flex items-center space-x-4">
            <div class="bg-green-50 border border-green-200 rounded-xl px-4 py-2">
              <div class="text-green-700 text-sm font-semibold">{{ generatedThumbnails.length }} Generated</div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="max-w-4xl mx-auto px-6 pb-12">
      <!-- Modern Video Input Card -->
      <div class="relative mb-8">
        <div class="absolute inset-0 bg-gradient-to-r from-blue-500/10 to-purple-500/10 rounded-3xl blur-xl"></div>
        <div class="relative bg-white/60 backdrop-blur-sm border border-white/20 shadow-xl rounded-3xl p-8">
          <div class="mb-6">
            <label class="block text-lg font-semibold text-gray-900 mb-3">
              🎬 Enter YouTube Video URL
            </label>
            <div class="relative">
              <input
                v-model="videoUrl"
                type="url"
                placeholder="https://www.youtube.com/watch?v=dQw4w9WgXcQ"
                class="w-full px-6 py-4 bg-white/80 border-2 border-gray-200 rounded-2xl focus:ring-4 focus:ring-red-500/20 focus:border-red-500 text-lg placeholder-gray-400 font-medium transition-all duration-200"
                :disabled="isProcessing"
                @keyup.enter="extractVideoInfo"
              />
              <div class="absolute right-3 top-1/2 transform -translate-y-1/2">
                <button
                  @click="extractVideoInfo"
                  :disabled="!videoUrl || isProcessing"
                  class="px-6 py-3 bg-gradient-to-r from-red-500 to-pink-500 hover:from-red-600 hover:to-pink-600 text-white rounded-xl font-semibold shadow-lg hover:shadow-xl disabled:opacity-50 disabled:cursor-not-allowed flex items-center space-x-2 transition-all duration-200 transform hover:scale-105"
                >
                  <svg v-if="isProcessing && !videoInfo" class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="m4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                  </svg>
                  <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                  </svg>
                  <span>{{ isProcessing && !videoInfo ? 'Analyzing...' : 'Analyze' }}</span>
                </button>
              </div>
            </div>
          </div>
          
          <!-- Error Message with Modern Design -->
          <div v-if="errorMessage" class="mb-6 p-4 bg-red-50/80 backdrop-blur-sm border border-red-200 rounded-2xl">
            <div class="flex items-center space-x-3">
              <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z" />
              </svg>
              <p class="text-red-700 font-medium">{{ errorMessage }}</p>
            </div>
          </div>
          
          <!-- Enhanced Video Preview -->
          <div v-if="videoInfo" class="bg-white/80 backdrop-blur-sm border border-gray-200 rounded-2xl p-6 shadow-lg">
            <div class="flex items-center space-x-6">
              <div class="relative group">
                <img
                  :src="videoInfo.thumbnailUrl"
                  :alt="videoInfo.title"
                  class="w-32 h-24 object-cover rounded-xl shadow-md group-hover:shadow-lg transition-shadow duration-200"
                />
                <div class="absolute inset-0 bg-black/20 rounded-xl flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity duration-200">
                  <svg class="w-8 h-8 text-white" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M8 5v14l11-7z"/>
                  </svg>
                </div>
              </div>
              <div class="flex-1 min-w-0">
                <h3 class="text-xl font-bold text-gray-900 mb-2 truncate">{{ videoInfo.title }}</h3>
                <p class="text-gray-600 font-medium mb-3">{{ videoInfo.channelTitle }}</p>
                <div class="flex items-center space-x-4">
                  <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">
                    ✅ Video Analyzed
                  </span>
                  <span class="text-sm text-gray-500">Ready for generation</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Generation Options with Modern Design -->
      <div v-if="videoInfo" class="relative mb-8">
        <div class="absolute inset-0 bg-gradient-to-r from-purple-500/10 to-pink-500/10 rounded-3xl blur-xl"></div>
        <div class="relative bg-white/60 backdrop-blur-sm border border-white/20 shadow-xl rounded-3xl p-8">
          <div class="flex items-center space-x-3 mb-6">
            <div class="relative">
              <div class="absolute inset-0 bg-gradient-to-r from-purple-500 to-pink-500 rounded-xl blur opacity-75"></div>
              <div class="relative bg-gradient-to-r from-purple-500 to-pink-500 p-2 rounded-xl">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                </svg>
              </div>
            </div>
            <h4 class="text-xl font-bold bg-gradient-to-r from-gray-900 to-gray-600 bg-clip-text text-transparent">
              AI Generation Settings
            </h4>
          </div>
          
          <!-- AI Generation Info -->
          <div class="mb-8 p-4 bg-gradient-to-r from-blue-50/80 to-indigo-50/80 backdrop-blur-sm border border-blue-200/50 rounded-2xl">
            <div class="flex items-start space-x-3">
              <div class="flex-shrink-0 mt-0.5">
                <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center">
                  <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>
                </div>
              </div>
              <div>
                <h5 class="font-semibold text-blue-900 mb-1">🤖 AI-Powered Generation</h5>
                <p class="text-sm text-blue-700 leading-relaxed">
                  Uses <strong>Google Imagen 4 Ultra</strong> (Replicate) or <strong>OpenAI GPT-4 Vision</strong> 
                  for high-quality, contextual thumbnail creation.
                </p>
              </div>
            </div>
          </div>
          
          <div class="grid md:grid-cols-2 gap-8">
            <!-- Style Selection -->
            <div class="space-y-4">
              <Listbox v-model="selectedThumbnailStyle">
                <ListboxLabel class="block text-lg font-semibold text-gray-900 mb-3">
                  🎨 Thumbnail Style
                </ListboxLabel>
                <div class="relative">
                  <ListboxButton class="relative w-full cursor-pointer rounded-2xl bg-white/80 backdrop-blur-sm py-4 pl-4 pr-12 text-left border-2 border-gray-200 focus:border-purple-500 focus:outline-none focus:ring-4 focus:ring-purple-500/20 text-base font-medium shadow-lg hover:shadow-xl transition-all duration-200">
                    <span class="flex items-center">
                      <div class="w-8 h-8 rounded-xl bg-purple-100 text-purple-600 flex items-center justify-center mr-3 shadow-sm">
                        <component :is="getStyleIcon(selectedThumbnailStyle)" class="w-5 h-5" />
                      </div>
                      <div>
                        <div class="font-semibold text-gray-900">
                          {{ thumbnailStyles.find(s => s.value === selectedThumbnailStyle)?.label }}
                        </div>
                        <div class="text-sm text-gray-600 mt-0.5">
                          {{ thumbnailStyles.find(s => s.value === selectedThumbnailStyle)?.description }}
                        </div>
                      </div>
                    </span>
                    <span class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-4">
                      <ChevronUpDownIcon class="h-6 w-6 text-gray-400" aria-hidden="true" />
                    </span>
                  </ListboxButton>

                  <transition
                    enter-active-class="transition duration-200 ease-out"
                    enter-from-class="opacity-0 scale-95"
                    enter-to-class="opacity-100 scale-100"
                    leave-active-class="transition duration-150 ease-in"
                    leave-from-class="opacity-100 scale-100"
                    leave-to-class="opacity-0 scale-95"
                  >
                    <ListboxOptions class="absolute z-20 mt-2 max-h-80 w-full overflow-auto rounded-2xl bg-white/95 backdrop-blur-md py-2 shadow-2xl ring-1 ring-black/5 focus:outline-none">
                      <ListboxOption
                        v-for="style in thumbnailStyles"
                        :key="style.value"
                        :value="style.value"
                        v-slot="{ active, selected }"
                      >
                        <li
                          :class="[
                            active ? 'bg-purple-50/80 text-purple-900' : 'text-gray-900',
                            'relative cursor-pointer select-none py-3 px-4 m-1 rounded-xl transition-colors duration-150'
                          ]"
                        >
                          <div class="flex items-center">
                            <div
                              :class="[
                                'w-8 h-8 rounded-xl flex items-center justify-center mr-3 shadow-sm',
                                selected || active
                                  ? 'bg-purple-100 text-purple-600'
                                  : 'bg-gray-100 text-gray-400'
                              ]"
                            >
                              <component :is="getStyleIcon(style.value)" class="w-5 h-5" />
                            </div>
                            <div class="flex-1">
                              <span
                                :class="[
                                  selected ? 'font-bold' : 'font-semibold',
                                  'block text-sm'
                                ]"
                              >
                                {{ style.label }}
                              </span>
                              <span
                                :class="[
                                  active ? 'text-purple-700' : 'text-gray-500',
                                  'block text-xs mt-0.5'
                                ]"
                              >
                                {{ style.description }}
                              </span>
                            </div>
                            <span
                              v-if="selected"
                              :class="[
                                active ? 'text-purple-600' : 'text-purple-500',
                                'flex items-center'
                              ]"
                            >
                              <CheckIcon class="h-5 w-5" aria-hidden="true" />
                            </span>
                          </div>
                        </li>
                      </ListboxOption>
                    </ListboxOptions>
                  </transition>
                </div>
              </Listbox>
            </div>

            <!-- Settings Panel -->
            <div class="space-y-6">
              <!-- Number of Thumbnails -->
              <div>
                <label class="block text-lg font-semibold text-gray-900 mb-3">
                  📊 Generate {{ maxThumbnails }} Thumbnails
                </label>
                <div class="relative">
                  <input
                    v-model.number="maxThumbnails"
                    type="range"
                    min="1"
                    max="10"
                    class="w-full h-3 bg-gradient-to-r from-purple-200 to-pink-200 rounded-full appearance-none cursor-pointer slider"
                  />
                  <div class="flex justify-between text-sm font-medium text-gray-600 mt-2">
                    <span class="bg-white/80 px-2 py-1 rounded-lg">1</span>
                    <span class="bg-white/80 px-2 py-1 rounded-lg">10</span>
                  </div>
                </div>
              </div>

              <!-- Custom Prompt -->
              <div>
                <label class="block text-lg font-semibold text-gray-900 mb-3">
                  ✍️ Custom Instructions (Optional)
                </label>
                <textarea
                  v-model="customPrompt"
                  rows="3"
                  placeholder="e.g., 'Make it more dramatic with dark colors' or 'Add gaming elements'"
                  class="w-full px-4 py-3 bg-white/80 backdrop-blur-sm border-2 border-gray-200 rounded-2xl focus:ring-4 focus:ring-purple-500/20 focus:border-purple-500 text-base placeholder-gray-400 font-medium resize-none"
                  :disabled="isProcessing"
                ></textarea>
              </div>
            </div>
          </div>

          <!-- Generate Button -->
          <div class="mt-8">
            <button
              @click="generateThumbnails"
              :disabled="isProcessing"
              class="w-full group relative overflow-hidden px-8 py-5 bg-gradient-to-r from-purple-600 via-pink-600 to-red-600 hover:from-purple-700 hover:via-pink-700 hover:to-red-700 disabled:from-gray-400 disabled:to-gray-500 text-white rounded-2xl font-bold text-lg shadow-2xl hover:shadow-purple-500/25 disabled:cursor-not-allowed transition-all duration-300 transform hover:scale-105 disabled:hover:scale-100"
            >
              <div class="absolute inset-0 bg-gradient-to-r from-white/20 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
              <div class="relative flex items-center justify-center space-x-3">
                <svg v-if="isProcessing && currentJob" class="w-6 h-6 animate-spin" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="m4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <svg v-else class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                </svg>
                <span>{{ isProcessing && currentJob ? '🚀 Creating AI Thumbnails...' : '✨ Generate Thumbnails with AI' }}</span>
              </div>
            </button>
          </div>
        </div>
      </div>

      <!-- Enhanced Progress Indicator -->
      <div v-if="isProcessing && currentJob" class="relative mb-8">
        <div class="absolute inset-0 bg-gradient-to-r from-blue-500/10 to-cyan-500/10 rounded-3xl blur-xl"></div>
        <div class="relative bg-white/70 backdrop-blur-md border border-white/30 shadow-2xl rounded-3xl p-8">
          
          <!-- Progress Header -->
          <div class="flex items-start space-x-4 mb-6">
            <div class="flex-shrink-0">
              <div class="relative">
                <div class="absolute inset-0 bg-gradient-to-r from-blue-500 to-cyan-500 rounded-2xl blur opacity-75 animate-pulse"></div>
                <div class="relative bg-gradient-to-r from-blue-500 to-cyan-500 p-3 rounded-2xl">
                  <svg class="w-8 h-8 text-white animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="m4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                  </svg>
                </div>
              </div>
            </div>
            <div class="flex-1">
              <h4 class="text-xl font-bold text-gray-900 mb-2">
                🚀 {{ currentJob.method === 'replicate' ? 'AI Generation in Progress' : 'Creating Thumbnails' }}
              </h4>
              <p class="text-gray-700 font-medium">{{ getProgressMessage() }}</p>
              <div class="flex items-center space-x-2 mt-2">
                <div class="px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-sm font-semibold">
                  {{ currentJob.method === 'replicate' ? '🧠 Imagen 4 Ultra' : '🤖 GPT-4 Vision' }}
                </div>
                <div class="px-3 py-1 bg-gray-100 text-gray-700 rounded-full text-sm font-medium">
                  {{ Math.round((Date.now() - currentJob.startTime) / 1000) }}s elapsed
                </div>
              </div>
            </div>
          </div>
          
          <!-- Enhanced Timeout Warning for Replicate -->
          <div v-if="currentJob.method === 'replicate'" class="mb-6 p-4 bg-gradient-to-r from-amber-50/80 to-orange-50/80 backdrop-blur-sm border border-amber-200/70 rounded-2xl">
            <div class="flex items-start space-x-3">
              <div class="flex-shrink-0 mt-0.5">
                <div class="w-8 h-8 bg-amber-100 rounded-full flex items-center justify-center">
                  <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.864-.833-2.634 0L4.232 15.5c-.77.833.192 2.5 1.732 2.5z" />
                  </svg>
                </div>
              </div>
              <div>
                <h5 class="font-semibold text-amber-900 mb-1">⏱️ High-Quality AI Processing</h5>
                <p class="text-sm text-amber-800 leading-relaxed">
                  <strong>Google Imagen 4 Ultra</strong> creates exceptional thumbnails but takes 3-8 minutes for {{ maxThumbnails }} variations. 
                  The wait is worth it! ✨
                </p>
              </div>
            </div>
          </div>
          
          <!-- Modern Progress Bar -->
          <div class="space-y-4">
            <div class="flex justify-between items-center text-sm font-semibold">
              <span class="text-gray-700">Progress</span>
              <span class="text-blue-600">{{ currentJob.progress || 0 }}% Complete</span>
            </div>
            
            <div class="relative">
              <div class="w-full bg-gray-200/80 rounded-full h-3 shadow-inner">
                <div
                  class="bg-gradient-to-r from-blue-500 via-purple-500 to-pink-500 h-3 rounded-full transition-all duration-500 ease-out shadow-lg relative overflow-hidden"
                  :style="{ width: `${currentJob.progress || 0}%` }"
                >
                  <div class="absolute inset-0 bg-gradient-to-r from-white/30 to-transparent animate-pulse"></div>
                </div>
              </div>
            </div>
            
            <div class="flex justify-between items-center text-xs text-gray-600">
              <span class="bg-white/60 px-2 py-1 rounded-lg">{{ currentJob.status || 'processing' }}</span>
              <span v-if="currentJob.method === 'replicate' && currentJob.currentVariation" class="bg-white/60 px-2 py-1 rounded-lg">
                Variation {{ currentJob.currentVariation }} of {{ maxThumbnails }}
              </span>
            </div>
          </div>
          
          <!-- Estimated Time Remaining -->
          <div v-if="currentJob.method === 'replicate' && currentJob.progress > 0" class="mt-4 text-center">
            <div class="inline-flex items-center space-x-2 px-4 py-2 bg-blue-50/80 backdrop-blur-sm border border-blue-200/50 rounded-full">
              <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
              <span class="text-sm font-medium text-blue-800">
                Est. {{ Math.max(1, Math.ceil((100 - currentJob.progress) / 15)) }} min remaining
              </span>
            </div>
          </div>
        </div>
      </div>

      <!-- Enhanced Recent Thumbnails Section -->
      <div class="relative mb-8">
        <div class="absolute inset-0 bg-gradient-to-r from-green-500/10 to-emerald-500/10 rounded-3xl blur-xl"></div>
        <div class="relative bg-white/60 backdrop-blur-sm border border-white/20 shadow-xl rounded-3xl p-8">
          
          <!-- Section Header -->
          <div class="flex items-center justify-between mb-6">
            <div class="flex items-center space-x-3">
              <div class="relative">
                <div class="absolute inset-0 bg-gradient-to-r from-green-500 to-emerald-500 rounded-xl blur opacity-75"></div>
                <div class="relative bg-gradient-to-r from-green-500 to-emerald-500 p-2 rounded-xl">
                  <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>
                </div>
              </div>
              <h4 class="text-xl font-bold bg-gradient-to-r from-gray-900 to-gray-600 bg-clip-text text-transparent">
                📚 Recent Generations
              </h4>
            </div>
            <button
              @click="toggleRecentThumbnails"
              :disabled="loadingRecentThumbnails"
              class="group relative overflow-hidden px-4 py-2 bg-gradient-to-r from-green-100 to-emerald-100 hover:from-green-200 hover:to-emerald-200 text-green-700 rounded-xl font-semibold shadow-lg hover:shadow-xl transition-all duration-200 transform hover:scale-105 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              <div class="flex items-center space-x-2">
                <svg v-if="loadingRecentThumbnails" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="m4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <svg v-else class="w-4 h-4 transition-transform duration-200" :class="{ 'rotate-180': showRecentThumbnails }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
                <span>{{ showRecentThumbnails ? 'Hide Gallery' : 'View Gallery' }}</span>
              </div>
            </button>
          </div>
          
          <!-- Recent Thumbnails Grid -->
          <div v-if="showRecentThumbnails" class="space-y-6">
            
            <!-- Empty State -->
            <div v-if="recentThumbnails.length === 0 && !loadingRecentThumbnails" class="text-center py-12">
              <div class="relative inline-block mb-4">
                <div class="absolute inset-0 bg-gradient-to-r from-gray-300 to-gray-400 rounded-full blur opacity-75"></div>
                <div class="relative bg-gradient-to-r from-gray-300 to-gray-400 p-4 rounded-full">
                  <svg class="w-12 h-12 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                  </svg>
                </div>
              </div>
              <h5 class="text-xl font-bold text-gray-900 mb-2">🎨 No thumbnails yet</h5>
              <p class="text-gray-600 mb-4">Generate some AI thumbnails to see them here</p>
              <div class="inline-flex items-center space-x-2 px-4 py-2 bg-blue-50 border border-blue-200 rounded-xl">
                <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span class="text-blue-700 text-sm font-medium">Start by entering a YouTube URL above</span>
              </div>
            </div>
            
            <!-- Thumbnail Grid -->
            <div v-else class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4">
              <div
                v-for="thumbnail in recentThumbnails"
                :key="thumbnail.id"
                class="group relative bg-white/80 backdrop-blur-sm rounded-2xl overflow-hidden border border-gray-200/50 hover:border-white hover:shadow-2xl transition-all duration-300 transform hover:scale-105"
              >
                <!-- Image Container -->
                <div class="aspect-video relative overflow-hidden">
                  <img
                    :src="thumbnail.preview_url"
                    :alt="thumbnail.title"
                    class="w-full h-full object-cover transition-transform duration-300 group-hover:scale-110"
                  />
                  
                  <!-- Hover Overlay -->
                  <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300">
                    <div class="absolute bottom-3 left-3 right-3 flex space-x-2">
                      <a
                        :href="thumbnail.image_url"
                        target="_blank"
                        class="flex-1 px-3 py-2 bg-white/90 backdrop-blur-sm text-gray-900 rounded-lg text-xs font-semibold hover:bg-white transition-colors text-center"
                      >
                        🔍 View Full
                      </a>
                    </div>
                  </div>
                  
                  <!-- AI Badge -->
                  <div class="absolute top-2 left-2">
                    <div class="px-2 py-1 bg-black/70 backdrop-blur-sm text-white text-xs rounded-lg font-medium">
                      {{ thumbnail.generation_method === 'replicate' ? '🧠 Imagen' : '🤖 GPT-4' }}
                    </div>
                  </div>
                </div>
                
                <!-- Info Panel -->
                <div class="p-3">
                  <div class="mb-2">
                    <h6 class="font-semibold text-gray-900 text-sm truncate" :title="thumbnail.video_title || thumbnail.video_id">
                      {{ thumbnail.video_title || thumbnail.video_id }}
                    </h6>
                  </div>
                  
                  <div class="flex items-center justify-between text-xs text-gray-600">
                    <span class="flex items-center space-x-1">
                      <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                      </svg>
                      <span>{{ new Date(thumbnail.created_at).toLocaleDateString() }}</span>
                    </span>
                    <div class="w-2 h-2 bg-green-400 rounded-full"></div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Enhanced Generated Thumbnails -->
      <div v-if="generatedThumbnails.length > 0" class="relative">
        <div class="absolute inset-0 bg-gradient-to-r from-indigo-500/10 to-purple-500/10 rounded-3xl blur-xl"></div>
        <div class="relative bg-white/60 backdrop-blur-sm border border-white/20 shadow-xl rounded-3xl p-8">
          
          <!-- Results Header -->
          <div class="flex items-center justify-between mb-8">
            <div class="flex items-center space-x-3">
              <div class="relative">
                <div class="absolute inset-0 bg-gradient-to-r from-indigo-500 to-purple-500 rounded-xl blur opacity-75"></div>
                <div class="relative bg-gradient-to-r from-indigo-500 to-purple-500 p-2 rounded-xl">
                  <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                  </svg>
                </div>
              </div>
              <div>
                <h4 class="text-xl font-bold bg-gradient-to-r from-gray-900 to-gray-600 bg-clip-text text-transparent">
                  ✨ Generated Thumbnails
                </h4>
                <p class="text-gray-600 font-medium">{{ generatedThumbnails.length }} high-quality AI thumbnails</p>
              </div>
            </div>
            
            <!-- Quick Actions -->
            <div class="flex items-center space-x-3">
              <button
                @click="downloadAllThumbnails"
                class="group px-4 py-2 bg-gradient-to-r from-blue-500 to-cyan-500 hover:from-blue-600 hover:to-cyan-600 text-white rounded-xl font-semibold shadow-lg hover:shadow-xl transition-all duration-200 transform hover:scale-105"
              >
                <div class="flex items-center space-x-2">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                  </svg>
                  <span>Download All</span>
                </div>
              </button>
            </div>
          </div>
          
          <!-- Thumbnails Grid -->
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <div
              v-for="(thumbnail, index) in generatedThumbnails"
              :key="thumbnail.id"
              class="group relative bg-white/80 backdrop-blur-sm rounded-2xl overflow-hidden border border-gray-200/50 hover:border-white hover:shadow-2xl transition-all duration-300 transform hover:scale-105"
            >
              <!-- Image Container -->
              <div class="aspect-video relative overflow-hidden">
                <img
                  :src="thumbnail.previewUrl"
                  :alt="thumbnail.title"
                  class="w-full h-full object-cover transition-transform duration-300 group-hover:scale-110"
                />
                
                <!-- Hover Overlay -->
                <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/20 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300">
                  <div class="absolute bottom-4 left-4 right-4 space-y-3">
                    <!-- Preview Button -->
                    <button
                      @click="previewThumbnail(thumbnail)"
                      class="w-full bg-white/90 backdrop-blur-sm hover:bg-white text-gray-900 py-2 px-4 rounded-xl font-semibold shadow-lg hover:shadow-xl transition-all duration-200 transform hover:scale-105"
                    >
                      🔍 Preview Full Size
                    </button>
                  </div>
                </div>
                
                <!-- Quality Badge -->
                <div class="absolute top-3 left-3">
                  <div class="px-3 py-1 bg-black/70 backdrop-blur-sm text-white text-xs rounded-full font-semibold">
                    {{ thumbnail.generationMethod === 'replicate' ? '🧠 Imagen 4 Ultra' : '🤖 GPT-4 Vision' }}
                  </div>
                </div>
                
                <!-- Index Badge -->
                <div class="absolute top-3 right-3">
                  <div class="w-8 h-8 bg-indigo-500 text-white rounded-full flex items-center justify-center text-sm font-bold shadow-lg">
                    {{ index + 1 }}
                  </div>
                </div>
              </div>
              
              <!-- Content Panel -->
              <div class="p-5 space-y-4">
                <!-- Title and Style -->
                <div>
                  <h5 class="font-bold text-gray-900 text-base truncate mb-1" :title="thumbnail.title">
                    {{ thumbnail.title }}
                  </h5>
                  <div class="flex items-center space-x-2">
                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold bg-purple-100 text-purple-800">
                      {{ thumbnail.style }} style
                    </span>
                    <span class="text-xs text-gray-500">{{ new Date(thumbnail.createdAt).toLocaleDateString() }}</span>
                  </div>
                </div>

                <!-- Action Buttons -->
                <div class="grid grid-cols-2 gap-3">
                  <button
                    @click="previewThumbnail(thumbnail)"
                    class="flex items-center justify-center space-x-2 bg-gray-100 hover:bg-gray-200 text-gray-700 py-3 px-4 rounded-xl font-semibold transition-all duration-200 transform hover:scale-105"
                  >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    </svg>
                    <span>Preview</span>
                  </button>
                  <button
                    @click="downloadThumbnail(thumbnail)"
                    class="flex items-center justify-center space-x-2 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white py-3 px-4 rounded-xl font-semibold shadow-lg hover:shadow-xl transition-all duration-200 transform hover:scale-105"
                  >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <span>Download</span>
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, h, onUnmounted } from 'vue'
import {
  Listbox,
  ListboxButton,
  ListboxLabel,
  ListboxOption,
  ListboxOptions,
} from '@headlessui/vue'
import { ChevronUpDownIcon, CheckIcon } from '@heroicons/vue/24/outline'
import { pluginAPI } from '@/services/api'
import type { 
  YouTubeVideoInfo, 
  YouTubeThumbnailDisplay,
  YouTubeVideoAnalysisResult,
  YouTubeThumbnailGenerationResult,
  PluginServiceResponse
} from '@/types'

// Props
interface Props {
  initialVideoUrl?: string
  autoAnalyze?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  initialVideoUrl: '',
  autoAnalyze: false
})

// Emits
const emit = defineEmits<{
  'thumbnail-selected': [thumbnail: YouTubeThumbnailDisplay]
  'video-analyzed': [videoInfo: YouTubeVideoInfo]
  'thumbnails-generated': [thumbnails: YouTubeThumbnailDisplay[]]
  'error': [error: string]
}>()

// Reactive state
const videoUrl = ref(props.initialVideoUrl)
const videoInfo = ref<YouTubeVideoInfo | null>(null)
const isProcessing = ref(false)
const errorMessage = ref('')
const currentJob = ref<any>(null)
const generatedThumbnails = ref<YouTubeThumbnailDisplay[]>([])

// Thumbnail-specific reactive variables
const selectedThumbnailStyle = ref<string>('modern')
const maxThumbnails = ref(5)
const customPrompt = ref('')

// Recent thumbnails
const recentThumbnails = ref<any[]>([])
const showRecentThumbnails = ref(false)
const loadingRecentThumbnails = ref(false)

// Track polling interval for cleanup
let pollInterval: NodeJS.Timeout | null = null

// Enhanced thumbnail style options (matching backend)
const thumbnailStyles = [
  { 
    value: 'modern', 
    label: 'Modern & Clean',
    description: 'Contemporary design with bold typography'
  },
  { 
    value: 'dramatic', 
    label: 'Dramatic & Bold',
    description: 'High-contrast, cinematic feel'
  },
  { 
    value: 'colorful', 
    label: 'Vibrant & Colorful',
    description: 'Bright, energetic rainbow colors'
  },
  { 
    value: 'minimalist', 
    label: 'Minimal & Professional',
    description: 'Simple, elegant design with white space'
  },
  { 
    value: 'professional', 
    label: 'Professional & Polished',
    description: 'Corporate, sophisticated design'
  },
  { 
    value: 'gaming', 
    label: 'Gaming & Esports',
    description: 'Neon accents, competitive energy'
  },
  { 
    value: 'tech', 
    label: 'Tech & Futuristic',
    description: 'Sci-fi inspired, digital elements'
  },
  { 
    value: 'educational', 
    label: 'Educational & Clear',
    description: 'Learning-focused, informative design'
  },
  { 
    value: 'entertainment', 
    label: 'Entertainment & Fun',
    description: 'Playful, engaging show-business vibes'
  },
  { 
    value: 'business', 
    label: 'Business & Success',
    description: 'Professional growth, corporate success'
  },
  { 
    value: 'lifestyle', 
    label: 'Lifestyle & Personal',
    description: 'Warm, relatable everyday imagery'
  },
  { 
    value: 'vintage', 
    label: 'Vintage & Retro',
    description: 'Classic 80s-90s nostalgic appeal'
  },
  { 
    value: 'neon', 
    label: 'Neon & Cyberpunk',
    description: 'Glowing effects, futuristic energy'
  },
  { 
    value: 'cinematic', 
    label: 'Cinematic & Epic',
    description: 'Movie-quality, dramatic storytelling'
  },
  { 
    value: 'cartoon', 
    label: 'Cartoon & Animated',
    description: 'Fun illustrations, comic-book style'
  }
] as const

// Function to get appropriate icon component for each style
const getStyleIcon = (styleValue: string) => {
  const iconComponents = {
    modern: () => h('svg', {
      fill: 'none',
      stroke: 'currentColor',
      viewBox: '0 0 24 24'
    }, [
      h('path', {
        'stroke-linecap': 'round',
        'stroke-linejoin': 'round',
        'stroke-width': '2',
        d: 'M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z'
      })
    ]),
    dramatic: () => h('svg', {
      fill: 'none',
      stroke: 'currentColor',
      viewBox: '0 0 24 24'
    }, [
      h('path', {
        'stroke-linecap': 'round',
        'stroke-linejoin': 'round',
        'stroke-width': '2',
        d: 'M13 10V3L4 14h7v7l9-11h-7z'
      })
    ]),
    colorful: () => h('svg', {
      fill: 'none',
      stroke: 'currentColor',
      viewBox: '0 0 24 24'
    }, [
      h('path', {
        'stroke-linecap': 'round',
        'stroke-linejoin': 'round',
        'stroke-width': '2',
        d: 'M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zM21 5a2 2 0 00-2-2h-4a2 2 0 00-2 2v12a4 4 0 004 4h4a2 2 0 002-2V5z'
      })
    ]),
    minimalist: () => h('svg', {
      fill: 'none',
      stroke: 'currentColor',
      viewBox: '0 0 24 24'
    }, [
      h('path', {
        'stroke-linecap': 'round',
        'stroke-linejoin': 'round',
        'stroke-width': '2',
        d: 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'
      })
    ]),
    professional: () => h('svg', {
      fill: 'none',
      stroke: 'currentColor',
      viewBox: '0 0 24 24'
    }, [
      h('path', {
        'stroke-linecap': 'round',
        'stroke-linejoin': 'round',
        'stroke-width': '2',
        d: 'M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2-2v2m8 0V6a2 2 0 012 2v6a2 2 0 01-2 2H6a2 2 0 01-2-2V8a2 2 0 012-2V6'
      })
    ])
  }
  
  return iconComponents[styleValue as keyof typeof iconComponents] || iconComponents.modern
}

// Methods
const extractVideoInfo = async (): Promise<void> => {
  if (!videoUrl.value) return
  
  try {
    isProcessing.value = true
    errorMessage.value = ''
    
    const response = await pluginAPI.executeCommand({
      pluginId: 'youtube_thumbnail',
      command: 'analyze_video',
      layerId: null,
      parameters: {
        video_url: videoUrl.value
      }
    })
    
    if (response.data?.success && response.data.data.success) {
      const analysisResult = response.data.data.result as YouTubeVideoAnalysisResult
      // Convert to the expected YouTubeVideoInfo format
      videoInfo.value = {
        videoId: analysisResult.video_id,
        title: analysisResult.title,
        description: analysisResult.description || '',
        thumbnailUrl: analysisResult.thumbnail_url,
        channelTitle: analysisResult.author_name,
        publishedAt: analysisResult.fetched_at,
        viewCount: analysisResult.view_count || 0,
        tags: []
      }
      emit('video-analyzed', videoInfo.value as YouTubeVideoInfo)
    } else {
      throw new Error('Failed to analyze video')
    }
  } catch (error: any) {
    console.error('Failed to extract video info:', error)
    const errorMsg = error?.response?.data?.message || 'Failed to analyze video. Please check the URL and try again.'
    errorMessage.value = errorMsg
    emit('error', errorMsg)
  } finally {
    isProcessing.value = false
  }
}

const generateThumbnails = async (): Promise<void> => {
  if (!videoInfo.value) return
  
  let progressInterval: NodeJS.Timeout | null = null
  
  try {
    isProcessing.value = true
    errorMessage.value = ''
    
    // Initialize progress tracking
    currentJob.value = {
      progress: 0,
      status: 'starting',
      method: 'unknown',
      currentVariation: null,
      startTime: Date.now()
    }
    
    // Try async mode first (better for long-running operations)
    try {
      await generateThumbnailsAsync()
    } catch (asyncError) {
      console.warn('Async mode failed, falling back to sync mode:', asyncError)
      await generateThumbnailsSync()
    }
    
  } catch (error: any) {
    console.error('Failed to generate thumbnails:', error)
    const errorMsg = error?.response?.data?.message || 'Failed to generate thumbnails. Please try again.'
    errorMessage.value = errorMsg
    emit('error', errorMsg)
  } finally {
    if (progressInterval) {
      clearInterval(progressInterval)
    }
    isProcessing.value = false
  }
}

const generateThumbnailsAsync = async (): Promise<void> => {
  // Start async job
  const response = await pluginAPI.executeCommand({
    pluginId: 'youtube_thumbnail',
    command: 'generate_thumbnail_variations_async',
    layerId: null,
    parameters: {
      video_url: videoUrl.value,
      thumbnail_count: maxThumbnails.value,
      style: selectedThumbnailStyle.value,
      custom_prompt: customPrompt.value || null
    }
  })
  
  console.log('Async response structure:', JSON.stringify(response.data, null, 2))
  
  if (!response.data?.success || !(response.data as any)?.data?.result?.success) {
    throw new Error('Failed to start async thumbnail generation')
  }
  
  const jobId = (response.data as any).data.result.job_id
  
  if (!jobId) {
    console.error('No job_id found in response:', response.data)
    throw new Error('No job_id returned from async command')
  }
  
  console.log('Extracted job_id:', jobId)
  currentJob.value.status = 'queued'
  currentJob.value.progress = 5
  
  // Poll for progress
  await pollJobProgress(jobId)
}

const generateThumbnailsSync = async (): Promise<void> => {
  // Simulate progress for sync mode
  const progressInterval = setInterval(() => {
    if (currentJob.value && currentJob.value.progress < 90) {
      const increment = Math.random() * 8 + 2 // 2-10% increments
      currentJob.value.progress = Math.min(90, currentJob.value.progress + increment)
      
      const estimatedVariation = Math.ceil((currentJob.value.progress / 90) * maxThumbnails.value)
      currentJob.value.currentVariation = estimatedVariation
    }
  }, 3000)
  
  try {
    const response = await pluginAPI.executeCommand({
      pluginId: 'youtube_thumbnail',
      command: 'generate_thumbnail_variations',
      layerId: null,
      parameters: {
        video_url: videoUrl.value,
        thumbnail_count: maxThumbnails.value,
        style: selectedThumbnailStyle.value,
        custom_prompt: customPrompt.value || null
      }
    }, {
      timeout: 700000 // 11+ minutes timeout to accommodate Replicate processing
    })
    
    clearInterval(progressInterval)
    
    if (response.data?.success && response.data.data.success) {
      processGenerationResult(response.data.data.result as YouTubeThumbnailGenerationResult)
    } else {
      throw new Error('Sync generation failed')
    }
  } catch (error) {
    clearInterval(progressInterval)
    throw error
  }
}

const pollJobProgress = async (jobId: string): Promise<void> => {
  const maxPolls = 200 // ~15 minutes with 4.5 second intervals
  let polls = 0
  
  while (polls < maxPolls) {
    await new Promise(resolve => setTimeout(resolve, 4500)) // 4.5 second intervals
    polls++
    
    try {
      const statusResponse = await pluginAPI.executeCommand({
        pluginId: 'youtube_thumbnail',
        command: 'get_job_status',
        layerId: null,
        parameters: { job_id: jobId }
      })
      
      if (statusResponse.data?.success) {
        const jobStatus = statusResponse.data.data.result
        
        // Update progress from backend
        if (currentJob.value) {
          currentJob.value.progress = jobStatus.progress || 0
          currentJob.value.status = jobStatus.message || jobStatus.status || 'processing'
          currentJob.value.method = jobStatus.generation_method || 'unknown'
          currentJob.value.currentVariation = jobStatus.current_variation || null
        }
        
        if (jobStatus.status === 'completed' && jobStatus.result) {
          processGenerationResult(jobStatus.result as YouTubeThumbnailGenerationResult)
          return
        } else if (jobStatus.status === 'failed') {
          throw new Error(jobStatus.message || 'Job failed')
        } else if (jobStatus.status === 'cancelled') {
          throw new Error('Job was cancelled')
        }
      }
    } catch (pollError) {
      console.error('Error polling job status:', pollError)
      // Continue polling unless it's a fatal error
    }
  }
  
  throw new Error('Job timeout - took longer than expected')
}

const processGenerationResult = (generationResult: YouTubeThumbnailGenerationResult): void => {
  // Update final job info
  if (currentJob.value) {
    currentJob.value.method = generationResult.generation_method || 'unknown'
    currentJob.value.progress = 100
    currentJob.value.status = 'completed'
  }
  
  generatedThumbnails.value = generationResult.thumbnail_variations.map((thumb) => ({
    id: thumb.id,
    title: thumb.title,
    previewUrl: thumb.preview_url,
    fullImageUrl: thumb.image_url,
    thumbnailUrl: thumb.thumbnail_url,
    style: thumb.style,
    createdAt: thumb.created_at,
    generationMethod: thumb.generation_method || generationResult.generation_method
  }))
  
  emit('thumbnails-generated', generatedThumbnails.value)
  
  // Show success message with generation method
  const methodName = generationResult.generation_method === 'replicate' ? 'Replicate (Imagen 4 Ultra)' : 'OpenAI (GPT-4 Vision)'
  console.log(`Successfully generated ${generatedThumbnails.value.length} thumbnails using ${methodName}`)
}

const previewThumbnail = (thumbnail: YouTubeThumbnailDisplay): void => {
  // Create a simple lightbox overlay to preview the image
  const overlay = document.createElement('div')
  overlay.className = 'fixed inset-0 bg-black bg-opacity-75 flex items-center justify-center z-50 cursor-pointer'
  
  const closeOverlay = () => {
    overlay.remove()
    document.removeEventListener('keydown', handleEscape)
  }
  
  const handleEscape = (e: KeyboardEvent) => {
    if (e.key === 'Escape') closeOverlay()
  }
  
  overlay.onclick = closeOverlay
  document.addEventListener('keydown', handleEscape)
  
  const img = document.createElement('img')
  img.src = thumbnail.fullImageUrl
  img.className = 'max-w-full max-h-full object-contain'
  img.alt = thumbnail.title
  
  const container = document.createElement('div')
  container.className = 'relative max-w-4xl max-h-4xl p-4'
  container.onclick = (e) => e.stopPropagation()
  
  const closeBtn = document.createElement('button')
  closeBtn.innerHTML = '✕'
  closeBtn.className = 'absolute top-2 right-2 text-white bg-black bg-opacity-50 rounded-full w-8 h-8 flex items-center justify-center hover:bg-opacity-75'
  closeBtn.onclick = closeOverlay
  
  container.appendChild(img)
  container.appendChild(closeBtn)
  overlay.appendChild(container)
  document.body.appendChild(overlay)
}

const downloadThumbnail = async (thumbnail: YouTubeThumbnailDisplay): Promise<void> => {
  try {
    // Download the full size image
    const response = await fetch(thumbnail.fullImageUrl)
    const blob = await response.blob()
    const url = window.URL.createObjectURL(blob)
    
    const link = document.createElement('a')
    link.href = url
    link.download = `youtube-thumbnail-${thumbnail.id}.jpg`
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
    
    // Clean up the blob URL
    window.URL.revokeObjectURL(url)
  } catch (error) {
    console.error('Failed to download thumbnail:', error)
    emit('error', 'Failed to download thumbnail. Please try again.')
  }
}

const downloadAllThumbnails = async (): Promise<void> => {
  try {
    // Download all thumbnails sequentially to avoid overwhelming the browser
    for (let i = 0; i < generatedThumbnails.value.length; i++) {
      const thumbnail = generatedThumbnails.value[i]
      
      const response = await fetch(thumbnail.fullImageUrl)
      const blob = await response.blob()
      const url = window.URL.createObjectURL(blob)
      
      const link = document.createElement('a')
      link.href = url
      link.download = `youtube-thumbnail-${i + 1}-${thumbnail.id}.jpg`
      document.body.appendChild(link)
      link.click()
      document.body.removeChild(link)
      
      // Clean up the blob URL
      window.URL.revokeObjectURL(url)
      
      // Small delay between downloads
      await new Promise(resolve => setTimeout(resolve, 500))
    }
  } catch (error) {
    console.error('Failed to download thumbnails:', error)
    emit('error', 'Failed to download all thumbnails. Please try again.')
  }
}

const getProgressMessage = (): string => {
  if (!currentJob.value) return ''
  
  const progress = currentJob.value.progress || 0
  const method = currentJob.value.method
  
  if (method === 'replicate') {
    // Progress messages for Replicate (longer process)
    if (progress < 10) return 'Initializing Google Imagen 4 Ultra...'
    if (progress < 30) return 'Analyzing video content and extracting key elements...'
    if (progress < 60) return 'Generating high-quality thumbnails with AI...'
    if (progress < 90) return 'Processing and optimizing images...'
    if (progress < 100) return 'Finalizing thumbnail variations...'
    return 'Thumbnail generation complete!'
  } else if (method === 'openai') {
    // Progress messages for OpenAI (faster process)
    if (progress < 20) return 'Preparing original thumbnail for AI processing...'
    if (progress < 50) return 'Generating variations with GPT-4 Vision...'
    if (progress < 80) return 'Processing and creating multiple sizes...'
    if (progress < 100) return 'Finalizing thumbnail previews...'
    return 'Thumbnail generation complete!'
  } else {
    // Generic progress messages
    if (progress < 25) return 'Extracting key frames and analyzing video content...'
    if (progress < 50) return 'Identifying best moments and visual elements...'
    if (progress < 75) return 'Generating thumbnail designs with AI...'
    if (progress < 100) return 'Finalizing layouts and preparing previews...'
    return 'Thumbnail generation complete!'
  }
}

// Load recent thumbnails
const loadRecentThumbnails = async (): Promise<void> => {
  try {
    loadingRecentThumbnails.value = true
    errorMessage.value = ''
    
    const response = await pluginAPI.executeCommand({
      pluginId: 'youtube_thumbnail',
      command: 'get_recent_thumbnails',
      layerId: null,
      parameters: {
        limit: 10
      }
    })
    
    if (response.data?.success && response.data.data?.result?.success) {
      // Flatten generations into individual thumbnails for display
      const generations = response.data.data.result.generations || []
      const flatThumbnails: any[] = []
      
      generations.forEach((generation: any) => {
        generation.thumbnails.forEach((thumbnail: any) => {
          flatThumbnails.push({
            ...thumbnail,
            video_id: generation.video_id,
            video_title: generation.video_title,
            video_channel: generation.video_channel,
            generation_id: generation.id,
            generation_method: generation.generation_method,
            created_at: generation.created_at
          })
        })
      })
      
      recentThumbnails.value = flatThumbnails
    } else {
      throw new Error('Failed to load recent thumbnails')
    }
  } catch (error: any) {
    console.error('Error loading recent thumbnails:', error)
    errorMessage.value = 'Failed to load recent thumbnails: ' + (error.message || 'Unknown error')
  } finally {
    loadingRecentThumbnails.value = false
  }
}

// Toggle recent thumbnails view
const toggleRecentThumbnails = async (): Promise<void> => {
  showRecentThumbnails.value = !showRecentThumbnails.value
  if (showRecentThumbnails.value && recentThumbnails.value.length === 0) {
    await loadRecentThumbnails()
  }
}

// Auto-analyze on mount if props specify
if (props.autoAnalyze && props.initialVideoUrl) {
  extractVideoInfo()
}

// Cleanup
onUnmounted(() => {
  if (pollInterval) {
    clearInterval(pollInterval)
    pollInterval = null
  }
})
</script>

<style scoped>
/* Enhanced Slider Styles */
.slider {
  background: linear-gradient(to right, #a855f7 0%, #ec4899 100%);
  outline: none;
  border-radius: 15px;
  box-shadow: 0 4px 15px rgba(168, 85, 247, 0.3);
  transition: all 0.3s ease;
}

.slider:hover {
  box-shadow: 0 6px 20px rgba(168, 85, 247, 0.4);
  transform: translateY(-1px);
}

.slider::-webkit-slider-thumb {
  appearance: none;
  height: 24px;
  width: 24px;
  border-radius: 50%;
  background: linear-gradient(45deg, #a855f7, #ec4899);
  cursor: pointer;
  border: 3px solid white;
  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
  transition: all 0.2s ease;
}

.slider::-webkit-slider-thumb:hover {
  transform: scale(1.1);
  box-shadow: 0 6px 20px rgba(168, 85, 247, 0.4);
}

.slider::-moz-range-thumb {
  height: 24px;
  width: 24px;
  border-radius: 50%;
  background: linear-gradient(45deg, #a855f7, #ec4899);
  cursor: pointer;
  border: 3px solid white;
  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
  transition: all 0.2s ease;
}

.slider::-moz-range-thumb:hover {
  transform: scale(1.1);
  box-shadow: 0 6px 20px rgba(168, 85, 247, 0.4);
}

/* Smooth animations for all interactive elements */
.transition-all {
  transition-property: all;
  transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
  transition-duration: 200ms;
}

/* Custom gradient text */
.bg-clip-text {
  background-clip: text;
  -webkit-background-clip: text;
}

/* Enhanced glassmorphism effect */
.backdrop-blur-sm {
  backdrop-filter: blur(8px);
  -webkit-backdrop-filter: blur(8px);
}

.backdrop-blur-md {
  backdrop-filter: blur(12px);
  -webkit-backdrop-filter: blur(12px);
}

.backdrop-blur-lg {
  backdrop-filter: blur(16px);
  -webkit-backdrop-filter: blur(16px);
}

/* Smooth hover states for cards */
.hover\:scale-105:hover {
  --tw-scale-x: 1.05;
  --tw-scale-y: 1.05;
  transform: translate(var(--tw-translate-x), var(--tw-translate-y)) rotate(var(--tw-rotate)) skewX(var(--tw-skew-x)) skewY(var(--tw-skew-y)) scaleX(var(--tw-scale-x)) scaleY(var(--tw-scale-y));
}

/* Custom aspect ratio */
.aspect-video {
  aspect-ratio: 16 / 9;
}
</style>

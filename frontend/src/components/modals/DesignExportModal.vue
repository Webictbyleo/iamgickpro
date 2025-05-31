<template>
  <TransitionRoot as="template" :show="isOpen">
    <Dialog as="div" class="relative z-50" @close="closeModal">
      <TransitionChild
        as="template"
        enter="ease-out duration-300"
        enter-from="opacity-0"
        enter-to="opacity-100"
        leave="ease-in duration-200"
        leave-from="opacity-100"
        leave-to="opacity-0"
      >
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity backdrop-blur-sm" />
      </TransitionChild>

      <div class="fixed inset-0 z-10 overflow-y-auto">
        <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
          <TransitionChild
            as="template"
            enter="ease-out duration-300"
            enter-from="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            enter-to="opacity-100 translate-y-0 sm:scale-100"
            leave="ease-in duration-200"
            leave-from="opacity-100 translate-y-0 sm:scale-100"
            leave-to="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
          >
            <DialogPanel class="relative transform overflow-hidden rounded-3xl bg-white px-6 pb-6 pt-6 text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-3xl">
              <!-- Header -->
              <div class="flex items-center justify-between mb-6">
                <div class="flex items-center space-x-4">
                  <div class="w-12 h-12 bg-gradient-to-br from-violet-500 via-purple-500 to-pink-500 rounded-2xl flex items-center justify-center shadow-lg">
                    <ArrowDownTrayIcon class="w-6 h-6 text-white" />
                  </div>
                  <div>
                    <DialogTitle as="h3" class="text-2xl font-bold text-gray-900">
                      Export Design
                    </DialogTitle>
                    <p class="text-sm text-gray-500 mt-1 flex items-center space-x-2">
                      <span>{{ design?.title || 'Untitled Design' }}</span>
                      <span v-if="design?.hasAnimation || design?.isVideo" class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-purple-100 text-purple-700">
                        {{ design?.hasAnimation ? '🎬 Animated' : '🎥 Video' }}
                      </span>
                    </p>
                  </div>
                </div>
                <button
                  @click="closeModal"
                  class="text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-full p-2 transition-all duration-200"
                >
                  <XMarkIcon class="w-6 h-6" />
                </button>
              </div>

              <!-- Design Preview -->
              <div class="mb-8">
                <div class="bg-gradient-to-br from-gray-50 to-gray-100 rounded-2xl p-6 border border-gray-200/50 shadow-inner">
                  <div class="flex items-center space-x-6">
                    <div class="relative w-24 h-24 bg-white rounded-xl shadow-lg border border-gray-200/50 overflow-hidden group">
                      <img
                        v-if="design?.thumbnail"
                        :src="design.thumbnail"
                        :alt="design.title"
                        class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                      />
                      <div v-else class="w-full h-full bg-gradient-to-br from-gray-100 to-gray-200 flex items-center justify-center">
                        <PaintBrushIcon class="w-10 h-10 text-gray-400" />
                      </div>
                      <!-- Animation/Video overlay indicator -->
                      <div v-if="design?.hasAnimation || design?.isVideo" class="absolute bottom-1 right-1 w-6 h-6 bg-purple-600 rounded-full flex items-center justify-center shadow-lg">
                        <span class="text-white text-xs">{{ design?.hasAnimation ? '🎬' : '🎥' }}</span>
                      </div>
                    </div>
                    <div class="flex-1">
                      <h4 class="text-lg font-bold text-gray-900 mb-1">{{ design?.title || 'Untitled Design' }}</h4>
                      <p class="text-sm text-gray-600 mb-3 line-clamp-2">{{ design?.description || 'No description available' }}</p>
                      <div class="flex items-center space-x-6 text-xs text-gray-500">
                        <div class="flex items-center space-x-1">
                          <span class="w-1.5 h-1.5 bg-gray-400 rounded-full"></span>
                          <span>{{ formatDate(design?.created_at || new Date().toISOString()) }}</span>
                        </div>
                        <div class="flex items-center space-x-1">
                          <span class="w-1.5 h-1.5 bg-gray-400 rounded-full"></span>
                          <span>by {{ design?.author || 'Unknown Author' }}</span>
                        </div>
                        <div v-if="design?.duration" class="flex items-center space-x-1">
                          <span class="w-1.5 h-1.5 bg-gray-400 rounded-full"></span>
                          <span>{{ formatDuration(design.duration) }}</span>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Export Tip -->
              <div class="mb-8 bg-gradient-to-r from-amber-50 to-orange-50 border border-amber-200 rounded-2xl p-4 shadow-sm">
                <div class="flex items-start space-x-3">
                  <div class="w-8 h-8 bg-amber-100 rounded-lg flex items-center justify-center flex-shrink-0">
                    <span class="text-amber-600 text-lg">💡</span>
                  </div>
                  <div>
                    <h4 class="text-sm font-bold text-amber-900 mb-1">Export Tip</h4>
                    <p class="text-sm text-amber-800">{{ getExportTip() }}</p>
                  </div>
                </div>
              </div>

              <!-- Export Options -->
              <div class="space-y-8">
                <!-- Format Selection -->
                <div>
                  <div class="flex items-center justify-between mb-4">
                    <label class="block text-lg font-bold text-gray-900">Export Format</label>
                    <span class="text-sm text-gray-500">Choose your preferred format</span>
                  </div>
                  <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                    <button
                      v-for="format in exportFormats"
                      :key="format.type"
                      @click="selectedFormat = format.type"
                      :disabled="!isFormatAvailable(format.type)"
                      :class="[
                        selectedFormat === format.type
                          ? 'border-violet-500 bg-gradient-to-br from-violet-50 to-purple-50 text-violet-700 shadow-lg scale-105'
                          : isFormatAvailable(format.type)
                          ? 'border-gray-200 hover:border-violet-300 hover:shadow-md text-gray-700 hover:scale-102'
                          : 'border-gray-100 bg-gray-50 text-gray-400 cursor-not-allowed opacity-60',
                        'relative flex flex-col items-center p-5 border-2 rounded-2xl transition-all duration-300 transform',
                        isFormatAvailable(format.type) ? 'hover:shadow-lg' : ''
                      ]"
                    >
                      <span class="text-3xl mb-3 transform transition-transform duration-200 hover:scale-110">{{ format.icon }}</span>
                      <span class="font-bold text-sm mb-1">{{ format.label }}</span>
                      <span class="text-xs text-center leading-tight">{{ format.description }}</span>
                      
                      <!-- Animation/Video Required Badge -->
                      <div
                        v-if="!isFormatAvailable(format.type) && ['gif', 'mp4'].includes(format.type)"
                        class="absolute -top-2 -right-2 bg-gradient-to-r from-amber-400 to-orange-400 text-white text-xs px-2 py-1 rounded-full font-bold shadow-lg animate-pulse"
                      >
                        {{ format.type === 'gif' ? 'Animation' : 'Video' }} Required
                      </div>
                      
                      <!-- Selected indicator -->
                      <div
                        v-if="selectedFormat === format.type"
                        class="absolute -top-3 -right-3 w-8 h-8 bg-gradient-to-br from-violet-500 to-purple-600 rounded-full flex items-center justify-center shadow-lg border-2 border-white"
                      >
                        <CheckIcon class="w-4 h-4 text-white" />
                      </div>
                      
                      <!-- Hover glow effect -->
                      <div
                        v-if="isFormatAvailable(format.type)"
                        class="absolute inset-0 rounded-2xl bg-gradient-to-br from-violet-500/0 to-purple-500/0 group-hover:from-violet-500/5 group-hover:to-purple-500/5 transition-all duration-300 pointer-events-none"
                      ></div>
                    </button>
                  </div>
                </div>

                <!-- Quality/Size Settings -->
                <div v-if="['png', 'jpg'].includes(selectedFormat)">
                  <div class="flex items-center justify-between mb-4">
                    <label class="block text-lg font-bold text-gray-900">Quality & Dimensions</label>
                    <span class="text-sm text-gray-500">Customize output settings</span>
                  </div>
                  <div class="bg-gradient-to-br from-gray-50 to-gray-100 rounded-2xl p-6 border border-gray-200/50">
                    <div class="space-y-6">
                      <!-- Quality Preset -->
                      <div>
                        <label class="block text-sm font-bold text-gray-700 mb-3">Quality Preset</label>
                        <div class="grid grid-cols-3 gap-3">
                          <button
                            v-for="preset in [
                              { value: 'web' as const, label: 'Web', desc: '72 DPI', icon: '🌐' },
                              { value: 'print' as const, label: 'Print', desc: '300 DPI', icon: '🖨️' },
                              { value: 'high' as const, label: 'High', desc: '600 DPI', icon: '⭐' }
                            ]"
                            :key="preset.value"
                            @click="exportSettings.quality = preset.value"
                            :class="[
                              exportSettings.quality === preset.value
                                ? 'border-violet-500 bg-violet-50 text-violet-700'
                                : 'border-gray-200 hover:border-violet-300 text-gray-700',
                              'relative flex flex-col items-center p-4 border-2 rounded-xl transition-all duration-200 hover:shadow-md'
                            ]"
                          >
                            <span class="text-2xl mb-2">{{ preset.icon }}</span>
                            <span class="font-bold text-sm">{{ preset.label }}</span>
                            <span class="text-xs text-gray-500">{{ preset.desc }}</span>
                            <!-- Selected indicator -->
                            <div
                              v-if="exportSettings.quality === preset.value"
                              class="absolute -top-2 -right-2 w-6 h-6 bg-violet-500 rounded-full flex items-center justify-center"
                            >
                              <CheckIcon class="w-3 h-3 text-white" />
                            </div>
                          </button>
                        </div>
                      </div>

                      <!-- Custom Dimensions -->
                      <div>
                        <div class="flex items-center justify-between mb-3">
                          <label class="block text-sm font-bold text-gray-700">Custom Dimensions</label>
                          <div class="flex items-center space-x-2">
                            <!-- Maintain Aspect Ratio Toggle -->
                            <label class="flex items-center space-x-2 cursor-pointer">
                              <input
                                v-model="exportSettings.maintainAspectRatio"
                                type="checkbox"
                                class="sr-only peer"
                              />
                              <div class="relative w-9 h-5 bg-gray-200 peer-focus:outline-none peer-focus:ring-3 peer-focus:ring-violet-300/50 rounded-full peer peer-checked:after:translate-x-4 peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-violet-600"></div>
                              <span class="text-xs font-medium text-gray-600">Lock ratio</span>
                              <div :class="[
                                exportSettings.maintainAspectRatio ? 'text-violet-600' : 'text-gray-400',
                                'transition-colors duration-200'
                              ]">
                                🔗
                              </div>
                            </label>
                          </div>
                        </div>
                        
                        <!-- Preset Dimensions -->
                        <div class="mb-4">
                          <div class="flex flex-wrap gap-2">
                            <button
                              v-for="preset in [
                                { name: 'HD', width: 1920, height: 1080 },
                                { name: '4K', width: 3840, height: 2160 },
                                { name: 'Instagram Post', width: 1080, height: 1080 },
                                { name: 'Instagram Story', width: 1080, height: 1920 },
                                { name: 'Facebook Cover', width: 1200, height: 630 },
                                { name: 'Twitter Header', width: 1500, height: 500 }
                              ]"
                              :key="preset.name"
                              @click="setDimensions(preset.width, preset.height)"
                              :class="[
                                exportSettings.width === preset.width && exportSettings.height === preset.height
                                  ? 'bg-violet-100 text-violet-700 border-violet-300'
                                  : 'bg-white text-gray-600 border-gray-200 hover:border-violet-300 hover:bg-violet-50',
                                'px-3 py-1.5 text-xs font-medium border rounded-lg transition-all duration-200 hover:shadow-sm'
                              ]"
                            >
                              {{ preset.name }}
                              <span class="text-gray-400 ml-1">{{ preset.width }}×{{ preset.height }}</span>
                            </button>
                          </div>
                        </div>
                        
                        <!-- Dimension Inputs -->
                        <div class="grid grid-cols-2 gap-3">
                          <div class="space-y-2">
                            <label class="flex items-center justify-between text-xs font-medium text-gray-600">
                              <span>Width</span>
                              <span v-if="exportSettings.width" class="text-violet-600 font-mono">{{ exportSettings.width.toLocaleString() }}px</span>
                            </label>
                            <div class="relative group">
                              <input
                                ref="widthInput"
                                v-model.number="exportSettings.width"
                                type="number"
                                min="1"
                                max="10000"
                                step="1"
                                @input="onWidthChange"
                                @blur="validateDimension('width')"
                                class="block w-full pl-4 pr-12 py-3 border border-gray-300 rounded-xl text-sm font-medium focus:outline-none focus:ring-2 focus:ring-violet-500/20 focus:border-violet-500 bg-white shadow-sm transition-all duration-200 group-hover:border-gray-400"
                                placeholder="1920"
                              />
                              <div class="absolute right-3 top-1/2 transform -translate-y-1/2 flex items-center space-x-1">
                                <span class="text-xs text-gray-400 font-medium">px</span>
                              </div>
                              <!-- Validation indicator -->
                              <div v-if="dimensionErrors.width" class="absolute -bottom-5 left-0 text-xs text-red-500">
                                {{ dimensionErrors.width }}
                              </div>
                            </div>
                          </div>
                          
                          <div class="space-y-2">
                            <label class="flex items-center justify-between text-xs font-medium text-gray-600">
                              <span>Height</span>
                              <span v-if="exportSettings.height" class="text-violet-600 font-mono">{{ exportSettings.height.toLocaleString() }}px</span>
                            </label>
                            <div class="relative group">
                              <input
                                ref="heightInput"
                                v-model.number="exportSettings.height"
                                type="number"
                                min="1"
                                max="10000"
                                step="1"
                                @input="onHeightChange"
                                @blur="validateDimension('height')"
                                class="block w-full pl-4 pr-12 py-3 border border-gray-300 rounded-xl text-sm font-medium focus:outline-none focus:ring-2 focus:ring-violet-500/20 focus:border-violet-500 bg-white shadow-sm transition-all duration-200 group-hover:border-gray-400"
                                placeholder="1080"
                              />
                              <div class="absolute right-3 top-1/2 transform -translate-y-1/2 flex items-center space-x-1">
                                <span class="text-xs text-gray-400 font-medium">px</span>
                              </div>
                              <!-- Validation indicator -->
                              <div v-if="dimensionErrors.height" class="absolute -bottom-5 left-0 text-xs text-red-500">
                                {{ dimensionErrors.height }}
                              </div>
                            </div>
                          </div>
                        </div>
                        
                        <!-- Aspect Ratio Info -->
                        <div v-if="exportSettings.maintainAspectRatio" class="mt-3 p-3 bg-violet-50 rounded-lg border border-violet-200">
                          <div class="flex items-center space-x-2 text-xs">
                            <span class="text-violet-600">🔗</span>
                            <span class="text-violet-700 font-medium">
                              Aspect ratio locked: {{ aspectRatioDisplay }}
                            </span>
                            <span class="text-violet-600">
                              ({{ (exportSettings.width / exportSettings.height).toFixed(2) }}:1)
                            </span>
                          </div>
                        </div>
                    </div>
                  </div>
                </div>

                <!-- Background Options -->
                <div v-if="['png', 'jpg'].includes(selectedFormat)">
                  <div class="flex items-center justify-between mb-4">
                    <label class="block text-lg font-bold text-gray-900">Background</label>
                    <span class="text-sm text-gray-500">Choose background style</span>
                  </div>
                  <div class="bg-gradient-to-br from-gray-50 to-gray-100 rounded-2xl p-6 border border-gray-200/50">
                    <div class="grid grid-cols-3 gap-4">
                      <button
                        v-for="bg in backgroundOptions"
                        :key="bg.type"
                        @click="exportSettings.background = bg.type"
                        :class="[
                          exportSettings.background === bg.type
                            ? 'border-violet-500 bg-violet-50 shadow-lg scale-105'
                            : 'border-gray-200 hover:border-violet-300 hover:shadow-md',
                          'relative flex flex-col items-center p-5 border-2 rounded-xl transition-all duration-300 transform bg-white'
                        ]"
                      >
                        <div :class="bg.preview" class="w-12 h-12 rounded-lg mb-3 shadow-inner border border-gray-200/50"></div>
                        <span class="text-sm font-bold text-gray-900">{{ bg.label }}</span>
                        <span v-if="bg.type === 'transparent'" class="text-xs text-gray-500 mt-1">Best for overlays</span>
                        <span v-if="bg.type === 'white'" class="text-xs text-gray-500 mt-1">Clean & minimal</span>
                        <span v-if="bg.type === 'custom'" class="text-xs text-gray-500 mt-1">Your own color</span>
                        
                        <!-- Selected indicator -->
                        <div
                          v-if="exportSettings.background === bg.type"
                          class="absolute -top-2 -right-2 w-7 h-7 bg-gradient-to-br from-violet-500 to-purple-600 rounded-full flex items-center justify-center shadow-lg border-2 border-white"
                        >
                          <CheckIcon class="w-3 h-3 text-white" />
                        </div>
                      </button>
                    </div>
                    
                    <!-- Custom Background Color Picker -->
                    <div v-if="exportSettings.background === 'custom'" class="mt-6 p-4 bg-white rounded-xl border border-gray-200 shadow-sm">
                      <label class="block text-sm font-bold text-gray-700 mb-3">Custom Background Color</label>
                      <div class="flex items-center space-x-4">
                        <input
                          v-model="exportSettings.customBackground"
                          type="color"
                          class="w-12 h-12 border-2 border-gray-300 rounded-xl cursor-pointer hover:border-violet-400 transition-colors duration-200"
                        />
                        <div class="flex-1">
                          <input
                            v-model="exportSettings.customBackground"
                            type="text"
                            placeholder="#FFFFFF"
                            class="block w-full px-4 py-3 border border-gray-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-violet-500/20 focus:border-violet-500 bg-white shadow-sm transition-all duration-200"
                          />
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Animation/Video Settings -->
                <div v-if="['gif', 'mp4'].includes(selectedFormat) && (design?.hasAnimation || design?.isVideo)">
                  <div class="flex items-center justify-between mb-4">
                    <label class="block text-lg font-bold text-gray-900">
                      {{ selectedFormat === 'gif' ? '🎬 Animation Settings' : '🎥 Video Settings' }}
                    </label>
                    <span class="text-sm text-gray-500">Optimize for {{ selectedFormat === 'gif' ? 'animation' : 'video' }}</span>
                  </div>
                  <div class="bg-gradient-to-br from-blue-50 to-indigo-50 rounded-2xl p-6 border border-blue-200/50">
                    <div class="space-y-6">
                      <!-- Duration Info -->
                      <div v-if="design?.duration" class="bg-white border border-blue-200/50 rounded-xl p-4 shadow-sm">
                        <div class="flex items-center space-x-3">
                          <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-xl flex items-center justify-center shadow-md">
                            <span class="text-white text-lg">⏱️</span>
                          </div>
                          <div>
                            <p class="text-sm font-bold text-blue-900">
                              {{ selectedFormat === 'gif' ? 'Animation' : 'Video' }} Duration
                            </p>
                            <p class="text-xs text-blue-700">{{ formatDuration(design.duration) }} total</p>
                          </div>
                        </div>
                      </div>

                      <!-- GIF Settings -->
                      <div v-if="selectedFormat === 'gif'" class="grid grid-cols-2 gap-6">
                        <div class="space-y-3">
                          <label class="block text-sm font-bold text-gray-700">Frame Rate</label>
                          <div class="space-y-2">
                            <div
                              v-for="fps in [
                                { value: 12, label: '12 FPS', desc: 'Smaller file size', icon: '💾' },
                                { value: 24, label: '24 FPS', desc: 'Smooth motion', icon: '✨' },
                                { value: 30, label: '30 FPS', desc: 'High quality', icon: '⭐' }
                              ]"
                              :key="fps.value"
                              @click="exportSettings.frameRate = fps.value"
                              :class="[
                                exportSettings.frameRate === fps.value
                                  ? 'border-purple-500 bg-purple-50'
                                  : 'border-gray-200 hover:border-purple-300',
                                'flex items-center p-3 border-2 rounded-xl cursor-pointer transition-all duration-200 bg-white'
                              ]"
                            >
                              <span class="mr-3">{{ fps.icon }}</span>
                              <div class="flex-1">
                                <div class="font-medium text-sm">{{ fps.label }}</div>
                                <div class="text-xs text-gray-500">{{ fps.desc }}</div>
                              </div>
                              <div
                                v-if="exportSettings.frameRate === fps.value"
                                class="w-5 h-5 bg-purple-500 rounded-full flex items-center justify-center"
                              >
                                <CheckIcon class="w-3 h-3 text-white" />
                              </div>
                            </div>
                          </div>
                        </div>
                        <div class="space-y-3">
                          <label class="block text-sm font-bold text-gray-700">Loop Behavior</label>
                          <div class="space-y-2">
                            <div
                              v-for="loop in [
                                { value: 'infinite', label: 'Infinite Loop', desc: 'Continuous play', icon: '🔄' },
                                { value: '1', label: 'Play Once', desc: 'Single playthrough', icon: '▶️' },
                                { value: '3', label: '3 Times', desc: 'Triple play', icon: '3️⃣' }
                              ]"
                              :key="loop.value"
                              @click="exportSettings.loopCount = loop.value"
                              :class="[
                                exportSettings.loopCount === loop.value
                                  ? 'border-purple-500 bg-purple-50'
                                  : 'border-gray-200 hover:border-purple-300',
                                'flex items-center p-3 border-2 rounded-xl cursor-pointer transition-all duration-200 bg-white'
                              ]"
                            >
                              <span class="mr-3">{{ loop.icon }}</span>
                              <div class="flex-1">
                                <div class="font-medium text-sm">{{ loop.label }}</div>
                                <div class="text-xs text-gray-500">{{ loop.desc }}</div>
                              </div>
                              <div
                                v-if="exportSettings.loopCount === loop.value"
                                class="w-5 h-5 bg-purple-500 rounded-full flex items-center justify-center"
                              >
                                <CheckIcon class="w-3 h-3 text-white" />
                              </div>
                            </div>
                          </div>
                        </div>
                      </div>

                      <!-- MP4 Settings -->
                      <div v-if="selectedFormat === 'mp4'" class="grid grid-cols-2 gap-6">
                        <div class="space-y-3">
                          <label class="block text-sm font-bold text-gray-700">Video Quality</label>
                          <div class="space-y-2">
                            <div
                              v-for="quality in [
                                { value: '720p', label: '720p HD', desc: 'Good quality', icon: '📱' },
                                { value: '1080p', label: '1080p Full HD', desc: 'High quality', icon: '💻' },
                                { value: '1440p', label: '1440p 2K', desc: 'Premium quality', icon: '🖥️' }
                              ]"
                              :key="quality.value"
                              @click="exportSettings.videoQuality = quality.value"
                              :class="[
                                exportSettings.videoQuality === quality.value
                                  ? 'border-indigo-500 bg-indigo-50'
                                  : 'border-gray-200 hover:border-indigo-300',
                                'flex items-center p-3 border-2 rounded-xl cursor-pointer transition-all duration-200 bg-white'
                              ]"
                            >
                              <span class="mr-3">{{ quality.icon }}</span>
                              <div class="flex-1">
                                <div class="font-medium text-sm">{{ quality.label }}</div>
                                <div class="text-xs text-gray-500">{{ quality.desc }}</div>
                              </div>
                              <div
                                v-if="exportSettings.videoQuality === quality.value"
                                class="w-5 h-5 bg-indigo-500 rounded-full flex items-center justify-center"
                              >
                                <CheckIcon class="w-3 h-3 text-white" />
                              </div>
                            </div>
                          </div>
                        </div>
                        <div class="space-y-3">
                          <label class="block text-sm font-bold text-gray-700">Frame Rate</label>
                          <div class="space-y-2">
                            <div
                              v-for="fps in [
                                { value: 24, label: '24 FPS', desc: 'Cinema standard', icon: '🎬' },
                                { value: 30, label: '30 FPS', desc: 'Web standard', icon: '🌐' },
                                { value: 60, label: '60 FPS', desc: 'Ultra smooth', icon: '⚡' }
                              ]"
                              :key="fps.value"
                              @click="exportSettings.frameRate = fps.value"
                              :class="[
                                exportSettings.frameRate === fps.value
                                  ? 'border-indigo-500 bg-indigo-50'
                                  : 'border-gray-200 hover:border-indigo-300',
                                'flex items-center p-3 border-2 rounded-xl cursor-pointer transition-all duration-200 bg-white'
                              ]"
                            >
                              <span class="mr-3">{{ fps.icon }}</span>
                              <div class="flex-1">
                                <div class="font-medium text-sm">{{ fps.label }}</div>
                                <div class="text-xs text-gray-500">{{ fps.desc }}</div>
                              </div>
                              <div
                                v-if="exportSettings.frameRate === fps.value"
                                class="w-5 h-5 bg-indigo-500 rounded-full flex items-center justify-center"
                              >
                                <CheckIcon class="w-3 h-3 text-white" />
                              </div>
                            </div>
                          </div>
                        </div>
                      </div>

                      <!-- Compression Settings -->
                      <div class="bg-white rounded-xl p-5 border border-gray-200/50 shadow-sm">
                        <div class="space-y-4">
                          <div class="flex items-center justify-between">
                            <label class="text-sm font-bold text-gray-700">
                              {{ selectedFormat === 'gif' ? 'File Size Optimization' : 'Video Compression' }}
                            </label>
                            <div class="flex items-center space-x-2">
                              <span class="text-sm font-bold text-violet-600">{{ exportSettings.compression || 80 }}%</span>
                              <div class="w-12 h-6 bg-gradient-to-r from-red-400 via-yellow-400 to-green-400 rounded-full relative">
                                <div 
                                  class="absolute top-0.5 w-5 h-5 bg-white rounded-full shadow-sm border border-gray-200 transition-all duration-200"
                                  :style="{ left: `${((exportSettings.compression || 80) - 10) / 90 * (3 - 1.25)}rem` }"
                                ></div>
                              </div>
                            </div>
                          </div>
                          <input
                            v-model.number="exportSettings.compression"
                            type="range"
                            min="10"
                            max="100"
                            step="10"
                            class="w-full h-3 bg-gray-200 rounded-lg appearance-none cursor-pointer slider"
                          />
                          <div class="flex justify-between text-xs text-gray-500">
                            <span class="flex items-center space-x-1">
                              <span>💾</span>
                              <span>Smaller file</span>
                            </span>
                            <span class="flex items-center space-x-1">
                              <span>⭐</span>
                              <span>Better quality</span>
                            </span>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Export Progress -->
                <div v-if="isExporting" class="bg-gradient-to-br from-blue-50 to-indigo-50 border border-blue-200 rounded-2xl p-6 shadow-lg">
                  <div class="flex items-center space-x-4 mb-4">
                    <div class="relative">
                      <div class="w-12 h-12 border-3 border-blue-200 border-t-blue-600 rounded-full animate-spin"></div>
                      <div class="absolute inset-0 flex items-center justify-center">
                        <ArrowDownTrayIcon class="w-5 h-5 text-blue-600" />
                      </div>
                    </div>
                    <div>
                      <p class="text-lg font-bold text-blue-900">Exporting your design...</p>
                      <p class="text-sm text-blue-700">{{ exportProgress }}% complete • Please wait</p>
                    </div>
                  </div>
                  <div class="space-y-2">
                    <div class="flex justify-between text-sm">
                      <span class="text-blue-700">Progress</span>
                      <span class="font-bold text-blue-900">{{ exportProgress }}%</span>
                    </div>
                    <div class="w-full bg-white rounded-full overflow-hidden shadow-inner">
                      <div
                        class="h-3 bg-gradient-to-r from-blue-500 via-indigo-500 to-purple-500 transition-all duration-500 ease-out rounded-full relative overflow-hidden"
                        :style="{ width: `${exportProgress}%` }"
                      >
                        <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/30 to-transparent animate-pulse"></div>
                      </div>
                    </div>
                  </div>
                  <div class="mt-4 p-3 bg-white/70 rounded-xl border border-blue-200/50">
                    <div class="flex items-center space-x-2 text-sm text-blue-800">
                      <span>💡</span>
                      <span>Tip: {{ selectedFormat.toUpperCase() }} exports maintain high quality for {{ selectedFormat === 'gif' ? 'animations' : selectedFormat === 'mp4' ? 'videos' : 'images' }}</span>
                    </div>
                  </div>
                </div>

                <!-- Export Success -->
                <div v-if="exportComplete" class="bg-gradient-to-br from-green-50 to-emerald-50 border border-green-200 rounded-2xl p-6 shadow-lg">
                  <div class="flex items-center space-x-4 mb-4">
                    <div class="w-12 h-12 bg-gradient-to-br from-green-500 to-emerald-600 rounded-full flex items-center justify-center shadow-lg">
                      <CheckIcon class="w-6 h-6 text-white" />
                    </div>
                    <div>
                      <p class="text-lg font-bold text-green-900">Export completed successfully! 🎉</p>
                      <p class="text-sm text-green-700">Your {{ selectedFormat.toUpperCase() }} file is ready for download</p>
                    </div>
                  </div>
                  <div class="bg-white/70 rounded-xl p-4 border border-green-200/50">
                    <div class="grid grid-cols-2 gap-4 text-sm">
                      <div>
                        <span class="text-green-700 font-medium">Format:</span>
                        <span class="ml-2 text-green-900 font-bold">{{ selectedFormat.toUpperCase() }}</span>
                      </div>
                      <div>
                        <span class="text-green-700 font-medium">Quality:</span>
                        <span class="ml-2 text-green-900 font-bold capitalize">{{ exportSettings.quality }}</span>
                      </div>
                      <div>
                        <span class="text-green-700 font-medium">Dimensions:</span>
                        <span class="ml-2 text-green-900 font-bold">{{ exportSettings.width }}×{{ exportSettings.height }}px</span>
                      </div>
                      <div v-if="['gif', 'mp4'].includes(selectedFormat)">
                        <span class="text-green-700 font-medium">{{ selectedFormat === 'gif' ? 'Frame Rate:' : 'Quality:' }}</span>
                        <span class="ml-2 text-green-900 font-bold">{{ selectedFormat === 'gif' ? `${exportSettings.frameRate} FPS` : exportSettings.videoQuality }}</span>
                      </div>
                    </div>
                  </div>                  </div>
                </div>
              </div>

              <!-- Actions -->
              <div class="flex items-center justify-end space-x-3 mt-8 pt-6 border-t border-gray-200">
                <button
                  @click="closeModal"
                  class="px-6 py-3 text-sm font-semibold text-gray-700 bg-white border-2 border-gray-300 rounded-xl hover:bg-gray-50 hover:border-gray-400 focus:outline-none focus:ring-4 focus:ring-gray-200 transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed"
                  :disabled="isExporting"
                >
                  {{ exportComplete ? 'Close' : 'Cancel' }}
                </button>
                
                <button
                  v-if="exportComplete"
                  @click="downloadExport"
                  class="px-8 py-3 text-sm font-bold text-white bg-gradient-to-r from-green-600 via-emerald-600 to-green-700 rounded-xl hover:from-green-700 hover:via-emerald-700 hover:to-green-800 focus:outline-none focus:ring-4 focus:ring-green-300 transition-all duration-200 transform hover:scale-105 hover:shadow-lg flex items-center space-x-2"
                >
                  <ArrowDownTrayIcon class="w-5 h-5" />
                  <span>Download {{ selectedFormat.toUpperCase() }}</span>
                </button>
                
                <button
                  v-else
                  @click="startExport"
                  :disabled="isExporting"
                  class="px-8 py-3 text-sm font-bold text-white bg-gradient-to-r from-violet-600 via-purple-600 to-indigo-600 rounded-xl hover:from-violet-700 hover:via-purple-700 hover:to-indigo-700 focus:outline-none focus:ring-4 focus:ring-violet-300 transition-all duration-200 transform hover:scale-105 hover:shadow-lg flex items-center space-x-2 disabled:opacity-50 disabled:cursor-not-allowed disabled:transform-none disabled:shadow-none"
                >
                  <ArrowDownTrayIcon class="w-5 h-5" />
                  <span>{{ isExporting ? 'Exporting...' : `Export as ${selectedFormat.toUpperCase()}` }}</span>
                </button>
              </div>
            </DialogPanel>
          </TransitionChild>
        </div>
      </div>
    </Dialog>
  </TransitionRoot>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import {
  Dialog,
  DialogPanel,
  DialogTitle,
  TransitionChild,
  TransitionRoot,
} from '@headlessui/vue'
import {
  XMarkIcon,
  ArrowDownTrayIcon,
  PaintBrushIcon,
  CheckIcon
} from '@heroicons/vue/24/outline'

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
  hasAnimation?: boolean
  isVideo?: boolean
  duration?: number // in seconds for videos/animations
  width?: number
  height?: number
  exportStatus?: 'pending' | 'processing' | 'completed' | 'failed'
}

interface ExportSettings {
  quality: 'web' | 'print' | 'high'
  width: number
  height: number
  maintainAspectRatio: boolean
  background: 'transparent' | 'white' | 'custom'
  customBackground?: string
  // Animation/Video settings
  frameRate?: number
  loopCount?: string | number
  videoQuality?: string
  compression?: number
}

interface ExportFormat {
  type: string
  label: string
  description: string
  icon: string
}

interface BackgroundOption {
  type: 'transparent' | 'white' | 'custom'
  label: string
  preview: string
}

interface Props {
  isOpen: boolean
  design: SearchResult | null
}

interface Emits {
  close: []
  exported: [url: string, filename: string]
}

const props = defineProps<Props>()
const emit = defineEmits<Emits>()

// Export state
const selectedFormat = ref('png')
const isExporting = ref(false)
const exportProgress = ref(0)
const exportComplete = ref(false)
const exportUrl = ref('')

// Export settings
const exportSettings = ref<ExportSettings>({
  quality: 'web',
  width: 1920,
  height: 1080,
  maintainAspectRatio: true,
  background: 'transparent',
  frameRate: 24,
  loopCount: 'infinite',
  videoQuality: '1080p',
  compression: 80
})

// Template refs
const widthInput = ref<HTMLInputElement>()
const heightInput = ref<HTMLInputElement>()

// Dimension validation
const dimensionErrors = ref({
  width: '',
  height: ''
})

// Flag to prevent infinite loops in watchers
const isUpdatingDimensions = ref(false)

// Export formats
const exportFormats = computed<ExportFormat[]>(() => {
  const baseFormats: ExportFormat[] = [
    { type: 'png', label: 'PNG', description: 'Transparent', icon: '🖼️' },
    { type: 'jpg', label: 'JPG', description: 'Compressed', icon: '📷' },
    { type: 'svg', label: 'SVG', description: 'Vector', icon: '📐' },
    { type: 'pdf', label: 'PDF', description: 'Document', icon: '📄' }
  ]

  // Add animation formats if the design has animation or is a video
  if (props.design?.hasAnimation || props.design?.isVideo) {
    baseFormats.push(
      { type: 'gif', label: 'GIF', description: 'Animated', icon: '🎬' },
      { type: 'mp4', label: 'MP4', description: 'Video', icon: '🎥' }
    )
  }

  return baseFormats
})

// Background options
const backgroundOptions: BackgroundOption[] = [
  { type: 'transparent', label: 'Transparent', preview: 'bg-gradient-to-br from-gray-100 to-gray-200 opacity-50' },
  { type: 'white', label: 'White', preview: 'bg-white border border-gray-200' },
  { type: 'custom', label: 'Custom', preview: 'bg-gradient-to-br from-violet-400 to-purple-500' }
]

// Computed properties for dimension inputs
const aspectRatioDisplay = computed(() => {
  const width = exportSettings.value.width
  const height = exportSettings.value.height
  
  if (!width || !height) return '16:9'
  
  // Find common aspect ratios
  const ratio = width / height
  if (Math.abs(ratio - 16/9) < 0.01) return '16:9'
  if (Math.abs(ratio - 4/3) < 0.01) return '4:3'
  if (Math.abs(ratio - 1) < 0.01) return '1:1'
  if (Math.abs(ratio - 9/16) < 0.01) return '9:16'
  if (Math.abs(ratio - 3/2) < 0.01) return '3:2'
  if (Math.abs(ratio - 21/9) < 0.01) return '21:9'
  
  // For other ratios, show simplified fraction
  const gcd = (a: number, b: number): number => b === 0 ? a : gcd(b, a % b)
  const divisor = gcd(width, height)
  return `${width / divisor}:${height / divisor}`
})

// Methods
const isFormatAvailable = (format: string): boolean => {
  // Basic formats are always available
  if (['png', 'jpg', 'svg', 'pdf'].includes(format)) {
    return true
  }
  
  // Animation formats (GIF, MP4) are only available for designs with animation or video
  if (['gif', 'mp4'].includes(format)) {
    return !!(props.design?.hasAnimation || props.design?.isVideo)
  }
  
  return false
}

// Dimension management methods
const setDimensions = (width: number, height: number) => {
  isUpdatingDimensions.value = true
  exportSettings.value.width = width
  exportSettings.value.height = height
  clearDimensionErrors()
  setTimeout(() => {
    isUpdatingDimensions.value = false
  }, 100)
}

const validateDimension = (type: 'width' | 'height') => {
  const value = exportSettings.value[type]
  dimensionErrors.value[type] = ''
  
  if (!value || value < 1) {
    dimensionErrors.value[type] = 'Must be at least 1px'
  } else if (value > 10000) {
    dimensionErrors.value[type] = 'Maximum 10,000px'
  } else if (!Number.isInteger(value)) {
    dimensionErrors.value[type] = 'Must be a whole number'
  }
}

const clearDimensionErrors = () => {
  dimensionErrors.value.width = ''
  dimensionErrors.value.height = ''
}

const onWidthChange = () => {
  if (isUpdatingDimensions.value) return
  
  clearDimensionErrors()
  const width = exportSettings.value.width
  
  if (exportSettings.value.maintainAspectRatio && width > 0) {
    isUpdatingDimensions.value = true
    const aspectRatio = getAspectRatio()
    exportSettings.value.height = Math.round(width / aspectRatio)
    setTimeout(() => {
      isUpdatingDimensions.value = false
    }, 100)
  }
}

const onHeightChange = () => {
  if (isUpdatingDimensions.value) return
  
  clearDimensionErrors()
  const height = exportSettings.value.height
  
  if (exportSettings.value.maintainAspectRatio && height > 0) {
    isUpdatingDimensions.value = true
    const aspectRatio = getAspectRatio()
    exportSettings.value.width = Math.round(height * aspectRatio)
    setTimeout(() => {
      isUpdatingDimensions.value = false
    }, 100)
  }
}

const getAspectRatio = (): number => {
  // Default to 16:9 if no design dimensions available
  if (props.design?.width && props.design?.height) {
    return props.design.width / props.design.height
  }
  return 16 / 9 // Default aspect ratio
}

const closeModal = () => {
  if (!isExporting.value) {
    resetModal()
    emit('close')
  }
}

const resetModal = () => {
  selectedFormat.value = 'png'
  isExporting.value = false
  exportProgress.value = 0
  exportComplete.value = false
  exportUrl.value = ''
  exportSettings.value = {
    quality: 'web',
    width: 1920,
    height: 1080,
    maintainAspectRatio: true,
    background: 'transparent',
    frameRate: 24,
    loopCount: 'infinite',
    videoQuality: '1080p',
    compression: 80
  }
}

const startExport = async () => {
  if (!props.design) return

  isExporting.value = true
  exportProgress.value = 0
  exportComplete.value = false

  try {
    // Simulate export progress
    const progressInterval = setInterval(() => {
      exportProgress.value += Math.random() * 15
      if (exportProgress.value >= 100) {
        exportProgress.value = 100
        clearInterval(progressInterval)
        
        // Complete export
        setTimeout(() => {
          isExporting.value = false
          exportComplete.value = true
          
          // Generate mock download URL
          const timestamp = Date.now()
          const filename = `${props.design?.title || 'design'}-${timestamp}.${selectedFormat.value}`
          exportUrl.value = `https://example.com/exports/${filename}`
          
          emit('exported', exportUrl.value, filename)
        }, 500)
      }
    }, 200)

    // In a real implementation, you would call the export API here
    // const response = await exportAPI.exportDesign(props.design.id, {
    //   format: selectedFormat.value,
    //   settings: exportSettings.value
    // })
    
  } catch (error) {
    console.error('Export failed:', error)
    isExporting.value = false
    // Handle error state
  }
}

const downloadExport = () => {
  if (exportUrl.value) {
    // Create temporary download link
    const link = document.createElement('a')
    link.href = exportUrl.value
    link.download = `${props.design?.title || 'design'}.${selectedFormat.value}`
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
    
    // Close modal after download starts
    setTimeout(() => {
      closeModal()
    }, 1000)
  }
}

const formatDate = (dateString: string): string => {
  const date = new Date(dateString)
  return new Intl.DateTimeFormat('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  }).format(date)
}

const formatDuration = (seconds: number): string => {
  const minutes = Math.floor(seconds / 60)
  const remainingSeconds = seconds % 60
  
  if (minutes > 0) {
    return `${minutes}m ${remainingSeconds}s`
  }
  return `${remainingSeconds}s`
}

const getExportTip = (): string => {
  const tips = {
    png: "PNG format maintains transparency and is perfect for designs with transparent backgrounds.",
    jpg: "JPEG format offers smaller file sizes and is ideal for designs with complex colors or photos.",
    svg: "SVG format creates scalable vector graphics that remain crisp at any size.",
    pdf: "PDF format is perfect for print materials and maintains high quality at any resolution.",
    gif: "GIF format creates animated graphics with support for transparency and looping.",
    mp4: "MP4 format provides high-quality video exports with excellent compression and wide compatibility."
  }
  
  return tips[selectedFormat.value as keyof typeof tips] || "Choose the best format for your intended use case."
}

// Watch for modal close to reset state
watch(() => props.isOpen, (newValue) => {
  if (!newValue) {
    setTimeout(() => {
      resetModal()
      clearDimensionErrors()
    }, 300) // Wait for close animation
  }
})

// Watch aspect ratio setting - when enabled, sync dimensions
watch(() => exportSettings.value.maintainAspectRatio, (maintain) => {
  if (maintain && !isUpdatingDimensions.value) {
    const aspectRatio = getAspectRatio()
    const width = exportSettings.value.width
    if (width > 0) {
      isUpdatingDimensions.value = true
      exportSettings.value.height = Math.round(width / aspectRatio)
      setTimeout(() => {
        isUpdatingDimensions.value = false
      }, 100)
    }
  }
})
</script>

<style scoped>
/* Custom scrollbar for the modal */
::-webkit-scrollbar {
  width: 6px;
}

::-webkit-scrollbar-track {
  background: #f1f5f9;
  border-radius: 8px;
}

::-webkit-scrollbar-thumb {
  background: linear-gradient(180deg, #8b5cf6, #7c3aed);
  border-radius: 8px;
}

::-webkit-scrollbar-thumb:hover {
  background: linear-gradient(180deg, #7c3aed, #6d28d9);
}

/* Custom range slider styles */
.slider::-webkit-slider-thumb {
  appearance: none;
  height: 20px;
  width: 20px;
  border-radius: 50%;
  background: linear-gradient(135deg, #8b5cf6, #7c3aed);
  cursor: pointer;
  box-shadow: 0 4px 12px rgba(139, 92, 246, 0.4);
  border: 2px solid white;
  transition: all 0.2s ease;
}

.slider::-webkit-slider-thumb:hover {
  transform: scale(1.1);
  box-shadow: 0 6px 16px rgba(139, 92, 246, 0.6);
}

.slider::-webkit-slider-track {
  height: 8px;
  border-radius: 8px;
  background: linear-gradient(90deg, #ef4444, #f59e0b, #10b981);
  cursor: pointer;
}

.slider::-moz-range-thumb {
  height: 20px;
  width: 20px;
  border-radius: 50%;
  background: linear-gradient(135deg, #8b5cf6, #7c3aed);
  cursor: pointer;
  border: 2px solid white;
  box-shadow: 0 4px 12px rgba(139, 92, 246, 0.4);
}

/* Hover effects and animations */
.group:hover .group-hover\:scale-110 {
  transform: scale(1.1);
}

.hover\:scale-102:hover {
  transform: scale(1.02);
}

/* Text truncation */
.line-clamp-2 {
  overflow: hidden;
  display: -webkit-box;
  -webkit-box-orient: vertical;
  -webkit-line-clamp: 2;
  line-clamp: 2;
}

/* Border width utility */
.border-3 {
  border-width: 3px;
}

/* Custom background animations */
@keyframes shimmer {
  0% {
    transform: translateX(-100%);
  }
  100% {
    transform: translateX(100%);
  }
}

.bg-shimmer {
  position: relative;
  overflow: hidden;
}

.bg-shimmer::before {
  content: '';
  position: absolute;
  top: 0;
  left: -100%;
  width: 100%;
  height: 100%;
  background: linear-gradient(
    90deg,
    transparent,
    rgba(255, 255, 255, 0.2),
    transparent
  );
  animation: shimmer 2s infinite;
}
</style>

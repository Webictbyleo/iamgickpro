<template>
  <TransitionRoot appear :show="show" as="template">
    <Dialog as="div" class="relative z-[100]" @close="close">
      <TransitionChild
        as="template"
        enter="duration-300 ease-out"
        enter-from="opacity-0"
        enter-to="opacity-100"
        leave="duration-200 ease-in"
        leave-from="opacity-100"
        leave-to="opacity-0"
      >
        <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" />
      </TransitionChild>

      <div class="fixed inset-0 overflow-y-auto">
        <div class="flex min-h-full items-center justify-center p-4 text-center">
          <TransitionChild
            as="template"
            enter="duration-300 ease-out"
            enter-from="opacity-0 scale-95"
            enter-to="opacity-100 scale-100"
            leave="duration-200 ease-in"
            leave-from="opacity-100 scale-100"
            leave-to="opacity-0 scale-95"
          >
            <DialogPanel class="w-full max-w-4xl transform overflow-hidden rounded-2xl bg-white dark:bg-gray-800 text-left align-middle shadow-xl border border-gray-200 dark:border-gray-700 transition-all">
              <!-- Header -->
              <div class="relative px-6 py-6 bg-gray-50 dark:bg-gray-900 border-b border-gray-200 dark:border-gray-700">
                <DialogTitle as="h3" class="text-2xl font-bold text-gray-900 dark:text-white mb-1">
                  Choose Canvas Size
                </DialogTitle>
                <p class="text-gray-600 dark:text-gray-300">
                  Select a format or enter custom dimensions
                </p>
                <button
                  @click="close"
                  class="absolute top-4 right-4 p-2 text-gray-400 dark:text-gray-500 hover:text-gray-600 dark:hover:text-gray-300 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                >
                  <component :is="icons.close" class="w-5 h-5" />
                </button>
              </div>

              <!-- Popular Sizes -->
              <div class="px-6 py-6">
                <div class="flex items-center justify-between mb-4">
                  <h4 class="text-lg font-semibold text-gray-900 dark:text-white">Popular Formats</h4>
                  <div class="flex gap-1">
                    <button 
                      v-for="category in categories.slice(0, 4)" 
                      :key="category"
                      @click="selectedCategory = category"
                      :class="[
                        'px-3 py-1.5 rounded-md text-xs font-medium transition-colors',
                        selectedCategory === category 
                          ? 'bg-primary-600 dark:bg-primary-700 text-white' 
                          : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600'
                      ]"
                    >
                      {{ category }}
                    </button>
                  </div>
                </div>
                
                <div v-if="filteredSizes.length === 0" class="text-center py-8">
                  <div class="mx-auto w-12 h-12 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center mb-3">
                    <component :is="icons.search" class="w-5 h-5 text-gray-400 dark:text-gray-500" />
                  </div>
                  <h3 class="text-sm font-medium text-gray-900 dark:text-white mb-1">No formats found</h3>
                  <p class="text-xs text-gray-500 dark:text-gray-400 mb-3">
                    Try selecting a different category
                  </p>
                  <button 
                    @click="selectedCategory = 'All'"
                    class="px-3 py-1.5 bg-primary-600 dark:bg-primary-700 text-white rounded-md text-xs font-medium hover:bg-primary-700 dark:hover:bg-primary-800 transition-colors"
                  >
                    Show All
                  </button>
                </div>
                
                <div v-else class="grid grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-3 mb-6">
                  <button
                    v-for="size in filteredSizes"
                    :key="size.id"
                    @click="selectSize(size)"
                    :title="`Create ${size.name} (${size.width}×${size.height}px)`"
                    class="group relative p-3 border border-gray-200 dark:border-gray-600 rounded-lg hover:border-primary-300 dark:hover:border-primary-500 hover:bg-primary-50/50 dark:hover:bg-primary-900/20 transition-all duration-200 text-left hover:shadow-md focus:outline-none focus:ring-2 focus:ring-primary-500/50 dark:focus:ring-primary-400/50"
                  >
                    <div class="flex flex-col items-center space-y-2">
                      <div class="relative">
                        <div 
                          class="border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 shadow-sm group-hover:border-primary-400 dark:group-hover:border-primary-500 transition-colors"
                          :style="getPreviewStyle(size)"
                        >
                        </div>
                        <div class="absolute -top-1 -right-1 w-4 h-4 bg-primary-500 dark:bg-primary-600 rounded-full opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                          <component :is="icons.check" class="w-2 h-2 text-white" />
                        </div>
                      </div>
                      <div class="text-center">
                        <h5 class="font-medium text-gray-900 dark:text-white text-xs group-hover:text-primary-700 dark:group-hover:text-primary-300 transition-colors">{{ size.name }}</h5>
                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ size.width }}×{{ size.height }}</p>
                      </div>
                    </div>
                  </button>
                </div>

                <!-- Custom Size -->
                <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
                  <div class="flex items-center space-x-3 mb-6">
                    <div class="w-8 h-8 bg-primary-100 dark:bg-primary-900/30 rounded-lg flex items-center justify-center">
                      <component :is="icons.cog" class="w-4 h-4 text-primary-600 dark:text-primary-400" />
                    </div>
                    <div>
                      <h4 class="text-lg font-semibold text-gray-900 dark:text-white">Custom Size</h4>
                      <p class="text-sm text-gray-500 dark:text-gray-400">Enter your preferred dimensions</p>
                    </div>
                  </div>
                  
                  <div class="bg-gray-50 dark:bg-gray-900/50 rounded-xl p-6 border border-gray-200 dark:border-gray-700">
                    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-end">
                      <!-- Width Input -->
                      <div class="lg:col-span-3">
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">
                          Width
                          <span class="text-xs font-normal text-gray-500 dark:text-gray-400 ml-1">(px)</span>
                        </label>
                        <div class="relative">
                          <input
                            v-model.number="customWidth"
                            type="number"
                            min="1"
                            max="10000"
                            class="w-full px-4 py-3 pr-10 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white rounded-lg focus:ring-2 focus:ring-primary-500/50 dark:focus:ring-primary-400/50 focus:border-primary-500 dark:focus:border-primary-400 transition-all text-center font-medium placeholder:text-gray-400 dark:placeholder:text-gray-500"
                            placeholder="800"
                            @focus="(event) => (event.target as HTMLInputElement)?.select()"
                          />
                          <span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs text-gray-400 dark:text-gray-500 font-medium">px</span>
                        </div>
                      </div>
                      
                      <!-- Height Input -->
                      <div class="lg:col-span-3">
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">
                          Height
                          <span class="text-xs font-normal text-gray-500 dark:text-gray-400 ml-1">(px)</span>
                        </label>
                        <div class="relative">
                          <input
                            v-model.number="customHeight"
                            type="number"
                            min="1"
                            max="10000"
                            class="w-full px-4 py-3 pr-10 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white rounded-lg focus:ring-2 focus:ring-primary-500/50 dark:focus:ring-primary-400/50 focus:border-primary-500 dark:focus:border-primary-400 transition-all text-center font-medium placeholder:text-gray-400 dark:placeholder:text-gray-500"
                            placeholder="600"
                            @focus="(event) => (event.target as HTMLInputElement)?.select()"
                          />
                          <span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs text-gray-400 dark:text-gray-500 font-medium">px</span>
                        </div>
                      </div>
                      
                      <!-- Preview -->
                      <div class="lg:col-span-3 flex items-center justify-center">
                        <div v-if="customWidth && customHeight" class="text-center p-4 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-600 shadow-sm">
                          <div class="mb-3">
                            <div 
                              class="border-2 border-primary-300 dark:border-primary-500 bg-gradient-to-br from-primary-50 to-primary-100 dark:from-primary-900/20 dark:to-primary-900/40 mx-auto shadow-sm"
                              :style="getCustomPreviewStyle()"
                            ></div>
                          </div>
                          <p class="text-xs text-gray-500 dark:text-gray-400 font-medium">Preview</p>
                        </div>
                        <div v-else class="text-center p-4">
                          <div class="w-10 h-10 bg-gray-100 dark:bg-gray-700 rounded-lg flex items-center justify-center mx-auto mb-2">
                            <component :is="icons.photo" class="w-5 h-5 text-gray-400 dark:text-gray-500" />
                          </div>
                          <p class="text-xs text-gray-500 dark:text-gray-400">Enter dimensions</p>
                        </div>
                      </div>
                      
                      <!-- Create Button -->
                      <div class="lg:col-span-3">
                        <button
                          @click="selectCustomSize"
                          :disabled="!isCustomSizeValid"
                          :class="[
                            'w-full px-6 py-3 rounded-lg font-semibold transition-all duration-200 transform',
                            isCustomSizeValid 
                              ? 'bg-primary-600 dark:bg-primary-700 text-white hover:bg-primary-700 dark:hover:bg-primary-800 hover:scale-105 shadow-md hover:shadow-lg focus:ring-2 focus:ring-primary-500/50 dark:focus:ring-primary-400/50' 
                              : 'bg-gray-300 dark:bg-gray-600 text-gray-500 dark:text-gray-400 cursor-not-allowed opacity-60'
                          ]"
                        >
                          <span class="flex items-center justify-center space-x-2">
                            <component :is="icons.plus" class="w-4 h-4" />
                            <span>Create Custom</span>
                          </span>
                        </button>
                      </div>
                    </div>
                    
                    <!-- Enhanced Info Panel -->
                    <div v-if="customWidth && customHeight" class="mt-6 p-4 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm">
                      <div class="flex items-start space-x-3">
                        <div class="w-8 h-8 bg-gray-100 dark:bg-gray-700 rounded-lg flex items-center justify-center flex-shrink-0">
                          <component :is="icons.information" class="w-4 h-4 text-gray-600 dark:text-gray-400" />
                        </div>
                        <div class="flex-1 min-w-0">
                          <div class="flex items-center justify-between mb-2">
                            <p class="text-gray-900 dark:text-white font-bold text-lg">{{ customWidth }} × {{ customHeight }}px</p>
                            <span class="inline-flex items-center px-2.5 py-1 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-xs font-semibold rounded-full">
                              {{ customWidth > customHeight ? 'Landscape' : customWidth < customHeight ? 'Portrait' : 'Square' }}
                            </span>
                          </div>
                          <div class="flex items-center space-x-4 text-sm">
                            <span class="text-gray-700 dark:text-gray-300">
                              <span class="font-medium">Ratio:</span> {{ aspectRatio }}
                            </span>
                            <span class="text-gray-600 dark:text-gray-400">
                              <span class="font-medium">Format:</span> {{ getFormatDescription(customWidth, customHeight) }}
                            </span>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </DialogPanel>
          </TransitionChild>
        </div>
      </div>
    </Dialog>
  </TransitionRoot>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { Dialog, DialogPanel, DialogTitle, TransitionRoot, TransitionChild } from '@headlessui/vue'
import { useIcons } from '@/composables/useIcons'
import { useRouter } from 'vue-router'

interface DesignSize {
  id: string
  name: string
  width: number
  height: number
  category: string
}

interface Props {
  show: boolean
}

interface Emits {
  close: []
}

defineProps<Props>()
const emit = defineEmits<Emits>()

const router = useRouter()
const icons = useIcons()

const customWidth = ref<number>()
const customHeight = ref<number>()
const selectedCategory = ref<string>('All')

// Available categories
const categories = computed(() => {
  const allCategories = ['All', ...new Set(popularSizes.map(size => size.category))]
  return allCategories
})

// Filtered sizes based on selected category
const filteredSizes = computed(() => {
  if (selectedCategory.value === 'All') {
    return popularSizes
  }
  return popularSizes.filter(size => size.category === selectedCategory.value)
})

// Popular design sizes
const popularSizes: DesignSize[] = [
    { id: 'youtube-thumbnail', name: 'YouTube Thumbnail', width: 1280, height: 720, category: 'Video' },
  { id: 'social-post', name: 'Social Media Post', width: 1080, height: 1080, category: 'Social Media' },
  { id: 'instagram-story', name: 'Instagram Story', width: 1080, height: 1920, category: 'Social Media' },
  { id: 'facebook-cover', name: 'Facebook Cover', width: 1640, height: 859, category: 'Social Media' },
  { id: 'poster', name: 'Poster', width: 2480, height: 3508, category: 'Print' },
  { id: 'flyer', name: 'Flyer', width: 2480, height: 3508, category: 'Print' },
  { id: 'business-card', name: 'Business Card', width: 1050, height: 600, category: 'Print' },
  { id: 'presentation', name: 'Presentation', width: 1920, height: 1080, category: 'Document' },
  { id: 'web-banner', name: 'Web Banner', width: 1200, height: 400, category: 'Web' },
  { id: 'email-header', name: 'Email Header', width: 600, height: 200, category: 'Email' },
  { id: 'linkedin-post', name: 'LinkedIn Post', width: 1200, height: 627, category: 'Social Media' },
  { id: 'pinterest-pin', name: 'Pinterest Pin', width: 1000, height: 1500, category: 'Social Media' }
]

const isCustomSizeValid = computed(() => {
  return customWidth.value && customHeight.value && 
         customWidth.value > 0 && customHeight.value > 0 &&
         customWidth.value <= 10000 && customHeight.value <= 10000
})

const aspectRatio = computed(() => {
  if (!customWidth.value || !customHeight.value) return ''
  const ratio = customWidth.value / customHeight.value
  return ratio.toFixed(2)
})

const getFormatDescription = (width: number, height: number) => {
  const ratio = width / height
  
  // Common format detection
  if (Math.abs(ratio - 16/9) < 0.1) return 'Widescreen (16:9)'
  if (Math.abs(ratio - 4/3) < 0.1) return 'Standard (4:3)'
  if (Math.abs(ratio - 1) < 0.05) return 'Square (1:1)'
  if (Math.abs(ratio - 3/4) < 0.1) return 'Portrait (3:4)'
  if (Math.abs(ratio - 9/16) < 0.1) return 'Mobile Portrait'
  if (Math.abs(ratio - 21/9) < 0.1) return 'Ultra-wide'
  if (ratio > 3) return 'Banner'
  if (ratio < 0.5) return 'Vertical Banner'
  
  return 'Custom Ratio'
}

const getPreviewStyle = (size: DesignSize) => {
  // Calculate aspect ratio and scale to fit in a 48x36px container
  const maxWidth = 48
  const maxHeight = 36
  const aspectRatio = size.width / size.height
  
  let width = maxWidth
  let height = maxWidth / aspectRatio
  
  if (height > maxHeight) {
    height = maxHeight
    width = maxHeight * aspectRatio
  }
  
  return {
    width: `${width}px`,
    height: `${height}px`,
    borderRadius: '3px'
  }
}

const getCustomPreviewStyle = () => {
  if (!customWidth.value || !customHeight.value) return {}
  
  // Calculate aspect ratio and scale to fit in a 50x40px container for better visibility
  const maxWidth = 50
  const maxHeight = 40
  const aspectRatio = customWidth.value / customHeight.value
  
  let width = maxWidth
  let height = maxWidth / aspectRatio
  
  if (height > maxHeight) {
    height = maxHeight
    width = maxHeight * aspectRatio
  }
  
  return {
    width: `${Math.round(width)}px`,
    height: `${Math.round(height)}px`,
    borderRadius: '4px'
  }
}

const selectSize = (size: DesignSize) => {
  // Navigate to editor with size parameters
  router.push({
    name: 'Editor',
    query: {
      width: size.width.toString(),
      height: size.height.toString(),
      name: size.name
    }
  })
  close()
}

const selectCustomSize = () => {
  if (!isCustomSizeValid.value) return
  
  // Navigate to editor with custom size parameters
  router.push({
    name: 'Editor',
    query: {
      width: customWidth.value!.toString(),
      height: customHeight.value!.toString(),
      name: 'Custom Design'
    }
  })
  close()
}

const close = () => {
  emit('close')
}
</script>

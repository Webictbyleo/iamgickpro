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
            <DialogPanel class="w-full max-w-4xl transform overflow-hidden rounded-2xl bg-white text-left align-middle shadow-xl transition-all">
              <!-- Header -->
              <div class="relative px-6 py-6 bg-gray-50 border-b border-gray-200">
                <DialogTitle as="h3" class="text-2xl font-bold text-gray-900 mb-1">
                  Choose Canvas Size
                </DialogTitle>
                <p class="text-gray-600">
                  Select a format or enter custom dimensions
                </p>
                <button
                  @click="close"
                  class="absolute top-4 right-4 p-2 text-gray-400 hover:text-gray-600 rounded-lg hover:bg-gray-100 transition-colors"
                >
                  <component :is="icons.close" class="w-5 h-5" />
                </button>
              </div>

              <!-- Popular Sizes -->
              <div class="px-6 py-6">
                <div class="flex items-center justify-between mb-4">
                  <h4 class="text-lg font-semibold text-gray-900">Popular Formats</h4>
                  <div class="flex gap-1">
                    <button 
                      v-for="category in categories.slice(0, 4)" 
                      :key="category"
                      @click="selectedCategory = category"
                      :class="[
                        'px-3 py-1.5 rounded-md text-xs font-medium transition-colors',
                        selectedCategory === category 
                          ? 'bg-violet-600 text-white' 
                          : 'bg-gray-100 text-gray-600 hover:bg-gray-200'
                      ]"
                    >
                      {{ category }}
                    </button>
                  </div>
                </div>
                
                <div v-if="filteredSizes.length === 0" class="text-center py-8">
                  <div class="mx-auto w-12 h-12 bg-gray-100 rounded-full flex items-center justify-center mb-3">
                    <component :is="icons.search" class="w-5 h-5 text-gray-400" />
                  </div>
                  <h3 class="text-sm font-medium text-gray-900 mb-1">No formats found</h3>
                  <p class="text-xs text-gray-500 mb-3">
                    Try selecting a different category
                  </p>
                  <button 
                    @click="selectedCategory = 'All'"
                    class="px-3 py-1.5 bg-violet-600 text-white rounded-md text-xs font-medium hover:bg-violet-700 transition-colors"
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
                    class="group relative p-3 border border-gray-200 rounded-lg hover:border-violet-300 hover:bg-violet-50/50 transition-all duration-200 text-left hover:shadow-md focus:outline-none focus:ring-2 focus:ring-violet-500/50"
                  >
                    <div class="flex flex-col items-center space-y-2">
                      <div class="relative">
                        <div 
                          class="border border-gray-300 bg-white shadow-sm group-hover:border-violet-400 transition-colors"
                          :style="getPreviewStyle(size)"
                        >
                        </div>
                        <div class="absolute -top-1 -right-1 w-4 h-4 bg-violet-500 rounded-full opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                          <component :is="icons.check" class="w-2 h-2 text-white" />
                        </div>
                      </div>
                      <div class="text-center">
                        <h5 class="font-medium text-gray-900 text-xs group-hover:text-violet-700 transition-colors">{{ size.name }}</h5>
                        <p class="text-xs text-gray-500">{{ size.width }}×{{ size.height }}</p>
                      </div>
                    </div>
                  </button>
                </div>

                <!-- Custom Size -->
                <div class="border-t border-gray-200 pt-4">
                  <div class="flex items-center space-x-2 mb-4">
                    <component :is="icons.cog" class="w-4 h-4 text-violet-600" />
                    <h4 class="text-lg font-semibold text-gray-900">Custom Size</h4>
                  </div>
                  
                  <div class="bg-gray-50 rounded-lg p-4">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
                      <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Width</label>
                        <div class="relative">
                          <input
                            v-model.number="customWidth"
                            type="number"
                            min="1"
                            max="10000"
                            class="w-full px-3 py-2 pr-8 border border-gray-300 rounded-md focus:ring-1 focus:ring-violet-500 focus:border-violet-400 transition-colors text-center"
                            placeholder="800"
                            @focus="(event) => (event.target as HTMLInputElement)?.select()"
                          />
                          <span class="absolute right-2 top-1/2 -translate-y-1/2 text-xs text-gray-400">px</span>
                        </div>
                      </div>
                      
                      <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Height</label>
                        <div class="relative">
                          <input
                            v-model.number="customHeight"
                            type="number"
                            min="1"
                            max="10000"
                            class="w-full px-3 py-2 pr-8 border border-gray-300 rounded-md focus:ring-1 focus:ring-violet-500 focus:border-violet-400 transition-colors text-center"
                            placeholder="600"
                            @focus="(event) => (event.target as HTMLInputElement)?.select()"
                          />
                          <span class="absolute right-2 top-1/2 -translate-y-1/2 text-xs text-gray-400">px</span>
                        </div>
                      </div>
                      
                      <div class="flex items-center justify-center">
                        <div v-if="customWidth && customHeight" class="text-center p-2 bg-white rounded-md border border-gray-200">
                          <div 
                            class="border border-violet-300 bg-violet-50 mx-auto mb-1"
                            :style="getCustomPreviewStyle()"
                          ></div>
                          <p class="text-xs text-gray-500">Preview</p>
                        </div>
                      </div>
                      
                      <div>
                        <button
                          @click="selectCustomSize"
                          :disabled="!isCustomSizeValid"
                          :class="[
                            'w-full px-4 py-2 rounded-md font-medium transition-colors',
                            isCustomSizeValid 
                              ? 'bg-violet-600 text-white hover:bg-violet-700' 
                              : 'bg-gray-300 text-gray-500 cursor-not-allowed'
                          ]"
                        >
                          Create Custom
                        </button>
                      </div>
                    </div>
                    
                    <div v-if="customWidth && customHeight" class="mt-3 p-3 bg-violet-50 border border-violet-200 rounded-md">
                      <div class="flex items-start space-x-2">
                        <component :is="icons.information" class="w-4 h-4 text-violet-600 flex-shrink-0 mt-0.5" />
                        <div class="text-sm">
                          <p class="text-violet-800 font-medium">{{ customWidth }} × {{ customHeight }}px</p>
                          <p class="text-violet-600 text-xs">
                            {{ customWidth > customHeight ? 'Landscape' : customWidth < customHeight ? 'Portrait' : 'Square' }} • Ratio: {{ aspectRatio }}
                          </p>
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
  { id: 'social-post', name: 'Social Media Post', width: 1080, height: 1080, category: 'Social Media' },
  { id: 'instagram-story', name: 'Instagram Story', width: 1080, height: 1920, category: 'Social Media' },
  { id: 'facebook-cover', name: 'Facebook Cover', width: 1640, height: 859, category: 'Social Media' },
  { id: 'youtube-thumbnail', name: 'YouTube Thumbnail', width: 1280, height: 720, category: 'Video' },
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
  
  // Calculate aspect ratio and scale to fit in a 40x30px container
  const maxWidth = 40
  const maxHeight = 30
  const aspectRatio = customWidth.value / customHeight.value
  
  let width = maxWidth
  let height = maxWidth / aspectRatio
  
  if (height > maxHeight) {
    height = maxHeight
    width = maxHeight * aspectRatio
  }
  
  return {
    width: `${width}px`,
    height: `${height}px`,
    borderRadius: '2px'
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

<template>
  <div class="relative">
    <Listbox v-model="selectedFont" @update:model-value="handleFontSelection">
      <div class="relative">
        <!-- Font Selection Button -->
        <ListboxButton 
          class="group relative w-full cursor-pointer rounded-lg bg-white py-2 pl-4 pr-10 text-left border border-gray-300 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all duration-200 hover:border-gray-400 hover:shadow-sm"
        >
          <div class="flex items-center space-x-3">
            <!-- Loading Indicator -->
            <div class="flex-shrink-0 w-4 h-4 flex items-center justify-center">
              <div v-if="isCurrentFontLoading" class="w-3 h-3 border border-purple-300 border-t-purple-600 rounded-full animate-spin"></div>
              <CheckIcon v-else-if="isCurrentFontLoaded" class="w-3 h-3 text-green-600" />
              <div v-else class="w-3 h-3 border border-gray-300 rounded-full"></div>
            </div>
            
            <!-- Font Preview -->
            <span 
              class="block truncate text-gray-900"
              :style="{ fontFamily: currentFontStack }"
            >
              {{ value || placeholder }}
            </span>
          </div>
          
          <span class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-2">
            <ChevronDownIcon class="h-5 w-5 text-gray-400" />
          </span>
        </ListboxButton>

        <!-- Dropdown -->
        <Transition
          enter-active-class="transition duration-200 ease-out"
          enter-from-class="translate-y-1 opacity-0"
          enter-to-class="translate-y-0 opacity-100"
          leave-active-class="transition duration-150 ease-in"
          leave-from-class="translate-y-0 opacity-100"
          leave-to-class="translate-y-1 opacity-0"
        >
          <ListboxOptions class="absolute z-20 mt-2 max-h-96 w-80 min-w-full rounded-xl bg-white shadow-2xl border border-gray-200 focus:outline-none overflow-hidden">
            <!-- Search -->
            <div class="sticky top-0 z-10 bg-white border-b border-gray-100 p-3">
              <div class="relative">
                <MagnifyingGlassIcon class="absolute left-3 top-1/2 transform -translate-y-1/2 w-4 h-4 text-gray-400" />
                <input
                  v-model="searchQuery"
                  type="text"
                  placeholder="Search fonts..."
                  class="w-full pl-10 pr-3 py-2 text-sm border border-gray-200 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                />
              </div>
            </div>

            <!-- Font List Container -->
            <div 
              ref="scrollContainer" 
              class="max-h-80 overflow-y-auto"
              @scroll="handleScroll"
            >
              <!-- Popular Fonts -->
              <div v-if="!searchQuery && popularFonts.length > 0" class="bg-purple-50 border-b border-gray-100">
                <div class="px-4 py-2 text-xs font-semibold text-purple-700 uppercase tracking-wide">
                  ⭐ Popular Fonts
                </div>
                <ListboxOption
                  v-for="font in popularFonts"
                  :key="`popular-${font.family}`"
                  :value="font.family"
                  v-slot="{ active, selected }"
                >
                  <li 
                    :data-font-family="font.family"
                    :class="[
                      'relative cursor-pointer select-none py-3 px-4 transition-colors',
                      active ? 'bg-purple-100' : 'hover:bg-gray-50'
                    ]"
                  >
                    <FontItem 
                      :font="font" 
                      :selected="selected" 
                      :preview-text="previewText"
                    />
                  </li>
                </ListboxOption>
              </div>

              <!-- All Fonts -->
              <div v-if="displayedFonts.length > 0">
                <div v-if="!searchQuery && popularFonts.length > 0" class="px-4 py-2 text-xs font-semibold text-gray-600 uppercase tracking-wide bg-gray-50">
                  All Fonts
                </div>
                <ListboxOption
                  v-for="font in displayedFonts"
                  :key="`all-${font.family}`"
                  :value="font.family"
                  v-slot="{ active, selected }"
                >
                  <li 
                    :data-font-family="font.family"
                    :class="[
                      'relative cursor-pointer select-none py-3 px-4 border-b border-gray-50 last:border-b-0 transition-colors',
                      active ? 'bg-purple-50' : 'hover:bg-gray-50'
                    ]"
                  >
                    <FontItem 
                      :font="font" 
                      :selected="selected" 
                      :preview-text="previewText"
                    />
                  </li>
                </ListboxOption>
              </div>

              <!-- Loading More -->
              <div v-if="isLoadingMore && hasMoreFontsToLoad" class="p-4 text-center">
                <div class="w-6 h-6 border-2 border-purple-200 border-t-purple-600 rounded-full animate-spin mx-auto"></div>
                <div class="text-sm text-gray-500 mt-2">Loading more fonts...</div>
              </div>

              <!-- No Results -->
              <div v-if="searchQuery && displayedFonts.length === 0 && !isLoadingMore" class="p-8 text-center">
                <div class="text-gray-500">No fonts found matching "{{ searchQuery }}"</div>
              </div>
            </div>
          </ListboxOptions>
        </Transition>
      </div>
    </Listbox>
  </div>
</template>

<script setup lang="ts">
import { computed, ref, onMounted, watch, nextTick } from 'vue'
import { 
  Listbox, 
  ListboxButton, 
  ListboxOptions, 
  ListboxOption 
} from '@headlessui/vue'
import { 
  ChevronDownIcon, 
  CheckIcon, 
  MagnifyingGlassIcon
} from '@heroicons/vue/24/outline'
import { FontManager } from '@/services/FontManager'
import type { FontFamily } from '@/services/FontManager'
import FontItem from './FontItem.vue'

interface Props {
  value: string
  placeholder?: string
  previewText?: string
}

const props = withDefaults(defineProps<Props>(), {
  placeholder: 'Select a font',
  previewText: 'The quick brown fox jumps over the lazy dog'
})

const emit = defineEmits<{
  update: [value: string]
}>()

// State
const searchQuery = ref('')
const allFonts = ref<FontFamily[]>([])
const loadedFonts = ref(new Set<string>())
const loadingFonts = ref(new Set<string>())
const isLoadingMore = ref(false)
const scrollContainer = ref<HTMLElement | null>(null)

// Popular fonts that should be loaded first
const popularFontNames = [
  'Inter', 'Roboto', 'Open Sans', 'Lato', 'Montserrat', 'Poppins',
  'Source Sans Pro', 'Nunito', 'Raleway', 'Ubuntu'
]

// Computed
const selectedFont = computed({
  get: () => props.value,
  set: (value: string) => emit('update', value)
})

const currentFontStack = computed(() => {
  return props.value ? FontManager.getFontWithFallbacks(props.value) : 'inherit'
})

const isCurrentFontLoading = computed(() => {
  return loadingFonts.value.has(props.value)
})

const isCurrentFontLoaded = computed(() => {
  return loadedFonts.value.has(props.value) || FontManager.isFontLoaded(props.value)
})

const filteredFonts = computed(() => {
  if (!searchQuery.value.trim()) {
    return allFonts.value
  }
  
  const query = searchQuery.value.toLowerCase()
  return allFonts.value.filter(font => 
    font.family.toLowerCase().includes(query)
  )
})

const popularFonts = computed(() => {
  if (searchQuery.value.trim()) return []
  
  return allFonts.value
    .filter(font => popularFontNames.includes(font.family))
    .sort((a, b) => {
      const aIndex = popularFontNames.indexOf(a.family)
      const bIndex = popularFontNames.indexOf(b.family)
      return aIndex - bIndex
    })
    .slice(0, 6)
})

const displayedFonts = computed(() => {
  const fonts = filteredFonts.value
  
  // Remove popular fonts from main list if not searching
  if (!searchQuery.value.trim()) {
    const popularNames = new Set(popularFonts.value.map(f => f.family))
    return fonts.filter(font => !popularNames.has(font.family))
  }
  
  return fonts
})

const hasMoreFontsToLoad = computed(() => {
  const totalUnloadedFonts = displayedFonts.value.filter(font => 
    !loadedFonts.value.has(font.family) && !loadingFonts.value.has(font.family)
  ).length
  
  return totalUnloadedFonts > 0
})

// Methods
const loadFonts = async () => {
  try {
    await FontManager.initialize()
    allFonts.value = FontManager.getAvailableFonts()
    
    // Load current font if set
    if (props.value && !FontManager.isFontLoaded(props.value)) {
      await loadFont(props.value)
    }
    
    // Load popular fonts immediately
    await loadPopularFonts()
  } catch (error) {
    console.error('Failed to load fonts:', error)
  }
}

const loadPopularFonts = async () => {
  const promises = popularFonts.value
    .slice(0, 4) // Load first 4 popular fonts immediately
    .map(font => loadFont(font.family))
  
  await Promise.allSettled(promises)
}

const loadFont = async (fontFamily: string): Promise<void> => {
  if (loadedFonts.value.has(fontFamily) || loadingFonts.value.has(fontFamily)) {
    return
  }

  loadingFonts.value.add(fontFamily)
  
  try {
    await FontManager.loadFont(fontFamily, {
      weights: ['400', '700'],
      styles: ['normal'], // Only load normal style initially to reduce errors
      display: 'swap'
    })
    
    loadedFonts.value.add(fontFamily)
    
    // Update the font in allFonts
    const fontIndex = allFonts.value.findIndex(f => f.family === fontFamily)
    if (fontIndex >= 0) {
      allFonts.value[fontIndex] = {
        ...allFonts.value[fontIndex],
        loaded: true,
        loading: false,
        error: false
      }
    }
  } catch (error) {
    console.warn(`Failed to load font ${fontFamily}:`, error)
    
    // Mark as error in allFonts
    const fontIndex = allFonts.value.findIndex(f => f.family === fontFamily)
    if (fontIndex >= 0) {
      allFonts.value[fontIndex] = {
        ...allFonts.value[fontIndex],
        loaded: false,
        loading: false,
        error: true
      }
    }
  } finally {
    loadingFonts.value.delete(fontFamily)
  }
}

const handleScroll = () => {
  const container = scrollContainer.value
  if (!container || isLoadingMore.value) return

  // Load fonts that are currently visible or about to be visible
  loadVisibleFonts()

  // Check if we're near the bottom and there are more fonts to load
  if (hasMoreFontsToLoad.value) {
    const { scrollTop, scrollHeight, clientHeight } = container
    if (scrollTop + clientHeight >= scrollHeight - 100) {
      loadMoreVisibleFonts()
    }
  }
}

const loadVisibleFonts = () => {
  const container = scrollContainer.value
  if (!container) return

  const containerRect = container.getBoundingClientRect()
  const fontItems = container.querySelectorAll('li[data-font-family]')
  
  let fontsToLoad: string[] = []
  
  fontItems.forEach((item) => {
    const itemRect = item.getBoundingClientRect()
    const fontFamily = item.getAttribute('data-font-family')
    
    if (!fontFamily) return
    
    // Check if item is visible or within a buffer zone (200px above/below viewport)
    const isVisible = (
      itemRect.bottom >= containerRect.top - 200 &&
      itemRect.top <= containerRect.bottom + 200
    )
    
    if (isVisible && !loadedFonts.value.has(fontFamily) && !loadingFonts.value.has(fontFamily)) {
      fontsToLoad.push(fontFamily)
    }
  })
  
  // Limit concurrent font loading to prevent overwhelming the browser
  const maxConcurrentLoads = 3
  fontsToLoad.slice(0, maxConcurrentLoads).forEach(fontFamily => {
    loadFont(fontFamily)
  })
}

const loadMoreVisibleFonts = async () => {
  if (isLoadingMore.value || !hasMoreFontsToLoad.value) return
  
  isLoadingMore.value = true
  
  // Wait a bit for UI to update
  await new Promise(resolve => setTimeout(resolve, 100))
  
  // Load next batch of visible fonts
  loadVisibleFonts()
  
  // Wait for fonts to start loading before hiding indicator
  await new Promise(resolve => setTimeout(resolve, 200))
  
  isLoadingMore.value = false
}

const handleFontSelection = async (fontFamily: string) => {
  if (!loadedFonts.value.has(fontFamily)) {
    await loadFont(fontFamily)
  }
  emit('update', fontFamily)
}

// Watchers
watch(searchQuery, () => {
  // Load visible fonts when search changes
  nextTick(() => {
    loadVisibleFonts()
  })
})

watch(() => props.value, (newFont) => {
  if (newFont && !loadedFonts.value.has(newFont)) {
    loadFont(newFont)
  }
})

// Lifecycle
onMounted(() => {
  loadFonts()
})
</script>

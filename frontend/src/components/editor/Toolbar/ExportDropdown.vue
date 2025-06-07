<template>
  <Menu as="div" class="relative">
    <MenuButton as="template">
      <button
        class="px-6 py-2.5 bg-gradient-to-r from-violet-600 to-purple-600 hover:from-violet-700 hover:to-purple-700 text-white rounded-xl font-semibold text-sm transition-all duration-200 hover:shadow-lg hover:shadow-violet-500/25 focus:outline-none focus:ring-2 focus:ring-violet-500 focus:ring-offset-2 focus:ring-offset-gray-900 hover:scale-105 w-32 flex items-center justify-center"
      >
        <span>Export</span>
        <ChevronDownIcon class="w-4 h-4 ml-2" />
      </button>
    </MenuButton>

    <Transition
      enter-active-class="transition duration-100 ease-out"
      enter-from-class="transform scale-95 opacity-0"
      enter-to-class="transform scale-100 opacity-100"
      leave-active-class="transition duration-75 ease-in"
      leave-from-class="transform scale-100 opacity-100"
      leave-to-class="transform scale-95 opacity-0"
    >
      <MenuItems class="absolute right-0 mt-3 w-80 bg-white dark:bg-gray-900 rounded-2xl shadow-xl border border-gray-100 dark:border-gray-800 py-3 z-50 backdrop-blur-sm">
        <NestedDropdownView>
          <!-- Main View - Quick Export Options -->
          <template #main="{ goToNested }">
            <!-- Quick Export Options -->
            <div class="px-4 py-2 border-b border-gray-100 dark:border-gray-800">
              <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-3">Quick Export</p>
              <div class="grid grid-cols-2 gap-2">
                <MenuItem v-slot="{ active }">
                  <button
                    @click="handleExport('png')"
                    :class="[
                      'flex items-center p-3 rounded-xl text-sm w-full transition-all duration-200',
                      active ? 'bg-violet-50 dark:bg-violet-900/20 text-violet-700 dark:text-violet-300 shadow-sm' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800'
                    ]"
                  >
                    <div class="w-10 h-10 bg-gradient-to-br from-blue-100 to-blue-200 dark:from-blue-900/30 dark:to-blue-800/30 rounded-xl flex items-center justify-center mr-3">
                      <PhotoIcon class="w-5 h-5 text-blue-600 dark:text-blue-400" />
                    </div>
                    <div class="text-left">
                      <p class="font-semibold">PNG</p>
                      <p class="text-xs text-gray-500 dark:text-gray-400">High quality</p>
                    </div>
                  </button>
                </MenuItem>
                
                <MenuItem v-slot="{ active }">
                  <button
                    @click="handleExport('jpg')"
                    :class="[
                      'flex items-center p-3 rounded-xl text-sm w-full transition-all duration-200',
                      active ? 'bg-violet-50 dark:bg-violet-900/20 text-violet-700 dark:text-violet-300 shadow-sm' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800'
                    ]"
                  >
                    <div class="w-10 h-10 bg-gradient-to-br from-emerald-100 to-emerald-200 dark:from-emerald-900/30 dark:to-emerald-800/30 rounded-xl flex items-center justify-center mr-3">
                      <PhotoIcon class="w-5 h-5 text-emerald-600 dark:text-emerald-400" />
                    </div>
                    <div class="text-left">
                      <p class="font-semibold">JPG</p>
                      <p class="text-xs text-gray-500 dark:text-gray-400">Smaller size</p>
                    </div>
                  </button>
                </MenuItem>
              </div>
            </div>

            <!-- More Formats -->
            <div class="px-4 py-2 border-b border-gray-100 dark:border-gray-800">
              <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">More Formats</p>
              <div class="space-y-1">
                <MenuItem 
                  v-for="format in moreFormats"
                  :key="format.value"
                  v-slot="{ active }"
                >
                  <button
                    @click="handleExport(format.value)"
                    :class="[
                      'flex items-center justify-between w-full px-3 py-2 text-sm rounded-lg transition-all duration-200',
                      active ? 'bg-violet-50 dark:bg-violet-900/20 text-violet-700 dark:text-violet-300' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800'
                    ]"
                  >
                    <div class="flex items-center">
                      <component :is="format.icon" class="w-4 h-4 mr-3 text-gray-500 dark:text-gray-400" />
                      <span class="font-medium">{{ format.label }}</span>
                    </div>
                    <span class="text-xs text-gray-500 dark:text-gray-400">{{ format.extension }}</span>
                  </button>
                </MenuItem>
              </div>
            </div>

            <!-- Advanced Export -->
            <div class="px-4 py-2">
              <button
                @click.stop="goToNested"
                class="flex items-center w-full px-3 py-2 text-sm rounded-lg transition-all duration-200 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 hover:bg-violet-50 hover:dark:bg-violet-900/20 hover:text-violet-700 hover:dark:text-violet-300"
              >
                <Cog6ToothIcon class="w-4 h-4 mr-3 text-gray-500 dark:text-gray-400" />
                <span class="font-medium">Advanced Options</span>
              </button>
            </div>
          </template>

          <!-- Nested View - Advanced Export Options -->
          <template #nested="{ goBack }">
            <div class="space-y-4">
              <div>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Advanced Export</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">Customize your export settings</p>
              </div>

              <!-- Format Selection -->
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                  Format
                </label>
                <select
                  v-model="selectedFormat"
                  class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-violet-500 focus:border-transparent transition-all duration-200"
                >
                  <option value="png">PNG - High Quality</option>
                  <option value="jpg">JPG - Smaller Size</option>
                  <option value="svg">SVG - Vector</option>
                  <option value="pdf">PDF - Document</option>
                  <option value="webp">WebP - Modern</option>
                </select>
              </div>

              <!-- Quality/Scale Settings -->
              <div v-if="selectedFormat === 'jpg' || selectedFormat === 'webp'">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                  Quality ({{ exportQuality }}%)
                </label>
                <input
                  v-model.number="exportQuality"
                  type="range"
                  min="10"
                  max="100"
                  step="10"
                  class="w-full h-2 bg-gray-200 dark:bg-gray-700 rounded-lg appearance-none cursor-pointer"
                />
                <div class="flex justify-between text-xs text-gray-500 dark:text-gray-400 mt-1">
                  <span>Lower size</span>
                  <span>Higher quality</span>
                </div>
              </div>

              <div v-if="selectedFormat !== 'svg'">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                  Scale ({{ exportScale }}x)
                </label>
                <input
                  v-model.number="exportScale"
                  type="range"
                  min="0.5"
                  max="4"
                  step="0.5"
                  class="w-full h-2 bg-gray-200 dark:bg-gray-700 rounded-lg appearance-none cursor-pointer"
                />
                <div class="flex justify-between text-xs text-gray-500 dark:text-gray-400 mt-1">
                  <span>0.5x</span>
                  <span>4x</span>
                </div>
              </div>

              <!-- Background Options -->
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                  Background
                </label>
                <div class="grid grid-cols-2 gap-2">
                  <button
                    @click="exportBackground = 'transparent'"
                    :class="[
                      'p-3 rounded-lg border text-sm transition-all duration-200',
                      exportBackground === 'transparent' 
                        ? 'bg-violet-50 dark:bg-violet-900/30 border-violet-200 dark:border-violet-700 text-violet-700 dark:text-violet-300' 
                        : 'bg-gray-50 dark:bg-gray-700 border-gray-200 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-600'
                    ]"
                  >
                    Transparent
                  </button>
                  <button
                    @click="exportBackground = 'white'"
                    :class="[
                      'p-3 rounded-lg border text-sm transition-all duration-200',
                      exportBackground === 'white' 
                        ? 'bg-violet-50 dark:bg-violet-900/30 border-violet-200 dark:border-violet-700 text-violet-700 dark:text-violet-300' 
                        : 'bg-gray-50 dark:bg-gray-700 border-gray-200 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-600'
                    ]"
                  >
                    White
                  </button>
                </div>
              </div>

              <!-- Action Buttons -->
              <div class="flex space-x-3 pt-2">
                <button
                  @click="handleAdvancedExport"
                  class="flex-1 px-4 py-2 bg-gradient-to-r from-violet-600 to-purple-600 hover:from-violet-700 hover:to-purple-700 text-white rounded-lg font-medium text-sm transition-all duration-200"
                >
                  Export {{ selectedFormat.toUpperCase() }}
                </button>
                <button
                  @click="goBack"
                  class="px-4 py-2 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-lg font-medium text-sm transition-all duration-200"
                >
                  Cancel
                </button>
              </div>
            </div>
          </template>
        </NestedDropdownView>
      </MenuItems>
    </Transition>
  </Menu>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { Menu, MenuButton, MenuItems, MenuItem } from '@headlessui/vue'
import {
  ChevronDownIcon,
  PhotoIcon,
  DocumentIcon,
  CodeBracketIcon,
  FilmIcon,
  Cog6ToothIcon
} from '@heroicons/vue/24/outline'
import NestedDropdownView from '@/components/ui/NestedDropdownView.vue'

interface Props {
  compact?: boolean;
}

withDefaults(defineProps<Props>(), {
  compact: false
})

const emit = defineEmits<{
  export: [format: string, options?: any]
  'advanced-export': []
}>()

// Advanced export state
const selectedFormat = ref('png')
const exportQuality = ref(90)
const exportScale = ref(1)
const exportBackground = ref<'transparent' | 'white'>('transparent')

const moreFormats = [
  { 
    value: 'svg', 
    label: 'SVG Vector', 
    extension: '.svg',
    icon: CodeBracketIcon
  },
  { 
    value: 'pdf', 
    label: 'PDF Document', 
    extension: '.pdf',
    icon: DocumentIcon
  },
  { 
    value: 'webp', 
    label: 'WebP Modern', 
    extension: '.webp',
    icon: PhotoIcon
  },
  { 
    value: 'gif', 
    label: 'Animated GIF', 
    extension: '.gif',
    icon: FilmIcon
  }
]

const handleExport = (format: string) => {
  emit('export', format)
}

const handleAdvancedExport = () => {
  const options = {
    format: selectedFormat.value,
    quality: exportQuality.value,
    scale: exportScale.value,
    background: exportBackground.value
  }
  emit('export', selectedFormat.value, options)
}
</script>

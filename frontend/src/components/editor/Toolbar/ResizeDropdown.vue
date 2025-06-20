<template>
  <Menu as="div" class="relative">
    <MenuButton as="template">
      <button
        class="px-6 py-2.5 bg-gray-50 dark:bg-gray-700 hover:bg-gray-100 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 rounded-xl font-medium text-sm transition-all duration-200 shadow-sm hover:shadow-md focus:outline-none focus:ring-2 focus:ring-violet-500 focus:ring-offset-2 border border-gray-200 dark:border-gray-600 w-32 flex items-center justify-center"
      >
        <span>Resize</span>
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
      <MenuItems class="absolute left-0 mt-3 w-96 bg-white dark:bg-gray-800 rounded-2xl shadow-xl border border-gray-200 dark:border-gray-700 py-4 z-50">
        <NestedDropdownView>
          <!-- Main View - Size Presets -->
          <template #main="{ goToNested }">
            <!-- Quick Resize Options -->
            <div class="px-5 py-3 border-b border-gray-100 dark:border-gray-700">
              <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-4">Quick Resize</p>
              <div class="grid grid-cols-2 gap-3">
                <MenuItem 
                  v-for="size in quickSizes"
                  :key="size.name"
                  v-slot="{ active }"
                >
                  <button
                    @click="handleResize(size.width, size.height)"
                    :class="[
                      'flex flex-col items-center p-3 rounded-xl text-sm w-full transition-all duration-200',
                      active ? 'bg-violet-50 dark:bg-violet-900/30 text-violet-700 dark:text-violet-300 shadow-lg ring-1 ring-violet-200 dark:ring-violet-700' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700'
                    ]"
                  >
                    <div class="w-10 h-6 bg-gray-200 dark:bg-gray-600 rounded border-2 border-gray-300 dark:border-gray-500 mb-2 flex items-center justify-center">
                      <div :class="[
                        'rounded-sm',
                        size.aspect === 'square' ? 'w-6 h-6 bg-violet-400' : 
                        size.aspect === 'wide' ? 'w-8 h-4 bg-violet-400' : 
                        'w-4 h-8 bg-violet-400'
                      ]"></div>
                    </div>
                    <div class="text-center">
                      <p class="font-semibold text-xs text-gray-900 dark:text-gray-100">{{ size.name }}</p>
                      <p class="text-xs text-gray-500 dark:text-gray-400">{{ size.width }}×{{ size.height }}</p>
                    </div>
                  </button>
                </MenuItem>
              </div>
            </div>

            <!-- More Sizes -->
            <div class="px-5 py-3 border-b border-gray-100 dark:border-gray-700">
              <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-3">More Sizes</p>
              <div class="space-y-1">
                <MenuItem 
                  v-for="size in moreSizes"
                  :key="size.name"
                  v-slot="{ active }"
                >
                  <button
                    @click="handleResize(size.width, size.height)"
                    :class="[
                      'flex items-center justify-between w-full px-3 py-2 text-sm rounded-lg transition-all duration-200',
                      active ? 'bg-violet-50 dark:bg-violet-900/30 text-violet-700 dark:text-violet-300' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700'
                    ]"
                  >
                    <span class="font-medium text-gray-900 dark:text-gray-100">{{ size.name }}</span>
                    <span class="text-xs text-gray-500 dark:text-gray-400 font-mono">{{ size.width }}×{{ size.height }}</span>
                  </button>
                </MenuItem>
              </div>
            </div>

            <!-- Custom Size -->
            <div class="px-5 py-3">
              <button
                @click.stop="goToNested"
                class="flex items-center w-full px-3 py-2 text-sm rounded-lg transition-all duration-200 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 hover:bg-violet-50 hover:dark:bg-violet-900/30 hover:text-violet-700 hover:dark:text-violet-300"
              >
                <Cog6ToothIcon class="w-4 h-4 mr-3 text-gray-500 dark:text-gray-400" />
                <span class="font-medium text-gray-900 dark:text-gray-100">Custom Size</span>
              </button>
            </div>
          </template>

          <!-- Nested View - Custom Size Form -->
          <template #nested="{ goBack }">
            <div class="space-y-4">
              <div>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Custom Size</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">Enter your desired canvas dimensions</p>
              </div>

              <div class="grid grid-cols-2 gap-4">
                <div>
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Width
                  </label>
                  <div class="relative">
                    <input
                      v-model.number="customWidth"
                      type="number"
                      min="1"
                      max="10000"
                      class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-violet-500 focus:border-transparent transition-all duration-200"
                      placeholder="1080"
                    />
                    <span class="absolute right-3 top-2 text-sm text-gray-500 dark:text-gray-400">px</span>
                  </div>
                </div>

                <div>
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Height
                  </label>
                  <div class="relative">
                    <input
                      v-model.number="customHeight"
                      type="number"
                      min="1"
                      max="10000"
                      class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-violet-500 focus:border-transparent transition-all duration-200"
                      placeholder="1080"
                    />
                    <span class="absolute right-3 top-2 text-sm text-gray-500 dark:text-gray-400">px</span>
                  </div>
                </div>
              </div>

              <!-- Aspect Ratio Lock -->
              <div class="flex items-center space-x-3">
                <button
                  @click="toggleAspectRatioLock"
                  :class="[
                    'p-2 rounded-lg transition-all duration-200',
                    aspectRatioLocked 
                      ? 'bg-violet-100 dark:bg-violet-900/30 text-violet-600 dark:text-violet-400' 
                      : 'bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-600'
                  ]"
                  :title="aspectRatioLocked ? 'Unlock aspect ratio' : 'Lock aspect ratio'"
                >
                  <LinkIcon v-if="aspectRatioLocked" class="w-4 h-4" />
                  <LinkSlashIcon v-else class="w-4 h-4" />
                </button>
                <span class="text-sm text-gray-600 dark:text-gray-400">
                  {{ aspectRatioLocked ? 'Aspect ratio locked' : 'Aspect ratio unlocked' }}
                </span>
              </div>

              <!-- Action Buttons -->
              <div class="flex space-x-3 pt-2">
                <button
                  @click="applyCustomSize"
                  :disabled="!isValidCustomSize"
                  class="flex-1 px-4 py-2 bg-gradient-to-r from-violet-600 to-purple-600 hover:from-violet-700 hover:to-purple-700 disabled:from-gray-400 disabled:to-gray-500 text-white rounded-lg font-medium text-sm transition-all duration-200 disabled:cursor-not-allowed"
                >
                  Apply Size
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
import { ref, computed, watch } from 'vue'
import { Menu, MenuButton, MenuItems, MenuItem } from '@headlessui/vue'
import {
  ChevronDownIcon,
  Cog6ToothIcon,
  LinkIcon,
  LinkSlashIcon
} from '@heroicons/vue/24/outline'
import NestedDropdownView from '@/components/ui/NestedDropdownView.vue'
import { useDesignStore } from '@/stores/design';

const emit = defineEmits<{
  resize: [width: number, height: number]
  'custom-resize': []
}>()

const props = defineProps<{
  canvasWidth: number
  canvasHeight: number
}>()

const quickSizes = [
  { name: 'Instagram Post', width: 1080, height: 1080, aspect: 'square' },
  { name: 'Instagram Story', width: 1080, height: 1920, aspect: 'tall' },
  { name: 'Facebook Cover', width: 1200, height: 630, aspect: 'wide' },
  { name: 'YouTube Thumbnail', width: 1280, height: 720, aspect: 'wide' }
]

const moreSizes = [
  { name: 'Twitter Header', width: 1500, height: 500 },
  { name: 'LinkedIn Post', width: 1200, height: 627 },
  { name: 'Pinterest Pin', width: 1000, height: 1500 },
  { name: 'TikTok Video', width: 1080, height: 1920 },
  { name: 'A4 Document', width: 2480, height: 3508 },
  { name: 'Letter Size', width: 2550, height: 3300 }
]
const designStore = useDesignStore()
// Custom size state
const customWidth = computed({
  get: () => designStore.currentDesign?.width || props.canvasWidth,
  set: (value: number) => {
    if (designStore.currentDesign) {
      designStore.currentDesign.width = value
    }
  }
})
const customHeight = computed({
  get: () => designStore.currentDesign?.height || props.canvasHeight,
  set: (value: number) => {
    if (designStore.currentDesign) {
      designStore.currentDesign.height = value
    }
  }
})



console.log('Initial custom size:', customWidth.value, customHeight.value)
const aspectRatioLocked = ref(false)
const originalAspectRatio = ref<number>(1)

// Computed properties
const isValidCustomSize = computed(() => {
  return customWidth.value > 0 && customHeight.value > 0 && 
         customWidth.value <= 10000 && customHeight.value <= 10000
})

// Watchers for aspect ratio locking
watch(customWidth, (newWidth) => {
  if (aspectRatioLocked.value && newWidth > 0) {
    customHeight.value = Math.round(newWidth / originalAspectRatio.value)
  }
})

watch(customHeight, (newHeight) => {
  if (aspectRatioLocked.value && newHeight > 0) {
    customWidth.value = Math.round(newHeight * originalAspectRatio.value)
  }
})

const handleResize = (width: number, height: number) => {
  emit('resize', width, height)
}

const toggleAspectRatioLock = () => {
  if (!aspectRatioLocked.value && customWidth.value > 0 && customHeight.value > 0) {
    // Calculate and store the current aspect ratio
    originalAspectRatio.value = customWidth.value / customHeight.value
  }
  aspectRatioLocked.value = !aspectRatioLocked.value
}

const applyCustomSize = () => {
  if (isValidCustomSize.value) {
    emit('resize', customWidth.value, customHeight.value)
  }
}
</script>

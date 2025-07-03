<template>
  <div class="space-y-8">
    <!-- Theme Mode Selection -->
    <div class="bg-white rounded-xl border border-gray-200 p-6 dark:bg-gray-800 dark:border-gray-700">
      <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
        Theme Mode
      </h3>
      <p class="text-sm text-gray-600 dark:text-gray-400 mb-6">
        Choose your preferred theme mode. System will automatically match your device's theme.
      </p>
      
      <div class="grid grid-cols-3 gap-3">
        <button
          v-for="option in themeOptions"
          :key="option.value"
          @click="themeStore.setMode(option.value)"
          :class="[
            'relative flex flex-col items-center p-4 rounded-lg border-2 transition-all duration-200',
            themeStore.mode === option.value
              ? 'border-primary-500 bg-primary-500/10 dark:bg-primary-400/10 text-primary-700 dark:text-primary-300'
              : 'border-gray-200 hover:border-gray-300 dark:border-gray-600 dark:hover:border-gray-500 bg-white dark:bg-gray-800'
          ]"
        >
          <component :is="option.icon" class="w-6 h-6 mb-2 text-gray-600 dark:text-gray-400" />
          <span class="text-sm font-medium text-gray-900 dark:text-white">{{ option.label }}</span>
          <span class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ option.description }}</span>
          
          <!-- Active indicator -->
          <div
            v-if="themeStore.mode === option.value"
            class="absolute -top-2 -right-2 w-5 h-5 bg-primary-500 rounded-full flex items-center justify-center"
          >
            <CheckIcon class="w-3 h-3 text-white" />
          </div>
        </button>
      </div>
    </div>

    <!-- Color Scheme Selection -->
    <div class="bg-white rounded-xl border border-gray-200 p-6 dark:bg-gray-800 dark:border-gray-700">
      <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
        Color Scheme
      </h3>
      <p class="text-sm text-gray-600 dark:text-gray-400 mb-6">
        Select a color scheme that matches your style and preferences.
      </p>
      
      <div class="grid grid-cols-2 lg:grid-cols-3 gap-4">
        <button
          v-for="(preset, key) in themeStore.themePresets"
          :key="key"
          @click="themeStore.setColorScheme(key)"
          :class="[
            'relative p-4 rounded-lg border-2 transition-all duration-200 text-left',
            themeStore.activeColorScheme === key && !themeStore.customColors
              ? 'border-primary-500 bg-primary-500/10 dark:bg-primary-400/10'
              : 'border-gray-200 hover:border-gray-300 dark:border-gray-600 dark:hover:border-gray-500 bg-white dark:bg-gray-800'
          ]"
        >
          <!-- Color preview -->
          <div class="flex space-x-1 mb-3">
            <div
              class="w-4 h-4 rounded-full border border-gray-200"
              :style="{ backgroundColor: preset.primary[500] }"
            />
            <div
              class="w-4 h-4 rounded-full border border-gray-200"
              :style="{ backgroundColor: preset.secondary[500] }"
            />
            <div
              v-if="preset.accent"
              class="w-4 h-4 rounded-full border border-gray-200"
              :style="{ backgroundColor: preset.accent[500] }"
            />
          </div>
          
          <h4 class="font-medium text-gray-900 dark:text-white">{{ preset.name }}</h4>
          
          <!-- Active indicator -->
          <div
            v-if="themeStore.activeColorScheme === key && !themeStore.customColors"
            class="absolute -top-2 -right-2 w-5 h-5 bg-primary-500 rounded-full flex items-center justify-center"
          >
            <CheckIcon class="w-3 h-3 text-white" />
          </div>
        </button>

        <!-- Custom Colors Card -->
        <button
          @click="showCustomColorsModal = true"
          :class="[
            'relative p-4 rounded-lg border-2 border-dashed transition-all duration-200 text-left',
            themeStore.customColors
              ? 'border-primary-500 bg-primary-500/10 dark:bg-primary-400/10'
              : 'border-gray-300 hover:border-gray-400 dark:border-gray-600 dark:hover:border-gray-500 bg-white dark:bg-gray-800'
          ]"
        >
          <div class="flex items-center justify-center mb-3">
            <PlusIcon class="w-6 h-6 text-gray-400" />
          </div>
          <h4 class="font-medium text-gray-900 dark:text-white">Custom Colors</h4>
          <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Create your own</p>
          
          <!-- Active indicator -->
          <div
            v-if="themeStore.customColors"
            class="absolute -top-2 -right-2 w-5 h-5 bg-primary-500 rounded-full flex items-center justify-center"
          >
            <CheckIcon class="w-3 h-3 text-white" />
          </div>
        </button>
      </div>
    </div>

    <!-- Theme Preview -->
    <div class="bg-white rounded-xl border border-gray-200 p-6 dark:bg-gray-800 dark:border-gray-700">
      <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
        Theme Preview
      </h3>
      <p class="text-sm text-gray-600 dark:text-gray-400 mb-6">
        See how your selected theme looks across different UI elements.
      </p>
      
      <div class="space-y-4">
        <!-- Button previews -->
        <div class="flex flex-wrap gap-3">
          <button
            class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors"
          >
            Primary Button
          </button>
          <button
            class="px-4 py-2 bg-secondary-200 text-secondary-900 rounded-lg hover:bg-secondary-300 transition-colors dark:bg-secondary-700 dark:text-secondary-100"
          >
            Secondary Button
          </button>
          <button
            class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700"
          >
            Outline Button
          </button>
        </div>
        
        <!-- Form preview -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
              Input Field
            </label>
            <input
              type="text"
              placeholder="Enter some text..."
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent bg-white dark:border-gray-600 dark:bg-gray-700 dark:text-white"
            />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
              Select Field
            </label>
            <select class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent bg-white dark:border-gray-600 dark:bg-gray-700 dark:text-white">
              <option>Option 1</option>
              <option>Option 2</option>
            </select>
          </div>
        </div>
        
        <!-- Card preview -->
        <div class="p-4 bg-gray-50 rounded-lg border border-gray-200 dark:bg-gray-700 dark:border-gray-600">
          <h4 class="font-medium text-gray-900 dark:text-white mb-2">Sample Card</h4>
          <p class="text-sm text-gray-600 dark:text-gray-400">
            This is how cards and panels will look with your selected theme.
          </p>
        </div>
      </div>
    </div>

    <!-- Reset Theme -->
    <div class="bg-white rounded-xl border border-gray-200 p-6 dark:bg-gray-800 dark:border-gray-700">
      <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
        Reset Theme
      </h3>
      <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
        Reset all theme settings back to their default values.
      </p>
      <button
        @click="resetTheme"
        class="px-4 py-2 bg-red-600 dark:bg-red-500 text-white rounded-lg hover:bg-red-700 dark:hover:bg-red-600 transition-colors"
      >
        Reset to Defaults
      </button>
    </div>

    <!-- Custom Colors Modal -->
    <CustomColorsModal
      :show="showCustomColorsModal"
      @close="showCustomColorsModal = false"
      @save="handleCustomColors"
    />
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { useThemeStore } from '@/stores/theme'
import { useNotifications } from '@/composables/useNotifications'
import CustomColorsModal from './CustomColorsModal.vue'
import { CheckIcon, PlusIcon } from '@heroicons/vue/24/solid'
import {
  SunIcon,
  MoonIcon,
  ComputerDesktopIcon
} from '@heroicons/vue/24/outline'
import type { ThemeColors } from '@/stores/theme'

const themeStore = useThemeStore()
const { addNotification } = useNotifications()

const showCustomColorsModal = ref(false)

const themeOptions = [
  {
    value: 'light' as const,
    label: 'Light',
    description: 'Always light',
    icon: SunIcon
  },
  {
    value: 'dark' as const,
    label: 'Dark',
    description: 'Always dark',
    icon: MoonIcon
  },
  {
    value: 'system' as const,
    label: 'System',
    description: 'Match device',
    icon: ComputerDesktopIcon
  }
]

const handleCustomColors = (colors: ThemeColors) => {
  themeStore.setCustomColors(colors)
  showCustomColorsModal.value = false
  addNotification({
    title: 'Custom Colors Applied',
    message: 'Your custom color scheme has been saved and applied.',
    type: 'success',
    duration: 3000
  })
}

const resetTheme = () => {
  themeStore.resetToDefaults()
  addNotification({
    title: 'Theme Reset',
    message: 'All theme settings have been reset to their defaults.',
    type: 'success',
    duration: 3000
  })
}
</script>

<template>
  <div class="relative">
    <!-- Theme Toggle Button -->
    <button
      @click="toggleTheme"
      class="p-2.5 text-gray-400 hover:text-gray-600 hover:bg-gray-100/80 rounded-xl transition-all duration-200 dark:text-gray-300 dark:hover:text-gray-200 dark:hover:bg-gray-700/50"
      :title="`Switch to ${nextThemeLabel} mode`"
    >
      <Transition
        name="theme-icon"
        mode="out-in"
      >
        <SunIcon v-if="themeStore.isDark" class="w-6 h-6" />
        <MoonIcon v-else class="w-6 h-6" />
      </Transition>
    </button>

    <!-- Quick Theme Menu (Optional) -->
    <div
      v-if="showQuickMenu"
      class="absolute right-0 top-full mt-2 w-48 bg-white dark:bg-gray-800 rounded-xl shadow-xl border border-gray-200 dark:border-gray-700 py-2 z-50"
    >
      <button
        v-for="mode in themeOptions"
        :key="mode.value"
        @click="setThemeMode(mode.value)"
        :class="[
          'w-full flex items-center px-4 py-2 text-sm transition-colors',
          themeStore.mode === mode.value
            ? 'text-primary-600 bg-primary-50 dark:text-primary-400 dark:bg-primary-900/20'
            : 'text-gray-700 hover:bg-gray-50 dark:text-gray-300 dark:hover:bg-gray-700'
        ]"
      >
        <component :is="mode.icon" class="w-4 h-4 mr-3" />
        <span>{{ mode.label }}</span>
        <CheckIcon
          v-if="themeStore.mode === mode.value"
          class="w-4 h-4 ml-auto text-primary-600 dark:text-primary-400"
        />
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'
import { useThemeStore } from '@/stores/theme'
import {
  SunIcon,
  MoonIcon,
  ComputerDesktopIcon,
  CheckIcon
} from '@heroicons/vue/24/outline'

interface Props {
  showQuickMenu?: boolean
}

withDefaults(defineProps<Props>(), {
  showQuickMenu: false
})

const themeStore = useThemeStore()

const themeOptions = [
  {
    value: 'light' as const,
    label: 'Light',
    icon: SunIcon
  },
  {
    value: 'dark' as const,
    label: 'Dark',
    icon: MoonIcon
  },
  {
    value: 'system' as const,
    label: 'System',
    icon: ComputerDesktopIcon
  }
]

const nextThemeLabel = computed(() => {
  if (themeStore.mode === 'system') {
    return themeStore.isDark ? 'light' : 'dark'
  }
  return themeStore.mode === 'light' ? 'dark' : 'light'
})

const toggleTheme = () => {
  if (themeStore.mode === 'system') {
    // If system mode, switch to opposite of current system theme
    themeStore.setMode(themeStore.isDark ? 'light' : 'dark')
  } else {
    // If manual mode, toggle between light and dark
    themeStore.setMode(themeStore.mode === 'light' ? 'dark' : 'light')
  }
}

const setThemeMode = (mode: 'light' | 'dark' | 'system') => {
  themeStore.setMode(mode)
}
</script>

<style scoped>
.theme-icon-enter-active,
.theme-icon-leave-active {
  transition: all 0.3s ease;
}

.theme-icon-enter-from {
  opacity: 0;
  transform: rotate(-90deg) scale(0.8);
}

.theme-icon-leave-to {
  opacity: 0;
  transform: rotate(90deg) scale(0.8);
}
</style>

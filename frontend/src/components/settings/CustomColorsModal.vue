<template>
  <TransitionRoot appear :show="show" as="template">
    <Dialog as="div" @close="$emit('close')" class="relative z-50">
      <TransitionChild
        as="template"
        enter="duration-300 ease-out"
        enter-from="opacity-0"
        enter-to="opacity-100"
        leave="duration-200 ease-in"
        leave-from="opacity-100"
        leave-to="opacity-0"
      >
        <div class="fixed inset-0 bg-black/60 backdrop-blur-sm" />
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
            <DialogPanel class="w-full max-w-4xl transform overflow-hidden rounded-2xl bg-white p-6 text-left align-middle shadow-xl transition-all dark:bg-gray-800">
              <DialogTitle as="h3" class="text-lg font-semibold leading-6 text-gray-900 dark:text-white mb-2">
                Create Custom Color Scheme
              </DialogTitle>
              <p class="text-sm text-gray-600 dark:text-gray-400 mb-6">
                Design your own color palette with primary, secondary, and optional accent colors.
              </p>

              <div class="space-y-8">
                <!-- Scheme Name -->
                <div>
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Scheme Name
                  </label>
                  <input
                    v-model="schemeName"
                    type="text"
                    placeholder="My Custom Theme"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                  />
                </div>

                <!-- Primary Colors -->
                <div>
                  <h4 class="text-md font-medium text-gray-900 dark:text-white mb-4">
                    Primary Colors
                  </h4>
                  <div class="grid grid-cols-5 lg:grid-cols-10 gap-3">
                    <div
                      v-for="shade in colorShades"
                      :key="`primary-${shade}`"
                      class="text-center"
                    >
                      <label class="block text-xs text-gray-600 dark:text-gray-400 mb-1">
                        {{ shade }}
                      </label>
                      <div class="relative">
                        <input
                          v-model="primaryColors[shade]"
                          type="color"
                          class="w-full h-12 rounded-lg border border-gray-300 cursor-pointer dark:border-gray-600"
                        />
                        <button
                          @click="generateShade('primary', shade)"
                          class="absolute -top-1 -right-1 w-5 h-5 bg-white rounded-full shadow-md flex items-center justify-center text-xs hover:bg-gray-50 dark:bg-gray-700 dark:hover:bg-gray-600"
                        >
                          ✨
                        </button>
                      </div>
                    </div>
                  </div>
                  <div class="mt-4 flex space-x-3">
                    <button
                      @click="generatePrimaryPalette"
                      class="px-3 py-1 text-sm bg-primary-100 text-primary-700 rounded-lg hover:bg-primary-200 transition-colors dark:bg-primary-900/30 dark:text-primary-300"
                    >
                      Generate from Base Color
                    </button>
                    <button
                      @click="resetPrimaryColors"
                      class="px-3 py-1 text-sm bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors dark:bg-gray-700 dark:text-gray-300"
                    >
                      Reset
                    </button>
                  </div>
                </div>

                <!-- Secondary Colors -->
                <div>
                  <h4 class="text-md font-medium text-gray-900 dark:text-white mb-4">
                    Secondary Colors
                  </h4>
                  <div class="grid grid-cols-5 lg:grid-cols-10 gap-3">
                    <div
                      v-for="shade in colorShades"
                      :key="`secondary-${shade}`"
                      class="text-center"
                    >
                      <label class="block text-xs text-gray-600 dark:text-gray-400 mb-1">
                        {{ shade }}
                      </label>
                      <div class="relative">
                        <input
                          v-model="secondaryColors[shade]"
                          type="color"
                          class="w-full h-12 rounded-lg border border-gray-300 cursor-pointer dark:border-gray-600"
                        />
                        <button
                          @click="generateShade('secondary', shade)"
                          class="absolute -top-1 -right-1 w-5 h-5 bg-white rounded-full shadow-md flex items-center justify-center text-xs hover:bg-gray-50 dark:bg-gray-700 dark:hover:bg-gray-600"
                        >
                          ✨
                        </button>
                      </div>
                    </div>
                  </div>
                  <div class="mt-4 flex space-x-3">
                    <button
                      @click="generateSecondaryPalette"
                      class="px-3 py-1 text-sm bg-primary-100 text-primary-700 rounded-lg hover:bg-primary-200 transition-colors dark:bg-primary-900/30 dark:text-primary-300"
                    >
                      Generate from Base Color
                    </button>
                    <button
                      @click="resetSecondaryColors"
                      class="px-3 py-1 text-sm bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors dark:bg-gray-700 dark:text-gray-300"
                    >
                      Reset
                    </button>
                  </div>
                </div>

                <!-- Accent Colors (Optional) -->
                <div>
                  <div class="flex items-center justify-between mb-4">
                    <h4 class="text-md font-medium text-gray-900 dark:text-white">
                      Accent Colors (Optional)
                    </h4>
                    <label class="flex items-center">
                      <input
                        v-model="includeAccent"
                        type="checkbox"
                        class="rounded border-gray-300 text-primary-600 focus:ring-primary-500"
                      />
                      <span class="ml-2 text-sm text-gray-600 dark:text-gray-400">Include accent colors</span>
                    </label>
                  </div>
                  
                  <div v-if="includeAccent" class="space-y-4">
                    <div class="grid grid-cols-5 lg:grid-cols-10 gap-3">
                      <div
                        v-for="shade in colorShades"
                        :key="`accent-${shade}`"
                        class="text-center"
                      >
                        <label class="block text-xs text-gray-600 dark:text-gray-400 mb-1">
                          {{ shade }}
                        </label>
                        <div class="relative">
                          <input
                            v-model="accentColors[shade]"
                            type="color"
                            class="w-full h-12 rounded-lg border border-gray-300 cursor-pointer dark:border-gray-600"
                          />
                          <button
                            @click="generateShade('accent', shade)"
                            class="absolute -top-1 -right-1 w-5 h-5 bg-white rounded-full shadow-md flex items-center justify-center text-xs hover:bg-gray-50 dark:bg-gray-700 dark:hover:bg-gray-600"
                          >
                            ✨
                          </button>
                        </div>
                      </div>
                    </div>
                    <div class="flex space-x-3">
                      <button
                        @click="generateAccentPalette"
                        class="px-3 py-1 text-sm bg-primary-100 text-primary-700 rounded-lg hover:bg-primary-200 transition-colors dark:bg-primary-900/30 dark:text-primary-300"
                      >
                        Generate from Base Color
                      </button>
                      <button
                        @click="resetAccentColors"
                        class="px-3 py-1 text-sm bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors dark:bg-gray-700 dark:text-gray-300"
                      >
                        Reset
                      </button>
                    </div>
                  </div>
                </div>

                <!-- Preview -->
                <div>
                  <h4 class="text-md font-medium text-gray-900 dark:text-white mb-4">
                    Preview
                  </h4>
                  <div class="p-4 bg-gray-50 rounded-lg border border-gray-200 dark:bg-gray-700 dark:border-gray-600">
                    <div class="flex space-x-3 mb-4">
                      <button
                        class="px-4 py-2 rounded-lg text-white transition-colors custom-primary-button"
                        :style="{ backgroundColor: primaryColors[600] }"
                      >
                        Primary Button
                      </button>
                      <button
                        class="px-4 py-2 rounded-lg transition-colors"
                        :style="{ backgroundColor: secondaryColors[200], color: secondaryColors[900] }"
                      >
                        Secondary Button
                      </button>
                      <button
                        v-if="includeAccent"
                        class="px-4 py-2 rounded-lg text-white transition-colors"
                        :style="{ backgroundColor: accentColors[500] }"
                      >
                        Accent Button
                      </button>
                    </div>
                    <div class="space-y-2">
                      <div
                        class="h-2 rounded-full"
                        :style="{ backgroundColor: primaryColors[500] }"
                      />
                      <div
                        class="h-2 rounded-full w-3/4"
                        :style="{ backgroundColor: secondaryColors[400] }"
                      />
                      <div
                        v-if="includeAccent"
                        class="h-2 rounded-full w-1/2"
                        :style="{ backgroundColor: accentColors[400] }"
                      />
                    </div>
                  </div>
                </div>
              </div>

              <!-- Actions -->
              <div class="mt-8 flex justify-end space-x-3">
                <button
                  @click="$emit('close')"
                  class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-600"
                >
                  Cancel
                </button>
                <button
                  @click="saveColors"
                  class="px-4 py-2 text-sm font-medium text-white bg-primary-600 border border-transparent rounded-lg hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
                >
                  Save Theme
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
import { ref, reactive } from 'vue'
import { Dialog, DialogPanel, DialogTitle, TransitionChild, TransitionRoot } from '@headlessui/vue'
import type { ThemeColors } from '@/stores/theme'

interface Props {
  show: boolean
}

defineProps<Props>()

const emit = defineEmits<{
  close: []
  save: [colors: ThemeColors]
}>()

const schemeName = ref('My Custom Theme')
const includeAccent = ref(false)

const colorShades = [50, 100, 200, 300, 400, 500, 600, 700, 800, 900] as const

const primaryColors = reactive({
  50: '#eff6ff',
  100: '#dbeafe',
  200: '#bfdbfe',
  300: '#93c5fd',
  400: '#60a5fa',
  500: '#3b82f6',
  600: '#2563eb',
  700: '#1d4ed8',
  800: '#1e40af',
  900: '#1e3a8a',
})

const secondaryColors = reactive({
  50: '#f8fafc',
  100: '#f1f5f9',
  200: '#e2e8f0',
  300: '#cbd5e1',
  400: '#94a3b8',
  500: '#64748b',
  600: '#475569',
  700: '#334155',
  800: '#1e293b',
  900: '#0f172a',
})

const accentColors = reactive({
  50: '#faf5ff',
  100: '#f3e8ff',
  200: '#e9d5ff',
  300: '#d8b4fe',
  400: '#c084fc',
  500: '#a855f7',
  600: '#9333ea',
  700: '#7c3aed',
  800: '#6b21a8',
  900: '#581c87',
  950: '#3b0764',
})

// Helper function to generate color shades from a base color
const hexToHsl = (hex: string) => {
  const r = parseInt(hex.slice(1, 3), 16) / 255
  const g = parseInt(hex.slice(3, 5), 16) / 255
  const b = parseInt(hex.slice(5, 7), 16) / 255

  const max = Math.max(r, g, b)
  const min = Math.min(r, g, b)
  let h, s, l = (max + min) / 2

  if (max === min) {
    h = s = 0
  } else {
    const d = max - min
    s = l > 0.5 ? d / (2 - max - min) : d / (max + min)
    switch (max) {
      case r: h = (g - b) / d + (g < b ? 6 : 0); break
      case g: h = (b - r) / d + 2; break
      case b: h = (r - g) / d + 4; break
      default: h = 0
    }
    h /= 6
  }

  return [h * 360, s * 100, l * 100]
}

const hslToHex = (h: number, s: number, l: number) => {
  l /= 100
  const a = s * Math.min(l, 1 - l) / 100
  const f = (n: number) => {
    const k = (n + h / 30) % 12
    const color = l - a * Math.max(Math.min(k - 3, 9 - k, 1), -1)
    return Math.round(255 * color).toString(16).padStart(2, '0')
  }
  return `#${f(0)}${f(8)}${f(4)}`
}

const generateColorPalette = (baseColor: string) => {
  const [h, s, l] = hexToHsl(baseColor)
  
  return {
    50: hslToHex(h, s, 95),
    100: hslToHex(h, s, 90),
    200: hslToHex(h, s, 80),
    300: hslToHex(h, s, 70),
    400: hslToHex(h, s, 60),
    500: baseColor,
    600: hslToHex(h, s, 40),
    700: hslToHex(h, s, 30),
    800: hslToHex(h, s, 20),
    900: hslToHex(h, s, 10),
  }
}

const generateShade = (type: 'primary' | 'secondary' | 'accent', shade: number) => {
  const colors = type === 'primary' ? primaryColors : type === 'secondary' ? secondaryColors : accentColors
  const baseColor = colors[500]
  const palette = generateColorPalette(baseColor)
  colors[shade as keyof typeof colors] = palette[shade as keyof typeof palette]
}

const generatePrimaryPalette = () => {
  const palette = generateColorPalette(primaryColors[500])
  Object.assign(primaryColors, palette)
}

const generateSecondaryPalette = () => {
  const palette = generateColorPalette(secondaryColors[500])
  Object.assign(secondaryColors, palette)
}

const generateAccentPalette = () => {
  const palette = generateColorPalette(accentColors[500])
  Object.assign(accentColors, palette)
}

const resetPrimaryColors = () => {
  Object.assign(primaryColors, {
    50: '#eff6ff',
    100: '#dbeafe',
    200: '#bfdbfe',
    300: '#93c5fd',
    400: '#60a5fa',
    500: '#3b82f6',
    600: '#2563eb',
    700: '#1d4ed8',
    800: '#1e40af',
    900: '#1e3a8a',
  })
}

const resetSecondaryColors = () => {
  Object.assign(secondaryColors, {
    50: '#f8fafc',
    100: '#f1f5f9',
    200: '#e2e8f0',
    300: '#cbd5e1',
    400: '#94a3b8',
    500: '#64748b',
    600: '#475569',
    700: '#334155',
    800: '#1e293b',
    900: '#0f172a',
  })
}

const resetAccentColors = () => {
  Object.assign(accentColors, {
    50: '#faf5ff',
    100: '#f3e8ff',
    200: '#e9d5ff',
    300: '#d8b4fe',
    400: '#c084fc',
    500: '#a855f7',
    600: '#9333ea',
    700: '#7c3aed',
    800: '#6b21a8',
    900: '#581c87',
    950: '#3b0764',
  })
}

const saveColors = () => {
  const customColors: ThemeColors = {
    name: schemeName.value,
    primary: { ...primaryColors },
    secondary: { ...secondaryColors },
  }

  if (includeAccent.value) {
    customColors.accent = { ...accentColors }
  }

  emit('save', customColors)
}
</script>

<style scoped>
.custom-primary-button:hover {
  opacity: 0.9;
}
</style>

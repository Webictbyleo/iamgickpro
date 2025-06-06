<template>
  <Popover v-slot="{ open }" class="relative">
    <PopoverButton
      :class="[
        'w-8 h-8 rounded-lg border-2 border-gray-300 focus:outline-none focus:ring-2 focus:ring-purple-500 transition-all duration-200',
        open ? 'ring-2 ring-purple-500' : ''
      ]"
      :style="{ backgroundColor: displayColor }"
    >
      <span class="sr-only">Select color</span>
    </PopoverButton>

    <Transition
      enter-active-class="transition duration-200 ease-out"
      enter-from-class="transform scale-95 opacity-0"
      enter-to-class="transform scale-100 opacity-100"
      leave-active-class="transition duration-75 ease-in"
      leave-from-class="transform scale-100 opacity-100"
      leave-to-class="transform scale-95 opacity-0"
    >
      <PopoverPanel class="absolute z-10 mt-2 w-64 bg-white rounded-xl shadow-xl border border-gray-200 p-4">
        <!-- Color Picker Tabs -->
        <div class="flex space-x-1 mb-4">
          <button
            v-for="tab in tabs"
            :key="tab.id"
            @click="activeTab = tab.id"
            :class="[
              'flex-1 px-3 py-2 text-sm font-medium rounded-lg transition-colors',
              activeTab === tab.id
                ? 'bg-purple-100 text-purple-700'
                : 'text-gray-600 hover:text-gray-900 hover:bg-gray-100'
            ]"
          >
            {{ tab.label }}
          </button>
        </div>

        <!-- Solid Color Tab -->
        <div v-if="activeTab === 'solid'" class="space-y-4">
          <!-- Hex Input -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Hex Color</label>
            <div class="flex">
              <span class="inline-flex items-center px-3 py-2 border border-r-0 border-gray-300 bg-gray-50 text-gray-500 text-sm rounded-l-lg">#</span>
              <input
                :value="hexValue"
                @input="updateHex"
                class="flex-1 px-3 py-2 border border-gray-300 rounded-r-lg focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent text-sm"
                placeholder="000000"
                maxlength="6"
              />
            </div>
          </div>

          <!-- Preset Colors -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Preset Colors</label>
            <div class="grid grid-cols-8 gap-2">
              <button
                v-for="color in presetColors"
                :key="color"
                @click="updateColor(color)"
                :class="[
                  'w-6 h-6 rounded-lg border-2 transition-transform hover:scale-110',
                  value === color ? 'border-purple-500 ring-2 ring-purple-200' : 'border-gray-300'
                ]"
                :style="{ backgroundColor: color }"
              />
            </div>
          </div>

          <!-- Recently Used -->
          <div v-if="recentColors.length > 0">
            <label class="block text-sm font-medium text-gray-700 mb-2">Recently Used</label>
            <div class="flex space-x-2">
              <button
                v-for="color in recentColors"
                :key="color"
                @click="updateColor(color)"
                :class="[
                  'w-6 h-6 rounded-lg border-2 transition-transform hover:scale-110',
                  value === color ? 'border-purple-500 ring-2 ring-purple-200' : 'border-gray-300'
                ]"
                :style="{ backgroundColor: color }"
              />
            </div>
          </div>
        </div>

        <!-- Gradient Tab -->
        <div v-if="activeTab === 'gradient' && allowGradient" class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Gradient Type</label>
            <PropertyDropdown
              :value="gradientType"
              :options="gradientOptions"
              @update="updateGradientType"
            />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Colors</label>
            <div class="space-y-2">
              <div v-for="(color, index) in gradientColors" :key="index" class="flex items-center space-x-2">
                <div
                  :style="{ backgroundColor: color }"
                  class="w-6 h-6 rounded border border-gray-300"
                />
                <input
                  :value="color"
                  @input="updateGradientColor(index, $event)"
                  class="flex-1 px-2 py-1 border border-gray-300 rounded text-sm"
                />
                <button
                  v-if="gradientColors.length > 2"
                  @click="removeGradientColor(index)"
                  class="text-red-500 hover:text-red-700"
                >
                  ×
                </button>
              </div>
            </div>
            <button
              v-if="gradientColors.length < 5"
              @click="addGradientColor"
              class="mt-2 text-sm text-purple-600 hover:text-purple-700"
            >
              + Add Color
            </button>
          </div>
        </div>
      </PopoverPanel>
    </Transition>
  </Popover>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { Popover, PopoverButton, PopoverPanel } from '@headlessui/vue'
import PropertyDropdown from './PropertyDropdown.vue'

interface Props {
  value: string
  allowGradient?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  allowGradient: false
})

const emit = defineEmits<{
  update: [value: string]
}>()

const activeTab = ref('solid')
const recentColors = ref<string[]>([])

const tabs = computed(() => {
  const baseTabs = [{ id: 'solid', label: 'Solid' }]
  if (props.allowGradient) {
    baseTabs.push({ id: 'gradient', label: 'Gradient' })
  }
  return baseTabs
})

const displayColor = computed(() => {
  if (props.value.startsWith('linear-gradient') || props.value.startsWith('radial-gradient')) {
    return props.value
  }
  return props.value
})

const hexValue = computed(() => {
  if (props.value.startsWith('#')) {
    return props.value.slice(1)
  }
  return props.value
})

// Gradient state
const gradientType = ref('linear')
const gradientColors = ref(['#ff0000', '#0000ff'])

const gradientOptions = [
  { value: 'linear', label: 'Linear' },
  { value: 'radial', label: 'Radial' }
]

const presetColors = [
  '#000000', '#ffffff', '#ff0000', '#00ff00', '#0000ff', '#ffff00', '#ff00ff', '#00ffff',
  '#800000', '#008000', '#000080', '#808000', '#800080', '#008080', '#c0c0c0', '#808080',
  '#ff6b6b', '#4ecdc4', '#45b7d1', '#96ceb4', '#feca57', '#ff9ff3', '#54a0ff', '#5f27cd'
]

const updateColor = (color: string) => {
  // Add to recent colors
  if (!recentColors.value.includes(color)) {
    recentColors.value.unshift(color)
    if (recentColors.value.length > 8) {
      recentColors.value.pop()
    }
  }
  
  emit('update', color)
}

const updateHex = (event: Event) => {
  const target = event.target as HTMLInputElement
  let hex = target.value.replace(/[^0-9a-fA-F]/g, '')
  if (hex.length <= 6) {
    updateColor(`#${hex}`)
  }
}

const updateGradientType = (type: string) => {
  gradientType.value = type
  updateGradientValue()
}

const updateGradientColor = (index: number, event: Event) => {
  const target = event.target as HTMLInputElement
  gradientColors.value[index] = target.value
  updateGradientValue()
}

const addGradientColor = () => {
  gradientColors.value.push('#000000')
  updateGradientValue()
}

const removeGradientColor = (index: number) => {
  gradientColors.value.splice(index, 1)
  updateGradientValue()
}

const updateGradientValue = () => {
  const colors = gradientColors.value.join(', ')
  const gradientValue = gradientType.value === 'linear' 
    ? `linear-gradient(90deg, ${colors})`
    : `radial-gradient(circle, ${colors})`
  emit('update', gradientValue)
}

// Parse gradient value when prop changes
watch(() => props.value, (newValue) => {
  if (newValue.startsWith('linear-gradient')) {
    activeTab.value = 'gradient'
    gradientType.value = 'linear'
    // Parse colors from gradient string
    const matches = newValue.match(/#[0-9a-fA-F]{6}/g)
    if (matches) {
      gradientColors.value = matches
    }
  } else if (newValue.startsWith('radial-gradient')) {
    activeTab.value = 'gradient'
    gradientType.value = 'radial'
    // Parse colors from gradient string
    const matches = newValue.match(/#[0-9a-fA-F]{6}/g)
    if (matches) {
      gradientColors.value = matches
    }
  }
}, { immediate: true })
</script>

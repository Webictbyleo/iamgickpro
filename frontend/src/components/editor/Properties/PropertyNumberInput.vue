<template>
  <div class="relative">
    <!-- Main Input -->
    <div class="relative">
      <input
        ref="inputRef"
        type="text"
        :value="formattedValue"
        @input="handleInput"
        @blur="handleBlur"
        @focus="handleFocus"
        @keydown="handleKeydown"
        :class="[
          'w-full px-3 py-1.5 text-sm border border-gray-300 rounded text-left focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors',
          'appearance-none', // Remove browser styling
          { 'pr-8': unit }, // Add padding for unit suffix
          inputClass
        ]"
        :placeholder="placeholder"
        :disabled="disabled"
      />
      
      <!-- Unit Suffix -->
      <span 
        v-if="unit" 
        class="absolute right-2 top-1/2 transform -translate-y-1/2 text-xs text-gray-400 pointer-events-none select-none"
      >
        {{ unit }}
      </span>
      
      <!-- Dropdown Arrow -->
      <button
        v-if="presets && presets.length > 0"
        @click="toggleDropdown"
        @mousedown.prevent
        class="absolute right-1 top-1/2 transform -translate-y-1/2 p-1 hover:bg-gray-100 rounded transition-colors"
        :class="{ 'right-8': unit }"
        type="button"
      >
        <ChevronDownIcon class="w-3 h-3 text-gray-400" />
      </button>
    </div>

    <!-- Presets Dropdown -->
    <div 
      v-if="showDropdown && presets && presets.length > 0"
      class="absolute top-full left-0 right-0 mt-1 bg-white border border-gray-200 rounded-md shadow-lg z-50 max-h-48 overflow-y-auto"
    >
      <div class="py-1">
        <button
          v-for="preset in presets"
          :key="preset.value"
          @click="selectPreset(preset.value)"
          class="w-full px-3 py-2 text-left text-sm hover:bg-gray-50 transition-colors flex items-center justify-between"
          :class="{ 'bg-blue-50 text-blue-600': preset.value === internalValue }"
        >
          <span>{{ preset.label }}</span>
          <span class="text-xs text-gray-400">{{ formatDisplayValue(preset.value) }}{{ unit }}</span>
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch, nextTick, onMounted, onUnmounted } from 'vue'
import ChevronDownIcon from '@/components/icons/ChevronDownIcon.vue'

interface Preset {
  label: string
  value: number
}

interface Props {
  value?: number
  min?: number
  max?: number
  step?: number
  unit?: string
  placeholder?: string
  disabled?: boolean
  presets?: Preset[]
  precision?: number // Number of decimal places for display
  inputClass?: string
}

const props = withDefaults(defineProps<Props>(), {
  value: 0,
  step: 1,
  precision: 0,
  disabled: false
})

const emit = defineEmits<{
  'update:value': [value: number]
  'change': [value: number]
}>()

const inputRef = ref<HTMLInputElement>()
const showDropdown = ref(false)
const internalValue = ref(props.value)
const isEditing = ref(false)

// Watch for external value changes
watch(() => props.value, (newValue) => {
  if (newValue !== internalValue.value && !isEditing.value) {
    internalValue.value = newValue
  }
})

// Initialize internal value
onMounted(() => {
  internalValue.value = props.value
})

const formattedValue = computed(() => {
  if (isEditing.value) {
    // During editing, show the raw input value
    return inputRef.value?.value || ''
  }
  return formatDisplayValue(internalValue.value)
})

const formatDisplayValue = (value: number): string => {
  if (isNaN(value)) return '0'
  
  if (props.precision === 0) {
    return Math.round(value).toString()
  } else {
    return Number(value.toFixed(props.precision)).toString()
  }
}

const parseInputValue = (inputValue: string): number => {
  // Remove non-numeric characters except decimal point and minus
  const cleanValue = inputValue.replace(/[^\d.-]/g, '')
  const parsed = parseFloat(cleanValue)
  return isNaN(parsed) ? 0 : parsed
}

const clampValue = (value: number): number => {
  let clamped = value
  
  if (props.min !== undefined) {
    clamped = Math.max(props.min, clamped)
  }
  
  if (props.max !== undefined) {
    clamped = Math.min(props.max, clamped)
  }
  
  return clamped
}

const handleInput = (event: Event) => {
  const target = event.target as HTMLInputElement
  const inputValue = target.value
  const parsed = parseInputValue(inputValue)
  const clamped = clampValue(parsed)
  
  // Update internal value but don't format during editing
  internalValue.value = clamped
  emit('update:value', clamped)
}

const handleBlur = (event: Event) => {
  const target = event.target as HTMLInputElement
  const inputValue = target.value
  const parsed = parseInputValue(inputValue)
  const clamped = clampValue(parsed)
  
  internalValue.value = clamped
  isEditing.value = false
  
  // Update the input with formatted value
  nextTick(() => {
    if (inputRef.value) {
      inputRef.value.value = formatDisplayValue(clamped)
    }
  })
  
  emit('update:value', clamped)
  emit('change', clamped)
  showDropdown.value = false
}

const handleFocus = () => {
  isEditing.value = true
  // Select all text when focused
  nextTick(() => {
    inputRef.value?.select()
  })
}

const handleKeydown = (event: KeyboardEvent) => {
  if (event.key === 'Enter') {
    inputRef.value?.blur()
  } else if (event.key === 'Escape') {
    // Reset to original value and blur
    if (inputRef.value) {
      inputRef.value.value = formatDisplayValue(props.value)
    }
    internalValue.value = props.value
    isEditing.value = false
    inputRef.value?.blur()
  } else if (event.key === 'ArrowUp') {
    event.preventDefault()
    incrementValue()
  } else if (event.key === 'ArrowDown') {
    event.preventDefault()
    decrementValue()
  }
}

const incrementValue = () => {
  const newValue = clampValue(internalValue.value + props.step)
  internalValue.value = newValue
  emit('update:value', newValue)
  
  // Update the input display value immediately
  if (inputRef.value) {
    inputRef.value.value = formatDisplayValue(newValue)
  }
  
  // Ensure we're not in editing state for proper display
  isEditing.value = false
}

const decrementValue = () => {
  const newValue = clampValue(internalValue.value - props.step)
  internalValue.value = newValue
  emit('update:value', newValue)
  
  // Update the input display value immediately
  if (inputRef.value) {
    inputRef.value.value = formatDisplayValue(newValue)
  }
  
  // Ensure we're not in editing state for proper display
  isEditing.value = false
}

const toggleDropdown = () => {
  if (!props.disabled && props.presets && props.presets.length > 0) {
    showDropdown.value = !showDropdown.value
  }
}

const selectPreset = (value: number) => {
  internalValue.value = value
  showDropdown.value = false
  emit('update:value', value)
  emit('change', value)
  
  // Update input display
  if (inputRef.value) {
    inputRef.value.value = formatDisplayValue(value)
  }
}

// Close dropdown when clicking outside
const handleClickOutside = (event: Event) => {
  const target = event.target as Element
  if (!target.closest('.relative')) {
    showDropdown.value = false
  }
}

onMounted(() => {
  document.addEventListener('click', handleClickOutside)
})

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside)
})
</script>

<style scoped>
/* Remove browser number input arrows */
input[type="text"]::-webkit-outer-spin-button,
input[type="text"]::-webkit-inner-spin-button {
  -webkit-appearance: none;
  margin: 0;
}

input[type="text"] {
  appearance: textfield;
  -moz-appearance: textfield;
}
</style>

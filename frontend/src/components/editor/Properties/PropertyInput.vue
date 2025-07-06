<template>
  <div v-if="label" class="space-y-1">
    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">
      {{ label }}
    </label>
    <component
      :is="multiline ? 'textarea' : 'input'"
      :value="value"
      :type="multiline ? undefined : type"
      :rows="multiline ? rows : undefined"
      :min="min"
      :max="max"
      :step="step"
      :placeholder="placeholder"
      :disabled="disabled"
      :class="inputClasses"
      @input="handleInput"
      @blur="handleBlur"
      @focus="handleFocus"
      @keydown.enter="multiline ? undefined : handleBlur"
    />
    <div v-if="suffix && type !== 'range'" class="text-xs text-gray-500 dark:text-gray-400">
      {{ formattedValue }} {{ suffix }}
    </div>
  </div>
  <component
    v-else
    :is="multiline ? 'textarea' : 'input'"
    :value="value"
    :type="multiline ? undefined : type"
    :rows="multiline ? rows : undefined"
    :min="min"
    :max="max"
    :step="step"
    :placeholder="placeholder"
    :disabled="disabled"
    :class="inputClasses"
    @input="handleInput"
    @blur="handleBlur"
    @focus="handleFocus"
    @keydown.enter="multiline ? undefined : handleBlur"
  />
</template>

<script setup lang="ts">
import { computed, ref, watch, onMounted } from 'vue'

interface Props {
  value: string | number
  type?: 'text' | 'number' | 'email' | 'password' | 'range'
  placeholder?: string
  disabled?: boolean
  min?: number
  max?: number
  step?: number
  label?: string
  multiline?: boolean
  rows?: number
  suffix?: string
}

const props = withDefaults(defineProps<Props>(), {
  type: 'text',
  placeholder: '',
  disabled: false,
  step: 1,
  multiline: false,
  rows: 3
})

const emit = defineEmits<{
  update: [value: string | number]
  change: [value: string | number]
}>()

// Track original value to detect actual changes
const originalValue = ref<string | number>(props.value)
const isEditing = ref(false)

// Watch for external value changes
watch(() => props.value, (newValue) => {
  if (newValue !== originalValue.value && !isEditing.value) {
    originalValue.value = newValue
  }
})

// Initialize original value
onMounted(() => {
  originalValue.value = props.value
})

const inputClasses = computed(() => [
  'block w-full rounded-lg border px-3 py-2 text-sm transition-colors duration-200',
  'border-gray-300 dark:border-gray-600',
  'bg-white dark:bg-gray-800',
  'text-gray-900 dark:text-gray-100',
  'placeholder-gray-400 dark:placeholder-gray-500',
  'focus:outline-none focus:ring-2 focus:ring-primary-500/50 dark:focus:ring-primary-400/50 focus:border-primary-500 dark:focus:border-primary-400',
  'disabled:opacity-50 disabled:cursor-not-allowed disabled:bg-gray-50 dark:disabled:bg-gray-700'
])

const formattedValue = computed(() => {
  if (props.type === 'number') {
    return typeof props.value === 'number' ? props.value.toString() : props.value
  }
  return props.value
})

const handleFocus = () => {
  isEditing.value = true
  originalValue.value = props.value // Store the original value when editing starts
}

const handleInput = (event: Event) => {
  const target = event.target as HTMLInputElement
  let value: string | number = target.value
  
  if (props.type === 'number') {
    value = parseFloat(value) || 0
    
    // Apply min/max constraints
    if (props.min !== undefined && value < props.min) {
      value = props.min
      target.value = value.toString()
    }
    if (props.max !== undefined && value > props.max) {
      value = props.max
      target.value = value.toString()
    }
  }
  
  // Only emit if value actually changed
  const hasChanged = value !== originalValue.value
  if (hasChanged) {
    emit('update', value)
  }
}

const handleBlur = (event: Event) => {
  const target = event.target as HTMLInputElement
  isEditing.value = false
  
  if (props.type === 'number') {
    let value = parseFloat(target.value) || 0
    
    // Apply min/max constraints on blur
    if (props.min !== undefined && value < props.min) {
      value = props.min
    }
    if (props.max !== undefined && value > props.max) {
      value = props.max
    }
    
    target.value = value.toString()
    
    // Only emit events if value actually changed from original
    const hasChanged = value !== originalValue.value
    if (hasChanged) {
      originalValue.value = value
      emit('update', value)
      emit('change', value)
    }
  } else {
    // For text inputs
    const value = target.value
    const hasChanged = value !== originalValue.value
    if (hasChanged) {
      originalValue.value = value
      emit('update', value)
      emit('change', value)
    }
  }
}
</script>

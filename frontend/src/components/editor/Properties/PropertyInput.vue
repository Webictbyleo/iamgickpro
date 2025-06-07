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
    @keydown.enter="multiline ? undefined : handleBlur"
  />
</template>

<script setup lang="ts">
import { computed } from 'vue'

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
}>()

const inputClasses = computed(() => [
  'block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm placeholder-gray-400',
  'focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent',
  'disabled:opacity-50 disabled:cursor-not-allowed disabled:bg-gray-50',
  'transition-colors duration-200'
])

const formattedValue = computed(() => {
  if (props.type === 'number') {
    return typeof props.value === 'number' ? props.value.toString() : props.value
  }
  return props.value
})

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
  
  emit('update', value)
}

const handleBlur = (event: Event) => {
  const target = event.target as HTMLInputElement
  
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
    emit('update', value)
  }
}
</script>

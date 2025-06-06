<template>
  <input
    :value="value"
    :type="type"
    :min="min"
    :max="max"
    :step="step"
    :placeholder="placeholder"
    :disabled="disabled"
    :class="inputClasses"
    @input="handleInput"
    @blur="handleBlur"
    @keydown.enter="handleBlur"
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
}

const props = withDefaults(defineProps<Props>(), {
  type: 'text',
  placeholder: '',
  disabled: false,
  step: 1
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

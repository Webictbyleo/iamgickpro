<template>
  <button
    :class="buttonClasses"
    :title="tooltip"
    @click="toggle"
  >
    <slot />
  </button>
</template>

<script setup lang="ts">
import { computed } from 'vue'

interface Props {
  active: boolean
  disabled?: boolean
  tooltip?: string
}

const props = withDefaults(defineProps<Props>(), {
  disabled: false
})

const emit = defineEmits<{
  update: [active: boolean]
}>()

const buttonClasses = computed(() => [
  'inline-flex items-center justify-center w-8 h-8 rounded-lg transition-all duration-200',
  'focus:outline-none focus:ring-2 focus:ring-primary-500/50 dark:focus:ring-primary-400/50',
  props.active 
    ? 'bg-primary-600 dark:bg-primary-500 text-white shadow-md ring-2 ring-primary-400/50 dark:ring-primary-300/50' 
    : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600 hover:text-gray-900 dark:hover:text-gray-100',
  props.disabled 
    ? 'opacity-50 cursor-not-allowed' 
    : 'cursor-pointer'
])

const toggle = () => {
  if (!props.disabled) {
    emit('update', !props.active)
  }
}
</script>

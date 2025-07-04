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
    : 'bg-secondary-100 dark:bg-secondary-700 text-secondary-600 dark:text-secondary-300 hover:bg-secondary-200 dark:hover:bg-secondary-600 hover:text-secondary-900 dark:hover:text-secondary-100',
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

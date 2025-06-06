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
  'focus:outline-none focus:ring-2 focus:ring-purple-500 focus:ring-offset-2',
  props.active 
    ? 'bg-purple-600 text-white shadow-md' 
    : 'bg-gray-100 text-gray-600 hover:bg-gray-200 hover:text-gray-900',
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

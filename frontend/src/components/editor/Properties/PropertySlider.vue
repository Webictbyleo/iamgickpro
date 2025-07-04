<template>
  <div class="flex items-center space-x-2">
    <input
      :value="value"
      :min="min"
      :max="max"
      :step="step"
      :disabled="disabled"
      type="range"
      class="slider"
      @input="handleInput"
    />
    <span class="text-xs text-gray-500 dark:text-gray-400 min-w-[2rem] text-center">
      {{ displayValue }}
    </span>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'

interface Props {
  value: number
  min?: number
  max?: number
  step?: number
  disabled?: boolean
  showValue?: boolean
  valueFormatter?: (value: number) => string
}

const props = withDefaults(defineProps<Props>(), {
  min: 0,
  max: 100,
  step: 1,
  disabled: false,
  showValue: true,
  valueFormatter: (value: number) => {
    // Format decimal values nicely
    if (value % 1 !== 0) {
      return value.toFixed(1)
    }
    return value.toString()
  }
})

const emit = defineEmits<{
  update: [value: number]
}>()

const handleInput = (event: Event) => {
  const target = event.target as HTMLInputElement
  const value = Number(target.value)
  emit('update', value)
}

const displayValue = computed(() => {
  if (!props.showValue) return ''
  return props.valueFormatter(props.value)
})
</script>

<style scoped>
.slider {
  width: 100%;
  height: 0.5rem;
  border-radius: 0.5rem;
  appearance: none;
  cursor: pointer;
}

/* Light mode styles */
.slider {
  background-color: #e5e7eb; /* gray-300 */
}

/* Dark mode styles */
:global(.dark) .slider {
  background-color: #4b5563; /* gray-600 */
}

.slider::-webkit-slider-thumb {
  appearance: none;
  width: 1rem;
  height: 1rem;
  border-radius: 50%;
  cursor: pointer;
}

/* Light mode thumb */
.slider::-webkit-slider-thumb {
  background-color: #3b82f6; /* blue-500 as fallback for primary */
}

/* Dark mode thumb */
:global(.dark) .slider::-webkit-slider-thumb {
  background-color: #60a5fa; /* blue-400 as fallback for primary */
}

.slider::-moz-range-thumb {
  width: 1rem;
  height: 1rem;
  border-radius: 50%;
  cursor: pointer;
  border: 0;
}

/* Light mode thumb - Firefox */
.slider::-moz-range-thumb {
  background-color: #3b82f6; /* blue-500 as fallback for primary */
}

/* Dark mode thumb - Firefox */
:global(.dark) .slider::-moz-range-thumb {
  background-color: #60a5fa; /* blue-400 as fallback for primary */
}

.slider:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.slider:disabled::-webkit-slider-thumb {
  cursor: not-allowed;
}

.slider:disabled::-moz-range-thumb {
  cursor: not-allowed;
}
</style>

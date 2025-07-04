<template>
  <div class="flex items-center space-x-2">
    <input
      :value="value"
      :min="min"
      :max="max"
      :step="step"
      :disabled="disabled"
      type="range"
      class="property-slider"
      @input="handleInput"
    />
    <span class="text-xs text-secondary-500 dark:text-secondary-400 min-w-[2rem] text-center">
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
.property-slider {
  width: 100%;
  height: 0.5rem;
  border-radius: 0.5rem;
  appearance: none;
  cursor: pointer;
  background: rgb(203 213 225); /* secondary-300 */
  transition: background-color 0.2s ease;
}

.dark .property-slider {
  background: rgb(71 85 105); /* secondary-600 */
}

.property-slider:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.property-slider:focus {
  outline: none;
  box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.5); /* ring-primary-500/50 */
}

.dark .property-slider:focus {
  box-shadow: 0 0 0 2px rgba(96, 165, 250, 0.5); /* ring-primary-400/50 */
}

/* WebKit browsers (Chrome, Safari, Edge) */
.property-slider::-webkit-slider-thumb {
  appearance: none;
  width: 1rem;
  height: 1rem;
  border-radius: 50%;
  cursor: pointer;
  background: rgb(59 130 246); /* primary-500 */
  border: 2px solid rgb(255 255 255); /* white */
  box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1); /* shadow-md */
  transition: all 0.2s ease;
}

.dark .property-slider::-webkit-slider-thumb {
  background: rgb(96 165 250); /* primary-400 */
  border-color: rgb(30 41 59); /* secondary-800 */
}

.property-slider::-webkit-slider-thumb:hover {
  background: rgb(37 99 235); /* primary-600 */
  box-shadow: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1); /* shadow-lg */
  transform: scale(1.1);
}

.dark .property-slider::-webkit-slider-thumb:hover {
  background: rgb(147 197 253); /* primary-300 */
}

.property-slider::-webkit-slider-thumb:active {
  background: rgb(29 78 216); /* primary-700 */
  transform: scale(0.95);
}

.dark .property-slider::-webkit-slider-thumb:active {
  background: rgb(191 219 254); /* primary-200 */
}

.property-slider:focus::-webkit-slider-thumb {
  box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1), 0 0 0 2px rgba(59, 130, 246, 0.5);
}

.dark .property-slider:focus::-webkit-slider-thumb {
  box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1), 0 0 0 2px rgba(96, 165, 250, 0.5);
}

.property-slider:disabled::-webkit-slider-thumb {
  cursor: not-allowed;
  background: rgb(148 163 184); /* secondary-400 */
  border-color: rgb(255 255 255);
}

.dark .property-slider:disabled::-webkit-slider-thumb {
  background: rgb(100 116 139); /* secondary-500 */
  border-color: rgb(30 41 59); /* secondary-800 */
}

.property-slider:disabled::-webkit-slider-thumb:hover {
  background: rgb(148 163 184); /* secondary-400 */
  transform: scale(1);
}

.dark .property-slider:disabled::-webkit-slider-thumb:hover {
  background: rgb(100 116 139); /* secondary-500 */
}

/* Firefox */
.property-slider::-moz-range-thumb {
  width: 1rem;
  height: 1rem;
  border-radius: 50%;
  cursor: pointer;
  border: 0;
  background: rgb(59 130 246); /* primary-500 */
  box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1); /* shadow-md */
  transition: all 0.2s ease;
}

.dark .property-slider::-moz-range-thumb {
  background: rgb(96 165 250); /* primary-400 */
}

.property-slider::-moz-range-thumb:hover {
  background: rgb(37 99 235); /* primary-600 */
  box-shadow: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1); /* shadow-lg */
}

.dark .property-slider::-moz-range-thumb:hover {
  background: rgb(147 197 253); /* primary-300 */
}

.property-slider::-moz-range-thumb:active {
  background: rgb(29 78 216); /* primary-700 */
}

.dark .property-slider::-moz-range-thumb:active {
  background: rgb(191 219 254); /* primary-200 */
}

.property-slider:focus::-moz-range-thumb {
  box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1), 0 0 0 2px rgba(59, 130, 246, 0.5);
}

.dark .property-slider:focus::-moz-range-thumb {
  box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1), 0 0 0 2px rgba(96, 165, 250, 0.5);
}

.property-slider:disabled::-moz-range-thumb {
  cursor: not-allowed;
  background: rgb(148 163 184); /* secondary-400 */
}

.dark .property-slider:disabled::-moz-range-thumb {
  background: rgb(100 116 139); /* secondary-500 */
}

/* Firefox track */
.property-slider::-moz-range-track {
  background: rgb(203 213 225); /* secondary-300 */
  height: 0.5rem;
  border-radius: 0.5rem;
}

.dark .property-slider::-moz-range-track {
  background: rgb(71 85 105); /* secondary-600 */
}
</style>

<template>
  <div class="filter-dropdown bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded-lg p-3 shadow-lg">
    <div class="space-y-3">
      <!-- Filter type selector -->
      <div>
        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
          Filter Type
        </label>
        <select
          v-model="filterOperator"
          class="w-full px-2 py-1 text-sm border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-1 focus:ring-primary-500 focus:border-primary-500"
        >
          <option v-for="operator in availableOperators" :key="operator.value" :value="operator.value">
            {{ operator.label }}
          </option>
        </select>
      </div>
      
      <!-- Filter value input -->
      <div v-if="needsValue">
        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
          Value
        </label>
        
        <!-- Text input -->
        <input
          v-if="inputType === 'text'"
          v-model="filterValue"
          type="text"
          :placeholder="getPlaceholder()"
          class="w-full px-2 py-1 text-sm border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-1 focus:ring-primary-500 focus:border-primary-500"
        />
        
        <!-- Number input -->
        <input
          v-else-if="inputType === 'number'"
          v-model.number="filterValue"
          type="number"
          :placeholder="getPlaceholder()"
          class="w-full px-2 py-1 text-sm border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-1 focus:ring-primary-500 focus:border-primary-500"
        />
        
        <!-- Date input -->
        <input
          v-else-if="inputType === 'date'"
          v-model="filterValue"
          type="date"
          class="w-full px-2 py-1 text-sm border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-1 focus:ring-primary-500 focus:border-primary-500"
        />
        
        <!-- Select input -->
        <select
          v-else-if="inputType === 'select'"
          v-model="filterValue"
          class="w-full px-2 py-1 text-sm border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-1 focus:ring-primary-500 focus:border-primary-500"
        >
          <option value="">Select option...</option>
          <option v-for="option in column.options" :key="option.value" :value="option.value">
            {{ option.label }}
          </option>
        </select>
        
        <!-- Boolean input -->
        <select
          v-else-if="inputType === 'boolean'"
          v-model="filterValue"
          class="w-full px-2 py-1 text-sm border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-1 focus:ring-primary-500 focus:border-primary-500"
        >
          <option value="">Any</option>
          <option :value="true">Yes</option>
          <option :value="false">No</option>
        </select>
      </div>
      
      <!-- Second value for 'between' operator -->
      <div v-if="filterOperator === 'between'">
        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
          To
        </label>
        <input
          v-if="column.type === 'number' || column.type === 'currency'"
          v-model.number="filterValue2"
          type="number"
          placeholder="End value"
          class="w-full px-2 py-1 text-sm border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-1 focus:ring-primary-500 focus:border-primary-500"
        />
        <input
          v-else-if="column.type === 'date'"
          v-model="filterValue2"
          type="date"
          class="w-full px-2 py-1 text-sm border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-1 focus:ring-primary-500 focus:border-primary-500"
        />
      </div>
      
      <!-- Actions -->
      <div class="flex justify-between pt-2 border-t border-gray-200 dark:border-gray-600">
        <button
          @click="handleClear"
          class="px-3 py-1 text-xs text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200 transition-colors"
        >
          Clear
        </button>
        
        <div class="flex space-x-2">
          <button
            @click="handleClose"
            class="px-3 py-1 text-xs border border-gray-300 dark:border-gray-600 rounded text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
          >
            Cancel
          </button>
          <button
            @click="handleApply"
            :disabled="!isValid"
            class="px-3 py-1 text-xs bg-primary-600 hover:bg-primary-700 disabled:bg-gray-400 text-white rounded transition-colors"
          >
            Apply
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import type { DataTableColumn, DataTableFilter } from '../types'

interface Props {
  column: DataTableColumn
  currentFilter?: DataTableFilter
}

interface Emits {
  (e: 'apply', filter: DataTableFilter): void
  (e: 'clear', columnKey: string): void
  (e: 'close'): void
}

const props = defineProps<Props>()
const emit = defineEmits<Emits>()

// Local state
const filterOperator = ref(props.currentFilter?.operator || 'contains')
const filterValue = ref(props.currentFilter?.value || '')
const filterValue2 = ref(props.currentFilter?.value2 || '')

// Computed
const availableOperators = computed(() => {
  const baseOperators = [
    { value: 'equals', label: 'Equals' },
    { value: 'not_equals', label: 'Not equals' },
    { value: 'is_empty', label: 'Is empty' },
    { value: 'is_not_empty', label: 'Is not empty' }
  ]
  
  switch (props.column.type) {
    case 'text':
    case 'email':
    case 'url':
      return [
        ...baseOperators,
        { value: 'contains', label: 'Contains' },
        { value: 'not_contains', label: 'Does not contain' },
        { value: 'starts_with', label: 'Starts with' },
        { value: 'ends_with', label: 'Ends with' }
      ]
    
    case 'number':
    case 'currency':
    case 'date':
      return [
        ...baseOperators,
        { value: 'greater_than', label: 'Greater than' },
        { value: 'less_than', label: 'Less than' },
        { value: 'between', label: 'Between' }
      ]
    
    case 'boolean':
      return [
        { value: 'equals', label: 'Equals' },
        { value: 'not_equals', label: 'Not equals' }
      ]
    
    case 'select':
      return baseOperators
    
    default:
      return baseOperators
  }
})

const inputType = computed(() => {
  switch (props.column.type) {
    case 'number':
    case 'currency':
      return 'number'
    case 'date':
      return 'date'
    case 'boolean':
      return 'boolean'
    case 'select':
      return 'select'
    default:
      return 'text'
  }
})

const needsValue = computed(() => {
  return !['is_empty', 'is_not_empty'].includes(filterOperator.value)
})

const isValid = computed(() => {
  if (!needsValue.value) return true
  
  if (filterOperator.value === 'between') {
    return filterValue.value !== '' && filterValue2.value !== ''
  }
  
  return filterValue.value !== ''
})

// Methods
const getPlaceholder = (): string => {
  switch (props.column.type) {
    case 'email':
      return 'user@example.com'
    case 'url':
      return 'https://example.com'
    case 'number':
      return '123'
    case 'currency':
      return '100.00'
    default:
      return 'Enter value...'
  }
}

const handleApply = () => {
  if (!isValid.value) return
  
  const filter: DataTableFilter = {
    column: props.column.key,
    operator: filterOperator.value as any,
    value: filterValue.value,
    value2: filterOperator.value === 'between' ? filterValue2.value : undefined
  }
  
  emit('apply', filter)
}

const handleClear = () => {
  emit('clear', props.column.key)
}

const handleClose = () => {
  emit('close')
}

// Reset value when operator changes to one that doesn't need value
watch(filterOperator, (newOperator) => {
  if (['is_empty', 'is_not_empty'].includes(newOperator)) {
    filterValue.value = ''
    filterValue2.value = ''
  }
})
</script>

<style scoped>
.filter-dropdown {
  min-width: 250px;
  max-width: 350px;
}
</style>

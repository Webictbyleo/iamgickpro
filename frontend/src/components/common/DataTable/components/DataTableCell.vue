<template>
  <td
    :class="cellClasses"
    :style="cellStyle"
    :data-cell="`${row}-${col}`"
    @click="handleClick"
    @dblclick="handleDoubleClick"
    @mousedown="handleMouseDown"
    @contextmenu="handleContextMenu"
  >
    <!-- Editing mode -->
    <div v-if="isEditing" class="cell-editor">
      <!-- Text input -->
      <input
        v-if="editorType === 'input'"
        ref="inputRef"
        v-model="tempValue"
        :type="inputType"
        :placeholder="column.placeholder"
        :class="inputClasses"
        @blur="handleBlur"
        @keydown="handleKeydown"
        @input="handleInput"
      />
      
      <!-- Textarea -->
      <textarea
        v-else-if="editorType === 'textarea'"
        ref="textareaRef"
        v-model="tempValue"
        :placeholder="column.placeholder"
        :class="textareaClasses"
        @blur="handleBlur"
        @keydown="handleKeydown"
        @input="handleInput"
      />
      
      <!-- Select dropdown -->
      <select
        v-else-if="editorType === 'select'"
        ref="selectRef"
        v-model="tempValue"
        :class="selectClasses"
        @blur="handleBlur"
        @keydown="handleKeydown"
        @change="handleSelectChange"
      >
        <option value="" disabled>{{ column.placeholder || 'Select...' }}</option>
        <option
          v-for="option in column.options"
          :key="option.value"
          :value="option.value"
          :disabled="option.disabled"
          :class="option.className"
        >
          {{ option.label }}
        </option>
      </select>
      
      <!-- Date input -->
      <input
        v-else-if="editorType === 'date'"
        ref="dateRef"
        v-model="tempValue"
        type="date"
        :class="inputClasses"
        @blur="handleBlur"
        @keydown="handleKeydown"
        @change="handleDateChange"
      />
      
      <!-- Checkbox -->
      <input
        v-else-if="editorType === 'checkbox'"
        ref="checkboxRef"
        v-model="tempValue"
        type="checkbox"
        :class="checkboxClasses"
        @blur="handleBlur"
        @keydown="handleKeydown"
        @change="handleCheckboxChange"
      />
      
      <!-- Custom editor component -->
      <component
        v-else-if="editorType === 'custom' && column.editor?.component"
        :is="column.editor.component"
        ref="customRef"
        v-model="tempValue"
        v-bind="column.editor.props"
        @blur="handleBlur"
        @keydown="handleKeydown"
        @change="handleCustomChange"
      />
    </div>
    
    <!-- Display mode -->
    <div v-else class="cell-display">
      <!-- Loading state -->
      <div v-if="loading" class="flex items-center space-x-2">
        <div class="w-3 h-3 bg-gray-200 dark:bg-gray-600 rounded animate-pulse"></div>
        <div class="h-3 bg-gray-200 dark:bg-gray-600 rounded flex-1 animate-pulse"></div>
      </div>
      
      <!-- Error state -->
      <div v-else-if="hasError" class="flex items-center space-x-1 text-danger-600 dark:text-danger-400">
        <svg class="w-3 h-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
          <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
        </svg>
        <span class="text-xs truncate" :title="validation.error || undefined">{{ validation.error }}</span>
      </div>
      
      <!-- Warning state -->
      <div v-else-if="hasWarnings" class="flex items-center space-x-1 text-warning-600 dark:text-warning-400">
        <svg class="w-3 h-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
          <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
        </svg>
        <span class="text-xs truncate">{{ displayValue }}</span>
      </div>
      
      <!-- Normal display -->
      <div v-else class="cell-content">
        <!-- Boolean display -->
        <div v-if="column.type === 'boolean'" class="flex items-center justify-center">
          <input
            type="checkbox"
            :checked="!!cellValue"
            disabled
            class="w-4 h-4 text-primary-600 bg-gray-100 border-gray-300 rounded focus:ring-0 cursor-default"
          />
        </div>
        
        <!-- Currency display -->
        <div v-else-if="column.type === 'currency'" class="text-right font-mono">
          {{ formatCurrency(cellValue) }}
        </div>
        
        <!-- Number display -->
        <div v-else-if="column.type === 'number'" class="text-right font-mono">
          {{ formatNumber(cellValue) }}
        </div>
        
        <!-- Date display -->
        <div v-else-if="column.type === 'date'">
          {{ formatDate(cellValue) }}
        </div>
        
        <!-- Email display -->
        <a 
          v-else-if="column.type === 'email' && cellValue"
          :href="`mailto:${cellValue}`"
          class="text-primary-600 dark:text-primary-400 hover:underline"
          @click.stop
        >
          {{ cellValue }}
        </a>
        
        <!-- URL display -->
        <a 
          v-else-if="column.type === 'url' && cellValue"
          :href="cellValue"
          target="_blank"
          rel="noopener noreferrer"
          class="text-primary-600 dark:text-primary-400 hover:underline"
          @click.stop
        >
          {{ displayValue }}
        </a>
        
        <!-- Select display -->
        <div v-else-if="column.type === 'select'">
          {{ getSelectLabel(cellValue) }}
        </div>
        
        <!-- Custom renderer -->
        <component
          v-else-if="column.renderer?.component"
          :is="column.renderer.component"
          :value="cellValue"
          :row="rowData"
          :column="column"
          v-bind="column.renderer.props"
        />
        
        <!-- Default text display -->
        <div v-else :class="textClasses" :title="displayValue">
          {{ displayValue }}
        </div>
      </div>
    </div>
    
    <!-- Cell indicators -->
    <div v-if="showIndicators" class="cell-indicators absolute top-0 right-0 flex">
      <!-- Dirty indicator -->
      <div
        v-if="isDirty"
        class="w-2 h-2 bg-warning-500 rounded-full"
        title="Modified"
      ></div>
      
      <!-- Required indicator -->
      <div
        v-if="column.required && isEmpty"
        class="w-2 h-2 bg-danger-500 rounded-full ml-1"
        title="Required field"
      ></div>
    </div>
  </td>
</template>

<script setup lang="ts">
import { ref, computed, nextTick, inject, watch, type Ref } from 'vue'
import type { DataTableColumn, DataTableRow, CellState } from '../types'

interface Props {
  column: DataTableColumn
  row: number
  col: number
  rowData: DataTableRow
  value: any
  editing?: boolean
  selected?: boolean
  loading?: boolean
  className?: string
}

interface Emits {
  (e: 'click', row: number, col: number, event: MouseEvent): void
  (e: 'dblclick', row: number, col: number): void
  (e: 'edit-start', row: number, col: number): void
  (e: 'edit-save', value: any): void
  (e: 'edit-cancel'): void
  (e: 'context-menu', row: number, col: number, event: MouseEvent): void
}

const props = withDefaults(defineProps<Props>(), {
  editing: false,
  selected: false,
  loading: false,
  className: ''
})

const emit = defineEmits<Emits>()

// Template refs
const inputRef = ref<HTMLInputElement>()
const textareaRef = ref<HTMLTextAreaElement>()
const selectRef = ref<HTMLSelectElement>()
const dateRef = ref<HTMLInputElement>()
const checkboxRef = ref<HTMLInputElement>()
const customRef = ref()

// Inject data table context
const dataTable = inject('dataTable', {
  getCellValidation: () => ({ error: null, warnings: [], hasError: false, hasWarnings: false })
})

// Local state
const tempValue = ref<any>('')
const isDirty = ref(false)
const originalValue = ref(props.value)

// Computed properties
const cellValue = computed(() => props.value)

const isEditing = computed(() => props.editing)

const validation = computed(() => 
  dataTable.getCellValidation ? dataTable.getCellValidation() : {
    error: null,
    warnings: [],
    hasError: false,
    hasWarnings: false
  }
)

const hasError = computed(() => validation.value.hasError)
const hasWarnings = computed(() => validation.value.hasWarnings)

const isEmpty = computed(() => 
  cellValue.value === null || 
  cellValue.value === undefined || 
  cellValue.value === ''
)

const showIndicators = computed(() => 
  isDirty.value || (props.column.required && isEmpty.value)
)

const editorType = computed(() => {
  if (props.column.editor?.type) {
    return props.column.editor.type
  }
  
  switch (props.column.type) {
    case 'textarea':
      return 'textarea'
    case 'select':
      return 'select'
    case 'date':
      return 'date'
    case 'boolean':
      return 'checkbox'
    case 'email':
    case 'url':
    case 'number':
    case 'currency':
    case 'text':
    default:
      return 'input'
  }
})

const inputType = computed(() => {
  switch (props.column.type) {
    case 'email':
      return 'email'
    case 'url':
      return 'url'
    case 'number':
    case 'currency':
      return 'number'
    case 'password':
      return 'password'
    default:
      return 'text'
  }
})

const displayValue = computed(() => {
  if (cellValue.value === null || cellValue.value === undefined) {
    return ''
  }
  
  if (props.column.formatter) {
    return props.column.formatter(cellValue.value, props.rowData.data)
  }
  
  return String(cellValue.value)
})

const cellClasses = computed(() => [
  'relative px-3 py-2 border-r border-gray-200 dark:border-gray-600 transition-colors',
  {
    'bg-primary-50 dark:bg-primary-900/20': props.selected,
    'bg-white dark:bg-gray-700 ring-2 ring-primary-500': isEditing.value,
    'bg-danger-50 dark:bg-danger-900/20': hasError.value,
    'bg-warning-50 dark:bg-warning-900/20': hasWarnings.value,
    'cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700/50': !isEditing.value,
    [`text-${props.column.align || 'left'}`]: true
  },
  props.className,
  props.column.cellClassName
])

const cellStyle = computed(() => ({
  width: props.column.width ? `${props.column.width}px` : undefined,
  minWidth: props.column.minWidth ? `${props.column.minWidth}px` : undefined,
  maxWidth: props.column.maxWidth ? `${props.column.maxWidth}px` : undefined
}))

const inputClasses = computed(() => [
  'w-full px-2 py-1 text-sm border-0 bg-transparent focus:ring-0 focus:outline-none',
  'text-gray-900 dark:text-gray-100',
  {
    'text-right': props.column.type === 'number' || props.column.type === 'currency'
  }
])

const textareaClasses = computed(() => [
  'w-full px-2 py-1 text-sm border-0 bg-transparent focus:ring-0 focus:outline-none resize-none',
  'text-gray-900 dark:text-gray-100'
])

const selectClasses = computed(() => [
  'w-full px-2 py-1 text-sm border-0 bg-transparent focus:ring-0 focus:outline-none',
  'text-gray-900 dark:text-gray-100'
])

const checkboxClasses = computed(() => [
  'w-4 h-4 text-primary-600 bg-gray-100 border-gray-300 rounded focus:ring-primary-500'
])

const textClasses = computed(() => [
  'truncate',
  {
    'font-mono': props.column.type === 'number' || props.column.type === 'currency',
    'text-gray-500 dark:text-gray-400 italic': isEmpty.value
  }
])

// Methods
const handleClick = (event: MouseEvent) => {
  emit('click', props.row, props.col, event)
}

const handleDoubleClick = () => {
  if (props.column.editable) {
    emit('dblclick', props.row, props.col)
  }
}

const handleMouseDown = (event: MouseEvent) => {
  // Handle selection drag start
  if (event.button === 0) { // Left click only
    // Implement drag selection logic
  }
}

const handleContextMenu = (event: MouseEvent) => {
  emit('context-menu', props.row, props.col, event)
}

const handleBlur = () => {
  if (isEditing.value) {
    saveEdit()
  }
}

const handleKeydown = (event: KeyboardEvent) => {
  switch (event.key) {
    case 'Enter':
      event.preventDefault()
      saveEdit()
      break
    case 'Escape':
      event.preventDefault()
      cancelEdit()
      break
    case 'Tab':
      // Let the parent handle tab navigation
      saveEdit()
      break
  }
}

const handleInput = (event: Event) => {
  const target = event.target as HTMLInputElement
  tempValue.value = target.value
  isDirty.value = tempValue.value !== originalValue.value
}

const handleSelectChange = (event: Event) => {
  const target = event.target as HTMLSelectElement
  tempValue.value = target.value
  isDirty.value = tempValue.value !== originalValue.value
  saveEdit() // Auto-save on select change
}

const handleDateChange = (event: Event) => {
  const target = event.target as HTMLInputElement
  tempValue.value = target.value
  isDirty.value = tempValue.value !== originalValue.value
}

const handleCheckboxChange = (event: Event) => {
  const target = event.target as HTMLInputElement
  tempValue.value = target.checked
  isDirty.value = tempValue.value !== originalValue.value
  saveEdit() // Auto-save on checkbox change
}

const handleCustomChange = (value: any) => {
  tempValue.value = value
  isDirty.value = tempValue.value !== originalValue.value
}

const saveEdit = () => {
  let finalValue = tempValue.value
  
  // Parse value based on column type
  if (props.column.parser) {
    finalValue = props.column.parser(tempValue.value)
  } else {
    switch (props.column.type) {
      case 'number':
      case 'currency':
        finalValue = tempValue.value === '' ? null : Number(tempValue.value)
        break
      case 'date':
        finalValue = tempValue.value ? new Date(tempValue.value) : null
        break
      case 'boolean':
        finalValue = Boolean(tempValue.value)
        break
    }
  }
  
  emit('edit-save', finalValue)
  isDirty.value = false
}

const cancelEdit = () => {
  tempValue.value = formatValueForEditing(originalValue.value)
  isDirty.value = false
  emit('edit-cancel')
}

const formatValueForEditing = (value: any): string => {
  if (value === null || value === undefined) {
    return ''
  }
  
  switch (props.column.type) {
    case 'date':
      if (value instanceof Date) {
        return value.toISOString().split('T')[0]
      }
      return String(value)
    case 'boolean':
      return String(Boolean(value))
    default:
      return String(value)
  }
}

const formatCurrency = (value: any): string => {
  if (value === null || value === undefined || isNaN(Number(value))) {
    return ''
  }
  
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD'
  }).format(Number(value))
}

const formatNumber = (value: any): string => {
  if (value === null || value === undefined || isNaN(Number(value))) {
    return ''
  }
  
  return new Intl.NumberFormat().format(Number(value))
}

const formatDate = (value: any): string => {
  if (!value) return ''
  
  const date = value instanceof Date ? value : new Date(value)
  if (isNaN(date.getTime())) return String(value)
  
  return date.toLocaleDateString()
}

const getSelectLabel = (value: any): string => {
  if (!props.column.options) return String(value || '')
  
  const option = props.column.options.find(opt => opt.value === value)
  return option ? option.label : String(value || '')
}

// Focus the editor when editing starts
watch(() => props.editing, (editing) => {
  if (editing) {
    tempValue.value = formatValueForEditing(props.value)
    originalValue.value = props.value
    isDirty.value = false
    
    nextTick(() => {
      const editor = inputRef.value || textareaRef.value || selectRef.value || dateRef.value || checkboxRef.value || customRef.value?.$el
      if (editor && typeof editor.focus === 'function') {
        editor.focus()
        
        // Select text for text inputs
        if (editor instanceof HTMLInputElement && editor.type === 'text') {
          editor.select()
        }
      }
    })
  }
})

// Watch for value changes from outside
watch(() => props.value, (newValue) => {
  if (!isEditing.value) {
    originalValue.value = newValue
    isDirty.value = false
  }
})
</script>

<style scoped>
.cell-editor {
  min-height: 100%;
  display: flex;
  align-items: center;
}

.cell-display {
  min-height: 100%;
  display: flex;
  align-items: center;
}

.cell-content {
  width: 100%;
  min-height: 1.5rem;
  display: flex;
  align-items: center;
}

.cell-indicators {
  pointer-events: none;
}

/* Remove default focus styles and add custom ones */
input:focus,
textarea:focus,
select:focus {
  outline: none;
  box-shadow: none;
}

/* Ensure proper text truncation */
.truncate {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
</style>

<template>
  <thead class="bg-gray-50 dark:bg-gray-700" :class="{ 'sticky top-0 z-10': sticky }">
    <tr>
      <!-- Checkbox column -->
      <th
        v-if="showCheckboxes"
        class="w-12 px-3 py-3 text-left border-r border-gray-200 dark:border-gray-600"
      >
        <input
          type="checkbox"
          :checked="allSelected"
          :indeterminate="someSelected"
          @change="handleSelectAll"
          class="w-4 h-4 text-primary-600 bg-gray-100 border-gray-300 rounded focus:ring-primary-500"
        />
      </th>
      
      <!-- Row numbers column -->
      <th
        v-if="showRowNumbers"
        class="w-16 px-3 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 border-r border-gray-200 dark:border-gray-600"
      >
        #
      </th>
      
      <!-- Data columns -->
      <th
        v-for="(column, index) in columns"
        :key="column.key"
        :class="getHeaderClasses(column)"
        :style="getHeaderStyle(column)"
        @click="handleHeaderClick(column, $event)"
        @mousedown="handleResizeStart(column, index, $event)"
      >
        <div class="flex items-center justify-between group">
          <!-- Column content -->
          <div class="flex items-center space-x-2 min-w-0 flex-1">
            <!-- Column icon -->
            <component
              v-if="column.icon"
              :is="column.icon"
              class="w-4 h-4 flex-shrink-0 text-gray-400"
            />
            
            <!-- Column label -->
            <span class="truncate font-medium text-gray-700 dark:text-gray-300">
              {{ column.label }}
            </span>
            
            <!-- Required indicator -->
            <span
              v-if="column.required"
              class="text-danger-500 text-xs"
              title="Required field"
            >
              *
            </span>
          </div>
          
          <!-- Column actions -->
          <div class="flex items-center space-x-1 opacity-0 group-hover:opacity-100 transition-opacity">
            <!-- Sort indicator -->
            <div v-if="column.sortable" class="flex flex-col">
              <ChevronUpIcon
                :class="[
                  'w-3 h-3 transition-colors',
                  getSortDirection(column.key) === 'asc' 
                    ? 'text-primary-600 dark:text-primary-400' 
                    : 'text-gray-300 dark:text-gray-600'
                ]"
              />
              <ChevronDownIcon
                :class="[
                  'w-3 h-3 -mt-1 transition-colors',
                  getSortDirection(column.key) === 'desc' 
                    ? 'text-primary-600 dark:text-primary-400' 
                    : 'text-gray-300 dark:text-gray-600'
                ]"
              />
            </div>
            
            <!-- Filter indicator -->
            <FunnelIcon
              v-if="column.filterable && hasFilter(column.key)"
              class="w-4 h-4 text-primary-600 dark:text-primary-400"
            />
            
            <!-- Filter button -->
            <button
              v-if="column.filterable"
              @click.stop="toggleFilter(column, index)"
              class="w-4 h-4 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
              :title="`Filter ${column.label}`"
            >
              <AdjustmentsHorizontalIcon class="w-4 h-4" />
            </button>
            
            <!-- Resize handle -->
            <div
              v-if="column.resizable !== false"
              class="w-1 h-6 bg-gray-300 dark:bg-gray-600 cursor-col-resize opacity-0 group-hover:opacity-100 transition-opacity"
              @mousedown.stop="handleResizeStart(column, index, $event)"
            />
          </div>
        </div>
        
        <!-- Filter dropdown -->
        <div
          v-if="activeFilter === index"
          class="absolute top-full left-0 mt-1 p-3 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded-lg shadow-lg z-20 min-w-64"
          @click.stop
        >
          <HeaderFilter
            :column="column"
            :current-filter="getFilter(column.key)"
            @apply="handleFilterApply"
            @clear="handleFilterClear"
            @close="closeFilter"
          />
        </div>
      </th>
    </tr>
  </thead>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import {
  ChevronUpIcon,
  ChevronDownIcon,
  FunnelIcon,
  AdjustmentsHorizontalIcon
} from '@heroicons/vue/24/outline'
import type { 
  DataTableColumn, 
  DataTableSort, 
  DataTableFilter, 
  SelectionState 
} from '../types'
import HeaderFilter from './HeaderFilter.vue'

interface Props {
  columns: DataTableColumn[]
  sorts: DataTableSort[]
  filters: DataTableFilter[]
  selection: SelectionState
  sticky?: boolean
  showCheckboxes?: boolean
  showRowNumbers?: boolean
}

interface Emits {
  (e: 'sort', column: string, direction: 'asc' | 'desc', multiSort: boolean): void
  (e: 'filter', filter: DataTableFilter): void
  (e: 'select-all', selected: boolean): void
  (e: 'column-resize', column: string, width: number): void
}

const props = withDefaults(defineProps<Props>(), {
  sticky: true,
  showCheckboxes: true,
  showRowNumbers: false
})

const emit = defineEmits<Emits>()

// Local state
const activeFilter = ref<number | null>(null)
const resizing = ref<{
  column: DataTableColumn
  startX: number
  startWidth: number
} | null>(null)

// Computed
const allSelected = computed(() => 
  props.selection.selectedRows.size > 0 && 
  // Check if all visible rows are selected (this would need data from parent)
  true // Simplified for now
)

const someSelected = computed(() => 
  props.selection.selectedRows.size > 0 && !allSelected.value
)

// Methods
const getHeaderClasses = (column: DataTableColumn) => [
  'relative px-3 py-3 text-left text-xs font-medium border-r border-gray-200 dark:border-gray-600',
  {
    'cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-600': column.sortable,
    'bg-primary-50 dark:bg-primary-900/20': props.selection.selectedColumns.has(column.key),
    [`text-${column.align || 'left'}`]: true
  },
  column.headerClassName
]

const getHeaderStyle = (column: DataTableColumn) => ({
  width: column.width ? `${column.width}px` : undefined,
  minWidth: column.minWidth ? `${column.minWidth}px` : undefined,
  maxWidth: column.maxWidth ? `${column.maxWidth}px` : undefined
})

const getSortDirection = (columnKey: string): 'asc' | 'desc' | null => {
  const sort = props.sorts.find(s => s.column === columnKey)
  return sort ? sort.direction : null
}

const hasFilter = (columnKey: string): boolean => {
  return props.filters.some(f => f.column === columnKey)
}

const getFilter = (columnKey: string): DataTableFilter | undefined => {
  return props.filters.find(f => f.column === columnKey)
}

const handleHeaderClick = (column: DataTableColumn, event: MouseEvent) => {
  if (!column.sortable) return
  
  const currentDirection = getSortDirection(column.key)
  let newDirection: 'asc' | 'desc' = 'asc'
  
  if (currentDirection === 'asc') {
    newDirection = 'desc'
  } else if (currentDirection === 'desc') {
    newDirection = 'asc'
  }
  
  emit('sort', column.key, newDirection, event.ctrlKey || event.metaKey)
}

const handleSelectAll = (event: Event) => {
  const target = event.target as HTMLInputElement
  emit('select-all', target.checked)
}

const toggleFilter = (column: DataTableColumn, index: number) => {
  if (activeFilter.value === index) {
    activeFilter.value = null
  } else {
    activeFilter.value = index
  }
}

const closeFilter = () => {
  activeFilter.value = null
}

const handleFilterApply = (filter: DataTableFilter) => {
  emit('filter', filter)
  closeFilter()
}

const handleFilterClear = (columnKey: string) => {
  const filter: DataTableFilter = {
    column: columnKey,
    operator: 'equals',
    value: null
  }
  emit('filter', filter)
  closeFilter()
}

const handleResizeStart = (column: DataTableColumn, index: number, event: MouseEvent) => {
  if (column.resizable === false) return
  
  event.preventDefault()
  
  const rect = (event.currentTarget as HTMLElement).getBoundingClientRect()
  
  resizing.value = {
    column,
    startX: event.clientX,
    startWidth: rect.width
  }
  
  document.addEventListener('mousemove', handleResize)
  document.addEventListener('mouseup', handleResizeEnd)
}

const handleResize = (event: MouseEvent) => {
  if (!resizing.value) return
  
  const deltaX = event.clientX - resizing.value.startX
  const newWidth = Math.max(
    resizing.value.column.minWidth || 100,
    Math.min(
      resizing.value.column.maxWidth || 500,
      resizing.value.startWidth + deltaX
    )
  )
  
  emit('column-resize', resizing.value.column.key, newWidth)
}

const handleResizeEnd = () => {
  if (resizing.value) {
    resizing.value = null
  }
  
  document.removeEventListener('mousemove', handleResize)
  document.removeEventListener('mouseup', handleResizeEnd)
}

// Close filter when clicking outside
const handleGlobalClick = (event: Event) => {
  if (activeFilter.value !== null) {
    const target = event.target as HTMLElement
    if (!target.closest('.filter-dropdown')) {
      closeFilter()
    }
  }
}

// Lifecycle
document.addEventListener('click', handleGlobalClick)
</script>

<style scoped>
/* Custom styles for better visual hierarchy */
th {
  user-select: none;
}

.resize-handle:hover {
  background-color: theme('colors.primary.500');
}

/* Sort indicator animation */
.sort-indicator {
  transition: all 0.15s ease;
}

/* Filter dropdown positioning */
.filter-dropdown {
  min-width: 250px;
  max-width: 400px;
}
</style>

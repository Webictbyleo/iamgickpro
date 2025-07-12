<template>
  <div 
    class="datatable-container bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded-lg overflow-hidden"
    :class="className"
  >
    <!-- Toolbar -->
    <DataTableToolbar
      v-if="showToolbar"
      :selected-rows="selectedRows"
      :has-selection="hasSelection"
      :can-undo="canUndo"
      :can-redo="canRedo"
      :filters="filters"
      :sorts="sorts"
      @add-row="handleAddRow"
      @delete-rows="handleDeleteRows"
      @duplicate-rows="handleDuplicateRows"
      @export="handleExport"
      @import="handleImport"
      @undo="undo"
      @redo="redo"
      @clear-filters="clearFilters"
      @clear-sorts="clearSorts"
    />
    
    <!-- Loading overlay -->
    <div 
      v-if="loading"
      class="absolute inset-0 bg-white/80 dark:bg-gray-800/80 flex items-center justify-center z-10"
    >
      <div class="flex items-center space-x-2 text-gray-600 dark:text-gray-300">
        <div class="animate-spin w-4 h-4 border-2 border-primary-600 border-t-transparent rounded-full"></div>
        <span class="text-sm">{{ loadingMessage || 'Loading...' }}</span>
      </div>
    </div>
    
    <!-- Error state -->
    <div 
      v-if="error"
      class="p-4 bg-danger-50 dark:bg-danger-900/20 text-danger-700 dark:text-danger-300 text-sm"
    >
      {{ errorMessage || error }}
    </div>
    
    <!-- Table container with virtual scrolling support -->
    <div 
      ref="tableContainer"
      class="overflow-auto"
      :style="{ 
        maxHeight: maxHeight, 
        minHeight: minHeight,
        height: virtualScrolling ? '400px' : 'auto'
      }"
      @scroll="handleScroll"
    >
      <!-- Table -->
      <table class="w-full text-sm">
        <!-- Header -->
        <DataTableHeader
          :columns="visibleColumns"
          :sorts="sorts"
          :filters="filters"
          :selection="selection"
          :sticky="stickyHeader"
          :show-checkboxes="showCheckboxes"
          :show-row-numbers="showRowNumbers"
          @sort="handleSort"
          @filter="handleFilter"
          @select-all="handleSelectAll"
          @column-resize="handleColumnResize"
        />
        
        <!-- Body -->
        <DataTableBody
          :columns="visibleColumns"
          :data="displayData"
          :selection="selection"
          :editing="editing"
          :show-checkboxes="showCheckboxes"
          :show-row-numbers="showRowNumbers"
          :row-height="rowHeight"
          :virtual-scrolling="virtualScrolling"
          :visible-range="visibleRange"
          @cell-click="handleCellClick"
          @cell-double-click="handleCellDoubleClick"
          @cell-edit="handleCellEdit"
          @cell-change="handleCellChange"
          @row-select="handleRowSelect"
          @row-context-menu="handleRowContextMenu"
        />
      </table>
    </div>
    
    <!-- Empty state -->
    <div 
      v-if="!loading && displayData.length === 0"
      class="p-8 text-center text-gray-500 dark:text-gray-400"
    >
      <div class="mb-2">
        <svg class="w-12 h-12 mx-auto text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
        </svg>
      </div>
      <p class="text-sm">{{ emptyMessage || 'No data available' }}</p>
      <button
        v-if="editable"
        @click="handleAddRow"
        class="mt-3 px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white text-sm rounded-lg transition-colors"
      >
        Add First Row
      </button>
    </div>
    
    <!-- Pagination -->
    <DataTablePagination
      v-if="pagination && pagination.totalPages > 1"
      :pagination="pagination"
      @update:pagination="updatePagination"
    />
    
    <!-- Context Menu -->
    <DataTableContextMenu
      v-if="contextMenu.visible"
      :visible="contextMenu.visible"
      :x="contextMenu.x"
      :y="contextMenu.y"
      :target="contextMenu.target"
      :items="contextMenuItems"
      @close="closeContextMenu"
      @action="handleContextMenuAction"
    />
  </div>
</template>

<script setup lang="ts" generic="T">
import { ref, computed, onMounted, onUnmounted, provide, watch, type Ref } from 'vue'
import type { 
  DataTableProps, 
  DataTableEmits, 
  DataTableRow,
  DataTableColumn,
  DataTableFilter,
  DataTableSort,
  DataTablePagination as DataTablePaginationType,
  CellState,
  ContextMenuState,
  ContextMenuItem
} from './types'
import { useDataTable } from './composables/useDataTable'
import { useTableEditing } from './composables/useTableEditing'
import { useTableSelection } from './composables/useTableSelection'
import { useTableKeyboard } from './composables/useTableKeyboard'
import DataTableHeader from './components/DataTableHeader.vue'
import DataTableBody from './components/DataTableBody.vue'
import DataTableToolbar from './components/DataTableToolbar.vue'
import DataTablePagination from './components/DataTablePagination.vue'
import DataTableContextMenu from './components/DataTableContextMenu.vue'
import formatters from './utils/formatters'

// Props with defaults
const props = withDefaults(defineProps<DataTableProps<T>>(), {
  sortable: true,
  filterable: true,
  selectable: true,
  editable: true,
  virtualScrolling: false,
  stickyHeader: true,
  showRowNumbers: false,
  showCheckboxes: true,
  rowHeight: 40,
  maxHeight: '600px',
  minHeight: '200px',
  className: '',
  emptyMessage: 'No data available',
  loadingMessage: 'Loading...',
  errorMessage: ''
})

// Emits
const emit = defineEmits<DataTableEmits<T>>()

// Template refs
const tableContainer = ref<HTMLElement>()

// Core composables
const {
  columns,
  data,
  loading,
  error,
  pagination,
  sorts,
  filters,
  selection,
  editing,
  visibleColumns,
  filteredData,
  paginatedData,
  selectedRows,
  hasSelection,
  canUndo,
  canRedo,
  updatePagination,
  addSort,
  removeSort,
  clearSorts,
  addFilter,
  removeFilter,
  clearFilters,
  selectRow,
  deselectRow,
  selectAllRows,
  deselectAllRows,
  addRow,
  removeRow,
  duplicateRow,
  undo,
  redo
} = useDataTable({
  columns: props.columns,
  data: props.data,
  pagination: props.pagination,
  sortable: props.sortable,
  filterable: props.filterable,
  selectable: props.selectable,
  editable: props.editable
})

// Editing composable
const {
  editingCell,
  isEditing,
  startEditing,
  saveEdit,
  cancelEditing,
  handleEditKeydown,
  getCellValidation
} = useTableEditing({
  columns,
  data: data as Ref<DataTableRow<T>[]>,
  autoSave: true,
  saveDelay: 1000,
  onCellChange: (cellState, oldValue) => {
    emit('cell-change', cellState, oldValue)
  }
})

// Selection composable
const {
  handleCellSelection,
  handleRowSelection,
  handleRangeSelection
} = useTableSelection({
  columns,
  data: data as Ref<DataTableRow<T>[]>,
  selection
})

// Keyboard navigation
const {
  handleKeyDown
} = useTableKeyboard({
  columns,
  data: data as Ref<DataTableRow<T>[]>,
  selection,
  editing: { editingCell, isEditing },
  startEditing,
  saveEdit,
  cancelEditing
})

// Context menu state
const contextMenu = ref<ContextMenuState>({
  visible: false,
  x: 0,
  y: 0,
  target: { type: 'cell' }
})

// Virtual scrolling
const visibleRange = ref({ start: 0, end: 50 })

// Computed properties
const displayData = computed(() => {
  if (props.virtualScrolling) {
    const { start, end } = visibleRange.value
    return filteredData.value.slice(start, end)
  }
  return props.pagination ? paginatedData.value : filteredData.value
})

const showToolbar = computed(() => 
  props.editable || props.filterable || props.sortable
)

// Cell formatting function using formatters utility
const formatCellValue = (value: any, column: DataTableColumn<T>): string => {
  try {
    // Use custom formatter if provided
    if (column.formatter) {
      return column.formatter(value, undefined)
    }
    
    // Use built-in formatters based on column type
    switch (column.type) {
      case 'number':
        return formatters.number(value, 2)
      case 'currency':
        return formatters.currency(value, 'USD')
      case 'date':
        return formatters.date(value, 'medium')
      case 'boolean':
        return formatters.boolean(value, 'yes-no')
      case 'email':
      case 'url':
        return formatters.text(value)
      case 'password':
        return value ? '••••••••' : ''
      case 'textarea':
        return formatters.truncate(value, 100)
      case 'text':
      case 'select':
      default:
        return formatters.text(value)
    }
  } catch (error) {
    console.error('Error formatting cell value:', error)
    return String(value || '')
  }
}

// Provide formatters to child components
provide('formatCellValue', formatCellValue)
provide('formatters', formatters)

const contextMenuItems = computed((): ContextMenuItem[] => {
  const items: ContextMenuItem[] = []
  
  if (props.editable) {
    items.push(
      { key: 'edit', label: 'Edit Cell', icon: 'pencil' },
      { key: 'copy', label: 'Copy', icon: 'clipboard' },
      { key: 'paste', label: 'Paste', icon: 'clipboard' },
      { separator: true, key: 'sep1', label: '' },
      { key: 'add-row', label: 'Add Row', icon: 'plus' },
      { key: 'duplicate-row', label: 'Duplicate Row', icon: 'duplicate' },
      { key: 'delete-row', label: 'Delete Row', icon: 'trash' }
    )
  }
  
  return items
})

// Event handlers
const handleCellClick = (row: number, col: number, event: MouseEvent) => {
  try {
    if (props.selectable && visibleColumns.value[col]) {
      handleCellSelection(row, col, event.ctrlKey || event.metaKey, event.shiftKey)
    }
  } catch (error) {
    console.error('Error handling cell click:', error)
  }
}

const handleCellDoubleClick = (row: number, col: number) => {
  try {
    if (props.editable && visibleColumns.value[col]?.editable !== false) {
      startEditing(row, col)
    }
  } catch (error) {
    console.error('Error handling cell double click:', error)
  }
}

const handleCellEdit = (row: number, col: number) => {
  try {
    const column = visibleColumns.value[col]
    if (!column) return
    
    const cellState: CellState = {
      row,
      col,
      key: column.key,
      editing: true,
      selected: true,
      value: null,
      originalValue: null,
      invalid: false,
      dirty: false
    }
    emit('cell-edit', cellState)
  } catch (error) {
    console.error('Error handling cell edit:', error)
  }
}

const handleCellChange = (row: number, col: number, value: any) => {
  try {
    const column = visibleColumns.value[col]
    if (!column) return
    
    const cellState: CellState = {
      row,
      col,
      key: column.key,
      editing: false,
      selected: false,
      value,
      originalValue: null,
      invalid: false,
      dirty: true
    }
    emit('cell-change', cellState, value)
  } catch (error) {
    console.error('Error handling cell change:', error)
  }
}

const handleRowSelect = (row: DataTableRow<T>, selected: boolean) => {
  try {
    if (selected) {
      selectRow(row.id)
      emit('row-select', row)
    } else {
      deselectRow(row.id)
      emit('row-deselect', row)
    }
    
    ;(emit as any)('selection-change', selectedRows.value)
  } catch (error) {
    console.error('Error handling row selection:', error)
  }
}

const handleSelectAll = (selected: boolean) => {
  try {
    if (selected) {
      selectAllRows()
    } else {
      deselectAllRows()
    }
    
    ;(emit as any)('selection-change', selectedRows.value)
  } catch (error) {
    console.error('Error handling select all:', error)
  }
}

const handleSort = (column: string, direction: 'asc' | 'desc', multiSort = false) => {
  addSort(column, direction, multiSort)
  emit('sort', sorts.value)
}

const handleFilter = (filter: DataTableFilter) => {
  addFilter(filter)
  emit('filter', filters.value)
}

const handleColumnResize = (column: string, width: number) => {
  // Implement column resizing logic
  console.log('Column resize:', column, width)
}

const handleAddRow = () => {
  const newRowData = {} as T
  // Initialize with default values based on column types
  visibleColumns.value.forEach(column => {
    switch (column.type) {
      case 'number':
      case 'currency':
        (newRowData as any)[column.key] = 0
        break
      case 'boolean':
        (newRowData as any)[column.key] = false
        break
      case 'date':
        (newRowData as any)[column.key] = new Date()
        break
      default:
        (newRowData as any)[column.key] = ''
    }
  })
  
  const newRow = addRow(newRowData)
  emit('row-add', newRow)
}

const handleDeleteRows = () => {
  try {
    const rowsToDelete = [...selectedRows.value] // Create a copy to avoid mutation during iteration
    rowsToDelete.forEach(row => {
      removeRow(row.id)
      ;(emit as any)('row-delete', row)
    })
    deselectAllRows()
  } catch (error) {
    console.error('Error deleting rows:', error)
  }
}

const handleDuplicateRows = () => {
  try {
    const rowsToDuplicate = [...selectedRows.value] // Create a copy
    rowsToDuplicate.forEach(row => {
      const duplicated = duplicateRow(row.id)
      if (duplicated) {
        emit('row-duplicate', duplicated)
      }
    })
  } catch (error) {
    console.error('Error duplicating rows:', error)
  }
}

const handleExport = (format: 'csv' | 'excel' | 'json') => {
  try {
    emit('export', format)
  } catch (error) {
    console.error('Error during export:', error)
  }
}

const handleImport = (data: any[]) => {
  try {
    emit('import', data)
  } catch (error) {
    console.error('Error during import:', error)
  }
}

const handleRowContextMenu = (row: number, event: MouseEvent) => {
  event.preventDefault()
  
  contextMenu.value = {
    visible: true,
    x: event.clientX,
    y: event.clientY,
    target: { type: 'row', row }
  }
}

const closeContextMenu = () => {
  contextMenu.value.visible = false
}

const handleContextMenuAction = (action: string) => {
  const { target } = contextMenu.value
  
  switch (action) {
    case 'edit':
      if (target.row !== undefined && target.col !== undefined) {
        startEditing(target.row, target.col)
      }
      break
    case 'add-row':
      handleAddRow()
      break
    case 'duplicate-row':
      if (target.row !== undefined) {
        const row = data.value[target.row]
        if (row) {
          duplicateRow(row.id)
        }
      }
      break
    case 'delete-row':
      if (target.row !== undefined) {
        const row = data.value[target.row]
        if (row) {
          removeRow(row.id)
        }
      }
      break
  }
  
  closeContextMenu()
}

const handleScroll = () => {
  if (!props.virtualScrolling || !tableContainer.value) return
  
  try {
    const { scrollTop, clientHeight } = tableContainer.value
    const itemHeight = props.rowHeight || 40
    
    const start = Math.floor(scrollTop / itemHeight)
    const viewportSize = Math.ceil(clientHeight / itemHeight)
    const bufferSize = Math.min(5, Math.floor(viewportSize * 0.5)) // Dynamic buffer
    const end = Math.min(start + viewportSize + bufferSize, data.value.length)
    
    // Only update if range actually changed to prevent unnecessary re-renders
    const newRange = { start: Math.max(0, start - bufferSize), end }
    if (newRange.start !== visibleRange.value.start || newRange.end !== visibleRange.value.end) {
      visibleRange.value = newRange
    }
  } catch (error) {
    console.error('Error handling scroll:', error)
  }
}

// Global keyboard event handler
const handleGlobalKeyDown = (event: KeyboardEvent) => {
  // Only handle events when component is focused or has active editing
  if (!tableContainer.value?.contains(document.activeElement) && !isEditing.value) {
    return
  }
  
  try {
    handleKeyDown(event)
    
    // Handle editing keyboard events
    if (isEditing.value) {
      handleEditKeydown(event)
    }
  } catch (error) {
    console.error('Error handling keyboard event:', error)
  }
}

// Global click handler for context menu
const handleGlobalClick = (event: Event) => {
  if (contextMenu.value.visible && !(event.target as Element)?.closest('.context-menu')) {
    closeContextMenu()
  }
}

// Provide context for child components
provide('dataTable', {
  columns,
  data,
  editing,
  selection,
  getCellValidation
})

// Lifecycle
onMounted(() => {
  // Add event listeners with proper cleanup
  document.addEventListener('keydown', handleGlobalKeyDown, { passive: false })
  document.addEventListener('click', handleGlobalClick, { passive: true })
  
  // Set up table container accessibility
  if (tableContainer.value) {
    tableContainer.value.setAttribute('role', 'grid')
    tableContainer.value.setAttribute('aria-label', 'Data table')
    tableContainer.value.setAttribute('tabindex', '0')
    tableContainer.value.setAttribute('aria-rowcount', String(data.value.length))
    tableContainer.value.setAttribute('aria-colcount', String(visibleColumns.value.length))
  }
})

onUnmounted(() => {
  // Clean up event listeners to prevent memory leaks
  document.removeEventListener('keydown', handleGlobalKeyDown)
  document.removeEventListener('click', handleGlobalClick)
})

// Watch for prop changes and update internal state
watch(() => props.data, (newData: DataTableRow<T>[]) => {
  data.value = newData
  // Update pagination total if pagination is enabled
  if (pagination.value) {
    pagination.value.total = newData.length
    pagination.value.totalPages = Math.ceil(newData.length / pagination.value.pageSize)
  }
}, { deep: true })

watch(() => props.columns, (newColumns: DataTableColumn<T>[]) => {
  columns.value = newColumns
}, { deep: true })

watch(() => props.pagination, (newPagination: DataTablePaginationType | undefined) => {
  if (newPagination) {
    Object.assign(pagination.value, newPagination)
  }
}, { deep: true })

// Watch for data length changes and update aria attributes
watch(() => data.value.length, (newLength: number) => {
  if (tableContainer.value) {
    tableContainer.value.setAttribute('aria-rowcount', String(newLength))
  }
})

watch(() => visibleColumns.value.length, (newLength: number) => {
  if (tableContainer.value) {
    tableContainer.value.setAttribute('aria-colcount', String(newLength))
  }
})

// Watch for prop changes
// (Vue 3 automatically updates refs when props change)
</script>

<style scoped>
.datatable-container {
  position: relative;
  contain: layout style paint;
}

/* Ensure table takes full width */
table {
  table-layout: fixed;
  width: 100%;
}

/* Custom scrollbar for table container */
.datatable-container ::-webkit-scrollbar {
  width: 8px;
  height: 8px;
}

.datatable-container ::-webkit-scrollbar-track {
  background-color: rgb(243 244 246);
}

.dark .datatable-container ::-webkit-scrollbar-track {
  background-color: rgb(55 65 81);
}

.datatable-container ::-webkit-scrollbar-thumb {
  background-color: rgb(209 213 219);
  border-radius: 0.375rem;
}

.dark .datatable-container ::-webkit-scrollbar-thumb {
  background-color: rgb(107 114 128);
}

.datatable-container ::-webkit-scrollbar-thumb:hover {
  background-color: rgb(156 163 175);
}

.dark .datatable-container ::-webkit-scrollbar-thumb:hover {
  background-color: rgb(156 163 175);
}

/* Selection styles */
.cell-selected {
  background-color: rgb(239 246 255);
  --tw-ring-offset-shadow: var(--tw-ring-inset) 0 0 0 var(--tw-ring-offset-width) var(--tw-ring-offset-color);
  --tw-ring-shadow: var(--tw-ring-inset) 0 0 0 calc(1px + var(--tw-ring-offset-width)) var(--tw-ring-color);
  box-shadow: var(--tw-ring-offset-shadow), var(--tw-ring-shadow), var(--tw-shadow, 0 0 #0000);
  --tw-ring-color: rgb(147 197 253);
}

.dark .cell-selected {
  background-color: rgb(30 58 138 / 0.3);
  --tw-ring-color: rgb(37 99 235);
}

.row-selected {
  background-color: rgb(254 249 195);
}

.dark .row-selected {
  background-color: rgb(30 58 138 / 0.2);
}

/* Editing styles */
.cell-editing {
  background-color: rgb(255 255 255);
  --tw-ring-offset-shadow: var(--tw-ring-inset) 0 0 0 var(--tw-ring-offset-width) var(--tw-ring-offset-color);
  --tw-ring-shadow: var(--tw-ring-inset) 0 0 0 calc(2px + var(--tw-ring-offset-width)) var(--tw-ring-color);
  box-shadow: var(--tw-ring-offset-shadow), var(--tw-ring-shadow), var(--tw-shadow, 0 0 #0000);
  --tw-ring-color: rgb(59 130 246);
}

.dark .cell-editing {
  background-color: rgb(55 65 81);
}

/* Sticky header */
.sticky-header {
  position: sticky;
  top: 0;
  z-index: 10;
}

/* Loading animation */
@keyframes shimmer {
  0% {
    background-position: -200px 0;
  }
  100% {
    background-position: calc(200px + 100%) 0;
  }
}

.loading-shimmer {
  background: linear-gradient(90deg, transparent, rgba(255,255,255,0.4), transparent);
  background-size: 200px 100%;
  animation: shimmer 1.5s infinite;
}

/* Focus styles for accessibility */
.datatable-container:focus-within {
  outline: 2px solid rgb(59 130 246);
  outline-offset: 2px;
}

/* Screen reader only content */
.sr-only {
  position: absolute;
  width: 1px;
  height: 1px;
  padding: 0;
  margin: -1px;
  overflow: hidden;
  clip: rect(0, 0, 0, 0);
  white-space: nowrap;
  border: 0;
}
</style>

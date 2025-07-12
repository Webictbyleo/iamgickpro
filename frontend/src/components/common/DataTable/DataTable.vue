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
import { ref, computed, onMounted, onUnmounted, provide, type Ref } from 'vue'
import type { 
  DataTableProps, 
  DataTableEmits, 
  DataTableRow,
  DataTableFilter,
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
  if (props.selectable) {
    handleCellSelection(row, col, event.ctrlKey || event.metaKey, event.shiftKey)
  }
}

const handleCellDoubleClick = (row: number, col: number) => {
  if (props.editable) {
    startEditing(row, col)
  }
}

const handleCellEdit = (row: number, col: number) => {
  const cellState: CellState = {
    row,
    col,
    key: visibleColumns.value[col]?.key || '',
    editing: true,
    selected: true,
    value: null,
    originalValue: null,
    invalid: false,
    dirty: false
  }
  emit('cell-edit', cellState)
}

const handleCellChange = (row: number, col: number, value: any) => {
  const cellState: CellState = {
    row,
    col,
    key: visibleColumns.value[col]?.key || '',
    editing: false,
    selected: false,
    value,
    originalValue: null,
    invalid: false,
    dirty: true
  }
  emit('cell-change', cellState, value)
}

const handleRowSelect = (row: DataTableRow<T>, selected: boolean) => {
  if (selected) {
    selectRow(row.id)
    emit('row-select', row)
  } else {
    deselectRow(row.id)
    emit('row-deselect', row)
  }
  
  (emit as any)('selection-change', selectedRows.value)
}

const handleSelectAll = (selected: boolean) => {
  if (selected) {
    selectAllRows()
  } else {
    deselectAllRows()
  }
  
  (emit as any)('selection-change', selectedRows.value)
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
  selectedRows.value.forEach(row => {
    removeRow(row.id)
    ;(emit as any)('row-delete', row)
  })
  deselectAllRows()
}

const handleDuplicateRows = () => {
  selectedRows.value.forEach(row => {
    const duplicated = duplicateRow(row.id)
    if (duplicated) {
      emit('row-duplicate', duplicated)
    }
  })
}

const handleExport = (format: 'csv' | 'excel' | 'json') => {
  emit('export', format)
}

const handleImport = (data: any[]) => {
  emit('import', data)
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
  if (props.virtualScrolling && tableContainer.value) {
    const { scrollTop, clientHeight } = tableContainer.value
    const itemHeight = props.rowHeight || 40
    
    const start = Math.floor(scrollTop / itemHeight)
    const end = Math.min(start + Math.ceil(clientHeight / itemHeight) + 5, data.value.length)
    
    visibleRange.value = { start, end }
  }
}

// Global keyboard event handler
const handleGlobalKeyDown = (event: KeyboardEvent) => {
  handleKeyDown(event)
  
  // Handle editing keyboard events
  if (isEditing.value) {
    handleEditKeydown(event)
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
  document.addEventListener('keydown', handleGlobalKeyDown)
  document.addEventListener('click', (event) => {
    if (contextMenu.value.visible && !(event.target as Element)?.closest('.context-menu')) {
      closeContextMenu()
    }
  })
})

onUnmounted(() => {
  document.removeEventListener('keydown', handleGlobalKeyDown)
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
  @apply bg-gray-100 dark:bg-gray-700;
}

.datatable-container ::-webkit-scrollbar-thumb {
  @apply bg-gray-300 dark:bg-gray-500 rounded;
}

.datatable-container ::-webkit-scrollbar-thumb:hover {
  @apply bg-gray-400 dark:bg-gray-400;
}

/* Selection styles */
.cell-selected {
  @apply bg-primary-50 dark:bg-primary-900/30 ring-1 ring-primary-300 dark:ring-primary-600;
}

.row-selected {
  @apply bg-primary-25 dark:bg-primary-900/20;
}

/* Editing styles */
.cell-editing {
  @apply bg-white dark:bg-gray-700 ring-2 ring-primary-500;
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
</style>

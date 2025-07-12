<template>
  <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-600">
    <!-- Virtual scrolling spacer (top) -->
    <tr v-if="virtualScrolling && visibleRange.start > 0">
      <td :colspan="totalColumns" :style="{ height: `${visibleRange.start * rowHeight}px` }"></td>
    </tr>
    
    <!-- Data rows -->
    <tr
      v-for="(row, rowIndex) in displayRows"
      :key="row.id"
      :class="getRowClasses(row, rowIndex)"
      @click="handleRowClick(row, rowIndex, $event)"
      @contextmenu="handleRowContextMenu(row, rowIndex, $event)"
    >
      <!-- Checkbox column -->
      <td v-if="showCheckboxes" class="w-12 px-3 py-2 border-r border-gray-200 dark:border-gray-600">
        <input
          type="checkbox"
          :checked="isRowSelected(row)"
          @change="handleRowSelect(row, $event)"
          @click.stop
          class="w-4 h-4 text-primary-600 bg-gray-100 border-gray-300 rounded focus:ring-primary-500"
        />
      </td>
      
      <!-- Row number column -->
      <td
        v-if="showRowNumbers"
        class="w-16 px-3 py-2 text-center text-xs text-gray-500 dark:text-gray-400 border-r border-gray-200 dark:border-gray-600"
      >
        {{ getRowNumber(rowIndex) }}
      </td>
      
      <!-- Data cells -->
      <DataTableCell
        v-for="(column, colIndex) in columns"
        :key="column.key"
        :column="column"
        :row="getActualRowIndex(rowIndex)"
        :col="colIndex"
        :row-data="row"
        :value="row.data[column.key]"
        :editing="isCellEditing(getActualRowIndex(rowIndex), colIndex)"
        :selected="isCellSelected(getActualRowIndex(rowIndex), colIndex)"
        :loading="row.loading"
        @click="handleCellClick"
        @dblclick="handleCellDoubleClick"
        @edit-start="handleEditStart"
        @edit-save="handleEditSave(getActualRowIndex(rowIndex), colIndex, $event)"
        @edit-cancel="handleEditCancel"
        @context-menu="handleCellContextMenu"
      />
    </tr>
    
    <!-- Virtual scrolling spacer (bottom) -->
    <tr v-if="virtualScrolling && visibleRange.end < data.length">
      <td :colspan="totalColumns" :style="{ height: `${(data.length - visibleRange.end) * rowHeight}px` }"></td>
    </tr>
    
    <!-- Loading rows -->
    <tr v-if="loading" v-for="i in 5" :key="`loading-${i}`" class="animate-pulse">
      <td v-if="showCheckboxes" class="w-12 px-3 py-2 border-r border-gray-200 dark:border-gray-600">
        <div class="w-4 h-4 bg-gray-200 dark:bg-gray-600 rounded"></div>
      </td>
      <td v-if="showRowNumbers" class="w-16 px-3 py-2 border-r border-gray-200 dark:border-gray-600">
        <div class="h-4 bg-gray-200 dark:bg-gray-600 rounded"></div>
      </td>
      <td
        v-for="column in columns"
        :key="column.key"
        class="px-3 py-2 border-r border-gray-200 dark:border-gray-600"
      >
        <div class="h-4 bg-gray-200 dark:bg-gray-600 rounded" :style="{ width: `${Math.random() * 60 + 40}%` }"></div>
      </td>
    </tr>
  </tbody>
</template>

<script setup lang="ts">
import { computed, inject } from 'vue'
import type { 
  DataTableColumn, 
  DataTableRow, 
  SelectionState, 
  EditingState 
} from '../types'
import DataTableCell from './DataTableCell.vue'

interface Props {
  columns: DataTableColumn[]
  data: DataTableRow[]
  selection: SelectionState
  editing: EditingState
  showCheckboxes?: boolean
  showRowNumbers?: boolean
  rowHeight?: number
  virtualScrolling?: boolean
  visibleRange?: { start: number; end: number }
  loading?: boolean
}

interface Emits {
  (e: 'cell-click', row: number, col: number, event: MouseEvent): void
  (e: 'cell-double-click', row: number, col: number): void
  (e: 'cell-edit', row: number, col: number): void
  (e: 'cell-change', row: number, col: number, value: any): void
  (e: 'row-select', row: DataTableRow, selected: boolean): void
  (e: 'row-context-menu', row: number, event: MouseEvent): void
}

const props = withDefaults(defineProps<Props>(), {
  showCheckboxes: true,
  showRowNumbers: false,
  rowHeight: 40,
  virtualScrolling: false,
  visibleRange: () => ({ start: 0, end: 50 }),
  loading: false
})

const emit = defineEmits<Emits>()

// Inject data table context
const dataTable = inject('dataTable', {})

// Computed properties
const totalColumns = computed(() => {
  let count = props.columns.length
  if (props.showCheckboxes) count++
  if (props.showRowNumbers) count++
  return count
})

const displayRows = computed(() => {
  if (props.virtualScrolling) {
    const { start, end } = props.visibleRange
    return props.data.slice(start, end)
  }
  return props.data
})

// Methods
const getRowClasses = (row: DataTableRow, rowIndex: number) => [
  'hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors group',
  {
    'bg-primary-50 dark:bg-primary-900/10': isRowSelected(row),
    'bg-danger-50 dark:bg-danger-900/10': row.invalid,
    'opacity-75': row.disabled
  },
  row.className
]

const getRowNumber = (rowIndex: number): number => {
  if (props.virtualScrolling) {
    return props.visibleRange.start + rowIndex + 1
  }
  return rowIndex + 1
}

const getActualRowIndex = (displayRowIndex: number): number => {
  if (props.virtualScrolling) {
    return props.visibleRange.start + displayRowIndex
  }
  return displayRowIndex
}

const isRowSelected = (row: DataTableRow): boolean => {
  return props.selection.selectedRows.has(row.id)
}

const isCellSelected = (row: number, col: number): boolean => {
  const key = `${row}-${col}`
  return props.selection.selectedCells.has(key)
}

const isCellEditing = (row: number, col: number): boolean => {
  return props.editing.cell?.row === row && props.editing.cell?.col === col
}

// Event handlers
const handleRowClick = (row: DataTableRow, rowIndex: number, event: MouseEvent) => {
  if (event.target instanceof HTMLInputElement) {
    return // Don't select row when clicking checkbox
  }
  
  const actualRowIndex = getActualRowIndex(rowIndex)
  emit('row-select', row, !isRowSelected(row))
}

const handleRowSelect = (row: DataTableRow, event: Event) => {
  const target = event.target as HTMLInputElement
  emit('row-select', row, target.checked)
}

const handleRowContextMenu = (row: DataTableRow, rowIndex: number, event: MouseEvent) => {
  const actualRowIndex = getActualRowIndex(rowIndex)
  emit('row-context-menu', actualRowIndex, event)
}

const handleCellClick = (row: number, col: number, event: MouseEvent) => {
  emit('cell-click', row, col, event)
}

const handleCellDoubleClick = (row: number, col: number) => {
  emit('cell-double-click', row, col)
}

const handleEditStart = (row: number, col: number) => {
  emit('cell-edit', row, col)
}

const handleEditSave = (row: number, col: number, value: any) => {
  emit('cell-change', row, col, value)
}

const handleEditCancel = () => {
  // Handle edit cancellation
}

const handleCellContextMenu = (row: number, col: number, event: MouseEvent) => {
  // Handle cell context menu
  event.preventDefault()
  // Emit context menu event to parent
}
</script>

<style scoped>
/* Ensure consistent row heights */
tr {
  height: v-bind('`${rowHeight}px`');
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

/* Row selection animation */
.row-selected {
  animation: selectRow 0.2s ease-out;
}

@keyframes selectRow {
  0% {
    background-color: transparent;
  }
  50% {
    background-color: theme('colors.primary.100');
  }
  100% {
    background-color: theme('colors.primary.50');
  }
}

/* Hover effects */
.group:hover .group-hover\\:opacity-100 {
  opacity: 1;
}

/* Virtual scrolling optimization */
.virtual-row {
  contain: layout style paint;
}
</style>

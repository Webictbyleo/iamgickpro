// Main DataTable component and types export
export { default as DataTable } from './DataTable.vue'

// Import types for internal use
import type {
  DataTableColumn,
  DataTableSelectOption,
  KeyboardShortcut,
  DataTableRow,
  DataTableProps,
  DataTablePagination
} from './types'

// Component exports
export { default as DataTableCell } from './components/DataTableCell.vue'
export { default as DataTableHeader } from './components/DataTableHeader.vue'
export { default as DataTableBody } from './components/DataTableBody.vue'
export { default as DataTableToolbar } from './components/DataTableToolbar.vue'
export { default as DataTablePagination } from './components/DataTablePagination.vue'
export { default as DataTableContextMenu } from './components/DataTableContextMenu.vue'
export { default as HeaderFilter } from './components/HeaderFilter.vue'

// Composables exports
export { useDataTable } from './composables/useDataTable'
export { useTableEditing } from './composables/useTableEditing'

// Types exports
export type {
  DataTableColumn,
  DataTableRow,
  DataTableProps,
  DataTableEmits,
  CellState,
  TableSelection,
  DataTableSort,
  DataTableFilter,
  SelectionState,
  EditingState,
  UndoRedoState,
  DataTableHistoryEntry,
  KeyboardShortcut,
  ClipboardData,
  ColumnResizeState,
  ContextMenuState,
  ValidationResult,
  BulkEditOperation,
  SearchReplaceState,
  DataTableSelectOption
} from './types'

export type {
  ColumnDefinition,
  ColumnGroup,
  ColumnVisibilityState,
  ColumnFilterState,
  ColumnSortState
} from './types/column'

export type {
  CellPosition,
  CellValue,
  CellEditState,
  CellRenderer,
  CellEditor,
  CellValidation,
  CellStyle,
  CellMetadata,
  CellChangeEvent
} from './types/cell'

export type {
  DataTableCellEditEvent,
  DataTableCellChangeEvent,
  DataTableRowEvent,
  DataTableSelectionEvent,
  DataTableSortEvent,
  DataTableFilterEvent,
  DataTablePaginationEvent,
  DataTableKeyboardEvent,
  DataTableClipboardEvent,
  DataTableExportEvent,
  DataTableImportEvent,
  DataTableContextMenuEvent,
  ContextMenuItem,
  DataTableValidationEvent,
  DataTableBulkEditEvent
} from './types/events'

// Utility functions and formatters
// export { default as formatters } from './utils/formatters'
// export { default as validators } from './utils/validators' 
// export { default as keyboardUtils } from './utils/keyboard'
// export { default as clipboardUtils } from './utils/clipboard'

// Default column configurations for common data types
export const defaultColumns = {
  text: (key: string, label: string): DataTableColumn => ({
    key,
    label,
    type: 'text',
    sortable: true,
    filterable: true,
    editable: true,
    align: 'left'
  }),
  
  number: (key: string, label: string): DataTableColumn => ({
    key,
    label,
    type: 'number',
    sortable: true,
    filterable: true,
    editable: true,
    align: 'right'
  }),
  
  currency: (key: string, label: string): DataTableColumn => ({
    key,
    label,
    type: 'currency',
    sortable: true,
    filterable: true,
    editable: true,
    align: 'right',
    formatter: (value: any) => {
      if (value == null) return ''
      return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD'
      }).format(Number(value))
    }
  }),
  
  date: (key: string, label: string): DataTableColumn => ({
    key,
    label,
    type: 'date',
    sortable: true,
    filterable: true,
    editable: true,
    align: 'left',
    formatter: (value: any) => {
      if (!value) return ''
      const date = value instanceof Date ? value : new Date(value)
      return isNaN(date.getTime()) ? String(value) : date.toLocaleDateString()
    }
  }),
  
  boolean: (key: string, label: string): DataTableColumn => ({
    key,
    label,
    type: 'boolean',
    sortable: true,
    filterable: true,
    editable: true,
    align: 'center'
  }),
  
  email: (key: string, label: string): DataTableColumn => ({
    key,
    label,
    type: 'email',
    sortable: true,
    filterable: true,
    editable: true,
    align: 'left',
    validator: (value: any) => {
      if (!value) return true
      const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
      return emailRegex.test(String(value)) || 'Please enter a valid email address'
    }
  }),
  
  url: (key: string, label: string): DataTableColumn => ({
    key,
    label,
    type: 'url',
    sortable: true,
    filterable: true,
    editable: true,
    align: 'left',
    validator: (value: any) => {
      if (!value) return true
      try {
        new URL(String(value))
        return true
      } catch {
        return 'Please enter a valid URL'
      }
    }
  }),
  
  select: (key: string, label: string, options: DataTableSelectOption[]): DataTableColumn => ({
    key,
    label,
    type: 'select',
    sortable: true,
    filterable: true,
    editable: true,
    align: 'left',
    options
  })
}

// Default keyboard shortcuts
export const defaultKeyboardShortcuts: KeyboardShortcut[] = [
  { key: 'Enter', action: 'edit-or-navigate-down', description: 'Edit cell or navigate down' },
  { key: 'Tab', action: 'navigate-right', description: 'Navigate to next cell' },
  { key: 'Tab', shiftKey: true, action: 'navigate-left', description: 'Navigate to previous cell' },
  { key: 'Escape', action: 'cancel-edit', description: 'Cancel editing' },
  { key: 'F2', action: 'edit-cell', description: 'Edit current cell' },
  { key: 'Delete', action: 'clear-content', description: 'Clear cell content' },
  { key: 'Backspace', action: 'clear-content', description: 'Clear cell content' },
  { key: 'c', ctrlKey: true, action: 'copy', description: 'Copy selection' },
  { key: 'v', ctrlKey: true, action: 'paste', description: 'Paste content' },
  { key: 'x', ctrlKey: true, action: 'cut', description: 'Cut selection' },
  { key: 'a', ctrlKey: true, action: 'select-all', description: 'Select all' },
  { key: 'z', ctrlKey: true, action: 'undo', description: 'Undo last action' },
  { key: 'y', ctrlKey: true, action: 'redo', description: 'Redo last action' },
  { key: 'ArrowUp', action: 'navigate-up', description: 'Navigate up' },
  { key: 'ArrowDown', action: 'navigate-down', description: 'Navigate down' },
  { key: 'ArrowLeft', action: 'navigate-left', description: 'Navigate left' },
  { key: 'ArrowRight', action: 'navigate-right', description: 'Navigate right' },
  { key: 'Home', action: 'navigate-row-start', description: 'Go to start of row' },
  { key: 'End', action: 'navigate-row-end', description: 'Go to end of row' },
  { key: 'Home', ctrlKey: true, action: 'navigate-table-start', description: 'Go to start of table' },
  { key: 'End', ctrlKey: true, action: 'navigate-table-end', description: 'Go to end of table' },
  { key: 'PageUp', action: 'navigate-page-up', description: 'Navigate page up' },
  { key: 'PageDown', action: 'navigate-page-down', description: 'Navigate page down' }
]

// Helper function to create a basic data table configuration
export function createDataTable<T = any>(config: {
  columns: DataTableColumn<T>[]
  data?: T[]
  editable?: boolean
  sortable?: boolean
  filterable?: boolean
  selectable?: boolean
  pagination?: Partial<DataTablePagination>
}): DataTableProps<T> {
  const rows: DataTableRow<T>[] = (config.data || []).map((item, index) => ({
    id: `row-${index}`,
    data: item,
    selected: false,
    editing: false,
    invalid: false
  }))
  
  return {
    columns: config.columns,
    data: rows,
    editable: config.editable ?? true,
    sortable: config.sortable ?? true,
    filterable: config.filterable ?? true,
    selectable: config.selectable ?? true,
    pagination: config.pagination ? {
      page: 1,
      pageSize: 50,
      total: rows.length,
      totalPages: Math.ceil(rows.length / 50),
      ...config.pagination
    } : undefined
  }
}

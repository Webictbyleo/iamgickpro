import type { 
  DataTableRow, 
  CellState, 
  TableSelection, 
  DataTableSort, 
  DataTableFilter,
  DataTablePagination 
} from './index'

export interface DataTableCellEditEvent<T = any> {
  cell: CellState
  row: DataTableRow<T>
  column: string
  oldValue: any
  newValue: any
}

export interface DataTableCellChangeEvent<T = any> {
  cell: CellState
  row: DataTableRow<T>
  column: string
  value: any
  source: 'user' | 'api' | 'validation' | 'import'
}

export interface DataTableRowEvent<T = any> {
  row: DataTableRow<T>
  index: number
  selected: boolean
}

export interface DataTableSelectionEvent<T = any> {
  selection: TableSelection
  selectedRows: DataTableRow<T>[]
  selectedCells: CellState[]
}

export interface DataTableSortEvent {
  sorts: DataTableSort[]
  column: string
  direction: 'asc' | 'desc'
}

export interface DataTableFilterEvent {
  filters: DataTableFilter[]
  column: string
  value: any
}

export interface DataTablePaginationEvent {
  pagination: DataTablePagination
  page: number
  pageSize: number
}

export interface DataTableKeyboardEvent {
  key: string
  ctrlKey: boolean
  shiftKey: boolean
  altKey: boolean
  metaKey: boolean
  position: { row: number; col: number }
  preventDefault: () => void
  stopPropagation: () => void
}

export interface DataTableClipboardEvent {
  action: 'copy' | 'cut' | 'paste'
  data: string[][]
  selection: TableSelection
  format: 'text/plain' | 'text/html' | 'application/json'
}

export interface DataTableExportEvent {
  format: 'csv' | 'excel' | 'json' | 'pdf'
  data: any[]
  filename: string
  options: Record<string, any>
}

export interface DataTableImportEvent {
  data: any[]
  format: 'csv' | 'excel' | 'json'
  preview: boolean
  mapping: Record<string, string>
}

export interface DataTableContextMenuEvent {
  type: 'cell' | 'row' | 'column' | 'header'
  position: { x: number; y: number }
  target: {
    row?: number
    col?: number
    key?: string
  }
  items: ContextMenuItem[]
}

export interface ContextMenuItem {
  key: string
  label: string
  icon?: string
  disabled?: boolean
  separator?: boolean
  children?: ContextMenuItem[]
  action?: () => void
}

export interface DataTableValidationEvent<T = any> {
  row: DataTableRow<T>
  column: string
  value: any
  valid: boolean
  error?: string
  warnings?: string[]
}

export interface DataTableBulkEditEvent<T = any> {
  rows: DataTableRow<T>[]
  columns: string[]
  value: any
  condition?: (row: DataTableRow<T>) => boolean
}

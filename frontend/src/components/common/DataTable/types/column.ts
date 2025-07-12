import type { DataTableColumn } from './index'

export interface ColumnDefinition<T = any> extends DataTableColumn<T> {
  // Extended column properties
  resizable?: boolean
  sortable?: boolean
  filterable?: boolean
  pinned?: 'left' | 'right' | false
  group?: string
  tooltip?: string
  icon?: string
  
  // Rendering options
  headerTemplate?: string
  cellTemplate?: string
  footerTemplate?: string
  
  // Column-specific validation
  min?: number
  max?: number
  pattern?: RegExp
  
  // Column actions
  onHeaderClick?: (column: ColumnDefinition<T>) => void
  onCellClick?: (value: any, row: T, column: ColumnDefinition<T>) => void
  onCellDoubleClick?: (value: any, row: T, column: ColumnDefinition<T>) => void
}

export interface ColumnGroup {
  key: string
  label: string
  columns: string[]
  collapsible?: boolean
  collapsed?: boolean
  className?: string
}

export interface ColumnVisibilityState {
  visible: Record<string, boolean>
  order: string[]
  widths: Record<string, number>
  pinned: Record<string, 'left' | 'right' | false>
}

export interface ColumnFilterState {
  column: string
  type: 'text' | 'number' | 'date' | 'select' | 'boolean'
  operator: string
  value: any
  active: boolean
}

export interface ColumnSortState {
  column: string
  direction: 'asc' | 'desc'
  index: number // For multi-column sorting
}

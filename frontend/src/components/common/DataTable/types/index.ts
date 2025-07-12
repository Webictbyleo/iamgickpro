export interface DataTableColumn<T = any> {
  key: string
  label: string
  type: 'text' | 'number' | 'date' | 'select' | 'boolean' | 'currency' | 'email' | 'url' | 'textarea' | 'password'
  width?: number | string
  minWidth?: number
  maxWidth?: number
  sortable?: boolean
  filterable?: boolean
  editable?: boolean
  required?: boolean
  resizable?: boolean
  validator?: (value: any, row?: T) => boolean | string
  formatter?: (value: any, row?: T) => string
  parser?: (value: string) => any
  options?: DataTableSelectOption[]
  placeholder?: string
  align?: 'left' | 'center' | 'right'
  sticky?: boolean
  hidden?: boolean
  className?: string
  headerClassName?: string
  cellClassName?: string
  icon?: any
  editor?: any
  renderer?: any
}

export interface DataTableSelectOption {
  label: string
  value: any
  disabled?: boolean
  className?: string
}

export interface DataTableRow<T = any> {
  id: string | number
  data: T
  selected?: boolean
  editing?: boolean
  invalid?: boolean
  errors?: Record<string, string>
  className?: string
  disabled?: boolean
  loading?: boolean
}

export interface CellState {
  row: number
  col: number
  key: string
  editing: boolean
  selected: boolean
  value: any
  originalValue: any
  invalid: boolean
  error?: string
  dirty?: boolean
}

export interface TableSelection {
  start: { row: number; col: number }
  end: { row: number; col: number }
  type: 'single' | 'range' | 'column' | 'row'
}

export interface DataTableFilter {
  column: string
  operator: 'equals' | 'not_equals' | 'contains' | 'not_contains' | 'starts_with' | 'ends_with' | 'greater_than' | 'less_than' | 'between' | 'is_empty' | 'is_not_empty'
  value: any
  value2?: any // For 'between' operator
}

export interface DataTableSort {
  column: string
  direction: 'asc' | 'desc'
}

export interface DataTablePagination {
  page: number
  pageSize: number
  total: number
  totalPages: number
}

export interface DataTableProps<T = any> {
  columns: DataTableColumn<T>[]
  data: DataTableRow<T>[]
  loading?: boolean
  sortable?: boolean
  filterable?: boolean
  selectable?: boolean
  editable?: boolean
  pagination?: DataTablePagination
  virtualScrolling?: boolean
  stickyHeader?: boolean
  showRowNumbers?: boolean
  showCheckboxes?: boolean
  rowHeight?: number
  maxHeight?: string
  minHeight?: string
  className?: string
  emptyMessage?: string
  loadingMessage?: string
  errorMessage?: string
}

export interface DataTableEmits<T = any> {
  'update:data': [data: DataTableRow<T>[]]
  'update:pagination': [pagination: DataTablePagination]
  'cell-edit': [cellState: CellState]
  'cell-change': [cellState: CellState, oldValue: any]
  'row-select': [row: DataTableRow<T>]
  'row-deselect': [row: DataTableRow<T>]
  'selection-change': [selectedRows: DataTableRow<T>[]]
  'sort': [sort: DataTableSort[]]
  'filter': [filters: DataTableFilter[]]
  'row-add': [row: DataTableRow<T>]
  'row-delete': [row: DataTableRow<T>]
  'row-duplicate': [row: DataTableRow<T>]
  'export': [format: 'csv' | 'excel' | 'json']
  'import': [data: any[]]
}

export interface EditingState {
  cell: CellState | null
  isEditing: boolean
  tempValue: string
  originalValue: any
}

export interface SelectionState {
  selectedCells: Set<string>
  selectedRows: Set<string | number>
  selectedColumns: Set<string>
  activeSelection: TableSelection | null
  dragSelection: boolean
}

export interface UndoRedoState {
  history: DataTableHistoryEntry[]
  currentIndex: number
  maxHistorySize: number
}

export interface DataTableHistoryEntry {
  type: 'cell-edit' | 'row-add' | 'row-delete' | 'row-duplicate' | 'bulk-edit'
  timestamp: number
  data: any
  description: string
}

export interface KeyboardShortcut {
  key: string
  ctrlKey?: boolean
  shiftKey?: boolean
  altKey?: boolean
  action: string
  description: string
}

export interface ClipboardData {
  data: string[][]
  source: 'internal' | 'external'
  format: 'text/plain' | 'text/html' | 'application/json'
}

export interface ColumnResizeState {
  isResizing: boolean
  columnKey: string
  startX: number
  startWidth: number
}

export interface ContextMenuState {
  visible: boolean
  x: number
  y: number
  target: {
    type: 'cell' | 'row' | 'column' | 'header'
    row?: number
    col?: number
    key?: string
  }
}

export interface ContextMenuItem {
  key: string
  label: string
  icon?: any
  disabled?: boolean
  divider?: boolean
  separator?: boolean
  shortcut?: string
  children?: ContextMenuItem[]
  action?: () => void
}

export interface ValidationResult {
  valid: boolean
  error?: string
  warnings?: string[]
}

export interface BulkEditOperation {
  columns: string[]
  value: any
  condition?: (row: DataTableRow) => boolean
}

export interface SearchReplaceState {
  searchTerm: string
  replaceTerm: string
  matchCase: boolean
  matchWholeWord: boolean
  useRegex: boolean
  searchColumns: string[]
  currentMatch: { row: number; col: number } | null
  matches: Array<{ row: number; col: number }>
}

import { ref, reactive, computed, watch, type Ref } from 'vue'
import type { 
  DataTableColumn, 
  DataTableRow, 
  DataTablePagination, 
  DataTableSort, 
  DataTableFilter,
  SelectionState,
  EditingState,
  UndoRedoState
} from '../types'

export interface UseDataTableOptions<T = any> {
  columns: DataTableColumn<T>[]
  data: DataTableRow<T>[]
  pagination?: DataTablePagination
  sortable?: boolean
  filterable?: boolean
  selectable?: boolean
  editable?: boolean
  autoSave?: boolean
  saveDelay?: number
}

export function useDataTable<T = any>(options: UseDataTableOptions<T>) {
  // Reactive state
  const columns = ref<DataTableColumn<T>[]>(options.columns)
  const data = ref<DataTableRow<T>[]>(options.data)
  const loading = ref(false)
  const error = ref<string | null>(null)
  
  // Pagination state
  const pagination = ref<DataTablePagination>(
    options.pagination || {
      page: 1,
      pageSize: 50,
      total: options.data.length,
      totalPages: Math.ceil(options.data.length / 50)
    }
  )
  
  // Sorting state
  const sorts = ref<DataTableSort[]>([])
  
  // Filtering state
  const filters = ref<DataTableFilter[]>([])
  
  // Selection state
  const selection = reactive<SelectionState>({
    selectedCells: new Set(),
    selectedRows: new Set(),
    selectedColumns: new Set(),
    activeSelection: null,
    dragSelection: false
  })
  
  // Editing state
  const editing = reactive<EditingState>({
    cell: null,
    isEditing: false,
    tempValue: '',
    originalValue: null
  })
  
  // Undo/Redo state
  const undoRedo = reactive<UndoRedoState>({
    history: [],
    currentIndex: -1,
    maxHistorySize: 100
  })
  
  // Computed properties
  const visibleColumns = computed(() => 
    columns.value.filter(col => !col.hidden)
  )
  
  const sortedData = computed(() => {
    if (sorts.value.length === 0) return data.value
    
    return [...data.value].sort((a, b) => {
      for (const sort of sorts.value) {
        const column = columns.value.find(col => col.key === sort.column)
        if (!column) continue
        
        const aValue = (a.data as any)[sort.column]
        const bValue = (b.data as any)[sort.column]
        
        let comparison = 0
        
        if (column.type === 'number') {
          comparison = (Number(aValue) || 0) - (Number(bValue) || 0)
        } else if (column.type === 'date') {
          comparison = new Date(aValue).getTime() - new Date(bValue).getTime()
        } else {
          comparison = String(aValue || '').localeCompare(String(bValue || ''))
        }
        
        if (comparison !== 0) {
          return sort.direction === 'asc' ? comparison : -comparison
        }
      }
      return 0
    })
  })
  
  const filteredData = computed(() => {
    if (filters.value.length === 0) return sortedData.value
    
    return sortedData.value.filter(row => {
      return filters.value.every(filter => {
        const value = (row.data as any)[filter.column]
        
        switch (filter.operator) {
          case 'equals':
            return value === filter.value
          case 'not_equals':
            return value !== filter.value
          case 'contains':
            return String(value || '').toLowerCase().includes(String(filter.value || '').toLowerCase())
          case 'not_contains':
            return !String(value || '').toLowerCase().includes(String(filter.value || '').toLowerCase())
          case 'starts_with':
            return String(value || '').toLowerCase().startsWith(String(filter.value || '').toLowerCase())
          case 'ends_with':
            return String(value || '').toLowerCase().endsWith(String(filter.value || '').toLowerCase())
          case 'greater_than':
            return Number(value) > Number(filter.value)
          case 'less_than':
            return Number(value) < Number(filter.value)
          case 'between':
            return Number(value) >= Number(filter.value) && Number(value) <= Number(filter.value2)
          case 'is_empty':
            return !value || value === ''
          case 'is_not_empty':
            return value && value !== ''
          default:
            return true
        }
      })
    })
  })
  
  const paginatedData = computed(() => {
    const start = (pagination.value.page - 1) * pagination.value.pageSize
    const end = start + pagination.value.pageSize
    return filteredData.value.slice(start, end)
  })
  
  const selectedRows = computed(() => 
    data.value.filter(row => selection.selectedRows.has(row.id))
  )
  
  const hasSelection = computed(() => 
    selection.selectedRows.size > 0 || selection.selectedCells.size > 0
  )
  
  const canUndo = computed(() => 
    undoRedo.currentIndex >= 0
  )
  
  const canRedo = computed(() => 
    undoRedo.currentIndex < undoRedo.history.length - 1
  )
  
  // Methods
  const updatePagination = (newPagination: Partial<DataTablePagination>) => {
    Object.assign(pagination.value, newPagination)
    
    // Update total pages
    pagination.value.totalPages = Math.ceil(pagination.value.total / pagination.value.pageSize)
  }
  
  const addSort = (column: string, direction: 'asc' | 'desc', multiSort = false) => {
    if (!multiSort) {
      sorts.value = [{ column, direction }]
    } else {
      const existingIndex = sorts.value.findIndex(s => s.column === column)
      if (existingIndex >= 0) {
        sorts.value[existingIndex].direction = direction
      } else {
        sorts.value.push({ column, direction })
      }
    }
  }
  
  const removeSort = (column: string) => {
    const index = sorts.value.findIndex(s => s.column === column)
    if (index >= 0) {
      sorts.value.splice(index, 1)
    }
  }
  
  const clearSorts = () => {
    sorts.value = []
  }
  
  const addFilter = (filter: DataTableFilter) => {
    const existingIndex = filters.value.findIndex(f => f.column === filter.column)
    if (existingIndex >= 0) {
      filters.value[existingIndex] = filter
    } else {
      filters.value.push(filter)
    }
  }
  
  const removeFilter = (column: string) => {
    const index = filters.value.findIndex(f => f.column === column)
    if (index >= 0) {
      filters.value.splice(index, 1)
    }
  }
  
  const clearFilters = () => {
    filters.value = []
  }
  
  const selectRow = (rowId: string | number, multi = false) => {
    if (!multi) {
      selection.selectedRows.clear()
    }
    selection.selectedRows.add(rowId)
  }
  
  const deselectRow = (rowId: string | number) => {
    selection.selectedRows.delete(rowId)
  }
  
  const selectAllRows = () => {
    data.value.forEach(row => selection.selectedRows.add(row.id))
  }
  
  const deselectAllRows = () => {
    selection.selectedRows.clear()
  }
  
  const addRow = (rowData: T, position?: number) => {
    const newRow: DataTableRow<T> = {
      id: Date.now().toString(),
      data: rowData,
      selected: false,
      editing: false,
      invalid: false
    }
    
    if (position !== undefined) {
      data.value.splice(position, 0, newRow as any)
    } else {
      data.value.push(newRow as any)
    }
    
    // Update pagination total
    pagination.value.total = data.value.length
    updatePagination({})
    
    return newRow
  }
  
  const removeRow = (rowId: string | number) => {
    const index = data.value.findIndex(row => row.id === rowId)
    if (index >= 0) {
      const removedRow = data.value.splice(index, 1)[0]
      selection.selectedRows.delete(rowId)
      
      // Update pagination total
      pagination.value.total = data.value.length
      updatePagination({})
      
      return removedRow
    }
    return null
  }
  
  const duplicateRow = (rowId: string | number) => {
    const row = data.value.find(r => r.id === rowId)
    if (row) {
      return addRow({ ...(row.data as any) })
    }
    return null
  }
  
  const updateRowData = (rowId: string | number, newData: Partial<T>) => {
    const row = data.value.find(r => r.id === rowId)
    if (row) {
      Object.assign(row.data as any, newData)
      return row
    }
    return null
  }
  
  const addToHistory = (entry: Omit<typeof undoRedo.history[0], 'timestamp'>) => {
    // Remove entries after current index
    undoRedo.history = undoRedo.history.slice(0, undoRedo.currentIndex + 1)
    
    // Add new entry
    undoRedo.history.push({
      ...entry,
      timestamp: Date.now()
    })
    
    // Limit history size
    if (undoRedo.history.length > undoRedo.maxHistorySize) {
      undoRedo.history = undoRedo.history.slice(-undoRedo.maxHistorySize)
    }
    
    undoRedo.currentIndex = undoRedo.history.length - 1
  }
  
  const undo = () => {
    if (!canUndo.value) return
    
    const entry = undoRedo.history[undoRedo.currentIndex]
    // Implement undo logic based on entry type
    // This would be expanded based on the specific operations
    
    undoRedo.currentIndex--
  }
  
  const redo = () => {
    if (!canRedo.value) return
    
    undoRedo.currentIndex++
    const entry = undoRedo.history[undoRedo.currentIndex]
    // Implement redo logic based on entry type
    
  }
  
  // Watch for data changes to update pagination
  watch(() => data.value.length, (newLength) => {
    pagination.value.total = newLength
    updatePagination({})
  })
  
  return {
    // State
    columns,
    data,
    loading,
    error,
    pagination,
    sorts,
    filters,
    selection,
    editing,
    undoRedo,
    
    // Computed
    visibleColumns,
    sortedData,
    filteredData,
    paginatedData,
    selectedRows,
    hasSelection,
    canUndo,
    canRedo,
    
    // Methods
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
    updateRowData,
    addToHistory,
    undo,
    redo
  }
}

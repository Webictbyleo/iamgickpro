import { type Ref } from 'vue'
import type { 
  DataTableColumn, 
  DataTableRow, 
  SelectionState, 
  TableSelection 
} from '../types'
import formatters from '../utils/formatters'

export interface UseTableSelectionOptions<T = any> {
  columns: Ref<DataTableColumn<T>[]>
  data: Ref<DataTableRow<T>[]>
  selection: SelectionState
}

export function useTableSelection<T = any>(options: UseTableSelectionOptions<T>) {
  const { columns, data, selection } = options
  
  // Generate selection key
  const getSelectionKey = (row: number, col: number) => `${row}-${col}`
  
  // Handle single cell selection
  const handleCellSelection = (row: number, col: number, ctrlKey = false, shiftKey = false) => {
    const key = getSelectionKey(row, col)
    
    if (shiftKey && selection.activeSelection) {
      // Range selection
      handleRangeSelection(row, col)
    } else if (ctrlKey) {
      // Multi-selection
      if (selection.selectedCells.has(key)) {
        selection.selectedCells.delete(key)
      } else {
        selection.selectedCells.add(key)
      }
    } else {
      // Single selection
      selection.selectedCells.clear()
      selection.selectedCells.add(key)
      
      selection.activeSelection = {
        start: { row, col },
        end: { row, col },
        type: 'single'
      }
    }
  }
  
  // Handle range selection
  const handleRangeSelection = (endRow: number, endCol: number) => {
    if (!selection.activeSelection) {
      return handleCellSelection(endRow, endCol)
    }
    
    const { start } = selection.activeSelection
    
    // Clear current selection
    selection.selectedCells.clear()
    
    // Calculate selection bounds
    const minRow = Math.min(start.row, endRow)
    const maxRow = Math.max(start.row, endRow)
    const minCol = Math.min(start.col, endCol)
    const maxCol = Math.max(start.col, endCol)
    
    // Select all cells in range
    for (let row = minRow; row <= maxRow; row++) {
      for (let col = minCol; col <= maxCol; col++) {
        const key = getSelectionKey(row, col)
        selection.selectedCells.add(key)
      }
    }
    
    // Update active selection
    selection.activeSelection = {
      start,
      end: { row: endRow, col: endCol },
      type: 'range'
    }
  }
  
  // Handle row selection
  const handleRowSelection = (rowIndex: number, ctrlKey = false, shiftKey = false) => {
    const row = data.value[rowIndex]
    if (!row) return
    
    if (shiftKey && selection.selectedRows.size > 0) {
      // Range selection for rows
      const selectedIndices = Array.from(selection.selectedRows)
        .map(id => data.value.findIndex(r => r.id === id))
        .filter(index => index !== -1)
      
      if (selectedIndices.length > 0) {
        const minIndex = Math.min(...selectedIndices, rowIndex)
        const maxIndex = Math.max(...selectedIndices, rowIndex)
        
        selection.selectedRows.clear()
        for (let i = minIndex; i <= maxIndex; i++) {
          if (data.value[i]) {
            selection.selectedRows.add(data.value[i].id)
          }
        }
      }
    } else if (ctrlKey) {
      // Multi-selection
      if (selection.selectedRows.has(row.id)) {
        selection.selectedRows.delete(row.id)
      } else {
        selection.selectedRows.add(row.id)
      }
    } else {
      // Single selection
      selection.selectedRows.clear()
      selection.selectedRows.add(row.id)
    }
  }
  
  // Handle column selection
  const handleColumnSelection = (colIndex: number, ctrlKey = false, shiftKey = false) => {
    const column = columns.value[colIndex]
    if (!column) return
    
    if (shiftKey && selection.selectedColumns.size > 0) {
      // Range selection for columns
      const selectedIndices = Array.from(selection.selectedColumns)
        .map(key => columns.value.findIndex(c => c.key === key))
        .filter(index => index !== -1)
      
      if (selectedIndices.length > 0) {
        const minIndex = Math.min(...selectedIndices, colIndex)
        const maxIndex = Math.max(...selectedIndices, colIndex)
        
        selection.selectedColumns.clear()
        for (let i = minIndex; i <= maxIndex; i++) {
          if (columns.value[i]) {
            selection.selectedColumns.add(columns.value[i].key)
          }
        }
      }
    } else if (ctrlKey) {
      // Multi-selection
      if (selection.selectedColumns.has(column.key)) {
        selection.selectedColumns.delete(column.key)
      } else {
        selection.selectedColumns.add(column.key)
      }
    } else {
      // Single selection
      selection.selectedColumns.clear()
      selection.selectedColumns.add(column.key)
    }
    
    // Select all cells in selected columns
    updateCellSelectionFromColumns()
  }
  
  // Update cell selection based on selected columns
  const updateCellSelectionFromColumns = () => {
    if (selection.selectedColumns.size === 0) return
    
    selection.selectedCells.clear()
    
    data.value.forEach((_, rowIndex) => {
      columns.value.forEach((column, colIndex) => {
        if (selection.selectedColumns.has(column.key)) {
          const key = getSelectionKey(rowIndex, colIndex)
          selection.selectedCells.add(key)
        }
      })
    })
  }
  
  // Update cell selection based on selected rows
  const updateCellSelectionFromRows = () => {
    if (selection.selectedRows.size === 0) return
    
    selection.selectedCells.clear()
    
    data.value.forEach((row, rowIndex) => {
      if (selection.selectedRows.has(row.id)) {
        columns.value.forEach((_, colIndex) => {
          const key = getSelectionKey(rowIndex, colIndex)
          selection.selectedCells.add(key)
        })
      }
    })
  }
  
  // Select all cells
  const selectAll = () => {
    selection.selectedCells.clear()
    selection.selectedRows.clear()
    selection.selectedColumns.clear()
    
    // Select all rows
    data.value.forEach(row => {
      selection.selectedRows.add(row.id)
    })
    
    // Select all columns
    columns.value.forEach(column => {
      selection.selectedColumns.add(column.key)
    })
    
    // Select all cells
    data.value.forEach((_, rowIndex) => {
      columns.value.forEach((_, colIndex) => {
        const key = getSelectionKey(rowIndex, colIndex)
        selection.selectedCells.add(key)
      })
    })
    
    selection.activeSelection = {
      start: { row: 0, col: 0 },
      end: { row: data.value.length - 1, col: columns.value.length - 1 },
      type: 'range'
    }
  }
  
  // Clear all selections
  const clearSelection = () => {
    selection.selectedCells.clear()
    selection.selectedRows.clear()
    selection.selectedColumns.clear()
    selection.activeSelection = null
    selection.dragSelection = false
  }
  
  // Check if a cell is selected
  const isCellSelected = (row: number, col: number): boolean => {
    const key = getSelectionKey(row, col)
    return selection.selectedCells.has(key)
  }
  
  // Check if a row is selected
  const isRowSelected = (rowIndex: number): boolean => {
    const row = data.value[rowIndex]
    return row ? selection.selectedRows.has(row.id) : false
  }
  
  // Check if a column is selected
  const isColumnSelected = (colIndex: number): boolean => {
    const column = columns.value[colIndex]
    return column ? selection.selectedColumns.has(column.key) : false
  }
  
  // Get selected cell positions
  const getSelectedCellPositions = (): Array<{ row: number; col: number }> => {
    return Array.from(selection.selectedCells).map(key => {
      const [row, col] = key.split('-').map(Number)
      return { row, col }
    })
  }
  
  // Get selected data for clipboard operations with formatting
  const getSelectedData = (formatted = false): string[][] => {
    const positions = getSelectedCellPositions()
    if (positions.length === 0) return []
    
    // Sort positions by row, then by column
    positions.sort((a, b) => a.row - b.row || a.col - b.col)
    
    // Group by rows
    const rowMap = new Map<number, Array<{ col: number; value: any; column: DataTableColumn<T> }>>()
    
    positions.forEach(({ row, col }) => {
      if (!rowMap.has(row)) {
        rowMap.set(row, [])
      }
      
      const rowData = data.value[row]
      const column = columns.value[col]
      
      if (rowData && column) {
        const value = (rowData.data as any)[column.key]
        rowMap.get(row)!.push({ col, value, column })
      }
    })
    
    // Convert to 2D array with optional formatting
    const result: string[][] = []
    
    for (const [, cells] of rowMap) {
      cells.sort((a, b) => a.col - b.col)
      const row = cells.map(cell => {
        if (formatted) {
          // Use formatters for display values
          try {
            if (cell.column.formatter) {
              return cell.column.formatter(cell.value, undefined)
            }
            
            switch (cell.column.type) {
              case 'number':
                return formatters.number(cell.value, 2)
              case 'currency':
                return formatters.currency(cell.value, 'USD')
              case 'date':
                return formatters.date(cell.value, 'short')
              case 'boolean':
                return formatters.boolean(cell.value, 'yes-no')
              default:
                return formatters.text(cell.value)
            }
          } catch (error) {
            return String(cell.value || '')
          }
        } else {
          // Raw values for data processing
          return String(cell.value || '')
        }
      })
      result.push(row)
    }
    
    return result
  }
  
  // Set selection from external data (e.g., clipboard paste)
  const setSelectionData = (pasteData: string[][], startRow: number, startCol: number) => {
    if (pasteData.length === 0) return
    
    clearSelection()
    
    const endRow = Math.min(startRow + pasteData.length - 1, data.value.length - 1)
    const endCol = Math.min(startCol + pasteData[0].length - 1, columns.value.length - 1)
    
    // Select the range where data will be pasted
    for (let row = startRow; row <= endRow; row++) {
      for (let col = startCol; col <= endCol; col++) {
        const key = getSelectionKey(row, col)
        selection.selectedCells.add(key)
      }
    }
    
    selection.activeSelection = {
      start: { row: startRow, col: startCol },
      end: { row: endRow, col: endCol },
      type: 'range'
    }
  }
  
  return {
    // Methods
    handleCellSelection,
    handleRangeSelection,
    handleRowSelection,
    handleColumnSelection,
    selectAll,
    clearSelection,
    
    // Getters
    isCellSelected,
    isRowSelected,
    isColumnSelected,
    getSelectedCellPositions,
    getSelectedData,
    setSelectionData,
    
    // Utilities
    updateCellSelectionFromColumns,
    updateCellSelectionFromRows
  }
}

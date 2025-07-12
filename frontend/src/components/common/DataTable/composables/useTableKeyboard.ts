import { type Ref } from 'vue'
import type { 
  DataTableColumn, 
  DataTableRow, 
  SelectionState, 
  EditingState, 
  CellState 
} from '../types'
import keyboardUtils from '../utils/keyboard'
import clipboardUtils from '../utils/clipboard'

export interface UseTableKeyboardOptions<T = any> {
  columns: Ref<DataTableColumn<T>[]>
  data: Ref<DataTableRow<T>[]>
  selection: SelectionState
  editing: { editingCell: Ref<CellState | null>; isEditing: Ref<boolean> }
  startEditing: (row: number, col: number) => boolean
  saveEdit: () => Promise<boolean>
  cancelEditing: () => void
}

export function useTableKeyboard<T = any>(options: UseTableKeyboardOptions<T>) {
  const { columns, data, selection, editing, startEditing, saveEdit, cancelEditing } = options
  
  // Get current active cell position
  const getActiveCellPosition = (): { row: number; col: number } | null => {
    if (editing.editingCell.value) {
      return { row: editing.editingCell.value.row, col: editing.editingCell.value.col }
    }
    
    if (selection.activeSelection) {
      return selection.activeSelection.end
    }
    
    return null
  }
  
  // Move selection in a direction
  const moveSelection = (rowDelta: number, colDelta: number, extend = false) => {
    const current = getActiveCellPosition()
    if (!current) {
      // Start at top-left if no selection
      moveToCell(0, 0, extend)
      return
    }
    
    const newRow = Math.max(0, Math.min(data.value.length - 1, current.row + rowDelta))
    const newCol = Math.max(0, Math.min(columns.value.length - 1, current.col + colDelta))
    
    moveToCell(newRow, newCol, extend)
  }
  
  // Move to specific cell
  const moveToCell = (row: number, col: number, extend = false) => {
    if (row < 0 || row >= data.value.length || col < 0 || col >= columns.value.length) {
      return
    }
    
    if (extend && selection.activeSelection) {
      // Extend selection
      selection.activeSelection.end = { row, col }
      updateSelectionRange()
    } else {
      // Single cell selection
      selection.selectedCells.clear()
      const key = `${row}-${col}`
      selection.selectedCells.add(key)
      
      selection.activeSelection = {
        start: { row, col },
        end: { row, col },
        type: 'single'
      }
    }
    
    // Scroll cell into view
    scrollCellIntoView(row, col)
  }
  
  // Update selection range based on start and end
  const updateSelectionRange = () => {
    if (!selection.activeSelection) return
    
    const { start, end } = selection.activeSelection
    
    selection.selectedCells.clear()
    
    const minRow = Math.min(start.row, end.row)
    const maxRow = Math.max(start.row, end.row)
    const minCol = Math.min(start.col, end.col)
    const maxCol = Math.max(start.col, end.col)
    
    for (let row = minRow; row <= maxRow; row++) {
      for (let col = minCol; col <= maxCol; col++) {
        const key = `${row}-${col}`
        selection.selectedCells.add(key)
      }
    }
    
    selection.activeSelection.type = 'range'
  }
  
  // Scroll cell into view
  const scrollCellIntoView = (row: number, col: number) => {
    const cell = document.querySelector(`[data-cell="${row}-${col}"]`)
    if (cell) {
      cell.scrollIntoView({ 
        behavior: 'smooth', 
        block: 'nearest', 
        inline: 'nearest' 
      })
    }
  }
  
  // Handle Tab navigation
  const handleTab = (shiftKey = false) => {
    const current = getActiveCellPosition()
    if (!current) return
    
    let { row, col } = current
    
    if (shiftKey) {
      // Move backwards
      col--
      if (col < 0) {
        col = columns.value.length - 1
        row--
        if (row < 0) {
          row = 0
          col = 0
        }
      }
    } else {
      // Move forwards
      col++
      if (col >= columns.value.length) {
        col = 0
        row++
        if (row >= data.value.length) {
          row = data.value.length - 1
          col = columns.value.length - 1
        }
      }
    }
    
    moveToCell(row, col)
    
    // Start editing if the new cell is editable
    const column = columns.value[col]
    if (column?.editable) {
      startEditing(row, col)
    }
  }
  
  // Handle Enter navigation
  const handleEnter = (shiftKey = false) => {
    const current = getActiveCellPosition()
    if (!current) return
    
    if (editing.isEditing.value) {
      // Save current edit and move
      saveEdit().then(success => {
        if (success) {
          const newRow = shiftKey ? current.row - 1 : current.row + 1
          if (newRow >= 0 && newRow < data.value.length) {
            moveToCell(newRow, current.col)
            
            // Start editing if the new cell is editable
            const column = columns.value[current.col]
            if (column?.editable) {
              startEditing(newRow, current.col)
            }
          }
        }
      })
    } else {
      // Start editing current cell or move
      const column = columns.value[current.col]
      if (column?.editable) {
        startEditing(current.row, current.col)
      } else {
        // Move to next/previous row
        moveSelection(shiftKey ? -1 : 1, 0)
      }
    }
  }
  
  // Handle Home/End keys
  const handleHome = (ctrlKey = false) => {
    const current = getActiveCellPosition()
    if (!current) return
    
    if (ctrlKey) {
      // Go to top-left cell
      moveToCell(0, 0)
    } else {
      // Go to first column of current row
      moveToCell(current.row, 0)
    }
  }
  
  const handleEnd = (ctrlKey = false) => {
    const current = getActiveCellPosition()
    if (!current) return
    
    if (ctrlKey) {
      // Go to bottom-right cell
      moveToCell(data.value.length - 1, columns.value.length - 1)
    } else {
      // Go to last column of current row
      moveToCell(current.row, columns.value.length - 1)
    }
  }
  
  // Handle Page Up/Down
  const handlePageUp = () => {
    moveSelection(-10, 0) // Move up 10 rows
  }
  
  const handlePageDown = () => {
    moveSelection(10, 0) // Move down 10 rows
  }
  
  // Handle Delete key
  const handleDelete = () => {
    if (editing.isEditing.value) return
    
    // Clear content of selected cells
    const positions = Array.from(selection.selectedCells).map(key => {
      const [row, col] = key.split('-').map(Number)
      return { row, col }
    })
    
    positions.forEach(({ row, col }) => {
      const rowData = data.value[row]
      const column = columns.value[col]
      
      if (rowData && column && column.editable) {
        (rowData.data as any)[column.key] = null
      }
    })
  }
  
  // Handle copy operation
  const handleCopy = async (): Promise<boolean> => {
    const positions = Array.from(selection.selectedCells).map(key => {
      const [row, col] = key.split('-').map(Number)
      return { row, col }
    })
    
    if (positions.length === 0) return false
    
    // Sort positions
    positions.sort((a, b) => a.row - b.row || a.col - b.col)
    
    // Group by rows
    const rowMap = new Map<number, Array<{ col: number; value: any }>>()
    
    positions.forEach(({ row, col }) => {
      if (!rowMap.has(row)) {
        rowMap.set(row, [])
      }
      
      const rowData = data.value[row]
      const column = columns.value[col]
      
      if (rowData && column) {
        const value = (rowData.data as any)[column.key]
        rowMap.get(row)!.push({ col, value })
      }
    })
    
    // Convert to text format
    const dataRows: string[][] = []
    
    for (const [, cells] of rowMap) {
      cells.sort((a, b) => a.col - b.col)
      const row = cells.map(cell => String(cell.value || ''))
      dataRows.push(row)
    }
    
    try {
      // Use clipboard utilities for better error handling and features
      const result = await clipboardUtils.copy(dataRows, {
        sanitizeHTML: true,
        maxRows: 10000,
        maxColumns: 100
      })
      
      if (result.success) {
        // Announce to screen readers
        keyboardUtils.accessibility.announce(`Copied ${dataRows.length} rows to clipboard`)
        return true
      } else {
        console.error('Copy failed:', result.error)
        return false
      }
    } catch (error) {
      console.error('Copy operation failed:', error)
      return false
    }
  }
  
  // Handle paste operation using clipboard utilities
  const handlePaste = async (): Promise<boolean> => {
    try {
      const result = await clipboardUtils.paste({
        sanitizeHTML: true,
        maxRows: 10000,
        maxColumns: 100
      })
      
      if (!result.success || !result.data) {
        console.error('Paste failed:', result.error)
        return false
      }
      
      const pasteData = result.data
      const current = getActiveCellPosition()
      if (!current) return false
      
      // Paste data starting from current position
      for (let rowOffset = 0; rowOffset < pasteData.length; rowOffset++) {
        const targetRow = current.row + rowOffset
        if (targetRow >= data.value.length) break
        
        const rowData = data.value[targetRow]
        if (!rowData) continue
        
        for (let colOffset = 0; colOffset < pasteData[rowOffset].length; colOffset++) {
          const targetCol = current.col + colOffset
          if (targetCol >= columns.value.length) break
          
          const column = columns.value[targetCol]
          if (!column?.editable) continue
          
          const value = pasteData[rowOffset][colOffset]
          
          // Parse value based on column type
          let parsedValue: any = value
          
          switch (column.type) {
            case 'number':
            case 'currency':
              parsedValue = value === '' ? null : Number(value)
              break
            case 'boolean':
              parsedValue = value.toLowerCase() === 'true' || value === '1'
              break
            case 'date':
              parsedValue = value ? new Date(value) : null
              break
          }
          
          (rowData.data as any)[column.key] = parsedValue
        }
      }
      
      return true
    } catch (error) {
      console.error('Failed to paste:', error)
      return false
    }
  }
  
  // Handle select all
  const handleSelectAll = () => {
    selection.selectedCells.clear()
    selection.selectedRows.clear()
    selection.selectedColumns.clear()
    
    // Select all cells
    data.value.forEach((_, rowIndex) => {
      columns.value.forEach((_, colIndex) => {
        const key = `${rowIndex}-${colIndex}`
        selection.selectedCells.add(key)
      })
    })
    
    selection.activeSelection = {
      start: { row: 0, col: 0 },
      end: { row: data.value.length - 1, col: columns.value.length - 1 },
      type: 'range'
    }
  }
  
  // Main keyboard event handler using keyboard utilities
  const handleKeyDown = (event: KeyboardEvent) => {
    // Don't handle keys when editing (except special ones)
    if (editing.isEditing.value) {
      if (keyboardUtils.matches(event, 'Tab')) {
        keyboardUtils.preventDefault(event)
        saveEdit().then(() => handleTab(event.shiftKey))
      } else if (keyboardUtils.matches(event, 'Enter')) {
        keyboardUtils.preventDefault(event)
        handleEnter(event.shiftKey)
      } else if (keyboardUtils.isEscapeKey(event)) {
        keyboardUtils.preventDefault(event)
        cancelEditing()
      }
      return
    }
    
    // Handle navigation keys
    if (keyboardUtils.isNavigationKey(event)) {
      keyboardUtils.preventDefault(event)
      
      switch (event.key) {
        case 'ArrowUp':
          moveSelection(-1, 0, event.shiftKey)
          break
        case 'ArrowDown':
          moveSelection(1, 0, event.shiftKey)
          break
        case 'ArrowLeft':
          moveSelection(0, -1, event.shiftKey)
          break
        case 'ArrowRight':
          moveSelection(0, 1, event.shiftKey)
          break
        case 'Tab':
          handleTab(event.shiftKey)
          break
        case 'Enter':
          handleEnter(event.shiftKey)
          break
        case 'Home':
          handleHome(event.ctrlKey || event.metaKey)
          break
        case 'End':
          handleEnd(event.ctrlKey || event.metaKey)
          break
        case 'PageUp':
          handlePageUp()
          break
        case 'PageDown':
          handlePageDown()
          break
      }
      return
    }
    
    // Handle edit keys
    if (keyboardUtils.isEditKey(event)) {
      keyboardUtils.preventDefault(event)
      const current = getActiveCellPosition()
      if (current) {
        startEditing(current.row, current.col)
      }
      return
    }
    
    // Handle delete keys
    if (keyboardUtils.isDeleteKey(event)) {
      keyboardUtils.preventDefault(event)
      handleDelete()
      return
    }
    
    // Handle clipboard operations
    if (keyboardUtils.isClipboardKey(event)) {
      keyboardUtils.preventDefault(event)
      
      if (keyboardUtils.matches(event, 'c', { ctrl: true }) || keyboardUtils.matches(event, 'c', { meta: true })) {
        handleCopy()
      } else if (keyboardUtils.matches(event, 'v', { ctrl: true }) || keyboardUtils.matches(event, 'v', { meta: true })) {
        handlePaste()
      }
      return
    }
    
    // Handle select all
    if (keyboardUtils.isSelectAllKey(event)) {
      keyboardUtils.preventDefault(event)
      handleSelectAll()
      return
    }
    
    // Handle printable characters to start editing
    if (keyboardUtils.isPrintable(event)) {
      const current = getActiveCellPosition()
      if (current) {
        const column = columns.value[current.col]
        if (column?.editable) {
          startEditing(current.row, current.col)
        }
      }
    }
  }
  
  return {
    handleKeyDown,
    moveSelection,
    moveToCell,
    handleTab,
    handleEnter,
    handleCopy,
    handlePaste,
    handleSelectAll,
    getActiveCellPosition
  }
}

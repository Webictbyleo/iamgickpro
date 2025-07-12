import { ref, reactive, nextTick, type Ref } from 'vue'
import type { 
  CellState, 
  DataTableColumn, 
  DataTableRow, 
  EditingState,
  ValidationResult 
} from '../types'
import formatters from '../utils/formatters'
import validators from '../utils/validators'

export interface UseTableEditingOptions<T = any> {
  columns: Ref<DataTableColumn<T>[]>
  data: Ref<DataTableRow<T>[]>
  autoSave?: boolean
  saveDelay?: number
  onCellChange?: (cellState: CellState, oldValue: any) => void
  onValidation?: (cellState: CellState) => ValidationResult
}

export function useTableEditing<T = any>(options: UseTableEditingOptions<T>) {
  // Editing state
  const editingCell = ref<CellState | null>(null)
  const tempValue = ref('')
  const originalValue = ref<any>(null)
  const isEditing = ref(false)
  const saveTimeout = ref<NodeJS.Timeout | null>(null)
  
  // Validation state
  const validationErrors = reactive<Record<string, string>>({})
  const validationWarnings = reactive<Record<string, string[]>>({})
  
  // Get cell key for tracking
  const getCellKey = (row: number, col: number) => `${row}-${col}`
  
  // Get column by index
  const getColumn = (colIndex: number): DataTableColumn<T> | undefined => {
    return options.columns.value[colIndex]
  }
  
  // Get row by index
  const getRow = (rowIndex: number): DataTableRow<T> | undefined => {
    return options.data.value[rowIndex]
  }
  
  // Start editing a cell
  const startEditing = (row: number, col: number, field?: string) => {
    const column = getColumn(col)
    const rowData = getRow(row)
    
    if (!column || !rowData || !column.editable) {
      return false
    }
    
    // Cancel any existing edit
    cancelEditing()
    
    const cellKey = getCellKey(row, col)
    const currentValue = (rowData.data as any)[column.key]
    
    editingCell.value = {
      row,
      col,
      key: column.key,
      editing: true,
      selected: true,
      value: currentValue,
      originalValue: currentValue,
      invalid: false,
      dirty: false
    }
    
    tempValue.value = formatValueForEditing(currentValue, column)
    originalValue.value = currentValue
    isEditing.value = true
    
    // Focus the input after DOM update
    nextTick(() => {
      const input = document.querySelector(`[data-cell="${cellKey}"] input, [data-cell="${cellKey}"] textarea, [data-cell="${cellKey}"] select`)
      if (input instanceof HTMLElement) {
        input.focus()
        
        // Select all text for text inputs
        if (input instanceof HTMLInputElement && input.type === 'text') {
          input.select()
        }
      }
    })
    
    return true
  }
  
  // Save the current edit
  const saveEdit = async (): Promise<boolean> => {
    if (!editingCell.value || !isEditing.value) {
      return false
    }
    
    try {
      const { row, col, key } = editingCell.value
      const column = getColumn(col)
      const rowData = getRow(row)
      
      if (!column || !rowData) {
        console.error('Cannot save edit: missing column or row data')
        return false
      }
      
      // Parse the value based on column type
      const parsedValue = parseValue(tempValue.value, column)
      
      // Validate the value
      const validation = await validateValue(parsedValue, column, rowData)
      
      if (!validation.valid) {
        // Show validation error
        if (editingCell.value) {
          editingCell.value.invalid = true
          editingCell.value.error = validation.error
        }
        
        const cellKey = getCellKey(row, col)
        validationErrors[cellKey] = validation.error || 'Invalid value'
        
        return false
      }
      
      // Clear any previous validation errors
      const cellKey = getCellKey(row, col)
      delete validationErrors[cellKey]
      delete validationWarnings[cellKey]
      
      // Update the data
      const oldValue = (rowData.data as any)[key]
      (rowData.data as any)[key] = parsedValue
      
      // Mark cell as dirty if value changed
      const isDirty = oldValue !== parsedValue
      if (editingCell.value) {
        editingCell.value.dirty = isDirty
      }
      
      // Call change callback
      if (isDirty && options.onCellChange && editingCell.value) {
        options.onCellChange(editingCell.value, oldValue)
      }
      
      // Auto-save if enabled
      if (options.autoSave && isDirty) {
        scheduleAutoSave()
      }
      
      // Clear editing state
      clearEditingState()
      
      return true
    } catch (error) {
      console.error('Error saving edit:', error)
      return false
    }
  }
  
  // Cancel the current edit
  const cancelEditing = () => {
    if (editingCell.value) {
      // Restore original value if it was changed
      const { row, col, key } = editingCell.value
      const rowData = getRow(row)
      
      if (rowData && originalValue.value !== undefined) {
        (rowData.data as any)[key] = originalValue.value
      }
    }
    
    clearEditingState()
  }
  
  // Clear editing state
  const clearEditingState = () => {
    editingCell.value = null
    tempValue.value = ''
    originalValue.value = null
    isEditing.value = false
    
    if (saveTimeout.value) {
      clearTimeout(saveTimeout.value)
      saveTimeout.value = null
    }
  }
  
  // Format value for editing using formatters utility
  const formatValueForEditing = (value: any, column: DataTableColumn<T>): string => {
    if (value === null || value === undefined) {
      return ''
    }
    
    try {
      // Use formatters utility for consistent formatting
      switch (column.type) {
        case 'number':
          return formatters.number(value, 10) // Higher precision for editing
        case 'currency':
          return formatters.number(value, 2) // Just the number part for editing
        case 'date':
          if (value instanceof Date) {
            return value.toISOString().split('T')[0]
          }
          return formatters.date(value, 'short')
        case 'boolean':
          return formatters.boolean(value, 'true-false')
        case 'email':
        case 'url':
        case 'text':
        case 'textarea':
        case 'password':
          return formatters.text(value)
        default:
          return formatters.text(value)
      }
    } catch (error) {
      console.error('Error formatting value for editing:', error)
      return String(value || '')
    }
  }
  
  // Parse value from string based on column type
  const parseValue = (value: string, column: DataTableColumn<T>): any => {
    if (value === '' && !column.required) {
      return null
    }
    
    switch (column.type) {
      case 'number':
      case 'currency':
        const num = Number(value)
        return isNaN(num) ? null : num
      
      case 'date':
        const date = new Date(value)
        return isNaN(date.getTime()) ? null : date
      
      case 'boolean':
        return value === 'true' || value === '1' || value === 'yes'
      
      case 'select':
        // Find the option value
        const option = column.options?.find(opt => opt.label === value || opt.value === value)
        return option ? option.value : value
      
      default:
        return value
    }
  }
  
  // Validate a value against column rules using validators utility
  const validateValue = async (value: any, column: DataTableColumn<T>, row?: DataTableRow<T>): Promise<ValidationResult> => {
    try {
      // Check required using validators utility
      if (column.required) {
        const requiredResult = validators.required(value, column.label)
        if (requiredResult !== true) {
          return { valid: false, error: typeof requiredResult === 'string' ? requiredResult : `${column.label} is required` }
        }
      }
      
      // Skip validation for empty optional fields
      if (!column.required && (value === null || value === undefined || value === '')) {
        return { valid: true }
      }
      
      // Type-specific validation using validators utility
      let validationResult: boolean | string = true
      
      switch (column.type) {
        case 'email':
          validationResult = validators.email(value)
          break
        
        case 'url':
          validationResult = validators.url(value)
          break
        
        case 'number':
          validationResult = validators.number(value)
          break
        
        case 'currency':
          validationResult = validators.number(value)
          if (validationResult === true) {
            // Additional currency validation
            validationResult = validators.positive(value, true) // Allow zero
          }
          break
        
        case 'password':
          // Use basic validation for password, full validation can be in custom validator
          validationResult = validators.minLength(8)(value)
          break
        
        case 'boolean':
          // Boolean values are generally always valid if present
          validationResult = true
          break
        
        case 'date':
          validationResult = validators.date(value)
          break
        
        case 'text':
        case 'textarea':
        case 'select':
        default:
          // Basic text validation - just check it's not excessively long
          if (typeof value === 'string' && value.length > 10000) {
            validationResult = 'Value is too long (maximum 10000 characters)'
          }
          break
      }
      
      if (validationResult !== true) {
        return { valid: false, error: typeof validationResult === 'string' ? validationResult : 'Invalid value' }
      }
      
      // Custom validator
      if (column.validator) {
        const result = column.validator(value, row?.data)
        if (typeof result === 'string') {
          return { valid: false, error: result }
        }
        if (result === false) {
          return { valid: false, error: `Invalid value for ${column.label}` }
        }
      }
      
      // Call external validation if provided
      if (options.onValidation && editingCell.value) {
        return options.onValidation(editingCell.value)
      }
      
      return { valid: true }
    } catch (error) {
      console.error('Validation error:', error)
      return { valid: false, error: 'Validation failed due to an error' }
    }
  }
  
  // Schedule auto-save
  const scheduleAutoSave = () => {
    if (saveTimeout.value) {
      clearTimeout(saveTimeout.value)
    }
    
    saveTimeout.value = setTimeout(() => {
      // Implement actual save logic here
      console.log('Auto-saving changes...')
    }, options.saveDelay || 1000)
  }
  
  // Handle keyboard events during editing
  const handleEditKeydown = (event: KeyboardEvent) => {
    if (!isEditing.value) return
    
    switch (event.key) {
      case 'Enter':
        event.preventDefault()
        saveEdit().then(success => {
          if (success) {
            // Move to next row
            const nextRow = editingCell.value!.row + 1
            if (nextRow < options.data.value.length) {
              startEditing(nextRow, editingCell.value!.col)
            }
          }
        })
        break
      
      case 'Tab':
        event.preventDefault()
        saveEdit().then(success => {
          if (success) {
            // Move to next cell
            const currentRow = editingCell.value!.row
            const nextCol = editingCell.value!.col + (event.shiftKey ? -1 : 1)
            
            if (nextCol >= 0 && nextCol < options.columns.value.length) {
              startEditing(currentRow, nextCol)
            } else if (!event.shiftKey && currentRow + 1 < options.data.value.length) {
              // Move to first column of next row
              startEditing(currentRow + 1, 0)
            } else if (event.shiftKey && currentRow > 0) {
              // Move to last column of previous row
              startEditing(currentRow - 1, options.columns.value.length - 1)
            }
          }
        })
        break
      
      case 'Escape':
        event.preventDefault()
        cancelEditing()
        break
    }
  }
  
  // Quick edit (double-click)
  const quickEdit = (row: number, col: number) => {
    return startEditing(row, col)
  }
  
  // Batch edit multiple cells
  const batchEdit = (cells: Array<{ row: number; col: number; value: any }>) => {
    cells.forEach(({ row, col, value }) => {
      const column = getColumn(col)
      const rowData = getRow(row)
      
      if (column && rowData && column.editable) {
        (rowData.data as any)[column.key] = value
      }
    })
  }
  
  // Get validation state for a cell
  const getCellValidation = (row: number, col: number) => {
    const cellKey = getCellKey(row, col)
    return {
      error: validationErrors[cellKey],
      warnings: validationWarnings[cellKey] || [],
      hasError: !!validationErrors[cellKey],
      hasWarnings: (validationWarnings[cellKey] || []).length > 0
    }
  }
  
  return {
    // State
    editingCell,
    tempValue,
    isEditing,
    validationErrors,
    validationWarnings,
    
    // Methods
    startEditing,
    saveEdit,
    cancelEditing,
    clearEditingState,
    handleEditKeydown,
    quickEdit,
    batchEdit,
    getCellValidation,
    validateValue,
    formatValueForEditing,
    parseValue
  }
}

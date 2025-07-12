export interface CellPosition {
  row: number
  col: number
  key: string
}

export interface CellValue {
  raw: any
  formatted: string
  valid: boolean
  dirty: boolean
}

export interface CellEditState {
  position: CellPosition
  value: CellValue
  originalValue: any
  editing: boolean
  focused: boolean
  selected: boolean
  error?: string
  warning?: string
}

export interface CellRenderer {
  type: 'text' | 'number' | 'date' | 'select' | 'boolean' | 'custom'
  props?: Record<string, any>
  component?: any
}

export interface CellEditor {
  type: 'input' | 'textarea' | 'select' | 'datepicker' | 'checkbox' | 'custom'
  props?: Record<string, any>
  component?: any
  validation?: CellValidation
}

export interface CellValidation {
  required?: boolean
  min?: number
  max?: number
  pattern?: RegExp
  custom?: (value: any) => boolean | string
  async?: boolean
}

export interface CellStyle {
  backgroundColor?: string
  color?: string
  fontWeight?: string
  fontStyle?: string
  textAlign?: string
  border?: string
  className?: string
}

export interface CellMetadata {
  tooltip?: string
  comment?: string
  formula?: string
  dependencies?: CellPosition[]
  readonly?: boolean
  locked?: boolean
  hidden?: boolean
}

export interface CellChangeEvent {
  position: CellPosition
  oldValue: any
  newValue: any
  column: string
  row: number
  source: 'user' | 'api' | 'formula' | 'import'
  timestamp: number
}

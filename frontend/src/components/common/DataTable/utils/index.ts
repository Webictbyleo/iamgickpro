// DataTable utilities index - centralized exports for easy importing

// Main utility exports
export { default as clipboardUtils } from './clipboard'
export { default as formatters } from './formatters'
export { default as keyboardUtils } from './keyboard'
export { default as validators } from './validators'

// Type exports
export type { ClipboardOptions } from './clipboard'

// Type exports for utilities
export type ClipboardResult = {
  success: boolean
  data?: string[][]
  error?: string
}

export type ValidationResult = boolean | string

export type ValidatorFunction = (value: any) => ValidationResult

export type AsyncValidatorFunction = (value: any) => Promise<ValidationResult>

export type KeyboardShortcut = {
  key: string
  ctrl?: boolean
  shift?: boolean
  alt?: boolean
  meta?: boolean
}

export type FormatterFunction = (value: any, ...args: any[]) => string

// Utility helper functions
export const utils = {
  // Check if a value is empty (null, undefined, or empty string)
  isEmpty: (value: any): boolean => {
    return value === null || value === undefined || value === ''
  },

  // Debounce function for performance optimization
  debounce: <T extends (...args: any[]) => any>(
    func: T,
    wait: number
  ): ((...args: Parameters<T>) => void) => {
    let timeout: NodeJS.Timeout
    return (...args: Parameters<T>) => {
      clearTimeout(timeout)
      timeout = setTimeout(() => func(...args), wait)
    }
  },

  // Throttle function for performance optimization
  throttle: <T extends (...args: any[]) => any>(
    func: T,
    wait: number
  ): ((...args: Parameters<T>) => void) => {
    let inThrottle: boolean
    return (...args: Parameters<T>) => {
      if (!inThrottle) {
        func(...args)
        inThrottle = true
        setTimeout(() => inThrottle = false, wait)
      }
    }
  },

  // Deep clone utility
  deepClone: <T>(obj: T): T => {
    if (obj === null || typeof obj !== 'object') return obj
    if (obj instanceof Date) return new Date(obj.getTime()) as unknown as T
    if (obj instanceof Array) return obj.map(item => utils.deepClone(item)) as unknown as T
    if (typeof obj === 'object') {
      const clonedObj = {} as { [key: string]: any }
      for (const key in obj) {
        if (obj.hasOwnProperty(key)) {
          clonedObj[key] = utils.deepClone(obj[key])
        }
      }
      return clonedObj as T
    }
    return obj
  },

  // Safe JSON parse with fallback
  safeJsonParse: <T = any>(str: string, fallback: T): T => {
    try {
      return JSON.parse(str)
    } catch {
      return fallback
    }
  },

  // Generate unique ID
  generateId: (prefix = 'id'): string => {
    return `${prefix}_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`
  },

  // Format file size for human reading
  formatFileSize: (bytes: number): string => {
    if (bytes === 0) return '0 B'
    
    const units = ['B', 'KB', 'MB', 'GB', 'TB']
    const i = Math.floor(Math.log(bytes) / Math.log(1024))
    const size = bytes / Math.pow(1024, i)
    
    return `${size.toFixed(i === 0 ? 0 : 1)} ${units[i]}`
  },

  // Escape HTML to prevent XSS
  escapeHtml: (text: string): string => {
    const div = document.createElement('div')
    div.textContent = text
    return div.innerHTML
  },

  // Get nested object property safely
  getNestedProperty: (obj: any, path: string, defaultValue: any = undefined): any => {
    try {
      return path.split('.').reduce((current, key) => current?.[key], obj) ?? defaultValue
    } catch {
      return defaultValue
    }
  },

  // Set nested object property safely
  setNestedProperty: (obj: any, path: string, value: any): void => {
    const keys = path.split('.')
    const lastKey = keys.pop()
    
    if (!lastKey) return
    
    const target = keys.reduce((current, key) => {
      if (!current[key] || typeof current[key] !== 'object') {
        current[key] = {}
      }
      return current[key]
    }, obj)
    
    target[lastKey] = value
  }
}

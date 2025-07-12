// Data formatters for different column types
const formatters = {
  // Text formatter
  text: (value: any): string => {
    if (value === null || value === undefined) return ''
    return String(value)
  },

  // Number formatter
  number: (value: any, decimals = 2): string => {
    if (value === null || value === undefined || isNaN(Number(value))) return ''
    return Number(value).toLocaleString(undefined, {
      minimumFractionDigits: 0,
      maximumFractionDigits: decimals
    })
  },

  // Currency formatter
  currency: (value: any, currency = 'USD', locale = 'en-US'): string => {
    if (value === null || value === undefined || isNaN(Number(value))) return ''
    return new Intl.NumberFormat(locale, {
      style: 'currency',
      currency
    }).format(Number(value))
  },

  // Percentage formatter
  percentage: (value: any, decimals = 1): string => {
    if (value === null || value === undefined || isNaN(Number(value))) return ''
    return new Intl.NumberFormat(undefined, {
      style: 'percent',
      minimumFractionDigits: 0,
      maximumFractionDigits: decimals
    }).format(Number(value) / 100)
  },

  // Date formatter
  date: (value: any, format: 'short' | 'medium' | 'long' | 'full' = 'short'): string => {
    if (!value) return ''
    
    const date = value instanceof Date ? value : new Date(value)
    if (isNaN(date.getTime())) return String(value)
    
    const options: Intl.DateTimeFormatOptions = {
      short: { year: 'numeric', month: 'numeric', day: 'numeric' },
      medium: { year: 'numeric', month: 'short', day: 'numeric' },
      long: { year: 'numeric', month: 'long', day: 'numeric' },
      full: { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' }
    }[format] as Intl.DateTimeFormatOptions
    
    return date.toLocaleDateString(undefined, options)
  },

  // DateTime formatter
  datetime: (value: any, format: 'short' | 'medium' | 'long' = 'medium'): string => {
    if (!value) return ''
    
    const date = value instanceof Date ? value : new Date(value)
    if (isNaN(date.getTime())) return String(value)
    
    const options: Intl.DateTimeFormatOptions = {
      short: { 
        year: 'numeric', month: 'numeric', day: 'numeric',
        hour: 'numeric', minute: 'numeric'
      },
      medium: { 
        year: 'numeric', month: 'short', day: 'numeric',
        hour: 'numeric', minute: 'numeric', second: 'numeric'
      },
      long: { 
        year: 'numeric', month: 'long', day: 'numeric',
        hour: 'numeric', minute: 'numeric', second: 'numeric', timeZoneName: 'short'
      }
    }[format] as Intl.DateTimeFormatOptions
    
    return date.toLocaleString(undefined, options)
  },

  // Time formatter
  time: (value: any, format: 'short' | 'medium' | 'long' = 'short'): string => {
    if (!value) return ''
    
    const date = value instanceof Date ? value : new Date(value)
    if (isNaN(date.getTime())) return String(value)
    
    const options: Intl.DateTimeFormatOptions = {
      short: { hour: 'numeric', minute: 'numeric' },
      medium: { hour: 'numeric', minute: 'numeric', second: 'numeric' },
      long: { hour: 'numeric', minute: 'numeric', second: 'numeric', timeZoneName: 'short' }
    }[format] as Intl.DateTimeFormatOptions
    
    return date.toLocaleTimeString(undefined, options)
  },

  // Boolean formatter
  boolean: (value: any, format: 'yes-no' | 'true-false' | 'on-off' | 'icons' = 'yes-no'): string => {
    const boolValue = Boolean(value)
    
    const formats = {
      'yes-no': boolValue ? 'Yes' : 'No',
      'true-false': boolValue ? 'True' : 'False',
      'on-off': boolValue ? 'On' : 'Off',
      'icons': boolValue ? '✓' : '✗'
    }
    
    return formats[format]
  },

  // File size formatter
  fileSize: (value: any): string => {
    if (value === null || value === undefined || isNaN(Number(value))) return ''
    
    const bytes = Number(value)
    const units = ['B', 'KB', 'MB', 'GB', 'TB']
    
    if (bytes === 0) return '0 B'
    
    const i = Math.floor(Math.log(bytes) / Math.log(1024))
    const size = bytes / Math.pow(1024, i)
    
    return `${size.toFixed(i === 0 ? 0 : 1)} ${units[i]}`
  },

  // Duration formatter (in seconds)
  duration: (value: any): string => {
    if (value === null || value === undefined || isNaN(Number(value))) return ''
    
    const seconds = Number(value)
    const hours = Math.floor(seconds / 3600)
    const minutes = Math.floor((seconds % 3600) / 60)
    const remainingSeconds = Math.floor(seconds % 60)
    
    if (hours > 0) {
      return `${hours}:${minutes.toString().padStart(2, '0')}:${remainingSeconds.toString().padStart(2, '0')}`
    } else if (minutes > 0) {
      return `${minutes}:${remainingSeconds.toString().padStart(2, '0')}`
    } else {
      return `${remainingSeconds}s`
    }
  },

  // Truncate text formatter
  truncate: (value: any, maxLength = 50, suffix = '...'): string => {
    const text = String(value || '')
    if (text.length <= maxLength) return text
    return text.substring(0, maxLength - suffix.length) + suffix
  },

  // Phone number formatter (US format)
  phone: (value: any): string => {
    if (!value) return ''
    
    const digits = String(value).replace(/\D/g, '')
    
    if (digits.length === 10) {
      return `(${digits.substring(0, 3)}) ${digits.substring(3, 6)}-${digits.substring(6)}`
    } else if (digits.length === 11 && digits[0] === '1') {
      return `+1 (${digits.substring(1, 4)}) ${digits.substring(4, 7)}-${digits.substring(7)}`
    }
    
    return String(value)
  },

  // Social Security Number formatter
  ssn: (value: any): string => {
    if (!value) return ''
    
    const digits = String(value).replace(/\D/g, '')
    
    if (digits.length === 9) {
      return `${digits.substring(0, 3)}-${digits.substring(3, 5)}-${digits.substring(5)}`
    }
    
    return String(value)
  },

  // Credit card formatter
  creditCard: (value: any): string => {
    if (!value) return ''
    
    const digits = String(value).replace(/\D/g, '')
    
    // Group digits in sets of 4
    return digits.replace(/(.{4})/g, '$1 ').trim()
  },

  // Capitalize first letter
  capitalize: (value: any): string => {
    const text = String(value || '')
    return text.charAt(0).toUpperCase() + text.slice(1).toLowerCase()
  },

  // Title case formatter
  titleCase: (value: any): string => {
    const text = String(value || '')
    return text.replace(/\w\S*/g, (txt) => 
      txt.charAt(0).toUpperCase() + txt.substr(1).toLowerCase()
    )
  },

  // Upper case formatter
  upperCase: (value: any): string => {
    return String(value || '').toUpperCase()
  },

  // Lower case formatter
  lowerCase: (value: any): string => {
    return String(value || '').toLowerCase()
  },

  // Array formatter
  array: (value: any, separator = ', ', maxItems?: number): string => {
    if (!Array.isArray(value)) return String(value || '')
    
    let items = value
    if (maxItems && items.length > maxItems) {
      items = items.slice(0, maxItems)
      return items.join(separator) + ` (+${value.length - maxItems} more)`
    }
    
    return items.join(separator)
  },

  // JSON formatter
  json: (value: any, pretty = false): string => {
    if (value === null || value === undefined) return ''
    
    try {
      return JSON.stringify(value, null, pretty ? 2 : 0)
    } catch {
      return String(value)
    }
  },

  // Custom formatter helper
  custom: (formatter: (value: any) => string) => formatter
}

export default formatters

// Data formatters for different column types with improved error handling and performance

// Cache for Intl formatters to improve performance
const formatterCache = new Map<string, Intl.NumberFormat | Intl.DateTimeFormat>()

// Get cached formatter or create new one
const getCachedFormatter = (key: string, creator: () => Intl.NumberFormat | Intl.DateTimeFormat) => {
  if (!formatterCache.has(key)) {
    formatterCache.set(key, creator())
  }
  return formatterCache.get(key)!
}

const formatters = {
  // Text formatter with null safety
  text: (value: any): string => {
    if (value === null || value === undefined) return ''
    if (typeof value === 'object') {
      try {
        return JSON.stringify(value)
      } catch {
        return String(value)
      }
    }
    return String(value)
  },

  // Number formatter with improved error handling
  number: (value: any, decimals = 2, locale = 'en-US'): string => {
    if (value === null || value === undefined || value === '') return ''
    
    const num = Number(value)
    if (isNaN(num)) return String(value) // Return original if not a number
    
    // Check for infinite values
    if (!isFinite(num)) return num > 0 ? '∞' : '-∞'
    
    try {
      const cacheKey = `number-${locale}-${decimals}`
      const formatter = getCachedFormatter(cacheKey, () => 
        new Intl.NumberFormat(locale, {
          minimumFractionDigits: 0,
          maximumFractionDigits: decimals
        })
      ) as Intl.NumberFormat
      
      return formatter.format(num)
    } catch (error) {
      // Fallback to basic formatting
      return num.toFixed(decimals)
    }
  },

  // Currency formatter with error handling
  currency: (value: any, currency = 'USD', locale = 'en-US'): string => {
    if (value === null || value === undefined || value === '') return ''
    
    const num = Number(value)
    if (isNaN(num)) return String(value)
    if (!isFinite(num)) return num > 0 ? '∞' : '-∞'
    
    try {
      const cacheKey = `currency-${locale}-${currency}`
      const formatter = getCachedFormatter(cacheKey, () => 
        new Intl.NumberFormat(locale, {
          style: 'currency',
          currency
        })
      ) as Intl.NumberFormat
      
      return formatter.format(num)
    } catch (error) {
      // Fallback formatting
      return `${currency} ${num.toFixed(2)}`
    }
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

  // Date formatter with better error handling
  date: (value: any, format: 'short' | 'medium' | 'long' | 'full' = 'short', locale = 'en-US'): string => {
    if (!value) return ''
    
    let date: Date
    
    // Handle different input types
    if (value instanceof Date) {
      date = value
    } else if (typeof value === 'string' || typeof value === 'number') {
      date = new Date(value)
    } else {
      return String(value) // Return original if can't parse
    }
    
    if (isNaN(date.getTime())) return String(value)
    
    try {
      const options: Intl.DateTimeFormatOptions = {
        short: { year: 'numeric' as const, month: 'numeric' as const, day: 'numeric' as const },
        medium: { year: 'numeric' as const, month: 'short' as const, day: 'numeric' as const },
        long: { year: 'numeric' as const, month: 'long' as const, day: 'numeric' as const },
        full: { weekday: 'long' as const, year: 'numeric' as const, month: 'long' as const, day: 'numeric' as const }
      }[format]
      
      const cacheKey = `date-${locale}-${format}`
      const formatter = getCachedFormatter(cacheKey, () => 
        new Intl.DateTimeFormat(locale, options)
      ) as Intl.DateTimeFormat
      
      return formatter.format(date)
    } catch (error) {
      // Fallback to ISO string
      return date.toLocaleDateString()
    }
  },

  // DateTime formatter with timezone support
  datetime: (value: any, format: 'short' | 'medium' | 'long' = 'medium', locale = 'en-US', timeZone?: string): string => {
    if (!value) return ''
    
    let date: Date
    
    if (value instanceof Date) {
      date = value
    } else if (typeof value === 'string' || typeof value === 'number') {
      date = new Date(value)
    } else {
      return String(value)
    }
    
    if (isNaN(date.getTime())) return String(value)
    
    try {
      const options: Intl.DateTimeFormatOptions = {
        short: { 
          year: 'numeric' as const, month: 'numeric' as const, day: 'numeric' as const,
          hour: 'numeric' as const, minute: 'numeric' as const
        },
        medium: { 
          year: 'numeric' as const, month: 'short' as const, day: 'numeric' as const,
          hour: 'numeric' as const, minute: 'numeric' as const, second: 'numeric' as const
        },
        long: { 
          year: 'numeric' as const, month: 'long' as const, day: 'numeric' as const,
          hour: 'numeric' as const, minute: 'numeric' as const, second: 'numeric' as const, timeZoneName: 'short' as const
        }
      }[format]
      
      if (timeZone) {
        options.timeZone = timeZone
      }
      
      const cacheKey = `datetime-${locale}-${format}-${timeZone || 'default'}`
      const formatter = getCachedFormatter(cacheKey, () => 
        new Intl.DateTimeFormat(locale, options)
      ) as Intl.DateTimeFormat
      
      return formatter.format(date)
    } catch (error) {
      // Fallback
      return date.toLocaleString()
    }
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

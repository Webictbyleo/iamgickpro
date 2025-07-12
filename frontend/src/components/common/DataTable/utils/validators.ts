// Data validators for different column types
const validators = {
  // Required field validator
  required: (value: any): boolean | string => {
    if (value === null || value === undefined || value === '') {
      return 'This field is required'
    }
    return true
  },

  // Email validator
  email: (value: any): boolean | string => {
    if (!value) return true // Allow empty for optional fields
    
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
    return emailRegex.test(String(value)) || 'Please enter a valid email address'
  },

  // URL validator
  url: (value: any): boolean | string => {
    if (!value) return true // Allow empty for optional fields
    
    try {
      new URL(String(value))
      return true
    } catch {
      return 'Please enter a valid URL'
    }
  },

  // Number validators
  number: (value: any): boolean | string => {
    if (value === null || value === undefined || value === '') return true
    return !isNaN(Number(value)) || 'Please enter a valid number'
  },

  integer: (value: any): boolean | string => {
    if (value === null || value === undefined || value === '') return true
    const num = Number(value)
    return (Number.isInteger(num) && !isNaN(num)) || 'Please enter a valid integer'
  },

  positive: (value: any): boolean | string => {
    if (value === null || value === undefined || value === '') return true
    const num = Number(value)
    return (num > 0) || 'Please enter a positive number'
  },

  negative: (value: any): boolean | string => {
    if (value === null || value === undefined || value === '') return true
    const num = Number(value)
    return (num < 0) || 'Please enter a negative number'
  },

  // Range validators
  min: (minValue: number) => (value: any): boolean | string => {
    if (value === null || value === undefined || value === '') return true
    const num = Number(value)
    return (num >= minValue) || `Value must be at least ${minValue}`
  },

  max: (maxValue: number) => (value: any): boolean | string => {
    if (value === null || value === undefined || value === '') return true
    const num = Number(value)
    return (num <= maxValue) || `Value must be at most ${maxValue}`
  },

  range: (minValue: number, maxValue: number) => (value: any): boolean | string => {
    if (value === null || value === undefined || value === '') return true
    const num = Number(value)
    return (num >= minValue && num <= maxValue) || `Value must be between ${minValue} and ${maxValue}`
  },

  // String length validators
  minLength: (minLength: number) => (value: any): boolean | string => {
    if (!value) return true
    const str = String(value)
    return (str.length >= minLength) || `Must be at least ${minLength} characters long`
  },

  maxLength: (maxLength: number) => (value: any): boolean | string => {
    if (!value) return true
    const str = String(value)
    return (str.length <= maxLength) || `Must be at most ${maxLength} characters long`
  },

  exactLength: (length: number) => (value: any): boolean | string => {
    if (!value) return true
    const str = String(value)
    return (str.length === length) || `Must be exactly ${length} characters long`
  },

  // Pattern validators
  pattern: (regex: RegExp, message = 'Invalid format') => (value: any): boolean | string => {
    if (!value) return true
    return regex.test(String(value)) || message
  },

  // Phone number validator (US format)
  phone: (value: any): boolean | string => {
    if (!value) return true
    
    const phoneRegex = /^[\+]?[1]?[\s\-\.]?[\(]?[0-9]{3}[\)]?[\s\-\.]?[0-9]{3}[\s\-\.]?[0-9]{4}$/
    return phoneRegex.test(String(value).replace(/\s/g, '')) || 'Please enter a valid phone number'
  },

  // Credit card validator (basic Luhn algorithm)
  creditCard: (value: any): boolean | string => {
    if (!value) return true
    
    const digits = String(value).replace(/\D/g, '')
    
    if (digits.length < 13 || digits.length > 19) {
      return 'Credit card number must be between 13 and 19 digits'
    }
    
    // Luhn algorithm
    let sum = 0
    let isEven = false
    
    for (let i = digits.length - 1; i >= 0; i--) {
      let digit = parseInt(digits[i])
      
      if (isEven) {
        digit *= 2
        if (digit > 9) {
          digit -= 9
        }
      }
      
      sum += digit
      isEven = !isEven
    }
    
    return (sum % 10 === 0) || 'Please enter a valid credit card number'
  },

  // Date validators
  date: (value: any): boolean | string => {
    if (!value) return true
    
    const date = new Date(value)
    return !isNaN(date.getTime()) || 'Please enter a valid date'
  },

  minDate: (minDate: Date | string) => (value: any): boolean | string => {
    if (!value) return true
    
    const date = new Date(value)
    const min = new Date(minDate)
    
    if (isNaN(date.getTime())) return 'Please enter a valid date'
    
    return (date >= min) || `Date must be on or after ${min.toLocaleDateString()}`
  },

  maxDate: (maxDate: Date | string) => (value: any): boolean | string => {
    if (!value) return true
    
    const date = new Date(value)
    const max = new Date(maxDate)
    
    if (isNaN(date.getTime())) return 'Please enter a valid date'
    
    return (date <= max) || `Date must be on or before ${max.toLocaleDateString()}`
  },

  // Password validators
  password: (value: any): boolean | string => {
    if (!value) return true
    
    const str = String(value)
    
    if (str.length < 8) {
      return 'Password must be at least 8 characters long'
    }
    
    if (!/(?=.*[a-z])/.test(str)) {
      return 'Password must contain at least one lowercase letter'
    }
    
    if (!/(?=.*[A-Z])/.test(str)) {
      return 'Password must contain at least one uppercase letter'
    }
    
    if (!/(?=.*\d)/.test(str)) {
      return 'Password must contain at least one number'
    }
    
    if (!/(?=.*[@$!%*?&])/.test(str)) {
      return 'Password must contain at least one special character'
    }
    
    return true
  },

  // Confirm password validator
  confirmPassword: (originalPassword: any) => (value: any): boolean | string => {
    if (!value) return true
    return (value === originalPassword) || 'Passwords do not match'
  },

  // Alpha validators
  alpha: (value: any): boolean | string => {
    if (!value) return true
    return /^[a-zA-Z]+$/.test(String(value)) || 'Only letters are allowed'
  },

  alphaNumeric: (value: any): boolean | string => {
    if (!value) return true
    return /^[a-zA-Z0-9]+$/.test(String(value)) || 'Only letters and numbers are allowed'
  },

  alphaSpace: (value: any): boolean | string => {
    if (!value) return true
    return /^[a-zA-Z\s]+$/.test(String(value)) || 'Only letters and spaces are allowed'
  },

  // Numeric validators
  numeric: (value: any): boolean | string => {
    if (!value) return true
    return /^[0-9]+$/.test(String(value)) || 'Only numbers are allowed'
  },

  // File validators
  fileSize: (maxSizeInMB: number) => (file: File): boolean | string => {
    if (!file) return true
    
    const maxSizeInBytes = maxSizeInMB * 1024 * 1024
    return (file.size <= maxSizeInBytes) || `File size must be less than ${maxSizeInMB}MB`
  },

  fileType: (allowedTypes: string[]) => (file: File): boolean | string => {
    if (!file) return true
    
    const fileType = file.type.toLowerCase()
    const fileName = file.name.toLowerCase()
    
    const isAllowed = allowedTypes.some(type => {
      if (type.startsWith('.')) {
        return fileName.endsWith(type)
      }
      return fileType.includes(type)
    })
    
    return isAllowed || `Only ${allowedTypes.join(', ')} files are allowed`
  },

  // Custom validator combiner
  combine: (...validators: Array<(value: any) => boolean | string>) => (value: any): boolean | string => {
    for (const validator of validators) {
      const result = validator(value)
      if (result !== true) {
        return result
      }
    }
    return true
  },

  // Conditional validator
  when: (condition: (value: any) => boolean, validator: (value: any) => boolean | string) => 
    (value: any): boolean | string => {
      if (condition(value)) {
        return validator(value)
      }
      return true
    },

  // Custom validator helper
  custom: (validator: (value: any) => boolean | string) => validator
}

export default validators

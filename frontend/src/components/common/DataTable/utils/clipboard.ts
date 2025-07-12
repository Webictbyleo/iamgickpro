// Clipboard utility functions for DataTable
export interface ClipboardOptions {
  maxCellLength?: number
  maxRows?: number
  maxColumns?: number
  sanitizeHTML?: boolean
}

const DEFAULT_OPTIONS: ClipboardOptions = {
  maxCellLength: 32767, // Excel cell limit
  maxRows: 1048576, // Excel row limit
  maxColumns: 16384, // Excel column limit
  sanitizeHTML: true
}

const clipboardUtils = {
  // Check if clipboard API is supported
  isSupported: (): boolean => {
    return typeof navigator !== 'undefined' && 
           'clipboard' in navigator && 
           typeof navigator.clipboard.writeText === 'function'
  },

  // Check if clipboard read is supported and allowed
  isReadSupported: async (): Promise<boolean> => {
    if (!clipboardUtils.isSupported()) return false
    
    try {
      // Test if we have read permission
      const permission = await navigator.permissions.query({ name: 'clipboard-read' as PermissionName })
      return permission.state === 'granted' || permission.state === 'prompt'
    } catch (error) {
      // Fallback: try to read once to test support
      try {
        await navigator.clipboard.readText()
        return true
      } catch {
        return false
      }
    }
  },

  // Copy data to clipboard with size limits and sanitization
  copy: async (data: string[][] | string, options: ClipboardOptions = {}): Promise<{ success: boolean; error?: string }> => {
    try {
      const opts = { ...DEFAULT_OPTIONS, ...options }
      let text: string
      
      if (typeof data === 'string') {
        text = data
      } else {
        // Validate data size
        const sizeValidation = clipboardUtils.validateDataSize(data, opts)
        if (!sizeValidation.valid) {
          return { success: false, error: sizeValidation.error }
        }
        
        // Convert 2D array to tab-separated values with sanitization
        text = data.map(row => 
          row.map(cell => {
            let cellValue = String(cell || '')
            
            // Truncate if too long
            if (cellValue.length > opts.maxCellLength!) {
              cellValue = cellValue.substring(0, opts.maxCellLength! - 3) + '...'
            }
            
            // Sanitize if needed
            if (opts.sanitizeHTML) {
              cellValue = clipboardUtils.sanitizeText(cellValue)
            }
            
            return cellValue
          }).join('\t')
        ).join('\n')
      }
      
      if (clipboardUtils.isSupported()) {
        await navigator.clipboard.writeText(text)
      } else {
        // Fallback for older browsers
        const fallbackResult = clipboardUtils.fallbackCopy(text)
        if (!fallbackResult) {
          return { success: false, error: 'Clipboard access not supported' }
        }
      }
      
      return { success: true }
    } catch (error) {
      console.error('Failed to copy to clipboard:', error)
      return { 
        success: false, 
        error: error instanceof Error ? error.message : 'Unknown error occurred' 
      }
    }
  },

  // Paste data from clipboard with validation
  paste: async (options: ClipboardOptions = {}): Promise<{ 
    success: boolean
    data?: string[][]
    error?: string 
  }> => {
    try {
      const opts = { ...DEFAULT_OPTIONS, ...options }
      
      // Check if read is supported
      const readSupported = await clipboardUtils.isReadSupported()
      if (!readSupported) {
        return { success: false, error: 'Clipboard read not supported or permission denied' }
      }
      
      let text: string
      
      if (clipboardUtils.isSupported()) {
        text = await navigator.clipboard.readText()
      } else {
        return { success: false, error: 'Clipboard read not supported' }
      }
      
      const parsedData = clipboardUtils.parseClipboardData(text, opts)
      
      if (!parsedData) {
        return { success: false, error: 'Failed to parse clipboard data' }
      }
      
      return { success: true, data: parsedData }
    } catch (error) {
      console.error('Failed to paste from clipboard:', error)
      return { 
        success: false, 
        error: error instanceof Error ? error.message : 'Failed to read clipboard'
      }
    }
  },

  // Validate data size constraints
  validateDataSize: (data: string[][], options: ClipboardOptions): { valid: boolean; error?: string } => {
    if (data.length > (options.maxRows || DEFAULT_OPTIONS.maxRows!)) {
      return { 
        valid: false, 
        error: `Too many rows. Maximum ${options.maxRows || DEFAULT_OPTIONS.maxRows} rows allowed.` 
      }
    }
    
    for (const row of data) {
      if (row.length > (options.maxColumns || DEFAULT_OPTIONS.maxColumns!)) {
        return { 
          valid: false, 
          error: `Too many columns. Maximum ${options.maxColumns || DEFAULT_OPTIONS.maxColumns} columns allowed.` 
        }
      }
    }
    
    return { valid: true }
  },

  // Sanitize text to prevent XSS
  sanitizeText: (text: string): string => {
    // Remove potentially dangerous characters and patterns
    return text
      .replace(/[<>]/g, '') // Remove angle brackets
      .replace(/javascript:/gi, '') // Remove javascript: protocol
      .replace(/data:/gi, '') // Remove data: protocol
      .replace(/vbscript:/gi, '') // Remove vbscript: protocol
      .trim()
  },

  // Parse clipboard text data into 2D array with validation
  parseClipboardData: (text: string, options: ClipboardOptions = {}): string[][] | null => {
    if (!text) return []
    
    try {
      const opts = { ...DEFAULT_OPTIONS, ...options }
      
      // Split by lines and then by tabs
      const lines = text.split(/\r?\n/).filter(line => line.trim().length > 0)
      
      // Validate row count
      if (lines.length > opts.maxRows!) {
        console.warn(`Data truncated: ${lines.length} rows exceeds limit of ${opts.maxRows}`)
        lines.splice(opts.maxRows!)
      }
      
      const result = lines.map(line => {
        const cells = line.split('\t')
        
        // Validate column count
        if (cells.length > opts.maxColumns!) {
          console.warn(`Row truncated: ${cells.length} columns exceeds limit of ${opts.maxColumns}`)
          cells.splice(opts.maxColumns!)
        }
        
        // Sanitize cells if needed
        return cells.map(cell => {
          let sanitized = cell
          
          if (opts.sanitizeHTML) {
            sanitized = clipboardUtils.sanitizeText(cell)
          }
          
          // Truncate if too long
          if (sanitized.length > opts.maxCellLength!) {
            sanitized = sanitized.substring(0, opts.maxCellLength! - 3) + '...'
          }
          
          return sanitized
        })
      })
      
      return result
    } catch (error) {
      console.error('Error parsing clipboard data:', error)
      return null
    }
  },

  // Convert 2D array to clipboard text format
  formatForClipboard: (data: string[][]): string => {
    return data.map(row => row.join('\t')).join('\n')
  },

  // Copy with HTML format (for rich text editors)
  copyHTML: async (data: string[][], headers?: string[]): Promise<boolean> => {
    try {
      const text = clipboardUtils.formatForClipboard(data)
      const html = clipboardUtils.convertToHTML(data, headers)
      
      if (clipboardUtils.isSupported() && 'write' in navigator.clipboard) {
        const clipboardItem = new ClipboardItem({
          'text/plain': new Blob([text], { type: 'text/plain' }),
          'text/html': new Blob([html], { type: 'text/html' })
        })
        
        await navigator.clipboard.write([clipboardItem])
      } else {
        // Fallback to plain text
        await clipboardUtils.copy(text)
      }
      
      return true
    } catch (error) {
      console.error('Failed to copy HTML to clipboard:', error)
      return false
    }
  },

  // Convert 2D array to HTML table
  convertToHTML: (data: string[][], headers?: string[]): string => {
    let html = '<table>'
    
    // Add headers if provided
    if (headers && headers.length > 0) {
      html += '<thead><tr>'
      headers.forEach(header => {
        html += `<th>${clipboardUtils.escapeHTML(header)}</th>`
      })
      html += '</tr></thead>'
    }
    
    // Add data rows
    html += '<tbody>'
    data.forEach(row => {
      html += '<tr>'
      row.forEach(cell => {
        html += `<td>${clipboardUtils.escapeHTML(cell)}</td>`
      })
      html += '</tr>'
    })
    html += '</tbody></table>'
    
    return html
  },

  // Escape HTML entities
  escapeHTML: (text: string): string => {
    const div = document.createElement('div')
    div.textContent = text
    return div.innerHTML
  },

  // Improved fallback copy method with better error handling
  fallbackCopy: (text: string): boolean => {
    try {
      // Check if document.execCommand is supported
      if (!document.queryCommandSupported || !document.queryCommandSupported('copy')) {
        return false
      }
      
      const textArea = document.createElement('textarea')
      textArea.value = text
      textArea.style.position = 'fixed'
      textArea.style.left = '-999999px'
      textArea.style.top = '-999999px'
      textArea.style.opacity = '0'
      textArea.style.pointerEvents = 'none'
      textArea.setAttribute('readonly', '')
      
      document.body.appendChild(textArea)
      textArea.select()
      textArea.setSelectionRange(0, 99999) // For mobile devices
      
      const successful = document.execCommand('copy')
      document.body.removeChild(textArea)
      
      return successful
    } catch (err) {
      console.error('Fallback copy failed:', err)
      return false
    }
  },

  // Copy selection with formatting preservation
  copyFormatted: async (
    data: Array<{
      value: any
      formatted: string
      type: string
    }>[]
  ): Promise<boolean> => {
    try {
      // Create plain text version
      const plainText = data.map(row => 
        row.map(cell => cell.formatted || String(cell.value || '')).join('\t')
      ).join('\n')
      
      // Create rich text version
      const richData = data.map(row => 
        row.map(cell => cell.formatted || String(cell.value || ''))
      )
      
      await clipboardUtils.copyHTML(richData)
      return true
    } catch (error) {
      console.error('Failed to copy formatted data:', error)
      return false
    }
  },

  // Paste and validate data types
  pasteTyped: async (
    columnTypes: string[], 
    options: ClipboardOptions = {}
  ): Promise<{ 
    success: boolean
    data?: Array<{ value: any; type: string; valid: boolean }>[]
    error?: string 
  }> => {
    try {
      const pasteResult = await clipboardUtils.paste(options)
      if (!pasteResult.success || !pasteResult.data) {
        return { success: false, error: pasteResult.error }
      }
      
      const typedData = pasteResult.data.map(row => 
        row.map((cell, index) => {
          const type = columnTypes[index] || 'text'
          const { value, valid } = clipboardUtils.parseTypedValue(cell, type)
          
          return { value, type, valid }
        })
      )
      
      return { success: true, data: typedData }
    } catch (error) {
      console.error('Failed to paste typed data:', error)
      return { 
        success: false, 
        error: error instanceof Error ? error.message : 'Failed to parse typed data'
      }
    }
  },

  // Parse value according to type
  parseTypedValue: (text: string, type: string): { value: any; valid: boolean } => {
    const trimmed = text.trim()
    
    switch (type) {
      case 'number':
      case 'currency':
        const num = Number(trimmed)
        return { value: isNaN(num) ? trimmed : num, valid: !isNaN(num) }
      
      case 'boolean':
        const lowerText = trimmed.toLowerCase()
        if (['true', '1', 'yes', 'on'].includes(lowerText)) {
          return { value: true, valid: true }
        } else if (['false', '0', 'no', 'off'].includes(lowerText)) {
          return { value: false, valid: true }
        }
        return { value: trimmed, valid: false }
      
      case 'date':
        const date = new Date(trimmed)
        return { value: isNaN(date.getTime()) ? trimmed : date, valid: !isNaN(date.getTime()) }
      
      case 'email':
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
        return { value: trimmed, valid: emailRegex.test(trimmed) }
      
      case 'url':
        try {
          new URL(trimmed)
          return { value: trimmed, valid: true }
        } catch {
          return { value: trimmed, valid: false }
        }
      
      default:
        return { value: trimmed, valid: true }
    }
  },

  // Export data in different formats
  export: {
    // Export as CSV
    csv: (data: string[][], filename = 'export.csv'): void => {
      const csvContent = data.map(row => 
        row.map(cell => `"${String(cell || '').replace(/"/g, '""')}"`).join(',')
      ).join('\n')
      
      clipboardUtils.downloadFile(csvContent, filename, 'text/csv')
    },

    // Export as TSV (Tab-separated values)
    tsv: (data: string[][], filename = 'export.tsv'): void => {
      const tsvContent = data.map(row => row.join('\t')).join('\n')
      clipboardUtils.downloadFile(tsvContent, filename, 'text/tab-separated-values')
    },

    // Export as JSON
    json: (data: any[], filename = 'export.json'): void => {
      const jsonContent = JSON.stringify(data, null, 2)
      clipboardUtils.downloadFile(jsonContent, filename, 'application/json')
    },

    // Export as HTML table
    html: (data: string[][], headers?: string[], filename = 'export.html'): void => {
      const table = clipboardUtils.convertToHTML(data, headers)
      const html = `<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>Data Export</title>
  <style>
    table { border-collapse: collapse; width: 100%; }
    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
    th { background-color: #f2f2f2; }
  </style>
</head>
<body>
  ${table}
</body>
</html>`
      
      clipboardUtils.downloadFile(html, filename, 'text/html')
    }
  },

  // Download file helper
  downloadFile: (content: string, filename: string, mimeType: string): void => {
    const blob = new Blob([content], { type: mimeType })
    const url = URL.createObjectURL(blob)
    
    const link = document.createElement('a')
    link.href = url
    link.download = filename
    link.style.display = 'none'
    
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
    
    URL.revokeObjectURL(url)
  },

  // Import data from file
  import: {
    // Import from file input
    fromFile: (file: File, options: ClipboardOptions = {}): Promise<string[][] | any[]> => {
      return new Promise((resolve, reject) => {
        // Validate file size (limit to 10MB for safety)
        const maxFileSize = 10 * 1024 * 1024 // 10MB
        if (file.size > maxFileSize) {
          reject(new Error(`File too large. Maximum size is ${maxFileSize / 1024 / 1024}MB`))
          return
        }
        
        const reader = new FileReader()
        
        reader.onload = (e) => {
          try {
            const content = e.target?.result as string
            
            if (file.name.endsWith('.json')) {
              const data = JSON.parse(content)
              resolve(Array.isArray(data) ? data : [data])
            } else if (file.name.endsWith('.csv')) {
              const result = clipboardUtils.import.parseCSV(content)
              resolve(result || [])
            } else if (file.name.endsWith('.tsv')) {
              const result = clipboardUtils.import.parseTSV(content)
              resolve(result || [])
            } else {
              // Try to parse as plain text
              const result = clipboardUtils.parseClipboardData(content, options)
              resolve(result || [])
            }
          } catch (error) {
            reject(error instanceof Error ? error : new Error('Failed to parse file'))
          }
        }
        
        reader.onerror = () => reject(new Error('Failed to read file'))
        reader.readAsText(file)
      })
    },

    // Parse CSV content with improved error handling
    parseCSV: (content: string): string[][] | null => {
      try {
        if (!content.trim()) return []
        
        const lines = content.split(/\r?\n/)
        const result: string[][] = []
        
        for (const line of lines) {
          if (!line.trim()) continue // Skip empty lines
          
          const row: string[] = []
          let current = ''
          let inQuotes = false
          let i = 0
          
          while (i < line.length) {
            const char = line[i]
            
            if (char === '"') {
              if (inQuotes && line[i + 1] === '"') {
                // Escaped quote
                current += '"'
                i += 2
              } else {
                // Toggle quote state
                inQuotes = !inQuotes
                i++
              }
            } else if (char === ',' && !inQuotes) {
              // End of field
              row.push(current)
              current = ''
              i++
            } else {
              current += char
              i++
            }
          }
          
          // Add the last field
          row.push(current)
          result.push(row)
        }
        
        return result
      } catch (error) {
        console.error('Error parsing CSV:', error)
        return null
      }
    },

    // Parse TSV content with error handling
    parseTSV: (content: string): string[][] | null => {
      try {
        if (!content.trim()) return []
        
        return content.split(/\r?\n/)
          .filter(line => line.trim())
          .map(line => line.split('\t'))
      } catch (error) {
        console.error('Error parsing TSV:', error)
        return null
      }
    }
  }
}

export default clipboardUtils

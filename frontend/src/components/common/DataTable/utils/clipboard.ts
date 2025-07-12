// Clipboard utility functions for DataTable
const clipboardUtils = {
  // Check if clipboard API is supported
  isSupported: (): boolean => {
    return typeof navigator !== 'undefined' && 
           'clipboard' in navigator && 
           typeof navigator.clipboard.writeText === 'function'
  },

  // Copy data to clipboard
  copy: async (data: string[][] | string): Promise<boolean> => {
    try {
      let text: string
      
      if (typeof data === 'string') {
        text = data
      } else {
        // Convert 2D array to tab-separated values
        text = data.map(row => row.join('\t')).join('\n')
      }
      
      if (clipboardUtils.isSupported()) {
        await navigator.clipboard!.writeText(text)
      } else {
        // Fallback for older browsers
        clipboardUtils.fallbackCopy(text)
      }
      
      return true
    } catch (error) {
      console.error('Failed to copy to clipboard:', error)
      return false
    }
  },

  // Paste data from clipboard
  paste: async (): Promise<string[][] | null> => {
    try {
      let text: string
      
      if (clipboardUtils.isSupported()) {
        text = await navigator.clipboard.readText()
      } else {
        // Fallback not available for reading
        return null
      }
      
      return clipboardUtils.parseClipboardData(text)
    } catch (error) {
      console.error('Failed to paste from clipboard:', error)
      return null
    }
  },

  // Parse clipboard text data into 2D array
  parseClipboardData: (text: string): string[][] => {
    if (!text) return []
    
    // Split by lines and then by tabs
    const lines = text.split(/\r?\n/).filter(line => line.trim().length > 0)
    return lines.map(line => line.split('\t'))
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

  // Fallback copy method for older browsers
  fallbackCopy: (text: string): void => {
    const textArea = document.createElement('textarea')
    textArea.value = text
    textArea.style.position = 'fixed'
    textArea.style.left = '-999999px'
    textArea.style.top = '-999999px'
    
    document.body.appendChild(textArea)
    textArea.select()
    
    try {
      document.execCommand('copy')
    } catch (err) {
      console.error('Fallback copy failed:', err)
    }
    
    document.body.removeChild(textArea)
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
  pasteTyped: async (columnTypes: string[]): Promise<Array<{ value: any; type: string; valid: boolean }>[] | null> => {
    try {
      const rawData = await clipboardUtils.paste()
      if (!rawData) return null
      
      return rawData.map((row: any) => 
        row.map((cell: any, index: number) => {
          const type = columnTypes[index] || 'text'
          const { value, valid } = clipboardUtils.parseTypedValue(cell, type)
          
          return { value, type, valid }
        })
      )
    } catch (error) {
      console.error('Failed to paste typed data:', error)
      return null
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
    fromFile: (file: File): Promise<string[][] | any[]> => {
      return new Promise((resolve, reject) => {
        const reader = new FileReader()
        
        reader.onload = (e) => {
          try {
            const content = e.target?.result as string
            
            if (file.name.endsWith('.json')) {
              const data = JSON.parse(content)
              resolve(Array.isArray(data) ? data : [data])
            } else if (file.name.endsWith('.csv')) {
              resolve(clipboardUtils.import.parseCSV(content))
            } else if (file.name.endsWith('.tsv')) {
              resolve(clipboardUtils.import.parseTSV(content))
            } else {
              // Try to parse as plain text
              resolve(clipboardUtils.parseClipboardData(content))
            }
          } catch (error) {
            reject(error)
          }
        }
        
        reader.onerror = () => reject(new Error('Failed to read file'))
        reader.readAsText(file)
      })
    },

    // Parse CSV content
    parseCSV: (content: string): string[][] => {
      const lines = content.split(/\r?\n/)
      return lines.map(line => {
        const result: string[] = []
        let current = ''
        let inQuotes = false
        
        for (let i = 0; i < line.length; i++) {
          const char = line[i]
          
          if (char === '"') {
            if (inQuotes && line[i + 1] === '"') {
              current += '"'
              i++
            } else {
              inQuotes = !inQuotes
            }
          } else if (char === ',' && !inQuotes) {
            result.push(current)
            current = ''
          } else {
            current += char
          }
        }
        
        result.push(current)
        return result
      })
    },

    // Parse TSV content
    parseTSV: (content: string): string[][] => {
      return content.split(/\r?\n/).map(line => line.split('\t'))
    }
  }
}

export default clipboardUtils

// Keyboard utility functions for DataTable with improved performance and accessibility

// Cache for platform detection
let platformCache: { isMac: boolean; isWindows: boolean; isLinux: boolean } | null = null

const getPlatform = () => {
  if (!platformCache) {
    const platform = navigator.platform.toLowerCase()
    const userAgent = navigator.userAgent.toLowerCase()
    
    platformCache = {
      isMac: /mac|ipod|iphone|ipad/.test(platform) || /mac os x/.test(userAgent),
      isWindows: /win/.test(platform),
      isLinux: /linux/.test(platform)
    }
  }
  return platformCache
}

// Debounce function for performance
const debounce = <T extends (...args: any[]) => any>(
  func: T,
  wait: number
): ((...args: Parameters<T>) => void) => {
  let timeout: NodeJS.Timeout
  return (...args: Parameters<T>) => {
    clearTimeout(timeout)
    timeout = setTimeout(() => func(...args), wait)
  }
}

const keyboardUtils = {
  // Check if a key combination matches with better performance
  matches: (event: KeyboardEvent, key: string, modifiers: {
    ctrl?: boolean
    shift?: boolean
    alt?: boolean
    meta?: boolean
  } = {}): boolean => {
    // Quick exit if key doesn't match
    if (event.key !== key) return false
    
    return event.ctrlKey === (modifiers.ctrl || false) &&
           event.shiftKey === (modifiers.shift || false) &&
           event.altKey === (modifiers.alt || false) &&
           event.metaKey === (modifiers.meta || false)
  },

  // Enhanced printable character check
  isPrintable: (event: KeyboardEvent): boolean => {
    const { key, ctrlKey, metaKey, altKey } = event
    
    // Quick exit for modifier combinations
    if (ctrlKey || metaKey) return false
    
    // Allow Alt combinations on Windows/Linux for international characters
    const platform = getPlatform()
    if (altKey && !platform.isMac) {
      // Allow Alt+key combinations for international input
      return key.length === 1 && /[\w\s\p{L}\p{N}\p{P}\p{S}]/u.test(key)
    } else if (altKey) {
      return false
    }
    
    // Comprehensive special keys list
    const specialKeys = new Set([
      'Tab', 'Enter', 'Escape', 'Backspace', 'Delete',
      'ArrowUp', 'ArrowDown', 'ArrowLeft', 'ArrowRight',
      'Home', 'End', 'PageUp', 'PageDown',
      'F1', 'F2', 'F3', 'F4', 'F5', 'F6',
      'F7', 'F8', 'F9', 'F10', 'F11', 'F12',
      'Insert', 'CapsLock', 'NumLock', 'ScrollLock',
      'Pause', 'ContextMenu', 'PrintScreen'
    ])
    
    if (specialKeys.has(key)) return false
    
    // Check for modifier keys
    const modifierKeys = new Set([
      'Control', 'Alt', 'Shift', 'Meta', 'AltGraph',
      'ControlLeft', 'ControlRight',
      'AltLeft', 'AltRight',
      'ShiftLeft', 'ShiftRight',
      'MetaLeft', 'MetaRight'
    ])
    
    if (modifierKeys.has(key)) return false
    
    // Enhanced printable character detection
    return key.length === 1 && /[\w\s\p{L}\p{N}\p{P}\p{S}]/u.test(key)
  },

  // Navigation key check
  isNavigationKey: (event: KeyboardEvent): boolean => {
    const navigationKeys = [
      'Tab', 'Enter',
      'ArrowUp', 'ArrowDown', 'ArrowLeft', 'ArrowRight',
      'Home', 'End', 'PageUp', 'PageDown'
    ]
    
    return navigationKeys.includes(event.key)
  },

  // Edit key check
  isEditKey: (event: KeyboardEvent): boolean => {
    const editKeys = ['F2', 'Enter']
    return editKeys.includes(event.key)
  },

  // Delete key check
  isDeleteKey: (event: KeyboardEvent): boolean => {
    return ['Delete', 'Backspace'].includes(event.key)
  },

  // Copy/paste/cut key check
  isClipboardKey: (event: KeyboardEvent): boolean => {
    const { key, ctrlKey, metaKey } = event
    const cmdKey = ctrlKey || metaKey
    
    return cmdKey && ['c', 'v', 'x'].includes(key.toLowerCase())
  },

  // Undo/redo key check
  isUndoRedoKey: (event: KeyboardEvent): boolean => {
    const { key, ctrlKey, metaKey, shiftKey } = event
    const cmdKey = ctrlKey || metaKey
    
    return cmdKey && (key.toLowerCase() === 'z' || (shiftKey && key.toLowerCase() === 'z') || key.toLowerCase() === 'y')
  },

  // Select all key check
  isSelectAllKey: (event: KeyboardEvent): boolean => {
    const { key, ctrlKey, metaKey } = event
    const cmdKey = ctrlKey || metaKey
    
    return cmdKey && key.toLowerCase() === 'a'
  },

  // Escape key check
  isEscapeKey: (event: KeyboardEvent): boolean => {
    return event.key === 'Escape'
  },

  // Format keyboard shortcut for display
  formatShortcut: (key: string, modifiers: {
    ctrl?: boolean
    shift?: boolean
    alt?: boolean
    meta?: boolean
  } = {}): string => {
    const parts: string[] = []
    
    // Detect platform
    const isMac = /Mac|iPod|iPhone|iPad/.test(navigator.platform)
    
    if (modifiers.ctrl || modifiers.meta) {
      parts.push(isMac ? '⌘' : 'Ctrl')
    }
    
    if (modifiers.shift) {
      parts.push(isMac ? '⇧' : 'Shift')
    }
    
    if (modifiers.alt) {
      parts.push(isMac ? '⌥' : 'Alt')
    }
    
    // Format key name
    const keyNames: Record<string, string> = {
      'ArrowUp': '↑',
      'ArrowDown': '↓',
      'ArrowLeft': '←',
      'ArrowRight': '→',
      'Enter': '↵',
      'Tab': '⇥',
      'Escape': 'Esc',
      'Backspace': '⌫',
      'Delete': 'Del',
      'Home': 'Home',
      'End': 'End',
      'PageUp': 'PgUp',
      'PageDown': 'PgDn'
    }
    
    const displayKey = keyNames[key] || key.toUpperCase()
    parts.push(displayKey)
    
    return parts.join(isMac ? '' : '+')
  },

  // Get keyboard shortcut from event
  getShortcutFromEvent: (event: KeyboardEvent): string => {
    return keyboardUtils.formatShortcut(event.key, {
      ctrl: event.ctrlKey,
      shift: event.shiftKey,
      alt: event.altKey,
      meta: event.metaKey
    })
  },

  // Prevent default and stop propagation
  preventDefault: (event: KeyboardEvent) => {
    event.preventDefault()
    event.stopPropagation()
  },

  // Keyboard shortcut definitions
  shortcuts: {
    EDIT_CELL: { key: 'F2' },
    EDIT_OR_NAVIGATE_DOWN: { key: 'Enter' },
    NAVIGATE_DOWN: { key: 'ArrowDown' },
    NAVIGATE_UP: { key: 'ArrowUp' },
    NAVIGATE_LEFT: { key: 'ArrowLeft' },
    NAVIGATE_RIGHT: { key: 'ArrowRight' },
    NAVIGATE_NEXT: { key: 'Tab' },
    NAVIGATE_PREVIOUS: { key: 'Tab', shift: true },
    NAVIGATE_ROW_START: { key: 'Home' },
    NAVIGATE_ROW_END: { key: 'End' },
    NAVIGATE_TABLE_START: { key: 'Home', ctrl: true },
    NAVIGATE_TABLE_END: { key: 'End', ctrl: true },
    NAVIGATE_PAGE_UP: { key: 'PageUp' },
    NAVIGATE_PAGE_DOWN: { key: 'PageDown' },
    CANCEL_EDIT: { key: 'Escape' },
    DELETE_CONTENT: { key: 'Delete' },
    BACKSPACE_CONTENT: { key: 'Backspace' },
    COPY: { key: 'c', ctrl: true },
    PASTE: { key: 'v', ctrl: true },
    CUT: { key: 'x', ctrl: true },
    SELECT_ALL: { key: 'a', ctrl: true },
    UNDO: { key: 'z', ctrl: true },
    REDO: { key: 'y', ctrl: true }
  },

  // Create keyboard event listener
  createListener: (shortcuts: Record<string, () => void>) => {
    return (event: KeyboardEvent) => {
      for (const [action, handler] of Object.entries(shortcuts)) {
        // Find matching shortcut definition
        const shortcut = Object.values(keyboardUtils.shortcuts).find((s: any) => 
          s.key === event.key &&
          s.ctrl === event.ctrlKey &&
          s.shift === event.shiftKey &&
          s.alt === event.altKey &&
          s.meta === event.metaKey
        )
        
        if (shortcut) {
          const actionKey = Object.keys(keyboardUtils.shortcuts).find(key => 
            keyboardUtils.shortcuts[key as keyof typeof keyboardUtils.shortcuts] === shortcut
          )
          
          if (actionKey && shortcuts[actionKey]) {
            event.preventDefault()
            event.stopPropagation()
            handler()
            break
          }
        }
      }
    }
  },

  // Focus management
  focus: {
    // Focus an element safely
    element: (element: HTMLElement | null) => {
      if (element && typeof element.focus === 'function') {
        element.focus()
      }
    },

    // Focus and select text in input
    inputAndSelect: (input: HTMLInputElement | null) => {
      if (input && typeof input.focus === 'function') {
        input.focus()
        if (typeof input.select === 'function') {
          input.select()
        }
      }
    },

    // Set cursor position in input
    setCursor: (input: HTMLInputElement | null, position: number) => {
      if (input && typeof input.setSelectionRange === 'function') {
        input.setSelectionRange(position, position)
      }
    },

    // Set cursor to end of input
    setCursorToEnd: (input: HTMLInputElement | null) => {
      if (input) {
        const length = input.value.length
        if (input && typeof input.setSelectionRange === 'function') {
          input.setSelectionRange(length, length)
        }
      }
    }
  },

  // Accessibility helpers
  accessibility: {
    // Announce to screen readers
    announce: (message: string) => {
      const announcement = document.createElement('div')
      announcement.setAttribute('aria-live', 'polite')
      announcement.setAttribute('aria-atomic', 'true')
      announcement.style.position = 'absolute'
      announcement.style.left = '-10000px'
      announcement.style.width = '1px'
      announcement.style.height = '1px'
      announcement.style.overflow = 'hidden'
      
      document.body.appendChild(announcement)
      announcement.textContent = message
      
      setTimeout(() => {
        document.body.removeChild(announcement)
      }, 1000)
    },

    // Set ARIA attributes for table navigation
    setTableNavigation: (cell: HTMLElement, row: number, col: number, rowCount: number, colCount: number) => {
      cell.setAttribute('aria-rowindex', String(row + 1))
      cell.setAttribute('aria-colindex', String(col + 1))
      cell.setAttribute('aria-setsize', String(rowCount * colCount))
      cell.setAttribute('aria-posinset', String(row * colCount + col + 1))
    }
  }
}

export default keyboardUtils

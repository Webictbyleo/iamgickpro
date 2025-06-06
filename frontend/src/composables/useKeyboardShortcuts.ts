import { onMounted, onUnmounted } from 'vue'

interface KeyboardShortcuts {
  onSave?: () => void
  onUndo?: () => void
  onRedo?: () => void
  onDuplicate?: () => void
  onDelete?: () => void
  onSelectAll?: () => void
  onCopy?: () => void
  onPaste?: () => void
  onZoomIn?: () => void
  onZoomOut?: () => void
  onZoomToFit?: () => void
  onResetZoom?: () => void
}

export function useKeyboardShortcuts(shortcuts: KeyboardShortcuts) {
  const handleKeydown = (event: KeyboardEvent) => {
    const { ctrlKey, metaKey, key, shiftKey } = event
    const isModifier = ctrlKey || metaKey

    // Don't trigger shortcuts when typing in inputs
    if (event.target instanceof HTMLInputElement || 
        event.target instanceof HTMLTextAreaElement || 
        (event.target as HTMLElement)?.contentEditable === 'true') {
      return
    }

    if (isModifier) {
      switch (key.toLowerCase()) {
        case 's':
          event.preventDefault()
          shortcuts.onSave?.()
          break
        case 'z':
          event.preventDefault()
          if (shiftKey) {
            shortcuts.onRedo?.()
          } else {
            shortcuts.onUndo?.()
          }
          break
        case 'y':
          event.preventDefault()
          shortcuts.onRedo?.()
          break
        case 'd':
          event.preventDefault()
          shortcuts.onDuplicate?.()
          break
        case 'a':
          event.preventDefault()
          shortcuts.onSelectAll?.()
          break
        case 'c':
          event.preventDefault()
          shortcuts.onCopy?.()
          break
        case 'v':
          event.preventDefault()
          shortcuts.onPaste?.()
          break
        case '=':
        case '+':
          event.preventDefault()
          shortcuts.onZoomIn?.()
          break
        case '-':
          event.preventDefault()
          shortcuts.onZoomOut?.()
          break
        case '0':
          event.preventDefault()
          if (shiftKey) {
            shortcuts.onZoomToFit?.()
          } else {
            shortcuts.onResetZoom?.()
          }
          break
      }
    } else {
      switch (key) {
        case 'Delete':
        case 'Backspace':
          shortcuts.onDelete?.()
          break
        case 'v':
          // Select tool
          break
        case 'h':
          // Pan tool
          break
        case 't':
          // Text tool
          break
        case 'r':
          // Rectangle tool
          break
      }
    }
  }

  onMounted(() => {
    document.addEventListener('keydown', handleKeydown)
  })

  onUnmounted(() => {
    document.removeEventListener('keydown', handleKeydown)
  })

  return {
    handleKeydown
  }
}

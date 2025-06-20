import { ref, computed, watch, onUnmounted } from 'vue'
import { HistoryService, type HistoryConfig } from '@/services/HistoryService'
import type { Design } from '@/types'

/**
 * Design-specific history configuration
 */
export interface DesignHistoryConfig extends HistoryConfig {
  // Additional design-specific options
  trackLayerChanges?: boolean
  trackCanvasChanges?: boolean
  trackPropertyChanges?: boolean
}

/**
 * Vue composable for design history management
 * Integrates the generic HistoryService with Vue reactivity
 */
export function useDesignHistory(initialDesign?: Design, config: DesignHistoryConfig = {}) {
  // Default configuration for design editing
  const defaultConfig: DesignHistoryConfig = {
    maxEntries: 50,
    enableSnapshots: true,
    snapshotInterval: 5,
    debounceMs: 100,
    excludePaths: [
      '/updatedAt', // Don't track timestamp changes
      '/viewportSettings/zoom', // Don't track zoom changes
      '/viewportSettings/panX', // Don't track pan changes
      '/viewportSettings/panY'
    ],
    trackLayerChanges: true,
    trackCanvasChanges: true,
    trackPropertyChanges: true,
    ...config
  }

  // Create history service instance
  const historyService = new HistoryService<Design>(initialDesign, defaultConfig)

  // Reactive state
  const canUndo = ref(false)
  const canRedo = ref(false)
  const currentEntry = ref<any>(null)
  const totalEntries = ref(0)
  const currentData = ref<Design | null>(null)

  // Computed properties
  const historyStats = computed(() => historyService.getStats())
  
  const hasChanges = computed(() => totalEntries.value > 1)

  // Update reactive state when history changes
  const updateState = () => {
    const state = historyService.getHistoryState()
    canUndo.value = state.canUndo
    canRedo.value = state.canRedo
    currentEntry.value = state.currentEntry
    totalEntries.value = state.totalEntries
    currentData.value = historyService.getCurrentData()
  }

  // History event listeners
  historyService.on('history:change', updateState)
  historyService.on('history:add', updateState)
  historyService.on('history:undo', updateState)
  historyService.on('history:redo', updateState)
  historyService.on('history:clear', updateState)

  // Initialize state
  updateState()

  /**
   * Add a new history entry with a descriptive label
   */
  const addHistoryEntry = (design: Design, action: string, details?: string, force: boolean = false) => {
    const label = details ? `${action}: ${details}` : action
    historyService.addEntry(design, label, force)
  }

  /**
   * Add history entry for layer operations
   */
  const addLayerHistoryEntry = (design: Design, action: 'add' | 'delete' | 'modify' | 'reorder', layerInfo?: string) => {
    if (!defaultConfig.trackLayerChanges) return
    
    const actionLabels = {
      add: 'Add Layer',
      delete: 'Delete Layer', 
      modify: 'Modify Layer',
      reorder: 'Reorder Layers'
    }
    
    // Force immediate execution for layer changes to provide better UX
    addHistoryEntry(design, actionLabels[action], layerInfo, true)
  }

  /**
   * Add history entry for canvas operations
   */
  const addCanvasHistoryEntry = (design: Design, action: 'resize' | 'background' | 'settings', details?: string) => {
    if (!defaultConfig.trackCanvasChanges) return
    
    const actionLabels = {
      resize: 'Resize Canvas',
      background: 'Change Background',
      settings: 'Canvas Settings'
    }
    
    addHistoryEntry(design, actionLabels[action], details)
  }

  /**
   * Add history entry for property changes
   */
  const addPropertyHistoryEntry = (design: Design, property: string, details?: string) => {
    if (!defaultConfig.trackPropertyChanges) return
    
    addHistoryEntry(design, `Change ${property}`, details)
  }

  /**
   * Undo the last change
   */
  const undo = (): Design | null => {
    
    return historyService.undo()
  }

  /**
   * Redo the next change  
   */
  const redo = (): Design | null => {
    return historyService.redo()
  }

  /**
   * Jump to a specific history entry
   */
  const jumpToEntry = (index: number): Design | null => {
    return historyService.jumpTo(index)
  }

  /**
   * Get history entries for UI display
   */
  const getHistoryEntries = () => {
    return historyService.getHistory().map((entry, index) => ({
      ...entry,
      index,
      isCurrent: index === historyService.getHistoryState().currentIndex,
      timeAgo: formatTimeAgo(entry.timestamp)
    }))
  }

  /**
   * Clear all history
   */
  const clearHistory = () => {
    historyService.clear()
  }

  /**
   * Initialize with a design
   */
  const initialize = (design: Design, label: string = 'Initial Design') => {
    historyService.initialize(design, label)
  }

  /**
   * Get current design state
   */
  const getCurrentDesign = (): Design | null => {
    return historyService.getCurrentData()
  }

  /**
   * Create a snapshot of current state
   */
  const createSnapshot = (design: Design, label: string) => {
    historyService.addEntry(design, `Snapshot: ${label}`, true)
  }

  /**
   * Batch multiple operations
   */
  const batch = (operations: Array<() => void>, label: string) => {
    // Disable debouncing temporarily
    const originalDebounce = defaultConfig.debounceMs
    defaultConfig.debounceMs = 0

    operations.forEach(op => op())

    // Re-enable debouncing
    defaultConfig.debounceMs = originalDebounce

    // Add single entry for the batch
    const currentDesign = getCurrentDesign()
    if (currentDesign) {
      addHistoryEntry(currentDesign, `Batch: ${label}`)
    }
  }

  // Cleanup on unmount
  onUnmounted(() => {
    historyService.destroy()
  })

  return {
    // State
    canUndo: computed(() => canUndo.value),
    canRedo: computed(() => canRedo.value),
    currentEntry: computed(() => currentEntry.value),
    totalEntries: computed(() => totalEntries.value),
    currentData: computed(() => currentData.value),
    hasChanges,
    historyStats,

    // Methods
    addHistoryEntry,
    addLayerHistoryEntry,
    addCanvasHistoryEntry,
    addPropertyHistoryEntry,
    undo,
    redo,
    jumpToEntry,
    getHistoryEntries,
    clearHistory,
    initialize,
    getCurrentDesign,
    createSnapshot,
    batch,

    // Direct access to history service for advanced usage
    historyService
  }
}

/**
 * Format timestamp as "time ago" string
 */
function formatTimeAgo(timestamp: number): string {
  const now = Date.now()
  const diff = now - timestamp
  
  if (diff < 60000) {
    return 'Just now'
  } else if (diff < 3600000) {
    const minutes = Math.floor(diff / 60000)
    return `${minutes}m ago`
  } else if (diff < 86400000) {
    const hours = Math.floor(diff / 3600000)
    return `${hours}h ago`
  } else {
    const days = Math.floor(diff / 86400000)
    return `${days}d ago`
  }
}

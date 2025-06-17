import { compare, applyPatch, deepClone, type Operation } from 'fast-json-patch'

/**
 * Represents a single history entry
 */
export interface HistoryEntry<T = any> {
  id: string
  timestamp: number
  label: string
  patch: Operation[]
  data: T // Snapshot of the data at this point
  metadata?: Record<string, any>
}

/**
 * Configuration options for the HistoryService
 */
export interface HistoryConfig {
  maxEntries?: number // Maximum number of history entries to keep
  enableSnapshots?: boolean // Whether to store full data snapshots
  snapshotInterval?: number // Store full snapshot every N entries
  debounceMs?: number // Debounce rapid changes
  excludePaths?: string[] // Paths to exclude from diffing
}

/**
 * Events emitted by the HistoryService
 */
export interface HistoryEvents<T = any> {
  'history:change': { canUndo: boolean; canRedo: boolean; currentEntry?: HistoryEntry<T> }
  'history:undo': { previousEntry: HistoryEntry<T>; currentEntry: HistoryEntry<T> }
  'history:redo': { previousEntry: HistoryEntry<T>; currentEntry: HistoryEntry<T> }
  'history:add': { entry: HistoryEntry<T>; totalEntries: number }
  'history:clear': void
}

/**
 * Comprehensive History Service using JSON Patch for efficient diffing
 * Can be used for any object type, not just editor data
 */
export class HistoryService<T = any> {
  private history: HistoryEntry<T>[] = []
  private currentIndex: number = -1
  private currentData: T | null = null
  private config: Required<HistoryConfig>
  private eventListeners: Map<keyof HistoryEvents<T>, Set<Function>> = new Map()
  private debounceTimer: ReturnType<typeof setTimeout> | null = null
  private lastSnapshotIndex: number = -1

  constructor(initialData?: T, config: HistoryConfig = {}) {
    this.config = {
      maxEntries: config.maxEntries ?? 100,
      enableSnapshots: config.enableSnapshots ?? true,
      snapshotInterval: config.snapshotInterval ?? 10,
      debounceMs: config.debounceMs ?? 300,
      excludePaths: config.excludePaths ?? []
    }

    if (initialData) {
      this.initialize(initialData)
    }
  }

  /**
   * Initialize the history with initial data
   */
  public initialize(data: T, label: string = 'Initial State'): void {
    this.currentData = deepClone(data)
    this.history = []
    this.currentIndex = -1
    
    this.addEntry(data, label, true)
    this.emit('history:change', this.getHistoryState())
  }

  /**
   * Add a new history entry
   */
  public addEntry(data: T, label: string, force: boolean = false): void {
    if (!this.currentData) {
      throw new Error('HistoryService not initialized. Call initialize() first.')
    }

    // Debounce rapid changes unless forced
    if (!force && this.config.debounceMs > 0) {
      if (this.debounceTimer) {
        clearTimeout(this.debounceTimer)
      }
      
      this.debounceTimer = setTimeout(() => {
        this.performAddEntry(data, label)
      }, this.config.debounceMs)
      
      return
    }

    this.performAddEntry(data, label)
  }

  private performAddEntry(data: T, label: string): void {
    if (!this.currentData) return

    // Create deep clones to prevent mutation issues
    const newData = deepClone(data)
    const previousData = deepClone(this.currentData)

    // Generate patch from previous to current state
    const patch = compare(previousData, newData)

    // Skip if no changes detected
    if (patch.length === 0) {
      return
    }

    // Filter out excluded paths
    const filteredPatch = this.config.excludePaths.length > 0
      ? patch.filter(op => !this.isPathExcluded(op.path))
      : patch

    if (filteredPatch.length === 0) {
      return
    }

    // Remove any history entries after current index (when undoing then making new changes)
    if (this.currentIndex < this.history.length - 1) {
      this.history = this.history.slice(0, this.currentIndex + 1)
    }

    // Create new history entry
    const entry: HistoryEntry<T> = {
      id: this.generateId(),
      timestamp: Date.now(),
      label,
      patch: filteredPatch,
      data: this.config.enableSnapshots ? newData : {} as T,
      metadata: {
        changeCount: filteredPatch.length,
        size: JSON.stringify(newData).length
      }
    }

    // Add to history
    this.history.push(entry)
    this.currentIndex = this.history.length - 1
    this.currentData = newData

    // Manage history size
    this.enforceMaxEntries()

    // Update snapshot tracking
    if (this.config.enableSnapshots && 
        (this.currentIndex - this.lastSnapshotIndex) >= this.config.snapshotInterval) {
      this.lastSnapshotIndex = this.currentIndex
    }

    // Emit events
    this.emit('history:add', { entry, totalEntries: this.history.length })
    this.emit('history:change', this.getHistoryState())
  }

  /**
   * Undo the last change
   */
  public undo(): T | null {
    if (!this.canUndo()) {
      return null
    }

    const currentEntry = this.history[this.currentIndex]
    this.currentIndex--

    // Reconstruct data by applying inverse patches
    const targetData = this.reconstructDataAtIndex(this.currentIndex)
    if (!targetData) {
      return null
    }

    const previousEntry = this.history[this.currentIndex]
    this.currentData = targetData

    this.emit('history:undo', { previousEntry: currentEntry, currentEntry: previousEntry })
    this.emit('history:change', this.getHistoryState())

    return deepClone(targetData)
  }

  /**
   * Redo the next change
   */
  public redo(): T | null {
    if (!this.canRedo()) {
      return null
    }

    this.currentIndex++
    const targetEntry = this.history[this.currentIndex]

    // Reconstruct data at target index
    const targetData = this.reconstructDataAtIndex(this.currentIndex)
    if (!targetData) {
      return null
    }

    const previousData = this.currentData
    this.currentData = targetData

    this.emit('history:redo', { 
      previousEntry: { ...targetEntry, data: previousData } as HistoryEntry<T>, 
      currentEntry: targetEntry 
    })
    this.emit('history:change', this.getHistoryState())

    return deepClone(targetData)
  }

  /**
   * Jump to a specific history entry by index
   */
  public jumpTo(index: number): T | null {
    if (index < 0 || index >= this.history.length) {
      return null
    }

    this.currentIndex = index
    const targetData = this.reconstructDataAtIndex(index)
    
    if (targetData) {
      this.currentData = targetData
      this.emit('history:change', this.getHistoryState())
      return deepClone(targetData)
    }

    return null
  }

  /**
   * Get the current data state
   */
  public getCurrentData(): T | null {
    return this.currentData ? deepClone(this.currentData) : null
  }

  /**
   * Get a specific history entry
   */
  public getEntry(index: number): HistoryEntry<T> | null {
    return this.history[index] || null
  }

  /**
   * Get all history entries
   */
  public getHistory(): HistoryEntry<T>[] {
    return [...this.history]
  }

  /**
   * Get current history state
   */
  public getHistoryState() {
    return {
      canUndo: this.canUndo(),
      canRedo: this.canRedo(),
      currentEntry: this.history[this.currentIndex] || undefined,
      currentIndex: this.currentIndex,
      totalEntries: this.history.length
    }
  }

  /**
   * Check if undo is possible
   */
  public canUndo(): boolean {
    return this.currentIndex > 0
  }

  /**
   * Check if redo is possible
   */
  public canRedo(): boolean {
    return this.currentIndex < this.history.length - 1
  }

  /**
   * Clear all history
   */
  public clear(): void {
    this.history = []
    this.currentIndex = -1
    this.lastSnapshotIndex = -1
    this.currentData = null
    
    if (this.debounceTimer) {
      clearTimeout(this.debounceTimer)
      this.debounceTimer = null
    }

    this.emit('history:clear', undefined)
    this.emit('history:change', this.getHistoryState())
  }

  /**
   * Get memory usage statistics
   */
  public getStats(): {
    totalEntries: number
    currentIndex: number
    memoryUsage: number
    averagePatchSize: number
  } {
    const memoryUsage = JSON.stringify(this.history).length
    const averagePatchSize = this.history.length > 0
      ? this.history.reduce((sum, entry) => sum + entry.patch.length, 0) / this.history.length
      : 0

    return {
      totalEntries: this.history.length,
      currentIndex: this.currentIndex,
      memoryUsage,
      averagePatchSize
    }
  }

  /**
   * Event listener management
   */
  public on<K extends keyof HistoryEvents<T>>(event: K, listener: (data: HistoryEvents<T>[K]) => void): void {
    if (!this.eventListeners.has(event)) {
      this.eventListeners.set(event, new Set())
    }
    this.eventListeners.get(event)!.add(listener)
  }

  public off<K extends keyof HistoryEvents<T>>(event: K, listener: (data: HistoryEvents<T>[K]) => void): void {
    const listeners = this.eventListeners.get(event)
    if (listeners) {
      listeners.delete(listener)
    }
  }

  private emit<K extends keyof HistoryEvents<T>>(event: K, data: HistoryEvents<T>[K]): void {
    const listeners = this.eventListeners.get(event)
    if (listeners) {
      listeners.forEach(listener => listener(data))
    }
  }

  /**
   * Reconstruct data at a specific index using patches
   */
  private reconstructDataAtIndex(targetIndex: number): T | null {
    if (targetIndex < 0 || targetIndex >= this.history.length) {
      return null
    }

    // Find the nearest snapshot
    let baseIndex = 0
    let baseData: T

    // Look for the most recent snapshot before or at target index
    if (this.config.enableSnapshots) {
      for (let i = targetIndex; i >= 0; i--) {
        const entry = this.history[i]
        if (entry.data && Object.keys(entry.data).length > 0) {
          baseIndex = i
          baseData = deepClone(entry.data)
          break
        }
      }
    }

    // If no snapshot found, start from the first entry
    if (!baseData!) {
      baseData = deepClone(this.history[0].data)
      baseIndex = 0
    }

    // Apply patches from base to target
    let currentData = baseData
    for (let i = baseIndex + 1; i <= targetIndex; i++) {
      const entry = this.history[i]
      try {
        currentData = applyPatch(currentData, entry.patch, false, false).newDocument
      } catch (error) {
        console.error('Failed to apply patch at index', i, error)
        return null
      }
    }

    return currentData
  }

  /**
   * Enforce maximum number of entries
   */
  private enforceMaxEntries(): void {
    if (this.history.length > this.config.maxEntries) {
      const excessCount = this.history.length - this.config.maxEntries
      this.history = this.history.slice(excessCount)
      this.currentIndex = Math.max(0, this.currentIndex - excessCount)
      this.lastSnapshotIndex = Math.max(-1, this.lastSnapshotIndex - excessCount)
    }
  }

  /**
   * Check if a path should be excluded from diffing
   */
  private isPathExcluded(path: string): boolean {
    return this.config.excludePaths.some(excludePath => {
      if (excludePath.endsWith('*')) {
        return path.startsWith(excludePath.slice(0, -1))
      }
      return path === excludePath
    })
  }

  /**
   * Generate a unique ID for history entries
   */
  private generateId(): string {
    return `${Date.now()}-${Math.random().toString(36).substr(2, 9)}`
  }

  /**
   * Clean up resources
   */
  public destroy(): void {
    this.clear()
    this.eventListeners.clear()
  }
}

/**
 * Factory function to create a new HistoryService instance
 */
export function createHistoryService<T = any>(
  initialData?: T, 
  config: HistoryConfig = {}
): HistoryService<T> {
  return new HistoryService<T>(initialData, config)
}

/**
 * Composable for Vue components
 */
export function useHistory<T = any>(
  initialData?: T,
  config: HistoryConfig = {}
) {
  const historyService = new HistoryService<T>(initialData, config)
  
  return {
    historyService,
    addEntry: (data: T, label: string) => historyService.addEntry(data, label),
    undo: () => historyService.undo(),
    redo: () => historyService.redo(),
    canUndo: () => historyService.canUndo(),
    canRedo: () => historyService.canRedo(),
    getCurrentData: () => historyService.getCurrentData(),
    getHistoryState: () => historyService.getHistoryState(),
    clear: () => historyService.clear(),
    getStats: () => historyService.getStats(),
    on: historyService.on.bind(historyService),
    off: historyService.off.bind(historyService)
  }
}

import { EventEmitter } from './EventEmitter'
import type { LayerNode, EditorState } from './types'
import type { Layer } from '../../types'

/**
 * Base interface for all commands that can be executed and undone
 */
export interface Command {
  /** Unique identifier for the command */
  id: string
  /** Human-readable description of the command */
  description: string
  /** Timestamp when the command was created */
  timestamp: number
  /** Execute the command (do the action) */
  execute(): Promise<void> | void
  /** Undo the command (reverse the action) */
  undo(): Promise<void> | void
  /** Check if the command can be executed */
  canExecute(): boolean
  /** Check if the command can be undone */
  canUndo(): boolean
  /** Merge this command with another command if possible */
  merge?(other: Command): Command | null
}

/**
 * Command for creating a layer
 */
export class CreateLayerCommand implements Command {
  public readonly id: string
  public readonly description: string
  public readonly timestamp: number

  constructor(
    private layerManager: any,
    private layerData: LayerNode,
    private emitter: EventEmitter
  ) {
    this.id = `create_layer_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`
    this.description = `Create ${layerData.name}`
    this.timestamp = Date.now()
  }

  execute(): void {
    // Layer is already created, this is called after the fact
    // But we could use this to re-create the layer if needed
    console.log(`Executed: ${this.description}`)
  }

  undo(): void {
    console.log(`Undoing: ${this.description}`)
    this.layerManager.deleteLayer(this.layerData.id, true)
  }

  canExecute(): boolean {
    return true
  }

  canUndo(): boolean {
    return this.layerManager.getLayer(this.layerData.id) !== null
  }
}

/**
 * Command for deleting a layer
 */
export class DeleteLayerCommand implements Command {
  public readonly id: string
  public readonly description: string
  public readonly timestamp: number

  constructor(
    private layerManager: any,
    private layerData: LayerNode,
    private emitter: EventEmitter
  ) {
    this.id = `delete_layer_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`
    this.description = `Delete ${layerData.name}`
    this.timestamp = Date.now()
  }

  execute(): void {
    // Layer is already deleted, this is called after the fact
    console.log(`Executed: ${this.description}`)
  }

  undo(): void {
    console.log(`Undoing: ${this.description}`)
    // Recreate the layer with all its original properties
    this.layerManager.createLayer(this.layerData.type, this.layerData, true)
  }

  canExecute(): boolean {
    return true
  }

  canUndo(): boolean {
    return this.layerManager.getLayer(this.layerData.id) === null
  }
}

/**
 * Command for layer transformations (position, scale, rotation)
 */
export class TransformLayerCommand implements Command {
  public readonly id: string
  public readonly description: string
  public readonly timestamp: number

  constructor(
    private layerManager: any,
    private layerId: string,
    private oldTransform: Partial<LayerNode>,
    private newTransform: Partial<LayerNode>,
    private emitter: EventEmitter
  ) {
    this.id = `transform_layer_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`
    this.description = `Transform ${oldTransform.name || layerId}`
    this.timestamp = Date.now()
  }

  execute(): void {
    console.log(`Executed: ${this.description}`)
    this.layerManager.updateLayer(this.layerId, this.newTransform, true)
  }

  undo(): void {
    console.log(`Undoing: ${this.description}`)
    this.layerManager.updateLayer(this.layerId, this.oldTransform, true)
  }

  canExecute(): boolean {
    return this.layerManager.getLayer(this.layerId) !== null
  }

  canUndo(): boolean {
    return this.layerManager.getLayer(this.layerId) !== null
  }

  merge(other: Command): Command | null {
    // Merge consecutive transforms to the same layer within a shorter time window
    // Only merge very rapid transforms (like during a single drag operation)
    if (other instanceof TransformLayerCommand && 
        other.layerId === this.layerId && 
        (other.timestamp - this.timestamp) < 500) { // Reduced to 500ms for more discrete operations
      
      return new TransformLayerCommand(
        this.layerManager,
        this.layerId,
        this.oldTransform, // Keep original old transform
        other.newTransform, // Use latest new transform
        this.emitter
      )
    }
    return null
  }
}
export class UpdateLayerCommand implements Command {
  public readonly id: string
  public readonly description: string
  public readonly timestamp: number

  constructor(
    private layerManager: any,
    private layerId: string,
    private oldData: Partial<LayerNode>,
    private newData: Partial<LayerNode>,
    private emitter: EventEmitter
  ) {
    this.id = `update_layer_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`
    this.description = `Update ${oldData.name || layerId}`
    this.timestamp = Date.now()
  }

  execute(): void {
    console.log(`Executed: ${this.description}`)
    this.layerManager.updateLayer(this.layerId, this.newData, true)
  }

  undo(): void {
    console.log(`Undoing: ${this.description}`)
    this.layerManager.updateLayer(this.layerId, this.oldData, true)
  }

  canExecute(): boolean {
    return this.layerManager.getLayer(this.layerId) !== null
  }

  canUndo(): boolean {
    return this.layerManager.getLayer(this.layerId) !== null
  }

  merge(other: Command): Command | null {
    // Merge consecutive updates to the same layer within a short time window
    if (other instanceof UpdateLayerCommand && 
        other.layerId === this.layerId && 
        (other.timestamp - this.timestamp) < 1000) { // 1 second window
      
      return new UpdateLayerCommand(
        this.layerManager,
        this.layerId,
        this.oldData, // Keep original old data
        other.newData, // Use latest new data
        this.emitter
      )
    }
    return null
  }
}

/**
 * Command for reordering layers
 */
export class ReorderLayersCommand implements Command {
  public readonly id: string
  public readonly description: string
  public readonly timestamp: number

  constructor(
    private layerManager: any,
    private oldOrder: string[],
    private newOrder: string[],
    private emitter: EventEmitter
  ) {
    this.id = `reorder_layers_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`
    this.description = `Reorder ${newOrder.length} layers`
    this.timestamp = Date.now()
  }

  execute(): void {
    console.log(`Executed: ${this.description}`)
    this.layerManager.reorderLayers(this.newOrder, true)
  }

  undo(): void {
    console.log(`Undoing: ${this.description}`)
    this.layerManager.reorderLayers(this.oldOrder, true)
  }

  canExecute(): boolean {
    return this.newOrder.every(id => this.layerManager.getLayer(id) !== null)
  }

  canUndo(): boolean {
    return this.oldOrder.every(id => this.layerManager.getLayer(id) !== null)
  }
}

/**
 * Compound command that groups multiple commands together
 */
export class CompoundCommand implements Command {
  public readonly id: string
  public readonly description: string
  public readonly timestamp: number

  constructor(
    private commands: Command[],
    description?: string
  ) {
    this.id = `compound_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`
    this.description = description || `${commands.length} operations`
    this.timestamp = Date.now()
  }

  execute(): void {
    console.log(`Executed compound: ${this.description}`)
    for (const command of this.commands) {
      if (command.canExecute()) {
        command.execute()
      }
    }
  }

  undo(): void {
    console.log(`Undoing compound: ${this.description}`)
    // Undo in reverse order
    for (let i = this.commands.length - 1; i >= 0; i--) {
      const command = this.commands[i]
      if (command.canUndo()) {
        command.undo()
      }
    }
  }

  canExecute(): boolean {
    return this.commands.every(cmd => cmd.canExecute())
  }

  canUndo(): boolean {
    return this.commands.every(cmd => cmd.canUndo())
  }

  addCommand(command: Command): void {
    this.commands.push(command)
  }
}

/**
 * History state interface
 */
export interface HistoryState {
  canUndo: boolean
  canRedo: boolean
  currentIndex: number
  totalCommands: number
  undoDescription?: string
  redoDescription?: string
}

/**
 * Advanced History Manager with Command Pattern
 * 
 * Features:
 * - Command pattern for reversible operations
 * - Command merging for performance
 * - Compound commands for grouped operations
 * - Proper error handling and recovery
 * - Memory management with size limits
 * - Event emission for UI updates
 */
export class HistoryManager extends EventEmitter {
  private mergingEnabled: boolean = true
  private commands: Command[] = []
  private currentIndex: number = -1
  private isExecutingCommand: boolean = false
  private maxHistorySize: number

  constructor(
    private layerManager: any,
    private emitter: EventEmitter,
    maxHistorySize: number = 100
  ) {
    super()
    this.maxHistorySize = maxHistorySize
  }

  /**
   * Temporarily disable command merging for discrete operations
   */
  disableMergingTemporarily(duration: number = 1000): void {
    this.mergingEnabled = false
    setTimeout(() => {
      this.mergingEnabled = true
    }, duration)
  }

  /**
   * Add a new command to the history
   */
  addCommand(command: Command, execute: boolean = false): void {
    if (this.isExecutingCommand) {
      console.warn('Cannot add command while executing another command')
      return
    }

    try {
      // Try to merge with the last command if merging is enabled
      if (this.mergingEnabled && this.commands.length > 0 && this.currentIndex >= 0) {
        const lastCommand = this.commands[this.currentIndex]
        const mergedCommand = lastCommand.merge?.(command)
        
        if (mergedCommand) {
          console.log('Merged command with previous command')
          this.commands[this.currentIndex] = mergedCommand
          this.emitHistoryChanged()
          return
        }
      }

      // Remove any commands after current index (we're creating a new branch)
      if (this.currentIndex < this.commands.length - 1) {
        this.commands = this.commands.slice(0, this.currentIndex + 1)
      }

      // Execute command if requested
      if (execute && command.canExecute()) {
        this.isExecutingCommand = true
        command.execute()
        this.isExecutingCommand = false
      }

      // Add command to history
      this.commands.push(command)
      this.currentIndex++

      // Maintain size limit
      if (this.commands.length > this.maxHistorySize) {
        this.commands.shift()
        this.currentIndex--
      }

      console.log(`Added command: ${command.description} (${this.currentIndex + 1}/${this.commands.length})`)
      this.emitHistoryChanged()

    } catch (error) {
      console.error('Error adding command to history:', error)
      this.isExecutingCommand = false
    }
  }

  /**
   * Create and add a layer creation command
   */
  addCreateLayerCommand(layerData: LayerNode): void {
    const command = new CreateLayerCommand(this.layerManager, layerData, this.emitter)
    this.addCommand(command)
  }

  /**
   * Create and add a layer deletion command
   */
  addDeleteLayerCommand(layerData: LayerNode): void {
    const command = new DeleteLayerCommand(this.layerManager, layerData, this.emitter)
    this.addCommand(command)
  }

  /**
   * Create and add a layer update command
   */
  addUpdateLayerCommand(layerId: string, oldData: Partial<LayerNode>, newData: Partial<LayerNode>): void {
    const command = new UpdateLayerCommand(this.layerManager, layerId, oldData, newData, this.emitter)
    this.addCommand(command)
  }

  /**
   * Create and add a layer transform command
   */
  addTransformLayerCommand(layerId: string, oldTransform: Partial<LayerNode>, newTransform: Partial<LayerNode>): void {
    const command = new TransformLayerCommand(this.layerManager, layerId, oldTransform, newTransform, this.emitter)
    this.addCommand(command)
  }

  /**
   * Create and add a layer reorder command
   */
  addReorderLayersCommand(oldOrder: string[], newOrder: string[]): void {
    const command = new ReorderLayersCommand(this.layerManager, oldOrder, newOrder, this.emitter)
    this.addCommand(command)
  }

  /**
   * Undo the last command
   */
  undo(): boolean {
    if (!this.canUndo()) {
      console.warn('Cannot undo: no commands available or already at beginning')
      return false
    }

    const command = this.commands[this.currentIndex]
    
    if (!command.canUndo()) {
      console.warn(`Cannot undo command: ${command.description}`)
      return false
    }

    try {
      this.isExecutingCommand = true
      console.log(`Undoing: ${command.description}`)
      
      command.undo()
      this.currentIndex--
      
      this.emitHistoryChanged()
      console.log(`Undo successful (${this.currentIndex + 1}/${this.commands.length})`)
      return true

    } catch (error) {
      console.error('Error during undo:', error)
      return false
    } finally {
      this.isExecutingCommand = false
    }
  }

  /**
   * Redo the next command
   */
  redo(): boolean {
    if (!this.canRedo()) {
      console.warn('Cannot redo: no commands available or already at end')
      return false
    }

    const command = this.commands[this.currentIndex + 1]
    
    if (!command.canExecute()) {
      console.warn(`Cannot redo command: ${command.description}`)
      return false
    }

    try {
      this.isExecutingCommand = true
      console.log(`Redoing: ${command.description}`)
      
      command.execute()
      this.currentIndex++
      
      this.emitHistoryChanged()
      console.log(`Redo successful (${this.currentIndex + 1}/${this.commands.length})`)
      return true

    } catch (error) {
      console.error('Error during redo:', error)
      return false
    } finally {
      this.isExecutingCommand = false
    }
  }

  /**
   * Check if undo is possible
   */
  canUndo(): boolean {
    return this.currentIndex >= 0 && 
           this.currentIndex < this.commands.length &&
           !this.isExecutingCommand
  }

  /**
   * Check if redo is possible
   */
  canRedo(): boolean {
    return this.currentIndex < this.commands.length - 1 &&
           !this.isExecutingCommand
  }

  /**
   * Get current history state
   */
  getState(): HistoryState {
    const undoCommand = this.canUndo() ? this.commands[this.currentIndex] : null
    const redoCommand = this.canRedo() ? this.commands[this.currentIndex + 1] : null

    return {
      canUndo: this.canUndo(),
      canRedo: this.canRedo(),
      currentIndex: this.currentIndex,
      totalCommands: this.commands.length,
      undoDescription: undoCommand?.description,
      redoDescription: redoCommand?.description
    }
  }

  /**
   * Clear all history
   */
  clear(): void {
    this.commands = []
    this.currentIndex = -1
    this.emitHistoryChanged()
    console.log('History cleared')
  }

  /**
   * Get command history for debugging
   */
  getCommands(): Command[] {
    return [...this.commands]
  }

  /**
   * Enable or disable command merging
   */
  setMergingEnabled(enabled: boolean): void {
    this.mergingEnabled = enabled
  }

  /**
   * Set maximum history size
   */
  setMaxHistorySize(size: number): void {
    this.maxHistorySize = Math.max(1, size)
    
    // Trim history if needed
    if (this.commands.length > this.maxHistorySize) {
      const excess = this.commands.length - this.maxHistorySize
      this.commands.splice(0, excess)
      this.currentIndex = Math.max(-1, this.currentIndex - excess)
    }
  }

  /**
   * Start a compound command (for grouping multiple operations)
   */
  startCompound(description?: string): CompoundCommand {
    return new CompoundCommand([], description)
  }

  /**
   * Emit history changed event
   */
  private emitHistoryChanged(): void {
    const state = this.getState()
    this.emit('history:changed', state)
    
    // Also emit to the main emitter for compatibility
    this.emitter.emit('history:changed', {
      canUndo: state.canUndo,
      canRedo: state.canRedo,
      currentIndex: state.currentIndex,
      totalStates: state.totalCommands
    })
  }

  /**
   * Check if currently executing a command
   */
  isExecuting(): boolean {
    return this.isExecutingCommand
  }

  /**
   * Destroy the history manager
   */
  destroy(): void {
    this.clear()
    this.removeAllListeners()
  }
}

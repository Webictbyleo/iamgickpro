/**
 * Browser-compatible EventEmitter implementation
 * Replacement for Node.js EventEmitter in browser environments
 */
export class EventEmitter {
  private events: Map<string, Function[]> = new Map()
  private maxListeners: number = 10

  /**
   * Add an event listener
   */
  on(event: string, listener: Function): this {
    if (!this.events.has(event)) {
      this.events.set(event, [])
    }
    
    const listeners = this.events.get(event)!
    listeners.push(listener)
    
    // Warn if too many listeners
    if (listeners.length > this.maxListeners) {
      console.warn(`Possible EventEmitter memory leak detected. ${listeners.length} listeners added. Use setMaxListeners() to increase limit.`)
    }
    
    return this
  }

  /**
   * Add a one-time event listener
   */
  once(event: string, listener: Function): this {
    const onceWrapper = (...args: any[]) => {
      this.off(event, onceWrapper)
      listener.apply(this, args)
    }
    
    return this.on(event, onceWrapper)
  }

  /**
   * Remove an event listener
   */
  off(event: string, listener: Function): this {
    const listeners = this.events.get(event)
    if (!listeners) return this
    
    const index = listeners.indexOf(listener)
    if (index > -1) {
      listeners.splice(index, 1)
    }
    
    if (listeners.length === 0) {
      this.events.delete(event)
    }
    
    return this
  }

  /**
   * Remove all listeners for an event, or all events if no event specified
   */
  removeAllListeners(event?: string): this {
    if (event) {
      this.events.delete(event)
    } else {
      this.events.clear()
    }
    return this
  }

  /**
   * Emit an event
   */
  emit(event: string, ...args: any[]): boolean {
    const listeners = this.events.get(event)
    if (!listeners || listeners.length === 0) {
      return false
    }
    
    listeners.forEach(listener => {
      try {
        listener.apply(this, args)
      } catch (error) {
        console.error(`Error in event listener for "${event}":`, error)
      }
    })
    
    return true
  }

  /**
   * Get the number of listeners for an event
   */
  listenerCount(event: string): number {
    const listeners = this.events.get(event)
    return listeners ? listeners.length : 0
  }

  /**
   * Get all listeners for an event
   */
  listeners(event: string): Function[] {
    const listeners = this.events.get(event)
    return listeners ? [...listeners] : []
  }

  /**
   * Get all event names that have listeners
   */
  eventNames(): string[] {
    return Array.from(this.events.keys())
  }

  /**
   * Set the maximum number of listeners per event
   */
  setMaxListeners(n: number): this {
    this.maxListeners = n
    return this
  }

  /**
   * Get the maximum number of listeners per event
   */
  getMaxListeners(): number {
    return this.maxListeners
  }
}

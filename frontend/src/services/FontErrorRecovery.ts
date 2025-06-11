/**
 * Font Loading Error Recovery and Edge Case Handling
 */

export class FontErrorRecovery {
  private static maxRetries = 3
  private static retryDelay = 1000
  private static networkTimeoutMs = 10000

  /**
   * Handle font loading with retry logic and fallbacks
   */
  static async loadFontWithRetry(
    fontFamily: string,
    options: any,
    retryCount = 0
  ): Promise<void> {
    try {
      await this.loadFontWithTimeout(fontFamily, options)
    } catch (error) {
      if (retryCount < this.maxRetries) {
        console.warn(`Font loading failed for ${fontFamily}, retrying (${retryCount + 1}/${this.maxRetries})...`)
        
        // Exponential backoff
        const delay = this.retryDelay * Math.pow(2, retryCount)
        await this.delay(delay)
        
        return this.loadFontWithRetry(fontFamily, options, retryCount + 1)
      }
      
      // Final failure - log and use fallback
      console.error(`Font loading failed permanently for ${fontFamily}:`, error)
      this.handleFinalFontFailure(fontFamily)
      throw error
    }
  }

  /**
   * Load font with network timeout
   */
  private static async loadFontWithTimeout(fontFamily: string, options: any): Promise<void> {
    return Promise.race([
      this.loadGoogleFont(fontFamily, options),
      this.timeoutPromise(this.networkTimeoutMs, `Font loading timeout for ${fontFamily}`)
    ])
  }

  /**
   * Handle permanent font loading failure
   */
  private static handleFinalFontFailure(fontFamily: string): void {
    // Remove from cache to prevent future attempts
    localStorage.removeItem(`font-cache-${fontFamily}`)
    
    // Log analytics event
    this.logFontFailure(fontFamily)
    
    // Show user notification (optional)
    this.showFontFailureNotification(fontFamily)
  }

  private static async loadGoogleFont(fontFamily: string, options: any): Promise<void> {
    // Implementation would be here
    throw new Error('Not implemented')
  }

  private static timeoutPromise(ms: number, message: string): Promise<never> {
    return new Promise((_, reject) => {
      setTimeout(() => reject(new Error(message)), ms)
    })
  }

  private static delay(ms: number): Promise<void> {
    return new Promise(resolve => setTimeout(resolve, ms))
  }

  private static logFontFailure(fontFamily: string): void {
    // Send to analytics service
    console.warn(`Font failure logged for analytics: ${fontFamily}`)
  }

  private static showFontFailureNotification(fontFamily: string): void {
    // Optional: Show user-friendly notification
    console.info(`Using fallback font for ${fontFamily}`)
  }
}

/**
 * Font Performance Monitor
 */
export class FontPerformanceMonitor {
  private static metrics = new Map<string, {
    loadStartTime: number
    loadEndTime?: number
    loadDuration?: number
    retryCount: number
    success: boolean
  }>()

  static startLoadMetric(fontFamily: string): void {
    this.metrics.set(fontFamily, {
      loadStartTime: performance.now(),
      retryCount: 0,
      success: false
    })
  }

  static endLoadMetric(fontFamily: string, success: boolean): void {
    const metric = this.metrics.get(fontFamily)
    if (metric) {
      metric.loadEndTime = performance.now()
      metric.loadDuration = metric.loadEndTime - metric.loadStartTime
      metric.success = success
      
      // Log performance data
      this.logPerformanceMetric(fontFamily, metric)
    }
  }

  static incrementRetryCount(fontFamily: string): void {
    const metric = this.metrics.get(fontFamily)
    if (metric) {
      metric.retryCount++
    }
  }

  private static logPerformanceMetric(fontFamily: string, metric: any): void {
    if (metric.loadDuration && metric.loadDuration > 3000) {
      console.warn(`Slow font loading detected: ${fontFamily} took ${metric.loadDuration}ms`)
    }
    
    if (metric.retryCount > 0) {
      console.warn(`Font required retries: ${fontFamily} (${metric.retryCount} retries)`)
    }
  }

  static getMetrics(): Map<string, any> {
    return new Map(this.metrics)
  }
}

/**
 * Font Cache Manager for offline support
 */
export class FontCacheManager {
  private static cacheKey = 'font-manager-cache'
  private static cacheVersion = '1.0'
  private static maxCacheSize = 50
  private static cacheExpiration = 7 * 24 * 60 * 60 * 1000 // 7 days

  /**
   * Cache font loading result
   */
  static cacheFontResult(fontFamily: string, loaded: boolean, error?: string): void {
    try {
      const cache = this.getCache()
      cache[fontFamily] = {
        loaded,
        error,
        timestamp: Date.now(),
        version: this.cacheVersion
      }
      
      // Limit cache size
      this.limitCacheSize(cache)
      
      localStorage.setItem(this.cacheKey, JSON.stringify(cache))
    } catch (error) {
      console.warn('Failed to cache font result:', error)
    }
  }

  /**
   * Get cached font result
   */
  static getCachedFontResult(fontFamily: string): { loaded: boolean; error?: string } | null {
    try {
      const cache = this.getCache()
      const result = cache[fontFamily]
      
      if (!result) return null
      
      // Check expiration
      if (Date.now() - result.timestamp > this.cacheExpiration) {
        delete cache[fontFamily]
        localStorage.setItem(this.cacheKey, JSON.stringify(cache))
        return null
      }
      
      // Check version
      if (result.version !== this.cacheVersion) {
        delete cache[fontFamily]
        localStorage.setItem(this.cacheKey, JSON.stringify(cache))
        return null
      }
      
      return { loaded: result.loaded, error: result.error }
    } catch (error) {
      console.warn('Failed to get cached font result:', error)
      return null
    }
  }

  /**
   * Clear font cache
   */
  static clearCache(): void {
    try {
      localStorage.removeItem(this.cacheKey)
    } catch (error) {
      console.warn('Failed to clear font cache:', error)
    }
  }

  private static getCache(): any {
    try {
      const cached = localStorage.getItem(this.cacheKey)
      return cached ? JSON.parse(cached) : {}
    } catch (error) {
      console.warn('Failed to parse font cache:', error)
      return {}
    }
  }

  private static limitCacheSize(cache: any): void {
    const entries = Object.entries(cache)
    if (entries.length > this.maxCacheSize) {
      // Sort by timestamp and remove oldest
      entries.sort((a: any, b: any) => a[1].timestamp - b[1].timestamp)
      
      const toRemove = entries.slice(0, entries.length - this.maxCacheSize)
      toRemove.forEach(([key]) => delete cache[key])
    }
  }
}

/**
 * Network Status Monitor for offline font handling
 */
export class NetworkStatusMonitor {
  private static isOnline = navigator.onLine
  private static listeners: ((online: boolean) => void)[] = []

  static initialize(): void {
    window.addEventListener('online', () => {
      this.isOnline = true
      this.notifyListeners(true)
    })

    window.addEventListener('offline', () => {
      this.isOnline = false
      this.notifyListeners(false)
    })
  }

  static getStatus(): boolean {
    return this.isOnline
  }

  static addListener(callback: (online: boolean) => void): void {
    this.listeners.push(callback)
  }

  static removeListener(callback: (online: boolean) => void): void {
    const index = this.listeners.indexOf(callback)
    if (index > -1) {
      this.listeners.splice(index, 1)
    }
  }

  private static notifyListeners(online: boolean): void {
    this.listeners.forEach(listener => {
      try {
        listener(online)
      } catch (error) {
        console.warn('Network status listener error:', error)
      }
    })
  }
}

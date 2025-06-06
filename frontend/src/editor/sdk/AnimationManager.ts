import Konva from 'konva'
import type { EditorState, AnimationAPI, LayerNode } from './types'
import type { EventEmitter } from './EventEmitter'

/**
 * AnimationManager - Handles timeline management and keyframe animations
 */
export class AnimationManager implements AnimationAPI {
  private animation: Konva.Animation | null = null
  private layerFinder: ((layerId: string) => LayerNode | null) | null = null
  private keyframes: Map<string, Map<number, Record<string, any>>> = new Map()

  constructor(
    private state: EditorState,
    private emitter: EventEmitter
  ) {
    // Debug canvas count during AnimationManager initialization
    const beforeCanvases = document.querySelectorAll('canvas')
    console.log(`🔍 AnimationManager: Starting constructor, global canvas count: ${beforeCanvases.length}`)
    
    // AnimationManager initialization complete
    const finalCanvases = document.querySelectorAll('canvas')
    console.log(`🔍 AnimationManager: Constructor complete, global canvas count: ${finalCanvases.length}`)
  }

  // ============================================================================
  // PLAYBACK CONTROL
  // ============================================================================

  play(): void {
    if (this.animation) {
      this.animation.start()
      this.state.isPlaying = true
      this.emitter.emit('animation:play')
    }
  }

  pause(): void {
    if (this.animation) {
      this.animation.stop()
      this.state.isPlaying = false
      this.emitter.emit('animation:pause')
    }
  }

  stop(): void {
    if (this.animation) {
      this.animation.stop()
      this.state.isPlaying = false
      this.state.currentTime = 0
      this.emitter.emit('animation:stop')
    }
  }

  seek(time: number): void {
    const clampedTime = Math.max(0, Math.min(this.state.duration, time))
    this.state.currentTime = clampedTime
    this.updateLayersAtTime(clampedTime)
    this.emitter.emit('animation:seek', clampedTime)
  }

  // ============================================================================
  // TIMELINE MANAGEMENT
  // ============================================================================

  setDuration(duration: number): void {
    this.state.duration = Math.max(0.1, duration)
    this.emitter.emit('animation:duration-changed', this.state.duration)
  }

  getDuration(): number {
    return this.state.duration
  }

  getCurrentTime(): number {
    return this.state.currentTime
  }

  isPlaying(): boolean {
    return this.state.isPlaying
  }

  // ============================================================================
  // KEYFRAME MANAGEMENT
  // ============================================================================

  addKeyframe(layerId: string, time: number, properties: Record<string, any>): void {
    if (!this.keyframes.has(layerId)) {
      this.keyframes.set(layerId, new Map())
    }
    
    const layerKeyframes = this.keyframes.get(layerId)!
    layerKeyframes.set(time, { ...properties })
    
    this.emitter.emit('animation:keyframe-added', { layerId, time, properties })
  }

  removeKeyframe(layerId: string, time: number): void {
    const layerKeyframes = this.keyframes.get(layerId)
    if (layerKeyframes) {
      layerKeyframes.delete(time)
      this.emitter.emit('animation:keyframe-removed', { layerId, time })
    }
  }

  // ============================================================================
  // EXPORT/IMPORT
  // ============================================================================

  exportAnimation(): any {
    const exportData: any = {
      duration: this.state.duration,
      tracks: {}
    }
    
    this.keyframes.forEach((keyframeMap, layerId) => {
      exportData.tracks[layerId] = Array.from(keyframeMap.entries()).map(([time, properties]) => ({
        time,
        properties
      }))
    })
    
    return exportData
  }

  importAnimation(data: any): void {
    this.stop()
    this.keyframes.clear()
    
    if (data.duration) {
      this.setDuration(data.duration)
    }
    
    if (data.tracks) {
      Object.entries(data.tracks).forEach(([layerId, keyframeArray]) => {
        const keyframes = keyframeArray as any[]
        const layerKeyframes = new Map<number, Record<string, any>>()
        
        keyframes.forEach(keyframeData => {
          layerKeyframes.set(keyframeData.time, keyframeData.properties)
        })
        
        this.keyframes.set(layerId, layerKeyframes)
      })
    }
  }

  // ============================================================================
  // PRIVATE METHODS
  // ============================================================================

  private updateLayersAtTime(time: number): void {
    if (!this.layerFinder) return

    Array.from(this.keyframes.entries()).forEach(([layerId, keyframes]) => {
      const layer = this.layerFinder!(layerId)
      if (!layer || !layer.konvaNode) return

      // Find surrounding keyframes
      const times = Array.from(keyframes.keys()).sort((a: number, b: number) => a - b)
      const beforeTime = times.filter((t: number) => t <= time).pop()
      const afterTime = times.find((t: number) => t > time)

      if (beforeTime !== undefined) {
        const beforeKeyframe = keyframes.get(beforeTime)!
        
        if (afterTime !== undefined && beforeTime !== afterTime) {
          // Interpolate between keyframes
          const afterKeyframe = keyframes.get(afterTime)!
          const progress = (time - beforeTime) / (afterTime - beforeTime)
          
          const interpolated = this.interpolateProperties(beforeKeyframe, afterKeyframe, progress)
          this.applyPropertiesToNode(layer.konvaNode, interpolated)
        } else {
          // Use exact keyframe
          this.applyPropertiesToNode(layer.konvaNode, beforeKeyframe)
        }
      }
    })
  }

  private interpolateProperties(from: Record<string, any>, to: Record<string, any>, progress: number): Record<string, any> {
    const result: Record<string, any> = {}
    
    for (const key in from) {
      if (typeof from[key] === 'number' && typeof to[key] === 'number') {
        result[key] = from[key] + (to[key] - from[key]) * progress
      } else {
        result[key] = progress < 0.5 ? from[key] : to[key]
      }
    }
    
    return result
  }

  private applyPropertiesToNode(node: Konva.Node, properties: Record<string, any>): void {
    node.setAttrs(properties)
  }

  // ============================================================================
  // PUBLIC METHODS FOR INTEGRATION
  // ============================================================================

  destroy(): void {
    if (this.animation) {
      this.animation.stop()
      this.animation = null
    }
    this.keyframes.clear()
  }

  // Method to connect with layer manager
  setLayerFinder(finder: (layerId: string) => LayerNode | null): void {
    this.layerFinder = finder
  }
}

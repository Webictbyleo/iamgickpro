import Konva from 'konva'
import type { Design, Layer, LayerType, DesignBackground } from '../../types'
import type { LayerNode, KonvaLayerRenderer } from './types'
import { TextLayerRenderer } from './renderers/TextLayerRenderer'
import { ImageLayerRenderer } from './renderers/ImageLayerRenderer'
import { ShapeLayerRenderer } from './renderers/ShapeLayerRenderer'
import { SVGLayerRenderer } from './renderers/SVGLayerRenderer'
import { GroupLayerRenderer } from './renderers/GroupLayerRenderer'

/**
 * DesignRenderer - Utility class for rendering designs to various formats
 * Used for previews, thumbnails, and exports
 * Leverages the existing renderer system for consistency and feature completeness
 */
export class DesignRenderer {
  private stage: Konva.Stage | null = null
  private layer: Konva.Layer | null = null
  private renderers: Map<LayerType, KonvaLayerRenderer> = new Map()

  constructor() {
    // Initialize the renderer system
    this.initializeRenderers()
    // Create an off-screen stage for rendering
    this.createOffscreenStage()
  }

  /**
   * Initialize all layer renderers
   */
  private initializeRenderers(): void {
    this.renderers.set('text', new TextLayerRenderer())
    this.renderers.set('image', new ImageLayerRenderer())
    this.renderers.set('shape', new ShapeLayerRenderer())
    this.renderers.set('svg', new SVGLayerRenderer())
    this.renderers.set('group', new GroupLayerRenderer())
  }

  /**
   * Render a design to a canvas element
   */
  async renderToCanvas(design: Design, options: {
    width?: number
    height?: number
    scale?: number
    quality?: number
    background?: string
  } = {}): Promise<HTMLCanvasElement> {
    if (!this.stage || !this.layer) {
      throw new Error('DesignRenderer not properly initialized')
    }

    const {
      width = design.width,
      height = design.height,
      scale = 1,
      quality = 1,
      background = design.data?.backgroundColor || design.data?.background?.color || '#ffffff'
    } = options

    // Set stage dimensions
    this.stage.width(width * scale)
    this.stage.height(height * scale)
    this.stage.scale({ x: scale, y: scale })

    // Clear previous content
    this.layer.destroyChildren()

    // Add background with proper configuration support
    const backgroundRect = new Konva.Rect({
      x: 0,
      y: 0,
      width: design.width,
      height: design.height,
      listening: false
    })
    
    // Apply background configuration (supports solid, linear, and radial gradients)
    const backgroundConfig = design.data?.background || { type: 'solid', color: background }
    this.applyBackgroundToRect(backgroundRect, backgroundConfig, design.width, design.height)
    this.layer.add(backgroundRect)

    // Render layers using the renderer system
    if (design.layers && design.layers.length > 0) {
      await this.renderLayersWithRenderers(design.layers)
    }

    // Convert to canvas
    return this.stage.toCanvas({
      pixelRatio: quality
    })
  }

  /**
   * Render layers using the existing renderer system
   */
  private async renderLayersWithRenderers(layers: Layer[]): Promise<void> {
    if (!this.layer) return

    // Sort layers by z-index for proper rendering order
    const sortedLayers = [...layers].sort((a, b) => (a.zIndex || 0) - (b.zIndex || 0))

    for (const layer of sortedLayers) {
      if (!layer.visible) continue

      const renderer = this.renderers.get(layer.type as LayerType)
      if (!renderer) {
        console.warn(`No renderer found for layer type: ${layer.type}`)
        continue
      }

      try {
        // Convert Layer to LayerNode format expected by renderers
        const layerNode: LayerNode = {
          id: layer.id,
          type: layer.type as LayerType,
          name: layer.name,
          x: layer.transform?.x || 0,
          y: layer.transform?.y || 0,
          width: layer.transform?.width || 100,
          height: layer.transform?.height || 100,
          rotation: layer.transform?.rotation || 0,
          scaleX: layer.transform?.scaleX || 1,
          scaleY: layer.transform?.scaleY || 1,
          opacity: layer.transform?.opacity !== undefined ? layer.transform.opacity : 1,
          visible: layer.visible,
          locked: layer.locked || false,
          zIndex: layer.zIndex || 0,
          properties: layer.properties || {},
          konvaNode: undefined // Will be set by renderer
        }

        // Render the layer using the appropriate renderer
        const konvaNode = await this.renderLayerNode(layerNode, renderer)
        if (konvaNode) {
          this.layer.add(konvaNode as any) // Cast to avoid Konva type issues
        }
      } catch (error) {
        console.error(`Failed to render layer ${layer.id}:`, error)
      }
    }
  }

  /**
   * Render a single layer node using the appropriate renderer
   */
  private async renderLayerNode(layerNode: LayerNode, renderer: KonvaLayerRenderer): Promise<Konva.Node | null> {
    try {
      // Use the renderer to create the Konva node
      const konvaNode = renderer.render(layerNode)
      
      // Apply basic transform properties
      if (konvaNode) {
        konvaNode.setAttrs({
          x: layerNode.x,
          y: layerNode.y,
          rotation: layerNode.rotation,
          scaleX: layerNode.scaleX,
          scaleY: layerNode.scaleY,
          opacity: layerNode.opacity,
          visible: layerNode.visible
        })

        // If the renderer has an update method, call it to apply all properties
        if (typeof renderer.update === 'function') {
          renderer.update(konvaNode, layerNode)
        }

        // For image layers, we need to wait for the image to load
        if (layerNode.type === 'image' && konvaNode instanceof Konva.Group) {
          await this.waitForImageLoad(konvaNode)
        }
      }

      return konvaNode
    } catch (error) {
      console.error(`Renderer failed for layer ${layerNode.id}:`, error)
      return null
    }
  }

  /**
   * Wait for image layers to load before continuing
   */
  private async waitForImageLoad(group: Konva.Group): Promise<void> {
    return new Promise((resolve) => {
      const checkImage = () => {
        const imageNode = group.findOne('Image') as Konva.Image
        if (imageNode && imageNode.image()) {
          resolve()
        } else {
          setTimeout(checkImage, 50)
        }
      }
      checkImage()
    })
  }

  /**
   * Generate a thumbnail as base64 data URL
   */
  async generateThumbnail(design: Design, options: {
    width?: number
    height?: number
    format?: 'png' | 'jpeg'
    quality?: number
    background?: string
  } = {}): Promise<string> {
    const {
      width = 300,
      height = 200,
      format = 'png',
      quality = 0.8,
      background = design.data?.backgroundColor || design.data?.background?.color || '#ffffff'
    } = options

    // Calculate scale to fit design into thumbnail dimensions
    const scaleX = width / design.width
    const scaleY = height / design.height
    const scale = Math.min(scaleX, scaleY)

    const canvas = await this.renderToCanvas(design, {
      width: design.width,
      height: design.height,
      scale,
      quality,
      background
    })

    // Create final thumbnail canvas with exact dimensions
    const thumbnailCanvas = document.createElement('canvas')
    thumbnailCanvas.width = width
    thumbnailCanvas.height = height
    
    const ctx = thumbnailCanvas.getContext('2d')!
    
    // Apply background configuration to thumbnail canvas
    const backgroundConfig = design.data?.background || { type: 'solid', color: background }
    this.applyBackgroundToCanvas(ctx, backgroundConfig, width, height)

    // Center the rendered design in the thumbnail
    const scaledWidth = design.width * scale
    const scaledHeight = design.height * scale
    const offsetX = (width - scaledWidth) / 2
    const offsetY = (height - scaledHeight) / 2

    ctx.drawImage(canvas, offsetX, offsetY, scaledWidth, scaledHeight)

    return thumbnailCanvas.toDataURL(`image/${format}`, quality)
  }

  /**
   * Generate a preview image as blob
   */
  async generatePreviewBlob(design: Design, options: {
    width?: number
    height?: number
    format?: 'png' | 'jpeg'
    quality?: number
    background?: string
  } = {}): Promise<Blob> {
    return new Promise(async (resolve, reject) => {
      try {
        const canvas = await this.renderToCanvas(design, options)
        canvas.toBlob((blob) => {
          if (blob) {
            resolve(blob)
          } else {
            reject(new Error('Failed to generate preview blob'))
          }
        }, `image/${options.format || 'png'}`, options.quality || 0.8)
      } catch (error) {
        reject(error)
      }
    })
  }

  /**
   * Generate multiple preview sizes at once
   */
  async generateMultiplePreviews(design: Design, sizes: Array<{
    name: string
    width: number
    height: number
    format?: 'png' | 'jpeg'
    quality?: number
  }>): Promise<Record<string, string>> {
    const previews: Record<string, string> = {}

    for (const size of sizes) {
      try {
        const thumbnail = await this.generateThumbnail(design, {
          width: size.width,
          height: size.height,
          format: size.format || 'png',
          quality: size.quality || 0.8
        })
        previews[size.name] = thumbnail
      } catch (error) {
        console.error(`Failed to generate preview for size ${size.name}:`, error)
      }
    }

    return previews
  }

  /**
   * Create an off-screen stage for rendering
   */
  private createOffscreenStage(): void {
    // Create a temporary container
    const container = document.createElement('div')
    container.style.position = 'absolute'
    container.style.top = '-9999px'
    container.style.left = '-9999px'
    document.body.appendChild(container)

    this.stage = new Konva.Stage({
      container,
      width: 800,
      height: 600
    })

    this.layer = new Konva.Layer()
    this.stage.add(this.layer)
  }

  /**
   * Clean up resources
   */
  destroy(): void {
    if (this.stage) {
      const container = this.stage.container()
      this.stage.destroy()
      if (container && container.parentNode) {
        container.parentNode.removeChild(container)
      }
      this.stage = null
      this.layer = null
    }
  }

  /**
   * Apply background configuration to a Konva rectangle
   */
  private applyBackgroundToRect(rect: Konva.Rect, backgroundConfig: DesignBackground | null | undefined, width: number, height: number): void {
    if (!backgroundConfig) {
      rect.fill('#ffffff')
      return
    }

    // Handle different background types
    if (backgroundConfig.type === 'solid' || !backgroundConfig.type) {
      // Solid color background
      rect.fill(backgroundConfig.color || '#ffffff')
      rect.fillLinearGradientStartPoint(undefined)
      rect.fillLinearGradientEndPoint(undefined)
      rect.fillRadialGradientStartPoint(undefined)
      rect.fillRadialGradientEndPoint(undefined)
    } else if (backgroundConfig.type === 'linear' && backgroundConfig.gradient) {
      // Linear gradient background
      const gradient = backgroundConfig.gradient
      const angle = gradient.angle || 0
      
      // Convert angle to start/end points
      const radians = (angle * Math.PI) / 180
      const length = Math.sqrt(width * width + height * height)
      const centerX = width / 2
      const centerY = height / 2
      const startX = centerX - (Math.cos(radians) * length) / 2
      const startY = centerY - (Math.sin(radians) * length) / 2
      const endX = centerX + (Math.cos(radians) * length) / 2
      const endY = centerY + (Math.sin(radians) * length) / 2
      
      // Build color stops array
      const colorStops: (number | string)[] = []
      gradient.colors.forEach((stop: { color: string; stop: number }) => {
        colorStops.push(stop.stop, stop.color)
      })
      
      rect.fill(undefined)
      rect.fillLinearGradientStartPoint({ x: startX, y: startY })
      rect.fillLinearGradientEndPoint({ x: endX, y: endY })
      rect.fillLinearGradientColorStops(colorStops)
      
      // Clear radial gradient properties
      rect.fillRadialGradientStartPoint(undefined)
      rect.fillRadialGradientEndPoint(undefined)
    } else if (backgroundConfig.type === 'radial' && backgroundConfig.gradient) {
      // Radial gradient background
      const gradient = backgroundConfig.gradient
      
      const centerX = (gradient.centerX || 0.5) * width
      const centerY = (gradient.centerY || 0.5) * height
      const radius = (gradient.radius || 0.7) * Math.max(width, height) / 2
      
      // Build color stops array
      const colorStops: (number | string)[] = []
      gradient.colors.forEach((stop: { color: string; stop: number }) => {
        colorStops.push(stop.stop, stop.color)
      })
      
      rect.fill(undefined)
      rect.fillRadialGradientStartPoint({ x: centerX, y: centerY })
      rect.fillRadialGradientEndPoint({ x: centerX, y: centerY })
      rect.fillRadialGradientStartRadius(0)
      rect.fillRadialGradientEndRadius(radius)
      rect.fillRadialGradientColorStops(colorStops)
      
      // Clear linear gradient properties
      rect.fillLinearGradientStartPoint(undefined)
      rect.fillLinearGradientEndPoint(undefined)
    } else {
      // Fallback to solid color
      rect.fill(backgroundConfig.color || '#ffffff')
    }
  }

  /**
   * Apply background to a canvas context (for thumbnail generation)
   */
  private applyBackgroundToCanvas(ctx: CanvasRenderingContext2D, backgroundConfig: DesignBackground | null | undefined, width: number, height: number): void {
    if (!backgroundConfig) {
      ctx.fillStyle = '#ffffff'
      ctx.fillRect(0, 0, width, height)
      return
    }

    if (backgroundConfig.type === 'solid' || !backgroundConfig.type) {
      // Solid color background
      ctx.fillStyle = backgroundConfig.color || '#ffffff'
      ctx.fillRect(0, 0, width, height)
    } else if (backgroundConfig.type === 'linear' && backgroundConfig.gradient) {
      // Linear gradient background
      const gradient = backgroundConfig.gradient
      const angle = gradient.angle || 0
      
      // Convert angle to start/end points
      const radians = (angle * Math.PI) / 180
      const length = Math.sqrt(width * width + height * height)
      const centerX = width / 2
      const centerY = height / 2
      const startX = centerX - (Math.cos(radians) * length) / 2
      const startY = centerY - (Math.sin(radians) * length) / 2
      const endX = centerX + (Math.cos(radians) * length) / 2
      const endY = centerY + (Math.sin(radians) * length) / 2
      
      // Create gradient
      const canvasGradient = ctx.createLinearGradient(startX, startY, endX, endY)
      gradient.colors.forEach((stop: { color: string; stop: number }) => {
        canvasGradient.addColorStop(stop.stop, stop.color)
      })
      
      ctx.fillStyle = canvasGradient
      ctx.fillRect(0, 0, width, height)
    } else if (backgroundConfig.type === 'radial' && backgroundConfig.gradient) {
      // Radial gradient background
      const gradient = backgroundConfig.gradient
      
      const centerX = (gradient.centerX || 0.5) * width
      const centerY = (gradient.centerY || 0.5) * height
      const radius = (gradient.radius || 0.7) * Math.max(width, height) / 2
      
      // Create gradient
      const canvasGradient = ctx.createRadialGradient(centerX, centerY, 0, centerX, centerY, radius)
      gradient.colors.forEach((stop: { color: string; stop: number }) => {
        canvasGradient.addColorStop(stop.stop, stop.color)
      })
      
      ctx.fillStyle = canvasGradient
      ctx.fillRect(0, 0, width, height)
    } else {
      // Fallback to solid color
      ctx.fillStyle = backgroundConfig.color || '#ffffff'
      ctx.fillRect(0, 0, width, height)
    }
  }
}

// Singleton instance for app-wide use
export const designRenderer = new DesignRenderer()

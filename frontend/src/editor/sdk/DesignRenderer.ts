import Konva from 'konva'
import type { Design, Layer } from '../../types'

/**
 * DesignRenderer - Utility class for rendering designs to various formats
 * Used for previews, thumbnails, and exports
 */
export class DesignRenderer {
  private stage: Konva.Stage | null = null
  private layer: Konva.Layer | null = null

  constructor() {
    // Create an off-screen stage for rendering
    this.createOffscreenStage()
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

    // Add background
    const backgroundRect = new Konva.Rect({
      x: 0,
      y: 0,
      width: design.width,
      height: design.height,
      fill: background
    })
    this.layer.add(backgroundRect)

    // Render layers
    if (design.layers && design.layers.length > 0) {
      await this.renderLayers(design.layers, this.layer)
    }

    // Convert to canvas
    return this.stage.toCanvas({
      pixelRatio: quality
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
    ctx.fillStyle = background
    ctx.fillRect(0, 0, width, height)

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
   * Render layers to a Konva layer
   */
  private async renderLayers(layers: Layer[], parentLayer: Konva.Layer): Promise<void> {
    // Sort layers by zIndex
    const sortedLayers = [...layers].sort((a, b) => (a.zIndex || 0) - (b.zIndex || 0))

    for (const layer of sortedLayers) {
      if (!layer.visible) continue

      try {
        const node = await this.renderLayer(layer)
        if (node) {
          parentLayer.add(node as any) // Type assertion needed for Konva compatibility
        }
      } catch (error) {
        console.error(`Failed to render layer ${layer.id}:`, error)
      }
    }
  }

  /**
   * Render a single layer to a Konva node
   */
  private async renderLayer(layer: Layer): Promise<Konva.Node | null> {
    const transform = layer.transform || {
      x: 0,
      y: 0,
      width: 100,
      height: 100,
      scaleX: 1,
      scaleY: 1,
      rotation: 0
    }

    switch (layer.type) {
      case 'text':
        return this.renderTextLayer(layer, transform)
      case 'image':
        return await this.renderImageLayer(layer, transform)
      case 'shape':
        return this.renderShapeLayer(layer, transform)
      case 'svg':
        return await this.renderSVGLayer(layer, transform)
      default:
        console.warn(`Unsupported layer type for rendering: ${layer.type}`)
        return null
    }
  }

  /**
   * Render text layer
   */
  private renderTextLayer(layer: Layer, transform: any): Konva.Text {
    const props = layer.properties as any
    
    return new Konva.Text({
      x: transform.x,
      y: transform.y,
      width: transform.width,
      height: transform.height,
      scaleX: transform.scaleX || 1,
      scaleY: transform.scaleY || 1,
      rotation: transform.rotation || 0,
      text: props.text || 'Text',
      fontSize: props.fontSize || 16,
      fontFamily: props.fontFamily || 'Arial',
      fill: props.color || '#000000',
      fontStyle: props.fontStyle || 'normal',
      fontVariant: props.fontWeight || 'normal',
      textDecoration: props.textDecoration || '',
      align: props.textAlign || 'left',
      opacity: transform.opacity || 1
    })
  }

  /**
   * Render image layer
   */
  private async renderImageLayer(layer: Layer, transform: any): Promise<Konva.Image> {
    const props = layer.properties as any
    
    return new Promise((resolve, reject) => {
      const imageObj = new Image()
      imageObj.crossOrigin = 'anonymous'
      
      imageObj.onload = () => {
        const image = new Konva.Image({
          x: transform.x,
          y: transform.y,
          width: transform.width,
          height: transform.height,
          scaleX: transform.scaleX || 1,
          scaleY: transform.scaleY || 1,
          rotation: transform.rotation || 0,
          image: imageObj,
          opacity: transform.opacity || 1
        })

        // Apply filters if they exist
        if (props.brightness !== 1 || props.contrast !== 1 || props.saturation !== 1) {
          image.filters([Konva.Filters.Brighten, Konva.Filters.Contrast, Konva.Filters.HSV])
          image.brightness(props.brightness - 1 || 0)
          image.contrast(props.contrast - 1 || 0)
          image.saturation(props.saturation - 1 || 0)
        }

        resolve(image)
      }
      
      imageObj.onerror = () => {
        // Create placeholder rectangle for failed images
        const placeholder = new Konva.Rect({
          x: transform.x,
          y: transform.y,
          width: transform.width,
          height: transform.height,
          fill: '#f0f0f0',
          stroke: '#ddd',
          strokeWidth: 1
        })
        resolve(placeholder as any)
      }
      
      imageObj.src = props.src || ''
    })
  }

  /**
   * Render shape layer
   */
  private renderShapeLayer(layer: Layer, transform: any): Konva.Shape {
    const props = layer.properties as any
    
    const commonAttrs = {
      x: transform.x,
      y: transform.y,
      width: transform.width,
      height: transform.height,
      scaleX: transform.scaleX || 1,
      scaleY: transform.scaleY || 1,
      rotation: transform.rotation || 0,
      fill: props.fill?.color || '#3B82F6',
      stroke: props.stroke || 'transparent',
      strokeWidth: props.strokeWidth || 0,
      opacity: transform.opacity || 1
    }

    switch (props.shapeType) {
      case 'circle':
        return new Konva.Circle({
          ...commonAttrs,
          radius: Math.min(transform.width, transform.height) / 2
        })
      case 'rectangle':
      default:
        return new Konva.Rect({
          ...commonAttrs,
          cornerRadius: props.cornerRadius || 0
        })
    }
  }

  /**
   * Render SVG layer
   */
  private async renderSVGLayer(layer: Layer, transform: any): Promise<Konva.Group> {
    const props = layer.properties as any
    const group = new Konva.Group({
      x: transform.x,
      y: transform.y,
      width: transform.width,
      height: transform.height,
      scaleX: transform.scaleX || 1,
      scaleY: transform.scaleY || 1,
      rotation: transform.rotation || 0,
      opacity: transform.opacity || 1
    })

    // For now, create a placeholder for SVG layers
    // In a full implementation, you'd parse the SVG and convert to Konva shapes
    const placeholder = new Konva.Rect({
      width: transform.width,
      height: transform.height,
      fill: '#e0e7ff',
      stroke: '#3b82f6',
      strokeWidth: 2,
      dash: [5, 5]
    })

    group.add(placeholder)
    return group
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
}

// Singleton instance for app-wide use
export const designRenderer = new DesignRenderer()

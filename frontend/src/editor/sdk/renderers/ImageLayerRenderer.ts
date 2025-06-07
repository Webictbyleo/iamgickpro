import Konva from 'konva'
import type { KonvaLayerRenderer, LayerNode } from '../types'
import type { Layer, ImageLayerProperties } from '../../../types'

/**
 * Image Layer Renderer - renders image layers using Konva.Image
 * Matches backend ImageLayerProperties structure
 */
export class ImageLayerRenderer implements KonvaLayerRenderer {
  private imageCache: Map<string, HTMLImageElement> = new Map()

  canRender(layer: Layer): boolean {
    return layer.type === 'image'
  }

  render(layer: LayerNode): Konva.Node {
    const properties = layer.properties as ImageLayerProperties
    
    const group = new Konva.Group({
      x: layer.x,
      y: layer.y,
      width: layer.width,
      height: layer.height,
      draggable: true
    })

    this.applyCommonProperties(group, properties)

    // Create placeholder while loading
    const placeholder = new Konva.Rect({
      width: layer.width,
      height: layer.height,
      fill: '#f0f0f0',
      stroke: '#ddd',
      strokeWidth: 1,
      dash: [5, 5]
    })

    const loadingText = new Konva.Text({
      x: 0,
      y: layer.height / 2 - 8,
      width: layer.width,
      height: 16,
      text: 'Loading...',
      fontSize: 12,
      fontFamily: 'Arial',
      fill: '#999',
      align: 'center'
    })

    group.add(placeholder)
    group.add(loadingText)

    // Load image if src is provided
    if (properties.src) {
      this.loadImage(properties.src, layer, group, placeholder, loadingText)
    }

    this.setupInteractions(group, layer)
    return group
  }

  update(node: Konva.Node, layer: LayerNode): void {
    if (!(node instanceof Konva.Group)) return

    const properties = layer.properties as ImageLayerProperties
    const imageNode = node.findOne('Image') as Konva.Image

    // Update image source if changed
    if (properties.src && (!imageNode || (imageNode.image() as HTMLImageElement)?.src !== properties.src)) {
      const placeholder = node.findOne('Rect') as Konva.Rect
      const loadingText = node.findOne('Text') as Konva.Text
      
      this.loadImage(properties.src, layer, node, placeholder, loadingText)
    }

    // Update transforms and positioning if image exists
    if (imageNode) {
      this.applyImageTransforms(imageNode, layer, properties)
      
      // Update filters if present
      if (this.hasImageFilters(properties)) {
        this.applyImageFilters(imageNode, properties)
      }
    }
  }

  destroy(node: Konva.Node): void {
    node.destroy()
  }

  getSupportedTypes(): string[] {
    return ['image']
  }

  private applyCommonProperties(node: Konva.Group, properties: ImageLayerProperties): void {
    if (properties.opacity !== undefined && properties.opacity !== null) {
      const opacity = Math.max(0, Math.min(1, properties.opacity))
      node.setAttrs({ opacity })
    }

    if (properties.visible !== undefined) {
      node.setAttrs({ visible: properties.visible })
    }

    if (properties.rotation !== undefined && properties.rotation !== null) {
      node.setAttrs({ rotation: properties.rotation })
    }
  }

  private async loadImage(
    src: string, 
    layer: LayerNode, 
    group: Konva.Group, 
    placeholder: Konva.Rect, 
    loadingText: Konva.Text
  ): Promise<void> {
    try {
      let img = this.imageCache.get(src)
      
      if (!img) {
        img = new Image()
        img.crossOrigin = 'anonymous'
        
        await new Promise((resolve, reject) => {
          img!.onload = resolve
          img!.onerror = reject
          img!.src = src
        })
        
        this.imageCache.set(src, img)
      }

      placeholder.destroy()
      loadingText.destroy()

      const imageNode = new Konva.Image({
        x: 0,
        y: 0,
        image: img,
        width: layer.width,
        height: layer.height
      })

      const properties = layer.properties as ImageLayerProperties
      this.applyImageScaling(imageNode, img, layer, properties)
      this.applyImageTransforms(imageNode, layer, properties)

      if (this.hasImageFilters(properties)) {
        this.applyImageFilters(imageNode, properties)
      }

      group.add(imageNode)
      group.getLayer()?.batchDraw()

    } catch (error) {
      console.error('Failed to load image:', error)
      loadingText.text('Failed to load')
      loadingText.fill('#e74c3c')
      placeholder.fill('#fee')
    }
  }

  private applyImageScaling(
    imageNode: Konva.Image, 
    img: HTMLImageElement, 
    layer: LayerNode,
    properties: ImageLayerProperties
  ): void {
    // Default to 'contain' behavior - scale to fit while preserving aspect ratio
    const containerRatio = layer.width / layer.height
    const imageRatio = img.width / img.height

    if (properties.preserveAspectRatio !== false) {
      // Contain behavior: scale to fit within container while preserving aspect ratio
      if (imageRatio > containerRatio) {
        const height = layer.width / imageRatio
        imageNode.setAttrs({
          width: layer.width,
          height: height,
          y: (layer.height - height) / 2
        })
      } else {
        const width = layer.height * imageRatio
        imageNode.setAttrs({
          width: width,
          height: layer.height,
          x: (layer.width - width) / 2
        })
      }
    } else {
      // Fill behavior: stretch to fill container exactly
      imageNode.setAttrs({
        width: layer.width,
        height: layer.height
      })
    }
  }

  private hasImageFilters(properties: ImageLayerProperties): boolean {
    return !!(
      properties.brightness !== undefined ||
      properties.contrast !== undefined ||
      properties.saturation !== undefined ||
      properties.blur !== undefined ||
      properties.hue !== undefined ||
      properties.sepia !== undefined ||
      properties.grayscale !== undefined ||
      properties.invert !== undefined ||
      (properties.shadow && properties.shadow.enabled)
    )
  }

  private applyImageFilters(imageNode: Konva.Image, properties: ImageLayerProperties): void {
    const konvaFilters: any[] = []

    // Apply filters in the same order as backend for consistency
    // 1. Basic color adjustments
    if (properties.brightness !== undefined && properties.brightness !== 1) {
      konvaFilters.push(Konva.Filters.Brighten)
      // Convert 0-3 range to Konva's -1 to 1 range
      const brightnessValue = (properties.brightness - 1) * 0.5
      imageNode.brightness(Math.max(-1, Math.min(1, brightnessValue)))
    }

    if (properties.contrast !== undefined && properties.contrast !== 1) {
      konvaFilters.push(Konva.Filters.Contrast)
      // Convert 0-3 range to Konva's contrast range (-100 to 100)
      const contrastValue = (properties.contrast - 1) * 50
      imageNode.contrast(Math.max(-100, Math.min(100, contrastValue)))
    }

    if (properties.saturation !== undefined && properties.saturation !== 1) {
      konvaFilters.push(Konva.Filters.HSV)
      imageNode.saturation(Math.max(0, properties.saturation))
    }

    // 2. Hue rotation
    if (properties.hue !== undefined && properties.hue !== 0) {
      if (!konvaFilters.includes(Konva.Filters.HSV)) {
        konvaFilters.push(Konva.Filters.HSV)
      }
      imageNode.hue(properties.hue)
    }

    // 3. Special effects
    if (properties.sepia !== undefined && properties.sepia > 0) {
      konvaFilters.push(Konva.Filters.Sepia)
    }

    if (properties.grayscale !== undefined && properties.grayscale > 0) {
      konvaFilters.push(Konva.Filters.Grayscale)
    }

    if (properties.invert !== undefined && properties.invert > 0) {
      konvaFilters.push(Konva.Filters.Invert)
    }

    // 4. Blur (applied last for best visual result)
    if (properties.blur !== undefined && properties.blur > 0) {
      konvaFilters.push(Konva.Filters.Blur)
      imageNode.blurRadius(Math.max(0, Math.min(50, properties.blur)))
    }

    // 5. Shadow effect (applied to the image node directly, not as a filter)
    if (properties.shadow?.enabled) {
      imageNode.shadowColor(properties.shadow.color || '#000000')
      imageNode.shadowBlur(Math.max(0, properties.shadow.blur || 10))
      imageNode.shadowOffset({
        x: properties.shadow.offsetX || 0,
        y: properties.shadow.offsetY || 0
      })
      imageNode.shadowOpacity(Math.max(0, Math.min(1, properties.shadow.opacity || 0.5)))
    }

    // Apply all filters and cache for performance
    if (konvaFilters.length > 0) {
      imageNode.filters(konvaFilters)
      imageNode.cache()
    }
  }

  private applyImageTransforms(
    imageNode: Konva.Image,
    layer: LayerNode,
    properties: ImageLayerProperties
  ): void {
    let scaleX = 1
    let scaleY = 1
    let offsetX = 0
    let offsetY = 0

    // Handle flip transformations
    if (properties.flipX) {
      scaleX = -1
      offsetX = imageNode.width()
    }

    if (properties.flipY) {
      scaleY = -1
      offsetY = imageNode.height()
    }

    // Apply transformations
    if (scaleX !== 1 || scaleY !== 1) {
      imageNode.setAttrs({
        scaleX,
        scaleY,
        offsetX,
        offsetY
      })
    }

    // Handle object positioning for cropping/positioning within container
    this.applyObjectPosition(imageNode, layer, properties)
  }

  private applyObjectPosition(
    imageNode: Konva.Image,
    layer: LayerNode,
    properties: ImageLayerProperties
  ): void {
    const position = properties.objectPosition || 'center'
    
    // This method adjusts the image position within its container
    // based on the objectPosition property (similar to CSS object-position)
    const imageWidth = imageNode.width()
    const imageHeight = imageNode.height()
    const containerWidth = layer.width
    const containerHeight = layer.height
    
    let x = imageNode.x()
    let y = imageNode.y()
    
    // Calculate positioning based on objectPosition value
    switch (position) {
      case 'top':
        x = (containerWidth - imageWidth) / 2
        y = 0
        break
      case 'bottom':
        x = (containerWidth - imageWidth) / 2
        y = containerHeight - imageHeight
        break
      case 'left':
        x = 0
        y = (containerHeight - imageHeight) / 2
        break
      case 'right':
        x = containerWidth - imageWidth
        y = (containerHeight - imageHeight) / 2
        break
      case 'top left':
        x = 0
        y = 0
        break
      case 'top right':
        x = containerWidth - imageWidth
        y = 0
        break
      case 'bottom left':
        x = 0
        y = containerHeight - imageHeight
        break
      case 'bottom right':
        x = containerWidth - imageWidth
        y = containerHeight - imageHeight
        break
      case 'center':
      default:
        x = (containerWidth - imageWidth) / 2
        y = (containerHeight - imageHeight) / 2
        break
    }
    
    imageNode.setAttrs({ x, y })
  }

  private setupInteractions(group: Konva.Group, layer: LayerNode): void {
    group.on('dragstart', () => {
      const container = group.getStage()?.container()
      if (container) container.style.cursor = 'grabbing'
    })

    group.on('dragend', () => {
      const container = group.getStage()?.container()
      if (container) container.style.cursor = 'default'
      layer.x = group.x()
      layer.y = group.y()
    })

    group.on('mouseenter', () => {
      const container = group.getStage()?.container()
      if (container) container.style.cursor = 'grab'
    })

    group.on('mouseleave', () => {
      const container = group.getStage()?.container()
      if (container) container.style.cursor = 'default'
    })
  }
}

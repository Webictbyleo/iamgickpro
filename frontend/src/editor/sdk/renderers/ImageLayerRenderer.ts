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

    // Update filters if present
    if (imageNode && this.hasImageFilters(properties)) {
      this.applyImageFilters(imageNode, properties)
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
      this.applyObjectFit(imageNode, img, layer, properties)

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

  private applyObjectFit(
    imageNode: Konva.Image, 
    img: HTMLImageElement, 
    layer: LayerNode,
    properties: ImageLayerProperties
  ): void {
    const objectFit = properties.objectFit || 'contain'
    const containerRatio = layer.width / layer.height
    const imageRatio = img.width / img.height

    switch (objectFit) {
      case 'contain':
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
        break

      case 'cover':
        if (imageRatio > containerRatio) {
          const width = layer.height * imageRatio
          imageNode.setAttrs({
            width: width,
            height: layer.height,
            x: (layer.width - width) / 2
          })
        } else {
          const height = layer.width / imageRatio
          imageNode.setAttrs({
            width: layer.width,
            height: height,
            y: (layer.height - height) / 2
          })
        }
        break

      case 'fill':
        imageNode.setAttrs({
          width: layer.width,
          height: layer.height
        })
        break

      case 'none':
        imageNode.setAttrs({
          width: img.width,
          height: img.height,
          x: (layer.width - img.width) / 2,
          y: (layer.height - img.height) / 2
        })
        break
    }
  }

  private hasImageFilters(properties: ImageLayerProperties): boolean {
    return !!(
      properties.brightness !== undefined ||
      properties.contrast !== undefined ||
      properties.saturation !== undefined ||
      properties.blur !== undefined
    )
  }

  private applyImageFilters(imageNode: Konva.Image, properties: ImageLayerProperties): void {
    const konvaFilters: any[] = []

    if (properties.brightness !== undefined && properties.brightness !== 0) {
      konvaFilters.push(Konva.Filters.Brighten)
      imageNode.brightness(properties.brightness / 100)
    }

    if (properties.contrast !== undefined && properties.contrast !== 0) {
      konvaFilters.push(Konva.Filters.Contrast)
      imageNode.contrast(properties.contrast)
    }

    if (properties.saturation !== undefined && properties.saturation !== 0) {
      konvaFilters.push(Konva.Filters.HSV)
      const saturationValue = 1 + (properties.saturation / 100)
      imageNode.saturation(Math.max(0, saturationValue))
    }

    if (properties.blur !== undefined && properties.blur > 0) {
      konvaFilters.push(Konva.Filters.Blur)
      imageNode.blurRadius(properties.blur)
    }

    if (konvaFilters.length > 0) {
      imageNode.filters(konvaFilters)
      imageNode.cache()
    }
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

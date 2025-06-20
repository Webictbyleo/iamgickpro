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
      id: layer.id.toString(),  // Convert number ID to string for Konva
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

    // Update group dimensions
    node.setAttrs({
      width: layer.width,
      height: layer.height
    })

    // Check if we need to reload the image
    const needsImageReload = this.needsImageReload(imageNode, properties.src)
    
    if (properties.src && needsImageReload) {
      // Remove existing image node if it exists
      if (imageNode) {
        imageNode.destroy()
      }
      
      const placeholder = node.findOne('Rect') as Konva.Rect
      const loadingText = node.findOne('Text') as Konva.Text
      
      // If no placeholder exists, create one for loading state
      if (!placeholder) {
        const newPlaceholder = new Konva.Rect({
          width: layer.width,
          height: layer.height,
          fill: '#f0f0f0',
          stroke: '#ddd',
          strokeWidth: 1,
          dash: [5, 5]
        })
        
        const newLoadingText = new Konva.Text({
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
        
        node.add(newPlaceholder)
        node.add(newLoadingText)
        
        this.loadImage(properties.src, layer, node, newPlaceholder, newLoadingText)
      } else {
        this.loadImage(properties.src, layer, node, placeholder, loadingText)
      }
      return // Exit early since loadImage will handle the rest
    }

    // Update transforms and positioning if image exists
    if (imageNode) {
      // Update image dimensions first
      imageNode.setAttrs({
        width: layer.width,
        height: layer.height
      })
      
      // Apply transforms (including flip) to the group, not the image node
      this.applyImageTransforms(node, layer, properties)
      
      // Re-apply scaling with new layer dimensions for proper clipping
      const img = imageNode.image() as HTMLImageElement
      if (img) {
        this.applyImageScaling(imageNode, img, layer, properties)
      }
      
      // Always clear any existing border radius first, then apply new one if needed
      this.clearBorderRadius(imageNode)
      
      // Always apply image filters to ensure proper clearing when reset to defaults
      this.applyImageFilters(imageNode, properties)
      
      // Force canvas redraw after all updates
      node.getLayer()?.batchDraw()
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
    placeholder?: Konva.Rect, 
    loadingText?: Konva.Text
  ): Promise<void> {
    try {
      let img = this.imageCache.get(src)
      
      if (!img) {
        img = new Image()
        img.crossOrigin = 'anonymous'
        
        await new Promise((resolve, reject) => {
          img!.onload = () => {
            resolve(img)
          }
          img!.onerror = (error) => {
            console.error('❌ Image failed to load:', src, error)
            reject(error)
          }
          img!.src = src
        })
        
        // Cache the loaded image
        this.imageCache.set(src, img)
      } else {
        // Using cached image
      }

      // Safely destroy placeholder elements if they exist
      if (placeholder) {
        placeholder.destroy()
      }
      if (loadingText) {
        loadingText.destroy()
      }
      
      const imageNode = new Konva.Image({
        id: layer.id.toString(), // Convert number ID to string for Konva
        x: 0,
        y: 0,
        image: img,
        width: layer.width,
        height: layer.height
      })

      // Store the real image dimensions for proper scaling and clipping
      const properties = layer.properties as ImageLayerProperties
      
      // Update layer properties with actual image dimensions if not already set
      if (!properties.originalWidth || !properties.originalHeight) {
        properties.originalWidth = img.width
        properties.originalHeight = img.height
      }

      this.applyImageScaling(imageNode, img, layer, properties)
      this.applyImageTransforms(group, layer, properties)

      // Always apply image filters to ensure proper clearing when reset to defaults
      this.applyImageFilters(imageNode, properties)

      group.add(imageNode)
      
      // Force canvas redraw
      const stage = group.getStage()
      if (stage) {
        stage.batchDraw()
      }

    } catch (error) {
      console.error('💥 Failed to load image:', { src, error, layerId: layer.id })
      if (loadingText) {
        loadingText.text('Failed to load')
        loadingText.fill('#e74c3c')
      }
      if (placeholder) {
        placeholder.fill('#fee')
      }
      
      // Force canvas redraw even on error to show error state
      const stage = group.getStage()
      if (stage) {
        stage.batchDraw()
      }
    }
  }

  private applyImageScaling(
    imageNode: Konva.Image, 
    img: HTMLImageElement, 
    layer: LayerNode,
    properties: ImageLayerProperties
  ): void {
    // Store original image dimensions for clipping calculations
    if (!imageNode.getAttr('originalWidth')) {
      imageNode.setAttr('originalWidth', img.width)
      imageNode.setAttr('originalHeight', img.height)
    }

    const originalWidth = imageNode.getAttr('originalWidth')
    const originalHeight = imageNode.getAttr('originalHeight')
    const containerWidth = layer.width
    const containerHeight = layer.height

    // Determine scaling mode
    const scaleMode = properties.scaleMode || 'fill' // Default to fill for Canva-like behavior
    
    if (scaleMode === 'fill') {
      // Fill mode: Scale image to fill container completely with proper cropping
      // This creates the Canva-style behavior where images fill the container
      
      // Set the display size to match container
      imageNode.setAttrs({
        width: containerWidth,
        height: containerHeight,
        x: 0,
        y: 0
      })

      // Calculate crop using Konva's proper crop method
      const crop = this.getCrop(
        { width: originalWidth, height: originalHeight },
        { width: containerWidth, height: containerHeight },
        properties.objectPosition || 'center-middle'
      )
      
      // Apply crop attributes
      imageNode.setAttrs({
        cropX: crop.cropX,
        cropY: crop.cropY,
        cropWidth: crop.cropWidth,
        cropHeight: crop.cropHeight
      })
    } else if (scaleMode === 'fit' || properties.preserveAspectRatio !== false) {
      // Fit/Contain mode: Scale to fit within container while preserving aspect ratio
      const imageRatio = originalWidth / originalHeight
      const containerRatio = containerWidth / containerHeight
      
      if (imageRatio > containerRatio) {
        const height = containerWidth / imageRatio
        imageNode.setAttrs({
          width: containerWidth,
          height: height,
          x: 0,
          y: (containerHeight - height) / 2
        })
      } else {
        const width = containerHeight * imageRatio
        imageNode.setAttrs({
          width: width,
          height: containerHeight,
          x: (containerWidth - width) / 2,
          y: 0
        })
      }
      
      // No clipping needed in fit mode - clear any existing crop
      imageNode.setAttrs({
        cropX: undefined,
        cropY: undefined,
        cropWidth: undefined,
        cropHeight: undefined
      })
    } else {
      // Stretch mode: stretch to fill container exactly (no aspect ratio preservation)
      imageNode.setAttrs({
        width: containerWidth,
        height: containerHeight,
        x: 0,
        y: 0
      })
      
      // No clipping needed in stretch mode - clear any existing crop
      imageNode.setAttrs({
        cropX: undefined,
        cropY: undefined,
        cropWidth: undefined,
        cropHeight: undefined
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
      properties.borderRadius !== undefined ||
      (properties.shadow && properties.shadow.enabled)
    )
  }

  private applyImageFilters(imageNode: Konva.Image, properties: ImageLayerProperties): void {
    const konvaFilters: any[] = []

    // Clear any previous cache to ensure filters are properly updated
    imageNode.clearCache()

    // Apply filters in the same order as backend for consistency
    // 1. Basic color adjustments
    if (properties.brightness !== undefined && properties.brightness !== 1) {
      konvaFilters.push(Konva.Filters.Brighten)
      // Convert 0-3 range to Konva's -1 to 1 range
      const brightnessValue = (properties.brightness - 1) * 0.5
      imageNode.brightness(Math.max(-1, Math.min(1, brightnessValue)))
    } else {
      // Reset brightness to default when not applied
      imageNode.brightness(0)
    }

    if (properties.contrast !== undefined && properties.contrast !== 1) {
      konvaFilters.push(Konva.Filters.Contrast)
      // Convert 0-3 range to Konva's contrast range (-100 to 100)
      const contrastValue = (properties.contrast - 1) * 50
      imageNode.contrast(Math.max(-100, Math.min(100, contrastValue)))
    } else {
      // Reset contrast to default when not applied
      imageNode.contrast(0)
    }

    if (properties.saturation !== undefined && properties.saturation !== 1) {
      konvaFilters.push(Konva.Filters.HSV)
      imageNode.saturation(Math.max(0, properties.saturation))
    } else if (properties.hue === undefined || properties.hue === 0) {
      // Only reset saturation if hue is also not being used (since they share HSV filter)
      imageNode.saturation(1)
    }

    // 2. Hue rotation
    if (properties.hue !== undefined && properties.hue !== 0) {
      if (!konvaFilters.includes(Konva.Filters.HSV)) {
        konvaFilters.push(Konva.Filters.HSV)
      }
      imageNode.hue(properties.hue)
    } else if (properties.saturation === undefined || properties.saturation === 1) {
      // Only reset hue if saturation is also not being used (since they share HSV filter)
      imageNode.hue(0)
    }

    // Ensure HSV filter is added if either saturation or hue is non-default
    if ((properties.saturation !== undefined && properties.saturation !== 1) || 
        (properties.hue !== undefined && properties.hue !== 0)) {
      if (!konvaFilters.includes(Konva.Filters.HSV)) {
        konvaFilters.push(Konva.Filters.HSV)
      }
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
    } else {
      // Reset blur to default when not applied
      imageNode.blurRadius(0)
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
    } else {
      // Clear shadow when not enabled
      imageNode.shadowColor('')
      imageNode.shadowBlur(0)
      imageNode.shadowOffset({ x: 0, y: 0 })
      imageNode.shadowOpacity(0)
    }

    // 6. Border radius (applied via clipping mask)
    if (properties.borderRadius !== undefined && properties.borderRadius > 0) {
      this.applyBorderRadius(imageNode, properties.borderRadius)
    } else if (properties.borderRadius === 0) {
      // Clear border radius by removing clipping
      this.clearBorderRadius(imageNode)
    }

    // Always set filters (even if empty array) to ensure proper clearing
    imageNode.filters(konvaFilters)
    
    // Apply cache for performance, but only if there are filters to apply
    if (konvaFilters.length > 0) {
      imageNode.cache()
    }
  }

  private applyBorderRadius(imageNode: Konva.Image, borderRadius: number): void {
    // Apply border radius by setting clipFunc on the group
    if (borderRadius > 0) {
      const group = imageNode.getParent() as Konva.Group
      if (group) {
        // Remove any existing clipping first
        this.clearBorderRadius(imageNode)
        
        // Set the clip function on the group
        group.clipFunc((ctx: any) => {
          const width = group.width()
          const height = group.height()
          ctx.beginPath()
          // Use roundRect if available, fallback to manual rounded rectangle
          if (ctx.roundRect) {
            ctx.roundRect(0, 0, width, height, borderRadius)
          } else {
            // Manual rounded rectangle for older browsers
            ctx.moveTo(borderRadius, 0)
            ctx.lineTo(width - borderRadius, 0)
            ctx.quadraticCurveTo(width, 0, width, borderRadius)
            ctx.lineTo(width, height - borderRadius)
            ctx.quadraticCurveTo(width, height, width - borderRadius, height)
            ctx.lineTo(borderRadius, height)
            ctx.quadraticCurveTo(0, height, 0, height - borderRadius)
            ctx.lineTo(0, borderRadius)
            ctx.quadraticCurveTo(0, 0, borderRadius, 0)
          }
          ctx.closePath()
        })
        
        // Mark that border radius is applied for cleanup
        group.setAttr('hasBorderRadius', true)
      }
    }
  }

  private clearBorderRadius(imageNode: Konva.Image): void {
    // Clear border radius by removing the clipping
    const group = imageNode.getParent() as Konva.Group
    if (group && group.getAttr('hasBorderRadius')) {
      // Remove the clip function
      group.clipFunc(null)
      group.setAttr('hasBorderRadius', false)
    }
  }

  private applyImageTransforms(
    group: Konva.Group,
    layer: LayerNode,
    properties: ImageLayerProperties
  ): void {
    let scaleX = 1
    let scaleY = 1
    let offsetX = 0
    let offsetY = 0

    // Handle flip transformations - apply to the group to avoid conflicts with image scaling
    if (properties.flipX) {
      scaleX = -1
      offsetX = layer.width // Use layer width, not image width
    }

    if (properties.flipY) {
      scaleY = -1
      offsetY = layer.height // Use layer height, not image height
    }

    // Always apply transformations to ensure proper reset when flips are disabled
    group.setAttrs({
      scaleX,
      scaleY,
      offsetX,
      offsetY
    })
  }

  private setupInteractions(group: Konva.Group, layer: LayerNode): void {
    group.on('dragstart', () => {
      const container = group.getStage()?.container()
      if (container) container.style.cursor = 'grabbing'
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

  // Function to calculate crop values from source image, its visible size and a crop strategy
  // Based on Konva.js documentation: https://konvajs.org/docs/sandbox/Scale_Image_To_Fit.html
  private getCrop(
    image: { width: number; height: number }, 
    size: { width: number; height: number }, 
    clipPosition: string = 'center-middle'
  ): { cropX: number; cropY: number; cropWidth: number; cropHeight: number } {
    const width = size.width
    const height = size.height
    const aspectRatio = width / height

    let newWidth: number
    let newHeight: number

    const imageRatio = image.width / image.height

    // Calculate the crop dimensions to fill the container
    if (aspectRatio >= imageRatio) {
      newWidth = image.width
      newHeight = image.width / aspectRatio
    } else {
      newWidth = image.height * aspectRatio
      newHeight = image.height
    }

    let x = 0
    let y = 0
    
    // Map our objectPosition values to Konva crop positions
    const position = this.mapObjectPositionToCropPosition(clipPosition)
    
    if (position === 'left-top') {
      x = 0
      y = 0
    } else if (position === 'left-middle') {
      x = 0
      y = (image.height - newHeight) / 2
    } else if (position === 'left-bottom') {
      x = 0
      y = image.height - newHeight
    } else if (position === 'center-top') {
      x = (image.width - newWidth) / 2
      y = 0
    } else if (position === 'center-middle') {
      x = (image.width - newWidth) / 2
      y = (image.height - newHeight) / 2
    } else if (position === 'center-bottom') {
      x = (image.width - newWidth) / 2
      y = image.height - newHeight
    } else if (position === 'right-top') {
      x = image.width - newWidth
      y = 0
    } else if (position === 'right-middle') {
      x = image.width - newWidth
      y = (image.height - newHeight) / 2
    } else if (position === 'right-bottom') {
      x = image.width - newWidth
      y = image.height - newHeight
    }

    return {
      cropX: x,
      cropY: y,
      cropWidth: newWidth,
      cropHeight: newHeight,
    }
  }

  // Map CSS object-position values to Konva crop position values
  private mapObjectPositionToCropPosition(objectPosition: string): string {
    switch (objectPosition) {
      case 'top':
        return 'center-top'
      case 'bottom':
        return 'center-bottom'
      case 'left':
        return 'left-middle'
      case 'right':
        return 'right-middle'
      case 'top left':
        return 'left-top'
      case 'top right':
        return 'right-top'
      case 'bottom left':
        return 'left-bottom'
      case 'bottom right':
        return 'right-bottom'
      case 'center':
      default:
        return 'center-middle'
    }
  }

  private needsImageReload(imageNode: Konva.Image | null, newSrc: string | undefined): boolean {
    if (!newSrc) return false
    if (!imageNode) return true
    
    const currentImg = imageNode.image() as HTMLImageElement
    if (!currentImg) return true
    
    // Normalize URLs for comparison to handle relative vs absolute URLs
    const normalizeUrl = (url: string): string => {
      try {
        // Create a URL object to normalize the URL
        const urlObj = new URL(url, window.location.origin)
        return urlObj.href
      } catch {
        // If URL construction fails, use original URL
        return url
      }
    }
    
    const currentSrc = normalizeUrl(currentImg.src)
    const nextSrc = normalizeUrl(newSrc)
    
    const isDifferent = currentSrc !== nextSrc
    
    return isDifferent
  }

  /**
   * Clear the image cache - useful for debugging image reload issues
   */
  clearImageCache(): void {
    this.imageCache.clear()
  }

  /**
   * Get cache statistics for debugging
   */
  getCacheStats(): { size: number; urls: string[] } {
    return {
      size: this.imageCache.size,
      urls: Array.from(this.imageCache.keys())
    }
  }
}

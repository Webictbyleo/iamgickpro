import Konva from 'konva'
import type { KonvaLayerRenderer, LayerNode } from '../types'
import type { Layer, SVGLayerProperties } from '../../../types'

interface SVGImageCache {
  originalSvg: string
  customizedSvg: string
  imageElement: HTMLImageElement
  originalViewBox: { x: number; y: number; width: number; height: number }
  lastCustomizations: string // JSON of customizations to detect changes
}

/**
 * SVG Layer Renderer - renders SVG vector graphics as Konva.Image with customization support
 * Applies fill/stroke customizations to the SVG before rendering as image for better performance
 */
export class SVGLayerRenderer implements KonvaLayerRenderer {
  private svgCache: Map<string, SVGImageCache> = new Map()

  canRender(layer: Layer): boolean {
    return layer.type === 'svg'
  }

  render(layer: LayerNode): Konva.Node {
    const properties = layer.properties as SVGLayerProperties
    
    const group = new Konva.Group({
      id: layer.id.toString(),
      x: layer.x,
      y: layer.y,
      width: layer.width,
      height: layer.height,
      draggable: true
    })

    // Create placeholder while processing SVG
    const placeholder = new Konva.Rect({
      width: layer.width,
      height: layer.height,
      fill: '#f8f9fa',
      stroke: '#e9ecef',
      strokeWidth: 1,
      dash: [3, 3]
    })

    const loadingText = new Konva.Text({
      x: 0,
      y: layer.height / 2 - 8,
      width: layer.width,
      height: 16,
      text: 'Loading SVG...',
      fontSize: 11,
      fontFamily: 'Arial',
      fill: '#6c757d',
      align: 'center'
    })

    group.add(placeholder)
    group.add(loadingText)

    // Load SVG if src is provided
    if (properties.src) {
      this.loadSVGAsImage(properties, layer, group, placeholder, loadingText)
    }

    this.setupInteractions(group, layer)
    return group
  }

  update(node: Konva.Node, layer: LayerNode): void {
    if (!(node instanceof Konva.Group)) return

    const properties = layer.properties as SVGLayerProperties

    console.log('🔄 SVGLayerRenderer: update called', {
      layerId: layer.id,
      dimensions: { width: layer.width, height: layer.height, x: layer.x, y: layer.y }
    })

    // Update group dimensions (x, y are handled by LayerManager)
    node.setAttrs({
      width: layer.width,
      height: layer.height
    })

    // Find the SVG image
    const svgImage = node.findOne('.svg-image') as Konva.Image
    
    // Check if we need to reload the SVG
    const needsSvgReload = this.needsSvgReload(svgImage, properties)
    
    if (properties.src && needsSvgReload) {
      console.log('� SVGLayerRenderer: SVG needs reload')
      // Remove existing image node if it exists
      if (svgImage) {
        svgImage.destroy()
      }
      
      const placeholder = node.findOne('Rect') as Konva.Rect
      const loadingText = node.findOne('Text') as Konva.Text
      
      // If no placeholder exists, create one for loading state
      if (!placeholder) {
        this.createPlaceholdersAndLoad(properties, layer, node)
      } else {
        placeholder.setAttrs({
          width: layer.width,
          height: layer.height
        })
        this.loadSVGAsImage(properties, layer, node, placeholder, loadingText)
      }
      return // Exit early since loadSVGAsImage will handle the rest
    }

    // Update dimensions if SVG image exists
    if (svgImage) {
      console.log('✅ SVGLayerRenderer: Found SVG image, updating size')
      // Update image size to match layer
      svgImage.setAttrs({
        width: layer.width,
        height: layer.height
      })

      // Check if customizations changed and re-render if needed
      this.updateCustomizations(properties, layer, node, svgImage)
      
      // Force canvas redraw after updates
      node.getLayer()?.batchDraw()
    }
  }

  destroy(node: Konva.Node): void {
    if (node instanceof Konva.Group) {
      // Clear cache for this node
      const nodeId = node.id()
      if (nodeId) {
        this.svgCache.delete(nodeId)
      }
      
      // Destroy all children recursively
      node.destroyChildren()
    }
    
    // Destroy the node itself
    node.destroy()
  }

  private async loadSVGAsImage(
    properties: SVGLayerProperties,
    layer: LayerNode,
    group: Konva.Group,
    placeholder: Konva.Rect,
    loadingText: Konva.Text
  ): Promise<void> {
    const cacheKey = layer.id.toString()

    try {
      console.log('📥 SVGLayerRenderer: Loading SVG from source')
      loadingText.text('Loading SVG...')
      
      // Fetch SVG content
      const response = await fetch(properties.src!)
      if (!response.ok) {
        throw new Error(`Failed to fetch SVG: ${response.statusText}`)
      }
      const originalSvg = await response.text()

      // Parse viewBox
      const viewBox = this.parseViewBox(originalSvg)
      
      // Apply customizations to SVG
      loadingText.text('Applying customizations...')
      const customizedSvg = this.applyCustomizationsToSvg(originalSvg, properties)
      
      // Create image element and load the customized SVG
      loadingText.text('Rendering image...')
      const imageElement = await this.createImageFromSvg(customizedSvg)
      
      const currentCustomizations = JSON.stringify({
        fillColors: properties.fillColors,
        strokeColors: properties.strokeColors,
        strokeWidths: properties.strokeWidths
      })
      
      // Cache the result
      this.svgCache.set(cacheKey, {
        originalSvg,
        customizedSvg,
        imageElement,
        originalViewBox: viewBox,
        lastCustomizations: currentCustomizations
      })

      // Create Konva image
      this.createKonvaImageFromCache(this.svgCache.get(cacheKey)!, layer, group, placeholder, loadingText)

    } catch (error) {
      console.error('Error loading SVG:', error)
      this.showErrorState(placeholder, loadingText)
    }
  }

  private createKonvaImageFromCache(
    cache: SVGImageCache,
    layer: LayerNode,
    group: Konva.Group,
    placeholder?: Konva.Rect,
    loadingText?: Konva.Text
  ): void {
    const svgImage = new Konva.Image({
      name: 'svg-image',
      image: cache.imageElement,
      width: layer.width,
      height: layer.height,
      x: 0,
      y: 0
    })

    // Remove placeholder elements if they exist
    if (placeholder) placeholder.destroy()
    if (loadingText) loadingText.destroy()

    // Add the image to the group
    group.add(svgImage)

    console.log('✅ SVGLayerRenderer: SVG image added to group')
  }

  private updateCustomizations(
    properties: SVGLayerProperties,
    layer: LayerNode,
    group: Konva.Group,
    svgImage: Konva.Image
  ): void {
    const cacheKey = layer.id.toString()
    const currentCustomizations = JSON.stringify({
      fillColors: properties.fillColors,
      strokeColors: properties.strokeColors,
      strokeWidths: properties.strokeWidths
    })

    const cached = this.svgCache.get(cacheKey)
    if (!cached || cached.lastCustomizations === currentCustomizations) {
      return // No changes needed
    }

    console.log('🎨 SVGLayerRenderer: Customizations changed, re-rendering')
    
    // Apply new customizations and update image
    const customizedSvg = this.applyCustomizationsToSvg(cached.originalSvg, properties)
    
    this.createImageFromSvg(customizedSvg).then(newImage => {
      // Update cache
      cached.customizedSvg = customizedSvg
      cached.imageElement = newImage
      cached.lastCustomizations = currentCustomizations
      
      // Update the Konva image
      svgImage.image(newImage)
      svgImage.getLayer()?.batchDraw()
    }).catch(error => {
      console.error('Error updating SVG customizations:', error)
    })
  }

  private parseViewBox(svgContent: string): { x: number; y: number; width: number; height: number } {
    const doc = new DOMParser().parseFromString(svgContent, 'image/svg+xml')
    const svgElement = doc.documentElement

    const viewBoxAttr = svgElement.getAttribute('viewBox')
    if (viewBoxAttr) {
      const [x, y, width, height] = viewBoxAttr.split(' ').map(Number)
      return { x, y, width, height }
    }

    // Fallback to width/height attributes
    const width = parseFloat(svgElement.getAttribute('width') || '100')
    const height = parseFloat(svgElement.getAttribute('height') || '100')
    return { x: 0, y: 0, width, height }
  }

  private applyCustomizationsToSvg(originalSvg: string, properties: SVGLayerProperties): string {
    let customizedSvg = originalSvg

    // Apply fill color customizations
    if (properties.fillColors) {
      for (const [selector, color] of Object.entries(properties.fillColors)) {
        if (selector.startsWith('#')) {
          // ID selector
          const regex = new RegExp(`(<[^>]*id=["']${selector.slice(1)}["'][^>]*)(fill=["'][^"']*["'])?`, 'g')
          customizedSvg = customizedSvg.replace(regex, `$1 fill="${color}"`)
        } else {
          // Class selector  
          const regex = new RegExp(`(<[^>]*class=["'][^"']*${selector}[^"']*["'][^>]*)(fill=["'][^"']*["'])?`, 'g')
          customizedSvg = customizedSvg.replace(regex, `$1 fill="${color}"`)
        }
      }
    }

    // Apply stroke color customizations
    if (properties.strokeColors) {
      for (const [selector, color] of Object.entries(properties.strokeColors)) {
        if (selector.startsWith('#')) {
          // ID selector
          const regex = new RegExp(`(<[^>]*id=["']${selector.slice(1)}["'][^>]*)(stroke=["'][^"']*["'])?`, 'g')
          customizedSvg = customizedSvg.replace(regex, `$1 stroke="${color}"`)
        } else {
          // Class selector
          const regex = new RegExp(`(<[^>]*class=["'][^"']*${selector}[^"']*["'][^>]*)(stroke=["'][^"']*["'])?`, 'g')
          customizedSvg = customizedSvg.replace(regex, `$1 stroke="${color}"`)
        }
      }
    }

    // Apply stroke width customizations
    if (properties.strokeWidths) {
      for (const [selector, width] of Object.entries(properties.strokeWidths)) {
        if (selector.startsWith('#')) {
          // ID selector
          const regex = new RegExp(`(<[^>]*id=["']${selector.slice(1)}["'][^>]*)(stroke-width=["'][^"']*["'])?`, 'g')
          customizedSvg = customizedSvg.replace(regex, `$1 stroke-width="${width}"`)
        } else {
          // Class selector
          const regex = new RegExp(`(<[^>]*class=["'][^"']*${selector}[^"']*["'][^>]*)(stroke-width=["'][^"']*["'])?`, 'g')
          customizedSvg = customizedSvg.replace(regex, `$1 stroke-width="${width}"`)
        }
      }
    }

    return customizedSvg
  }

  private createImageFromSvg(svgContent: string): Promise<HTMLImageElement> {
    return new Promise((resolve, reject) => {
      const image = new Image()
      
      image.onload = () => {
        URL.revokeObjectURL(image.src) // Clean up blob URL
        resolve(image)
      }
      
      image.onerror = () => {
        URL.revokeObjectURL(image.src) // Clean up blob URL
        reject(new Error('Failed to load SVG as image'))
      }

      // Create blob URL from SVG content
      const blob = new Blob([svgContent], { type: 'image/svg+xml' })
      image.src = URL.createObjectURL(blob)
    })
  }

  private showErrorState(placeholder: Konva.Rect, loadingText: Konva.Text): void {
    // Show error state
    loadingText.text('Failed to load SVG')
    loadingText.fill('#dc3545')
    
    // Keep placeholder with error styling
    placeholder.fill('#fee')
    placeholder.stroke('#dc3545')
  }

  private setupInteractions(group: Konva.Group, layer: LayerNode): void {
    group.on('dragstart', () => {
      // Emit event for drag start
      group.getStage()?.fire('layer:dragstart', { layer })
    })

    group.on('dragend', () => {
      // Update layer position
      const newX = group.x()
      const newY = group.y()
      
      // Emit event for position change
      group.getStage()?.fire('layer:positionchange', {
        layer,
        x: newX,
        y: newY
      })
    })

    group.on('click tap', () => {
      // Emit selection event
      group.getStage()?.fire('layer:select', { layer })
    })
  }

  private createPlaceholdersAndLoad(
    properties: SVGLayerProperties,
    layer: LayerNode,
    group: Konva.Group
  ): void {
    // Create placeholder while processing SVG
    const placeholder = new Konva.Rect({
      width: layer.width,
      height: layer.height,
      fill: '#f8f9fa',
      stroke: '#e9ecef',
      strokeWidth: 1,
      dash: [3, 3]
    })

    const loadingText = new Konva.Text({
      x: 0,
      y: layer.height / 2 - 8,
      width: layer.width,
      height: 16,
      text: 'Loading SVG...',
      fontSize: 11,
      fontFamily: 'Arial',
      fill: '#6c757d',
      align: 'center'
    })

    group.add(placeholder)
    group.add(loadingText)

    // Load SVG
    this.loadSVGAsImage(properties, layer, group, placeholder, loadingText)
  }

  private needsSvgReload(svgImage: Konva.Image | null, properties: SVGLayerProperties): boolean {
    if (!properties.src) return false
    if (!svgImage) return true
    
    // Check if customizations changed
    const cacheKey = (svgImage.getParent() as Konva.Group)?.id()
    if (!cacheKey) return true
    
    const cached = this.svgCache.get(cacheKey)
    if (!cached) return true
    
    const currentCustomizations = JSON.stringify({
      fillColors: properties.fillColors,
      strokeColors: properties.strokeColors,
      strokeWidths: properties.strokeWidths
    })
    
    // Reload if customizations changed
    return cached.lastCustomizations !== currentCustomizations
  }
}

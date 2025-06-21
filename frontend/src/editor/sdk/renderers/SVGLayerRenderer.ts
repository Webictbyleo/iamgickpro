import Konva from 'konva'
import type { KonvaLayerRenderer, LayerNode } from '../types'
import type { Layer, SVGLayerProperties } from '../../../types'

interface SVGPathElement {
  type: string // 'path', 'circle', 'rect', etc.
  data: string // path data for paths, or converted path data for other shapes
  originalFill?: string
  originalStroke?: string
  originalStrokeWidth?: number
  id?: string
  className?: string
}

interface SVGCache {
  originalSvg: string
  pathElements: SVGPathElement[]
  viewBox: { x: number; y: number; width: number; height: number }
  lastSrc: string
}

/**
 * SVG Layer Renderer - renders SVG vector graphics as Konva.Path elements for optimal performance
 * Directly manipulates Konva.Path properties without re-parsing or recreating elements
 */
export class SVGLayerRenderer implements KonvaLayerRenderer {
  private svgCache: Map<string, SVGCache> = new Map()

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

    // Load SVG if src is provided
    if (properties.src) {
      this.loadSVGAsPaths(properties, layer, group)
    } else {
      this.showNoSvgState(group, layer)
    }

    this.setupInteractions(group, layer)
    return group
  }

  update(node: Konva.Node, layer: LayerNode): void {
    if (!(node instanceof Konva.Group)) return

    const properties = layer.properties as SVGLayerProperties

    console.log('🔄 SVGLayerRenderer: update called', {
      layerId: layer.id,
      dimensions: { width: layer.width, height: layer.height, x: layer.x, y: layer.y },
      hasStyleChanges: !!(properties.fillColors || properties.strokeColors || properties.strokeWidths)
    })

    // Update group dimensions
    node.setAttrs({
      width: layer.width,
      height: layer.height
    })

    // Check if we need to reload SVG or just update properties
    if (this.needsSvgReload(node, properties)) {
      console.log('🔄 SVGLayerRenderer: SVG source changed, reloading paths from scratch')
      // Clear existing paths and reload
      this.clearPaths(node)
      if (properties.src) {
        this.loadSVGAsPaths(properties, layer, node)
      } else {
        this.showNoSvgState(node, layer)
      }
    } else {
      console.log('⚡ SVGLayerRenderer: Fast path update - only updating existing Konva.Path properties')
      // Just update path properties directly - no re-parsing needed!
      this.updatePathProperties(node, layer, properties)
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

  private async loadSVGAsPaths(
    properties: SVGLayerProperties,
    layer: LayerNode,
    group: Konva.Group
  ): Promise<void> {
    // Use layer ID as primary cache key for consistency across different Group instances
    const primaryCacheKey = layer.id.toString()
    const groupId = group.id()

    try {
      console.log('📥 SVGLayerRenderer: Loading SVG and parsing to paths', {
        layerId: layer.id,
        src: properties.src,
        primaryCacheKey,
        groupId,
        cacheExists: this.svgCache.has(primaryCacheKey)
      })
      
      // Show loading state
      this.showLoadingState(group, layer)
      
      // Check cache with primary key (layer ID)
      let cache = this.svgCache.get(primaryCacheKey)
      if (!cache || cache.lastSrc !== properties.src) {
        console.log('🔄 SVGLayerRenderer: Cache miss or source changed, fetching and parsing SVG', {
          cacheExists: !!cache,
          cachedSrc: cache?.lastSrc,
          newSrc: properties.src
        })
        
        // Fetch and parse SVG
        const response = await fetch(properties.src!)
        if (!response.ok) {
          throw new Error(`Failed to fetch SVG: ${response.statusText}`)
        }
        const svgContent = await response.text()

        // Parse SVG to extract path elements
        const pathElements = this.parseSvgToPaths(svgContent)
        const viewBox = this.parseViewBox(svgContent)
        
        // Update cache
        cache = {
          originalSvg: svgContent,
          pathElements,
          viewBox,
          lastSrc: properties.src!
        }
        this.svgCache.set(primaryCacheKey, cache)
        
        // Also set cache with group ID if different (for compatibility)
        if (groupId && groupId !== primaryCacheKey) {
          this.svgCache.set(groupId, cache)
        }
        
        console.log('💾 SVGLayerRenderer: Updated cache for layer', primaryCacheKey)
      } else {
        console.log('⚡ SVGLayerRenderer: Using cached SVG data', {
          primaryCacheKey,
          pathCount: cache.pathElements.length
        })
      }

      // Create Konva paths from parsed elements
      this.createKonvaPathsFromCache(cache, layer, group, properties)

      // Update layer properties with parsed elements for toolbar integration
      this.updateLayerPropertiesWithElements(layer, cache.pathElements)

    } catch (error) {
      console.error('Error loading SVG:', error)
      this.showErrorState(group, layer)
    }
  }

  private parseSvgToPaths(svgContent: string): SVGPathElement[] {
    const parser = new DOMParser()
    const doc = parser.parseFromString(svgContent, 'image/svg+xml')
    const pathElements: SVGPathElement[] = []

    // Helper function to extract attributes
    const getAttr = (element: Element, attr: string): string | undefined => {
      return element.getAttribute(attr) || undefined
    }

    // Parse different SVG elements
    const elements = doc.querySelectorAll('path, circle, rect, ellipse, line, polyline, polygon')
    
    elements.forEach((element, index) => {
      const type = element.tagName.toLowerCase()
      let pathData = ''

      switch (type) {
        case 'path':
          pathData = element.getAttribute('d') || ''
          break
        case 'circle':
          pathData = this.circleToPath(element)
          break
        case 'rect':
          pathData = this.rectToPath(element)
          break
        case 'ellipse':
          pathData = this.ellipseToPath(element)
          break
        case 'line':
          pathData = this.lineToPath(element)
          break
        case 'polyline':
        case 'polygon':
          pathData = this.polyToPath(element)
          break
      }

      if (pathData) {
        pathElements.push({
          type,
          data: pathData,
          originalFill: getAttr(element, 'fill'),
          originalStroke: getAttr(element, 'stroke'),
          originalStrokeWidth: getAttr(element, 'stroke-width') ? parseFloat(getAttr(element, 'stroke-width')!) : undefined,
          id: getAttr(element, 'id'),
          className: getAttr(element, 'class')
        })
      }
    })

    console.log(`📋 SVGLayerRenderer: Parsed ${pathElements.length} path elements`)
    return pathElements
  }

  private createKonvaPathsFromCache(
    cache: SVGCache,
    layer: LayerNode,
    group: Konva.Group,
    properties: SVGLayerProperties
  ): void {
    console.log('🎯 SVGLayerRenderer: Creating Konva paths from cache', {
      layerId: layer.id,
      pathCount: cache.pathElements.length,
      groupId: group.id(),
      existingChildren: group.getChildren().length
    })
    
    // Clear any existing content
    this.clearPaths(group)

    const scaleX = layer.width / cache.viewBox.width
    const scaleY = layer.height / cache.viewBox.height

    cache.pathElements.forEach((pathElement, index) => {
      const pathConfig: Konva.PathConfig = {
        name: 'svg-path',
        data: pathElement.data,
        scaleX,
        scaleY,
        x: -cache.viewBox.x * scaleX,
        y: -cache.viewBox.y * scaleY,
        // Apply customizations or use original values
        fill: this.getCustomFill(pathElement, properties) || pathElement.originalFill || 'black',
        stroke: this.getCustomStroke(pathElement, properties) || pathElement.originalStroke || 'none',
        strokeWidth: this.getCustomStrokeWidth(pathElement, properties) || pathElement.originalStrokeWidth || 0
      }

      const konvaPath = new Konva.Path(pathConfig)
      
      // Store element info for property updates
      konvaPath.setAttr('svgElement', pathElement)
      
      group.add(konvaPath)
    })

    console.log(`✅ SVGLayerRenderer: Created ${cache.pathElements.length} Konva paths for layer ${layer.id}`)
  }

  private updatePathProperties(
    group: Konva.Group,
    layer: LayerNode,
    properties: SVGLayerProperties
  ): void {
    const paths = group.find('.svg-path') as Konva.Path[]
    
    if (paths.length === 0) {
      console.warn('⚠️ SVGLayerRenderer: No paths found to update properties')
      return
    }

    let changesApplied = 0
    const startTime = performance.now()

    paths.forEach(path => {
      const svgElement = path.getAttr('svgElement') as SVGPathElement
      if (!svgElement) return

      let pathChanged = false

      // Update fill only if it would change
      const newFill = this.getCustomFill(svgElement, properties) || svgElement.originalFill || 'black'
      if (path.fill() !== newFill) {
        path.fill(newFill)
        pathChanged = true
      }

      // Update stroke only if it would change
      const newStroke = this.getCustomStroke(svgElement, properties) || svgElement.originalStroke || 'none'
      if (path.stroke() !== newStroke) {
        path.stroke(newStroke)
        pathChanged = true
      }

      // Update stroke width only if it would change
      const newStrokeWidth = this.getCustomStrokeWidth(svgElement, properties) || svgElement.originalStrokeWidth || 0
      if (path.strokeWidth() !== newStrokeWidth) {
        path.strokeWidth(newStrokeWidth)
        pathChanged = true
      }

      if (pathChanged) {
        changesApplied++
      }
    })

    // Only redraw if changes were actually applied
    if (changesApplied > 0) {
      group.getLayer()?.batchDraw()
      const endTime = performance.now()
      console.log(`⚡ SVGLayerRenderer: Fast property update completed - ${changesApplied}/${paths.length} paths updated in ${(endTime - startTime).toFixed(2)}ms`)
    } else {
      console.log(`⚡ SVGLayerRenderer: No property changes needed for ${paths.length} paths`)
    }
  }

  private getCustomFill(pathElement: SVGPathElement, properties: SVGLayerProperties): string | undefined {
    if (!properties.fillColors) return undefined
    
    // Check global style first (new simplified approach)
    if (properties.fillColors['global']) {
      return properties.fillColors['global']
    }
    
    // Check by element type
    if (properties.fillColors[pathElement.type]) {
      return properties.fillColors[pathElement.type]
    }
    
    // Check by ID
    if (pathElement.id && properties.fillColors[`#${pathElement.id}`]) {
      return properties.fillColors[`#${pathElement.id}`]
    }
    
    // Check by class
    if (pathElement.className && properties.fillColors[`.${pathElement.className}`]) {
      return properties.fillColors[`.${pathElement.className}`]
    }
    
    return undefined
  }

  private getCustomStroke(pathElement: SVGPathElement, properties: SVGLayerProperties): string | undefined {
    if (!properties.strokeColors) return undefined
    
    // Check global style first (new simplified approach)
    if (properties.strokeColors['global']) {
      return properties.strokeColors['global']
    }
    
    // Check by element type
    if (properties.strokeColors[pathElement.type]) {
      return properties.strokeColors[pathElement.type]
    }
    
    // Check by ID
    if (pathElement.id && properties.strokeColors[`#${pathElement.id}`]) {
      return properties.strokeColors[`#${pathElement.id}`]
    }
    
    // Check by class
    if (pathElement.className && properties.strokeColors[`.${pathElement.className}`]) {
      return properties.strokeColors[`.${pathElement.className}`]
    }
    
    return undefined
  }

  private getCustomStrokeWidth(pathElement: SVGPathElement, properties: SVGLayerProperties): number | undefined {
    if (!properties.strokeWidths) return undefined
    
    // Check global style first (new simplified approach)
    if (properties.strokeWidths['global'] !== undefined) {
      return properties.strokeWidths['global']
    }
    
    // Check by element type
    if (properties.strokeWidths[pathElement.type] !== undefined) {
      return properties.strokeWidths[pathElement.type]
    }
    
    // Check by ID
    if (pathElement.id && properties.strokeWidths[`#${pathElement.id}`] !== undefined) {
      return properties.strokeWidths[`#${pathElement.id}`]
    }
    
    // Check by class
    if (pathElement.className && properties.strokeWidths[`.${pathElement.className}`] !== undefined) {
      return properties.strokeWidths[`.${pathElement.className}`]
    }
    
    return undefined
  }

  /**
   * Get parsed SVG elements for a specific layer (for toolbar integration)
   */
  getParsedElements(layerId: number): SVGPathElement[] {
    const cache = this.svgCache.get(layerId.toString())
    return cache ? cache.pathElements : []
  }

  /**
   * Get SVG elements formatted for toolbar consumption
   */
  getToolbarElements(layerId: number): Array<{
    type: string
    id?: string
    className?: string
    originalFill?: string
    originalStroke?: string
    originalStrokeWidth?: number
  }> {
    const pathElements = this.getParsedElements(layerId)
    return pathElements.map(element => ({
      type: element.type,
      id: element.id,
      className: element.className,
      originalFill: element.originalFill,
      originalStroke: element.originalStroke,
      originalStrokeWidth: element.originalStrokeWidth
    }))
  }

  /**
   * Update layer properties with parsed SVG elements for toolbar integration
   */
  private updateLayerPropertiesWithElements(layer: LayerNode, pathElements: SVGPathElement[]): void {
    const properties = layer.properties as SVGLayerProperties
    
    // Convert pathElements to the format expected by the toolbar
    const svgElements = pathElements.map(element => ({
      type: element.type,
      id: element.id,
      className: element.className,
      originalFill: element.originalFill,
      originalStroke: element.originalStroke,
      originalStrokeWidth: element.originalStrokeWidth
    }))

    // Update the layer properties
    properties.svgElements = svgElements

    // Notify LayerManager if stage has layerEmitter
    const stage = layer.konvaNode?.getStage()
    const layerEmitter = (stage as any)?.layerEmitter
    if (layerEmitter && typeof layerEmitter.emit === 'function') {
      // Convert LayerNode back to Layer for emission
      const layerData = {
        id: layer.id,
        type: layer.type,
        x: layer.x,
        y: layer.y,
        width: layer.width,
        height: layer.height,
        rotation: layer.rotation,
        opacity: layer.opacity,
        visible: layer.visible,
        zIndex: layer.zIndex,
        properties: { ...properties }
      }
      layerEmitter.emit('layer:updated', layerData)
    }
  }

  // SVG shape to path conversion methods
  private circleToPath(element: Element): string {
    const cx = parseFloat(element.getAttribute('cx') || '0')
    const cy = parseFloat(element.getAttribute('cy') || '0')
    const r = parseFloat(element.getAttribute('r') || '0')
    
    return `M ${cx-r},${cy} A ${r},${r} 0 1,0 ${cx+r},${cy} A ${r},${r} 0 1,0 ${cx-r},${cy} Z`
  }

  private rectToPath(element: Element): string {
    const x = parseFloat(element.getAttribute('x') || '0')
    const y = parseFloat(element.getAttribute('y') || '0')
    const width = parseFloat(element.getAttribute('width') || '0')
    const height = parseFloat(element.getAttribute('height') || '0')
    const rx = parseFloat(element.getAttribute('rx') || '0')
    const ry = parseFloat(element.getAttribute('ry') || '0')
    
    if (rx || ry) {
      // Rounded rectangle
      const rxVal = rx || ry
      const ryVal = ry || rx
      return `M ${x+rxVal},${y} L ${x+width-rxVal},${y} Q ${x+width},${y} ${x+width},${y+ryVal} L ${x+width},${y+height-ryVal} Q ${x+width},${y+height} ${x+width-rxVal},${y+height} L ${x+rxVal},${y+height} Q ${x},${y+height} ${x},${y+height-ryVal} L ${x},${y+ryVal} Q ${x},${y} ${x+rxVal},${y} Z`
    } else {
      // Regular rectangle
      return `M ${x},${y} L ${x+width},${y} L ${x+width},${y+height} L ${x},${y+height} Z`
    }
  }

  private ellipseToPath(element: Element): string {
    const cx = parseFloat(element.getAttribute('cx') || '0')
    const cy = parseFloat(element.getAttribute('cy') || '0')
    const rx = parseFloat(element.getAttribute('rx') || '0')
    const ry = parseFloat(element.getAttribute('ry') || '0')
    
    return `M ${cx-rx},${cy} A ${rx},${ry} 0 1,0 ${cx+rx},${cy} A ${rx},${ry} 0 1,0 ${cx-rx},${cy} Z`
  }

  private lineToPath(element: Element): string {
    const x1 = parseFloat(element.getAttribute('x1') || '0')
    const y1 = parseFloat(element.getAttribute('y1') || '0')
    const x2 = parseFloat(element.getAttribute('x2') || '0')
    const y2 = parseFloat(element.getAttribute('y2') || '0')
    
    return `M ${x1},${y1} L ${x2},${y2}`
  }

  private polyToPath(element: Element): string {
    const points = element.getAttribute('points') || ''
    const coords = points.trim().split(/[\s,]+/).map(parseFloat)
    
    if (coords.length < 4) return ''
    
    let path = `M ${coords[0]},${coords[1]}`
    for (let i = 2; i < coords.length; i += 2) {
      path += ` L ${coords[i]},${coords[i+1]}`
    }
    
    // Close path for polygon
    if (element.tagName.toLowerCase() === 'polygon') {
      path += ' Z'
    }
    
    return path
  }

  private parseViewBox(svgContent: string): { x: number; y: number; width: number; height: number } {
    const parser = new DOMParser()
    const doc = parser.parseFromString(svgContent, 'image/svg+xml')
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

  private needsSvgReload(group: Konva.Group, properties: SVGLayerProperties): boolean {
    // Use layer ID from group ID as primary cache key
    const groupId = group.id()
    
    // Extract layer ID from group ID if it follows the pattern
    const layerId = groupId // Assuming group ID is set to layer ID
    
    if (!layerId) {
      console.log('🔍 SVGLayerRenderer: needsSvgReload = true (no layer ID found)')
      return true
    }
    
    // Check cache with layer ID
    const cache = this.svgCache.get(layerId)
    
    if (!cache) {
      console.log('🔍 SVGLayerRenderer: needsSvgReload = true (no cache found)', {
        layerId,
        cacheKeys: Array.from(this.svgCache.keys())
      })
      return true
    }
    
    const needsReload = cache.lastSrc !== properties.src
    console.log('🔍 SVGLayerRenderer: needsSvgReload =', needsReload, {
      cachedSrc: cache.lastSrc,
      currentSrc: properties.src,
      layerId,
      groupId
    })
    
    return needsReload
  }

  private clearPaths(group: Konva.Group): void {
    group.destroyChildren()
  }

  private showLoadingState(group: Konva.Group, layer: LayerNode): void {
    this.clearPaths(group)
    
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
  }

  private showErrorState(group: Konva.Group, layer: LayerNode): void {
    this.clearPaths(group)
    
    const placeholder = new Konva.Rect({
      width: layer.width,
      height: layer.height,
      fill: '#fee',
      stroke: '#dc3545',
      strokeWidth: 1,
      dash: [3, 3]
    })

    const errorText = new Konva.Text({
      x: 0,
      y: layer.height / 2 - 8,
      width: layer.width,
      height: 16,
      text: 'Failed to load SVG',
      fontSize: 11,
      fontFamily: 'Arial',
      fill: '#dc3545',
      align: 'center'
    })

    group.add(placeholder)
    group.add(errorText)
  }

  private showNoSvgState(group: Konva.Group, layer: LayerNode): void {
    this.clearPaths(group)
    
    const placeholder = new Konva.Rect({
      width: layer.width,
      height: layer.height,
      fill: '#f8f9fa',
      stroke: '#e9ecef',
      strokeWidth: 1,
      dash: [5, 5]
    })

    const noSvgText = new Konva.Text({
      x: 0,
      y: layer.height / 2 - 8,
      width: layer.width,
      height: 16,
      text: 'No SVG source',
      fontSize: 11,
      fontFamily: 'Arial',
      fill: '#6c757d',
      align: 'center'
    })

    group.add(placeholder)
    group.add(noSvgText)
  }

  private setupInteractions(group: Konva.Group, layer: LayerNode): void {
    group.on('dragstart', () => {
      group.getStage()?.fire('layer:dragstart', { layer })
    })

    group.on('dragend', () => {
      const newX = group.x()
      const newY = group.y()
      
      group.getStage()?.fire('layer:positionchange', {
        layer,
        x: newX,
        y: newY
      })
    })

    group.on('click tap', () => {
      group.getStage()?.fire('layer:select', { layer })
    })
  }

  /**
   * Get cache statistics for debugging performance
   */
  getCacheStats(): { totalCached: number; cacheKeys: string[] } {
    return {
      totalCached: this.svgCache.size,
      cacheKeys: Array.from(this.svgCache.keys())
    }
  }

  /**
   * Clear cache for specific layer (useful for debugging)
   */
  clearCacheForLayer(layerId: number): boolean {
    const key = layerId.toString()
    const existed = this.svgCache.has(key)
    this.svgCache.delete(key)
    console.log(`🗑️ SVGLayerRenderer: Cache cleared for layer ${layerId}, existed: ${existed}`)
    return existed
  }
}

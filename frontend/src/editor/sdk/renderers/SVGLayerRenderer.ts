import Konva from 'konva'
import type { KonvaLayerRenderer, LayerNode } from '../types'
import type { Layer, SVGLayerProperties } from '../../../types'

/**
 * SVG Layer Renderer - renders SVG vector graphics using Konva.Group with HTML/DOM elements
 * Provides ability to modify individual fill colors and other properties of vector elements
 */
export class SVGLayerRenderer implements KonvaLayerRenderer {
  private svgCache: Map<string, {
    elements: Element[]
    originalSvg: string
  }> = new Map()

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
      text: 'Processing SVG...',
      fontSize: 11,
      fontFamily: 'Arial',
      fill: '#6c757d',
      align: 'center'
    })

    group.add(placeholder)
    group.add(loadingText)

    // Process SVG if content is provided
    if (properties.svgContent) {
      this.processSVG(properties.svgContent, layer, group, placeholder, loadingText)
    }

    this.setupInteractions(group, layer)
    return group
  }

  update(node: Konva.Node, layer: LayerNode): void {
    if (!(node instanceof Konva.Group)) return

    const properties = layer.properties as SVGLayerProperties

    // Update group dimensions
    node.setAttrs({
      width: layer.width,
      height: layer.height
    })

    // Check if SVG content has changed
    const existingSvgNode = node.findOne('.svg-content') as Konva.Group
    if (!existingSvgNode && properties.svgContent) {
      // SVG content was added
      const placeholder = node.findOne('Rect') as Konva.Rect
      const loadingText = node.findOne('Text') as Konva.Text
      this.processSVG(properties.svgContent, layer, node, placeholder, loadingText)
    } else if (existingSvgNode && properties.svgContent) {
      // Update existing SVG with new customizations
      this.updateSVGCustomizations(existingSvgNode, properties)
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

  private processSVG(
    svgContent: string,
    layer: LayerNode,
    group: Konva.Group,
    placeholder: Konva.Rect,
    loadingText: Konva.Text
  ): void {
    try {
      // Parse SVG content
      const parser = new DOMParser()
      const svgDoc = parser.parseFromString(svgContent, 'image/svg+xml')
      const svgElement = svgDoc.documentElement

      if (svgElement.tagName !== 'svg') {
        throw new Error('Invalid SVG content')
      }

      // Extract viewBox and dimensions
      const viewBox = svgElement.getAttribute('viewBox')
      const svgWidth = parseFloat(svgElement.getAttribute('width') || '100')
      const svgHeight = parseFloat(svgElement.getAttribute('height') || '100')

      // Create a group to hold SVG elements
      const svgGroup = new Konva.Group({
        name: 'svg-content',
        width: layer.width,
        height: layer.height
      })

      // Convert SVG elements to Konva shapes
      this.convertSVGElementsToKonva(svgElement, svgGroup, layer, {
        scaleX: layer.width / svgWidth,
        scaleY: layer.height / svgHeight,
        fillColors: (layer.properties as SVGLayerProperties).fillColors || {},
        strokeColors: (layer.properties as SVGLayerProperties).strokeColors || {},
        strokeWidths: (layer.properties as SVGLayerProperties).strokeWidths || {}
      })

      // Remove placeholder and add SVG content
      placeholder.destroy()
      loadingText.destroy()
      group.add(svgGroup)

      // Cache the processed SVG
      this.svgCache.set(layer.id.toString(), {
        elements: Array.from(svgElement.querySelectorAll('*')),
        originalSvg: svgContent
      })

    } catch (error) {
      console.error('Failed to process SVG:', error)
      loadingText.text('Failed to load SVG')
    }
  }

  private convertSVGElementsToKonva(
    element: Element,
    parentGroup: Konva.Group,
    layer: LayerNode,
    context: {
      scaleX: number
      scaleY: number
      fillColors: Record<string, string>
      strokeColors: Record<string, string>
      strokeWidths: Record<string, number>
    }
  ): void {
    const children = Array.from(element.children)
    
    for (const child of children) {
      const tagName = child.tagName.toLowerCase()
      
      switch (tagName) {
        case 'rect':
          this.createKonvaRect(child, parentGroup, context)
          break
        case 'circle':
          this.createKonvaCircle(child, parentGroup, context)
          break
        case 'ellipse':
          this.createKonvaEllipse(child, parentGroup, context)
          break
        case 'path':
          this.createKonvaPath(child, parentGroup, context)
          break
        case 'line':
          this.createKonvaLine(child, parentGroup, context)
          break
        case 'polyline':
        case 'polygon':
          this.createKonvaPolygon(child, parentGroup, context)
          break
        case 'g':
          // Create a group for nested elements
          const group = new Konva.Group()
          parentGroup.add(group)
          this.convertSVGElementsToKonva(child, group, layer, context)
          break
        default:
          // For unsupported elements, try to convert to path if possible
          console.warn(`Unsupported SVG element: ${tagName}`)
          break
      }
    }
  }

  private createKonvaRect(element: Element, parent: Konva.Group, context: any): void {
    const x = parseFloat(element.getAttribute('x') || '0') * context.scaleX
    const y = parseFloat(element.getAttribute('y') || '0') * context.scaleY
    const width = parseFloat(element.getAttribute('width') || '0') * context.scaleX
    const height = parseFloat(element.getAttribute('height') || '0') * context.scaleY
    
    const rect = new Konva.Rect({
      x,
      y,
      width,
      height,
      fill: this.getCustomizedFill(element, context),
      stroke: this.getCustomizedStroke(element, context),
      strokeWidth: this.getCustomizedStrokeWidth(element, context)
    })
    
    parent.add(rect)
  }

  private createKonvaCircle(element: Element, parent: Konva.Group, context: any): void {
    const cx = parseFloat(element.getAttribute('cx') || '0') * context.scaleX
    const cy = parseFloat(element.getAttribute('cy') || '0') * context.scaleY
    const r = parseFloat(element.getAttribute('r') || '0') * Math.min(context.scaleX, context.scaleY)
    
    const circle = new Konva.Circle({
      x: cx,
      y: cy,
      radius: r,
      fill: this.getCustomizedFill(element, context),
      stroke: this.getCustomizedStroke(element, context),
      strokeWidth: this.getCustomizedStrokeWidth(element, context)
    })
    
    parent.add(circle)
  }

  private createKonvaEllipse(element: Element, parent: Konva.Group, context: any): void {
    const cx = parseFloat(element.getAttribute('cx') || '0') * context.scaleX
    const cy = parseFloat(element.getAttribute('cy') || '0') * context.scaleY
    const rx = parseFloat(element.getAttribute('rx') || '0') * context.scaleX
    const ry = parseFloat(element.getAttribute('ry') || '0') * context.scaleY
    
    const ellipse = new Konva.Ellipse({
      x: cx,
      y: cy,
      radiusX: rx,
      radiusY: ry,
      fill: this.getCustomizedFill(element, context),
      stroke: this.getCustomizedStroke(element, context),
      strokeWidth: this.getCustomizedStrokeWidth(element, context)
    })
    
    parent.add(ellipse)
  }

  private createKonvaPath(element: Element, parent: Konva.Group, context: any): void {
    const d = element.getAttribute('d')
    if (!d) return
    
    // Scale the path data
    const scaledPath = this.scaleSVGPath(d, context.scaleX, context.scaleY)
    
    const path = new Konva.Path({
      data: scaledPath,
      fill: this.getCustomizedFill(element, context),
      stroke: this.getCustomizedStroke(element, context),
      strokeWidth: this.getCustomizedStrokeWidth(element, context)
    })
    
    parent.add(path)
  }

  private createKonvaLine(element: Element, parent: Konva.Group, context: any): void {
    const x1 = parseFloat(element.getAttribute('x1') || '0') * context.scaleX
    const y1 = parseFloat(element.getAttribute('y1') || '0') * context.scaleY
    const x2 = parseFloat(element.getAttribute('x2') || '0') * context.scaleX
    const y2 = parseFloat(element.getAttribute('y2') || '0') * context.scaleY
    
    const line = new Konva.Line({
      points: [x1, y1, x2, y2],
      stroke: this.getCustomizedStroke(element, context),
      strokeWidth: this.getCustomizedStrokeWidth(element, context)
    })
    
    parent.add(line)
  }

  private createKonvaPolygon(element: Element, parent: Konva.Group, context: any): void {
    const pointsAttr = element.getAttribute('points')
    if (!pointsAttr) return
    
    const points = pointsAttr.trim().split(/[\s,]+/).map(Number)
    const scaledPoints = points.map((point, index) => 
      index % 2 === 0 ? point * context.scaleX : point * context.scaleY
    )
    
    const polygon = new Konva.Line({
      points: scaledPoints,
      closed: element.tagName.toLowerCase() === 'polygon',
      fill: this.getCustomizedFill(element, context),
      stroke: this.getCustomizedStroke(element, context),
      strokeWidth: this.getCustomizedStrokeWidth(element, context)
    })
    
    parent.add(polygon)
  }

  private getCustomizedFill(element: Element, context: any): string {
    const id = element.getAttribute('id')
    const className = element.getAttribute('class')
    
    // Check for customized fill colors
    if (id && context.fillColors[id]) {
      return context.fillColors[id]
    }
    if (className && context.fillColors[className]) {
      return context.fillColors[className]
    }
    
    // Return original fill or default
    return element.getAttribute('fill') || '#000000'
  }

  private getCustomizedStroke(element: Element, context: any): string {
    const id = element.getAttribute('id')
    const className = element.getAttribute('class')
    
    // Check for customized stroke colors
    if (id && context.strokeColors[id]) {
      return context.strokeColors[id]
    }
    if (className && context.strokeColors[className]) {
      return context.strokeColors[className]
    }
    
    // Return original stroke or default
    return element.getAttribute('stroke') || 'transparent'
  }

  private getCustomizedStrokeWidth(element: Element, context: any): number {
    const id = element.getAttribute('id')
    const className = element.getAttribute('class')
    
    // Check for customized stroke widths
    if (id && context.strokeWidths[id]) {
      return context.strokeWidths[id]
    }
    if (className && context.strokeWidths[className]) {
      return context.strokeWidths[className]
    }
    
    // Return original stroke width or default
    return parseFloat(element.getAttribute('stroke-width') || '1')
  }

  private scaleSVGPath(pathData: string, scaleX: number, scaleY: number): string {
    // This is a simplified path scaling - in a production environment,
    // you'd want to use a proper SVG path parser and transformer
    return pathData.replace(/([ML])\s*(\d+(?:\.\d+)?)\s*,?\s*(\d+(?:\.\d+)?)/g, 
      (match, command, x, y) => {
        const scaledX = parseFloat(x) * scaleX
        const scaledY = parseFloat(y) * scaleY
        return `${command} ${scaledX} ${scaledY}`
      }
    )
  }

  private updateSVGCustomizations(svgGroup: Konva.Group, properties: SVGLayerProperties): void {
    // Update fill colors, stroke colors, and stroke widths based on properties
    const shapes = svgGroup.find('Shape')
    
    shapes.forEach(shape => {
      const konvaShape = shape as Konva.Shape
      const id = konvaShape.id()
      const className = konvaShape.className
      
      // Update fill colors
      if (properties.fillColors) {
        if (id && properties.fillColors[id]) {
          konvaShape.fill(properties.fillColors[id])
        }
        if (className && properties.fillColors[className]) {
          konvaShape.fill(properties.fillColors[className])
        }
      }
      
      // Update stroke colors
      if (properties.strokeColors) {
        if (id && properties.strokeColors[id]) {
          konvaShape.stroke(properties.strokeColors[id])
        }
        if (className && properties.strokeColors[className]) {
          konvaShape.stroke(properties.strokeColors[className])
        }
      }
      
      // Update stroke widths
      if (properties.strokeWidths) {
        if (id && properties.strokeWidths[id]) {
          konvaShape.strokeWidth(properties.strokeWidths[id])
        }
        if (className && properties.strokeWidths[className]) {
          konvaShape.strokeWidth(properties.strokeWidths[className])
        }
      }
    })
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
}

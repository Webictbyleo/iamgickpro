import Konva from 'konva'
import type { KonvaLayerRenderer, LayerNode } from '../types'
import type { Layer, ShapeLayerProperties, ShapeFillConfig } from '../../../types'

/**
 * Shape Layer Renderer - renders shape layers using various Konva shapes
 * Matches backend ShapeLayerRenderer properties and validation
 */
export class ShapeLayerRenderer implements KonvaLayerRenderer {
  canRender(layer: Layer): boolean {
    return layer.type === 'shape'
  }

  render(layer: LayerNode): Konva.Node {
    const props = this.getShapeProperties(layer.properties as ShapeLayerProperties)
    const shapeType = this.sanitizeShapeType(props.shapeType)
    
    let shape: Konva.Shape

    switch (shapeType) {
      case 'rectangle':
        shape = this.createRectangle(layer, props)
        break
      case 'circle':
        shape = this.createCircle(layer, props)
        break
      case 'ellipse':
        shape = this.createEllipse(layer, props)
        break
      case 'triangle':
        shape = this.createTriangle(layer, props)
        break
      case 'polygon':
        shape = this.createPolygon(layer, props)
        break
      case 'star':
        shape = this.createStar(layer, props)
        break
      case 'line':
        shape = this.createLine(layer, props)
        break
      case 'arrow':
        shape = this.createArrow(layer, props)
        break
      default:
        shape = this.createRectangle(layer, props)
    }

    this.applyCommonProperties(shape, layer, props)
    this.setupInteractions(shape, layer)
    
    return shape
  }

  update(node: Konva.Node, layer: LayerNode): void {
    if (!(node instanceof Konva.Shape)) return

    const props = this.getShapeProperties(layer.properties as ShapeLayerProperties)
    this.applyCommonProperties(node, layer, props)
  }

  destroy(node: Konva.Node): void {
    node.destroy()
  }

  getSupportedTypes(): string[] {
    return ['shape']
  }

  /**
   * Get shape properties with defaults matching backend ShapeLayerProperties
   */
  private getShapeProperties(properties: Partial<ShapeLayerProperties>): ShapeLayerProperties {
    const fillConfig = this.getFillConfig(properties.fill)
    
    return {
      shapeType: properties.shapeType || 'rectangle',
      fill: fillConfig,
      stroke: this.validateColor(properties.stroke || 'none'),
      strokeWidth: this.validateNumber(properties.strokeWidth || 0, 0, 0, 100),
      strokeOpacity: this.validateNumber(properties.strokeOpacity || 1.0, 1.0, 0.0, 1.0),
      strokeDashArray: properties.strokeDashArray || undefined,
      strokeLineCap: properties.strokeLineCap || 'butt',
      strokeLineJoin: properties.strokeLineJoin || 'miter',
      cornerRadius: this.validateNumber(properties.cornerRadius || 0, 0, 0, Infinity),
      sides: this.validateNumber(properties.sides || 3, 3, 3, 12),
      points: this.validateNumber(properties.points || 5, 5, 3, 12),
      innerRadius: this.validateNumber(properties.innerRadius || 0.5, 0.5, 0.1, 0.9),
      x1: this.validateNumber(properties.x1 || 0, 0),
      y1: this.validateNumber(properties.y1 || 0, 0),
      x2: this.validateNumber(properties.x2 || 100, 100),
      y2: this.validateNumber(properties.y2 || 100, 100),
      shadow: properties.shadow,
      glow: properties.glow
    }
  }

  /**
   * Get fill configuration with defaults
   */
  private getFillConfig(fill?: Partial<ShapeFillConfig>): ShapeFillConfig {
    return {
      type: fill?.type || 'solid',
      color: this.validateColor(fill?.color || '#cccccc'),
      opacity: this.validateNumber(fill?.opacity || 1.0, 1.0, 0.0, 1.0),
      colors: fill?.colors || [],
      angle: fill?.angle || 0,
      centerX: fill?.centerX || 0.5,
      centerY: fill?.centerY || 0.5,
      radius: fill?.radius || 0.5,
      patternType: fill?.patternType || 'dots',
      size: fill?.size || 10,
      backgroundColor: this.validateColor(fill?.backgroundColor || '#ffffff'),
      spacing: fill?.spacing || 20
    }
  }

  /**
   * Sanitize shape type matching backend validation
   */
  private sanitizeShapeType(shapeType: string): ShapeLayerProperties['shapeType'] {
    const validTypes: ShapeLayerProperties['shapeType'][] = [
      'rectangle', 'circle', 'ellipse', 'triangle', 'polygon', 'star', 'line', 'arrow'
    ]
    return validTypes.includes(shapeType as any) ? shapeType as ShapeLayerProperties['shapeType'] : 'rectangle'
  }

  /**
   * Validate color format matching backend validation
   */
  private validateColor(color: string): string {
    if (typeof color !== 'string') return '#000000'
    if (color === 'none') return 'none'
    const hexPattern = /^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/
    return hexPattern.test(color) ? color : '#000000'
  }

  /**
   * Validate numeric values with min/max constraints
   */
  private validateNumber(value: number, defaultValue: number, min?: number, max?: number): number {
    if (typeof value !== 'number' || isNaN(value)) return defaultValue
    if (min !== undefined && value < min) return min
    if (max !== undefined && value > max) return max
    return value
  }

  private createRectangle(layer: LayerNode, props: ShapeLayerProperties): Konva.Rect {
    return new Konva.Rect({
      x: layer.x,
      y: layer.y,
      width: layer.width,
      height: layer.height,
      cornerRadius: props.cornerRadius,
      draggable: true
    })
  }

  private createCircle(layer: LayerNode, props: ShapeLayerProperties): Konva.Circle {
    const radius = Math.min(layer.width, layer.height) / 2
    return new Konva.Circle({
      x: layer.x + layer.width / 2,
      y: layer.y + layer.height / 2,
      radius: radius,
      draggable: true
    })
  }

  private createEllipse(layer: LayerNode, props: ShapeLayerProperties): Konva.Ellipse {
    return new Konva.Ellipse({
      x: layer.x + layer.width / 2,
      y: layer.y + layer.height / 2,
      radiusX: layer.width / 2,
      radiusY: layer.height / 2,
      draggable: true
    })
  }

  private createTriangle(layer: LayerNode, props: ShapeLayerProperties): Konva.RegularPolygon {
    return new Konva.RegularPolygon({
      x: layer.x + layer.width / 2,
      y: layer.y + layer.height / 2,
      sides: 3,
      radius: Math.min(layer.width, layer.height) / 2,
      draggable: true
    })
  }

  private createPolygon(layer: LayerNode, props: ShapeLayerProperties): Konva.RegularPolygon {
    return new Konva.RegularPolygon({
      x: layer.x + layer.width / 2,
      y: layer.y + layer.height / 2,
      sides: props.sides,
      radius: Math.min(layer.width, layer.height) / 2,
      draggable: true
    })
  }

  private createStar(layer: LayerNode, props: ShapeLayerProperties): Konva.Star {
    const outerRadius = Math.min(layer.width, layer.height) / 2
    
    return new Konva.Star({
      x: layer.x + layer.width / 2,
      y: layer.y + layer.height / 2,
      numPoints: props.points,
      innerRadius: outerRadius * props.innerRadius,
      outerRadius: outerRadius,
      draggable: true
    })
  }

  private createLine(layer: LayerNode, props: ShapeLayerProperties): Konva.Line {
    const points = [props.x1, props.y1, props.x2, props.y2]
    return new Konva.Line({
      x: layer.x,
      y: layer.y,
      points: points,
      draggable: true
    })
  }

  private createArrow(layer: LayerNode, props: ShapeLayerProperties): Konva.Path {
    // Create arrow shape using path
    const arrowHeadSize = Math.min(layer.width, layer.height) * 0.2
    const bodyHeight = layer.height * 0.4
    const bodyWidth = layer.width - arrowHeadSize
    
    const pathData = [
      `M 0,${layer.height / 2 - bodyHeight / 2}`,
      `L ${bodyWidth},${layer.height / 2 - bodyHeight / 2}`,
      `L ${bodyWidth},0`,
      `L ${layer.width},${layer.height / 2}`,
      `L ${bodyWidth},${layer.height}`,
      `L ${bodyWidth},${layer.height / 2 + bodyHeight / 2}`,
      `L 0,${layer.height / 2 + bodyHeight / 2}`,
      'Z'
    ].join(' ')

    return new Konva.Path({
      x: layer.x,
      y: layer.y,
      data: pathData,
      draggable: true
    })
  }

  private applyCommonProperties(shape: Konva.Shape, layer: LayerNode, props: ShapeLayerProperties): void {
    // Apply fill based on type
    this.applyFill(shape, props.fill)
    
    // Apply stroke properties
    shape.setAttrs({
      stroke: props.stroke === 'none' ? undefined : props.stroke,
      strokeWidth: props.strokeWidth,
      strokeOpacity: props.strokeOpacity,
      dash: props.strokeDashArray ? props.strokeDashArray.split(',').map(Number) : undefined,
      lineCap: props.strokeLineCap,
      lineJoin: props.strokeLineJoin
    })
    
    // Apply layer-level properties
    shape.setAttrs({
      x: layer.x,
      y: layer.y,
      rotation: layer.rotation || 0,
      scaleX: layer.scaleX || 1,
      scaleY: layer.scaleY || 1,
      opacity: layer.opacity || 1,
      visible: layer.visible !== false,
      listening: !layer.locked
    })
    
    // Apply shadow if present
    if (props.shadow) {
      shape.setAttrs({
        shadowColor: props.shadow.color || '#000000',
        shadowBlur: props.shadow.blur || 0,
        shadowOffsetX: props.shadow.offsetX || 0,
        shadowOffsetY: props.shadow.offsetY || 0,
        shadowOpacity: props.shadow.opacity || 1
      })
    }
    
    // Apply glow if present
    if (props.glow) {
      shape.setAttrs({
        shadowColor: props.glow.color || '#ffffff',
        shadowBlur: props.glow.blur || 10,
        shadowOpacity: props.glow.opacity || 0.8
      })
    }
  }

  /**
   * Apply fill configuration to shape
   */
  private applyFill(shape: Konva.Shape, fill: ShapeFillConfig): void {
    switch (fill.type) {
      case 'solid':
        shape.setAttrs({
          fill: fill.color,
          fillOpacity: fill.opacity
        })
        break
        
      case 'linear':
        if (fill.colors && fill.colors.length >= 2) {
          const colorStops: (string | number)[] = []
          fill.colors.forEach((colorData, index) => {
            if (typeof colorData === 'string') {
              colorStops.push(index / (fill.colors!.length - 1), colorData)
            } else {
              colorStops.push(colorData.stop, colorData.color)
            }
          })
          
          const angle = fill.angle || 0
          shape.setAttrs({
            fillLinearGradientColorStops: colorStops,
            fillLinearGradientStartPoint: { x: 0, y: 0 },
            fillLinearGradientEndPoint: { 
              x: Math.cos(angle * Math.PI / 180) * 100,
              y: Math.sin(angle * Math.PI / 180) * 100
            },
            fillOpacity: fill.opacity
          })
        }
        break
        
      case 'radial':
        if (fill.colors && fill.colors.length >= 2) {
          const colorStops: (string | number)[] = []
          fill.colors.forEach((colorData, index) => {
            if (typeof colorData === 'string') {
              colorStops.push(index / (fill.colors!.length - 1), colorData)
            } else {
              colorStops.push(colorData.stop, colorData.color)
            }
          })
          
          const centerX = fill.centerX || 0.5
          const centerY = fill.centerY || 0.5
          const radius = fill.radius || 0.5
          
          shape.setAttrs({
            fillRadialGradientColorStops: colorStops,
            fillRadialGradientStartPoint: { 
              x: centerX * 100, 
              y: centerY * 100 
            },
            fillRadialGradientEndPoint: { 
              x: centerX * 100, 
              y: centerY * 100 
            },
            fillRadialGradientStartRadius: 0,
            fillRadialGradientEndRadius: radius * 100,
            fillOpacity: fill.opacity
          })
        }
        break
        
      case 'pattern':
        // Pattern fill would require creating a pattern image
        // For now, fall back to solid color
        shape.setAttrs({
          fill: fill.backgroundColor || fill.color,
          fillOpacity: fill.opacity
        })
        break
        
      default:
        shape.setAttrs({
          fill: fill.color,
          fillOpacity: fill.opacity
        })
    }
  }

  private updateRectangle(rect: Konva.Rect, layer: LayerNode): void {
    rect.setAttrs({
      width: layer.width,
      height: layer.height,
      cornerRadius: layer.properties.cornerRadius || 0
    })
  }

  private updateCircle(circle: Konva.Circle, layer: LayerNode): void {
    const radius = Math.min(layer.width, layer.height) / 2
    circle.setAttrs({
      x: layer.x + layer.width / 2,
      y: layer.y + layer.height / 2,
      radius: radius
    })
  }

  private updateEllipse(ellipse: Konva.Ellipse, layer: LayerNode): void {
    ellipse.setAttrs({
      x: layer.x + layer.width / 2,
      y: layer.y + layer.height / 2,
      radiusX: layer.width / 2,
      radiusY: layer.height / 2
    })
  }

  private updatePolygon(polygon: Konva.RegularPolygon, layer: LayerNode): void {
    const sides = layer.properties.sides || (layer.properties.shapeType === 'triangle' ? 3 : 6)
    polygon.setAttrs({
      x: layer.x + layer.width / 2,
      y: layer.y + layer.height / 2,
      sides: sides,
      radius: Math.min(layer.width, layer.height) / 2
    })
  }

  private updateStar(star: Konva.Star, layer: LayerNode): void {
    const points = layer.properties.points || 5
    const innerRadius = layer.properties.innerRadius || 0.5
    const outerRadius = Math.min(layer.width, layer.height) / 2
    
    star.setAttrs({
      x: layer.x + layer.width / 2,
      y: layer.y + layer.height / 2,
      numPoints: points,
      innerRadius: outerRadius * innerRadius,
      outerRadius: outerRadius
    })
  }

  private updateLine(line: Konva.Line, layer: LayerNode): void {
    const points = layer.properties.points || [0, 0, layer.width, layer.height]
    line.setAttrs({
      points: points
    })
  }

  private setupInteractions(shape: Konva.Shape, layer: LayerNode): void {
    // Drag event handlers
    shape.on('dragstart', () => {
      const container = shape.getStage()?.container()
      if (container) {
        container.style.cursor = 'grabbing'
      }
    })



    // Hover effects
    shape.on('mouseenter', () => {
      if (!layer.locked) {
        const container = shape.getStage()?.container()
        if (container) {
          container.style.cursor = 'pointer'
        }
      }
    })

    shape.on('mouseleave', () => {
      const container = shape.getStage()?.container()
      if (container) {
        container.style.cursor = 'default'
      }
    })
  }
}

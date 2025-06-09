import Konva from 'konva'
import type { KonvaLayerRenderer, LayerNode } from '../types'
import type { Layer, GroupLayerProperties } from '../../../types'

/**
 * Group Layer Renderer - renders group layers as Konva.Group containers
 * Matches backend GroupLayerProperties structure with proper type consistency
 */
export class GroupLayerRenderer implements KonvaLayerRenderer {
  canRender(layer: Layer): boolean {
    return layer.type === 'group'
  }

  render(layer: LayerNode): Konva.Node {
    const properties = layer.properties as GroupLayerProperties
    
    const group = new Konva.Group({
      x: layer.x,
      y: layer.y,
      width: layer.width,
      height: layer.height,
      draggable: true,
      clipFunc: properties.clipPath ? this.createClipFunction(layer, properties) : undefined
    })

    // Apply group-specific properties
    this.applyGroupProperties(group, layer, properties)
    this.setupInteractions(group, layer)
    
    return group
  }

  update(node: Konva.Node, layer: LayerNode): void {
    if (!(node instanceof Konva.Group)) return

    const properties = layer.properties as GroupLayerProperties
    this.applyGroupProperties(node, layer, properties)
    
    // Update clip path if changed
    if (properties.clipPath) {
      node.clipFunc(this.createClipFunction(layer, properties))
    } else {
      node.clipFunc(undefined)
    }
  }

  destroy(node: Konva.Node): void {
    node.destroy()
  }

  getSupportedTypes(): string[] {
    return ['group']
  }

  private applyGroupProperties(group: Konva.Group, layer: LayerNode, properties: GroupLayerProperties): void {
    group.setAttrs({
      opacity: layer.opacity,
      visible: layer.visible,
      listening: !layer.locked
    })

    // Apply blend mode if specified
    if (properties.blendMode) {
      group.setAttrs({
        globalCompositeOperation: this.mapBlendMode(properties.blendMode)
      })
    }

    // Apply isolation if specified
    if (properties.isolation) {
      // Create an isolated context for the group
      group.cache()
    }

    // Apply mask if specified
    if (properties.mask) {
      this.applyMask(group, properties.mask)
    }
  }

  private createClipFunction(layer: LayerNode, properties: GroupLayerProperties) {
    const clipPath = properties.clipPath
    
    return (ctx: any) => {
      if (!clipPath) {
        ctx.rect(0, 0, layer.width, layer.height)
        return
      }

      switch (clipPath.type) {
        case 'rectangle':
          const width = clipPath.width ?? layer.width
          const height = clipPath.height ?? layer.height
          const x = clipPath.x ?? 0
          const y = clipPath.y ?? 0
          ctx.rect(x, y, width, height)
          break
          
        case 'circle':
          const cx = clipPath.cx ?? layer.width / 2
          const cy = clipPath.cy ?? layer.height / 2
          const r = clipPath.r ?? Math.min(layer.width, layer.height) / 2
          ctx.arc(cx, cy, r, 0, Math.PI * 2)
          break
          
        case 'ellipse':
          const ellipseCx = clipPath.cx ?? layer.width / 2
          const ellipseCy = clipPath.cy ?? layer.height / 2
          const rx = clipPath.rx ?? layer.width / 2
          const ry = clipPath.ry ?? layer.height / 2
          ctx.ellipse(ellipseCx, ellipseCy, rx, ry, 0, 0, Math.PI * 2)
          break
          
        case 'polygon':
          const points = clipPath.points || []
          if (points.length > 0) {
            ctx.beginPath()
            ctx.moveTo(points[0].x, points[0].y)
            for (let i = 1; i < points.length; i++) {
              ctx.lineTo(points[i].x, points[i].y)
            }
            ctx.closePath()
          } else {
            // Default triangle if no points provided
            ctx.beginPath()
            ctx.moveTo(layer.width / 2, 0)
            ctx.lineTo(0, layer.height)
            ctx.lineTo(layer.width, layer.height)
            ctx.closePath()
          }
          break
          
        case 'path':
          // Path clipping would require parsing SVG path data
          // For now, fallback to rectangle
          ctx.rect(0, 0, layer.width, layer.height)
          break
          
        default:
          ctx.rect(0, 0, layer.width, layer.height)
      }
    }
  }

  private mapBlendMode(blendMode: string): GlobalCompositeOperation {
    const blendModeMap: Record<string, GlobalCompositeOperation> = {
      'normal': 'source-over',
      'multiply': 'multiply',
      'screen': 'screen',
      'overlay': 'overlay',
      'darken': 'darken',
      'lighten': 'lighten',
      'color-dodge': 'color-dodge',
      'color-burn': 'color-burn',
      'hard-light': 'hard-light',
      'soft-light': 'soft-light',
      'difference': 'difference',
      'exclusion': 'exclusion',
      'hue': 'hue',
      'saturation': 'saturation',
      'color': 'color',
      'luminosity': 'luminosity'
    }
    
    return blendModeMap[blendMode] || 'source-over'
  }

  private applyMask(group: Konva.Group, maskConfig: any): void {
    // Create mask shape based on configuration
    let maskShape: Konva.Shape | null = null
    
    switch (maskConfig.type) {
      case 'rectangle':
        maskShape = new Konva.Rect({
          x: 0,
          y: 0,
          width: group.width(),
          height: group.height(),
          fill: 'white'
        })
        break
        
      case 'circle':
        const radius = Math.min(group.width(), group.height()) / 2
        maskShape = new Konva.Circle({
          x: group.width() / 2,
          y: group.height() / 2,
          radius: radius,
          fill: 'white'
        })
        break
        
      case 'gradient':
        maskShape = new Konva.Rect({
          x: 0,
          y: 0,
          width: group.width(),
          height: group.height(),
          fillLinearGradientStartPoint: { x: 0, y: 0 },
          fillLinearGradientEndPoint: { x: group.width(), y: 0 },
          fillLinearGradientColorStops: [0, 'white', 1, 'black']
        })
        break
        
      default:
        return
    }
    
    if (maskShape) {
      // Apply mask using composite operation
      group.cache()
      maskShape.cache()
      group.globalCompositeOperation('destination-in')
    }
  }

  private setupInteractions(group: Konva.Group, layer: LayerNode): void {
    // Drag event handlers
    group.on('dragstart', () => {
      const stage = group.getStage()
      const container = stage?.container()
      if (container && container.style) {
        container.style.cursor = 'grabbing'
      }
    })

    // Hover effects for group container
    group.on('mouseenter', () => {
      if (!layer.locked) {
        const stage = group.getStage()
        const container = stage?.container()
        if (container && container.style) {
          container.style.cursor = 'pointer'
        }
      }
    })

    group.on('mouseleave', () => {
      const stage = group.getStage()
      const container = stage?.container()
      if (container && container.style) {
        container.style.cursor = 'default'
      }
    })

    // Prevent clicks from propagating to children when group is selected
    group.on('click tap', (e) => {
      e.cancelBubble = true
    })
  }
}

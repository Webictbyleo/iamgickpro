import Konva from 'konva'
import type { KonvaLayerRenderer, LayerNode } from '../types'
import type { Layer, TextLayerProperties } from '../../../types'

/**
 * Text Layer Renderer - renders text layers using Konva.Text
 * Matches backend TextLayerRenderer properties and validation
 */
export class TextLayerRenderer implements KonvaLayerRenderer {
  canRender(layer: Layer): boolean {
    return layer.type === 'text'
  }

  render(layerData: LayerNode): Konva.Node {
    const props = this.getTextProperties(layerData.properties as Partial<TextLayerProperties>)
    
    const textNode = new Konva.Text({
      id: layerData.id,
      x: layerData.x,
      y: layerData.y,
      width: layerData.width,
      height: layerData.height,
      text: props.text,
      fontSize: props.fontSize,
      fontFamily: props.fontFamily,
      fontStyle: this.getFontStyle(props),
      fill: props.color,
      align: props.textAlign as any,
      verticalAlign: 'top',
      lineHeight: props.lineHeight,
      letterSpacing: props.letterSpacing,
      visible: layerData.visible,
      opacity: layerData.opacity,
      rotation: layerData.rotation,
      scaleX: layerData.scaleX,
      scaleY: layerData.scaleY,
      draggable: !layerData.locked
    })

    this.applyTextEffects(textNode, props)
    return textNode
  }

  update(node: Konva.Node, layerData: LayerNode): void {
    if (node instanceof Konva.Text) {
      const props = this.getTextProperties(layerData.properties as Partial<TextLayerProperties>)
      
      node.setAttrs({
        x: layerData.x,
        y: layerData.y,
        width: layerData.width,
        height: layerData.height,
        text: props.text,
        fontSize: props.fontSize,
        fontFamily: props.fontFamily,
        fontStyle: this.getFontStyle(props),
        fill: props.color,
        align: props.textAlign,
        verticalAlign: 'top',
        lineHeight: props.lineHeight,
        letterSpacing: props.letterSpacing,
        visible: layerData.visible,
        opacity: layerData.opacity,
        rotation: layerData.rotation,
        scaleX: layerData.scaleX,
        scaleY: layerData.scaleY,
        draggable: !layerData.locked
      })
      
      this.applyTextEffects(node, props)
    }
  }

  destroy(node: Konva.Node): void {
    node.destroy()
  }

  getSupportedTypes(): string[] {
    return ['text']
  }

  /**
   * Get text properties with defaults matching backend TextLayerProperties
   */
  private getTextProperties(properties: Partial<TextLayerProperties>): TextLayerProperties {
    return {
      text: properties.text || 'Sample Text',
      fontSize: properties.fontSize || 16,
      fontFamily: properties.fontFamily || 'Arial',
      fontWeight: properties.fontWeight || 'normal',
      fontStyle: properties.fontStyle || 'normal',
      textDecoration: properties.textDecoration || 'none',
      color: properties.color || '#000000',
      textAlign: properties.textAlign || 'left',
      lineHeight: properties.lineHeight || 1.2,
      letterSpacing: properties.letterSpacing || 0
    } as TextLayerProperties
  }

  private getFontStyle(props: TextLayerProperties): string {
    const parts: string[] = []
    
    if (props.fontStyle !== 'normal') {
      parts.push(props.fontStyle)
    }
    
    if (props.fontWeight !== 'normal') {
      parts.push(props.fontWeight)
    }
    
    return parts.length > 0 ? parts.join(' ') : 'normal'
  }

  private applyTextEffects(textNode: Konva.Text, props: TextLayerProperties): void {
    // Apply shadow if present
    if (props.shadow) {
      textNode.setAttrs({
        shadowColor: props.shadow.color,
        shadowBlur: props.shadow.blur,
        shadowOffsetX: props.shadow.offsetX,
        shadowOffsetY: props.shadow.offsetY,
        shadowOpacity: props.shadow.opacity
      })
    }
    
    // Apply stroke if present
    if (props.stroke && props.strokeWidth) {
      textNode.setAttrs({
        stroke: props.stroke,
        strokeWidth: props.strokeWidth
      })
    }
  }
}

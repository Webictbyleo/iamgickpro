import Konva from 'konva'
import type { KonvaLayerRenderer, LayerNode } from '../types'
import type { Layer, TextLayerProperties, AutoResizeConfig } from '../../../types'
import { FontManager } from '../../../services/FontManager'

/**
 * Text Layer Renderer - Enhanced for modern text editing experience
 * Features: Inline editing, real-time preview, better text handling, automatic font loading and reflow
 * Matches backend TextLayerRenderer properties and validation
 */
export class TextLayerRenderer implements KonvaLayerRenderer {
  private editingLayer: string | null = null
  private textInput: HTMLTextAreaElement | null = null
  private editStartText: string = ''
  private eventEmitter?: any // Will be injected for communication
  private fontLoadingHandlers = new Map<string, () => void>() // Track font loading callbacks
  canRender(layer: Layer): boolean {
    return layer.type === 'text'
  }

  /**
   * Set the event emitter for communication with the editor
   */
  setEventEmitter(eventEmitter: any): void {
    this.eventEmitter = eventEmitter
  }

  render(layerData: LayerNode): Konva.Node {
    const props = this.getTextProperties(layerData.properties as Partial<TextLayerProperties>)
    
    // Following Konva docs exactly - NEVER set height for text wrapping
    const textNode = new Konva.Text({
      id: layerData.id,
      x: layerData.x,
      y: layerData.y,
      width: layerData.width,
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
      draggable: !layerData.locked,
      wrap: 'word'
    })

    this.applyTextEffects(textNode, props)
    this.setupTextInteractions(textNode, layerData)
    
    // Handle font loading and reflow
    this.handleFontLoading(textNode, layerData, props.fontFamily)
    
    return textNode
  }

  update(node: Konva.Node, layerData: LayerNode): void {
    if (node instanceof Konva.Text) {
      const props = this.getTextProperties(layerData.properties as Partial<TextLayerProperties>)
      
      // Following Konva docs exactly - NEVER set height for text wrapping
      node.setAttrs({
        x: layerData.x,
        y: layerData.y,
        width: layerData.width,
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
        draggable: !layerData.locked,
        wrap: 'word'
      })
      
      this.applyTextEffects(node, props)
      
      // Re-setup interactions (they may have been lost during update)
      this.setupTextInteractions(node, layerData)
      
      // Handle font loading and reflow for font changes
      this.handleFontLoading(node, layerData, props.fontFamily)
    }
  }

  /**
   * Handle font loading and text reflow internally
   * This method ensures fonts are loaded and triggers reflow when font changes occur
   */
  private async handleFontLoading(textNode: Konva.Text, layerData: LayerNode, fontFamily: string): Promise<void> {
    // Skip if font is already loaded
    if (FontManager.isFontLoaded(fontFamily)) {
      this.reflowTextTransformations(textNode, layerData)
      return
    }

    // Clean up any previous font loading handler for this layer
    const existingHandler = this.fontLoadingHandlers.get(layerData.id)
    if (existingHandler) {
      existingHandler()
      this.fontLoadingHandlers.delete(layerData.id)
    }

    try {
      // Load font with default options
      await FontManager.loadFont(fontFamily, {
        weights: ['400', '700'],
        styles: ['normal', 'italic'],
        display: 'swap'
      })

      // Font loaded successfully - trigger reflow
      this.reflowTextTransformations(textNode, layerData)
      
    } catch (error) {
      console.warn(`Failed to load font ${fontFamily} for text layer ${layerData.id}:`, error)
      // Use fallback font and still trigger reflow
      const fallbackFont = FontManager.getFontWithFallbacks(fontFamily)
      textNode.fontFamily(fallbackFont)
      this.reflowTextTransformations(textNode, layerData)
    }
  }

  /**
   * Trigger text reflow and transformation handle updates
   * This method handles the text layout recalculation when fonts change
   */
  private reflowTextTransformations(textNode: Konva.Text, layerData: LayerNode): void {
    // Force text node to recalculate dimensions with new font
    textNode.text(textNode.text()) // Trigger internal text measurement update
    
    // Get the text's new natural height after font load
    const newHeight = textNode.height()
    
    // Update layer data with new natural height
    layerData.height = newHeight
    
    // Fire transform-related events to update transformation handles
    if (this.eventEmitter) {
      // Emit layer:updated (not layer:update) to properly trigger store and UI updates
      this.eventEmitter.emit('layer:updated', { 
        id: layerData.id,
        type: layerData.type,
        name: layerData.name,
        visible: layerData.visible,
        locked: layerData.locked,
        opacity: layerData.opacity,
        x: layerData.x,
        y: layerData.y,
        width: layerData.width,
        height: newHeight, // Use the new calculated height
        rotation: layerData.rotation,
        scaleX: layerData.scaleX,
        scaleY: layerData.scaleY,
        zIndex: layerData.zIndex,
        properties: layerData.properties
      })
      
      // Also emit a specific event to refresh transformation handles
      this.eventEmitter.emit('text:reflow', { 
        layerId: layerData.id,
        newHeight: newHeight,
        reason: 'font-change'
      })
    }

    // Force layer redraw
    textNode.getLayer()?.batchDraw()
  }

  destroy(node: Konva.Node): void {
    // Clean up any font loading handlers
    if (node.id()) {
      const handler = this.fontLoadingHandlers.get(node.id())
      if (handler) {
        handler()
        this.fontLoadingHandlers.delete(node.id())
      }
    }
    
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
      letterSpacing: properties.letterSpacing || 0,
      autoResize: properties.autoResize || {
        enabled: false,
        mode: 'none',
        padding: { top: 4, right: 4, bottom: 4, left: 4 }
      }
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

  

  /**
   * Setup enhanced text interactions for modern editing experience
   */
  private setupTextInteractions(textNode: Konva.Text, layerData: LayerNode): void {
    // Drag event handlers
    textNode.on('dragstart', () => {
      const container = textNode.getStage()?.container()
      if (container) {
        container.style.cursor = 'grabbing'
      }
    })



    // Enhanced hover states
    textNode.on('mouseenter', () => {
      if (!layerData.locked && this.editingLayer !== layerData.id) {
        const container = textNode.getStage()?.container()
        if (container) {
          container.style.cursor = 'text'
        }
        // Add subtle highlight when hovering
        textNode.strokeEnabled(true)
        textNode.stroke('rgba(59, 130, 246, 0.3)')
        textNode.strokeWidth(1)
        textNode.getLayer()?.batchDraw()
      }
    })

    textNode.on('mouseleave', () => {
      const container = textNode.getStage()?.container()
      if (container) {
        container.style.cursor = 'default'
      }
      // Remove hover highlight
      if (this.editingLayer !== layerData.id) {
        textNode.strokeEnabled(false)
        textNode.getLayer()?.batchDraw()
      }
    })

    // Click handling for selection
    textNode.on('click tap', (e) => {
      e.cancelBubble = true
      if (this.eventEmitter) {
        this.eventEmitter.emit('layer:select', { layerId: layerData.id })
      }
    })
  }

  


 

  /**
   * Check if a layer is currently being edited
   */
  public isEditing(layerId: string): boolean {
    return this.editingLayer === layerId
  }

  /**
   * Stop any active text editing
   */
  public stopEditing(): void {
    if (this.textInput && this.editingLayer) {
      this.textInput.blur()
    }
  }
}

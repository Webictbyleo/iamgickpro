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
  private editingLayer: number | null = null
  private textInput: HTMLTextAreaElement | null = null
  private editStartText: string = ''
  private eventEmitter?: any // Will be injected for communication
  private fontLoadingHandlers = new Map<number, () => void>() // Track font loading callbacks
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
      id: layerData.id.toString(), // Convert number ID to string for Konva
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
      // Use a visual-only reflow event that doesn't trigger backend persistence
      // This event should only update the visual representation and transform handles
      this.eventEmitter.emit('layer:reflow', { 
        id: layerData.id,
        height: newHeight,
        type: 'text-font-loaded'
      })
    }

    // Force layer redraw
    textNode.getLayer()?.batchDraw()
  }

  destroy(node: Konva.Node): void {
    // Clean up any font loading handlers
    if (node.id()) {
      const layerId = parseInt(node.id(), 10) // Convert Konva string ID back to number
      const handler = this.fontLoadingHandlers.get(layerId)
      if (handler) {
        handler()
        this.fontLoadingHandlers.delete(layerId)
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

    textNode.on('dragend', () => {
      const container = textNode.getStage()?.container()
      if (container) {
        container.style.cursor = 'text'
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

    // Double-click handling for inline editing
    textNode.on('dblclick dbltap', (e) => {
      e.cancelBubble = true
      if (!layerData.locked && this.editingLayer !== layerData.id) {
        this.startInlineEditing(textNode, layerData)
      }
    })
  }

  /**
   * Start inline text editing with proper transform/scale support
   */
  private startInlineEditing(textNode: Konva.Text, layerData: LayerNode): void {
    if (this.editingLayer) {
      this.stopEditing()
    }

    this.editingLayer = layerData.id
    this.editStartText = textNode.text()

    // Hide the text node during editing
    textNode.visible(false)
    textNode.getLayer()?.batchDraw()

    // Create textarea for editing
    this.createTextEditor(textNode, layerData)

    // Emit editing started event
    if (this.eventEmitter) {
      this.eventEmitter.emit('text:editing:started', { layerId: layerData.id })
    }
  }

  /**
   * Create textarea editor positioned over the text node
   */
  private createTextEditor(textNode: Konva.Text, layerData: LayerNode): void {
    const stage = textNode.getStage()
    if (!stage) return

    // Get stage container and position
    const container = stage.container()
    const containerRect = container.getBoundingClientRect()
    
    // Calculate text position in screen coordinates
    const transform = textNode.getAbsoluteTransform()
    const pos = transform.point({ x: 0, y: 0 })
    const scale = textNode.getAbsoluteScale()

    // Create textarea
    this.textInput = document.createElement('textarea')
    this.textInput.value = textNode.text()
    
    // Position and style the textarea to match the text
    const computedFontSize = textNode.fontSize() * scale.x
    const computedWidth = textNode.width() * scale.x
    
    Object.assign(this.textInput.style, {
      position: 'absolute',
      top: `${containerRect.top + pos.y}px`,
      left: `${containerRect.left + pos.x}px`,
      width: `${Math.max(computedWidth, 100)}px`,
      minHeight: `${computedFontSize * 1.2}px`,
      fontSize: `${computedFontSize}px`,
      fontFamily: textNode.fontFamily(),
      fontWeight: textNode.fontStyle().includes('bold') ? 'bold' : 'normal',
      fontStyle: textNode.fontStyle().includes('italic') ? 'italic' : 'normal',
      textDecoration: textNode.textDecoration(),
      color: textNode.fill(),
      backgroundColor: 'transparent',
      border: '2px solid #3b82f6',
      borderRadius: '4px',
      padding: '4px 8px',
      margin: '0',
      outline: 'none',
      resize: 'none',
      overflow: 'hidden',
      zIndex: '1000',
      textAlign: textNode.align() as any,
      lineHeight: `${textNode.lineHeight()}`,
      letterSpacing: `${textNode.letterSpacing()}px`,
      wordWrap: 'break-word',
      whiteSpace: 'pre-wrap',
      boxShadow: '0 2px 8px rgba(0, 0, 0, 0.15)',
      // Match text transformation
      transform: `rotate(${textNode.rotation()}deg) scale(${textNode.scaleX()}, ${textNode.scaleY()})`,
      transformOrigin: 'top left'
    })

    // Auto-resize textarea
    this.textInput.addEventListener('input', this.handleTextareaResize.bind(this))
    
    // Handle editing events
    this.textInput.addEventListener('blur', this.handleTextEditingEnd.bind(this))
    this.textInput.addEventListener('keydown', this.handleTextEditingKeydown.bind(this))

    // Add to page and focus
    document.body.appendChild(this.textInput)
    this.textInput.focus()
    this.textInput.select()

    // Initial resize
    this.handleTextareaResize()
  }

  /**
   * Auto-resize textarea to fit content
   */
  private handleTextareaResize(): void {
    if (!this.textInput) return
    
    this.textInput.style.height = 'auto'
    this.textInput.style.height = `${Math.max(this.textInput.scrollHeight, 30)}px`
  }

  /**
   * Handle keydown events during text editing
   */
  private handleTextEditingKeydown(event: KeyboardEvent): void {
    if (event.key === 'Escape') {
      event.preventDefault()
      this.cancelTextEditing()
    } else if (event.key === 'Enter' && (event.ctrlKey || event.metaKey)) {
      event.preventDefault()
      this.finishTextEditing()
    }
    // Allow other keys for normal editing
  }

  /**
   * Handle end of text editing (blur event)
   */
  private handleTextEditingEnd(): void {
    this.finishTextEditing()
  }

  /**
   * Finish text editing and apply changes
   */
  private finishTextEditing(): void {
    if (!this.textInput || !this.editingLayer) return

    const newText = this.textInput.value || this.editStartText // Fallback to original text
    const layerId = this.editingLayer

    // Always update the text layer to ensure it's not lost
    if (this.eventEmitter) {
      this.eventEmitter.emit('layer:update-properties', {
        layerId,
        properties: { text: newText }
      })
    }

    // Clean up editor after updating
    this.cleanupTextEditor()

    // Emit editing finished event
    if (this.eventEmitter) {
      this.eventEmitter.emit('text:editing:finished', { 
        layerId, 
        changed: newText !== this.editStartText 
      })
    }

    this.resetEditingState()
  }

  /**
   * Cancel text editing and restore original text
   */
  private cancelTextEditing(): void {
    if (!this.editingLayer) return

    const layerId = this.editingLayer

    // Clean up editor
    this.cleanupTextEditor()

    // Emit editing cancelled event
    if (this.eventEmitter) {
      this.eventEmitter.emit('text:editing:cancelled', { layerId })
    }

    this.resetEditingState()
  }

  /**
   * Clean up the text editor DOM element
   */
  private cleanupTextEditor(): void {
    if (this.textInput) {
      this.textInput.removeEventListener('input', this.handleTextareaResize.bind(this))
      this.textInput.removeEventListener('blur', this.handleTextEditingEnd.bind(this))
      this.textInput.removeEventListener('keydown', this.handleTextEditingKeydown.bind(this))
      
      if (this.textInput.parentNode) {
        this.textInput.parentNode.removeChild(this.textInput)
      }
      this.textInput = null
    }
  }

  /**
   * Reset editing state and show text node
   */
  private resetEditingState(): void {
    if (this.editingLayer) {
      // Find and show the text node
      const stage = document.querySelector('canvas')?.closest('.konva-stage')
      if (stage) {
        const konvaStage = (stage as any).__konvaStage as Konva.Stage
        if (konvaStage && this.editingLayer) {
          const textNode = konvaStage.findOne(`#${this.editingLayer}`) as Konva.Text
          if (textNode) {
            textNode.visible(true)
            textNode.strokeEnabled(false)
            textNode.getLayer()?.batchDraw()
          }
        }
      }
    }

    this.editingLayer = null
    this.editStartText = ''
  }

  /**
   * Check if a layer is currently being edited
   */
  public isEditing(layerId: number): boolean {
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

import Konva from 'konva'
import type { KonvaLayerRenderer, LayerNode } from '../types'
import type { Layer, TextLayerProperties, AutoResizeConfig } from '../../../types'

/**
 * Text Layer Renderer - Enhanced for modern text editing experience
 * Features: Inline editing, real-time preview, better text handling
 * Matches backend TextLayerRenderer properties and validation
 */
export class TextLayerRenderer implements KonvaLayerRenderer {
  private editingLayer: string | null = null
  private textInput: HTMLTextAreaElement | null = null
  private editStartText: string = ''
  private eventEmitter?: any // Will be injected for communication
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
      draggable: !layerData.locked,
      wrap: 'word' // Always enable word wrapping for better text behavior
    })

    this.applyTextEffects(textNode, props)
    this.setupTextInteractions(textNode, layerData)
    
    // Apply auto-resize if enabled
    if (props.autoResize.enabled) {
      this.applyAutoResize(textNode, layerData, props.autoResize)
    }
    
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
        draggable: !layerData.locked,
        wrap: 'word' // Always enable word wrapping for better text behavior
      })
      
      this.applyTextEffects(node, props)
      
      // Re-setup interactions (they may have been lost during update)
      this.setupTextInteractions(node, layerData)
      
      // Apply auto-resize if enabled
      if (props.autoResize.enabled) {
        this.applyAutoResize(node, layerData, props.autoResize)
      }
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
   * Apply auto-resize logic to text node based on configuration
   */
  public applyAutoResize(textNode: Konva.Text, layerData: LayerNode, config: AutoResizeConfig): void {
    if (!config.enabled || config.mode === 'none') {
      return
    }

    const padding = config.padding || { top: 4, right: 4, bottom: 4, left: 4 }
    
    let newWidth = layerData.width
    let newHeight = layerData.height
    
    // Calculate auto-resize dimensions based on mode
    switch (config.mode) {
      case 'width':
        // Enable wrapping to get accurate text measurements
        textNode.wrap('word')
        const textWidthCase1 = textNode.getTextWidth()
        newWidth = textWidthCase1 + padding.left + padding.right
        
        // Apply width constraints
        if (config.minWidth && newWidth < config.minWidth) {
          newWidth = config.minWidth
        }
        if (config.maxWidth && newWidth > config.maxWidth) {
          newWidth = config.maxWidth
        }
        
        // Set the constrained width and measure wrapped height
        textNode.width(newWidth - padding.left - padding.right)
        textNode._clearCache('text')
        newHeight = textNode.getTextHeight() + padding.top + padding.bottom
        break
        
      case 'height':
        // For height-only mode, text should wrap to fit current width and expand height
        textNode.wrap('word')
        textNode.width(layerData.width - padding.left - padding.right)
        textNode._clearCache('text')
        newHeight = textNode.getTextHeight() + padding.top + padding.bottom
        
        // Apply height constraints (but prioritize content over constraints)
        if (config.minHeight && newHeight < config.minHeight) {
          newHeight = config.minHeight
        }
        // Don't apply maxHeight constraint for height mode - let content expand
        break
        
      case 'both':
        // Enable wrapping first to get accurate measurements
        textNode.wrap('word')
        const textWidthCase3 = textNode.getTextWidth()
        
        newWidth = textWidthCase3 + padding.left + padding.right
        
        // Apply width constraints
        if (config.minWidth && newWidth < config.minWidth) {
          newWidth = config.minWidth
        }
        if (config.maxWidth && newWidth > config.maxWidth) {
          newWidth = config.maxWidth
        }
        
        // Set the constrained width and recalculate height based on wrapping
        textNode.width(newWidth - padding.left - padding.right)
        textNode._clearCache('text')
        newHeight = textNode.getTextHeight() + padding.top + padding.bottom
        
        // Apply height constraints (but prioritize content over maxHeight)
        if (config.minHeight && newHeight < config.minHeight) {
          newHeight = config.minHeight
        }
        // Don't apply maxHeight constraint - let content expand naturally
        break
    }
    
    // Update text node dimensions
    textNode.setAttrs({
      width: newWidth - padding.left - padding.right,
      height: newHeight - padding.top - padding.bottom
    })
    
    // Update layer data for consistency (this would typically trigger layer update events)
    layerData.width = newWidth
    layerData.height = newHeight
  }

  /**
   * Setup enhanced text interactions for modern editing experience
   */
  private setupTextInteractions(textNode: Konva.Text, layerData: LayerNode): void {
    // Double-click to enter inline edit mode
    textNode.on('dblclick dbltap', (e) => {
      e.cancelBubble = true
      this.startInlineEdit(textNode, layerData)
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
   * Start inline text editing
   */
  private startInlineEdit(textNode: Konva.Text, layerData: LayerNode): void {
    if (layerData.locked || this.editingLayer) return

    this.editingLayer = layerData.id
    this.editStartText = textNode.text()

    const stage = textNode.getStage()
    if (!stage) return

    const container = stage.container()
    
    // Get text position on screen
    const textPosition = textNode.getAbsolutePosition()
    const stageBox = stage.container().getBoundingClientRect()
    const transform = stage.getAbsoluteTransform().copy()
    transform.invert()
    
    // Create invisible textarea for editing
    this.textInput = document.createElement('textarea')
    this.textInput.value = textNode.text()
    
    // Style the textarea to match the text
    const props = this.getTextProperties(layerData.properties as Partial<TextLayerProperties>)
    this.textInput.style.cssText = `
      position: absolute;
      top: ${stageBox.top + textPosition.y * stage.scaleY()}px;
      left: ${stageBox.left + textPosition.x * stage.scaleX()}px;
      width: ${textNode.width() * stage.scaleX()}px;
      min-height: ${textNode.height() * stage.scaleY()}px;
      font-family: ${props.fontFamily};
      font-size: ${props.fontSize * stage.scaleY()}px;
      font-weight: ${props.fontWeight};
      font-style: ${props.fontStyle};
      color: ${props.color};
      text-align: ${props.textAlign};
      line-height: ${props.lineHeight};
      background: rgba(255, 255, 255, 0.95);
      border: 2px solid #3b82f6;
      border-radius: 4px;
      padding: 4px;
      margin: 0;
      resize: none;
      outline: none;
      z-index: 1000;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
      backdrop-filter: blur(8px);
    `

    // Hide the original text node
    textNode.visible(false)
    textNode.getLayer()?.batchDraw()

    // Add to document
    document.body.appendChild(this.textInput)
    this.textInput.focus()
    this.textInput.select()

    // Auto-resize textarea as user types
    const autoResize = () => {
      this.textInput!.style.height = 'auto'
      this.textInput!.style.height = Math.max(this.textInput!.scrollHeight, parseInt(this.textInput!.style.minHeight)) + 'px'
    }

    // Real-time preview
    const updatePreview = () => {
      if (this.textInput && this.editingLayer) {
        textNode.text(this.textInput.value)
        
        // Apply auto-resize if enabled
        const props = this.getTextProperties(layerData.properties as Partial<TextLayerProperties>)
        if (props.autoResize.enabled) {
          this.applyAutoResize(textNode, layerData, props.autoResize)
        }
        
        autoResize()
        // Don't show text node yet, keep it hidden until editing is done
      }
    }

    // Event listeners
    this.textInput.addEventListener('input', updatePreview)
    this.textInput.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') {
        this.cancelInlineEdit(textNode)
      } else if (e.key === 'Enter' && (e.ctrlKey || e.metaKey)) {
        this.finishInlineEdit(textNode, layerData)
      }
      e.stopPropagation()
    })

    this.textInput.addEventListener('blur', () => {
      this.finishInlineEdit(textNode, layerData)
    })

    // Initial auto-resize
    autoResize()
  }

  /**
   * Finish inline text editing and commit changes
   */
  private finishInlineEdit(textNode: Konva.Text, layerData: LayerNode): void {
    if (!this.textInput || !this.editingLayer) return

    const newText = this.textInput.value
    
    // Update the text
    textNode.text(newText)
    textNode.visible(true)
    
    // Apply auto-resize if enabled
    const props = this.getTextProperties(layerData.properties as Partial<TextLayerProperties>)
    if (props.autoResize.enabled) {
      this.applyAutoResize(textNode, layerData, props.autoResize)
    }

    // Clean up
    document.body.removeChild(this.textInput)
    this.textInput = null
    this.editingLayer = null

    // Redraw
    textNode.getLayer()?.batchDraw()

    // Emit change event if text actually changed
    if (newText !== this.editStartText && this.eventEmitter) {
      this.eventEmitter.emit('layer:update', {
        layerId: layerData.id,
        properties: { text: newText }
      })
    }
  }

  /**
   * Cancel inline text editing without saving changes
   */
  private cancelInlineEdit(textNode: Konva.Text): void {
    if (!this.textInput || !this.editingLayer) return

    // Restore original text
    textNode.text(this.editStartText)
    textNode.visible(true)

    // Clean up
    document.body.removeChild(this.textInput)
    this.textInput = null
    this.editingLayer = null

    // Redraw
    textNode.getLayer()?.batchDraw()
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

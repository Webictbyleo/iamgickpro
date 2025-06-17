/**
 * Geometric utility types
 */
export interface Dimensions {
  width: number
  height: number
}

export interface Position {
  x: number
  y: number
}

export interface Rectangle extends Position, Dimensions {}

export interface ResizeOptions {
  mode: 'width' | 'height' | 'cover' | 'contain' | 'fill' | 'scale-down'
  aspectRatio?: number // Optional override for aspect ratio
  maxWidth?: number
  maxHeight?: number
  minWidth?: number
  minHeight?: number
  allowUpscaling?: boolean
  center?: boolean // Whether to center the result
}

export interface ResizeResult extends Dimensions {
  scale: number
  aspectRatio: number
  position?: Position // If centering was applied
  overflow?: {
    x: number
    y: number
  }
}

/**
 * Comprehensive utility class for geometric calculations and resizing operations
 * Supports various resize modes while maintaining aspect ratios and constraints
 */
export class GeometryUtils {
  
  /**
   * Calculate aspect ratio from dimensions
   */
  static getAspectRatio(dimensions: Dimensions): number {
    return dimensions.width / dimensions.height
  }

  /**
   * Check if dimensions are landscape (wider than tall)
   */
  static isLandscape(dimensions: Dimensions): boolean {
    return dimensions.width > dimensions.height
  }

  /**
   * Check if dimensions are portrait (taller than wide)
   */
  static isPortrait(dimensions: Dimensions): boolean {
    return dimensions.height > dimensions.width
  }

  /**
   * Check if dimensions are square
   */
  static isSquare(dimensions: Dimensions): boolean {
    return dimensions.width === dimensions.height
  }

  /**
   * Calculate dimensions from width while maintaining aspect ratio
   */
  static dimensionsFromWidth(width: number, aspectRatio: number): Dimensions {
    return {
      width,
      height: width / aspectRatio
    }
  }

  /**
   * Calculate dimensions from height while maintaining aspect ratio
   */
  static dimensionsFromHeight(height: number, aspectRatio: number): Dimensions {
    return {
      width: height * aspectRatio,
      height
    }
  }

  /**
   * Comprehensive resize function supporting multiple modes
   */
  static resize(
    sourceDimensions: Dimensions,
    targetDimensions: Dimensions,
    options: ResizeOptions = { mode: 'contain' }
  ): ResizeResult {
    const {
      mode,
      aspectRatio: overrideAspectRatio,
      maxWidth,
      maxHeight,
      minWidth = 1,
      minHeight = 1,
      allowUpscaling = true,
      center = false
    } = options

    // Use provided aspect ratio or calculate from source
    const sourceAspectRatio = overrideAspectRatio || this.getAspectRatio(sourceDimensions)
    const targetAspectRatio = this.getAspectRatio(targetDimensions)

    let result: Dimensions
    let scale: number

    switch (mode) {
      case 'width':
        result = this.resizeByWidth(sourceDimensions, targetDimensions.width, sourceAspectRatio)
        scale = result.width / sourceDimensions.width
        break

      case 'height':
        result = this.resizeByHeight(sourceDimensions, targetDimensions.height, sourceAspectRatio)
        scale = result.height / sourceDimensions.height
        break

      case 'cover':
        result = this.resizeCover(sourceDimensions, targetDimensions, sourceAspectRatio)
        scale = Math.max(
          result.width / sourceDimensions.width,
          result.height / sourceDimensions.height
        )
        break

      case 'contain':
        result = this.resizeContain(sourceDimensions, targetDimensions, sourceAspectRatio)
        scale = Math.min(
          result.width / sourceDimensions.width,
          result.height / sourceDimensions.height
        )
        break

      case 'fill':
        result = { ...targetDimensions }
        scale = Math.max(
          targetDimensions.width / sourceDimensions.width,
          targetDimensions.height / sourceDimensions.height
        )
        break

      case 'scale-down':
        // Like contain, but never scale up
        const containResult = this.resizeContain(sourceDimensions, targetDimensions, sourceAspectRatio)
        const containScale = Math.min(
          containResult.width / sourceDimensions.width,
          containResult.height / sourceDimensions.height
        )
        
        if (containScale > 1 && !allowUpscaling) {
          result = { ...sourceDimensions }
          scale = 1
        } else {
          result = containResult
          scale = containScale
        }
        break

      default:
        throw new Error(`Unsupported resize mode: ${mode}`)
    }

    // Apply constraints
    result = this.applyConstraints(result, {
      maxWidth,
      maxHeight,
      minWidth,
      minHeight
    })

    // Recalculate scale after constraints
    scale = Math.min(
      result.width / sourceDimensions.width,
      result.height / sourceDimensions.height
    )

    // Calculate position if centering is enabled
    let position: Position | undefined
    let overflow: { x: number; y: number } | undefined

    if (center || mode === 'cover') {
      position = {
        x: (targetDimensions.width - result.width) / 2,
        y: (targetDimensions.height - result.height) / 2
      }

      // Calculate overflow for cover mode
      if (mode === 'cover') {
        overflow = {
          x: Math.max(0, result.width - targetDimensions.width),
          y: Math.max(0, result.height - targetDimensions.height)
        }
      }
    }

    return {
      width: Math.round(result.width),
      height: Math.round(result.height),
      scale,
      aspectRatio: sourceAspectRatio,
      position,
      overflow
    }
  }

  /**
   * Resize by width, maintaining aspect ratio
   */
  private static resizeByWidth(
    sourceDimensions: Dimensions,
    targetWidth: number,
    aspectRatio: number
  ): Dimensions {
    return this.dimensionsFromWidth(targetWidth, aspectRatio)
  }

  /**
   * Resize by height, maintaining aspect ratio
   */
  private static resizeByHeight(
    sourceDimensions: Dimensions,
    targetHeight: number,
    aspectRatio: number
  ): Dimensions {
    return this.dimensionsFromHeight(targetHeight, aspectRatio)
  }

  /**
   * Resize to cover target area (may crop)
   */
  private static resizeCover(
    sourceDimensions: Dimensions,
    targetDimensions: Dimensions,
    aspectRatio: number
  ): Dimensions {
    const scaleX = targetDimensions.width / sourceDimensions.width
    const scaleY = targetDimensions.height / sourceDimensions.height
    const scale = Math.max(scaleX, scaleY)

    return {
      width: sourceDimensions.width * scale,
      height: sourceDimensions.height * scale
    }
  }

  /**
   * Resize to fit within target area (no cropping)
   */
  private static resizeContain(
    sourceDimensions: Dimensions,
    targetDimensions: Dimensions,
    aspectRatio: number
  ): Dimensions {
    const scaleX = targetDimensions.width / sourceDimensions.width
    const scaleY = targetDimensions.height / sourceDimensions.height
    const scale = Math.min(scaleX, scaleY)

    return {
      width: sourceDimensions.width * scale,
      height: sourceDimensions.height * scale
    }
  }

  /**
   * Apply dimension constraints
   */
  private static applyConstraints(
    dimensions: Dimensions,
    constraints: {
      maxWidth?: number
      maxHeight?: number
      minWidth?: number
      minHeight?: number
    }
  ): Dimensions {
    let { width, height } = dimensions
    const { maxWidth, maxHeight, minWidth = 1, minHeight = 1 } = constraints

    // Apply maximum constraints
    if (maxWidth && width > maxWidth) {
      const ratio = height / width
      width = maxWidth
      height = width * ratio
    }

    if (maxHeight && height > maxHeight) {
      const ratio = width / height
      height = maxHeight
      width = height * ratio
    }

    // Apply minimum constraints
    if (width < minWidth) {
      const ratio = height / width
      width = minWidth
      height = width * ratio
    }

    if (height < minHeight) {
      const ratio = width / height
      height = minHeight
      width = height * ratio
    }

    return { width, height }
  }

  /**
   * Calculate optimal size for canvas/viewport
   */
  static calculateOptimalViewportSize(
    contentDimensions: Dimensions,
    viewportDimensions: Dimensions,
    options: {
      padding?: number
      minZoom?: number
      maxZoom?: number
      preferredZoom?: number
    } = {}
  ): ResizeResult & { zoom: number } {
    const {
      padding = 40,
      minZoom = 0.1,
      maxZoom = 5,
      preferredZoom = 1
    } = options

    // Calculate available viewport space (minus padding)
    const availableViewport = {
      width: viewportDimensions.width - (padding * 2),
      height: viewportDimensions.height - (padding * 2)
    }

    // Calculate zoom to fit content in viewport
    const fitZoom = Math.min(
      availableViewport.width / contentDimensions.width,
      availableViewport.height / contentDimensions.height
    )

    // Choose optimal zoom level
    let zoom = preferredZoom

    // If preferred zoom doesn't fit, use fit zoom
    if (preferredZoom > fitZoom) {
      zoom = fitZoom
    }

    // Apply zoom constraints
    zoom = Math.max(minZoom, Math.min(maxZoom, zoom))

    // Calculate final dimensions
    const finalDimensions = {
      width: contentDimensions.width * zoom,
      height: contentDimensions.height * zoom
    }

    // Calculate centering position
    const position = {
      x: (viewportDimensions.width - finalDimensions.width) / 2,
      y: (viewportDimensions.height - finalDimensions.height) / 2
    }

    return {
      width: finalDimensions.width,
      height: finalDimensions.height,
      scale: zoom,
      aspectRatio: this.getAspectRatio(contentDimensions),
      position,
      zoom
    }
  }

  /**
   * Calculate responsive breakpoints for a design
   */
  static calculateResponsiveBreakpoints(
    baseDimensions: Dimensions,
    targetSizes: Array<{ name: string; maxWidth: number; maxHeight?: number }>
  ): Array<{ name: string; dimensions: Dimensions; scale: number }> {
    return targetSizes.map(target => {
      const constraints = {
        width: target.maxWidth,
        height: target.maxHeight || target.maxWidth / this.getAspectRatio(baseDimensions)
      }

      const result = this.resize(baseDimensions, constraints, {
        mode: 'contain',
        allowUpscaling: false
      })

      return {
        name: target.name,
        dimensions: { width: result.width, height: result.height },
        scale: result.scale
      }
    })
  }

  /**
   * Calculate grid layout dimensions
   */
  static calculateGridLayout(
    containerDimensions: Dimensions,
    itemCount: number,
    options: {
      aspectRatio?: number
      minItemWidth?: number
      maxItemWidth?: number
      gap?: number
      columns?: number
    } = {}
  ): {
    columns: number
    rows: number
    itemDimensions: Dimensions
    totalDimensions: Dimensions
    gap: number
  } {
    const {
      aspectRatio = 1,
      minItemWidth = 100,
      maxItemWidth = 300,
      gap = 16,
      columns: fixedColumns
    } = options

    let columns: number
    let itemWidth: number

    if (fixedColumns) {
      columns = fixedColumns
      itemWidth = (containerDimensions.width - (gap * (columns - 1))) / columns
    } else {
      // Calculate optimal columns based on container width and item constraints
      columns = Math.floor((containerDimensions.width + gap) / (minItemWidth + gap))
      columns = Math.max(1, columns)
      
      itemWidth = (containerDimensions.width - (gap * (columns - 1))) / columns
      
      // Ensure item width doesn't exceed maximum
      if (itemWidth > maxItemWidth) {
        itemWidth = maxItemWidth
        columns = Math.floor((containerDimensions.width + gap) / (itemWidth + gap))
      }
    }

    const itemHeight = itemWidth / aspectRatio
    const rows = Math.ceil(itemCount / columns)

    const totalWidth = (itemWidth * columns) + (gap * (columns - 1))
    const totalHeight = (itemHeight * rows) + (gap * (rows - 1))

    return {
      columns,
      rows,
      itemDimensions: { width: itemWidth, height: itemHeight },
      totalDimensions: { width: totalWidth, height: totalHeight },
      gap
    }
  }

  /**
   * Utility function to fit text within given dimensions
   */
  static calculateOptimalFontSize(
    text: string,
    containerDimensions: Dimensions,
    options: {
      fontFamily?: string
      maxFontSize?: number
      minFontSize?: number
      lineHeight?: number
      padding?: number
    } = {}
  ): { fontSize: number; fits: boolean } {
    const {
      fontFamily = 'Arial',
      maxFontSize = 72,
      minFontSize = 8,
      lineHeight = 1.2,
      padding = 0
    } = options

    const availableWidth = containerDimensions.width - (padding * 2)
    const availableHeight = containerDimensions.height - (padding * 2)

    // Binary search for optimal font size
    let low = minFontSize
    let high = maxFontSize
    let bestFontSize = minFontSize

    while (low <= high) {
      const fontSize = Math.floor((low + high) / 2)
      const textMetrics = this.measureText(text, fontSize, fontFamily, lineHeight)

      if (textMetrics.width <= availableWidth && textMetrics.height <= availableHeight) {
        bestFontSize = fontSize
        low = fontSize + 1
      } else {
        high = fontSize - 1
      }
    }

    const finalMetrics = this.measureText(text, bestFontSize, fontFamily, lineHeight)
    const fits = finalMetrics.width <= availableWidth && finalMetrics.height <= availableHeight

    return { fontSize: bestFontSize, fits }
  }

  /**
   * Measure text dimensions (approximate)
   */
  private static measureText(
    text: string,
    fontSize: number,
    fontFamily: string,
    lineHeight: number
  ): Dimensions {
    // This is a simplified measurement - in a real implementation,
    // you'd want to use Canvas measureText or create a DOM element
    const avgCharWidth = fontSize * 0.6 // Approximate average character width
    const lines = text.split('\n')
    const maxLineLength = Math.max(...lines.map(line => line.length))
    
    return {
      width: maxLineLength * avgCharWidth,
      height: lines.length * fontSize * lineHeight
    }
  }
}

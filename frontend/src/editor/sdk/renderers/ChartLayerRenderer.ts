import Konva from 'konva'
import type { KonvaLayerRenderer, LayerNode } from '../types'
import type { Layer, ChartLayerProperties, ChartData, ChartDataset, ChartOptions, ChartTheme, ScatterDataPoint, BubbleDataPoint } from '../../../types'

/**
 * Chart Layer Renderer - renders chart layers using Konva shapes and text
 * Supports various chart types: bar, line, pie, doughnut, area, scatter, bubble
 */
export class ChartLayerRenderer implements KonvaLayerRenderer {
  canRender(layer: Layer): boolean {
    return layer.type === 'chart'
  }

  render(layer: LayerNode): Konva.Node {
    const props = this.getChartProperties(layer.properties as ChartLayerProperties)
    const chartGroup = new Konva.Group({
      x: layer.x,
      y: layer.y,
      width: layer.width,
      height: layer.height
    })
   
    this.renderChart(chartGroup, props, layer.width, layer.height)
    this.applyCommonProperties(chartGroup, layer)
    this.setupInteractions(chartGroup, layer)
    
    return chartGroup
  }

  update(node: Konva.Node, layer: LayerNode): void {
    if (!(node instanceof Konva.Group)) return

    const props = this.getChartProperties(layer.properties as ChartLayerProperties)
    // Clear existing chart content
    node.destroyChildren()
    
    // Re-render chart with layer dimensions (same as shape renderer approach)
    this.renderChart(node, props, layer.width, layer.height)
    
    // Apply common properties (this will set position, size, and other properties from layer)
    this.applyCommonProperties(node, layer)
    
    // Re-setup interactions (now safe - won't remove transform handle listeners)
    this.setupInteractions(node, layer)
  }

  destroy(node: Konva.Node): void {
    node.destroy()
  }

  getSupportedTypes(): string[] {
    return ['chart']
  }

  /**
   * Set up interactions for chart elements including hover effects and context menu
   */
  private setupInteractions(group: Konva.Group, layer: LayerNode): void {
    // Only clear specific event handlers to avoid removing transform handle listeners
    // DO NOT use group.off() as it removes ALL handlers including transform handles
    group.off('dragstart.chart')
    group.off('dragend.chart')
    group.off('mouseenter.chart')
    group.off('mouseleave.chart')
    group.off('click.chart')
    group.off('tap.chart')
    group.off('contextmenu.chart')
    
    // Drag event handlers for canvas movement
    group.on('dragstart.chart', () => {
      const container = group.getStage()?.container()
      if (container) container.style.cursor = 'grabbing'
    })

    group.on('dragend.chart', () => {
      const container = group.getStage()?.container()
      if (container) container.style.cursor = 'default'
    })

    // Hover effects for better UX
    group.on('mouseenter.chart', () => {
      if (!layer.locked) {
        const container = group.getStage()?.container()
        if (container) container.style.cursor = 'grab'
      }
    })

    group.on('mouseleave.chart', () => {
      const container = group.getStage()?.container()
      if (container) container.style.cursor = 'default'
    })

    // Click handling for selection (similar to other renderers)
    group.on('click.chart tap.chart', (e) => {
      e.cancelBubble = true
      // Chart selections will be handled by the EditorSDK layer selection system
    })

    // Context menu handling (following EditorSDK pattern)
    group.on('contextmenu.chart', (e) => {
      e.evt.preventDefault()
      e.cancelBubble = true
      
      // Create a custom event that the EditorSDK will capture
      // This follows the same pattern as in EditorSDK.ts
      const stage = group.getStage()
      if (stage) {
        const customEvent = new CustomEvent('editor:context-menu', {
          detail: {
            event: e.evt,
            layer: {
              id: layer.id,
              type: layer.type,
              x: layer.x,
              y: layer.y,
              width: layer.width,
              height: layer.height,
              rotation: layer.rotation,
              scaleX: layer.scaleX,
              scaleY: layer.scaleY,
              zIndex: layer.zIndex,
              properties: layer.properties
            },
            position: {
              x: e.evt instanceof MouseEvent ? e.evt.clientX : 0,
              y: e.evt instanceof MouseEvent ? e.evt.clientY : 0
            }
          }
        })
        
        // Dispatch to the document for the DesignCanvas to handle
        document.dispatchEvent(customEvent)
      }
    })
  }

  /**
   * Get chart properties with defaults
   */
  private getChartProperties(properties: Partial<ChartLayerProperties>): ChartLayerProperties {
    return {
      chartType: properties.chartType || 'bar',
      data: this.getDefaultData(properties.data),
      options: this.getDefaultOptions(properties.options),
      theme: this.getDefaultTheme(properties.theme)
    }
  }

  /**
   * Get default chart data
   */
  // Type guards for different data formats
  private isScatterData(data: ChartDataset['data']): data is ScatterDataPoint[] {
    return Array.isArray(data) && data.length > 0 && typeof data[0] === 'object' && 'x' in data[0] && 'y' in data[0] && !('r' in data[0])
  }

  private isBubbleData(data: ChartDataset['data']): data is BubbleDataPoint[] {
    return Array.isArray(data) && data.length > 0 && typeof data[0] === 'object' && 'x' in data[0] && 'y' in data[0] && 'r' in data[0]
  }

  private isNumericData(data: ChartDataset['data']): data is number[] {
    return Array.isArray(data) && (data.length === 0 || typeof data[0] === 'number')
  }

  private getDefaultData(data?: ChartData): ChartData {
    return data || {
      labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May'],
      datasets: [{
        label: 'Sample Data',
        data: [10, 20, 15, 25, 30],
        backgroundColor: '#3B82F6', // Will be overridden by theme
        borderColor: '#2563EB', // Primary-600 (darker variant)
        borderWidth: 2
      }]
    }
  }

  /**
   * Get default chart options with enhanced styling
   */
  private getDefaultOptions(options?: ChartOptions): ChartOptions {
    return {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          display: true,
          position: 'top'
        },
        title: {
          display: false,
          text: 'Chart'
        },
        tooltip: {
          enabled: true
        }
      },
      scales: {
        x: {
          display: true,
          grid: {
            display: true,
            color: '#F1F5F9' // Lighter grid color for better aesthetics
          }
        },
        y: {
          display: true,
          grid: {
            display: true,
            color: '#F1F5F9' // Lighter grid color for better aesthetics
          }
        }
      },
      animation: {
        duration: 1000,
        easing: 'easeInOutQuad'
      },
      ...options
    }
  }

  /**
   * Get default chart theme with rich color palette
   */
  private getDefaultTheme(theme?: ChartTheme): ChartTheme {
    return {
      primary: '#3B82F6', // Primary-500
      secondary: '#8B5CF6', // Secondary-500
      background: '#FFFFFF',
      text: '#1F2937', // Gray-800
      grid: '#E5E7EB', // Gray-200
      accent: [
        '#EF4444', // Red-500
        '#F59E0B', // Amber-500
        '#10B981', // Emerald-500
        '#F97316', // Orange-500
        '#8B5CF6', // Purple-500
        '#EC4899', // Pink-500
        '#06B6D4', // Cyan-500
        '#84CC16', // Lime-500
        '#F43F5E', // Rose-500
        '#6366F1', // Indigo-500
        '#8B5CF6', // Violet-500
        '#14B8A6'  // Teal-500
      ],
      tooltip: {
        background: '#1F2937', // Gray-800
        text: '#FFFFFF'
      },
      ...theme
    }
  }

  /**
   * Main chart rendering method
   */
  private renderChart(group: Konva.Group, props: ChartLayerProperties, width: number, height: number): void {
    const { chartType, data, options, theme } = props

    // Calculate responsive font scaling based on chart size
    // Base size is 400x300, scale font sizes proportionally
    const baseWidth = 400
    const baseHeight = 300
    const scaleX = width / baseWidth
    const scaleY = height / baseHeight
    const fontScale = Math.min(scaleX, scaleY) // Use minimum to prevent oversized text
    
    // Store font scale in the group for use by rendering methods
    ;(group as any)._fontScale = Math.max(0.5, Math.min(fontScale, 3)) // Clamp between 0.5x and 3x

    // Calculate chart area (excluding legend and title space)
    const margins = this.calculateMargins(options, width, height)
    const chartArea = {
      x: margins.left,
      y: margins.top,
      width: width - margins.left - margins.right,
      height: height - margins.top - margins.bottom
    }

    // Render background (skip for pie/doughnut charts to allow transparent center)
    if (theme.background !== 'transparent' && !['pie', 'doughnut'].includes(chartType)) {
      const background = new Konva.Rect({
        x: 0,
        y: 0,
        width: width,
        height: height,
        fill: theme.background
      })
      group.add(background)
    }

    // Render title
    if (options.plugins?.title?.display) {
      this.renderTitle(group, options.plugins.title, theme, width)
    }

    // Render chart based on type
    switch (chartType) {
      case 'bar':
        this.renderBarChart(group, data, chartArea, theme, options)
        break
      case 'line':
        this.renderLineChart(group, data, chartArea, theme, options)
        break
      case 'pie':
        this.renderPieChart(group, data, chartArea, theme, options)
        break
      case 'doughnut':
        this.renderDoughnutChart(group, data, chartArea, theme, options)
        break
      case 'area':
        this.renderAreaChart(group, data, chartArea, theme, options)
        break
      case 'scatter':
        this.renderScatterChart(group, data, chartArea, theme, options)
        break
      case 'bubble':
        this.renderBubbleChart(group, data, chartArea, theme, options)
        break
    }

    // Render legend
    if (options.plugins?.legend?.display) {
      this.renderLegend(group, data, options.plugins.legend, theme, width, height)
    }

    // Render axes for charts that need them
    if (['bar', 'line', 'area', 'scatter', 'bubble'].includes(chartType)) {
      this.renderAxes(group, data, chartArea, theme, options, chartType)
    }
  }

  /**
   * Calculate margins for chart area
   */
  private calculateMargins(options: ChartOptions, width: number, height: number) {
    let top = 20
    let bottom = 50 // Increased for better X-axis label spacing
    let left = 70 // Increased for better Y-axis label spacing
    let right = 30 // Increased for potential overflow

    if (options.plugins?.title?.display) {
      top += 40
    }

    if (options.plugins?.legend?.display) {
      const position = options.plugins.legend.position || 'top'
      switch (position) {
        case 'top':
          top += 40
          break
        case 'bottom':
          bottom += 40
          break
        case 'left':
          left += 80
          break
        case 'right':
          right += 80
          break
      }
    }

    return { top, bottom, left, right }
  }

  /**
   * Render chart title
   */
  private renderTitle(group: Konva.Group, title: any, theme: ChartTheme, width: number): void {
    const fontScale = (group as any)._fontScale || 1
    const titleText = new Konva.Text({
      x: 0,
      y: 10,
      width: width,
      text: title.text,
      fontSize: (title.font?.size || 16) * fontScale,
      fontStyle: title.font?.weight || 'bold',
      fill: theme.text,
      align: 'center'
    })
    group.add(titleText)
  }

  /**
   * Render bar chart
   */
  private renderBarChart(group: Konva.Group, data: ChartData, chartArea: any, theme: ChartTheme, options: ChartOptions): void {
    const { labels, datasets } = data
    
    // Only use numeric data for bar charts
    const numericDatasets = datasets.filter(d => this.isNumericData(d.data))
    const maxValue = Math.max(...numericDatasets.flatMap(d => d.data as number[]))
    const scale = chartArea.height / maxValue

    // Calculate proper spacing within chartArea bounds
    const categoryCount = labels.length
    const datasetCount = numericDatasets.length
    
    // Available width for all bars (with some padding)
    const availableWidth = chartArea.width * 0.9 // Leave 10% padding
    const categoryWidth = availableWidth / categoryCount // Width per category group
    const barGroupWidth = categoryWidth * 0.8 // 80% of category width for bars, 20% for spacing
    const barWidth = barGroupWidth / datasetCount // Width per individual bar
    
    // Calculate starting position to center all bars within chartArea
    const startOffset = (chartArea.width - availableWidth) / 2

    numericDatasets.forEach((dataset, datasetIndex) => {
      const numericData = dataset.data as number[]
      numericData.forEach((value, index) => {
        // Calculate the center position for this category
        const categoryCenter = startOffset + (index + 0.5) * categoryWidth
        
        // Calculate the starting position for the bar group
        const barGroupStart = categoryCenter - (barGroupWidth / 2)
        
        // Calculate individual bar position
        const x = chartArea.x + barGroupStart + (datasetIndex * barWidth)
        
        const barHeight = value * scale
        const y = chartArea.y + chartArea.height - barHeight

        // Get primary color for this bar
        const primaryColor = this.getColor(dataset.backgroundColor, datasetIndex, theme)
        const borderColor = this.getColor(dataset.borderColor, datasetIndex, theme)
        
        // Create gradient effect for bars
        const lighterColor = this.adjustColorBrightness(primaryColor, 0.1)
        const darkerColor = this.adjustColorBrightness(primaryColor, -0.1)

        const bar = new Konva.Rect({
          x: x,
          y: y,
          width: barWidth * 0.95, // Small gap between bars in same group
          height: barHeight,
          fillLinearGradientStartPoint: { x: 0, y: 0 },
          fillLinearGradientEndPoint: { x: 0, y: barHeight },
          fillLinearGradientColorStops: [0, lighterColor, 1, darkerColor],
          stroke: borderColor,
          strokeWidth: dataset.borderWidth || 0,
          shadowColor: 'rgba(0, 0, 0, 0.1)',
          shadowBlur: 2,
          shadowOffset: { x: 0, y: 1 },
          cornerRadius: 2 // Add subtle rounded corners
        })

        // Add hover effect
        bar.on('mouseenter', () => {
          bar.shadowBlur(4)
          bar.shadowOffset({ x: 0, y: 2 })
          bar.opacity(0.9)
        })

        bar.on('mouseleave', () => {
          bar.shadowBlur(2)
          bar.shadowOffset({ x: 0, y: 1 })
          bar.opacity(1)
        })

        group.add(bar)
      })
    })
  }

  /**
   * Render line chart
   */
  private renderLineChart(group: Konva.Group, data: ChartData, chartArea: any, theme: ChartTheme, options: ChartOptions): void {
    const { labels, datasets } = data
    const pointSpacing = chartArea.width / (labels.length - 1)
    
    // Only use numeric data for line charts
    const numericDatasets = datasets.filter(d => this.isNumericData(d.data))
    const maxValue = Math.max(...numericDatasets.flatMap(d => d.data as number[]))
    const scale = chartArea.height / maxValue

    numericDatasets.forEach((dataset, datasetIndex) => {
      const points: number[] = []
      const numericData = dataset.data as number[]
      
      numericData.forEach((value, index) => {
        const x = chartArea.x + index * pointSpacing
        const y = chartArea.y + chartArea.height - (value * scale)
        points.push(x, y)
      })

      // Get enhanced colors
      const lineColor = this.getColor(dataset.borderColor, datasetIndex, theme)
      const pointColor = this.getColor(dataset.pointBackgroundColor, datasetIndex, theme)
      
      // Draw line with shadow for depth
      const line = new Konva.Line({
        points: points,
        stroke: lineColor,
        strokeWidth: dataset.borderWidth || 2,
        tension: dataset.tension || 0,
        shadowColor: 'rgba(0, 0, 0, 0.1)',
        shadowBlur: 3,
        shadowOffset: { x: 0, y: 1 }
      })
      group.add(line)

      // Draw enhanced points with hover effects
      if (dataset.pointRadius && dataset.pointRadius > 0) {
        for (let i = 0; i < points.length; i += 2) {
          const circle = new Konva.Circle({
            x: points[i],
            y: points[i + 1],
            radius: dataset.pointRadius,
            fill: pointColor,
            stroke: this.getColor(dataset.pointBorderColor, datasetIndex, theme),
            strokeWidth: 1,
            shadowColor: 'rgba(0, 0, 0, 0.1)',
            shadowBlur: 2,
            shadowOffset: { x: 0, y: 1 }
          })
          
          // Add hover effect
          circle.on('mouseenter', () => {
            circle.radius(dataset.pointRadius! * 1.3)
            circle.shadowBlur(4)
            circle.shadowOffset({ x: 0, y: 2 })
          })
          
          circle.on('mouseleave', () => {
            circle.radius(dataset.pointRadius!)
            circle.shadowBlur(2)
            circle.shadowOffset({ x: 0, y: 1 })
          })
          
          group.add(circle)
        }
      }
    })
  }

  /**
   * Render pie chart (solid circle without hole)
   */
  private renderPieChart(group: Konva.Group, data: ChartData, chartArea: any, theme: ChartTheme, options: ChartOptions): void {
    const centerX = chartArea.x + chartArea.width / 2
    const centerY = chartArea.y + chartArea.height / 2
    const radius = Math.min(chartArea.width, chartArea.height) / 2 - 10
    const innerRadius =  0 // Increased from 0.4 to 0.5 for more visible hole

    // Store slice data for better label positioning
    const sliceData: Array<{
      index: number
      label: string
      value: number
      percentage: string
      midAngle: number
      sliceAngle: number
      color: string
    }> = []

    // Create a separate group for tooltips that won't be clipped
    const tooltipGroup = new Konva.Group()

    // Get responsive font scale for tooltips
    const fontScale = (group as any)._fontScale || 1

    // Assuming single dataset for pie charts
    const dataset = data.datasets[0]
    
    // Only use numeric data for pie charts
    if (!this.isNumericData(dataset.data)) {
      return
    }
    
    const numericData = dataset.data as number[]
    const total = numericData.reduce((sum, value) => sum + value, 0)
    let currentAngle = -Math.PI / 2 // Start at top

    numericData.forEach((value, index) => {
      const sliceAngle = (value / total) * 2 * Math.PI
      const endAngle = currentAngle + sliceAngle

      let slice: Konva.Shape

        // Use Wedge for pie charts
        slice = new Konva.Wedge({
          x: centerX,
          y: centerY,
          innerRadius: 0,
          radius: radius,
          angle: sliceAngle * (180 / Math.PI),
          rotation: currentAngle * (180 / Math.PI),
          fill: this.getColor(dataset.backgroundColor, index, theme),
          stroke: theme.background,
          strokeWidth: 2
        })
      

      group.add(slice)

      // Create interactive tooltip for this slice
      const midAngle = currentAngle + sliceAngle / 2
      const tooltipX = centerX + Math.cos(midAngle) * (radius * 0.7)
      const tooltipY = centerY + Math.sin(midAngle) * (radius * 0.7)
      const sliceLabel = data.labels[index] || `Slice ${index + 1}`
      const percentage = ((value / total) * 100).toFixed(1)
      
      const tooltip = this.createTooltip(
        tooltipGroup, 
        tooltipX, 
        tooltipY, 
        -30, 
        `${sliceLabel}: ${percentage}% (${this.formatAxisValue(value, 1)})`, 
        theme, 
        fontScale
      )
      tooltipGroup.add(tooltip)

      // Add interactive hover effects to the slice
      slice.on('mouseenter', () => {
        // Expand slice slightly on hover (cast to Wedge to access radius property)
        const wedge = slice as Konva.Wedge
        wedge.radius(radius + 5)
        wedge.strokeWidth(3)
        wedge.moveToTop()
        tooltip.visible(true)
        tooltip.moveToTop()
        // Move the entire tooltip group to top to ensure it's above all slices
        tooltipGroup.moveToTop()
        
        // Disable tooltip listening to prevent mouse events from interfering
        tooltip.listening(false)
        
        // Change cursor to pointer to indicate interactivity
        const container = slice.getStage()?.container()
        if (container) container.style.cursor = 'pointer'
      })

      slice.on('mouseleave', () => {
        // Return slice to normal state
        const wedge = slice as Konva.Wedge
        wedge.radius(radius)
        wedge.strokeWidth(2)
        tooltip.visible(false)
        
        // Reset cursor
        const container = slice.getStage()?.container()
        if (container) container.style.cursor = 'grab'
      })

      // Store data for potential interaction
      slice.setAttr('dataPoint', { 
        label: sliceLabel,
        value: value,
        percentage: percentage,
        dataset: dataset.label,
        index: index
      })

      // Store slice data for label positioning
      sliceData.push({
        index,
        label: sliceLabel,
        value,
        percentage: percentage,
        midAngle: currentAngle + sliceAngle / 2,
        sliceAngle,
        color: this.getColor(dataset.backgroundColor, index, theme)
      })
      currentAngle = endAngle
    })

    // Add the tooltip group on top
    group.add(tooltipGroup)
  
    // Render labels with better positioning
    this.renderPieLabels(group, sliceData, centerX, centerY, radius, innerRadius, theme)
    
    // Ensure tooltip group stays on top after labels are rendered
    tooltipGroup.moveToTop()
  }

  /**
   * Render doughnut chart (pie chart with hollow center)
   */
  private renderDoughnutChart(group: Konva.Group, data: ChartData, chartArea: any, theme: ChartTheme, options: ChartOptions): void {
    const centerX = chartArea.x + chartArea.width / 2
    const centerY = chartArea.y + chartArea.height / 2
    const radius = Math.min(chartArea.width, chartArea.height) / 2 - 10
    const innerRadius = radius * 0.5 // 50% of outer radius for the hole

    // Store slice data for better label positioning
    const sliceData: Array<{
      index: number
      label: string
      value: number
      percentage: string
      midAngle: number
      sliceAngle: number
      color: string
    }> = []

    // Create a separate group for tooltips that won't be clipped
    const tooltipGroup = new Konva.Group()

    // Get responsive font scale for tooltips
    const fontScale = (group as any)._fontScale || 1

    // Assuming single dataset for doughnut charts
    const dataset = data.datasets[0]
    
    // Only use numeric data for doughnut charts
    if (!this.isNumericData(dataset.data)) {
      return
    }
    
    const numericData = dataset.data as number[]
    const total = numericData.reduce((sum, value) => sum + value, 0)
    let currentAngle = -Math.PI / 2 // Start at top

    // First, create the full outer circle
    const outerCircle = new Konva.Circle({
      x: centerX,
      y: centerY,
      radius: radius,
      fill: 'transparent',
      stroke: theme.grid,
      strokeWidth: 0
    })
    group.add(outerCircle)

    // Create a clipping group for the doughnut effect
    const clipGroup = new Konva.Group({
      clipFunc: (ctx) => {
        // Create a circular clipping path that excludes the center
        ctx.arc(centerX, centerY, radius, 0, Math.PI * 2, false)
        ctx.arc(centerX, centerY, innerRadius, 0, Math.PI * 2, true) // Inner circle (clockwise = true creates hole)
      }
    })

    // Store references to slices for interactive effects
    const sliceElements: Konva.Wedge[] = []

    // Create each slice as a solid wedge and add to clipping group
    numericData.forEach((value, index) => {
      const sliceAngle = (value / total) * 2 * Math.PI
      const endAngle = currentAngle + sliceAngle

      // Create the slice using Wedge WITHOUT innerRadius - this creates a solid slice like pie chart
      const slice = new Konva.Wedge({
        x: centerX,
        y: centerY,
        innerRadius: 0, // No inner radius - create solid wedge
        radius: radius,
        angle: sliceAngle * (180 / Math.PI),
        rotation: currentAngle * (180 / Math.PI),
        fill: this.getColor(dataset.backgroundColor, index, theme),
        stroke: theme.background,
        strokeWidth: 2
      })

      clipGroup.add(slice)
      sliceElements.push(slice)

      // Create interactive tooltip for this slice
      const midAngle = currentAngle + sliceAngle / 2
      const tooltipRadius = (radius + innerRadius) / 2 // Position tooltip in the middle of the doughnut ring
      const tooltipX = centerX + Math.cos(midAngle) * tooltipRadius
      const tooltipY = centerY + Math.sin(midAngle) * tooltipRadius
      const sliceLabel = data.labels[index] || `Slice ${index + 1}`
      const percentage = ((value / total) * 100).toFixed(1)
      
      const tooltip = this.createTooltip(
        tooltipGroup, 
        tooltipX, 
        tooltipY, 
        -30, 
        `${sliceLabel}: ${percentage}% (${this.formatAxisValue(value, 1)})`, 
        theme, 
        fontScale
      )
      tooltipGroup.add(tooltip)

      // Add interactive hover effects to the slice
      slice.on('mouseenter', () => {
        // Expand slice slightly on hover
        slice.radius(radius + 5)
        slice.strokeWidth(3)
        slice.moveToTop()
        tooltip.visible(true)
        tooltip.moveToTop()
        // Move the entire tooltip group to top to ensure it's above all slices
        tooltipGroup.moveToTop()
        
        // Change cursor to pointer to indicate interactivity
        const container = slice.getStage()?.container()
        if (container) container.style.cursor = 'pointer'
      })

      slice.on('mouseleave', () => {
        // Return slice to normal state
        slice.radius(radius)
        slice.strokeWidth(2)
        tooltip.visible(false)
        
        // Reset cursor
        const container = slice.getStage()?.container()
        if (container) container.style.cursor = 'grab'
      })

      // Store data for potential interaction
      slice.setAttr('dataPoint', { 
        label: sliceLabel,
        value: value,
        percentage: percentage,
        dataset: dataset.label,
        index: index
      })

      // Store slice data for label positioning
      sliceData.push({
        index,
        label: sliceLabel,
        value,
        percentage: percentage,
        midAngle: currentAngle + sliceAngle / 2,
        sliceAngle,
        color: this.getColor(dataset.backgroundColor, index, theme)
      })
      currentAngle = endAngle
    })

    // Add the clipped group to the main group
    group.add(clipGroup)

    // Add the tooltip group on top
    group.add(tooltipGroup)

    // Render labels using pie chart labeling logic
    this.renderPieLabels(group, sliceData, centerX, centerY, radius, innerRadius, theme)
    
    // Ensure tooltip group stays on top after labels are rendered
    tooltipGroup.moveToTop()
  }

  /**
   * Render area chart (line chart with filled area)
   */
  private renderAreaChart(group: Konva.Group, data: ChartData, chartArea: any, theme: ChartTheme, options: ChartOptions): void {
    const { labels, datasets } = data
    const pointSpacing = chartArea.width / (labels.length - 1)
    
    // Only use numeric data for area charts
    const numericDatasets = datasets.filter(d => this.isNumericData(d.data))
    const maxValue = Math.max(...numericDatasets.flatMap(d => d.data as number[]))
    const scale = chartArea.height / maxValue

    numericDatasets.forEach((dataset, datasetIndex) => {
      const points: number[] = []
      const numericData = dataset.data as number[]
      
      // Start from bottom left
      points.push(chartArea.x, chartArea.y + chartArea.height)
      
      numericData.forEach((value, index) => {
        const x = chartArea.x + index * pointSpacing
        const y = chartArea.y + chartArea.height - (value * scale)
        points.push(x, y)
      })

      // End at bottom right
      points.push(chartArea.x + chartArea.width, chartArea.y + chartArea.height)

      // Get enhanced colors for gradient effect
      const fillColor = this.getColor(dataset.backgroundColor, datasetIndex, theme, 0.3)
      const strokeColor = this.getColor(dataset.borderColor, datasetIndex, theme)
      
      // Create gradient fill for area
      const topY = Math.min(...points.filter((_, i) => i % 2 === 1))
      const bottomY = chartArea.y + chartArea.height
      
      // Create filled area with gradient
      const area = new Konva.Line({
        points: points,
        fillLinearGradientStartPoint: { x: 0, y: topY },
        fillLinearGradientEndPoint: { x: 0, y: bottomY },
        fillLinearGradientColorStops: [
          0, this.getColor(dataset.backgroundColor, datasetIndex, theme, 0.6),
          1, this.getColor(dataset.backgroundColor, datasetIndex, theme, 0.1)
        ],
        stroke: strokeColor,
        strokeWidth: dataset.borderWidth || 2,
        closed: true,
        tension: dataset.tension || 0,
        shadowColor: 'rgba(0, 0, 0, 0.1)',
        shadowBlur: 3,
        shadowOffset: { x: 0, y: 1 }
      })
      group.add(area)
    })
  }

  /**
   * Render scatter chart - Industry standard implementation with proper clipping
   */
  private renderScatterChart(group: Konva.Group, data: ChartData, chartArea: any, theme: ChartTheme, options: ChartOptions): void {
    const { labels, datasets } = data
    
    // For scatter charts, we need to handle different data formats:
    // 1. Array of objects: [{x: 1, y: 2}, {x: 3, y: 4}]
    // 2. Separate X/Y arrays (if xData is provided)
    // 3. Fallback: use labels as X values and data as Y values
    
    let allXValues: number[] = []
    let allYValues: number[] = []
    
    // Collect all X and Y values for proper scaling
    datasets.forEach((dataset, datasetIndex) => {
      if (Array.isArray(dataset.data) && dataset.data.length > 0) {
        // Check if data contains objects with x/y properties
        if (typeof dataset.data[0] === 'object' && dataset.data[0] !== null && 'x' in dataset.data[0] && 'y' in dataset.data[0]) {
          // Format: [{x: 1, y: 2}, {x: 3, y: 4}]
          dataset.data.forEach((point: any) => {
            allXValues.push(point.x)
            allYValues.push(point.y)
          })
        } else {
          // Fallback: use labels as X values, data as Y values
          // This handles the case where we have category-style data
          const numericData = dataset.data as number[]
          numericData.forEach((yValue: number, index: number) => {
            // Try to parse label as number, otherwise use index
            const xValue = labels[index] ? (isNaN(Number(labels[index])) ? index : Number(labels[index])) : index
            allXValues.push(xValue)
            allYValues.push(yValue)
          })
        }
      }
    })
    
    if (allXValues.length === 0 || allYValues.length === 0) {
      console.warn('Scatter chart: No valid data points found')
      return
    }
    
    // Calculate raw data range
    const dataMinX = Math.min(...allXValues)
    const dataMaxX = Math.max(...allXValues)
    const dataMinY = Math.min(...allYValues)
    const dataMaxY = Math.max(...allYValues)
    
    // Generate nice axis values starting from 0 (industry standard for scatter charts)
    const niceXValues = this.generateNiceAxisValues(dataMinX, dataMaxX, 5, true) // Include 0 for X-axis
    const niceYValues = this.generateNiceAxisValues(dataMinY, dataMaxY, 5, true) // Include 0 for Y-axis
    
    // Use the nice axis bounds as the actual chart bounds
    const xMin = niceXValues[0]
    const xMax = niceXValues[niceXValues.length - 1]
    const yMin = niceYValues[0]
    const yMax = niceYValues[niceYValues.length - 1]
    
    // Calculate scales based on nice bounds
    const xScale = chartArea.width / (xMax - xMin)
    const yScale = chartArea.height / (yMax - yMin)
    
    // Calculate responsive point radius based on chart size
    const fontScale = (group as any)._fontScale || 1
    const basePointRadius = 5
    const responsivePointRadius = Math.max(2, basePointRadius * fontScale)

    // Create a clipping group to ensure points don't render outside chart area
    const pointsGroup = new Konva.Group({
      clipFunc: (ctx) => {
        ctx.rect(chartArea.x, chartArea.y, chartArea.width, chartArea.height)
      }
    })
    
    // Create a separate group for tooltips that won't be clipped
    const tooltipGroup = new Konva.Group()
    
    // Render data points for each dataset
    datasets.forEach((dataset, datasetIndex) => {
      if (!Array.isArray(dataset.data)) return
      
      dataset.data.forEach((dataPoint: any, index: number) => {
        let xValue: number, yValue: number, pointLabel: string
        
        // Extract X and Y coordinates based on data format
        if (typeof dataPoint === 'object' && dataPoint !== null && 'x' in dataPoint && 'y' in dataPoint) {
          xValue = dataPoint.x
          yValue = dataPoint.y
          // Generate a meaningful label for scatter points
          pointLabel = `Point ${index + 1}: (${xValue}, ${yValue})`
        } else {
          // Fallback format
          const labelValue = labels[index]
          xValue = labelValue ? (isNaN(Number(labelValue)) ? index : Number(labelValue)) : index
          yValue = dataPoint
          pointLabel = labelValue || `Point ${index + 1}: (${xValue}, ${yValue})`
        }
        
        // Calculate screen coordinates - ensure they're within chart bounds
        const x = chartArea.x + (xValue - xMin) * xScale
        const y = chartArea.y + chartArea.height - (yValue - yMin) * yScale
        
        // Skip points that are outside the visible data range
        if (x < chartArea.x || x > chartArea.x + chartArea.width || 
            y < chartArea.y || y > chartArea.y + chartArea.height) {
          return
        }
        
        // Get point styling with proper responsive defaults
        const pointRadius = dataset.pointRadius ? dataset.pointRadius * fontScale : responsivePointRadius
        const pointColor = this.getColor(dataset.pointBackgroundColor || dataset.backgroundColor, datasetIndex, theme)
        const borderColor = this.getColor(dataset.pointBorderColor || dataset.borderColor, datasetIndex, theme)
        const borderWidth = (dataset.borderWidth || 1) * fontScale
        
        // Create scatter point
        const point = new Konva.Circle({
          x: x,
          y: y,
          radius: pointRadius,
          fill: pointColor,
          stroke: borderColor,
          strokeWidth: borderWidth,
          opacity: 0.8 // Slight transparency for overlapping points
        })
        
        pointsGroup.add(point)
        
        // Create responsive tooltip for hover effect
        const tooltip = this.createTooltip(tooltipGroup, x, y, -40, `(${this.formatAxisValue(xValue, 1)}, ${this.formatAxisValue(yValue, 1)})`, theme, fontScale)
        tooltipGroup.add(tooltip)
        
        // Add professional hover effect with tooltip
        point.on('mouseenter', () => {
          point.radius(pointRadius * 1.3) // More noticeable hover
          point.opacity(1)
          point.strokeWidth(borderWidth + 1)
          point.moveToTop() // Bring to front
          tooltip.visible(true)
          tooltip.moveToTop()
        })
        
        point.on('mouseleave', () => {
          point.radius(pointRadius)
          point.opacity(0.8)
          point.strokeWidth(borderWidth)
          tooltip.visible(false)
        })
        
        // Store data for potential tooltip/interaction
        point.setAttr('dataPoint', { 
          x: xValue, 
          y: yValue, 
          label: pointLabel,
          dataset: dataset.label 
        })
      })
    })
    
    // Add the clipped points group to the main group
    group.add(pointsGroup)
    
    // Add the unclipped tooltip group on top so tooltips can render outside chart area
    group.add(tooltipGroup)
    
    // Store scale information for axis rendering with nice values
    ;(group as any)._scatterScales = {
      xMin, xMax, yMin, yMax,
      niceXValues, niceYValues
    }
  }

  /**
   * Create a responsive tooltip for scatter and bubble charts with smart positioning
   */
  private createTooltip(
    group: Konva.Group,
    x: number,
    y: number,
    offsetY: number,
    tooltipText: string,
    theme: ChartTheme,
    fontScale: number = 1
  ): Konva.Group {
    // Calculate tooltip dimensions based on content
    const tooltipWidth = Math.max(100, tooltipText.length * 6 * fontScale)
    const tooltipHeight = 30 * fontScale
    
    // Get the parent group to determine chart boundaries
    const parentGroup = group.getParent()
    const chartWidth = parentGroup ? parentGroup.width() : 400
    const chartHeight = parentGroup ? parentGroup.height() : 300
    
    // Smart positioning logic to avoid chart edges
    let finalX = x
    let finalY = y + offsetY
    let finalOffsetX = 0
    
    // Check if tooltip would extend beyond right edge
    if (x + tooltipWidth / 2 > chartWidth - 10) {
      finalOffsetX = -tooltipWidth / 2 - 10 // Position to the left of point
    }
    // Check if tooltip would extend beyond left edge
    else if (x - tooltipWidth / 2 < 10) {
      finalOffsetX = tooltipWidth / 2 + 10 // Position to the right of point
    }
    
    // Check if tooltip would extend beyond top edge
    if (finalY - tooltipHeight / 2 < 10) {
      finalY = y + Math.abs(offsetY) + 10 // Position below point instead
    }
    // Check if tooltip would extend beyond bottom edge
    else if (finalY + tooltipHeight / 2 > chartHeight - 10) {
      finalY = y - Math.abs(offsetY) - 10 // Position above point
    }
    
    const tooltip = new Konva.Group({
      x: finalX + finalOffsetX,
      y: finalY,
      visible: false
    })
    
    const tooltipBg = new Konva.Rect({
      x: -tooltipWidth / 2,
      y: -tooltipHeight / 2,
      width: tooltipWidth,
      height: tooltipHeight,
      fill: theme.tooltip.background,
      cornerRadius: 4,
      opacity: 0.9
    })
    
    const tooltipTextNode = new Konva.Text({
      x: -tooltipWidth / 2,
      y: -tooltipHeight / 2,
      width: tooltipWidth,
      height: tooltipHeight,
      text: tooltipText,
      fontSize: 10 * fontScale,
      fill: theme.tooltip.text,
      align: 'center',
      verticalAlign: 'middle',
      fontFamily: 'Arial, sans-serif'
    })
    
    tooltip.add(tooltipBg)
    tooltip.add(tooltipTextNode)
    
    return tooltip
  }

  /**
   * Render bubble chart - Industry standard implementation with variable bubble sizes
   */
  private renderBubbleChart(group: Konva.Group, data: ChartData, chartArea: any, theme: ChartTheme, options: ChartOptions): void {
    const { labels, datasets } = data
    
    let allXValues: number[] = []
    let allYValues: number[] = []
    let allRValues: number[] = []
    
    // Collect all X, Y, and R values for proper scaling
    datasets.forEach((dataset, datasetIndex) => {
      if (Array.isArray(dataset.data) && dataset.data.length > 0) {
        // Check if data contains bubble data objects with x, y, r properties
        if (this.isBubbleData(dataset.data)) {
          dataset.data.forEach((point: BubbleDataPoint) => {
            allXValues.push(point.x)
            allYValues.push(point.y)
            allRValues.push(point.r)
          })
        } else if (this.isScatterData(dataset.data)) {
          // Fallback: treat as scatter with default radius
          dataset.data.forEach((point: ScatterDataPoint) => {
            allXValues.push(point.x)
            allYValues.push(point.y)
            allRValues.push(5) // Default radius
          })
        }
      }
    })
    
    if (allXValues.length === 0 || allYValues.length === 0) {
      console.warn('Bubble chart: No valid data points found')
      return
    }
    
    // Calculate raw data range
    const dataMinX = Math.min(...allXValues)
    const dataMaxX = Math.max(...allXValues)
    const dataMinY = Math.min(...allYValues)
    const dataMaxY = Math.max(...allYValues)
    const dataMinR = Math.min(...allRValues)
    const dataMaxR = Math.max(...allRValues)
    
    // Generate nice axis values starting from 0 (industry standard for bubble charts)
    const niceXValues = this.generateNiceAxisValues(dataMinX, dataMaxX, 5, true) // Include 0 for X-axis
    const niceYValues = this.generateNiceAxisValues(dataMinY, dataMaxY, 5, true) // Include 0 for Y-axis
    
    // Use the nice axis bounds as the actual chart bounds
    const xMin = niceXValues[0]
    const xMax = niceXValues[niceXValues.length - 1]
    const yMin = niceYValues[0]
    const yMax = niceYValues[niceYValues.length - 1]
    
    // Calculate scales based on nice bounds
    const xScale = chartArea.width / (xMax - xMin)
    const yScale = chartArea.height / (yMax - yMin)
    
    // Calculate responsive bubble radius scaling
    const fontScale = (group as any)._fontScale || 1
    const minBubbleRadius = 3 * fontScale
    const maxBubbleRadius = 25 * fontScale
    
    // Handle case where all bubbles have the same radius
    const radiusRange = dataMaxR - dataMinR
    const radiusScale = radiusRange > 0 ? (maxBubbleRadius - minBubbleRadius) / radiusRange : 0

    // Create a clipping group with padding for bubble radius to prevent edge clipping
    const bubblePadding = maxBubbleRadius
    const bubblesGroup = new Konva.Group({
      clipFunc: (ctx) => {
        ctx.rect(
          chartArea.x - bubblePadding, 
          chartArea.y - bubblePadding, 
          chartArea.width + bubblePadding * 2, 
          chartArea.height + bubblePadding * 2
        )
      }
    })
    
    // Create a separate group for tooltips that won't be clipped
    const tooltipGroup = new Konva.Group()
    
    // Render data points for each dataset
    datasets.forEach((dataset, datasetIndex) => {
      if (!Array.isArray(dataset.data)) return
      
      dataset.data.forEach((dataPoint: any, index: number) => {
        let xValue: number, yValue: number, rValue: number, pointLabel: string
        
        // Extract X, Y, and R coordinates based on data format
        if (typeof dataPoint === 'object' && dataPoint !== null && 'x' in dataPoint && 'y' in dataPoint && 'r' in dataPoint) {
          const bubblePoint = dataPoint as BubbleDataPoint
          xValue = bubblePoint.x
          yValue = bubblePoint.y
          rValue = bubblePoint.r
          pointLabel = `Bubble ${index + 1}: (${xValue}, ${yValue}, r=${rValue})`
        } else if (typeof dataPoint === 'object' && dataPoint !== null && 'x' in dataPoint && 'y' in dataPoint) {
          const scatterPoint = dataPoint as ScatterDataPoint
          xValue = scatterPoint.x
          yValue = scatterPoint.y
          rValue = 5 // Default radius for scatter points treated as bubbles
          pointLabel = `Point ${index + 1}: (${xValue}, ${yValue})`
        } else {
          return // Skip invalid data points
        }
        
        // Calculate screen coordinates
        const x = chartArea.x + (xValue - xMin) * xScale
        const y = chartArea.y + chartArea.height - (yValue - yMin) * yScale
        
        // Skip points that are outside the visible data range
        if (x < chartArea.x || x > chartArea.x + chartArea.width || 
            y < chartArea.y || y > chartArea.y + chartArea.height) {
          return
        }
        
        // Calculate bubble radius based on r value
        const bubbleRadius = radiusRange > 0 
          ? minBubbleRadius + (rValue - dataMinR) * radiusScale
          : (minBubbleRadius + maxBubbleRadius) / 2 // Use average radius when all bubbles are same size
        
        // Get bubble styling
        const bubbleColor = this.getColor(dataset.backgroundColor, datasetIndex, theme)
        const borderColor = this.getColor(dataset.borderColor, datasetIndex, theme)
        const borderWidth = (dataset.borderWidth || 1) * fontScale
        
        // Create bubble
        const bubble = new Konva.Circle({
          x: x,
          y: y,
          radius: bubbleRadius,
          fill: bubbleColor,
          stroke: borderColor,
          strokeWidth: borderWidth,
          opacity: 0.7 // More transparency for overlapping bubbles
        })
        
        bubblesGroup.add(bubble)
        
        // Create responsive tooltip for hover effect
        const tooltip = this.createTooltip(tooltipGroup, x, y, -bubbleRadius - 30, `(${this.formatAxisValue(xValue, 1)}, ${this.formatAxisValue(yValue, 1)}, r=${this.formatAxisValue(rValue, 1)})`, theme, fontScale)
        tooltipGroup.add(tooltip)
        
        // Add professional hover effect with tooltip
        bubble.on('mouseenter', () => {
          bubble.opacity(0.9)
          bubble.strokeWidth(borderWidth + 1)
          bubble.moveToTop()
          tooltip.visible(true)
          tooltip.moveToTop()
          // Reposition tooltip to avoid overlapping with larger bubbles
          tooltip.y(y - bubbleRadius - 35)
        })
        
        bubble.on('mouseleave', () => {
          bubble.opacity(0.7)
          bubble.strokeWidth(borderWidth)
          tooltip.visible(false)
        })
        
        // Store data for potential interaction
        bubble.setAttr('dataPoint', { 
          x: xValue, 
          y: yValue, 
          r: rValue,
          label: pointLabel,
          dataset: dataset.label 
        })
      })
    })
    
    // Add the clipped bubbles group to the main group
    group.add(bubblesGroup)
    
    // Add the unclipped tooltip group on top so tooltips can render outside chart area
    group.add(tooltipGroup)
    
    // Store scale information for axis rendering with nice values
    ;(group as any)._scatterScales = {
      xMin, xMax, yMin, yMax,
      niceXValues, niceYValues
    }
  }

  /**
   * Render axes with enhanced styling
   */
  private renderAxes(group: Konva.Group, data: ChartData, chartArea: any, theme: ChartTheme, options: ChartOptions, chartType: string): void {
    const fontScale = (group as any)._fontScale || 1
    
    // Enhanced axis line styling
    const axisStyle = {
      stroke: theme.grid,
      strokeWidth: 1.5,
      shadowColor: 'rgba(0, 0, 0, 0.05)',
      shadowBlur: 1,
      shadowOffset: { x: 0, y: 1 }
    }
    
    // X-axis
    if (options.scales?.x?.display) {
      const xAxis = new Konva.Line({
        points: [chartArea.x, chartArea.y + chartArea.height, chartArea.x + chartArea.width, chartArea.y + chartArea.height],
        ...axisStyle
      })
      group.add(xAxis)

      // X-axis labels - different logic for scatter/bubble vs other charts
      if (chartType === 'scatter' || chartType === 'bubble') {
        // For scatter and bubble charts, use the nice axis values from the rendered data
        const scatterScales = (group as any)._scatterScales
        if (scatterScales) {
          const { xMin, xMax, niceXValues } = scatterScales
          
          // Use pre-calculated nice values for consistent, professional appearance
          niceXValues.forEach((value: number) => {
            // Calculate position based on the actual scale used in renderScatterChart
            const x = chartArea.x + ((value - xMin) / (xMax - xMin)) * chartArea.width
            
            // Ensure label is within chart area with proper padding
            if (x >= chartArea.x && x <= chartArea.x + chartArea.width) {
              const labelText = this.createSmartLabel(
                this.formatAxisValue(value, 1),
                x,
                chartArea.y + chartArea.height + 15,
                60, // Max width for numeric labels
                12 * fontScale,
                theme.text,
                'center'
              )
              group.add(labelText)
            }
          })
        }
      } else {
        // For other charts, use category labels with smart width calculation
        const categoryCount = data.labels.length
        const availableWidth = chartArea.width * 0.9
        const categoryWidth = availableWidth / categoryCount
        const startOffset = (chartArea.width - availableWidth) / 2
        
        // Calculate the maximum width available for each label
        const maxLabelWidth = Math.max(40, categoryWidth * 0.8) // Use 80% of category width, minimum 40px
        
        data.labels.forEach((label, index) => {
          const categoryCenter = startOffset + (index + 0.5) * categoryWidth
          const x = chartArea.x + categoryCenter
          
          const labelText = this.createSmartLabel(
            label,
            x,
            chartArea.y + chartArea.height + 15,
            maxLabelWidth,
            12 * fontScale,
            theme.text,
            'center'
          )
          group.add(labelText)
        })
      }
    }

    // Y-axis
    if (options.scales?.y?.display) {
      const yAxis = new Konva.Line({
        points: [chartArea.x, chartArea.y, chartArea.x, chartArea.y + chartArea.height],
        ...axisStyle
      })
      group.add(yAxis)

      // Y-axis grid lines and labels
      if (chartType === 'scatter' || chartType === 'bubble') {
        // For scatter and bubble charts, use the scatter scales for consistent labeling
        const scatterScales = (group as any)._scatterScales
        if (scatterScales) {
          const { yMin, yMax, niceYValues } = scatterScales
          
          // Use pre-calculated nice values for consistent, professional appearance
          niceYValues.forEach((value: number) => {
            // Calculate position based on the actual scale used in renderScatterChart
            const y = chartArea.y + chartArea.height - ((value - yMin) / (yMax - yMin)) * chartArea.height

            // Ensure label is within chart area
            if (y >= chartArea.y && y <= chartArea.y + chartArea.height) {
              // Enhanced grid line
              if (options.scales?.y?.grid?.display) {
                const gridLine = new Konva.Line({
                  points: [chartArea.x, y, chartArea.x + chartArea.width, y],
                  stroke: theme.grid,
                  strokeWidth: 0.5,
                  opacity: 0.3,
                  dash: [2, 4] // Dashed grid lines for better readability
                })
                group.add(gridLine)
              }

              // Enhanced label
              const labelText = this.createSmartLabel(
                this.formatAxisValue(value, 1),
                chartArea.x - 15,
                y - 6,
                50, // Max width for Y-axis labels
                12 * fontScale,
                theme.text,
                'right'
              )
              group.add(labelText)
            }
          })
        }
      } else {
        // For other charts, only use numeric datasets
        const numericDatasets = data.datasets.filter(d => this.isNumericData(d.data))
        const allValues = numericDatasets.flatMap(d => d.data as number[])
        const maxValue = Math.max(...allValues)
        const minValue = 0
        
        const steps = 5
        
        for (let i = 0; i <= steps; i++) {
          const value = minValue + (maxValue - minValue) * (i / steps)
          const y = chartArea.y + chartArea.height - (chartArea.height * i) / steps

          // Enhanced grid line
          if (options.scales?.y?.grid?.display) {
            const gridLine = new Konva.Line({
              points: [chartArea.x, y, chartArea.x + chartArea.width, y],
              stroke: theme.grid,
              strokeWidth: 0.5,
              opacity: 0.3,
              dash: [2, 4] // Dashed grid lines for better readability
            })
            group.add(gridLine)
          }

          // Enhanced label
          const labelText = this.createSmartLabel(
            this.formatAxisValue(value, 1),
            chartArea.x - 15,
            y - 6,
            50, // Max width for Y-axis labels
            12 * fontScale,
            theme.text,
            'right'
          )
          group.add(labelText)
        }
      }
    }
  }

  /**
   * Render legend with enhanced styling
   */
  private renderLegend(group: Konva.Group, data: ChartData, legend: any, theme: ChartTheme, width: number, height: number): void {
    const position = legend.position || 'top'
    const fontScale = (group as any)._fontScale || 1
    const itemHeight = 20 * fontScale
    const itemSpacing = 10 * fontScale
    const iconSize = 12 * fontScale

    let startX = 10
    let startY = 10

    switch (position) {
      case 'bottom':
        startY = height - (data.datasets.length * itemHeight + (data.datasets.length - 1) * itemSpacing) - 10
        break
      case 'left':
        startX = 10
        startY = height / 2 - (data.datasets.length * itemHeight) / 2
        break
      case 'right':
        startX = width - 100
        startY = height / 2 - (data.datasets.length * itemHeight) / 2
        break
    }

    data.datasets.forEach((dataset, index) => {
      const y = startY + index * (itemHeight + itemSpacing)
      const legendColor = this.getColor(dataset.backgroundColor, index, theme)

      // Enhanced legend color box with border and shadow
      const colorBox = new Konva.Rect({
        x: startX,
        y: y,
        width: iconSize,
        height: iconSize,
        fill: legendColor,
        stroke: this.adjustColorBrightness(legendColor, -0.2),
        strokeWidth: 1,
        cornerRadius: 2,
        shadowColor: 'rgba(0, 0, 0, 0.1)',
        shadowBlur: 1,
        shadowOffset: { x: 0, y: 1 }
      })
      group.add(colorBox)

      // Enhanced legend text with better typography
      const legendText = new Konva.Text({
        x: startX + iconSize + 8,
        y: y - 2,
        text: dataset.label || `Dataset ${index + 1}`,
        fontSize: 12 * fontScale,
        fill: theme.text,
        fontFamily: 'Arial, sans-serif',
        fontWeight: '500'
      })
      group.add(legendText)
    })
  }

  /**
   * Format axis value for display
   */
  private formatAxisValue(value: number, maxDecimals: number = 2): string {
    // Format numbers appropriately for axis display
    if (Math.abs(value) >= 1000000) {
      return (value / 1000000).toFixed(maxDecimals).replace(/\.?0+$/, '') + 'M'
    } else if (Math.abs(value) >= 1000) {
      return (value / 1000).toFixed(maxDecimals).replace(/\.?0+$/, '') + 'K'
    } else if (value % 1 === 0) {
      return value.toString()
    } else {
      return value.toFixed(maxDecimals).replace(/\.?0+$/, '')
    }
  }

  /**
   * Generate nice axis values for better readability
   * For scatter charts, always include 0 in the range like professional libraries
   */
  private generateNiceAxisValues(min: number, max: number, targetSteps: number = 5, includeZero: boolean = false): number[] {
    // Handle edge case where min equals max
    if (min === max) {
      if (includeZero && min !== 0) {
        return min > 0 ? [0, min, min * 1.5] : [min * 1.5, min, 0]
      }
      return [min - 1, min, min + 1]
    }

    // Extend range to include zero if requested
    if (includeZero) {
      min = Math.min(min, 0)
      max = Math.max(max, 0)
    }

    // Calculate range and rough step size
    const range = max - min
    const roughStep = range / targetSteps

    // Find the order of magnitude
    const orderOfMagnitude = Math.floor(Math.log10(roughStep))
    const stepMagnitude = Math.pow(10, orderOfMagnitude)

    // Round step to a nice number
    const normalizedStep = roughStep / stepMagnitude
    let niceStep: number
    if (normalizedStep <= 1) {
      niceStep = 1
    } else if (normalizedStep <= 2) {
      niceStep = 2
    } else if (normalizedStep <= 5) {
      niceStep = 5
    } else {
      niceStep = 10
    }

    niceStep *= stepMagnitude

    // Generate nice values
    const niceMin = Math.floor(min / niceStep) * niceStep
    const niceMax = Math.ceil(max / niceStep) * niceStep

    const values: number[] = []
    for (let value = niceMin; value <= niceMax; value += niceStep) {
      // Round to avoid floating point precision issues
      const rounded = Math.round(value / niceStep) * niceStep
      values.push(rounded)
    }

    return values
  }

  /**
   * Render pie chart labels with improved positioning and visibility
   */
  private renderPieLabels(
    group: Konva.Group, 
    sliceData: Array<{
      index: number
      label: string
      value: number
      percentage: string
      midAngle: number
      sliceAngle: number
      color: string
    }>, 
    centerX: number, 
    centerY: number, 
    radius: number, 
    innerRadius: number, 
    theme: ChartTheme
  ): void {
    // Get responsive font size
    const fontScale = (group as any)._fontScale || 1
    const labelFontSize = Math.max(9, 12 * fontScale)
    const percentageFontSize = Math.max(8, 10 * fontScale)
    
    // Only show labels for slices larger than this threshold
    const minSliceAngleForLabel = 0.15 // About 8.5 degrees
    
    // Filter slices that are large enough to show labels
    const visibleSlices = sliceData.filter(slice => slice.sliceAngle > minSliceAngleForLabel)
    
    // Position all labels outside the chart with leader lines
    visibleSlices.forEach(slice => {
      const { label, percentage, midAngle, sliceAngle } = slice
      
      // Always place labels outside the chart
      const labelRadius = radius + 40
      
      // Calculate label position
      const labelX = centerX + Math.cos(midAngle) * labelRadius
      const labelY = centerY + Math.sin(midAngle) * labelRadius
      
      // Calculate the maximum text width for external labels
      let maxTextWidth: number
      
      // For external labels, use a fixed width that's readable
      maxTextWidth = 100
      
      // Add leader line from slice edge to label
      const lineStartRadius = radius + 2
      const lineStartX = centerX + Math.cos(midAngle) * lineStartRadius
      const lineStartY = centerY + Math.sin(midAngle) * lineStartRadius
      const lineEndX = centerX + Math.cos(midAngle) * (labelRadius - 15)
      const lineEndY = centerY + Math.sin(midAngle) * (labelRadius - 15)
      
      const line = new Konva.Line({
        points: [lineStartX, lineStartY, lineEndX, lineEndY],
        stroke: theme.text,
        strokeWidth: 1,
        opacity: 0.6
      })
      group.add(line)
      
      // Use the original label without truncation
      const displayLabel = label
      
      // Use theme text color for external labels
      const textColor = theme.text
      
      // Create background for all external labels
      const padding = 6
      const tempBgText = new Konva.Text({
        text: `${displayLabel}\n${percentage}%`,
        fontSize: labelFontSize,
        fontFamily: 'Arial, sans-serif'
      })
      const bgWidth = Math.max(tempBgText.width() + padding * 2, 70)
      const bgHeight = tempBgText.height() + padding * 2
      tempBgText.destroy()
      
      const background = new Konva.Rect({
        x: labelX - bgWidth / 2,
        y: labelY - bgHeight / 2,
        width: bgWidth,
        height: bgHeight,
        fill: theme.background,
        cornerRadius: 4,
        stroke: theme.text,
        strokeWidth: 0.5,
        opacity: 0.95
      })
      group.add(background)
      
      // Calculate text heights for proper positioning
      const tempLabelText = new Konva.Text({
        text: displayLabel,
        fontSize: labelFontSize,
        fontFamily: 'Arial, sans-serif',
        fontWeight: 'bold'
      })
      const labelHeight = tempLabelText.height()
      tempLabelText.destroy()
      
      const tempPercentageText = new Konva.Text({
        text: `${percentage}%`,
        fontSize: percentageFontSize,
        fontFamily: 'Arial, sans-serif',
        fontWeight: '600'
      })
      const percentageHeight = tempPercentageText.height()
      tempPercentageText.destroy()
      
      // Calculate total text height with gap
      const textGap = 2
      const totalTextHeight = labelHeight + percentageHeight + textGap
      
      // Calculate starting Y position to center both texts
      const startY = labelY - (totalTextHeight / 2)
      
      // Create the label text
      const labelText = new Konva.Text({
        x: labelX,
        y: startY,
        text: displayLabel,
        fontSize: labelFontSize,
        fill: textColor,
        align: 'center',
        fontFamily: 'Arial, sans-serif',
        fontWeight: 'bold'
      })
      
      // Center the text horizontally
      labelText.offsetX(labelText.width() / 2)
      
      group.add(labelText)
      
      // Create percentage text below the label
      const percentageText = new Konva.Text({
        x: labelX,
        y: startY + labelHeight + textGap,
        text: `${percentage}%`,
        fontSize: percentageFontSize,
        fill: textColor,
        align: 'center',
        fontFamily: 'Arial, sans-serif',
        fontWeight: '600',
        opacity: 0.95
      })
      
      // Center the percentage text horizontally
      percentageText.offsetX(percentageText.width() / 2)
      
      group.add(percentageText)
    })
    
    // For very small slices, show a compact legend
    const hiddenSlices = sliceData.filter(slice => slice.sliceAngle <= minSliceAngleForLabel)
    if (hiddenSlices.length > 0) {
      const legendY = centerY + radius + 60
      const legendText = new Konva.Text({
        x: centerX,
        y: legendY,
        text: `${hiddenSlices.length} small slices not labeled`,
        fontSize: 10 * fontScale,
        fill: theme.text,
        align: 'center',
        opacity: 0.7
      })
      
      legendText.offsetX(legendText.width() / 2)
      group.add(legendText)
    }
  }

  /**
   * Adjust the brightness of a color
   */
  private adjustColorBrightness(color: string, amount: number): string {
    // Convert hex to RGB
    const hex = color.replace('#', '')
    const r = parseInt(hex.substring(0, 2), 16)
    const g = parseInt(hex.substring(2, 4), 16)
    const b = parseInt(hex.substring(4, 6), 16)
    
    // Apply brightness adjustment
    const newR = Math.max(0, Math.min(255, r + (255 * amount)))
    const newG = Math.max(0, Math.min(255, g + (255 * amount)))
    const newB = Math.max(0, Math.min(255, b + (255 * amount)))
    
    // Convert back to hex
    const toHex = (value: number) => {
      const hex = Math.round(value).toString(16)
      return hex.length === 1 ? '0' + hex : hex
    }
    
    return `#${toHex(newR)}${toHex(newG)}${toHex(newB)}`
  }

  /**
   * Get color from dataset or theme with enhanced color selection
   */
  private getColor(color: string | string[] | undefined, index: number, theme: ChartTheme, alpha: number = 1): string {
    if (Array.isArray(color)) {
      return color[index % color.length]
    }
    
    if (color) {
      if (alpha < 1 && color.startsWith('#')) {
        // Convert hex to rgba
        const hex = color.replace('#', '')
        const r = parseInt(hex.substring(0, 2), 16)
        const g = parseInt(hex.substring(2, 4), 16)
        const b = parseInt(hex.substring(4, 6), 16)
        return `rgba(${r}, ${g}, ${b}, ${alpha})`
      }
      return color
    }
    
    // Use a wider range of theme colors for better variety
    const extendedAccentColors = [
      ...theme.accent,
      theme.primary,
      theme.secondary,
      // Add color variations by adjusting brightness/saturation
      this.adjustColorBrightness(theme.primary, 0.2),
      this.adjustColorBrightness(theme.secondary, 0.2),
      this.adjustColorBrightness(theme.accent[0], -0.2),
      this.adjustColorBrightness(theme.accent[1], -0.2),
      this.adjustColorBrightness(theme.accent[2], -0.2)
    ]
    
    return extendedAccentColors[index % extendedAccentColors.length]
  }

  /**
   * Apply common properties to chart group
   */
  private applyCommonProperties(group: Konva.Group, layer: LayerNode): void {
    group.setAttrs({
      id: layer.id.toString(),
      x: layer.x,
      y: layer.y,
      width: layer.width,
      height: layer.height,
      rotation: layer.rotation || 0,
      scaleX: layer.scaleX || 1,
      scaleY: layer.scaleY || 1,
      opacity: layer.opacity || 1,
      visible: layer.visible !== false,
      listening: !layer.locked,
      draggable: true
    })
  }

  /**
   * Create a smart label with optimal width and wrapping
   */
  private createSmartLabel(
    text: string,
    x: number,
    y: number,
    maxWidth: number,
    fontSize: number,
    color: string,
    align: string = 'center'
  ): Konva.Text {
    // Create a temporary text element to measure the natural width
    const tempText = new Konva.Text({
      text: text,
      fontSize: fontSize,
      fontFamily: 'Arial, sans-serif'
    })
    const naturalWidth = tempText.width()
    tempText.destroy()
    
    // Determine optimal settings
    const shouldWrap = naturalWidth > maxWidth
    const labelWidth = shouldWrap ? maxWidth : naturalWidth
    
    // Adjust X position based on alignment
    let finalX = x
    if (align === 'center') {
      finalX = x - labelWidth / 2
    } else if (align === 'right') {
      finalX = x - labelWidth
    }
    
    return new Konva.Text({
      x: finalX,
      y: y,
      width: labelWidth,
      text: text,
      fontSize: fontSize,
      fill: color,
      align: align,
      wrap: shouldWrap ? 'word' : 'none',
      ellipsis: !shouldWrap && naturalWidth > maxWidth,
      fontFamily: 'Arial, sans-serif'
    })
  }

}

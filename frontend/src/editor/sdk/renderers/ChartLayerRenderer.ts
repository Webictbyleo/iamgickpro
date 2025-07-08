import Konva from 'konva'
import type { KonvaLayerRenderer, LayerNode } from '../types'
import type { Layer, ChartLayerProperties, ChartData, ChartDataset, ChartOptions, ChartTheme } from '../../../types'

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
  }

  destroy(node: Konva.Node): void {
    node.destroy()
  }

  getSupportedTypes(): string[] {
    return ['chart']
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
  private getDefaultData(data?: ChartData): ChartData {
    return data || {
      labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May'],
      datasets: [{
        label: 'Sample Data',
        data: [10, 20, 15, 25, 30],
        backgroundColor: '#3B82F6',
        borderColor: '#1E40AF',
        borderWidth: 2
      }]
    }
  }

  /**
   * Get default chart options
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
            color: '#E5E7EB'
          }
        },
        y: {
          display: true,
          grid: {
            display: true,
            color: '#E5E7EB'
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
   * Get default chart theme
   */
  private getDefaultTheme(theme?: ChartTheme): ChartTheme {
    return {
      primary: '#3B82F6',
      secondary: '#8B5CF6',
      background: '#FFFFFF',
      text: '#1F2937',
      grid: '#E5E7EB',
      accent: ['#EF4444', '#F59E0B', '#10B981', '#F97316', '#8B5CF6', '#EC4899'],
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
      case 'doughnut':
        this.renderPieChart(group, data, chartArea, theme, options, chartType === 'doughnut')
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
      this.renderAxes(group, data, chartArea, theme, options)
    }
  }

  /**
   * Calculate margins for chart area
   */
  private calculateMargins(options: ChartOptions, width: number, height: number) {
    let top = 20
    let bottom = 40
    let left = 60
    let right = 20

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
    const maxValue = Math.max(...datasets.flatMap(d => d.data))
    const scale = chartArea.height / maxValue

    // Calculate proper spacing within chartArea bounds
    const categoryCount = labels.length
    const datasetCount = datasets.length
    
    // Available width for all bars (with some padding)
    const availableWidth = chartArea.width * 0.9 // Leave 10% padding
    const categoryWidth = availableWidth / categoryCount // Width per category group
    const barGroupWidth = categoryWidth * 0.8 // 80% of category width for bars, 20% for spacing
    const barWidth = barGroupWidth / datasetCount // Width per individual bar
    
    // Calculate starting position to center all bars within chartArea
    const startOffset = (chartArea.width - availableWidth) / 2

    datasets.forEach((dataset, datasetIndex) => {
      dataset.data.forEach((value, index) => {
        // Calculate the center position for this category
        const categoryCenter = startOffset + (index + 0.5) * categoryWidth
        
        // Calculate the starting position for the bar group
        const barGroupStart = categoryCenter - (barGroupWidth / 2)
        
        // Calculate individual bar position
        const x = chartArea.x + barGroupStart + (datasetIndex * barWidth)
        
        const barHeight = value * scale
        const y = chartArea.y + chartArea.height - barHeight

        const bar = new Konva.Rect({
          x: x,
          y: y,
          width: barWidth * 0.95, // Small gap between bars in same group
          height: barHeight,
          fill: this.getColor(dataset.backgroundColor, datasetIndex, theme),
          stroke: this.getColor(dataset.borderColor, datasetIndex, theme),
          strokeWidth: dataset.borderWidth || 0
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
    const maxValue = Math.max(...datasets.flatMap(d => d.data))
    const scale = chartArea.height / maxValue

    datasets.forEach((dataset, datasetIndex) => {
      const points: number[] = []
      
      dataset.data.forEach((value, index) => {
        const x = chartArea.x + index * pointSpacing
        const y = chartArea.y + chartArea.height - (value * scale)
        points.push(x, y)
      })

      // Draw line
      const line = new Konva.Line({
        points: points,
        stroke: this.getColor(dataset.borderColor, datasetIndex, theme),
        strokeWidth: dataset.borderWidth || 2,
        tension: dataset.tension || 0
      })
      group.add(line)

      // Draw points
      if (dataset.pointRadius && dataset.pointRadius > 0) {
        for (let i = 0; i < points.length; i += 2) {
          const circle = new Konva.Circle({
            x: points[i],
            y: points[i + 1],
            radius: dataset.pointRadius,
            fill: this.getColor(dataset.pointBackgroundColor, datasetIndex, theme),
            stroke: this.getColor(dataset.pointBorderColor, datasetIndex, theme),
            strokeWidth: 1
          })
          group.add(circle)
        }
      }
    })
  }

  /**
   * Render pie/doughnut chart
   */
  private renderPieChart(group: Konva.Group, data: ChartData, chartArea: any, theme: ChartTheme, options: ChartOptions, isDoughnut: boolean = false): void {
    const centerX = chartArea.x + chartArea.width / 2
    const centerY = chartArea.y + chartArea.height / 2
    const radius = Math.min(chartArea.width, chartArea.height) / 2 - 10
    const innerRadius = isDoughnut ? radius * 0.5 : 0 // Increased from 0.4 to 0.5 for more visible hole

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

    // Assuming single dataset for pie charts
    const dataset = data.datasets[0]
    const total = dataset.data.reduce((sum, value) => sum + value, 0)
    let currentAngle = -Math.PI / 2 // Start at top

    dataset.data.forEach((value, index) => {
      const sliceAngle = (value / total) * 2 * Math.PI
      const endAngle = currentAngle + sliceAngle

      let slice: Konva.Shape

      if (isDoughnut) {
        // Capture the angles in local scope for the closure
        const startAngle = currentAngle
        const endAngleLocal = endAngle
        
        // Use custom shape for doughnut charts to create proper transparent hole
        slice = new Konva.Shape({
          sceneFunc: function (context, shape) {
            context.beginPath()
            // Draw outer arc
            context.arc(0, 0, radius, startAngle, endAngleLocal, false)
            // Draw line to inner radius
            context.lineTo(Math.cos(endAngleLocal) * innerRadius, Math.sin(endAngleLocal) * innerRadius)
            // Draw inner arc (reverse direction)
            context.arc(0, 0, innerRadius, endAngleLocal, startAngle, true)
            // Close the path
            context.closePath()
            context.fillStrokeShape(shape)
          },
          x: centerX,
          y: centerY,
          fill: this.getColor(dataset.backgroundColor, index, theme)
        })
      } else {
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
      }

      group.add(slice)

      // Store slice data for label positioning
      sliceData.push({
        index,
        label: data.labels[index] || `Slice ${index + 1}`,
        value,
        percentage: ((value / total) * 100).toFixed(1),
        midAngle: currentAngle + sliceAngle / 2,
        sliceAngle,
        color: this.getColor(dataset.backgroundColor, index, theme)
      })
      currentAngle = endAngle
    })

    // Add center text for doughnut charts (but no filled circle - keep the hole transparent)
    if (isDoughnut) {
      // Add center text showing total or chart type
      const fontScale = (group as any)._fontScale || 1
      const centerText = new Konva.Text({
        x: centerX,
        y: centerY - 8 * fontScale,
        text: 'Total',
        fontSize: Math.max(10, 14 * fontScale),
        fill: theme.text,
        align: 'center',
        fontFamily: 'Arial, sans-serif',
        fontWeight: 'bold',
        opacity: 0.7
      })
      centerText.offsetX(centerText.width() / 2)
      group.add(centerText)

      // Add total value below
      const totalText = new Konva.Text({
        x: centerX,
        y: centerY + 4 * fontScale,
        text: total.toString(),
        fontSize: Math.max(8, 12 * fontScale),
        fill: theme.text,
        align: 'center',
        fontFamily: 'Arial, sans-serif',
        fontWeight: '600',
        opacity: 0.6
      })
      totalText.offsetX(totalText.width() / 2)
      group.add(totalText)
    }

    // Render labels with better positioning
    this.renderPieLabels(group, sliceData, centerX, centerY, radius, innerRadius, theme, isDoughnut)
  }

  /**
   * Render area chart (line chart with filled area)
   */
  private renderAreaChart(group: Konva.Group, data: ChartData, chartArea: any, theme: ChartTheme, options: ChartOptions): void {
    const { labels, datasets } = data
    const pointSpacing = chartArea.width / (labels.length - 1)
    const maxValue = Math.max(...datasets.flatMap(d => d.data))
    const scale = chartArea.height / maxValue

    datasets.forEach((dataset, datasetIndex) => {
      const points: number[] = []
      
      // Start from bottom left
      points.push(chartArea.x, chartArea.y + chartArea.height)
      
      dataset.data.forEach((value, index) => {
        const x = chartArea.x + index * pointSpacing
        const y = chartArea.y + chartArea.height - (value * scale)
        points.push(x, y)
      })

      // End at bottom right
      points.push(chartArea.x + chartArea.width, chartArea.y + chartArea.height)

      // Create filled area
      const area = new Konva.Line({
        points: points,
        fill: this.getColor(dataset.backgroundColor, datasetIndex, theme, 0.3),
        stroke: this.getColor(dataset.borderColor, datasetIndex, theme),
        strokeWidth: dataset.borderWidth || 2,
        closed: true,
        tension: dataset.tension || 0
      })
      group.add(area)
    })
  }

  /**
   * Render scatter chart
   */
  private renderScatterChart(group: Konva.Group, data: ChartData, chartArea: any, theme: ChartTheme, options: ChartOptions): void {
    // For scatter chart, data should be array of {x, y} objects
    // This is simplified implementation assuming data is array of numbers
    this.renderLineChart(group, data, chartArea, theme, options)
  }

  /**
   * Render bubble chart
   */
  private renderBubbleChart(group: Konva.Group, data: ChartData, chartArea: any, theme: ChartTheme, options: ChartOptions): void {
    // For bubble chart, data should be array of {x, y, r} objects
    // This is simplified implementation
    this.renderLineChart(group, data, chartArea, theme, options)
  }

  /**
   * Render axes
   */
  private renderAxes(group: Konva.Group, data: ChartData, chartArea: any, theme: ChartTheme, options: ChartOptions): void {
    // X-axis
    if (options.scales?.x?.display) {
      const xAxis = new Konva.Line({
        points: [chartArea.x, chartArea.y + chartArea.height, chartArea.x + chartArea.width, chartArea.y + chartArea.height],
        stroke: theme.grid,
        strokeWidth: 1
      })
      group.add(xAxis)

      // X-axis labels
      const fontScale = (group as any)._fontScale || 1
      const categoryCount = data.labels.length
      const availableWidth = chartArea.width * 0.9 // Same as bars
      const categoryWidth = availableWidth / categoryCount
      const startOffset = (chartArea.width - availableWidth) / 2
      
      data.labels.forEach((label, index) => {
        // Center label for each category group (matching bar positioning)
        const categoryCenter = startOffset + (index + 0.5) * categoryWidth
        const x = chartArea.x + categoryCenter
        
        const labelText = new Konva.Text({
          x: x - 30, // Wider centering area for longer labels
          y: chartArea.y + chartArea.height + 10,
          width: 60,
          text: label,
          fontSize: 12 * fontScale,
          fill: theme.text,
          align: 'center'
        })
        group.add(labelText)
      })
    }

    // Y-axis
    if (options.scales?.y?.display) {
      const yAxis = new Konva.Line({
        points: [chartArea.x, chartArea.y, chartArea.x, chartArea.y + chartArea.height],
        stroke: theme.grid,
        strokeWidth: 1
      })
      group.add(yAxis)

      // Y-axis grid lines and labels
      const maxValue = Math.max(...data.datasets.flatMap(d => d.data))
      const steps = 5
      for (let i = 0; i <= steps; i++) {
        const value = (maxValue / steps) * i
        const y = chartArea.y + chartArea.height - (value / maxValue) * chartArea.height

        // Grid line
        if (options.scales.y.grid?.display) {
          const gridLine = new Konva.Line({
            points: [chartArea.x, y, chartArea.x + chartArea.width, y],
            stroke: theme.grid,
            strokeWidth: 0.5,
            opacity: 0.3
          })
          group.add(gridLine)
        }

        // Label
        const fontScale = (group as any)._fontScale || 1
        const labelText = new Konva.Text({
          x: chartArea.x - 50,
          y: y - 6,
          width: 40,
          text: Math.round(value).toString(),
          fontSize: 12 * fontScale,
          fill: theme.text,
          align: 'right'
        })
        group.add(labelText)
      }
    }
  }

  /**
   * Render legend
   */
  private renderLegend(group: Konva.Group, data: ChartData, legend: any, theme: ChartTheme, width: number, height: number): void {
    const position = legend.position || 'top'
    const itemHeight = 20
    const itemSpacing = 10

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

      // Legend color box
      const colorBox = new Konva.Rect({
        x: startX,
        y: y,
        width: 12,
        height: 12,
        fill: this.getColor(dataset.backgroundColor, index, theme)
      })
      group.add(colorBox)

      // Legend text
      const fontScale = (group as any)._fontScale || 1
      const legendText = new Konva.Text({
        x: startX + 20,
        y: y - 2,
        text: dataset.label,
        fontSize: 12 * fontScale,
        fill: theme.text
      })
      group.add(legendText)
    })
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
    theme: ChartTheme, 
    isDoughnut: boolean
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
      const useExternalLabel = true
      
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
      // Add a compact legend for hidden slices
      const legendStartY = centerY + radius + 40
      const legendFontSize = Math.max(7, 8 * fontScale)
      const legendItemHeight = legendFontSize + 3
      
      // Add legend title
      const legendTitle = new Konva.Text({
        x: centerX - 60,
        y: legendStartY - 15,
        width: 120,
        text: 'Other:',
        fontSize: legendFontSize + 1,
        fill: theme.text,
        align: 'center',
        fontFamily: 'Arial, sans-serif',
        fontWeight: 'bold',
        opacity: 0.8
      })
      group.add(legendTitle)
      
      hiddenSlices.forEach((slice, index) => {
        if (index < 3) { // Show only first 3 hidden slices in legend
          const legendY = legendStartY + (index * legendItemHeight)
          const legendText = new Konva.Text({
            x: centerX - 60,
            y: legendY,
            width: 120,
            text: `${slice.label}: ${slice.percentage}%`,
            fontSize: legendFontSize,
            fill: theme.text,
            align: 'center',
            fontFamily: 'Arial, sans-serif',
            opacity: 0.7
          })
          group.add(legendText)
        }
      })
    }
  }

  /**
   * Calculate contrast color (black or white) based on background color
   */
  private getContrastColor(backgroundColor: string, theme: ChartTheme): string {
    // If it's a light background, use dark text, otherwise use light text
    return this.isLightColor(backgroundColor) ? '#000000' : '#FFFFFF'
  }

  /**
   * Determine if a color is light or dark for contrast calculation
   */
  private isLightColor(color: string): boolean {
    // Convert color to RGB values
    let r: number, g: number, b: number

    if (color.startsWith('#')) {
      // Hex color
      const hex = color.replace('#', '')
      if (hex.length === 3) {
        r = parseInt(hex[0] + hex[0], 16)
        g = parseInt(hex[1] + hex[1], 16)
        b = parseInt(hex[2] + hex[2], 16)
      } else {
        r = parseInt(hex.substring(0, 2), 16)
        g = parseInt(hex.substring(2, 4), 16)
        b = parseInt(hex.substring(4, 6), 16)
      }
    } else if (color.startsWith('rgb')) {
      // RGB or RGBA color
      const matches = color.match(/\d+/g)
      if (matches && matches.length >= 3) {
        r = parseInt(matches[0])
        g = parseInt(matches[1])
        b = parseInt(matches[2])
      } else {
        return false // Default to dark if we can't parse
      }
    } else {
      // Named colors or other formats - use a simple heuristic
      const namedColors: { [key: string]: boolean } = {
        'white': true,
        'lightgray': true,
        'lightgrey': true,
        'yellow': true,
        'cyan': true,
        'magenta': true,
        'lime': true,
        'silver': true
      }
      return namedColors[color.toLowerCase()] || false
    }

    // Calculate luminance using the relative luminance formula
    // Formula: (0.299 * R + 0.587 * G + 0.114 * B) / 255
    const luminance = (0.299 * r + 0.587 * g + 0.114 * b) / 255
    
    // If luminance is greater than 0.5, it's a light color
    return luminance > 0.5
  }

  /**
   * Get color from dataset or theme
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
    
    return theme.accent[index % theme.accent.length]
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

}

<template>
  <div class="flex items-center space-x-4">
    <!-- Shape Type Display -->
    <div class="flex items-center space-x-2">
      <label class="text-sm font-medium text-gray-700 min-w-max">Shape:</label>
      <span class="px-3 py-1 text-sm bg-gray-100 rounded-md border min-w-[100px] text-gray-800 font-medium">
        {{ getShapeLabel(shapeType) }}
      </span>
    </div>

    <!-- Fill Color -->
    <div class="flex items-center space-x-2">
      <label class="text-sm font-medium text-gray-700">Fill:</label>
      <PropertyColorPicker
        :value="getFillColorString(props.fill)"
        @update="handleFillUpdate"
        allow-gradient
      />
    </div>

    <!-- Stroke Color -->
    <div class="flex items-center space-x-2">
      <label class="text-sm font-medium text-gray-700">Stroke:</label>
      <PropertyColorPicker
        :value="getStrokeColorString(props.stroke)"
        @update="handleStrokeUpdate"
      />
    </div>

    <!-- Stroke Width -->
    <div class="flex items-center space-x-2">
      <label class="text-sm font-medium text-gray-700">Width:</label>
      <PropertyNumberInput
        :value="strokeWidth"
        @update:value="(value) => $emit('update', { strokeWidth: value })"
        @change="(value) => $emit('update', { strokeWidth: value })"
        :min="0"
        :max="20"
        :step="1"
        unit="px"
        input-class="w-16"
        placeholder="2"
      />
    </div>

    <!-- Corner Radius (for rectangles) -->
    <div v-if="shapeType === 'rectangle'" class="flex items-center space-x-2">
      <label class="text-sm font-medium text-gray-700">Radius:</label>
      <PropertyNumberInput
        :value="cornerRadius"
        @update:value="(value) => $emit('update', { cornerRadius: value })"
        @change="(value) => $emit('update', { cornerRadius: value })"
        :min="0"
        :max="100"
        :step="1"
        unit="px"
        input-class="w-16"
        placeholder="0"
      />
    </div>

    <!-- Shadow Toggle Removed - Effects now handled through separate panels -->
  </div>
</template>

<script setup lang="ts">
import PropertyDropdown from '@/components/editor/Properties/PropertyDropdown.vue'
import PropertyInput from '@/components/editor/Properties/PropertyInput.vue'
import PropertyNumberInput from '@/components/editor/Properties/PropertyNumberInput.vue'
import PropertyColorPicker from '@/components/editor/Properties/PropertyColorPicker.vue'
import type { ShapeFillConfig } from '@/types'

interface Props {
  shapeType?: string
  fill?: string | ShapeFillConfig
  stroke?: string | ShapeFillConfig
  strokeWidth?: number
  cornerRadius?: number
}

const props = withDefaults(defineProps<Props>(), {
  shapeType: 'rectangle',
  fill: '#3498db',
  stroke: '#2980b9',
  strokeWidth: 2,
  cornerRadius: 0
})

const emit = defineEmits<{
  update: [properties: Partial<Props>]
}>()

// Get human-readable shape label
function getShapeLabel(shapeType: string): string {
  const shapeLabels: Record<string, string> = {
    rectangle: 'Rectangle',
    circle: 'Circle',
    ellipse: 'Ellipse',
    triangle: 'Triangle',
    line: 'Line',
    arrow: 'Arrow',
    star: 'Star',
    polygon: 'Polygon',
    heart: 'Heart'
  }
  return shapeLabels[shapeType] || 'Unknown'
}

// Helper functions to convert between ShapeFillConfig and string values
function getFillColorString(fill: string | ShapeFillConfig | undefined): string {
  if (!fill) return '#3498db'
  if (typeof fill === 'string') return fill
  
  // Handle ShapeFillConfig object
  if (fill.type === 'solid') {
    return fill.color || '#3498db'
  } else if (fill.type === 'linear' && fill.colors && fill.colors.length >= 2) {
    // Convert gradient to CSS linear-gradient string
    const angle = fill.angle || 0
    const stops = fill.colors || []
    const stopStrings = stops.map(stop => `${stop.color} ${stop.stop * 100}%`)
    return `linear-gradient(${angle}deg, ${stopStrings.join(', ')})`
  } else if (fill.type === 'radial' && fill.colors && fill.colors.length >= 2) {
    // Convert radial gradient to CSS radial-gradient string
    const stops = fill.colors || []
    const stopStrings = stops.map(stop => `${stop.color} ${stop.stop * 100}%`)
    return `radial-gradient(circle, ${stopStrings.join(', ')})`
  }
  
  return fill.color || '#3498db'
}

function getStrokeColorString(stroke: string | ShapeFillConfig | undefined): string {
  if (!stroke) return '#2980b9'
  if (typeof stroke === 'string') return stroke
  
  // Handle ShapeFillConfig object - stroke is typically solid color
  if (stroke.type === 'solid') {
    return stroke.color || '#2980b9'
  }
  
  return stroke.color || '#2980b9'
}

function handleFillUpdate(colorValue: string) {
  // Always create a consistent ShapeFillConfig object
  if (colorValue.startsWith('linear-gradient') || colorValue.startsWith('radial-gradient')) {
    // For gradients, create a ShapeFillConfig object
    const fillConfig: ShapeFillConfig = {
      type: colorValue.startsWith('linear-gradient') ? 'linear' : 'radial',
      color: colorValue,
      opacity: 1,
      ...parseGradientString(colorValue)
    }
    emit('update', { fill: fillConfig })
  } else {
    // For solid colors, always use ShapeFillConfig format for consistency
    const fillConfig: ShapeFillConfig = {
      type: 'solid',
      color: colorValue,
      opacity: 1
    }
    emit('update', { fill: fillConfig })
  }
}

function handleStrokeUpdate(colorValue: string) {
  // Stroke is typically a solid color
  emit('update', { stroke: colorValue })
}

function parseGradientString(gradientStr: string) {
  // Basic gradient parsing - this is a simplified version
  // In a real implementation, you'd want more robust CSS gradient parsing
  const isLinear = gradientStr.startsWith('linear-gradient')
  
  if (isLinear) {
    return {
      angle: 0, // Default angle
      colors: [
        { color: '#3498db', stop: 0 },
        { color: '#2980b9', stop: 1 }
      ]
    }
  } else {
    // Radial gradient
    return {
      centerX: 0.5,
      centerY: 0.5,
      radius: 0.5,
      colors: [
        { color: '#3498db', stop: 0 },
        { color: '#2980b9', stop: 1 }
      ]
    }
  }
}
</script>

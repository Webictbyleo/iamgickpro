<template>
  <div class="p-4 bg-gray-100 border-b">
    <h3 class="text-lg font-semibold mb-4">Canvas Debug Test</h3>
    <div class="space-x-2">
      <button 
        @click="testAddRectangle"
        class="px-3 py-1 bg-blue-500 text-white rounded hover:bg-blue-600"
      >
        Add Rectangle
      </button>
      <button 
        @click="testAddCircle"
        class="px-3 py-1 bg-green-500 text-white rounded hover:bg-green-600"
      >
        Add Circle
      </button>
      <button 
        @click="testAddText"
        class="px-3 py-1 bg-purple-500 text-white rounded hover:bg-purple-600"
      >
        Add Text
      </button>
      <button 
        @click="debugCanvas"
        class="px-3 py-1 bg-red-500 text-white rounded hover:bg-red-600"
      >
        Debug Canvas
      </button>
    </div>
    <div v-if="debugInfo" class="mt-4 p-2 bg-gray-800 text-green-400 font-mono text-xs rounded">
      <pre>{{ debugInfo }}</pre>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { useDesignEditor } from '@/composables/useDesignEditor'

const debugInfo = ref('')

// Get the EditorSDK instance from the composable
const { editorSDK } = useDesignEditor()

const log = (message: string) => {
  const timestamp = new Date().toLocaleTimeString()
  debugInfo.value += `[${timestamp}] ${message}\n`
  console.log(`[CanvasDebug] ${message}`)
}

const testAddRectangle = async () => {
  if (!editorSDK?.value) {
    log('ERROR: EditorSDK not available')
    return
  }

  try {
    log('Creating rectangle...')
    const layer = await editorSDK.value.layers.createLayer('shape', {
      name: 'Test Rectangle',
      x: Math.random() * 400 + 100,
      y: Math.random() * 300 + 100,
      width: 150,
      height: 100,
      properties: {
        shapeType: 'rectangle',
        fill: '#ff6b6b',
        stroke: '#333',
        strokeWidth: 2
      }
    })
    log(`Rectangle created with ID: ${layer.id}`)
  } catch (error) {
    log(`ERROR creating rectangle: ${error}`)
  }
}

const testAddCircle = async () => {
  if (!editorSDK?.value) {
    log('ERROR: EditorSDK not available')
    return
  }

  try {
    log('Creating circle...')
    const layer = await editorSDK.value.layers.createLayer('shape', {
      name: 'Test Circle',
      x: Math.random() * 400 + 100,
      y: Math.random() * 300 + 100,
      width: 100,
      height: 100,
      properties: {
        shapeType: 'circle',
        fill: '#4ecdc4',
        stroke: '#333',
        strokeWidth: 2
      }
    })
    log(`Circle created with ID: ${layer.id}`)
  } catch (error) {
    log(`ERROR creating circle: ${error}`)
  }
}

const testAddText = async () => {
  if (!editorSDK?.value) {
    log('ERROR: EditorSDK not available')
    return
  }

  try {
    log('Creating text...')
    const layer = await editorSDK.value.layers.createLayer('text', {
      name: 'Test Text',
      x: Math.random() * 400 + 100,
      y: Math.random() * 300 + 100,
      properties: {
        text: 'Hello World!',
        fontSize: 24,
        fill: '#333',
        fontFamily: 'Arial'
      }
    })
    log(`Text created with ID: ${layer.id}`)
  } catch (error) {
    log(`ERROR creating text: ${error}`)
  }
}

const debugCanvas = () => {
  if (!editorSDK?.value) {
    log('ERROR: EditorSDK not available')
    return
  }

  try {
    const state = editorSDK.value.getState()
    log('=== CANVAS DEBUG INFO ===')
    log(`Selected layers: ${state.selectedLayers.length}`)
    log(`Zoom: ${state.zoom}`)
    log(`Pan: (${state.panX}, ${state.panY})`)
    
    // Get stage info
    const stage = (editorSDK.value as any).stage
    if (stage) {
      log(`Stage size: ${stage.width()}x${stage.height()}`)
      log(`Stage children: ${stage.children.length}`)
      
      stage.children.forEach((layer: any, index: number) => {
        log(`  Layer ${index}: ${layer.children?.length || 0} children`)
      })
    }
    
    // Get container info
    const container = stage?.container()
    if (container) {
      log(`Container: ${container.tagName} (${container.offsetWidth}x${container.offsetHeight})`)
      log(`Container children: ${container.children.length}`)
    }
  } catch (error) {
    log(`ERROR debugging canvas: ${error}`)
  }
}
</script>

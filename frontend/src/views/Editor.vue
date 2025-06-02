<template>
  <div class="min-h-screen bg-gray-900 flex flex-col">
    <!-- Editor Toolbar -->
    <div class="bg-white border-b shadow-sm">
      <div class="flex items-center justify-between px-4 py-2">
        <div class="flex items-center space-x-4">
          <router-link to="/dashboard" class="text-primary-600 hover:text-primary-700">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
            </svg>
          </router-link>
          <div>
            <h1 class="text-lg font-semibold text-gray-900">{{ designName }}</h1>
            <p class="text-sm text-gray-500">{{ lastSaved }}</p>
          </div>
        </div>
        
        <div class="flex items-center space-x-2">
          <button 
            @click="handlePreview"
            class="btn-outline px-4 py-2"
          >
            Preview
          </button>
          <button 
            @click="handleShare"
            class="btn-outline px-4 py-2"
          >
            Share
          </button>
          <button 
            @click="handleExport"
            class="btn-primary px-4 py-2"
            :disabled="designStore.isLoading"
          >
            {{ designStore.isLoading ? 'Exporting...' : 'Export' }}
          </button>
        </div>
      </div>
    </div>

    <!-- Editor Layout -->
    <div class="flex-1 flex">
      <!-- Left Sidebar -->
      <div class="w-64 bg-white border-r">
        <div class="p-4">
          <h3 class="text-sm font-medium text-gray-900 mb-3">Layers</h3>
          <div class="space-y-2">
            <div 
              v-if="designStore.currentDesign?.designData.layers.length === 0"
              class="text-sm text-gray-500 text-center py-4"
            >
              No layers yet
            </div>
            <div
              v-for="layer in designStore.currentDesign?.designData.layers"
              :key="layer.id"
              class="p-2 bg-gray-50 rounded text-sm flex items-center justify-between hover:bg-gray-100 cursor-pointer"
            >
              <span>{{ layer.name }}</span>
              <div class="flex items-center space-x-1">
                <button
                  @click="() => designStore.removeLayer(layer.id)"
                  class="text-red-500 hover:text-red-700 text-xs"
                >
                  Delete
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Canvas Area -->
      <div class="flex-1 bg-gray-100 relative overflow-hidden">
        <div class="absolute inset-0 flex items-center justify-center">
          <div
            id="canvas-container"
            ref="canvasContainer"
            class="bg-white shadow-lg"
            :style="{ width: canvasWidth + 'px', height: canvasHeight + 'px' }"
          >
            <!-- Konva Stage will be mounted here -->
          </div>
        </div>
      </div>

      <!-- Right Sidebar -->
      <div class="w-80 bg-white border-l">
        <div class="p-4">
          <h3 class="text-sm font-medium text-gray-900 mb-3">Properties</h3>
          <div class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">
                Width
              </label>
              <input
                v-model="canvasWidth"
                type="number"
                class="input w-full"
              />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">
                Height
              </label>
              <input
                v-model="canvasHeight"
                type="number"
                class="input w-full"
              />
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted, computed, watch } from 'vue'
import { useRoute } from 'vue-router'
import { useDesignStore } from '@/stores/design'

const route = useRoute()
const designStore = useDesignStore()

const canvasContainer = ref<HTMLDivElement>()

// Computed properties from store
const designName = computed(() => designStore.currentDesign?.title || 'Untitled Design')
const lastSaved = computed(() => {
  if (designStore.currentDesign?.updatedAt) {
    const date = new Date(designStore.currentDesign.updatedAt)
    return `Last saved ${date.toLocaleTimeString()}`
  }
  return 'Not saved'
})

const canvasWidth = computed({
  get: () => designStore.currentDesign?.dimensions.width || 800,
  set: (value: number) => {
    if (designStore.currentDesign) {
      designStore.updateCanvasSize(value, canvasHeight.value)
    }
  }
})

const canvasHeight = computed({
  get: () => designStore.currentDesign?.dimensions.height || 600,
  set: (value: number) => {
    if (designStore.currentDesign) {
      designStore.updateCanvasSize(canvasWidth.value, value)
    }
  }
})

// Auto-save functionality
let autoSaveInterval: ReturnType<typeof setInterval>

const startAutoSave = () => {
  autoSaveInterval = setInterval(async () => {
    if (designStore.currentDesign) {
      await designStore.saveDesign()
    }
  }, 30000) // Auto-save every 30 seconds
}

const stopAutoSave = () => {
  if (autoSaveInterval) {
    clearInterval(autoSaveInterval)
  }
}

// Export functionality
const handleExport = async () => {
  if (!designStore.currentDesign) return
  
  try {
    await designStore.exportDesign(designStore.currentDesign.id, 'png')
  } catch (error) {
    console.error('Export failed:', error)
  }
}

// Preview functionality
const handlePreview = () => {
  // TODO: Implement preview functionality
  window.open(`/preview/${route.params.id}`, '_blank')
}

// Share functionality
const handleShare = () => {
  // TODO: Implement share functionality
  navigator.clipboard?.writeText(window.location.href)
}

onMounted(async () => {
  // Initialize Konva stage here
  // TODO: Initialize Konva canvas
  
  // If there's an ID in the route, load that design
  if (route.params.id && typeof route.params.id === 'string') {
    const result = await designStore.loadDesign(route.params.id)
    if (!result.success) {
      console.error('Failed to load design:', result.error)
      // Could redirect to 404 or show error message
    }
  } else {
    // Create new design
    designStore.createNewDesign()
  }
  
  // Start auto-save
  startAutoSave()
})

onUnmounted(() => {
  // Cleanup Konva stage
  // TODO: Cleanup Konva canvas
  
  // Stop auto-save
  stopAutoSave()
  
  // Save before leaving
  if (designStore.currentDesign) {
    designStore.saveDesign()
  }
})
</script>

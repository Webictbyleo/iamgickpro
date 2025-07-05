<template>
  <div class="p-4">
    <div class="flex items-center justify-between mb-3">
      <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">Layers</h3>
    </div>
    
    <div v-if="layers.length === 0" class="text-center py-8 text-gray-500 dark:text-gray-400">
      <Square3Stack3DIcon class="w-12 h-12 mx-auto mb-3 text-gray-300 dark:text-gray-600" />
      <p class="text-sm">No layers yet</p>
      <p class="text-xs mt-1">Add elements to get started</p>
    </div>
    
    <div v-else class="space-y-1" ref="layersPanelContainer">
      <div
        v-for="(layer, index) in reversedLayers"
        :key="layer.id"
        :data-layer-id="layer.id"
        :draggable="true"
        tabindex="0"
        @dragstart="handleDragStart($event, layer)"
        @dragover="handleDragOver"
        @drop="handleDrop($event, layer)"
        @keydown.enter="handleLayerKeyboard(layer, $event)"
        @keydown.space.prevent="handleLayerKeyboard(layer, $event)"
        :class="[
          'group flex items-center p-2 rounded border transition-all duration-200 cursor-pointer focus:outline-none focus:ring-2 focus:ring-primary-500 dark:focus:ring-primary-400 focus:ring-offset-2 dark:focus:ring-offset-secondary-900 relative',
          isSelected(layer.id)
            ? 'bg-gray-50 dark:bg-gray-700 border-gray-300 dark:border-gray-500 shadow-sm before:absolute before:left-0 before:top-0 before:bottom-0 before:w-1 before:bg-primary-500 dark:before:bg-primary-400 before:rounded-l'
            : 'bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700 hover:border-gray-300 dark:hover:border-gray-500',
          draggedLayer?.id === layer.id ? 'opacity-50' : ''
        ]"
        @click="handleLayerClick(layer, $event)"
      >
        <!-- Layer Icon -->
        <div class="flex-shrink-0 mr-2">
          <component
            :is="getLayerIcon(layer.type)"
            :class="[
              'w-4 h-4',
              isSelected(layer.id)
                ? 'text-gray-700 dark:text-gray-300'
                : 'text-gray-600 dark:text-gray-400'
            ]"
          />
        </div>
        
        <!-- Layer Name -->
        <div class="flex-1 min-w-0">
          <input
            v-if="editingLayerId === layer.id"
            v-model="editingLayerName"
            @blur="finishEditingLayerName"
            @keydown.enter="finishEditingLayerName"
            @keydown.escape="cancelEditingLayerName"
            :class="[
              'w-full text-sm bg-transparent border-none outline-none focus:ring-2 focus:ring-primary-500 dark:focus:ring-primary-400 focus:ring-offset-1 dark:focus:ring-offset-secondary-800 rounded px-1',
              isSelected(layer.id)
                ? 'text-gray-900 dark:text-gray-100'
                : 'text-gray-900 dark:text-gray-100'
            ]"
            @click.stop
            ref="editInput"
          />
          <div
            v-else
            :class="[
              'text-sm font-medium truncate',
              isSelected(layer.id)
                ? 'text-gray-900 dark:text-gray-100'
                : 'text-gray-900 dark:text-gray-100'
            ]"
            @dblclick="startEditingLayerName(layer)"
          >
            {{ layer.name }}
          </div>
          <div :class="[
            'text-xs',
            isSelected(layer.id)
              ? 'text-gray-600 dark:text-gray-400'
              : 'text-gray-500 dark:text-gray-400'
          ]">
            {{ getLayerTypeLabel(layer.type) }}
          </div>
        </div>
        
        <!-- Layer Controls -->
        <div class="flex items-center space-x-1 opacity-0 group-hover:opacity-100 transition-opacity">
          <!-- Visibility Toggle -->
          <button
            @click.stop="$emit('toggle-visibility', layer.id)"
            :class="[
              'p-1 rounded transition-colors',
              layer.visible
                ? 'text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200'
                : 'text-gray-400 dark:text-gray-500 hover:text-gray-600 dark:hover:text-gray-300'
            ]"
            :title="layer.visible ? 'Hide Layer' : 'Show Layer'"
          >
            <EyeIcon v-if="layer.visible" class="w-4 h-4" />
            <EyeSlashIcon v-else class="w-4 h-4" />
          </button>
          
          <!-- Lock Toggle -->
          <button
            @click.stop="$emit('toggle-lock', layer.id)"
            :class="[
              'p-1 rounded transition-colors',
              layer.locked
                ? 'text-danger-600 dark:text-danger-400 hover:text-danger-800 dark:hover:text-danger-300'
                : 'text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200'
            ]"
            :title="layer.locked ? 'Unlock Layer' : 'Lock Layer'"
          >
            <LockClosedIcon v-if="layer.locked" class="w-4 h-4" />
            <LockOpenIcon v-else class="w-4 h-4" />
          </button>
          
          <!-- More Actions -->
          <BaseDropdown 
            placement="bottom-end" 
            width="w-48"
            triggerClasses="p-1 rounded text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200 transition-colors"
          >
            <template #trigger>
              <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                <path d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z" />
              </svg>
            </template>
            
            <template #default="{ close }">
              <button
                @click="handleDuplicate(layer.id, close)"
                class="w-full text-left px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 focus:bg-gray-100 dark:focus:bg-gray-700 flex items-center transition-colors focus:outline-none"
              >
                  <svg class="w-4 h-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                  </svg>
                  Duplicate
                </button>
                
                <button
                  @click="startEditingLayerName(layer, close)"
                  class="w-full text-left px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 focus:bg-gray-100 dark:focus:bg-gray-700 flex items-center transition-colors focus:outline-none"
                >
                  <svg class="w-4 h-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                  </svg>
                  Rename
                </button>
                
                <div class="border-t border-gray-200 dark:border-gray-600 my-1"></div>
                
                <button
                  @click="handleDelete(layer.id, close)"
                  class="w-full text-left px-4 py-2 text-sm text-danger-600 dark:text-danger-400 hover:bg-danger-50 dark:hover:bg-danger-900/20 focus:bg-danger-50 dark:focus:bg-danger-900/20 flex items-center transition-colors focus:outline-none"
                >
                  <svg class="w-4 h-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                  </svg>
                  Delete
                </button>
            </template>
          </BaseDropdown>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, nextTick, watch, onUnmounted } from 'vue'
import BaseButton from '@/components/common/BaseButton.vue'
import BaseDropdown from '@/components/common/BaseDropdown.vue'
import {
  Square3Stack3DIcon,
  DocumentTextIcon,
  PhotoIcon,
  EyeIcon,
  EyeSlashIcon,
  LockClosedIcon,
  LockOpenIcon
} from '@heroicons/vue/24/outline'
import type { Layer } from '@/types'

interface Props {
  layers: Layer[]
  selectedLayers: Layer[]
}

const props = defineProps<Props>()

const emit = defineEmits<{
  'select-layer': [layerId: number, event: MouseEvent]
  'duplicate-layer': [layerId: number]
  'delete-layer': [layerId: number]
  'toggle-visibility': [layerId: number]
  'toggle-lock': [layerId: number]
  'reorder-layers': [layerIds: number[]]
  'update-layer-name': [layerId: number, name: string]
}>()

// Layer editing
const editingLayerId = ref<number | null>(null)
const editingLayerName = ref('')
const editInput = ref<HTMLInputElement>()

// Drag state
const draggedLayer = ref<Layer | null>(null)

// Refs for auto-scroll functionality
const layersPanelContainer = ref<HTMLElement>()
const layerElements = ref<HTMLElement[]>([])
const scrollTimeout = ref<ReturnType<typeof setTimeout> | null>(null)

// Auto-scroll to selected layer when selection changes
watch(
  () => props.selectedLayers,
  (newSelectedLayers, oldSelectedLayers) => {
    if (newSelectedLayers.length > 0) {
      const selectedLayer = newSelectedLayers[0]
      
      // Only auto-scroll if the selection actually changed
      const wasAlreadySelected = oldSelectedLayers?.some(layer => layer.id === selectedLayer.id)
      if (!wasAlreadySelected) {
        // Use debounced scroll to avoid excessive calls
        if (scrollTimeout.value) {
          clearTimeout(scrollTimeout.value)
        }
        scrollTimeout.value = setTimeout(() => {
          scrollToLayer(selectedLayer.id)
        }, 100)
      }
    }
  },
  { deep: true, flush: 'post' }
)

// Function to scroll to a specific layer
const scrollToLayer = async (layerId: number) => {
  await nextTick() // Ensure DOM is updated
  
  const layerElement = document.querySelector(`[data-layer-id="${layerId}"]`) as HTMLElement
  
  if (layerElement) {
    // Find the actual scroll container (the parent with overflow-y-auto)
    let scrollContainer = layerElement.closest('.overflow-y-auto') as HTMLElement
    
    // Fallback to finding panel container if specific class not found
    if (!scrollContainer) {
      scrollContainer = layerElement.closest('[class*="overflow"]') as HTMLElement
    }
    
    // Final fallback to using scrollIntoView directly
    if (!scrollContainer) {
      layerElement.scrollIntoView({
        behavior: 'smooth',
        block: 'center',
        inline: 'nearest'
      })
    } else {
      // Calculate positions relative to the scroll container
      const containerRect = scrollContainer.getBoundingClientRect()
      const layerRect = layerElement.getBoundingClientRect()
      
      // Check if the layer is already fully visible
      const isVisible = (
        layerRect.top >= containerRect.top &&
        layerRect.bottom <= containerRect.bottom
      )
      
      if (!isVisible) {
        // Calculate scroll position to center the layer
        const containerScrollTop = scrollContainer.scrollTop
        const layerOffsetTop = layerElement.offsetTop
        const containerHeight = scrollContainer.clientHeight
        const layerHeight = layerElement.offsetHeight
        
        const targetScrollTop = layerOffsetTop - (containerHeight / 2) + (layerHeight / 2)
        
        // Smooth scroll to the calculated position
        scrollContainer.scrollTo({
          top: Math.max(0, targetScrollTop),
          behavior: 'smooth'
        })
      }
    }
    
    // Add a subtle highlight effect to indicate the scrolled-to layer
    layerElement.classList.add('layer-scroll-highlight')
    setTimeout(() => {
      layerElement.classList.remove('layer-scroll-highlight')
    }, 1500)
  }
}

// Computed
const reversedLayers = computed(() => [...props.layers].reverse())

const isSelected = (layerId: number): boolean => {
  return props.selectedLayers.some(layer => layer.id === layerId)
}

// Layer management
const handleLayerClick = (layer: Layer, event: MouseEvent) => {
  emit('select-layer', layer.id, event)
}

const handleLayerKeyboard = (layer: Layer, event: KeyboardEvent) => {
  // Create a synthetic MouseEvent for consistency with the emit signature
  const syntheticEvent = new MouseEvent('click', {
    bubbles: true,
    cancelable: true,
    ctrlKey: event.ctrlKey,
    metaKey: event.metaKey,
    shiftKey: event.shiftKey
  })
  emit('select-layer', layer.id, syntheticEvent)
}

const startEditingLayerName = async (layer: Layer, closeDropdown?: () => void) => {
  closeDropdown?.()
  editingLayerId.value = layer.id
  editingLayerName.value = layer.name
  
  await nextTick()
  editInput.value?.focus()
  editInput.value?.select()
}

const finishEditingLayerName = () => {
  if (!editingLayerId.value) return
  
  emit('update-layer-name', editingLayerId.value, editingLayerName.value)
  editingLayerId.value = null
  editingLayerName.value = ''
}

const cancelEditingLayerName = () => {
  editingLayerId.value = null
  editingLayerName.value = ''
}

const handleDuplicate = (layerId: number, closeDropdown: () => void) => {
  emit('duplicate-layer', layerId)
  closeDropdown()
}

const handleDelete = (layerId: number, closeDropdown: () => void) => {
  emit('delete-layer', layerId)
  closeDropdown()
}

// Drag and drop for layer reordering
const handleDragStart = (event: DragEvent, layer: Layer) => {
  draggedLayer.value = layer
  if (event.dataTransfer) {
    event.dataTransfer.effectAllowed = 'move'
  }
}

const handleDragOver = (event: DragEvent) => {
  event.preventDefault()
  if (event.dataTransfer) {
    event.dataTransfer.dropEffect = 'move'
  }
}

const handleDrop = (event: DragEvent, targetLayer: Layer) => {
  event.preventDefault()
  
  if (!draggedLayer.value || draggedLayer.value.id === targetLayer.id) {
    draggedLayer.value = null
    return
  }

  const currentLayers = [...props.layers]
  const draggedIndex = currentLayers.findIndex(l => l.id === draggedLayer.value!.id)
  const targetIndex = currentLayers.findIndex(l => l.id === targetLayer.id)

  if (draggedIndex !== -1 && targetIndex !== -1) {
    // Remove dragged layer and insert at target position
    const [draggedLayerData] = currentLayers.splice(draggedIndex, 1)
    currentLayers.splice(targetIndex, 0, draggedLayerData)
    
    // Emit reorder event
    emit('reorder-layers', currentLayers.map(l => l.id))
  }

  draggedLayer.value = null
}

// Helper functions
const getLayerIcon = (type: string) => {
  switch (type) {
    case 'text': return DocumentTextIcon
    case 'image': return PhotoIcon
    case 'shape': return Square3Stack3DIcon
    default: return Square3Stack3DIcon
  }
}

const getLayerTypeLabel = (type: string) => {
  return type.charAt(0).toUpperCase() + type.slice(1)
}

// Cleanup timeout on unmount
onUnmounted(() => {
  if (scrollTimeout.value) {
    clearTimeout(scrollTimeout.value)
  }
})
</script>

<style scoped>
/* Highlight effect for auto-scrolled layers */
.layer-scroll-highlight {
  box-shadow: 0 0 0 2px rgb(var(--color-primary-300) / 0.5);
  animation: pulse-highlight 1.5s ease-in-out;
}

@keyframes pulse-highlight {
  0% {
    box-shadow: 0 0 0 2px rgb(var(--color-primary-300) / 0);
  }
  50% {
    box-shadow: 0 0 0 2px rgb(var(--color-primary-300) / 0.75);
  }
  100% {
    box-shadow: 0 0 0 2px rgb(var(--color-primary-300) / 0);
  }
}

/* Ensure smooth scrolling for the layers container */
.space-y-1 {
  scroll-behavior: smooth;
}
</style>

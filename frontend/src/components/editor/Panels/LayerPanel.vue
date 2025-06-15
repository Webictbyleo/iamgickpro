<template>
  <div class="p-4">
    <div class="flex items-center justify-between mb-3">
      <h3 class="text-sm font-semibold text-gray-900">Layers</h3>
    </div>
    
    <div v-if="layers.length === 0" class="text-center py-8 text-gray-500">
      <Square3Stack3DIcon class="w-12 h-12 mx-auto mb-3 text-gray-300" />
      <p class="text-sm">No layers yet</p>
      <p class="text-xs mt-1">Add elements to get started</p>
    </div>
    
    <div v-else class="space-y-1">
      <div
        v-for="(layer, index) in reversedLayers"
        :key="layer.id"
        :draggable="true"
        @dragstart="handleDragStart($event, layer)"
        @dragover="handleDragOver"
        @drop="handleDrop($event, layer)"
        :class="[
          'group flex items-center p-2 rounded border transition-colors cursor-pointer',
          isSelected(layer.id)
            ? 'bg-blue-50 border-blue-200'
            : 'bg-white border-gray-200 hover:bg-gray-50 hover:border-gray-300',
          draggedLayer?.id === layer.id ? 'opacity-50' : ''
        ]"
        @click="handleLayerClick(layer, $event)"
      >
        <!-- Layer Icon -->
        <div class="flex-shrink-0 mr-2">
          <component
            :is="getLayerIcon(layer.type)"
            class="w-4 h-4 text-gray-600"
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
            class="w-full text-sm bg-transparent border-none outline-none"
            @click.stop
            ref="editInput"
          />
          <div
            v-else
            class="text-sm font-medium text-gray-900 truncate"
            @dblclick="startEditingLayerName(layer)"
          >
            {{ layer.name }}
          </div>
          <div class="text-xs text-gray-500">
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
                ? 'text-gray-600 hover:text-gray-800'
                : 'text-gray-400 hover:text-gray-600'
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
                ? 'text-red-600 hover:text-red-800'
                : 'text-gray-600 hover:text-gray-800'
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
            triggerClasses="p-1 rounded text-gray-600 hover:text-gray-800 transition-colors"
          >
            <template #trigger>
              <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                <path d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z" />
              </svg>
            </template>
            
            <template #default="{ close }">
              <div class="py-1">
                <button
                  @click="handleDuplicate(layer.id, close)"
                  class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 flex items-center"
                >
                  <svg class="w-4 h-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                  </svg>
                  Duplicate
                </button>
                
                <button
                  @click="startEditingLayerName(layer, close)"
                  class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 flex items-center"
                >
                  <svg class="w-4 h-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                  </svg>
                  Rename
                </button>
                
                <div class="border-t border-gray-100 my-1"></div>
                
                <button
                  @click="handleDelete(layer.id, close)"
                  class="w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50 flex items-center"
                >
                  <svg class="w-4 h-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                  </svg>
                  Delete
                </button>
              </div>
            </template>
          </BaseDropdown>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, nextTick } from 'vue'
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

// Computed
const reversedLayers = computed(() => [...props.layers].reverse())

const isSelected = (layerId: number): boolean => {
  return props.selectedLayers.some(layer => layer.id === layerId)
}

// Layer management
const handleLayerClick = (layer: Layer, event: MouseEvent) => {
  emit('select-layer', layer.id, event)
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
</script>

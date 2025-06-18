<template>
  <div class="h-full flex flex-col bg-white">
    <!-- Content Area -->
    <div class="flex-1 overflow-y-auto">
      <!-- Dynamic Plugin Component -->
      <component
        :is="currentPluginComponent"
        v-if="currentPluginComponent"
        :layer-id="selectedLayer.id"
        :layer-type="selectedLayer.type"
        :plugin-id="pluginId"
        @close="closePanel"
        @layer-updated="handleLayerUpdated"
      />
      
      <!-- Fallback for unknown plugins -->
      <div v-else class="p-4">
        <div class="text-center py-12">
          <div class="w-16 h-16 mx-auto mb-4 bg-gray-100 rounded-full flex items-center justify-center">
            <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
            </svg>
          </div>
          <h4 class="text-lg font-medium text-gray-900 mb-2">Plugin Not Available</h4>
          <p class="text-sm text-gray-500 mb-4">
            The plugin "{{ pluginId }}" could not be loaded or is not available.
          </p>
          <button
            @click="closePanel"
            class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors text-sm font-medium"
          >
            Close Panel
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import type { Layer, PluginLayerUpdate } from '@/types';
import { ref, computed, watch, defineAsyncComponent } from 'vue'

interface Props {
  pluginId: string | null
  selectedLayer: Layer
}

const props = defineProps<Props>()

const emit = defineEmits<{
  close: []
  layerUpdated: [update: PluginLayerUpdate]
}>()

// Plugin component registry
const pluginComponents = {
  removebg: defineAsyncComponent(() => import('./RemoveBgPanel.vue')),
  // Add more plugins here as they are created
  // imageenhancer: defineAsyncComponent(() => import('./ImageEnhancerPanel.vue')),
  // textgenerator: defineAsyncComponent(() => import('./TextGeneratorPanel.vue')),
}

// Computed properties
const currentPluginComponent = computed(() => {
  if (!props.pluginId) return null
  return pluginComponents[props.pluginId as keyof typeof pluginComponents] || null
})

// Methods
const closePanel = () => {
  emit('close')
}

const handleLayerUpdated = (update: PluginLayerUpdate) => {
  emit('layerUpdated', update)
}

// Watch for plugin changes and log for debugging
watch(() => props.pluginId, (newPluginId) => {
  if (newPluginId) {
    console.log(`Loading plugin panel: ${newPluginId}`)
  }
})
</script>



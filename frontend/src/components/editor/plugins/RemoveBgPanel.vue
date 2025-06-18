<template>
  <div class="p-6 space-y-6 bg-white dark:bg-gray-900">
    <!-- Header with Enhanced Visual -->
    <div class="flex items-center justify-between">
      <div class="flex items-center space-x-4">
        <div class="relative">
          <div class="p-3 bg-gradient-to-br from-purple-500 to-pink-500 rounded-xl shadow-lg">
            <ScissorsIcon class="w-6 h-6 text-white" />
          </div>
          <div class="absolute -top-1 -right-1 p-1 bg-yellow-400 rounded-full">
            <SparklesIcon class="w-3 h-3 text-yellow-900" />
          </div>
        </div>
        <div>
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Background Remover</h3>
          <p class="text-sm text-gray-500 dark:text-gray-400">AI-powered background removal</p>
        </div>
      </div>
    </div>

    <!-- Current Layer Info -->
    <div v-if="currentLayer" class="p-4 bg-gray-50 dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
      <div class="flex items-center space-x-3">
        <PhotoIcon class="w-5 h-5 text-gray-400" />
        <div>
          <p class="text-sm font-medium text-gray-900 dark:text-white">{{ currentLayer.name }}</p>
          <p class="text-xs text-gray-500 dark:text-gray-400">{{ layerType }} layer • ID: {{ layerId }}</p>
        </div>
      </div>
    </div>

    <!-- Status Display with Enhanced Design -->
    <div v-if="pluginStatus !== 'not_processed'" class="p-4 rounded-lg border" :class="statusClasses">
      <div class="flex items-center space-x-3">
        <component :is="statusIcon" class="w-5 h-5 flex-shrink-0" />
        <div class="flex-1">
          <p class="text-sm font-medium">{{ statusMessage }}</p>
          <div v-if="hasOriginalBackup" class="text-xs mt-1 opacity-75">
            Original image is safely backed up
          </div>
        </div>
      </div>
    </div>

    <!-- Main Controls -->
    <div v-if="!isProcessing" class="space-y-6">
      <!-- Remove Background Section -->
      <div v-if="canRemove" class="space-y-4">
        <div class="flex items-center justify-between">
          <h4 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center space-x-2">
            <ScissorsIcon class="w-5 h-5 text-purple-600" />
            <span>Remove Background</span>
          </h4>
        </div>
        
        <!-- Feature Highlights -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mb-4">
          <div class="flex items-center space-x-2 text-sm text-gray-600 dark:text-gray-400">
            <CheckCircleIcon class="w-4 h-4 text-green-500" />
            <span>AI-powered precision</span>
          </div>
          <div class="flex items-center space-x-2 text-sm text-gray-600 dark:text-gray-400">
            <CheckCircleIcon class="w-4 h-4 text-green-500" />
            <span>Original backup kept</span>
          </div>
          <div class="flex items-center space-x-2 text-sm text-gray-600 dark:text-gray-400">
            <CheckCircleIcon class="w-4 h-4 text-green-500" />
            <span>Transparent background</span>
          </div>
          <div class="flex items-center space-x-2 text-sm text-gray-600 dark:text-gray-400">
            <CheckCircleIcon class="w-4 h-4 text-green-500" />
            <span>Multiple formats</span>
          </div>
        </div>
        
        <!-- Advanced Options -->
        <div class="space-y-4">
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Output Size
              </label>
              <select
                v-model="removeOptions.size"
                class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-colors"
              >
                <option value="auto">Auto (Recommended)</option>
                <option value="preview">Preview (640×640)</option>
                <option value="small">Small (640×640)</option>
                <option value="regular">Regular (1080×1080)</option>
                <option value="medium">Medium (1500×1500)</option>
                <option value="hd">HD (2000×2000)</option>
                <option value="full">Full Size</option>
              </select>
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Output Format
              </label>
              <select
                v-model="removeOptions.format"
                class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-colors"
              >
                <option value="png">PNG (Transparent)</option>
                <option value="jpg">JPG (White background)</option>
              </select>
            </div>
          </div>

          <!-- Advanced Settings Toggle -->
          <button
            @click="showAdvanced = !showAdvanced"
            class="flex items-center space-x-2 text-sm text-purple-600 dark:text-purple-400 hover:text-purple-700 dark:hover:text-purple-300 transition-colors font-medium"
          >
            <component :is="showAdvanced ? ChevronUpIcon : ChevronDownIcon" class="w-4 h-4" />
            <span>Advanced Settings</span>
          </button>

          <!-- Advanced Settings -->
          <div v-if="showAdvanced" class="space-y-4 p-4 bg-gradient-to-br from-gray-50 to-gray-100 dark:from-gray-800 dark:to-gray-700 rounded-lg border border-gray-200 dark:border-gray-600">
            <div class="flex items-center justify-between">
              <div class="flex items-center space-x-3">
                <input
                  id="add-shadow"
                  v-model="removeOptions.add_shadow"
                  type="checkbox"
                  class="w-4 h-4 text-purple-600 bg-gray-100 border-gray-300 rounded focus:ring-purple-500 dark:focus:ring-purple-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600"
                >
                <label for="add-shadow" class="text-sm font-medium text-gray-700 dark:text-gray-300">
                  Add natural shadow
                </label>
              </div>
              <InformationCircleIcon class="w-4 h-4 text-gray-400" title="Adds a subtle shadow effect to the subject" />
            </div>
            
            <div class="flex items-center justify-between">
              <div class="flex items-center space-x-3">
                <input
                  id="semitransparency"
                  v-model="removeOptions.semitransparency"
                  type="checkbox"
                  class="w-4 h-4 text-purple-600 bg-gray-100 border-gray-300 rounded focus:ring-purple-500 dark:focus:ring-purple-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600"
                >
                <label for="semitransparency" class="text-sm font-medium text-gray-700 dark:text-gray-300">
                  Preserve semitransparency
                </label>
              </div>
              <InformationCircleIcon class="w-4 h-4 text-gray-400" title="Keeps semi-transparent areas like glass or hair" />
            </div>
          </div>
        </div>

        <ModernButton
          @click="removeBackground"
          :loading="isProcessing"
          class="w-full py-3 text-base font-semibold"
          variant="primary"
        >
          <ScissorsIcon class="w-5 h-5 mr-2" />
          Remove Background
        </ModernButton>
      </div>

      <!-- Restore Background Section -->
      <div v-if="canRestore" class="space-y-4">
        <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
          <div class="flex items-center justify-between mb-4">
            <h4 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center space-x-2">
              <ArrowUturnLeftIcon class="w-5 h-5 text-blue-600" />
              <span>Restore Original</span>
            </h4>
          </div>
          
          <div class="p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800 mb-4">
            <div class="flex items-start space-x-3">
              <InformationCircleIcon class="w-5 h-5 text-blue-600 flex-shrink-0 mt-0.5" />
              <div>
                <p class="text-sm font-medium text-blue-900 dark:text-blue-200">Original Safely Stored</p>
                <p class="text-xs text-blue-700 dark:text-blue-300 mt-1">
                  Your original image with background is safely backed up and can be restored at any time.
                </p>
              </div>
            </div>
          </div>
          
          <ModernButton
            @click="restoreBackground"
            :loading="isProcessing"
            class="w-full py-3 text-base font-semibold"
            variant="secondary"
          >
            <ArrowUturnLeftIcon class="w-5 h-5 mr-2" />
            Restore Original Background
          </ModernButton>
        </div>
      </div>

      <!-- Preview Section -->
      <div v-if="hasPreview" class="space-y-4">
        <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
          <div class="flex items-center justify-between mb-4">
            <h4 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center space-x-2">
              <PhotoIcon class="w-5 h-5 text-green-600" />
              <span>Preview</span>
            </h4>
          </div>
          
          <div class="relative bg-gradient-to-br from-gray-100 to-gray-200 dark:from-gray-800 dark:to-gray-700 rounded-xl overflow-hidden border border-gray-200 dark:border-gray-600 shadow-inner">
            <!-- Checkerboard pattern for transparency -->
            <div class="absolute inset-0 opacity-20 checkerboard-pattern"></div>
            <img
              :src="previewUrl"
              alt="Background removed preview"
              class="relative w-full h-auto max-h-64 object-contain p-4"
            >
          </div>
          
          <div class="mt-3 text-center">
            <p class="text-xs text-gray-500 dark:text-gray-400">
              Preview shows transparency with checkerboard pattern
            </p>
          </div>
        </div>
      </div>
    </div>

    <!-- Enhanced Processing State -->
    <div v-if="isProcessing" class="text-center py-12">
      <div class="inline-flex flex-col items-center space-y-4">
        <div class="relative">
          <div class="animate-spin rounded-full h-12 w-12 border-4 border-purple-200 border-t-purple-600"></div>
          <div class="absolute inset-2 bg-purple-100 rounded-full flex items-center justify-center">
            <ScissorsIcon class="w-4 h-4 text-purple-600" />
          </div>
        </div>
        <div class="space-y-1">
          <p class="text-sm font-medium text-gray-900 dark:text-white">{{ processingMessage }}</p>
          <p class="text-xs text-gray-500 dark:text-gray-400">This may take a few seconds...</p>
        </div>
      </div>
    </div>

    <!-- Enhanced Error State -->
    <div v-if="error" class="p-6 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-xl">
      <div class="flex items-start space-x-4">
        <div class="flex-shrink-0">
          <div class="p-2 bg-red-100 dark:bg-red-800/50 rounded-lg">
            <ExclamationTriangleIcon class="w-5 h-5 text-red-600 dark:text-red-400" />
          </div>
        </div>
        <div class="flex-1">
          <h4 class="text-sm font-semibold text-red-800 dark:text-red-200 mb-2">Error Processing Image</h4>
          <p class="text-sm text-red-700 dark:text-red-300 mb-3">{{ error }}</p>
          <div class="flex space-x-3">
            <button
              @click="error = undefined"
              class="text-sm text-red-600 dark:text-red-400 hover:text-red-800 dark:hover:text-red-200 font-medium transition-colors"
            >
              Dismiss
            </button>
            <button
              @click="removeBackground"
              class="text-sm text-red-600 dark:text-red-400 hover:text-red-800 dark:hover:text-red-200 font-medium transition-colors"
            >
              Try Again
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Enhanced Credits Information -->
    <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
      <div class="flex items-center justify-center space-x-2 text-xs text-gray-500 dark:text-gray-400">
        <InformationCircleIcon class="w-4 h-4" />
        <span>Each background removal uses 1 API credit • Configure API key in settings</span>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue'
import { pluginAPI } from '@/services/api'
import { useDesignStore } from '@/stores/design'
import ModernButton from '@/components/common/ModernButton.vue'
import {
  ScissorsIcon,
  XMarkIcon,
  ArrowUturnLeftIcon,
  ChevronUpIcon,
  ChevronDownIcon,
  ExclamationTriangleIcon,
  CheckCircleIcon,
  ClockIcon,
  InformationCircleIcon,
  SparklesIcon,
  PhotoIcon
} from '@heroicons/vue/24/outline'
import type { PluginLayerUpdate, Transform, Layer, RemoveBgPluginData, RemoveBgApiResponse } from '@/types'

interface Props {
  layerId: number
  layerType: string
}

const props = defineProps<Props>()

const emit = defineEmits<{
  layerUpdated: [update: PluginLayerUpdate]
}>()

// Store and layer management
const designStore = useDesignStore()

// Get layer data reactively
const currentLayer = computed((): Layer | null => {
  return designStore.getLayerById(props.layerId)
})

// Get plugin data from the layer
const pluginData = computed((): RemoveBgPluginData | null => {
  return currentLayer.value?.plugins?.removebg || null
})

// State
const isProcessing = ref(false)
const processingMessage = ref('')
const error = ref<string | undefined>(undefined)
const showAdvanced = ref(false)
const previewUrl = ref<string | undefined>(undefined)

// Remove options
const removeOptions = ref({
  size: 'auto',
  format: 'png',
  add_shadow: false,
  semitransparency: true
})

// Computed properties
const pluginStatus = computed(() => {
  return pluginData.value?.current_state || 'not_processed'
})

const canRemove = computed(() => {
  return pluginStatus.value === 'not_processed' || pluginStatus.value === 'background_original'
})

const canRestore = computed(() => {
  return pluginStatus.value === 'background_removed' && hasOriginalBackup.value
})

const hasPreview = computed(() => {
  return previewUrl.value !== undefined && previewUrl.value !== ''
})

const hasOriginalBackup = computed(() => {
  return pluginData.value?.original_image !== undefined && pluginData.value?.original_image?.src !== undefined
})

const hasProcessedImage = computed(() => {
  return pluginData.value?.processed_images?.removed_bg?.src !== undefined
})

const statusClasses = computed(() => {
  switch (pluginStatus.value) {
    case 'background_removed':
      return 'bg-green-50 dark:bg-green-900/20 border-green-200 dark:border-green-800 text-green-800 dark:text-green-200'
    case 'background_original':
      return 'bg-blue-50 dark:bg-blue-900/20 border-blue-200 dark:border-blue-800 text-blue-800 dark:text-blue-200'
    default:
      return 'bg-gray-50 dark:bg-gray-800/50 border-gray-200 dark:border-gray-700 text-gray-800 dark:text-gray-200'
  }
})

const statusIcon = computed(() => {
  switch (pluginStatus.value) {
    case 'background_removed':
      return CheckCircleIcon
    case 'background_original':
      return PhotoIcon
    default:
      return InformationCircleIcon
  }
})

const statusMessage = computed(() => {
  switch (pluginStatus.value) {
    case 'background_removed':
      return 'Background has been removed'
    case 'background_original':
      return 'Original background is active'
    case 'not_processed':
      return 'Ready to remove background'
    default:
      return 'Unknown status'
  }
})

// Methods
const removeBackground = async () => {
  if (props.layerType !== 'image') {
    error.value = 'Background removal is only available for image layers'
    return
  }

  isProcessing.value = true
  processingMessage.value = 'Removing background...'
  error.value = undefined

  try {
    const response = await pluginAPI.executeCommand({
      pluginId: 'removebg',
      command: 'remove_background',
      layerId: props.layerId,
      parameters: removeOptions.value
    })

    if (response.data && response.data.success) {
      // The plugin status will be updated automatically through the layer update
      emit('layerUpdated', {
        layerId: props.layerId,
        plugins: response.data.data.layer.plugins,
        updates: {
          properties: { src: response.data.data.result.processed_image }
        }
      })
      
      // Set preview to the processed image
      previewUrl.value = response.data.data.result.processed_image
    } else {
      throw new Error('Failed to remove background')
    }
  } catch (err: any) {
    error.value = err.response?.data?.message || err.message || 'Failed to remove background'
  } finally {
    isProcessing.value = false
    processingMessage.value = ''
  }
}

const restoreBackground = async () => {
  isProcessing.value = true
  processingMessage.value = 'Restoring background...'
  error.value = undefined

  try {
    const response = await pluginAPI.executeCommand({
      pluginId: 'removebg',
      command: 'restore_background',
      layerId: props.layerId
    })

    if (response.data && response.data.success) {
      // The plugin status will be updated automatically through the layer update
      emit('layerUpdated', {
        layerId: props.layerId,
        plugins: response.data.data.layer.plugins,
        updates: {
          properties: { src: response.data.data.result.restored_image }
        }
      })
      previewUrl.value = undefined
    } else {
      throw new Error('Failed to restore background')
    }
  } catch (err: any) {
    error.value = err.response?.data?.message || err.message || 'Failed to restore background'
  } finally {
    isProcessing.value = false
    processingMessage.value = ''
  }
}

const loadPreview = async () => {
  try {
    const response = await pluginAPI.executeCommand({
      pluginId: 'removebg',
      command: 'preview_removal',
      layerId: props.layerId
    })

    if (response.data?.success && response.data.data?.result?.preview_url) {
      previewUrl.value = response.data.data.result.preview_url
    }
  } catch (err) {
    console.error('Failed to load preview:', err)
  }
}

// Watch for layer changes to update status
watch(
  () => [props.layerId, pluginData.value],
  ([newLayerId, newPluginData]) => {
    console.log('🔄 Layer or plugin data changed:', { layerId: newLayerId, pluginData: newPluginData })
    
    // Load preview from processed image if available
    if (typeof newPluginData === 'object' && newPluginData?.current_state === 'background_removed') {
      const processedImageSrc = newPluginData?.processed_images?.removed_bg?.src
      if (processedImageSrc) {
        previewUrl.value = processedImageSrc
      }
    }
  },
  { immediate: true, deep: true }
)

// Lifecycle
onMounted(async () => {
  console.log('🔌 RemoveBgPanel mounted for layer:', props.layerId)
  console.log('📊 Current layer data:', currentLayer.value)
  console.log('🔧 Plugin data:', pluginData.value)
  
  // Initialize preview if processed image is available
  if (pluginData.value?.processed_images?.removed_bg?.src) {
    previewUrl.value = pluginData.value.processed_images.removed_bg.src
  }
  
  // Validate layer type
  if (props.layerType !== 'image') {
    error.value = 'Background removal is only available for image layers'
  }
})
</script>

<style scoped>
.checkerboard-pattern {
  background-image: 
    linear-gradient(45deg, #ccc 25%, transparent 25%), 
    linear-gradient(45deg, transparent 75%, #ccc 75%), 
    linear-gradient(45deg, #ccc 25%, transparent 25%), 
    linear-gradient(45deg, transparent 75%, #ccc 75%);
  background-size: 20px 20px;
  background-position: 0 0, 0 0, 10px 10px, 10px 10px;
}

.dark .checkerboard-pattern {
  background-image: 
    linear-gradient(45deg, #444 25%, transparent 25%), 
    linear-gradient(45deg, transparent 75%, #444 75%), 
    linear-gradient(45deg, #444 25%, transparent 25%), 
    linear-gradient(45deg, transparent 75%, #444 75%);
}
</style>

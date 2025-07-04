<template>
  <Transition name="floating-toolbar">
    <div 
      v-if="shouldShow"
      class="relative bg-secondary-50/95 dark:bg-secondary-800/95 rounded-xl shadow-xl border border-secondary-200 dark:border-secondary-600 px-4 py-2.5 flex items-center space-x-3 "
      :style="floatingStyle"
    >
      <!-- Context-specific toolbars based on selected layer or active tool -->
      
      <!-- Text Layer Toolbar -->
      <TextToolbar
        v-if="selectedLayer && selectedLayer.type === 'text'"
        :fontFamily="selectedLayer.properties?.fontFamily"
        :fontSize="selectedLayer.properties?.fontSize"
        :fontWeight="selectedLayer.properties?.fontWeight"
        :fontStyle="selectedLayer.properties?.fontStyle"
        :textDecoration="selectedLayer.properties?.textDecoration"
        :textAlign="selectedLayer.properties?.textAlign"
        :color="selectedLayer.properties?.color"
        @update="(props) => $emit('tool-update', 'text', props)"
      />
      
      <!-- Shape Layer Toolbar -->
      <ShapeToolbar
        v-else-if="selectedLayer && selectedLayer.type === 'shape'"
        :shapeType="selectedLayer.properties?.shapeType"
        :fill="selectedLayer.properties?.fill"
        :stroke="selectedLayer.properties?.stroke"
        :strokeWidth="selectedLayer.properties?.strokeWidth"
        :cornerRadius="selectedLayer.properties?.cornerRadius"
        :hasShadow="selectedLayer.properties?.hasShadow"
        @update="(props) => $emit('tool-update', 'shape', props)"
      />
      
      <!-- Image Layer Toolbar -->
      <ImageToolbar
        v-else-if="selectedLayer && selectedLayer.type === 'image'"
        :alt="selectedLayer.properties?.alt"
        :borderRadius="selectedLayer.properties?.borderRadius"
        :objectPosition="selectedLayer.properties?.objectPosition"
        :preserveAspectRatio="selectedLayer.properties?.preserveAspectRatio"
        :quality="selectedLayer.properties?.quality"
        :brightness="selectedLayer.properties?.brightness"
        :contrast="selectedLayer.properties?.contrast"
        :saturation="selectedLayer.properties?.saturation"
        :blur="selectedLayer.properties?.blur"
        :hue="selectedLayer.properties?.hue"
        :sepia="selectedLayer.properties?.sepia"
        :grayscale="selectedLayer.properties?.grayscale"
        :invert="selectedLayer.properties?.invert"
        :flipX="selectedLayer.properties?.flipX"
        :flipY="selectedLayer.properties?.flipY"
        :shadowEnabled="selectedLayer.properties?.shadow?.enabled"
        @update="(props) => $emit('tool-update', 'image', props)"
        @edit-image="$emit('toggle-panel', 'image-editing', selectedLayer)"
        @plugin-tool="(props) => $emit('plugin-tool', props)"
      />
      
      <!-- SVG Layer Toolbar -->
      <SVGToolbar
        v-else-if="selectedLayer && selectedLayer.type === 'svg'"
        :src="selectedLayer.properties?.src"
        :fillColors="selectedLayer.properties?.fillColors"
        :strokeColors="selectedLayer.properties?.strokeColors"
        :strokeWidths="selectedLayer.properties?.strokeWidths"
        :preserveAspectRatio="selectedLayer.properties?.preserveAspectRatio"
        :svgElements="selectedLayer.properties?.svgElements"
        @update="(props) => $emit('tool-update', 'svg', props)"
      />
      
      <!-- Tool-specific Toolbar (when no layer selected but tool is active) -->
      <component 
        v-else-if="!selectedLayer && activeTool && toolOptions[activeTool]"
        :is="toolOptions[activeTool].component" 
        v-bind="toolOptions[activeTool].props"
        @update="handleToolUpdate"
      />

      <!-- Quick Actions for any selected layer -->
      <div 
        v-if="selectedLayer" 
        class="flex items-center space-x-1 ml-3 pl-3 border-l border-secondary-200 dark:border-secondary-600"
      >
        <!-- Position Controls -->
        <div class="flex items-center space-x-1 pr-2 mr-2 border-r border-secondary-200 dark:border-secondary-600">
          <IconDropdown
            :icon="PositionIcon"
            tooltip="Position presets"
            placement="bottom-start"
            text="Position"
            width="w-48"
            :showChevron="false"
            buttonClass="hover:bg-secondary-100 dark:hover:bg-secondary-700 text-secondary-600 hover:text-primary-600 dark:text-secondary-300 dark:hover:text-primary-400"
            iconClass="w-3.5 h-3.5"
          >
            <template #default="{ close }">
              <div class="p-2">
                <div class="text-xs font-medium text-secondary-500 dark:text-secondary-400 mb-2 px-2">Position Presets</div>
                <div class="grid grid-cols-3 gap-1 mb-3">
                  <!-- Top row -->
                  <button
                    @click="setPosition('top-left', close)"
                    class="p-2 rounded-lg hover:bg-secondary-100 dark:hover:bg-secondary-700 flex flex-col items-center space-y-1 text-xs text-secondary-600 dark:text-secondary-300"
                    title="Top Left"
                  >
                    <div class="w-6 h-6 border border-secondary-300 dark:border-secondary-600 rounded relative">
                      <div class="absolute top-0 left-0 w-2 h-2 bg-primary-500 rounded-sm"></div>
                    </div>
                    <span>Top Left</span>
                  </button>
                  <button
                    @click="setPosition('top-center', close)"
                    class="p-2 rounded-lg hover:bg-secondary-100 dark:hover:bg-secondary-700 flex flex-col items-center space-y-1 text-xs text-secondary-600 dark:text-secondary-300"
                    title="Top Center"
                  >
                    <div class="w-6 h-6 border border-secondary-300 dark:border-secondary-600 rounded relative">
                      <div class="absolute top-0 left-1/2 transform -translate-x-1/2 w-2 h-2 bg-primary-500 rounded-sm"></div>
                    </div>
                    <span>Top Center</span>
                  </button>
                  <button
                    @click="setPosition('top-right', close)"
                    class="p-2 rounded-lg hover:bg-secondary-100 dark:hover:bg-secondary-700 flex flex-col items-center space-y-1 text-xs text-secondary-600 dark:text-secondary-300"
                    title="Top Right"
                  >
                    <div class="w-6 h-6 border border-secondary-300 dark:border-secondary-600 rounded relative">
                      <div class="absolute top-0 right-0 w-2 h-2 bg-primary-500 rounded-sm"></div>
                    </div>
                    <span>Top Right</span>
                  </button>
                  
                  <!-- Middle row -->
                  <button
                    @click="setPosition('center-left', close)"
                    class="p-2 rounded-lg hover:bg-secondary-100 dark:hover:bg-secondary-700 flex flex-col items-center space-y-1 text-xs text-secondary-600 dark:text-secondary-300"
                    title="Center Left"
                  >
                    <div class="w-6 h-6 border border-secondary-300 dark:border-secondary-600 rounded relative">
                      <div class="absolute top-1/2 left-0 transform -translate-y-1/2 w-2 h-2 bg-primary-500 rounded-sm"></div>
                    </div>
                    <span>Left</span>
                  </button>
                  <button
                    @click="setPosition('center', close)"
                    class="p-2 rounded-lg hover:bg-secondary-100 dark:hover:bg-secondary-700 flex flex-col items-center space-y-1 text-xs text-secondary-600 dark:text-secondary-300"
                    title="Center"
                  >
                    <div class="w-6 h-6 border border-secondary-300 dark:border-secondary-600 rounded relative">
                      <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-2 h-2 bg-primary-500 rounded-sm"></div>
                    </div>
                    <span>Center</span>
                  </button>
                  <button
                    @click="setPosition('center-right', close)"
                    class="p-2 rounded-lg hover:bg-secondary-100 dark:hover:bg-secondary-700 flex flex-col items-center space-y-1 text-xs text-secondary-600 dark:text-secondary-300"
                    title="Center Right"
                  >
                    <div class="w-6 h-6 border border-secondary-300 dark:border-secondary-600 rounded relative">
                      <div class="absolute top-1/2 right-0 transform -translate-y-1/2 w-2 h-2 bg-primary-500 rounded-sm"></div>
                    </div>
                    <span>Right</span>
                  </button>
                  
                  <!-- Bottom row -->
                  <button
                    @click="setPosition('bottom-left', close)"
                    class="p-2 rounded-lg hover:bg-secondary-100 dark:hover:bg-secondary-700 flex flex-col items-center space-y-1 text-xs text-secondary-600 dark:text-secondary-300"
                    title="Bottom Left"
                  >
                    <div class="w-6 h-6 border border-secondary-300 dark:border-secondary-600 rounded relative">
                      <div class="absolute bottom-0 left-0 w-2 h-2 bg-primary-500 rounded-sm"></div>
                    </div>
                    <span>Bottom Left</span>
                  </button>
                  <button
                    @click="setPosition('bottom-center', close)"
                    class="p-2 rounded-lg hover:bg-secondary-100 dark:hover:bg-secondary-700 flex flex-col items-center space-y-1 text-xs text-secondary-600 dark:text-secondary-300"
                    title="Bottom Center"
                  >
                    <div class="w-6 h-6 border border-secondary-300 dark:border-secondary-600 rounded relative">
                      <div class="absolute bottom-0 left-1/2 transform -translate-x-1/2 w-2 h-2 bg-primary-500 rounded-sm"></div>
                    </div>
                    <span>Bottom Center</span>
                  </button>
                  <button
                    @click="setPosition('bottom-right', close)"
                    class="p-2 rounded-lg hover:bg-secondary-100 dark:hover:bg-secondary-700 flex flex-col items-center space-y-1 text-xs text-secondary-600 dark:text-secondary-300"
                    title="Bottom Right"
                  >
                    <div class="w-6 h-6 border border-secondary-300 dark:border-secondary-600 rounded relative">
                      <div class="absolute bottom-0 right-0 w-2 h-2 bg-primary-500 rounded-sm"></div>
                    </div>
                    <span>Bottom Right</span>
                  </button>
                </div>
                
                <!-- Current position display -->
                <div class="border-t border-secondary-200 dark:border-secondary-600 pt-2 px-2">
                  <div class="text-xs text-secondary-500 dark:text-secondary-400">
                    Current: X: {{ Math.round(selectedLayer.transform?.x || 0) }}, Y: {{ Math.round(selectedLayer.transform?.y || 0) }}
                  </div>
                </div>
              </div>
            </template>
          </IconDropdown>
        </div>

        <!-- Transparency Control -->
        <div class="flex items-center space-x-1 pr-2 mr-2 border-r border-secondary-200 dark:border-secondary-600">
          <IconDropdown
            :icon="TransparencyIcon"
            tooltip="Layer transparency"
            placement="bottom-start"
            text="Opacity"
            width="w-48"
            :showChevron="false"
            buttonClass="hover:bg-secondary-100 dark:hover:bg-secondary-700 text-secondary-600 hover:text-primary-600 dark:text-secondary-300 dark:hover:text-primary-400"
            iconClass="w-3.5 h-3.5"
          >
            <template #default="{ close }">
              <div class="p-4">
                <div class="flex items-center justify-between mb-3">
                  <span class="text-sm font-medium text-secondary-700 dark:text-secondary-300">Layer Opacity</span>
                  <span class="text-xs text-secondary-500 dark:text-secondary-400">{{ Math.round((selectedLayer.transform.opacity || 1) * 100) }}%</span>
                </div>
                
                <PropertySlider
                  :value="selectedLayer.transform.opacity || 1"
                  :min="0"
                  :max="1"
                  :step="0.01"
                  @update="(value: number) => $emit('update-layer-opacity', value)"
                  class="mb-3"
                />
                
                <!-- Opacity Presets -->
                <div class="flex flex-wrap gap-1">
                  <button
                    v-for="preset in opacityPresets"
                    :key="preset.value"
                    @click="() => { 
                      $emit('update-layer-opacity', preset.value); 
                      close(); 
                    }"
                    :class="[
                      'px-2 py-1 text-xs rounded border transition-colors',
                      Math.abs((selectedLayer.transform.opacity || 1) - preset.value) < 0.01
                        ? 'bg-primary-100 dark:bg-primary-900/30 border-primary-300 dark:border-primary-600 text-primary-700 dark:text-primary-300'
                        : 'bg-secondary-50 dark:bg-secondary-700 border-secondary-200 dark:border-secondary-600 text-secondary-600 dark:text-secondary-300 hover:bg-secondary-100 dark:hover:bg-secondary-600'
                    ]"
                  >
                    {{ preset.label }}
                  </button>
                </div>
                
                <div class="mt-2 text-xs text-secondary-500 dark:text-secondary-400">
                  Drag slider or click presets to adjust layer transparency
                </div>
              </div>
            </template>
          </IconDropdown>
        </div>

        <ModernButton
          variant="ghost"
          size="xs"
          @click="$emit('duplicate-layer')"
          tooltip="Duplicate (Ctrl+D)"
          class="hover:bg-secondary-100 dark:hover:bg-secondary-700 text-secondary-600 hover:text-primary-600 dark:text-secondary-300 dark:hover:text-primary-400"
        >
          <DocumentDuplicateIcon class="w-3.5 h-3.5" />
        </ModernButton>
        
        <ModernButton
          variant="ghost"
          size="xs"
          @click="$emit('delete-layer')"
          tooltip="Delete (Del)"
          class="hover:bg-danger-100 dark:hover:bg-danger-900/30 text-secondary-600 hover:text-danger-600 dark:text-secondary-300 dark:hover:text-danger-400"
        >
          <TrashIcon class="w-3.5 h-3.5" />
        </ModernButton>
        
        <ModernButton
          variant="ghost"
          size="xs"
          @click="$emit('lock-layer')"
          :tooltip="selectedLayer.locked ? 'Unlock Layer' : 'Lock Layer'"
          :class="selectedLayer.locked 
            ? 'text-amber-600 hover:text-amber-700 dark:text-amber-400 dark:hover:text-amber-300 bg-amber-100 dark:bg-amber-900/30' 
            : 'hover:bg-secondary-100 dark:hover:bg-secondary-700 text-secondary-600 hover:text-secondary-700 dark:text-secondary-300 dark:hover:text-secondary-200'"
        >
          <component 
            :is="selectedLayer.locked ? LockClosedIcon : LockOpenIcon" 
            class="w-3.5 h-3.5" 
          />
        </ModernButton>
      </div>
    </div>
  </Transition>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import type { Component } from 'vue'
import type { Layer, PluginEvent } from '@/types'
import { 
  DocumentDuplicateIcon, 
  TrashIcon, 
  LockClosedIcon, 
  LockOpenIcon
} from '@heroicons/vue/24/outline'
import PositionIcon from '@/components/icons/PositionIcon.vue'
import TransparencyIcon from '@/components/icons/TransparencyIcon.vue'
import ModernButton from '@/components/common/ModernButton.vue'
import IconDropdown from '@/components/ui/IconDropdown.vue'
import PropertySlider from '@/components/editor/Properties/PropertySlider.vue'
import TextToolbar from '@/components/editor/Toolbar/TextToolbar.vue'
import ShapeToolbar from '@/components/editor/Toolbar/ShapeToolbar.vue'
import ImageToolbar from '@/components/editor/Toolbar/ImageToolbar.vue'
import SVGToolbar from '@/components/editor/Toolbar/SVGToolbar.vue'

interface ToolOption {
  component: Component
  props: Record<string, any>
}

interface Props {
  selectedLayer?: Layer | null
  activeTool?: string
  position?: { x: number; y: number }
  show?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  selectedLayer: null,
  activeTool: undefined,
  position: () => ({ x: 0, y: 0 }),
  show: true
})

const emit = defineEmits<{
  'tool-update': [tool: string, props: any]
  'duplicate-layer': []
  'delete-layer': []
  'lock-layer': []
  'toggle-panel': [panelType: string, data?: any]
  'position-preset': [preset: string]
  'update-layer-opacity': [opacity: number]
  'plugin-tool': [event: PluginEvent]
}>()

// Opacity presets for quick selection
const opacityPresets = [
  { label: '100%', value: 1 },
  { label: '75%', value: 0.75 },
  { label: '50%', value: 0.5 },
  { label: '25%', value: 0.25 },
  { label: '10%', value: 0.1 }
]

// Tool-specific options and components - fixed 'shapes' to 'shape'
const toolOptions = computed((): Record<string, ToolOption> => ({
  text: {
    component: TextToolbar,
    props: {}
  },
  shape: {
    component: ShapeToolbar,
    props: {}
  },
  image: {
    component: ImageToolbar,
    props: {}
  },
  svg: {
    component: SVGToolbar,
    props: {}
  }
}))

// Show the toolbar if there's a selected layer or an active tool (but not select tool)
const shouldShow = computed(() => {
  if (!props.show) return false
  
  return (
    props.selectedLayer || 
    (props.activeTool && props.activeTool !== 'select' && props.activeTool !== 'pan')
  )
})

// Dynamic positioning for the floating toolbar
const floatingStyle = computed(() => {
  // For future use if we need dynamic positioning
  // Currently using relative positioning within the toolbar area
  return {
    maxWidth: '100%'
  }
})

const handleToolUpdate = (data: any) => {
  emit('tool-update', props.activeTool || 'select', data)
}

const setPosition = (preset: string, closeCallback: () => void) => {
  emit('position-preset', preset)
  // Don't automatically close the dropdown when clicking presets
  // closeCallback()
}
</script>

<style scoped>
/* Enhanced animations for the floating toolbar */
.floating-toolbar-enter-active {
  transition: all 0.25s cubic-bezier(0.25, 0.46, 0.45, 0.94);
}

.floating-toolbar-leave-active {
  transition: all 0.2s cubic-bezier(0.55, 0.055, 0.675, 0.19);
}

.floating-toolbar-enter-from {
  opacity: 0;
  transform: scale(0.9) translateY(-10px);
}

.floating-toolbar-leave-to {
  opacity: 0;
  transform: scale(0.95) translateY(-5px);
}

.floating-toolbar-enter-to,
.floating-toolbar-leave-from {
  opacity: 1;
  transform: scale(1) translateY(0);
}

/* Enhanced backdrop blur and shadow */
.backdrop-blur-md {
  backdrop-filter: blur(12px);
  -webkit-backdrop-filter: blur(12px);
}

/* Subtle border glow effect */
.border-secondary-200\/50 {
  border-color: rgba(229, 231, 235, 0.5);
  box-shadow: 0 0 0 1px rgba(255, 255, 255, 0.1) inset;
}

.dark .border-secondary-700\/50 {
  border-color: rgba(55, 65, 81, 0.5);
  box-shadow: 0 0 0 1px rgba(255, 255, 255, 0.05) inset;
}

/* Enhanced shadow */
.shadow-xl {
  box-shadow: 
    0 20px 25px -5px rgba(0, 0, 0, 0.1),
    0 10px 10px -5px rgba(0, 0, 0, 0.04),
    0 0 0 1px rgba(0, 0, 0, 0.05);
}

.dark .shadow-xl {
  box-shadow: 
    0 20px 25px -5px rgba(0, 0, 0, 0.25),
    0 10px 10px -5px rgba(0, 0, 0, 0.1),
    0 0 0 1px rgba(255, 255, 255, 0.05);
}
</style>

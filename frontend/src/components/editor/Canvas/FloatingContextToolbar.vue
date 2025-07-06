<template>
  <Transition name="floating-toolbar">
    <div 
      v-if="shouldShow"
      ref="toolbarRef"
      class="fixed flex flex-wrap items-start gap-x-3 gap-y-4 px-2 py-2 z-[9999] pointer-events-auto translate-y-10 toolbar-container"
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
        class="toolbar-section"
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
        class="toolbar-section"
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
        class="toolbar-section"
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
        class="toolbar-section"
      />
      
      <!-- Tool-specific Toolbar (when no layer selected but tool is active) -->
      <component 
        v-else-if="!selectedLayer && activeTool && toolOptions[activeTool]"
        :is="toolOptions[activeTool].component" 
        v-bind="toolOptions[activeTool].props"
        @update="handleToolUpdate"
        class="toolbar-section"
      />

      <!-- Quick Actions for any selected layer -->
      <div 
        v-if="selectedLayer" 
        class="toolbar-section toolbar-actions"
      >
        <!-- Position Controls -->
        <div class="flex items-center space-x-1">
          <IconDropdown
            :icon="PositionIcon"
            tooltip="Position presets"
            placement="bottom-start"
            text="Position"
            width="w-48"
            :showChevron="false"
            buttonClass="hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-600 hover:text-primary-600 dark:text-gray-300 dark:hover:text-primary-400"
            iconClass="w-3.5 h-3.5"
          >
            <template #default="{ close }">
              <div class="p-2">
                <div class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-2 px-2">Position Presets</div>
                <div class="grid grid-cols-3 gap-1 mb-3">
                  <!-- Top row -->
                  <button
                    @click="setPosition('top-left', close)"
                    class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 flex flex-col items-center space-y-1 text-xs text-gray-600 dark:text-gray-300"
                    title="Top Left"
                  >
                    <div class="w-6 h-6 border border-gray-300 dark:border-gray-500 rounded relative">
                      <div class="absolute top-0 left-0 w-2 h-2 bg-primary-500 rounded-sm"></div>
                    </div>
                    <span>Top Left</span>
                  </button>
                  <button
                    @click="setPosition('top-center', close)"
                    class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 flex flex-col items-center space-y-1 text-xs text-gray-600 dark:text-gray-300"
                    title="Top Center"
                  >
                    <div class="w-6 h-6 border border-gray-300 dark:border-gray-600 rounded relative">
                      <div class="absolute top-0 left-1/2 transform -translate-x-1/2 w-2 h-2 bg-primary-500 rounded-sm"></div>
                    </div>
                    <span>Top Center</span>
                  </button>
                  <button
                    @click="setPosition('top-right', close)"
                    class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 flex flex-col items-center space-y-1 text-xs text-gray-600 dark:text-gray-300"
                    title="Top Right"
                  >
                    <div class="w-6 h-6 border border-gray-300 dark:border-gray-600 rounded relative">
                      <div class="absolute top-0 right-0 w-2 h-2 bg-primary-500 rounded-sm"></div>
                    </div>
                    <span>Top Right</span>
                  </button>
                  
                  <!-- Middle row -->
                  <button
                    @click="setPosition('center-left', close)"
                    class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 flex flex-col items-center space-y-1 text-xs text-gray-600 dark:text-gray-300"
                    title="Center Left"
                  >
                    <div class="w-6 h-6 border border-gray-300 dark:border-gray-600 rounded relative">
                      <div class="absolute top-1/2 left-0 transform -translate-y-1/2 w-2 h-2 bg-primary-500 rounded-sm"></div>
                    </div>
                    <span>Left</span>
                  </button>
                  <button
                    @click="setPosition('center', close)"
                    class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 flex flex-col items-center space-y-1 text-xs text-gray-600 dark:text-gray-300"
                    title="Center"
                  >
                    <div class="w-6 h-6 border border-gray-300 dark:border-gray-600 rounded relative">
                      <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-2 h-2 bg-primary-500 rounded-sm"></div>
                    </div>
                    <span>Center</span>
                  </button>
                  <button
                    @click="setPosition('center-right', close)"
                    class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 flex flex-col items-center space-y-1 text-xs text-gray-600 dark:text-gray-300"
                    title="Center Right"
                  >
                    <div class="w-6 h-6 border border-gray-300 dark:border-gray-600 rounded relative">
                      <div class="absolute top-1/2 right-0 transform -translate-y-1/2 w-2 h-2 bg-primary-500 rounded-sm"></div>
                    </div>
                    <span>Right</span>
                  </button>
                  
                  <!-- Bottom row -->
                  <button
                    @click="setPosition('bottom-left', close)"
                    class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 flex flex-col items-center space-y-1 text-xs text-gray-600 dark:text-gray-300"
                    title="Bottom Left"
                  >
                    <div class="w-6 h-6 border border-gray-300 dark:border-gray-600 rounded relative">
                      <div class="absolute bottom-0 left-0 w-2 h-2 bg-primary-500 rounded-sm"></div>
                    </div>
                    <span>Bottom Left</span>
                  </button>
                  <button
                    @click="setPosition('bottom-center', close)"
                    class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 flex flex-col items-center space-y-1 text-xs text-gray-600 dark:text-gray-300"
                    title="Bottom Center"
                  >
                    <div class="w-6 h-6 border border-gray-300 dark:border-gray-600 rounded relative">
                      <div class="absolute bottom-0 left-1/2 transform -translate-x-1/2 w-2 h-2 bg-primary-500 rounded-sm"></div>
                    </div>
                    <span>Bottom Center</span>
                  </button>
                  <button
                    @click="setPosition('bottom-right', close)"
                    class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 flex flex-col items-center space-y-1 text-xs text-gray-600 dark:text-gray-300"
                    title="Bottom Right"
                  >
                    <div class="w-6 h-6 border border-gray-300 dark:border-gray-600 rounded relative">
                      <div class="absolute bottom-0 right-0 w-2 h-2 bg-primary-500 rounded-sm"></div>
                    </div>
                    <span>Bottom Right</span>
                  </button>
                </div>
                
                <!-- Current position display -->
                <div class="border-t border-gray-200 dark:border-gray-600 pt-2 px-2">
                  <div class="text-xs text-gray-500 dark:text-gray-400">
                    Current: X: {{ Math.round(selectedLayer.transform?.x || 0) }}, Y: {{ Math.round(selectedLayer.transform?.y || 0) }}
                  </div>
                </div>
              </div>
            </template>
          </IconDropdown>
        </div>

        <!-- Transparency Control -->
        <div class="flex items-center space-x-1">
          <IconDropdown
            :icon="TransparencyIcon"
            tooltip="Layer transparency"
            placement="bottom-start"
            text="Opacity"
            width="w-48"
            :showChevron="false"
            buttonClass="hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-600 hover:text-primary-600 dark:text-gray-300 dark:hover:text-primary-400"
            iconClass="w-3.5 h-3.5"
          >
            <template #default="{ close }">
              <div class="p-4">
                <div class="flex items-center justify-between mb-3">
                  <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Layer Opacity</span>
                  <span class="text-xs text-gray-500 dark:text-gray-400">{{ Math.round((selectedLayer.transform.opacity || 1) * 100) }}%</span>
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
                        : 'bg-gray-50 dark:bg-gray-700 border-gray-200 dark:border-gray-600 text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-600'
                    ]"
                  >
                    {{ preset.label }}
                  </button>
                </div>
                
                <div class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                  Drag slider or click presets to adjust layer transparency
                </div>
              </div>
            </template>
          </IconDropdown>
        </div>

        <!-- Action Buttons -->
        <div class="flex items-center space-x-1">
          <ModernButton
            variant="ghost"
            size="xs"
            @click="$emit('duplicate-layer')"
            tooltip="Duplicate (Ctrl+D)"
            class="hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-600 hover:text-primary-600 dark:text-gray-300 dark:hover:text-primary-400"
          >
            <DocumentDuplicateIcon class="w-3.5 h-3.5" />
          </ModernButton>
          
          <ModernButton
            variant="ghost"
            size="xs"
            @click="$emit('delete-layer')"
            tooltip="Delete (Del)"
            class="hover:bg-danger-100 dark:hover:bg-danger-900/30 text-gray-600 hover:text-danger-600 dark:text-gray-300 dark:hover:text-danger-400"
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
              : 'hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-600 hover:text-gray-700 dark:text-gray-300 dark:hover:text-gray-200'"
          >
            <component 
              :is="selectedLayer.locked ? LockClosedIcon : LockOpenIcon" 
              class="w-3.5 h-3.5" 
            />
          </ModernButton>
        </div>
      </div>
    </div>
  </Transition>
</template>

<script setup lang="ts">
import { computed, ref, nextTick, watch, onMounted, onUnmounted } from 'vue'
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
import { Container } from 'konva/lib/Container'

interface ToolOption {
  component: Component
  props: Record<string, any>
}

interface Props {
  selectedLayer?: Layer | null
  activeTool?: string
  show?: boolean,
  containerWidth?: number,
  position: { x: number, y: number }
}

const props = withDefaults(defineProps<Props>(), {
  selectedLayer: null,
  activeTool: undefined,
  show: true,
  containerWidth: 800 // Default width, can be adjusted based on actual container size
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

const toolbarRef = ref<HTMLElement>()

// Helper function to detech is toolbar has wrapped content and organize elements by rows
const WrappedRows = () => {
  if (!toolbarRef.value) return []
  const container = toolbarRef.value
  const children = container.querySelectorAll('.toolbar-section > *')
  if (children.length === 0) return []
  
  // Create an array to hold rows
  const rows: HTMLElement[][] = []
  let currentRow: HTMLElement[] = []

  // Iterate through each child element
  children.forEach((child) => {
    const element = child as HTMLElement
    // If the element is on a new row, push the current row and start a new one
    if (currentRow.length > 0 && element.offsetTop > currentRow[0].offsetTop) {
      rows.push(currentRow)
      currentRow = []
    }
    currentRow.push(element)
  })


  // Push the last row if it has elements
  if (currentRow.length > 0) {
    rows.push(currentRow)
  }

  return rows
}

// Helper function to detect if the toolbar has wrapped content
const hasWrappedContent = () => {
  if (!toolbarRef.value) return false
  const container = toolbarRef.value
  const children = container.querySelectorAll('.toolbar-section > *')
  if (children.length === 0) return false
  const rowStart = children[0] as HTMLElement
  // Check if the first child's top offset is greater than 0, indicating a new row
  return Array.from(children).some((child) => (child as HTMLElement).offsetTop > 0)
}

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



const handleToolUpdate = (data: any) => {
  emit('tool-update', props.activeTool || 'select', data)
}

const setPosition = (preset: string, closeCallback: () => void) => {
  emit('position-preset', preset)
  // Don't automatically close the dropdown when clicking presets
  // closeCallback()
}

watch(()=>props.containerWidth, async (newWidth) => {
  if (toolbarRef.value) {
    // Force re-evaluation of wrapped content on width change
    await nextTick()
    const rows = WrappedRows()
    if (hasWrappedContent()) {
      toolbarRef.value.classList.add('has-wrapped-content')
      rows.forEach((row, index) => {
        row.forEach((item, itemIndex) => {
          if (itemIndex === 0) {
            item.classList.add('row-start')
          } else {
            item.classList.remove('row-start')
          }
        })
      })
    } else {
      toolbarRef.value.classList.remove('has-wrapped-content')
      rows.forEach((row) => {
        row.forEach((item) => {
          item.classList.remove('row-start')
        })
      })
    }
  }
})

onMounted(() => {
  // Initial check for wrapped content
  if (toolbarRef.value) {
    if (hasWrappedContent()) {
      toolbarRef.value.classList.add('has-wrapped-content')
    } else {
      toolbarRef.value.classList.remove('has-wrapped-content')
    }
  }
})
</script>

<style scoped>
/* Enhanced animations for the floating toolbar */
.floating-toolbar-enter-active {
  transition: all 0.3s cubic-bezier(0.25, 0.46, 0.45, 0.94);
}

.floating-toolbar-leave-active {
  transition: all 0.25s cubic-bezier(0.55, 0.055, 0.675, 0.19);
}

.floating-toolbar-enter-from {
  opacity: 0;
  transform: scale(0.85) translateY(-20px);
}

.floating-toolbar-leave-to {
  opacity: 0;
  transform: scale(0.9) translateY(-10px);
}

.floating-toolbar-enter-to,
.floating-toolbar-leave-from {
  opacity: 1;
  transform: scale(1) translateY(0);
}

/* Toolbar container with row separation */
.toolbar-container {
  /* Use flexbox with row wrapping and visual row separation */
  display: flex;
  flex-wrap: wrap;
  align-content: flex-start;
  align-items: flex-start;
  position: relative;
  transition: all 0.3s ease;
}

/* Toolbar section styling for wrapped appearance */
.toolbar-section {
  display: contents;
}

/* Individual toolbar items get the visual styling */
.toolbar-section > * {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.5rem 0.75rem;
  margin: 0.25rem;
  border-radius: 0.75rem;
  background: linear-gradient(135deg, rgba(249, 250, 251, 0.95), rgba(243, 244, 246, 0.9));
  border: 1px solid rgba(229, 231, 235, 0.8);
  backdrop-filter: blur(8px);
  box-shadow: 
    0 2px 4px rgba(0, 0, 0, 0.06),
    0 1px 2px rgba(0, 0, 0, 0.04);
  transition: all 0.2s cubic-bezier(0.25, 0.46, 0.45, 0.94);
  position: relative;
  flex: 0 0 auto;
  min-width: fit-content;
}

/* Row separation using dynamic wrapping detection */
.toolbar-container.has-wrapped-content {
  /* Add padding and background when wrapped */
  padding: 0.75rem;
  background: linear-gradient(135deg, 
    rgba(249, 250, 251, 0.03), 
    rgba(243, 244, 246, 0.08)
  );
  border-radius: 1rem;
  transition: all 0.3s ease;
}

.dark .toolbar-container.has-wrapped-content {
  background: linear-gradient(135deg, 
    rgba(31, 41, 55, 0.03), 
    rgba(55, 65, 81, 0.08)
  );
}

/* Detect rows using JavaScript and apply classes dynamically */
.toolbar-container.has-wrapped-content .toolbar-section {
  /* All sections get slight spacing adjustment when wrapped */
  margin: 0.375rem 0.25rem;
}

/* Add separators between visual rows */
.toolbar-container.has-wrapped-content .toolbar-section .row-start {
  /* Class applied via JavaScript to items starting new rows */
  margin-top: 1.5rem;
  position: relative;
  background: red !important;
}

.toolbar-container.has-wrapped-content .toolbar-section .row-start::before {
  content: '';
  position: absolute;
  top: -0.875rem;
  left: -4rem;
  right: -4rem;
  height: 2px;
  background: linear-gradient(90deg, 
    transparent 0%, 
    rgba(229, 231, 235, 0.1) 3%, 
    rgba(229, 231, 235, 0.3) 10%, 
    rgba(229, 231, 235, 0.5) 20%, 
    rgba(229, 231, 235, 0.7) 35%, 
    rgba(229, 231, 235, 0.8) 50%, 
    rgba(229, 231, 235, 0.7) 65%, 
    rgba(229, 231, 235, 0.5) 80%, 
    rgba(229, 231, 235, 0.3) 90%, 
    rgba(229, 231, 235, 0.1) 97%, 
    transparent 100%
  );
  pointer-events: none;
  z-index: 1;
  border-radius: 1px;
  opacity: 0.9;
  animation: fadeInSeparator 0.3s ease-out;
}

.dark .toolbar-container.has-wrapped-content .toolbar-section .row-start::before {
  background: linear-gradient(90deg, 
    transparent 0%, 
    rgba(75, 85, 99, 0.1) 3%, 
    rgba(75, 85, 99, 0.3) 10%, 
    rgba(75, 85, 99, 0.5) 20%, 
    rgba(75, 85, 99, 0.7) 35%, 
    rgba(75, 85, 99, 0.8) 50%, 
    rgba(75, 85, 99, 0.7) 65%, 
    rgba(75, 85, 99, 0.5) 80%, 
    rgba(75, 85, 99, 0.3) 90%, 
    rgba(75, 85, 99, 0.1) 97%, 
    transparent 100%
  );
}

@keyframes fadeInSeparator {
  from {
    opacity: 0;
    transform: scaleX(0.3);
  }
  to {
    opacity: 0.9;
    transform: scaleX(1);
  }
}

/* Alternative: Use flex-basis for more predictable wrapping behavior */
.toolbar-container .toolbar-section {
  flex: 0 0 auto;
  min-width: fit-content;
}

/* Add subtle inner glow */
.toolbar-section::before {
  content: '';
  position: absolute;
  inset: 0;
  border-radius: inherit;
  background: linear-gradient(135deg, rgba(255, 255, 255, 0.3), rgba(255, 255, 255, 0.1));
  mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
  mask-composite: xor;
  padding: 1px;
  pointer-events: none;
}

.dark .toolbar-section {
  background: linear-gradient(135deg, rgba(31, 41, 55, 0.95), rgba(55, 65, 81, 0.9));
  border-color: rgba(75, 85, 99, 0.8);
  box-shadow: 
    0 2px 4px rgba(0, 0, 0, 0.15),
    0 1px 2px rgba(0, 0, 0, 0.1);
}

.dark .toolbar-section::before {
  background: linear-gradient(135deg, rgba(255, 255, 255, 0.1), rgba(255, 255, 255, 0.05));
}

.toolbar-section:hover {
  background: linear-gradient(135deg, rgba(243, 244, 246, 0.98), rgba(229, 231, 235, 0.95));
  border-color: rgba(209, 213, 219, 0.9);
  transform: translateY(-1px);
  box-shadow: 
    0 4px 8px rgba(0, 0, 0, 0.1),
    0 2px 4px rgba(0, 0, 0, 0.06);
}

.dark .toolbar-section:hover {
  background: linear-gradient(135deg, rgba(55, 65, 81, 0.98), rgba(75, 85, 99, 0.95));
  border-color: rgba(107, 114, 128, 0.9);
}

/* Reset any default styles for child components */
.toolbar-section > * {
  margin: 0;
  padding: 0;
  border: none;
  background: none;
  border-radius: 0;
  box-shadow: none;
}

/* Special styling for action toolbar */
.toolbar-actions {
  gap: 0.75rem;
  padding: 0.625rem 1rem;
  background: linear-gradient(135deg, rgba(239, 246, 255, 0.95), rgba(229, 237, 255, 0.9));
  border-color: rgba(191, 219, 254, 0.8);
}

.dark .toolbar-actions {
  background: linear-gradient(135deg, rgba(30, 58, 138, 0.15), rgba(29, 78, 216, 0.1));
  border-color: rgba(59, 130, 246, 0.3);
}

.toolbar-actions:hover {
  background: linear-gradient(135deg, rgba(229, 237, 255, 0.98), rgba(219, 234, 254, 0.95));
  border-color: rgba(147, 197, 253, 0.9);
}

.dark .toolbar-actions:hover {
  background: linear-gradient(135deg, rgba(30, 58, 138, 0.2), rgba(29, 78, 216, 0.15));
  border-color: rgba(59, 130, 246, 0.4);
}

.toolbar-actions > div {
  display: flex;
  align-items: center;
  gap: 0.25rem;
}

/* Enhanced backdrop blur and shadow */
.backdrop-blur-md {
  backdrop-filter: blur(12px);
  -webkit-backdrop-filter: blur(12px);
}

/* Subtle border glow effect */
.border-gray-200\/50 {
  border-color: rgba(229, 231, 235, 0.5);
  box-shadow: 0 0 0 1px rgba(255, 255, 255, 0.1) inset;
}

.dark .border-gray-700\/50 {
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

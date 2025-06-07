<template>
  <div class="bg-white dark:bg-gray-900 border-l border-gray-200 dark:border-gray-700 w-80 h-full overflow-y-auto">
    <!-- Sidebar Header -->
    <div class="p-4 border-b border-gray-200 dark:border-gray-700">
      <div class="flex items-center justify-between">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
          Properties
        </h2>
        <ModernButton
          variant="ghost"
          size="sm"
          @click="$emit('close')"
          class="lg:hidden"
        >
          <XMarkIcon class="w-5 h-5" />
        </ModernButton>
      </div>
    </div>

    <!-- Dynamic Content Based on Selection -->
    <div class="p-4 space-y-6">
      <!-- No Selection State -->
      <div v-if="!selectedLayer && !showDesignProperties" class="text-center py-12">
        <div class="w-16 h-16 mx-auto mb-4 bg-gray-100 dark:bg-gray-800 rounded-full flex items-center justify-center">
          <CursorArrowRaysIcon class="w-8 h-8 text-gray-400" />
        </div>
        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">
          No Selection
        </h3>
        <p class="text-sm text-gray-500 dark:text-gray-400">
          Select an element to edit its properties
        </p>
      </div>

      <!-- Design Properties -->
      <div v-else-if="showDesignProperties" class="space-y-6">
        <div>
          <h3 class="text-sm font-medium text-gray-900 dark:text-white mb-3">
            Design Settings
          </h3>
          
          <!-- Design Name -->
          <div class="mb-4">
            <PropertyInput
              label="Design Name"
              :value="design?.name || ''"
              @update:value="updateDesignName"
              placeholder="Enter design name"
            />
          </div>

          <!-- Canvas Size -->
          <div class="mb-4">
            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-2">
              Canvas Size
            </label>
            <div class="grid grid-cols-2 gap-2">
              <PropertyInput
                label="Width"
                type="number"
                :value="design?.dimensions?.width || 800"
                @update:value="updateCanvasWidth"
                suffix="px"
              />
              <PropertyInput
                label="Height"
                type="number"
                :value="design?.dimensions?.height || 800"
                @update:value="updateCanvasHeight"
                suffix="px"
              />
            </div>
          </div>

          <!-- Background -->
          <div class="mb-4">
            <PropertyColorPicker
              label="Background Color"
              :value="design?.designData.canvas.backgroundColor || '#ffffff'"
              @update:value="updateBackgroundColor"
              show-gradients
            />
          </div>
        </div>
      </div>

      <!-- Layer Properties -->
      <div v-else-if="selectedLayer" class="space-y-6">
        <!-- Layer Header -->
        <div class="flex items-center justify-between">
          <div class="flex items-center space-x-2">
            <div class="w-8 h-8 bg-blue-100 dark:bg-blue-900 rounded flex items-center justify-center">
              <component :is="getLayerIcon(selectedLayer.type)" class="w-4 h-4 text-blue-600 dark:text-blue-400" />
            </div>
            <div>
              <h3 class="text-sm font-medium text-gray-900 dark:text-white">
                {{ getLayerTypeName(selectedLayer.type) }}
              </h3>
              <p class="text-xs text-gray-500 dark:text-gray-400">
                {{ selectedLayer.name || 'Unnamed Layer' }}
              </p>
            </div>
          </div>
          
          <ModernButton
            variant="ghost"
            size="sm"
            @click="deleteLayer"
            class="text-red-600 hover:text-red-700"
          >
            <TrashIcon class="w-4 h-4" />
          </ModernButton>
        </div>

        <!-- Layer Name -->
        <div>
          <PropertyInput
            label="Layer Name"
            :value="selectedLayer.name || ''"
            @update:value="updateLayerName"
            placeholder="Enter layer name"
          />
        </div>

        <!-- Position & Size -->
        <div>
          <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-3">
            Position & Size
          </h4>
          
          <div class="grid grid-cols-2 gap-3 mb-3">
            <PropertyInput
              label="X"
              type="number"
              :value="selectedLayer.x || 0"
              @update:value="updateLayerX"
              suffix="px"
            />
            <PropertyInput
              label="Y"
              type="number"
              :value="selectedLayer.y || 0"
              @update:value="updateLayerY"
              suffix="px"
            />
          </div>
          
          <div class="grid grid-cols-2 gap-3 mb-3">
            <PropertyInput
              label="Width"
              type="number"
              :value="selectedLayer.width || 0"
              @update:value="updateLayerWidth"
              suffix="px"
            />
            <PropertyInput
              label="Height"
              type="number"
              :value="selectedLayer.height || 0"
              @update:value="updateLayerHeight"
              suffix="px"
            />
          </div>

          <PropertyInput
            label="Rotation"
            type="number"
            :value="selectedLayer.rotation || 0"
            @update:value="updateLayerRotation"
            suffix="°"
            :min="-180"
            :max="180"
          />
        </div>

        <!-- Opacity -->
        <div>
          <PropertyInput
            label="Opacity"
            type="range"
            :value="(selectedLayer.opacity || 1) * 100"
            @update:value="updateLayerOpacity"
            suffix="%"
            :min="0"
            :max="100"
          />
        </div>

        <!-- Text Properties - Simplified to avoid duplication with toolbar -->
        <div v-if="selectedLayer.type === 'text'" class="space-y-4">
          <h4 class="text-sm font-medium text-gray-900 dark:text-white">
            Text Content
          </h4>
          
          <PropertyInput
            label="Text Content"
            :value="selectedLayer.properties?.text || ''"
            @update:value="updateTextContent"
            placeholder="Enter text"
            multiline
            :rows="3"
          />

          <!-- Auto-Resize Configuration -->
          <div class="space-y-3">
            <div class="flex items-center justify-between">
              <h5 class="text-xs font-medium text-gray-700 dark:text-gray-300">
                Auto-Resize
              </h5>
              <PropertyToggle
                :active="selectedLayer.properties?.autoResize?.enabled || false"
                @update="updateAutoResizeEnabled"
                tooltip="Enable auto-resize to automatically adjust text layer dimensions"
              />
            </div>
            
            <div v-if="selectedLayer.properties?.autoResize?.enabled" class="space-y-3 pl-4 border-l-2 border-gray-200 dark:border-gray-700">
              <!-- Auto-Resize Mode -->
              <div>
                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-2">
                  Resize Mode
                </label>
                <PropertyDropdown
                  :value="selectedLayer.properties?.autoResize?.mode || 'none'"
                  :options="autoResizeModeOptions"
                  @update="updateAutoResizeMode"
                />
              </div>
              
              <!-- Constraints -->
              <div v-if="selectedLayer.properties?.autoResize?.mode !== 'none'" class="space-y-3">
                <h6 class="text-xs font-medium text-gray-700 dark:text-gray-300">
                  Constraints
                </h6>
                
                <!-- Width Constraints -->
                <div v-if="selectedLayer.properties?.autoResize?.mode === 'width' || selectedLayer.properties?.autoResize?.mode === 'both'" class="grid grid-cols-2 gap-2">
                  <PropertyInput
                    label="Min Width"
                    type="number"
                    :value="selectedLayer.properties?.autoResize?.minWidth || 50"
                    @update:value="updateAutoResizeMinWidth"
                    suffix="px"
                    :min="10"
                    :max="2000"
                  />
                  <PropertyInput
                    label="Max Width"
                    type="number"
                    :value="selectedLayer.properties?.autoResize?.maxWidth || 800"
                    @update:value="updateAutoResizeMaxWidth"
                    suffix="px"
                    :min="50"
                    :max="2000"
                  />
                </div>
                
                <!-- Height Constraints -->
                <div v-if="selectedLayer.properties?.autoResize?.mode === 'height' || selectedLayer.properties?.autoResize?.mode === 'both'" class="grid grid-cols-2 gap-2">
                  <PropertyInput
                    label="Min Height"
                    type="number"
                    :value="selectedLayer.properties?.autoResize?.minHeight || 20"
                    @update:value="updateAutoResizeMinHeight"
                    suffix="px"
                    :min="10"
                    :max="1000"
                  />
                  <PropertyInput
                    label="Max Height"
                    type="number"
                    :value="selectedLayer.properties?.autoResize?.maxHeight || 400"
                    @update:value="updateAutoResizeMaxHeight"
                    suffix="px"
                    :min="20"
                    :max="1000"
                  />
                </div>
                
                <!-- Padding -->
                <div class="space-y-2">
                  <label class="text-xs font-medium text-gray-700 dark:text-gray-300">
                    Padding
                  </label>
                  <div class="grid grid-cols-2 gap-2">
                    <PropertyInput
                      label="Horizontal"
                      type="number"
                      :value="(selectedLayer.properties?.autoResize?.padding?.left || 4) + (selectedLayer.properties?.autoResize?.padding?.right || 4)"
                      @update:value="updateAutoResizePaddingHorizontal"
                      suffix="px"
                      :min="0"
                      :max="50"
                    />
                    <PropertyInput
                      label="Vertical"
                      type="number"
                      :value="(selectedLayer.properties?.autoResize?.padding?.top || 4) + (selectedLayer.properties?.autoResize?.padding?.bottom || 4)"
                      @update:value="updateAutoResizePaddingVertical"
                      suffix="px"
                      :min="0"
                      :max="50"
                    />
                  </div>
                </div>
              </div>
            </div>
          </div>
          
          <!-- Note about text formatting -->
          <div class="p-3 bg-blue-50 dark:bg-blue-900 rounded-md">
            <p class="text-xs text-blue-700 dark:text-blue-300">
              Text formatting options are available in the toolbar when this text element is selected.
            </p>
          </div>
        </div>

        <!-- Shape Properties -->
        <div v-else-if="selectedLayer.type === 'shape'" class="space-y-4">
          <h4 class="text-sm font-medium text-gray-900 dark:text-white">
            Shape Properties
          </h4>
          
          <PropertyColorPicker
            label="Fill Color"
            :value="selectedLayer.properties?.fill || '#000000'"
            @update:value="updateShapeFill"
            show-gradients
          />

          <PropertyColorPicker
            label="Stroke Color"
            :value="selectedLayer.properties?.stroke || '#000000'"
            @update:value="updateShapeStroke"
          />

          <PropertyInput
            label="Stroke Width"
            type="number"
            :value="selectedLayer.properties?.strokeWidth || 0"
            @update:value="updateStrokeWidth"
            suffix="px"
            :min="0"
            :max="20"
          />

          <PropertyInput
            label="Corner Radius"
            type="number"
            :value="selectedLayer.properties?.cornerRadius || 0"
            @update:value="updateCornerRadius"
            suffix="px"
            :min="0"
            :max="100"
          />
        </div>

        <!-- Image Properties -->
        <div v-else-if="selectedLayer.type === 'image'" class="space-y-4">
          <h4 class="text-sm font-medium text-gray-900 dark:text-white">
            Image Properties
          </h4>

          <!-- Image Filters -->
          <div class="space-y-3">
            <h5 class="text-xs font-medium text-gray-700 dark:text-gray-300">Filters</h5>
            
            <PropertyInput
              label="Brightness"
              type="range"
              :value="selectedLayer.properties?.brightness || 100"
              @update:value="updateBrightness"
              suffix="%"
              :min="0"
              :max="200"
            />
            
            <PropertyInput
              label="Contrast"
              type="range"
              :value="selectedLayer.properties?.contrast || 100"
              @update:value="updateContrast"
              suffix="%"
              :min="0"
              :max="200"
            />
            
            <PropertyInput
              label="Saturation"
              type="range"
              :value="selectedLayer.properties?.saturation || 100"
              @update:value="updateSaturation"
              suffix="%"
              :min="0"
              :max="200"
            />
          </div>
        </div>

        <!-- Effects -->
        <div class="space-y-4">
          <h4 class="text-sm font-medium text-gray-900 dark:text-white">
            Effects
          </h4>
          
          <!-- Shadow -->
          <div class="space-y-3">
            <div class="flex items-center justify-between">
              <label class="text-xs font-medium text-gray-700 dark:text-gray-300">
                Drop Shadow
              </label>
              <PropertyToggle
                :active="selectedLayer.properties?.shadow?.enabled || false"
                @update="updateShadowEnabled"
              />
            </div>
            
            <div v-if="selectedLayer.properties?.shadow?.enabled" class="space-y-3 pl-4 border-l-2 border-gray-200 dark:border-gray-700">
              <PropertyColorPicker
                label="Shadow Color"
                :value="selectedLayer.properties?.shadow?.color || '#000000'"
                @update:value="updateShadowColor"
              />
              
              <div class="grid grid-cols-2 gap-2">
                <PropertyInput
                  label="Offset X"
                  type="number"
                  :value="selectedLayer.properties?.shadow?.offsetX || 0"
                  @update:value="updateShadowOffsetX"
                  suffix="px"
                />
                <PropertyInput
                  label="Offset Y"
                  type="number"
                  :value="selectedLayer.properties?.shadow?.offsetY || 0"
                  @update:value="updateShadowOffsetY"
                  suffix="px"
                />
              </div>
              
              <PropertyInput
                label="Blur"
                type="number"
                :value="selectedLayer.properties?.shadow?.blur || 0"
                @update:value="updateShadowBlur"
                suffix="px"
                :min="0"
                :max="50"
              />
            </div>
          </div>
        </div>

        <!-- Layer Actions -->
        <div class="pt-4 border-t border-gray-200 dark:border-gray-700">
          <div class="grid grid-cols-2 gap-2">
            <ModernButton
              variant="outline"
              size="sm"
              @click="duplicateLayer"
              class="justify-center"
            >
              <DocumentDuplicateIcon class="w-4 h-4 mr-1" />
              Duplicate
            </ModernButton>
            <ModernButton
              variant="outline"
              size="sm"
              @click="lockLayer"
              class="justify-center"
              :class="{ 'bg-amber-50 text-amber-700 border-amber-200': selectedLayer.locked }"
            >
              <component :is="selectedLayer.locked ? LockClosedIcon : LockOpenIcon" class="w-4 h-4 mr-1" />
              {{ selectedLayer.locked ? 'Unlock' : 'Lock' }}
            </ModernButton>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, inject } from 'vue';
import { useDesignStore } from '@/stores/design';
import ModernButton from '@/components/common/ModernButton.vue';
import PropertyInput from '@/components/editor/Properties/PropertyInput.vue';
import PropertyDropdown from '@/components/editor/Properties/PropertyDropdown.vue';
import PropertyToggle from '@/components/editor/Properties/PropertyToggle.vue';
import PropertyColorPicker from '@/components/editor/Properties/PropertyColorPicker.vue';
import type { EditorSDK } from '@/editor/sdk/EditorSDK';
import {
  XMarkIcon,
  CursorArrowRaysIcon,
  TrashIcon,
  DocumentDuplicateIcon,
  LockClosedIcon,
  LockOpenIcon,
  PhotoIcon,
  DocumentTextIcon,
  Square3Stack3DIcon
} from '@heroicons/vue/24/outline';
import type { Layer } from '@/types';

interface Props {
  showDesignProperties?: boolean;
}

defineProps<Props>();

const emit = defineEmits<{
  close: [];
}>();

const designStore = useDesignStore();
const editorSDK = inject<EditorSDK | null>('editorSDK', null);

const selectedLayer = computed(() => designStore.selectedLayers[0]);
const design = computed(() => designStore.currentDesign);

// Font options
const fontFamilyOptions = [
  { label: 'Arial', value: 'Arial' },
  { label: 'Helvetica', value: 'Helvetica' },
  { label: 'Times New Roman', value: 'Times New Roman' },
  { label: 'Georgia', value: 'Georgia' },
  { label: 'Verdana', value: 'Verdana' },
  { label: 'Inter', value: 'Inter' },
  { label: 'Roboto', value: 'Roboto' },
  { label: 'Open Sans', value: 'Open Sans' },
];

const textAlignOptions = [
  { label: 'Left', value: 'left' },
  { label: 'Center', value: 'center' },
  { label: 'Right', value: 'right' },
  { label: 'Justify', value: 'justify' },
];

// Auto-resize mode options
const autoResizeModeOptions = [
  { label: 'None', value: 'none' },
  { label: 'Width Only', value: 'width' },
  { label: 'Height Only', value: 'height' },
  { label: 'Both Dimensions', value: 'both' },
];

// Helper functions
const getLayerIcon = (type: string) => {
  switch (type) {
    case 'text': return DocumentTextIcon;
    case 'image': return PhotoIcon;
    case 'shape': return Square3Stack3DIcon;
    default: return Square3Stack3DIcon;
  }
};

const getLayerTypeName = (type: string) => {
  switch (type) {
    case 'text': return 'Text Layer';
    case 'image': return 'Image Layer';
    case 'shape': return 'Shape Layer';
    default: return 'Layer';
  }
};

// Design update methods
const updateDesignName = (value: string) => {
  if (designStore.currentDesign) {
    designStore.updateDesignName(value);
  }
};

const updateCanvasWidth = (value: number) => {
  if (designStore.currentDesign) {
    designStore.updateCanvasSize(value, designStore.currentDesign.height);
  }
};

const updateCanvasHeight = (value: number) => {
  if (designStore.currentDesign) {
    designStore.updateCanvasSize(designStore.currentDesign.width, value);
  }
};

const updateBackgroundColor = (value: string) => {
  if (designStore.currentDesign) {
    designStore.currentDesign.designData.canvas.backgroundColor = value;
    designStore.currentDesign.updatedAt = new Date().toISOString();
  }
};

// Layer update methods
const updateLayerName = (value: string) => {
  if (selectedLayer.value) {
    designStore.updateLayerProperty(selectedLayer.value.id, 'name', value);
  }
};

const updateLayerX = (value: number) => {
  if (selectedLayer.value) {
    designStore.updateLayerProperty(selectedLayer.value.id, 'x', value);
  }
};

const updateLayerY = (value: number) => {
  if (selectedLayer.value) {
    designStore.updateLayerProperty(selectedLayer.value.id, 'y', value);
  }
};

const updateLayerWidth = (value: number) => {
  if (selectedLayer.value) {
    designStore.updateLayerProperty(selectedLayer.value.id, 'width', value);
  }
};

const updateLayerHeight = (value: number) => {
  if (selectedLayer.value) {
    designStore.updateLayerProperty(selectedLayer.value.id, 'height', value);
  }
};

const updateLayerRotation = (value: number) => {
  if (selectedLayer.value) {
    designStore.updateLayerProperty(selectedLayer.value.id, 'rotation', value);
  }
};

const updateLayerOpacity = (value: number) => {
  if (selectedLayer.value) {
    designStore.updateLayerProperty(selectedLayer.value.id, 'opacity', value / 100);
  }
};

// Text properties
const updateTextContent = (value: string) => {
  if (selectedLayer.value) {
    designStore.updateLayerProperty(selectedLayer.value.id, 'properties.text', value);
  }
};

// Auto-resize properties
const updateAutoResizeEnabled = (value: boolean) => {
  if (selectedLayer.value) {
    designStore.updateLayerProperty(selectedLayer.value.id, 'properties.autoResize.enabled', value);
    
    // Set default values when enabling auto-resize
    if (value && !selectedLayer.value.properties?.autoResize) {
      const defaultConfig = {
        enabled: true,
        mode: 'width',
        minWidth: 50,
        maxWidth: 800,
        minHeight: 20,
        maxHeight: 400,
        padding: { top: 4, right: 4, bottom: 4, left: 4 }
      };
      
      Object.entries(defaultConfig).forEach(([key, val]) => {
        designStore.updateLayerProperty(selectedLayer.value.id, `properties.autoResize.${key}`, val);
      });
    }
  }
};

const updateAutoResizeMode = (value: string | number) => {
  if (selectedLayer.value) {
    designStore.updateLayerProperty(selectedLayer.value.id, 'properties.autoResize.mode', String(value));
  }
};

const updateAutoResizeMinWidth = (value: number) => {
  if (selectedLayer.value) {
    designStore.updateLayerProperty(selectedLayer.value.id, 'properties.autoResize.minWidth', value);
  }
};

const updateAutoResizeMaxWidth = (value: number) => {
  if (selectedLayer.value) {
    designStore.updateLayerProperty(selectedLayer.value.id, 'properties.autoResize.maxWidth', value);
  }
};

const updateAutoResizeMinHeight = (value: number) => {
  if (selectedLayer.value) {
    designStore.updateLayerProperty(selectedLayer.value.id, 'properties.autoResize.minHeight', value);
  }
};

const updateAutoResizeMaxHeight = (value: number) => {
  if (selectedLayer.value) {
    designStore.updateLayerProperty(selectedLayer.value.id, 'properties.autoResize.maxHeight', value);
  }
};

const updateAutoResizePaddingHorizontal = (value: number) => {
  if (selectedLayer.value) {
    const horizontalPadding = value / 2;
    designStore.updateLayerProperty(selectedLayer.value.id, 'properties.autoResize.padding.left', horizontalPadding);
    designStore.updateLayerProperty(selectedLayer.value.id, 'properties.autoResize.padding.right', horizontalPadding);
  }
};

const updateAutoResizePaddingVertical = (value: number) => {
  if (selectedLayer.value) {
    const verticalPadding = value / 2;
    designStore.updateLayerProperty(selectedLayer.value.id, 'properties.autoResize.padding.top', verticalPadding);
    designStore.updateLayerProperty(selectedLayer.value.id, 'properties.autoResize.padding.bottom', verticalPadding);
  }
};

// Shape properties
const updateShapeFill = (value: string) => {
  if (selectedLayer.value) {
    designStore.updateLayerProperty(selectedLayer.value.id, 'properties.fill', value);
  }
};

const updateShapeStroke = (value: string) => {
  if (selectedLayer.value) {
    designStore.updateLayerProperty(selectedLayer.value.id, 'properties.stroke', value);
  }
};

const updateStrokeWidth = (value: number) => {
  if (selectedLayer.value) {
    designStore.updateLayerProperty(selectedLayer.value.id, 'properties.strokeWidth', value);
  }
};

const updateCornerRadius = (value: number) => {
  if (selectedLayer.value) {
    designStore.updateLayerProperty(selectedLayer.value.id, 'properties.cornerRadius', value);
  }
};

// Image properties
const updateBrightness = (value: number) => {
  if (selectedLayer.value) {
    designStore.updateLayerProperty(selectedLayer.value.id, 'properties.brightness', value);
  }
};

const updateContrast = (value: number) => {
  if (selectedLayer.value) {
    designStore.updateLayerProperty(selectedLayer.value.id, 'properties.contrast', value);
  }
};

const updateSaturation = (value: number) => {
  if (selectedLayer.value) {
    designStore.updateLayerProperty(selectedLayer.value.id, 'properties.saturation', value);
  }
};

// Effects
const updateShadowEnabled = (value: boolean) => {
  if (selectedLayer.value) {
    designStore.updateLayerProperty(selectedLayer.value.id, 'properties.shadow.enabled', value);
  }
};

const updateShadowColor = (value: string) => {
  if (selectedLayer.value) {
    designStore.updateLayerProperty(selectedLayer.value.id, 'properties.shadow.color', value);
  }
};

const updateShadowOffsetX = (value: number) => {
  if (selectedLayer.value) {
    designStore.updateLayerProperty(selectedLayer.value.id, 'properties.shadow.offsetX', value);
  }
};

const updateShadowOffsetY = (value: number) => {
  if (selectedLayer.value) {
    designStore.updateLayerProperty(selectedLayer.value.id, 'properties.shadow.offsetY', value);
  }
};

const updateShadowBlur = (value: number) => {
  if (selectedLayer.value) {
    designStore.updateLayerProperty(selectedLayer.value.id, 'properties.shadow.blur', value);
  }
};

// Layer actions
const deleteLayer = () => {
  if (selectedLayer.value && editorSDK) {
    editorSDK.layers.deleteLayer(selectedLayer.value.id);
  }
};

const duplicateLayer = () => {
  if (selectedLayer.value) {
    designStore.duplicateLayer(selectedLayer.value.id);
  }
};

const lockLayer = () => {
  if (selectedLayer.value) {
    designStore.updateLayerProperty(selectedLayer.value.id, 'locked', !selectedLayer.value.locked);
  }
};
</script>

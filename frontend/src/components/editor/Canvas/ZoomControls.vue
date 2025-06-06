<template>
  <div class="absolute bottom-4 right-4 z-10">
    <div class="bg-white dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-700 shadow-lg p-2">
      <!-- Zoom Controls -->
      <div class="flex items-center space-x-2">
        <!-- Zoom Out -->
        <ModernButton
          variant="ghost"
          size="sm"
          @click="zoomOut"
          :disabled="zoom <= minZoom"
          class="p-1.5"
        >
          <MinusIcon class="w-4 h-4" />
        </ModernButton>

        <!-- Zoom Display -->
        <div class="flex items-center space-x-1 px-2">
          <span class="text-sm font-medium text-gray-700 dark:text-gray-300 min-w-[3rem] text-center">
            {{ Math.round(zoom * 100) }}%
          </span>
        </div>

        <!-- Zoom In -->
        <ModernButton
          variant="ghost"
          size="sm"
          @click="zoomIn"
          :disabled="zoom >= maxZoom"
          class="p-1.5"
        >
          <PlusIcon class="w-4 h-4" />
        </ModernButton>

        <!-- Separator -->
        <div class="w-px h-6 bg-gray-200 dark:bg-gray-700"></div>

        <!-- Fit to Screen -->
        <ModernButton
          variant="ghost"
          size="sm"
          @click="fitToScreen"
          class="p-1.5"
          title="Fit to screen"
        >
          <ArrowsPointingOutIcon class="w-4 h-4" />
        </ModernButton>

        <!-- Actual Size -->
        <ModernButton
          variant="ghost"
          size="sm"
          @click="actualSize"
          class="p-1.5"
          title="Actual size (100%)"
        >
          <Square2StackIcon class="w-4 h-4" />
        </ModernButton>
      </div>

      <!-- Zoom Slider (Optional - shown on hover/focus) -->
      <div 
        v-if="showSlider" 
        class="mt-2 pt-2 border-t border-gray-200 dark:border-gray-700"
      >
        <input
          type="range"
          :min="minZoom * 100"
          :max="maxZoom * 100"
          :value="zoom * 100"
          @input="onSliderChange"
          class="w-32 h-1 bg-gray-200 rounded-lg appearance-none cursor-pointer dark:bg-gray-700 
                 slider:bg-blue-600 slider:rounded-lg slider:cursor-pointer"
        />
      </div>
    </div>

    <!-- Quick Zoom Levels -->
    <div 
      v-if="showQuickZoom" 
      class="mt-2 bg-white dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-700 shadow-lg p-2"
    >
      <div class="flex flex-col space-y-1">
        <ModernButton
          v-for="level in quickZoomLevels"
          :key="level.value"
          variant="ghost"
          size="sm"
          @click="setZoom(level.value)"
          :class="{ 'bg-blue-50 text-blue-700': Math.abs(zoom - level.value) < 0.01 }"
          class="justify-start text-xs"
        >
          {{ level.label }}
        </ModernButton>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue';
import ModernButton from '@/components/common/ModernButton.vue';
import {
  PlusIcon,
  MinusIcon,
  ArrowsPointingOutIcon,
  Square2StackIcon
} from '@heroicons/vue/24/outline';

interface Props {
  zoom: number;
  minZoom?: number;
  maxZoom?: number;
  canvasWidth?: number;
  canvasHeight?: number;
  containerWidth?: number;
  containerHeight?: number;
}

const props = withDefaults(defineProps<Props>(), {
  minZoom: 0.1,
  maxZoom: 5,
  canvasWidth: 800,
  canvasHeight: 600,
  containerWidth: 1000,
  containerHeight: 700
});

const emit = defineEmits<{
  'update:zoom': [zoom: number];
  'pan-to-center': [];
}>();

const showSlider = ref(false);
const showQuickZoom = ref(false);

const quickZoomLevels = [
  { label: '25%', value: 0.25 },
  { label: '50%', value: 0.5 },
  { label: '75%', value: 0.75 },
  { label: '100%', value: 1 },
  { label: '125%', value: 1.25 },
  { label: '150%', value: 1.5 },
  { label: '200%', value: 2 },
];

// Zoom functions
const zoomIn = () => {
  const newZoom = Math.min(props.zoom * 1.2, props.maxZoom);
  emit('update:zoom', newZoom);
};

const zoomOut = () => {
  const newZoom = Math.max(props.zoom / 1.2, props.minZoom);
  emit('update:zoom', newZoom);
};

const actualSize = () => {
  emit('update:zoom', 1);
  emit('pan-to-center');
};

const fitToScreen = () => {
  const scaleX = (props.containerWidth - 100) / props.canvasWidth; // 100px padding
  const scaleY = (props.containerHeight - 100) / props.canvasHeight; // 100px padding
  const scale = Math.min(scaleX, scaleY, 1); // Don't zoom in beyond 100%
  
  emit('update:zoom', Math.max(scale, props.minZoom));
  emit('pan-to-center');
};

const setZoom = (zoom: number) => {
  emit('update:zoom', zoom);
  emit('pan-to-center');
};

const onSliderChange = (event: Event) => {
  const target = event.target as HTMLInputElement;
  const zoom = parseInt(target.value) / 100;
  emit('update:zoom', zoom);
};

// Keyboard shortcuts
const handleKeydown = (event: KeyboardEvent) => {
  // Check if user is not typing in an input field
  if (event.target instanceof HTMLInputElement || event.target instanceof HTMLTextAreaElement) {
    return;
  }

  // Zoom shortcuts
  if (event.ctrlKey || event.metaKey) {
    switch (event.key) {
      case '=':
      case '+':
        event.preventDefault();
        zoomIn();
        break;
      case '-':
        event.preventDefault();
        zoomOut();
        break;
      case '0':
        event.preventDefault();
        actualSize();
        break;
      case '1':
        if (event.shiftKey) {
          event.preventDefault();
          fitToScreen();
        }
        break;
    }
  }
};

// Mouse wheel zoom (when over zoom controls)
const handleWheel = (event: WheelEvent) => {
  if (event.ctrlKey || event.metaKey) {
    event.preventDefault();
    
    const delta = event.deltaY > 0 ? -1 : 1;
    const zoomFactor = 1 + (delta * 0.1);
    const newZoom = Math.max(props.minZoom, Math.min(props.maxZoom, props.zoom * zoomFactor));
    
    emit('update:zoom', newZoom);
  }
};

onMounted(() => {
  document.addEventListener('keydown', handleKeydown);
  document.addEventListener('wheel', handleWheel, { passive: false });
});

onUnmounted(() => {
  document.removeEventListener('keydown', handleKeydown);
  document.removeEventListener('wheel', handleWheel);
});
</script>

<style scoped>
/* Custom slider styles */
input[type="range"] {
  -webkit-appearance: none;
  background: transparent;
}

input[type="range"]::-webkit-slider-track {
  @apply bg-gray-200 dark:bg-gray-700 h-1 rounded-lg;
}

input[type="range"]::-webkit-slider-thumb {
  @apply bg-blue-600 w-3 h-3 rounded-full cursor-pointer;
  -webkit-appearance: none;
  margin-top: -4px;
}

input[type="range"]::-moz-range-track {
  @apply bg-gray-200 dark:bg-gray-700 h-1 rounded-lg border-0;
}

input[type="range"]::-moz-range-thumb {
  @apply bg-blue-600 w-3 h-3 rounded-full cursor-pointer border-0;
  margin-top: -4px;
}
</style>

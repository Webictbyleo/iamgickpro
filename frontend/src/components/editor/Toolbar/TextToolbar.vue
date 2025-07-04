<template>
  <div class="flex items-center space-x-4">
    <!-- Font Family with enhanced width and styling -->
    <div class="w-56">
      <FontSelector
        :value="fontFamily"
        @update="handleFontChange"
        @font-loaded="handleFontLoaded"
        @font-error="handleFontError"
        :preview-text="'AaBbCc'"
        placeholder="Choose font family"
        :max-display-fonts="30"
      />
    </div>

    <!-- Font Size -->
    <div class="flex items-center space-x-1 w-36">
      <PropertyNumberInput
        :value="fontSize"
        @update:value="(value) => $emit('update', { fontSize: value })"
        @change="(value) => $emit('update', { fontSize: value })"
        :min="8"
        :max="200"
        :step="1"
        unit="px"
        :presets="fontSizePresets"
        input-class="w-16"
        placeholder="16"
      />
    </div>

    <!-- Font Style Controls -->
    <div class="flex items-center space-x-1">
      <PropertyToggle
        :active="fontWeight === 'bold'"
        @update="$emit('update', { fontWeight: $event ? 'bold' : 'normal' })"
        tooltip="Bold (Ctrl+B)"
      >
        <BoldIcon class="w-5 h-5" />
      </PropertyToggle>
      <PropertyToggle
        :active="fontStyle === 'italic'"
        @update="$emit('update', { fontStyle: $event ? 'italic' : 'normal' })"
        tooltip="Italic (Ctrl+I)"
      >
        <ItalicIcon class="w-5 h-5" />
      </PropertyToggle>
      <PropertyToggle
        :active="textDecoration === 'underline'"
        @update="$emit('update', { textDecoration: $event ? 'underline' : 'none' })"
        tooltip="Underline (Ctrl+U)"
      >
        <UnderlineIcon class="w-5 h-5" />
      </PropertyToggle>
    </div>

    <!-- Text Alignment -->
    <div class="flex items-center space-x-1">
      <PropertyToggle
        :active="textAlign === 'left'"
        @update="$emit('update', { textAlign: 'left' })"
        tooltip="Align Left"
      >
        <AlignLeftIcon class="w-4 h-4" />
      </PropertyToggle>
      <PropertyToggle
        :active="textAlign === 'center'"
        @update="$emit('update', { textAlign: 'center' })"
        tooltip="Align Center"
      >
        <AlignCenterIcon class="w-4 h-4" />
      </PropertyToggle>
      <PropertyToggle
        :active="textAlign === 'right'"
        @update="$emit('update', { textAlign: 'right' })"
        tooltip="Align Right"
      >
        <AlignRightIcon class="w-4 h-4" />
      </PropertyToggle>
    </div>

    <!-- Text Color -->
    <div class="flex items-center space-x-2">
      <PropertyColorPicker
        :value="color"
        @update="(colorValue) => $emit('update', { color: colorValue })"
        tooltip="Text Color"
      />
    </div>

    <!-- Spacing -->
    <div class="relative">
      <button
        @click="showAdvancedControls = !showAdvancedControls"
        class="px-2 py-1 text-xs bg-gray-100 hover:bg-gray-200 rounded border flex items-center"
        title="Spacing"
      >
        <SpacingIcon class="w-5 h-5" />
      </button>
      <div v-if="showAdvancedControls" class="absolute top-full left-0 mt-1 p-3 bg-white border rounded shadow-lg z-10 min-w-48">
        <div class="space-y-3">
          <!-- Line Height -->
          <div>
            <label class="block text-xs font-medium text-gray-700 mb-1">Line Height:</label>
            <div class="flex items-center space-x-2">
              <PropertySlider
                :value="lineHeight || 1.2"
                :min="0.8"
                :max="3.0"
                :step="0.1"
                @update="$emit('update', { lineHeight: $event })"
              />
            </div>
          </div>

          <!-- Letter Spacing -->
          <div>
            <label class="block text-xs font-medium text-gray-700 mb-1">Letter Spacing:</label>
            <div class="flex items-center space-x-2">
              <PropertySlider
                :value="letterSpacing || 0"
                :min="-5"
                :max="10"
                :step="0.1"
                @update="$emit('update', { letterSpacing: $event })"
              />
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'
import FontSelector from '@/components/editor/Properties/FontSelector.vue'
import PropertyNumberInput from '@/components/editor/Properties/PropertyNumberInput.vue'
import PropertyToggle from '@/components/editor/Properties/PropertyToggle.vue'
import PropertyColorPicker from '@/components/editor/Properties/PropertyColorPicker.vue'
import PropertySlider from '@/components/editor/Properties/PropertySlider.vue'
import BoldIcon from '@/components/icons/BoldIcon.vue'
import ItalicIcon from '@/components/icons/ItalicIcon.vue'
import UnderlineIcon from '@/components/icons/UnderlineIcon.vue'
import AlignLeftIcon from '@/components/icons/AlignLeftIcon.vue'
import AlignCenterIcon from '@/components/icons/AlignCenterIcon.vue'
import AlignRightIcon from '@/components/icons/AlignRightIcon.vue'
import SpacingIcon from '@/components/icons/SpacingIcon.vue'
import { useNotifications } from '@/composables/useNotifications'

interface AutoResize {
  enabled: boolean
  mode: 'none' | 'width' | 'height' | 'both'
  minWidth?: number
  maxWidth?: number
  minHeight?: number
  maxHeight?: number
}

interface Props {
  fontFamily?: string
  fontSize?: number
  fontWeight?: string
  fontStyle?: string
  textDecoration?: string
  textAlign?: string
  color?: string
  lineHeight?: number
  letterSpacing?: number
  autoResize?: AutoResize
}

const props = withDefaults(defineProps<Props>(), {
  fontFamily: 'Inter',
  fontSize: 16,
  fontWeight: 'normal',
  fontStyle: 'normal',
  textDecoration: 'none',
  textAlign: 'left',
  color: '#000000',
  lineHeight: 1.2,
  letterSpacing: 0
})

const emit = defineEmits<{
  update: [properties: Partial<Props>]
}>()

// Advanced controls toggle state
const showAdvancedControls = ref(false)

// Notifications
const { error } = useNotifications()

// Font event handlers
const handleFontChange = (fontFamily: string) => {
  emit('update', { fontFamily })
}

const handleFontLoaded = (fontFamily: string) => {
  // Font loading is now handled internally by TextLayerRenderer
  // No external events needed - text reflow happens automatically
}

const handleFontError = (fontFamily: string, err: Error) => {
  error('Font Load Error', `Failed to load ${fontFamily}. Using fallback font.`)
}

const fontSizePresets = [
  { label: 'Tiny', value: 8 },
  { label: 'Small', value: 12 },
  { label: 'Default', value: 16 },
  { label: 'Medium', value: 20 },
  { label: 'Large', value: 24 },
  { label: 'X-Large', value: 32 },
  { label: 'XX-Large', value: 48 },
  { label: 'Huge', value: 64 },
  { label: 'Display', value: 96 }
]

const autoResizeOptions = [
  { value: 'none', label: 'None' },
  { value: 'width', label: 'Width' },
  { value: 'height', label: 'Height' },
  { value: 'both', label: 'Both' }
]

// Auto-resize controls
const updateAutoResize = (mode: string | number) => {
  const modeStr = String(mode)
  if (modeStr === 'none') {
    emit('update', {
      autoResize: {
        enabled: false,
        mode: 'none'
      }
    })
  } else {
    emit('update', {
      autoResize: {
        enabled: true,
        mode: modeStr as 'width' | 'height' | 'both',
        minWidth: 50,
        maxWidth: 800,
        minHeight: 20,
        maxHeight: 400
      }
    })
  }
}
</script>

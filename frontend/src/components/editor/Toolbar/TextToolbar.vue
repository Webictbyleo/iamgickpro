<template>
  <div class="flex items-center space-x-4">
    <!-- Font Family -->
    <div>
      <PropertyDropdown
        :value="fontFamily"
        :options="fontOptions"
        @update="$emit('update', { fontFamily: String($event) })"
        class="w-36"
        :show-dropdown-icon="false"
      />
    </div>

    <!-- Font Size -->
    <div class="flex items-center space-x-1">
      <PropertyDropdown
        :value="fontSize"
        :options="fontSizeOptions"
        @update="$emit('update', { fontSize: Number($event) })"
        class="w-20"
        :show-dropdown-icon="false"
      />
      
    </div>

    <!-- Font Style Controls -->
    <div class="flex items-center space-x-1">
      <PropertyToggle
        :active="fontWeight === 'bold'"
        @update="$emit('update', { fontWeight: $event ? 'bold' : 'normal' })"
        tooltip="Bold (Ctrl+B)"
      >
        <BoldIcon class="w-4 h-4" />
      </PropertyToggle>
      
      <PropertyToggle
        :active="fontStyle === 'italic'"
        @update="$emit('update', { fontStyle: $event ? 'italic' : 'normal' })"
        tooltip="Italic (Ctrl+I)"
      >
        <ItalicIcon class="w-4 h-4" />
      </PropertyToggle>
      
      <PropertyToggle
        :active="textDecoration === 'underline'"
        @update="$emit('update', { textDecoration: $event ? 'underline' : 'none' })"
        tooltip="Underline (Ctrl+U)"
      >
        <UnderlineIcon class="w-4 h-4" />
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
        @update="$emit('update', { color: $event })"
        tooltip="Text Color"
      />
    </div>

    <!-- Advanced Text Controls Toggle -->
    <div class="relative">
      <button
        @click="showAdvancedControls = !showAdvancedControls"
        class="px-2 py-1 text-xs bg-gray-100 hover:bg-gray-200 rounded border"
        title="Advanced Text Controls"
      >
        Aa
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

    <!-- Auto-resize Controls -->
    <div class="flex items-center space-x-2">
      <PropertyDropdown
        :value="autoResize?.mode || 'none'"
        :options="autoResizeOptions"
        @update="updateAutoResize"
        class="w-20"
        :show-dropdown-icon="false"
      />
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'
import PropertyDropdown from '@/components/editor/Properties/PropertyDropdown.vue'
import PropertyToggle from '@/components/editor/Properties/PropertyToggle.vue'
import PropertyColorPicker from '@/components/editor/Properties/PropertyColorPicker.vue'
import PropertySlider from '@/components/editor/Properties/PropertySlider.vue'
import BoldIcon from '@/components/icons/BoldIcon.vue'
import ItalicIcon from '@/components/icons/ItalicIcon.vue'
import UnderlineIcon from '@/components/icons/UnderlineIcon.vue'
import AlignLeftIcon from '@/components/icons/AlignLeftIcon.vue'
import AlignCenterIcon from '@/components/icons/AlignCenterIcon.vue'
import AlignRightIcon from '@/components/icons/AlignRightIcon.vue'
import ChevronUpIcon from '@/components/icons/ChevronUpIcon.vue'
import ChevronDownIcon from '@/components/icons/ChevronDownIcon.vue'

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

const fontOptions = [
  { value: 'Inter', label: 'Inter' },
  { value: 'Arial', label: 'Arial' },
  { value: 'Helvetica', label: 'Helvetica' },
  { value: 'Times New Roman', label: 'Times New Roman' },
  { value: 'Georgia', label: 'Georgia' },
  { value: 'Roboto', label: 'Roboto' },
  { value: 'Open Sans', label: 'Open Sans' },
  { value: 'Lato', label: 'Lato' },
  { value: 'Montserrat', label: 'Montserrat' },
  { value: 'Poppins', label: 'Poppins' },
  { value: 'Playfair Display', label: 'Playfair Display' },
  { value: 'Source Sans Pro', label: 'Source Sans Pro' },
  { value: 'Oswald', label: 'Oswald' },
  { value: 'Merriweather', label: 'Merriweather' }
]

const fontSizeOptions = [
  { value: 8, label: '8px' },
  { value: 10, label: '10px' },
  { value: 12, label: '12px' },
  { value: 14, label: '14px' },
  { value: 16, label: '16px' },
  { value: 18, label: '18px' },
  { value: 20, label: '20px' },
  { value: 24, label: '24px' },
  { value: 28, label: '28px' },
  { value: 32, label: '32px' },
  { value: 36, label: '36px' },
  { value: 42, label: '42px' },
  { value: 48, label: '48px' },
  { value: 56, label: '56px' },
  { value: 64, label: '64px' },
  { value: 72, label: '72px' },
  { value: 96, label: '96px' }
]

const autoResizeOptions = [
  { value: 'none', label: 'None' },
  { value: 'width', label: 'Width' },
  { value: 'height', label: 'Height' },
  { value: 'both', label: 'Both' }
]

// Font size controls
const increaseFontSize = () => {
  const currentSize = props.fontSize || 16
  const newSize = Math.min(96, currentSize + (currentSize < 24 ? 2 : 4))
  emit('update', { fontSize: newSize })
}

const decreaseFontSize = () => {
  const currentSize = props.fontSize || 16
  const newSize = Math.max(8, currentSize - (currentSize <= 24 ? 2 : 4))
  emit('update', { fontSize: newSize })
}

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

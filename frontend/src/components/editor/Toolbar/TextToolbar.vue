<template>
  <div class="flex items-center space-x-4">
    <!-- Font Family -->
    <div class="flex items-center space-x-2">
      <label class="text-sm font-medium text-gray-700 min-w-max">Font:</label>
      <PropertyDropdown
        :value="fontFamily"
        :options="fontOptions"
        @update="$emit('update', { fontFamily: String($event) })"
        class="w-32"
      />
    </div>

    <!-- Font Size -->
    <div class="flex items-center space-x-2">
      <label class="text-sm font-medium text-gray-700">Size:</label>
      <PropertyInput
        :value="fontSize"
        type="number"
        @update="$emit('update', { fontSize: Number($event) })"
        class="w-16"
        :min="8"
        :max="200"
      />
    </div>

    <!-- Font Style Controls -->
    <div class="flex items-center space-x-1">
      <PropertyToggle
        :active="fontWeight === 'bold'"
        @update="$emit('update', { fontWeight: $event ? 'bold' : 'normal' })"
        tooltip="Bold"
      >
        <BoldIcon class="w-4 h-4" />
      </PropertyToggle>
      
      <PropertyToggle
        :active="fontStyle === 'italic'"
        @update="$emit('update', { fontStyle: $event ? 'italic' : 'normal' })"
        tooltip="Italic"
      >
        <ItalicIcon class="w-4 h-4" />
      </PropertyToggle>
      
      <PropertyToggle
        :active="textDecoration === 'underline'"
        @update="$emit('update', { textDecoration: $event ? 'underline' : 'none' })"
        tooltip="Underline"
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
      <label class="text-sm font-medium text-gray-700">Color:</label>
      <PropertyColorPicker
        :value="color"
        @update="$emit('update', { color: $event })"
      />
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import PropertyDropdown from '@/components/editor/Properties/PropertyDropdown.vue'
import PropertyInput from '@/components/editor/Properties/PropertyInput.vue'
import PropertyToggle from '@/components/editor/Properties/PropertyToggle.vue'
import PropertyColorPicker from '@/components/editor/Properties/PropertyColorPicker.vue'
import BoldIcon from '@/components/icons/BoldIcon.vue'
import ItalicIcon from '@/components/icons/ItalicIcon.vue'
import UnderlineIcon from '@/components/icons/UnderlineIcon.vue'
import AlignLeftIcon from '@/components/icons/AlignLeftIcon.vue'
import AlignCenterIcon from '@/components/icons/AlignCenterIcon.vue'
import AlignRightIcon from '@/components/icons/AlignRightIcon.vue'

interface Props {
  fontFamily?: string
  fontSize?: number
  fontWeight?: string
  fontStyle?: string
  textDecoration?: string
  textAlign?: string
  color?: string
}

const props = withDefaults(defineProps<Props>(), {
  fontFamily: 'Inter',
  fontSize: 16,
  fontWeight: 'normal',
  fontStyle: 'normal',
  textDecoration: 'none',
  textAlign: 'left',
  color: '#000000'
})

const emit = defineEmits<{
  update: [properties: Partial<Props>]
}>()

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
  { value: 'Poppins', label: 'Poppins' }
]
</script>

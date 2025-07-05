<template>
  <button
    :class="[
      'w-full text-left px-4 py-2 text-sm flex items-center transition-colors',
      variant === 'danger' 
        ? 'text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20' 
        : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700',
      disabled && 'opacity-50 cursor-not-allowed'
    ]"
    :disabled="disabled"
    @click="handleClick"
    @mouseenter="$emit('mouseenter')"
    @mouseleave="$emit('mouseleave')"
  >
    <!-- Icon -->
    <component
      v-if="iconComponent"
      :is="iconComponent"
      class="w-4 h-4 mr-3 flex-shrink-0"
    />
    
    <!-- Label -->
    <span class="flex-1 truncate">{{ label }}</span>
    
    <!-- Shortcut -->
    <span v-if="shortcut" class="ml-auto text-xs text-gray-500 dark:text-gray-400 flex-shrink-0">
      {{ shortcut }}
    </span>
    
    <!-- Submenu indicator -->
    <ChevronRightIcon v-if="hasSubmenu" class="w-4 h-4 ml-auto flex-shrink-0" />
  </button>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { ChevronRightIcon } from '@heroicons/vue/24/outline'
import {
  DocumentDuplicateIcon,
  ClipboardDocumentIcon,
  Square2StackIcon,
  Square3Stack3DIcon,
  ChevronDoubleUpIcon,
  ChevronUpIcon,
  ChevronDownIcon,
  ChevronDoubleDownIcon,
  LockClosedIcon,
  LockOpenIcon,
  EyeIcon,
  EyeSlashIcon,
  TrashIcon,
  PencilIcon,
  PhotoIcon,
  Squares2X2Icon,
  MagnifyingGlassIcon,
  ArrowsPointingOutIcon
} from '@heroicons/vue/24/outline'

interface Props {
  icon?: string
  label: string
  shortcut?: string
  hasSubmenu?: boolean
  disabled?: boolean
  variant?: 'default' | 'danger'
}

const props = withDefaults(defineProps<Props>(), {
  variant: 'default',
  disabled: false,
  hasSubmenu: false
})

const emit = defineEmits<{
  click: []
  mouseenter: []
  mouseleave: []
}>()

// Icon mapping
const iconMap = {
  DocumentDuplicateIcon,
  ClipboardDocumentIcon,
  Square2StackIcon,
  Square3Stack3DIcon,
  ChevronDoubleUpIcon,
  ChevronUpIcon,
  ChevronDownIcon,
  ChevronDoubleDownIcon,
  LockClosedIcon,
  LockOpenIcon,
  EyeIcon,
  EyeSlashIcon,
  TrashIcon,
  PencilIcon,
  PhotoIcon,
  Squares2X2Icon,
  MagnifyingGlassIcon,
  ArrowsPointingOutIcon
}

const iconComponent = computed(() => {
  return props.icon ? iconMap[props.icon as keyof typeof iconMap] : null
})

const handleClick = () => {
  if (!props.disabled) {
    emit('click')
  }
}
</script>

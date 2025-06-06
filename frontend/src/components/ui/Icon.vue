<template>
  <svg
    :width="size"
    :height="size"
    :viewBox="viewBox"
    :class="className"
    fill="currentColor"
  >
    <path :d="iconPath" />
  </svg>
</template>

<script setup lang="ts">
import { computed } from 'vue'

interface Props {
  name: string
  size?: number | string
  className?: string
}

const props = withDefaults(defineProps<Props>(), {
  size: 20,
  className: ''
})

const iconPaths: Record<string, string> = {
  // Basic icons
  'x': 'M6 6l12 12M6 18L18 6',
  'cursor': 'M3 3l7.07 16.97 2.51-7.39 7.39-2.51L3 3z',
  'type': 'M4 7V4h16v3M9 20h6M12 4v16',
  'square': 'M5 3h14a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2z',
  'image': 'M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zM9 11l2.5 3.25L15 10l4 5H5l4-4z',
  'layers': 'M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z',
  'grid': 'M3 3h7v7H3zM14 3h7v7h-7zM14 14h7v7h-7zM3 14h7v7H3z',
  
  // Action icons
  'undo': 'M3 7v6h6M9 1a9 9 0 1 1-6 2.3l3.3 3.3',
  'redo': 'M21 7v6h-6M15 1a9 9 0 1 0 6 2.3l-3.3 3.3',
  'download': 'M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M7 10l5 5 5-5M12 15V3',
  'settings': 'M12.22 2l-.2.4c-.6 1.2-2.2 1.2-2.8 0L9.02 2A10 10 0 0 0 2 12a10 10 0 0 0 7.02 10l.2-.4c.6-1.2 2.2-1.2 2.8 0l.2.4A10 10 0 0 0 22 12a10 10 0 0 0-7.78-10z',
  'eye': 'M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z M12 9a3 3 0 1 1 0 6 3 3 0 0 1 0-6z',
  'share': 'M4 12v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8M16 6l-4-4-4 4M12 2v13',
  
  // Zoom icons
  'zoom-in': 'M8 4a4 4 0 1 0 0 8 4 4 0 0 0 0-8zM2 8a6 6 0 1 1 10.89 3.476l4.817 4.817a1 1 0 0 1-1.414 1.414l-4.816-4.816A6 6 0 0 1 2 8z M6 8h4M8 6v4',
  'zoom-out': 'M8 4a4 4 0 1 0 0 8 4 4 0 0 0 0-8zM2 8a6 6 0 1 1 10.89 3.476l4.817 4.817a1 1 0 0 1-1.414 1.414l-4.816-4.816A6 6 0 0 1 2 8z M6 8h4',
  'zoom-fit': 'M8 3H5a2 2 0 0 0-2 2v3m18 0V5a2 2 0 0 0-2-2h-3m0 18h3a2 2 0 0 0 2-2v-3M3 16v3a2 2 0 0 0 2 2h3',
  'zoom-reset': 'M8 4a4 4 0 1 0 0 8 4 4 0 0 0 0-8zM2 8a6 6 0 1 1 10.89 3.476l4.817 4.817a1 1 0 0 1-1.414 1.414l-4.816-4.816A6 6 0 0 1 2 8z',
}

const iconPath = computed(() => iconPaths[props.name] || '')

const viewBox = computed(() => '0 0 24 24')
</script>

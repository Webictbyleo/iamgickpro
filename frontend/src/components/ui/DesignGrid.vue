<template>
  <!-- Modern masonry-style grid that adapts to various aspect ratios -->
  <div class="grid gap-6" :class="gridColumns">
    <!-- Design Cards -->
    <DesignCard
      v-for="design in designs"
      :key="design.id"
      :design="design"
      @click="$emit('open', design)"
      @edit="$emit('edit', design)"
      @duplicate="$emit('duplicate', design)"
      @delete="$emit('delete', design)"
      @preview="$emit('preview', design)"
      @share="$emit('share', design)"
      @rename="$emit('rename', design)"
      @download="$emit('download', design)"
    />

    <!-- Loading Cards with varied heights for realistic layout -->
    <div 
      v-if="loading"
      v-for="i in loadingCount"
      :key="`loading-${i}`"
      :class="[
        'bg-white rounded-2xl shadow-sm border border-gray-100 animate-pulse overflow-hidden',
        getLoadingCardHeight(i)
      ]"
    >
      <div class="h-full flex flex-col">
        <div class="flex-1 bg-gradient-to-br from-gray-200 to-gray-300 rounded-t-2xl"></div>
        <div class="p-4 space-y-3">
          <div class="h-4 bg-gray-200 rounded-lg w-3/4"></div>
          <div class="h-3 bg-gray-200 rounded-lg w-1/2"></div>
        </div>
      </div>
    </div>
  </div>

  <!-- Empty State -->
  <div 
    v-if="!loading && designs.length === 0"
    class="text-center py-16 bg-gradient-to-br from-gray-50 to-blue-50 rounded-2xl border border-gray-100"
  >
    <div class="w-20 h-20 bg-blue-100 rounded-2xl flex items-center justify-center mx-auto mb-6">
      <component :is="icons.design" class="w-10 h-10 text-blue-600" />
    </div>
    <h3 class="text-xl font-semibold text-gray-900 mb-3">No designs yet</h3>
    <p class="text-gray-600 mb-8 max-w-sm mx-auto">No designs available at the moment</p>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import type { Design } from '@/types'
import { useIcons } from '@/composables/useIcons'
import DesignCard from './DesignCard.vue'

const icons = useIcons()

interface Props {
  designs: Design[]
  loading?: boolean
  loadingCount?: number
}

withDefaults(defineProps<Props>(), {
  loading: false,
  loadingCount: 8
})

defineEmits<{
  open: [design: Design]
  edit: [design: Design]
  duplicate: [design: Design]
  delete: [design: Design]
  preview: [design: Design]
  share: [design: Design]
  rename: [design: Design]
  download: [design: Design]
}>()

// Responsive grid columns based on screen size
const gridColumns = computed(() => {
  return 'grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-5'
})

// Generate varied loading card heights for more realistic layout
const getLoadingCardHeight = (index: number) => {
  const heights = [
    'h-[280px]', // Portrait
    'h-[220px]', // Square-ish
    'h-[180px]', // Landscape
    'h-[320px]', // Tall portrait
    'h-[200px]', // Medium
    'h-[160px]', // Short landscape
    'h-[240px]', // Standard
    'h-[260px]'  // Tall
  ]
  return heights[index % heights.length]
}
</script>


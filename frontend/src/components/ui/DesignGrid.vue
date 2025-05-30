<template>
  <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-6">
    <!-- Create New Design Card -->
    <div 
      class="aspect-video bg-gradient-to-br from-blue-50 to-indigo-50 border-2 border-dashed border-blue-200 rounded-2xl flex flex-col items-center justify-center cursor-pointer hover:border-blue-400 hover:from-blue-100 hover:to-indigo-100 transition-all duration-200 transform hover:scale-[1.02] group"
      @click="$emit('create')"
    >
      <div class="text-center">
        <div class="w-16 h-16 bg-white group-hover:bg-blue-50 rounded-2xl flex items-center justify-center mx-auto mb-4 shadow-sm transition-all duration-200">
          <component :is="icons.plus" class="w-8 h-8 text-blue-600 group-hover:text-blue-700" />
        </div>
        <p class="text-sm font-semibold text-blue-700 group-hover:text-blue-800">Create New Design</p>
        <p class="text-xs text-blue-500 mt-1">Start from scratch</p>
      </div>
    </div>

    <!-- Design Cards -->
    <DesignCard
      v-for="design in designs"
      :key="design.id"
      :design="design"
      @click="$emit('open', design)"
      @edit="$emit('edit', design)"
      @duplicate="$emit('duplicate', design)"
      @delete="$emit('delete', design)"
    />

    <!-- Loading Cards -->
    <div 
      v-if="loading"
      v-for="i in loadingCount"
      :key="`loading-${i}`"
      class="aspect-video bg-white rounded-2xl shadow-sm border border-gray-100 animate-pulse overflow-hidden"
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
    <p class="text-gray-600 mb-8 max-w-sm mx-auto">Get started by creating your first design and bring your ideas to life</p>
    <button
      @click="$emit('create')"
      class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white font-semibold rounded-xl shadow-lg hover:shadow-xl transition-all duration-200 transform hover:scale-105"
    >
      <component :is="icons.plus" class="w-5 h-5 mr-2" />
      Create New Design
    </button>
  </div>
</template>

<script setup lang="ts">
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
  create: []
  open: [design: Design]
  edit: [design: Design]
  duplicate: [design: Design]
  delete: [design: Design]
}>()
</script>

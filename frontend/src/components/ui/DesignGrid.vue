<template>
  <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-6">
    <!-- Create New Design Card - Only show when there are existing designs -->
    <div 
      v-if="!loading && designs.length > 0"
      class="relative min-h-[240px] bg-gradient-to-br from-violet-50 via-blue-50 to-indigo-50 border-2 border-dashed border-violet-300/60 rounded-2xl flex flex-col items-center justify-center cursor-pointer hover:border-violet-500/80 hover:border-solid hover:from-violet-100 hover:to-indigo-100 transition-all duration-300 transform hover:scale-[1.02] hover:shadow-xl hover:shadow-violet-500/10 group overflow-hidden"
      @click="$emit('create')"
    >
      <!-- Animated background particles -->
      <div class="absolute inset-0 opacity-30">
        <div class="absolute top-4 left-4 w-2 h-2 bg-violet-300 rounded-full animate-pulse"></div>
        <div class="absolute top-12 right-6 w-1 h-1 bg-blue-400 rounded-full animate-ping delay-300"></div>
        <div class="absolute bottom-8 left-8 w-1.5 h-1.5 bg-indigo-300 rounded-full animate-pulse delay-700"></div>
        <div class="absolute bottom-4 right-4 w-1 h-1 bg-violet-400 rounded-full animate-ping delay-1000"></div>
      </div>
      
      <!-- Glow effect -->
      <div class="absolute inset-0 bg-gradient-to-br from-violet-400/5 via-blue-400/5 to-indigo-400/5 opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>
      
      <div class="text-center relative z-10 px-4 py-2">
        <!-- Enhanced icon container -->
        <div class="w-16 h-16 bg-white/80  group-hover:bg-white group-hover:shadow-lg group-hover:shadow-violet-500/20 rounded-2xl flex items-center justify-center mx-auto mb-3 shadow-sm transition-all duration-300 group-hover:scale-110 group-hover:-translate-y-1">
          <div class="relative">
            <component :is="icons.plus" class="w-8 h-8 text-violet-600 group-hover:text-violet-700 transition-all duration-300 group-hover:rotate-90" />
            <!-- Rotating ring -->
            <div class="absolute inset-0 w-8 h-8 border-2 border-violet-300/30 rounded-full animate-spin group-hover:border-violet-400/50 transition-colors duration-300" style="animation-duration: 3s;"></div>
          </div>
        </div>
        
        <!-- Enhanced text content -->
        <div class="space-y-1">
          <p class="text-sm font-bold bg-gradient-to-r from-violet-700 via-blue-700 to-indigo-700 bg-clip-text text-transparent group-hover:from-violet-800 group-hover:via-blue-800 group-hover:to-indigo-800 transition-all duration-300">
            Create New Design
          </p>
          <p class="text-xs text-violet-600/80 group-hover:text-violet-700/90 transition-colors duration-300 font-medium">
            Start your creative journey
          </p>
          
          <!-- Action hint -->
          <div class="mt-2 opacity-0 group-hover:opacity-100 transition-all duration-300 delay-150 transform translate-y-1 group-hover:translate-y-0">
            <div class="inline-flex items-center text-xs text-violet-500 bg-white/60  px-2 py-1 rounded-full border border-violet-200/50">
              <span class="mr-1">✨</span>
              Click to begin
            </div>
          </div>
        </div>
      </div>
      
      <!-- Subtle corner accents -->
      <div class="absolute top-0 left-0 w-8 h-8 border-l-2 border-t-2 border-violet-300/40 rounded-tl-2xl group-hover:border-violet-400/60 transition-colors duration-300"></div>
      <div class="absolute bottom-0 right-0 w-8 h-8 border-r-2 border-b-2 border-violet-300/40 rounded-br-2xl group-hover:border-violet-400/60 transition-colors duration-300"></div>
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
      @preview="$emit('preview', design)"
      @share="$emit('share', design)"
      @rename="$emit('rename', design)"
      @download="$emit('download', design)"
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
  preview: [design: Design]
  share: [design: Design]
  rename: [design: Design]
  download: [design: Design]
}>()
</script>

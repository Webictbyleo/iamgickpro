<template>
  <div v-if="totalPages > 1" class="flex items-center justify-center space-x-2">
    <button
      @click="$emit('page-change', currentPage - 1)"
      :disabled="currentPage === 1"
      class="p-3 rounded-xl border border-gray-300 text-gray-500 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-200"
    >
      <ChevronLeftIcon class="w-5 h-5" />
    </button>
    
    <div class="flex items-center space-x-1">
      <button
        v-for="page in getPaginationPages"
        :key="page"
        @click="$emit('page-change', page)"
        :class="[
          page === currentPage
            ? 'bg-gradient-to-r from-indigo-600 to-violet-600 text-white shadow-lg'
            : 'bg-white text-gray-700 hover:bg-gray-50 border border-gray-300',
          'px-4 py-3 rounded-xl text-sm font-semibold transition-all duration-200 min-w-[48px]'
        ]"
      >
        {{ page }}
      </button>
    </div>
    
    <button
      @click="$emit('page-change', currentPage + 1)"
      :disabled="currentPage === totalPages"
      class="p-3 rounded-xl border border-gray-300 text-gray-500 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-200"
    >
      <ChevronRightIcon class="w-5 h-5" />
    </button>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { ChevronLeftIcon, ChevronRightIcon } from '@heroicons/vue/24/outline'

interface Props {
  currentPage: number
  totalPages: number
  maxVisible?: number
}

const props = withDefaults(defineProps<Props>(), {
  maxVisible: 7
})

defineEmits<{
  'page-change': [page: number]
}>()

const getPaginationPages = computed((): number[] => {
  const maxVisible = props.maxVisible
  const pages: number[] = []
  
  if (props.totalPages <= maxVisible) {
    for (let i = 1; i <= props.totalPages; i++) {
      pages.push(i)
    }
  } else {
    const start = Math.max(1, props.currentPage - 3)
    const end = Math.min(props.totalPages, start + maxVisible - 1)
    
    for (let i = start; i <= end; i++) {
      pages.push(i)
    }
  }
  
  return pages
})
</script>

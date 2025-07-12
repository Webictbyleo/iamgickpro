<template>
  <div class="datatable-pagination bg-gray-50 dark:bg-gray-700 border-t border-gray-200 dark:border-gray-600 px-4 py-3">
    <div class="flex items-center justify-between">
      <!-- Results info -->
      <div class="flex items-center space-x-4">
        <div class="text-sm text-gray-700 dark:text-gray-300">
          Showing {{ startItem }} to {{ endItem }} of {{ pagination.total }} results
        </div>
        
        <!-- Page size selector -->
        <div class="flex items-center space-x-2">
          <label class="text-sm text-gray-700 dark:text-gray-300">Show:</label>
          <select
            :value="pagination.pageSize"
            @change="handlePageSizeChange"
            class="px-2 py-1 text-sm border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-1 focus:ring-primary-500 focus:border-primary-500"
          >
            <option v-for="size in pageSizeOptions" :key="size" :value="size">
              {{ size }}
            </option>
          </select>
        </div>
      </div>
      
      <!-- Pagination controls -->
      <nav class="flex items-center space-x-2">
        <!-- First page -->
        <button
          @click="goToPage(1)"
          :disabled="pagination.page === 1"
          class="px-2 py-1 text-sm border border-gray-300 dark:border-gray-600 rounded text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
          title="First page"
        >
          <ChevronDoubleLeftIcon class="w-4 h-4" />
        </button>
        
        <!-- Previous page -->
        <button
          @click="goToPage(pagination.page - 1)"
          :disabled="pagination.page === 1"
          class="px-2 py-1 text-sm border border-gray-300 dark:border-gray-600 rounded text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
          title="Previous page"
        >
          <ChevronLeftIcon class="w-4 h-4" />
        </button>
        
        <!-- Page numbers -->
        <div class="flex items-center space-x-1">
          <button
            v-for="page in visiblePages"
            :key="page"
            @click="goToPage(page)"
            :class="[
              'px-3 py-1 text-sm rounded transition-colors',
              page === pagination.page
                ? 'bg-primary-600 text-white'
                : 'border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700'
            ]"
          >
            {{ page }}
          </button>
        </div>
        
        <!-- Next page -->
        <button
          @click="goToPage(pagination.page + 1)"
          :disabled="pagination.page === pagination.totalPages"
          class="px-2 py-1 text-sm border border-gray-300 dark:border-gray-600 rounded text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
          title="Next page"
        >
          <ChevronRightIcon class="w-4 h-4" />
        </button>
        
        <!-- Last page -->
        <button
          @click="goToPage(pagination.totalPages)"
          :disabled="pagination.page === pagination.totalPages"
          class="px-2 py-1 text-sm border border-gray-300 dark:border-gray-600 rounded text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
          title="Last page"
        >
          <ChevronDoubleRightIcon class="w-4 h-4" />
        </button>
      </nav>
      
      <!-- Page jump -->
      <div class="flex items-center space-x-2">
        <label class="text-sm text-gray-700 dark:text-gray-300">Go to:</label>
        <input
          v-model.number="jumpToPage"
          type="number"
          :min="1"
          :max="pagination.totalPages"
          @keydown.enter="handlePageJump"
          class="w-16 px-2 py-1 text-sm border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-1 focus:ring-primary-500 focus:border-primary-500"
        />
        <button
          @click="handlePageJump"
          class="px-2 py-1 text-sm bg-primary-600 hover:bg-primary-700 text-white rounded transition-colors"
        >
          Go
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import {
  ChevronDoubleLeftIcon,
  ChevronLeftIcon,
  ChevronRightIcon,
  ChevronDoubleRightIcon
} from '@heroicons/vue/24/outline'
import type { DataTablePagination } from '../types'

interface Props {
  pagination: DataTablePagination
}

interface Emits {
  (e: 'update:pagination', pagination: DataTablePagination): void
}

const props = defineProps<Props>()
const emit = defineEmits<Emits>()

// Local state
const jumpToPage = ref(props.pagination.page)
const pageSizeOptions = [10, 20, 50, 100, 200]

// Computed
const startItem = computed(() => 
  (props.pagination.page - 1) * props.pagination.pageSize + 1
)

const endItem = computed(() => 
  Math.min(props.pagination.page * props.pagination.pageSize, props.pagination.total)
)

const visiblePages = computed(() => {
  const totalPages = props.pagination.totalPages
  const currentPage = props.pagination.page
  const maxVisible = 7
  
  if (totalPages <= maxVisible) {
    return Array.from({ length: totalPages }, (_, i) => i + 1)
  }
  
  const pages: number[] = []
  const halfVisible = Math.floor(maxVisible / 2)
  
  let start = Math.max(1, currentPage - halfVisible)
  let end = Math.min(totalPages, start + maxVisible - 1)
  
  // Adjust start if we're near the end
  if (end - start < maxVisible - 1) {
    start = Math.max(1, end - maxVisible + 1)
  }
  
  for (let i = start; i <= end; i++) {
    pages.push(i)
  }
  
  return pages
})

// Methods
const goToPage = (page: number) => {
  if (page < 1 || page > props.pagination.totalPages) return
  
  const newPagination = {
    ...props.pagination,
    page
  }
  
  emit('update:pagination', newPagination)
}

const handlePageSizeChange = (event: Event) => {
  const target = event.target as HTMLSelectElement
  const newPageSize = Number(target.value)
  
  // Calculate new page to maintain roughly the same position
  const currentFirstItem = (props.pagination.page - 1) * props.pagination.pageSize + 1
  const newPage = Math.max(1, Math.ceil(currentFirstItem / newPageSize))
  
  const newPagination = {
    ...props.pagination,
    pageSize: newPageSize,
    page: newPage,
    totalPages: Math.ceil(props.pagination.total / newPageSize)
  }
  
  emit('update:pagination', newPagination)
}

const handlePageJump = () => {
  if (jumpToPage.value >= 1 && jumpToPage.value <= props.pagination.totalPages) {
    goToPage(jumpToPage.value)
  }
}

// Update jumpToPage when pagination changes
watch(() => props.pagination.page, (newPage: number) => {
  jumpToPage.value = newPage
})
</script>

<style scoped>
/* Ensure consistent spacing and alignment */
.datatable-pagination {
  min-height: 3rem;
}

/* Remove spinner buttons from number input */
input[type="number"]::-webkit-outer-spin-button,
input[type="number"]::-webkit-inner-spin-button {
  -webkit-appearance: none;
  margin: 0;
}

input[type="number"] {
  -moz-appearance: textfield;
  appearance: textfield;
}
</style>

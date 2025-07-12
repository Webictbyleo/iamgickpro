<template>
  <div class="datatable-toolbar bg-gray-50 dark:bg-gray-700 border-b border-gray-200 dark:border-gray-600 px-4 py-3">
    <div class="flex items-center justify-between">
      <!-- Left side - Add/Edit actions -->
      <div class="flex items-center space-x-2">
        <button
          @click="$emit('add-row')"
          class="inline-flex items-center px-3 py-1.5 text-sm bg-primary-600 hover:bg-primary-700 text-white rounded-md transition-colors"
        >
          <PlusIcon class="w-4 h-4 mr-1" />
          Add Row
        </button>
        
        <div v-if="hasSelection" class="flex items-center space-x-1">
          <button
            @click="$emit('duplicate-rows')"
            class="inline-flex items-center px-3 py-1.5 text-sm border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-md transition-colors"
          >
            <DocumentDuplicateIcon class="w-4 h-4 mr-1" />
            Duplicate ({{ selectedRows.length }})
          </button>
          
          <button
            @click="$emit('delete-rows')"
            class="inline-flex items-center px-3 py-1.5 text-sm border border-danger-300 text-danger-700 dark:text-danger-400 bg-white dark:bg-gray-800 hover:bg-danger-50 dark:hover:bg-danger-900/20 rounded-md transition-colors"
          >
            <TrashIcon class="w-4 h-4 mr-1" />
            Delete ({{ selectedRows.length }})
          </button>
        </div>
      </div>
      
      <!-- Right side - Utility actions -->
      <div class="flex items-center space-x-2">
        <!-- Undo/Redo -->
        <div class="flex items-center border border-gray-300 dark:border-gray-600 rounded-md">
          <button
            @click="$emit('undo')"
            :disabled="!canUndo"
            class="px-2 py-1.5 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
            title="Undo"
          >
            <ArrowUturnLeftIcon class="w-4 h-4" />
          </button>
          <div class="h-6 w-px bg-gray-300 dark:bg-gray-600"></div>
          <button
            @click="$emit('redo')"
            :disabled="!canRedo"
            class="px-2 py-1.5 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
            title="Redo"
          >
            <ArrowUturnRightIcon class="w-4 h-4" />
          </button>
        </div>
        
        <!-- Search -->
        <div class="relative">
          <input
            v-model="searchQuery"
            type="text"
            placeholder="Search..."
            class="pl-8 pr-3 py-1.5 text-sm border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-1 focus:ring-primary-500 focus:border-primary-500"
          />
          <MagnifyingGlassIcon class="absolute left-2.5 top-2 w-4 h-4 text-gray-400" />
        </div>
        
        <!-- Filter indicator -->
        <div v-if="activeFilters.length > 0" class="flex items-center space-x-1">
          <span class="text-xs text-gray-600 dark:text-gray-400">{{ activeFilters.length }} filter(s)</span>
          <button
            @click="$emit('clear-filters')"
            class="text-xs text-primary-600 hover:text-primary-700 dark:text-primary-400 dark:hover:text-primary-300"
          >
            Clear
          </button>
        </div>
        
        <!-- Sort indicator -->
        <div v-if="activeSorts.length > 0" class="flex items-center space-x-1">
          <span class="text-xs text-gray-600 dark:text-gray-400">{{ activeSorts.length }} sort(s)</span>
          <button
            @click="$emit('clear-sorts')"
            class="text-xs text-primary-600 hover:text-primary-700 dark:text-primary-400 dark:hover:text-primary-300"
          >
            Clear
          </button>
        </div>
        
        <!-- Export dropdown -->
        <div class="relative" ref="exportDropdown">
          <button
            @click="toggleExportDropdown"
            class="inline-flex items-center px-3 py-1.5 text-sm border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-md transition-colors"
          >
            <ArrowDownTrayIcon class="w-4 h-4 mr-1" />
            Export
            <ChevronDownIcon class="w-3 h-3 ml-1" />
          </button>
          
          <div
            v-if="showExportDropdown"
            class="absolute right-0 top-full mt-1 w-48 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded-md shadow-lg z-20"
          >
            <div class="py-1">
              <button
                @click="handleExport('csv')"
                class="w-full text-left px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
              >
                Export as CSV
              </button>
              <button
                @click="handleExport('excel')"
                class="w-full text-left px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
              >
                Export as Excel
              </button>
              <button
                @click="handleExport('json')"
                class="w-full text-left px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
              >
                Export as JSON
              </button>
            </div>
          </div>
        </div>
        
        <!-- Import button -->
        <label class="inline-flex items-center px-3 py-1.5 text-sm border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-md transition-colors cursor-pointer">
          <ArrowUpTrayIcon class="w-4 h-4 mr-1" />
          Import
          <input
            type="file"
            accept=".csv,.xlsx,.json"
            @change="handleImport"
            class="hidden"
          />
        </label>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue'
import {
  PlusIcon,
  DocumentDuplicateIcon,
  TrashIcon,
  ArrowUturnLeftIcon,
  ArrowUturnRightIcon,
  MagnifyingGlassIcon,
  ArrowDownTrayIcon,
  ArrowUpTrayIcon,
  ChevronDownIcon
} from '@heroicons/vue/24/outline'
import type { DataTableRow, DataTableSort, DataTableFilter } from '../types'

interface Props {
  selectedRows: DataTableRow[]
  hasSelection: boolean
  canUndo: boolean
  canRedo: boolean
  filters: DataTableFilter[]
  sorts: DataTableSort[]
}

interface Emits {
  (e: 'add-row'): void
  (e: 'delete-rows'): void
  (e: 'duplicate-rows'): void
  (e: 'export', format: 'csv' | 'excel' | 'json'): void
  (e: 'import', data: any[]): void
  (e: 'undo'): void
  (e: 'redo'): void
  (e: 'clear-filters'): void
  (e: 'clear-sorts'): void
}

const props = defineProps<Props>()
const emit = defineEmits<Emits>()

// Local state
const searchQuery = ref('')
const showExportDropdown = ref(false)
const exportDropdown = ref<HTMLElement>()

// Computed
const activeFilters = computed(() => 
  props.filters.filter(f => f.value !== null && f.value !== '')
)

const activeSorts = computed(() => props.sorts)

// Methods
const toggleExportDropdown = () => {
  showExportDropdown.value = !showExportDropdown.value
}

const handleExport = (format: 'csv' | 'excel' | 'json') => {
  emit('export', format)
  showExportDropdown.value = false
}

const handleImport = (event: Event) => {
  const target = event.target as HTMLInputElement
  const file = target.files?.[0]
  
  if (!file) return
  
  const reader = new FileReader()
  
  reader.onload = (e) => {
    try {
      const content = e.target?.result as string
      let data: any[] = []
      
      if (file.name.endsWith('.json')) {
        data = JSON.parse(content)
      } else if (file.name.endsWith('.csv')) {
        // Parse CSV
        const lines = content.split('\n').filter(line => line.trim())
        const headers = lines[0].split(',').map(h => h.trim())
        
        data = lines.slice(1).map(line => {
          const values = line.split(',').map(v => v.trim())
          const obj: any = {}
          headers.forEach((header, index) => {
            obj[header] = values[index] || ''
          })
          return obj
        })
      }
      
      if (data.length > 0) {
        emit('import', data)
      }
    } catch (error) {
      console.error('Failed to parse file:', error)
      alert('Failed to parse file. Please check the format.')
    }
  }
  
  reader.readAsText(file)
  
  // Reset input
  target.value = ''
}

// Close dropdown when clicking outside
const handleClickOutside = (event: Event) => {
  if (exportDropdown.value && !exportDropdown.value.contains(event.target as Node)) {
    showExportDropdown.value = false
  }
}

onMounted(() => {
  document.addEventListener('click', handleClickOutside)
})

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside)
})
</script>

<style scoped>
/* Ensure proper positioning for dropdowns */
.relative {
  position: relative;
}

/* Animation for dropdown */
.dropdown-enter-active,
.dropdown-leave-active {
  transition: all 0.15s ease;
}

.dropdown-enter-from,
.dropdown-leave-to {
  opacity: 0;
  transform: translateY(-4px);
}
</style>

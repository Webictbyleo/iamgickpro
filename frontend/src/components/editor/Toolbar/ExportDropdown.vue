<template>
  <Menu as="div" class="relative">
    <MenuButton as="template">
      <ModernButton
        variant="primary"
        size="sm"
      >
        <ArrowDownTrayIcon class="w-4 h-4 mr-2" />
        Export
        <ChevronDownIcon class="w-3 h-3 ml-1" />
      </ModernButton>
    </MenuButton>

    <Transition
      enter-active-class="transition duration-100 ease-out"
      enter-from-class="transform scale-95 opacity-0"
      enter-to-class="transform scale-100 opacity-100"
      leave-active-class="transition duration-75 ease-in"
      leave-from-class="transform scale-100 opacity-100"
      leave-to-class="transform scale-95 opacity-0"
    >
      <MenuItems class="absolute right-0 mt-2 w-64 bg-white rounded-xl shadow-xl border border-gray-200 py-2 z-50">
        <!-- Quick Export Options -->
        <div class="px-3 py-2 border-b border-gray-100">
          <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Quick Export</p>
          <div class="grid grid-cols-2 gap-2">
            <MenuItem v-slot="{ active }">
              <button
                @click="handleExport('png')"
                :class="[
                  'flex items-center p-2 rounded-lg text-sm w-full',
                  active ? 'bg-purple-50 text-purple-700' : 'text-gray-700 hover:bg-gray-50'
                ]"
              >
                <div class="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center mr-3">
                  <PhotoIcon class="w-4 h-4 text-blue-600" />
                </div>
                <div class="text-left">
                  <p class="font-medium">PNG</p>
                  <p class="text-xs text-gray-500">High quality</p>
                </div>
              </button>
            </MenuItem>
            
            <MenuItem v-slot="{ active }">
              <button
                @click="handleExport('jpg')"
                :class="[
                  'flex items-center p-2 rounded-lg text-sm w-full',
                  active ? 'bg-purple-50 text-purple-700' : 'text-gray-700 hover:bg-gray-50'
                ]"
              >
                <div class="w-8 h-8 bg-green-100 rounded-lg flex items-center justify-center mr-3">
                  <PhotoIcon class="w-4 h-4 text-green-600" />
                </div>
                <div class="text-left">
                  <p class="font-medium">JPG</p>
                  <p class="text-xs text-gray-500">Smaller size</p>
                </div>
              </button>
            </MenuItem>
          </div>
        </div>

        <!-- All Export Options -->
        <div class="px-3 py-2">
          <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">All Formats</p>
          <div class="space-y-1">
            <MenuItem 
              v-for="format in exportFormats"
              :key="format.value"
              v-slot="{ active }"
            >
              <button
                @click="handleExport(format.value)"
                :class="[
                  'flex items-center justify-between w-full px-3 py-2 text-sm rounded-lg',
                  active ? 'bg-purple-50 text-purple-700' : 'text-gray-700 hover:bg-gray-50'
                ]"
              >
                <div class="flex items-center">
                  <component :is="format.icon" class="w-4 h-4 mr-3 text-gray-400" />
                  <span class="font-medium">{{ format.label }}</span>
                </div>
                <span class="text-xs text-gray-500">{{ format.extension }}</span>
              </button>
            </MenuItem>
          </div>
        </div>

        <!-- Advanced Options -->
        <div class="border-t border-gray-100 px-3 py-2">
          <MenuItem v-slot="{ active }">
            <button
              @click="openAdvancedExport"
              :class="[
                'flex items-center w-full px-3 py-2 text-sm rounded-lg',
                active ? 'bg-purple-50 text-purple-700' : 'text-gray-700 hover:bg-gray-50'
              ]"
            >
              <Cog6ToothIcon class="w-4 h-4 mr-3" />
              <span class="font-medium">Advanced Export Settings</span>
            </button>
          </MenuItem>
        </div>
      </MenuItems>
    </Transition>
  </Menu>
</template>

<script setup lang="ts">
import { Menu, MenuButton, MenuItems, MenuItem } from '@headlessui/vue'
import {
  ArrowDownTrayIcon,
  ChevronDownIcon,
  PhotoIcon,
  DocumentIcon,
  CodeBracketIcon,
  FilmIcon,
  Cog6ToothIcon
} from '@heroicons/vue/24/outline'
import ModernButton from '@/components/common/ModernButton.vue'

const emit = defineEmits<{
  export: [format: string]
  'advanced-export': []
}>()

const exportFormats = [
  { 
    value: 'png', 
    label: 'PNG Image', 
    extension: '.png',
    icon: PhotoIcon
  },
  { 
    value: 'jpg', 
    label: 'JPEG Image', 
    extension: '.jpg',
    icon: PhotoIcon
  },
  { 
    value: 'svg', 
    label: 'SVG Vector', 
    extension: '.svg',
    icon: CodeBracketIcon
  },
  { 
    value: 'pdf', 
    label: 'PDF Document', 
    extension: '.pdf',
    icon: DocumentIcon
  },
  { 
    value: 'gif', 
    label: 'Animated GIF', 
    extension: '.gif',
    icon: FilmIcon
  },
  { 
    value: 'mp4', 
    label: 'MP4 Video', 
    extension: '.mp4',
    icon: FilmIcon
  }
]

const handleExport = (format: string) => {
  emit('export', format)
}

const openAdvancedExport = () => {
  emit('advanced-export')
}
</script>

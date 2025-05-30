<template>
  <div 
    class="bg-white rounded-2xl shadow-sm hover:shadow-lg transition-all duration-200 cursor-pointer group border border-gray-100 hover:border-gray-200 overflow-hidden"
    @click="handleClick"
  >
    <!-- Design Thumbnail -->
    <div class="relative aspect-video bg-gradient-to-br from-gray-100 to-gray-200 overflow-hidden">
      <img 
        v-if="design.thumbnail"
        :src="design.thumbnail"
        :alt="design.title"
        class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
      />
      <div v-else class="w-full h-full flex items-center justify-center">
        <component :is="icons.image" class="w-12 h-12 text-gray-400" />
      </div>
      
      <!-- Actions overlay -->
      <div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-20 transition-all duration-200 flex items-center justify-center opacity-0 group-hover:opacity-100">
        <div class="flex space-x-3">
          <button 
            @click.stop="$emit('edit', design)"
            class="p-3 bg-white/90 backdrop-blur-sm rounded-xl shadow-lg hover:bg-white hover:scale-110 transition-all duration-200"
            title="Edit Design"
          >
            <component :is="icons.edit" class="w-4 h-4 text-gray-700" />
          </button>
          
          <!-- Menu for more actions -->
          <Menu as="div" class="relative">
            <MenuButton 
              @click.stop
              class="p-3 bg-white/90 backdrop-blur-sm rounded-xl shadow-lg hover:bg-white hover:scale-110 transition-all duration-200"
              title="More Actions"
            >
              <component :is="icons.more" class="w-4 h-4 text-gray-700" />
            </MenuButton>
            
            <Transition
              enter-active-class="transition ease-out duration-100"
              enter-from-class="transform opacity-0 scale-95"
              enter-to-class="transform opacity-100 scale-100"
              leave-active-class="transition ease-in duration-75"
              leave-from-class="transform opacity-100 scale-100"
              leave-to-class="transform opacity-0 scale-95"
            >
              <MenuItems class="absolute right-0 mt-2 w-48 origin-top-right bg-white rounded-xl shadow-xl ring-1 ring-black ring-opacity-5 focus:outline-none overflow-hidden z-10">
                <MenuItem v-slot="{ active }">
                  <button
                    @click="$emit('duplicate', design)"
                    :class="[
                      active ? 'bg-gray-50' : '',
                      'group flex items-center w-full px-4 py-3 text-sm text-gray-700'
                    ]"
                  >
                    <component :is="icons.duplicate" class="w-4 h-4 mr-3 text-gray-400" />
                    Duplicate
                  </button>
                </MenuItem>
                <MenuItem v-slot="{ active }">
                  <button
                    @click="$emit('delete', design)"
                    :class="[
                      active ? 'bg-red-50' : '',
                      'group flex items-center w-full px-4 py-3 text-sm text-red-600'
                    ]"
                  >
                    <component :is="icons.delete" class="w-4 h-4 mr-3 text-red-400" />
                    Delete
                  </button>
                </MenuItem>
              </MenuItems>
            </Transition>
          </Menu>
        </div>
      </div>
      
      <!-- Status badge -->
      <div class="absolute top-3 left-3">
        <span class="inline-flex items-center px-2 py-1 rounded-lg text-xs font-medium bg-white/90 backdrop-blur-sm text-gray-700 shadow-sm">
          {{ design.dimensions.width }}×{{ design.dimensions.height }}
        </span>
      </div>
    </div>

    <!-- Design Info -->
    <div class="p-5">
      <div class="flex items-start justify-between">
        <div class="flex-1 min-w-0">
          <h3 class="font-semibold text-gray-900 mb-2 truncate text-base">{{ design.title }}</h3>
          <div class="flex items-center justify-between text-sm text-gray-500">
            <span class="inline-flex items-center">
              <component :is="icons.design" class="w-3 h-3 mr-1" />
              {{ formatDate(design.updatedAt) }}
            </span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { Menu, MenuButton, MenuItems, MenuItem } from '@headlessui/vue'
import type { Design } from '@/types'
import { useIcons } from '@/composables/useIcons'

const icons = useIcons()

interface Props {
  design: Design
}

const props = defineProps<Props>()

const emit = defineEmits<{
  click: [design: Design]
  edit: [design: Design]
  duplicate: [design: Design]
  delete: [design: Design]
}>()

const handleClick = () => {
  emit('click', props.design)
}

const formatDate = (date: string | Date) => {
  const d = new Date(date)
  const now = new Date()
  const diff = now.getTime() - d.getTime()
  const days = Math.floor(diff / (1000 * 60 * 60 * 24))
  
  if (days === 0) return 'Today'
  if (days === 1) return 'Yesterday'
  if (days < 7) return `${days} days ago`
  if (days < 30) return `${Math.floor(days / 7)} weeks ago`
  
  return d.toLocaleDateString()
}
</script>

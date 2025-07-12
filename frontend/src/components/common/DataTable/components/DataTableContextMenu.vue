<template>
  <div
    v-if="visible"
    :style="{ left: `${x}px`, top: `${y}px` }"
    class="context-menu fixed z-50 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded-lg shadow-lg py-1 min-w-48"
    @click.stop
  >
    <template v-for="(item, index) in items" :key="item.key">
      <!-- Separator -->
      <div
        v-if="item.separator"
        class="border-t border-gray-200 dark:border-gray-600 my-1"
      ></div>
      
      <!-- Menu item -->
      <button
        v-else
        @click="handleItemClick(item)"
        :disabled="item.disabled"
        class="w-full text-left px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors flex items-center space-x-2"
      >
        <!-- Icon -->
        <component
          v-if="item.icon"
          :is="getIcon(item.icon)"
          class="w-4 h-4 flex-shrink-0"
        />
        
        <!-- Label -->
        <span>{{ item.label }}</span>
        
        <!-- Keyboard shortcut -->
        <span
          v-if="item.shortcut"
          class="ml-auto text-xs text-gray-400 dark:text-gray-500"
        >
          {{ item.shortcut }}
        </span>
      </button>
      
      <!-- Submenu -->
      <div
        v-if="item.children && item.children.length > 0"
        class="relative group"
      >
        <div
          class="absolute left-full top-0 ml-1 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded-lg shadow-lg py-1 min-w-48 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-150"
        >
          <template v-for="(childItem, childIndex) in item.children" :key="childItem.key">
            <div
              v-if="childItem.separator"
              class="border-t border-gray-200 dark:border-gray-600 my-1"
            ></div>
            
            <button
              v-else
              @click="handleItemClick(childItem)"
              :disabled="childItem.disabled"
              class="w-full text-left px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors flex items-center space-x-2"
            >
              <component
                v-if="childItem.icon"
                :is="getIcon(childItem.icon)"
                class="w-4 h-4 flex-shrink-0"
              />
              <span>{{ childItem.label }}</span>
            </button>
          </template>
        </div>
      </div>
    </template>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import {
  PencilIcon,
  ClipboardIcon,
  ClipboardDocumentIcon,
  PlusIcon,
  DocumentDuplicateIcon,
  TrashIcon,
  ScissorsIcon,
  EyeIcon,
  EyeSlashIcon,
  ArrowUpIcon,
  ArrowDownIcon,
  FunnelIcon,
  Bars3Icon
} from '@heroicons/vue/24/outline'
import type { ContextMenuItem } from '../types'

interface Props {
  visible: boolean
  x: number
  y: number
  target: {
    type: 'cell' | 'row' | 'column' | 'header'
    row?: number
    col?: number
    key?: string
  }
  items: ContextMenuItem[]
}

interface Emits {
  (e: 'close'): void
  (e: 'action', action: string, target: any): void
}

const props = defineProps<Props>()
const emit = defineEmits<Emits>()

// Icon mapping
const iconMap = {
  pencil: PencilIcon,
  clipboard: ClipboardIcon,
  'clipboard-document': ClipboardDocumentIcon,
  plus: PlusIcon,
  duplicate: DocumentDuplicateIcon,
  trash: TrashIcon,
  scissors: ScissorsIcon,
  eye: EyeIcon,
  'eye-slash': EyeSlashIcon,
  'arrow-up': ArrowUpIcon,
  'arrow-down': ArrowDownIcon,
  funnel: FunnelIcon,
  bars: Bars3Icon
}

// Methods
const getIcon = (iconName: string) => {
  return iconMap[iconName as keyof typeof iconMap] || null
}

const handleItemClick = (item: ContextMenuItem) => {
  if (item.disabled) return
  
  if (item.action) {
    item.action()
  } else {
    emit('action', item.key, props.target)
  }
  
  emit('close')
}

// Position adjustment to keep menu in viewport
const adjustedPosition = computed(() => {
  const menuWidth = 192 // min-w-48 = 12rem = 192px
  const menuHeight = props.items.length * 32 // Approximate height
  
  const viewportWidth = window.innerWidth
  const viewportHeight = window.innerHeight
  
  let x = props.x
  let y = props.y
  
  // Adjust horizontal position
  if (x + menuWidth > viewportWidth) {
    x = viewportWidth - menuWidth - 8
  }
  
  // Adjust vertical position
  if (y + menuHeight > viewportHeight) {
    y = viewportHeight - menuHeight - 8
  }
  
  return { x: Math.max(8, x), y: Math.max(8, y) }
})
</script>

<style scoped>
.context-menu {
  animation: contextMenuShow 0.15s ease-out;
}

@keyframes contextMenuShow {
  from {
    opacity: 0;
    transform: scale(0.95) translateY(-4px);
  }
  to {
    opacity: 1;
    transform: scale(1) translateY(0);
  }
}

/* Submenu positioning */
.group:hover .group-hover\\:opacity-100 {
  opacity: 1;
}

.group:hover .group-hover\\:visible {
  visibility: visible;
}

/* Ensure menu stays within viewport */
.context-menu {
  max-height: calc(100vh - 16px);
  overflow-y: auto;
}

/* Custom scrollbar for menu */
.context-menu::-webkit-scrollbar {
  width: 4px;
}

.context-menu::-webkit-scrollbar-track {
  background: transparent;
}

.context-menu::-webkit-scrollbar-thumb {
  background: theme('colors.gray.300');
  border-radius: 2px;
}

.dark .context-menu::-webkit-scrollbar-thumb {
  background: theme('colors.gray.600');
}
</style>

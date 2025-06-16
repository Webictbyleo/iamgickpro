<template>
  <Teleport to="body">
    <div
      v-if="visible"
      ref="overlayRef"
      class="fixed inset-0 z-[9998]"
      @click="handleOverlayClick"
      @contextmenu.prevent
    >
      <Transition
        enter-active-class="transition duration-100 ease-out"
        enter-from-class="transform scale-95 opacity-0"
        enter-to-class="transform scale-100 opacity-100"
        leave-active-class="transition duration-75 ease-in"
        leave-from-class="transform scale-100 opacity-100"
        leave-to-class="transform scale-95 opacity-0"
      >
        <div
          v-if="visible"
          ref="menuRef"
          :style="menuStyle"
          class="absolute bg-white dark:bg-gray-800 rounded-lg shadow-xl border border-gray-200 dark:border-gray-700 py-1 min-w-[200px] max-w-[280px]"
          @click.stop
          @contextmenu.prevent.stop
          @mousedown.stop
        >
          <!-- Context-specific header -->
          <div v-if="targetLayer" class="px-4 py-2 border-b border-gray-100 dark:border-gray-600">
            <div class="flex items-center space-x-2">
              <div class="w-3 h-3 rounded-sm" :class="getLayerTypeColor(targetLayer.type)"></div>
              <span class="text-sm font-medium text-gray-700 dark:text-gray-300 truncate">
                {{ targetLayer.name }}
              </span>
            </div>
            <span class="text-xs text-gray-500 dark:text-gray-400 capitalize">
              {{ targetLayer.type }} Layer
            </span>
          </div>

          <!-- Canvas context header (when no layer) -->
          <div v-else class="px-4 py-2 border-b border-gray-100 dark:border-gray-600">
            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
              Canvas
            </span>
          </div>

          <!-- Basic Actions -->
          <div class="py-1">
            <ContextMenuItem
              v-if="targetLayer"
              icon="DocumentDuplicateIcon"
              label="Copy"
              shortcut="Ctrl+C"
              @click="handleAction('copy')"
            />
            
            <ContextMenuItem
              v-if="hasClipboard"
              icon="ClipboardDocumentIcon"
              label="Paste"
              shortcut="Ctrl+V"
              @click="handleAction('paste')"
            />
            
            <ContextMenuItem
              v-if="targetLayer"
              icon="Square2StackIcon"
              label="Duplicate"
              shortcut="Ctrl+D"
              @click="handleAction('duplicate')"
            />
          </div>
          
          <!-- Arrange Menu - Nested (only for layers) -->
          <template v-if="targetLayer">
            <div class="border-t border-gray-100 dark:border-gray-600"></div>
            <div class="py-1 relative">
              <ContextMenuItem
                icon="Square3Stack3DIcon"
                label="Arrange"
                :has-submenu="true"
                @mouseenter="showArrangeSubmenu = true"
                @mouseleave="scheduleHideArrangeSubmenu"
              />
              
              <!-- Arrange Submenu -->
              <Transition
                enter-active-class="transition duration-100 ease-out"
                enter-from-class="transform scale-95 opacity-0"
                enter-to-class="transform scale-100 opacity-100"
                leave-active-class="transition duration-75 ease-in"
                leave-from-class="transform scale-100 opacity-100"
                leave-to-class="transform scale-95 opacity-0"
              >
                <div
                  v-if="showArrangeSubmenu"
                  :style="submenuStyle"
                  class="absolute bg-white dark:bg-gray-800 rounded-lg shadow-xl border border-gray-200 dark:border-gray-700 py-1 min-w-[160px] z-10"
                  @mouseenter="cancelHideArrangeSubmenu"
                  @mouseleave="scheduleHideArrangeSubmenu"
                  @click.stop
                >
                  <ContextMenuItem
                    icon="ChevronDoubleUpIcon"
                    label="Bring to Front"
                    @click="handleAction('bring-to-front')"
                  />
                  
                  <ContextMenuItem
                    icon="ChevronUpIcon"
                    label="Bring Forward"
                    @click="handleAction('bring-forward')"
                  />
                  
                  <ContextMenuItem
                    icon="ChevronDownIcon"
                    label="Send Backward"
                    @click="handleAction('send-backward')"
                  />
                  
                  <ContextMenuItem
                    icon="ChevronDoubleDownIcon"
                    label="Send to Back"
                    @click="handleAction('send-to-back')"
                  />
                </div>
              </Transition>
            </div>
          </template>

          <!-- Layer Properties (only for layers) -->
          <template v-if="targetLayer">
            <div class="border-t border-gray-100 dark:border-gray-600"></div>
            <div class="py-1">
              <ContextMenuItem
                :icon="targetLayer?.locked ? 'LockOpenIcon' : 'LockClosedIcon'"
                :label="targetLayer?.locked ? 'Unlock' : 'Lock'"
                @click="handleAction('toggle-lock')"
              />
              
              <ContextMenuItem
                :icon="targetLayer?.visible ? 'EyeSlashIcon' : 'EyeIcon'"
                :label="targetLayer?.visible ? 'Hide' : 'Show'"
                @click="handleAction('toggle-visibility')"
              />
            </div>
          </template>

          <!-- Context-specific actions -->
          <template v-if="targetLayer && getContextActions(targetLayer.type).length > 0">
            <div class="border-t border-gray-100 dark:border-gray-600"></div>
            <div class="py-1">
              <ContextMenuItem
                v-for="action in getContextActions(targetLayer.type)"
                :key="action.key"
                :icon="action.icon"
                :label="action.label"
                @click="handleAction('context-action', action.key)"
              />
            </div>
          </template>

          <!-- Canvas-specific actions (when no layer) -->
          <template v-if="!targetLayer">
            <div class="border-t border-gray-100 dark:border-gray-600"></div>
            <div class="py-1">
              <ContextMenuItem
                icon="MagnifyingGlassIcon"
                label="Zoom to Fit"
                @click="handleAction('zoom-to-fit')"
              />
              
              <ContextMenuItem
                icon="ArrowsPointingOutIcon"
                label="Reset Zoom"
                @click="handleAction('reset-zoom')"
              />
            </div>
          </template>
          
          <!-- Destructive Actions (only for layers) -->
          <template v-if="targetLayer">
            <div class="border-t border-gray-100 dark:border-gray-600"></div>
            <div class="py-1">
              <ContextMenuItem
                icon="TrashIcon"
                label="Delete"
                shortcut="Del"
                variant="danger"
                @click="handleAction('delete')"
              />
            </div>
          </template>
        </div>
      </Transition>
    </div>
  </Teleport>
</template>

<script setup lang="ts">
import { computed, ref, watch, onMounted, onUnmounted } from 'vue'
import type { Layer } from '@/types'
import ContextMenuItem from './ContextMenuItem.vue'

interface Props {
  visible: boolean
  position: { x: number; y: number }
  targetLayer?: Layer | null
  hasClipboard?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  visible: false,
  position: () => ({ x: 0, y: 0 }),
  targetLayer: null,
  hasClipboard: false
})

const emit = defineEmits<{
  action: [actionType: string, payload?: any]
  close: []
}>()

const menuRef = ref<HTMLElement>()
const overlayRef = ref<HTMLElement>()
const showArrangeSubmenu = ref(false)
let hideSubmenuTimeout: NodeJS.Timeout | null = null
let openedAt = 0

// Smart positioning with viewport awareness
const menuStyle = computed(() => {
  if (!props.visible) return { display: 'none' }
  
  const padding = 12
  const viewportWidth = window.innerWidth
  const viewportHeight = window.innerHeight
  const menuWidth = 280
  const menuHeight = props.targetLayer ? 450 : 250
  
  let x = props.position.x
  let y = props.position.y
  
  // Adjust horizontal position
  if (x + menuWidth > viewportWidth - padding) {
    x = Math.max(padding, viewportWidth - menuWidth - padding)
  }
  if (x < padding) {
    x = padding
  }
  
  // Adjust vertical position
  if (y + menuHeight > viewportHeight - padding) {
    y = Math.max(padding, viewportHeight - menuHeight - padding)
  }
  if (y < padding) {
    y = padding
  }
  
  return {
    left: `${x}px`,
    top: `${y}px`
  }
})

// Submenu positioning
const submenuStyle = computed(() => {
  const menuWidth = 200
  const submenuWidth = 160
  const viewportWidth = window.innerWidth
  
  // Position submenu to the right by default, or left if would overflow
  let left = menuWidth + 4
  if (props.position.x + menuWidth + submenuWidth > viewportWidth - 8) {
    left = -submenuWidth - 4
  }
  
  return {
    left: `${left}px`,
    top: '0px'
  }
})

// Layer type colors
const getLayerTypeColor = (type: string) => {
  const colors = {
    text: 'bg-blue-500',
    image: 'bg-green-500',
    shape: 'bg-purple-500',
    group: 'bg-orange-500',
    video: 'bg-red-500',
    audio: 'bg-yellow-500'
  }
  return colors[type as keyof typeof colors] || 'bg-gray-500'
}

// Context-specific actions based on layer type
const getContextActions = (layerType: string) => {
  const actions = {
    text: [
      // Removed 'edit-text' - double-clicking is the primary way to edit text
    ],
    image: [
      { key: 'edit-image', label: 'Edit Image', icon: 'PhotoIcon' }
      // Removed 'replace-image' for now to avoid potential issues
    ],
    shape: [
      // Removed 'edit-shape' - properties panel is the primary way to edit shapes
    ],
    group: [
      { key: 'ungroup', label: 'Ungroup', icon: 'Square2StackIcon' }
    ]
  }
  return actions[layerType as keyof typeof actions] || []
}

// Submenu management
const scheduleHideArrangeSubmenu = () => {
  hideSubmenuTimeout = setTimeout(() => {
    showArrangeSubmenu.value = false
  }, 300)
}

const cancelHideArrangeSubmenu = () => {
  if (hideSubmenuTimeout) {
    clearTimeout(hideSubmenuTimeout)
    hideSubmenuTimeout = null
  }
}

// Handle actions
const handleAction = (actionType: string, payload?: any) => {
  emit('action', actionType, payload)
  close()
}

// Handle overlay click to close
const handleOverlayClick = (event: MouseEvent) => {
  // Prevent closing if the menu was just opened (within 100ms)
  if (Date.now() - openedAt < 100) {
    return
  }
  
  // Only close if clicking directly on the overlay, not on the menu
  if (event.target === overlayRef.value) {
    close()
  }
}

// Close the menu
const close = () => {
  showArrangeSubmenu.value = false
  if (hideSubmenuTimeout) {
    clearTimeout(hideSubmenuTimeout)
    hideSubmenuTimeout = null
  }
  emit('close')
}

// Handle escape key
const handleKeydown = (event: KeyboardEvent) => {
  if (event.key === 'Escape' && props.visible) {
    close()
  }
}

// Add/remove event listeners
watch(() => props.visible, (visible) => {
  if (visible) {
    openedAt = Date.now()
    document.addEventListener('keydown', handleKeydown)
  } else {
    document.removeEventListener('keydown', handleKeydown)
    showArrangeSubmenu.value = false
  }
})

// Cleanup
onUnmounted(() => {
  document.removeEventListener('keydown', handleKeydown)
  if (hideSubmenuTimeout) {
    clearTimeout(hideSubmenuTimeout)
  }
})
</script>

<style scoped>
/* Additional styling if needed */
</style>

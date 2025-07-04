<template>
  <div class="relative">
    <!-- Trigger Button -->
    <button
      ref="triggerRef"
      class="w-8 h-8 bg-white dark:bg-gray-800 rounded-full flex items-center justify-center shadow-sm hover:shadow-md border border-gray-200 dark:border-gray-700 transition-all duration-200"
      :class="[
        isOpen 
          ? 'opacity-100 bg-white dark:bg-gray-800 shadow-md border-primary-300 dark:border-primary-600' 
          : 'opacity-0 group-hover:opacity-100',
        triggerClass
      ]"
      @click.stop="toggleDropdown"
    >
      <slot name="trigger">
        <EllipsisVerticalIcon class="w-4 h-4 text-gray-600 dark:text-gray-400" />
      </slot>
    </button>
  </div>

  <!-- Floating Dropdown Portal -->
  <Teleport to="body">
    <div 
      v-if="isOpen"
      ref="dropdownRef"
      class="fixed bg-white dark:bg-gray-800 rounded-lg shadow-xl border border-gray-200 dark:border-gray-700 py-1 z-[9999] transition-all duration-200 ease-out backdrop-blur-sm"
      :class="computedDropdownClass"
      :style="dropdownPosition"
      @click.stop
    >
      <!-- Header Section (Optional) -->
      <div v-if="$slots.header || title" class="px-4 py-3 border-b border-gray-100 dark:border-gray-700 min-w-0">
        <slot name="header">
          <p class="text-sm font-medium text-gray-900 dark:text-gray-100 truncate">{{ title }}</p>
          <p v-if="subtitle" class="text-xs text-gray-500 dark:text-gray-400 mt-1 truncate">{{ subtitle }}</p>
        </slot>
      </div>

      <!-- Actions Section -->
      <div class="py-1 min-w-0">
        <slot name="actions" :close="closeDropdown">
          <button
            v-for="action in actions"
            :key="action.key"
            @click="handleAction(action)"
            :disabled="action.disabled"
            :class="[
              'w-full px-4 py-2 text-left text-sm flex items-center transition-colors disabled:opacity-50 disabled:cursor-not-allowed min-w-0',
              getActionClasses(action)
            ]"
          >
            <component 
              v-if="action.icon" 
              :is="action.icon" 
              class="w-4 h-4 mr-3 flex-shrink-0" 
              :class="getActionIconClasses(action)"
            />
            <span class="flex-1 truncate">{{ action.label }}</span>
            <span v-if="action.shortcut" class="text-xs text-gray-400 dark:text-gray-500 ml-2 flex-shrink-0">{{ action.shortcut }}</span>
          </button>
        </slot>
      </div>

      <!-- Footer Section (Optional) -->
      <div v-if="$slots.footer" class="px-4 py-3 border-t border-gray-100 dark:border-gray-700 min-w-0">
        <slot name="footer" :close="closeDropdown" />
      </div>
    </div>
  </Teleport>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted, nextTick, watch } from 'vue'
import { EllipsisVerticalIcon } from '@heroicons/vue/24/outline'

// Types
export interface DropdownAction {
  key: string
  label: string
  icon?: any
  variant?: 'default' | 'primary' | 'danger' | 'warning' | 'success'
  disabled?: boolean
  shortcut?: string
  separator?: boolean // Add separator before this action
}

interface Props {
  actions?: DropdownAction[]
  title?: string
  subtitle?: string
  position?: 'auto' | 'bottom-left' | 'bottom-right' | 'top-left' | 'top-right'
  width?: string
  maxWidth?: string
  triggerClass?: string
  dropdownClass?: string
  autoClose?: boolean
  disabled?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  actions: () => [],
  position: 'auto',
  width: 'w-48',
  autoClose: true,
  disabled: false
})

// Emits
interface Emits {
  (e: 'action', action: DropdownAction): void
  (e: 'open'): void
  (e: 'close'): void
}

const emit = defineEmits<Emits>()

// Refs
const triggerRef = ref<HTMLElement>()
const dropdownRef = ref<HTMLElement>()
const isOpen = ref(false)
const dropdownPosition = ref({ top: '0px', left: '0px' })

// Computed
const computedDropdownClass = computed(() => {
  const classes = [props.width]
  if (props.maxWidth) classes.push(props.maxWidth)
  if (props.dropdownClass) classes.push(props.dropdownClass)
  return classes.join(' ')
})

// Methods
const toggleDropdown = () => {
  if (props.disabled) return
  
  if (isOpen.value) {
    closeDropdown()
  } else {
    openDropdown()
  }
}

const openDropdown = () => {
  isOpen.value = true
  emit('open')
  nextTick(() => {
    positionDropdown()
  })
}

const closeDropdown = () => {
  isOpen.value = false
  emit('close')
}

const handleAction = (action: DropdownAction) => {
  if (action.disabled) return
  
  emit('action', action)
  
  if (props.autoClose) {
    closeDropdown()
  }
}

const getActionClasses = (action: DropdownAction): string => {
  const baseClasses = 'hover:bg-gray-50 dark:hover:bg-gray-700'
  
  switch (action.variant) {
    case 'danger':
      return `text-danger-600 dark:text-danger-400 hover:bg-danger-50 dark:hover:bg-danger-900/20 ${baseClasses}`
    case 'warning':
      return `text-warning-600 dark:text-warning-400 hover:bg-warning-50 dark:hover:bg-warning-900/20 ${baseClasses}`
    case 'success':
      return `text-success-600 dark:text-success-400 hover:bg-success-50 dark:hover:bg-success-900/20 ${baseClasses}`
    case 'primary':
      return `text-primary-600 dark:text-primary-400 hover:bg-primary-50 dark:hover:bg-primary-900/20 ${baseClasses}`
    default:
      return `text-gray-700 dark:text-gray-300 ${baseClasses}`
  }
}

const getActionIconClasses = (action: DropdownAction): string => {
  switch (action.variant) {
    case 'danger':
      return 'text-danger-500 dark:text-danger-400'
    case 'warning':
      return 'text-warning-500 dark:text-warning-400'
    case 'success':
      return 'text-success-500 dark:text-success-400'
    case 'primary':
      return 'text-primary-500 dark:text-primary-400'
    default:
      return 'text-gray-400 dark:text-gray-500'
  }
}

const positionDropdown = () => {
  if (!triggerRef.value || !dropdownRef.value) return

  const calculatePosition = () => {
    const triggerRect = triggerRef.value!.getBoundingClientRect()
    const dropdownEl = dropdownRef.value!
    const windowWidth = window.innerWidth
    const windowHeight = window.innerHeight
    
    // Get actual dropdown dimensions
    const dropdownWidth = dropdownEl.offsetWidth || 192 // fallback to w-48
    const dropdownHeight = dropdownEl.offsetHeight || 150 // fallback estimation
    
    let left: number
    let top: number
    
    if (props.position === 'auto') {
      // Smart positioning based on available space
      
      // Horizontal positioning - prefer right alignment
      left = triggerRect.right - dropdownWidth
      
      // Adjust if dropdown would overflow left
      if (left < 16) {
        left = triggerRect.left
      }
      
      // Adjust if dropdown would overflow right
      if (left + dropdownWidth > windowWidth - 16) {
        left = windowWidth - dropdownWidth - 16
      }
      
      // Vertical positioning - prefer below trigger
      top = triggerRect.bottom + 4
      
      // Position above if dropdown would overflow below
      if (top + dropdownHeight > windowHeight - 16) {
        top = triggerRect.top - dropdownHeight - 4
      }
      
      // Ensure dropdown doesn't go above viewport
      if (top < 16) {
        top = Math.min(triggerRect.bottom + 4, windowHeight - dropdownHeight - 16)
      }
    } else {
      // Fixed positioning based on position prop
      switch (props.position) {
        case 'bottom-right':
          left = triggerRect.left
          top = triggerRect.bottom + 4
          break
        case 'bottom-left':
          left = triggerRect.right - dropdownWidth
          top = triggerRect.bottom + 4
          break
        case 'top-right':
          left = triggerRect.left
          top = triggerRect.top - dropdownHeight - 4
          break
        case 'top-left':
          left = triggerRect.right - dropdownWidth
          top = triggerRect.top - dropdownHeight - 4
          break
        default:
          left = triggerRect.right - dropdownWidth
          top = triggerRect.bottom + 4
      }
    }
    
    return {
      top: `${Math.round(top)}px`,
      left: `${Math.round(left)}px`
    }
  }

  // Set initial position
  dropdownPosition.value = calculatePosition()
  
  // Fine-tune position after dropdown is fully rendered
  nextTick(() => {
    dropdownPosition.value = calculatePosition()
  })
}

// Handle click outside to close dropdown
const handleClickOutside = (event: Event) => {
  if (!isOpen.value) return
  
  const target = event.target as HTMLElement
  
  // Check if click is on the trigger button
  if (triggerRef.value && triggerRef.value.contains(target)) {
    return
  }
  
  // Check if click is on the dropdown itself
  if (dropdownRef.value && dropdownRef.value.contains(target)) {
    return
  }
  
  closeDropdown()
}

// Handle escape key
const handleEscapeKey = (event: KeyboardEvent) => {
  if (event.key === 'Escape' && isOpen.value) {
    closeDropdown()
  }
}

// Handle window resize
const handleWindowResize = () => {
  if (isOpen.value) {
    nextTick(() => {
      positionDropdown()
    })
  }
}

// Lifecycle
onMounted(() => {
  document.addEventListener('click', handleClickOutside)
  document.addEventListener('keydown', handleEscapeKey)
  window.addEventListener('resize', handleWindowResize)
})

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside)
  document.removeEventListener('keydown', handleEscapeKey)
  window.removeEventListener('resize', handleWindowResize)
})

// Watch for position changes (e.g., when parent scrolls)
watch(isOpen, (newValue) => {
  if (newValue) {
    // Reposition when opened
    nextTick(() => {
      positionDropdown()
    })
  }
})

// Expose methods for parent component
defineExpose({
  open: openDropdown,
  close: closeDropdown,
  toggle: toggleDropdown,
  isOpen: computed(() => isOpen.value)
})
</script>

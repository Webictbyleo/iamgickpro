<template>
  <div class="relative" ref="dropdownRef">
    <!-- Trigger Button -->
    <button
      @click="toggleDropdown"
      :class="[
        'inline-flex items-center justify-center p-2 rounded-lg transition-all duration-200',
        'hover:bg-gray-100 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-violet-500 focus:ring-offset-1',
        isOpen ? 'bg-gray-100 dark:bg-gray-700' : 'bg-transparent',
        buttonClass
      ]"
      :title="tooltip"
      :disabled="disabled"
    >
      <!-- Icon -->
      <component 
        :is="icon" 
        :class="[
          'transition-colors duration-200',
          iconClass,
          disabled ? 'text-gray-400' : 'text-gray-600 dark:text-gray-300'
        ]"
      />
      
      <!-- Optional text -->
      <span 
        v-if="text"
        :class="[
          'ml-2 text-sm font-medium transition-colors duration-200',
          disabled ? 'text-gray-400' : 'text-gray-700 dark:text-gray-300'
        ]"
      >
        {{ text }}
      </span>
      
      <!-- Optional chevron -->
      <ChevronDownIcon 
        v-if="showChevron"
        :class="[
          'w-3 h-3 ml-1 transition-transform duration-200',
          isOpen ? 'rotate-180' : '',
          disabled ? 'text-gray-400' : 'text-gray-500'
        ]"
      />
    </button>

    <!-- Dropdown Content -->
    <Transition
      enter-active-class="transition ease-out duration-100"
      enter-from-class="transform opacity-0 scale-95"
      enter-to-class="transform opacity-100 scale-100"
      leave-active-class="transition ease-in duration-75"
      leave-from-class="transform opacity-100 scale-100"
      leave-to-class="transform opacity-0 scale-95"
    >
      <div
        v-if="isOpen"
        :class="[
          'absolute z-50 bg-white dark:bg-gray-800 rounded-xl shadow-xl border border-gray-200 dark:border-gray-700',
          'backdrop-blur-xl',
          getPlacementClasses(),
          width
        ]"
      >
        <slot :close="closeDropdown" />
      </div>
    </Transition>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { ChevronDownIcon } from '@heroicons/vue/24/outline'

interface Props {
  icon: any // Vue component for the icon
  text?: string // Optional text to show alongside icon
  tooltip?: string
  placement?: 'bottom-start' | 'bottom-end' | 'top-start' | 'top-end'
  width?: string
  showChevron?: boolean
  buttonClass?: string
  iconClass?: string
  disabled?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  text: '',
  tooltip: '',
  placement: 'bottom-start',
  width: 'w-64',
  showChevron: false,
  buttonClass: '',
  iconClass: 'w-5 h-5',
  disabled: false
})

const emit = defineEmits<{
  open: []
  close: []
}>()

const isOpen = ref(false)
const dropdownRef = ref<HTMLElement>()

const getPlacementClasses = () => {
  const placementMap = {
    'bottom-start': 'mt-2 left-0 origin-top-left',
    'bottom-end': 'mt-2 right-0 origin-top-right', 
    'top-start': 'mb-2 left-0 bottom-full origin-bottom-left',
    'top-end': 'mb-2 right-0 bottom-full origin-bottom-right'
  }
  return placementMap[props.placement]
}

const toggleDropdown = () => {
  if (props.disabled) return
  
  isOpen.value = !isOpen.value
  if (isOpen.value) {
    emit('open')
  } else {
    emit('close')
  }
}

const closeDropdown = () => {
  isOpen.value = false
  emit('close')
}

const handleClickOutside = (event: Event) => {
  if (dropdownRef.value && !dropdownRef.value.contains(event.target as Node)) {
    closeDropdown()
  }
}

const handleEscapeKey = (event: KeyboardEvent) => {
  if (event.key === 'Escape' && isOpen.value) {
    closeDropdown()
  }
}

onMounted(() => {
  document.addEventListener('click', handleClickOutside)
  document.addEventListener('keydown', handleEscapeKey)
})

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside)
  document.removeEventListener('keydown', handleEscapeKey)
})
</script>

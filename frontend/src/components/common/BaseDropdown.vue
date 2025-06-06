<template>
  <div class="relative inline-block text-left" ref="dropdownRef">
    <div>
      <button
        @click="toggleDropdown"
        :class="triggerClasses"
        :aria-expanded="isOpen"
        aria-haspopup="true"
      >
        <slot name="trigger">
          <span>{{ triggerText }}</span>
          <svg
            class="ml-2 h-4 w-4 transition-transform"
            :class="{ 'rotate-180': isOpen }"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
          </svg>
        </slot>
      </button>
    </div>

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
        :class="dropdownClasses"
        role="menu"
        aria-orientation="vertical"
      >
        <div class="py-1" role="none">
          <slot :close="closeDropdown" />
        </div>
      </div>
    </Transition>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue'

interface Props {
  triggerText?: string
  placement?: 'bottom-start' | 'bottom-end' | 'top-start' | 'top-end'
  triggerClasses?: string
  width?: string
}

const props = withDefaults(defineProps<Props>(), {
  triggerText: 'Options',
  placement: 'bottom-start',
  triggerClasses: 'inline-flex justify-center w-full rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500',
  width: 'w-56'
})

const emit = defineEmits<{
  open: []
  close: []
}>()

const isOpen = ref(false)
const dropdownRef = ref<HTMLElement>()

const dropdownClasses = computed(() => {
  const placementClasses = {
    'bottom-start': 'origin-top-left left-0 mt-2',
    'bottom-end': 'origin-top-right right-0 mt-2',
    'top-start': 'origin-bottom-left left-0 mb-2 bottom-full',
    'top-end': 'origin-bottom-right right-0 mb-2 bottom-full'
  }

  return [
    'absolute z-50 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5',
    'focus:outline-none',
    placementClasses[props.placement],
    props.width
  ].join(' ')
})

const toggleDropdown = () => {
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

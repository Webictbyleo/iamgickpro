<template>
  <div class="custom-scrollbar-container" ref="containerRef">
    <!-- Content area with hidden scrollbar -->
    <div 
      ref="scrollAreaRef"
      class="scroll-area"
      :class="scrollAreaClass"
      @scroll="handleScroll"
    >
      <slot />
    </div>
    
    <!-- Custom scrollbar track (overlay) -->
    <div 
      v-show="showScrollbar"
      class="scrollbar-track"
      :class="trackClass"
      ref="trackRef"
      @mousedown="handleTrackClick"
    >
      <!-- Scrollbar thumb -->
      <div 
        ref="thumbRef"
        class="scrollbar-thumb"
        :class="thumbClass"
        :style="thumbStyle"
        @mousedown="handleThumbMouseDown"
      ></div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted, nextTick } from 'vue'

interface Props {
  scrollAreaClass?: string
  trackClass?: string
  thumbClass?: string
  trackWidth?: number
  thumbMinHeight?: number
  hideDelay?: number
}

const props = withDefaults(defineProps<Props>(), {
  scrollAreaClass: '',
  trackClass: '',
  thumbClass: '',
  trackWidth: 8,
  thumbMinHeight: 20,
  hideDelay: 1000
})

const emit = defineEmits<{
  scroll: [{ scrollTop: number; scrollHeight: number; clientHeight: number }]
}>()

// Refs
const containerRef = ref<HTMLElement>()
const scrollAreaRef = ref<HTMLElement>()
const trackRef = ref<HTMLElement>()
const thumbRef = ref<HTMLElement>()

// State
const showScrollbar = ref(false)
const thumbHeight = ref(0)
const thumbTop = ref(0)
const isDragging = ref(false)
const dragStartY = ref(0)
const dragStartScrollTop = ref(0)

// Computed styles
const thumbStyle = computed(() => ({
  height: `${thumbHeight.value}px`,
  top: `${thumbTop.value}px`,
  transform: isDragging.value ? 'scaleX(1.2)' : 'scaleX(1)',
}))

// Update scrollbar visibility and thumb position
const updateScrollbar = () => {
  if (!scrollAreaRef.value || !trackRef.value) return

  const { scrollTop, scrollHeight, clientHeight } = scrollAreaRef.value
  
  // Show scrollbar only if content is scrollable
  const isScrollable = scrollHeight > clientHeight
  showScrollbar.value = isScrollable

  if (isScrollable) {
    const trackHeight = trackRef.value.clientHeight
    const scrollRatio = scrollTop / (scrollHeight - clientHeight)
    const visibleRatio = clientHeight / scrollHeight
    
    // Calculate thumb height (minimum specified height)
    thumbHeight.value = Math.max(props.thumbMinHeight, trackHeight * visibleRatio)
    
    // Calculate thumb position
    const maxThumbTop = trackHeight - thumbHeight.value
    thumbTop.value = scrollRatio * maxThumbTop
  }
}

// Handle scroll events
const handleScroll = () => {
  if (!scrollAreaRef.value) return
  
  const { scrollTop, scrollHeight, clientHeight } = scrollAreaRef.value
  
  updateScrollbar()
  
  // Emit scroll event
  emit('scroll', { scrollTop, scrollHeight, clientHeight })
}

// Handle track click (jump to position)
const handleTrackClick = (event: MouseEvent) => {
  if (!scrollAreaRef.value || !trackRef.value || isDragging.value) return

  const trackRect = trackRef.value.getBoundingClientRect()
  const clickY = event.clientY - trackRect.top
  const trackHeight = trackRef.value.clientHeight
  
  const { scrollHeight, clientHeight } = scrollAreaRef.value
  const scrollRatio = clickY / trackHeight
  const newScrollTop = scrollRatio * (scrollHeight - clientHeight)
  
  scrollAreaRef.value.scrollTop = Math.max(0, Math.min(newScrollTop, scrollHeight - clientHeight))
}

// Handle thumb drag start
const handleThumbMouseDown = (event: MouseEvent) => {
  if (!scrollAreaRef.value) return

  isDragging.value = true
  dragStartY.value = event.clientY
  dragStartScrollTop.value = scrollAreaRef.value.scrollTop

  // Add global listeners
  document.addEventListener('mousemove', handleThumbMouseMove)
  document.addEventListener('mouseup', handleThumbMouseUp)
  
  // Prevent text selection
  event.preventDefault()
  event.stopPropagation()
}

// Handle thumb dragging
const handleThumbMouseMove = (event: MouseEvent) => {
  if (!isDragging.value || !scrollAreaRef.value || !trackRef.value) return

  const deltaY = event.clientY - dragStartY.value
  const trackHeight = trackRef.value.clientHeight
  const { scrollHeight, clientHeight } = scrollAreaRef.value
  
  // Calculate new scroll position
  const scrollRange = scrollHeight - clientHeight
  const thumbRange = trackHeight - thumbHeight.value
  const scrollDelta = (deltaY / thumbRange) * scrollRange
  const newScrollTop = dragStartScrollTop.value + scrollDelta
  
  // Apply scroll position
  scrollAreaRef.value.scrollTop = Math.max(0, Math.min(newScrollTop, scrollRange))
}

// Handle thumb drag end
const handleThumbMouseUp = () => {
  isDragging.value = false
  
  // Remove global listeners
  document.removeEventListener('mousemove', handleThumbMouseMove)
  document.removeEventListener('mouseup', handleThumbMouseUp)
}

// Initialize scrollbar on mount
onMounted(() => {
  nextTick(() => {
    updateScrollbar()
  })
})

// Cleanup on unmount
onUnmounted(() => {
  document.removeEventListener('mousemove', handleThumbMouseMove)
  document.removeEventListener('mouseup', handleThumbMouseUp)
})

// Expose methods for parent component
defineExpose({
  updateScrollbar,
  scrollToTop: () => {
    if (scrollAreaRef.value) {
      scrollAreaRef.value.scrollTop = 0
    }
  },
  scrollToBottom: () => {
    if (scrollAreaRef.value) {
      scrollAreaRef.value.scrollTop = scrollAreaRef.value.scrollHeight
    }
  }
})
</script>

<style scoped>
.custom-scrollbar-container {
  position: relative;
  height: 100%;
  width: 100%;
}

.scroll-area {
  height: 100%;
  width: 100%;
  overflow-y: auto;
  overflow-x: hidden;
  
  /* Hide native scrollbar */
  scrollbar-width: none; /* Firefox */
  -ms-overflow-style: none; /* IE/Edge */
}

.scroll-area::-webkit-scrollbar {
  display: none; /* Chrome/Safari/Opera */
}

.scrollbar-track {
  position: absolute;
  right: 2px;
  top: 2px;
  bottom: 2px;
  width: 8px;
  background: transparent;
  border-radius: 4px;
  z-index: 100;
  opacity: 0;
  transition: opacity 0.3s ease;
  cursor: pointer;
}

.custom-scrollbar-container:hover .scrollbar-track {
  opacity: 1;
}

.scrollbar-thumb {
  position: absolute;
  left: 0;
  right: 0;
  background: rgba(0, 0, 0, 0.3);
  border-radius: 4px;
  cursor: pointer;
  transition: all 0.2s ease;
  min-height: 20px;
}

.scrollbar-thumb:hover {
  background: rgba(0, 0, 0, 0.5);
}

.scrollbar-thumb:active {
  background: rgba(0, 0, 0, 0.7);
}

/* Dark mode */
.dark .scrollbar-thumb {
  background: rgba(255, 255, 255, 0.3);
}

.dark .scrollbar-thumb:hover {
  background: rgba(255, 255, 255, 0.5);
}

.dark .scrollbar-thumb:active {
  background: rgba(255, 255, 255, 0.7);
}

/* Smooth scrolling */
.scroll-area {
  scroll-behavior: smooth;
}
</style>

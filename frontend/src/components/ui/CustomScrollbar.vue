<template>
  <div class="custom-scrollbar-container" ref="containerRef">
    <!-- Content area with hidden scrollbar -->
    <div 
      ref="scrollAreaRef"
      class="scroll-area"
      :class="scrollAreaClass"
      @scroll="handleScroll"
      @mouseenter="handleMouseEnter"
      @mouseleave="handleMouseLeave"
    >
      <slot />
    </div>
    
    <!-- Custom scrollbar track (overlay) -->
    <div 
      v-show="showScrollbar"
      class="scrollbar-track"
      :class="[trackClass, { 'scrollbar-visible': isHovered || isDragging }]"
      :style="trackStyle"
      ref="trackRef"
      @mousedown="handleTrackClick"
      @mouseenter="handleMouseEnter"
      @mouseleave="handleMouseLeave"
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
const isHovered = ref(false)

// Performance optimization: throttle scroll updates
let scrollUpdateTimeoutId: number | null = null
let resizeObserver: ResizeObserver | null = null

// Computed styles with performance optimizations
const thumbStyle = computed(() => ({
  height: `${thumbHeight.value}px`,
  top: `${thumbTop.value}px`,
  transform: isDragging.value ? 'scaleX(1.2)' : 'scaleX(1)',
  // Use transform instead of opacity for better performance
  willChange: isDragging.value ? 'transform' : 'auto'
}))

// Computed track style to use the trackWidth prop
const trackStyle = computed(() => ({
  width: `${props.trackWidth}px`,
  right: '2px',
  // Use transform for GPU acceleration
  transform: 'translateZ(0)'
}))

// Throttled update function for better performance
const throttledUpdateScrollbar = () => {
  if (scrollUpdateTimeoutId) {
    return
  }
  
  scrollUpdateTimeoutId = window.requestAnimationFrame(() => {
    updateScrollbar()
    scrollUpdateTimeoutId = null
  })
}

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

// Handle scroll events with throttling
const handleScroll = () => {
  if (!scrollAreaRef.value) return
  
  const { scrollTop, scrollHeight, clientHeight } = scrollAreaRef.value
  
  throttledUpdateScrollbar()
  
  // Emit scroll event (also throttled)
  emit('scroll', { scrollTop, scrollHeight, clientHeight })
}

// Handle mouse enter/leave for better visibility control
const handleMouseEnter = () => {
  isHovered.value = true
}

const handleMouseLeave = () => {
  if (!isDragging.value) {
    isHovered.value = false
  }
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
  isHovered.value = false
  
  // Remove global listeners
  document.removeEventListener('mousemove', handleThumbMouseMove)
  document.removeEventListener('mouseup', handleThumbMouseUp)
}

// Setup ResizeObserver for automatic updates when content changes
const setupResizeObserver = () => {
  if (!scrollAreaRef.value) return
  
  resizeObserver = new ResizeObserver(() => {
    throttledUpdateScrollbar()
  })
  
  resizeObserver.observe(scrollAreaRef.value)
}

// Initialize scrollbar on mount
onMounted(() => {
  nextTick(() => {
    updateScrollbar()
    setupResizeObserver()
  })
})

// Cleanup on unmount
onUnmounted(() => {
  document.removeEventListener('mousemove', handleThumbMouseMove)
  document.removeEventListener('mouseup', handleThumbMouseUp)
  
  if (scrollUpdateTimeoutId) {
    cancelAnimationFrame(scrollUpdateTimeoutId)
  }
  
  if (resizeObserver) {
    resizeObserver.disconnect()
  }
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
  
  /* GPU acceleration */
  transform: translateZ(0);
  backface-visibility: hidden;
}

.scroll-area::-webkit-scrollbar {
  display: none; /* Chrome/Safari/Opera */
}

.scrollbar-track {
  position: absolute;
  top: 2px;
  bottom: 2px;
  background: transparent;
  border-radius: 4px;
  z-index: 100;
  opacity: 0;
  transition: opacity 0.2s ease;
  cursor: pointer;
  
  /* GPU acceleration */
  transform: translateZ(0);
  will-change: opacity;
}

.scrollbar-track.scrollbar-visible {
  opacity: 1;
}

.scrollbar-thumb {
  position: absolute;
  left: 0;
  right: 0;
  border-radius: 4px;
  cursor: pointer;
  transition: all 0.15s ease;
  min-height: 20px;
  
  /* Better visibility in light mode */
  background: rgb(156 163 175 / 0.7); /* gray-400 with opacity */
  
  /* GPU acceleration */
  transform: translateZ(0);
  will-change: transform, background-color;
}

.scrollbar-thumb:hover {
  background: rgb(107 114 128 / 0.8); /* gray-500 with opacity */
  transform: scaleX(1.1);
}

.scrollbar-thumb:active {
  background: rgb(75 85 99 / 0.9); /* gray-600 with opacity */
  transform: scaleX(1.2);
}

/* Dark mode - improved visibility */
.dark .scrollbar-thumb {
  background: rgb(229 231 235 / 0.4); /* gray-200 with opacity */
}

.dark .scrollbar-thumb:hover {
  background: rgb(229 231 235 / 0.6); /* gray-200 with opacity */
}

.dark .scrollbar-thumb:active {
  background: rgb(229 231 235 / 0.8); /* gray-200 with opacity */
}

/* Smooth scrolling with better performance */
.scroll-area {
  scroll-behavior: smooth;
}

/* Reduce repaints by containing layout changes */
.custom-scrollbar-container {
  contain: layout style paint;
}

.scrollbar-track {
  contain: layout style paint;
}

.scrollbar-thumb {
  contain: layout style paint;
}

/* Better visual feedback for active states */
.scrollbar-track:active .scrollbar-thumb {
  background: rgb(107 114 128 / 0.9); /* gray-500 with higher opacity */
}

.dark .scrollbar-track:active .scrollbar-thumb {
  background: rgb(229 231 235 / 0.9); /* gray-200 with higher opacity */
}
</style>

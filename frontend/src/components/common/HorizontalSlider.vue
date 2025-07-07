<template>
  <div v-if="items.length > 0" class="horizontal-slider">
    <div class="slider-wrapper">
      <!-- Left Navigation Arrow -->
      <button
        v-if="showArrows && showLeftArrow && canScrollLeft"
        @click="scrollLeft"
        class="nav-arrow nav-arrow-le.nav-arrow-left {
  left: 0.25rem;
}

.nav-arrow-right {
  right: 0.25rem;
}w-active"
        :class="arrowSize === 'sm' ? 'arrow-sm' : 'arrow-md'"
        aria-label="Scroll left"
      >
        <ChevronLeftIcon :class="arrowSize === 'sm' ? 'w-3 h-3' : 'w-4 h-4'" />
      </button>

      <!-- Scrollable Content Area -->
      <div class="content-wrapper" :class="{ 'with-arrows': showArrows && (showLeftArrow || showRightArrow) }">
        <!-- Left Edge Gradient -->
        <button 
          v-if="showGradients && canScrollLeft"
          @click="scrollLeft"
          class="edge-gradient left-gradient bg-gradient-to-r from-gray-100/90 via-gray-50/60 to-transparent dark:from-black/95 dark:via-black/70 dark:to-transparent"
          aria-label="Scroll left"
        ></button>
        
        <!-- Right Edge Gradient -->
        <button 
          v-if="showGradients && canScrollRight"
          @click="scrollRight"
          class="edge-gradient right-gradient bg-gradient-to-l from-gray-100/90 via-gray-50/60 to-transparent dark:from-black/95 dark:via-black/70 dark:to-transparent"
          aria-label="Scroll right"
        ></button>
        
        <!-- Items Container -->
        <div 
          ref="scrollContainer"
          class="items-container"
          :class="gapClass"
          @scroll="handleScroll"
        >
          <slot 
            v-for="(item, index) in items" 
            :key="getItemKey(item, index)"
            :item="item" 
            :index="index"
            name="item"
          >
            <!-- Default Item Slot -->
            <div class="default-item">
              <span class="item-label">{{ index + 1 }}</span>
            </div>
          </slot>
        </div>
      </div>

      <!-- Right Navigation Arrow -->
      <button
        v-if="showArrows && showRightArrow && canScrollRight"
        @click="scrollRight"
        class="nav-arrow nav-arrow-right arrow-active"
        :class="arrowSize === 'sm' ? 'arrow-sm' : 'arrow-md'"
        aria-label="Scroll right"
      >
        <ChevronRightIcon :class="arrowSize === 'sm' ? 'w-3 h-3' : 'w-4 h-4'" />
      </button>
    </div>

    <!-- View All Text -->
    <div v-if="showViewAll && hasMoreItems" class="view-all-section">
      <button
        @click="handleViewAll"
        class="view-all-text"
      >
        <span>{{ viewAllText || `View All (${totalItems})` }}</span>
        <ArrowRightIcon class="w-3 h-3" />
      </button>
    </div>
  </div>

  <!-- Empty State -->
  <div v-else-if="showEmptyState" class="empty-state">
    <slot name="empty">
      <div class="empty-icon">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
        </svg>
      </div>
      <p class="empty-text">No items available</p>
    </slot>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, nextTick, watch } from 'vue'
import { ChevronLeftIcon, ChevronRightIcon, ArrowRightIcon } from '@heroicons/vue/24/outline'

interface Props {
  items: any[]
  totalItems?: number
  scrollAmount?: number
  gap?: 'sm' | 'md' | 'lg'
  minItemsForArrows?: number
  showArrows?: boolean
  showViewAll?: boolean
  showEmptyState?: boolean
  showGradients?: boolean
  viewAllText?: string
  keyField?: string
  arrowSize?: 'sm' | 'md'
  itemsPerView?: number
}

const props = withDefaults(defineProps<Props>(), {
  items: () => [],
  totalItems: 0,
  scrollAmount: 240,
  gap: 'md',
  minItemsForArrows: 3,
  showArrows: true,
  showViewAll: true,
  showEmptyState: true,
  showGradients: true,
  viewAllText: '',
  keyField: 'id',
  arrowSize: 'sm',
  itemsPerView: 0
})

const emit = defineEmits<{
  'viewAll': []
}>()

// Refs
const scrollContainer = ref<HTMLElement>()
const canScrollLeft = ref(false)
const canScrollRight = ref(false)

// Computed
const hasMoreItems = computed(() => {
  const total = props.totalItems || props.items.length
  return total > props.items.length
})

const showLeftArrow = computed(() => {
  return props.items.length >= props.minItemsForArrows
})

const showRightArrow = computed(() => {
  return props.items.length >= props.minItemsForArrows
})

const gapClass = computed(() => {
  const gaps = {
    'sm': 'gap-2',
    'md': 'gap-3', 
    'lg': 'gap-4'
  }
  return gaps[props.gap]
})

// Methods
const getItemKey = (item: any, index: number): string | number => {
  if (props.keyField && item[props.keyField]) {
    return item[props.keyField]
  }
  return index
}

const handleScroll = () => {
  if (!scrollContainer.value) return
  
  const el = scrollContainer.value
  const scrollTolerance = 2
  
  canScrollLeft.value = el.scrollLeft > scrollTolerance
  canScrollRight.value = el.scrollLeft < (el.scrollWidth - el.clientWidth - scrollTolerance)
}

const scrollLeft = () => {
  if (!scrollContainer.value) return
  
  scrollContainer.value.scrollBy({
    left: -props.scrollAmount,
    behavior: 'smooth'
  })
  
  // Update scroll state after animation
  setTimeout(handleScroll, 300)
}

const scrollRight = () => {
  if (!scrollContainer.value) return
  
  scrollContainer.value.scrollBy({
    left: props.scrollAmount,
    behavior: 'smooth'
  })
  
  // Update scroll state after animation
  setTimeout(handleScroll, 300)
}

const handleViewAll = () => {
  emit('viewAll')
}

// Lifecycle
onMounted(() => {
  nextTick(() => {
    handleScroll()
  })
})

// Watchers
watch(() => props.items, () => {
  nextTick(() => {
    handleScroll()
  })
}, { deep: true })
</script>

<style scoped>
/* Main Container */
.horizontal-slider {
  position: relative;
  width: 100%;
}

/* Slider Wrapper */
.slider-wrapper {
  position: relative;
  display: flex;
  align-items: center;
}

/* Content Wrapper */
.content-wrapper {
  position: relative;
  flex: 1;
  overflow: hidden;
}

/* Navigation Arrows */
.nav-arrow {
  position: absolute;
  z-index: 30;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  border: none;
  background: none;
  transition: all 0.2s ease;
  backdrop-filter: none;
  -webkit-backdrop-filter: none;
  box-shadow: none;
}

.arrow-sm {
  width: 0.75rem;
  height: 0.75rem;
}

.arrow-md {
  width: 1.5rem;
  height: 1.5rem;
}

.arrow-active {
  color: rgb(107 114 128);
  cursor: pointer;
}

.arrow-active:hover {
  color: rgb(75 85 99);
  transform: scale(1.1);
}

.nav-arrow-left {
  left: 0;
}

.nav-arrow-right {
  right: 0;
}

/* Edge Gradients */
.edge-gradient {
  position: absolute;
  top: 0;
  bottom: 0;
  width: 1.25rem;
  z-index: 20;
  border: none;
  transition: opacity 0.3s ease;
  opacity: 0.95;
  backdrop-filter: blur(4px);
  -webkit-backdrop-filter: blur(4px);
  cursor: pointer;
}

.edge-gradient:hover {
  opacity: 1;
}

.left-gradient {
  left: 0;
}

.right-gradient {
  right: 0;
}

/* Items Container */
.items-container {
  display: flex;
  overflow-x: auto;
  overflow-y: hidden;
  padding: 0.5rem 0;
  scroll-behavior: smooth;
  -ms-overflow-style: none;
  scrollbar-width: none;
}

.items-container::-webkit-scrollbar {
  display: none;
}

/* Gap Classes */
.gap-2 > * + * {
  margin-left: 0.5rem;
}

.gap-3 > * + * {
  margin-left: 0.75rem;
}

.gap-4 > * + * {
  margin-left: 1rem;
}

/* Default Item */
.default-item {
  flex-shrink: 0;
  width: 5rem;
  height: 5rem;
  background-color: rgb(243 244 246);
  border-radius: 0.5rem;
  border: 1px solid rgb(229 231 235);
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s ease;
}

.default-item:hover {
  transform: translateY(-2px) scale(1.02);
  box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
}

.item-label {
  font-size: 0.875rem;
  color: rgb(107 114 128);
  font-weight: 500;
}

/* View All Section */
.view-all-section {
  display: flex;
  justify-content: flex-end;
  margin-top: 0.5rem;
}

.view-all-text {
  background: none;
  border: none;
  padding: 0.25rem 0;
  font-size: 0.75rem;
  font-weight: 500;
  color: rgb(107 114 128);
  transition: all 0.2s ease;
  display: flex;
  align-items: center;
  gap: 0.25rem;
  cursor: pointer;
}

.view-all-text:hover {
  color: rgb(75 85 99);
  transform: translateX(2px);
}

/* Empty State */
.empty-state {
  text-align: center;
  padding: 2rem 0;
}

.empty-icon {
  width: 2.5rem;
  height: 2.5rem;
  margin: 0 auto 0.75rem;
  color: rgb(156 163 175);
}

.empty-text {
  font-size: 0.875rem;
  color: rgb(107 114 128);
  font-weight: 500;
}

/* Dark Mode */
@media (prefers-color-scheme: dark) {
  .arrow-active {
    color: rgb(243 244 246);
  }
  
  .arrow-active:hover {
    color: rgb(255 255 255);
  }
  
  .default-item {
    background-color: rgb(31 41 55);
    border-color: rgb(75 85 99);
  }
  
  .item-label {
    color: rgb(156 163 175);
  }
  
  .view-all-text {
    color: rgb(156 163 175);
  }
  
  .view-all-text:hover {
    color: rgb(209 213 219);
  }
  
  .empty-icon {
    color: rgb(107 114 128);
  }
  
  .empty-text {
    color: rgb(156 163 175);
  }
}

/* Focus States */
.nav-arrow:focus-visible,
.view-all-text:focus-visible,
.edge-gradient:focus-visible {
  outline: 2px solid rgb(107 114 128);
  outline-offset: 2px;
}

/* Dark Mode Focus States */
@media (prefers-color-scheme: dark) {
  .nav-arrow:focus-visible,
  .view-all-text:focus-visible,
  .edge-gradient:focus-visible {
    outline-color: rgb(156 163 175);
  }
}

/* Responsive Design */
@media (max-width: 640px) {
  .edge-gradient {
    width: 1rem;
    opacity: 0.8;
  }
  
  .nav-arrow-left {
    left: 0;
  }
  
  .nav-arrow-right {
    right: 0;
  }
}

/* High Contrast Mode */
@media (prefers-contrast: high) {
  .nav-arrow {
    background-color: rgba(0, 0, 0, 0.8);
    border: 2px solid rgb(255 255 255);
    border-radius: 50%;
  }
  
  .arrow-active {
    color: rgb(255 255 255);
  }
  
  .default-item {
    border-width: 2px;
  }
}

/* Reduced Motion */
@media (prefers-reduced-motion: reduce) {
  .nav-arrow,
  .default-item,
  .view-all-text,
  .edge-gradient {
    transition: none;
  }
  
  .items-container {
    scroll-behavior: auto;
  }
}
</style>

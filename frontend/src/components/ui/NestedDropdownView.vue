<template>
  <div class="relative">
    <!-- Slide Transition Container -->
    <div class="relative overflow-hidden">
      <!-- Main View -->
      <Transition
        name="slide"
        mode="out-in"
      >
        <div
          v-if="!showNestedView"
          key="main"
          class="min-h-[200px] max-h-[70vh] overflow-y-auto"
          ref="mainViewRef"
        >
          <slot name="main" :goToNested="goToNested" />
        </div>
        
        <!-- Nested View -->
        <div
          v-else
          key="nested"
          class="min-h-[200px] max-h-[70vh] overflow-y-auto"
          ref="nestedViewRef"
        >
          <!-- Back Button -->
          <div class="px-5 py-3 border-b border-gray-100 dark:border-gray-700 sticky top-0 bg-white dark:bg-gray-800 z-10">
            <button
              @click="goBack"
              class="flex items-center text-sm text-gray-600 dark:text-gray-400 hover:text-violet-600 dark:hover:text-violet-400 transition-colors duration-200"
            >
              <ChevronLeftIcon class="w-4 h-4 mr-2" />
              <span class="font-medium">Back</span>
            </button>
          </div>
          
          <!-- Nested Content -->
          <div class="px-5 py-3">
            <slot name="nested" :goBack="goBack" />
          </div>
        </div>
      </Transition>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { ChevronLeftIcon } from '@heroicons/vue/24/outline'

const showNestedView = ref(false)

const goToNested = () => {
  showNestedView.value = true
}

const goBack = () => {
  showNestedView.value = false
}
</script>

<style scoped>
.slide-enter-active,
.slide-leave-active {
  transition: transform 0.3s ease-in-out;
}

.slide-enter-from {
  transform: translateX(100%);
}

.slide-leave-to {
  transform: translateX(-100%);
}
</style>

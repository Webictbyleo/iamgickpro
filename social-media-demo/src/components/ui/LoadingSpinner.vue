<template>
  <div class="animate-spin rounded-full border-b-2 border-primary-600"
       :class="sizeClass">
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';

interface Props {
  size?: 'sm' | 'md' | 'lg';
}

const props = withDefaults(defineProps<Props>(), {
  size: 'md'
});

const sizeClass = computed(() => {
  switch (props.size) {
    case 'sm':
      return 'h-4 w-4';
    case 'md':
      return 'h-8 w-8';
    case 'lg':
      return 'h-12 w-12';
    default:
      return 'h-8 w-8';
  }
});
</script>

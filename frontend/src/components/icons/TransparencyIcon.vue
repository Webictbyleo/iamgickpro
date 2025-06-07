<template>
  <svg 
    :width="size" 
    :height="size" 
    viewBox="0 0 24 24" 
    fill="none" 
    xmlns="http://www.w3.org/2000/svg"
    :class="className"
  >
    <!-- Checkerboard pattern for transparency -->
    <defs>
      <pattern id="transparency-pattern" patternUnits="userSpaceOnUse" width="4" height="4">
        <rect width="2" height="2" fill="#e5e7eb" />
        <rect x="2" y="2" width="2" height="2" fill="#e5e7eb" />
        <rect x="2" y="0" width="2" height="2" fill="#ffffff" />
        <rect x="0" y="2" width="2" height="2" fill="#ffffff" />
      </pattern>
    </defs>
    
    <!-- Background circle with transparency pattern -->
    <circle cx="12" cy="12" r="9" fill="url(#transparency-pattern)" stroke="currentColor" stroke-width="1.5" />
    
    <!-- Opacity gradient overlay -->
    <circle cx="12" cy="12" r="6" fill="none" stroke="currentColor" stroke-width="2" opacity="0.7" />
    
    <!-- Center dot -->
    <circle cx="12" cy="12" r="2" fill="currentColor" opacity="0.8" />
  </svg>
</template>

<script setup lang="ts">
interface Props {
  size?: number | string;
  className?: string;
}

withDefaults(defineProps<Props>(), {
  size: 20,
  className: ''
});
</script>

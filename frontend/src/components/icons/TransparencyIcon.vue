<template>
  <svg 
    :width="size" 
    :height="size" 
    viewBox="0 0 24 24" 
    fill="none" 
    xmlns="http://www.w3.org/2000/svg"
    :class="className"
  >
    <!-- Define patterns and gradients -->
    <defs>
      <!-- Checkerboard pattern for transparency -->
      <pattern id="transparency-checkerboard" x="0" y="0" width="3" height="3" patternUnits="userSpaceOnUse">
        <rect x="0" y="0" width="1.5" height="1.5" fill="#d1d5db" />
        <rect x="1.5" y="1.5" width="1.5" height="1.5" fill="#d1d5db" />
        <rect x="0" y="1.5" width="1.5" height="1.5" fill="#f3f4f6" />
        <rect x="1.5" y="0" width="1.5" height="1.5" fill="#f3f4f6" />
      </pattern>
      
      <!-- Gradient from solid to transparent -->
      <linearGradient id="fade-to-transparent" x1="0%" y1="0%" x2="100%" y2="0%">
        <stop offset="0%" style="stop-color:currentColor;stop-opacity:1" />
        <stop offset="70%" style="stop-color:currentColor;stop-opacity:0.3" />
        <stop offset="100%" style="stop-color:currentColor;stop-opacity:0" />
      </linearGradient>
    </defs>
    
    <!-- Main square with rounded corners -->
    <rect 
      x="3" 
      y="3" 
      width="18" 
      height="18" 
      rx="3" 
      ry="3"
      stroke="currentColor" 
      stroke-width="1.5"
      fill="none"
    />
    
    <!-- Checkerboard background in the right portion -->
    <rect 
      x="12" 
      y="4.5" 
      width="7.5" 
      height="15" 
      rx="2" 
      ry="2"
      fill="url(#transparency-checkerboard)"
    />
    
    <!-- Gradient overlay showing fade to transparency -->
    <rect 
      x="4.5" 
      y="4.5" 
      width="15" 
      height="15" 
      rx="2" 
      ry="2"
      fill="url(#fade-to-transparent)"
    />
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

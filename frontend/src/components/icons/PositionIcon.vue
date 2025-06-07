<template>
  <svg 
    :width="size" 
    :height="size" 
    viewBox="0 0 24 24" 
    fill="none" 
    xmlns="http://www.w3.org/2000/svg"
    :class="className"
  >
    <!-- Grid background -->
    <path 
      d="M3 3h18v18H3V3z" 
      :stroke="fill" 
      stroke-width="1.5" 
      fill="none"
    />
    <!-- Grid lines -->
    <path 
      d="M3 9h18M3 15h18M9 3v18M15 3v18" 
      :stroke="fill" 
      stroke-width="0.8" 
      opacity="0.4"
    />
    <!-- Position indicator dot -->
    <circle 
      cx="12" 
      cy="12" 
      r="2.5" 
      :fill="fill"
    />
    <!-- Corner indicators (small squares) -->
    <rect 
      x="2" 
      y="2" 
      width="2" 
      height="2" 
      :fill="fill" 
      opacity="0.6"
    />
    <rect 
      x="20" 
      y="2" 
      width="2" 
      height="2" 
      :fill="fill" 
      opacity="0.6"
    />
    <rect 
      x="2" 
      y="20" 
      width="2" 
      height="2" 
      :fill="fill" 
      opacity="0.6"
    />
    <rect 
      x="20" 
      y="20" 
      width="2" 
      height="2" 
      :fill="fill" 
      opacity="0.6"
    />
  </svg>
</template>

<script setup lang="ts">
interface Props {
  size?: number | string;
  fill?: string;
  className?: string;
}

withDefaults(defineProps<Props>(), {
  size: 20,
  fill: 'currentColor',
  className: ''
});
</script>

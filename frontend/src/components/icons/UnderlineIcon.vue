<template>
  <svg 
    :width="size" 
    :height="size" 
    viewBox="0 0 24 24" 
    fill="none" 
    xmlns="http://www.w3.org/2000/svg"
    :class="className"
  >
    <path 
      d="M6 4v7c0 3.314 2.686 6 6 6s6-2.686 6-6V4h-3v7c0 1.657-1.343 3-3 3s-3-1.343-3-3V4H6zm-2 16h16v2H4v-2z" 
      :fill="fill"
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

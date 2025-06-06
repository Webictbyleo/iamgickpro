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
      d="M6 4h8a4 4 0 0 1 4 4 4 4 0 0 1-4 4H6z" 
      :fill="fill"
    />
    <path 
      d="M6 12h9a4 4 0 0 1 4 4 4 4 0 0 1-4 4H6z" 
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

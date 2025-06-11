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
      d="M3 4h18v2H3V4zm3 4h12v2H6V8zm-3 4h18v2H3v-2zm3 4h12v2H6v-2zm-3 4h18v2H3v-2z" 
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

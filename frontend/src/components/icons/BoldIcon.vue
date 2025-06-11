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
      d="M6 4v16h7.5c2.485 0 4.5-2.015 4.5-4.5s-2.015-4.5-4.5-4.5H15c1.933 0 3.5-1.567 3.5-3.5S16.933 4 15 4H6zm3 3h5.5c.828 0 1.5.672 1.5 1.5S15.328 10 14.5 10H9V7zm0 6h6.5c.828 0 1.5.672 1.5 1.5s-.672 1.5-1.5 1.5H9v-3z" 
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

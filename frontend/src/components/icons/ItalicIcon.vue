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
      d="M19 4H5a2 2 0 0 0-2 2v1h18V6a2 2 0 0 0-2-2zM3 20a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V9H3v11zm2-7h2v2H5v-2zm4 0h2v2H9v-2zm4 0h2v2h-2v-2zm-8 4h2v2H5v-2zm4 0h2v2H9v-2zm4 0h2v2h-2v-2z" 
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

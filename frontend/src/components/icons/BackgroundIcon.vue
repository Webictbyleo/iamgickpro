<template>
  <svg
    :width="size"
    :height="size"
    viewBox="0 0 24 24"
    fill="none"
    xmlns="http://www.w3.org/2000/svg"
    :class="className"
  >
    <!-- Background fill rectangle -->
    <rect
      x="3"
      y="6"
      width="18"
      height="12"
      rx="2"
      :fill="fill"
      :stroke="stroke"
      :stroke-width="strokeWidth"
    />
    <!-- Text lines to represent text on background -->
    <line
      x1="6"
      y1="9"
      x2="18"
      y2="9"
      :stroke="textColor"
      stroke-width="1.5"
      stroke-linecap="round"
    />
    <line
      x1="6"
      y1="12"
      x2="15"
      y2="12"
      :stroke="textColor"
      stroke-width="1.5"
      stroke-linecap="round"
    />
    <line
      x1="6"
      y1="15"
      x2="12"
      y2="15"
      :stroke="textColor"
      stroke-width="1.5"
      stroke-linecap="round"
    />
  </svg>
</template>

<script setup lang="ts">
interface Props {
  size?: number | string;
  fill?: string;
  stroke?: string;
  strokeWidth?: number | string;
  textColor?: string;
  className?: string;
}

withDefaults(defineProps<Props>(), {
  size: 24,
  fill: 'currentColor',
  stroke: 'none',
  strokeWidth: 0,
  textColor: '#ffffff',
  className: ''
});
</script>

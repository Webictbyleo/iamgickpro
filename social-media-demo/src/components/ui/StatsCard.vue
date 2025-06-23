<template>
  <div class="card">
    <div class="flex items-center justify-between">
      <div>
        <div class="text-2xl font-bold text-gray-900">{{ value }}</div>
        <div class="text-sm font-medium text-gray-600">{{ title }}</div>
      </div>
      <div class="text-right">
        <div class="text-2xl mb-1">{{ icon }}</div>
        <div
          v-if="trend !== 'neutral'"
          class="text-sm font-medium"
          :class="{
            'text-green-600': trend === 'up',
            'text-red-600': trend === 'down'
          }"
        >
          {{ change }}
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
interface Props {
  title: string;
  value: string;
  change: string;
  trend: 'up' | 'down' | 'neutral';
  icon: string;
}

defineProps<Props>();
</script>

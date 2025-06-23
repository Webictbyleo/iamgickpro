<template>
  <div class="flex items-center justify-between py-3 border-b border-gray-100 last:border-b-0">
    <div class="flex-1">
      <div class="text-sm font-medium text-gray-900">{{ action }}</div>
      <div class="text-sm text-gray-600">{{ content }}</div>
    </div>
    <div class="flex items-center space-x-2">
      <span
        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
        :class="{
          'bg-green-100 text-green-800': status === 'success',
          'bg-yellow-100 text-yellow-800': status === 'pending',
          'bg-red-100 text-red-800': status === 'failed'
        }"
      >
        {{ statusText }}
      </span>
      <span class="text-sm text-gray-500">{{ time }}</span>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';

interface Props {
  action: string;
  content: string;
  time: string;
  status: 'success' | 'pending' | 'failed';
}

const props = defineProps<Props>();

const statusText = computed(() => {
  switch (props.status) {
    case 'success':
      return 'Success';
    case 'pending':
      return 'Pending';
    case 'failed':
      return 'Failed';
    default:
      return '';
  }
});
</script>

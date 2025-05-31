<template>
  <div id="app" class="min-h-screen bg-gray-50">
    <RouterView />
    <NotificationContainer />
  </div>
</template>

<script setup lang="ts">
import { RouterView } from 'vue-router'
import NotificationContainer from '@/components/NotificationContainer.vue'
</script>

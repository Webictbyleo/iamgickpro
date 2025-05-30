<template>
  <div class="h-screen flex overflow-hidden bg-gradient-to-br from-slate-50 via-purple-50/30 to-blue-50/20">
    <!-- Sidebar -->
    <div 
      class="fixed inset-y-0 left-0 z-50 w-64 transform transition-all duration-300 ease-in-out md:relative md:translate-x-0"
      :class="{ '-translate-x-full': !sidebarOpen, 'translate-x-0': sidebarOpen }"
    >
      <AppSidebar @logout="handleLogout" />
    </div>

    <!-- Sidebar overlay for mobile -->
    <Transition
      enter-active-class="transition-opacity duration-300 ease-out"
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-active-class="transition-opacity duration-200 ease-in"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div 
        v-if="sidebarOpen"
        class="fixed inset-0 z-40 bg-black/60 backdrop-blur-sm md:hidden"
        @click="sidebarOpen = false"
      ></div>
    </Transition>

    <!-- Main content -->
    <div class="flex-1 flex flex-col overflow-hidden">
      <!-- Header -->
      <AppHeader 
        :title="title"
        :subtitle="subtitle"
        @toggle-sidebar="sidebarOpen = !sidebarOpen"
        @search="handleSearch"
        @create-design="handleCreateDesign"
        @logout="handleLogout"
      />

      <!-- Toast Notifications -->
      <ToastNotifications />

      <!-- Page content -->
      <main class="flex-1 overflow-y-auto">
        <div class="min-h-full p-4">
          <slot />
        </div>
      </main>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import AppSidebar from './AppSidebar.vue'
import AppHeader from './AppHeader.vue'
import ToastNotifications from '@/components/ui/ToastNotifications.vue'

interface Props {
  title?: string
  subtitle?: string
}

withDefaults(defineProps<Props>(), {
  title: 'Dashboard',
  subtitle: ''
})

const emit = defineEmits<{
  search: [query: string]
  createDesign: []
  logout: []
}>()

const sidebarOpen = ref(false)

const handleSearch = (query: string) => {
  emit('search', query)
}

const handleLogout = () => {
  emit('logout')
}

const handleCreateDesign = () => {
  emit('createDesign')
}
</script>

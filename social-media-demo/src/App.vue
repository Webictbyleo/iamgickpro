<template>
  <div id="app" class="min-h-screen bg-gray-50">
    <!-- Navigation Header -->
    <nav class="bg-white shadow-sm border-b border-gray-200">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
          <div class="flex items-center">
            <div class="flex-shrink-0">
              <h1 class="text-2xl font-bold text-primary-600">
                🚀 AI Social Studio
              </h1>
            </div>
            <div class="hidden sm:ml-6 sm:flex sm:space-x-8">
              <router-link
                to="/dashboard"
                class="border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm transition-colors"
                active-class="border-primary-500 text-primary-600"
              >
                Dashboard
              </router-link>
              <router-link
                to="/workflow"
                class="border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm transition-colors"
                active-class="border-primary-500 text-primary-600"
              >
                Create Content
              </router-link>
            </div>
          </div>
          
          <div class="flex items-center space-x-4">
            <div class="text-sm text-gray-500">
              Demo Mode
            </div>
            <div class="h-8 w-8 bg-primary-100 rounded-full flex items-center justify-center">
              <span class="text-primary-600 font-medium text-sm">JD</span>
            </div>
          </div>
        </div>
      </div>
    </nav>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
      <router-view />
    </main>

    <!-- Notifications -->
    <NotificationContainer />
  </div>
</template>

<script setup lang="ts">
import NotificationContainer from './components/ui/NotificationContainer.vue'
</script>

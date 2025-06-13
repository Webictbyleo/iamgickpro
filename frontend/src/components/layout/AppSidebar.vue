<template>
  <!-- Modern Sidebar with Glass Morphism -->
  <div class="h-full bg-white/95 backdrop-blur-xl border-r border-gray-200/50 shadow-xl">
    <div class="flex flex-col h-full">
      <!-- Logo Section -->
      <div class="flex items-center flex-shrink-0 px-6 py-5 border-b border-gray-200/50">
        <router-link to="/" class="flex items-center group">
          <div class="relative">
            <div class="w-10 h-10 bg-gradient-to-br from-violet-600 via-purple-600 to-blue-600 rounded-xl flex items-center justify-center shadow-lg transform group-hover:scale-105 transition-transform duration-200">
              <span class="text-white font-bold text-lg">I</span>
            </div>
            <div class="absolute -top-1 -right-1 w-3 h-3 bg-green-400 rounded-full border-2 border-white"></div>
          </div>
          <div class="ml-3">
            <span class="text-xl font-bold bg-gradient-to-r from-gray-900 to-gray-700 bg-clip-text text-transparent">{{ appTitle }}</span>
            <div class="text-xs text-gray-500 font-medium">{{ appSubtitle }}</div>
          </div>
        </router-link>
      </div>

      <!-- Navigation Section -->
      <div class="flex-1 flex flex-col overflow-hidden">
        <!-- Scrollable Navigation Area -->
        <div class="flex-1 overflow-y-auto px-4 py-6 space-y-4">
          <!-- Main Navigation -->
          <nav class="space-y-2">
            <div class="text-xs font-semibold text-gray-400 uppercase tracking-wider px-3 mb-2">
              Main Menu
            </div>
          
          <!-- Dashboard -->
          <router-link
            to="/dashboard"
            class="sidebar-nav-item group"
            :class="{ 'sidebar-nav-item-active': $route.name === 'Dashboard' }"
          >
            <component :is="icons.home" class="w-5 h-5 mr-3 transition-colors" />
            <span>Dashboard</span>
            <div class="ml-auto opacity-0 group-hover:opacity-100 transition-opacity">
              <component :is="icons.arrowRight" class="w-4 h-4" />
            </div>
          </router-link>

          <!-- My Designs -->
          <router-link
            to="/designs"
            class="sidebar-nav-item group"
            :class="{ 'sidebar-nav-item-active': $route.name === 'Designs' }"
          >
            <component :is="icons.design" class="w-5 h-5 mr-3 transition-colors" />
            <span>My Designs</span>
            
          </router-link>

          <!-- Templates -->
          <router-link
            to="/templates"
            class="sidebar-nav-item group"
            :class="{ 'sidebar-nav-item-active': $route.name === 'Templates' }"
          >
            <component :is="icons.template" class="w-5 h-5 mr-3 transition-colors" />
            <span>Templates</span>
            <div class="ml-auto opacity-0 group-hover:opacity-100 transition-opacity">
              <component :is="icons.sparkle" class="w-4 h-4 text-yellow-500" />
            </div>
          </router-link>

          <!-- YouTube Thumbnails -->
          <router-link
            to="/video-to-design"
            class="sidebar-nav-item group"
            :class="{ 'sidebar-nav-item-active': $route.name === 'VideoToDesign' || isActive('/video-to-design') }"
          >
            <div class="relative mr-3">
              <svg class="w-5 h-5 text-red-600 transition-colors" fill="currentColor" viewBox="0 0 24 24">
                <path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/>
              </svg>
              <div class="absolute -top-1 -right-1 w-2 h-2 bg-red-500 rounded-full animate-pulse"></div>
            </div>
            <span>YouTube Thumbnails</span>
            <div class="ml-auto flex items-center space-x-2">
              <span class="bg-gradient-to-r from-red-500 to-pink-500 text-white text-xs px-2 py-1 rounded-full font-medium">
                NEW
              </span>
              <div class="opacity-0 group-hover:opacity-100 transition-opacity">
                <component :is="icons.arrowRight" class="w-4 h-4" />
              </div>
            </div>
          </router-link>

          <!-- Stock Media -->
          <router-link
            to="/media"
            class="sidebar-nav-item group"
            :class="{ 'sidebar-nav-item-active': $route.name === 'Media' }"
          >
            <component :is="icons.image" class="w-5 h-5 mr-3 transition-colors" />
            <span>Stock Media</span>
            <div class="ml-auto opacity-0 group-hover:opacity-100 transition-opacity">
              <component :is="icons.arrowRight" class="w-4 h-4" />
            </div>
          </router-link>

          <!-- Export Jobs -->
          <router-link
            to="/exports"
            class="sidebar-nav-item group"
            :class="{ 'sidebar-nav-item-active': $route.name === 'Exports' }"
          >
            <component :is="icons.documentDownload" class="w-5 h-5 mr-3 transition-colors" />
            <span>Export Jobs</span>
            <div v-if="hasActiveJobs" class="ml-auto">
              <span class="bg-gradient-to-r from-blue-500 to-cyan-600 text-white text-xs px-2 py-1 rounded-full font-medium animate-pulse">
                {{ activeJobsCount }}
              </span>
            </div>
            <div v-else class="ml-auto opacity-0 group-hover:opacity-100 transition-opacity">
              <component :is="icons.arrowRight" class="w-4 h-4" />
            </div>
          </router-link>
        </nav>

        <!-- Settings Section -->
        <nav class="space-y-2">
          <div class="text-xs font-semibold text-gray-400 uppercase tracking-wider px-3 mb-2">
            Settings
          </div>
          
          <!-- Settings Overview -->
          <router-link
            to="/settings"
            class="sidebar-nav-item group"
            :class="{ 'sidebar-nav-item-active': $route.name === 'Settings' }"
          >
            <component :is="icons.cog" class="w-5 h-5 mr-3 transition-colors" />
            <span>Settings</span>
            <div class="ml-auto opacity-0 group-hover:opacity-100 transition-opacity">
              <component :is="icons.arrowRight" class="w-4 h-4" />
            </div>
          </router-link>
        </nav>
      </div>
      </div>

      <!-- User Profile Section -->
        <div class="flex-shrink-0 border-t border-gray-200/50 p-4">
        <div class="flex items-center">
          <div class="relative">
            <div class="w-10 h-10 bg-gradient-to-br from-violet-500 to-purple-600 rounded-full flex items-center justify-center shadow-lg">
              <span class="text-white font-semibold text-sm">{{ userInitials }}</span>
            </div>
            <div class="absolute -bottom-0.5 -right-0.5 w-4 h-4 bg-green-400 border-2 border-white rounded-full"></div>
          </div>
          <div class="ml-3 flex-1 min-w-0">
            <p class="text-sm font-semibold text-gray-900 truncate">{{ userName }}</p>
            <p class="text-xs text-gray-500 truncate">{{ userEmail }}</p>
          </div>
          <Menu as="div" class="relative">
            <MenuButton class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg transition-colors">
              <component :is="icons.moreVertical" class="w-5 h-5" />
            </MenuButton>
            <Transition
              enter-active-class="transition duration-100 ease-out"
              enter-from-class="transform scale-95 opacity-0"
              enter-to-class="transform scale-100 opacity-100"
              leave-active-class="transition duration-75 ease-in"
              leave-from-class="transform scale-100 opacity-100"
              leave-to-class="transform scale-95 opacity-0"
            >
              <MenuItems class="absolute right-0 bottom-full mb-2 w-48 bg-white rounded-xl shadow-lg border border-gray-200 py-1 z-50">
                <MenuItem v-slot="{ active }">
                  <router-link
                    to="/settings/profile"
                    :class="[
                      active ? 'bg-gray-50 text-gray-900' : 'text-gray-700',
                      'group flex w-full items-center px-4 py-2 text-sm'
                    ]"
                  >
                    <component :is="icons.user" class="w-4 h-4 mr-3" />
                    Profile Settings
                  </router-link>
                </MenuItem>
                <MenuItem v-slot="{ active }">
                  <router-link
                    to="/settings/subscription"
                    :class="[
                      active ? 'bg-gray-50 text-gray-900' : 'text-gray-700',
                      'group flex w-full items-center px-4 py-2 text-sm'
                    ]"
                  >
                    <component :is="icons.creditCard" class="w-4 h-4 mr-3" />
                    Subscription Plans
                  </router-link>
                </MenuItem>
                <MenuItem v-slot="{ active }">
                  <router-link
                    to="/settings/general"
                    :class="[
                      active ? 'bg-gray-50 text-gray-900' : 'text-gray-700',
                      'group flex w-full items-center px-4 py-2 text-sm'
                    ]"
                  >
                    <component :is="icons.cog" class="w-4 h-4 mr-3" />
                    Preferences
                  </router-link>
                </MenuItem>
                <MenuItem v-slot="{ active }">
                  <button
                    :class="[
                      active ? 'bg-gray-50 text-gray-900' : 'text-gray-700',
                      'group flex w-full items-center px-4 py-2 text-sm'
                    ]"
                  >
                    <component :is="icons.help" class="w-4 h-4 mr-3" />
                    Help & Support
                  </button>
                </MenuItem>
                <div class="border-t border-gray-100 my-1"></div>
                <MenuItem v-slot="{ active }">
                  <button
                    @click="$emit('logout')"
                    :class="[
                      active ? 'bg-red-50 text-red-900' : 'text-red-700',
                      'group flex w-full items-center px-4 py-2 text-sm'
                    ]"
                  >
                    <component :is="icons.logout" class="w-4 h-4 mr-3" />
                    Sign out
                  </button>
                </MenuItem>
              </MenuItems>
            </Transition>
          </Menu>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { Menu, MenuButton, MenuItems, MenuItem } from '@headlessui/vue'
import { useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useExportsStore } from '@/stores/exports'
import { useIcons } from '@/composables/useIcons'

interface Props {
  sidebarOpen?: boolean
}

defineProps<Props>()

defineEmits<{
  logout: []
  closeSidebar: []
}>()

const authStore = useAuthStore()
const exportsStore = useExportsStore()
const icons = useIcons()
const route = useRoute()

const appTitle = computed(() => import.meta.env.VITE_APP_TITLE || 'Design Studio')
const appSubtitle = computed(() => import.meta.env.VITE_APP_SUBTITLE || 'Design Studio')

const userName = computed(() => {
  const user = authStore.user
  return user ? `${user.firstName} ${user.lastName}` : 'Demo User'
})
const userEmail = computed(() => authStore.user?.email || 'demo@example.com')
const userInitials = computed(() => {
  const user = authStore.user
  if (user) {
    return `${user.firstName[0]}${user.lastName[0]}`.toUpperCase()
  }
  return 'DU'
})



const hasActiveJobs = computed(() => exportsStore.hasActiveJobs)
const activeJobsCount = computed(() => 
  exportsStore.pendingJobs.length + exportsStore.processingJobs.length
)

const formatDate = (date: string | Date) => {
  const d = new Date(date)
  const now = new Date()
  const diffDays = Math.floor((now.getTime() - d.getTime()) / (1000 * 60 * 60 * 24))
  
  if (diffDays === 0) return 'Today'
  if (diffDays === 1) return 'Yesterday'
  if (diffDays < 7) return `${diffDays} days ago`
  return d.toLocaleDateString()
}

const isActive = (path: string): boolean => {
  return route.path.startsWith(path)
}
</script>

<style scoped>
.sidebar-nav-item {
  display: flex;
  align-items: center;
  padding: 0.625rem 0.75rem;
  font-size: 0.875rem;
  font-weight: 500;
  border-radius: 0.75rem;
  color: rgb(75 85 99);
  transition: all 0.2s;
  position: relative;
  overflow: hidden;
}

.sidebar-nav-item:hover {
  color: rgb(17 24 39);
  background-color: rgb(249 250 251 / 0.8);
}

.sidebar-nav-item-active {
  background: linear-gradient(to right, rgb(139 92 246 / 0.1), rgb(168 85 247 / 0.1));
  color: rgb(109 40 217);
  border-right: 2px solid rgb(139 92 246);
  box-shadow: 0 1px 2px 0 rgb(0 0 0 / 0.05);
}

.sidebar-nav-item-active::before {
  content: '';
  position: absolute;
  inset: 0;
  background: linear-gradient(to right, rgb(139 92 246 / 0.05), rgb(168 85 247 / 0.05));
  z-index: -10;
}
</style>

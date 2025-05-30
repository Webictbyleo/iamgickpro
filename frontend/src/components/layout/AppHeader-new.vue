<template>
  <header class="bg-white/95 backdrop-blur-xl border-b border-gray-200/50 px-4 sm:px-6 lg:px-8 shadow-sm">
    <div class="flex items-center justify-between h-16">
      <!-- Left side -->
      <div class="flex items-center">
        <!-- Mobile menu button -->
        <button
          @click="$emit('toggleSidebar')"
          class="p-2.5 rounded-xl text-gray-400 hover:text-gray-600 hover:bg-gray-100/80 transition-all duration-200 md:hidden focus:outline-none focus:ring-2 focus:ring-violet-500/20"
        >
          <component :is="icons.menu" class="w-6 h-6" />
        </button>

        <!-- Page title -->
        <div class="ml-4 md:ml-0">
          <h1 class="text-2xl font-bold bg-gradient-to-r from-gray-900 to-gray-700 bg-clip-text text-transparent">
            {{ title }}
          </h1>
          <p v-if="subtitle" class="text-sm text-gray-500 font-medium">{{ subtitle }}</p>
        </div>
      </div>

      <!-- Right side -->
      <div class="flex items-center space-x-3">
        <!-- Search -->
        <div class="hidden sm:block">
          <div class="relative">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
              <component :is="icons.search" class="h-5 w-5 text-gray-400" />
            </div>
            <input
              v-model="searchQuery"
              type="text"
              placeholder="Search designs, templates..."
              class="block w-80 pl-10 pr-4 py-2.5 border border-gray-200 rounded-xl bg-gray-50/50 backdrop-blur-sm text-sm placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-violet-500/20 focus:border-violet-300 focus:bg-white transition-all duration-200"
              @input="$emit('search', searchQuery)"
              @focus="searchFocused = true"
              @blur="searchFocused = false"
            />
            <!-- Search suggestions dropdown would go here -->
          </div>
        </div>

        <!-- Quick Actions -->
        <div class="flex items-center space-x-2">
          <!-- Notifications -->
          <button class="relative p-2.5 text-gray-400 hover:text-gray-600 hover:bg-gray-100/80 rounded-xl transition-all duration-200 group">
            <component :is="icons.bell" class="w-6 h-6" />
            <!-- Notification badge -->
            <span class="absolute -top-0.5 -right-0.5 h-4 w-4 bg-gradient-to-r from-red-500 to-pink-500 rounded-full flex items-center justify-center">
              <span class="text-xs font-semibold text-white">3</span>
            </span>
          </button>

          <!-- Help & Support -->
          <button class="p-2.5 text-gray-400 hover:text-gray-600 hover:bg-gray-100/80 rounded-xl transition-all duration-200">
            <component :is="icons.help" class="w-6 h-6" />
          </button>
        </div>

        <!-- Create new design button -->
        <Menu as="div" class="relative">
          <MenuButton
            class="inline-flex items-center px-4 py-2.5 bg-gradient-to-r from-violet-600 to-purple-600 hover:from-violet-700 hover:to-purple-700 text-white text-sm font-semibold rounded-xl shadow-md hover:shadow-lg transform hover:scale-105 transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-violet-500/20"
          >
            <component :is="icons.plus" class="w-4 h-4 mr-2" />
            <span class="hidden sm:inline">Create</span>
            <component :is="icons.chevronDown" class="w-4 h-4 ml-2" />
          </MenuButton>
          <Transition
            enter-active-class="transition duration-100 ease-out"
            enter-from-class="transform scale-95 opacity-0"
            enter-to-class="transform scale-100 opacity-100"
            leave-active-class="transition duration-75 ease-in"
            leave-from-class="transform scale-100 opacity-100"
            leave-to-class="transform scale-95 opacity-0"
          >
            <MenuItems class="absolute right-0 mt-2 w-56 bg-white rounded-xl shadow-xl border border-gray-200/50 backdrop-blur-xl py-1 z-50">
              <MenuItem v-slot="{ active }">
                <button
                  @click="$emit('createDesign', 'blank')"
                  :class="[
                    active ? 'bg-violet-50 text-violet-700' : 'text-gray-700',
                    'group flex w-full items-center px-4 py-3 text-sm font-medium'
                  ]"
                >
                  <div class="w-8 h-8 bg-gradient-to-br from-violet-500 to-purple-600 rounded-lg flex items-center justify-center mr-3">
                    <component :is="icons.plus" class="w-4 h-4 text-white" />
                  </div>
                  <div class="text-left">
                    <div class="font-semibold">Blank Design</div>
                    <div class="text-xs text-gray-500">Start from scratch</div>
                  </div>
                </button>
              </MenuItem>
              <MenuItem v-slot="{ active }">
                <button
                  @click="$emit('createDesign', 'template')"
                  :class="[
                    active ? 'bg-violet-50 text-violet-700' : 'text-gray-700',
                    'group flex w-full items-center px-4 py-3 text-sm font-medium'
                  ]"
                >
                  <div class="w-8 h-8 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-lg flex items-center justify-center mr-3">
                    <component :is="icons.template" class="w-4 h-4 text-white" />
                  </div>
                  <div class="text-left">
                    <div class="font-semibold">From Template</div>
                    <div class="text-xs text-gray-500">Choose a template</div>
                  </div>
                </button>
              </MenuItem>
              <MenuItem v-slot="{ active }">
                <button
                  @click="$emit('createDesign', 'upload')"
                  :class="[
                    active ? 'bg-violet-50 text-violet-700' : 'text-gray-700',
                    'group flex w-full items-center px-4 py-3 text-sm font-medium'
                  ]"
                >
                  <div class="w-8 h-8 bg-gradient-to-br from-green-500 to-teal-600 rounded-lg flex items-center justify-center mr-3">
                    <component :is="icons.upload" class="w-4 h-4 text-white" />
                  </div>
                  <div class="text-left">
                    <div class="font-semibold">Upload Image</div>
                    <div class="text-xs text-gray-500">Edit existing image</div>
                  </div>
                </button>
              </MenuItem>
            </MenuItems>
          </Transition>
        </Menu>

        <!-- User menu -->
        <Menu as="div" class="relative">
          <MenuButton class="flex items-center p-1.5 rounded-xl hover:bg-gray-100/80 transition-all duration-200 group">
            <div class="relative">
              <div class="w-9 h-9 bg-gradient-to-br from-violet-500 to-purple-600 rounded-xl flex items-center justify-center shadow-md group-hover:shadow-lg transition-shadow">
                <span class="text-white font-semibold text-sm">{{ userInitials }}</span>
              </div>
              <div class="absolute -bottom-0.5 -right-0.5 w-3 h-3 bg-green-400 border-2 border-white rounded-full"></div>
            </div>
            <component :is="icons.chevronDown" class="ml-2 w-4 h-4 text-gray-400 group-hover:text-gray-600 transition-colors" />
          </MenuButton>

          <!-- User dropdown -->
          <Transition
            enter-active-class="transition duration-100 ease-out"
            enter-from-class="transform scale-95 opacity-0"
            enter-to-class="transform scale-100 opacity-100"
            leave-active-class="transition duration-75 ease-in"
            leave-from-class="transform scale-100 opacity-100"
            leave-to-class="transform scale-95 opacity-0"
          >
            <MenuItems class="absolute right-0 mt-2 w-64 bg-white rounded-xl shadow-xl border border-gray-200/50 backdrop-blur-xl py-2 z-50">
              <!-- User info -->
              <div class="px-4 py-3 border-b border-gray-100">
                <div class="flex items-center">
                  <div class="w-10 h-10 bg-gradient-to-br from-violet-500 to-purple-600 rounded-xl flex items-center justify-center">
                    <span class="text-white font-semibold">{{ userInitials }}</span>
                  </div>
                  <div class="ml-3 flex-1 min-w-0">
                    <p class="text-sm font-semibold text-gray-900 truncate">{{ userName }}</p>
                    <p class="text-xs text-gray-500 truncate">{{ userEmail }}</p>
                  </div>
                  <div class="flex items-center">
                    <span class="bg-green-100 text-green-800 text-xs font-medium px-2 py-1 rounded-full">Pro</span>
                  </div>
                </div>
              </div>

              <!-- Menu items -->
              <div class="py-1">
                <MenuItem v-slot="{ active }">
                  <button
                    :class="[
                      active ? 'bg-gray-50 text-gray-900' : 'text-gray-700',
                      'group flex w-full items-center px-4 py-2 text-sm font-medium'
                    ]"
                  >
                    <component :is="icons.user" class="w-4 h-4 mr-3 text-gray-400" />
                    Profile Settings
                  </button>
                </MenuItem>
                <MenuItem v-slot="{ active }">
                  <button
                    :class="[
                      active ? 'bg-gray-50 text-gray-900' : 'text-gray-700',
                      'group flex w-full items-center px-4 py-2 text-sm font-medium'
                    ]"
                  >
                    <component :is="icons.creditCard" class="w-4 h-4 mr-3 text-gray-400" />
                    Billing & Plans
                  </button>
                </MenuItem>
                <MenuItem v-slot="{ active }">
                  <button
                    :class="[
                      active ? 'bg-gray-50 text-gray-900' : 'text-gray-700',
                      'group flex w-full items-center px-4 py-2 text-sm font-medium'
                    ]"
                  >
                    <component :is="icons.settings" class="w-4 h-4 mr-3 text-gray-400" />
                    Preferences
                  </button>
                </MenuItem>
                <MenuItem v-slot="{ active }">
                  <button
                    :class="[
                      active ? 'bg-gray-50 text-gray-900' : 'text-gray-700',
                      'group flex w-full items-center px-4 py-2 text-sm font-medium'
                    ]"
                  >
                    <component :is="icons.help" class="w-4 h-4 mr-3 text-gray-400" />
                    Help & Support
                  </button>
                </MenuItem>
                <div class="border-t border-gray-100 my-1"></div>
                <MenuItem v-slot="{ active }">
                  <button
                    @click="$emit('logout')"
                    :class="[
                      active ? 'bg-red-50 text-red-700' : 'text-red-600',
                      'group flex w-full items-center px-4 py-2 text-sm font-medium'
                    ]"
                  >
                    <component :is="icons.logout" class="w-4 h-4 mr-3" />
                    Sign out
                  </button>
                </MenuItem>
              </div>
            </MenuItems>
          </Transition>
        </Menu>
      </div>
    </div>
  </header>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { Menu, MenuButton, MenuItems, MenuItem } from '@headlessui/vue'
import { useAuthStore } from '@/stores/auth'
import { useIcons } from '@/composables/useIcons'

interface Props {
  title: string
  subtitle?: string
}

defineProps<Props>()

defineEmits<{
  toggleSidebar: []
  search: [query: string]
  createDesign: [type?: string]
  logout: []
}>()

const authStore = useAuthStore()
const icons = useIcons()
const searchQuery = ref('')
const searchFocused = ref(false)

const userName = computed(() => authStore.user?.name || 'Demo User')
const userEmail = computed(() => authStore.user?.email || 'demo@example.com')
const userInitials = computed(() => {
  const name = userName.value
  return name
    .split(' ')
    .map(n => n[0])
    .join('')
    .toUpperCase()
    .slice(0, 2)
})
</script>

<template>
  <AppLayout
    title="My Designs"
    subtitle="Manage all your creative projects"
    @search="handleSearch"
  >
    <div class="p-6">
      <!-- Filter and Sort Bar -->
      <div class="bg-white rounded-lg shadow-sm p-4 mb-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between space-y-4 sm:space-y-0">
          <div class="flex items-center space-x-4">
            <select
              v-model="selectedCategory"
              class="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
            >
              <option value="">All Categories</option>
              <option value="social-media">Social Media</option>
              <option value="business">Business</option>
              <option value="marketing">Marketing</option>
              <option value="personal">Personal</option>
            </select>
            
            <select
              v-model="sortBy"
              class="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
            >
              <option value="updated">Recently Updated</option>
              <option value="created">Recently Created</option>
              <option value="title">Name A-Z</option>
            </select>
          </div>
          
          <div class="flex items-center space-x-2">
            <button
              @click="viewMode = 'grid'"
              :class="[
                'p-2 rounded-md',
                viewMode === 'grid' ? 'bg-blue-100 text-blue-600' : 'text-gray-400 hover:text-gray-600'
              ]"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path>
              </svg>
            </button>
            <button
              @click="viewMode = 'list'"
              :class="[
                'p-2 rounded-md',
                viewMode === 'list' ? 'bg-blue-100 text-blue-600' : 'text-gray-400 hover:text-gray-600'
              ]"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"></path>
              </svg>
            </button>
          </div>
        </div>
      </div>

      <!-- Designs Content -->
      <div class="bg-white rounded-lg shadow-sm p-6">
        <!-- Grid View -->
        <DesignGrid
          v-if="viewMode === 'grid'"
          :designs="filteredDesigns"
          :loading="loading"
          @create="createNewDesign"
          @open="openDesign"
          @edit="editDesign"
          @duplicate="duplicateDesign"
          @delete="deleteDesign"
        />

        <!-- List View -->
        <div v-else-if="viewMode === 'list'" class="space-y-4">
          <div
            v-for="design in filteredDesigns"
            :key="design.id"
            class="flex items-center p-4 border border-gray-200 rounded-lg hover:bg-gray-50 cursor-pointer"
            @click="openDesign(design)"
          >
            <div class="w-16 h-16 bg-gray-100 rounded-lg flex-shrink-0 overflow-hidden">
              <img
                v-if="design.thumbnail"
                :src="design.thumbnail"
                :alt="design.title"
                class="w-full h-full object-cover"
              />
              <div v-else class="w-full h-full flex items-center justify-center">
                <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                </svg>
              </div>
            </div>
            
            <div class="ml-4 flex-1">
              <h3 class="font-semibold text-gray-900">{{ design.title }}</h3>
              <p class="text-sm text-gray-500">{{ design.width }}×{{ design.height }}</p>
              <p class="text-sm text-gray-500">Updated {{ formatDate(design.updatedAt) }}</p>
            </div>
            
            <div class="flex items-center space-x-2">
              <button
                @click.stop="editDesign(design)"
                class="p-2 text-gray-400 hover:text-blue-600 rounded-md hover:bg-blue-50"
              >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path>
                </svg>
              </button>
              <button
                @click.stop="duplicateDesign(design)"
                class="p-2 text-gray-400 hover:text-green-600 rounded-md hover:bg-green-50"
              >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                </svg>
              </button>
              <button
                @click.stop="deleteDesign(design)"
                class="p-2 text-gray-400 hover:text-red-600 rounded-md hover:bg-red-50"
              >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                </svg>
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useDesignStore } from '@/stores/design'
import type { Design } from '@/types'

// Components
import AppLayout from '@/components/layout/AppLayout.vue'
import DesignGrid from '@/components/ui/DesignGrid.vue'

const router = useRouter()
const designStore = useDesignStore()

// State
const loading = ref(false)
const viewMode = ref<'grid' | 'list'>('grid')
const selectedCategory = ref('')
const sortBy = ref('updated')
const searchQuery = ref('')

// Computed
const filteredDesigns = computed(() => {
  let designs = [...designStore.designs]

  // Filter by search query
  if (searchQuery.value) {
    designs = designs.filter(design => 
      design.title.toLowerCase().includes(searchQuery.value.toLowerCase())
    )
  }

  // Filter by category
  if (selectedCategory.value) {
    // This would need to be implemented based on how you store design categories
    // For now, we'll just return all designs
  }

  // Sort designs
  designs.sort((a, b) => {
    switch (sortBy.value) {
      case 'updated':
        return new Date(b.updatedAt).getTime() - new Date(a.updatedAt).getTime()
      case 'created':
        return new Date(b.createdAt).getTime() - new Date(a.createdAt).getTime()
      case 'title':
        return a.title.localeCompare(b.title)
      default:
        return 0
    }
  })

  return designs
})

// Methods
const handleSearch = (query: string) => {
  searchQuery.value = query
}

const createNewDesign = () => {
  router.push('/editor')
}

const openDesign = (design: Design) => {
  router.push(`/editor/${design.id}`)
}

const editDesign = (design: Design) => {
  router.push(`/editor/${design.id}`)
}

const duplicateDesign = async (design: Design) => {
  try {
    await designStore.duplicateDesign(design.id)
    await loadDesigns()
  } catch (error) {
    console.error('Failed to duplicate design:', error)
  }
}

const deleteDesign = async (design: Design) => {
  if (confirm('Are you sure you want to delete this design?')) {
    try {
      await designStore.deleteDesign(design.id)
      await loadDesigns()
    } catch (error) {
      console.error('Failed to delete design:', error)
    }
  }
}

const formatDate = (date: string | Date) => {
  const d = new Date(date)
  const now = new Date()
  const diff = now.getTime() - d.getTime()
  const days = Math.floor(diff / (1000 * 60 * 60 * 24))
  
  if (days === 0) return 'Today'
  if (days === 1) return 'Yesterday'
  if (days < 7) return `${days} days ago`
  if (days < 30) return `${Math.floor(days / 7)} weeks ago`
  
  return d.toLocaleDateString()
}

const loadDesigns = async () => {
  loading.value = true
  try {
    await designStore.loadUserDesigns()
  } catch (error) {
    console.error('Failed to load designs:', error)
  } finally {
    loading.value = false
  }
}

// Lifecycle
onMounted(() => {
  loadDesigns()
})
</script>

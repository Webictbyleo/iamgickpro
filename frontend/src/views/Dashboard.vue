<template>
  <AppLayout
    title="Dashboard"
    subtitle="Welcome back! Ready to create something amazing?"
    @search="handleSearch"
  >
    <div class="space-y-8">
      <!-- Welcome Hero Section - Refined and Compact -->
      <div class="relative overflow-hidden bg-gradient-to-br from-violet-600 via-purple-600 to-fuchsia-600 rounded-2xl shadow-xl">
        <!-- Simplified background elements -->
        <div class="absolute inset-0">
          <div class="absolute top-0 -left-4 w-48 h-48 bg-purple-300 rounded-full mix-blend-multiply filter blur-xl opacity-20 animate-blob"></div>
          <div class="absolute top-0 -right-4 w-48 h-48 bg-yellow-300 rounded-full mix-blend-multiply filter blur-xl opacity-20 animate-blob animation-delay-2000"></div>
        </div>
        
        <!-- Glass morphism overlay -->
        <div class="absolute inset-0 bg-white/5 backdrop-blur-sm"></div>
        
        <div class="relative px-6 py-8">
          <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between">
            <div class="flex-1 mb-8 lg:mb-0">
              <!-- Compact Welcome Message -->
              <div class="flex items-center space-x-4 mb-6">
                <div class="w-14 h-14 bg-white/20 backdrop-blur-md rounded-2xl flex items-center justify-center border border-white/30">
                  <component :is="icons.sparkle" class="w-7 h-7 text-white" />
                </div>
                <div class="flex-1">
                  <div class="inline-flex items-center px-3 py-1 bg-white/20 backdrop-blur-sm rounded-full text-white/90 text-sm font-medium mb-2 border border-white/30">
                    <div class="w-2 h-2 bg-green-400 rounded-full mr-2 animate-pulse"></div>
                    Online & Ready
                  </div>
                  <h1 class="text-3xl lg:text-4xl font-bold text-white leading-tight">
                    Welcome back,
                    <span class="bg-gradient-to-r from-yellow-300 to-pink-300 bg-clip-text text-transparent">
                      {{ user?.firstName || 'Designer' }}
                    </span>!
                  </h1>
                  <p class="text-lg text-white/90 mt-2">
                    Your creative studio awaits. Let's bring your vision to life.
                  </p>
                </div>
              </div>
              
              <!-- Compact Quick Stats -->
              <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-white/15 backdrop-blur-md rounded-xl p-4 border border-white/20 hover:border-white/40 transition-all duration-300">
                  <div class="flex items-center justify-between mb-2">
                    <component :is="icons.design" class="w-6 h-6 text-blue-300" />
                    <div class="text-xs text-white/70 bg-white/20 px-2 py-0.5 rounded-full">+12%</div>
                  </div>
                  <div class="text-2xl font-bold text-white">{{ dashboardStats?.total_designs || 0 }}</div>
                  <div class="text-sm text-white/80">Total Designs</div>
                </div>
                
                <div class="bg-white/15 backdrop-blur-md rounded-xl p-4 border border-white/20 hover:border-white/40 transition-all duration-300">
                  <div class="flex items-center justify-between mb-2">
                    <component :is="icons.template" class="w-6 h-6 text-purple-300" />
                    <div class="text-xs text-white/70 bg-white/20 px-2 py-0.5 rounded-full">+8%</div>
                  </div>
                  <div class="text-2xl font-bold text-white">{{ dashboardStats?.total_templates_used || 0 }}</div>
                  <div class="text-sm text-white/80">Templates Used</div>
                </div>
                
                <div class="bg-white/15 backdrop-blur-md rounded-xl p-4 border border-white/20 hover:border-white/40 transition-all duration-300">
                  <div class="flex items-center justify-between mb-2">
                    <component :is="icons.folder" class="w-6 h-6 text-green-300" />
                    <div class="text-xs text-white/70 bg-white/20 px-2 py-0.5 rounded-full">+5%</div>
                  </div>
                  <div class="text-2xl font-bold text-white">{{ dashboardStats?.active_projects || 0 }}</div>
                  <div class="text-sm text-white/80">Active Projects</div>
                </div>
                
                <div class="bg-white/15 backdrop-blur-md rounded-xl p-4 border border-white/20 hover:border-white/40 transition-all duration-300">
                  <div class="flex items-center justify-between mb-2">
                    <component :is="icons.download" class="w-6 h-6 text-orange-300" />
                    <div class="text-xs text-white/70 bg-white/20 px-2 py-0.5 rounded-full">+15%</div>
                  </div>
                  <div class="text-2xl font-bold text-white">{{ dashboardStats?.export_count || 0 }}</div>
                  <div class="text-sm text-white/80">Total Exports</div>
                </div>
              </div>
            </div>
            
            <!-- Compact Action Buttons -->
            <div class="flex flex-col space-y-3 lg:ml-8">
              <!-- Primary CTA -->
              <button
                @click="() => createNewDesign()"
                class="group relative bg-white text-violet-600 px-8 py-4 rounded-xl font-bold text-lg hover:bg-gray-50 transition-all duration-300 transform hover:scale-105 shadow-xl flex items-center justify-center space-x-3"
              >
                <component :is="icons.plus" class="w-6 h-6 group-hover:rotate-90 transition-transform duration-300" />
                <span>Create Design</span>
              </button>
              
              <!-- Secondary Action -->
              <router-link
                to="/templates"
                class="group w-full bg-white/15 backdrop-blur-md text-white px-8 py-3 rounded-xl font-semibold hover:bg-white/25 transition-all duration-300 border border-white/30 hover:border-white/50 flex items-center justify-center space-x-3"
              >
                <component :is="icons.template" class="w-5 h-5" />
                <span>Browse Templates</span>
                <component :is="icons.arrowRight" class="w-4 h-4 group-hover:translate-x-1 transition-transform duration-300" />
              </router-link>
            </div>
          </div>
        </div>
      </div>

      <!-- Quick Actions Grid -->
      <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-8">
        <h2 class="text-2xl font-bold text-gray-900 mb-6">Quick Actions</h2>
        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
          <button
            @click="() => createNewDesign()"
            class="group p-6 bg-gradient-to-br from-blue-50 to-indigo-50 rounded-2xl hover:from-blue-100 hover:to-indigo-100 transition-all transform hover:scale-105 border border-blue-100"
          >
            <div class="w-12 h-12 bg-blue-500 rounded-xl flex items-center justify-center mb-4 group-hover:bg-blue-600 transition-colors">
              <component :is="icons.plus" class="w-6 h-6 text-white" />
            </div>
            <h3 class="font-semibold text-gray-900 text-sm">New Design</h3>
            <p class="text-xs text-gray-500 mt-1">Start from scratch</p>
          </button>

          <router-link
            to="/templates"
            class="group p-6 bg-gradient-to-br from-green-50 to-emerald-50 rounded-2xl hover:from-green-100 hover:to-emerald-100 transition-all transform hover:scale-105 border border-green-100"
          >
            <div class="w-12 h-12 bg-green-500 rounded-xl flex items-center justify-center mb-4 group-hover:bg-green-600 transition-colors">
              <component :is="icons.template" class="w-6 h-6 text-white" />
            </div>
            <h3 class="font-semibold text-gray-900 text-sm">Templates</h3>
            <p class="text-xs text-gray-500 mt-1">Browse library</p>
          </router-link>

          <router-link
            to="/media"
            class="group p-6 bg-gradient-to-br from-purple-50 to-violet-50 rounded-2xl hover:from-purple-100 hover:to-violet-100 transition-all transform hover:scale-105 border border-purple-100"
          >
            <div class="w-12 h-12 bg-purple-500 rounded-xl flex items-center justify-center mb-4 group-hover:bg-purple-600 transition-colors">
              <component :is="icons.media" class="w-6 h-6 text-white" />
            </div>
            <h3 class="font-semibold text-gray-900 text-sm">Stock Media</h3>
            <p class="text-xs text-gray-500 mt-1">Photos & videos</p>
          </router-link>

          <router-link
            to="/designs"
            class="group p-6 bg-gradient-to-br from-orange-50 to-amber-50 rounded-2xl hover:from-orange-100 hover:to-amber-100 transition-all transform hover:scale-105 border border-orange-100"
          >
            <div class="w-12 h-12 bg-orange-500 rounded-xl flex items-center justify-center mb-4 group-hover:bg-orange-600 transition-colors">
              <component :is="icons.folder" class="w-6 h-6 text-white" />
            </div>
            <h3 class="font-semibold text-gray-900 text-sm">My Designs</h3>
            <p class="text-xs text-gray-500 mt-1">View all projects</p>
          </router-link>

          <button
            @click="createNewDesign(1080, 1080)"
            class="group p-6 bg-gradient-to-br from-pink-50 to-rose-50 rounded-2xl hover:from-pink-100 hover:to-rose-100 transition-all transform hover:scale-105 border border-pink-100"
          >
            <div class="w-12 h-12 bg-pink-500 rounded-xl flex items-center justify-center mb-4 group-hover:bg-pink-600 transition-colors">
              <component :is="icons.image" class="w-6 h-6 text-white" />
            </div>
            <h3 class="font-semibold text-gray-900 text-sm">Social Post</h3>
            <p class="text-xs text-gray-500 mt-1">1080×1080</p>
          </button>

          <button
            @click="createNewDesign(1920, 1080)"
            class="group p-6 bg-gradient-to-br from-cyan-50 to-teal-50 rounded-2xl hover:from-cyan-100 hover:to-teal-100 transition-all transform hover:scale-105 border border-cyan-100"
          >
            <div class="w-12 h-12 bg-cyan-500 rounded-xl flex items-center justify-center mb-4 group-hover:bg-cyan-600 transition-colors">
              <component :is="icons.video" class="w-6 h-6 text-white" />
            </div>
            <h3 class="font-semibold text-gray-900 text-sm">Video</h3>
            <p class="text-xs text-gray-500 mt-1">1920×1080</p>
          </button>
        </div>
      </div>

      <!-- Recent Designs Section -->
      <div class="bg-white rounded-2xl shadow-sm border border-gray-100">
        <div class="p-8 border-b border-gray-100">
          <div class="flex items-center justify-between">
            <div>
              <h2 class="text-2xl font-bold text-gray-900">Recent Designs</h2>
              <p class="text-gray-600 mt-1">Continue working on your latest projects</p>
            </div>
            <router-link
              to="/designs" 
              class="inline-flex items-center px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl font-medium transition-colors"
            >
              View all designs
              <ArrowRightIcon class="w-4 h-4 ml-2" />
            </router-link>
          </div>
        </div>
        
        <div class="p-8">
          <DesignGrid
            :designs="recentDesigns"
            :loading="loading"
            @create="createNewDesign"
            @open="openDesign"
            @edit="editDesign"
            @duplicate="duplicateDesign"
            @delete="deleteDesign"
          />
        </div>
      </div>

      <!-- Featured Templates Section -->
      <div class="bg-white rounded-2xl shadow-sm border border-gray-100">
        <div class="p-8 border-b border-gray-100">
          <div class="flex items-center justify-between">
            <div>
              <h2 class="text-2xl font-bold text-gray-900">Featured Templates</h2>
              <p class="text-gray-600 mt-1">Professional designs to get you started quickly</p>
            </div>
            <router-link
              to="/templates"
              class="inline-flex items-center px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl font-medium transition-colors"
            >
              Browse all
              <ArrowRightIcon class="w-4 h-4 ml-2" />
            </router-link>
          </div>
        </div>
        
        <div class="p-8">
          <TemplateGrid
            title=""
            :templates="featuredTemplates"
            :loading="templatesLoading"
            :show-view-all="false"
            @select="useTemplate"
          />
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { 
  ArrowRightIcon 
} from '@heroicons/vue/24/outline'
import { useAuthStore } from '@/stores/auth'
import { useDesignStore } from '@/stores/design'
import { useIcons } from '@/composables/useIcons'
import { analyticsAPI, templateAPI } from '@/services/api'
import type { Design, Template } from '@/types'

// Components
import AppLayout from '@/components/layout/AppLayout.vue'
import DesignGrid from '@/components/ui/DesignGrid.vue'
import TemplateGrid from '@/components/ui/TemplateGrid.vue'

const router = useRouter()
const authStore = useAuthStore()
const designStore = useDesignStore()
const icons = useIcons()

// State
const loading = ref(false)
const templatesLoading = ref(false)
const statsLoading = ref(false)
const recentDesigns = ref<Design[]>([])
const featuredTemplates = ref<Template[]>([])
const dashboardStats = ref<{
  total_designs: number
  total_templates_used: number
  active_projects: number
  storage_used: number
  export_count: number
} | null>(null)

// Computed
const user = computed(() => authStore.user)

const formatDesignCount = (count: number): string => {
  if (count >= 1000000) {
    return `${(count / 1000000).toFixed(1)}M`
  } else if (count >= 1000) {
    return `${(count / 1000).toFixed(1)}K`
  }
  return count.toString()
}

const formatLastLogin = (date: Date): string => {
  const now = new Date()
  const diffMs = now.getTime() - date.getTime()
  const diffHours = Math.floor(diffMs / (1000 * 60 * 60))
  
  if (diffHours < 1) return 'Just now'
  if (diffHours < 24) return `${diffHours}h ago`
  const diffDays = Math.floor(diffHours / 24)
  if (diffDays < 7) return `${diffDays}d ago`
  return date.toLocaleDateString()
}

// Methods
const handleSearch = (query: string) => {
  router.push(`/designs?search=${encodeURIComponent(query)}`)
}

const createNewDesign = (width = 800, height = 600) => {
  const newDesign = designStore.createNewDesign(width, height)
  router.push(`/editor/${newDesign.id}`)
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
    await loadRecentDesigns()
  } catch (error) {
    console.error('Failed to duplicate design:', error)
  }
}

const deleteDesign = async (design: Design) => {
  if (confirm('Are you sure you want to delete this design?')) {
    try {
      await designStore.deleteDesign(design.id)
      await loadRecentDesigns()
    } catch (error) {
      console.error('Failed to delete design:', error)
    }
  }
}

const useTemplate = async (template: Template) => {
  // Create new design from template
  const newDesign = designStore.createNewDesign(
    template.width,
    template.height
  )
  
  // Copy template data to design
  if (template.designData) {
    newDesign.designData = { ...template.designData }
  }
  
  newDesign.name = `${template.name} Copy`
  
  // Save the design first
  const result = await designStore.saveDesign(newDesign)
  
  if (result.success) {
    router.push(`/editor/${newDesign.id}`)
  } else {
    console.error('Failed to create design from template:', result.error)
  }
}

const loadDashboardStats = async () => {
  try {
    statsLoading.value = true
    const response = await analyticsAPI.getDashboardStats()
    
    if (response.data?.data) {
      dashboardStats.value = response.data.data
    }
  } catch (error) {
    console.error('Failed to load dashboard stats:', error)
    // Fallback to mock data for development
    dashboardStats.value = {
      total_designs: designStore.designs.length,
      total_templates_used: 24,
      active_projects: 6,
      storage_used: 1024,
      export_count: 48
    }
  } finally {
    statsLoading.value = false
  }
}

const loadRecentDesigns = async () => {
  loading.value = true
  try {
    await designStore.loadUserDesigns()
    recentDesigns.value = designStore.designs.slice(0, 6) // Show only recent 6
  } catch (error) {
    console.error('Failed to load recent designs:', error)
  } finally {
    loading.value = false
  }
}

const loadFeaturedTemplates = async () => {
  templatesLoading.value = true
  try {
    const response = await templateAPI.getTemplates({
      page: 1,
      limit: 6
    })
    
    if (response.data?.data) {
      featuredTemplates.value = response.data.data
    }
  } catch (error) {
    console.error('Failed to load featured templates:', error)
    // Fallback to mock data for development
    featuredTemplates.value = [
      {
        id: '1',
        uuid: 'template-1',
        name: 'Social Media Post',
        category: 'Social Media',
        thumbnail: 'https://images.unsplash.com/photo-1611224923853-80b023f02d71?w=300&h=300&fit=crop',
        thumbnailUrl: 'https://images.unsplash.com/photo-1611224923853-80b023f02d71?w=300&h=300&fit=crop',
        previewUrl: 'https://images.unsplash.com/photo-1611224923853-80b023f02d71?w=600&h=600&fit=crop',
        width: 1080,
        height: 1080,
        isPremium: false,
        isActive: true,
        rating: 4.5,
        ratingCount: 150,
        usageCount: 1200,
        tags: ['social', 'instagram'],
        createdAt: new Date().toISOString(),
        updatedAt: new Date().toISOString()
      },
      {
        id: '2',
        uuid: 'template-2',
        name: 'Business Card',
        category: 'Business',
        thumbnail: 'https://images.unsplash.com/photo-1586953208448-b95a79798f07?w=300&h=200&fit=crop',
        thumbnailUrl: 'https://images.unsplash.com/photo-1586953208448-b95a79798f07?w=300&h=200&fit=crop',
        previewUrl: 'https://images.unsplash.com/photo-1586953208448-b95a79798f07?w=600&h=400&fit=crop',
        width: 1050,
        height: 600,
        isPremium: true,
        isActive: true,
        rating: 4.8,
        ratingCount: 89,
        usageCount: 650,
        tags: ['business', 'professional'],
        createdAt: new Date().toISOString(),
        updatedAt: new Date().toISOString()
      },
      {
        id: '3',
        uuid: 'template-3',
        name: 'Logo Design',
        category: 'Branding',
        thumbnail: 'https://images.unsplash.com/photo-1599305445671-ac291c95aaa9?w=300&h=300&fit=crop',
        thumbnailUrl: 'https://images.unsplash.com/photo-1599305445671-ac291c95aaa9?w=300&h=300&fit=crop',
        previewUrl: 'https://images.unsplash.com/photo-1599305445671-ac291c95aaa9?w=600&h=600&fit=crop',
        width: 500,
        height: 500,
        isPremium: false,
        isActive: true,
        rating: 4.3,
        ratingCount: 205,
        usageCount: 980,
        tags: ['logo', 'branding'],
        createdAt: new Date().toISOString(),
        updatedAt: new Date().toISOString()
      },
      {
        id: '4',
        uuid: 'template-4',
        name: 'YouTube Thumbnail',
        category: 'Social Media',
        thumbnail: 'https://images.unsplash.com/photo-1611162617474-5b21e879e113?w=300&h=200&fit=crop',
        thumbnailUrl: 'https://images.unsplash.com/photo-1611162617474-5b21e879e113?w=300&h=200&fit=crop',
        previewUrl: 'https://images.unsplash.com/photo-1611162617474-5b21e879e113?w=600&h=400&fit=crop',
        width: 1280,
        height: 720,
        isPremium: false,
        isActive: true,
        rating: 4.6,
        ratingCount: 320,
        usageCount: 1500,
        tags: ['youtube', 'thumbnail'],
        createdAt: new Date().toISOString(),
        updatedAt: new Date().toISOString()
      },
      {
        id: '5',
        uuid: 'template-5',
        name: 'Presentation Slide',
        category: 'Presentation',
        thumbnail: 'https://images.unsplash.com/photo-1460925895917-afdab827c52f?w=300&h=200&fit=crop',
        thumbnailUrl: 'https://images.unsplash.com/photo-1460925895917-afdab827c52f?w=300&h=200&fit=crop',
        previewUrl: 'https://images.unsplash.com/photo-1460925895917-afdab827c52f?w=600&h=400&fit=crop',
        width: 1920,
        height: 1080,
        isPremium: true,
        isActive: true,
        rating: 4.7,
        ratingCount: 156,
        usageCount: 890,
        tags: ['presentation', 'slide'],
        createdAt: new Date().toISOString(),
        updatedAt: new Date().toISOString()
      },
      {
        id: '6',
        uuid: 'template-6',
        name: 'Instagram Story',
        category: 'Social Media',
        thumbnail: 'https://images.unsplash.com/photo-1611224923853-80b023f02d71?w=200&h=350&fit=crop',
        thumbnailUrl: 'https://images.unsplash.com/photo-1611224923853-80b023f02d71?w=200&h=350&fit=crop',
        previewUrl: 'https://images.unsplash.com/photo-1611224923853-80b023f02d71?w=400&h=700&fit=crop',
        width: 1080,
        height: 1920,
        isPremium: false,
        isActive: true,
        rating: 4.4,
        ratingCount: 278,
        usageCount: 1350,
        tags: ['instagram', 'story'],
        createdAt: new Date().toISOString(),
        updatedAt: new Date().toISOString()
      }
    ]
  } finally {
    templatesLoading.value = false
  }
}

// Lifecycle hooks
onMounted(async () => {
  try {
    await Promise.all([
      loadDashboardStats(),
      loadRecentDesigns(),
      loadFeaturedTemplates()
    ])
  } catch (error) {
    console.error('Failed to load dashboard data:', error)
  }
})


</script>

<style scoped>
@keyframes blob {
  0% {
    transform: translate(0px, 0px) scale(1);
  }
  33% {
    transform: translate(30px, -50px) scale(1.1);
  }
  66% {
    transform: translate(-20px, 20px) scale(0.9);
  }
  100% {
    transform: translate(0px, 0px) scale(1);
  }
}

.animate-blob {
  animation: blob 7s infinite;
}

.animation-delay-2000 {
  animation-delay: 2s;
}

.animation-delay-4000 {
  animation-delay: 4s;
}
</style>

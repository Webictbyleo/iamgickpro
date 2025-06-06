import { ref, type Ref } from 'vue'
import { mediaAPI } from '@/services/api'
import type { MediaItem } from '@/types'

export interface StockMediaItem {
  id: string
  thumbnail: string
  src: string
  alt: string
  tags?: string[]
  source?: string
}

interface UseStockMediaReturn {
  // State
  stockPhotos: Ref<StockMediaItem[]>
  stockIcons: Ref<StockMediaItem[]>
  stockVideos: Ref<StockMediaItem[]>
  isLoadingPhotos: Ref<boolean>
  isLoadingIcons: Ref<boolean>
  isLoadingVideos: Ref<boolean>
  
  // Pagination
  photosPage: Ref<number>
  iconsPage: Ref<number>
  videosPage: Ref<number>
  hasMorePhotos: Ref<boolean>
  hasMoreIcons: Ref<boolean>
  hasMoreVideos: Ref<boolean>
  
  // Methods
  searchPhotos: (query: string) => Promise<void>
  searchIcons: (query: string) => Promise<void>
  searchVideos: (query: string) => Promise<void>
  loadMorePhotos: () => Promise<void>
  loadMoreIcons: () => Promise<void>
  loadMoreVideos: () => Promise<void>
  clearResults: () => void
  transformMediaItem: (item: MediaItem) => StockMediaItem
}

export function useStockMedia(): UseStockMediaReturn {
  // State
  const stockPhotos = ref<StockMediaItem[]>([])
  const stockIcons = ref<StockMediaItem[]>([])
  const stockVideos = ref<StockMediaItem[]>([])
  
  const isLoadingPhotos = ref(false)
  const isLoadingIcons = ref(false)
  const isLoadingVideos = ref(false)
  
  // Pagination
  const photosPage = ref(1)
  const iconsPage = ref(1)
  const videosPage = ref(1)
  const hasMorePhotos = ref(true)
  const hasMoreIcons = ref(true)
  const hasMoreVideos = ref(true)
  
  // Current search queries
  const currentPhotoQuery = ref('')
  const currentIconQuery = ref('')
  const currentVideoQuery = ref('')
  
  // API limits
  const ITEMS_PER_PAGE = 24
  
  /**
   * Search for stock photos
   */
  const searchPhotos = async (query: string) => {
    if (isLoadingPhotos.value) return
    
    try {
      isLoadingPhotos.value = true
      currentPhotoQuery.value = query
      photosPage.value = 1
      
      const response = await mediaAPI.getStockMedia({
        query,
        category: 'image',
        page: 1,
        limit: ITEMS_PER_PAGE
      })
      
      if (response.data?.data?.media) {
        stockPhotos.value = response.data.data.media.map(transformMediaItem)
        hasMorePhotos.value = response.data.data.media.length === ITEMS_PER_PAGE
      } else {
        stockPhotos.value = []
        hasMorePhotos.value = false
      }
    } catch (error) {
      console.error('Failed to search stock photos:', error)
      stockPhotos.value = []
      hasMorePhotos.value = false
    } finally {
      isLoadingPhotos.value = false
    }
  }
  
  /**
   * Search for stock icons
   */
  const searchIcons = async (query: string) => {
    if (isLoadingIcons.value) return
    
    try {
      isLoadingIcons.value = true
      currentIconQuery.value = query
      iconsPage.value = 1
      
      const response = await mediaAPI.getStockMedia({
        query,
        category: 'icon',
        page: 1,
        limit: ITEMS_PER_PAGE
      })
      
      if (response.data?.data?.media) {
        stockIcons.value = response.data.data.media.map(transformMediaItem)
        hasMoreIcons.value = response.data.data.media.length === ITEMS_PER_PAGE
      } else {
        stockIcons.value = []
        hasMoreIcons.value = false
      }
    } catch (error) {
      console.error('Failed to search stock icons:', error)
      stockIcons.value = []
      hasMoreIcons.value = false
    } finally {
      isLoadingIcons.value = false
    }
  }
  
  /**
   * Search for stock videos
   */
  const searchVideos = async (query: string) => {
    if (isLoadingVideos.value) return
    
    try {
      isLoadingVideos.value = true
      currentVideoQuery.value = query
      videosPage.value = 1
      
      const response = await mediaAPI.getStockMedia({
        query,
        category: 'video',
        page: 1,
        limit: ITEMS_PER_PAGE
      })
      
      if (response.data?.data?.media) {
        stockVideos.value = response.data.data.media.map(transformMediaItem)
        hasMoreVideos.value = response.data.data.media.length === ITEMS_PER_PAGE
      } else {
        stockVideos.value = []
        hasMoreVideos.value = false
      }
    } catch (error) {
      console.error('Failed to search stock videos:', error)
      stockVideos.value = []
      hasMoreVideos.value = false
    } finally {
      isLoadingVideos.value = false
    }
  }
  
  /**
   * Load more stock photos
   */
  const loadMorePhotos = async () => {
    if (isLoadingPhotos.value || !hasMorePhotos.value || !currentPhotoQuery.value) return
    
    try {
      isLoadingPhotos.value = true
      photosPage.value += 1
      
      const response = await mediaAPI.getStockMedia({
        query: currentPhotoQuery.value,
        category: 'image',
        page: photosPage.value,
        limit: ITEMS_PER_PAGE
      })
      
      if (response.data?.data?.media && response.data.data.media.length > 0) {
        const newPhotos = response.data.data.media.map(transformMediaItem)
        stockPhotos.value.push(...newPhotos)
        hasMorePhotos.value = response.data.data.media.length === ITEMS_PER_PAGE
      } else {
        hasMorePhotos.value = false
      }
    } catch (error) {
      console.error('Failed to load more photos:', error)
      hasMorePhotos.value = false
    } finally {
      isLoadingPhotos.value = false
    }
  }
  
  /**
   * Load more stock icons
   */
  const loadMoreIcons = async () => {
    if (isLoadingIcons.value || !hasMoreIcons.value || !currentIconQuery.value) return
    
    try {
      isLoadingIcons.value = true
      iconsPage.value += 1
      
      const response = await mediaAPI.getStockMedia({
        query: currentIconQuery.value,
        category: 'icon',
        page: iconsPage.value,
        limit: ITEMS_PER_PAGE
      })
      
      if (response.data?.data?.media && response.data.data.media.length > 0) {
        const newIcons = response.data.data.media.map(transformMediaItem)
        stockIcons.value.push(...newIcons)
        hasMoreIcons.value = response.data.data.media.length === ITEMS_PER_PAGE
      } else {
        hasMoreIcons.value = false
      }
    } catch (error) {
      console.error('Failed to load more icons:', error)
      hasMoreIcons.value = false
    } finally {
      isLoadingIcons.value = false
    }
  }
  
  /**
   * Load more stock videos
   */
  const loadMoreVideos = async () => {
    if (isLoadingVideos.value || !hasMoreVideos.value || !currentVideoQuery.value) return
    
    try {
      isLoadingVideos.value = true
      videosPage.value += 1
      
      const response = await mediaAPI.getStockMedia({
        query: currentVideoQuery.value,
        category: 'video',
        page: videosPage.value,
        limit: ITEMS_PER_PAGE
      })
      
      if (response.data?.data?.media && response.data.data.media.length > 0) {
        const newVideos = response.data.data.media.map(transformMediaItem)
        stockVideos.value.push(...newVideos)
        hasMoreVideos.value = response.data.data.media.length === ITEMS_PER_PAGE
      } else {
        hasMoreVideos.value = false
      }
    } catch (error) {
      console.error('Failed to load more videos:', error)
      hasMoreVideos.value = false
    } finally {
      isLoadingVideos.value = false
    }
  }
  
  /**
   * Clear all search results
   */
  const clearResults = () => {
    stockPhotos.value = []
    stockIcons.value = []
    stockVideos.value = []
    photosPage.value = 1
    iconsPage.value = 1
    videosPage.value = 1
    hasMorePhotos.value = true
    hasMoreIcons.value = true
    hasMoreVideos.value = true
    currentPhotoQuery.value = ''
    currentIconQuery.value = ''
    currentVideoQuery.value = ''
  }
  
  /**
   * Transform API media item to stock media format
   */
  const transformMediaItem = (item: MediaItem): StockMediaItem => {
    return {
      id: item.id,
      thumbnail: item.thumbnail || item.url,
      src: item.url,
      alt: item.name || 'Stock media',
      tags: item.tags || [],
      source: item.source || 'stock'
    }
  }
  
  return {
    // State
    stockPhotos,
    stockIcons,
    stockVideos,
    isLoadingPhotos,
    isLoadingIcons,
    isLoadingVideos,
    
    // Pagination
    photosPage,
    iconsPage,
    videosPage,
    hasMorePhotos,
    hasMoreIcons,
    hasMoreVideos,
    
    // Methods
    searchPhotos,
    searchIcons,
    searchVideos,
    loadMorePhotos,
    loadMoreIcons,
    loadMoreVideos,
    clearResults,
    transformMediaItem
  }
}

// Predefined search suggestions for different categories
export const stockMediaSuggestions = {
  photos: [
    'business', 'technology', 'nature', 'people', 'abstract', 'travel',
    'food', 'architecture', 'lifestyle', 'workspace', 'team', 'success'
  ],
  icons: [
    'arrow', 'social', 'business', 'communication', 'interface', 'navigation',
    'shopping', 'education', 'medical', 'finance', 'weather', 'transport'
  ],
  videos: [
    'motion graphics', 'background', 'abstract', 'nature', 'business',
    'technology', 'particles', 'geometric', 'overlay', 'transition'
  ]
}

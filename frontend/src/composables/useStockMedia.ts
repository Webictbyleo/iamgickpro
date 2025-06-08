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
  stockShapes: Ref<StockMediaItem[]>
  stockVideos: Ref<StockMediaItem[]>
  isLoadingPhotos: Ref<boolean>
  isLoadingIcons: Ref<boolean>
  isLoadingShapes: Ref<boolean>
  isLoadingVideos: Ref<boolean>
  
  // Pagination
  photosPage: Ref<number>
  iconsPage: Ref<number>
  shapesPage: Ref<number>
  videosPage: Ref<number>
  hasMorePhotos: Ref<boolean>
  hasMoreIcons: Ref<boolean>
  hasMoreShapes: Ref<boolean>
  hasMoreVideos: Ref<boolean>
  
  // Methods
  searchPhotos: (query: string) => Promise<void>
  searchIcons: (query: string) => Promise<void>
  searchShapes: (query: string) => Promise<void>
  searchVideos: (query: string) => Promise<void>
  loadMorePhotos: () => Promise<void>
  loadMoreIcons: () => Promise<void>
  loadMoreShapes: () => Promise<void>
  loadMoreVideos: () => Promise<void>
  clearResults: () => void
  transformMediaItem: (item: MediaItem) => StockMediaItem
}

export function useStockMedia(): UseStockMediaReturn {
  // State
  const stockPhotos = ref<StockMediaItem[]>([])
  const stockIcons = ref<StockMediaItem[]>([])
  const stockShapes = ref<StockMediaItem[]>([])
  const stockVideos = ref<StockMediaItem[]>([])
  
  const isLoadingPhotos = ref(false)
  const isLoadingIcons = ref(false)
  const isLoadingShapes = ref(false)
  const isLoadingVideos = ref(false)
  
  // Pagination
  const photosPage = ref(1)
  const iconsPage = ref(1)
  const shapesPage = ref(1)
  const videosPage = ref(1)
  const hasMorePhotos = ref(true)
  const hasMoreIcons = ref(true)
  const hasMoreShapes = ref(true)
  const hasMoreVideos = ref(true)
  
  // Current search queries
  const currentPhotoQuery = ref('')
  const currentIconQuery = ref('')
  const currentShapeQuery = ref('')
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
      
      const response = await mediaAPI.searchStockMedia({
        query,
        type: 'image',
        page: 1,
        limit: ITEMS_PER_PAGE
      })
      
      if (response.data?.data) {
        stockPhotos.value = response.data.data.map(transformMediaItem)
        hasMorePhotos.value = response.data.data.length === ITEMS_PER_PAGE
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
      
      const response = await mediaAPI.searchStockMedia({
        query,
        type: 'icon', // Use icon type for icon searches
        page: 1,
        limit: ITEMS_PER_PAGE
      })
      
      if (response.data?.data) {
        stockIcons.value = response.data.data.map(transformMediaItem)
        hasMoreIcons.value = response.data.data.length === ITEMS_PER_PAGE
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
   * Search for stock shapes
   */
  const searchShapes = async (query: string) => {
    if (isLoadingShapes.value) return
    
    try {
      isLoadingShapes.value = true
      currentShapeQuery.value = query
      shapesPage.value = 1
      
      const response = await mediaAPI.searchStockMedia({
        query,
        type: 'shape',
        page: 1,
        limit: ITEMS_PER_PAGE
      })
      
      if (response.data?.data) {
        stockShapes.value = response.data.data.map(transformMediaItem)
        hasMoreShapes.value = response.data.data.length === ITEMS_PER_PAGE
      } else {
        stockShapes.value = []
        hasMoreShapes.value = false
      }
    } catch (error) {
      console.error('Failed to search stock shapes:', error)
      stockShapes.value = []
      hasMoreShapes.value = false
    } finally {
      isLoadingShapes.value = false
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
      
      const response = await mediaAPI.searchStockMedia({
        query,
        type: 'video',
        page: 1,
        limit: ITEMS_PER_PAGE
      })
      
      if (response.data?.data) {
        stockVideos.value = response.data.data.map(transformMediaItem)
        hasMoreVideos.value = response.data.data.length === ITEMS_PER_PAGE
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
      
      const response = await mediaAPI.searchStockMedia({
        query: currentPhotoQuery.value,
        type: 'image',
        page: photosPage.value,
        limit: ITEMS_PER_PAGE
      })
      
      if (response.data?.data && response.data.data.length > 0) {
        const newPhotos = response.data.data.map(transformMediaItem)
        stockPhotos.value.push(...newPhotos)
        hasMorePhotos.value = response.data.data.length === ITEMS_PER_PAGE
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
      
      const response = await mediaAPI.searchStockMedia({
        query: currentIconQuery.value,
        type: 'icon',
        page: iconsPage.value,
        limit: ITEMS_PER_PAGE
      })
      
      if (response.data?.data && response.data.data.length > 0) {
        const newIcons = response.data.data.map(transformMediaItem)
        stockIcons.value.push(...newIcons)
        hasMoreIcons.value = response.data.data.length === ITEMS_PER_PAGE
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
   * Load more stock shapes
   */
  const loadMoreShapes = async () => {
    if (isLoadingShapes.value || !hasMoreShapes.value || !currentShapeQuery.value) return
    
    try {
      isLoadingShapes.value = true
      shapesPage.value += 1
      
      const response = await mediaAPI.searchStockMedia({
        query: currentShapeQuery.value,
        type: 'shape',
        page: shapesPage.value,
        limit: ITEMS_PER_PAGE
      })
      
      if (response.data?.data && response.data.data.length > 0) {
        const newShapes = response.data.data.map(transformMediaItem)
        stockShapes.value.push(...newShapes)
        hasMoreShapes.value = response.data.data.length === ITEMS_PER_PAGE
      } else {
        hasMoreShapes.value = false
      }
    } catch (error) {
      console.error('Failed to load more shapes:', error)
      hasMoreShapes.value = false
    } finally {
      isLoadingShapes.value = false
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
      
      const response = await mediaAPI.searchStockMedia({
        query: currentVideoQuery.value,
        type: 'video',
        page: videosPage.value,
        limit: ITEMS_PER_PAGE
      })
      
      if (response.data?.data && response.data.data.length > 0) {
        const newVideos = response.data.data.map(transformMediaItem)
        stockVideos.value.push(...newVideos)
        hasMoreVideos.value = response.data.data.length === ITEMS_PER_PAGE
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
    stockShapes.value = []
    stockVideos.value = []
    photosPage.value = 1
    iconsPage.value = 1
    shapesPage.value = 1
    videosPage.value = 1
    hasMorePhotos.value = true
    hasMoreIcons.value = true
    hasMoreShapes.value = true
    hasMoreVideos.value = true
    currentPhotoQuery.value = ''
    currentIconQuery.value = ''
    currentShapeQuery.value = ''
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
    stockShapes,
    stockVideos,
    isLoadingPhotos,
    isLoadingIcons,
    isLoadingShapes,
    isLoadingVideos,
    
    // Pagination
    photosPage,
    iconsPage,
    shapesPage,
    videosPage,
    hasMorePhotos,
    hasMoreIcons,
    hasMoreShapes,
    hasMoreVideos,
    
    // Methods
    searchPhotos,
    searchIcons,
    searchShapes,
    searchVideos,
    loadMorePhotos,
    loadMoreIcons,
    loadMoreShapes,
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
  shapes: [
    'geometric', 'basic', 'abstract', 'polygon', 'circle', 'square', 
    'triangle', 'star', 'arrow', 'frame', 'border', 'pattern'
  ],
  videos: [
    'motion graphics', 'background', 'abstract', 'nature', 'business',
    'technology', 'particles', 'geometric', 'overlay', 'transition'
  ]
}

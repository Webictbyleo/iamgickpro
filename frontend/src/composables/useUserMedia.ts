import { ref, type Ref } from 'vue'
import { mediaAPI } from '@/services/api'
import type { MediaItem } from '@/types'

export interface UserMediaReturn {
  // State
  userMedia: Ref<MediaItem[]>
  isLoadingUserMedia: Ref<boolean>
  
  // Pagination
  userMediaPage: Ref<number>
  hasMoreUserMedia: Ref<boolean>
  
  // Methods
  searchUserMedia: (query: string, type?: string) => Promise<void>
  loadMoreUserMedia: () => Promise<void>
  uploadFiles: (files: FileList) => Promise<void>
  clearUserMedia: () => void
}

export function useUserMedia(): UserMediaReturn {
  // State
  const userMedia = ref<MediaItem[]>([])
  const isLoadingUserMedia = ref(false)
  
  // Pagination
  const userMediaPage = ref(1)
  const hasMoreUserMedia = ref(true)
  
  // Current search parameters
  const currentQuery = ref('')
  const currentType = ref('')
  
  // API limits
  const ITEMS_PER_PAGE = 24
  
  /**
   * Search user's uploaded media
   */
  const searchUserMedia = async (query: string = '', type: string = '') => {
    if (isLoadingUserMedia.value) return
    
    try {
      isLoadingUserMedia.value = true
      currentQuery.value = query
      currentType.value = type
      userMediaPage.value = 1
      
      // Use the media list endpoint with search parameters
      const response = await mediaAPI.getMedia({
        search: query || undefined,
        type: (type as 'image' | 'video' | 'audio') || undefined,
        page: 1,
        limit: ITEMS_PER_PAGE
      })
      
      if (response.data?.data) {
        userMedia.value = response.data.data
        hasMoreUserMedia.value = response.data.data.length === ITEMS_PER_PAGE
      } else {
        userMedia.value = []
        hasMoreUserMedia.value = false
      }
    } catch (error) {
      console.error('Failed to search user media:', error)
      userMedia.value = []
      hasMoreUserMedia.value = false
    } finally {
      isLoadingUserMedia.value = false
    }
  }
  
  /**
   * Load more user media (pagination)
   */
  const loadMoreUserMedia = async () => {
    if (isLoadingUserMedia.value || !hasMoreUserMedia.value) return
    
    try {
      isLoadingUserMedia.value = true
      userMediaPage.value += 1
      
      const response = await mediaAPI.getMedia({
        search: currentQuery.value || undefined,
        type: (currentType.value as 'image' | 'video' | 'audio') || undefined,
        page: userMediaPage.value,
        limit: ITEMS_PER_PAGE
      })
      
      if (response.data?.data && response.data.data.length > 0) {
        userMedia.value.push(...response.data.data)
        hasMoreUserMedia.value = response.data.data.length === ITEMS_PER_PAGE
      } else {
        hasMoreUserMedia.value = false
      }
    } catch (error) {
      console.error('Failed to load more user media:', error)
      hasMoreUserMedia.value = false
    } finally {
      isLoadingUserMedia.value = false
    }
  }
  
  /**
   * Upload files to the server
   */
  const uploadFiles = async (files: FileList) => {
    if (isLoadingUserMedia.value) return
    
    try {
      isLoadingUserMedia.value = true
      
      // Upload files one by one
      for (const file of Array.from(files)) {
        const response = await mediaAPI.uploadMedia(file, {
          name: file.name
        })
        
        // The response has the structure: { success, message, timestamp, data: { media: MediaItem } }
        if (response.data?.data?.media) {
          // Add the uploaded media to the beginning of the list
          userMedia.value.unshift(response.data.data.media)
        }
      }
      
      // Note: We don't need to refresh the entire list since we're adding uploaded files directly
      
    } catch (error) {
      console.error('Failed to upload files:', error)
      throw error
    } finally {
      isLoadingUserMedia.value = false
    }
  }
  
  /**
   * Clear user media results
   */
  const clearUserMedia = () => {
    userMedia.value = []
    userMediaPage.value = 1
    hasMoreUserMedia.value = true
    currentQuery.value = ''
    currentType.value = ''
  }
  
  return {
    // State
    userMedia,
    isLoadingUserMedia,
    
    // Pagination
    userMediaPage,
    hasMoreUserMedia,
    
    // Methods
    searchUserMedia,
    loadMoreUserMedia,
    uploadFiles,
    clearUserMedia
  }
}

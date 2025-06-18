import { ref, computed } from 'vue'
import { useDesignStore } from '@/stores/design'
import { designAPI } from '@/services/api'
import { designRenderer } from '@/editor/sdk/DesignRenderer'
import { GeometryUtils } from '@/utils/GeometryUtils'
import type { Design } from '@/types'

export function useDesignPreview() {
  const designStore = useDesignStore()
  const isGenerating = ref(false)
  const lastPreviewUrl = ref<string>('')
  
  const currentDesign = computed(() => designStore.currentDesign)

  /**
   * Generate and save design thumbnail
   */
  const generateAndSaveThumbnail = async (
    design?: Design,
    options: {
      width?: number
      height?: number
      format?: 'png' | 'jpeg'
      quality?: number
      updateBackend?: boolean
    } = {}
  ): Promise<string | null> => {
    const targetDesign = design || currentDesign.value
    if (!targetDesign) {
      console.warn('No design available for thumbnail generation')
      return null
    }

    const {
      width = 300,
      height = 200,
      format = 'png',
      quality = 0.8,
      updateBackend = true
    } = options

    try {
      isGenerating.value = true

      // Generate thumbnail using the design renderer
      const thumbnailDataUrl = await designRenderer.generateThumbnail(targetDesign, {
        width,
        height,
        format,
        quality
      })

      lastPreviewUrl.value = thumbnailDataUrl

      // Update backend if requested
      if (updateBackend && targetDesign.id) {
        try {
          await designAPI.updateThumbnail(targetDesign.id, {
            thumbnail: thumbnailDataUrl,
            format
          })
          
          // Update local store
          if (currentDesign.value && currentDesign.value.id === targetDesign.id) {
            currentDesign.value.thumbnail = thumbnailDataUrl
          }
          
          console.log('✅ Design thumbnail updated successfully')
        } catch (error) {
          console.error('Failed to save thumbnail to backend:', error)
          // Continue with local thumbnail even if backend save fails
        }
      }

      return thumbnailDataUrl
    } catch (error) {
      console.error('Failed to generate design thumbnail:', error)
      return null
    } finally {
      isGenerating.value = false
    }
  }

  /**
   * Generate multiple preview sizes
   */
  const generateMultiplePreviews = async (
    design?: Design,
    sizes: Array<{
      name: string
      width: number
      height: number
      format?: 'png' | 'jpeg'
      quality?: number
    }> = [
      { name: 'thumbnail', width: 300, height: 200 },
      { name: 'small', width: 150, height: 100 },
      { name: 'medium', width: 600, height: 400 },
      { name: 'large', width: 1200, height: 800 }
    ]
  ): Promise<Record<string, string>> => {
    const targetDesign = design || currentDesign.value
    if (!targetDesign) {
      console.warn('No design available for preview generation')
      return {}
    }

    try {
      isGenerating.value = true
      return await designRenderer.generateMultiplePreviews(targetDesign, sizes)
    } catch (error) {
      console.error('Failed to generate multiple previews:', error)
      return {}
    } finally {
      isGenerating.value = false
    }
  }

  /**
   * Generate a quick preview for dashboard/list views
   */
  const generateQuickPreview = async (design?: Design): Promise<string | null> => {
    const targetDesign = design || currentDesign.value
    if (!targetDesign) {
      return null
    }

    // Calculate proper thumbnail dimensions for quick preview while preserving aspect ratio
    const designDimensions = {
      width: targetDesign.width || 800,
      height: targetDesign.height || 600
    }
    
    // Target maximum size for quick previews (smaller than main thumbnails)
    const maxQuickPreviewSize = { width: 240, height: 160 }
    
    // Use GeometryUtils to calculate dimensions that preserve aspect ratio
    const previewResult = GeometryUtils.resize(designDimensions, maxQuickPreviewSize, {
      mode: 'contain',
      allowUpscaling: false // Don't upscale small designs
    })

    return generateAndSaveThumbnail(targetDesign, {
      width: Math.round(previewResult.width),
      height: Math.round(previewResult.height),
      format: 'jpeg',
      quality: 0.7,
      updateBackend: false // Don't save quick previews to backend
    })
  }

  /**
   * Generate high-quality preview for sharing/exports
   */
  const generateHighQualityPreview = async (
    design?: Design,
    dimensions: { width: number; height: number } = { width: 1200, height: 800 }
  ): Promise<Blob | null> => {
    const targetDesign = design || currentDesign.value
    if (!targetDesign) {
      console.warn('No design available for high-quality preview generation')
      return null
    }

    try {
      isGenerating.value = true
      return await designRenderer.generatePreviewBlob(targetDesign, {
        width: dimensions.width,
        height: dimensions.height,
        format: 'png',
        quality: 1.0
      })
    } catch (error) {
      console.error('Failed to generate high-quality preview:', error)
      return null
    } finally {
      isGenerating.value = false
    }
  }

  /**
   * Auto-generate thumbnail when design changes
   */
  const autoGenerateThumbnail = async (debounceMs: number = 2000): Promise<void> => {
    // Simple debouncing - in production you might want to use a more sophisticated approach
    setTimeout(async () => {
      if (currentDesign.value && !isGenerating.value) {
        // Calculate proper thumbnail dimensions while preserving aspect ratio
        const designDimensions = {
          width: currentDesign.value.width || 800,
          height: currentDesign.value.height || 600
        }
        
        // Target maximum thumbnail size
        const maxThumbnailSize = { width: 400, height: 300 }
        
        // Use GeometryUtils to calculate dimensions that preserve aspect ratio
        const thumbnailResult = GeometryUtils.resize(designDimensions, maxThumbnailSize, {
          mode: 'contain',
          allowUpscaling: false // Don't upscale small designs
        })

        await generateAndSaveThumbnail(currentDesign.value, {
          width: Math.round(thumbnailResult.width),
          height: Math.round(thumbnailResult.height),
          format: 'jpeg',
          quality: 0.8,
          updateBackend: true
        })
      }
    }, debounceMs)
  }

  /**
   * Get preview URL from design (fallback to generated if none exists)
   */
  const getPreviewUrl = async (design?: Design): Promise<string | null> => {
    const targetDesign = design || currentDesign.value
    if (!targetDesign) return null

    // Return existing thumbnail if available
    if (targetDesign.thumbnail) {
      return targetDesign.thumbnail
    }

    // Generate new thumbnail if none exists
    return generateQuickPreview(targetDesign)
  }

  /**
   * Clear preview cache
   */
  const clearPreviewCache = (): void => {
    lastPreviewUrl.value = ''
  }

  return {
    // State
    isGenerating: computed(() => isGenerating.value),
    lastPreviewUrl: computed(() => lastPreviewUrl.value),
    
    // Methods
    generateAndSaveThumbnail,
    generateMultiplePreviews,
    generateQuickPreview,
    generateHighQualityPreview,
    autoGenerateThumbnail,
    getPreviewUrl,
    clearPreviewCache
  }
}

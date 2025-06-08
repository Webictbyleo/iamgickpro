/**
 * Media URL utilities for the ImagePro frontend
 * 
 * These utilities generate proper URLs for media files, thumbnails, and secure media
 * that work with both the Vite dev server proxy and production environments.
 */

/**
 * Get the base URL for media endpoints
 * In development: Uses Vite proxy (relative URLs)
 * In production: Uses full domain URLs
 */
const getMediaBaseUrl = (): string => {
  // In development mode, Vite proxy handles routing
  if (import.meta.env.DEV) {
    return '';
  }
  
  // In production, use the configured API URL or current domain
  return import.meta.env.VITE_API_URL?.replace('/api', '') || '';
};

/**
 * Generate URL for direct media file access
 * @param filename - The media filename (e.g., "image.jpg")
 * @returns Full URL to the media file
 */
export const getMediaUrl = (filename: string): string => {
  if (!filename) return '';
  
  const baseUrl = getMediaBaseUrl();
  return `${baseUrl}/media/${filename}`;
};

/**
 * Generate URL for thumbnail access
 * @param filename - The thumbnail filename (e.g., "thumb_image.jpg")
 * @returns Full URL to the thumbnail file
 */
export const getThumbnailUrl = (filename: string): string => {
  if (!filename) return '';
  
  const baseUrl = getMediaBaseUrl();
  return `${baseUrl}/thumbnails/${filename}`;
};

/**
 * Generate URL for secure media access (requires authentication)
 * @param uuid - The media UUID
 * @returns Full URL to the secure media endpoint
 */
export const getSecureMediaUrl = (uuid: string): string => {
  if (!uuid) return '';
  
  const baseUrl = getMediaBaseUrl();
  return `${baseUrl}/secure-media/${uuid}`;
};

/**
 * Extract filename from a media URL
 * @param url - The media URL
 * @returns The filename part of the URL
 */
export const extractFilenameFromUrl = (url: string): string => {
  if (!url) return '';
  
  // Handle both full URLs and relative paths
  const urlPath = url.includes('://') ? new URL(url).pathname : url;
  return urlPath.split('/').pop() || '';
};

/**
 * Check if a URL is a media URL (points to our media endpoints)
 * @param url - The URL to check
 * @returns True if it's a media URL
 */
export const isMediaUrl = (url: string): boolean => {
  if (!url) return false;
  
  const urlPath = url.includes('://') ? new URL(url).pathname : url;
  return urlPath.startsWith('/media/') || 
         urlPath.startsWith('/thumbnails/') || 
         urlPath.startsWith('/secure-media/');
};

/**
 * Generate a thumbnail filename from a media filename
 * @param filename - Original media filename
 * @param prefix - Thumbnail prefix (default: "thumb_")
 * @returns Thumbnail filename
 */
export const generateThumbnailFilename = (filename: string, prefix: string = 'thumb_'): string => {
  if (!filename) return '';
  
  const extension = filename.split('.').pop();
  const nameWithoutExt = filename.replace(/\.[^/.]+$/, '');
  
  return `${prefix}${nameWithoutExt}.${extension}`;
};

/**
 * Media URL configuration for different environments
 */
export const mediaConfig = {
  // Maximum file size for uploads (100MB)
  maxFileSize: 100 * 1024 * 1024,
  
  // Supported image formats
  supportedImageFormats: ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'],
  
  // Supported video formats  
  supportedVideoFormats: ['mp4', 'webm', 'ogg'],
  
  // Supported audio formats
  supportedAudioFormats: ['mp3', 'wav', 'ogg'],
  
  // Thumbnail settings
  thumbnail: {
    defaultSize: 300,
    quality: 80,
    format: 'jpg'
  }
} as const;

/**
 * Check if a file extension is supported
 * @param filename - The filename to check
 * @param type - The media type ('image', 'video', 'audio', or 'all')
 * @returns True if the format is supported
 */
export const isFormatSupported = (filename: string, type: 'image' | 'video' | 'audio' | 'all' = 'all'): boolean => {
  if (!filename) return false;
  
  const extension = filename.split('.').pop()?.toLowerCase();
  if (!extension) return false;
  
  switch (type) {
    case 'image':
      return mediaConfig.supportedImageFormats.includes(extension);
    case 'video':
      return mediaConfig.supportedVideoFormats.includes(extension);
    case 'audio':
      return mediaConfig.supportedAudioFormats.includes(extension);
    case 'all':
      return [
        ...mediaConfig.supportedImageFormats,
        ...mediaConfig.supportedVideoFormats,
        ...mediaConfig.supportedAudioFormats
      ].includes(extension);
    default:
      return false;
  }
};

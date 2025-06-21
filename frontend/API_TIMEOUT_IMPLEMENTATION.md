# Frontend API Timeout Support Implementation

## Overview
Updated the frontend API service to support per-request timeout options for long-running operations like Replicate API calls and large file uploads.

## Changes Made

### 1. Updated `frontend/src/utils/api.ts`
- **Increased default timeout**: From 10 seconds to 60 seconds for better support of long-running operations
- **Added extended API wrapper**: New `apiWithOptions` object that supports per-request AxiosRequestConfig
- **Maintained backward compatibility**: Existing `api` instance continues to work as before

### 2. Updated `frontend/src/services/api.ts`
- **Added AxiosRequestConfig import**: For type support
- **Updated import**: Now imports both `api` and `apiWithOptions`
- **Enhanced key API methods with timeout support**:
  - `pluginAPI.executeCommand()`: Added optional `requestOptions` parameter for plugin commands
  - `exportAPI.createExportJob()`: Added timeout support for export job creation
  - `exportAPI.downloadExport()`: Added timeout support for download operations
  - `mediaAPI.uploadMedia()`: Added timeout support for large file uploads

### 3. Updated `frontend/src/components/plugins/YoutubeThumbnailPlugin.vue`
- **Fixed timeout implementation**: Moved 700-second timeout from plugin options to axios request options
- **Improved API call**: Now properly passes timeout as a request option rather than plugin parameter

## API Method Signatures

### Plugin API
```typescript
// Before
executeCommand: (data: PluginCommandData) => Promise<ApiResponse<any>>

// After  
executeCommand: (data: PluginCommandData, requestOptions?: AxiosRequestConfig) => Promise<ApiResponse<any>>
```

### Export API
```typescript
// Before
createExportJob: (data: ExportJobData) => Promise<ApiResponse<ExportJob>>
downloadExport: (id: string) => Promise<any>

// After
createExportJob: (data: ExportJobData, requestOptions?: AxiosRequestConfig) => Promise<ApiResponse<ExportJob>>
downloadExport: (id: string, requestOptions?: AxiosRequestConfig) => Promise<any>
```

### Media API
```typescript
// Before
uploadMedia: (file: File, data?: MediaData) => Promise<ApiResponse<MediaItem>>

// After
uploadMedia: (file: File, data?: MediaData, requestOptions?: AxiosRequestConfig) => Promise<ApiResponse<MediaItem>>
```

## Usage Examples

### Long-running plugin commands (e.g., Replicate API)
```typescript
const response = await pluginAPI.executeCommand({
  pluginId: 'youtube_thumbnail',
  command: 'generate_thumbnail_variations',
  layerId: null,
  parameters: { /* ... */ }
}, {
  timeout: 700000 // 11+ minutes for Replicate processing
})
```

### Large file uploads
```typescript
const response = await mediaAPI.uploadMedia(largeFile, { name: 'video.mp4' }, {
  timeout: 300000 // 5 minutes for large video files
})
```

### Video export jobs
```typescript
const response = await exportAPI.createExportJob({
  designId: 123,
  format: 'mp4',
  quality: 'ultra'
}, {
  timeout: 180000 // 3 minutes for video rendering
})
```

## Backward Compatibility
- All existing API calls continue to work without modification
- New timeout support is optional - methods work with default 60-second timeout if no options provided
- Progressive enhancement approach allows gradual adoption of timeout options where needed

## Benefits
1. **Robust long-running operations**: Prevents timeouts on Replicate API calls (up to 11+ minutes)
2. **Better user experience**: Appropriate timeouts for different operation types
3. **Flexible timeout management**: Per-request control over timeout values
4. **Future-proof**: Easy to add timeout support to any new API methods
5. **Backward compatible**: No breaking changes to existing code

## Integration with Backend
- Backend already supports long-running operations with progress updates
- Frontend timeout must be longer than backend processing time
- Backend sends periodic progress updates to prevent browser timeout during long operations
- Error handling properly distinguishes between network timeouts and backend processing errors

# YouTube Thumbnail Plugin Configuration Update

## Changes Made

### 1. Reverted PluginController Changes ✅
- **Removed AsyncMediaProcessingService dependency** from constructor
- **Removed getJobStatus() method** - plugins handle their own commands
- **Removed cancelJob() method** - plugins handle their own commands
- **Cleaned up imports** - removed AsyncMediaProcessingService import

### 2. Updated YouTube Thumbnail Plugin Config ✅
**File**: `backend/config/plugins/youtube_thumbnail.yaml`

#### Version & Description
- **Version**: Updated from `1.0.0` to `2.0.0`
- **Description**: Enhanced to reflect dual AI provider support and async capabilities

#### New Commands Added
```yaml
commands:
  - "analyze_video"
  - "generate_thumbnail_variations"          # Original sync method
  - "generate_thumbnail_variations_async"    # New async method
  - "get_job_status"                        # Poll job progress
  - "cancel_job"                            # Cancel running job
  - "get_video_info"
  - "clear_cache"
```

#### Enhanced Dependencies
```yaml
dependencies:
  - "SecureRequestBuilder"
  - "PluginService"
  - "MediaProcessingService"
  - "AsyncMediaProcessingService"           # New for async jobs
  - "MessageBusInterface"                   # New for background processing
  - "RequestStack"
  - "LoggerInterface"
  - "CacheItemPoolInterface"
```

#### Dual AI Provider Support
**OpenAI Integration** (existing):
- Uses `gpt-image-1` model
- Requires reference image (original thumbnail)
- Processes multiple images per request

**Replicate Integration** (new):
- Uses `google-deepmind/imagen-3` model
- Text-only prompts (no reference image needed)
- Processes one image per request
- Higher quality output, longer processing time

#### Enhanced Internet Configuration
```yaml
internet:
  integrations:
    openai: # Enhanced existing config
    replicate: # New integration
      endpoints:
        - "https://api.replicate.com/v1/models/google-deepmind/imagen-3/predictions"
        - "https://api.replicate.com/v1/predictions"
      rate_limits:
        per_minute: 5
        per_hour: 50
  domains:
    allow:
      - "api.replicate.com"  # New domain
  constraints:
    timeout: 60  # Increased from 30 for long operations
```

#### Async Processing Metadata
```yaml
metadata:
  processing_types:
    - "image_generation"
    - "async_processing"    # New capability
  generation_methods:
    - "sync"               # Traditional immediate processing  
    - "async"              # New background job processing
  async_processing:
    max_job_time: 900      # 15 minutes
    poll_interval: 4500    # 4.5 seconds
    progress_updates: true
    background_queue: true
```

## Architecture Benefits

### Clean Separation of Concerns
- **PluginController**: Handles generic plugin operations only
- **YouTube Plugin**: Manages its own async commands and job status
- **Config File**: Declares all capabilities and requirements

### Plugin-Based Command Handling
- Commands routed through standard plugin execution flow
- Uses existing `/api/plugins/execute-command` endpoint
- No special controller endpoints needed for specific plugins

### Comprehensive Configuration
- **All commands declared** in plugin config
- **Dependencies clearly listed** for dependency injection
- **Internet permissions** defined for both AI providers
- **Metadata** describes capabilities and parameters

## Usage Examples

### Check Job Status (via Plugin Command)
```typescript
const statusResponse = await pluginAPI.executeCommand({
  pluginId: 'youtube_thumbnail',
  command: 'get_job_status',
  layerId: null,
  parameters: { job_id: 'yt_thumb_12345...' }
})
```

### Cancel Job (via Plugin Command)
```typescript
const cancelResponse = await pluginAPI.executeCommand({
  pluginId: 'youtube_thumbnail', 
  command: 'cancel_job',
  layerId: null,
  parameters: { job_id: 'yt_thumb_12345...' }
})
```

### Start Async Generation
```typescript
const asyncResponse = await pluginAPI.executeCommand({
  pluginId: 'youtube_thumbnail',
  command: 'generate_thumbnail_variations_async',
  layerId: null,
  parameters: {
    video_url: 'https://youtube.com/watch?v=...',
    thumbnail_count: 5,
    style: 'professional'
  }
})
```

This clean architecture ensures that plugins are self-contained and the core controller remains focused on generic plugin management operations.

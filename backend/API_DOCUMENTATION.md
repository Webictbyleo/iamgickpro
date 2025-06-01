# API Documentation - IamgickPro Design Platform

## Overview

This documentation provides comprehensive information about the IamgickPro Design Platform API endpoints, including routes, HTTP methods, request/response schemas, and authentication requirements.

**Base URL**: `http://localhost:8000/api`  
**Authentication**: JWT Bearer Token (where required)  
**Content-Type**: `application/json`

*Generated on: 2025-06-01 20:05:42*

---

## Table of Contents

1. [Schema Definitions](#schema-definitions)
2. [Authentication](#authentication)
3. [Designs](#designs)
4. [Projects](#projects)
5. [Layers](#layers)
6. [Media](#media)
7. [Export Jobs](#export-jobs)
8. [Templates](#templates)
9. [Plugins](#plugins)
10. [User Management](#user-management)
11. [Error Handling](#error-handling)

---
## Schema Definitions

The following TypeScript interfaces define the structure of requests and responses used throughout the API.

### Request Schemas

#### BulkDeleteMediaRequestDTO

> Data Transfer Object for bulk media deletion requests.

```typescript
interface BulkDeleteMediaRequestDTO {
  uuids: string[];  // Array of media file UUIDs to delete. Each UUID represents a media file that the user wants to delete. The system will validate ownership and existence before deletion. Maximum 100 items can be dele...
}
```

#### BulkUpdateLayersRequestDTO

> Request DTO for performing bulk updates on multiple layers.

```typescript
interface BulkUpdateLayersRequestDTO {
  layers: any[];  // Request DTO for performing bulk updates on multiple layers. This DTO handles batch operations for updating layer properties, allowing efficient modification of multiple layers in a single API call....
}
```

#### ChangePasswordRequestDTO

> Data Transfer Object for password change requests

```typescript
interface ChangePasswordRequestDTO {
  currentPassword: string;  // CurrentPassword value
  newPassword: string;  // NewPassword value
  confirmPassword: string;  // ConfirmPassword value
}
```

#### CreateDesignRequestDTO

> Request DTO for creating a new design within a project.

```typescript
interface CreateDesignRequestDTO {
  name: string;  // Request DTO for creating a new design within a project. This DTO handles the creation of new designs with specified canvas dimensions, initial design settings, and organizational metadata.
  description?: string;  // Optional description providing additional context about the design. Used to document the design's purpose, goals, or any other relevant information. Maximum 1000 characters to keep descriptions con...
  data?: {
    backgroundColor?: string;  // BackgroundColor value
    animationSettings?: any[];  // Animation settings for the design. Contains configuration for design-level animations including: - duration: Total animation duration in seconds - loop: Whether animations should loop - autoplay: W...
    gridSettings?: any[];  // Grid and snap settings for the design canvas. Contains configuration for design assistance tools: - gridSize: Size of grid squares in pixels - snapToGrid: Whether elements snap to grid - showGrid: ...
    viewSettings?: any[];  // View and zoom settings for the design canvas. Contains configuration for canvas view state: - zoom: Current zoom level (1. 0 = 100%) - panX: Horizontal pan offset in pixels - panY: Vertical pan off...
    globalStyles?: any[];  // Global styles and themes applied to the design. Contains design-level styling that can be applied to layers: - colorPalette: Array of color swatches for the design - fontPairs: Recommended font com...
    customProperties?: any[];  // Custom metadata and extended properties. Allows for custom properties and future extensibility without breaking the schema. Should contain key-value pairs of additional design-level configuration.
  };  // Design-level configuration and settings. Contains global design settings including canvas background, animation configuration, grid settings, view preferences, and custom styling options that apply...
  projectId?: number;  // Optional project ID to associate the design with. If provided, the design will be created within the specified project for organization and collaboration purposes. If null, the design will be creat...
  width?: number;  // Canvas width in pixels. Defines the horizontal dimension of the design canvas. Must be between 1-10000 pixels for reasonable performance and export capabilities. Defaults to 1920px (Full HD width).
  height?: number;  // Canvas height in pixels. Defines the vertical dimension of the design canvas. Must be between 1-10000 pixels for reasonable performance and export capabilities. Defaults to 1080px (Full HD height).
  isPublic?: boolean;  // Whether the design should be publicly accessible. Public designs can be viewed by other users and may appear in community galleries or search results. Private designs are only accessible to the own...
}
```

#### CreateLayerRequestDTO

> Data Transfer Object for layer creation requests

```typescript
interface CreateLayerRequestDTO {
  designId: string;  // Data Transfer Object for layer creation requests Handles the creation of new design layers with all necessary properties for proper initialization in the canvas editor. Supports all layer types inc...
  type: string;  // Type of layer being created Determines which properties and behaviors the layer will have Valid values: text, image, shape, group, video, audio.
  name: string;  // Display name for the layer Used for identification in the layers panel and timeline Must be 1-255 characters long.
  properties: {
    text?: string; // Properties specific to text layers.
    fontFamily?: string; // Font family name (e. g. , Arial, Helvetica, Times New Roman).
    fontSize?: number; // Font size in pixels (1-500px).
    fontWeight?: string; // Font weight (normal, bold, or numeric values 100-900).
    fontStyle?: string; // Font style (normal, italic, or oblique).
    textAlign?: string; // Text alignment within the layer bounds.
    color?: string; // Color value
    lineHeight?: number; // Line height multiplier for text spacing (1. 0 = normal, 1. 5 = 1. 5x spacing).
    letterSpacing?: number; // Letter spacing in pixels (positive or negative values allowed).
    textDecoration?: string; // Text decoration style (none, underline, overline, line-through).
  } | {
    src?: string; // Properties specific to image layers.
    alt?: string; // Alternative text for accessibility and fallback display.
    objectFit?: string; // How the image should be resized to fit its container (fill, contain, cover, none, scale-down).
    objectPosition?: string;
    quality?: number; // Image quality percentage for compression (1-100, higher = better quality).
    brightness?: number; // Image brightness multiplier (0. 0 = black, 1. 0 = normal, 2. 0 = very bright).
    contrast?: number; // Image contrast multiplier (0. 0 = gray, 1. 0 = normal, 2. 0 = high contrast).
    saturation?: number; // Image saturation multiplier (0. 0 = grayscale, 1. 0 = normal, 2. 0 = vivid).
    blur?: number; // Blur radius in pixels (0. 0 = sharp, higher values = more blur).
  } | {
    shapeType?: string; // Properties specific to shape layers.
    fillColor?: string; // FillColor value
    fillOpacity?: number; // Opacity of the fill color (0. 0 = transparent, 1. 0 = opaque).
    strokeColor?: string; // StrokeColor value
    strokeWidth?: number; // Width of the border/stroke in pixels (0 = no border).
    strokeOpacity?: number; // Opacity of the border/stroke (0. 0 = transparent, 1. 0 = opaque).
    borderRadius?: number; // Border radius in pixels for rounded corners (applies to rectangles).
    sides?: number; // Number of sides for polygon and star shapes (3-20).
  };  // Layer-specific visual and behavior properties Contains type-specific attributes like text styling, image src, or shape appearance Structure depends on layer type and is validated accordingly.
  transform: {
    x?: number;  // Represents a 2D transformation matrix for layer positioning and scaling.
    y?: number;  // Y coordinate position of the layer in pixels.
    width?: number;  // Width of the layer in pixels.
    height?: number;  // Height of the layer in pixels.
    rotation?: number;  // Rotation angle in degrees (0-360). Positive values rotate clockwise.
    scaleX?: number;  // Horizontal scale factor (1. 0 = normal size, 2. 0 = double width).
    scaleY?: number;  // Vertical scale factor (1. 0 = normal size, 2. 0 = double height).
    skewX?: number;  // Horizontal skew transformation in degrees.
    skewY?: number;  // Vertical skew transformation in degrees.
    opacity?: number;  // Layer opacity from 0. 0 (transparent) to 1. 0 (opaque).
  };  // 2D transformation matrix for initial layer positioning Controls position, size, rotation, scale, skew, and opacity Defaults to standard position if not provided.
  zIndex?: number;  // Layer stacking order within the design Higher values appear above lower values in the visual stack Must be zero or positive integer, null for auto-assignment.
  visible?: boolean;  // Initial visibility state of the layer true: Layer is visible and rendered in the canvas (default) false: Layer is hidden from view but remains in the design.
  locked?: boolean;  // Initial edit protection state false: Layer can be freely edited and manipulated (default) true: Layer cannot be selected, moved, or modified.
  parentLayerId?: string;  // Parent layer ID for hierarchical grouping null: Layer is created at root level of the design (default).
}
```

#### CreateMediaRequestDTO

> Request DTO for creating a new media item in the library.

```typescript
interface CreateMediaRequestDTO {
  name: string;  // Request DTO for creating a new media item in the library. This DTO handles the creation of media assets including images, videos, and audio files from various sources (uploads, stock photo APIs, et...
  type: string;  // Type of media content. Categorizes the media into broad types for filtering and appropriate handling in the editor. Supported types: - image: Static images (JPEG, PNG, SVG, etc. ) - video: Video fi...
  mimeType: string;  // MIME type of the media file. Specifies the exact format of the media file for proper handling by browsers and processing tools. Examples: - image/jpeg, image/png, image/svg+xml - video/mp4, video/w...
  size: number;  // File size in bytes. Used for storage quota management, upload progress, and performance optimization decisions. Must be a positive number representing the file size in bytes.
  url: string;  // Direct URL to access the media file. This is the primary URL used to display or download the media. Should be a publicly accessible HTTPS URL for security and browser compatibility.
  thumbnailUrl?: string;  // Optional URL to a thumbnail or preview image. Used for quick previews in the media library and layer thumbnails. For videos, this could be a frame capture. For audio, this could be a waveform visua...
  width?: number;  // Width of the media in pixels (for visual media). Essential for layout calculations and aspect ratio preservation. Not applicable for audio files.
  height?: number;  // Height of the media in pixels (for visual media). Essential for layout calculations and aspect ratio preservation. Not applicable for audio files.
  duration?: number;  // Duration in seconds (for time-based media). Used for video and audio files to display playback length and for timeline-based editing features. Not applicable for static images.
  source?: string;  // Source platform or service where the media originated. Tracks the origin of media for attribution, licensing, and integration purposes. Supported sources: - upload: User-uploaded content - unsplash...
  sourceId?: string;  // Unique identifier from the source platform. For stock photo services, this is their internal ID for the media. For uploads, this may be null or a user-defined reference. Used for attribution and pr...
  metadata?: {
    fileSize: number;  // Media file metadata containing technical information about the media file.
    mimeType: string;  // MIME type of the media file (e. g. , image/jpeg, video/mp4, audio/mpeg).
    width?: number;  // Width of the media in pixels (for images and videos).
    height?: number;  // Height of the media in pixels (for images and videos).
    duration?: number;  // Duration of the media in seconds (for audio and video files).
    bitrate?: number;  // Bitrate of the media in bits per second (for audio and video).
    sampleRate?: number;  // Audio sample rate in Hz (for audio files).
    channels?: number;  // Number of audio channels (1 = mono, 2 = stereo, etc. ).
    colorSpace?: string;  // Color space of the image (e. g. , sRGB, Adobe RGB, CMYK).
    hasTransparency?: boolean;  // Whether the image has transparency/alpha channel support.
    frameRate?: number;  // Frame rate of video files in frames per second.
    codec?: string;  // Codec used to encode the media file (e. g. , H. 264, VP9, AAC).
    aspectRatio?: number;  // Aspect ratio of the media (width/height, e. g. , 1. 777 for 16:9).
  };  // Technical metadata about the media file. Contains detailed information about the media file including file size, MIME type, dimensions, and format-specific data like codec information for videos or...
  tags?: {
    name: string; // Represents a tag for categorizing and organizing content.
  }[];  // Organizational tags for categorizing and searching media. Tags help users organize their media library and make content discoverable through search and filtering. Each tag must be 1-50 characters a...
  attribution?: string;  // Attribution text for the media creator. Required for some stock photo services and user-generated content. Displayed in media details and export metadata to comply with licensing requirements.
  license?: string;  // License type under which the media is distributed. Defines usage rights and restrictions for the media. Common values include 'CC0', 'CC BY', 'Commercial', 'Editorial'. Used to ensure proper usage ...
  isPremium?: boolean;  // Whether this media requires a premium subscription to use. Premium media may have additional licensing costs or require special subscription tiers. Affects availability and usage tracking.
  isActive?: boolean;  // Whether this media is currently active and available for use. Inactive media is hidden from search results and cannot be used in new designs. Existing usages remain functional. Used for content mod...
}
```

#### CreateProjectRequestDTO

> Request DTO for creating a new design project.

```typescript
interface CreateProjectRequestDTO {
  name: string;  // Request DTO for creating a new design project. This DTO handles the creation of new projects with all necessary configuration options, including canvas settings, metadata, and organizational tags.
  description?: string;  // Optional description providing additional context about the project. Used to document the project's purpose, goals, or any other relevant information. Maximum 1000 characters to keep descriptions c...
  isPublic: boolean;  // Whether the project should be publicly accessible. Public projects can be viewed by other users and may appear in community galleries or search results. Private projects are only accessible to the ...
  settings: {
    canvasWidth?: number;  // Project settings that control project behavior and defaults.
    canvasHeight?: number;  // Height of the canvas in pixels (1-10000).
    backgroundColor?: string;  // BackgroundColor value
    orientation?: string;  // Canvas orientation mode (portrait or landscape).
    units?: string;  // Measurement units for the canvas (px, cm, mm, in, pt).
    dpi?: number;  // Dots per inch resolution for print output (72-600).
    gridVisible?: boolean;  // Whether the grid overlay is visible on the canvas.
    rulersVisible?: boolean;  // Whether rulers are visible around the canvas edges.
    guidesVisible?: boolean;  // Whether guide lines are visible on the canvas.
    snapToGrid?: boolean;  // Whether objects automatically snap to grid lines.
    snapToGuides?: boolean;  // Whether objects automatically snap to guide lines.
    snapToObjects?: boolean;  // Whether objects automatically snap to other objects.
  };  // Project configuration settings including canvas dimensions, DPI, etc. Contains all technical settings that define how the project behaves and renders, including: - Canvas size and background - Expo...
  tags?: {
    name: string; // Represents a tag for categorizing and organizing content.
  }[];  // Organizational tags for categorizing and searching projects. Tags help users organize their projects and make them discoverable through search and filtering. Each tag must be 1-50 characters and co...
  thumbnail?: string;  // Optional URL to a thumbnail image representing the project. Used for project previews in lists and galleries. Should be a valid URL pointing to an image file. If not provided, a thumbnail will be g...
}
```

#### DuplicateDesignRequestDTO

> Data Transfer Object for design duplication requests.

```typescript
interface DuplicateDesignRequestDTO {
  name?: string;  // Custom name for the duplicated design. If provided, this will be used as the name for the new design. If null, the system will automatically generate a name like "Copy of {original name}". Must be ...
  projectId?: number;  // Target project ID for the duplicated design. If provided, the duplicated design will be placed in the specified project. The user must have write access to the target project. If null, the design i...
}
```

#### DuplicateLayerRequestDTO

> Data Transfer Object for layer duplication requests.

```typescript
interface DuplicateLayerRequestDTO {
  name?: string;  // Custom name for the duplicated layer. If provided, this will be used as the name for the new layer. If null, the system will automatically generate a name based on the original layer name (e. g. , ...
  targetDesignId?: string;  // Target design ID for cross-design duplication. If provided, the layer will be duplicated into the specified design instead of the current design. The user must have write access to the target desig...
}
```

#### DuplicateMediaRequestDTO

> Data Transfer Object for media file duplication requests.

```typescript
interface DuplicateMediaRequestDTO {
  name?: string;  // Custom name for the duplicated media file. If provided, this will be used as the name for the duplicated media file. If null, the system will automatically generate a name like "Copy of {original n...
}
```

#### DuplicateProjectRequestDTO

> Data Transfer Object for project duplication requests.

```typescript
interface DuplicateProjectRequestDTO {
  name?: string;  // Display name for the duplicated project. The name given to the new project copy. Must be between 1-255 characters and will be displayed in the user's project list. If null, a default name will be g...
}
```

#### LoginRequestDTO

> Data Transfer Object for user authentication login requests.

```typescript
interface LoginRequestDTO {
  email: string;  // User's email address for authentication. Must be a valid email format and correspond to a registered user account in the system. Used as the primary identifier for user authentication.
  password: string;  // User's password for authentication. Plain text password that will be verified against the hashed password stored in the database. Should be handled securely throughout the authentication process.
}
```

#### MoveLayerRequestDTO

> Data Transfer Object for layer movement requests.

```typescript
interface MoveLayerRequestDTO {
  direction?: string;  // Direction to move the layer relative to its current position. Supported values: - 'up': Move one position forward in z-order - 'down': Move one position backward in z-order - 'top': Move to highest...
  targetZIndex?: number;  // Absolute z-index position to move the layer to. When provided, moves the layer to this exact z-index position. Must be a non-negative integer. Other layers will be adjusted automatically to maintai...
}
```

#### RegisterRequestDTO

> Data Transfer Object for user registration requests.

```typescript
interface RegisterRequestDTO {
  email: string;  // User's email address for account registration. Must be a valid email format and unique in the system. Used as the primary login identifier and for sending account verification and notification emails.
  password: string;  // User's chosen password for account security. Must meet security requirements: minimum 8 characters, containing at least one lowercase letter, one uppercase letter, and one number. Will be hashed be...
  firstName: string;  // User's first (given) name. Required for account identification and personalization. Used in user interface greetings and profile displays. Must be 1-255 characters long.
  lastName: string;  // User's last (family) name. Required for account identification and personalization. Used in user interface displays and profile information. Must be 1-255 characters long.
  username?: string;  // Optional unique username for the account. If provided, must be 3-100 characters and contain only letters, numbers, and underscores. Can be used as an alternative identifier for the user. If not pro...
}
```

#### SearchMediaRequestDTO

> Data Transfer Object for media search and filtering requests.

```typescript
interface SearchMediaRequestDTO {
  page?: number;  // Page number for pagination. Specifies which page of media results to return. Must be 1 or greater for valid pagination.
  limit?: number;  // Number of media items per page. Controls how many media files are returned per page. Limited to a maximum of 50 to maintain performance.
  type?: string;  // Media type filter. Filters media by type (image, video, or audio). When null, all media types are included in results.
  source?: string;  // Media source filter. Filters media by its source origin (upload for user uploads, or stock photo providers like unsplash, pexels, pixabay). When null, all sources are included.
  search?: string;  // Search query term. Text to search for in media file names, descriptions, and metadata. When null, no text-based filtering is applied.
  tags?: string;  // Comma-separated list of tags for filtering. Tags to filter media by. Multiple tags can be specified separated by commas. The system will find media that matches any of the specified tags.
}
```

#### SearchProjectsRequestDTO

> Data Transfer Object for project search requests.

```typescript
interface SearchProjectsRequestDTO {
  page?: number;  // Page number for pagination. Specifies which page of project results to return. Must be 1 or greater for valid pagination navigation.
  limit?: number;  // Number of projects per page. Controls how many projects are returned per page. Limited to a maximum of 50 to maintain performance and reasonable response times.
  search?: string;  // Search query for project names and descriptions. Text to search for in project names, descriptions, and other searchable fields. When null, no text-based filtering is applied to the results.
  tags?: string;  // Comma-separated list of tags for filtering. Tags to filter projects by. Multiple tags can be specified separated by commas. The system will find projects that match any of the specified tags.
}
```

#### SearchRequestDTO

> Data Transfer Object for general search requests.

```typescript
interface SearchRequestDTO {
  query: string;  // Data Transfer Object for general search requests. Handles search operations across various entities in the platform including designs, projects, templates, and media. Provides standardized paginati...
  page?: number;  // Page number for pagination. Specifies which page of results to return. Must be a positive integer starting from 1. Used for implementing pagination in search results.
  limit?: number;  // Number of items per page. Controls how many search results are returned per page. Limited to a maximum of 50 items to maintain performance and prevent excessive data transfer.
}
```

#### StockSearchRequestDTO

> Data Transfer Object for stock media search requests.

```typescript
interface StockSearchRequestDTO {
  query: string;  // Search query for stock media. The search term to find relevant stock photos and videos from external providers. Must be descriptive enough to return meaningful results from stock media APIs.
  page?: number;  // Page number for stock media results pagination. Specifies which page of stock media results to return from the external provider's API. Used to implement pagination for large result sets.
  limit?: number;  // Number of stock media items per page. Controls how many stock media items are requested from the external provider. Limited to prevent API rate limiting and maintain performance.
  type?: string;  // Type of stock media to search for. Specifies whether to search for images or videos from the stock media provider. Defaults to images as they are more commonly used in designs.
  source?: string;  // Stock media provider source. Specifies which external stock media provider to search. Each provider has different content, licensing, and API characteristics. Defaults to Unsplash for high-quality ...
}
```

#### UpdateDesignRequestDTO

> Request DTO for updating an existing design.

```typescript
interface UpdateDesignRequestDTO {
  name?: string;  // Request DTO for updating an existing design. This DTO handles partial updates to designs, allowing clients to update only the fields they want to change. All fields are optional and null values ind...
  description?: string;  // Updated description for the design. If provided, replaces the current description. Maximum 1000 characters. Null indicates no change.
  data?: {
    backgroundColor?: string;  // BackgroundColor value
    animationSettings?: any[];  // Animation settings for the design. Contains configuration for design-level animations including: - duration: Total animation duration in seconds - loop: Whether animations should loop - autoplay: W...
    gridSettings?: any[];  // Grid and snap settings for the design canvas. Contains configuration for design assistance tools: - gridSize: Size of grid squares in pixels - snapToGrid: Whether elements snap to grid - showGrid: ...
    viewSettings?: any[];  // View and zoom settings for the design canvas. Contains configuration for canvas view state: - zoom: Current zoom level (1. 0 = 100%) - panX: Horizontal pan offset in pixels - panY: Vertical pan off...
    globalStyles?: any[];  // Global styles and themes applied to the design. Contains design-level styling that can be applied to layers: - colorPalette: Array of color swatches for the design - fontPairs: Recommended font com...
    customProperties?: any[];  // Custom metadata and extended properties. Allows for custom properties and future extensibility without breaking the schema. Should contain key-value pairs of additional design-level configuration.
  };  // Updated design-level configuration and settings. If provided, merges with or replaces current design settings including canvas background, animations, grid settings, etc. Null indicates no change t...
  projectId?: number;  // Updated project association for the design. If provided, moves the design to the specified project. Can be used to move designs between projects or remove from project (if supported). Null indicate...
  width?: number;  // Updated canvas width in pixels. If provided, resizes the canvas width. Must be between 1-10000 pixels. This may affect layer positioning. Null indicates no change.
  height?: number;  // Updated canvas height in pixels. If provided, resizes the canvas height. Must be between 1-10000 pixels. This may affect layer positioning. Null indicates no change.
  isPublic?: boolean;  // Updated public visibility for the design. If provided, changes whether the design is publicly accessible and can appear in community galleries. Null indicates no change.
}
```

#### UpdateDesignThumbnailRequestDTO

> Data Transfer Object for design thumbnail update requests.

```typescript
interface UpdateDesignThumbnailRequestDTO {
  thumbnail: string;  // Data Transfer Object for design thumbnail update requests. Handles updating the thumbnail image for an existing design. Used when users want to change the preview image that represents their design...
}
```

#### UpdateLayerRequestDTO

> Data Transfer Object for layer update requests

```typescript
interface UpdateLayerRequestDTO {
  name?: string;  // Data Transfer Object for layer update requests Handles modification of existing design layers including visual properties, positioning, visibility, and hierarchy changes. All fields are optional to...
  properties?: {
    text?: string; // Properties specific to text layers.
    fontFamily?: string; // Font family name (e. g. , Arial, Helvetica, Times New Roman).
    fontSize?: number; // Font size in pixels (1-500px).
    fontWeight?: string; // Font weight (normal, bold, or numeric values 100-900).
    fontStyle?: string; // Font style (normal, italic, or oblique).
    textAlign?: string; // Text alignment within the layer bounds.
    color?: string; // Color value
    lineHeight?: number; // Line height multiplier for text spacing (1. 0 = normal, 1. 5 = 1. 5x spacing).
    letterSpacing?: number; // Letter spacing in pixels (positive or negative values allowed).
    textDecoration?: string; // Text decoration style (none, underline, overline, line-through).
  } | {
    src?: string; // Properties specific to image layers.
    alt?: string; // Alternative text for accessibility and fallback display.
    objectFit?: string; // How the image should be resized to fit its container (fill, contain, cover, none, scale-down).
    objectPosition?: string;
    quality?: number; // Image quality percentage for compression (1-100, higher = better quality).
    brightness?: number; // Image brightness multiplier (0. 0 = black, 1. 0 = normal, 2. 0 = very bright).
    contrast?: number; // Image contrast multiplier (0. 0 = gray, 1. 0 = normal, 2. 0 = high contrast).
    saturation?: number; // Image saturation multiplier (0. 0 = grayscale, 1. 0 = normal, 2. 0 = vivid).
    blur?: number; // Blur radius in pixels (0. 0 = sharp, higher values = more blur).
  } | {
    shapeType?: string; // Properties specific to shape layers.
    fillColor?: string; // FillColor value
    fillOpacity?: number; // Opacity of the fill color (0. 0 = transparent, 1. 0 = opaque).
    strokeColor?: string; // StrokeColor value
    strokeWidth?: number; // Width of the border/stroke in pixels (0 = no border).
    strokeOpacity?: number; // Opacity of the border/stroke (0. 0 = transparent, 1. 0 = opaque).
    borderRadius?: number; // Border radius in pixels for rounded corners (applies to rectangles).
    sides?: number; // Number of sides for polygon and star shapes (3-20).
  };  // Layer-specific visual and behavior properties Contains type-specific attributes like text styling, image filters, or shape appearance Structure depends on layer type (text, image, shape, etc. ).
  transform?: {
    x?: number;  // Represents a 2D transformation matrix for layer positioning and scaling.
    y?: number;  // Y coordinate position of the layer in pixels.
    width?: number;  // Width of the layer in pixels.
    height?: number;  // Height of the layer in pixels.
    rotation?: number;  // Rotation angle in degrees (0-360). Positive values rotate clockwise.
    scaleX?: number;  // Horizontal scale factor (1. 0 = normal size, 2. 0 = double width).
    scaleY?: number;  // Vertical scale factor (1. 0 = normal size, 2. 0 = double height).
    skewX?: number;  // Horizontal skew transformation in degrees.
    skewY?: number;  // Vertical skew transformation in degrees.
    opacity?: number;  // Layer opacity from 0. 0 (transparent) to 1. 0 (opaque).
  };  // 2D transformation matrix for layer positioning and scaling Controls position, size, rotation, scale, skew, and opacity Used by the canvas renderer for accurate layer placement.
  zIndex?: number;  // Layer stacking order within its parent container Higher values appear above lower values in the visual stack Must be zero or positive integer.
  visible?: boolean;  // Layer visibility state in the design canvas false: Layer is hidden from view but remains in the design true: Layer is visible and rendered in the canvas.
  locked?: boolean;  // Layer edit protection state true: Layer cannot be selected, moved, or modified false: Layer can be freely edited and manipulated.
  parentLayerId?: string;  // Parent layer ID for hierarchical grouping null: Layer is at root level of the design Used for group operations and layer organization.
}
```

#### UpdateMediaRequestDTO

> Request DTO for updating an existing media item.

```typescript
interface UpdateMediaRequestDTO {
  name?: string;  // Request DTO for updating an existing media item. This DTO handles partial updates to media assets, allowing clients to update only the fields they want to change. All fields are optional and null v...
  description?: string;  // Updated description for the media item. If provided, replaces the current description. Maximum 1000 characters. Null indicates no change.
  tags?: {
    name: string; // Represents a tag for categorizing and organizing content.
  }[];  // Updated organizational tags for the media. If provided, replaces the current tag set. Each tag must be 1-50 characters and contain only alphanumeric characters, spaces, hyphens, and underscores. Nu...
  metadata?: {
    fileSize: number;  // Media file metadata containing technical information about the media file.
    mimeType: string;  // MIME type of the media file (e. g. , image/jpeg, video/mp4, audio/mpeg).
    width?: number;  // Width of the media in pixels (for images and videos).
    height?: number;  // Height of the media in pixels (for images and videos).
    duration?: number;  // Duration of the media in seconds (for audio and video files).
    bitrate?: number;  // Bitrate of the media in bits per second (for audio and video).
    sampleRate?: number;  // Audio sample rate in Hz (for audio files).
    channels?: number;  // Number of audio channels (1 = mono, 2 = stereo, etc. ).
    colorSpace?: string;  // Color space of the image (e. g. , sRGB, Adobe RGB, CMYK).
    hasTransparency?: boolean;  // Whether the image has transparency/alpha channel support.
    frameRate?: number;  // Frame rate of video files in frames per second.
    codec?: string;  // Codec used to encode the media file (e. g. , H. 264, VP9, AAC).
    aspectRatio?: number;  // Aspect ratio of the media (width/height, e. g. , 1. 777 for 16:9).
  };  // Updated technical metadata for the media file. If provided, replaces or merges with current metadata. Contains detailed information about the media file including dimensions, codec info, EXIF data,...
  isPremium?: boolean;  // Updated premium status for the media. If provided, changes whether the media requires a premium subscription to use. Null indicates no change.
  isActive?: boolean;  // Updated active status for the media. If provided, changes whether the media is currently available for use in new designs. Null indicates no change.
  isPublic?: boolean;  // Updated public visibility for the media. If provided, changes whether the media is publicly accessible and can appear in community galleries. Null indicates no change.
}
```

#### UpdateProfileRequestDTO

> Data Transfer Object for user profile update requests

```typescript
interface UpdateProfileRequestDTO {
  firstName?: string;  // Data Transfer Object for user profile update requests Handles updating user profile information including personal details, username, avatar, and application settings. All fields are optional to su...
  lastName?: string;  // User's last name for display and identification purposes Must be between 1-255 characters if provided.
  username?: string;  // Username value
  avatar?: string;  // URL to the user's profile avatar image Must be a valid URL pointing to an image file Used for profile display and team collaboration identification.
  settings?: {
    theme?: string;  // User application settings.
    language?: string;  // User interface language (ISO 639-1 language code).
    timezone?: string;  // User's timezone for date/time display.
    emailNotifications?: boolean;  // Whether to send email notifications to the user.
    pushNotifications?: boolean;  // Whether to send push notifications to the user's devices.
    autoSave?: boolean;  // Whether to automatically save designs while working.
    autoSaveInterval?: number;  // How often to auto-save in seconds (30-600).
    gridSnap?: boolean;  // Whether objects automatically snap to grid when moving.
    gridSize?: number;  // Grid spacing in pixels for snap and alignment (1-100).
    canvasQuality?: number;  // Canvas rendering quality level (1=low, 2=medium, 3=high, 4=ultra).
  };  // User's application settings and preferences Controls theme, language, notifications, auto-save, and editor behavior.
}
```

#### UpdateProjectRequestDTO

> Request DTO for updating an existing design project.

```typescript
interface UpdateProjectRequestDTO {
  name?: string;  // Request DTO for updating an existing design project. This DTO handles partial updates to projects, allowing clients to update only the fields they want to change. All fields are optional and null v...
  description?: string;  // Updated description for the project. If provided, replaces the current description. Maximum 1000 characters. Null indicates no change.
  isPublic?: boolean;  // Updated visibility setting for the project. If provided, changes whether the project is publicly accessible. Null indicates no change.
  settings?: {
    canvasWidth?: number;  // Project settings that control project behavior and defaults.
    canvasHeight?: number;  // Height of the canvas in pixels (1-10000).
    backgroundColor?: string;  // BackgroundColor value
    orientation?: string;  // Canvas orientation mode (portrait or landscape).
    units?: string;  // Measurement units for the canvas (px, cm, mm, in, pt).
    dpi?: number;  // Dots per inch resolution for print output (72-600).
    gridVisible?: boolean;  // Whether the grid overlay is visible on the canvas.
    rulersVisible?: boolean;  // Whether rulers are visible around the canvas edges.
    guidesVisible?: boolean;  // Whether guide lines are visible on the canvas.
    snapToGrid?: boolean;  // Whether objects automatically snap to grid lines.
    snapToGuides?: boolean;  // Whether objects automatically snap to guide lines.
    snapToObjects?: boolean;  // Whether objects automatically snap to other objects.
  };  // Updated project configuration settings. If provided, replaces or merges with current project settings including canvas dimensions, DPI, export preferences, etc. Null indicates no change to settings.
  tags?: {
    name: string; // Represents a tag for categorizing and organizing content.
  }[];  // Updated organizational tags for the project. If provided, replaces the current tag set. Each tag must be 1-50 characters and contain only alphanumeric characters, spaces, hyphens, and underscores. ...
  thumbnail?: string;  // Updated thumbnail URL for the project. If provided, replaces the current thumbnail. Should be a valid URL pointing to an image file. Null indicates no change.
}
```

#### UploadMediaRequestDTO

> Data Transfer Object for media file upload requests

```typescript
interface UploadMediaRequestDTO {
  name: string;  // Data Transfer Object for media file upload requests Handles the upload of media files (images, videos, audio, documents) with metadata, categorization tags, and privacy settings. Used by the media ...
  type: string;  // Media file type category for proper handling and filtering Determines how the file is processed and where it appears in the media library Valid values: image, video, audio, document.
  description?: string;  // Optional description of the media file content Used for accessibility, SEO, and content organization Maximum 1000 characters to provide detailed context.
  tags?: {
    name: string; // Represents a tag for categorizing and organizing content.
  }[];  // Array of categorization tags for content organization and search Each tag must be 1-50 characters and contain only letters, numbers, spaces, hyphens, underscores Used for filtering and discovering ...
  isPublic?: boolean;  // Privacy setting determining media visibility and sharing permissions true: Media can be shared and used by other users in the platform false: Media is private to the uploading user only.
}
```

### Response Schemas

#### AuthResponseDTO

> Authentication response DTO containing user data and JWT token.

```typescript
interface AuthResponseDTO {
  message: string;  // Authentication response DTO containing user data and JWT token. Returned by authentication endpoints (login, register, refresh) to provide the client with the access token and user information need...
  token: string;  // JWT access token for API authentication. Bearer token that must be included in the Authorization header for subsequent authenticated API requests. Contains encoded user claims and expiration inform...
  user: {
    id: string;  // User data DTO for API responses.
    email: string;  // Email address
    firstName: string;  // FirstName value
    lastName: string;  // LastName value
    username?: string;  // Username value
    roles: any[];  // Roles value
    avatar?: string;  // Avatar value
    plan?: string;  // Plan value
    emailVerified?: boolean;  // EmailVerified value
    isActive?: boolean;  // IsActive value
    createdAt?: string;  // CreatedAt value
    lastLoginAt?: string;  // LastLoginAt value
    updatedAt?: string;  // UpdatedAt value
    settings?: any[];  // Settings value
    stats?: any[];  // Stats value
  };  // Authenticated user's profile information. Complete user data including ID, name, email, avatar, and other profile details needed for UI personalization and user identification throughout the applic...
  success?: boolean;  // Indicates if the request was successful
  timestamp?: string;  // ISO 8601 timestamp of when the response was generated
}
```

#### BaseResponseDTO

> Base response DTO providing common fields for all API responses.

```typescript
interface BaseResponseDTO {
  message: string;  // Base response DTO providing common fields for all API responses. Standardizes the response format across all API endpoints with consistent success indicators, messages, and timestamps. Extended by ...
  success?: boolean;  // Indicates whether the operation completed successfully. Boolean flag that allows clients to quickly determine if the request was processed successfully without parsing the entire response structure.
  timestamp?: string;  // ISO 8601 timestamp of when the response was generated. Optional timestamp that can be provided or will be Useful for debugging and client-side caching.
}
```

#### DesignResponseDTO

> Response DTO for Design data

```typescript
interface DesignResponseDTO {
  success: boolean;  // Response DTO for Design data.
  message: string;  // Human-readable message describing the result
  design?: any[];  // Design object data
  designs?: any[];  // Array of design objects
  total?: number;  // Total count of items
  page?: number;  // Current page number
  totalPages?: number;  // Total number of pages
  timestamp?: string;  // ISO 8601 timestamp of when the response was generated
}
```

#### ErrorResponseDTO

> Error response DTO for API errors

```typescript
interface ErrorResponseDTO {
  message: string;  // Error response DTO for API errors.
  details?: any[];  // Additional error details
  code?: string;  // Error code identifier
  timestamp?: string;  // ISO 8601 timestamp of when the response was generated
  success?: boolean;  // Indicates whether the operation completed successfully. Boolean flag that allows clients to quickly determine if the request was processed successfully without parsing the entire response structure.
}
```

#### ExportJobResponseDTO

> Response DTO for Export Job data

```typescript
interface ExportJobResponseDTO {
  success: boolean;  // Response DTO for Export Job data.
  message: string;  // Human-readable message describing the result
  job?: any[];  // Export job object data
  jobs?: any[];  // Array of export job objects
  total?: number;  // Total count of items
  page?: number;  // Current page number
  totalPages?: number;  // Total number of pages
  timestamp?: string;  // ISO 8601 timestamp of when the response was generated
}
```

#### LayerResponseDTO

> Response DTO for Layer data

```typescript
interface LayerResponseDTO {
  success: boolean;  // Response DTO for Layer data.
  message: string;  // Human-readable message describing the result
  layer?: any[];  // Layer object data
  layers?: any[];  // Array of layer objects
  timestamp?: string;  // ISO 8601 timestamp of when the response was generated
}
```

#### MediaResponseDTO

> Response DTO for Media data

```typescript
interface MediaResponseDTO {
  success: boolean;  // Response DTO for Media data.
  message: string;  // Human-readable message describing the result
  media?: any[];  // Media object data
  mediaList?: any[];  // MediaList value
  total?: number;  // Total count of items
  page?: number;  // Current page number
  totalPages?: number;  // Total number of pages
  timestamp?: string;  // ISO 8601 timestamp of when the response was generated
}
```

#### PaginatedResponseDTO

> Paginated response DTO for API endpoints

```typescript
interface PaginatedResponseDTO {
  data: any[];  // Paginated response DTO for API endpoints.
  page: number;  // Current page number
  limit: number;  // Items per page
  total: number;  // Total count of items
  totalPages: number;  // Total number of pages
  message?: string;  // Human-readable message describing the result
}
```

#### PluginResponseDTO

> Response DTO for Plugin data

```typescript
interface PluginResponseDTO {
  success: boolean;  // Response DTO for Plugin data.
  message: string;  // Human-readable message describing the result
  plugin?: any[];  // Plugin object data
  plugins?: any[];  // Array of plugin objects
  total?: number;  // Total count of items
  page?: number;  // Current page number
  totalPages?: number;  // Total number of pages
  timestamp?: string;  // ISO 8601 timestamp of when the response was generated
}
```

#### ProjectResponseDTO

> Response DTO for Project data

```typescript
interface ProjectResponseDTO {
  success: boolean;  // Response DTO for Project data.
  message: string;  // Human-readable message describing the result
  project?: any[];  // Project object data
  projects?: any[];  // Array of project objects
  total?: number;  // Total count of items
  page?: number;  // Current page number
  totalPages?: number;  // Total number of pages
  timestamp?: string;  // ISO 8601 timestamp of when the response was generated
}
```

#### SearchResponseDTO

> Response DTO for Search results

```typescript
interface SearchResponseDTO {
  success: boolean;  // Response DTO for Search results.
  message: string;  // Human-readable message describing the result
  results?: any[];  // Results value
  query?: string;  // Query value
  total?: number;  // Total count of items
  page?: number;  // Current page number
  totalPages?: number;  // Total number of pages
  timestamp?: string;  // ISO 8601 timestamp of when the response was generated
}
```

#### SuccessResponseDTO

> Simple success response DTO for operations that don't return data

```typescript
interface SuccessResponseDTO {
  message: string;  // Simple success response DTO for operations that don't return data.
  success?: boolean;  // Indicates if the request was successful
  timestamp?: string;  // ISO 8601 timestamp of when the response was generated
}
```

#### TemplateResponseDTO

> Response DTO for Template data

```typescript
interface TemplateResponseDTO {
  success: boolean;  // Response DTO for Template data.
  message: string;  // Human-readable message describing the result
  template?: any[];  // Template object data
  templates?: any[];  // Array of template objects
  total?: number;  // Total count of items
  page?: number;  // Current page number
  totalPages?: number;  // Total number of pages
  timestamp?: string;  // ISO 8601 timestamp of when the response was generated
}
```

#### TemplateSearchResponseDTO

> Response DTO for template search results

```typescript
interface TemplateSearchResponseDTO {
  templates: any[];  // Array of template data with specific structure
  page: number;  // Current page number
  limit: number;  // Items per page
  total: number;  // Total number of templates found
  totalPages: number;  // Total number of pages
  message?: string;  // Response message
}
```

#### UserProfileResponseDTO

> User profile response DTO

```typescript
interface UserProfileResponseDTO {
  user: {
    id: string;  // User data DTO for API responses.
    email: string;  // Email address
    firstName: string;  // FirstName value
    lastName: string;  // LastName value
    username?: string;  // Username value
    roles: any[];  // Roles value
    avatar?: string;  // Avatar value
    plan?: string;  // Plan value
    emailVerified?: boolean;  // EmailVerified value
    isActive?: boolean;  // IsActive value
    createdAt?: string;  // CreatedAt value
    lastLoginAt?: string;  // LastLoginAt value
    updatedAt?: string;  // UpdatedAt value
    settings?: any[];  // Settings value
    stats?: any[];  // Stats value
  };  // User profile response DTO.
  message?: string;  // Human-readable message describing the result
  success?: boolean;  // Indicates if the request was successful
  timestamp?: string;  // ISO 8601 timestamp of when the response was generated
}
```

#### UserResponseDTO

> User data DTO for API responses

```typescript
interface UserResponseDTO {
  id: string;  // User data DTO for API responses.
  email: string;  // Email address
  firstName: string;  // FirstName value
  lastName: string;  // LastName value
  username?: string;  // Username value
  roles: any[];  // Roles value
  avatar?: string;  // Avatar value
  plan?: string;  // Plan value
  emailVerified?: boolean;  // EmailVerified value
  isActive?: boolean;  // IsActive value
  createdAt?: string;  // CreatedAt value
  lastLoginAt?: string;  // LastLoginAt value
  updatedAt?: string;  // UpdatedAt value
  settings?: any[];  // Settings value
  stats?: any[];  // Stats value
}
```

## Auth

### Auth Register

- **Route**: `POST /api/auth/register`
- **Authentication**: None required
- **Description**: Register a new user account

#### Request Schema

See [RegisterRequestDTO](#registerrequestdto)

#### Response Schema

See [AuthResponseDTO](#authresponsedto)

---

### Auth Login

- **Route**: `POST /api/auth/login`
- **Authentication**: None required
- **Description**: Authenticate user and receive JWT token

#### Request Schema

See [LoginRequestDTO](#loginrequestdto)

#### Response Schema

See [AuthResponseDTO](#authresponsedto)

---

### Auth Me

- **Route**: `GET /api/auth/me`
- **Authentication**: Required (JWT Bearer Token)
- **Description**: Get current authenticated user information

#### Response Schema

See [AuthResponseDTO](#authresponsedto)

---

### Auth Update Profile

- **Route**: `PUT /api/auth/profile`
- **Authentication**: Required (JWT Bearer Token)
- **Description**: Update user profile information

#### Request Schema

See [UpdateProfileRequestDTO](#updateprofilerequestdto)

#### Response Schema

See [SuccessResponseDTO](#successresponsedto)

---

### Auth Change Password

- **Route**: `PUT /api/auth/change-password`
- **Authentication**: Required (JWT Bearer Token)
- **Description**: Change user password

#### Request Schema

See [ChangePasswordRequestDTO](#changepasswordrequestdto)

#### Response Schema

See [SuccessResponseDTO](#successresponsedto)

---

### Auth Logout

- **Route**: `POST /api/auth/logout`
- **Authentication**: Required (JWT Bearer Token)
- **Description**: Logout user and invalidate token

#### Response Schema

See [SuccessResponseDTO](#successresponsedto)

---

## Designs

### Designs Index

- **Route**: `GET /api/designs`
- **Authentication**: Required (JWT Bearer Token)
- **Description**: Get paginated list of designs

#### Response Schema

See [PaginatedResponseDTO](#paginatedresponsedto)

---

### Designs Create

- **Route**: `POST /api/designs`
- **Authentication**: Required (JWT Bearer Token)
- **Description**: Create a new designs

#### Request Schema

See [CreateDesignsRequestDTO](#createdesignsrequestdto)

#### Response Schema

See [DesignsResponseDTO](#designsresponsedto)

---

### Designs Show

- **Route**: `GET /api/designs/{id}`
- **Authentication**: Required (JWT Bearer Token)
- **Description**: Get a specific designs

#### Response Schema

See [DesignsResponseDTO](#designsresponsedto)

---

### Designs Update

- **Route**: `PUT /api/designs/{id}`
- **Authentication**: Required (JWT Bearer Token)
- **Description**: Update an existing designs

#### Request Schema

See [UpdateDesignsRequestDTO](#updatedesignsrequestdto)

#### Response Schema

See [DesignsResponseDTO](#designsresponsedto)

---

### Designs Delete

- **Route**: `DELETE /api/designs/{id}`
- **Authentication**: Required (JWT Bearer Token)
- **Description**: Delete a designs

#### Response Schema

See [SuccessResponseDTO](#successresponsedto)

---

### Designs Duplicate

- **Route**: `POST /api/designs/{id}/duplicate`
- **Authentication**: Required (JWT Bearer Token)
- **Description**: Duplicate a designs

#### Request Schema

See [DuplicateDesignsRequestDTO](#duplicatedesignsrequestdto)

#### Response Schema

See [SuccessResponseDTO](#successresponsedto)

---

### Designs Update Thumbnail

- **Route**: `PUT /api/designs/{id}/thumbnail`
- **Authentication**: Required (JWT Bearer Token)
- **Description**: Update an existing designs

#### Request Schema

See [UpdateDesignsRequestDTO](#updatedesignsrequestdto)

#### Response Schema

See [DesignsResponseDTO](#designsresponsedto)

---

### Designs Search

- **Route**: `GET /api/designs/search`
- **Authentication**: Required (JWT Bearer Token)
- **Description**: Search designs

#### Request Schema

See [SearchDesignsRequestDTO](#searchdesignsrequestdto)

#### Response Schema

See [PaginatedResponseDTO](#paginatedresponsedto)

---

## Export

### Export Jobs List

- **Route**: `GET /api/export-jobs`
- **Authentication**: Required (JWT Bearer Token)
- **Description**: jobs export

#### Response Schema

See [SuccessResponseDTO](#successresponsedto)

---

### Export Jobs Show

- **Route**: `GET /api/export-jobs/{id}`
- **Authentication**: Required (JWT Bearer Token)
- **Description**: jobs export

#### Response Schema

See [SuccessResponseDTO](#successresponsedto)

---

### Export Jobs Create

- **Route**: `POST /api/export-jobs`
- **Authentication**: Required (JWT Bearer Token)
- **Description**: jobs export

#### Response Schema

See [SuccessResponseDTO](#successresponsedto)

---

### Export Jobs Update

- **Route**: `PUT /api/export-jobs/{id}`
- **Authentication**: Required (JWT Bearer Token)
- **Description**: jobs export

#### Response Schema

See [SuccessResponseDTO](#successresponsedto)

---

### Export Jobs Delete

- **Route**: `DELETE /api/export-jobs/{id}`
- **Authentication**: Required (JWT Bearer Token)
- **Description**: jobs export

#### Response Schema

See [SuccessResponseDTO](#successresponsedto)

---

### Export Jobs Cancel

- **Route**: `POST /api/export-jobs/{id}/cancel`
- **Authentication**: Required (JWT Bearer Token)
- **Description**: jobs export

#### Response Schema

See [SuccessResponseDTO](#successresponsedto)

---

### Export Jobs Retry

- **Route**: `POST /api/export-jobs/{id}/retry`
- **Authentication**: Required (JWT Bearer Token)
- **Description**: jobs export

#### Response Schema

See [SuccessResponseDTO](#successresponsedto)

---

### Export Jobs Download

- **Route**: `GET /api/export-jobs/{id}/download`
- **Authentication**: Required (JWT Bearer Token)
- **Description**: jobs export

#### Response Schema

See [SuccessResponseDTO](#successresponsedto)

---

### Export Jobs Stats

- **Route**: `GET /api/export-jobs/stats`
- **Authentication**: Required (JWT Bearer Token)
- **Description**: jobs export

#### Response Schema

See [SuccessResponseDTO](#successresponsedto)

---

### Export Jobs Queue Status

- **Route**: `GET /api/export-jobs/queue-status`
- **Authentication**: Required (JWT Bearer Token)
- **Description**: jobs export

#### Response Schema

See [SuccessResponseDTO](#successresponsedto)

---

## Layers

### Layers Create

- **Route**: `POST /api/layers`
- **Authentication**: Required (JWT Bearer Token)
- **Description**: Create a new layers

#### Request Schema

See [CreateLayersRequestDTO](#createlayersrequestdto)

#### Response Schema

See [LayersResponseDTO](#layersresponsedto)

---

### Layers Show

- **Route**: `GET /api/layers/{id}`
- **Authentication**: Required (JWT Bearer Token)
- **Description**: Get a specific layers

#### Response Schema

See [LayersResponseDTO](#layersresponsedto)

---

### Layers Update

- **Route**: `PUT /api/layers/{id}`
- **Authentication**: Required (JWT Bearer Token)
- **Description**: Update an existing layers

#### Request Schema

See [UpdateLayersRequestDTO](#updatelayersrequestdto)

#### Response Schema

See [LayersResponseDTO](#layersresponsedto)

---

### Layers Delete

- **Route**: `DELETE /api/layers/{id}`
- **Authentication**: Required (JWT Bearer Token)
- **Description**: Delete a layers

#### Response Schema

See [SuccessResponseDTO](#successresponsedto)

---

### Layers Duplicate

- **Route**: `POST /api/layers/{id}/duplicate`
- **Authentication**: Required (JWT Bearer Token)
- **Description**: Duplicate a layers

#### Request Schema

See [DuplicateLayersRequestDTO](#duplicatelayersrequestdto)

#### Response Schema

See [SuccessResponseDTO](#successresponsedto)

---

### Layers Move

- **Route**: `PUT /api/layers/{id}/move`
- **Authentication**: Required (JWT Bearer Token)
- **Description**: move layers

#### Response Schema

See [SuccessResponseDTO](#successresponsedto)

---

### Layers Bulk Update

- **Route**: `PUT /api/layers/bulk-update`
- **Authentication**: Required (JWT Bearer Token)
- **Description**: bulk layers

#### Response Schema

See [SuccessResponseDTO](#successresponsedto)

---

## Media

### Media List

- **Route**: `GET /api/media`
- **Authentication**: Required (JWT Bearer Token)
- **Description**: Get paginated list of media

#### Response Schema

See [PaginatedResponseDTO](#paginatedresponsedto)

---

### Media Show

- **Route**: `GET /api/media/{uuid}`
- **Authentication**: Required (JWT Bearer Token)
- **Description**: Get a specific media

#### Response Schema

See [MediaResponseDTO](#mediaresponsedto)

---

### Media Create

- **Route**: `POST /api/media`
- **Authentication**: Required (JWT Bearer Token)
- **Description**: Create a new media

#### Request Schema

See [CreateMediaRequestDTO](#createmediarequestdto)

#### Response Schema

See [MediaResponseDTO](#mediaresponsedto)

---

### Media Update

- **Route**: `PUT /api/media/{uuid}`
- **Authentication**: Required (JWT Bearer Token)
- **Description**: Update an existing media

#### Request Schema

See [UpdateMediaRequestDTO](#updatemediarequestdto)

#### Response Schema

See [MediaResponseDTO](#mediaresponsedto)

---

### Media Delete

- **Route**: `DELETE /api/media/{uuid}`
- **Authentication**: Required (JWT Bearer Token)
- **Description**: Delete a media

#### Response Schema

See [SuccessResponseDTO](#successresponsedto)

---

### Media Search

- **Route**: `GET /api/media/search`
- **Authentication**: Required (JWT Bearer Token)
- **Description**: Search media

#### Request Schema

See [SearchMediaRequestDTO](#searchmediarequestdto)

#### Response Schema

See [PaginatedResponseDTO](#paginatedresponsedto)

---

### Media Duplicate

- **Route**: `POST /api/media/duplicate/{uuid}`
- **Authentication**: Required (JWT Bearer Token)
- **Description**: Duplicate a media

#### Request Schema

See [DuplicateMediaRequestDTO](#duplicatemediarequestdto)

#### Response Schema

See [SuccessResponseDTO](#successresponsedto)

---

### Media Stock Search

- **Route**: `GET /api/media/stock/search`
- **Authentication**: Required (JWT Bearer Token)
- **Description**: stock media

#### Response Schema

See [SuccessResponseDTO](#successresponsedto)

---

### Media Bulk Delete

- **Route**: `DELETE /api/media/bulk/delete`
- **Authentication**: Required (JWT Bearer Token)
- **Description**: bulk media

#### Response Schema

See [SuccessResponseDTO](#successresponsedto)

---

## Plugins

### Plugins List

- **Route**: `GET /api/plugins`
- **Authentication**: Required (JWT Bearer Token)
- **Description**: Get paginated list of plugins

#### Response Schema

See [PaginatedResponseDTO](#paginatedresponsedto)

---

### Plugins Show

- **Route**: `GET /api/plugins/{id}`
- **Authentication**: Required (JWT Bearer Token)
- **Description**: Get a specific plugins

#### Response Schema

See [PluginsResponseDTO](#pluginsresponsedto)

---

### Plugins Create

- **Route**: `POST /api/plugins`
- **Authentication**: Required (JWT Bearer Token)
- **Description**: Create a new plugins

#### Request Schema

See [CreatePluginsRequestDTO](#createpluginsrequestdto)

#### Response Schema

See [PluginsResponseDTO](#pluginsresponsedto)

---

### Plugins Update

- **Route**: `PUT /api/plugins/{id}`
- **Authentication**: Required (JWT Bearer Token)
- **Description**: Update an existing plugins

#### Request Schema

See [UpdatePluginsRequestDTO](#updatepluginsrequestdto)

#### Response Schema

See [PluginsResponseDTO](#pluginsresponsedto)

---

### Plugins Delete

- **Route**: `DELETE /api/plugins/{id}`
- **Authentication**: Required (JWT Bearer Token)
- **Description**: Delete a plugins

#### Response Schema

See [SuccessResponseDTO](#successresponsedto)

---

### Plugins Install

- **Route**: `POST /api/plugins/{id}/install`
- **Authentication**: Required (JWT Bearer Token)
- **Description**: install plugins

#### Response Schema

See [SuccessResponseDTO](#successresponsedto)

---

### Plugins Uninstall

- **Route**: `POST /api/plugins/{id}/uninstall`
- **Authentication**: Required (JWT Bearer Token)
- **Description**: uninstall plugins

#### Response Schema

See [SuccessResponseDTO](#successresponsedto)

---

### Plugins Upload File

- **Route**: `POST /api/plugins/{id}/upload-file`
- **Authentication**: Required (JWT Bearer Token)
- **Description**: upload plugins

#### Response Schema

See [SuccessResponseDTO](#successresponsedto)

---

### Plugins Approve

- **Route**: `POST /api/plugins/{id}/approve`
- **Authentication**: Required (JWT Bearer Token)
- **Description**: approve plugins

#### Response Schema

See [SuccessResponseDTO](#successresponsedto)

---

### Plugins Reject

- **Route**: `POST /api/plugins/{id}/reject`
- **Authentication**: Required (JWT Bearer Token)
- **Description**: reject plugins

#### Response Schema

See [SuccessResponseDTO](#successresponsedto)

---

### Plugins Categories

- **Route**: `GET /api/plugins/categories`
- **Authentication**: Required (JWT Bearer Token)
- **Description**: categories plugins

#### Response Schema

See [SuccessResponseDTO](#successresponsedto)

---

### Plugins My Plugins

- **Route**: `GET /api/plugins/my-plugins`
- **Authentication**: Required (JWT Bearer Token)
- **Description**: my plugins

#### Response Schema

See [SuccessResponseDTO](#successresponsedto)

---

## Projects

### Projects Index

- **Route**: `GET /api/projects`
- **Authentication**: Required (JWT Bearer Token)
- **Description**: Get paginated list of projects

#### Response Schema

See [PaginatedResponseDTO](#paginatedresponsedto)

---

### Projects Create

- **Route**: `POST /api/projects`
- **Authentication**: Required (JWT Bearer Token)
- **Description**: Create a new projects

#### Request Schema

See [CreateProjectsRequestDTO](#createprojectsrequestdto)

#### Response Schema

See [ProjectsResponseDTO](#projectsresponsedto)

---

### Projects Show

- **Route**: `GET /api/projects/{id}`
- **Authentication**: Required (JWT Bearer Token)
- **Description**: Get a specific projects

#### Response Schema

See [ProjectsResponseDTO](#projectsresponsedto)

---

### Projects Update

- **Route**: `PUT /api/projects/{id}`
- **Authentication**: Required (JWT Bearer Token)
- **Description**: Update an existing projects

#### Request Schema

See [UpdateProjectsRequestDTO](#updateprojectsrequestdto)

#### Response Schema

See [ProjectsResponseDTO](#projectsresponsedto)

---

### Projects Delete

- **Route**: `DELETE /api/projects/{id}`
- **Authentication**: Required (JWT Bearer Token)
- **Description**: Delete a projects

#### Response Schema

See [SuccessResponseDTO](#successresponsedto)

---

### Projects Duplicate

- **Route**: `POST /api/projects/{id}/duplicate`
- **Authentication**: Required (JWT Bearer Token)
- **Description**: Duplicate a projects

#### Request Schema

See [DuplicateProjectsRequestDTO](#duplicateprojectsrequestdto)

#### Response Schema

See [SuccessResponseDTO](#successresponsedto)

---

### Projects Public

- **Route**: `GET /api/projects/public`
- **Authentication**: Required (JWT Bearer Token)
- **Description**: public projects

#### Response Schema

See [SuccessResponseDTO](#successresponsedto)

---

### Projects Toggle Share

- **Route**: `POST /api/projects/{id}/share`
- **Authentication**: Required (JWT Bearer Token)
- **Description**: toggle projects

#### Response Schema

See [SuccessResponseDTO](#successresponsedto)

---

## Templates

### Templates List

- **Route**: `GET /api/templates`
- **Authentication**: Required (JWT Bearer Token)
- **Description**: Get paginated list of templates

#### Response Schema

See [PaginatedResponseDTO](#paginatedresponsedto)

---

### Templates Show

- **Route**: `GET /api/templates/{uuid}`
- **Authentication**: Required (JWT Bearer Token)
- **Description**: Get a specific templates

#### Response Schema

See [TemplatesResponseDTO](#templatesresponsedto)

---

### Templates Create

- **Route**: `POST /api/templates`
- **Authentication**: Required (JWT Bearer Token)
- **Description**: Create a new templates

#### Request Schema

See [CreateTemplatesRequestDTO](#createtemplatesrequestdto)

#### Response Schema

See [TemplatesResponseDTO](#templatesresponsedto)

---

### Templates Search

- **Route**: `GET /api/templates/search`
- **Authentication**: Required (JWT Bearer Token)
- **Description**: Search templates

#### Request Schema

See [SearchTemplatesRequestDTO](#searchtemplatesrequestdto)

#### Response Schema

See [TemplateSearchResponseDTO](#templatesearchresponsedto)

---

### Templates Use

- **Route**: `POST /api/templates/{uuid}/use`
- **Authentication**: Required (JWT Bearer Token)
- **Description**: use templates

#### Response Schema

See [SuccessResponseDTO](#successresponsedto)

---

### Templates Categories

- **Route**: `GET /api/templates/categories`
- **Authentication**: Required (JWT Bearer Token)
- **Description**: categories templates

#### Response Schema

See [SuccessResponseDTO](#successresponsedto)

---

## User

### User Profile

- **Route**: `GET /api/user/profile`
- **Authentication**: Required (JWT Bearer Token)
- **Description**: profile user

#### Response Schema

See [SuccessResponseDTO](#successresponsedto)

---

### User Update Profile

- **Route**: `PUT /api/user/profile`
- **Authentication**: Required (JWT Bearer Token)
- **Description**: Update an existing user

#### Request Schema

See [UpdateUserRequestDTO](#updateuserrequestdto)

#### Response Schema

See [UserResponseDTO](#userresponsedto)

---

### User Upload Avatar

- **Route**: `POST /api/user/avatar`
- **Authentication**: Required (JWT Bearer Token)
- **Description**: upload user

#### Response Schema

See [SuccessResponseDTO](#successresponsedto)

---

### User Change Password

- **Route**: `PUT /api/user/password`
- **Authentication**: Required (JWT Bearer Token)
- **Description**: change user

#### Response Schema

See [SuccessResponseDTO](#successresponsedto)

---

### User Download Data

- **Route**: `POST /api/user/settings/privacy/download`
- **Authentication**: Required (JWT Bearer Token)
- **Description**: download user

#### Response Schema

See [SuccessResponseDTO](#successresponsedto)

---

### User Export Data

- **Route**: `POST /api/user/settings/privacy/export`
- **Authentication**: Required (JWT Bearer Token)
- **Description**: export user

#### Response Schema

See [SuccessResponseDTO](#successresponsedto)

---

### User Delete Account

- **Route**: `DELETE /api/user/settings/privacy/delete`
- **Authentication**: Required (JWT Bearer Token)
- **Description**: Delete a user

#### Response Schema

See [SuccessResponseDTO](#successresponsedto)

---

### User Subscription

- **Route**: `GET /api/user/subscription`
- **Authentication**: Required (JWT Bearer Token)
- **Description**: subscription user

#### Response Schema

See [SuccessResponseDTO](#successresponsedto)

---


## Error Handling

All API endpoints return consistent error responses when something goes wrong.

### Error Response Schema

See [ErrorResponseDTO](#errorresponsedto)

### Common HTTP Status Codes

- **200**: Success
- **201**: Created
- **400**: Bad Request (validation errors)
- **401**: Unauthorized (invalid or missing token)
- **403**: Forbidden (insufficient permissions)
- **404**: Not Found
- **422**: Unprocessable Entity (validation failed)
- **429**: Too Many Requests (rate limited)
- **500**: Internal Server Error

---

## Development Notes

### TypeScript Integration

All schemas are provided as TypeScript interfaces for easy integration with frontend applications.

### Authentication

Include the JWT token in the Authorization header:

```typescript
headers: {
  'Authorization': `Bearer ${token}`,
  'Content-Type': 'application/json'
}
```

---

*This documentation was auto-generated from the backend DTOs and routes. To regenerate, run: `php scripts/generate-api-docs.php`*

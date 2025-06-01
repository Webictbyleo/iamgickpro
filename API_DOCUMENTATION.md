# Design Platform API

Comprehensive API for the modern web-based design platform

**Version:** 1.0.0

**Generated on:** 2025-06-01 21:39:24
**Generator:** Enhanced API Documentation Generator v2.0
**Symfony Version:** 7.0.10
**PHP Version:** 8.4.7

**Contact:** API Support ([api-support@example.com](mailto:api-support@example.com))

**License:** [MIT](https://opensource.org/licenses/MIT)

## Servers

- **Production server:** `https://api.example.com/v1`
- **Staging server:** `https://staging-api.example.com/v1`
- **Development server:** `http://localhost:8000`

## Authentication

### BearerAuth

- **Type:** Http
- **Scheme:** Bearer
- **Bearer Format:** JWT

## Table of Contents

- [MediaController](#mediacontroller) *(9 routes)*
- [DesignController](#designcontroller) *(8 routes)*
- [AuthController](#authcontroller) *(6 routes)*
- [ProjectController](#projectcontroller) *(8 routes)*
- [LayerController](#layercontroller) *(7 routes)*
- [ExportJobController](#exportjobcontroller) *(10 routes)*
- [TemplateController](#templatecontroller) *(6 routes)*
- [SearchController](#searchcontroller) *(5 routes)*
- [PluginController](#plugincontroller) *(12 routes)*
- [UserController](#usercontroller) *(8 routes)*

---

## MediaController

Media Controller

Manages media file operations including upload, retrieval, updating, and deletion.
Handles media search, duplication, stock media integration, and bulk operations.
All endpoints require authentication and enforce user ownership for security.

### GET 

List media files for authenticated user

Returns a paginated list of media files belonging to the authenticated user.
Supports filtering by type, format, and search functionality.

#### Request Body

**SearchMediaRequestDTO**

Data Transfer Object for media search and filtering requests.

Handles search operations for media files with advanced filtering
capabilities including type, source, pagination, and tag-based
filtering. Used by the media library to provide rich search
functionality for users to find specific media files.

Properties:

- **page** (int) *optional* *Range(min: 1, notInRangeMessage: 'Page must be {{ min }} or greater')*
  Page number for pagination.
  
  Specifies which page of media results to return.
  Must be 1 or greater for valid pagination.
  *Example: `2`*

- **limit** (int) *optional* *Range(min: 1, max: 50, notInRangeMessage: 'Limit must be between {{ min }} and {{ max }}')*
  Number of media items per page.
  
  Controls how many media files are returned per page.
  Limited to a maximum of 50 to maintain performance.
  *Example: `2`*

- **type** (string) *optional* *Choice(choices: ['image', 'video', 'audio'], message: 'Type must be one of: image, video, audio')*
  Media type filter.
  
  Filters media by type (image, video, or audio).
  When null, all media types are included in results.
  *Example: `image`*

- **source** (string) *optional* *Choice(choices: ['upload', 'unsplash', 'pexels', 'pixabay'], message: 'Source must be one of: upload, unsplash, pexels, pixabay')*
  Media source filter.
  
  Filters media by its source origin (upload for user uploads,
  or stock photo providers like unsplash, pexels, pixabay).
  When null, all sources are included.
  *Example: `upload`*

- **search** (string) *optional* *Length(max: 255, maxMessage: 'Search query cannot be longer than {{ limit }} characters')*
  Search query term.
  
  Text to search for in media file names, descriptions,
  and metadata. When null, no text-based filtering is applied.
  *Example: `"example_string"`*

- **tags** (string) *optional*
  Comma-separated list of tags for filtering.
  
  Tags to filter media by. Multiple tags can be specified
  separated by commas. The system will find media that
  matches any of the specified tags.
  *Example: `"example_string"`*


#### Response

**JsonResponse**

Response represents an HTTP response in JSON format.

Note that this class does not force the returned JSON content to be an
object. It is however recommended that you do return an object as it
protects yourself against XSSI and JSON-JavaScript Hijacking.

Properties:

- **data** (mixed)
  *Example: `null`*

- **callback** (string) *optional*
  *Example: `"example_string"`*

- **encodingOptions** (int) *optional*
  *Example: `42`*

- **headers** (Symfony\Component\HttpFoundation\ResponseHeaderBag)
  *Example: `null`*

- **content** (string)
  *Example: `"example_string"`*

- **version** (string)
  *Example: `"example_string"`*

- **statusCode** (int)
  *Example: `42`*

- **statusText** (string)
  *Example: `"example_string"`*

- **charset** (string) *optional*
  *Example: `"example_string"`*

- **statusTexts** (array) *optional*
  Status codes translation table.
  
  The list of codes is complete according to the
  {@link https://www.iana.org/assignments/http-status-codes/http-status-codes.xhtml Hypertext Transfer Protocol (HTTP) Status Code Registry}
  (last updated 2021-10-01).
  
  Unless otherwise noted, the status code is defined in RFC2616.
  *Example: `[]`*


---

### POST 

Create a new media file entry

Creates a new media file record in the database with the provided metadata.
This endpoint handles media file upload processing and validation.
All uploaded media files are associated with the authenticated user.

#### Request Body

**CreateMediaRequestDTO**

Request DTO for creating a new media item in the library.

This DTO handles the creation of media assets including images, videos,
and audio files from various sources (uploads, stock photo APIs, etc.).
Includes comprehensive metadata and organizational features.

Properties:

- **name** (string) *NotBlank(message: 'Media name is required'), Length(min: 1, max: 255, minMessage: 'Media name must be at least {{ limit }} character long', maxMessage: 'Media name cannot be longer than {{ limit }} characters')*
  Display name for the media item.
  
  This name is shown in the media library and used for searching.
  Should be descriptive and meaningful to help users identify
  the content. Must be between 1-255 characters.
  *Example: `"example_string"`*

- **type** (string) *NotBlank(message: 'Media type is required'), Choice(choices: ['image', 'video', 'audio'], message: 'Type must be one of: image, video, audio')*
  Type of media content.
  
  Categorizes the media into broad types for filtering and
  appropriate handling in the editor. Supported types:
  - image: Static images (JPEG, PNG, SVG, etc.)
  - video: Video files (MP4, WebM, etc.)
  - audio: Audio files (MP3, WAV, etc.)
  *Example: `image`*

- **mimeType** (string) *NotBlank(message: 'MIME type is required')*
  MIME type of the media file.
  
  Specifies the exact format of the media file for proper
  handling by browsers and processing tools. Examples:
  - image/jpeg, image/png, image/svg+xml
  - video/mp4, video/webm
  - audio/mpeg, audio/wav
  *Example: `"example_string"`*

- **size** (int) *PositiveOrZero(message: 'Size must be a positive number')*
  File size in bytes.
  
  Used for storage quota management, upload progress,
  and performance optimization decisions. Must be a
  positive number representing the file size in bytes.
  *Example: `42`*

- **url** (string) *NotBlank(message: 'URL is required'), Url(message: 'URL must be a valid URL')*
  Direct URL to access the media file.
  
  This is the primary URL used to display or download the media.
  Should be a publicly accessible HTTPS URL for security and
  browser compatibility.
  *Example: `"example_string"`*

- **thumbnailUrl** (string) *optional* *Url(message: 'Thumbnail URL must be a valid URL')*
  Optional URL to a thumbnail or preview image.
  
  Used for quick previews in the media library and layer
  thumbnails. For videos, this could be a frame capture.
  For audio, this could be a waveform visualization.
  *Example: `"example_string"`*

- **width** (int) *optional* *PositiveOrZero(message: 'Width must be a positive number')*
  Width of the media in pixels (for visual media).
  
  Essential for layout calculations and aspect ratio
  preservation. Not applicable for audio files.
  *Example: `42`*

- **height** (int) *optional* *PositiveOrZero(message: 'Height must be a positive number')*
  Height of the media in pixels (for visual media).
  
  Essential for layout calculations and aspect ratio
  preservation. Not applicable for audio files.
  *Example: `42`*

- **duration** (float) *optional* *PositiveOrZero(message: 'Duration must be a positive number')*
  Duration in seconds (for time-based media).
  
  Used for video and audio files to display playback
  length and for timeline-based editing features.
  Not applicable for static images.
  *Example: `3.14`*

- **source** (string) *optional* *Choice(choices: ['upload', 'unsplash', 'pexels', 'pixabay'], message: 'Source must be one of: upload, unsplash, pexels, pixabay')*
  Source platform or service where the media originated.
  
  Tracks the origin of media for attribution, licensing,
  and integration purposes. Supported sources:
  - upload: User-uploaded content
  - unsplash: Unsplash stock photos
  - pexels: Pexels stock photos
  - pixabay: Pixabay stock media
  *Example: `upload`*

- **sourceId** (string) *optional*
  Unique identifier from the source platform.
  
  For stock photo services, this is their internal ID for the media.
  For uploads, this may be null or a user-defined reference.
  Used for attribution and preventing duplicate imports.
  *Example: `"example_string"`*

- **metadata** (App\DTO\ValueObject\MediaMetadata) *optional* *Valid*
  Technical metadata about the media file.
  
  Contains detailed information about the media file including
  file size, MIME type, dimensions, and format-specific data
  like codec information for videos or EXIF data for images.
  
  Used for display, processing, and compatibility checks.

  **Union Type Details:**

  **MediaMetadata**
  
  Media file metadata containing technical information about the media file
  
  
  
  Properties:
  
    - **fileSize** (int) *Type(type: 'integer', message: 'File size must be an integer'), Positive(message: 'File size must be positive')*
      File size in bytes of the media file
      
      
      *Example: `42`*
  
    - **mimeType** (string) *NotBlank(message: 'MIME type is required'), Length(max: 100, maxMessage: 'MIME type cannot exceed 100 characters')*
      MIME type of the media file (e.g., image/jpeg, video/mp4, audio/mpeg)
      
      
      *Example: `"example_string"`*
  
    - **width** (int) *optional* *Type(type: 'integer', message: 'Width must be an integer'), Positive(message: 'Width must be positive')*
      Width of the media in pixels (for images and videos)
      
      
      *Example: `42`*
  
    - **height** (int) *optional* *Type(type: 'integer', message: 'Height must be an integer'), Positive(message: 'Height must be positive')*
      Height of the media in pixels (for images and videos)
      
      
      *Example: `42`*
  
    - **duration** (float) *optional* *Type(type: 'float', message: 'Duration must be a number'), Positive(message: 'Duration must be positive')*
      Duration of the media in seconds (for audio and video files)
      
      
      *Example: `3.14`*
  
    - **bitrate** (int) *optional* *Type(type: 'integer', message: 'Bitrate must be an integer'), Positive(message: 'Bitrate must be positive')*
      Bitrate of the media in bits per second (for audio and video)
      
      
      *Example: `42`*
  
    - **sampleRate** (int) *optional* *Type(type: 'integer', message: 'Sample rate must be an integer'), Positive(message: 'Sample rate must be positive')*
      Audio sample rate in Hz (for audio files)
      
      
      *Example: `42`*
  
    - **channels** (int) *optional* *Type(type: 'integer', message: 'Channels must be an integer'), Positive(message: 'Channels must be positive')*
      Number of audio channels (1 = mono, 2 = stereo, etc.)
      
      
      *Example: `42`*
  
    - **colorSpace** (string) *optional* *Length(max: 100, maxMessage: 'Color space cannot exceed 100 characters')*
      Color space of the image (e.g., sRGB, Adobe RGB, CMYK)
      
      
      *Example: `"example_string"`*
  
    - **hasTransparency** (bool) *optional* *Type(type: 'boolean', message: 'Has transparency must be boolean')*
      Whether the image has transparency/alpha channel support
      
      
      *Example: `true`*
  
    - **frameRate** (int) *optional* *Type(type: 'integer', message: 'Frame rate must be an integer'), Positive(message: 'Frame rate must be positive')*
      Frame rate of video files in frames per second
      
      
      *Example: `42`*
  
    - **codec** (string) *optional* *Length(max: 255, maxMessage: 'Codec cannot exceed 255 characters')*
      Codec used to encode the media file (e.g., H.264, VP9, AAC)
      
      
      *Example: `"example_string"`*
  
    - **aspectRatio** (float) *optional* *Type(type: 'float', message: 'Aspect ratio must be a number'), Positive(message: 'Aspect ratio must be positive')*
      Aspect ratio of the media (width/height, e.g., 1.777 for 16:9)
      
      
      *Example: `3.14`*
  
  
  *Example: `null`*

- **tags** (array) *optional* *Valid*
  Organizational tags for categorizing and searching media.
  
  Tags help users organize their media library and make content
  discoverable through search and filtering. Each tag must be
  1-50 characters and contain only alphanumeric characters,
  spaces, hyphens, and underscores.
  *Example: `[]`*

- **attribution** (string) *optional*
  Attribution text for the media creator.
  
  Required for some stock photo services and user-generated content.
  Displayed in media details and export metadata to comply with
  licensing requirements.
  *Example: `"example_string"`*

- **license** (string) *optional*
  License type under which the media is distributed.
  
  Defines usage rights and restrictions for the media.
  Common values include 'CC0', 'CC BY', 'Commercial', 'Editorial'.
  Used to ensure proper usage compliance.
  *Example: `"example_string"`*

- **isPremium** (bool) *optional* *Type('bool', message: 'isPremium must be a boolean')*
  Whether this media requires a premium subscription to use.
  
  Premium media may have additional licensing costs or
  require special subscription tiers. Affects availability
  and usage tracking.
  *Example: `true`*

- **isActive** (bool) *optional* *Type('bool', message: 'isActive must be a boolean')*
  Whether this media is currently active and available for use.
  
  Inactive media is hidden from search results and cannot be
  used in new designs. Existing usages remain functional.
  Used for content moderation and lifecycle management.
  *Example: `true`*


#### Response

**JsonResponse**

Response represents an HTTP response in JSON format.

Note that this class does not force the returned JSON content to be an
object. It is however recommended that you do return an object as it
protects yourself against XSSI and JSON-JavaScript Hijacking.

Properties:

- **data** (mixed)
  *Example: `null`*

- **callback** (string) *optional*
  *Example: `"example_string"`*

- **encodingOptions** (int) *optional*
  *Example: `42`*

- **headers** (Symfony\Component\HttpFoundation\ResponseHeaderBag)
  *Example: `null`*

- **content** (string)
  *Example: `"example_string"`*

- **version** (string)
  *Example: `"example_string"`*

- **statusCode** (int)
  *Example: `42`*

- **statusText** (string)
  *Example: `"example_string"`*

- **charset** (string) *optional*
  *Example: `"example_string"`*

- **statusTexts** (array) *optional*
  Status codes translation table.
  
  The list of codes is complete according to the
  {@link https://www.iana.org/assignments/http-status-codes/http-status-codes.xhtml Hypertext Transfer Protocol (HTTP) Status Code Registry}
  (last updated 2021-10-01).
  
  Unless otherwise noted, the status code is defined in RFC2616.
  *Example: `[]`*


---

### DELETE /bulk/delete

Bulk delete multiple media files

Deletes multiple media files in a single operation for efficiency.
Processes each file individually with proper permission checks.
Returns detailed results including successful deletions and failures.
Users can only delete media files they own.

#### Request Body

**BulkDeleteMediaRequestDTO**

Data Transfer Object for bulk media deletion requests.

Handles bulk deletion of multiple media files by their UUIDs.
Used by the media management system to allow users to delete
multiple media files in a single operation with validation
and permission checks.

Properties:

- **uuids** (array) *NotBlank(message: 'UUIDs are required'), Type('array', message: 'UUIDs must be an array'), Count(min: 1, max: 100, minMessage: 'At least one UUID is required', maxMessage: 'Cannot delete more than 100 items at once'), All([Symfony\Component\Validator\Constraints\NotBlank, Symfony\Component\Validator\Constraints\Uuid])*
  Array of media file UUIDs to delete.
  
  Each UUID represents a media file that the user wants to delete.
  The system will validate ownership and existence before deletion.
  Maximum 100 items can be deleted in a single request to prevent
  performance issues and timeouts.
  *Example: `[]`*


#### Response

**JsonResponse**

Response represents an HTTP response in JSON format.

Note that this class does not force the returned JSON content to be an
object. It is however recommended that you do return an object as it
protects yourself against XSSI and JSON-JavaScript Hijacking.

Properties:

- **data** (mixed)
  *Example: `null`*

- **callback** (string) *optional*
  *Example: `"example_string"`*

- **encodingOptions** (int) *optional*
  *Example: `42`*

- **headers** (Symfony\Component\HttpFoundation\ResponseHeaderBag)
  *Example: `null`*

- **content** (string)
  *Example: `"example_string"`*

- **version** (string)
  *Example: `"example_string"`*

- **statusCode** (int)
  *Example: `42`*

- **statusText** (string)
  *Example: `"example_string"`*

- **charset** (string) *optional*
  *Example: `"example_string"`*

- **statusTexts** (array) *optional*
  Status codes translation table.
  
  The list of codes is complete according to the
  {@link https://www.iana.org/assignments/http-status-codes/http-status-codes.xhtml Hypertext Transfer Protocol (HTTP) Status Code Registry}
  (last updated 2021-10-01).
  
  Unless otherwise noted, the status code is defined in RFC2616.
  *Example: `[]`*


---

### POST /duplicate/{uuid}

Duplicate a media file

Creates a copy of an existing media file for the authenticated user.
The duplicated media inherits all properties from the original but gets
a new UUID and is owned by the current user. This allows users to
create personal copies of accessible media files.

#### Parameters

- **uuid** (string)

#### Request Body

**DuplicateMediaRequestDTO**

Data Transfer Object for media file duplication requests.

Handles the duplication of media files including images, videos,
and other assets. Creates a personal copy of accessible media
files for the authenticated user with optional name customization.
Used by the media library for creating user-owned copies of media.

Properties:

- **name** (string) *optional* *Length(max: 255, maxMessage: 'Media name cannot be longer than {{ limit }} characters')*
  Custom name for the duplicated media file.
  
  If provided, this will be used as the name for the duplicated
  media file. If null, the system will automatically generate
  a name like "Copy of {original name}". Must not exceed 255
  characters if provided.
  *Example: `"example_string"`*


#### Response

**JsonResponse**

Response represents an HTTP response in JSON format.

Note that this class does not force the returned JSON content to be an
object. It is however recommended that you do return an object as it
protects yourself against XSSI and JSON-JavaScript Hijacking.

Properties:

- **data** (mixed)
  *Example: `null`*

- **callback** (string) *optional*
  *Example: `"example_string"`*

- **encodingOptions** (int) *optional*
  *Example: `42`*

- **headers** (Symfony\Component\HttpFoundation\ResponseHeaderBag)
  *Example: `null`*

- **content** (string)
  *Example: `"example_string"`*

- **version** (string)
  *Example: `"example_string"`*

- **statusCode** (int)
  *Example: `42`*

- **statusText** (string)
  *Example: `"example_string"`*

- **charset** (string) *optional*
  *Example: `"example_string"`*

- **statusTexts** (array) *optional*
  Status codes translation table.
  
  The list of codes is complete according to the
  {@link https://www.iana.org/assignments/http-status-codes/http-status-codes.xhtml Hypertext Transfer Protocol (HTTP) Status Code Registry}
  (last updated 2021-10-01).
  
  Unless otherwise noted, the status code is defined in RFC2616.
  *Example: `[]`*


---

### GET /search

Search media files

Performs advanced search across user's media library with filtering and sorting.
Supports full-text search on filenames, descriptions, and tags.
Returns paginated results with comprehensive media metadata.

#### Request Body

**SearchMediaRequestDTO**

Data Transfer Object for media search and filtering requests.

Handles search operations for media files with advanced filtering
capabilities including type, source, pagination, and tag-based
filtering. Used by the media library to provide rich search
functionality for users to find specific media files.

Properties:

- **page** (int) *optional* *Range(min: 1, notInRangeMessage: 'Page must be {{ min }} or greater')*
  Page number for pagination.
  
  Specifies which page of media results to return.
  Must be 1 or greater for valid pagination.
  *Example: `2`*

- **limit** (int) *optional* *Range(min: 1, max: 50, notInRangeMessage: 'Limit must be between {{ min }} and {{ max }}')*
  Number of media items per page.
  
  Controls how many media files are returned per page.
  Limited to a maximum of 50 to maintain performance.
  *Example: `2`*

- **type** (string) *optional* *Choice(choices: ['image', 'video', 'audio'], message: 'Type must be one of: image, video, audio')*
  Media type filter.
  
  Filters media by type (image, video, or audio).
  When null, all media types are included in results.
  *Example: `image`*

- **source** (string) *optional* *Choice(choices: ['upload', 'unsplash', 'pexels', 'pixabay'], message: 'Source must be one of: upload, unsplash, pexels, pixabay')*
  Media source filter.
  
  Filters media by its source origin (upload for user uploads,
  or stock photo providers like unsplash, pexels, pixabay).
  When null, all sources are included.
  *Example: `upload`*

- **search** (string) *optional* *Length(max: 255, maxMessage: 'Search query cannot be longer than {{ limit }} characters')*
  Search query term.
  
  Text to search for in media file names, descriptions,
  and metadata. When null, no text-based filtering is applied.
  *Example: `"example_string"`*

- **tags** (string) *optional*
  Comma-separated list of tags for filtering.
  
  Tags to filter media by. Multiple tags can be specified
  separated by commas. The system will find media that
  matches any of the specified tags.
  *Example: `"example_string"`*


#### Response

**JsonResponse**

Response represents an HTTP response in JSON format.

Note that this class does not force the returned JSON content to be an
object. It is however recommended that you do return an object as it
protects yourself against XSSI and JSON-JavaScript Hijacking.

Properties:

- **data** (mixed)
  *Example: `null`*

- **callback** (string) *optional*
  *Example: `"example_string"`*

- **encodingOptions** (int) *optional*
  *Example: `42`*

- **headers** (Symfony\Component\HttpFoundation\ResponseHeaderBag)
  *Example: `null`*

- **content** (string)
  *Example: `"example_string"`*

- **version** (string)
  *Example: `"example_string"`*

- **statusCode** (int)
  *Example: `42`*

- **statusText** (string)
  *Example: `"example_string"`*

- **charset** (string) *optional*
  *Example: `"example_string"`*

- **statusTexts** (array) *optional*
  Status codes translation table.
  
  The list of codes is complete according to the
  {@link https://www.iana.org/assignments/http-status-codes/http-status-codes.xhtml Hypertext Transfer Protocol (HTTP) Status Code Registry}
  (last updated 2021-10-01).
  
  Unless otherwise noted, the status code is defined in RFC2616.
  *Example: `[]`*


---

### GET /stock/search

Search stock media from external providers

Integrates with external stock media APIs (Unsplash, Pexels, Pixabay, etc.)
to provide users with access to high-quality stock images and videos.
Results include licensing information and attribution requirements.
Currently in development - returns empty results with implementation notice.

#### Request Body

**StockSearchRequestDTO**

Data Transfer Object for stock media search requests.

Handles search operations for stock media from external providers
like Unsplash, Pexels, and Pixabay. Provides structured search
parameters for integrating with third-party stock media APIs
to expand the available media library for users.

Properties:

- **query** (string) *NotBlank(message: 'Query is required for stock search'), Length(min: 1, max: 255, minMessage: 'Query must be at least {{ limit }} character long', maxMessage: 'Query cannot be longer than {{ limit }} characters')*
  Search query for stock media.
  
  The search term to find relevant stock photos and videos
  from external providers. Must be descriptive enough to
  return meaningful results from stock media APIs.
  *Example: `"example_string"`*

- **page** (int) *optional* *Range(min: 1, notInRangeMessage: 'Page must be {{ min }} or greater')*
  Page number for stock media results pagination.
  
  Specifies which page of stock media results to return
  from the external provider's API. Used to implement
  pagination for large result sets.
  *Example: `2`*

- **limit** (int) *optional* *Range(min: 1, max: 50, notInRangeMessage: 'Limit must be between {{ min }} and {{ max }}')*
  Number of stock media items per page.
  
  Controls how many stock media items are requested from
  the external provider. Limited to prevent API rate
  limiting and maintain performance.
  *Example: `2`*

- **type** (string) *optional* *Choice(choices: ['image', 'video'], message: 'Type must be one of: image, video')*
  Type of stock media to search for.
  
  Specifies whether to search for images or videos from
  the stock media provider. Defaults to images as they
  are more commonly used in designs.
  *Example: `image`*

- **source** (string) *optional* *Choice(choices: ['unsplash', 'pexels', 'pixabay'], message: 'Source must be one of: unsplash, pexels, pixabay')*
  Stock media provider source.
  
  Specifies which external stock media provider to search.
  Each provider has different content, licensing, and API
  characteristics. Defaults to Unsplash for high-quality images.
  *Example: `unsplash`*


#### Response

**JsonResponse**

Response represents an HTTP response in JSON format.

Note that this class does not force the returned JSON content to be an
object. It is however recommended that you do return an object as it
protects yourself against XSSI and JSON-JavaScript Hijacking.

Properties:

- **data** (mixed)
  *Example: `null`*

- **callback** (string) *optional*
  *Example: `"example_string"`*

- **encodingOptions** (int) *optional*
  *Example: `42`*

- **headers** (Symfony\Component\HttpFoundation\ResponseHeaderBag)
  *Example: `null`*

- **content** (string)
  *Example: `"example_string"`*

- **version** (string)
  *Example: `"example_string"`*

- **statusCode** (int)
  *Example: `42`*

- **statusText** (string)
  *Example: `"example_string"`*

- **charset** (string) *optional*
  *Example: `"example_string"`*

- **statusTexts** (array) *optional*
  Status codes translation table.
  
  The list of codes is complete according to the
  {@link https://www.iana.org/assignments/http-status-codes/http-status-codes.xhtml Hypertext Transfer Protocol (HTTP) Status Code Registry}
  (last updated 2021-10-01).
  
  Unless otherwise noted, the status code is defined in RFC2616.
  *Example: `[]`*


---

### GET /{uuid}

Get details of a specific media file

Returns detailed information about a single media file including metadata.
Only allows access to media files owned by the authenticated user.

#### Parameters

- **uuid** (string)

#### Response

**JsonResponse**

Response represents an HTTP response in JSON format.

Note that this class does not force the returned JSON content to be an
object. It is however recommended that you do return an object as it
protects yourself against XSSI and JSON-JavaScript Hijacking.

Properties:

- **data** (mixed)
  *Example: `null`*

- **callback** (string) *optional*
  *Example: `"example_string"`*

- **encodingOptions** (int) *optional*
  *Example: `42`*

- **headers** (Symfony\Component\HttpFoundation\ResponseHeaderBag)
  *Example: `null`*

- **content** (string)
  *Example: `"example_string"`*

- **version** (string)
  *Example: `"example_string"`*

- **statusCode** (int)
  *Example: `42`*

- **statusText** (string)
  *Example: `"example_string"`*

- **charset** (string) *optional*
  *Example: `"example_string"`*

- **statusTexts** (array) *optional*
  Status codes translation table.
  
  The list of codes is complete according to the
  {@link https://www.iana.org/assignments/http-status-codes/http-status-codes.xhtml Hypertext Transfer Protocol (HTTP) Status Code Registry}
  (last updated 2021-10-01).
  
  Unless otherwise noted, the status code is defined in RFC2616.
  *Example: `[]`*


---

### PUT /{uuid}

Update media file metadata

Updates the metadata and properties of an existing media file.
Only allows updating specific fields like name, metadata, tags, and status flags.
Core file properties like URL, type, and dimensions cannot be modified.
Users can only update media files they own.

#### Parameters

- **uuid** (string)

#### Request Body

**UpdateMediaRequestDTO**

Request DTO for updating an existing media item.

This DTO handles partial updates to media assets, allowing clients to
update only the fields they want to change. All fields are optional
and null values indicate no change should be made.

Properties:

- **name** (string) *optional* *Length(min: 1, max: 255, minMessage: 'File name must be at least 1 character', maxMessage: 'File name cannot exceed 255 characters')*
  Updated display name for the media item.
  
  If provided, replaces the current media name. Must be
  between 1-255 characters. Null indicates no change.
  *Example: `"example_string"`*

- **description** (string) *optional* *Length(max: 1000, maxMessage: 'Description cannot exceed 1000 characters')*
  Updated description for the media item.
  
  If provided, replaces the current description. Maximum
  1000 characters. Null indicates no change.
  *Example: `"example_string"`*

- **tags** (array) *optional* *Valid*
  Updated organizational tags for the media.
  
  If provided, replaces the current tag set. Each tag must be
  1-50 characters and contain only alphanumeric characters,
  spaces, hyphens, and underscores. Null indicates no change.
  *Example: `[]`*

- **metadata** (App\DTO\ValueObject\MediaMetadata) *optional* *Valid*
  Updated technical metadata for the media file.
  
  If provided, replaces or merges with current metadata.
  Contains detailed information about the media file including
  dimensions, codec info, EXIF data, etc. Null indicates no change.

  **Union Type Details:**

  **MediaMetadata**
  
  Media file metadata containing technical information about the media file
  
  
  
  Properties:
  
    - **fileSize** (int) *Type(type: 'integer', message: 'File size must be an integer'), Positive(message: 'File size must be positive')*
      File size in bytes of the media file
      
      
      *Example: `42`*
  
    - **mimeType** (string) *NotBlank(message: 'MIME type is required'), Length(max: 100, maxMessage: 'MIME type cannot exceed 100 characters')*
      MIME type of the media file (e.g., image/jpeg, video/mp4, audio/mpeg)
      
      
      *Example: `"example_string"`*
  
    - **width** (int) *optional* *Type(type: 'integer', message: 'Width must be an integer'), Positive(message: 'Width must be positive')*
      Width of the media in pixels (for images and videos)
      
      
      *Example: `42`*
  
    - **height** (int) *optional* *Type(type: 'integer', message: 'Height must be an integer'), Positive(message: 'Height must be positive')*
      Height of the media in pixels (for images and videos)
      
      
      *Example: `42`*
  
    - **duration** (float) *optional* *Type(type: 'float', message: 'Duration must be a number'), Positive(message: 'Duration must be positive')*
      Duration of the media in seconds (for audio and video files)
      
      
      *Example: `3.14`*
  
    - **bitrate** (int) *optional* *Type(type: 'integer', message: 'Bitrate must be an integer'), Positive(message: 'Bitrate must be positive')*
      Bitrate of the media in bits per second (for audio and video)
      
      
      *Example: `42`*
  
    - **sampleRate** (int) *optional* *Type(type: 'integer', message: 'Sample rate must be an integer'), Positive(message: 'Sample rate must be positive')*
      Audio sample rate in Hz (for audio files)
      
      
      *Example: `42`*
  
    - **channels** (int) *optional* *Type(type: 'integer', message: 'Channels must be an integer'), Positive(message: 'Channels must be positive')*
      Number of audio channels (1 = mono, 2 = stereo, etc.)
      
      
      *Example: `42`*
  
    - **colorSpace** (string) *optional* *Length(max: 100, maxMessage: 'Color space cannot exceed 100 characters')*
      Color space of the image (e.g., sRGB, Adobe RGB, CMYK)
      
      
      *Example: `"example_string"`*
  
    - **hasTransparency** (bool) *optional* *Type(type: 'boolean', message: 'Has transparency must be boolean')*
      Whether the image has transparency/alpha channel support
      
      
      *Example: `true`*
  
    - **frameRate** (int) *optional* *Type(type: 'integer', message: 'Frame rate must be an integer'), Positive(message: 'Frame rate must be positive')*
      Frame rate of video files in frames per second
      
      
      *Example: `42`*
  
    - **codec** (string) *optional* *Length(max: 255, maxMessage: 'Codec cannot exceed 255 characters')*
      Codec used to encode the media file (e.g., H.264, VP9, AAC)
      
      
      *Example: `"example_string"`*
  
    - **aspectRatio** (float) *optional* *Type(type: 'float', message: 'Aspect ratio must be a number'), Positive(message: 'Aspect ratio must be positive')*
      Aspect ratio of the media (width/height, e.g., 1.777 for 16:9)
      
      
      *Example: `3.14`*
  
  
  *Example: `null`*

- **isPremium** (bool) *optional* *Type('bool', message: 'Is premium must be a boolean')*
  Updated premium status for the media.
  
  If provided, changes whether the media requires a premium
  subscription to use. Null indicates no change.
  *Example: `true`*

- **isActive** (bool) *optional* *Type('bool', message: 'Is active must be a boolean')*
  Updated active status for the media.
  
  If provided, changes whether the media is currently available
  for use in new designs. Null indicates no change.
  *Example: `true`*

- **isPublic** (bool) *optional* *Type('bool', message: 'Is public must be a boolean')*
  Updated public visibility for the media.
  
  If provided, changes whether the media is publicly accessible
  and can appear in community galleries. Null indicates no change.
  *Example: `true`*


#### Response

**JsonResponse**

Response represents an HTTP response in JSON format.

Note that this class does not force the returned JSON content to be an
object. It is however recommended that you do return an object as it
protects yourself against XSSI and JSON-JavaScript Hijacking.

Properties:

- **data** (mixed)
  *Example: `null`*

- **callback** (string) *optional*
  *Example: `"example_string"`*

- **encodingOptions** (int) *optional*
  *Example: `42`*

- **headers** (Symfony\Component\HttpFoundation\ResponseHeaderBag)
  *Example: `null`*

- **content** (string)
  *Example: `"example_string"`*

- **version** (string)
  *Example: `"example_string"`*

- **statusCode** (int)
  *Example: `42`*

- **statusText** (string)
  *Example: `"example_string"`*

- **charset** (string) *optional*
  *Example: `"example_string"`*

- **statusTexts** (array) *optional*
  Status codes translation table.
  
  The list of codes is complete according to the
  {@link https://www.iana.org/assignments/http-status-codes/http-status-codes.xhtml Hypertext Transfer Protocol (HTTP) Status Code Registry}
  (last updated 2021-10-01).
  
  Unless otherwise noted, the status code is defined in RFC2616.
  *Example: `[]`*


---

### DELETE /{uuid}

Delete a media file

Permanently removes a media file record from the database.
This operation also triggers cleanup of associated file storage.
Users can only delete media files they own for security.

#### Parameters

- **uuid** (string)

#### Response

**JsonResponse**

Response represents an HTTP response in JSON format.

Note that this class does not force the returned JSON content to be an
object. It is however recommended that you do return an object as it
protects yourself against XSSI and JSON-JavaScript Hijacking.

Properties:

- **data** (mixed)
  *Example: `null`*

- **callback** (string) *optional*
  *Example: `"example_string"`*

- **encodingOptions** (int) *optional*
  *Example: `42`*

- **headers** (Symfony\Component\HttpFoundation\ResponseHeaderBag)
  *Example: `null`*

- **content** (string)
  *Example: `"example_string"`*

- **version** (string)
  *Example: `"example_string"`*

- **statusCode** (int)
  *Example: `42`*

- **statusText** (string)
  *Example: `"example_string"`*

- **charset** (string) *optional*
  *Example: `"example_string"`*

- **statusTexts** (array) *optional*
  Status codes translation table.
  
  The list of codes is complete according to the
  {@link https://www.iana.org/assignments/http-status-codes/http-status-codes.xhtml Hypertext Transfer Protocol (HTTP) Status Code Registry}
  (last updated 2021-10-01).
  
  Unless otherwise noted, the status code is defined in RFC2616.
  *Example: `[]`*


---

## DesignController

Design Controller

Manages design operations including creation, retrieval, updating, and deletion.
Handles design duplication, thumbnail management, and search functionality.
All endpoints require authentication and enforce user ownership for security.

### GET 

List designs for authenticated user

Returns a paginated list of designs belonging to the authenticated user.
Supports filtering by project, status, and search functionality.

#### Parameters

- **request** (Symfony\Component\HttpFoundation\Request)

#### Response

**JsonResponse**

Response represents an HTTP response in JSON format.

Note that this class does not force the returned JSON content to be an
object. It is however recommended that you do return an object as it
protects yourself against XSSI and JSON-JavaScript Hijacking.

Properties:

- **data** (mixed)
  *Example: `null`*

- **callback** (string) *optional*
  *Example: `"example_string"`*

- **encodingOptions** (int) *optional*
  *Example: `42`*

- **headers** (Symfony\Component\HttpFoundation\ResponseHeaderBag)
  *Example: `null`*

- **content** (string)
  *Example: `"example_string"`*

- **version** (string)
  *Example: `"example_string"`*

- **statusCode** (int)
  *Example: `42`*

- **statusText** (string)
  *Example: `"example_string"`*

- **charset** (string) *optional*
  *Example: `"example_string"`*

- **statusTexts** (array) *optional*
  Status codes translation table.
  
  The list of codes is complete according to the
  {@link https://www.iana.org/assignments/http-status-codes/http-status-codes.xhtml Hypertext Transfer Protocol (HTTP) Status Code Registry}
  (last updated 2021-10-01).
  
  Unless otherwise noted, the status code is defined in RFC2616.
  *Example: `[]`*


---

### POST 

Create a new design

Creates a new design with the provided information and associates it with the authenticated user.
Validates design data and initializes default canvas settings.

#### Request Body

**CreateDesignRequestDTO**

Request DTO for creating a new design within a project.

This DTO handles the creation of new designs with specified canvas
dimensions, initial design settings, and organizational metadata.

Properties:

- **name** (string) *NotBlank(message: 'Design name is required'), Length(min: 1, max: 255, minMessage: 'Design name must be at least 1 character', maxMessage: 'Design name cannot exceed 255 characters')*
  Display name for the new design.
  
  This name is used throughout the application interface
  and should be descriptive and meaningful to the user.
  Must be between 1-255 characters.
  *Example: `"example_string"`*

- **description** (string) *optional* *Length(max: 1000, maxMessage: 'Description cannot exceed 1000 characters')*
  Optional description providing additional context about the design.
  
  Used to document the design's purpose, goals, or any other
  relevant information. Maximum 1000 characters to keep descriptions
  concise but informative.
  *Example: `"example_string"`*

- **data** (App\DTO\ValueObject\DesignData) *optional* *Valid*
  Design-level configuration and settings.
  
  Contains global design settings including canvas background,
  animation configuration, grid settings, view preferences,
  and custom styling options that apply to the entire design.
  
  Defaults to empty configuration if not provided.

  **Union Type Details:**

  **DesignData**
  
  Value object representing design-level configuration and settings.
  
  This object contains global design settings that affect the entire
  canvas and design behavior, separate from individual layer properties.
  
  Properties:
  
    - **backgroundColor** (string) *optional* *Regex(pattern: '/^(#[0-9a-fA-F]{3,8}|rgba?\([^)]+\)|[a-zA-Z]+|transparent)$/', message: 'Background color must be a valid color format')*
      Background color of the design canvas.
      
      Can be a hex color (#ffffff), RGB/RGBA, or named color.
      Defaults to transparent if not specified.
      *Example: `"example_string"`*
  
    - **animationSettings** (array) *optional* *Type('array', message: 'Animation settings must be an array')*
      Animation settings for the design.
      
      Contains configuration for design-level animations including:
      - duration: Total animation duration in seconds
      - loop: Whether animations should loop
      - autoplay: Whether to start animations automatically
      - easing: Global easing function for animations
      *Example: `[]`*
  
    - **gridSettings** (array) *optional* *Type('array', message: 'Grid settings must be an array')*
      Grid and snap settings for the design canvas.
      
      Contains configuration for design assistance tools:
      - gridSize: Size of grid squares in pixels
      - snapToGrid: Whether elements snap to grid
      - showGrid: Whether grid is visible
      - snapToObjects: Whether elements snap to other objects
      - snapTolerance: Distance in pixels for snap activation
      *Example: `[]`*
  
    - **viewSettings** (array) *optional* *Type('array', message: 'View settings must be an array')*
      View and zoom settings for the design canvas.
      
      Contains configuration for canvas view state:
      - zoom: Current zoom level (1.0 = 100%)
      - panX: Horizontal pan offset in pixels
      - panY: Vertical pan offset in pixels
      - viewMode: Current view mode (fit, fill, actual, etc.)
      *Example: `[]`*
  
    - **globalStyles** (array) *optional* *Type('array', message: 'Global styles must be an array')*
      Global styles and themes applied to the design.
      
      Contains design-level styling that can be applied to layers:
      - colorPalette: Array of color swatches for the design
      - fontPairs: Recommended font combinations
      - theme: Overall design theme or style guide
      *Example: `[]`*
  
    - **customProperties** (array) *optional* *Type('array', message: 'Custom properties must be an array')*
      Custom metadata and extended properties.
      
      Allows for custom properties and future extensibility
      without breaking the schema. Should contain key-value
      pairs of additional design-level configuration.
      *Example: `[]`*
  
  
  *Example: `null`*

- **projectId** (int) *optional* *Type('integer', message: 'Project ID must be an integer'), Positive(message: 'Project ID must be positive')*
  Optional project ID to associate the design with.
  
  If provided, the design will be created within the specified
  project for organization and collaboration purposes. If null,
  the design will be created as a standalone item.
  *Example: `42`*

- **width** (int) *optional* *Type('integer', message: 'Width must be an integer'), Positive(message: 'Width must be positive'), Range(min: 1, max: 10000, notInRangeMessage: 'Width must be between 1 and 10000 pixels')*
  Canvas width in pixels.
  
  Defines the horizontal dimension of the design canvas.
  Must be between 1-10000 pixels for reasonable performance
  and export capabilities. Defaults to 1920px (Full HD width).
  *Example: `2`*

- **height** (int) *optional* *Type('integer', message: 'Height must be an integer'), Positive(message: 'Height must be positive'), Range(min: 1, max: 10000, notInRangeMessage: 'Height must be between 1 and 10000 pixels')*
  Canvas height in pixels.
  
  Defines the vertical dimension of the design canvas.
  Must be between 1-10000 pixels for reasonable performance
  and export capabilities. Defaults to 1080px (Full HD height).
  *Example: `2`*

- **isPublic** (bool) *optional* *Type('bool', message: 'Is public must be a boolean')*
  Whether the design should be publicly accessible.
  
  Public designs can be viewed by other users and may appear
  in community galleries or search results. Private designs
  are only accessible to the owner and collaborators.
  *Example: `true`*


#### Response

**JsonResponse**

Response represents an HTTP response in JSON format.

Note that this class does not force the returned JSON content to be an
object. It is however recommended that you do return an object as it
protects yourself against XSSI and JSON-JavaScript Hijacking.

Properties:

- **data** (mixed)
  *Example: `null`*

- **callback** (string) *optional*
  *Example: `"example_string"`*

- **encodingOptions** (int) *optional*
  *Example: `42`*

- **headers** (Symfony\Component\HttpFoundation\ResponseHeaderBag)
  *Example: `null`*

- **content** (string)
  *Example: `"example_string"`*

- **version** (string)
  *Example: `"example_string"`*

- **statusCode** (int)
  *Example: `42`*

- **statusText** (string)
  *Example: `"example_string"`*

- **charset** (string) *optional*
  *Example: `"example_string"`*

- **statusTexts** (array) *optional*
  Status codes translation table.
  
  The list of codes is complete according to the
  {@link https://www.iana.org/assignments/http-status-codes/http-status-codes.xhtml Hypertext Transfer Protocol (HTTP) Status Code Registry}
  (last updated 2021-10-01).
  
  Unless otherwise noted, the status code is defined in RFC2616.
  *Example: `[]`*


---

### GET /search

Search designs

Performs a comprehensive search across designs accessible to the authenticated user.
Searches in design names, descriptions, and associated project information.

#### Parameters

- **request** (Symfony\Component\HttpFoundation\Request)

#### Response

**JsonResponse**

Response represents an HTTP response in JSON format.

Note that this class does not force the returned JSON content to be an
object. It is however recommended that you do return an object as it
protects yourself against XSSI and JSON-JavaScript Hijacking.

Properties:

- **data** (mixed)
  *Example: `null`*

- **callback** (string) *optional*
  *Example: `"example_string"`*

- **encodingOptions** (int) *optional*
  *Example: `42`*

- **headers** (Symfony\Component\HttpFoundation\ResponseHeaderBag)
  *Example: `null`*

- **content** (string)
  *Example: `"example_string"`*

- **version** (string)
  *Example: `"example_string"`*

- **statusCode** (int)
  *Example: `42`*

- **statusText** (string)
  *Example: `"example_string"`*

- **charset** (string) *optional*
  *Example: `"example_string"`*

- **statusTexts** (array) *optional*
  Status codes translation table.
  
  The list of codes is complete according to the
  {@link https://www.iana.org/assignments/http-status-codes/http-status-codes.xhtml Hypertext Transfer Protocol (HTTP) Status Code Registry}
  (last updated 2021-10-01).
  
  Unless otherwise noted, the status code is defined in RFC2616.
  *Example: `[]`*


---

### GET /{id}

Get details of a specific design

Returns detailed information about a single design including canvas data and layers.
Only allows access to designs owned by the authenticated user or public designs.

#### Parameters

- **id** (int)

#### Response

**JsonResponse**

Response represents an HTTP response in JSON format.

Note that this class does not force the returned JSON content to be an
object. It is however recommended that you do return an object as it
protects yourself against XSSI and JSON-JavaScript Hijacking.

Properties:

- **data** (mixed)
  *Example: `null`*

- **callback** (string) *optional*
  *Example: `"example_string"`*

- **encodingOptions** (int) *optional*
  *Example: `42`*

- **headers** (Symfony\Component\HttpFoundation\ResponseHeaderBag)
  *Example: `null`*

- **content** (string)
  *Example: `"example_string"`*

- **version** (string)
  *Example: `"example_string"`*

- **statusCode** (int)
  *Example: `42`*

- **statusText** (string)
  *Example: `"example_string"`*

- **charset** (string) *optional*
  *Example: `"example_string"`*

- **statusTexts** (array) *optional*
  Status codes translation table.
  
  The list of codes is complete according to the
  {@link https://www.iana.org/assignments/http-status-codes/http-status-codes.xhtml Hypertext Transfer Protocol (HTTP) Status Code Registry}
  (last updated 2021-10-01).
  
  Unless otherwise noted, the status code is defined in RFC2616.
  *Example: `[]`*


---

### PUT /{id}

Update an existing design

Updates design information and canvas data with the provided information.
Only allows updates to designs owned by the authenticated user.
Supports partial updates and handles canvas data versioning.

#### Parameters

- **id** (int)

#### Request Body

**UpdateDesignRequestDTO**

Request DTO for updating an existing design.

This DTO handles partial updates to designs, allowing clients to
update only the fields they want to change. All fields are optional
and null values indicate no change should be made.

Properties:

- **name** (string) *optional* *Length(min: 1, max: 255, minMessage: 'Design name must be at least 1 character', maxMessage: 'Design name cannot exceed 255 characters')*
  Updated display name for the design.
  
  If provided, replaces the current design name. Must be
  between 1-255 characters. Null indicates no change.
  *Example: `"example_string"`*

- **description** (string) *optional* *Length(max: 1000, maxMessage: 'Description cannot exceed 1000 characters')*
  Updated description for the design.
  
  If provided, replaces the current description. Maximum
  1000 characters. Null indicates no change.
  *Example: `"example_string"`*

- **data** (App\DTO\ValueObject\DesignData) *optional* *Valid*
  Updated design-level configuration and settings.
  
  If provided, merges with or replaces current design settings
  including canvas background, animations, grid settings, etc.
  Null indicates no change to design data.

  **Union Type Details:**

  **DesignData**
  
  Value object representing design-level configuration and settings.
  
  This object contains global design settings that affect the entire
  canvas and design behavior, separate from individual layer properties.
  
  Properties:
  
    - **backgroundColor** (string) *optional* *Regex(pattern: '/^(#[0-9a-fA-F]{3,8}|rgba?\([^)]+\)|[a-zA-Z]+|transparent)$/', message: 'Background color must be a valid color format')*
      Background color of the design canvas.
      
      Can be a hex color (#ffffff), RGB/RGBA, or named color.
      Defaults to transparent if not specified.
      *Example: `"example_string"`*
  
    - **animationSettings** (array) *optional* *Type('array', message: 'Animation settings must be an array')*
      Animation settings for the design.
      
      Contains configuration for design-level animations including:
      - duration: Total animation duration in seconds
      - loop: Whether animations should loop
      - autoplay: Whether to start animations automatically
      - easing: Global easing function for animations
      *Example: `[]`*
  
    - **gridSettings** (array) *optional* *Type('array', message: 'Grid settings must be an array')*
      Grid and snap settings for the design canvas.
      
      Contains configuration for design assistance tools:
      - gridSize: Size of grid squares in pixels
      - snapToGrid: Whether elements snap to grid
      - showGrid: Whether grid is visible
      - snapToObjects: Whether elements snap to other objects
      - snapTolerance: Distance in pixels for snap activation
      *Example: `[]`*
  
    - **viewSettings** (array) *optional* *Type('array', message: 'View settings must be an array')*
      View and zoom settings for the design canvas.
      
      Contains configuration for canvas view state:
      - zoom: Current zoom level (1.0 = 100%)
      - panX: Horizontal pan offset in pixels
      - panY: Vertical pan offset in pixels
      - viewMode: Current view mode (fit, fill, actual, etc.)
      *Example: `[]`*
  
    - **globalStyles** (array) *optional* *Type('array', message: 'Global styles must be an array')*
      Global styles and themes applied to the design.
      
      Contains design-level styling that can be applied to layers:
      - colorPalette: Array of color swatches for the design
      - fontPairs: Recommended font combinations
      - theme: Overall design theme or style guide
      *Example: `[]`*
  
    - **customProperties** (array) *optional* *Type('array', message: 'Custom properties must be an array')*
      Custom metadata and extended properties.
      
      Allows for custom properties and future extensibility
      without breaking the schema. Should contain key-value
      pairs of additional design-level configuration.
      *Example: `[]`*
  
  
  *Example: `null`*

- **projectId** (int) *optional* *Type('integer', message: 'Project ID must be an integer'), Positive(message: 'Project ID must be positive')*
  Updated project association for the design.
  
  If provided, moves the design to the specified project.
  Can be used to move designs between projects or remove
  from project (if supported). Null indicates no change.
  *Example: `42`*

- **width** (int) *optional* *Type('integer', message: 'Width must be an integer'), Positive(message: 'Width must be positive'), Range(min: 1, max: 10000, notInRangeMessage: 'Width must be between 1 and 10000 pixels')*
  Updated canvas width in pixels.
  
  If provided, resizes the canvas width. Must be between
  1-10000 pixels. This may affect layer positioning.
  Null indicates no change.
  *Example: `2`*

- **height** (int) *optional* *Type('integer', message: 'Height must be an integer'), Positive(message: 'Height must be positive'), Range(min: 1, max: 10000, notInRangeMessage: 'Height must be between 1 and 10000 pixels')*
  Updated canvas height in pixels.
  
  If provided, resizes the canvas height. Must be between
  1-10000 pixels. This may affect layer positioning.
  Null indicates no change.
  *Example: `2`*

- **isPublic** (bool) *optional* *Type('bool', message: 'Is public must be a boolean')*
  Updated public visibility for the design.
  
  If provided, changes whether the design is publicly accessible
  and can appear in community galleries. Null indicates no change.
  *Example: `true`*


#### Response

**JsonResponse**

Response represents an HTTP response in JSON format.

Note that this class does not force the returned JSON content to be an
object. It is however recommended that you do return an object as it
protects yourself against XSSI and JSON-JavaScript Hijacking.

Properties:

- **data** (mixed)
  *Example: `null`*

- **callback** (string) *optional*
  *Example: `"example_string"`*

- **encodingOptions** (int) *optional*
  *Example: `42`*

- **headers** (Symfony\Component\HttpFoundation\ResponseHeaderBag)
  *Example: `null`*

- **content** (string)
  *Example: `"example_string"`*

- **version** (string)
  *Example: `"example_string"`*

- **statusCode** (int)
  *Example: `42`*

- **statusText** (string)
  *Example: `"example_string"`*

- **charset** (string) *optional*
  *Example: `"example_string"`*

- **statusTexts** (array) *optional*
  Status codes translation table.
  
  The list of codes is complete according to the
  {@link https://www.iana.org/assignments/http-status-codes/http-status-codes.xhtml Hypertext Transfer Protocol (HTTP) Status Code Registry}
  (last updated 2021-10-01).
  
  Unless otherwise noted, the status code is defined in RFC2616.
  *Example: `[]`*


---

### DELETE /{id}

Delete a design

Permanently deletes a design and all its associated data (layers, media files, export jobs).
Only allows deletion of designs owned by the authenticated user.
This action cannot be undone.

#### Parameters

- **id** (int)

#### Response

**JsonResponse**

Response represents an HTTP response in JSON format.

Note that this class does not force the returned JSON content to be an
object. It is however recommended that you do return an object as it
protects yourself against XSSI and JSON-JavaScript Hijacking.

Properties:

- **data** (mixed)
  *Example: `null`*

- **callback** (string) *optional*
  *Example: `"example_string"`*

- **encodingOptions** (int) *optional*
  *Example: `42`*

- **headers** (Symfony\Component\HttpFoundation\ResponseHeaderBag)
  *Example: `null`*

- **content** (string)
  *Example: `"example_string"`*

- **version** (string)
  *Example: `"example_string"`*

- **statusCode** (int)
  *Example: `42`*

- **statusText** (string)
  *Example: `"example_string"`*

- **charset** (string) *optional*
  *Example: `"example_string"`*

- **statusTexts** (array) *optional*
  Status codes translation table.
  
  The list of codes is complete according to the
  {@link https://www.iana.org/assignments/http-status-codes/http-status-codes.xhtml Hypertext Transfer Protocol (HTTP) Status Code Registry}
  (last updated 2021-10-01).
  
  Unless otherwise noted, the status code is defined in RFC2616.
  *Example: `[]`*


---

### POST /{id}/duplicate

Duplicate an existing design

Creates a copy of an existing design with all its layers and settings.
Only allows duplication of designs owned by the authenticated user or public designs.
The duplicated design is always private and owned by the authenticated user.

#### Parameters

- **id** (int)

#### Request Body

**DuplicateDesignRequestDTO**

Data Transfer Object for design duplication requests.

Handles the duplication of complete designs including all layers,
settings, and metadata. Used by the design management system to
create copies of existing designs with optional customization
of the duplicate's name and target project.

Properties:

- **name** (string) *optional* *Length(min: 1, max: 255, minMessage: 'Design name must be at least 1 character', maxMessage: 'Design name cannot exceed 255 characters')*
  Custom name for the duplicated design.
  
  If provided, this will be used as the name for the new design.
  If null, the system will automatically generate a name like
  "Copy of {original name}". Must be between 1-255 characters
  if provided.
  *Example: `"example_string"`*

- **projectId** (int) *optional* *Type('integer', message: 'Project ID must be an integer'), Positive(message: 'Project ID must be positive')*
  Target project ID for the duplicated design.
  
  If provided, the duplicated design will be placed in the
  specified project. The user must have write access to the
  target project. If null, the design is duplicated to the
  same project as the original or as a standalone design.
  *Example: `42`*


#### Response

**JsonResponse**

Response represents an HTTP response in JSON format.

Note that this class does not force the returned JSON content to be an
object. It is however recommended that you do return an object as it
protects yourself against XSSI and JSON-JavaScript Hijacking.

Properties:

- **data** (mixed)
  *Example: `null`*

- **callback** (string) *optional*
  *Example: `"example_string"`*

- **encodingOptions** (int) *optional*
  *Example: `42`*

- **headers** (Symfony\Component\HttpFoundation\ResponseHeaderBag)
  *Example: `null`*

- **content** (string)
  *Example: `"example_string"`*

- **version** (string)
  *Example: `"example_string"`*

- **statusCode** (int)
  *Example: `42`*

- **statusText** (string)
  *Example: `"example_string"`*

- **charset** (string) *optional*
  *Example: `"example_string"`*

- **statusTexts** (array) *optional*
  Status codes translation table.
  
  The list of codes is complete according to the
  {@link https://www.iana.org/assignments/http-status-codes/http-status-codes.xhtml Hypertext Transfer Protocol (HTTP) Status Code Registry}
  (last updated 2021-10-01).
  
  Unless otherwise noted, the status code is defined in RFC2616.
  *Example: `[]`*


---

### PUT /{id}/thumbnail

Update design thumbnail

Updates the thumbnail image for a design.
Only allows updates to designs owned by the authenticated user.
Validates thumbnail format and size requirements.

#### Parameters

- **id** (int)

#### Request Body

**UpdateDesignThumbnailRequestDTO**

Data Transfer Object for design thumbnail update requests.

Handles updating the thumbnail image for an existing design.
Used when users want to change the preview image that represents
their design in galleries, project lists, and search results.

Properties:

- **thumbnail** (string) *NotBlank(message: 'Thumbnail URL is required'), Url(message: 'Thumbnail must be a valid URL')*
  URL of the new thumbnail image.
  
  Must be a valid URL pointing to an image file that will
  serve as the design's preview thumbnail. The image should
  be optimized for display in lists and galleries, typically
  in common web formats (PNG, JPEG, WebP).
  *Example: `"example_string"`*


#### Response

**JsonResponse**

Response represents an HTTP response in JSON format.

Note that this class does not force the returned JSON content to be an
object. It is however recommended that you do return an object as it
protects yourself against XSSI and JSON-JavaScript Hijacking.

Properties:

- **data** (mixed)
  *Example: `null`*

- **callback** (string) *optional*
  *Example: `"example_string"`*

- **encodingOptions** (int) *optional*
  *Example: `42`*

- **headers** (Symfony\Component\HttpFoundation\ResponseHeaderBag)
  *Example: `null`*

- **content** (string)
  *Example: `"example_string"`*

- **version** (string)
  *Example: `"example_string"`*

- **statusCode** (int)
  *Example: `42`*

- **statusText** (string)
  *Example: `"example_string"`*

- **charset** (string) *optional*
  *Example: `"example_string"`*

- **statusTexts** (array) *optional*
  Status codes translation table.
  
  The list of codes is complete according to the
  {@link https://www.iana.org/assignments/http-status-codes/http-status-codes.xhtml Hypertext Transfer Protocol (HTTP) Status Code Registry}
  (last updated 2021-10-01).
  
  Unless otherwise noted, the status code is defined in RFC2616.
  *Example: `[]`*


---

## AuthController

Authentication Controller

Handles user authentication, registration, profile management, and password operations.
All endpoints return JSON responses with consistent error handling.

### PUT /change-password

**Security:** IsGranted

Change user password

Updates the authenticated user's password after validating the current password.
Enforces password strength requirements.

#### Request Body

**ChangePasswordRequestDTO**

Data Transfer Object for password change requests



Properties:

- **currentPassword** (string)
  *Example: `"example_string"`*

- **newPassword** (string)
  *Example: `"example_string"`*

- **confirmPassword** (string)
  *Example: `"example_string"`*


#### Response

**JsonResponse**

Response represents an HTTP response in JSON format.

Note that this class does not force the returned JSON content to be an
object. It is however recommended that you do return an object as it
protects yourself against XSSI and JSON-JavaScript Hijacking.

Properties:

- **data** (mixed)
  *Example: `null`*

- **callback** (string) *optional*
  *Example: `"example_string"`*

- **encodingOptions** (int) *optional*
  *Example: `42`*

- **headers** (Symfony\Component\HttpFoundation\ResponseHeaderBag)
  *Example: `null`*

- **content** (string)
  *Example: `"example_string"`*

- **version** (string)
  *Example: `"example_string"`*

- **statusCode** (int)
  *Example: `42`*

- **statusText** (string)
  *Example: `"example_string"`*

- **charset** (string) *optional*
  *Example: `"example_string"`*

- **statusTexts** (array) *optional*
  Status codes translation table.
  
  The list of codes is complete according to the
  {@link https://www.iana.org/assignments/http-status-codes/http-status-codes.xhtml Hypertext Transfer Protocol (HTTP) Status Code Registry}
  (last updated 2021-10-01).
  
  Unless otherwise noted, the status code is defined in RFC2616.
  *Example: `[]`*


---

### POST /login

Authenticate user and return JWT token

Validates user credentials and returns a JWT token with user information.
Updates the user's last login timestamp.

#### Request Body

**LoginRequestDTO**

Data Transfer Object for user authentication login requests.

Handles user login credentials for JWT token-based authentication.
Used by the authentication system to validate user credentials
and generate access tokens for API authorization.

Properties:

- **email** (string)
  User's email address for authentication.
  
  Must be a valid email format and correspond to a registered
  user account in the system. Used as the primary identifier
  for user authentication.
  *Example: `"example_string"`*

- **password** (string)
  User's password for authentication.
  
  Plain text password that will be verified against the
  hashed password stored in the database. Should be handled
  securely throughout the authentication process.
  *Example: `"example_string"`*


#### Response

**JsonResponse**

Response represents an HTTP response in JSON format.

Note that this class does not force the returned JSON content to be an
object. It is however recommended that you do return an object as it
protects yourself against XSSI and JSON-JavaScript Hijacking.

Properties:

- **data** (mixed)
  *Example: `null`*

- **callback** (string) *optional*
  *Example: `"example_string"`*

- **encodingOptions** (int) *optional*
  *Example: `42`*

- **headers** (Symfony\Component\HttpFoundation\ResponseHeaderBag)
  *Example: `null`*

- **content** (string)
  *Example: `"example_string"`*

- **version** (string)
  *Example: `"example_string"`*

- **statusCode** (int)
  *Example: `42`*

- **statusText** (string)
  *Example: `"example_string"`*

- **charset** (string) *optional*
  *Example: `"example_string"`*

- **statusTexts** (array) *optional*
  Status codes translation table.
  
  The list of codes is complete according to the
  {@link https://www.iana.org/assignments/http-status-codes/http-status-codes.xhtml Hypertext Transfer Protocol (HTTP) Status Code Registry}
  (last updated 2021-10-01).
  
  Unless otherwise noted, the status code is defined in RFC2616.
  *Example: `[]`*


---

### POST /logout

**Security:** IsGranted

Logout user

Since JWT tokens are stateless, logout is primarily handled client-side by removing the token.
This endpoint provides a standardized logout response and could be extended with token blacklisting.

#### Response

**JsonResponse**

Response represents an HTTP response in JSON format.

Note that this class does not force the returned JSON content to be an
object. It is however recommended that you do return an object as it
protects yourself against XSSI and JSON-JavaScript Hijacking.

Properties:

- **data** (mixed)
  *Example: `null`*

- **callback** (string) *optional*
  *Example: `"example_string"`*

- **encodingOptions** (int) *optional*
  *Example: `42`*

- **headers** (Symfony\Component\HttpFoundation\ResponseHeaderBag)
  *Example: `null`*

- **content** (string)
  *Example: `"example_string"`*

- **version** (string)
  *Example: `"example_string"`*

- **statusCode** (int)
  *Example: `42`*

- **statusText** (string)
  *Example: `"example_string"`*

- **charset** (string) *optional*
  *Example: `"example_string"`*

- **statusTexts** (array) *optional*
  Status codes translation table.
  
  The list of codes is complete according to the
  {@link https://www.iana.org/assignments/http-status-codes/http-status-codes.xhtml Hypertext Transfer Protocol (HTTP) Status Code Registry}
  (last updated 2021-10-01).
  
  Unless otherwise noted, the status code is defined in RFC2616.
  *Example: `[]`*


---

### GET /me

**Security:** IsGranted

Get current authenticated user profile

Returns detailed information about the currently authenticated user,
including profile data, statistics, and account status.

#### Response

**JsonResponse**

Response represents an HTTP response in JSON format.

Note that this class does not force the returned JSON content to be an
object. It is however recommended that you do return an object as it
protects yourself against XSSI and JSON-JavaScript Hijacking.

Properties:

- **data** (mixed)
  *Example: `null`*

- **callback** (string) *optional*
  *Example: `"example_string"`*

- **encodingOptions** (int) *optional*
  *Example: `42`*

- **headers** (Symfony\Component\HttpFoundation\ResponseHeaderBag)
  *Example: `null`*

- **content** (string)
  *Example: `"example_string"`*

- **version** (string)
  *Example: `"example_string"`*

- **statusCode** (int)
  *Example: `42`*

- **statusText** (string)
  *Example: `"example_string"`*

- **charset** (string) *optional*
  *Example: `"example_string"`*

- **statusTexts** (array) *optional*
  Status codes translation table.
  
  The list of codes is complete according to the
  {@link https://www.iana.org/assignments/http-status-codes/http-status-codes.xhtml Hypertext Transfer Protocol (HTTP) Status Code Registry}
  (last updated 2021-10-01).
  
  Unless otherwise noted, the status code is defined in RFC2616.
  *Example: `[]`*


---

### PUT /profile

**Security:** IsGranted

Update user profile information

Updates the authenticated user's profile data such as name, username, avatar, and settings.
Validates uniqueness of username if provided.

#### Request Body

**UpdateProfileRequestDTO**

Data Transfer Object for user profile update requests

Handles updating user profile information including personal details,
username, avatar, and application settings. All fields are optional
to support partial updates.

Properties:

- **firstName** (string) *optional* *Length(min: 1, max: 255, minMessage: 'First name cannot be empty', maxMessage: 'First name cannot be longer than {{ limit }} characters')*
  User's first name for display and identification purposes
  Must be between 1-255 characters if provided
  
  
  *Example: `"example_string"`*

- **lastName** (string) *optional* *Length(min: 1, max: 255, minMessage: 'Last name cannot be empty', maxMessage: 'Last name cannot be longer than {{ limit }} characters')*
  User's last name for display and identification purposes
  Must be between 1-255 characters if provided
  
  
  *Example: `"example_string"`*

- **username** (string) *optional* *Length(min: 3, max: 100, minMessage: 'Username must be at least {{ limit }} characters long', maxMessage: 'Username cannot be longer than {{ limit }} characters'), Regex(pattern: '/^[a-zA-Z0-9_]+$/', message: 'Username can only contain letters, numbers, and underscores')*
  Unique username for the user account
  Must be 3-100 characters, containing only letters, numbers, and underscores
  Used for public profile identification and @mentions
  
  
  *Example: `"example_string"`*

- **avatar** (string) *optional* *Url(message: 'Avatar must be a valid URL'), Length(max: 500, maxMessage: 'Avatar URL cannot exceed 500 characters')*
  URL to the user's profile avatar image
  Must be a valid URL pointing to an image file
  Used for profile display and team collaboration identification
  
  
  *Example: `"example_string"`*

- **settings** (App\DTO\ValueObject\UserSettings) *optional*
  User's application settings and preferences
  Controls theme, language, notifications, auto-save, and editor behavior
  Structured object containing all user preference configurations
  
  

  **Union Type Details:**

  **UserSettings**
  
  User application settings
  
  
  
  Properties:
  
    - **theme** (string) *optional* *Choice(choices: ['light', 'dark', 'auto'], message: 'Theme must be light, dark, or auto')*
      UI theme preference (light, dark, or auto)
      
      
      *Example: `light`*
  
    - **language** (string) *optional* *Choice(choices: ['en', 'es', 'fr', 'de', 'it', 'pt', 'ru', 'zh', 'ja'], message: 'Invalid language code')*
      User interface language (ISO 639-1 language code)
      
      
      *Example: `en`*
  
    - **timezone** (string) *optional* *Choice(choices: ['UTC', 'America/New_York', 'America/Los_Angeles', 'Europe/London', 'Europe/Paris', 'Asia/Tokyo'], message: 'Invalid timezone')*
      User's timezone for date/time display
      
      
      *Example: `UTC`*
  
    - **emailNotifications** (bool) *optional* *Type(type: 'boolean', message: 'Email notifications setting must be boolean')*
      Whether to send email notifications to the user
      
      
      *Example: `true`*
  
    - **pushNotifications** (bool) *optional* *Type(type: 'boolean', message: 'Push notifications setting must be boolean')*
      Whether to send push notifications to the user's devices
      
      
      *Example: `true`*
  
    - **autoSave** (bool) *optional* *Type(type: 'boolean', message: 'Auto save setting must be boolean')*
      Whether to automatically save designs while working
      
      
      *Example: `true`*
  
    - **autoSaveInterval** (int) *optional* *Type(type: 'integer', message: 'Auto save interval must be an integer'), Range(min: 30, max: 600, notInRangeMessage: 'Auto save interval must be between 30 and 600 seconds')*
      How often to auto-save in seconds (30-600)
      
      
      *Example: `31`*
  
    - **gridSnap** (bool) *optional* *Type(type: 'boolean', message: 'Grid snap setting must be boolean')*
      Whether objects automatically snap to grid when moving
      
      
      *Example: `true`*
  
    - **gridSize** (int) *optional* *Type(type: 'integer', message: 'Grid size must be an integer'), Range(min: 1, max: 100, notInRangeMessage: 'Grid size must be between 1 and 100 pixels')*
      Grid spacing in pixels for snap and alignment (1-100)
      
      
      *Example: `2`*
  
    - **canvasQuality** (int) *optional* *Type(type: 'integer', message: 'Canvas quality must be an integer'), Range(min: 1, max: 4, notInRangeMessage: 'Canvas quality must be between 1 and 4')*
      Canvas rendering quality level (1=low, 2=medium, 3=high, 4=ultra)
      
      
      *Example: `2`*
  
  
  *Example: `null`*


#### Response

**JsonResponse**

Response represents an HTTP response in JSON format.

Note that this class does not force the returned JSON content to be an
object. It is however recommended that you do return an object as it
protects yourself against XSSI and JSON-JavaScript Hijacking.

Properties:

- **data** (mixed)
  *Example: `null`*

- **callback** (string) *optional*
  *Example: `"example_string"`*

- **encodingOptions** (int) *optional*
  *Example: `42`*

- **headers** (Symfony\Component\HttpFoundation\ResponseHeaderBag)
  *Example: `null`*

- **content** (string)
  *Example: `"example_string"`*

- **version** (string)
  *Example: `"example_string"`*

- **statusCode** (int)
  *Example: `42`*

- **statusText** (string)
  *Example: `"example_string"`*

- **charset** (string) *optional*
  *Example: `"example_string"`*

- **statusTexts** (array) *optional*
  Status codes translation table.
  
  The list of codes is complete according to the
  {@link https://www.iana.org/assignments/http-status-codes/http-status-codes.xhtml Hypertext Transfer Protocol (HTTP) Status Code Registry}
  (last updated 2021-10-01).
  
  Unless otherwise noted, the status code is defined in RFC2616.
  *Example: `[]`*


---

### POST /register

Register a new user account

Creates a new user with the provided information and returns a JWT token for immediate authentication.

#### Request Body

**RegisterRequestDTO**

Data Transfer Object for user registration requests.

Handles new user account creation with validation for all
required fields. Used by the registration system to collect
and validate user information before creating new accounts
in the platform.

Properties:

- **email** (string)
  User's email address for account registration.
  
  Must be a valid email format and unique in the system.
  Used as the primary login identifier and for sending
  account verification and notification emails.
  *Example: `"example_string"`*

- **password** (string)
  User's chosen password for account security.
  
  Must meet security requirements: minimum 8 characters,
  containing at least one lowercase letter, one uppercase
  letter, and one number. Will be hashed before storage.
  *Example: `"example_string"`*

- **firstName** (string)
  User's first (given) name.
  
  Required for account identification and personalization.
  Used in user interface greetings and profile displays.
  Must be 1-255 characters long.
  *Example: `"example_string"`*

- **lastName** (string)
  User's last (family) name.
  
  Required for account identification and personalization.
  Used in user interface displays and profile information.
  Must be 1-255 characters long.
  *Example: `"example_string"`*

- **username** (string)
  Optional unique username for the account.
  
  If provided, must be 3-100 characters and contain only
  letters, numbers, and underscores. Can be used as an
  alternative identifier for the user. If not provided,
  email will be the primary identifier.
  *Example: `"example_string"`*


#### Response

**JsonResponse**

Response represents an HTTP response in JSON format.

Note that this class does not force the returned JSON content to be an
object. It is however recommended that you do return an object as it
protects yourself against XSSI and JSON-JavaScript Hijacking.

Properties:

- **data** (mixed)
  *Example: `null`*

- **callback** (string) *optional*
  *Example: `"example_string"`*

- **encodingOptions** (int) *optional*
  *Example: `42`*

- **headers** (Symfony\Component\HttpFoundation\ResponseHeaderBag)
  *Example: `null`*

- **content** (string)
  *Example: `"example_string"`*

- **version** (string)
  *Example: `"example_string"`*

- **statusCode** (int)
  *Example: `42`*

- **statusText** (string)
  *Example: `"example_string"`*

- **charset** (string) *optional*
  *Example: `"example_string"`*

- **statusTexts** (array) *optional*
  Status codes translation table.
  
  The list of codes is complete according to the
  {@link https://www.iana.org/assignments/http-status-codes/http-status-codes.xhtml Hypertext Transfer Protocol (HTTP) Status Code Registry}
  (last updated 2021-10-01).
  
  Unless otherwise noted, the status code is defined in RFC2616.
  *Example: `[]`*


---

## ProjectController

Project Controller

Manages project operations including creation, retrieval, updating, and deletion.
Handles project sharing, duplication, and public project discovery.
All endpoints require authentication and enforce user ownership for security.

### GET 

List projects for authenticated user

Returns a paginated list of projects belonging to the authenticated user.
Supports filtering by status, sorting by various fields, and search functionality.

#### Parameters

- **request** (Symfony\Component\HttpFoundation\Request)

#### Response

**JsonResponse**

Response represents an HTTP response in JSON format.

Note that this class does not force the returned JSON content to be an
object. It is however recommended that you do return an object as it
protects yourself against XSSI and JSON-JavaScript Hijacking.

Properties:

- **data** (mixed)
  *Example: `null`*

- **callback** (string) *optional*
  *Example: `"example_string"`*

- **encodingOptions** (int) *optional*
  *Example: `42`*

- **headers** (Symfony\Component\HttpFoundation\ResponseHeaderBag)
  *Example: `null`*

- **content** (string)
  *Example: `"example_string"`*

- **version** (string)
  *Example: `"example_string"`*

- **statusCode** (int)
  *Example: `42`*

- **statusText** (string)
  *Example: `"example_string"`*

- **charset** (string) *optional*
  *Example: `"example_string"`*

- **statusTexts** (array) *optional*
  Status codes translation table.
  
  The list of codes is complete according to the
  {@link https://www.iana.org/assignments/http-status-codes/http-status-codes.xhtml Hypertext Transfer Protocol (HTTP) Status Code Registry}
  (last updated 2021-10-01).
  
  Unless otherwise noted, the status code is defined in RFC2616.
  *Example: `[]`*


---

### POST 

Create a new project

Creates a new project with the provided information and associates it with the authenticated user.
Validates project data and sets default values for optional fields.

#### Request Body

**CreateProjectRequestDTO**

Request DTO for creating a new design project.

This DTO handles the creation of new projects with all necessary
configuration options, including canvas settings, metadata, and
organizational tags.

Properties:

- **name** (string) *NotBlank(message: 'Project name is required'), Length(min: 1, max: 255, minMessage: 'Project name must be at least 1 character', maxMessage: 'Project name cannot exceed 255 characters')*
  The display name of the project.
  
  This name is used throughout the application interface
  and should be descriptive and meaningful to the user.
  Must be between 1-255 characters.
  *Example: `"example_string"`*

- **description** (string) *Length(max: 1000, maxMessage: 'Description cannot exceed 1000 characters')*
  Optional description providing additional context about the project.
  
  Used to document the project's purpose, goals, or any other
  relevant information. Maximum 1000 characters to keep descriptions
  concise but informative.
  *Example: `"example_string"`*

- **isPublic** (bool) *Type('bool', message: 'Is public must be a boolean')*
  Whether the project should be publicly accessible.
  
  Public projects can be viewed by other users and may appear
  in community galleries or search results. Private projects
  are only accessible to the owner and collaborators.
  *Example: `true`*

- **settings** (App\DTO\ValueObject\ProjectSettings) *Valid*
  Project configuration settings including canvas dimensions, DPI, etc.
  
  Contains all technical settings that define how the project
  behaves and renders, including:
  - Canvas size and background
  - Export settings (DPI, quality)
  - Snap and grid configurations
  - Auto-save preferences

  **Union Type Details:**

  **ProjectSettings**
  
  Project settings that control project behavior and defaults
  
  
  
  Properties:
  
    - **canvasWidth** (int) *optional* *Type(type: 'integer', message: 'Canvas width must be an integer'), Range(min: 1, max: 10000, notInRangeMessage: 'Canvas width must be between 1 and 10000 pixels')*
      Width of the canvas in pixels (1-10000)
      
      
      *Example: `2`*
  
    - **canvasHeight** (int) *optional* *Type(type: 'integer', message: 'Canvas height must be an integer'), Range(min: 1, max: 10000, notInRangeMessage: 'Canvas height must be between 1 and 10000 pixels')*
      Height of the canvas in pixels (1-10000)
      
      
      *Example: `2`*
  
    - **backgroundColor** (string) *optional* *Regex(pattern: '/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/', message: 'Background color must be a valid hex color code')*
      Background color of the canvas as hex value (e.g., #ffffff, #000)
      
      
      *Example: `"example_string"`*
  
    - **orientation** (string) *optional* *Choice(choices: ['portrait', 'landscape'], message: 'Orientation must be portrait or landscape')*
      Canvas orientation mode (portrait or landscape)
      
      
      *Example: `portrait`*
  
    - **units** (string) *optional* *Choice(choices: ['px', 'cm', 'mm', 'in', 'pt'], message: 'Invalid unit type')*
      Measurement units for the canvas (px, cm, mm, in, pt)
      
      
      *Example: `px`*
  
    - **dpi** (int) *optional* *Type(type: 'integer', message: 'DPI must be an integer'), Range(min: 72, max: 600, notInRangeMessage: 'DPI must be between 72 and 600')*
      Dots per inch resolution for print output (72-600)
      
      
      *Example: `73`*
  
    - **gridVisible** (bool) *optional* *Type(type: 'boolean', message: 'Grid visible setting must be boolean')*
      Whether the grid overlay is visible on the canvas
      
      
      *Example: `true`*
  
    - **rulersVisible** (bool) *optional* *Type(type: 'boolean', message: 'Rulers visible setting must be boolean')*
      Whether rulers are visible around the canvas edges
      
      
      *Example: `true`*
  
    - **guidesVisible** (bool) *optional* *Type(type: 'boolean', message: 'Guides visible setting must be boolean')*
      Whether guide lines are visible on the canvas
      
      
      *Example: `true`*
  
    - **snapToGrid** (bool) *optional* *Type(type: 'boolean', message: 'Snap to grid setting must be boolean')*
      Whether objects automatically snap to grid lines
      
      
      *Example: `true`*
  
    - **snapToGuides** (bool) *optional* *Type(type: 'boolean', message: 'Snap to guides setting must be boolean')*
      Whether objects automatically snap to guide lines
      
      
      *Example: `true`*
  
    - **snapToObjects** (bool) *optional* *Type(type: 'boolean', message: 'Snap to objects setting must be boolean')*
      Whether objects automatically snap to other objects
      
      
      *Example: `true`*
  
  
  *Example: `null`*

- **tags** (array) *optional* *Valid*
  Organizational tags for categorizing and searching projects.
  
  Tags help users organize their projects and make them discoverable
  through search and filtering. Each tag must be 1-50 characters
  and contain only alphanumeric characters, spaces, hyphens, and underscores.
  *Example: `[]`*

- **thumbnail** (string) *optional* *Url(message: 'Thumbnail must be a valid URL')*
  Optional URL to a thumbnail image representing the project.
  
  Used for project previews in lists and galleries. Should be
  a valid URL pointing to an image file. If not provided,
  a thumbnail will be generated from the project content.
  *Example: `"example_string"`*


#### Response

**JsonResponse**

Response represents an HTTP response in JSON format.

Note that this class does not force the returned JSON content to be an
object. It is however recommended that you do return an object as it
protects yourself against XSSI and JSON-JavaScript Hijacking.

Properties:

- **data** (mixed)
  *Example: `null`*

- **callback** (string) *optional*
  *Example: `"example_string"`*

- **encodingOptions** (int) *optional*
  *Example: `42`*

- **headers** (Symfony\Component\HttpFoundation\ResponseHeaderBag)
  *Example: `null`*

- **content** (string)
  *Example: `"example_string"`*

- **version** (string)
  *Example: `"example_string"`*

- **statusCode** (int)
  *Example: `42`*

- **statusText** (string)
  *Example: `"example_string"`*

- **charset** (string) *optional*
  *Example: `"example_string"`*

- **statusTexts** (array) *optional*
  Status codes translation table.
  
  The list of codes is complete according to the
  {@link https://www.iana.org/assignments/http-status-codes/http-status-codes.xhtml Hypertext Transfer Protocol (HTTP) Status Code Registry}
  (last updated 2021-10-01).
  
  Unless otherwise noted, the status code is defined in RFC2616.
  *Example: `[]`*


---

### GET /public

List public projects

Returns a paginated list of publicly shared projects from all users.
Supports search, filtering, and sorting functionality for project discovery.

#### Request Body

**SearchProjectsRequestDTO**

Data Transfer Object for project search requests.

Handles search and filtering operations for user projects
with support for text-based search, tag filtering, and
pagination. Used by the project management system to allow
users to find and organize their design projects efficiently.

Properties:

- **page** (int) *optional* *Range(min: 1, notInRangeMessage: 'Page must be {{ min }} or greater')*
  Page number for pagination.
  
  Specifies which page of project results to return.
  Must be 1 or greater for valid pagination navigation.
  *Example: `2`*

- **limit** (int) *optional* *Range(min: 1, max: 50, notInRangeMessage: 'Limit must be between {{ min }} and {{ max }}')*
  Number of projects per page.
  
  Controls how many projects are returned per page.
  Limited to a maximum of 50 to maintain performance
  and reasonable response times.
  *Example: `2`*

- **search** (string) *optional* *Length(max: 255, maxMessage: 'Search query cannot be longer than {{ limit }} characters')*
  Search query for project names and descriptions.
  
  Text to search for in project names, descriptions,
  and other searchable fields. When null, no text-based
  filtering is applied to the results.
  *Example: `"example_string"`*

- **tags** (string) *optional*
  Comma-separated list of tags for filtering.
  
  Tags to filter projects by. Multiple tags can be specified
  separated by commas. The system will find projects that
  match any of the specified tags.
  *Example: `"example_string"`*


#### Response

**JsonResponse**

Response represents an HTTP response in JSON format.

Note that this class does not force the returned JSON content to be an
object. It is however recommended that you do return an object as it
protects yourself against XSSI and JSON-JavaScript Hijacking.

Properties:

- **data** (mixed)
  *Example: `null`*

- **callback** (string) *optional*
  *Example: `"example_string"`*

- **encodingOptions** (int) *optional*
  *Example: `42`*

- **headers** (Symfony\Component\HttpFoundation\ResponseHeaderBag)
  *Example: `null`*

- **content** (string)
  *Example: `"example_string"`*

- **version** (string)
  *Example: `"example_string"`*

- **statusCode** (int)
  *Example: `42`*

- **statusText** (string)
  *Example: `"example_string"`*

- **charset** (string) *optional*
  *Example: `"example_string"`*

- **statusTexts** (array) *optional*
  Status codes translation table.
  
  The list of codes is complete according to the
  {@link https://www.iana.org/assignments/http-status-codes/http-status-codes.xhtml Hypertext Transfer Protocol (HTTP) Status Code Registry}
  (last updated 2021-10-01).
  
  Unless otherwise noted, the status code is defined in RFC2616.
  *Example: `[]`*


---

### GET /{id}

Get details of a specific project

Returns detailed information about a single project.
Only allows access to projects owned by the authenticated user or public projects.

#### Parameters

- **id** (int)

#### Response

**JsonResponse**

Response represents an HTTP response in JSON format.

Note that this class does not force the returned JSON content to be an
object. It is however recommended that you do return an object as it
protects yourself against XSSI and JSON-JavaScript Hijacking.

Properties:

- **data** (mixed)
  *Example: `null`*

- **callback** (string) *optional*
  *Example: `"example_string"`*

- **encodingOptions** (int) *optional*
  *Example: `42`*

- **headers** (Symfony\Component\HttpFoundation\ResponseHeaderBag)
  *Example: `null`*

- **content** (string)
  *Example: `"example_string"`*

- **version** (string)
  *Example: `"example_string"`*

- **statusCode** (int)
  *Example: `42`*

- **statusText** (string)
  *Example: `"example_string"`*

- **charset** (string) *optional*
  *Example: `"example_string"`*

- **statusTexts** (array) *optional*
  Status codes translation table.
  
  The list of codes is complete according to the
  {@link https://www.iana.org/assignments/http-status-codes/http-status-codes.xhtml Hypertext Transfer Protocol (HTTP) Status Code Registry}
  (last updated 2021-10-01).
  
  Unless otherwise noted, the status code is defined in RFC2616.
  *Example: `[]`*


---

### PUT /{id}

Update an existing project

Updates project information with the provided data.
Only allows updates to projects owned by the authenticated user.
Validates updated data and handles partial updates.

#### Parameters

- **id** (int)

#### Request Body

**UpdateProjectRequestDTO**

Request DTO for updating an existing design project.

This DTO handles partial updates to projects, allowing clients to
update only the fields they want to change. All fields are optional
and null values indicate no change should be made.

Properties:

- **name** (string) *optional* *Length(min: 1, max: 255, minMessage: 'Project name must be at least 1 character', maxMessage: 'Project name cannot exceed 255 characters')*
  Updated display name for the project.
  
  If provided, replaces the current project name. Must be
  between 1-255 characters. Null indicates no change.
  *Example: `"example_string"`*

- **description** (string) *optional* *Length(max: 1000, maxMessage: 'Description cannot exceed 1000 characters')*
  Updated description for the project.
  
  If provided, replaces the current description. Maximum
  1000 characters. Null indicates no change.
  *Example: `"example_string"`*

- **isPublic** (bool) *optional* *Type('bool', message: 'Is public must be a boolean')*
  Updated visibility setting for the project.
  
  If provided, changes whether the project is publicly
  accessible. Null indicates no change.
  *Example: `true`*

- **settings** (App\DTO\ValueObject\ProjectSettings) *optional* *Valid*
  Updated project configuration settings.
  
  If provided, replaces or merges with current project settings
  including canvas dimensions, DPI, export preferences, etc.
  Null indicates no change to settings.

  **Union Type Details:**

  **ProjectSettings**
  
  Project settings that control project behavior and defaults
  
  
  
  Properties:
  
    - **canvasWidth** (int) *optional* *Type(type: 'integer', message: 'Canvas width must be an integer'), Range(min: 1, max: 10000, notInRangeMessage: 'Canvas width must be between 1 and 10000 pixels')*
      Width of the canvas in pixels (1-10000)
      
      
      *Example: `2`*
  
    - **canvasHeight** (int) *optional* *Type(type: 'integer', message: 'Canvas height must be an integer'), Range(min: 1, max: 10000, notInRangeMessage: 'Canvas height must be between 1 and 10000 pixels')*
      Height of the canvas in pixels (1-10000)
      
      
      *Example: `2`*
  
    - **backgroundColor** (string) *optional* *Regex(pattern: '/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/', message: 'Background color must be a valid hex color code')*
      Background color of the canvas as hex value (e.g., #ffffff, #000)
      
      
      *Example: `"example_string"`*
  
    - **orientation** (string) *optional* *Choice(choices: ['portrait', 'landscape'], message: 'Orientation must be portrait or landscape')*
      Canvas orientation mode (portrait or landscape)
      
      
      *Example: `portrait`*
  
    - **units** (string) *optional* *Choice(choices: ['px', 'cm', 'mm', 'in', 'pt'], message: 'Invalid unit type')*
      Measurement units for the canvas (px, cm, mm, in, pt)
      
      
      *Example: `px`*
  
    - **dpi** (int) *optional* *Type(type: 'integer', message: 'DPI must be an integer'), Range(min: 72, max: 600, notInRangeMessage: 'DPI must be between 72 and 600')*
      Dots per inch resolution for print output (72-600)
      
      
      *Example: `73`*
  
    - **gridVisible** (bool) *optional* *Type(type: 'boolean', message: 'Grid visible setting must be boolean')*
      Whether the grid overlay is visible on the canvas
      
      
      *Example: `true`*
  
    - **rulersVisible** (bool) *optional* *Type(type: 'boolean', message: 'Rulers visible setting must be boolean')*
      Whether rulers are visible around the canvas edges
      
      
      *Example: `true`*
  
    - **guidesVisible** (bool) *optional* *Type(type: 'boolean', message: 'Guides visible setting must be boolean')*
      Whether guide lines are visible on the canvas
      
      
      *Example: `true`*
  
    - **snapToGrid** (bool) *optional* *Type(type: 'boolean', message: 'Snap to grid setting must be boolean')*
      Whether objects automatically snap to grid lines
      
      
      *Example: `true`*
  
    - **snapToGuides** (bool) *optional* *Type(type: 'boolean', message: 'Snap to guides setting must be boolean')*
      Whether objects automatically snap to guide lines
      
      
      *Example: `true`*
  
    - **snapToObjects** (bool) *optional* *Type(type: 'boolean', message: 'Snap to objects setting must be boolean')*
      Whether objects automatically snap to other objects
      
      
      *Example: `true`*
  
  
  *Example: `null`*

- **tags** (array) *optional* *Valid*
  Updated organizational tags for the project.
  
  If provided, replaces the current tag set. Each tag must be
  1-50 characters and contain only alphanumeric characters,
  spaces, hyphens, and underscores. Null indicates no change.
  *Example: `[]`*

- **thumbnail** (string) *optional* *Url(message: 'Thumbnail must be a valid URL')*
  Updated thumbnail URL for the project.
  
  If provided, replaces the current thumbnail. Should be a
  valid URL pointing to an image file. Null indicates no change.
  *Example: `"example_string"`*


#### Response

**JsonResponse**

Response represents an HTTP response in JSON format.

Note that this class does not force the returned JSON content to be an
object. It is however recommended that you do return an object as it
protects yourself against XSSI and JSON-JavaScript Hijacking.

Properties:

- **data** (mixed)
  *Example: `null`*

- **callback** (string) *optional*
  *Example: `"example_string"`*

- **encodingOptions** (int) *optional*
  *Example: `42`*

- **headers** (Symfony\Component\HttpFoundation\ResponseHeaderBag)
  *Example: `null`*

- **content** (string)
  *Example: `"example_string"`*

- **version** (string)
  *Example: `"example_string"`*

- **statusCode** (int)
  *Example: `42`*

- **statusText** (string)
  *Example: `"example_string"`*

- **charset** (string) *optional*
  *Example: `"example_string"`*

- **statusTexts** (array) *optional*
  Status codes translation table.
  
  The list of codes is complete according to the
  {@link https://www.iana.org/assignments/http-status-codes/http-status-codes.xhtml Hypertext Transfer Protocol (HTTP) Status Code Registry}
  (last updated 2021-10-01).
  
  Unless otherwise noted, the status code is defined in RFC2616.
  *Example: `[]`*


---

### DELETE /{id}

Delete a project

Permanently deletes a project and all its associated data (designs, media files, etc.).
Only allows deletion of projects owned by the authenticated user.
This action cannot be undone.

#### Parameters

- **id** (int)

#### Response

**JsonResponse**

Response represents an HTTP response in JSON format.

Note that this class does not force the returned JSON content to be an
object. It is however recommended that you do return an object as it
protects yourself against XSSI and JSON-JavaScript Hijacking.

Properties:

- **data** (mixed)
  *Example: `null`*

- **callback** (string) *optional*
  *Example: `"example_string"`*

- **encodingOptions** (int) *optional*
  *Example: `42`*

- **headers** (Symfony\Component\HttpFoundation\ResponseHeaderBag)
  *Example: `null`*

- **content** (string)
  *Example: `"example_string"`*

- **version** (string)
  *Example: `"example_string"`*

- **statusCode** (int)
  *Example: `42`*

- **statusText** (string)
  *Example: `"example_string"`*

- **charset** (string) *optional*
  *Example: `"example_string"`*

- **statusTexts** (array) *optional*
  Status codes translation table.
  
  The list of codes is complete according to the
  {@link https://www.iana.org/assignments/http-status-codes/http-status-codes.xhtml Hypertext Transfer Protocol (HTTP) Status Code Registry}
  (last updated 2021-10-01).
  
  Unless otherwise noted, the status code is defined in RFC2616.
  *Example: `[]`*


---

### POST /{id}/duplicate

Duplicate an existing project

Creates a copy of an existing project with all its designs and settings.
Only allows duplication of projects owned by the authenticated user or public projects.
The duplicated project is always private and owned by the authenticated user.

#### Parameters

- **id** (int)

#### Request Body

**DuplicateProjectRequestDTO**

Data Transfer Object for project duplication requests.

Handles the creation of a complete copy of an existing project
including all associated designs, layers, and metadata. Used
in the project management system to allow users to quickly
create new projects based on existing templates.

Properties:

- **name** (string) *optional* *NotBlank(message: 'Project name is required'), Length(min: 1, max: 255, minMessage: 'Project name must be at least {{ limit }} characters long', maxMessage: 'Project name cannot be longer than {{ limit }} characters')*
  Display name for the duplicated project.
  
  The name given to the new project copy. Must be between
  1-255 characters and will be displayed in the user's
  project list. If null, a default name will be generated
  based on the original project name with a copy suffix.
  *Example: `"example_string"`*


#### Response

**JsonResponse**

Response represents an HTTP response in JSON format.

Note that this class does not force the returned JSON content to be an
object. It is however recommended that you do return an object as it
protects yourself against XSSI and JSON-JavaScript Hijacking.

Properties:

- **data** (mixed)
  *Example: `null`*

- **callback** (string) *optional*
  *Example: `"example_string"`*

- **encodingOptions** (int) *optional*
  *Example: `42`*

- **headers** (Symfony\Component\HttpFoundation\ResponseHeaderBag)
  *Example: `null`*

- **content** (string)
  *Example: `"example_string"`*

- **version** (string)
  *Example: `"example_string"`*

- **statusCode** (int)
  *Example: `42`*

- **statusText** (string)
  *Example: `"example_string"`*

- **charset** (string) *optional*
  *Example: `"example_string"`*

- **statusTexts** (array) *optional*
  Status codes translation table.
  
  The list of codes is complete according to the
  {@link https://www.iana.org/assignments/http-status-codes/http-status-codes.xhtml Hypertext Transfer Protocol (HTTP) Status Code Registry}
  (last updated 2021-10-01).
  
  Unless otherwise noted, the status code is defined in RFC2616.
  *Example: `[]`*


---

### POST /{id}/share

Toggle project sharing status

Toggles the public/private status of a project.
Only allows modification of projects owned by the authenticated user.
Updates the project's visibility and sharing settings.

#### Parameters

- **id** (int)

#### Response

**JsonResponse**

Response represents an HTTP response in JSON format.

Note that this class does not force the returned JSON content to be an
object. It is however recommended that you do return an object as it
protects yourself against XSSI and JSON-JavaScript Hijacking.

Properties:

- **data** (mixed)
  *Example: `null`*

- **callback** (string) *optional*
  *Example: `"example_string"`*

- **encodingOptions** (int) *optional*
  *Example: `42`*

- **headers** (Symfony\Component\HttpFoundation\ResponseHeaderBag)
  *Example: `null`*

- **content** (string)
  *Example: `"example_string"`*

- **version** (string)
  *Example: `"example_string"`*

- **statusCode** (int)
  *Example: `42`*

- **statusText** (string)
  *Example: `"example_string"`*

- **charset** (string) *optional*
  *Example: `"example_string"`*

- **statusTexts** (array) *optional*
  Status codes translation table.
  
  The list of codes is complete according to the
  {@link https://www.iana.org/assignments/http-status-codes/http-status-codes.xhtml Hypertext Transfer Protocol (HTTP) Status Code Registry}
  (last updated 2021-10-01).
  
  Unless otherwise noted, the status code is defined in RFC2616.
  *Example: `[]`*


---

## LayerController

Layer Controller

Manages design layer operations including creation, modification, deletion, and organization.
Handles layer positioning, duplication, and bulk operations for design elements.
All operations enforce design ownership validation and proper layer hierarchy management.
Layers are the core building blocks of designs in the canvas editor system.

### POST 

Create a new layer in a design

Creates a new layer with specified properties and adds it to the target design.
Automatically assigns appropriate z-index and validates design ownership.
Supports various layer types including text, shape, image, and group layers.

#### Request Body

**CreateLayerRequestDTO**

Data Transfer Object for layer creation requests

Handles the creation of new design layers with all necessary properties
for proper initialization in the canvas editor. Supports all layer types
including text, image, shape, group, video, and audio layers.

Properties:

- **designId** (string) *NotBlank(message: 'Design ID is required'), Type(type: 'string', message: 'Design ID must be a string')*
  Design ID where the new layer will be created
  Must be a valid UUID string identifying an existing design
  Used to ensure layer is added to the correct design context
  
  
  *Example: `"example_string"`*

- **type** (string) *NotBlank(message: 'Layer type is required'), Choice(choices: ['text', 'image', 'shape', 'group', 'video', 'audio'], message: 'Invalid layer type. Must be one of: text, image, shape, group, video, audio')*
  Type of layer being created
  Determines which properties and behaviors the layer will have
  Valid values: text, image, shape, group, video, audio
  
  
  *Example: `text`*

- **name** (string) *NotBlank(message: 'Layer name is required'), Length(max: 255, maxMessage: 'Layer name cannot be longer than 255 characters')*
  Display name for the layer
  Used for identification in the layers panel and timeline
  Must be 1-255 characters long
  
  
  *Example: `"example_string"`*

- **properties** (App\DTO\ValueObject\TextLayerProperties|App\DTO\ValueObject\ImageLayerProperties|App\DTO\ValueObject\ShapeLayerProperties)
  Layer-specific visual and behavior properties
  Contains type-specific attributes like text styling, image src, or shape appearance
  Structure depends on layer type and is validated accordingly
  
  

  **Union Type Details:**

  **TextLayerProperties**
  
  Properties specific to text layers
  
  
  
  Properties:
  
    - **text** (string) *optional* *NotBlank(message: 'Text content is required'), Length(max: 10000, maxMessage: 'Text content cannot exceed 10000 characters')*
      The text content to display in the layer
      
      
      *Example: `"example_string"`*
  
    - **fontFamily** (string) *optional* *NotBlank(message: 'Font family is required'), Length(max: 255, maxMessage: 'Font family name cannot exceed 255 characters')*
      Font family name (e.g., Arial, Helvetica, Times New Roman)
      
      
      *Example: `"example_string"`*
  
    - **fontSize** (int) *optional* *Type(type: 'integer', message: 'Font size must be an integer'), Range(min: 1, max: 500, notInRangeMessage: 'Font size must be between 1 and 500 pixels')*
      Font size in pixels (1-500px)
      
      
      *Example: `2`*
  
    - **fontWeight** (string) *optional* *Choice(choices: ['normal', 'bold', '100', '200', '300', '400', '500', '600', '700', '800', '900'], message: 'Invalid font weight')*
      Font weight (normal, bold, or numeric values 100-900)
      
      
      *Example: `normal`*
  
    - **fontStyle** (string) *optional* *Choice(choices: ['normal', 'italic', 'oblique'], message: 'Invalid font style')*
      Font style (normal, italic, or oblique)
      
      
      *Example: `normal`*
  
    - **textAlign** (string) *optional* *Choice(choices: ['left', 'center', 'right', 'justify'], message: 'Invalid text alignment')*
      Text alignment within the layer bounds
      
      
      *Example: `left`*
  
    - **color** (string) *optional* *Regex(pattern: '/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/', message: 'Color must be a valid hex color code')*
      Text color as hex value (e.g., #000000, #fff)
      
      
      *Example: `"example_string"`*
  
    - **lineHeight** (float) *optional* *Type(type: 'float', message: 'Line height must be a number'), Range(min: 0.1, max: 10, notInRangeMessage: 'Line height must be between 0.1 and 10.0')*
      Line height multiplier for text spacing (1.0 = normal, 1.5 = 1.5x spacing)
      
      
      *Example: `1`*
  
    - **letterSpacing** (float) *optional* *Type(type: 'float', message: 'Letter spacing must be a number')*
      Letter spacing in pixels (positive or negative values allowed)
      
      
      *Example: `3.14`*
  
    - **textDecoration** (string) *optional* *Choice(choices: ['none', 'underline', 'overline', 'line-through'], message: 'Invalid text decoration')*
      Text decoration style (none, underline, overline, line-through)
      
      
      *Example: `none`*
  
  
  **ImageLayerProperties**
  
  Properties specific to image layers
  
  
  
  Properties:
  
    - **src** (string) *optional* *NotBlank(message: 'Image source URL is required'), Url(message: 'Image source must be a valid URL')*
      Source URL or path to the image file
      
      
      *Example: `"example_string"`*
  
    - **alt** (string) *optional* *Length(max: 255, maxMessage: 'Alt text cannot exceed 255 characters')*
      Alternative text for accessibility and fallback display
      
      
      *Example: `"example_string"`*
  
    - **objectFit** (string) *optional* *Choice(choices: ['fill', 'contain', 'cover', 'none', 'scale-down'], message: 'Invalid object fit value')*
      How the image should be resized to fit its container (fill, contain, cover, none, scale-down)
      
      
      *Example: `fill`*
  
    - **objectPosition** (string) *optional* *Choice(choices: ['center', 'top', 'bottom', 'left', 'right', 'top left', 'top right', 'bottom left', 'bottom right'], message: 'Invalid object position')*
      Position of the image within its container when using object-fit
      
      
      *Example: `center`*
  
    - **quality** (int) *optional* *Type(type: 'integer', message: 'Quality must be an integer'), Range(min: 1, max: 100, notInRangeMessage: 'Quality must be between 1 and 100')*
      Image quality percentage for compression (1-100, higher = better quality)
      
      
      *Example: `2`*
  
    - **brightness** (float) *optional* *Type(type: 'float', message: 'Brightness must be a number'), Range(min: 0, max: 2, notInRangeMessage: 'Brightness must be between 0 and 2')*
      Image brightness multiplier (0.0 = black, 1.0 = normal, 2.0 = very bright)
      
      
      *Example: `1`*
  
    - **contrast** (float) *optional* *Type(type: 'float', message: 'Contrast must be a number'), Range(min: 0, max: 2, notInRangeMessage: 'Contrast must be between 0 and 2')*
      Image contrast multiplier (0.0 = gray, 1.0 = normal, 2.0 = high contrast)
      
      
      *Example: `1`*
  
    - **saturation** (float) *optional* *Type(type: 'float', message: 'Saturation must be a number'), Range(min: 0, max: 2, notInRangeMessage: 'Saturation must be between 0 and 2')*
      Image saturation multiplier (0.0 = grayscale, 1.0 = normal, 2.0 = vivid)
      
      
      *Example: `1`*
  
    - **blur** (float) *optional* *Type(type: 'float', message: 'Blur must be a number'), PositiveOrZero(message: 'Blur must be positive or zero')*
      Blur radius in pixels (0.0 = sharp, higher values = more blur)
      
      
      *Example: `3.14`*
  
  
  **ShapeLayerProperties**
  
  Properties specific to shape layers
  
  
  
  Properties:
  
    - **shapeType** (string) *optional* *Choice(choices: ['rectangle', 'circle', 'ellipse', 'triangle', 'polygon', 'star', 'line'], message: 'Invalid shape type')*
      Type of shape to render (rectangle, circle, ellipse, triangle, polygon, star, line)
      
      
      *Example: `rectangle`*
  
    - **fillColor** (string) *optional* *Regex(pattern: '/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/', message: 'Fill color must be a valid hex color code')*
      Fill color of the shape as hex value (e.g., #000000, #fff)
      
      
      *Example: `"example_string"`*
  
    - **fillOpacity** (float) *optional* *Type(type: 'float', message: 'Fill opacity must be a number'), Range(min: 0, max: 1, notInRangeMessage: 'Fill opacity must be between 0 and 1')*
      Opacity of the fill color (0.0 = transparent, 1.0 = opaque)
      
      
      *Example: `1`*
  
    - **strokeColor** (string) *optional* *Regex(pattern: '/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/', message: 'Stroke color must be a valid hex color code')*
      Border/stroke color of the shape as hex value
      
      
      *Example: `"example_string"`*
  
    - **strokeWidth** (float) *optional* *Type(type: 'float', message: 'Stroke width must be a number'), PositiveOrZero(message: 'Stroke width must be positive or zero')*
      Width of the border/stroke in pixels (0 = no border)
      
      
      *Example: `3.14`*
  
    - **strokeOpacity** (float) *optional* *Type(type: 'float', message: 'Stroke opacity must be a number'), Range(min: 0, max: 1, notInRangeMessage: 'Stroke opacity must be between 0 and 1')*
      Opacity of the border/stroke (0.0 = transparent, 1.0 = opaque)
      
      
      *Example: `1`*
  
    - **borderRadius** (float) *optional* *Type(type: 'float', message: 'Border radius must be a number'), PositiveOrZero(message: 'Border radius must be positive or zero')*
      Border radius in pixels for rounded corners (applies to rectangles)
      
      
      *Example: `3.14`*
  
    - **sides** (int) *optional* *Type(type: 'integer', message: 'Number of sides must be an integer'), Range(min: 3, max: 20, notInRangeMessage: 'Number of sides must be between 3 and 20')*
      Number of sides for polygon and star shapes (3-20)
      
      
      *Example: `4`*
  
  
  *Example: `null`*

- **transform** (App\DTO\ValueObject\Transform)
  2D transformation matrix for initial layer positioning
  Controls position, size, rotation, scale, skew, and opacity
  Defaults to standard position if not provided
  
  

  **Union Type Details:**

  **Transform**
  
  Represents a 2D transformation matrix for layer positioning and scaling
  
  
  
  Properties:
  
    - **x** (float) *optional* *Type(type: 'float', message: 'X position must be a number')*
      X coordinate position of the layer in pixels
      
      
      *Example: `3.14`*
  
    - **y** (float) *optional* *Type(type: 'float', message: 'Y position must be a number')*
      Y coordinate position of the layer in pixels
      
      
      *Example: `3.14`*
  
    - **width** (float) *optional* *Type(type: 'float', message: 'Width must be a number'), Positive(message: 'Width must be positive')*
      Width of the layer in pixels
      
      
      *Example: `3.14`*
  
    - **height** (float) *optional* *Type(type: 'float', message: 'Height must be a number'), Positive(message: 'Height must be positive')*
      Height of the layer in pixels
      
      
      *Example: `3.14`*
  
    - **rotation** (float) *optional* *Type(type: 'float', message: 'Rotation must be a number'), Range(min: -360, max: 360, notInRangeMessage: 'Rotation must be between -360 and 360 degrees')*
      Rotation angle in degrees (0-360). Positive values rotate clockwise
      
      
      *Example: `3.14`*
  
    - **scaleX** (float) *optional* *Type(type: 'float', message: 'Scale X must be a number'), Positive(message: 'Scale X must be positive')*
      Horizontal scale factor (1.0 = normal size, 2.0 = double width)
      
      
      *Example: `3.14`*
  
    - **scaleY** (float) *optional* *Type(type: 'float', message: 'Scale Y must be a number'), Positive(message: 'Scale Y must be positive')*
      Vertical scale factor (1.0 = normal size, 2.0 = double height)
      
      
      *Example: `3.14`*
  
    - **skewX** (float) *optional* *Type(type: 'float', message: 'Skew X must be a number')*
      Horizontal skew transformation in degrees
      
      
      *Example: `3.14`*
  
    - **skewY** (float) *optional* *Type(type: 'float', message: 'Skew Y must be a number')*
      Vertical skew transformation in degrees
      
      
      *Example: `3.14`*
  
    - **opacity** (float) *optional* *Type(type: 'float', message: 'Opacity must be a number'), Range(min: 0, max: 1, notInRangeMessage: 'Opacity must be between 0 and 1')*
      Layer opacity from 0.0 (transparent) to 1.0 (opaque)
      
      
      *Example: `1`*
  
  
  *Example: `null`*

- **zIndex** (int) *optional* *Type(type: 'integer', message: 'Z-index must be an integer'), PositiveOrZero(message: 'Z-index must be positive or zero')*
  Layer stacking order within the design
  Higher values appear above lower values in the visual stack
  Must be zero or positive integer, null for auto-assignment
  
  
  *Example: `42`*

- **visible** (bool) *optional* *Type(type: 'boolean', message: 'Visible must be a boolean')*
  Initial visibility state of the layer
  true: Layer is visible and rendered in the canvas (default)
  false: Layer is hidden from view but remains in the design
  
  
  *Example: `true`*

- **locked** (bool) *optional* *Type(type: 'boolean', message: 'Locked must be a boolean')*
  Initial edit protection state
  false: Layer can be freely edited and manipulated (default)
  true: Layer cannot be selected, moved, or modified
  
  
  *Example: `true`*

- **parentLayerId** (string) *optional* *Type(type: 'string', message: 'Parent layer ID must be a string')*
  Parent layer ID for hierarchical grouping
  null: Layer is created at root level of the design (default)
  string: Layer is nested under the specified parent layer
  
  
  *Example: `"example_string"`*


#### Response

**JsonResponse**

Response represents an HTTP response in JSON format.

Note that this class does not force the returned JSON content to be an
object. It is however recommended that you do return an object as it
protects yourself against XSSI and JSON-JavaScript Hijacking.

Properties:

- **data** (mixed)
  *Example: `null`*

- **callback** (string) *optional*
  *Example: `"example_string"`*

- **encodingOptions** (int) *optional*
  *Example: `42`*

- **headers** (Symfony\Component\HttpFoundation\ResponseHeaderBag)
  *Example: `null`*

- **content** (string)
  *Example: `"example_string"`*

- **version** (string)
  *Example: `"example_string"`*

- **statusCode** (int)
  *Example: `42`*

- **statusText** (string)
  *Example: `"example_string"`*

- **charset** (string) *optional*
  *Example: `"example_string"`*

- **statusTexts** (array) *optional*
  Status codes translation table.
  
  The list of codes is complete according to the
  {@link https://www.iana.org/assignments/http-status-codes/http-status-codes.xhtml Hypertext Transfer Protocol (HTTP) Status Code Registry}
  (last updated 2021-10-01).
  
  Unless otherwise noted, the status code is defined in RFC2616.
  *Example: `[]`*


---

### PUT /bulk-update

Bulk update multiple layers

Updates multiple layers in a single operation for performance efficiency.
Processes each layer individually with proper validation and permission checks.
Returns detailed results including successful updates and any failures.

#### Request Body

**BulkUpdateLayersRequestDTO**

Request DTO for performing bulk updates on multiple layers.

This DTO handles batch operations for updating layer properties, allowing
efficient modification of multiple layers in a single API call. Each layer
update includes the layer ID and the specific changes to apply.

Properties:

- **layers** (array)
  Array of layer updates to perform in batch.
  
  Each LayerUpdate contains:
  - id: The unique identifier of the layer to update
  - updates: Object containing the properties to change
  
  This allows for efficient bulk operations while maintaining
  type safety and validation for each individual update.
  *Example: `[]`*


#### Response

**JsonResponse**

Response represents an HTTP response in JSON format.

Note that this class does not force the returned JSON content to be an
object. It is however recommended that you do return an object as it
protects yourself against XSSI and JSON-JavaScript Hijacking.

Properties:

- **data** (mixed)
  *Example: `null`*

- **callback** (string) *optional*
  *Example: `"example_string"`*

- **encodingOptions** (int) *optional*
  *Example: `42`*

- **headers** (Symfony\Component\HttpFoundation\ResponseHeaderBag)
  *Example: `null`*

- **content** (string)
  *Example: `"example_string"`*

- **version** (string)
  *Example: `"example_string"`*

- **statusCode** (int)
  *Example: `42`*

- **statusText** (string)
  *Example: `"example_string"`*

- **charset** (string) *optional*
  *Example: `"example_string"`*

- **statusTexts** (array) *optional*
  Status codes translation table.
  
  The list of codes is complete according to the
  {@link https://www.iana.org/assignments/http-status-codes/http-status-codes.xhtml Hypertext Transfer Protocol (HTTP) Status Code Registry}
  (last updated 2021-10-01).
  
  Unless otherwise noted, the status code is defined in RFC2616.
  *Example: `[]`*


---

### GET /{id}

Get details of a specific layer

Returns comprehensive information about a single layer including all properties,
position, styling, and metadata. Validates access permissions through design ownership.

#### Parameters

- **id** (int)

#### Response

**JsonResponse**

Response represents an HTTP response in JSON format.

Note that this class does not force the returned JSON content to be an
object. It is however recommended that you do return an object as it
protects yourself against XSSI and JSON-JavaScript Hijacking.

Properties:

- **data** (mixed)
  *Example: `null`*

- **callback** (string) *optional*
  *Example: `"example_string"`*

- **encodingOptions** (int) *optional*
  *Example: `42`*

- **headers** (Symfony\Component\HttpFoundation\ResponseHeaderBag)
  *Example: `null`*

- **content** (string)
  *Example: `"example_string"`*

- **version** (string)
  *Example: `"example_string"`*

- **statusCode** (int)
  *Example: `42`*

- **statusText** (string)
  *Example: `"example_string"`*

- **charset** (string) *optional*
  *Example: `"example_string"`*

- **statusTexts** (array) *optional*
  Status codes translation table.
  
  The list of codes is complete according to the
  {@link https://www.iana.org/assignments/http-status-codes/http-status-codes.xhtml Hypertext Transfer Protocol (HTTP) Status Code Registry}
  (last updated 2021-10-01).
  
  Unless otherwise noted, the status code is defined in RFC2616.
  *Example: `[]`*


---

### PUT /{id}

Update an existing layer

Modifies layer properties including position, dimensions, styling, and content.
Supports partial updates with validation and maintains layer hierarchy integrity.
Users can only update layers in designs they own.

#### Parameters

- **id** (int)

#### Request Body

**UpdateLayerRequestDTO**

Data Transfer Object for layer update requests

Handles modification of existing design layers including visual properties,
positioning, visibility, and hierarchy changes. All fields are optional
to support partial updates of layer attributes.

Properties:

- **name** (string) *optional* *Length(max: 255, maxMessage: 'Layer name cannot be longer than 255 characters')*
  New display name for the layer
  Used for layer identification in the layers panel and timeline
  Must be 255 characters or less if provided
  
  
  *Example: `"example_string"`*

- **properties** (App\DTO\ValueObject\TextLayerProperties|App\DTO\ValueObject\ImageLayerProperties|App\DTO\ValueObject\ShapeLayerProperties|null) *optional*
  Layer-specific visual and behavior properties
  Contains type-specific attributes like text styling, image filters, or shape appearance
  Structure depends on layer type (text, image, shape, etc.)
  
  

  **Union Type Details:**

  **TextLayerProperties**
  
  Properties specific to text layers
  
  
  
  Properties:
  
    - **text** (string) *optional* *NotBlank(message: 'Text content is required'), Length(max: 10000, maxMessage: 'Text content cannot exceed 10000 characters')*
      The text content to display in the layer
      
      
      *Example: `"example_string"`*
  
    - **fontFamily** (string) *optional* *NotBlank(message: 'Font family is required'), Length(max: 255, maxMessage: 'Font family name cannot exceed 255 characters')*
      Font family name (e.g., Arial, Helvetica, Times New Roman)
      
      
      *Example: `"example_string"`*
  
    - **fontSize** (int) *optional* *Type(type: 'integer', message: 'Font size must be an integer'), Range(min: 1, max: 500, notInRangeMessage: 'Font size must be between 1 and 500 pixels')*
      Font size in pixels (1-500px)
      
      
      *Example: `2`*
  
    - **fontWeight** (string) *optional* *Choice(choices: ['normal', 'bold', '100', '200', '300', '400', '500', '600', '700', '800', '900'], message: 'Invalid font weight')*
      Font weight (normal, bold, or numeric values 100-900)
      
      
      *Example: `normal`*
  
    - **fontStyle** (string) *optional* *Choice(choices: ['normal', 'italic', 'oblique'], message: 'Invalid font style')*
      Font style (normal, italic, or oblique)
      
      
      *Example: `normal`*
  
    - **textAlign** (string) *optional* *Choice(choices: ['left', 'center', 'right', 'justify'], message: 'Invalid text alignment')*
      Text alignment within the layer bounds
      
      
      *Example: `left`*
  
    - **color** (string) *optional* *Regex(pattern: '/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/', message: 'Color must be a valid hex color code')*
      Text color as hex value (e.g., #000000, #fff)
      
      
      *Example: `"example_string"`*
  
    - **lineHeight** (float) *optional* *Type(type: 'float', message: 'Line height must be a number'), Range(min: 0.1, max: 10, notInRangeMessage: 'Line height must be between 0.1 and 10.0')*
      Line height multiplier for text spacing (1.0 = normal, 1.5 = 1.5x spacing)
      
      
      *Example: `1`*
  
    - **letterSpacing** (float) *optional* *Type(type: 'float', message: 'Letter spacing must be a number')*
      Letter spacing in pixels (positive or negative values allowed)
      
      
      *Example: `3.14`*
  
    - **textDecoration** (string) *optional* *Choice(choices: ['none', 'underline', 'overline', 'line-through'], message: 'Invalid text decoration')*
      Text decoration style (none, underline, overline, line-through)
      
      
      *Example: `none`*
  
  
  **ImageLayerProperties**
  
  Properties specific to image layers
  
  
  
  Properties:
  
    - **src** (string) *optional* *NotBlank(message: 'Image source URL is required'), Url(message: 'Image source must be a valid URL')*
      Source URL or path to the image file
      
      
      *Example: `"example_string"`*
  
    - **alt** (string) *optional* *Length(max: 255, maxMessage: 'Alt text cannot exceed 255 characters')*
      Alternative text for accessibility and fallback display
      
      
      *Example: `"example_string"`*
  
    - **objectFit** (string) *optional* *Choice(choices: ['fill', 'contain', 'cover', 'none', 'scale-down'], message: 'Invalid object fit value')*
      How the image should be resized to fit its container (fill, contain, cover, none, scale-down)
      
      
      *Example: `fill`*
  
    - **objectPosition** (string) *optional* *Choice(choices: ['center', 'top', 'bottom', 'left', 'right', 'top left', 'top right', 'bottom left', 'bottom right'], message: 'Invalid object position')*
      Position of the image within its container when using object-fit
      
      
      *Example: `center`*
  
    - **quality** (int) *optional* *Type(type: 'integer', message: 'Quality must be an integer'), Range(min: 1, max: 100, notInRangeMessage: 'Quality must be between 1 and 100')*
      Image quality percentage for compression (1-100, higher = better quality)
      
      
      *Example: `2`*
  
    - **brightness** (float) *optional* *Type(type: 'float', message: 'Brightness must be a number'), Range(min: 0, max: 2, notInRangeMessage: 'Brightness must be between 0 and 2')*
      Image brightness multiplier (0.0 = black, 1.0 = normal, 2.0 = very bright)
      
      
      *Example: `1`*
  
    - **contrast** (float) *optional* *Type(type: 'float', message: 'Contrast must be a number'), Range(min: 0, max: 2, notInRangeMessage: 'Contrast must be between 0 and 2')*
      Image contrast multiplier (0.0 = gray, 1.0 = normal, 2.0 = high contrast)
      
      
      *Example: `1`*
  
    - **saturation** (float) *optional* *Type(type: 'float', message: 'Saturation must be a number'), Range(min: 0, max: 2, notInRangeMessage: 'Saturation must be between 0 and 2')*
      Image saturation multiplier (0.0 = grayscale, 1.0 = normal, 2.0 = vivid)
      
      
      *Example: `1`*
  
    - **blur** (float) *optional* *Type(type: 'float', message: 'Blur must be a number'), PositiveOrZero(message: 'Blur must be positive or zero')*
      Blur radius in pixels (0.0 = sharp, higher values = more blur)
      
      
      *Example: `3.14`*
  
  
  **ShapeLayerProperties**
  
  Properties specific to shape layers
  
  
  
  Properties:
  
    - **shapeType** (string) *optional* *Choice(choices: ['rectangle', 'circle', 'ellipse', 'triangle', 'polygon', 'star', 'line'], message: 'Invalid shape type')*
      Type of shape to render (rectangle, circle, ellipse, triangle, polygon, star, line)
      
      
      *Example: `rectangle`*
  
    - **fillColor** (string) *optional* *Regex(pattern: '/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/', message: 'Fill color must be a valid hex color code')*
      Fill color of the shape as hex value (e.g., #000000, #fff)
      
      
      *Example: `"example_string"`*
  
    - **fillOpacity** (float) *optional* *Type(type: 'float', message: 'Fill opacity must be a number'), Range(min: 0, max: 1, notInRangeMessage: 'Fill opacity must be between 0 and 1')*
      Opacity of the fill color (0.0 = transparent, 1.0 = opaque)
      
      
      *Example: `1`*
  
    - **strokeColor** (string) *optional* *Regex(pattern: '/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/', message: 'Stroke color must be a valid hex color code')*
      Border/stroke color of the shape as hex value
      
      
      *Example: `"example_string"`*
  
    - **strokeWidth** (float) *optional* *Type(type: 'float', message: 'Stroke width must be a number'), PositiveOrZero(message: 'Stroke width must be positive or zero')*
      Width of the border/stroke in pixels (0 = no border)
      
      
      *Example: `3.14`*
  
    - **strokeOpacity** (float) *optional* *Type(type: 'float', message: 'Stroke opacity must be a number'), Range(min: 0, max: 1, notInRangeMessage: 'Stroke opacity must be between 0 and 1')*
      Opacity of the border/stroke (0.0 = transparent, 1.0 = opaque)
      
      
      *Example: `1`*
  
    - **borderRadius** (float) *optional* *Type(type: 'float', message: 'Border radius must be a number'), PositiveOrZero(message: 'Border radius must be positive or zero')*
      Border radius in pixels for rounded corners (applies to rectangles)
      
      
      *Example: `3.14`*
  
    - **sides** (int) *optional* *Type(type: 'integer', message: 'Number of sides must be an integer'), Range(min: 3, max: 20, notInRangeMessage: 'Number of sides must be between 3 and 20')*
      Number of sides for polygon and star shapes (3-20)
      
      
      *Example: `4`*
  
  
  *Example: `null`*

- **transform** (App\DTO\ValueObject\Transform) *optional*
  2D transformation matrix for layer positioning and scaling
  Controls position, size, rotation, scale, skew, and opacity
  Used by the canvas renderer for accurate layer placement
  
  

  **Union Type Details:**

  **Transform**
  
  Represents a 2D transformation matrix for layer positioning and scaling
  
  
  
  Properties:
  
    - **x** (float) *optional* *Type(type: 'float', message: 'X position must be a number')*
      X coordinate position of the layer in pixels
      
      
      *Example: `3.14`*
  
    - **y** (float) *optional* *Type(type: 'float', message: 'Y position must be a number')*
      Y coordinate position of the layer in pixels
      
      
      *Example: `3.14`*
  
    - **width** (float) *optional* *Type(type: 'float', message: 'Width must be a number'), Positive(message: 'Width must be positive')*
      Width of the layer in pixels
      
      
      *Example: `3.14`*
  
    - **height** (float) *optional* *Type(type: 'float', message: 'Height must be a number'), Positive(message: 'Height must be positive')*
      Height of the layer in pixels
      
      
      *Example: `3.14`*
  
    - **rotation** (float) *optional* *Type(type: 'float', message: 'Rotation must be a number'), Range(min: -360, max: 360, notInRangeMessage: 'Rotation must be between -360 and 360 degrees')*
      Rotation angle in degrees (0-360). Positive values rotate clockwise
      
      
      *Example: `3.14`*
  
    - **scaleX** (float) *optional* *Type(type: 'float', message: 'Scale X must be a number'), Positive(message: 'Scale X must be positive')*
      Horizontal scale factor (1.0 = normal size, 2.0 = double width)
      
      
      *Example: `3.14`*
  
    - **scaleY** (float) *optional* *Type(type: 'float', message: 'Scale Y must be a number'), Positive(message: 'Scale Y must be positive')*
      Vertical scale factor (1.0 = normal size, 2.0 = double height)
      
      
      *Example: `3.14`*
  
    - **skewX** (float) *optional* *Type(type: 'float', message: 'Skew X must be a number')*
      Horizontal skew transformation in degrees
      
      
      *Example: `3.14`*
  
    - **skewY** (float) *optional* *Type(type: 'float', message: 'Skew Y must be a number')*
      Vertical skew transformation in degrees
      
      
      *Example: `3.14`*
  
    - **opacity** (float) *optional* *Type(type: 'float', message: 'Opacity must be a number'), Range(min: 0, max: 1, notInRangeMessage: 'Opacity must be between 0 and 1')*
      Layer opacity from 0.0 (transparent) to 1.0 (opaque)
      
      
      *Example: `1`*
  
  
  *Example: `null`*

- **zIndex** (int) *optional* *Type(type: 'integer', message: 'Z-index must be an integer'), PositiveOrZero(message: 'Z-index must be positive or zero')*
  Layer stacking order within its parent container
  Higher values appear above lower values in the visual stack
  Must be zero or positive integer
  
  
  *Example: `42`*

- **visible** (bool) *optional* *Type(type: 'boolean', message: 'Visible must be a boolean')*
  Layer visibility state in the design canvas
  false: Layer is hidden from view but remains in the design
  true: Layer is visible and rendered in the canvas
  
  
  *Example: `true`*

- **locked** (bool) *optional* *Type(type: 'boolean', message: 'Locked must be a boolean')*
  Layer edit protection state
  true: Layer cannot be selected, moved, or modified
  false: Layer can be freely edited and manipulated
  
  
  *Example: `true`*

- **parentLayerId** (string) *optional* *Type(type: 'string', message: 'Parent layer ID must be a string')*
  Parent layer ID for hierarchical grouping
  null: Layer is at root level of the design
  string: Layer is nested under the specified parent layer
  Used for group operations and layer organization
  
  
  *Example: `"example_string"`*


#### Response

**JsonResponse**

Response represents an HTTP response in JSON format.

Note that this class does not force the returned JSON content to be an
object. It is however recommended that you do return an object as it
protects yourself against XSSI and JSON-JavaScript Hijacking.

Properties:

- **data** (mixed)
  *Example: `null`*

- **callback** (string) *optional*
  *Example: `"example_string"`*

- **encodingOptions** (int) *optional*
  *Example: `42`*

- **headers** (Symfony\Component\HttpFoundation\ResponseHeaderBag)
  *Example: `null`*

- **content** (string)
  *Example: `"example_string"`*

- **version** (string)
  *Example: `"example_string"`*

- **statusCode** (int)
  *Example: `42`*

- **statusText** (string)
  *Example: `"example_string"`*

- **charset** (string) *optional*
  *Example: `"example_string"`*

- **statusTexts** (array) *optional*
  Status codes translation table.
  
  The list of codes is complete according to the
  {@link https://www.iana.org/assignments/http-status-codes/http-status-codes.xhtml Hypertext Transfer Protocol (HTTP) Status Code Registry}
  (last updated 2021-10-01).
  
  Unless otherwise noted, the status code is defined in RFC2616.
  *Example: `[]`*


---

### DELETE /{id}

Delete a layer from a design

Permanently removes a layer and all its associated data from the design.
Automatically adjusts z-indexes of remaining layers to maintain proper ordering.
Users can only delete layers in designs they own.

#### Parameters

- **id** (int)

#### Response

**JsonResponse**

Response represents an HTTP response in JSON format.

Note that this class does not force the returned JSON content to be an
object. It is however recommended that you do return an object as it
protects yourself against XSSI and JSON-JavaScript Hijacking.

Properties:

- **data** (mixed)
  *Example: `null`*

- **callback** (string) *optional*
  *Example: `"example_string"`*

- **encodingOptions** (int) *optional*
  *Example: `42`*

- **headers** (Symfony\Component\HttpFoundation\ResponseHeaderBag)
  *Example: `null`*

- **content** (string)
  *Example: `"example_string"`*

- **version** (string)
  *Example: `"example_string"`*

- **statusCode** (int)
  *Example: `42`*

- **statusText** (string)
  *Example: `"example_string"`*

- **charset** (string) *optional*
  *Example: `"example_string"`*

- **statusTexts** (array) *optional*
  Status codes translation table.
  
  The list of codes is complete according to the
  {@link https://www.iana.org/assignments/http-status-codes/http-status-codes.xhtml Hypertext Transfer Protocol (HTTP) Status Code Registry}
  (last updated 2021-10-01).
  
  Unless otherwise noted, the status code is defined in RFC2616.
  *Example: `[]`*


---

### POST /{id}/duplicate

Duplicate a layer within a design

Creates an exact copy of an existing layer with all properties and styling.
The duplicated layer is positioned slightly offset from the original and
assigned a new z-index to appear on top. Maintains all layer relationships.

#### Parameters

- **id** (int)

#### Request Body

**DuplicateLayerRequestDTO**

Data Transfer Object for layer duplication requests.

Handles the duplication of design layers with optional customization
of the duplicate layer name and target design. Used by the layer
management system to create copies of existing layers within the
same design or across different designs.

Properties:

- **name** (string) *optional* *Length(max: 255, maxMessage: 'New layer name cannot be longer than 255 characters')*
  Custom name for the duplicated layer.
  
  If provided, this will be used as the name for the new layer.
  If null, the system will automatically generate a name based
  on the original layer name (e.g., "Original Name Copy").
  Must not exceed 255 characters.
  *Example: `"example_string"`*

- **targetDesignId** (string) *optional* *Type(type: 'string', message: 'Target design ID must be a string')*
  Target design ID for cross-design duplication.
  
  If provided, the layer will be duplicated into the specified
  design instead of the current design. The user must have
  write access to the target design. If null, the layer is
  duplicated within the same design.
  *Example: `"example_string"`*


#### Response

**JsonResponse**

Response represents an HTTP response in JSON format.

Note that this class does not force the returned JSON content to be an
object. It is however recommended that you do return an object as it
protects yourself against XSSI and JSON-JavaScript Hijacking.

Properties:

- **data** (mixed)
  *Example: `null`*

- **callback** (string) *optional*
  *Example: `"example_string"`*

- **encodingOptions** (int) *optional*
  *Example: `42`*

- **headers** (Symfony\Component\HttpFoundation\ResponseHeaderBag)
  *Example: `null`*

- **content** (string)
  *Example: `"example_string"`*

- **version** (string)
  *Example: `"example_string"`*

- **statusCode** (int)
  *Example: `42`*

- **statusText** (string)
  *Example: `"example_string"`*

- **charset** (string) *optional*
  *Example: `"example_string"`*

- **statusTexts** (array) *optional*
  Status codes translation table.
  
  The list of codes is complete according to the
  {@link https://www.iana.org/assignments/http-status-codes/http-status-codes.xhtml Hypertext Transfer Protocol (HTTP) Status Code Registry}
  (last updated 2021-10-01).
  
  Unless otherwise noted, the status code is defined in RFC2616.
  *Example: `[]`*


---

### PUT /{id}/move

Move a layer within the design hierarchy

Changes the layer's position in the z-index stack or moves it to a specific position.
Supports moving layers up/down in the stack or to absolute positions.
Automatically adjusts other layers' z-indexes to maintain proper ordering.

#### Parameters

- **id** (int)

#### Request Body

**MoveLayerRequestDTO**

Data Transfer Object for layer movement requests.

Handles repositioning of layers within the design canvas z-order.
Supports both relative movements (up/down/top/bottom) and absolute
positioning via z-index targeting. Used in the layer management
system to control layer stacking order and visual hierarchy.

Properties:

- **direction** (string) *optional* *Choice(choices: ['up', 'down', 'top', 'bottom'], message: 'Direction must be one of: up, down, top, bottom')*
  Direction to move the layer relative to its current position.
  
  Supported values:
  - 'up': Move one position forward in z-order
  - 'down': Move one position backward in z-order
  - 'top': Move to highest z-index (front-most layer)
  - 'bottom': Move to lowest z-index (back-most layer)
  
  Either direction or targetZIndex should be provided, not both.
  Null indicates absolute positioning via targetZIndex should be used.
  *Example: `up`*

- **targetZIndex** (int) *optional* *Type(type: 'integer', message: 'Target Z-index must be an integer'), PositiveOrZero(message: 'Target Z-index must be positive or zero')*
  Absolute z-index position to move the layer to.
  
  When provided, moves the layer to this exact z-index position.
  Must be a non-negative integer. Other layers will be adjusted
  automatically to maintain proper z-order sequence.
  
  Either targetZIndex or direction should be provided, not both.
  Null indicates relative movement via direction should be used.
  *Example: `42`*


#### Response

**JsonResponse**

Response represents an HTTP response in JSON format.

Note that this class does not force the returned JSON content to be an
object. It is however recommended that you do return an object as it
protects yourself against XSSI and JSON-JavaScript Hijacking.

Properties:

- **data** (mixed)
  *Example: `null`*

- **callback** (string) *optional*
  *Example: `"example_string"`*

- **encodingOptions** (int) *optional*
  *Example: `42`*

- **headers** (Symfony\Component\HttpFoundation\ResponseHeaderBag)
  *Example: `null`*

- **content** (string)
  *Example: `"example_string"`*

- **version** (string)
  *Example: `"example_string"`*

- **statusCode** (int)
  *Example: `42`*

- **statusText** (string)
  *Example: `"example_string"`*

- **charset** (string) *optional*
  *Example: `"example_string"`*

- **statusTexts** (array) *optional*
  Status codes translation table.
  
  The list of codes is complete according to the
  {@link https://www.iana.org/assignments/http-status-codes/http-status-codes.xhtml Hypertext Transfer Protocol (HTTP) Status Code Registry}
  (last updated 2021-10-01).
  
  Unless otherwise noted, the status code is defined in RFC2616.
  *Example: `[]`*


---

## ExportJobController

Export Job Controller

Manages export job operations for design rendering and file generation.
Handles job creation, monitoring, cancellation, retry, and file download functionality.
All endpoints require authentication and enforce user ownership for security.

### GET 

**Security:** IsGranted

List export jobs for authenticated user

Returns a paginated list of export jobs belonging to the authenticated user.
Supports filtering by status and format, with configurable pagination.

#### Parameters

- **request** (Symfony\Component\HttpFoundation\Request)

#### Response

**JsonResponse**

Response represents an HTTP response in JSON format.

Note that this class does not force the returned JSON content to be an
object. It is however recommended that you do return an object as it
protects yourself against XSSI and JSON-JavaScript Hijacking.

Properties:

- **data** (mixed)
  *Example: `null`*

- **callback** (string) *optional*
  *Example: `"example_string"`*

- **encodingOptions** (int) *optional*
  *Example: `42`*

- **headers** (Symfony\Component\HttpFoundation\ResponseHeaderBag)
  *Example: `null`*

- **content** (string)
  *Example: `"example_string"`*

- **version** (string)
  *Example: `"example_string"`*

- **statusCode** (int)
  *Example: `42`*

- **statusText** (string)
  *Example: `"example_string"`*

- **charset** (string) *optional*
  *Example: `"example_string"`*

- **statusTexts** (array) *optional*
  Status codes translation table.
  
  The list of codes is complete according to the
  {@link https://www.iana.org/assignments/http-status-codes/http-status-codes.xhtml Hypertext Transfer Protocol (HTTP) Status Code Registry}
  (last updated 2021-10-01).
  
  Unless otherwise noted, the status code is defined in RFC2616.
  *Example: `[]`*


---

### POST 

**Security:** IsGranted

Create a new export job

Creates a new export job for rendering a design in the specified format and settings.
Validates user access to the design and enqueues the job for background processing.

#### Request Body

**CreateExportJobRequestDTO**

Data Transfer Object for export job creation requests.

Handles the submission of design export jobs to the background
processing system. Supports multiple output formats and quality
settings for rendering designs to various file types including
static images, PDFs, and animated formats.

Properties:

- **designId** (int) *NotBlank(message: 'Design ID is required'), Type(type: 'integer', message: 'Design ID must be an integer'), Positive(message: 'Design ID must be positive')*
  ID of the design to export.
  
  References the specific design that should be rendered
  and exported. The user must have access permissions
  to the design for the export to succeed.
  *Example: `42`*

- **format** (string) *optional* *NotBlank(message: 'Format is required'), Choice(choices: ['png', 'jpeg', 'svg', 'pdf', 'mp4', 'gif'], message: 'Invalid format')*
  Output format for the exported file.
  
  Determines the file type and rendering pipeline used
  for the export. Each format has different capabilities
  and use cases:
  
  - 'png': High-quality raster with transparency support
  - 'jpeg': Compressed raster for smaller file sizes
  - 'svg': Vector format for scalable graphics
  - 'pdf': Print-ready document format
  - 'mp4': Video format for animated designs
  - 'gif': Animated raster format
  *Example: `png`*

- **quality** (string) *optional* *Choice(choices: ['low', 'medium', 'high', 'ultra'], message: 'Invalid quality')*
  Quality level for the export rendering.
  
  Controls the balance between file size and visual quality:
  - 'low': Faster rendering, smaller files, reduced quality
  - 'medium': Balanced rendering and quality (default)
  - 'high': Better quality, larger files, slower rendering
  - 'ultra': Maximum quality for professional use
  *Example: `low`*

- **width** (int) *optional* *Type(type: 'integer', message: 'Width must be an integer'), Positive(message: 'Width must be positive')*
  Custom width for the exported file in pixels.
  
  If provided, overrides the design's canvas width.
  Must be used with height or scale. Null uses the
  design's original dimensions.
  *Example: `42`*

- **height** (int) *optional* *Type(type: 'integer', message: 'Height must be an integer'), Positive(message: 'Height must be positive')*
  Custom height for the exported file in pixels.
  
  If provided, overrides the design's canvas height.
  Must be used with width or scale. Null uses the
  design's original dimensions.
  *Example: `42`*

- **scale** (float) *optional* *Type(type: 'float', message: 'Scale must be a number'), PositiveOrZero(message: 'Scale must be positive or zero')*
  Scale factor for resizing the export.
  
  Multiplier applied to the design's original dimensions.
  For example, 2.0 doubles the size, 0.5 halves it.
  Alternative to specifying exact width/height.
  *Example: `3.14`*

- **transparent** (bool) *optional* *Type(type: 'bool', message: 'Transparent must be a boolean')*
  Enable transparent background for supported formats.
  
  When true, removes the canvas background and exports
  with transparency. Only supported by PNG and SVG formats.
  For other formats, the background will be white.
  *Example: `true`*

- **backgroundColor** (string) *optional* *Type(type: 'string', message: 'Background color must be a string')*
  Custom background color for the export.
  
  Hex color code (e.g., "#ffffff") to use as the canvas
  background. Overrides the design's background color.
  Ignored when transparent is true.
  *Example: `"example_string"`*

- **animationSettings** (array) *optional* *Type(type: 'array', message: 'Animation settings must be an array')*
  Animation-specific settings for video/GIF exports.
  
  Configuration for animated exports including:
  - duration: Animation length in seconds
  - fps: Frames per second for video formats
  - loop: Whether GIFs should loop infinitely
  - timeline: Specific animation timeline to export
  *Example: `[]`*


#### Response

**JsonResponse**

Response represents an HTTP response in JSON format.

Note that this class does not force the returned JSON content to be an
object. It is however recommended that you do return an object as it
protects yourself against XSSI and JSON-JavaScript Hijacking.

Properties:

- **data** (mixed)
  *Example: `null`*

- **callback** (string) *optional*
  *Example: `"example_string"`*

- **encodingOptions** (int) *optional*
  *Example: `42`*

- **headers** (Symfony\Component\HttpFoundation\ResponseHeaderBag)
  *Example: `null`*

- **content** (string)
  *Example: `"example_string"`*

- **version** (string)
  *Example: `"example_string"`*

- **statusCode** (int)
  *Example: `42`*

- **statusText** (string)
  *Example: `"example_string"`*

- **charset** (string) *optional*
  *Example: `"example_string"`*

- **statusTexts** (array) *optional*
  Status codes translation table.
  
  The list of codes is complete according to the
  {@link https://www.iana.org/assignments/http-status-codes/http-status-codes.xhtml Hypertext Transfer Protocol (HTTP) Status Code Registry}
  (last updated 2021-10-01).
  
  Unless otherwise noted, the status code is defined in RFC2616.
  *Example: `[]`*


---

### GET /queue-status

**Security:** IsGranted

Get export job queue status (Admin only)

Returns system-wide export job queue statistics and health information.
Includes pending/processing job counts, average processing times, and queue health metrics.
Only accessible to users with ROLE_ADMIN.

#### Response

**JsonResponse**

Response represents an HTTP response in JSON format.

Note that this class does not force the returned JSON content to be an
object. It is however recommended that you do return an object as it
protects yourself against XSSI and JSON-JavaScript Hijacking.

Properties:

- **data** (mixed)
  *Example: `null`*

- **callback** (string) *optional*
  *Example: `"example_string"`*

- **encodingOptions** (int) *optional*
  *Example: `42`*

- **headers** (Symfony\Component\HttpFoundation\ResponseHeaderBag)
  *Example: `null`*

- **content** (string)
  *Example: `"example_string"`*

- **version** (string)
  *Example: `"example_string"`*

- **statusCode** (int)
  *Example: `42`*

- **statusText** (string)
  *Example: `"example_string"`*

- **charset** (string) *optional*
  *Example: `"example_string"`*

- **statusTexts** (array) *optional*
  Status codes translation table.
  
  The list of codes is complete according to the
  {@link https://www.iana.org/assignments/http-status-codes/http-status-codes.xhtml Hypertext Transfer Protocol (HTTP) Status Code Registry}
  (last updated 2021-10-01).
  
  Unless otherwise noted, the status code is defined in RFC2616.
  *Example: `[]`*


---

### GET /stats

**Security:** IsGranted

Get export job statistics for authenticated user

Returns comprehensive statistics about the user's export job usage,
including totals by status, format breakdown, and success rate.

#### Response

**JsonResponse**

Response represents an HTTP response in JSON format.

Note that this class does not force the returned JSON content to be an
object. It is however recommended that you do return an object as it
protects yourself against XSSI and JSON-JavaScript Hijacking.

Properties:

- **data** (mixed)
  *Example: `null`*

- **callback** (string) *optional*
  *Example: `"example_string"`*

- **encodingOptions** (int) *optional*
  *Example: `42`*

- **headers** (Symfony\Component\HttpFoundation\ResponseHeaderBag)
  *Example: `null`*

- **content** (string)
  *Example: `"example_string"`*

- **version** (string)
  *Example: `"example_string"`*

- **statusCode** (int)
  *Example: `42`*

- **statusText** (string)
  *Example: `"example_string"`*

- **charset** (string) *optional*
  *Example: `"example_string"`*

- **statusTexts** (array) *optional*
  Status codes translation table.
  
  The list of codes is complete according to the
  {@link https://www.iana.org/assignments/http-status-codes/http-status-codes.xhtml Hypertext Transfer Protocol (HTTP) Status Code Registry}
  (last updated 2021-10-01).
  
  Unless otherwise noted, the status code is defined in RFC2616.
  *Example: `[]`*


---

### GET /{id}

**Security:** IsGranted

Get details of a specific export job

Returns detailed information about a single export job.
Only allows access to export jobs owned by the authenticated user.

#### Parameters

- **exportJob** (App\Entity\ExportJob)

#### Response

**JsonResponse**

Response represents an HTTP response in JSON format.

Note that this class does not force the returned JSON content to be an
object. It is however recommended that you do return an object as it
protects yourself against XSSI and JSON-JavaScript Hijacking.

Properties:

- **data** (mixed)
  *Example: `null`*

- **callback** (string) *optional*
  *Example: `"example_string"`*

- **encodingOptions** (int) *optional*
  *Example: `42`*

- **headers** (Symfony\Component\HttpFoundation\ResponseHeaderBag)
  *Example: `null`*

- **content** (string)
  *Example: `"example_string"`*

- **version** (string)
  *Example: `"example_string"`*

- **statusCode** (int)
  *Example: `42`*

- **statusText** (string)
  *Example: `"example_string"`*

- **charset** (string) *optional*
  *Example: `"example_string"`*

- **statusTexts** (array) *optional*
  Status codes translation table.
  
  The list of codes is complete according to the
  {@link https://www.iana.org/assignments/http-status-codes/http-status-codes.xhtml Hypertext Transfer Protocol (HTTP) Status Code Registry}
  (last updated 2021-10-01).
  
  Unless otherwise noted, the status code is defined in RFC2616.
  *Example: `[]`*


---

### PUT /{id}

**Security:** IsGranted

Update an export job (Not allowed)

Export jobs are immutable after creation and cannot be modified.
This endpoint always returns an error indicating that modifications are not permitted.

#### Parameters

- **exportJob** (App\Entity\ExportJob)

#### Response

**JsonResponse**

Response represents an HTTP response in JSON format.

Note that this class does not force the returned JSON content to be an
object. It is however recommended that you do return an object as it
protects yourself against XSSI and JSON-JavaScript Hijacking.

Properties:

- **data** (mixed)
  *Example: `null`*

- **callback** (string) *optional*
  *Example: `"example_string"`*

- **encodingOptions** (int) *optional*
  *Example: `42`*

- **headers** (Symfony\Component\HttpFoundation\ResponseHeaderBag)
  *Example: `null`*

- **content** (string)
  *Example: `"example_string"`*

- **version** (string)
  *Example: `"example_string"`*

- **statusCode** (int)
  *Example: `42`*

- **statusText** (string)
  *Example: `"example_string"`*

- **charset** (string) *optional*
  *Example: `"example_string"`*

- **statusTexts** (array) *optional*
  Status codes translation table.
  
  The list of codes is complete according to the
  {@link https://www.iana.org/assignments/http-status-codes/http-status-codes.xhtml Hypertext Transfer Protocol (HTTP) Status Code Registry}
  (last updated 2021-10-01).
  
  Unless otherwise noted, the status code is defined in RFC2616.
  *Example: `[]`*


---

### DELETE /{id}

**Security:** IsGranted

Delete an export job

Deletes an export job and its associated output file.
Only allows deletion of jobs in pending, failed, or completed status.
Only allows access to export jobs owned by the authenticated user.

#### Parameters

- **exportJob** (App\Entity\ExportJob)

#### Response

**JsonResponse**

Response represents an HTTP response in JSON format.

Note that this class does not force the returned JSON content to be an
object. It is however recommended that you do return an object as it
protects yourself against XSSI and JSON-JavaScript Hijacking.

Properties:

- **data** (mixed)
  *Example: `null`*

- **callback** (string) *optional*
  *Example: `"example_string"`*

- **encodingOptions** (int) *optional*
  *Example: `42`*

- **headers** (Symfony\Component\HttpFoundation\ResponseHeaderBag)
  *Example: `null`*

- **content** (string)
  *Example: `"example_string"`*

- **version** (string)
  *Example: `"example_string"`*

- **statusCode** (int)
  *Example: `42`*

- **statusText** (string)
  *Example: `"example_string"`*

- **charset** (string) *optional*
  *Example: `"example_string"`*

- **statusTexts** (array) *optional*
  Status codes translation table.
  
  The list of codes is complete according to the
  {@link https://www.iana.org/assignments/http-status-codes/http-status-codes.xhtml Hypertext Transfer Protocol (HTTP) Status Code Registry}
  (last updated 2021-10-01).
  
  Unless otherwise noted, the status code is defined in RFC2616.
  *Example: `[]`*


---

### POST /{id}/cancel

**Security:** IsGranted

Cancel a pending or processing export job

Cancels an export job that is currently pending or being processed.
Sets the job status to cancelled and stops any ongoing processing.
Only allows access to export jobs owned by the authenticated user.

#### Parameters

- **exportJob** (App\Entity\ExportJob)

#### Response

**JsonResponse**

Response represents an HTTP response in JSON format.

Note that this class does not force the returned JSON content to be an
object. It is however recommended that you do return an object as it
protects yourself against XSSI and JSON-JavaScript Hijacking.

Properties:

- **data** (mixed)
  *Example: `null`*

- **callback** (string) *optional*
  *Example: `"example_string"`*

- **encodingOptions** (int) *optional*
  *Example: `42`*

- **headers** (Symfony\Component\HttpFoundation\ResponseHeaderBag)
  *Example: `null`*

- **content** (string)
  *Example: `"example_string"`*

- **version** (string)
  *Example: `"example_string"`*

- **statusCode** (int)
  *Example: `42`*

- **statusText** (string)
  *Example: `"example_string"`*

- **charset** (string) *optional*
  *Example: `"example_string"`*

- **statusTexts** (array) *optional*
  Status codes translation table.
  
  The list of codes is complete according to the
  {@link https://www.iana.org/assignments/http-status-codes/http-status-codes.xhtml Hypertext Transfer Protocol (HTTP) Status Code Registry}
  (last updated 2021-10-01).
  
  Unless otherwise noted, the status code is defined in RFC2616.
  *Example: `[]`*


---

### GET /{id}/download

**Security:** IsGranted

Download export job output file

Downloads the generated file from a completed export job.
Returns the file as an attachment with appropriate headers.
Only allows download of completed jobs with existing output files.
Only allows access to export jobs owned by the authenticated user.

#### Parameters

- **exportJob** (App\Entity\ExportJob)

#### Response

**Response**

Response represents an HTTP response.



Properties:

- **headers** (Symfony\Component\HttpFoundation\ResponseHeaderBag)
  *Example: `null`*

- **content** (string)
  *Example: `"example_string"`*

- **version** (string)
  *Example: `"example_string"`*

- **statusCode** (int)
  *Example: `42`*

- **statusText** (string)
  *Example: `"example_string"`*

- **charset** (string) *optional*
  *Example: `"example_string"`*

- **statusTexts** (array) *optional*
  Status codes translation table.
  
  The list of codes is complete according to the
  {@link https://www.iana.org/assignments/http-status-codes/http-status-codes.xhtml Hypertext Transfer Protocol (HTTP) Status Code Registry}
  (last updated 2021-10-01).
  
  Unless otherwise noted, the status code is defined in RFC2616.
  *Example: `[]`*

- **sentHeaders** (array)
  Tracks headers already sent in informational responses.
  
  
  *Example: `[]`*


---

### POST /{id}/retry

**Security:** IsGranted

Retry a failed export job

Resets a failed export job back to pending status for re-processing.
Clears error messages and resets progress to zero.
Only allows retry of jobs with failed status.
Only allows access to export jobs owned by the authenticated user.

#### Parameters

- **exportJob** (App\Entity\ExportJob)

#### Response

**JsonResponse**

Response represents an HTTP response in JSON format.

Note that this class does not force the returned JSON content to be an
object. It is however recommended that you do return an object as it
protects yourself against XSSI and JSON-JavaScript Hijacking.

Properties:

- **data** (mixed)
  *Example: `null`*

- **callback** (string) *optional*
  *Example: `"example_string"`*

- **encodingOptions** (int) *optional*
  *Example: `42`*

- **headers** (Symfony\Component\HttpFoundation\ResponseHeaderBag)
  *Example: `null`*

- **content** (string)
  *Example: `"example_string"`*

- **version** (string)
  *Example: `"example_string"`*

- **statusCode** (int)
  *Example: `42`*

- **statusText** (string)
  *Example: `"example_string"`*

- **charset** (string) *optional*
  *Example: `"example_string"`*

- **statusTexts** (array) *optional*
  Status codes translation table.
  
  The list of codes is complete according to the
  {@link https://www.iana.org/assignments/http-status-codes/http-status-codes.xhtml Hypertext Transfer Protocol (HTTP) Status Code Registry}
  (last updated 2021-10-01).
  
  Unless otherwise noted, the status code is defined in RFC2616.
  *Example: `[]`*


---

## TemplateController

Template Controller

Manages design templates including browsing, creation, usage tracking, and categorization.
Provides template marketplace functionality with search, filtering, and category management.
Handles template usage analytics and supports both public and user-created templates.
Templates serve as starting points for new design projects.

### GET 

List available templates with filtering and pagination

Returns a paginated list of templates with optional category filtering.
Includes template metadata, thumbnail images, and usage statistics.
Both public templates and user-created templates are included in results.

#### Parameters

- **request** (Symfony\Component\HttpFoundation\Request)

#### Response

**JsonResponse**

Response represents an HTTP response in JSON format.

Note that this class does not force the returned JSON content to be an
object. It is however recommended that you do return an object as it
protects yourself against XSSI and JSON-JavaScript Hijacking.

Properties:

- **data** (mixed)
  *Example: `null`*

- **callback** (string) *optional*
  *Example: `"example_string"`*

- **encodingOptions** (int) *optional*
  *Example: `42`*

- **headers** (Symfony\Component\HttpFoundation\ResponseHeaderBag)
  *Example: `null`*

- **content** (string)
  *Example: `"example_string"`*

- **version** (string)
  *Example: `"example_string"`*

- **statusCode** (int)
  *Example: `42`*

- **statusText** (string)
  *Example: `"example_string"`*

- **charset** (string) *optional*
  *Example: `"example_string"`*

- **statusTexts** (array) *optional*
  Status codes translation table.
  
  The list of codes is complete according to the
  {@link https://www.iana.org/assignments/http-status-codes/http-status-codes.xhtml Hypertext Transfer Protocol (HTTP) Status Code Registry}
  (last updated 2021-10-01).
  
  Unless otherwise noted, the status code is defined in RFC2616.
  *Example: `[]`*


---

### POST 

**Security:** IsGranted

Create a new template

Creates a new template from user design with metadata and canvas configuration.
Templates can be made public for marketplace or kept private for personal use.
Requires authentication and validates all template data before creation.

#### Request Body

**CreateTemplateRequestDTO**

Properties:

- **name** (string) *NotBlank(message: 'Template name is required'), Length(max: 255, maxMessage: 'Template name cannot be longer than 255 characters')*
  The template name (required, max 255 characters)
  Used for identification and search purposes
  
  
  *Example: `"example_string"`*

- **description** (string) *Length(max: 1000, maxMessage: 'Description cannot be longer than 1000 characters')*
  Optional description of the template (max 1000 characters)
  Provides context about the template's purpose and usage
  
  
  *Example: `"example_string"`*

- **category** (string) *NotBlank(message: 'Category is required'), Choice(choices: ['social-media', 'presentation', 'print', 'marketing', 'document', 'logo', 'web-graphics', 'video', 'animation'], message: 'Invalid category')*
  Template category for organization and filtering
  Must be one of the predefined categories
  
  
  *Example: `social-media`*

- **tags** (array) *Type(type: 'array', message: 'Tags must be an array'), Valid*
  Array of tags for categorization and search
  Each tag must be 1-50 characters and contain only alphanumeric characters, spaces, hyphens, and underscores
  
  
  *Example: `[]`*

- **width** (int) *NotBlank(message: 'Width is required'), Type(type: 'integer', message: 'Width must be an integer'), Positive(message: 'Width must be positive')*
  Canvas width in pixels (required, must be positive)
  Defines the template's design area width
  
  
  *Example: `42`*

- **height** (int) *NotBlank(message: 'Height is required'), Type(type: 'integer', message: 'Height must be an integer'), Positive(message: 'Height must be positive')*
  Canvas height in pixels (required, must be positive)
  Defines the template's design area height
  
  
  *Example: `42`*

- **canvasSettings** (array) *optional* *Type(type: 'array', message: 'Canvas settings must be an array')*
  Canvas configuration settings as key-value pairs
  Contains background color, grid settings, guides, etc.
  
  
  *Example: `[]`*

- **layers** (array) *optional* *Type(type: 'array', message: 'Layers must be an array')*
  Layer definitions for the template
  Contains the visual elements that make up the template
  
  
  *Example: `[]`*

- **thumbnailUrl** (string) *optional* *Url(message: 'Thumbnail URL must be a valid URL')*
  Optional URL to template thumbnail image
  Used for preview in template galleries
  
  
  *Example: `"example_string"`*

- **previewUrl** (string) *optional* *Url(message: 'Preview URL must be a valid URL')*
  Optional URL to template preview image
  Used for larger preview displays
  
  
  *Example: `"example_string"`*

- **isPremium** (bool) *optional* *Type(type: 'bool', message: 'isPremium must be a boolean')*
  Whether this template requires premium access
  Premium templates are only available to paid users
  
  
  *Example: `true`*

- **isActive** (bool) *optional* *Type(type: 'bool', message: 'isActive must be a boolean')*
  Whether this template is active and visible
  Inactive templates are hidden from users
  
  
  *Example: `true`*


#### Response

**JsonResponse**

Response represents an HTTP response in JSON format.

Note that this class does not force the returned JSON content to be an
object. It is however recommended that you do return an object as it
protects yourself against XSSI and JSON-JavaScript Hijacking.

Properties:

- **data** (mixed)
  *Example: `null`*

- **callback** (string) *optional*
  *Example: `"example_string"`*

- **encodingOptions** (int) *optional*
  *Example: `42`*

- **headers** (Symfony\Component\HttpFoundation\ResponseHeaderBag)
  *Example: `null`*

- **content** (string)
  *Example: `"example_string"`*

- **version** (string)
  *Example: `"example_string"`*

- **statusCode** (int)
  *Example: `42`*

- **statusText** (string)
  *Example: `"example_string"`*

- **charset** (string) *optional*
  *Example: `"example_string"`*

- **statusTexts** (array) *optional*
  Status codes translation table.
  
  The list of codes is complete according to the
  {@link https://www.iana.org/assignments/http-status-codes/http-status-codes.xhtml Hypertext Transfer Protocol (HTTP) Status Code Registry}
  (last updated 2021-10-01).
  
  Unless otherwise noted, the status code is defined in RFC2616.
  *Example: `[]`*


---

### GET /categories

Get available template categories

Returns a list of all available template categories for filtering
and organization purposes. Categories help users find relevant templates
for their specific design needs and use cases.

#### Response

**JsonResponse**

Response represents an HTTP response in JSON format.

Note that this class does not force the returned JSON content to be an
object. It is however recommended that you do return an object as it
protects yourself against XSSI and JSON-JavaScript Hijacking.

Properties:

- **data** (mixed)
  *Example: `null`*

- **callback** (string) *optional*
  *Example: `"example_string"`*

- **encodingOptions** (int) *optional*
  *Example: `42`*

- **headers** (Symfony\Component\HttpFoundation\ResponseHeaderBag)
  *Example: `null`*

- **content** (string)
  *Example: `"example_string"`*

- **version** (string)
  *Example: `"example_string"`*

- **statusCode** (int)
  *Example: `42`*

- **statusText** (string)
  *Example: `"example_string"`*

- **charset** (string) *optional*
  *Example: `"example_string"`*

- **statusTexts** (array) *optional*
  Status codes translation table.
  
  The list of codes is complete according to the
  {@link https://www.iana.org/assignments/http-status-codes/http-status-codes.xhtml Hypertext Transfer Protocol (HTTP) Status Code Registry}
  (last updated 2021-10-01).
  
  Unless otherwise noted, the status code is defined in RFC2616.
  *Example: `[]`*


---

### GET /search

Search templates with advanced filtering

Performs comprehensive template search with support for text queries,
category filtering, and tag-based search. Returns paginated results
sorted by relevance and usage popularity.

#### Request Body

**SearchTemplateRequestDTO**

Properties:

- **q** (string) *optional* *Type(type: 'string', message: 'Query must be a string')*
  *Example: `"example_string"`*

- **category** (string) *optional* *Choice(choices: ['social-media', 'presentation', 'print', 'marketing', 'document', 'logo', 'web-graphics', 'video', 'animation'], message: 'Invalid category')*
  *Example: `social-media`*

- **page** (int) *optional* *Type(type: 'integer', message: 'Page must be an integer'), Positive(message: 'Page must be positive')*
  *Example: `42`*

- **limit** (int) *optional* *Type(type: 'integer', message: 'Limit must be an integer'), Range(min: 1, max: 50, notInRangeMessage: 'Limit must be between 1 and 50')*
  *Example: `2`*


#### Response

**JsonResponse**

Response represents an HTTP response in JSON format.

Note that this class does not force the returned JSON content to be an
object. It is however recommended that you do return an object as it
protects yourself against XSSI and JSON-JavaScript Hijacking.

Properties:

- **data** (mixed)
  *Example: `null`*

- **callback** (string) *optional*
  *Example: `"example_string"`*

- **encodingOptions** (int) *optional*
  *Example: `42`*

- **headers** (Symfony\Component\HttpFoundation\ResponseHeaderBag)
  *Example: `null`*

- **content** (string)
  *Example: `"example_string"`*

- **version** (string)
  *Example: `"example_string"`*

- **statusCode** (int)
  *Example: `42`*

- **statusText** (string)
  *Example: `"example_string"`*

- **charset** (string) *optional*
  *Example: `"example_string"`*

- **statusTexts** (array) *optional*
  Status codes translation table.
  
  The list of codes is complete according to the
  {@link https://www.iana.org/assignments/http-status-codes/http-status-codes.xhtml Hypertext Transfer Protocol (HTTP) Status Code Registry}
  (last updated 2021-10-01).
  
  Unless otherwise noted, the status code is defined in RFC2616.
  *Example: `[]`*


---

### GET /{uuid}

Get details of a specific template

Returns comprehensive template information including design data, metadata,
and usage statistics. Automatically increments the view count for analytics.
Only returns active templates that are publicly available.

#### Parameters

- **uuid** (string)

#### Response

**JsonResponse**

Response represents an HTTP response in JSON format.

Note that this class does not force the returned JSON content to be an
object. It is however recommended that you do return an object as it
protects yourself against XSSI and JSON-JavaScript Hijacking.

Properties:

- **data** (mixed)
  *Example: `null`*

- **callback** (string) *optional*
  *Example: `"example_string"`*

- **encodingOptions** (int) *optional*
  *Example: `42`*

- **headers** (Symfony\Component\HttpFoundation\ResponseHeaderBag)
  *Example: `null`*

- **content** (string)
  *Example: `"example_string"`*

- **version** (string)
  *Example: `"example_string"`*

- **statusCode** (int)
  *Example: `42`*

- **statusText** (string)
  *Example: `"example_string"`*

- **charset** (string) *optional*
  *Example: `"example_string"`*

- **statusTexts** (array) *optional*
  Status codes translation table.
  
  The list of codes is complete according to the
  {@link https://www.iana.org/assignments/http-status-codes/http-status-codes.xhtml Hypertext Transfer Protocol (HTTP) Status Code Registry}
  (last updated 2021-10-01).
  
  Unless otherwise noted, the status code is defined in RFC2616.
  *Example: `[]`*


---

### POST /{uuid}/use

**Security:** IsGranted

Use a template to create a new design

Creates a new design project based on the specified template.
Copies all template layers, settings, and properties to the new design.
Automatically increments the template usage count for analytics.

#### Parameters

- **uuid** (string)

#### Response

**JsonResponse**

Response represents an HTTP response in JSON format.

Note that this class does not force the returned JSON content to be an
object. It is however recommended that you do return an object as it
protects yourself against XSSI and JSON-JavaScript Hijacking.

Properties:

- **data** (mixed)
  *Example: `null`*

- **callback** (string) *optional*
  *Example: `"example_string"`*

- **encodingOptions** (int) *optional*
  *Example: `42`*

- **headers** (Symfony\Component\HttpFoundation\ResponseHeaderBag)
  *Example: `null`*

- **content** (string)
  *Example: `"example_string"`*

- **version** (string)
  *Example: `"example_string"`*

- **statusCode** (int)
  *Example: `42`*

- **statusText** (string)
  *Example: `"example_string"`*

- **charset** (string) *optional*
  *Example: `"example_string"`*

- **statusTexts** (array) *optional*
  Status codes translation table.
  
  The list of codes is complete according to the
  {@link https://www.iana.org/assignments/http-status-codes/http-status-codes.xhtml Hypertext Transfer Protocol (HTTP) Status Code Registry}
  (last updated 2021-10-01).
  
  Unless otherwise noted, the status code is defined in RFC2616.
  *Example: `[]`*


---

## SearchController

Search Controller

Provides comprehensive search functionality across all platform content types.
Handles global search, content-specific searches, and search suggestions.
Supports full-text search with filtering, pagination, and relevance scoring.
All searches respect user permissions and visibility settings.

### GET 

Perform a global search across multiple content types

Searches across templates, media, projects based on the query and type filter.
Supports pagination and returns results in a structured format.

#### Parameters

- **request** (Symfony\Component\HttpFoundation\Request)

#### Response

**JsonResponse**

Response represents an HTTP response in JSON format.

Note that this class does not force the returned JSON content to be an
object. It is however recommended that you do return an object as it
protects yourself against XSSI and JSON-JavaScript Hijacking.

Properties:

- **data** (mixed)
  *Example: `null`*

- **callback** (string) *optional*
  *Example: `"example_string"`*

- **encodingOptions** (int) *optional*
  *Example: `42`*

- **headers** (Symfony\Component\HttpFoundation\ResponseHeaderBag)
  *Example: `null`*

- **content** (string)
  *Example: `"example_string"`*

- **version** (string)
  *Example: `"example_string"`*

- **statusCode** (int)
  *Example: `42`*

- **statusText** (string)
  *Example: `"example_string"`*

- **charset** (string) *optional*
  *Example: `"example_string"`*

- **statusTexts** (array) *optional*
  Status codes translation table.
  
  The list of codes is complete according to the
  {@link https://www.iana.org/assignments/http-status-codes/http-status-codes.xhtml Hypertext Transfer Protocol (HTTP) Status Code Registry}
  (last updated 2021-10-01).
  
  Unless otherwise noted, the status code is defined in RFC2616.
  *Example: `[]`*


---

### GET /media

Search specifically for media files

Performs targeted media search with support for media type filtering (image, video, audio).
Returns structured media results with pagination.

#### Parameters

- **request** (Symfony\Component\HttpFoundation\Request)

#### Response

**JsonResponse**

Response represents an HTTP response in JSON format.

Note that this class does not force the returned JSON content to be an
object. It is however recommended that you do return an object as it
protects yourself against XSSI and JSON-JavaScript Hijacking.

Properties:

- **data** (mixed)
  *Example: `null`*

- **callback** (string) *optional*
  *Example: `"example_string"`*

- **encodingOptions** (int) *optional*
  *Example: `42`*

- **headers** (Symfony\Component\HttpFoundation\ResponseHeaderBag)
  *Example: `null`*

- **content** (string)
  *Example: `"example_string"`*

- **version** (string)
  *Example: `"example_string"`*

- **statusCode** (int)
  *Example: `42`*

- **statusText** (string)
  *Example: `"example_string"`*

- **charset** (string) *optional*
  *Example: `"example_string"`*

- **statusTexts** (array) *optional*
  Status codes translation table.
  
  The list of codes is complete according to the
  {@link https://www.iana.org/assignments/http-status-codes/http-status-codes.xhtml Hypertext Transfer Protocol (HTTP) Status Code Registry}
  (last updated 2021-10-01).
  
  Unless otherwise noted, the status code is defined in RFC2616.
  *Example: `[]`*


---

### GET /projects

Search specifically for user projects

Performs targeted project search within the user's own projects.
Returns structured project results with pagination.

#### Parameters

- **request** (Symfony\Component\HttpFoundation\Request)

#### Response

**JsonResponse**

Response represents an HTTP response in JSON format.

Note that this class does not force the returned JSON content to be an
object. It is however recommended that you do return an object as it
protects yourself against XSSI and JSON-JavaScript Hijacking.

Properties:

- **data** (mixed)
  *Example: `null`*

- **callback** (string) *optional*
  *Example: `"example_string"`*

- **encodingOptions** (int) *optional*
  *Example: `42`*

- **headers** (Symfony\Component\HttpFoundation\ResponseHeaderBag)
  *Example: `null`*

- **content** (string)
  *Example: `"example_string"`*

- **version** (string)
  *Example: `"example_string"`*

- **statusCode** (int)
  *Example: `42`*

- **statusText** (string)
  *Example: `"example_string"`*

- **charset** (string) *optional*
  *Example: `"example_string"`*

- **statusTexts** (array) *optional*
  Status codes translation table.
  
  The list of codes is complete according to the
  {@link https://www.iana.org/assignments/http-status-codes/http-status-codes.xhtml Hypertext Transfer Protocol (HTTP) Status Code Registry}
  (last updated 2021-10-01).
  
  Unless otherwise noted, the status code is defined in RFC2616.
  *Example: `[]`*


---

### GET /suggestions

Get search suggestions based on user query

Returns search suggestions to help users find relevant content.
Used for autocomplete and search assistance features.

#### Parameters

- **request** (Symfony\Component\HttpFoundation\Request)

#### Response

**JsonResponse**

Response represents an HTTP response in JSON format.

Note that this class does not force the returned JSON content to be an
object. It is however recommended that you do return an object as it
protects yourself against XSSI and JSON-JavaScript Hijacking.

Properties:

- **data** (mixed)
  *Example: `null`*

- **callback** (string) *optional*
  *Example: `"example_string"`*

- **encodingOptions** (int) *optional*
  *Example: `42`*

- **headers** (Symfony\Component\HttpFoundation\ResponseHeaderBag)
  *Example: `null`*

- **content** (string)
  *Example: `"example_string"`*

- **version** (string)
  *Example: `"example_string"`*

- **statusCode** (int)
  *Example: `42`*

- **statusText** (string)
  *Example: `"example_string"`*

- **charset** (string) *optional*
  *Example: `"example_string"`*

- **statusTexts** (array) *optional*
  Status codes translation table.
  
  The list of codes is complete according to the
  {@link https://www.iana.org/assignments/http-status-codes/http-status-codes.xhtml Hypertext Transfer Protocol (HTTP) Status Code Registry}
  (last updated 2021-10-01).
  
  Unless otherwise noted, the status code is defined in RFC2616.
  *Example: `[]`*


---

### GET /templates

Search specifically for templates

Performs targeted template search with support for category and tag filtering.
Returns structured template results with pagination.

#### Parameters

- **request** (Symfony\Component\HttpFoundation\Request)

#### Response

**JsonResponse**

Response represents an HTTP response in JSON format.

Note that this class does not force the returned JSON content to be an
object. It is however recommended that you do return an object as it
protects yourself against XSSI and JSON-JavaScript Hijacking.

Properties:

- **data** (mixed)
  *Example: `null`*

- **callback** (string) *optional*
  *Example: `"example_string"`*

- **encodingOptions** (int) *optional*
  *Example: `42`*

- **headers** (Symfony\Component\HttpFoundation\ResponseHeaderBag)
  *Example: `null`*

- **content** (string)
  *Example: `"example_string"`*

- **version** (string)
  *Example: `"example_string"`*

- **statusCode** (int)
  *Example: `42`*

- **statusText** (string)
  *Example: `"example_string"`*

- **charset** (string) *optional*
  *Example: `"example_string"`*

- **statusTexts** (array) *optional*
  Status codes translation table.
  
  The list of codes is complete according to the
  {@link https://www.iana.org/assignments/http-status-codes/http-status-codes.xhtml Hypertext Transfer Protocol (HTTP) Status Code Registry}
  (last updated 2021-10-01).
  
  Unless otherwise noted, the status code is defined in RFC2616.
  *Example: `[]`*


---

## PluginController

Plugin Controller

Manages the plugin system including plugin registration, approval, installation, and management.
Handles plugin file uploads, metadata management, and lifecycle operations.
Provides marketplace functionality for plugin discovery and category management.
Includes admin approval workflow and user plugin management features.

### GET 

Retrieve paginated list of plugins with filtering options

Supports filtering by category, search terms, status, and sorting.
Returns paginated results with plugin metadata.

#### Parameters

- **request** (Symfony\Component\HttpFoundation\Request)

#### Response

**JsonResponse**

Response represents an HTTP response in JSON format.

Note that this class does not force the returned JSON content to be an
object. It is however recommended that you do return an object as it
protects yourself against XSSI and JSON-JavaScript Hijacking.

Properties:

- **data** (mixed)
  *Example: `null`*

- **callback** (string) *optional*
  *Example: `"example_string"`*

- **encodingOptions** (int) *optional*
  *Example: `42`*

- **headers** (Symfony\Component\HttpFoundation\ResponseHeaderBag)
  *Example: `null`*

- **content** (string)
  *Example: `"example_string"`*

- **version** (string)
  *Example: `"example_string"`*

- **statusCode** (int)
  *Example: `42`*

- **statusText** (string)
  *Example: `"example_string"`*

- **charset** (string) *optional*
  *Example: `"example_string"`*

- **statusTexts** (array) *optional*
  Status codes translation table.
  
  The list of codes is complete according to the
  {@link https://www.iana.org/assignments/http-status-codes/http-status-codes.xhtml Hypertext Transfer Protocol (HTTP) Status Code Registry}
  (last updated 2021-10-01).
  
  Unless otherwise noted, the status code is defined in RFC2616.
  *Example: `[]`*


---

### POST 

**Security:** IsGranted

Create a new plugin

Creates a new plugin with the provided metadata. The plugin is initially
set to pending status and requires admin approval before becoming available.

#### Request Body

**CreatePluginRequestDTO**

Data Transfer Object for plugin creation requests.

Handles the submission of new plugins to the platform's plugin
system. Contains all necessary metadata and configuration for
plugin registration, validation, and eventual approval process.
Used in the plugin management system to onboard new extensions.

Properties:

- **name** (string) *NotBlank(message: 'Plugin name is required'), Length(min: 2, max: 100, minMessage: 'Plugin name must be at least 2 characters long', maxMessage: 'Plugin name cannot exceed 100 characters')*
  Display name of the plugin.
  
  Human-readable name shown in the plugin marketplace
  and installation interface. Must be descriptive and
  unique to help users identify the plugin's purpose.
  *Example: `"example_string"`*

- **description** (string) *NotBlank(message: 'Plugin description is required'), Length(min: 10, max: 1000, minMessage: 'Plugin description must be at least 10 characters long', maxMessage: 'Plugin description cannot exceed 1000 characters')*
  Detailed description of the plugin's functionality.
  
  Comprehensive explanation of what the plugin does,
  its features, and how users can benefit from it.
  Displayed in the plugin marketplace and during installation.
  *Example: `"example_string"`*

- **categories** (array) *NotBlank(message: 'Categories are required'), Count(min: 1, max: 5, minMessage: 'At least one category is required', maxMessage: 'Cannot exceed 5 categories'), All([Symfony\Component\Validator\Constraints\NotBlank, Symfony\Component\Validator\Constraints\Length])*
  Categories that classify the plugin's functionality.
  
  Array of category names that help users discover the plugin
  through marketplace browsing and filtering. Common categories
  include 'Design Tools', 'Export', 'Templates', 'Media', etc.
  *Example: `[]`*

- **version** (string) *NotBlank(message: 'Plugin version is required'), Regex(pattern: '/^\d+\.\d+\.\d+(-[a-zA-Z0-9-]+)?$/', message: 'Version must follow semantic versioning (e.g., 1.0.0)')*
  Semantic version number of the plugin.
  
  Version identifier following semantic versioning (semver)
  format (e.g., "1.0.0" or "2.1.3-beta"). Used for update
  management and compatibility checking.
  *Example: `"example_string"`*

- **permissions** (array) *NotBlank(message: 'Permissions are required'), Count(min: 1, minMessage: 'At least one permission is required'), All([Symfony\Component\Validator\Constraints\Choice])*
  Required permissions for the plugin to function.
  
  Array of permission types that the plugin needs to access
  platform features. Used for security validation and to
  inform users about what the plugin can access.
  
  Available permissions:
  - 'editor': Access to design editor APIs
  - 'filesystem': File system read/write access
  - 'network': Network/HTTP request capabilities
  - 'clipboard': Clipboard read/write access
  - 'notifications': Push notification capabilities
  *Example: `[]`*

- **manifest** (array) *NotBlank(message: 'Plugin manifest is required'), Type(type: 'array', message: 'Manifest must be a valid array')*
  Plugin manifest configuration.
  
  JSON-formatted configuration containing plugin metadata,
  entry points, dependencies, and runtime configuration.
  Defines how the plugin integrates with the platform.
  *Example: `[]`*


#### Response

**JsonResponse**

Response represents an HTTP response in JSON format.

Note that this class does not force the returned JSON content to be an
object. It is however recommended that you do return an object as it
protects yourself against XSSI and JSON-JavaScript Hijacking.

Properties:

- **data** (mixed)
  *Example: `null`*

- **callback** (string) *optional*
  *Example: `"example_string"`*

- **encodingOptions** (int) *optional*
  *Example: `42`*

- **headers** (Symfony\Component\HttpFoundation\ResponseHeaderBag)
  *Example: `null`*

- **content** (string)
  *Example: `"example_string"`*

- **version** (string)
  *Example: `"example_string"`*

- **statusCode** (int)
  *Example: `42`*

- **statusText** (string)
  *Example: `"example_string"`*

- **charset** (string) *optional*
  *Example: `"example_string"`*

- **statusTexts** (array) *optional*
  Status codes translation table.
  
  The list of codes is complete according to the
  {@link https://www.iana.org/assignments/http-status-codes/http-status-codes.xhtml Hypertext Transfer Protocol (HTTP) Status Code Registry}
  (last updated 2021-10-01).
  
  Unless otherwise noted, the status code is defined in RFC2616.
  *Example: `[]`*


---

### GET /categories

Get available plugin categories

Returns a list of all available plugin categories for filtering
and classification purposes.

#### Response

**JsonResponse**

Response represents an HTTP response in JSON format.

Note that this class does not force the returned JSON content to be an
object. It is however recommended that you do return an object as it
protects yourself against XSSI and JSON-JavaScript Hijacking.

Properties:

- **data** (mixed)
  *Example: `null`*

- **callback** (string) *optional*
  *Example: `"example_string"`*

- **encodingOptions** (int) *optional*
  *Example: `42`*

- **headers** (Symfony\Component\HttpFoundation\ResponseHeaderBag)
  *Example: `null`*

- **content** (string)
  *Example: `"example_string"`*

- **version** (string)
  *Example: `"example_string"`*

- **statusCode** (int)
  *Example: `42`*

- **statusText** (string)
  *Example: `"example_string"`*

- **charset** (string) *optional*
  *Example: `"example_string"`*

- **statusTexts** (array) *optional*
  Status codes translation table.
  
  The list of codes is complete according to the
  {@link https://www.iana.org/assignments/http-status-codes/http-status-codes.xhtml Hypertext Transfer Protocol (HTTP) Status Code Registry}
  (last updated 2021-10-01).
  
  Unless otherwise noted, the status code is defined in RFC2616.
  *Example: `[]`*


---

### GET /my-plugins

**Security:** IsGranted

Get current user's plugins

Returns paginated list of plugins created by the authenticated user,
including all statuses (pending, approved, rejected).

#### Parameters

- **request** (Symfony\Component\HttpFoundation\Request)

#### Response

**JsonResponse**

Response represents an HTTP response in JSON format.

Note that this class does not force the returned JSON content to be an
object. It is however recommended that you do return an object as it
protects yourself against XSSI and JSON-JavaScript Hijacking.

Properties:

- **data** (mixed)
  *Example: `null`*

- **callback** (string) *optional*
  *Example: `"example_string"`*

- **encodingOptions** (int) *optional*
  *Example: `42`*

- **headers** (Symfony\Component\HttpFoundation\ResponseHeaderBag)
  *Example: `null`*

- **content** (string)
  *Example: `"example_string"`*

- **version** (string)
  *Example: `"example_string"`*

- **statusCode** (int)
  *Example: `42`*

- **statusText** (string)
  *Example: `"example_string"`*

- **charset** (string) *optional*
  *Example: `"example_string"`*

- **statusTexts** (array) *optional*
  Status codes translation table.
  
  The list of codes is complete according to the
  {@link https://www.iana.org/assignments/http-status-codes/http-status-codes.xhtml Hypertext Transfer Protocol (HTTP) Status Code Registry}
  (last updated 2021-10-01).
  
  Unless otherwise noted, the status code is defined in RFC2616.
  *Example: `[]`*


---

### GET /{id}

Retrieve detailed information about a specific plugin

Returns comprehensive plugin details including manifest, permissions,
and review information. Access is restricted based on plugin status and user role.

#### Parameters

- **plugin** (App\Entity\Plugin)

#### Response

**JsonResponse**

Response represents an HTTP response in JSON format.

Note that this class does not force the returned JSON content to be an
object. It is however recommended that you do return an object as it
protects yourself against XSSI and JSON-JavaScript Hijacking.

Properties:

- **data** (mixed)
  *Example: `null`*

- **callback** (string) *optional*
  *Example: `"example_string"`*

- **encodingOptions** (int) *optional*
  *Example: `42`*

- **headers** (Symfony\Component\HttpFoundation\ResponseHeaderBag)
  *Example: `null`*

- **content** (string)
  *Example: `"example_string"`*

- **version** (string)
  *Example: `"example_string"`*

- **statusCode** (int)
  *Example: `42`*

- **statusText** (string)
  *Example: `"example_string"`*

- **charset** (string) *optional*
  *Example: `"example_string"`*

- **statusTexts** (array) *optional*
  Status codes translation table.
  
  The list of codes is complete according to the
  {@link https://www.iana.org/assignments/http-status-codes/http-status-codes.xhtml Hypertext Transfer Protocol (HTTP) Status Code Registry}
  (last updated 2021-10-01).
  
  Unless otherwise noted, the status code is defined in RFC2616.
  *Example: `[]`*


---

### PUT /{id}

**Security:** IsGranted

Update an existing plugin

Updates plugin metadata. Only the plugin developer or admin can perform updates.
Admin users can also modify the plugin status.

#### Parameters

- **plugin** (App\Entity\Plugin)

#### Request Body

**UpdatePluginRequestDTO**

Data Transfer Object for plugin update requests.

Handles partial updates to existing plugins in the platform's
plugin system. All fields are optional and null values indicate
no change should be made. Used for both plugin developer updates
and administrative status changes.

Properties:

- **name** (string) *optional* *Length(min: 2, max: 100, minMessage: 'Plugin name must be at least 2 characters long', maxMessage: 'Plugin name cannot exceed 100 characters')*
  Updated display name of the plugin.
  
  If provided, replaces the current plugin name. Must be
  descriptive and unique. Null indicates no change.
  *Example: `"example_string"`*

- **description** (string) *optional* *Length(min: 10, max: 1000, minMessage: 'Plugin description must be at least 10 characters long', maxMessage: 'Plugin description cannot exceed 1000 characters')*
  Updated description of the plugin's functionality.
  
  If provided, replaces the current description. Should
  comprehensively explain the plugin's features and benefits.
  Null indicates no change.
  *Example: `"example_string"`*

- **categories** (array) *optional* *Count(min: 1, max: 5, minMessage: 'At least one category is required', maxMessage: 'Cannot exceed 5 categories'), All([Symfony\Component\Validator\Constraints\NotBlank, Symfony\Component\Validator\Constraints\Length])*
  Updated categories for plugin classification.
  
  If provided, replaces the current category set. Categories
  help users discover plugins through marketplace filtering.
  Null indicates no change.
  *Example: `[]`*

- **version** (string) *optional* *Regex(pattern: '/^\d+\.\d+\.\d+(-[a-zA-Z0-9-]+)?$/', message: 'Version must follow semantic versioning (e.g., 1.0.0)')*
  Updated semantic version number.
  
  If provided, updates the plugin version. Must follow
  semantic versioning format. Used for update management.
  Null indicates no change.
  *Example: `"example_string"`*

- **permissions** (array) *optional* *All([Symfony\Component\Validator\Constraints\Choice])*
  Updated permission requirements.
  
  If provided, replaces the current permission set. Each
  permission grants access to specific platform features.
  Null indicates no change.
  *Example: `[]`*

- **manifest** (array) *optional* *Type(type: 'array', message: 'Manifest must be a valid array')*
  Updated plugin manifest configuration.
  
  If provided, replaces the current manifest. Contains
  plugin metadata, entry points, and runtime configuration.
  Null indicates no change.
  *Example: `[]`*

- **status** (string) *optional* *Choice(choices: ['pending', 'approved', 'rejected'], message: 'Invalid status. Must be pending, approved, or rejected')*
  Updated approval status for the plugin.
  
  If provided, changes the plugin's approval status in the
  marketplace. Typically used by administrators for plugin
  review and approval workflows. Null indicates no change.
  
  Available statuses:
  - 'pending': Awaiting review
  - 'approved': Available in marketplace
  - 'rejected': Not approved for marketplace
  *Example: `pending`*


#### Response

**JsonResponse**

Response represents an HTTP response in JSON format.

Note that this class does not force the returned JSON content to be an
object. It is however recommended that you do return an object as it
protects yourself against XSSI and JSON-JavaScript Hijacking.

Properties:

- **data** (mixed)
  *Example: `null`*

- **callback** (string) *optional*
  *Example: `"example_string"`*

- **encodingOptions** (int) *optional*
  *Example: `42`*

- **headers** (Symfony\Component\HttpFoundation\ResponseHeaderBag)
  *Example: `null`*

- **content** (string)
  *Example: `"example_string"`*

- **version** (string)
  *Example: `"example_string"`*

- **statusCode** (int)
  *Example: `42`*

- **statusText** (string)
  *Example: `"example_string"`*

- **charset** (string) *optional*
  *Example: `"example_string"`*

- **statusTexts** (array) *optional*
  Status codes translation table.
  
  The list of codes is complete according to the
  {@link https://www.iana.org/assignments/http-status-codes/http-status-codes.xhtml Hypertext Transfer Protocol (HTTP) Status Code Registry}
  (last updated 2021-10-01).
  
  Unless otherwise noted, the status code is defined in RFC2616.
  *Example: `[]`*


---

### DELETE /{id}

**Security:** IsGranted

Delete a plugin

Permanently removes a plugin from the system. Only the plugin developer
or administrators can delete plugins. This action is irreversible.

#### Parameters

- **plugin** (App\Entity\Plugin)

#### Response

**JsonResponse**

Response represents an HTTP response in JSON format.

Note that this class does not force the returned JSON content to be an
object. It is however recommended that you do return an object as it
protects yourself against XSSI and JSON-JavaScript Hijacking.

Properties:

- **data** (mixed)
  *Example: `null`*

- **callback** (string) *optional*
  *Example: `"example_string"`*

- **encodingOptions** (int) *optional*
  *Example: `42`*

- **headers** (Symfony\Component\HttpFoundation\ResponseHeaderBag)
  *Example: `null`*

- **content** (string)
  *Example: `"example_string"`*

- **version** (string)
  *Example: `"example_string"`*

- **statusCode** (int)
  *Example: `42`*

- **statusText** (string)
  *Example: `"example_string"`*

- **charset** (string) *optional*
  *Example: `"example_string"`*

- **statusTexts** (array) *optional*
  Status codes translation table.
  
  The list of codes is complete according to the
  {@link https://www.iana.org/assignments/http-status-codes/http-status-codes.xhtml Hypertext Transfer Protocol (HTTP) Status Code Registry}
  (last updated 2021-10-01).
  
  Unless otherwise noted, the status code is defined in RFC2616.
  *Example: `[]`*


---

### POST /{id}/approve

**Security:** IsGranted

Approve a plugin (Admin only)

Changes plugin status to approved, making it available in the marketplace.
Records approval timestamp and reviewing administrator.

#### Parameters

- **plugin** (App\Entity\Plugin)

#### Response

**JsonResponse**

Response represents an HTTP response in JSON format.

Note that this class does not force the returned JSON content to be an
object. It is however recommended that you do return an object as it
protects yourself against XSSI and JSON-JavaScript Hijacking.

Properties:

- **data** (mixed)
  *Example: `null`*

- **callback** (string) *optional*
  *Example: `"example_string"`*

- **encodingOptions** (int) *optional*
  *Example: `42`*

- **headers** (Symfony\Component\HttpFoundation\ResponseHeaderBag)
  *Example: `null`*

- **content** (string)
  *Example: `"example_string"`*

- **version** (string)
  *Example: `"example_string"`*

- **statusCode** (int)
  *Example: `42`*

- **statusText** (string)
  *Example: `"example_string"`*

- **charset** (string) *optional*
  *Example: `"example_string"`*

- **statusTexts** (array) *optional*
  Status codes translation table.
  
  The list of codes is complete according to the
  {@link https://www.iana.org/assignments/http-status-codes/http-status-codes.xhtml Hypertext Transfer Protocol (HTTP) Status Code Registry}
  (last updated 2021-10-01).
  
  Unless otherwise noted, the status code is defined in RFC2616.
  *Example: `[]`*


---

### POST /{id}/install

**Security:** IsGranted

Install a plugin for the current user

Installs an approved plugin for the authenticated user. Increments the
installation count and handles plugin registration logic.

#### Parameters

- **plugin** (App\Entity\Plugin)

#### Response

**JsonResponse**

Response represents an HTTP response in JSON format.

Note that this class does not force the returned JSON content to be an
object. It is however recommended that you do return an object as it
protects yourself against XSSI and JSON-JavaScript Hijacking.

Properties:

- **data** (mixed)
  *Example: `null`*

- **callback** (string) *optional*
  *Example: `"example_string"`*

- **encodingOptions** (int) *optional*
  *Example: `42`*

- **headers** (Symfony\Component\HttpFoundation\ResponseHeaderBag)
  *Example: `null`*

- **content** (string)
  *Example: `"example_string"`*

- **version** (string)
  *Example: `"example_string"`*

- **statusCode** (int)
  *Example: `42`*

- **statusText** (string)
  *Example: `"example_string"`*

- **charset** (string) *optional*
  *Example: `"example_string"`*

- **statusTexts** (array) *optional*
  Status codes translation table.
  
  The list of codes is complete according to the
  {@link https://www.iana.org/assignments/http-status-codes/http-status-codes.xhtml Hypertext Transfer Protocol (HTTP) Status Code Registry}
  (last updated 2021-10-01).
  
  Unless otherwise noted, the status code is defined in RFC2616.
  *Example: `[]`*


---

### POST /{id}/reject

**Security:** IsGranted

Reject a plugin (Admin only)

Changes plugin status to rejected with a provided reason.
Records rejection timestamp and reviewing admin.

#### Parameters

- **plugin** (App\Entity\Plugin)

#### Request Body

**RejectPluginRequestDTO**

Properties:

- **reason** (string) *NotBlank(message: 'Rejection reason is required'), Length(min: 10, max: 500, minMessage: 'Rejection reason must be at least 10 characters long', maxMessage: 'Rejection reason cannot exceed 500 characters')*
  *Example: `"example_string"`*


#### Response

**JsonResponse**

Response represents an HTTP response in JSON format.

Note that this class does not force the returned JSON content to be an
object. It is however recommended that you do return an object as it
protects yourself against XSSI and JSON-JavaScript Hijacking.

Properties:

- **data** (mixed)
  *Example: `null`*

- **callback** (string) *optional*
  *Example: `"example_string"`*

- **encodingOptions** (int) *optional*
  *Example: `42`*

- **headers** (Symfony\Component\HttpFoundation\ResponseHeaderBag)
  *Example: `null`*

- **content** (string)
  *Example: `"example_string"`*

- **version** (string)
  *Example: `"example_string"`*

- **statusCode** (int)
  *Example: `42`*

- **statusText** (string)
  *Example: `"example_string"`*

- **charset** (string) *optional*
  *Example: `"example_string"`*

- **statusTexts** (array) *optional*
  Status codes translation table.
  
  The list of codes is complete according to the
  {@link https://www.iana.org/assignments/http-status-codes/http-status-codes.xhtml Hypertext Transfer Protocol (HTTP) Status Code Registry}
  (last updated 2021-10-01).
  
  Unless otherwise noted, the status code is defined in RFC2616.
  *Example: `[]`*


---

### POST /{id}/uninstall

**Security:** IsGranted

Uninstall a plugin for the current user

Removes a plugin from the user's installed plugins and cleans up
associated data and files.

#### Parameters

- **plugin** (App\Entity\Plugin)

#### Response

**JsonResponse**

Response represents an HTTP response in JSON format.

Note that this class does not force the returned JSON content to be an
object. It is however recommended that you do return an object as it
protects yourself against XSSI and JSON-JavaScript Hijacking.

Properties:

- **data** (mixed)
  *Example: `null`*

- **callback** (string) *optional*
  *Example: `"example_string"`*

- **encodingOptions** (int) *optional*
  *Example: `42`*

- **headers** (Symfony\Component\HttpFoundation\ResponseHeaderBag)
  *Example: `null`*

- **content** (string)
  *Example: `"example_string"`*

- **version** (string)
  *Example: `"example_string"`*

- **statusCode** (int)
  *Example: `42`*

- **statusText** (string)
  *Example: `"example_string"`*

- **charset** (string) *optional*
  *Example: `"example_string"`*

- **statusTexts** (array) *optional*
  Status codes translation table.
  
  The list of codes is complete according to the
  {@link https://www.iana.org/assignments/http-status-codes/http-status-codes.xhtml Hypertext Transfer Protocol (HTTP) Status Code Registry}
  (last updated 2021-10-01).
  
  Unless otherwise noted, the status code is defined in RFC2616.
  *Example: `[]`*


---

### POST /{id}/upload-file

**Security:** IsGranted

Upload a plugin file

Allows plugin developers to upload plugin files (ZIP format).
Performs validation and stores the file securely.

#### Parameters

- **plugin** (App\Entity\Plugin)

#### Request Body

**UploadPluginFileRequestDTO**

Data Transfer Object for plugin file upload requests.

Handles validation and encapsulation of plugin file upload data including
file type validation, size constraints, and security checks for plugin files.

Properties:

- **file** (Symfony\Component\HttpFoundation\File\UploadedFile) *optional* *NotNull(message: 'Plugin file is required'), File(maxSize: '50M', maxSizeMessage: 'Plugin file size cannot exceed 50MB', mimeTypes: ['application/zip', 'application/x-zip-compressed', 'application/x-zip'], mimeTypesMessage: 'Plugin file must be a valid ZIP archive')*
  Data Transfer Object for plugin file upload requests.
  
  Handles validation and encapsulation of plugin file upload data including
  file type validation, size constraints, and security checks for plugin files.
  *Example: `null`*


#### Response

**JsonResponse**

Response represents an HTTP response in JSON format.

Note that this class does not force the returned JSON content to be an
object. It is however recommended that you do return an object as it
protects yourself against XSSI and JSON-JavaScript Hijacking.

Properties:

- **data** (mixed)
  *Example: `null`*

- **callback** (string) *optional*
  *Example: `"example_string"`*

- **encodingOptions** (int) *optional*
  *Example: `42`*

- **headers** (Symfony\Component\HttpFoundation\ResponseHeaderBag)
  *Example: `null`*

- **content** (string)
  *Example: `"example_string"`*

- **version** (string)
  *Example: `"example_string"`*

- **statusCode** (int)
  *Example: `42`*

- **statusText** (string)
  *Example: `"example_string"`*

- **charset** (string) *optional*
  *Example: `"example_string"`*

- **statusTexts** (array) *optional*
  Status codes translation table.
  
  The list of codes is complete according to the
  {@link https://www.iana.org/assignments/http-status-codes/http-status-codes.xhtml Hypertext Transfer Protocol (HTTP) Status Code Registry}
  (last updated 2021-10-01).
  
  Unless otherwise noted, the status code is defined in RFC2616.
  *Example: `[]`*


---

## UserController

User Controller

Manages user account operations including profile management, avatar uploads,
password changes, privacy settings, and subscription information.
Handles personal data export/download and account deletion functionality.
All endpoints require authentication and operate on the current user's data.

### POST /avatar

Upload and update user avatar image

Handles avatar file upload, validation, and updates user profile.
Automatically removes old avatar file and returns new avatar URL.

#### Request Body

**UploadAvatarRequestDTO**

Data Transfer Object for avatar upload requests.

Handles validation and encapsulation of avatar file upload data including
file type validation, size constraints, and security checks.

Properties:

- **avatar** (Symfony\Component\HttpFoundation\File\UploadedFile) *optional* *NotNull(message: 'Avatar file is required'), File(maxSize: '5M', maxSizeMessage: 'Avatar file size cannot exceed 5MB', mimeTypes: ['image/jpeg', 'image/png', 'image/gif', 'image/webp'], mimeTypesMessage: 'Avatar must be a valid image file (JPEG, PNG, GIF, or WebP)')*
  Data Transfer Object for avatar upload requests.
  
  Handles validation and encapsulation of avatar file upload data including
  file type validation, size constraints, and security checks.
  *Example: `null`*


#### Response

**JsonResponse**

Response represents an HTTP response in JSON format.

Note that this class does not force the returned JSON content to be an
object. It is however recommended that you do return an object as it
protects yourself against XSSI and JSON-JavaScript Hijacking.

Properties:

- **data** (mixed)
  *Example: `null`*

- **callback** (string) *optional*
  *Example: `"example_string"`*

- **encodingOptions** (int) *optional*
  *Example: `42`*

- **headers** (Symfony\Component\HttpFoundation\ResponseHeaderBag)
  *Example: `null`*

- **content** (string)
  *Example: `"example_string"`*

- **version** (string)
  *Example: `"example_string"`*

- **statusCode** (int)
  *Example: `42`*

- **statusText** (string)
  *Example: `"example_string"`*

- **charset** (string) *optional*
  *Example: `"example_string"`*

- **statusTexts** (array) *optional*
  Status codes translation table.
  
  The list of codes is complete according to the
  {@link https://www.iana.org/assignments/http-status-codes/http-status-codes.xhtml Hypertext Transfer Protocol (HTTP) Status Code Registry}
  (last updated 2021-10-01).
  
  Unless otherwise noted, the status code is defined in RFC2616.
  *Example: `[]`*


---

### PUT /password

Change user password

Updates user password after validating current password and ensuring
new password meets security requirements including confirmation match.

#### Request Body

**ChangePasswordRequestDTO**

Data Transfer Object for password change requests.

Handles secure password updates for authenticated users.
Validates the current password before allowing the change
and ensures the new password meets security requirements
with confirmation validation.

Properties:

- **currentPassword** (string)
  User's current password for verification.
  
  Required to verify the user's identity before allowing
  a password change. Must match the currently stored
  password hash for the authenticated user.
  *Example: `"example_string"`*

- **newPassword** (string)
  New password that will replace the current one.
  
  Must meet security requirements: minimum 8 characters,
  containing at least one lowercase letter, one uppercase
  letter, and one number. Will be hashed before storage.
  *Example: `"example_string"`*

- **confirmPassword** (string)
  Confirmation of the new password.
  
  Must exactly match the newPassword field to prevent
  accidental password typos during the change process.
  Validated through the isPasswordConfirmed() method.
  *Example: `"example_string"`*


#### Response

**JsonResponse**

Response represents an HTTP response in JSON format.

Note that this class does not force the returned JSON content to be an
object. It is however recommended that you do return an object as it
protects yourself against XSSI and JSON-JavaScript Hijacking.

Properties:

- **data** (mixed)
  *Example: `null`*

- **callback** (string) *optional*
  *Example: `"example_string"`*

- **encodingOptions** (int) *optional*
  *Example: `42`*

- **headers** (Symfony\Component\HttpFoundation\ResponseHeaderBag)
  *Example: `null`*

- **content** (string)
  *Example: `"example_string"`*

- **version** (string)
  *Example: `"example_string"`*

- **statusCode** (int)
  *Example: `42`*

- **statusText** (string)
  *Example: `"example_string"`*

- **charset** (string) *optional*
  *Example: `"example_string"`*

- **statusTexts** (array) *optional*
  Status codes translation table.
  
  The list of codes is complete according to the
  {@link https://www.iana.org/assignments/http-status-codes/http-status-codes.xhtml Hypertext Transfer Protocol (HTTP) Status Code Registry}
  (last updated 2021-10-01).
  
  Unless otherwise noted, the status code is defined in RFC2616.
  *Example: `[]`*


---

### GET /profile

Get current user's profile information

Returns comprehensive profile data including personal information,
settings, and account details for the authenticated user.

#### Response

**JsonResponse**

Response represents an HTTP response in JSON format.

Note that this class does not force the returned JSON content to be an
object. It is however recommended that you do return an object as it
protects yourself against XSSI and JSON-JavaScript Hijacking.

Properties:

- **data** (mixed)
  *Example: `null`*

- **callback** (string) *optional*
  *Example: `"example_string"`*

- **encodingOptions** (int) *optional*
  *Example: `42`*

- **headers** (Symfony\Component\HttpFoundation\ResponseHeaderBag)
  *Example: `null`*

- **content** (string)
  *Example: `"example_string"`*

- **version** (string)
  *Example: `"example_string"`*

- **statusCode** (int)
  *Example: `42`*

- **statusText** (string)
  *Example: `"example_string"`*

- **charset** (string) *optional*
  *Example: `"example_string"`*

- **statusTexts** (array) *optional*
  Status codes translation table.
  
  The list of codes is complete according to the
  {@link https://www.iana.org/assignments/http-status-codes/http-status-codes.xhtml Hypertext Transfer Protocol (HTTP) Status Code Registry}
  (last updated 2021-10-01).
  
  Unless otherwise noted, the status code is defined in RFC2616.
  *Example: `[]`*


---

### PUT /profile

Update current user's profile information

Updates user profile data including personal information, professional details,
and account preferences. Uses comprehensive validation and returns updated profile.

#### Request Body

**UpdateProfileRequestDTO**

Data Transfer Object for comprehensive profile update requests



Properties:

- **firstName** (string)
  *Example: `"example_string"`*

- **lastName** (string)
  *Example: `"example_string"`*

- **username** (string)
  *Example: `"example_string"`*

- **email** (string)
  *Example: `"example_string"`*

- **jobTitle** (string)
  *Example: `"example_string"`*

- **company** (string)
  *Example: `"example_string"`*

- **website** (string)
  *Example: `"example_string"`*

- **portfolio** (string)
  *Example: `"example_string"`*

- **bio** (string)
  *Example: `"example_string"`*

- **socialLinks** (array)
  *Example: `[]`*

- **timezone** (string)
  *Example: `"example_string"`*

- **language** (string)
  *Example: `"example_string"`*


#### Response

**JsonResponse**

Response represents an HTTP response in JSON format.

Note that this class does not force the returned JSON content to be an
object. It is however recommended that you do return an object as it
protects yourself against XSSI and JSON-JavaScript Hijacking.

Properties:

- **data** (mixed)
  *Example: `null`*

- **callback** (string) *optional*
  *Example: `"example_string"`*

- **encodingOptions** (int) *optional*
  *Example: `42`*

- **headers** (Symfony\Component\HttpFoundation\ResponseHeaderBag)
  *Example: `null`*

- **content** (string)
  *Example: `"example_string"`*

- **version** (string)
  *Example: `"example_string"`*

- **statusCode** (int)
  *Example: `42`*

- **statusText** (string)
  *Example: `"example_string"`*

- **charset** (string) *optional*
  *Example: `"example_string"`*

- **statusTexts** (array) *optional*
  Status codes translation table.
  
  The list of codes is complete according to the
  {@link https://www.iana.org/assignments/http-status-codes/http-status-codes.xhtml Hypertext Transfer Protocol (HTTP) Status Code Registry}
  (last updated 2021-10-01).
  
  Unless otherwise noted, the status code is defined in RFC2616.
  *Example: `[]`*


---

### DELETE /settings/privacy/delete

Delete user account and all associated data

Initiates account deletion process which removes all user data,
content, and associated resources permanently.

#### Response

**JsonResponse**

Response represents an HTTP response in JSON format.

Note that this class does not force the returned JSON content to be an
object. It is however recommended that you do return an object as it
protects yourself against XSSI and JSON-JavaScript Hijacking.

Properties:

- **data** (mixed)
  *Example: `null`*

- **callback** (string) *optional*
  *Example: `"example_string"`*

- **encodingOptions** (int) *optional*
  *Example: `42`*

- **headers** (Symfony\Component\HttpFoundation\ResponseHeaderBag)
  *Example: `null`*

- **content** (string)
  *Example: `"example_string"`*

- **version** (string)
  *Example: `"example_string"`*

- **statusCode** (int)
  *Example: `42`*

- **statusText** (string)
  *Example: `"example_string"`*

- **charset** (string) *optional*
  *Example: `"example_string"`*

- **statusTexts** (array) *optional*
  Status codes translation table.
  
  The list of codes is complete according to the
  {@link https://www.iana.org/assignments/http-status-codes/http-status-codes.xhtml Hypertext Transfer Protocol (HTTP) Status Code Registry}
  (last updated 2021-10-01).
  
  Unless otherwise noted, the status code is defined in RFC2616.
  *Example: `[]`*


---

### POST /settings/privacy/download

Request user data download for GDPR compliance

Initiates a background process to prepare comprehensive data export
for the user, including all personal data and content.

#### Response

**JsonResponse**

Response represents an HTTP response in JSON format.

Note that this class does not force the returned JSON content to be an
object. It is however recommended that you do return an object as it
protects yourself against XSSI and JSON-JavaScript Hijacking.

Properties:

- **data** (mixed)
  *Example: `null`*

- **callback** (string) *optional*
  *Example: `"example_string"`*

- **encodingOptions** (int) *optional*
  *Example: `42`*

- **headers** (Symfony\Component\HttpFoundation\ResponseHeaderBag)
  *Example: `null`*

- **content** (string)
  *Example: `"example_string"`*

- **version** (string)
  *Example: `"example_string"`*

- **statusCode** (int)
  *Example: `42`*

- **statusText** (string)
  *Example: `"example_string"`*

- **charset** (string) *optional*
  *Example: `"example_string"`*

- **statusTexts** (array) *optional*
  Status codes translation table.
  
  The list of codes is complete according to the
  {@link https://www.iana.org/assignments/http-status-codes/http-status-codes.xhtml Hypertext Transfer Protocol (HTTP) Status Code Registry}
  (last updated 2021-10-01).
  
  Unless otherwise noted, the status code is defined in RFC2616.
  *Example: `[]`*


---

### POST /settings/privacy/export

Export user data in portable format

Generates and returns comprehensive user data export including
all user content, settings, and account information.

#### Response

**JsonResponse**

Response represents an HTTP response in JSON format.

Note that this class does not force the returned JSON content to be an
object. It is however recommended that you do return an object as it
protects yourself against XSSI and JSON-JavaScript Hijacking.

Properties:

- **data** (mixed)
  *Example: `null`*

- **callback** (string) *optional*
  *Example: `"example_string"`*

- **encodingOptions** (int) *optional*
  *Example: `42`*

- **headers** (Symfony\Component\HttpFoundation\ResponseHeaderBag)
  *Example: `null`*

- **content** (string)
  *Example: `"example_string"`*

- **version** (string)
  *Example: `"example_string"`*

- **statusCode** (int)
  *Example: `42`*

- **statusText** (string)
  *Example: `"example_string"`*

- **charset** (string) *optional*
  *Example: `"example_string"`*

- **statusTexts** (array) *optional*
  Status codes translation table.
  
  The list of codes is complete according to the
  {@link https://www.iana.org/assignments/http-status-codes/http-status-codes.xhtml Hypertext Transfer Protocol (HTTP) Status Code Registry}
  (last updated 2021-10-01).
  
  Unless otherwise noted, the status code is defined in RFC2616.
  *Example: `[]`*


---

### GET /subscription

Get user subscription information

Returns current subscription details including plan type, billing status,
usage limits, and subscription features for the authenticated user.

#### Response

**JsonResponse**

Response represents an HTTP response in JSON format.

Note that this class does not force the returned JSON content to be an
object. It is however recommended that you do return an object as it
protects yourself against XSSI and JSON-JavaScript Hijacking.

Properties:

- **data** (mixed)
  *Example: `null`*

- **callback** (string) *optional*
  *Example: `"example_string"`*

- **encodingOptions** (int) *optional*
  *Example: `42`*

- **headers** (Symfony\Component\HttpFoundation\ResponseHeaderBag)
  *Example: `null`*

- **content** (string)
  *Example: `"example_string"`*

- **version** (string)
  *Example: `"example_string"`*

- **statusCode** (int)
  *Example: `42`*

- **statusText** (string)
  *Example: `"example_string"`*

- **charset** (string) *optional*
  *Example: `"example_string"`*

- **statusTexts** (array) *optional*
  Status codes translation table.
  
  The list of codes is complete according to the
  {@link https://www.iana.org/assignments/http-status-codes/http-status-codes.xhtml Hypertext Transfer Protocol (HTTP) Status Code Registry}
  (last updated 2021-10-01).
  
  Unless otherwise noted, the status code is defined in RFC2616.
  *Example: `[]`*


---


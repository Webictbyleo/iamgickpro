# Design Platform API

Comprehensive API for the modern web-based design platform

**Version:** 1.0.0

**Generated on:** 2025-06-01 22:07:44
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

*9 routes*

Media Controller

Manages media file operations including upload, retrieval, updating, and deletion.
Handles media search, duplication, stock media integration, and bulk operations.
All endpoints require authentication and enforce user ownership for security.

<!-- Route 1 -->
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

| Property | Type | Required | Description |
|----------|------|----------|-------------|
| `page` | `int` | No | Page number for pagination. Specifies which page of media results to return. Must be 1 or greater... *(Min: 1)* |
| `limit` | `int` | No | Number of media items per page. Controls how many media files are returned per page. Limited to a... *(Min: 1, Max: 50)* |
| `type` | `string` | No | Media type filter. Filters media by type (image, video, or audio). When null, all media types are... *(Choices: 'image', 'video', 'audio')* |
| `source` | `string` | No | Media source filter. Filters media by its source origin (upload for user uploads, or stock photo ... *(Choices: 'upload', 'unsplash', 'pexels', 'pixabay')* |
| `search` | `string` | No | Search query term. Text to search for in media file names, descriptions, and metadata. When null,... *(Max length: 255)* |
| `tags` | `string` | No | Comma-separated list of tags for filtering. Tags to filter media by. Multiple tags can be specifi... |


**Example Request:**

```json
{
    "page": 25,
    "limit": 20,
    "type": null,
    "source": null,
    "search": null,
    "tags": null
}
```

#### Response

**JsonResponse**

Response represents an HTTP response in JSON format.

Note that this class does not force the returned JSON content to be an
object. It is however recommended that you do return an object as it
protects yourself against XSSI and JSON-JavaScript Hijacking.

| Property | Type | Required | Description |
|----------|------|----------|-------------|
| `data` | `mixed` | Yes | No description available |
| `callback` | `string` | No | No description available |
| `encodingOptions` | `int` | No | No description available |
| `headers` | `Symfony\Component\HttpFoundation\ResponseHeaderBag` | Yes | No description available |
| `content` | `string` | Yes | No description available |
| `version` | `string` | Yes | No description available |
| `statusCode` | `int` | Yes | No description available |
| `statusText` | `string` | Yes | No description available |
| `charset` | `string` | No | No description available |
| `statusTexts` | `array` | No | Status codes translation table. The list of codes is complete according to the {@link https://www... |


**Example Response:**

```json
{
    "data": null,
    "callback": "example_string",
    "encodingOptions": 42,
    "headers": null,
    "content": "example_string",
    "version": "example_string",
    "statusCode": 42,
    "statusText": "active",
    "charset": "example_string",
    "statusTexts": [
        "example_item"
    ]
}
```

---

<!-- Route 2 -->
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

| Property | Type | Required | Description |
|----------|------|----------|-------------|
| `name` | `string` | Yes | Display name for the media item. This name is shown in the media library and used for searching. ... *(Required, Min length: 1, Max length: 255)* |
| `type` | `string` | Yes | Type of media content. Categorizes the media into broad types for filtering and appropriate handl... *(Required, Choices: 'image', 'video', 'audio')* |
| `mimeType` | `string` | Yes | MIME type of the media file. Specifies the exact format of the media file for proper handling by ... *(Required)* |
| `size` | `int` | Yes | File size in bytes. Used for storage quota management, upload progress, and performance optimizat... |
| `url` | `string` | Yes | Direct URL to access the media file. This is the primary URL used to display or download the medi... *(Required)* |
| `thumbnailUrl` | `string` | No | Optional URL to a thumbnail or preview image. Used for quick previews in the media library and la... |
| `width` | `int` | No | Width of the media in pixels (for visual media). Essential for layout calculations and aspect rat... |
| `height` | `int` | No | Height of the media in pixels (for visual media). Essential for layout calculations and aspect ra... |
| `duration` | `float` | No | Duration in seconds (for time-based media). Used for video and audio files to display playback le... |
| `source` | `string` | No | Source platform or service where the media originated. Tracks the origin of media for attribution... *(Choices: 'upload', 'unsplash', 'pexels', 'pixabay')* |
| `sourceId` | `string` | No | Unique identifier from the source platform. For stock photo services, this is their internal ID f... |
| `metadata` | `MediaMetadata` | No | Technical metadata about the media file. Contains detailed information about the media file inclu... |
| `tags` | `array` | No | Organizational tags for categorizing and searching media. Tags help users organize their media li... |
| `attribution` | `string` | No | Attribution text for the media creator. Required for some stock photo services and user-generated... |
| `license` | `string` | No | License type under which the media is distributed. Defines usage rights and restrictions for the ... |
| `isPremium` | `bool` | No | Whether this media requires a premium subscription to use. Premium media may have additional lice... |
| `isActive` | `bool` | No | Whether this media is currently active and available for use. Inactive media is hidden from searc... |


**Example Request:**

```json
{
    "name": "Example Name",
    "type": "example_type",
    "mimeType": "example_type",
    "size": 100,
    "url": "https:\/\/example.com",
    "thumbnailUrl": null,
    "width": null,
    "height": null,
    "duration": null,
    "source": "example_string",
    "sourceId": null,
    "metadata": null,
    "tags": null,
    "attribution": null,
    "license": null,
    "isPremium": true,
    "isActive": true
}
```

#### Response

**JsonResponse**

Response represents an HTTP response in JSON format.

Note that this class does not force the returned JSON content to be an
object. It is however recommended that you do return an object as it
protects yourself against XSSI and JSON-JavaScript Hijacking.

| Property | Type | Required | Description |
|----------|------|----------|-------------|
| `data` | `mixed` | Yes | No description available |
| `callback` | `string` | No | No description available |
| `encodingOptions` | `int` | No | No description available |
| `headers` | `Symfony\Component\HttpFoundation\ResponseHeaderBag` | Yes | No description available |
| `content` | `string` | Yes | No description available |
| `version` | `string` | Yes | No description available |
| `statusCode` | `int` | Yes | No description available |
| `statusText` | `string` | Yes | No description available |
| `charset` | `string` | No | No description available |
| `statusTexts` | `array` | No | Status codes translation table. The list of codes is complete according to the {@link https://www... |


**Example Response:**

```json
{
    "data": null,
    "callback": "example_string",
    "encodingOptions": 42,
    "headers": null,
    "content": "example_string",
    "version": "example_string",
    "statusCode": 42,
    "statusText": "active",
    "charset": "example_string",
    "statusTexts": [
        "example_item"
    ]
}
```

---

<!-- Route 3 -->
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

| Property | Type | Required | Description |
|----------|------|----------|-------------|
| `uuids` | `array` | Yes | Array of media file UUIDs to delete. Each UUID represents a media file that the user wants to del... *(Required, Required)* |


**Example Request:**

```json
{
    "uuids": [
        1,
        2,
        3
    ]
}
```

#### Response

**JsonResponse**

Response represents an HTTP response in JSON format.

Note that this class does not force the returned JSON content to be an
object. It is however recommended that you do return an object as it
protects yourself against XSSI and JSON-JavaScript Hijacking.

| Property | Type | Required | Description |
|----------|------|----------|-------------|
| `data` | `mixed` | Yes | No description available |
| `callback` | `string` | No | No description available |
| `encodingOptions` | `int` | No | No description available |
| `headers` | `Symfony\Component\HttpFoundation\ResponseHeaderBag` | Yes | No description available |
| `content` | `string` | Yes | No description available |
| `version` | `string` | Yes | No description available |
| `statusCode` | `int` | Yes | No description available |
| `statusText` | `string` | Yes | No description available |
| `charset` | `string` | No | No description available |
| `statusTexts` | `array` | No | Status codes translation table. The list of codes is complete according to the {@link https://www... |


**Example Response:**

```json
{
    "data": null,
    "callback": "example_string",
    "encodingOptions": 42,
    "headers": null,
    "content": "example_string",
    "version": "example_string",
    "statusCode": 42,
    "statusText": "active",
    "charset": "example_string",
    "statusTexts": [
        "example_item"
    ]
}
```

---

<!-- Route 4 -->
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

| Property | Type | Required | Description |
|----------|------|----------|-------------|
| `name` | `string` | No | Custom name for the duplicated media file. If provided, this will be used as the name for the dup... *(Max length: 255)* |


**Example Request:**

```json
{
    "name": null
}
```

#### Response

**JsonResponse**

Response represents an HTTP response in JSON format.

Note that this class does not force the returned JSON content to be an
object. It is however recommended that you do return an object as it
protects yourself against XSSI and JSON-JavaScript Hijacking.

| Property | Type | Required | Description |
|----------|------|----------|-------------|
| `data` | `mixed` | Yes | No description available |
| `callback` | `string` | No | No description available |
| `encodingOptions` | `int` | No | No description available |
| `headers` | `Symfony\Component\HttpFoundation\ResponseHeaderBag` | Yes | No description available |
| `content` | `string` | Yes | No description available |
| `version` | `string` | Yes | No description available |
| `statusCode` | `int` | Yes | No description available |
| `statusText` | `string` | Yes | No description available |
| `charset` | `string` | No | No description available |
| `statusTexts` | `array` | No | Status codes translation table. The list of codes is complete according to the {@link https://www... |


**Example Response:**

```json
{
    "data": null,
    "callback": "example_string",
    "encodingOptions": 42,
    "headers": null,
    "content": "example_string",
    "version": "example_string",
    "statusCode": 42,
    "statusText": "active",
    "charset": "example_string",
    "statusTexts": [
        "example_item"
    ]
}
```

---

<!-- Route 5 -->
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

| Property | Type | Required | Description |
|----------|------|----------|-------------|
| `page` | `int` | No | Page number for pagination. Specifies which page of media results to return. Must be 1 or greater... *(Min: 1)* |
| `limit` | `int` | No | Number of media items per page. Controls how many media files are returned per page. Limited to a... *(Min: 1, Max: 50)* |
| `type` | `string` | No | Media type filter. Filters media by type (image, video, or audio). When null, all media types are... *(Choices: 'image', 'video', 'audio')* |
| `source` | `string` | No | Media source filter. Filters media by its source origin (upload for user uploads, or stock photo ... *(Choices: 'upload', 'unsplash', 'pexels', 'pixabay')* |
| `search` | `string` | No | Search query term. Text to search for in media file names, descriptions, and metadata. When null,... *(Max length: 255)* |
| `tags` | `string` | No | Comma-separated list of tags for filtering. Tags to filter media by. Multiple tags can be specifi... |


**Example Request:**

```json
{
    "page": 25,
    "limit": 20,
    "type": null,
    "source": null,
    "search": null,
    "tags": null
}
```

#### Response

**JsonResponse**

Response represents an HTTP response in JSON format.

Note that this class does not force the returned JSON content to be an
object. It is however recommended that you do return an object as it
protects yourself against XSSI and JSON-JavaScript Hijacking.

| Property | Type | Required | Description |
|----------|------|----------|-------------|
| `data` | `mixed` | Yes | No description available |
| `callback` | `string` | No | No description available |
| `encodingOptions` | `int` | No | No description available |
| `headers` | `Symfony\Component\HttpFoundation\ResponseHeaderBag` | Yes | No description available |
| `content` | `string` | Yes | No description available |
| `version` | `string` | Yes | No description available |
| `statusCode` | `int` | Yes | No description available |
| `statusText` | `string` | Yes | No description available |
| `charset` | `string` | No | No description available |
| `statusTexts` | `array` | No | Status codes translation table. The list of codes is complete according to the {@link https://www... |


**Example Response:**

```json
{
    "data": null,
    "callback": "example_string",
    "encodingOptions": 42,
    "headers": null,
    "content": "example_string",
    "version": "example_string",
    "statusCode": 42,
    "statusText": "active",
    "charset": "example_string",
    "statusTexts": [
        "example_item"
    ]
}
```

---

<!-- Route 6 -->
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

| Property | Type | Required | Description |
|----------|------|----------|-------------|
| `query` | `string` | Yes | Search query for stock media. The search term to find relevant stock photos and videos from exter... *(Required, Min length: 1, Max length: 255)* |
| `page` | `int` | No | Page number for stock media results pagination. Specifies which page of stock media results to re... *(Min: 1)* |
| `limit` | `int` | No | Number of stock media items per page. Controls how many stock media items are requested from the ... *(Min: 1, Max: 50)* |
| `type` | `string` | No | Type of stock media to search for. Specifies whether to search for images or videos from the stoc... *(Choices: 'image', 'video')* |
| `source` | `string` | No | Stock media provider source. Specifies which external stock media provider to search. Each provid... *(Choices: 'unsplash', 'pexels', 'pixabay')* |


**Example Request:**

```json
{
    "query": "example_string",
    "page": 25,
    "limit": 20,
    "type": "example_type",
    "source": "example_string"
}
```

#### Response

**JsonResponse**

Response represents an HTTP response in JSON format.

Note that this class does not force the returned JSON content to be an
object. It is however recommended that you do return an object as it
protects yourself against XSSI and JSON-JavaScript Hijacking.

| Property | Type | Required | Description |
|----------|------|----------|-------------|
| `data` | `mixed` | Yes | No description available |
| `callback` | `string` | No | No description available |
| `encodingOptions` | `int` | No | No description available |
| `headers` | `Symfony\Component\HttpFoundation\ResponseHeaderBag` | Yes | No description available |
| `content` | `string` | Yes | No description available |
| `version` | `string` | Yes | No description available |
| `statusCode` | `int` | Yes | No description available |
| `statusText` | `string` | Yes | No description available |
| `charset` | `string` | No | No description available |
| `statusTexts` | `array` | No | Status codes translation table. The list of codes is complete according to the {@link https://www... |


**Example Response:**

```json
{
    "data": null,
    "callback": "example_string",
    "encodingOptions": 42,
    "headers": null,
    "content": "example_string",
    "version": "example_string",
    "statusCode": 42,
    "statusText": "active",
    "charset": "example_string",
    "statusTexts": [
        "example_item"
    ]
}
```

---

<!-- Route 7 -->
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

| Property | Type | Required | Description |
|----------|------|----------|-------------|
| `data` | `mixed` | Yes | No description available |
| `callback` | `string` | No | No description available |
| `encodingOptions` | `int` | No | No description available |
| `headers` | `Symfony\Component\HttpFoundation\ResponseHeaderBag` | Yes | No description available |
| `content` | `string` | Yes | No description available |
| `version` | `string` | Yes | No description available |
| `statusCode` | `int` | Yes | No description available |
| `statusText` | `string` | Yes | No description available |
| `charset` | `string` | No | No description available |
| `statusTexts` | `array` | No | Status codes translation table. The list of codes is complete according to the {@link https://www... |


**Example Response:**

```json
{
    "data": null,
    "callback": "example_string",
    "encodingOptions": 42,
    "headers": null,
    "content": "example_string",
    "version": "example_string",
    "statusCode": 42,
    "statusText": "active",
    "charset": "example_string",
    "statusTexts": [
        "example_item"
    ]
}
```

---

<!-- Route 8 -->
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

| Property | Type | Required | Description |
|----------|------|----------|-------------|
| `name` | `string` | No | Updated display name for the media item. If provided, replaces the current media name. Must be be... *(Min length: 1, Max length: 255)* |
| `description` | `string` | No | Updated description for the media item. If provided, replaces the current description. Maximum 10... *(Max length: 1000)* |
| `tags` | `array` | No | Updated organizational tags for the media. If provided, replaces the current tag set. Each tag mu... |
| `metadata` | `MediaMetadata` | No | Updated technical metadata for the media file. If provided, replaces or merges with current metad... |
| `isPremium` | `bool` | No | Updated premium status for the media. If provided, changes whether the media requires a premium s... |
| `isActive` | `bool` | No | Updated active status for the media. If provided, changes whether the media is currently availabl... |
| `isPublic` | `bool` | No | Updated public visibility for the media. If provided, changes whether the media is publicly acces... |


**Example Request:**

```json
{
    "name": null,
    "description": null,
    "tags": null,
    "metadata": null,
    "isPremium": null,
    "isActive": null,
    "isPublic": null
}
```

#### Response

**JsonResponse**

Response represents an HTTP response in JSON format.

Note that this class does not force the returned JSON content to be an
object. It is however recommended that you do return an object as it
protects yourself against XSSI and JSON-JavaScript Hijacking.

| Property | Type | Required | Description |
|----------|------|----------|-------------|
| `data` | `mixed` | Yes | No description available |
| `callback` | `string` | No | No description available |
| `encodingOptions` | `int` | No | No description available |
| `headers` | `Symfony\Component\HttpFoundation\ResponseHeaderBag` | Yes | No description available |
| `content` | `string` | Yes | No description available |
| `version` | `string` | Yes | No description available |
| `statusCode` | `int` | Yes | No description available |
| `statusText` | `string` | Yes | No description available |
| `charset` | `string` | No | No description available |
| `statusTexts` | `array` | No | Status codes translation table. The list of codes is complete according to the {@link https://www... |


**Example Response:**

```json
{
    "data": null,
    "callback": "example_string",
    "encodingOptions": 42,
    "headers": null,
    "content": "example_string",
    "version": "example_string",
    "statusCode": 42,
    "statusText": "active",
    "charset": "example_string",
    "statusTexts": [
        "example_item"
    ]
}
```

---

<!-- Route 9 -->
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

| Property | Type | Required | Description |
|----------|------|----------|-------------|
| `data` | `mixed` | Yes | No description available |
| `callback` | `string` | No | No description available |
| `encodingOptions` | `int` | No | No description available |
| `headers` | `Symfony\Component\HttpFoundation\ResponseHeaderBag` | Yes | No description available |
| `content` | `string` | Yes | No description available |
| `version` | `string` | Yes | No description available |
| `statusCode` | `int` | Yes | No description available |
| `statusText` | `string` | Yes | No description available |
| `charset` | `string` | No | No description available |
| `statusTexts` | `array` | No | Status codes translation table. The list of codes is complete according to the {@link https://www... |


**Example Response:**

```json
{
    "data": null,
    "callback": "example_string",
    "encodingOptions": 42,
    "headers": null,
    "content": "example_string",
    "version": "example_string",
    "statusCode": 42,
    "statusText": "active",
    "charset": "example_string",
    "statusTexts": [
        "example_item"
    ]
}
```

---

## DesignController

*8 routes*

Design Controller

Manages design operations including creation, retrieval, updating, and deletion.
Handles design duplication, thumbnail management, and search functionality.
All endpoints require authentication and enforce user ownership for security.

<!-- Route 1 -->
### GET 

List designs for authenticated user

Returns a paginated list of designs belonging to the authenticated user.
Supports filtering by project, status, and search functionality.

#### Parameters

- **request** (Symfony\Component\HttpFoundation\Request)

#### Request Body

**Request**

Request represents an HTTP request.

The methods dealing with URL accept / return a raw path (% encoded):
* getBasePath
* getBaseUrl
* getPathInfo
* getRequestUri
* getUri
* getUriForPath

| Property | Type | Required | Description |
|----------|------|----------|-------------|
| `trustedProxies` | `array` | No | No description available |
| `trustedHostPatterns` | `array` | No | No description available |
| `trustedHosts` | `array` | No | No description available |
| `httpMethodParameterOverride` | `bool` | No | No description available |
| `attributes` | `Symfony\Component\HttpFoundation\ParameterBag` | Yes | Custom parameters. |
| `request` | `Symfony\Component\HttpFoundation\InputBag` | Yes | Request body parameters ($_POST). |
| `query` | `Symfony\Component\HttpFoundation\InputBag` | Yes | Query string parameters ($_GET). |
| `server` | `Symfony\Component\HttpFoundation\ServerBag` | Yes | Server and execution environment parameters ($_SERVER). |
| `files` | `Symfony\Component\HttpFoundation\FileBag` | Yes | Uploaded files ($_FILES). |
| `cookies` | `Symfony\Component\HttpFoundation\InputBag` | Yes | Cookies ($_COOKIE). |
| `headers` | `Symfony\Component\HttpFoundation\HeaderBag` | Yes | Headers (taken from the $_SERVER). |
| `content` | `mixed` | No | No description available |
| `languages` | `array` | No | No description available |
| `charsets` | `array` | No | No description available |
| `encodings` | `array` | No | No description available |
| `acceptableContentTypes` | `array` | No | No description available |
| `pathInfo` | `string` | No | No description available |
| `requestUri` | `string` | No | No description available |
| `baseUrl` | `string` | No | No description available |
| `basePath` | `string` | No | No description available |
| `method` | `string` | No | No description available |
| `format` | `string` | No | No description available |
| `session` | `Symfony\Component\HttpFoundation\Session\SessionInterface` \| `Closure` \| `null` | No | No description available |
| `locale` | `string` | No | No description available |
| `defaultLocale` | `string` | No | No description available |
| `formats` | `array` | No | No description available |
| `requestFactory` | `Closure` | No | No description available |
| `preferredFormat` | `string` | No | No description available |
| `isHostValid` | `bool` | No | No description available |
| `isForwardedValid` | `bool` | No | No description available |
| `isSafeContentPreferred` | `bool` | Yes | No description available |
| `trustedValuesCache` | `array` | No | No description available |
| `trustedHeaderSet` | `int` | No | No description available |
| `isIisRewrite` | `mixed` | No | No description available |


**Example Request:**

```json
{
    "trustedProxies": [
        "example_item"
    ],
    "trustedHostPatterns": [
        "example_item"
    ],
    "trustedHosts": [
        "example_item"
    ],
    "httpMethodParameterOverride": true,
    "attributes": null,
    "request": null,
    "query": null,
    "server": null,
    "files": null,
    "cookies": null,
    "headers": null,
    "content": null,
    "languages": [
        "example_item"
    ],
    "charsets": [
        "example_item"
    ],
    "encodings": [
        "example_item"
    ],
    "acceptableContentTypes": [
        "example_item"
    ],
    "pathInfo": "example_string",
    "requestUri": "example_string",
    "baseUrl": "https:\/\/example.com",
    "basePath": "example_string",
    "method": "example_string",
    "format": "example_string",
    "session": null,
    "locale": "example_string",
    "defaultLocale": "example_string",
    "formats": [
        "example_item"
    ],
    "requestFactory": null,
    "preferredFormat": "example_string",
    "isHostValid": true,
    "isForwardedValid": true,
    "isSafeContentPreferred": true,
    "trustedValuesCache": [
        "example_item"
    ],
    "trustedHeaderSet": 42,
    "isIisRewrite": null
}
```

#### Response

**JsonResponse**

Response represents an HTTP response in JSON format.

Note that this class does not force the returned JSON content to be an
object. It is however recommended that you do return an object as it
protects yourself against XSSI and JSON-JavaScript Hijacking.

| Property | Type | Required | Description |
|----------|------|----------|-------------|
| `data` | `mixed` | Yes | No description available |
| `callback` | `string` | No | No description available |
| `encodingOptions` | `int` | No | No description available |
| `headers` | `Symfony\Component\HttpFoundation\ResponseHeaderBag` | Yes | No description available |
| `content` | `string` | Yes | No description available |
| `version` | `string` | Yes | No description available |
| `statusCode` | `int` | Yes | No description available |
| `statusText` | `string` | Yes | No description available |
| `charset` | `string` | No | No description available |
| `statusTexts` | `array` | No | Status codes translation table. The list of codes is complete according to the {@link https://www... |


**Example Response:**

```json
{
    "data": null,
    "callback": "example_string",
    "encodingOptions": 42,
    "headers": null,
    "content": "example_string",
    "version": "example_string",
    "statusCode": 42,
    "statusText": "active",
    "charset": "example_string",
    "statusTexts": [
        "example_item"
    ]
}
```

---

<!-- Route 2 -->
### POST 

Create a new design

Creates a new design with the provided information and associates it with the authenticated user.
Validates design data and initializes default canvas settings.

#### Request Body

**CreateDesignRequestDTO**

Request DTO for creating a new design within a project.

This DTO handles the creation of new designs with specified canvas
dimensions, initial design settings, and organizational metadata.

| Property | Type | Required | Description |
|----------|------|----------|-------------|
| `name` | `string` | Yes | Display name for the new design. This name is used throughout the application interface and shoul... *(Required, Min length: 1, Max length: 255)* |
| `description` | `string` | No | Optional description providing additional context about the design. Used to document the design's... *(Max length: 1000)* |
| `data` | `DesignData` | No | Design-level configuration and settings. Contains global design settings including canvas backgro... |
| `projectId` | `int` | No | Optional project ID to associate the design with. If provided, the design will be created within ... |
| `width` | `int` | No | Canvas width in pixels. Defines the horizontal dimension of the design canvas. Must be between 1-... *(Min: 1, Max: 10000)* |
| `height` | `int` | No | Canvas height in pixels. Defines the vertical dimension of the design canvas. Must be between 1-1... *(Min: 1, Max: 10000)* |
| `isPublic` | `bool` | No | Whether the design should be publicly accessible. Public designs can be viewed by other users and... |


**Example Request:**

```json
{
    "name": "Example Name",
    "description": null,
    "data": {
        "backgroundColor": "example_string",
        "animationSettings": [
            "example_item"
        ],
        "gridSettings": [
            1,
            2,
            3
        ],
        "viewSettings": [
            "example_item"
        ],
        "globalStyles": [
            "example_item"
        ],
        "customProperties": [
            "example_item"
        ]
    },
    "projectId": null,
    "width": 123,
    "height": 600,
    "isPublic": true
}
```

#### Response

**JsonResponse**

Response represents an HTTP response in JSON format.

Note that this class does not force the returned JSON content to be an
object. It is however recommended that you do return an object as it
protects yourself against XSSI and JSON-JavaScript Hijacking.

| Property | Type | Required | Description |
|----------|------|----------|-------------|
| `data` | `mixed` | Yes | No description available |
| `callback` | `string` | No | No description available |
| `encodingOptions` | `int` | No | No description available |
| `headers` | `Symfony\Component\HttpFoundation\ResponseHeaderBag` | Yes | No description available |
| `content` | `string` | Yes | No description available |
| `version` | `string` | Yes | No description available |
| `statusCode` | `int` | Yes | No description available |
| `statusText` | `string` | Yes | No description available |
| `charset` | `string` | No | No description available |
| `statusTexts` | `array` | No | Status codes translation table. The list of codes is complete according to the {@link https://www... |


**Example Response:**

```json
{
    "data": null,
    "callback": "example_string",
    "encodingOptions": 42,
    "headers": null,
    "content": "example_string",
    "version": "example_string",
    "statusCode": 42,
    "statusText": "active",
    "charset": "example_string",
    "statusTexts": [
        "example_item"
    ]
}
```

---

<!-- Route 3 -->
### GET /search

Search designs

Performs a comprehensive search across designs accessible to the authenticated user.
Searches in design names, descriptions, and associated project information.

#### Parameters

- **request** (Symfony\Component\HttpFoundation\Request)

#### Request Body

**Request**

Request represents an HTTP request.

The methods dealing with URL accept / return a raw path (% encoded):
* getBasePath
* getBaseUrl
* getPathInfo
* getRequestUri
* getUri
* getUriForPath

| Property | Type | Required | Description |
|----------|------|----------|-------------|
| `trustedProxies` | `array` | No | No description available |
| `trustedHostPatterns` | `array` | No | No description available |
| `trustedHosts` | `array` | No | No description available |
| `httpMethodParameterOverride` | `bool` | No | No description available |
| `attributes` | `Symfony\Component\HttpFoundation\ParameterBag` | Yes | Custom parameters. |
| `request` | `Symfony\Component\HttpFoundation\InputBag` | Yes | Request body parameters ($_POST). |
| `query` | `Symfony\Component\HttpFoundation\InputBag` | Yes | Query string parameters ($_GET). |
| `server` | `Symfony\Component\HttpFoundation\ServerBag` | Yes | Server and execution environment parameters ($_SERVER). |
| `files` | `Symfony\Component\HttpFoundation\FileBag` | Yes | Uploaded files ($_FILES). |
| `cookies` | `Symfony\Component\HttpFoundation\InputBag` | Yes | Cookies ($_COOKIE). |
| `headers` | `Symfony\Component\HttpFoundation\HeaderBag` | Yes | Headers (taken from the $_SERVER). |
| `content` | `mixed` | No | No description available |
| `languages` | `array` | No | No description available |
| `charsets` | `array` | No | No description available |
| `encodings` | `array` | No | No description available |
| `acceptableContentTypes` | `array` | No | No description available |
| `pathInfo` | `string` | No | No description available |
| `requestUri` | `string` | No | No description available |
| `baseUrl` | `string` | No | No description available |
| `basePath` | `string` | No | No description available |
| `method` | `string` | No | No description available |
| `format` | `string` | No | No description available |
| `session` | `Symfony\Component\HttpFoundation\Session\SessionInterface` \| `Closure` \| `null` | No | No description available |
| `locale` | `string` | No | No description available |
| `defaultLocale` | `string` | No | No description available |
| `formats` | `array` | No | No description available |
| `requestFactory` | `Closure` | No | No description available |
| `preferredFormat` | `string` | No | No description available |
| `isHostValid` | `bool` | No | No description available |
| `isForwardedValid` | `bool` | No | No description available |
| `isSafeContentPreferred` | `bool` | Yes | No description available |
| `trustedValuesCache` | `array` | No | No description available |
| `trustedHeaderSet` | `int` | No | No description available |
| `isIisRewrite` | `mixed` | No | No description available |


**Example Request:**

```json
{
    "trustedProxies": [
        "example_item"
    ],
    "trustedHostPatterns": [
        "example_item"
    ],
    "trustedHosts": [
        "example_item"
    ],
    "httpMethodParameterOverride": true,
    "attributes": null,
    "request": null,
    "query": null,
    "server": null,
    "files": null,
    "cookies": null,
    "headers": null,
    "content": null,
    "languages": [
        "example_item"
    ],
    "charsets": [
        "example_item"
    ],
    "encodings": [
        "example_item"
    ],
    "acceptableContentTypes": [
        "example_item"
    ],
    "pathInfo": "example_string",
    "requestUri": "example_string",
    "baseUrl": "https:\/\/example.com",
    "basePath": "example_string",
    "method": "example_string",
    "format": "example_string",
    "session": null,
    "locale": "example_string",
    "defaultLocale": "example_string",
    "formats": [
        "example_item"
    ],
    "requestFactory": null,
    "preferredFormat": "example_string",
    "isHostValid": true,
    "isForwardedValid": true,
    "isSafeContentPreferred": true,
    "trustedValuesCache": [
        "example_item"
    ],
    "trustedHeaderSet": 42,
    "isIisRewrite": null
}
```

#### Response

**JsonResponse**

Response represents an HTTP response in JSON format.

Note that this class does not force the returned JSON content to be an
object. It is however recommended that you do return an object as it
protects yourself against XSSI and JSON-JavaScript Hijacking.

| Property | Type | Required | Description |
|----------|------|----------|-------------|
| `data` | `mixed` | Yes | No description available |
| `callback` | `string` | No | No description available |
| `encodingOptions` | `int` | No | No description available |
| `headers` | `Symfony\Component\HttpFoundation\ResponseHeaderBag` | Yes | No description available |
| `content` | `string` | Yes | No description available |
| `version` | `string` | Yes | No description available |
| `statusCode` | `int` | Yes | No description available |
| `statusText` | `string` | Yes | No description available |
| `charset` | `string` | No | No description available |
| `statusTexts` | `array` | No | Status codes translation table. The list of codes is complete according to the {@link https://www... |


**Example Response:**

```json
{
    "data": null,
    "callback": "example_string",
    "encodingOptions": 42,
    "headers": null,
    "content": "example_string",
    "version": "example_string",
    "statusCode": 42,
    "statusText": "active",
    "charset": "example_string",
    "statusTexts": [
        "example_item"
    ]
}
```

---

<!-- Route 4 -->
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

| Property | Type | Required | Description |
|----------|------|----------|-------------|
| `data` | `mixed` | Yes | No description available |
| `callback` | `string` | No | No description available |
| `encodingOptions` | `int` | No | No description available |
| `headers` | `Symfony\Component\HttpFoundation\ResponseHeaderBag` | Yes | No description available |
| `content` | `string` | Yes | No description available |
| `version` | `string` | Yes | No description available |
| `statusCode` | `int` | Yes | No description available |
| `statusText` | `string` | Yes | No description available |
| `charset` | `string` | No | No description available |
| `statusTexts` | `array` | No | Status codes translation table. The list of codes is complete according to the {@link https://www... |


**Example Response:**

```json
{
    "data": null,
    "callback": "example_string",
    "encodingOptions": 42,
    "headers": null,
    "content": "example_string",
    "version": "example_string",
    "statusCode": 42,
    "statusText": "active",
    "charset": "example_string",
    "statusTexts": [
        "example_item"
    ]
}
```

---

<!-- Route 5 -->
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

| Property | Type | Required | Description |
|----------|------|----------|-------------|
| `name` | `string` | No | Updated display name for the design. If provided, replaces the current design name. Must be betwe... *(Min length: 1, Max length: 255)* |
| `description` | `string` | No | Updated description for the design. If provided, replaces the current description. Maximum 1000 c... *(Max length: 1000)* |
| `data` | `DesignData` | No | Updated design-level configuration and settings. If provided, merges with or replaces current des... |
| `projectId` | `int` | No | Updated project association for the design. If provided, moves the design to the specified projec... |
| `width` | `int` | No | Updated canvas width in pixels. If provided, resizes the canvas width. Must be between 1-10000 pi... *(Min: 1, Max: 10000)* |
| `height` | `int` | No | Updated canvas height in pixels. If provided, resizes the canvas height. Must be between 1-10000 ... *(Min: 1, Max: 10000)* |
| `isPublic` | `bool` | No | Updated public visibility for the design. If provided, changes whether the design is publicly acc... |


**Example Request:**

```json
{
    "name": null,
    "description": null,
    "data": null,
    "projectId": null,
    "width": null,
    "height": null,
    "isPublic": null
}
```

#### Response

**JsonResponse**

Response represents an HTTP response in JSON format.

Note that this class does not force the returned JSON content to be an
object. It is however recommended that you do return an object as it
protects yourself against XSSI and JSON-JavaScript Hijacking.

| Property | Type | Required | Description |
|----------|------|----------|-------------|
| `data` | `mixed` | Yes | No description available |
| `callback` | `string` | No | No description available |
| `encodingOptions` | `int` | No | No description available |
| `headers` | `Symfony\Component\HttpFoundation\ResponseHeaderBag` | Yes | No description available |
| `content` | `string` | Yes | No description available |
| `version` | `string` | Yes | No description available |
| `statusCode` | `int` | Yes | No description available |
| `statusText` | `string` | Yes | No description available |
| `charset` | `string` | No | No description available |
| `statusTexts` | `array` | No | Status codes translation table. The list of codes is complete according to the {@link https://www... |


**Example Response:**

```json
{
    "data": null,
    "callback": "example_string",
    "encodingOptions": 42,
    "headers": null,
    "content": "example_string",
    "version": "example_string",
    "statusCode": 42,
    "statusText": "active",
    "charset": "example_string",
    "statusTexts": [
        "example_item"
    ]
}
```

---

<!-- Route 6 -->
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

| Property | Type | Required | Description |
|----------|------|----------|-------------|
| `data` | `mixed` | Yes | No description available |
| `callback` | `string` | No | No description available |
| `encodingOptions` | `int` | No | No description available |
| `headers` | `Symfony\Component\HttpFoundation\ResponseHeaderBag` | Yes | No description available |
| `content` | `string` | Yes | No description available |
| `version` | `string` | Yes | No description available |
| `statusCode` | `int` | Yes | No description available |
| `statusText` | `string` | Yes | No description available |
| `charset` | `string` | No | No description available |
| `statusTexts` | `array` | No | Status codes translation table. The list of codes is complete according to the {@link https://www... |


**Example Response:**

```json
{
    "data": null,
    "callback": "example_string",
    "encodingOptions": 42,
    "headers": null,
    "content": "example_string",
    "version": "example_string",
    "statusCode": 42,
    "statusText": "active",
    "charset": "example_string",
    "statusTexts": [
        "example_item"
    ]
}
```

---

<!-- Route 7 -->
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

| Property | Type | Required | Description |
|----------|------|----------|-------------|
| `name` | `string` | No | Custom name for the duplicated design. If provided, this will be used as the name for the new des... *(Min length: 1, Max length: 255)* |
| `projectId` | `int` | No | Target project ID for the duplicated design. If provided, the duplicated design will be placed in... |


**Example Request:**

```json
{
    "name": null,
    "projectId": null
}
```

#### Response

**JsonResponse**

Response represents an HTTP response in JSON format.

Note that this class does not force the returned JSON content to be an
object. It is however recommended that you do return an object as it
protects yourself against XSSI and JSON-JavaScript Hijacking.

| Property | Type | Required | Description |
|----------|------|----------|-------------|
| `data` | `mixed` | Yes | No description available |
| `callback` | `string` | No | No description available |
| `encodingOptions` | `int` | No | No description available |
| `headers` | `Symfony\Component\HttpFoundation\ResponseHeaderBag` | Yes | No description available |
| `content` | `string` | Yes | No description available |
| `version` | `string` | Yes | No description available |
| `statusCode` | `int` | Yes | No description available |
| `statusText` | `string` | Yes | No description available |
| `charset` | `string` | No | No description available |
| `statusTexts` | `array` | No | Status codes translation table. The list of codes is complete according to the {@link https://www... |


**Example Response:**

```json
{
    "data": null,
    "callback": "example_string",
    "encodingOptions": 42,
    "headers": null,
    "content": "example_string",
    "version": "example_string",
    "statusCode": 42,
    "statusText": "active",
    "charset": "example_string",
    "statusTexts": [
        "example_item"
    ]
}
```

---

<!-- Route 8 -->
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

| Property | Type | Required | Description |
|----------|------|----------|-------------|
| `thumbnail` | `string` | Yes | URL of the new thumbnail image. Must be a valid URL pointing to an image file that will serve as ... *(Required)* |


**Example Request:**

```json
{
    "thumbnail": "example_string"
}
```

#### Response

**JsonResponse**

Response represents an HTTP response in JSON format.

Note that this class does not force the returned JSON content to be an
object. It is however recommended that you do return an object as it
protects yourself against XSSI and JSON-JavaScript Hijacking.

| Property | Type | Required | Description |
|----------|------|----------|-------------|
| `data` | `mixed` | Yes | No description available |
| `callback` | `string` | No | No description available |
| `encodingOptions` | `int` | No | No description available |
| `headers` | `Symfony\Component\HttpFoundation\ResponseHeaderBag` | Yes | No description available |
| `content` | `string` | Yes | No description available |
| `version` | `string` | Yes | No description available |
| `statusCode` | `int` | Yes | No description available |
| `statusText` | `string` | Yes | No description available |
| `charset` | `string` | No | No description available |
| `statusTexts` | `array` | No | Status codes translation table. The list of codes is complete according to the {@link https://www... |


**Example Response:**

```json
{
    "data": null,
    "callback": "example_string",
    "encodingOptions": 42,
    "headers": null,
    "content": "example_string",
    "version": "example_string",
    "statusCode": 42,
    "statusText": "active",
    "charset": "example_string",
    "statusTexts": [
        "example_item"
    ]
}
```

---

## AuthController

*6 routes*

Authentication Controller

Handles user authentication, registration, profile management, and password operations.
All endpoints return JSON responses with consistent error handling.

<!-- Route 1 -->
### PUT /change-password

**Security:** IsGranted

Change user password

Updates the authenticated user's password after validating the current password.
Enforces password strength requirements.

#### Request Body

**ChangePasswordRequestDTO**

Data Transfer Object for password change requests



| Property | Type | Required | Description |
|----------|------|----------|-------------|
| `currentPassword` | `string` | Yes | No description available |
| `newPassword` | `string` | Yes | No description available |
| `confirmPassword` | `string` | Yes | No description available |


**Example Request:**

```json
{
    "currentPassword": "secure_password123",
    "newPassword": "secure_password123",
    "confirmPassword": "secure_password123"
}
```

#### Response

**JsonResponse**

Response represents an HTTP response in JSON format.

Note that this class does not force the returned JSON content to be an
object. It is however recommended that you do return an object as it
protects yourself against XSSI and JSON-JavaScript Hijacking.

| Property | Type | Required | Description |
|----------|------|----------|-------------|
| `data` | `mixed` | Yes | No description available |
| `callback` | `string` | No | No description available |
| `encodingOptions` | `int` | No | No description available |
| `headers` | `Symfony\Component\HttpFoundation\ResponseHeaderBag` | Yes | No description available |
| `content` | `string` | Yes | No description available |
| `version` | `string` | Yes | No description available |
| `statusCode` | `int` | Yes | No description available |
| `statusText` | `string` | Yes | No description available |
| `charset` | `string` | No | No description available |
| `statusTexts` | `array` | No | Status codes translation table. The list of codes is complete according to the {@link https://www... |


**Example Response:**

```json
{
    "data": null,
    "callback": "example_string",
    "encodingOptions": 42,
    "headers": null,
    "content": "example_string",
    "version": "example_string",
    "statusCode": 42,
    "statusText": "active",
    "charset": "example_string",
    "statusTexts": [
        "example_item"
    ]
}
```

---

<!-- Route 2 -->
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

| Property | Type | Required | Description |
|----------|------|----------|-------------|
| `email` | `string` | Yes | User's email address for authentication. Must be a valid email format and correspond to a registe... |
| `password` | `string` | Yes | User's password for authentication. Plain text password that will be verified against the hashed ... |


**Example Request:**

```json
{
    "email": "user@example.com",
    "password": "secure_password123"
}
```

#### Response

**JsonResponse**

Response represents an HTTP response in JSON format.

Note that this class does not force the returned JSON content to be an
object. It is however recommended that you do return an object as it
protects yourself against XSSI and JSON-JavaScript Hijacking.

| Property | Type | Required | Description |
|----------|------|----------|-------------|
| `data` | `mixed` | Yes | No description available |
| `callback` | `string` | No | No description available |
| `encodingOptions` | `int` | No | No description available |
| `headers` | `Symfony\Component\HttpFoundation\ResponseHeaderBag` | Yes | No description available |
| `content` | `string` | Yes | No description available |
| `version` | `string` | Yes | No description available |
| `statusCode` | `int` | Yes | No description available |
| `statusText` | `string` | Yes | No description available |
| `charset` | `string` | No | No description available |
| `statusTexts` | `array` | No | Status codes translation table. The list of codes is complete according to the {@link https://www... |


**Example Response:**

```json
{
    "data": null,
    "callback": "example_string",
    "encodingOptions": 42,
    "headers": null,
    "content": "example_string",
    "version": "example_string",
    "statusCode": 42,
    "statusText": "active",
    "charset": "example_string",
    "statusTexts": [
        "example_item"
    ]
}
```

---

<!-- Route 3 -->
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

| Property | Type | Required | Description |
|----------|------|----------|-------------|
| `data` | `mixed` | Yes | No description available |
| `callback` | `string` | No | No description available |
| `encodingOptions` | `int` | No | No description available |
| `headers` | `Symfony\Component\HttpFoundation\ResponseHeaderBag` | Yes | No description available |
| `content` | `string` | Yes | No description available |
| `version` | `string` | Yes | No description available |
| `statusCode` | `int` | Yes | No description available |
| `statusText` | `string` | Yes | No description available |
| `charset` | `string` | No | No description available |
| `statusTexts` | `array` | No | Status codes translation table. The list of codes is complete according to the {@link https://www... |


**Example Response:**

```json
{
    "data": null,
    "callback": "example_string",
    "encodingOptions": 42,
    "headers": null,
    "content": "example_string",
    "version": "example_string",
    "statusCode": 42,
    "statusText": "active",
    "charset": "example_string",
    "statusTexts": [
        "example_item"
    ]
}
```

---

<!-- Route 4 -->
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

| Property | Type | Required | Description |
|----------|------|----------|-------------|
| `data` | `mixed` | Yes | No description available |
| `callback` | `string` | No | No description available |
| `encodingOptions` | `int` | No | No description available |
| `headers` | `Symfony\Component\HttpFoundation\ResponseHeaderBag` | Yes | No description available |
| `content` | `string` | Yes | No description available |
| `version` | `string` | Yes | No description available |
| `statusCode` | `int` | Yes | No description available |
| `statusText` | `string` | Yes | No description available |
| `charset` | `string` | No | No description available |
| `statusTexts` | `array` | No | Status codes translation table. The list of codes is complete according to the {@link https://www... |


**Example Response:**

```json
{
    "data": null,
    "callback": "example_string",
    "encodingOptions": 42,
    "headers": null,
    "content": "example_string",
    "version": "example_string",
    "statusCode": 42,
    "statusText": "active",
    "charset": "example_string",
    "statusTexts": [
        "example_item"
    ]
}
```

---

<!-- Route 5 -->
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

| Property | Type | Required | Description |
|----------|------|----------|-------------|
| `firstName` | `string` | No | User's first name for display and identification purposes Must be between 1-255 characters if pro... *(Min length: 1, Max length: 255)* |
| `lastName` | `string` | No | User's last name for display and identification purposes Must be between 1-255 characters if prov... *(Min length: 1, Max length: 255)* |
| `username` | `string` | No | Unique username for the user account Must be 3-100 characters, containing only letters, numbers, ... *(Min length: 3, Max length: 100)* |
| `avatar` | `string` | No | URL to the user's profile avatar image Must be a valid URL pointing to an image file Used for pro... *(Max length: 500)* |
| `settings` | `UserSettings` | No | User's application settings and preferences Controls theme, language, notifications, auto-save, a... |


**Example Request:**

```json
{
    "firstName": null,
    "lastName": null,
    "username": null,
    "avatar": null,
    "settings": null
}
```

#### Response

**JsonResponse**

Response represents an HTTP response in JSON format.

Note that this class does not force the returned JSON content to be an
object. It is however recommended that you do return an object as it
protects yourself against XSSI and JSON-JavaScript Hijacking.

| Property | Type | Required | Description |
|----------|------|----------|-------------|
| `data` | `mixed` | Yes | No description available |
| `callback` | `string` | No | No description available |
| `encodingOptions` | `int` | No | No description available |
| `headers` | `Symfony\Component\HttpFoundation\ResponseHeaderBag` | Yes | No description available |
| `content` | `string` | Yes | No description available |
| `version` | `string` | Yes | No description available |
| `statusCode` | `int` | Yes | No description available |
| `statusText` | `string` | Yes | No description available |
| `charset` | `string` | No | No description available |
| `statusTexts` | `array` | No | Status codes translation table. The list of codes is complete according to the {@link https://www... |


**Example Response:**

```json
{
    "data": null,
    "callback": "example_string",
    "encodingOptions": 42,
    "headers": null,
    "content": "example_string",
    "version": "example_string",
    "statusCode": 42,
    "statusText": "active",
    "charset": "example_string",
    "statusTexts": [
        "example_item"
    ]
}
```

---

<!-- Route 6 -->
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

| Property | Type | Required | Description |
|----------|------|----------|-------------|
| `email` | `string` | Yes | User's email address for account registration. Must be a valid email format and unique in the sys... |
| `password` | `string` | Yes | User's chosen password for account security. Must meet security requirements: minimum 8 character... |
| `firstName` | `string` | Yes | User's first (given) name. Required for account identification and personalization. Used in user ... |
| `lastName` | `string` | Yes | User's last (family) name. Required for account identification and personalization. Used in user ... |
| `username` | `string` | Yes | Optional unique username for the account. If provided, must be 3-100 characters and contain only ... |


**Example Request:**

```json
{
    "email": "user@example.com",
    "password": "secure_password123",
    "firstName": "Example Name",
    "lastName": "Example Name",
    "username": "Example Name"
}
```

#### Response

**JsonResponse**

Response represents an HTTP response in JSON format.

Note that this class does not force the returned JSON content to be an
object. It is however recommended that you do return an object as it
protects yourself against XSSI and JSON-JavaScript Hijacking.

| Property | Type | Required | Description |
|----------|------|----------|-------------|
| `data` | `mixed` | Yes | No description available |
| `callback` | `string` | No | No description available |
| `encodingOptions` | `int` | No | No description available |
| `headers` | `Symfony\Component\HttpFoundation\ResponseHeaderBag` | Yes | No description available |
| `content` | `string` | Yes | No description available |
| `version` | `string` | Yes | No description available |
| `statusCode` | `int` | Yes | No description available |
| `statusText` | `string` | Yes | No description available |
| `charset` | `string` | No | No description available |
| `statusTexts` | `array` | No | Status codes translation table. The list of codes is complete according to the {@link https://www... |


**Example Response:**

```json
{
    "data": null,
    "callback": "example_string",
    "encodingOptions": 42,
    "headers": null,
    "content": "example_string",
    "version": "example_string",
    "statusCode": 42,
    "statusText": "active",
    "charset": "example_string",
    "statusTexts": [
        "example_item"
    ]
}
```

---

## ProjectController

*8 routes*

Project Controller

Manages project operations including creation, retrieval, updating, and deletion.
Handles project sharing, duplication, and public project discovery.
All endpoints require authentication and enforce user ownership for security.

<!-- Route 1 -->
### GET 

List projects for authenticated user

Returns a paginated list of projects belonging to the authenticated user.
Supports filtering by status, sorting by various fields, and search functionality.

#### Parameters

- **request** (Symfony\Component\HttpFoundation\Request)

#### Request Body

**Request**

Request represents an HTTP request.

The methods dealing with URL accept / return a raw path (% encoded):
* getBasePath
* getBaseUrl
* getPathInfo
* getRequestUri
* getUri
* getUriForPath

| Property | Type | Required | Description |
|----------|------|----------|-------------|
| `trustedProxies` | `array` | No | No description available |
| `trustedHostPatterns` | `array` | No | No description available |
| `trustedHosts` | `array` | No | No description available |
| `httpMethodParameterOverride` | `bool` | No | No description available |
| `attributes` | `Symfony\Component\HttpFoundation\ParameterBag` | Yes | Custom parameters. |
| `request` | `Symfony\Component\HttpFoundation\InputBag` | Yes | Request body parameters ($_POST). |
| `query` | `Symfony\Component\HttpFoundation\InputBag` | Yes | Query string parameters ($_GET). |
| `server` | `Symfony\Component\HttpFoundation\ServerBag` | Yes | Server and execution environment parameters ($_SERVER). |
| `files` | `Symfony\Component\HttpFoundation\FileBag` | Yes | Uploaded files ($_FILES). |
| `cookies` | `Symfony\Component\HttpFoundation\InputBag` | Yes | Cookies ($_COOKIE). |
| `headers` | `Symfony\Component\HttpFoundation\HeaderBag` | Yes | Headers (taken from the $_SERVER). |
| `content` | `mixed` | No | No description available |
| `languages` | `array` | No | No description available |
| `charsets` | `array` | No | No description available |
| `encodings` | `array` | No | No description available |
| `acceptableContentTypes` | `array` | No | No description available |
| `pathInfo` | `string` | No | No description available |
| `requestUri` | `string` | No | No description available |
| `baseUrl` | `string` | No | No description available |
| `basePath` | `string` | No | No description available |
| `method` | `string` | No | No description available |
| `format` | `string` | No | No description available |
| `session` | `Symfony\Component\HttpFoundation\Session\SessionInterface` \| `Closure` \| `null` | No | No description available |
| `locale` | `string` | No | No description available |
| `defaultLocale` | `string` | No | No description available |
| `formats` | `array` | No | No description available |
| `requestFactory` | `Closure` | No | No description available |
| `preferredFormat` | `string` | No | No description available |
| `isHostValid` | `bool` | No | No description available |
| `isForwardedValid` | `bool` | No | No description available |
| `isSafeContentPreferred` | `bool` | Yes | No description available |
| `trustedValuesCache` | `array` | No | No description available |
| `trustedHeaderSet` | `int` | No | No description available |
| `isIisRewrite` | `mixed` | No | No description available |


**Example Request:**

```json
{
    "trustedProxies": [
        "example_item"
    ],
    "trustedHostPatterns": [
        "example_item"
    ],
    "trustedHosts": [
        "example_item"
    ],
    "httpMethodParameterOverride": true,
    "attributes": null,
    "request": null,
    "query": null,
    "server": null,
    "files": null,
    "cookies": null,
    "headers": null,
    "content": null,
    "languages": [
        "example_item"
    ],
    "charsets": [
        "example_item"
    ],
    "encodings": [
        "example_item"
    ],
    "acceptableContentTypes": [
        "example_item"
    ],
    "pathInfo": "example_string",
    "requestUri": "example_string",
    "baseUrl": "https:\/\/example.com",
    "basePath": "example_string",
    "method": "example_string",
    "format": "example_string",
    "session": null,
    "locale": "example_string",
    "defaultLocale": "example_string",
    "formats": [
        "example_item"
    ],
    "requestFactory": null,
    "preferredFormat": "example_string",
    "isHostValid": true,
    "isForwardedValid": true,
    "isSafeContentPreferred": true,
    "trustedValuesCache": [
        "example_item"
    ],
    "trustedHeaderSet": 42,
    "isIisRewrite": null
}
```

#### Response

**JsonResponse**

Response represents an HTTP response in JSON format.

Note that this class does not force the returned JSON content to be an
object. It is however recommended that you do return an object as it
protects yourself against XSSI and JSON-JavaScript Hijacking.

| Property | Type | Required | Description |
|----------|------|----------|-------------|
| `data` | `mixed` | Yes | No description available |
| `callback` | `string` | No | No description available |
| `encodingOptions` | `int` | No | No description available |
| `headers` | `Symfony\Component\HttpFoundation\ResponseHeaderBag` | Yes | No description available |
| `content` | `string` | Yes | No description available |
| `version` | `string` | Yes | No description available |
| `statusCode` | `int` | Yes | No description available |
| `statusText` | `string` | Yes | No description available |
| `charset` | `string` | No | No description available |
| `statusTexts` | `array` | No | Status codes translation table. The list of codes is complete according to the {@link https://www... |


**Example Response:**

```json
{
    "data": null,
    "callback": "example_string",
    "encodingOptions": 42,
    "headers": null,
    "content": "example_string",
    "version": "example_string",
    "statusCode": 42,
    "statusText": "active",
    "charset": "example_string",
    "statusTexts": [
        "example_item"
    ]
}
```

---

<!-- Route 2 -->
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

| Property | Type | Required | Description |
|----------|------|----------|-------------|
| `name` | `string` | Yes | The display name of the project. This name is used throughout the application interface and shoul... *(Required, Min length: 1, Max length: 255)* |
| `description` | `string` | Yes | Optional description providing additional context about the project. Used to document the project... *(Max length: 1000)* |
| `isPublic` | `bool` | Yes | Whether the project should be publicly accessible. Public projects can be viewed by other users a... |
| `settings` | `ProjectSettings` | Yes | Project configuration settings including canvas dimensions, DPI, etc. Contains all technical sett... |
| `tags` | `array` | No | Organizational tags for categorizing and searching projects. Tags help users organize their proje... |
| `thumbnail` | `string` | No | Optional URL to a thumbnail image representing the project. Used for project previews in lists an... |


**Example Request:**

```json
{
    "name": "Example Name",
    "description": "This is an example description.",
    "isPublic": true,
    "settings": {
        "canvasWidth": 123,
        "canvasHeight": 600,
        "backgroundColor": "example_string",
        "orientation": "example_string",
        "units": "example_string",
        "dpi": 42,
        "gridVisible": true,
        "rulersVisible": true,
        "guidesVisible": true,
        "snapToGrid": true,
        "snapToGuides": true,
        "snapToObjects": true
    },
    "tags": [
        "tag1",
        "tag2"
    ],
    "thumbnail": null
}
```

#### Response

**JsonResponse**

Response represents an HTTP response in JSON format.

Note that this class does not force the returned JSON content to be an
object. It is however recommended that you do return an object as it
protects yourself against XSSI and JSON-JavaScript Hijacking.

| Property | Type | Required | Description |
|----------|------|----------|-------------|
| `data` | `mixed` | Yes | No description available |
| `callback` | `string` | No | No description available |
| `encodingOptions` | `int` | No | No description available |
| `headers` | `Symfony\Component\HttpFoundation\ResponseHeaderBag` | Yes | No description available |
| `content` | `string` | Yes | No description available |
| `version` | `string` | Yes | No description available |
| `statusCode` | `int` | Yes | No description available |
| `statusText` | `string` | Yes | No description available |
| `charset` | `string` | No | No description available |
| `statusTexts` | `array` | No | Status codes translation table. The list of codes is complete according to the {@link https://www... |


**Example Response:**

```json
{
    "data": null,
    "callback": "example_string",
    "encodingOptions": 42,
    "headers": null,
    "content": "example_string",
    "version": "example_string",
    "statusCode": 42,
    "statusText": "active",
    "charset": "example_string",
    "statusTexts": [
        "example_item"
    ]
}
```

---

<!-- Route 3 -->
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

| Property | Type | Required | Description |
|----------|------|----------|-------------|
| `page` | `int` | No | Page number for pagination. Specifies which page of project results to return. Must be 1 or great... *(Min: 1)* |
| `limit` | `int` | No | Number of projects per page. Controls how many projects are returned per page. Limited to a maxim... *(Min: 1, Max: 50)* |
| `search` | `string` | No | Search query for project names and descriptions. Text to search for in project names, description... *(Max length: 255)* |
| `tags` | `string` | No | Comma-separated list of tags for filtering. Tags to filter projects by. Multiple tags can be spec... |


**Example Request:**

```json
{
    "page": 25,
    "limit": 20,
    "search": null,
    "tags": null
}
```

#### Response

**JsonResponse**

Response represents an HTTP response in JSON format.

Note that this class does not force the returned JSON content to be an
object. It is however recommended that you do return an object as it
protects yourself against XSSI and JSON-JavaScript Hijacking.

| Property | Type | Required | Description |
|----------|------|----------|-------------|
| `data` | `mixed` | Yes | No description available |
| `callback` | `string` | No | No description available |
| `encodingOptions` | `int` | No | No description available |
| `headers` | `Symfony\Component\HttpFoundation\ResponseHeaderBag` | Yes | No description available |
| `content` | `string` | Yes | No description available |
| `version` | `string` | Yes | No description available |
| `statusCode` | `int` | Yes | No description available |
| `statusText` | `string` | Yes | No description available |
| `charset` | `string` | No | No description available |
| `statusTexts` | `array` | No | Status codes translation table. The list of codes is complete according to the {@link https://www... |


**Example Response:**

```json
{
    "data": null,
    "callback": "example_string",
    "encodingOptions": 42,
    "headers": null,
    "content": "example_string",
    "version": "example_string",
    "statusCode": 42,
    "statusText": "active",
    "charset": "example_string",
    "statusTexts": [
        "example_item"
    ]
}
```

---

<!-- Route 4 -->
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

| Property | Type | Required | Description |
|----------|------|----------|-------------|
| `data` | `mixed` | Yes | No description available |
| `callback` | `string` | No | No description available |
| `encodingOptions` | `int` | No | No description available |
| `headers` | `Symfony\Component\HttpFoundation\ResponseHeaderBag` | Yes | No description available |
| `content` | `string` | Yes | No description available |
| `version` | `string` | Yes | No description available |
| `statusCode` | `int` | Yes | No description available |
| `statusText` | `string` | Yes | No description available |
| `charset` | `string` | No | No description available |
| `statusTexts` | `array` | No | Status codes translation table. The list of codes is complete according to the {@link https://www... |


**Example Response:**

```json
{
    "data": null,
    "callback": "example_string",
    "encodingOptions": 42,
    "headers": null,
    "content": "example_string",
    "version": "example_string",
    "statusCode": 42,
    "statusText": "active",
    "charset": "example_string",
    "statusTexts": [
        "example_item"
    ]
}
```

---

<!-- Route 5 -->
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

| Property | Type | Required | Description |
|----------|------|----------|-------------|
| `name` | `string` | No | Updated display name for the project. If provided, replaces the current project name. Must be bet... *(Min length: 1, Max length: 255)* |
| `description` | `string` | No | Updated description for the project. If provided, replaces the current description. Maximum 1000 ... *(Max length: 1000)* |
| `isPublic` | `bool` | No | Updated visibility setting for the project. If provided, changes whether the project is publicly ... |
| `settings` | `ProjectSettings` | No | Updated project configuration settings. If provided, replaces or merges with current project sett... |
| `tags` | `array` | No | Updated organizational tags for the project. If provided, replaces the current tag set. Each tag ... |
| `thumbnail` | `string` | No | Updated thumbnail URL for the project. If provided, replaces the current thumbnail. Should be a v... |


**Example Request:**

```json
{
    "name": null,
    "description": null,
    "isPublic": null,
    "settings": null,
    "tags": null,
    "thumbnail": null
}
```

#### Response

**JsonResponse**

Response represents an HTTP response in JSON format.

Note that this class does not force the returned JSON content to be an
object. It is however recommended that you do return an object as it
protects yourself against XSSI and JSON-JavaScript Hijacking.

| Property | Type | Required | Description |
|----------|------|----------|-------------|
| `data` | `mixed` | Yes | No description available |
| `callback` | `string` | No | No description available |
| `encodingOptions` | `int` | No | No description available |
| `headers` | `Symfony\Component\HttpFoundation\ResponseHeaderBag` | Yes | No description available |
| `content` | `string` | Yes | No description available |
| `version` | `string` | Yes | No description available |
| `statusCode` | `int` | Yes | No description available |
| `statusText` | `string` | Yes | No description available |
| `charset` | `string` | No | No description available |
| `statusTexts` | `array` | No | Status codes translation table. The list of codes is complete according to the {@link https://www... |


**Example Response:**

```json
{
    "data": null,
    "callback": "example_string",
    "encodingOptions": 42,
    "headers": null,
    "content": "example_string",
    "version": "example_string",
    "statusCode": 42,
    "statusText": "active",
    "charset": "example_string",
    "statusTexts": [
        "example_item"
    ]
}
```

---

<!-- Route 6 -->
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

| Property | Type | Required | Description |
|----------|------|----------|-------------|
| `data` | `mixed` | Yes | No description available |
| `callback` | `string` | No | No description available |
| `encodingOptions` | `int` | No | No description available |
| `headers` | `Symfony\Component\HttpFoundation\ResponseHeaderBag` | Yes | No description available |
| `content` | `string` | Yes | No description available |
| `version` | `string` | Yes | No description available |
| `statusCode` | `int` | Yes | No description available |
| `statusText` | `string` | Yes | No description available |
| `charset` | `string` | No | No description available |
| `statusTexts` | `array` | No | Status codes translation table. The list of codes is complete according to the {@link https://www... |


**Example Response:**

```json
{
    "data": null,
    "callback": "example_string",
    "encodingOptions": 42,
    "headers": null,
    "content": "example_string",
    "version": "example_string",
    "statusCode": 42,
    "statusText": "active",
    "charset": "example_string",
    "statusTexts": [
        "example_item"
    ]
}
```

---

<!-- Route 7 -->
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

| Property | Type | Required | Description |
|----------|------|----------|-------------|
| `name` | `string` | No | Display name for the duplicated project. The name given to the new project copy. Must be between ... *(Required, Min length: 1, Max length: 255)* |


**Example Request:**

```json
{
    "name": null
}
```

#### Response

**JsonResponse**

Response represents an HTTP response in JSON format.

Note that this class does not force the returned JSON content to be an
object. It is however recommended that you do return an object as it
protects yourself against XSSI and JSON-JavaScript Hijacking.

| Property | Type | Required | Description |
|----------|------|----------|-------------|
| `data` | `mixed` | Yes | No description available |
| `callback` | `string` | No | No description available |
| `encodingOptions` | `int` | No | No description available |
| `headers` | `Symfony\Component\HttpFoundation\ResponseHeaderBag` | Yes | No description available |
| `content` | `string` | Yes | No description available |
| `version` | `string` | Yes | No description available |
| `statusCode` | `int` | Yes | No description available |
| `statusText` | `string` | Yes | No description available |
| `charset` | `string` | No | No description available |
| `statusTexts` | `array` | No | Status codes translation table. The list of codes is complete according to the {@link https://www... |


**Example Response:**

```json
{
    "data": null,
    "callback": "example_string",
    "encodingOptions": 42,
    "headers": null,
    "content": "example_string",
    "version": "example_string",
    "statusCode": 42,
    "statusText": "active",
    "charset": "example_string",
    "statusTexts": [
        "example_item"
    ]
}
```

---

<!-- Route 8 -->
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

| Property | Type | Required | Description |
|----------|------|----------|-------------|
| `data` | `mixed` | Yes | No description available |
| `callback` | `string` | No | No description available |
| `encodingOptions` | `int` | No | No description available |
| `headers` | `Symfony\Component\HttpFoundation\ResponseHeaderBag` | Yes | No description available |
| `content` | `string` | Yes | No description available |
| `version` | `string` | Yes | No description available |
| `statusCode` | `int` | Yes | No description available |
| `statusText` | `string` | Yes | No description available |
| `charset` | `string` | No | No description available |
| `statusTexts` | `array` | No | Status codes translation table. The list of codes is complete according to the {@link https://www... |


**Example Response:**

```json
{
    "data": null,
    "callback": "example_string",
    "encodingOptions": 42,
    "headers": null,
    "content": "example_string",
    "version": "example_string",
    "statusCode": 42,
    "statusText": "active",
    "charset": "example_string",
    "statusTexts": [
        "example_item"
    ]
}
```

---

## LayerController

*7 routes*

Layer Controller

Manages design layer operations including creation, modification, deletion, and organization.
Handles layer positioning, duplication, and bulk operations for design elements.
All operations enforce design ownership validation and proper layer hierarchy management.
Layers are the core building blocks of designs in the canvas editor system.

<!-- Route 1 -->
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

| Property | Type | Required | Description |
|----------|------|----------|-------------|
| `designId` | `string` | Yes | Design ID where the new layer will be created Must be a valid UUID string identifying an existing... *(Required)* |
| `type` | `string` | Yes | Type of layer being created Determines which properties and behaviors the layer will have Valid v... *(Required, Choices: 'text', 'image', 'shape', 'group', 'video', 'audio')* |
| `name` | `string` | Yes | Display name for the layer Used for identification in the layers panel and timeline Must be 1-255... *(Required, Max length: 255)* |
| `properties` | `ShapeLayerProperties` | Yes | Layer-specific visual and behavior properties Contains type-specific attributes like text styling... |
| `transform` | `Transform` | Yes | 2D transformation matrix for initial layer positioning Controls position, size, rotation, scale, ... |
| `zIndex` | `int` | No | Layer stacking order within the design Higher values appear above lower values in the visual stac... |
| `visible` | `bool` | No | Initial visibility state of the layer true: Layer is visible and rendered in the canvas (default)... |
| `locked` | `bool` | No | Initial edit protection state false: Layer can be freely edited and manipulated (default) true: L... |
| `parentLayerId` | `string` | No | Parent layer ID for hierarchical grouping null: Layer is created at root level of the design (def... |


**Example Request:**

```json
{
    "designId": "example_id_123",
    "type": "example_type",
    "name": "Example Name",
    "properties": null,
    "transform": {
        "x": 3.14,
        "y": 3.14,
        "width": 3.14,
        "height": 3.14,
        "rotation": 3.14,
        "scaleX": 3.14,
        "scaleY": 3.14,
        "skewX": 3.14,
        "skewY": 3.14,
        "opacity": 3.14
    },
    "zIndex": null,
    "visible": null,
    "locked": null,
    "parentLayerId": null
}
```

#### Response

**JsonResponse**

Response represents an HTTP response in JSON format.

Note that this class does not force the returned JSON content to be an
object. It is however recommended that you do return an object as it
protects yourself against XSSI and JSON-JavaScript Hijacking.

| Property | Type | Required | Description |
|----------|------|----------|-------------|
| `data` | `mixed` | Yes | No description available |
| `callback` | `string` | No | No description available |
| `encodingOptions` | `int` | No | No description available |
| `headers` | `Symfony\Component\HttpFoundation\ResponseHeaderBag` | Yes | No description available |
| `content` | `string` | Yes | No description available |
| `version` | `string` | Yes | No description available |
| `statusCode` | `int` | Yes | No description available |
| `statusText` | `string` | Yes | No description available |
| `charset` | `string` | No | No description available |
| `statusTexts` | `array` | No | Status codes translation table. The list of codes is complete according to the {@link https://www... |


**Example Response:**

```json
{
    "data": null,
    "callback": "example_string",
    "encodingOptions": 42,
    "headers": null,
    "content": "example_string",
    "version": "example_string",
    "statusCode": 42,
    "statusText": "active",
    "charset": "example_string",
    "statusTexts": [
        "example_item"
    ]
}
```

---

<!-- Route 2 -->
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

| Property | Type | Required | Description |
|----------|------|----------|-------------|
| `layers` | `array` | Yes | Array of layer updates to perform in batch. Each LayerUpdate contains: - id: The unique identifie... |


**Example Request:**

```json
{
    "layers": [
        "example_item"
    ]
}
```

#### Response

**JsonResponse**

Response represents an HTTP response in JSON format.

Note that this class does not force the returned JSON content to be an
object. It is however recommended that you do return an object as it
protects yourself against XSSI and JSON-JavaScript Hijacking.

| Property | Type | Required | Description |
|----------|------|----------|-------------|
| `data` | `mixed` | Yes | No description available |
| `callback` | `string` | No | No description available |
| `encodingOptions` | `int` | No | No description available |
| `headers` | `Symfony\Component\HttpFoundation\ResponseHeaderBag` | Yes | No description available |
| `content` | `string` | Yes | No description available |
| `version` | `string` | Yes | No description available |
| `statusCode` | `int` | Yes | No description available |
| `statusText` | `string` | Yes | No description available |
| `charset` | `string` | No | No description available |
| `statusTexts` | `array` | No | Status codes translation table. The list of codes is complete according to the {@link https://www... |


**Example Response:**

```json
{
    "data": null,
    "callback": "example_string",
    "encodingOptions": 42,
    "headers": null,
    "content": "example_string",
    "version": "example_string",
    "statusCode": 42,
    "statusText": "active",
    "charset": "example_string",
    "statusTexts": [
        "example_item"
    ]
}
```

---

<!-- Route 3 -->
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

| Property | Type | Required | Description |
|----------|------|----------|-------------|
| `data` | `mixed` | Yes | No description available |
| `callback` | `string` | No | No description available |
| `encodingOptions` | `int` | No | No description available |
| `headers` | `Symfony\Component\HttpFoundation\ResponseHeaderBag` | Yes | No description available |
| `content` | `string` | Yes | No description available |
| `version` | `string` | Yes | No description available |
| `statusCode` | `int` | Yes | No description available |
| `statusText` | `string` | Yes | No description available |
| `charset` | `string` | No | No description available |
| `statusTexts` | `array` | No | Status codes translation table. The list of codes is complete according to the {@link https://www... |


**Example Response:**

```json
{
    "data": null,
    "callback": "example_string",
    "encodingOptions": 42,
    "headers": null,
    "content": "example_string",
    "version": "example_string",
    "statusCode": 42,
    "statusText": "active",
    "charset": "example_string",
    "statusTexts": [
        "example_item"
    ]
}
```

---

<!-- Route 4 -->
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

| Property | Type | Required | Description |
|----------|------|----------|-------------|
| `name` | `string` | No | New display name for the layer Used for layer identification in the layers panel and timeline Mus... *(Max length: 255)* |
| `properties` | `ShapeLayerProperties|null` | No | Layer-specific visual and behavior properties Contains type-specific attributes like text styling... |
| `transform` | `Transform` | No | 2D transformation matrix for layer positioning and scaling Controls position, size, rotation, sca... |
| `zIndex` | `int` | No | Layer stacking order within its parent container Higher values appear above lower values in the v... |
| `visible` | `bool` | No | Layer visibility state in the design canvas false: Layer is hidden from view but remains in the d... |
| `locked` | `bool` | No | Layer edit protection state true: Layer cannot be selected, moved, or modified false: Layer can b... |
| `parentLayerId` | `string` | No | Parent layer ID for hierarchical grouping null: Layer is at root level of the design string: Laye... |


**Example Request:**

```json
{
    "name": null,
    "properties": null,
    "transform": null,
    "zIndex": null,
    "visible": null,
    "locked": null,
    "parentLayerId": null
}
```

#### Response

**JsonResponse**

Response represents an HTTP response in JSON format.

Note that this class does not force the returned JSON content to be an
object. It is however recommended that you do return an object as it
protects yourself against XSSI and JSON-JavaScript Hijacking.

| Property | Type | Required | Description |
|----------|------|----------|-------------|
| `data` | `mixed` | Yes | No description available |
| `callback` | `string` | No | No description available |
| `encodingOptions` | `int` | No | No description available |
| `headers` | `Symfony\Component\HttpFoundation\ResponseHeaderBag` | Yes | No description available |
| `content` | `string` | Yes | No description available |
| `version` | `string` | Yes | No description available |
| `statusCode` | `int` | Yes | No description available |
| `statusText` | `string` | Yes | No description available |
| `charset` | `string` | No | No description available |
| `statusTexts` | `array` | No | Status codes translation table. The list of codes is complete according to the {@link https://www... |


**Example Response:**

```json
{
    "data": null,
    "callback": "example_string",
    "encodingOptions": 42,
    "headers": null,
    "content": "example_string",
    "version": "example_string",
    "statusCode": 42,
    "statusText": "active",
    "charset": "example_string",
    "statusTexts": [
        "example_item"
    ]
}
```

---

<!-- Route 5 -->
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

| Property | Type | Required | Description |
|----------|------|----------|-------------|
| `data` | `mixed` | Yes | No description available |
| `callback` | `string` | No | No description available |
| `encodingOptions` | `int` | No | No description available |
| `headers` | `Symfony\Component\HttpFoundation\ResponseHeaderBag` | Yes | No description available |
| `content` | `string` | Yes | No description available |
| `version` | `string` | Yes | No description available |
| `statusCode` | `int` | Yes | No description available |
| `statusText` | `string` | Yes | No description available |
| `charset` | `string` | No | No description available |
| `statusTexts` | `array` | No | Status codes translation table. The list of codes is complete according to the {@link https://www... |


**Example Response:**

```json
{
    "data": null,
    "callback": "example_string",
    "encodingOptions": 42,
    "headers": null,
    "content": "example_string",
    "version": "example_string",
    "statusCode": 42,
    "statusText": "active",
    "charset": "example_string",
    "statusTexts": [
        "example_item"
    ]
}
```

---

<!-- Route 6 -->
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

| Property | Type | Required | Description |
|----------|------|----------|-------------|
| `name` | `string` | No | Custom name for the duplicated layer. If provided, this will be used as the name for the new laye... *(Max length: 255)* |
| `targetDesignId` | `string` | No | Target design ID for cross-design duplication. If provided, the layer will be duplicated into the... |


**Example Request:**

```json
{
    "name": null,
    "targetDesignId": null
}
```

#### Response

**JsonResponse**

Response represents an HTTP response in JSON format.

Note that this class does not force the returned JSON content to be an
object. It is however recommended that you do return an object as it
protects yourself against XSSI and JSON-JavaScript Hijacking.

| Property | Type | Required | Description |
|----------|------|----------|-------------|
| `data` | `mixed` | Yes | No description available |
| `callback` | `string` | No | No description available |
| `encodingOptions` | `int` | No | No description available |
| `headers` | `Symfony\Component\HttpFoundation\ResponseHeaderBag` | Yes | No description available |
| `content` | `string` | Yes | No description available |
| `version` | `string` | Yes | No description available |
| `statusCode` | `int` | Yes | No description available |
| `statusText` | `string` | Yes | No description available |
| `charset` | `string` | No | No description available |
| `statusTexts` | `array` | No | Status codes translation table. The list of codes is complete according to the {@link https://www... |


**Example Response:**

```json
{
    "data": null,
    "callback": "example_string",
    "encodingOptions": 42,
    "headers": null,
    "content": "example_string",
    "version": "example_string",
    "statusCode": 42,
    "statusText": "active",
    "charset": "example_string",
    "statusTexts": [
        "example_item"
    ]
}
```

---

<!-- Route 7 -->
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

| Property | Type | Required | Description |
|----------|------|----------|-------------|
| `direction` | `string` | No | Direction to move the layer relative to its current position. Supported values: - 'up': Move one ... *(Choices: 'up', 'down', 'top', 'bottom')* |
| `targetZIndex` | `int` | No | Absolute z-index position to move the layer to. When provided, moves the layer to this exact z-in... |


**Example Request:**

```json
{
    "direction": null,
    "targetZIndex": null
}
```

#### Response

**JsonResponse**

Response represents an HTTP response in JSON format.

Note that this class does not force the returned JSON content to be an
object. It is however recommended that you do return an object as it
protects yourself against XSSI and JSON-JavaScript Hijacking.

| Property | Type | Required | Description |
|----------|------|----------|-------------|
| `data` | `mixed` | Yes | No description available |
| `callback` | `string` | No | No description available |
| `encodingOptions` | `int` | No | No description available |
| `headers` | `Symfony\Component\HttpFoundation\ResponseHeaderBag` | Yes | No description available |
| `content` | `string` | Yes | No description available |
| `version` | `string` | Yes | No description available |
| `statusCode` | `int` | Yes | No description available |
| `statusText` | `string` | Yes | No description available |
| `charset` | `string` | No | No description available |
| `statusTexts` | `array` | No | Status codes translation table. The list of codes is complete according to the {@link https://www... |


**Example Response:**

```json
{
    "data": null,
    "callback": "example_string",
    "encodingOptions": 42,
    "headers": null,
    "content": "example_string",
    "version": "example_string",
    "statusCode": 42,
    "statusText": "active",
    "charset": "example_string",
    "statusTexts": [
        "example_item"
    ]
}
```

---

## ExportJobController

*10 routes*

Export Job Controller

Manages export job operations for design rendering and file generation.
Handles job creation, monitoring, cancellation, retry, and file download functionality.
All endpoints require authentication and enforce user ownership for security.

<!-- Route 1 -->
### GET 

**Security:** IsGranted

List export jobs for authenticated user

Returns a paginated list of export jobs belonging to the authenticated user.
Supports filtering by status and format, with configurable pagination.

#### Parameters

- **request** (Symfony\Component\HttpFoundation\Request)

#### Request Body

**Request**

Request represents an HTTP request.

The methods dealing with URL accept / return a raw path (% encoded):
* getBasePath
* getBaseUrl
* getPathInfo
* getRequestUri
* getUri
* getUriForPath

| Property | Type | Required | Description |
|----------|------|----------|-------------|
| `trustedProxies` | `array` | No | No description available |
| `trustedHostPatterns` | `array` | No | No description available |
| `trustedHosts` | `array` | No | No description available |
| `httpMethodParameterOverride` | `bool` | No | No description available |
| `attributes` | `Symfony\Component\HttpFoundation\ParameterBag` | Yes | Custom parameters. |
| `request` | `Symfony\Component\HttpFoundation\InputBag` | Yes | Request body parameters ($_POST). |
| `query` | `Symfony\Component\HttpFoundation\InputBag` | Yes | Query string parameters ($_GET). |
| `server` | `Symfony\Component\HttpFoundation\ServerBag` | Yes | Server and execution environment parameters ($_SERVER). |
| `files` | `Symfony\Component\HttpFoundation\FileBag` | Yes | Uploaded files ($_FILES). |
| `cookies` | `Symfony\Component\HttpFoundation\InputBag` | Yes | Cookies ($_COOKIE). |
| `headers` | `Symfony\Component\HttpFoundation\HeaderBag` | Yes | Headers (taken from the $_SERVER). |
| `content` | `mixed` | No | No description available |
| `languages` | `array` | No | No description available |
| `charsets` | `array` | No | No description available |
| `encodings` | `array` | No | No description available |
| `acceptableContentTypes` | `array` | No | No description available |
| `pathInfo` | `string` | No | No description available |
| `requestUri` | `string` | No | No description available |
| `baseUrl` | `string` | No | No description available |
| `basePath` | `string` | No | No description available |
| `method` | `string` | No | No description available |
| `format` | `string` | No | No description available |
| `session` | `Symfony\Component\HttpFoundation\Session\SessionInterface` \| `Closure` \| `null` | No | No description available |
| `locale` | `string` | No | No description available |
| `defaultLocale` | `string` | No | No description available |
| `formats` | `array` | No | No description available |
| `requestFactory` | `Closure` | No | No description available |
| `preferredFormat` | `string` | No | No description available |
| `isHostValid` | `bool` | No | No description available |
| `isForwardedValid` | `bool` | No | No description available |
| `isSafeContentPreferred` | `bool` | Yes | No description available |
| `trustedValuesCache` | `array` | No | No description available |
| `trustedHeaderSet` | `int` | No | No description available |
| `isIisRewrite` | `mixed` | No | No description available |


**Example Request:**

```json
{
    "trustedProxies": [
        "example_item"
    ],
    "trustedHostPatterns": [
        "example_item"
    ],
    "trustedHosts": [
        "example_item"
    ],
    "httpMethodParameterOverride": true,
    "attributes": null,
    "request": null,
    "query": null,
    "server": null,
    "files": null,
    "cookies": null,
    "headers": null,
    "content": null,
    "languages": [
        "example_item"
    ],
    "charsets": [
        "example_item"
    ],
    "encodings": [
        "example_item"
    ],
    "acceptableContentTypes": [
        "example_item"
    ],
    "pathInfo": "example_string",
    "requestUri": "example_string",
    "baseUrl": "https:\/\/example.com",
    "basePath": "example_string",
    "method": "example_string",
    "format": "example_string",
    "session": null,
    "locale": "example_string",
    "defaultLocale": "example_string",
    "formats": [
        "example_item"
    ],
    "requestFactory": null,
    "preferredFormat": "example_string",
    "isHostValid": true,
    "isForwardedValid": true,
    "isSafeContentPreferred": true,
    "trustedValuesCache": [
        "example_item"
    ],
    "trustedHeaderSet": 42,
    "isIisRewrite": null
}
```

#### Response

**JsonResponse**

Response represents an HTTP response in JSON format.

Note that this class does not force the returned JSON content to be an
object. It is however recommended that you do return an object as it
protects yourself against XSSI and JSON-JavaScript Hijacking.

| Property | Type | Required | Description |
|----------|------|----------|-------------|
| `data` | `mixed` | Yes | No description available |
| `callback` | `string` | No | No description available |
| `encodingOptions` | `int` | No | No description available |
| `headers` | `Symfony\Component\HttpFoundation\ResponseHeaderBag` | Yes | No description available |
| `content` | `string` | Yes | No description available |
| `version` | `string` | Yes | No description available |
| `statusCode` | `int` | Yes | No description available |
| `statusText` | `string` | Yes | No description available |
| `charset` | `string` | No | No description available |
| `statusTexts` | `array` | No | Status codes translation table. The list of codes is complete according to the {@link https://www... |


**Example Response:**

```json
{
    "data": null,
    "callback": "example_string",
    "encodingOptions": 42,
    "headers": null,
    "content": "example_string",
    "version": "example_string",
    "statusCode": 42,
    "statusText": "active",
    "charset": "example_string",
    "statusTexts": [
        "example_item"
    ]
}
```

---

<!-- Route 2 -->
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

| Property | Type | Required | Description |
|----------|------|----------|-------------|
| `designId` | `int` | Yes | ID of the design to export. References the specific design that should be rendered and exported. ... *(Required)* |
| `format` | `string` | No | Output format for the exported file. Determines the file type and rendering pipeline used for the... *(Required, Choices: 'png', 'jpeg', 'svg', 'pdf', 'mp4', 'gif')* |
| `quality` | `string` | No | Quality level for the export rendering. Controls the balance between file size and visual quality... *(Choices: 'low', 'medium', 'high', 'ultra')* |
| `width` | `int` | No | Custom width for the exported file in pixels. If provided, overrides the design's canvas width. M... |
| `height` | `int` | No | Custom height for the exported file in pixels. If provided, overrides the design's canvas height.... |
| `scale` | `float` | No | Scale factor for resizing the export. Multiplier applied to the design's original dimensions. For... |
| `transparent` | `bool` | No | Enable transparent background for supported formats. When true, removes the canvas background and... |
| `backgroundColor` | `string` | No | Custom background color for the export. Hex color code (e.g., "#ffffff") to use as the canvas bac... |
| `animationSettings` | `array` | No | Animation-specific settings for video/GIF exports. Configuration for animated exports including: ... |


**Example Request:**

```json
{
    "designId": 123,
    "format": "example_string",
    "quality": "example_string",
    "width": null,
    "height": null,
    "scale": null,
    "transparent": true,
    "backgroundColor": null,
    "animationSettings": null
}
```

#### Response

**JsonResponse**

Response represents an HTTP response in JSON format.

Note that this class does not force the returned JSON content to be an
object. It is however recommended that you do return an object as it
protects yourself against XSSI and JSON-JavaScript Hijacking.

| Property | Type | Required | Description |
|----------|------|----------|-------------|
| `data` | `mixed` | Yes | No description available |
| `callback` | `string` | No | No description available |
| `encodingOptions` | `int` | No | No description available |
| `headers` | `Symfony\Component\HttpFoundation\ResponseHeaderBag` | Yes | No description available |
| `content` | `string` | Yes | No description available |
| `version` | `string` | Yes | No description available |
| `statusCode` | `int` | Yes | No description available |
| `statusText` | `string` | Yes | No description available |
| `charset` | `string` | No | No description available |
| `statusTexts` | `array` | No | Status codes translation table. The list of codes is complete according to the {@link https://www... |


**Example Response:**

```json
{
    "data": null,
    "callback": "example_string",
    "encodingOptions": 42,
    "headers": null,
    "content": "example_string",
    "version": "example_string",
    "statusCode": 42,
    "statusText": "active",
    "charset": "example_string",
    "statusTexts": [
        "example_item"
    ]
}
```

---

<!-- Route 3 -->
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

| Property | Type | Required | Description |
|----------|------|----------|-------------|
| `data` | `mixed` | Yes | No description available |
| `callback` | `string` | No | No description available |
| `encodingOptions` | `int` | No | No description available |
| `headers` | `Symfony\Component\HttpFoundation\ResponseHeaderBag` | Yes | No description available |
| `content` | `string` | Yes | No description available |
| `version` | `string` | Yes | No description available |
| `statusCode` | `int` | Yes | No description available |
| `statusText` | `string` | Yes | No description available |
| `charset` | `string` | No | No description available |
| `statusTexts` | `array` | No | Status codes translation table. The list of codes is complete according to the {@link https://www... |


**Example Response:**

```json
{
    "data": null,
    "callback": "example_string",
    "encodingOptions": 42,
    "headers": null,
    "content": "example_string",
    "version": "example_string",
    "statusCode": 42,
    "statusText": "active",
    "charset": "example_string",
    "statusTexts": [
        "example_item"
    ]
}
```

---

<!-- Route 4 -->
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

| Property | Type | Required | Description |
|----------|------|----------|-------------|
| `data` | `mixed` | Yes | No description available |
| `callback` | `string` | No | No description available |
| `encodingOptions` | `int` | No | No description available |
| `headers` | `Symfony\Component\HttpFoundation\ResponseHeaderBag` | Yes | No description available |
| `content` | `string` | Yes | No description available |
| `version` | `string` | Yes | No description available |
| `statusCode` | `int` | Yes | No description available |
| `statusText` | `string` | Yes | No description available |
| `charset` | `string` | No | No description available |
| `statusTexts` | `array` | No | Status codes translation table. The list of codes is complete according to the {@link https://www... |


**Example Response:**

```json
{
    "data": null,
    "callback": "example_string",
    "encodingOptions": 42,
    "headers": null,
    "content": "example_string",
    "version": "example_string",
    "statusCode": 42,
    "statusText": "active",
    "charset": "example_string",
    "statusTexts": [
        "example_item"
    ]
}
```

---

<!-- Route 5 -->
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

| Property | Type | Required | Description |
|----------|------|----------|-------------|
| `data` | `mixed` | Yes | No description available |
| `callback` | `string` | No | No description available |
| `encodingOptions` | `int` | No | No description available |
| `headers` | `Symfony\Component\HttpFoundation\ResponseHeaderBag` | Yes | No description available |
| `content` | `string` | Yes | No description available |
| `version` | `string` | Yes | No description available |
| `statusCode` | `int` | Yes | No description available |
| `statusText` | `string` | Yes | No description available |
| `charset` | `string` | No | No description available |
| `statusTexts` | `array` | No | Status codes translation table. The list of codes is complete according to the {@link https://www... |


**Example Response:**

```json
{
    "data": null,
    "callback": "example_string",
    "encodingOptions": 42,
    "headers": null,
    "content": "example_string",
    "version": "example_string",
    "statusCode": 42,
    "statusText": "active",
    "charset": "example_string",
    "statusTexts": [
        "example_item"
    ]
}
```

---

<!-- Route 6 -->
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

| Property | Type | Required | Description |
|----------|------|----------|-------------|
| `data` | `mixed` | Yes | No description available |
| `callback` | `string` | No | No description available |
| `encodingOptions` | `int` | No | No description available |
| `headers` | `Symfony\Component\HttpFoundation\ResponseHeaderBag` | Yes | No description available |
| `content` | `string` | Yes | No description available |
| `version` | `string` | Yes | No description available |
| `statusCode` | `int` | Yes | No description available |
| `statusText` | `string` | Yes | No description available |
| `charset` | `string` | No | No description available |
| `statusTexts` | `array` | No | Status codes translation table. The list of codes is complete according to the {@link https://www... |


**Example Response:**

```json
{
    "data": null,
    "callback": "example_string",
    "encodingOptions": 42,
    "headers": null,
    "content": "example_string",
    "version": "example_string",
    "statusCode": 42,
    "statusText": "active",
    "charset": "example_string",
    "statusTexts": [
        "example_item"
    ]
}
```

---

<!-- Route 7 -->
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

| Property | Type | Required | Description |
|----------|------|----------|-------------|
| `data` | `mixed` | Yes | No description available |
| `callback` | `string` | No | No description available |
| `encodingOptions` | `int` | No | No description available |
| `headers` | `Symfony\Component\HttpFoundation\ResponseHeaderBag` | Yes | No description available |
| `content` | `string` | Yes | No description available |
| `version` | `string` | Yes | No description available |
| `statusCode` | `int` | Yes | No description available |
| `statusText` | `string` | Yes | No description available |
| `charset` | `string` | No | No description available |
| `statusTexts` | `array` | No | Status codes translation table. The list of codes is complete according to the {@link https://www... |


**Example Response:**

```json
{
    "data": null,
    "callback": "example_string",
    "encodingOptions": 42,
    "headers": null,
    "content": "example_string",
    "version": "example_string",
    "statusCode": 42,
    "statusText": "active",
    "charset": "example_string",
    "statusTexts": [
        "example_item"
    ]
}
```

---

<!-- Route 8 -->
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

| Property | Type | Required | Description |
|----------|------|----------|-------------|
| `data` | `mixed` | Yes | No description available |
| `callback` | `string` | No | No description available |
| `encodingOptions` | `int` | No | No description available |
| `headers` | `Symfony\Component\HttpFoundation\ResponseHeaderBag` | Yes | No description available |
| `content` | `string` | Yes | No description available |
| `version` | `string` | Yes | No description available |
| `statusCode` | `int` | Yes | No description available |
| `statusText` | `string` | Yes | No description available |
| `charset` | `string` | No | No description available |
| `statusTexts` | `array` | No | Status codes translation table. The list of codes is complete according to the {@link https://www... |


**Example Response:**

```json
{
    "data": null,
    "callback": "example_string",
    "encodingOptions": 42,
    "headers": null,
    "content": "example_string",
    "version": "example_string",
    "statusCode": 42,
    "statusText": "active",
    "charset": "example_string",
    "statusTexts": [
        "example_item"
    ]
}
```

---

<!-- Route 9 -->
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



| Property | Type | Required | Description |
|----------|------|----------|-------------|
| `headers` | `Symfony\Component\HttpFoundation\ResponseHeaderBag` | Yes | No description available |
| `content` | `string` | Yes | No description available |
| `version` | `string` | Yes | No description available |
| `statusCode` | `int` | Yes | No description available |
| `statusText` | `string` | Yes | No description available |
| `charset` | `string` | No | No description available |
| `statusTexts` | `array` | No | Status codes translation table. The list of codes is complete according to the {@link https://www... |
| `sentHeaders` | `array` | Yes | Tracks headers already sent in informational responses. |


**Example Response:**

```json
{
    "headers": null,
    "content": "example_string",
    "version": "example_string",
    "statusCode": 42,
    "statusText": "active",
    "charset": "example_string",
    "statusTexts": [
        "example_item"
    ],
    "sentHeaders": [
        "example_item"
    ]
}
```

---

<!-- Route 10 -->
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

| Property | Type | Required | Description |
|----------|------|----------|-------------|
| `data` | `mixed` | Yes | No description available |
| `callback` | `string` | No | No description available |
| `encodingOptions` | `int` | No | No description available |
| `headers` | `Symfony\Component\HttpFoundation\ResponseHeaderBag` | Yes | No description available |
| `content` | `string` | Yes | No description available |
| `version` | `string` | Yes | No description available |
| `statusCode` | `int` | Yes | No description available |
| `statusText` | `string` | Yes | No description available |
| `charset` | `string` | No | No description available |
| `statusTexts` | `array` | No | Status codes translation table. The list of codes is complete according to the {@link https://www... |


**Example Response:**

```json
{
    "data": null,
    "callback": "example_string",
    "encodingOptions": 42,
    "headers": null,
    "content": "example_string",
    "version": "example_string",
    "statusCode": 42,
    "statusText": "active",
    "charset": "example_string",
    "statusTexts": [
        "example_item"
    ]
}
```

---

## TemplateController

*6 routes*

Template Controller

Manages design templates including browsing, creation, usage tracking, and categorization.
Provides template marketplace functionality with search, filtering, and category management.
Handles template usage analytics and supports both public and user-created templates.
Templates serve as starting points for new design projects.

<!-- Route 1 -->
### GET 

List available templates with filtering and pagination

Returns a paginated list of templates with optional category filtering.
Includes template metadata, thumbnail images, and usage statistics.
Both public templates and user-created templates are included in results.

#### Parameters

- **request** (Symfony\Component\HttpFoundation\Request)

#### Request Body

**Request**

Request represents an HTTP request.

The methods dealing with URL accept / return a raw path (% encoded):
* getBasePath
* getBaseUrl
* getPathInfo
* getRequestUri
* getUri
* getUriForPath

| Property | Type | Required | Description |
|----------|------|----------|-------------|
| `trustedProxies` | `array` | No | No description available |
| `trustedHostPatterns` | `array` | No | No description available |
| `trustedHosts` | `array` | No | No description available |
| `httpMethodParameterOverride` | `bool` | No | No description available |
| `attributes` | `Symfony\Component\HttpFoundation\ParameterBag` | Yes | Custom parameters. |
| `request` | `Symfony\Component\HttpFoundation\InputBag` | Yes | Request body parameters ($_POST). |
| `query` | `Symfony\Component\HttpFoundation\InputBag` | Yes | Query string parameters ($_GET). |
| `server` | `Symfony\Component\HttpFoundation\ServerBag` | Yes | Server and execution environment parameters ($_SERVER). |
| `files` | `Symfony\Component\HttpFoundation\FileBag` | Yes | Uploaded files ($_FILES). |
| `cookies` | `Symfony\Component\HttpFoundation\InputBag` | Yes | Cookies ($_COOKIE). |
| `headers` | `Symfony\Component\HttpFoundation\HeaderBag` | Yes | Headers (taken from the $_SERVER). |
| `content` | `mixed` | No | No description available |
| `languages` | `array` | No | No description available |
| `charsets` | `array` | No | No description available |
| `encodings` | `array` | No | No description available |
| `acceptableContentTypes` | `array` | No | No description available |
| `pathInfo` | `string` | No | No description available |
| `requestUri` | `string` | No | No description available |
| `baseUrl` | `string` | No | No description available |
| `basePath` | `string` | No | No description available |
| `method` | `string` | No | No description available |
| `format` | `string` | No | No description available |
| `session` | `Symfony\Component\HttpFoundation\Session\SessionInterface` \| `Closure` \| `null` | No | No description available |
| `locale` | `string` | No | No description available |
| `defaultLocale` | `string` | No | No description available |
| `formats` | `array` | No | No description available |
| `requestFactory` | `Closure` | No | No description available |
| `preferredFormat` | `string` | No | No description available |
| `isHostValid` | `bool` | No | No description available |
| `isForwardedValid` | `bool` | No | No description available |
| `isSafeContentPreferred` | `bool` | Yes | No description available |
| `trustedValuesCache` | `array` | No | No description available |
| `trustedHeaderSet` | `int` | No | No description available |
| `isIisRewrite` | `mixed` | No | No description available |


**Example Request:**

```json
{
    "trustedProxies": [
        "example_item"
    ],
    "trustedHostPatterns": [
        "example_item"
    ],
    "trustedHosts": [
        "example_item"
    ],
    "httpMethodParameterOverride": true,
    "attributes": null,
    "request": null,
    "query": null,
    "server": null,
    "files": null,
    "cookies": null,
    "headers": null,
    "content": null,
    "languages": [
        "example_item"
    ],
    "charsets": [
        "example_item"
    ],
    "encodings": [
        "example_item"
    ],
    "acceptableContentTypes": [
        "example_item"
    ],
    "pathInfo": "example_string",
    "requestUri": "example_string",
    "baseUrl": "https:\/\/example.com",
    "basePath": "example_string",
    "method": "example_string",
    "format": "example_string",
    "session": null,
    "locale": "example_string",
    "defaultLocale": "example_string",
    "formats": [
        "example_item"
    ],
    "requestFactory": null,
    "preferredFormat": "example_string",
    "isHostValid": true,
    "isForwardedValid": true,
    "isSafeContentPreferred": true,
    "trustedValuesCache": [
        "example_item"
    ],
    "trustedHeaderSet": 42,
    "isIisRewrite": null
}
```

#### Response

**JsonResponse**

Response represents an HTTP response in JSON format.

Note that this class does not force the returned JSON content to be an
object. It is however recommended that you do return an object as it
protects yourself against XSSI and JSON-JavaScript Hijacking.

| Property | Type | Required | Description |
|----------|------|----------|-------------|
| `data` | `mixed` | Yes | No description available |
| `callback` | `string` | No | No description available |
| `encodingOptions` | `int` | No | No description available |
| `headers` | `Symfony\Component\HttpFoundation\ResponseHeaderBag` | Yes | No description available |
| `content` | `string` | Yes | No description available |
| `version` | `string` | Yes | No description available |
| `statusCode` | `int` | Yes | No description available |
| `statusText` | `string` | Yes | No description available |
| `charset` | `string` | No | No description available |
| `statusTexts` | `array` | No | Status codes translation table. The list of codes is complete according to the {@link https://www... |


**Example Response:**

```json
{
    "data": null,
    "callback": "example_string",
    "encodingOptions": 42,
    "headers": null,
    "content": "example_string",
    "version": "example_string",
    "statusCode": 42,
    "statusText": "active",
    "charset": "example_string",
    "statusTexts": [
        "example_item"
    ]
}
```

---

<!-- Route 2 -->
### POST 

**Security:** IsGranted

Create a new template

Creates a new template from user design with metadata and canvas configuration.
Templates can be made public for marketplace or kept private for personal use.
Requires authentication and validates all template data before creation.

#### Request Body

**CreateTemplateRequestDTO**

| Property | Type | Required | Description |
|----------|------|----------|-------------|
| `name` | `string` | Yes | The template name (required, max 255 characters) Used for identification and search purposes *(Required, Max length: 255)* |
| `description` | `string` | Yes | Optional description of the template (max 1000 characters) Provides context about the template's ... *(Max length: 1000)* |
| `category` | `string` | Yes | Template category for organization and filtering Must be one of the predefined categories *(Required, Choices: 'social-media', 'presentation', 'print', 'marketing', 'document', 'logo', 'web-graphics', 'video', 'animation')* |
| `tags` | `array` | Yes | Array of tags for categorization and search Each tag must be 1-50 characters and contain only alp... |
| `width` | `int` | Yes | Canvas width in pixels (required, must be positive) Defines the template's design area width *(Required)* |
| `height` | `int` | Yes | Canvas height in pixels (required, must be positive) Defines the template's design area height *(Required)* |
| `canvasSettings` | `array` | No | Canvas configuration settings as key-value pairs Contains background color, grid settings, guides... |
| `layers` | `array` | No | Layer definitions for the template Contains the visual elements that make up the template |
| `thumbnailUrl` | `string` | No | Optional URL to template thumbnail image Used for preview in template galleries |
| `previewUrl` | `string` | No | Optional URL to template preview image Used for larger preview displays |
| `isPremium` | `bool` | No | Whether this template requires premium access Premium templates are only available to paid users |
| `isActive` | `bool` | No | Whether this template is active and visible Inactive templates are hidden from users |


**Example Request:**

```json
{
    "name": "Example Name",
    "description": "This is an example description.",
    "category": "category_example",
    "tags": [
        "tag1",
        "tag2"
    ],
    "width": 123,
    "height": 600,
    "canvasSettings": [
        "example_item"
    ],
    "layers": [
        "example_item"
    ],
    "thumbnailUrl": null,
    "previewUrl": null,
    "isPremium": true,
    "isActive": true
}
```

#### Response

**JsonResponse**

Response represents an HTTP response in JSON format.

Note that this class does not force the returned JSON content to be an
object. It is however recommended that you do return an object as it
protects yourself against XSSI and JSON-JavaScript Hijacking.

| Property | Type | Required | Description |
|----------|------|----------|-------------|
| `data` | `mixed` | Yes | No description available |
| `callback` | `string` | No | No description available |
| `encodingOptions` | `int` | No | No description available |
| `headers` | `Symfony\Component\HttpFoundation\ResponseHeaderBag` | Yes | No description available |
| `content` | `string` | Yes | No description available |
| `version` | `string` | Yes | No description available |
| `statusCode` | `int` | Yes | No description available |
| `statusText` | `string` | Yes | No description available |
| `charset` | `string` | No | No description available |
| `statusTexts` | `array` | No | Status codes translation table. The list of codes is complete according to the {@link https://www... |


**Example Response:**

```json
{
    "data": null,
    "callback": "example_string",
    "encodingOptions": 42,
    "headers": null,
    "content": "example_string",
    "version": "example_string",
    "statusCode": 42,
    "statusText": "active",
    "charset": "example_string",
    "statusTexts": [
        "example_item"
    ]
}
```

---

<!-- Route 3 -->
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

| Property | Type | Required | Description |
|----------|------|----------|-------------|
| `data` | `mixed` | Yes | No description available |
| `callback` | `string` | No | No description available |
| `encodingOptions` | `int` | No | No description available |
| `headers` | `Symfony\Component\HttpFoundation\ResponseHeaderBag` | Yes | No description available |
| `content` | `string` | Yes | No description available |
| `version` | `string` | Yes | No description available |
| `statusCode` | `int` | Yes | No description available |
| `statusText` | `string` | Yes | No description available |
| `charset` | `string` | No | No description available |
| `statusTexts` | `array` | No | Status codes translation table. The list of codes is complete according to the {@link https://www... |


**Example Response:**

```json
{
    "data": null,
    "callback": "example_string",
    "encodingOptions": 42,
    "headers": null,
    "content": "example_string",
    "version": "example_string",
    "statusCode": 42,
    "statusText": "active",
    "charset": "example_string",
    "statusTexts": [
        "example_item"
    ]
}
```

---

<!-- Route 4 -->
### GET /search

Search templates with advanced filtering

Performs comprehensive template search with support for text queries,
category filtering, and tag-based search. Returns paginated results
sorted by relevance and usage popularity.

#### Request Body

**SearchTemplateRequestDTO**

| Property | Type | Required | Description |
|----------|------|----------|-------------|
| `q` | `string` | No | No description available |
| `category` | `string` | No | No description available *(Choices: 'social-media', 'presentation', 'print', 'marketing', 'document', 'logo', 'web-graphics', 'video', 'animation')* |
| `page` | `int` | No | No description available |
| `limit` | `int` | No | No description available *(Min: 1, Max: 50)* |


**Example Request:**

```json
{
    "q": null,
    "category": null,
    "page": 25,
    "limit": 20
}
```

#### Response

**JsonResponse**

Response represents an HTTP response in JSON format.

Note that this class does not force the returned JSON content to be an
object. It is however recommended that you do return an object as it
protects yourself against XSSI and JSON-JavaScript Hijacking.

| Property | Type | Required | Description |
|----------|------|----------|-------------|
| `data` | `mixed` | Yes | No description available |
| `callback` | `string` | No | No description available |
| `encodingOptions` | `int` | No | No description available |
| `headers` | `Symfony\Component\HttpFoundation\ResponseHeaderBag` | Yes | No description available |
| `content` | `string` | Yes | No description available |
| `version` | `string` | Yes | No description available |
| `statusCode` | `int` | Yes | No description available |
| `statusText` | `string` | Yes | No description available |
| `charset` | `string` | No | No description available |
| `statusTexts` | `array` | No | Status codes translation table. The list of codes is complete according to the {@link https://www... |


**Example Response:**

```json
{
    "data": null,
    "callback": "example_string",
    "encodingOptions": 42,
    "headers": null,
    "content": "example_string",
    "version": "example_string",
    "statusCode": 42,
    "statusText": "active",
    "charset": "example_string",
    "statusTexts": [
        "example_item"
    ]
}
```

---

<!-- Route 5 -->
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

| Property | Type | Required | Description |
|----------|------|----------|-------------|
| `data` | `mixed` | Yes | No description available |
| `callback` | `string` | No | No description available |
| `encodingOptions` | `int` | No | No description available |
| `headers` | `Symfony\Component\HttpFoundation\ResponseHeaderBag` | Yes | No description available |
| `content` | `string` | Yes | No description available |
| `version` | `string` | Yes | No description available |
| `statusCode` | `int` | Yes | No description available |
| `statusText` | `string` | Yes | No description available |
| `charset` | `string` | No | No description available |
| `statusTexts` | `array` | No | Status codes translation table. The list of codes is complete according to the {@link https://www... |


**Example Response:**

```json
{
    "data": null,
    "callback": "example_string",
    "encodingOptions": 42,
    "headers": null,
    "content": "example_string",
    "version": "example_string",
    "statusCode": 42,
    "statusText": "active",
    "charset": "example_string",
    "statusTexts": [
        "example_item"
    ]
}
```

---

<!-- Route 6 -->
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

| Property | Type | Required | Description |
|----------|------|----------|-------------|
| `data` | `mixed` | Yes | No description available |
| `callback` | `string` | No | No description available |
| `encodingOptions` | `int` | No | No description available |
| `headers` | `Symfony\Component\HttpFoundation\ResponseHeaderBag` | Yes | No description available |
| `content` | `string` | Yes | No description available |
| `version` | `string` | Yes | No description available |
| `statusCode` | `int` | Yes | No description available |
| `statusText` | `string` | Yes | No description available |
| `charset` | `string` | No | No description available |
| `statusTexts` | `array` | No | Status codes translation table. The list of codes is complete according to the {@link https://www... |


**Example Response:**

```json
{
    "data": null,
    "callback": "example_string",
    "encodingOptions": 42,
    "headers": null,
    "content": "example_string",
    "version": "example_string",
    "statusCode": 42,
    "statusText": "active",
    "charset": "example_string",
    "statusTexts": [
        "example_item"
    ]
}
```

---

## SearchController

*5 routes*

Search Controller

Provides comprehensive search functionality across all platform content types.
Handles global search, content-specific searches, and search suggestions.
Supports full-text search with filtering, pagination, and relevance scoring.
All searches respect user permissions and visibility settings.

<!-- Route 1 -->
### GET 

Perform a global search across multiple content types

Searches across templates, media, projects based on the query and type filter.
Supports pagination and returns results in a structured format.

#### Parameters

- **request** (Symfony\Component\HttpFoundation\Request)

#### Request Body

**Request**

Request represents an HTTP request.

The methods dealing with URL accept / return a raw path (% encoded):
* getBasePath
* getBaseUrl
* getPathInfo
* getRequestUri
* getUri
* getUriForPath

| Property | Type | Required | Description |
|----------|------|----------|-------------|
| `trustedProxies` | `array` | No | No description available |
| `trustedHostPatterns` | `array` | No | No description available |
| `trustedHosts` | `array` | No | No description available |
| `httpMethodParameterOverride` | `bool` | No | No description available |
| `attributes` | `Symfony\Component\HttpFoundation\ParameterBag` | Yes | Custom parameters. |
| `request` | `Symfony\Component\HttpFoundation\InputBag` | Yes | Request body parameters ($_POST). |
| `query` | `Symfony\Component\HttpFoundation\InputBag` | Yes | Query string parameters ($_GET). |
| `server` | `Symfony\Component\HttpFoundation\ServerBag` | Yes | Server and execution environment parameters ($_SERVER). |
| `files` | `Symfony\Component\HttpFoundation\FileBag` | Yes | Uploaded files ($_FILES). |
| `cookies` | `Symfony\Component\HttpFoundation\InputBag` | Yes | Cookies ($_COOKIE). |
| `headers` | `Symfony\Component\HttpFoundation\HeaderBag` | Yes | Headers (taken from the $_SERVER). |
| `content` | `mixed` | No | No description available |
| `languages` | `array` | No | No description available |
| `charsets` | `array` | No | No description available |
| `encodings` | `array` | No | No description available |
| `acceptableContentTypes` | `array` | No | No description available |
| `pathInfo` | `string` | No | No description available |
| `requestUri` | `string` | No | No description available |
| `baseUrl` | `string` | No | No description available |
| `basePath` | `string` | No | No description available |
| `method` | `string` | No | No description available |
| `format` | `string` | No | No description available |
| `session` | `Symfony\Component\HttpFoundation\Session\SessionInterface` \| `Closure` \| `null` | No | No description available |
| `locale` | `string` | No | No description available |
| `defaultLocale` | `string` | No | No description available |
| `formats` | `array` | No | No description available |
| `requestFactory` | `Closure` | No | No description available |
| `preferredFormat` | `string` | No | No description available |
| `isHostValid` | `bool` | No | No description available |
| `isForwardedValid` | `bool` | No | No description available |
| `isSafeContentPreferred` | `bool` | Yes | No description available |
| `trustedValuesCache` | `array` | No | No description available |
| `trustedHeaderSet` | `int` | No | No description available |
| `isIisRewrite` | `mixed` | No | No description available |


**Example Request:**

```json
{
    "trustedProxies": [
        "example_item"
    ],
    "trustedHostPatterns": [
        "example_item"
    ],
    "trustedHosts": [
        "example_item"
    ],
    "httpMethodParameterOverride": true,
    "attributes": null,
    "request": null,
    "query": null,
    "server": null,
    "files": null,
    "cookies": null,
    "headers": null,
    "content": null,
    "languages": [
        "example_item"
    ],
    "charsets": [
        "example_item"
    ],
    "encodings": [
        "example_item"
    ],
    "acceptableContentTypes": [
        "example_item"
    ],
    "pathInfo": "example_string",
    "requestUri": "example_string",
    "baseUrl": "https:\/\/example.com",
    "basePath": "example_string",
    "method": "example_string",
    "format": "example_string",
    "session": null,
    "locale": "example_string",
    "defaultLocale": "example_string",
    "formats": [
        "example_item"
    ],
    "requestFactory": null,
    "preferredFormat": "example_string",
    "isHostValid": true,
    "isForwardedValid": true,
    "isSafeContentPreferred": true,
    "trustedValuesCache": [
        "example_item"
    ],
    "trustedHeaderSet": 42,
    "isIisRewrite": null
}
```

#### Response

**JsonResponse**

Response represents an HTTP response in JSON format.

Note that this class does not force the returned JSON content to be an
object. It is however recommended that you do return an object as it
protects yourself against XSSI and JSON-JavaScript Hijacking.

| Property | Type | Required | Description |
|----------|------|----------|-------------|
| `data` | `mixed` | Yes | No description available |
| `callback` | `string` | No | No description available |
| `encodingOptions` | `int` | No | No description available |
| `headers` | `Symfony\Component\HttpFoundation\ResponseHeaderBag` | Yes | No description available |
| `content` | `string` | Yes | No description available |
| `version` | `string` | Yes | No description available |
| `statusCode` | `int` | Yes | No description available |
| `statusText` | `string` | Yes | No description available |
| `charset` | `string` | No | No description available |
| `statusTexts` | `array` | No | Status codes translation table. The list of codes is complete according to the {@link https://www... |


**Example Response:**

```json
{
    "data": null,
    "callback": "example_string",
    "encodingOptions": 42,
    "headers": null,
    "content": "example_string",
    "version": "example_string",
    "statusCode": 42,
    "statusText": "active",
    "charset": "example_string",
    "statusTexts": [
        "example_item"
    ]
}
```

---

<!-- Route 2 -->
### GET /media

Search specifically for media files

Performs targeted media search with support for media type filtering (image, video, audio).
Returns structured media results with pagination.

#### Parameters

- **request** (Symfony\Component\HttpFoundation\Request)

#### Request Body

**Request**

Request represents an HTTP request.

The methods dealing with URL accept / return a raw path (% encoded):
* getBasePath
* getBaseUrl
* getPathInfo
* getRequestUri
* getUri
* getUriForPath

| Property | Type | Required | Description |
|----------|------|----------|-------------|
| `trustedProxies` | `array` | No | No description available |
| `trustedHostPatterns` | `array` | No | No description available |
| `trustedHosts` | `array` | No | No description available |
| `httpMethodParameterOverride` | `bool` | No | No description available |
| `attributes` | `Symfony\Component\HttpFoundation\ParameterBag` | Yes | Custom parameters. |
| `request` | `Symfony\Component\HttpFoundation\InputBag` | Yes | Request body parameters ($_POST). |
| `query` | `Symfony\Component\HttpFoundation\InputBag` | Yes | Query string parameters ($_GET). |
| `server` | `Symfony\Component\HttpFoundation\ServerBag` | Yes | Server and execution environment parameters ($_SERVER). |
| `files` | `Symfony\Component\HttpFoundation\FileBag` | Yes | Uploaded files ($_FILES). |
| `cookies` | `Symfony\Component\HttpFoundation\InputBag` | Yes | Cookies ($_COOKIE). |
| `headers` | `Symfony\Component\HttpFoundation\HeaderBag` | Yes | Headers (taken from the $_SERVER). |
| `content` | `mixed` | No | No description available |
| `languages` | `array` | No | No description available |
| `charsets` | `array` | No | No description available |
| `encodings` | `array` | No | No description available |
| `acceptableContentTypes` | `array` | No | No description available |
| `pathInfo` | `string` | No | No description available |
| `requestUri` | `string` | No | No description available |
| `baseUrl` | `string` | No | No description available |
| `basePath` | `string` | No | No description available |
| `method` | `string` | No | No description available |
| `format` | `string` | No | No description available |
| `session` | `Symfony\Component\HttpFoundation\Session\SessionInterface` \| `Closure` \| `null` | No | No description available |
| `locale` | `string` | No | No description available |
| `defaultLocale` | `string` | No | No description available |
| `formats` | `array` | No | No description available |
| `requestFactory` | `Closure` | No | No description available |
| `preferredFormat` | `string` | No | No description available |
| `isHostValid` | `bool` | No | No description available |
| `isForwardedValid` | `bool` | No | No description available |
| `isSafeContentPreferred` | `bool` | Yes | No description available |
| `trustedValuesCache` | `array` | No | No description available |
| `trustedHeaderSet` | `int` | No | No description available |
| `isIisRewrite` | `mixed` | No | No description available |


**Example Request:**

```json
{
    "trustedProxies": [
        "example_item"
    ],
    "trustedHostPatterns": [
        "example_item"
    ],
    "trustedHosts": [
        "example_item"
    ],
    "httpMethodParameterOverride": true,
    "attributes": null,
    "request": null,
    "query": null,
    "server": null,
    "files": null,
    "cookies": null,
    "headers": null,
    "content": null,
    "languages": [
        "example_item"
    ],
    "charsets": [
        "example_item"
    ],
    "encodings": [
        "example_item"
    ],
    "acceptableContentTypes": [
        "example_item"
    ],
    "pathInfo": "example_string",
    "requestUri": "example_string",
    "baseUrl": "https:\/\/example.com",
    "basePath": "example_string",
    "method": "example_string",
    "format": "example_string",
    "session": null,
    "locale": "example_string",
    "defaultLocale": "example_string",
    "formats": [
        "example_item"
    ],
    "requestFactory": null,
    "preferredFormat": "example_string",
    "isHostValid": true,
    "isForwardedValid": true,
    "isSafeContentPreferred": true,
    "trustedValuesCache": [
        "example_item"
    ],
    "trustedHeaderSet": 42,
    "isIisRewrite": null
}
```

#### Response

**JsonResponse**

Response represents an HTTP response in JSON format.

Note that this class does not force the returned JSON content to be an
object. It is however recommended that you do return an object as it
protects yourself against XSSI and JSON-JavaScript Hijacking.

| Property | Type | Required | Description |
|----------|------|----------|-------------|
| `data` | `mixed` | Yes | No description available |
| `callback` | `string` | No | No description available |
| `encodingOptions` | `int` | No | No description available |
| `headers` | `Symfony\Component\HttpFoundation\ResponseHeaderBag` | Yes | No description available |
| `content` | `string` | Yes | No description available |
| `version` | `string` | Yes | No description available |
| `statusCode` | `int` | Yes | No description available |
| `statusText` | `string` | Yes | No description available |
| `charset` | `string` | No | No description available |
| `statusTexts` | `array` | No | Status codes translation table. The list of codes is complete according to the {@link https://www... |


**Example Response:**

```json
{
    "data": null,
    "callback": "example_string",
    "encodingOptions": 42,
    "headers": null,
    "content": "example_string",
    "version": "example_string",
    "statusCode": 42,
    "statusText": "active",
    "charset": "example_string",
    "statusTexts": [
        "example_item"
    ]
}
```

---

<!-- Route 3 -->
### GET /projects

Search specifically for user projects

Performs targeted project search within the user's own projects.
Returns structured project results with pagination.

#### Parameters

- **request** (Symfony\Component\HttpFoundation\Request)

#### Request Body

**Request**

Request represents an HTTP request.

The methods dealing with URL accept / return a raw path (% encoded):
* getBasePath
* getBaseUrl
* getPathInfo
* getRequestUri
* getUri
* getUriForPath

| Property | Type | Required | Description |
|----------|------|----------|-------------|
| `trustedProxies` | `array` | No | No description available |
| `trustedHostPatterns` | `array` | No | No description available |
| `trustedHosts` | `array` | No | No description available |
| `httpMethodParameterOverride` | `bool` | No | No description available |
| `attributes` | `Symfony\Component\HttpFoundation\ParameterBag` | Yes | Custom parameters. |
| `request` | `Symfony\Component\HttpFoundation\InputBag` | Yes | Request body parameters ($_POST). |
| `query` | `Symfony\Component\HttpFoundation\InputBag` | Yes | Query string parameters ($_GET). |
| `server` | `Symfony\Component\HttpFoundation\ServerBag` | Yes | Server and execution environment parameters ($_SERVER). |
| `files` | `Symfony\Component\HttpFoundation\FileBag` | Yes | Uploaded files ($_FILES). |
| `cookies` | `Symfony\Component\HttpFoundation\InputBag` | Yes | Cookies ($_COOKIE). |
| `headers` | `Symfony\Component\HttpFoundation\HeaderBag` | Yes | Headers (taken from the $_SERVER). |
| `content` | `mixed` | No | No description available |
| `languages` | `array` | No | No description available |
| `charsets` | `array` | No | No description available |
| `encodings` | `array` | No | No description available |
| `acceptableContentTypes` | `array` | No | No description available |
| `pathInfo` | `string` | No | No description available |
| `requestUri` | `string` | No | No description available |
| `baseUrl` | `string` | No | No description available |
| `basePath` | `string` | No | No description available |
| `method` | `string` | No | No description available |
| `format` | `string` | No | No description available |
| `session` | `Symfony\Component\HttpFoundation\Session\SessionInterface` \| `Closure` \| `null` | No | No description available |
| `locale` | `string` | No | No description available |
| `defaultLocale` | `string` | No | No description available |
| `formats` | `array` | No | No description available |
| `requestFactory` | `Closure` | No | No description available |
| `preferredFormat` | `string` | No | No description available |
| `isHostValid` | `bool` | No | No description available |
| `isForwardedValid` | `bool` | No | No description available |
| `isSafeContentPreferred` | `bool` | Yes | No description available |
| `trustedValuesCache` | `array` | No | No description available |
| `trustedHeaderSet` | `int` | No | No description available |
| `isIisRewrite` | `mixed` | No | No description available |


**Example Request:**

```json
{
    "trustedProxies": [
        "example_item"
    ],
    "trustedHostPatterns": [
        "example_item"
    ],
    "trustedHosts": [
        "example_item"
    ],
    "httpMethodParameterOverride": true,
    "attributes": null,
    "request": null,
    "query": null,
    "server": null,
    "files": null,
    "cookies": null,
    "headers": null,
    "content": null,
    "languages": [
        "example_item"
    ],
    "charsets": [
        "example_item"
    ],
    "encodings": [
        "example_item"
    ],
    "acceptableContentTypes": [
        "example_item"
    ],
    "pathInfo": "example_string",
    "requestUri": "example_string",
    "baseUrl": "https:\/\/example.com",
    "basePath": "example_string",
    "method": "example_string",
    "format": "example_string",
    "session": null,
    "locale": "example_string",
    "defaultLocale": "example_string",
    "formats": [
        "example_item"
    ],
    "requestFactory": null,
    "preferredFormat": "example_string",
    "isHostValid": true,
    "isForwardedValid": true,
    "isSafeContentPreferred": true,
    "trustedValuesCache": [
        "example_item"
    ],
    "trustedHeaderSet": 42,
    "isIisRewrite": null
}
```

#### Response

**JsonResponse**

Response represents an HTTP response in JSON format.

Note that this class does not force the returned JSON content to be an
object. It is however recommended that you do return an object as it
protects yourself against XSSI and JSON-JavaScript Hijacking.

| Property | Type | Required | Description |
|----------|------|----------|-------------|
| `data` | `mixed` | Yes | No description available |
| `callback` | `string` | No | No description available |
| `encodingOptions` | `int` | No | No description available |
| `headers` | `Symfony\Component\HttpFoundation\ResponseHeaderBag` | Yes | No description available |
| `content` | `string` | Yes | No description available |
| `version` | `string` | Yes | No description available |
| `statusCode` | `int` | Yes | No description available |
| `statusText` | `string` | Yes | No description available |
| `charset` | `string` | No | No description available |
| `statusTexts` | `array` | No | Status codes translation table. The list of codes is complete according to the {@link https://www... |


**Example Response:**

```json
{
    "data": null,
    "callback": "example_string",
    "encodingOptions": 42,
    "headers": null,
    "content": "example_string",
    "version": "example_string",
    "statusCode": 42,
    "statusText": "active",
    "charset": "example_string",
    "statusTexts": [
        "example_item"
    ]
}
```

---

<!-- Route 4 -->
### GET /suggestions

Get search suggestions based on user query

Returns search suggestions to help users find relevant content.
Used for autocomplete and search assistance features.

#### Parameters

- **request** (Symfony\Component\HttpFoundation\Request)

#### Request Body

**Request**

Request represents an HTTP request.

The methods dealing with URL accept / return a raw path (% encoded):
* getBasePath
* getBaseUrl
* getPathInfo
* getRequestUri
* getUri
* getUriForPath

| Property | Type | Required | Description |
|----------|------|----------|-------------|
| `trustedProxies` | `array` | No | No description available |
| `trustedHostPatterns` | `array` | No | No description available |
| `trustedHosts` | `array` | No | No description available |
| `httpMethodParameterOverride` | `bool` | No | No description available |
| `attributes` | `Symfony\Component\HttpFoundation\ParameterBag` | Yes | Custom parameters. |
| `request` | `Symfony\Component\HttpFoundation\InputBag` | Yes | Request body parameters ($_POST). |
| `query` | `Symfony\Component\HttpFoundation\InputBag` | Yes | Query string parameters ($_GET). |
| `server` | `Symfony\Component\HttpFoundation\ServerBag` | Yes | Server and execution environment parameters ($_SERVER). |
| `files` | `Symfony\Component\HttpFoundation\FileBag` | Yes | Uploaded files ($_FILES). |
| `cookies` | `Symfony\Component\HttpFoundation\InputBag` | Yes | Cookies ($_COOKIE). |
| `headers` | `Symfony\Component\HttpFoundation\HeaderBag` | Yes | Headers (taken from the $_SERVER). |
| `content` | `mixed` | No | No description available |
| `languages` | `array` | No | No description available |
| `charsets` | `array` | No | No description available |
| `encodings` | `array` | No | No description available |
| `acceptableContentTypes` | `array` | No | No description available |
| `pathInfo` | `string` | No | No description available |
| `requestUri` | `string` | No | No description available |
| `baseUrl` | `string` | No | No description available |
| `basePath` | `string` | No | No description available |
| `method` | `string` | No | No description available |
| `format` | `string` | No | No description available |
| `session` | `Symfony\Component\HttpFoundation\Session\SessionInterface` \| `Closure` \| `null` | No | No description available |
| `locale` | `string` | No | No description available |
| `defaultLocale` | `string` | No | No description available |
| `formats` | `array` | No | No description available |
| `requestFactory` | `Closure` | No | No description available |
| `preferredFormat` | `string` | No | No description available |
| `isHostValid` | `bool` | No | No description available |
| `isForwardedValid` | `bool` | No | No description available |
| `isSafeContentPreferred` | `bool` | Yes | No description available |
| `trustedValuesCache` | `array` | No | No description available |
| `trustedHeaderSet` | `int` | No | No description available |
| `isIisRewrite` | `mixed` | No | No description available |


**Example Request:**

```json
{
    "trustedProxies": [
        "example_item"
    ],
    "trustedHostPatterns": [
        "example_item"
    ],
    "trustedHosts": [
        "example_item"
    ],
    "httpMethodParameterOverride": true,
    "attributes": null,
    "request": null,
    "query": null,
    "server": null,
    "files": null,
    "cookies": null,
    "headers": null,
    "content": null,
    "languages": [
        "example_item"
    ],
    "charsets": [
        "example_item"
    ],
    "encodings": [
        "example_item"
    ],
    "acceptableContentTypes": [
        "example_item"
    ],
    "pathInfo": "example_string",
    "requestUri": "example_string",
    "baseUrl": "https:\/\/example.com",
    "basePath": "example_string",
    "method": "example_string",
    "format": "example_string",
    "session": null,
    "locale": "example_string",
    "defaultLocale": "example_string",
    "formats": [
        "example_item"
    ],
    "requestFactory": null,
    "preferredFormat": "example_string",
    "isHostValid": true,
    "isForwardedValid": true,
    "isSafeContentPreferred": true,
    "trustedValuesCache": [
        "example_item"
    ],
    "trustedHeaderSet": 42,
    "isIisRewrite": null
}
```

#### Response

**JsonResponse**

Response represents an HTTP response in JSON format.

Note that this class does not force the returned JSON content to be an
object. It is however recommended that you do return an object as it
protects yourself against XSSI and JSON-JavaScript Hijacking.

| Property | Type | Required | Description |
|----------|------|----------|-------------|
| `data` | `mixed` | Yes | No description available |
| `callback` | `string` | No | No description available |
| `encodingOptions` | `int` | No | No description available |
| `headers` | `Symfony\Component\HttpFoundation\ResponseHeaderBag` | Yes | No description available |
| `content` | `string` | Yes | No description available |
| `version` | `string` | Yes | No description available |
| `statusCode` | `int` | Yes | No description available |
| `statusText` | `string` | Yes | No description available |
| `charset` | `string` | No | No description available |
| `statusTexts` | `array` | No | Status codes translation table. The list of codes is complete according to the {@link https://www... |


**Example Response:**

```json
{
    "data": null,
    "callback": "example_string",
    "encodingOptions": 42,
    "headers": null,
    "content": "example_string",
    "version": "example_string",
    "statusCode": 42,
    "statusText": "active",
    "charset": "example_string",
    "statusTexts": [
        "example_item"
    ]
}
```

---

<!-- Route 5 -->
### GET /templates

Search specifically for templates

Performs targeted template search with support for category and tag filtering.
Returns structured template results with pagination.

#### Parameters

- **request** (Symfony\Component\HttpFoundation\Request)

#### Request Body

**Request**

Request represents an HTTP request.

The methods dealing with URL accept / return a raw path (% encoded):
* getBasePath
* getBaseUrl
* getPathInfo
* getRequestUri
* getUri
* getUriForPath

| Property | Type | Required | Description |
|----------|------|----------|-------------|
| `trustedProxies` | `array` | No | No description available |
| `trustedHostPatterns` | `array` | No | No description available |
| `trustedHosts` | `array` | No | No description available |
| `httpMethodParameterOverride` | `bool` | No | No description available |
| `attributes` | `Symfony\Component\HttpFoundation\ParameterBag` | Yes | Custom parameters. |
| `request` | `Symfony\Component\HttpFoundation\InputBag` | Yes | Request body parameters ($_POST). |
| `query` | `Symfony\Component\HttpFoundation\InputBag` | Yes | Query string parameters ($_GET). |
| `server` | `Symfony\Component\HttpFoundation\ServerBag` | Yes | Server and execution environment parameters ($_SERVER). |
| `files` | `Symfony\Component\HttpFoundation\FileBag` | Yes | Uploaded files ($_FILES). |
| `cookies` | `Symfony\Component\HttpFoundation\InputBag` | Yes | Cookies ($_COOKIE). |
| `headers` | `Symfony\Component\HttpFoundation\HeaderBag` | Yes | Headers (taken from the $_SERVER). |
| `content` | `mixed` | No | No description available |
| `languages` | `array` | No | No description available |
| `charsets` | `array` | No | No description available |
| `encodings` | `array` | No | No description available |
| `acceptableContentTypes` | `array` | No | No description available |
| `pathInfo` | `string` | No | No description available |
| `requestUri` | `string` | No | No description available |
| `baseUrl` | `string` | No | No description available |
| `basePath` | `string` | No | No description available |
| `method` | `string` | No | No description available |
| `format` | `string` | No | No description available |
| `session` | `Symfony\Component\HttpFoundation\Session\SessionInterface` \| `Closure` \| `null` | No | No description available |
| `locale` | `string` | No | No description available |
| `defaultLocale` | `string` | No | No description available |
| `formats` | `array` | No | No description available |
| `requestFactory` | `Closure` | No | No description available |
| `preferredFormat` | `string` | No | No description available |
| `isHostValid` | `bool` | No | No description available |
| `isForwardedValid` | `bool` | No | No description available |
| `isSafeContentPreferred` | `bool` | Yes | No description available |
| `trustedValuesCache` | `array` | No | No description available |
| `trustedHeaderSet` | `int` | No | No description available |
| `isIisRewrite` | `mixed` | No | No description available |


**Example Request:**

```json
{
    "trustedProxies": [
        "example_item"
    ],
    "trustedHostPatterns": [
        "example_item"
    ],
    "trustedHosts": [
        "example_item"
    ],
    "httpMethodParameterOverride": true,
    "attributes": null,
    "request": null,
    "query": null,
    "server": null,
    "files": null,
    "cookies": null,
    "headers": null,
    "content": null,
    "languages": [
        "example_item"
    ],
    "charsets": [
        "example_item"
    ],
    "encodings": [
        "example_item"
    ],
    "acceptableContentTypes": [
        "example_item"
    ],
    "pathInfo": "example_string",
    "requestUri": "example_string",
    "baseUrl": "https:\/\/example.com",
    "basePath": "example_string",
    "method": "example_string",
    "format": "example_string",
    "session": null,
    "locale": "example_string",
    "defaultLocale": "example_string",
    "formats": [
        "example_item"
    ],
    "requestFactory": null,
    "preferredFormat": "example_string",
    "isHostValid": true,
    "isForwardedValid": true,
    "isSafeContentPreferred": true,
    "trustedValuesCache": [
        "example_item"
    ],
    "trustedHeaderSet": 42,
    "isIisRewrite": null
}
```

#### Response

**JsonResponse**

Response represents an HTTP response in JSON format.

Note that this class does not force the returned JSON content to be an
object. It is however recommended that you do return an object as it
protects yourself against XSSI and JSON-JavaScript Hijacking.

| Property | Type | Required | Description |
|----------|------|----------|-------------|
| `data` | `mixed` | Yes | No description available |
| `callback` | `string` | No | No description available |
| `encodingOptions` | `int` | No | No description available |
| `headers` | `Symfony\Component\HttpFoundation\ResponseHeaderBag` | Yes | No description available |
| `content` | `string` | Yes | No description available |
| `version` | `string` | Yes | No description available |
| `statusCode` | `int` | Yes | No description available |
| `statusText` | `string` | Yes | No description available |
| `charset` | `string` | No | No description available |
| `statusTexts` | `array` | No | Status codes translation table. The list of codes is complete according to the {@link https://www... |


**Example Response:**

```json
{
    "data": null,
    "callback": "example_string",
    "encodingOptions": 42,
    "headers": null,
    "content": "example_string",
    "version": "example_string",
    "statusCode": 42,
    "statusText": "active",
    "charset": "example_string",
    "statusTexts": [
        "example_item"
    ]
}
```

---

## PluginController

*12 routes*

Plugin Controller

Manages the plugin system including plugin registration, approval, installation, and management.
Handles plugin file uploads, metadata management, and lifecycle operations.
Provides marketplace functionality for plugin discovery and category management.
Includes admin approval workflow and user plugin management features.

<!-- Route 1 -->
### GET 

Retrieve paginated list of plugins with filtering options

Supports filtering by category, search terms, status, and sorting.
Returns paginated results with plugin metadata.

#### Parameters

- **request** (Symfony\Component\HttpFoundation\Request)

#### Request Body

**Request**

Request represents an HTTP request.

The methods dealing with URL accept / return a raw path (% encoded):
* getBasePath
* getBaseUrl
* getPathInfo
* getRequestUri
* getUri
* getUriForPath

| Property | Type | Required | Description |
|----------|------|----------|-------------|
| `trustedProxies` | `array` | No | No description available |
| `trustedHostPatterns` | `array` | No | No description available |
| `trustedHosts` | `array` | No | No description available |
| `httpMethodParameterOverride` | `bool` | No | No description available |
| `attributes` | `Symfony\Component\HttpFoundation\ParameterBag` | Yes | Custom parameters. |
| `request` | `Symfony\Component\HttpFoundation\InputBag` | Yes | Request body parameters ($_POST). |
| `query` | `Symfony\Component\HttpFoundation\InputBag` | Yes | Query string parameters ($_GET). |
| `server` | `Symfony\Component\HttpFoundation\ServerBag` | Yes | Server and execution environment parameters ($_SERVER). |
| `files` | `Symfony\Component\HttpFoundation\FileBag` | Yes | Uploaded files ($_FILES). |
| `cookies` | `Symfony\Component\HttpFoundation\InputBag` | Yes | Cookies ($_COOKIE). |
| `headers` | `Symfony\Component\HttpFoundation\HeaderBag` | Yes | Headers (taken from the $_SERVER). |
| `content` | `mixed` | No | No description available |
| `languages` | `array` | No | No description available |
| `charsets` | `array` | No | No description available |
| `encodings` | `array` | No | No description available |
| `acceptableContentTypes` | `array` | No | No description available |
| `pathInfo` | `string` | No | No description available |
| `requestUri` | `string` | No | No description available |
| `baseUrl` | `string` | No | No description available |
| `basePath` | `string` | No | No description available |
| `method` | `string` | No | No description available |
| `format` | `string` | No | No description available |
| `session` | `Symfony\Component\HttpFoundation\Session\SessionInterface` \| `Closure` \| `null` | No | No description available |
| `locale` | `string` | No | No description available |
| `defaultLocale` | `string` | No | No description available |
| `formats` | `array` | No | No description available |
| `requestFactory` | `Closure` | No | No description available |
| `preferredFormat` | `string` | No | No description available |
| `isHostValid` | `bool` | No | No description available |
| `isForwardedValid` | `bool` | No | No description available |
| `isSafeContentPreferred` | `bool` | Yes | No description available |
| `trustedValuesCache` | `array` | No | No description available |
| `trustedHeaderSet` | `int` | No | No description available |
| `isIisRewrite` | `mixed` | No | No description available |


**Example Request:**

```json
{
    "trustedProxies": [
        "example_item"
    ],
    "trustedHostPatterns": [
        "example_item"
    ],
    "trustedHosts": [
        "example_item"
    ],
    "httpMethodParameterOverride": true,
    "attributes": null,
    "request": null,
    "query": null,
    "server": null,
    "files": null,
    "cookies": null,
    "headers": null,
    "content": null,
    "languages": [
        "example_item"
    ],
    "charsets": [
        "example_item"
    ],
    "encodings": [
        "example_item"
    ],
    "acceptableContentTypes": [
        "example_item"
    ],
    "pathInfo": "example_string",
    "requestUri": "example_string",
    "baseUrl": "https:\/\/example.com",
    "basePath": "example_string",
    "method": "example_string",
    "format": "example_string",
    "session": null,
    "locale": "example_string",
    "defaultLocale": "example_string",
    "formats": [
        "example_item"
    ],
    "requestFactory": null,
    "preferredFormat": "example_string",
    "isHostValid": true,
    "isForwardedValid": true,
    "isSafeContentPreferred": true,
    "trustedValuesCache": [
        "example_item"
    ],
    "trustedHeaderSet": 42,
    "isIisRewrite": null
}
```

#### Response

**JsonResponse**

Response represents an HTTP response in JSON format.

Note that this class does not force the returned JSON content to be an
object. It is however recommended that you do return an object as it
protects yourself against XSSI and JSON-JavaScript Hijacking.

| Property | Type | Required | Description |
|----------|------|----------|-------------|
| `data` | `mixed` | Yes | No description available |
| `callback` | `string` | No | No description available |
| `encodingOptions` | `int` | No | No description available |
| `headers` | `Symfony\Component\HttpFoundation\ResponseHeaderBag` | Yes | No description available |
| `content` | `string` | Yes | No description available |
| `version` | `string` | Yes | No description available |
| `statusCode` | `int` | Yes | No description available |
| `statusText` | `string` | Yes | No description available |
| `charset` | `string` | No | No description available |
| `statusTexts` | `array` | No | Status codes translation table. The list of codes is complete according to the {@link https://www... |


**Example Response:**

```json
{
    "data": null,
    "callback": "example_string",
    "encodingOptions": 42,
    "headers": null,
    "content": "example_string",
    "version": "example_string",
    "statusCode": 42,
    "statusText": "active",
    "charset": "example_string",
    "statusTexts": [
        "example_item"
    ]
}
```

---

<!-- Route 2 -->
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

| Property | Type | Required | Description |
|----------|------|----------|-------------|
| `name` | `string` | Yes | Display name of the plugin. Human-readable name shown in the plugin marketplace and installation ... *(Required, Min length: 2, Max length: 100)* |
| `description` | `string` | Yes | Detailed description of the plugin's functionality. Comprehensive explanation of what the plugin ... *(Required, Min length: 10, Max length: 1000)* |
| `categories` | `array` | Yes | Categories that classify the plugin's functionality. Array of category names that help users disc... *(Required, Required)* |
| `version` | `string` | Yes | Semantic version number of the plugin. Version identifier following semantic versioning (semver) ... *(Required)* |
| `permissions` | `array` | Yes | Required permissions for the plugin to function. Array of permission types that the plugin needs ... *(Required)* |
| `manifest` | `array` | Yes | Plugin manifest configuration. JSON-formatted configuration containing plugin metadata, entry poi... *(Required)* |


**Example Request:**

```json
{
    "name": "Example Name",
    "description": "This is an example description.",
    "categories": [
        "example_item"
    ],
    "version": "example_string",
    "permissions": [
        "example_item"
    ],
    "manifest": [
        "example_item"
    ]
}
```

#### Response

**JsonResponse**

Response represents an HTTP response in JSON format.

Note that this class does not force the returned JSON content to be an
object. It is however recommended that you do return an object as it
protects yourself against XSSI and JSON-JavaScript Hijacking.

| Property | Type | Required | Description |
|----------|------|----------|-------------|
| `data` | `mixed` | Yes | No description available |
| `callback` | `string` | No | No description available |
| `encodingOptions` | `int` | No | No description available |
| `headers` | `Symfony\Component\HttpFoundation\ResponseHeaderBag` | Yes | No description available |
| `content` | `string` | Yes | No description available |
| `version` | `string` | Yes | No description available |
| `statusCode` | `int` | Yes | No description available |
| `statusText` | `string` | Yes | No description available |
| `charset` | `string` | No | No description available |
| `statusTexts` | `array` | No | Status codes translation table. The list of codes is complete according to the {@link https://www... |


**Example Response:**

```json
{
    "data": null,
    "callback": "example_string",
    "encodingOptions": 42,
    "headers": null,
    "content": "example_string",
    "version": "example_string",
    "statusCode": 42,
    "statusText": "active",
    "charset": "example_string",
    "statusTexts": [
        "example_item"
    ]
}
```

---

<!-- Route 3 -->
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

| Property | Type | Required | Description |
|----------|------|----------|-------------|
| `data` | `mixed` | Yes | No description available |
| `callback` | `string` | No | No description available |
| `encodingOptions` | `int` | No | No description available |
| `headers` | `Symfony\Component\HttpFoundation\ResponseHeaderBag` | Yes | No description available |
| `content` | `string` | Yes | No description available |
| `version` | `string` | Yes | No description available |
| `statusCode` | `int` | Yes | No description available |
| `statusText` | `string` | Yes | No description available |
| `charset` | `string` | No | No description available |
| `statusTexts` | `array` | No | Status codes translation table. The list of codes is complete according to the {@link https://www... |


**Example Response:**

```json
{
    "data": null,
    "callback": "example_string",
    "encodingOptions": 42,
    "headers": null,
    "content": "example_string",
    "version": "example_string",
    "statusCode": 42,
    "statusText": "active",
    "charset": "example_string",
    "statusTexts": [
        "example_item"
    ]
}
```

---

<!-- Route 4 -->
### GET /my-plugins

**Security:** IsGranted

Get current user's plugins

Returns paginated list of plugins created by the authenticated user,
including all statuses (pending, approved, rejected).

#### Parameters

- **request** (Symfony\Component\HttpFoundation\Request)

#### Request Body

**Request**

Request represents an HTTP request.

The methods dealing with URL accept / return a raw path (% encoded):
* getBasePath
* getBaseUrl
* getPathInfo
* getRequestUri
* getUri
* getUriForPath

| Property | Type | Required | Description |
|----------|------|----------|-------------|
| `trustedProxies` | `array` | No | No description available |
| `trustedHostPatterns` | `array` | No | No description available |
| `trustedHosts` | `array` | No | No description available |
| `httpMethodParameterOverride` | `bool` | No | No description available |
| `attributes` | `Symfony\Component\HttpFoundation\ParameterBag` | Yes | Custom parameters. |
| `request` | `Symfony\Component\HttpFoundation\InputBag` | Yes | Request body parameters ($_POST). |
| `query` | `Symfony\Component\HttpFoundation\InputBag` | Yes | Query string parameters ($_GET). |
| `server` | `Symfony\Component\HttpFoundation\ServerBag` | Yes | Server and execution environment parameters ($_SERVER). |
| `files` | `Symfony\Component\HttpFoundation\FileBag` | Yes | Uploaded files ($_FILES). |
| `cookies` | `Symfony\Component\HttpFoundation\InputBag` | Yes | Cookies ($_COOKIE). |
| `headers` | `Symfony\Component\HttpFoundation\HeaderBag` | Yes | Headers (taken from the $_SERVER). |
| `content` | `mixed` | No | No description available |
| `languages` | `array` | No | No description available |
| `charsets` | `array` | No | No description available |
| `encodings` | `array` | No | No description available |
| `acceptableContentTypes` | `array` | No | No description available |
| `pathInfo` | `string` | No | No description available |
| `requestUri` | `string` | No | No description available |
| `baseUrl` | `string` | No | No description available |
| `basePath` | `string` | No | No description available |
| `method` | `string` | No | No description available |
| `format` | `string` | No | No description available |
| `session` | `Symfony\Component\HttpFoundation\Session\SessionInterface` \| `Closure` \| `null` | No | No description available |
| `locale` | `string` | No | No description available |
| `defaultLocale` | `string` | No | No description available |
| `formats` | `array` | No | No description available |
| `requestFactory` | `Closure` | No | No description available |
| `preferredFormat` | `string` | No | No description available |
| `isHostValid` | `bool` | No | No description available |
| `isForwardedValid` | `bool` | No | No description available |
| `isSafeContentPreferred` | `bool` | Yes | No description available |
| `trustedValuesCache` | `array` | No | No description available |
| `trustedHeaderSet` | `int` | No | No description available |
| `isIisRewrite` | `mixed` | No | No description available |


**Example Request:**

```json
{
    "trustedProxies": [
        "example_item"
    ],
    "trustedHostPatterns": [
        "example_item"
    ],
    "trustedHosts": [
        "example_item"
    ],
    "httpMethodParameterOverride": true,
    "attributes": null,
    "request": null,
    "query": null,
    "server": null,
    "files": null,
    "cookies": null,
    "headers": null,
    "content": null,
    "languages": [
        "example_item"
    ],
    "charsets": [
        "example_item"
    ],
    "encodings": [
        "example_item"
    ],
    "acceptableContentTypes": [
        "example_item"
    ],
    "pathInfo": "example_string",
    "requestUri": "example_string",
    "baseUrl": "https:\/\/example.com",
    "basePath": "example_string",
    "method": "example_string",
    "format": "example_string",
    "session": null,
    "locale": "example_string",
    "defaultLocale": "example_string",
    "formats": [
        "example_item"
    ],
    "requestFactory": null,
    "preferredFormat": "example_string",
    "isHostValid": true,
    "isForwardedValid": true,
    "isSafeContentPreferred": true,
    "trustedValuesCache": [
        "example_item"
    ],
    "trustedHeaderSet": 42,
    "isIisRewrite": null
}
```

#### Response

**JsonResponse**

Response represents an HTTP response in JSON format.

Note that this class does not force the returned JSON content to be an
object. It is however recommended that you do return an object as it
protects yourself against XSSI and JSON-JavaScript Hijacking.

| Property | Type | Required | Description |
|----------|------|----------|-------------|
| `data` | `mixed` | Yes | No description available |
| `callback` | `string` | No | No description available |
| `encodingOptions` | `int` | No | No description available |
| `headers` | `Symfony\Component\HttpFoundation\ResponseHeaderBag` | Yes | No description available |
| `content` | `string` | Yes | No description available |
| `version` | `string` | Yes | No description available |
| `statusCode` | `int` | Yes | No description available |
| `statusText` | `string` | Yes | No description available |
| `charset` | `string` | No | No description available |
| `statusTexts` | `array` | No | Status codes translation table. The list of codes is complete according to the {@link https://www... |


**Example Response:**

```json
{
    "data": null,
    "callback": "example_string",
    "encodingOptions": 42,
    "headers": null,
    "content": "example_string",
    "version": "example_string",
    "statusCode": 42,
    "statusText": "active",
    "charset": "example_string",
    "statusTexts": [
        "example_item"
    ]
}
```

---

<!-- Route 5 -->
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

| Property | Type | Required | Description |
|----------|------|----------|-------------|
| `data` | `mixed` | Yes | No description available |
| `callback` | `string` | No | No description available |
| `encodingOptions` | `int` | No | No description available |
| `headers` | `Symfony\Component\HttpFoundation\ResponseHeaderBag` | Yes | No description available |
| `content` | `string` | Yes | No description available |
| `version` | `string` | Yes | No description available |
| `statusCode` | `int` | Yes | No description available |
| `statusText` | `string` | Yes | No description available |
| `charset` | `string` | No | No description available |
| `statusTexts` | `array` | No | Status codes translation table. The list of codes is complete according to the {@link https://www... |


**Example Response:**

```json
{
    "data": null,
    "callback": "example_string",
    "encodingOptions": 42,
    "headers": null,
    "content": "example_string",
    "version": "example_string",
    "statusCode": 42,
    "statusText": "active",
    "charset": "example_string",
    "statusTexts": [
        "example_item"
    ]
}
```

---

<!-- Route 6 -->
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

| Property | Type | Required | Description |
|----------|------|----------|-------------|
| `name` | `string` | No | Updated display name of the plugin. If provided, replaces the current plugin name. Must be descri... *(Min length: 2, Max length: 100)* |
| `description` | `string` | No | Updated description of the plugin's functionality. If provided, replaces the current description.... *(Min length: 10, Max length: 1000)* |
| `categories` | `array` | No | Updated categories for plugin classification. If provided, replaces the current category set. Cat... *(Required)* |
| `version` | `string` | No | Updated semantic version number. If provided, updates the plugin version. Must follow semantic ve... |
| `permissions` | `array` | No | Updated permission requirements. If provided, replaces the current permission set. Each permissio... |
| `manifest` | `array` | No | Updated plugin manifest configuration. If provided, replaces the current manifest. Contains plugi... |
| `status` | `string` | No | Updated approval status for the plugin. If provided, changes the plugin's approval status in the ... *(Choices: 'pending', 'approved', 'rejected')* |


**Example Request:**

```json
{
    "name": null,
    "description": null,
    "categories": null,
    "version": null,
    "permissions": null,
    "manifest": null,
    "status": null
}
```

#### Response

**JsonResponse**

Response represents an HTTP response in JSON format.

Note that this class does not force the returned JSON content to be an
object. It is however recommended that you do return an object as it
protects yourself against XSSI and JSON-JavaScript Hijacking.

| Property | Type | Required | Description |
|----------|------|----------|-------------|
| `data` | `mixed` | Yes | No description available |
| `callback` | `string` | No | No description available |
| `encodingOptions` | `int` | No | No description available |
| `headers` | `Symfony\Component\HttpFoundation\ResponseHeaderBag` | Yes | No description available |
| `content` | `string` | Yes | No description available |
| `version` | `string` | Yes | No description available |
| `statusCode` | `int` | Yes | No description available |
| `statusText` | `string` | Yes | No description available |
| `charset` | `string` | No | No description available |
| `statusTexts` | `array` | No | Status codes translation table. The list of codes is complete according to the {@link https://www... |


**Example Response:**

```json
{
    "data": null,
    "callback": "example_string",
    "encodingOptions": 42,
    "headers": null,
    "content": "example_string",
    "version": "example_string",
    "statusCode": 42,
    "statusText": "active",
    "charset": "example_string",
    "statusTexts": [
        "example_item"
    ]
}
```

---

<!-- Route 7 -->
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

| Property | Type | Required | Description |
|----------|------|----------|-------------|
| `data` | `mixed` | Yes | No description available |
| `callback` | `string` | No | No description available |
| `encodingOptions` | `int` | No | No description available |
| `headers` | `Symfony\Component\HttpFoundation\ResponseHeaderBag` | Yes | No description available |
| `content` | `string` | Yes | No description available |
| `version` | `string` | Yes | No description available |
| `statusCode` | `int` | Yes | No description available |
| `statusText` | `string` | Yes | No description available |
| `charset` | `string` | No | No description available |
| `statusTexts` | `array` | No | Status codes translation table. The list of codes is complete according to the {@link https://www... |


**Example Response:**

```json
{
    "data": null,
    "callback": "example_string",
    "encodingOptions": 42,
    "headers": null,
    "content": "example_string",
    "version": "example_string",
    "statusCode": 42,
    "statusText": "active",
    "charset": "example_string",
    "statusTexts": [
        "example_item"
    ]
}
```

---

<!-- Route 8 -->
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

| Property | Type | Required | Description |
|----------|------|----------|-------------|
| `data` | `mixed` | Yes | No description available |
| `callback` | `string` | No | No description available |
| `encodingOptions` | `int` | No | No description available |
| `headers` | `Symfony\Component\HttpFoundation\ResponseHeaderBag` | Yes | No description available |
| `content` | `string` | Yes | No description available |
| `version` | `string` | Yes | No description available |
| `statusCode` | `int` | Yes | No description available |
| `statusText` | `string` | Yes | No description available |
| `charset` | `string` | No | No description available |
| `statusTexts` | `array` | No | Status codes translation table. The list of codes is complete according to the {@link https://www... |


**Example Response:**

```json
{
    "data": null,
    "callback": "example_string",
    "encodingOptions": 42,
    "headers": null,
    "content": "example_string",
    "version": "example_string",
    "statusCode": 42,
    "statusText": "active",
    "charset": "example_string",
    "statusTexts": [
        "example_item"
    ]
}
```

---

<!-- Route 9 -->
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

| Property | Type | Required | Description |
|----------|------|----------|-------------|
| `data` | `mixed` | Yes | No description available |
| `callback` | `string` | No | No description available |
| `encodingOptions` | `int` | No | No description available |
| `headers` | `Symfony\Component\HttpFoundation\ResponseHeaderBag` | Yes | No description available |
| `content` | `string` | Yes | No description available |
| `version` | `string` | Yes | No description available |
| `statusCode` | `int` | Yes | No description available |
| `statusText` | `string` | Yes | No description available |
| `charset` | `string` | No | No description available |
| `statusTexts` | `array` | No | Status codes translation table. The list of codes is complete according to the {@link https://www... |


**Example Response:**

```json
{
    "data": null,
    "callback": "example_string",
    "encodingOptions": 42,
    "headers": null,
    "content": "example_string",
    "version": "example_string",
    "statusCode": 42,
    "statusText": "active",
    "charset": "example_string",
    "statusTexts": [
        "example_item"
    ]
}
```

---

<!-- Route 10 -->
### POST /{id}/reject

**Security:** IsGranted

Reject a plugin (Admin only)

Changes plugin status to rejected with a provided reason.
Records rejection timestamp and reviewing admin.

#### Parameters

- **plugin** (App\Entity\Plugin)

#### Request Body

**RejectPluginRequestDTO**

| Property | Type | Required | Description |
|----------|------|----------|-------------|
| `reason` | `string` | Yes | No description available *(Required, Min length: 10, Max length: 500)* |


**Example Request:**

```json
{
    "reason": "example_string"
}
```

#### Response

**JsonResponse**

Response represents an HTTP response in JSON format.

Note that this class does not force the returned JSON content to be an
object. It is however recommended that you do return an object as it
protects yourself against XSSI and JSON-JavaScript Hijacking.

| Property | Type | Required | Description |
|----------|------|----------|-------------|
| `data` | `mixed` | Yes | No description available |
| `callback` | `string` | No | No description available |
| `encodingOptions` | `int` | No | No description available |
| `headers` | `Symfony\Component\HttpFoundation\ResponseHeaderBag` | Yes | No description available |
| `content` | `string` | Yes | No description available |
| `version` | `string` | Yes | No description available |
| `statusCode` | `int` | Yes | No description available |
| `statusText` | `string` | Yes | No description available |
| `charset` | `string` | No | No description available |
| `statusTexts` | `array` | No | Status codes translation table. The list of codes is complete according to the {@link https://www... |


**Example Response:**

```json
{
    "data": null,
    "callback": "example_string",
    "encodingOptions": 42,
    "headers": null,
    "content": "example_string",
    "version": "example_string",
    "statusCode": 42,
    "statusText": "active",
    "charset": "example_string",
    "statusTexts": [
        "example_item"
    ]
}
```

---

<!-- Route 11 -->
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

| Property | Type | Required | Description |
|----------|------|----------|-------------|
| `data` | `mixed` | Yes | No description available |
| `callback` | `string` | No | No description available |
| `encodingOptions` | `int` | No | No description available |
| `headers` | `Symfony\Component\HttpFoundation\ResponseHeaderBag` | Yes | No description available |
| `content` | `string` | Yes | No description available |
| `version` | `string` | Yes | No description available |
| `statusCode` | `int` | Yes | No description available |
| `statusText` | `string` | Yes | No description available |
| `charset` | `string` | No | No description available |
| `statusTexts` | `array` | No | Status codes translation table. The list of codes is complete according to the {@link https://www... |


**Example Response:**

```json
{
    "data": null,
    "callback": "example_string",
    "encodingOptions": 42,
    "headers": null,
    "content": "example_string",
    "version": "example_string",
    "statusCode": 42,
    "statusText": "active",
    "charset": "example_string",
    "statusTexts": [
        "example_item"
    ]
}
```

---

<!-- Route 12 -->
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

| Property | Type | Required | Description |
|----------|------|----------|-------------|
| `file` | `Symfony\Component\HttpFoundation\File\UploadedFile` | No | Data Transfer Object for plugin file upload requests. Handles validation and encapsulation of plu... |


**Example Request:**

```json
{
    "file": null
}
```

#### Response

**JsonResponse**

Response represents an HTTP response in JSON format.

Note that this class does not force the returned JSON content to be an
object. It is however recommended that you do return an object as it
protects yourself against XSSI and JSON-JavaScript Hijacking.

| Property | Type | Required | Description |
|----------|------|----------|-------------|
| `data` | `mixed` | Yes | No description available |
| `callback` | `string` | No | No description available |
| `encodingOptions` | `int` | No | No description available |
| `headers` | `Symfony\Component\HttpFoundation\ResponseHeaderBag` | Yes | No description available |
| `content` | `string` | Yes | No description available |
| `version` | `string` | Yes | No description available |
| `statusCode` | `int` | Yes | No description available |
| `statusText` | `string` | Yes | No description available |
| `charset` | `string` | No | No description available |
| `statusTexts` | `array` | No | Status codes translation table. The list of codes is complete according to the {@link https://www... |


**Example Response:**

```json
{
    "data": null,
    "callback": "example_string",
    "encodingOptions": 42,
    "headers": null,
    "content": "example_string",
    "version": "example_string",
    "statusCode": 42,
    "statusText": "active",
    "charset": "example_string",
    "statusTexts": [
        "example_item"
    ]
}
```

---

## UserController

*8 routes*

User Controller

Manages user account operations including profile management, avatar uploads,
password changes, privacy settings, and subscription information.
Handles personal data export/download and account deletion functionality.
All endpoints require authentication and operate on the current user's data.

<!-- Route 1 -->
### POST /avatar

Upload and update user avatar image

Handles avatar file upload, validation, and updates user profile.
Automatically removes old avatar file and returns new avatar URL.

#### Request Body

**UploadAvatarRequestDTO**

Data Transfer Object for avatar upload requests.

Handles validation and encapsulation of avatar file upload data including
file type validation, size constraints, and security checks.

| Property | Type | Required | Description |
|----------|------|----------|-------------|
| `avatar` | `Symfony\Component\HttpFoundation\File\UploadedFile` | No | Data Transfer Object for avatar upload requests. Handles validation and encapsulation of avatar f... |


**Example Request:**

```json
{
    "avatar": null
}
```

#### Response

**JsonResponse**

Response represents an HTTP response in JSON format.

Note that this class does not force the returned JSON content to be an
object. It is however recommended that you do return an object as it
protects yourself against XSSI and JSON-JavaScript Hijacking.

| Property | Type | Required | Description |
|----------|------|----------|-------------|
| `data` | `mixed` | Yes | No description available |
| `callback` | `string` | No | No description available |
| `encodingOptions` | `int` | No | No description available |
| `headers` | `Symfony\Component\HttpFoundation\ResponseHeaderBag` | Yes | No description available |
| `content` | `string` | Yes | No description available |
| `version` | `string` | Yes | No description available |
| `statusCode` | `int` | Yes | No description available |
| `statusText` | `string` | Yes | No description available |
| `charset` | `string` | No | No description available |
| `statusTexts` | `array` | No | Status codes translation table. The list of codes is complete according to the {@link https://www... |


**Example Response:**

```json
{
    "data": null,
    "callback": "example_string",
    "encodingOptions": 42,
    "headers": null,
    "content": "example_string",
    "version": "example_string",
    "statusCode": 42,
    "statusText": "active",
    "charset": "example_string",
    "statusTexts": [
        "example_item"
    ]
}
```

---

<!-- Route 2 -->
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

| Property | Type | Required | Description |
|----------|------|----------|-------------|
| `currentPassword` | `string` | Yes | User's current password for verification. Required to verify the user's identity before allowing ... |
| `newPassword` | `string` | Yes | New password that will replace the current one. Must meet security requirements: minimum 8 charac... |
| `confirmPassword` | `string` | Yes | Confirmation of the new password. Must exactly match the newPassword field to prevent accidental ... |


**Example Request:**

```json
{
    "currentPassword": "secure_password123",
    "newPassword": "secure_password123",
    "confirmPassword": "secure_password123"
}
```

#### Response

**JsonResponse**

Response represents an HTTP response in JSON format.

Note that this class does not force the returned JSON content to be an
object. It is however recommended that you do return an object as it
protects yourself against XSSI and JSON-JavaScript Hijacking.

| Property | Type | Required | Description |
|----------|------|----------|-------------|
| `data` | `mixed` | Yes | No description available |
| `callback` | `string` | No | No description available |
| `encodingOptions` | `int` | No | No description available |
| `headers` | `Symfony\Component\HttpFoundation\ResponseHeaderBag` | Yes | No description available |
| `content` | `string` | Yes | No description available |
| `version` | `string` | Yes | No description available |
| `statusCode` | `int` | Yes | No description available |
| `statusText` | `string` | Yes | No description available |
| `charset` | `string` | No | No description available |
| `statusTexts` | `array` | No | Status codes translation table. The list of codes is complete according to the {@link https://www... |


**Example Response:**

```json
{
    "data": null,
    "callback": "example_string",
    "encodingOptions": 42,
    "headers": null,
    "content": "example_string",
    "version": "example_string",
    "statusCode": 42,
    "statusText": "active",
    "charset": "example_string",
    "statusTexts": [
        "example_item"
    ]
}
```

---

<!-- Route 3 -->
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

| Property | Type | Required | Description |
|----------|------|----------|-------------|
| `data` | `mixed` | Yes | No description available |
| `callback` | `string` | No | No description available |
| `encodingOptions` | `int` | No | No description available |
| `headers` | `Symfony\Component\HttpFoundation\ResponseHeaderBag` | Yes | No description available |
| `content` | `string` | Yes | No description available |
| `version` | `string` | Yes | No description available |
| `statusCode` | `int` | Yes | No description available |
| `statusText` | `string` | Yes | No description available |
| `charset` | `string` | No | No description available |
| `statusTexts` | `array` | No | Status codes translation table. The list of codes is complete according to the {@link https://www... |


**Example Response:**

```json
{
    "data": null,
    "callback": "example_string",
    "encodingOptions": 42,
    "headers": null,
    "content": "example_string",
    "version": "example_string",
    "statusCode": 42,
    "statusText": "active",
    "charset": "example_string",
    "statusTexts": [
        "example_item"
    ]
}
```

---

<!-- Route 4 -->
### PUT /profile

Update current user's profile information

Updates user profile data including personal information, professional details,
and account preferences. Uses comprehensive validation and returns updated profile.

#### Request Body

**UpdateProfileRequestDTO**

Data Transfer Object for comprehensive profile update requests



| Property | Type | Required | Description |
|----------|------|----------|-------------|
| `firstName` | `string` | Yes | No description available |
| `lastName` | `string` | Yes | No description available |
| `username` | `string` | Yes | No description available |
| `email` | `string` | Yes | No description available |
| `jobTitle` | `string` | Yes | No description available |
| `company` | `string` | Yes | No description available |
| `website` | `string` | Yes | No description available |
| `portfolio` | `string` | Yes | No description available |
| `bio` | `string` | Yes | No description available |
| `socialLinks` | `array` | Yes | No description available |
| `timezone` | `string` | Yes | No description available |
| `language` | `string` | Yes | No description available |


**Example Request:**

```json
{
    "firstName": "Example Name",
    "lastName": "Example Name",
    "username": "Example Name",
    "email": "user@example.com",
    "jobTitle": "Example Title",
    "company": "example_string",
    "website": "example_string",
    "portfolio": "example_string",
    "bio": "example_string",
    "socialLinks": [
        "example_item"
    ],
    "timezone": "example_string",
    "language": "example_string"
}
```

#### Response

**JsonResponse**

Response represents an HTTP response in JSON format.

Note that this class does not force the returned JSON content to be an
object. It is however recommended that you do return an object as it
protects yourself against XSSI and JSON-JavaScript Hijacking.

| Property | Type | Required | Description |
|----------|------|----------|-------------|
| `data` | `mixed` | Yes | No description available |
| `callback` | `string` | No | No description available |
| `encodingOptions` | `int` | No | No description available |
| `headers` | `Symfony\Component\HttpFoundation\ResponseHeaderBag` | Yes | No description available |
| `content` | `string` | Yes | No description available |
| `version` | `string` | Yes | No description available |
| `statusCode` | `int` | Yes | No description available |
| `statusText` | `string` | Yes | No description available |
| `charset` | `string` | No | No description available |
| `statusTexts` | `array` | No | Status codes translation table. The list of codes is complete according to the {@link https://www... |


**Example Response:**

```json
{
    "data": null,
    "callback": "example_string",
    "encodingOptions": 42,
    "headers": null,
    "content": "example_string",
    "version": "example_string",
    "statusCode": 42,
    "statusText": "active",
    "charset": "example_string",
    "statusTexts": [
        "example_item"
    ]
}
```

---

<!-- Route 5 -->
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

| Property | Type | Required | Description |
|----------|------|----------|-------------|
| `data` | `mixed` | Yes | No description available |
| `callback` | `string` | No | No description available |
| `encodingOptions` | `int` | No | No description available |
| `headers` | `Symfony\Component\HttpFoundation\ResponseHeaderBag` | Yes | No description available |
| `content` | `string` | Yes | No description available |
| `version` | `string` | Yes | No description available |
| `statusCode` | `int` | Yes | No description available |
| `statusText` | `string` | Yes | No description available |
| `charset` | `string` | No | No description available |
| `statusTexts` | `array` | No | Status codes translation table. The list of codes is complete according to the {@link https://www... |


**Example Response:**

```json
{
    "data": null,
    "callback": "example_string",
    "encodingOptions": 42,
    "headers": null,
    "content": "example_string",
    "version": "example_string",
    "statusCode": 42,
    "statusText": "active",
    "charset": "example_string",
    "statusTexts": [
        "example_item"
    ]
}
```

---

<!-- Route 6 -->
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

| Property | Type | Required | Description |
|----------|------|----------|-------------|
| `data` | `mixed` | Yes | No description available |
| `callback` | `string` | No | No description available |
| `encodingOptions` | `int` | No | No description available |
| `headers` | `Symfony\Component\HttpFoundation\ResponseHeaderBag` | Yes | No description available |
| `content` | `string` | Yes | No description available |
| `version` | `string` | Yes | No description available |
| `statusCode` | `int` | Yes | No description available |
| `statusText` | `string` | Yes | No description available |
| `charset` | `string` | No | No description available |
| `statusTexts` | `array` | No | Status codes translation table. The list of codes is complete according to the {@link https://www... |


**Example Response:**

```json
{
    "data": null,
    "callback": "example_string",
    "encodingOptions": 42,
    "headers": null,
    "content": "example_string",
    "version": "example_string",
    "statusCode": 42,
    "statusText": "active",
    "charset": "example_string",
    "statusTexts": [
        "example_item"
    ]
}
```

---

<!-- Route 7 -->
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

| Property | Type | Required | Description |
|----------|------|----------|-------------|
| `data` | `mixed` | Yes | No description available |
| `callback` | `string` | No | No description available |
| `encodingOptions` | `int` | No | No description available |
| `headers` | `Symfony\Component\HttpFoundation\ResponseHeaderBag` | Yes | No description available |
| `content` | `string` | Yes | No description available |
| `version` | `string` | Yes | No description available |
| `statusCode` | `int` | Yes | No description available |
| `statusText` | `string` | Yes | No description available |
| `charset` | `string` | No | No description available |
| `statusTexts` | `array` | No | Status codes translation table. The list of codes is complete according to the {@link https://www... |


**Example Response:**

```json
{
    "data": null,
    "callback": "example_string",
    "encodingOptions": 42,
    "headers": null,
    "content": "example_string",
    "version": "example_string",
    "statusCode": 42,
    "statusText": "active",
    "charset": "example_string",
    "statusTexts": [
        "example_item"
    ]
}
```

---

<!-- Route 8 -->
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

| Property | Type | Required | Description |
|----------|------|----------|-------------|
| `data` | `mixed` | Yes | No description available |
| `callback` | `string` | No | No description available |
| `encodingOptions` | `int` | No | No description available |
| `headers` | `Symfony\Component\HttpFoundation\ResponseHeaderBag` | Yes | No description available |
| `content` | `string` | Yes | No description available |
| `version` | `string` | Yes | No description available |
| `statusCode` | `int` | Yes | No description available |
| `statusText` | `string` | Yes | No description available |
| `charset` | `string` | No | No description available |
| `statusTexts` | `array` | No | Status codes translation table. The list of codes is complete according to the {@link https://www... |


**Example Response:**

```json
{
    "data": null,
    "callback": "example_string",
    "encodingOptions": 42,
    "headers": null,
    "content": "example_string",
    "version": "example_string",
    "statusCode": 42,
    "statusText": "active",
    "charset": "example_string",
    "statusTexts": [
        "example_item"
    ]
}
```

---


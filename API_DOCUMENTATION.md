# API Documentation - IamgickPro Design Platform

## Overview

This documentation provides comprehensive information about the IamgickPro Design Platform API endpoints, including routes, HTTP methods, request/response schemas, and authentication requirements.

**Base URL**: `http://localhost:8000/api`  
**Authentication**: JWT Bearer Token (where required)  
**Content-Type**: `application/json`

*Generated on: 2025-06-01 12:26:22*

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

```typescript
interface BulkDeleteMediaRequestDTO {
  uuids: any[];  // Uuids value
}
```

#### BulkUpdateLayersRequestDTO

```typescript
interface BulkUpdateLayersRequestDTO {
  layers: any[];  // Array of layer objects
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

```typescript
interface CreateDesignRequestDTO {
  name: string;  // Display name
  description?: string;  // Description value
  data?: any[];  // Response data payload
  projectId?: number;  // ProjectId value
  width?: number;  // Width value
  height?: number;  // Height value
  isPublic?: boolean;  // IsPublic value
}
```

#### CreateLayerRequestDTO

```typescript
interface CreateLayerRequestDTO {
  designId: string;  // DesignId value
  type: string;  // Type value
  name: string;  // Display name
  properties?: any[];  // Properties value
  transform?: any[];  // Transform value
  zIndex?: number;  // ZIndex value
  visible?: boolean;  // Visible value
  locked?: boolean;  // Locked value
  parentLayerId?: string;  // ParentLayerId value
}
```

#### CreateMediaRequestDTO

```typescript
interface CreateMediaRequestDTO {
  name: string;  // Display name
  type: string;  // Type value
  mimeType: string;  // MimeType value
  size: number;  // Size value
  url: string;  // Url value
  thumbnailUrl?: string;  // ThumbnailUrl value
  width?: number;  // Width value
  height?: number;  // Height value
  duration?: number;  // Duration value
  source?: string;  // Source value
  sourceId?: string;  // SourceId value
  metadata?: any[];  // Metadata value
  tags?: any[];  // Tags value
  attribution?: string;  // Attribution value
  license?: string;  // License value
  isPremium?: boolean;  // IsPremium value
  isActive?: boolean;  // IsActive value
}
```

#### CreateProjectRequestDTO

```typescript
interface CreateProjectRequestDTO {
  name: string;  // Display name
  description?: string;  // Description value
  isPublic?: boolean;  // IsPublic value
  settings?: any[];  // Settings value
  tags?: any[];  // Tags value
  thumbnail?: string;  // Thumbnail value
}
```

#### DuplicateDesignRequestDTO

```typescript
interface DuplicateDesignRequestDTO {
  name?: string;  // Display name
  projectId?: number;  // ProjectId value
}
```

#### DuplicateLayerRequestDTO

```typescript
interface DuplicateLayerRequestDTO {
  name?: string;  // Display name
  targetDesignId?: string;  // TargetDesignId value
}
```

#### DuplicateMediaRequestDTO

```typescript
interface DuplicateMediaRequestDTO {
  name?: string;  // Display name
}
```

#### DuplicateProjectRequestDTO

```typescript
interface DuplicateProjectRequestDTO {
  name?: string;  // Display name
}
```

#### LoginRequestDTO

> Data Transfer Object for user login requests

```typescript
interface LoginRequestDTO {
  email: string;  // Email address
  password: string;  // Password value
}
```

#### MoveLayerRequestDTO

```typescript
interface MoveLayerRequestDTO {
  direction?: string;  // Direction value
  targetZIndex?: number;  // TargetZIndex value
}
```

#### RegisterRequestDTO

> Data Transfer Object for user registration requests

```typescript
interface RegisterRequestDTO {
  email: string;  // Email address
  password: string;  // Password value
  firstName: string;  // FirstName value
  lastName: string;  // LastName value
  username?: string;  // Username value
}
```

#### SearchMediaRequestDTO

```typescript
interface SearchMediaRequestDTO {
  page?: number;  // Current page number
  limit?: number;  // Items per page
  type?: string;  // Type value
  source?: string;  // Source value
  search?: string;  // Search value
  tags?: string;  // Tags value
}
```

#### SearchProjectsRequestDTO

```typescript
interface SearchProjectsRequestDTO {
  page?: number;  // Current page number
  limit?: number;  // Items per page
  search?: string;  // Search value
  tags?: string;  // Tags value
}
```

#### SearchRequestDTO

```typescript
interface SearchRequestDTO {
  query: string;  // Query value
  page?: number;  // Current page number
  limit?: number;  // Items per page
}
```

#### StockSearchRequestDTO

```typescript
interface StockSearchRequestDTO {
  query: string;  // Query value
  page?: number;  // Current page number
  limit?: number;  // Items per page
  type?: string;  // Type value
  source?: string;  // Source value
}
```

#### UpdateDesignRequestDTO

```typescript
interface UpdateDesignRequestDTO {
  name?: string;  // Display name
  description?: string;  // Description value
  data?: any[];  // Response data payload
  projectId?: number;  // ProjectId value
  width?: number;  // Width value
  height?: number;  // Height value
  isPublic?: boolean;  // IsPublic value
}
```

#### UpdateDesignThumbnailRequestDTO

```typescript
interface UpdateDesignThumbnailRequestDTO {
  thumbnail: string;  // Thumbnail value
}
```

#### UpdateLayerRequestDTO

```typescript
interface UpdateLayerRequestDTO {
  name?: string;  // Display name
  properties?: any[];  // Properties value
  transform?: any[];  // Transform value
  zIndex?: number;  // ZIndex value
  visible?: boolean;  // Visible value
  locked?: boolean;  // Locked value
  parentLayerId?: string;  // ParentLayerId value
}
```

#### UpdateMediaRequestDTO

```typescript
interface UpdateMediaRequestDTO {
  name?: string;  // Display name
  description?: string;  // Description value
  tags?: any[];  // Tags value
  metadata?: any[];  // Metadata value
  isPremium?: boolean;  // IsPremium value
  isActive?: boolean;  // IsActive value
  isPublic?: boolean;  // IsPublic value
}
```

#### UpdateProfileRequestDTO

> Data Transfer Object for profile update requests

```typescript
interface UpdateProfileRequestDTO {
  firstName?: string;  // FirstName value
  lastName?: string;  // LastName value
  username?: string;  // Username value
  avatar?: string;  // Avatar value
  settings?: any[];  // Settings value
}
```

#### UpdateProjectRequestDTO

```typescript
interface UpdateProjectRequestDTO {
  name?: string;  // Display name
  description?: string;  // Description value
  isPublic?: boolean;  // IsPublic value
  settings?: any[];  // Settings value
  tags?: any[];  // Tags value
  thumbnail?: string;  // Thumbnail value
}
```

#### UploadMediaRequestDTO

```typescript
interface UploadMediaRequestDTO {
  name: string;  // Display name
  type: string;  // Type value
  description?: string;  // Description value
  tags?: any[];  // Tags value
  isPublic?: boolean;  // IsPublic value
}
```

### Response Schemas

#### AuthResponseDTO

> Authentication response DTO with user data and JWT token

```typescript
interface AuthResponseDTO {
  message: string;  // Human-readable message describing the result
  token: string;  // JWT authentication token
  user: {
    id: string;  // Unique identifier
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
  };  // User information object
  success?: boolean;  // Indicates if the request was successful
  timestamp?: string;  // ISO 8601 timestamp of when the response was generated
}
```

#### BaseResponseDTO

> Base response DTO with common fields

```typescript
interface BaseResponseDTO {
  message: string;  // Human-readable message describing the result
  success?: boolean;  // Indicates if the request was successful
  timestamp?: string;  // ISO 8601 timestamp of when the response was generated
}
```

#### DesignResponseDTO

> Response DTO for Design data

```typescript
interface DesignResponseDTO {
  success: boolean;  // Indicates if the request was successful
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
  message: string;  // Human-readable message describing the result
  details?: any[];  // Additional error details
  code?: string;  // Error code identifier
  timestamp?: string;  // ISO 8601 timestamp of when the response was generated
  success?: boolean;  // Indicates if the request was successful
}
```

#### ExportJobResponseDTO

> Response DTO for Export Job data

```typescript
interface ExportJobResponseDTO {
  success: boolean;  // Indicates if the request was successful
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
  success: boolean;  // Indicates if the request was successful
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
  success: boolean;  // Indicates if the request was successful
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
  data: any[];  // */
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
  success: boolean;  // Indicates if the request was successful
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
  success: boolean;  // Indicates if the request was successful
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
  success: boolean;  // Indicates if the request was successful
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
  message: string;  // Human-readable message describing the result
  success?: boolean;  // Indicates if the request was successful
  timestamp?: string;  // ISO 8601 timestamp of when the response was generated
}
```

#### TemplateResponseDTO

> Response DTO for Template data

```typescript
interface TemplateResponseDTO {
  success: boolean;  // Indicates if the request was successful
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
  templates: any[];  // Array of template objects
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
    id: string;  // Unique identifier
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
  };  // User information object
  message?: string;  // Human-readable message describing the result
  success?: boolean;  // Indicates if the request was successful
  timestamp?: string;  // ISO 8601 timestamp of when the response was generated
}
```

#### UserResponseDTO

> User data DTO for API responses

```typescript
interface UserResponseDTO {
  id: string;  // Unique identifier
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

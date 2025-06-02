# Frontend Types System

This directory contains all TypeScript type definitions for the frontend application, ensuring type safety and maintaining alignment with the backend API contract.

## Architecture

### Response Structure Alignment

Our frontend types are designed to match the backend API response structure exactly:

#### Backend Response Pattern (PHP/Symfony)
```php
// Single entity response
{
  "success": true,
  "message": "Design retrieved successfully",
  "timestamp": "2025-06-02T10:30:00Z",
  "data": {
    "design": { /* single design object */ }
  }
}

// Paginated collection response
{
  "success": true,
  "message": "Designs retrieved successfully",
  "timestamp": "2025-06-02T10:30:00Z",
  "data": {
    "designs": [ /* array of design objects */ ],
    "pagination": {
      "total": 50,
      "page": 1,
      "totalPages": 5
    }
  }
}
```

#### Frontend Type System (TypeScript)
```typescript
// Base response structure
interface BaseApiResponse {
  success: boolean
  message: string
  timestamp: string
}

// Single entity response
interface ApiResponse<T> extends BaseApiResponse {
  data: T
}

// Paginated response (entity-specific)
interface DesignsApiResponse extends BaseApiResponse {
  data: {
    designs: Design[]
    pagination: {
      total: number
      page: number
      totalPages: number
    }
  }
}
```

## Type Categories

### 1. Response Structure Types
- `BaseApiResponse` - Common fields for all responses
- `ApiResponse<T>` - Single entity responses
- `AuthApiResponse` - Authentication responses with token and user
- `ErrorApiResponse` - Error responses with additional error details
- Entity-specific paginated responses (`DesignsApiResponse`, `TemplatesApiResponse`, etc.)

### 2. Entity Types
- `User` - User profile and authentication data
- `Design` - Design entity with canvas data
- `Template` - Template entity with metadata
- `MediaItem` - Media files and stock assets
- `Project` - Project organization entity
- `ExportJob` - Export job status and metadata

### 3. Request Parameter Types
- `*SearchParams` - Search and filter parameters for each entity
- `PaginationParams` - Basic pagination parameters
- Authentication types (`LoginCredentials`, `RegisterData`, etc.)

### 4. Support Types
- Canvas and layer types for design editor
- Analytics and dashboard types
- Collaboration and sharing types

## Key Features

### Type Safety
- All API responses are strongly typed
- Request parameters are validated by TypeScript
- Entity relationships are properly typed

### Backend Contract Compliance
- Response structures match PHP DTO classes exactly
- Pagination format follows backend implementation
- Property names and types align with Symfony serialization

### Maintainability
- Single source of truth for all types
- Organized by category and purpose
- Comprehensive documentation and examples

## Usage Examples

### API Service Usage
```typescript
// Strongly typed API calls
const designs = await designAPI.getDesigns({ page: 1, limit: 20 })
// designs.data.designs is Design[]
// designs.data.pagination has total, page, totalPages

const design = await designAPI.getDesign(id)
// design.data is Design
```

### Component Usage
```typescript
// Props are type-safe
interface DesignListProps {
  designs: Design[]
  pagination: {
    total: number
    page: number
    totalPages: number
  }
}
```

## Backend Alignment

This type system ensures perfect alignment with the backend API contract:

- **Response DTOs**: Match PHP response DTO structures
- **Pagination**: Follows backend pagination format
- **Entity Properties**: Align with Symfony entity serialization
- **API Endpoints**: Match controller method signatures

## Maintenance

When updating types:

1. Check corresponding backend DTO classes
2. Ensure property names match exactly
3. Validate response structure alignment
4. Update API service methods accordingly
5. Test with actual API responses

This ensures the frontend remains in sync with backend changes and maintains type safety throughout the application.

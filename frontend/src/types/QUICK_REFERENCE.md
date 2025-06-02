# API Types Quick Reference

## Response Types

### Single Entity
```typescript
ApiResponse<T> {
  success: boolean
  message: string  
  timestamp: string
  data: T
}
```

### Paginated Collections
```typescript
DesignsApiResponse {
  success: boolean
  message: string
  timestamp: string
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

### Authentication
```typescript
AuthApiResponse {
  success: boolean
  message: string
  timestamp: string
  token: string
  user: User
}
```

## Common Usage Patterns

### API Service Methods
```typescript
// Paginated endpoint
getDesigns: (params?: DesignSearchParams) => 
  api.get<DesignsApiResponse>('/designs', { params })

// Single entity endpoint  
getDesign: (id: string) => 
  api.get<ApiResponse<Design>>(`/designs/${id}`)

// Create/Update endpoint
createDesign: (data: CreateDesignData) => 
  api.post<ApiResponse<Design>>('/designs', data)
```

### Component Props
```typescript
interface DesignListProps {
  designs: Design[]
  pagination?: {
    total: number
    page: number
    totalPages: number
  }
  onPageChange?: (page: number) => void
}
```

### Store State
```typescript
interface DesignStore {
  designs: Design[]
  currentDesign: Design | null
  pagination: {
    total: number
    page: number
    totalPages: number
  }
  loading: boolean
  error: string | null
}
```

## Search Parameters

### Design Search
```typescript
DesignSearchParams {
  page?: number
  limit?: number
  search?: string
  project_id?: string
  sort_by?: 'name' | 'created_at' | 'updated_at'
  sort_order?: 'asc' | 'desc'
}
```

### Template Search
```typescript
TemplateSearchParams {
  page?: number
  limit?: number
  search?: string
  category?: string
  is_premium?: boolean
  sort_by?: 'created_at' | 'updated_at' | 'name' | 'popularity'
  sort_order?: 'asc' | 'desc'
}
```

## Backend Alignment Checklist

When adding new types:

- [ ] Check corresponding PHP DTO class
- [ ] Verify property names match exactly
- [ ] Ensure response structure follows pattern
- [ ] Add to API service with correct type
- [ ] Update validation script
- [ ] Test with actual API response

## Common Gotchas

1. **Property Names**: Backend uses `designData`, not `data`
2. **Date Format**: All dates are ISO 8601 strings, not Date objects
3. **Pagination**: Always includes `total`, `page`, `totalPages`
4. **Response Wrapper**: All responses include `success`, `message`, `timestamp`
5. **Entity IDs**: UUIDs as strings, not numbers

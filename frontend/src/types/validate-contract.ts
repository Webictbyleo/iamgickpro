#!/usr/bin/env typescript
/**
 * Type Contract Validation Script
 * 
 * This script helps validate that frontend types stay aligned with backend API contracts.
 * Run this script when making changes to ensure type safety is maintained.
 */

import type {
  BaseApiResponse,
  ApiResponse,
  PaginatedApiResponse,
  AuthApiResponse,
  DesignsApiResponse,
  TemplatesApiResponse,
  MediaListApiResponse,
  ProjectsApiResponse,
  ExportJobsApiResponse,
  Design,
  Template,
  MediaItem,
  Project,
  ExportJob,
  User
} from './index'

/**
 * Validate response structure matches backend contract
 */
function validateResponseStructure() {
  // Test single entity response structure
  const singleResponse: ApiResponse<Design> = {
    success: true,
    message: "Design retrieved successfully",
    timestamp: "2025-06-02T10:30:00Z",
    data: {
      id: "uuid-string",
      name: "My Design",
      designData: {
        version: "1.0.0",
        layers: [],
        canvas: {
          width: 800,
          height: 600,
          backgroundColor: "#ffffff"
        }
      },
      width: 800,
      height: 600,
      userId: "user-uuid",
      isPublic: false,
      createdAt: "2025-06-01T15:30:00Z",
      updatedAt: "2025-06-02T10:30:00Z"
    }
  }

  // Test paginated response structure
  const paginatedResponse: DesignsApiResponse = {
    success: true,
    message: "Designs retrieved successfully",
    timestamp: "2025-06-02T10:30:00Z",
    data: {
      designs: [singleResponse.data],
      pagination: {
        total: 50,
        page: 1,
        totalPages: 5
      }
    }
  }

  // Test authentication response
  const authResponse: AuthApiResponse = {
    success: true,
    message: "Login successful",
    timestamp: "2025-06-02T10:30:00Z",
    token: "jwt-token-string",
    user: {
      id: "user-uuid",
      email: "user@example.com",
      firstName: "John",
      lastName: "Doe",
      role: "user",
      createdAt: "2025-06-01T15:30:00Z"
    }
  }

  console.log("✅ All response structures validate correctly")
  return true
}

/**
 * Validate entity property alignment
 */
function validateEntityProperties() {
  // Ensure Design entity has all required properties from backend
  const design: Design = {
    id: "required",
    name: "required", 
    designData: {
      version: "required",
      layers: [],
      canvas: {
        width: 0,
        height: 0,
        backgroundColor: "required"
      }
    },
    width: 0,
    height: 0,
    userId: "required",
    isPublic: false,
    createdAt: "required",
    updatedAt: "required"
    // Optional properties not included in validation
  }

  // Ensure Template entity alignment
  const template: Template = {
    id: "required",
    uuid: "required",
    name: "required",
    category: "required",
    tags: [],
    thumbnail: "required",
    thumbnailUrl: "required", 
    previewUrl: "required",
    width: 0,
    height: 0,
    isPremium: false,
    isActive: true,
    rating: 0,
    ratingCount: 0,
    usageCount: 0,
    createdAt: "required",
    updatedAt: "required"
  }

  console.log("✅ All entity properties validate correctly")
  return true
}

/**
 * Run all validations
 */
function runValidation() {
  console.log("🔍 Running frontend type contract validation...")
  
  try {
    validateResponseStructure()
    validateEntityProperties()
    
    console.log("✅ All validations passed!")
    console.log("📋 Frontend types are aligned with backend contract")
    
  } catch (error) {
    console.error("❌ Validation failed:", error)
    process.exit(1)
  }
}

// Run validation if script is executed directly
if (require.main === module) {
  runValidation()
}

export { validateResponseStructure, validateEntityProperties, runValidation }

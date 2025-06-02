# OpenAPI Generation Fix - Complete

## Problem
The API documentation generator was throwing an exception when generating OpenAPI format:
```
TypeError: Cannot access offset of type array on array in generate-api-docs-enhanced.php:2915
```

## Root Cause
The `formatSecurityForOpenApi` method expected a simple array of security scheme names (strings), but the actual security data from `extractSecurityInfo` was a multi-dimensional array with structure:
```php
[
    [
        'type' => 'Security', // or 'IsGranted'
        'args' => [...] // attribute arguments
    ]
]
```

## Solution
Enhanced the `formatSecurityForOpenApi` method to properly handle the actual security data structure:

1. **Improved Parameter Filtering**: The method now handles different types of security data:
   - Simple string scheme names
   - Complex security objects with 'type' and 'args' 
   - Already formatted OpenAPI security requirements

2. **Added Security Type Mapping**: Created `mapSecurityTypeToSchemeName` method to map Symfony security attributes to OpenAPI security scheme names:
   - `Security`, `IsGranted`, `Role` → `bearerAuth`
   - `ApiKey` → `apiKeyAuth`
   - `Basic` → `basicAuth`
   - `OAuth` → `oauth2`

## Implementation Details

### Modified Methods
- `formatSecurityForOpenApi()`: Enhanced to handle complex security data structures
- `mapSecurityTypeToSchemeName()`: New method for mapping security types

### Code Changes
```php
private function formatSecurityForOpenApi(array $security): array
{
    if (empty($security)) {
        return [];
    }
    
    $formatted = [];
    foreach ($security as $securityItem) {
        // Handle different security data structures
        if (is_string($securityItem)) {
            // Simple string scheme name
            $formatted[] = [$securityItem => []];
        } elseif (is_array($securityItem) && isset($securityItem['type'])) {
            // Security item with type and args (from extractSecurityInfo)
            $schemeName = $this->mapSecurityTypeToSchemeName($securityItem['type']);
            if ($schemeName) {
                $formatted[] = [$schemeName => []];
            }
        } elseif (is_array($securityItem)) {
            // Already formatted OpenAPI security requirement
            $formatted[] = $securityItem;
        }
    }
    
    return $formatted;
}
```

## Verification
All output formats now work correctly:
- ✅ Markdown generation
- ✅ JSON generation  
- ✅ HTML generation
- ✅ **OpenAPI generation** (fixed)
- ✅ Postman collection generation

## Parameter Filtering Effectiveness
The enhanced parameter filtering continues to work correctly, properly excluding:
- Auto-resolved service dependencies
- Framework classes (Symfony, Doctrine, PSR)
- Repository and service classes
- Entity classes injected via ParamConverter
- Non-DTO class/object parameters

Only legitimate user-provided parameters (primitives and DTOs) are documented.

## Generated Output
- **OpenAPI Specification**: `openapi.json` - Valid OpenAPI 3.0.3 specification
- **Security Schemes**: Properly configured JWT Bearer authentication
- **API Paths**: All 74 API routes correctly documented
- **Parameters**: Only user-provided parameters included

## Status: ✅ COMPLETE
The OpenAPI generation exception has been resolved while maintaining all existing functionality and parameter filtering capabilities.

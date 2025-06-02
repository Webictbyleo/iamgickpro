# API Documentation

**Generated:** 2025-06-02T12:43:05+00:00  
**Generator:** Refactored API Documentation Generator v3.0.0  
**Symfony:** 7.0.10  
**PHP:** 8.4.7  

**Statistics:**
- **Routes:** 74
- **Controllers:** 9
- **Schemas:** 43
## Table of Contents

- [AuthController](#authcontroller)
  - [api_auth_register](#api_auth_register)
  - [api_auth_login](#api_auth_login)
  - [api_auth_me](#api_auth_me)
  - [api_auth_update_profile](#api_auth_update_profile)
  - [api_auth_change_password](#api_auth_change_password)
  - [api_auth_logout](#api_auth_logout)
- [DesignController](#designcontroller)
  - [api_designs_index](#api_designs_index)
  - [api_designs_create](#api_designs_create)
  - [api_designs_show](#api_designs_show)
  - [api_designs_update](#api_designs_update)
  - [api_designs_delete](#api_designs_delete)
  - [api_designs_duplicate](#api_designs_duplicate)
  - [api_designs_update_thumbnail](#api_designs_update_thumbnail)
  - [api_designs_search](#api_designs_search)
- [ExportJobController](#exportjobcontroller)
  - [api_export_jobs_list](#api_export_jobs_list)
  - [api_export_jobs_show](#api_export_jobs_show)
  - [api_export_jobs_create](#api_export_jobs_create)
  - [api_export_jobs_update](#api_export_jobs_update)
  - [api_export_jobs_delete](#api_export_jobs_delete)
  - [api_export_jobs_cancel](#api_export_jobs_cancel)
  - [api_export_jobs_retry](#api_export_jobs_retry)
  - [api_export_jobs_download](#api_export_jobs_download)
  - [api_export_jobs_stats](#api_export_jobs_stats)
  - [api_export_jobs_queue_status](#api_export_jobs_queue_status)
- [LayerController](#layercontroller)
  - [api_layers_create](#api_layers_create)
  - [api_layers_show](#api_layers_show)
  - [api_layers_update](#api_layers_update)
  - [api_layers_delete](#api_layers_delete)
  - [api_layers_duplicate](#api_layers_duplicate)
  - [api_layers_move](#api_layers_move)
  - [api_layers_bulk_update](#api_layers_bulk_update)
- [MediaController](#mediacontroller)
  - [api_media_list](#api_media_list)
  - [api_media_show](#api_media_show)
  - [api_media_create](#api_media_create)
  - [api_media_update](#api_media_update)
  - [api_media_delete](#api_media_delete)
  - [api_media_search](#api_media_search)
  - [api_media_duplicate](#api_media_duplicate)
  - [api_media_stock_search](#api_media_stock_search)
  - [api_media_bulk_delete](#api_media_bulk_delete)
- [PluginController](#plugincontroller)
  - [api_plugins_list](#api_plugins_list)
  - [api_plugins_show](#api_plugins_show)
  - [api_plugins_create](#api_plugins_create)
  - [api_plugins_update](#api_plugins_update)
  - [api_plugins_delete](#api_plugins_delete)
  - [api_plugins_install](#api_plugins_install)
  - [api_plugins_uninstall](#api_plugins_uninstall)
  - [api_plugins_upload_file](#api_plugins_upload_file)
  - [api_plugins_approve](#api_plugins_approve)
  - [api_plugins_reject](#api_plugins_reject)
  - [api_plugins_categories](#api_plugins_categories)
  - [api_plugins_my_plugins](#api_plugins_my_plugins)
- [ProjectController](#projectcontroller)
  - [api_projects_index](#api_projects_index)
  - [api_projects_create](#api_projects_create)
  - [api_projects_show](#api_projects_show)
  - [api_projects_update](#api_projects_update)
  - [api_projects_delete](#api_projects_delete)
  - [api_projects_duplicate](#api_projects_duplicate)
  - [api_projects_public](#api_projects_public)
  - [api_projects_toggle_share](#api_projects_toggle_share)
- [TemplateController](#templatecontroller)
  - [api_templates_list](#api_templates_list)
  - [api_templates_show](#api_templates_show)
  - [api_templates_create](#api_templates_create)
  - [api_templates_search](#api_templates_search)
  - [api_templates_use](#api_templates_use)
  - [api_templates_categories](#api_templates_categories)
- [UserController](#usercontroller)
  - [api_user_profile](#api_user_profile)
  - [api_user_update_profile](#api_user_update_profile)
  - [api_user_upload_avatar](#api_user_upload_avatar)
  - [api_user_change_password](#api_user_change_password)
  - [api_user_download_data](#api_user_download_data)
  - [api_user_export_data](#api_user_export_data)
  - [api_user_delete_account](#api_user_delete_account)
  - [api_user_subscription](#api_user_subscription)
- [Schemas](#schemas)
  - [RegisterRequestDTO](#registerrequestdto)
  - [AuthResponseDTO](#authresponsedto)
  - [LoginRequestDTO](#loginrequestdto)
  - [UserProfileResponseDTO](#userprofileresponsedto)
  - [UpdateProfileRequestDTO](#updateprofilerequestdto)
  - [ChangePasswordRequestDTO](#changepasswordrequestdto)
  - [SuccessResponseDTO](#successresponsedto)
  - [PaginatedResponseDTO](#paginatedresponsedto)
  - [CreateDesignRequestDTO](#createdesignrequestdto)
  - [DesignResponseDTO](#designresponsedto)
  - [UpdateDesignRequestDTO](#updatedesignrequestdto)
  - [DuplicateDesignRequestDTO](#duplicatedesignrequestdto)
  - [UpdateDesignThumbnailRequestDTO](#updatedesignthumbnailrequestdto)
  - [ExportJobResponseDTO](#exportjobresponsedto)
  - [CreateExportJobRequestDTO](#createexportjobrequestdto)
  - [ErrorResponseDTO](#errorresponsedto)
  - [CreateLayerRequestDTO](#createlayerrequestdto)
  - [LayerResponseDTO](#layerresponsedto)
  - [UpdateLayerRequestDTO](#updatelayerrequestdto)
  - [DuplicateLayerRequestDTO](#duplicatelayerrequestdto)
  - [MoveLayerRequestDTO](#movelayerrequestdto)
  - [BulkUpdateLayersRequestDTO](#bulkupdatelayersrequestdto)
  - [SearchMediaRequestDTO](#searchmediarequestdto)
  - [MediaResponseDTO](#mediaresponsedto)
  - [CreateMediaRequestDTO](#createmediarequestdto)
  - [UpdateMediaRequestDTO](#updatemediarequestdto)
  - [DuplicateMediaRequestDTO](#duplicatemediarequestdto)
  - [StockSearchRequestDTO](#stocksearchrequestdto)
  - [BulkDeleteMediaRequestDTO](#bulkdeletemediarequestdto)
  - [CreatePluginRequestDTO](#createpluginrequestdto)
  - [UpdatePluginRequestDTO](#updatepluginrequestdto)
  - [UploadPluginFileRequestDTO](#uploadpluginfilerequestdto)
  - [RejectPluginRequestDTO](#rejectpluginrequestdto)
  - [CreateProjectRequestDTO](#createprojectrequestdto)
  - [ProjectResponseDTO](#projectresponsedto)
  - [UpdateProjectRequestDTO](#updateprojectrequestdto)
  - [DuplicateProjectRequestDTO](#duplicateprojectrequestdto)
  - [SearchProjectsRequestDTO](#searchprojectsrequestdto)
  - [TemplateResponseDTO](#templateresponsedto)
  - [CreateTemplateRequestDTO](#createtemplaterequestdto)
  - [SearchTemplateRequestDTO](#searchtemplaterequestdto)
  - [TemplateSearchResponseDTO](#templatesearchresponsedto)
  - [UploadAvatarRequestDTO](#uploadavatarrequestdto)

---

## AuthController

Auth endpoints

**Route Count:** 6

### api_auth_register

**Path:** `/api/auth/register`  
**Methods:** `POST`  
**Description:** Creates a new user with the provided information and returns a JWT token for immediate authentication.

**Summary:** Register a new user account

#### Request Body

Handles new user account creation with validation for all
required fields. Used by the registration system to collect
and validate user information before creating new accounts
in the platform.

**Content Type:** `application/json`

**Schema:** [RegisterRequestDTO](#registerrequestdto)

**Example:**

```json
{
    "email": "example_email",
    "password": "example_password",
    "firstName": "example_firstName",
    "lastName": "example_lastName",
    "username": "example_username"
}
```

#### Responses

**200** - Success

**Schema:** [AuthResponseDTO](#authresponsedto)

**Example:**

```json
{
    "message": "example_message",
    "token": "example_token",
    "user": {
        "id": "example_id",
        "email": "example_email",
        "firstName": "example_firstName",
        "lastName": "example_lastName",
        "username": "example_username",
        "roles": [],
        "avatar": "example_avatar",
        "plan": "example_plan",
        "emailVerified": true,
        "isActive": true,
        "createdAt": "example_createdAt",
        "lastLoginAt": "example_lastLoginAt",
        "updatedAt": "example_updatedAt",
        "settings": [],
        "stats": []
    },
    "success": true,
    "timestamp": "example_timestamp"
}
```

**400** - Bad Request

**Example:**

```json
{
    "error": "Bad Request",
    "message": "Invalid input data"
}
```

**401** - Unauthorized

**Example:**

```json
{
    "error": "Unauthorized",
    "message": "Authentication required"
}
```


---

### api_auth_login

**Path:** `/api/auth/login`  
**Methods:** `POST`  
**Description:** Validates user credentials and returns a JWT token with user information.
Updates the user's last login timestamp.

**Summary:** Authenticate user and return JWT token

#### Request Body

Handles user login credentials for JWT token-based authentication.
Used by the authentication system to validate user credentials
and generate access tokens for API authorization.

**Content Type:** `application/json`

**Schema:** [LoginRequestDTO](#loginrequestdto)

**Example:**

```json
{
    "email": "example_email",
    "password": "example_password"
}
```

#### Responses

**200** - Success

**Schema:** [AuthResponseDTO](#authresponsedto)

**Example:**

```json
{
    "message": "example_message",
    "token": "example_token",
    "user": {
        "id": "example_id",
        "email": "example_email",
        "firstName": "example_firstName",
        "lastName": "example_lastName",
        "username": "example_username",
        "roles": [],
        "avatar": "example_avatar",
        "plan": "example_plan",
        "emailVerified": true,
        "isActive": true,
        "createdAt": "example_createdAt",
        "lastLoginAt": "example_lastLoginAt",
        "updatedAt": "example_updatedAt",
        "settings": [],
        "stats": []
    },
    "success": true,
    "timestamp": "example_timestamp"
}
```

**400** - Bad Request

**Example:**

```json
{
    "error": "Bad Request",
    "message": "Invalid input data"
}
```

**401** - Unauthorized

**Example:**

```json
{
    "error": "Unauthorized",
    "message": "Authentication required"
}
```


---

### api_auth_me

**Path:** `/api/auth/me`  
**Methods:** `GET`  
**Description:** Returns detailed information about the currently authenticated user,
including profile data, statistics, and account status.

**Summary:** Get current authenticated user profile

🔒 **Security:** IsGranted

#### Responses

**200** - Success

**Schema:** [UserProfileResponseDTO](#userprofileresponsedto)

**Example:**

```json
{
    "user": {
        "id": "example_id",
        "email": "example_email",
        "firstName": "example_firstName",
        "lastName": "example_lastName",
        "username": "example_username",
        "roles": [],
        "avatar": "example_avatar",
        "plan": "example_plan",
        "emailVerified": true,
        "isActive": true,
        "createdAt": "example_createdAt",
        "lastLoginAt": "example_lastLoginAt",
        "updatedAt": "example_updatedAt",
        "settings": [],
        "stats": []
    },
    "message": "example_message",
    "success": true,
    "timestamp": "example_timestamp"
}
```

**400** - Bad Request

**Example:**

```json
{
    "error": "Bad Request",
    "message": "Invalid input data"
}
```

**401** - Unauthorized

**Example:**

```json
{
    "error": "Unauthorized",
    "message": "Authentication required"
}
```


---

### api_auth_update_profile

**Path:** `/api/auth/profile`  
**Methods:** `PUT`  
**Description:** Updates the authenticated user's profile data such as name, username, avatar, and settings.
Validates uniqueness of username if provided.

**Summary:** Update user profile information

🔒 **Security:** IsGranted

#### Request Body

Handles updating user profile information including personal details,
username, avatar, and application settings. All fields are optional
to support partial updates.

**Content Type:** `application/json`

**Schema:** [UpdateProfileRequestDTO](#updateprofilerequestdto)

**Example:**

```json
{
    "firstName": "example_firstName",
    "lastName": "example_lastName",
    "username": "example_username",
    "avatar": "example_avatar",
    "settings": {
        "theme": "example_theme",
        "language": "example_language",
        "timezone": "example_timezone",
        "emailNotifications": true,
        "pushNotifications": true,
        "autoSave": true,
        "autoSaveInterval": 123,
        "gridSnap": true,
        "gridSize": 123,
        "canvasQuality": 123
    }
}
```

#### Responses

**200** - Success

**Schema:** [UserProfileResponseDTO](#userprofileresponsedto)

**Example:**

```json
{
    "user": {
        "id": "example_id",
        "email": "example_email",
        "firstName": "example_firstName",
        "lastName": "example_lastName",
        "username": "example_username",
        "roles": [],
        "avatar": "example_avatar",
        "plan": "example_plan",
        "emailVerified": true,
        "isActive": true,
        "createdAt": "example_createdAt",
        "lastLoginAt": "example_lastLoginAt",
        "updatedAt": "example_updatedAt",
        "settings": [],
        "stats": []
    },
    "message": "example_message",
    "success": true,
    "timestamp": "example_timestamp"
}
```

**400** - Bad Request

**Example:**

```json
{
    "error": "Bad Request",
    "message": "Invalid input data"
}
```

**401** - Unauthorized

**Example:**

```json
{
    "error": "Unauthorized",
    "message": "Authentication required"
}
```


---

### api_auth_change_password

**Path:** `/api/auth/change-password`  
**Methods:** `PUT`  
**Description:** Updates the authenticated user's password after validating the current password.
Enforces password strength requirements.

**Summary:** Change user password

🔒 **Security:** IsGranted

#### Request Body

**Content Type:** `application/json`

**Schema:** [ChangePasswordRequestDTO](#changepasswordrequestdto)

**Example:**

```json
{
    "currentPassword": "example_currentPassword",
    "newPassword": "example_newPassword",
    "confirmPassword": "example_confirmPassword"
}
```

#### Responses

**200** - Success

**Schema:** [SuccessResponseDTO](#successresponsedto)

**Example:**

```json
{
    "message": "example_message",
    "success": true,
    "timestamp": "example_timestamp"
}
```

**400** - Bad Request

**Example:**

```json
{
    "error": "Bad Request",
    "message": "Invalid input data"
}
```

**401** - Unauthorized

**Example:**

```json
{
    "error": "Unauthorized",
    "message": "Authentication required"
}
```


---

### api_auth_logout

**Path:** `/api/auth/logout`  
**Methods:** `POST`  
**Description:** Since JWT tokens are stateless, logout is primarily handled client-side by removing the token.
This endpoint provides a standardized logout response and could be extended with token blacklisting.

**Summary:** Logout user

🔒 **Security:** IsGranted

#### Responses

**200** - Success

**Schema:** [SuccessResponseDTO](#successresponsedto)

**Example:**

```json
{
    "message": "example_message",
    "success": true,
    "timestamp": "example_timestamp"
}
```

**400** - Bad Request

**Example:**

```json
{
    "error": "Bad Request",
    "message": "Invalid input data"
}
```

**401** - Unauthorized

**Example:**

```json
{
    "error": "Unauthorized",
    "message": "Authentication required"
}
```


---

## DesignController

Design endpoints

**Route Count:** 8

### api_designs_index

**Path:** `/api/designs`  
**Methods:** `GET`  
**Description:** Returns a paginated list of designs belonging to the authenticated user.
Supports filtering by project, status, and search functionality.

**Summary:** List designs for authenticated user

#### Parameters

- **request** `Symfony\Component\HttpFoundation\Request` (required) - HTTP request with optional query parameters:
- page: Page number (default: 1, min: 1)
- limit: Items per page (default: 20, max: 100)
- project_id: Filter by project ID
- search: Search term for design name/description
- sort: Sort field (name, created_at, updated_at)
- order: Sort direction (asc, desc)

#### Responses

**200** - Success

**Schema:** [PaginatedResponseDTO](#paginatedresponsedto)

**Example:**

```json
{
    "data": [],
    "page": 123,
    "limit": 123,
    "total": 123,
    "totalPages": 123,
    "message": "example_message"
}
```

**400** - Bad Request

**Example:**

```json
{
    "error": "Bad Request",
    "message": "Invalid input data"
}
```

**401** - Unauthorized

**Example:**

```json
{
    "error": "Unauthorized",
    "message": "Authentication required"
}
```


---

### api_designs_create

**Path:** `/api/designs`  
**Methods:** `POST`  
**Description:** Creates a new design with the provided information and associates it with the authenticated user.
Validates design data and initializes default canvas settings.

**Summary:** Create a new design

#### Request Body

This DTO handles the creation of new designs with specified canvas
dimensions, initial design settings, and organizational metadata.

**Content Type:** `application/json`

**Schema:** [CreateDesignRequestDTO](#createdesignrequestdto)

**Example:**

```json
{
    "name": "example_name",
    "description": "example_description",
    "data": {
        "backgroundColor": "example_backgroundColor",
        "animationSettings": [],
        "gridSettings": [],
        "viewSettings": [],
        "globalStyles": [],
        "customProperties": []
    },
    "projectId": 123,
    "width": 123,
    "height": 123,
    "isPublic": true
}
```

#### Responses

**200** - Success

**Schema:** [DesignResponseDTO](#designresponsedto)

**Example:**

```json
{
    "success": true,
    "message": "example_message",
    "design": [],
    "designs": [],
    "total": 123,
    "page": 123,
    "totalPages": 123,
    "timestamp": "example_value"
}
```

**400** - Bad Request

**Example:**

```json
{
    "error": "Bad Request",
    "message": "Invalid input data"
}
```

**401** - Unauthorized

**Example:**

```json
{
    "error": "Unauthorized",
    "message": "Authentication required"
}
```


---

### api_designs_show

**Path:** `/api/designs/{id}`  
**Methods:** `GET`  
**Description:** Returns detailed information about a single design including canvas data and layers.
Only allows access to designs owned by the authenticated user or public designs.

**Summary:** Get details of a specific design

#### Parameters

- **id** `int` (required) - The design ID

#### Responses

**200** - Success

**Schema:** [DesignResponseDTO](#designresponsedto)

**Example:**

```json
{
    "success": true,
    "message": "example_message",
    "design": [],
    "designs": [],
    "total": 123,
    "page": 123,
    "totalPages": 123,
    "timestamp": "example_value"
}
```

**400** - Bad Request

**Example:**

```json
{
    "error": "Bad Request",
    "message": "Invalid input data"
}
```

**401** - Unauthorized

**Example:**

```json
{
    "error": "Unauthorized",
    "message": "Authentication required"
}
```


---

### api_designs_update

**Path:** `/api/designs/{id}`  
**Methods:** `PUT`  
**Description:** Updates design information and canvas data with the provided information.
Only allows updates to designs owned by the authenticated user.
Supports partial updates and handles canvas data versioning.

**Summary:** Update an existing design

#### Parameters

- **id** `int` (required) - The design ID to update

#### Request Body

This DTO handles partial updates to designs, allowing clients to
update only the fields they want to change. All fields are optional
and null values indicate no change should be made.

**Content Type:** `application/json`

**Schema:** [UpdateDesignRequestDTO](#updatedesignrequestdto)

**Example:**

```json
{
    "name": "example_name",
    "description": "example_description",
    "data": {
        "backgroundColor": "example_backgroundColor",
        "animationSettings": [],
        "gridSettings": [],
        "viewSettings": [],
        "globalStyles": [],
        "customProperties": []
    },
    "projectId": 123,
    "width": 123,
    "height": 123,
    "isPublic": true
}
```

#### Responses

**200** - Success

**Schema:** [DesignResponseDTO](#designresponsedto)

**Example:**

```json
{
    "success": true,
    "message": "example_message",
    "design": [],
    "designs": [],
    "total": 123,
    "page": 123,
    "totalPages": 123,
    "timestamp": "example_value"
}
```

**400** - Bad Request

**Example:**

```json
{
    "error": "Bad Request",
    "message": "Invalid input data"
}
```

**401** - Unauthorized

**Example:**

```json
{
    "error": "Unauthorized",
    "message": "Authentication required"
}
```


---

### api_designs_delete

**Path:** `/api/designs/{id}`  
**Methods:** `DELETE`  
**Description:** Permanently deletes a design and all its associated data (layers, media files, export jobs).
Only allows deletion of designs owned by the authenticated user.
This action cannot be undone.

**Summary:** Delete a design

#### Parameters

- **id** `int` (required) - The design ID to delete

#### Responses

**200** - Success

**Schema:** [SuccessResponseDTO](#successresponsedto)

**Example:**

```json
{
    "message": "example_message",
    "success": true,
    "timestamp": "example_timestamp"
}
```

**400** - Bad Request

**Example:**

```json
{
    "error": "Bad Request",
    "message": "Invalid input data"
}
```

**401** - Unauthorized

**Example:**

```json
{
    "error": "Unauthorized",
    "message": "Authentication required"
}
```


---

### api_designs_duplicate

**Path:** `/api/designs/{id}/duplicate`  
**Methods:** `POST`  
**Description:** Creates a copy of an existing design with all its layers and settings.
Only allows duplication of designs owned by the authenticated user or public designs.
The duplicated design is always private and owned by the authenticated user.

**Summary:** Duplicate an existing design

#### Parameters

- **id** `int` (required) - The design ID to duplicate

#### Request Body

Handles the duplication of complete designs including all layers,
settings, and metadata. Used by the design management system to
create copies of existing designs with optional customization
of the duplicate's name and target project.

**Content Type:** `application/json`

**Schema:** [DuplicateDesignRequestDTO](#duplicatedesignrequestdto)

**Example:**

```json
{
    "name": "example_name",
    "projectId": 123
}
```

#### Responses

**200** - Success

**Schema:** [DesignResponseDTO](#designresponsedto)

**Example:**

```json
{
    "success": true,
    "message": "example_message",
    "design": [],
    "designs": [],
    "total": 123,
    "page": 123,
    "totalPages": 123,
    "timestamp": "example_value"
}
```

**400** - Bad Request

**Example:**

```json
{
    "error": "Bad Request",
    "message": "Invalid input data"
}
```

**401** - Unauthorized

**Example:**

```json
{
    "error": "Unauthorized",
    "message": "Authentication required"
}
```


---

### api_designs_update_thumbnail

**Path:** `/api/designs/{id}/thumbnail`  
**Methods:** `PUT`  
**Description:** Updates the thumbnail image for a design.
Only allows updates to designs owned by the authenticated user.
Validates thumbnail format and size requirements.

**Summary:** Update design thumbnail

#### Parameters

- **id** `int` (required) - The design ID to update thumbnail for

#### Request Body

Handles updating the thumbnail image for an existing design.
Used when users want to change the preview image that represents
their design in galleries, project lists, and search results.

**Content Type:** `application/json`

**Schema:** [UpdateDesignThumbnailRequestDTO](#updatedesignthumbnailrequestdto)

**Example:**

```json
{
    "thumbnail": "example_thumbnail"
}
```

#### Responses

**200** - Success

**Schema:** [DesignResponseDTO](#designresponsedto)

**Example:**

```json
{
    "success": true,
    "message": "example_message",
    "design": [],
    "designs": [],
    "total": 123,
    "page": 123,
    "totalPages": 123,
    "timestamp": "example_value"
}
```

**400** - Bad Request

**Example:**

```json
{
    "error": "Bad Request",
    "message": "Invalid input data"
}
```

**401** - Unauthorized

**Example:**

```json
{
    "error": "Unauthorized",
    "message": "Authentication required"
}
```


---

### api_designs_search

**Path:** `/api/designs/search`  
**Methods:** `GET`  
**Description:** Performs a comprehensive search across designs accessible to the authenticated user.
Searches in design names, descriptions, and associated project information.

**Summary:** Search designs

#### Parameters

- **request** `Symfony\Component\HttpFoundation\Request` (required) - HTTP request with search parameters:
- q: Search query term (required)
- page: Page number (default: 1, min: 1)
- limit: Items per page (default: 20, max: 100)
- project_id: Filter by specific project (optional)
- sort: Sort field (relevance, name, created_at, updated_at)
- order: Sort direction (asc, desc)

#### Responses

**200** - Success

**Schema:** [PaginatedResponseDTO](#paginatedresponsedto)

**Example:**

```json
{
    "data": [],
    "page": 123,
    "limit": 123,
    "total": 123,
    "totalPages": 123,
    "message": "example_message"
}
```

**400** - Bad Request

**Example:**

```json
{
    "error": "Bad Request",
    "message": "Invalid input data"
}
```

**401** - Unauthorized

**Example:**

```json
{
    "error": "Unauthorized",
    "message": "Authentication required"
}
```


---

## ExportJobController

ExportJob endpoints

**Route Count:** 10

### api_export_jobs_list

**Path:** `/api/export-jobs`  
**Methods:** `GET`  
**Description:** Returns a paginated list of export jobs belonging to the authenticated user.
Supports filtering by status and format, with configurable pagination.

**Summary:** List export jobs for authenticated user

🔒 **Security:** IsGranted

#### Parameters

- **request** `Symfony\Component\HttpFoundation\Request` (required) - HTTP request with optional query parameters:
- page: Page number (default: 1, min: 1)
- limit: Items per page (default: 20, max: 50)
- status: Filter by job status (pending, processing, completed, failed, cancelled)
- format: Filter by export format (png, jpg, svg, mp4, gif)

#### Responses

**200** - Success

**Schema:** [ExportJobResponseDTO](#exportjobresponsedto)

**Example:**

```json
{
    "success": true,
    "message": "example_message",
    "job": [],
    "jobs": [],
    "total": 123,
    "page": 123,
    "totalPages": 123,
    "timestamp": "example_value"
}
```

**400** - Bad Request

**Example:**

```json
{
    "error": "Bad Request",
    "message": "Invalid input data"
}
```

**401** - Unauthorized

**Example:**

```json
{
    "error": "Unauthorized",
    "message": "Authentication required"
}
```


---

### api_export_jobs_show

**Path:** `/api/export-jobs/{id}`  
**Methods:** `GET`  
**Description:** Returns detailed information about a single export job.
Only allows access to export jobs owned by the authenticated user.

**Summary:** Get details of a specific export job

🔒 **Security:** IsGranted

#### Parameters

- **exportJob** `App\Entity\ExportJob` (required) - The export job entity (auto-resolved by Symfony)

#### Responses

**200** - Success

**Schema:** [ExportJobResponseDTO](#exportjobresponsedto)

**Example:**

```json
{
    "success": true,
    "message": "example_message",
    "job": [],
    "jobs": [],
    "total": 123,
    "page": 123,
    "totalPages": 123,
    "timestamp": "example_value"
}
```

**400** - Bad Request

**Example:**

```json
{
    "error": "Bad Request",
    "message": "Invalid input data"
}
```

**401** - Unauthorized

**Example:**

```json
{
    "error": "Unauthorized",
    "message": "Authentication required"
}
```


---

### api_export_jobs_create

**Path:** `/api/export-jobs`  
**Methods:** `POST`  
**Description:** Creates a new export job for rendering a design in the specified format and settings.
Validates user access to the design and enqueues the job for background processing.

**Summary:** Create a new export job

🔒 **Security:** IsGranted

#### Request Body

Handles the submission of design export jobs to the background
processing system. Supports multiple output formats and quality
settings for rendering designs to various file types including
static images, PDFs, and animated formats.

**Content Type:** `application/json`

**Schema:** [CreateExportJobRequestDTO](#createexportjobrequestdto)

**Example:**

```json
{
    "designId": 123,
    "format": "example_format",
    "quality": "example_quality",
    "width": 123,
    "height": 123,
    "scale": 123.45,
    "transparent": true,
    "backgroundColor": "example_backgroundColor",
    "animationSettings": []
}
```

#### Responses

**200** - Success

**Schema:** [ExportJobResponseDTO](#exportjobresponsedto)

**Example:**

```json
{
    "success": true,
    "message": "example_message",
    "job": [],
    "jobs": [],
    "total": 123,
    "page": 123,
    "totalPages": 123,
    "timestamp": "example_value"
}
```

**400** - Bad Request

**Example:**

```json
{
    "error": "Bad Request",
    "message": "Invalid input data"
}
```

**401** - Unauthorized

**Example:**

```json
{
    "error": "Unauthorized",
    "message": "Authentication required"
}
```


---

### api_export_jobs_update

**Path:** `/api/export-jobs/{id}`  
**Methods:** `PUT`  
**Description:** Export jobs are immutable after creation and cannot be modified.
This endpoint always returns an error indicating that modifications are not permitted.

**Summary:** Update an export job (Not allowed)

🔒 **Security:** IsGranted

#### Parameters

- **exportJob** `App\Entity\ExportJob` (required) - The export job entity (auto-resolved by Symfony)

#### Responses

**200** - Success

**Schema:** [ErrorResponseDTO](#errorresponsedto)

**Example:**

```json
{
    "message": "example_message",
    "details": [],
    "code": "example_code",
    "timestamp": "example_timestamp"
}
```

**400** - Bad Request

**Example:**

```json
{
    "error": "Bad Request",
    "message": "Invalid input data"
}
```

**401** - Unauthorized

**Example:**

```json
{
    "error": "Unauthorized",
    "message": "Authentication required"
}
```


---

### api_export_jobs_delete

**Path:** `/api/export-jobs/{id}`  
**Methods:** `DELETE`  
**Description:** Deletes an export job and its associated output file.
Only allows deletion of jobs in pending, failed, or completed status.
Only allows access to export jobs owned by the authenticated user.

**Summary:** Delete an export job

🔒 **Security:** IsGranted

#### Parameters

- **exportJob** `App\Entity\ExportJob` (required) - The export job entity (auto-resolved by Symfony)

#### Responses

**200** - Success

**Schema:** [SuccessResponseDTO](#successresponsedto)

**Example:**

```json
{
    "message": "example_message",
    "success": true,
    "timestamp": "example_timestamp"
}
```

**400** - Bad Request

**Example:**

```json
{
    "error": "Bad Request",
    "message": "Invalid input data"
}
```

**401** - Unauthorized

**Example:**

```json
{
    "error": "Unauthorized",
    "message": "Authentication required"
}
```


---

### api_export_jobs_cancel

**Path:** `/api/export-jobs/{id}/cancel`  
**Methods:** `POST`  
**Description:** Cancels an export job that is currently pending or being processed.
Sets the job status to cancelled and stops any ongoing processing.
Only allows access to export jobs owned by the authenticated user.

**Summary:** Cancel a pending or processing export job

🔒 **Security:** IsGranted

#### Parameters

- **exportJob** `App\Entity\ExportJob` (required) - The export job entity (auto-resolved by Symfony)

#### Responses

**200** - Success

**Schema:** [ExportJobResponseDTO](#exportjobresponsedto)

**Example:**

```json
{
    "success": true,
    "message": "example_message",
    "job": [],
    "jobs": [],
    "total": 123,
    "page": 123,
    "totalPages": 123,
    "timestamp": "example_value"
}
```

**400** - Bad Request

**Example:**

```json
{
    "error": "Bad Request",
    "message": "Invalid input data"
}
```

**401** - Unauthorized

**Example:**

```json
{
    "error": "Unauthorized",
    "message": "Authentication required"
}
```


---

### api_export_jobs_retry

**Path:** `/api/export-jobs/{id}/retry`  
**Methods:** `POST`  
**Description:** Resets a failed export job back to pending status for re-processing.
Clears error messages and resets progress to zero.
Only allows retry of jobs with failed status.
Only allows access to export jobs owned by the authenticated user.

**Summary:** Retry a failed export job

🔒 **Security:** IsGranted

#### Parameters

- **exportJob** `App\Entity\ExportJob` (required) - The export job entity (auto-resolved by Symfony)

#### Responses

**200** - Success

**Schema:** [ExportJobResponseDTO](#exportjobresponsedto)

**Example:**

```json
{
    "success": true,
    "message": "example_message",
    "job": [],
    "jobs": [],
    "total": 123,
    "page": 123,
    "totalPages": 123,
    "timestamp": "example_value"
}
```

**400** - Bad Request

**Example:**

```json
{
    "error": "Bad Request",
    "message": "Invalid input data"
}
```

**401** - Unauthorized

**Example:**

```json
{
    "error": "Unauthorized",
    "message": "Authentication required"
}
```


---

### api_export_jobs_download

**Path:** `/api/export-jobs/{id}/download`  
**Methods:** `GET`  
**Description:** Downloads the generated file from a completed export job.
Returns the file as an attachment with appropriate headers.
Only allows download of completed jobs with existing output files.
Only allows access to export jobs owned by the authenticated user.

**Summary:** Download export job output file

🔒 **Security:** IsGranted

#### Parameters

- **exportJob** `App\Entity\ExportJob` (required) - The export job entity (auto-resolved by Symfony)

#### Responses

**200** - Success

**Example:**

```json
{
    "message": "Success"
}
```

**400** - Bad Request

**Example:**

```json
{
    "error": "Bad Request",
    "message": "Invalid input data"
}
```

**401** - Unauthorized

**Example:**

```json
{
    "error": "Unauthorized",
    "message": "Authentication required"
}
```


---

### api_export_jobs_stats

**Path:** `/api/export-jobs/stats`  
**Methods:** `GET`  
**Description:** Returns comprehensive statistics about the user's export job usage,
including totals by status, format breakdown, and success rate.

**Summary:** Get export job statistics for authenticated user

🔒 **Security:** IsGranted

#### Responses

**200** - Success

**Schema:** [ErrorResponseDTO](#errorresponsedto)

**Example:**

```json
{
    "message": "example_message",
    "details": [],
    "code": "example_code",
    "timestamp": "example_timestamp"
}
```

**400** - Bad Request

**Example:**

```json
{
    "error": "Bad Request",
    "message": "Invalid input data"
}
```

**401** - Unauthorized

**Example:**

```json
{
    "error": "Unauthorized",
    "message": "Authentication required"
}
```


---

### api_export_jobs_queue_status

**Path:** `/api/export-jobs/queue-status`  
**Methods:** `GET`  
**Description:** Returns system-wide export job queue statistics and health information.
Includes pending/processing job counts, average processing times, and queue health metrics.
Only accessible to users with ROLE_ADMIN.

**Summary:** Get export job queue status (Admin only)

🔒 **Security:** IsGranted

#### Responses

**200** - Success

**Schema:** [ErrorResponseDTO](#errorresponsedto)

**Example:**

```json
{
    "message": "example_message",
    "details": [],
    "code": "example_code",
    "timestamp": "example_timestamp"
}
```

**400** - Bad Request

**Example:**

```json
{
    "error": "Bad Request",
    "message": "Invalid input data"
}
```

**401** - Unauthorized

**Example:**

```json
{
    "error": "Unauthorized",
    "message": "Authentication required"
}
```


---

## LayerController

Layer endpoints

**Route Count:** 7

### api_layers_create

**Path:** `/api/layers`  
**Methods:** `POST`  
**Description:** Creates a new layer with specified properties and adds it to the target design.
Automatically assigns appropriate z-index and validates design ownership.
Supports various layer types including text, shape, image, and group layers.

**Summary:** Create a new layer in a design

#### Request Body

Handles the creation of new design layers with all necessary properties
for proper initialization in the canvas editor. Supports all layer types
including text, image, shape, group, video, and audio layers.

**Content Type:** `application/json`

**Schema:** [CreateLayerRequestDTO](#createlayerrequestdto)

**Example:**

```json
{
    "designId": "example_designId",
    "type": "example_type",
    "name": "example_name",
    "properties": "example_properties",
    "transform": {
        "x": 123.45,
        "y": 123.45,
        "width": 123.45,
        "height": 123.45,
        "rotation": 123.45,
        "scaleX": 123.45,
        "scaleY": 123.45,
        "skewX": 123.45,
        "skewY": 123.45,
        "opacity": 123.45
    },
    "zIndex": 123,
    "visible": true,
    "locked": true,
    "parentLayerId": "example_parentLayerId"
}
```

#### Responses

**200** - Success

**Schema:** [LayerResponseDTO](#layerresponsedto)

**Example:**

```json
{
    "success": true,
    "message": "example_message",
    "layer": [],
    "layers": [],
    "timestamp": "example_value"
}
```

**400** - Bad Request

**Example:**

```json
{
    "error": "Bad Request",
    "message": "Invalid input data"
}
```

**401** - Unauthorized

**Example:**

```json
{
    "error": "Unauthorized",
    "message": "Authentication required"
}
```


---

### api_layers_show

**Path:** `/api/layers/{id}`  
**Methods:** `GET`  
**Description:** Returns comprehensive information about a single layer including all properties,
position, styling, and metadata. Validates access permissions through design ownership.

**Summary:** Get details of a specific layer

#### Parameters

- **id** `int` (required) - The layer ID to retrieve

#### Responses

**200** - Success

**Schema:** [LayerResponseDTO](#layerresponsedto)

**Example:**

```json
{
    "success": true,
    "message": "example_message",
    "layer": [],
    "layers": [],
    "timestamp": "example_value"
}
```

**400** - Bad Request

**Example:**

```json
{
    "error": "Bad Request",
    "message": "Invalid input data"
}
```

**401** - Unauthorized

**Example:**

```json
{
    "error": "Unauthorized",
    "message": "Authentication required"
}
```


---

### api_layers_update

**Path:** `/api/layers/{id}`  
**Methods:** `PUT`  
**Description:** Modifies layer properties including position, dimensions, styling, and content.
Supports partial updates with validation and maintains layer hierarchy integrity.
Users can only update layers in designs they own.

**Summary:** Update an existing layer

#### Parameters

- **id** `int` (required) - The layer ID to update

#### Request Body

Handles modification of existing design layers including visual properties,
positioning, visibility, and hierarchy changes. All fields are optional
to support partial updates of layer attributes.

**Content Type:** `application/json`

**Schema:** [UpdateLayerRequestDTO](#updatelayerrequestdto)

**Example:**

```json
{
    "name": "example_name",
    "properties": "example_properties",
    "transform": {
        "x": 123.45,
        "y": 123.45,
        "width": 123.45,
        "height": 123.45,
        "rotation": 123.45,
        "scaleX": 123.45,
        "scaleY": 123.45,
        "skewX": 123.45,
        "skewY": 123.45,
        "opacity": 123.45
    },
    "zIndex": 123,
    "visible": true,
    "locked": true,
    "parentLayerId": "example_parentLayerId"
}
```

#### Responses

**200** - Success

**Schema:** [LayerResponseDTO](#layerresponsedto)

**Example:**

```json
{
    "success": true,
    "message": "example_message",
    "layer": [],
    "layers": [],
    "timestamp": "example_value"
}
```

**400** - Bad Request

**Example:**

```json
{
    "error": "Bad Request",
    "message": "Invalid input data"
}
```

**401** - Unauthorized

**Example:**

```json
{
    "error": "Unauthorized",
    "message": "Authentication required"
}
```


---

### api_layers_delete

**Path:** `/api/layers/{id}`  
**Methods:** `DELETE`  
**Description:** Permanently removes a layer and all its associated data from the design.
Automatically adjusts z-indexes of remaining layers to maintain proper ordering.
Users can only delete layers in designs they own.

**Summary:** Delete a layer from a design

#### Parameters

- **id** `int` (required) - The layer ID to delete

#### Responses

**200** - Success

**Schema:** [SuccessResponseDTO](#successresponsedto)

**Example:**

```json
{
    "message": "example_message",
    "success": true,
    "timestamp": "example_timestamp"
}
```

**400** - Bad Request

**Example:**

```json
{
    "error": "Bad Request",
    "message": "Invalid input data"
}
```

**401** - Unauthorized

**Example:**

```json
{
    "error": "Unauthorized",
    "message": "Authentication required"
}
```


---

### api_layers_duplicate

**Path:** `/api/layers/{id}/duplicate`  
**Methods:** `POST`  
**Description:** Creates an exact copy of an existing layer with all properties and styling.
The duplicated layer is positioned slightly offset from the original and
assigned a new z-index to appear on top. Maintains all layer relationships.

**Summary:** Duplicate a layer within a design

#### Parameters

- **id** `int` (required) - The layer ID to duplicate

#### Request Body

Handles the duplication of design layers with optional customization
of the duplicate layer name and target design. Used by the layer
management system to create copies of existing layers within the
same design or across different designs.

**Content Type:** `application/json`

**Schema:** [DuplicateLayerRequestDTO](#duplicatelayerrequestdto)

**Example:**

```json
{
    "name": "example_name",
    "targetDesignId": "example_targetDesignId"
}
```

#### Responses

**200** - Success

**Schema:** [LayerResponseDTO](#layerresponsedto)

**Example:**

```json
{
    "success": true,
    "message": "example_message",
    "layer": [],
    "layers": [],
    "timestamp": "example_value"
}
```

**400** - Bad Request

**Example:**

```json
{
    "error": "Bad Request",
    "message": "Invalid input data"
}
```

**401** - Unauthorized

**Example:**

```json
{
    "error": "Unauthorized",
    "message": "Authentication required"
}
```


---

### api_layers_move

**Path:** `/api/layers/{id}/move`  
**Methods:** `PUT`  
**Description:** Changes the layer's position in the z-index stack or moves it to a specific position.
Supports moving layers up/down in the stack or to absolute positions.
Automatically adjusts other layers' z-indexes to maintain proper ordering.

**Summary:** Move a layer within the design hierarchy

#### Parameters

- **id** `int` (required) - The layer ID to move

#### Request Body

Handles repositioning of layers within the design canvas z-order.
Supports both relative movements (up/down/top/bottom) and absolute
positioning via z-index targeting. Used in the layer management
system to control layer stacking order and visual hierarchy.

**Content Type:** `application/json`

**Schema:** [MoveLayerRequestDTO](#movelayerrequestdto)

**Example:**

```json
{
    "direction": "example_direction",
    "targetZIndex": 123
}
```

#### Responses

**200** - Success

**Schema:** [LayerResponseDTO](#layerresponsedto)

**Example:**

```json
{
    "success": true,
    "message": "example_message",
    "layer": [],
    "layers": [],
    "timestamp": "example_value"
}
```

**400** - Bad Request

**Example:**

```json
{
    "error": "Bad Request",
    "message": "Invalid input data"
}
```

**401** - Unauthorized

**Example:**

```json
{
    "error": "Unauthorized",
    "message": "Authentication required"
}
```


---

### api_layers_bulk_update

**Path:** `/api/layers/bulk-update`  
**Methods:** `PUT`  
**Description:** Updates multiple layers in a single operation for performance efficiency.
Processes each layer individually with proper validation and permission checks.
Returns detailed results including successful updates and any failures.

**Summary:** Bulk update multiple layers

#### Request Body

This DTO handles batch operations for updating layer properties, allowing
efficient modification of multiple layers in a single API call. Each layer
update includes the layer ID and the specific changes to apply.

**Content Type:** `application/json`

**Schema:** [BulkUpdateLayersRequestDTO](#bulkupdatelayersrequestdto)

**Example:**

```json
{
    "layers": []
}
```

#### Responses

**200** - Success

**Schema:** [SuccessResponseDTO](#successresponsedto)

**Example:**

```json
{
    "message": "example_message",
    "success": true,
    "timestamp": "example_timestamp"
}
```

**400** - Bad Request

**Example:**

```json
{
    "error": "Bad Request",
    "message": "Invalid input data"
}
```

**401** - Unauthorized

**Example:**

```json
{
    "error": "Unauthorized",
    "message": "Authentication required"
}
```


---

## MediaController

Media endpoints

**Route Count:** 9

### api_media_list

**Path:** `/api/media`  
**Methods:** `GET`  
**Description:** Returns a paginated list of media files belonging to the authenticated user.
Supports filtering by type, format, and search functionality.

**Summary:** List media files for authenticated user

#### Request Body

Handles search operations for media files with advanced filtering
capabilities including type, source, pagination, and tag-based
filtering. Used by the media library to provide rich search
functionality for users to find specific media files.

**Content Type:** `application/json`

**Schema:** [SearchMediaRequestDTO](#searchmediarequestdto)

**Example:**

```json
{
    "page": 123,
    "limit": 123,
    "type": "example_type",
    "source": "example_source",
    "search": "example_search",
    "tags": "example_tags"
}
```

#### Responses

**200** - Success

**Schema:** [PaginatedResponseDTO](#paginatedresponsedto)

**Example:**

```json
{
    "data": [],
    "page": 123,
    "limit": 123,
    "total": 123,
    "totalPages": 123,
    "message": "example_message"
}
```

**400** - Bad Request

**Example:**

```json
{
    "error": "Bad Request",
    "message": "Invalid input data"
}
```

**401** - Unauthorized

**Example:**

```json
{
    "error": "Unauthorized",
    "message": "Authentication required"
}
```


---

### api_media_show

**Path:** `/api/media/{uuid}`  
**Methods:** `GET`  
**Description:** Returns detailed information about a single media file including metadata.
Only allows access to media files owned by the authenticated user.

**Summary:** Get details of a specific media file

#### Parameters

- **uuid** `string` (required) - The media file UUID

#### Responses

**200** - Success

**Schema:** [MediaResponseDTO](#mediaresponsedto)

**Example:**

```json
{
    "success": true,
    "message": "example_message",
    "media": [],
    "mediaList": [],
    "total": 123,
    "page": 123,
    "totalPages": 123,
    "timestamp": "example_value"
}
```

**400** - Bad Request

**Example:**

```json
{
    "error": "Bad Request",
    "message": "Invalid input data"
}
```

**401** - Unauthorized

**Example:**

```json
{
    "error": "Unauthorized",
    "message": "Authentication required"
}
```


---

### api_media_create

**Path:** `/api/media`  
**Methods:** `POST`  
**Description:** Creates a new media file record in the database with the provided metadata.
This endpoint handles media file upload processing and validation.
All uploaded media files are associated with the authenticated user.

**Summary:** Create a new media file entry

#### Request Body

This DTO handles the creation of media assets including images, videos,
and audio files from various sources (uploads, stock photo APIs, etc.).
Includes comprehensive metadata and organizational features.

**Content Type:** `application/json`

**Schema:** [CreateMediaRequestDTO](#createmediarequestdto)

**Example:**

```json
{
    "name": "example_name",
    "type": "example_type",
    "mimeType": "example_mimeType",
    "size": 123,
    "url": "example_url",
    "thumbnailUrl": "example_thumbnailUrl",
    "width": 123,
    "height": 123,
    "duration": 123.45,
    "source": "example_source",
    "sourceId": "example_sourceId",
    "metadata": {
        "fileSize": 123,
        "mimeType": "example_mimeType",
        "width": 123,
        "height": 123,
        "duration": 123.45,
        "bitrate": 123,
        "sampleRate": 123,
        "channels": 123,
        "colorSpace": "example_colorSpace",
        "hasTransparency": true,
        "frameRate": 123,
        "codec": "example_codec",
        "aspectRatio": 123.45
    },
    "tags": [],
    "attribution": "example_attribution",
    "license": "example_license",
    "isPremium": true,
    "isActive": true
}
```

#### Responses

**200** - Success

**Schema:** [MediaResponseDTO](#mediaresponsedto)

**Example:**

```json
{
    "success": true,
    "message": "example_message",
    "media": [],
    "mediaList": [],
    "total": 123,
    "page": 123,
    "totalPages": 123,
    "timestamp": "example_value"
}
```

**400** - Bad Request

**Example:**

```json
{
    "error": "Bad Request",
    "message": "Invalid input data"
}
```

**401** - Unauthorized

**Example:**

```json
{
    "error": "Unauthorized",
    "message": "Authentication required"
}
```


---

### api_media_update

**Path:** `/api/media/{uuid}`  
**Methods:** `PUT`  
**Description:** Updates the metadata and properties of an existing media file.
Only allows updating specific fields like name, metadata, tags, and status flags.
Core file properties like URL, type, and dimensions cannot be modified.
Users can only update media files they own.

**Summary:** Update media file metadata

#### Parameters

- **uuid** `string` (required) - The media file UUID to update

#### Request Body

This DTO handles partial updates to media assets, allowing clients to
update only the fields they want to change. All fields are optional
and null values indicate no change should be made.

**Content Type:** `application/json`

**Schema:** [UpdateMediaRequestDTO](#updatemediarequestdto)

**Example:**

```json
{
    "name": "example_name",
    "description": "example_description",
    "tags": [],
    "metadata": {
        "fileSize": 123,
        "mimeType": "example_mimeType",
        "width": 123,
        "height": 123,
        "duration": 123.45,
        "bitrate": 123,
        "sampleRate": 123,
        "channels": 123,
        "colorSpace": "example_colorSpace",
        "hasTransparency": true,
        "frameRate": 123,
        "codec": "example_codec",
        "aspectRatio": 123.45
    },
    "isPremium": true,
    "isActive": true,
    "isPublic": true
}
```

#### Responses

**200** - Success

**Schema:** [MediaResponseDTO](#mediaresponsedto)

**Example:**

```json
{
    "success": true,
    "message": "example_message",
    "media": [],
    "mediaList": [],
    "total": 123,
    "page": 123,
    "totalPages": 123,
    "timestamp": "example_value"
}
```

**400** - Bad Request

**Example:**

```json
{
    "error": "Bad Request",
    "message": "Invalid input data"
}
```

**401** - Unauthorized

**Example:**

```json
{
    "error": "Unauthorized",
    "message": "Authentication required"
}
```


---

### api_media_delete

**Path:** `/api/media/{uuid}`  
**Methods:** `DELETE`  
**Description:** Permanently removes a media file record from the database.
This operation also triggers cleanup of associated file storage.
Users can only delete media files they own for security.

**Summary:** Delete a media file

#### Parameters

- **uuid** `string` (required) - The media file UUID to delete

#### Responses

**200** - Success

**Schema:** [SuccessResponseDTO](#successresponsedto)

**Example:**

```json
{
    "message": "example_message",
    "success": true,
    "timestamp": "example_timestamp"
}
```

**400** - Bad Request

**Example:**

```json
{
    "error": "Bad Request",
    "message": "Invalid input data"
}
```

**401** - Unauthorized

**Example:**

```json
{
    "error": "Unauthorized",
    "message": "Authentication required"
}
```


---

### api_media_search

**Path:** `/api/media/search`  
**Methods:** `GET`  
**Description:** Performs advanced search across user's media library with filtering and sorting.
Supports full-text search on filenames, descriptions, and tags.
Returns paginated results with comprehensive media metadata.

**Summary:** Search media files

#### Request Body

Handles search operations for media files with advanced filtering
capabilities including type, source, pagination, and tag-based
filtering. Used by the media library to provide rich search
functionality for users to find specific media files.

**Content Type:** `application/json`

**Schema:** [SearchMediaRequestDTO](#searchmediarequestdto)

**Example:**

```json
{
    "page": 123,
    "limit": 123,
    "type": "example_type",
    "source": "example_source",
    "search": "example_search",
    "tags": "example_tags"
}
```

#### Responses

**200** - Success

**Schema:** [PaginatedResponseDTO](#paginatedresponsedto)

**Example:**

```json
{
    "data": [],
    "page": 123,
    "limit": 123,
    "total": 123,
    "totalPages": 123,
    "message": "example_message"
}
```

**400** - Bad Request

**Example:**

```json
{
    "error": "Bad Request",
    "message": "Invalid input data"
}
```

**401** - Unauthorized

**Example:**

```json
{
    "error": "Unauthorized",
    "message": "Authentication required"
}
```


---

### api_media_duplicate

**Path:** `/api/media/duplicate/{uuid}`  
**Methods:** `POST`  
**Description:** Creates a copy of an existing media file for the authenticated user.
The duplicated media inherits all properties from the original but gets
a new UUID and is owned by the current user. This allows users to
create personal copies of accessible media files.

**Summary:** Duplicate a media file

#### Parameters

- **uuid** `string` (required) - The UUID of the media file to duplicate

#### Request Body

Handles the duplication of media files including images, videos,
and other assets. Creates a personal copy of accessible media
files for the authenticated user with optional name customization.
Used by the media library for creating user-owned copies of media.

**Content Type:** `application/json`

**Schema:** [DuplicateMediaRequestDTO](#duplicatemediarequestdto)

**Example:**

```json
{
    "name": "example_name"
}
```

#### Responses

**200** - Success

**Schema:** [MediaResponseDTO](#mediaresponsedto)

**Example:**

```json
{
    "success": true,
    "message": "example_message",
    "media": [],
    "mediaList": [],
    "total": 123,
    "page": 123,
    "totalPages": 123,
    "timestamp": "example_value"
}
```

**400** - Bad Request

**Example:**

```json
{
    "error": "Bad Request",
    "message": "Invalid input data"
}
```

**401** - Unauthorized

**Example:**

```json
{
    "error": "Unauthorized",
    "message": "Authentication required"
}
```


---

### api_media_stock_search

**Path:** `/api/media/stock/search`  
**Methods:** `GET`  
**Description:** Integrates with external stock media APIs (Unsplash, Pexels, Pixabay, etc.)
to provide users with access to high-quality stock images and videos.
Results include licensing information and attribution requirements.
Currently in development - returns empty results with implementation notice.

**Summary:** Search stock media from external providers

#### Request Body

Handles search operations for stock media from external providers
like Unsplash, Pexels, and Pixabay. Provides structured search
parameters for integrating with third-party stock media APIs
to expand the available media library for users.

**Content Type:** `application/json`

**Schema:** [StockSearchRequestDTO](#stocksearchrequestdto)

**Example:**

```json
{
    "query": "example_query",
    "page": 123,
    "limit": 123,
    "type": "example_type",
    "source": "example_source"
}
```

#### Responses

**200** - Success

**Schema:** [PaginatedResponseDTO](#paginatedresponsedto)

**Example:**

```json
{
    "data": [],
    "page": 123,
    "limit": 123,
    "total": 123,
    "totalPages": 123,
    "message": "example_message"
}
```

**400** - Bad Request

**Example:**

```json
{
    "error": "Bad Request",
    "message": "Invalid input data"
}
```

**401** - Unauthorized

**Example:**

```json
{
    "error": "Unauthorized",
    "message": "Authentication required"
}
```


---

### api_media_bulk_delete

**Path:** `/api/media/bulk/delete`  
**Methods:** `DELETE`  
**Description:** Deletes multiple media files in a single operation for efficiency.
Processes each file individually with proper permission checks.
Returns detailed results including successful deletions and failures.
Users can only delete media files they own.

**Summary:** Bulk delete multiple media files

#### Request Body

Handles bulk deletion of multiple media files by their UUIDs.
Used by the media management system to allow users to delete
multiple media files in a single operation with validation
and permission checks.

**Content Type:** `application/json`

**Schema:** [BulkDeleteMediaRequestDTO](#bulkdeletemediarequestdto)

**Example:**

```json
{
    "uuids": []
}
```

#### Responses

**200** - Success

**Schema:** [SuccessResponseDTO](#successresponsedto)

**Example:**

```json
{
    "message": "example_message",
    "success": true,
    "timestamp": "example_timestamp"
}
```

**400** - Bad Request

**Example:**

```json
{
    "error": "Bad Request",
    "message": "Invalid input data"
}
```

**401** - Unauthorized

**Example:**

```json
{
    "error": "Unauthorized",
    "message": "Authentication required"
}
```


---

## PluginController

Plugin endpoints

**Route Count:** 12

### api_plugins_list

**Path:** `/api/plugins`  
**Methods:** `GET`  
**Description:** Supports filtering by category, search terms, status, and sorting.
Returns paginated results with plugin metadata.

**Summary:** Retrieve paginated list of plugins with filtering options

#### Parameters

- **request** `Symfony\Component\HttpFoundation\Request` (required) - HTTP request containing query parameters

#### Responses

**200** - Success

**Example:**

```json
{
    "message": "Success"
}
```

**400** - Bad Request

**Example:**

```json
{
    "error": "Bad Request",
    "message": "Invalid input data"
}
```

**401** - Unauthorized

**Example:**

```json
{
    "error": "Unauthorized",
    "message": "Authentication required"
}
```


---

### api_plugins_show

**Path:** `/api/plugins/{id}`  
**Methods:** `GET`  
**Description:** Returns comprehensive plugin details including manifest, permissions,
and review information. Access is restricted based on plugin status and user role.

**Summary:** Retrieve detailed information about a specific plugin

#### Parameters

- **plugin** `App\Entity\Plugin` (required) - The plugin entity to display

#### Responses

**200** - Success

**Example:**

```json
{
    "message": "Success"
}
```

**400** - Bad Request

**Example:**

```json
{
    "error": "Bad Request",
    "message": "Invalid input data"
}
```

**401** - Unauthorized

**Example:**

```json
{
    "error": "Unauthorized",
    "message": "Authentication required"
}
```


---

### api_plugins_create

**Path:** `/api/plugins`  
**Methods:** `POST`  
**Description:** Creates a new plugin with the provided metadata. The plugin is initially
set to pending status and requires admin approval before becoming available.

**Summary:** Create a new plugin

🔒 **Security:** IsGranted

#### Request Body

Handles the submission of new plugins to the platform's plugin
system. Contains all necessary metadata and configuration for
plugin registration, validation, and eventual approval process.
Used in the plugin management system to onboard new extensions.

**Content Type:** `application/json`

**Schema:** [CreatePluginRequestDTO](#createpluginrequestdto)

**Example:**

```json
{
    "name": "example_name",
    "description": "example_description",
    "categories": [],
    "version": "example_version",
    "permissions": [],
    "manifest": []
}
```

#### Responses

**200** - Success

**Example:**

```json
{
    "message": "Success"
}
```

**400** - Bad Request

**Example:**

```json
{
    "error": "Bad Request",
    "message": "Invalid input data"
}
```

**401** - Unauthorized

**Example:**

```json
{
    "error": "Unauthorized",
    "message": "Authentication required"
}
```


---

### api_plugins_update

**Path:** `/api/plugins/{id}`  
**Methods:** `PUT`  
**Description:** Updates plugin metadata. Only the plugin developer or admin can perform updates.
Admin users can also modify the plugin status.

**Summary:** Update an existing plugin

🔒 **Security:** IsGranted

#### Parameters

- **plugin** `App\Entity\Plugin` (required) - The plugin entity to update

#### Request Body

Handles partial updates to existing plugins in the platform's
plugin system. All fields are optional and null values indicate
no change should be made. Used for both plugin developer updates
and administrative status changes.

**Content Type:** `application/json`

**Schema:** [UpdatePluginRequestDTO](#updatepluginrequestdto)

**Example:**

```json
{
    "name": "example_name",
    "description": "example_description",
    "categories": [],
    "version": "example_version",
    "permissions": [],
    "manifest": [],
    "status": "example_status"
}
```

#### Responses

**200** - Success

**Example:**

```json
{
    "message": "Success"
}
```

**400** - Bad Request

**Example:**

```json
{
    "error": "Bad Request",
    "message": "Invalid input data"
}
```

**401** - Unauthorized

**Example:**

```json
{
    "error": "Unauthorized",
    "message": "Authentication required"
}
```


---

### api_plugins_delete

**Path:** `/api/plugins/{id}`  
**Methods:** `DELETE`  
**Description:** Permanently removes a plugin from the system. Only the plugin developer
or administrators can delete plugins. This action is irreversible.

**Summary:** Delete a plugin

🔒 **Security:** IsGranted

#### Parameters

- **plugin** `App\Entity\Plugin` (required) - The plugin entity to delete

#### Responses

**200** - Success

**Example:**

```json
{
    "message": "Success"
}
```

**400** - Bad Request

**Example:**

```json
{
    "error": "Bad Request",
    "message": "Invalid input data"
}
```

**401** - Unauthorized

**Example:**

```json
{
    "error": "Unauthorized",
    "message": "Authentication required"
}
```


---

### api_plugins_install

**Path:** `/api/plugins/{id}/install`  
**Methods:** `POST`  
**Description:** Installs an approved plugin for the authenticated user. Increments the
installation count and handles plugin registration logic.

**Summary:** Install a plugin for the current user

🔒 **Security:** IsGranted

#### Parameters

- **plugin** `App\Entity\Plugin` (required) - The plugin entity to install

#### Responses

**200** - Success

**Example:**

```json
{
    "message": "Success"
}
```

**400** - Bad Request

**Example:**

```json
{
    "error": "Bad Request",
    "message": "Invalid input data"
}
```

**401** - Unauthorized

**Example:**

```json
{
    "error": "Unauthorized",
    "message": "Authentication required"
}
```


---

### api_plugins_uninstall

**Path:** `/api/plugins/{id}/uninstall`  
**Methods:** `POST`  
**Description:** Removes a plugin from the user's installed plugins and cleans up
associated data and files.

**Summary:** Uninstall a plugin for the current user

🔒 **Security:** IsGranted

#### Parameters

- **plugin** `App\Entity\Plugin` (required) - The plugin entity to uninstall

#### Responses

**200** - Success

**Example:**

```json
{
    "message": "Success"
}
```

**400** - Bad Request

**Example:**

```json
{
    "error": "Bad Request",
    "message": "Invalid input data"
}
```

**401** - Unauthorized

**Example:**

```json
{
    "error": "Unauthorized",
    "message": "Authentication required"
}
```


---

### api_plugins_upload_file

**Path:** `/api/plugins/{id}/upload-file`  
**Methods:** `POST`  
**Description:** Allows plugin developers to upload plugin files (ZIP format).
Performs validation and stores the file securely.

**Summary:** Upload a plugin file

🔒 **Security:** IsGranted

#### Parameters

- **plugin** `App\Entity\Plugin` (required) - The plugin entity to upload files for

#### Request Body

Handles validation and encapsulation of plugin file upload data including
file type validation, size constraints, and security checks for plugin files.

**Content Type:** `application/json`

**Schema:** [UploadPluginFileRequestDTO](#uploadpluginfilerequestdto)

**Example:**

```json
{
    "file": "example_value"
}
```

#### Responses

**200** - Success

**Example:**

```json
{
    "message": "Success"
}
```

**400** - Bad Request

**Example:**

```json
{
    "error": "Bad Request",
    "message": "Invalid input data"
}
```

**401** - Unauthorized

**Example:**

```json
{
    "error": "Unauthorized",
    "message": "Authentication required"
}
```


---

### api_plugins_approve

**Path:** `/api/plugins/{id}/approve`  
**Methods:** `POST`  
**Description:** Changes plugin status to approved, making it available in the marketplace.
Records approval timestamp and reviewing administrator.

**Summary:** Approve a plugin (Admin only)

🔒 **Security:** IsGranted

#### Parameters

- **plugin** `App\Entity\Plugin` (required) - The plugin entity to approve

#### Responses

**200** - Success

**Example:**

```json
{
    "message": "Success"
}
```

**400** - Bad Request

**Example:**

```json
{
    "error": "Bad Request",
    "message": "Invalid input data"
}
```

**401** - Unauthorized

**Example:**

```json
{
    "error": "Unauthorized",
    "message": "Authentication required"
}
```


---

### api_plugins_reject

**Path:** `/api/plugins/{id}/reject`  
**Methods:** `POST`  
**Description:** Changes plugin status to rejected with a provided reason.
Records rejection timestamp and reviewing admin.

**Summary:** Reject a plugin (Admin only)

🔒 **Security:** IsGranted

#### Parameters

- **plugin** `App\Entity\Plugin` (required) - The plugin entity to reject

#### Request Body

**Content Type:** `application/json`

**Schema:** [RejectPluginRequestDTO](#rejectpluginrequestdto)

**Example:**

```json
{
    "reason": "example_reason"
}
```

#### Responses

**200** - Success

**Example:**

```json
{
    "message": "Success"
}
```

**400** - Bad Request

**Example:**

```json
{
    "error": "Bad Request",
    "message": "Invalid input data"
}
```

**401** - Unauthorized

**Example:**

```json
{
    "error": "Unauthorized",
    "message": "Authentication required"
}
```


---

### api_plugins_categories

**Path:** `/api/plugins/categories`  
**Methods:** `GET`  
**Description:** Returns a list of all available plugin categories for filtering
and classification purposes.

**Summary:** Get available plugin categories

#### Responses

**200** - Success

**Example:**

```json
{
    "message": "Success"
}
```

**400** - Bad Request

**Example:**

```json
{
    "error": "Bad Request",
    "message": "Invalid input data"
}
```

**401** - Unauthorized

**Example:**

```json
{
    "error": "Unauthorized",
    "message": "Authentication required"
}
```


---

### api_plugins_my_plugins

**Path:** `/api/plugins/my-plugins`  
**Methods:** `GET`  
**Description:** Returns paginated list of plugins created by the authenticated user,
including all statuses (pending, approved, rejected).

**Summary:** Get current user's plugins

🔒 **Security:** IsGranted

#### Parameters

- **request** `Symfony\Component\HttpFoundation\Request` (required) - HTTP request containing pagination parameters

#### Responses

**200** - Success

**Example:**

```json
{
    "message": "Success"
}
```

**400** - Bad Request

**Example:**

```json
{
    "error": "Bad Request",
    "message": "Invalid input data"
}
```

**401** - Unauthorized

**Example:**

```json
{
    "error": "Unauthorized",
    "message": "Authentication required"
}
```


---

## ProjectController

Project endpoints

**Route Count:** 8

### api_projects_index

**Path:** `/api/projects`  
**Methods:** `GET`  
**Description:** Returns a paginated list of projects belonging to the authenticated user.
Supports filtering by status, sorting by various fields, and search functionality.

**Summary:** List projects for authenticated user

#### Parameters

- **request** `Symfony\Component\HttpFoundation\Request` (required) - HTTP request with optional query parameters:
- page: Page number (default: 1, min: 1)
- limit: Items per page (default: 20, max: 100)
- sort: Sort field (name, created_at, updated_at)
- order: Sort direction (asc, desc)
- search: Search term for project name/description
- status: Filter by project status

#### Responses

**200** - Success

**Schema:** [PaginatedResponseDTO](#paginatedresponsedto)

**Example:**

```json
{
    "data": [],
    "page": 123,
    "limit": 123,
    "total": 123,
    "totalPages": 123,
    "message": "example_message"
}
```

**400** - Bad Request

**Example:**

```json
{
    "error": "Bad Request",
    "message": "Invalid input data"
}
```

**401** - Unauthorized

**Example:**

```json
{
    "error": "Unauthorized",
    "message": "Authentication required"
}
```


---

### api_projects_create

**Path:** `/api/projects`  
**Methods:** `POST`  
**Description:** Creates a new project with the provided information and associates it with the authenticated user.
Validates project data and sets default values for optional fields.

**Summary:** Create a new project

#### Request Body

This DTO handles the creation of new projects with all necessary
configuration options, including canvas settings, metadata, and
organizational tags.

**Content Type:** `application/json`

**Schema:** [CreateProjectRequestDTO](#createprojectrequestdto)

**Example:**

```json
{
    "name": "example_name",
    "description": "example_description",
    "isPublic": true,
    "settings": {
        "canvasWidth": 123,
        "canvasHeight": 123,
        "backgroundColor": "example_backgroundColor",
        "orientation": "example_orientation",
        "units": "example_units",
        "dpi": 123,
        "gridVisible": true,
        "rulersVisible": true,
        "guidesVisible": true,
        "snapToGrid": true,
        "snapToGuides": true,
        "snapToObjects": true
    },
    "tags": [],
    "thumbnail": "example_thumbnail"
}
```

#### Responses

**200** - Success

**Schema:** [ProjectResponseDTO](#projectresponsedto)

**Example:**

```json
{
    "success": true,
    "message": "example_message",
    "project": [],
    "projects": [],
    "total": 123,
    "page": 123,
    "totalPages": 123,
    "timestamp": "example_value"
}
```

**400** - Bad Request

**Example:**

```json
{
    "error": "Bad Request",
    "message": "Invalid input data"
}
```

**401** - Unauthorized

**Example:**

```json
{
    "error": "Unauthorized",
    "message": "Authentication required"
}
```


---

### api_projects_show

**Path:** `/api/projects/{id}`  
**Methods:** `GET`  
**Description:** Returns detailed information about a single project.
Only allows access to projects owned by the authenticated user or public projects.

**Summary:** Get details of a specific project

#### Parameters

- **id** `int` (required) - The project ID

#### Responses

**200** - Success

**Schema:** [ProjectResponseDTO](#projectresponsedto)

**Example:**

```json
{
    "success": true,
    "message": "example_message",
    "project": [],
    "projects": [],
    "total": 123,
    "page": 123,
    "totalPages": 123,
    "timestamp": "example_value"
}
```

**400** - Bad Request

**Example:**

```json
{
    "error": "Bad Request",
    "message": "Invalid input data"
}
```

**401** - Unauthorized

**Example:**

```json
{
    "error": "Unauthorized",
    "message": "Authentication required"
}
```


---

### api_projects_update

**Path:** `/api/projects/{id}`  
**Methods:** `PUT`  
**Description:** Updates project information with the provided data.
Only allows updates to projects owned by the authenticated user.
Validates updated data and handles partial updates.

**Summary:** Update an existing project

#### Parameters

- **id** `int` (required) - The project ID to update

#### Request Body

This DTO handles partial updates to projects, allowing clients to
update only the fields they want to change. All fields are optional
and null values indicate no change should be made.

**Content Type:** `application/json`

**Schema:** [UpdateProjectRequestDTO](#updateprojectrequestdto)

**Example:**

```json
{
    "name": "example_name",
    "description": "example_description",
    "isPublic": true,
    "settings": {
        "canvasWidth": 123,
        "canvasHeight": 123,
        "backgroundColor": "example_backgroundColor",
        "orientation": "example_orientation",
        "units": "example_units",
        "dpi": 123,
        "gridVisible": true,
        "rulersVisible": true,
        "guidesVisible": true,
        "snapToGrid": true,
        "snapToGuides": true,
        "snapToObjects": true
    },
    "tags": [],
    "thumbnail": "example_thumbnail"
}
```

#### Responses

**200** - Success

**Schema:** [ProjectResponseDTO](#projectresponsedto)

**Example:**

```json
{
    "success": true,
    "message": "example_message",
    "project": [],
    "projects": [],
    "total": 123,
    "page": 123,
    "totalPages": 123,
    "timestamp": "example_value"
}
```

**400** - Bad Request

**Example:**

```json
{
    "error": "Bad Request",
    "message": "Invalid input data"
}
```

**401** - Unauthorized

**Example:**

```json
{
    "error": "Unauthorized",
    "message": "Authentication required"
}
```


---

### api_projects_delete

**Path:** `/api/projects/{id}`  
**Methods:** `DELETE`  
**Description:** Permanently deletes a project and all its associated data (designs, media files, etc.).
Only allows deletion of projects owned by the authenticated user.
This action cannot be undone.

**Summary:** Delete a project

#### Parameters

- **id** `int` (required) - The project ID to delete

#### Responses

**200** - Success

**Schema:** [SuccessResponseDTO](#successresponsedto)

**Example:**

```json
{
    "message": "example_message",
    "success": true,
    "timestamp": "example_timestamp"
}
```

**400** - Bad Request

**Example:**

```json
{
    "error": "Bad Request",
    "message": "Invalid input data"
}
```

**401** - Unauthorized

**Example:**

```json
{
    "error": "Unauthorized",
    "message": "Authentication required"
}
```


---

### api_projects_duplicate

**Path:** `/api/projects/{id}/duplicate`  
**Methods:** `POST`  
**Description:** Creates a copy of an existing project with all its designs and settings.
Only allows duplication of projects owned by the authenticated user or public projects.
The duplicated project is always private and owned by the authenticated user.

**Summary:** Duplicate an existing project

#### Parameters

- **id** `int` (required) - The project ID to duplicate

#### Request Body

Handles the creation of a complete copy of an existing project
including all associated designs, layers, and metadata. Used
in the project management system to allow users to quickly
create new projects based on existing templates.

**Content Type:** `application/json`

**Schema:** [DuplicateProjectRequestDTO](#duplicateprojectrequestdto)

**Example:**

```json
{
    "name": "example_name"
}
```

#### Responses

**200** - Success

**Schema:** [ProjectResponseDTO](#projectresponsedto)

**Example:**

```json
{
    "success": true,
    "message": "example_message",
    "project": [],
    "projects": [],
    "total": 123,
    "page": 123,
    "totalPages": 123,
    "timestamp": "example_value"
}
```

**400** - Bad Request

**Example:**

```json
{
    "error": "Bad Request",
    "message": "Invalid input data"
}
```

**401** - Unauthorized

**Example:**

```json
{
    "error": "Unauthorized",
    "message": "Authentication required"
}
```


---

### api_projects_public

**Path:** `/api/projects/public`  
**Methods:** `GET`  
**Description:** Returns a paginated list of publicly shared projects from all users.
Supports search, filtering, and sorting functionality for project discovery.

**Summary:** List public projects

#### Request Body

Handles search and filtering operations for user projects
with support for text-based search, tag filtering, and
pagination. Used by the project management system to allow
users to find and organize their design projects efficiently.

**Content Type:** `application/json`

**Schema:** [SearchProjectsRequestDTO](#searchprojectsrequestdto)

**Example:**

```json
{
    "page": 123,
    "limit": 123,
    "search": "example_search",
    "tags": "example_tags"
}
```

#### Responses

**200** - Success

**Schema:** [PaginatedResponseDTO](#paginatedresponsedto)

**Example:**

```json
{
    "data": [],
    "page": 123,
    "limit": 123,
    "total": 123,
    "totalPages": 123,
    "message": "example_message"
}
```

**400** - Bad Request

**Example:**

```json
{
    "error": "Bad Request",
    "message": "Invalid input data"
}
```

**401** - Unauthorized

**Example:**

```json
{
    "error": "Unauthorized",
    "message": "Authentication required"
}
```


---

### api_projects_toggle_share

**Path:** `/api/projects/{id}/share`  
**Methods:** `POST`  
**Description:** Toggles the public/private status of a project.
Only allows modification of projects owned by the authenticated user.
Updates the project's visibility and sharing settings.

**Summary:** Toggle project sharing status

#### Parameters

- **id** `int` (required) - The project ID to toggle sharing for

#### Responses

**200** - Success

**Schema:** [ProjectResponseDTO](#projectresponsedto)

**Example:**

```json
{
    "success": true,
    "message": "example_message",
    "project": [],
    "projects": [],
    "total": 123,
    "page": 123,
    "totalPages": 123,
    "timestamp": "example_value"
}
```

**400** - Bad Request

**Example:**

```json
{
    "error": "Bad Request",
    "message": "Invalid input data"
}
```

**401** - Unauthorized

**Example:**

```json
{
    "error": "Unauthorized",
    "message": "Authentication required"
}
```


---

## TemplateController

Template endpoints

**Route Count:** 6

### api_templates_list

**Path:** `/api/templates`  
**Methods:** `GET`  
**Description:** Returns a paginated list of templates with optional category filtering.
Includes template metadata, thumbnail images, and usage statistics.
Both public templates and user-created templates are included in results.

**Summary:** List available templates with filtering and pagination

#### Parameters

- **request** `Symfony\Component\HttpFoundation\Request` (required) - HTTP request containing query parameters:
- page: Page number (default: 1, min: 1)
- limit: Items per page (default: 20, max: 50)
- category: Category filter (optional)

#### Responses

**200** - Success

**Schema:** [TemplateResponseDTO](#templateresponsedto)

**Example:**

```json
{
    "success": true,
    "message": "example_message",
    "template": [],
    "templates": [],
    "total": 123,
    "page": 123,
    "totalPages": 123,
    "timestamp": "example_value"
}
```

**400** - Bad Request

**Example:**

```json
{
    "error": "Bad Request",
    "message": "Invalid input data"
}
```

**401** - Unauthorized

**Example:**

```json
{
    "error": "Unauthorized",
    "message": "Authentication required"
}
```


---

### api_templates_show

**Path:** `/api/templates/{uuid}`  
**Methods:** `GET`  
**Description:** Returns comprehensive template information including design data, metadata,
and usage statistics. Automatically increments the view count for analytics.
Only returns active templates that are publicly available.

**Summary:** Get details of a specific template

#### Parameters

- **uuid** `string` (required) - The template UUID to retrieve

#### Responses

**200** - Success

**Schema:** [TemplateResponseDTO](#templateresponsedto)

**Example:**

```json
{
    "success": true,
    "message": "example_message",
    "template": [],
    "templates": [],
    "total": 123,
    "page": 123,
    "totalPages": 123,
    "timestamp": "example_value"
}
```

**400** - Bad Request

**Example:**

```json
{
    "error": "Bad Request",
    "message": "Invalid input data"
}
```

**401** - Unauthorized

**Example:**

```json
{
    "error": "Unauthorized",
    "message": "Authentication required"
}
```


---

### api_templates_create

**Path:** `/api/templates`  
**Methods:** `POST`  
**Description:** Creates a new template from user design with metadata and canvas configuration.
Templates can be made public for marketplace or kept private for personal use.
Requires authentication and validates all template data before creation.

**Summary:** Create a new template

🔒 **Security:** IsGranted

#### Request Body

**Content Type:** `application/json`

**Schema:** [CreateTemplateRequestDTO](#createtemplaterequestdto)

**Example:**

```json
{
    "name": "example_name",
    "description": "example_description",
    "category": "example_category",
    "tags": [],
    "width": 123,
    "height": 123,
    "canvasSettings": [],
    "layers": [],
    "thumbnailUrl": "example_thumbnailUrl",
    "previewUrl": "example_previewUrl",
    "isPremium": true,
    "isActive": true
}
```

#### Responses

**200** - Success

**Schema:** [TemplateResponseDTO](#templateresponsedto)

**Example:**

```json
{
    "success": true,
    "message": "example_message",
    "template": [],
    "templates": [],
    "total": 123,
    "page": 123,
    "totalPages": 123,
    "timestamp": "example_value"
}
```

**400** - Bad Request

**Example:**

```json
{
    "error": "Bad Request",
    "message": "Invalid input data"
}
```

**401** - Unauthorized

**Example:**

```json
{
    "error": "Unauthorized",
    "message": "Authentication required"
}
```


---

### api_templates_search

**Path:** `/api/templates/search`  
**Methods:** `GET`  
**Description:** Performs comprehensive template search with support for text queries,
category filtering, and tag-based search. Returns paginated results
sorted by relevance and usage popularity.

**Summary:** Search templates with advanced filtering

#### Request Body

**Content Type:** `application/json`

**Schema:** [SearchTemplateRequestDTO](#searchtemplaterequestdto)

**Example:**

```json
{
    "q": "example_q",
    "category": "example_category",
    "page": 123,
    "limit": 123
}
```

#### Responses

**200** - Success

**Schema:** [TemplateSearchResponseDTO](#templatesearchresponsedto)

**Example:**

```json
{
    "templates": [],
    "page": 123,
    "limit": 123,
    "total": 123,
    "totalPages": 123,
    "message": "example_message"
}
```

**400** - Bad Request

**Example:**

```json
{
    "error": "Bad Request",
    "message": "Invalid input data"
}
```

**401** - Unauthorized

**Example:**

```json
{
    "error": "Unauthorized",
    "message": "Authentication required"
}
```


---

### api_templates_use

**Path:** `/api/templates/{uuid}/use`  
**Methods:** `POST`  
**Description:** Creates a new design project based on the specified template.
Copies all template layers, settings, and properties to the new design.
Automatically increments the template usage count for analytics.

**Summary:** Use a template to create a new design

🔒 **Security:** IsGranted

#### Parameters

- **uuid** `string` (required) - The template UUID to use for design creation

#### Responses

**200** - Success

**Schema:** [DesignResponseDTO](#designresponsedto)

**Example:**

```json
{
    "success": true,
    "message": "example_message",
    "design": [],
    "designs": [],
    "total": 123,
    "page": 123,
    "totalPages": 123,
    "timestamp": "example_value"
}
```

**400** - Bad Request

**Example:**

```json
{
    "error": "Bad Request",
    "message": "Invalid input data"
}
```

**401** - Unauthorized

**Example:**

```json
{
    "error": "Unauthorized",
    "message": "Authentication required"
}
```


---

### api_templates_categories

**Path:** `/api/templates/categories`  
**Methods:** `GET`  
**Description:** Returns a list of all available template categories for filtering
and organization purposes. Categories help users find relevant templates
for their specific design needs and use cases.

**Summary:** Get available template categories

#### Responses

**200** - Success

**Schema:** [SuccessResponseDTO](#successresponsedto)

**Example:**

```json
{
    "message": "example_message",
    "success": true,
    "timestamp": "example_timestamp"
}
```

**400** - Bad Request

**Example:**

```json
{
    "error": "Bad Request",
    "message": "Invalid input data"
}
```

**401** - Unauthorized

**Example:**

```json
{
    "error": "Unauthorized",
    "message": "Authentication required"
}
```


---

## UserController

User endpoints

**Route Count:** 8

### api_user_profile

**Path:** `/api/user/profile`  
**Methods:** `GET`  
**Description:** Returns comprehensive profile data including personal information,
settings, and account details for the authenticated user.

**Summary:** Get current user's profile information

#### Responses

**200** - Success

**Example:**

```json
{
    "message": "Success"
}
```

**400** - Bad Request

**Example:**

```json
{
    "error": "Bad Request",
    "message": "Invalid input data"
}
```

**401** - Unauthorized

**Example:**

```json
{
    "error": "Unauthorized",
    "message": "Authentication required"
}
```


---

### api_user_update_profile

**Path:** `/api/user/profile`  
**Methods:** `PUT`  
**Description:** Updates user profile data including personal information, professional details,
and account preferences. Uses comprehensive validation and returns updated profile.

**Summary:** Update current user's profile information

#### Request Body

**Content Type:** `application/json`

**Schema:** [UpdateProfileRequestDTO](#updateprofilerequestdto)

**Example:**

```json
{
    "firstName": "example_firstName",
    "lastName": "example_lastName",
    "username": "example_username",
    "email": "example_email",
    "jobTitle": "example_jobTitle",
    "company": "example_company",
    "website": "example_website",
    "portfolio": "example_portfolio",
    "bio": "example_bio",
    "socialLinks": [],
    "timezone": "example_timezone",
    "language": "example_language"
}
```

#### Responses

**200** - Success

**Example:**

```json
{
    "message": "Success"
}
```

**400** - Bad Request

**Example:**

```json
{
    "error": "Bad Request",
    "message": "Invalid input data"
}
```

**401** - Unauthorized

**Example:**

```json
{
    "error": "Unauthorized",
    "message": "Authentication required"
}
```


---

### api_user_upload_avatar

**Path:** `/api/user/avatar`  
**Methods:** `POST`  
**Description:** Handles avatar file upload, validation, and updates user profile.
Automatically removes old avatar file and returns new avatar URL.

**Summary:** Upload and update user avatar image

#### Request Body

Handles validation and encapsulation of avatar file upload data including
file type validation, size constraints, and security checks.

**Content Type:** `application/json`

**Schema:** [UploadAvatarRequestDTO](#uploadavatarrequestdto)

**Example:**

```json
{
    "avatar": "example_value"
}
```

#### Responses

**200** - Success

**Example:**

```json
{
    "message": "Success"
}
```

**400** - Bad Request

**Example:**

```json
{
    "error": "Bad Request",
    "message": "Invalid input data"
}
```

**401** - Unauthorized

**Example:**

```json
{
    "error": "Unauthorized",
    "message": "Authentication required"
}
```


---

### api_user_change_password

**Path:** `/api/user/password`  
**Methods:** `PUT`  
**Description:** Updates user password after validating current password and ensuring
new password meets security requirements including confirmation match.

**Summary:** Change user password

#### Request Body

Handles secure password updates for authenticated users.
Validates the current password before allowing the change
and ensures the new password meets security requirements
with confirmation validation.

**Content Type:** `application/json`

**Schema:** [ChangePasswordRequestDTO](#changepasswordrequestdto)

**Example:**

```json
{
    "currentPassword": "example_currentPassword",
    "newPassword": "example_newPassword",
    "confirmPassword": "example_confirmPassword"
}
```

#### Responses

**200** - Success

**Example:**

```json
{
    "message": "Success"
}
```

**400** - Bad Request

**Example:**

```json
{
    "error": "Bad Request",
    "message": "Invalid input data"
}
```

**401** - Unauthorized

**Example:**

```json
{
    "error": "Unauthorized",
    "message": "Authentication required"
}
```


---

### api_user_download_data

**Path:** `/api/user/settings/privacy/download`  
**Methods:** `POST`  
**Description:** Initiates a background process to prepare comprehensive data export
for the user, including all personal data and content.

**Summary:** Request user data download for GDPR compliance

#### Responses

**200** - Success

**Example:**

```json
{
    "message": "Success"
}
```

**400** - Bad Request

**Example:**

```json
{
    "error": "Bad Request",
    "message": "Invalid input data"
}
```

**401** - Unauthorized

**Example:**

```json
{
    "error": "Unauthorized",
    "message": "Authentication required"
}
```


---

### api_user_export_data

**Path:** `/api/user/settings/privacy/export`  
**Methods:** `POST`  
**Description:** Generates and returns comprehensive user data export including
all user content, settings, and account information.

**Summary:** Export user data in portable format

#### Responses

**200** - Success

**Example:**

```json
{
    "message": "Success"
}
```

**400** - Bad Request

**Example:**

```json
{
    "error": "Bad Request",
    "message": "Invalid input data"
}
```

**401** - Unauthorized

**Example:**

```json
{
    "error": "Unauthorized",
    "message": "Authentication required"
}
```


---

### api_user_delete_account

**Path:** `/api/user/settings/privacy/delete`  
**Methods:** `DELETE`  
**Description:** Initiates account deletion process which removes all user data,
content, and associated resources permanently.

**Summary:** Delete user account and all associated data

#### Responses

**200** - Success

**Example:**

```json
{
    "message": "Success"
}
```

**400** - Bad Request

**Example:**

```json
{
    "error": "Bad Request",
    "message": "Invalid input data"
}
```

**401** - Unauthorized

**Example:**

```json
{
    "error": "Unauthorized",
    "message": "Authentication required"
}
```


---

### api_user_subscription

**Path:** `/api/user/subscription`  
**Methods:** `GET`  
**Description:** Returns current subscription details including plan type, billing status,
usage limits, and subscription features for the authenticated user.

**Summary:** Get user subscription information

#### Responses

**200** - Success

**Example:**

```json
{
    "message": "Success"
}
```

**400** - Bad Request

**Example:**

```json
{
    "error": "Bad Request",
    "message": "Invalid input data"
}
```

**401** - Unauthorized

**Example:**

```json
{
    "error": "Unauthorized",
    "message": "Authentication required"
}
```


---

## Schemas

### RegisterRequestDTO

Handles new user account creation with validation for all
required fields. Used by the registration system to collect
and validate user information before creating new accounts
in the platform.

#### Properties

| Property | Type | Required | Description |
|----------|------|----------|-------------|
| email | `string` | ✅ | User's email address |
| password | `string` | ✅ | User's password |
| firstName | `string` | ✅ | User's first name |
| lastName | `string` | ✅ | User's last name |
| username | `string` | ❌ | User's optional username |

#### Example

```json
{
    "email": "example_email",
    "password": "example_password",
    "firstName": "example_firstName",
    "lastName": "example_lastName",
    "username": "example_username"
}
```

---

### AuthResponseDTO

Returned by authentication endpoints (login, register, refresh)
to provide the client with the access token and user information
needed for authenticated API requests and UI personalization.

#### Properties

| Property | Type | Required | Description |
|----------|------|----------|-------------|
| message | `string` | ✅ |  |
| token | `string` | ✅ |  |
| user | `string` | ✅ |  |
| success | `boolean` | ❌ |  |
| timestamp | `string` | ❌ |  |

#### Example

```json
{
    "message": "example_message",
    "token": "example_token",
    "user": "example_user",
    "success": true,
    "timestamp": "example_timestamp"
}
```

---

### LoginRequestDTO

Handles user login credentials for JWT token-based authentication.
Used by the authentication system to validate user credentials
and generate access tokens for API authorization.

#### Properties

| Property | Type | Required | Description |
|----------|------|----------|-------------|
| email | `string` | ✅ |  |
| password | `string` | ✅ |  |

#### Example

```json
{
    "email": "example_email",
    "password": "example_password"
}
```

---

### UserProfileResponseDTO

#### Properties

| Property | Type | Required | Description |
|----------|------|----------|-------------|
| user | `string` | ✅ |  |
| message | `string` | ❌ |  |
| success | `boolean` | ❌ |  |
| timestamp | `string` | ❌ |  |

#### Example

```json
{
    "user": "example_user",
    "message": "example_message",
    "success": true,
    "timestamp": "example_timestamp"
}
```

---

### UpdateProfileRequestDTO

#### Properties

| Property | Type | Required | Description |
|----------|------|----------|-------------|
| firstName | `string` | ❌ |  |
| lastName | `string` | ❌ |  |
| username | `string` | ❌ |  |
| email | `string` | ❌ |  |
| jobTitle | `string` | ❌ |  |
| company | `string` | ❌ |  |
| website | `string` | ❌ |  |
| portfolio | `string` | ❌ |  |
| bio | `string` | ❌ |  |
| socialLinks | `array` | ❌ |  |
| timezone | `string` | ❌ |  |
| language | `string` | ❌ |  |

#### Example

```json
{
    "firstName": "example_firstName",
    "lastName": "example_lastName",
    "username": "example_username",
    "email": "example_email",
    "jobTitle": "example_jobTitle",
    "company": "example_company",
    "website": "example_website",
    "portfolio": "example_portfolio",
    "bio": "example_bio",
    "socialLinks": [],
    "timezone": "example_timezone",
    "language": "example_language"
}
```

---

### ChangePasswordRequestDTO

Handles secure password updates for authenticated users.
Validates the current password before allowing the change
and ensures the new password meets security requirements
with confirmation validation.

#### Properties

| Property | Type | Required | Description |
|----------|------|----------|-------------|
| currentPassword | `string` | ✅ |  |
| newPassword | `string` | ✅ |  |
| confirmPassword | `string` | ✅ |  |

#### Example

```json
{
    "currentPassword": "example_currentPassword",
    "newPassword": "example_newPassword",
    "confirmPassword": "example_confirmPassword"
}
```

---

### SuccessResponseDTO

#### Properties

| Property | Type | Required | Description |
|----------|------|----------|-------------|
| message | `string` | ✅ |  |
| success | `boolean` | ❌ |  |
| timestamp | `string` | ❌ |  |

#### Example

```json
{
    "message": "example_message",
    "success": true,
    "timestamp": "example_timestamp"
}
```

---

### PaginatedResponseDTO

#### Properties

| Property | Type | Required | Description |
|----------|------|----------|-------------|
| data | `array` | ✅ |  |
| page | `integer` | ✅ |  |
| limit | `integer` | ✅ |  |
| total | `integer` | ✅ |  |
| totalPages | `integer` | ✅ |  |
| message | `string` | ❌ |  |

#### Example

```json
{
    "data": [],
    "page": 123,
    "limit": 123,
    "total": 123,
    "totalPages": 123,
    "message": "example_message"
}
```

---

### CreateDesignRequestDTO

This DTO handles the creation of new designs with specified canvas
dimensions, initial design settings, and organizational metadata.

#### Properties

| Property | Type | Required | Description |
|----------|------|----------|-------------|
| name | `string` | ✅ |  |
| description | `string` | ❌ |  |
| data | `string` | ❌ |  |
| projectId | `integer` | ❌ |  |
| width | `integer` | ❌ |  |
| height | `integer` | ❌ |  |
| isPublic | `boolean` | ❌ |  |

#### Example

```json
{
    "name": "example_name",
    "description": "example_description",
    "data": "example_data",
    "projectId": 123,
    "width": 123,
    "height": 123,
    "isPublic": true
}
```

---

### DesignResponseDTO

#### Properties

| Property | Type | Required | Description |
|----------|------|----------|-------------|
| success | `boolean` | ✅ |  |
| message | `string` | ✅ |  |
| design | `array` | ❌ |  |
| designs | `array` | ❌ |  |
| total | `integer` | ❌ |  |
| page | `integer` | ❌ |  |
| totalPages | `integer` | ❌ |  |
| timestamp | `string` | ❌ |  |

#### Example

```json
{
    "success": true,
    "message": "example_message",
    "design": [],
    "designs": [],
    "total": 123,
    "page": 123,
    "totalPages": 123,
    "timestamp": "example_timestamp"
}
```

---

### UpdateDesignRequestDTO

This DTO handles partial updates to designs, allowing clients to
update only the fields they want to change. All fields are optional
and null values indicate no change should be made.

#### Properties

| Property | Type | Required | Description |
|----------|------|----------|-------------|
| name | `string` | ❌ |  |
| description | `string` | ❌ |  |
| data | `string` | ❌ |  |
| projectId | `integer` | ❌ |  |
| width | `integer` | ❌ |  |
| height | `integer` | ❌ |  |
| isPublic | `boolean` | ❌ |  |

#### Example

```json
{
    "name": "example_name",
    "description": "example_description",
    "data": "example_data",
    "projectId": 123,
    "width": 123,
    "height": 123,
    "isPublic": true
}
```

---

### DuplicateDesignRequestDTO

Handles the duplication of complete designs including all layers,
settings, and metadata. Used by the design management system to
create copies of existing designs with optional customization
of the duplicate's name and target project.

#### Properties

| Property | Type | Required | Description |
|----------|------|----------|-------------|
| name | `string` | ❌ |  |
| projectId | `integer` | ❌ |  |

#### Example

```json
{
    "name": "example_name",
    "projectId": 123
}
```

---

### UpdateDesignThumbnailRequestDTO

Handles updating the thumbnail image for an existing design.
Used when users want to change the preview image that represents
their design in galleries, project lists, and search results.

#### Properties

| Property | Type | Required | Description |
|----------|------|----------|-------------|
| thumbnail | `string` | ✅ |  |

#### Example

```json
{
    "thumbnail": "example_thumbnail"
}
```

---

### ExportJobResponseDTO

#### Properties

| Property | Type | Required | Description |
|----------|------|----------|-------------|
| success | `boolean` | ✅ |  |
| message | `string` | ✅ |  |
| job | `array` | ❌ |  |
| jobs | `array` | ❌ |  |
| total | `integer` | ❌ |  |
| page | `integer` | ❌ |  |
| totalPages | `integer` | ❌ |  |
| timestamp | `string` | ❌ |  |

#### Example

```json
{
    "success": true,
    "message": "example_message",
    "job": [],
    "jobs": [],
    "total": 123,
    "page": 123,
    "totalPages": 123,
    "timestamp": "example_timestamp"
}
```

---

### CreateExportJobRequestDTO

Handles the submission of design export jobs to the background
processing system. Supports multiple output formats and quality
settings for rendering designs to various file types including
static images, PDFs, and animated formats.

#### Properties

| Property | Type | Required | Description |
|----------|------|----------|-------------|
| designId | `integer` | ✅ |  |
| format | `string` | ❌ |  |
| quality | `string` | ❌ |  |
| width | `integer` | ❌ |  |
| height | `integer` | ❌ |  |
| scale | `number` | ❌ |  |
| transparent | `boolean` | ❌ |  |
| backgroundColor | `string` | ❌ |  |
| animationSettings | `array` | ❌ |  |

#### Example

```json
{
    "designId": 123,
    "format": "example_format",
    "quality": "example_quality",
    "width": 123,
    "height": 123,
    "scale": 123.45,
    "transparent": true,
    "backgroundColor": "example_backgroundColor",
    "animationSettings": []
}
```

---

### ErrorResponseDTO

#### Properties

| Property | Type | Required | Description |
|----------|------|----------|-------------|
| message | `string` | ✅ |  |
| details | `array` | ❌ |  |
| code | `string` | ❌ |  |
| timestamp | `string` | ❌ |  |

#### Example

```json
{
    "message": "example_message",
    "details": [],
    "code": "example_code",
    "timestamp": "example_timestamp"
}
```

---

### CreateLayerRequestDTO

Handles the creation of new design layers with all necessary properties
for proper initialization in the canvas editor. Supports all layer types
including text, image, shape, group, video, and audio layers.

#### Properties

| Property | Type | Required | Description |
|----------|------|----------|-------------|
| designId | `string` | ✅ |  |
| type | `string` | ✅ |  |
| name | `string` | ✅ |  |
| properties | `string` | ✅ |  |
| transform | `string` | ✅ |  |
| zIndex | `integer` | ❌ |  |
| visible | `boolean` | ❌ |  |
| locked | `boolean` | ❌ |  |
| parentLayerId | `string` | ❌ |  |

#### Example

```json
{
    "designId": "example_designId",
    "type": "example_type",
    "name": "example_name",
    "properties": "example_properties",
    "transform": "example_transform",
    "zIndex": 123,
    "visible": true,
    "locked": true,
    "parentLayerId": "example_parentLayerId"
}
```

---

### LayerResponseDTO

#### Properties

| Property | Type | Required | Description |
|----------|------|----------|-------------|
| success | `boolean` | ✅ |  |
| message | `string` | ✅ |  |
| layer | `array` | ❌ |  |
| layers | `array` | ❌ |  |
| timestamp | `string` | ❌ |  |

#### Example

```json
{
    "success": true,
    "message": "example_message",
    "layer": [],
    "layers": [],
    "timestamp": "example_timestamp"
}
```

---

### UpdateLayerRequestDTO

Handles modification of existing design layers including visual properties,
positioning, visibility, and hierarchy changes. All fields are optional
to support partial updates of layer attributes.

#### Properties

| Property | Type | Required | Description |
|----------|------|----------|-------------|
| name | `string` | ❌ |  |
| properties | `string` | ❌ |  |
| transform | `string` | ❌ |  |
| zIndex | `integer` | ❌ |  |
| visible | `boolean` | ❌ |  |
| locked | `boolean` | ❌ |  |
| parentLayerId | `string` | ❌ |  |

#### Example

```json
{
    "name": "example_name",
    "properties": "example_properties",
    "transform": "example_transform",
    "zIndex": 123,
    "visible": true,
    "locked": true,
    "parentLayerId": "example_parentLayerId"
}
```

---

### DuplicateLayerRequestDTO

Handles the duplication of design layers with optional customization
of the duplicate layer name and target design. Used by the layer
management system to create copies of existing layers within the
same design or across different designs.

#### Properties

| Property | Type | Required | Description |
|----------|------|----------|-------------|
| name | `string` | ❌ |  |
| targetDesignId | `string` | ❌ |  |

#### Example

```json
{
    "name": "example_name",
    "targetDesignId": "example_targetDesignId"
}
```

---

### MoveLayerRequestDTO

Handles repositioning of layers within the design canvas z-order.
Supports both relative movements (up/down/top/bottom) and absolute
positioning via z-index targeting. Used in the layer management
system to control layer stacking order and visual hierarchy.

#### Properties

| Property | Type | Required | Description |
|----------|------|----------|-------------|
| direction | `string` | ❌ |  |
| targetZIndex | `integer` | ❌ |  |

#### Example

```json
{
    "direction": "example_direction",
    "targetZIndex": 123
}
```

---

### BulkUpdateLayersRequestDTO

This DTO handles batch operations for updating layer properties, allowing
efficient modification of multiple layers in a single API call. Each layer
update includes the layer ID and the specific changes to apply.

#### Properties

| Property | Type | Required | Description |
|----------|------|----------|-------------|
| layers | `array` | ✅ |  |

#### Example

```json
{
    "layers": []
}
```

---

### SearchMediaRequestDTO

Handles search operations for media files with advanced filtering
capabilities including type, source, pagination, and tag-based
filtering. Used by the media library to provide rich search
functionality for users to find specific media files.

#### Properties

| Property | Type | Required | Description |
|----------|------|----------|-------------|
| page | `integer` | ❌ |  |
| limit | `integer` | ❌ |  |
| type | `string` | ❌ |  |
| source | `string` | ❌ |  |
| search | `string` | ❌ |  |
| tags | `string` | ❌ |  |

#### Example

```json
{
    "page": 123,
    "limit": 123,
    "type": "example_type",
    "source": "example_source",
    "search": "example_search",
    "tags": "example_tags"
}
```

---

### MediaResponseDTO

#### Properties

| Property | Type | Required | Description |
|----------|------|----------|-------------|
| success | `boolean` | ✅ |  |
| message | `string` | ✅ |  |
| media | `array` | ❌ |  |
| mediaList | `array` | ❌ |  |
| total | `integer` | ❌ |  |
| page | `integer` | ❌ |  |
| totalPages | `integer` | ❌ |  |
| timestamp | `string` | ❌ |  |

#### Example

```json
{
    "success": true,
    "message": "example_message",
    "media": [],
    "mediaList": [],
    "total": 123,
    "page": 123,
    "totalPages": 123,
    "timestamp": "example_timestamp"
}
```

---

### CreateMediaRequestDTO

This DTO handles the creation of media assets including images, videos,
and audio files from various sources (uploads, stock photo APIs, etc.).
Includes comprehensive metadata and organizational features.

#### Properties

| Property | Type | Required | Description |
|----------|------|----------|-------------|
| name | `string` | ✅ |  |
| type | `string` | ✅ |  |
| mimeType | `string` | ✅ |  |
| size | `integer` | ✅ |  |
| url | `string` | ✅ |  |
| thumbnailUrl | `string` | ❌ |  |
| width | `integer` | ❌ |  |
| height | `integer` | ❌ |  |
| duration | `number` | ❌ |  |
| source | `string` | ❌ |  |
| sourceId | `string` | ❌ |  |
| metadata | `string` | ❌ |  |
| tags | `array` | ❌ |  |
| attribution | `string` | ❌ |  |
| license | `string` | ❌ |  |
| isPremium | `boolean` | ❌ |  |
| isActive | `boolean` | ❌ |  |

#### Example

```json
{
    "name": "example_name",
    "type": "example_type",
    "mimeType": "example_mimeType",
    "size": 123,
    "url": "example_url",
    "thumbnailUrl": "example_thumbnailUrl",
    "width": 123,
    "height": 123,
    "duration": 123.45,
    "source": "example_source",
    "sourceId": "example_sourceId",
    "metadata": "example_metadata",
    "tags": [],
    "attribution": "example_attribution",
    "license": "example_license",
    "isPremium": true,
    "isActive": true
}
```

---

### UpdateMediaRequestDTO

This DTO handles partial updates to media assets, allowing clients to
update only the fields they want to change. All fields are optional
and null values indicate no change should be made.

#### Properties

| Property | Type | Required | Description |
|----------|------|----------|-------------|
| name | `string` | ❌ |  |
| description | `string` | ❌ |  |
| tags | `array` | ❌ |  |
| metadata | `string` | ❌ |  |
| isPremium | `boolean` | ❌ |  |
| isActive | `boolean` | ❌ |  |
| isPublic | `boolean` | ❌ |  |

#### Example

```json
{
    "name": "example_name",
    "description": "example_description",
    "tags": [],
    "metadata": "example_metadata",
    "isPremium": true,
    "isActive": true,
    "isPublic": true
}
```

---

### DuplicateMediaRequestDTO

Handles the duplication of media files including images, videos,
and other assets. Creates a personal copy of accessible media
files for the authenticated user with optional name customization.
Used by the media library for creating user-owned copies of media.

#### Properties

| Property | Type | Required | Description |
|----------|------|----------|-------------|
| name | `string` | ❌ |  |

#### Example

```json
{
    "name": "example_name"
}
```

---

### StockSearchRequestDTO

Handles search operations for stock media from external providers
like Unsplash, Pexels, and Pixabay. Provides structured search
parameters for integrating with third-party stock media APIs
to expand the available media library for users.

#### Properties

| Property | Type | Required | Description |
|----------|------|----------|-------------|
| query | `string` | ✅ |  |
| page | `integer` | ❌ |  |
| limit | `integer` | ❌ |  |
| type | `string` | ❌ |  |
| source | `string` | ❌ |  |

#### Example

```json
{
    "query": "example_query",
    "page": 123,
    "limit": 123,
    "type": "example_type",
    "source": "example_source"
}
```

---

### BulkDeleteMediaRequestDTO

Handles bulk deletion of multiple media files by their UUIDs.
Used by the media management system to allow users to delete
multiple media files in a single operation with validation
and permission checks.

#### Properties

| Property | Type | Required | Description |
|----------|------|----------|-------------|
| uuids | `array` | ✅ |  |

#### Example

```json
{
    "uuids": []
}
```

---

### CreatePluginRequestDTO

Handles the submission of new plugins to the platform's plugin
system. Contains all necessary metadata and configuration for
plugin registration, validation, and eventual approval process.
Used in the plugin management system to onboard new extensions.

#### Properties

| Property | Type | Required | Description |
|----------|------|----------|-------------|
| name | `string` | ✅ |  |
| description | `string` | ✅ |  |
| categories | `array` | ✅ |  |
| version | `string` | ✅ |  |
| permissions | `array` | ✅ |  |
| manifest | `array` | ✅ |  |

#### Example

```json
{
    "name": "example_name",
    "description": "example_description",
    "categories": [],
    "version": "example_version",
    "permissions": [],
    "manifest": []
}
```

---

### UpdatePluginRequestDTO

Handles partial updates to existing plugins in the platform's
plugin system. All fields are optional and null values indicate
no change should be made. Used for both plugin developer updates
and administrative status changes.

#### Properties

| Property | Type | Required | Description |
|----------|------|----------|-------------|
| name | `string` | ❌ |  |
| description | `string` | ❌ |  |
| categories | `array` | ❌ |  |
| version | `string` | ❌ |  |
| permissions | `array` | ❌ |  |
| manifest | `array` | ❌ |  |
| status | `string` | ❌ |  |

#### Example

```json
{
    "name": "example_name",
    "description": "example_description",
    "categories": [],
    "version": "example_version",
    "permissions": [],
    "manifest": [],
    "status": "example_status"
}
```

---

### UploadPluginFileRequestDTO

Handles validation and encapsulation of plugin file upload data including
file type validation, size constraints, and security checks for plugin files.

#### Properties

| Property | Type | Required | Description |
|----------|------|----------|-------------|
| file | `string` | ❌ |  |

#### Example

```json
{
    "file": "example_file"
}
```

---

### RejectPluginRequestDTO

#### Properties

| Property | Type | Required | Description |
|----------|------|----------|-------------|
| reason | `string` | ✅ |  |

#### Example

```json
{
    "reason": "example_reason"
}
```

---

### CreateProjectRequestDTO

This DTO handles the creation of new projects with all necessary
configuration options, including canvas settings, metadata, and
organizational tags.

#### Properties

| Property | Type | Required | Description |
|----------|------|----------|-------------|
| name | `string` | ✅ |  |
| description | `string` | ✅ |  |
| isPublic | `boolean` | ✅ |  |
| settings | `string` | ✅ |  |
| tags | `array` | ❌ |  |
| thumbnail | `string` | ❌ |  |

#### Example

```json
{
    "name": "example_name",
    "description": "example_description",
    "isPublic": true,
    "settings": "example_settings",
    "tags": [],
    "thumbnail": "example_thumbnail"
}
```

---

### ProjectResponseDTO

#### Properties

| Property | Type | Required | Description |
|----------|------|----------|-------------|
| success | `boolean` | ✅ |  |
| message | `string` | ✅ |  |
| project | `array` | ❌ |  |
| projects | `array` | ❌ |  |
| total | `integer` | ❌ |  |
| page | `integer` | ❌ |  |
| totalPages | `integer` | ❌ |  |
| timestamp | `string` | ❌ |  |

#### Example

```json
{
    "success": true,
    "message": "example_message",
    "project": [],
    "projects": [],
    "total": 123,
    "page": 123,
    "totalPages": 123,
    "timestamp": "example_timestamp"
}
```

---

### UpdateProjectRequestDTO

This DTO handles partial updates to projects, allowing clients to
update only the fields they want to change. All fields are optional
and null values indicate no change should be made.

#### Properties

| Property | Type | Required | Description |
|----------|------|----------|-------------|
| name | `string` | ❌ |  |
| description | `string` | ❌ |  |
| isPublic | `boolean` | ❌ |  |
| settings | `string` | ❌ |  |
| tags | `array` | ❌ |  |
| thumbnail | `string` | ❌ |  |

#### Example

```json
{
    "name": "example_name",
    "description": "example_description",
    "isPublic": true,
    "settings": "example_settings",
    "tags": [],
    "thumbnail": "example_thumbnail"
}
```

---

### DuplicateProjectRequestDTO

Handles the creation of a complete copy of an existing project
including all associated designs, layers, and metadata. Used
in the project management system to allow users to quickly
create new projects based on existing templates.

#### Properties

| Property | Type | Required | Description |
|----------|------|----------|-------------|
| name | `string` | ❌ |  |

#### Example

```json
{
    "name": "example_name"
}
```

---

### SearchProjectsRequestDTO

Handles search and filtering operations for user projects
with support for text-based search, tag filtering, and
pagination. Used by the project management system to allow
users to find and organize their design projects efficiently.

#### Properties

| Property | Type | Required | Description |
|----------|------|----------|-------------|
| page | `integer` | ❌ |  |
| limit | `integer` | ❌ |  |
| search | `string` | ❌ |  |
| tags | `string` | ❌ |  |

#### Example

```json
{
    "page": 123,
    "limit": 123,
    "search": "example_search",
    "tags": "example_tags"
}
```

---

### TemplateResponseDTO

#### Properties

| Property | Type | Required | Description |
|----------|------|----------|-------------|
| success | `boolean` | ✅ |  |
| message | `string` | ✅ |  |
| template | `array` | ❌ |  |
| templates | `array` | ❌ |  |
| total | `integer` | ❌ |  |
| page | `integer` | ❌ |  |
| totalPages | `integer` | ❌ |  |
| timestamp | `string` | ❌ |  |

#### Example

```json
{
    "success": true,
    "message": "example_message",
    "template": [],
    "templates": [],
    "total": 123,
    "page": 123,
    "totalPages": 123,
    "timestamp": "example_timestamp"
}
```

---

### CreateTemplateRequestDTO

#### Properties

| Property | Type | Required | Description |
|----------|------|----------|-------------|
| name | `string` | ✅ |  |
| description | `string` | ✅ |  |
| category | `string` | ✅ |  |
| tags | `array` | ✅ |  |
| width | `integer` | ✅ |  |
| height | `integer` | ✅ |  |
| canvasSettings | `array` | ❌ |  |
| layers | `array` | ❌ |  |
| thumbnailUrl | `string` | ❌ |  |
| previewUrl | `string` | ❌ |  |
| isPremium | `boolean` | ❌ |  |
| isActive | `boolean` | ❌ |  |

#### Example

```json
{
    "name": "example_name",
    "description": "example_description",
    "category": "example_category",
    "tags": [],
    "width": 123,
    "height": 123,
    "canvasSettings": [],
    "layers": [],
    "thumbnailUrl": "example_thumbnailUrl",
    "previewUrl": "example_previewUrl",
    "isPremium": true,
    "isActive": true
}
```

---

### SearchTemplateRequestDTO

#### Properties

| Property | Type | Required | Description |
|----------|------|----------|-------------|
| q | `string` | ❌ |  |
| category | `string` | ❌ |  |
| page | `integer` | ❌ |  |
| limit | `integer` | ❌ |  |

#### Example

```json
{
    "q": "example_q",
    "category": "example_category",
    "page": 123,
    "limit": 123
}
```

---

### TemplateSearchResponseDTO

Provides specific typing for template search operations to ensure the frontend API
knows exactly what structure to expect from template searches.

#### Properties

| Property | Type | Required | Description |
|----------|------|----------|-------------|
| templates | `array` | ✅ | Array of template data with specific structure |
| page | `integer` | ✅ | Current page number |
| limit | `integer` | ✅ | Items per page |
| total | `integer` | ✅ | Total number of templates found |
| totalPages | `integer` | ✅ | Total number of pages |
| message | `string` | ❌ | Response message |

#### Example

```json
{
    "templates": [],
    "page": 123,
    "limit": 123,
    "total": 123,
    "totalPages": 123,
    "message": "example_message"
}
```

---

### UploadAvatarRequestDTO

Handles validation and encapsulation of avatar file upload data including
file type validation, size constraints, and security checks.

#### Properties

| Property | Type | Required | Description |
|----------|------|----------|-------------|
| avatar | `string` | ❌ |  |

#### Example

```json
{
    "avatar": "example_avatar"
}
```

---


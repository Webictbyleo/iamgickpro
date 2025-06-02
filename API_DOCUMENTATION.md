# Design Platform API

Comprehensive API for the modern web-based design platform

**Version:** 1.0.0

**Generated on:** 2025-06-02 16:18:08
**Generator:** Enhanced API Documentation Generator v2.0
**Symfony Version:** 7.0.10
**PHP Version:** 8.4.7

**Contact:** API Support ([api-support@example.com](mailto:api-support@example.com))

**License:** [MIT](https://opensource.org/licenses/MIT)

## Servers

**Current Server (based on APP_ENV):** `http://localhost:8000` - Development server

**All Available Servers:**

- **Production server:** `https://api.example.com/v1`
- **Staging server:** `https://staging-api.example.com/v1`
- **Development server:** `http://localhost:8000` *(current)*
- **Testing server:** `http://localhost:8080`

## Authentication

### BearerAuth

- **Type:** Http
- **Scheme:** Bearer
- **Bearer Format:** JWT

## Table of Contents

- [AuthController](#authcontroller) *(6 routes)*
- [DesignController](#designcontroller) *(8 routes)*
- [ExportJobController](#exportjobcontroller) *(10 routes)*
- [LayerController](#layercontroller) *(7 routes)*
- [MediaController](#mediacontroller) *(9 routes)*
- [PluginController](#plugincontroller) *(12 routes)*
- [ProjectController](#projectcontroller) *(8 routes)*
- [TemplateController](#templatecontroller) *(6 routes)*
- [UserController](#usercontroller) *(8 routes)*

---

## AuthController

*6 routes*

Authentication Controller

Handles user authentication, registration, profile management, and password operations.
All endpoints return JSON responses with consistent error handling.

<!-- Route 1 -->
### PUT /api/auth/change-password

**Security:** IsGranted

Change user password

Updates the authenticated user's password after validating the current password.
Enforces password strength requirements.

#### Request Body

**ChangePasswordRequestDTO**

Data Transfer Object for password change requests



```typescript
interface ChangePasswordRequestDTO {
  currentPassword: string;

  newPassword: string;

  confirmPassword: string;

}
```

**Example Request:**

```javascript
// Example API Request for PUT /api/auth/change-password
async function examplePutRequest() {
  const url = 'https://example.com/api/auth/change-password';
  const requestData = {
        "currentPassword": "secure_password123",
        "newPassword": "secure_password123",
        "confirmPassword": "secure_password123"
    };

  try {
    const response = await fetch(url, {
      method: 'PUT',
      headers: {
        'Content-Type': 'application/json',
        'Authorization': 'Bearer YOUR_JWT_TOKEN', // This endpoint requires authentication
      },
      body: JSON.stringify(requestData)
    });

    if (!response.ok) {
      throw new Error('API error: ' + response.status);
    }

    const data = await response.json();
    console.log('Success:', data);
    return data;
  } catch (error) {
    console.error('Error:', error);
    throw error;
  }
}
```

#### Response

**Response Schema:**

```json
{
    "message": "example_string",
    "success": true,
    "timestamp": "example_string"
}
```

---

<!-- Route 2 -->
### POST /api/auth/login

Authenticate user and return JWT token

Validates user credentials and returns a JWT token with user information.
Updates the user's last login timestamp.

#### Request Body

**LoginRequestDTO**

Data Transfer Object for user authentication login requests.

Handles user login credentials for JWT token-based authentication.
Used by the authentication system to validate user credentials
and generate access tokens for API authorization.

```typescript
interface LoginRequestDTO {
  /**
   * User's email address for authentication. Must be a valid email format and correspond to a registe...
   */
  email: string;

  /**
   * User's password for authentication. Plain text password that will be verified against the hashed ...
   */
  password: string;

}
```

**Example Request:**

```javascript
// Example API Request for POST /api/auth/login
async function examplePostRequest() {
  const url = 'https://example.com/api/auth/login';
  const requestData = {
        "email": "user@example.com",
        "password": "secure_password123"
    };

  try {
    const response = await fetch(url, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify(requestData)
    });

    if (!response.ok) {
      throw new Error('API error: ' + response.status);
    }

    const data = await response.json();
    console.log('Success:', data);
    return data;
  } catch (error) {
    console.error('Error:', error);
    throw error;
  }
}
```

#### Response

**Response Schema:**

```json
{
    "token": "example_token_12345",
    "user": {
        "id": "example_id_123",
        "email": "user@example.com",
        "firstName": "Example Name",
        "lastName": "Example Name",
        "username": "Example Name",
        "roles": [
            "example_item"
        ],
        "avatar": null,
        "plan": "example_string",
        "emailVerified": null,
        "isActive": null,
        "createdAt": null,
        "lastLoginAt": null,
        "updatedAt": null,
        "settings": null,
        "stats": null
    },
    "message": "example_string",
    "success": true,
    "timestamp": "example_string"
}
```

---

<!-- Route 3 -->
### POST /api/auth/logout

**Security:** IsGranted

Logout user

Since JWT tokens are stateless, logout is primarily handled client-side by removing the token.
This endpoint provides a standardized logout response and could be extended with token blacklisting.

#### Response

**Response Schema:**

```json
{
    "message": "example_string",
    "success": true,
    "timestamp": "example_string"
}
```

---

<!-- Route 4 -->
### GET /api/auth/me

**Security:** IsGranted

Get current authenticated user profile

Returns detailed information about the currently authenticated user,
including profile data, statistics, and account status.

#### Response

**Response Schema:**

```json
{
    "user": {
        "id": "example_id_123",
        "email": "user@example.com",
        "firstName": "Example Name",
        "lastName": "Example Name",
        "username": "Example Name",
        "roles": [
            "example_item"
        ],
        "avatar": null,
        "plan": "example_string",
        "emailVerified": null,
        "isActive": null,
        "createdAt": null,
        "lastLoginAt": null,
        "updatedAt": null,
        "settings": null,
        "stats": null
    },
    "message": "example_string",
    "success": true,
    "timestamp": "example_string"
}
```

---

<!-- Route 5 -->
### PUT /api/auth/profile

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

```typescript
interface UpdateProfileRequestDTO {
  /**
   * User's first name for display and identification purposes Must be between 1-255 characters if pro...
   * @validation Length=min: 1  max: 255  minMessage: 'First name cannot be empty'  maxMessage: 'First name cannot be longer than {{ limit }} characters'
   */
  firstName?: string;

  /**
   * User's last name for display and identification purposes Must be between 1-255 characters if prov...
   * @validation Length=min: 1  max: 255  minMessage: 'Last name cannot be empty'  maxMessage: 'Last name cannot be longer than {{ limit }} characters'
   */
  lastName?: string;

  /**
   * Unique username for the user account Must be 3-100 characters, containing only letters, numbers, ...
   * @validation Length=min: 3  max: 100  minMessage: 'Username must be at least {{ limit }} characters long'  maxMessage: 'Username cannot be longer than {{ limit }} characters'
   * @validation Regex=pattern: '/^[a-zA-Z0-9_]+$/'  message: 'Username can only contain letters  numbers  and underscores'
   */
  username?: string;

  /**
   * URL to the user's profile avatar image Must be a valid URL pointing to an image file Used for pro...
   * @validation Url=message: 'Avatar must be a valid URL'
   * @validation Length=max: 500  maxMessage: 'Avatar URL cannot exceed 500 characters'
   */
  avatar?: string;

  /**
   * User's application settings and preferences Controls theme, language, notifications, auto-save, a...
   */
  settings?: {
    theme?: string;
    language?: string;
    timezone?: string;
    emailNotifications?: boolean;
    pushNotifications?: boolean;
    autoSave?: boolean;
    autoSaveInterval?: number;
    gridSnap?: boolean;
    gridSize?: number;
    canvasQuality?: number;
  };

}
```

**Example Request:**

```javascript
// Example API Request for PUT /api/auth/profile
async function examplePutRequest() {
  const url = 'https://example.com/api/auth/profile';
  const requestData = {
        "firstName": null,
        "lastName": null,
        "username": null,
        "avatar": null,
        "settings": null
    };

  try {
    const response = await fetch(url, {
      method: 'PUT',
      headers: {
        'Content-Type': 'application/json',
        'Authorization': 'Bearer YOUR_JWT_TOKEN', // This endpoint requires authentication
      },
      body: JSON.stringify(requestData)
    });

    if (!response.ok) {
      throw new Error('API error: ' + response.status);
    }

    const data = await response.json();
    console.log('Success:', data);
    return data;
  } catch (error) {
    console.error('Error:', error);
    throw error;
  }
}
```

#### Response

**Response Schema:**

```json
{
    "user": {
        "id": "example_id_123",
        "email": "user@example.com",
        "firstName": "Example Name",
        "lastName": "Example Name",
        "username": "Example Name",
        "roles": [
            "example_item"
        ],
        "avatar": null,
        "plan": "example_string",
        "emailVerified": null,
        "isActive": null,
        "createdAt": null,
        "lastLoginAt": null,
        "updatedAt": null,
        "settings": null,
        "stats": null
    },
    "message": "example_string",
    "success": true,
    "timestamp": "example_string"
}
```

---

<!-- Route 6 -->
### POST /api/auth/register

Register a new user account

Creates a new user with the provided information and returns a JWT token for immediate authentication.

#### Request Body

**RegisterRequestDTO**

Data Transfer Object for user registration requests.

Handles new user account creation with validation for all
required fields. Used by the registration system to collect
and validate user information before creating new accounts
in the platform.

```typescript
interface RegisterRequestDTO {
  /**
   * User's email address for account registration. Must be a valid email format and unique in the sys...
   */
  email: string;

  /**
   * User's chosen password for account security. Must meet security requirements: minimum 8 character...
   */
  password: string;

  /**
   * User's first (given) name. Required for account identification and personalization. Used in user ...
   */
  firstName: string;

  /**
   * User's last (family) name. Required for account identification and personalization. Used in user ...
   */
  lastName: string;

  /**
   * Optional unique username for the account. If provided, must be 3-100 characters and contain only ...
   */
  username: string;

}
```

**Example Request:**

```javascript
// Example API Request for POST /api/auth/register
async function examplePostRequest() {
  const url = 'https://example.com/api/auth/register';
  const requestData = {
        "email": "user@example.com",
        "password": "secure_password123",
        "firstName": "Example Name",
        "lastName": "Example Name",
        "username": "Example Name"
    };

  try {
    const response = await fetch(url, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify(requestData)
    });

    if (!response.ok) {
      throw new Error('API error: ' + response.status);
    }

    const data = await response.json();
    console.log('Success:', data);
    return data;
  } catch (error) {
    console.error('Error:', error);
    throw error;
  }
}
```

#### Response

**Response Schema:**

```json
{
    "token": "example_token_12345",
    "user": {
        "id": "example_id_123",
        "email": "user@example.com",
        "firstName": "Example Name",
        "lastName": "Example Name",
        "username": "Example Name",
        "roles": [
            "example_item"
        ],
        "avatar": null,
        "plan": "example_string",
        "emailVerified": null,
        "isActive": null,
        "createdAt": null,
        "lastLoginAt": null,
        "updatedAt": null,
        "settings": null,
        "stats": null
    },
    "message": "example_string",
    "success": true,
    "timestamp": "example_string"
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
### GET /api/designs

List designs for authenticated user

Returns a paginated list of designs belonging to the authenticated user.
Supports filtering by project, status, and search functionality.

#### Response

**Response Schema:**

```json
{
    "data": [
        "example_item"
    ],
    "page": 25,
    "limit": 20,
    "total": 42,
    "totalPages": 25,
    "message": "example_string"
}
```

---

<!-- Route 2 -->
### POST /api/designs

Create a new design

Creates a new design with the provided information and associates it with the authenticated user.
Validates design data and initializes default canvas settings.

#### Request Body

**CreateDesignRequestDTO**

Request DTO for creating a new design within a project.

This DTO handles the creation of new designs with specified canvas
dimensions, initial design settings, and organizational metadata.

```typescript
interface CreateDesignRequestDTO {
  /**
   * Display name for the new design. This name is used throughout the application interface and shoul...
   * @validation NotBlank=message: 'Design name is required'
   * @validation Length=min: 1  max: 255  minMessage: 'Design name must be at least 1 character'  maxMessage: 'Design name cannot exceed 255 characters'
   */
  name: string;

  /**
   * Optional description providing additional context about the design. Used to document the design's...
   * @validation Length=max: 1000  maxMessage: 'Description cannot exceed 1000 characters'
   */
  description?: string;

  /**
   * Design-level configuration and settings. Contains global design settings including canvas backgro...
   * @validation Valid
   */
  data?: {
    backgroundColor?: string;
    animationSettings?: Record<string, any>;
    gridSettings?: Record<string, any>;
    viewSettings?: Record<string, any>;
    globalStyles?: Record<string, any>;
    customProperties?: Record<string, any>;
  };

  /**
   * Optional project ID to associate the design with. If provided, the design will be created within ...
   * @validation Type='integer'  message: 'Project ID must be an integer'
   * @validation Positive=message: 'Project ID must be positive'
   */
  projectId?: number;

  /**
   * Canvas width in pixels. Defines the horizontal dimension of the design canvas. Must be between 1-...
   * @validation Type='integer'  message: 'Width must be an integer'
   * @validation Positive=message: 'Width must be positive'
   * @validation Range=min: 1  max: 10000  notInRangeMessage: 'Width must be between 1 and 10000 pixels'
   */
  width?: number;

  /**
   * Canvas height in pixels. Defines the vertical dimension of the design canvas. Must be between 1-1...
   * @validation Type='integer'  message: 'Height must be an integer'
   * @validation Positive=message: 'Height must be positive'
   * @validation Range=min: 1  max: 10000  notInRangeMessage: 'Height must be between 1 and 10000 pixels'
   */
  height?: number;

  /**
   * Whether the design should be publicly accessible. Public designs can be viewed by other users and...
   * @validation Type='bool'  message: 'Is public must be a boolean'
   */
  isPublic?: boolean;

}
```

**Example Request:**

```javascript
// Example API Request for POST /api/designs
async function examplePostRequest() {
  const url = 'https://example.com/api/designs';
  const requestData = {
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
    };

  try {
    const response = await fetch(url, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify(requestData)
    });

    if (!response.ok) {
      throw new Error('API error: ' + response.status);
    }

    const data = await response.json();
    console.log('Success:', data);
    return data;
  } catch (error) {
    console.error('Error:', error);
    throw error;
  }
}
```

#### Response

**Response Schema:**

```json
{
    "design": null,
    "designs": null,
    "total": null,
    "page": null,
    "totalPages": null,
    "message": "example_string",
    "success": true,
    "timestamp": "example_string"
}
```

---

<!-- Route 3 -->
### GET /api/designs/search

Search designs

Performs a comprehensive search across designs accessible to the authenticated user.
Searches in design names, descriptions, and associated project information.

#### Response

**Response Schema:**

```json
{
    "data": [
        "example_item"
    ],
    "page": 25,
    "limit": 20,
    "total": 42,
    "totalPages": 25,
    "message": "example_string"
}
```

---

<!-- Route 4 -->
### GET /api/designs/{id}

Get details of a specific design

Returns detailed information about a single design including canvas data and layers.
Only allows access to designs owned by the authenticated user or public designs.

#### Parameters

- **id** (int)

#### Response

**Response Schema:**

```json
{
    "design": null,
    "designs": null,
    "total": null,
    "page": null,
    "totalPages": null,
    "message": "example_string",
    "success": true,
    "timestamp": "example_string"
}
```

---

<!-- Route 5 -->
### PUT /api/designs/{id}

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

```typescript
interface UpdateDesignRequestDTO {
  /**
   * Updated display name for the design. If provided, replaces the current design name. Must be betwe...
   * @validation Length=min: 1  max: 255  minMessage: 'Design name must be at least 1 character'  maxMessage: 'Design name cannot exceed 255 characters'
   */
  name?: string;

  /**
   * Updated description for the design. If provided, replaces the current description. Maximum 1000 c...
   * @validation Length=max: 1000  maxMessage: 'Description cannot exceed 1000 characters'
   */
  description?: string;

  /**
   * Updated design-level configuration and settings. If provided, merges with or replaces current des...
   * @validation Valid
   */
  data?: {
    backgroundColor?: string;
    animationSettings?: Record<string, any>;
    gridSettings?: Record<string, any>;
    viewSettings?: Record<string, any>;
    globalStyles?: Record<string, any>;
    customProperties?: Record<string, any>;
  };

  /**
   * Updated project association for the design. If provided, moves the design to the specified projec...
   * @validation Type='integer'  message: 'Project ID must be an integer'
   * @validation Positive=message: 'Project ID must be positive'
   */
  projectId?: number;

  /**
   * Updated canvas width in pixels. If provided, resizes the canvas width. Must be between 1-10000 pi...
   * @validation Type='integer'  message: 'Width must be an integer'
   * @validation Positive=message: 'Width must be positive'
   * @validation Range=min: 1  max: 10000  notInRangeMessage: 'Width must be between 1 and 10000 pixels'
   */
  width?: number;

  /**
   * Updated canvas height in pixels. If provided, resizes the canvas height. Must be between 1-10000 ...
   * @validation Type='integer'  message: 'Height must be an integer'
   * @validation Positive=message: 'Height must be positive'
   * @validation Range=min: 1  max: 10000  notInRangeMessage: 'Height must be between 1 and 10000 pixels'
   */
  height?: number;

  /**
   * Updated public visibility for the design. If provided, changes whether the design is publicly acc...
   * @validation Type='bool'  message: 'Is public must be a boolean'
   */
  isPublic?: boolean;

}
```

**Example Request:**

```javascript
// Example API Request for PUT /api/designs/{id}
async function examplePutRequest() {
  const url = 'https://example.com/api/designs/{id}';
  const requestData = {
        "name": null,
        "description": null,
        "data": null,
        "projectId": null,
        "width": null,
        "height": null,
        "isPublic": null
    };

  try {
    const response = await fetch(url, {
      method: 'PUT',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify(requestData)
    });

    if (!response.ok) {
      throw new Error('API error: ' + response.status);
    }

    const data = await response.json();
    console.log('Success:', data);
    return data;
  } catch (error) {
    console.error('Error:', error);
    throw error;
  }
}
```

#### Response

**Response Schema:**

```json
{
    "design": null,
    "designs": null,
    "total": null,
    "page": null,
    "totalPages": null,
    "message": "example_string",
    "success": true,
    "timestamp": "example_string"
}
```

---

<!-- Route 6 -->
### DELETE /api/designs/{id}

Delete a design

Permanently deletes a design and all its associated data (layers, media files, export jobs).
Only allows deletion of designs owned by the authenticated user.
This action cannot be undone.

#### Parameters

- **id** (int)

#### Response

**Response Schema:**

```json
{
    "message": "example_string",
    "success": true,
    "timestamp": "example_string"
}
```

---

<!-- Route 7 -->
### POST /api/designs/{id}/duplicate

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

```typescript
interface DuplicateDesignRequestDTO {
  /**
   * Custom name for the duplicated design. If provided, this will be used as the name for the new des...
   * @validation Length=min: 1  max: 255  minMessage: 'Design name must be at least 1 character'  maxMessage: 'Design name cannot exceed 255 characters'
   */
  name?: string;

  /**
   * Target project ID for the duplicated design. If provided, the duplicated design will be placed in...
   * @validation Type='integer'  message: 'Project ID must be an integer'
   * @validation Positive=message: 'Project ID must be positive'
   */
  projectId?: number;

}
```

**Example Request:**

```javascript
// Example API Request for POST /api/designs/{id}/duplicate
async function examplePostRequest() {
  const url = 'https://example.com/api/designs/{id}/duplicate';
  const requestData = {
        "name": null,
        "projectId": null
    };

  try {
    const response = await fetch(url, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify(requestData)
    });

    if (!response.ok) {
      throw new Error('API error: ' + response.status);
    }

    const data = await response.json();
    console.log('Success:', data);
    return data;
  } catch (error) {
    console.error('Error:', error);
    throw error;
  }
}
```

#### Response

**Response Schema:**

```json
{
    "design": null,
    "designs": null,
    "total": null,
    "page": null,
    "totalPages": null,
    "message": "example_string",
    "success": true,
    "timestamp": "example_string"
}
```

---

<!-- Route 8 -->
### PUT /api/designs/{id}/thumbnail

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

```typescript
interface UpdateDesignThumbnailRequestDTO {
  /**
   * URL of the new thumbnail image. Must be a valid URL pointing to an image file that will serve as ...
   * @validation NotBlank=message: 'Thumbnail URL is required'
   * @validation Url=message: 'Thumbnail must be a valid URL'
   */
  thumbnail: string;

}
```

**Example Request:**

```javascript
// Example API Request for PUT /api/designs/{id}/thumbnail
async function examplePutRequest() {
  const url = 'https://example.com/api/designs/{id}/thumbnail';
  const requestData = {
        "thumbnail": "example_string"
    };

  try {
    const response = await fetch(url, {
      method: 'PUT',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify(requestData)
    });

    if (!response.ok) {
      throw new Error('API error: ' + response.status);
    }

    const data = await response.json();
    console.log('Success:', data);
    return data;
  } catch (error) {
    console.error('Error:', error);
    throw error;
  }
}
```

#### Response

**Response Schema:**

```json
{
    "design": null,
    "designs": null,
    "total": null,
    "page": null,
    "totalPages": null,
    "message": "example_string",
    "success": true,
    "timestamp": "example_string"
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
### GET /api/export-jobs

**Security:** IsGranted

List export jobs for authenticated user

Returns a paginated list of export jobs belonging to the authenticated user.
Supports filtering by status and format, with configurable pagination.

#### Response

**Response Schema:**

```json
{
    "job": null,
    "jobs": null,
    "total": null,
    "page": null,
    "totalPages": null,
    "message": "example_string",
    "success": true,
    "timestamp": "example_string"
}
```

---

<!-- Route 2 -->
### POST /api/export-jobs

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

```typescript
interface CreateExportJobRequestDTO {
  /**
   * ID of the design to export. References the specific design that should be rendered and exported. ...
   * @validation NotBlank=message: 'Design ID is required'
   * @validation Type=type: 'integer'  message: 'Design ID must be an integer'
   * @validation Positive=message: 'Design ID must be positive'
   */
  designId: number;

  /**
   * Output format for the exported file. Determines the file type and rendering pipeline used for the...
   * @validation NotBlank=message: 'Format is required'
   * @validation Choice=choices: ['png'  'jpeg'  'svg'  'pdf'  'mp4'  'gif']  message: 'Invalid format'
   */
  format?: string;

  /**
   * Quality level for the export rendering. Controls the balance between file size and visual quality...
   * @validation Choice=choices: ['low'  'medium'  'high'  'ultra']  message: 'Invalid quality'
   */
  quality?: string;

  /**
   * Custom width for the exported file in pixels. If provided, overrides the design's canvas width. M...
   * @validation Type=type: 'integer'  message: 'Width must be an integer'
   * @validation Positive=message: 'Width must be positive'
   */
  width?: number;

  /**
   * Custom height for the exported file in pixels. If provided, overrides the design's canvas height....
   * @validation Type=type: 'integer'  message: 'Height must be an integer'
   * @validation Positive=message: 'Height must be positive'
   */
  height?: number;

  /**
   * Scale factor for resizing the export. Multiplier applied to the design's original dimensions. For...
   * @validation Type=type: 'float'  message: 'Scale must be a number'
   * @validation PositiveOrZero=message: 'Scale must be positive or zero'
   */
  scale?: number;

  /**
   * Enable transparent background for supported formats. When true, removes the canvas background and...
   * @validation Type=type: 'bool'  message: 'Transparent must be a boolean'
   */
  transparent?: boolean;

  /**
   * Custom background color for the export. Hex color code (e.g., "#ffffff") to use as the canvas bac...
   * @validation Type=type: 'string'  message: 'Background color must be a string'
   */
  backgroundColor?: string;

  /**
   * Animation-specific settings for video/GIF exports. Configuration for animated exports including: ...
   * @validation Type=type: 'array'  message: 'Animation settings must be an array'
   */
  animationSettings?: Record<string, any>;

}
```

**Example Request:**

```javascript
// Example API Request for POST /api/export-jobs
async function examplePostRequest() {
  const url = 'https://example.com/api/export-jobs';
  const requestData = {
        "designId": 123,
        "format": "example_string",
        "quality": "example_string",
        "width": null,
        "height": null,
        "scale": null,
        "transparent": true,
        "backgroundColor": null,
        "animationSettings": null
    };

  try {
    const response = await fetch(url, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Authorization': 'Bearer YOUR_JWT_TOKEN', // This endpoint requires authentication
      },
      body: JSON.stringify(requestData)
    });

    if (!response.ok) {
      throw new Error('API error: ' + response.status);
    }

    const data = await response.json();
    console.log('Success:', data);
    return data;
  } catch (error) {
    console.error('Error:', error);
    throw error;
  }
}
```

#### Response

**Response Schema:**

```json
{
    "job": null,
    "jobs": null,
    "total": null,
    "page": null,
    "totalPages": null,
    "message": "example_string",
    "success": true,
    "timestamp": "example_string"
}
```

---

<!-- Route 3 -->
### GET /api/export-jobs/queue-status

**Security:** IsGranted

Get export job queue status (Admin only)

Returns system-wide export job queue statistics and health information.
Includes pending/processing job counts, average processing times, and queue health metrics.
Only accessible to users with ROLE_ADMIN.

#### Response

**Response Schema:**

```json
{
    "details": [
        "example_item"
    ],
    "code": null,
    "message": "example_string",
    "success": true,
    "timestamp": "example_string"
}
```

---

<!-- Route 4 -->
### GET /api/export-jobs/stats

**Security:** IsGranted

Get export job statistics for authenticated user

Returns comprehensive statistics about the user's export job usage,
including totals by status, format breakdown, and success rate.

#### Response

**Response Schema:**

```json
{
    "details": [
        "example_item"
    ],
    "code": null,
    "message": "example_string",
    "success": true,
    "timestamp": "example_string"
}
```

---

<!-- Route 5 -->
### GET /api/export-jobs/{id}

**Security:** IsGranted

Get details of a specific export job

Returns detailed information about a single export job.
Only allows access to export jobs owned by the authenticated user.

#### Response

**Response Schema:**

```json
{
    "job": null,
    "jobs": null,
    "total": null,
    "page": null,
    "totalPages": null,
    "message": "example_string",
    "success": true,
    "timestamp": "example_string"
}
```

---

<!-- Route 6 -->
### PUT /api/export-jobs/{id}

**Security:** IsGranted

Update an export job (Not allowed)

Export jobs are immutable after creation and cannot be modified.
This endpoint always returns an error indicating that modifications are not permitted.

#### Response

**Response Schema:**

```json
{
    "details": [
        "example_item"
    ],
    "code": null,
    "message": "example_string",
    "success": true,
    "timestamp": "example_string"
}
```

---

<!-- Route 7 -->
### DELETE /api/export-jobs/{id}

**Security:** IsGranted

Delete an export job

Deletes an export job and its associated output file.
Only allows deletion of jobs in pending, failed, or completed status.
Only allows access to export jobs owned by the authenticated user.

#### Response

**Response Schema:**

```json
{
    "message": "example_string",
    "success": true,
    "timestamp": "example_string"
}
```

---

<!-- Route 8 -->
### POST /api/export-jobs/{id}/cancel

**Security:** IsGranted

Cancel a pending or processing export job

Cancels an export job that is currently pending or being processed.
Sets the job status to cancelled and stops any ongoing processing.
Only allows access to export jobs owned by the authenticated user.

#### Response

**Response Schema:**

```json
{
    "job": null,
    "jobs": null,
    "total": null,
    "page": null,
    "totalPages": null,
    "message": "example_string",
    "success": true,
    "timestamp": "example_string"
}
```

---

<!-- Route 9 -->
### GET /api/export-jobs/{id}/download

**Security:** IsGranted

Download export job output file

Downloads the generated file from a completed export job.
Returns the file as an attachment with appropriate headers.
Only allows download of completed jobs with existing output files.
Only allows access to export jobs owned by the authenticated user.

---

<!-- Route 10 -->
### POST /api/export-jobs/{id}/retry

**Security:** IsGranted

Retry a failed export job

Resets a failed export job back to pending status for re-processing.
Clears error messages and resets progress to zero.
Only allows retry of jobs with failed status.
Only allows access to export jobs owned by the authenticated user.

#### Response

**Response Schema:**

```json
{
    "job": null,
    "jobs": null,
    "total": null,
    "page": null,
    "totalPages": null,
    "message": "example_string",
    "success": true,
    "timestamp": "example_string"
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
### POST /api/layers

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

```typescript
interface CreateLayerRequestDTO {
  /**
   * Design ID where the new layer will be created Must be a valid UUID string identifying an existing...
   * @validation NotBlank=message: 'Design ID is required'
   * @validation Type=type: 'string'  message: 'Design ID must be a string'
   */
  designId: string;

  /**
   * Type of layer being created Determines which properties and behaviors the layer will have Valid v...
   * @validation NotBlank=message: 'Layer type is required'
   * @validation Choice=choices: ['text'  'image'  'shape'  'group'  'video'  'audio']  message: 'Invalid layer type. Must be one of: text  image  shape  group  video  audio'
   */
  type: string;

  /**
   * Display name for the layer Used for identification in the layers panel and timeline Must be 1-255...
   * @validation NotBlank=message: 'Layer name is required'
   * @validation Length=max: 255  maxMessage: 'Layer name cannot be longer than 255 characters'
   */
  name: string;

  /**
   * Layer-specific visual and behavior properties Contains type-specific attributes like text styling...
   */
  properties: {
    text?: string;
    fontFamily?: string;
    fontSize?: number;
    fontWeight?: string;
    fontStyle?: string;
    textAlign?: string;
    color?: string;
    lineHeight?: number;
    letterSpacing?: number;
    textDecoration?: string;
  } | {
    src?: string;
    alt?: string;
    objectFit?: string;
    objectPosition?: string;
    quality?: number;
    brightness?: number;
    contrast?: number;
    saturation?: number;
    blur?: number;
  } | {
    shapeType?: string;
    fillColor?: string;
    fillOpacity?: number;
    strokeColor?: string;
    strokeWidth?: number;
    strokeOpacity?: number;
    borderRadius?: number;
    sides?: number;
  };

  /**
   * 2D transformation matrix for initial layer positioning Controls position, size, rotation, scale, ...
   */
  transform: {
    x?: number;
    y?: number;
    width?: number;
    height?: number;
    rotation?: number;
    scaleX?: number;
    scaleY?: number;
    skewX?: number;
    skewY?: number;
    opacity?: number;
  };

  /**
   * Layer stacking order within the design Higher values appear above lower values in the visual stac...
   * @validation Type=type: 'integer'  message: 'Z-index must be an integer'
   * @validation PositiveOrZero=message: 'Z-index must be positive or zero'
   */
  zIndex?: number;

  /**
   * Initial visibility state of the layer true: Layer is visible and rendered in the canvas (default)...
   * @validation Type=type: 'boolean'  message: 'Visible must be a boolean'
   */
  visible?: boolean;

  /**
   * Initial edit protection state false: Layer can be freely edited and manipulated (default) true: L...
   * @validation Type=type: 'boolean'  message: 'Locked must be a boolean'
   */
  locked?: boolean;

  /**
   * Parent layer ID for hierarchical grouping null: Layer is created at root level of the design (def...
   * @validation Type=type: 'string'  message: 'Parent layer ID must be a string'
   */
  parentLayerId?: string;

}
```

**Example Request:**

```javascript
// Example API Request for POST /api/layers
async function examplePostRequest() {
  const url = 'https://example.com/api/layers';
  const requestData = {
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
    };

  try {
    const response = await fetch(url, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify(requestData)
    });

    if (!response.ok) {
      throw new Error('API error: ' + response.status);
    }

    const data = await response.json();
    console.log('Success:', data);
    return data;
  } catch (error) {
    console.error('Error:', error);
    throw error;
  }
}
```

#### Response

**Response Schema:**

```json
{
    "layer": null,
    "layers": null,
    "message": "example_string",
    "success": true,
    "timestamp": "example_string"
}
```

---

<!-- Route 2 -->
### PUT /api/layers/bulk-update

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

```typescript
interface BulkUpdateLayersRequestDTO {
  /**
   * Array of layer updates to perform in batch. Each LayerUpdate contains: - id: The unique identifie...
   */
  layers: {
    id: number;
    name?: string | null;
    transform?: {
    x?: number;
    y?: number;
    width?: number;
    height?: number;
    rotation?: number;
    scaleX?: number;
    scaleY?: number;
    skewX?: number;
    skewY?: number;
    opacity?: number;
  };
    properties?: {
    text?: string;
    fontFamily?: string;
    fontSize?: number;
    fontWeight?: string;
    fontStyle?: string;
    textAlign?: string;
    color?: string;
    lineHeight?: number;
    letterSpacing?: number;
    textDecoration?: string;
  } | {
    src?: string;
    alt?: string;
    objectFit?: string;
    objectPosition?: string;
    quality?: number;
    brightness?: number;
    contrast?: number;
    saturation?: number;
    blur?: number;
  } | {
    shapeType?: string;
    fillColor?: string;
    fillOpacity?: number;
    strokeColor?: string;
    strokeWidth?: number;
    strokeOpacity?: number;
    borderRadius?: number;
    sides?: number;
  };
    zIndex?: number | null;
    visible?: boolean | null;
    locked?: boolean | null;
    parentLayerId?: string | null;
  }[];

}
```

**Example Request:**

```javascript
// Example API Request for PUT /api/layers/bulk-update
async function examplePutRequest() {
  const url = 'https://example.com/api/layers/bulk-update';
  const requestData = {
        "layers": [
            "example_item"
        ]
    };

  try {
    const response = await fetch(url, {
      method: 'PUT',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify(requestData)
    });

    if (!response.ok) {
      throw new Error('API error: ' + response.status);
    }

    const data = await response.json();
    console.log('Success:', data);
    return data;
  } catch (error) {
    console.error('Error:', error);
    throw error;
  }
}
```

#### Response

**Response Schema:**

```json
{
    "message": "example_string",
    "success": true,
    "timestamp": "example_string"
}
```

---

<!-- Route 3 -->
### GET /api/layers/{id}

Get details of a specific layer

Returns comprehensive information about a single layer including all properties,
position, styling, and metadata. Validates access permissions through design ownership.

#### Parameters

- **id** (int)

#### Response

**Response Schema:**

```json
{
    "layer": null,
    "layers": null,
    "message": "example_string",
    "success": true,
    "timestamp": "example_string"
}
```

---

<!-- Route 4 -->
### PUT /api/layers/{id}

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

```typescript
interface UpdateLayerRequestDTO {
  /**
   * New display name for the layer Used for layer identification in the layers panel and timeline Mus...
   * @validation Length=max: 255  maxMessage: 'Layer name cannot be longer than 255 characters'
   */
  name?: string;

  /**
   * Layer-specific visual and behavior properties Contains type-specific attributes like text styling...
   */
  properties?: {
    text?: string;
    fontFamily?: string;
    fontSize?: number;
    fontWeight?: string;
    fontStyle?: string;
    textAlign?: string;
    color?: string;
    lineHeight?: number;
    letterSpacing?: number;
    textDecoration?: string;
  } | {
    src?: string;
    alt?: string;
    objectFit?: string;
    objectPosition?: string;
    quality?: number;
    brightness?: number;
    contrast?: number;
    saturation?: number;
    blur?: number;
  } | {
    shapeType?: string;
    fillColor?: string;
    fillOpacity?: number;
    strokeColor?: string;
    strokeWidth?: number;
    strokeOpacity?: number;
    borderRadius?: number;
    sides?: number;
  };

  /**
   * 2D transformation matrix for layer positioning and scaling Controls position, size, rotation, sca...
   */
  transform?: {
    x?: number;
    y?: number;
    width?: number;
    height?: number;
    rotation?: number;
    scaleX?: number;
    scaleY?: number;
    skewX?: number;
    skewY?: number;
    opacity?: number;
  };

  /**
   * Layer stacking order within its parent container Higher values appear above lower values in the v...
   * @validation Type=type: 'integer'  message: 'Z-index must be an integer'
   * @validation PositiveOrZero=message: 'Z-index must be positive or zero'
   */
  zIndex?: number;

  /**
   * Layer visibility state in the design canvas false: Layer is hidden from view but remains in the d...
   * @validation Type=type: 'boolean'  message: 'Visible must be a boolean'
   */
  visible?: boolean;

  /**
   * Layer edit protection state true: Layer cannot be selected, moved, or modified false: Layer can b...
   * @validation Type=type: 'boolean'  message: 'Locked must be a boolean'
   */
  locked?: boolean;

  /**
   * Parent layer ID for hierarchical grouping null: Layer is at root level of the design string: Laye...
   * @validation Type=type: 'string'  message: 'Parent layer ID must be a string'
   */
  parentLayerId?: string;

}
```

**Example Request:**

```javascript
// Example API Request for PUT /api/layers/{id}
async function examplePutRequest() {
  const url = 'https://example.com/api/layers/{id}';
  const requestData = {
        "name": null,
        "properties": null,
        "transform": null,
        "zIndex": null,
        "visible": null,
        "locked": null,
        "parentLayerId": null
    };

  try {
    const response = await fetch(url, {
      method: 'PUT',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify(requestData)
    });

    if (!response.ok) {
      throw new Error('API error: ' + response.status);
    }

    const data = await response.json();
    console.log('Success:', data);
    return data;
  } catch (error) {
    console.error('Error:', error);
    throw error;
  }
}
```

#### Response

**Response Schema:**

```json
{
    "layer": null,
    "layers": null,
    "message": "example_string",
    "success": true,
    "timestamp": "example_string"
}
```

---

<!-- Route 5 -->
### DELETE /api/layers/{id}

Delete a layer from a design

Permanently removes a layer and all its associated data from the design.
Automatically adjusts z-indexes of remaining layers to maintain proper ordering.
Users can only delete layers in designs they own.

#### Parameters

- **id** (int)

#### Response

**Response Schema:**

```json
{
    "message": "example_string",
    "success": true,
    "timestamp": "example_string"
}
```

---

<!-- Route 6 -->
### POST /api/layers/{id}/duplicate

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

```typescript
interface DuplicateLayerRequestDTO {
  /**
   * Custom name for the duplicated layer. If provided, this will be used as the name for the new laye...
   * @validation Length=max: 255  maxMessage: 'New layer name cannot be longer than 255 characters'
   */
  name?: string;

  /**
   * Target design ID for cross-design duplication. If provided, the layer will be duplicated into the...
   * @validation Type=type: 'string'  message: 'Target design ID must be a string'
   */
  targetDesignId?: string;

}
```

**Example Request:**

```javascript
// Example API Request for POST /api/layers/{id}/duplicate
async function examplePostRequest() {
  const url = 'https://example.com/api/layers/{id}/duplicate';
  const requestData = {
        "name": null,
        "targetDesignId": null
    };

  try {
    const response = await fetch(url, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify(requestData)
    });

    if (!response.ok) {
      throw new Error('API error: ' + response.status);
    }

    const data = await response.json();
    console.log('Success:', data);
    return data;
  } catch (error) {
    console.error('Error:', error);
    throw error;
  }
}
```

#### Response

**Response Schema:**

```json
{
    "layer": null,
    "layers": null,
    "message": "example_string",
    "success": true,
    "timestamp": "example_string"
}
```

---

<!-- Route 7 -->
### PUT /api/layers/{id}/move

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

```typescript
interface MoveLayerRequestDTO {
  /**
   * Direction to move the layer relative to its current position. Supported values: - 'up': Move one ...
   * @validation Choice=choices: ['up'  'down'  'top'  'bottom']  message: 'Direction must be one of: up  down  top  bottom'
   */
  direction?: string;

  /**
   * Absolute z-index position to move the layer to. When provided, moves the layer to this exact z-in...
   * @validation Type=type: 'integer'  message: 'Target Z-index must be an integer'
   * @validation PositiveOrZero=message: 'Target Z-index must be positive or zero'
   */
  targetZIndex?: number;

}
```

**Example Request:**

```javascript
// Example API Request for PUT /api/layers/{id}/move
async function examplePutRequest() {
  const url = 'https://example.com/api/layers/{id}/move';
  const requestData = {
        "direction": null,
        "targetZIndex": null
    };

  try {
    const response = await fetch(url, {
      method: 'PUT',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify(requestData)
    });

    if (!response.ok) {
      throw new Error('API error: ' + response.status);
    }

    const data = await response.json();
    console.log('Success:', data);
    return data;
  } catch (error) {
    console.error('Error:', error);
    throw error;
  }
}
```

#### Response

**Response Schema:**

```json
{
    "layer": null,
    "layers": null,
    "message": "example_string",
    "success": true,
    "timestamp": "example_string"
}
```

---

## MediaController

*9 routes*

Media Controller

Manages media file operations including upload, retrieval, updating, and deletion.
Handles media search, duplication, stock media integration, and bulk operations.
All endpoints require authentication and enforce user ownership for security.

<!-- Route 1 -->
### GET /api/media

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

```typescript
interface SearchMediaRequestDTO {
  /**
   * Page number for pagination. Specifies which page of media results to return. Must be 1 or greater...
   * @validation Range=min: 1  notInRangeMessage: 'Page must be {{ min }} or greater'
   */
  page?: number;

  /**
   * Number of media items per page. Controls how many media files are returned per page. Limited to a...
   * @validation Range=min: 1  max: 50  notInRangeMessage: 'Limit must be between {{ min }} and {{ max }}'
   */
  limit?: number;

  /**
   * Media type filter. Filters media by type (image, video, or audio). When null, all media types are...
   * @validation Choice=choices: ['image'  'video'  'audio']  message: 'Type must be one of: image  video  audio'
   */
  type?: string;

  /**
   * Media source filter. Filters media by its source origin (upload for user uploads, or stock photo ...
   * @validation Choice=choices: ['upload'  'unsplash'  'pexels'  'pixabay']  message: 'Source must be one of: upload  unsplash  pexels  pixabay'
   */
  source?: string;

  /**
   * Search query term. Text to search for in media file names, descriptions, and metadata. When null,...
   * @validation Length=max: 255  maxMessage: 'Search query cannot be longer than {{ limit }} characters'
   */
  search?: string;

  /**
   * Comma-separated list of tags for filtering. Tags to filter media by. Multiple tags can be specifi...
   */
  tags?: string;

}
```

**Example Request:**

```javascript
// Example API Request for GET /api/media
async function exampleGetRequest() {
  const url = 'https://example.com/api/media';
  const requestData = {
        "page": 25,
        "limit": 20,
        "type": null,
        "source": null,
        "search": null,
        "tags": null
    };

  try {
    const response = await fetch(url, {
      method: 'GET',
      headers: {
        'Content-Type': 'application/json',
      },
    });

    if (!response.ok) {
      throw new Error('API error: ' + response.status);
    }

    const data = await response.json();
    console.log('Success:', data);
    return data;
  } catch (error) {
    console.error('Error:', error);
    throw error;
  }
}
```

#### Response

**Response Schema:**

```json
{
    "data": [
        "example_item"
    ],
    "page": 25,
    "limit": 20,
    "total": 42,
    "totalPages": 25,
    "message": "example_string"
}
```

---

<!-- Route 2 -->
### POST /api/media

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

```typescript
interface CreateMediaRequestDTO {
  /**
   * Display name for the media item. This name is shown in the media library and used for searching. ...
   * @validation NotBlank=message: 'Media name is required'
   * @validation Length=min: 1  max: 255  minMessage: 'Media name must be at least {{ limit }} character long'  maxMessage: 'Media name cannot be longer than {{ limit }} characters'
   */
  name: string;

  /**
   * Type of media content. Categorizes the media into broad types for filtering and appropriate handl...
   * @validation NotBlank=message: 'Media type is required'
   * @validation Choice=choices: ['image'  'video'  'audio']  message: 'Type must be one of: image  video  audio'
   */
  type: string;

  /**
   * MIME type of the media file. Specifies the exact format of the media file for proper handling by ...
   * @validation NotBlank=message: 'MIME type is required'
   */
  mimeType: string;

  /**
   * File size in bytes. Used for storage quota management, upload progress, and performance optimizat...
   * @validation PositiveOrZero=message: 'Size must be a positive number'
   */
  size: number;

  /**
   * Direct URL to access the media file. This is the primary URL used to display or download the medi...
   * @validation NotBlank=message: 'URL is required'
   * @validation Url=message: 'URL must be a valid URL'
   */
  url: string;

  /**
   * Optional URL to a thumbnail or preview image. Used for quick previews in the media library and la...
   * @validation Url=message: 'Thumbnail URL must be a valid URL'
   */
  thumbnailUrl?: string;

  /**
   * Width of the media in pixels (for visual media). Essential for layout calculations and aspect rat...
   * @validation PositiveOrZero=message: 'Width must be a positive number'
   */
  width?: number;

  /**
   * Height of the media in pixels (for visual media). Essential for layout calculations and aspect ra...
   * @validation PositiveOrZero=message: 'Height must be a positive number'
   */
  height?: number;

  /**
   * Duration in seconds (for time-based media). Used for video and audio files to display playback le...
   * @validation PositiveOrZero=message: 'Duration must be a positive number'
   */
  duration?: number;

  /**
   * Source platform or service where the media originated. Tracks the origin of media for attribution...
   * @validation Choice=choices: ['upload'  'unsplash'  'pexels'  'pixabay']  message: 'Source must be one of: upload  unsplash  pexels  pixabay'
   */
  source?: string;

  /**
   * Unique identifier from the source platform. For stock photo services, this is their internal ID f...
   */
  sourceId?: string;

  /**
   * Technical metadata about the media file. Contains detailed information about the media file inclu...
   * @validation Valid
   */
  metadata?: {
    fileSize: number;
    mimeType: string;
    width?: number | null;
    height?: number | null;
    duration?: number | null;
    bitrate?: number | null;
    sampleRate?: number | null;
    channels?: number | null;
    colorSpace?: string | null;
    hasTransparency?: boolean | null;
    frameRate?: number | null;
    codec?: string | null;
    aspectRatio?: number | null;
  };

  /**
   * Organizational tags for categorizing and searching media. Tags help users organize their media li...
   * @validation Valid
   */
  tags?: Tag[];

  /**
   * Attribution text for the media creator. Required for some stock photo services and user-generated...
   */
  attribution?: string;

  /**
   * License type under which the media is distributed. Defines usage rights and restrictions for the ...
   */
  license?: string;

  /**
   * Whether this media requires a premium subscription to use. Premium media may have additional lice...
   * @validation Type='bool'  message: 'isPremium must be a boolean'
   */
  isPremium?: boolean;

  /**
   * Whether this media is currently active and available for use. Inactive media is hidden from searc...
   * @validation Type='bool'  message: 'isActive must be a boolean'
   */
  isActive?: boolean;

}
```

**Example Request:**

```javascript
// Example API Request for POST /api/media
async function examplePostRequest() {
  const url = 'https://example.com/api/media';
  const requestData = {
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
    };

  try {
    const response = await fetch(url, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify(requestData)
    });

    if (!response.ok) {
      throw new Error('API error: ' + response.status);
    }

    const data = await response.json();
    console.log('Success:', data);
    return data;
  } catch (error) {
    console.error('Error:', error);
    throw error;
  }
}
```

#### Response

**Response Schema:**

```json
{
    "media": null,
    "mediaList": null,
    "total": null,
    "page": null,
    "totalPages": null,
    "message": "example_string",
    "success": true,
    "timestamp": "example_string"
}
```

---

<!-- Route 3 -->
### DELETE /api/media/bulk/delete

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

```typescript
interface BulkDeleteMediaRequestDTO {
  /**
   * Array of media file UUIDs to delete. Each UUID represents a media file that the user wants to del...
   * @validation NotBlank=message: 'UUIDs are required'
   * @validation Type='array'  message: 'UUIDs must be an array'
   * @validation Count=min: 1  max: 100  minMessage: 'At least one UUID is required'  maxMessage: 'Cannot delete more than 100 items at once'
   * @validation All=[Symfony\Component\Validator\Constraints\NotBlank  Symfony\Component\Validator\Constraints\Uuid]
   */
  uuids: string[];

}
```

**Example Request:**

```javascript
// Example API Request for DELETE /api/media/bulk/delete
async function exampleDeleteRequest() {
  const url = 'https://example.com/api/media/bulk/delete';
  const requestData = {
        "uuids": [
            1,
            2,
            3
        ]
    };

  try {
    const response = await fetch(url, {
      method: 'DELETE',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify(requestData)
    });

    if (!response.ok) {
      throw new Error('API error: ' + response.status);
    }

    const data = await response.json();
    console.log('Success:', data);
    return data;
  } catch (error) {
    console.error('Error:', error);
    throw error;
  }
}
```

#### Response

**Response Schema:**

```json
{
    "message": "example_string",
    "success": true,
    "timestamp": "example_string"
}
```

---

<!-- Route 4 -->
### POST /api/media/duplicate/{uuid}

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

```typescript
interface DuplicateMediaRequestDTO {
  /**
   * Custom name for the duplicated media file. If provided, this will be used as the name for the dup...
   * @validation Length=max: 255  maxMessage: 'Media name cannot be longer than {{ limit }} characters'
   */
  name?: string;

}
```

**Example Request:**

```javascript
// Example API Request for POST /api/media/duplicate/{uuid}
async function examplePostRequest() {
  const url = 'https://example.com/api/media/duplicate/{uuid}';
  const requestData = {
        "name": null
    };

  try {
    const response = await fetch(url, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify(requestData)
    });

    if (!response.ok) {
      throw new Error('API error: ' + response.status);
    }

    const data = await response.json();
    console.log('Success:', data);
    return data;
  } catch (error) {
    console.error('Error:', error);
    throw error;
  }
}
```

#### Response

**Response Schema:**

```json
{
    "media": null,
    "mediaList": null,
    "total": null,
    "page": null,
    "totalPages": null,
    "message": "example_string",
    "success": true,
    "timestamp": "example_string"
}
```

---

<!-- Route 5 -->
### GET /api/media/search

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

```typescript
interface SearchMediaRequestDTO {
  /**
   * Page number for pagination. Specifies which page of media results to return. Must be 1 or greater...
   * @validation Range=min: 1  notInRangeMessage: 'Page must be {{ min }} or greater'
   */
  page?: number;

  /**
   * Number of media items per page. Controls how many media files are returned per page. Limited to a...
   * @validation Range=min: 1  max: 50  notInRangeMessage: 'Limit must be between {{ min }} and {{ max }}'
   */
  limit?: number;

  /**
   * Media type filter. Filters media by type (image, video, or audio). When null, all media types are...
   * @validation Choice=choices: ['image'  'video'  'audio']  message: 'Type must be one of: image  video  audio'
   */
  type?: string;

  /**
   * Media source filter. Filters media by its source origin (upload for user uploads, or stock photo ...
   * @validation Choice=choices: ['upload'  'unsplash'  'pexels'  'pixabay']  message: 'Source must be one of: upload  unsplash  pexels  pixabay'
   */
  source?: string;

  /**
   * Search query term. Text to search for in media file names, descriptions, and metadata. When null,...
   * @validation Length=max: 255  maxMessage: 'Search query cannot be longer than {{ limit }} characters'
   */
  search?: string;

  /**
   * Comma-separated list of tags for filtering. Tags to filter media by. Multiple tags can be specifi...
   */
  tags?: string;

}
```

**Example Request:**

```javascript
// Example API Request for GET /api/media/search
async function exampleGetRequest() {
  const url = 'https://example.com/api/media/search';
  const requestData = {
        "page": 25,
        "limit": 20,
        "type": null,
        "source": null,
        "search": null,
        "tags": null
    };

  try {
    const response = await fetch(url, {
      method: 'GET',
      headers: {
        'Content-Type': 'application/json',
      },
    });

    if (!response.ok) {
      throw new Error('API error: ' + response.status);
    }

    const data = await response.json();
    console.log('Success:', data);
    return data;
  } catch (error) {
    console.error('Error:', error);
    throw error;
  }
}
```

#### Response

**Response Schema:**

```json
{
    "data": [
        "example_item"
    ],
    "page": 25,
    "limit": 20,
    "total": 42,
    "totalPages": 25,
    "message": "example_string"
}
```

---

<!-- Route 6 -->
### GET /api/media/stock/search

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

```typescript
interface StockSearchRequestDTO {
  /**
   * Search query for stock media. The search term to find relevant stock photos and videos from exter...
   * @validation NotBlank=message: 'Query is required for stock search'
   * @validation Length=min: 1  max: 255  minMessage: 'Query must be at least {{ limit }} character long'  maxMessage: 'Query cannot be longer than {{ limit }} characters'
   */
  query: string;

  /**
   * Page number for stock media results pagination. Specifies which page of stock media results to re...
   * @validation Range=min: 1  notInRangeMessage: 'Page must be {{ min }} or greater'
   */
  page?: number;

  /**
   * Number of stock media items per page. Controls how many stock media items are requested from the ...
   * @validation Range=min: 1  max: 50  notInRangeMessage: 'Limit must be between {{ min }} and {{ max }}'
   */
  limit?: number;

  /**
   * Type of stock media to search for. Specifies whether to search for images or videos from the stoc...
   * @validation Choice=choices: ['image'  'video']  message: 'Type must be one of: image  video'
   */
  type?: string;

  /**
   * Stock media provider source. Specifies which external stock media provider to search. Each provid...
   * @validation Choice=choices: ['unsplash'  'pexels'  'pixabay']  message: 'Source must be one of: unsplash  pexels  pixabay'
   */
  source?: string;

}
```

**Example Request:**

```javascript
// Example API Request for GET /api/media/stock/search
async function exampleGetRequest() {
  const url = 'https://example.com/api/media/stock/search';
  const requestData = {
        "query": "example_string",
        "page": 25,
        "limit": 20,
        "type": "example_type",
        "source": "example_string"
    };

  try {
    const response = await fetch(url, {
      method: 'GET',
      headers: {
        'Content-Type': 'application/json',
      },
    });

    if (!response.ok) {
      throw new Error('API error: ' + response.status);
    }

    const data = await response.json();
    console.log('Success:', data);
    return data;
  } catch (error) {
    console.error('Error:', error);
    throw error;
  }
}
```

#### Response

**Response Schema:**

```json
{
    "data": [
        "example_item"
    ],
    "page": 25,
    "limit": 20,
    "total": 42,
    "totalPages": 25,
    "message": "example_string"
}
```

---

<!-- Route 7 -->
### GET /api/media/{uuid}

Get details of a specific media file

Returns detailed information about a single media file including metadata.
Only allows access to media files owned by the authenticated user.

#### Parameters

- **uuid** (string)

#### Response

**Response Schema:**

```json
{
    "media": null,
    "mediaList": null,
    "total": null,
    "page": null,
    "totalPages": null,
    "message": "example_string",
    "success": true,
    "timestamp": "example_string"
}
```

---

<!-- Route 8 -->
### PUT /api/media/{uuid}

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

```typescript
interface UpdateMediaRequestDTO {
  /**
   * Updated display name for the media item. If provided, replaces the current media name. Must be be...
   * @validation Length=min: 1  max: 255  minMessage: 'File name must be at least 1 character'  maxMessage: 'File name cannot exceed 255 characters'
   */
  name?: string;

  /**
   * Updated description for the media item. If provided, replaces the current description. Maximum 10...
   * @validation Length=max: 1000  maxMessage: 'Description cannot exceed 1000 characters'
   */
  description?: string;

  /**
   * Updated organizational tags for the media. If provided, replaces the current tag set. Each tag mu...
   * @validation Valid
   */
  tags?: Tag[] | null;

  /**
   * Updated technical metadata for the media file. If provided, replaces or merges with current metad...
   * @validation Valid
   */
  metadata?: {
    fileSize: number;
    mimeType: string;
    width?: number | null;
    height?: number | null;
    duration?: number | null;
    bitrate?: number | null;
    sampleRate?: number | null;
    channels?: number | null;
    colorSpace?: string | null;
    hasTransparency?: boolean | null;
    frameRate?: number | null;
    codec?: string | null;
    aspectRatio?: number | null;
  };

  /**
   * Updated premium status for the media. If provided, changes whether the media requires a premium s...
   * @validation Type='bool'  message: 'Is premium must be a boolean'
   */
  isPremium?: boolean;

  /**
   * Updated active status for the media. If provided, changes whether the media is currently availabl...
   * @validation Type='bool'  message: 'Is active must be a boolean'
   */
  isActive?: boolean;

  /**
   * Updated public visibility for the media. If provided, changes whether the media is publicly acces...
   * @validation Type='bool'  message: 'Is public must be a boolean'
   */
  isPublic?: boolean;

}
```

**Example Request:**

```javascript
// Example API Request for PUT /api/media/{uuid}
async function examplePutRequest() {
  const url = 'https://example.com/api/media/{uuid}';
  const requestData = {
        "name": null,
        "description": null,
        "tags": null,
        "metadata": null,
        "isPremium": null,
        "isActive": null,
        "isPublic": null
    };

  try {
    const response = await fetch(url, {
      method: 'PUT',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify(requestData)
    });

    if (!response.ok) {
      throw new Error('API error: ' + response.status);
    }

    const data = await response.json();
    console.log('Success:', data);
    return data;
  } catch (error) {
    console.error('Error:', error);
    throw error;
  }
}
```

#### Response

**Response Schema:**

```json
{
    "media": null,
    "mediaList": null,
    "total": null,
    "page": null,
    "totalPages": null,
    "message": "example_string",
    "success": true,
    "timestamp": "example_string"
}
```

---

<!-- Route 9 -->
### DELETE /api/media/{uuid}

Delete a media file

Permanently removes a media file record from the database.
This operation also triggers cleanup of associated file storage.
Users can only delete media files they own for security.

#### Parameters

- **uuid** (string)

#### Response

**Response Schema:**

```json
{
    "message": "example_string",
    "success": true,
    "timestamp": "example_string"
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
### GET /api/plugins

Retrieve paginated list of plugins with filtering options

Supports filtering by category, search terms, status, and sorting.
Returns paginated results with plugin metadata.

---

<!-- Route 2 -->
### POST /api/plugins

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

```typescript
interface CreatePluginRequestDTO {
  /**
   * Display name of the plugin. Human-readable name shown in the plugin marketplace and installation ...
   * @validation NotBlank=message: 'Plugin name is required'
   * @validation Length=min: 2  max: 100  minMessage: 'Plugin name must be at least 2 characters long'  maxMessage: 'Plugin name cannot exceed 100 characters'
   */
  name: string;

  /**
   * Detailed description of the plugin's functionality. Comprehensive explanation of what the plugin ...
   * @validation NotBlank=message: 'Plugin description is required'
   * @validation Length=min: 10  max: 1000  minMessage: 'Plugin description must be at least 10 characters long'  maxMessage: 'Plugin description cannot exceed 1000 characters'
   */
  description: string;

  /**
   * Categories that classify the plugin's functionality. Array of category names that help users disc...
   * @validation NotBlank=message: 'Categories are required'
   * @validation Count=min: 1  max: 5  minMessage: 'At least one category is required'  maxMessage: 'Cannot exceed 5 categories'
   * @validation All=[Symfony\Component\Validator\Constraints\NotBlank  Symfony\Component\Validator\Constraints\Length]
   */
  categories: string[];

  /**
   * Semantic version number of the plugin. Version identifier following semantic versioning (semver) ...
   * @validation NotBlank=message: 'Plugin version is required'
   * @validation Regex=pattern: '/^\d+\.\d+\.\d+=-[a-zA-Z0-9-]+?$/'  message: 'Version must follow semantic versioning =e.g.  1.0.0'
   */
  version: string;

  /**
   * Required permissions for the plugin to function. Array of permission types that the plugin needs ...
   * @validation NotBlank=message: 'Permissions are required'
   * @validation Count=min: 1  minMessage: 'At least one permission is required'
   * @validation All=[Symfony\Component\Validator\Constraints\Choice]
   */
  permissions: string[];

  /**
   * Plugin manifest configuration. JSON-formatted configuration containing plugin metadata, entry poi...
   * @validation NotBlank=message: 'Plugin manifest is required'
   * @validation Type=type: 'array'  message: 'Manifest must be a valid array'
   */
  manifest: Record<string, any>;

}
```

**Example Request:**

```javascript
// Example API Request for POST /api/plugins
async function examplePostRequest() {
  const url = 'https://example.com/api/plugins';
  const requestData = {
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
    };

  try {
    const response = await fetch(url, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Authorization': 'Bearer YOUR_JWT_TOKEN', // This endpoint requires authentication
      },
      body: JSON.stringify(requestData)
    });

    if (!response.ok) {
      throw new Error('API error: ' + response.status);
    }

    const data = await response.json();
    console.log('Success:', data);
    return data;
  } catch (error) {
    console.error('Error:', error);
    throw error;
  }
}
```

---

<!-- Route 3 -->
### GET /api/plugins/categories

Get available plugin categories

Returns a list of all available plugin categories for filtering
and classification purposes.

---

<!-- Route 4 -->
### GET /api/plugins/my-plugins

**Security:** IsGranted

Get current user's plugins

Returns paginated list of plugins created by the authenticated user,
including all statuses (pending, approved, rejected).

---

<!-- Route 5 -->
### GET /api/plugins/{id}

Retrieve detailed information about a specific plugin

Returns comprehensive plugin details including manifest, permissions,
and review information. Access is restricted based on plugin status and user role.

---

<!-- Route 6 -->
### PUT /api/plugins/{id}

**Security:** IsGranted

Update an existing plugin

Updates plugin metadata. Only the plugin developer or admin can perform updates.
Admin users can also modify the plugin status.

#### Request Body

**UpdatePluginRequestDTO**

Data Transfer Object for plugin update requests.

Handles partial updates to existing plugins in the platform's
plugin system. All fields are optional and null values indicate
no change should be made. Used for both plugin developer updates
and administrative status changes.

```typescript
interface UpdatePluginRequestDTO {
  /**
   * Updated display name of the plugin. If provided, replaces the current plugin name. Must be descri...
   * @validation Length=min: 2  max: 100  minMessage: 'Plugin name must be at least 2 characters long'  maxMessage: 'Plugin name cannot exceed 100 characters'
   */
  name?: string;

  /**
   * Updated description of the plugin's functionality. If provided, replaces the current description....
   * @validation Length=min: 10  max: 1000  minMessage: 'Plugin description must be at least 10 characters long'  maxMessage: 'Plugin description cannot exceed 1000 characters'
   */
  description?: string;

  /**
   * Updated categories for plugin classification. If provided, replaces the current category set. Cat...
   * @validation Count=min: 1  max: 5  minMessage: 'At least one category is required'  maxMessage: 'Cannot exceed 5 categories'
   * @validation All=[Symfony\Component\Validator\Constraints\NotBlank  Symfony\Component\Validator\Constraints\Length]
   */
  categories?: string[] | null;

  /**
   * Updated semantic version number. If provided, updates the plugin version. Must follow semantic ve...
   * @validation Regex=pattern: '/^\d+\.\d+\.\d+=-[a-zA-Z0-9-]+?$/'  message: 'Version must follow semantic versioning =e.g.  1.0.0'
   */
  version?: string;

  /**
   * Updated permission requirements. If provided, replaces the current permission set. Each permissio...
   * @validation All=[Symfony\Component\Validator\Constraints\Choice]
   */
  permissions?: string[] | null;

  /**
   * Updated plugin manifest configuration. If provided, replaces the current manifest. Contains plugi...
   * @validation Type=type: 'array'  message: 'Manifest must be a valid array'
   */
  manifest?: Record<string, any>;

  /**
   * Updated approval status for the plugin. If provided, changes the plugin's approval status in the ...
   * @validation Choice=choices: ['pending'  'approved'  'rejected']  message: 'Invalid status. Must be pending  approved  or rejected'
   */
  status?: string;

}
```

**Example Request:**

```javascript
// Example API Request for PUT /api/plugins/{id}
async function examplePutRequest() {
  const url = 'https://example.com/api/plugins/{id}';
  const requestData = {
        "name": null,
        "description": null,
        "categories": null,
        "version": null,
        "permissions": null,
        "manifest": null,
        "status": null
    };

  try {
    const response = await fetch(url, {
      method: 'PUT',
      headers: {
        'Content-Type': 'application/json',
        'Authorization': 'Bearer YOUR_JWT_TOKEN', // This endpoint requires authentication
      },
      body: JSON.stringify(requestData)
    });

    if (!response.ok) {
      throw new Error('API error: ' + response.status);
    }

    const data = await response.json();
    console.log('Success:', data);
    return data;
  } catch (error) {
    console.error('Error:', error);
    throw error;
  }
}
```

---

<!-- Route 7 -->
### DELETE /api/plugins/{id}

**Security:** IsGranted

Delete a plugin

Permanently removes a plugin from the system. Only the plugin developer
or administrators can delete plugins. This action is irreversible.

---

<!-- Route 8 -->
### POST /api/plugins/{id}/approve

**Security:** IsGranted

Approve a plugin (Admin only)

Changes plugin status to approved, making it available in the marketplace.
Records approval timestamp and reviewing administrator.

---

<!-- Route 9 -->
### POST /api/plugins/{id}/install

**Security:** IsGranted

Install a plugin for the current user

Installs an approved plugin for the authenticated user. Increments the
installation count and handles plugin registration logic.

---

<!-- Route 10 -->
### POST /api/plugins/{id}/reject

**Security:** IsGranted

Reject a plugin (Admin only)

Changes plugin status to rejected with a provided reason.
Records rejection timestamp and reviewing admin.

#### Request Body

**RejectPluginRequestDTO**

```typescript
interface RejectPluginRequestDTO {
  reason: string;

}
```

**Example Request:**

```javascript
// Example API Request for POST /api/plugins/{id}/reject
async function examplePostRequest() {
  const url = 'https://example.com/api/plugins/{id}/reject';
  const requestData = {
        "reason": "example_string"
    };

  try {
    const response = await fetch(url, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Authorization': 'Bearer YOUR_JWT_TOKEN', // This endpoint requires authentication
      },
      body: JSON.stringify(requestData)
    });

    if (!response.ok) {
      throw new Error('API error: ' + response.status);
    }

    const data = await response.json();
    console.log('Success:', data);
    return data;
  } catch (error) {
    console.error('Error:', error);
    throw error;
  }
}
```

---

<!-- Route 11 -->
### POST /api/plugins/{id}/uninstall

**Security:** IsGranted

Uninstall a plugin for the current user

Removes a plugin from the user's installed plugins and cleans up
associated data and files.

---

<!-- Route 12 -->
### POST /api/plugins/{id}/upload-file

**Security:** IsGranted

Upload a plugin file

Allows plugin developers to upload plugin files (ZIP format).
Performs validation and stores the file securely.

#### Request Body

**UploadPluginFileRequestDTO**

Data Transfer Object for plugin file upload requests.

Handles validation and encapsulation of plugin file upload data including
file type validation, size constraints, and security checks for plugin files.

```typescript
interface UploadPluginFileRequestDTO {
  /**
   * Data Transfer Object for plugin file upload requests. Handles validation and encapsulation of plu...
   * @validation NotNull=message: 'Plugin file is required'
   * @validation File=maxSize: '50M'  maxSizeMessage: 'Plugin file size cannot exceed 50MB'  mimeTypes: ['application/zip'  'application/x-zip-compressed'  'application/x-zip']  mimeTypesMessage: 'Plugin file must be a valid ZIP archive'
   */
  file?: UploadedFile;

}
```

**Example Request:**

```javascript
// Example API Request for POST /api/plugins/{id}/upload-file
async function examplePostRequest() {
  const url = 'https://example.com/api/plugins/{id}/upload-file';
  const requestData = {
        "file": null
    };

  try {
    const response = await fetch(url, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Authorization': 'Bearer YOUR_JWT_TOKEN', // This endpoint requires authentication
      },
      body: JSON.stringify(requestData)
    });

    if (!response.ok) {
      throw new Error('API error: ' + response.status);
    }

    const data = await response.json();
    console.log('Success:', data);
    return data;
  } catch (error) {
    console.error('Error:', error);
    throw error;
  }
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
### GET /api/projects

List projects for authenticated user

Returns a paginated list of projects belonging to the authenticated user.
Supports filtering by status, sorting by various fields, and search functionality.

#### Response

**Response Schema:**

```json
{
    "data": [
        "example_item"
    ],
    "page": 25,
    "limit": 20,
    "total": 42,
    "totalPages": 25,
    "message": "example_string"
}
```

---

<!-- Route 2 -->
### POST /api/projects

Create a new project

Creates a new project with the provided information and associates it with the authenticated user.
Validates project data and sets default values for optional fields.

#### Request Body

**CreateProjectRequestDTO**

Request DTO for creating a new design project.

This DTO handles the creation of new projects with all necessary
configuration options, including canvas settings, metadata, and
organizational tags.

```typescript
interface CreateProjectRequestDTO {
  /**
   * The display name of the project. This name is used throughout the application interface and shoul...
   * @validation NotBlank=message: 'Project name is required'
   * @validation Length=min: 1  max: 255  minMessage: 'Project name must be at least 1 character'  maxMessage: 'Project name cannot exceed 255 characters'
   */
  name: string;

  /**
   * Optional description providing additional context about the project. Used to document the project...
   * @validation Length=max: 1000  maxMessage: 'Description cannot exceed 1000 characters'
   */
  description: string;

  /**
   * Whether the project should be publicly accessible. Public projects can be viewed by other users a...
   * @validation Type='bool'  message: 'Is public must be a boolean'
   */
  isPublic: boolean;

  /**
   * Project configuration settings including canvas dimensions, DPI, etc. Contains all technical sett...
   * @validation Valid
   */
  settings: {
    canvasWidth?: number;
    canvasHeight?: number;
    backgroundColor?: string;
    orientation?: string;
    units?: string;
    dpi?: number;
    gridVisible?: boolean;
    rulersVisible?: boolean;
    guidesVisible?: boolean;
    snapToGrid?: boolean;
    snapToGuides?: boolean;
    snapToObjects?: boolean;
  };

  /**
   * Organizational tags for categorizing and searching projects. Tags help users organize their proje...
   * @validation Valid
   */
  tags?: Tag[];

  /**
   * Optional URL to a thumbnail image representing the project. Used for project previews in lists an...
   * @validation Url=message: 'Thumbnail must be a valid URL'
   */
  thumbnail?: string;

}
```

**Example Request:**

```javascript
// Example API Request for POST /api/projects
async function examplePostRequest() {
  const url = 'https://example.com/api/projects';
  const requestData = {
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
    };

  try {
    const response = await fetch(url, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify(requestData)
    });

    if (!response.ok) {
      throw new Error('API error: ' + response.status);
    }

    const data = await response.json();
    console.log('Success:', data);
    return data;
  } catch (error) {
    console.error('Error:', error);
    throw error;
  }
}
```

#### Response

**Response Schema:**

```json
{
    "project": null,
    "projects": null,
    "total": null,
    "page": null,
    "totalPages": null,
    "message": "example_string",
    "success": true,
    "timestamp": "example_string"
}
```

---

<!-- Route 3 -->
### GET /api/projects/public

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

```typescript
interface SearchProjectsRequestDTO {
  /**
   * Page number for pagination. Specifies which page of project results to return. Must be 1 or great...
   * @validation Range=min: 1  notInRangeMessage: 'Page must be {{ min }} or greater'
   */
  page?: number;

  /**
   * Number of projects per page. Controls how many projects are returned per page. Limited to a maxim...
   * @validation Range=min: 1  max: 50  notInRangeMessage: 'Limit must be between {{ min }} and {{ max }}'
   */
  limit?: number;

  /**
   * Search query for project names and descriptions. Text to search for in project names, description...
   * @validation Length=max: 255  maxMessage: 'Search query cannot be longer than {{ limit }} characters'
   */
  search?: string;

  /**
   * Comma-separated list of tags for filtering. Tags to filter projects by. Multiple tags can be spec...
   */
  tags?: string;

}
```

**Example Request:**

```javascript
// Example API Request for GET /api/projects/public
async function exampleGetRequest() {
  const url = 'https://example.com/api/projects/public';
  const requestData = {
        "page": 25,
        "limit": 20,
        "search": null,
        "tags": null
    };

  try {
    const response = await fetch(url, {
      method: 'GET',
      headers: {
        'Content-Type': 'application/json',
      },
    });

    if (!response.ok) {
      throw new Error('API error: ' + response.status);
    }

    const data = await response.json();
    console.log('Success:', data);
    return data;
  } catch (error) {
    console.error('Error:', error);
    throw error;
  }
}
```

#### Response

**Response Schema:**

```json
{
    "data": [
        "example_item"
    ],
    "page": 25,
    "limit": 20,
    "total": 42,
    "totalPages": 25,
    "message": "example_string"
}
```

---

<!-- Route 4 -->
### GET /api/projects/{id}

Get details of a specific project

Returns detailed information about a single project.
Only allows access to projects owned by the authenticated user or public projects.

#### Parameters

- **id** (int)

#### Response

**Response Schema:**

```json
{
    "project": null,
    "projects": null,
    "total": null,
    "page": null,
    "totalPages": null,
    "message": "example_string",
    "success": true,
    "timestamp": "example_string"
}
```

---

<!-- Route 5 -->
### PUT /api/projects/{id}

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

```typescript
interface UpdateProjectRequestDTO {
  /**
   * Updated display name for the project. If provided, replaces the current project name. Must be bet...
   * @validation Length=min: 1  max: 255  minMessage: 'Project name must be at least 1 character'  maxMessage: 'Project name cannot exceed 255 characters'
   */
  name?: string;

  /**
   * Updated description for the project. If provided, replaces the current description. Maximum 1000 ...
   * @validation Length=max: 1000  maxMessage: 'Description cannot exceed 1000 characters'
   */
  description?: string;

  /**
   * Updated visibility setting for the project. If provided, changes whether the project is publicly ...
   * @validation Type='bool'  message: 'Is public must be a boolean'
   */
  isPublic?: boolean;

  /**
   * Updated project configuration settings. If provided, replaces or merges with current project sett...
   * @validation Valid
   */
  settings?: {
    canvasWidth?: number;
    canvasHeight?: number;
    backgroundColor?: string;
    orientation?: string;
    units?: string;
    dpi?: number;
    gridVisible?: boolean;
    rulersVisible?: boolean;
    guidesVisible?: boolean;
    snapToGrid?: boolean;
    snapToGuides?: boolean;
    snapToObjects?: boolean;
  };

  /**
   * Updated organizational tags for the project. If provided, replaces the current tag set. Each tag ...
   * @validation Valid
   */
  tags?: Tag[] | null;

  /**
   * Updated thumbnail URL for the project. If provided, replaces the current thumbnail. Should be a v...
   * @validation Url=message: 'Thumbnail must be a valid URL'
   */
  thumbnail?: string;

}
```

**Example Request:**

```javascript
// Example API Request for PUT /api/projects/{id}
async function examplePutRequest() {
  const url = 'https://example.com/api/projects/{id}';
  const requestData = {
        "name": null,
        "description": null,
        "isPublic": null,
        "settings": null,
        "tags": null,
        "thumbnail": null
    };

  try {
    const response = await fetch(url, {
      method: 'PUT',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify(requestData)
    });

    if (!response.ok) {
      throw new Error('API error: ' + response.status);
    }

    const data = await response.json();
    console.log('Success:', data);
    return data;
  } catch (error) {
    console.error('Error:', error);
    throw error;
  }
}
```

#### Response

**Response Schema:**

```json
{
    "project": null,
    "projects": null,
    "total": null,
    "page": null,
    "totalPages": null,
    "message": "example_string",
    "success": true,
    "timestamp": "example_string"
}
```

---

<!-- Route 6 -->
### DELETE /api/projects/{id}

Delete a project

Permanently deletes a project and all its associated data (designs, media files, etc.).
Only allows deletion of projects owned by the authenticated user.
This action cannot be undone.

#### Parameters

- **id** (int)

#### Response

**Response Schema:**

```json
{
    "message": "example_string",
    "success": true,
    "timestamp": "example_string"
}
```

---

<!-- Route 7 -->
### POST /api/projects/{id}/duplicate

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

```typescript
interface DuplicateProjectRequestDTO {
  /**
   * Display name for the duplicated project. The name given to the new project copy. Must be between ...
   * @validation NotBlank=message: 'Project name is required'
   * @validation Length=min: 1  max: 255  minMessage: 'Project name must be at least {{ limit }} characters long'  maxMessage: 'Project name cannot be longer than {{ limit }} characters'
   */
  name?: string;

}
```

**Example Request:**

```javascript
// Example API Request for POST /api/projects/{id}/duplicate
async function examplePostRequest() {
  const url = 'https://example.com/api/projects/{id}/duplicate';
  const requestData = {
        "name": null
    };

  try {
    const response = await fetch(url, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify(requestData)
    });

    if (!response.ok) {
      throw new Error('API error: ' + response.status);
    }

    const data = await response.json();
    console.log('Success:', data);
    return data;
  } catch (error) {
    console.error('Error:', error);
    throw error;
  }
}
```

#### Response

**Response Schema:**

```json
{
    "project": null,
    "projects": null,
    "total": null,
    "page": null,
    "totalPages": null,
    "message": "example_string",
    "success": true,
    "timestamp": "example_string"
}
```

---

<!-- Route 8 -->
### POST /api/projects/{id}/share

Toggle project sharing status

Toggles the public/private status of a project.
Only allows modification of projects owned by the authenticated user.
Updates the project's visibility and sharing settings.

#### Parameters

- **id** (int)

#### Response

**Response Schema:**

```json
{
    "project": null,
    "projects": null,
    "total": null,
    "page": null,
    "totalPages": null,
    "message": "example_string",
    "success": true,
    "timestamp": "example_string"
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
### GET /api/templates

List available templates with filtering and pagination

Returns a paginated list of templates with optional category filtering.
Includes template metadata, thumbnail images, and usage statistics.
Both public templates and user-created templates are included in results.

#### Response

**Response Schema:**

```json
{
    "template": null,
    "templates": null,
    "total": null,
    "page": null,
    "totalPages": null,
    "message": "example_string",
    "success": true,
    "timestamp": "example_string"
}
```

---

<!-- Route 2 -->
### POST /api/templates

**Security:** IsGranted

Create a new template

Creates a new template from user design with metadata and canvas configuration.
Templates can be made public for marketplace or kept private for personal use.
Requires authentication and validates all template data before creation.

#### Request Body

**CreateTemplateRequestDTO**

```typescript
interface CreateTemplateRequestDTO {
  /**
   * The template name (required, max 255 characters) Used for identification and search purposes
   * @validation NotBlank=message: 'Template name is required'
   * @validation Length=max: 255  maxMessage: 'Template name cannot be longer than 255 characters'
   */
  name: string;

  /**
   * Optional description of the template (max 1000 characters) Provides context about the template's ...
   * @validation Length=max: 1000  maxMessage: 'Description cannot be longer than 1000 characters'
   */
  description: string;

  /**
   * Template category for organization and filtering Must be one of the predefined categories
   * @validation NotBlank=message: 'Category is required'
   * @validation Choice=choices: ['social-media'  'presentation'  'print'  'marketing'  'document'  'logo'  'web-graphics'  'video'  'animation']  message: 'Invalid category'
   */
  category: string;

  /**
   * Array of tags for categorization and search Each tag must be 1-50 characters and contain only alp...
   * @validation Type=type: 'array'  message: 'Tags must be an array'
   * @validation Valid
   */
  tags: Tag[];

  /**
   * Canvas width in pixels (required, must be positive) Defines the template's design area width
   * @validation NotBlank=message: 'Width is required'
   * @validation Type=type: 'integer'  message: 'Width must be an integer'
   * @validation Positive=message: 'Width must be positive'
   */
  width: number;

  /**
   * Canvas height in pixels (required, must be positive) Defines the template's design area height
   * @validation NotBlank=message: 'Height is required'
   * @validation Type=type: 'integer'  message: 'Height must be an integer'
   * @validation Positive=message: 'Height must be positive'
   */
  height: number;

  /**
   * Canvas configuration settings as key-value pairs Contains background color, grid settings, guides...
   * @validation Type=type: 'array'  message: 'Canvas settings must be an array'
   */
  canvasSettings?: any[];

  /**
   * Layer definitions for the template Contains the visual elements that make up the template
   * @validation Type=type: 'array'  message: 'Layers must be an array'
   */
  layers?: any[];

  /**
   * Optional URL to template thumbnail image Used for preview in template galleries
   * @validation Url=message: 'Thumbnail URL must be a valid URL'
   */
  thumbnailUrl?: string;

  /**
   * Optional URL to template preview image Used for larger preview displays
   * @validation Url=message: 'Preview URL must be a valid URL'
   */
  previewUrl?: string;

  /**
   * Whether this template requires premium access Premium templates are only available to paid users
   * @validation Type=type: 'bool'  message: 'isPremium must be a boolean'
   */
  isPremium?: boolean;

  /**
   * Whether this template is active and visible Inactive templates are hidden from users
   * @validation Type=type: 'bool'  message: 'isActive must be a boolean'
   */
  isActive?: boolean;

}
```

**Example Request:**

```javascript
// Example API Request for POST /api/templates
async function examplePostRequest() {
  const url = 'https://example.com/api/templates';
  const requestData = {
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
    };

  try {
    const response = await fetch(url, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Authorization': 'Bearer YOUR_JWT_TOKEN', // This endpoint requires authentication
      },
      body: JSON.stringify(requestData)
    });

    if (!response.ok) {
      throw new Error('API error: ' + response.status);
    }

    const data = await response.json();
    console.log('Success:', data);
    return data;
  } catch (error) {
    console.error('Error:', error);
    throw error;
  }
}
```

#### Response

**Response Schema:**

```json
{
    "template": null,
    "templates": null,
    "total": null,
    "page": null,
    "totalPages": null,
    "message": "example_string",
    "success": true,
    "timestamp": "example_string"
}
```

---

<!-- Route 3 -->
### GET /api/templates/categories

Get available template categories

Returns a list of all available template categories for filtering
and organization purposes. Categories help users find relevant templates
for their specific design needs and use cases.

#### Response

**Response Schema:**

```json
{
    "message": "example_string",
    "success": true,
    "timestamp": "example_string"
}
```

---

<!-- Route 4 -->
### GET /api/templates/search

Search templates with advanced filtering

Performs comprehensive template search with support for text queries,
category filtering, and tag-based search. Returns paginated results
sorted by relevance and usage popularity.

#### Request Body

**SearchTemplateRequestDTO**

```typescript
interface SearchTemplateRequestDTO {
  q?: string;

  category?: string;

  page?: number;

  limit?: number;

}
```

**Example Request:**

```javascript
// Example API Request for GET /api/templates/search
async function exampleGetRequest() {
  const url = 'https://example.com/api/templates/search';
  const requestData = {
        "q": null,
        "category": null,
        "page": 25,
        "limit": 20
    };

  try {
    const response = await fetch(url, {
      method: 'GET',
      headers: {
        'Content-Type': 'application/json',
      },
    });

    if (!response.ok) {
      throw new Error('API error: ' + response.status);
    }

    const data = await response.json();
    console.log('Success:', data);
    return data;
  } catch (error) {
    console.error('Error:', error);
    throw error;
  }
}
```

#### Response

**Response Schema:**

```json
{
    "templates": [
        "example_item"
    ],
    "page": 25,
    "limit": 20,
    "total": 42,
    "totalPages": 25,
    "message": "example_string"
}
```

---

<!-- Route 5 -->
### GET /api/templates/{uuid}

Get details of a specific template

Returns comprehensive template information including design data, metadata,
and usage statistics. Automatically increments the view count for analytics.
Only returns active templates that are publicly available.

#### Parameters

- **uuid** (string)

#### Response

**Response Schema:**

```json
{
    "template": null,
    "templates": null,
    "total": null,
    "page": null,
    "totalPages": null,
    "message": "example_string",
    "success": true,
    "timestamp": "example_string"
}
```

---

<!-- Route 6 -->
### POST /api/templates/{uuid}/use

**Security:** IsGranted

Use a template to create a new design

Creates a new design project based on the specified template.
Copies all template layers, settings, and properties to the new design.
Automatically increments the template usage count for analytics.

#### Parameters

- **uuid** (string)

#### Response

**Response Schema:**

```json
{
    "design": null,
    "designs": null,
    "total": null,
    "page": null,
    "totalPages": null,
    "message": "example_string",
    "success": true,
    "timestamp": "example_string"
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
### POST /api/user/avatar

Upload and update user avatar image

Handles avatar file upload, validation, and updates user profile.
Automatically removes old avatar file and returns new avatar URL.

#### Request Body

**UploadAvatarRequestDTO**

Data Transfer Object for avatar upload requests.

Handles validation and encapsulation of avatar file upload data including
file type validation, size constraints, and security checks.

```typescript
interface UploadAvatarRequestDTO {
  /**
   * Data Transfer Object for avatar upload requests. Handles validation and encapsulation of avatar f...
   * @validation NotNull=message: 'Avatar file is required'
   * @validation File=maxSize: '5M'  maxSizeMessage: 'Avatar file size cannot exceed 5MB'  mimeTypes: ['image/jpeg'  'image/png'  'image/gif'  'image/webp']  mimeTypesMessage: 'Avatar must be a valid image file =JPEG  PNG  GIF  or WebP'
   */
  avatar?: UploadedFile;

}
```

**Example Request:**

```javascript
// Example API Request for POST /api/user/avatar
async function examplePostRequest() {
  const url = 'https://example.com/api/user/avatar';
  const requestData = {
        "avatar": null
    };

  try {
    const response = await fetch(url, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify(requestData)
    });

    if (!response.ok) {
      throw new Error('API error: ' + response.status);
    }

    const data = await response.json();
    console.log('Success:', data);
    return data;
  } catch (error) {
    console.error('Error:', error);
    throw error;
  }
}
```

---

<!-- Route 2 -->
### PUT /api/user/password

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

```typescript
interface ChangePasswordRequestDTO {
  /**
   * User's current password for verification. Required to verify the user's identity before allowing ...
   */
  currentPassword: string;

  /**
   * New password that will replace the current one. Must meet security requirements: minimum 8 charac...
   */
  newPassword: string;

  /**
   * Confirmation of the new password. Must exactly match the newPassword field to prevent accidental ...
   */
  confirmPassword: string;

}
```

**Example Request:**

```javascript
// Example API Request for PUT /api/user/password
async function examplePutRequest() {
  const url = 'https://example.com/api/user/password';
  const requestData = {
        "currentPassword": "secure_password123",
        "newPassword": "secure_password123",
        "confirmPassword": "secure_password123"
    };

  try {
    const response = await fetch(url, {
      method: 'PUT',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify(requestData)
    });

    if (!response.ok) {
      throw new Error('API error: ' + response.status);
    }

    const data = await response.json();
    console.log('Success:', data);
    return data;
  } catch (error) {
    console.error('Error:', error);
    throw error;
  }
}
```

---

<!-- Route 3 -->
### GET /api/user/profile

Get current user's profile information

Returns comprehensive profile data including personal information,
settings, and account details for the authenticated user.

---

<!-- Route 4 -->
### PUT /api/user/profile

Update current user's profile information

Updates user profile data including personal information, professional details,
and account preferences. Uses comprehensive validation and returns updated profile.

#### Request Body

**UpdateProfileRequestDTO**

Data Transfer Object for comprehensive profile update requests



```typescript
interface UpdateProfileRequestDTO {
  firstName: string;

  lastName: string;

  username: string;

  email: string;

  jobTitle: string;

  company: string;

  website: string;

  portfolio: string;

  bio: string;

  socialLinks: any[];

  timezone: string;

  language: string;

}
```

**Example Request:**

```javascript
// Example API Request for PUT /api/user/profile
async function examplePutRequest() {
  const url = 'https://example.com/api/user/profile';
  const requestData = {
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
    };

  try {
    const response = await fetch(url, {
      method: 'PUT',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify(requestData)
    });

    if (!response.ok) {
      throw new Error('API error: ' + response.status);
    }

    const data = await response.json();
    console.log('Success:', data);
    return data;
  } catch (error) {
    console.error('Error:', error);
    throw error;
  }
}
```

---

<!-- Route 5 -->
### DELETE /api/user/settings/privacy/delete

Delete user account and all associated data

Initiates account deletion process which removes all user data,
content, and associated resources permanently.

---

<!-- Route 6 -->
### POST /api/user/settings/privacy/download

Request user data download for GDPR compliance

Initiates a background process to prepare comprehensive data export
for the user, including all personal data and content.

---

<!-- Route 7 -->
### POST /api/user/settings/privacy/export

Export user data in portable format

Generates and returns comprehensive user data export including
all user content, settings, and account information.

---

<!-- Route 8 -->
### GET /api/user/subscription

Get user subscription information

Returns current subscription details including plan type, billing status,
usage limits, and subscription features for the authenticated user.

---


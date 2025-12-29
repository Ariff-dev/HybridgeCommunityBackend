# Hybridge Community Backend - API Documentation

**Version:** 2.0  
**Last Updated:** 2025-12-28  
**Authentication:** JWT (JSON Web Tokens)

---

## 📋 Table of Contents

1. [Base Information](#base-information)
2. [Authentication](#authentication)
3. [Authentication Endpoints](#authentication-endpoints)
4. [User Endpoints](#user-endpoints)
5. [Board Endpoints](#board-endpoints)
6. [Blog Endpoints](#blog-endpoints)
7. [Error Responses](#error-responses)
8. [Authentication Flow](#authentication-flow)

---

## Base Information

### Base URL

**When running with Docker (Recommended):**
```
http://localhost:8001/api
```

**When running PHP natively (without Docker):**
```
http://localhost:8000/api
```

### Authentication Method
All protected endpoints use **JWT (JSON Web Tokens)** authentication.

**Header Format:**
```http
Authorization: Bearer <access_token>
```

### Content Type
All requests and responses use `application/json`.

### Token Expiration
- **Access Token:** 15 minutes (900 seconds)
- **Refresh Token:** 7 days (604,800 seconds)

---

## Authentication

### Public Endpoints (No Auth Required)
- `POST /api/auth/register` - Register new user
- `POST /api/auth/login` - Login
- `POST /api/auth/refresh` - Refresh access token
- `GET /api/blog/posts` - List published posts
- `GET /api/blog/posts/:id` - View single post

### Protected Endpoints (JWT Required)
All other endpoints require a valid JWT token in the `Authorization` header.

---

## Authentication Endpoints

### 1. Register New User

**Endpoint:** `POST /api/auth/register`

**Description:** Create a new user account

**Request Body:**
```json
{
  "name": "John Doe",
  "email": "john@example.com",
  "password": "SecurePass123"
}
```

**Validation Rules:**
- `name`: Required, string
- `email`: Required, valid email format
- `password`: Required, minimum 6 characters

**Response 201 (Created):**
```json
{
  "success": true,
  "message": "User registered successfully",
  "data": {
    "user_id": "550e8400-e29b-41d4-a716-446655440000",
    "email": "john@example.com",
    "name": "John Doe"
  }
}
```

**Error 400 (Bad Request):**
```json
{
  "error": true,
  "message": "Email and password are required"
}
```

**Error 409 (Conflict):**
```json
{
  "error": true,
  "message": "Email already registered"
}
```

---

### 2. Login

**Endpoint:** `POST /api/auth/login`

**Description:** Authenticate user and receive JWT tokens

**Request Body:**
```json
{
  "email": "john@example.com",
  "password": "SecurePass123"
}
```

**Response 200 (OK):**
```json
{
  "success": true,
  "message": "Login successful",
  "data": {
    "access_token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
    "refresh_token": "a1b2c3d4e5f6...",
    "token_type": "Bearer",
    "expires_in": 900,
    "user": {
      "id": "550e8400-e29b-41d4-a716-446655440000",
      "name": "John Doe",
      "email": "john@example.com"
    }
  }
}
```

**Error 400 (Bad Request):**
```json
{
  "error": true,
  "message": "Email and password are required"
}
```

**Error 401 (Unauthorized):**
```json
{
  "error": true,
  "message": "Invalid credentials"
}
```

---

### 3. Refresh Access Token

**Endpoint:** `POST /api/auth/refresh`

**Description:** Get a new access token using refresh token

**Request Body:**
```json
{
  "refresh_token": "a1b2c3d4e5f6..."
}
```

**Response 200 (OK):**
```json
{
  "success": true,
  "data": {
    "access_token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
    "token_type": "Bearer",
    "expires_in": 900
  }
}
```

**Error 401 (Unauthorized):**
```json
{
  "error": true,
  "message": "Invalid or expired refresh token"
}
```

---

### 4. Logout

**Endpoint:** `POST /api/auth/logout`

**Description:** Revoke refresh token and logout user

**Headers:**
```http
Authorization: Bearer <access_token>
```

**Request Body:**
```json
{
  "refresh_token": "a1b2c3d4e5f6..."
}
```

**Response 200 (OK):**
```json
{
  "success": true,
  "message": "Logout successful"
}
```

---

### 5. Get Current User

**Endpoint:** `GET /api/auth/me`

**Description:** Get authenticated user information

**Headers:**
```http
Authorization: Bearer <access_token>
```

**Response 200 (OK):**
```json
{
  "success": true,
  "data": {
    "id": "550e8400-e29b-41d4-a716-446655440000",
    "name": "John Doe",
    "email": "john@example.com",
    "created_at": "2025-12-10 22:00:00"
  }
}
```

**Error 401 (Unauthorized):**
```json
{
  "error": true,
  "message": "Unauthorized"
}
```

---

## User Endpoints

### Get All Users

**Endpoint:** `GET /api/users`

**Description:** Retrieve list of all users (protected)

**Headers:**
```http
Authorization: Bearer <access_token>
```

**Response 200 (OK):**
```json
{
  "success": true,
  "data": [
    {
      "id_user": "550e8400-e29b-41d4-a716-446655440000",
      "name": "John Doe",
      "email": "john@example.com",
      "created_at": "2025-12-10 22:00:00",
      "updated_at": "2025-12-10 22:00:00"
    }
  ],
  "count": 1
}
```

**Error 401 (Unauthorized):**
```json
{
  "error": true,
  "message": "Unauthorized"
}
```

---

## Board Endpoints

### 1. Get All Board Items

**Endpoint:** `GET /api/board`

**Description:** Retrieve all board items (protected)

**Headers:**
```http
Authorization: Bearer <access_token>
```

**Response 200 (OK):**
```json
{
  "success": true,
  "data": [
    {
      "id_board": 1,
      "name": "Task 1",
      "description": "Description here",
      "state": "pending",
      "created_at": "2025-12-10 22:00:00",
      "assigned": "550e8400-e29b-41d4-a716-446655440000"
    }
  ],
  "count": 1
}
```

---

### 2. Create Board Item

**Endpoint:** `POST /api/board`

**Description:** Create a new board item (protected)

**Headers:**
```http
Authorization: Bearer <access_token>
Content-Type: application/json
```

**Request Body:**
```json
{
  "name": "New Task",
  "description": "Task description",
  "state": "pending",
  "assigned": "550e8400-e29b-41d4-a716-446655440000"
}
```

**Validation:**
- `name`: Required

**Response 200 (OK):**
```json
{
  "success": true,
  "message": "Item created correctly"
}
```

**Error 400 (Bad Request):**
```json
{
  "error": true,
  "message": "Name is required"
}
```

---

### 3. Update Board Item

**Endpoint:** `PUT /api/board`

**Description:** Update an existing board item (protected)

**Headers:**
```http
Authorization: Bearer <access_token>
Content-Type: application/json
```

**Request Body:**
```json
{
  "id_board": 1,
  "name": "Updated Task",
  "description": "Updated description",
  "state": "in_progress",
  "assigned": "550e8400-e29b-41d4-a716-446655440000"
}
```

**Validation:**
- `id_board`: Required

**Response 200 (OK):**
```json
{
  "success": true,
  "message": "Item updated successfully"
}
```

**Error 400 (Bad Request):**
```json
{
  "error": true,
  "message": "Expected items not found"
}
```

---

## Blog Endpoints

### Overview

Blog posts have three possible states:
- **draft**: Post is not visible publicly, `published_at` is `null`
- **published**: Post is visible publicly, `published_at` is set to publication timestamp
- **archived**: Post is hidden but preserved (future feature)

#### Important Field Behaviors

**`published_at` Field:**
- Automatically set to current timestamp when:
  - Creating a post with `status: "published"`
  - Publishing a draft via `POST /api/blog/posts/:id/publish`
- Remains `null` for draft posts
- Used for ordering published posts (most recent first)

**`author_id` Field:**
- Automatically set to the authenticated user's ID on post creation
- Cannot be changed after creation

**`excerpt` Field:**
- Auto-generated from first 300 characters of `content_markdown` if not provided
- Can be manually specified for custom summaries

### 1. List Blog Posts

**Endpoint:** `GET /api/blog/posts`

**Description:** Retrieve paginated list of blog posts (public)

**Query Parameters:**
- `status` (optional): Filter by status (`published`, `draft`, `archived`) - Default: `published`
- `author_id` (optional): Filter by author UUID
- `page` (optional): Page number - Default: `1`
- `limit` (optional): Items per page (1-50) - Default: `10`

**Example:**
```http
GET /api/blog/posts?status=published&page=1&limit=10
```

**Response 200 (OK):**
```json
{
  "success": true,
  "data": [
    {
      "id_post": "uuid-here",
      "title": "My Blog Post",
      "excerpt": "Post summary...",
      "content_markdown": "# Title\n\nContent here...",
      "cover_image_url": "https://res.cloudinary.com/...",
      "status": "published",
      "likes_count": 42,
      "published_at": "2025-12-25 12:00:00",
      "created_at": "2025-12-25 11:00:00",
      "updated_at": "2025-12-25 12:00:00",
      "author_name": "John Doe",
      "author_email": "john@example.com",
      "tags": [
        {
          "id_tag": 1,
          "name": "Tutorial",
          "slug": "tutorial"
        }
      ]
    }
  ],
  "pagination": {
    "page": 1,
    "limit": 10,
    "count": 10
  }
}
```

---

### 2. Get Single Post

**Endpoint:** `GET /api/blog/posts/:id`

**Description:** Retrieve detailed information of a single post (public)

**Example:**
```http
GET /api/blog/posts/uuid-here
```

**Response 200 (OK):**
```json
{
  "success": true,
  "data": {
    "id_post": "uuid-here",
    "title": "My Blog Post",
    "content_markdown": "# Title\n\nFull content...",
    "excerpt": "Summary...",
    "cover_image_url": "https://res.cloudinary.com/...",
    "status": "published",
    "likes_count": 42,
    "published_at": "2025-12-25 12:00:00",
    "author_name": "John Doe",
    "tags": [
      {"id_tag": 1, "name": "Tutorial", "slug": "tutorial"}
    ],
    "images": [
      {
        "id_image": 1,
        "image_url": "https://res.cloudinary.com/...",
        "cloudinary_id": "blog/image1",
        "alt_text": "Image description",
        "position": 1
      }
    ],
    "has_liked": true
  }
}
```

**Note:** `has_liked` only appears if user is authenticated

**Error 404 (Not Found):**
```json
{
  "error": true,
  "message": "Post not found"
}
```

---

### 3. Create Blog Post

**Endpoint:** `POST /api/blog/posts`

**Description:** Create a new blog post (protected)

**Headers:**
```http
Authorization: Bearer <access_token>
Content-Type: application/json
```

**Request Body:**
```json
{
  "title": "My New Post",
  "content_markdown": "# Title\n\nContent in markdown...",
  "excerpt": "Optional summary (auto-generated if not provided)",
  "cover_image_url": "https://res.cloudinary.com/...",
  "status": "draft",
  "tag_ids": [1, 2, 3],
  "images": [
    {
      "url": "https://res.cloudinary.com/...",
      "cloudinary_id": "blog/image1",
      "alt_text": "Image description"
    }
  ]
}
```

**Validation:**
- `title`: Required, max 200 characters
- `content_markdown`: Required
- `status`: Optional, defaults to `draft`. Can be `draft` or `published`
- `excerpt`: Optional, auto-generated from content if not provided
- `cover_image_url`: Optional
- `tag_ids`: Optional array of tag IDs
- `images`: Optional array of image objects

**Important Notes:**
- If `status` is set to `"published"`, the `published_at` field will be automatically set to the current timestamp
- If `status` is `"draft"` (or omitted), `published_at` will be `null`
- The post author is automatically set to the authenticated user

**Response 201 (Created):**
```json
{
  "success": true,
  "message": "Post created successfully",
  "data": {
    "id_post": "uuid-here"
  }
}
```

**Error 400 (Bad Request):**
```json
{
  "error": true,
  "message": "Title and content are required"
}
```

**Error 401 (Unauthorized):**
```json
{
  "error": true,
  "message": "Unauthorized"
}
```

---

### 4. Update Blog Post

**Endpoint:** `PUT /api/blog/posts/:id`

**Description:** Update a blog post (protected - author only)

**Headers:**
```http
Authorization: Bearer <access_token>
Content-Type: application/json
```

**Request Body:**
```json
{
  "title": "Updated Title",
  "content_markdown": "# Updated\n\nNew content...",
  "excerpt": "Updated summary",
  "cover_image_url": "https://res.cloudinary.com/...",
  "tag_ids": [1, 4, 5]
}
```

**Response 200 (OK):**
```json
{
  "success": true,
  "message": "Post updated successfully"
}
```

**Error 403 (Forbidden):**
```json
{
  "error": true,
  "message": "You are not authorized to update this post"
}
```

**Error 404 (Not Found):**
```json
{
  "error": true,
  "message": "Post not found"
}
```

---

### 5. Delete Blog Post

**Endpoint:** `DELETE /api/blog/posts/:id`

**Description:** Delete a blog post (protected - author only)

**Headers:**
```http
Authorization: Bearer <access_token>
```

**Response 200 (OK):**
```json
{
  "success": true,
  "message": "Post deleted successfully",
  "cloudinary_ids": ["blog/image1", "blog/image2"]
}
```

**Note:** `cloudinary_ids` contains the Cloudinary public IDs that should be deleted from Cloudinary by the frontend.

**Error 403 (Forbidden):**
```json
{
  "error": true,
  "message": "You are not authorized to delete this post"
}
```

---

### 6. Publish Blog Post

**Endpoint:** `POST /api/blog/posts/:id/publish`

**Description:** Publish a draft post (protected - author only)

**Headers:**
```http
Authorization: Bearer <access_token>
```

**Important Note:**
- When a draft is published, the `published_at` field is automatically set to the current timestamp
- The post status changes from `draft` to `published`

**Response 200 (OK):**
```json
{
  "success": true,
  "message": "Post published successfully"
}
```

**Error 400 (Bad Request):**
```json
{
  "error": true,
  "message": "Post is already published"
}
```

**Error 403 (Forbidden):**
```json
{
  "error": true,
  "message": "You are not authorized to publish this post"
}
```

---

### 7. Toggle Like on Post

**Endpoint:** `POST /api/blog/posts/:id/like`

**Description:** Like or unlike a post (protected)

**Headers:**
```http
Authorization: Bearer <access_token>
```

**Response 200 (OK) - Liked:**
```json
{
  "success": true,
  "message": "Liked",
  "data": {
    "action": "liked",
    "likes_count": 43
  }
}
```

**Response 200 (OK) - Unliked:**
```json
{
  "success": true,
  "message": "Unliked",
  "data": {
    "action": "unliked",
    "likes_count": 42
  }
}
```

**Error 401 (Unauthorized):**
```json
{
  "error": true,
  "message": "Unauthorized"
}
```

**Error 404 (Not Found):**
```json
{
  "error": true,
  "message": "Post not found"
}
```

---

## Error Responses

### 400 Bad Request
Invalid request data or missing required fields.

```json
{
  "error": true,
  "message": "Specific error message"
}
```

### 401 Unauthorized
Missing or invalid authentication token.

```json
{
  "error": true,
  "message": "Unauthorized"
}
```

### 403 Forbidden
Authenticated but not authorized for this action.

```json
{
  "error": true,
  "message": "You are not authorized to perform this action"
}
```

### 404 Not Found
Resource not found.

```json
{
  "error": true,
  "message": "Resource not found"
}
```

### 409 Conflict
Resource conflict (e.g., email already exists).

```json
{
  "error": true,
  "message": "Email already registered"
}
```

### 500 Internal Server Error
Server error.

```json
{
  "error": true,
  "message": "Internal server error"
}
```

---

## Authentication Flow

### 1. Registration & Login
```
User Registration:
POST /api/auth/register
  ↓
Account Created

User Login:
POST /api/auth/login
  ↓
Receive:
  - access_token (15 min)
  - refresh_token (7 days)
```

### 2. Making Authenticated Requests
```
Include in headers:
Authorization: Bearer <access_token>

Example:
GET /api/blog/posts
Authorization: Bearer eyJ0eXAiOiJKV1Q...
```

### 3. Token Refresh
```
When access_token expires (15 min):

POST /api/auth/refresh
{
  "refresh_token": "..."
}
  ↓
Receive new access_token
```

### 4. Logout
```
POST /api/auth/logout
{
  "refresh_token": "..."
}
  ↓
Refresh token revoked
```

---

## Quick Start Example

### JavaScript/Fetch Example

```javascript
// 1. Login
const loginResponse = await fetch('http://localhost/api/auth/login', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({
    email: 'user@example.com',
    password: 'password123'
  })
});

const { data } = await loginResponse.json();
const accessToken = data.access_token;

// 2. Make authenticated request
const boardResponse = await fetch('http://localhost/api/board', {
  headers: {
    'Authorization': `Bearer ${accessToken}`
  }
});

const boardData = await boardResponse.json();
console.log(boardData);

// 3. Create blog post
const postResponse = await fetch('http://localhost/api/blog/posts', {
  method: 'POST',
  headers: {
    'Authorization': `Bearer ${accessToken}`,
    'Content-Type': 'application/json'
  },
  body: JSON.stringify({
    title: 'My Post',
    content_markdown': '# Hello\n\nWorld!',
    status: 'draft'
  })
});

const postData = await postResponse.json();
console.log(postData);
```

---

## Notes

- All endpoints return JSON responses
- All protected endpoints require `Authorization: Bearer <token>` header
- Access tokens expire in **15 minutes**
- Refresh tokens expire in **7 days**
- Passwords are hashed with **bcrypt**
- All timestamps are in format: `YYYY-MM-DD HH:mm:ss`
- UUIDs are in format: `xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx`
- Content is sanitized on frontend with **DOMPurify** before rendering

---

## Endpoints Summary

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| **Authentication** |
| POST | `/api/auth/register` | No | Register new user |
| POST | `/api/auth/login` | No | Login |
| POST | `/api/auth/refresh` | No | Refresh access token |
| POST | `/api/auth/logout` | Yes | Logout |
| GET | `/api/auth/me` | Yes | Get current user |
| **Users** |
| GET | `/api/users` | Yes | Get all users |
| **Board** |
| GET | `/api/board` | Yes | Get all board items |
| POST | `/api/board` | Yes | Create board item |
| PUT | `/api/board` | Yes | Update board item |
| **Blog** |
| GET | `/api/blog/posts` | No | List blog posts |
| GET | `/api/blog/posts/:id` | No | Get single post |
| POST | `/api/blog/posts` | Yes | Create post |
| PUT | `/api/blog/posts/:id` | Yes (Author) | Update post |
| DELETE | `/api/blog/posts/:id` | Yes (Author) | Delete post |
| POST | `/api/blog/posts/:id/publish` | Yes (Author) | Publish post |
| POST | `/api/blog/posts/:id/like` | Yes | Toggle like |

---

**Total Endpoints:** 17  
**Public Endpoints:** 5  
**Protected Endpoints:** 12

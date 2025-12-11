# 🔐 API Authentication Documentation

## Base URL
```
http://localhost/api
```

---

## 📋 Authentication Endpoints

### 1. Register New User

**Endpoint:** `POST /api/auth/register`

**Request Body:**
```json
{
  "name": "John Doe",
  "email": "john@example.com",
  "password": "SecurePass123"
}
```

**Response (201 Created):**
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

**Validation Rules:**
- `name`: Required
- `email`: Required, valid email format
- `password`: Required, minimum 6 characters

---

### 2. Login

**Endpoint:** `POST /api/auth/login`

**Request Body:**
```json
{
  "email": "john@example.com",
  "password": "SecurePass123"
}
```

**Response (200 OK):**
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

**Token Expiration:**
- Access Token: 15 minutes (900 seconds)
- Refresh Token: 7 days (604800 seconds)

---

### 3. Refresh Access Token

**Endpoint:** `POST /api/auth/refresh`

**Request Body:**
```json
{
  "refresh_token": "a1b2c3d4e5f6..."
}
```

**Response (200 OK):**
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

---

### 4. Logout

**Endpoint:** `POST /api/auth/logout`

**Headers:**
```
Authorization: Bearer <access_token>
```

**Request Body:**
```json
{
  "refresh_token": "a1b2c3d4e5f6..."
}
```

**Response (200 OK):**
```json
{
  "success": true,
  "message": "Logout successful"
}
```

---

### 5. Get Current User

**Endpoint:** `GET /api/auth/me`

**Headers:**
```
Authorization: Bearer <access_token>
```

**Response (200 OK):**
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

---

## 👥 User Endpoints (Protected)

### Get All Users

**Endpoint:** `GET /api/users`

**Headers:**
```
Authorization: Bearer <access_token>
```

**Response (200 OK):**
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

---

## 📋 Board Endpoints (Protected)

### Get All Board Items

**Endpoint:** `GET /api/board`

**Headers:**
```
Authorization: Bearer <access_token>
```

**Response (200 OK):**
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

### Create Board Item

**Endpoint:** `POST /api/board`

**Headers:**
```
Authorization: Bearer <access_token>
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

**Response (200 OK):**
```json
{
  "success": true,
  "message": "Item created correctly"
}
```

---

### Update Board Item

**Endpoint:** `PUT /api/board`

**Headers:**
```
Authorization: Bearer <access_token>
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

**Response (200 OK):**
```json
{
  "success": true,
  "message": "Item updated successfully"
}
```

---

## ⚠️ Error Responses

### 400 Bad Request
```json
{
  "error": true,
  "message": "Email and password are required"
}
```

### 401 Unauthorized
```json
{
  "error": true,
  "message": "Invalid or expired token"
}
```

### 404 Not Found
```json
{
  "error": true,
  "message": "Route not found"
}
```

### 409 Conflict
```json
{
  "error": true,
  "message": "Email already registered"
}
```

### 500 Internal Server Error
```json
{
  "error": true,
  "message": "Error interno del servidor"
}
```

---

## 🔒 Authentication Flow

```
1. User registers → POST /api/auth/register
2. User logs in → POST /api/auth/login
   ↓
   Receives: access_token + refresh_token
   
3. Make authenticated requests with access_token
   ↓
   Header: Authorization: Bearer <access_token>
   
4. When access_token expires (15 min):
   → POST /api/auth/refresh with refresh_token
   → Get new access_token
   
5. Logout → POST /api/auth/logout
   → Revokes refresh_token
```

---

## 📝 Notes

- All protected endpoints require `Authorization: Bearer <token>` header
- Access tokens expire in 15 minutes
- Refresh tokens expire in 7 days
- Passwords are hashed with bcrypt
- All responses are in JSON format

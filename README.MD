# HybridgeCommunityBackend - JWT Authentication API

REST API developed in PHP with JWT authentication for user and board management.

## Features

- JWT Authentication (Access + Refresh Tokens)
- Password hashing with bcrypt
- User CRUD operations
- Board CRUD operations
- Protected routes with middleware
- Input validation
- MariaDB database
- Docker for development
- Comprehensive documentation

---

## Requirements

- PHP 8.x
- Composer
- Docker Desktop (for database)
- MariaDB 11.5

---

## Installation

### 1. Clone Repository

```bash
git clone https://github.com/Ariff-dev/HybridgeCommunityBackend.git
cd HybridgeCommunityBackend
```

### 2. Install Dependencies

```bash
composer install
```

### 3. Configure Environment Variables

```bash
cp .env.example .env
```

Edit `.env` with your credentials:

```env
DB_USER=your_user
DB_PASS=your_password
DB_NAME=hybridge_community
DB_HOST=localhost
DB_ROOT_PASSWORD=your_root_password

JWT_SECRET=generate-a-secure-random-secret
JWT_ACCESS_TOKEN_EXPIRY=900
JWT_REFRESH_TOKEN_EXPIRY=604800

API_DEBUG=true
```

**Generate JWT Secret:**
```bash
php -r "echo bin2hex(random_bytes(32));"
```

### 4. Start with Docker (Recommended)

**Option A: Full Docker (Recommended)**

Start both PHP and MariaDB with one command:

```bash
docker-compose up -d
```

The database schema will be imported automatically on first run.

API will be available at: `http://localhost:8001/api`

---

**Option B: Hybrid (MariaDB in Docker, PHP Native)**

If you prefer running PHP on your host machine:

1. Start only the database:
   ```bash
   docker-compose up -d mariadb
   ```

2. Update `.env` with `DB_HOST=localhost` (instead of `mariadb`)

3. Import schema manually:
   ```bash
   mysql -h 127.0.0.1 -P 2025 -u your_user -p your_database < database_schema.sql
   ```

4. Start PHP server:
   ```bash
   php -S localhost:8000 -t public
   ```

API will be available at: `http://localhost:8000/api`

---

## Documentation

- **[API Documentation](API_DOCUMENTATION.md)** - Complete endpoint reference
- **[React Integration](REACT_INTEGRATION.md)** - Frontend integration guide
- **[Deployment Guide](DEPLOYMENT.md)** - Shared hosting deployment
- **[Development Guide](DEVELOPMENT_GUIDE.md)** - Local development setup
- **[Docker Guide](DOCKER_GUIDE.md)** - Containerized development

---

## Main Endpoints

### Authentication (Public)

```bash
POST /api/auth/register  # Register user
POST /api/auth/login     # Login
POST /api/auth/refresh   # Refresh access token
```

### Protected (Require JWT)

```bash
GET  /api/auth/me        # Current user
POST /api/auth/logout    # Logout

GET  /api/users          # List users
GET  /api/board          # List board items
POST /api/board          # Create item
PUT  /api/board          # Update item
```

---

## Quick Testing

**Note:** Use `http://localhost:8001/api` if running with Docker, or `http://localhost:8000/api` if running PHP natively.

### 1. Register User

```bash
curl -X POST http://localhost:8001/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "name": "John Doe",
    "email": "john@example.com",
    "password": "password123"
  }'
```

### 2. Login

```bash
curl -X POST http://localhost:8001/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "john@example.com",
    "password": "password123"
  }'
```

Save the `access_token` from response.

### 3. Access Protected Route

```bash
curl -X GET http://localhost:8001/api/auth/me \
  -H "Authorization: Bearer YOUR_ACCESS_TOKEN"
```

---

## Project Structure

```
HybridgeCommunityBackend/
├── public/
│   └── index.php              # Entry point
├── src/
│   ├── Auth/
│   │   └── JwtHandler.php     # JWT management
│   ├── Config/
│   │   └── database.php       # Database connection
│   ├── Controllers/
│   │   ├── AuthController.php # Authentication
│   │   ├── UserController.php # Users
│   │   └── BoardController.php # Boards
│   ├── Models/
│   │   ├── UserModel.php
│   │   ├── BoardItem.php
│   │   └── RefreshTokenModel.php
│   ├── Middlewares/
│   │   ├── JwtAuth.php        # JWT middleware
│   │   └── ApiKeyAuth.php     # API Keys (legacy)
│   ├── Helpers/
│   │   └── Uuid.php
│   └── Routes/
│       └── api.php            # Route definitions
├── vendor/                    # Composer dependencies
├── .env                       # Environment variables
├── composer.json
├── docker-compose.yml
├── database_schema.sql        # Database schema
├── API_DOCUMENTATION.md
├── REACT_INTEGRATION.md
└── DEPLOYMENT.md
```

---

## Security

- Passwords hashed with bcrypt
- JWT tokens with expiration
- Revocable refresh tokens
- Prepared statements (SQL injection prevention)
- Input validation
- Configurable CORS headers

---

## Deployment

See [DEPLOYMENT.md](DEPLOYMENT.md) for detailed deployment instructions on shared hosting.

**Summary:**
1. `composer install --no-dev`
2. Configure production `.env`
3. Import SQL schema
4. Upload files to hosting
5. Configure `.htaccess`

---

## Technology Stack

- **PHP 8.x** - Backend language
- **MariaDB 11.5** - Database
- **Firebase PHP-JWT** - JWT token management
- **vlucas/phpdotenv** - Environment variables
- **Docker** - Database container
- **PDO** - Database abstraction layer

---

## Notes

- Access tokens expire in **15 minutes**
- Refresh tokens expire in **7 days**
- Database port: **2025** (development)
- Set `API_DEBUG=false` in production

---

## React Integration

See [REACT_INTEGRATION.md](REACT_INTEGRATION.md) for:
- Complete AuthContext
- Automatic token refresh handling
- Example components (Login, Register, Dashboard)
- Protected Routes

---

## Support

For issues or questions:
1. Review documentation
2. Check PHP logs
3. Verify dependencies are installed
4. Confirm `.env` is configured correctly

---

## License

This project is open source.

---

## Next Steps

- Add unit tests (PHPUnit)
- Implement rate limiting
- Add pagination to listings
- Implement DELETE method for boards
- Add advanced roles and permissions
- OpenAPI/Swagger documentation
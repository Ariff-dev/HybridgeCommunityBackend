# Guía de Desarrollo - HybridgeCommunityBackend

Esta guía te explica cómo configurar, levantar y probar la API en tu entorno de desarrollo local.

---

## Estructura del Proyecto

```
HybridgeCommunityBackend/
│
├── public/                          # Punto de entrada público
│   └── index.php                    # Router principal de la API
│
├── src/                             # Código fuente
│   ├── Auth/                        # Sistema de autenticación
│   │   └── JwtHandler.php           # Generación y validación de JWT
│   │
│   ├── Config/                      # Configuración
│   │   └── database.php             # Conexión a base de datos
│   │
│   ├── Controllers/                 # Controladores (lógica de negocio)
│   │   ├── AuthController.php       # Login, register, refresh, logout
│   │   ├── UserController.php       # Gestión de usuarios
│   │   └── BoardController.php      # Gestión de tableros
│   │
│   ├── Models/                      # Modelos (acceso a datos)
│   │   ├── UserModel.php            # CRUD de usuarios
│   │   ├── BoardItem.php            # CRUD de tableros
│   │   └── RefreshTokenModel.php    # Gestión de refresh tokens
│   │
│   ├── Middlewares/                 # Middlewares
│   │   ├── JwtAuth.php              # Protección de rutas con JWT
│   │   └── ApiKeyAuth.php           # API Keys (sistema legacy)
│   │
│   ├── Helpers/                     # Utilidades
│   │   └── Uuid.php                 # Generador de UUIDs
│   │
│   └── Routes/                      # Definición de rutas
│       └── api.php                  # Mapeo de endpoints
│
├── vendor/                          # Dependencias de Composer
├── .env                             # Variables de entorno (NO subir a Git)
├── .env.example                     # Plantilla de variables de entorno
├── composer.json                    # Dependencias PHP
├── docker-compose.yml               # Configuración de MariaDB
├── database_schema.sql              # Schema de base de datos
│
└── Documentación/
    ├── README.MD                    # Documentación principal
    ├── API_DOCUMENTATION.md         # Referencia de endpoints
    ├── REACT_INTEGRATION.md         # Integración con React
    └── DEPLOYMENT.md                # Guía de deployment
```

---

## Flujo de la Aplicación

### 1. **Flujo de Autenticación**

```
┌─────────────────────────────────────────────────────────────┐
│                    FLUJO DE AUTENTICACIÓN                   │
└─────────────────────────────────────────────────────────────┘

1. REGISTRO
   Cliente → POST /api/auth/register {name, email, password}
         ↓
   AuthController::register()
         ↓
   UserModel::createUser() → Hash password con bcrypt
         ↓
   Guardar en BD
         ↓
   Respuesta: {success: true, user_id, email, name}

2. LOGIN
   Cliente → POST /api/auth/login {email, password}
         ↓
   AuthController::login()
         ↓
   UserModel::getUserByEmail()
         ↓
   Verificar password con password_verify()
         ↓
   JwtHandler::generateAccessToken() → JWT (15 min)
   JwtHandler::generateRefreshToken() → Token aleatorio (7 días)
         ↓
   RefreshTokenModel::create() → Guardar refresh token en BD
         ↓
   Respuesta: {access_token, refresh_token, user}

3. ACCESO A RUTA PROTEGIDA
   Cliente → GET /api/board
   Header: Authorization: Bearer <access_token>
         ↓
   JwtAuth Middleware
         ↓
   JwtHandler::validateToken() → Verificar firma y expiración
         ↓
   Si válido: Inyectar user_id en contexto
         ↓
   BoardController::index() → Procesar request
         ↓
   Respuesta: {success: true, data: [...]}

4. RENOVAR TOKEN
   Cliente → POST /api/auth/refresh {refresh_token}
         ↓
   AuthController::refresh()
         ↓
   RefreshTokenModel::validate() → Verificar en BD
         ↓
   JwtHandler::generateAccessToken() → Nuevo JWT
         ↓
   Respuesta: {access_token}

5. LOGOUT
   Cliente → POST /api/auth/logout {refresh_token}
   Header: Authorization: Bearer <access_token>
         ↓
   AuthController::logout()
         ↓
   RefreshTokenModel::revoke() → Marcar token como revocado
         ↓
   Respuesta: {success: true}
```

### 2. **Flujo de Request HTTP**

```
Request HTTP
    ↓
public/index.php (Entry Point)
    ↓
Cargar .env y autoload
    ↓
Parsear URI y método HTTP
    ↓
src/Routes/api.php (Buscar ruta)
    ↓
¿Ruta requiere autenticación?
    ↓ Sí
JwtAuth Middleware → Validar token
    ↓
Instanciar Controller
    ↓
Ejecutar método del Controller
    ↓
Controller llama a Model
    ↓
Model ejecuta query en BD
    ↓
Controller formatea respuesta JSON
    ↓
Enviar respuesta al cliente
```

---

## Configuración de Desarrollo

### **Paso 1: Requisitos Previos**

Asegúrate de tener instalado:
- PHP 8.0 o superior
- Composer
- Docker Desktop
- Git

Verificar versiones:
```bash
php -v
composer -v
docker -v
```

### **Paso 2: Clonar el Proyecto**

```bash
git clone https://github.com/Ariff-dev/HybridgeCommunityBackend.git
cd HybridgeCommunityBackend
```

### **Paso 3: Instalar Dependencias**

```bash
composer install
```

Esto instalará:
- `firebase/php-jwt` - Manejo de JWT
- `vlucas/phpdotenv` - Variables de entorno

### **Paso 4: Configurar Variables de Entorno**

```bash
# Copiar plantilla
cp .env.example .env

# Generar JWT secret
php -r "echo bin2hex(random_bytes(32));"
```

Editar `.env`:
```env
# Database
DB_USER=hybridge_user
DB_PASS=tu_password_seguro
DB_NAME=hybridge_community
DB_HOST=localhost
DB_ROOT_PASSWORD=tu_root_password

# JWT Configuration
JWT_SECRET=<pega-aqui-el-secret-generado>
JWT_ACCESS_TOKEN_EXPIRY=900
JWT_REFRESH_TOKEN_EXPIRY=604800

# API Configuration
API_DEBUG=true
```

### **Paso 5: Levantar Base de Datos**

```bash
# Iniciar contenedor de MariaDB
docker-compose up -d

# Verificar que está corriendo
docker ps
```

Deberías ver:
```
CONTAINER ID   IMAGE          PORTS                    NAMES
xxxxx          mariadb:11.5   0.0.0.0:2025->3306/tcp   hybridge_community_dev
```

### **Paso 6: Importar Schema de Base de Datos**

**Opción A: Usando MySQL CLI**
```bash
mysql -h 127.0.0.1 -P 2025 -u hybridge_user -p hybridge_community < database_schema.sql
```

**Opción B: Usando DBeaver**
1. Conectar a la base de datos:
   - Host: `localhost`
   - Port: `2025`
   - Database: `hybridge_community`
   - User: `hybridge_user`
   - Password: (tu password del .env)

2. Ejecutar el archivo `database_schema.sql`

**Opción C: Usando phpMyAdmin**
1. Acceder a phpMyAdmin (si lo tienes instalado)
2. Importar `database_schema.sql`

### **Paso 7: Levantar Servidor PHP**

```bash
php -S localhost:8000 -t public
```

Deberías ver:
```
[Tue Dec 10 22:00:00 2024] PHP 8.2.0 Development Server (http://localhost:8000) started
```

¡La API está corriendo! 🎉

---

## Probar la API

### **Herramientas Recomendadas**

1. **cURL** (línea de comandos)
2. **Postman** (GUI)
3. **Insomnia** (GUI)
4. **Thunder Client** (extensión VS Code)

### **Prueba Básica**

```bash
# Verificar que la API responde
curl http://localhost:8000/api/auth/register
```

Si ves un error JSON, ¡está funcionando! (es normal, falta el body)

---

## Testing de Endpoints

### **1. Registrar Usuario**

```bash
curl -X POST http://localhost:8000/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Juan Pérez",
    "email": "juan@example.com",
    "password": "password123"
  }'
```

**Respuesta esperada (201):**
```json
{
  "success": true,
  "message": "User registered successfully",
  "data": {
    "user_id": "550e8400-e29b-41d4-a716-446655440000",
    "email": "juan@example.com",
    "name": "Juan Pérez"
  }
}
```

### **2. Login**

```bash
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "juan@example.com",
    "password": "password123"
  }'
```

**Respuesta esperada (200):**
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
      "name": "Juan Pérez",
      "email": "juan@example.com"
    }
  }
}
```

** IMPORTANTE:** Guarda el `access_token` para los siguientes pasos.

### **3. Obtener Usuario Actual (Ruta Protegida)**

```bash
# Reemplaza YOUR_ACCESS_TOKEN con el token del login
curl -X GET http://localhost:8000/api/auth/me \
  -H "Authorization: Bearer YOUR_ACCESS_TOKEN"
```

**Respuesta esperada (200):**
```json
{
  "success": true,
  "data": {
    "id": "550e8400-e29b-41d4-a716-446655440000",
    "name": "Juan Pérez",
    "email": "juan@example.com",
    "created_at": "2025-12-10 22:00:00"
  }
}
```

### **4. Listar Usuarios**

```bash
curl -X GET http://localhost:8000/api/users \
  -H "Authorization: Bearer YOUR_ACCESS_TOKEN"
```

### **5. Crear Item en Board**

```bash
curl -X POST http://localhost:8000/api/board \
  -H "Authorization: Bearer YOUR_ACCESS_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Tarea 1",
    "description": "Descripción de la tarea",
    "state": "pending",
    "assigned": "550e8400-e29b-41d4-a716-446655440000"
  }'
```

### **6. Listar Items del Board**

```bash
curl -X GET http://localhost:8000/api/board \
  -H "Authorization: Bearer YOUR_ACCESS_TOKEN"
```

### **7. Actualizar Item del Board**

```bash
curl -X PUT http://localhost:8000/api/board \
  -H "Authorization: Bearer YOUR_ACCESS_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "id_board": 1,
    "name": "Tarea 1 Actualizada",
    "description": "Nueva descripción",
    "state": "in_progress",
    "assigned": "550e8400-e29b-41d4-a716-446655440000"
  }'
```

### **8. Renovar Access Token**

```bash
curl -X POST http://localhost:8000/api/auth/refresh \
  -H "Content-Type: application/json" \
  -d '{
    "refresh_token": "YOUR_REFRESH_TOKEN"
  }'
```

### **9. Logout**

```bash
curl -X POST http://localhost:8000/api/auth/logout \
  -H "Authorization: Bearer YOUR_ACCESS_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "refresh_token": "YOUR_REFRESH_TOKEN"
  }'
```

---

## Testing con Postman

### **Importar Colección**

Crea una colección en Postman con estos endpoints:

**Variables de entorno:**
```
base_url: http://localhost:8000
access_token: (se actualiza después del login)
refresh_token: (se actualiza después del login)
```

**Endpoints:**

1. **Register** - POST `{{base_url}}/api/auth/register`
2. **Login** - POST `{{base_url}}/api/auth/login`
   - En "Tests", agregar:
   ```javascript
   pm.environment.set("access_token", pm.response.json().data.access_token);
   pm.environment.set("refresh_token", pm.response.json().data.refresh_token);
   ```
3. **Me** - GET `{{base_url}}/api/auth/me`
   - Header: `Authorization: Bearer {{access_token}}`
4. **Users** - GET `{{base_url}}/api/users`
5. **Board List** - GET `{{base_url}}/api/board`
6. **Board Create** - POST `{{base_url}}/api/board`
7. **Board Update** - PUT `{{base_url}}/api/board`
8. **Refresh** - POST `{{base_url}}/api/auth/refresh`
9. **Logout** - POST `{{base_url}}/api/auth/logout`

---

## Troubleshooting

### **Error: "Connection refused" en base de datos**

```bash
# Verificar que Docker está corriendo
docker ps

# Si no está, iniciar
docker-compose up -d
```

### **Error: "Class 'Dotenv\Dotenv' not found"**

```bash
# Instalar dependencias
composer install
```

### **Error: "Route not found"**

Verifica que estás usando la ruta correcta:
-  `http://localhost:8000/api/auth/login`
-  `http://localhost:8000/auth/login`

### **Error 401: "Invalid or expired token"**

- El token expiró (15 minutos)
- Usa el endpoint `/api/auth/refresh` para renovarlo
- O haz login nuevamente

### **Ver logs de errores**

```bash
# En el terminal donde corre PHP
# Los errores aparecerán automáticamente

# O revisa el log de PHP
tail -f /var/log/php_errors.log
```

---

## Verificar Base de Datos

```bash
# Conectar a MariaDB
mysql -h 127.0.0.1 -P 2025 -u hybridge_user -p

# Ver tablas
USE hybridge_community;
SHOW TABLES;

# Ver usuarios
SELECT * FROM users;

# Ver refresh tokens
SELECT * FROM refresh_tokens;

# Ver board items
SELECT * FROM board;
```

---

## Workflow de Desarrollo

```
1. Hacer cambios en el código
2. Guardar archivos
3. El servidor PHP se recarga automáticamente
4. Probar endpoint con cURL/Postman
5. Revisar respuesta
6. Ajustar si es necesario
7. Commit a Git
```

---

## Recursos Adicionales

- [API_DOCUMENTATION.md](API_DOCUMENTATION.md) - Referencia completa de endpoints
- [REACT_INTEGRATION.md](REACT_INTEGRATION.md) - Integración con React
- [DEPLOYMENT.md](DEPLOYMENT.md) - Deploy a producción

---

## Checklist de Desarrollo

- [ ] PHP 8+ instalado
- [ ] Composer instalado
- [ ] Docker Desktop corriendo
- [ ] Dependencias instaladas (`composer install`)
- [ ] `.env` configurado
- [ ] JWT_SECRET generado
- [ ] Base de datos levantada (`docker-compose up -d`)
- [ ] Schema importado (`database_schema.sql`)
- [ ] Servidor PHP corriendo (`php -S localhost:8000 -t public`)
- [ ] Endpoint de registro probado
- [ ] Endpoint de login probado
- [ ] Rutas protegidas probadas con token

---

¡Listo! Ahora tienes la API corriendo en desarrollo. 

# 🚀 HybridgeCommunityBackend - JWT Authentication API

API REST desarrollada en PHP con autenticación JWT para gestión de usuarios y tableros (boards).

## ✨ Características

- ✅ Autenticación JWT (Access + Refresh Tokens)
- ✅ Hash de contraseñas con bcrypt
- ✅ CRUD de usuarios
- ✅ CRUD de tableros (boards)
- ✅ Rutas protegidas con middleware
- ✅ Validación de datos
- ✅ Base de datos MariaDB
- ✅ Docker para desarrollo
- ✅ Documentación completa

---

## 📋 Requisitos

- PHP 8.x
- Composer
- Docker Desktop (para base de datos)
- MariaDB 11.5

---

## 🔧 Instalación

### 1. Clonar el repositorio

```bash
git clone https://github.com/Ariff-dev/HybridgeCommunityBackend.git
cd HybridgeCommunityBackend
```

### 2. Instalar dependencias

```bash
composer install
```

### 3. Configurar variables de entorno

```bash
cp .env.example .env
```

Edita `.env` con tus credenciales:

```env
DB_USER=tu_usuario
DB_PASS=tu_password
DB_NAME=hybridge_community
DB_HOST=localhost
DB_ROOT_PASSWORD=tu_root_password

JWT_SECRET=genera-un-secret-aleatorio-seguro
JWT_ACCESS_TOKEN_EXPIRY=900
JWT_REFRESH_TOKEN_EXPIRY=604800

API_DEBUG=true
```

**Generar JWT Secret:**
```bash
php -r "echo bin2hex(random_bytes(32));"
```

### 4. Iniciar base de datos

```bash
docker-compose up -d
```

### 5. Importar schema de base de datos

Conecta a la base de datos (puerto 2025) e importa `database_schema.sql`:

```bash
mysql -h 127.0.0.1 -P 2025 -u tu_usuario -p tu_database < database_schema.sql
```

O usa DBeaver/phpMyAdmin.

### 6. Iniciar servidor PHP

```bash
php -S localhost:8000 -t public
```

La API estará disponible en: `http://localhost:8000/api`

---

## 📚 Documentación

- **[API Documentation](API_DOCUMENTATION.md)** - Todos los endpoints y ejemplos
- **[React Integration](REACT_INTEGRATION.md)** - Guía de integración con React
- **[Deployment Guide](DEPLOYMENT.md)** - Deploy en hosting compartido

---

## 🔐 Endpoints Principales

### Autenticación (Públicos)

```bash
POST /api/auth/register  # Registrar usuario
POST /api/auth/login     # Iniciar sesión
POST /api/auth/refresh   # Renovar access token
```

### Protegidos (Requieren JWT)

```bash
GET  /api/auth/me        # Usuario actual
POST /api/auth/logout    # Cerrar sesión

GET  /api/users          # Listar usuarios
GET  /api/board          # Listar items del tablero
POST /api/board          # Crear item
PUT  /api/board          # Actualizar item
```

---

## 🧪 Pruebas Rápidas

### 1. Registrar usuario

```bash
curl -X POST http://localhost:8000/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "name": "John Doe",
    "email": "john@example.com",
    "password": "password123"
  }'
```

### 2. Login

```bash
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "john@example.com",
    "password": "password123"
  }'
```

Guarda el `access_token` de la respuesta.

### 3. Acceder a ruta protegida

```bash
curl -X GET http://localhost:8000/api/auth/me \
  -H "Authorization: Bearer TU_ACCESS_TOKEN"
```

---

## 🏗️ Estructura del Proyecto

```
HybridgeCommunityBackend/
├── public/
│   └── index.php              # Entry point
├── src/
│   ├── Auth/
│   │   └── JwtHandler.php     # Manejo de JWT
│   ├── Config/
│   │   └── database.php       # Conexión BD
│   ├── Controllers/
│   │   ├── AuthController.php # Autenticación
│   │   ├── UserController.php # Usuarios
│   │   └── BoardController.php # Tableros
│   ├── Models/
│   │   ├── UserModel.php
│   │   ├── BoardItem.php
│   │   └── RefreshTokenModel.php
│   ├── Middlewares/
│   │   ├── JwtAuth.php        # Middleware JWT
│   │   └── ApiKeyAuth.php     # API Keys (legacy)
│   ├── Helpers/
│   │   └── Uuid.php
│   └── Routes/
│       └── api.php            # Definición de rutas
├── vendor/                    # Dependencias Composer
├── .env                       # Variables de entorno
├── composer.json
├── docker-compose.yml
├── database_schema.sql        # Schema de BD
├── API_DOCUMENTATION.md
├── REACT_INTEGRATION.md
└── DEPLOYMENT.md
```

---

## 🔒 Seguridad

- ✅ Contraseñas hasheadas con bcrypt
- ✅ Tokens JWT con expiración
- ✅ Refresh tokens revocables
- ✅ Prepared statements (prevención SQL injection)
- ✅ Validación de entrada
- ✅ Headers CORS configurables

---

## 🚀 Deployment

Ver [DEPLOYMENT.md](DEPLOYMENT.md) para instrucciones detalladas de deploy en hosting compartido.

**Resumen:**
1. `composer install --no-dev`
2. Configurar `.env` de producción
3. Importar schema SQL
4. Subir archivos al hosting
5. Configurar `.htaccess`

---

## 🛠️ Tecnologías

- **PHP 8.x** - Lenguaje backend
- **MariaDB 11.5** - Base de datos
- **Firebase PHP-JWT** - Manejo de tokens JWT
- **vlucas/phpdotenv** - Variables de entorno
- **Docker** - Contenedor de base de datos
- **PDO** - Capa de abstracción de BD

---

## 📝 Notas

- Access tokens expiran en **15 minutos**
- Refresh tokens expiran en **7 días**
- Puerto de base de datos: **2025** (desarrollo)
- Cambiar `API_DEBUG=false` en producción

---

## 🤝 Integración con React

Ver [REACT_INTEGRATION.md](REACT_INTEGRATION.md) para:
- AuthContext completo
- Manejo automático de refresh tokens
- Componentes de ejemplo (Login, Register, Dashboard)
- Protected Routes

---

## 📞 Soporte

Para problemas o preguntas:
1. Revisa la documentación
2. Verifica los logs de PHP
3. Confirma que las dependencias están instaladas
4. Revisa que `.env` está configurado correctamente

---

## 📄 Licencia

Este proyecto es de código abierto.

---

## 🎯 Próximos Pasos

- [ ] Agregar tests unitarios (PHPUnit)
- [ ] Implementar rate limiting
- [ ] Agregar paginación en listados
- [ ] Implementar método DELETE para boards
- [ ] Agregar roles y permisos avanzados
- [ ] Documentación OpenAPI/Swagger
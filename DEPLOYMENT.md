# Deployment Guide - Hosting Compartido

## Pasos para Deploy

### 1. Preparar el Proyecto Localmente

```bash
# Instalar dependencias
composer install --no-dev --optimize-autoloader

# Crear archivo .env de producción
cp .env.example .env
```

### 2. Configurar .env para Producción

```bash
# Database
DB_USER=tu_usuario_db
DB_PASS=tu_password_db
DB_NAME=tu_database
DB_HOST=localhost
DB_ROOT_PASSWORD=tu_root_password

# JWT Configuration
JWT_SECRET=genera-un-secret-aleatorio-muy-largo-y-seguro
JWT_ACCESS_TOKEN_EXPIRY=900
JWT_REFRESH_TOKEN_EXPIRY=604800

# API Configuration
API_DEBUG=false  # IMPORTANTE: false en producción
```

### 3. Generar JWT Secret Seguro

```bash
# En tu terminal local:
php -r "echo bin2hex(random_bytes(32));"
# Copia el resultado y úsalo como JWT_SECRET
```

### 4. Estructura de Archivos en Hosting

```
/home/tu_usuario/
├── public_html/              # Carpeta pública
│   ├── api/
│   │   └── index.php         # Proxy al backend
│   └── .htaccess            # Configuración Apache
│
└── backend/                  # FUERA de public_html
    ├── src/
    ├── vendor/
    ├── .env
    ├── composer.json
    └── public/
        └── index.php
```

### 5. Crear Proxy en public_html/api/index.php

```php
<?php
// Proxy que apunta al backend real
require_once '../../backend/public/index.php';
```

### 6. Configurar .htaccess en public_html/

```apache
# Habilitar rewrite
RewriteEngine On

# API Routes - Redirigir /api/* al backend
RewriteCond %{REQUEST_URI} ^/api/
RewriteRule ^api/(.*)$ /api/index.php [QSA,L]

# Proteger archivos sensibles
<FilesMatch "\.(env|json|lock)$">
    Order allow,deny
    Deny from all
</FilesMatch>

# Proteger directorios
RedirectMatch 403 ^/api/(vendor|src|config)/.*$
```

### 7. Crear Base de Datos

1. Accede a cPanel → MySQL Databases
2. Crea una nueva base de datos
3. Crea un usuario y asígnalo a la base de datos
4. Importa el schema:

```bash
# Conecta a tu base de datos y ejecuta:
mysql -u tu_usuario -p tu_database < database_schema.sql
```

O usa phpMyAdmin para importar `database_schema.sql`

### 8. Subir Archivos

**Opción A: FTP/SFTP**
```bash
# Usando FileZilla o similar:
1. Conecta a tu hosting
2. Sube la carpeta 'backend' a /home/tu_usuario/
3. Sube el proxy index.php a /home/tu_usuario/public_html/api/
4. Sube .htaccess a /home/tu_usuario/public_html/
```

**Opción B: Git (si tu hosting lo soporta)**
```bash
ssh tu_usuario@tu_hosting.com
cd /home/tu_usuario/
git clone https://github.com/tu-usuario/HybridgeCommunityBackend.git backend
cd backend
composer install --no-dev
```

### 9. Configurar Permisos

```bash
# Conecta por SSH y ejecuta:
chmod 644 backend/.env
chmod 755 backend/src
chmod 755 backend/vendor
chmod 644 public_html/api/index.php
```

### 10. Probar la API

```bash
# Test básico
curl https://tu-dominio.com/api/auth/register \
  -X POST \
  -H "Content-Type: application/json" \
  -d '{"name":"Test","email":"test@test.com","password":"test123"}'
```

---

## Seguridad en Producción

### Archivo .htaccess Completo

```apache
# Habilitar rewrite
RewriteEngine On

# Forzar HTTPS
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]

# API Routes
RewriteCond %{REQUEST_URI} ^/api/
RewriteRule ^api/(.*)$ /api/index.php [QSA,L]

# Proteger archivos sensibles
<FilesMatch "\.(env|json|lock|sql|md)$">
    Order allow,deny
    Deny from all
</FilesMatch>

# Proteger directorios
RedirectMatch 403 ^/api/(vendor|src|config|Auth|Models|Controllers|Middlewares)/.*$

# Deshabilitar listado de directorios
Options -Indexes

# Protección contra inyección
<IfModule mod_rewrite.c>
    RewriteCond %{QUERY_STRING} (\<|%3C).*script.*(\>|%3E) [NC,OR]
    RewriteCond %{QUERY_STRING} GLOBALS(=|\[|\%[0-9A-Z]{0,2}) [OR]
    RewriteCond %{QUERY_STRING} _REQUEST(=|\[|\%[0-9A-Z]{0,2})
    RewriteRule ^(.*)$ index.php [F,L]
</IfModule>
```

### Archivo .user.ini (Configuración PHP)

Crea `/home/tu_usuario/public_html/api/.user.ini`:

```ini
; Seguridad
expose_php = Off
display_errors = Off
log_errors = On
error_log = /home/tu_usuario/logs/php_errors.log

; Límites
upload_max_filesize = 10M
post_max_size = 10M
memory_limit = 256M
max_execution_time = 60

; Sesiones
session.cookie_httponly = 1
session.cookie_secure = 1
session.use_strict_mode = 1
```

---

## Checklist de Deploy

- [ ] Composer install ejecutado
- [ ] .env configurado con credenciales de producción
- [ ] JWT_SECRET generado y configurado
- [ ] API_DEBUG=false en .env
- [ ] Base de datos creada
- [ ] Schema SQL importado
- [ ] Archivos subidos al hosting
- [ ] Permisos configurados correctamente
- [ ] .htaccess configurado
- [ ] HTTPS habilitado (SSL)
- [ ] Prueba de registro funcionando
- [ ] Prueba de login funcionando
- [ ] Endpoints protegidos requieren token

---

## Troubleshooting

### Error: "Class 'Dotenv\Dotenv' not found"
```bash
# Asegúrate de que vendor/ existe
composer install --no-dev
```

### Error: "Connection refused" en base de datos
```bash
# Verifica en .env:
DB_HOST=localhost  # NO uses 127.0.0.1 en hosting compartido
```

### Error 500 en todas las rutas
```bash
# Verifica permisos:
chmod 755 public_html/api
chmod 644 public_html/api/index.php

# Revisa logs de PHP:
tail -f ~/logs/php_errors.log
```

### CORS errors desde React
```php
// En public/index.php, asegúrate de tener:
header('Access-Control-Allow-Origin: https://tu-frontend.com');
// O para desarrollo:
header('Access-Control-Allow-Origin: *');
```

---

## Soporte

Si tienes problemas:
1. Revisa los logs de PHP
2. Verifica que composer install se ejecutó
3. Confirma que .env tiene las credenciales correctas
4. Prueba los endpoints con curl o Postman

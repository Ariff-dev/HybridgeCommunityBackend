# Guía de Desarrollo con Docker

Esta guía te permite correr **TODO el proyecto en Docker** sin instalar PHP ni MariaDB en tu laptop.

---

## Requisitos

**Solo necesitas:**
- Docker Desktop
- Git

**NO necesitas:**
-  PHP
-  Composer
-  MariaDB/MySQL

---

## Inicio Rápido (3 pasos)

### **1. Configurar Variables de Entorno**

```bash
# Copiar plantilla
cp .env.example .env

# Generar JWT secret (usando Docker)
docker run --rm php:8.2-cli php -r "echo bin2hex(random_bytes(32));"
```

Editar `.env` y configurar:
```env
DB_USER=hybridge_user
DB_PASS=tu_password_seguro
DB_NAME=hybridge_community
DB_HOST=mariadb
DB_ROOT_PASSWORD=root_password_seguro

JWT_SECRET=<pega-aqui-el-secret-generado>
JWT_ACCESS_TOKEN_EXPIRY=900
JWT_REFRESH_TOKEN_EXPIRY=604800

API_DEBUG=true
```

### **2. Levantar Contenedores**

```bash
docker-compose up -d
```

Esto hará:
-  Descargar imagen de PHP 8.2
-  Descargar imagen de MariaDB 11.5
-  Instalar extensiones PHP (PDO, MySQL, ZIP)
-  Instalar Composer
-  Instalar dependencias del proyecto
-  Importar schema de base de datos
-  Iniciar servidor PHP en puerto 8000

### **3. Probar la API**

```bash
curl -X POST http://localhost:8000/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Test User",
    "email": "test@test.com",
    "password": "test123"
  }'
```

¡Listo! 🎉

---

## Arquitectura Docker

```
┌─────────────────────────────────────────┐
│         Docker Network                  │
│     (dev_network_community)             │
│                                         │
│  ┌──────────────┐    ┌──────────────┐  │
│  │  PHP 8.2     │    │  MariaDB     │  │
│  │  Container   │───▶│  11.5        │  │
│  │              │    │  Container   │  │
│  │ - Composer   │    │              │  │
│  │ - PDO        │    │ Port: 3306   │  │
│  │ - Extensions │    │              │  │
│  │              │    │              │  │
│  │ Port: 8000   │    │              │  │
│  └──────────────┘    └──────────────┘  │
│         │                    │          │
└─────────┼────────────────────┼──────────┘
          │                    │
    localhost:8000      localhost:2025
          │                    │
     ┌────▼────────────────────▼─────┐
     │      Tu Laptop (Host)         │
     └───────────────────────────────┘
```

---

## Servicios Docker

### **1. PHP Container (`hybridge_php`)**
- **Imagen:** `php:8.2-cli`
- **Puerto:** `8000` (API)
- **Extensiones:** PDO, PDO_MySQL, ZIP
- **Herramientas:** Composer, Git, Curl
- **Volumen:** Código del proyecto montado en `/var/www`

### **2. MariaDB Container (`hybridge_mariadb`)**
- **Imagen:** `mariadb:11.5`
- **Puerto:** `2025` (acceso externo)
- **Puerto interno:** `3306`
- **Volumen:** Datos persistentes en `mariadb_data`
- **Auto-import:** `database_schema.sql` al iniciar

---

## Comandos Útiles

### **Ver logs de los contenedores**

```bash
# Todos los logs
docker-compose logs -f

# Solo PHP
docker-compose logs -f php

# Solo MariaDB
docker-compose logs -f mariadb
```

### **Acceder al contenedor PHP**

```bash
# Entrar al bash del contenedor
docker exec -it hybridge_php bash

# Dentro del contenedor puedes:
php -v                    # Ver versión de PHP
composer --version        # Ver versión de Composer
ls -la                    # Ver archivos
cat .env                  # Ver variables de entorno
```

### **Acceder a MariaDB**

```bash
# Desde tu laptop (puerto 2025)
docker exec -it hybridge_mariadb mysql -u hybridge_user -p hybridge_community

# Dentro de MySQL:
SHOW TABLES;
SELECT * FROM users;
SELECT * FROM refresh_tokens;
```

### **Ejecutar comandos PHP**

```bash
# Generar JWT secret
docker exec hybridge_php php -r "echo bin2hex(random_bytes(32));"

# Ver info de PHP
docker exec hybridge_php php -i

# Ejecutar script
docker exec hybridge_php php tu_script.php
```

### **Ejecutar Composer**

```bash
# Instalar dependencias
docker exec hybridge_php composer install

# Actualizar dependencias
docker exec hybridge_php composer update

# Agregar paquete
docker exec hybridge_php composer require nombre/paquete
```

### **Reiniciar servicios**

```bash
# Reiniciar todo
docker-compose restart

# Reiniciar solo PHP
docker-compose restart php

# Reiniciar solo MariaDB
docker-compose restart mariadb
```

### **Detener servicios**

```bash
# Detener (mantiene datos)
docker-compose stop

# Detener y eliminar contenedores (mantiene volúmenes)
docker-compose down

# Detener y eliminar TODO (incluyendo base de datos)
docker-compose down -v
```

### **Ver estado de contenedores**

```bash
docker-compose ps
```

---

## Testing de la API

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

### **2. Login**

```bash
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "juan@example.com",
    "password": "password123"
  }'
```

Guarda el `access_token` de la respuesta.

### **3. Acceder a Ruta Protegida**

```bash
curl -X GET http://localhost:8000/api/auth/me \
  -H "Authorization: Bearer TU_ACCESS_TOKEN"
```

---

## Workflow de Desarrollo

```bash
# 1. Levantar contenedores
docker-compose up -d

# 2. Ver logs (opcional)
docker-compose logs -f php

# 3. Hacer cambios en el código
# Los cambios se reflejan automáticamente (volumen montado)

# 4. Probar endpoint
curl http://localhost:8000/api/...

# 5. Ver logs de errores
docker-compose logs php

# 6. Cuando termines
docker-compose stop
```

---

## Troubleshooting

### **Error: "Port 8000 already in use"**

```bash
# Ver qué está usando el puerto
lsof -i :8000

# Cambiar puerto en docker-compose.yml
ports:
  - "8001:8000"  # Usar 8001 en lugar de 8000
```

### **Error: "Connection refused" a base de datos**

```bash
# Verificar que MariaDB está corriendo
docker-compose ps

# Ver logs de MariaDB
docker-compose logs mariadb

# Verificar que DB_HOST=mariadb en .env
cat .env | grep DB_HOST
```

### **Cambios en código no se reflejan**

```bash
# Reiniciar contenedor PHP
docker-compose restart php
```

### **Reinstalar dependencias**

```bash
# Eliminar vendor y reinstalar
docker exec hybridge_php rm -rf vendor
docker exec hybridge_php composer install
```

### **Resetear base de datos**

```bash
# Detener y eliminar volúmenes
docker-compose down -v

# Levantar de nuevo (reimporta schema)
docker-compose up -d
```

### **Ver errores de PHP**

```bash
# Logs en tiempo real
docker-compose logs -f php

# Últimas 100 líneas
docker-compose logs --tail=100 php
```

---

## Acceso a Base de Datos

### **Opción 1: Desde terminal**

```bash
docker exec -it hybridge_mariadb mysql -u hybridge_user -p
# Ingresa tu password del .env

# Comandos útiles:
USE hybridge_community;
SHOW TABLES;
SELECT * FROM users;
DESCRIBE users;
```

### **Opción 2: Desde DBeaver/MySQL Workbench**

```
Host: localhost
Port: 2025
Database: hybridge_community
User: hybridge_user
Password: (tu password del .env)
```

---

## Personalización

### **Cambiar versión de PHP**

En `docker-compose.yml`:
```yaml
php:
  image: php:8.3-cli  # Cambiar a 8.3
```

### **Agregar extensión PHP**

En `docker-compose.yml`, en el comando:
```yaml
command: >
  bash -c "
  apt-get update && 
  apt-get install -y libzip-dev zip unzip git curl libpng-dev &&
  docker-php-ext-install pdo pdo_mysql zip gd &&
  ...
  "
```

### **Cambiar puerto de la API**

En `docker-compose.yml`:
```yaml
ports:
  - "9000:8000"  # API en puerto 9000
```

---

## Checklist de Inicio

- [ ] Docker Desktop instalado y corriendo
- [ ] Proyecto clonado
- [ ] `.env` creado y configurado
- [ ] JWT_SECRET generado
- [ ] `docker-compose up -d` ejecutado
- [ ] Contenedores corriendo (`docker-compose ps`)
- [ ] API responde en http://localhost:8000
- [ ] Endpoint de registro probado
- [ ] Endpoint de login probado

---

## Ventajas de este Setup

 **No contaminas tu laptop** - Todo corre en contenedores
 **Fácil de limpiar** - `docker-compose down -v`
 **Mismo entorno para todos** - Desarrollo = Producción
 **Fácil de compartir** - Solo necesitas Docker
 **Aislado** - No conflictos con otros proyectos
 **Reproducible** - Funciona igual en cualquier máquina

---

## Comandos Rápidos

```bash
# Iniciar todo
docker-compose up -d

# Ver logs
docker-compose logs -f

# Detener todo
docker-compose stop

# Reiniciar
docker-compose restart

# Limpiar todo
docker-compose down -v

# Acceder a PHP
docker exec -it hybridge_php bash

# Acceder a MySQL
docker exec -it hybridge_mariadb mysql -u root -p
```

---

¡Listo! Ahora puedes desarrollar sin instalar nada en tu laptop. 

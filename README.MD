# 🚀 Project Setup and Execution Guide

Follow these steps to set up the development environment and local database.

---

## 1. Prerequisites

Ensure the following tools are installed on your system:

- **PHP** (Recommended Version 8.x)
- **Composer** (PHP Package Manager)
- **Docker Desktop** (Required for the database service)
- **Git**
- **An SQL client** (e.g., DBeaver)

---

## 2. Project Initialization

### A. Clone the Repository

Clone the project and navigate into the main directory:

```bash
git clone https://github.com/Ariff-dev/HybridgeCommunityBackend.git
cd hybridge_community_backend
```

### B. Environment Variables

Set up your secrets and configuration file.

1. Copy the example file to create your active configuration file:

```bash
cp .env.example .env
```

2. Edit the `.env` file and provide values for the database credentials: `MYSQLI_ROOT_PASSWORD`, `MYSQLI_DATABASE_NAME`, `MYSQLI_USER`, and `MYSQLI_PASSWORD`.

### C. Install PHP Dependencies

Use Composer to download all required third-party libraries (including `vlucas/phpdotenv`):

```bash
composer install
```

---

## 3. Running the Database (MariaDB)

The project uses Docker Compose to manage the database container.

1. Ensure **Docker Desktop** is running.
2. Execute the following command in the project root to start the MariaDB service in the background:

```bash
docker-compose up -d
```

---

## 4. Database Connection Details (DBeaver)

The MariaDB container is mapped to port `2025` on your local machine to avoid conflicts. Use the following configuration to connect via DBeaver or any other SQL client:

| Parameter | Value |
|-----------|-------|
| **Host/Server** | `localhost` or `127.0.0.1` |
| **Port** | `2025` |
| **Database** | Value from `MYSQLI_DATABASE_NAME` in `.env` |
| **User** | Value from `MYSQLI_USER` in `.env` |
| **Password** | Value from `MYSQLI_PASSWORD` in `.env` |

---

## 5. Stopping the Services

When you are done working, you can stop the database service without losing your data (thanks to the persistent volume):

```bash
docker-compose down
```
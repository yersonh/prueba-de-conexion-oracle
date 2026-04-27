# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

**NAYLEX Store** is an e-commerce application for selling agricultural machinery, automotive parts, and lighting products. It's a PHP-based virtual store with user authentication, product catalog management, and order handling.

- **Primary Language**: PHP 8.1
- **Database**: Oracle Database (OCI8 extension required)
- **Web Server**: Apache (via Docker)
- **Deployment**: Railway
- **Architecture**: MVC-like pattern with models, controllers, and views

## Directory Structure

```
├── public/                    # Web root (served by Apache)
│   ├── index.php             # Login page (entry point)
│   ├── Registro.php          # Registration page
│   ├── imagenes/             # Static images (logos, backgrounds)
│   └── views/                # View templates
│       ├── Login.php
│       └── Producto.php
├── models/                   # Database models (data layer)
│   ├── UsuarioModel.php      # User operations, credentials validation
│   ├── ProductoModel.php     # Product operations
│   ├── PersonaModel.php
│   ├── ProveedorModel.php
│   ├── CategoriaModel.php
│   ├── TipoUsuarioModel.php
│   └── ProductoImagenModel.php
├── controllers/              # Business logic
│   └── LoginController.php   # Authentication logic
├── config/                   # Configuration files
│   ├── database.php          # Oracle database connection (OCI8)
│   └── uploads.php           # File upload configuration (images)
├── Dockerfile                # Docker configuration for deployment
├── railway.json              # Railway deployment config
└── Composer.json             # PHP dependencies
```

## Database Connection

The application uses **Oracle Database** with OCI8 PHP extension. Connection details are configured via environment variables:

- `ORACLE_HOST`: Database host
- `ORACLE_PORT`: Database port (default: 1522)
- `ORACLE_SERVICE`: Service name
- `ORACLE_USER`: Username (default: ADMIN)
- `ORACLE_PASSWORD`: Password

The `Database` class in [config/database.php](config/database.php) provides:
- Connection management via `oci_connect()`
- Query execution with parameter binding
- Result fetching (fetchAll, fetchOne methods)

## Models

Models in [models/](models/) handle database operations:

- **UsuarioModel** ([models/UsuarioModel.php](models/UsuarioModel.php)): User CRUD, credential validation, username/CC existence checks
- **ProductoModel** ([models/ProductoModel.php](models/ProductoModel.php)): Product listing and creation
- Other models: PersonaModel, CategoriaModel, ProveedorModel, TipoUsuarioModel, ProductoImagenModel

Models receive a database connection via constructor and use prepared statements for queries.

## Authentication

Authentication flow:

1. User submits login form in [public/index.php](public/index.php)
2. LoginController validates credentials via UsuarioModel
3. On success, user session is created with `id_usuario`, `nickname`, `tipo_usuario`
4. User state must be "Activo" to proceed
5. Session redirects to appropriate dashboard based on user type (admin/vendor/customer)

**Security Note**: Current implementation stores passwords as plain text (comment at [models/UsuarioModel.php:76](models/UsuarioModel.php#L76)). This should be migrated to hashing (password_hash/password_verify) for production.

## Image Upload Handling

Upload configuration in [config/uploads.php](config/uploads.php):

- **Allowed types**: JPEG, PNG, GIF, WebP
- **Max size**: 5MB
- **Max dimensions**: 1200×1200px
- **Quality**: 85 for JPEG compression
- **Upload path**: `/var/www/html/uploads` (persistent volume on Railway)

## Development Commands

### Docker Build & Run

```bash
docker build -t naylex-store .
docker run -p 8080:80 naylex-store
```

### Local Apache (if running outside Docker)

```bash
# Enable required Apache modules
a2enmod rewrite

# Set proper permissions
chown -R www-data:www-data /path/to/html
chmod -R 755 /path/to/html
mkdir -p uploads && chmod 777 uploads
```

### Test Locally

Place `.php` files in the `public/` directory. Apache will serve them. Access via `http://localhost/index.php` (or `http://localhost:8080` if using Docker).

## Deployment

Deployed on **Railway** using:

1. **Dockerfile** - Builds PHP 8.1 Apache image with OCI8 extension
2. **railway.json** - Configures deployment:
   - Health check path: `/`
   - Restart policy: ON_FAILURE (max 10 retries)
   - Persistent volume: `/var/www/html/uploads` for file storage

Environment variables must be set in Railway:
- `ORACLE_HOST`, `ORACLE_PORT`, `ORACLE_SERVICE`, `ORACLE_USER`, `ORACLE_PASSWORD`

## Common Tasks

### Adding a New Model

1. Create a new file in [models/](models/) following the pattern of existing models
2. Extend with methods that use `$this->conn->prepare()` and `execute()`
3. Return PDO fetch results as associative arrays

### Adding a Database Query

1. Use prepared statements with parameter binding (`:param` syntax)
2. Use `$this->conn->prepare()` for SELECT queries
3. Use parameter binding to prevent SQL injection

### Handling User Sessions

Access session data via `$_SESSION`:
- `$_SESSION['id_usuario']` - User ID
- `$_SESSION['nickname']` - Username
- `$_SESSION['tipo_usuario']` - User type (1=Admin, 2=Vendor, 3=Customer)

### File Uploads

Validate against [config/uploads.php](config/uploads.php) rules. ProductoImagenModel handles image-product associations. Store uploads in `/uploads` directory.

## Key Files to Know

- **Entry Point**: [public/index.php](public/index.php) - Login form and authentication logic
- **Database Config**: [config/database.php](config/database.php) - Oracle connection wrapper
- **User Model**: [models/UsuarioModel.php](models/UsuarioModel.php) - User authentication and validation
- **Docker Config**: [Dockerfile](Dockerfile) - Production environment setup

# Guía de Despliegue en Railway con Oracle Cloud

## Configuración en Railway

### Variables de Entorno Requeridas
Asegúrate de que estas variables estén configuradas en Railway:

```
ORACLE_HOST=adb.mx-queretaro-1.oraclecloud.com
ORACLE_PORT=1522
ORACLE_SERVICE=g7d0a109709d57d_bc27bncudfcgiclb_high.adb.oraclecloud.com
ORACLE_USER=ADMIN
ORACLE_PASSWORD=Yer-Ale061024
```

⚠️ **IMPORTANTE**: Nunca comitas la contraseña en el código. Solo configúrala en Railway.

## Cambios Realizados

### 1. Dockerfile
- ✅ Agregadas librerías `libaio-dev` y `ca-certificates` para compilación y certificados SSL
- ✅ Agregada validación de descarga de Oracle Instant Client (fallará si hay error)
- ✅ Removidas las flags `-q` (quiet) para ver el progreso de compilación

### 2. railway.json
- ✅ Cambiado `healthcheckPath` de "/" a "/index.php" (más específico)
- ✅ Reducido `healthcheckTimeout` de 100s a 30s (más rápido)

### 3. config/database.php
- ✅ Eliminados los valores por defecto fallback (no funcionan en producción)
- ✅ Agregada validación de variables de entorno requeridas
- ✅ Mejor mensaje de error si falta alguna variable

## Checklist Antes de Desplegar

- [ ] Variables de entorno configuradas en Railway
- [ ] Dockerfile commit realizado
- [ ] railway.json actualizado
- [ ] La base de datos Oracle está accesible desde Railway (firewall/network rules)
- [ ] El usuario ADMIN tiene permiso para crear tablas (si es necesario crear schema)
- [ ] SSL está habilitado correctamente en la TNS (ya está configurado)

## Troubleshooting

### Error: "Error conectando a la base de datos"

**Causa**: Las variables de entorno no están configuradas o la conexión a Oracle falla.

**Solución**:
1. Verifica que todas las variables de entorno estén en Railway
2. Verifica conectividad: `telnet adb.mx-queretaro-1.oraclecloud.com 1522`
3. Revisa los logs de Railway para más detalles

### Error: "oracle extension not found"

**Causa**: La extensión oci8 no se compiló correctamente.

**Solución**:
1. Verifica que `libaio-dev` esté instalado (ya está en el Dockerfile)
2. Intenta limpiar y reconstruir la imagen en Railway
3. Revisa si hay errores de compilación en los logs

### Error: "SSL certificate verification failed"

**Causa**: Los certificados SSL no están siendo validados correctamente.

**Solución**:
1. La TNS ya tiene `(SSL_SERVER_DN_MATCH=yes)` configurado
2. `ca-certificates` está instalado en Dockerfile
3. Si persiste, configura `ORACLE_DISABLE_WARNINGS=true` como variable de entorno temporal

## Conexión Local (desarrollo)

Si necesitas probar localmente:

```bash
# Instalar PHP y Oracle Instant Client
# macOS: brew install php@8.1
# Linux: sudo apt-get install php8.1-dev

# Compilar oci8
pecl install oci8

# Crear archivo .env local (NO commitar)
ORACLE_HOST=adb.mx-queretaro-1.oraclecloud.com
ORACLE_PORT=1522
ORACLE_SERVICE=g7d0a109709d57d_bc27bncudfcgiclb_high.adb.oraclecloud.com
ORACLE_USER=ADMIN
ORACLE_PASSWORD=tu_contraseña

# Ejecutar servidor local
php -S localhost:8000 -t public/
```

## Scripts Útiles

Para verificar que todo está bien:

```bash
# En local, crear archivo de prueba: test_db.php
<?php
require_once 'config/database.php';
try {
    $conn = Database::getConnection();
    echo "✅ Conexión exitosa a Oracle!";
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage();
}
?>

php test_db.php
```

## Estructura de Base de Datos

Asegúrate de que la base de datos Oracle tenga estas tablas:

```sql
CREATE TABLE usuario (
    id_usuario NUMBER PRIMARY KEY,
    id_persona NUMBER,
    id_tipo NUMBER,
    username VARCHAR2(100) UNIQUE,
    password VARCHAR2(255),
    estado VARCHAR2(20)
);

CREATE TABLE persona (
    id_persona NUMBER PRIMARY KEY,
    nombres VARCHAR2(100),
    apellidos VARCHAR2(100),
    cc VARCHAR2(20),
    correo VARCHAR2(100),
    telefono VARCHAR2(20),
    direccion VARCHAR2(255)
);

-- Agregar más tablas según tu schema
```

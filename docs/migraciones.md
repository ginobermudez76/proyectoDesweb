# Guía de Migraciones y Gestión de Base de Datos

Esta guía detalla el funcionamiento del sistema de migraciones del ORM Eloquent, el control de ejecuciones mediante metadatos y cómo utilizar los comandos simplificados y multiplataforma del proyecto.

---

## ¿Cómo funcionan las Migraciones en Laravel Eloquent?

Laravel Eloquent utiliza una tabla de metadatos en la base de datos para controlar y registrar qué archivos de migración ya han sido ejecutados.

1. **La Tabla de Control (`migrations`)**:
   La primera vez que ejecutas las migraciones, Laravel crea automáticamente una tabla llamada `migrations` en PostgreSQL. Esta tabla almacena:
   * `id`: Identificador incremental.
   * `migration`: El nombre del archivo de migración ejecutado (ej. `2026_06_18_000001_create_rol_table`).
   * `batch`: El número de lote en el que fue ejecutado. Esto permite agrupar ejecuciones consecutivas para poder revertirlas juntas si es necesario.

2. **Prevención de Doble Ejecución**:
   Cada vez que ejecutas un comando de migración, Laravel escanea tu carpeta `backend/database/migrations/` y compara los archivos físicos con los nombres registrados en la tabla `migrations` de la base de datos.
   * **Sólo se ejecutan las migraciones pendientes** (las que no están registradas en la tabla).
   * Si no hay archivos nuevos, el comando te informará que no hay nada que migrar.

3. **Manejo de Errores y Transacciones DDL**:
   Dado que estamos utilizando **PostgreSQL** (que soporta transacciones DDL), si una migración falla en medio de su ejecución (por ejemplo, debido a una columna duplicada o un error de sintaxis SQL):
   * La base de datos realiza un **Rollback automático** de toda la migración fallida, asegurando que no queden tablas o columnas creadas a medias.
   * Laravel detiene el proceso inmediatamente, detalla el fallo con la razón exacta del error (como vimos en el error de tabla duplicada `relation "rol" already exists`), y no registra la migración en la tabla `migrations`.

---

## Comandos de Ayuda Multiplataforma (`db` y `db.sh`)

Para evitar tener que recordar y escribir comandos largos dentro del contenedor de Docker, hemos creado scripts simplificados en la raíz del proyecto.

### Uso en Windows (PowerShell / CMD)
Utiliza el comando directamente en la terminal de la raíz:
```bash
db [comando] [argumentos]
```

### Uso en Linux / macOS (Terminal)
Utiliza el script ejecutable:
```bash
./db.sh [comando] [argumentos]
```

---

### Listado de Comandos Disponibles

| Comando | Acción | Descripción |
| :--- | :--- | :--- |
| **`create [nombre]`** | Crear Migración | Crea una nueva plantilla de migración vacía en `backend/database/migrations` con el prefijo de fecha exacta `AAAA_MM_DD_HHMMSS_[nombre].php`. |
| **`migrate`** | Ejecutar Pendientes | Ejecuta todas las migraciones que no han sido aplicadas aún en la base de datos. |
| **`fresh`** | Limpieza y Carga | **CUIDADO (Solo Desarrollo):** Borra todas las tablas de la base de datos (`drop`) y vuelve a ejecutar todas las migraciones y seeders desde cero. |
| **`rollback`** | Deshacer Lote | Revierte el último lote (batch) de migraciones ejecutadas (útil para corregir cambios recientes). |
| **`status`** | Estado de Base de Datos | Muestra un listado de todas las migraciones indicando si están ejecutadas (`Ran`) o pendientes (`Pending`). |
| **`seed`** | Poblar Datos | Ejecuta los seeders para insertar datos base en las tablas existentes. |

---

### Ejemplos Prácticos

#### 1. Crear una nueva tabla de incidentes
```bash
# Windows
db create create_incidentes_table

# Linux / macOS
./db.sh create create_incidentes_table
```
Esto creará automáticamente un archivo como `backend/database/migrations/2026_06_18_130000_create_incidentes_table.php` con la estructura base:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('incidentes', function (Blueprint $table) {
            $table->id();
            // Agrega tus columnas aquí
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('incidentes');
    }
};
```

#### 2. Correr las migraciones pendientes
```bash
# Windows
db migrate

# Linux / macOS
./db.sh migrate
```

#### 3. Revisar qué migraciones se han aplicado
```bash
# Windows
db status

# Linux / macOS
./db.sh status
```
Devolverá un reporte en consola como este:
```text
+------+---------------------------------------------+-------+
| Ran? | Migration                                   | Batch |
+------+---------------------------------------------+-------+
| Yes  | 0001_01_01_000001_create_cache_table        | 1     |
| Yes  | 0001_01_01_000002_create_jobs_table         | 1     |
| Yes  | 2026_06_18_000001_create_rol_table          | 1     |
| Pending | 2026_06_18_130000_create_incidentes_table |       |
+------+---------------------------------------------+-------+
```

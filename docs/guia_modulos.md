# Guía de Módulos (Domain-Driven Design)

Este proyecto ha sido configurado para utilizar una arquitectura modular, separando la lógica por características (features) o dominios de negocio.

## Estructura de un Módulo
Todos los módulos deben crearse dentro de la carpeta `backend/app/Modules/`.
Un módulo típico debe contener las siguientes carpetas:

```text
NombreModulo/
├── Controllers/    # Lógica de entrada/salida HTTP
├── Services/       # Reglas de negocio puras
├── Repositories/   # Abstracción de acceso a datos (BD)
├── DTOs/           # Data Transfer Objects (paso de mensajes)
├── Mappers/        # Transformación DTO <-> Entity
├── Entities/       # Modelos de Eloquent o Entidades de Negocio
├── Routes/         # Definición de rutas (api.php)
└── Providers/      # Registro del módulo en Laravel
```

> **Nota:** Se utiliza el nombre `Entities` en lugar del clásico `Models` para mantener la similitud con arquitecturas Java/Spring. Para Laravel, estas clases seguirán extendiendo de `Illuminate\Database\Eloquent\Model` o de `MongoDB\Laravel\Eloquent\Model`.

## ¿Cómo acoplar un nuevo módulo?

Si necesitas crear un nuevo módulo (por ejemplo, `Billing` o `Audit`), puedes usar la vía rápida o la manual.

### Vía Rápida (Recomendada)
Hemos creado un comando de consola personalizado para automatizar este proceso. Solo debes ejecutar:

```bash
docker compose exec app php artisan make:module NombreDelModulo
```

Este comando creará instantáneamente toda la estructura de carpetas (Controllers, Services, Entities, etc.), generará el archivo de rutas base (`api.php`) y construirá tu `ServiceProvider` por defecto. 

**Importante:** Después de ejecutar el comando, debes ir a `backend/bootstrap/providers.php` y registrar el nuevo Provider generado.

### Vía Manual (Paso a paso)
1. **Crear la Estructura**
   Crea la carpeta `backend/app/Modules/Billing/` y dentro de ella, las carpetas mencionadas arriba.

2. **Crear el Provider**
   Crea `backend/app/Modules/Billing/Providers/BillingServiceProvider.php`.
   Debe extender de `Illuminate\Support\ServiceProvider` y en su método `boot()` invocar la carga de sus rutas:
   ```php
   protected function registerRoutes(): void
   {
       Route::prefix('api')
            ->middleware('api')
            ->group(__DIR__ . '/../Routes/api.php');
   }
   ```

3. **Definir Rutas**
   Crea el archivo `backend/app/Modules/Billing/Routes/api.php` y define las rutas allí usando el Facade `Route`.

4. **Registrar el Módulo en Laravel**
   Abre el archivo `backend/bootstrap/providers.php` y añade tu nuevo Provider al arreglo:
   ```php
   return [
       App\Providers\AppServiceProvider::class,
       App\Modules\Incidence\Providers\IncidenceServiceProvider::class,
       App\Modules\Billing\Providers\BillingServiceProvider::class, // <-- Nuevo módulo
   ];
   ```

## Compartiendo Elementos
Las interfaces y clases base que sean comunes a *todos* los módulos (como el `BaseRepository` o el `BaseDTO`) deben colocarse en la carpeta `backend/app/Shared/`.

¡Siguiendo estos pasos, el proyecto mantendrá su escalabilidad intacta!

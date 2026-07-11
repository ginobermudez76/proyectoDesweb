# Guía de Códigos de Estado HTTP y Estandarización de Respuestas

Esta guía documenta el estándar de códigos de estado HTTP configurado en la API del proyecto y cómo interactuar con ellos desde el backend (Laravel) y el frontend (JS).

---

## Listado de Códigos Estandarizados

| Código | Nombre | Cuándo ocurre |
| :--- | :--- | :--- |
| **`200 OK`** | Operación exitosa | La consulta o solicitud se completó correctamente. |
| **`201 Created`** | Recurso creado | Se registró una nueva georreferencia o recurso correctamente. |
| **`204 No Content`** | Sin contenido | La operación fue exitosa pero no hay datos para devolver (ej. eliminar). |
| **`400 Bad Request`** | Solicitud incorrecta | Faltan parámetros, formato inválido o coordenadas fuera de rango. |
| **`401 Unauthorized`** | No autorizado | El usuario no ha iniciado sesión o el token es inválido/expirado. |
| **`403 Forbidden`** | Prohibido | El usuario está autenticado, pero no tiene permisos (RBAC) para el recurso. |
| **`404 Not Found`** | No encontrado | El recurso, usuario o georreferencia solicitado no existe. |
| **`405 Method Not Allowed`** | Método no permitido | Se intenta usar un método HTTP no soportado (ej. DELETE en ruta GET). |
| **`409 Conflict`** | Conflicto | Ya existe un registro con la misma información o conflicto de estado. |
| **`415 Unsupported Media Type`** | Contenido no soportado | Se envía un formato diferente al esperado (ej. XML en lugar de JSON). |
| **`422 Unprocessable Entity`**| Entidad no procesable | Los datos tienen formato correcto pero fallan las validaciones de negocio. |
| **`429 Too Many Requests`** | Demasiadas solicitudes | Se excedió el límite de peticiones permitidas por minuto (Rate Limiting). |
| **`500 Internal Server Error`**| Error interno del servidor | Ocurrió un error inesperado o no controlado en el servidor. |
| **`502 Bad Gateway`** | Puerta de enlace incorrecta | El servidor recibió respuesta inválida de un servicio externo (GPS, mapas, etc). |
| **`503 Service Unavailable`** | Servicio no disponible | El servidor está temporalmente en mantenimiento o sobrecargado. |
| **`504 Gateway Timeout`** | Tiempo de espera agotado | Un servicio externo tardó demasiado tiempo en responder. |

---

## Backend (Laravel)

Disponemos de un Enum y un Trait en la carpeta `App\Shared` para facilitar el uso de este estándar en cualquier controlador.

### 1. Enum: `HttpStatus`
Ubicación: [HttpStatus.php](file:///c:/xampp/htdocs/proyectoDesweb/backend/app/Shared/HttpStatus/HttpStatus.php)

Provee los códigos numéricos (`value`), un título corto (`label()`) y una descripción detallada en español (`description()`).

### 2. Trait: `ApiResponses`
Ubicación: [ApiResponses.php](file:///c:/xampp/htdocs/proyectoDesweb/backend/app/Shared/Traits/ApiResponses.php)

Permite unificar las respuestas success/error en formato JSON. Para usarlo en un controlador:

```php
<?php

namespace App\Modules\Auth\Controllers;

use App\Http\Controllers\Controller;
use App\Shared\Traits\ApiResponses; // 1. Importar el trait
use App\Shared\HttpStatus\HttpStatus; // 2. Importar el enum

class EjemploController extends Controller
{
    use ApiResponses; // 3. Usar el trait

    public function index()
    {
        $datos = ['ejemplo' => 'informacion'];
        
        // Retorna: 200 OK con estructura { status: "success", message: "Operación exitosa", data: [...] }
        return $this->successResponse($datos);
    }

    public function store()
    {
        // Retorna: 201 Created con estructura estandarizada
        return $this->successResponse(
            data: $nuevoRegistro, 
            message: HttpStatus::CREATED, 
            code: HttpStatus::CREATED
        );
    }

    public function errorManual()
    {
        // Retorna: 409 Conflict
        // Estructura: { status: "error", message: "Conflicto", description: "Ya existe un registro..." }
        return $this->errorResponse(HttpStatus::CONFLICT, HttpStatus::CONFLICT);
    }
}
```

### 3. Manejo Global de Excepciones
Cualquier error inesperado o de validación de Laravel (como validaciones de requests, rutas inválidas, token ausente o rate limiting) es atrapado por el gestor de excepciones en [bootstrap/app.php](file:///c:/xampp/htdocs/proyectoDesweb/backend/bootstrap/app.php) y devuelto al cliente automáticamente en formato JSON estructurado respetando la tabla de códigos HTTP.

---

## Frontend (Javascript)

El interceptor centralizado en la función `apiFetch()` de [app.js](file:///c:/xampp/htdocs/proyectoDesweb/frontend/js/app.js) analiza los códigos de estado HTTP devueltos por la API para reaccionar de forma global:

* **Error 401**: Borra la sesión en `localStorage` y redirige al usuario a `login.html` mostrando un mensaje informativo.
* **Error 403**: Muestra una alerta en pantalla avisando del acceso prohibido por RBAC.
* **Error 429**: Muestra una alerta informando que ha sobrepasado el límite de solicitudes por minuto.
* **Errores 5xx / Externos**: Muestra alertas amigables dependiendo de si el error es del servidor (500), de un API externa como mapas/GPS (502/504) o mantenimiento (503).
* **Otros errores**: Lanza una excepción ordinaria (`throw new Error(...)`) para que el formulario o página en particular que inició la petición pueda procesar y mostrar los detalles del error (por ejemplo, los errores específicos de campos inválidos de un código `422`).

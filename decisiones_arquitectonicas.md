# Registro de Decisiones Arquitectónicas (ADR)

Este documento registra las decisiones clave de diseño de software e infraestructura tomadas durante este desarrollo para el **Sistema de Gestión de Incidencias Urbanas**.

---

## 1. Diseño de Ubicación Híbrido (SQL + MongoDB)
### Contexto
El registro de ciudadanos requería capturar su ubicación exacta (País, Estado/Provincia, Ciudad, Cantón, etc.). En la base de datos relacional (PostgreSQL), modelar las jerarquías de división política de todos los países del mundo de manera estática resulta extremadamente complejo y rígido (debido a la variabilidad de niveles: algunos países tienen estados y ciudades, otros departamentos, cantones, provincias o comunas).

### Decisión
- **PostgreSQL**: Se almacena únicamente la información obligatoria de identidad del usuario (Nombres, Apellidos, Correo, Password Hash, Documento de identidad y Celular).
- **MongoDB**: Se crea la colección `ciudadano_perfiles` referenciando el `usuario_uuid`. Toda la jerarquía de ubicación seleccionada por el usuario (sin importar el número de niveles o profundidad del país) se guarda como un **documento JSON flexible**.
- **Consecuencia**: Flexibilidad total en el almacenamiento geográfico mundial sin sobrecargar ni fragmentar el esquema relacional de PostgreSQL.

---

## 2. API Proxy de Ubicación y Caché en Redis
### Contexto
Para llenar dinámicamente los dropdowns del formulario de registro de ciudadanos se requería consultar países, estados y ciudades. Consumir APIs de ubicación gratuitas directamente desde el navegador del cliente presenta riesgos de velocidad, límites de rate-limits en producción, bloqueos CORS y latencia variable.

### Decisión
- Se implementó un **Backend Proxy** en Laravel (`/api/ubicaciones/*`).
- **Almacenamiento en Caché (Redis)**: Cada vez que un usuario consulta un país o estado, Laravel comprueba si los datos ya están en Redis (llaves `ubicaciones:paises`, `ubicaciones:estados:*`, `ubicaciones:ciudades:*`). Si no están, los obtiene del API externo, los ordena alfabéticamente en el backend y los guarda en Redis por **7 días**.
- **Consecuencia**: Peticiones instantáneas (<15ms) servidas directamente desde Redis en producción, eliminación total de problemas de CORS en el cliente y protección del rate-limit del API gratuito.

---

## 3. Permisos de Lectura/Escritura Calculados Dinámicamente
### Contexto
El administrador necesitaba activar/desactivar permisos de "Lectura" (consultas) y "Escritura" (creación, edición, eliminación) para cada menú asignado a un rol. Mapear esto a nivel de base de datos vinculando roles a miles de endpoints individuales de forma estática incrementa de manera exponencial el número de registros en las tablas pivot.

### Decisión
- Se agregaron las columnas `lectura` y `escritura` (booleanos) directamente a la tabla pivot `rol_opcion`.
- **Cálculo en Middleware**: Al evaluar una petición, el middleware RBAC (`CheckRolePermission`) determina:
  - Si el método HTTP es `GET`, comprueba si la opción asignada al rol tiene `lectura = true`.
  - Si el método HTTP es de escritura (`POST`, `PUT`, `PATCH` o `DELETE`), comprueba si tiene `escritura = true`.
- **Consecuencia**: Control de lectura/escritura simplificado a nivel de menú en base de datos sin duplicar relaciones de endpoints, resuelto en tiempo de ejecución en el middleware.

---

## 4. Invalidación de Caché de Perfiles en Redis en Lote Selectivo
### Contexto
Cuando el Administrador edita los permisos de un Rol, los perfiles de usuario correspondientes (`user_profile:{userId}`) almacenados en caché en Redis deben invalidarse para aplicar los cambios de inmediato. Utilizar `Cache::flush()` para limpiar Redis destruye todas las sesiones activas (`auth_token:*`) obligando a todos los usuarios del sistema a volver a iniciar sesión.

### Decisión
- Al actualizar un rol, el controlador consulta los IDs de los usuarios asociados a dicho rol:
  ```php
  $userIds = $rol->usuarios()->pluck('usuario.id');
  ```
- Invalida selectivamente solo sus claves de perfil correspondientes mediante:
  ```php
  Cache::store('redis')->forget('user_profile:' . $uid);
  ```
- **Consecuencia**: Aplicación inmediata de cambios en permisos en tiempo real sin desconectar a otros usuarios que están operando el sistema en producción.

---

## 5. Notificaciones en Tiempo Real mediante Polling Inteligente (Opción B)
### Contexto
Se requería notificar a los usuarios en tiempo real sobre cambios de estado de incidencias y chat.
- **Opción A**: WebSockets (Canal abierto persistente).
- **Opción B**: Smart Polling (Consultas periódicas periódicas de 5 segundos contra base de datos).

### Decisión
Dado que el sistema no es una aplicación de mensajería instantánea crítica (donde cada milisegundo cuenta) y para evitar la complejidad operativa de mantener un servicio daemon de websockets encendido en producción, se implementó la **Opción B (Sondeo de 5 segundos)** consultando la colección de notificaciones de MongoDB, complementado con permisos de alertas de escritorio nativas del sistema operativo si la pestaña está en segundo plano.

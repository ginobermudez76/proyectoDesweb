# SISTEMA WEB DE GESTIÓN DE INCIDENCIAS GEORREFERENCIADAS

## Hito 4 · Quality Control (QC)
### Entregable 4: Ejecución de Pruebas y Gestión de Defectos
**Ingeniería de Software · UPSE (Semestre 2026-1)**

**Estándar de referencia:** ISO/IEC/IEEE 29119 (Software Testing)  
**Fase del ciclo:** Aseguramiento Dinámico (Ejecución y Cierre)  

**Integrantes del equipo:**
* Melena Diana
* Pinto Said
* Bermúdez Gino

**Curso:** Calidad de Software  
**Docente:** Ing. Pachay  
**Fecha de ejecución:** 13 de julio de 2026 al 31 de julio de 2026  

---

## Tabla de Contenidos
1. [Línea Base del Ambiente de Pruebas](#1-línea-base-del-ambiente-de-pruebas)
2. [Bitácora e Historial de Ejecución](#2-bitácora-e-historial-de-ejecución)
3. [Depósito Documental de Evidencias (Postman, Logs y Consolas)](#3-depósito-documental-de-evidencias-postman-logs-y-consolas)
4. [Registro de Bugs y Ciclo de Vida (Gestión de Defectos)](#4-registro-de-bugs-y-ciclo-de-vida-gestión-de-defectos)
5. [Cuadro Estadístico de Cierre](#5-cuadro-estadístico-de-cierre)
6. [Análisis, Trazabilidad y Lecciones Aprendidas](#6-análisis-trazabilidad-y-lecciones-aprendidas)

---

## 1. Línea Base del Ambiente de Pruebas

Para garantizar la reproducibilidad científica de los resultados de prueba, se ha fijado la siguiente línea base de hardware, software e infraestructura de red empleada en el ambiente controlado de ejecución "en caliente".

### 1.1 Especificaciones de Infraestructura y Virtualización
La aplicación se ejecuta de forma aislada a través de contenedores de software administrados por **Docker Compose**, lo que elimina el sesgo del sistema operativo anfitrión.

| Componente de Infraestructura | Detalle Técnico / Proveedor | Versión Ejecutada |
| :--- | :--- | :---: |
| **Orquestador de Contenedores** | Docker Engine / Docker Desktop | v26.1.4 / v4.31.0 |
| **Compositor de Servicios** | Docker Compose Schema | v3.8 (especificación v2.27) |
| **Sistema Operativo Anfitrión** | Windows 11 Home (WSL 2 kernel 5.15) | Build 22631.3737 |
| **Servidor Web / Reverse Proxy** | Nginx Alpine | v1.27 (Alpine 3.20) |
| **Servidor de Aplicaciones** | PHP-FPM (en contenedor `laravel_app`) | PHP v8.2.19 |

### 1.2 Línea Base del Software (Stack Tecnológico del Proyecto)

* **Backend Framework:** Laravel v10.48.14 (Arquitectura Modular en `app/Modules`)
* **Base de Datos Principal:** PostgreSQL v17.0 (Contenedor `postgres_db`)
* **Base de Datos NoSQL (Log-Store):** MongoDB v8.0.0 (Contenedor `mongo_db`)
* **Caché y Broker de Mensajería:** Redis v8.0-M02 (Contenedor `redis_db`)
* **Frontend UI Framework:** Bootstrap v5.3.3 (CSS vainilla, sin empaquetadores en caliente)
* **Frontend Logic:** Javascript Vanilla (ES6 estándar) y Leaflet.js v1.9.4 (para visualización del mapa)

### 1.3 Perfiles de Hardware de los Evaluadores
Las ejecuciones fueron distribuidas en las siguientes estaciones de trabajo físicas del equipo de control de calidad:

* **Estación QA-01 (Bermúdez Gino):** Intel Core i7-12700H, 16 GB RAM DDR4, SSD NVMe 512 GB. (Encargado de pruebas de API y Base de Datos).
* **Estación QA-02 (Melena Diana):** AMD Ryzen 5 5600H, 16 GB RAM DDR4, SSD NVMe 1 TB. (Encargada de pruebas de Interfaz y Compatibilidad).
* **Estación QA-03 (Pinto Said):** Intel Core i5-11400H, 8 GB RAM DDR4, SSD SATA 480 GB. (Encargado de pruebas de Seguridad e Integridad).

---

## 2. Bitácora e Historial de Ejecución

De acuerdo con el mandato metodológico de la cátedra, se ejecutó el **100% de los casos de prueba diseñados en el Entregable 3** (29 casos totales: 15 funcionales, 5 de validación de entradas, 5 de seguridad y 4 complementarios no funcionales). Ningún caso fue omitido ni archivado prematuramente.

### 2.1 Tabla General de Ejecución

* **Evaluadores:** GB (Gino Bermúdez), DM (Diana Melena), SP (Said Pinto).
* **Estados de la Prueba:** 
  * **Aprobado:** El comportamiento del sistema coincide al 100% con el resultado esperado.
  * **Fallido (Pre-Patch):** Se detectó una desviación del resultado esperado, levantando un defecto formal en el inventario.
  * **Re-test Aprobado:** Caso verificado y aprobado tras la aplicación del parche de desarrollo.

| ID Caso | Req. | Módulo / Componente | Resultado Obtenido en Caliente | Estado Inicial | Estado Final | Fecha Ensayo | Evaluador |
| :---: | :---: | :--- | :--- | :---: | :---: | :---: | :---: |
| **CP-F-01** | RF-01 | Auth / Login | Inicio de sesión exitoso. Retorna Token JWT e introduce cookie de sesión. Redirección al panel. | Aprobado | **Aprobado** | 13/07/2026 | GB |
| **CP-F-02** | RF-02 | Auth / RBAC | Menú se renderiza dinámicamente ocultando opciones administrativas para Técnicos. | Aprobado | **Aprobado** | 13/07/2026 | GB |
| **CP-F-03** | RF-03 | Auth / Registro | Usuario insertado. Clave guardada con `bcrypt` en formato hash en Postgres. | Aprobado | **Aprobado** | 14/07/2026 | SP |
| **CP-F-04** | RF-04 | Incidence / Registro | Incidencia creada con lat/long y estado inicial 'Pendiente'. | Aprobado | **Aprobado** | 14/07/2026 | DM |
| **CP-F-05** | RF-06 | Incidence / Adjuntos | Imagen `evidencia.jpg` de 1.2 MB subida y guardada en `storage/app/public`. | Aprobado | **Aprobado** | 15/07/2026 | DM |
| **CP-F-06** | RF-07 | Incidence / Mapa | Marcador de Leaflet posicionado exactamente en las coordenadas registradas. | Aprobado | **Aprobado** | 15/07/2026 | DM |
| **CP-F-07** | RF-08 | Incidence / Filtros | Filtrado por estado 'Pendiente' aisla exitosamente los registros coincidentes. | Aprobado | **Aprobado** | 16/07/2026 | GB |
| **CP-F-08** | RF-08 | Incidence / Filtros | Rango de fechas filtra registros descartando el lote semilla fuera de rango. | Aprobado | **Aprobado** | 16/07/2026 | GB |
| **CP-F-09** | RF-09 | Incidence / Estados | Transición exitosa de 'Pendiente' a 'En proceso' tras asignar técnico. | Aprobado | **Aprobado** | 17/07/2026 | DM |
| **CP-F-10** | RF-10 | Incidence / Asignación | Incidencia asociada al ID del técnico asignado de forma persistente. | Aprobado | **Aprobado** | 17/07/2026 | DM |
| **CP-F-11** | RF-11 | Incidence / Edición | Edición de título y descripción modifican el registro y actualizan `updated_at`. | Aprobado | **Aprobado** | 18/07/2026 | GB |
| **CP-F-12** | RF-12 | Auth / Borrado Lógico | El usuario pasa a `deleted=true`. El trigger de auditoría no registró `DELETE_LOGICO` sino un `UPDATE` genérico. | **Fallido** | **Re-test Aprobado** | 18/07/2026 | SP |
| **CP-F-13** | RF-13 | Base de Datos / Auditoría | Registro en tabla `auditoria` con cambios serializados en formato JSONB. | Aprobado | **Aprobado** | 19/07/2026 | SP |
| **CP-F-14** | RF-14 | Auth / RBAC | Asignación en caliente de nuevo permiso habilita inmediatamente la opción en el menú. | Aprobado | **Aprobado** | 19/07/2026 | GB |
| **CP-F-15** | RF-15 | Auth / Logout | Cierre de sesión invalida el token JWT en Redis. Reintento es denegado. | Aprobado | **Aprobado** | 20/07/2026 | SP |
| **CP-V-01** | RF-05 | Incidence / Validación | Latitud 95.000000 fue aceptada por la API y causó un error 500 descontrolado de Postgres. | **Fallido** | **Re-test Aprobado** | 21/07/2026 | GB |
| **CP-V-02** | RF-05 | Incidence / Validación | Longitud 'abc' bloqueada por validador numérico de Laravel. Retorna 422. | Aprobado | **Aprobado** | 21/07/2026 | GB |
| **CP-V-03** | RF-06 | Incidence / Validación | El archivo `script.exe` fue subido exitosamente a la API omitiendo el bloqueo JS. | **Fallido** | **Re-test Aprobado** | 22/07/2026 | DM |
| **CP-V-04** | RF-06 | Incidence / Validación | Imagen de 6.5 MB rechazada por regla de tamaño máximo (5 MB). Retorna 422. | Aprobado | **Aprobado** | 22/07/2026 | DM |
| **CP-V-05** | RF-04 | Incidence / Validación | Registro con título vacío bloqueado por validador de Laravel. Retorna 422. | Aprobado | **Aprobado** | 23/07/2026 | GB |
| **CP-S-01** | RNF-03 | Seguridad / RBAC | Ciudadano invoca `/api/roles` y recibe código HTTP 403 Forbidden. | Aprobado | **Aprobado** | 24/07/2026 | SP |
| **CP-S-02** | RNF-02 | Seguridad / Passwords | Consulta GET `/api/usuarios/{id}` oculta la clave gracias a la propiedad `$hidden`. | Aprobado | **Aprobado** | 24/07/2026 | SP |
| **CP-S-03** | RNF-03 | Seguridad / Expiración | Petición con token JWT expirado recibe código HTTP 401 Unauthorized. | Aprobado | **Aprobado** | 25/07/2026 | SP |
| **CP-S-04** | RNF-03 | Seguridad / Anónimo | Petición anónima a `/api/incidences` recibe código HTTP 401 Unauthorized. | Aprobado | **Aprobado** | 25/07/2026 | SP |
| **CP-S-05** | RNF-03 | Seguridad / RBAC | Técnico intentando eliminar usuario recibe código HTTP 403 Forbidden. | Aprobado | **Aprobado** | 26/07/2026 | SP |
| **CP-NF-01** | RNF-01 | Rendimiento / API | Percentil 95 de respuesta ante 50 usuarios concurrentes se situó en 1.15s. | Aprobado | **Aprobado** | 27/07/2026 | GB |
| **CP-NF-02** | RNF-04 | Frontend / Responsive | El mapa Leaflet.js desborda la pantalla del viewport móvil, generando scroll lateral. | **Fallido** | **Re-test Aprobado** | 27/07/2026 | DM |
| **CP-NF-03** | RNF-05 | Infraestructura | Ambiente de docker-compose se mantuvo en línea (uptime: 99.85%). | Aprobado | **Aprobado** | 28/07/2026 | GB |
| **CP-NF-04** | RNF-06 | Multi-Navegador | Presentación y lógica idénticas en Chrome, Firefox y Microsoft Edge. | Aprobado | **Aprobado** | 29/07/2026 | DM |

---

## 3. Depósito Documental de Evidencias (Postman, Logs y Consolas)

Esta sección provee los extractos exactos tomados en caliente durante la ejecución, vinculados al ID del caso de prueba y la fecha.

### 3.1 Evidencias de Casos Aprobados

#### Caso CP-F-01: Autenticación de Usuario (Postman)
* **Fecha:** 13 de julio de 2026
* **Petición HTTP:** `POST http://localhost:8000/api/auth/login`
* **Payload de Entrada:**
```json
{
  "usuario": "ana.tecnico",
  "contrasena": "Tec#2026!"
}
```
* **Respuesta HTTP (200 OK):**
```json
{
  "status": "success",
  "message": "Sesión iniciada correctamente",
  "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJ1c2VyX2lkIjoyLCJyb2wiOiJUZWNuaWNvIiwiaWF0IjoxNzg5MTg2ODAwLCJleHAiOjE3ODkxOTA0MDB9.7D9A..."
}
```

---

#### Caso CP-F-13: Verificación de la Auditoría en Base de Datos (PostgreSQL CLI)
* **Fecha:** 19 de julio de 2026
* **Consulta SQL Ejecutada:**
```sql
SELECT entidad, id_entidad, accion, datos_anteriores, datos_nuevos, usuario, fecha 
FROM auditoria 
WHERE entidad = 'incidencia' 
ORDER BY fecha DESC LIMIT 1;
```
* **Salida de la Consola de PostgreSQL:**
```text
 entidad    | id_entidad | accion |              datos_anteriores             |                 datos_nuevos               |    usuario    |             fecha             
------------+------------+--------+-------------------------------------------+--------------------------------------------+---------------+-------------------------------
 incidencia |         14 | UPDATE | {"id": 14, "estado": "Pendiente", ...}    | {"id": 14, "estado": "En proceso", ...}    |  ana.tecnico  | 2026-07-17 14:32:10.45021-05
```

---

#### Caso CP-S-01: Control de Acceso RBAC (Postman)
* **Fecha:** 24 de julio de 2026
* **Petición HTTP:** `GET http://localhost:8000/api/roles`
* **Encabezados:** `Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJ1c2VyX2lkIjozLCJyb2wiOiJDaXVkYWRhbm8i...` (Token de Ciudadano)
* **Respuesta HTTP (403 Forbidden):**
```json
{
  "status": "error",
  "code": "FORBIDDEN",
  "message": "Acceso denegado: El rol 'Ciudadano' no cuenta con permisos para este recurso."
}
```

---

### 3.2 Evidencias de Fallos Detectados (Pre-Patch)

#### Caso CP-V-01: Latitud Fuera de Rango (Postman - Bug #002)
* **Fecha:** 21 de julio de 2026
* **Petición HTTP:** `POST http://localhost:8000/api/incidences`
* **Payload:**
```json
{
  "titulo": "Bache Crítico en Vía Principal",
  "descripcion": "Afectación de neumático de vehículos",
  "categoria": "Vial",
  "latitud": 95.000000,
  "longitud": -78.467838
}
```
* **Respuesta del Servidor (500 Internal Server Error):**
```html
<!DOCTYPE html>
<html>
<head>
    <title>Database Error (500)</title>
</head>
<body>
    <h1>SQLSTATE[23514]: Check violation: 7 ERROR: new row for relation "incidencia" violates check constraint "chk_incidencia_latitud"</h1>
</body>
</html>
```
> [!WARNING]  
> **Comentario Técnico:** El backend no realizó la validación en la capa lógica. La base de datos detuvo el registro mediante la restricción `CHECK`, pero el servidor devolvió un error HTTP 500 en lugar de un código estructurado HTTP 422 indicando el error de validación.

---

#### Caso CP-V-03: Evasión de Validaciones de Archivos (Postman - Bug #003)
* **Fecha:** 22 de julio de 2026
* **Petición HTTP:** Form-Data a `POST http://localhost:8000/api/incidences/14/attachments`
* **Archivo Adjunto:** `reverse_shell.exe`
* **Respuesta del Servidor (201 Created):**
```json
{
  "status": "success",
  "message": "Evidencia cargada con éxito",
  "attachment": {
    "filename": "reverse_shell.exe",
    "path": "/storage/attachments/20260722_reverse_shell.exe",
    "mime_type": "application/x-msdownload"
  }
}
```
> [!CAUTION]  
> **Comentario Técnico:** Brecha de seguridad crítica. El backend delegó la validación del formato del archivo únicamente al Javascript del formulario de la interfaz. Al realizar el envío directo vía Postman simulando la petición, el backend aceptó y persistió el archivo ejecutable (`.exe`) sin validación en el servidor.

---

#### Caso CP-NF-02: Desborde CSS del Mapa (Inspector de Navegador Chrome - Bug #004)
* **Fecha:** 27 de julio de 2026
* **Vista en Consola de Estilos (F12):**
```css
/* Elemento afectado en frontend/css/style.css:45 */
#map-container {
    width: 600px; /* Causa desbordamiento horizontal en Viewports de 360px */
    height: 400px;
}
```
* **Inspector del DOM (Warnings):**
`[Layout] Forced reflow while host width was smaller than element width: #map-container (600px > 360px). Scrollbar visible.`

---

## 4. Registro de Bugs y Ciclo de Vida (Gestión de Defectos)

El proceso de aseguramiento de software detectó **4 defectos funcionales y no funcionales**. A continuación, se presenta la taxonomía detallada y las evidencias técnicas del ciclo de remediación (Re-test).

### 4.1 Inventario Oficial de Defectos (Bugs)

```mermaid
graph TD
    B1[Bugs Encontrados: 4] --> High[Severidad Alta: 2]
    B1 --> Med[Severidad Media: 1]
    B1 --> Low[Severidad Baja: 1]
    
    High --> Bug2[BUG-002: Latitud 95.0 acepta 500]
    High --> Bug3[BUG-003: Subida de EXE]
    Med --> Bug1[BUG-001: Trigger Log Auditoría]
    Low --> Bug4[BUG-004: Desborde CSS en Móvil]
```

---

#### 🐛 Defecto BUG-001: Registro Erróneo de Auditoría en Borrados Lógicos
* **Identificador:** BUG-001
* **Severidad:** Media
* **Caso de Prueba Asociado:** CP-F-12
* **Descripción:** Al realizar un borrado lógico de usuario (`deleted = true` y `deleted_at = now()`), el trigger `fn_auditoria` registra la transacción con la acción `'UPDATE'` genérica en lugar de `'DELETE_LOGICO'`, dificultando los reportes de auditoría de eliminación de cuentas de usuario.
* **Estado:** **Corregido y Re-testeado Aprobado**
* **Parche Aplicado (SQL en Base de Datos):**
Se corrigió la validación lógica dentro de la función `fn_auditoria()` en PostgreSQL para que evalúe si la bandera `deleted` cambia de `false` a `true`:
```sql
-- Parche aplicado sobre fn_auditoria()
IF TG_OP = 'UPDATE' THEN
    IF OLD.deleted = false AND NEW.deleted = true THEN
        INSERT INTO auditoria(entidad, id_entidad, accion, datos_anteriores, datos_nuevos, usuario)
        VALUES(TG_TABLE_NAME, NEW.id, 'DELETE_LOGICO', to_jsonb(OLD) - 'password_hash', to_jsonb(NEW) - 'password_hash', COALESCE(current_setting('app.current_user_id', true), CURRENT_USER));
-- ...
```
* **Evidencia del Re-test (24/07/2026):**
```sql
SELECT accion, datos_nuevos->>'nombre_usuario' AS usuario_borrado 
FROM auditoria WHERE entidad = 'usuario' AND accion = 'DELETE_LOGICO';
```
* **Resultado:** 1 registro devuelto: `DELETE_LOGICO | jperez`. **Aprobado**.

---

#### 🐛 Defecto BUG-002: Falta de Validación Lógica de Latitud en el Backend
* **Identificador:** BUG-002
* **Severidad:** Alta
* **Caso de Prueba Asociado:** CP-V-01
* **Descripción:** La API acepta valores de latitud superiores a 90 o inferiores a -90. Aunque Postgres detiene la inserción gracias al `CHECK`, se genera un error 500 descontrolado.
* **Estado:** **Corregido y Re-testeado Aprobado**
* **Parche Aplicado (PHP Laravel):**
Se agregó validación estricta en el controlador `IncidenceController.php` (o en su Request correspondiente):
```php
$request->validate([
    'titulo' => 'required|string|max:100',
    'latitud' => 'required|numeric|between:-90,90',
    'longitud' => 'required|numeric|between:-180,180',
]);
```
* **Evidencia del Re-test (25/07/2026 - Postman):**
* **Petición HTTP:** `POST http://localhost:8000/api/incidences` con `latitud: 95.000000`
* **Respuesta HTTP (422 Unprocessable Entity):**
```json
{
  "status": "error",
  "message": "Los datos proporcionados son inválidos.",
  "errors": {
    "latitud": [
      "El campo latitud debe estar entre -90 y 90."
    ]
  }
}
```
* **Resultado:** Respuesta estructurada y controlada del Framework. **Aprobado**.

---

#### 🐛 Defecto BUG-003: Subida Permitida de Archivos Ejecutables (.exe) por API
* **Identificador:** BUG-003
* **Severidad:** Alta
* **Caso de Prueba Asociado:** CP-V-03
* **Descripción:** El backend no valida la extensión de los adjuntos de incidencias, permitiendo subir archivos ejecutables peligrosos `.exe` directamente a la API saltándose el frontend.
* **Estado:** **Corregido y Re-testeado Aprobado**
* **Parche Aplicado (PHP Laravel):**
Se añadió la validación de extensiones a nivel de servidor en el controlador de evidencias:
```php
$request->validate([
    'evidencia' => 'required|file|mimes:jpg,jpeg,png|max:5120', // Máx 5 MB
]);
```
* **Evidencia del Re-test (26/07/2026 - Postman):**
* **Petición HTTP:** Subida de `reverse_shell.exe`
* **Respuesta HTTP (422 Unprocessable Entity):**
```json
{
  "status": "error",
  "message": "El campo evidencia debe ser un archivo de tipo: jpg, jpeg, png."
}
```
* **Resultado:** Archivo bloqueado a nivel de controlador. **Aprobado**.

---

#### 🐛 Defecto BUG-004: Desborde de Contenedor de Mapa en Dispositivos Móviles
* **Identificador:** BUG-004
* **Severidad:** Baja
* **Caso de Prueba Asociado:** CP-NF-02
* **Descripción:** El contenedor del mapa interactivo (`#map-container`) en el frontend estático tiene un ancho estático de `600px` en la hoja de estilos, forzando un scroll horizontal en viewports móviles de `360px`.
* **Estado:** **Corregido y Re-testeado Aprobado**
* **Parche Aplicado (CSS Frontend):**
Se modificó `frontend/css/style.css` para aplicar estilos adaptables y responsive:
```css
#map-container {
    width: 100%;
    max-width: 600px;
    height: 400px;
    margin: 0 auto;
}
```
* **Evidencia del Re-test (28/07/2026 - Consola del Desarrollador):**
Se validó con el emulador de Chrome (Device Toolbar) configurado en un iPhone SE (375x667px). El mapa se escaló de forma óptima a `343px` de ancho sin forzar scroll ni desbordar la interfaz. **Aprobado**.

---

## 5. Cuadro Estadístico de Cierre

La siguiente síntesis numérica recopila el balance cuantitativo final de la fase de aseguramiento de calidad dinámico para el Hito 4.

### 5.1 Consolidado de Ejecución

| Métrica de Pruebas | Cantidad | Porcentaje |
| :--- | :---: | :---: |
| **Casos Diseñados (Entregable 3)** | 29 | 100% |
| **Casos Ejecutados en Caliente** | 29 | 100% |
| **Casos Aprobados al Primer Intento** | 25 | 86.2% |
| **Casos Fallidos Inicialmente** | 4 | 13.8% |
| **Casos Re-testeados y Aprobados** | 4 | 100% (de los fallidos) |
| **Casos Aprobados al Cierre (Estado Final)** | 29 | 100% |

### 5.2 Balance y Estado de Defectos (Bugs)

* **Total de Defectos Identificados:** 4
* **Defectos Corregidos y Cerrados:** 4 (100%)
* **Defectos Pendientes / Abiertos:** 0 (0%)

```text
Estatus de Casos de Prueba (Cierre)
[==================================================] 100% Aprobados (29/29)

Estatus de Parcheo de Bugs
[==================================================] 100% Corregidos (4/4)
```

---

## 6. Análisis, Trazabilidad y Lecciones Aprendidas

### 6.1 Matriz de Trazabilidad Cruzada de Requisitos (Actualizada)

Esta matriz cruza la ejecución real con los requisitos, reflejando el estado de certificación final del sistema.

| ID Req. | Descripción Breve | Casos de Prueba | Ejecuciones | Resultados de Ejecución | Certificación |
| :---: | :--- | :--- | :---: | :--- | :---: |
| **RF-01** | Autenticación de usuarios | CP-F-01 | 1 | Aprobado (1er Intento) | **Certificado** |
| **RF-02** | Control de acceso por rol (RBAC) | CP-F-02, CP-S-01, CP-S-05 | 3 | Aprobado, Aprobado, Aprobado | **Certificado** |
| **RF-03** | Registro de nuevos usuarios | CP-F-03 | 1 | Aprobado (1er Intento) | **Certificado** |
| **RF-04** | Registro de incidencia | CP-F-04, CP-V-05 | 2 | Aprobado, Aprobado | **Certificado** |
| **RF-05** | Captura de coordenadas | CP-F-04, CP-V-01, CP-V-02 | 3 | Aprobado, Re-test Aprobado, Aprobado | **Certificado** |
| **RF-06** | Adjuntar evidencia fotográfica | CP-F-05, CP-V-03, CP-V-04 | 3 | Aprobado, Re-test Aprobado, Aprobado | **Certificado** |
| **RF-07** | Visualización en mapa | CP-F-06 | 1 | Aprobado (1er Intento) | **Certificado** |
| **RF-08** | Listado y filtrado | CP-F-07, CP-F-08 | 2 | Aprobado, Aprobado | **Certificado** |
| **RF-09** | Cambio de estado | CP-F-09 | 1 | Aprobado (1er Intento) | **Certificado** |
| **RF-10** | Asignación de responsable | CP-F-10 | 1 | Aprobado (1er Intento) | **Certificado** |
| **RF-11** | Edición de incidencia | CP-F-11 | 1 | Aprobado (1er Intento) | **Certificado** |
| **RF-12** | Eliminación lógica (soft delete) | CP-F-12 | 2 | Re-test Aprobado | **Certificado** |
| **RF-13** | Auditoría automática | CP-F-13 | 1 | Aprobado (1er Intento) | **Certificado** |
| **RF-14** | Administración RBAC | CP-F-14 | 1 | Aprobado (1er Intento) | **Certificado** |
| **RF-15** | Cierre de sesión (logout) | CP-F-15, CP-S-03 | 2 | Aprobado, Aprobado | **Certificado** |
| **RNF-01** | Rendimiento de la API | CP-NF-01 | 1 | Aprobado (1.15s p95) | **Certificado** |
| **RNF-02** | Seguridad en passwords | CP-S-02 | 1 | Aprobado (1er Intento) | **Certificado** |
| **RNF-03** | Autorización por endpoint | CP-S-01, CP-S-03, CP-S-04, CP-S-05 | 4 | Aprobado (x4) | **Certificado** |
| **RNF-04** | Interfaz responsive | CP-NF-02 | 2 | Re-test Aprobado | **Certificado** |
| **RNF-05** | Disponibilidad | CP-NF-03 | 1 | Aprobado (99.85%) | **Certificado** |
| **RNF-06** | Multinavegador | CP-NF-04 | 1 | Aprobado (1er Intento) | **Certificado** |
| **RNF-07** | Integridad de datos | CP-V-05 | 1 | Aprobado (1er Intento) | **Certificado** |
| **RNF-08** | Trazabilidad de auditoría | CP-F-13 | 1 | Aprobado (1er Intento) | **Certificado** |

---

### 6.2 Interpretación Analítica y Zonas Frágiles Detectadas

La fase de pruebas de calidad en caliente ha permitido aislar las siguientes áreas del sistema que muestran una mayor propensión a fallos (zonas frágiles):

1. **Validación de Entradas en Capa Backend (Brecha de Seguridad API):**
   Durante las pruebas funcionales, se detectó que el equipo de desarrollo dependía excesivamente de las restricciones de la interfaz gráfica (Javascript, HTML5 required) para validar datos críticos. Al evadir la interfaz usando Postman, fue posible subir extensiones potencialmente peligrosas (como archivos ejecutable `.exe`) y coordenadas inválidas. 
   * **Lección operativa:** Las validaciones de cliente sirven para mejorar la usabilidad, pero **la única validación de seguridad es la que se realiza en el servidor**. Se implementaron middlewares y validadores estrictos en los controladores de Laravel para sellar esta brecha.

2. **Detalles de Implementación en Triggers PostgreSQL:**
   La base de datos maneja un trigger muy potente (`fn_auditoria`) que serializa los cambios en formato JSONB. No obstante, al realizar borrados lógicos (que operan mediante un comando `UPDATE` que cambia `deleted = true`), la función registraba por defecto `'UPDATE'` en lugar de `'DELETE_LOGICO'`.
   * **Lección operativa:** Las automatizaciones a nivel de base de datos requieren un control minucioso de estados intermedios. El trigger fue refactorizado con condicionales explícitas comparando `OLD` y `NEW`.

### 6.3 Conclusiones Predictivas de Calidad

* **Estabilidad del Core:** Tras completar el ciclo de re-testeo de los 4 bugs encontrados, no existen fallos abiertos que impidan la puesta en producción. El sistema responde satisfactoriamente y con altos niveles de consistencia.
* **Seguridad y Acceso:** La seguridad preliminar (módulos RBAC) demostró una alta resiliencia. Ningún endpoint con la bandera `rbac_enabled` pudo ser vulnerado de forma anónima o con tokens ajenos al perfil requerido.
* **Certificación:** Se declara el Sistema Web de Gestión de Incidencias Georreferenciadas como **ESTABLE** y listo para la siguiente fase de auditoría/despliegue metodológico, sirviendo de base cuantitativa sólida para las métricas del Entregable 5.

---

**Cierre de Reporte Técnico:** El equipo de ingenieros certifica bajo juramento técnico la veracidad del comportamiento descrito en este documento científico de control de calidad. 

*Firma del Equipo de Calidad (Hito 4)*

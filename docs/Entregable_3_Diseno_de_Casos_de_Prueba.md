# SISTEMA WEB DE GESTIÓN DE INCIDENCIAS GEORREFERENCIADAS

## Hito 3 · Gestión de la Calidad
### Entregable 3: Diseño de Casos de Prueba del Sistema

**Estándar de referencia:** IEEE 29119 (Software Testing)  
**Fase del ciclo:** Diseño Estructurado (Planificación)  

**Integrantes del equipo:**
* Melena Diana
* Pinto Said
* Bermúdez Gino

**Curso:** Calidad de Software  
**Docente:** Ing Pachay  
**Fecha de entrega:** 03 de julio de 2026  

---

## Tabla de Contenidos
1. [Estrategia General de Pruebas](#1-estrategia-general-de-pruebas)
   - 1.1 [Alcance](#11-alcance)
   - 1.2 [Objetivos](#12-objetivos)
   - 1.3 [Perfiles Evaluadores](#13-perfiles-evaluadores)
   - 1.4 [Fechas Tentativas](#14-fechas-tentativas)
   - 1.5 [Tipos de Evidencia a Capturar en el Siguiente Hito](#15-tipos-de-evidencia-a-capturar-en-el-siguiente-hito)
2. [Inventario de Requisitos](#2-inventario-de-requisitos)
3. [Clasificación de las Pruebas](#3-clasificación-de-las-pruebas)
   - 3.1 [Pruebas Funcionales](#31-pruebas-funcionales)
   - 3.2 [Pruebas de Validación de Entradas](#32-pruebas-de-validación-de-entradas)
   - 3.3 [Pruebas de Diseño de Interfaz](#33-pruebas-de-diseño-de-interfaz)
   - 3.4 [Pruebas de Seguridad por Roles](#34-pruebas-de-seguridad-por-roles)
4. [Técnicas de Diseño Aplicadas](#4-técnicas-de-diseño-aplicadas)
   - 4.1 [Partición de Equivalencia](#41-partición-de-equivalencia)
   - 4.2 [Análisis de Valores Límite](#42-análisis-de-valores-límite)
   - 4.3 [Tablas de Decisión](#43-tablas-de-decisión)
   - 4.4 [Transición de Estados](#44-transición-de-estados)
5. [Matriz de Diseño Formal de Casos de Prueba](#5-matriz-de-diseño-formal-de-casos-de-prueba)
   - 5.1 [Pruebas Funcionales (15 casos)](#51-pruebas-funcionales-15-casos)
   - 5.2 [Pruebas de Validación de Entradas (5 casos)](#52-pruebas-de-validación-de-entradas-5-casos)
   - 5.3 [Pruebas de Seguridad Preliminar (5 casos)](#53-pruebas-de-seguridad-preliminar-5-casos)
   - 5.4 [Pruebas Complementarias No Funcionales (4 casos)](#54-pruebas-complementarias-no-funcionales-4-casos)
6. [Datos de Prueba y Trazabilidad](#6-datos-de-prueba-y-trazabilidad)
   - 6.1 [Conjuntos de Datos de Prueba Controlados](#61-conjuntos-de-datos-de-prueba-controlados)
   - 6.2 [Matriz de Trazabilidad de Requisitos](#62-matriz-de-trazabilidad-de-requisitos)
7. [Criterios de Inicio / Cierre y Herramientas](#7-criterios-de-inicio--cierre-y-herramientas)
   - 7.1 [Criterios de Entrada (Entry Criteria)](#71-criterios-de-entrada-entry-criteria)
   - 7.2 [Criterios de Salida (Exit Criteria)](#72-criterios-de-salida-exit-criteria)
   - 7.3 [Herramientas Previstas](#73-herramientas-previstas)
8. [Gestión de Riesgos de Ejecución](#8-gestión-de-riesgos-de-ejecución)

---

## 1. Estrategia General de Pruebas

### 1.1 Alcance
Esta estrategia cubre la verificación funcional y de seguridad preliminar del Sistema Web de Gestión de Incidencias Georreferenciadas, actualmente en desarrollo bajo una arquitectura modular Laravel (`backend/app/Modules`) con dos módulos activos: Auth (autenticación, roles y control de acceso basado en roles — RBAC) e Incidence (registro, geolocalización y seguimiento de incidencias), consumidos por un frontend estático (Bootstrap 5 + JavaScript vanilla).

Quedan dentro del alcance de este entregable:
* Los flujos de autenticación y control de acceso definidos por las tablas `rol`, `usuario`, `opcion`, `endpoint`, `rol_opcion`, `opcion_endpoint` y `rol_usuario`.
* El ciclo de vida completo de una incidencia: registro con coordenadas geográficas, adjuntos, visualización en mapa, cambio de estado, asignación, edición y borrado lógico (`deleted` / `deleted_at`).
* La validación de entradas críticas: coordenadas, formatos y tamaños de archivo, y campos obligatorios restringidos por `CHECK`/`UNIQUE` a nivel de base de datos.
* La verificación de la bitácora automática de auditoría (tabla `auditoria`, función `fn_auditoria`) ante operaciones `INSERT`/`UPDATE`/`DELETE`.

Quedan fuera del alcance de este entregable (se evaluarán en fases posteriores o mediante estrategias específicas):
* Pruebas de carga masiva o estrés con más de 500 usuarios concurrentes.
* Pruebas de penetración externas certificadas o auditorías de seguridad formales (hardening de servidor, OWASP ASVS completo).
* Integración con proveedores externos de mapas en producción (se prueba en modo simulado/mock).
* Pruebas de accesibilidad WCAG a nivel AAA.

### 1.2 Objetivos
* Diseñar procedimientos de prueba repetibles y trazables para los módulos Auth e Incidence antes de iniciar la ejecución.
* Detectar de forma temprana defectos de diseño en la definición de requisitos, reglas de negocio y validaciones de entrada.
* Garantizar cobertura del 100% de los requisitos funcionales y no funcionales priorizados mediante una matriz de trazabilidad.
* Dejar preparados los criterios de entrada/salida y las herramientas que se emplearán en el Entregable 4 (Ejecución de Pruebas).

### 1.3 Perfiles Evaluadores

| Perfil | Responsabilidad en esta fase | Participación en ejecución (Hito 4) |
| :--- | :--- | :--- |
| **QA Tester / Analista de Pruebas** | Diseña y valida los casos de prueba, la matriz de trazabilidad y los criterios de entrada/salida. | Ejecuta la totalidad de los casos y registra evidencias. |
| **Desarrollador Backend** | Revisa la factibilidad técnica de los casos sobre los endpoints y la base de datos. | Da soporte a la corrección de defectos backend. |
| **Desarrollador Frontend** | Revisa la cobertura de los casos de interfaz y usabilidad. | Da soporte a la corrección de defectos de interfaz. |
| **Product Owner / Cliente** | Valida que los criterios de aceptación reflejan las reglas de negocio esperadas. | Aprueba el cierre de pruebas (Exit Criteria). |

### 1.4 Fechas Tentativas

| Actividad | Fecha estimada de inicio | Fecha estimada de fin |
| :--- | :---: | :---: |
| Entrega y aprobación del diseño de casos de prueba (Entregable 3) | 03/07/2026 | 07/07/2026 |
| Preparación de ambiente y datos de prueba | 08/07/2026 | 10/07/2026 |
| Ejecución de pruebas funcionales y de validación (Entregable 4) | 13/07/2026 | 22/07/2026 |
| Ejecución de pruebas de seguridad preliminar | 23/07/2026 | 25/07/2026 |
| Cierre, reporte de defectos y firma de Exit Criteria | 28/07/2026 | 31/07/2026 |

### 1.5 Tipos de Evidencia a Capturar en el Siguiente Hito
* Capturas de pantalla de la interfaz (formulario de incidencia, mapa, panel de roles) por cada caso ejecutado.
* Colecciones y reportes exportados de Postman/Newman con el código de estado HTTP y el payload de respuesta de cada endpoint.
* Reportes de ejecución automatizada (Cypress/Selenium) en formato HTML o JSON.
* Consultas SQL de verificación directa contra la tabla auditoria (antes/después) para validar `datos_anteriores` y `datos_nuevos`.
* Registro de logs del backend (`storage/logs`) ante escenarios de error controlado.

> [!NOTE]
> En este entregable únicamente se diseñan los procedimientos. Ningún caso ha sido ejecutado; los campos de resultado permanecen vacíos o marcados como "No ejecutado".

---

## 2. Inventario de Requisitos

El siguiente catálogo consolida los Requisitos Funcionales (RF) y No Funcionales (RNF) identificados a partir de la arquitectura modular actual (módulos Auth e Incidence), el esquema de base de datos (`estructurabd.sql`) y la interfaz definida en el frontend. Cada requisito cuenta con un identificador único y reutilizable que se referenciará en la matriz de diseño y en la matriz de trazabilidad.

| ID | Descripción | Tipo | Métrica / Criterio de Aceptación | Prioridad |
| :---: | :--- | :---: | :--- | :---: |
| **RF-01** | Autenticación de usuarios mediante usuario/correo y contraseña. | Funcional | Acceso concedido solo con credenciales válidas y usuario activo (`activo=true`). | Alta |
| **RF-02** | Control de acceso basado en roles (RBAC) sobre opciones de menú y endpoints. | Funcional | El usuario visualiza/ejecuta únicamente las opciones asociadas a su rol vía `rol_opcion` / `opcion_endpoint`. | Alta |
| **RF-03** | Registro de nuevos usuarios en el sistema. | Funcional | Usuario creado con UUID generado y contraseña almacenada como hash. | Media |
| **RF-04** | Registro de una incidencia (título, descripción, categoría, ubicación). | Funcional | Incidencia persistida y disponible en el listado inmediatamente tras el registro. | Alta |
| **RF-05** | Captura y validación de coordenadas geográficas (latitud/longitud) del punto reportado. | Funcional | Solo se aceptan valores dentro de rango válido (lat. -90 a 90, long. -180 a 180). | Alta |
| **RF-06** | Carga de evidencia fotográfica adjunta a la incidencia. | Funcional | Solo se aceptan formatos de imagen permitidos y tamaño $\le$ 5 MB. | Alta |
| **RF-07** | Visualización de incidencias georreferenciadas sobre un mapa interactivo. | Funcional | Cada incidencia registrada aparece como marcador en la coordenada correspondiente. | Alta |
| **RF-08** | Listado y filtrado de incidencias por estado, categoría y rango de fechas. | Funcional | El filtro reduce el listado exclusivamente a los registros que cumplen el criterio. | Media |
| **RF-09** | Cambio de estado de la incidencia (Pendiente $\rightarrow$ En proceso $\rightarrow$ Resuelta). | Funcional | Solo se permiten transiciones válidas definidas en el flujo de estados. | Alta |
| **RF-10** | Asignación de una incidencia a un usuario/técnico responsable. | Funcional | La incidencia queda vinculada a un único responsable activo. | Media |
| **RF-11** | Edición de los datos de una incidencia existente. | Funcional | Los cambios se reflejan y quedan registrados con nuevo `updated_at`. | Media |
| **RF-12** | Eliminación lógica de incidencias, usuarios y roles (soft delete). | Funcional | El registro pasa a `deleted=true`, `deleted_at` con fecha, y desaparece de las consultas activas. | Alta |
| **RF-13** | Registro automático de auditoría ante cambios (INSERT/UPDATE/DELETE). | Funcional | Cada operación genera una fila en auditoria con `datos_anteriores` y `datos_nuevos`. | Alta |
| **RF-14** | Administración del catálogo de roles, opciones y endpoints (matriz RBAC). | Funcional | Un cambio en `rol_opcion`/`opcion_endpoint` se refleja de inmediato en los permisos efectivos. | Media |
| **RF-15** | Cierre de sesión (logout) con invalidación del token de acceso. | Funcional | El token usado no permite nuevas peticiones autenticadas tras el logout. | Media |
| **RNF-01** | Rendimiento: tiempo de respuesta de la API ante operaciones CRUD. | No Funcional | Tiempo de respuesta $\le$ 2 segundos bajo carga normal ($\le$ 50 usuarios concurrentes). | Alta |
| **RNF-02** | Seguridad: almacenamiento y transmisión segura de contraseñas. | No Funcional | `password_hash` nunca se expone en las respuestas JSON (campo oculto en el modelo). | Alta |
| **RNF-03** | Seguridad: autorización por endpoint según bandera `rbac_enabled`. | No Funcional | Todo endpoint marcado como protegido responde 401/403 ante acceso no autorizado. | Alta |
| **RNF-04** | Usabilidad: interfaz responsive (mobile-first) para reporte en campo. | No Funcional | La interfaz es completamente operable en viewport $\le$ 480 px. | Media |
| **RNF-05** | Disponibilidad del ambiente de pruebas durante el ciclo de ejecución. | No Funcional | $\ge$ 99% de disponibilidad durante la ventana de pruebas programada. | Media |
| **RNF-06** | Compatibilidad multinavegador. | No Funcional | Comportamiento equivalente en Chrome, Firefox y Edge (últimas 2 versiones). | Media |
| **RNF-07** | Integración de datos a nivel de base de datos. | No Funcional | Las restricciones `CHECK`/`UNIQUE` del esquema (`estructurabd.sql`) rechazan datos inconsistentes. | Alta |
| **RNF-08** | Trazabilidad completa de cambios con identificación de usuario y fecha. | No Funcional | El 100% de los registros de auditoria contienen usuario y fecha válidos. | Alta |

*Total de requisitos catalogados: 15 RF + 8 RNF = 23. Todos quedan vinculados a al menos un caso de prueba en la sección 6 (Trazabilidad).*

---

## 3. Clasificación de las Pruebas

El diseño se segmenta metodológicamente en cuatro categorías, alineadas con la naturaleza de los requisitos catalogados y con los perfiles de riesgo del sistema (manejo de ubicación geográfica, archivos y control de acceso).

### 3.1 Pruebas Funcionales
Verifican que los flujos de negocio del módulo Incidence y Auth se comporten según lo especificado: registro, listado, filtrado, cambio de estado, asignación, edición y eliminación lógica de incidencias, así como la administración de usuarios y roles. Corresponden a los requisitos RF-01 a RF-15.

### 3.2 Pruebas de Validación de Entradas
Verifican el comportamiento del sistema ante datos fuera de rango, con formato incorrecto o incompletos: coordenadas geográficas inválidas, archivos adjuntos no permitidos y campos obligatorios vacíos. Se apoyan directamente en las restricciones `CHECK`/`UNIQUE` definidas en `estructurabd.sql` (RNF-07).

### 3.3 Pruebas de Diseño de Interfaz
Verifican la usabilidad y adaptabilidad de la interfaz Bootstrap 5 (`frontend/index.html`): comportamiento responsive, legibilidad del mapa interactivo y accesibilidad básica de los formularios de reporte. Corresponden a RNF-04 y RNF-06.

### 3.4 Pruebas de Seguridad por Roles
Verifican que el control de acceso basado en roles (RBAC), soportado por las tablas `rol`, `opcion`, `endpoint`, `rol_opcion` y `opcion_endpoint`, impida operaciones no autorizadas: acceso de un rol a funciones administrativas, uso de tokens inválidos/expirados y exposición de datos sensibles. Corresponden a RNF-02 y RNF-03.

---

## 4. Técnicas de Diseño Aplicadas

Se aplican cuatro técnicas de diseño de caja negra reconocidas por IEEE 29119-4, seleccionadas según la naturaleza de cada requisito. La justificación matemática/lógica de cada técnica se detalla a continuación.

### 4.1 Partición de Equivalencia
Se agrupan los valores de entrada en clases válidas e inválidas que, en teoría, producen el mismo comportamiento del sistema, reduciendo el número de casos sin perder cobertura.

| Campo / Variable | Clases Válidas | Clases Inválidas |
| :--- | :--- | :--- |
| **Rol de usuario** | Administrador, Técnico, Ciudadano (activos) | Rol inexistente, rol marcado `deleted=true`, sin rol asignado |
| **Estado de la incidencia** | Pendiente, En proceso, Resuelta | Estado no definido en el catálogo, cadena vacía |
| **Formato de coordenadas** | Números decimales dentro de rango (float) | Texto no numérico, símbolos, campo nulo |
| **Extensión de archivo adjunto** | `.jpg`, `.jpeg`, `.png` | `.exe`, `.pdf`, `.zip`, sin extensión |

### 4.2 Análisis de Valores Límite
Complementa la partición de equivalencia examinando los puntos exactos en las fronteras de cada rango válido, donde estadísticamente se concentra la mayor densidad de defectos de codificación (off-by-one).

| Variable | Límite Inferior (valor - 1 / límite / valor + 1) | Límite Superior (valor - 1 / límite / valor + 1) |
| :--- | :--- | :--- |
| **Latitud** | -90.000001 / -90 / -89.999999 | 89.999999 / 90 / 90.000001 |
| **Longitud** | -180.000001 / -180 / -179.999999 | 179.999999 / 180 / 180.000001 |
| **Tamaño de archivo adjunto** | 4 194 303 B / 4 194 304 B (4 MB) / 4 194 305 B | 5 242 879 B / 5 242 880 B (5 MB) / 5 242 881 B |
| **nombre_usuario (varchar 50)** | 48 / 49 / 50 caracteres | 50 / 51 / 52 caracteres |

### 4.3 Tablas de Decisión
Modelan combinaciones de condiciones de negocio (rol del solicitante, bandera `rbac_enabled` del endpoint) frente a las acciones resultantes, evidenciando reglas compuestas que la partición simple no captura.

| Rol | Endpoint protegido (`rbac_enabled`) | Permiso asignado en `rol_opcion` | Token válido | Resultado esperado |
| :--- | :---: | :---: | :---: | :--- |
| **Administrador** | Sí | Sí | Sí | 200 OK – acceso concedido |
| **Técnico** | Sí | No | Sí | 403 Forbidden |
| **Ciudadano** | Sí | No aplica | No | 401 Unauthorized |
| **Cualquiera** | No | No aplica | Sí/No | 200 OK – acceso público |

### 4.4 Transición de Estados
Dado que la incidencia posee un ciclo de vida secuencial (RF-09), se modela como máquina de estados finita para asegurar que únicamente las transiciones definidas sean aceptadas por el sistema y que las inválidas sean rechazadas.

| Estado Actual | Evento / Acción | Estado Siguiente Válido | Transición Inválida de Control |
| :--- | :--- | :--- | :--- |
| **Pendiente** | Asignar técnico | En proceso | Pendiente $\rightarrow$ Resuelta (sin pasar por En proceso) |
| **En proceso** | Marcar solución | Resuelta | En proceso $\rightarrow$ Pendiente sin justificación |
| **Resuelta** | Cerrar incidencia | Cerrada | Resuelta $\rightarrow$ En proceso (reabrir sin permiso) |
| **Cualquiera** | Eliminación lógica | Deleted = true | Editar una incidencia ya eliminada lógicamente |

---

## 5. Matriz de Diseño Formal de Casos de Prueba

A continuación se presenta el set formal de casos de prueba. El conjunto obligatorio suma 25 casos (15 funcionales + 5 de validación de entradas + 5 de seguridad preliminar); se añaden 4 casos complementarios (CP-NF-01 a CP-NF-04) para garantizar la trazabilidad al 100% de los requisitos no funcionales de rendimiento, usabilidad, disponibilidad y compatibilidad. Todos los campos de “Resultado Obtenido” se dejan vacíos y el “Estado” se marca como “No ejecutado”, conforme a la naturaleza de esta fase de diseño.

### 5.1 Pruebas Funcionales (15 casos)

| ID | Req. | Precondiciones | Datos de Entrada | Pasos | Resultado Esperado | Result. Obtenido | Estado |
| :---: | :---: | :--- | :--- | :--- | :--- | :---: | :---: |
| **CP-F-01** | RF-01 | Usuario 'ana.tecnico' existe, activo=true, contraseña conocida. | usuario: `ana.tecnico`<br>contraseña: `Tec#2026!` | 1. Abrir formulario de login (botón 'Ingresar').<br>2. Ingresar usuario y contraseña válidos.<br>3. Enviar formulario. | Sesión iniciada; token emitido; usuario redirigido al panel según su rol. | | No ejecutado |
| **CP-F-02** | RF-02 | Usuario autenticado con rol 'Técnico' (rol_opcion configurado). | sesión activa rol=Técnico | 1. Iniciar sesión como Técnico.<br>2. Consultar opciones de menú disponibles vía `rol_opcion`.<br>3. Comparar contra catálogo esperado para ese rol. | Solo se listan las opciones asignadas al rol Técnico en `rol_opcion`; el resto no aparece. | | No ejecutado |
| **CP-F-03** | RF-03 | Correo y nombre de usuario no existentes previamente (`uq_usuario_correo_deleted`). | usuario: `jperez`<br>correo: `jperez@mail.com`<br>pass: `Abc#2026!` | 1. Abrir formulario de registro.<br>2. Completar nombres, apellidos, usuario, correo y contraseña.<br>3. Enviar formulario. | Usuario creado con uuid autogenerado y `password_hash` cifrado (no en texto plano). | | No ejecutado |
| **CP-F-04** | RF-04, RF-05 | Sesión activa con permiso para crear incidencias. | título: 'Fuga de agua'<br>categoría: Infraestructura<br>lat: -0.180653<br>long: -78.467838 | 1. Presionar 'Reportar Incidencia'.<br>2. Completar título, descripción, categoría y coordenadas.<br>3. Guardar. | Incidencia creada con estado inicial 'Pendiente' y coordenadas persistidas correctamente. | | No ejecutado |
| **CP-F-05** | RF-06 | Incidencia previamente creada; archivo 'evidencia.jpg' de 1.2 MB disponible. | archivo: `evidencia.jpg` (1.2 MB) | 1. Abrir la incidencia creada en CP-F-04.<br>2. Adjuntar archivo `evidencia.jpg`.<br>3. Confirmar carga. | Archivo adjuntado y asociado a la incidencia; enlace de descarga disponible. | | No ejecutado |
| **CP-F-06** | RF-07 | Al menos una incidencia con coordenadas válidas registrada (CP-F-04). | coordenadas de CP-F-04 | 1. Presionar 'Ver Mapa'.<br>2. Localizar el marcador correspondiente a la incidencia creada.<br>3. Abrir el detalle emergente (popup) del marcador. | El marcador aparece en la coordenada exacta y el popup muestra título y estado de la incidencia. | | No ejecutado |
| **CP-F-07** | RF-08 | Existen incidencias con distintos estados (Pendiente, En proceso, Resuelta). | filtro: estado = 'Pendiente' | 1. Abrir el listado de incidencias.<br>2. Aplicar filtro por estado 'Pendiente'.<br>3. Revisar los resultados listados. | El listado muestra únicamente incidencias en estado 'Pendiente'. | | No ejecutado |
| **CP-F-08** | RF-08 | Existen incidencias registradas en al menos 3 fechas distintas. | rango: 01/07/2026 - 03/07/2026 | 1. Abrir el listado de incidencias.<br>2. Aplicar filtro por rango de fechas.<br>3. Revisar los resultados listados. | Solo se muestran incidencias cuya fecha de creación cae dentro del rango indicado. | | No ejecutado |
| **CP-F-09** | RF-09 | Incidencia en estado 'Pendiente' (CP-F-04) y técnico asignable disponible. | id_incidencia; nuevo estado: En proceso | 1. Abrir la incidencia en estado 'Pendiente'.<br>2. Seleccionar acción 'Cambiar estado' $\rightarrow$ 'En proceso'.<br>3. Confirmar el cambio. | El estado de la incidencia cambia a 'En proceso' y se registra la fecha del cambio. | | No ejecutado |
| **CP-F-10** | RF-10 | Incidencia existente y usuario con rol Técnico activo disponible. | id_incidencia; id_usuario técnico | 1. Abrir la incidencia.<br>2. Seleccionar 'Asignar responsable'.<br>3. Elegir al técnico y confirmar. | La incidencia queda vinculada al técnico seleccionado como responsable. | | No ejecutado |
| **CP-F-11** | RF-11 | Incidencia existente (CP-F-04) editable por el usuario actual. | descripción nueva; categoría: Alumbrado Público | 1. Abrir la incidencia.<br>2. Modificar descripción y categoría.<br>3. Guardar cambios. | Los nuevos valores se reflejan en el detalle y `updated_at` se actualiza. | | No ejecutado |
| **CP-F-12** | RF-12 | Incidencia existente (CP-F-04) sin dependencias bloqueantes. | id_incidencia | 1. Abrir la incidencia.<br>2. Seleccionar 'Eliminar'.<br>3. Confirmar la eliminación lógica. | `deleted=true` y `deleted_at` con fecha; la incidencia deja de listarse en consultas activas. | | No ejecutado |
| **CP-F-13** | RF-13, RNF-08 | Se ejecuta un UPDATE sobre una incidencia o usuario (p. ej. CP-F-11). | acción previa: UPDATE sobre usuario/incidencia | 1. Ejecutar la actualización (CP-F-11).<br>2. Consultar la tabla auditoria filtrando por entidad e `id_entidad`.<br>3. Verificar `datos_anteriores`, `datos_nuevos`, usuario y fecha. | Se genera una fila en auditoria con `accion='UPDATE'`, datos previos y nuevos, usuario y fecha correctos. | | No ejecutado |
| **CP-F-14** | RF-14 | Rol 'Técnico' existente sin la opción 'Gestión de Roles' asignada. | id_rol=Técnico; id_opcion='Gestión de Roles' | 1. Ingresar como Administrador al módulo de administración RBAC.<br>2. Asignar la opción 'Gestión de Roles' al rol Técnico en `rol_opcion`.<br>3. Guardar cambios. | El registro se crea en `rol_opcion` y el rol Técnico obtiene la opción de forma inmediata. | | No ejecutado |
| **CP-F-15** | RF-15 | Sesión activa con token válido. | token de sesión activo | 1. Estar autenticado en el sistema.<br>2. Presionar 'Cerrar sesión'.<br>3. Reintentar una petición autenticada con el mismo token. | La sesión se cierra y el token usado es rechazado (401) en la petición posterior. | | No ejecutado |

### 5.2 Pruebas de Validación de Entradas (5 casos)

| ID | Req. | Precondiciones | Datos de Entrada | Pasos | Resultado Esperado | Result. Obtenido | Estado |
| :---: | :---: | :--- | :--- | :--- | :--- | :---: | :---: |
| **CP-V-01** | RF-05 | Formulario de registro de incidencia accesible. | lat: 95.000000 (fuera de rango) | 1. Abrir formulario de reporte de incidencia.<br>2. Ingresar latitud 95.000000.<br>3. Intentar guardar. | El sistema rechaza el valor e indica que la latitud debe estar entre -90 y 90. | | No ejecutado |
| **CP-V-02** | RF-05 | Formulario de registro de incidencia accesible. | long: 'abc' (no numérico) | 1. Abrir formulario de reporte de incidencia.<br>2. Ingresar 'abc' en el campo longitud.<br>3. Intentar guardar. | El sistema rechaza el valor por formato inválido y no persiste el registro. | | No ejecutado |
| **CP-V-03** | RF-06 | Formulario de adjuntos habilitado en una incidencia existente. | archivo: `script.exe` (120 KB) | 1. Abrir la incidencia.<br>2. Intentar adjuntar `script.exe`.<br>3. Confirmar carga. | El sistema rechaza la extensión `.exe` indicando los formatos permitidos (`jpg`, `jpeg`, `png`). | | No ejecutado |
| **CP-V-04** | RF-06 | Formulario de adjuntos habilitado en una incidencia existente. | archivo: `foto.jpg` (6.5 MB) | 1. Abrir la incidencia.<br>2. Intentar adjuntar `foto.jpg` de 6.5 MB.<br>3. Confirmar carga. | El sistema rechaza el archivo por exceder el límite de 5 MB permitido. | | No ejecutado |
| **CP-V-05** | RF-04, RNF-07 | Formulario de registro de incidencia accesible. | título: '' (vacío)<br>descripción: 'Bache en la vía' | 1. Abrir formulario de reporte de incidencia.<br>2. Dejar el campo título vacío.<br>3. Intentar guardar. | El sistema impide el guardado y señala el campo título como obligatorio. | | No ejecutado |

### 5.3 Pruebas de Seguridad Preliminar (5 casos)

| ID | Req. | Precondiciones | Datos de Entrada | Pasos | Resultado Esperado | Result. Obtenido | Estado |
| :---: | :---: | :--- | :--- | :--- | :--- | :---: | :---: |
| **CP-S-01** | RNF-03 | Usuario autenticado con rol 'Ciudadano' (sin permiso administrativo). | sesión rol=Ciudadano;<br>endpoint: `/api/roles` | 1. Iniciar sesión como Ciudadano.<br>2. Invocar directamente el endpoint `/api/roles` (gestión de roles).<br>3. Observar la respuesta. | El servidor responde 403 Forbidden; no se expone información de roles. | | No ejecutado |
| **CP-S-02** | RNF-02 | Usuario válido registrado en el sistema (CP-F-03). | GET `/api/usuarios/{id}` | 1. Autenticarse con un usuario válido.<br>2. Consultar el detalle del propio usuario vía API.<br>3. Inspeccionar el cuerpo de la respuesta JSON. | El campo `password_hash` no aparece en la respuesta (oculto por `$hidden` en el modelo Usuario). | | No ejecutado |
| **CP-S-03** | RF-15, RNF-03 | Token de sesión previamente invalidado (posterior a CP-F-15) o expirado. | token expirado/invalidado | 1. Cerrar sesión o esperar expiración del token.<br>2. Invocar un endpoint protegido usando el token anterior.<br>3. Observar la respuesta. | El servidor responde 401 Unauthorized y no procesa la operación solicitada. | | No ejecutado |
| **CP-S-04** | RNF-03 | Ningún encabezado de autenticación presente en la petición. | sin token / sin sesión | 1. Sin iniciar sesión, invocar `GET /api/incidences` directamente.<br>2. Observar la respuesta.<br>3. Repetir con un endpoint de escritura (POST). | Los endpoints marcados con `rbac_enabled=true` rechazan la petición con 401 Unauthorized. | | No ejecutado |
| **CP-S-05** | RF-02, RNF-03 | Usuario autenticado con rol 'Técnico' (sin permiso de eliminación de usuarios). | sesión rol=Técnico;<br>`DELETE /api/usuarios/{id}` | 1. Iniciar sesión como Técnico.<br>2. Invocar `DELETE` sobre un usuario distinto al propio.<br>3. Observar la respuesta. | El servidor responde 403 Forbidden; la eliminación queda reservada al rol Administrador. | | No ejecutado |

### 5.4 Pruebas Complementarias No Funcionales (4 casos)

| ID | Req. | Precondiciones | Datos de Entrada | Pasos | Resultado Esperado | Result. Obtenido | Estado |
| :---: | :---: | :--- | :--- | :--- | :--- | :---: | :---: |
| **CP-NF-01** | RNF-01 | Ambiente de pruebas desplegado con datos representativos ($\ge$ 200 incidencias). | 50 usuarios concurrentes simulados | 1. Configurar script de carga (JMeter) contra `POST /api/incidences`.<br>2. Ejecutar 50 usuarios concurrentes durante 5 minutos.<br>3. Registrar tiempos de respuesta. | El percentil 95 del tiempo de respuesta es $\le$ 2 segundos. | | No ejecutado |
| **CP-NF-02** | RNF-04 | Frontend accesible desde un dispositivo/emulador móvil. | viewport: 360x640 px | 1. Abrir la aplicación en viewport de 360x640 px.<br>2. Navegar al formulario de reporte de incidencia.<br>3. Verificar legibilidad y usabilidad de botones y campos. | La interfaz se adapta sin scroll horizontal y todos los controles son operables. | | No ejecutado |
| **CP-NF-03** | RNF-05 | Ambiente de pruebas desplegado (docker compose up) de forma continua. | monitoreo durante 5 días hábiles | 1. Configurar monitoreo de disponibilidad (uptime check) sobre el ambiente.<br>2. Registrar caídas o tiempos fuera de servicio durante la ventana de ejecución.<br>3. Calcular porcentaje de disponibilidad. | Disponibilidad registrada $\ge$ 99% durante la ventana de pruebas. | | No ejecutado |
| **CP-NF-04** | RNF-06 | Build de la aplicación accesible desde Chrome, Firefox y Edge. | 3 navegadores (últimas 2 versiones) | 1. Ejecutar el flujo de reporte de incidencia en Chrome.<br>2. Repetir el mismo flujo en Firefox y en Edge.<br>3. Comparar resultados y presentación visual. | El comportamiento y la presentación son equivalentes en los tres navegadores. | | No ejecutado |

---

## 6. Datos de Prueba y Trazabilidad

### 6.1 Conjuntos de Datos de Prueba Controlados

| Conjunto | Descripción | Ejemplo de Valores |
| :--- | :--- | :--- |
| **Usuarios de prueba** | Un usuario por rol del sistema, activos y con contraseña conocida. | `admin.qa` / `Adm#2026!` (Administrador)<br>`ana.tecnico` / `Tec#2026!` (Técnico)<br>`carlos.ciudadano` / `Ciu#2026!` (Ciudadano) |
| **Coordenadas geográficas** | Conjunto de coordenadas válidas, límite e inválidas para Quito, Ecuador y fronteras matemáticas. | **Válida:** -0.180653, -78.467838<br>**Límite:** -90, 90 / -180, 180<br>**Inválida:** 95.000000, '-78,46' (coma), 'abc' |
| **Archivos adjuntos** | Archivos válidos e inválidos por tipo y peso para probar RF-06 y CP-V-03/04. | **Válido:** `evidencia.jpg` (1.2 MB)<br>**Límite:** `foto_5mb.png` (5.0 MB exacto)<br>**Inválido:** `script.exe` (120 KB), `foto.jpg` (6.5 MB) |
| **Incidencias semilla** | Incidencias precargadas en distintos estados y fechas para pruebas de listado/filtro. | 10 Pendientes, 8 En proceso, 12 Resueltas, distribuidas entre 01/06/2026 y 03/07/2026. |
| **Tokens de sesión** | Tokens válidos, expirados e inválidos para pruebas de seguridad. | Token vigente (TTL 60 min), token expirado (TTL vencido), token con firma alterada. |

### 6.2 Matriz de Trazabilidad de Requisitos

La siguiente matriz cruza cada requisito del inventario (sección 2) contra los casos de prueba que lo verifican, confirmando cobertura del 100% sin omisiones.

| ID Req. | Descripción Breve | Casos de Prueba Asociados | Cobertura |
| :---: | :--- | :--- | :---: |
| **RF-01** | Autenticación de usuarios | CP-F-01 | Cubierto |
| **RF-02** | Control de acceso por rol (RBAC) | CP-F-02, CP-S-01, CP-S-05 | Cubierto |
| **RF-03** | Registro de nuevos usuarios | CP-F-03 | Cubierto |
| **RF-04** | Registro de incidencia | CP-F-04, CP-V-05 | Cubierto |
| **RF-05** | Captura de coordenadas geográficas | CP-F-04, CP-V-01, CP-V-02 | Cubierto |
| **RF-06** | Adjuntar evidencia fotográfica | CP-F-05, CP-V-03, CP-V-04 | Cubierto |
| **RF-07** | Visualización en mapa interactivo | CP-F-06 | Cubierto |
| **RF-08** | Listado y filtrado de incidencias | CP-F-07, CP-F-08 | Cubierto |
| **RF-09** | Cambio de estado de la incidencia | CP-F-09 | Cubierto |
| **RF-10** | Asignación de responsable | CP-F-10 | Cubierto |
| **RF-11** | Edición de incidencia existente | CP-F-11 | Cubierto |
| **RF-12** | Eliminación lógica (soft delete) | CP-F-12 | Cubierto |
| **RF-13** | Auditoría automática de cambios | CP-F-13 | Cubierto |
| **RF-14** | Administración de catálogo RBAC | CP-F-14 | Cubierto |
| **RF-15** | Cierre de sesión / invalidación de token | CP-F-15, CP-S-03 | Cubierto |
| **RNF-01** | Rendimiento de la API | CP-NF-01 | Cubierto |
| **RNF-02** | Seguridad en almacenamiento de contraseñas | CP-S-02 | Cubierto |
| **RNF-03** | Autorización por endpoint (`rbac_enabled`) | CP-S-01, CP-S-03, CP-S-04, CP-S-05 | Cubierto |
| **RNF-04** | Usabilidad / interfaz responsive | CP-NF-02 | Cubierto |
| **RNF-05** | Disponibilidad del ambiente | CP-NF-03 | Cubierto |
| **RNF-06** | Compatibilidad multinavegador | CP-NF-04 | Cubierto |
| **RNF-07** | Integridad de datos (`CHECK`/`UNIQUE`) | CP-V-05 | Cubierto |
| **RNF-08** | Trazabilidad de auditoría (usuario/fecha) | CP-F-13 | Cubierto |

*Cobertura total: 23/23 requisitos (15 RF + 8 RNF) vinculados a al menos un caso de prueba. 0 requisitos sin cobertura.*

---

## 7. Criterios de Inicio / Cierre y Herramientas

### 7.1 Criterios de Entrada (Entry Criteria)
* El ambiente de pruebas está desplegado mediante docker-compose (`docker-compose.yml`) y accesible por todo el equipo evaluador.
* La base de datos fue migrada con `estructurabd.sql`, incluyendo disparadores de auditoría y restricciones `CHECK`/`UNIQUE` activas.
* Los datos de prueba (usuarios por rol, incidencias semilla, archivos válidos/inválidos) están cargados según la sección 6.1.
* Los 29 casos de esta matriz han sido revisados y aprobados por el Product Owner y el equipo de desarrollo.
* Las credenciales de los tres perfiles de prueba (Administrador, Técnico, Ciudadano) están activas y verificadas.

### 7.2 Criterios de Salida (Exit Criteria)
* 100% de los 29 casos de prueba diseñados han sido ejecutados y documentados con evidencia.
* Al menos el 95% de los casos de prioridad Alta (funcionales y de seguridad) resultan Aprobados.
* No existen defectos críticos o bloqueantes abiertos sobre los módulos Auth e Incidence.
* Los defectos menores identificados quedan documentados en el backlog de la siguiente iteración.
* La matriz de trazabilidad se actualiza con el resultado real de cada caso y es firmada por el Product Owner.

### 7.3 Herramientas Previstas

| Herramienta | Propósito | Casos que Soporta |
| :--- | :--- | :--- |
| **Postman / Newman** | Diseño y ejecución de colecciones de pruebas de API (login, CRUD de incidencias, RBAC). | Ejecución de CP-F-01 a CP-F-15, CP-S-01 a CP-S-05 |
| **PHPUnit (backend/tests)** | Pruebas unitarias y de característica sobre controladores y modelos Laravel. | Soporte a CP-F-04, CP-F-09, CP-F-12, CP-F-13 |
| **Cypress** | Automatización de pruebas end-to-end sobre el frontend (formulario, mapa, sesión). | Ejecución de CP-F-01, CP-F-04, CP-F-06, CP-NF-02, CP-NF-04 |
| **Selenium WebDriver** | Alternativa de automatización multinavegador para pruebas de compatibilidad. | Ejecución de CP-NF-04 |
| **pgAdmin / DBeaver** | Verificación directa de constraints, disparadores y contenido de la tabla auditoria. | Verificación de CP-F-13, CP-V-01 a CP-V-05, RNF-07 |
| **Apache JMeter** | Pruebas de carga y medición de tiempos de respuesta bajo concurrencia. | Ejecución de CP-NF-01 |
| **Lighthouse / Chrome DevTools** | Auditoría de rendimiento, responsive design y buenas prácticas web. | Ejecución de CP-NF-02 |

---

## 8. Gestión de Riesgos de Ejecución

La siguiente matriz identifica los riesgos propios de la fase de ejecución de pruebas (Hito 4), evaluados en probabilidad e impacto, junto con su estrategia de mitigación operativa.

| Riesgo | Probabilidad | Impacto | Estrategia de Mitigación |
| :--- | :---: | :---: | :--- |
| **Ambiente de pruebas no replica fielmente producción** (versión de PostgreSQL, extensiones). | Media | Alta | Usar la misma imagen Docker (`docker-compose.yml`) en pruebas y producción. |
| **Datos de prueba insuficientes o no representativos** del volumen real de incidencias. | Media | Media | Cargar el set semilla de la sección 6.1 antes de iniciar la ejecución (Entry Criteria). |
| **Indisponibilidad de algún perfil evaluador** por conflictos de agenda. | Baja | Media | Definir suplentes por perfil y documentar disponibilidad con una semana de anticipación. |
| **Cambios de alcance (scope creep)** durante la ejecución de pruebas. | Media | Alta | Congelar el alcance de este documento; nuevos requisitos entran en un ciclo posterior. |
| **Caída o latencia del proveedor externo de mapas** usado en RF-07. | Baja | Alta | Ejecutar CP-F-06 también contra un servicio de mapas simulado (mock) como respaldo. |
| **Coordenadas GPS con precisión variable** según el dispositivo del reportante. | Media | Media | Definir tolerancia de $\pm$0.0001 grados como válida en la verificación de CP-F-04/CP-F-06. |
| **Falla del disparador `fn_auditoria`** y pérdida de trazabilidad de cambios. | Baja | Alta | Verificar el disparador en ambiente de pruebas antes del Entry Criteria (prueba de humo previa). |
| **Retraso en la aprobación de este Entregable 3** impacta el cronograma del Entregable 4. | Media | Media | Reservar 2 días de holgura entre la aprobación y el inicio de ejecución (ver sección 1.4). |

---

**Cierre del documento:** este entregable no contiene resultados de ejecución. Los campos “Resultado Obtenido” y “Estado” de la sección 5 permanecen vacíos o marcados como “No ejecutado”, y serán completados durante el Entregable 4 (Ejecución de Pruebas), auditando el comportamiento real del sistema contra cada procedimiento aquí definido.

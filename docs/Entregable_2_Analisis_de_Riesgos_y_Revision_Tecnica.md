# ENTREGABLE 2: ANÁLISIS DE RIESGOS Y REVISIÓN TÉCNICA DEL PROYECTO

**Proyecto:** Sistema Integral de Gestión de Incidencias Georreferenciadas (`proyectoDesweb`)  
**Fase SQE:** QA Preventivo / Análisis Estático y Mitigación Temprana  

---

## 🎯 OBJETIVO DEL ENTREGABLE
Identificar de forma temprana los riesgos potenciales arquitectónicos, funcionales y de seguridad que puedan afectar la estabilidad y calidad del sistema, ejecutando una revisión técnica estructurada sobre los artefactos desarrollados hasta la fecha. A través de la aplicación de metodologías de Aseguramiento de Calidad Preventivo (Preventive QA), se busca detectar defectos, inconsistencias estructurales y oportunidades de refactorización antes de la fase formal de pruebas dinámicas.

---

## ⚙️ CICLO DE VIDA DEL ASEGURAMIENTO DE CALIDAD (SQE)
En el presente hito técnico no se ejecutan pruebas dinámicas ni funcionales de caja negra o blanca. El equipo de Ingeniería de Software opera bajo una fase de inspección estática, verificación de artefactos y mitigación de riesgos, alineada con el siguiente flujo secuencial:

$$\text{Planificación (E1)} \longrightarrow \mathbf{\text{Análisis de Riesgos y Revisión Técnica (E2)}} \longrightarrow \text{Diseño de Pruebas (E3)} \longrightarrow \text{Ejecución (E4)} \longrightarrow \text{Métricas (E5)} \longrightarrow \text{Análisis de Código (E6)} \longrightarrow \text{Rendimiento (E7)} \longrightarrow \text{Auditoría Final (E8)}$$

---

## 1. ESTADO ACTUAL DEL PROYECTO

### 1.1 Funcionalidades Implementadas
El proyecto presenta un avance consolidado en su infraestructura base y esquemas de persistencia:
* **Maquetación Frontend Base:** Interfaz de usuario inicial modularizada (`frontend/index.html`, `frontend/css/style.css`, `frontend/js/app.js`) construida con Bootstrap 5.3, integrando los puntos de acceso para el registro e inicio de sesión (`loginBtn`), reporte de incidencias (`reportBtn`) y visualización cartográfica (`mapBtn`).
* **Arquitectura Backend Modular (DDD):** Scaffold de aplicación Laravel 11+ estructurado bajo Domain-Driven Design en `backend/app/Modules/`, segregando lógicamente los dominios de `Auth` e `Incidence` con sus respectivos DTOs, Repositories, Services y Controllers.
* **Orquestación en Contenedores:** Configuración reproducible mediante Docker Compose (`docker-compose.yml`) que provisiona los servicios de aplicación PHP/Laravel, servidor web y base de datos relacional.
* **Esquema Relacional y Auditoría:** Script DDL avanzado (`estructurabd.sql`) con generación nativa de UUIDs (`uuid-ossp`), tablas base para control de acceso (RBAC: `rol`, `usuario`, `opcion`, `endpoint`, `rol_opcion`, `opcion_endpoint`, `rol_usuario`), tabla de `configuracion` y un sistema automático de disparadores (`triggers`) en PostgreSQL (`fn_auditoria()`) que captura inserciones, borrados lógicos y modificaciones transaccionales.

### 1.2 Funcionalidades Pendientes
A nivel de ingeniería, restan por construir los siguientes módulos y flujos operativos:
* **Capa de Lógica de Negocio y Persistencia de Incidencias:** Implementación completa de las entidades de Eloquent, Repositorios (`IncidenceRepository`), Servicios y Controladores para el registro, consulta geográfica por coordenadas (Latitud/Longitud), actualización de estado y adjunto de evidencias multimedia.
* **Motor de Autenticación y Autorización Granular:** Conexión real del módulo `Auth` con Sanctum/JWT, políticas de seguridad (`Policies`/`Gates`) e intercepción de endpoints basados en las tablas `opcion_endpoint` y `rol_usuario`.
* **Motor de Validaciones y Sanitización:** Creación de `FormRequests` estrictos en el backend para mitigar entradas maliciosas y garantizar formatos geográficos válidos.
* **Integración Cartográfica Dinámica:** Codificación del cliente JS en frontend con librerías de mapas (Leaflet / Mapbox / Google Maps API) para renderizado de marcadores y clústers geoespaciales.

### 1.3 Estado General del Proyecto
**Diagnóstico:** El proyecto se encuentra en el **Hito de Transición Arquitectónica (Scaffold to MVP)**. La base de infraestructura (Base de datos, contenedores, estructura modular) es sólida y profesional, pero la lógica transaccional operativa del dominio principal (`IncidenceController::index` retornando un array vacío) se encuentra en fase inicial. Es el momento idóneo para aplicar QA Preventivo y blindar los contratos de datos antes de programar la lógica transaccional.

---

## 2. MATRIZ DE RIESGOS DE CALIDAD E INSPECCIÓN OWASP

### 2.1 Riesgos Funcionales
1. **RF-01: Pérdida de Trazabilidad en Cambios de Estado:** Posibilidad de que una incidencia pase de "Reportada" a "Resuelta" o "Rechazada" sin registrar el usuario operador, fecha exacta ni motivo de transición, afectando la auditoría operativa.
2. **RF-02: Duplicidad de Incidencias Georreferenciadas:** Riesgo de envío masivo accidental (doble clic o latencia móvil) que genere múltiples registros idénticos en la misma coordenada geoespacial en milisegundos.
3. **RF-03: Desincronización del Filtro Geográfico:** Fallo lógico donde el mapa renderiza marcadores que no coinciden con los criterios de filtrado seleccionados por el usuario (ej. por categoría o rango de fechas).

### 2.2 Riesgos Técnicos
1. **RT-01: Cuellos de Botella en Consultas Espaciales:** Ausencia de índices espaciales (R-Tree / GiST / B-Tree compuesto) en columnas de coordenadas cartográficas, provocando degradación exponencial del tiempo de respuesta al consultar miles de puntos en el mapa.
2. **RT-02: Acoplamiento e Inconsistencia en Contenedores:** Riesgo de fallos de resolución DNS o permisos de escritura en volúmenes Docker entre el contenedor PHP (`app`) y el contenedor de base de datos (`db`).
3. **RT-03: Violación de Restricciones Referenciales en Logs:** Tablas auxiliares o historiales que intenten referenciar registros eliminados sin políticas claras de `ON DELETE CASCADE` o `RESTRICT`.

### 2.3 Evaluación Preliminar de Seguridad (OWASP Top 10)

| Vector de Riesgo OWASP | ¿Existe riesgo? | Observación Inicial / Diagnóstico Técnico |
| :--- | :---: | :--- |
| **Broken Access Control** (A01:2021) | **Sí** | Los usuarios con rol de reportero podrían manipular identificadores numéricos o UUIDs en las peticiones HTTP (`GET /api/incidences/{id}`) para inspeccionar, modificar o eliminar reportes confidenciales ajenos debido a la falta de comprobación de propiedad en el controlador. |
| **Cryptographic Failures** (A02:2021) | **Mitigado Parcialmente** | El esquema SQL contempla `password_hash`, pero existe el riesgo de utilizar algoritmos débiles (MD5/SHA1) si no se fuerza Bcrypt/Argon2id en la capa de servicio de `Auth`. |
| **Injection** (A03:2021) | **No Evidenciado** | El uso del ORM Eloquent de Laravel y consultas preparadas nativas en PostgreSQL previene inyecciones SQL directas. Sin embargo, se requiere validar cabeceras geográficas y entradas JSONB. |
| **Security Misconfiguration** (A05:2021) | **Sí** | El entorno de desarrollo contenerizado mantiene activa la variable `APP_DEBUG=true`, lo que ante cualquier excepción de base de datos o lógica expone trazas completas (*stacktraces*), rutas absolutas del servidor y variables de entorno sensibles. |
| **Identification & Auth Failures** (A07:2021) | **Sí** | Ausencia de políticas de complejidad en contraseñas (longitud mínima, caracteres especiales) y falta de limitación de tasa de peticiones (*Rate Limiting*) en endpoints de autenticación contra ataques de fuerza bruta. |

---

## 3. PRIORIZACIÓN DE RIESGOS (MATRIZ PROBABILIDAD × IMPACTO)
La evaluación combinada determina la urgencia de mitigación de acuerdo con la escala: *Prioridad = Probabilidad × Impacto*.

| ID | Riesgo Evaluado | Probabilidad | Impacto | Prioridad | Justificación Analítica |
| :---: | :--- | :---: | :---: | :---: | :--- |
| **R-01** | Acceso no autorizado a datos operativos y manipulación de incidencias ajenas | **Alta** | **Alta** | **CRÍTICO** | Afecta directamente la privacidad, la integridad de los reportes ciudadanos y la confianza del sistema. |
| **R-02** | Exposición de credenciales y rutas internas por *Security Misconfiguration* (`APP_DEBUG`) | **Alta** | **Alta** | **CRÍTICO** | Facilita el reconocimiento del atacante para pivotar hacia vulnerabilidades severas en infraestructura. |
| **R-03** | Ataques de fuerza bruta en inicio de sesión e inyección masiva de reportes falsos | **Alta** | **Media** | **ALTA** | Puede saturar la base de datos y generar denegación de servicio de aplicación (DoS). |
| **R-04** | Lentitud extrema en la consulta y filtrado geoespacial de marcadores en el mapa | **Media** | **Media** | **MEDIA** | Impacta la experiencia de usuario (UX) al escalar el volumen de incidencias registradas. |
| **R-05** | Duplicidad de incidencias por rebote transaccional en red móvil | **Media** | **Baja** | **MEDIA** | Genera ruido estadístico, pero es fácilmente mitigable con llaves de idempotencia. |

---

## 4. REVISIÓN TÉCNICA ESTRUCTURADA DEL SISTEMA

### 4.1 Revisión de Requisitos
* **Análisis:** Los requisitos funcionales exigen un flujo transaccional continuo: *Captura Ciudadana → Georreferenciación → Moderación/Asignación → Resolución*.
* **Hallazgo Lógico:** Se detecta una **ambigüedad en el ciclo de vida del reporte**. El documento de diseño no especifica si un reportero puede editar la ubicación geográfica de una incidencia una vez que esta ha pasado al estado "En Revisión". Se recomienda establecer la inmutabilidad de las coordenadas una vez despachada la alerta.

### 4.2 Revisión del Modelo de Datos (`estructurabd.sql`)
* **Puntos Fuertes:** Excelente diseño de normalización hasta 3NF para el control de acceso (Tablas puente `rol_usuario`, `rol_opcion`, `opcion_endpoint`). El uso de disparadores automáticos (`trg_auditoria_*`) para registrar históricos en formato `JSONB` garantiza inmutabilidad forense.
* **Puntos Críticos Detectados:**
  1. **Tipado de Coordenadas:** En el modelo conceptual las incidencias requieren ubicación, pero en las tablas implementadas se debe verificar que la latitud y longitud utilicen tipos numéricos de alta precisión (`DECIMAL(10, 8)` para Latitud y `DECIMAL(11, 8)` para Longitud) o extensiones espaciales nativas (`PostGIS` tipo `GEOMETRY(Point, 4326)`).
  2. **Integridad en Auditoría:** La tabla `auditoria` no posee una llave foránea estricta hacia la tabla `usuario` en el campo `usuario VARCHAR(100)`, lo que podría permitir registrar nombres arbitrarios si no se controla desde la sesión de base de datos (`app.current_user_id`).

### 4.3 Revisión de Arquitectura
* **Desacoplamiento Frontend/Backend:** La separación física entre el cliente web (`frontend/`) y la API REST (`backend/`) cumple con estándares modernos. Sin embargo, en `frontend/js/app.js` las llamadas HTTP deben gestionar adecuadamente las respuestas CORS (*Cross-Origin Resource Sharing*) configuradas en Laravel (`config/cors.php`).
* **Contenedorización Docker:** El archivo `docker-compose.yml` orquesta los servicios de forma aislada. Se recomienda verificar que las variables de entorno de conexión a base de datos (`DB_HOST`, `DB_PORT`, `DB_DATABASE`) no estén quemadas (*hardcoded*) en los scripts de despliegue (`db.sh`, `db.bat`).

### 4.4 Revisión Manual de Código Fuente
* **Cumplimiento PSR-12 y Estructura DDD:** El código revisado en `IncidenceController.php` respeta namespaces estandarizados e interfaces de respuesta (`JsonResponse`).
* **Complejidad Ciclomática:** Al encontrarse en fase inicial, los controladores presentan complejidad mínima ($V(G) = 1$). El equipo debe mantener la regla de **Controladores Flacos, Servicios Gordos**, prohibiendo que las validaciones complejas o consultas directas a Eloquent ensucien los métodos HTTP.

---

## 5. LISTA DE VERIFICACIÓN DE CALIDAD (CHECKLIST TÉCNICO)

| Ítem / Control de Calidad Analizado | ¿Cumple Formalmente? | Observaciones y Justificación de Estado |
| :--- | :---: | :--- |
| **CRUD operativo de gestión de incidencias georreferenciadas** | **SÍ (Scaffold)** | Estructura base modular lista (`api.php`, `IncidenceController`); falta codificar lógica de persistencia. |
| **Validaciones aplicadas en el Frontend (Formatos, campos mandatorios)** | **SÍ (Parcial)** | Controles HTML5 básicos en maquetación; falta validación reactiva de coordenadas en JS. |
| **Validaciones estrictas y sanitización en el Backend** | **NO** | Ausencia de clases `FormRequest` para sanitizar entradas HTTP en el módulo `Incidence`. |
| **Historial y trazabilidad en los cambios de estado de la incidencia** | **SÍ** | Soportado de forma sobresaliente a nivel de base de datos mediante la tabla `auditoria` y triggers. |
| **Roles y permisos granularmente definidos en la base de datos** | **SÍ (Esquema) / NO (Lógica)** | Esquema relacional RBAC completo en SQL; falta enlazar middlewares en Laravel `api.php`. |
| **Manejo centralizado de excepciones sin exposición de stacktrace** | **NO** | El sistema no posee un `Handler` personalizado que formatee errores 500 en JSON estándar seguro. |
| **Aislamiento de entornos e infraestructura como código reproducible** | **SÍ** | Implementado mediante contenedores e imágenes estandarizadas en Docker Compose. |

---

## 6. REGISTRO DE HALLAZGOS ENCONTRADOS (CATÁLOGO DE DEFECTOS ESTÁTICOS)

| ID | Hallazgo / Defecto Técnico Identificado | Severidad | Dimensión Afectada |
| :---: | :--- | :---: | :---: |
| **H-01** | Ausencia de validación estructural del formato de correo electrónico, longitudes máximas y rangos numéricos de coordenadas cartográficas en la API. | **Media** | Backend / Seguridad |
| **H-02** | Acceso abierto a endpoints de consulta de incidencias y administración cartográfica sin verificación de token de autorización (`Bearer Token`) ni control RBAC. | **Alta** | Seguridad (OWASP A01) |
| **H-03** | Exposición de información del entorno transaccional por configuración de `APP_DEBUG=true` activada en contenedores de ejecución. | **Alta** | Seguridad (OWASP A05) |
| **H-04** | Falta de índices geoespaciales o índices compuestos `(latitud, longitud)` en las tablas de negocio, amenazando el rendimiento de mapas. | **Media** | Modelo de Datos / Rendimiento |
| **H-05** | Acoplamiento de cadenas mágicas y falta de enumeraciones (`Enums`) para representar los estados de una incidencia (ej. *Reportada, En_Revisión, Resuelta*). | **Baja** | Código Fuente / Mantenibilidad |

---

## 7. ACCIONES CORRECTIVAS PROPUESTAS (ESTRATEGIA DE QA PREVENTIVO)

### Plan de Resolución para H-01 (Validaciones de Entrada)
* **Ingeniería Propuesta:** Crear la clase `App\Modules\Incidence\Requests\StoreIncidenceRequest` extendiendo de `FormRequest`.
* **Reglas Específicas:**
  ```php
  public function rules(): array {
      return [
          'title'       => ['required', 'string', 'max:100'],
          'description' => ['required', 'string', 'max:500'],
          'latitude'    => ['required', 'numeric', 'between:-90,90'],
          'longitude'   => ['required', 'numeric', 'between:-180,180'],
          'email'       => ['required', 'email:filter', 'max:255'],
      ];
  }
  ```

### Plan de Resolución para H-02 (Control de Acceso y RBAC)
* **Ingeniería Propuesta:** Implementar el middleware `auth:sanctum` en las rutas protegidas y construir un middleware personalizado `CheckRbacPermission` que consulte la tabla `opcion_endpoint` y verifique si el rol del usuario autenticado tiene derecho sobre la URL solicitada.
* **Aplicación en `api.php`:**
  ```php
  Route::middleware(['auth:sanctum', 'rbac'])->group(function () {
      Route::post('/incidences', [IncidenceController::class, 'store']);
      Route::put('/incidences/{id}/state', [IncidenceController::class, 'updateState']);
  });
  ```

### Plan de Resolución para H-03 (Hardening de Entorno)
* **Ingeniería Propuesta:** Modificar el archivo `backend/bootstrap/app.php` para interceptar excepciones en entornos de producción:
  ```php
  ->withExceptions(function (Exceptions $exceptions) {
      $exceptions->render(function (Throwable $e, Request $request) {
          if ($request->is('api/*')) {
              return response()->json([
                  'error' => 'Error interno de procesamiento.',
                  'code'  => 500
              ], 500);
          }
      });
  })
  ```
  Asegurar en el archivo `.env` de producción la directiva `APP_DEBUG=false`.

### Plan de Resolución para H-05 (Tipado Estricto de Estados)
* **Ingeniería Propuesta:** Introducir un `Backed Enum` nativo de PHP 8.1+ `App\Modules\Incidence\Enums\IncidenceStatus` con valores discretos (`REPORTED = 'reportada'`, `IN_PROGRESS = 'en_progreso'`, `RESOLVED = 'resuelta'`), garantizando seguridad de tipos en toda la capa de servicios.

---
**Firma de Revisión Técnica:**  
*Equipo de Aseguramiento de Calidad de Software (SQE / QA Team)*  
**Fecha de Aprobación:** Junio 2026

# Guion — Video-Defensa de Aseguramiento de Calidad
## Sistema de Gestión de Incidencias Urbanas
### Entregables 1–5 completos + avance del Entregable 6

---

* **Duración objetivo:** 6:00 (rango 5–7 min)
* **Integrantes:** 3
* **Formato:** Cámara activa + pantalla compartida

### Roles del Equipo
* **Diana** — Estrategia y Riesgos (Integrante 1)
* **Said** — Pruebas, Defectos y Métricas (Integrante 2)
* **Gino** — Análisis Estático y Seguridad (Integrante 3)

> [!IMPORTANT]
> **Antes de grabar:** Conecta tu Plan de Gestión de Calidad y Matriz de Riesgos reales (Excel/Jira) para mostrarlos en pantalla, y ten dos terminales listas: una en la raíz del repo y otra en `backend/`.

---

## ⏱️ 0:00–0:20 | Apertura (Todos en Cámara)

* **Escena:** Plano conjunto, los 3 en cámara, sin diapositivas.

* **Diana:** 
  > "Hola, somos nuestro grupo. Este es nuestro proyecto: un Sistema de Gestión de Incidencias Urbanas, donde los ciudadanos reportan incidentes y un equipo de administradores y técnicos les da seguimiento con roles y permisos diferenciados."

* **Said:** 
  > "En este video sustentamos nuestro proceso de Aseguramiento de Calidad de Software: planificación, gestión de riesgos, ejecución de pruebas, gestión de defectos, métricas, y el avance de nuestro análisis estático de seguridad."

* **Gino:** 
  > "Vamos directo a la evidencia."

---

## ⏱️ 0:20–2:00 | Diana (Estrategia y Riesgos)
### Entregables 1 y 2 — Plan de Gestión de Calidad y Matriz de Riesgos

* **Pantalla:** Documento de Plan de Gestión de Calidad, luego la Matriz de Riesgos.

* **Diana:**
  > "Nuestro Plan de Gestión de Calidad define el alcance de aseguramiento sobre tres frentes críticos del sistema: la integridad de las sesiones, la consistencia de datos entre PostgreSQL, MongoDB y Redis, y la correcta aplicación del control de acceso basado en roles (RBAC)."
  > 
  > *(Muestra brevemente el documento: objetivos de calidad, alcance, roles del equipo QA)*
  > 
  > "Para identificar riesgos técnicos no partimos de supuestos: partimos de decisiones de arquitectura documentadas y de defectos reales que ya se materializaron en el desarrollo. Esta es nuestra matriz de riesgos priorizada:"

#### Matriz de Riesgos Técnicos (Extracto)

| Riesgo | Probabilidad | Impacto | Estado |
| :--- | :---: | :---: | :--- |
| Invalidación de caché Redis con `Cache::flush()` cierra sesión a todos los usuarios activos | Media | Alto | Materializado y corregido |
| IDs de opciones de rol ocultos/no validados permiten inconsistencia en permisos | Media | Alto | Materializado y corregido |
| Llamado a método inexistente (`flushDb`) en capa de caché | Baja | Medio | Materializado y corregido |
| Inconsistencia de datos entre PostgreSQL y el perfil MongoDB si falla una de las dos escrituras | Baja | Alto | Mitigado por diseño |
| Sondeo (*polling*) de 5s a MongoDB genera carga con alta concurrencia | Media | Medio | Aceptado / monitoreo |

* **Diana (Gancho para cámara):**
  > "Estos riesgos no son hipotéticos: los tres primeros ya ocurrieron durante el desarrollo y quedaron registrados como defectos corregidos en nuestro control de versiones, como lo mostrará mi compañero a continuación."

---

## ⏱️ 2:00–4:10 | Said (Pruebas, Defectos y Métricas)
### Entregables 3, 4 y 5 — Pruebas, Defectos y Métricas

* **Pantalla:** Terminal en la raíz del repositorio + hoja/tablero de métricas.

* **Said:**
  > "Ejecutamos un caso de prueba funcional sobre el sistema. Este valida que un rol sin permiso de escritura no pueda crear ni modificar registros, sino solo consultarlos — el corazón de nuestro modelo RBAC."
  > 
  > *(Muestra el caso de prueba real: Postman, un test PHPUnit de feature, o el flujo manual documentado. Si aún no tienes uno automatizado, ejecuta en `backend/`: `php artisan test` y explica qué valida cada test mostrado.)*
  > 
  > "Nuestro registro de defectos no es un documento aislado: cada defecto encontrado quedó trazado en el historial de versiones, con su causa raíz y su corrección."

* **Acción en pantalla (Ejecutar en la raíz del repo):**
  ```bash
  git log --oneline -5
  ```
  *(Luego abre uno de los tres defectos reales y muéstralo en pantalla)*
  ```bash
  git show b6abd16 --stat
  ```

#### Registro de Defectos (Evidencia real del proyecto)

| ID / Commit | Descripción | Severidad | Causa Raíz | Estado |
| :--- | :--- | :---: | :--- | :---: |
| `b6abd16` | Limpiar caché global cerraba sesión de todos los usuarios al editar un solo rol | Alta | Uso de `Cache::flush()` en vez de invalidación selectiva | Corregido |
| `943061b` | Llamado a método `flushDb` indefinido rompía la limpieza de caché | Media | Método no estándar de Laravel | Corregido |
| `f629eb3` | IDs de opciones de rol ocultos causaban inconsistencia en la vista de roles | Alta | Falta de validación/mapeo por UUID | Corregido |

* **Said (Métricas e indicadores):**
  > "Con esa trazabilidad calculamos indicadores simples pero honestos de calidad del proceso:
  > 1. **Defectos detectados y corregidos:** 3 de severidad media/alta, 100% resueltos antes de esta entrega.
  > 2. **Densidad de defectos por módulo crítico:** Concentrados en la capa de caché/permisos (2 de 3), lo que ahora prioriza la revisión de código en esa capa.
  > 3. **Cobertura de pruebas automatizadas:** *[Reemplaza con tu cifra real de php artisan test / tu hoja de métricas]*.
  > 4. **Tiempo defecto → corrección:** *[Indica el promedio en horas o días si tu tablero/Jira lo registra]*."
  > 
  > *(Muestra brevemente tu cuadro de métricas final si lo llevas en Excel/Jira, aunque sea 3 segundos en pantalla, para cumplir con la evidencia visual requerida).*

---

## ⏱️ 4:10–5:40 | Gino (Análisis Estático y Seguridad)
### Entregable 6 — Análisis Estático y Seguridad (Avance)

* **Pantalla:** Terminal dentro del directorio `backend/`.

* **Gino:**
  > "Para el análisis estático de código integramos Larastan, la extensión de PHPStan para Laravel, directamente como dependencia de desarrollo del backend. Ya está configurado en nivel 5 de estrictez sobre nuestra capa de aplicación."
  > 
  > *(Abre `backend/phpstan.neon` y muéstralo 2-3 segundos)*

```neon
includes:
    - vendor/larastan/larastan/extension.neon
parameters:
    paths:
        - app/
    level: 5
```

* **Gino:**
  > "Lo ejecutamos en vivo sobre el código real del proyecto:"

* **Acción en pantalla (Ejecutar en la terminal):**
  ```bash
  cd backend
  vendor/bin/phpstan analyse --memory-limit=1G
  ```
  *(Deja correr el comando y muestra el reporte de hallazgos tal cual sale — errores de tipos, llamadas a métodos indefinidos, propiedades sin verificar, etc. Si sale limpio, dilo explícitamente: es evidencia igual de válida).*

* **Gino:**
  > "Este es nuestro punto de partida de seguridad y calidad estática: un entorno de escaneo funcional y reproducible por cualquier integrante del equipo con un solo comando. Como siguientes pasos planeamos subir el nivel de estrictez, incorporar esta verificación al pipeline de integración continua, y complementar con una revisión dirigida a los riesgos más altos de nuestra matriz: invalidación de caché y validación de permisos."

---

## ⏱️ 5:40–6:00 | Cierre (Todos en Cámara)

* **Escena:** Plano conjunto, los 3 en cámara, sin diapositivas.

* **Diana:**
  > "En resumen: planificamos con base en riesgos reales, probamos el control de acceso, trazamos y corregimos cada defecto, medimos el resultado, y ya dimos el primer paso en análisis estático de seguridad."

* **Said y Gino:**
  > "Gracias por su atención."
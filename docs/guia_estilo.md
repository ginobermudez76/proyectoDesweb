# Guía de Calidad de Código y Estilo (Lint y Prettier)

Esta guía documenta las herramientas de calidad de código, análisis estático y formateo automático configuradas en el proyecto, así como los comandos necesarios para su uso.

---

## Backend (PHP / Laravel)

Para asegurar la homogeneidad y evitar errores de lógica en el backend, disponemos de dos herramientas integradas dentro del contenedor de la aplicación.

### 1. Formateador Automático: Laravel Pint
Laravel Pint es un formateador de estilo de código PHP basado en PHP-CS-Fixer que asegura que el código siga el estándar definido en `backend/pint.json`.

* **Comando para formatear todo el backend:**
  ```bash
  docker compose exec app composer format
  ```
* **Comando para comprobar errores de estilo sin modificar archivos:**
  ```bash
  docker compose exec app ./vendor/bin/pint --test
  ```

### 2. Análisis Estático: Larastan (PHPStan)
Larastan ejecuta análisis estático de código PHP en nivel 5 para detectar errores de tipo, llamadas a métodos inexistentes u otras malas prácticas lógicas.

* **Comando para analizar el backend:**
  ```bash
  docker compose exec app composer lint
  ```
  *(Este comando ya tiene configurada la bandera `--memory-limit=512M` para evitar problemas de memoria en el CLI del contenedor).*

---

## Frontend (HTML, CSS, JS)

Dado que no utilizamos Node.js en producción para mantener el frontend estático y ligero, hemos preparado configuraciones que se integran directamente con los editores de código (IDE) de los desarrolladores.

### Configuración del Editor (Recomendado)
Para que tu editor de código formatee y busque errores automáticamente al guardar los archivos:

#### En VS Code:
1. Instala las extensiones oficiales:
   * **Prettier - Code formatter** (por Esben Petersen)
   * **ESLint** (por Microsoft)
2. Abre tu configuración de VS Code (`settings.json`) y asegúrate de establecer Prettier como formateador predeterminado para HTML, CSS y JS:
   ```json
   "[javascript]": {
     "editor.defaultFormatter": "esbenp.prettier-vscode",
     "editor.formatOnSave": true
   },
   "[html]": {
     "editor.defaultFormatter": "esbenp.prettier-vscode",
     "editor.formatOnSave": true
   },
   "[css]": {
     "editor.defaultFormatter": "esbenp.prettier-vscode",
     "editor.formatOnSave": true
   }
   ```
3. El editor leerá automáticamente [.prettierrc](file:///c:/xampp/htdocs/proyectoDesweb/.prettierrc) y [.eslintrc.json](file:///c:/xampp/htdocs/proyectoDesweb/.eslintrc.json) de la raíz del proyecto.

#### En PhpStorm / WebStorm:
1. Ve a **Settings > Languages & Frameworks > JavaScript > Code Quality > ESLint** y actívalo en modo *Automatic*.
2. Ve a **Settings > Languages & Frameworks > Schemes > Prettier** y marca *On save* o configura un atajo de teclado.

---

## Ejecución de comandos de Frontend (Opcional - Requiere Node.js Local)

Si un desarrollador prefiere ejecutar los comandos desde consola localmente (fuera de Docker) o para la integración continua (CI), puede instalar Node.js y usar los siguientes comandos de forma manual:

1. **Instalar dependencias de desarrollo localmente:**
   ```bash
   npm install -g eslint prettier
   ```
   *(O instalar en la raíz local si se prefiere no global).*

2. **Formatear frontend con Prettier:**
   ```bash
   # Comprobar formato sin alterar archivos
   npx prettier --check "frontend/**/*"

   # Formatear archivos automáticamente
   npx prettier --write "frontend/**/*"
   ```

3. **Analizar Javascript con ESLint:**
   ```bash
   npx eslint "frontend/js/**/*.js"
   ```

# Guía de Despliegue en Producción

Este documento detalla los pasos necesarios para desplegar el "Sistema Web de Gestión de Incidencias Georreferenciadas" en un entorno de producción utilizando Docker, NGINX y PHP-FPM.

## 1. Requisitos Previos
- Servidor Linux (Ubuntu 22.04+ recomendado).
- Docker Engine y Docker Compose instalados.
- Un dominio o subdominio apuntando a la IP pública del servidor.
- Puertos `80` y `443` abiertos en el firewall.

## 2. Preparación del Entorno
Clona el repositorio en tu servidor:
```bash
git clone https://github.com/tu-usuario/proyectoDesweb.git /opt/proyectoDesweb
cd /opt/proyectoDesweb
```

Copia el archivo `.env.example` a `.env` y configura las credenciales de producción (contraseñas fuertes para DB y Redis, `APP_ENV=production`, `APP_DEBUG=false`, etc).
```bash
cp .env.example .env
```

## 3. Modificaciones para Producción en Docker

A diferencia del entorno de desarrollo local (que usa volúmenes para el código fuente), en producción el código debe estar empaquetado dentro de la imagen.

1. **Editar `docker-compose.yml`**:
   - Remover la sección `volumes: - ./:/var/www/html` de los servicios `app` y `web`.
   - Esto asegura que el contenedor use el código estático compilado en la imagen.

2. **Editar `Dockerfile` (Opcional, pero recomendado)**:
   - Asegúrate de copiar el código fuente al final del `Dockerfile`:
   ```dockerfile
   # Al final del archivo
   COPY . /var/www/html
   RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
   ```

## 4. Construcción y Ejecución

Construye las imágenes con la instrucción de no usar caché para tomar las últimas dependencias:
```bash
docker compose build --no-cache
```

Levanta los contenedores en modo detached (segundo plano):
```bash
docker compose up -d
```

## 5. Tareas Post-Despliegue

Dentro del contenedor `app`, debes instalar las dependencias de Composer (optimizadas) y generar la llave de la aplicación:

```bash
# Instalar dependencias sin paquetes de desarrollo
docker compose exec app composer install --optimize-autoloader --no-dev

# Generar llave de aplicación (solo la primera vez)
docker compose exec app php artisan key:generate

# Ejecutar migraciones de base de datos
docker compose exec app php artisan migrate --force

# Optimizar cachés de configuración y rutas
docker compose exec app php artisan config:cache
docker compose exec app php artisan route:cache
docker compose exec app php artisan view:cache
```

## 6. Configuración de SSL (HTTPS) con Certbot

Para producción es fundamental usar HTTPS. Se recomienda instalar `certbot` en el servidor host y crear un proxy reverso hacia el puerto `8000` de NGINX, o configurar NGINX dentro de Docker para manejar los certificados let's encrypt.

Ejemplo simplificado usando NGINX en el Host (Ubuntu):
```bash
sudo apt install nginx certbot python3-certbot-nginx
```
Crea un Server Block en el host que escuche el puerto 80 y haga `proxy_pass http://localhost:8000;`. Luego ejecuta:
```bash
sudo certbot --nginx -d tu-dominio.com
```

## 7. Mantenimiento y Logs

Para revisar el estado y los logs de la aplicación:
```bash
# Ver estado de los contenedores
docker compose ps

# Ver logs de PHP-FPM
docker compose logs -f app

# Ver logs de NGINX
docker compose logs -f web
```

FROM php:8.3-fpm-alpine

# Instalar dependencias del sistema operativo y dependencias de compilación
RUN apk add --no-cache \
    postgresql-dev \
    libzip-dev \
    unzip \
    curl \
    git \
    openssl-dev \
    linux-headers \
    ${PHPIZE_DEPS}

# Instalar extensiones nativas de PHP
RUN docker-php-ext-install pdo_pgsql zip

# Instalar extensiones vía PECL (Redis y MongoDB)
RUN pecl install redis mongodb \
    && docker-php-ext-enable redis mongodb

# Instalar Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Directorio de trabajo
WORKDIR /var/www/html

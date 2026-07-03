FROM php:8.3-fpm-alpine

# Dependances
RUN apk add --no-cache \
    bash \
    postgresql-dev \
    git \
    unzip \
    && docker-php-ext-install pdo pdo_pgsql

# Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

EXPOSE 9000
CMD ["php-fpm"]
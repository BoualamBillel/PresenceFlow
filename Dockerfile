FROM php:8.4-fpm-alpine

# Dependances
RUN apk add --no-cache \
    bash \
    postgresql-dev \
    git \
    unzip \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo pdo_pgsql gd

# Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Le projet est monte depuis l'hote : l'UID differe de celui du conteneur,
# et Git refuse alors d'operer sur le depot (protection safe.directory).
RUN git config --system --add safe.directory /var/www/html

EXPOSE 9000
CMD ["php-fpm"]
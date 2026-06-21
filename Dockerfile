# HomeWatt Dockerfile — multi-stage build
# Stage 1: Build frontend assets
FROM node:22-alpine AS vite
WORKDIR /build
COPY package.json package-lock.json ./
RUN npm ci --ignore-scripts
COPY vite.config.js ./
COPY resources/ resources/
RUN npm run build

# Stage 2: PHP dependencies
FROM composer:2 AS vendor
WORKDIR /build
COPY composer.json composer.lock ./
COPY database/ database/
RUN composer install --no-dev --no-interaction --no-scripts --prefer-dist --optimize-autoloader

# Stage 3: App runtime
FROM php:8.4-fpm-alpine AS app

RUN apk add --no-cache \
    nginx \
    supervisor \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    libwebp-dev \
    libxml2-dev \
    libzip-dev \
    oniguruma-dev \
    icu-dev \
    $PHPIZE_DEPS \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j$(nproc) \
    pdo_mysql \
    mbstring \
    xml \
    zip \
    intl \
    gd \
    opcache \
    bcmath \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del $PHPIZE_DEPS \
    && rm -rf /var/cache/apk/*

COPY docker/php/php.ini /usr/local/etc/php/conf.d/99-homewatt.ini
COPY docker/php/opcache.ini /usr/local/etc/php/conf.d/99-opcache.ini

WORKDIR /var/www
COPY --from=vendor /build/vendor/ vendor/
COPY . .
COPY --from=vite /build/public/build/ public/build/

RUN chown -R www-data:www-data storage bootstrap/cache \
    && php artisan optimize

EXPOSE 9000
CMD ["php-fpm", "-F"]

FROM php:8.4-fpm-alpine

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

COPY . /var/www
RUN chown -R www-data:www-data storage bootstrap/cache

EXPOSE 9000
CMD ["php-fpm", "-F"]

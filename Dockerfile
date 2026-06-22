# syntax=docker/dockerfile:1.7

ARG PHP_VERSION=8.4
ARG NODE_VERSION=22
ARG REDIS_EXTENSION_VERSION=6.2.0

FROM node:${NODE_VERSION}-alpine AS frontend

WORKDIR /var/www

RUN apk add --no-cache libc6-compat

COPY package.json package-lock.json ./
RUN npm ci --no-audit --no-fund

COPY . .
RUN npm run build


FROM php:${PHP_VERSION}-cli-alpine AS vendor

WORKDIR /var/www

RUN apk add --no-cache \
        git \
        icu-dev \
        libzip-dev \
        unzip \
    && docker-php-ext-install -j"$(nproc)" intl zip

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer
COPY . .

RUN composer install \
        --no-dev \
        --no-interaction \
        --no-progress \
        --no-scripts \
        --prefer-dist \
        --optimize-autoloader \
    && composer clear-cache


FROM php:${PHP_VERSION}-fpm-alpine AS production

ARG REDIS_EXTENSION_VERSION
ARG APP_RELEASE=unknown

ENV APP_RELEASE=${APP_RELEASE}

LABEL org.opencontainers.image.revision=${APP_RELEASE}

RUN apk add --no-cache \
        bash \
        curl \
        fcgi \
        freetype \
        icu-libs \
        libjpeg-turbo \
        libpng \
        libwebp \
        libzip \
        mysql-client \
        netcat-openbsd \
        oniguruma \
        su-exec \
    && apk add --no-cache --virtual .build-deps \
        $PHPIZE_DEPS \
        curl-dev \
        freetype-dev \
        icu-dev \
        libjpeg-turbo-dev \
        libpng-dev \
        libwebp-dev \
        libzip-dev \
        linux-headers \
        oniguruma-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j"$(nproc)" \
        bcmath \
        curl \
        exif \
        gd \
        intl \
        mbstring \
        opcache \
        pcntl \
        pdo_mysql \
        sockets \
        zip \
    && curl --fail --location --retry 5 \
        --output /tmp/phpredis.tar.gz \
        "https://github.com/phpredis/phpredis/archive/refs/tags/${REDIS_EXTENSION_VERSION}.tar.gz" \
    && mkdir /tmp/phpredis \
    && tar -xzf /tmp/phpredis.tar.gz --strip-components=1 -C /tmp/phpredis \
    && cd /tmp/phpredis \
    && phpize \
    && ./configure --enable-redis \
    && make -j"$(nproc)" \
    && make install \
    && docker-php-ext-enable redis \
    && apk del .build-deps \
    && rm -rf /tmp/pear /tmp/phpredis /tmp/phpredis.tar.gz

COPY <<'EOF' /usr/local/etc/php/conf.d/homewatt.ini
memory_limit = 256M
upload_max_filesize = 20M
post_max_size = 20M
max_execution_time = 300
expose_php = Off
opcache.enable = 1
opcache.enable_cli = 1
opcache.memory_consumption = 256
opcache.interned_strings_buffer = 16
opcache.max_accelerated_files = 20000
opcache.validate_timestamps = 0
opcache.save_comments = 1
EOF

RUN sed -i 's|^listen = .*|listen = 0.0.0.0:9000|' /usr/local/etc/php-fpm.d/www.conf

WORKDIR /var/www

COPY --from=vendor --chown=www-data:www-data /var/www /var/www
COPY --from=frontend --chown=www-data:www-data /var/www/public/build /var/www/public/build
COPY --chown=root:root docker-entrypoint.sh /usr/local/bin/docker-entrypoint
COPY --chown=root:root docker/healthcheck.sh /usr/local/bin/container-healthcheck

RUN chmod +x /usr/local/bin/docker-entrypoint /usr/local/bin/container-healthcheck \
    && ln -sf /var/www/storage/app/public /var/www/public/storage \
    && mkdir -p \
        storage/app/public \
        storage/app/private \
        storage/framework/cache/data \
        storage/framework/sessions \
        storage/framework/views \
        storage/logs \
        bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache

EXPOSE 9000

ENTRYPOINT ["docker-entrypoint"]
CMD ["php-fpm"]


FROM nginx:1.27-alpine AS web

ARG APP_RELEASE=unknown

LABEL org.opencontainers.image.revision=${APP_RELEASE}

COPY docker/nginx/default.conf /etc/nginx/conf.d/default.conf
COPY public /var/www/public
COPY --from=frontend /var/www/public/build /var/www/public/build
RUN ln -sf /var/www/storage/app/public /var/www/public/storage

EXPOSE 80

HEALTHCHECK --interval=10s --timeout=3s --start-period=10s --retries=5 \
    CMD wget -q -O /dev/null http://127.0.0.1/up || exit 1

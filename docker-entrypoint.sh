#!/usr/bin/env sh
set -eu

role="${CONTAINER_ROLE:-app}"

log() {
    printf '[homewatt:%s] %s\n' "$role" "$1"
}

run_as_app() {
    if [ "$(id -u)" = "0" ]; then
        su-exec www-data "$@"
    else
        "$@"
    fi
}

wait_for_service() {
    host="$1"
    port="$2"
    attempts="${3:-60}"

    log "Waiting for ${host}:${port}"

    i=1
    while [ "$i" -le "$attempts" ]; do
        if nc -z "$host" "$port" >/dev/null 2>&1; then
            log "${host}:${port} is available"
            return 0
        fi

        i=$((i + 1))
        sleep 2
    done

    log "Timed out waiting for ${host}:${port}"
    return 1
}

mkdir -p \
    storage/app/public \
    storage/app/private \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache

if [ "$(id -u)" = "0" ]; then
    chown -R www-data:www-data storage bootstrap/cache
    chmod -R ug+rwX storage bootstrap/cache
fi

# Never reuse Laravel bootstrap caches copied from an older deployment.
rm -f bootstrap/cache/*.php

case "$role" in
    app|queue|queue-ai|scheduler)
        if [ -n "${DB_HOST:-}" ]; then
            wait_for_service "$DB_HOST" "${DB_PORT:-3306}"
        fi
        if [ -n "${REDIS_HOST:-}" ]; then
            wait_for_service "$REDIS_HOST" "${REDIS_PORT:-6379}"
        fi
        ;;
esac

run_as_app php artisan package:discover --ansi

if [ "$role" = "app" ]; then
    if [ "${APP_ENV:-production}" = "production" ]; then
        log "Building Laravel production caches"
        run_as_app php artisan optimize
    else
        log "Clearing Laravel caches"
        run_as_app php artisan optimize:clear
    fi
fi

log "Executing: $*"

if [ "$(id -u)" = "0" ] && [ "${1:-}" != "php-fpm" ]; then
    exec su-exec www-data "$@"
fi

exec "$@"

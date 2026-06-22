#!/usr/bin/env sh
set -eu

log() {
    printf '[homewatt:healthcheck] %s\n' "$1"
}

APP_ENV="${APP_ENV:-production}"

case "${CONTAINER_ROLE:-app}" in
    app)
        if [ "$APP_ENV" = "production" ]; then
            SCRIPT_NAME=/ SCRIPT_FILENAME=/var/www/public/index.php REQUEST_METHOD=GET cgi-fcgi -bind -connect 127.0.0.1:9000 >/dev/null 2>&1
        else
            pgrep -x php-fpm >/dev/null 2>&1
        fi
        ;;
    queue|queue-ai)
        pgrep -x php >/dev/null 2>&1 && pgrep -f "queue:work" >/dev/null 2>&1
        ;;
    scheduler)
        pgrep -x php >/dev/null 2>&1 && pgrep -f "schedule:work" >/dev/null 2>&1
        ;;
    *)
        exit 0
        ;;
esac

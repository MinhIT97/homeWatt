#!/usr/bin/env sh
set -eu

role="${CONTAINER_ROLE:-app}"

process_is_running() {
    pattern="$1"

    for command_line in /proc/[0-9]*/cmdline; do
        if tr '\000' ' ' <"$command_line" 2>/dev/null | grep -F "$pattern" >/dev/null 2>&1; then
            return 0
        fi
    done

    return 1
}

case "$role" in
    app)
        nc -z 127.0.0.1 9000
        ;;
    queue)
        process_is_running "artisan queue:work --queue=default"
        ;;
    queue-ai)
        process_is_running "artisan queue:work --queue=ai"
        ;;
    scheduler)
        process_is_running "artisan schedule:work"
        ;;
    *)
        exit 1
        ;;
esac

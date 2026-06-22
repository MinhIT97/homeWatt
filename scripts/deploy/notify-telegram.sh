#!/usr/bin/env sh
set -eu

status="${1:?Deployment status is required}"
release="${2:?Release is required}"
details="${3:-}"

if [ -z "${TELEGRAM_BOT_TOKEN:-}" ] || [ -z "${TELEGRAM_DEPLOY_CHAT_ID:-}" ]; then
    echo 'Telegram deployment notification skipped: credentials are not configured.'
    exit 0
fi

message="HomeWatt deploy ${status}
Release: ${release}
Workflow: ${GITHUB_SERVER_URL:-https://github.com}/${GITHUB_REPOSITORY:-unknown}/actions/runs/${GITHUB_RUN_ID:-unknown}"

if [ -n "$details" ]; then
    message="$message
Details: $details"
fi

curl --fail --silent --show-error \
    --connect-timeout 10 \
    --max-time 30 \
    --request POST \
    --data-urlencode "chat_id=$TELEGRAM_DEPLOY_CHAT_ID" \
    --data-urlencode "text=$message" \
    "https://api.telegram.org/bot${TELEGRAM_BOT_TOKEN}/sendMessage" >/dev/null

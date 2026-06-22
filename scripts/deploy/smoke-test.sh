#!/usr/bin/env sh
set -eu

base_url="${1:?Base URL is required}"
expected_release="${2:?Expected release is required}"
base_url="${base_url%/}"
temp_dir="$(mktemp -d)"

cleanup() {
    rm -rf "$temp_dir"
}

trap cleanup EXIT

curl --fail --silent --show-error --connect-timeout 10 --max-time 30 --retry 5 --retry-delay 2 \
    "$base_url/up" >/dev/null

curl --fail --silent --show-error --connect-timeout 10 --max-time 30 --retry 5 --retry-delay 2 \
    --dump-header "$temp_dir/version.headers" \
    "$base_url/version" >"$temp_dir/version.json"
grep -F "\"release\":\"$expected_release\"" "$temp_dir/version.json" >/dev/null
grep -i '^cache-control:' "$temp_dir/version.headers" | grep -qi 'no-store'

curl --fail --silent --show-error --connect-timeout 10 --max-time 30 --retry 5 --retry-delay 2 \
    "$base_url/login" >"$temp_dir/login.html"

asset_url="$(grep -oE '(https?://[^"[:space:]]+)?/build/assets/[^"[:space:]]+\.(css|js)' "$temp_dir/login.html" | head -n 1 || true)"
if [ -z "$asset_url" ]; then
    echo 'No built CSS or JavaScript asset was found on the login page.' >&2
    exit 1
fi

case "$asset_url" in
    http://*|https://*) ;;
    *) asset_url="$base_url$asset_url" ;;
esac

curl --fail --silent --show-error --connect-timeout 10 --max-time 30 --retry 5 --retry-delay 2 \
    "$asset_url" >/dev/null

echo "Smoke test passed for release $expected_release"

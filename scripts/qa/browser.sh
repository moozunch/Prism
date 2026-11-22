#!/usr/bin/env bash
set -euo pipefail
cd "$(dirname "${BASH_SOURCE[0]}")/../.."

output="$(realpath -m "${1:?Provide an evidence directory}")"
mkdir -p "$output/screenshots"
scratch="$(mktemp -d "${TMPDIR:-/tmp}/prism-qa.XXXXXXXX")"
server=''
cleanup() {
    if [[ -n "$server" ]]; then
        kill "$server" 2>/dev/null || true
        wait "$server" 2>/dev/null || true
    fi
    rm -rf -- "$scratch"
}
trap cleanup EXIT
export APP_ENV=testing APP_DEBUG=false APP_KEY="$(php -r 'echo "base64:".base64_encode(random_bytes(32));')"
export DB_CONNECTION=sqlite DB_DATABASE="$scratch/database.sqlite" DB_URL=''
export APP_CONFIG_CACHE="$scratch/config.php" APP_ROUTES_CACHE="$scratch/routes.php"
export LARAVEL_STORAGE_PATH="$scratch/storage"
export VIEW_COMPILED_PATH="$scratch/views" LOG_CHANNEL=stderr
export GOOGLE_CALENDAR_ENABLED=false GOOGLE_DRIVE_ENABLED=false MAIL_MAILER=array QUEUE_CONNECTION=sync
export SESSION_DRIVER=database CACHE_STORE=database
export PRISM_QA_PASSWORD="$(php -r 'echo bin2hex(random_bytes(18));')"
export PRISM_QA_FIXTURES="$scratch/fixtures.json"
export APP_URL="http://127.0.0.1:${PRISM_QA_PORT:-8010}"
unset PHP_CLI_SERVER_WORKERS
mkdir -p "$VIEW_COMPILED_PATH" "$LARAVEL_STORAGE_PATH/framework/"{cache/data,sessions,views}
touch "$DB_DATABASE"
if ! php artisan migrate --force --no-interaction > "$scratch/migrate.log" 2>&1; then
    cat "$scratch/migrate.log"
    exit 1
fi
php artisan config:cache --no-interaction > /dev/null
php tests/Browser/seed.php
test -f public/build/manifest.json
router="$PWD/vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php"
(cd public && exec php -S "127.0.0.1:${PRISM_QA_PORT:-8010}" "$router") > "$scratch/server.log" 2>&1 &
server=$!
for attempt in {1..40}; do
    kill -0 "$server"
    if curl --noproxy '*' -fsS "$APP_URL/admin/login" -o /dev/null 2>/dev/null; then
        break
    fi
    sleep 0.25
done
curl --noproxy '*' -fsS "$APP_URL/admin/login" -o /dev/null
node tests/Browser/prism.mjs "$output"

#!/usr/bin/env bash
set -euo pipefail
cd "$(dirname "${BASH_SOURCE[0]}")/../.."
evidence="$(realpath -m "${1:?Provide a QA output directory}")"
mkdir -p "$evidence"
export APP_ENV=testing APP_DEBUG=false
export APP_KEY="$(php -r 'echo "base64:".base64_encode(random_bytes(32));')"
export DB_CONNECTION=sqlite DB_DATABASE=:memory: DB_URL=''
export CACHE_STORE=array SESSION_DRIVER=array MAIL_MAILER=array QUEUE_CONNECTION=sync
export GOOGLE_CALENDAR_ENABLED=false GOOGLE_DRIVE_ENABLED=false
export APP_CONFIG_CACHE="$evidence/nonexistent-config.php"
test ! -e "$APP_CONFIG_CACHE"

php vendor/bin/pest --colors=never --log-junit "$evidence/junit.xml" 2>&1 | tee "$evidence/pest.log"

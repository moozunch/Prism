#!/usr/bin/env bash
set -euo pipefail
cd "$(dirname "${BASH_SOURCE[0]}")/../.."
test -f vendor/autoload.php
test -f public/build/manifest.json
scratch="$(mktemp -d)"
name="prism-qa-$(basename "$scratch" | tr -cd '[:alnum:]')"
image="prism-qa:$(git rev-parse --short HEAD)"
network_started=false
mysql_started=false
app_started=false
cleanup() {
    if "$app_started"; then docker rm -fv "$name-app" >/dev/null; fi
    if "$mysql_started"; then docker rm -fv "$name-db" >/dev/null; fi
    if "$network_started"; then docker network rm "$name" >/dev/null; fi
    rm -rf -- "$scratch"
}
trap cleanup EXIT
umask 077
root_password="$(openssl rand -hex 20)"
db_password="$(openssl rand -hex 20)"
admin_password="$(openssl rand -hex 20)"
cat > "$scratch/mysql.env" <<EOF
MYSQL_ROOT_PASSWORD=$root_password
MYSQL_USER=prism_qa
MYSQL_PASSWORD=$db_password
MYSQL_DATABASE=prism_perf_qa
EOF
cat > "$scratch/app.env" <<EOF
APP_ENV=testing
APP_DEBUG=false
APP_KEY=base64:$(openssl rand -base64 32)
APP_URL=http://127.0.0.1:8000
DB_CONNECTION=mysql
DB_URL=
DB_HOST=$name-db
DB_PORT=3306
DB_DATABASE=prism_perf_qa
DB_USERNAME=prism_qa
DB_PASSWORD=$db_password
GOOGLE_CALENDAR_ENABLED=false
GOOGLE_DRIVE_ENABLED=false
SESSION_DRIVER=database
CACHE_STORE=array
MAIL_MAILER=array
QUEUE_CONNECTION=sync
PRISM_QA_PASSWORD=$admin_password
EOF
docker build -t "$image" .
docker network create --internal "$name" >/dev/null
network_started=true
docker run -d --name "$name-db" --network "$name" --tmpfs /var/lib/mysql \
    --env-file "$scratch/mysql.env" mysql:8.0 >/dev/null
mysql_started=true
docker run -d --name "$name-app" --network "$name" --env-file "$scratch/app.env" \
    "$image" sleep infinity >/dev/null
app_started=true
docker cp vendor "$name-app:/var/www/vendor"
docker cp public/build/. "$name-app:/var/www/public/build"
docker exec "$name-app" php-fpm -t
docker exec "$name-app" composer check-platform-reqs
for attempt in {1..60}; do
    if docker exec "$name-db" sh -c 'MYSQL_PWD="$MYSQL_PASSWORD" mysql --protocol=tcp -u "$MYSQL_USER" "$MYSQL_DATABASE" -Nse "SELECT VERSION()"' 2>/dev/null; then
        break
    fi
    sleep 1
done
docker exec "$name-db" sh -c 'MYSQL_PWD="$MYSQL_PASSWORD" mysql --protocol=tcp -u "$MYSQL_USER" "$MYSQL_DATABASE" -Nse "SELECT DATABASE(), COUNT(*) FROM information_schema.tables WHERE table_schema=DATABASE()"' |
    grep -Eq '^prism_perf_qa[[:space:]]+0$'
docker exec "$name-app" php tools/benchmark.php --mysql-test
docker exec "$name-app" php artisan migrate:status --no-interaction
docker exec "$name-app" php artisan db:seed --class=RoleSeeder --no-interaction --force
docker exec "$name-app" php -r '
    require "vendor/autoload.php";
    $app = require "bootstrap/app.php";
    $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
    $user = App\Models\User::factory()->create([
        "name" => "QA Container Admin", "email" => "qa-container@example.test",
        "password" => getenv("PRISM_QA_PASSWORD"),
    ]);
    $user->assignRole("admin");
    if (! $user->canAccessPanel(Filament\Facades\Filament::getPanel("admin"))) exit(1);
    echo "Disposable Filament admin can access panel.", PHP_EOL;
'
docker exec -d "$name-app" php artisan serve --host=127.0.0.1 --port=8000 --no-reload
for attempt in {1..20}; do
    if docker exec "$name-app" curl -fsS -o /dev/null http://127.0.0.1:8000/ 2>/dev/null; then
        break
    fi
    sleep 0.5
done
for path in / /admin /admin/login; do
    docker exec "$name-app" curl -fsSL -o /dev/null -w "$path %{http_code}\n" "http://127.0.0.1:8000$path"
done
theme="$(docker exec "$name-app" php -r 'echo json_decode(file_get_contents("public/build/manifest.json"), true)["resources/css/filament/admin/theme.css"]["file"];')"
docker exec "$name-app" curl -fsS -o /dev/null -w 'Compiled theme %{http_code}\n' "http://127.0.0.1:8000/build/$theme"

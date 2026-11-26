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
web_created=false
cleanup() {
    if "$web_created"; then docker rm -fv "$name-web" >/dev/null || true; fi
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
APP_URL=http://$name-web
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
git archive --format=tar HEAD | docker build -t "$image" -
docker network create --internal "$name" >/dev/null
network_started=true
docker run -d --name "$name-db" --network "$name" --tmpfs /var/lib/mysql \
    --env-file "$scratch/mysql.env" mysql:8.0 >/dev/null
mysql_started=true
docker run -d --name "$name-app" --network "$name" --network-alias app --env-file "$scratch/app.env" \
    "$image" sleep infinity >/dev/null
app_started=true
docker cp vendor "$name-app:/var/www/vendor"
docker exec "$name-app" mkdir -p /var/www/public/build
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
docker exec "$name-app" php artisan migrate --force --no-interaction
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
    $member = App\Models\User::factory()->create(["email" => "qa-member-container@example.test"]);
    $member->assignRole("member");
    [$visible, $hidden] = App\Models\Project::orderBy("id")->take(2)->get()->all();
    $visible->members()->attach($member);
    if (! $member->can("view", $visible) || $member->can("view", $hidden)) exit(1);
    echo "MySQL project ownership policy verified.", PHP_EOL;
'
docker exec -e QUEUE_CONNECTION=database "$name-app" php -r '
    require "vendor/autoload.php";
    $app = require "bootstrap/app.php";
    $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
    Illuminate\Support\Facades\Queue::connection("database")
        ->push(new App\Jobs\SyncGoogleCalendarTask(0), "", "prism-qa");
'
docker exec -e QUEUE_CONNECTION=database "$name-app" php artisan queue:work database --queue=prism-qa --once --tries=1
queue_count="$(docker exec "$name-db" sh -c 'MYSQL_PWD="$MYSQL_PASSWORD" mysql --protocol=tcp -u "$MYSQL_USER" "$MYSQL_DATABASE" -Nse "SELECT (SELECT COUNT(*) FROM jobs WHERE queue=\"prism-qa\") + (SELECT COUNT(*) FROM failed_jobs WHERE queue=\"prism-qa\")"')"
test "$queue_count" = 0
printf 'MySQL worker consumed one job; no pending or failed QA jobs.\n'

docker exec "$name-db" sh -c 'MYSQL_PWD="$MYSQL_PASSWORD" mysqldump --protocol=tcp -u "$MYSQL_USER" --single-transaction --no-tablespaces --set-gtid-purged=OFF "$MYSQL_DATABASE"' > "$scratch/backup.sql"
docker exec "$name-db" sh -c 'MYSQL_PWD="$MYSQL_ROOT_PASSWORD" mysql -u root -e "CREATE DATABASE prism_restore_qa"'
docker exec -i "$name-db" sh -c 'MYSQL_PWD="$MYSQL_ROOT_PASSWORD" mysql -u root prism_restore_qa' < "$scratch/backup.sql"
restore_check="$(docker exec "$name-db" sh -c 'MYSQL_PWD="$MYSQL_ROOT_PASSWORD" mysql -u root prism_restore_qa -Nse "SELECT COUNT(*) FROM tickets"')"
test "$restore_check" = 200
printf 'Logical restore into disposable MySQL schema verified (%s tasks).\n' "$restore_check"

docker exec "$name-app" chown -R www-data:www-data storage bootstrap/cache
mkdir -p "$scratch/nginx-root/var/www/public"
docker cp "$name-app:/var/www/public/." "$scratch/nginx-root/var/www/public"
chmod -R a+rX "$scratch/nginx-root"
docker create --name "$name-web" --network "$name" nginx:alpine >/dev/null
web_created=true
docker cp nginx/conf.d/app.conf "$name-web:/etc/nginx/conf.d/default.conf"
docker cp "$scratch/nginx-root/var/." "$name-web:/var"
docker exec -d "$name-app" php-fpm -F
docker start "$name-web" >/dev/null
docker exec "$name-web" nginx -t
docker exec -u nginx "$name-web" test -r /var/www/public/index.php

origin="http://$name-web"
for attempt in {1..30}; do
    if docker exec "$name-app" curl --noproxy "$name-web" -fsS -o /dev/null "$origin/admin/login" 2>/dev/null; then
        break
    fi
    sleep 0.5
done
for path in / /admin /admin/login; do
    docker exec "$name-app" curl --noproxy "$name-web" -fsSL -o /dev/null -w "$path %{http_code} %{time_total}s\n" "$origin$path"
done
docker exec "$name-app" curl --noproxy "$name-web" -fsS "$origin/admin/login" > "$scratch/login.html"
grep -qi 'type="password"' "$scratch/login.html"
theme="$(docker exec "$name-app" php -r 'echo json_decode(file_get_contents("public/build/manifest.json"), true)["resources/css/filament/admin/theme.css"]["file"];')"
docker exec "$name-app" curl --noproxy "$name-web" -fsS -o /dev/null -w 'Compiled theme %{http_code} %{content_type}\n' "$origin/build/$theme"
printf 'Nginx to PHP-FPM via FastCGI, 12 anonymous /admin/login requests at concurrency 4\n'
seq 12 | xargs -n1 -P4 sh -c 'docker exec "$1" curl --noproxy "$2" -fsS -o /dev/null -w "%{http_code} %{time_total}\n" "http://$2/admin/login"' qa "$name-app" "$name-web"

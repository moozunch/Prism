#!/usr/bin/env bash
set -euo pipefail
cd "$(dirname "${BASH_SOURCE[0]}")/../.."
scratch="$(mktemp -d)"
trap 'rm -rf -- "$scratch"' EXIT
export APP_ENV=testing APP_DEBUG=false APP_CONFIG_CACHE="$scratch/config.php"
export APP_KEY="$(php -r 'echo "base64:".base64_encode(random_bytes(32));')"
export DB_CONNECTION=sqlite DB_DATABASE="$scratch/queue.sqlite" DB_URL=''
export GOOGLE_CALENDAR_ENABLED=false GOOGLE_CALENDAR_AUTO_SYNC_ENABLED=false
export CACHE_STORE=array MAIL_MAILER=array QUEUE_CONNECTION=database
touch "$DB_DATABASE"
php artisan migrate --force --no-interaction > "$scratch/migration.log"
php -r '
    require "vendor/autoload.php";
    $app = require "bootstrap/app.php";
    $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
    if (config("database.default") !== "sqlite" ||
        config("database.connections.sqlite.database") !== getenv("DB_DATABASE")) exit(1);
    Illuminate\Support\Facades\Queue::connection("database")
        ->push(new App\Jobs\SyncGoogleCalendarTask(0), "", "prism-qa");
    if (Illuminate\Support\Facades\DB::table("jobs")->where("queue", "prism-qa")->count() !== 1) exit(1);
'
php artisan queue:work database --queue=prism-qa --once --tries=1
php -r '
    require "vendor/autoload.php";
    $app = require "bootstrap/app.php";
    $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
    foreach (["jobs", "failed_jobs"] as $table) {
        if (Illuminate\Support\Facades\DB::table($table)->where("queue", "prism-qa")->exists()) exit(1);
    }
    echo "One job consumed, none pending or failed. Google disabled.", PHP_EOL;
'

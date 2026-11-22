#!/usr/bin/env bash
set -euo pipefail
cd "$(dirname "${BASH_SOURCE[0]}")/../.."
scratch="$(mktemp -d)"
trap 'rm -rf -- "$scratch"' EXIT
for deployment in a b; do
    (
        mkdir -p "$scratch/$deployment"
        export APP_ENV=testing APP_DEBUG=false DB_CONNECTION=sqlite DB_URL=''
        export DB_DATABASE="$scratch/$deployment/database.sqlite"
        export APP_CONFIG_CACHE="$scratch/$deployment/config.php"
        export APP_KEY="$(php -r 'echo "base64:".base64_encode(random_bytes(32));')"
        export CACHE_STORE=array SESSION_DRIVER=array MAIL_MAILER=array QUEUE_CONNECTION=sync
        touch "$DB_DATABASE"
        php artisan migrate --no-interaction --force > "$scratch/$deployment/migration.log"
        php -r '
            require "vendor/autoload.php";
            $app = require "bootstrap/app.php";
            $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
            if (config("database.default") !== "sqlite" ||
                config("database.connections.sqlite.database") !== getenv("DB_DATABASE")) exit(1);
            $deployment = $argv[1];
            $other = $deployment === "a" ? "b" : "a";
            App\Models\Project::create(["name" => "QA deployment ".$deployment, "ticket_prefix" => "QA"]);
            if (! App\Models\Project::where("name", "QA deployment ".$deployment)->exists() ||
                App\Models\Project::where("name", "QA deployment ".$other)->exists()) exit(1);
            echo "Deployment $deployment has its own project only.", PHP_EOL;
        ' "$deployment"
    )
done

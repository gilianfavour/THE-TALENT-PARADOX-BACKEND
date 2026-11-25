#!/bin/sh
# Boot script for the container: prepare env, build a fresh demo database, serve.
set -e
cd /app

[ -f .env ] || cp .env.example .env
if [ -z "$APP_KEY" ]; then
    php artisan key:generate --force --no-interaction
fi

mkdir -p database storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs
touch database/database.sqlite

php artisan migrate:fresh --seed --force --no-interaction
php artisan storage:link > /dev/null 2>&1 || true

exec php artisan serve --host=0.0.0.0 --port="${PORT:-8000}"

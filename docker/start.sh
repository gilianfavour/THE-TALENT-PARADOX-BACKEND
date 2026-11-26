#!/bin/sh
# Boot script for production (Render + Supabase Postgres).
# - Never drops or re-seeds the database.
# - Runs only pending migrations on each deploy.
set -e
cd /app

# Storage directories must exist inside the container
mkdir -p storage/framework/cache/data \
         storage/framework/sessions \
         storage/framework/views \
         storage/logs \
         bootstrap/cache

# Generate APP_KEY only if one was not injected via environment
if [ -z "$APP_KEY" ]; then
    php artisan key:generate --force --no-interaction
fi

# Cache config/routes for faster cold starts
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Run any pending migrations (safe to run on every deploy)
php artisan migrate --force --no-interaction

# Link storage (safe to call repeatedly)
php artisan storage:link > /dev/null 2>&1 || true

exec php artisan serve --host=0.0.0.0 --port="${PORT:-8000}"

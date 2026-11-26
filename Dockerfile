# TalentBridge API — Laravel 13 + Filament
# Production-ready for Render (free tier) with Supabase Postgres.
FROM php:8.4-cli

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        git unzip libicu-dev libzip-dev libpq-dev \
    && docker-php-ext-install intl zip pdo pdo_pgsql pgsql \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

# Install PHP dependencies first (layer-cached)
COPY composer.json composer.lock ./
RUN composer install --no-interaction --prefer-dist --no-scripts --no-autoloader --no-dev

# Copy the full application
COPY . .

RUN composer dump-autoload --optimize --no-scripts \
    && php artisan package:discover --ansi \
    && (php artisan filament:assets || true)

# Runtime defaults — secrets are injected via Render environment variables
ENV APP_ENV=production \
    APP_DEBUG=false \
    LOG_CHANNEL=stderr \
    DB_CONNECTION=pgsql \
    SESSION_DRIVER=file \
    CACHE_STORE=file \
    PHP_CLI_SERVER_WORKERS=4

EXPOSE 8000
CMD ["sh", "docker/start.sh"]

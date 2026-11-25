# TalentBridge API (Laravel 13 + Filament) - free-tier friendly single container.
# SQLite database is rebuilt and seeded with the demo cast on every start.
FROM php:8.4-cli

RUN apt-get update \
    && apt-get install -y --no-install-recommends git unzip libicu-dev libzip-dev \
    && docker-php-ext-install intl zip \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

COPY composer.json composer.lock ./
RUN composer install --no-interaction --prefer-dist --no-scripts --no-autoloader

COPY . .
RUN composer dump-autoload --optimize --no-scripts \
    && php artisan package:discover --ansi \
    && (php artisan filament:assets || true)

ENV APP_ENV=production \
    APP_DEBUG=false \
    LOG_CHANNEL=stderr \
    DB_CONNECTION=sqlite \
    SESSION_DRIVER=database \
    CACHE_STORE=database \
    PHP_CLI_SERVER_WORKERS=4

EXPOSE 8000
CMD ["sh", "docker/start.sh"]

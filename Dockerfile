# Production image for Render (or any Docker host).
# PHP-FPM + Nginx from serversideup/php, listening on port 8080.

############################################
# 1. PHP dependencies
############################################
FROM serversideup/php:8.4-fpm-nginx AS base

USER root
# intl: money formatting (Number::currency). pdo_pgsql, opcache and zip ship with the image.
RUN install-php-extensions intl
USER www-data

WORKDIR /var/www/html

FROM base AS vendor
COPY --chown=www-data:www-data composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction --no-progress

############################################
# 2. Front-end assets (Vite + Tailwind)
############################################
FROM node:22-alpine AS assets
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci --no-audit --no-fund
COPY vite.config.js ./
COPY resources ./resources
COPY app/Livewire ./app/Livewire
COPY public ./public
# Tailwind scans Laravel's pagination views too.
COPY --from=vendor /var/www/html/vendor/laravel/framework/src/Illuminate/Pagination/resources/views ./vendor/laravel/framework/src/Illuminate/Pagination/resources/views
RUN npm run build

############################################
# 3. The app
############################################
FROM base

ENV PHP_OPCACHE_ENABLE=1 \
    # On start: cache config, routes, views and events and link storage.
    # Migrations run once per deploy in Render's pre-deploy step instead.
    AUTORUN_ENABLED=true \
    AUTORUN_LARAVEL_MIGRATION=false

COPY --from=vendor --chown=www-data:www-data /var/www/html/vendor ./vendor
COPY --chown=www-data:www-data . .
COPY --from=assets --chown=www-data:www-data /app/public/build ./public/build

RUN mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
    && composer dump-autoload --optimize --no-dev --no-interaction \
    && php artisan package:discover --ansi

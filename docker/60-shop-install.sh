#!/bin/sh
# Runs at container start (serversideup/php entrypoint.d), after migrations.
# Only on the web service: the worker sets AUTORUN_ENABLED=false.
if [ "${AUTORUN_ENABLED:-false}" = "true" ]; then
    echo "🛒 Running first-run setup: php artisan shop:install"
    php "${APP_BASE_DIR:-/var/www/html}/artisan" shop:install || echo "⚠️ shop:install failed; the site will still start."
fi

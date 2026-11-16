#!/bin/sh
set -e

chown -R www-data:www-data storage bootstrap/cache

if [ "$1" = "php-fpm" ]; then
    if [ -z "$APP_KEY" ]; then
        generated_key="$(su-exec www-data php artisan key:generate --show)"
        export APP_KEY="$generated_key"

        if grep -q '^APP_KEY=' .env; then
            sed -i "s#^APP_KEY=.*#APP_KEY=${generated_key}#" .env
        else
            echo "APP_KEY=${generated_key}" >> .env
        fi
    fi

    fresh_install=false
    if ! su-exec www-data php artisan migrate:status >/dev/null 2>&1; then
        fresh_install=true
    fi

    su-exec www-data php artisan migrate --force

    if [ "$fresh_install" = "true" ]; then
        su-exec www-data php artisan db:seed --force
    fi

    exec "$@"
fi

exec su-exec www-data "$@"

#!/bin/sh
set -e

chown -R www-data:www-data storage bootstrap/cache

if [ "$1" = "php-fpm" ]; then
    exec "$@"
fi

exec su-exec www-data "$@"

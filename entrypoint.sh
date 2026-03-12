#!/bin/sh

chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache

echo "Waiting for database at $DB_HOST:$DB_PORT ..."

until mysqladmin ping -h"DB_HOST" -p"DB_PORT" --silent; do
      sleep 2
done

echo "running migrations"

sleep 5
# to put migrations inside CI/CD
php artisan migrate --force

php-fpm -F
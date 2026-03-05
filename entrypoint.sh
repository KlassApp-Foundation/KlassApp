#!/bin/sh

chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache

echo "running migrations"

sleep 5
# to put migrations inside CI/CD
php artisan migrate --force

php-fpm -F
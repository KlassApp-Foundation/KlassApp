#!/bin/sh

chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache

echo "running migrations"

sleep 5
php artisan migrate --force

php-fpm
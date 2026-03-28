#!/bin/sh

set -e #exit on every error

echo "Fixing Laravel permission (after volume mount)"

# Use absolute paths for reliability
chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache
chmod -R 775 /var/www/storage /var/www/bootstrap/cache

# Ensure all required subdirectories exist and are writable (-p create if not exist)
mkdir -p /var/www/storage/logs \
         /var/www/storage/framework/sessions \
         /var/www/storage/framework/views \
         /var/www/storage/framework/cache

chown -R www-data:www-data /var/www/storage/framework /var/www/storage/logs
chmod -R 775 /var/www/storage/framework /var/www/storage/logs

echo "Permissions fixed successfully."

# waiting for DB
echo "Waiting for database at $DB_HOST:$DB_PORT ..."
while ! nc -z $DB_HOST $DB_PORT; do
      sleep 2
done

echo "running migrations"

# to put migrations inside CI/CD
php artisan migrate --force

# clear caches on startup (helps after permission fixes)
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:clear

echo "starting PHP-FPM"
exec php-fpm -F
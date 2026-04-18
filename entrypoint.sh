#!/bin/sh

set -e #exit on every error

# Use absolute paths for reliability
# chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache
# chmod -R 775 /var/www/storage /var/www/bootstrap/cache

# Ensure all required subdirectories exist and are writable (-p create if not exist)
# mkdir -p /var/www/storage/logs \
#          /var/www/storage/framework/sessions \
#          /var/www/storage/framework/views \
#          /var/www/storage/framework/cache \
#          /var/www/bootstrap/cache

# chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache 2>/dev/null || true
# # Set group-writable permissions (775) without forcing ownership change
# chmod -R 775 /var/www/storage /var/www/bootstrap/cache 2>/dev/null || true
# make sure the current user (www-data) can write 
# chmod -R g+w /var/www/storage /var/www/bootstrap/cache 2>/dev/null || true
# waiting for DB
echo "Waiting for database at $DB_HOST:$DB_PORT ..."
while ! nc -z $DB_HOST $DB_PORT; do
      sleep 2
done

echo "running migrations"

# to put migrations inside CI/CD
# php artisan migrate:fresh --seed
php artisan migrate --force

# clear caches on startup (helps after permission fixes)
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:clear

echo "starting PHP-FPM"
exec php-fpm -F
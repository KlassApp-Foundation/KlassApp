#!/bin/sh

set -e #exit on every error

echo "Waiting for database DNS ($DB_HOST)..."
# Wait for DNS to resolve first
while ! getent hosts "$DB_HOST" > /dev/null 2>&1; do
     sleep 1
done     

echo "Waiting for database at ($DB_HOST:$DB_PORT) ..."
while ! nc -z "$DB_HOST" "$DB_PORT"; do
      sleep 1
done
echo "Database is ready!"

echo "Running post-install scripts..."
composer run-script post-autoload-dump --no-interaction 2>/dev/null || true

echo "Running migrations (safe)..."
php artisan migrate --force --no-interaction || true
# to put migrations inside CI/CD
# php artisan migrate:fresh --seed

# clear caches on startup (helps after permission fixes)
php artisan config:clear || true
php artisan cache:clear || true
php artisan view:clear || true
php artisan route:clear || true

echo "starting PHP-FPM"
exec php-fpm -F
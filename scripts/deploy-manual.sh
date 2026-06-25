#!/bin/bash
set -e

# ====================================
# KlassApp Deploy Script (No SSH Key)
# Server: root@46.101.111.131
# Use SSH password or add key via DigitalOcean panel
# ====================================

APP_SERVER="root@46.101.111.131"
APP_DIR="/var/www/klassapp"
GIT_BRANCH="main"

echo "========================================"
echo " KlassApp Deploy"
echo " Server: $APP_SERVER"
echo " Branch: $GIT_BRANCH"
echo "========================================"
echo ""
echo "This will SSH into the server and run the deploy."
echo "You may need to enter the server password."
echo ""

# Deploy via SSH
echo "[1/5] Connecting to server..."
ssh "$APP_SERVER" << REMOTE_SCRIPT
set -e
APP_DIR="/var/www/klassapp"
GIT_BRANCH="main"

cd "$APP_DIR"

echo "[2/5] Pulling latest code..."
git pull origin "$GIT_BRANCH" --ff-only

echo "[3/5] Installing dependencies..."
composer install --no-dev --optimize-autoloader --no-interaction

echo "[4/5] Clearing caches..."
php artisan cache:clear
php artisan config:clear
php artisan view:clear

echo "[5/5] Restarting services..."
systemctl reload php8.3-fpm || systemctl reload php-fpm
systemctl reload nginx

echo ""
echo "========================================"
echo " Deploy complete!"
echo " https://klassapp.xyz"
echo "========================================"
REMOTE_SCRIPT

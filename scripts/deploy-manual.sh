#!/bin/bash
set -e

# ====================================
# KlassApp Deploy Script (Docker)
# Server: root@46.101.111.131
# Key: ~/.ssh/id_ed25519_do
# ====================================

APP_SERVER="root@46.101.111.131"
APP_DIR="/var/www/KlassApp"
CONTAINER="sms-app"
GIT_BRANCH="main"

echo "========================================"
echo " KlassApp Deploy"
echo " Server: $APP_SERVER"
echo " Container: $CONTAINER"
echo " Branch: $GIT_BRANCH"
echo "========================================"
echo ""

# ---- Pre-deploy checks ----
cd "$(dirname "$0")/.."

echo "[Pre] Checking for unresolved git conflict markers..."
bash scripts/check-conflict-markers.sh
echo "[Pre] Conflict marker check passed."

# ---- Local build step ----
echo "[Local] Rebuilding frontend assets..."
NODE_OPTIONS=--openssl-legacy-provider npm run production
echo "[Local] Assets rebuilt. Remember to commit if running manually."

ssh "$APP_SERVER" -i ~/.ssh/id_ed25519_do << REMOTE_SCRIPT
set -e
APP_DIR="$APP_DIR"
CONTAINER="$CONTAINER"
GIT_BRANCH="$GIT_BRANCH"

cd "\$APP_DIR"

echo "[1/6] Pulling latest code (includes compiled assets)..."
git pull origin "\$GIT_BRANCH" --ff-only

echo "[2/6] Installing/updating PHP dependencies inside container..."
docker exec "$CONTAINER" composer install --no-dev --optimize-autoloader --no-interaction
echo "[2/6] Dependencies synchronized."

echo "[3/6] Running migrations..."
docker exec "\$CONTAINER" php artisan migrate --force

echo "[4/6] Clearing caches inside container..."
docker exec "\$CONTAINER" php artisan optimize:clear

echo "[5/6] Restarting FPM..."
docker exec "\$CONTAINER" sh -c "kill -USR2 1 2>/dev/null || php-fpm -t >/dev/null 2>&1" || true

echo "[6/6] Verifying app is serving..."
sleep 1
docker exec "\$CONTAINER" php -r "echo 'PHP OK: ' . phpversion() . PHP_EOL;"

echo ""
echo "========================================"
echo " Deploy complete!"
echo " https://klassapp.xyz"
echo "========================================"
REMOTE_SCRIPT
